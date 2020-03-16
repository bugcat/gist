<?php namespace Bugcat\Gist\Laravel\Helpers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\{DB, Route};

//公用静态方法
class Sttc
{
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  array   $mergeData
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    final static function view($view = null, $data = [], $mergeData = [])
    {
        $factory = app(ViewFactory::class);
        /** 在config/view.php文件中 增加以下配置 可根据自己需要来增加
        'extensions' => [
            //'blade.php' => 'blade',
            //'php' => 'php',
            //'css' => 'file',
            'html' => 'blade',
            'tpl' => 'blade',
        ], 
        **/
        $cfg_ext = config('view.extensions') ?? [];
        $resolver = null;
        foreach ( array_reverse($cfg_ext) as $extension => $engine ) {
            $factory->addExtension($extension, $engine, $resolver);
        }
        //$obj_ext = $factory->getExtensions(); var_dump($obj_ext); die; //测试
        
        if (func_num_args() === 0) {
            return $factory;
        }
        
        return $factory->make($view, $data, $mergeData);
    }

    /**
     * URL后缀名的路由设置
     *
     * @param  array  $list  //[ [http_method, uri, class@obj_method, name], ]
     * @return 
     */
    final static function routeSuffix($list = [])
    {
        //在config/app.php文件中 增加以下配置 可根据自己需要来增加
        //'uri_suffix' => ['html', 'htm', 'php', 'jsp', 'asp', 'aspx', 'py'],
        $suffix = config('app.uri_suffix');
        foreach ( $list as $li ) {
            list($method, $uri, $obj) = $li;
            $uri_list = [];
            //将后缀名添加到规则中
            foreach ( $suffix as $suf ) {
                $uri_list[] = $uri . '.' . $suf;
            }
            $uri_list[] = $uri;
            //再将这些规则写到路由中
            $i = 0;
            foreach ( $uri_list as $u ) {
                //给首个规则命名
                if ( 0 == $i && isset($li[3]) ) {
                    $name = $li[3];
                    Route::$method($u, $obj)->name($name);
                } else {
                    Route::$method($u, $obj);
                }
                $i++;
            }
        }
    }
    
    /**
     * Get recent time.
     *
     * @param  obj  $timestamp
     * @return str
     */
    final static function rTime($timestamp)
    {
        $now = time();
        $time = $timestamp->getTimestamp();
        $diff = $now - $time;
        if ( $diff < 12 * 3600 ) {
            //十二小时内的 显示时分
            return $timestamp->format('H:i');
        } elseif ( $diff < 180 * 24 * 3600 ) {
            //半年以内的 显示月日
            return $timestamp->format('m-d');
        } else {
            //其余的 显示年
            return $timestamp->format('y年');
        }
    }

    /**
     * 獲取頁碼列
     *
     * @param  int $maxp   總頁數
     * @param  int $nowp   當面頁碼
     * @param  int $psize  [可選]頁碼顯示單邊寬度
     *
     * @return array
     */
    final static function pages($maxp, $nowp, $psize = 3)
    {
        $parr = [];
        $minp = 1;
        if ( $maxp < $minp || $nowp < $minp || $nowp > $maxp || $psize < 1 ) {
            return [1];
        }
        $startp = max($nowp - $psize, $minp);
        $endp   = min($nowp + $psize, $maxp);
        $mid_arr = range($startp, $endp);
        switch ( $startp - $minp ) {
            case 0:
                $left_arr = [];
                break;
            case 1:
                $left_arr = [$minp];
                break;
            case 2:
                $left_arr = [$minp, $minp + 1];
                break;
            default:
                $left_arr = [$minp, 0];
        }
        switch ( $maxp - $endp ) {
            case 0:
                $right_arr = [];
                break;
            case 1:
                $right_arr = [$maxp];
                break;
            case 2:
                $right_arr = [$maxp - 1, $maxp];
                break;
            default:
                $right_arr = [0, $maxp];
        }
        return array_merge($left_arr, $mid_arr, $right_arr);
    }
    
