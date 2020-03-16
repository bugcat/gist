<?php namespace Bugcat\Gist\PHP\Crypto;

//數字組編碼成字符串
class NumbersToString
{
    //新分割數字乘積最大長度
    //6個z的36進制 轉10進制是2176782335
    const MAX_TEMP_NUM = 2147483647; //int長度
    
    /**
     * 编码数字组
     *
     * @param  array  $nums 数字组 限十个 每个限十位 總數字量限90個
     * @param  int    $min_length 最小编码长度 
     *                  0表示自由长度
     *                  正数表示最小长度 不足补齐 超过自动增加
     *                  负数表示其绝对值的固定长度 不足补齐 超过报错 
     * @return string $cryptstr 编码结果
     */
    public static function encrypt(array $nums, int $min_length = 0)
    {
        //将输入的数字组合并转成字符串
        $input_str = implode($nums);
        //计算该字符串的长度
        $input_len = strlen($input_str);
        //每隔九位分隔成新数组
        $new_arr = str_split($input_str, 9);
        //新数组数量
        $group_num = count($new_arr) - 1;
        if ( $group_num > 9 ) {
            self::exception('The $nums value is too many.');
            return '';
        }
        $last_len = strlen(end($new_arr)); //第一位记录最后一组的长度
        $info_num  = $last_len . $group_num; //第二位记录新数组数量
        foreach ( $nums as $temp_num ) {
            $info_num .= strlen($temp_num) - 1;
        }
        $info_str0 = base_convert($info_num, 10, 36);
        $info_str = '';
        $info_times = 1;
        $info_len = strlen($info_str0) - 1;
        for ( $i = 4; $i > 0; $i-- ) {
            $temp_times = random_int(1, $i);
            $info_str = base_convert($info_num * $temp_times, 10, 36);
            if ( strlen($info_str) == strlen($info_str0) ) {
                $info_times = $temp_times - 1;
                break;
            }
        }
        $info_pre = base_convert($info_times . $info_len, 8, 36);
        $info_str_all = $info_pre . '' . $info_str;
        
        $num_str_all = '';
        //遍历新数组
        foreach ( $new_arr as $new_num ) {
            $num_times = 0;
            $num_len = 0;
            $num_str = '';
            for ( $j = 6; $j > 0; $j-- ) {
                $temp_times = random_int(1, $j);
                $temp_num = $new_num * $temp_times;
                if ( $temp_num < self::MAX_TEMP_NUM ) {
                    $num_str = base_convert($temp_num, 10, 36);
                    $num_times = $temp_times - 1;
                    $num_len = strlen($num_str) - 1;
                    break;
                }
            }
            $num_pre = base_convert($num_times . $num_len, 6, 36);
            $num_str_all .= $num_pre . $num_str;
        }
        
        $str_valid = $info_str_all . $num_str_all;
        $str = $str_valid;
        $str_len = strlen($str_valid);
        $set_len = 0 == $min_length ? $str_len : abs($min_length);
        if ( 0 > $min_length && $str_len > $set_len ) {
            self::exception('The encrypt string is too long as set.');
            return '';
        } elseif ( $set_len > $str_len ) {
            $ext_len = $set_len - $str_len;
            $temp_str = base_convert(bin2hex(random_bytes($ext_len)), 16, 36);
            $ext_str = substr($temp_str, 0, $ext_len);
            $str .= $ext_str;
        }
        return $str;
    }
    
    /**
     * 解码数字组
     *
     * @param  str  $str 编码串 
     * @return arr  数字组
     */
    public static function decrypt(string $str)
    {
        $start = 0;
        $str_len = strlen($str);
        //解析前置信息
        $info_pre_len = 1;
        if ( $start + $info_pre_len > $str_len ) {
            $rtn = 'no pre info.';
            goto rtn;
        }
        $info_pre = substr($str, $start, $info_pre_len);
        $start += $info_pre_len;
        $info_pre_8 = sprintf('%02d', base_convert($info_pre, 36, 8));
        $info_pre_arr = str_split($info_pre_8, 1);
        $info_times = $info_pre_arr[0] + 1;
        $info_len = $info_pre_arr[1] + 1;
        //解析信息区
        if ( $start + $info_len > $str_len ) {
            $rtn = 'no info.';
            goto rtn;
        }
        $info_str = substr($str, $start, $info_len);
        $start += $info_len;
        $info_num_temp = base_convert($info_str, 36, 10);
        if ( 0 != $info_num_temp % $info_times ) {
            $rtn = 'info times error ('
            . $info_num_temp . '/' . $info_times
            . ').';
            goto rtn;
        }
        $info_num = intval($info_num_temp / $info_times);
        if ( strlen($info_num) < 3 ) {
            $rtn = 'info num < 3.';
            goto rtn;
        }
        $info_arr = str_split($info_num, 1);
        $last_len = array_shift($info_arr); //第一位记录最后一组的长度
        $group_num = array_shift($info_arr) + 1; //第二位记录新数组数量
        //开始解析数字
        $num_all = '';
        for ( $i = 1; $i <= $group_num; $i++ ) {
            //获取数字组的信息前缀
            $pre_len = 1;
            if ( $start + $pre_len > $str_len ) {
                $rtn = 'no group num pre.';
                goto rtn;
            }
            $num_pre = substr($str, $start, $pre_len);
            $start += $pre_len;
            //解析前缀信息
            $num_info = sprintf('%02d', base_convert($num_pre, 36, 6));
            $num_info_arr = str_split($num_info, 1);
            $num_times = $num_info_arr[0] + 1;
            $num_len = $num_info_arr[1] + 1;
            //获取数字信息
            if ( $start + $num_len > $str_len ) {
                $rtn = 'no group num str.';
                goto rtn;
            }
            $num_str = substr($str, $start, $num_len);
            $start += $num_len;
            //解析数字
            $num_temp = base_convert($num_str, 36, 10);
            if ( 0 != $num_temp % $num_times ) {
                $rtn = 'num times error ('
                . $num_temp . '/' . $num_times
                . ').';
                goto rtn;
            }
            $new_num = intval($num_temp / $num_times);
            //確定數字
            $new_num_len = $i == $group_num ? $last_len : 9;
            $num_all .= sprintf('%0'.$new_num_len.'d', $new_num);
        }
        //開始還原數字組
        $num_all_len = strlen($num_all);
        $num_start = 0;
        $rtn = [];
        foreach ( $info_arr as $info_len ) {
            $temp_len = $info_len + 1;
            if ( $num_start + $temp_len > $num_all_len ) {
                $rtn = 'no temp num.';
                goto rtn;
            }
            $num = substr($num_all, $num_start, $temp_len);
            $num_start += $temp_len;
            $rtn[] = $num;
        }
        rtn:
        return $rtn;
    }
    
    
    /**
     * 异常处理
     *
     * @param  string  $err     错误信息
     * @return Exception 
     */
    public static function exception(string $err)
    {
        throw new \Exception($err);
    }
    
}
