<?php
/**
 * Email: linzhv@qq.com
 * Date: 2016/1/21
 * Time: 14:45
 */
namespace System\Core;
use System\Traits\Crux;
use System\Core\Router\KbylinRouteParser;
use System\Core\Router\KbylinURICreater;
/**
 * Class Router 路由类
 * @package System\Core
 */
class Router {

    use Crux;

    const CONF_NAME = 'router';
    const CONF_CONVENTION = [
        //Router类的驱动使用不同的驱动，但使用相同的配置
        'DRIVER_CLASS_LIST' => [
            'parser'    => KbylinRouteParser::class,
            'creater'   => KbylinURICreater::class,
        ],
        'DRIVER_CONFIG_LIST' => [
            //驱动配置
            [
                //API模式，直接使用$_GET
                'API_MODE_ON'   => false,
                //API模式 对应的$_GET变量名称
                'API_MODULES_VARIABLE'   => '_m',//该模式下使用到多层模块时涉及'MM_BRIDGE'的配置
                'API_CONTROLLER_VARIABLE'   => '_c',
                'API_ACTION_VARIABLE'   => '_a',

                //普通模式
                'MASQUERADE_TAIL'   => '.html',
                //重写模式下 消除的部分，对应.htaccess文件下
                'REWRITE_HIDDEN'      => '/index.php',
                'MM_BRIDGE'     => '/',//模块与模块之间的连接桥
                'MC_BRIDGE'     => '/',
                'CA_BRIDGE'     => '/',
                'AP_BRIDGE'     => '$!',//*** 必须保证操作与控制器之间的符号将是$_SERVER['PATH_INFO']字符串中第一个出现的,为了更好地显示URL，参数一般通过POST传递
                'PP_BRIDGE'     => '/',//参数与参数之间的连接桥
                'PKV_BRIDGE'    => '/',//参数的键值对之前的连接桥

                //默认的模块，控制器和操作(无参数)
                'DEFAULT_MODULES'     => 'Home',//默认的模块只有一个
                'DEFAULT_CONTROLLER'  => // 默认的控制器通常与对应的模块匹配
                    [
                        //键为模块名，值为对应的默认控制器，不存在指定的键时使用默认的(键位0)
                        0   => 'Index',
                    ],
                'DEFAULT_ACTION'      =>
                    [
                        //键为 模块加控制器 序列 e.q.'Ma/Mb@C',不存在时使用默认的0键
                        0   => 'index',
                    ],

                //是否开启域名部署（包括子域名部署）
                'DOMAIN_DEPLOY_ON'  => false,
                //子域名部署模式下 的 完整域名
                'DOMAIN_NAME'=>'xor.com',
                //是否将子域名段和模块进行映射
                'SUBDOMAIN_AUTO_MAPPING_ON' => true,
                //子域名部署规则
                //注意参与array_flip()函数,键值互换
                'SUBDOMAIN_MAPPINIG' => [],

                //是否对URI地址进行路由
                'URI_ROUTE_ON'          => false,//总开关
                'STATIC_ROUTE_ON'       => true,
                'STATIC_ROUTE_RULES'    => [],
                'WILDCARD_ROUTE_ON'     => true,
                'WILDCARD_ROUTE_RULES'  => [],
                'REGULAR_ROUTE_ON'      => true,
                'REGULAR_ROUTE_RULES'   => [],

                //使用的协议名称
                'SERVER_PROTOCOL' => 'http',
                //使用的端口号，默认为80时会显示为隐藏
                'SERVER_PORT' => 80,
            ],
        ],
    ];

    /**
     * 解析出的模块
     * @var array
     */
    private static $access_modules = null;
    /**
     * 解析出的控制器
     * @var string
     */
    private static $access_controller = null;
    /**
     * 解析出的操作
     * @var string
     */
    private static $access_action = null;
    /**
     * 解析出的参数
     * @var array
     */
    private static $access_parameters = null;

    /**
     * 处理URI地址和Hostname对应的规则地址
     * @param string $uri 待解析的URI，如果为null则自动从运行环境中获取
     * @param string $hostname 访问的主机名称
     * @return array 返回解析结果
     */
    public static function parse($uri,$hostname){
        self::getDriverInstance('parser')->parse($uri,$hostname)
            ->fetchModules(self::$access_modules)
            ->fetchController(self::$access_controller)
            ->fetchAction(self::$access_action)
            ->fetchParameters(self::$access_parameters);
        return [
            self::$access_modules,
            self::$access_controller,
            self::$access_action,
            self::$access_parameters,
        ];
    }

    /**
     * 创建可以访问的URI字符串
     * @param $modules
     * @param $controller
     * @param $action
     * @param array $params
     * @return string
     */
    public static function create($modules=null,$controller=null,$action=null,array $params=null){
        return self::getDriverInstance('creater')->create($modules,$controller,$action,$params);
    }

    /**
     * 返回解析的控制结果
     * @return array
     */
    public static function getModules(){
        return self::$access_modules;
    }

    /**
     * 返回解析的控制器名称
     * @return string
     */
    public static function getController(){
        return self::$access_controller;
    }

    /**
     * 返回解析的操作名称
     * @return string
     */
    public static function getAction(){
        return self::$access_action;
    }

    /**
     * 返回解析的参数名称
     * @return array
     */
    public static function getParameters(){
        return self::$access_parameters;
    }

}