    /**
     * 獲取記錄列表
     *
     * @param  array  $dbset 数据库查询设置
     * @param  array  $pageset 页码设置
     * @return object
     */
    final static function getPageList($dbset = [], $pageset = [])
    {
        $pagesize = $pageset['pagesize'] ?? 1;
        $pagesize = $pagesize > 0 ? $pagesize : 1; //每页数量
        
        $page = $pageset['page'] ?? 1;
        $page = $page > 0 ? $page : 1; //当前页码
        
        $pagenum = 1;
        $count = 0;
        $list = [];
        
        if ( isset($dbset['table']) ) {
            if ( isset($dbset['connection']) ) {
                $db = DB::connection($dbset['connection'])->table($dbset['table']);
            } else {
                $db = DB::table($dbset['table']);
            }
        } else {
            goto end; 
        }
        if ( isset($dbset['where']) ) {
            $db = $db->where($dbset['where']);
        }
        //所有規則
        if ( isset($dbset['rule']) ) {
            self::setDBRule($db, $dbset['rule']);
        }
        
        $count = $db->count(); //总记录条数
        if ( 0 == $count ) {
            goto end; 
        }
        $pagenum = ceil($count / $pagesize); //总页码数
        $pagenum = $pagenum > 0 ? $pagenum : 1;
        $page = $page < $pagenum ? $page : $pagenum;
        $offset = ($page - 1) * $pagesize;
        
        //查询数据
        if ( isset($dbset['order']) ) {
            foreach ( $dbset['order'] as $key => $val ) {
                if ( '__raw' == $key ) {
                    $db = $db->orderByRaw(DB::raw($val));
                } else {
                    $db = $db->orderBy($key, $val);
                }
            }
        }
        if ( isset($dbset['field']) ) {
            $db = $db->select(...$dbset['field']);
        }
        //DB::connection()->enableQueryLog();
        $list = $db->offset($offset)->limit($pagesize)->get();
        //var_dump(DB::getQueryLog());
        end:
        return [
            'pagesize' => $pagesize,
            'pagenum' => $pagenum,
            'count' => $count,
            'nowpage' => $page,
            'list' => $list,
        ];
    }
    
    /**
     * 生成查詢規則
     *
     * @param  DB  $db 數據庫
     * @param  array  $rule 規則
     * @return bool
     */
    final static function setDBRule(& $db, $rule = [])
    {
        //常規規則
        if ( isset($rule['where']) ) {
            $db = $db->where($rule['where']);
        }
        //JSON 規則
        if ( isset($rule['inJson']) ) {
            $inJson = $rule['inJson'];
            if ( isset($inJson['key'], $inJson['value']) ) {
                $bool = $inJson['bool'] ?? 'and';
                $not = $inJson['not'] ?? false;
                $db = $db->whereJsonContains($inJson['key'], $inJson['value'], $bool, $not);
            } else {
                foreach ( $inJson as $ij ) {
                    $bool = $ij['bool'] ?? 'and';
                    $not = $ij['not'] ?? false;
                    $db = $db->whereJsonContains($ij['key'], $ij['value'], $bool, $not);
                }
            }
        }
        //in 規則
        if ( isset($rule['in']) ) {
            $in = $rule['in'];
            if ( isset($in[0], $in[1]) ) {
                $db = $db->whereIn($in[0], $in[1]);
            } else {
                foreach ( $in as $ni_k => $ni_v ) {
                    $db = $db->whereIn($ni_k, $ni_v);
                }
            }
        }
        //no in 規則
        if ( isset($rule['notIn']) ) {
            $notIn = $rule['notIn'];
            if ( isset($notIn[0], $notIn[1]) ) {
                $db = $db->whereNotIn($notIn[0], $notIn[1]);
            } else {
                foreach ( $notIn as $ni_k => $ni_v ) {
                    $db = $db->whereNotIn($ni_k, $ni_v);
                }
            }
        }
        return true;
    }
    
