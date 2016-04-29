<?php
/**
 * Created by Linzh.
 * User: Administrator
 * Date: 2015/10/16
 * Time: 8:57
 */

/**
 * Class InputAction 成绩输入 子模块
 * 国语英文对照：
 *  补考       - resit
 *  期末考     - finals
 *  重修考     - retake
 * 期末成绩     - finals_score
 * 平时成绩     - normal_score
 * 期中成绩     - midter_mscore
 * 总评成绩     - general_score（禁止计算）
 *
 *
 * updateStudentLockStatusByRecno
updateStudentMidtermLockStatusByCoursegroup
updateStudentFinalsLockStatusByCoursegroup
updateStudentResitLockStatusByCoursegroup
updateAllLock
 */
class InputAction extends RightAction {
    /**
     * @var ResultInputModel
     */
    private $model = null;

    /**
     * @var array|null|string
     */
    private $_yearterm = null;

    private $isdean = false;

    /**
     * 每次构造的时候都初始化并不见得高效
     * 后期建议用 __get 来获取Model实例
     */
    public function __construct(){
        parent::__construct();
        $this->model = new ResultInputModel();
        //预分配通用信息
        $this->assign('yearterm',$this->_yearterm = $this->model->getYearTerm('J'));
        $this->assign('school',$_SESSION['S_USER_INFO']['SCHOOL']);
        $this->assign('teacherno',$_SESSION['S_USER_INFO']['TEACHERNO']);
        $this->assign('imdean',$this->isdean = $this->model->checkTeacherRole('D'));
    }

    /**
     * 成绩导入
     */
    public function pageImport(){

        if(null !== REQTAG){
            $excelModel = new ExcelExtensionModel();
            if(REQTAG === 'resitimport'){
                //补考成绩导入
                $data = $excelModel->import(array(
                    'year'  => '学年',
                    'term'  => '学期',
                    '_classname'  => '班级',
                    'courseno'  => '课号',
                    '_coursename'  => '课名',
                    'studentno'  => '学号',
                    '_studentname'  => '姓名',
                    'resitscore'  => '补考成绩',
                ),null,1);

                $this->doModified($this->model,'updateStudentsResitScoreByMultiCondition',$data,1);
            }elseif(REQTAG === 'finalsimport'){
                $data = $excelModel->import(array(
                    'year'  => '学年',
                    'term'  => '学期',
                    '_classname'  => '班级',
                    'courseno'  => '课号',
                    '_coursename'  => '课名',
                    'studentno'  => '学号',
                    '_studentname'  => '姓名',
                    'normal_score'  => '平时成绩',
                    'finals_score'  => '期末成绩',
                    'general_score'  => '总评成绩',
                ),null,1);
                $this->doModified($this->model,'updateStudentsFinalsScoreByMultiCondition',$data,2);
            }elseif(REQTAG === 'midtermimport'){

                $data = $excelModel->import(array(
                    'year'  => '学年',
                    'term'  => '学期',
                    '_classname'  => '班级',
                    'courseno'  => '课号',
                    '_coursename'  => '课名',
                    'studentno'  => '学号',
                    '_studentname'  => '姓名',
                    'midterm_score'  => '期中成绩',
                ),null,1);

                $this->doModified($this->model,'updateStudentsMidtermScoreByMultiCondition',$data,3);

            }
            exit();
        }

        $this->display();
    }

    /**
     * @param $model
     * @param $functionname
     * @param $data
     * @param $type
     * @return void
     * @throws
     */
    private function doModified($model,$functionname,$data,$type){
        if(!is_array($data)){
            exit('导入失败,请检查文档格式!');
        }
        $failednum = 0;
        $info = '<pre><b>导入失败的学号如下：</b><br />';
        foreach($data as $student){
            switch($type){
                case 1:
                    $condition = array(
                        $student['year'],
                        $student['term'],$student['courseno'],'%',$student['studentno'],$student['resitscore']
                    );
                    break;
                case 2:
                    $condition = array(
                        $student['year'],
                        $student['term'],$student['courseno'],'%',$student['studentno'],$student['normal_score'],$student['finals_score'],$student['general_score']
                    );
                    break;
                case 3:
                    $condition = array(
                        $student['year'],
                        $student['term'],$student['courseno'],'%',$student['studentno'],$student['midterm_score']
                    );
                    break;
                default:
                    throw new Exception('错误的参数！ ');
            }

            $rst = call_user_func_array(array($model,$functionname),$condition);
            if(is_string($rst)){
                ++$failednum;
                $info .= "  {$student['studentno']} 发生了错误！<br />";
            }elseif(!$rst){
                ++$failednum;
                $info .= "  {$student['studentno']} 修改失败,请检查是否存在填写错误！<br />";
            }
        }
        $action = "<input type=\"button\" name=\"back\" value=\"继续导入\" onclick=\"javascript:history.back(-1);\"/>";
        if(!$failednum){
            exit('所有的学生导入成功！'.$action);
        }else{
            exit("{$info}</pre>{$action}");
        }
    }


//TODO:成绩输入时间设置
    /**
     * 成绩输入时间 页面显示
     */
    public function pageInputTimeSetting(){
        $gateModel = new GateModel();
        $setting = $gateModel->getResultInput();
        if(is_string($setting) or !$setting){
            $this->exitWithReport( "查询时间记录失败！{$setting}");
        }
        $this->assign('setting',$setting);
        $this->display('pageInputTimeSetting');
    }

