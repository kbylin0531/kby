<?php
class CourseAction extends RightAction{


    private $model = null;
    /*
     * 新建课程的方法
     */
    public function index(){
        $this->display();
    }
    public function __construct(){
        parent::__construct();
        $this->model = new CourseModel();
    }


    /*
     * 添加时候用到的 前台验证
     */
    function numberyz(){
            $shuju=M('courses');
        if(strlen($_POST['COURSENO'])!=7){
            exit('sev');
        }
            $one=$shuju->where($_POST)->find();
            if($one){
                echo 'false';
            }else{
                echo 'aa';
            }
    }

    /*
     * todo:新建课程的方法。
     */
    function newcourse(){
    	
        //todo:课程类别Volist
        $this->xiala('coursetypeoptions','coursetype');
        //todo:课程类型数据Volist    (纯理论-纯实践-理论实践)
        $this->xiala('coursetypeoptions2','coursetype2');
        //当前登录用户所在学院

//        varsdump($_SESSION['S_USER_INFO']['SCHOOL']);

        $mdl=new SqlsrvModel();
        $this->assign("userSchool",$_SESSION['S_USER_INFO']['SCHOOL']);
        
        $isdean = $this->checkUserIsDean(getUsername());
        
        $this->assign('tgroup',$mdl->sqlQuery('SELECT * from TGROUPS order by ORDERBY ;'));
        
        $this->assign('school',getSchoolList());
        
        $this->assign('isdean',$isdean);
        
        $user_teacherno=$_SESSION["S_USER_INFO"]["TEACHERNO"];
        $user_school = $mdl->sqlFind("SELECT T.SCHOOL,T.TGROUP FROM TEACHERS T WHERE T.TEACHERNO='$user_teacherno'");
        $this->assign('user_school',$user_school["SCHOOL"]);
        $this->assign('user_tgroup',$user_school["TGROUP"]);
        
        $this->display();
    }

    /*
     * 对课程进行插入操作的方法
     */
    function courseyz(){
     $shuju=M('courses');
        foreach($_POST AS $key=>$value){        //对旁边的空格进行过滤用的
            $arr[$key]=trim($value);
        }
       $arr['课程介绍']=$arr['INTRODUCE'];
        unset($arr['INTRODUCE']);
         $num=$shuju->add($arr);
        if($num){
            echo 'true';
        }else{
            echo 'false';
        }

    }

