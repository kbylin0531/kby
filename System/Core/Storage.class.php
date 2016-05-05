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
                'READABLE_SCOPE'    => BASE_PATH,
                'WRITABLE_SCOPE'    => RUNTIME_PATH,
                'ACCESS_FAILED_MODE'    => MODE_EXCEPTION,
            ]
        ],

    ];

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
     * @return string|false 文件不存在或者文件无法访问时返回false,否则返回文件文本内容string
     */
    public static function read($filepath, $fileEncoding='UTF-8'){
        return self::getDriver(self::$_curindex)->read($filepath,$fileEncoding);
    }

    /**
     * 文件写入
     * @param string $filepath 文件名
     * @param string $content 文件内容
     * @param string $write_encode 写入编码
     * @param string $text_encode 文本本身的编码格式
     * @return bool 文件是否写入成功
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
     * @return bool 是否成功追加
     */
    public static function append($filename,$content,$write_encode='UTF-8'){
        return self::getDriver(self::$_curindex)->append($filename,$content,$write_encode);
    }

    /**
     * 文件是否存在
     * @param string $filename  文件名
     * @return bool 文件是否存在
     */
    public static function has($filename){
        return self::getDriver(self::$_curindex)->has($filename);
    }


    /**
     * 文件删除
     * @param string $filename  文件名
     * @return bool 是否成功删除文件
     */
    public static function unlink($filename){
        return self::getDriver(self::$_curindex)->unlink($filename);
    }

    /**
     * 返回文件内容上次的修改时间
     * 可以使用stat获取信息
     * @access public
     * @param string $filename  文件名
     * @return int|false 返回Unix时间戳,失败时返回false
     */
    public static function mtime($filename){
        return self::getDriver(self::$_curindex)->mtime($filename);
    }

    /**
     * 获取文件大小
     * @param string $filename 文件路径信息
     * @return int|false 返回文件大小,失败时返回false
     */
    public static function size($filename){
        return self::getDriver(self::$_curindex)->size($filename);
    }

    /**
     * 创建文件夹
     * 如果文件夹已经存在，则修改权限
     * @param string $fullpath 文件夹路径
     * @param int $auth 文件权限，八进制表示
     * @return bool 文件夹是否创建成功
     */
    public static function makeDirectory($fullpath,$auth = 0755){
        return self::getDriver(self::$_curindex)->makeDirectory($fullpath,$auth);
    }

    /**
     * 删除文件夹
     * @param string $path 文件夹目录
     * @param bool $recursion 是否递归删除
     * @return bool 是否成功删除文件夹
     */
    public static function removeDirectory($path,$recursion=false) {
        return self::getDriver(self::$_curindex)->removeDirectory($path,$recursion);
    }

    
    /**
     * 读取文件夹内容，并返回一个数组(不包含'.'和'..')
     * array(
     *      //文件内容  => 文件内容
     *      'filename' => 'file full path',
     * );
     * @param string $path 文件夹路径
     * @param bool $recursion 是否递归读取
     * @return array|false 返回文件列表信息数组,失败时返回false
     */
    public static function readDirectory($path,$recursion=false){
        return self::getDriver(self::$_curindex)->readDirectory($path,$recursion);
    }

}