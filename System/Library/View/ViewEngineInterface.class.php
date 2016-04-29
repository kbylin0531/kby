<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/2/2
 * Time: 10:49
 */
namespace System\Library\View;
use System\Core\Exception\FileNotFoundException;

/**
 * Interface ViewEngineInterface 视图引擎接口
 * @package System\Core\View
 */
interface ViewEngineInterface {

    /**
     * 设置上下文环境
     * @param array $context 上下文环境，包括模块、控制器、方法和模板信息可供设置使用
     * @return $this
     */
    public function setContext(array $context);

    /**
     * 注册一个插件
     * @param $type
     * @param $name
     * @param $callback
     * @param bool $cacheable
     * @param null $cache_attr
     * @return mixed
     */
    public function registerPlugin($type, $name, $callback, $cacheable = true, $cache_attr = null);

    /**
     * 保存控制器分配的变量
     * @param string $tpl_var
     * @param null $value
     * @param bool $nocache
     * @return $this
     */
    public function assign($tpl_var,$value=null,$nocache=false);

    /**
     * 显示模板
     * @param string $template 全部模板引擎通用的
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return void
     * @throws FileNotFoundException
     */
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null);

}