    /**
     * 检索课程
     */
    function scourse(){
        $shuju=new SqlsrvModel();
        
    	if($this->_hasJson){
            $count=$shuju->getSqlMap('course/teacher/coursecount.SQL');              //统计条数的sql
            $sql=$shuju->getSqlMap('course/teacher/courseselect.SQL');               //查询课程的sql语句
            $bind=array(':TYPE'=>doWithBindStr($_POST['TYPE']),':TGROUP'=>doWithBindStr($_POST['TGROUP']),':SCHOOL'=>doWithBindStr($_POST['SCHOOL']),':COURSENO'=>doWithBindStr($_POST['COURSENO']) ,':OLDCOURSENO'=>  doWithBindStr($_POST['OLDCOURSENO']) ,':COURSENAME'=>  doWithBindStr($_POST['COURSENAME']) ,':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize);
            $bind2=array(':TYPE'=>doWithBindStr($_POST['TYPE']),':TGROUP'=>doWithBindStr($_POST['TGROUP']),':SCHOOL'=>  doWithBindStr($_POST['SCHOOL']) ,':COURSENO'=>  doWithBindStr($_POST['COURSENO']) ,':OLDCOURSENO'=>  doWithBindStr($_POST['OLDCOURSENO']) ,':COURSENAME'=>  doWithBindStr($_POST['COURSENAME']) );
            $one=$shuju->sqlQuery($count,$bind2);
            if($arr['total']=$one[0]['']){
            $arr['rows']=$shuju->sqlQuery($sql,$bind);
            }else{
            $arr['rows']=array();
            }
            $this->ajaxReturn($arr,'JSON');
            exit;
        }
		$this->assign('school',getSchoolList());
		$this->assign('tgroup',$shuju->sqlQuery('SELECT * from TGROUPS order by ORDERBY ;'));
        $this->xiala('coursetypeoptions','coursetype');
        $this->xiala('coursetypeoptions2','coursetype2');
        $this->display();
    }

    /**
     * excel导入编辑
     */
    public function excelimpcourseedit(){
    
    	$this->display();
    }
    
    /**
     * excel导入保存
     */
    public function excelimpcoursesave(){
    
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

    	TaskMonitorModel::init(session("S_USER_NAME"), "课程信息excel导入");
    	 
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
    	//		$columns = array ( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L' );
    	//		$keysInFile = array ( '课程代码', '课程名称', '课程类别', '考试方式', '总课时', '课程英文名', '课堂模式', '难度等级', '适用对象', '状态', '教研组代码', '学院代码' );
    	//		foreach( $columns as $keyIndex => $columnIndex ){
    	//			if ( $keywords[$columnIndex] != $keysInFile[$keyIndex] ){
    	//				echo $warning . $columnIndex . '列应为' . $keysInFile[$keyIndex] . '，而非' . $keywords[$columnIndex];
    	//				exit();
    	//			}
    	//		}
    
    	$mdl=new SqlsrvModel();

    	TaskMonitorModel::run(session("S_USER_NAME"),"课程信息excel导入", count($sheetData)-1);
    	 
    	$result = array();
    	$count = 0;
    	$succount = 0;
    	$failcount = 0;
    
    	$key_val = array ( '课程代码'=>'A', '课程名称'=>'B', '课程类别'=>'C', '考试方式'=>'D', '总课时'=>'E', '课程英文名'=>'F', '课堂模式'=>'G', '难度等级'=>'H', '适用对象'=>'I', '状态'=>'J', '教研组代码'=>'K', '学院代码'=>'L' );
    	foreach ( $sheetData as $key => $words ){
    		if ( $key != 1 ){
    			$count++;
    			 
    			$courseno = trim($words[$key_val['课程代码']]);
    			$coursename = trim($words[$key_val['课程名称']]);
    			if (isset($courseno) && !empty($courseno) && isset($coursename) && !empty($coursename))
    			{
    				$typename = trim($words[$key_val['课程类别']]);
    				$typename2 = trim($words[$key_val['课堂模式']]);
    				$tgroupno = trim($words[$key_val['教研组代码']]);
    				$schoolno = trim($words[$key_val['学院代码']]);    				
    				$total = trim($words[$key_val['总课时']]);    				

    				$typeno = "";
    				if (isset($typename) && !empty($typename))
    				{
    					$typeOBJ = $mdl->sqlFind("SELECT * FROM COURSETYPEOPTIONS WHERE upper(ltrim(rtrim(VALUE))) = :VALUE ",array(":VALUE" => strtoupper($typename)));
    					if ($typeOBJ)
    					{
    						$typeno = $typeOBJ['NAME'];
    					}
    				}
    				
    				$typeno2 = "";
    				if (isset($typename2) && !empty($typename2))
    				{
    					$type2OBJ = $mdl->sqlFind("SELECT * FROM COURSETYPEOPTIONS2 WHERE upper(ltrim(rtrim(VALUE))) = :VALUE ",array(":VALUE" => strtoupper($typename2)));
    					if ($type2OBJ)
    					{
    						$typeno2 = $type2OBJ['NAME'];
    					}
    				}
    				
    				$courseOBJ = $mdl->sqlFind("SELECT * FROM COURSES WHERE upper(ltrim(rtrim(COURSENO))) = :COURSENO ",array(":COURSENO" => strtoupper($courseno)));
    				if ($courseOBJ)
    				{
    					$failcount++;
    
    					$iresult = array();
    					$iresult["row"] = $key;
    					$iresult["courseno"] = $courseno;
    					$iresult["coursename"] = $coursename;
    					$iresult["content"] = "相同课程代码数据库已存在";
    
    					array_push($result, $iresult);    
    					
    					TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$courseno,2);
    				}
    				else
    				{
    					$ary = array(
    							":COURSENO" => strtoupper($courseno),
    							":COURSENAME" => $coursename,
    							":TYPE" => $typeno,
    							":TYPE2" => $typeno2,
    							":TGROUP" => $tgroupno,
    							":SCHOOL" => $schoolno
    					);

    					//开始添加
   						$sql = " insert into COURSES(COURSENO,COURSENAME,TYPE,TYPE2,TGROUP,SCHOOL,CREDITS,TOTAL,HOURS,Lhours,EXPERIMENTS,COMPUTING,SHOURS,KHOURS,ZHOURS,Limit,Quarter) VALUES
 (:COURSENO,:COURSENAME,:TYPE,:TYPE2,:TGROUP,:SCHOOL,0,0,0,0,0,0,0,0,0,0,1); ";
   							
    					$bool = $mdl->sqlExecute($sql,$ary);
    
    					if ($bool)
    					{
    						$succount++;
    						
    						TaskMonitorModel::next(session("S_USER_NAME"),$count,true,$courseno,2);
    					}
    					else
    					{
    						$failcount++;
    							
    						$iresult = array();
    						$iresult["row"] = $key;
    						$iresult["courseno"] = $courseno;
    						$iresult["coursename"] = $coursename;
    						$iresult["content"] = "数据库出错导入失败";
    							
    						array_push($result, $iresult);
    						
    						TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$courseno,2);
    					}    

    				}
    
    			}
    			else
    			{
    				$failcount++;
    
    				$iresult = array();
    				$iresult["row"] = $key;
    				$iresult["content"] = "课程代码或课程名称为空没有导入";
    
    				array_push($result, $iresult);
    				
    				TaskMonitorModel::next(session("S_USER_NAME"),$count,false,$courseno,2);
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
//    	$this->display("excelimpcourseresult");    
    }

    /**
     * excel导入结果输出
     */
    public function excelimpcourseresult(){
    	 
    	$result = wincache_ucache_get(session("S_USER_NAME")."_RESULT");
    	wincache_ucache_delete(session("S_USER_NAME")."_RESULT");

    	$succount = wincache_ucache_get(session("S_USER_NAME")."_succount");
    	wincache_ucache_delete(session("S_USER_NAME")."_succount");
    	 
    	$failcount = wincache_ucache_get(session("S_USER_NAME")."_failcount");
    	wincache_ucache_delete(session("S_USER_NAME")."_failcount");
    	 
    	$this->assign("result",$result);
    	$this->assign("succount",$succount);
    	$this->assign("failcount",$failcount);
    	$this->display("excelimpcourseresult");
    }
    
    /*
     * 删除课程的方法
     */
    function decourse(){
        $shuju=new SqlsrvModel();
       // $sql=$shuju->getSqlMap('./courseDelete.SQL');                //删除课程的方法
        if(count($_POST['in'])==1&&$_POST['in'][0]==""){
            exit('false');
        }else{
            $v="";
            foreach($_POST['in'] as $val){
                $val=str_replace($val,"'$val'",$val);
                $v.=$val.',';                                         //拼接条件
            }
            $v=rtrim($v,',');
         //   $bind=array(':jihe'=>"(003J03A)");
         $sql=  "delete from COURSES where COURSENO in ($v)";
            $row=$shuju->sqlExecute($sql);
                if($row){
                    echo 'yes';
                }else{

                    echo 'false';
                }
        }

    }

    /*
     * 查看详细信息时候的方法
     */
    function infoaa(){
        //COURSETYPEOPTIONS2.[VALUE] AS TYPE2NAME,COURSETYPEOPTIONS.[VALUE] AS TYPENAME,,isnull(temp.amount,0) as sycs
        $shuju=new SqlsrvModel();
        $sql=$shuju->getSqlMap('course/teacher/infoaa.SQL');
        $bind=array(':idone'=>$_POST['id'],':idtwo'=>$_POST['id']);

    $one=$shuju->sqlQuery($sql,$bind);

        echo json_encode($one[0]);
    }

    /**
     * 根据需要修改课程信息
     */
   function courseup(){
       $teacher = $this->model->getTeacherInfo();
       $course = $this->model->getCourseInfo(trim($_POST['COURSENO']));
       if (trim($course['SCHOOL']) !== trim($teacher['SCHOOL']) and !isDeanByUsername(getUsername())){
            $this->exitWithReport('非教务处人员无法修改其他学院开设的课程！');
       }
       $updFileds = array();
       foreach($_POST as $key => $item){
           if(in_array($key,array(
               'COURSENAME','TGROUP','CREDITS','TOTAL','HOURS','LHOURS','EXPERIMENTS','COMPUTING'
           ,'SHOURS','KHOURS','ZHOURS','Limit','QUARTER','TYPE','TYPE2','SYLLABUS','REM'
           ))){
                $updFileds[$key]    = $item;
           }elseif($key === 'EditSchool'){
               $updFileds['SCHOOL'] = $item;
           }elseif($key === 'INTRODUCE'){
               $updFileds['课程介绍'] = $item;
           }
       }

       $rst = $this->model->updateCourse($_POST['COURSENO'],$updFileds
//           array(
//           'COURSENAME' => $_POST['COURSENAME'],
//           'SCHOOL' => $_POST['EditSchool'],//开课学院
//           'TGROUP' => $_POST['TGROUP'],//??
//           'CREDITS'    => $_POST['CREDITS'],
//           'TOTAL'  =>$_POST['TOTAL'],
//           'HOURS'   =>$_POST['HOURS'],
//           'Lhours'   =>$_POST['LHOURS'],
//           'EXPERIMENTS'   =>$_POST['EXPERIMENTS'],
//           'COMPUTING'   =>$_POST['COMPUTING'],
//           'SHOURS'   =>$_POST['SHOURS'],
//           'KHOURS'   =>$_POST['KHOURS'],
//           'ZHOURS'   =>$_POST['ZHOURS'],
//           'Limit'   =>$_POST['Limit'],
//           'Quarter'   =>$_POST['QUARTER'],
//           'TYPE' => $_POST['TYPE'],
//           'TYPE2' => $_POST['TYPE2'],
//           'SYLLABUS' => $_POST['SYLLABUS'],
//           'REM' => $_POST['REM'],
//           '课程介绍' => $_POST['INTRODUCE'],
//       )
   );
       if(is_string($rst)){
            $this->exitWithReport('更新课程信息失败！'.$rst);
       }elseif(!$rst){
           $this->exitWithReport("无法更新课程号为[{$_POST['COURSENO']}]的课程的信息!");
       }else{
           $this->exitWithReport("成功更新课程号为[{$_POST['COURSENO']}]的课程的信息",'info');
       }
}

    /**
     * 等价课程
     */
    public function eqcourse(){
        if($this->_hasJson){
            $shuju=new SqlsrvModel();
           $sql=$shuju->getSqlMap('course/teacher/eqcourseselect.SQL');                 //查询
            $count=$shuju->getSqlMap('course/teacher/eqcoursecount.SQL');                //统计
            $bind=array(':COURSENO'=>doWithBindStr($_POST['COURSENO']),':EQNO'=>doWithBindStr($_POST['EQNO']),':PROGRAMNO'=>doWithBindStr($_POST['PROGRAMNO']),':strat'=>$this->_pageDataIndex,':end2'=>$this->_pageDataIndex+$this->_pageSize);
            $bind2=array(':COURSENO'=>doWithBindStr($_POST['COURSENO']),':EQNO'=>doWithBindStr($_POST['EQNO']),':PROGRAMNO'=>doWithBindStr($_POST['PROGRAMNO']));
            $ct=$shuju->sqlQuery($count,$bind2);
            if($arr['total']=$ct[0]['']){
                $arr['rows']=$shuju->sqlQuery($sql,$bind);
            }else{
                $arr['rows']=array();
            }
            $this->ajaxReturn($arr,'JSON');
            exit;
        }
        $this->display();

    }

    /*
     * 等价课程的添加
     */
    public function eqcourseadd(){
        $shuju=M('r33');                //等价关系表
        $shuju2=M('courses');
        $shuju3=M('programs');         //教学计划表
        $courseno['COURSENO']=$_POST['COURSENO'];                                //判断课号
        $one=$shuju2->where($courseno)->select();
        $eqno['EQNO']=$_POST['EQNO'];                                             //判断等价课号
        $two=$shuju2->where($eqno)->find();
        $programno['PROGRAMNO']=$_POST['PROGRAMNO'];                            //判断教学计划号
        $three=$shuju3->where($programno)->find();


        if($one==null){
            exit('1');               //      课程号不存在
        }

        if($two==null){
            exit('2');              //等价课程号不存在
        }

        if($three==null){
            exit('3');              //教学计划号不存在
        }

       $pd=$shuju->add($_POST);
            if($pd){
                echo 'yes';
            }else{
                echo 'no';
            }
    }

    /*
     * 等价课程的删除
     */
    public function eqcoursede(){
       $shuju=new SqlsrvModel();
       $sql= $shuju->getSqlMap("course/teacher/eqcoursedelete.SQL");
       $bind=array(":COURSENO"=>$_POST['COURSENO'],':EQNO'=>$_POST['EQNO'],':PROGRAMNO'=>$_POST['PROGRAMNO']);
        $boo=$shuju->sqlQuery($sql,$bind);
        echo 'yes';
    }
}
?>