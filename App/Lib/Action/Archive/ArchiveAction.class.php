<?php
/**
 * 教学档案
 * @author shencp
 * Date: 14-04-03
 * Time: 上午09:32
 */
class ArchiveAction extends RightAction {
	
	private $model;
	/**
	 * 构造
	 **/
	public function __construct(){
		parent::__construct();
		$this->model = M("SqlsrvModel:");
	}
	
	public function getjsonschools()
	{
		$jsonschools = array();
		
		$jsonschool["id"] = "0";
		$jsonschool["text"] = "全部";
		$jsonschool["parentId"] = "-1";
		array_push($jsonschools, $jsonschool);
		
		$schools = M('schools')->select();
		foreach($schools as $school)
		{
			$jsonschool["id"] = trim($school["SCHOOL"]);
			$jsonschool["text"] = trim($school["NAME"]);
			$parentId = "0";
			if (trim($school["PARENT"]))
			{
				$parentId = trim($school["PARENT"]);
			}
			$jsonschool["parentId"] = $parentId;
				
			array_push($jsonschools, $jsonschool);
		}	
		
		$jsonschools = create_tree($jsonschools,"-1");		
		
		$this->ajaxReturn($jsonschools,"JSON");	
	}

	public function getjsontgroups()
	{
//		if (isset($_GET["SCHOOL"]))
		{
			$jsontgroups = array();
			
			$jsontgroup["id"] = "";
			$jsontgroup["text"] = "请选择";				
			array_push($jsontgroups, $jsontgroup);
				
			$schools = M('tgroups')->query(" select * from __TABLE__ order by ORDERBY ");
			foreach($schools as $school)
			{
				$jsontgroup["id"] = trim($school["TGROUP"]);
				$jsontgroup["text"] = trim($school["NAME"]);
					
				array_push($jsontgroups, $jsontgroup);
			}
			
			$this->ajaxReturn($jsontgroups,"JSON");
		}		
	}
	
	/**
	 * 添加新教师
	 */
	public function add(){
		if($this->_hasJson){
			//获取添加新教师信息
			 $ary=array(":NAME"=>$_POST["NAME"],":TEACHERNO"=>$_POST["TEACHERNO"],
			 		":POSITION"=>$_POST["POSITION"],":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"],
			 		":SEX"=>$_POST["SEX"],":YEAR"=>$_POST["YEAR"],
			 		":TYPE"=>$_POST["TYPE"],":USERNAME"=>$_POST["TEACHERNO"],
			 		":PWD"=>$_POST["PWD"],":TEACHER_NO"=>$_POST["TEACHERNO"]);
			 //开始添加
			 $sql=$this->model->getSqlMap("Archive/insertTeacher.sql");
	         $bool=$this->model->sqlExecute($sql,$ary);
	         if($bool===false) echo false;
	         else echo true;
		}else{
			//获取当前学年
			$year = $this->model->sqlFind($this->model->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
			$this->assign("year",$year["YEAR"]);
			//获取教师类型
			$this->assign("teachertype",M("teachertype")->select());
			//获取教师职称
			$this->assign("position",M("positions")->select());
			//所有学部			
			$this->assign('school',M('schools')->query(" select * from __TABLE__ where (PARENT is null or ltrim(rtrim(PARENT)) = '') order by ORDERBY "));
			//性别
			$sexcode = M("sexcode")->query("select rtrim(code) code,rtrim(name) name from __TABLE__");
			$this->assign("sex",$sexcode);
			//当前登录用户所在学部	
			$teacherNo=$_SESSION["S_USER_INFO"]["TEACHERNO"];
			$user_school = $this->model->sqlFind("SELECT T.SCHOOL FROM TEACHERS T WHERE T.TEACHERNO='$teacherNo'");

			$this->assign("user_school",$user_school["SCHOOL"]);
			
			//所有教研组
			$this->assign("tgroup",M("tgroups")->query(" select * from __TABLE__ order by ORDERBY "));

			$this->assign('isdean',$this->checkUserIsDean(getUsername()));
			$this->display();
		}
	}
	
