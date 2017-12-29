<?php namespace Flutterwave;

class Account {

  private static $resources = [
    "staging" => [
      "enquiry" => "http://staging1flutterwave.co:8080/pwc/rest/pay/resolveaccount",
      "charge" => "http://staging1flutterwave.co:8080/pwc/rest/account/pay",
      "validate" => "http://staging1flutterwave.co:8080/pwc/rest/account/pay/validate"
    ],
    "production" => [
      "enquiry" => "https://prod1flutterwave.co:8181/pwc/rest/pay/resolveaccount",
      "charge" => "https://prod1flutterwave.co:8181/pwc/rest/account/pay",
      "validate" => "https://prod1flutterwave.co:8181/pwc/rest/account/pay/validate"
    ]
  ];

  /**
   * resolve account name information
   * @param  string $accountNumber account number to resolve to name
   * @param  string $bankCode      bank code of account number
   * @return ApiResponse
   */
  public static function enquiry($accountNumber, $bankCode) {
    $key = Flutterwave::getApiKey();
    $accountNumber = FlutterEncrypt::encrypt3Des($accountNumber, $key);
    $bankCode = FlutterEncrypt::encrypt3Des($bankCode, $key);

    $url = self::$resources[Flutterwave::getEnv()]["enquiry"];
    $resp = (new ApiRequest($url))
              ->addBody("recipientaccount", $accountNumber)
              ->addBody("destbankcode", $bankCode)
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }

  /**
   * charge a bank account
   * @param  string $accountNumber  account number to charge
   * @param  string $bankCode       bank code of the account to charge
   * @param  string $passCode       password
   * @param  array $paymentDetails ['amount'=>'', 'currency'=>'', 'narration'=> '', 'email'=>'', 'firstname'=>'', 'lastname'=>'', 'phonenumber'=>'']
   * @param  string $ref            reference
   * @param  string $responseUrl            redirect url on your page to call back with response
   * @param  string $authModel            Authentication model of charge, can be AUTH or NOAUTH
   * @param  string $accountToken            Account token gotten from previous successful charge
   * @return ApiResponse
   */
  public static function charge($accountNumber, $bankCode, $passCode = "", $paymentDetails, $ref, $responseUrl = "", $authModel = "", $accountToken = "") {
    $key = Flutterwave::getApiKey();
    $accountNumber = FlutterEncrypt::encrypt3Des($accountNumber, $key);
    $bankCode = FlutterEncrypt::encrypt3Des($bankCode, $key);
    $passCode = FlutterEncrypt::encrypt3Des($passCode, $key);
    $ref = FlutterEncrypt::encrypt3Des($ref, $key);
    $amount = FlutterEncrypt::encrypt3Des($paymentDetails['amount'], $key);
    $country = "";
    if(isset($paymentDetails['country']) && !empty($paymentDetails['country'])){
      $country = FlutterEncrypt::encrypt3Des($paymentDetails['country'], $key);
    }
    $currency = FlutterEncrypt::encrypt3Des($paymentDetails['currency'], $key);
    $narration = FlutterEncrypt::encrypt3Des($paymentDetails['narration'], $key);
    $firstname = FlutterEncrypt::encrypt3Des($paymentDetails['firstname'], $key);
    $lastname = FlutterEncrypt::encrypt3Des($paymentDetails['lastname'], $key);
    $email = FlutterEncrypt::encrypt3Des($paymentDetails['email'], $key);
    $phonenumber = FlutterEncrypt::encrypt3Des($paymentDetails['phonenumber'], $key);
    $responseurl = FlutterEncrypt::encrypt3Des($responseUrl, $key);
    $authmodel = FlutterEncrypt::encrypt3Des($authModel, $key);
    $accounttoken = FlutterEncrypt::encrypt3Des($accountToken, $key);

    $url = self::$resources[Flutterwave::getEnv()]["charge"];
    $resp = (new ApiRequest($url))
              ->addBody("narration", $narration)
              ->addBody("accountnumber", $accountNumber)
              ->addBody("bankcode", $bankCode)
              ->addBody("passcode", $passCode)
              ->addBody("amount", $amount)
              ->addBody("currency", $currency)
              ->addBody("country", $country)
              ->addBody("firstname", $firstname)
              ->addBody("lastname", $lastname)
              ->addBody("email", $email)
              ->addBody("phonenumber", $phonenumber)
              ->addBody("transactionreference", $ref)
              ->addBody("authmodel", $authmodel)
              ->addBody("responseurl", $responseurl)
              ->addBody("accounttoken", $accounttoken)
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }

  /**
   * validate an account charge
   * @param  string $parameter validation parameter
   * @param  string $value validation value
   * @param  string $ref reference
   * @return ApiResponse
   */
  public static function validate($parameter, $value, $ref) {
    $key = Flutterwave::getApiKey();
    $parameter = FlutterEncrypt::encrypt3Des($parameter, $key);
    $value = FlutterEncrypt::encrypt3Des($value, $key);
    $ref = FlutterEncrypt::encrypt3Des($ref, $key);

    $url = self::$resources[Flutterwave::getEnv()]["validate"];
    $resp = (new ApiRequest($url))
              ->addBody("validateparameter", $parameter)
              ->addBody("validateparametervalue", $value)
              ->addBody("transactionreference", $ref)
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }

}
