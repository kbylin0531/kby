<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 14-5-23
 * Time: 下午12:00
 */
class IndexAction extends RightAction {
    private $model;
    protected $message = array("type"=>"info","message"=>"","dbError"=>"");

    public function __construct(){
        parent::__construct();
        $this->model = new ScheduleModel();
    }

    public function index(){
        $this->assign("yearTerm",$this->model->sqlFind("select * from YEAR_TERM where [TYPE]='O'"));
        $this->__done("index");
    }

    /**
     * 自动创建课程总表
     */
    public function auto(){
        if(VarIsIntval("YEAR,TERM")==false){
            $this->exitWithReport('无法获取提交的学年学期数据，执行失败！');
        }
        $year = $_REQUEST['YEAR'];
        $term = $_REQUEST['TERM'];

        $bind = array(
            ':YEAR'=>$year,
            ':TERM'=>$term,
        );
        //删除旧的排课计划
        if(trim($_POST['checked']) == '1'){
            $rst = $this->model->deleteSchedule($bind);
            if(is_string($rst)){
                $this->exitWithReport("删除第[{$year}]学年第[{$term}]学期的旧的课程总表(排课计划)失败了");
            }
        }

        $sql = "
SELECT
	dbo.getone(scheduleplan.YEAR) as  YEAR,
	dbo.getone(scheduleplan.TERM) as  TERM,
	dbo.getone(scheduleplan.COURSENO) as  COURSENO,
	dbo.getone(scheduleplan.[GROUP]) as  [GROUP],
	dbo.getone(scheduleplan.ESTIMATE) as  ESTIMATE,
	dbo.getone(scheduleplan.ATTENDENTS) as  ATTENDENTS,
	dbo.getone(scheduleplan.ROOMTYPE) as  ROOMTYPE,
	dbo.getone(scheduleplan.LHOURS) as  LHOURS,
	dbo.getone(scheduleplan.EHOURS) as  EHOURS,
	dbo.getone(scheduleplan.WEEKS) as  WEEKS,
	dbo.getone(scheduleplan.RECNO) as  SCHEDULEPLANRECNO,
	dbo.GROUP_CONCAT(RTRIM(teacherplan.TEACHERNO),',')  as TEACHERNO,
	dbo.getone(teacherplan.HOURS) as  HOURS,
	dbo.getone(teacherplan.UNIT) as  UNIT,
	dbo.getone(teacherplan.REM) as  REM,
	dbo.getone(scheduleplan.EMPROOM) as  EMPROOM,
	dbo.getone(scheduleplan.TIME) as  TIME,
	dbo.getone(scheduleplan.SCHEDULED) as  SCHEDULED,
	dbo.getone(scheduleplan.EBATCH) as  EBATCH,
	dbo.getone(scheduleplan.DAYS) as  DAYS,
	dbo.getone(scheduleplan.AREA) as  AREA,
	dbo.getone(teacherplan.TASK) as  TASK,
	dbo.getone(teacherplan.AMOUNT) as  AMOUNT,
	dbo.getone(teacherplan.TOSCHEDULE) as  TOSCHEDULE,
	dbo.getone(teacherplan.RECNO) as  RECNO,
	dbo.getone(COURSEPLAN.course_type_options) as  TYPE
FROM SCHEDULEPLAN
LEFT OUTER JOIN TEACHERPLAN ON SCHEDULEPLAN.RECNO=TEACHERPLAN.MAP
INNER JOIN COURSEPLAN ON SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO
WHERE scheduleplan.YEAR=:YEAR
  AND scheduleplan.TERM=:TERM
  AND teacherplan.TEACHERNO IS NOT NULL
  AND teacherplan.TEACHERNO<>''
  AND teacherplan.TEACHERNO<>'000000'
GROUP BY scheduleplan.YEAR,
       scheduleplan.TERM,
       scheduleplan.COURSENO,
       scheduleplan.[GROUP]
ORDER BY scheduleplan.COURSENO,
         scheduleplan.[GROUP]";
        $data = $this->model->sqlQuery($sql, $bind);
        if($data==null){
            $this->message["type"] = "info";
            $this->message["message"] = "没有任何排课计划需要导入！";
            $this->__done();
        }
		$lines = 0;
        $sql = "INSERT INTO SCHEDULE (YEAR,TERM,COURSENO,[GROUP],OEW,WEEKS,LE,MAP,UNIT,TIMER,ROOMR,EMPROOM) VALUES "
                ."(:YEAR,:TERM,:COURSENO,:GROUP,:OEW,:WEEKS,:TASK,:RECNO,:UNIT,:TIME,:ROOMTYPE,:EMPROOM)";
        foreach($data as $row){
            $max = floor($row["HOURS"]/$row["UNIT"]);
            for($i=0;$i<$max;$i++){
                $bind = $this->model->getBind("YEAR,TERM,COURSENO,GROUP,OEW,WEEKS,TASK,RECNO,UNIT,TIME,ROOMTYPE,EMPROOM", $row);
                $bind[":OEW"] = "B";
                $rst = $this->model->sqlExecute($sql, $bind);
				if($rst === 1) ++$lines;
            }
            $mod = $row["HOURS"]-$row["UNIT"]*$max;
            if($mod==0) continue;
            elseif($mod*2>$row["UNIT"]) $oew = "E";
            else $oew = "O";

            trace($row["HOURS"]."%".$row["UNIT"]."=".$mod."(".$oew.")");

            $bind = $this->model->getBind("YEAR,TERM,COURSENO,GROUP,OEW,WEEKS,TASK,RECNO,UNIT,TIME,ROOMTYPE,EMPROOM", $row);
            $bind[":OEW"] = $oew;
            $rst = $this->model->sqlExecute($sql, $bind);
			if($rst === 1){
				++$lines;
			}
        }

        //todo 设置教师已传递到排课表
        //$bind = $this->model->getBind("YEAR,TERM", $_REQUEST);
        //$this->model->sqlExecute("UPDATE SCHEDULEPLAN SET TOSCHEDULE=1 WHERE YEAR=:YEAR AND TERM=:TERM AND TEACHERNO is not null AND TEACHERNO<>'' AND <>'000000'", $bind);

        $this->message["type"] = "info";
        $this->message["message"] = $_REQUEST["YEAR"]."年".$_REQUEST["TERM"]."学期排课计划成功导入{$lines}！";
        $this->__done("auto");
    }

    /**
     * excel更欣课表导入编辑
     */
    public function excelimpedit(){
    
    	$this->display();
    }