	/**
	 * 教师号验证
	 */
	public function validation(){
		if($this->_hasJson){
			//查询教师号是否存在
			$teacherno=$this->model->sqlFind("SELECT COUNT(*) [COUNT] FROM TEACHERS T WHERE T.TEACHERNO='".trim($_POST["VALUE"])."'");
			echo $teacherno["COUNT"];
		}
	}
	
	/**
	 * 教师查询
	 */
	public function query(){
		if($this->_hasJson){		
			$bind = array(":TEACHERNO"=>doWithBindStr($_POST["TEACHERNO"]),
					":NAME"=>doWithBindStr($_POST["NAME"]),":SEX"=>$_POST["SEX"],
					":POSITION"=>$_POST["POSITION"],":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"]); 
			$arr = array("total"=>0, "rows"=>array());

			$sql = $this->model->getSqlMap("Archive/queryTeacherCount.sql");
			$count = $this->model->sqlCount($sql,$bind);
			$arr["total"] = intval($count);
				
			if($arr["total"] > 0){
				$sql = $this->model->getPageSql(null,"Archive/queryTeacherList.sql", $this->_pageDataIndex, $this->_pageSize);
				$arr["rows"] = $this->model->sqlQuery($sql,$bind);
			}
            //var_dump($bind);
            //echo $this->model->getLastSql();
			$this->ajaxReturn($arr,"JSON");
			exit;
		}

		//性别，格式：json
		//性别
		$sexcode = M("sexcode")->query("select rtrim(code) code,rtrim(name) name from __TABLE__");
		$this->assign("sex",$sexcode);
		$sex=array();
		//性别数据转成json格式给前台的combobox使用
		foreach($sexcode as $val){
			$jsonMap["text"]=trim($val["name"]);
			$jsonMap["value"]=$val["code"];
			array_push($sex,$jsonMap);
		}
		$this->assign("sjson",json_encode($sex));

		//获取教师职称
		$position=M("positions")->select();
		$this->assign("position",$position);
		$pjson=array();
		//把教师职称数据转成json格式给前台的combobox使用
		foreach($position as $val){
			$jsonMap["text"]=trim($val["VALUE"]);
			$jsonMap["value"]=$val["NAME"];
			array_push($pjson,$jsonMap);
		}
		$this->assign("pjson",json_encode($pjson));
		
		//获取教师类型
		$teachertype=M("teachertype")->select();
		$tjson=array();
		//把教师类型据转成json格式给前台的combobox使用
		foreach($teachertype as $val){
			$jsonMap["text"]=trim($val["VALUE"]);
			$jsonMap["value"]=$val["NAME"];
			array_push($tjson,$jsonMap);
		}
		$this->assign("tjson",json_encode($tjson));
		
		//当前用户所在学部	
		$teacherNo=$_SESSION["S_USER_INFO"]["TEACHERNO"];
		$user_school = $this->model->sqlFind("SELECT T.SCHOOL FROM TEACHERS T WHERE T.TEACHERNO='$teacherNo'");
		$this->assign("user_school",$user_school["SCHOOL"]);

