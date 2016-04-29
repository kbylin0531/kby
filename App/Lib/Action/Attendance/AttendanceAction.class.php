<?php
/**
 * 学生考勤管理
 * @author shencp
 * Date: 14-3-11
 * Time: 下午15:14
 */
class AttendanceAction extends RightAction {
	private $model;
	/**
	 * 构造
	 **/
	public function __construct(){
		parent::__construct();
		$this->model = M("SqlsrvModel:");
	}

	/**
	 * 下拉选择课程
	 */
	public function get_course_list()
	{
		$courses = array();
		if ($_GET["YEAR"] && $_GET["TERM"])
		{
			$prmCOURSENO = "%";
			$prmCOURSENAME = "%";
			if ($_POST["q"])
			{
				$prmCOURSENO = trim($_POST["q"])."%";
				$prmCOURSENAME = "%".trim($_POST["q"])."%";
			}
			
			$bind = array(":YEAR"=>$_GET["YEAR"],":TERM"=>$_GET["TERM"],":COURSENO"=>$prmCOURSENO,":COURSENAME"=>$prmCOURSENAME);
			$courses = $this->model->sqlQuery("select top 30 x.* from (select distinct rtrim(i.COURSENO)+rtrim(i.[GROUP]) as id, rtrim(i.COURSENO)+rtrim(i.[GROUP]) + ' / ' + rtrim(m1.COURSENAME) as text from R32 i inner join COURSES m1 on m1.COURSENO = i.COURSENO inner join STUDENTS m2 on m2.STUDENTNO = i.STUDENTNO where i.YEAR=:YEAR and i.TERM=:TERM and (rtrim(i.COURSENO)+rtrim(i.[GROUP]) like :COURSENO or rtrim(m1.COURSENAME) like :COURSENAME)) x order by x.id", $bind);
		}
		$this->ajaxReturn($courses,"JSON");
	}

	/**
	 * 下拉选择班级
	 */
	public function get_courses_class_list()
	{
		$classes = array();
		if ($_GET["YEAR"] && $_GET["TERM"] && $_GET["COURSENO"])
		{
			$prmCLASSNO = "%";
			$prmCLASSNAME = "%";
			if ($_POST["q"])
			{
				$prmCLASSNO = trim($_POST["q"])."%";
				$prmCLASSNAME = "%".trim($_POST["q"])."%";
			}
				
			$bind = array(":YEAR"=>$_GET["YEAR"],":TERM"=>$_GET["TERM"],":COURSENO"=>trim($_GET["COURSENO"]),":CLASSNO"=>$prmCLASSNO,":CLASSNAME"=>$prmCLASSNAME);
			$classes = $this->model->sqlQuery("select top 30 x.* from (select distinct rtrim(m2.CLASSNO) as id, rtrim(m2.CLASSNO) + ' / ' + rtrim(m3.CLASSNAME) as text from R32 i inner join COURSES m1 on m1.COURSENO = i.COURSENO inner join STUDENTS m2 on m2.STUDENTNO = i.STUDENTNO inner join CLASSES m3 on m3.CLASSNO = m2.CLASSNO where i.YEAR=:YEAR and i.TERM=:TERM and rtrim(i.COURSENO)+rtrim(i.[GROUP])=:COURSENO and (rtrim(m2.CLASSNO) like :CLASSNO or rtrim(m3.CLASSNAME) like :CLASSNAME)) x order by x.id", $bind);
		}
		$this->ajaxReturn($classes,"JSON");
	}
	
