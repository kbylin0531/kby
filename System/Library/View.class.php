<?php
/**
 * User: linzh_000
 * Date: 2016/3/17
 * Time: 11:24
 */
namespace System\Library;
use System\Core\Exception\FileNotFoundException;
use System\Core\KbylinException;
use System\Library\View\SmartyEngine;
use System\Traits\Crux;

class View {
    use Crux;
    const CONF_NAME = 'view';
    const CONF_CONVENTION = [
        'DRIVER_DEFAULT_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            SmartyEngine::class,
            KbylinException::class,
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                'SMARTY_DIR'        => SYSTEM_PATH.'Vendor/smarty3/libs/',
                'TEMPLATE_CACHE_DIR'    => RUNTIME_PATH.'View/',

                'SMARTY_CONF'       => [ // 对应着smarty的配置项
                    //模板变量分割符号
                    'left_delimiter'    => '{',
                    'right_delimiter'   => '}',
                    //缓存开启和缓存时间
                    'caching'        => true,
                    'cache_lifetime'  => 1,
                ],
            ]
        ],

        //模板文件后缀名
        'TEMPLATE_SUFFIX'   => 'html',
        //模板文件提示错误信息模板
        'TEMPLATE_EMPTY_PATH'   => 'notpl',
    ];

    /**
     * 调用本类display的方法的上下文环境
     * @var array
     */
    protected static $_context = null;

    /**
     * 驱动实例
     * @var SmartyEngine
     */
    private static $_instance = null;

    /**
     * 保存控制器分配的变量
     * @param string $tpl_var
     * @param null $value
     * @param bool $nocache
     * @return void
     */
    public static function assign($tpl_var,$value=null,$nocache=false){
        self::getDriverInstance()->assign($tpl_var,$value,$nocache);
    }

    /**
     * 获取模板引擎实例
     * @return SmartyEngine
     */
    public static function getDriver(){
        return self::getDriverInstance();
    }

    /**
     * 显示模板
     * @param array $context 模板调用上下文环境，包括模块、控制器、方法和模板主题
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return void
     * @throws FileNotFoundException 模板文件不存在时抛出
     */
    public static function display(array $context, $cache_id = null, $compile_id = null,$parent = null){
        self::checkInitialized(true);
        $template = self::fetchTemplatePath($context);
        self::getDriverInstance()->setContext($context)->display($template,$cache_id,$compile_id,$parent);
    }

    /**
     * 解析资源文件地址
     * 模板文件资源位置格式：
     *      ModuleA/ModuleB@ControllerName/ActionName:themeName
     *
     * @param array|null $context 模板调用上下文环境，包括模块、控制器、方法和模板主题
     * @return array 类型由参数三决定
     */
    public static function fetchTemplatePath($context){
        $thisconvention = self::getConventions();

        $path = APP_PATH."{$context['m']}/View/{$context['c']}/";
        isset($context['t']) and $path = "{$path}{$context['t']}/";
//        dumpout($thisconvention,$path,$context);
        $path = "{$path}{$context['a']}.{$thisconvention['TEMPLATE_SUFFIX']}";
        return $path;
    }
}
