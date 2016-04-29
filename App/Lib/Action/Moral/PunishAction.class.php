<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 2015/8/27
 * Time: 14:09
 */
class PunishAction extends RightAction{
    private $model;
    protected $message = array("type"=>"info","message"=>"","dbError"=>"");
    protected $YearTerm;

    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");

        $this->YearTerm = $this->model->sqlFind("select * from YEAR_TERM where [TYPE]='S'");
        $this->assign("YearTerm", $this->YearTerm);
    }

    public function qlist(){
        $user = session("S_USER_INFO");
        $this->assign('isdean',$this->isDeanTeahcer());
        $classes = $this->getChargeClasses($user["TEACHERNO"]);
        //$classes = $this->getChargeClasses("2011205");
        if($this->_hasJson){
            $this->ajaxReturn($this->getPage($_REQUEST["YEAR"], $_REQUEST["TERM"], $_REQUEST["SNAME"], $_REQUEST["SNO"]));
        }

        $this->assign("classes", $classes);
        $this->display("qlist");
    }

    public function save(){
        if($this->_hasJson){
            //todo 检查参数
            if(VarIsNotEmpty("YEAR,TERM,STUDENTNO,DETAIL_ID,REM")==false){
                $this->message["type"] = "error";
                $this->message["message"] = "参数错误非法提交！";
                $this->ajaxReturn($this->message, "JSON");
                exit();
            }

            //todo 检查学生是否存在
            $data = $this->model->sqlCount("select count(*) from STUDENTS WHERE STUDENTNO=:STUDENTNO",array(":STUDENTNO"=>$_REQUEST["STUDENTNO"]));
            if(!$data){
                $this->message["type"] = "error";
                $this->message["message"] = "学号[".$_REQUEST["STUDENTNO"]."]没有找到对应学生信息！";
                $this->ajaxReturn($this->message, "JSON");
                exit();
            }

            //todo 检查扣分规则是否存在
            $data = $this->model->sqlFind("select * from MORAL_SORT WHERE CODE=:CODE",array(":CODE"=>$_REQUEST["DETAIL_ID"]));
            if(!$data){
                $this->message["type"] = "error";
                $this->message["message"] = "处分规则[".$_REQUEST["DETAIL_ID"]."]没有找到对应信息！";
                $this->ajaxReturn($this->message, "JSON");
                exit();
            }

            $user = session("S_USER_INFO");
            $bind = $this->model->getBind("YEAR,TERM,STUDENTNO,DETAIL_ID,DETAILS,DETAIL_VALUE,TEACHER_NAME,REM",$_REQUEST);
            $bind[":DETAILS"] = $data["NAME"];
            $bind[":DETAIL_VALUE"] = 0;
            $bind[":TEACHER_NAME"] = $user["NAME"];
            if($_REQUEST["ID"]){ //修改
                $bind[":ID"] = $_REQUEST["ID"];
                $data = $this->model->sqlExecute("update MORAL_INFO set [YEAR]=:YEAR,TERM=:TERM,STUDENTNO=:STUDENTNO,DETAIL_ID=:DETAIL_ID,DETAILS=:DETAILS,DETAIL_VALUE=:DETAIL_VALUE,TEACHER_NAME=:TEACHER_NAME,REM=:REM WHERE MORAL_TYPE=3 and STATUS=0 and ID=:ID",$bind);
                if(!$data){
                    $this->message["type"] = "error";
                    $this->message["message"] = "修改学生处分记录时发生错误！";
                }else{
                    $this->message["message"] = "成功修改一条学生处分信息!";
                }
            }else{ //增加
                $data = $this->model->sqlExecute("insert into MORAL_INFO ([YEAR],TERM,STUDENTNO,DETAIL_ID,DETAILS,DETAIL_VALUE,MORAL_TYPE,TEACHER_NAME,STATUS,REM) VALUES (:YEAR,:TERM,:STUDENTNO,:DETAIL_ID,:DETAILS,:DETAIL_VALUE,3,:TEACHER_NAME,0,:REM)", $bind);
                if(!$data){
                    $this->message["type"] = "error";
                    $this->message["message"] = "插入学生处分记录时发生错误！";
                }else{
                    $this->message["message"] = "成功新增一条学生处分信息!";
                }
            }
            $this->ajaxReturn($this->message, "JSON");
        }
    }

    public function del(){
        if($this->_hasJson){
            //todo 这里其实要检测教师是否有删除权利(本班级学生)
            if($_REQUEST["IDS"] && is_array($_REQUEST["IDS"])){
                $data = $this->model->sqlExecute("delete from MORAL_INFO where MORAL_TYPE=3 and STATUS=0 and ID IN (".@implode(",",$_REQUEST["IDS"]).")");
                $this->message["message"] = intval($data)."条记录删除成功！";
            }else $this->message["message"] = "没有指定任一条记录进行删除！";
            $this->ajaxReturn($this->message, "JSON");
        }
    }

    public function vlist(){
        if($this->_hasJson){
            $this->ajaxReturn($this->getVerifyPage($_REQUEST["YEAR"], $_REQUEST["TERM"], $_REQUEST["SNAME"], $_REQUEST["SNO"], $_REQUEST["STATUS"]));
        }
        return $this->display("vlist");
    }

    public function verify(){
        if($this->_hasJson){
            trace($_REQUEST,".................");
            if(!$_REQUEST["STATUS"] || !$_REQUEST["IDS"] || !is_array($_REQUEST["IDS"])){
                $this->message["type"] = "error";
                $this->message["message"] = "参数错误，非法提交！";
            }else{
                $data = $this->model->sqlExecute("update MORAL_INFO set STATUS=:STATUS WHERE MORAL_TYPE=3 AND ID IN (".@implode(",",$_REQUEST['IDS']).")",array(":STATUS"=>intval($_REQUEST["STATUS"])));
                $this->message["message"] = intval($data)."条记录操作成功！";
            }
            $this->ajaxReturn($this->message,"JSON");
        }
    }

    /**
     * 一个班级只能有一个斑主任，一个教师只能任一个班级的班主任
     * @param $teacherNo
     * @return bool
     */
    private function getChargeClasses($teacherNo){
        $data = $this->model->sqlQuery("select * from CLASSES WHERE CHARGE_TEACHERNO=:TEACHERNO",array(":TEACHERNO"=>trim($teacherNo)));
        if($data===false) return false;
        elseif(count($data)==1) return $data[0];
        return false;
    }

    /**
     * 获得扣分列表
     * @param $year
     * @param $term
     * @param $sname
     * @param $sno
     */
    private function getPage($year, $term, $sname, $sno){
        $json = array("total"=>0, "rows"=>array());
        $sql = "from MORAL_INFO i LEFT JOIN STUDENTS s ON (i.STUDENTNO=s.STUDENTNO) LEFT JOIN CLASSES c ON (s.CLASSNO=c.CLASSNO)";
        $where = "i.MORAL_TYPE=3 and i.YEAR=:YEAR and i.TERM=:TERM and s.name LIKE :SNAME and s.STUDENTNO LIKE :SNO";

        $bind = array(":YEAR"=>$year,":TERM"=>$term,":SNAME"=>$sname,":SNO"=>$sno);
        $count = $this->model->sqlCount("select count(*) ".$sql." where ".$where, $bind);
        $json["total"] = intval($count);
        if($count>0){
            $sql = $this->model->getPageSql("select i.*,s.NAME,s.CLASSNO,c.CLASSNAME ".$sql." where ".$where." order by YEAR,TERM,CLASSNO,ID", null, $this->_pageDataIndex, $this->_pageSize);
            $json["rows"] = $this->model->sqlQuery($sql, $bind);
        }
        return $json;
    }

    private function getVerifyPage($year, $term, $sname, $sno, $status){
        $json = array("total"=>0, "rows"=>array());
        $sql = "from MORAL_INFO i LEFT JOIN STUDENTS s ON (i.STUDENTNO=s.STUDENTNO) LEFT JOIN CLASSES c ON (s.CLASSNO=c.CLASSNO)";
        $where = "i.MORAL_TYPE=3 and i.STATUS=:STATUS and i.YEAR=:YEAR and i.TERM=:TERM and s.name LIKE :SNAME and s.STUDENTNO LIKE :SNO";

        $bind = array(":STATUS"=>$status,":YEAR"=>$year,":TERM"=>$term,":SNAME"=>$sname,":SNO"=>$sno);
        $count = $this->model->sqlCount("select count(*) ".$sql." where ".$where, $bind);
        $json["total"] = intval($count);
        if($count>0){
            $sql = $this->model->getPageSql("select i.*,s.NAME,s.CLASSNO,c.CLASSNAME ".$sql." where ".$where." order by YEAR,TERM,CLASSNO,ID", null, $this->_pageDataIndex, $this->_pageSize);
            $json["rows"] = $this->model->sqlQuery($sql, $bind);
        }
        return $json;
    }
}