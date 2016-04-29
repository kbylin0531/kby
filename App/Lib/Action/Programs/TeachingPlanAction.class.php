<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 14-4-21
 * Time: 下午1:35
 */
class TeachingPlanAction extends RightAction {
    private $model;
    private $message = array('type'=>'info','message'=>'','dbError'=>'');
    private $theacher;

    public function __construct(){
        parent::__construct();
        $this->model = new ProgramModel();

        $this->theacher = $this->model->getTeacherInfo();
        //教师信息
        $this->assign("theacher", $this->theacher);
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
    }

    /**
     * 检索教学计划
     */
    public function search(){
        if($this->_hasJson){
            $json = $this->model->getProgramTableList($this->_pageDataIndex, $this->_pageSize);
            if(is_string($json)){
                exit($json);
            }
            $this->ajaxReturn($json,"JSON");
            exit;
        }
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->display("search");
    }




    /**
     * 编辑教学计划条目
     */
    public function update(){
        $programname = $_POST['PROGNAME'];
        $rst = $this->model->getProgram(array(
            'PROGNAME'  => array($programname,true,'like'),
        ));
        if(is_string($rst)){
            $this->exitWithReport('检测教学计划名称是否冲突失败！'.$rst);
        }elseif($rst){
            $this->exitWithReport('该教学计划已经存在!');
        }

        $progRecs = $this->model->getPrograms($_REQUEST['PROGRAMNO']);
        if(is_string($progRecs)){
            $this->exitWithReport($progRecs);
        }elseif(!$progRecs){
            $this->exitWithReport('未查询到该教学计划的信息！');
        }
        $realSchool = $progRecs[0]['SCHOOL'];
        if( ($realSchool != $this->theacher["SCHOOL"] && !isDeanByUsername(getUsername()))){
            $this->exitWithReport('对不起，指定的教学计划不存在或者该教学计划不是本学部的教学计划不能修改！');
        }

        $bind = $this->model->getBind("PROGNAME,DATE,REMS,URL,VALID,TYPE,MAJOR,PROGRAMNO",$_REQUEST);
        $data = $this->model->sqlExecute("Update Programs Set ProgName=:PROGNAME,Date=:DATE,Rem=:REMS,Url=:URL,Valid=:VALID,Type=:TYPE,major=:MAJOR Where ProgramNo=:PROGRAMNO",$bind);
        if($data===false){
            $this->message["type"] = "error";
            $this->message["message"] .= "<font color='red'>[".$_REQUEST["PROGRAMNO"]."]该教学计划更新时发生错误！</font>\n";
            $this->message["dbError"] .= $this->model->getDbError();
        }else $this->message["message"] .= "[".$_REQUEST["PROGRAMNO"]."]该教学计划已经被更新！\n";
        $this->ajaxReturn($this->message,"JSON");
        exit;
    }





