<?php
namespace Flutterwave;
use Flutterwave\FlutterEncrypt;
use Flutterwave\FlutterValidator;

class AccountResolve {

  /**
   * urls to endpoints on staging and production server
   * @var [type]
   */
  private static $accountResolveResources = [
    "staging" => [
      "resolve" => "http://staging1flutterwave.co:8080/pwc/rest/pay/resolveaccount/"
    ],
    "production" => [
      "resolve" => "https://prod1flutterwave.co:8181/pwc/rest/pay/resolveaccount/"
    ]
  ];

  /**
   * resolve an account number
   * @param   string $bankCode          Bank Code
   * @param   string $accountNum   account number
   * @return ApiResponse
   */
  public static function resolve($bankCode, $accountNum) {
    FlutterValidator::validateClientCredentialsSet();

    $key = Flutterwave::getApiKey();
    $bankCode = FlutterEncrypt::encrypt3Des($bankCode, $key);
    $accountNum = FlutterEncrypt::encrypt3Des($accountNum, $key);

    $resource = self::$accountResolveResources[Flutterwave::getEnv()]['resolve'];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("destbankcode", $bankCode)
              ->addBody("recipientaccount", $accountNum)
              ->makePostRequest();
    return $resp;
  }
}
