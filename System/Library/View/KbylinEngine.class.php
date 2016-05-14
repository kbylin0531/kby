<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/14/16
 * Time: 9:50 AM
 */

namespace System\Library\View;

/**
 * Class KbylinEngine 自定义模板引擎
 * @package System\Library\View
 */
class KbylinEngine implements ViewEngineInterface{


    public function setContext(array $context)
    {
        // TODO: Implement setContext() method.
    }

    public function registerPlugin($type, $name, $callback, $cacheable = true, $cache_attr = null){}

    public function assign($tpl_var, $value = null, $nocache = false){

    }

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        // TODO: Implement display() method.

        ob_start();
        ob_implicit_flush(0);

        // 渲染输出
//        $method = $renderContent ? 'display' : 'fetch';
//        $this->engine->$method($template, $vars, $config);

        // 获取并清空缓存
        $content = ob_get_clean();
        // 允许用户自定义模板的字符串替换
        echo $content;
    }


}