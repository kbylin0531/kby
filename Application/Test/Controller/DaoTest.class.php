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
//        dump(Dao::getAvailableDrivers());

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
        dump(isset($rst)?$rst:null);
    }


    //测试Dao的prepare方法
    public function testPrepare(){
        $sql = 'SELECT `name`,title from ot_action;';
        $this->dao->prepare($sql);
        $sql = 'SELECT `name`,titlXXXXe from ot_action;';
        return $this->dao->prepare($sql);
    }

    //测试Dao的escape方法
    public function testEscape(){
        return $this->dao->escape('SSS');
    }

    //测试Dao的execute方法
    public function testExecute(){
        $sql = '
INSERT INTO `onethink`.`ot_action` ( `name`, `title`, `remark`, `rule`, `log`, `type`, `status`, `update_time`) VALUES
(:name,:title,:remark,:rule,:log,:type,:status,:update_time);';
        if(null === $this->dao->prepare($sql)){
            return $this->dao->getError();
        }else{
            $rst = $this->dao->execute([
                ':name' => 'update_menu',
//                ':title' => '更新菜单',
                ':remark' => '新增或修改或删除菜单',
                ':rule' => '',
                ':log' => '',
                ':type' => '1',
                ':status' => '1',
                ':update_time' => '1383296392',
            ]);
            return false === $rst ? $this->dao->getError() : $rst;
        }
    }

    //测试Dao的query方法
    public function testBaiscQuery(){
        $sql = 'SELECT `name`,title from ot_action;';
        return $this->dao->query($sql);
    }
    public function testBaiscErrorQuery(){
        $sql = 'SELECT `name`,title from ot_action_aa;';
        return $this->dao->query($sql);
    }
    public function testComplexQuery(){
        $sql = 'SELECT `name`,title,remark from ot_action WHERE remark like :remark;';
        return $this->dao->query($sql,[':remark'    => '%积分%']);
    }
    public function testComplexQueryError(){
        $sql = 'SELECT `name`,title,remark from ot_actionaa WHERE remark like :remark;';
        return $this->dao->query($sql,[':remark'    => '%积分%']);
    }

    //测试Dao的exec方法
    public function testbasicExec(){
        $sql = '
INSERT INTO `onethink`.`ot_action` ( `name`, `title`, `remark`, `rule`, `log`, `type`, `status`, `update_time`) VALUES
(\'update_menu\', \'更新菜单\', \'新增或修改或删除菜单\', \'\', \'\', \'1\', \'1\', \'1383296392\');';
        return $this->dao->exec($sql);
    }
    public function testbasicExecError(){
        $sql = '
INSERT INTO `onethink`.`ot_actionsssssssss` ( `name`, `title`, `remark`, `rule`, `log`, `type`, `status`, `update_time`) VALUES
(\'update_menu\', \'更新菜单\', \'新增或修改或删除菜单\', \'\', \'\', \'1\', \'1\', \'1383296392\');';
        return $this->dao->exec($sql);
    }
    public function testComplexExec(){
        $sql = '
INSERT INTO `onethink`.`ot_action` ( `name`, `title`, `remark`, `rule`, `log`, `type`, `status`, `update_time`) VALUES
(:name,:title,:remark,:rule,:log,:type,:status,:update_time);';
        return $this->dao->exec($sql,[
            ':name' => 'update_menu',
            ':title' => '更新菜单',
            ':remark' => '新增或修改或删除菜单',
            ':rule' => '',
            ':log' => '',
            ':type' => '1',
            ':status' => '1',
            ':update_time' => '1383296392',
        ]);
    }
    public function testComplexExecError1(){
        $sql = '
INSERT INTO `onethink`.`ot_actionXXXXXX` ( `name`, `title`, `remark`, `rule`, `log`, `type`, `status`, `update_time`) VALUES
(:name,:title,:remark,:rule,:log,:type,:status,:update_time);';
        return $this->dao->exec($sql,[
            ':name' => 'update_menu',
            ':title' => '更新菜单',
            ':remark' => '新增或修改或删除菜单',
            ':rule' => '',
            ':log' => '',
            ':type' => '1',
            ':status' => '1',
            ':update_time' => '1383296392',
        ]);
    }
    public function testComplexExecError2(){
        $sql = '
INSERT INTO `onethink`.`ot_action` ( `name`, `title`, `remark`, `rule`, `log`, `type`, `status`, `update_time`) VALUES
(:name,:title,:remark,:rule,:log,:type,:status,:update_time);';
        return $this->dao->exec($sql,[
            ':name' => 'update_menu',
            ':title' => '更新菜单',
            ':remark' => '新增或修改或删除菜单',
//            ':rule' => '',
            ':log' => '',
            ':type' => '1',
            ':status' => '1',
            ':update_time' => '1383296392',
        ]);
    }

}