<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/2/2
 * Time: 9:40
 */
namespace System\Library;
use System\Core\Dao;
use System\Core\KbylinException;
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
    const CONF_FIELDS = [];

    const TABLE_NAME = '';//用于指定本模型对应的表,只允许字符串类型
    const TABLE_FIELDS = [];//用于指定本模型对应的字段列表,键为字段名称,值为字段默认值
    const TABLE_ORDER = ''; // 用于指定查询数据的默认排序如: [order] DESC (数字越大越靠前)

    /**
     * 操作类型
     */
    const ACTION_SELECT = 0;//查询操作,将使用到$_fields和$_where字段
    const ACTION_CREATE = 1;//添加操作,将使用到$_fields字段
    const ACTION_UPDATE = 2;//更新操作,将使用到$_fields和$_where字段
    const ACTION_DELETE = 3;//删除操作,将使用到$_where字段


    /**
     * 连接符号
     */
    CONST CONNECT_AND = ' AND ';
    CONST CONNECT_OR = ' OR ';
    CONST CONNECT_COMMA = ' , ';

    /**
     * 运算符
     */
    CONST OPERATOR_EQUAL = ' = ';
    CONST OPERATOR_NOTEQUAL = ' != ';
    CONST OPERATOR_LIKE = ' LIKE ';
    CONST OPERATOR_NOTLIKE = ' NOT LIKE ';
    CONST OPERATOR_IN = ' IN ';
    CONST OPERATOR_NOTIN = ' NOT IN ';

    /**
     * 当前的查询选项
     * 具体参照reset方法的内部变量
     * @var array
     */
    private $_options = [];
    /**
     * 输入参数,数组类型
     * 按照where,fields设置的进行分类
     * @var array
     */
    private $_inputs = [];

    /**
     * 默认的dao的角标
     * @var int|string
     */
    private $_cur_dao_index = null;

    /**
     * 数据访问对象
     * @var Dao
     */
    private $dao = null;

    /**
     * Model constructor.
     * 单参数为非null时就指定了该表的数据库和字段,来对制定的表进行操作
     * @param string $tablename 表的实际名称,不指定时候将使用类常量中定义的值
     * @param string $fields 字段数组,不指定时候将使用类常量中定义的值
     * @param string $order 用于指定默认排序
     * @throws KbylinException
     */
    public function __construct($tablename=null,$fields=null,$order=null){
        $clsnm = static::class;
        null === $tablename and $tablename = defined("$clsnm::TABLE_NAME")?$clsnm::TABLE_NAME:KbylinException::throwing('The table of model should not be empty on constructor!');
        null === $fields and $fields = defined("$clsnm::TABLE_FIELDS")?$clsnm::TABLE_FIELDS:null;
        null === $order and $order = defined("$clsnm::TABLE_ORDER")?$clsnm::TABLE_ORDER:null;

        is_string($tablename) or KbylinException::throwing('Constant TABLE_NAME require to be string !');
        is_array($fields) or KbylinException::throwing('Constant TABLE_FIELDS require to be array !');

        $this->dao = Dao::getInstance();

        $this->reset([
            'table'     => $tablename,
            'order'     => $order,
        ]);
    }

    /**
     * 重置CURD参数
     * @param array|null $originOption 初始化时使用的参数
     * @return void
     * @throws KbylinException
     */
    protected function reset(array $originOption=null){
        static $origin = [
            //查询
            'distinct'  => false,
            'fields'    => ' * ',//操作的字段,最终将转化成字符串类型.(可以转换的格式为['fieldname'=>'value'])
            'table'     => null,//操作的数据表名称
            'join'      => null,
            'where'     => null,//操作的where信息
            'group'     => null,
            'order'     => null,
            'having'    => null,
        ];
        null !== $originOption and $origin = array_merge($origin,$originOption);

        $this->_options = $origin;
        $this->_inputs = [];
    }

    /**
     * 上一次执行的SQL语句
     * @var string
     */
    public static $_lastSql = null;
    /**
     * 返回上一次查询的SQL输入参数
     * @var array
     */
    public static $_lastInputs = null;

    /**
     * 获取上一次执行的SQL
     * @return null|string
     */
    public static function getLastSql(){
        return Model::$_lastSql;
    }

    public static function getLastInputs(){
        return Model::$_lastInputs;
    }

