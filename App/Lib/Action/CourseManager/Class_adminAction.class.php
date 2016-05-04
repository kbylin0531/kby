<?php

//todo:班级选课管理
class Class_adminAction extends RightAction
{
    private $md;
    /**
     * @var CourseSelectionAction
     */
    private $commonAction;

    private $base;
    /**
     *   学期课表
     *
     **/
    public function __construct(){
        parent::__construct();
        $this->md = new CourseManagerModel();
        $this->commonAction = A('CourseManager/CourseSelection');
        $this->base='CourseManager/';
    }
    /**
     * 显示教师端的学生个人主页
     */
    public function My_student_Page(){
        $this->assign('year',$_REQUEST);

        if(empty($_GET['YEAR'])){
            $data = $this->md->getYearTerm('C');
            $this->assign("yearTerm",$data);
        }ELSE{
            $this->assign('yearTerm',array('YEAR'=>$_GET['YEAR'],'TERM'=>$_GET['TERM']));
        }
        $userinfo=$this->md->sqlFind("SELECT RTRIM(STUDENTS.NAME) AS name, STUDENTS.FREE, STUDENTS.CLASSNO,CLASSES.CLASSNAME FROM STUDENTS INNER JOIN CLASSES ON STUDENTS.CLASSNO = CLASSES.CLASSNO where studentno='{$_GET['username']}'");
        session('student_info',$userinfo);
        session('x_username',$userinfo['name']);
        session('x_classno',$userinfo['CLASSNO']);
        session('x_classname',$userinfo['CLASSNAME']);
        session('studnet_studentno',$_GET['username']);
        $this->assign('studentno',$_GET['username']);
        $this->display();
    }

    /**
     * 已选课程（退课）
     */
    public function removeList(){
        $this->assign('query_url','CourseManager/Class_admin/removeList/hasJson/true');
        $this->assign('remove_url','CourseManager/Class_admin/remove/hasJson/true');//remove☞地址
        $this->commonAction->studentCourseSelectionList(session('studnet_studentno'));
    }
    /**
     * [ACT]退课操作
     */
    public function remove(){
        $this->commonAction->removeStudentCourse(session("studnet_studentno"));
    }

    /**
     * 普通类、通识课、社团课查询界面选课查询界面
     */
    public function xuanke(){
        $this->assign('qlist_url','CourseManager/Class_admin/qlist');//数据查询地址

        if(isset($_GET['tag'])){
            if($_GET['tag'] == 'assoc'){
                $this->assign('query_url','CourseManager/Class_admin/xuanke/tag/assoc');//刷新显示界面
                $this->commonAction->queryPage(session('studnet_studentno'),'I');
            }elseif($_GET['tag'] == 'general'){
                $this->assign('query_url','CourseManager/Class_admin/xuanke/tag/general');
                $this->commonAction->queryPage(session('studnet_studentno'),'J');
            }
        }ELSE{
            $this->assign('query_url','CourseManager/Class_admin/xuanke/');
            $this->commonAction->queryPage(session('studnet_studentno'));
        }
    }
    /**
     * 选课列表显示界面
     */
    public function qlist(){
        $this->assign('qlist_url','CourseManager/Class_admin/qlist/hasJson/true');
        $this->assign('save_url','CourseManager/Class_admin/selected/hasJson/true');
        $this->commonAction->queryData(session('studnet_studentno'));
    }
    /**
     * 学生端界面确认选课操作
     */
    public function selected(){
        $this->commonAction->selectCourseForStudent(session('studnet_studentno'));
    }




    /******************* 选课模块集中 分隔线 *************************************/

























    public function  Four_classcourse(){
        if($this->_hasJson){
            $data_List=array();
            $credit=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Four_four_studentANDcredit.SQL'),array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':CLASSNO'=>$_POST['CLASSNO']));
            //          $course=$this->md->sqlQuery($this->md->getSqlMap($this->base.'One_Four_selectCourse.SQL'),array(':CLASSNO'=>$_POST['CLASSNO'],':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM']));
            $student=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Four_four_studentANDcourse.SQL'),array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':CLASSNO'=>$_POST['CLASSNO']));

            $inum=count($credit);
            $jnum=count($student);
            for($i=0;$i<$inum;$i++){
                $data_List[$i]=$credit[$i];
                for($j=0;$j<$jnum;$j++){
                    if($credit[$i]['xh']!=$student[$j]['STUDENTNO']){
                        continue;
                    }
                    $data_List[$i]['a'.strtoupper($student[$j]['COURSENOGROUP'])]=$student[$j]['COURSETYPE'];
                }
            }
            $arr['total']=count($data_List);
            $arr['rows']=$data_List;

            echo json_encode($arr);
            exit;
        }

        $this->display();

    }





