<?php
use Flutterwave\AccessAccount;
use Flutterwave\Flutterwave;

class AccessAccountTest extends PHPUnit_Framework_TestCase {
  public function setUp() {
    Flutterwave::setMerchantCredentials("tk_tdyrSMQo8a", "tk_NabYp2XjZ6G9WwdFruzK", "staging");
  }

  public function testInitiate() {
    $accountNumber = "0690000001";
    $resp = AccessAccount::initiate($accountNumber);
    $this->assertTrue($resp->isSuccessfulResponse());
  }

  public function testValidate() {
    $ref = "66736";
    $accountNum = "0690000001";
    $otp = "12345";
    $billingAmount = 1000;
    $narration = "testing";

    $resp = AccessAccount::validate($ref, $accountNum, $otp, $billingAmount, $narration);
    $this->assertTrue($resp->isSuccessfulResponse());
  }

}
