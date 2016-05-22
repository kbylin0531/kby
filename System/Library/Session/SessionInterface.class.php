<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 22/05/16
 * Time: 21:42
 */
namespace System\Library\Session;

/**
 * Interface SessionInterface
 * @package System\Core\Session
 */
interface SessionInterface {

    /**
     * 获取指定名称的session的值
     * @param null|string $name 为null时获取全部session
     * @return mixed
     */
    public function get($name=null);

    /**
     * 设置session
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name,$value);

    /**
     * 检查是否设置了指定名称的session
     * @param string $name
     * @return bool
     */
    public function has($name);
    /**
     * 清除指定名称的session
     * @param string|array $name 如果为null将清空全部
     * @return void
     */
    public function clear($name=null);

}