    /**
     * 獲取過去的時間 Difference Time 
     *
     * @param  mix  $time   時間
     * @param  str  $type   時間格式 int|str|stamp
     * @param  mix  $target 目標時間 默認爲當前
     * @return object
     */
    final static function dTime($time, $type = 'int', $target = null)
    {
        $arr = ['零', 
              '一',   '兩',   '三',   '四',   '五',   '六',   '七',   '八',   '九',   '十', 
            '十一', '十二', '十三', '十四', '十五', '十六', '十七', '十八', '十九', '二十', 
            '廿一', '廿二', '廿三', '廿四', '廿五', '廿六', '廿七', '廿八', '廿九', '三十', 
            '卅一',
        ];
        $t0 = 0;
        $t1 = time();
        switch ( $type )
        {
            case 'int':
                $t0 = intval($time);
                if ( !empty($target) ) {
                    $t1 = intval($target);
                }
                break;  
            case 'str':
                $t0 = strtotime($time);
                if ( !empty($target) ) {
                    $t1 = strtotime($target);
                }
                break;
            case 'stamp':
                $t0 = $time->getTimestamp();
                if ( !empty($target) ) {
                    $t1 = $target->getTimestamp();
                }
                break;
            default:
                $t0 = 0;
        }
        $diff = abs($t0 - $t1);
        $ext = $t0 > $t1 ? '後' : '前';
        $pre = '';
        if ( $diff < 900 ) {
            //一刻鐘內
            $ext = '內';
            $pre = '一刻鐘';
        } elseif ( $diff >= 900 && $diff < 7200 ) {
            //一刻鐘到七刻鐘
            $kz = floor($diff / 900);
            $pre = $arr[$kz] . '刻鐘';
        } elseif ( $diff >= 7200 && $diff < 7200 * 12 ) {
            //一時辰到十一個時辰
            $sc = floor($diff / 7200);
            $kz = floor(($diff % 7200) / 900);
            $pre = $arr[$sc] . '個時辰' . $arr[$kz] . '刻鐘';
        } elseif ( $diff >= 86400 && $diff < 86400*10 ) {
            //一天到十天
            $day = floor($diff / 86400);
            $sc  = floor(($diff % 86400) / 7200);
            $pre = $arr[$day] . '天'.$arr[$sc] . '個時辰';
        } elseif ( $diff >= 86400*10 && $diff < 86400*30 ) {
            //十天到三十天
            $day = floor($diff / 86400);
            $pre = $arr[$day] . '天';
        } elseif ( $diff >= 86400 * 30 && $diff < 86400 * 365 ) {
            //一月到十二月
            $month = floor($diff / (86400 * 30));
            $pre = $arr[$month] . '個月';
        } else {
            //一年以上
            $year = floor($diff / (86400 * 365));
            $year = $year > 30 ? 30 : $year;
            $pre = $arr[$year] . '年';
        }
        return $pre.$ext;
    }
    
    /**
     * 字節轉換
     *
     * @param  int  $size   字節數
     * @param  arr  $param  參數 待擴展
     *     @key  
     * @return object
     */
    final static function byteFormat(int $size, $param = [])
    {
        //定義字節符號
        $karr = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB', 'BB');
        //獲取當前最適合的位置
        $key = 0;
        for ( $i = 0; $i < count($karr); $i++ ) {
            if ( $size >= 1000 ) {
                $size = $size / 1024;
            } else {
                $key = $i;
                break;
            }
        }
        //顯示
        $new_size = 0;
        if ( $size < 10 ) {
            $new_size = number_format($size, 2);
            if ( $new_size >= 10 ) {
                $new_size = number_format($size, 1);
            }
        } elseif ( $size < 100 ) {
            $new_size = number_format($size, 1);
            if ( $new_size >= 100 ) {
                $new_size = number_format($size, 0);
            }
        } else {
            $new_size = number_format($size, 0);
        }
        return $new_size . $karr[$key];
    }
    
    /**
     * 嘗試返回靜態文件
     *
     * @param  string  $path   路徑
     * @return 
     */
    final static function tryFile($path)
    {
        //若是可讀文件
        if ( is_readable($path) ) {
            //獲取文件信息
            $file = pathinfo($path);
            $ext = $file['extension'] ?? '';
            //獲取文件頭
            $mime_arr = [
                'css' => 'text/css',
                'js'  => 'application/javascript',
            ];
            $mime = $mime_arr[$ext] ?? mime_content_type($path);   
            //返回文件流
            return response()->stream(
                function () use ($path) {
                    //輸入文件流
                    echo file_get_contents($path);
                }, 
                200, 
                ['Content-Type' => $mime] //指定文件頭
            );
        } else  {
            //否則直接返回404
            return abort(404);
        }
    }
    
    
}
