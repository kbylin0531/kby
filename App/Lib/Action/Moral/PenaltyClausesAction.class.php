<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 2015/8/19
 * Time: 10:06
 */
class PenaltyClausesAction extends RightAction {
    private $model;
    protected $message = array("type"=>"info","message"=>"","dbError"=>"");

    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");
    }

    public function qlist(){
        if($this->_hasJson){
            $json = array("total"=>0, "rows"=>array());

            $where = " WHERE (CODE LIKE :CODE OR [NAME] LIKE :NAME)";
            $bind = array(":CODE"=>"%",":NAME"=>"%");
            $key = $_REQUEST["queryStr"];
            if($key) $bind = array(":CODE"=>"%".$key."%",":NAME"=>"%".$key."%");
            if($_REQUEST["moralType"]) {
                $bind[":MORAL_TYPE"] = intval($_REQUEST["moralType"]);
                $where .= " and MORAL_TYPE=:MORAL_TYPE";
            }


            $count = $this->model->sqlCount("select count(*) from MORAL_SORT ".$where, $bind);
            $json["total"] = intval($count);
            if($json["total"]>0){
                $sql = $this->model->getPageSql("select * from MORAL_SORT ".$where." order by MORAL_TYPE,CODE",null, $this->_pageDataIndex, $this->_pageSize);
                $json["rows"] = $this->model->sqlQuery($sql, $bind);
            }
            $this->ajaxReturn($json,"JSON");
            exit;
        }
        $this->display("qlist");
    }

    function save(){
        if($this->_hasJson){
            $count=0;
            if($_REQUEST["DATAS"] && is_array($_REQUEST["DATAS"])){
                foreach($_REQUEST["DATAS"] as $row){
                    if(VarIsNotEmpty("CODE,NAME,VALUE,MORAL_TYPE",$row)==false) continue;
                    $data = $this->model->sqlCount("select count(*) from MORAL_SORT WHERE CODE=:CODE",array(":CODE"=>$row["CODE"]));
                    if($data===false) continue;

                    if($data==0) {
                        $bind = $this->model->getBind("CODE,NAME,VALUE,MORAL_TYPE",$row);
                        $this->model->sqlExecute("insert into MORAL_SORT (CODE,[NAME],[VALUE],MORAL_TYPE) VALUES (:CODE,:NAME,:VALUE,:MORAL_TYPE)", $bind);
                    }else {
                        $bind = $this->model->getBind("NAME,VALUE,MORAL_TYPE,CODE",$row);
                        $this->model->sqlExecute("UPDATE MORAL_SORT set [NAME]=:NAME,[VALUE]=:VALUE,MORAL_TYPE=:MORAL_TYPE WHERE CODE=:CODE", $bind);
                    }
                    $count++;
                }
            }
            $this->message["message"] = $count."条数据处理成功！";
        }else{
            $this->message["type"] = "error";
            $this->message["message"] = "参数错误，非法操作！";
        }
        $this->ajaxReturn($this->message, "JSON");
        exit;
    }

    function del(){
        if($this->_hasJson){
            if(isset($_REQUEST["CODES"]) && is_array($_REQUEST["CODES"])){
                foreach($_REQUEST["CODES"] as $code){
                    $bind = array(":CODE"=>$code);
                    $this->model->sqlExecute("delete from MORAL_SORT WHERE CODE=:CODE",$bind);
                }
                $this->message["message"] = "指定的记录删除成功！";
            }else {
                $this->message["type"] = "error";
                $this->message["message"] = "没有指定任一记录！";
            }
        }else{
            $this->message["type"] = "error";
            $this->message["message"] = "参数错误，非法操作！";
        }
        $this->ajaxReturn($this->message, "JSON");
        exit;
    }
}