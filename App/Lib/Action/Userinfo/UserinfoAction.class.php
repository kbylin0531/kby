<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-12-2
 * Time: 下午3:01
 **/
class UserinfoAction extends RightAction
{
    private $md;        //存放模型对象
    private $base;      //路径
    /**
     *  班级管理
     *
     **/
    public function __construct(){
        parent::__construct();
        $this->md=new UserinfoModel();
        $this->base='Userinfo/';
        $this->assign('quanxian',trim($_SESSION['S_USER_INFO']['ROLES']));

    }

    /*
     *基本信息的方法页面
     */
    public function basicinfo(){
        if($_POST['SQLPATH']){
            $jieguo=$this->md->sqlExecute($this->md->getSqlMap($_POST['SQLPATH']),$_POST['arr']);
            if($jieguo)
                echo '修改成功';
            else echo 'error';
            exit;
        }
        $info=$this->md->sqlFind($this->md->getSqlMap($this->base.'one_selectBasicinfo.SQL'),array(':username'=>$_SESSION['S_USER_INFO']['USERNAME']));
        $this->xiala('nationalitycode','nationalitycode');
        $this->xiala('partycode','partycode');
        $this->xiala('professioncode','professioncode');
        $this->xiala('edulevelcode','edulevelcode');
        $this->xiala('degreecode','degreecode');
        $this->assign('info',$info);
        $this->assign('teacherno',$_SESSION['S_USER_INFO']['TEACHERNO']);
        $this->display();
    }

    /*
     * 个人学习经历页面
     */
    public function xuexijingli(){
        if($_POST['SQLPATH']){
            $jieguo=$this->md->sqlExecute($this->md->getSqlMap($this->base.$_POST['SQLPATH']),$_POST['arr']);
            echo $jieguo;
            exit;
        }
        $info=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Two_selectStudy.SQL'),array(':USERNAME'=>$_SESSION['S_USER_INFO']['USERNAME']));
        $this->assign('info',$info);

        $this->xiala('honorlevelcode','honorlevelcode');                //获奖级别
        $this->assign('teacherno',$_SESSION['S_USER_INFO']['TEACHERNO']);
        $this->display();
    }

    //荣耀与获奖页面
    public function honor(){
        if($_POST['SQLPATH']){
            $jieguo=$this->md->sqlExecute($this->md->getSqlMap($this->base.$_POST['SQLPATH']),$_POST['arr']);
            echo $jieguo;
            exit;
        }
        $info=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Three_selectHonor.SQL'),array(':USERNAME'=>$_SESSION['S_USER_INFO']['USERNAME']));
        $this->xiala('honorlevelcode','honorlevelcode');                //获奖级别
        $this->assign('info',$info);
        $this->assign('teacherno',$_SESSION['S_USER_INFO']['TEACHERNO']);
        $this->display();
    }

    //todo：论文页面
    public function thesis(){
        if($_POST['SQLPATH']){
            $jieguo=$this->md->sqlExecute($this->md->getSqlMap($this->base.$_POST['SQLPATH']),$_POST['arr']);
            echo $jieguo;
            exit;
        }
        $info=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Four_selectThesis.SQL'),array(':USERNAME'=>$_SESSION['S_USER_INFO']['USERNAME']));
        $this->assign('info',$info);

        $this->assign('teacherno',$_SESSION['S_USER_INFO']['TEACHERNO']);
        $this->display();
    }

    //工作量页面
    public function work(){
        if($this->_hasJson){
            $count=$this->md->sqlFind($this->md->getSqlMap($this->base.'Five_countwork.SQL'),array(':USERNAME'=>$_SESSION['S_USER_INFO']['USERNAME']));
            if($arr['total']=$count['']){
                $arr['rows']=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Five_selectwork.SQL'),array(':USERNAME'=>$_SESSION['S_USER_INFO']['USERNAME'],':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
            }else{
                $arr['rows']=array();
            }
            $this->ajaxReturn($arr,'JSON');
            exit;
        }
        $this->display();
    }


    /**
     * 学期课程(teacherList)页面
     */
    public function teacherList(){
        if($this->_hasJson){
            $teacherno = $_SESSION['S_USER_INFO']['TEACHERNO'];
            $arr = $this->md->getMyMessage($teacherno,$this->_pageDataIndex,$this->_pageSize);
            $this->ajaxReturn($arr,'JSON');
            exit;
        }
        $this->assign('yearterm',$this->md->getYearTerm('C'));
        $this->display();
    }