    /**
     * excel导出教学计划表
     */
    public function excelexpteachingplan(){
    
    	TaskMonitorModel::init(session("S_USER_NAME"), 'excel 导出教学计划表 ');
    
    	$bind = array(":PROGRAMNO"=>$_POST["PROGRAMNO"],":PROGRAMNAME"=>$_POST["PROGRAMNAME"],":SCHOOL"=>$_POST["SCHOOL"]);
    	$x_bind = array(":PROGRAMNO"=>$_POST["PROGRAMNO"],":PROGRAMNAME"=>$_POST["PROGRAMNAME"],":SCHOOL"=>$_POST["SCHOOL"],":x_PROGRAMNO_x"=>$_POST["PROGRAMNO"],":x_PROGRAMNAME_x"=>$_POST["PROGRAMNAME"],":x_SCHOOL_x"=>$_POST["SCHOOL"]);
    	 
    	$sql_count = " SELECT COUNT(*) as icount "
    			   . " from PROGRAMS i "
    			   . " inner join R16 m1 on m1.PROGRAMNO = i.PROGRAMNO "
    			   . " inner join R12 m2 on m2.PROGRAMNO = i.PROGRAMNO "
    			   . " inner join CLASSES m11 on m11.CLASSNO = m1.CLASSNO "
    			   . " inner join COURSES m21 on m21.COURSENO = m2.COURSENO "
    			   . " left join COURSETYPEOPTIONS m22 on m22.NAME = m2.CATEGORY " 
    			   . " WHERE i.PROGRAMNO LIKE :PROGRAMNO "
    			   . " AND i.PROGNAME LIKE :PROGRAMNAME "
    			   . " AND i.SCHOOL LIKE :SCHOOL ";
    
    	$data = $this->model->sqlFind($sql_count,$bind);
    	$totalCount = $data['icount'];
    
    	if ($totalCount > 0)
    	{
    		TaskMonitorModel::run(session("S_USER_NAME"),"excel导出教学计划表", $totalCount);
    		    		
    		$inputFileType = 'Excel5';
    		$inputFileName = $_SERVER['DOCUMENT_ROOT'] . "\\res\\templates\\programs_teachingplan_exp.xls";
    
    		vendor("PHPExcel.PHPExcel");
    
    		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    		$objPHPExcel = $objReader->load($inputFileName);
    		$targetws = $objPHPExcel->getSheet(0);
    
    		$sql_select = " select x.*, x1.icount from ( "

						. " select "
						. " m1.CLASSNO, "
						. " m11.CLASSNAME, "
						. " m2.CATEGORY, "
						. " m22.VALUE as CATEGORYNAME, "
						. " dbo.GROUP_CONCAT_MERGE((case when m2.COURSETYPE='E' then '选' else '' end) + '（' + +m2.COURSENO + '）'+RTRIM(m21.COURSENAME)+'（' + isnull(cast(m2.CREDITS as varchar),'0.0') + '）',',') AS COURSE "
						. " from PROGRAMS i "
						. " inner join R16 m1 on m1.PROGRAMNO = i.PROGRAMNO "
						. " inner join R12 m2 on m2.PROGRAMNO = i.PROGRAMNO "
						. " inner join CLASSES m11 on m11.CLASSNO = m1.CLASSNO "
						. " inner join COURSES m21 on m21.COURSENO = m2.COURSENO "
						. " left join COURSETYPEOPTIONS m22 on m22.NAME = m2.CATEGORY "
						. " WHERE i.PROGRAMNO LIKE :PROGRAMNO "
						. " AND i.PROGNAME LIKE :PROGRAMNAME "
						. " AND i.SCHOOL LIKE :SCHOOL "
						. " group by m1.CLASSNO, m11.CLASSNAME, m2.CATEGORY, m22.VALUE "
								
						. " ) x "

						. " left join ( "

						. " select xx1.CLASSNO, xx1.CLASSNAME, count(*) as icount  from ( "								
						. " select "
						. " m1.CLASSNO, "
						. " m11.CLASSNAME, "
						. " m2.CATEGORY, "
						. " m22.VALUE as CATEGORYNAME "
						. " from PROGRAMS i "
						. " inner join R16 m1 on m1.PROGRAMNO = i.PROGRAMNO "
						. " inner join R12 m2 on m2.PROGRAMNO = i.PROGRAMNO "
						. " inner join CLASSES m11 on m11.CLASSNO = m1.CLASSNO "
						. " inner join COURSES m21 on m21.COURSENO = m2.COURSENO "
						. " left join COURSETYPEOPTIONS m22 on m22.NAME = m2.CATEGORY "
						. " WHERE i.PROGRAMNO LIKE :x_PROGRAMNO_x "
						. " AND i.PROGNAME LIKE :x_PROGRAMNAME_x "
						. " AND i.SCHOOL LIKE :x_SCHOOL_x "
						. " group by m1.CLASSNO, m11.CLASSNAME, m2.CATEGORY, m22.VALUE "
						. " ) xx1 group by xx1.CLASSNO, xx1.CLASSNAME "
								
						. " ) x1 on x1.CLASSNO = x.CLASSNO "    
						. " order by  x.CLASSNO, x.CATEGORY ";
    
    		$result = $this->model->sqlQuery($sql_select,$x_bind);
    		if (count($result) > 0)
    		{    			 
    			$this->set_expteachingplan_xls($result, $targetws);    
    		}
    
    		TaskMonitorModel::done(session("S_USER_NAME"));
    
    		ob_end_clean();
    
    		$filename = "教学计划表_" . date ( 'Y-m-d', time () );
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
    
    private function set_expteachingplan_xls($result, $targetws) {
    	$tmpCLASSNO = "@";
    	$tmpCLASSicount = 0;
    	$toprow = 0;
    	$bottomrow = 0;
    	
    	$row = 1;
    	foreach ( $result as $iresult )
    	{
    		if ($iresult)
    		{
    			$row++;    			
    			
    			$CLASSNO = $iresult['CLASSNO'];
    			$CLASSicount = $iresult['icount'];
    			
    			if ($tmpCLASSNO == $CLASSNO)
    			{
    				$tmpCLASSicount++;
    			}
    			else
    			{
    				$tmpCLASSicount = 1;    	
    				$toprow = $row;
    			}
    
    			$targetws->setCellValueExplicit ( 'A' . $row, $iresult['CLASSNAME'], PHPExcel_Cell_DataType::TYPE_STRING ); //班级
    			$targetws->setCellValueExplicit ( 'B' . $row, $iresult['CATEGORYNAME'], PHPExcel_Cell_DataType::TYPE_STRING ); //课程类别
    			$targetws->setCellValueExplicit ( 'C' . $row, $iresult['COURSE'], PHPExcel_Cell_DataType::TYPE_STRING ); //课程
    			
    			if ($CLASSicount == $tmpCLASSicount)
    			{
    				$bottomrow = $row;
    				
    				$targetws->mergeCells('A'.$toprow.':'.'A'.$bottomrow); //合并班级单元格    		
    			}
    			
    			$tmpCLASSNO = $iresult['CLASSNO'];
    
    			TaskMonitorModel::next(session("S_USER_NAME"),$row,true,$iresult['CLASSNO']."_".$iresult['CATEGORY'],2);
    		}
    	}
    }
    
    /**
     * 删除教学计划
     */
    public function delete(){
        if(!$_REQUEST['PROGRAMNO']){
            $this->exitWithReport('没有指定要删除的教学计划！');
        }

        $programs = $this->model->getPrograms($_REQUEST['PROGRAMNO']);
        if(is_string($programs)){
            $this->exitWithReport('获取教学计划失败了！'.$programs);
        }elseif(!$programs){
            $this->exitWithReport('无法查到指定的教学计划！');
        }elseif(($programs[0]['SCHOOL']!=$this->theacher['SCHOOL'] && !isDeanByUsername(getUsername()))){
            $this->exitWithReport('对不起，指定的教学计划不存在或者该教学计划不是本学部的教学计划不能删除！');
        }

        //检测该教学计划下是否有课程
        $count = $this->model->getProgramCourses($_REQUEST['PROGRAMNO'],true);
        if(is_string($count)){
            $this->exitWithReport('获取教学计划中的课程数目时出错！'.$count);
        }elseif($count>0){
            $this->exitWithReport("对不起，该教学计划下已经有 {$count} 门课程，请先删除教学计划内的课程列表！");
        }

        //检测该教学计划下是否有班级
        $count = $this->model->getProgramClasses($_REQUEST['PROGRAMNO'],true);
        if(is_string($count)){
            $this->exitWithReport('获取教学计划中的班级数目时出错！'.$count);
        }elseif($count>0){
            $this->exitWithReport("对不起，该教学计划下已经已经绑定了 {$count} 班级 ，请先删除教学计划内的班级列表！");
        }

        //检测该教学计划下是否有学生
        $count = $this->model->getProgramStudents($_REQUEST['PROGRAMNO'],true);
        if(is_string($count)){
            $this->exitWithReport('获取教学计划中的学生数目时出错！'.$count);
        }elseif($count>0){
            $this->exitWithReport("对不起，该教学计划下已经已经绑定了 {$count} 个学生的修课计划 ，请先删除教学计划内的个人选修列表！");
        }

        //检测该教学计划下是否有有辅修班
        $count = $this->model->getProgramMinorClasses($_REQUEST['PROGRAMNO'],true);
        if(is_string($count)){
            $this->exitWithReport('获取教学计划中的辅修班级数目时出错！'.$count);
        }elseif($count>0){
            $this->exitWithReport("对不起，该教学计划已经绑定到 {$count} 个班级的辅修计划，要删除该教学计划，请先删除教学计划内的辅修班列表！");
        }


        //检测是否有专业在修读该教学计划
        $count = $this->model->getProgramMajorPlan($_REQUEST['PROGRAMNO'],true);
        if(is_string($count)){
            $this->exitWithReport('获取修读该教学计划的专业方向的数目时出错！'.$count);
        }elseif($count>0){
            $this->exitWithReport("对不起，该教学计划已经绑定到 {$count} 个专业方向的修读计划，请先删除修读该教学计划的专业方向！");
        }


        //执行删除
        $rst = $this->model->deletePrograms($_REQUEST['PROGRAMNO']);
        if(is_string($count)){
            $this->exitWithReport('删除过程中出现错误！'.$rst);
        }else{
            if($rst){
                $this->exitWithReport('该教学计划已经被安全的删除','info');
            }else{
                $this->exitWithReport('系统错误，未能成功删除该教学计划');
            }
        }
    }


    /**
     * 教学计划复制
     * 复制原有教学计划下的课程列表到新的教学计划下
     * @access public
     * @return void
     */
    public function copy(){
        if(!$_REQUEST["PROGRAMNO"] || !$_REQUEST["NEWPROGRAMNO"]){
            $this->exitWithReport('没有指定要复制的教学计划！');
        }

        $originProgramNo = trim($_REQUEST['PROGRAMNO']);
        $newProgramNo = trim($_REQUEST["NEWPROGRAMNO"]);

        $programs = $this->model->getPrograms($originProgramNo);
        if(!$programs || ($programs[0]['SCHOOL']!=$this->theacher['SCHOOL'] && !isDeanByUsername(getUsername()))){
            $this->exitWithReport('对不起，指定的教学计划不存在或者该教学计划不是本学部的教学计划不能复制！');
        }
        $programs = $programs[0];

        // 启动事务机制
        $this->model->startTrans();

        //启动事务机制插入教学计划(名称不规范)
        $rst = $this->model->newProgram(array(
            $newProgramNo,
            $_REQUEST["PROGNAME"],
            $programs["SCHOOL"],
            $programs["TYPE"]
        ));
        if(is_string($rst)){
            $this->model->rollback();
            $this->exitWithReport('插入教学计划发生错误！'.$rst);
        }

        //复制教学计划修读课程
        $count = $this->model->getProgramCourses($originProgramNo,true);
        trace($count,"复制教学计划修读课程");
        if(is_string($count)){
            $this->model->rollback();
            $this->exitWithReport('查询教学计划修读课程数量失败了!');
        }
        if($count>0){
            $rst = $this->model->copyProgramCourses($newProgramNo,$originProgramNo);
            if(is_string($rst)){
                $this->model->rollback();
                $this->exitWithReport('复制教学计划修读课程时发生错误！'.$rst);
            }
        }else{
            //原先的教学计划没有课程，不复制
        }

        $this->model->commit();
        $this->exitWithReport("[{$programs["PROGRAMNO"]}]教学计划已成功复制成[{$_REQUEST["NEWPROGRAMNO"]}]教学计划！！",'info');
    }

    /**
     * 修课班级
     */
    public function classListTemplate(){
        if($this->_hasJson){
            $rst = $this->model->getProgramClassTableList($_REQUEST['programno'],$this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($rst,'JSON');
            exit;
        }
        $rst = $this->model->_getProgramDetail($_REQUEST['programno']);
        $this->assign('failed',is_string($rst)?$rst:'');
        $this->assign("programs", is_string($rst)?array():$rst);
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));

        $this->display("classlisttemplate");
    }
    /**
     * 修读班级 添加的查找页面
     */
    public function classAdd(){
        $bind = $this->model->getBind("CLASSNO,CLASSNAME,SCHOOL",$_REQUEST);
        $rtn = $this->model->getClassesTableList($bind,$this->_pageDataIndex, $this->_pageSize);
        $this->ajaxReturn($rtn,'json');//如果是错误信息，则将错误信息一并返回
        exit;
    }
    /**
     * 教学计划添加班级 班级列表提交到数据库
     */
    public function classSave(){
        if(!is_array($_REQUEST["CLASSNO"]) || count($_REQUEST["CLASSNO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }
        $programno = $_REQUEST["PROGRAMNO"];
        $msg = '';
        foreach($_REQUEST["CLASSNO"] as $CLASSNO){
            if($CLASSNO){
                $rst = $this->model->addProgramClass($programno,$CLASSNO);
                if(is_string($rst)){
                    $this->exitWithReport("班号[{$CLASSNO}]已存在于该教学计划中！");
                }elseif($rst > 0){
                    $msg .= "班号[{$CLASSNO}]添加成功！\n";
                }
            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }

    /**
     * 将教学计划绑定到班级的每个学生
     */
    public function  classBind(){
        if(!is_array($_REQUEST['CLASSNO']) || count($_REQUEST['CLASSNO'])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }
        $programno = $_REQUEST['PROGRAMNO'];
        $msg = '';
        foreach($_REQUEST['CLASSNO'] as $CLASSNO){
            if($CLASSNO){
                $this->model->startTrans();

                $rst = $this->model->deleteProgramStudentsByClassno($programno,$CLASSNO);
                if(is_string($rst)){
                    $this->model->rollback();
                    $this->exitWithReport('清除该班级原有的学生修课计划出现错误！'.$rst);
                }

                $rst = $this->model->insertProgramStudentsByClassno($programno,$CLASSNO);
                if(is_string($rst)){
                    $this->model->rollback();
                    $this->exitWithReport('添加学生到该教学计划过程中出现错误！'.$rst);
                }else{
                    $this->model->commit();
                    $msg .= "班级号为 {$CLASSNO} 下的学生成功添加到教学计划下！\n";
                }

            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }
    /**
     * 删除修读班级
     */
    public function classDelete(){
        if(!is_array($_REQUEST['CLASSNO']) || count($_REQUEST['CLASSNO'])==0){
            $this->exitWithReport('没有提交任一数据');
        }
        $programno = trim($_REQUEST['PROGRAMNO']);
        $msg = '';
        foreach($_REQUEST['CLASSNO'] as $CLASSNO){
            if($CLASSNO){
                $rst = $this->model->deleteClassesFromProgram($programno,$CLASSNO);
                if(is_string($rst) or !$rst){
                    $this->exitWithReport("班号[{$CLASSNO}]删除时发生错误");
                }else{
                    $msg .= "班号[{$CLASSNO}]删除成功！\n";
                }
            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }


    /**
     * 查看教学计划的 辅修班级
     */
    public function subsidListTemplate(){
        $bind = $this->model->getBind("programno",$_REQUEST);
        if($this->_hasJson){
            $json = array("total"=>0, "rows"=>array());
            $sql = $this->model->getSqlMap("programs/QueryGetR7Count.sql");
            $count = $this->model->sqlCount($sql, $bind);
            $json["total"] = intval($count);

            if($json["total"]>0){
                $sql = $this->model->getSqlMap("programs/QueryGetR7.sql");
                $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
                $json["rows"] = $this->model->sqlQuery($sql, $bind);
            }
            $this->ajaxReturn($json,"JSON");
            exit;
        }
        $sql = $this->model->getSqlMap("programs/QueryProgram.sql");
        $this->assign("programs", $this->model->sqlFind($sql, $bind));

        $this->display("subsidlisttemplate");
    }
    /**
     * 辅修班级添加
     */
    public function subsidAdd(){
        $bind = $this->model->getBind("CLASSNO,CLASSNAME,SCHOOL",$_REQUEST);
        $json = array("total"=>0, "rows"=>array());
        $sql = $this->model->getSqlMap("Class/QuerySubsidSelectCount.sql");
        $count = $this->model->sqlCount($sql, $bind);
        $json["total"] = intval($count);

        if($json["total"]>0){
            $sql = $this->model->getSqlMap("Class/QuerySubsidSelect.sql");
            $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
            $json["rows"] = $this->model->sqlQuery($sql, $bind);
        }
        $this->ajaxReturn($json,"JSON");
    }
    /**
     * 辅修班级保存
     */
    public function subsidSave(){
        if(!is_array($_REQUEST["CLASSNO"]) || count($_REQUEST["CLASSNO"])==0){
            $this->message["message"] = "没有提交任一数据\n";
            $this->ajaxReturn($this->message,"JSON");
            exit;
        }
        foreach($_REQUEST["CLASSNO"] as $CLASSNO){
            if($CLASSNO){
                $bind = $this->model->getBind("PROGRAMNO,CLASSNO",$_REQUEST["PROGRAMNO"].",".$CLASSNO);
                $data = $this->model->sqlExecute("INSERT INTO R7 (PROGRAMNO,CLASSNO) VALUES (:PROGRAMNO,:CLASSNO)",$bind);
                if($data===false){
                    $this->message["type"] = "error";
                    if(strpos($this->model->getDbError(), "PRIMARY KEY 约束")){
                        $this->message["message"] .= "<font color='red'>辅修班号[".$CLASSNO."]已存在！</font>\n";
                    }else{
                        $this->message["dbError"] .= $this->model->getDbError();
                    }
                }else $this->message["message"] .= "辅修班号[".$CLASSNO."]添加成功！\n";
            }
        }
        $this->message["message"] = nl2br($this->message["message"]);
        $this->ajaxReturn($this->message,"JSON");
        exit;
    }
    /**
     * 删除辅修班级
     */
    public function subsidDelete(){
        if(!is_array($_REQUEST["CLASSNO"]) || count($_REQUEST["CLASSNO"])==0){
            $this->message["message"] = "没有提交任一数据\n";
            $this->ajaxReturn($this->message,"JSON");
            exit;
        }

        foreach($_REQUEST["CLASSNO"] as $CLASSNO){
            if($CLASSNO){
                $bind = $this->model->getBind("PROGRAMNO,CLASSNO",$_REQUEST["PROGRAMNO"].",".$CLASSNO);
                $data = $this->model->sqlExecute("DELETE R7 WHERE PROGRAMNO=:PROGRAMNO AND CLASSNO=:CLASSNO",$bind);
                if($data===false){
                    $this->message["type"] = "error";
                    $this->message["message"] .= "<font color='red'>辅修班号[".$CLASSNO."]删除时发生错误！</font>\n";
                    $this->message["dbError"] .= $this->model->getDbError();
                }else $this->message["message"] .= "辅修班号[".$CLASSNO."]删除成功！\n";
            }
        }

        $this->message["message"] = nl2br($this->message["message"]);
        $this->ajaxReturn($this->message,"JSON");
        exit;
    }
    /**
     * 绑定到学生
     */
    public function  subsidBind(){
        if(!is_array($_REQUEST["CLASSNO"]) || count($_REQUEST["CLASSNO"])==0){
            $this->message["message"] = "没有提交任一数据\n";
            $this->ajaxReturn($this->message,"JSON");
            exit;
        }

        foreach($_REQUEST["CLASSNO"] as $CLASSNO){
            if($CLASSNO){
                $bind = $this->model->getBind("PROGRAMNO,CLASSNO",$_REQUEST["PROGRAMNO"].",".$CLASSNO);
                $data = $this->model->sqlExecute($this->model->getSqlMap("programs/subsidBind.sql"),$bind);
                if($data===false){
                    $this->message["type"] = "error";
                    $this->message["message"] .= "<font color='red'>辅修班号[".$CLASSNO."]绑定时发生错误！</font>\n";
                    $this->message["dbError"] .= $this->model->getDbError();
                }else $this->message["message"] .= "辅修班号[".$CLASSNO."]绑定成功！\n";
            }
        }

        $this->message["message"] = nl2br($this->message["message"]);
        $this->ajaxReturn($this->message,"JSON");
        exit;
    }

    /**
     * 教学计划下修读学生列表
     */
    public function studentsListTemplate(){
        if($this->_hasJson){
            $bind = array(
                ':programno'=>$_REQUEST['programno'],
            );
            $rst = $this->model->getProgramStudentsTableList($bind,$this->_pageDataIndex, $this->_pageSize);
            if(is_string($rst)){
                $this->exitWithReport('获取学生列表失败'.$rst);
            }
            $this->ajaxReturn($rst,'JSON');
            exit;
        }
        $rst = $this->model->_getProgramDetail($_REQUEST['programno']);
        $this->assign('failed',is_string($rst)?$rst:'');
        $this->assign("programs", is_string($rst)?array():$rst);

        $this->display("studentslisttemplate");
    }

    /**
     * 教学计划下 修读学生添加页面的搜索
     */
    public function studentsAdd(){
        $bind = $this->model->getBind("STUDENTNO,NAME,CLASSNO,CLASSNAME,SCHOOL",$_REQUEST);
        $rst = $this->model->getStudentsTableList($bind, $this->_pageDataIndex, $this->_pageSize);
        if(is_string($rst)){
            $this->exitWithReport('获取学生列表失败'.$rst);
        }
        $this->ajaxReturn($rst,"JSON");
    }

    /**
     * 教学计划下添加修读学生 提交数据库的操作
     */
    public function studentsSave(){
        if(!is_array($_REQUEST["STUDENTNO"]) || count($_REQUEST["STUDENTNO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }

        $programno = $_REQUEST["PROGRAMNO"];
        $msg = '';
        foreach($_REQUEST["STUDENTNO"] as $STUDENTNO){
            if($STUDENTNO){
                $rst = $this->model->insertProgramStudentByStudentno($programno,$STUDENTNO);
                if(is_string($rst)){
                    $this->exitWithReport("学号[{$STUDENTNO}]学生已存在！");
                }else{
                    $msg .= "学号[{$STUDENTNO}]学生添加成功！\n";
                }
            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }

    /**
     * 教学计划下学生列表 的删除学生的功能
     */
    public function studentsDelete(){
        if(!is_array($_REQUEST["STUDENTNO"]) || count($_REQUEST["STUDENTNO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }
        $programno = trim($_REQUEST["PROGRAMNO"]);
        $msg = '';
        foreach($_REQUEST["STUDENTNO"] as $STUDENTNO){
            if($STUDENTNO){
                $rst = $this->model->deleteProgramStduentByStudentno($programno,$STUDENTNO);
                if(is_string($rst)){
                    $this->exitWithReport("删除学号为'{$STUDENTNO}'的学生出现了错误！ ");
                }else{
                    $msg .= "学号[{$STUDENTNO}]学生删除成功！\n";
                }
            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }

    /**
     * 修课课程
     */
    public function courselistTemplate(){
        //跳转到课程的详细页面
        if(isset($_GET['godetail']) && ($_GET['godetail'] == 1)){
            $bind = array(':courseno'=>$_GET['courseno']);
            $sql = $this->model->getSqlMap("programs/queryCourseDetail.SQL");
            $data = $this->model->sqlFind($sql, $bind);
            if(isset($_GET['refleshData']) && ($_GET['refleshData'] == 1)){
            	$this->ajaxReturn($data,"JSON");
            	exit;
            }
        	//todo:课程Volist
        	$this->assign('school',getSchoolList());
        	//todo:课程类别Volist
        	$this->xiala('coursetypeoptions','coursetype');
        	//todo:课程类型数据Volist    (纯理论-纯实践-理论实践)
        	$this->xiala('coursetypeoptions2','coursetype2');
        	 
            //var_dump(str_replace("\"",'\'',json_encode($data)));
            $this->assign('data',str_replace("\"",'\'',json_encode($data)));
            $this->display('coursedetail');
            exit;
        }

        //自动分组
        if(isset($_GET['autogroup']) && ($_GET['autogroup'] == 1)){
            $rows = $_POST['rows'];
            $recno = null;
            $sql = 'SELECT * from R12 WHERE LIMITGROUPNO = :recno;';
            foreach($rows as $row){
                $bind = array(':recno'=>$row['RECNO']);
                $recs = $this->model->sqlQuery($sql,$bind);
                if($recs !== false && count($recs) == 0){
                    $recno = $row['RECNO'];
                    break;
                }
            }
            if($recno !== null){
                //更新数据库
                $sql = 'UPDATE R12 SET LIMITGROUPNO = :recno WHERE COURSENO = :courseno and PROGRAMNO = :programno;';
                foreach($rows as $row){
                    $bind = array(
                        ':recno' => $recno,
                        ':courseno' => $row['CourseNo'],
                        ':programno' => $row['PROGRAMNO'],
                    );
                    $ret = $this->model->sqlExecute($sql,$bind);
                    if(!$ret){
                        exit('update_failure');
                    }
                }
                exit('success');
            }
            exit('find_failure');
        }
//        $bind = $this->model->getBind("programno",$_REQUEST);

        //查询课程列表
        if($this->_hasJson){
            $json = array('total'=>0, 'rows'=>array() );
            $json['rows'] = $this->model->getProgramCoursesTableList($_REQUEST['programno']);
            if(is_string($json['rows'])){
                $this->exitWithReport('查询出错！'.$json['rows']);
            }
            if($json['total'] = count($json['rows'])){
                foreach($json['rows'] as $k=>$row){
                    $json['rows'][$k]['Weeks'] = strrev(sprintf('%018s', decbin($row['Weeks'])));
                }
            }
            $this->ajaxReturn($json,'JSON');
            exit;
        }

        $rst = $this->model->_getProgramDetail($_REQUEST['programno']);
        $this->assign('failed',is_string($rst)?$rst:'');
        $this->assign("programs", is_string($rst)?array():$rst);

//        $sql = $this->model->getSqlMap("programs/QueryProgram.sql");
//        $this->assign("programs", $this->model->sqlFind($sql, $bind));

        import('ORG.Util.Utils');
        //考试方式
        $this->assign('examtype',Utils::getComboData('examoptions','NAME','VALUE'));
        //修课方式
        $this->assign('xktype',Utils::getComboData('courseapproaches','NAME','VALUE'));
        //考试级别
        $this->assign('kaoshijibie',Utils::getComboData('testlevel','NAME','VALUE','学部统考'));
        //课程类别
//        $this->assign('kctype',Utils::getComboData('COURSECAT','NAME','VALUE'));
        $this->assign('kctype',Utils::getComboData('COURSETYPEOPTIONS','NAME','VALUE'));
        $this->assign('tgroup',$this->model->sqlQuery('SELECT TGROUP,RTRIM(NAME) as NAME from TGROUPS order by ORDERBY '));

        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->display("courselisttemplate");
    }

    private function combobox($tablename,$value,$text){
        $arr=$this->model->sqlQuery("select * from $tablename");
        $sjson=array();
        foreach($arr as $val){
            $sjson2['text']=trim($val[$text]);
            $sjson2['value']=trim($val[$value]);                  // 把学院数据转成json格式给前台的combobox使用
            array_push($sjson,$sjson2);
        }
        $sjson=json_encode($sjson);
        return $sjson;
    }


    public function updateP(){
        $this->model->startTrans();
        $msg = '';
        foreach($_POST['bind'] as $val){
            //要修改的列
            $upd = array(
                'YEAR'=>trim($val['Year']),
                'TERM'=>trim($val['Term']),
                'CREDITS'=>$val['Credits'],
                'LIMITGROUPNO'=>$val['LIMITGROUPNO'],
                'LIMITNUM'=>$val['LIMITNUM'],
                'LIMITCREDIT'=>$val['LIMITCREDIT']
            );
            //修改的时候一下的值是Code代码，判断长度是否等于1可以得出是否进行了修改操作
            if(strlen(trim($val['ExamType']))==1)   $upd['EXAMTYPE']=$val['ExamType'];
            if(strlen(trim($val['CourseType']))==1) $upd['COURSETYPE']=$val['CourseType'];
            if(strlen(trim($val['TESTVALUE']))==1)  $upd['TEST']=$val['TESTVALUE'];
            if(strlen(trim($val['CATEGORYVALUE']))==1)  $upd['CATEGORY']=$val['CATEGORYVALUE'];
            //筛选条件
            $whr = array(
                'COURSENO'=>array($val['CourseNo'],'='),
                'PROGRAMNO'=>array($val['PROGRAMNO'],'='),
            );

            $rst = $this->model->updateProgramCourse($upd,$whr);


            if(is_string($rst)){
                $this->model->rollback();
                $this->exitWithReport('更新出现错误！'.$rst);
            }elseif($rst){
                $msg .= "教学计划[{$val['PROGRAMNO']}]下的课号为{$val['CourseNo']}的课程更新成功\n";
            }
        }
        $this->model->commit();
        $this->exitWithReport(nl2br($msg),'info');
    }


    /**
     * 教学计划下修读课程 列表的查询
     */
    public function courseAdd(){
        $bind = $this->model->getBind("COURSENO,COURSENAME,SCHOOL,COURSETYPE,TGROUPNO",$_REQUEST);
        $rst = $this->model->getProgramCourseTableList($bind, $this->_pageDataIndex, $this->_pageSize);
        $this->ajaxReturn($rst,"JSON");
    }

    /**
     * 教学计划下添加修读课程 提交数据库
     */
    public function courseSave(){
        if(!is_array($_REQUEST["COURSENO"]) || count($_REQUEST["COURSENO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }
        $programno = $_REQUEST["PROGRAMNO"];
        $msg = '';
        foreach($_REQUEST['COURSENO'] as $COURSENO){
            if($COURSENO){
                $rst = $this->model->addProgramCourse($programno,$COURSENO);
                if(is_string($rst) or !$rst){
                    $this->exitWithReport("课程号[{$COURSENO}]，可能是课号已经存在的缘故！".$rst);
                }else{
                    $msg .= "课程号[{$COURSENO}]添加成功！\n";
                }
            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }

    /**
     * 删除修读课程
     */
    public function courseDelete(){
        if(!is_array($_REQUEST["COURSENO"]) || count($_REQUEST["COURSENO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }

        $programno = $_REQUEST["PROGRAMNO"];
        $msg = '';

        foreach($_REQUEST["COURSENO"] as $COURSENO){
            if($COURSENO){
                $rst = $this->model->deleteProgramCourse($programno,$COURSENO);
                if(is_string($rst)){
                    $this->exitWithReport("课程号为[{$COURSENO}]的课程删除时发生错误！");
                }elseif($rst > 0 ){
                    $msg .= "课程号为[{$COURSENO}]的课程删除成功！\n";
                }
            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }

    /**
     * 更新修读课程
     */
    public function courseUpdate(){
        if(!$_REQUEST["COURSENO"] || !isset($_REQUEST["YEAR"]) || !isset($_REQUEST["TERM"]) || !isset($_REQUEST["EXAMTYPECODE"])
            || !isset($_REQUEST["TESTCODE"]) || !isset($_REQUEST["CATEGORYCODE"]) || !isset($_REQUEST["WEEKS"])){
            $this->message["message"] = "提交的数据非法\n";
            $this->ajaxReturn($this->message,"JSON");
            exit;
        }

        $_REQUEST["WEEKS"] = bindec(strrev($_REQUEST["WEEKS"]));
        $bind = $this->model->getBind("COURSETYPECODE,EXAMTYPECODE,TESTCODE,CATEGORYCODE,YEAR,TERM,WEEKS,COURSENO,PROGRAMNO",$_REQUEST);
        $data = $this->model->sqlExecute("UPDATE R12 SET COURSETYPE=:COURSETYPECODE,EXAMTYPE=:EXAMTYPECODE,TEST=:TESTCODE,CATEGORY=:CATEGORYCODE,YEAR=:YEAR,TERM=:TERM,WEEKS=:WEEKS WHERE COURSENO=:COURSENO AND PROGRAMNO=:PROGRAMNO", $bind);
        if($data===false){
            $this->message["type"] = "error";
            $this->message["message"] .= "<font color='red'>课程号[".$_REQUEST["COURSENO"]."]更新时发生错误！</font>\n";
            $this->message["dbError"] .= $this->model->getDbError();
        }else $this->message["message"] .= "课程号[".$_REQUEST["COURSENO"]."]更新成功！\n";

        $this->message["message"] = nl2br($this->message["message"]);
        $this->ajaxReturn($this->message,"JSON");
        exit;
    }
}