<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/2/1
 * Time: 16:23
 */
namespace System\Core;
use System\Core\Dao\DaoAbstract;
use System\Core\Dao\MySQL;
use System\Core\Dao\OCI;
use System\Core\Dao\SQLServer;
use System\Traits\Crux;
use PDOStatement;
use PDO;

/**
 * Class Dao 数据入口对象(Data Access Object)
 * 一个Dao对应一个数据路的入口
 * 具体方法的实现以来于各个驱动
 *
 *
 * 可以通过Dao::getInstance()获取默认的Dao实例
 *
 * @package System\Core
 */
class Dao {
    use Crux;
    const CONF_NAME = 'dao';
    const CONF_CONVENTION = [
        'AUTO_ESCAPE_ON'    => true,
        'DRIVER_DEFAULT_INDEX' => 0,
        'DRIVER_CLASS_LIST' => [
            MySQL::class,
            OCI::class,
            SQLServer::class,
        ],
        'DRIVER_CONFIG_LIST' => [
            [
                'type'      => 'Mysql',//数据库类型
                'dbname'    => 'xor',//选择的数据库
                'username'  => 'lin',
                'password'  => '123456',
                'host'      => 'localhost',
                'port'      => '3306',
                'charset'   => 'UTF8',
                'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
                'options'   => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
                ],
            ],
            [
                'type'      => 'Oci',//数据库类型
                'dbname'    => 'xor',//选择的数据库
                'username'  => 'lin',
                'password'  => '123456',
                'host'      => 'localhost',
                'port'      => '3306',
                'charset'   => 'UTF8',
                'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
                'options'   => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
                ],
            ],
            [
                'type'      => 'Sqlsrv',//数据库类型
                'dbname'    => 'xor',//选择的数据库
                'username'  => 'lin',
                'password'  => '123456',
                'host'      => 'localhost',
                'port'      => '3306',
                'charset'   => 'UTF8',
                'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
                'options'   => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
                ],
            ],
        ],
    ];

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
     * 每一个Dao的驱动实现
     * @var DaoAbstract
     */
    private $driver = null;

    /**
     * 当前操作的PDOStatement对象
     * @var PDOStatement
     */
    private $curStatement = null;
    /**
     * SQL执行发生的错误信息
     * @var string|null
     */
    private $error = null;

    /**
     * 获取Dao实例
     * @param int|string|array $driver_index 驱动器角标
     * @return Dao
     */
    public static function getInstance($driver_index=null){
        static $_daoInstances = [];//Dao[]
        if(!isset($_daoInstances[$driver_index])){
            $_daoInstances[$driver_index] = new Dao($driver_index);
        }
        return $_daoInstances[$driver_index];
    }

    public static function getInstanceBy($config){

    }

    /**
     * 获取PDO对象中的错误信息
     * @param PDO $pdo PDO对象或者继承类的实例
     * @return null|string null表示未发生错误,string表示序列化的错误信息
     */
    public static function fetchPDOError(PDO $pdo){
        $pdoError = $pdo->errorInfo();
        return null !== $pdoError[0]? "PDO Error:{$pdoError[0]} >>> [{$pdoError[1]}]:[{$pdoError[2]}]":null;// PDO错误未被设置或者错误未发生,0位的值为null
    }

    /**
     * 获取PDOStatemnent对象上查询时发生的错误
     * 错误代号参照ANSI CODE ps: https://docs.oracle.com/cd/F49540_01/DOC/server.815/a58231/appd.htm
     * @param PDOStatement|null $statement 发生了错误的PDOStatement对象
     * @return string|null 错误未发生时返回null
     */
    public static function fetchStatementError(PDOStatement $statement){
        $stmtError = $statement->errorInfo();
        return 0 !== intval($stmtError[0])?"Error Code:[{$stmtError[0]}]::[{$stmtError[1]}]:[{$stmtError[2]}]":null;//代号为0时表示错误未发生
    }

    /**
     * Dao constructor.
     * @param int|string|array $index 驱动器角标,数组形式则为配置
     */
    private function __construct($index=null){
        $this->driver = self::getDriverInstance($index);
    }

    /**
     * 获取原生的PDO继承对象
     * @return DaoAbstract
     */
    public function getDriver(){
        return $this->driver;
    }

