<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/13
 * Time: 14:17
 */

/**
 * 课程选课管理公共控制器，教师端和学生的 学生选课页面都使用了该控制器的方法
 * Class CourseSelectionAction
 */
final class CourseSelectionAction extends RightAction{

    protected $model = null;
    protected $studentInfo = array();

    public function __construct($model=null){
        parent::__construct();
        if(!$model && ($model instanceof CourseManagerModel)){
            $this->model = $model;
        }else{
            $this->model = new CourseManagerModel();
        }
    }

    /**
     * 获取已选课程列表
     * @param $studentno
     */
    public function studentCourseSelectionList($studentno){
        if($this->_hasJson){
            $this->ajaxReturn($this->model->getStudentCourseTableListForRemoveList(
                $studentno,
                intval($_REQUEST['YEAR']),
                intval($_REQUEST['TERM'])
            ),'JSON');
            exit;
        }
        $yearTerm = $this->model->getYearTerm('C');
        $this->assign('year',$yearTerm['YEAR']);
        $this->assign('term',$yearTerm['TERM']);

        $this->display('CourseManager@CourseSelection/'.substr(strstr(__METHOD__,'::'),2));
    }
    /**
     * 对选择的非必修课进行退选
     * @param $studentno
     */
    public function removeStudentCourse($studentno){
        //检测学年，学期是否正确
        if(!isset($_REQUEST['YEAR']) || $_REQUEST['YEAR']<2000 || !isset($_REQUEST['TERM']) || $_REQUEST['TERM']==0 || $_REQUEST['TERM']>4){
            $this->exitWithReport('无法从请求中获取正确的学年学期！');
        }elseif(empty($_REQUEST['ids'])){
            $this->exitWithReport('没有选择任一课程');
        }
        $message = '';
        $year = $_REQUEST["YEAR"];
        $term = $_REQUEST["TERM"];
        //批量删除
        foreach($_REQUEST["ids"] as $courseNo){
            $rst = $this->model->removeStudentCourse($year,$term,$studentno,$courseNo);
            if(is_string($rst)){
                $message .= nl2br("$rst \n");
            }elseif($rst){
                //成功创建，不显示
            }
        }
        $success = empty($message);
        $this->exitWithReport(
            $success?"成功删除第[$year]学年第[$term]学期学生号为[$studentno]的".count($_REQUEST["ids"])."条选课记录！":$message,
            $success?'info':'error'
        );
    }
    /**
     * 选课列表 查询筛选界面
     * @param $studentno
     * @param string $type other表示普通类选课，I表示社团类选课，J表示通识课选课
     */
    public function queryPage($studentno,$type='other'){
        $qualityModel = new QualityModel();
        $studentinfo = $this->getStudentInfo($studentno);
        if(is_string($studentinfo)){
            $this->exitWithReport("查询学号为[$studentno]的学生信息失败！");
        }
        $courseTypeOptions = null;
        switch($type){
            case 'I':
                $courseTypeOptions = array(array(
                    'CODE' => 'I',
                    'NAME' => '社团课'
                ));
                break;
            case 'J':
                $courseTypeOptions = array(array(
                    'CODE' => 'J',
                    'NAME' => '通识课'
                ));
                break;
            case 'other':
            default:
                $courseTypeOptions = array_merge(array(array(
                    'CODE' => 'other',
                    'NAME' => '全部'
                )),$this->model->_getCourseTypeOptionsOfCommon());
        }

        $this->assign("isKaopingLock",$qualityModel->hasQualityEvaluationUnfinished($studentno));
        $this->assign("isFee",$studentinfo['FREE']);
        $this->assign('coursetype_op',$courseTypeOptions);
        $this->assign("yearTerm",$this->model->getYearTerm('C'));
        $this->assign('classno',$studentinfo['CLASSNO']);

        $this->display('CourseManager@CourseSelection/'.substr(strstr(__METHOD__,'::'),2));
    }

