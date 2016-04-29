<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 2015/8/31
 * Time: 15:37
 */
class NonSafeAction extends CommonAction{
    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");
    }

    public function findPenaltyClausesALL(){
        if($this->_hasJson){
            $data = $this->model->sqlQuery("select * from MORAL_SORT WHERE MORAL_TYPE=:MTYPE", array(":MTYPE"=>$_REQUEST["MTYPE"]));
            $this->ajaxReturn($data, "JSON");
        }
    }
}