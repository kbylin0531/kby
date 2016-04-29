<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/9
 * Time: 14:24
 */
namespace System\Library;

use System\Core\Dao;

class ExtDao extends Dao{

    /**
     * 绑定一个参数到指定的变量名
     * 绑定一个PHP变量到用作预处理的SQL语句中的对应命名占位符或问号占位符。
     *      不同于 PDOStatement::bindValue() ，此变量作为引用被绑定，
     *      并只在 PDOStatement::execute() 被调用的时候才取其值
     * <note>
     *      ①如果要使用like查询，%的位置应该在变量处而非SQL语句中
     *      ②foreach ($params as $key => &$val) { $sth->bindParam($key, $val); }时正确的
     *        foreach ($params as $key => $val) { $sth->bindParam($key, $val); }会失败，因为bingParam参数二明确要求是引用变量
     *      ③在MySQL中经过绑定参数，值得类型会发生改变
     *          $active = 1;
     *          $active === 1; //is true
     *          $ps->bindParam(":active", $active, PDO::PARAM_INT);
     *          $ps->execute();
     *          $active === 1;//  will be false
     *      ④一个值对应多个位置在PHP5.2.0及之前的版本中会导致错误，在5.2.1版本之后貌似能正常工作
     *          $sql = "SELECT * FROM u WHERE a = :myValue AND d = :myValue ";
     *          $params = array("myValue" => "0");
     * </note>
     * @param int|string $parameter 参数标识符。
     *                          对于使用命名占位符的预处理语句，应是类似 :name 形式的参数名。
     *                          对于使用问号占位符的预处理语句，应是以1开始索引的参数位置。
     * @param mixed $variable 绑定到 SQL 语句参数的 PHP 变量名
     * @param int $data_type 使用 PDO::PARAM_* 常量明确地指定参数的类型。
     *                       要从一个存储过程中返回一个 INOUT 参数，需要为 data_type 参数使用按位或操作符去设置 PDO::PARAM_INPUT_OUTPUT 位。
     * @param int $length 数据类型的长度。为表明参数是一个存储过程的 OUT 参数，必须明确地设置此长度
     * @param mixed $driver_options 驱动的可选参数
     * @return bool 成功时返回 TRUE， 或者在失败时返回 FALSE。
     */
    public function bindParam($parameter, &$variable, $data_type = \PDO::PARAM_STR, $length = null, $driver_options = null){
        return $this->curStatement->bindParam($parameter,$variable,$data_type,$length,$driver_options);
    }

    /**
     * 绑定一个值到用作预处理的 SQL 语句中的对应命名占位符或问号占位符
     *  参数一和三的意义同bindParam，参数二的意义类似，只是bindValue传递的是值，而非引用
     * <note>
     *      ①由于参数二传递的是值，所以类似一下的调用可以通过，而相同的参数bindParam方法是不通过的
     *          $stmt->bindValue(":something", "bind this");
     * </note>
     * @param mixed $parameter 参数标识符。对于使用命名占位符的预处理语句，应是类似 :name 形式的参数名。对于使用问号占位符的预处理语句，应是以1开始索引的参数位置。
     * @param mixed $value
     * @param int $data_type
     * @return bool 成功时返回 TRUE， 或者在失败时返回 FALSE
     */
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR){
        return $this->curStatement->bindValue($parameter, $value, $data_type);
    }

    /**
     * 安排一个特定的变量绑定到一个查询结果集中给定的列。每次调用 PDOStatement::fetch()
     *  或 PDOStatement::fetchAll() 都将更新所有绑定到列的变量
     * <note>
     *      ①在语句执行前 PDO 有关列的信息并非总是可用，可移植的应用应在 PDOStatement::execute() 之后 调用此函数（方法）。
     *      ②但是，当使用 PgSQL 驱动 时，要想能绑定一个 LOB 列作为流，应用程序必须在调用 PDOStatement::execute() 之前调用此方法，
     *        否则大对象 OID 作为一个整数返回
     *      ③用法实例：
     *          $stmt = $dbh->prepare('SELECT name, colour, calories FROM fruit');
     *          $stmt->execute();//在execute之后、fetch之前调用
     *          $stmt->bindColumn(1, $name);
     *          $stmt->bindColumn(2, $colour);
     *          $stmt->bindColumn('calories', $cals);//通过名称绑定
     *          while ($row = $stmt->fetch(PDO::FETCH_BOUND)) {//参数传入PDO::FETCH_BOUND
     *              echo $name . "\t" . $colour . "\t" . $cals . "\n";
     *          }
     * </note>
     * @param int|string $column 结果集中的列号（从1开始索引）或列名。如果使用列名，注意名称应该与由驱动返回的列名大小写保持一致。
     * @param mixed $param 将绑定到列的 PHP 变量的引用
     * @param int  $type 通过 PDO::PARAM_* 常量指定的参数的数据类型
     * @param int  $maxlen 预分配提示
     * @param mixed $driverdata 驱动的可选参数
     * @return bool 成功时返回 TRUE， 或者在失败时返回 FALSE
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null){
        return $this->curStatement->bindColumn($column,$param,$type,$maxlen,$driverdata);
    }

    /**
     * 返回由 PDOStatement 对象代表的结果集中的列数
     * <note>
     *      ①只有在执行PDOStatement::execute()之后才能准确地获取列数，空的结果集的列数位0
     * </note>
     * @return int
     */
    public function columnCount(){
        return $this->curStatement->columnCount();
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
     * 从结果集中的下一行返回单独的一列。
     * （这样的一列返回后，结果集中的指针将往后移动）
     * <note>
     *      ①这个方法很有用处的是：(直接获取记录数目)
     *          $db = new PDO('mysql:host=localhost;dbname=pictures','user','password');
     *          $pics = $db->query('SELECT COUNT(id) FROM pics');
     *          $this->totalpics = $pics->fetchColumn();
     *          $db = null; // 释放PDO等对象使其等待回收
     * </note>
     * @param int $column_number 列的索引，默认是第一列
     * @return string 从结果集中的下一行返回单独的一列，如果没有了，则返回 FALSE
     */
    public function fetchColumn($column_number = 0){
        return $this->curStatement->fetchColumn($column_number);
    }

    /**
     * 获取下一行并作为一个对象返回
     * 适合做框架中的Model类
     * 说明：获取下一行并作为一个对象返回。此函数（方法）是使用 PDO::FETCH_CLASS 或 PDO::FETCH_OBJ 风格的 PDOStatement::fetch() 的一种替代
     * @param string $class_name 类的名称,默认是stdClass类
     * @param array $constructor_args 构造函数参数
     * @return bool|Object 返回一个属性名对应于列名的所要求类的实例， 或者在失败时返回 FALSE
     */
    public function fetchObject($class_name = 'stdClass', array $constructor_args = []){
        return $this->curStatement->fetchObject($class_name,$constructor_args);
    }


}