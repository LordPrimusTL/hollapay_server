<?php
use Flutterwave\Ach;
use Flutterwave\Flutterwave;

class AchTest extends PHPUnit_Framework_TestCase {
  public function setUp() {
    Flutterwave::setMerchantCredentials("tk_tdyrSMQo8a", "tk_NabYp2XjZ6G9WwdFruzK", "staging");
  }

  public function testGetInstitutions() {
    $resp = Ach::getInstitutions();
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());
  }

  public function testGetInstitutionById() {
    $institutionId = "5301a93ac140de84910000e0";
    $resp = Ach::getInstitutionById($institutionId);
    $data = $resp->getResponseData();
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());
    return $data['data']['institutions'][0]['type'];
  }

  /**
  * @depends testGetInstitutionById
  */
  public function testAddUser($type) {
    $user = [
      "username" => "gondy",
      "password" => "password",
      "pin" => "1111",
      "email" =>"email@domain.com",
      "institution" => $type
    ];
    $resp = Ach::addUser($user);
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());
  }

  public function testChargeEach() {
    $request = [
      "publictoken" => "",
      "accountid" => "",
      "custid" => "798809090",
      "narration" => "Testing ACH Payment",
      "trxRef" => "121313Ghjjhkjk",
      "amount" => "1000.00",
      "currency" => "NGN"
    ];
    $resp = Ach::chargeEach($request);
    $this->assertTrue($resp->isSuccessfulResponse());
    $this->assertEquals(200, $resp->getStatusCode());
    $this->assertNotEmpty($resp->getResponseData());
  }

}
