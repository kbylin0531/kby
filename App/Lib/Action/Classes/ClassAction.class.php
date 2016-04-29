<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-12-2
 * Time: 下午3:01
 **/
class ClassAction extends RightAction
{
    private $md;        //存放模型对象

    /**
     *  班级管理
     *
     **/
    public function __construct(){
  //      $this->model = M("SqlsrvModel:");
        parent::__construct();
        $this->md = new ClassModel();
        $this->assign('school',getSchoolList());
        $this->assign('quanxian',trim($_SESSION['S_USER_INFO']['ROLES']));
    }


    public function classesq($CLASSNO='%',$CLASSNAME='%',$SCHOOL='%',$YEAR='%')
    {
        if($_POST['auth_judge']){
            $sqlone="select SCHOOL from TEACHERS where TEACHERNO=:TEACHERNO";
            $school=$this->md->sqlFind($sqlone,array(':TEACHERNO'=>$_SESSION['S_USER_INFO']['TEACHERNO']));
            if((!isDeanByUsername(getUsername())) && ($school['SCHOOL']!=$_POST['SCHOOL'])) {
                exit('false');
            }
        }


        if($this->_hasJson) {
			$classModel = new ClassesModel();
			$list = $classModel->listClasses($CLASSNO,$CLASSNAME,$SCHOOL,$YEAR,$this->_pageDataIndex,$this->_pageSize);
			$this->ajaxReturn($list);
        }

    	$school = M('schools')->query(" select * from __TABLE__ where (PARENT is null or ltrim(rtrim(PARENT)) = '') order by ORDERBY ");
        $sjson=array();


        foreach(getSchoolList(false) as $val)
        {
            $sjson2['text']= $val['NAME'];
            $sjson2['value']=$val['SCHOOL'];                  // 把学院数据转成json格式给前台的combobox使用
            array_push($sjson,$sjson2);
        }
        $sjson=json_encode($sjson);
        $this->assign('school',$school);
        $this->assign('sjson',$sjson);

        $this->display();
    }

    /**
     * excel导入编辑
     */
    public function excelimpclassedit(){
    
    	$this->display();
    }
    
