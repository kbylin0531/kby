<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/16
 * Time: 16:52
 */
function dump(){
    $params = func_get_args();
    //随机浅色背景
    $str='9ABCDEF';
    $color='#';
    for($i=0;$i<6;$i++) {
        $color=$color.$str[rand(0,strlen($str)-1)];
    }
    //传入空的字符串或者==false的值时 打印文件
    $traces = debug_backtrace();
    $title = "<b>File:</b>{$traces[0]['file']} << <b>Line:</b>{$traces[0]['line']} >> ";
    echo "<pre style='background: {$color};width: 100%;'><h3 style='color: midnightblue'>{$title}</h3>";
    foreach ($params as $key=>$val){
        echo '<b>Param '.$key.':</b><br />'.var_export($val, true).'<br />';
    }
    echo '</pre>';
}

function dumpout(){
//        ob_end_clean();//取消注释时打印会清空之前的输出
    $params = func_get_args();
    //随机浅色背景
    $str='9ABCDEF';
    $color='#';
    for($i=0;$i<6;$i++) {
        $color=$color.$str[rand(0,strlen($str)-1)];
    }
    //传入空的字符串或者==false的值时 打印文件
    $traces = debug_backtrace();
    $title = "<b>File:</b>{$traces[0]['file']} << <b>Line:</b>{$traces[0]['line']} >> ";
    echo "<pre style='background: {$color};width: 100%;'><h3 style='color: midnightblue'>{$title}</h3>";
    foreach ($params as $key=>$val){
        echo '<b>Param '.$key.':</b><br />'.var_export($val, true).'<br />';
    }
    exit('</pre>');
}


function json_format_protect(&$val, $key, $type = 'encode')
{
    if (!empty($val) && true !== $val) {
        $val = 'decode' == $type ? urldecode($val) : urlencode($val);
    }
}

function array_map_recursive($filter, $data) {
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
            ? array_map_recursive($filter, $val)
            : call_user_func($filter, $val);
    }
    return $result;
}