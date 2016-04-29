<?php
/**
 * User: linzh_000
 * Date: 2016/3/15
 * Time: 16:32
 */
namespace System\Traits;
use StdClass;
use System\Core\Exception\Driver\DriverNotFoundException;
use System\Core\KbylinException;
use System\Core\Config;
use System\Utils\SEK;

/**
 * Class Crux 系统运行关键组件
 *
 * 集成单例设计模式和类配置自动加载
 *
 * 集合初始化方法和单例设计模式的类
 * 注释：
 *  ① 将所有的self替换成static表示调用该继承类的对应方法而不是父类的方法，在子类中调用self表示该类的该方法（允许的）
 *  ② 在trait类中设置const常量‘CONF_NAME’可以设置将要加载的config目录下的文件的名称
 *  ③ 更多详细内容参照Test目录下的testCrux.php文件
 *  ④ 类本身的管理配置为const常量'CONF_CONVENTION'定义的宿主
 *
 * @package System\Core
 */
trait Crux {

    /**
     * 类的静态配置
     * @var array
     */
    private static $_conventions = [
        /************************************
         'System/Sample' => [
            'DRIVER_DEFAULT_INDEX' => 0,//默认的驱动标识符，类型为int或者string
            'DRIVER_CLASS_LIST' => [],//驱动类列表
            'DRIVER_CONFIG_LIST' => [],//驱动类配置数组列表,如果不存在对应的但存在唯一的一个配置数组，则上面的driver类均使用该配置项
         ]
         ************************************/
    ];

    /**
     * 实例数组
     * @var StdClass[]
     */
    private static $classes = [];

    /**
     * 初始化本类配置
     * @param string|array|null $conf 配置文件名称或者配置数组
     *      null时将自动获取配置目录下的配置文件，文件名称依据本类的类常量CONF_NAME
     *      string类型时表示类的配置文件的名称，配置文件存放于预定义目录下，使用PHP的设置
     *      array类型时表示类的惯例配置，此时将不读取用户配置，该配置可以是缓存中的（提高执行效率）
     * @return bool|true 返回是否初始化成功（现在总是返回true）
     * @throws KbylinException
     */
    protected static function initialize($conf=null){
        $classname = static::class;//调用该方法的类的名称

        //读取类的惯例配置
        $conventionconst = "{$classname}::CONF_CONVENTION";
        if(defined($conventionconst)){
            static::$_conventions[$classname] = $classname::CONF_CONVENTION;
        }

        //加载外部配置
        if(null === $conf){
            //检查调用类中是否定义了此常量，是则获取之作为自己的配置文件名称，否则将自动根据类名称获取配置文件名
            $confconstant = "{$classname}::CONF_NAME";
            if(defined($confconstant)){
                $conf = $classname::CONF_NAME;
            }else{
                //未定义该常量时自动更具类名获取
                $pos = strrpos($classname,'\\');
                $conf = strtolower(false === $pos?$classname:substr($classname,$pos+1));//调用该方法的类的短名称
            }
            $conf = Config::readGlobal($conf);
        }elseif(is_string($conf)){
            $conf = Config::readGlobal($conf);
        }
        is_array($conf) and SEK::merge(static::$_conventions[$classname],$conf);

        //默认的追加配置
        isset(static::$_conventions[$classname]['DRIVER_DEFAULT_INDEX'])    or static::$_conventions[$classname]['DRIVER_DEFAULT_INDEX'] = 0;
        isset(static::$_conventions[$classname]['DRIVER_CLASS_LIST'])       or static::$_conventions[$classname]['DRIVER_CLASS_LIST'] = [];
        isset(static::$_conventions[$classname]['DRIVER_CONFIG_LIST'])      or static::$_conventions[$classname]['DRIVER_CONFIG_LIST'] = [];
        return true;
    }

    /**
     * 检查是否经理初始化
     * @param bool $doinit 在未初始化的情况下是否继续进行初始化
     * @param string|array|null $conf 配置文件名称或者配置数组
     * @return bool
     * @throws KbylinException
     */
    protected static function checkInitialized($doinit=false,$conf=null){
        return isset(static::$_conventions[static::class])?
            true:$doinit?static::initialize($conf):false;
    }

    /**
     * 获取本例示例
     * @param int|string $index
     * @param mixed $conf 详细说明参考
     * @return mixed
     * @throws KbylinException
     */
    public static function getDriverInstance($index=null,$conf=null){
        //检查初始化情况
        isset(static::$_conventions[static::class]) or static::initialize($conf);

        //实例不存在时候创建
        isset(self::$classes[static::class]) or self::$classes[static::class] = [];

        $thisinstances = &static::$classes[static::class];

        if(!isset($thisinstances[$index])){
            $info = self::getDriverInfo($index);
            $driverclass = $info[0];

            $thisinstances[$index] = new $driverclass($info[1]);
        }

        return $thisinstances[$index];
    }

    /**
     * 根据角标获取驱动类名称[0]和驱动器配置构成的数组[1]
     * @param int|string|null $index
     * @return array
     * @throws KbylinException
     */
    public static function getDriverInfo($index=null) {
        $thisconvention = static::getConventions();
        null === $index and $index = $thisconvention['DRIVER_DEFAULT_INDEX'];

        if(!isset($thisconvention['DRIVER_CLASS_LIST'][$index])) {
            throw new DriverNotFoundException(['Index'=>$index,'config'=>$thisconvention['DRIVER_CLASS_LIST']]);
        }

        if(isset($thisconvention['DRIVER_CONFIG_LIST'][$index])){
            $driverconfig = $thisconvention['DRIVER_CONFIG_LIST'][$index];
        }else{
            $first = reset($thisconvention['DRIVER_CONFIG_LIST']);//参阅reset返回值
            $driverconfig = false === $first?null:$first;
        }

        //获取驱动类名称和构造参数
        return [$thisconvention['DRIVER_CLASS_LIST'][$index],$driverconfig];
    }


    /**
     * 获取本类的惯例配置
     * @param bool $all
     * @return array
     */
    protected static function getConventions($all=false){
        self::checkInitialized(true);
        return $all?self::$_conventions:
            isset(static::$_conventions[static::class])?static::$_conventions[static::class]:null;
    }
}