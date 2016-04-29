<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2016/3/4
 * Time: 22:46
 */
namespace System\Core\Dao;

class OCI extends DaoAbstract {

    /**
     * 保留字段转义字符
     * @var string
     */
    protected $_l_quote = '"';
    protected $_r_quote = '"';


    /**
     * 转义保留字字段名称
     * @param string $fieldname 字段名称
     * @return string
     */
    public function escape($fieldname){}
    /**
     * 根据配置创建DSN
     * @param array $config 数据库连接配置
     * @return string
     */
    public function buildDSN(array $config){
        $dsn  =   'oci:dbname=//'.$config['hostname'].($config['port']?':'.$config['port']:'').'/'.$config['dbname'];
        if(!empty($config['charset'])) {
            $dsn  .= ';charset='.$config['charset'];
        }
        return $dsn;
    }

    /**
     * 编译组件成适应当前数据库的SQL字符串
     * @param array $components  复杂SQL的组成部分
     * @return string
     */
    public function compile(array $components){}
}