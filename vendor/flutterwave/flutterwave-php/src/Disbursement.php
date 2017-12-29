<?php
namespace Flutterwave;

use Flutterwave\FlutterEncrypt;

/**
 *
 */
class Disbursement {

  //@var array resources
  private static $disburseResources = [
    "staging" => [
      "link" => "http://staging1flutterwave.co:8080/pwc/rest/pay/linkaccount/",
      "validate" => "http://staging1flutterwave.co:8080/pwc/rest/pay/linkaccount/validate/",
      "getLinkedAccounts" => "http://staging1flutterwave.co:8080/pwc/rest/pay/getlinkedaccounts",
      "send" => "http://staging1flutterwave.co:8080/pwc/rest/pay/send",
      "unlink" => "http://staging1flutterwave.co:8080/pwc/rest/pay/unlinkaccount"
    ],
    "production" => [
      "link" => "https://prod1flutterwave.co:8181/pwc/rest/pay/linkaccount/",
      "validate" => "https://prod1flutterwave.co:8181/pwc/rest/pay/linkaccount/validate/",
      "getLinkedAccounts" => "https://prod1flutterwave.co:8181/pwc/rest/pay/getlinkedaccounts",
      "send" => "https://prod1flutterwave.co:8181/pwc/rest/pay/send",
      "unlink" => "https://prod1flutterwave.co:8181/pwc/rest/pay/unlinkaccount"
    ]
  ];

  /**
  * use link to link account number to merchant
  * @param string $accountno
  * @return ApiResponse
  */
  public static function link($accountno) {
    FlutterValidator::validateClientCredentialsSet();

    $encryptedAccountNo = FlutterEncrypt::encrypt3Des($accountno, Flutterwave::getApiKey());
    
    $resource = self::$disburseResources[Flutterwave::getEnv()]["link"];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("accountnumber", $encryptedAccountNo)
              ->makePostRequest();
    return $resp;
  }

  /**
  * use validate to verify the account number belongs
  * to the merchant
  * @param string $otp
  * @param string $relatedreference
  * @param string $otptype
  * @return ApiResponse
  */
  public static function validate($otp, $relatedreference, $otptype) {
    FlutterValidator::validateClientCredentialsSet();
    
    $key = Flutterwave::getApiKey();
    $encryptedOtp = FlutterEncrypt::encrypt3Des($otp, $key);
    $encryptedRelatedReference = FlutterEncrypt::encrypt3Des($relatedreference, $key);
    $encryptedOtpType = FlutterEncrypt::encrypt3Des($otptype, $key);

    $resource = self::$disburseResources[Flutterwave::getEnv()]["validate"];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("otp", $encryptedOtp)
              ->addBody("relatedreference", $encryptedRelatedReference)
              ->addBody("otptype", $encryptedOtpType)
              ->makePostRequest();
    return $resp;
  }

   /**
  * use getLinkedAccounts to see all the account numbers that
  * have been linked by a merchant
  * @return ApiResponse
  */
  public static function getLinkedAccounts() {
    FlutterValidator::validateClientCredentialsSet();

    $resource = self::$disburseResources[Flutterwave::getEnv()]["getLinkedAccounts"];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->makePostRequest();
    return $resp;
  }

  /**
   * can be used to disburse funds
   * @param string $accounttoken
   * @param double/float $amount
   * @param string $uniqueRef
   * @param string $sender name of the sender
   * @param array $destination
   * @param string $narration
   * @param string $unique reference
   * @param string $country
   * @param string $currency
   * @return ApiResponse
   */
  public static function send($accountToken, $uniqueRef, $amount, $narration, $senderName, $destination) {
    FlutterValidator::validateClientCredentialsSet();
    //TODO: validate keys of destination is correct
    $key = Flutterwave::getApiKey();
    $encryptedAccountToken = FlutterEncrypt::encrypt3Des($accountToken, $key);
    $encryptedAmount = FlutterEncrypt::encrypt3Des($amount, $key);
    $encryptedRef = FlutterEncrypt::encrypt3Des($uniqueRef, $key);
    $encryptedBank = FlutterEncrypt::encrypt3Des($destination["bankCode"], $key);
    $encryptedNarration = FlutterEncrypt::encrypt3Des($narration, $key);
    $encryptedRecipientAccount = FlutterEncrypt::encrypt3Des($destination["recipientAccount"], $key);
    $encryptedRecipientName = FlutterEncrypt::encrypt3Des($destination["recipientName"], $key);
    $encryptedSender = FlutterEncrypt::encrypt3Des($senderName, $key);
    $encryptedcountry = FlutterEncrypt::encrypt3Des($destination["country"], $key);
    $encryptedcurrency = FlutterEncrypt::encrypt3Des($destination["currency"], $key);

    $resource = self::$disburseResources[Flutterwave::getEnv()]["send"];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("accounttoken", $encryptedAccountToken)
              ->addBody("transferamount", $encryptedAmount)
              ->addBody("uniquereference", $encryptedRef)
              ->addBody("destbankcode", $encryptedBank)
              ->addBody("narration", $encryptedNarration)
              ->addBody("recipientaccount", $encryptedRecipientAccount)
              ->addBody("recipientname", $encryptedRecipientName)
              ->addBody("sendername", $encryptedSender)
              ->addBody("country", $encryptedcountry)
              ->addBody("currency", $encryptedcurrency)
              ->makePostRequest();
    return $resp;
  }

  /**
  * use unlinkaccount to remove a linked and validated account
  * @param string $accountnumber
  * @param string $country
  * @return ApiResponse
  */
  public static function unlinkaccount($accountnumber, $country) {
    FlutterValidator::validateClientCredentialsSet();
    
    $key = Flutterwave::getApiKey();
    $encryptedAccountNumber = FlutterEncrypt::encrypt3Des($accountnumber, $key);
    $encryptedCountry = FlutterEncrypt::encrypt3Des($country, $key);

    $resource = self::$disburseResources[Flutterwave::getEnv()]["unlink"];
    $resp = (new ApiRequest($resource))
              ->addBody("merchantid", Flutterwave::getMerchantKey())
              ->addBody("accountnumber", $encryptedAccountNumber)
              ->addBody("country", $encryptedCountry)
              ->makePostRequest();
    return $resp;
  }
}