		//所有教研组
		$this->assign("tgroup",M("tgroups")->query(" select * from __TABLE__ order by ORDERBY "));
		$this->assign('school',getSchoolList());
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
		$this->display();
	}

	/**
	 * excel导入编辑
	 */
	public function excelimpedit(){
		
		$this->display();
	}
	
	/**
	 * excel导入保存
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

		TaskMonitorModel::init(session("S_USER_NAME"), "教师信息excel导入");
		
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
//		$columns = array ( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J' );
//		$keysInFile = array ( '排序号', '登陆账号', '教工编号', '姓名', '手机长号', '手机短号', '类别', '教研组', '教学部', '职能部门领导' );
//		foreach( $columns as $keyIndex => $columnIndex ){
//			if ( $keywords[$columnIndex] != $keysInFile[$keyIndex] ){
//				echo $warning . $columnIndex . '列应为' . $keysInFile[$keyIndex] . '，而非' . $keywords[$columnIndex];
//				exit();
//			}
//		}

		TaskMonitorModel::run(session("S_USER_NAME"),"教师信息excel导入", count($sheetData)-1);
		
		$result = array();
    	$count = 0;
		$succount = 0;
		$failcount = 0;
		
		$key_val = array ( '排序号'=>'A', '登陆账号'=>'B', '教工编号'=>'C', '姓名'=>'D', '手机长号'=>'E', '手机短号'=>'F', '类别'=>'G', '教研组'=>'H', '教学部'=>'I', '职能部门领导'=>'J' );		
		foreach ( $sheetData as $key => $words ){
			if ( $key != 1 ){	
				$count++;
				
				$teacherno = trim($words[$key_val['教工编号']]);
				$name = trim($words[$key_val['姓名']]);
				if (isset($teacherno) && !empty($teacherno) && isset($name) && !empty($name))
				{
					$username = trim($words[$key_val['登陆账号']]);
					$name = trim($words[$key_val['姓名']]);
					$mobilelong = trim($words[$key_val['手机长号']]);
					$mobileshort = trim($words[$key_val['手机短号']]);
					$typename = trim($words[$key_val['类别']]);
					$tgroupname = trim($words[$key_val['教研组']]);
					$schoolname = trim($words[$key_val['教学部']]);
					$department = trim($words[$key_val['职能部门领导']]);
					
					$typeno = "";
					if (isset($typename) && !empty($typename))
					{
						$typeOBJ = $this->model->sqlFind("SELECT * FROM TEACHERTYPE WHERE upper(ltrim(rtrim(VALUE))) = :VALUE ",array(":VALUE" => strtoupper($typename)));
						if ($typeOBJ)
						{
							$typeno = $typeOBJ['NAME'];
						}
					}
					
					$schoolno = "";
					if (isset($schoolname) && !empty($schoolname))
					{
						$schoolOBJ = $this->model->sqlFind("SELECT * FROM SCHOOLS WHERE upper(ltrim(rtrim(NAME))) = :NAME ",array(":NAME" => strtoupper($schoolname)));
						if ($schoolOBJ)
						{
							$schoolno = $schoolOBJ['SCHOOL'];
						
							
						}
					}
					$tgroupno = "";
					if (isset($tgroupname) && !empty($tgroupname))
					{
						$tgroupOBJ = $this->model->sqlFind("SELECT * FROM TGROUPS WHERE upper(ltrim(rtrim(NAME))) = :NAME ",array(":NAME" => strtoupper($tgroupname)));
						if ($tgroupOBJ)
						{
							$tgroupno = $tgroupOBJ['TGROUP'];
						}
					}
					
					$teacherOBJ = $this->model->sqlFind("SELECT * FROM TEACHERS WHERE upper(ltrim(rtrim(TEACHERNO))) = :TEACHERNO ",array(":TEACHERNO" => strtoupper($teacherno)));
					if ($teacherOBJ)
					{	
						$failcount++;
						
						$iresult = array();
						$iresult["row"] = $key;
						$iresult["teacherno"] = $teacherno;
						$iresult["name"] = $name;
						$iresult["content"] = "相同教工编号数据库已存在";
												
						array_push($result, $iresult);
						
    					
    					TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$teacherno,2);
					}
					else
					{
						$ary = array(
								":TEACHERNO" => $teacherno,
								":NAME" => $name,
								":MOBILE_LONG" => $mobilelong,
								":MOBILE_SHORT" => $mobileshort,
								":TYPE" => $typeno,
								":TGROUP" => $tgroupno,
								":SCHOOL" => $schoolno,
								":Department" => $department
						);
						
						 //开始添加
						$sql = " INSERT INTO TEACHERS(TEACHERNO,NAME,MOBILE_LONG,MOBILE_SHORT,TYPE,TGROUP,SCHOOL,Department,SEX,[POSITION])VALUES(:TEACHERNO,:NAME,:MOBILE_LONG,:MOBILE_SHORT,:TYPE,:TGROUP,:SCHOOL,:Department,'',''); ";
						
						$bool = $this->model->sqlExecute($sql,$ary);
						
						if ($bool)
						{
							$succount++;
							
							if (isset($username) && !empty($username))
							{
								$user = $this->model->sqlFind("SELECT * FROM USERS WHERE upper(ltrim(rtrim(USERNAME))) = :USERNAME ",array(":USERNAME" => strtoupper($username)));
								if ($user)
								{
								}
								else
								{
									$ary = array(
											":USERNAME" => $username,
											":PASSWORD" => $teacherno,
											":TEACHERNO" => $teacherno
									);
										
									//开始添加
									$sql = " INSERT INTO USERS(USERNAME,PASSWORD,TEACHERNO)VALUES(:USERNAME,:PASSWORD,:TEACHERNO); ";
										
									$bool=$this->model->sqlExecute($sql,$ary);
								}
							}
							
    						TaskMonitorModel::next(session("S_USER_NAME"),$count,true,$teacherno,2);
						}
						else
						{
							$failcount++;
							
							$iresult = array();
							$iresult["row"] = $key;
							$iresult["teacherno"] = $teacherno;
							$iresult["name"] = $name;
							$iresult["content"] = "数据库出错导入失败";
							
							array_push($result, $iresult);
							
    						TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$teacherno,2);
						}
						
//						var_dump($teacherno);echo "_$bool<br>";
					}
						
				}
				else
				{
					$failcount++;
						
					$iresult = array();
					$iresult["row"] = $key;
					$iresult["content"] = "教工编号或姓名为空没有导入";
						
					array_push($result, $iresult);
					
    				TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$teacherno,2);
				}
			}
			else {
			}
		}

		TaskMonitorModel::done(session("S_USER_NAME"));
		 
		wincache_ucache_set(session("S_USER_NAME")."_RESULT", $result);
		wincache_ucache_set(session("S_USER_NAME")."_succount", $succount);
		wincache_ucache_set(session("S_USER_NAME")."_failcount", $failcount);
		
//		$this->assign("result",$result);
//		$this->assign("succount",$succount);
//		$this->assign("failcount",$failcount);
//		$this->display("excelimpresult");
		
	}

	/**
	 * excel导入结果输出
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

	/**
	 * excel导出更欣课表老师名单
	 */
	public function excelexpGXKBarchive(){
	
		TaskMonitorModel::init(session("S_USER_NAME"), "excel导出用于更欣课表的老师名单");
			
		$pageSize = 500;
		
		$bind = array(":TEACHERNO"=>doWithBindStr($_POST["TEACHERNO"]),
					":NAME"=>doWithBindStr($_POST["NAME"]),":SEX"=>$_POST["SEX"],
					":POSITION"=>$_POST["POSITION"],":SCHOOL"=>$_POST["SCHOOL"],":TGROUP"=>$_POST["TGROUP"]); 
			
		$sql_count = " SELECT COUNT(*) as icount "
				   . " FROM TEACHERS T " 
				   . " WHERE T.TEACHERNO LIKE :TEACHERNO "
				   . " AND T.NAME LIKE :NAME "
				   . " AND T.SEX LIKE :SEX "
				   . " AND T.[POSITION] LIKE :POSITION "
				   . " AND (T.SCHOOL LIKE :SCHOOL or T.TGROUP LIKE :TGROUP) ";
			
		$data = $this->model->sqlFind($sql_count,$bind);
		$totalCount = $data['icount'];
	
		if ($totalCount > 0)
		{
			TaskMonitorModel::run(session("S_USER_NAME"),"excel导出用于更欣课表的老师名单", $totalCount);
	
			$inputFileType = 'Excel5';
			$inputFileName = $_SERVER['DOCUMENT_ROOT'] . "\\res\\templates\\archive_archive_expGXKB.xls";
	
			vendor("PHPExcel.PHPExcel");
	
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objPHPExcel = $objReader->load($inputFileName);
			$targetws = $objPHPExcel->getSheet(0);
	
			$totalPage = ceil($totalCount / $pageSize);
	
			$pageNumber = 1;
			$startNumber = 0;
	
			$sql_select = " SELECT T.TEACHERNO, "
					    . " T.NAME, "
					    . " P.[VALUE] [POSITION], "
					    . " T2.[VALUE] [TYPE], "
					    . " T.SCHOOL, "
					    . " S.NAME SCHOOLNAME, "
					    . " S1.NAME TGROUP " 
					    . " FROM TEACHERS T "
					    . " LEFT JOIN POSITIONS P ON T.[POSITION]=P.NAME " 
					    . " LEFT JOIN TEACHERTYPE T2 ON T.TYPE=T2.NAME " 
					    . " LEFT JOIN SCHOOLS S ON T.SCHOOL=S.SCHOOL " 
					    . " LEFT JOIN SCHOOLS S1 ON T.TGROUP=S1.SCHOOL " 
					    . " LEFT JOIN SEXCODE S2 ON S2.CODE=T.SEX "
					    . " WHERE T.TEACHERNO LIKE :TEACHERNO "
					    . " AND T.NAME LIKE :NAME "
					    . " AND T.SEX LIKE :SEX "
					    . " AND T.[POSITION] LIKE :POSITION "
					    . " AND (T.SCHOOL LIKE :SCHOOL or T.TGROUP LIKE :TGROUP) "
					    . " ORDER BY T.TEACHERNO ";
	
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
			
			$filename = "用于更欣课表的老师名单_" . date ( 'Y-m-d', time () );
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
	 * 删除
	 */
	public function del(){
		$newids="";
		foreach($_POST["in"] as $val){
			$newids.="'".$val."',";
		}
		$newids=rtrim($newids,",");
		$sql="delete from teachers where teacherno in($newids);delete from users where teacherno in($newids)";
		$row=$this->model->sqlExecute($sql);
		if($row) echo true;
		else echo false;
	}
	
	/**
	 * 修改更新
	 */
	public function update(){
		if($this->_hasJson){
            $shuju=new SqlsrvModel();
			$sex_sql=trim($_POST["SEX"]);//性别
			$position_sql=trim($_POST["POSITION"]);//职称
			$type_sql=trim($_POST["TYPE"]);//类型
			$tgroup_sql=trim($_POST["TGROUP"]);//类型
			//开始更新
			$sql="UPDATE TEACHERS SET NAME='".trim($_POST["NAME"])."',EnterYear='".trim($_POST["YEAR"])."'";
				
			if(strlen($sex_sql) < 3){
				$sql=$sql.",SEX='$sex_sql'";
			}
			if(strlen($position_sql) < 3){
				$sql=$sql.",POSITION='$position_sql'";
			}
			if(strlen($type_sql) < 3){
				$sql=$sql.",TYPE='$type_sql'";
			}
			if(strlen($tgroup_sql) < 3){
				$sql=$sql.",TGROUP='$tgroup_sql'";
			}
			$sql=$sql." WHERE TEACHERNO = '".trim($_POST["TEACHERNO"])."'";

          // $this->model->sqlExecute("update teachers set name='{$_POST["NAME"]}',SEX='{$_POST["SEX"]}',POSITION='{$_POST["POSITION"]}',TYPE='{$_POST["TYPE"]}' where TEACHERNO={}");
			$bool=$shuju->SqlExecute($sql);
			if($bool===false) echo false;
			else echo true;
		}else{
			//修改口令
			$pwd=$_POST['PWD'];$no=$_POST['TEACHERNO'];
			$sql="UPDATE USERS SET PASSWORD = '$pwd' WHERE TEACHERNO = '$no'";
			$bool=$this->model->sqlQuery($sql);
			if($bool===false) echo false;
			else echo true;
		}
	}
	
	/**
	 * 编辑详细页面
	 */
	public function edit(){
		if($this->_hasJson){
			//更新教师基本信息
			$Birth=trim($_POST["Birth"])==""?null:trim($_POST["Birth"]);
			$HDate=trim($_POST["HDate"])==""?null:trim($_POST["HDate"]);
			$PDate=trim($_POST["PDate"])==""?null:trim($_POST["PDate"]);
			$teacherno=doWithBindStr($_POST["TEACHERNO"]);
			
			$bind=array(":SEX"=>trim($_POST["SEX"]),":Birth"=>$Birth,":ID"=>trim($_POST["ID"]),
			":Nationality"=>trim($_POST["Nationality"]),":Party"=>trim($_POST["Party"]),":Department"=>trim($_POST["Department"]),":TGROUP"=>trim($_POST["TGROUP"]),
			":HeadShip"=>trim($_POST["HeadShip"]),":HDate"=>$HDate,":Profession"=>trim($_POST["Profession"]),
			":PDate"=>$PDate,":PSubject"=>trim($_POST["PSubject"]),":EduLevel"=>trim($_POST["EduLevel"]),
			":ESchool"=>trim($_POST["ESchool"]),":Degree"=>trim($_POST["Degree"]),":DSchool"=>trim($_POST["DSchool"]),
			":MOBILE_LONG"=>trim($_POST["MOBILE_LONG"]),":MOBILE_SHORT"=>trim($_POST["MOBILE_SHORT"]),":Tel"=>trim($_POST["Tel"]),":Email"=>trim($_POST["Email"]),":TEACHERNO"=>$teacherno);
			
			if($teacherno != null && $teacherno != ""){
				$sql=$this->model->getSqlMap("Archive/updateTeacher.sql");
				$row=$this->model->sqlExecute($sql,$bind);
				if($row) echo true;
				else echo false;
			}
		}else{
			$teacherno=iconv("gb2312","utf-8",trim($_GET["TEACHERNO"]));
			//教师详情相关数据查询
			$this->assign("national",M("nationalitycode")->select());//民族
			$this->assign("partycode",M("partycode")->select());//政治面貌
			$this->assign("pro",M("professioncode")->select());//现任职称
			$this->assign("edu",M("edulevelcode")->select());//最高学历
			$this->assign("deg",M("degreecode")->select());//最高学位
			$this->assign("honorl",M("honorlevelcode")->select());//获奖级别
			$sexcode = M("sexcode")->query("select rtrim(code) code,rtrim(name) name from __TABLE__");//性别
			$this->assign("sex",$sexcode);
			
			if($teacherno != null && $teacherno != ""){
				//获取教师基本信息
				$sql=$this->model->getSqlMap("Archive/getTeacher.sql");
				$data = $this->model->sqlFind($sql,array(":TEACHERNO"=>$teacherno));
				$this->assign("t",$data);
				
				//判断登录用户所在学部是否与待编辑教师相同
				$is_school="false";
				$user_teacherno=$_SESSION["S_USER_INFO"]["TEACHERNO"];
				$user_school = $this->model->sqlFind("SELECT T.SCHOOL FROM TEACHERS T WHERE T.TEACHERNO='$user_teacherno'");
				if($data["SCHOOL"] == $user_school["SCHOOL"] || isDeanByUsername(getUsername())){
					$is_school="true";
				}
				$this->assign("is_school",$is_school);
				
				
				//所有教研组
				$this->assign("tgroup",M("tgroups")->query(" select * from __TABLE__ order by ORDERBY "));
				
				
				
				//获取教师学习与工作经历
				$study = M("study");
				$sql="select convert(varchar(10), startdate, 23) startdate,convert(varchar(10),enddate, 23) enddate,".
				"school,recno from __TABLE__ where teacherno = '$teacherno' order by startdate asc";
				$studyData=$study->query($sql);
				$this->assign("study",$studyData);
				
				//获取各类获奖情况
				$honor = M("honor");
				$sql="select h1.name,convert(varchar(10),[date], 23) [date],department,h2.name [level],".
					"myorder,recno from __TABLE__ h1 left join honorlevelcode h2 on ".
					"h1.[level]=h2.code where teacherno='$teacherno' order by [date] asc";
				$honorData=$honor->query($sql);
				$this->assign("honor",$honorData);
				
				//获取论文、编写教材、科研教研成果一览表
				$thesis = M("thesis");
				$sql="select name,publish,content,honor,recno from __TABLE__ where teacherno = '$teacherno'";
				$thesisData=$thesis->query($sql);
				$this->assign("thesis",$thesisData);
			}
			$this->display();
		}
	}
	
	/**
	 * 编辑学习与工作经历
	 */
	public function editStudy(){
		if($this->_hasJson){
			//新增
			$enddate=trim($_POST["ENDDATE"])==""?null:trim($_POST["ENDDATE"]);
			$ary=array(":STARTDATE"=>$_POST["STARTDATE"],":ENDDATE"=>$enddate,
						":SCHOOL"=>$_POST["SCHOOL"],":TEACHERNO"=>$_POST["TEACHERNO"]);
			
			$sql=$this->model->getSqlMap("Archive/insertStudy.sql");
			$row=$this->model->sqlExecute($sql,$ary);
			if($row) echo true;
			else echo false;
		}else{
			//删除
			$recno=trim($_POST["recno"]);
			if($recno!="" && $recno!=null){
				$row=$this->model->sqlExecute("delete from study where recno ='$recno'");
				if($row) echo true;
				else echo false;
			}
		}
	}
	
	/**
	 * 编辑各类获奖情况
	 */
	public function editHonor(){
		if($this->_hasJson){
			//新增
			$date=trim($_POST["DATE"])==""?null:trim($_POST["DATE"]);
			$ary=array(":NAME"=>$_POST["NAME"],":DATE"=>$date,":DEPARTMENT"=>$_POST["DEPARTMENT"],
					":LEVEL"=>$_POST["LEVEL"],":MYORDER"=>$_POST["MYORDER"],":TEACHERNO"=>$_POST["TEACHERNO"]);
			
			$sql=$this->model->getSqlMap("Archive/insertHonor.sql");
			$row=$this->model->sqlExecute($sql,$ary);
			if($row) echo true;
			else echo false;
		}else{
			//删除
			$recno=trim($_POST["recno"]);
			if($recno!="" && $recno!=null){
				$row=$this->model->sqlExecute("delete from honor where recno ='$recno'");
				if($row) echo true;
				else echo false;
			}
		}
	}
	
	/**
	 * 编辑论文、编写教材、科研教研成果一览表
	 */
	public function editThesis(){
		if($this->_hasJson){
			//新增
			$ary=array(":NAME"=>$_POST["NAME"],":PUBLISH"=>$_POST["PUBLISH"],":CONTENT"=>$_POST["CONTENT"],
					":HONOR"=>$_POST["HONOR"],":TEACHERNO"=>$_POST["TEACHERNO"]);
			
			$sql=$this->model->getSqlMap("Archive/insertThesis.sql");
			$row=$this->model->sqlExecute($sql,$ary);
			if($row) echo true;
			else echo false;
		}else{
			//删除
			$recno=trim($_POST["recno"]);
			if($recno!="" && $recno!=null){
				$row=$this->model->sqlExecute("delete from thesis where recno ='$recno'");
				if($row) echo true;
				else echo false;
			}
		}
	}
}