<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 2015/8/31
 * Time: 14:52
 */
class NonSafeAction extends CommonAction{
    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");
    }

    public function findStudentsByClassNO(){
        if(VarIsNotEmpty("CLASSNO")==false) return;
        if($this->_hasJson){
            $data = $this->model->sqlQuery("select RTrim(STUDENTNO) as STUDENTNO,(RTrim([NAME]) + '('+RTrim(STUDENTNO)+')') as NAME from STUDENTS where CLASSNO=:CLASSNO ORDER BY STUDENTNO ",array(":CLASSNO"=>$_REQUEST["CLASSNO"]));
            $this->ajaxReturn($data, "JSON");
        }
    }

    public function findStudentsByName(){
        if(VarIsNotEmpty("KEYS")==false) return;
        if($this->_hasJson){
            $bind = array(":SNAME"=>$_REQUEST["KEYS"]."%",":STUDENTNO"=>$_REQUEST["KEYS"]."%");
            $data = $this->model->sqlQuery("select * from STUDENTS where [NAME] LIKE :SNAME OR STUDENTNO LIKE :STUDENTNO ORDER BY STUDENTNO",$bind);
            $this->ajaxReturn($data,"JSON");
        }
    }
}