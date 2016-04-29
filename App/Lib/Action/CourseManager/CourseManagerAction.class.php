<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-12-2
 * Time: 下午3:01
 **/
class CourseManagerAction extends RightAction
{

    private static $teacherno = null;


    private $md;        //存放模型对象
    private $base;      //路径
    /**
     *   学期课表
     *
     **/
    public function __construct(){
        parent::__construct();
        self::$teacherno = $_SESSION['S_USER_INFO']['TEACHERNO'];

        $this->md=new CourseManagerModel();
        $this->base='CourseManager/';
    }

    /**
     * 课程选课情况 页面显示
     */
    public function one_one(){
        //选课查询列表
        if(REQTAG  == 'courseChoicesStatement' ){
            $bind = $_REQUEST['bind'];
            $rst = $this->md->getCourseChoicesStatementTableList($bind,$this->_pageDataIndex,$this->_pageSize);
            $this->ajaxReturn($rst,'JSON');
            exit();
        }
        if(REQTAG == 'exportexcel'){
            $bind = $_REQUEST['bind'];
            $rst = $this->md->getCourseChoicesStatementTableList($bind);
            if(is_string($rst)){
                $this->exitWithReport('获取选课情况信息失败！');
            }
            $rst = $rst['rows'];

            $this->md->initPHPExcel();
            $data['title'] = '课程选课情况';
            //表头
            $fields = "
row_number() over(order by dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENOGROUP)))as row,
dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENOGROUP)) AS kh,
dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENAME)) AS km,
dbo.getOne(VIEWSCHEDULE.CREDITS) AS xf,
dbo.getOne(VIEWSCHEDULE.WEEKHOURS) AS zxs,
dbo.getOne(VIEWSCHEDULE.WEEKEXPEHOURS) AS zsy,
dbo.getOne(RTRIM(VIEWSCHEDULE.COURSETYPE)) AS xk,
dbo.getOne(RTRIM(VIEWSCHEDULE.APPROACH)) AS xk2,
dbo.getOne(RTRIM(VIEWSCHEDULE.EXAMTYPE)) AS kh2,
dbo.getOne(SCHEDULEPLAN.ATTENDENTS) AS yxrs,
dbo.getOne(RTRIM(VIEWSCHEDULE.COURSETYPENAME)) AS kclb,
dbo.getOne(RTRIM(VIEWSCHEDULE.SCHOOLNAME)) AS kkxy,
  dbo.getOne(RTRIM(VIEWSCHEDULE.SCHOOL)) AS SCHOOLNO,
