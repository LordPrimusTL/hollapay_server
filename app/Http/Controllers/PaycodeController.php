<?php

namespace App\Http\Controllers;

use App\Organization;
use App\Paycode;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaycodeController extends Controller{
    //

    protected $url = "https://sandbox.interswitchng.com/api/v1";
    protected $auth;
    protected $clientId = "IKIAF49F8BCB3AFD2CB33E130A7B56A39FD5DF03B872";
    protected $clientSecret = "6bRg2vVdPQGN6VcllvZ2aXendIWLy0y9w/9mhwk1JZA=";
    protected $encodedUrl;
    protected $nonce;
    protected $token = "eyJhbGciOiJSUzI1NiJ9.eyJhdWQiOlsiaXN3LWNvbGxlY3Rpb25zIiwiaXN3LXBheW1lbnRnYXRld2F5IiwicGFzc3BvcnQiLCJ2YXVsdCJdLCJwcm9kdWN0aW9uX3BheW1lbnRfY29kZSI6IjA0MjYwNDEzMDM1MSIsInJlcXVlc3Rvcl9pZCI6IjAwMTg2MTEwMzU2Iiwic2NvcGUiOlsicHJvZmlsZSJdLCJleHAiOjE1MTQ1NzgzNDQsImp0aSI6IjE4YjA1OTgxLWYzNWYtNGY4MC05N2M4LWZkYTUwYjk3MWQwOCIsImNsaWVudF9pZCI6IklLSUFCOUNBQzgzQjhDQjhEMDY0Nzk5REIzNEE1OEQyQzhBNzAyNkEyMDNCIiwicGF5bWVudF9jb2RlIjoiMDUxNDIwNDE1OTQ5OCJ9.hbN6vOICLTgf-AaZUsjJvhL3cFQ1ZijYNXdHBSp9Vm-iSiESR9VsP6GAuIsxqIMee4RX0ZADt7hg9LvnvGAKh_WhQPD94Ce2zbWC8ldi4Hd2OF4fqb071AX6ln2ZlhFnumTgO5DPrMKspQpxMRPCcNMU6lZZPWFXQsCTwLYl1Kr6_Anhq-aaJGbcprLN3F0NhT6278VvpWG0QXmHuJekqAm-IIfneigepJnynkJwUtJ--lkJkfM4_r5O6YhEWxKdD1Fj6uR41IgvQZLEIrUrnCCe2VV8ZAQHmbCmxJHKIPuGZNXos0IXskWQZermTxk33v_syPEREiey3i5UtcyVHg";
    protected $timestamp;
    protected $signature;
    protected $signatureMethod = "SHA1";
    protected $subscriberId = "2348124888436";
    protected $header;
    protected $frontEndPartnerId;
    protected $accountNumber = "0689371332";

    private function getGuzzle(){
        return new Client();
    }

    private function initialize($organization_id, $fullUrl, $httpMethod){
        //$this->auth = [$this->clientId, $this->clientSecret];
        $httpMethod = strtoupper($httpMethod);
        $encodedFullUrl = urlencode($fullUrl);
        $this->timestamp = Carbon::now()->timestamp;
        $this->nonce = "$organization_id".str_random(20);
        $rawSignature = "$httpMethod&$encodedFullUrl&$this->timestamp&$this->nonce&$this->clientId&$this->clientSecret";
        $hashedValue = sha1($rawSignature,true);
        //Log::info("success: HashedValue",['val' => $hashedValue]);
        $this->signature = base64_encode($hashedValue);
        $this->frontEndPartnerId = "WEMA";
        $this->header = [
            'Timestamp' => $this->timestamp,
            'Nonce' => $this->nonce,
            'SignatureMethod' => $this->signatureMethod,
            'Signature' => $this->signature,
            'Authorization' => "InterswitchAuth ". base64_encode($this->clientId),
            'ACCESS_TOKEN' => $this->token,
            'FrontEndPartnerId' => $this->frontEndPartnerId,
            'Content-Type' => 'application/json'
        ];
    }

    public function generate(Request $request, Organization $organization){
        try{
            $pid = Paycode::generatePID();
            $fullUrl = "$this->url/pwm/subscribers/$this->subscriberId/tokens";
            $this->initialize($organization->organization_id, $fullUrl, "POST");
            //return Utility::responseError($request->all());
            $val = Validator::make($request->all(),[
                'ttid' => 'required',
                'beneficiaryNumber' => 'required',
                'amount' => 'required'
            ]);
            if($val->fails()){
                return Utility::responseError($val->errors()->all());
            }
            if($organization->u_wallet >= $request->amount){
                $amountInKobo = $request->amount * 100;
                $req = $this->getGuzzle()->post($fullUrl,[
                    'headers' => $this->header,
                    'json' => [
                        "ttid"=> $request->ttid,
                        "paymentMethodTypeCode"=> "MMO",
                        "paymentMethodCode"=> $this->frontEndPartnerId,
                        "payWithMobileChannel"=> "ATM",
                        "tokenLifeTimeInMinutes"=> "1440",
                        "amount"=> $amountInKobo,
                        "oneTimePin"=>"1234",
                        "codeGenerationChannel"=> "USSD",
                        "codeGenerationChannelProvider"=> "WEMA",
                        "accountNo"=> $this->accountNumber,
                        "accountType"=> "10",
                        "autoEnroll" => "True",
                        "transactionRef"=>$pid,
                        "payWithMobileToken"=> "546456",
                        "frontEndPartnerId"=> $this->frontEndPartnerId,
                        "beneficiaryNumber"=>$request->beneficiaryNumber
                    ]
                ]);
                $res = json_decode($req->getBody()->getContents());
                if(isset($res->payWithMobileToken)){
                    //save token and status
                    $pay = new Paycode();
                    $pay->organization_id = $organization->organization_id;
                    $pay->pay_id = $request->ttid;
                    $pay->code = $res->payWithMobileToken;
                    $pay->amount = $request->amount;
                    $pay->transaction_ref = $pid;
                    $pay->frontend_partner_id = $this->frontEndPartnerId;
                    $pay->status = 0;
                    $pay->extras = json_encode($res);
                    //$pay->save();

                    $organization->u_wallet -= $request->amount;
                    $organization->save();
                    return Utility::responseSuccess(['data' => $res, 'paycode' => $pay]);
                }else{
                    return Utility::responseError("An Unknown Error Occurred, Please Try Again");
                }
            }else{
                return Utility::responseBalance();
            }
        }catch (\Exception $ex){
            Log::error("Error,",['error' => $ex]);
            return Utility::returnError("An Error Occurred, Please try again later.", [$this->header]);
        }
    }
    public function status(Request $request, Organization $organization){
        $val = Validator::make($request->all(),[
            'pay_id' => 'required|exists:paycodes,pay_id'
        ]);
        if($val->fails()){
            return Utility::responseError($val->errors()->all());
        }

        $pay = Paycode::findByID($request->pay_id);
        if(isset($pay)){
            //return $pay;
            if($pay->status == 1){
                return Utility::responseSuccess("Paycode has been used", ['pay' => $pay]);
            }
            if($pay->status == 2){
                return Utility::responseSuccess("Paycode has been cancelled", ['pay' => $pay]);
            }
            $fullUrl = "$this->url/pwm/info/$this->subscriberId/tokens";
            $this->initialize($organization->organization_id, $fullUrl, "GET");
            $this->header['Paycode'] = $pay->code;
            //return $this->header;
            try{
                $req = $this->getGuzzle()->get($fullUrl,[
                    'headers' => $this->header,
                ]);
                if($req->getStatusCode() == 200){
                    $res = json_decode($req->getBody()->getContents());
                    //if The Paycode is still active and it hasnt been used.
                    if($res->status == 0){
                        $pay->status = 0;
                        $pay->save();
                    }

                    //if the Paycode has been used.
                    if($res->status == 1 || $res->status == 6){
                        $pay->status = 1;
                        $pay->save();
                        $organization->wallet -= $pay->amount;
                        $organization->save();
                    }
                    //if the paycode wans't used, has expired, or cancelled.
                    if($res->status == 2 || $res->status == 3 || $res->status == 4){
                        $pay->status = 2;
                        $pay->save();
                        $organization->u_wallet += $pay->amount;
                        $organization->save();
                    }
                    return Utility::responseSuccess($pay);
                }else{
                    $res = json_decode($req->getBody()->getContents());
                    return Utility::responseError($res);
                }
            }catch(\Exception $ex){
                Log::error("PayStatus: An Error Occurred",['Error' => $ex]);
                //return Utility::responseError("An Unknown Error Occurred");
                return Utility::responseError($ex->getMessage());
            }
        }else{
            return Utility::returnError("Paycode Could Not Be Found", ['pay' => $pay]);
        }
    }
    public function getAccessToken(){
        $req = $this->generateAccessToken();
        if($req['code'] == 1){
            $req = json_decode($req['res']);
            return Utility::responseSuccess($req);
        }else{
            return Utility::responseError($req["message"]);
        }
    }
    private function generateAccessToken(){
        try{
            $req = $this->getGuzzle()->post("https://sandbox.interswitchng.com/passport/oauth/token",[
                'auth' => [$this->clientId,$this->clientSecret],
                'json' => [
                    "scope" => "profile",
                    "grant_type" => "client_credentials"
                ]
            ]);
            Log::info("Success",['req' => $req]);
            $req = ['code' => 1, 'res' => $req->getBody()->getContents()];
            return $req;
        }catch (\Exception $ex){
            Log::error("error",['error' => $ex]);
            $req = ['code' => 0, 'message' => "An Error Occurred, Please try again later."];
            return $req;
        }
    }

    public function cancel(Request $request, Organization $org){
        try{
            $val = Validator::make($request->all(),[
                'pay_id' => 'required|exists:paycodes,pay_id'
            ]);
            if($val->fails()){
                return Utility::responseError($val->errors()->all());
            }

            $pay = Paycode::findByID($request->pay_id);
            if(isset($pay)) {
                if ($pay->status == 1) {
                    return Utility::responseError("Paycode has been used", ['pay' => $pay, 'org' => $org]);
                }

                if ($pay->status == 2) {
                    return Utility::responseError("Paycode has been cancelled", ['pay' => $pay, 'org' => $org]);
                }

                $fullUrl = "$this->url/pwm/tokens";
                $this->initialize($org->organization_id, $fullUrl, "DELETE");
                $req = $this->getGuzzle()->delete($fullUrl,[
                    'headers' => $this->header,
                    'json' => [
                        "transactionRef" => $pay->transaction_ref,
                        "frontEndPartner" => $this->frontEndPartnerId
                    ]
                ]);
                $res = json_decode($req->getBody()->getContents());
                if($res->code == 00){
                    return Utility::responseSuccess("Paycode Has Been successfully queued for cancellation",['pay' => $pay,'org'=> $org]);
                }else{
                    return Utility::responseError("Paycode could not be cancelled at this time.");
                }
            }else{
                return Utility::responseError("Paycode data could not be found");
            }
        }catch (\Exception $ex){
            Log::error("PayStatus: An Error Occurred",['Error' => $ex]);
            //return Utility::responseError("An Unknown Error Occurred");
            return Utility::responseError($ex->getMessage());

        }

    }
}