    /**
     * excel更欣课表导入保存
     */
    public function excelimpsave(){
    
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
    
    	TaskMonitorModel::init(session("S_USER_NAME"), "更欣课表excel导入");
    
    	//导入phpExcel
    	vendor("PHPExcel.PHPExcel");
    
    	//设置php服务器可用内存，上传较大文件时可能会用到
    	ini_set('memory_limit', '1024M');
    
    	$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    
    	$WorksheetInfo = $objReader->listWorksheetInfo($inputFileName);
    
    	//读取文件最大行数、列数，偶尔会用到。
    	$maxRows = $WorksheetInfo[0]['totalRows'];
    	$maxColumn = $WorksheetInfo[0]['totalColumns'];
    	//列数可用于粗略判断所上传文件是否符合模板要求
    
    	//设置只读，可取消类似"3.08E-05"之类自动转换的数据格式，避免写库失败
    	$objReader->setReadDataOnly(true);
    
    	$objPHPExcel = $objReader->load($inputFileName);

    	$currentSheet = $objPHPExcel->getSheet(0);
    	
    	$allRow = $currentSheet->getHighestRow();

    	TaskMonitorModel::run(session("S_USER_NAME"),"更欣课表excel导入", $allRow-6+1+5);
    	 
    	$result = array();
    	$count = 0;
    	$succount = 0;
    	$failcount = 0;
    	
    	$mzts = $_POST["mzts"];
    	$mtjs = 0;
    	if ($_POST["mtswjs"])
    	{
    		$mtjs += $_POST["mtswjs"];
    	}
    	if ($_POST["mtxwjs"])
    	{
    		$mtjs += $_POST["mtxwjs"];
    	}
    	if ($_POST["mtzzxjs"])
    	{
    		$mtjs += $_POST["mtzzxjs"];
    	}
    	if ($_POST["mtwzxjs"])
    	{
    		$mtjs += $_POST["mtwzxjs"];
    	}    
    	 
    	if ($mzts > 0 && $mtjs > 0)
    	{
    		$temp_COURSENO_TEACHERNO_array = array();    		
    		$temp_COURSE_TEACHER_array = array();    		
    		$temp_CLASSNOarray = array();
    		$temp_CLASSarray = array();
    		$temp_COURSENOarray = array();
    		$temp_COURSEarray = array();
    		$temp_TEACHERNOarray = array();
    		$temp_TEACHERarray = array();
    		$temp_ROOMNOarray = array();
    		$temp_ROOMarray = array();
    		
    		for($currentRow = 6; $currentRow <= $allRow; $currentRow++)
    		{
    			$col_CLASS = 0;    			
    			$CLASSNO = $currentSheet->getCellByColumnAndRow($col_CLASS, $currentRow)->getValue();
    			 
    			$COURSENOarray = array();
    			$COURSEarray = array();
    			for ($i_day=1; $i_day<=$mzts; $i_day++)
    			{
    				for ($i_js=1; $i_js<=$mtjs; $i_js++)
    				{
    					$col_COURSE = ($i_day - 1) * $mtjs + $i_js;
    					
    					$COURSE = $currentSheet->getCellByColumnAndRow($col_COURSE, $currentRow)->getValue();
    					
    					$COURSEdata = explode(chr(10),$COURSE);
    					$COURSENO = $COURSEdata[0];
    					$TEACHERNO = $COURSEdata[1];
    					$ROOMNO = "000000000";
    					if (count($COURSEdata)==3)
    					{
    						$ROOMNO = $COURSEdata[2];
    					}
    					
    					
    					if ($COURSENO)
    					{
    						if (in_array($COURSENO, $COURSENOarray))
    						{ 
    							$timesarray = array();    							
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => strtoupper(dechex($i_js))));
    							
    							$timesarray = array_merge($COURSEarray[$COURSENO]["TIMES"], $timesarray);
    							
    							$COURSEarray[$COURSENO]["TIMES"] = $timesarray;
    							
    						}
    						else 
    						{
    							array_push($COURSENOarray, $COURSENO);   

    							$COURSEarray[$COURSENO] = array("TEACHERNO" => $TEACHERNO, "ROOMNO" => $ROOMNO);
    							
    							$timesarray = array();    							
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => strtoupper(dechex($i_js))));
    							
    							$COURSEarray[$COURSENO]["TIMES"] = $timesarray;
    						}
    						
    						
    					}
    					
    					
    				}
    				
    				
    			}
    			
    			foreach ($COURSENOarray as $COURSENO) 
    			{
    				$TEACHERNO = $COURSEarray[$COURSENO]["TEACHERNO"];
    				$ROOMNO = $COURSEarray[$COURSENO]["ROOMNO"];    				
    				$temptimesarray = $COURSEarray[$COURSENO]["TIMES"];
    				
    				$timesarray = array();
    				foreach ($temptimesarray as $time)
    				{
    					$i_day = $time["i_day"];
    					$i_js = $time["i_js"];   

    					if ($timesarray[$i_day])
    					{
    						$timesarray[$i_day] .= ",".$i_js;
    					}
    					else
    					{
    						$timesarray[$i_day] = $i_js;
    					}    					
    				}
    				$COURSEarray[$COURSENO]["TIMES"] = $timesarray;    	
    				
    				foreach ($timesarray as $i_day => $i_js)
    				{
    					    	
    					if ($CLASSNO && $COURSENO && $TEACHERNO && $ROOMNO && $i_day && $i_js)
    					{
    						$COURSENO_TEACHERNO = $COURSENO."_".$TEACHERNO;
    						$ROOMNO_i_day_i_js = $ROOMNO."_".$i_day."_".$i_js;
    						if (in_array($COURSENO_TEACHERNO, $temp_COURSENO_TEACHERNO_array))
    						{    	
    							$ROOMNO_i_day_i_js_array = $temp_COURSE_TEACHER_array[$COURSENO_TEACHERNO]["ROOMNO_i_day_i_js"];
    							if (in_array($ROOMNO_i_day_i_js, $ROOMNO_i_day_i_js_array))
    							{    								
    							}
    							else
    							{
    								array_push($ROOMNO_i_day_i_js_array, $ROOMNO_i_day_i_js);
    								$temp_COURSE_TEACHER_array[$COURSENO_TEACHERNO]["ROOMNO_i_day_i_js"] = $ROOMNO_i_day_i_js_array;
    								
    								$ROOM_i_day_i_js_array = $temp_COURSE_TEACHER_array[$COURSENO_TEACHERNO]["ROOM_i_day_i_js"];
    								array_push($ROOM_i_day_i_js_array, array("ROOMNO" => $ROOMNO, "i_day" => $i_day, "i_js" => $i_js));
    								$temp_COURSE_TEACHER_array[$COURSENO_TEACHERNO]["ROOM_i_day_i_js"] = $ROOM_i_day_i_js_array;    								
    							}    												
    						}
    						else
    						{
    							array_push($temp_COURSENO_TEACHERNO_array, $COURSENO_TEACHERNO);
    						
    							$temp_COURSE_TEACHER_array[$COURSENO_TEACHERNO] = array("COURSENO" => $COURSENO, "TEACHERNO" => $TEACHERNO); 							
    							
    							$ROOMNO_i_day_i_js_array = array();
    							array_push($ROOMNO_i_day_i_js_array, $ROOMNO_i_day_i_js);					
    							$temp_COURSE_TEACHER_array[$COURSENO_TEACHERNO]["ROOMNO_i_day_i_js"] = $ROOMNO_i_day_i_js_array;
    							
    							$ROOM_i_day_i_js_array = array();
    							array_push($ROOM_i_day_i_js_array, array("ROOMNO" => $ROOMNO, "i_day" => $i_day, "i_js" => $i_js));
    							$temp_COURSE_TEACHER_array[$COURSENO_TEACHERNO]["ROOM_i_day_i_js"] = $ROOM_i_day_i_js_array;
    						}
    						
    						
    						if (in_array($CLASSNO, $temp_CLASSNOarray))
    						{
    							$timesarray = array();
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => $i_js));
    								
    							$timesarray = array_merge($temp_CLASSarray[$CLASSNO]["TIMES"], $timesarray);
    								
    							$temp_CLASSarray[$CLASSNO]["TIMES"] = $timesarray;
    								
    						}
    						else
    						{
    							array_push($temp_CLASSNOarray, $CLASSNO);
    								
    							$timesarray = array();
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => $i_js));
    								
    							$temp_CLASSarray[$CLASSNO]["TIMES"] = $timesarray;
    						}
    						
    						
    						if (in_array($COURSENO, $temp_COURSENOarray))
    						{
    							$timesarray = array();
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => $i_js));
    						
    							$timesarray = array_merge($temp_COURSEarray[$COURSENO]["TIMES"], $timesarray);
    						
    							$temp_COURSEarray[$COURSENO]["TIMES"] = $timesarray;
    						
    						}
    						else
    						{
    							array_push($temp_COURSENOarray, $COURSENO);
    						
    							$timesarray = array();
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => $i_js));
    						
    							$temp_COURSEarray[$COURSENO]["TIMES"] = $timesarray;
    						}


    						if (in_array($TEACHERNO, $temp_TEACHERNOarray))
    						{
    							$timesarray = array();
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => $i_js));
    						
    							$timesarray = array_merge($temp_TEACHERarray[$TEACHERNO]["TIMES"], $timesarray);
    						
    							$temp_TEACHERarray[$TEACHERNO]["TIMES"] = $timesarray;
    						
    						}
    						else
    						{
    							array_push($temp_TEACHERNOarray, $TEACHERNO);
    						
    							$timesarray = array();
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => $i_js));
    						
    							$temp_TEACHERarray[$TEACHERNO]["TIMES"] = $timesarray;
    						}


    						if (in_array($ROOMNO, $temp_ROOMNOarray))
    						{
    							$timesarray = array();
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => $i_js));
    						
    							$timesarray = array_merge($temp_ROOMarray[$ROOMNO]["TIMES"], $timesarray);
    						
    							$temp_ROOMarray[$ROOMNO]["TIMES"] = $timesarray;
    						
    						}
    						else
    						{
    							array_push($temp_ROOMNOarray, $ROOMNO);
    						
    							$timesarray = array();
    							array_push($timesarray, array("i_day" => $i_day, "i_js" => $i_js));
    						
    							$temp_ROOMarray[$ROOMNO]["TIMES"] = $timesarray;
    						}
    						
    					}				
    						    					
    				}
    				
			
    			}
    			
    			$count++;
    			TaskMonitorModel::next(session("S_USER_NAME"),$count,true,$CLASSNO,2);
    			
    		}
    		

    		$TIMESECTORS = array();
    		$data = $this->model->sqlQuery("select * from TIMESECTORS");
    		foreach($data as $row){
    			$TIMESECTORS[$row["TIMEBITS"]] = $row;
    		}

    		//更新SCHEDULE表
    		foreach($temp_COURSE_TEACHER_array as $temp_COURSE_TEACHER)
    		{
    			$COURSENO = $temp_COURSE_TEACHER["COURSENO"];
    			$TEACHERNO = $temp_COURSE_TEACHER["TEACHERNO"];

    			$bind = array(
    					':YEAR'=>$_POST["YEAR"],
    					':TERM'=>$_POST["TERM"],
    					':COURSENO'=>$COURSENO,
    					':TEACHERNO'=>$TEACHERNO  					
    			);
    			$sql = " SELECT scheduleplan.YEAR,scheduleplan.TERM,scheduleplan.COURSENO,scheduleplan.[GROUP],scheduleplan.ESTIMATE, "
    				 . " scheduleplan.ATTENDENTS,scheduleplan.ROOMTYPE,scheduleplan.LHOURS,scheduleplan.EHOURS,scheduleplan.WEEKS, "
    				 . " scheduleplan.RECNO AS SCHEDULEPLANRECNO,teacherplan.TEACHERNO,teacherplan.HOURS,teacherplan.UNIT,teacherplan.REM, "
    				 . " scheduleplan.EMPROOM,scheduleplan.TIME,scheduleplan.SCHEDULED,scheduleplan.EBATCH,scheduleplan.DAYS,scheduleplan.AREA, "
    				 . " teacherplan.TASK,teacherplan.AMOUNT,teacherplan.TOSCHEDULE,teacherplan.RECNO,courses.TYPE "
    				 . " FROM SCHEDULEPLAN "
    				 . " LEFT OUTER JOIN TEACHERPLAN ON SCHEDULEPLAN.RECNO=TEACHERPLAN.MAP "
    				 . " JOIN COURSES ON SCHEDULEPLAN.COURSENO=COURSES.COURSENO "
    				 . " WHERE scheduleplan.YEAR=:YEAR AND scheduleplan.TERM=:TERM "
    				 . " AND rtrim(SCHEDULEPLAN.COURSENO)+SCHEDULEPLAN.[GROUP] =:COURSENO AND teacherplan.TEACHERNO= :TEACHERNO ";
    			$scheduleplan = $this->model->sqlFind($sql, $bind);
    			 
    			if ($scheduleplan)
    			{    				
    				$sql = " delete from SCHEDULE from SCHEDULE, TEACHERPLAN where SCHEDULE.MAP=TEACHERPLAN.RECNO AND SCHEDULE.YEAR=:YEAR AND SCHEDULE.TERM=:TERM and rtrim(SCHEDULE.COURSENO)+SCHEDULE.[GROUP] =:COURSENO and TEACHERPLAN.TEACHERNO =:TEACHERNO ";
    				$xx = $this->model->sqlExecute($sql, $bind);    				
    				
    				$bind = array(
    						':COURSENO'=>$COURSENO,
    						':TEACHERNO'=>$TEACHERNO
    				);
    				$ROOM_i_day_i_js_array = $temp_COURSE_TEACHER["ROOM_i_day_i_js"];
    				
    				foreach($ROOM_i_day_i_js_array as $ROOM_i_day_i_js)
    				{
    					$ROOMNO = $ROOM_i_day_i_js["ROOMNO"];
    					$TIMES_DAY = $ROOM_i_day_i_js["i_day"];
    					$TIMES_SECTOR = $ROOM_i_day_i_js["i_js"];
    					$TIMES = cwebsSchedule::getLesson($TIMES_SECTOR);
    					
    					$sql = " INSERT INTO SCHEDULE (YEAR,TERM,COURSENO,[GROUP],WEEKS,LE,MAP,UNIT,ROOMR,EMPROOM,DAY,TIME,ROOMNO,OEW) VALUES "
    						 . " (:YEAR,:TERM,:COURSENO,:GROUP,:WEEKS,:TASK,:RECNO,:UNIT,:ROOMTYPE,:EMPROOM,:DAY,:TIME,:ROOMNO,:OEW) ";

    					$bind = $this->model->getBind("YEAR,TERM,COURSENO,GROUP,WEEKS,TASK,RECNO,UNIT,ROOMTYPE,EMPROOM", $scheduleplan);
    					$bind[":DAY"] = $TIMES_DAY;
    					$bind[":TIME"] = $TIMESECTORS[$TIMES]["NAME"];
    					$bind[":ROOMNO"] = $ROOMNO;
    					$bind[":OEW"] = "O";
    					$ok = $this->model->sqlExecute($sql, $bind);
    					if ($ok)
    					{
    						$succount++;
    					}
    					else
    					{
    						$failcount++;
    						
    						$iresult = array();
    						$iresult["TYPE"] = "COURSE_TEACHER";
    						$iresult["COURSENO"] = $COURSENO;
    						$iresult["TEACHERNO"] = $TEACHERNO;
    						$iresult["ROOMNO"] = $ROOMNO;
    						$iresult["TIMES_DAY"] = $TIMES_DAY;
    						$iresult["TIMES_SECTOR"] = $TIMES_SECTOR;
    						$iresult["content"] = "排课SCHEDULE表添加失败";
    						
    						array_push($result, $iresult);
    					}
    						
    				}
    				
    			}
    			
    		}
    		$count++;
    		TaskMonitorModel::next(session("S_USER_NAME"),$count,true,"COURSE_TEACHER",2);
    		
    		//更新TIMELIST表教室R
    		foreach($temp_ROOMarray as $ROOMNO => $temp_ROOM)
    		{
    			$bind = array(
    					':DR_YEAR'=>$_POST["YEAR"],
    					':DR_TERM'=>$_POST["TERM"],
    					':DR_ROOMNO'=>$ROOMNO
    			);
    			$sql = " delete from TIMELIST WHERE [YEAR]=:DR_YEAR AND TERM=:DR_TERM AND TYPE='R' AND WHO=:DR_ROOMNO ";
    			$this->model->sqlExecute($sql, $bind);    			
    			 
    			$temp_TIMES_DAY_SECTOR_array = $temp_ROOM["TIMES"];
    			$TIMES_DAY_SECTOR_array = array();
    			foreach ($temp_TIMES_DAY_SECTOR_array as $temp_TIMES_DAY_SECTOR)
    			{
    				$i_day = $temp_TIMES_DAY_SECTOR["i_day"];
    				$i_js = $temp_TIMES_DAY_SECTOR["i_js"];
    			
    				if ($TIMES_DAY_SECTOR_array[$i_day])
    				{
    					$haystack = ",".trim($TIMES_DAY_SECTOR_array[$i_day]).",";
    					$needle = ",".trim($i_js).",";
    					
    					$offset = strpos($haystack,$needle);    					
    					if (is_bool($offset) && $offset === false)
    					{    
    						$TIMES_DAY_SECTOR_array[$i_day] .= ",".$i_js;
    					} 					    					
    				}
    				else
    				{
    					$TIMES_DAY_SECTOR_array[$i_day] = $i_js;
    				}
    			}
    			$temp_ROOMarray[$ROOMNO]["TIMES"] = $TIMES_DAY_SECTOR_array;    
    			
    			$bind = array(
    					':SR_YEAR'=>$_POST["YEAR"],
    					':SR_TERM'=>$_POST["TERM"],
    					':SR_ROOMNO'=>$ROOMNO,
    					':IR_YEAR'=>$_POST["YEAR"],
    					':IR_TERM'=>$_POST["TERM"],
    					':IR_ROOMNO'=>$ROOMNO,
    					':to_R_ROOMNO'=>$ROOMNO,
    					':R_YEAR'=>$_POST["YEAR"],
    					':R_TERM'=>$_POST["TERM"],
    					':R_ROOMNO'=>$ROOMNO
    			);   
    			$isql = "";
    			foreach ($TIMES_DAY_SECTOR_array as $TIMES_DAY => $TIMES_SECTOR)
    			{
    				$TIMES = cwebsSchedule::lesson2Time(cwebsSchedule::getLesson($TIMES_SECTOR),"O");    				 
    						
    				if ($TIMES_DAY==1)
    				{
    					$isql .= "MON='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==2)
    				{
    					$isql .= "TUE='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==3)
    				{
    					$isql .= "WES='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==4)
    				{
    					$isql .= "THU='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==5)
    				{
    					$isql .= "FRI='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==6)
    				{
    					$isql .= "SAT='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==7)
    				{
    					$isql .= "SUN='".$TIMES."',";
    				}    				
    			}
    			$sql = " insert INTO TIMELIST SELECT :SR_YEAR,:SR_TERM,:SR_ROOMNO,'R','',0,0,0,0,0,0,0 "
    					. " WHERE NOT EXISTS (SELECT * from TIMELIST WHERE [YEAR]=:IR_YEAR AND TERM=:IR_TERM AND TYPE='R' AND WHO=:IR_ROOMNO); "
    					. " UPDATE TIMELIST set {$isql} WHO=:to_R_ROOMNO where [YEAR]=:R_YEAR AND TERM=:R_TERM AND [TYPE]='R' AND WHO=:R_ROOMNO; ";
    			$ok = $this->model->sqlExecute($sql, $bind);
    			if ($ok)
    			{
    				$succount++;
    			}
    			else
    			{
    				$failcount++;
    			
    				$iresult = array();
    				$iresult["TYPE"] = "ROOM";
    				$iresult["ROOMNO"] = $ROOMNO;
    				$iresult["TIMES_DAY"] = $TIMES_DAY;
    				$iresult["TIMES_SECTOR"] = $TIMES_SECTOR;
    				$iresult["content"] = "排课TIMELIST表添加失败";
    			
    				array_push($result, $iresult);
    			}
    			 
    			
    		}
    		$count++;
    		TaskMonitorModel::next(session("S_USER_NAME"),$count,true,"ROOM",2);
    		
    		//更新TIMELIST表班级C
    		foreach($temp_CLASSarray as $CLASSNO => $temp_CLASS)
    		{
    			$bind = array(
    					':DC_YEAR'=>$_POST["YEAR"],
    					':DC_TERM'=>$_POST["TERM"],
    					':DC_CLASSNO'=>$CLASSNO
    			);
    			$sql = " delete from TIMELIST WHERE [YEAR]=:DC_YEAR AND TERM=:DC_TERM AND TYPE='C' AND WHO=:DC_CLASSNO ";
    			$this->model->sqlExecute($sql, $bind);
    		
    			$temp_TIMES_DAY_SECTOR_array = $temp_CLASS["TIMES"];
    			$TIMES_DAY_SECTOR_array = array();
    			foreach ($temp_TIMES_DAY_SECTOR_array as $temp_TIMES_DAY_SECTOR)
    			{
    				$i_day = $temp_TIMES_DAY_SECTOR["i_day"];
    				$i_js = $temp_TIMES_DAY_SECTOR["i_js"];
    				 
    				if ($TIMES_DAY_SECTOR_array[$i_day])
    				{
    					$haystack = ",".trim($TIMES_DAY_SECTOR_array[$i_day]).",";
    					$needle = ",".trim($i_js).",";
    					
    					$offset = strpos($haystack,$needle);    					
    					if (is_bool($offset) && $offset === false)
    					{    
    						$TIMES_DAY_SECTOR_array[$i_day] .= ",".$i_js;
    					} 					
    				}
    				else
    				{
    					$TIMES_DAY_SECTOR_array[$i_day] = $i_js;
    				}
    			}
    			$temp_CLASSarray[$CLASSNO]["TIMES"] = $TIMES_DAY_SECTOR_array;
    			 
    			$bind = array(
    					':SC_YEAR'=>$_POST["YEAR"],
    					':SC_TERM'=>$_POST["TERM"],
    					':SC_CLASSNO'=>$CLASSNO,
    					':IC_YEAR'=>$_POST["YEAR"],
    					':IC_TERM'=>$_POST["TERM"],
    					':IC_CLASSNO'=>$CLASSNO,
    					':to_C_CLASSNO'=>$CLASSNO,
    					':C_YEAR'=>$_POST["YEAR"],
    					':C_TERM'=>$_POST["TERM"],
    					':C_CLASSNO'=>$CLASSNO
    			);
    			$isql = ""; 				
    			foreach ($TIMES_DAY_SECTOR_array as $TIMES_DAY => $TIMES_SECTOR)
    			{
    				$TIMES = cwebsSchedule::lesson2Time(cwebsSchedule::getLesson($TIMES_SECTOR),"O");
    				
    				if ($TIMES_DAY==1)
    				{
    					$isql = "MON='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==2)
    				{
    					$isql = "TUE='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==3)
    				{
    					$isql = "WES='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==4)
    				{
    					$isql = "THU='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==5)
    				{
    					$isql = "FRI='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==6)
    				{
    					$isql = "SAT='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==7)
    				{
    					$isql = "SUN='".$TIMES."',";
    				}
       			}
       			$sql = " insert INTO TIMELIST SELECT :SC_YEAR,:SC_TERM,:SC_CLASSNO,'C','',0,0,0,0,0,0,0 "
    			     . " WHERE NOT EXISTS (SELECT * from TIMELIST WHERE [YEAR]=:IC_YEAR AND TERM=:IC_TERM AND TYPE='C' AND WHO=:IC_CLASSNO); "
    			     . " UPDATE TIMELIST set {$isql} WHO=:to_C_CLASSNO where [YEAR]=:C_YEAR AND TERM=:C_TERM AND [TYPE]='C' AND WHO=:C_CLASSNO; ";
    			$ok = $this->model->sqlExecute($sql,$bind);
    			if ($ok)
    			{
    				$succount++;
    			}
    			else
    			{
    				$failcount++;
    				
    				$iresult = array();
    				$iresult["TYPE"] = "CLASS";
    				$iresult["CLASSNO"] = $CLASSNO;
    				$iresult["TIMES_DAY"] = $TIMES_DAY;
    				$iresult["TIMES_SECTOR"] = $TIMES_SECTOR;
    				$iresult["content"] = "排课TIMELIST表添加失败";
    				
    				array_push($result, $iresult);
    			}
    		}
    		$count++;
    		TaskMonitorModel::next(session("S_USER_NAME"),$count,true,"CLASS",2);
    		
    		//更新TIMELIST表教师T
    		foreach($temp_TEACHERarray as $TEACHERNO => $temp_TEACHER)
    		{
    			$bind = array(
    					':DT_YEAR'=>$_POST["YEAR"],
    					':DT_TERM'=>$_POST["TERM"],
    					':DT_TEACHERNO'=>$TEACHERNO
    			);
    			$sql = " delete from TIMELIST WHERE [YEAR]=:DT_YEAR AND TERM=:DT_TERM AND TYPE='T' AND WHO=:DT_TEACHERNO ";
    			$this->model->sqlExecute($sql, $bind);
    		
    			$temp_TIMES_DAY_SECTOR_array = $temp_TEACHER["TIMES"];
    			$TIMES_DAY_SECTOR_array = array();
    			foreach ($temp_TIMES_DAY_SECTOR_array as $temp_TIMES_DAY_SECTOR)
    			{
    				$i_day = $temp_TIMES_DAY_SECTOR["i_day"];
    				$i_js = $temp_TIMES_DAY_SECTOR["i_js"];
    					
    				if ($TIMES_DAY_SECTOR_array[$i_day])
    				{
    					$haystack = ",".trim($TIMES_DAY_SECTOR_array[$i_day]).",";
    					$needle = ",".trim($i_js).",";
    					
    					$offset = strpos($haystack,$needle);    					
    					if (is_bool($offset) && $offset === false)
    					{    
    						$TIMES_DAY_SECTOR_array[$i_day] .= ",".$i_js;
    					} 
    				}
    				else
    				{
    					$TIMES_DAY_SECTOR_array[$i_day] = $i_js;
    				}
    			}
    			$temp_TEACHERarray[$TEACHERNO]["TIMES"] = $TIMES_DAY_SECTOR_array;
    		
    			$bind = array(
    					':ST_YEAR'=>$_POST["YEAR"],
    					':ST_TERM'=>$_POST["TERM"],
    					':ST_TEACHERNO'=>$TEACHERNO,
    					':IT_YEAR'=>$_POST["YEAR"],
    					':IT_TERM'=>$_POST["TERM"],
    					':IT_TEACHERNO'=>$TEACHERNO,
    					':to_T_TEACHERNO'=>$TEACHERNO,
    					':T_YEAR'=>$_POST["YEAR"],
    					':T_TERM'=>$_POST["TERM"],
    					':T_TEACHERNO'=>$TEACHERNO
    			);
    			$isql = ""; 				
    			foreach ($TIMES_DAY_SECTOR_array as $TIMES_DAY => $TIMES_SECTOR)
    			{
    				$TIMES = cwebsSchedule::lesson2Time(cwebsSchedule::getLesson($TIMES_SECTOR),"O");
    				
    				if ($TIMES_DAY==1)
    				{
    					$isql = "MON='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==2)
    				{
    					$isql = "TUE='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==3)
    				{
    					$isql = "WES='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==4)
    				{
    					$isql = "THU='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==5)
    				{
    					$isql = "FRI='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==6)
    				{
    					$isql = "SAT='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==7)
    				{
    					$isql = "SUN='".$TIMES."',";
    				}
    			}
    		    				 
    			$sql = " insert INTO TIMELIST SELECT :ST_YEAR,:ST_TERM,:ST_TEACHERNO,'T','',0,0,0,0,0,0,0 "
    			     . " WHERE NOT EXISTS (SELECT * from TIMELIST WHERE [YEAR]=:IT_YEAR AND TERM=:IT_TERM AND TYPE='T' AND WHO=:IT_TEACHERNO); "
    				 . " UPDATE TIMELIST set {$isql} WHO=:to_T_TEACHERNO where [YEAR]=:T_YEAR AND TERM=:T_TERM AND [TYPE]='T' AND WHO=:T_TEACHERNO; ";
    			$ok = $this->model->sqlExecute($sql,$bind);
    			if ($ok)
    			{
    				$succount++;
    			}
    			else
    			{
    				$failcount++;
    				
    				$iresult = array();
    				$iresult["TYPE"] = "TEACHER";
    				$iresult["TEACHERNO"] = $TEACHERNO;
    				$iresult["TIMES_DAY"] = $TIMES_DAY;
    				$iresult["TIMES_SECTOR"] = $TIMES_SECTOR;
    				$iresult["content"] = "排课TIMELIST表添加失败";
    				
    				array_push($result, $iresult);
    			}
    		}
    		$count++;
    		TaskMonitorModel::next(session("S_USER_NAME"),$count,true,"TEACHER",2);
    		
    		//更新TIMELIST表课程P
    		foreach($temp_COURSEarray as $COURSENO => $temp_COURSE)
    		{
    			$bind = array(
    					':DP_YEAR'=>$_POST["YEAR"],
    					':DP_TERM'=>$_POST["TERM"],
    					':DP_COURSENO'=>$COURSENO
    			);
    			$sql = " delete from TIMELIST WHERE [YEAR]=:DP_YEAR AND TERM=:DP_TERM AND TYPE='P' AND RTRIM(TIMELIST.WHO)+RTRIM(TIMELIST.PARA)=:DP_COURSENO ";
    			$this->model->sqlExecute($sql, $bind);
    		
    			$temp_TIMES_DAY_SECTOR_array = $temp_COURSE["TIMES"];
    			$TIMES_DAY_SECTOR_array = array();
    			foreach ($temp_TIMES_DAY_SECTOR_array as $temp_TIMES_DAY_SECTOR)
    			{
    				$i_day = $temp_TIMES_DAY_SECTOR["i_day"];
    				$i_js = $temp_TIMES_DAY_SECTOR["i_js"];
    					
    				if ($TIMES_DAY_SECTOR_array[$i_day])
    				{
    					$haystack = ",".trim($TIMES_DAY_SECTOR_array[$i_day]).",";
    					$needle = ",".trim($i_js).",";    					
    					
    					$offset = strpos($haystack,$needle);    					
    					if (is_bool($offset) && $offset === false)
    					{    
    						$TIMES_DAY_SECTOR_array[$i_day] .= ",".$i_js;
    					} 
    						
    				}
    				else
    				{
    					$TIMES_DAY_SECTOR_array[$i_day] = $i_js;
    				}
    			}
    			$temp_COURSEarray[$COURSENO]["TIMES"] = $TIMES_DAY_SECTOR_array;
    		
    			$bind = array(
    					':SP_YEAR'=>$_POST["YEAR"],
    					':SP_TERM'=>$_POST["TERM"],
    					':SP_COURSENO'=>substr($COURSENO,0,7),
    					':SP_PARA'=>substr($COURSENO,7,2),
    					':IP_YEAR'=>$_POST["YEAR"],
    					':IP_TERM'=>$_POST["TERM"],
    					':IP_COURSENO'=>$COURSENO,
    					':to_P_COURSENO'=>substr($COURSENO,0,7),
    					':P_YEAR'=>$_POST["YEAR"],
    					':P_TERM'=>$_POST["TERM"],
    					':P_COURSENO'=>$COURSENO
    			);    			
    			$isql = ""; 				
    			foreach ($TIMES_DAY_SECTOR_array as $TIMES_DAY => $TIMES_SECTOR)
    			{
    				$TIMES = cwebsSchedule::lesson2Time(cwebsSchedule::getLesson($TIMES_SECTOR),"O");
    				
    				if ($TIMES_DAY==1)
    				{
    					$isql = "MON='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==2)
    				{
    					$isql = "TUE='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==3)
    				{
    					$isql = "WES='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==4)
    				{
    					$isql = "THU='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==5)
    				{
    					$isql = "FRI='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==6)
    				{
    					$isql = "SAT='".$TIMES."',";
    				}
    				else if ($TIMES_DAY==7)
    				{
    					$isql = "SUN='".$TIMES."',";
    				}
    				 
    				    				
    			}
    		    $sql = " insert INTO TIMELIST SELECT :SP_YEAR,:SP_TERM,:SP_COURSENO,'P',:SP_PARA,0,0,0,0,0,0,0 "
    			     . " WHERE NOT EXISTS (SELECT * from TIMELIST WHERE [YEAR]=:IP_YEAR AND TERM=:IP_TERM AND TYPE='P' AND RTRIM(TIMELIST.WHO)+RTRIM(TIMELIST.PARA)=:IP_COURSENO); "
    			     . " UPDATE TIMELIST set {$isql} WHO=:to_P_COURSENO where [YEAR]=:P_YEAR AND TERM=:P_TERM AND [TYPE]='P' AND RTRIM(TIMELIST.WHO)+RTRIM(TIMELIST.PARA)=:P_COURSENO; ";
    			$ok = $this->model->sqlExecute($sql,$bind);
    			if ($ok)
    			{
    				$succount++;
    			}
    			else
    			{
    				$failcount++;
    				
    				$iresult = array();
    				$iresult["TYPE"] = "COURSE";
    				$iresult["COURSENO"] = $COURSENO;
    				$iresult["TIMES_DAY"] = $TIMES_DAY;
    				$iresult["TIMES_SECTOR"] = $TIMES_SECTOR;
    				$iresult["content"] = "排课TIMELIST表添加失败";
    				
    				array_push($result, $iresult);
    			}
    		}
    		$count++;
    		TaskMonitorModel::next(session("S_USER_NAME"),$count,true,"COURSE",2);
    		
    	}
    
    	TaskMonitorModel::done(session("S_USER_NAME"));
    	 
    	wincache_ucache_set(session("S_USER_NAME")."_RESULT", $result);
    	wincache_ucache_set(session("S_USER_NAME")."_succount", $succount);
    	wincache_ucache_set(session("S_USER_NAME")."_failcount", $failcount);
    
    }
    
    /**
     * excel更欣课表导入结果输出
     */
    public function excelimpresult(){
    
    	$result = wincache_ucache_get(session("S_USER_NAME")."_RESULT");
    	wincache_ucache_delete(session("S_USER_NAME")."_RESULT");
    
    	$succount = wincache_ucache_get(session("S_USER_NAME")."_succount");
    	wincache_ucache_delete(session("S_USER_NAME")."_succount");
    	 
    	$failcount = wincache_ucache_get(session("S_USER_NAME")."_failcount");
    	wincache_ucache_delete(session("S_USER_NAME")."_failcount");
    
    	$this->assign("result",$result);
    	$this->assign("succount",$succount);
    	$this->assign("failcount",$failcount);
    	$this->display("excelimpresult");
    }
    

    public function refreshResTime2(){
        if($this->_hasJson){
            //todo:找出所有被安排到老师
            $teachernoList=$this->model->sqlQuery("SELECT DISTINCT(RTRIM(TEACHERS.TEACHERNO)) AS TEACHERNO
FROM TEACHERS JOIN TEACHERPLAN ON TEACHERS.TEACHERNO=TEACHERPLAN.TEACHERNO
JOIN SCHEDULE ON TEACHERPLAN.RECNO=SCHEDULE.MAP
WHERE SCHEDULE.YEAR=:year AND SCHEDULE.TERM=:term
ORDER BY TEACHERNO",array(':year'=>$_POST['YEAR'],':term'=>$_POST['TERM']));
            $str='';
            $num=0;
       /*     echo '<pre>';
            var_dump($teachernoList);*/
            //todo:如果有教师
            if($teachernoList){
                foreach($teachernoList as $val){
                    if($val['TEACHERNO']=='000000')continue;
                    //todo:
                    $day=array(1=>0,0,0,0,0,0,0);

                    //todo:找出每个教师上的课程
                    $courseList=$this->model->sqlQuery($this->model->getSqlMap('schedule/source_teacherCourse.SQL'),
                        array(':year'=>$_POST['YEAR'],':term'=>$_POST['TERM'],':teacherno'=>$val['TEACHERNO']));
                    $courseList2=$this->groupday($courseList);
                    foreach($courseList as $v){
                        if($v['TIMES']&$day[$v['DAY']]){       //todo:某一天的某节课和该天的课 &
                            foreach($courseList2[$v['DAY']] as $v2){
                                if($v2['TIMES']&$v['TIMES']&&$v2['WEEKS']&$v['WEEKS']&&$v['row']!=$v2['row']){
                                    $str.=$num.':错误:教师'.$val['TEACHERNO'].',课程:'.$v['COURSE'].',在星期:'.$v['DAY'].',时段:'.$v['jc'].',单双周:'.$v['dsz'].','.'周次:'.str_pad(strrev(decbin($v['WEEKS'])),18,0).'<br>';
                                    $num++;
                                    break;
                                }
                            }
                        }
                        $day[$v['DAY']]|=$v['TIMES'];
                    }
                    //$this->groupday()
                }
            }
            echo json_encode(array('str'=>$str,'count'=>$num));
            exit;
        }

        $this->assign('year',$_GET['YEAR']);
        $this->assign('term',$_GET['TERM']);
        $this->display();
    }
    /**
     *刷新资源时间表
     */
    public function refreshResTime(){
        $this->display();
    }


    //todo:判断班级时间
    public function classTime(){

        //todo:找出班号
        $classList=$this->model->sqlQuery("SELECT DISTINCT(RTRIM(CLASSES.CLASSNO)) AS CLASSNO
FROM CLASSES JOIN COURSEPLAN ON CLASSES.CLASSNO=COURSEPLAN.CLASSNO
WHERE COURSEPLAN.YEAR=:YEAR AND COURSEPLAN.TERM=:TERM
ORDER BY CLASSNO",array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM']));


        $str='';
        $num=$_POST['NUM'];
        //todo:如果有班级
        if($classList){
            foreach($classList as $val){
                if($val['CLASSNO']=='000000')continue;
                //todo:


                //todo:找出每个班级上的课程
                $courseList=$this->model->sqlQuery($this->model->getSqlMap('schedule/source_classCourse.SQL'),
                    array(':classno'=>$val['CLASSNO'],':year'=>$_POST['YEAR'],':term'=>$_POST['TERM']));

                $courseList2=$this->groupday($courseList);

           /*   if(count($courseList)){
                  echo '<pre>';
                echo $val['CLASSNO'];
                 print_r($courseList);
                    print_r($courseList2);

              }*/
                $day=array();
                foreach($courseList as $v){
                    if($v['TIMES']&$day[$v['DAY']]){       //todo:某一天的某节课和该天的课 &

                        foreach($courseList2[$v['DAY']] as $v2){
                            if($v2['TIMES']&$v['TIMES']&&$v2['WEEKS']&$v['WEEKS']&&$v['row']!=$v2['row']){
                       /*        echo '进去了';
                       */         $str.=$num.':错误:班级'.$val['CLASSNO'].',课程:'.$v['COURSE'].',在星期:'.$v['DAY'].',时段:'.$v['jc'].',单双周:'.$v['dsz'].','.'周次:'.str_pad(strrev(decbin($v['WEEKS'])),18,0).'<br>';
                                $num++;
                                break;
                            }
                        }
                    }
                    $day[$v['DAY']]|=$v['TIMES'];
                }
                //$this->groupday()
            }
        }
        echo $str;
    }

    //todo:判断课程是否冲突
    public function is_Repeat(){
        $courseList=$this->model->sqlQuery("SELECT (COURSENO+[GROUP]) as courseno,SCHEDULE.DAY,SCHEDULE.TIME,SCHEDULE.OEW,WEEKS,OEWOPTIONS.TIMEBIT&TIMESECTIONS.TIMEBITS2 AS TIMES
 FROM SCHEDULE
inner join TIMESECTIONS on TIMESECTIONS.NAME=SCHEDULE.TIME
inner join OEWOPTIONS on OEWOPTIONS.CODE=SCHEDULE.OEW
WHERE SCHEDULE.YEAR=:YEAR AND SCHEDULE.TERM=:TERM
ORDER BY courseno",
        array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM']));
        $arr=array();
      /*  echo '<pre>';
        print_r($courseList);*/
        foreach($courseList as $val){
                    if(!is_array($arr[$val['courseno']])){
                        $arr[$val['courseno']]=array();
                    }
                 array_push($arr[$val['courseno']],$val);
        }
        $str='';
       ;
        foreach($arr as $val){
            if(count($val)>1){
                $time=array(1=>array('TIMES'=>0),array('TIMES'=>0),array('TIMES'=>0),array('TIMES'=>0),array('TIMES'=>0),array('TIMES'=>0),array('TIMES'=>0));
                foreach($val as $v){
                    if($v['TIMES']&$time[$v['DAY']]['TIMES']){
                        $str.=':错误:课程'.$val['courseno'].'<br>';
                    }
                    $time[$v['DAY']]['TIMES']|=$v['TIMES'];
                }
            }
        }
        echo $str;
    }



        public function groupday($arr){


        $array=array(1=>array(),2=>array(),3=>array(),4=>array(),5=>array(),6=>array(),7=>array());
        foreach($arr as $val){

            array_push($array[$val['DAY']],$val);
        }

        return $array;

    }
    /**
     *锁定
     */
    public function lock(){
        if($this->_hasJson){
            if(VarIsIntval("YEAR,TERM")==false){
                $this->message["type"] = "error";
                $this->message["message"] = "提交的参数不正确，非法提交！";
                $this->__done();
            }
            $bind = $this->model->getBind("YEAR,TERM",$_REQUEST);
            $sql = "UPDATE SCHEDULE SET LOCK=1 where YEAR=:YEAR and TERM=:TERM and DAY<>'0'";
            $data = $this->model->sqlExecute($sql,$bind);
            if($data===false){
                $this->message["type"] = "error";
                $this->message["message"] = "<font color='red'>加锁时发生错误，加锁失败！</font>";
            }else{
                $this->message["type"] = "info";
                $this->message["message"] = $_REQUEST['YEAR']."年第".$_REQUEST['TERM']."学期排定的课表成功加锁！";
            }
            $this->__done();
        }
        $this->__done("lock");
    }

    /**
     *解锁
     */
    public function unlock(){
        if($this->_hasJson){
            if(VarIsIntval("YEAR,TERM")==false){
                $this->message["type"] = "error";
                $this->message["message"] = "提交的参数不正确，非法提交！";
                $this->__done();
            }
            $bind = $this->model->getBind("YEAR,TERM",$_REQUEST);
            $sql = "UPDATE SCHEDULE SET LOCK=0 where YEAR=:YEAR and TERM=:TERM";
            $data = $this->model->sqlExecute($sql,$bind);
            if($data===false){
                $this->message["type"] = "error";
                $this->message["message"] = "<font color='red'>解锁时发生错误，解锁失败！</font>";
            }else{
                $this->message["type"] = "info";
                $this->message["message"] = $_REQUEST['YEAR']."年第".$_REQUEST['TERM']."学期排定的课表封锁解除！";
            }
            $this->__done();
        }
        $this->__done("unlock");
    }

    /**
     * 设定资源竞争状态
     */
    public function setResStatus(){
         if($this->_hasJson){
             if(VarIsIntval("SCHEDULERESOURCE")==false){
                 $this->message["type"] = "error";
                 $this->message["message"] = "提交的参数不正确，非法提交！";
                 $this->__done();
             }
             $bind = $this->model->getBind("STATE",$_REQUEST['SCHEDULERESOURCE']==1?1:0);
             $sql = "UPDATE STATES SET STATE=:STATE WHERE STATENAME='SCHEDULERESOURCE'";
             $data = $this->model->sqlExecute($sql,$bind);
             if($data===false){
                 $this->message["type"] = "error";
                 $this->message["message"] = "<font color='red'>设定资源竞争状态发生错误，设定失败！</font>";
             }else{
                 $this->message["type"] = "info";
                 $this->message["message"] = "资源竞争状态已成功设定！";
             }
             $this->__done();
         }
        $data = $this->model->sqlCount($this->model->getSqlMap("Schedule/queryScheduleState.sql"));
        $this->assign("ScheduleResource",$data);
        $this->__done("setresstatus");
    }
}