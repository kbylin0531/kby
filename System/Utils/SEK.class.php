<?php
/**
 * User: linzh_000
 * Date: 2016/3/15
 * Time: 16:00
 */
namespace System\Utils;
use System\Core\Exception\ParameterInvalidException;
use System\Core\KbylinException;

/**
 * Class SEK 系统执行工具(System Execute Kits)
 * @package System\Utils
 */
final class SEK {

    /**
     * 调用位置
     */
    const CALL_PLACE_SELF       = 0; //表示调用者自身的位置
    const CALL_PLACE_FORWARD    = 1;// 表示调用调用者的位置
    /**
     * 信息组成
     */
    const CALL_ELEMENT_FUNCTION = 1;
    const CALL_ELEMENT_FILE     = 2;
    const CALL_ELEMENT_LINE     = 4;
    const CALL_ELEMENT_CLASS    = 8;
    const CALL_ELEMENT_TYPE     = 16;
    const CALL_ELEMENT_ARGS     = 32;

    /**
     * 配置类型
     * 值使用字符串而不是效率更高的数字是处于可以直接匹配后缀名的考虑
     */
    const CONF_TYPE_PHP     = 'php';
    const CONF_TYPE_INI     = 'ini';
    const CONF_TYPE_YAML    = 'yaml';
    const CONF_TYPE_XML     = 'xml';
    const CONF_TYPE_JSON    = 'json';
    
    /**
     * 变量跟踪信息
     * @var array
     */
    protected static $_traces = [];

    /**
     * 获取所有可用的数据库PDO驱动
     * 如：'mysql'或者'odbc'
     * @return array 如"['mysql','odbc',]"
     */
    public static function getAvailablePdoDrivers(){
        return \PDO::getAvailableDrivers();
    }

    /**
     * 加载模板
     * @param string $tplname 模板文件路径
     * @param mixed $vars 释放到模板中的变量
     * @param bool $clean 是否清空之前的输出，默认为true
     */
    public static function loadTemplate($tplname,$vars=null,$clean=true){
        $clean and Response::cleanOutput();
        if(is_array($vars)) extract($vars, EXTR_OVERWRITE);
        $path = SYSTEM_PATH."Tpl/{$tplname}.php";
        is_file($path) or $path = SYSTEM_PATH."Tpl/systemerror.php";
        include $path;
    }

    /**
     * 跟踪trace信息
     * @param ...
     * @return void
     */
    public static function trace(){
        $values = func_get_args();
        $trace = debug_backtrace();
        if(isset($trace[0])){
            //显示调用trace方法的行号
            $path = "{$trace[0]['class']}{$trace[0]['type']}{$trace[0]['function']}[Line:{$trace[0]['line']}]";
        }else{//特殊情况，使用特殊值
            $path = uniqid('[ANY]');
        }
        self::$_traces[$path] = $values;
    }

    /**
     * 是否允许显示
     * @return bool
     */
    private static $_allowTrace = true;

    /**
     * 禁用trace页面
     * @return bool
     */
    public static function disableTrace(){
        self::$_allowTrace = false;
    }

    /**
     * 启用trace页面
     * @return bool
     */
    public static function enableTrace(){
        self::$_allowTrace = true;
    }

