<?php namespace Bugcat\Gist\Laravel\Traits;

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
        
        //错误信息的键
        'err_key' => 'validation.invalid_captcha',
        
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
    
    
    private function getCaptchaCfg($key = null)
    {
        if ( empty($key) ) {
            $cfg = [];
            foreach ( self::$cfg as $k => $v ) {
                $cfg[$k] = $this->__mergeCaptchaCfg($k);
            }
            return $cfg;
        } elseif ( isset(self::$cfg[$key]) ) {
            return $this->__mergeCaptchaCfg($key);
        } else {
            return null;
        }
        return self::$cfg;
    }
    
    private function __mergeCaptchaCfg($k)
    {
        if ( isset($this->captcha_cfg[$k]) ) {
            if ( is_array(self::$cfg[$k]) ) {
                return array_merge(self::$cfg[$k], $this->captcha_cfg[$k]);
            } else {
                return $this->captcha_cfg[$k];
            }
        } else {
            return self::$cfg[$k];
        }
    }
    
    
    private function setCaptchaCfg($newcfg = [])
    {
        $this->captcha_cfg = $newcfg;
    }
    
    /**
     * 驗證碼。
     *
     * @return void
     */
    private function showCaptcha($from = '')
    {
        $key = $this->__getCaptchaSessionKey($from);
        return $this->__textCaptcha($key);
    }
    
    private function __getCaptchaSessionKey($from = '')
    {
        $key = '';
        $keys_cfg = $this->getCaptchaCfg('keys');
        if ( empty($from) ) {
            $key = self::$cfg['keys']['_pre'];
        } elseif ( isset($keys_cfg[$from]) ) {
            $key = $keys_cfg[$from];
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
        $settings = $this->getCaptchaCfg('settings');
        //初始化验证码
        $captcha = new Captcha();
        $vcode = $captcha->gnrt($settings);
        $sensitive = $this->getCaptchaCfg('sensitive');
        $vcode = $sensitive ? $vcode : Str::lower($vcode);
        //存储验证码
        $hasher = new Hasher;
        $session = session();
        $hash = $hasher->make($vcode);
        $session->put($key, $hash);
        $session->save();
        //输出验证码
        $captcha->img();
        exit;
    }
    
    
    /**
     * 判断验证码是否正确
     *
     * @param  array  $info 验证码数据 一维数组
     * @param  string $from 来源控制器
     * @param  mix    $rtn  返回数据方式
     * @return bool||\Illuminate\Contracts\Validation\Validator
     */
    public function captchaValidator($info, $from, $rtn = null)
    {
        $err_key = $this->getCaptchaCfg('err_key');
        $messages = is_string($rtn) ? [$err_key => $rtn] : [];
        $validator = Validator::make($info, [
            key($info) => ['bail', 'required', 'string', function() use ($from) {
                //判断环境
                $envs = $this->getCaptchaCfg('envs_ignored');
                if ( in_array(config('app.env'), $envs) ) {
                    return true;
                }
                //获取session键
                $skey = $this->__getCaptchaSessionKey($from);
                return $this->__takeCheckCaptcha(func_get_args(), $skey);
            },],
        ], $messages);
        if ( true === $rtn ) {
            return $validator;
        }
        return $validator->validate();
    }
    
    /**
     * 验证码判断
     *
     * @param $value
     * @param $skey
     * @return bool
     */
    private final function __takeCheckCaptcha($args, $skey)
    {
        $bool = true;
        $hasher = new Hasher;
        $session = session();
        list($attribute, $value, $fail) = $args;
        if ( empty($skey) || ! $session->has($skey) ) {
            $bool = false;
        } else {
            $vcode = $session->get($skey);
            $sensitive = $this->getCaptchaCfg('sensitive');
            $value = $sensitive ? $value : Str::lower($value);
            $res = $hasher->check($value, $vcode);
            //  if verify pass,remove session
            if ( $res ) {
                $session->remove($skey);
            }
            $bool = $res;
        }
        if ( $bool ) {
            return true;
        }
        $err_key = $this->getCaptchaCfg('err_key');
        return $fail(trans($err_key));
    }
}