dbo.getOne(RTRIM(VIEWSCHEDULE.CLASSNONAME)) AS bj,
dbo.getOne(RTRIM(VIEWSCHEDULE.TEACHERNONAME)) AS js,
dbo.getOne(RTRIM(VIEWSCHEDULE.REM)) AS bz,
dbo.GROUP_CONCAT_MERGE(RTRIM(VIEWSCHEDULE.DAYNTIME)+'座位数:'+CAST(VIEWSCHEDULE.SEATS AS char),',') AS kcap
";
            $data['head'] = array(
                //默认值如 align type 的设计实例
                'kh' => array( '课号', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'km' => array(0=> '课名','align' => CommonModel::ALI_RIGHT,'width'=>30),
                'xf' => array( '学分', 'align' => CommonModel::ALI_CENTER),
                'zxs' => array( '周学时', 'align' => CommonModel::ALI_CENTER,'width'=>30),
                'zsy' => array( '周实验', 'align' => CommonModel::ALI_CENTER,'color' => array('argb' => '00000000')),
                'xk' => array( '修课', 'align' => CommonModel::ALI_CENTER,'width'=>30),
                'kh2'   => array( '考核', 'align' => CommonModel::ALI_LEFT,'width'=>50),
                'yxrs' => array( '已选人数', 'align' => CommonModel::ALI_CENTER),
                'kclb' => array( '课程类别', 'align' => CommonModel::ALI_CENTER),
                'SCHOOLNO' => array( '开课学部', 'align' => CommonModel::ALI_CENTER),
            );
            $xlsData = $rst;
            //表体
            $data['body'] = $xlsData;
            $this->md->fullyExportExcelFile($data,$data['title']);

            exit;
        }

        if($this->_hasJson){
            $this->md->startTrans();
            foreach($_POST['bind']['obj'] as $key=>$val){
                $panduan=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_one_updateStatus.SQL'),
                    array(':TINGKE_TYPE'=>$val['tkfs'],':REPEAT'=>$val['cx'],':FEE'=>$val['cxf'],':YEAR'=>$_POST['bind'][':YEAR'],':TERM'=>$_POST['bind'][':TERM'],':COURSENO'=>$_POST['bind'][':COURSENO'],':STUDENTNO'=>$val['xh']));
                if(!$panduan){
                    $this->md->rollback();
                    exit('error');
                }
            }
            $this->md->commit();
            exit('成功');
        }

        $this->assign('myschool',$this->md->getTeacher(self::$teacherno));
        $this->assign('coursetype',$this->md->getComboCourseTypeOptions());
        $this->assign('approachcode',$this->md->getComboApproach());
		$this->assign('school',getSchoolList());                //todo:学院
        $this->display();
    }

    private function getTeacherSchoolno($nm){
        $bind = array(':teacherno'=>trim($nm));
        $rst = $this->md->sqlFind('select SCHOOL from TEACHERS WHERE TEACHERNO=:teacherno',$bind);
        return $rst?$rst['SCHOOL']:null;
    }


    //todo:列出所有选课人数超过教室座位数的课程
    public function one_two(){
        $this->display();
    }


    //todo:所有有空的公选课
    public function one_three(){
		$this->assign('schools',getSchoolList());
        $this->display();
    }


    /**
     * 列出少于(条件)的课程
     */
    public function one_four(){
        if(REQTAG === 'attdentslt'){

            exit;
        }
        $this->display();
    }


    public function one_five(){

        $this->xiala('coursetypeoptions','coursetypeoptions');
        $this->display();
    }




    public function one_seven(){
        if(REQTAG ==='getlist'){
            $sql = '
R32DUMP.STUDENTNO as xh,
STUDENTS.NAME AS xm,
R32DUMP.COURSENO+R32DUMP.[GROUP] AS kh,
COURSES.COURSENAME as km,
COURSES.CREDITS as xf,
R32DUMP.REASON as scyy,
CAST(R32DUMP.INPROGRAM AS CHAR) AS xkjhn,
COURSEAPPROACHES.VALUE AS xkfs,STUDENTS.FREE
FROM R32DUMP JOIN COURSES ON R32DUMP.COURSENO=COURSES.COURSENO
JOIN STUDENTS ON R32DUMP.STUDENTNO=STUDENTS.STUDENTNO
JOIN COURSEAPPROACHES ON R32DUMP.COURSETYPE=COURSEAPPROACHES.NAME
WHERE R32DUMP.YEAR=:YEAR
AND R32DUMP.TERM=:TERM
AND R32DUMP.STUDENTNO LIKE :STUDENTNO
AND R32DUMP.COURSENO+R32DUMP.[GROUP] LIKE :COURSENOGROUP';
            exit;
        }
        $this->display();
    }

    //todo:回收站恢复
    public function huifu(){
        if($this->_hasJson){
            foreach($_POST['bind'] as $val){
                $this->md->startTrans();
                //todo:查出学生的信息                                       1
                $zero=$this->md->sqlFind($this->md->getSqlMap($this->base.'One_seven_huifu_one.SQL'),array(':STUDENTNO'=>doWithBindStr($val['xh'])));
                //todo:查出 这个课号 这个学生  在R32DUMP中的信息             2
                $one=$this->md->SqlFind($this->md->getSqlMap($this->base.'One_seven_huifu_two.SQL'),array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':COURSENO'=>$val['kh'],':STUDENTNO'=>$val['xh']));
                //todo:往R32表中插入数据                                    3
                $two=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_seven_huifu_three.SQL'), array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':COURSENO'=>substr($val['kh'],0,7),':GP'=>substr($val['kh'],7),
                    ':STUDENTNO'=>$val['xh'],':INPROGRAM'=>$one['INPROGRAM'],':CONFLICTS'=>$one['CONFLICTS'],':CF'=>$one['CONFIRM'],':APPROACH'=>$one['APPROACH'],':REPEAT'=>$one['REPEAT'],
                    ':FEE'=>$one['FEE'],':COURSETYPE'=>$one['COURSETYPE'],':EXAMTYPE'=>$one['EXAMTYPE'],':SELECTTIME'=>$one['TIME'] ));
                //todo:删除R32DUMP中的数据                                  4
                $three=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_seven_huifu_four.SQL'),array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':COURSENO'=>$val['kh'],':STUDENTNO'=>$val['xh']));

                //todo:统计出最新的人数                                     5
                $four=$this->md->sqlFind("SELECT COUNT(*) AS NUMBER FROM R32 WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']} AND COURSENO+[GROUP]='{$val['kh']}'");

                //todo:设置排课计划表的选课人数                              6
                $five=$this->md->sqlExecute("UPDATE SCHEDULEPLAN SET ATTENDENTS={$four['NUMBER']} WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']} AND COURSENO+[GROUP]='{$_POST['COURSENO']}'");
                if($zero&$one&$two&$three&$four){
                    $this->md->commit();
                    $boo=true;
                }else{
                    dump($zero);
                    dump($one.'=========one');
                    dump($two.'==========two');
                    dump($three.'==========three');
                    dump($four.'=========four');
                    dump($five.'==========five');
                    $this->md->rollback();
                    exit('失败');
                }

            }
            if($boo){
                exit('成功');
            }

        }
    }

    //todo:选课人数+新生预计人数大于教室座位数的课程
    public function one_eight(){

        $this->display();
    }


    /**
     * 将课程从开课计划删除
     */
    public function delete_course(){
        //获取用户信息 和 班级信息，判断是否有权限修改
        $userinfo = $this->md->getTeacherInfo();
        if(is_string($userinfo) or !$userinfo){
            $this->exitWithReport("获取用户名为[{$_SESSION['S_USER_NAME']}]的用户信息失败！".var_export($userinfo,true));
        }
        $classno = &$_POST['CLASSNO'];
        $classinfo = $this->md->getClass($classno);
        if(is_string($classinfo) or !$classinfo){
            $this->exitWithReport("获取班级号为[{$_POST['CLASS']}]的班级信息失败！{$classinfo}");
        }
        $classinfo = $classinfo[0];
        if($userinfo['SCHOOL'] != $classinfo['SCHOOL']  &&  !isDeanByUsername(getUsername())){
            $this->exitWithReport('您不能删除其他学部的课程!');
        }

        $courseno = substr($_POST['COURSENO'],0,7);
        $groupno  = substr($_POST['COURSENO'],7);
        $year = &$_POST['YEAR'];
        $term = &$_POST['TERM'];

        $this->md->startTrans();
        $cond = array(
            'YEAR' => $year,
            'TERM' => $term,
            'COURSENO' => $courseno,
            'GROUP' => array($groupno,true),
        );

        $courseplanModel = new CoursePlanModel();
        $rst = $courseplanModel->removeCoursePlan(array_merge($cond,array('CLASSNO' => $classno)));
        if(is_string($rst) or !$rst){
            $this->exitWithReport("删除第[$year]学年第[$term]学期课号组号为[$courseno - $groupno]，班级号为[$classno]的开课计划失败!{$rst}");
        }

        $scheduleModel = new ScheduleModel();
        $rst = $scheduleModel->deleteSchedule($cond);
        if(is_string($rst)){
            $this->exitWithReport("删除第[$year]学年第[$term]学期课号组号为[$courseno - $groupno]排课计划失败!s{$rst}");
        }

        $courseManagerModel = new CourseManagerModel();
        $rst = $courseManagerModel->deleteStduentCourse($cond);
        if(is_string($rst)){
            $this->exitWithReport("删除第[$year]学年第[$term]学期课号组号为[$courseno - $groupno]全部学生的修课计划失败!{$rst}");
        }

        $schedulePlanModel = new SchedulePlanModel();
        $schedulePlanModel->deleteSchedulePlan($cond);
        if(is_string($rst)){
            $this->exitWithReport("删除第[$year]学年第[$term]学期课号组号为[$courseno - $groupno]排课计划计划失败!sp{$rst}");
        }

        $this->md->commit();
        $this->exitWithReport("删除第[$year]学年第[$term]学期课号组号为[$courseno - $groupno]，班级号为[$classno]的开课计划成功!",'info');
        exit;
    }

    /**
     * 课程选课情况 学生选课列表
     */
    public function StudentList(){
        if(REQTAG === 'getlist'){
            $json = $this->md->getCourseStudentTableList($_POST['coursegroupno'],$_POST['year'],$_POST['term']);
            exit(json_encode($json));
        }
        $this->assign('myschool',$this->md->getTeacher(self::$teacherno));
        $this->assign('approachcode',$this->md->getComboApproach());
        $this->assign('shuju',$_GET);
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->display();
    }

    /**
     * 删除选课学生的方法
     */
    public function delete_student(){
        $this->md->startTrans();
      //  ini_set("display_errors","On");
        //todo:这个是什么  设置confirm =1
        $four=$this->md->sqlExecute("UPDATE R32 SET [CONFIRM]=1 WHERE YEAR={$_POST['bind'][':YEAR']} AND TERM={$_POST['bind'][':TERM']} AND COURSENO+[GROUP]='{$_POST['bind'][':COURSENO']}'");

        $courseno=substr($_POST['bind'][':COURSENO'],0,7);
        $gp=substr($_POST['bind'][':COURSENO'],7);

        foreach($_POST['bind']['obj'] as $v){
            $val=array();
            $val['xh']=trim($v);

            $one=$this->md->sqlFind("select  b.SELECTTIME  as TIME,b.* from(SELECT * FROM R32 WHERE YEAR={$_POST['bind'][':YEAR']} AND TERM={$_POST['bind'][':TERM']} AND COURSENO+[GROUP]='{$_POST['bind'][':COURSENO']}' AND STUDENTNO='{$val['xh']}')as b");

            //todo:往RE2 DUMP 插入数据
            $bind =  array(':YEAR'=>$_POST['bind'][':YEAR'],':TERM'=>$_POST['bind'][':TERM'],':COURSENO'=>$courseno,':GP'=>$gp,
                ':STUDENTNO'=>$val['xh'],':INPROGRAM'=>$one['INPROGRAM'],':CONFLICTS'=>$one['CONFLICTS'],':CF'=>$one['CONFIRM'],':APPROACH'=>$one['APPROACH'],':REPEAT'=>$one['REPEAT'],
                ':FEE'=>$one['FEE'],':COURSETYPE'=>$one['COURSETYPE'],':EXAMTYPE'=>$one['EXAMTYPE'],':SELECTTIME'=>$one['TIME']
                );
            $two=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_one_deleteStudent_two.SQL'),$bind);

            //todo:删除R32的数据
            $three=$this->md->sqlExecute("DELETE R32 WHERE YEAR={$_POST['bind'][':YEAR']} AND TERM={$_POST['bind'][':TERM']} AND COURSENO+[GROUP]='{$_POST['bind'][':COURSENO']}' AND STUDENTNO='{$val['xh']}'");

            //todo:统计出最新的人数
            $five=$this->md->sqlFind("SELECT COUNT(*) AS NUMBER FROM R32 WHERE YEAR={$_POST['bind'][':YEAR']} AND TERM={$_POST['bind'][':TERM']} AND COURSENO+[GROUP]='{$_POST['bind'][':COURSENO']}'");

            //todo:设置排课计划表的选课人数
            $six=$this->md->sqlExecute("UPDATE SCHEDULEPLAN SET ATTENDENTS={$five['NUMBER']} WHERE YEAR={$_POST['bind'][':YEAR']} AND TERM={$_POST['bind'][':TERM']} AND COURSENO+[GROUP]='{$_POST['bind'][':COURSENO']}'");

            if(!($one&&$two&&$three&&$four&&$five&&$six)){
                $this->md->rollback();
                exit($val['xh'].'删除失败'."$one $two $three $four $five $six".$this->md->getDbError());
            }else{
                $this->md->commit();
            }
        }

        exit('删除成功');
    }


    //todo:删除 one_six的内容的方法
    public function delete_six(){

        foreach($_POST['bind'] as $val){
            $this->md->startTrans();
            //todo:查询R32的数据
            $one=$this->md->sqlFind("select CONVERT(varchar(10),b.SELECTTIME,20) as TIME,b.* from(SELECT * FROM R32 WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']} AND COURSENO+[GROUP]='{$val['kh']}' AND STUDENTNO='{$val['xh']}')as b");

            //todo:插入到 R32DUMP
            $two=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_six_One_insertR32dump.SQL')
                ,array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':COURSENO'=>substr($val['kh'],0,7),':GP'=>substr($val['kh'],7),':STUDENTNO'=>$val['xh'],
                ':INPROGRAM'=>$one['INPROGRAM'],':CONFLICTS'=>$one['CONFLICTS'],':CF'=>$one['CONFIRM'],':APPROACH'=>$one['APPROACH'],':REPEAT'=>$one['REPEAT'],
                ':FEE'=>$one['FEE'],':COURSETYPE'=>$one['COURSETYPE'],':EXAMTYPE'=>$one['EXAMTYPE'],':SELECTTIME'=>$one['TIME'],':REASON'=>'违反选课规则，过多选择此类课程！'));
            //todo:删除R32
            $three=$this->md->sqlExecute("DELETE R32 WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']} AND COURSENO+[GROUP]='{$val['kh']}' AND STUDENTNO='{$val['xh']}'");
            //todo:查询出多少人
            $four=$this->md->sqlFind("SELECT COUNT(*) AS NUMBER FROM R32 WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']} AND COURSENO+[GROUP]='{$val['kh']}'");

            //todo:设置排课计划表的选课人数
            $six=$this->md->sqlExecute("UPDATE SCHEDULEPLAN SET ATTENDENTS={$four['NUMBER']} WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']} AND COURSENO+[GROUP]='{$val['kh']}'");
            if($one&&$two&$three&&$four&&$six){
                $this->md->commit();

            }else{
                dump($one);
                dump($two);
                dump($three);
                dump($four);

                dump($six);
                $this->md->rollback();
                exit('失败');
            }

        }
    }

    public function one_six(){
        $this->display();
    }


    /**
     * 同步课程总表
     */
    public function tongbu(){
        $year = $_POST['YEAR'];
        $term = $_POST['TERM'];
        $exclusion =true;
        $type = NULL;
        if(REQTAG === 'assocSyn'){
            //点击同步社团课时仅将社团课同步
            $type = 'I';
        }else{
            //同步社团课除外的课程
            $type = 'I';
            $exclusion = false;
        }

        $this->md->startTrans();

        //更新排课计划表实际人数
        $usa = $this->md->updateScheduleAttendents($year,$term,$type,$exclusion);
        if(is_string($usa)){
            $this->exitWithReport('更新排课计划人数失败了！'.$usa);
        }
        //清空旧的排课计划(VIEWSCHEDULETABLE)
        $cvs = $this->md->clearViewScheduleTable($year,$term,$type,$exclusion);
        if(is_string($cvs)){
            $this->exitWithReport('清除失败！'.$cvs);
        }
        //重新加入指定类型的排课计划
        $ivs = $this->md->insertViewScheduleTable($year,$term,$type,$exclusion);
        if(is_string($ivs)){
            $this->exitWithReport('插入失败！'.$ivs);
        }

        $this->md->commit();
        $this->exitWithReport("此次同步共更新了[{$usa}]条排课计划记录，删除了[$cvs]条旧的数据，插入了[{$ivs}]条数据!",'info');
    }

    /**
     * 同步课程
     */
    public function tb_suzhi(){
        if(REQTAG === 'synchronize'){
            //':ID':'EXE','exe':'CourseManager/Three_five_tongbu.SQL'
            //放入全体学生名单
            $inst = $this->md->insertIntoMakingCredit();
            if(is_string($inst)){
                $this->exitWithReport('Insert making credit falied!'.$inst);
            }

            $updt = $this->md->updateMakingCredit();
            if(is_string($updt)){
                $this->exitWithReport('Update making credit addttion falied!'.$inst);
            }

            $updt2 = $this->md->updateMakingCreditCourse();
            if(is_string($updt2)){
                $this->exitWithReport('Update making credit addttion falied!'.$updt2);
            }

            $this->exitWithReport("完成同步，此次共插入了[{$inst}]条数据,更新了[{$updt}]条创新技能素质学分部分，更新了[{$updt2}]条素质类课程部分",'info');
        }
        $this->display();
    }

    
    public function addStudent_add(){
        //数据模型雷源
        $courseManagerModel = $this->md;

        //获取课和组号
        $courseno=substr($_POST['COURSENO'],0,7);
        $groupno=substr($_POST['COURSENO'],7);
        $year = $_POST['YEAR'];
        $term = $_POST['TERM'];


        $str='';
        $panduan=true;

        //排课计划表中检查，人数是否超出限制
        $sp = $courseManagerModel->getSchedulePlan($year,$term,$courseno,$groupno);
        if (is_string($sp)){
            $this->exitWithReport('查询排课计划的过程出现错误'.$sp);
        }elseif(empty($sp)){
            $this->exitWithReport("无法依据参数：$year,$term,$courseno,$groupno 获取排课计划表中的记录");
        }else{
            /**
             * 管理员可以不受人数限制
             */
//            if($sp['ATTENDENTS'] >= $sp['ESTIMATE']){
//                $this->exitWithReport("课程号为[{$courseno}]的选课人数已超过人数限制！");
//            }
        }

        foreach($_POST['bind'] as $val){
            $studentno=trim($val['xh']);//学号
            if(empty($studentno)){
                $this->exitWithReport('无法从请求中获取学号信息，可能是浏览器兼容原因，请尝试Chrome浏览器');
            }

            $this->md->startTrans();

            //查询该课程的对应的开课计划
            $ct = $courseManagerModel->getCoursePlan($year,$term,$courseno,$groupno);
            if(is_string($ct)){
                $this->exitWithReport('查询该课程的开课计划失败了！'.$ct);
            }elseif(!$ct){
                $this->exitWithReport('无法查询到开课计划的记录.');
            }
//
//            $timelistModel = new TimelistModel();
//
//            //课程的TIMELIST
//            $ctl = $courseManagerModel->getCourseTimelist($year,$term,trim($courseno).trim($groupno),null);
//            if(is_string($ctl)){
//                $this->exitWithReport('查询该课程的时间表失败，错误信息：'.var_export($ctl));
//            }elseif(!$ctl){
////                $this->exitWithReport("未查询到该课程组号为{$courseno},{$groupno}的课程时间记录!");
//                //课程时间不存在则创建
//                $rst = $timelistModel->createTimelistRecord(array(
//                    'YEAR' => $_POST['YEAR'],
//                    'TERM' => $_POST['TERM'],
//                    'WHO' => $courseno,
//                    'TYPE' => 'P',
//                    'PARA'=>$groupno,
//                    'MON'=>0,
//                    'TUE'=>0,
//                    'WES'=>0,
//                    'THU'=>0,
//                    'FRI'=>0,
//                    'SAT'=>0,
//                    'SUN'=>0,
//                ));
//                if(is_string($rst) or !$rst){
//                    $this->exitWithReport("检测到改学生的周时间安排记录为空，尝试创建课程组号为{$courseno},{$groupno}的课程时间记录失败！$rst");
//                }
//            }


            //查询学生号存在不存在
            $std = $courseManagerModel->getStudents($studentno,false);
            if(!is_array($std)){
                $this->exitWithReport('查询学生的过程中出现错误，错误信息：'.var_export($std));
            }elseif(!$std){
                $this->exitWithReport("未查询到学生号为[{$studentno}]的时间表,请输入完整的学生号！");
            }

//
//            //找出学生的TIMELIST
//            $stl = $courseManagerModel->getStudentTimelist($year,$term,$studentno);
//            if(!is_array($stl)){
//                $this->exitWithReport('查询该学生的时间表失败，错误信息：'.var_export($stl));
//            }elseif(!$stl){
//                //学生的时间记录记录可能不存在,则创建
////                $this->exitWithReport("未查询到学生号为{$courseno},{$groupno}的时间表");
//                $rst = $timelistModel->createTimelistRecord(array(
//                    'YEAR' => $_POST['YEAR'],
//                    'TERM' => $_POST['TERM'],
//                    'WHO' => $studentno,
//                    'TYPE' => 'S',
//                    'PARA'=>'',
//                    'MON'=>0,
//                    'TUE'=>0,
//                    'WES'=>0,
//                    'THU'=>0,
//                    'FRI'=>0,
//                    'SAT'=>0,
//                    'SUN'=>0,
//                ));
//                if(is_string($rst) or !$rst){
//                    $this->exitWithReport('检测到改学生的周时间安排记录为空，尝试创建失败！'.$rst);
//                }
//            }
//
//            $Timearr=array();
//            foreach($ctl as $key=>$v){//遍历所有的课程时间列表
//                if($v&$stl[$key]){
//                    $str .= "{$studentno}所选的课程：{$courseno}{$groupno}和他已选的课程上课时间有冲突，建议他进入退选程序，适当退选课程或者办理相关免听手续";
//                }
//                $Timearr[$key] = $v | $ctl[$key];
//            }

//            $seven=$this->md->sqlFind("SELECT COURSENO,COURSETYPE,EXAMTYPE FROM VIEWSTUDENTPLANCOURSE WHERE STUDENTNO='{$std['STUDENTNO']}' AND COURSENO LIKE '{$courseno}%'");
//            if(!$seven){
//                exit( '该学生不属于该教学计划!<br />');
//            }
            $courseplanModel = new CoursePlanModel();
            $coursetype = $courseplanModel->getCoursePlanMessage($_POST['YEAR'],$_POST['TERM'],$courseno,$groupno);
            if(is_string($coursetype)){
                $this->exitWithReport($coursetype);
            }

            $courseManagerModel = new CourseManagerModel();
            $rst = $courseManagerModel->createStudentCourseRecord(array(
                'YEAR' => $_POST['YEAR'],
                'TERM' => $_POST['TERM'],
                'COURSENO'=>$courseno,
                'GROUP'=>array($groupno,true),
                'STUDENTNO'=>$studentno,
                'INPROGRAM'=>0,
                'CONFLICTS'=>0,
                'CONFIRM'=>0,
                'APPROACH' => $coursetype['COURSETYPE'],
                'REPEAT'=>0,
                'FEE'=>1,
                'COURSETYPE'=>$coursetype['course_type_options'],
                'EXAMTYPE'=>$coursetype['EXAMTYPE'],
            ));
//            $bind1 = array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':COURSENO'=>$courseno,':GP'=>$group,':STUDENTNO'=>$std['STUDENTNO'],':INPROGRAM'=>0,':CONFLICTS'=>0,':REPEAT'=>0,
//                ':FEE'=>1,':COURSETYPE'=>$seven['COURSETYPE'],':EXAMTYPE'=);
//            $eight=$this->md->sqlExecute($this->md->getSqlMap($this->base."One_one_addStudent_insertR32.SQL"),$bind1 );
            if(is_string($rst) or !$rst){
                $this->exitWithReport('创建学生选课记录失败！'.$rst);
            }


            $nine=$this->md->sqlExecute("UPDATE SCHEDULEPLAN SET ATTENDENTS=ATTENDENTS+1 WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']} AND COURSENO='$courseno' AND [GROUP]='$groupno'");

//            $ten=$this->md->sqlExecute("DELETE TIMELIST WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']} AND WHO='{$std['STUDENTNO']}' AND TYPE='S'");
//
//            $rst = $timelistModel->updateTimelistRecord(array(
//                'MON'=>$this->nullToZero($Timearr['MON']),
//                'TUE'=>$this->nullToZero($Timearr['TUE']),
//                'WES'=>$this->nullToZero($Timearr['WES']),
//                'THU'=>$this->nullToZero($Timearr['THU']),
//                'FRI'=>$this->nullToZero($Timearr['FRI']),
//                'SAT'=>$this->nullToZero($Timearr['SAT']),
//                'SUN'=>$this->nullToZero($Timearr['SUN']),),array(
//                'YEAR' => $_POST['YEAR'],
//                'TERM' => $_POST['TERM'],
//                'WHO' =>$studentno,
//                'TYPE' => 'S',));
//            $rst = $timelistModel->createTimelistRecord(array(
//                'PARA'=>'',
//            ));
            if(is_string($rst) or !$rst){
                $this->exitWithReport('重新创建学生的时间安排记录失败'.$rst);
            }

            if(!$nine){
                $str .= '更新选课人数操作失败!<br />';
            }

            if($nine){
                $this->md->commit();
                continue;
            }

            $panduan=false;
            $this->md->rollback();
        }
        if($panduan){
            $this->exitWithReport('学生选课添加成功,请刷新页面','info');
        }

        if(trim($str) == ''){
            $str = '学生选课添加失败！';
        }

        $this->exitWithReport($str);
        }

	private function nullToZero($param){
		return isset($param)?$param:0;
	}

    /**
     * 选课终止
     */
    public function Two_ElectiveStop(){
        if(REQTAG === 'forbidCourse'){
            $schedulePlan = new SchedulePlanModel();
            $rst = $schedulePlan->gateSelectCourse($_POST['YEAR'],$_POST['TERM'],$_POST['COURSENO']);
            if(is_string($rst) or !$rst){
                $this->exitWithReport('终止选课的过程中出现错误！'.$rst);
            }else{
                $this->exitWithReport("成功终止了[$rst]门课程的选课开放！",'info');
            }
        }
        $this->display();
    }

    /**
     * 选课开放
     */
    public function Three_ElectiveStart(){

        if(NULL !== REQTAG){//选课开放
            $schedulePlan = new SchedulePlanModel();
            $rst = null;

            if(REQTAG === 'openCourse'){
                //开放特定课程
                $rst = $schedulePlan->gateSelectCourse($_POST['YEAR'],$_POST['TERM'],$_POST['COURSENO'],0);
            }elseif(REQTAG === 'openAllCourse'){
                //开放全部课程
                $rst = $schedulePlan->gateSelectCourse($_POST['YEAR'],$_POST['TERM'],'%',0);
            }

            if(is_string($rst) or !$rst){
                $this->exitWithReport('开放课程的过程中出现了错误！'.$rst);
            }else{
                $this->exitWithReport("成功开放了[$rst]门课程的选课！",'info');
            }
        }



        if($this->_hasJson){
            $rst= $this->md->sqlExecute("UPDATE SCHEDULEPLAN SET HALFLOCK=0 WHERE YEAR=:year AND TERM=:term and lock = 1;",array(':year'=>$_POST['YEAR'],':term'=>$_POST['TERM']));
            exit($rst?'success':'failure');
        }
        $this->assign('quanxian',trim($_SESSION['S_USER_INFO']['ROLES']));
        $this->display();
    }





    //todo:选课初始化
    public function Three_chushihua(){
        //todo:删除R32的数据
        $bool=$this->md->sqlExecute("DELETE R32 WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']}");
        //todo:修改SCHEDULEPLAN的状态
        $bool2=$this->md->sqlExecute("UPDATE SCHEDULEPLAN SET ATTENDENTS=0 WHERE YEAR={$_POST['YEAR']} AND TERM={$_POST['TERM']}");

        dump($bool);
        dump($bool2);
    }













    public function getTime($arr){
        $ar2=array();
        foreach($arr as $val){
            $ar2[$val['NAME']]=$val;
        }
        return $ar2;
    }

    //todo:选课统计
    public function Five_count(){
		$this->assign('school',getSchoolList());
        $this->display();
    }


    //todo:添加学生的列表
    public function liebiao(){

     //   'CourseManager/One_one_title_Info.SQL','bind':{':YEAR':year,':TERM':term,':COURSENO':COURSENO}
       $countent=$this->md->Sqlfind('
SELECT COURSEPLAN.COURSENO+COURSEPLAN.[GROUP] AS COURSENOGROUP,
COURSES.COURSENAME,
COURSES.CREDITS,
SCHOOLS.NAME AS SCHOOLNAME,
SCHOOLS.SCHOOL,
EXAMOPTIONS.VALUE AS EXAM,
SCHEDULEPLAN.ESTIMATE AS ESTIMATE,
COURSES.SYLLABUS AS SYLLABUS
FROM COURSEPLAN INNER JOIN COURSES ON COURSEPLAN.COURSENO=COURSES.COURSENO
INNER JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL
INNER JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME
JOIN SCHEDULEPLAN ON COURSEPLAN.YEAR=SCHEDULEPLAN.YEAR AND COURSEPLAN.TERM=SCHEDULEPLAN.TERM
AND COURSEPLAN.COURSENO=SCHEDULEPLAN.COURSENO AND COURSEPLAN.[GROUP]=SCHEDULEPLAN.[GROUP]
WHERE COURSEPLAN.YEAR=:YEAR
AND COURSEPLAN.TERM=:TERM
AND COURSEPLAN.COURSENO+COURSEPLAN.[GROUP]=:COURSENO',
           array(':YEAR'=>$_GET['year'],':TERM'=>$_GET['term'],':COURSENO'=>$_GET['courseno']));
        $this->assign('info',$countent);
        $this->assign('year',$_GET['year']);
        $this->assign('term',$_GET['term']);
        $this->assign('coursetype',$_GET['coursetype']);
        $this->assign('courseno',$_GET['courseno']);
        $this->display();
    }

    public function liebiao2(){
        $classno=$_GET['classno']?$_GET['classno']:'';
        $countent=$this->md->Sqlfind($this->md->getSqlMap('CourseManager/One_one_title_Info.SQL'),array(':YEAR'=>$_GET['year'],':TERM'=>$_GET['term'],':COURSENO'=>$_GET['courseno']));
        $this->assign('info',$countent);
        $this->assign('year',$_GET['year']);
        $this->assign('term',$_GET['term']);
        $this->assign('coursetype',$_GET['coursetype']);
        $this->assign('courseno',$_GET['courseno']);

        $this->assign('classno',$classno);
        $this->display();

    }

    /**
     * 给学生选必修课
     * @param $year
     * @param $term
     */
    public function bixiu($year,$term){
        //同步前检测是否会有重复键插入到R32中
        //注：R32表复合主键是(YEAR,TERM,COURSENO,STUDNETNO) 可能有学生会选择同一门课程的不同组号
        $rst = $this->md->checkStudentCoursePlanUnRepeat(null,array('M'),$year,$term);
        if(is_string($rst)){
            $this->exitWithReport('检测学生是否重复选择同一门课程出现错误！');
        }elseif($rst){//存在重复的课程
            $this->exitWithReport($rst);
        }

        $this->md->startTrans();

        $crst = $this->md->clearOldStudentMustedCourse($year,$term);
        if(is_string($crst)){
            $this->exitWithReport('清空旧的必修课数据失败!'.$rst);
        }

        $rst = $this->md->synchStudentCourseByClassno(null,array('M'),$year,$term);
        if(is_string($rst)){
            $this->exitWithReport('同步学生的必修课和模块课失败了!'.$rst);
        }

        //更新人数列表
        $rst1 = $this->md->updateSchedulePlanAttendentsByYearTerm($year,$term);
        if(is_string($rst1)){
            $this->exitWithReport('更新排课计划表出错!'.$rst1);
        }

        $this->md->commit();
        $this->exitWithReport("同步结束，此次共清空[$crst]条旧的必修课记录，并且同步[$rst]条数据，共更新{$rst1}条排课计划的人数！",'info');
    }
}

//todo:一天有几节课
function countOneDay($v1, $v2){
    if(!$v1) $v1 = array();
    if($v2['UNIT']=="1") $v1[]=$v2["NAME"];
    return $v1;
}