    /**
     * 选课列表 显示课程列表界面
     * @param null $studentno
     */
    public function queryData($studentno=null){
        if($this->_hasJson){
            $bind = $this->model->getBind('STUDENTNO,YEAR,TERM,COURSENOGROUP,COURSENAME,TEACHERNAME,COURSETYPE,SCHOOL,CLASSNO,DAY,TIME', $_REQUEST, '%');
//            $bind = array_merge(array(':stno'=>$studentno),$bind);
            $coursetypeFilter = ' VIEWSCHEDULETABLE.TYPE LIKE :COURSETYPE ';
            switch($bind[':COURSETYPE']){
                case 'other':
                    unset($bind[':COURSETYPE']);
                    $coursetypeFilter = " VIEWSCHEDULETABLE.TYPE not in ('I','J') ";
                    break;
            }
            if(isset($studentno)){
                $bind[':STUDENTNO'] = $studentno;
            }
            $bind[':CLASSNO'] = trim($bind[':CLASSNO']).'%';
            $rst = $this->model->getStudentCourseTableList($coursetypeFilter, $bind, $this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($rst,'JSON'); exit;
        }
        $this->assign('queryParams',$_REQUEST?json_encode($_REQUEST):'{}');
        $this->display('CourseManager@CourseSelection/'.substr(strstr(__METHOD__,'::'),2));
    }

    /**
     * 为学生添加课程
     * @param $studentno
     * @return void
     */
    public function selectCourseForStudent($studentno){
        //加测学年学期输入和选择的课程是否合法
        if(empty($_REQUEST['YEAR']) || empty($_REQUEST['TERM'])){
            $this->exitWithReport("学年学期[{$_REQUEST["YEAR"]}-{$_REQUEST["TERM"]}]无效");
        }elseif(empty($_REQUEST['ids'])){
            $this->exitWithReport('没有选择任一课程!');
        }

        $message = '';
        //检查限选条件和限选学分是否超出限制
        $coursePlanModel = new CoursePlanModel();
        $limitGroupNo = 0;//限选组号
        $limitCredit = 0;//限选学分
        $limitNum = 0;//限选条数
        $totalCredit = 0;//课程总的学分
        $flag = true;
        foreach($_REQUEST['ids'] as $item){
            $courseno = substr($item['COURSENOGROUP'],0,7);
            $groupno  = substr($item['COURSENOGROUP'],7);
            $rst = $coursePlanModel->getLimitCondition($_REQUEST['YEAR'],$_REQUEST['TERM'],$courseno,$groupno);
//            var_dump($rst);
            if(is_string($rst)){
                $this->exitWithReport("获取第[{$_REQUEST['YEAR']}]学年第[{$_REQUEST['TERM']}]学期课号组号为[$courseno-$groupno]的课程信息失败！".$rst);
            }
            if($flag){
                $limitCredit = floatval($rst['LIMITCREDIT']);
                $limitGroupNo = $rst['LIMITGROUPNO'];
                $limitNum = floatval($rst['LIMITNUM']);
                $flag = false;
            }
            $totalCredit += floatval($rst['CREDITS']);
        }
//        vardump($limitCredit,$limitGroupNo,$limitNum,$totalCredit);10 4 0 0
//        vardump(floatval($limitGroupNo));
        if(floatval($limitGroupNo)){
            if($limitNum){
                $cc = count($_REQUEST['ids']);
                if($cc > $limitNum){
                    $this->exitWithReport("限选组号为[$limitGroupNo]的课程限选条数为[$limitNum]，无法选择[$cc]门课程！");
                }
            }elseif($limitCredit){
                if($totalCredit != $limitCredit){//限选总学分必须等于选择的课程的总学分
                    $this->exitWithReport("限选组号为[$limitGroupNo]的课程限选学分为[$limitCredit]，你选择的课程学分为[$totalCredit]!");
                }
            }else{
                //不作限制
            }
        }

        foreach($_REQUEST['ids'] as $item){
            $message .= $this->model->insertStudentCourse($_REQUEST['YEAR'],$_REQUEST['TERM'],$studentno,$item,1);
            $message .= '<br />';
        }

        $message = trim($message,'<br />');
        if(empty($message)){
            $this->exitWithReport("成功为学号为[$studentno]的学生添加[".count($_REQUEST['ids'])."]门课程！",'info');
        }else{
            $this->exitWithReport($message);
        }
    }

    /**
     * 根据学号获取学生信息
     * @param $studentno
     * @return string
     */
    protected function getStudentInfo($studentno){
        if(isset($this->studentInfo[$studentno])){
            return $this->studentInfo[$studentno];
        }else{
            $student = $this->model->getStudents($studentno);
            if(is_string($student) or !$student){
                return '无法获取学生信息'.$student;
            }
            return $this->studentInfo[$studentno] = $student[0];
        }
    }

    protected function isFee($studentNo=null){
        $studentInfo = $this->getStudentInfo($studentNo);
        if(is_string($studentInfo)){
            return $studentInfo;
        }else{
            return $studentInfo['FREE'];
        }
    }


}
