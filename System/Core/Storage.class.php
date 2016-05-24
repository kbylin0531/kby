<?php
/**
 * User: Administrator
 * Date: 2015/8/25
 * Time: 9:08
 */
namespace System\Core;
use System\Core\Storage\File;
use System\Core\Storage\StorageInterface;
use System\Traits\Crux;

/**
 * Class Storage 持久化存储类
 * 实际文件可能写在伺服器的文件中，也可能存放到数据库文件中，或者远程文件服务器中
 * @package System\Core
 */
class Storage {

    use Crux;

    const CONF_NAME = 'storage';
    const CONF_CONVENTION = [
        'DRIVER_DEFAULT_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            File::class,
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                'READ_LIMIT_ON'     => true,
                'WRITE_LIMIT_ON'    => true,
                'READABLE_SCOPE'    => KL_BASE_PATH,
                'WRITABLE_SCOPE'    => KL_RUNTIME_PATH,
                'ACCESS_FAILED_MODE'    => MODE_EXCEPTION,
            ]
        ],

    ];


    /**
     * 目录存在与否
     */
    const IS_DIR = -1;
    const IS_FILE = 1;
    const IS_EMPTY  =0;

    /**
     * 驱动实例
     * @var StorageInterface[]
     */
    private static $_instances = [];
    /**
     * 当前增再使用的驱动的角标
     * @var int|string
     */
    private static $_curindex = null;

    /**
     * 获取驱动
     * @param int|string $index 驱动器ID,null或者参数未设置时为null
     * @return StorageInterface
     */
    private static function getDriver($index){
        self::using($index);
        if(!isset(self::$_instances[$index])){
            self::$_instances[$index] = self::getDriverInstance($index);
        }
        return self::$_instances[$index];
    }

    /**
     * 指定使用的驱动角标
     * @param int|string $index
     * @return void
     * @throws KbylinException 角标不存在时抛出异常
     */
    public static function using($index){
        $convention = self::getConventions();
        if(key_exists($index,$convention['DRIVER_CLASS_LIST'])){
            throw new KbylinException("Driver {$index} not found!");
        }
        self::$_curindex = $index;
    }

    /**
     * 获取文件内容
     * @param string $filepath 文件路径
     * @param string $fileEncoding 文件内容实际编码
     * @param bool $recursion 如果读取到的文件是目录,是否进行递归读取,默认为false
     * @return string|array|false|null 返回文件时间内容;返回null表示在访问的范围之外
     */
    public static function read($filepath, $fileEncoding=null,$recursion=false){
        return self::getDriver(self::$_curindex)->read($filepath,$fileEncoding,$recursion);
    }

    /**
     * 文件写入
     * @param string $filepath 文件名
     * @param string $content 文件内容
     * @param string $write_encode 写入编码
     * @param string $text_encode 文本本身的编码格式
     * @return bool 是否成功写入|返回null表示在访问的范围之外
     */
    public static function write($filepath,$content,$write_encode='UTF-8',$text_encode='UTF-8') {
        return self::getDriver(self::$_curindex)->write($filepath,$content,$write_encode,$text_encode);
    }

    /**
     * 文件追加写入
     * @access public
     * @param string $filename  文件名
     * @param string $content  追加的文件内容
     * @param string $write_encode 文件写入编码
     * @return bool|null 是否成功写入,返回null表示无法访问该范围的文件
     */
    public static function append($filename,$content,$write_encode='UTF-8'){
        return self::getDriver(self::$_curindex)->append($filename,$content,$write_encode);
    }

    /**
     * 文件是否存在
     * @param string $filename  文件名
     * @return int 0表示目录不存在,<0表示是目录 >0表示是文件,可以用Storage的三个常量判断
     */
    public static function has($filename){
        return self::getDriver(self::$_curindex)->has($filename);
    }


    /**
     * 设定文件的访问和修改时间
     * @param string $filename 文件路径
     * @param int $mtime  文件最后修改时间
     * @param int $atime  文件最后访问时间
     * @return bool 是否成功|返回null表示在访问的范围之外
     */
    public static function touch($filename, $mtime = null, $atime = null){
        return self::getDriver(self::$_curindex)->touch($filename,$mtime,$atime);
    }

    /**
     * 文件删除
     * @param string $filename 文件名
     * @param bool $recursion 删除的目标是目录时,若目录下存在文件,是否进行递归删除,默认为false
     * @return bool 是否成功删除|返回null表示在访问的范围之外
     */
    public static function unlink($filename,$recursion=false){
        return self::getDriver(self::$_curindex)->unlink($filename,$recursion);
    }

    /**
     * 返回文件内容上次的修改时间
     * @param string $filepath 文件路径
     * @param int $mtime 修改时间
     * @return int|bool|null 如果是修改时间的操作返回的bool;如果是获取修改时间,则返回Unix时间戳;返回null表示在访问的范围之外
     */
    public static function mtime($filepath,$mtime=null){
        return self::getDriver(self::$_curindex)->mtime($filepath,$mtime);
    }

    /**
     * 获取文件大小
     * @param string $filename 文件路径信息
     * @return int|false|null 按照字节计算的单位;返回null表示在访问的范围之外
     */
    public static function size($filename){
        return self::getDriver(self::$_curindex)->size($filename);
    }

    /**
     * 创建文件夹
     * 如果文件夹已经存在，则修改权限
     * @param string $fullpath 文件夹路径
     * @param int $auth 文件权限，八进制表示
     * @return bool|null 返回null表示在访问的范围之外
     */
    public static function mkdir($fullpath,$auth = 0755){
        return self::getDriver(self::$_curindex)->mkdir($fullpath,$auth);
    }

    /**
     * 修改文件权限
     * @param string $filepath 文件路径
     * @param int $auth 文件权限
     * @return bool 是否成功修改了该文件|返回null表示在访问的范围之外
     */
    public static function chmod($filepath,$auth = 0755){
        return self::getDriver(self::$_curindex)->chmod($filepath,$auth);
    }

}