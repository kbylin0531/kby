<?php
/**
 * 工作当量-参数设置
 * User: shencp
 * Date: 15-05-18
 * Time: 下午13:07
 */
class SettingAction extends RightAction {
    private $model;
    //构造
    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");
    }
    //课程参数设置-列表
    public function courseParam(){
        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,COURSENO,COURSENAME,SCHOOL",$_REQUEST,"%");
            $arr = array("total"=>0, "rows"=>array());
            $sql = $this->model->getSqlMap("Workload/Setting/Q_courseList.sql");
            $count = $this->model->sqlCount($sql,$bind,true);
            $arr["total"] = intval($count);
            if($arr["total"] > 0){
                $sql = $this->model->getPageSql($sql." ORDER BY C.COURSENO",null, $this->_pageDataIndex, $this->_pageSize);
                $arr["rows"] = $this->model->sqlQuery($sql,$bind);
            }
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        //开课学院
        $this->assign("school",M("schools")->select());
        $this->display();
    }
    //类型设置-列表
    public function courseType(){
        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,COURSENO,COURSENAME,SCHOOL",$_REQUEST,"%");
            $arr = array("total"=>0, "rows"=>array());
            $sql = $this->model->getSqlMap("Workload/Setting/Q_courseList.sql");
            if($_POST["TYPE"]!="%"){
                $sql.=" AND T.WORKTYPE_CODE LIKE :TYPE";
                $bind[":TYPE"]=$_POST["TYPE"];
            }
            $count = $this->model->sqlCount($sql,$bind,true);
            $arr["total"] = intval($count);
            if($arr["total"] > 0){
                $sql = $this->model->getPageSql($sql." ORDER BY C.COURSENO",null, $this->_pageDataIndex, $this->_pageSize);
                $arr["rows"] = $this->model->sqlQuery($sql,$bind);
            }
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        //开课学院
        $this->assign("school",M("schools")->select());
        $this->assign("type",$this->model->sqlQuery("select worktype_code code,worktype_name name from workloadtype"));
        $this->display();
    }
    //获取教学工作代码
    public function getWorkCode(){
        $sql="select worktype_code code,worktype_name name from workloadtype";
        $data=$this->model->sqlQuery($sql);
        echo json_encode($data);
    }
    //更新设置-共用
    public function updateParam(){
        $num=0;
        $this->model->startTrans();
        if($_POST["update"] == "courseParam"){
            //课程参数设置更新
            foreach($_POST["list"] as $val){
                $row=$this->model->sqlExecute("UPDATE COURSES SET STANDARD=:STANDARD WHERE COURSENO=:COURSENO",array(":STANDARD"=>$val["STANDARD"],":COURSENO"=>$val["COURSENO"]));
                if(!$row) return;
                $count = $this->model->sqlCount("SELECT COUNT(*) FROM WORKLOADPARAM WHERE COURSENO=:COURSENO",array(":COURSENO"=>$val["COURSENO"]));
                if(intval($count) > 0){
                    $sql="UPDATE WORKLOADPARAM SET HOURS=:HOURS,WEEKS=:WEEKS WHERE COURSENO=:COURSENO;";
                    $bind=array(":HOURS"=>$val["HOURS"],":WEEKS"=>$val["WEEKS"],":COURSENO"=>$val["COURSENO"]);
                }else{
                    $sql="INSERT INTO WORKLOADPARAM(COURSENO,HOURS,WEEKS) VALUES(:COURSENO,:HOURS,:WEEKS)";
                    $bind=array(":COURSENO"=>$val["COURSENO"],":HOURS"=>$val["HOURS"],":WEEKS"=>$val["WEEKS"]);
                }
                $row=$this->model->sqlExecute($sql,$bind);
                if($row) $num++;
            }
        }else{
            //类型设置更新
            foreach($_POST["list"] as $val){
                $row=$this->model->sqlExecute("UPDATE COURSES SET STANDARD=:STANDARD WHERE COURSENO=:COURSENO",array(":STANDARD"=>$val["STANDARD"],":COURSENO"=>$val["COURSENO"]));
                if(!$row) return;
                if(trim($val["WORKTYPENAME"])=="" || strlen($val["WORKTYPENAME"]) > 3){
                    $num++;
                    continue;
                }
                $count = $this->model->sqlCount("SELECT COUNT(*) FROM WORKLOADPARAM WHERE COURSENO=:COURSENO",array(":COURSENO"=>$val["COURSENO"]));
                if(intval($count) > 0){
                    $sql="UPDATE WORKLOADPARAM SET WORKTYPE=:WORKTYPE WHERE COURSENO=:COURSENO;";
                    $bind=array(":WORKTYPE"=>$val["WORKTYPENAME"],":COURSENO"=>$val["COURSENO"]);
                }else{
                    $sql="INSERT INTO WORKLOADPARAM(COURSENO,WORKTYPE) VALUES(:COURSENO,:WORKTYPE)";
                    $bind=array(":COURSENO"=>$val["COURSENO"],":WORKTYPE"=>$val["WORKTYPENAME"]);
                }
                $row=$this->model->sqlExecute($sql,$bind);
                if($row) $num++;
            }
        }
        if($num == count($_POST["list"])){
            $this->model->commit();
            echo true;
        }else {
            $this->model->rollback();
            echo false;
        }
    }
    //时间设置
    public function time(){
        if(isset($_POST['bind'])){
            $this->model->sqlExecute('update workload_applydate set status=0');
            $this->model->sqlExecute($this->model->getSqlMap("Workload/Setting/insertTime.SQL"),$_POST['bind']);
            exit;
        }
        $this->getdateinfo();
        $this->display();
    }
    //获取认定时间
    public function getdateinfo(){
        $timearr=$this->model->sqlQuery('select status,year,term,convert(varchar(20),begintime,20) as begintime,convert(varchar(20),endtime,20) as endtime from workload_applydate where status=1');
        if(count($timearr)==1){
            $this->assign('timearr',$timearr[0]);
        }else if(count($timearr)==0){//todo:全部关闭
            $timearr=$this->model->sqlFind('select top 1 status,year,term,convert(varchar(20),begintime,20) as begintime,convert(varchar(20),endtime,20) as endtime from workload_applydate order by applydate_id desc');
            $this->assign('timearr',$timearr);      //todo:取最后一次操作的
        }
    }
    //教师工作量要求-列表
    public function teacherList(){
        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,TEACHERNO,NAME,SCHOOL",$_REQUEST,"%");
            $arr = array("total"=>0, "rows"=>array());
            $sql = $this->model->getSqlMap("Workload/Setting/Q_teacherList.sql");

            $count = $this->model->sqlCount($sql,$bind,true);
            $arr["total"] = intval($count);
            if($arr["total"] > 0){
                $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
                $arr["rows"] = $this->model->sqlQuery($sql,$bind);
            }
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        //开课学院
        $this->assign("school",M("schools")->select());
        $this->display();
    }
    //初始化教师工作量要求
    public function initWorkload(){
        set_time_limit(0);
        $num=0;
        $this->model->startTrans();
        $sql="DELETE WORKLOADTEACHERS WHERE [YEAR]=:YEAR AND TERM=:TERM";
        $bind=array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"]);
        if($_POST["SCHOOL"]!="A1"){
            $sql.=" AND TEACHERNO IN (SELECT TEACHERNO FROM TEACHERS WHERE SCHOOL=:SCHOOL)";
            $bind[":SCHOOL"]=$_POST["SCHOOL"];
        }
        $this->model->sqlExecute($sql,$bind);
        $sql="SELECT TEACHERNO,CASE WHEN TYPE='A' THEN 360 ELSE 0 END AS WORKLOAD FROM TEACHERS";
        $bind=array();
        if($_POST["SCHOOL"]!="A1"){
            $sql.=" WHERE SCHOOL=:SCHOOL";
            $bind[":SCHOOL"]=$_POST["SCHOOL"];
        }
        $data=$this->model->sqlQuery($sql,$bind);
        if(count($data) > 0){
            foreach($data as $t){
                $row=$this->model->sqlExecute("INSERT INTO WORKLOADTEACHERS(YEAR,TERM,TEACHERNO,WORKLOAD) VALUES(:YEAR,:TERM,:TEACHERNO,:WORKLOAD)",
                    array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],":TEACHERNO"=>$t["TEACHERNO"],":WORKLOAD"=>$t["WORKLOAD"]));
                if($row) $num++;
            }
        }
        if($num == count($data)){
            $this->model->commit();
            echo true;
        }else {
            $this->model->rollback();
            echo false;
        }
    }
    //更新教师工作量要求信息
    public function updateWorkload(){
        $num=0;
        $this->model->startTrans();
        foreach($_POST["list"] as $val){
            $row=$this->model->sqlExecute("UPDATE WORKLOADTEACHERS SET WORKLOAD=:WORKLOAD,REMARK=:REMARK WHERE ID=:ID",
                array(":WORKLOAD"=>$val["WORKLOAD"],":REMARK"=>$val["REMARK"],":ID"=>$val["ID"]));
            if($row) $num++;
        }
        if($num == count($_POST["list"])){
            $this->model->commit();
            echo true;
        }else {
            $this->model->rollback();
            echo false;
        }
    }
}