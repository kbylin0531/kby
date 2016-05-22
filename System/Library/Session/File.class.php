<?php
/**
 * Created by PhpStorm.
 * User: Lin
 * Date: 2015/11/12
 * Time: 19:32
 */
namespace System\Library\Session;
use System\Core\KbylinException;

/**
 * Class File PHP默认的文件驱动
 * @package System\Core\Session
 */
class File implements SessionInterface{

    /**
     * 清除指定名称的session
     * @param string|array $name 如果为null将清空全部
     * @return void
     */
    public function clear($name=null){
        if(null === $name){
            $_SESSION = array();
        }elseif(is_string($name)){
            if(strpos($name,'.')){
                list($name1,$name2) =   explode('.',$name);
                unset($_SESSION[$name1][$name2]);
            }else{
                unset($_SESSION[$name]);
            }
        }elseif(is_array($name)){
            foreach($name as $val){
                $this->clear($val);
            }
        }else{
            KbylinException::throwing($name);
        }
    }
    /**
     * 检查是否设置了指定名称的session
     * @param string $name
     * @return bool
     */
    public function has($name){
        if(strpos($name,'.')){ // 支持数组
            list($name1,$name2) =   explode('.',$name);
            return isset($_SESSION[$name1][$name2]);
        }else{
            return isset($_SESSION[$name]);
        }
    }
    /**
     * 获取指定名称的session的值
     * @param null|string $name 为null时获取全部session
     * @return mixed
     */
    public function get($name=null){
        if(isset($name)){
            if(strpos($name,'.')){
                list($name1,$name2) =   explode('.',$name);
                return isset($_SESSION[$name1][$name2])?$_SESSION[$name1][$name2]:null;
            }else{
                return isset($_SESSION[$name])?$_SESSION[$name]:null;
            }
        }
        return $_SESSION;
    }


    /**
     * 设置session
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name,$value){
        if(strpos($name,'.')){
            list($name1,$name2) =   explode('.',$name,2);
            $_SESSION[$name1][$name2] = $value;
        }else{
            $_SESSION[$name] = $value;
        }
    }

}