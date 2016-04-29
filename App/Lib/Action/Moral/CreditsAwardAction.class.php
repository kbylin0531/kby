<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 2015/8/27
 * Time: 14:09
 */
class CreditsAwardAction extends RightAction{
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
        $classes = $this->getChargeClasses($user["TEACHERNO"]);
        //$classes = $this->getChargeClasses("2011205");
        if($this->_hasJson){
            $this->ajaxReturn($this->getPage($_REQUEST["YEAR"], $_REQUEST["TERM"], $_REQUEST["SNAME"], $_REQUEST["SNO"]));
        }

        $this->assign("classes", $classes);
        return $this->display("qlist");
    }

    public function save(){
        if($this->_hasJson){
            //todo 检查参数
            if(VarIsNotEmpty("YEAR,TERM,STUDENTNO,DETAILS,DETAIL_VALUE")==false){
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

            $user = session("S_USER_INFO");
            $bind = $this->model->getBind("YEAR,TERM,STUDENTNO,DETAILS,DETAIL_VALUE,TEACHER_NAME",$_REQUEST);
            $bind[":DETAIL_VALUE"] = abs($bind[":DETAIL_VALUE"]);
            $bind[":TEACHER_NAME"] = $user["NAME"];
            if($_REQUEST["ID"]){ //修改
                $bind[":ID"] = $_REQUEST["ID"];
                $data = $this->model->sqlExecute("update MORAL_INFO set [YEAR]=:YEAR,TERM=:TERM,STUDENTNO=:STUDENTNO,DETAILS=:DETAILS,DETAIL_VALUE=:DETAIL_VALUE,TEACHER_NAME=:TEACHER_NAME WHERE ID=:ID and MORAL_TYPE=2",$bind);
                if(!$data){
                    $this->message["type"] = "error";
                    $this->message["message"] = "修改学生加分记录时发生错误！";
                }else{
                    $this->message["message"] = "成功修改一条学生加分信息!";
                }
            }else{ //增加
                $data = $this->model->sqlExecute("insert into MORAL_INFO ([YEAR],TERM,STUDENTNO,DETAILS,DETAIL_VALUE,MORAL_TYPE,TEACHER_NAME,STATUS) VALUES (:YEAR,:TERM,:STUDENTNO,:DETAILS,:DETAIL_VALUE,2,:TEACHER_NAME,1)", $bind);
                if(!$data){
                    $this->message["type"] = "error";
                    $this->message["message"] = "插入学生加分记录时发生错误！";
                }else{
                    $this->message["message"] = "成功新增一条学生加分信息!";
                }
            }
            $this->ajaxReturn($this->message, "JSON");
        }
    }

    public function del(){
        if($this->_hasJson){
            //todo 这里其实要检测教师是否有删除权利(本班级学生)
            if($_REQUEST["IDS"] && is_array($_REQUEST["IDS"])){
                $data = $this->model->sqlExecute("delete from MORAL_INFO where MORAL_TYPE=2 and ID IN (".@implode(",",$_REQUEST["IDS"]).")");
                $this->message["message"] = intval($data)."条记录删除成功！";
            }else $this->message["message"] = "没有指定任一条记录进行删除！";
            $this->ajaxReturn($this->message, "JSON");
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
        $where = "i.MORAL_TYPE=2 and i.YEAR=:YEAR and i.TERM=:TERM and s.name LIKE :SNAME and s.STUDENTNO LIKE :SNO";

        $bind = array(":YEAR"=>$year,":TERM"=>$term,":SNAME"=>$sname,":SNO"=>$sno);
        $count = $this->model->sqlCount("select count(*) ".$sql." where ".$where, $bind);
        $json["total"] = intval($count);
        if($count>0){
            $sql = $this->model->getPageSql("select i.*,s.NAME,s.CLASSNO,c.CLASSNAME ".$sql." where ".$where." order by YEAR,TERM,CLASSNO,ID", null, $this->_pageDataIndex, $this->_pageSize);
            $json["rows"] = $this->model->sqlQuery($sql, $bind);
        }
        return $json;
    }
}