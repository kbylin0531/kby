<?php
/**
 * User: linzh_000
 * Date: 2016/3/15
 * Time: 16:32
 */
namespace System\Traits;
use stdClass;
use System\Core\KbylinException;
use System\Utils\SEK;

/**
 * Class Crux 系统运行关键组件
 *
 * 管理类的初始化和驱动实例设置
 *
 * 注释：
 *  ① 将所有的self替换成static表示调用该继承类的对应方法而不是父类的方法，在子类中调用self表示该类的该方法（允许的）
 *  ② 在trait类中设置const常量‘CONF_NAME’可以设置将要加载的配置目录下的文件的名称
 *  ③ 类本身的管理配置为const常量'CONF_CONVENTION'定义的宿主
 * @package System\Traits
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
        $const_convention = "{$classname}::CONF_CONVENTION";
        static::$_conventions[$classname] = defined($const_convention)?$classname::CONF_CONVENTION:[];

        //加载外部配置
        if(null === $conf){
            //通过类中的常量定义 获取其配置文件名称
            $outer_config = "{$classname}::CONF_NAME";
            defined($outer_config) and $conf = $classname::CONF_NAME;//只有在定义了该常量的情况下才获取外部配置,否则只要遵循默认的配置就可以了
        }

        if(is_string($conf)) $conf = Crux::readGlobal($conf);
        is_array($conf) and SEK::merge(static::$_conventions[$classname],$conf);

        //默认的追加配置
        isset(static::$_conventions[$classname]['DRIVER_DEFAULT_INDEX'])    or static::$_conventions[$classname]['DRIVER_DEFAULT_INDEX'] = 0;
        isset(static::$_conventions[$classname]['DRIVER_CLASS_LIST'])       or static::$_conventions[$classname]['DRIVER_CLASS_LIST'] = [];
        isset(static::$_conventions[$classname]['DRIVER_CONFIG_LIST'])      or static::$_conventions[$classname]['DRIVER_CONFIG_LIST'] = [];
        return true;
    }

    /**
     * <不存在依赖关系>
     * 读取全局配置
     * 设定在 'KL_CONFIG_PATH' 目录下的配置文件的名称
     * @param string|array $itemname 自定义配置项名称
     * @param string $conf_path 配置文件的路径
     * @return array|mixed 配置项存在的情况下返回array，否则返回参数$replacement的值
     * @throws
     */
    public static function readGlobal($itemname, $conf_path=KL_CONFIG_PATH) {
        $result = [];

        $type = gettype($itemname);
        switch ($type){
            case 'array':
                foreach($itemname as $item){
                    $temp = self::readGlobal($item);
                    null !== $temp and SEK::merge($result,$temp);
                }
            break;
            case 'string':
                $path = $conf_path."{$itemname}.php";
                if(!is_file($path)) KbylinException::throwing($path,'not found!');
                $result = include $path;
                break;
            default:
                KbylinException::throwing($itemname,'expect string/array(multiple)');
        }
        return $result;
    }

    /**
     * 检查是否经理初始化
     * @param bool $dowhilenot 在未初始化的情况下是否继续进行初始化
     * @return bool
     * @throws KbylinException
     */
    protected static function checkInitialized($dowhilenot=true){
        return isset(static::$_conventions[static::class])?true:$dowhilenot?static::initialize():false;
    }

    /**
     * 获取本例示例
     * @param int|string $index
     * @return Object
     * @throws KbylinException
     */
    public static function getDriverInstance($index=null){
        static::checkInitialized(true);

        //本类的实例列表为空时创建
        isset(self::$classes[static::class]) or self::$classes[static::class] = [];
        $thisinstances = &static::$classes[static::class];

        if(!isset($thisinstances[$index])){
            $info = self::getDriverInfo($index);
            $thisinstances[$index] = new $info[0]($info[1]);
        }

        return $thisinstances[$index];
    }

    /**
     * 根据角标获取驱动类名称[0]和驱动器配置构成的数组[1]
     * @param int|string|null $index
     * @return array
     * @throws KbylinException
     */
    protected static function getDriverInfo($index=null) {
        $thisconvention = static::getConventions();

        null === $index and $index = $thisconvention['DRIVER_DEFAULT_INDEX'];

        isset($thisconvention['DRIVER_CLASS_LIST'][$index]) or KbylinException::throwing($thisconvention['DRIVER_CLASS_LIST'],'do not have',$index);

        if(isset($thisconvention['DRIVER_CONFIG_LIST'][$index])){
            $driverconfig = $thisconvention['DRIVER_CONFIG_LIST'][$index];
        }else{
            //不存在时返回第一个配置
            //reset:mixed the value of the first array element, or false if the array is empty.
            $first = reset($thisconvention['DRIVER_CONFIG_LIST']);
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