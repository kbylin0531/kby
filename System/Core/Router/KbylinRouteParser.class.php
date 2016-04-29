<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/3
 * Time: 15:04
 */
namespace System\Core\Router;
use System\Utils\SEK;
use System\Utils\StringHelper;

/**
 * Class KbylinRouteParser Kbylin内置路由解析器
 * 系欸规则:
 *  一、普通规则 : 符合该式"Ma[{MMB}Mb]{MCB}C{CAB}A{APB}PN1[{PKVB}PV1[[{PPB}PN2{PKVB}PV2]...]]"的URI可以进行解析
 *  二、路由规则 :
 *      ① 静态路由:URI包括大小写全部匹配 触发的路由
 *      ② 规则路由:符合规则表达式 触发的路由
 *      ③ 正则路由:匹配正则表达式 触发的路由
 *
 * 注释：
 *  一、普通路由
 *      [] 表示可选
 *      {} 表示符号占位
 *      MMB 表示Module2ModuleBridge
 *      MCB 表示module2ControllerBridge
 *      ......
 *  二、规则路由
 *
 * @package System\Core\Router
 */
class KbylinRouteParser implements RouteParserInterface{


    private $convention = [
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
        //*** 必须保证操作与控制器之间的符号将是$_SERVER['PATH_INFO']字符串中第一个出现的,为了更好地显示URL，参数一般通过POST传递
        //特别注意的是若使用了问号，则后面的字符串将被认为是请求参数
        'AP_BRIDGE'     => '!',
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

        //使用的协议名称
        'SERVER_PROTOCOL' => 'http',
        //使用的端口号，默认为80时会显示为隐藏
        'SERVER_PORT' => 80,
    ];

    /**
     * 返回解析结果
     * @var array
     */
    protected $result = [
        'm' => null,
        'c' => null,
        'a' => null,
        'p' => null,
    ];

    protected $moduleBound = false;

    /**
     * XorRouteParser constructor.
     * @param array|null $config
     */
    public function __construct(array $config=null){
        isset($config) and SEK::merge($this->convention,$config);
    }

    /**
     * 解析URI
     * @param string $uri 请求的URI
     * @param string $hostname
     * @return $this
     */
    public function parse($uri,$hostname){
        //API模式下
        if($this->convention['API_MODE_ON']){
            $this->parseInAPI();
        }else{
            //解析域名部署
            if($this->convention['DOMAIN_DEPLOY_ON']){
                $this->parseHostname($hostname);//如果绑定了模块，之后的解析将无法指定模块
            }
            //检查、寻找和解析URI路由
            if($this->convention['URI_ROUTE_ON']){
                //TODO:路由功能待测试
                $rule = $this->fetchURIRoute($uri);
                if(isset($rule)){
                    $this->parseURIRoute($rule);
                    return $this;
                }
            }
            //普通模式下解析URI地址
            $this->parseInCommon($uri);
        }
//        UDK::dump($this->result);
        //寻找默认
        $this->checkDefault();
//        UDK::dumpout($this->result);

        return $this;
    }


    /**
     * 按照API模式进行解析(都组最快)
     * 保持原样
     * @return void
     */
    public function parseInAPI(){
        \Kbylin::recordStatus('fetchurl_in_topspeed_begin');
        $vars = [
            $this->convention['API_MODULES_VARIABLE'],
            $this->convention['API_CONTROLLER_VARIABLE'],
            $this->convention['API_ACTION_VARIABLE'],
        ];
        //获取模块名称
        isset($_GET[$vars[0]]) and $this->result['m'] = $_GET[$vars[0]];
        //获取控制器名称
        isset($_GET[$vars[1]]) and $this->result['c'] = $_GET[$vars[1]];
        //获取操作名称，类方法不区分大小写
        isset($_GET[$vars[2]]) and $this->result['a'] = $_GET[$vars[2]];
        //参数为剩余的变量
        unset($_GET[$vars[0]],$_GET[$vars[1]],$_GET[$vars[2]]);
        $this->result['p'] = $_GET;

        \Kbylin::recordStatus('fetchurl_in_topspeed_end');
    }

