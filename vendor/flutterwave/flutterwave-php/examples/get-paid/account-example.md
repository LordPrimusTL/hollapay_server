## Get Paid Account Example
This example will show you how to get paid using bank accounts from available banks.

#### Account Flow
To get paid from a account, you need to first tokenize the account by performing the
operation `charge` then `validate`, in the validate operation the account owner will either get a credit (ACCOUNT_CREDIT) into their account or an otp (OTP). For the credit, you need to enter the last 6 digits of the reference as stipulated in the transaction description. These have to be supplied to the `charge` method as well as the validate parameter, after this is done successfully, the account is charged. If Auth model is AUTH, an extra validation will be required for the charge and validateparamter will be PAY_VALIDATE.

```PHP
use Flutterwave\Account;
use Flutterwave\Flutterwave;

//merchantKey and apiKey can be found in your flutter developer console
//env can be production or staging depending on your stage of development
$merchantKey = ""; //can be found on flutterwave dev portal
$apiKey = ""; //can be found on flutterwave dev portal
$env = "staging"; //this can be production when ready for deployment
Flutterwave::setMerchantCredentials($merchantKey, $apiKey, $env);

$paymentDetails = [
	"amount" => "",
	"currency" => "",
	"narration" => "",
	"firstname" => "",
	"lastname" => "",
	"email" => "",
	"phonenumber" => "",
	"country" => ""
];

$accountNumber = ""; //account number you want to charge
$bankCode = "";
$passCode = ""; //4 to 6 digit security pin, it will be mapped to the account
$ref = ""; //unique transaction ref
$responseUrl = ""; //url on your server to call back for SA banks (optional)
$authModel = ""; //authentication model, can be AUTH or NOAUTH
$accountToken = ""; //gotten after successful charge, used for recurrent payment (optional)
$result = Account::charge($accountNumber, $bankCode, $passCode, $paymentDetails, $ref, $responseUrl, $authModel, $accountToken);
if ($result->isSuccessfulResponse()) {
  echo("Charge Works");
}

$resp = $result->getResponseData();
$parameter = $resp['data']['authparams'][0]['validateparameter'];
$value = ""; //sent to account owners number
$ref = $resp['data']['transactionreference'];

$result2 = Account::validate($parameter, $value, $ref);

if ($result2->isSuccessfulResponse()) {
  echo("Successfully validated");
}

```
