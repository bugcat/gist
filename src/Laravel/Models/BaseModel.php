<?php namespace Bugcat\Gist\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

//基础的模型
class BaseModel extends Model
{
    //use SoftDeletes; //软删除
    use CustomExpansionNew, CustomExpansionRewrite; //自定义扩展
    
    /**
     * The connection name for the model.
     *
     * @var string
     */
    //protected $connection;
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    //protected $primaryKey = 'id';
    
    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    //protected $keyType = 'int';
    
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    //public $incrementing = true;
    
    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    //protected $perPage = 15;
    
    /**
     * 与模型关联的表名 类名的蛇型复数形式
     *
     * @var string
     */
    //protected $table = 'users';
    
    /**
     * 指示模型是否自动维护时间戳  created_at 和 updated_at
     *
     * @var bool
     */
    //public $timestamps = false;
    
    /**
     * 模型日期列的存储格式。
     *
     * @var string
     */
    //protected $dateFormat = 'U';
    
    //自定义存储时间戳的字段名
    //const CREATED_AT = 'created_at';
    //const UPDATED_AT = 'updated_at';
    
    /**
     * 模型的默认属性值。
     *
     * @var array
     */
    /* protected $attributes = [
        'delayed' => false,
    ]; */
    
    /**
     * 初始化模型
     *
     * @param  string|array  $set   table or some settings
     * @return true
     */
    public function __init__($set)
    {
        if ( is_string($set) ) {
            $this->table = $set;
        } elseif ( is_array($set) ) {
            foreach ( $set as $key => $value ) {
                if ( '::' == $key ) {
                    foreach ( $value as $k => $v ) {
                        $this::$$k = $v;
                    }
                } else {
                    $this->$key = $value;
                }
            }
        } else {
            $this->table = $set;
        }
        return true;
    }
    
}