    /**
     *  教师课程
     */
    public function teachercourse(){
        if(REQTAG === 'exportexcel'){
            //获取学年学期教师号数据
            $json = $this->md->getCourseSelectionTableList($_GET['year'],$_GET['term'],$_GET['courseno']);
            $this->md->initPHPExcel();
            $data['title'] = '课程学生列表';
            //表头
            $data['head'] = array(
                //默认值如 align type 的设计实例
                'studentname' => array('title' => '学号', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'studentno' => array(0=> '姓名','align' => CommonModel::ALI_RIGHT),
                'coursetype' => array( '修课方式', 'align' => CommonModel::ALI_CENTER),
                'kh' => array( '考核', 'align' => CommonModel::ALI_CENTER),
                'studentclass' => array( '学生班级', 'align' => CommonModel::ALI_CENTER,'color' => array('argb' => '00000000')),
                'headteacher' => array( '班主任', 'align' => CommonModel::ALI_CENTER),
            );
            $xlsData = $json['rows'];
            //表体
            $data['body'] = $xlsData;


            $this->md->fullyExportExcelFile($data);
            exit;
        }elseif(REQTAG === 'lookdetail'){
            $json = $this->md->getCourseSelectionTableList($_POST['year'],$_POST['term'],$_POST['courseno']);
            $this->ajaxReturn($json,'JSON');
            exit;
        }
        if($this->_hasJson){
            $arr = $this->md->getTeacherCourses($_POST['year'],$_POST['term'],$_POST['teacherno'],$this->_pageDataIndex,$this->_pageSize);
            $this->ajaxReturn($arr,'JSON');
            exit;
        }

        $this->assign('year',$_GET['year']);            //window.open传过来的学年
        $this->assign('term',$_GET['term']);            //window.open传过来的学期
        $this->assign('teacherno',$_GET['teacherno']);//window.open传过来的教师号
        $this->display();
    }

    /**
     * 课程情况的页面
     */
    public function teachercourse2(){
        $rst = $this->md->getCoursePlanInfo($_POST['year'],$_POST['term'],$_POST['courseno']);
        if(is_string($rst) || !$rst){
            $one = $rst;
        }else{
            $one = $rst[0];
        }
        $rst = $this->md->getTeacherInfo($_POST['year'],$_POST['term'],$_POST['courseno']);
        if(is_string($rst) || !$rst){
            $two = $rst;
        }else{
            $two = $rst[0];
        }
//        $one=$this->md->sqlFind($this->md->getSqlMap($this->base.'Seven_Mystudent_Top_two.SQL'),array(':year'=>));
//        $two=$this->md->sqlFind($this->md->getSqlMap($this->base.'Seven_Mystudent_Top_js.SQL'),array(':year'=>$_POST['year'],':term'=>$_POST['term'],':courseno'=>$_POST['courseno']));
        $this->ajaxReturn(array_merge($one,$two),'JSON');
    }
    //成绩输入
    public function inputResults(){
        if($this->_hasJson){

            exit;
        }
        $this->assign('school',$this->md->sqlFind("select SCHOOL from TEACHERS where TEACHERNO='{$_SESSION['S_USER_INFO']['TEACHERNO']}'"));
        $this->assign('teacherno',$_SESSION['S_USER_INFO']['TEACHERNO']);
        $this->assign('schools',getSchoolList());
        $this->display();
    }
    //执行方法
    public function Sexecute(){
        foreach($_POST['order'] as $key=>$val){
            $_POST['order'][$key]=$_POST['bind'][$key];
        }
        $bool=$this->md->sqlExecute($this->md->getSqlMap($_POST['exe']),$_POST['order']);
        if($bool)echo 'true';
    }


    //教学质量的页面
    public function teachingQuality(){
        $this->assign('username',$_SESSION['S_USER_INFO']['USERNAME']);
        $this->display();
    }

    //教师周课表页面
    public function teacherWeekCourse(){


        $array=array();            //保存排列后的信息
        $OEW = array("B"=>"","E"=>"（双周）","O"=>"（单周）");      //单双周数组
        $courseList=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Six_teacherWeekCourse.SQL'),array(':teacherno'=>$_GET['teacherno'],':year'=>$_GET['year'],':term'=>$_GET['term']));

        $teacherinfo=$this->md->sqlFind('select NAME from TEACHERS where TEACHERNO=:teacherno',array(':teacherno'=>$_GET['teacherno']));
        $User = A('Room/Room');
        $coursetype=$this->md->sqlQuery('select * from TIMESECTORS');        //获取 所有课程类型信息

        $skarray=array();  //要上课的课号列表
        foreach($courseList as $v){
            if($v['TIME'][1]=='0')continue;
            $skarray[$v['COURSENO'].$v['COURSEGROUP']]=$v['COURSENO'].$v['COURSEGROUP'];
        }
        $ar2=$User->getTime($coursetype);   //节次数组        (以NAME为下标)
        $onejie=array_reduce($coursetype,function($v1, $v2){
            if(!$v1) $v1 = array();
            if($v2['UNIT']=="1") $v1[]=$v2["NAME"];
            return $v1;
        });                               //获得有1节课的数组



        $num=count($onejie);                                                             //统计一节课的  有几节
        $ddstr='';
        // print_r($onejie);

        $daiding=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Six_selectTeacher_Course.SQL'),array(':YEAR'=>$_GET['year'],':TERM'=>$_GET['term'],':TEACHERNO'=>$_GET['teacherno'],':start'=>0,':end'=>1000));
        /*    echo '<pre>';
                print_r($daiding);
                echo '-------------------------------';
                print_r($ar2);
                echo '+++++++++++++++++++++++++++++';
                print_r($onejie);*/
        /*        echo '<pre>';
                print_r($courseList);
                echo '<hr>';
                print_r($skarray);
                echo '<hr>';
                print_r($daiding);*/
        foreach($daiding as $v){
            if(!in_array($v['kh'],$skarray)){
                $ddstr.=$v['km']."({$v['kh']})&nbsp&nbsp";
            }
        }
        $array[0]=$ddstr;

        foreach($courseList as $key=>$val){
            $split = str_split($val["TIME"]);
            //   if($split[0]=='0') return $list[0] .= $times["COURSE"]."<br/>";

            if($val['WEEKS']!=262143){
                $weeks='周次'.str_pad(strrev(decbin($val['WEEKS'])),18,0);
            }else{
                $weeks='';
            }
            //去查询课程名称
            $classname=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Six_teacherWeekClassName.SQL'),array(':YEAR'=>$_GET['year'],':TERM'=>$_GET['term'],':COURSENO'=>$val['COURSENO'],':GROUP'=>$val['COURSEGROUP']));
            $classstr='';

            foreach($classname as $vall){
                $classstr.=$vall['CLASSNAME'];
            }

            for($i=1;$i<$num;$i+=2){

                for($j=0;$j<2;$j++){

                    if(($ar2[$val['TIME'][0]]['TIMEBITS'] & $ar2[$onejie[$i-1+$j]]['TIMEBITS'])>0){
                        //ar2节次数组        (以NAME为下标)

                        if($ar2[$val['TIME'][0]]['UNIT']=="3"){
                            //取最后一节课是第几节
                            $len=strlen(strrev(decbin($ar2[$val['TIME'][0]]['TIMEBITS'])));
                            //表示到单节了
                            if(!($i+1<$len)){
                                $array[($i-1)/2+1][$val['TIME'][1]] .='(第'.$len.'节)'.$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks} {$classstr}{$val['TASK']}" ;
                            }else{
                                $array[($i-1)/2+1][$val['TIME'][1]] .=$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks} {$classstr}{$val['TASK']}<br/>";
                            }
                            break;
                        }
                        //是一节课的就加上(第几节)  否则为空
                        $array[($i-1)/2+1][$val['TIME'][1]] .= ($ar2[$val['TIME'][0]]['UNIT']=="1" ? '('.trim($ar2[$val['TIME'][0]]['VALUE']).')' : '').$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks} {$classstr}{$val['TASK']}<br/>";
                        break;
                        /*   $array[($i-1)/2+1][$val['TIME'][1]] .= ($jieci[$val['TIME'][0]]['UNIT']=="1" ? '('.trim($jieci[$val['TIME'][0]]['VALUE']).')' : '').$OEW[$val['TIME'][2]].$val["COURSE"]."{$weeks}<br/>";
                           break;*/
                    }
                }
            }
        }

        $str=$User->web($array,$teacherinfo['NAME'],date('Y-m-d H:i:s'),array('year'=>$_GET['year'],'term'=>$_GET['term']));

        $this->assign('str',$str);

        $this->display();

    }}
