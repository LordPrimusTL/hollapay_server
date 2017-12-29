# Flutterwave-PHP
The Flutterwave PHP library allows you to write php code to consume Flutterwaves APIs.
You can sign up for a Flutterwave account at [https://flutterwave.com](https://flutterwave.com)

## Flutterwave Services
- Account Payment
- Card Charge
- Bin Check
- Disbursement
- Bvn Check
- List of banks and financial institutions
- Ip Check

### Requirements
- > PHP 5.5.3
- mcrypt

### Installation

Add flutterwave to your `composer.json` file
```
"require": {
  "flutterwave/flutterwave-php": "dev-master"
}
```

Then do to update your packages with flutterwave-php
```
composer update
```

If your framework does not autoload by default or you are creating a composer project from scratch, please
remember that you will need to include vendor/autoload e.g
```
require_once 'path_to_vendor/autoload.php';
```

### Get Started
To get started using Flutterwave-PHP visit [here](https://github.com/Flutterwave/flutterwave-php/tree/master/examples) to see code examples.

### Please note that a success status on our response does not always mean that the payment was successful, it most probably simply means that we were able to make an attempt in processing the payment. This means that you as a customer has to ensure that you check the content of the data returned for the proper response, for response codes and response messages especially.