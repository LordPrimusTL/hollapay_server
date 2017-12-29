<?php

namespace App\Http\Controllers;

use App\BillsTransaction;
use App\Organization;
use App\TransactionLog;
use App\Transactions;
use Flutterwave\Card;
use Flutterwave\Currencies;
use Flutterwave\Flutterwave;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BillsController extends Controller
{
    //
    protected $url = "https://billpayment.paypad.com.ng/api/";
    protected $username = "aworeni@cashenvoy.com";
    protected $password = "welcome";
    protected $auth;
    private function getGuzzle()
    {
        $this->auth = [$this->username, $this->password];
        return new Client();
    }

    //DSTV
    public function dstvProduct()
    {
        try{
            $request =  $this->getGuzzle()->get($this->url."dstv/products",[
                'auth' => $this->auth
            ]);
            $res = json_decode($request->getBody()->getContents());
            if($res->id === "00")
                return Utility::return200($res->object->items);
            else
                return Utility::returnError($res->message);
            //return json_decode($res);
        }
        catch (\Exception $ex) {
            return Utility::responseError($ex->getMessage());
        }
    }

    public function dstvAddon(Request $request)
    {
        $val = Validator::make($request->all(),[
            'primaryProductCode' => 'required',
        ]);
        if($val->fails())
        {
            return Utility::returnError(implode('<br>',$val->errors()->all()));
        }
        try{
            $req = $this->getGuzzle()->post($this->url."dstv/addon",[
                'auth' => $this->auth,
                'json' => [
                    'primaryProductCode' => $request->primaryProductCode,
                ]
            ]);
            $res = json_decode($req->getBody()->getContents());
            if($res->id === "00")
                return Utility::return200($res->object->items);
            else
                return Utility::returnError($res->message);
        }
        catch(\Exception $ex)
        {
            Log::error('error',[$ex->getMessage(), $ex->getLine(), $ex]);
            return response()->json(Utility::returnError("An Unknown Error Occurred"));
        }
    }

    public function dstvInquiry(Request $request)
    {
        $val = Validator::make($request->all(),[
            'number' => 'required',
        ]);
        if($val->fails())
        {
            return response()->json(Utility::returnError($val->errors()->all()));
        }
        try{
            $req = $this->getGuzzle()->post($this->url."dstv/inquiry",[
                'auth' => $this->auth,
                'json' => [
                    'number' => $request->number,
                ]
            ]);
            $res = json_decode($req->getBody()->getContents());
            if($res->id === "00")
                return Utility::return200($res->object);
            else
                return Utility::returnError($res->message);
        }
        catch(\Exception $ex)
        {
            Log::error('error',[$ex->getMessage(), $ex->getLine(), $ex]);
            return response()->json(Utility::returnError("An Unknown Error Occurred"));
        }
    }

    public function dstvAdvice(Request $request, Organization $organization){
        $part = "dstv";
        $val = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required|numeric',
            'cardNumber' => 'required',
            'customerNumber' => 'required',
            'code' => 'required',
            'customerName' => 'required',
            'invoicePeriod' => 'required|numeric',
            'uniqueId' => 'required|numeric'
        ]);
        if($val->fails())
        {
            $errorMsg = implode("<br>",$val->errors()->all());
            return response()->json(Utility::returnError($errorMsg));
        }
        try {
            $trans = new Transactions();
            $trans->organization_id = $organization->id;
            $trans->_from = "$organization->organization_id:$request->transaction_id";
            $trans->_to = "HollaPay Wallet: $organization->organization_id";
            $trans->text = "Payment for $part bills";
            $trans->transaction_type = "Credit";
            $trans->reference = "";
            $trans->amount = $request->amount;
            $trans->save();

            $bLog = new BillsTransaction();
            $bLog->organization_id = $organization->id;
            $bLog->tid = $request->uniqueId;
            $bLog->bills_type = "DSTV";
            $bLog->amount = $request->amount;
            $bLog->status = "Pending";
            $bLog->save();
            if(strtoupper($request->type) == "CARD") {
                $val = Validator::make($request->all(),[
                    'card_token' => 'required'
                ]);
                if($val->fails())
                {
                    $errorMsg = implode("<br>",$val->errors()->all());
                    return response()->json(Utility::returnError($errorMsg));
                }
                if ($this->deductFunds($request->card_token, $request->amount, $trans, $organization->id)){
                    $req = $this->getGuzzle()->post($this->url."dstv/advice",[
                        'auth' => $this->auth,
                        'json' => [
                            'cardNumber' => $request->cardNumber,
                            'customerNumber' => $request->customerNumber,
                            'code' => $request->code,
                            'amount' => $request->amount,
                            'customerName' => $request->customerName,
                            'invoicePeriod' => $request->invoicePeriod,
                            'uniqueId' => $request->uniqueId,
                            'productCode' => $request->productCode,
                        ]
                    ]);
                    $res = json_decode($req->getBody()->getContents());
                    if($res->id === "00"){
                        $bLog->status = "Success";
                        $bLog->extras = \GuzzleHttp\json_encode($res->object);
                        $bLog->save();
                        return response()->json(Utility::return200($res->object));
                    }
                    else
                        return response()->json(Utility::returnError($res->message));
                }
                else
                    return response()->json(Utility::returnError("Unable to Charge The Card. Please Try Again"));
            }elseif (strtoupper($request->type == "WALLET")){
                $req = $this->getGuzzle()->post($this->url."dstv/advice",[
                    'auth' => $this->auth,
                    'json' => [
                        'cardNumber' => $request->cardNumber,
                        'customerNumber' => $request->customerNumber,
                        'code' => $request->code,
                        'amount' => $request->amount,
                        'customerName' => $request->customerName,
                        'invoicePeriod' => $request->invoicePeriod,
                        'uniqueId' => $request->uniqueId,
                        'productCode' => $request->productCode,
                    ]
                ]);
                $res = json_decode($req->getBody()->getContents());
                if($res->id === "00"){
                    $bLog->status = "Success";
                    $bLog->extras = \GuzzleHttp\json_encode($res->object);
                    $bLog->save();
                    $organization->wallet -= $request->amount;
                    $organization->save();
                    return response()->json(Utility::return200($res->object));
                }
                else
                    return response()->json(Utility::returnError($res->message));

            }else{
                return response()->json(Utility::returnError("Invalid Selection"));
            }

        }
        catch(\Exception $ex)
        {
            Log::error('error',[$ex->getMessage(), $ex->getLine(), $ex]);
            return response()->json(Utility::returnError("An Unknown Error Occurred"));
        }
    }

    public function dstvQuery(Request $request)
    {
        $val = Validator::make($request->all(),[
            'id' => 'required|numeric',
        ]);
        if($val->fails())
        {
            return response()->json(Utility::returnError($val->errors()->all()));
        }
        try{
            $request =  $this->getGuzzle()->post($this->url."dstv/query",[
                'auth' => $this->auth,
                'json' => [
                    'id' => $request->id,
                ]
            ]);
            $res = json_decode($request->getBody()->getContents());
            if($res->id === "00")
                return Utility::return200($res->object);
            else
                return Utility::returnError($res->message);
        }
        catch (\Exception $ex) {
            return response()->json($ex);
        }
    }


    //GoTV
    public function gotvProduct()
    {
        try{
            $request =  $this->getGuzzle()->get($this->url."gotv/products",[
                'auth' => $this->auth
            ]);
            $res = json_decode($request->getBody()->getContents());
            if($res->id === "00")
                return response()->json(Utility::return200($res->object->items));
            else
                return response()->json(Utility::returnError($res->message));
        }
        catch (\Exception $ex) {
            return response()->json(Utility::returnError($ex->getMessage()));
        }
    }

    public function gotvInquiry(Request $request){
        $val = Validator::make($request->all(),[
            'number' => 'required',
        ]);
        if($val->fails())
        {
            return response()->json(Utility::returnError($val->errors()->all()));
        }
        try{
            $req = $this->getGuzzle()->post($this->url."dstv/inquiry",[
                'auth' => $this->auth,
                'json' => [
                    'number' => $request->number,
                ]
            ]);
            $res = json_decode($req->getBody()->getContents());
            if($res->id === "00")
                return Utility::return200($res->object);
            else
                return Utility::returnError($res->message);
        }
        catch(\Exception $ex)
        {
            Log::error('error',[$ex->getMessage(), $ex->getLine(), $ex]);
            return response()->json(Utility::returnError("An Unknown Error Occurred"));
        }
    }

    public function gotvAdvice(Request $request, Organization $organization)
    {
        $part = "gotv";
        $val = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required|numeric',
            'cardNumber' => 'required',
            'customerNumber' => 'required',
            'code' => 'required',
            'customerName' => 'required',
            'invoicePeriod' => 'required|numeric',
            'uniqueId' => 'required|numeric'
        ]);
        if($val->fails())
        {
            $errorMsg = implode("<br>",$val->errors()->all());
            return response()->json(Utility::returnError($errorMsg));
        }
        try{
            $trans = new Transactions();
            $trans->organization_id = $organization->id;
            $trans->_from = "$organization->organization_id:$request->transaction_id";
            $trans->_to = "HollaPay Wallet: $organization->organization_id";
            $trans->text = "Payment for $part bills";
            $trans->transaction_type = "Credit";
            $trans->reference = "";
            $trans->amount = $request->amount;
            $trans->save();

            $bLog = new BillsTransaction();
            $bLog->organization_id = $organization->id;
            $bLog->tid = $request->uniqueId;
            $bLog->bills_type = "GOTV";
            $bLog->amount = $request->amount;
            $bLog->status = "Pending";
            $bLog->save();
            if(strtoupper($request->type) == "CARD") {
                $val = Validator::make($request->all(),[
                    'card_token' => 'required',
                ]);
                if($val->fails())
                {
                    $errorMsg = implode("<br>",$val->errors()->all());
                    return response()->json(Utility::returnError($errorMsg));
                }
                if($this->deductFunds($request->card_token, $request->amount, $trans, $organization->id)){
                    $req = $this->getGuzzle()->post($this->url."gotv/advice",[
                        'auth' => $this->auth,
                        'json' => [
                            'cardNumber' => $request->cardNumber,
                            'code' => $request->code,
                            'amount' => $request->amount,
                            'customerNumber' => $request->customerNumber,
                            'customerName' => $request->customerName,
                            'invoicePeriod' => $request->invoicePeriod,
                            'uniqueId' => $request->uniqueId,
                        ]
                    ]);
                    $res = json_decode($req->getBody()->getContents());
                    if($res->id === "00"){
                        $bLog->status = "Success";
                        $bLog->extras = \GuzzleHttp\json_encode($res->object);
                        $bLog->save();
                        return response()->json(Utility::return200($res->object));
                    }
                    else
                        return response()->json(Utility::returnError($res->message));
                }
                else
                    return response()->json(Utility::returnError("Unable to Charge The Card. Please Try Again"));
            }elseif (strtoupper($request->type == "WALLET")){
                if($organization->wallet >= $request->amount){
                    $req = $this->getGuzzle()->post($this->url."gotv/advice",[
                        'auth' => $this->auth,
                        'json' => [
                            'cardNumber' => $request->cardNumber,
                            'code' => $request->code,
                            'customerNumber' => $request->customerNumber,
                            'amount' => $request->amount,
                            'customerName' => $request->customerName,
                            'invoicePeriod' => $request->invoicePeriod,
                            'uniqueId' => $request->uniqueId,
                        ]
                    ]);
                    $res = json_decode($req->getBody()->getContents());
                    if($res->id === "00"){
                        $organization->balance -= $request->amount;
                        $bLog->status = "Success";
                        $bLog->extras = \GuzzleHttp\json_encode($res->object);
                        $bLog->save();
                        return response()->json(Utility::return200($res->object));
                    }
                    else
                        return response()->json(Utility::returnError($res->message));
                }else{
                    //Notify Merchant OF Low Funds
                    return response()->json(Utility::returnError("An Error Occurred, Please Try Again"));
                }
            }else{
                return response()->json(Utility::returnError("Invalid Selection"));
            }
        }
        catch(\Exception $ex)
        {
            Log::error('error',[$ex->getMessage(), $ex->getLine(), $ex]);
            return response()->json(Utility::returnError("An Unknown Error Occurred"));
        }
    }

    public function gotvQuery(Request $request)
    {
        $val = Validator::make($request->all(),[
            'id' => 'required|numeric',
        ]);
        if($val->fails())
        {
            return response()->json(Utility::returnError($val->errors()->all()));
        }
        try{
            $request =  $this->getGuzzle()->post($this->url."gotv/query",[
                'auth' => $this->auth,
                'json' => [
                    'id' => $request->id,
                ]
            ]);
            $res = json_decode($request->getBody()->getContents());
            if($res->id === "00")
                return Utility::return200($res->object);
            else
                return Utility::returnError($res->message);
        }
        catch (\Exception $ex) {
            return response()->json($ex);
        }
    }


    //StarTimes
    public function startimesInquiry(Request $request)
    {
        $val = Validator::make($request->all(),[
            'smartCardNumber' => 'required',
        ]);

        if($val->fails())
        {
            return response()->json(Utility::returnError($val->errors()->all()));
        }
        try{
            $req = $this->getGuzzle()->post($this->url."startimes/inquiry",[
                'auth' => $this->auth,
                'json' => [
                    'smartCardNumber' => $request->smartCardNumber,
                ]
            ]);
            $res = json_decode($req->getBody()->getContents());
            if($res->id === "00")
                return Utility::return200($res->object);
            else
                return Utility::returnError($res->message);
        }
        catch(\Exception $ex)
        {
            Log::error('error',[$ex->getMessage(), $ex->getLine(), $ex]);
            return response()->json(Utility::returnError("An Unknown Error Occurred"));
        }
    }

    public function startimesAdvice(Request $request, Organization $organization)
    {
        $part ="startimes";
        $val = Validator::make($request->all(),[
            'type'=>'required',
            'smartCardNumber' => 'required',
            'amount' => 'required|numeric',
            'uniqueId' => 'required|numeric',
        ]);
        if($val->fails())
        {
            $errorMsg = implode("<br>",$val->errors()->all());
            return response()->json(Utility::returnError($errorMsg));
        }
        try{

            $trans = new Transactions();
            $trans->organization_id = $organization->id;
            $trans->_from = "$organization->organization_id:$request->transaction_id";
            $trans->_to = "HollaPay Wallet: $organization->organization_id";
            $trans->text = "Payment for $part bills";
            $trans->transaction_type = "Bills Payment";
            $trans->reference = "";
            $trans->amount = $request->amount;
            $trans->save();


            $bLog = new BillsTransaction();
            $bLog->organization_id = $organization->id;
            $bLog->tid = $request->uniqueId;
            $bLog->bills_type = "StarTimes";
            $bLog->amount = $request->amount;
            $bLog->status = "Pending";
            $bLog->save();
            $type = strtoupper($request->type);
            if($type == "CARD"){
                $val = Validator::make($request->all(),[
                    'card_token' => 'required',
                ]);
                if($val->fails())
                {
                    $errorMsg = implode("<br>",$val->errors()->all());
                    return response()->json(Utility::returnError($errorMsg));
                }
                if($this->deductFunds($request->card_token, $request->amount, $trans, $organization->id)) {
                    $req = $this->getGuzzle()->post($this->url . "startimes/advice", [
                        'auth' => $this->auth,
                        'json' => [
                            'smartCardNumber' => $request->smartCardNumber,
                            'amount' => $request->amount,
                            'uniqueId' => $request->uniqueId,
                        ]
                    ]);
                    $res = json_decode($req->getBody()->getContents());
                    if ($res->id === "00") {
                        $bLog->status = "Success";
                        $bLog->extras = \GuzzleHttp\json_encode($res->object);
                        $bLog->save();
                        return response()->json(Utility::return200($res->object));
                    } else
                        return response()->json(Utility::returnError($res->message));
                }

                $req = $this->getGuzzle()->post($this->url . "startimes/advice", [
                    'auth' => $this->auth,
                    'json' => [
                        'smartCardNumber' => $request->smartCardNumber,
                        'amount' => $request->amount,
                        'uniqueId' => $request->uniqueId,
                    ]
                ]);
                $res = json_decode($req->getBody()->getContents());
                if ($res->id === "00") {
                    $bLog->status = "Success";
                    $bLog->extras = \GuzzleHttp\json_encode($res->object);
                    $bLog->save();
                    return response()->json(Utility::return200($res->object));
                } else
                    return response()->json(Utility::returnError($res->message));
            }elseif($type == "WALLET"){
                if($organization->wallet >= $request->amount){
                    $req = $this->getGuzzle()->post($this->url . "startimes/advice", [
                        'auth' => $this->auth,
                        'json' => [
                            'smartCardNumber' => $request->smartCardNumber,
                            'amount' => $request->amount,
                            'uniqueId' => $request->uniqueId,
                        ]
                    ]);
                    $res = json_decode($req->getBody()->getContents());
                    if ($res->id === "00") {
                        $bLog->status = "Success";
                        $bLog->extras = \GuzzleHttp\json_encode($res->object);
                        $bLog->save();
                        return response()->json(Utility::return200($res->object));
                    } else
                        return response()->json(Utility::returnError($res->message));
                }else{
                    //Insufficirnt Balance
                    return response()->json(Utility::error("An Error Occurred"));
                }
            }else{
                return response()->json(Utility::returnError("Invalid Transaction Type"));
            }

        }
        catch(\Exception $ex)
        {
            Log::error('error',$ex);
            return response()->json(Utility::returnError("An Unknown Error Occurred"));
        }
    }

    public function startimesQuery(Request $request)
    {
        $val = Validator::make($request->all(),[
            'id' => 'required|numeric',
        ]);
        if($val->fails())
        {
            return response()->json(Utility::returnError($val->errors()->all()));
        }
        try{
            $request =  $this->getGuzzle()->post($this->url."startimes/query",[
                'auth' => $this->auth,
                'json' => [
                    'id' => $request->id,
                ]
            ]);
            $res = json_decode($request->getBody()->getContents());
            if($res->id === "00")
                return Utility::return200($res->object);
            else
                return Utility::returnError($res->message);
        }
        catch (\Exception $ex) {
            return response()->json($ex);
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

    private function deductFunds($card_token, $amount, $trans, $organization_id){
        $tLog = new TransactionLog();
        $tLog->organization_id = $organization_id;
        $tLog->transaction_id = $trans->id;
        $tLog->transaction_type = "Preauthorization";
        $tLog->amount = $amount;
        $tLog->status = "pending";
        $tLog->save();
        //$tLog
        $result = $this->preAuth($card_token, $amount);
        if($result->isSuccessfulResponse()){
            $response = $result->getResponseData();
            $authorizeId = $response["data"]["authorizeId"];
            $transactionreference = $response["data"]["transactionreference"];
            $tLog->reference = $transactionreference;
            $tLog->status = "successful";
            $tLog->save();

            $tLog = new TransactionLog();
            $tLog->organization_id = $organization_id;
            $tLog->transaction_id = $trans->id;
            $tLog->transaction_type = "capture";
            $tLog->amount = $amount;
            $tLog->status = "pending";
            $tLog->save();

            //========== CAPTURE =========
            $result = $this->capture($transactionreference, $authorizeId, $amount);
            if($result->isSuccessfulResponse()){
                $tLog->status = "successful";
                $tLog->save();
                return true;
            }
            else{
                return false;
            }
        }else{
            return false;
        }

    }
}
