<?php namespace Bugcat\Tools\Laravel\Traits;

use Bugcat\Tools\Laravel\Models\InstModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

trait CtrlTrait
{
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
}