    /**
     * 按照普通模式进行URI解析
     * @param string $uri 待解析的URI
     * @return void
     */
    public function parseInCommon($uri){
        \Kbylin::recordStatus('parseurl_in_common_begin');
        $bridges = [
            'mm'  => $this->convention['MM_BRIDGE'],
            'mc'  => $this->convention['MC_BRIDGE'],
            'ca'  => $this->convention['CA_BRIDGE'],
            'ap'  => $this->convention['AP_BRIDGE'],
            'pp'  => $this->convention['PP_BRIDGE'],
            'pkv'  => $this->convention['PKV_BRIDGE'],
        ];
        $this->stripMasqueradeTail($uri);
        \Kbylin::recordStatus('parseurl_in_pathinfo_getpathinfo_done');
        //-- 解析PATHINFO --//
        //截取参数段param与定位段local
        $papos          = strpos($uri,$bridges['ap']);
        $mcapart = null;
        $pparts = '';
        if(false === $papos){
            $mcapart  = trim($uri,'/');//不存在参数则认定PATH_INFO全部是MCA的部分，否则得到结果substr($uri,0,0)即空字符串
        }else{
            $mcapart  = trim(substr($uri,0,$papos),'/');
            $pparts   = substr($uri,$papos + strlen($bridges['ap']));
        }
//        UDK::dump($uri,$bridges['ap'],$mcapart,$pparts);

        //-- 解析MCA部分 --//
        //逆向检查CA是否存在衔接
        $mcaparsed = $this->parseMCA($mcapart,$bridges);
        $this->result = array_merge($this->result,$mcaparsed);
        \Kbylin::recordStatus('parseurl_in_pathinfo_getmac_done');

        //-- 解析参数部分 --//
        $this->result['p'] = SEK::toParametersArray($pparts,$bridges['pp'],$bridges['pkv']);
        //URL中解析结果合并到$_GET中，$_GET的其他参数不能和之前的一样，否则会被解析结果覆盖
        SEK::merge($_GET,$this->result['p']);

        //注意到$_GET和$_REQUEST并不同步，当动态添加元素到$_GET中后，$_REQUEST中不会自动添加
        \Kbylin::recordStatus('parseurl_in_common_end');
    }

    /**
     * 解析主机名
     * 如果找到了对应的主机名称，则绑定到对应的模块
     * @param string $hostname 访问的主机名
     * @return bool 返回是否绑定了模块
     */
    public function parseHostname($hostname){
        $subdomain = strstr($hostname,$this->convention['DOMAIN_NAME'],true);
        if(false === $subdomain) return ;
        $subdomain = rtrim($subdomain,'.');
        if(isset($this->convention['SUBDOMAIN_MAPPINIG'][$subdomain])){
            $this->result['m'] = $this->convention['SUBDOMAIN_MAPPINIG'][$subdomain];
        }elseif($this->convention['SUBDOMAIN_AUTO_MAPPING_ON']){
            if(false !== strpos($subdomain,'.')){
                $this->result['m'] = array_map(function ($val) {
                    return StringHelper::toJavaStyle($val);
                }, explode('.',$subdomain));
            }else{
                $this->result['m'] = ucfirst($subdomain);
            }
        }else{
            return ;
        }
        $this->moduleBound = true;//return 的情况将不能视为绑定
    }

    /**
     * @param $uri
     * @return mixed|null 返回匹配的路由规则，无匹配时返回null
     */
    public function fetchURIRoute($uri){
        //静态路由
        if($this->convention['STATIC_ROUTE_ON'] and $this->convention['STATIC_ROUTE_RULES']){
            if(isset($this->convention['STATIC_ROUTE_RULES'][$uri])){
                return $this->convention['STATIC_ROUTE_RULES'][$uri];
            }
        }
        //规则路由
        if($this->convention['WILDCARD_ROUTE_ON'] and $this->convention['WILDCARD_ROUTE_RULES']){
            foreach($this->convention['WILDCARD_ROUTE_RULES'] as $pattern => $rule){
                $pattern = preg_replace('/\[.+?\]/','([^/\[\]]+)',$pattern);//非贪婪匹配
                $rst = $this->_matchRegular($pattern,$rule, trim($uri,' /'));
                if(null !== $rst) return $rst;
            }
        }
        //正则路由
        if($this->convention['REGULAR_ROUTE_ON'] and $this->convention['REGULAR_ROUTE_RULES']){
            foreach($this->convention['REGULAR_ROUTE_RULES'] as $pattern => $rule){
                $rst = $this->_matchRegular($pattern,$rule, trim($uri,' /'));
                if(null !== $rst) return $rst;
            }
        }
        return null;
    }
    /**
     * 使用正则表达式匹配uri
     * @param string $pattern 路由规则
     * @param array|string|callable $rule 路由导向结果
     * @param string $uri 传递进来的URL字符串
     * @return array|string|null
     */
    private function _matchRegular($pattern, $rule, $uri){
        //TODO:设计路由规则并重写
        // Does the RegEx match? use '#' to ignore '/'
        if (preg_match('#^'.$pattern.'$#', $uri, $matches)) {
            if(is_array($rule)){
                if(isset($rule[3])){
                    $index = 1;//忽略第一个匹配（全匹配）
                    foreach($rule[3] as $pname=>&$pval){
                        if(isset($matches[$index])){
                            $pval = $matches[$index];
                        }
                        ++$index;
                    }
                    $_GET = array_merge($rule[3],$_GET);// 优先使用$_GET覆盖
                }else{
                    //未设置参数项，不作动作
                }
            }elseif(is_string($rule)){
                $rule = preg_replace('#^'.$pattern.'$#', $rule, $uri);//参数一代表的正则表达式从参数三的字符串中寻找匹配并替换到参数二代表的字符串中
            }elseif(is_callable($rule)){
                // Remove the original string from the matches array.
                $fulltext = array_shift($matches);
                // Execute the callback using the values in matches as its parameters.
                $rule = call_user_func_array($rule, [$matches,$fulltext]);//参数二是完整的匹配
                if(!is_string($rule) and !is_array($rule)){
                    //要求结果必须返回string或者数组
                    return null;
                }
            }
            return $rule;
        }
        return null;
    }

