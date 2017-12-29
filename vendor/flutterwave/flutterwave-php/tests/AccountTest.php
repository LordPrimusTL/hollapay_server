<?php

use Flutterwave\Flutterwave;
use Flutterwave\Account;

class AccountTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
    Flutterwave::setMerchantCredentials("tk_tdyrSMQo8a", "tk_NabYp2XjZ6G9WwdFruzK", "staging");
  }

  public function testEnquiry() {
    $accountNumber = "0921318712";
    $bankCode = "058";
    $resp = Account::enquiry($accountNumber, $bankCode);
    $this->assertTrue($resp->isSuccessfulResponse());
  }

  public function testCharge() {
    $accountNumber = "0921318712";
    $bankCode = "011";
    $passCode = "74454";
    $details = [
      'amount' => 1000,
      'currency' => 'NGN',
      'narration' => 'test',
      'email' => 'dd@gmail.com',
      'firstname' => 'Rili',
      'lastname' => 'Ola',
    ];
    $ref = uniqid();

    $resp = Account::charge($accountNumber, $bankCode, $passCode, $details, $ref);
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertTrue($resp->requiresValidation());

    return $resp->getResponseData()['data']['transactionreference'];
  }

  /**
   * @depends testCharge
   * @return [type] [description]
   */
  public function testValidate($ref) {
    $resp = Account::validate("12345", $ref);
    $this->assertTrue($resp->isSuccessfulResponse());
  }
}