    /**
     * excel导入保存
     */
    public function excelimpclasssave(){
    
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

    	TaskMonitorModel::init(session("S_USER_NAME"), "班级信息excel导入");
    	 
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
    	$sheetData = $objPHPExcel->getSheet(0)->toArray(null,true,true,true);
    
    	//获取表头，并判断是否符合格式
    	//		$keywords = $sheetData[1];
    	//		$warning = '上传文件格式不正确，请修改后重新上传！<br />';
    	//		$columns = array ( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K' );
    	//		$keysInFile = array ( '旧班号', '新班号', '班级名称', '专业名称', '年级名称', '班主任', '状态', '班级人数', '建班年月', '所属校区', '备注' );
    	//		foreach( $columns as $keyIndex => $columnIndex ){
    	//			if ( $keywords[$columnIndex] != $keysInFile[$keyIndex] ){
    	//				echo $warning . $columnIndex . '列应为' . $keysInFile[$keyIndex] . '，而非' . $keywords[$columnIndex];
    	//				exit();
    	//			}
    	//		}

    	TaskMonitorModel::run(session("S_USER_NAME"),"班级信息excel导入", count($sheetData)-1);
    	 
    	$result = array();
    	$count = 0;
    	$succount = 0;
    	$failcount = 0;
    
    	$key_val = array ( '旧班号'=>'A', '新班号'=>'B', '班级名称'=>'C', '专业名称'=>'D', '年级名称'=>'E', '班主任'=>'F', '状态'=>'G', '班级人数'=>'H', '建班年月'=>'I', '所属校区'=>'J', '备注'=>'K' );
    	foreach ( $sheetData as $key => $words ){
    		if ( $key != 1 ){
    			$count++;    			 
    			 
    			$priclassno = trim($words[$key_val['旧班号']]);
    			$classno = trim($words[$key_val['新班号']]);
    			$classname = trim($words[$key_val['班级名称']]);
    			if (isset($classno) && !empty($classno) && isset($classname) && !empty($classname))
    			{
    				$majorname = trim($words[$key_val['专业名称']]);
    				$clsgradename = trim($words[$key_val['年级名称']]);
    				$chargeteacher = trim($words[$key_val['班主任']]);
    				$remark = trim($words[$key_val['备注']]);
    				$students = trim($words[$key_val['班级人数']]);
    				$yearmonth = trim($words[$key_val['建班年月']]);
    				if (isset($yearmonth) && !empty($yearmonth))
    				{
    					$yearmonth .= "-01";
    				}
    				$schoolname = trim($words[$key_val['所属校区']]);
    				$grade = 1;
    
    				$schoolno = "";
    				if (isset($schoolname) && !empty($schoolname))
    				{
    					$schoolOBJ = $this->md->sqlFind("SELECT * FROM SCHOOLS WHERE upper(ltrim(rtrim(NAME))) = :NAME ",array(":NAME" => strtoupper($schoolname)));
    					if ($schoolOBJ)
    					{
    						$schoolno = $schoolOBJ['SCHOOL'];
    					}
    				}
    				
    				$majorno = "";
    				if (isset($schoolno) && !empty($schoolno) && isset($majorname) && !empty($majorname))
    				{
    					$majorOBJ = $this->md->sqlFind("SELECT i.MAJORNO FROM MAJORS i left join MAJORCODE m1 on m1.CODE = i.MAJORNO WHERE upper(ltrim(rtrim(i.SCHOOL))) = :SCHOOL and upper(ltrim(rtrim(m1.NAME))) = :MAJOR ",array(":SCHOOL" => strtoupper($schoolno), ":MAJOR" => strtoupper($majorname)));
    					if ($majorOBJ)
    					{
    						$majorno = $majorOBJ['MAJORNO'];
    					}
    				}

    				$classOBJ = $this->md->sqlFind("SELECT * FROM CLASSES WHERE upper(ltrim(rtrim(CLASSNO))) = :CLASSNO ",array(":CLASSNO" => strtoupper($classno)));
    				if ($classOBJ)
    				{
    					$failcount++;
    
    					$iresult = array();
    					$iresult["row"] = $key;
    					$iresult["priclassno"] = $priclassno;
    					$iresult["classno"] = $classno;
    					$iresult["classname"] = $classname;
    					$iresult["content"] = "相同新班号数据库已存在";
    
    					array_push($result, $iresult);    
    					
    					TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$classno,2);
    				}
    				else
    				{
    					$ary = array(
    							":PRI_CLASSNO" => $priclassno,
    							":CLASSNO" => $classno,
    							":SCHOOL" => $schoolno,
    							":CLASSNAME" => $classname,
    							":GRADE" => $grade,
    							":STUDENTS" => $students,
    							":YEAR" => $yearmonth,
    							":MAJORNO" => $majorno,
    							":CLS_GRADE_NAME" => $clsgradename,
    							":CHARGE_TEACHER" => $chargeteacher,
    							":REMARK" => $remark
    					);
    
    					//开始添加
    					$sql = " insert into CLASSES(PRI_CLASSNO,CLASSNO,SCHOOL,CLASSNAME,GRADE,STUDENTS,YEAR,MAJORNO,CLS_GRADE_NAME,CHARGE_TEACHER,REMARK) VALUES(:PRI_CLASSNO,:CLASSNO,:SCHOOL,:CLASSNAME,:GRADE,:STUDENTS,:YEAR,:MAJORNO,:CLS_GRADE_NAME,:CHARGE_TEACHER,:REMARK); ";
    
    					$bool = $this->md->sqlExecute($sql,$ary);
    
    					if ($bool)
    					{
    						$succount++;
    						
    						TaskMonitorModel::next(session("S_USER_NAME"),$count,true,$classno,2);
    					}
    					else
    					{
    						$failcount++;
    							
    						$iresult = array();
    						$iresult["row"] = $key;
    						$iresult["priclassno"] = $priclassno;
    						$iresult["classno"] = $classno;
    						$iresult["classname"] = $classname;
    						$iresult["content"] = "数据库出错导入失败";
    							
    						array_push($result, $iresult);
    						
    						TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$classno,2);
    					}
    
    				}
    
    			}
    			else
    			{
    				$failcount++;
    
    				$iresult = array();
    				$iresult["row"] = $key;
    				$iresult["content"] = "新班号或班级名称为空没有导入";
    
    				array_push($result, $iresult);
    				
    				TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$classno,2);
    			}
    		}
    		else {
    		}
    	}

    	TaskMonitorModel::done(session("S_USER_NAME"));
    	 
    	wincache_ucache_set(session("S_USER_NAME")."_RESULT", $result);
    	wincache_ucache_set(session("S_USER_NAME")."_succount", $succount);
    	wincache_ucache_set(session("S_USER_NAME")."_failcount", $failcount);
    	 
