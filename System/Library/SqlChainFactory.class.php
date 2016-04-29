<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/14
 * Time: 20:56
 */
namespace System\Library;

/**
 * Class SqlChainFactory 链式SQL工厂
 * @package System\Library
 */
class SqlChainFactory {


    private $_option = [
        'distinct'  => false,
        'top'       => null,
        'fields'    => ' * ',//查询的表域情况
        'table'     => null,//
        'join'      => null,//join部分，需要带上join关键字
        'where'     => null,//where部分
        'group'     => null,//分组 需要带上group by
        'having'    => null,//having子句，依赖$group存在，需要带上having部分
        'order'     => null,//排序，不需要带上order by
        'limit'     => null,
        'offset'    => null,
    ];

    /**
     * 设置要操作的表
     * @param string $tablename 设置当前操作的数据表的名称
     * @return $this
     */
    public function table($tablename){
        $this->_option['table'] = $tablename;
        return $this;
    }

    /**
     * 设置要操作的表
     * @param string $having
     * @return $this
     */
    public function having($having){
        $this->_option['having'] = $having;
        return $this;
    }

    /**
     * 设置查询或修改的字段
     * @param string|array $fields
     * @return $this
     */
    public function fields($fields){
        $this->_option['fields'] = $fields;
        return $this;
    }

    /**
     * 设置where
     * @param string|array $where
     * @return $this
     */
    public function where($where){
        $this->_option['where'] = $where;
        return $this;
    }

    /**
     * @param string|array $join
     * @return $this
     */
    public function join($join){
        $this->_option['join'] = $join;
        return $this;
    }

    /**
     * 设置group by
     * @param string|array $group
     * @return $this
     */
    public function group($group){
        $this->_option['group'] = $group;
        return $this;
    }

    /**
     * 设置order by
     * @param string|array $order
     * @return $this
     */
    public function order($order){
        $this->_option['order'] = $order;
        return $this;
    }

    /**
     * 设置top部分（部分数据库有效）
     * @param int $num
     * @return $this
     */
    public function top($num){
        $this->_option['top'] = intval($num);
        return $this;
    }

    /**
     * 各个数据库中表现一致
     * @param bool $dist 是否进行distinct
     * @return $this
     */
    public function distinct($dist=true){
        $this->_option['distinct'] = $dist;
        return $this;
    }

    /**
     * 参阅mysql中的'limit X,Y'
     * 各个数据库中的实现不一致
     * @param int $limit 返回的数量限制
     * @param null|int $offset 偏移，null时表示不设置
     * @return $this
     */
    public function limit($limit,$offset=null){
        $this->_option['offset'] = $offset;
        $this->_option['limit'] = $limit;
        return $this;
    }

    /**
     * 编译组件成适应当前数据库的SQL字符串
     * @param int $actiontype 操作类型
     * @return string 不完整的组件传入时返回null
     */
    public function compile($actiontype){
        if(!isset($this->_option['table'])) return null;
        $sql = $this->driver->compile($this->_option,$actiontype);
        $this->reset();
        return $sql;
    }

    /**
     * 重置结果集合
     * @return void
     */
    public function reset(){
        $this->_option = [
            'distinct'  => false,
            'top'       => null,
            'fields'    => ' * ',//查询的表域情况
            'table'     => null,//
            'join'      => null,//join部分，需要带上join关键字
            'where'     => null,//where部分
            'group'     => null,//分组 需要带上group by
            'having'    => null,//having子句，依赖$group存在，需要带上having部分
            'order'     => null,//排序，不需要带上order by
            'limit'     => null,
            'offset'    => null,
        ];
    }

}