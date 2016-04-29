<?php
/**
* 开课计划
* User: cwebs
* Date: 14-2-23
* Time: 上午8:47
*/
class TimetableAction extends RightAction {
    private $model;
    protected $message = array("type"=>"info","message"=>"","dbError"=>"");
    private $theacher;

    public function __construct(){
        parent::__construct();
        $this->model = new CoursePlanModel();

        $bind = $this->model->getBind("SESSIONID", session("S_GUID"));
        $sql = $this->model->getSqlMap("user/teacher/getUserBySessionId.sql");
        $this->theacher = $this->model->sqlFind($sql, $bind);
        $this->assign("theacher", $this->theacher);
    }

    /**
     * [PL06]自动创建排课计划
     */
    public function auto(){
        if($this->_hasJson){
            if(VarIsIntval("YEAR,TERM")==false){
                $this->exitWithReport('无法从请求中获取学年学期信息，请刷新页面重试！');
            }
            $this->model->startTrans();

            $year = $_REQUEST['YEAR'];
            $term = $_REQUEST['TERM'];
            $condition = array(
                'YEAR' => $year,
                'TERM' => $term,
            );
            is_string($this->model->clearTestPlan($condition)) and $this->exitWithReport("清除第[{$year}]学年第[{$term}]学期的考试计划失败了");
            is_string($this->model->clearTeacherPlan($condition)) and $this->exitWithReport("清除第[{$year}]学年第[{$term}]学期的教师教学计划失败了");
            is_string($this->model->clearSchedule($condition)) and $this->exitWithReport("清除第[{$year}]学年第[{$term}]学期的课程总表失败了");
            is_string($this->model->clearSchedulePlan($condition)) and $this->exitWithReport("清除第[{$year}]学年第[{$term}]学期的排课计划表失败了");

            $rst = $this->model->dumpCoursePlanIntoSchedulePlan($condition);
            if(is_string($rst)){
                $this->model->rollback();
                $this->exitWithReport('开课计划导入到排课计划的过程出现错误！'.$rst);
            }else{
                $this->model->commit();
                $this->exitWithReport("此次共有[{$rst}]条纪录被传送到排课计划表！",'info');
            }
        }
        $this->display('auto');
    }

    /**
     * [PL06]给课程添加未定教师
     */
    public function  addUnknow(){
        //todo: 检测传入的参数
        if(VarIsIntval("YEAR,TERM")==false){
            $this->message["type"] = "error";
            $this->message["message"] = "输入的参数有错误，非法提交数据！";
            $this->__done();
        }
        $bind = $this->model->getBind("YEAR,TERM",$_REQUEST);
        $data = $this->model->sqlExecute($this->model->getSqlMap("coursePlan/addUnknowTheacher.sql"),$bind);
        if($data===false){
            $this->model->rollback();
            $this->message["type"] = "error";
            $this->message["message"] = "给课程添加未定教师发生错误！！";
            $this->__done();
        }
        $this->message["type"] = "info";
        $this->message["message"] = "给课程添加未定教师成功！！";
        $this->__done("auto");
    }

    /**
     * [PL01]排课计划查询页
     */
    public function qform(){
        $this->__done("qform");
    }

    /**
     * [PL01]排课计划列表页
     */
    public function qlist(){

        if(isset($_GET['tag']) && trim($_GET['tag']) == 'delteacher'){
            $sql = 'DELETE from TEACHERPLAN WHERE RECNO = :recno;';
            $bind  = array(':recno'=>trim($_POST['recno']));
            $rst = $this->model->sqlExecute($sql,$bind);
            exit ($rst?'success':'failure code:'.$this->model->getDbError());
        }

        if(isset($_GET['tag']) && trim($_GET['tag']) == 'schedulepandetail'){
            $json = array();
            $bind = array(':RECNO'=>trim($_POST['recno']));
            $json['scheduleplan'] = $this->model->sqlFind($this->model->getSqlMap("coursePlan/SelectScheduleplanByRecNO.sql"), $bind);
            if($json['scheduleplan']!=null){
                $schoolno = $json['scheduleplan']["SCHOOL"];
                if(isset($schoolno)){
                    $sql = 'SELECT RTRIM(TEACHERS.TEACHERNO) AS CODE,RTRIM(TEACHERS.NAME) AS NAME from TEACHERS LEFT OUTER JOIN SCHOOLS on TEACHERS.SCHOOL = SCHOOLS .SCHOOL
                        WHERE TEACHERS.SCHOOL = :schoolno ORDER BY NAME;';
                    $json['selfTeachers'] = $this->model->sqlQuery($sql, array(":schoolno"=>$schoolno));
                }
            }
            $this->ajaxReturn($json,'JSON');
            exit;
        }

		if(REQTAG === 'export'){
			ini_set("max_execution_time", "1800");
			$bind = $this->model->getBind("YEAR,TERM,COURSENO,GROUP,SCHOOL,TGROUP,COURSETYPE,SCHEDULED,ROOMTYPE,CLASSNO,EXAMTYPE,XCTYPE,ESTIMATEUP,ESTIMATEDOWN,ATTENDENTSUP,ATTENDENTSDOWN,DAYS,classnoname",$_REQUEST,"%");
			$rst =  $this->model->getScheduleList($bind);
			foreach($rst["rows"] as $k=>$row){
				$json["rows"][$k]["WEEKS"] = strrev(sprintf("%018s", decbin($row["WEEKS"])));
			}
			if(is_string($rst)){
				$this->exitWithUtf8("获取数据失败！{$rst}");
			}
			$data = array();
			$this->model->initPHPExcel();
			$data['title'] = '排课计划表';
			//表头
			$data['head'] = array(
				//默认值如 align type 的设计实例
				'CLASS' => array( '班级', 'align' => CommonModel::ALI_LEFT,'width'=>20),
				'SCHOOLNAME' => array('学部','align' => CommonModel::ALI_LEFT,'width'=>20),
				'COURSENAME' => array( '课程名称', 'align' => CommonModel::ALI_LEFT,'width'=>20),
				'CREDITS' => array( '学分', 'align' => CommonModel::ALI_CENTER,'width'=>10),
				'TEACHERTASK' => array( '任课老师', 'align' => CommonModel::ALI_LEFT,'width'=>50),
			);
			//表体
			$data['body'] = $rst['rows'];
			$this->model->fullyExportExcelFile($data, $data['title']);
		}

        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,COURSENO,GROUP,SCHOOL,TGROUP,COURSETYPE,SCHEDULED,ROOMTYPE,CLASSNO,EXAMTYPE,XCTYPE,ESTIMATEUP,ESTIMATEDOWN,ATTENDENTSUP,ATTENDENTSDOWN,DAYS,grade,schoolno,teachernoname,classnoname",$_REQUEST,"%");
            $json =  $this->model->getScheduleList($bind,$this->_pageDataIndex, $this->_pageSize);
			foreach($json["rows"] as $k=>$row){
				$json["rows"][$k]["WEEKS"] = strrev(sprintf("%018s", decbin($row["WEEKS"])));
			}
            $this->ajaxReturn($json,"JSON");
        }


