<?php
/**
 * 工作当量-计算
 * User: shencp
 * Date: 15-05-20
 * Time: 下午16:27
 */
class CalculateAction extends RightAction{
    private $model;
    //构造
    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");
    }
    //总量核定-列表
    public function totalCheck(){
        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,COURSENO,COURSENAME,SCHOOL,TYPE",$_REQUEST,"%");
            $arr = array("total"=>0, "rows"=>array());
            $sql = $this->model->getSqlMap("Workload/Calculate/Q_workloadList.sql");
            $count = $this->model->sqlCount($sql,$bind,true);
            $arr["total"] = intval($count);
            if($arr["total"] > 0){
                $sql .=" ORDER BY COURSENO";
                $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
                $arr["rows"] = $this->model->sqlQuery($sql,$bind);
            }
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        //开课学院
        $this->assign("school",M("schools")->select());
        //工作量类型
        $this->assign("type",$this->model->sqlQuery("select worktype_code code,worktype_name name from workloadtype"));
        $this->display();
    }
    //初始化工作量
    public function initWorkload(){
        set_time_limit(0);
        $num=0;
        $bind=array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],":SCHOOL"=>$_POST["SCHOOL"]);
        $this->model->startTrans();
        //1、删除原有信息
        $sql="DELETE WORKLOADCALC WHERE [YEAR]=:YEAR AND TERM=:TERM AND COURSENO IN (SELECT COURSENO FROM COURSES WHERE SCHOOL LIKE :SCHOOL)";
        $this->model->sqlExecute($sql,$bind);
        //2、初始化课程部分参数信息
        $sql = $this->model->getSqlMap("Workload/Calculate/initWorkloadCalc.sql");
        $row=$this->model->sqlExecute($sql,$bind);
        if($row){
            //查询获得刚才初始化的信息
            $sql="SELECT ID FROM WORKLOADCALC WHERE YEAR=:YEAR AND TERM=:TERM AND COURSENO IN (SELECT COURSENO FROM COURSES WHERE SCHOOL LIKE :SCHOOL)";
            $data=$this->model->sqlQuery($sql,$bind);
            //3、根据初始化信息计算更新重复系数信息
            $sql_cfxs = $this->model->getSqlMap("Workload/Calculate/initRepeatcoeff.sql");
            foreach($data as $d){
                $row=$this->model->sqlExecute($sql_cfxs,array(":ID"=>$d["ID"]));
            }
            //4、根据初始化信息计算更新工作量信息
            $sql = $this->model->getSqlMap("Workload/Calculate/initWorkload.sql");
            $row=$this->model->sqlExecute($sql,$bind);
        }
        if($row){
            $this->model->commit();
            echo true;
        }else {
            $this->model->rollback();
            echo false;
        }
    }
    //更新教师工作量要求信息
    public function updateWorkCalc(){
        set_time_limit(0);
        $num=0;
        $this->model->startTrans();
        foreach($_POST["list"] as $val){
            $sql="UPDATE WORKLOADCALC SET TOTAL=:TOTAL,WEEKS=:WEEKS";
            $bind=array(":TOTAL"=>$val["TOTAL"],":WEEKS"=>$val["WEEKS"]);
            if(trim($val["WORKTYPENAME"])!="" && strlen($val["WORKTYPENAME"]) < 4){
                $sql.=",WORKTYPE=:WORKTYPE";
                $bind[":WORKTYPE"]=$val["WORKTYPENAME"];
            }
            $sql.=" WHERE ID=:ID";
            $bind[":ID"]=$val["ID"];
            $row=$this->model->sqlExecute($sql,$bind);
            if($row){
                $row=$this->model->sqlExecute($this->model->getSqlMap("Workload/Calculate/updateCoeff.sql"),array(":ID"=>$val["ID"]));
                $row=$this->model->sqlExecute($this->model->getSqlMap("Workload/Calculate/updateWorkload.sql"),array(":ID"=>$val["ID"]));
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
    //同步选课人数
    public function ajaxSynch(){
        set_time_limit(0);$num=0;
        $list=$_POST["list"];
        if(count($list) == 0){
            $bind = $this->model->getBind("YEAR,TERM,COURSENO,COURSENAME,SCHOOL,TYPE",$_REQUEST,"%");
            $sql = $this->model->getSqlMap("Workload/Calculate/Q_workloadList.sql");
            $list = $this->model->sqlQuery($sql,$bind);
        }
        $this->model->startTrans();
        $sql="UPDATE WORKLOADCALC SET ATTENDENTS=(SELECT COUNT(*) FROM R32 WHERE R32.[YEAR]=WORKLOADCALC.[YEAR] ".
            "AND R32.TERM=WORKLOADCALC.TERM AND R32.COURSENO=WORKLOADCALC.COURSENO AND R32.[GROUP]=WORKLOADCALC.[GROUP]) WHERE ID=:ID";
        foreach($list as $val){
            $row=$this->model->sqlExecute($sql,array(":ID"=>$val["ID"]));
            if($row){
                $row=$this->model->sqlExecute($this->model->getSqlMap("Workload/Calculate/updateCoeff.sql"),array(":ID"=>$val["ID"]));
                $row=$this->model->sqlExecute($this->model->getSqlMap("Workload/Calculate/updateWorkload.sql"),array(":ID"=>$val["ID"]));
                if($row) $num++;
            }
        }
        if($num == count($list)){
            $this->model->commit();
            echo true;
        }else {
            $this->model->rollback();
            echo false;
        }
    }
    //教师分配-列表
    public function allocation(){
        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,COURSENO,COURSENAME,SCHOOL,TYPE",$_REQUEST,"%");
            $arr = array("total"=>0, "rows"=>array());
            $sql = $this->model->getSqlMap("Workload/Calculate/Q_workloadList.sql");
            if($_POST["STATUS"]=="0"){
                $sql.=" AND (ALLOC=0 OR ALLOC IS NULL)";
            }else if($_POST["STATUS"]=="1"){
                $sql.=" AND ALLOC > 0 AND ALLOCWORKLOAD = 0";
            }else if($_POST["STATUS"]=="2"){
                $sql.=" AND ALLOCWORKLOAD > 0";
            }
            $count = $this->model->sqlCount($sql,$bind,true);
            $arr["total"] = intval($count);
            if($arr["total"] > 0){
                $sql .=" ORDER BY COURSENO";
                $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
                $arr["rows"] = $this->model->sqlQuery($sql,$bind);
            }
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        //开课学院
        $this->assign("school",M("schools")->select());
        //工作量类型
        $this->assign("type",$this->model->sqlQuery("select worktype_code code,worktype_name name from workloadtype"));
        $this->display();
    }
    //教师分配-详细列表
    public function queryAlloc(){
        $bind = array(":ID"=>$_POST["ID"]);
        $arr = array("total"=>0, "rows"=>array());
        $sql="SELECT WA.ID,T.TEACHERNO,T.NAME,W.REPEATCOEFF,W.TOTAL,WA.WORKLOAD FROM WORKLOADALLOC WA".
            " LEFT JOIN WORKLOADCALC W ON WA.CALCID=W.ID LEFT JOIN TEACHERS T ON T.TEACHERNO=WA.TEACHERNO WHERE W.ID=:ID";
        $count = $this->model->sqlCount($sql,$bind,true);
        if(intval($count) == 0){
            $sql = $this->model->getSqlMap("Workload/Calculate/Q_teacherList.sql");
            $count = $this->model->sqlCount($sql,$bind,true);
        }
        $arr["total"] = intval($count);
        if($arr["total"] > 0){
            $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
            $arr["rows"] = $this->model->sqlQuery($sql,$bind);
        }
        $this->ajaxReturn($arr,"JSON");
        exit;
    }
    //分配保存或更新
    public function updateAlloc(){
        $num=0;
        $this->model->startTrans();
        if($_POST["type"]=="update"){
            foreach($_POST["list"] as $val){
                $sql="UPDATE WORKLOADALLOC SET WORKLOAD=:WORKLOAD WHERE ID=:ID";
                $row=$this->model->sqlExecute($sql,array(":WORKLOAD"=>$val["WORKLOAD"],":ID"=>$val["ID"]));
                if($row) $num++;
            }
        }else{
            foreach($_POST["list"] as $val){
                $sql="INSERT INTO WORKLOADALLOC VALUES(:CALCID,:TEACHERNO,:REPEATCOEFF,:WORKLOAD)";
                $row=$this->model->sqlExecute($sql,array(":CALCID"=>$val["CALCID"],":TEACHERNO"=>$val["TEACHERNO"],":REPEATCOEFF"=>$val["REPEATCOEFF"],":WORKLOAD"=>$val["WORKLOAD"]));
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
    //确认分配
    public function confirmAlloc(){
        $num=0;
        foreach($_POST["list"] as $val){
            $sql="UPDATE WORKLOADCALC SET ALLOCWORKLOAD=WORKLOAD WHERE ID=:ID";
            $row=$this->model->sqlExecute($sql,array(":ID"=>$val));
            if($row) $num++;
        }
        if($num == count($_POST["list"]))echo true;
        else echo false;
    }
    //查询教师列表
    public function queryTeacher(){
        $bind = $this->model->getBind("NAME,TEACHERNO,SCHOOL,ID",$_REQUEST,"%");
        $arr = array("total"=>0, "rows"=>array());
        $sql = $this->model->getSqlMap("Workload/Calculate/Q_teacherAll.sql");
        $count = $this->model->sqlCount($sql,$bind,true);
        $arr["total"] = intval($count);

        if($arr["total"] > 0){
            $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
            $arr["rows"] = $this->model->sqlQuery($sql,$bind);
        }
        $this->ajaxReturn($arr,"JSON");
        exit;
    }
    //新增教师
    public function addTeacher(){
        $num=0;
        $this->model->startTrans();
        foreach($_POST["list"] as $val){
            $sql="INSERT INTO WORKLOADALLOC VALUES(:CALCID,:TEACHERNO,:REPEATCOEFF,0)";
            $row=$this->model->sqlExecute($sql,array(":CALCID"=>$_POST["COURSE"]["ID"],":TEACHERNO"=>$val["TEACHERNO"],":REPEATCOEFF"=>$_POST["COURSE"]["REPEATCOEFF"]));
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
    //工作量确定-列表
    public function confirm(){
        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,TEACHERNO,NAME,SCHOOL",$_REQUEST,"%");
            $arr = array("total"=>0, "rows"=>array());
            $sql = $this->model->getSqlMap("Workload/Calculate/Q_confirmList.sql");

            if($_POST["STATUS"]=="0"){
                $sql.=" AND (ALLOC=0 OR ALLOC IS NULL)";
            }else if($_POST["STATUS"]=="1"){
                $sql.=" AND ALLOC > 0";
            }else if($_POST["STATUS"]=="2"){
                $sql.=" AND ALLOC > 0 AND ACTUALWORKLOAD IS NULL";
            }else if($_POST["STATUS"]=="3"){
                $sql.=" AND ACTUALWORKLOAD IS NOT NULL";
            }
            $count = $this->model->sqlCount($sql,$bind,true);
            $arr["total"] = intval($count);
            if($arr["total"] > 0){
                $sql .=" ORDER BY SCHOOL";
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
    //工作量确定
    public function confirmWork(){
        $num=0;
        foreach($_POST["list"] as $val){
            $sql="UPDATE WORKLOADTEACHERS SET ACTUALWORKLOAD=:WORKLOAD WHERE ID=:ID";
            $row=$this->model->sqlExecute($sql,array(":WORKLOAD"=>$val["WORKLOAD"],":ID"=>$val["ID"]));
            if($row) $num++;
        }
        if($num == count($_POST["list"]))echo true;
        else echo false;
    }


}