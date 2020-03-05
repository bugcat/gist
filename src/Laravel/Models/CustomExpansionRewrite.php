<?php namespace Bugcat\Tools\Laravel\Models;

//复写的扩展
trait CustomExpansionRewrite
{
    
    /**
     * 创建一个 Eloquent 集合实例.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new CustomCollection($models);
    }
    
    /**
     * Cast an attribute to a native PHP type.
     * 自定义 casts 获取
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if ( is_null($value) ) {
            //return $value;
        }
        switch ( $this->getCastType($key) ) {
            case 'list':
                return array_values(json_decode($value, true) ?: []);
            case 'object':
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            default:
                return parent::castAttribute($key, $value);
        }
    }
    
    /**
     * Set a given attribute on the model.
     * 自定义 casts 设定
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ( $this->hasCast($key, ['list']) && ! is_null($value) ) {
            
            $value = $this->asJson( array_values($value) );
            
            $this->attributes[$key] = $value;
        }
        return parent::setAttribute($key, $value);
    }
    
    /**
     * Encode the given value as JSON.
     * 复写
     *
     * @param  mixed  $value
     * @return string
     */
    protected function asJson($value)
    {
        return json_encode($value, 256);
    }
    
}
