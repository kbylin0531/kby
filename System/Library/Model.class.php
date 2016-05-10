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

    const TABLE_NAME = '';//用于指定本模型对应的表,只允许字符串类型
    const TABLE_FIELDS = [];//用于指定本模型对应的字段列表,键为字段名称,值为字段默认值

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
    private $_table = '';

    /**
     * 对应表的字段列表
     * 格式为 ['fieldname'=>'value']
     * 如果value值为null时表示不对之进行设置,可以调用clear方法进行显示清空
     * @var array
     */
    private $_fields = [];

    /**
     * 对应表的查询字段列表
     * 为null时表示不限制
     * @var array|null
     */
    private $_where = [];

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

    /**用户自定义的错误
     * @var string
     */
    private $_errors = '';

    /**
     * Model constructor.
     * 单参数为非null时就指定了该表的数据库和字段,来对制定的表进行操作
     * @param string $tablename 表的实际名称,不指定时候将使用类常量中定义的值
     * @param string $fields 字段数组,不指定时候将使用类常量中定义的值
     * @throws KbylinException
     */
    public function __construct($tablename=null,$fields=null){
        if(null !== $tablename){
            $this->_table = $tablename;
        }else{
            $classname = static::class;
            $this->_table = $classname::TABLE_NAME;
        }
        if(null !== $fields){
            $this->_fields = $fields;
        }else{
            $classname = static::class;
            $this->_fields = $classname::TABLE_FIELDS;
        }
        if(!is_string($this->_table)){
            throw new KbylinException('Constant TABLE_NAME require to be string !');
        }
        if(!is_array($this->_fields)){
            throw new KbylinException('Constant TABLE_FIELDS require to be array !');
        }
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
        foreach ($where as $key=>$val)  key_exists($key,$this->_fields) and $this->_where[$key] = $val;
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
     * 设置模型的错误
     * 在下次调用getError(会将该错误一起返回)
     * @param string $error
     * @return void
     */
    public function setError($error){
        $this->_errors = $this->_errors.PHP_EOL.$error;
    }

    /**
     * 获取查询的错误
     * @param null|int|string $index
     * @return string
     */
    public function getError($index=null){
        if($this->_errors){
            $error = $this->_errors.PHP_EOL.$this->getDao($index)->getError();
            $this->_errors = '';
        }else{
            $error = $this->getDao($index)->getError();
        }
        return $error;
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
     * 从数据库中获取指定条件的数据对象
     * @return array|bool 返回数组或者false(发生了错误)
     * @throws KbylinException
     */
    public function select(){
        if(null === $this->_table) throw new KbylinException('Module has no table binded!');
        $list = $this->getDao()->select($this->_table,$this->_where);
        $this->clear();
        return $list;
    }

    /**
     * 从数据库中修改指定的数据
     * @return bool 是否成功
     * @throws KbylinException
     */
    public function update(){
        if(null === $this->_table) throw new KbylinException('Module has no table binded!');
//        dumpout($this->_fields,$this->_where);
        $fields = [];
        foreach ($this->_fields as $key=>$value) isset($value) and $fields[$key] = $value;
        $result = $this->getDao()->update($this->_table,$fields,$this->_where);
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

    /**
     * 获取SQL及其输入参数
     * @param $type
     * @throws KbylinException
     */
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