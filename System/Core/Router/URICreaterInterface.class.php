<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/1/22
 * Time: 10:44
 */
namespace System\Core\Router;

/**
 * Interface URICreaterInterface URL地址创建者
 * 根据URL路由规则创建相应的URL地址
 * @package System\Core\Router
 */
interface URICreaterInterface {

    /**
     * 创建URL
     * @param string|array $modules 模块序列
     * @param string $contler 控制器名称
     * @param string $action 操作名称
     * @param array|null $params 参数
     * @return string 可以访问的URI
     */
    public function create($modules,$contler,$action,array $params=null);

}