//    	$this->assign("result",$result);
//    	$this->assign("succount",$succount);
//   	$this->assign("failcount",$failcount);
//    	$this->display("excelimpclassresult");
    
    }

    /**
     * excel导入结果输出
     */
    public function excelimpclassresult(){
    	 
    	$result = wincache_ucache_get(session("S_USER_NAME")."_RESULT");
    	wincache_ucache_delete(session("S_USER_NAME")."_RESULT");

    	$succount = wincache_ucache_get(session("S_USER_NAME")."_succount");
    	wincache_ucache_delete(session("S_USER_NAME")."_succount");
    	
    	$failcount = wincache_ucache_get(session("S_USER_NAME")."_failcount");
    	wincache_ucache_delete(session("S_USER_NAME")."_failcount");
    	 
    	$this->assign("result",$result);
    	$this->assign("succount",$succount);
    	$this->assign("failcount",$failcount);
    	$this->display("excelimpclassresult");
 	}
 	
 	/**
 	 * excel导出更欣课表班级名单
 	 */
 	public function excelexpGXKBclass(){ 			
 			
 		TaskMonitorModel::init(session("S_USER_NAME"), "excel导出用于更欣课表的班级名单");
 		
 		$pageSize = 500; 			
 			
 		$bind = array(":CLASSNO"=>doWithBindStr($_POST['CLASSNO']),
 				":CLASSNAME"=>doWithBindStr($_POST['CLASSNAME']),":SCHOOL"=>doWithBindStr($_POST['SCHOOL']),":YEAR"=>trim($_POST['YEAR']));
 			
 		$sql_count = " select Count(Classes.ClassNo) As ROWS "
			       . " from Classes "
	   			   . " left outer join SCHOOLS ON CLASSES.SCHOOL=SCHOOLS.SCHOOL "
	   			   . " where CLASSES.ClassNo like :CLASSNO "
	   			   . " and CLASSES.ClassName like :CLASSNAME "
	   			   . " and CLASSES.School like :SCHOOL "
	   			   . " and CAST(YEAR(Classes.YEAR) AS CHAR) LIKE :YEAR ";
 		
 		$data = $this->md->sqlFind($sql_count,$bind);
 		$totalCount = $data['ROWS'];

 		if ($totalCount > 0)
 		{
 			TaskMonitorModel::run(session("S_USER_NAME"),"excel导出用于更欣课表的班级名单", $totalCount);
 			
 			$inputFileType = 'Excel5';
 			$inputFileName = $_SERVER['DOCUMENT_ROOT'] . "\\res\\templates\\classes_class_expGXKB.xls";
	
 			vendor("PHPExcel.PHPExcel");
 			
 			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
 			$objPHPExcel = $objReader->load($inputFileName);
 			$targetws = $objPHPExcel->getSheet(0); 			
 			
 			$totalPage = ceil($totalCount / $pageSize);
 			
 			$pageNumber = 1;
 			$startNumber = 0;
 			
 			$sql_select = " select Classes.ClassNo As CLASSNO, "
                        . " Classes.ClassName As CLASSNAME, "
                        . " Classes.School As SCHOOL, "
                        . " Classes.Students As STUDENTS, "
                        . " Schools.Name As SCHOOLNAME, "
                        . " MYCOUNT.COUNTS AS COUNTS "
                        . " from Classes "
                        . " left outer join Schools ON Classes.School=Schools.School "
                        . " left outer join "
                        . " ( "
                        . "     select CLASSES.CLASSNO AS CLASSNO,Count(students.studentNo) As COUNTS "
                        . "     from CLASSES "
                        . "     LEFT OUTER JOIN STUDENTS ON CLASSES.classno=STUDENTS.classno "
                        . "     JOIN schools ON CLASSES.school=schools.school "
                        . "     GROUP BY CLASSES.CLASSNO "
                        . " ) AS MYCOUNT ON CLASSES.CLASSNO=MYCOUNT.CLASSNO "
                        . " WHERE Classes.ClassNo like :CLASSNO "
                        . " and Classes.ClassName like :CLASSNAME "
                        . " and Classes.School like :SCHOOL "
                        . " and CAST(YEAR(Classes.YEAR) AS CHAR) LIKE :YEAR "
                        . " ORDER BY CLASSES.CLASSNO ";
 			
 			$pagesql_select = $this->md->getPageSql($sql_select,null,$startNumber,$pageSize); 	
 			
 			$result = $this->md->sqlQuery($pagesql_select,$bind);

 			if (count($result) > 0)
 			{
 				$this->set_expGXKBclass_xls($result, $pageNumber, $pageSize, $targetws);
 					
 				while ($pageNumber < $totalPage)
 				{
 					$pageNumber++;
 					$startNumber = ($pageNumber -1) * $pageSize;
 					
 					$pagesql_select = $this->md->getPageSql($sql_select,null,$startNumber,$pageSize);

 					$result = $this->md->sqlQuery($pagesql_select,$bind);
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
 			
 			$filename = "用于更欣课表的班级名单_" . date ( 'Y-m-d', time () );
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
 		$teachers = $this->md->sqlQuery("select top 30 rtrim(TEACHERNO) as id, rtrim(TEACHERNO) + ' / ' + rtrim(NAME) as text from TEACHERS where TEACHERNO like :TEACHERNO or NAME like :NAME order by TEACHERNO", $bind);
 		$this->ajaxReturn($teachers,"JSON");
 	}
 	
    private function getSchoolMap($orig,$flag = true){
        if($orig === false){
            return null;
        }
        $schoolsmap = array();
        foreach($orig as $key=>$val){
            if($flag){
                $schoolsmap[$val['schoolno']] = $val['schoolname'];
            }else{
                $schoolsmap[$val['schoolname']] = $val['schoolno'];
            }
        }
        return $schoolsmap;
    }

    /*
     * 插入数据的方法
     */
    public function insertcl()
    {

        $int=$this->md->sqlFind("select * from CLASSES where CLASSNO='{$_POST['CLASSNO']}'");
        if($int){
            exit('班号有冲突');
        }
        $shuju=M('classes');
        if(trim($_POST["YEAR"])=="")
            $_POST["YEAR"]=date("Y-m-d");
        // $_POST["SCHOOL"]=$_POST["SCHOOLNAME"];
        $sql=$shuju->add($_POST);
        //$arr=$_POST['data'];

        if($sql) echo '创建成功';
        else echo 'false';
    }
    /*
    * 修改数据的方法
    */
    public function updatecl()
    {
        $this->quanxianpd2();
        $sstr='';
        $shuju=M('classes');
       /* $data=array();
        $arr['CLASSNO']=$_POST['CLASSNO'];
        $data['CLASSNO']= $arr['CLASSNO'];
       */ 
//        if(is_numeric($_POST['SCHOOLNAME']))$sstr="SCHOOL='".$_POST['SCHOOLNAME']."',";
        if(isset($_POST['toSCHOOL']))$sstr="SCHOOL='".$_POST['toSCHOOL']."',";
        if ($sstr)
        {
        	if(isset($_POST['toCHARGE_TEACHERNO']))$sstr.="CHARGE_TEACHERNO='".$_POST['toCHARGE_TEACHERNO']."',";
        }
        else
        {
        	if(isset($_POST['toCHARGE_TEACHERNO']))$sstr="CHARGE_TEACHERNO='".$_POST['toCHARGE_TEACHERNO']."',";
        }
        /*   $arr['CLASSNAME']=$_POST['CLASSNAME'];
        $arr['STUDENTS']=$_POST['STUDENTS'];
        $arr['YEAR']=$_POST['YEAR'];
     */   //$pd=$shuju->where($data)->save($arr);

        $pd=$this->md->sqlExecute("update classes set {$sstr}CLASSNO=:classno,CLASSNAME=:classname,STUDENTS=:students,YEAR=:year,REMARK=:REMARK where CLASSNO=:ctwo",
        array(':classno'=>$_POST['CLASSNO'],':classname'=>$_POST['CLASSNAME'],':students'=>$_POST['STUDENTS'],':year'=>$_POST['YEAR'],':REMARK'=>$_POST['REMARK'],':ctwo'=>$_POST['CLASSNO']));
        
        if($pd)
            echo 'true';
        else
            echo 'false';
    }

    public function deletecl()
    {
        $this->quanxianpd2();
        $shuju=M('classes');
        $data=array();
        $data['CLASSNO']=array('in',$_POST['in']);

        $arr=$shuju->where($data)->delete();
        if($arr)
            echo 'true';
        else
            echo 'false';
    }

    //todo:查询出  班级学生所用到的方法
    public function ClassQueryStudent(){
        //查询出这个班级的信息
        $count=$this->md->sqlFind($this->md->getSqlMap('Class/classQueryStudentCount.SQL'),array(':ClassNo'=>$_POST['CLASSNO']));
        if($one['total']=$count[''])
            $one['rows']=$this->md->sqlQuery($this->md->getSqlMap('Class/classQueryStudent.SQL'),array(':ClassNo'=>$_POST['CLASSNO'],':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
        else
            $one['rows']=array();
        $this->ajaxReturn($one,'JSON');
    }

    /**
     * 查询班级绑定的教学计划
     */
    public function Classjiaoxuejihua(){
        $this->ajaxReturn($this->md->getClassProgramtableList($_POST['CLASSNO']),'JSON');
    }


    //todo:判断用户是否有权限删除 或添加 教学计划
    public function quanxianpd(){
        //查出教师的所在学院
        $sqlone="select SCHOOL from TEACHERS where TEACHERNO=:TEACHERNO";
        $school=$this->md->sqlFind($sqlone,array(':TEACHERNO'=>$_SESSION['S_USER_INFO']['TEACHERNO']));
        if(isDeanByUsername(getUsername())||$school['SCHOOL']==$_POST['SCHOOL'])    //todo:改变教学计划的权限是  本学院的老师    或者是  教务处级别的
            exit('true');           //todo:是教务处的是有权限的
        echo '<font color="red">不能修改其他班级的信息！</font>';

    }
    //todo:判断用户是否有权限删除 或添加 教学计划
    public function quanxianpd2(){
        //查出教师的所在学院
        $sqlone="select SCHOOL from TEACHERS where TEACHERNO=:TEACHERNO";
        $school=$this->md->sqlFind($sqlone,array(':TEACHERNO'=>$_SESSION['S_USER_INFO']['TEACHERNO']));
        if(!isDeanByUsername(getUsername())&& $school['SCHOOL']!=$_POST['SCHOOL'])    //todo:改变教学计划的权限是  本学院的老师    或者是  教务处级别的
            exit('false');           //todo:是教务处的是有权限的
    }


    /*
     * 删除 班级学生列表的方法
     */
    public function deleteClassStudent(){
        $pd=true;
        foreach($_POST['STUDENT'] as $val){
            $one=$this->md->sqlExecute("update students set classno='' where RTRIM(studentno)=:STUDENTNO",array(':STUDENTNO'=>trim($val['StudentNo'])));
            if(!$one)
                $pd=false;
        }
        if($pd)
            echo '<b>删除成功</b>';
        else
            echo '<font color="red">删除的过程中出现异常，请测试</font>';
    }

    /*
     * 删除 班级教学计划的方法
     */
    public function deleteClassprogram(){

        $bool=$this->md->sqlExecute($this->md->getSqlMap('Class/deleteClassProgra.SQL'),array(':PROGRAMNO'=>$_POST['programno'],':CLASSNO'=>$_POST['classno']));
        if($bool)
            echo '删除成功！';
        else
            echo '数据库异常';

    }

    /*
     *todo:查询出教学计划的方法
     */

    public function selectjiaoxuejihua(){
        $count=$this->md->sqlFind($this->md->getSqlMap('Class/countjiaoxuejihua.SQL'),array(':PROGRAMNO'=>doWithBindStr($_POST['PROGRAMNO']),':PROGRAMNAME'=>doWithBindStr($_POST['PROGRAMNAME']),':SCHOOL'=>doWithBindStr($_POST['SCHOOL'])));
        if($arr['total']=$count['ROWS'])
            $arr['rows']=$this->md->sqlQuery($this->md->getSqlMap('Class/selectjiaoxuejihua.SQL'),array(':PROGRAMNO'=>doWithBindStr($_POST['PROGRAMNO']),':PROGRAMNAME'=>doWithBindStr($_POST['PROGRAMNAME']),':SCHOOL'=>doWithBindStr($_POST['SCHOOL']),':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
        else
            $arr['rows']=array();
        echo json_encode($arr);
    }

    //todo:查询出学生的方法

    public function selectxuesheng(){
        $count=$this->md->sqlFind($this->md->getSqlMap('Class/ClassCountStudent.SQL'),array(':StudentNo'=>doWithBindStr($_POST['STUDENTNO']),':Name'=>doWithBindStr($_POST['NAME']),':School'=>doWithBindStr($_POST['SCHOOL']),':ClassNo'=>doWithBindStr($_POST['CLASSNO'])));
        if($arr['total']=$count['ROWS'])
            $arr['rows']=$this->md->sqlQuery($this->md->getSqlMap('Class/ClassSelectStudent.SQL'),array(':StudentNo'=>doWithBindStr($_POST['STUDENTNO']),':Name'=>doWithBindStr($_POST['NAME']),':School'=>doWithBindStr($_POST['SCHOOL']),':ClassNo'=>doWithBindStr($_POST['CLASSNO']),':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
        else
            $arr['rows']=array();
        echo json_encode($arr);
    }

    /*
     * todo:添加教学计划号的方法
     */
    public function addprogram(){

            $pd=true;
        foreach($_POST['data'] as $key=>$val){
            $bool=$this->md->sqlExecute('INSERT INTO R16(PROGRAMNO,CLASSNO) VALUES(:PROGRAMNO1,:CLASSNO1)',array(':PROGRAMNO1'=>$val['PROGRAMNO'],':CLASSNO1'=>$_POST['CLASSNO']));
            if(!$bool){
                $pd=false;
            }
        }
        if($pd)
            echo '<b>数据插入成功</b>';
        else
            echo '<font color="red">有重复提交的数据,请检查班级里是否已经有该教学计划</font>';
    }

    /*
     * todo:给班级添加学生的方法
     */
    public function addStudent(){

        $classno=$this->md->sqlFind("select CLASSNO,SCHOOL from CLASSES where CLASSNO=:CLASSNO",array(':CLASSNO'=>$_POST['CLASSNO']));//todo:查询出指定班级的 所在学院

        $pd=true;

        foreach($_POST['data'] as $key=>$val){
            $bool=$this->md->sqlExecute('update students set classno=:CLASSNO,school=:SCHOOL where studentno=:STUDENTNO',array(':CLASSNO'=>$classno['CLASSNO'],':SCHOOL'=>$classno['SCHOOL'],':STUDENTNO'=>$val['StudentNo']));
            if(!$bool){
                $pd=false;
            }
        }
        if($pd)
            echo '<b>数据插入成功</b>';
        else
            echo '<font color="red">待添加,这是添加学生失败时候的</font>';

    }


    //todo:将选中的教学计划统一绑定到班级学生 的 方法
    public function addProgramToStudent(){
        $this->md->startTrans();//启动事物
        $pd=true;
        foreach($_POST['P'] as $val){
            //todo:第一步：删除R28中已经绑定了该教学计划的学生（因为重复插入会报错（存在主键））
            $bool=$this->md->sqlExecute($this->md->getSqlMap('Class/deleteProgramToStudent.SQL'),array(':PROGRAMNO'=>$val['PROGRAMNO'],':CLASSNO'=>$_POST['CLASSNO']));
            //todo:第二步：插入R28中 这个班级里的所有学生 绑定该教学计划
            $bool2=$this->md->sqlExecute($this->md->getSqlMap('Class/insertProgramToStudent.SQL'),array(':PROGRAMNO'=>$val['PROGRAMNO'],':CLASSNO'=>$_POST['CLASSNO']));
            if($bool2){
               $this->md->commit();    //提交
            }else{
                $pd=false;
                $this->md->rollback();  //回滚
            }
        }
        if($pd)
            echo '<b>教学计划绑定成功</b>';
        else
            echo '<font color="red">多条插入过程中有异常，请检查</font>';

    }

    //todo:查询出  学生个人教学计划的方法
    public function studentProgram(){
        $count=$this->chaxun('Class/studentprogramCount.SQL',array(':STUDENTNO'=>$_POST['STUDENTNO']),'Find');
        if($arr['total']=$count[''])
            $arr['rows']=$this->chaxun('Class/studentprogram.SQL',array(':STUDENTNO'=>$_POST['STUDENTNO']));
        else
            $arr['rows']=array();
        $this->ajaxReturn($arr,'JSON');
    }

    //todo:查询出某个教学计划 所有课程的方法
    public function programcourse(){
        $count=$this->chaxun('Class/countprogramcourse.SQL',array(':PROGRAMNO'=>$_POST['PROGRAMNO']),'Find');
        if($arr['total']=$count['']){
            $User = A('Room/Room');     //todo:转换进制用的函数
            $arr['rows']=$User->jinzhi($this->chaxun('Class/selectprogramcourse.SQL',array(':PROGRAMNO'=>$_POST['PROGRAMNO'],':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize)));
        }else{
            $arr['rows']=array();
         }
           $this->ajaxReturn($arr,'JSON');
    }


    //todo:查询出某个专业的教学计划
    public function zhuanyeprogram(){
        $count=$this->chaxun('Class/countzhuanyeprogram.SQL',array(':SCHOOL'=>$_POST['SCHOOL']),'Find');
        if($arr['total']=$count['']){
            $arr['rows']=$this->chaxun('Class/zhuanyeprogram.SQL',array(':SCHOOL'=>$_POST['SCHOOL'],':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
        }else{
            $arr['rows']=array();
        }
        $this->ajaxReturn($arr,'JSON');
    }

    //专门用于查询的方法
    public function chaxun($sqlPath,$arr,$type='Query'){
        switch($type){
            case 'Query':
                return $this->md->sqlQuery($this->md->getSqlMap($sqlPath),$arr);
            case 'Find':
                return $this->md->sqlFind($this->md->getSqlMap($sqlPath),$arr);
            case 'Execute':
                return $this->md->sqlExecute($this->md->getSqlMap($sqlPath),$arr);
        }
    }



    //todo:班级导出excel功能
    public function excel(){
        $data=$this->md->sqlQuery($this->md->getSqlMap('Class/classExcel.SQL'),
            array());
    }


    public function studentlist($classno='%'){

		if(null !== REQTAG){
			$classModel = new ClassesModel();
			switch(REQTAG){
				case 'getlist':
					$list = $classModel->listStudentsByClassno($classno,$this->_pageDataIndex,$this->_pageSize);
					if(is_string($list)){
						$this->failedWithReport($list);
					}else{
						$this->ajaxReturn($list);
					}
					exit();
					break;
			}

		}


        $this->assign('info',$_GET);
        $this->display();
    }

    public function studentinfo(){
        $this->assign('info',$_GET);
        $this->display();
    }

    public function program_one(){
		$this->assign('school',getSchoolList());
        $this->assign('info',$_GET);
        $this->display();
    }

    public function program_course(){
        $this->assign('info',$_GET);
        $this->display();
    }

    //添加学生的页面
    public function add_student(){
        $shuju=M('schools');                                   // 学院数据
        $school=$shuju->select();
		$this->assign('school',getSchoolList());
        $this->assign('info',$_GET);
        $this->display();
    }


    //todo:教学计划列表
    public function programlist(){
        $this->assign('info',$_GET);
        $this->display();
    }

    public function add_program(){
        $shuju=M('schools');                                   // 学院数据
        $school=$shuju->select();
		$this->assign('school',getSchoolList());
        $this->assign('info',$_GET);
        $this->display();
    }

}