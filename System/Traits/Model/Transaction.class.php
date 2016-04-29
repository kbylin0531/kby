<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2016/3/19
 * Time: 9:58
 */
namespace System\Traits\Model;
use System\Core\Dao;
use System\Core\Exception\TransactionException;
use System\Core\KbylinException;

/**
 * Class Transaction 事物相关
 * @package System\Traits\Model
 */
trait Transaction {

    /**
     * 获取调用模型内部用于事务的Dao实例，也可以自定义获取
     * 注：
     *  之所以用getTransactionDao取代getDao这样的名称，
     *  是因为getDao可能出现在其他的trait中，可能导致方法冲突的问题
     * @param int $index
     * @return Dao
     * @throws KbylinException
     */
    protected function getTransactionDao($index=null){
        isset($this->dao) and $this->dao = Dao::getInstance($index);
        return $this->dao;
    }

    /**
     * 开启事务
     * @return bool
     */
    protected function beginTransaction(){
        return $this->getTransactionDao()->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(){
        return $this->getTransactionDao()->commit();
    }
    /**
     * 回滚事务
     * @return bool
     */
    public function rollBack(){
        return $this->getTransactionDao()->rollBack();
    }
    /**
     * 确认是否在事务中
     * @return bool
     */
    public function inTransaction(){
        return $this->getTransactionDao()->inTransaction();
    }

    /**
     * 批处理执行SQL语句
     * 批处理的指令都认为是execute操作
     * 注：
     *  如果其中的一条语句执行出现错误就会导致整体返回false
     *  如果其中的一条影响记录的行数为0，是否返回决定于参数三的布尔值
     * @access public
     * @param array $sqls  SQL指令集合
     * @param array $input_parameters 输入参数
     * @param bool|false $faliedback_Immediately 是否只要一条语句执行错误就立即返回false，默认为不执行这条策略
     * @return boolean 是否执行成功
     * @throws TransactionException
     */
    public function executeSqlInPatch(array $sqls, array $input_parameters, $faliedback_Immediately=false){
        $dao = $this->getTransactionDao();
        if($dao->beginTransaction()) throw new TransactionException('开启事务失败！');
        foreach($sqls as $index=>$sql){
            $input_param = $input_parameters?isset($input_parameters[$index])?$input_parameters[$index]:reset($input_parameters):null;
            $rst = $dao->prepare($sql)->execute($input_param);
            if($faliedback_Immediately and false === $rst){
                $dao->rollBack();
                return false;
            }
        }
        $dao->commit();
        return true;
    }

}