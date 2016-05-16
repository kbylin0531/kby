<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/17
 * Time: 10:38
 */
namespace System\Core\Dao;
use PDO;
use System\Core\KbylinException;

/**
 * Class DaoAbstract Dao
 *
 *
 * 实现的差异：
 *  ① MySQL的group by在字段未加入聚合函数时会取多条数据的第一条，而SQL Server会提示错误并终止执行
 *  ② mysql中是 ``, sqlserver中是 [], oracle中是 ""
 *
 * @package System\Core\Dao
 */
abstract class DaoAbstract extends PDO {

    /**
     * PDO驱动器名称
     * @var string
     */
    protected $driverName = null;

    /**
     * 禁止访问的PDO函数的名称
     * @var array
     */
    protected $forbidMethods = [
        'forbid','getColumnMeta'
    ];


    /**
     * 创建驱动类对象
     * DatabaseDriver constructor.
     * @param array $config
     * @throws KbylinException 未设置
     */
    public function __construct(array $config){
        try {
            $dsn = is_string($config['dsn'])?$config['dsn']:$this->buildDSN($config);
            parent::__construct($dsn,$config['username'],$config['password'],$config['options']);
        } catch(\PDOException $e){
            throw new KbylinException('连接失败!错误:'.$e->getMessage());
        }
    }

    abstract public function escape($field);

    /**
     * 调用不存在的方法时
     * 需要注意的是，访问了禁止访问的方法时将返回false
     * @param string $name 方法名称
     * @param array $args 方法参数
     * @return mixed
     */
    public function __call($name,$args){
        if(in_array($name,$this->forbidMethods,true))  return false;
        return call_user_func_array([$this,$name],$args);
    }



    /**
     * 根据配置创建DSN
     * @param array $config 数据库连接配置
     * @return string
     */
    abstract public function buildDSN(array $config);

    /**
     * 编译组件成适应当前数据库的SQL字符串
     * @param array $components  复杂SQL的组成部分
     * @param int $actiontype 操作类型
     * @return string
     */
    abstract public function compile(array $components,$actiontype);

}