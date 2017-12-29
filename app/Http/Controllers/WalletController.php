<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Validator;
use App\Organization;
use App\Transactions;
use App\Http\Requests;
use Flutterwave\Card;
use App\TransactionLog;
use Flutterwave\AuthModel;
use Flutterwave\Flutterwave;
use Illuminate\Http\Request;
use Flutterwave\Currencies;

class WalletController extends Controller {

    //############# DEPOSIT TO WALLET #############
    public function depositToWallet(Request $request, Organization $CURRENT_USER) {
        //========= GET POST VARIABLES =========
        $amount = $request->input('amount');
        $newCard = $request->input('newCard');
        $card_token = $request->input('card_token');

        //========= STORE TRANSACTION ==============
        $transaction = new Transactions();
        $transaction->organization_id = $CURRENT_USER->organization_id;
        $transaction->_from = "Card";
        $transaction->_to = "HollaPay Wallet";
        $transaction->text = "You paid " . $amount . " Naira into your HollaPay acccount";
        $transaction->transaction_type = "Credit";
        $transaction->amount = $amount;
        $transaction->reference = uniqid();
        $transaction->save();

        //========= STORE TRANSACTION LOG ==============
        $tLog = new TransactionLog();
        $tLog->organization_id = $CURRENT_USER->organization_id;
        $tLog->transaction_id = $transaction->id;
        $tLog->transaction_type = "Preauthorization";
        $tLog->amount = $amount;
        $tLog->status = "pending";
        $tLog->save();




        if ($newCard == true || $newCard == "true") {//Use new card
            $merchantKey = "tk_8hBUK9Vx4u";
            $apiKey = "tk_TM4fIPpCrmy9gJcYq280";
            $env = "staging";

            $card_number = $request->input("card_number");
            Flutterwave::setMerchantCredentials($merchantKey, $apiKey, $env);

            $card = [
                "card_no" => $request->input("card_number"),
                "cvv" => $request->input("cvv"),
                "expiry_month" => $request->input("expiry_month"),
                "expiry_year" => $request->input("expiry_year")
            ];

            $authModel = AuthModel::NOAUTH; //this tells flutterwave how to validate the user of the card is the card owner
            //you can also use AuthModel::NOAUTH //which does not need validate method call
            $validateOption = Flutterwave::SMS; //this tells flutterwave to send authentication otp via sms
            $bvn = ""; //represents the bvn number of the card owner/user
            $result = Card::tokenize($card, $authModel, $validateOption, $bvn = "");

            if ($result->isSuccessfulResponse()) {
                $card_token = $result->getResponseData();
                $card_token = $card_token["data"]["responsetoken"];

                $result = $this->preAuth($card_token, $amount);

                if ($result->isSuccessfulResponse()) {

                    $response = $result->getResponseData();
                    $authorizeId = $response["data"]["authorizeId"];
                    $transactionreference = $response["data"]["transactionreference"];

                    //=========== LOG IT ===========
                    $tLog->reference = $transactionreference;
                    $tLog->status = "successful";
                    $tLog->save();

                    $tLog = new TransactionLog();
                    $tLog->organization_id = $CURRENT_USER->organization_id;
                    $tLog->transaction_id = $transaction->id;
                    $tLog->transaction_type = "Capture";
                    $tLog->amount = $amount;
                    $tLog->status = "pending";
                    $tLog->save();


                    //========== CAPTURE =========
                    $result = $this->capture($transactionreference, $authorizeId, $amount);
                    if ($result->isSuccessfulResponse()) {//Charged card Successffully
                        $tLog->status = "successful";
                        $tLog->save();

                        //=== Get the User and Compute new Balance ===
                        $user = Organization::find($CURRENT_USER->id);
                        $balance = $user->wallet;
                        $balance = $balance + $amount;
                        $user->wallet = $balance;
                        $user->save();

                        $success = "Your HollaPay account has been credited with " . $amount . " Naira ";
                        return response()->json(Utility::return200($success));
                    } else {
                        $tLog->status = "failed";
                        $tLog->save();
                        $error = "Could not Charge the Card";
                        return response()->json(Utility::returnError($error));
                    }
                } else {
                    $tLog->status = "failed";
                    $tLog->save();
                    $error = "Could not Preauthorize your card";
                    return response()->json(Utility::returnError($error));
                }
            } else {
                $res = $result->getResponseData();
                $res = $res["data"]["responsemessage"];
                $error = $res;
                return response()->json(Utility::returnError($error));
            }
        }

        else { //Use Old Card
            $result = $this->preAuth($card_token, $amount);
            if ($result->isSuccessfulResponse()) {

                $response = $result->getResponseData();
                $authorizeId = $response["data"]["authorizeId"];
                $transactionreference = $response["data"]["transactionreference"];

                //=========== LOG IT ===========
                $tLog->reference = $transactionreference;
                $tLog->status = "successful";
                $tLog->save();

                $tLog = new TransactionLog();
                $tLog->organization_id = $CURRENT_USER->organization_id;
                $tLog->transaction_id = $transaction->id;
                $tLog->transaction_type = "Capture";
                $tLog->amount = $amount;
                $tLog->status = "pending";
                $tLog->save();


                //========== FLUTTERWAVE CAPTURE =========
                $result = $this->capture($transactionreference, $authorizeId, $amount);
                if ($result->isSuccessfulResponse()) {//Charged card Successffully
                    $tLog->status = "successful";
                    $tLog->save();

                    //=== Get the User and Compute new Balance ===
                    $user = Organization::find($CURRENT_USER->id);
                    $balance = $user->wallet;
                    $balance = $balance + $amount;
                    $user->wallet = $balance;
                    $user->save();

                    $success = "Your HollaPay account has been credited with " . $amount . " Naira ";
                    return response()->json(Utility::return200($success));
                } else {
                    $tLog->status = "failed";
                    $tLog->save();
                    $error = "Could not Charge the Card";
                    return response()->json(Utility::returnError($error));
                }
            } else {
                $tLog->status = "failed";
                $tLog->save();
                $error = "Could not Preauthorize your card";
                return response()->json(Utility::returnError($error));
            }
        }
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

}
