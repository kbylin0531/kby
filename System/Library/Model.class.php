<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/2/2
 * Time: 9:40
 */
namespace System\Library;
use System\Core\Dao;
use System\Traits\Crux;

/**
 * Class Model 模型类
 *
 * 处理数据，包括关系型数据库、缓存、高速内存数据库的处理
 *
 * @package System\Library
 */
class Model {

    use Crux;

    const CONF_NAME = 'model';

    /**
     * 使用private将之私有以
     * @var Dao[]
     */
    private $dao = [];

    /**
     * 获取数据访问接口对象
     * @param null|int|string $index
     * @param $config
     * @return \System\Core\Dao
     */
    protected function getDao($index=null,array $config=null){
        if(!isset($this->dao[$index])){
            $this->dao[$index] = Dao::getInstance($index,$config);
        }
        return $this->dao[$index];
    }

    /**
     * 获取查询的错误
     * @param null|int|string $index
     * @return string
     */
    protected function getError($index=null){
        return $this->getDao($index)->getError();
    }

}