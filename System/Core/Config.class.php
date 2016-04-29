<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/1/21
 * Time: 16:41
 */
namespace System\Core;
use System\Core\Config\ConfigInterface;
use System\Core\Config\File;
use System\Core\Exception\DriverInavailableException;
use System\Core\Exception\ParameterInvalidException;
use System\Traits\Crux;
use System\Utils\SEK;

/**
 * Class Configure 设定管理器
 * @package System\Core
 */
class Config {

    use Crux;

    const CONF_NAME = 'config';
    const CONF_CONVENTION = [
        'DRIVER_DEFAULT_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            File::class,
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                'CUSTOM_CONF_PATH'  => RUNTIME_PATH.'Config/', // 用户自定义配置目录
            ]
        ],
        'CONFIG_CACHE_LIST'     => [],
        'CONFIG_CACHE_EXPIRE'   => 0,//0表示永不过期
    ];

    const CACHE_ID = '__CONFIG__';

    /**
     * 配置缓存
     * @var array
     */
    protected static $confcache = [];


    /**
     * 获取驱动
     * @param int|string $index
     * @return ConfigInterface
     * @throws DriverInavailableException
     */
    public static function getDriver($index=null){
        static $cache = [];
        if(!isset($cache[$index])){
            $cache[$index] = self::getDriverInstance($index);
            if(!call_user_func([$cache[$index],'available'])){
                throw new DriverInavailableException($index);
            }
        }
        return $cache[$index];
    }

    /**
     * <不存在依赖关系>
     * 读取全局配置
     * 设定在 'CONFIG_PATH' 目录下的配置文件的名称
     * @param string|array $itemname 自定义配置项名称
     * @return array|mixed 配置项存在的情况下返回array，否则返回参数$replacement的值
     * @throws ParameterInvalidException
     */
    public static function readGlobal($itemname) {
        $type = gettype($itemname);
        if ('array' === $type) {
            $result = [];
            foreach($itemname as $item){
                $temp = self::readGlobal($item);
                null !== $temp and SEK::merge($result,$temp);
            }
        } elseif('string' === $type) {
            $path = CONFIG_PATH."{$itemname}.php";
            if(!is_file($path)) return null;
            $result = include $path;
        } else {
            throw new ParameterInvalidException($itemname);
        }
        return $result;
    }

    /**
     * 读取所有全局配置
     * @param string|array $list 配置列表,可以是数组，也可以是都好分隔的字符串
     * @return array 返回全部配置，配置名称为键
     * @throws ParameterInvalidException
     */
    public static function readAllGlobal($list=null){
        if(null === $list){
            self::checkInitialized(true);
            $config = self::getConventions();
            $list = $config['CONFIG_CACHE_LIST'];
        }
        if(is_string($list)) $list = explode(',',$list);
        //无法读取驱动内部的缓存或者缓存不存在  => 重新读取配置并生成缓存
        foreach($list as $item){
            self::$confcache[$item] = self::readGlobal($item);
        }
        return self::$confcache;
    }

    /**
     * 创建配置系统全局缓存
     * @param array $cachedata 缓存的数据
     * @param int $expire 缓存时间
     * @return bool 创建缓存是否成功
     * @throws KbylinException
     */
    public static function setGlobalCache(array $cachedata=null, $expire=null){
        if(!isset($cachedata,$expire)){
            self::checkInitialized(true);
            $config = self::getConventions();
            if(!isset($config['CONFIG_CACHE_LIST'],$config['CONFIG_CACHE_EXPIRE'])){
                //检验获取的配置项是否合理
                throw new KbylinException('配置项"CONFIG_CACHE_LIST"或"CONFIG_CACHE_EXPIRE"缺失！');
            }
            null === $cachedata and $cachedata = self::readAllGlobal($config['CONFIG_CACHE_LIST']);
            null === $expire  and $expire = $config['CONFIG_CACHE_EXPIRE'];
        }
        return Cache::set(self::CACHE_ID,$cachedata,$expire);
    }

    /**
     * 加载配置缓存
     * @return array|null
     */
    public static function getGlobalCache(){
        return Cache::get(self::CACHE_ID);
    }

    /**
     * 获取配置信息
     * 示例：
     *  database.DB_CONNECT.0.type
     * 除了第一段外要注意大小写
     * @param string|null|array $items 配置项
     * @param mixed|null $replacement 当指定的配置项不存在时,仅仅在获取第二段开始的部分时有效
     * @return mixed 返回配置信息数组
     * @throws KbylinException
     */
    public static function getGlobal($items=null,$replacement=null){
        static $globals = null;
        if(null === $globals){
            $globals = self::getGlobalCache();
            if(null === $globals){
                $globals = self::readAllGlobal();
                self::setGlobalCache($globals);
            }
        }
        if(null === $globals) throw new KbylinException('获取全局配置失败');

        $configes = null;//配置分段，如果未分段则保持null的值
        //检查参数并设置分段
        if(null === $items){
            //默认参数时返回全部
            return $globals;
        }elseif(is_string($items)){
            $configes = false === strpos($items,'.')?[$items]:explode('.',$items);
        }elseif(is_array($items)){
            $configes = $items;
        }

        //获取第一段的配置
        $rtn = $globals[array_shift($configes)];

        //如果为true表示是经过分段的
        if($configes){
            foreach($configes as $val){
                if(isset($rtn[$val])){
                    $rtn = $rtn[$val];
                }else{
                    return $replacement;
                }
            }
        }
        return $rtn;
    }

    /**
     * 写入自定义配置项
     * @param string $itemname 自定义配置项名称
     * @param array $config 配置数组
     * @param int $expire 以秒计算的缓存时间
     * @return bool 写入成功与否
     */
    public static function writeCustom($itemname,array $config,$expire=null){
        return self::getDriver()->write($itemname,$config,$expire);
    }

    /**
     * 读取自定义配置
     * @param string $itemname 自定义配置项名称
     * @param mixed|null $replacement 当指定的配置项不存在的时候的替代值
     * @return array|mixed 配置项存在的情况下返回array，否则返回参数$replacement的值
     */
    public static function readCustom($itemname,$replacement=null){
        $result = self::getDriver()->read($itemname);
        return null === $result?$replacement:$result;
    }


    /**
     * 设置临时配置项
     * 下次请求时临时的配置将被清空
     * <code>
     *  UDK::dump(Configer::get());
     *  Configer::set('custom.NAME.VALUE',true);
     *  UDK::dump(Configer::get());
     * </code>
     * @param string $items 配置项名称，同get方法，可以是分段的设置
     * @param mixed $value 配置项的值
     * @return bool
     * @throws KbylinException 要设置的第一项不存在时抛出异常
     */
    public static function set($items,$value){
        //检查配置缓存
        self::checkInitialized(true);

        $configes = null;//配置分段，如果未分段则保持null的值
        if(false !== strpos($items,'.')){
            $configes = explode('.',$items);
            $items = array_shift($configes);
        }
        if(!isset(self::$confcache[$items])){//不存在该配置
            if(!is_array(self::readGlobal($items))){
                return false;//不存在该配置，设置失败
            }
        }

        $confvars = &self::$confcache[$items];
        if($configes){
            foreach($configes as $item){
                if(!isset($confvars[$item])){
                    $confvars[$item] = [];
                }
                $confvars = &$confvars[$item];
            }
        }
        $confvars = $value;
        return true;
    }

}