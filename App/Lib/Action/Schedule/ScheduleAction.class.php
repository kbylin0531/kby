<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 14-5-26
 * Time: 下午4:53
 */
class ScheduleAction extends RightAction {
    /**
     * @var ScheduleModel
     */
    private $model;
    protected $message = array("type"=>"info","message"=>"","dbError"=>"","room"=>"");


    public function __construct(){
        parent::__construct();
        $this->model = new ScheduleModel();
    }

    public function qform(){
        $this->__done("qform");
    }


    public function shiyong(){
        $this->assign('schools',getSchoolList());                   //todo:学校
        $this->xiala('areas','areas');                     //todo:  校区
        $this->xiala('timesectors','timesectors');        //todo:空闲时段
        $this->xiala('roomoptions','roomoptions');        //todo:设施表
        $this->display();
    }

    public function qlist(){
        if(REQTAG === 'export20150909'){
//            vardump($_GET
//                /*,explode('&',urldecode($_SERVER['QUERY_STRING']))*/);
            $filter = '';
            switch($_GET['hasSet']){
                case 'all':
                    break;
                case 'yes':
                    $filter = " and TIMESECTORS.name != '0' ";
                    break;
                case 'no':
                    $filter = " and TIMESECTORS.name = '0' ";
                    break;
                case null:
                    throw new Exception();
            }
            $bind = $this->model->getBind("schoolno,YEAR,TERM,COURSENO,GROUP,CLASSNO,APPROACHES,EXAMTYPE,ROOMR,UNIT,DAY,TIME,ROOMNO,TEACHERNO,TEACHERNAME,COURSENAME,SCHOOL,COURSETYPE",$_GET,"%");  //,ROOMTYPE
            $rst = $this->model->getScheduleExcelList($bind,$filter);

//            vardump($rst);
            $this->model->initPHPExcel();
            $data['title'] = '排课时间表';
            //表头
            $data['head'] = array(
                //默认值如 align type 的设计实例
                'COURSENO' => array( '课号', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'COURSENAME' => array(0=> '课名','align' => CommonModel::ALI_RIGHT,'width'=>30),
                'DAY' => array( '星期', 'align' => CommonModel::ALI_CENTER),
                'TIME' => array( '时段', 'align' => CommonModel::ALI_CENTER,'width'=>30),
                'TEACHERNAME' => array( '教师', 'align' => CommonModel::ALI_CENTER,'color' => array('argb' => '00000000')),
                'ROOMNAM' => array( '教室', 'align' => CommonModel::ALI_CENTER,'width'=>30),
                'REM'   => array( '课程介绍', 'align' => CommonModel::ALI_LEFT,'width'=>50),
            );
            $xlsData = $rst;
            //表体
            $data['body'] = $xlsData;
            $this->model->fullyExportExcelFile($data,$data['title']);
            exit;
        }
        if($this->_hasJson){
            $filter = '';
            switch($_POST['hasSet']){
                case 'all':
                    break;
                case 'yes':
                    $filter = " and TIMESECTORS.name != '0' ";
                    break;
                case 'no':
                    $filter = " and TIMESECTORS.name = '0' ";
                    break;
                case null:
                    throw new Exception();
            }
            $bind = $this->model->getBind("schoolno,YEAR,TERM,COURSENO,GROUP,CLASSNO,APPROACHES,EXAMTYPE,ROOMR,UNIT,DAY,TIME,ROOMNO,CLASSNAME,TEACHERNO,TEACHERNAME,COURSENAME,SCHOOL,COURSETYPE",$_REQUEST,"%");  //,ROOMTYPE
            $json = $this->model->getScheuleTablelist($bind,$filter,$this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($json,"JSON");
            exit;
        }
        //scheduleQueryByCourseNo.sql
        $this->assign("queryParams",count($_REQUEST)>0?json_encode($_REQUEST):"{}");
        $this->assign("expParams",count($_REQUEST)>0?$_REQUEST:array());
        $this->assign('querystring',$_SERVER["QUERY_STRING"]);
        $this->__done("qlist");
    }

    public function manual(){

        $bind = $this->model->getBind("YEAR,TERM",$_REQUEST);

        //todo 教室时间表
        $sql = $this->model->getSqlMap("Schedule/timetableRoom.sql");
        $sql = str_replace(":COURSENO", $this->formatCourseNO($_REQUEST['COURSENOS']), $sql);
        $data = $this->model->sqlQuery($sql, $bind);

   /*     echo '<pre>';
        print_r($data);*/

        if($data && count($data)>0) $haveroom=$this->formatTimetable($data);
        $roomarr=array();
     /*   echo '<pre>';
        print_r($data);*/

        //todo 班级时间表
        $sql = $this->model->getSqlMap("Schedule/timetableClass.sql");
        $sql = str_replace(":COURSENO", $this->formatCourseNO($_REQUEST['COURSENOS']), $sql);
        $data = $this->model->sqlQuery($sql, $bind);


        if($data && count($data)>0)$haveclass=$this->formatTimetable($data);
        $classarr=array();


        //todo 教师时间表
        $sql = $this->model->getSqlMap("Schedule/timetableTeacher.sql");
        $sql = str_replace(":COURSENO", $this->formatCourseNO($_REQUEST['COURSENOS']), $sql);
        $data = $this->model->sqlQuery($sql, $bind);
        if($data && count($data)>0) $haveteacher=$this->formatTimetable($data);
        $teacherarr=array();



        //todo 课程时间表
        $sql = $this->model->getSqlMap("Schedule/timetableCourse.sql");
        $sql = str_replace(":COURSENO", $this->formatCourseNO($_REQUEST['COURSENOS']), $sql);
        $data = $this->model->sqlQuery($sql, $bind);

        if($data && count($data)>0) $havecourse=$this->formatTimetable($data);
        $coursearr=array();


        //todo 开课表
//        $sql = $this->model->getSqlMap("Schedule/timetableScheduleQueryByCourseNo.sql");
        $sql = "
SELECT
dbo.getOne(SCHEDULE.COURSENO)+dbo.getOne(SCHEDULE.[GROUP]) AS COURSE,
dbo.getOne(COURSES.COURSENAME) as COURSENAME,
dbo.getOne(SCHEDULE.DAY) as [DAY],
dbo.getOne(SCHEDULE.TIME) as [TIME],
dbo.getOne(TIMESECTORS.VALUE) AS TIMENAME,
dbo.getOne(SCHEDULE.ROOMNO) as ROOMNO,
dbo.getOne(TEACHERPLAN.HOURS) as [HOURS],
dbo.getOne(SCHEDULEPLAN.WEEKS) as WEEKS,
dbo.getOne(SCHEDULE.MAP) as MAP,
dbo.getOne(CLASSROOMS.SEATS) as SEATS,
dbo.getOne(CLASSROOMS.USAGE) as [USAGE],
dbo.getOne(CLASSROOMS.JSN) AS ROOMNAME,
dbo.getOne(TEACHERPLAN.TEACHERNO) as TEACHERNO,
dbo.getOne(TEACHERS.NAME) AS TEACHERNAME,
dbo.getOne(SCHEDULE.OEW) AS OEW,
dbo.getOne(OEWOPTIONS.NAME) AS WEEKNAME,
dbo.getOne(SCHEDULE.LE) AS TASK,
dbo.getOne(TASKOPTIONS.NAME) AS TASKNAME,
dbo.getOne(SCHEDULEPLAN.ESTIMATE) as ESTIMATE,
dbo.getOne(SCHEDULE.RECNO) as RECNO,
dbo.getOne(SCHEDULE.UNIT) as UNIT,
dbo.getOne(UNITOPTIONS.NAME) AS UNITNAME,
dbo.getOne(SCHEDULE.TIMER) as TIMER,
dbo.getOne(TIMESECTIONS.VALUE) AS TIMEREQUEST,
dbo.getOne(SCHEDULE.ROOMR) as ROOMR,
dbo.getOne(SCHEDULEPLAN.AREA) as AREA,
dbo.getOne(ROOMOPTIONS.VALUE) as ROOMEQUIPMENT,
dbo.getOne(ROOMOPTIONS.NAME) as ROOMR2,
dbo.getOne(COURSES.SCHOOL) as SCHOOL,
dbo.getOne(SCHOOLS.NAME) as SCHOOLNAME,
dbo.GROUP_CONCAT_MERGE(CLASSES.CLASSNO +'('+RTRIM(CLASSES.CLASSNAME)+')', ',') AS CLASSNAME,
dbo.GROUP_CONCAT_MERGE(CLASSES.CLASSNO,',') as CLASSNO,
dbo.GROUP_CONCAT_MERGE(CLASSES.CLASSNO+'-'+RTRIM(CLASSES.CLASSNAME),',') AS CLASSNO2,
cast(dbo.getOne(TIMESECTORS.TIMEBITS) as int) as TIMES
FROM SCHEDULE
LEFT OUTER JOIN SCHEDULEPLAN ON SCHEDULE.COURSENO = SCHEDULEPLAN.COURSENO and SCHEDULE.[GROUP] = SCHEDULEPLAN.[GROUP]
	and SCHEDULE.[YEAR] = SCHEDULEPLAN.[YEAR] and SCHEDULE.TERM = SCHEDULEPLAN.TERM
LEFT OUTER JOIN TEACHERPLAN ON SCHEDULEPLAN.RECNO=TEACHERPLAN.MAP
LEFT OUTER JOIN COURSEPLAN ON (SCHEDULE.COURSENO=COURSEPLAN.COURSENO AND SCHEDULE.[GROUP]=COURSEPLAN.[GROUP] AND SCHEDULE.YEAR=COURSEPLAN.YEAR AND SCHEDULE.TERM=COURSEPLAN.TERM)
LEFT OUTER JOIN CLASSES ON (COURSEPLAN.CLASSNO=CLASSES.CLASSNO)
LEFT OUTER JOIN TIMESECTORS ON SCHEDULE.TIME=TIMESECTORS.NAME
LEFT OUTER JOIN COURSES ON SCHEDULEPLAN.COURSENO=COURSES.COURSENO
LEFT OUTER JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN TASKOPTIONS ON (SCHEDULE.LE=TASKOPTIONS.CODE)
LEFT OUTER JOIN ROOMOPTIONS ON (SCHEDULE.ROOMR=ROOMOPTIONS.NAME)
LEFT OUTER JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO
LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIMER=TIMESECTIONS.NAME
LEFT OUTER JOIN UNITOPTIONS ON SCHEDULE.UNIT=UNITOPTIONS.CODE
LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO
LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE
WHERE SCHEDULEPLAN.YEAR=:YEAR AND SCHEDULEPLAN.TERM=:TERM
AND (SCHEDULEPLAN.COURSENO+SCHEDULEPLAN.[GROUP] IN (:COURSENO))
GROUP BY SCHEDULE.COURSENO, SCHEDULE.[GROUP], SCHEDULE.RECNO
ORDER BY SCHEDULE.COURSENO,SCHEDULE.[GROUP]";
        $sql = str_replace(":COURSENO", $this->formatCourseNO($_REQUEST['COURSENOS']), $sql);
        $data = $this->model->sqlQuery($sql, $bind);
// vardump($data);
     /*   echo '<pre>';
        var_dump($data);*/
       // print_r($data);
     /* echo '<pre>';
        print_r($data);*/
        $arr=$this->formatTimetable($data);
            // echo '<pre>';
//        print_r($this->formatTimetable($data));
        if($data && count($data)>0) $this->assign("lstSchedule",$this->formatTimetable($data));

//todo:class
            $classno=array();
            if(is_array($haveclass)){
                foreach($haveclass as $val){
                    $classno[trim($val['WHO'])]=$val['WHO'];
                }
            }else{
                $haveclass=array();
            }
            $array=array();
            foreach($data as $val){
                $douhao=explode(',',$val['CLASSNO2']);
                $arr=array();
                foreach($douhao as $val){
                    $heng=explode('-',$val);
                    if(isset($classno[$heng[0]]))continue;
                    $arr['WHO']=$heng[0];
                    $arr['TYPE']='C';
                    $arr['WHOSNAME']=$heng[1];
                    $arr['MON']=0;$arr['TUE']=0;$arr['WES']=0;$arr['THU']=0;$arr['FRI']=0;$arr['SAT']=0;$arr['SUN']=0;
                    array_push($array,$arr);
                    $classno[$heng[0]]='true';
                }
            }
            $nohaveclass=$this->formatTimetable($array);
  /*      echo '<pre>';
        print_r($haveclass);
        echo '====================================';
        print_r($nohaveclass);*/
            $this->assign("lstClass",array_merge($haveclass,$nohaveclass));
      /*      echo '<pre>';
            print_r($this->formatTimetable($array));*/


//todo: teacher

            $teacherno=array();
            if(is_array($haveteacher)){
                foreach($haveteacher as $val){
                    $teacherno[trim($val['WHO'])]=$val['WHO'];
                }
            }else{
                $haveteacher=array();
            }
            $array=array();
            foreach($data as $val){
                    if(isset($teacherno[$val['TEACHERNO']])/*||$val['TEACHERNO']=='000000'*/)continue;
                    $arr['WHO']=$val['TEACHERNO'];
                    $arr['TYPE']='T';
                    $arr['WHOSNAME']=$val['TEACHERNAME'];
                    $arr['MON']=0;$arr['TUE']=0;$arr['WES']=0;$arr['THU']=0;$arr['FRI']=0;$arr['SAT']=0;$arr['SUN']=0;
                    array_push($array,$arr);
                    $teacherno[$val['TEACHERNO']]='true';
            }
            $nohaveteacher=$this->formatTimetable($array);
            $this->assign("lstTeacher",array_merge($haveteacher,$nohaveteacher));
            




//todo:course
            $courseno=array();
            if(is_array($havecourse)){
                foreach($havecourse as $val){
                    $courseno[trim($val['WHO'])]=$val['WHO'];
                }
            }else{
                $havecourse=array();
            }
            $array=array();
            foreach($data as $val){
                if(isset($courseno[$val['COURSE']]))continue;
                $arr['WHO']=$val['COURSE'];
                $arr['TYPE']='P';
                $arr['WHOSNAME']=$val['COURSENAME'];
                $arr['MON']=0;$arr['TUE']=0;$arr['WES']=0;$arr['THU']=0;$arr['FRI']=0;$arr['SAT']=0;$arr['SUN']=0;
                array_push($array,$arr);
                $courseno[$val['COURSE']]='true';
            }
            $nohavecourse=$this->formatTimetable($array);
        //array($havecourse,$nohavecourse)
            $this->assign("lstCourse",array_merge($havecourse,$nohavecourse));


//todo:room
        $roomno=array();
    /*    echo '<pre>';
        print_r($haveroom);*/
        if(is_array($haveroom)){
            foreach($haveroom as $key=>$val){
        /*        if(trim($val['WHO'])=='000000000')continue;*/
                $roomno[trim($val['WHO'])]=trim($val['WHO']);
            }
        }else{
            $haveroom=array();
        }
        $array=array();
    /*        echo '<pre>';
        print_r($data);*/
        foreach($data as $val){
            if(isset($roomno[$val['ROOMNO']])/*||trim($val['ROOMNO'])=='000000000'*/)continue;

            $arr['WHO']=$val['ROOMNO'];
            $arr['TYPE']='R';
            $arr['WHOSNAME']=$val['ROOMNAME'];
            $arr['MON']=0;$arr['TUE']=0;$arr['WES']=0;$arr['THU']=0;$arr['FRI']=0;$arr['SAT']=0;$arr['SUN']=0;
            array_push($array,$arr);
            $roomno[$val['ROOMNO']]='true';
        }

        $nohaveroom=$this->formatTimetable($array);
/*        echo '<pre>';
        print_r(array_merge($haveroom,$nohaveroom));*/
        $this->assign("lstRoom",array_merge($haveroom,$nohaveroom));

        $this->assign('content',$_REQUEST);
        $this->__done("manual");
    }

    //todo:点击复制一条
    public function COPY(){



        $oew=array('单周'=>'O','双周'=>'E','单双'=>'B');
        $unit=array('单节课'=>1,'双节课'=>2,'三节课'=>3,'四节课'=>4);
        $int=$this->model->sqlfind("SELECT YEAR,TERM,COURSENO,[GROUP],ROOMR,ROOMNO,OEW,WEEKS,LE,UNIT,MAP,EMPROOM FROM SCHEDULE WHERE RECNO={$_POST['recno']}");
        if($int){       //存在才执行
            $bool=$this->model->sqlexecute($this->model->getSqlmap('Schedule/insertSchedule.SQL'),
               array(':year'=>$_POST['year'],':term'=>$_POST['term'],':courseno'=>substr($_POST['td'][0],0,7),':gp'=>substr($_POST['td'][0],7),
               ':roomr'=>$_POST['roomr'],':roomno'=>rtrim($_POST['td'][5]),':oew'=>$oew[$_POST['td'][6]],':weeks'=>bindec($_POST['td'][11]),':le'=>'L',
        ':unit'=>$unit[$_POST['td'][10]],':map'=>$_POST['map'],':emproom'=>''));
            echo '复制成功';

        }


       /* INSERT INTO SCHEDULE (YEAR,TERM,COURSENO,[GROUP],ROOMR,ROOMNO,OEW,WEEKS,LE,UNIT,MAP,EMPROOM,DAY,TIME)
VALUES(:year,:term,:courseno,:gp,:roomr,:roomno,:oew,:weeks,;le,:unit,:map,:emproom,0,'0')
'2011',1,'011M04D','05','M','00A203060','B',262143,'L','2',60425,'*/
    }


    //todo:点击删除一条记录
    public function delete(){

        $timesectors=$this->model->sqlquery("select * from timesectors");
        foreach($timesectors as $v){
            $this->unit[$v['NAME']]=$v['TIMEBITS'];
        }

        $inschedule=$this->model->sqlfind("SELECT LOCK FROM SCHEDULE WHERE RECNO={$_POST['recno']}");

        $this->model->sqlfind($this->model->getSqlmap('Schedule/delete_queryRECNO.SQL'),array(':recno'=>$_POST['recno']));

        $courseno=substr($_POST['td'][0],0,7);
        $gp=substr($_POST['td'][0],7);
        //查询班级号
        $this->model->sqlfind("SELECT CLASSNO FROM COURSEPLAN WHERE YEAR={$_POST['year']} AND TERM={$_POST['term']} AND COURSENO='{$courseno}' AND [GROUP]='{$gp}'");
        $this->model->startTrans();
        //删除schedule中的记录
       $this->model->sqlexecute("DELETE SCHEDULE WHERE RECNO='{$_POST['recno']}'");
         if(trim($_POST['td'][3])=='时间未定'){
             $this->model->commit();
            exit('删除成功');
         }

        //todo:查找老师要上的课程的课程时间
      $teacherarr=$this->model->sqlquery($this->model->getSqlmap('Schedule/delete_queryTEACHERNO.SQL'),array(':year'=>$_POST['year'],':term'=>$_POST['term'],':teacherno'=>$_POST['teacherno']));
        $time=$this->returntime($teacherarr);
      //教师归0
      $tone=$this->model->sqlexecute($this->model->getSqlmap('Schedule/delete_updateTimeList.SQL'),
       array(':mon'=>0,':tue'=>0,':wes'=>0,':thu'=>0,':fri'=>0,':sat'=>0,':sun'=>0,':year'=>$_POST['year'],
       ':term'=>$_POST['term'],':type'=>'T',':who'=>$_POST['teacherno'],':para'=>''));

        //修改教师timelist
      $ttwo=$this->model->sqlexecute($this->model->getSqlmap('Schedule/delete_updateTimeList.SQL'),
       array(':mon'=>$time[1],':tue'=>$time[2],':wes'=>$time[3],':thu'=>$time[4],':fri'=>$time[5],':sat'=>$time[6],':sun'=>$time[7],':year'=>$_POST['year'],
      ':term'=>$_POST['term'],':type'=>'T',':who'=>$_POST['teacherno'],':para'=>''));



        //todo:查找课程的课程时间
        $coursearr=$this->model->sqlquery($this->model->getSqlmap('Schedule/delete_queryCOURSENO.SQL'),
          array(':year'=>$_POST['year'],':term'=>$_POST['term'],':courseno'=>$courseno,':gp'=>$gp));
       $time=$this->returntime($coursearr);
        //课程归0
     $cone=$this->model->sqlexecute($this->model->getSqlmap('Schedule/delete_updateTimeList.SQL'),
     array(':mon'=>0,':tue'=>0,':wes'=>0,':thu'=>0,':fri'=>0,':sat'=>0,':sun'=>0,':year'=>$_POST['year'],
     ':term'=>$_POST['term'],':type'=>'P',':who'=>$courseno,':para'=>$gp));
        //修改课程timelist
      $ctwo=$this->model->sqlexecute($this->model->getSqlmap('Schedule/delete_updateTimeList.SQL'),
        array(':mon'=>$time[1],':tue'=>$time[2],':wes'=>$time[3],':thu'=>$time[4],':fri'=>$time[5],':sat'=>$time[6],':sun'=>$time[7],':year'=>$_POST['year'],
         ':term'=>$_POST['term'],':type'=>'P',':who'=>$courseno,':para'=>$gp));



        //todo:查找教室号的时间
        $roomarr=$this->model->sqlquery($this->model->getSqlmap('Schedule/delete_queryROOMNO.SQL'),
            array(':year'=>$_POST['year'],':term'=>$_POST['term'],':roomno'=>$_POST['td'][5]));
        $time=$this->returntime($roomarr);
      //教室归0
      $rone=$this->model->sqlexecute($this->model->getSqlmap('Schedule/delete_updateTimeList.SQL'),
      array(':mon'=>0,':tue'=>0,':wes'=>0,':thu'=>0,':fri'=>0,':sat'=>0,':sun'=>0,':year'=>$_POST['year'],
      ':term'=>$_POST['term'],':type'=>'R',':who'=>$_POST['td'][5],':para'=>''));
        //修改教室timelist
          $rtwo=$this->model->sqlexecute($this->model->getSqlmap('Schedule/delete_updateTimeList.SQL'),
            array(':mon'=>$time[1],':tue'=>$time[2],':wes'=>$time[3],':thu'=>$time[4],':fri'=>$time[5],':sat'=>$time[6],':sun'=>$time[7],':year'=>$_POST['year'],
               ':term'=>$_POST['term'],':type'=>'R',':who'=>$_POST['td'][5],':para'=>''));


        //todo:查找班级号的时间
        $roomarr=$this->model->sqlquery($this->model->getSqlmap('Schedule/delete_queryCLASSNO.SQL'),
            array(':classno'=>$_POST['classno'],':year'=>$_POST['year'],':term'=>$_POST['term']));
        $time=$this->returntime($roomarr);
      //教室归0
        $ccone=$this->model->sqlexecute($this->model->getSqlmap('Schedule/delete_updateTimeList.SQL'),
         array(':mon'=>0,':tue'=>0,':wes'=>0,':thu'=>0,':fri'=>0,':sat'=>0,':sun'=>0,':year'=>$_POST['year'],
        ':term'=>$_POST['term'],':type'=>'C',':who'=>$_POST['classno'],':para'=>''));
        //修改教室timelist
      $cctwo=$this->model->sqlexecute($this->model->getSqlmap('Schedule/delete_updateTimeList.SQL'),
      array(':mon'=>$time[1],':tue'=>$time[2],':wes'=>$time[3],':thu'=>$time[4],':fri'=>$time[5],':sat'=>$time[6],':sun'=>$time[7],':year'=>$_POST['year'],
     ':term'=>$_POST['term'],':type'=>'C',':who'=>$_POST['classno'],':para'=>''));

        if($cone&&$ctwo&&$tone&&$ttwo&&$rone&&$rtwo&&$ccone&&$cctwo){
           $this->model->commit();
            exit('删除成功');
        }
        var_dump($cone,$ctwo,$tone,$ttwo,$rone,$rtwo,$ccone,$cctwo);
        $this->model->rollback();
    }

    public function returntime($arr){
        $time=array(1=>0,0,0,0,0,0,0);
        foreach($arr as $val){
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



    /*
    *  $bin  这门课程的 TIMEBITS
    *  $time 老师的timelist
     * $OEW  单双周
    */

   private function jisuan($bin,$time,$OEW){
       for($i=0;$i<count($bin);$i++){
         $bin[$i].=$bin[$i];


            //todo:先和单双周&


      }



    }




    public function domanual(){
        $TIMESECTORS = array();
        $data = $this->model->sqlQuery("select * from TIMESECTORS");
        foreach($data as $row){
            $TIMESECTORS[$row["TIMEBITS"]] = $row;
        }


        //todo 检查参数
        if(VarIsIntval("YEAR,TERM")==false || VarIsSet("forcible,lists,times")==false){
            $this->message['type'] = "error";
            $this->message['message'] = "缺少必要的参数，非法提交！";
            $this->__done();
        }

        //todo 非强制制排课时，检查是否有冲突
        if(!$_REQUEST["forcible"]){
            foreach($_REQUEST["lists"] as $row){
                foreach(cwebsSchedule::$conflictItem as $conflict){
                    $no = $row[$conflict]; $week = $row["DAY"]-1;
                    $isTrue = cwebsSchedule::conflict($row["TIMES"], $row["OEW"],  $_REQUEST["times"][$conflict][$no][$week]);
                    if($isTrue) {
                        $this->message['type'] = "error";
                        $this->message['message'] = decbin($row["TIMES"])."上课时间表有冲突，非法提交！";
                        $this->__done();
                        return;
                    }
                }
            }
        }

        //todo 取出所有时段定义
        $TIMESECTORS = array();
        $data = $this->model->sqlQuery("select * from TIMESECTORS");
        foreach($data as $row){
            $TIMESECTORS[$row["TIMEBITS"]] = $row;
        }
        //todo 定义所有所用教室
        $allRooms = array();


        //todo 保存到数据库
        $bindStr = "SR_YEAR,SR_TERM,SR_ROOMNO,IR_YEAR,IR_TERM,IR_ROOMNO,";
        $bindStr .= "SC_YEAR,SC_TERM,SC_CLASSNO,IC_YEAR,IC_TERM,IC_CLASSNO,";
        $bindStr .= "ST_YEAR,ST_TERM,ST_TEACHNO,IT_YEAR,IT_TERM,IT_TEACHNO,";
        $bindStr .= "SP_YEAR,SP_TERM,SP_COURSENO,IP_YEAR,IP_TERM,IP_COURSENO,";
        $bindStr .= "DAY,TIME,ROOMNO,UNIT,OEW,RECNO,";
        $bindStr .= "R_MON,R_TUE,R_WES,R_THU,R_FRI,R_SAT,R_SUN,R_YEAR,R_TERM,R_ROOMNO,";
        $bindStr .= "C_MON,C_TUE,C_WES,C_THU,C_FRI,C_SAT,C_SUN,C_YEAR,C_TERM,C_CLASSNO,";
        $bindStr .= "T_MON,T_TUE,T_WES,T_THU,T_FRI,T_SAT,T_SUN,T_YEAR,T_TERM,T_TEACHNO,";
        $bindStr .= "P_MON,P_TUE,P_WES,P_THU,P_FRI,P_SAT,P_SUN,P_YEAR,P_TERM,P_COURSENO";
        

        foreach($_REQUEST["lists"] as $row){
            $allRooms[] = $row["ROOMNO"];
            $bind = $this->model->getBind($bindStr, $row);
            $bind[":TIME"] = $TIMESECTORS[$row["TIMES"]]["NAME"];
            $this->setBind($bind, "R", "ROOMNO", $row["ROOMNO"]);
            $this->setBind($bind, "C", "CLASSNO", $row["CLASSNO"]);
            $this->setBind($bind, "T", "TEACHNO", $row["TEACHNO"]);
            $this->setBind($bind, "P", "COURSENO", $row["COURSENO"]);

            $this->model->startTrans();


            $data = $this->model->sqlExecute($this->model->getSqlMap("Schedule/domanual.sql"),$bind);
            if($data===false){
                $this->message['type'] = "error";
                $this->message['message'] .= "更新课号[".$row["COURSENO"]."]时发生错误！\\n\\n";
                $this->model->rollback();
            }else $this->model->commit();
        }
        

        if($this->message['type'] == "info"){
            foreach($_REQUEST["times"]["ROOMNO"] as $k=>$row){
                if(!in_array($k,$allRooms)){
                    $bind = $this->model->getBind("MON,TUE,WES,THU,FRI,SAT,SUN,YEAR,TERM,ROOMNO", array(
                        $row[0]["TIMES"],$row[1]["TIMES"],$row[2]["TIMES"],$row[3]["TIMES"],$row[4]["TIMES"],$row[5]["TIMES"],$row[6]["TIMES"],
                        intval($_REQUEST["YEAR"]),intval($_REQUEST["TERM"]),$k
                    ));
                    $sql = "UPDATE TIMELIST set MON=:MON,TUE=:TUE,WES=:WES,THU=:THU,FRI=:FRI,SAT=:SAT,SUN=:SUN where [YEAR]=:YEAR AND TERM=:TERM AND [TYPE]='R' AND WHO=:ROOMNO";
                    $this->model->sqlExecute($sql,$bind);
                }
            }
            $this->message['message'] = "排课信息已成功保存！";
        }
        $this->__done();
    }

    public function room(){
        if(VarIsIntval("YEAR,TERM")==false || VarIsNotEmpty("ROOMNO")==false){
            $this->message["type"] = "error";
            $this->message["message"] = "输入的参数有错误，非法提交数据！";
            $this->__done();
        }

        $bind = $this->model->getBind("ROOMNO",$_REQUEST);
        $room = $this->model->sqlFind("SELECT RTRIM(ROOMNO) as ROOMNO, RTRIM(JSN) as JSN,SEATS,AREA,EQUIPMENT FROM CLASSROOMS WHERE ROOMNO=:ROOMNO", $bind);
        if($room==null){
            $this->message["type"] = "error";
            $this->message["message"] = "没有找到指定的教室！";
            $this->__done();
        }

        $bind = $this->model->getBind("YEAR,TERM,ROOMNO",$_REQUEST);
        $sql = "SELECT RTRIM(RTRIM(WHO)+PARA) AS WHO,CLASSROOMS.JSN AS WHOSNAME,CAST(CLASSROOMS.RESERVED AS INT) AS RESERVED,TIMELIST.TYPE,MON,TUE,WES,THU,FRI,SAT,SUN FROM TIMELIST "
                ."JOIN CLASSROOMS ON RTRIM(TIMELIST.WHO)=RTRIM(CLASSROOMS.ROOMNO) "
                ."WHERE TIMELIST.TYPE='R' AND TIMELIST.YEAR=:YEAR AND TIMELIST.TERM=:TERM AND RTRIM(TIMELIST.WHO)=RTRIM(:ROOMNO)";
        $data = $this->model->sqlQuery($sql, $bind);
        if($data==null){
            $data[0] = array('WHO'=>$room["ROOMNO"],'WHOSNAME'=>$room["JSN"],'RESERVED'=>0);
        }
        $data = $this->formatTimetable($data);
        $data[0]["SEATS"] = $room["SEATS"];
        $data[0]["AREA"] = $room["AREA"];
        $data[0]["ROOMR"] = $room["EQUIPMENT"];

        $this->message["type"] = "info";
        $this->message["room"] = $data[0];
        trace($this->message);
        $this->__done();
        exit;
    }

    public function search(){
        if($this->_hasJson){
            $json = array("total"=>0, "rows"=>array());
            $bind = $this->model->getBind("MON,TUE,WES,THU,FRI,SAT,SUN,ROOMNO,JSN,PRIORITY,EQUIPMENT,AREA,MINSEATS,MAXSEATS,YEAR,TERM",$_REQUEST);
            $bind[":PRIORITY"] = $_REQUEST["SCHOOL"];
            $bind[":JSN"] = "%";
            $bind[":MINSEATS"] = $_REQUEST["ESTIMATE"];
            $bind[":MAXSEATS"] = 999999;
            foreach(cwebsSchedule::$weekMap as $k=>$v){
                if($k) $bind[":".$k] = 0;
            }

            $sql = "
SELECT
			CLASSROOMS.ROOMNO ,
			CLASSROOMS.BUILDING,
			CLASSROOMS.[NO],
			CLASSROOMS.JSN,
			ISNULL(TIMELIST.MON,0) AS MON,
       ISNULL(TIMELIST.TUE,0) AS TUE,
       ISNULL(TIMELIST.WES,0) AS WES,
       ISNULL(TIMELIST.THU,0) AS THU,
       ISNULL(TIMELIST.FRI,0) AS FRI,
       ISNULL(TIMELIST.SAT,0) AS SAT,
       ISNULL(TIMELIST.SUN,0) AS SUN,
       CLASSROOMS.SEATS,
       CLASSROOMS.TESTERS,
       CLASSROOMS.RESERVED

FROM CLASSROOMS
LEFT OUTER JOIN TIMELIST ON CLASSROOMS.ROOMNO=TIMELIST.WHO
WHERE (ISNULL(TIMELIST.MON,0) & :MON=0) AND (ISNULL(TIMELIST.TUE,0) & :TUE=0) AND (ISNULL(TIMELIST.WES,0) & :WES=0)
AND (ISNULL(TIMELIST.THU,0) & :THU=0) AND (ISNULL(TIMELIST.FRI,0) & :FRI=0) AND (ISNULL(TIMELIST.SAT,0) & :SAT=0)
AND (ISNULL(TIMELIST.SUN,0) & :SUN=0)

AND rtrim(CLASSROOMS.ROOMNO) LIKE :ROOMNO
/*AND CLASSROOMS.JSN LIKE :JSN*/
AND CLASSROOMS.PRIORITY LIKE :PRIORITY
AND CLASSROOMS.EQUIPMENT LIKE :EQUIPMENT
AND CLASSROOMS.AREA LIKE :AREA
/*AND CLASSROOMS.SEATS>= :MINSEATS
AND CLASSROOMS.SEATS<=:MAXSEATS
AND TIMELIST.YEAR=:YEAR AND TIMELIST.TERM=:TERM AND TIMELIST.TYPE='R' */

ORDER BY ROOMNO";

            $data = $this->model->sqlQuery($sql, $bind);
            $json["total"] = count($data);

            if($json["total"]>0){
                $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
                $json["rows"] = $this->model->sqlQuery($sql, $bind);
            }
            $this->ajaxReturn($json,"JSON");
            exit;
        }
    }

    private function formatCourseNO($COURSENO){
        $returnVal = @implode("','",array_unique(@explode(",",$COURSENO)));
        return  $returnVal ? "'".$returnVal."'" : "'-1'";
    }
    private function timetable2Bin($timetable){
        return strrev(sprintf("%024s", decbin($timetable)));
    }
    private function formatTimetable($data){

        $week = array(1=>"MON","TUE","WES","THU","FRI","SAT","SUN");
        foreach($data as $i=>$rows){
            foreach($week as $k=>$w){

                $data[$i]["TIMES"][$k] = intval($rows[$w]); //$this->timetable2Bin($rows[$w]);
            }
        }
        return $data;
    }


    private function setBind(&$bind,$type,$whoName,$val){
        $bind[":S".$type."_YEAR"] = $bind[":I".$type."_YEAR"] = $bind[":".$type."_YEAR"] = intval($_REQUEST["YEAR"]);
        $bind[":S".$type."_TERM"] = $bind[":I".$type."_TERM"] = $bind[":".$type."_TERM"] = intval($_REQUEST["TERM"]);
        $bind[":S".$type."_".$whoName] = $bind[":I".$type."_".$whoName] = $bind[":".$type."_".$whoName] =$val;
        $i = 0;

        foreach(cwebsSchedule::$weekMap as $k=>$v){
            if($k) $bind[":".$type."_".$k] = $_REQUEST['times'][$whoName][$val][$i-1]["TIMES"];
            $i++;
        }
    }
}