/********************************* 基本的查询功能 ***************************************************************************************/
    /**
     * 简单地查询一段SQL，并且将解析出所有的结果集合
     * @param string $sql 查询的SQL
     * @param array|null $inputs 输入参数
     *                          如果输入参数未设置或者为null（显示声明），则直接查询
     *                          如果输入参数为非空数组，则使用PDOStatement对象查询
     * @return array|false 返回array类型表述查询结果，返回false表示查询出错，可能是数据表不存在等数据库返回的错误信息
     */
    public function query($sql,array $inputs=null){
        if(null === $inputs){
            //直接使用PDO的查询功能
            try{
                $statement = $this->driver->query($sql);//返回PDOstatement,失败时返回false(或者抛出异常)，视错误的处理方式而定

                if(false !== $statement){
                    //query成功时返回PDOStatement对象
                    return $statement->fetchAll();//成功返回
                }else{
                    $this->setPdoError();
                }
            }catch(\PDOException $e){
                $this->setPdoError($e->getMessage());
            }
        }else{
            try {
                //简介调用PDOStatement的查询功能
                $statement = $this->driver->prepare($sql);
                if(false !== $statement and false !== $statement->execute($inputs)){
                    dumpout($statement->fetch());
                    return $statement->fetchAll();
                }
            }catch(\PDOException $e){
                /* prepare可能失败,返回错误或者抛出异常视PDO::ERRMODE_EXCEPTION设置情况而定 */
                $this->setPdoStatementError($e->getMessage());
            }
        }
        return false;
    }
    /**
     * 简单地执行Insert、Delete、Update操作
     * @param string $sql 待查询的SQL语句，如果未设置输入参数则需要保证SQL已经被转义
     * @param array|null $inputs 输入参数,具体参考query方法的参数二
     * @return int|false 返回受到影响的行数，但是可能不会太可靠，需要用===判断返回值是0还是false
     *                   返回false表示了错误，可以用getError获取错误信息
     */
    public function exec($sql,array $inputs=null){
        if(empty($inputs)){
            //调用PDO的查询功能
            try{
                $rst = $this->driver->exec($sql);
                if(false === $rst){
                    $this->setPdoError();
                }else{
                    return $rst;
                }
            }catch (\PDOException $e){
                $this->error = $e->getMessage();
            }
        }else{
            try {
                //简介调用PDOStatement的查询功能
                $statement = $this->driver->prepare($sql);
                if(false !== $statement and false !== $statement->execute($inputs)){
                    return $statement->rowCount();
                }
            }catch(\PDOException $e){
                /* prepare可能失败,返回错误或者抛出异常视PDO::ERRMODE_EXCEPTION设置情况而定 */
                $this->setPdoStatementError($e->getMessage());
            }
        }
        return false;
    }


