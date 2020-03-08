<?php //自定義函數

function _url($link = '') {
    //獲取設定的域名鏈接
    $app_url = trim(config('app.url'), '/');
    $parts = parse_url($app_url);
    //獲取待轉換的鏈接信息
    $url_arr = parse_url($link);
    if ( isset($url_arr['path']) ) {
        $path_arr = explode('/', ltrim($url_arr['path'], '/'));
        //去除index.php段
        if ( 'index.php' == $path_arr[0] ) {
            unset($path_arr[0]);
        }
        $parts['path'] = '/' . implode('/', $path_arr);
    }
    //轉換新鏈接
    $url = http_build_url($url_arr, $parts);
    return $url;
}

function _route(...$param) {
    $link = route(...$param);
    $url = _url($link);
    return $url;
}