	/**
	 *考勤情况管理
	 */
	public function manager(){
		if($this->_hasJson){
			$bind = array(":TEACHERNO"=>$_SESSION["S_USER_INFO"]["TEACHERNO"],":YEAR"=>trim($_POST["YEAR"]),":TERM"=>trim($_POST["TERM"]),
					":COURSENO"=>doWithBindStr($_POST["COURSENO"]),
					":CLASSNO"=>doWithBindStr($_POST["CLASSNO"]),
					":CLASSNAME"=>doWithBindStr($_POST["CLASSNAME"]),
					":WEEK"=>doWithBindStr($_POST["WEEK"]),":SCHOOL"=>doWithBindStr($_POST["SCHOOL"]));
			$arr = array("total"=>0, "rows"=>array());
				
			$sql = $this->model->getSqlMap("attendance/queryCCACount.sql");
			$count = $this->model->sqlCount($sql,$bind);
			$arr["total"] = intval($count);
				
			if($arr["total"] > 0){
				$sql = $this->model->getPageSql(null,"attendance/queryCCAList.sql", $this->_pageDataIndex, $this->_pageSize);
				$arr["rows"] = $this->model->sqlQuery($sql,$bind);
			}
			$this->ajaxReturn($arr,"JSON");
			exit;
		}
	
		//当前学年学期
		$yearTerm = $this->model->sqlFind($this->model->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
		$this->assign("yearTerm",$yearTerm);
		//所有学部
		$this->assign('school',getSchoolList());
		//获取所有上课节次		
		$timesectors=M("timesectors")->select();		
		$this->assign("timesectors",$timesectors);
		$tjson=array();
		//把上课节次数据转成json格式给前台的combobox使用
		foreach($timesectors as $val){
			$jsonMap["text"]=trim($val["VALUE"]);
			$jsonMap["value"]=$val["NAME"];
			array_push($tjson,$jsonMap);
		}
		$tjson=json_encode($tjson);		
		$this->assign("tjson",$tjson);		
		
		$this->display();
	}
	
	/**
	 * 新增考勤课程
	 */
	public function minsert(){
		if ($_POST)
		{
			$ary=array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],":COURSENO"=>$_POST["COURSENO"],":CLASSNO"=>$_POST["CLASSNO"],":WEEK"=>$_POST["WEEK"],
					":DATETIME"=>$_POST["DATETIME"],":TIMENO"=>$_POST["TIMENO"],":TEACHERNO"=>$_SESSION["S_USER_INFO"]["TEACHERNO"]);
			
			$sql=$this->model->getSqlMap("attendance/insertCCA.sql");
			$bool=$this->model->sqlExecute($sql,$ary);
			if($bool===false) echo false;
			else echo true;
		}
	}

	/**
	 * 修改更新考勤课程
	 */
	public function mupdate(){		
		if ($_POST["YEAR"] && $_POST["TERM"])
		{
			$courseNo_sql=trim($_POST["COURSENO"]);//课号
			if (trim($_POST["toCOURSENO"]))
			{
				$courseNo_sql=trim($_POST["toCOURSENO"]);
			}
			$classNo_sql=trim($_POST["CLASSNO"]);//学号
			if (trim($_POST["toCLASSNO"]))
			{
				$classNo_sql=trim($_POST["toCLASSNO"]);
			}
			$timeNo_sql=trim($_POST["SECTORS"]);//节次
			if (trim($_POST["toTIMENO"]))
			{
				$timeNo_sql=trim($_POST["toTIMENO"]);
			}
					
			$courseno=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM R32 S inner join STUDENTS m2 on m2.STUDENTNO = S.STUDENTNO WHERE S.YEAR = '".trim($_POST["YEAR"])."' and S.TERM = '".trim($_POST["TERM"])."' and S.COURSENO+S.[GROUP] = '".$courseNo_sql."'");

			$classno=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM R32 S inner join STUDENTS m2 on m2.STUDENTNO = S.STUDENTNO WHERE S.YEAR = '".trim($_POST["YEAR"])."' and S.TERM = '".trim($_POST["TERM"])."' and S.COURSENO+S.[GROUP] = '".$courseNo_sql."' and m2.CLASSNO = '".$classNo_sql."'");
	
			if($courseno["COUNT"] <= 0){
				echo -1;
			}else if($classno["COUNT"] <= 0){
				echo -2;
			}else{
				
				$cca=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM [学生考勤表] S WHERE S.COURSES_CLASS_ATTENDANCE_ID = '".trim($_POST["ID"])."'");
				if($cca["COUNT"] > 0){
					echo -3;
				}
				else
				{
					//开始更新
					$sql="UPDATE courses_class_attendance SET [YEAR]='".trim($_POST["YEAR"])."',TERM='".trim($_POST["TERM"])."',[WEEKS]='".trim($_POST["WEEK"])."',[SCHOOLTIME]=CAST('".trim($_POST["DATETIME"])."' AS datetime),COURSENO='$courseNo_sql',CLASSNO='$classNo_sql',SECTORS='$timeNo_sql' WHERE ID = '".trim($_POST["ID"])."'";
			
					$bool=$this->model->sqlExecute($sql);
					if($bool===false) echo false;
					else echo true;
						
				}				
					
			}
				
		}
		else
		{
			echo false;
		}
	
	}
	
	/**
	 * 删除考勤课程
	 */
	public function mdelete(){
		$newids="";
		foreach($_POST["in"] as $val){
			$newids.=$val.",";
		}
		$newids=rtrim($newids,",");		

		$sql="delete from [学生考勤表] where COURSES_CLASS_ATTENDANCE_ID in ($newids)";
		$row=$this->model->sqlExecute($sql);
		$sql="delete from courses_class_attendance where ID in ($newids)";
		$row=$this->model->sqlExecute($sql);
		
		if($row) echo true;
		else echo false;
	}
	
	/**
	 *上课签到列表
	 */
	public function mstudents(){
		if (!($_POST["ID"])) $_POST["ID"] = $_GET["ID"];

		if ($_POST["ID"])
		{
			$cca=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM [学生考勤表] S WHERE S.COURSES_CLASS_ATTENDANCE_ID = '".trim($_POST["ID"])."'");
			if (!($cca["COUNT"] > 0))
			{
				$sql = " insert into [学生考勤表] (year,term,courseno,studentno,[周数],[上课时间],[节次],[请假理由],date,COURSES_CLASS_ATTENDANCE_ID) "
						." select i.YEAR, i.TERM, i.COURSENO, m1.STUDENTNO, i.WEEKS, i.SCHOOLTIME, i.SECTORS, 'H', getdate(), i.ID from R32 m1 inner join STUDENTS m2 on m2.STUDENTNO = m1.STUDENTNO inner join courses_class_attendance i on m1.YEAR = i.YEAR and m1.TERM = i.TERM and m1.COURSENO+m1.[GROUP] = i.COURSENO and m2.CLASSNO = i.CLASSNO where i.ID = '".$_POST["ID"]."' ";
			
				$this->model->sqlExecute($sql);
			
			}
			
			if($this->_hasJson){
				if (!($_POST["studentno"])) $_POST["studentno"]='%';
				if (!($_POST["studentname"])) $_POST["studentname"]='%';
				if (!($_POST["breaktherule"])) $_POST["breaktherule"]='%';			
				
				$bind = array(":COURSES_CLASS_ATTENDANCE_ID"=>trim($_POST["ID"]),":studentno"=>doWithBindStr($_POST["studentno"]),":studentname"=>doWithBindStr($_POST["studentname"]),":breaktherule"=>$_POST["breaktherule"]);
				$arr = array("total"=>0, "rows"=>array());
				
				$sql = $this->model->getSqlMap("attendance/queryCCAstudentsCount.sql");
				$count = $this->model->sqlCount($sql,$bind);
				$arr["total"] = intval($count);
				
				if($arr["total"] > 0){
					$sql = $this->model->getPageSql(null,"attendance/queryCCAstudentsList.sql", $this->_pageDataIndex, $this->_pageSize);
					$arr["rows"] = $this->model->sqlQuery($sql,$bind);
				}
				$this->ajaxReturn($arr,"JSON");
				exit;
				
			}
			
		}		
		
		$sql=$this->model->getSqlMap("attendance/getCCA.sql");
		$CCA = $this->model->sqlFind($sql,array(":ID"=>$_POST["ID"]));
		$this->assign("CCA",$CCA);
		
		//获取所有请假理由
		$op=M("学生考勤表请假理由options");
		$reason=$op->select();		
		$this->assign('reason',$reason);
		
		$rjson=array();
		//把请假理由数据转成json格式给前台的combobox使用
		foreach($reason as $val){
			$jsonMap["text"]=trim($val["name"]);
			$jsonMap["value"]=$val["code"];
			array_push($rjson,$jsonMap);
		}
		$rjson=json_encode($rjson);
		$this->assign("rjson",$rjson);
		
		//违纪情况设置默认值，格式：json
		$wjson=array();
		$jsonMap["text"]="使用手机";
		$jsonMap["value"]="使用手机";
		array_push($wjson,$jsonMap);
		$jsonMap["text"]="吃东西";
		$jsonMap["value"]="吃东西";
		array_push($wjson,$jsonMap);
		$jsonMap["text"]="讲话";
		$jsonMap["value"]="讲话";
		array_push($wjson,$jsonMap);
		$jsonMap["text"]="打瞌睡";
		$jsonMap["value"]="打瞌睡";
		array_push($wjson,$jsonMap);
		$jsonMap["text"]="其他";
		$jsonMap["value"]="其他";
		array_push($wjson,$jsonMap);
		$wjson=json_encode($wjson);
		$this->assign("wjson",$wjson);
		
		$this->display();
	}

	/**
	 * 上课签到修改更新
	 */
	public function msupdate(){
		if ($_POST["RECNO"])
		{
			$timenum_sql="null";//请假理由
			if ($_POST["TIMENUM"])
			{
				$timenum_sql=trim($_POST["TIMENUM"]);
			}
			$reason_sql=trim($_POST["REASON_ID"]);//请假理由
			if ($_POST["toREASON"])
			{
				$reason_sql=trim($_POST["toREASON"]);
			}
			
			//开始更新
			$sql="UPDATE [学生考勤表] SET [学时]=".$timenum_sql.",[备注]='".trim($_POST["BREAKTHERULE"])."',[请假理由]='$reason_sql' WHERE RECNO = '".trim($_POST["RECNO"])."'";
				
			$bool=$this->model->sqlQuery($sql);
			if($bool===false) echo false;
			else echo true;
								
		}
		else
		{
			echo false;
		}
	}
	
	/**
	 * 考勤周报表
	 */
	public function report(){
		if($this->_hasJson){
			//添加学生考勤信息
			 $ary=array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],":WEEK"=>$_POST["WEEK"],
			 		":DATETIME"=>$_POST["DATETIME"],":STUDENTNO"=>$_POST["STUDENTNO"],
			 		":TIMENO"=>$_POST["TIMENO"],":COURSENO"=>$_POST["COURSENO"],
			 		":TIMENUM"=>$_POST["TIMENUM"],":REASON"=>$_POST["REASON"],
			 		":BREAKTHERULE"=>$_POST["BREAKTHERULE"]);
			 
			 $sql=$this->model->getSqlMap("attendance/insertTimesectors.sql");
	         $bool=$this->model->sqlExecute($sql,$ary);
	         if($bool===false) echo false;
	         else echo true;
		}else{
			//获取当前学年学期
			$data = $this->model->sqlFind($this->model->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
			$this->assign("yearTerm",$data);
			//获取所有上课节次
			$this->assign("timesectors",M("timesectors")->select());
			//获取所有请假理由
			$this->assign("reason",M("学生考勤表请假理由options")->select());
			$this->display();
		}
	}
	
	/**
	 * 学号与课号验证
	 */
	public function validation(){
		if($this->_hasJson){
			
			if ($_GET["vn"]=="course")
			{
				if (trim($_POST["YEAR"]) && trim($_POST["TERM"]))
				{
					//查询课号是否存在
					$courseno=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM R32 S inner join STUDENTS m2 on m2.STUDENTNO = S.STUDENTNO WHERE S.YEAR = '".trim($_POST["YEAR"])."' and S.TERM = '".trim($_POST["TERM"])."' and S.COURSENO+S.[GROUP] = '".trim($_POST["VALUE"])."'");
					echo $courseno["COUNT"];
				}
				else 
				{
					echo 0;
				}
			}
			elseif ($_GET["vn"]=="class")
			{
				if (trim($_POST["YEAR"]) && trim($_POST["TERM"]) && trim($_POST["COURSENO"]))
				{
					$classno=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM R32 S inner join STUDENTS m2 on m2.STUDENTNO = S.STUDENTNO WHERE S.YEAR = '".trim($_POST["YEAR"])."' and S.TERM = '".trim($_POST["TERM"])."' and S.COURSENO+S.[GROUP] = '".trim($_POST["COURSENO"])."' and m2.CLASSNO = '".trim($_POST["VALUE"])."'");
					echo $classno["COUNT"];
				}
				else 
				{
					echo 0;
				}
			}			
			
		}else{
			//查询学号是否存在
			$studentno=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM STUDENTS WHERE STUDENTNO = '".trim($_POST["VALUE"])."'");
			echo $studentno["COUNT"];
		}
	}
	
	/**
	 *考勤情况查询
	 */
	public function query(){
 		//查询学生考勤表
		if($this->_hasJson){
			$bind = array(":YEAR"=>trim($_POST["YEAR"]),":TERM"=>trim($_POST["TERM"]),":STUDENTNO"=>doWithBindStr($_POST["STUDENTNO"]),
					":NAME"=>doWithBindStr($_POST["NAME"]),":COURSENO"=>doWithBindStr($_POST["COURSENO"]),
					":WEEK"=>doWithBindStr($_POST["WEEK"]),":SCHOOL"=>doWithBindStr($_POST["SCHOOL"]));
			$arr = array("total"=>0, "rows"=>array());
			
			$sql = $this->model->getSqlMap("attendance/queryTimesectorsCount.sql");
			$count = $this->model->sqlCount($sql,$bind);
			$arr["total"] = intval($count);
			
			if($arr["total"] > 0){
				$sql = $this->model->getPageSql(null,"attendance/queryTimesectorsList.sql", $this->_pageDataIndex, $this->_pageSize);
				$arr["rows"] = $this->model->sqlQuery($sql,$bind);
			}
			$this->ajaxReturn($arr,"JSON");
			exit;
		}
		
		//当前学年学期
		$yearTerm = $this->model->sqlFind($this->model->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
		$this->assign("yearTerm",$yearTerm);
		//所有学部	
		$this->assign('school',getSchoolList());
		//获取所有上课节次
		$timesectors=M("timesectors");
		$time=$timesectors->select();
		$tjson=array();
		//把上课节次数据转成json格式给前台的combobox使用
		foreach($time as $val){
			$jsonMap["text"]=trim($val["VALUE"]);
			$jsonMap["value"]=$val["NAME"];
			array_push($tjson,$jsonMap);
		}
		$tjson=json_encode($tjson);
		$this->assign("tjson",$tjson);
		
		//获取所有请假理由
		$op=M("学生考勤表请假理由options");
		$reason=$op->select();
		$rjson=array();
		//把请假理由数据转成json格式给前台的combobox使用
		foreach($reason as $val){
			$jsonMap["text"]=trim($val["name"]);
			$jsonMap["value"]=$val["code"];
			array_push($rjson,$jsonMap);
		}
		$rjson=json_encode($rjson);
		$this->assign("rjson",$rjson);
		
		//违纪情况设置默认值，格式：json
		$wjson=array();
		$jsonMap["text"]="使用手机";
		$jsonMap["value"]="使用手机";
		array_push($wjson,$jsonMap);
		$jsonMap["text"]="吃东西";
		$jsonMap["value"]="吃东西";
		array_push($wjson,$jsonMap);
		$jsonMap["text"]="讲话";
		$jsonMap["value"]="讲话";
		array_push($wjson,$jsonMap);
		$jsonMap["text"]="打瞌睡";
		$jsonMap["value"]="打瞌睡";
		array_push($wjson,$jsonMap);
		$jsonMap["text"]="其他";
		$jsonMap["value"]="其他";
		array_push($wjson,$jsonMap);
		$wjson=json_encode($wjson);
		$this->assign("wjson",$wjson);
		
		$this->display();
	}
	
	/**
	 * 删除
	 */
	public function del(){
		$newids="";
		foreach($_POST["in"] as $val){
			$newids.="'".$val."',";
		}
		$newids=rtrim($newids,",");
		$sql="delete from 学生考勤表 where recno in ($newids)";
		$row=$this->model->sqlExecute($sql);
		if($row) echo true;
		else echo false;
	}
	
	/**
	 * 修改更新
	 */
	public function update(){
		$courseNo_sql=trim($_POST["COURSENO"]);//课号
		$studentNo_sql=trim($_POST["STUDENTNO"]);//学号
		$timeNo_sql=trim($_POST["TIMENO"]);//节次
		$reason_sql=trim($_POST["REASON"]);//请假理由
		//查询课号是否存在
		$courseno=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM SCHEDULEPLAN S WHERE S.COURSENO+S.[GROUP] = '$courseNo_sql'");
		//查询学号是否存在
		$studentno=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM STUDENTS WHERE STUDENTNO = '$studentNo_sql'");
		
		if($courseno["COUNT"] <= 0){
			echo -1;
		}else if($studentno["COUNT"] <= 0){
			echo -2;
		}else{
			//开始更新
			$sql="UPDATE [学生考勤表] SET [YEAR]='".trim($_POST["YEAR"])."',TERM='".trim($_POST["TERM"])."',[周数]='"
					.trim($_POST["WEEK"])."',[上课时间]=CAST('".trim($_POST["DATETIME"])."' AS datetime),[学时]=".
					trim($_POST["TIMENUM"]).",[备注]='".trim($_POST["BREAKTHERULE"])."',COURSENO='$courseNo_sql',STUDENTNO='$studentNo_sql'";
			
			if(strlen($timeNo_sql) <= 3){
				$sql=$sql.",[节次]='$timeNo_sql'";
			}
			if(strlen($reason_sql) <= 3){
				$sql=$sql.",[请假理由]='$reason_sql'";
			}
			$sql=$sql." WHERE RECNO = '".trim($_POST["RECNO"])."'";
			
			$bool=$this->model->sqlQuery($sql);
	        if($bool===false) echo false;
	        else echo true;
		}
	}
	
	/**
	 * 考勤统计
	 */
	public function statis(){
		//设置默认数值
		$start=trim($_POST["STARTDATA"])==""?0:trim($_POST["STARTDATA"]);
		$end=trim($_POST["ENDDATA"])==""?50:trim($_POST["ENDDATA"]);
		
		//查询学生考勤表
		if($this->_hasJson){
			$bind = array(":YEAR"=>trim($_POST["YEAR"]),":TERM"=>trim($_POST["TERM"]),":SCHOOL"=>doWithBindStr($_POST["SCHOOL"]),
						":START"=>$start,":END"=>$end);
			$arr = array("total"=>0, "rows"=>array());
				
			$sql = $this->model->getSqlMap("attendance/statisTimesectorsCount.sql");
			$count = $this->model->sqlCount($sql,$bind,true);
			$arr["total"] = intval($count);
				
			if($arr["total"] > 0){
				$sql = $this->model->getPageSql(null,"attendance/statisTimesectorsList.sql", $this->_pageDataIndex, $this->_pageSize);
				$arr["rows"] = $this->model->sqlQuery($sql,$bind);
			}
			$this->ajaxReturn($arr,"JSON");
			exit;
		}
		//当前学年学期
		$yearTerm = $this->model->sqlFind($this->model->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
		$this->assign("yearTerm",$yearTerm);
		//所有学部
		$this->assign('school',getSchoolList());
		$this->display();
	}
	
	/**
	 * 统计旷课课时超过该课程三分之一的学生
	 */
	public function statisTimeout(){
		$bind = array(":YEAR"=>trim($_POST["YEAR"]),":TERM"=>trim($_POST["TERM"]),
				":SCHOOL"=>doWithBindStr($_POST["SCHOOL"]),":WEEK"=>doWithBindStr($_POST["WEEK"]));
		$arr = array("total"=>0, "rows"=>array());
		
		$sql = $this->model->getSqlMap("attendance/statisTimeoutTimesectorsCount.sql");
		$count = $this->model->sqlCount($sql,$bind,true);
		$arr["total"] = intval($count);
		
		if($arr["total"] > 0){
			//因参数别名放在where前面导致sql无法执行。于是出此下策
			$sql="SELECT 学生考勤表.STUDENTNO, STUDENTS.NAME STUDENTNAME,CLASSES.CLASSNAME,".
					"SCHOOLS.NAME SCHOOLSNAME,COURSES.COURSENAME,COURSES.HOURS WEEKHOURS,".
					"(COURSES.HOURS * ".$bind[":WEEK"].") HOURS".$this->model->getSqlMap("attendance/statisTimeoutTimesectorsList.sql");
			
			$sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
			$arr["rows"] = $this->model->sqlQuery($sql,$bind);
		}
		$this->ajaxReturn($arr,"JSON");
	}
}