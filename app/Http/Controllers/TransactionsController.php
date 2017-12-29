<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\User;
use Illuminate\Support\Facades\Validator;
use App\Organization;
use Flutterwave\Card;
use App\Transactions;
use GuzzleHttp\Client;
use App\TransactionLog;
use Flutterwave\Currencies;
use Flutterwave\Flutterwave;
use Illuminate\Http\Request;
use App\Services\HollaPayService;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller {

    var $BASE_URI_MONEYWAVE = "https://moneywave.herokuapp.com";

    public function index(Request $request, Organization $organization) {
        $limit = $request->input("limit");
        if ($limit > 0) {
            $transactions = Transactions::with('beneficiary')
                ->with('sender')
                ->where("organization_id", $organization->organization_id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } else {
            $transactions = Transactions::with('beneficiary')
                ->with('sender')
                ->where("organization_id", $organization->organization_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $status = "success";
        $message = "Transactions fetched";
        return response()->json(compact('status', 'message', 'transactions'));
    }

    public function send(Request $request, Organization $organization, HollaPayService $hollapay) {
        //========= GET POST VARIABLES =========
        //$from = $request->input('from');
        $val = Validator::make($request->all(),[
            'amount' => 'required:numeric',
            'reference' => 'required',
            'card_token' => 'required',
        ]);
        if($val->fails()){
            $error = implode("<br>", $val->errors->all());
            return response()->json(Utility::error($error));
        }

        $amount = $request->input('amount');
        $card_token = $request->input('card_token');
        $reference = $request->reference;

        //\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
        $code = "";

        //========= STORE TRANSACTION ==============
        $transaction = new Transactions();
        $transaction->reference = $reference;
        $transaction->organization_id = $organization->organization_id;
        $transaction->_from = $organization->organization_id;
        $transaction->_to = "Hollapay";
        $transaction->text = "Send Money Transaction from $organization->organization_id";
        $transaction->amount = $amount;
        $transaction->save();

        if (!isset($card_token)) {
            $card = [
                "card_no" => $request->input("card_number"),
                "cvv" => $request->input("cvv"),
                "expiry_month" => $request->input("expiry_month"),
                "expiry_year" => $request->input("expiry_year")
            ];
            $card_token = $hollapay->tokenize_card($card);
            if (is_array($card_token)) {//An error occured
                $status = "error";
                $message = $card_token["data"]["responsemessage"];
                return response()->json(Utility::returnError($message));
            }

            $tLog = new TransactionLog();
            $tLog->organization_id = $organization->organization_id;
            $tLog->transaction_id = $reference;
            $tLog->transaction_type = "Preauthorization";
            $tLog->amount = $amount;
            $tLog->status = "pending";
            $tLog->save();
            $result = $hollapay->preAuth($card_token, $amount);
            if ($result->isSuccessfulResponse()) {

                $response = $result->getResponseData();
                $authorizeId = $response["data"]["authorizeId"];
                $transactionreference = $response["data"]["transactionreference"];
                $tLog->reference = $transactionreference;
                $tLog->status = "successful";
                $tLog->save();

                $tLog = new TransactionLog();
                $tLog->organization_id = $organization->organization_id;
                $tLog->transaction_id = $reference;
                $tLog->transaction_type = "capture";
                $tLog->amount = $amount;
                $tLog->status = "pending";
                $tLog->save();

                $result = $hollapay->capture($transactionreference, $authorizeId, $amount);
                if ($result->isSuccessfulResponse()) {//Charged card Successffully
                    $organization->wallet = $organization->balance + $amount;
                    $organization->save();
                    $tLog->status = "successful";
                    $tLog->save();
                    $message = "Funds has been successfully transferred.";
                    return response()->json(Utility::return200($message));
                } else {
                    $tLog->status = "failed";
                    $tLog->save();
                    $error = "Could not Charge the Card";
                    $status = "error";
                    $message = $error;
                    return response()->json(Utility::returnError($message));
                }
            } else {
                $tLog->status = "failed";
                $tLog->save();
                $error = "Could not Preauthorize your card";
                $status = "error";
                $message = $error;
                $details = $result->getResponseMessage();
                return response()->json(Utility::returnError("$message - $details"));
            }
        }


        ################# USE EXISTING CARD #################
        else {
            $card_token = $request->card_token;
            if (!isset($card_token)) {
                return response()->json(Utility::returnError("The card_token parameter cannot be empty"));
            }
            $result = $hollapay->preAuth($card_token, $amount);

            $tLog = new TransactionLog();
            $tLog->organization_id = $organization->organization_id;
            $tLog->transaction_id = $reference;
            $tLog->transaction_type = "Preauthorization";
            $tLog->amount = $amount;
            $tLog->status = "pending";
            $tLog->save();
            if ($result->isSuccessfulResponse()) {

                $response = $result->getResponseData();
                $authorizeId = $response["data"]["authorizeId"];
                $transactionreference = $response["data"]["transactionreference"];
                $tLog->reference = $transactionreference;
                $tLog->status = "successful";
                $tLog->save();

                $tLog = new TransactionLog();
                $tLog->organization_id = $organization->organization_id;
                $tLog->transaction_id = $reference;
                $tLog->transaction_type = "capture";
                $tLog->amount = $amount;
                $tLog->status = "pending";
                $tLog->save();

                //=========== LOG IT ===========

                $result = $hollapay->capture($transactionreference, $authorizeId, $amount);
                if ($result->isSuccessfulResponse()) {//Charged card Successffully
                    $tLog->status = "successful";
                    $tLog->save();
                    //=== Get the User and Compute new Balance ===
                    $organization->wallet = $organization->balance + $amount;
                    $organization->save();
                    $message = "Funds has been successfully transferred.";
                    return response()->json(Utility::return200($message));
                } else {
                    $tLog->status = "failed";
                    $tLog->save();
                    $message = "Could not Charge the Card";
                    return response()->json(Utility::returnError($message));
                }
            } else {
                $tLog->status = "failed";
                $tLog->save();
                $response = $result->getResponseData();
                $authorizeId = $response["data"]["authorizeId"];
                $transactionreference = $response["data"]["transactionreference"];
                $error = "Could not Preauthorize your card: " . $response["data"]["responsemessage"];
                return response()->json(Utility::returnError($error));
            }
        }
    }

    //######################### WITHDRAW TO BANK ACCOUNT #########################
    public function withraw_bank(Request $request, Organization $organization) {
        //========= VALIDATE REQUEST =========
        $validate = Validator::make($request->all(), [
            'amount' => 'required|min:3|max:6',
            'acc_number' => 'required|min:10|max:10',
            'bank_code' => 'required|min:3|max:5',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            $status = "error";
            $message = "Validation Errors";
            return response()->json(compact('status', 'message', 'errors'));
        }

        //========= GET POST VARIABLES =========
        $amount = $request->input('amount');
        $acc_number = $request->input('acc_number');
        $bank = $request->input('bank_code');

        //========= CHECK IF USER HAS ENOUGH MONEY =========
        if ($organization->balance >= $amount) {
            //========= SEND REQUEST =========
            $token = $this->getAccessToken();
            try {
                $client = new Client(['base_uri' => $this->BASE_URI_MONEYWAVE]);
                $response = $client->request('POST', '/v1/disburse', [
                    'headers' => [
                        'Authorization' => $token],
                    'json' => [
                        'lock' => 'Password@123',
                        'amount' => $amount,
                        'bankcode' => $bank,
                        'accountNumber' => $acc_number,
                        'senderName' => "HollaPay",
                        'currency' => "NGN"],
                    'http_errors' => false
                ]);
                $response = $response->getBody()->getContents();
                $response = json_decode($response, true);

                if ($response["status"] == "error") {
                    $error = $response["data"] . "";
                    return response()->json(compact('error'));
                } else {
                    $message = $response["data"]["data"];
                    $message = $message["responsemessage"];
                    $ref = $message["uniquereference"];

                    try {
                        $transaction = new Transactions();
                        $transaction->reference = str_random(64);
                        $transaction->organization_id = $organization->organization_id;
                        $transaction->_from = 'Wallet';
                        $transaction->_to = 'Bank';
                        $transaction->text = "You withdrew " . $amount . " naira to your bank acccount";
                        $transaction->transaction_type = "Debit";
                        $transaction->save();

                        //========= DEBIT THE USER'S BALANCE ON HOLLAPAY =========
                        $this_user = Organization::find($organization->id);
                        $this_user->balance -= $amount;
                        $this_user->save();
                    }
                    catch (\Exception $ex){
                        return response(Utility::returnError('A database error occurred', $ex->getMessage()));
                    }
                    return response(Utility::returnSuccess('Transaction successful', $ref));
                }
            } catch (\ServerException $ex) {
                print_r($ex);
            }
        } else {
            return response(Utility::returnError('Insufficient funds', $organization));
        }
    }

//######################### WITHDRAW TO ATM #########################
    public function withraw_atm(Request $request, User $organization) {
        //========= VALIDATE REQUEST =========
        $validate = Validator::make($request->all(), [
            'amount' => 'required|min:3|max:6',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            $status = "error";
            $message = "Validation errors";
            return response()->json(compact('status', 'message', 'errors'));
            ;
        }

        //========= GET POST VARIABLES =========
        $amount = $request->input('amount');

        //========= CHECK IF USER HAS ENOUGH MONEY =========
        if ($organization->balance >= $amount) {

            //========= TRANSFER FROM HOLLAPAY TO USER =========
            //========= STORE TRANSACTION ==============
            $transaction = new Transactions();
            $transaction->organization_id = $organization->id;
            $transaction->reference = str_random(64);
            $transaction->_from = $organization->organization_id;
            $transaction->_to = $organization->organization_id;
            $transaction->text = "You withdrew " . $amount . " naira";
            $transaction->transaction_type = "Debit";
            $transaction->save();

            //========= DEBIT THE USER'S BALANCE ON HOLLAPAY =========
            //$this_user = User::find($user->user_id);
            //$balance = $this_user->balance;
            //$balance = $balance - $amount;
            //$this_user->balance = $balance;
            //$this_user->save();

            //========= SEND RRESPONSE TO USER =========
            $status = "success";
            $message = "Transaction Successful";
            //$reference = $ref;
            //return response()->json(compact('status', 'message', 'reference'));
        } else {
            $error = "Insufficient funds";
            return response()->json(compact('error'));
        }
    }

//######################### GENERATE FACTS #########################
    public function generateFacts(Organization $organization) {
        $currentFactType = $this->generateFactType($organization);
        $currentFactType = "spendPercent";

        if ($currentFactType == "highestSent") {
            $transactions = Transactions::select('_to', DB::raw('count(*) as frequency'))
                ->groupBy('_to')
                ->orderBy('_to', 'desc')
                ->where('_from', $organization->user_id)
                ->limit(1)
                ->first();
            $fact = "You have sent more money to " . $transactions->_to;
            return response()->json(compact('fact'));
        } else if ($currentFactType == "highestReceived") {
            $transactions = Transactions::select('_from', DB::raw('count(*) as frequency'))
                ->groupBy('_from')
                ->orderBy('_from', 'desc')
                ->where('_to', $organization->user_id)
                ->limit(1)
                ->first();
            $fact = "You have received more money from " . $transactions->_from;
            return response()->json(compact('fact'));
        } else if ($currentFactType == "spendPercent") {
            $received = Transactions::where('user_id', $organization->user_id)
                ->where('transaction_type', 'Credit')
                ->sum('amount');

            $spent = Transactions::where('user_id', $organization->user_id)
                ->where('transaction_type', 'Debit')
                ->sum('amount');
            $perc = ($spent * 100) / $received;
            $fact = "You have spent " . $perc . "% (₦" . $spent . ".00) of your received money";
            $status = "success";
            $message = "Facts fetched successfully";
            return response()->json(compact('status', 'message', 'fact'));
        }

        $fact = "You have not performed any transaction";
        $status = "success";
        $message = $fact;
        return response()->json(compact('status', 'message', 'fact'));
    }

    private function generateFactType($user) {
        $fact_type = array(
            "highestSent",
            "highestReceived",
            "spendPercent",
            "spendPercentMonth",
            "spendPercentAllTime",
            "unusualSpending");
        $currentFactType = $fact_type[array_rand($fact_type)];

        return $currentFactType;
    }

    function getAccessToken() {
        //Get Access Key
        $client = new Client(['base_uri' => $this->BASE_URI_MONEYWAVE]);
        $response = $client->request('POST', '/v1/merchant/verify', [
            'json' => [
                'apiKey' => 'ts_XMRPIE38TACPUGJP81GW',
                'secret' => 'ts_6BS4TFX6YOC5S358TN8Y6AJ8OJ74VS']
        ]);
        $response = $response->getBody()->getContents();
        $token = json_decode($response, true);
        return $token["token"];
    }

    function getAccountName(Request $request) {
        $acc_num = $request->input("acc_number");
        $bank_code = $request->input("bank_code");
        //Get Access Key
        $apiKey = "tk_TM4fIPpCrmy9gJcYq280";
        $merchantKey = "tk_8hBUK9Vx4u";
        $client = new Client(['base_uri' => "http://staging1flutterwave.co:8080"]);
        $response = $client->request('POST', '/pwc/rest/pay/resolveaccount', [
            'json' => [
                'destbankcode' => $this->encrypt3Des($bank_code, $apiKey),
                'recipientaccount' => $this->encrypt3Des($acc_num, $apiKey),
                'merchantid' => $merchantKey]
        ]);
        $response = $response->getBody()->getContents();
        $response = json_decode($response, true);
        $data = $response["data"];
        return response()->json(compact('data'));
    }

    public function encrypt3Des($data, $key) {
        //Generate a key from a hash
        $key = md5(utf8_encode($key), true);

        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);

        //Pad for PKCS7
        $blockSize = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($data);
        $pad = $blockSize - ($len % $blockSize);
        $data = $data . str_repeat(chr($pad), $pad);

        //Encrypt data
        $encData = mcrypt_encrypt('tripledes', $key, $data, 'ecb');

        //return $this->strToHex($encData);

        return base64_encode($encData);
    }

    private function preAuth($token, $amount) {
        $currency = Currencies::NAIRA;
        $merchantKey = "tk_8hBUK9Vx4u";
        $apiKey = "tk_TM4fIPpCrmy9gJcYq280";
        $env = "staging";

        Flutterwave::setMerchantCredentials($merchantKey, $apiKey, $env);
        $result = Card::preAuthorize($token, $amount, $currency);
        return $result;
    }

    private function capture($authRef, $transId, $amount) {
        $merchantKey = "tk_8hBUK9Vx4u";
        $apiKey = "tk_TM4fIPpCrmy9gJcYq280";
        $env = "staging";

        Flutterwave::setMerchantCredentials($merchantKey, $apiKey, $env);
        $currency = Currencies::NAIRA;
        $result = Card::capture($authRef, $transId, $amount, $currency);
        return $result;
    }

    public static function getCardBrand($pan, $include_sub_types = false) {
        //maximum length is not fixed now, there are growing number of CCs has more numbers in length, limiting can give false negatives atm
        //these regexps accept not whole cc numbers too
        //visa
        $visa_regex = "/^4[0-9]{0,}$/";
        $vpreca_regex = "/^428485[0-9]{0,}$/";
        $postepay_regex = "/^(402360|402361|403035|417631|529948){0,}$/";
        $cartasi_regex = "/^(432917|432930|453998)[0-9]{0,}$/";
        $entropay_regex = "/^(406742|410162|431380|459061|533844|522093)[0-9]{0,}$/";
        $o2money_regex = "/^(422793|475743)[0-9]{0,}$/";

        // MasterCard
        $mastercard_regex = "/^(5[1-5]|222[1-9]|22[3-9]|2[3-6]|27[01]|2720)[0-9]{0,}$/";
        $maestro_regex = "/^(5[06789]|6)[0-9]{0,}$/";
        $kukuruza_regex = "/^525477[0-9]{0,}$/";
        $yunacard_regex = "/^541275[0-9]{0,}$/";

        // American Express
        $amex_regex = "/^3[47][0-9]{0,}$/";

        // Diners Club
        $diners_regex = "/^3(?:0[0-59]{1}|[689])[0-9]{0,}$/";

        //Discover
        $discover_regex = "/^(6011|65|64[4-9]|62212[6-9]|6221[3-9]|622[2-8]|6229[01]|62292[0-5])[0-9]{0,}$/";

        //JCB
        $jcb_regex = "/^(?:2131|1800|35)[0-9]{0,}$/";

        //ordering matter in detection, otherwise can give false results in rare cases
        if (preg_match($jcb_regex, $pan)) {
            return "jcb";
        }
        if (preg_match($amex_regex, $pan)) {
            return "amex";
        }
        if (preg_match($diners_regex, $pan)) {
            return "diners_club";
        }

        //sub visa/mastercard cards
        if ($include_sub_types) {
            if (preg_match($vpreca_regex, $pan)) {
                return "v-preca";
            }
            if (preg_match($postepay_regex, $pan)) {
                return "postepay";
            }
            if (preg_match($cartasi_regex, $pan)) {
                return "cartasi";
            }
            if (preg_match($entropay_regex, $pan)) {
                return "entropay";
            }
            if (preg_match($o2money_regex, $pan)) {
                return "o2money";
            }
            if (preg_match($kukuruza_regex, $pan)) {
                return "kukuruza";
            }
            if (preg_match($yunacard_regex, $pan)) {
                return "yunacard";
            }
        }
        if (preg_match($visa_regex, $pan)) {
            return "visa";
        }
        if (preg_match($mastercard_regex, $pan)) {
            return "mastercard";
        }

        if (preg_match($discover_regex, $pan)) {
            return "discover";
        }

        if (preg_match($maestro_regex, $pan)) {
            if ($pan[0] == '5') {//started 5 must be mastercard
                return "mastercard";
            } else {
                return "maestro"; //maestro is all 60-69 which is not something else, thats why this condition in the end
            }
        }
        return "unknown"; //unknown for this system
    }
}
