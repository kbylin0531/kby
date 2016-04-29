<?php
class RoomAction extends RightAction{
    private $sy;
    private $pageYE;                //todo:最大页面
    private $mdl;
    private $unit=array();
    private $base;
    public function __construct(){
        parent::__construct();
        $this->sy=$_POST['arr'];
        $this->mdl=new SqlsrvModel();
        $this->base='Room/';
        $this->assign('quanxian',trim($_SESSION['S_USER_INFO']['ROLES']));
    }

    public function getjsonschools()
    {
    	$rootId = "0";
    	
    	$jsonschools = array();
    	if ($_GET["select"])
    	{
    		$rootId = "-1";

    		$jsonschool["id"] = "0";
    		$jsonschool["text"] = "全部";
    		$jsonschool["parentId"] = "-1";
    		array_push($jsonschools, $jsonschool);
    	}

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
    		
    	$jsonschools = create_tree($jsonschools,$rootId);
    
    	$this->ajaxReturn($jsonschools,"JSON");
    }
    
    public function selectroom(){           //教室查询的主页
        if($this->_hasJson){
            IF($_POST['RESERVED']>1){
                $RESERVED=0;                                                         //所有情况
                $UNRESERVED=1;
            }else{
                $RESERVED=$_POST['RESERVED'];
                $UNRESERVED=$_POST['RESERVED'];                                              //非所有情况
            };
            if($_POST['STATUS']>1){
                $STATUS=0;
                $UNSTATUS=1;                                                         //所有情况
            }else{
                $STATUS=$_POST['STATUS'];
                $UNSTATUS=$_POST['STATUS'];                                         //非所有情况
            }
            $page=array(':START'=>$this->_pageDataIndex,':END'=>$this->_pageDataIndex+$this->_pageSize);

            $bind=array(':ROOMNO'=>doWithBindStr($_POST['ROOMNO']),':AREA'=>doWithBindStr($_POST['AREA']),':BUILDING'=>doWithBindStr($_POST['BUILDING']),':EQUIPMENT'=>doWithBindStr($_POST['EQUIPMENT']),
                ':NO'=>doWithBindStr($_POST['NO']),':PRIORITY'=>doWithBindStr($_POST['PRIORITY']),':RESERVED'=>$RESERVED,':UNRESERVED'=>$UNRESERVED,':STATUS'=>$STATUS,':UNSTATUS'=>$UNSTATUS,
                ':SEATSDOWN'=>$_POST['SEATSDOWN'],':SEATSUP'=>$_POST['SEATSUP'],':TESTERSDOWN'=>$_POST['TESTERSDOWN'],':TESTERSUP'=>$_POST['TESTERSUP'],
                ':USAGE'=>doWithBindStr($_POST['USAGE']));

            $bind2=array_merge($bind,$page);
            $total=$this->mdl->sqlFind($this->mdl->getSqlMap('room/roomcount.SQL'),$bind);

            if($one['total']=$total['']){
                $one['rows']=$this->mdl->sqlQuery($this->mdl->getSqlMap('room/roomselect.SQL'),$bind2);
            }else{
                $one['rows']=array();
            }
            $this->ajaxReturn($one,'JSON');

            exit;
        }
        $this->xiala('areas','area');  //课时段信息
        
        $this->assign('school',getSchoolList());  ////学校数据
        
        
        $this->xiala('roomoptions','roomoption');  //教室类型选择
        $this->assign("yearTerm",$this->mdl->sqlFind("select * from YEAR_TERM where [TYPE]='O'"));
        // $timesectors=("select * from time")
        $this->xiala('timesections','timesectors');
        $this->display();

    }

    /**
     * excel导入编辑
     */
    public function excelimproomedit(){
    
    	$this->display();
    }
    
    /**
     * excel导入保存
     */
    public function excelimproomsave(){
    
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
    
    	TaskMonitorModel::init(session("S_USER_NAME"), "教室信息excel导入");
    	
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
    	//		$columns = array ( 'A', 'B', 'C', 'D', 'E', 'F' );
    	//		$keysInFile = array ( '教室代码', '教室名称', '教室面积', '容纳人数', '教室位置', '所属校区' );
    	//		foreach( $columns as $keyIndex => $columnIndex ){
    	//			if ( $keywords[$columnIndex] != $keysInFile[$keyIndex] ){
    	//				echo $warning . $columnIndex . '列应为' . $keysInFile[$keyIndex] . '，而非' . $keywords[$columnIndex];
    	//				exit();
    	//			}
    	//		}
    	
    	TaskMonitorModel::run(session("S_USER_NAME"),"教室信息excel导入", count($sheetData)-1);
    
    	$result = array();
    	$count = 0;
    	$succount = 0;
    	$failcount = 0;
    
    	$key_val = array ( '教室代码'=>'A', '教室名称'=>'B', '教室面积'=>'C', '容纳人数'=>'D', '教室位置'=>'E', '所属校区'=>'F' );
    	foreach ( $sheetData as $key => $words ){
    		if ( $key != 1 ){
    			$count++;    			 
    			
    			$roomno = trim($words[$key_val['教室代码']]);
    			$roomname = trim($words[$key_val['教室名称']]);
    			if (isset($roomno) && !empty($roomno) && isset($roomname) && !empty($roomname))
    			{
    				$roomarea = trim($words[$key_val['教室面积']]);
    				$seats = trim($words[$key_val['容纳人数']]);
    				$testers = trim($words[$key_val['容纳人数']]);
    				$building = trim($words[$key_val['教室位置']]);
    				$area = trim($words[$key_val['所属校区']]);
    				$equipment = "0";
    				$status = 1;
    				$priority = "0";
    				$usage = "N";
    				$reserved = 1;
    				
    				$areano = "";
    				if (isset($area) && !empty($area))
    				{
    					$areaOBJ = $this->mdl->sqlFind("SELECT * FROM AREAS WHERE upper(ltrim(rtrim(VALUE))) = :VALUE ",array(":VALUE" => strtoupper($area)));
    					if ($areaOBJ)
    					{
    						$areano = $areaOBJ['NAME'];
    					}
    				}
    					
    				$roomOBJ = $this->mdl->sqlFind("SELECT * FROM CLASSROOMS WHERE upper(ltrim(rtrim(ROOMNO))) = :ROOMNO ",array(":ROOMNO" => strtoupper($roomno)));
    				if ($roomOBJ)
    				{
    					$failcount++;
    
    					$iresult = array();
    					$iresult["row"] = $key;
    					$iresult["roomno"] = $roomno;
    					$iresult["roomname"] = $roomname;
    					$iresult["content"] = "相同教室代码数据库已存在";
    
    					array_push($result, $iresult);
    					
    					TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$roomno,2);
    
    				}
    				else
    				{
    					$ary = array(
    							":ROOMNO" => $roomno,
    							":NO" => $roomname,
    							":BUILDING" => $building,
    							":SEATS" => $seats,
    							":TESTERS" => $testers,
    							":AREA" => $areano,
    							":EQUIPMENT" => $equipment,
    							":STATUS" => $status,
    							":PRIORITY" => $priority,
    							":USAGE" => $usage,
    							":RESERVED" => $reserved,
    							":ROOM_AREA" => $roomarea
    					);
    
    					//开始添加
    					$sql = " insert into CLASSROOMS(ROOMNO,NO,BUILDING,SEATS,TESTERS,AREA,EQUIPMENT,STATUS,PRIORITY,USAGE,RESERVED,ROOM_AREA) VALUES(:ROOMNO,:NO,:BUILDING,:SEATS,:TESTERS,:AREA,:EQUIPMENT,:STATUS,:PRIORITY,:USAGE,:RESERVED,:ROOM_AREA); ";
    
    					$bool = $this->mdl->sqlExecute($sql,$ary);
    
    					if ($bool)
    					{
    						$succount++;    	

    						TaskMonitorModel::next(session("S_USER_NAME"),$count,true,$roomno,2);
    					}
    					else
    					{
    						$failcount++;
    							
    						$iresult = array();
    						$iresult["row"] = $key;
    						$iresult["roomno"] = $roomno;
    						$iresult["roomname"] = $roomname;
    						$iresult["content"] = "数据库出错导入失败";
    							
    						array_push($result, $iresult);
    						
    						TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$roomno,2);
    					}
    
    					//						var_dump($teacherno);echo "_$bool<br>";
    				}
    
    			}
    			else
    			{
    				$failcount++;
    
    				$iresult = array();
    				$iresult["row"] = $key;
    				$iresult["content"] = "教室代码或教室名称为空没有导入";
    
    				array_push($result, $iresult);
    				
    				TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$roomno,2);
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
//    	$this->assign("failcount",$failcount);
//    	$this->display("excelimproomresult");
    
    }
    
