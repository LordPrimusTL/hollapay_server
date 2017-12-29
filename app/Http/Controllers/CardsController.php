<?php

namespace App\Http\Controllers;

use App\Cards;
use App\Organization;
use Flutterwave\Card;
use GuzzleHttp\Client;
use Flutterwave\AuthModel;
use Flutterwave\Countries;
use Illuminate\Http\Request;
use Flutterwave\Flutterwave;
use Flutterwave\FlutterEncrypt;
use App\Http\Controllers\Controller;

class CardsController extends Controller {

    public function index(Request $request, Organization $CURRENT_USER) {
        $cards = Cards::where("organization_id", $CURRENT_USER->organization_id)->get();
        $status = "success";
        $message = "Cards fetched successfully";
        return response()->json(compact('status', 'message', 'cards'));
    }

    public function tokenize(Request $request, Organization $CURRENT_USER) {

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
            $card_response = $result->getResponseData();
            $card_token = $card_response["data"]["responsetoken"];
            $status = "success";
            $message = "The card has been successfully tokenized";
            return response()->json(Utility::return200($card_token,$message));
        } else {
            $res = $result->getResponseData();
            $res = $res["data"]["responsemessage"];
            $error = $res;
            $status = "error";
            $message = $res;
            return response()->json(Utility::returnError($message));
        }
    }

    public function delete(Request $request, Organization $CURRENT_USER) {
        $card_id = $request->input("card_id");
        $cards = Cards::where("id", $card_id)->delete();

        $status = "success";
        $message = "Card successfully deleted";
        return response()->json(Utility::return200($message));
    }

    /** OBTAINED FROM STACKOVERFLOW
     * Obtain a brand constant from a PAN 
     *
     * @param type $pan               Credit card number
     * @param type $include_sub_types Include detection of sub visa brands
     * @return string
     */
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
