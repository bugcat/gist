<?php namespace Bugcat\Gist\PHP\Crypto;

/**
 * PHP Class for handling Google Authenticator 2-factor authentication.
 *
 * @author bugcat
 *
 * @repository https://github.com/bugcat/gist
 * @link https://github.com/bugcat/gist/blob/master/src/PHP/Crypto/GoogleAuthenticator.php
 */
class GoogleAuthenticator
{
    /**
     * Get array with all 32 characters for decoding from/encoding to base32.
     *
     * @const array
     */
    const BASE32CHARS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '2', '3', '4', '5', '6', '7', 
        '=',  // padding char
    ];
    
    const CODELENGTH = 6;
    
    /**
     * Create new secret.
     * 16 characters, randomly chosen from the allowed base32 characters.
     *
     * @param int $secret_length
     *
     * @return string
     */
    public static function getSecret($secret_length = 16)
    {
        // Valid secret lengths are 80 to 640 bits
        if ( $secret_length < 16 || $secret_length > 128 ) {
            throw new Exception('Bad secret length');
        }
        $secret = '';
        $rnd = false;
        if ( function_exists('random_bytes') ) {
            $rnd = random_bytes($secret_length);
        } elseif ( function_exists('mcrypt_create_iv') ) {
            $rnd = mcrypt_create_iv($secret_length, MCRYPT_DEV_URANDOM);
        } elseif ( function_exists('openssl_random_pseudo_bytes') ) {
            $rnd = openssl_random_pseudo_bytes($secret_length, $crypto_strong);
            if ( !$crypto_strong ) {
                $rnd = false;
            }
        }
        if ( $rnd !== false ) {
            for ( $i = 0; $i < $secret_length; ++$i ) {
                $secret .= self::BASE32CHARS[ord($rnd[$i]) & 31];
            }
        } else {
            throw new Exception('No source of secure random');
        }
        return $secret;
    }
    
    /**
     * Calculate the code, with given secret and point in time.
     *
     * @param string   $secret
     * @param int|null $timeSlice
     *
     * @return string
     */
    public static function getCode($secret, $timeSlice = null)
    {
        if ( $timeSlice === null ) {
            $timeSlice = floor(time() / 30);
        }
        $secret = self::base32Decode($secret);
        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        // Hash it with users secret key
        $hm = hash_hmac('SHA1', $time, $secret, true);
        // Use last nipple of result as index/offset
        $offset = ord(substr($hm, -1)) & 0x0F;
        // grab 4 bytes of the result
        $hashpart = substr($hm, $offset, 4);
        
        // Unpak binary value
        $value = unpack('N', $hashpart);
        $value = $value[1];
        // Only 32 bits
        $value = $value & 0x7FFFFFFF;
        
        $modulo = pow(10, self::CODELENGTH);
        
        return str_pad($value % $modulo, self::CODELENGTH, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get otpauth url.
     *
     * @param array  $otps   
     * @param bool   $encode 
     *
     * @return string
     */
    public static function getOTP($otps, $encode = false)
    {
        $name   = $otps['name'] ?? 'name';
        $secret = $otps['secret'] ?? '';
        $title  = $otps['title'] ?? null;
        //get uri
        $otp_uri = $name . '?secret=' . $secret;
        if ( !empty($otp_uri) ) {
            $otp_uri .= '&issuer=' . $title;
        }
        $otp = 'otpauth://totp/' . $otp_uri;
        if ( $encode ) {
            $otp = urlencode($otp);
        }
        return $otp;
    }
    
    /**
     * Get QR-Code URL for image, from google charts.
     *
     * @param array  $otps   
     * @param array  $params 
     * @param bool   $rtn_url 
     *
     * @return string|image
     */
    public static function getQR($otps, $params = [], $rtn_url = true)
    {
        $otp = self::getOTP($otps, true);
        //params
        $width = $params['width'] ?? 200;
        $height = $params['height'] ?? 200;
        $wh = $width . 'x' . $height;
        $level_arr = ['L', 'M', 'Q', 'H'];
        $level = (!empty($params['level']) && in_array($params['level'], $level_arr)) ? $params['level'] : 'M';
        $api = $params['api'] ?? '';
        //TODO 增加更多的接口和自生成二维码
        switch ( $api ) {
            case 'qrserver':
                $url = "https://api.qrserver.com/v1/create-qr-code/?data={$otp}&size={$wh}&ecc={$level}";
                break;
            default:
                $url = "https://chart.apis.google.com/chart?chs={$wh}&chld={$level}|0&cht=qr&chl={$otp}";
        }
        return $url;
    }
    
    /**
     * Check if the code is correct. This will accept codes starting from $discrepancy*30sec ago to $discrepancy*30sec from now.
     *
     * @param string   $secret
     * @param string   $code
     * @param int      $discrepancy      This is the allowed time drift in 30 second units (8 means 4 minutes before or after)
     * @param int|null $currentTimeSlice time slice if we want use other that time()
     *
     * @return bool
     */
    public static function verifyCode($secret, $code, $discrepancy = 1, $currentTimeSlice = null)
    {
        if ( null === $currentTimeSlice ) {
            $currentTimeSlice = floor(time() / 30);
        }
        if ( strlen($code) != self::CODELENGTH ) {
            return false;
        }
        for ( $i = -$discrepancy; $i <= $discrepancy; ++$i ) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if ( self::timingSafeEquals($calculatedCode, $code) ) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * To decode base32.
     *
     * @param $secret
     *
     * @return bool|string
     */
    protected static function base32Decode($secret)
    {
        if ( empty($secret) ) {
            return '';
        }
        $base32charsFlipped = array_flip(self::BASE32CHARS);
        $paddingCharCount = substr_count($secret, self::BASE32CHARS[32]);
        $allowedValues = array(6, 4, 3, 1, 0);
        if ( !in_array($paddingCharCount, $allowedValues) ) {
            return false;
        }
        for ( $i = 0; $i < 4; ++$i ) {
            if ( $paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat(self::BASE32CHARS[32], $allowedValues[$i]) ) {
                return false;
            }
        }
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        for ( $i = 0; $i < count($secret); $i = $i + 8 ) {
            $x = '';
            if ( !in_array($secret[$i], self::BASE32CHARS) ) {
                return false;
            }
            for ( $j = 0; $j < 8; ++$j ) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ( $z = 0; $z < count($eightBits); ++$z ) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }
        return $binaryString;
    }
    
    /**
     * A timing safe equals comparison
     * more info here: http://blog.ircmaxell.com/2014/11/its-all-about-time.html.
     *
     * @param string $safeString The internal (safe) value to be checked
     * @param string $userString The user submitted (unsafe) value
     *
     * @return bool True if the two strings are identical
     */
    private static function timingSafeEquals($safeString, $userString)
    {
        if ( function_exists('hash_equals') ) {
            return hash_equals($safeString, $userString);
        }
        $safeLen = strlen($safeString);
        $userLen = strlen($userString);
        if ( $userLen != $safeLen ) {
            return false;
        }
        $result = 0;
        for ( $i = 0; $i < $userLen; ++$i ) {
            $result |= (ord($safeString[$i]) ^ ord($userString[$i]));
        }
        // They are only identical strings if $result is exactly 0...
        return $result === 0;
    }
}