    /**
     * excel导入结果输出
     */
    public function excelimproomresult(){
    	
    	$result = wincache_ucache_get(session("S_USER_NAME")."_RESULT");
    	wincache_ucache_delete(session("S_USER_NAME")."_RESULT");

    	$succount = wincache_ucache_get(session("S_USER_NAME")."_succount");
    	wincache_ucache_delete(session("S_USER_NAME")."_succount");
    	 
    	$failcount = wincache_ucache_get(session("S_USER_NAME")."_failcount");
    	wincache_ucache_delete(session("S_USER_NAME")."_failcount");
    	 
    	$this->assign("result",$result);
    	$this->assign("succount",$succount);
    	$this->assign("failcount",$failcount);
    	$this->display("excelimproomresult");    	 
    }
    
    /**
     * excel导出更欣课表场地名单
     */
    public function excelexpGXKBroom(){
    
    	TaskMonitorModel::init(session("S_USER_NAME"), "excel导出用于更欣课表的场地名单");
    		
    	$pageSize = 500;
    	
    	if ($_POST['RESERVED']>1)
    	{
    		$RESERVED = 0;
    		$UNRESERVED = 1;
    	}
    	else
    	{
    		$RESERVED = $_POST['RESERVED'];
    		$UNRESERVED = $_POST['RESERVED'];
    	}
    	if($_POST['STATUS']>1)
    	{
    		$STATUS = 0;
    		$UNSTATUS = 1;
    	}
    	else
    	{
    		$STATUS=$_POST['STATUS'];
    		$UNSTATUS=$_POST['STATUS'];
    	}    	
    	$bind = array(':ROOMNO'=>doWithBindStr($_POST['ROOMNO']),':AREA'=>doWithBindStr($_POST['AREA']),':BUILDING'=>doWithBindStr($_POST['BUILDING']),':EQUIPMENT'=>doWithBindStr($_POST['EQUIPMENT']),
                ':NO'=>doWithBindStr($_POST['NO']),':PRIORITY'=>doWithBindStr($_POST['PRIORITY']),':RESERVED'=>$RESERVED,':UNRESERVED'=>$UNRESERVED,':STATUS'=>$STATUS,':UNSTATUS'=>$UNSTATUS,
                ':SEATSDOWN'=>$_POST['SEATSDOWN'],':SEATSUP'=>$_POST['SEATSUP'],':TESTERSDOWN'=>$_POST['TESTERSDOWN'],':TESTERSUP'=>$_POST['TESTERSUP'],
                ':USAGE'=>doWithBindStr($_POST['USAGE']));
    	    		
    	$sql_count = " SELECT count(*) as icount "
                   . " FROM dbo.CLASSROOMS Dbo_classrooms "
                   . " left join dbo.AREAS Dbo_areas on Dbo_classrooms.AREA = Dbo_areas.NAME "
                   . " left join dbo.ROOMOPTIONS Dbo_roomoptions on Dbo_classrooms.EQUIPMENT = Dbo_roomoptions.NAME "
                   . " left join dbo.ROOMUSAGES Dbo_roomusages on Dbo_classrooms.USAGE = Dbo_roomusages.NAME "
                   . " left join dbo.SCHOOLS Dbo_schools on Dbo_classrooms.PRIORITY = Dbo_schools.SCHOOL "
                   . " WHERE ( Dbo_classrooms.ROOMNO LIKE :ROOMNO) "
                   . " AND  (Dbo_classrooms.AREA LIKE :AREA) "
                   . " AND  (Dbo_classrooms.BUILDING LIKE :BUILDING) "
                   . " AND  (Dbo_classrooms.EQUIPMENT LIKE :EQUIPMENT) "
                   . " AND  (Dbo_classrooms.NO LIKE :NO) "
                   . " AND  (Dbo_classrooms.PRIORITY LIKE :PRIORITY) "
                   . " AND  ( (Dbo_classrooms.RESERVED = :RESERVED)  OR  (Dbo_classrooms.RESERVED = :UNRESERVED) ) "
                   . " AND  ( (Dbo_classrooms.STATUS = :STATUS)  OR  (Dbo_classrooms.STATUS = :UNSTATUS) ) "
                   . " AND  ( (Dbo_classrooms.SEATS >= :SEATSDOWN)  AND  (Dbo_classrooms.SEATS <= :SEATSUP) ) "
                   . " AND  ( (Dbo_classrooms.TESTERS >= :TESTERSDOWN)  AND  (Dbo_classrooms.TESTERS <= :TESTERSUP) ) "
                   . " AND  (Dbo_classrooms.USAGE LIKE :USAGE) ";
    		
    	$data = $this->mdl->sqlFind($sql_count,$bind);
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
    
    		$sql_select = " SELECT Dbo_classrooms.ROOMNO, "
                        . " Dbo_classrooms.NO, "
                        . " Dbo_classrooms.JSN, "
                        . " Dbo_classrooms.BUILDING, "
                        . " Dbo_classrooms.AREA, "
                        . " Dbo_classrooms.SEATS, "
                        . " Dbo_classrooms.TESTERS, "
                        . " Dbo_classrooms.EQUIPMENT, "
                        . " Dbo_classrooms.STATUS, "
                        . " Dbo_classrooms.PRIORITY, "
                        . " Dbo_classrooms.USAGE, "
                        . " Dbo_classrooms.RESERVED, "
                        . " Dbo_areas.VALUE AS AREAVALUE, "
                        . " Dbo_roomoptions.VALUE AS EQUIPMENTVALUE, "
                        . " Dbo_roomusages.VALUE AS USAGEVALUE, "
                        . " Dbo_schools.NAME AS SCHOOLNAME, "
                        . " Dbo_classrooms.REM "
                        . " FROM dbo.CLASSROOMS Dbo_classrooms "
                        . " left join dbo.AREAS Dbo_areas on Dbo_classrooms.AREA = Dbo_areas.NAME "
                        . " left join dbo.ROOMOPTIONS Dbo_roomoptions on Dbo_classrooms.EQUIPMENT = Dbo_roomoptions.NAME "
                        . " left join dbo.ROOMUSAGES Dbo_roomusages on Dbo_classrooms.USAGE = Dbo_roomusages.NAME "
                        . " left join dbo.SCHOOLS Dbo_schools on Dbo_classrooms.PRIORITY = Dbo_schools.SCHOOL "
                        . " WHERE Dbo_classrooms.ROOMNO LIKE :ROOMNO "
                        . " AND  Dbo_classrooms.AREA LIKE :AREA "
                        . " AND  Dbo_classrooms.BUILDING LIKE :BUILDING "
                        . " AND  Dbo_classrooms.EQUIPMENT LIKE :EQUIPMENT "
                        . " AND  Dbo_classrooms.NO LIKE :NO "
                        . " AND  Dbo_classrooms.PRIORITY LIKE :PRIORITY "
                        . " AND  ( Dbo_classrooms.RESERVED = :RESERVED OR  Dbo_classrooms.RESERVED = :UNRESERVED ) "
                        . " AND  ( Dbo_classrooms.STATUS = :STATUS  OR  Dbo_classrooms.STATUS = :UNSTATUS ) "
                        . " AND  (Dbo_classrooms.SEATS >= :SEATSDOWN  AND  Dbo_classrooms.SEATS <= :SEATSUP) "
                        . " AND  ( Dbo_classrooms.TESTERS >= :TESTERSDOWN  AND  Dbo_classrooms.TESTERS <= :TESTERSUP) "
                        . " AND  Dbo_classrooms.USAGE LIKE :USAGE "
                        . " order by Dbo_classrooms.NO ";
    
    		$pagesql_select = $this->mdl->getPageSql($sql_select,null,$startNumber,$pageSize);
    
    		$result = $this->mdl->sqlQuery($pagesql_select,$bind);
    
    		if (count($result) > 0)
    		{
    			$this->set_expGXKBroom_xls($result, $pageNumber, $pageSize, $targetws);
    
    			while ($pageNumber < $totalPage)
    			{
    				$pageNumber++;
    				$startNumber = ($pageNumber -1) * $pageSize;
    
    				$pagesql_select = $this->mdl->getPageSql($sql_select,null,$startNumber,$pageSize);
    
    				$result = $this->mdl->sqlQuery($pagesql_select,$bind);
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
    
    /*
    * 获取下拉列表信息进行循环时用的
    */
    /* public function xiala($name,$as){
         $shuju=M($name);
         $one= $shuju->select();
         $this->assign($as,$one);
     }*/
    /*
     * 编辑教室的时候 获取教室信息方法
     */
    public function editroom(){
        $shuju=new SqlsrvModel();
        $sql=$shuju->getSqlMap('room/editRoom.SQL');
        $bind=array(':ROOMNO'=>$_POST['ROOMNO']);
        $one=$shuju->sqlFind($sql,$bind);
        foreach($one as $key=>$vl){
            $one[$key]=trim($vl);
        }
        echo json_encode($one);
    }

    /**
     * 修改教室信息
     */
    public function updateroom(){
        $roomModel = new RoomModel();
        $updFields=array(
            'NO'=>$_POST['NO'],
            'BUILDING'=>$_POST['BUILDING'],
            'SEATS'=>$_POST['SEATS'],
            'JSN'=>$_POST['JSN'],
            'TESTERS'=>$_POST['TESTERS'],
            'REM'=>$_POST['REM'],
            'AREA'=>$_POST['AREA'],
            'EQUIPMENT'=>$_POST['EQUIPMENT'],
            'STATUS'=>$_POST['STATUS'],
            'PRIORITY'=>$_POST['PRIORITY'],
            'USAGE'=>$_POST['USAGE'],
            'RESERVED'=>$_POST['RESERVED'],
            'ROOM_AREA'=>$_POST['ROOM_AREA'],
        );
        $rst  = $roomModel->updateRoom($_POST['ROOMNO'],$updFields);
        if(is_string($rst) || !$rst){
            $this->exitWithReport("更新教师号为[{$_POST['ROOMNO']}]的信息失败了！".$rst);
        }else{
            $this->exitWithReport("成功更新教室号为[{$_POST['ROOMNO']}]的教室的信息!",'info');
        }
    }

    /*
     * 删除教室的方法
     */
    public function deleteroom(){

        $shuju=new SqlsrvModel();
        $haha='';
        foreach($_POST['in'] as $val){
            $haha.="'".$val."',";
        }
        $haha=rtrim($haha,',');
        $sql="delete from CLASSROOMS where ROOMNO in ($haha)";
        $one=$shuju->sqlExecute($sql);
        if($one){
            echo 'true';
        }else{
            echo 'false';
        }
    }

    //显示addroom主页的方法
    public function addroom(){
        $this->xiala('areas','area');  //课时段信息
        $this->assign('school',getSchoolList());  ////学校数据
        $this->xiala('roomoptions','roomoption');  //教室类型选择
        $this->display();
    }
    /*
     * ROOM插入做ajax验证
     */
    public function roomyz(){
        $shuju=M('classrooms');
        $number['ROOMNO']=$_POST['ROOMNO'];
        if(strlen($number['ROOMNO'])!=9){
            exit('sev');
        }
        $one=$shuju->where($number)->select();
        if($one){
            echo 'false';
        }else{
            echo 'true';
        }
    }

    /**
     * 对room数据进行插入的方法
     */
    public function insertroom(){
        $model = new RoomModel();
        if(REQTAG === 'excel_import'){
            $rst = $model->importClassrooms($this->getUpload());
            if(empty($rst['index'])){
                echo '导入成功!';
            }else{
                echo '记录导入失败列:'.var_export($rst,true);
            }
            exit();
        }
        $fields = array(
            'ROOMNO' => $_POST['ROOMNO'],
            'NO' => $_POST['NO'],
            'BUILDING' => $_POST['BUILDING'],
            'SEATS' => $_POST['SEATS'],
            'TESTERS' => $_POST['TESTERS'],
            'REM' => $_POST['REM'],
            'JSN' => $_POST['JSN'],
            'AREA' => $_POST['AREA'],
            'EQUIPMENT' => $_POST['EQUIPMENT'],
            'STATUS' => $_POST['STATUS'],
            'PRIORITY' => $_POST['PRIORITY'],
            'USAGE' => $_POST['USAGE'],
            'RESERVED' => $_POST['RESERVED'],
            'ROOM_AREA' => $_POST['ROOM_AREA'],
        );
        $rst = $model->createRoom($fields);
        if(is_string($rst) or !$rst){
            $this->exitWithReport('添加教室失败!'.$rst);
        }else{
            $this->exitWithReport('添加教室成功!');
        }
    }


    /*
     * 查看教室使用情况
     *
     */
    public function selectjieyong(){
        if($this->_hasJson){
            $shuju=new SqlsrvModel();
            $string=$_POST['ORDER2']=='WEEKS'?'room/selectjieyong2.SQL':'room/selectjieyong.SQL';
            $sql=$shuju->getSqlMap($string);
            $sql=str_replace('TIHUAN',$string,$sql);
            $count=$shuju->getSqlMap('room/countjieyong.SQL');
            IF($_POST['CASE']>1){
                $APP1=0;
                $APP2=1;
            }ELSE{
                $APP1=$_POST['CASE'];
                $APP2=$_POST['CASE'];
            }
            $bind=array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':SCHOOL'=>doWithBindStr($_POST['SCHOOL']),':ROOMNO'=>doWithBindStr($_POST['ROOMNO']),':APPROVED'=>$APP1,':NOTAPPROVED'=>$APP2,':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize);
            $bind2=array(':ORDER2'=>$string,':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':SCHOOL'=>doWithBindStr($_POST['SCHOOL']),':ROOMNO'=>doWithBindStr($_POST['ROOMNO']),':APPROVED'=>$APP1,':NOTAPPROVED'=>$APP2);

            $ct=$shuju->sqlQuery($count,$bind2);
            if($arr['total']=$ct[0]['']){
                $info=$shuju->sqlQuery($sql,$bind);

                $info2=$this->jinzhi($info);
                $arr['rows']=$info2;
            }else{
                $arr['rows']=array();
            }
            $this->ajaxReturn($arr,'JSON');
            exit;
        }
        $this->xiala('areas','area');  //课时段信息
        $this->assign('school',getSchoolList());  ////学校数据
        $this->xiala('roomptions','roomoption');  //教室类型选择
        $this->xiala('timesectors','timesectors');
        $this->assign("yearTerm",$this->mdl->sqlFind("select * from YEAR_TERM where [TYPE]='O'"));
        $this->display();
    }
    //10进制转2进制的方法
    static public function jinzhi($arr){
        foreach($arr as $key=>$val){
            $arr[$key]['WEEKS']=str_pad(strrev(decbin($val['WEEKS'])),19,0);
        }
        return $arr;
    }

    /*
     * 删除教师借用信息的方法
     */
    public function deletejieyong(){
        $shuju=new SqlsrvModel();
        $str='';
        foreach($_POST['in'] as $key=>$val){
            $str.='\''.$val.'\',';
        }
        $str=rtrim($str,',');
        $sql="delete from roomreserve where recno in ({$str})";
        $one=$shuju->execute($sql);
        if($one){
            echo 'yes';
        }else{
            echo 'no';
        }
    }

    /*
     * 添加教室借用信息的方法
     */
    public function addjieyong($update=false){
        /*     echo '<pre>';
             print_r($_POST);
             echo '=====================================';*/
        $shuju=new SqlsrvModel();
        $shuju2=M('roomreserve');
        $er='';     //存放二进制
        $panduan=false;
        foreach($_POST['zhouci'] as $key => $val){
            if($val){
                $panduan=true;
            }
            $er.=$val;
        }
        if(!$panduan){
            exit('您还没有选择周次');
        }

        $wk=array(1=>'MON','TUE','WES','THU','FRI','SAT','SUN');
        //todo:第一步:判断TIMELIST有没有冲突
        //MON
        $tm=$this->mdl->sqlFind("select {$wk[$_POST['DAY']]} from TIMELIST WHERE WHO=:WHO AND YEAR=:YEAR AND TERM=:TERM",
            array(':WHO'=>$_POST['ROOMNO'],':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM']));

        //todo:去TIMESCTIONS表查询TIMEBITS2
        $timebit=$this->mdl->sqlQuery("select TIMEBITS2 from TIMESECTIONS WHERE NAME like :NAME",array(':NAME'=>doWithBindStr($_POST['TIME'])));


        /*   foreach($timebit as $val){
                                              // MON
              if($val['TIMEBITS2']&$tm[$wk[$_POST['DAY']]]){
                      exit('课程有冲突');
              }
           }*/

        $timesections=$this->mdl->sqlQuery('select NAME,TIMEBITS2 from TIMESECTIONS');
        $tarr=array();
        foreach($timesections as $val){
            $tarr[$val['NAME']]=$val['TIMEBITS2'];
        }
        $tarr['%']=16777215;


        $ROOMWEEKS=$this->mdl->sqlQuery("select WEEKS,[TIME] from ROOMRESERVE WHERE YEAR=:YEAR AND TERM=:TERM AND ROOMNO=:ROOMNO AND DAY=:DAY AND TIME like :TIME",
            array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':ROOMNO'=>$_POST['ROOMNO'],':DAY'=>$_POST['DAY'],':TIME'=>'%%'));

        $SCHEDULEWEEKS=$this->mdl->sqlQuery("select S.WEEKS&OEWOPTIONS.TIMEBIT2 as WEEKS,S.[TIME] from SCHEDULE S
inner join OEWOPTIONS on S.OEW=OEWOPTIONS.CODE
 WHERE S.YEAR=:YEAR AND S.TERM=:TERM AND ROOMNO=:ROOMNO AND DAY=:DAY AND TIME like :TIME",
            array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':ROOMNO'=>$_POST['ROOMNO'],':DAY'=>$_POST['DAY'],':TIME'=>'%%'));

        $erjinzhi=strrev($er);
        //var_dump($erjinzhi);
        foreach($ROOMWEEKS  as $val){
            if($tarr[$val['TIME']]&$tarr[$_POST['TIME']]){
                if($val['WEEKS']&bindec($erjinzhi)){
                    exit('和借用周次有冲突,请检查该申请是否已经提交过!');
                }
            }
        }

        /*        echo '<pre>';               */
        /*        print_r($SCHEDULEWEEKS);    */


        foreach($SCHEDULEWEEKS as $val){
            if($tarr[$val['TIME']]&$tarr[$_POST['TIME']]){
                /*   echo '1' ;                                     */
                /*   var_dump(bindec($erjinzhi));                   */
                /*   var_dump($val['WEEKS']&bindec($erjinzhi));     */
                if($val['WEEKS']&bindec($erjinzhi)){

                    exit('和排课周次有冲突');

                }
            }
        };

        //var_dump($ROOMWEEKS);


        /*    echo '=========================';
            //todo:全天部分
            $ROOMWEEKS=$this->mdl->sqlQuery("select WEEKS from ROOMRESERVE WHERE YEAR=:YEAR AND TERM=:TERM AND ROOMNO=:ROOMNO AND DAY=:DAY AND TIME=:TIME",
                array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':ROOMNO'=>$_POST['ROOMNO'],':DAY'=>$_POST['DAY'],':TIME'=>'%'));

            $SCHEDULEWEEKS=$this->mdl->sqlQuery("select WEEKS from SCHEDULE WHERE YEAR=:YEAR AND TERM=:TERM AND ROOMNO=:ROOMNO AND DAY=:DAY AND TIME=:TIME",
                array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':ROOMNO'=>$_POST['ROOMNO'],':DAY'=>$_POST['DAY'],':TIME'=>'%'));

            var_dump($ROOMWEEKS);

            echo '============================';
            var_dump($SCHEDULEWEEKS);
            foreach($ROOMWEEKS  as $val){
                if($val['WEEKS']&bindec($erjinzhi)){
                    exit('和借用周次有冲突,请检查该申请是否已经提交过!');
                }
            }

            foreach($SCHEDULEWEEKS as $val){
                if($val['WEEKS']&bindec($erjinzhi)){
                    exit('和排课周次有冲突');
                }
            };*/
        /* $sql2="select WEEKS from ROOMRESERVE where ROOMNO='{$_POST['ROOMNO']}' AND YEAR={$_POST['YEAR']} AND APPROVED=1 AND DAY={$_POST['DAY']} AND (TIME='{$_POST['TIME']}' OR TIME='%')";
         $two=$shuju->sqlQuery($sql2);
         if($two){                                               //如果有和他条件一样的  则判断他们的周次
             $bool=$this->roomchonghe($two,$er);
              if(!$bool){
                  exit('周次的课程已经被占用');
              }
         }*/
        //查询出申请用户的学院信息


        $sql="select TEACHERS.SCHOOL from TEACHERS,USERS WHERE USERS.USERNAME='{$_SESSION['S_USER_NAME']}' AND USERS.TEACHERNO=TEACHERS.TEACHERNO ";
        $one=$shuju->sqlFind($sql);
        $data['SCHOOL']=$one['SCHOOL'];     //学院ID
        $data['YEAR']=$_POST['YEAR'];       //学年
        $data['TERM']=$_POST['TERM'];       //学期
        $data['WEEKS']=bindec($erjinzhi);           //周次
        $data['PURPOSE']=$_POST['PURPOSE']; //目的
        $data['ROOMNO']=$_POST['ROOMNO'];   //教室号
        $data['DAY']=$_POST['DAY'];         //星期几
        $data['TIME']=$_POST['TIME'];         //第几节课
        $data['OEW']=$_POST['OEW'];             //单双周
        $data['USERNAME']=$_SESSION['S_USER_NAME'];     //申请用户名
        $data['APPROVED']=0;                                //批准状态
        if(!$update){
            $int=$shuju2->add($data);
            if($int){
                echo '申请成功，等待审核';
            }else{
                echo '该教室是保留教室';
            }
        }else{
            //  $shuju2=M('roomreserve');

            $int=$this->mdl->sqlExecute("update roomreserve set school=:school,year=:year,term=:term,weeks=:weeks,purpose=:purpose,roomno=:room,
            day=:day,time=:time,oew=:oew,username=:username,approved=0 where recno=:recno",
                array(':school'=>$one['SCHOOL'],':year'=>$_POST['YEAR'],':term'=>$_POST['TERM'],':weeks'=>bindec($erjinzhi),':purpose'=>$_POST['PURPOSE'],':room'=>$_POST['ROOMNO'],
                    ':day'=>$_POST['DAY'],':time'=>$_POST['TIME'],':oew'=>$_POST['OEW'],':username'=>$_SESSION['S_USER_NAME'],':recno'=>$_POST['RECNO']));
            $data2['RECNO']=$_POST['RECNO'];
            /// $int=$shuju2->where($data2)->save($data);

            if($int)
                echo '修改成功';
            else
                echo '修改失败';
        }

    }
    //点击修改按钮的时候 所使用的方法
    public function updatejieyong(){
        $this->addjieyong(true);        //使用修改方法
    }

    /*
     * 判断教室周次是否有被占用的方法
     */
    public function roomchonghe($arr,$er){          //这边的都是反过来的（相对于数据库的数据是反过来的）数据库里的7（000111）在这是111000
        foreach($arr as $key=>$val){                 //                    （相对于页面选的是对的）
            $weeks=str_pad(strrev(decbin($val['WEEKS'])),18,0);
            for($i=0;$i<18;$i++){
                if($weeks[$i]==1&&$er[$i]==1){
                    return false;
                }
            }
        }
        return true;
    }

    /*
     * 在添加的时候对roomno进行验证的方法 来让用户合理的提交(判断借用的教室号存在不存在的说)
     */
    public function roomnoyz(){
        if($_POST['ROOMNO']==""){
            exit('sev');
        }
        $shuju=M('classrooms');
        $where['ROOMNO']=$_POST['ROOMNO'];
        $where['RESERVED']=0;                   //必须是不保留的
        $one=$shuju->where($where)->find();
        if($one){
            echo 'true';
        }else{
            echo 'false';
        }

    }



    /*
     * 修改借用教室信息时候获取 用来获取单条信息的方法
     */
    public function editjieyong(){
        $shuju=new SqlsrvModel();            //教室借用登记表
        $one=$shuju->sqlFind('select * from ROOMRESERVE where RECNO=:RECNO ',array(':RECNO'=>$_POST['RECNO']));
        if(!$one){
            echo 'false';
        }else{
            $str=strrev(decbin($one['WEEKS']));
            $length=strlen($str);
            $two=array();
            for($i=0;$i<$length;$i++){
                if($str[$i]){
                    array_push($two,'C'.($i+1));
                }
            }
            $one['chek']=$two;
            echo json_encode($one);
        }
    }


    /*
     * 判断是否有权限审核的方法
     */
    private function shenhequanxian(){
        $shuju=new SqlsrvModel();
       // $sql="select USERNAME from USERS where USERNAME='{$_SESSION['S_USER_NAME']}' AND ROLES LIKE 'D'";
        $sql = "select USERS.USERNAME from USERS INNER JOIN TEACHERS ON TEACHERS.TEACHERNO = USERS.TEACHERNO where USERS.USERNAME='{$_SESSION['S_USER_NAME']}'  ";
        $one=$shuju->sqlFind($sql);
        if(!$one)
            return false;
		return isDeanByUsername(getUsername());
    }

    /*
     * 教室审核情况修改
     */
    public function shenhejieyong(){

        $shuju=M('roomreserve');
        $bool=$this->shenhequanxian();
        if(!$bool)
            exit('只有教务处的人才能审核');

        $this->mdl->startTrans();
        foreach($_POST['bind'] as $val){
            if($val['RECNO']==""){
                $this->mdl->rollback();
                exit('非法操作！');
            }
            /*    $data['RECNO']=$val['RECNO'];
                $save['APPROVED']=$val['APPROVED'];*/
            $one=$this->mdl->sqlExecute('update roomreserve set APPROVED=:approved where recno=:recno',
                array(':approved'=>$val['APPROVED'],':recno'=>$val['RECNO']));
            // $one=$shuju->where($data)->save($save);
            if(!$one){
                $this->mdl->rollback();
                exit('操作失败');
            }
        }
        $this->mdl->commit();
        exit('操作成功');

    }

    /*
     * 借用申请单的页面
     */
    public function shenqingdan(){
        $shuju=new SqlsrvModel();
        if($_GET['RECNO']=="")
            exit('警告：非法操作');
        $sql=$shuju->getSqlMap('room/shenqingdan.SQL');
        $bind=array(':RECNO'=>$_GET['RECNO']);
        $one=$shuju->sqlFind($sql,$bind);
        $one['WEEKS']=str_pad(strrev(decbin($one['WEEKS'])),18,0);

        $this->assign('value',$one);

        $this->display($_GET['html']);
    }



    /*
     * 教室使用情况的方法
     */

    public function shiyong(){
        $this->assign('schools',getSchoolList());                   //todo:学校
        $this->xiala('areas','areas');                     //todo:  校区
        $this->xiala('timesectors','timesectors');        //todo:空闲时段
        $this->xiala('roomoptions','roomoptions');        //todo:设施表
        $this->assign("yearTerm",$this->mdl->sqlFind("select * from YEAR_TERM where [TYPE]='O'"));
        $this->display();
    }


    public function selectshiyong(){


        $shuju=new SqlsrvModel();
        $one=$shuju->sqlquery("select TIMEBITS2 from TIMESECTIONS where NAME like :name",array(':name'=>doWithBindStr($_POST['arr']['TIME'])));
        $two=$shuju->sqlFind("select TIMEBIT from OEWOPTIONS where CODE=:code",array(':code'=>$_POST['arr']['OEW']));
        $three=0;
        foreach($one as $val){
            $three=$three|($val['TIMEBITS2']&$two['TIMEBIT']);
        }
        $mo=array(':order'=>$this->sy['ORDER'],':MON'=>'0',':TUE'=>'0',':WES'=>'0',':THU'=>'0',':FRI'=>'0',':SAT'=>'0',':SUN'=>'0',
            ':ROOMNO'=>doWithBindStr($this->sy['ROOMNO']),':JSN'=>doWithBindStr($this->sy['JSN']),':SCHOOL'=>doWithBindStr($this->sy['SCHOOL']),
            ':EQUIPMENT'=>doWithBindStr($this->sy['EQUIPMENT']),':AREA'=>doWithBindStr($this->sy['AREA']),':SEATSDOWN'=>$this->sy['SEATSDOWN'],
            ':SEATSUP'=>$this->sy['SEATSUP'],':YEAR'=>$this->sy['YEAR'],':TERM'=>$this->sy['TERM'],':TYPE'=>'R');
        if($_POST['arr']['DAY']!=-1){
            $mo[$_POST['arr']['DAY']]=$three;
        }
        $arr2=$shuju->sqlFind($shuju->getSqlMap('Room/countList.SQL'),$mo);


        if($arr2['']==0){
            exit;
        }
        $ar['total']=$arr2[''];
        $ar['page']=ceil($arr2['']/$this->_pageSize);
        if($_POST['page']>=$ar['page']){
            $ar['nowpage']=$ar['page'];
        }else if($_POST['page']<1){
            $ar['nowpage']=1;
        }else{
            $ar['nowpage']=$_POST['page'];
        }



        $mo2=array(':order'=>$this->sy['ORDER'],':MON'=>'0',':TUE'=>'0',':WES'=>'0',':THU'=>'0',':FRI'=>'0',':SAT'=>'0',':SUN'=>'0',
            ':ROOMNO'=>doWithBindStr($this->sy['ROOMNO']),':JSN'=>doWithBindStr($this->sy['JSN']),':SCHOOL'=>doWithBindStr($this->sy['SCHOOL']),
            ':EQUIPMENT'=>doWithBindStr($this->sy['EQUIPMENT']),':AREA'=>doWithBindStr($this->sy['AREA']),':SEATSDOWN'=>$this->sy['SEATSDOWN'],
            ':SEATSUP'=>$this->sy['SEATSUP'],':YEAR'=>$this->sy['YEAR'],':TERM'=>$this->sy['TERM'],':TYPE'=>'R',':start'=>($ar['nowpage']-1)*$this->_pageSize,':end'=>$ar['nowpage']*$this->_pageSize);

        if($_POST['arr']['DAY']!=-1){
            $mo2[$_POST['arr']['DAY']]=$three;
        }
        $arr=$shuju->sqlQuery($shuju->getSqlMap('Room/selectList.SQL'),$mo2);

        $str='';

        foreach($arr as $key=>$val){
            $str.='<tr name="oname" roomno="'.$val['ROOMNO'].'" >';
            $str.='<td><a href="javascript:void(0)" onclick="tanchu(this)">'.$val['ROOMNO'].'</a></td>';        //教室号
            $str.='<td>'.$val['JSN'].'</td>';           //简称
            $str.=$this->zhou($val['MON']);        //星期1
            $str.=$this->zhou($val['TUE']);        //星期2
            $str.=$this->zhou($val['WES']);        //星期3
            $str.=$this->zhou($val['THU']);        //星期4
            $str.=$this->zhou($val['FRI']);        //星期5
            $str.=$this->zhou($val['SAT']);        //星期6
            $str.=$this->zhou($val['SUN']);        //星期7
            $str.='</tr>';
        }
        $ar['str']=$str;


        echo json_encode($ar);
    }

    public function zhou($num){
        $str2='';
        $str=str_pad(strrev(decbin($num)),24,0);
        $arr=explode('.',trim(chunk_split($str,2,'.'),'.'));
        $num=count($arr);
        if($num>12){
            $arr=array('11','11','11','11','11','11','11','11','11','11','11','11');
        }
        foreach($arr as $val){
            switch($val){
                case '00':
                    $str2.='<td>&nbsp</td>';
                    break;
                case '01':
                    $str2.='<td>E</td>';
                    break;
                case '10':
                    $str2.='<td>D</td>';
                    break;
                case '11':
                    $str2.='<td>B</td>';
                    break;
            }
        }
        return $str2;
    }

    //todo:弹出教室学年课程的方法
    public function kecheng(){

        //todo:学年和学期
        $data= $this->mdl->sqlFind($this->mdl->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
        // todo:查询出某些学院借用周次
        $as=$this->arrSchool($arrSchool=$this->mdl->sqlQuery($this->mdl->getSqlMap('Room/getRoomCourseSchool.SQL'),array(':year'=>$_POST['year'],':term'=>$_POST['term'],':roomno'=>$_POST['roomno'])));
        //todo:查询出该教室该学年的课程安排
        $arr=$this->mdl->sqlQuery($this->mdl->getSqlMap('Room/getRoomCourse.SQL'),array(':year'=>$_POST['year'],':term'=>$_POST['term'],':roomno'=>$_POST['roomno']));

        $arr=array_merge($arr,$as);



        $arr2=$this->mdl->sqlQuery('select NAME,VALUE,UNIT,TIMEBITS from TIMESECTORS');

        $Tablearr=array();           //todo:前台页面用到的数组
        $ar2=$this->getTime($arr2);                                      //todo:节次数组        (以NAME为下标)

        $both = array("B"=>"","E"=>"（双周）","O"=>"（单周）");      //todo:单双周数组
        $countOneDay=array_reduce($arr2, "countOneDay2");                //todo:统计出一天12节课(单节次的自然下标)

        foreach($arr as $key=>$val){                              //todo:遍历查询出来的该学期的课程
            $str="";
            if($val['WEEKS']!=262143){
                $str='周次'.str_pad(strrev(decbin($val['WEEKS'])),18,0);
            }
            $valTIME=$val['TIME'];                                    //todo:TIME+DAY+OEW(F4E)
            for($i=1;$i<count($countOneDay);$i+=2){

                for($j=0;$j<2;$j++){


                    if(($ar2[$countOneDay[$i-1+$j]]['TIMEBITS'] & $ar2[$valTIME[0]]['TIMEBITS'])>0){

                        $Tablearr[($i-1)/2+1][$valTIME[1]] .= ($ar2[$valTIME[0]]['UNIT']=="1" ? '('.trim($ar2[$valTIME[0]]['VALUE']).')' : '').$both[$valTIME[2]].$val["COURSE"]."{$str}<br/>";
                        break;
                    }
                }

            }
        }




        $str=$this->web($Tablearr,$arr[0]['JSN'],date('Y-m-d H:i:s'),array($_POST['year'],$_POST['term']));
        echo $str;
        return $str;
    }


    public function getTime($arr){
        $ar2=array();
        foreach($arr as $val){
            $ar2[$val['NAME']]=$val;
        }
        return $ar2;
    }


    public function web($list,$title,$time,$week=array('year'=>2013,'term'=>1),$roomname=''){
        $str=<<<EOF
<p align="center" style="font-size:22px">{$roomname}{$title}在{$week[year]}学年第{$week['term']}学期的周课表</p>
 <p align="center">打印时间：$time</p>
        <table id="WeekSchedule" name="WeekSchedule"  style="color: #000000; font-size: 10px; border-collapse: collapse" border="1" cellpadding="0" cellspacing="0" width="100%" height="196" bordercolorlight="#336699" bordercolordark="#003399">
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="13"><font size="2">节次/星期</font></td>
            <td width="16%" align="center" bgcolor="#00FFFF" bordercolor="#3399CC" bordercolorlight="#008080" bordercolordark="#FFFFFF" height="13"><font size="2">星期一</font></td>
            <td width="16%" align="center" bgcolor="#00FFFF" bordercolor="#3399CC" bordercolorlight="#008080" bordercolordark="#FFFFFF" height="13"><font size="2">星期二</font></td>
            <td width="16%" align="center" bgcolor="#00FFFF" bordercolor="#3399CC" bordercolorlight="#008080" bordercolordark="#FFFFFF" height="13"><font size="2">星期三</font></td>
            <td width="16%" align="center" bgcolor="#00FFFF" bordercolor="#3399CC" bordercolorlight="#008080" bordercolordark="#FFFFFF" height="13"><font size="2">星期四</font></td>
            <td width="16%" align="center" bgcolor="#00FFFF" bordercolor="#3399CC" bordercolorlight="#008080" bordercolordark="#FFFFFF" height="13"><font size="2">星期五</font></td>
            <td width="5%" align="center" bgcolor="#00FFFF" bordercolor="#3399CC" bordercolorlight="#008080" bordercolordark="#FFFFFF" height="13"><font size="2">星期六</font></td>
            <td width="5%" align="center" bgcolor="#00FFFF" bordercolor="#3399CC" bordercolorlight="#008080" bordercolordark="#FFFFFF" height="13"><font size="2">星期天</font></td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第一节</font></td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[1][1]}</td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[1][2]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[1][3]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[1][4]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[1][5]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[1][6]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[1][7]}</td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第二节</font></td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第三节</font></td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[2][1]}</td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[2][2]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[2][3]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[2][4]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[2][5]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[2][6]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[2][7]}</td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第四节</font></td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第五节</font></td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[3][1]}</td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[3][2]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[3][3]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[3][4]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[3][5]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[3][6]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[3][7]}</td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第六节</font></td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第七节</font></td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[4][1]}</td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[4][2]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[4][3]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[4][4]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[4][5]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[4][6]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[4][7]}</td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="17"><font size="2">第八节</font></td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第九节</font></td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[5][1]}</td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[5][2]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[5][3]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[5][4]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[5][5]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[5][6]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[5][7]}</td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第十节</font></td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第11节</font></td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[6][1]}</td>
            <td width="16%" align="center" rowspan="2" height="34">{$list[6][2]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[6][3]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[6][4]}</td>
            <td width="16%" align="center" height="32" rowspan="2">{$list[6][5]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[6][6]}</td>
            <td width="5%" align="center" height="32" rowspan="2">{$list[6][7]}</td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">第12节</font></td>
        </tr>
        <tr>
            <td width="10%" align="center" bgcolor="#3399CC" bordercolorlight="#008080" bordercolordark="#00FFFF" bordercolor="#00FFFF" height="16"><font size="2">时间未定</font></td>
            <td colspan="7">{$list[0]}</td></tr>
    </table>
    <br>
    <br>

