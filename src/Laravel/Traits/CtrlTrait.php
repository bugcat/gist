<?php namespace Bugcat\Gist\Laravel\Traits;

use Bugcat\Gist\Laravel\Models\InstModel;
use Bugcat\Gist\Laravel\Helpers\Sttc;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

trait CtrlTrait
{
    
    /**
     * Where to redirect users after registration.
     *
     * @var array
     */
    protected $vdata = [];
    
    
    /**
     * 通用模型
     *
     * @param  string $table  table name
     * @param  array  $set    some settings
     * @return Illuminate\Database\Eloquent\Model
     */
    final protected function m(string $table, $set = [])
    {
        return InstModel::init($table, $set);
    }
    
    /**
     * 缓存
     *
     * @param  string $store
     * @return Illuminate\Support\Facades\Cache
     */
    final protected function cache(string $store = 'file')
    {
        return Cache::store($store);
    }
    
    /**
     * 请求
     *
     * @param  string $store
     * @return Illuminate\Http\Request
     */
    final protected function request(Request $request)
    {
        return $request;
    }
    
    /**
     * 设置视图变量的值
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return string
     */
    final protected function vset($key, $value = null)
    {
        if ( is_array($key) ) {
            $this->vdata = array_merge($this->vdata, $key);
        } else {
            $this->vdata[$key] = $value;
        }
    }
    
    /**
     * 输出视图
     *
     * @return view
     */
    protected function view(string $vpath, $mergeData = [])
    {
        return Sttc::view($vpath, $this->vdata, $mergeData);
    }
}