    /**
     * 解析访问的URI地址
     * 如果匹配到了指定的路由，解析结束
     * @param mixed $rule 解析到的路由
     * @return void
     */
    public function parseURIRoute($rule){
        //TODO:测试路由解析
    }


    /**
     * 检查模块默认设置
     */
    public function checkDefault(){
        if(!isset($this->result['m'])){
            $this->result['m'] = $this->convention['DEFAULT_MODULES'];
        }
        if(!isset($this->result['c'])){
            $defaults = $this->convention['DEFAULT_CONTROLLER'];
            $this->result['c'] = isset($defaults[$this->result['m']])?
                $defaults[$this->result['m']]:$defaults[0];
        }
        if(!isset($this->result['a'])){
            $defaults = $this->convention['DEFAULT_ACTION'];
            $mcnm = $this->result['m'].'@'.$this->result['c'];
            $this->result['a'] = isset($defaults[$mcnm])?$defaults[$mcnm]:$defaults[0];
        }
    }

    /**
     * 解析"模块、控制器、操作"
     * @param $mcapart
     * @param $bridges
     * @return array
     */
    private function parseMCA($mcapart,$bridges){
        $parsed = ['m'=>null,'c'=>null,'a'=>null];
        $capos = strrpos($mcapart,$bridges['ca']);
//        SEK::dump($mcapart,$capos,self::$_convention['CA_BRIDGE']);
        if(false === $capos){
            //找不到控制器与操作之间分隔符（一定不存在控制器）
            //先判断位置部分是否为空字符串来决定是否有操作名称
            if(strlen($mcapart)){
                //位置字段全部是字符串的部分
                $parsed['a'] = $mcapart;
            }else{
                //没有操作部分，MCA全部使用默认的
            }
        }else{
            //apos+CA_BRIDGE 后面的部分全部算作action
            $parsed['a'] = substr($mcapart,$capos+strlen($bridges['ca']));

            //CA存在衔接符 则说明一定存在控制器
            $mcalen = strlen($mcapart);
            $mcpart = substr($mcapart,0,$capos-$mcalen);//去除了action的部分

//            SEK::dump($mcpart);

            if(strlen($mcapart)){
                $mcpos = strrpos($mcpart,$bridges['mc']);
//                SEK::dump($mcpart,$mcpos);
                if(false === $mcpos){
                    //不存在模块
                    if(strlen($mcpart)){
                        //全部是控制器的部分
                        $parsed['c'] = $mcpart;
                    }else{
                        //没有控制器部分，则使用默认的
                    }
                }else{
                    //截取控制器的部分
                    $parsed['c']   = substr($mcpart,$mcpos+strlen($bridges['mc']));

                    //既然存在MC衔接符 说明一定存在模块
                    $mpart = substr($mcpart,0,$mcpos-strlen($mcpart));//以下的全是模块部分的字符串
                    if(strlen($mpart)){
                        if(false === strpos($mpart,$bridges['mm'])){
                            $parsed['m'] = $mpart;
                        }else{
                            $parsed['m'] = explode($bridges['mm'],$mpart);
                        }
                    }else{
                        //一般存在衔接符的情况下不为空,但也考虑下特殊情况
                    }
                }
            }else{
                //一般存在衔接符的情况下不为空,但也考虑下特殊情况
            }
        }
        return $parsed;
    }
    /**
     * 删除伪装的url后缀
     * @param string|array $uri 需要去除尾巴的字符串或者字符串数组（当数组中存在其他元素时忽略）
     * @return void
     */
    protected function stripMasqueradeTail(&$uri){
        $uri = trim($uri);
        $position = stripos($uri,$this->convention['MASQUERADE_TAIL']);
        //$position === false 表示 不存在伪装的后缀或者相关带嫌疑的url部分
//        UDK::dumpout($position,$uri,$this->convention['MASQUERADE_TAIL'],substr($uri,0,$position),
//            strlen($uri),$position,strlen($this->convention['MASQUERADE_TAIL'])
//            );

        if(false !== $position and strlen($uri) === ($position + strlen($this->convention['MASQUERADE_TAIL'])) ){
            //伪装的后缀存在且只出现在最后的位置时
            $uri = substr($uri,0,$position);
        }
    }

    /**
     * 返回模块
     * @param mixed $modules
     * @return $this
     */
    public function fetchModules(&$modules){
        $modules = isset($this->result['m'])?$this->result['m']:null;
        return $this;
    }

    /**
     * 返回控制器名称
     * @param mixed $controller
     * @return $this
     */
    public function fetchController(&$controller){
        $controller =isset($this->result['c'])?$this->result['c']:null;
        return $this;
    }

    /**
     * 返回操作方法名称
     * @param mixed $action
     * @return $this
     */
    public function fetchAction(&$action){
        $action = isset($this->result['a'])?$this->result['a']:null;
        return $this;
    }

    /**
     * 返回参数
     * @param mixed $params
     * @return $this
     */
    public function fetchParameters(&$params){
        $params = isset($this->result['p'])?$this->result['p']:null;
        return $this;
    }


}