<?php
defined('MOODLE_INTERNAL') || die();

class local_scuolib_tokenGenerator
{
    const pw = "5Ck5uJJ6Vd&uyS6r&AaSv4tp*9bEJD4D";
    public static function generateToken($user)
    {
        $inputMessage = self::getMessage();
        if (isset($inputMessage->webglversion)) {
            self::sendWebGLStatus($user, $inputMessage);
        }

        $message =  new stdClass();
        $message->date = gmdate("Y-m-d-H-i-s");
        $message->requestUri = self::getRequestURI();
        $message->fullName = $user->firstname . " " . $user->lastname;
        $message->randomId = $inputMessage->randomId;
        $message->activityId = $inputMessage->activityId;

        $encrypted = self::encrypt(json_encode($message), self::pw);

        return $encrypted;
    }

    public static function getMessage()
    {
        if (isset($_GET['message'])) {
            $unityMessageJson = self::decrypt($_GET["message"], self::pw);
            $unityMessageObj = json_decode($unityMessageJson);
            return $unityMessageObj;
        } else if (isset($_GET["randomId"])) {
            $unityMessageObj =  new stdClass();
            $unityMessageObj->randomId = self::decrypt($_GET["randomId"], self::pw);
            return $unityMessageObj;
        }
        return NULL;
    }

    public static function sendWebGLStatus($user, $unityMessageObj)
    {
        global $DB;
        $record = new stdClass();
        $record->clienttoken = hash("sha256", "clienttoken:$user->id");
        $record->webglversion = $unityMessageObj->webglStatus;
        $record->timecreated = time();
        $DB->insert_record('local_scuolib', $record);
    }

    public static function getRequestURI()
    {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            $isSecure = true;
        }
        $REQUEST_PROTOCOL = $isSecure ? 'https' : 'http';
        return $REQUEST_PROTOCOL . "://" . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI'];
    }

    private static function encrypt($plainText, $password)
    {

        // CBC has an IV and thus needs randomness every time a message is encrypted
        $method = 'aes-256-cbc';

        // Must be exact 32 chars (256 bit)
        // You must store this secret random key in a safe place of your system.
        $key = substr(hash('sha256', $password, true), 0, 32);

        // Most secure iv
        // Never ever use iv=0 in real life. Better use this iv:
        $ivLen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLen);

        // av3DYGLkwBsErphcyYp+imUW4QKs19hUnFyyYcXwURU=
        $encryptedBytes = openssl_encrypt($plainText, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $encryptedBytes, $key, $as_binary = true);
        $decodedBytes = $iv . $hmac . $encryptedBytes;
        $encodedString = base64_encode($decodedBytes);

        return $encodedString;
    }

    private static function decrypt($encodedString, $password)
    {

        //initialize constants
        $method = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($method);
        $hmacLen = 32;

        $key = substr(hash('sha256', $password, true), 0, 32);

        $decodedBytes = base64_decode($encodedString);

        $iv = substr($decodedBytes, 0, $ivlen);
        $hmac = substr($decodedBytes, $ivlen,  $hmacLen);
        $encryptedBytes = substr($decodedBytes, $ivlen + $hmacLen);

        // My secret message 1234
        $plainText = openssl_decrypt($encryptedBytes, $method, $key, OPENSSL_RAW_DATA, $iv);

        $calcHmac = hash_hmac('sha256', $encryptedBytes, $key, $as_binary = true);


        if (hash_equals($hmac, $calcHmac)) //PHP 5.6+ timing attack safe comparison
        {
            return $plainText;
        } else {
            return "";
        }
    }
}