EOF;
        return $str;
    }

    //todo:把学院的格式和正常的一样
    public function arrSchool($arr){
        $arr2=array();
        foreach($arr as $key=>$val){
            if($val['TIMESECTOR']=='%'){
                array_push($arr2,$this->push('E',$val));
                array_push($arr2,$this->push('F',$val));
                array_push($arr2,$this->push('G',$val));
                array_push($arr2,$this->push('H',$val));
                array_push($arr2,$this->push('I',$val));
                array_push($arr2,$this->push('J',$val));
            }
            array_push($arr2,$this->push($val['TIMESECTOR'],$val));
        }
        return $arr2;
    }

    //todo:把学院信息压入数组
    public function push($timesector,$val){
        $newarr['TIME']=$timesector.$val['TIME'];
        $newarr['COURSE']=$val['LEASE'];
        $newarr['WEEKS']=$val['WEEKS'];
        return $newarr;
    }

    //todo:教室周课表方法 代码重合了 下次再碰到相同的再说吧
    public function RoomjieyongCourse($sub=true){
        $array=array();                 //todo:存放前台的list数组
        $OEW = array("B"=>"","E"=>"（双周）","O"=>"（单周）");      //todo:单双周数组
        $as=$this->arrSchool($arrSchool=$this->mdl->sqlQuery($this->mdl->getSqlMap('Room/select_RoomCourse_school.SQL'),array(':year'=>$_POST['arr']['year'],':term'=>$_POST['arr']['term'],':roomno'=>$_POST['arr']['roomno'])));
        // todo:查询出该教室该学年的课程安排
        $arr=$this->mdl->sqlQuery($this->mdl->getSqlMap('Room/select_RoomCourse.SQL'),array(':year'=>$_POST['arr']['year'],':term'=>$_POST['arr']['term'],':roomno'=>$_POST['arr']['roomno']));

        //:todo:班名
        foreach($arr as $key=>$val){
            $classname=$this->mdl->sqlFind("SELECT RTRIM(CLASSES.CLASSNAME) AS CLASSNAME FROM CLASSES JOIN COURSEPLAN ON CLASSES.CLASSNO=COURSEPLAN.CLASSNO
WHERE COURSEPLAN.YEAR=:year AND COURSEPLAN.TERM=:term AND COURSEPLAN.COURSENO=:courseno AND COURSEPLAN.[GROUP]=:gp",
                array(':year'=>$_POST['arr']['year'],':term'=>$_POST['arr']['term'],':courseno'=>$val['COURSENO'],':gp'=>$val['COURSEGROUP']));
            $arr[$key]['classname']=$classname['CLASSNAME'];

            //var_dump($classname);
        }
        /*      echo '<pre>';
              print_R($as);*/
        /*   echo '<pre>';
           print_r($arr);*/
        $roomname=$this->mdl->sqlFind("select JSN from CLASSROOMS where ROOMNO='{$_POST['arr']['roomno']}'");

        $arr=array_merge($arr,$as);
        $jieci=$this->mdl->sqlQuery("select * from TIMESECTORS");//todo:节次数组
        $jieci=$this->getTime($jieci);
        $countOnejieci=array_reduce($jieci, "countOneDay2");              //todo:统计出一天几个单节课  12节


        foreach($arr as $key=>$val){
            if($val['WEEKS']!=262143){
                $weeks='周次'.$this->colorr($val['WEEKS']);

            }else{
                $weeks='';
            }

            for($i=1;$i<count($countOnejieci);$i+=2){
                for($j=0;$j<2;$j++){
                    if(($jieci[$val['TIME'][0]]['TIMEBITS'] & $jieci[$countOnejieci[$i-1+$j]]['TIMEBITS'])>0){


                        if($jieci[$val['TIME'][0]]['UNIT']=="3"){
                            //todo:取最后一节课是第几节
                            $len=strlen(strrev(decbin($jieci[$val['TIME'][0]]['TIMEBITS'])));
                            //todo:表示到单节了
                            if(!($i+1<$len)){
                                $array[($i-1)/2+1][$val['TIME'][1]] .='(第'.$len.'节)'.$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks}{$val['classname']}" ;
                            }else{
                                $array[($i-1)/2+1][$val['TIME'][1]] .=$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks}{$val['classname']}<br/>";
                            }
                            break;
                        }
                        //todo:是一节课的就加上(第几节)  否则为空
                        $array[($i-1)/2+1][$val['TIME'][1]] .= ($jieci[$val['TIME'][0]]['UNIT']=="1" ? '('.trim($jieci[$val['TIME'][0]]['VALUE']).')' : '').$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks}{$val['classname']}<br/>";
                        break;
                    }
                }
            }
        }
        $str=$this->web($array,$arr[0]['JSN'],date('Y-m-d H:i:s'),$_POST['arr'],$roomname['JSN']);
        if($sub){
            echo $str;
        }
        return $str;
    }

    public function colorr($str2){
        $aa=str_pad(strrev(decbin($str2)),18,0);

        $str='';
        $str.='<font color="blue">'.substr($aa,0,4).'</font>&nbsp';
        $str.='<font color="#222">'.substr($aa,4,4).'</font>&nbsp';
        $str.='<font color="green">'.substr($aa,8,4).'</font>&nbsp';
        $str.='<font color="red">'.substr($aa,12,4).'</font>&nbsp';
        $str.='<font color="black">'.substr($aa,16,4).'</font>&nbsp';

        return $str;
    }

    public function syqingkuang(){

        $shuju=new SqlsrvModel();

        $mo=array(':order'=>$this->sy['ORDER'],':MON'=>'0',':TUE'=>'0',':WES'=>'0',':THU'=>'0',':FRI'=>'0',':SAT'=>'0',':SUN'=>'0',
            ':ROOMNO'=>doWithBindStr(str_replace('_','',$_GET['ROOMNO'])),':JSN'=>doWithBindStr(str_replace('_','',$_GET['JSN'])),':SCHOOL'=>doWithBindStr(str_replace('_','',$_GET['SCHOOL'])),
            ':EQUIPMENT'=>doWithBindStr(str_replace('_','',$_GET['EQUIPMENT'])),':AREA'=>doWithBindStr(str_replace('_','',$_GET['AREA'])),':SEATSDOWN'=>str_replace('_','',$_GET['SEATSDOWN']),
            ':SEATSUP'=>str_replace('_','',$_GET['SEATSUP']),':YEAR'=>str_replace('_','',$_GET['YEAR']),':TERM'=>str_replace('_','',$_GET['TERM']),':TYPE'=>'R');
        if($_GET['DAY']!=-1){
            $mo[$_GET]=-1;

        }
        $arr2=$shuju->sqlFind($shuju->getSqlMap('Room/countList.SQL'),$mo);

        if($arr2['']==0){
            exit;
        }
        $ar['total']=$arr2[''];
        $ar['page']=ceil($arr2['']/$this->_pageSize);
        if($_POST['page']>=$ar['page']){
            $ar['nowpage']=$ar['page'];
        }else if($_POST['page']<1){
            $ar['nowpage']=1;
        }else{
            $ar['nowpage']=$_POST['page'];
        }

        $mo2=array(':order'=>$this->sy['ORDER'],':MON'=>'0',':TUE'=>'0',':WES'=>'0',':THU'=>'0',':FRI'=>'0',':SAT'=>'0',':SUN'=>'0',
            ':ROOMNO'=>doWithBindStr(str_replace('_','',$_GET['ROOMNO'])),':JSN'=>doWithBindStr(str_replace('_','',$_GET['JSN'])),':SCHOOL'=>doWithBindStr(str_replace('_','',$_GET['SCHOOL'])),
            ':EQUIPMENT'=>doWithBindStr(str_replace('_','',$_GET['EQUIPMENT'])),':AREA'=>doWithBindStr(str_replace('_','',$_GET['AREA'])),':SEATSDOWN'=>str_replace('_','',$_GET['SEATSDOWN']),
            ':SEATSUP'=>str_replace('_','',$_GET['SEATSUP']),':YEAR'=>str_replace('_','',$_GET['YEAR']),':TERM'=>str_replace('_','',$_GET['TERM']),':TYPE'=>'R',':start'=>($ar['nowpage']-1)*$this->_pageSize,':end'=>$ar['nowpage']*$this->_pageSize);
        if($_GET['DAY']!=-1){

            $mo2[$_GET['DAY']]=-1;
        }
        $arr=$shuju->sqlQuery($shuju->getSqlMap('Room/selectList.SQL'),$mo2);

        $str='';

        foreach($arr as $key=>$val){
            $str.='<tr name="oname" roomno="'.$val['ROOMNO'].'" >';
            $str.='<td><a href="javascript:void(0)" onclick="tanchu(this)">'.$val['ROOMNO'].'</a></td>';        //教室号
            $str.='<td>'.$val['JSN'].'</td>';           //简称
            $str.=$this->zhou($val['MON']);        //星期1
            $str.=$this->zhou($val['TUE']);        //星期2
            $str.=$this->zhou($val['WES']);        //星期3
            $str.=$this->zhou($val['THU']);        //星期4
            $str.=$this->zhou($val['FRI']);        //星期5
            $str.=$this->zhou($val['SAT']);        //星期6
            $str.=$this->zhou($val['SUN']);        //星期7
            $str.='</tr>';
        }
        $ar['str']=$str;


        $this->assign('ccc',json_encode($ar));

        $this->display();
    }

    public function roomWeek(){
        $_POST['arr']['year']=$_GET['year'];
        $_POST['arr']['term']=$_GET['term'];
        $_POST['arr']['roomno']=$_GET['roomno'];

        $str=$this-> RoomjieyongCourse(false);
        $this->assign('str',$str);
        $this->display();
    }



    //todo:刷新教室资源列表的方法
    public function source(){
        $arr=array(':yone'=>$_GET['YEAR'],':tone'=>$_GET['TERM'],':ytwo'=>$_GET['YEAR'],':ttwo'=>$_GET['TERM'],':ythree'=>$_GET['YEAR'],':tthree'=>$_GET['TERM']);
        $this->mdl->startTrans();
        $MON=$this->mdl->sqlExecute($this->mdl->getSqlMap($this->base.'source_MON.SQL'),$arr);

        $TUE=$this->mdl->sqlExecute($this->mdl->getSqlMap($this->base.'source_TUE.SQL'),$arr);

        $WES=$this->mdl->sqlExecute($this->mdl->getSqlMap($this->base.'source_WES.SQL'),$arr);

        $THU=$this->mdl->sqlExecute($this->mdl->getSqlMap($this->base.'source_THU.SQL'),$arr);

        $FRI=$this->mdl->sqlExecute($this->mdl->getSqlMap($this->base.'source_FRI.SQL'),$arr);

        $SAT=$this->mdl->sqlExecute($this->mdl->getSqlMap($this->base.'source_SAT.SQL'),$arr);

        $SUN=$this->mdl->sqlExecute($this->mdl->getSqlMap($this->base.'source_SUN.SQL'),$arr);

        $this->mdl->commit();
        var_dump($MON);
        var_dump($TUE);
        var_dump($WES);
        var_dump($THU);
        var_dump($FRI);
        var_dump($SAT);
        var_dump($SUN);
        exit('刷新成功');

        /* update TIMELIST SET :done=C.TT from TIMELIST INNER JOIN (

             select B.TT,T.* from TIMELIST T INNER JOIN (
             select ROOMNO,dbo.group_or(OEWOPTIONS.TIMEBIT&TIMESECTIONS.TIMEBITS2) AS TT
     from SCHEDULE inner join OEWOPTIONS on SCHEDULE.OEW=OEWOPTIONS.CODE
     INNER JOIN TIMESECTIONS ON  SCHEDULE.TIME=TIMESECTIONS.NAME
     INNER JOIN TIMELIST t1 ON t1.WHO=SCHEDULE.ROOMNO AND t1.YEAR=SCHEDULE.YEAR AND t1.TERM=SCHEDULE.TERM
     where SCHEDULE.YEAR=:yone AND SCHEDULE.TERM=:tone
         and day=:dtwo
     group by ROOMNO
 )AS B ON T.WHO=B.ROOMNO AND T.YEAR=:ytwo AND T.TERM=:ttwo

 ) AS C ON  C.WHO=TIMELIST.WHO AND C.YEAR=TIMELIST.YEAR AND C.TERM=TIMELIST.TERM
 WHERE TIMELIST.YEAR=:ythree AND TIMELIST.TERM=:tthree*/

    }




    public function returntime($arr){
        $time=array(1=>0,0,0,0,0,0,0);
        foreach($arr as $val){
            if($val['TIME']=='Q'||$val['TIME']=='R')continue;
            $vtime=$this->fz(decbin($this->unit[$val['TIME']]),cwebsSchedule::$oewVal[$val['OEW']]);
            $a= $time[$val['DAY']] | $vtime;
            $time[$val['DAY']]=$a;
        }
        return $time;
    }

    public function fz($bin,$oew){
        $str='';
        $bin=str_split($bin);
        foreach($bin as $v){
            $v.=$v;
            $vv=decbin($v)&$oew;
            if($vv=='1'||$vv=='0')
                $str.='0'.$vv;
            else
                $str.=decbin($vv);
        }
        return bindec($str);
    }



}

//todo:一天有几节课
function countOneDay2($v1, $v2){
    if(!$v1) $v1 = array();
    if($v2['UNIT']=="1") $v1[]=$v2["NAME"];
    return $v1;
}



?>