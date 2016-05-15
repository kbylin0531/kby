<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2016/3/27
 * Time: 14:28
 */
namespace Application\Test\Controller;
use System\Core\Dao;

class DaoTest {

    /**
     * @var Dao
     */
    protected $dao = null;


    public function index(){
        $this->dao = Dao::getInstance();

        dump($this->dao->query('select  top 100 * from students WHERE  studentno like :studentno;',[':studentno'=> '20150110%']));
        dump($this->dao->exec('insert '));
//        $rst = $this->testBaiscQuery();
//        ($rst = $this->testBaiscErrorQuery()) === false and  $rst = $this->dao->getError();
//        $rst = $this->testComplexQuery();
//        ($rst = $this->testComplexQueryError()) === false and $rst = $this->dao->getError();
//        $rst = $this->testbasicExec();
//        ($rst = $this->testbasicExecError()) === false and $rst = $this->dao->getError();
//        $rst = $this->testComplexExec();
//        ($rst = $this->testComplexExecError1()) === false and $rst = $this->dao->getError();
//        ($rst = $this->testComplexExecError2()) === false and $rst = $this->dao->getError();


//        $rst = $this->testPrepare();
//        $rst = $this->testExecute();
//        $rst = $this->testEscape();
    }

}