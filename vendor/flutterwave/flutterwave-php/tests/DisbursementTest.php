<?php
use Flutterwave\Disbursement;
use Flutterwave\Countries;
use Flutterwave\Currencies;
use Flutterwave\Flutterwave;

class DisbursementTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    Flutterwave::setMerchantCredentials("tk_tdyrSMQo8a", "tk_NabYp2XjZ6G9WwdFruzK");
  }

  public function testLink() {
    $resp = Disbursement::link("0690000041");
    $data = $resp->getResponseData();
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());

    return $data['data']['uniquereference'];
  }

  /**
  * @depends testLink
  */
  public function testValidate($ref) {
    $resp = Disbursement::validate("1.00", $ref, "ACCOUNT_DEBIT");
    $data = $resp->getResponseData();
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());
  }

  /**
  * @depends testLink
  */
  public function testValidate2($ref) {
    $resp = Disbursement::validate("12345", $ref, "PHONE_OTP");
    $data = $resp->getResponseData();
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());
    return $data['data']['accounttoken'];
  }

  /**
  * @depends testValidate2
  */
  public function testSend($accountToken) {
    $amount = 1000;
    $uniqueRef = "23323234547873436";
    $senderName = "Godswill Okwara";
    $destination = [
      "country" => Countries::NIGERIA,
      "currency" => Currencies::NAIRA,
      "bankCode" => "058",
      "recipientAccount" => "0123453241",
      "recipientName" => "Ridwan Olalere"
    ];
    $narration = "Testing";
    $resp = Disbursement::send($accountToken, $uniqueRef, $amount, $narration, $senderName, $destination);
    $data = $resp->getResponseData();
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());
  }

  public function testGetLinkedAccounts() {
    $resp = Disbursement::getLinkedAccounts();
    $data = $resp->getResponseData();
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());
  }
}