    /**
     * 显示trace页面
     * @param array $status 状态信息
     * @param int $accuracy 精确度
     * @return void
     */
    public static function showTrace(array $status,$accuracy=6){
        if(!self::$_allowTrace) return ;//如果被禁止了trace页面,则不显示该页面
        //吞吐率  1秒/单次执行时间
        if(count($status) > 1){
            $last  = end($status);
            $first = reset($status);            //注意先end后reset
            $stat = [
                1000*round($last[0] - $first[0], $accuracy),
                number_format(($last[1] - $first[1]), $accuracy)
            ];
        }else{
            $stat = [0,0];
        }
        $reqs = empty($stat[0])?'Unknown':1000*number_format(1/$stat[0],8).' req/s';

        //包含的文件数组
        $files  =  get_included_files();
        $info   =   [];
        foreach ($files as $key=>$file){
            $info[] = $file.' ( '.number_format(filesize($file)/1024,2).' KB )';
        }

        //运行时间与内存开销
        $fkey = null;
        $cmprst = [
            'Total' => "{$stat[0]}ms",//一共花费的时间
        ];
        foreach($status as $key=>$val){
            if(null === $fkey){
                $fkey = $key;
                continue;
            }
            $cmprst["[$fkey --> $key]    "] =
                number_format(1000 * floatval($status[$key][0] - $status[$fkey][0]),6).'ms&nbsp;&nbsp;'.
                number_format((floatval($status[$key][1] - $status[$fkey][1])/1024),2).' KB';
            $fkey = $key;
        }
        $vars = [
            'trace' => [
                'General'       => [
                    'Request'   => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']).' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_METHOD'],
                    'Time'      => "{$stat[0]}ms",
                    'QPS'       => $reqs,//吞吐率
                    'SessionID' => session_id(),
                    'Cookie'    => var_export($_COOKIE,true),
                    'Obcache-Size'  => number_format((ob_get_length()/1024),2).' KB (Unexpect Trace Page!)',//不包括trace
                ],
                'Trace'         => self::$_traces,
                'Files'         => array_merge(['Total'=>count($info)],$info),
                'Status'        => $cmprst,
                'GET'           => $_GET,
                'POST'          => $_POST,
                'SERVER'        => $_SERVER,
                'FILES'         => $_FILES,
                'ENV'           => $_ENV,
                'SESSION'       => isset($_SESSION)?$_SESSION:['SESSION state disabled'],//session_start()之后$_SESSION数组才会被创建
                'IP'            => [
                    '$_SERVER["HTTP_X_FORWARDED_FOR"]'  =>  isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:'NULL',
                    '$_SERVER["HTTP_CLIENT_IP"]'  =>  isset($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:'NULL',
                    '$_SERVER["REMOTE_ADDR"]'  =>  $_SERVER['REMOTE_ADDR'],
                    'getenv("HTTP_X_FORWARDED_FOR")'  =>  getenv('HTTP_X_FORWARDED_FOR'),
                    'getenv("HTTP_CLIENT_IP")'  =>  getenv('HTTP_CLIENT_IP'),
                    'getenv("REMOTE_ADDR")'  =>  getenv('REMOTE_ADDR'),
                ],
            ],
        ];
        SEK::loadTemplate('trace',$vars,false);//参数三表示不清空之前的缓存区
    }

    /**
     * Excel计算单元格列的名称
     * 如第6列对应'F'，27列对应'AA'
     * 注意下面的数字
     * ord('Z') = 90  ord('A') = 65
     * @param int $num 相对null的偏移
     * @return string
     */
    public static function excelchar($num){
        static $cache = [];
        if(!isset($cache[$num])){
            $num = intval($num);
            $gap = $num - 90;
            if($gap > 0){//是否超过一个'Z'的限制
                $piecenum = floor($gap/26); // 获取段数目
                $cache[$num] = self::excelchar(65 + $piecenum).chr(65+$gap - $piecenum * 26);
            }else{
                $cache[$num] = chr($num);
            }
        }
        return $cache[$num];
    }

    /**
     * 打印参数变量
     */
    public static function dump(){
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

    /**
     * 打印参数变量并且退出
     */
    public static function dumpout(){
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

    /**
     * 浏览器友好的变量输出
     * @param mixed $var 变量
     * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
     * @param string $label 标签 默认为空
     * @param boolean $strict 是否严谨 默认为true
     * @return void|string
     */
    public static function thinkDump($var, $echo=true, $label=null, $strict=true) {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        }else
            return $output;
    }

    /**
     * TP3.2.3
     * 根据PHP各种类型变量生成唯一标识号
     * @param mixed $mix
     *      参数为对象时返回其hash_id(对于每个对象都是唯一的)
     * @return string
     */
    public static function fetchVarIdentify($mix) {
        if (is_object($mix)) {
            return spl_object_hash($mix);
        } elseif (is_resource($mix)) {
            //资源名称以及转化为字符串时的名称
            $mix = get_resource_type($mix) . strval($mix);
        } else {
            $mix = serialize($mix);
        }
        return md5($mix);
    }

    /**
     * merge configure from $source to $dest
     * @param array $dest dest config
     * @param array $sourse sourse config whose will overide the $dest config
     * @param bool|false $cover it will merge the target in recursion while $cover is true
     *                  (will perfrom a high efficiency for using the built-in function)
     * @return void
     */
    public static function merge(array &$dest,array $sourse,$cover=false){
        if($cover){
            $dest = array_merge($dest,$sourse);
        }else{
            foreach($sourse as $key=>$val){
                if(isset($dest[$key]) and is_array($val)){
                    self::merge($dest[$key],$val);
                }else{
                    $dest[$key] = $val;
                }
            }
        }
    }


    /**
     * 判断是否有不合法的参数存在，不合法的参数参照参数一（使用严格的比较-判断类型）
     * 第一个参数将会被认为是不合法的值，参数一可以是单个字符串或者数组
     * 第二个参数开始是要比较的参数列表，如果任何一个参数"匹配"了参数一，将返回true表示存在不合法的参数
     * @return bool
     */
    public static function checkInvalidValueExistInStrict(){
        $params = func_get_args();
        return self::checkInvalidValueExist($params,true);
    }

    /**
     * 判断是否有不合法的参数存在，不合法的参数参照参数一（使用宽松的比较-不判断类型）
     * 第一个参数将会被认为是不合法的值，参数一可以是单个字符串或者数组
     * 第二个参数开始是要比较的参数列表，如果任何一个参数"匹配"了参数一，将返回true表示存在不合法的参数
     * @return bool
     */
    public static function checkInvalidValueExistInEase(){
        $params = func_get_args();
        return self::checkInvalidValueExist($params);
    }

    /**
     * 检测是否存在不合法的值
     * 参数一种第一个元素作为比较对象
     * 如果是数组，则数组中都是不合法的值，如果是单值，使用===进行比较
     * @param array $params 参与比较的值的有序集合
     * @param bool|false $district 比较时是否判断其类型，默认是
     * @return bool
     */
    public static function checkInvalidValueExist($params,$district=false){
        $invalidVal = array_shift($params);
        foreach ($params as $key=>&$val){
            if(is_array($invalidVal)){
                //参数三决定是否使用严格的方式
                if(in_array(trim($val),$invalidVal,$district)) return true;
            }else{
                if($district? ($invalidVal === $val) : ($invalidVal == $val)) return true;
            }
        }
        return false;
    }


    /**
     * 清空输出缓存
     * @return void
     */
    public static function cleanOutput(){
        ob_get_level() > 0 and ob_end_clean();
    }

    /**
     * 检查或获取开启的PHP扩展
     * @param null|string $extname 扩展名称
     * @return array|bool
     */
    public static function phpExtend($extname=NULL){
        if(isset($extname)){
            //dl($extname) 运行时开启
            return extension_loaded($extname);
        }
        return get_loaded_extensions();
    }
    /**
     * 检查PHP版本是否大于等于参数代表的值
     * @param $version
     * @return mixed
     */
    public static function checkPhpVersion($version){
        static $_is_php = [];
        $version = (string) $version;
        if ( ! isset($_is_php[$version])){
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }

    /**
     * 模块序列转换成数组形式
     * 且数组形式的都是大写字母开头的单词形式
     * @param string|array $modules 模块序列
     * @param string $mmbridge 模块之间的分隔符
     * @return array
     * @throws KbylinException
     */
    public static function toModulesArray($modules, $mmbridge='/'){
        if(is_string($modules)){
            if(false === stripos($modules,$mmbridge)){
                $modules = [$modules];
            }else{
                $modules = explode($mmbridge,$modules);
            }
        }
        if(!is_array($modules)){
            throw new KbylinException('Parameter should be an array!');
        }
        return array_map(function ($val) {
            return StringHelper::toJavaStyle($val);
        }, $modules);
    }
    /**
     * 模块学列数组转换成模块序列字符串
     * 模块名称全部小写化
     * @param array|string $modules 模块序列
     * @param string $mmb
     * @return string
     * @throws KbylinException
     */
    public static function toModulesString($modules,$mmb='/'){
        if(is_array($modules)){
            foreach($modules as &$modulename){
                $modulename = StringHelper::toCStyle($modulename);
            }
            $modules = implode($mmb,$modules);
        }
        if(!is_string($modules)) throw new KbylinException('Invalid Parameters');
        return trim($modules);
    }
    /**
     * 将参数序列装换成参数数组，应用Router模块的配置
     * @param string $params 参数字符串
     * @param string $ppb
     * @param string $pkvb
     * @return array
     */
    public static function toParametersArray($params,$ppb='/',$pkvb='/'){//解析字符串成数组
        $pc = [];
        if($ppb !== $pkvb){//使用不同的分割符
            $parampairs = explode($ppb,$params);
            foreach($parampairs as $val){
                $pos = strpos($val,$pkvb);
                if(false === $pos){
                    //非键值对，赋值数字键
                }else{
                    $key = substr($val,0,$pos);
                    $val = substr($val,$pos+strlen($pkvb));
                    $pc[$key] = $val;
                }
            }
        }else{//使用相同的分隔符
            $elements = explode($ppb,$params);
            $count = count($elements);
            for($i=0; $i<$count; $i += 2){
                if(isset($elements[$i+1])){
                    $pc[$elements[$i]] = $elements[$i+1];
                }else{
                    //单个将被投入匿名参数,先废弃
                }
            }
        }
        return $pc;
    }

    /**
     * 将参数数组转换成参数序列，应用Router模块的配置
     * @param array $params 参数数组
     * @param string $ppb
     * @param string $pkvb
     * @return string
     */
    public static function toParametersString(array $params=null,$ppb='/',$pkvb='/'){
        //希望返回的是字符串是，返回值是void，直接修改自$params
        if(empty($params)) return '';
        $temp = '';
        if($params){
            foreach($params as $key => $val){
                $temp .= "{$key}{$pkvb}{$val}{$ppb}";
            }
            return substr($temp,0,strlen($temp) - strlen($ppb));
        }else{
            return $temp;
        }
    }



    /**
     * 获取日期
     * 返回格式：
     * array (
     *      0 => '2016-03-17 09:55:55',
     *      1 => '2016-03-17',
     *      2 => '09',
     *      3 => '09:55:55',
     * )
     * @param bool|false $refresh
     * @return array 日期各个部分数组
     */
    public static function getDate($refresh=false){
        static $_date = [];
        if($refresh or !$_date){
            //完整时间
            $_date[0] = date('Y-m-d H:i:s');
            $_date[1] = substr($_date[0],0,10);
            $_date[2] = substr($_date[0],11,2);
            $_date[3] = substr($_date[0],11);
        }
        return $_date;
    }

    /**
     * 加载配置文件 支持格式转换 仅支持一级配置
     * @param string $file 配置文件名
     * @param callable $parser 配置解析方法 有些格式需要用户自己解析
     * @return array|mixed
     * @throws KbylinException
     */
    public static function parseConfigFile($file,callable $parser=null){
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        switch ($ext) {
            case self::CONF_TYPE_PHP:
                return include $file;
            case self::CONF_TYPE_INI:
                return parse_ini_file($file);
            case self::CONF_TYPE_YAML:
                return yaml_parse_file($file);
            case self::CONF_TYPE_XML:
                return (array)simplexml_load_file($file);
            case self::CONF_TYPE_JSON:
                return json_decode(file_get_contents($file), true);
            default:
                if (isset($parser)) {
                    return $parser($file);
                } else {
                    throw new KbylinException('无法解析配置文件');
                }
        }
    }
    /**
     * 获取调用者本身的位置
     * @param int $elements 为0是表示获取全部信息
     * @param int $place 位置属性
     * @return array|string
     */
    public static function getCallPlace($elements=0, $place=self::CALL_PLACE_FORWARD){
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2);
        if($elements){
            $result = [];
            $elements & self::CALL_ELEMENT_ARGS     and $result[self::CALL_ELEMENT_ARGS]    = isset($trace[$place]['args'])?$trace[$place]['args']:null;
            $elements & self::CALL_ELEMENT_CLASS    and $result[self::CALL_ELEMENT_CLASS]   = isset($trace[$place]['class'])?$trace[$place]['class']:null;
            $elements & self::CALL_ELEMENT_FILE     and $result[self::CALL_ELEMENT_FILE]    = isset($trace[$place]['file'])?$trace[$place]['file']:null;
            $elements & self::CALL_ELEMENT_FUNCTION and $result[self::CALL_ELEMENT_FUNCTION]= isset($trace[$place]['function'])?$trace[$place]['function']:null;
            $elements & self::CALL_ELEMENT_LINE     and $result[self::CALL_ELEMENT_LINE]    = isset($trace[$place]['line'])?$trace[$place]['line']:null;
            $elements & self::CALL_ELEMENT_TYPE     and $result[self::CALL_ELEMENT_TYPE]    = isset($trace[$place]['type'])?$trace[$place]['type']:null;
            return $result;
        }else{
            return $trace[$place];
        }
    }

    /**
     * 判断一个变量是否设置
     * <code>
     *  $vars = [
     *      'A' => [
     *          'B' => 3,
     *      ],
     *  ]
     *
     *  self::keysExistInArray('A.B',$vars)
     * </code>
     * @param string $keys
     * @param array $array
     * @return bool 键值是否存在
     * @throws KbylinException
     */
    public static function keysExistInArray($keys,$array){
        $keys = explode('.', $keys);
        if(false === $keys) throw new KbylinException("[{$keys}]不合法！");
        foreach ($keys as $val) {
            if (!isset($array[$val])) {
                return false;
            } else {
                $array = $array[$val];
            }
        }
        return true;
    }

    /**
     * 获取PDO对象中的错误信息
     * @param \PDO $pdo
     * @return null|string
     * @throws ParameterInvalidException
     */
    public static function fetchPDOError(\PDO $pdo){
        $pdoError = $pdo->errorInfo();
        return null !== $pdoError[0]? // PDO错误未被设置或者错误未发生,0位的值为null
            "Code:{$pdoError[0]} >>> [{$pdoError[1]}]:[{$pdoError[2]}]":null;
    }


    /**
     * 获取PDOStatemnent对象上查询时发生的错误
     * 错误代号参照ANSI CODE ps: https://docs.oracle.com/cd/F49540_01/DOC/server.815/a58231/appd.htm
     * @param \PDOStatement|null $statement
     * @return string
     */
    public static function fetchStatementError(\PDOStatement $statement){
        $stmtError = $statement->errorInfo();
        return 0 !== intval($stmtError[0])?"Error Code:[{$stmtError[0]}]::[{$stmtError[1]}]:[{$stmtError[2]}]":'';
    }


    /**
     * 从字面商判断$path是否被包含在$scope的范围内
     * @param string $path 路径
     * @param string $scope 范围
     * @return bool
     */
    public static function checkPathContainedInScope($path, $scope){
        if(false !== strpos($path,'\\\\'))  $path  = str_replace('\\\\','/',$path);
        if(false !== strpos($scope,'\\\\')) $scope = str_replace('\\\\','/',$scope);
        $path = rtrim($path,'/');
        $scope = rtrim($scope,'/');
        return (IS_WIN?stripos($path,$scope):strpos($path,$scope)) === 0;
    }

}