/********************************************** 链式操作 **************************************************************************************************/

    /**
     * 设置distinct
     * @param bool $dist
     * @return $this
     */
    public function distinct($dist=true){
        $this->_options['distinct'] = $dist;
        return $this;
    }

    /**
     * 当参数为非null时批量设置字段的值,并将全部字段的值返回
     * 参数为null时获取全部字段的值
     * @param array|string|true $fields 加入的字段数组
     * @return $this
     */
    public function fields($fields){
        if(is_array($fields)){
            //是数组的情况通常用于update/create
            $keys = array_keys($fields);
            array_walk($keys,function(&$field){ $field = $this->dao->escape($field);});//对字段进行转义
//            dumpout($fields);
            $this->_options['fields'] = implode(',', $keys);
            $this->_inputs['fields'] = array_values($fields);
        }elseif(is_string($fields)){
            //用于select的清空
            $this->_options['fields'] = $fields;
        }elseif(true === $fields){
            $this->_options['fields'] = ' * ';
        }else{
            KbylinException::throwing($fields,'expect to be array/string/true!');
        }
        return $this;
    }

    /**
     * 设置当前要操作的数据表
     * @param $tablename
     * @return $this
     */
    public function table($tablename){
        $this->_options['table'] = $tablename;
        return $this;
    }

    /**
     * 设置where条件,where条件设置为null或者任何empty值时表示不对之进行限制
     * @param array|string $where
     * @return $this
     */
    public function where($where){
        if(is_array($where)){
            $where = $this->_getSegments($where, Model::CONNECT_AND);
            $this->_inputs['where'] = $where[1];
            $where = $where[0];
        }
        is_string($where) or KbylinException::throwing('Where should be string/array!');
        $this->_options['where'] = $where;
        return $this;
    }

    /**
     * 添加数据对象到数据库中
     * <code>
     *      $fldsMap ==> array(
     *          'fieldName' => 'fieldValue',
     *      );
     * </code>
     *
     * 插入数据的sql可以是：
     * ①INSERT INTO 表名称 VALUES (值1, 值2,....)
     * ②INSERT INTO table_name (列1, 列2,...) VALUES (值1, 值2,....)
     *
     * @param string $tablename 表格名称
     * @param array $data 输入数据
     * @return bool 是否成功插入
     * @throws KbylinException
     */
    public function create($tablename=null,array $data=null){
        if($tablename === null){
            //空参数 - 显式声明是链式调用的终点
            empty($this->_inputs['fields']) and KbylinException::throwing('No data prepared to insert!');

            //所有要插入的参数都需要经过绑定进行插入
            $holder = rtrim(str_repeat('?,', count($this->_inputs['fields'])),',');

            //检查必要的两个字段
            $tablename = $this->_options['table']?$this->_options['table']:KbylinException::throwing('No table to insert!',static::class);
            $fields = $this->_options['fields']?$this->_options['fields']:KbylinException::throwing('Empty fields is not allowed!');
            //输入参数只使用到了fields字段

            $inputs = $this->_inputs['fields'];
            $sql = "INSERT INTO {$tablename}  ( {$fields} ) VALUES ({$holder});";
            return $this->exec($sql,$inputs);
        }else{
            //给定了参数的情况下无需考虑链式调用设置的参数
            $keys = array_keys($data);
            $inputs = array_values($data);
            array_walk($keys,function(&$field){ $field = $this->dao->escape($field);});//对字段进行转义
            $fields = implode(',', $keys);
            $placeholder = rtrim(str_repeat('?,', count($keys)),',');
            empty($fields) and KbylinException::throwing('Empty field is not allowed');

            return $this->exec("INSERT INTO {$tablename} ( {$fields} ) VALUES ( {$placeholder} );",$inputs);
        }
    }

    /**
     * 执行EXEC类型的SQL并返回结果
     * @param string $sql 查询SQL
     * @param array|null $inputs 输入参数
     * @return false|int
     */
    public function exec($sql,array $inputs=[]){
        $result = $this->dao->exec(Model::$_lastSql=$sql,Model::$_lastInputs = $inputs);
        $this->reset();
        return $result;
    }

    /**
     * 执行返回结果集合的SQL并返回结果集合
     * @param string $sql 查询SQL
     * @param array|null $inputs 输入参数
     * @return array|false
     */
    public function query($sql,array $inputs=null){
        $result = $this->dao->query(Model::$_lastSql=$sql,Model::$_lastInputs = $inputs);
        $this->reset();
        return $result;
    }

    /**
     * 从数据库中删除指定条件的数据对象
     * 如果不设置参数，则进行清空表的操作（谨慎使用）
     * @param string $tablename 数据表的名称
     * @param array $where 字段映射数组,显示声明为null时表示清空这张表,否则如果提供的where条件为空时会抛出异常
     * @return bool 是否成功删除
     * @throws KbylinException
     */
    public function delete($tablename=null,array $where=null){
        if(null === $tablename){
            //检查必要参数
            $tablename = $this->_options['table']?$this->_options['table']:KbylinException::throwing('No table to insert!');
            $where = $this->_options['where']?$this->_options['where']:KbylinException::throwing('Where condition should be declared while deleting records!!');
            $inputs = isset($this->_inputs['where'])?$this->_inputs['where']:[];
            return $this->exec("DELETE FROM {$tablename} WHERE {$where};",$inputs);
        }else{
            $where_missing = 'Where should not be empty while execute an delete sql!';
            $where or KbylinException::throwing($where_missing);
            $where  = $this->_getSegments($where,Model::CONNECT_AND);
            empty($where[0]) and KbylinException::throwing($where_missing);
            return $this->exec("DELETE FROM {$tablename} WHERE {$where[0]};",$where[1]);
        }
    }

    /**
     * 获取查询选项中满足条件的记录数目
     * @return int 返回表中的数据的条数,发生了错误将不会返回数据
     * @throws KbylinException
     */
    public function count(){
        empty($this->_options['table']) and KbylinException::throwing('Model has no table binded!');
        $this->_options['fields'] = ' count(*) as c';
        $result = $this->select();
        isset($result[0]['c']) or KbylinException::throwing($this->_options,$result);
        return intval($result[0]['c']);
    }

    /**
     * 从数据库中修改指定的数据
     * @param string $tablename
     * @param string|array $fields
     * @param string|array $where
     * @return bool
     * @throws KbylinException
     */
    public function update($tablename=null, $fields=null, $where=null){
//        static $c = 0;
        if(null === $tablename){
            /* 链式链式调用(不带参数) */
            empty($this->_options['table']) and KbylinException::throwing('Table should not be empty!',$this->_options);
            $tablename = $this->_options['table'];
            //设置更新字段
            empty($this->_options['fields']) and KbylinException::throwing('Fields should not be empty!',$this->_options);
            $fields = explode(',',$this->_options['fields'] );
            array_walk($fields,function (&$field){
                $field = " {$field} = ? ";
            });
            $fields = implode(',',$fields);
            //where条件设置
            empty($this->_options['where']) and KbylinException::throwing('Where should not be empty!',$this->_options);
            $where = $this->_options['where'];


            $sql = "UPDATE {$tablename} SET {$fields} WHERE {$where};";

            if(isset($this->_inputs['fields'])) $inputs = $this->_inputs['fields'];
            else $inputs = [];
            if(isset($this->_inputs['where'])) $inputs = array_merge($inputs,$this->_options['where']);

//            if(++$c === 4) dumpout([$sql,$inputs]);

            $result = $this->exec($sql,$inputs);
//            dumpout([$sql,$inputs],$result);
            return $result;
        }else{
            $inputs = [];
            if(is_array($fields)){
                $fields = $this->_getSegments($fields,Model::CONNECT_COMMA);

            }
            $fields = is_string($fields)?[$fields]:$this->_getSegments($fields,Model::CONNECT_COMMA);
            $where  = is_string($where) ?[$where] :$this->_getSegments($where, Model::CONNECT_AND);

            empty($fields[1]) or $inputs = $fields[1];
            empty($where[1]) or $inputs = array_merge($inputs,$where[1]);
            $sql = "UPDATE {$tablename} SET {$fields[0]} WHERE {$where[0]};";

            return $this->exec($sql,$inputs);
        }
    }


    public function find(){
        $result = $this->select(null);
        if(!empty($result[0])){
            return $result[0];
        }
        return false;
    }

    /**
     * 从数据库中获取指定条件的数据对象
     * @param array|null|string $options 如果是字符串是代表查询这张表中的所有数据并直接返回
     * @return array|bool 返回数组或者false(发生了错误)
     * @throws KbylinException
     */
    public function select($options=null){
        if(null === $options){
            //链式操作
            $sql = $this->_options['distinct']?'SELECT DISTINCE ':'SELECT ';
            empty($this->_options['table']) and KbylinException::throwing('Model has no table binded!');

//            dumpout($this->_options);
            //set the mastable parameters(fields and table)
            $sql .= $this->_options['fields'].' FROM '.$this->_options['table'];

            //set the choosable parameters
            $this->_options['where'] and $sql .= ' WHERE '.$this->_options['where'];
            $this->_options['group'] and $sql .= ' GROUP BY '.$this->_options['group'];
            $this->_options['order'] and $sql .= ' ORDER BY '.$this->_options['order'];

            //set the input parameters
            if(isset($this->_inputs['fields'])) $inputs = $this->_inputs['fields'];
            else $inputs = [];
            if(isset($this->_inputs['where'])) $inputs = array_merge($inputs,$this->_inputs['where']);

//            dumpout($sql,$inputs);
            return $this->query($sql,$inputs);
        }

        if(is_string($options)){
            $sql  = "SELECT * FROM {$options};";
            return $this->query($sql,null);
        }
        is_array($options) or KbylinException::throwing('The first parameter of Dao->select should be array(components) of string(tablename)!',$options);
        $components = [
            'distinct'  => false,
            'fields'    => null,//select all fields while '==' to false
            'join'      => [],
            'table'     => null,
            'where'     => [],
            'order'     => [],
            'group'     => [],
        ];
        $components = array_merge($components,$options);
//        extract($components,EXTR_OVERWRITE);
        $sql = $components['distinct']? 'SELECT DISTINCT':'SELECT ';
        $inputs = null;

        //设置选取字段
        if(empty($components['fields'])){
            $components['fields'] = ' * ';
        }elseif(is_array($components['fields'])){/*此时可以保证不是空数组,在第一关的时候已经被过滤掉了*/
            //默认转义
            array_map(function($param){
                return $this->dao->escape($param);
            },$components['fields']);
            $components['fields'] = implode(',',$components['fields']);
        }
        !is_string($components['fields']) and KbylinException::throwing('Fields should be string !',$components['fields']);
        $sql ="{$sql} {$components['fields']} ";

        if(!empty($components['join'])){
            if(is_array($components['join'])){
                foreach ($components['join'] as $join){
                    $sql .= "\n{$join}\n";
                }
            }elseif (is_string($components['join'])){
                $sql .= "\n{$components['join']}\n";
            }else{
                KbylinException::throwing('Wrong join for select!',$components['join']);//不为空却非法
            }
        }

        if(empty($components['table'])){
            KbylinException::throwing('Could not select data from an empty table',$components['table']);
        }else{
            $sql .= "FROM \n{$components['table']}\n";
        }

        if(!empty($components['where'])){
            if(is_array($components['where'])){
                $temp = $this->_getSegments($components['where'],Model::CONNECT_AND);
                $components['where'] = $temp[0];
                $inputs = $components['where'][1];
            }
            !is_string($components['where']) and KbylinException::throwing('Where should be the type of array or string!',$components['where']);
            $sql .= "WHERE {$components['where']} ";
        }

        if(!empty($components['group'])){
            if(is_array($components['group'])){
                $components['group'] = implode(',',$components['group']);
            }
            !is_string($components['group']) and KbylinException::throwing('Group should be the type of array or string!',$components['group']);
            $sql .= "GROUP BY {$components['group']} ";
        }

        if(!empty($components['order'])){
            if(is_array($components['order'])){
                $components['order'] = implode(',',$components['order']);
            }
            !is_string($components['order']) and KbylinException::throwing('Order should be the type of array or string!',$components['order']);
            $sql .= "ORDER BY {$components['order']} ";
        }

        return $this->query($sql,$inputs);
    }

    /**
     * 综合字段绑定的方法
     * <code>
     *      $operator = '='
     *          $fieldName = :$fieldName
     *          :$fieldName => trim($fieldValue)
     *
     *      $operator = 'like'
     *          $fieldName = :$fieldName
     *          :$fieldName => dowithbinstr($fieldValue)
     *
     *      $operator = 'in|not_in'
     *          $fieldName in|not_in array(...explode(...,$fieldValue)...)
     * </code>
     * @param string $fieldName 字段名称
     * @param string|array $fieldValue 字段值
     * @param string $operator 操作符
     * @param bool $escape 是否对字段名称进行转义,MSSQL中使用[]
     * @return array
     * @throws KbylinException
     */
    private function _getFieldSegment($fieldName, $fieldValue, $operator=Model::OPERATOR_EQUAL, $escape=false){
        $holder = null;
        //该库开启的清空下
        if(false !== strpos($fieldName,'.')){
            //字段被制定了表的情况下
            $arr = explode('.',$fieldName);
            $holder = ':'.array_pop($arr);
        }else{
            $holder = ":{$fieldName}";
        }

//        $sql = (self::$_conventions[self::class]['AUTO_ESCAPE_ON'] or $escape)? $this->dao->escape($fieldName):$fieldName;
        $sql = $this->dao->escape($fieldName);
        $input = [];

        switch($operator){
            case Model::OPERATOR_EQUAL:
            case Model::OPERATOR_NOTEQUAL:
            case Model::OPERATOR_LIKE:
            case Model::OPERATOR_NOTLIKE:
                $sql .= " {$operator} {$holder} ";
                $input[$holder] = $fieldValue;
                break;
            case Model::OPERATOR_IN:
            case Model::OPERATOR_NOTIN:
                if(is_array($fieldValue)) $fieldValue = "'".implode("','",$fieldValue)."'";
                is_string($fieldValue) or KbylinException::throwing($fieldValue);
                $sql .= " {$operator} ({$fieldValue}) ";
                break;
            default:
                KbylinException::throwing("Unkown operator of '{$operator}'");
        }
        return [$sql,$input];
    }

    /**
     * 片段翻译(片段转化)
     * <note>
     *      片段匹配准则:
     *      $map == array(
     *           //第一种情况,连接符号一定是'='//
     *          'key' => $val,
     *          'key' => array($val,$operator,true),
     *
     *          //第二种情况，数组键，数组值//    -- 现在保留为复杂and和or连接 --
     *          //array('key','val','like|=',true),//参数4的值为true时表示对key进行[]转义
     *          //array(array(array(...),'and/or'),array(array(...),'and/or'),...) //此时数组内部的连接形式
     *
     *          //第三种情况，字符键，数组值//
     *          'assignSql' => array(':bindSQLSegment',value)//与第一种情况第二子目相区分的是参数一以':' 开头
     *      );
     * </note>
     * @param array $segments 片段数组
     * @param string $connect 表示是否使用and作为连接符，false时为,
     * @return array
     * @throws KbylinException
     */
    private function _getSegments($segments, $connect=Model::CONNECT_AND){
        $segments or KbylinException::throwing($segments,$connect);

        $sql = '';
        $bind = [];

        //元素连接
        foreach($segments as $field=> $segment){
            if(is_numeric($field)){
                //第二中情况,符合形式组成
                $result = $this->_getSegments($segment[0],$segment[1]);
                $sql .= " {$result[0]} {$connect}";
                $bind = array_merge($bind, $result[1]);
            }
            elseif(is_array($segment) and strpos($segment[0],':') === 0){
                //第三种情况,过于复杂而选择由用户自定义
                $sql .= " {$field} {$connect}";
                $bind[$segment[0]] = $segment[1];
            }
            else{
                //第一种情况
                $escape = false;
                $operator = Model::OPERATOR_EQUAL;

                if(is_array($segment)){
                    $escape = isset($segment[2])?$segment[2]:false;
                    $operator = isset($segment[1])?$segment[1]:Model::OPERATOR_EQUAL;
                    $segment = $segment[0];
                }
                $rst = $this->_getFieldSegment($field,trim($segment),$operator,$escape);//第一种情况一定是'='的情况
                if(is_array($rst)){
                    $sql .= " {$rst[0]} {$connect}";
                    $bind = array_merge($bind, $rst[1]);
                }
            }
        }
        return [
            substr($sql,0,strlen($sql)-strlen($connect)),
            $bind,
        ];
    }

    /**
     * 设置默认操作的Dao的角标
     * @param null|int|string $index 角标的Index,设置成null时表示恢复默认
     * @return $this;
     */
    protected function using($index){
        $this->_cur_dao_index = $index;
        return $this;
    }

    private $_error = null;

    /**
     * @param null $error
     * @return bool|null|string
     */
    public function error($error=null){
        if(isset($error)){
            //设置了error参数表示设置自定义的错误,同时返回false表示发生了错误
            $this->_error = $error;
            return false;
        }
        if(null === $this->_error){
            $this->_error = $this->dao->getError();
        }
        return $this->_error;
    }
    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction(){
        return $this->dao->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(){
        return $this->dao->commit();
    }
    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack(){
        return $this->dao->rollBack();
    }
    /**
     * 确认是否在事务中
     * @return bool
     */
    public function inTransaction(){
        return $this->dao->inTransaction();
    }

}