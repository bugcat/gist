<?php namespace Bugcat\Tools\Laravel\Models;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

//创建模型实例
class InstModel
{
    
    //模型的命名空间
    const MNS = 'App\Models\\';
    
    /**
     * 加载模型
     *
     * @param  string $table  table name
     * @param  array  $set    some settings
     * @return Illuminate\Database\Eloquent\Model
     */
    public static function init(string $table, $set = [])
    {
        //判断表名
        if ( empty($table) ) {
            return null; //空表名
        } elseif ( 'users' == $table ) {
            //当为用户表时 直接返回用户类
            return new \App\User();
        } else {
            $mobj = 'Bugcat\Tools\Laravel\Models\BaseModel'; //默认模型
            //判断表名
            $arr = explode('.', $table);
            if ( isset($arr[1]) ) {
                $ns = self::MNS . ucfirst($arr[0]) . '\\';
                $t = $arr[1];
            } else {
                $ns = self::MNS;
                $t = $arr[0];
            }
            //生成模型类名
            $singular = Str::singular($t);
            $name = Str::studly($singular);
            $class = $ns . $name;
            if ( class_exists($class) ) {
                $mobj = $class;
            } else {
                $file = new Filesystem;
                $md   = app_path('Models');
                $dirs = $file->directories($md);
                foreach ( $dirs as $d ) {
                    $dir = trim(str_replace($md, '', $d), DIRECTORY_SEPARATOR);
                    $ns = self::MNS . ucfirst($dir) . '\\';
                    $class = $ns . $name;
                    if ( class_exists($class) ) {
                        $mobj = $class;
                        break;
                    }
                }
            }
            //开始加载模型
            $m = new $mobj();
            if ( method_exists($m, '__init__') ) {
                $set['table'] = $table;
                $m->__init__($set);
            }
            return $m;
        }
        return null;
    }
    
}