/********************************* 高级查询功能 ***************************************************************************************/

    /**
     * 准备一段SQL
     *  <note>
     *      prepare('insert *****',$id='helo');  准备一段SQL并命名ID为helo
     *      prepare( null|false|''|0 ,$id='helo');  切换到该ID下，并将PDOStatement返回
     *      prepare('insert *****');  将SQL语句设置ID为0并默认指向0
     *  </note>
     * @param string $sql 查询的SQL，当参数二指定的ID存在，只有在参数一布尔值不为false时，会进行真正地prepare
     * @param array $option prepare方法参数二
     * @return $this 返回prepare返回的对象，如果失败则返回null
     */
    public function prepare($sql,array $option=[]){
        try{
            $this->curStatement = null;//如果之前有prepare的SQLStatement对象，隐式清空
            $this->curStatement = $this->driver->prepare($sql,$option);//prepare失败抛出异常后赋值过程结束,$this->curStatement可能依旧指向之前的SQLStatement对象（可能不为null）
        }catch(\PDOException $e){
            /* 当表不存在或者字段不存在时候 */
            $this->setPdoStatementError($e->getMessage());
        }
        return $this;
    }

    /**
     * 执行查询功能，返回的结果是bool表示是否执行成功
     * @param array|null $input_parameters
     *                  一个元素个数和将被执行的 SQL 语句中绑定的参数一样多的数组。所有的值作为 PDO::PARAM_STR 对待。
     *                  不能绑定多个值到一个单独的参数,如果在 input_parameters 中存在比 PDO::prepare() 预处理的SQL 指定的多的键名，
     *                  则此语句将会失败并发出一个错误。(这个错误在PHP 5.2.0版本之前是默认忽略的)
     * @param PDOStatement|null $statement 该参数未设定或者为null时使用的PDOStatement为上次prepare的对象
     * @return bool bool值表示执行结果，当不存在执行对象时返回null，可以通过rowCount方法获取受到影响行数，或者getError获取错误信息
     */
    public function execute(array $input_parameters = null, PDOStatement $statement=null){
        null !== $statement and $this->curStatement = $statement;
        if(!$this->curStatement or !($this->curStatement instanceof PDOStatement)){
            return false;
        }

        try{
            //出错时设置错误信息，注：PDOStatement::execute返回bool类型的结果
            if(false === $this->curStatement->execute($input_parameters)){//参数数目不正确时候会抛出异常"Invalid parameter number"
                $this->setPdoStatementError($this->curStatement);
                return false;
            }
        }catch(\PDOException $e){
            $this->setPdoStatementError($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 返回一个包含结果集中所有剩余行的数组
     * 此数组的每一行要么是一个列值的数组，要么是属性对应每个列名的一个对象
     * @param int|null $fetch_style
     *          想要返回一个包含结果集中单独一列所有值的数组，需要指定 PDO::FETCH_COLUMN ，
     *          通过指定 column-index 参数获取想要的列。
     *          想要获取结果集中单独一列的唯一值，需要将 PDO::FETCH_COLUMN 和 PDO::FETCH_UNIQUE 按位或。
     *          想要返回一个根据指定列把值分组后的关联数组，需要将 PDO::FETCH_COLUMN 和 PDO::FETCH_GROUP 按位或
     * @param int $fetch_argument
     *                  参数一为PDO::FETCH_COLUMN时，返回指定以0开始索引的列（组合形式如上）
     *                  参数一为PDO::FETCH_CLASS时，返回指定类的实例，映射每行的列到类中对应的属性名
     *                  参数一为PDO::FETCH_FUNC时，将每行的列作为参数传递给指定的函数，并返回调用函数后的结果
     * @param array $constructor_args 参数二为PDO::FETCH_CLASS时，类的构造参数
     * @return array
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $constructor_args = null){
        $param = [];
        isset($fetch_style)         and $param[0] = $fetch_style;
        isset($fetch_argument)      and $param[1] = $fetch_argument;
        isset($constructor_args)    and $param[2] = $constructor_args;
        return call_user_func_array(array($this->curStatement,'fetchAll'),$param);
    }

    /**
     * 从结果集中获取下一行
     * @param int $fetch_style
     *              \PDO::FETCH_ASSOC 关联数组
     *              \PDO::FETCH_BOUND 使用PDOStatement::bindColumn()方法时绑定变量
     *              \PDO::FETCH_CLASS 放回该类的新实例，映射结果集中的列名到类中对应的属性名
     *              \PDO::FETCH_OBJ   返回一个属性名对应结果集列名的匿名对象
     * @param int $cursor_orientation 默认使用\PDO::FETCH_ORI_NEXT，还可以是PDO::CURSOR_SCROLL，PDO::FETCH_ORI_ABS，PDO::FETCH_ORI_REL
     * @param int $cursor_offset
     *              参数二设置为PDO::FETCH_ORI_ABS(absolute)时，此值指定结果集中想要获取行的绝对行号
     *              参数二设置为PDO::FETCH_ORI_REL(relative) 时 此值指定想要获取行相对于调用 PDOStatement::fetch() 前游标的位置
     * @return mixed 此函数（方法）成功时返回的值依赖于提取类型。在所有情况下，失败都返回 FALSE
     */
    public function fetch($fetch_style = null, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0){
        return $this->curStatement->fetch($fetch_style,$cursor_orientation,$cursor_offset);
    }

    /**
     * 返回上一个由对应的 PDOStatement 对象执行DELETE、 INSERT、或 UPDATE 语句受影响的行数
     * 如果上一条由相关 PDOStatement 执行的 SQL 语句是一条 SELECT 语句，有些数据可能返回由此语句返回的行数
     * 但这种方式不能保证对所有数据有效，且对于可移植的应用不应依赖于此方式
     * @return int
     * @throws KbylinException
     */
    public function rowCount(){
        if(!$this->curStatement){
            throw new KbylinException('Invalid PDOStatement');
        }
        return $this->curStatement->rowCount();
    }


    /**
     * 返回PDO驱动或者上一个PDO语句对象上发生的错误的信息（具体驱动的错误号和错误信息）
     * 注意：调用此函数后会将错误信息清空
     * @return string 返回错误信息字符串，没有错误发生时返回空字符串
     */
    public function getError(){
        $temp =  $this->error;
        $this->error = null;
        return $temp;
    }

    /**
     * 清除错误标记以进行下一次查询
     * @param string $error 错误信息
     * @return void
     */
    public function setError($error){
        $this->error = $error;
    }

    /**
     * 设置PDO对象上发生的错误
     * [
     *      0   => SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
     *      1   => Driver-specific error code.
     *      2   => Driver-specific error message.
     * ]
     * If the SQLSTATE error code is not set or there is no driver-specific error,
     * the elements following element 0 will be set to NULL .
     * @param null|string $errorInfo 设置错误信息，未设置时自动获取
     * @return bool 返回true表示发生了错误并成功设置错误信息，返回false表示模块未捕捉到错误
     */
    private function setPdoError($errorInfo=null){
        null === $errorInfo and $errorInfo = Dao::fetchPDOError($this->driver);
    }



    /**
     * 设置PDOStatement上设置的错误
     * @param string|null $errorString
     * @return bool
     */
    public function setPdoStatementError($errorString=null){
        null === $errorString and $errorString = Dao::fetchStatementError($this->curStatement);
        return ($this->error = $errorString)===null?false:true;
    }



/****************************** 事务功能 ***************************************************************************************

    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction(){
        return $this->driver->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(){
        return $this->driver->commit();
    }
    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack(){
        return $this->driver->rollBack();
    }
    /**
     * 确认是否在事务中
     * @return bool
     */
    public function inTransaction(){
        return $this->driver->inTransaction();
    }

    /**
     * 释放到数据库服务的连接，以便发出其他 SQL 语句(新的参数绑定)，使得该SQL语句处于一个可以被再次执行的状态
     * 当上一个执行的 PDOStatement 对象仍有未取行时，此方法对那些不支持再执行一个 PDOStatement 对象的数据库驱动非常有用。
     * 如果数据库驱动受此限制，则可能出现失序错误的问题
     * PDOStatement::Cursor() 要么是一个可选驱动的特有方法（效率最高）来实现，要么是在没有驱动特定的功能时作为一般的PDO 备用来实现
     * <note>
     *      ① 语意上相当于下面的语句的执行结果
     *          do {
     *              while ($stmt->fetch());
     *              if (!$stmt->nextRowset()) break;
     *          } while (true);
     * </note>
     * @param PDOStatement|null $statement
     * @return bool 成功时返回 TRUE， 或者在失败时返回 FALSE
     */
    public function closeCursor($statement=null){
        isset($statement) and $this->curStatement = $statement;
        return $this->curStatement->closeCursor();
    }

    /**
     * 获取预处理语句包含的信息
     * <note>
     *      ①实际不能获取参数的值，不像文档中写的那样
     *      ②无论是否发生了错误，信息都会存在
     * </note>
     * @return string
     */
    public function getStatementParams(){
        ob_start();//开始本层次的ob缓冲区
        $this->curStatement->debugDumpParams();
        return ob_get_clean();// 相当于ob_get_contents() 和 ob_end_clean()
    }


/************************************** 驱动实现扩展方法 ******************************************************************************************/

    /**
     * 转义保留字字段名称
     * @param string $fieldname 字段名称
     * @return string
     */
    public function escape($fieldname){
        return $this->driver->escape($fieldname);
    }

/************************************** 扩展方法 ******************************************************************************************/



    /**
     * 添加数据
     * <code>
     *      $fldsMap ==> array(
     *          'fieldName' => 'fieldValue',
     *          'fieldName' => array('fieldValue',boolean),//第二个元素表示是否对字段名称进行转义
     *      );
     *
     *     $data = ['a'=>'foo','b'=>'bar'];
     *     $keys = array_keys($data);
     *     $fields = '`'.implode('`, `',$keys).'`';
     *     #here is my way
     *     $placeholder = substr(str_repeat('?,',count($keys),0,-1));
     *     $pdo->prepare("INSERT INTO `baz`($fields) VALUES($placeholder)")->execute(array_values($data));
     * </code>
     *
     * 插入数据的sql可以是：
     * ①INSERT INTO 表名称 VALUES (值1, 值2,....)
     * ②INSERT INTO table_name (列1, 列2,...) VALUES (值1, 值2,....)
     *
     * @param string $tablename
     * @param array $fieldsMap
     * @return bool 返回true或者false
     * @throws KbylinException
     */
    public function create($tablename=null,array $fieldsMap=null){
        $sql = null; //返回的SQL语句
        $inputs  = []; //输入参数列表
        $fields = $fieldsholder = '';
        foreach($fieldsMap as $fieldName=>$fieldValue){
            if(null === $fieldValue) continue; //值为null是表示不对其进行设置

            $colnm = $fieldName;//插入列的名称
            if(is_array($fieldValue)){ //不设置字段名称进行插入时$fieldName无意义
                $colnm = $fieldValue[1]?$this->escape($fieldName):$fieldName;
                $fieldValue = $fieldValue[0];
            }
            $fields .= " {$colnm} ,";
            $fieldsholder  .= " :{$fieldName} ,";
            $inputs[":{$fieldName}"] = $fieldValue;
        }

        $fields = rtrim($fields,',');
        $fieldsholder = rtrim($fieldsholder,',');

        if(empty($fields)) throw new KbylinException('No fileds setted!');
        $sql = "INSERT INTO {$tablename} ( {$fields} ) VALUES ( {$fieldsholder} );";
        return $this->prepare($sql)->execute($inputs);
    }

    /**
     * 更新数据表
     * @param string $tablename
     * @param string|array $fields
     * @param string|array $where
     * @return bool
     * @throws KbylinException
     */
    public function update($tablename, $fields, $where){
        $input_parameters = [];
        if(is_array($fields)){
            $fields = $this->translateSegments($fields,Dao::CONNECT_COMMA);

        }
        $fields = is_string($fields)?[$fields]:$this->translateSegments($fields,self::CONNECT_COMMA);
        $where  = is_string($where) ?[$where] :$this->translateSegments($where, self::CONNECT_AND);

//        dumpout($fields,$where);
        empty($fields[1]) or $input_parameters = $fields[1];
        empty($where[1]) or $input_parameters = array_merge($input_parameters,$where[1]);
        $sql = "UPDATE {$tablename} SET {$fields[0]} WHERE {$where[0]};";

//        dumpout($sql,$inputs);
        return $this->prepare($sql)->execute($input_parameters);
    }

    /**
     * 执行删除数据的操作
     * 如果不设置参数，则进行清空表的操作（谨慎使用）
     * @param string $tablename 数据表的名称
     * @param array $where 字段映射数组,显示声明为null时表示清空这张表,否则如果提供的where条件为空时会抛出异常
     * @return bool 是否成功删除
     * @throws KbylinException
     */
    public function delete($tablename,array $where=null){
        $bind = null;
        if(null === $where){
            $sql = "delete from {$tablename};";
        }else{
            $where  = $this->translateSegments($where,self::CONNECT_AND);
            if(empty($where[0])) throw new KbylinException('Where condition miss');
            $sql    = "delete from {$tablename} where {$where[0]};";
            $bind   = $where[1];
        }
        return $this->prepare($sql)->execute($bind);
    }

    /**
     * 查询表的数据
     * @param array|null|string $options 如果是字符串是代表查询这张表中的所有数据并直接返回
     * @return array|bool
     * @throws KbylinException
     */
    public function select($options=null){
        if(is_string($options)){
            $sql  = "SELECT * FROM {$options}";
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
                return $this->driver->escape($param);
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
                $temp = $this->translateSegments($components['where'],self::CONNECT_AND);
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

        if(false === $this->prepare($sql)->execute($inputs) ){
            return false;
        }
        return $this->fetchAll();
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
    private function translateField($fieldName, $fieldValue, $operator=Dao::OPERATOR_EQUAL, $escape=false){
        $bindname = null;
        if(false !== strpos($fieldName,'.')){
            //字段被制定了表的情况下
            $arr = explode('.',$fieldName);
            $bindname = ':'.array_pop($arr);
        }elseif(mb_strlen($fieldName,'utf-8') < strlen($fieldName)){//其他编码
            //中文字段的清空下取md5值作为占位符
            $bindname = ':'.md5($fieldName);
        }else{
            $bindname = ":{$fieldName}";
        }

        $sql = (self::$_conventions[self::class]['AUTO_ESCAPE_ON'] or $escape)? $this->escape($fieldName):" {$fieldName} ";
        $input_parameter = [];


        switch($operator){
            case self::OPERATOR_EQUAL:
            case self::OPERATOR_NOTEQUAL:
            case self::OPERATOR_LIKE:
            case self::OPERATOR_NOTLIKE:
                $sql .= " {$operator} {$bindname} ";
                $input_parameter[$bindname] = $fieldValue;
                break;
            case self::OPERATOR_IN:
            case self::OPERATOR_NOTIN:
                if(is_array($fieldValue)) $fieldValue = "'".implode("','",$fieldValue)."'";
                is_string($fieldValue) or KbylinException::throwing($fieldValue);
                $sql .= " {$operator} ({$fieldValue}) ";
                break;
            default:
                KbylinException::throwing($operator);
        }
        return [$sql,$input_parameter];
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
    private function translateSegments($segments, $connect=Dao::CONNECT_AND){
        $segments or KbylinException::throwing($segments,$connect);

        $sql = '';
        $bind = [];

        //元素连接
        foreach($segments as $field=> $segment){
            if(is_numeric($field)){
                //第二中情况,符合形式组成
                $result = $this->translateSegments($segment[0],$segment[1]);
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
                $operator = Dao::OPERATOR_EQUAL;

                if(is_array($segment)){
                    $escape = isset($segment[2])?$segment[2]:false;
                    $operator = isset($segment[1])?$segment[1]:Dao::OPERATOR_EQUAL;
                    $segment = $segment[0];
                }
                $rst = $this->translateField($field,trim($segment),$operator,$escape);//第一种情况一定是'='的情况
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

}