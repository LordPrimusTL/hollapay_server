<?php
namespace Flutterwave;
use Flutterwave\FlutterEncrypt;
use Flutterwave\FlutterValidator;

class Card {
  private static $resources = [
    "staging" => [
      'tokenize' => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/tokenize/",
      'charge' => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/pay/",
      'validate' => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/pay/validate/",
      "preauth" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/preauthorize/",
      "capture" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/capture/",
      "refund" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/refund/",
      "avs" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/avs/pay",
      "status" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/status"
    ],
    "production" => [
      'tokenize' => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/tokenize/",
      'charge' => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/pay/",
      'validate' => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/pay/validate/",
      "preauth" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/preauthorize/",
      "capture" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/capture/",
      "refund" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/refund/",
      "avs" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/avs/pay",
      "status" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/status"
    ]
  ];

  /**
   * can be used to tokenize a card for future charges
   * @param  array $card    ['card_no', 'cvv', 'expiry_month', 'expiry_year', ]
   * @param  [type] $authModel      model for authentication can be bvn
   * @param  [type] $validateOption SMS or VOICE
   * @param  string $bvn            card holder bvn
   * @return ApiResponse
   */
  public static function tokenize($card, $authModel, $currency = "", $country ="", $validateOption = "", $bvn = "") {
    FlutterValidator::validateClientCredentialsSet();
    $key = Flutterwave::getApiKey();
    $cardNo = FlutterEncrypt::encrypt3Des($card['card_no'], $key);
    $expiryMonth = FlutterEncrypt::encrypt3Des($card['expiry_month'], $key);
    $expiryYear = FlutterEncrypt::encrypt3Des($card['expiry_year'], $key);
    $cvv = FlutterEncrypt::encrypt3Des($card['cvv'], $key);
    $country = FlutterEncrypt::encrypt3Des($country, $key);
    $currency = FlutterEncrypt::encrypt3Des($currency, $key);
    $authModel = FlutterEncrypt::encrypt3Des($authModel, $key);
    $validateOption = FlutterEncrypt::encrypt3Des($validateOption, $key);
    $bvn = FlutterEncrypt::encrypt3Des($bvn, $key);

    $merchantKey = Flutterwave::getMerchantKey();

    $resource = self::$resources[Flutterwave::getEnv()]['tokenize'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", $merchantKey)
              ->addBody("cardno", $cardNo)
              ->addBody("cvv", $cvv)
              ->addBody("country", $country)
              ->addBody("currency", $currency)
              ->addBody("expirymonth", $expiryMonth)
              ->addBody("expiryyear", $expiryYear)
              ->addBody("authmodel", $authModel)
              ->addBody("validateoption", $validateOption)
              ->addBody("bvn", $bvn)
              ->makePostRequest();
    return $resp;
  }

  /**
   * for chaging cards
   * @param  array $card        ['card_no', 'card_type', 'cvv', 'expiry_month', 'expiry_year']
   * @param  int|double|float $amount  amount to charge
   * @param  string $custId
   * @param  string $currency
   * @param  string $authModel   PIN|BVN|VBVSECURE|RANDOM_DEBIT
   * @param  string $narration
   * @param  string $responseUrl
   * @return ApiResponse
   */
  public static function charge($card, $amount, $custId, $currency, $country, $authModel, $narration, $responseUrl = "") {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $amount = FlutterEncrypt::encrypt3Des($amount, $key);
    $custId = FlutterEncrypt::encrypt3Des($custId, $key);
    $currency = FlutterEncrypt::encrypt3Des($currency, $key);
    $authModel = FlutterEncrypt::encrypt3Des($authModel, $key);
    $narration = FlutterEncrypt::encrypt3Des($narration, $key);
    $responseUrl = FlutterEncrypt::encrypt3Des($responseUrl, $key);
    $cardNo = FlutterEncrypt::encrypt3Des($card['card_no'], $key);
    $expiryMonth = FlutterEncrypt::encrypt3Des($card['expiry_month'], $key);
    $expiryYear = FlutterEncrypt::encrypt3Des($card['expiry_year'], $key);
    $country = FlutterEncrypt::encrypt3Des($country, $key);
    $cardType = "";
    if (isset($card['card_type']) && !empty($card['card_type'])) {
      $cardType = FlutterEncrypt::encrypt3Des($card['card_type'], $key);
    }
    $pin = "";
    if(isset($card['pin']) && !empty($card['pin'])){
      $pin = FlutterEncrypt::encrypt3Des($card['pin'], $key);
    }
    $bvn = "";
    if(isset($card['bvn']) && !empty($card['bvn'])){
      $bvn = FlutterEncrypt::encrypt3Des($card['bvn'], $key);
    }
    $cvv = FlutterEncrypt::encrypt3Des($card['cvv'], $key);
    $merchantKey = Flutterwave::getMerchantKey();

    $resource = self::$resources[Flutterwave::getEnv()]['charge'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", $merchantKey)
              ->addBody("amount", $amount)
              ->addBody("custid", $custId)
              ->addBody("currency", $currency)
              ->addBody("authmodel", $authModel)
              ->addBody("narration", $narration)
              ->addBody("responseurl", $responseUrl)
              ->addBody("cardno", $cardNo)
              ->addBody("cardtype", $cardType)
              ->addBody("cvv", $cvv)
              ->addBody("country", $country)
              ->addBody("expiryyear", $expiryYear)
              ->addBody("expirymonth", $expiryMonth)
              ->addBody("pin", $pin)
              ->addBody("bvn", $bvn)
              ->makePostRequest();
    return $resp;
  }

  /**
   *
   * @param  string $ref reference from charge response
   * @param  string $otp otp sent to card owner if otp authmodel is used
   * @param  sting $cardType optional parameter. only need this is its a diamond card. pass CardTpes::DIAMOND
   * @return ApiResponse
   */
  public static function validate($ref, $otp, $cardType = "") {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $encryptedRef = FlutterEncrypt::encrypt3Des($ref, $key);
    $encryptedOtp = FlutterEncrypt::encrypt3Des($otp, $key);

    $encryptedCardType = "";
    if (!empty($cardType)) {
      $encryptedCardType = FlutterEncrypt::encrypt3Des($cardType, $key);
    }

    $resource = self::$resources[Flutterwave::getEnv()]['validate'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("otp", $encryptedOtp)
              ->addBody("otptransactionidentifier", $encryptedRef)
              ->addBody("cardtype", $encryptedCardType)
              ->makePostRequest();
    return $resp;
  }

    /**
     * preAuthorize is used to hold certain amount from a card without actually
     * charging the card
     * @param  string $token token from tokenize response data
     * @param  int|double|float $amount amount to preauth
     * @param  string $currency currency to use for preauth
     * @param string $country country to use for preauth
     * @return ApiResponse
     */
  public static function preAuthorize($token, $amount, $currency, $country = 'NG') {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $token = FlutterEncrypt::encrypt3Des($token, $key);
    $amount = FlutterEncrypt::encrypt3Des($amount, $key);
    $currency = FlutterEncrypt::encrypt3Des($currency, $key);
    $country = FlutterEncrypt::encrypt3Des($country, $key);

    $resource = self::$resources[Flutterwave::getEnv()]['preauth'];
    $resp = (new ApiRequest($resource))
              ->addbody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("amount", $amount)
              ->addBody("currency", $currency)
              ->addBody("country", $country)
              ->addBody("chargetoken", $token)
              ->makePostRequest();
    return $resp;
  }

    /**
     * this can be used to charge the amount that was preAuthorized
     * @param  string $authRef transaction reference from
     * @param  string $transId transaction authorization id from preauthorize
     * @param  int|double|float $amount amount to put on hold
     * @param  string $currency currency
     * @param string $country country
     * @return ApiResponse
     */
  public static function capture($authRef, $transId, $amount, $currency, $country = 'NG') {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $authRef = FlutterEncrypt::encrypt3Des($authRef, $key);
    $transId = FlutterEncrypt::encrypt3Des($transId, $key);
    $currency = FlutterEncrypt::encrypt3Des($currency, $key);
    $amount = FlutterEncrypt::encrypt3Des($amount, $key);
    $country = FlutterEncrypt::encrypt3Des($country, $key);

    $resource = self::$resources[Flutterwave::getEnv()]['capture'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("trxreference", $authRef)
              ->addBody("trxauthorizeid", $transId)
              ->addBody("amount", $amount)
              ->addBody("currency", $currency)
              ->addBody("country", $country)
              ->makePostRequest();
    return $resp;
  }

  /**
   * use to refund a pre auth amount from the same card
   * @param  string $authRef  transaction reference
   * @param  string $transId  transaction id
   * @param  [type] $amount   amount to release from hold
   * @param  [type] $currency currency
   * @param  string $country country
   * @return ApiResponse
   */
  public static function refund($authRef, $transId, $amount, $currency, $country = 'NG') {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $authRef = FlutterEncrypt::encrypt3Des($authRef, $key);
    $transId = FlutterEncrypt::encrypt3Des($transId, $key);
    $currency = FlutterEncrypt::encrypt3Des($currency, $key);
    $amount = FlutterEncrypt::encrypt3Des($amount, $key);
    $country = FlutterEncrypt::encrypt3Des($country, $key);

    $resource = self::$resources[Flutterwave::getEnv()]['refund'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("trxreference", $authRef)
              ->addBody("trxauthorizeid", $transId)
              ->addBody("amount", $amount)
              ->addBody("currency", $currency)
              ->addBody("country", $country)
              ->makePostRequest();
    return $resp;
  }

  /**
   * use to refund a pre auth amount from the same card
   * @param  string $authRef  transaction reference
   * @param  string $transId  transaction id
   * @param  [type] $amount   amount to release from hold
   * @param  [type] $currency currency
   * @param  string $country country
   * @return ApiResponse
   */
  public static function void($authRef, $transId, $amount, $currency, $country = 'NG') {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $authRef = FlutterEncrypt::encrypt3Des($authRef, $key);
    $transId = FlutterEncrypt::encrypt3Des($transId, $key);
    $currency = FlutterEncrypt::encrypt3Des($currency, $key);
    $amount = FlutterEncrypt::encrypt3Des($amount, $key);
    $country = FlutterEncrypt::encrypt3Des($country, $key);

    $resource = self::$resources[Flutterwave::getEnv()]['refund'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("trxreference", $authRef)
              ->addBody("trxauthorizeid", $transId)
              ->addBody("amount", $amount)
              ->addBody("currency", $currency)
              ->addBody("country", $country)
              ->makePostRequest();
    return $resp;
  }

  /**
   * for chaging cards Using Token
   * @param  string $cardToken
   * @param  int|double|float $amount  amount to charge
   * @param  string $custId
   * @param  string $currency
   * @param  string $narration
   * @return ApiResponse
   */
  public static function chargeToken($cardToken, $amount, $custId, $currency, $country, $narration, $authmodel = "", $pin = "",  $cardType = "", $responseUrl = "") {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $authmodel = FlutterEncrypt::encrypt3Des($authmodel, $key);
    $pin = FlutterEncrypt::encrypt3Des($pin, $key);
    $amount = FlutterEncrypt::encrypt3Des($amount, $key);
    $custId = FlutterEncrypt::encrypt3Des($custId, $key);
    $currency = FlutterEncrypt::encrypt3Des($currency, $key);
    $narration = FlutterEncrypt::encrypt3Des($narration, $key);
    $cardToken = FlutterEncrypt::encrypt3Des($cardToken, $key);
    $country = FlutterEncrypt::encrypt3Des($country, $key);
    $cardType = FlutterEncrypt::encrypt3Des($cardType, $key);
    $responseUrl = FlutterEncrypt::encrypt3Des($responseUrl, $key);
    $merchantKey = Flutterwave::getMerchantKey();

    $resource = self::$resources[Flutterwave::getEnv()]['charge'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", $merchantKey)
              ->addBody("authmodel", $authmodel)
              ->addBody("amount", $amount)
              ->addBody("pin", $pin)
              ->addBody("custid", $custId)
              ->addBody("currency", $currency)
              ->addBody("narration", $narration)
              ->addBody("chargetoken", $cardToken)
              ->addBody("cardtype", $cardType)
              ->addBody("responseurl", $responseUrl)
              ->addBody("country", $country)
              ->makePostRequest();
    return $resp;
  }

  /**
   * for chaging cards Using AVS
   * @param  array $card
   * @param  int|double|float $amount  amount to charge
   * @param  string $currency
   * @param  string $country
   * @param  string $narration
   * @param  string $trxreference
   * @return ApiResponse
   */
  public static function chargeAvs($card, $amount, $currency, $country, $narration, $trxreference) {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $cardNo = FlutterEncrypt::encrypt3Des($card['card_no'], $key);
    $expiryMonth = FlutterEncrypt::encrypt3Des($card['expiry_month'], $key);
    $expiryYear = FlutterEncrypt::encrypt3Des($card['expiry_year'], $key);
    $cvv = FlutterEncrypt::encrypt3Des($card['cvv'], $key);
    $amount = FlutterEncrypt::encrypt3Des($amount, $key);
    $currency = FlutterEncrypt::encrypt3Des($currency, $key);
    $narration = FlutterEncrypt::encrypt3Des($narration, $key);
    $country = FlutterEncrypt::encrypt3Des($country, $key);
    $trxreference = FlutterEncrypt::encrypt3Des($trxreference, $key);
    $cardName = FlutterEncrypt::encrypt3Des($card['card_name'], $key);
    $cardAddressLine1 = FlutterEncrypt::encrypt3Des($card['card_address_line1'], $key);
    $cardState = FlutterEncrypt::encrypt3Des($card['card_state'], $key);
    $cardCity = FlutterEncrypt::encrypt3Des($card['card_city'], $key);
    $cardCountry = FlutterEncrypt::encrypt3Des($card['card_country'], $key);
    $pin = "";
    if(isset($card['pin']) && !empty($card['pin'])){
      $pin = FlutterEncrypt::encrypt3Des($card['pin'], $key);
    }
    $cardEmail = "";
    if(isset($card['card_email']) && !empty($card['card_email'])){
      $cardEmail = FlutterEncrypt::encrypt3Des($card['card_email'], $key);
    }
    $cardZip = "";
    if(isset($card['card_zip']) && !empty($card['card_zip'])){
      $cardZip = FlutterEncrypt::encrypt3Des($card['card_zip'], $key);
    }
    $cardPhoneType = "";
    if(isset($card['card_phone_type']) && !empty($card['card_phone_type'])){
      $cardPhoneType = FlutterEncrypt::encrypt3Des($card['card_phone_type'], $key);
    }
    $cardPhoneNumber = "";
    if(isset($card['card_phone_number']) && !empty($card['card_phone_number'])){
      $cardPhoneNumber = FlutterEncrypt::encrypt3Des($card['card_phone_number'], $key);
    }
    $merchantKey = Flutterwave::getMerchantKey();

    $resource = self::$resources[Flutterwave::getEnv()]['avs'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", $merchantKey)
              ->addBody("amount", $amount)
              ->addBody("trxreference", $trxreference)
              ->addBody("currency", $currency)
              ->addBody("narration", $narration)
              ->addBody("cardno", $cardNo)
              ->addBody("expiryyear", $expiryYear)
              ->addBody("expirymonth", $expiryMonth)
              ->addBody("cvv", $cvv)
              ->addBody("pin", $pin)
              ->addBody("cardname", $cardName)
              ->addBody("cardaddressline1", $cardAddressLine1)
              ->addBody("country", $country)
              ->addBody("cardstate", $cardState)
              ->addBody("cardcity", $cardCity)
              ->addBody("cardcountry", $cardCountry)
              ->addBody("cardemail", $cardEmail)
              ->addBody("cardzip", $cardZip)
              ->addBody("cardphonetype", $cardPhoneType)
              ->addBody("cardphonenumber", $cardPhoneNumber)
              ->makePostRequest();
    return $resp;
  }

  /**
   * use this method to check the status of a transaction
   * @param  string $ref  transaction reference
   * @return ApiResponse
   */
  public static function checkStatus($ref) {
    $key = Flutterwave::getApiKey();
    $ref = FlutterEncrypt::encrypt3Des($ref, $key);
    $resource = self::$resources[Flutterwave::getEnv()]["status"];
    $resp = (new ApiRequest($resource))
              ->addBody("trxreference", $ref)
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }
}