    /**
     * 更新成绩输入时间限制
     * @param int $year
     * @param int $term
     * @param string $begin_time
     * @param string $end_time
     * @param int $status
     */
    public function updateInputTime($year,$term,$begin_time,$end_time,$status){
        $gateModel = new GateModel();
        $rst = $gateModel->updateResultInput(array(
            'year'  => $year,
            'term'  => $term,
            'begin_time'    => $begin_time,
            'end_time'  => $end_time,
            'status'    => $status,
        ));
        if(is_string($rst) or !$rst){
            $this->failedWithReport("更新失败！{$rst}");
        }
        $this->successWithReport('更新成功！');
    }
//TODO:课程总评成绩百分比管理
    /**
     * 课程总评成绩百分比管理 页面显示
     */
    public function pageCourseGeneralScorePercentManage(){
        $this->display('pageCourseGeneralScorePercentManage');
    }
    /**
     * 获取百分比设置列表数据
     * @param string $coursegroupno
     */
    public function listCourseGeneralScorePercent($coursegroupno){
        $model = new CourseGeneralScoreManagementModel();
        $rst = $model->getPercentSettingLikableTableList($coursegroupno,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($rst)){
            $this->failedWithReport("查询列表失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }

    /**
     * 课程总评成绩百分比管理 添加
     * @param string $coursegroup
     * @param string $normalscore
     * @param string $midtermscore
     * @param string $finalsscore
     */
    public function createCourseGeneralScorePercent($coursegroup,$normalscore,$midtermscore,$finalsscore){
        $model = new CourseGeneralScoreManagementModel();
        $rst = $model->createPercentSetting($coursegroup,$normalscore,$midtermscore,$finalsscore);
        if(is_string($rst) or !$rst){
            $this->failedWithReport("添加失败！{$rst}");
        }else{
            $this->successWithReport("添加成功！");
        }
    }
    /**
     * 课程总评成绩百分比管理 删除百分比设置
     * @param string $coursegroup
     */
    public function deleteCourseGeneralScorePercent($coursegroup){
        $model = new CourseGeneralScoreManagementModel();
        $rst = $model->deletePercentSetting($coursegroup);
        if(is_string($rst) or !$rst){
            $this->exitWithReport("删除失败！{$rst}");
        }else{
            $this->successWithReport("删除成功！");
        }
    }
    /**
     * 获取课程的百分比设置
     * @param string $coursegroup
     */
    public function selectCourseScoreSetting($coursegroup){
        $model = new CourseGeneralScoreManagementModel();
        $rst = $model->getCourseScoreSetting($coursegroup);
        if(is_string($rst)){
            $this->exitWithReport("获取失败！{$rst}");
        }
        $this->ajaxReturn($rst.'JSON');
    }
    /**
     * 批量更新总评百分比设置
     * @param array $rowlist
     */
    public function updateGeneralScorePercentInBatch($rowlist){
        $model = new CourseGeneralScoreManagementModel();
        $count = 0;
        foreach($rowlist as $val){
            if(intval($val['normalscore']) +intval($val['midtermscore']) +intval($val['finalsscore'])  !== 100 ){
                $this->exitWithReport("课程{$val['coursegroup']}总百分比非100！");
            }
            $rst = $model->updatePercentSetting($val['coursegroup'],$val['normalscore'],$val['midtermscore'],$val['finalsscore']);
            if(is_string($rst) or !$rst){
                $this->exitWithReport("更新课程{$val['coursegroup']}失败！{$rst}");
            }
            ++ $count;
        }
        $this->successWithReport("成功更新{$count}门课程!");
    }

//TODO:课程成绩输入初始化
    /**
     * 课程成绩输入初始化 页面
     */
    public function pageInputCourseInit(){
        $this->display('pageInputCourseInit');
    }
    /**
     * 课程成绩输入初始化
     * @param int $year
     * @param int $term
     * @param string $coursegroupno
     */
    public function initCourseFinalsInput($year,$term,$coursegroupno){
        $rst = $this->model->initCourseScoresInputByCoursegroupLikelyInBatch($year,$term,$coursegroupno);
        if(is_string($rst)){
            $this->failedWithReport($rst);
        }
        $this->successWithReport("成功初始化{$rst['count']}门课程!");
    }
    /**
     * 课程补考成绩输入初始化
     * 寻找不及格的学生
     * 各个学校判断学生是否需要参加补考有一定的判断规则
     * @param int $year
     * @param int $term
     * @param string $coursegroupno
     */
    public function initCourseResitInput($year,$term,$coursegroupno){
        $rst = $this->model->initCourseResitInputByCoursegroupLikelyInBatch($year,$term,$coursegroupno);
        if(is_string($rst)){
            $this->failedWithReport($rst);
        }
        $this->successWithReport("成功初始化{$rst['count']}门课程!");
    }
//TODO:任课老师期末成绩输入
    /**
     * 任课老师期末成绩输入 课程选择界面
     */
    public function pageFinalsSelect(){
        $this->display('Input/pageFinalsSelect');
    }
    /**
     * 任课老师期末成绩输入 课程列表获取
     * @param int $year
     * @param int $term
     * @param string $teacherno
     */
    public function listFinalsSelect($year,$term,$teacherno){
        $rst = $this->model->listScheduleCoursesByTeacherno($year,$term,$teacherno);
        if(is_string($rst)){
            $this->exitWithReport("查询期末成绩输入课程列表失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }
    /**
     * 任课老师期末成绩输入 学生列表界面
     * @param int $year
     * @param int $term
     * @param string $coursegroup 课号加组号
     * @param $scoretype
     */
    public function pageFinalsInput($year=null,$term=null,$coursegroup=null,$scoretype=null) {


        if(REQTAG === 'sync'){
            //同步学生名单
            $this->sync($year,$term,$coursegroup);
        }


        $this->assign('year_a',$year);
        $this->assign('year_b',intval($year)+1);
        $this->assign('term',$term);
        $this->assign('coursegroup',$coursegroup);
        $this->assign('scoretype',$scoretype);
        $scoretypetext = null;
        switch($scoretype){
            case 'ten':
                $scoretypetext = '百分制';
                break;
            case 'five':
                $scoretypetext = '五级制';
                break;
            case 'two':
                $scoretypetext = '二级制';
                break;
            default:
                $scoretypetext = '记分制不明确';
        }
        $this->assign('scoretypetext',$scoretypetext);

        $info = $this->model->getCourseInfo($year,$term,$coursegroup);
        if(is_string($info)){
            header("Content-type:text/html;charset=utf-8");
            exit( "获取课程详细信息失败，请联系管理员协助解决你的问题！{$info}");
        }
        $this->assign('courseinfo',$info);//原先assignTT

        $teacherModel = new TeacherModel() ;
        $tinfo = $teacherModel->getTeachersBySchedulePlan($year,$term,$coursegroup);
        $this->assign('teachers',$tinfo);//原先assignTT

        $cgModel = new CourseGeneralScoreManagementModel();
        $rst = $cgModel->getCourseScoreSetting($coursegroup);
        if(is_string($rst)){
            exit('无法加载课程总评百分比计算信息，程序退出，请联系相关程序员协助解决您的问题！');
        }
        $this->assign('GSPS',$rst);//general score percent setting 简称
        $this->display('Input/pageFinalsInput');
    }
    /**
     * 任课老师期末成绩输入 学生成绩列表获取
     * @param int $year
     * @param int $term
     * @param string $courseno
     */
    public function listFinalsInput($year,$term,$courseno){
        $rst = $this->model->listStudentScoreByCourseno($year,$term,$courseno);
        if(is_string($rst)){
            $this->failedWithReport("获取期末考试成绩学生列表数据失败!{$rst}");
        }elseif(!$rst['total']){
            //空数组，可能未初始化，也可能没有学生上这课。。。
            $rtn = $this->model->initCourseScoresInputByCoursegroup($year,$term,$courseno);
            if(is_string($rtn)){
                $this->failedWithReport("初始化课号组号为{$courseno}的课程失败！{$rtn}");
            }
            $rst = $this->model->listStudentScoreByCourseno($year,$term,$courseno);
        }
        $this->ajaxReturn($rst,'JSON');
    }
    /**
     * 批量修改成绩(平时、期中、期末、总评)
     * @param string $examtime 考试时间
     * @param array $rows 行数据
     * @param string  $coursegroup 课号组号
     * @param int $year 学年
     * @param int $term 学期
     * @throws Exception
     */
    public function updateFinalsScoreInBatch($examtime,$rows=array(),$coursegroup=null,$year=null,$term=null){
        //修改考试时间
        if(isset($coursegroup,$year,$term)){
            $rst = $this->model->updateFinalsExamtime($coursegroup,$year,$term,$examtime);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("统一修改期末考试时间失败！{$rst}");
            }
            //目的只在于修改时间
            if(!$rows) $this->successWithReport("修改期末考试时间成功!{$rst}");
        }

        //修改各种类型的分数
        if(!$rows) $this->failedWithReport('提交为空，请确认修改!');
        $this->model->startTrans();
        $c1 = 0;
        foreach($rows as $key=>$val){
            $recno = $val['_origin']['recno'];
            $rst = $this->model->isStudentFinalsLockedByRecno($recno);
            if(is_string($rst)){
                $this->exitWithReport("检测期末输入是否锁定失败！{$rst}");
            }elseif($rst){
//                $this->exitWithReport("学生期末成绩输入已经上锁，请确认，有问题请联系教务处管理员！{$rst} ");
                continue;
            }
            //确认学生成绩是否可以输入
            $rst = $this->model->selectStudentScoresByRecno($recno);
            if(is_string($rst)){
                $this->exitWithReport($recno);
            }else{
                $rtn = $this->isInputable($rst['YEAR'],$rst['TERM']);
                if(true !== $rtn){
                    $this->failedWithReport($rtn);
                }
            }
            $rst = $this->model->updateStudentFinalsScoresByRecno($recno,$val);
            if(is_string($rst) or !$rst){
                $msg = "修改学生期末成绩失败！{$rst}";
                $this->failedWithReport($msg);
            }
            ++ $c1;
        }

        //计算学分
        $ca = new CalculationAction();
        $c = $ca->calculateScoreCredit($year,$term,'%','%',$coursegroup,false);
        if(is_string($c)){
            $this->failedWithReport("计算学分的过程中出现错误！ {$c}");
        }

        $this->model->commit();
        $this->successWithReport("修改成功，共保存了{$c1}个学生的成绩，{$c}个的学生学分数据进行了重新生成!");
    }
    /**
     * 期末成绩打印 页面显示
     * @param int $year
     * @param int $term
     * @param string $courseno
     * @param int $page
     */
    public function pageFinalsInputForPrint($year,$term,$courseno,$page=1){
        $teacherModel = new TeacherModel() ;
        $teachers = $teacherModel->getTeachersBySchedulePlan($year,$term,$courseno);

        $courseModel = new CourseModel();
        $courseinfo = $courseModel->getCourseInfo($courseno);
        if(is_string($courseinfo)  or !$courseinfo){
            $this->exitWithReport("获取课程[{$courseno}]的信息失败!{$courseinfo}");
        }

        $classModel = new ClassesModel();
        $classes = $classModel->getClassesnameByCourseno($year,$term,$courseno);
        if(is_string($classes)){
            $this->exitWithReport("获取课程[{$courseno}]的班级信息失败!{$classes}");
        }

        $this->assign('page',$page);
        $this->assign('year_a',$year);
        $this->assign('year_b',intval($year)+1);
        $this->assign('term',$term);
        $this->assign('courseno',$courseno);
        $this->assign('teachers',$teachers);
        $this->assign('courseinfo',$courseinfo[0]);
        $this->assign('classes',$classes['classname']);

        $this->display('Input/pageFinalsInputForPrint');
    }
    /**
     * 期末成绩打印界面 学生成绩数据获取
     * @param int $year
     * @param int $term
     * @param string  $courseno 课号组号
     * @param int $page 显示第几页
     */
    public function listFinalInputForPrint($year,$term,$courseno,$page=1){
        $start  = ($page-1)*120;
        $end    = $page*120-1;
        $rst = $this->model->listStudentScoreByCourseno($year,$term,$courseno,$start,$end);
        if(is_string($rst)){
            $this->exitWithReport("查询第[{$year}]学年第[{$term}]学期课号为[{$courseno}]的补考学生列表失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
        exit;
    }
//TODO:任课教师期中成绩输入
    /**
     * 任课教师补考成绩输入 补考成绩打印页面
     * @param int $year
     * @param int $term
     * @param string $courseno
     * @param string $scoretype
     * @param int $page
     */
    public function pageMidtermInputForPrint($year,$term,$courseno,$scoretype='ten',$page=1){
        $this->assign('year_a',$year);
        $this->assign('year_b',intval($year)+1);
        $this->assign('term',$term);
        $this->assign('coursegroup',$courseno);

        $this->assign('coursegroup',$courseno);
        $this->assign('scoretype',$scoretype);

        $this->assign('page',$page);

        $scoretypetext = null;
        switch($scoretype){
            case 'ten':
                $scoretypetext = '百分制';
                break;
            case 'five':
                $scoretypetext = '五级制';
                break;
            case 'two':
                $scoretypetext = '二级制';
                break;
            default:
                $scoretypetext = '记分制不明确';
        }
        $this->assign('scoretypetext',$scoretypetext);

        $info = $this->model->getCourseInfo($year,$term,$courseno);
        if(is_string($info)){
            header("Content-type:text/html;charset=utf-8");
            exit( "获取课程详细信息失败，请联系管理员协助解决你的问题！{$info}");
        }
//        mist($info);
        $this->assign('courseinfo',$info);//原先assignTT

        $teacherModel = new TeacherModel() ;
        $tinfo = $teacherModel->getTeachersBySchedulePlan($year,$term,$courseno);
        $this->assign('teachers',$tinfo);//原先assignTT

        $cgModel = new CourseGeneralScoreManagementModel();
        $rst = $cgModel->getCourseScoreSetting($courseno);
        if(is_string($rst)){
            exit('无法加载课程总评百分比计算信息，程序退出，请联系相关程序员协助解决您的问题！');
        }
        $this->assign('GSPS',$rst);//general score percent setting 简称
        $this->display('pageMidtermInputForPrint');
    }

    /**
     * 同步学生名单
     * @param $year
     * @param $term
     * @param $coursegroup
     */
    private function sync($year,$term,$coursegroup){
        $rst = $this->model->sync($year,$term,$coursegroup);
        if(is_string($rst)){
            $this->failedWithReport("同步学生列表出错!{$rst}");
        }
        $this->successWithReport("同步完成，一共删除了'{$rst[0]}'个退课学生，添加了'{$rst[1]}'个学生的数据！");
    }

    /**
     * <未使用>任课教师期中成绩输入 学生列表页面显示
     * @param int $year
     * @param int $term
     * @param string $coursegroup
     * @param string $scoretype
     */
    public function pageMidtermInput($year=null,$term=null,$coursegroup=null,$scoretype=null){

        if(REQTAG === 'sync'){
            //同步学生名单
            $this->sync($year,$term,$coursegroup);
        }

        $this->assign('year_a',$year);
        $this->assign('year_b',intval($year)+1);
        $this->assign('term',$term);
        $this->assign('coursegroup',$coursegroup);
        $this->assign('scoretype',$scoretype);
        $scoretypetext = null;
        switch($scoretype){
            case 'ten':
                $scoretypetext = '百分制';
                break;
            case 'five':
                $scoretypetext = '五级制';
                break;
            case 'two':
                $scoretypetext = '二级制';
                break;
            default:
                $scoretypetext = '记分制不明确';
        }
        $this->assign('scoretypetext',$scoretypetext);


        $info = $this->model->getCourseInfo($year,$term,$coursegroup);
        if(is_string($info)){
            $this->headerUtf8();
            exit( "获取课程详细信息失败，请联系管理员协助解决你的问题！{$info}");
        }
        $this->assign('courseinfo',$info);//原先assignTT

        $teacherModel = new TeacherModel() ;
        $tinfo = $teacherModel->getTeachersBySchedulePlan($year,$term,$coursegroup);
        $this->assign('teachers',$tinfo);//原先assignTT

        $cgModel = new CourseGeneralScoreManagementModel();
        $rst = $cgModel->getCourseScoreSetting($coursegroup);
        if(is_string($rst)){
            exit('无法加载课程总评百分比计算信息，程序退出，请联系相关程序员协助解决您的问题！');
        }
        $this->assign('GSPS',$rst);



        $this->display('pageMidtermInput');
    }
    /**
     * 批量更新补考成绩输入
     * @param int $year
     * @param int $term
     * @param string $courseno 课号组号
     * @param array $rows 所有行全部数据
     * @param string $examtime 考试时间
     */
    public function updateMidtermScoreInBatch($year=null,$term=null,$courseno=null,$examtime=null,$rows=array()){
        //修改考试时间
        if(isset($courseno,$year,$term)){
            $rst = $this->model->updateMidtermExamtime($courseno,$year,$term,$examtime);
            if(is_string($rst) or !$rst) $this->failedWithReport("统一修改期中考试时间失败！{$rst}");
            //单纯修改时间 回复时间修改成功
            if(!$rows) $this->successWithReport("考试时间修改成功！");
        }
        //如果修改了时间  则 $rows一定不是空数组
        if(!$rows) $this->failedWithReport("提交为空，终止执行!");
        $this->model->startTrans();
        $count = 0;
        foreach($rows as $key=>$val){
            $studentno = $val['_origin']['studentno'];
            $recno = $val['_origin']['recno'];
            //修改补考成绩
            $rst = $this->model->isStudentMidtermLocked($courseno,$year,$term,$studentno);
            $rtn = $this->isInputable($year,$term);
            if(true !== $rtn){
                $this->failedWithReport($rtn);
            }
            if(is_string($rst)){
                $this->exitWithReport("课号[{$courseno}]学年[{$year}]学期[{$term}]学号[{$studentno}]后台确认学生补考是否锁定失败！{$rst}");
            }elseif($rst){
//                $this->exitWithReport("课号[{$courseno}]学年[{$year}]学期[{$term}]学号[{$studentno}]后台确认锁定，有问题请联系管理员!{$rst}");
                continue;
            }
            $rst = $this->model->updateStudentMidtermScoreByRecno($val['_origin']['midterm_score'],$recno);
            if(is_string($rst) or !$rst){
                $this->exitWithReport("修改学生补考成绩失败！{$rst}");
            }
            ++$count;
        }
        $this->model->commit();
        $this->successWithReport("修改完成,共修改[{$count}]条记录 ！");
    }
//TODO:任课教师补考成绩输入
    /**
     * <弃用，整合到“任课教师成绩输入”中>任课教师补考成绩输入 课程列表页面
     */
    public function pageResitSelectNV(){
        $this->display('pageResitSelectNV');
    }
    /**
     * 任课教师补考成绩输入 获取课程列表
     * @param $year
     * @param $term
     * @param $schoolno
     * @param $classno
     * @param $teacherno
     * @param string $courseno
     */
    public function listResitSelect($year,$term,$schoolno,$classno,$teacherno,$courseno='%'){
        $rst = $this->model->getResitCourseTableList($year,$term,$schoolno,$courseno,$classno,$teacherno,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($rst)) $this->exitWithReport("查询补考成绩输入课程列表失败！{$rst}");
        $this->ajaxReturn($rst,'JSON');
    }


    /**
     * 任课教师补考成绩输入 成绩列表页面显示
     * 新增，区别于旧的版本
     * @param int  $year
     * @param int  $term
     * @param string  $coursegroup
     * @param string  $scoretype
     */
    public function pageResitInputNV($year=null,$term=null,$coursegroup=null,$scoretype=null){

        if(REQTAG === 'sync'){
            //同步学生名单
            $this->sync($year,$term,$coursegroup);
        }

        $this->assign('year_a',$year);
        $this->assign('year_b',intval($year)+1);
        $this->assign('term',$term);
        $this->assign('coursegroup',$coursegroup);
        $this->assign('scoretype',$scoretype);
        $scoretypetext = null;
        switch($scoretype){
            case 'ten':
                $scoretypetext = '百分制';
                break;
            case 'five':
                $scoretypetext = '五级制';
                break;
            case 'two':
                $scoretypetext = '二级制';
                break;
            default:
                $scoretypetext = '记分制不明确';
        }
        $this->assign('scoretypetext',$scoretypetext);

        $info = $this->model->getCourseInfo($year,$term,$coursegroup);
        if(is_string($info)){
            $this->headerUtf8();
            exit( "获取课程详细信息失败，请联系管理员协助解决你的问题！{$info}");
        }
        $this->assign('courseinfo',$info);//原先assignTT

        $teacherModel = new TeacherModel() ;
        $tinfo = $teacherModel->getTeachersBySchedulePlan($year,$term,$coursegroup);
        $this->assign('teachers',$tinfo);//原先assignTT

        $cgModel = new CourseGeneralScoreManagementModel();
        $rst = $cgModel->getCourseScoreSetting($coursegroup);
        if(is_string($rst)){
            exit('无法加载课程总评百分比计算信息，程序退出，请联系相关程序员协助解决您的问题！');
        }
        $this->assign('GSPS',$rst);
        $this->display('Input/pageResitInputNV');
    }

    /**
     * 任课老师补考成绩输入页面 学生成绩列表获取
     * @param int $year
     * @param int $term
     * @param string $courseno
     */
    public function listResitInputNV($year,$term,$courseno){
        $rst = $this->model->listStudentResitScoreByCourseno($year,$term,$courseno);
        if(is_string($rst)){
            $this->failedWithReport("获取期末考试成绩学生列表数据失败!{$rst}");
        }elseif(!$rst['total']){
            $rtn = $this->model->initCourseResitInputByCourseno($year,$term,$courseno);
            if(is_string($rtn)){
                $this->failedWithReport("初始化课号组号为{$courseno}的课程失败！{$rtn}");
            }
            $rst = $this->model->listStudentResitScoreByCourseno($year,$term,$courseno);
        }
        $this->ajaxReturn($rst,'JSON');
        exit;
    }
    /**
     * 任课教师补考成绩输入 补考成绩打印页面
     * @param int $year
     * @param int $term
     * @param string $courseno
     * @param string $scoretype
     * @param int $page
     */
    public function pageResitInputForPrintNV($year,$term,$courseno,$scoretype='ten',$page=1){

        $this->assign('year_a',$year);
        $this->assign('year_b',intval($year)+1);
        $this->assign('term',$term);
        $this->assign('coursegroup',$courseno);

        $this->assign('coursegroup',$courseno);
        $this->assign('scoretype',$scoretype);

        $this->assign('page',$page);

        $scoretypetext = null;
        switch($scoretype){
            case 'ten':
                $scoretypetext = '百分制';
                break;
            case 'five':
                $scoretypetext = '五级制';
                break;
            case 'two':
                $scoretypetext = '二级制';
                break;
            default:
                $scoretypetext = '记分制不明确';
        }
        $this->assign('scoretypetext',$scoretypetext);

        $info = $this->model->getCourseInfo($year,$term,$courseno);
        if(is_string($info)){
            header("Content-type:text/html;charset=utf-8");
            exit( "获取课程详细信息失败，请联系管理员协助解决你的问题！{$info}");
        }
//        mist($info);
        $this->assign('courseinfo',$info);//原先assignTT

        $teacherModel = new TeacherModel() ;
        $tinfo = $teacherModel->getTeachersBySchedulePlan($year,$term,$courseno);
        $this->assign('teachers',$tinfo);//原先assignTT

        $cgModel = new CourseGeneralScoreManagementModel();
        $rst = $cgModel->getCourseScoreSetting($courseno);
        if(is_string($rst)){
            exit('无法加载课程总评百分比计算信息，程序退出，请联系相关程序员协助解决您的问题！');
        }
        $this->assign('GSPS',$rst);//general score percent setting 简称
        $this->display('pageResitInputForPrintNV');
    }
    /**
     * 补考成绩输入 学生列表打印界面
     * @param int $year
     * @param int $term
     * @param string $courseno
     * @param int $page
     */
    public function pageResitInputForPrint($year,$term,$courseno,$page=1) {
        $courseModel = new CourseModel();
        $courseinfo = $courseModel->getCourseInfo($courseno);
        if(is_string($courseinfo)  or !$courseinfo){
            $this->exitWithReport("获取课程[{$courseno}]的信息失败!{$courseinfo}");
        }
        $this->assign('page',$page);
        $this->assign('courseinfo',$courseinfo[0]);
        $this->assign('year_a',$year);
        $this->assign('year_b',intval($year)+1);
        $this->assign('term',$term);
        $this->assign('courseno',$courseno);
        $this->display('Input/pageResitInputForPrint');
    }
//TODO:管理员补考成绩输入
    /**
     * 管理员成绩输入 课程列表页面(原先是补考是补考成绩输入界面)
     * @param int $year
     * @param int $term
     * @param string $classno
     * @param string $teachername
     * @param string $coursename
     */
    public function pageResitSelect($year=null,$term=null,$classno='%',$teachername='%',$coursename='%'){
        if(REQTAG === 'getlist'){
            $this->listCoursesSelectForAdmin($year,$term,$classno,$teachername,$coursename);
            exit();
        }

        //通用
        $this->xiala('schools','schools');
        $this->assign('isdean',$this->isDeanTeahcer());
        $this->display('pageResitSelect');
    }

    /**
     * 为管理员设置的解锁操作
     * @param $recno
     * @param $type
     * @throws Exception
     */
    public function unlockForAdmin($recno,$type){
        if($this->isdean === 1){
            $rst = $this->model->unlockForAdmin($recno,$type);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("解锁失败！{$rst}");
            }
            $this->successWithReport('解锁成功！ ');
        }
    }

    /**
     * @param null $year
     * @param null $term
     * @param string $classno
     * @param string $teachername
     * @param string $coursename
     */
    public function listCoursesSelectForAdmin($year=null,$term=null,$classno='%',$teachername='%',$coursename='%'){
        $this->ajaxReturn($this->model->listExamCourses($year,$term,$classno,$teachername,$coursename,
            $this->_pageDataIndex,$this->_pageSize));
        exit();
    }


    /**
     * 批量更新补考成绩输入
     * @param int $year
     * @param int $term
     * @param string $courseno 课号组号
     * @param array $rows 所有行全部数据
     * @param string $examtime 考试时间
     */
    public function updateResitScoreInBatchNV($year=null,$term=null,$courseno=null,$examtime=null,$rows=array()){
        //修改考试时间
        if(isset($courseno,$year,$term)){
            $rst = $this->model->updateResitExamtime($courseno,$year,$term,$examtime);
            if(is_string($rst) or !$rst) $this->failedWithReport("统一修改期末考试时间失败！{$rst}");
            //单纯修改时间 回复时间修改成功
            if(!$rows) $this->successWithReport("考试时间修改成功！");
        }
        //如果修改了时间  则 $rows一定不是空数组
        if(!$rows) $this->failedWithReport("提交为空，终止执行!");
        $this->model->startTrans();
        $count = 0;
        foreach($rows as $key=>$val){
            $studentno = $val['_origin']['studentno'];
            $recno = $val['_origin']['recno'];
            //修改补考成绩
            $rst = $this->model->isStudentResitLocked($courseno,$year,$term,$studentno);
            $rtn = $this->isInputable($year,$term);
            if(true !== $rtn){
                $this->failedWithReport($rtn);
            }
            if(is_string($rst)){
                $this->exitWithReport("课号[{$courseno}]学年[{$year}]学期[{$term}]学号[{$studentno}]后台确认学生补考是否锁定失败！{$rst}");
            }elseif($rst){
//                $this->exitWithReport("课号[{$courseno}]学年[{$year}]学期[{$term}]学号[{$studentno}]后台确认锁定，有问题请联系管理员!{$rst}");
                continue;
            }
            $rst = $this->model->updateStudentResitScoreByRecno($val['_origin']['resit_score'],$recno);
            if(is_string($rst) or !$rst){
                $this->exitWithReport("修改学生补考成绩失败！{$rst}");
            }
            ++$count;
        }

        //计算学分
        $ca = new CalculationAction();
        $c = $ca->calculateScoreCredit($year,$term,'%','%',$courseno,false);
        if(is_string($c)){
            $this->failedWithReport("计算学分的过程中出现错误！ {$c}");
        }

        $this->model->commit();
        $this->successWithReport("修改完成,共修改[{$count}]条记录 ！");
    }
//TODO:查看和开发课程
    /**
     * 开放与查看课程 页面
     */
    public function pageCoursesWithOpen(){
        if(REQTAG === 'lockAllInputed'){
            //锁定已有期末和总评成绩的学生
            $rst = $this->model->updateAllInputedLockStatus(1);
            if(is_string($rst)){
                $this->failedWithReport("操作失败！{$rst}");
            }
            $this->successWithReport("操作完成，共影响‘{$rst}’个学生！");
        }

        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->xiala('schools','schools');
        $this->display('Input/pageCoursesWithOpen');
    }
    /**
     * 开放与查看课程 课程列表数据获取
     * @param int $year
     * @param int $term
     * @param string $school
     * @param string $courseno
     * @param string $coursename
     * @param string $teachername
     */
    public function listCoursesWithOpen($year,$term,$school,$courseno='%',$coursename='%',$teachername='%'){
        $rst = $this->model->listCoursesWithOpenTableList($year,$term,$school,$courseno,$coursename,$teachername,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($rst)) $this->failedWithReport("获取失败!{$rst}");
        $this->ajaxReturn($rst,'JSON');
    }

    /**
     * 查看和开发课程 查看课程的学生列表
     * @param $year
     * @param $term
     * @param $coursegroup
     */
    public function listCoursesStudentsWithOpen($year,$term,$coursegroup) {
        $rst = $this->model->listCourseStudentsLockStatus($year,$term,$coursegroup,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($rst)){
            $this->failedWithReport("查询课程锁定情况的学生列表失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }
    /**
     * 批量结果单独的学生个体
     * @param int $type 成绩类型
     * @param array $students 选中学生信息
     * @param int $lock 默认为0，即解锁
     * @throws Exception
     */
    public function updateStudentLockInBatch($type,$students,$lock=0){
        $this->model->startTrans();
        foreach($students as $student){
            $recno = $student['recno'];
            $rst = $this->model->updateStudentLockStatusByRecno($recno,$type,0);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("学生'{$student['studentname']}'修改失败！");
            }
        }
        $this->model->commit();
        $this->successWithReport('修改成功！');
    }

    /**
     * 查看和开放课程导出excel
     * @param int $year
     * @param int $term
     * @param string $school
     * @param string $courseno
     * @param string $coursename
     * @param string $teachername
     */
    public function exportCoursesWithOpen($year,$term,$school,$courseno,$coursename,$teachername){
        //获取数据
        $rst = $this->model->listCoursesWithOpenTableList($year,$term,$school,$courseno,$coursename,$teachername);
        if(is_string($rst)){
            $this->failedWithReport("获取失败!{$rst}");
        }

        //初始化PHPExcel
        $this->model->initPHPExcel();

        //设置对齐信息和数据域
        $data['title'] = '课程选课情况';
        $data['head'] = array(
            'coursegroup' => array( '课号', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'coursename' => array(0=> '课程名称','align' => CommonModel::ALI_CENTER,'width'=>30),
            'approach' => array( '修课方式', 'align' => CommonModel::ALI_CENTER),
            'examtype' => array( '考核方式', 'align' => CommonModel::ALI_CENTER,'width'=>30),
            'finals_lock' => array( '锁定输入学生数', 'align' => CommonModel::ALI_CENTER,'width'=>30),
        );
        $data['body'] = $rst['rows'];

        //输出Excel文件
        $this->model->fullyExportExcelFile($data, $data['title']);
    }

    /**
     * 一键锁定
     * @param int $scoretype 成绩类型
     * @param int $lock 1表示将要进行上锁操作，0表示不进行上锁操作
     */
    public function updateAllLock($scoretype,$lock=0){
        $rst = $this->model->updateAllLock($scoretype,$lock);
        if(is_string($rst)){
            $this->exitWithReport("一键修改失败！{$rst}");
        }else{
            $this->successWithReport("一键修改成功！{$rst}");
        }
    }

    /**
     * 批量修改期中成绩输入状态
     * @param $year
     * @param $term
     * @param $courses
     * @param int $lock 0为解锁操作(默认)，1为上锁
     */
    public function updateMidtermLockStatusInBatch($year,$term,$courses,$lock=0){
        $this->model->startTrans();
        foreach($courses as $course){
            $coursegroup = $course['coursegroup'];
            $rtn = $this->isInputable($year,$term);
            if(true !== $rtn) $this->failedWithReport($rtn);
            $rst = $this->model->updateStudentMidtermLockStatusByCoursegroup($year,$term,$coursegroup,$lock);
            if(is_string($rst) or !$rst) $this->failedWithReport("修改第{$year}学年第{$term}学期课号为{$coursegroup}的课程期中成绩输入状态失败！{$rst}");
        }
        $this->model->commit();
        $this->successWithReport("修改课程期末成绩输入状态成功！");
    }

    /**
     * 批量修改课程的期末成绩输入状态
     * @param int $year
     * @param int $term
     * @param array $courses 课程数组
     * @param int $lock 0为解锁操作(默认)，1为上锁
     */
    public function updateFinalsLockStatusInBatch($year,$term,$courses,$lock=0){
        $this->model->startTrans();
        foreach($courses as $course){
            $coursegroup = $course['coursegroup'];
            $rtn = $this->isInputable($year,$term);
            if(true !== $rtn) $this->failedWithReport($rtn);
            $rst = $this->model->updateStudentFinalsLockStatusByCoursegroup($year,$term,$coursegroup,0);
            if(is_string($rst) or !$rst) $this->failedWithReport("修改第{$year}学年第{$term}学期课号为{$coursegroup}的课程期末成绩输入状态失败！{$rst}");
        }
        $this->model->commit();
        $this->successWithReport("修改课程期末成绩输入状态成功！");
    }

    /**
     * 批量修改课程的补考成绩输入状态
     * @param int $year
     * @param int $term
     * @param array $courses
     * @param int $lock 0为解锁操作(默认)，1为上锁
     */
    public function updateResitLockStatusInBatch($year,$term,$courses,$lock=0){
        $this->model->startTrans();
        foreach($courses as $course){
            $courseno = $course['coursegroup'];
            $rtn = $this->isInputable($year,$term);
            if(true !== $rtn){
                $this->failedWithReport($rtn);
            }
            $rst = $this->model->updateStudentResitLockStatusByCoursegroup($year,$term,$courseno,$lock);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改第{$year}学年第{$term}学期课号为{$courseno}的课程期末成绩输入状态失败！{$rst}");
            }
        }
        $this->model->commit();
        $this->successWithReport("修改课程期末成绩输入状态成功！");
    }
    /**
     * <暂时未使用>解锁 学生的补考成绩输入
     * @param int $year
     * @param int $term
     * @param string $courseno
     * @param int $lock 0为解锁操作(默认)，1为上锁
     */
    public function updateResitLockStatus($year,$term,$courseno,$lock=0){
        $rst = $this->model->updateStudentResitLockStatusByCoursegroup($year,$term,$courseno,0);
        if(is_string($rst) or !$rst){
            $this->exitWithReport("解锁第{$year}学年第{$term}学期课号为{$courseno}的补考成绩输入失败!{$rst}");
        }
        $this->successWithReport("解锁第{$year}学年第{$term}学期课号为{$courseno}的补考成绩输入成功!{$rst}");
    }
//TODO:还没有输入的课程
    /**
     * 还未输入成绩课程 页面
     */
    public function pageCoursesWhichScoreInputness(){
        $this->xiala('schools','schools');
        $this->display('Input/pageCoursesWhichScoreInputness');
    }

    /**
     * 还未输入成绩 课程列表显示
     * @param int $year
     * @param int $term
     * @param string  $school
     * @param string  $courseno
     * @param string  $coursename
     * @param string  $teachername
     * @param int $opera
     * @param int $scoretype 成绩类型
     */
    public function listCoursesWhichScoreInputness($year=null,$term=null,$school=null,$courseno=null,$coursename=null,$teachername=null,$opera=0,$scoretype=1){
        $rst = $this->model->listCoursesWhoseScoreInputness($year,$term,$school,$courseno,$coursename,$teachername,$scoretype,$this->_pageDataIndex,$this->_pageSize,$opera);
        if(is_string($rst)){
            exit("查询未输入成成绩的课程失败!{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }

//TODO:公共方法
    /**
     * 检查是否允许成绩输入
     * @param int  $year
     * @param int  $term
     * @param string  $time
     * @return bool|string
     */
    protected function isInputable($year=null,$term=null,$time=null) {
        isset($time) or $time = date('Y-m-d');
        isset($year) or $year = $this->_yearterm['YEAR'];
        isset($term) or $term = $this->_yearterm['TERM'];
        $gateModel = new GateModel();
        $rst = $gateModel->isResultInputable($year,$term,$time);
        return $rst;
    }

//TODO:是否可用未知
    /**
     * 学生列表 for 补考成绩打印界面
     * @param int $year
     * @param int $term
     * @param string $courseno
     * @param int $page
     */
    public function listResitInputForPrint($year,$term,$courseno,$page=1){
        $start  = ($page-1)*120;
        $end    = $page*120;
        $rst = $this->model->listStudentResitScoreByCourseno($year,$term,$courseno,$start,$end);
        if(is_string($rst)){
            $this->exitWithReport("查询第[{$year}]学年第[{$term}]学期课号为[{$courseno}]的补考学生列表失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
        exit;
    }
    /**
     * 获取补考成绩输入界面学生列表数据
     * @param int $year
     * @param int $term
     * @param string $courseno
     */
    public function listResitStudent($year,$term,$courseno){
        $rst = $this->model->listStudentResitScoreByCourseno($year,$term,$courseno);
        if(is_string($rst)){
            $this->exitWithReport("查询第[{$year}]学年第[{$term}]学期课号为[{$courseno}]的补考学生列表失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
        exit;
    }
    /**
     * 毕业重修成绩输入 页面
     */
    public function pageRetakeSelect(){
        $this->xiala('schools','schools');
        $this->display('Input/pageRetakeSelect');
    }

    /**
     * 毕业重修成绩输入 课程列表数据获取
     * @param int $year
     * @param int $term
     * @param string $coursegroupno
     * @param string $school
     */
    public function listRetakeSelect($year,$term,$coursegroupno,$school){
        $rst = $this->model->getRetakeCourseList($year,$term,$coursegroupno,$school,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($rst)){
            $this->exitWithReport("查询失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }

    /**
     * 毕业前补考成绩输入 直接套用期末成绩输入，将标题修改
     * @param int $year
     * @param int $term
     * @param string $coursegroupno
     * @param string $scoretype
     */
    public function pageRetakeInput($year,$term,$coursegroupno,$scoretype){
//        $isretake = true;
        $this->pageFinalsInput($year,$term,$coursegroupno,$scoretype);
    }


    /**
     * 根据学号信息获取学生数据
     * @param string $studentno
     * @param bool $innercall 是否是内部调用，true时只返回结果数据
     * @return array|int|string
     */
    public function selectStudentInfo($studentno,$innercall=false){
        $studentModel = new StatusModel();
        $rst = $studentModel->getStudentInfo($studentno);
//        mist($rst[0],array_slice($rst[0],0,17));
        $rst = array_slice($rst[0],0,17);
        if(is_string($rst)){
            $this->failedWithReport("检验学号为[{$studentno}]的学生是否存在失败！{$rst}");
        }elseif(!$rst){
            $this->failedWithReport("学号为[{$studentno}]的学生不存在！{$rst}");
        }
        if($innercall){
            return $rst;
        }
        $this->ajaxReturn($rst,'JSON');
        exit;
    }
}