<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/1/25
 * Time: 16:32
 */
namespace System\Core\Router;
use System\Core\KbylinException;
use System\Utils\Network;
use System\Utils\SEK;

/**
 * Class XorURICreater 用于URI地址创建
 * @package System\Core\Router
 */
class KbylinURICreater implements URICreaterInterface {

    private $convention = [
        //API模式，直接使用$_GET
        'API_MODE_ON'   => true,
        //API模式 对应的$_GET变量名称
        'API_MODULES_VARIABLE'   => '_m',//该模式下使用到多层模块时涉及'MM_BRIDGE'的配置
        'API_CONTROLLER_VARIABLE'   => '_c',
        'API_ACTION_VARIABLE'   => '_a',

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
        'DOMAIN_DEPLOY_ON'  => true,
        //子域名部署模式下 的 完整域名
        'DOMAIN_NAME'=>'xor.com',
        //是否将子域名段和模块进行映射
        'SUBDOMAIN_AUTO_MAPPING_ON' => true,
        //子域名部署规则
        //注意参与array_flip()函数,键值互换
        'SUBDOMAIN_MAPPINIG' => [],

        //是否对URI地址进行路由
        'URI_ROUTE_ON'          => true,//总开关
        'STATIC_ROUTE_ON'       => true,
        'STATIC_ROUTE_RULES'    => [],
        'WILDCARD_ROUTE_ON'     => true,
        'WILDCARD_ROUTE_RULES'  => [],
        'REGULAR_ROUTE_ON'      => true,
        'REGULAR_ROUTE_RULES'   => [],

        //是否开启伪后缀
        'MASQUERADE_ON'     => true,
        'MASQUERADE_TAIL'   => '.html',

        //是否开启URI重写
        'REWRITE_ON'        => true,
        //重写模式下 消除的部分，对应.htaccess文件下
        'REWRITE_HIDDEN'      => '/index.php',

    ];

    /**
     * XorURICreater constructor.
     * @param array|null $config
     */
    public function __construct(array $config=null){
        isset($config) and SEK::merge($this->convention,$config);
    }

    /**
     * 创建URL
     * @param string|array $modules 模块序列
     * @param string $contler 控制器名称
     * @param string $action 操作名称
     * @param array|null $params 参数
     * @return string 可以访问的URI
     */
    public function create($modules=null,$contler=null,$action=null,array $params=null){
        if($this->convention['API_MODE_ON']){
            $uri = Network::getBasicUrl().$this->createInAPI($modules,$contler,$action,$params);
        }else{
            //反向域名地址
            $moduleUsed = false;
            if($this->convention['DOMAIN_DEPLOY_ON']){
                $hostname = $this->createHostname($modules);//如果绑定了模块，之后的解析将无法指定模块
                $moduleUsed = true;//标注模块信息已经注入到域名中了
            }else{
                $hostname = $_SERVER['SERVER_NAME'];
            }
            $uri = Network::getBasicUrl(null,$hostname).'/'.
                $this->createInCommon($moduleUsed?null:$modules,$contler,$action,$params);
        }
        return $uri;
    }


    /**
     * 按照API模式创建URL地址
     * @param array|string $modules
     * @param string $contler
     * @param string $action
     * @param array|null $params
     * @return string
     */
    public function createInAPI($modules,$contler,$action,array $params=null){
        is_array($modules) and $modules = SEK::toModulesString($modules,$this->convention['MM_BRIDGE']);
        empty($params) and $params = [];
        return '?'.http_build_query(array_merge($params,array(
            $this->convention['API_MODULES_VARIABLE']       => $modules,
            $this->convention['API_CONTROLLER_VARIABLE']    => $contler,
            $this->convention['API_ACTION_VARIABLE']        => $action,
        )));
    }

    /**
     * 获取主机名称
     * @param string|array $modules
     * @return null|string
     * @throws KbylinException
     */
    public function createHostname($modules){
        //模块标识符
        $mid = is_array($modules)?SEK::toModulesString($modules,$this->convention['MM_BRIDGE']):$modules;
        $rmapping = array_flip($this->convention['SUBDOMAIN_MAPPINIG']);
        if(isset($rmapping[$mid])){
            $hostname = $rmapping[$mid];
        }elseif($this->convention['SUBDOMAIN_AUTO_MAPPING_ON']){
            if(is_string($modules)){
                $modules = strtolower(str_replace('/','.',$modules));
            }else{
                $modules = implode('.',$modules);
            }
            $hostname = $modules;
        }else{
            return $_SERVER['SERVER_NAME'];
        }

        return $hostname.'.'.$this->convention['DOMAIN_NAME'];
    }

    /**
     * @param null $modules
     * @param null $contler
     * @param null $action
     * @param array|null $params
     * @return string
     */
    public function createInCommon($modules=null,$contler=null,$action=null,array $params=null){
        $uri = '';
        $modules and $uri .= is_array($modules)?implode($this->convention['MM_BRIDGE'],$modules):$modules;
        $contler and $uri .= ''===$uri?$contler:$this->convention['MC_BRIDGE'].$contler;
        $action and $uri .= $this->convention['CA_BRIDGE'].$action;
        $params and $uri .= $this->convention['AP_BRIDGE'].SEK::toParametersString($params,$this->convention['PP_BRIDGE'],$this->convention['PKV_BRIDGE']);
        return $uri;
    }



}