<?php
/**
 * 主程序入口页
 * User: educk
 * Date: 13-11-20
 * Time: 下午12:54
 */
//分解请求参数
if(isset($_REQUEST['_PARAMS_'])){
    $temp = array();
    parse_str($_REQUEST['_PARAMS_'],$temp);
    $_POST = array_merge($_POST,$temp);
    $_REQUEST = array_merge($_REQUEST,$temp);
    $_GET = array_merge($_GET,$temp);
    unset($_REQUEST['_PARAMS_']);
}

//存放sql Map的路径，建议放到服务器要目录以外
define('BASE_PATH',str_replace('\\','/',dirname(__DIR__)).'/');
define("SQL_MAP_PATH",BASE_PATH."sqlMap/");
define('APP_PATH', BASE_PATH.'App/');

//定义项目名称
define('APP_NAME', 'App');
//定义项目路径
//开启调试模式
define('APP_DEBUG',true);
//URL模式
define("URL_MODEL", 1);

//151015定义 个别学院代码逻辑可能不同
const __SCHOOLNAME__ = '鄞州职业教育中心';
const SCHOOL_CODE = 'yzzj';
const DEAN_NAME = '教务处';//鄞州职教为教务处
if(!function_exists('dump')){
    function dump(){
        $params = func_get_args();
        $color='#';$str='9ABCDEF';//随机浅色背景
        for($i=0;$i<6;$i++) $color=$color.$str[rand(0,strlen($str)-1)];
        $traces = debug_backtrace();//0表示dump本身//如果是dumpout的内部调用,则1和2表现为call_user_func_array和dumpout,此时需要获取的是3开始的位置
        if(!empty($traces[1]['function']) and !empty($traces[2]['function']) and
            'call_user_func_array' === $traces[1]['function'] and 'dumpout' === $traces[2]['function']){
            array_shift($traces);
            array_shift($traces);
        }
        echo "<pre style='background: {$color};width: 100%;'><h3 style='color: midnightblue'><b>File:</b>{$traces[0]['file']} << <b>Line:</b>{$traces[0]['line']} >> </h3>";
        foreach ($params as $key=>$val) echo '<b>Param '.$key.':</b><br />'.var_export($val, true).'<br />';
        echo '</pre>';
    }
}
if(!function_exists('dumpout')){
    function dumpout(){
        $params = func_get_args();
        $color='#';$str='9ABCDEF';//随机浅色背景
        for($i=0;$i<6;$i++) $color=$color.$str[rand(0,strlen($str)-1)];
        $traces = debug_backtrace();//0表示dump本身//如果是dumpout的内部调用,则1和2表现为call_user_func_array和dumpout,此时需要获取的是3开始的位置
//    array_shift($traces);
//    echo "<pre>";var_dump($params,$traces );exit();
        echo "<pre style='background: {$color};width: 100%;'><h3 style='color: midnightblue'><b>File:</b>{$traces[0]['file']} << <b>Line:</b>{$traces[0]['line']} >> </h3>";
        foreach ($params as $key=>$val) echo '<b>Param '.$key.':</b><br />'.var_export($val, true).'<br />';
        echo '</pre>';
        exit();
    }
}
function loadKL(){
    static $flag  = true;
    if($flag){
        require BASE_PATH.'System/Kbylin.class.php';
        (new Kbylin())->init()->registerAutoloader();
        $flag = false;
    }
}


//加载框架入口文件
require BASE_PATH.'ThinkPHP/ThinkPHP.php';
