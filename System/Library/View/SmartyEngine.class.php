<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/2/2
 * Time: 10:51
 */
namespace System\Library\View;
use System\Core\Exception\FileNotFoundException;
use System\Library\View;
use System\Utils\Network;

/**
 * Class SmartyEngine Smarty模板引擎
 * @package System\Core\View
 */
class SmartyEngine implements ViewEngineInterface {

    protected $_context = null;

    protected $convention = [
        'SMARTY_DIR'        => SYSTEM_PATH.'Vendor/smarty3/libs/',
        'TEMPLATE_CACHE_DIR'    => RUNTIME_PATH.'Template/',

        'SMARTY_CONF'       => [
            //模板变量分割符号
            'left_delimiter'    => '{',
            'right_delimiter'   => '}',
            //缓存开启和缓存时间
            'caching'        => true,
            'cache_lifetime'  => 1,
        ],

    ];

    /**
     * 模板变量
     * @var array
     */
    protected $_tVars = [];

    /**
     * @var \SmartyBC
     */
    private $smarty = null;

    public function __construct(array $config){
        defined('SMARTY_DIR') or define('SMARTY_DIR',$this->convention['SMARTY_DIR']);
        if(!isset($this->smarty)){
            require_once SMARTY_DIR.'SmartyBC.class.php';
            $this->smarty = new \SmartyBC();
            if(isset($this->convention['SMARTY_CONF'])){
//            $this->smarty->left_delimiter  = $this->convention['DELIMITER_LEFT'];
//            $this->smarty->right_delimiter = $this->convention['DELIMITER_RIGHT'];
//            $this->smarty->caching         = $this->convention['CACHING_ON'];
//            $this->smarty->cache_lifetime  = $this->convention['CACHING_LIFTTIME'];
                foreach($this->convention['SMARTY_CONF'] as $name=>$value){
                    $this->smarty->{$name} = $value;
                }
            }
        }

        $this->smarty->registerPlugin('function','U',
            function($params){
                return Network::url($params['action'],$params);
            });
    }

    /**
     * 插件注册
     * @param $type
     * @param $name
     * @param $callback
     * @param bool $cacheable
     * @param null $cache_attr
     * @return mixed
     */
    public function registerPlugin($type, $name, $callback, $cacheable = true, $cache_attr = null){
        return $this->registerPlugin($type, $name, $callback, $cacheable , $cache_attr);
    }

    /**
     * 保存控制器分配的变量
     * @param string $tpl_var
     * @param null $value
     * @param bool $nocache
     * @return \Smarty_Internal_Data
     */
    public function assign($tpl_var,$value=null,$nocache=false){
        return $this->smarty->assign($tpl_var,$value,$nocache);
    }

    /**
     * 设置上下文环境
     * @param array $context 上下文环境，包括模块、控制器、方法和模板信息可供设置使用
     * @return $this
     */
    public function setContext(array $context){
        $this->_context = $context;
        return $this;
    }

    /**
     * 显示模板
     * @param string $template 模板文件位置
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return void
     * @throws FileNotFoundException
     */
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null){
        \Kbylin::recordStatus('display_begin');

        //拟上下文转化成数组
        $context = &$this->_context;
        //判断模板文件是否存在（改为由模板引擎判断）
        if(!is_file($template)) throw new FileNotFoundException($template);

//        dump($context);
        //编译缓存目录
        $cachedir = $this->convention['TEMPLATE_CACHE_DIR']."{$context['m']}/{$context['c']}/";

        //分配变量
        $this->smarty->assign($this->_tVars);
        //设置模板缓存目录
//        $this->smarty->setTemplateDir(dirname($template));
        $this->smarty->setCompileDir("{$cachedir}compile/");
        $this->smarty->setCacheDir("{$cachedir}cache/");
        \Kbylin::recordStatus('view_display_begin');

        //显示模板文件
        $this->smarty->display($template,$cache_id,$compile_id,$parent);
        \Kbylin::recordStatus('view_display_end');
    }

}