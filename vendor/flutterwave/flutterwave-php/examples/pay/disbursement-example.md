## Disbursement Example
With Flutterwave disbursement API, you can make payment to any financial institution in supported
countries.

```PHP
use Flutterwave\Disbursement;
use Flutterwave\Flutterwave;
use Flutterwave\Countries;
use Flutterwave\Currencies;

//merchantKey and apiKey can be found on your Flutterwave dashboard under settings
//env can be production or staging depending on your stage of development
$merchantKey = ""; //can be found on flutterwave dev portal
$apiKey = ""; //can be found on flutterwave dev portal
$env = "staging"; //this can be production when ready for deployment
Flutterwave::setMerchantCredentials($merchantKey, $apiKey, $env);

//In order to disburse funds, you need to first link the account you will disburse from
//You can link as many accounts as you want
$accountno = "0690000031";
$result = Disbursement::link($accountno);
//$result is an instance of ApiResponse class which has
//methods like getResponseData(), getStatusCode(), getResponseCode(), isSuccessfulResponse()

if ($result->isSuccessfulResponse()) {
  echo("I have successfully linked an account.");
}


//After linking an account, you need to do double validation of that linked account using the ref from the link account response
//This is to authenticate that the account belongs to you
//We will send an OTP to the phone number on the account as well do a random debit on the bank account
//You are to supply the OTP and the random debit amount to us in two validation steps
//The otp type for the the OTP validation is PHONE_OTP while that of the Random debit validation is ACCOUNT_DEBIT
//Any of the two validations can come first, it doesnt matter, but after the second one has been done successfully, we will return an account token to you that you are to save for all calls to the Send Endpoint


$response = $result->getResponseData();
$linkingRef = $response['data']['uniquereference'];

//Validation Step 1
$otp = "1.00";
$otpType = "ACCOUNT_DEBIT"; //(ACCOUNT_DEBIT | PHONE_OTP)
$result2 = Disbursement::validate($otp, $linkingRef, $otpType);
$response2 = $result2->getResponseData();

if ($result2->isSuccessfulResponse()) {
  echo("I have passed first validation test.");
}

//Validation Step 2
//This will return an account token if successful
$otp = "12345";
$otpType = "PHONE_OTP"; //(ACCOUNT_DEBIT | PHONE_OTP)
$result3 = Disbursement::validate($otp, $linkingRef, $otpType);
$response3 = $result3->getResponseData();

if ($result3->isSuccessfulResponse()) {
  echo("I have passed second validation test.");
}

//If Validation step 2 is successful, an account token is returned, you save the account token
//You will need the account token each time you want to disburse funds
$accountToken = $response3['data']['accounttoken'];
$amount = 1000;
$uniqueRef = "23323234547873436"; //This reference has to be unique
$senderName = "Godswill Okwara";
$destination = [
  "country" => Countries::NIGERIA,
  "currency" => Currencies::NAIRA,
  "bankCode" => "044", //the 044 represents the bank code, you can get all bank codes using the bank API
  "recipientAccount" => "0690000028", //Make sure you havent added this account before
  "recipientName" => "Ridwan Olalere"
];
$narration = "Testing";
$result4 = Disbursement::send($accountToken, $uniqueRef, $amount, $narration, $senderName, $destination);
$response4 = $result4->getResponseData();

if ($result4->isSuccessfulResponse()) {
  echo("I have successfully disbursed funds.");
}

//You can see a list of all your linked accounts as well
result5 = Disbursement::getLinkedAccounts();
$response5 = $result5->getResponseData();

if ($result5->isSuccessfulResponse()) {
  echo("I can see a list of all linked accounts.");
}

echo "<pre>";
    var_dump($response5['data']['linkedaccounts']);
echo "</pre>";

$accountnumber = "0690000031", $country = "NG";
$result6 = Disbursement::unlink($accountnumber, $country);
$response6 = $result6->getResponseData();

if ($result6->isSuccessfulResponse()) {
  echo("I have successfully unlinked the account");
}
```