        $this->assign('schools',getSchoolList());
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
		$this->assign("user_school",$_SESSION["S_USER_INFO"]["SCHOOL"]);
        $this->assign("queryParams",count($_REQUEST)>0?json_encode($_REQUEST):"{}");
        $this->__done("qlist");
    }

    /**
     * excel导出更欣课表班级名单
     */
    public function excelexpGXKBclass(){
    
    	TaskMonitorModel::init(session("S_USER_NAME"), "excel导出用于更欣课表的精准班级名单");
    		
    	$pageSize = 500;
        	
    	$bind = array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],
    			":COURSENO"=>doWithBindStr($_POST["COURSENO"]),":GROUP"=>$_POST["GROUP"],
    			":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"],
    			":COURSETYPE"=>$_POST["COURSETYPE"],":XCTYPE"=>$_POST["XCTYPE"],
    			":SCHEDULED"=>$_POST["SCHEDULED"],":ROOMTYPE"=>$_POST["ROOMTYPE"],
    			":CLASSNO"=>doWithBindStr($_POST["CLASSNO"]),":EXAMTYPE"=>$_POST["EXAMTYPE"],
    			":ESTIMATEUP"=>$_POST["ESTIMATEUP"],":ESTIMATEDOWN"=>$_POST["ESTIMATEDOWN"],
    			":ATTENDENTSUP"=>$_POST["ATTENDENTSUP"],":ATTENDENTSDOWN"=>$_POST["ATTENDENTSDOWN"],
    			":DAYS"=>$_POST["DAYS"]);
    		
    	$sql_count = " select COUNT(*) as icount from ( "
    			
    			   . " SELECT distinct ltrim(rtrim(CLASSES.CLASSNO)) as CLASSNO, ltrim(rtrim(CLASSES.CLASSNAME)) as CLASSNAME "
    			   . " FROM SCHEDULEPLAN " 
    			   . " inner JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    			   . " inner JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "
    			   		
    			   . " left JOIN "	
    			   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    			   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    			   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    			   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    			   		
    			   . " left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    			   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    			   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    			   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    			   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "

    			   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    			   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "

    			   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    			   . " LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    			   . " LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    			   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    			   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "

    			   . " WHERE SCHEDULEPLAN.YEAR = :YEAR "
    			   . " AND SCHEDULEPLAN.TERM = :TERM "
    			   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    			   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    			   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    			   . " AND COURSES.TGROUP LIKE :TGROUP "
    			   . " AND COURSES.TYPE LIKE :COURSETYPE "
    			   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    			   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    			   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    			   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    			   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    			   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    			   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    			   . " {\$SQL.LOCK} "
    			   . " {\$SQL.EXAM} "
    			   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    			   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) "
    			   		
    			   . " ) x ";
    	
    	$sql_count = $this->formatScheduleplanSQL($sql_count);
    	$data = $this->model->sqlFind($sql_count,$bind);
    	$totalCount = $data['icount'];
    
    	if ($totalCount > 0)
    	{
    		TaskMonitorModel::run(session("S_USER_NAME"),"excel导出用于更欣课表的精准班级名单", $totalCount);
    
    		$inputFileType = 'Excel5';
    		$inputFileName = $_SERVER['DOCUMENT_ROOT'] . "\\res\\templates\\classes_class_expGXKB.xls";
    
    		vendor("PHPExcel.PHPExcel");
    
    		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    		$objPHPExcel = $objReader->load($inputFileName);
    		$targetws = $objPHPExcel->getSheet(0);
    
    		$totalPage = ceil($totalCount / $pageSize);
    
    		$pageNumber = 1;
    		$startNumber = 0;
    
    		$sql_select = " select x.* from ( "
    			
    			   . " SELECT distinct ltrim(rtrim(CLASSES.CLASSNO)) as CLASSNO, ltrim(rtrim(CLASSES.CLASSNAME)) as CLASSNAME "
    			   . " FROM SCHEDULEPLAN " 
    			   . " inner JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    			   . " inner JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "
    			   		
    			   . " left JOIN "	
    			   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    			   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    			   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    			   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    			   		
    			   . " left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    			   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    			   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    			   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    			   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "

    			   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    			   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "

    			   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    			   . " LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    			   . " LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    			   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    			   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "

    			   . " WHERE SCHEDULEPLAN.YEAR = :YEAR "
    			   . " AND SCHEDULEPLAN.TERM = :TERM "
    			   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    			   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    			   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    			   . " AND COURSES.TGROUP LIKE :TGROUP "
    			   . " AND COURSES.TYPE LIKE :COURSETYPE "
    			   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    			   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    			   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    			   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    			   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    			   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    			   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    			   . " {\$SQL.LOCK} "
    			   . " {\$SQL.EXAM} "
    			   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    			   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) "
    			   		
    			   . " ) x order by x.CLASSNO ";
    	
    		$sql_select = $this->formatScheduleplanSQL($sql_select);
    		$pagesql_select = $this->model->getPageSql($sql_select,null,$startNumber,$pageSize);
    
    		$result = $this->model->sqlQuery($pagesql_select,$bind);
    
    		if (count($result) > 0)
    		{
    			$this->set_expGXKBclass_xls($result, $pageNumber, $pageSize, $targetws);
    
    			while ($pageNumber < $totalPage)
    			{
    				$pageNumber++;
    				$startNumber = ($pageNumber -1) * $pageSize;
    
    				$pagesql_select = $this->model->getPageSql($sql_select,null,$startNumber,$pageSize);
    
    				$result = $this->model->sqlQuery($pagesql_select,$bind);
    				if (count($result) > 0)
    				{
    					$this->set_expGXKBclass_xls($result, $pageNumber, $pageSize, $targetws);
    				}
    			}
    
    		}
    
    	}

    	TaskMonitorModel::done(session("S_USER_NAME"));    	 
    	 
    	if ($totalCount > 0)
    	{
    		ob_end_clean();
    		
    		$filename = "用于更欣课表的精准班级名单_" . date ( 'Y-m-d', time () );
    		header('Content-Type: application/vnd.ms-excel');
    		header("Content-Disposition: attachment;filename=".iconv('UTF-8','GB2312',$filename.".xls"));
    		header('Cache-Control: max-age=0');
    		
    		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    		header ('Cache-Control: cache, must-revalidate');
    		header ('Pragma: public');
    		
    		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    		$objWriter->save('php://output');
    		
    	}
    		
    }
    
    private function set_expGXKBclass_xls($result, $pageNumber, $pageSize, $targetws) {
    	$row = ($pageNumber - 1) * $pageSize + 1;
    	foreach ( $result as $iresult )
    	{
    		if ($iresult)
    		{
    			$row++;
    				
    			$targetws->insertNewRowBefore ( $row, 1 ); //插入新的行
    				
    			$targetws->setCellValue ( 'A' . $row, $row - 1); //序号
    			$targetws->setCellValueExplicit ( 'B' . $row, $iresult['CLASSNAME'], PHPExcel_Cell_DataType::TYPE_STRING ); //名字
    			$targetws->setCellValueExplicit ( 'C' . $row, $iresult['CLASSNAME'], PHPExcel_Cell_DataType::TYPE_STRING ); //全称
    			$targetws->setCellValueExplicit ( 'D' . $row, $iresult['CLASSNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //简称1
    			$targetws->setCellValueExplicit ( 'E' . $row, $iresult['CLASSNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //简称2
    			$targetws->setCellValueExplicit ( 'F' . $row, $iresult['CLASSNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //注脚
    			$targetws->setCellValueExplicit ( 'G' . $row, $iresult['CLASSNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //代码
    				
    			$targetws->setCellValueExplicit ( 'I' . $row, $iresult['COUNTS'] . "个", PHPExcel_Cell_DataType::TYPE_STRING ); //成员
    				
    				
    			TaskMonitorModel::next(session("S_USER_NAME"),$row - 1,true,$iresult['CLASSNO'],2);
    		}
    	}
    }

    /**
     * excel导出更欣课表课程名单
     */
    public function excelexpGXKBcourse(){
    
    	TaskMonitorModel::init(session("S_USER_NAME"), "excel导出用于更欣课表的精准课程名单");
    
    	$pageSize = 500;
        	
    	$bind = array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],
    			":COURSENO"=>doWithBindStr($_POST["COURSENO"]),":GROUP"=>$_POST["GROUP"],
    			":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"],
    			":COURSETYPE"=>$_POST["COURSETYPE"],":XCTYPE"=>$_POST["XCTYPE"],
    			":SCHEDULED"=>$_POST["SCHEDULED"],":ROOMTYPE"=>$_POST["ROOMTYPE"],
    			":CLASSNO"=>doWithBindStr($_POST["CLASSNO"]),":EXAMTYPE"=>$_POST["EXAMTYPE"],
    			":ESTIMATEUP"=>$_POST["ESTIMATEUP"],":ESTIMATEDOWN"=>$_POST["ESTIMATEDOWN"],
    			":ATTENDENTSUP"=>$_POST["ATTENDENTSUP"],":ATTENDENTSDOWN"=>$_POST["ATTENDENTSDOWN"],
    			":DAYS"=>$_POST["DAYS"]);
    		
    	$sql_count = " select COUNT(*) as icount from ( "
    			
    			   . " SELECT distinct (ltrim(rtrim(COURSEPLAN.COURSENO))+COURSEPLAN.[GROUP]) as COURSENO, ltrim(rtrim(COURSES.COURSENAME)) as COURSENAME "
    			   . " FROM SCHEDULEPLAN "     			   		
    			   . " inner JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    			   		
    			   . " left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    			   . " left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "    			   		
    			   . " left JOIN "	
    			   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    			   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    			   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    			   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    			   		
    			   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    			   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    			   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    			   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "

    			   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    			   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "

    			   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    			   . " LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    			   . " LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    			   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    			   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "

    			   . " WHERE SCHEDULEPLAN.YEAR = :YEAR "
    			   . " AND SCHEDULEPLAN.TERM = :TERM "
    			   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    			   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    			   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    			   . " AND COURSES.TGROUP LIKE :TGROUP "
    			   . " AND COURSES.TYPE LIKE :COURSETYPE "
    			   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    			   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    			   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    			   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    			   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    			   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    			   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    			   . " {\$SQL.LOCK} "
    			   . " {\$SQL.EXAM} "
    			   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    			   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) "
    			   		
    			   . " ) x ";
    	
    	$sql_count = $this->formatScheduleplanSQL($sql_count);
    	$data = $this->model->sqlFind($sql_count,$bind);
    	$totalCount = $data['icount'];
    
    	if ($totalCount > 0)
    	{
    		TaskMonitorModel::run(session("S_USER_NAME"),"excel导出用于更欣课表的精准课程名单", $totalCount);
    
    		$inputFileType = 'Excel5';
    		$inputFileName = $_SERVER['DOCUMENT_ROOT'] . "\\res\\templates\\course_course_expGXKB.xls";
    
    		vendor("PHPExcel.PHPExcel");
    
    		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    		$objPHPExcel = $objReader->load($inputFileName);
    		$targetws = $objPHPExcel->getSheet(0);
    
    		$totalPage = ceil($totalCount / $pageSize);
    
    		$pageNumber = 1;
    		$startNumber = 0;
    
   		 	$sql_select = " select x.* from ( "
    			
    			   . " SELECT distinct (ltrim(rtrim(COURSEPLAN.COURSENO))+COURSEPLAN.[GROUP]) as COURSENO, ltrim(rtrim(COURSES.COURSENAME)) as COURSENAME "
    			   . " FROM SCHEDULEPLAN "     			   		
    			   . " inner JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    			   		
    			   . " left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    			   . " left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "    			   		
    			   . " left JOIN "	
    			   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    			   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    			   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    			   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    			   		
    			   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    			   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    			   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    			   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "

    			   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    			   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "

    			   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    			   . " LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    			   . " LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    			   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    			   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "

    			   . " WHERE SCHEDULEPLAN.YEAR = :YEAR "
    			   . " AND SCHEDULEPLAN.TERM = :TERM "
    			   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    			   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    			   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    			   . " AND COURSES.TGROUP LIKE :TGROUP "
    			   . " AND COURSES.TYPE LIKE :COURSETYPE "
    			   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    			   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    			   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    			   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    			   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    			   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    			   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    			   . " {\$SQL.LOCK} "
    			   . " {\$SQL.EXAM} "
    			   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    			   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) "
    			   		
    			   . " ) x order by x.COURSENO ";
    	
    		$sql_select = $this->formatScheduleplanSQL($sql_select);
    		$pagesql_select = $this->model->getPageSql($sql_select,null,$startNumber,$pageSize);
    
    		$result = $this->model->sqlQuery($pagesql_select,$bind);
    
    		if (count($result) > 0)
    		{
    			$this->set_expGXKBcourse_xls($result, $pageNumber, $pageSize, $targetws);
    
    			while ($pageNumber < $totalPage)
    			{
    				$pageNumber++;
    				$startNumber = ($pageNumber -1) * $pageSize;
    
    				$pagesql_select = $this->model->getPageSql($sql_select,null,$startNumber,$pageSize);
    				$result = $this->model->sqlQuery($pagesql_select,$bind);
    				if (count($result) > 0)
    				{
    					$this->set_expGXKBcourse_xls($result, $pageNumber, $pageSize, $targetws);
    				}
    			}
    
    		}
    
    	}

    	TaskMonitorModel::done(session("S_USER_NAME"));    	
    	 
    	if ($totalCount > 0)
    	{
    		ob_end_clean();
    		 
    		$filename = "用于更欣课表的精准课程名单_" . date ( 'Y-m-d', time () );
    		header('Content-Type: application/vnd.ms-excel');
    		header("Content-Disposition: attachment;filename=".iconv('UTF-8','GB2312',$filename.".xls"));
    		header('Cache-Control: max-age=0');
    		
    		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    		header ('Cache-Control: cache, must-revalidate');
    		header ('Pragma: public');
    		
    		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    		$objWriter->save('php://output');
    		
    	}
    
    }
    
    private function set_expGXKBcourse_xls($result, $pageNumber, $pageSize, $targetws) {
    	$row = ($pageNumber - 1) * $pageSize + 1;
    	foreach ( $result as $iresult )
    	{
    		if ($iresult)
    		{
    			$row++;
    
    			$targetws->insertNewRowBefore ( $row, 1 ); //插入新的行
    
    			$targetws->setCellValue ( 'A' . $row, $row - 1); //序号
    			$targetws->setCellValueExplicit ( 'B' . $row, $iresult['COURSENAME'], PHPExcel_Cell_DataType::TYPE_STRING ); //名字
    			$targetws->setCellValueExplicit ( 'C' . $row, $iresult['COURSENAME'], PHPExcel_Cell_DataType::TYPE_STRING ); //全称
    			$targetws->setCellValueExplicit ( 'D' . $row, $iresult['COURSENO'], PHPExcel_Cell_DataType::TYPE_STRING ); //简称1
    			$targetws->setCellValueExplicit ( 'E' . $row, $iresult['COURSENO'], PHPExcel_Cell_DataType::TYPE_STRING ); //简称2
    			$targetws->setCellValueExplicit ( 'F' . $row, $iresult['COURSENO'], PHPExcel_Cell_DataType::TYPE_STRING ); //注脚
    			$targetws->setCellValueExplicit ( 'G' . $row, $iresult['COURSENO'], PHPExcel_Cell_DataType::TYPE_STRING ); //代码
    
    			$targetws->setCellValueExplicit ( 'I' . $row, "0个", PHPExcel_Cell_DataType::TYPE_STRING ); //成员
    
    
    			TaskMonitorModel::next(session("S_USER_NAME"),$row - 1,true,$iresult['COURSENO'],2);
    		}
    	}
    }
    
    /**
     * excel导出更欣课表老师名单
     */
    public function excelexpGXKBarchive(){
    
    	TaskMonitorModel::init(session("S_USER_NAME"), "excel导出用于更欣课表的精准老师名单");
    		
    	$pageSize = 500;    
    	
    	$bind = array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],
    			":COURSENO"=>doWithBindStr($_POST["COURSENO"]),":GROUP"=>$_POST["GROUP"],
    			":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"],
    			":COURSETYPE"=>$_POST["COURSETYPE"],":XCTYPE"=>$_POST["XCTYPE"],
    			":SCHEDULED"=>$_POST["SCHEDULED"],":ROOMTYPE"=>$_POST["ROOMTYPE"],
    			":CLASSNO"=>doWithBindStr($_POST["CLASSNO"]),":EXAMTYPE"=>$_POST["EXAMTYPE"],
    			":ESTIMATEUP"=>$_POST["ESTIMATEUP"],":ESTIMATEDOWN"=>$_POST["ESTIMATEDOWN"],
    			":ATTENDENTSUP"=>$_POST["ATTENDENTSUP"],":ATTENDENTSDOWN"=>$_POST["ATTENDENTSDOWN"],
    			":DAYS"=>$_POST["DAYS"]);
    		
    	$sql_count = " select COUNT(*) as icount from ( "
    			
    			   . " SELECT distinct ltrim(rtrim(THETEACHERS.TEACHERNO)) as TEACHERNO, ltrim(rtrim(THETEACHERS.TEACHERNAME)) as NAME "
    			   . " FROM SCHEDULEPLAN " 
    			   . " inner JOIN "	
    			   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    			   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    			   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    			   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    			   		
    			   . " left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    			   . " left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    			   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    			   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    			   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    			   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "
    			   . " left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "

    			   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    			   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "

    			   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    			   . " LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    			   . " LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    			   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    			   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "

    			   . " WHERE SCHEDULEPLAN.YEAR = :YEAR "
    			   . " AND SCHEDULEPLAN.TERM = :TERM "
    			   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    			   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    			   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    			   . " AND COURSES.TGROUP LIKE :TGROUP "
    			   . " AND COURSES.TYPE LIKE :COURSETYPE "
    			   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    			   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    			   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    			   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    			   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    			   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    			   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    			   . " {\$SQL.LOCK} "
    			   . " {\$SQL.EXAM} "
    			   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    			   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) "
    			   		
    			   . " ) x ";
    	
    	$sql_count = $this->formatScheduleplanSQL($sql_count);   	
    	$data = $this->model->sqlFind($sql_count,$bind);
    	$totalCount = $data['icount'];
    	
    	if ($totalCount > 0)
    	{
    		TaskMonitorModel::run(session("S_USER_NAME"),"excel导出用于更欣课表的精准老师名单", $totalCount);
    
    		$inputFileType = 'Excel5';
    		$inputFileName = $_SERVER['DOCUMENT_ROOT'] . "\\res\\templates\\archive_archive_expGXKB.xls";
    
    		vendor("PHPExcel.PHPExcel");
    
    		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    		$objPHPExcel = $objReader->load($inputFileName);
    		$targetws = $objPHPExcel->getSheet(0);
    
    		$totalPage = ceil($totalCount / $pageSize);
    
    		$pageNumber = 1;
    		$startNumber = 0;
    
 		   	$sql_select = " select x.* from ( "
    			
    			   . " SELECT distinct ltrim(rtrim(THETEACHERS.TEACHERNO)) as TEACHERNO, ltrim(rtrim(THETEACHERS.TEACHERNAME)) as NAME "
    			   . " FROM SCHEDULEPLAN " 
    			   . " inner JOIN "	
    			   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    			   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    			   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    			   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    			   		
    			   . " left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    			   . " left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    			   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    			   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    			   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    			   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "
    			   . " left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "

    			   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    			   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "

    			   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    			   . " LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    			   . " LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    			   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    			   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "

    			   . " WHERE SCHEDULEPLAN.YEAR = :YEAR "
    			   . " AND SCHEDULEPLAN.TERM = :TERM "
    			   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    			   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    			   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    			   . " AND COURSES.TGROUP LIKE :TGROUP "
    			   . " AND COURSES.TYPE LIKE :COURSETYPE "
    			   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    			   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    			   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    			   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    			   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    			   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    			   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    			   . " {\$SQL.LOCK} "
    			   . " {\$SQL.EXAM} "
    			   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    			   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) "
    			   		
    			   . " ) x order by x.TEACHERNO ";
    	
    		$sql_select = $this->formatScheduleplanSQL($sql_select);
    		$pagesql_select = $this->model->getPageSql($sql_select,null,$startNumber,$pageSize);
    		
    		$result = $this->model->sqlQuery($pagesql_select,$bind);
    
    		if (count($result) > 0)
    		{
    			$this->set_expGXKBarchive_xls($result, $pageNumber, $pageSize, $targetws);
    
    			while ($pageNumber < $totalPage)
    			{
    				$pageNumber++;
    				$startNumber = ($pageNumber -1) * $pageSize;
    
    				$pagesql_select = $this->model->getPageSql($sql_select,null,$startNumber,$pageSize);
    
    				$result = $this->model->sqlQuery($pagesql_select,$bind);
    				if (count($result) > 0)
    				{
    					$this->set_expGXKBarchive_xls($result, $pageNumber, $pageSize, $targetws);
    				}
    			}
    
    		}
    	}

    	TaskMonitorModel::done(session("S_USER_NAME"));    	
    	 
    	if ($totalCount > 0)
    	{
    		ob_end_clean();
    		
    		$filename = "用于更欣课表的精准老师名单_" . date ( 'Y-m-d', time () );
    		header('Content-Type: application/vnd.ms-excel');
    		header("Content-Disposition: attachment;filename=".iconv('UTF-8','GB2312',$filename.".xls"));
    		header('Cache-Control: max-age=0');
    		
    		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    		header ('Cache-Control: cache, must-revalidate');
    		header ('Pragma: public');
    		
    		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    		$objWriter->save('php://output');
    		
    	}
    		
    }
    
    private function set_expGXKBarchive_xls($result, $pageNumber, $pageSize, $targetws) {
    	$row = ($pageNumber - 1) * $pageSize + 1;
    	foreach ( $result as $iresult )
    	{
    		if ($iresult)
    		{
    			$row++;
    				
    			$targetws->insertNewRowBefore ( $row, 1 ); //插入新的行
    				
    			$targetws->setCellValue ( 'A' . $row, $row - 1); //序号
    			$targetws->setCellValueExplicit ( 'B' . $row, $iresult['NAME'], PHPExcel_Cell_DataType::TYPE_STRING ); //名字
    			$targetws->setCellValueExplicit ( 'C' . $row, $iresult['NAME'], PHPExcel_Cell_DataType::TYPE_STRING ); //全称
    			$targetws->setCellValueExplicit ( 'D' . $row, $iresult['TEACHERNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //简称1
    			$targetws->setCellValueExplicit ( 'E' . $row, $iresult['TEACHERNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //简称2
    			$targetws->setCellValueExplicit ( 'F' . $row, $iresult['TEACHERNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //注脚
    			$targetws->setCellValueExplicit ( 'G' . $row, $iresult['TEACHERNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //代码
    				
    			$targetws->setCellValueExplicit ( 'I' . $row, "0个", PHPExcel_Cell_DataType::TYPE_STRING ); //成员
    				
    				
    			TaskMonitorModel::next(session("S_USER_NAME"),$row - 1,true,$iresult['TEACHERNO'],2);
    		}
    	}
    }

    /**
     * excel导出更欣课表场地名单
     */
    public function excelexpGXKBroom(){
    
    	TaskMonitorModel::init(session("S_USER_NAME"), "excel导出用于更欣课表的场地名单");
    
    	$pageSize = 500;
    	 
    	$bind = array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],
    			":COURSENO"=>doWithBindStr($_POST["COURSENO"]),":GROUP"=>$_POST["GROUP"],
    			":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"],
    			":COURSETYPE"=>$_POST["COURSETYPE"],":XCTYPE"=>$_POST["XCTYPE"],
    			":SCHEDULED"=>$_POST["SCHEDULED"],":ROOMTYPE"=>$_POST["ROOMTYPE"],
    			":CLASSNO"=>doWithBindStr($_POST["CLASSNO"]),":EXAMTYPE"=>$_POST["EXAMTYPE"],
    			":ESTIMATEUP"=>$_POST["ESTIMATEUP"],":ESTIMATEDOWN"=>$_POST["ESTIMATEDOWN"],
    			":ATTENDENTSUP"=>$_POST["ATTENDENTSUP"],":ATTENDENTSDOWN"=>$_POST["ATTENDENTSDOWN"],
    			":DAYS"=>$_POST["DAYS"]);
    		
    	$sql_count = " select COUNT(*) as icount from ( "
    			
    			   . " SELECT distinct ltrim(rtrim(CLASSROOMS.ROOMNO)) as ROOMNO, ltrim(rtrim(CLASSROOMS.NO)) as NO "
    			   . " FROM SCHEDULEPLAN "
    			   . " inner JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    			   . " inner JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    			   		
    			   . " LEFT JOIN "	
    			   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    			   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    			   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    			   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    			   		
    			   . " left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    			   . " left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    			   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    			   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    			   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    			   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "
    			   . " left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "

    			   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    			   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "

    			   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    			   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    			   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "

    			   . " WHERE SCHEDULEPLAN.YEAR = :YEAR "
    			   . " AND SCHEDULEPLAN.TERM = :TERM "
    			   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    			   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    			   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    			   . " AND COURSES.TGROUP LIKE :TGROUP "
    			   . " AND COURSES.TYPE LIKE :COURSETYPE "
    			   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    			   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    			   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    			   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    			   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    			   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    			   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    			   . " {\$SQL.LOCK} "
    			   . " {\$SQL.EXAM} "
    			   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    			   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) "
    			   		
    			   . " ) x ";
    	
    	$sql_count = $this->formatScheduleplanSQL($sql_count);
    	$data = $this->model->sqlFind($sql_count,$bind);
    	$totalCount = $data['icount'];
    
    	if ($totalCount > 0)
    	{
    		TaskMonitorModel::run(session("S_USER_NAME"),"excel导出用于更欣课表的场地名单", $totalCount);
    
    		$inputFileType = 'Excel5';
    		$inputFileName = $_SERVER['DOCUMENT_ROOT'] . "\\res\\templates\\room_room_expGXKB.xls";
    
    		vendor("PHPExcel.PHPExcel");
    
    		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    		$objPHPExcel = $objReader->load($inputFileName);
    		$targetws = $objPHPExcel->getSheet(0);
    
    		$totalPage = ceil($totalCount / $pageSize);
    
    		$pageNumber = 1;
    		$startNumber = 0;
    
  		  	$sql_select = " select x.* from ( "
    			
    			   . " SELECT distinct ltrim(rtrim(CLASSROOMS.ROOMNO)) as ROOMNO, ltrim(rtrim(CLASSROOMS.NO)) as NO "
    			   . " FROM SCHEDULEPLAN "
    			   . " inner JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    			   . " inner JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    			   		
    			   . " LEFT JOIN "	
    			   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    			   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    			   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    			   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    			   		
    			   . " left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    			   . " left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    			   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    			   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    			   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    			   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "
    			   . " left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "

    			   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    			   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "

    			   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    			   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    			   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "

    			   . " WHERE SCHEDULEPLAN.YEAR = :YEAR "
    			   . " AND SCHEDULEPLAN.TERM = :TERM "
    			   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    			   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    			   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    			   . " AND COURSES.TGROUP LIKE :TGROUP "
    			   . " AND COURSES.TYPE LIKE :COURSETYPE "
    			   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    			   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    			   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    			   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    			   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    			   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    			   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    			   . " {\$SQL.LOCK} "
    			   . " {\$SQL.EXAM} "
    			   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    			   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) "
    			   		
    			   . " ) x order by x.ROOMNO ";
    	
    		$sql_select = $this->formatScheduleplanSQL($sql_select);
    		$pagesql_select = $this->model->getPageSql($sql_select,null,$startNumber,$pageSize);
    
    		$result = $this->model->sqlQuery($pagesql_select,$bind);
    
    		if (count($result) > 0)
    		{
    			$this->set_expGXKBroom_xls($result, $pageNumber, $pageSize, $targetws);
    
    			while ($pageNumber < $totalPage)
    			{
    				$pageNumber++;
    				$startNumber = ($pageNumber -1) * $pageSize;
    
    				$pagesql_select = $this->model->getPageSql($sql_select,null,$startNumber,$pageSize);
    
    				$result = $this->model->sqlQuery($pagesql_select,$bind);
    				if (count($result) > 0)
    				{
    					$this->set_expGXKBroom_xls($result, $pageNumber, $pageSize, $targetws);
    				}
    			}
    
    		}
    
    	}

    	TaskMonitorModel::done(session("S_USER_NAME"));    	
    	 
    	if ($totalCount > 0)
    	{
    		ob_end_clean();
    		
    		$filename = "用于更欣课表的场地名单_" . date ( 'Y-m-d', time () );
    		header('Content-Type: application/vnd.ms-excel');
    		header("Content-Disposition: attachment;filename=".iconv('UTF-8','GB2312',$filename.".xls"));
    		header('Cache-Control: max-age=0');
    		
    		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    		header ('Cache-Control: cache, must-revalidate');
    		header ('Pragma: public');
    		
    		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    		$objWriter->save('php://output');
    		
    	}
    
    }
    
    private function set_expGXKBroom_xls($result, $pageNumber, $pageSize, $targetws) {
    	$row = ($pageNumber - 1) * $pageSize + 1;
    	foreach ( $result as $iresult )
    	{
    		if ($iresult)
    		{
    			$row++;
    
    			$targetws->insertNewRowBefore ( $row, 1 ); //插入新的行
    
    			$targetws->setCellValue ( 'A' . $row, $row - 1); //序号
    			$targetws->setCellValueExplicit ( 'B' . $row, $iresult['NO'], PHPExcel_Cell_DataType::TYPE_STRING ); //名字
    			$targetws->setCellValueExplicit ( 'C' . $row, $iresult['NO'], PHPExcel_Cell_DataType::TYPE_STRING ); //全称
    			$targetws->setCellValueExplicit ( 'D' . $row, $iresult['ROOMNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //简称1
    			$targetws->setCellValueExplicit ( 'E' . $row, $iresult['ROOMNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //简称2
    			$targetws->setCellValueExplicit ( 'F' . $row, $iresult['ROOMNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //注脚
    			$targetws->setCellValueExplicit ( 'G' . $row, $iresult['ROOMNO'], PHPExcel_Cell_DataType::TYPE_STRING ); //代码
    
    			$targetws->setCellValueExplicit ( 'I' . $row, "0个", PHPExcel_Cell_DataType::TYPE_STRING ); //成员
    
    
    			TaskMonitorModel::next(session("S_USER_NAME"),$row - 1,true,$iresult['ROOMNO'],2);
    		}
    	}
    }

    /**
     * excel导出更欣课表班级教学计划编辑
     */
    public function excelexpGXKBeditscheduleplan(){
    	
    	$this->assign('SEARCHTYPE',$_GET['SEARCHTYPE']);
    	$this->assign('YEAR',$_GET['YEAR']);
    	$this->assign('ROOMTYPE',$_GET['ROOMTYPE']);
    	$this->assign('TERM',$_GET['TERM']);
    	$this->assign('LOCK',$_GET['LOCK']);
    	$this->assign('COURSENO',$_GET['COURSENO']);
    	$this->assign('ESTIMATEUP',$_GET['ESTIMATEUP']);
    	$this->assign('GROUP',$_GET['GROUP']);
    	$this->assign('ESTIMATEDOWN',$_GET['ESTIMATEDOWN']);
    	$this->assign('SCHOOL',$_GET['SCHOOL']);
    	$this->assign('ATTENDENTSUP',$_GET['ATTENDENTSUP']);
    	$this->assign('COURSETYPE',$_GET['COURSETYPE']);
    	$this->assign('ATTENDENTSDOWN',$_GET['ATTENDENTSDOWN']);
    	$this->assign('SCHEDULED',$_GET['SCHEDULED']);
    	$this->assign('EXAM',$_GET['EXAM']);
    	$this->assign('TEACHERNO',$_GET['TEACHERNO']);
    	$this->assign('EXAMTYPE',$_GET['EXAMTYPE']);
    	$this->assign('DAYS',$_GET['DAYS']);
    	$this->assign('CLASSNO',$_GET['CLASSNO']);
    	$this->assign('XCTYPE',$_GET['XCTYPE']);
    	$this->assign('TGROUP',$_GET['TGROUP']);
    	
    	$this->display();
    }
    
    /**
     * excel导出更欣课表班级教学计划
     */
    public function excelexpGXKBscheduleplan(){
    	
    	if ( $_FILES["afile"]["type"] == "application/vnd.ms-excel" ){
    		$inputFileType = 'Excel5';
    	}
    	elseif ( $_FILES["afile"]["type"] == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ){
    		$inputFileType = 'Excel2007';
    	}
    	else {
    		echo "Type: " . $_FILES["afile"]["type"] . "<br />";
    		echo "非法的文件格式！";
    		exit();
    	}
    	
    	if ($_FILES["afile"]["error"] > 0)
    	{
    		echo "Error: " . $_FILES["afile"]["error"] . "<br />";
    		exit();
    	}
    	
    	$inputFileName = C("__UPFILE__").  getGUIDStr(session_id()).".".pathinfo($_FILES["afile"]["name"], PATHINFO_EXTENSION);  //$_FILES["afile"]["name"];
    	$suc = move_uploaded_file($_FILES["afile"]["tmp_name"], $inputFileName);    	 

    	
    	TaskMonitorModel::init(session("S_USER_NAME"), "excel导出用于更欣课表的班级教学计划");
    	
    	$pageSize = 500;
    	
    	//导入phpExcel
    	vendor("PHPExcel.PHPExcel");
    	
    	//设置php服务器可用内存，上传较大文件时可能会用到
    	ini_set('memory_limit', '1024M');    	

    	$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    	$objPHPExcel = $objReader->load($inputFileName);
    	 
    	$countsum = 0;
    	$countarr = array();
    	
    	$WorksheetInfo = $objReader->listWorksheetInfo($inputFileName);
    	
    	for ($isheet=0; $isheet<count($WorksheetInfo);$isheet++)
    	{
    		$isheetname = $WorksheetInfo[$isheet]["worksheetName"];

    		$bind = array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],
    				":COURSENO"=>doWithBindStr($_POST["COURSENO"]),":GROUP"=>$_POST["GROUP"],
    				":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"],
    				":COURSETYPE"=>$_POST["COURSETYPE"],":XCTYPE"=>$_POST["XCTYPE"],
    				":SCHEDULED"=>$_POST["SCHEDULED"],":ROOMTYPE"=>$_POST["ROOMTYPE"],
    				":CLASSNO"=>doWithBindStr($_POST["CLASSNO"]),":EXAMTYPE"=>$_POST["EXAMTYPE"],
    				":ESTIMATEUP"=>$_POST["ESTIMATEUP"],":ESTIMATEDOWN"=>$_POST["ESTIMATEDOWN"],
    				":ATTENDENTSUP"=>$_POST["ATTENDENTSUP"],":ATTENDENTSDOWN"=>$_POST["ATTENDENTSDOWN"],
    				":DAYS"=>$_POST["DAYS"]);    		
    		
    		$sql_count = " SELECT COUNT(*) as icount "
    				   . " FROM SCHEDULEPLAN "
       				   . " left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
					   . " left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    				   . " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    				   . " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    				   . " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    				   . " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "
    				   . " left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "
    		
    				   . " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    				   . " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "
    				   . " LEFT OUTER JOIN "
    		
    				   . " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    				   . " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    				   . " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    				   . " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    		
    				   . " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    				   . " LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    				   . " LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    				   . " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    				   . " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "
    		
    				   . " WHERE (ltrim(rtrim(CLASSES.CLASSNAME))+upper(ltrim(rtrim(CLASSES.CLASSNO)))+'计划') = '".trim($isheetname)."' " 
    				   . " AND SCHEDULEPLAN.YEAR = :YEAR "
    				   . " AND SCHEDULEPLAN.TERM = :TERM "
    				   . " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    				   . " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    				   . " AND COURSES.SCHOOL LIKE :SCHOOL "
    				   . " AND COURSES.TGROUP LIKE :TGROUP "
    				   . " AND COURSES.TYPE LIKE :COURSETYPE "
    				   . " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    				   . " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    				   . " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    				   . " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    				   . " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    				   . " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    				   . " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    				   . " {\$SQL.LOCK} "
    				   . " {\$SQL.EXAM} "
    				   . " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    				   . " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) ";
    		 
    		$sql_count = $this->formatScheduleplanSQL($sql_count);
    		
    		$data = $this->model->sqlFind($sql_count,$bind);
    		$totalCount = $data['icount'];
    		
    		var_dump($totalCount);

    		    		
    		$countsum += $totalCount;
    		$countarr[$isheet] = $totalCount;
    		
    	}

    	
    	if ($countsum > 0)
    	{
    		$xxcount = 0;
    		TaskMonitorModel::run(session("S_USER_NAME"),"excel导出用于更欣课表的班级教学计划", $countsum);
    		
    		for ($isheet=0; $isheet<count($WorksheetInfo);$isheet++)
    		{
    			$isheetname = $WorksheetInfo[$isheet]["worksheetName"];
    			
    			$totalCount = $countarr[$isheet];    			
    			if ($totalCount > 0)
    			{
    				$bind = array(":YEAR"=>$_POST["YEAR"],":TERM"=>$_POST["TERM"],
    						":COURSENO"=>doWithBindStr($_POST["COURSENO"]),":GROUP"=>$_POST["GROUP"],
    						":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"],
    						":COURSETYPE"=>$_POST["COURSETYPE"],":XCTYPE"=>$_POST["XCTYPE"],
    						":SCHEDULED"=>$_POST["SCHEDULED"],":ROOMTYPE"=>$_POST["ROOMTYPE"],
    						":CLASSNO"=>doWithBindStr($_POST["CLASSNO"]),":EXAMTYPE"=>$_POST["EXAMTYPE"],
    						":ESTIMATEUP"=>$_POST["ESTIMATEUP"],":ESTIMATEDOWN"=>$_POST["ESTIMATEDOWN"],
    						":ATTENDENTSUP"=>$_POST["ATTENDENTSUP"],":ATTENDENTSDOWN"=>$_POST["ATTENDENTSDOWN"],
    						":DAYS"=>$_POST["DAYS"]);
    				
    				$targetws = $objPHPExcel->getSheet($isheet);    				

    				$totalPage = ceil($totalCount / $pageSize);
    				
    				$pageNumber = 1;
    				$startNumber = 0;
    				
    				$sql_select = " SELECT SCHEDULEPLAN.RECNO, SCHEDULEPLAN.YEAR, SCHEDULEPLAN.TERM, "
    						    . " SCHEDULEPLAN.COURSENO, (ltrim(rtrim(COURSES.COURSENAME)) + ltrim(rtrim(SCHEDULEPLAN.COURSENO)) + SCHEDULEPLAN.[GROUP]) as COURSE, "
    						    . " SCHEDULEPLAN.[GROUP], "
    							. " COURSEPLAN.CLASSNO, ltrim(rtrim(CLASSES.CLASSNAME)) + ltrim(rtrim(COURSEPLAN.CLASSNO)) as CLASS, "
    							. " THETEACHERS.TEACHERNO, (ltrim(rtrim(THETEACHERS.TEACHERNAME)) + ltrim(rtrim(THETEACHERS.TEACHERNO))) as TEACHER "
    				
    							. " FROM SCHEDULEPLAN "
    							. " left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO) "
    							. " left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP]) "
    							. " left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME) "
    							. " left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME "
    							. " left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2 "
    							. " left JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL "
    							. " left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO "
    				
    							. " LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME "
    							. " LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME "
    							. " LEFT OUTER JOIN "
    				
    							. " (SELECT TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS "
    							. " FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO "
    							. " LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE "
    							. " ) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP "
    				
    							. " LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME "
    							. " LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP]) "
    							. " LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO "
    							. " LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME "
    							. " LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE "
    				
    							. " WHERE (ltrim(rtrim(CLASSES.CLASSNAME))+upper(ltrim(rtrim(CLASSES.CLASSNO)))+'计划') = '".trim($isheetname)."' "
    							. " AND SCHEDULEPLAN.YEAR = :YEAR "
    							. " AND SCHEDULEPLAN.TERM = :TERM "
    							. " AND SCHEDULEPLAN.COURSENO LIKE :COURSENO "
    							. " AND SCHEDULEPLAN.[GROUP] LIKE :GROUP "
    							. " AND COURSES.SCHOOL LIKE :SCHOOL "
    							. " AND COURSES.TGROUP LIKE :TGROUP "
    							. " AND COURSES.TYPE LIKE :COURSETYPE "
    							. " AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED "
    							. " AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE "
    							. " AND COURSEPLAN.CLASSNO LIKE :CLASSNO "
    							. " AND COURSEPLAN.EXAMTYPE like :EXAMTYPE "
    							. " AND COURSEPLAN.COURSETYPE like :XCTYPE "
    							. " AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN) "
    							. " AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN) "
    							. " {\$SQL.LOCK} "
    							. " {\$SQL.EXAM} "
    							. " AND SCHEDULEPLAN.DAYS LIKE :DAYS "
    							. " AND (THETEACHERS.SCHOOL LIKE '%' OR THETEACHERS.SCHOOL IS NULL) ";
    				
    				$sql_select = $this->formatScheduleplanSQL($sql_select);
    				$order = " ORDER BY CLASSNO, COURSENO, RECNO ";
    				$pagesql_select = $this->model->getPageOrderSql($sql_select,$order,$startNumber,$pageSize);
    				
    				$result = $this->model->sqlQuery($pagesql_select,$bind);
    				if (count($result) > 0)
    				{
    					 
    					$this->set_expGXKBscheduleplan_xls($result, $pageNumber, $pageSize, $targetws, $xxcount);
    				
    					while ($pageNumber < $totalPage)
    					{
    						$pageNumber++;
    						$startNumber = ($pageNumber -1) * $pageSize;
    				
    						$pagesql_select = $this->model->getPageOrderSql($sql_select,$order,$startNumber,$pageSize);
    				
    						$result = $this->model->sqlQuery($pagesql_select,$bind);
    						if (count($result) > 0)
    						{
    							$this->set_expGXKBscheduleplan_xls($result, $pageNumber, $pageSize, $targetws, $xxcount);
    						}
    					}
    					
    					
    					$lastRow = $targetws->getHighestRow();
    					$targetws->removeRow($lastRow, 1);
    				
    				}
    				
    				
    				
    				
    				
    			}

    			
    			
    		}
    		
    		
    		
    	}     


    	TaskMonitorModel::done(session("S_USER_NAME"));
    	 
    	if ($countsum > 0)
    	{
    		ob_end_clean();
    		
    		$filename = "用于更欣课表的班级教学计划_" . date ( 'Y-m-d', time () );
    		header('Content-Type: application/vnd.ms-excel');
    		header("Content-Disposition: attachment;filename=".iconv('UTF-8','GB2312',$filename.".xls"));
    		header('Cache-Control: max-age=0');
    		
    		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    		header ('Cache-Control: cache, must-revalidate');
    		header ('Pragma: public');
    		
    		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    		$objWriter->save('php://output');    		
    		
    	}
    		
    }
    
    private function set_expGXKBscheduleplan_xls($result, $pageNumber, $pageSize, $targetws, &$xxcount) {
    	$row = ($pageNumber - 1) * $pageSize + 1;
    	foreach ( $result as $iresult )
    	{
    		if ($iresult)
    		{
    			$xxcount++;
    			$row++;
    				
    			$targetws->insertNewRowBefore ( $row, 1 ); //插入新的行
    				
    			$targetws->setCellValue ( 'A' . $row, $row - 1); //序号
    			$targetws->setCellValueExplicit ( 'B' . $row, $iresult['COURSE'], PHPExcel_Cell_DataType::TYPE_STRING ); //课程
    			$targetws->setCellValueExplicit ( 'C' . $row, $iresult['CLASS'], PHPExcel_Cell_DataType::TYPE_STRING ); //任教班级
    			$targetws->setCellValueExplicit ( 'D' . $row, $iresult['TEACHER'], PHPExcel_Cell_DataType::TYPE_STRING ); //任课老师    				
    			$targetws->setCellValueExplicit ( 'E' . $row, "1次", PHPExcel_Cell_DataType::TYPE_STRING ); //任课老师
    			$targetws->setCellValueExplicit ( 'F' . $row, "自动", PHPExcel_Cell_DataType::TYPE_STRING ); //任课老师
    			    
    			TaskMonitorModel::next(session("S_USER_NAME"),$xxcount,true,$iresult['RECNO'],2);
    		}
    	}
    }    

    //todo:导出excel
    public function courseplan_one(){
        echo '<pre>';
        print_r($_REQUEST);
        exit;
        $bind = $this->model->getBind("YEAR,TERM,COURSENO,GROUP,SCHOOL,COURSETYPE,SCHEDULED,ROOMTYPE,CLASSNO,EXAMTYPE,ESTIMATEUP,ESTIMATEDOWN,ATTENDENTSUP,ATTENDENTSDOWN,DAYS",$_REQUEST,"%");
        $sql = $this->model->getSqlMap("coursePlan/QueryScheduleplan.sql");
        $arr = $this->model->sqlQuery($this->formatScheduleplanSQL($sql), $bind);
        echo '<pre>';
        print_r($arr);

    }

    private function  formatScheduleplanSQL($sql){
        if(isset($_REQUEST["LOCK"]) && ($_REQUEST["LOCK"]==1 || $_REQUEST["LOCK"]==0))
            $sql = str_replace('{$SQL.LOCK}',"AND SCHEDULEPLAN.LOCK=".intval($_REQUEST["LOCK"]),$sql);
        else
            $sql = str_replace('{$SQL.LOCK}',"",$sql);
        if(isset($_REQUEST["EXAM"]) && ($_REQUEST["EXAM"]==1 || $_REQUEST["EXAM"]==0))
            $sql = str_replace('{$SQL.EXAM}',"AND SCHEDULEPLAN.EXAM=".intval($_REQUEST["EXAM"]),$sql);
        else
            $sql = str_replace('{$SQL.EXAM}',"",$sql);
        $searchType = intval($_REQUEST["SEARCHTYPE"]);
        if($searchType==2){
            $sql = str_replace('{$SQL.SRARCHTYPE}',"AND THETEACHERS.TEACHERNAME IS NULL",$sql);
        }elseif($searchType==3){
            $sql = str_replace('{$SQL.SRARCHTYPE}',"AND (THETEACHERS.TEACHERNAME IS NOT NULL AND L_ZC.JB='初级')",$sql);
        }elseif($searchType==4){
            $sql = str_replace('{$SQL.SRARCHTYPE}',"AND COURSEPLAN.CLASSNO LIKE '000000%'",$sql);
        }else{
            $sql = str_replace('{$SQL.SRARCHTYPE}',"AND (THETEACHERS.SCHOOL LIKE '".$_REQUEST["TEACHERNO"]."' OR THETEACHERS.SCHOOL IS NULL)",$sql);
        }
        return $sql;
    }

    /**
     * 更新排课计划
     */
    public function update(){
        if($this->_hasJson){
            $bind = $this->model->getBind("RECNO",$_REQUEST,"%");
            $data = $this->model->sqlFind($this->model->getSqlMap("coursePlan/SelectScheduleplanByRecNO.sql"), $bind);
            if($data==null || ($this->theacher["SCHOOL"]!=$data["SCHOOL"]&&!isDeanByUsername(getUsername()))){
                $this->message["type"] = "error";
                $this->message["message"] = "你不可以更改别的学部开设的课程属性！";
                $this->__done();
            }
            $bind = $this->model->getBind("ESTIMATE,EBATCH,EMPROOM,ROOMTYPE,REM,SEATSLOCK,TIME,SCHEDULED,LHOURS,EHOURS,CHOURS,KHOURS,SHOURS,ZHOURS,LOCK,EXAM,DAYS,AREA,RECNO",$_REQUEST);
            //去除degree
//            $bind = $this->model->getBind("ESTIMATE,EBATCH,degree,EMPROOM,ROOMTYPE,REM,SEATSLOCK,TIME,SCHEDULED,LHOURS,EHOURS,CHOURS,KHOURS,SHOURS,ZHOURS,LOCK,EXAM,DAYS,AREA,RECNO",$_REQUEST);
            $data = $this->model->sqlExecute($this->model->getSqlMap("coursePlan/updateSchedulePlan.sql"),$bind);
            if($data===false){
                $this->message["type"] = "error";
                $this->message["message"] = "<font color='red'>更新失败！</font>";
                $this->__done();
            }
            $this->message["type"] = "info";
            $this->message["message"] = "更新成功！";
            $this->__done();
        }
        $bind = $this->model->getBind("YEAR,TERM,COURSENO,GROUP",$_REQUEST,"%");
        $data = $this->model->sqlFind($this->model->getSqlMap("coursePlan/SelectScheduleplan.sql"), $bind);
        if($data) {
            $data["WEEKS"] = strrev(sprintf("%018s", decbin($data["WEEKS"])));
        }
//        vardump($data);
        $this->assign("schedulePlan",$data);
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->__done("update");
    }

    /**
     * 删除排课计划
     */
    public function delete(){
//        if(!isDeanByUsername(getUsername())){
//            $this->message["type"] = "error";
//            $this->message["message"] = "只有教务处人员才可以删除开课记录！";
//            $this->__done();
//        }
        
        $isdean = isDeanByUsername(getUsername());
        $user_school = $_SESSION["S_USER_INFO"]["SCHOOL"];
        
        //todo: 检测传入的参数
        if(is_array($_REQUEST['ITEM'])==false || count($_REQUEST['ITEM'])==0){
            $this->message["type"] = "error";
            $this->message["message"] = "输入的参数有错误，非法提交数据！";
            $this->__done();
        }

        $arr = array();
        foreach($_REQUEST['ITEM'] as $row){
            $items = @explode(",",$row);
            if(count($items)!=5) continue;
            $year = intval($items[0]);
            $term = intval($items[1]);
            $courseno = trim($items[2]);
            $group = trim($items[3]);
            $school = trim($items[4]);
        	        	 
        	if($isdean || ($user_school==$school)) 
        	{
        		$item["year"] = $year;
       			$item["term"] = $term;
       			$item["courseno"] = $courseno;
       			$item["group"] = $group;
       			$item["school"] = $school;
       			
       			array_push($arr, $item);
        	}
        }
         
        if (count($arr)==0)
        {
        	$this->message["type"] = "error";
        	$this->message["message"] = "只有教务处人员或本学部人员才可以删除开课记录！";
        	$this->__done();
        }
        
        
//        foreach($_REQUEST['ITEM'] as $row){
//            $items = @explode(",",$row);
//            if(count($items)!=5) continue;
//            $year = intval($items[0]);
//            $term = intval($items[1]);
//            $courseno = trim($items[2]);
//            $group = trim($items[3]);
//            $school = trim($items[4]);
        foreach($arr as $row){
            $year = $row["year"];
            $term = $row["term"];
            $courseno = $row["courseno"];
            $group = $row["group"];
            $school = $row["school"];
            
            //todo: 排课计划是否存在
            $bind = $this->model->getBind("YEAR,TERM,COURSENO,GROUP", array($year,$term,$courseno,$group));
            $data = $this->model->sqlQuery("SELECT RECNO FROM SCHEDULEPLAN WHERE YEAR=:YEAR AND TERM=:TERM AND COURSENO=:COURSENO AND [GROUP]=:GROUP",$bind);
            if($data==null || count($data)!=1) {
                $this->message["message"] .= "<font color='red'>排课号[$courseno][$group]不存在！</font><br />";
                continue;
            }

            $this->model->startTrans();
            $strBind  = "YEAR1,TERM1,COURSENO1,GROUP1,YEAR2,TERM2,COURSENO2,GROUP2,RECNO3,RECNO4,";
            $strBind .= "YEAR5,TERM5,COURSENO5,GROUP5,YEAR6,TERM6,COURSENO6,GROUP6";
            $bind = $this->model->getBind($strBind,
                array(
                    $year, $term, $courseno,$group,$year, $term, $courseno,$group,$data[0]["RECNO"],$data[0]["RECNO"],
                    $year, $term, $courseno,$group,$year, $term, $courseno,$group));
            $data = $this->model->sqlExecute($this->model->getSqlMap("coursePlan/deleteSchedulePlan.sql"),$bind);
            if($data===false){
                $this->model->rollback();
                $this->message["message"] .= "<font color='red'>排课号[$courseno][$group]，删除失败！</font><br />";
                $this->model->commit();
                continue;
            }
            $this->model->commit();
            $this->message["message"] .= "排课号[$courseno][$group]，删除成功！<br />";
        }
        $this->message["type"] = "info";
        $this->__done();
    }


    /**
     * 下拉选择教师
     */
    public function get_teacher_list()
    {
    	$prmTEACHERNO = "%";
    	$prmNAME = "%";    		
    	if ($_POST["q"])
    	{
    		$prmTEACHERNO = trim($_POST["q"])."%";
    		$prmNAME = "%".trim($_POST["q"])."%";    		
    	}
    	
    	$bind = array(":TEACHERNO"=>$prmTEACHERNO,":NAME"=>$prmNAME);
    	$teachers = $this->model->sqlQuery("select top 30 rtrim(TEACHERNO) as id, rtrim(TEACHERNO) + ' / ' + rtrim(NAME) as text from TEACHERS where TEACHERNO like :TEACHERNO or NAME like :NAME order by TEACHERNO", $bind);
    	$this->ajaxReturn($teachers,"JSON");
    }


    
    
    /**
     * 编辑教师
     */
    public function teacher($RECNO=null){
        $bind = $this->model->getBind("RECNO",$_REQUEST);
        if($this->_hasJson){
			$courseplanModel = new CoursePlanModel();
			$list = $courseplanModel->listScheduleplanTeachersByTeacherplanMap($RECNO,$this->_pageDataIndex,$this->_pageSize);
            $this->ajaxReturn($list,"JSON");
        }
        $data = $this->model->sqlFind($this->model->getSqlMap("coursePlan/SelectScheduleplanByRecNO.sql"), $bind);

        $this->assign("schedulePlan",$data);

        if($data!=null){
            $schoolname = $data["SCHOOLNAME"];
            if(strpos($schoolname,'-') !== false){
                $schoolname = strstr($schoolname,'-',true);
            }
            $data = $this->model->sqlQuery($this->model->getSqlMap("coursePlan/teachersBySchoolName.sql"), array(":SCHOOLNAME"=>doWithBindStr($schoolname)));
            //  $data = $this->model->sqlQuery($this->model->getSqlMap("coursePlan/teachersBySchoolName.sql"),array(':SCHOOL'=>));
            $this->assign("selfTeachers",$data);
        }
        $this->assign('schools',getSchoolList());
        $this->assign("queryParams",count($_REQUEST)>0?json_encode($_REQUEST):"{}");
        $this->__done("teacher");
    }


    /**
     * 新增教师
     */
    public function teacherSave(){
        //todo: 检测传入的参数
        if(VarIsSet("YEAR,TERM,MAP,TEACHERNO,HOURS,UNIT,REM,TASK,TOSCHEDULE")==false){
            $this->message["type"] = "error";
            $this->message["message"] = "输入的参数有错误，非法提交数据！";
            $this->__done();
        }
        //todo 检测指定的教师是否存在
        $bind = $this->model->getBind("TEACHERNO",$_REQUEST,"%");
        $data = $this->model->sqlFind("SELECT SCHOOL FROM TEACHERS WHERE TEACHERNO=:TEACHERNO", $bind);
        if($data==null){
            $this->message["type"] = "error";
            $this->message["message"] = "教师号[".$_REQUEST['TEACHERNO']."]不存在！";
            $this->__done();
        }
        //todo 插入教师
        $bind = $this->model->getBind("YEAR,TERM,MAP,TEACHERNO,HOURS,UNIT,REM,TASK,TOSCHEDULE",$_REQUEST);
        $sql  = "INSERT INTO TEACHERPLAN (YEAR,TERM,MAP,TEACHERNO,HOURS,UNIT,REM,TASK,TOSCHEDULE) VALUES ";
        $sql .= "(:YEAR,:TERM,:MAP,:TEACHERNO,:HOURS,:UNIT,:REM,:TASK,:TOSCHEDULE)";
        $data = $this->model->sqlExecute($sql,$bind);
        if(!$data){
            $this->message["type"] = "error";
            $this->message["message"] = "<font color='red'>教师号[".$_REQUEST['TEACHERNO']."]添加失败！".$this->model->getDbError()."</font>";
        }else{
            $this->message["type"] = "info";
            $this->message["insertId"] = $this->model->getLastInsID();
            $this->message["message"] = "教师号[".$_REQUEST['TEACHERNO']."]添加成功！";
        }
        $this->__done();
    }

    /**
     * 更新教师
     */
    public function teacherUpdate(){
        //todo: 检测传入的参数
        if(VarIsSet("YEAR,TERM,MAP,TEACHERNO,HOURS,UNIT,REM,TASK,TOSCHEDULE,RECNO")==false){
            $this->message["type"] = "error";
            $this->message["message"] = "输入的参数有错误，非法提交数据！";
            $this->__done();
        }
        //todo 检测指定的教师是否存在
        $bind = $this->model->getBind("TEACHERNO",$_REQUEST,"%");
        $data = $this->model->sqlFind("SELECT SCHOOL FROM TEACHERS WHERE TEACHERNO=:TEACHERNO", $bind);
        if($data==null){
            $this->message["type"] = "error";
            $this->message["message"] = "教师号[".$_REQUEST['TEACHERNO']."]不存在！";
            $this->__done();
        }
        //todo 修改教师
        $bind = $this->model->getBind("TEACHERNO,HOURS,UNIT,REM,TASK,TOSCHEDULE,RECNO",$_REQUEST);
        $sql  = "UPDATE TEACHERPLAN SET TEACHERNO=:TEACHERNO,HOURS=:HOURS,UNIT=:UNIT,REM=:REM,TASK=:TASK,TOSCHEDULE=:TOSCHEDULE WHERE RECNO=:RECNO";
        $data = $this->model->sqlExecute($sql,$bind);
        if(!$data){
            $this->message["type"] = "error";
            $this->message["message"] = "<font color='red'>教师号[".$_REQUEST['TEACHERNO']."]添加失败！".$this->model->getDbError()."</font>";
        }else{
            $this->message["type"] = "info";
            $this->message["message"] = "教师号[".$_REQUEST['TEACHERNO']."]添加成功！";
        }
        $this->__done();
    }

    /**
     * 删除教师
     */
    public function teacherDelete(){
        if(!is_array($_REQUEST["ITEMS"]) || count($_REQUEST["ITEMS"])==0){
            $this->message["type"] = "error";
            $this->message["message"] = "输入的参数有错误，非法提交数据！";
            $this->__done();
        }

        $count = 0;
        foreach($_REQUEST["ITEMS"] as $recno){
            $bind = $this->model->getBind("RECNO",$recno);
            $data = $this->model->sqlExecute("DELETE TEACHERPLAN WHERE RECNO=:RECNO",$bind);
            if($data!==false) $count++;
        }

        $this->message["type"] = "info";
        $this->message["message"] = $count."条记录已成功删除！";
        $this->__done();
    }

    /**
     * 将选中记录传递到课程总表
     */
    public function transfer(){
        if(!is_array($_REQUEST["ITEMS"]) || count($_REQUEST["ITEMS"])==0){
            $this->message["type"] = "error";
            $this->message["message"] = "输入的参数有错误，非法提交数据！";
            $this->__done();
        }

        $count = 0;
        foreach($_REQUEST["ITEMS"] as $recno){
            $bind = $this->model->getBind("RECNO",$recno);
            $data = $this->model->sqlExecute($this->model->getSqlMap("coursePlan/insertTransfer.sql"),$bind);
            if($data!==false) $count++;
        }

        $this->message["type"] = "info";
        $this->message["message"] = $count."条记录传递到课程总表！";
        $this->__done();
    }



    public function gongxuanke(){


       $this->model->startTrans();
        $int=$this->model->sqlExecute($this->model->getSqlMap('coursePlan/New_gongxuanke.SQL'),
            array(':yone'=>$_POST['YEAR'],':tone'=>$_POST['TERM'],':zhong'=>'%'.$_POST['TERM'].'%',':ytwo'=>$_POST['YEAR'],':ttwo'=>$_POST['TERM']));

       if($int===false){

           $this->model->rollback();
           exit(iconv('gb2312','utf-8',substr($this->model->getDbError(),0,strpos($this->model->getDbError(),'[ SQL语句 ]'))));
       }else{
           var_dump($int);
           $this->model->commit();
           exit('导入成功共');
       }


    }




}