    /**
     * 查看学生选课
     */
    public function one(){
        if(REQTAG === 'exportexcel') {
            $bind = array(//注意顺序
                ':year' => $_GET['YEAR'],
                ':term' => $_GET['TERM'],
                ':studentno' => doWithBindStr($_GET['STUDENTNO']),
                ':classno' => doWithBindStr($_GET['CLASSNO']),
            );
            $rst = $this->md->getStudentCourseChoosenExcelList($bind);
            if (is_string($rst)) {
                $this->exitWithReport('falied!');
            }
//vardump($bind,$rst);
            $this->md->initPHPExcel();
            $data['title'] = '班级学生选课情况';
            //表头
            $data['head'] = array(
                //默认值如 align type 的设计实例
                'xh' => array( '学号', 'align' => CommonModel::ALI_LEFT,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'xm' => array(0=> '姓名','align' => CommonModel::ALI_LEFT,'width'=>30),
                'xsbj' => array( '班级', 'align' => CommonModel::ALI_LEFT),
                'zxf' => array( '总学分', 'align' => CommonModel::ALI_CENTER,'width'=>30),
                'tscoursename' => array( '通识课', 'align' => CommonModel::ALI_LEFT,'width'=>30),
                'stcoursename' => array( '社团课', 'align' => CommonModel::ALI_LEFT,'width'=>30),
                'commoncoursenames'   => array( '普通课', 'align' => CommonModel::ALI_LEFT,'width'=>100),
                'xkms'   => array( '课程总数', 'align' => CommonModel::ALI_CENTER,'width'=>20),
            );
            $xlsData = $rst;
            //表体
            $data['body'] = $xlsData;
            $this->md->fullyExportExcelFile($data, $data['title']);
            exit;
        }
        if(REQTAG === 'getlist'){
            $bind = array(
                ':studentno' => doWithBindStr($_POST['STUDENTNO']),
                ':classno'   => doWithBindStr($_POST['CLASSNO']),
                ':year'      => $_POST['YEAR'],
                ':term'      => $_POST['TERM'],
            );
            $rst = $this->md->getStudentCourseChoosenTableList($bind,$this->_pageDataIndex,$this->_pageSize);
            $this->ajaxReturn($rst,'JSON');
            exit();
        }
        $this->display();
    }

    /**
     * 学生选课检测
     * 权限添加，INSERT INTO METHODS  VALUES ('CCSC', '*', '学生选课检测', 'CourseManager/Class_admin/checkStudentCourse');
     * @param int $YEAR
     * @param int $TERM
     * @param string $STUDENTNO
     * @param string $CLASSNO
     */
    public function checkStudentCourse($YEAR=null,$TERM=null,$STUDENTNO='%',$CLASSNO='%'){
        if(REQTAG === 'getlist'){
            $rst = $this->md->getStudentCoursesTableList($YEAR,$TERM,$STUDENTNO,$CLASSNO,$this->_pageDataIndex,$this->_pageSize);
            $this->ajaxReturn($rst,'JSON');
            exit;
        }
        $this->display('checkStudentCourse');
    }

    /**
     * 班级统一必修课
     */
    public function Four_two(){
        $this->display();
    }





    public function getTime($arr){
        $ar2=array();
        foreach($arr as $val){
            $ar2[$val['NAME']]=$val;
        }
        return $ar2;
    }

    public function Five_count(){
        $this->assign('schools',getSchoolList());
        $this->display();
    }


    public function Class_week_course(){

        $array=array();
        $OEW = array("B"=>"","E"=>"（双周）","O"=>"（单周）");
        $arr=$this->md->sqlQuery($this->md->getSqlMap('CourseManager/Four_five_ClassCourse.SQL'),array(':YEAR'=>$_GET['year'],':TERM'=>$_GET['term'],':CLASSNO'=>$_GET['CLASSNO']));

        //todo:查询标题名称
        $title_CLASSNAME=$this->md->sqlFind("SELECT RTRIM(CLASSNAME) AS CLASSNAME FROM CLASSES WHERE RTRIM(CLASSNO)='{$_GET['CLASSNO']}'");



        $jieci=$this->md->sqlQuery("select * from TIMESECTORS");//todo:节次数组
        $jieci=$this->getTime($jieci);
        $countOnejieci=array_reduce($jieci, function($v1, $v2){
                if(!$v1) $v1 = array();
                if($v2['UNIT']=="1") $v1[]=$v2["NAME"];
                return $v1;
            });
        foreach($arr as $key=>$val){
            if($val['WEEKS']!=262143){
                $weeks='周次'.str_pad(strrev(decbin($val['WEEKS'])),18,0);
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
                                $array[($i-1)/2+1][$val['TIME'][1]] .='(第'.$len.'节)'.$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks}" ;
                            }else{
                                $array[($i-1)/2+1][$val['TIME'][1]] .=$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks}<br/>";
                            }
                            break;
                        }
                        //todo:是一节课的就加上(第几节)  否则为空
                        $array[($i-1)/2+1][$val['TIME'][1]] .= ($jieci[$val['TIME'][0]]['UNIT']=="1" ? '('.trim($jieci[$val['TIME'][0]]['VALUE']).')' : '').
                            $OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks}<br/>";
                        break;
                    }
                }
            }
        }

        $web = A('Room/Room');
        $str=$web->web($array,$title_CLASSNAME['CLASSNAME'],date('Y-m-d H:i:s'),$_GET);

        $this->assign('content',$str);
        $this->display();
    }




    /*
     * 班级课表
     */
    public function myclasstime(){
        $year = $_GET['YEAR'];
        $term = $_GET['TERM'];
        $bind = $this->md->getBind("YEAR,TERM,CLASSNO",array($year, $term,session('x_classno')));
        $data = $this->md->sqlQuery($this->md->getSqlMap("timeTable/getClass.sql"),$bind);


        $this->assign("list",$this->getTimeTable($data));
        if(isset($_GET['daying'])){
            $this->assign('daying','true');
        }else{
            $this->assign('daying','false');
        }
        $this->assign("YEAR",$year);
        $this->assign("TERM", $term);
        $this->display();
    }



    /**
     * 周课表
     */
    public function myWeekTime(){
        $year = $_GET['YEAR'];
        $term = $_GET['TERM'];

        $bind = $this->md->getBind("YEAR,TERM,STUDENTNO",array($year, $term, session("studnet_studentno")));
        $data = $this->md->sqlQuery($this->md->getSqlMap("timeTable/getWeek.sql"),$bind);
        $this->assign("list",$this->getTimeTable($data));
        if(isset($_GET['daying'])){
            $this->assign('daying','true');
        }else{
            $this->assign('daying','false');
        }
        $this->assign("YEAR",$year);
        $this->assign("TERM", $term);
        $this->display();
    }

























    /**
     *  两个处理周课表的方法
     * @param $data
     * @param int $rowspan
     * @return array
     */
    private function getTimeTable($data, $rowspan=2){
        $list = array();
        if(!count($data)) return $list;

        $timeData = $this->md->sqlQuery("select NAME,VALUE,UNIT,TIMEBITS from TIMESECTORS");
        //所有课时列表以NAME为索引
        $timesectors = array_reduce($timeData, function($v1, $v2)
        {
            if (!$v1) $v1 = array();
            $v1[$v2["NAME"]] = $v2;
            return $v1;
        });
        //取得单节课时自然数为索引
        $countTimesectors = array_reduce($timeData, function($v1, $v2)
            {
                if (!$v1) $v1 = array();
                if ($v2['UNIT'] == "1") $v1[] = $v2["NAME"];
                return $v1;
            });
        //单双周
        $both = array("B"=>"","E"=>"（双周）","O"=>"（单周）");

        foreach($data as $row){
            $list = $this->makeTime($list,$row,$rowspan, $both, $timesectors,$countTimesectors);
        }
        //dump($list);

        return $list;
    }

    private function makeTime($list, $times, $rowspan, $both, $timesectors, $countTimesectors){
        $list = (array)$list;
        $split = str_split($times["TIME"]);
        if($split[0]=='0'){
            $list[0] .= $times["COURSE"]."<br/>";
            return $list;
        }
        $_time = $timesectors[$split[0]];
        for($i=1;$i<count($countTimesectors); $i+=$rowspan){
            //现在是以双节排
            for($j=0; $j<$rowspan; $j++){
                //说明有课跳出循环
                if(($timesectors[$countTimesectors[$i-1+$j]]['TIMEBITS'] & $_time['TIMEBITS'])>0){
                    $weeks='';
                    if($times['WEEKS']!=262143){
                        $weeks=' 周次 '.$this->colorr($times['WEEKS']);
                    }
                    $list[($i-1)/$rowspan+1][$split[1]] .= ($timesectors[$split[0]]['UNIT']=="1" ? '('.trim($timesectors[$split[0]]['VALUE']).')' : '').$both[$split[2]].$times["COURSE"].$weeks."<br/>";

                    break;
                }
            }
        }
        return $list;
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





    /**
     * 班级统一必修课
     */
    public function count_bixiu(){
        $rst = $this->_synchStudentCourseByClassno(trim($_GET['CLASSNO']),trim($_GET['YEAR']),trim($_GET['TERM']),'M');
        $this->exitWithReport($rst,'info');
    }
    /**
     * 班级统一非必修课
     */
    public function count_feibixiu(){
        $rst = $this->_synchStudentCourseByClassno(trim($_GET['CLASSNO']),trim($_GET['YEAR']),trim($_GET['TERM']),'M',false);
        $this->exitWithReport($rst,'info');
    }

    /**
     * 班级统一必修课和非必修课
     * @param $classno
     * @param $year
     * @param $term
     * @param $coursetype
     * @param bool $in
     * @return int|string
     */
    private function _synchStudentCourseByClassno($classno,$year,$term,$coursetype,$in=true){
        ini_set('max_execution_time', '1800');

        //判断是否有权限
        $teacher = $this->md->getTeacherInfo();
        if(is_string($teacher)){
            $this->exitWithReport('查询教师信息失败了！'.$teacher);
        }
        $class = $this->md->getClass(trim($_GET['CLASSNO']));
        if(is_string($class)){
            $this->exitWithReport('查询班级信息失败了！'.$class);
        }
        if($teacher['SCHOOL']!=$class[0]['SCHOOL'] && !isDeanByUsername(getUsername())){
            $this->exitWithReport('无法修改其他学部的班级！');
        }

        //清空旧的计划
        $rst = $this->md->clearStudentCourseByClassno($classno,'M',$year,$term);
        if(is_string($rst)){
            $this->exitWithReport('清空旧的修课计划失败！'.$rst);
        }

        //同步学生修课计划
        $rst = $this->md->synchStudentCourseByClassno($classno,$coursetype,$year,$term,$in);
        if(!is_string($rst)){
            $rst = "同步完成，此次共同步[{$rst}]条数据！";
        }
        return $rst;
    }



    /**
     * 修改学生密码
     */
    public function update_password(){

        $one=$this->md->sqlFind("select TEACHERS.SCHOOL from TEACHERS,USERS where USERS.TEACHERNO=TEACHERS.TEACHERNO AND USERS.USERNAME='{$_SESSION['S_USER_NAME']}'");
        $two=$this->md->sqlFind("SELECT SCHOOL FROM STUDENTS WHERE STUDENTNO='{$_POST['STUDENTNO']}'");

        if(!isDeanByUsername(getUsername())&&$one['SCHOOL']!=$two['SCHOOL']){
            exit('您不能修改其他学部的学生');
        }
        //todo:修改密码
        $three=$this->md->sqlExecute("UPDATE STUDENTS SET PASSWORD='{$_POST['PASSWORD']}',PASSEXPIRED=0 WHERE STUDENTNO='{$_POST['STUDENTNO']}'");
        if($three){
            exit('密码修改成功');
        }
    }

}


