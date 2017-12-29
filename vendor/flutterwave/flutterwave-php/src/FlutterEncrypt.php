<?php
namespace Flutterwave;

use PHP_Crypt\PHP_Crypt;

class FlutterEncrypt
{

    public static function encrypt3Des($data, $key)
    {
        //Generate a key from a hash
        $key = md5(utf8_encode($key), true);

        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);
        
        $encData = openssl_encrypt($data, 'DES-EDE3', $key, OPENSSL_RAW_DATA);

        return base64_encode($encData);
    }

    public static function decrypt3Des($data, $secret)
   {
       //Generate a key from a hash
       $key = md5(utf8_encode($secret), true);

       //Take first 8 bytes of $key and append them to the end of $key.
       $key .= substr($key, 0, 8);

       $data = base64_decode($data);
        
       $decData = openssl_decrypt($data, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
      
       return $decData;
   }
}
