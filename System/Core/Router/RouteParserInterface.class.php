<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/1/21
 * Time: 16:02
 */
namespace System\Core\Router;

/**
 * Interface RouteParserInterface
 * @package System\Core\Router
 */
interface RouteParserInterface {

    /**
     * 解析URI
     * @param string $uri
     * @param string $hostname
     * @return $this
     */
    public function parse($uri,$hostname);

    /**
     * 返回模块
     * @param mixed $modules
     * @return $this
     */
    public function fetchModules(&$modules);

    /**
     * 返回控制器名称
     * @param mixed $controller
     * @return $this
     */
    public function fetchController(&$controller);

    /**
     * 返回操作方法名称
     * @param mixed $action
     * @return $this
     */
    public function fetchAction(&$action);

    /**
     * 返回参数
     * @param mixed $params
     * @return $this
     */
    public function fetchParameters(&$params);


}