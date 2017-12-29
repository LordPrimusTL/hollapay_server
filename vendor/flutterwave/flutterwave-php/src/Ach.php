<?php
namespace Flutterwave;
use Flutterwave\FlutterEncrypt;
use Flutterwave\FlutterValidator;

class Ach {

  /**
   * urls to endpoints on staging and production server
   * @var [type]
   */
  private static $achResources = [
    "staging" => [
      "getInstitutions" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/institutions/",
      "getInstitutionById" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/institutions/id/",
      "addUser" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/adduser/",
      "chargeEach" => "http://staging1flutterwave.co:8080/pwc/rest/card/mvva/chargeach"
    ],
    "production" => [
      "getInstitutions" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/institutions/",
      "getInstitutionById" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/institutions/id/",
      "addUser" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/adduser/",
      "chargeEach" => "https://prod1flutterwave.co:8181/pwc/rest/card/mvva/chargeach"
    ]
  ];

  /**
   * get a list of all financial institutions
   * @return ApiResponse
   */
  public static function getInstitutions() {
    FlutterValidator::validateClientCredentialsSet();

    $resource = self::$achResources[Flutterwave::getEnv()]["getInstitutions"];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }

  /**
   * get  details of a particular financial institution
   * @param  string $institutionId Institution Id
   * @return ApiResponse
   */
  public static function getInstitutionById($institutionId) {
    FlutterValidator::validateClientCredentialsSet();

    $encryptedInstitutionId = FlutterEncrypt::encrypt3Des($institutionId, Flutterwave::getApiKey());

    $resource = self::$achResources[Flutterwave::getEnv()]["getInstitutionById"];
    $resp = (new ApiRequest($resource))
              ->addBody("institutionid", $encryptedInstitutionId)
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }

  /**
   * add New User
   * @param  string $username User username
   * @param  string $password User password
   * @param  string $pin User pin
   * @param  string $email User email address
   * @param  string $institution Institution type
   * @return ApiResponse
   */
  public static function addUser($user) {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $encryptedUsername = FlutterEncrypt::encrypt3Des($user["username"], $key);
    $encryptedPassword = FlutterEncrypt::encrypt3Des($user["password"], $key);
    $encryptedPin = FlutterEncrypt::encrypt3Des($user["pin"], $key);
    $encryptedEmail = FlutterEncrypt::encrypt3Des($user["email"], $key);
    $encryptedInstitution = FlutterEncrypt::encrypt3Des($user["institution"], $key);

    $resource = self::$achResources[Flutterwave::getEnv()]["addUser"];
    $resp = (new ApiRequest($resource))
              ->addBody("username", $encryptedUsername)
              ->addBody("password", $encryptedPassword)
              ->addBody("pin", $encryptedPin)
              ->addBody("email", $encryptedEmail)
              ->addBody("institution", $encryptedInstitution)
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }

  /**
   * Charge a user account
   * @param  string $publictoken Public token
   * @param  string $accountid Account Id
   * @param  string $custid Customer Id
   * @param  string $narration Transaction narration
   * @param  string $trxreference Transaction reference
   * @param  string $amount Transaction amount
   * @param  string $currency Transaction currency
   * @return ApiResponse
   */
  public static function chargeEach($request) {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $encryptedPublicToken = FlutterEncrypt::encrypt3Des($request["publicToken"], $key);
    $encryptedAccountId = FlutterEncrypt::encrypt3Des($request["accountId"], $key);
    $encryptedCustId = FlutterEncrypt::encrypt3Des($request["custId"], $key);
    $encryptedNarration = FlutterEncrypt::encrypt3Des($request["narration"], $key);
    $encryptedTrxRef = FlutterEncrypt::encrypt3Des($request["trxRef"], $key);
    $encryptedAmount = FlutterEncrypt::encrypt3Des($request["amount"], $key);
    $encryptedCurrency = FlutterEncrypt::encrypt3Des($request["currency"], $key);

    $resource = self::$achResources[Flutterwave::getEnv()]["chargeEach"];
    $resp = (new ApiRequest($resource))
              ->addBody("publictoken", $encryptedPublicToken)
              ->addBody("accountid", $encryptedAccountId)
              ->addBody("custid", $encryptedCustId)
              ->addBody("narration", $encryptedNarration)
              ->addBody("trxreference", $encryptedTrxRef)
              ->addBody("amount", $encryptedAmount)
              ->addBody("currency", $encryptedCurrency)
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }

}