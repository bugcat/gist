<?php namespace Bugcat\Tools\Laravel\Traits;

use Illuminate\Hashing\BcryptHasher as Hasher;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Bugcat\Captcha\Captcha;

trait CaptchaTrait
{
    
    /**
     * The config for the captcha.
     *
     * @var array
     */
    static private $cfg = [
        //The keys for all captcha session.
        'keys' => [
            '_pre'        => 'CAPTCHA_',
            'test'        => 'CAPTCHA_TEST',
            'login'       => 'CAPTCHA_LOGIN',
            'register'    => 'CAPTCHA_REGISTER',
            'pswd_forgot' => 'CAPTCHA_PSWD_FORGOT',
            'pswd_reset'  => 'CAPTCHA_PSWD_RESET',
        ],
        
        //不需要判断验证码的环境
        'envs_ignored' => ['local', ],
        
        //设置是否大小写敏感
        'sensitive' => false,
        
        //验证码配置
        'settings' => [
            'text'    => 'ENG',
            'charnum' => 4,
        ],
    ];
    //覆写self::$cfg的配置
    private $captcha_cfg = [];
    
    
    private function getCaptchaCfg()
    {
        return self::$cfg;
    }
    
    private function setCaptchaCfg($newcfg = [])
    {
        $this->$captcha_cfg = $newcfg;
    }
    
    /**
     * 驗證碼。
     *
     * @return void
     */
    private function showCaptcha($from = '', $rand = 0)
    {
        $key = $this->__getCaptchaSessionKey($from);
        return $this->__textCaptcha($key);
    }
    
    private function __getCaptchaSessionKey($from = '')
    {
        $key = '';
        if ( empty($from) ) {
            $key = self::$cfg['keys']['_pre'];
        } elseif ( isset($this->$captcha_cfg['keys'][$from]) ) {
            $key = $this->$captcha_cfg['keys'][$from];
        } elseif ( isset(self::$cfg['keys'][$from]) ) {
            $key = self::$cfg['keys'][$from];
        } else {
            $key = self::$cfg['keys']['_pre'] . $from;
        }
        return $key;
    }
    
    
    /**
     * Initialize text verification code.
     *
     * @param  string  $key
     * @return object
     */
    private function __textCaptcha($key)
    {
        //获取相关配置
        $setCfg = $this->captcha_cfg['settings'] ?? [];
        $config = array_merge(self::$cfg['settings'], $setCfg);
        
        //初始化验证码
        $captcha = new Captcha();
        $vcode = $captcha->gnrt($config);
        $sensitive = $this->captcha_cfg['sensitive'] ?? self::$cfg['sensitive'];
        $vcode = $sensitive ? $vcode : Str::lower($vcode);
        //存储验证码
        $hasher = new Hasher;
        $hash = $hasher->make($vcode);
        session([$key => $hash]);
        //输出验证码
        $captcha->img();
        exit;
    }
}
