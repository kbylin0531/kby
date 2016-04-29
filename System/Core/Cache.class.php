<?php
/**
 * Email: linzhv@qq.com
 * Date: 2016/1/21
 * Time: 16:26
 */
namespace System\Core;
use System\Core\Cache\CacheInterface;
use System\Core\Cache\File;
use System\Core\Cache\Memcache;
use System\Core\Exception\DriverInavailableException;
use System\Traits\Crux;

/**
 * Class Cache 缓存管理类
 * @package System\Library
 */
class Cache{

    use Crux;
    const CONF_NAME = 'cache';
    const CONF_CONVENTION = [
        'DRIVER_DEFAULT_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            File::class,
            Memcache::class,
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                //选自THinkPHP，大小写保持原样
                'expire'        => 0,
                'cache_subdir'  => false,
                'path_level'    => 1,
                'prefix'        => '',
                'length'        => 0,
                'path'          => RUNTIME_PATH.'/Cache/',
                'data_compress' => false,
            ],
            [
                'host'      => 'localhost',
                'port'      => 11211,
                'expire'    => 0,
                'prefix'    => '',
                'timeout'   => 1000, // 超时时间（单位：毫秒）
                'persistent'=> true,
                'length'    => 0,
            ],
        ],
    ];

    /**
     * 读取次数
     * @var int
     */
    private static $readTimes   = 0;
    /**
     * 写入次数
     * @var int
     */
    private static $writeTimes  = 0;

    /**
     * 使用的驱动的角标
     * 如果为null，则Crux使用配置中默认的设置
     * @var int|string|null
     */
    protected static $index = null;

    /**
     * 选择缓存驱动，在实际读写之前有效
     * @param int|string $index 驱动索引
     * @return void
     * @throws KbylinException 使用了不存在的索引
     */
    public static function using($index){
        $convention = self::getConventions();
        if(!isset($convention['DRIVER_CLASS_LIST'][$index])){
            throw new KbylinException($index);
        }
        self::$index = $index;
    }

    /**
     * 获取读取缓存次数
     * @return int
     */
    public static function getReadTimes(){
        return self::$readTimes;
    }

    /**
     * 获取写入缓存的速度
     * @return int
     */
    public static function getWriteTimes(){
        return self::$writeTimes;
    }

    /**
     * 获取驱动
     * @param int|string $index
     * @return CacheInterface
     * @throws DriverInavailableException
     */
    public static function getDriver($index=null){
        static $cache = [];
        null === $index and $index = self::$index;
        if(!isset($cache[$index])){
            $cache[$index] = self::getDriverInstance($index);
            if(!call_user_func([$cache[$index],'available'])){
                throw new DriverInavailableException($index);
            }
        }
        return $cache[$index];
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $replace 值不存在时的替代
     * @return mixed
     * @throws DriverInavailableException
     */
    public static function get($name,$replace=null){
        ++ self::$readTimes;
        $result = self::getDriver(self::$index)->get($name);
        return $result === null ? $replace : $result;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param int $expire  有效时间 0为永久
     * @return bool
     * @throws DriverInavailableException
     */
    public static function set($name, $value, $expire = null){
        ++ self::$writeTimes;
        return self::getDriver(self::$index)->set($name, $value, $expire);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     * @throws DriverInavailableException 驱动器无法使用的情况下抛出异常
     */
    public static function delete($name){
        ++ self::$writeTimes;
        return self::getDriver(self::$index)->delete($name);
    }

    /**
     * 清除全部缓存
     * @access public
     * @return boolean
     * @throws DriverInavailableException
     */
    public static function clean(){
        ++ self::$writeTimes;
        return self::getDriver(self::$index)->clean();
    }

}