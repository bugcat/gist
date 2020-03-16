<?php namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Hashing\BcryptHasher as Hasher;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Bugcat\Captcha\Captcha;

class CaptchaController extends Controller
{
    
    /**
     * The keys for all captcha session.
     *
     * @const array
     */
    const KEYS = [
        '_pre'        => 'CAPTCHA_',
        'login'       => 'CAPTCHA_LOGIN',
        'register'    => 'CAPTCHA_REGISTER',
        'pswd_forgot' => 'CAPTCHA_PSWD_FORGOT',
        'pswd_reset'  => 'CAPTCHA_PSWD_RESET',
    ];
    
    /**
     * 不需要判断验证码的环境
     *
     * @const array
     */
    const ENVS_IGNORED = [
        'local', //本地
    ]; 
    
    /**
     * 设置是否大小写敏感
     *
     * @const bool
     */
    const SENSITIVE = false;
        
    /**
     * Session
     *
     * @object
     */
    protected $session;
    
    /**
     * @var Hasher
     */
    private $hasher;
    
    /**
     * @var Str
     */
    private $str;
    
    /**
     * Constructor
     *
     * @param Session $session
     */
    public function __construct(Session $session, Hasher $hasher, Str $str)
    {
        $this->session = $session;
        $this->hasher = $hasher;
        $this->str = $str;
    }
    
    /**
     * 驗證碼。
     *
     * @return void
     */
    public function showCaptcha($from, $rand = 0)
    {
        $key = self::KEYS[$from] ?? (self::KEYS['_pre'].$from);
        $this->text($key);
    }
    
    /**
     * Initialize text verification code.
     *
     * @param  string  $key
     * @return object
     */
    private function text($key, $config = [])
    {
        //设置基本配置
        $config['text'] = $config['text'] ?? 'ENG';
        $config['charnum'] = $config['charnum'] ?? 4;
        //初始化验证码
        $captcha = new Captcha();
        $vcode = $captcha->gnrt($config);
        $vcode = self::SENSITIVE ? $vcode : $this->str->lower($vcode);
        //存储验证码
        $hash = $this->hasher->make($vcode);
        $this->session->put($key, $hash);
        $this->session->save();
        //输出验证码
        $captcha->img();
        exit;
    }
    
    /**
     * 判断验证码是否正确
     *
     * @param  array  $info 验证码数据
     * @param  string  $from 来源控制器
     * @param  string  $type 请求方式 表单或Ajax
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator($info, $from, $type = 'form')
    {
        if ( in_array(config('app.env'), self::ENVS_IGNORED) ) {
            //若当前环境不需要判断验证码 则直接略过
            return true;
        }
        $key = self::KEYS[$from] ?? (self::KEYS['_pre'].$from);
        Validator::make($info, [
            key($info) => ['bail', 'required', 'string', function() use ($type, $key) {
                if ( 'form' == $type ) {
                    return $this->checkFormCaptcha(func_get_args(), $key);
                } elseif ( 'ajax' == $type ) {
                    //TODO
                }
            },],
        ])->validate();
    }
    
    /**
     * 表单验证码判断直接返回
     *
     * @param $value
     * @param $key
     * @return bool
     */
    private final function checkFormCaptcha($args, $key)
    {
        list($attribute, $value, $fail) = $args;
        if ( $this->takeCheck($key, $value) ) {
            return true;
        }
        return $fail(trans('validation.invalid_vcode'));
    }
    
    /**
     * Captcha check
     *
     * @param $key
     * @param $value
     * @return bool
     */
    private final function takeCheck($key, $value)
    {
        if ( empty($key) || ! $this->session->has($key) ) {
            return false;
        }
        $vcode = $this->session->get($key);
        $value = self::SENSITIVE ? $value : $this->str->lower($value);
        $res = $this->hasher->check($value, $vcode);
        //  if verify pass,remove session
        if ( $res ) {
            $this->session->remove($key);
        }
        return $res;
    }
}
