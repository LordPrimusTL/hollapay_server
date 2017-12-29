<?php

namespace App\Services;

use Flutterwave\Card;
use Flutterwave\Flutterwave;
use Flutterwave\AuthModel;
use Flutterwave\Currencies;
use GuzzleHttp\Client;


class HollaPayService {

    public $merchantKey = "tk_8hBUK9Vx4u";
    public $apiKey = "tk_TM4fIPpCrmy9gJcYq280";
    public $env = "staging";
    public $SMS_API_KEY = '106004';
    public $SMS_API_SECRET = 'G2nkgZOihfjGW8K0iZQeFydoo';
    public $SMS_SOURCE = "HollaPay";

    public function preAuth($token, $amount) {
        $currency = Currencies::NAIRA;
        $merchantKey = "tk_8hBUK9Vx4u";
        $apiKey = "tk_TM4fIPpCrmy9gJcYq280";
        $env = "staging";

        Flutterwave::setMerchantCredentials($merchantKey, $apiKey, $env);
        $result = Card::preAuthorize($token, $amount, $currency);
        return $result;
    }

    public function capture($authRef, $transId, $amount) {
        $merchantKey = "tk_8hBUK9Vx4u";
        $apiKey = "tk_TM4fIPpCrmy9gJcYq280";
        $env = "staging";

        Flutterwave::setMerchantCredentials($merchantKey, $apiKey, $env);
        $currency = Currencies::NAIRA;
        $result = Card::capture($authRef, $transId, $amount, $currency);
        return $result;
    }

    public function tokenize_card($card) {
        Flutterwave::setMerchantCredentials($this->merchantKey, $this->apiKey, $this->env);

        $authModel = AuthModel::NOAUTH; //this tells flutterwave how to validate the user of the card is the card owner
        //you can also use AuthModel::NOAUTH //which does not need validate method call
        $validateOption = Flutterwave::SMS; //this tells flutterwave to send authentication otp via sms
        $bvn = ""; //represents the bvn number of the card owner/user
        $result = Card::tokenize($card, $authModel, $validateOption, $bvn = "");

        if ($result->isSuccessfulResponse()) {
            $card_token = $result->getResponseData();
            $card_token = $card_token["data"]["responsetoken"];
            return $card_token;
        } else {
            $res = $result->getResponseData();
            return $res;
            $error = $res["data"]["responsemessage"];
        }
    }

    function sendSMS($to, $message) {
        $url = "/sendsms?api_key=" . $this->SMS_API_KEY .
                "&api_secret=" . $this->SMS_API_SECRET .
                "&destination=" . $to .
                "&message=" . $message .
                "&source=" . $this->SMS_SOURCE;

        $client = new Client(['base_uri' => 'https://www.kedesa.com']);
        $response = $client->request('GET', $url);
        $response = $response->getBody()->getContents();
    }
}
