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


    /**
     * 操作类型
     */
    const DATA_SELECT = 0;//查询操作,将使用到$_fields和$_where字段
    const DATA_CREATE = 1;//添加操作,将使用到$_fields字段
    const DATA_UPDATE = 2;//更新操作,将使用到$_fields和$_where字段
    const DATA_DELETE = 3;//删除操作,将使用到$_where字段

    /**
     * 模型对应的实际表的名称
     * null时表示未存在对应表
     * @var string
     */
    protected $_table = null;

    /**
     * 对应表的字段列表
     * 格式为 ['fieldname'=>'value']
     * 如果value值为null时表示不对之进行设置,可以调用clear方法进行显示清空
     * @var array
     */
    protected $_fields = [];

    /**
     * 对应表的查询字段列表
     * 为null时表示不限制
     * @var array|null
     */
    protected $_where = [];

    /**
     * 使用private将之私有以
     * @var Dao[]
     */
    private $_daos = [];

    /**
     * 默认的dao的角标
     * @var int|string
     */
    private $_cur_dao_index = null;

    /**
     * Model constructor.
     * 单参数为非null时就指定了该表的数据库和字段,来对制定的表进行操作
     * @param string $tablename 表的实际名称
     * @param string $fields 字段数组
     */
    public function __construct($tablename=null,$fields=null){
        null !== $tablename and $this->_table = $tablename;
        null !== $fields and $this->_fields = $fields;
    }

    /**
     * 获取$_fields字段值
     * 不存在该字段时返回null
     * @param string $name 字段名称
     * @return mixed|null
     */
    public function __get($name){
        return isset($this->_fields[$name])?$this->_fields[$name]:null;
    }

    /**
     * 设置$_fields字段值
     * @param $name
     * @param $value
     * @return $this
     */
    public function __set($name, $value){
        if(isset($this->_fields[$name])){
            $this->_fields[$name] = $value;
        }
        return $this;
    }

    /**
     * 设置单个字段
     * @param string $key 字段名称
     * @param int|string $value 字段值,字段值未设置时表示将在之后的某个时间点批量执行
     * @return $this
     */
    public function field($key,$value=''){
        key_exists($key,$this->_fields) and $this->_fields[$key] = $value;
        return $this;
    }

    /**
     * 当参数为非null时批量设置字段的值,并将全部字段的值返回
     * 参数为null时获取全部字段的值
     * @param array|null $fields 加入的字段数组
     * @return $this
     */
    public function fields(array $fields=null){
        if(null !== $fields){
            foreach ($fields as $key=>$val) key_exists($key,$this->_fields) and $this->_fields[$key] = $val;
//            $this->_fields = array_merge($this->_fields,$fields);
        }
        return $this;
    }

    /**
     * 设置where条件,where条件设置为null或者任何empty值时表示不对之进行限制
     * @param array $where
     * @return $this
     */
    public function where(array $where){
        foreach ($where as $key=>$val)  isset($this->_fields[$key]) and $this->_fields[$key] = $val;
        return $this;
    }

    /**
     * 清除字段的预设
     * @return void
     */
    protected function clear(){
        //清空设置/查询字段表
        if($this->_fields) foreach ($this->_fields as $fieldname=>&$fieldval) $fieldval = null;
        //清空where字段表
        if($this->_where) foreach ($this->_where as $fieldname=>&$fieldval) $fieldval = null;
    }

    /**
     * 获取数据访问接口对象
     * @param null|int|string $index
     * @param $config
     * @return \System\Core\Dao
     */
    protected function getDao($index=null,array $config=null){
        $this->_cur_dao_index = $index;
        if(!isset($this->_daos[$this->_cur_dao_index])){
            $this->_daos[$this->_cur_dao_index] = Dao::getInstance($this->_cur_dao_index,$config);
        }
        return $this->_daos[$this->_cur_dao_index];
    }

    /**
     * 设置默认操作的Dao的角标
     * @param null|int|string $index 角标的Index,设置成null时表示恢复默认
     * @return $this;
     */
    protected function useDao($index){
        $this->_cur_dao_index = $index;
        return $this;
    }

    /**
     * 获取查询的错误
     * @param null|int|string $index
     * @return string
     */
    public function getError($index=null){
        return $this->getDao($index)->getError();
    }

    /**
     * 添加数据对象到数据库中
     * @return bool 是否成功插入
     * @throws KbylinException
     */
    public function create(){
        if(null === $this->_table) throw new KbylinException('Module has no table binded!');
        $result =  $this->getDao()->create($this->_table,$this->_fields);
        $this->clear();
        return $result;
    }

    /**
     * 从数据库中删除指定条件的数据对象
     * @return bool 是否成功删除
     * @throws KbylinException
     */
    public function delete(){
        if(null === $this->_table) throw new KbylinException('Module has no table binded!');
        $result = $this->getDao()->delete($this->_table,$this->_where);
        $this->clear();
        return $result;
    }

    /**
     * 清空一张表
     * @return bool 是否成功删除
     */
    public function clean(){
        return $this->getDao()->delete($this->_table,null);
    }

    public function build($type){
        switch ($type){
            case self::DATA_SELECT:
                $this->getDao()->select($this->_table,$this->_fields,$this->_where);
                $this->clear();
                break;
            case self::DATA_CREATE:

                break;
            case self::DATA_UPDATE:

                break;
            case self::DATA_DELETE:

                break;
            default:
                throw new KbylinException('Unexpected operation!');
        }

    }


}