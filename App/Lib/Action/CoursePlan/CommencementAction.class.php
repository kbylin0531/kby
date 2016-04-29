<?php
/**
 * 开课计划
 * User: cwebs
 * Date: 14-2-23
 * Time: 上午8:47
 */

/**
 * Class CommencementAction
 * Commencement:意思是开始、开端、毕业典礼、学位授予典礼
 */
class CommencementAction extends RightAction {


    /**
     * 开课计划对应的模型
     * @var CoursePlanModel
     */
    private $model;

    /**
     * 返回信息
     * @var array
     */
    protected $message = array('type'=>'info','message'=>'','dbError'=>'');

    /**
     * 登录的教师信息
     * @var array
     */
    private $teacher;

    public function __construct(){
        parent::__construct();
        $this->model = new CoursePlanModel();

        $this->teacher = $this->model->getTeacherFromSession(session("S_GUID"));

        $this->assign("theacher", $this->teacher);
    }

    /**
     * 新建开课计划
     */
    public function create(){
        $yearTerm =$this->model->getYearTerm('O');
        if(is_string($yearTerm)){
            $this->exitWithReport('查询学年学期过程中出现错误！'.$yearTerm);
        }
        $this->assign('yearTerm',$yearTerm);
        $this->assign('courseApproches',$this->model->getComboCourseApproches());
        $this->assign('coursetypeoption',$this->model->getComboCourseTypeOption());
        $this->assign('examType',$this->model->getComboExamType());

        $this->assign('tgroup',$this->model->sqlQuery('SELECT TGROUP,RTRIM(NAME) as NAME from TGROUPS order by ORDERBY '));
        $this->display('create');
        exit;
    }

    /**
     * 提交结果显示
     * @param string $msg 显示信息,如果不提供参数或者参数为NULL，则认为是成功的提交
     */
    private function exitWithCreatePage($msg=''){
        $this->assign('error',$msg);
        $this->create();
        exit;
    }

    /**
     * 新增开课计划 表单提交页
     */
    public function save(){
        $yearTerm =$this->model->getYearTerm('O');
        if(is_string($yearTerm)){
            $this->exitWithCreatePage('查询学年学期过程中出现错误！');
        }
        $this->assign('yearTerm',$yearTerm);

        // 检测传入的参数
        if(VarIsIntval("YEAR,TERM")==false || VarIsNotEmpty("COURSENO,GROUP,CLASSNO,COURSETYPE,EXAMTYPE,CATEGORY")==false){
            $this->exitWithCreatePage('输入的参数有错误，非法提交数据！');
        }
        $school = $this->model->getCourseSchool($_REQUEST['COURSENO']);
        if($school === false){
            $this->exitWithCreatePage('课程不存在！！');
        }elseif($this->teacher["SCHOOL"]!=$school && !isDeanByUsername(getUsername())){
            $this->exitWithCreatePage('无法操作其他学院课程！！');
        }


        $rst = $this->model->getClass($_REQUEST['CLASSNO'],true);
        if(is_string($rst)){
            $this->exitWithCreatePage('查询班级是否存在的过程中出现了错误！');
        }elseif(!$rst){
            $this->exitWithCreatePage("[{$_REQUEST["CLASSNO"]}]班号在班级库里没有找到！此班级并不存在！！");
        }

        $strWeeks = "";
        for($i=1;$i<=$yearTerm["WEEKS"];$i++){
            $strWeeks .= $_REQUEST["Week"][$i]==$i ? 1 : 0;
        }
        $_REQUEST["WEEKS"] = bindec(strrev($strWeeks));
        $_REQUEST["SCHOOL"] =$school;
        $_REQUEST["ATTENDENTS"] = intval($_REQUEST["ATTENDENTS"]);

        $bind = $this->model->getBind('YEAR,TERM,COURSENO,CLASSNO,SCHOOL,WEEKS,GROUP,COURSETYPE,EXAMTYPE,ATTENDENTS,REM,LIMITGROUPNO,LIMITNUM,CREDITS,CATEGORY,LIMITCREDIT', $_REQUEST);
        $rst = $this->model->insertCoursePlan($bind);
        if(is_string($rst) || !$rst){
            $this->exitWithCreatePage('新增开课计划失败了');
        }else{
            $this->exitWithCreatePage('开课计划添加成功!');
        }
    }


    /**
     * 考克计划
     * @param $types
     * @param $flag
     * @param string $year 前端传递的学年
     * @param string $term 前端传递的学期
     * @param string $grade
     * @param string $schoolno
     * @param string $classno
     * @throws Exception
     */
    private function startCoursePlan($types,$flag,$year,$term,$grade='%',$schoolno='%',$classno='%'){
        $this->model->startTrans();
        //清空原有数据
        $rst = $this->model->clearCoursePlan($year,$term,$grade,$schoolno,$classno,$types,$flag);
        if(is_string($rst) ){
            $this->model->rollback();
            $this->exitWithReport('<font color="red">清空已有开课计划发生错误，自动创建开课计划失败！</font>'.$rst);
        }
        //班级年级自增
        $data = $this->model->updateClassesGrade($year);
        if(is_string($data) or !$data){
            $this->model->rollback();
            $this->exitWithReport('<font color="red">计算班级开课等级时发生错误，自动创建开课计划失败！</font>'.$data);
        }
        //开课执行
        $rst = $this->model->startCoursePlan($types,$grade,$schoolno,$classno,$flag);
        if(is_int($rst)){
            $this->model->commit();
            $this->exitWithReport("{$rst}条开课计划已成功导入！",'info');
        }else{
            $this->model->rollback();
            $this->exitWithReport('执行过程中出现错误，错误信息'.$rst,'info');
        }
    }

    /**
     * 获取学年学期课程最大组号+1
     */

    public function getCourseGroupNo()
    {
        $arr = array();
        $sql ='select dbo.fn_10to36(dbo.fn_36to10(MAX([GROUP]))+1) as [group] from COURSEPLAN where YEAR=:COL_YEAR and TERM=:COL_TERM and COURSENO  = :COURSENO group by YEAR,TERM,COURSENO';

        $bind = array(
            ':COL_YEAR'=>intval($_REQUEST['YEAR']),
            ':COL_TERM'=>intval($_REQUEST['TERM']),
            ':COURSENO'  => $_REQUEST['COURSENO'],
        );
        $result = $this->model->sqlFind($sql,$bind);
        if($result)
        {
            $arr['r']='success';
            $arr['data']=$result;
            $this->ajaxReturn($arr);
        }else
        {
            $arr['r']='err';
            $arr['data']='获取最大组号失败！';
            $this->ajaxReturn($arr);
        }

    }

    /**
     * 修改开课计划
     */
    public function update() {
        $yearTerm = $this->model->getYearTerm('O');
        //: 检测传入的参数
        if(VarIsIntval("YEAR,TERM")==false || VarIsNotEmpty("COURSENO,GROUP,CLASSNO,COURSETYPE,CATEGORY,EXAMTYPE,NEWGROUP,CREDITS,NEWCLASSNO,WEEKS")==false){
            $this->exitWithReport('输入的参数不完整！');
        }

        //检测课程存在，并且是否为本院开设课程
        $course = $this->model->getCourse($_REQUEST['COURSENO']);
        if(is_string($course)){
            $this->exitWithReport('获取课程信息失败了！'.$course);
        }elseif(!$course){
            $this->exitWithReport("无法查询到课程号为[{$_REQUEST["COURSENO"]}]的课程信息！");
        }elseif($this->teacher["SCHOOL"]!=$course[0]["SCHOOL"]&&!isDeanByUsername(getUsername())) {
            $this->exitWithReport("检测到你学院的代号为[{$this->teacher["SCHOOL"]}]，非教务处管理人员无法修改学院代号为[{$course[0]["SCHOOL"]}]的开课计划".serialize($course));
        }
        //检查班级是否都存在
        $newClassno = trim($_REQUEST['NEWCLASSNO']);
        $classes = explode('#',$newClassno);
        foreach($classes as $classno){
            //检测班级
            $class = $this->model->getClass($classno);
            if(is_string($class)){
                $this->exitWithReport('获取班级信息失败了！'.$class);
            }elseif(!$class){
                $this->exitWithReport("无法查询到班级号为[$classno]的班级信息！");
            }
        }
        //检查是否穿在对应班级的记录
        //TODO:[CLASSNO] === [NEWCLASSNO]，可以与上面合并到一起
        $courseplan = null;//保存其中一个courseplan
        $classes = explode('#',trim($_REQUEST['CLASSNO']));
        foreach($classes as $classno){
            //检测开课记录
            $whr = array(
                'YEAR'=>$_REQUEST['YEAR'],
                'TERM'=>$_REQUEST['TERM'],
                'COURSENO'=>$_REQUEST['COURSENO'],
                '[GROUP]'=>array($_REQUEST['GROUP'],true),
                'CLASSNO'=>$classno,
            );
            $courseplan = $this->model->getCoursePlan($whr);
            if(is_string($courseplan)){
                $this->exitWithReport('获取开课记录信息失败了！'.$courseplan);
            }elseif(!$courseplan){
                $this->exitWithReport('无法查询到指定的开课记录！');
            }
        }
        $courseplan = $courseplan[0];
        //------------ 开课计划修改 ------------------------//
        $weeks = bindec(strrev($_REQUEST["WEEKS"]));
        $this->model->startTrans();
        //合并后的courseplan以下信息应该是相同的
        $filter01 = array(
            'YEAR' => $courseplan['YEAR'],
            'TERM' => $courseplan['TERM'],
            'COURSENO' => $courseplan['COURSENO'],
            'GROUP' => array($courseplan['GROUP'],true),
        );

        //更新排课计划表
        $rst = $this->model->updateSchedulePlan(
            array(
                '[GROUP]=:gp'    => array(':gp',$_REQUEST['NEWGROUP']),
                'WEEKS=:weeks'   => array(':weeks',$weeks),
            ),
            $filter01
        );
        if(is_string($rst)){
            $this->exitWithReport('更新排课计划的过程中出现错误!'.$rst);
        }
        //缺少排课计划
//        elseif(!$rst){
//            $this->exitWithReport("未能成功更新第[{$courseplan['YEAR']}]学年第[{$courseplan['TERM']}]学期课号组号为[{$courseplan['COURSENO']}-{$courseplan['GROUP']}]的排课计划");
//        }

        //更新课程时间表
        $rst = $this->model->updateSchedule(
            array(
                '[GROUP]=:gp'    => array(':gp',$_REQUEST['NEWGROUP']),
                'WEEKS=:weeks'   => array(':weeks',$weeks),
            ),
            $filter01
        );
        if(is_string($rst)){
            $this->exitWithReport('更新课程时间表的过程中出现错误!'.$rst);
        }
        //更新SCHEDULE表，可能不能查询到数据
//        elseif(!$rst){
//            $this->exitWithReport("未能成功更新第[{$courseplan['YEAR']}]学年第[{$courseplan['TERM']}]学期课号组号为[{$courseplan['COURSENO']}-{$courseplan['GROUP']}]的课程时间！");
//        }
        //修改学生修课计划表
        $rst = $this->model->updateStudentCoursePlan(
            array(
                '[GROUP]=:gp' => array(':gp',$_REQUEST['NEWGROUP']),
            ),
            $filter01
        );
        if(is_string($rst)){
            $this->exitWithReport('更新学生修课计划的过程中出现错误!'.$rst);
        }
        //查询不到
//        elseif(!$rst){
//            $this->exitWithReport("未能成功更新第[{$courseplan['YEAR']}]学年第[{$courseplan['TERM']}]学期课号组号为[{$courseplan['COURSENO']}-{$courseplan['GROUP']}]的学生修课计划！");
//        }
        //修改排课计划
        $classnos = explode('#',$_REQUEST['NEWCLASSNO']);
        foreach($classnos as $clsno){
            $rst = $this->model->updateCoursePlan(
                array(
                    'CLASSNO=:clsno'=>array(':clsno',$clsno),
                    'ATTENDENTS=:atd'=>array(':atd',$_REQUEST['ATTENDENTS']),
                    'REM=:rem'=>array(':rem',$_REQUEST['REM']),
                    'WEEKS=:wek'=>array(':wek',$weeks),
                    '[GROUP]=:gp'=>array(':gp',$_REQUEST['NEWGROUP']),
                    'COURSETYPE=:ct'=>array(':ct',$_REQUEST['COURSETYPE']),
                    'course_type_options=:cto'=>array(':cto',$_REQUEST['CATEGORY']),
                    'EXAMTYPE=:et'=>array(':et',$_REQUEST['EXAMTYPE']),
                    'LIMITGROUPNO=:lg'=>array(':lg',$_REQUEST['LIMITGROUPNO']),
                    'LIMITNUM=:ln'=>array(':ln',$_REQUEST['LIMITNUM']),
                    'CREDITS'        => $_REQUEST['CREDITS'],
                ),
                array_merge($filter01,array(
                    'CLASSNO' => $clsno,
                ))
            );
            if(is_string($rst)){
                $this->exitWithReport('更新开课计划的过程中出现错误!'.$rst);
            }
            //可能不存在该教学计划
            elseif(!$rst){
                $this->exitWithReport("未能成功更新第[{$courseplan['YEAR']}]学年第[{$courseplan['TERM']}]学期课号组号为[{$courseplan['COURSENO']}-{$courseplan['GROUP']}]的，班级号为[{$courseplan["CLASSNO"]}]开课计划！");
            }
        }
        $this->model->commit();
        $this->exitWithReport('开课计划修改成功!','info');
    }
    
    /**
     * 修改开课计划人数
     * INSERT INTO METHODS VALUES ('PLUA', 'A', '修改开课计划人数', 'CoursePlan/Commencement/updateATTENDENTS');
     * @param int $total_attendents_limit
     */
    public function updateATTENDENTS($total_attendents_limit=0){
        //批量修改
        if(REQTAG){
            if(empty($_POST['rows'])){
                $this->exitWithReport('未选择开课记录进行修改！');
            }
            $attends = intval($_POST['newAttends']);

            //修改操作
            if(REQTAG == 'updInBatch'){
                foreach($_POST['rows'] as $cp){
                    //修改班级人数
                    $rst = $this->updateCoursePlanAttendance(
                        $cp['YEAR'],
                        $cp['TERM'],
                        $cp['COURSENO'],
                        $cp['GROUP'],
                        explode('#',$cp['CLASSNO']),
                        $attends,
                        null
                    );
                    is_string($rst) and $this->exitWithReport($rst);
                }
            }elseif(REQTAG == 'updInBatchB'){
                foreach($_POST['rows'] as $cp){
                    //修改教学班人数
                    $rst = $this->updateCoursePlanAttendance(
                        $cp['YEAR'],
                        $cp['TERM'],
                        $cp['COURSENO'],
                        $cp['GROUP'],
                        explode('#',$cp['CLASSNO']),
                        null,
                        $attends
                    );
                    is_string($rst) and $this->exitWithReport($rst);
                }
            }

            $this->exitWithReport(count($_POST['rows']).'条开课计划人数修改成功！','info');
        }

//        mist($_POST,$_REQUEST);

        //单条记录
        $rst = $this->updateCoursePlanAttendance(
            $_POST['YEAR'],
            $_POST['TERM'],
            $_POST['COURSENO'],
            $_POST['GROUP'],
            explode('#',$_POST['CLASSNO']),
            intval($_POST['ATTENDENTS']),
            $total_attendents_limit
        );
        $this->exitWithReport(is_string($rst)?$rst:'开课计划人数修改成功！','info');
    }


    /**
     * 修改开课计划人数
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @param $classnoes
     * @param null $attendance null时表示不修改
     * @param null $totalAtendance null时表示不修改
     * @return bool|string true表示成功,string表示错误信息
     */
    private function updateCoursePlanAttendance($year,$term,$courseno,$groupno,$classnoes,$attendance=null,$totalAtendance=null){
        $classAmount = count($classnoes);
        $reducePiece = isset($attendance)?floor($attendance/$classAmount):null;//每个班级分到的数量
        $firstPiece = $attendance-$reducePiece*($classAmount-1);//第一个班级减少特别量
       $yu = $attendance%$classAmount;
        /*
                $this->model->startTrans();
                $sql1 = 'update COURSEPLAN set ATTENDENTS=:ATTENDENTS,total_attendents_limit=:TOTAL
         where [YEAR]=:YEAR and TERM=:TERM and COURSENO=:COURSENO AND [GROUP]=:GROUP';

                $bind1 = array(
                    ':ATTENDENTS'=>$reducePiece,
                    ':TOTAL' => $totalAtendance,
                    ':YEAR'  => $year,
                    ':TERM'  => $term,
                    ':COURSENO'  => $courseno,
                    ':GROUP' => $groupno
                );
                $r1 = $this->model->sqlExecute($sql1,$bind1);

                $sql2 = "update top ($yu) COURSEPLAN set ATTENDENTS=ATTENDENTS+1
         where [YEAR]=:YEAR and TERM=:TERM and COURSENO=:COURSENO AND [GROUP]=:GROUP";

                $bind2 = array(
                    ':YEAR'  => $year,
                    ':TERM'  => $term,
                    ':COURSENO'  => $courseno,
                    ':GROUP' => $groupno
                );
                $r2 = $this->model->sqlExecute($sql2,$bind2);
                if(is_string($r1)|| is_string($r2)) {
                    $this->model->rollback();
                    return '修改开课计划认识发生错误！'.$r1.$r2;
                }
                $this->model->commit();
             //   echo $reducePiece.'****'.$yu;
                return true;*/

        $flag = 1;

        $where = array(
            'YEAR'  => $year,
            'TERM'  => $term,
            'COURSENO'  => $courseno,
            'GROUP' => array($groupno,true),
        );

        $this->model->startTrans();
        foreach($classnoes as $classno){
            $where['CLASSNO'] = $classno;
            $rst = $this->model->updateCoursePlanAttendance($where,
                isset($attendance)?(($flag<=$yu)?$reducePiece+1:$reducePiece):null,
                $totalAtendance
            );
            $flag += 1;
            if(is_string($rst)){
                $this->model->rollback();
                return '修改开课计划认识发生错误！'.$rst;
            }elseif(!$rst){
                $this->model->rollback();
                return "未能成功修改班级[{$classno}]的开课计划！";
            }
        }
        $this->model->commit();
        return true;
    }


    	
    /**
     * 开课计划查询 分班
     */
    public function split(){
        static $maxGroupno = null;

        if(!is_array($_REQUEST['ITEM']) || !count($_REQUEST['ITEM'])){
            $this->exitWithReport('输入的参数有错误！');
        }

        $_REQUEST['ITEM'] = CoursePlanModel::splitItem($_REQUEST['ITEM']);

        $coursePlanFirst = explode(',',$_REQUEST['ITEM'][0]);
        $coursePlanFirstCoursenoAndGroupno = trim($coursePlanFirst[2]).trim($coursePlanFirst[3]);

        $this->model->startTrans();

        foreach($_REQUEST['ITEM'] as $coursePlanItem){

            $items = @explode(',',$coursePlanItem);
            if(count($items)!=5) {
                $this->model->rollback();
                $this->exitWithReport('输入参数项子条目数不为5，请更换浏览器重试！');
            }
            $year = intval($items[0]);
            $term = intval($items[1]);
            $courseno = trim($items[2]);
            $group = trim($items[3]);
            $classno = trim($items[4]);

            if($coursePlanFirstCoursenoAndGroupno != $courseno.$group){
                $this->model->rollback();
                $this->exitWithReport('错误:组号不一样！');
            }
            //检测课程存在，并且是否为本院开设课程
            $data = $this->model->getCourse($courseno);
            if(is_string($data) ){
                $this->model->rollback();
                $this->exitWithReport("获取课程号为[{$courseno}]的课程的详细信息失败!!");
            }elseif(!$data){
                $this->model->rollback();
                $this->exitWithReport("未查询到课程号为[{$courseno}]的课程!!");
            }
            if($this->teacher["SCHOOL"] != $data["SCHOOL"] && !isDeanByUsername(getUsername())) {
                $this->model->rollback();
                $this->exitWithReport("课号[{$courseno}][{$group}]不是本学部开设课程！");
            }


            //检测排课中是否存在
            $data = $this->model->getSchedulePlanList(array(
                'YEAR'=>$year,
                'TERM'=>$term,
                'COURSENO'=>$courseno,
                '[GROUP]'=>array($group,'like'),
            ),true);
            if(is_string($data)){
                $this->model->rollback();
                $this->exitWithReport("查询第[{$year}]学年第[{$term}]学期课程组号为[{$courseno}-{$group}]的排课计划失败了！");
            }elseif($data){
                $this->model->rollback();
                $this->exitWithReport("<font color='red'>课号组号为[{$courseno}][{$group}]已以送到排课计划！</font><br />");
            }

            // 检测开课计划存在
            $map = array(
                'YEAR' => $year,
                'TERM' => $term,
                'COURSENO' => $courseno,
                'CLASSNO' => $classno,
                'GROUP' => array($group,true),
            );//$map 正好是主键的位置
            $data = $this->model->getCoursePlan($map);
            if(is_string($data)){
                $this->model->rollback();
                $this->exitWithReport("查询第[{$year}]学年第[{$term}]学期课程组号为[{$courseno}-{$group}]，班级号为[{$classno}]的开课计划失败了！");
            }elseif(!$data){
                $this->model->rollback();
                $this->exitWithReport("未查询到第[{$year}]学年第[{$term}]学期课程组号为[{$courseno}-{$group}]，班级号为[{$classno}]的开课计划！");
            }
            $courseplan = $data[0];


            //获取最大组号
            if(!isset($maxGroupno)){
                $rst = $this->model->getCoursePlanMaxGroupno(array(
                    ':YEAR' => $year,
                    ':TERM' => $term,
                    ':COURSENO' => $courseno,
                ));
                if($rst[0] == 'error'){
                    $this->model->rollback();
                    $this->exitWithReport('获取最大组号失败了'.$rst);
                }elseif(NULL === $rst[1]){
                    $this->model->rollback();
                    $this->exitWithReport("查询不到第[{$year}]学年第[{$term}]学期课程号为[{$courseno}]的课程计划的最大组号".$rst);
                }
                $maxGroupno = $rst[1] + 1;//得到最大组号
            }else{
                ++$maxGroupno;
            }


            $rst = $this->model->updateCoursePlan(
                array(
                    '[GROUP]=dbo.fn_10to36(:GP)' => array(':GP',$maxGroupno)
                ),
                array(
                    'YEAR'  => $courseplan['YEAR'],
                    'TERM'  => $courseplan['TERM'],
                    'COURSENO'  => $courseplan['COURSENO'],
                    'CLASSNO'  => $courseplan['CLASSNO'],
                    'GROUP'  => array($courseplan['GROUP'],true),
                )
            );
            if(is_string($rst)){
                $this->model->rollback();
                $this->exitWithReport('更新开课计划失败！'.$rst);
            }elseif(!$rst){
                $this->model->rollback();
                $this->exitWithReport(
                    "未能成功修改第[{$courseplan['YEAR']}]学年第[{$courseplan['TERM']}]学期课程组号为[{$courseplan['COURSENO']}-{$courseplan['GROUP']}]，班级号为[{$courseplan['CLASSNO']}]的开课计划！"
                );
            }
        }

        $this->model->commit();
        $this->exitWithReport('分班成功！','info');
    }


    /**
     * 同相同课号，不同组号的课程可以一次性的合并为一条记录
     */
    private  function automerge(){
        $sql = 'SELECT * from COURSEPLAN
                            WHERE [YEAR]=:YEAR
                               AND [TERM]=:TERM
                               AND [COURSENO] LIKE :COURSENO
                               AND [GROUP] LIKE :GROUP
                               AND [SCHOOL] LIKE :SCHOOL
                               AND [COURSETYPE] LIKE :COURSETYPE
                               AND [CLASSNO] LIKE :CLASSNO
                               AND [EXAMTYPE] LIKE :EXAMTYPE
                               and course_type_options LIKE :CATEGORY';
        $bind = $this->model->getBind('YEAR,TERM,COURSENO,GROUP,SCHOOL,COURSETYPE,CLASSNO,EXAMTYPE,CATEGORY',$_REQUEST,'%');

        $rst = $this->model->sqlQuery($sql,$bind);
        if($rst === false){
            $this->failedWithReport('错误信息:'.$this->model->getDbError());
        }
        $inflines = 0;
        $count = 0;
        $year = $_POST['YEAR'];
        $term = $_POST['TERM'];
        $courseplanlist = array();//以课程号为键名
        foreach($rst as $val){
            $key = trim($val['COURSENO']);
            if(!array_key_exists($key,$courseplanlist)){
                $courseplanlist[$key] = array();
            }
            $courseplanlist[$key][] = $val;
        }

        $this->model->startTrans();
        foreach($courseplanlist as $key=>$coursegroup){
            $maxGroup = $this->model->getCourseMaxGroupno($year,$term,$key);
            if(is_string($maxGroup)){
                $this->model->rollback();
                $this->failedWithReport('获取最大组号的过程中出现错误'.$maxGroup);
            }

            $maxGroup = $maxGroup + 1;
            //设置共同组号
            foreach($coursegroup as $item){
                //修改每一条记录
                $classes = explode('#',$item['CLASSNO']);
                foreach($classes as $classno){
                    $rst = $this->model->updateCoursePlanOfGroupno($year,$term,$item['COURSENO'],$item['GROUP'],$classno,$maxGroup);
                    if(is_string($rst)){
                        $this->model->rollback();
                        $this->exitWithReport('更新组号的过程中出现错误，可能是有重复的班级记录！'.$this->model->getErrorMessage());
                    }
                    ++$inflines;
                }
            }
            ++$count;
        }
        $this->model->commit();
        $this->exitWithReport("合并完成：共影响 $inflines 个课程",'info');
    }

    /**
     * 开课计划 合班处理
     */
    public function  merge(){
        //一键合并班级
        if(isset($_GET['tag']) && trim($_GET['tag']) == 'automerge'){
            $this->automerge();
            exit;
        }

        if(is_array($_REQUEST['ITEM'])==false || count($_REQUEST['ITEM'])==0){
            $this->exitWithReport('输入的参数有错误，非法提交数据！\n');
        }

        //标记总人数和组号和
        $combinedAttendents = 0;
        $combinedGroups = '';
        $arrItems = array('ATTENDENTS'=>0,'GROUPS'=>'');

        $_REQUEST['ITEM'] = CoursePlanModel::splitItem($_REQUEST['ITEM']);


        $itemsLength = count($_REQUEST['ITEM']);

        //标记第一个开课计划以进行4项比对的后台执行
        $flagship = null;

        for($i=0; $i<$itemsLength; $i++){
            //分解参数项
            $items = @explode(",",$_REQUEST['ITEM'][$i]);
            if(count($items)!=5){
                $this->exitWithReport('数据提交项出错，可能是前段错误，请尝试更换浏览器!！');
            }
            
            $arrItems[$i] = array(
                'YEAR'=>intval($items[0]),
                'TERM'=>intval($items[1]),
                'COURSENO'=>trim($items[2]),
                'GROUP'=>array(trim($items[3]),true),
                'CLASSNO'=>trim($items[4]),
            );

            //一.取得课程计划
            $data = $this->model->getCoursePlan($arrItems[$i]);
            if(is_string($data)) {
                $this->exitWithReport('获取课程开课详细信息失败！'.$data);
            }elseif(empty($data)) {
                $this->exitWithReport("无法查询到第[{$arrItems[$i]['YEAR']}]学年第[{$arrItems[$i]['TERM']}]学期，
                课程号为[{$arrItems[$i]['COURSENO']}]组号为[{$arrItems[$i]['GROUP']}]，班级号为[{$arrItems[$i]['CLASSNO']}]的开课计划！" . $data);
            }else{
                //确立比较对象是第一个开课计划
                if(NULL === $flagship){
                    $flagship = $data[0];
                }
            }
            //检查有效性
            $courseplan = $data[0];
//            if($this->teacher["SCHOOL"]!=$courseplan["SCHOOL"]&&!isDeanByUsername(getUsername())){
//                $this->exitWithReport("你所在的学院代号是[{$this->teacher['SCHOOL']}],
//                正试图修改学院代号为[{$courseplan["SCHOOL"]}]的开课计划，因为不是教务处管理人员，系统拒绝了你的合班要求！");
//            }else
                if(
                $courseplan['SCHOOL'] != $flagship['SCHOOL'] ||
                $courseplan['COURSENO'] != $flagship['COURSENO'] ||
                $courseplan['WEEKS'] != $flagship['WEEKS'] ||
                $courseplan['COURSETYPE'] != $flagship['COURSETYPE']
            ){
                $this->exitWithReport('无法合并');
            }

            $combinedAttendents += intval($courseplan['ATTENDENTS']);
            $combinedGroups .= trim($data["GROUP"]).'&';

            //二.检测排课中是否存在
            $map = array(
                'YEAR'=> $arrItems[$i]['YEAR'],
                'TERM'=> $arrItems[$i]['TERM'],
                'COURSENO'=> $arrItems[$i]['COURSENO'],
                '[GROUP]'=> $arrItems[$i]['GROUPS'],
            );
            $data = $this->model->getSchedulePlanList($map,true);
            if(is_string($data)){
                $this->exitWithReport('获取排课计划信息失败了'.$data);
            }elseif($data){
                $this->exitWithReport('开课计划已以送到排课计划！');
            }
        }

        // 取出最大组号
        $map = array(
            ':YEAR' => $arrItems[0]['YEAR'],
            ':TERM' => $arrItems[0]['TERM'],
            ':COURSENO' => $arrItems[0]['COURSENO'],
        );
        $rst = $this->model->getCoursePlanMaxGroupno($map);
        if($rst[0] == 'error'){
            $this->exitWithReport('获取最大组号的过程中出现错误！'.$rst[1]);
        }elseif(NULL === $rst[1]){
            $this->exitWithReport("无法从数据库中取得第[{$map['YEAR']}]学年第[{$map['TERM']}]学期课程号为[{$map['COURSENO']}]的课程计划的组号！");
        }
        $maxGroup = $rst[1] + 1 ;//十进制的最大组号值

        $this->model->startTrans();
        for($i=0;$i<$itemsLength;$i++){
            $rst = $this->model->updateCoursePlan(
                array(
                    ' [group]=dbo.fn_10to36(:GP) '=> array(':GP',$maxGroup),
                ),
                array(
                    ':YEAR'=>$arrItems[$i]['YEAR'],
                    ':TERM'=>$arrItems[$i]['TERM'],
                    ':COURSENO'=>$arrItems[$i]['COURSENO'],
                    ':CLASSNO'=>$arrItems[$i]['CLASSNO'],
                    ':GROUP'=>$arrItems[$i]['GROUP'],
                )
            );
//            vardump($arrItems[$i]['GROUP'][0],$arrItems[$i]['[GROUP]']);
            if(is_string($rst)){
                $this->model->rollback();
                $this->exitWithReport('更新课程计划过程中出现错误！'.$rst);
            }elseif(!$rst){
                $this->model->rollback();
                $this->exitWithReport("无法更新第[{$arrItems[$i]['YEAR']}]学年第[{$arrItems[$i]['TERM']}]学期，
                课程号为[{$arrItems[$i]['COURSENO']}]组号为[{$arrItems[$i]['GROUP'][0]}]，班级号为[{$arrItems[$i]['CLASSNO']}]的开课计划！");
            }
        }

        $this->model->commit();
        $this->exitWithReport('开课计划合班成功！','info');
    }

    /**
     * 删除开课计划
     */
    public function delete(){
        if(!is_array($_REQUEST["ITEM"]) || count($_REQUEST["ITEM"])==0){
            $this->message["message"] = "没有提交任一数据";
            $this->__done();
        }
        foreach($_REQUEST["ITEM"] as $item){
            $items = @explode(",",$item);
            if(count($items)!=4) continue;
            $this->model->startTrans();
            $bind = $this->model->getBind("YEAR1,TERM1,COURSENO1,GROUP1,YEAR2,TERM2,COURSENO2,GROUP2,YEAR3,TERM3,COURSENO3,GROUP3,YEAR4,TERM4,COURSENO4,GROUP4",
                array(
                    $items[0], $items[1], $items[2],$items[3],
                    $items[0], $items[1], $items[2],$items[3],
                    $items[0], $items[1], $items[2],$items[3],
                    $items[0], $items[1], $items[2],$items[3]));
            $data = $this->model->sqlExecute($this->model->getSqlMap("coursePlan/delete.sql"),$bind);
            if($data===false){
                $this->model->rollback();
            }
            $this->model->commit();
        }
        $this->message["message"] = "选择的开课计划已成功删除！";
        $this->__done();
    }


    private function exportCoursePlanList($listdata){
        $data = array();
        $this->model->initPHPExcel();
        $data['title'] = '开课计划数据导出';
        //表头
        $data['head'] = array(
            //默认值如 align type 的设计实例
            'YEAR' => array( '学年', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'TERM' => array('学期','align' => CommonModel::ALI_LEFT,'width'=>10),
            'COURSENO' => array('课程号','align' => CommonModel::ALI_LEFT,'width'=>10),
            'GROUP' => array( '组号', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'COURSENAME' => array( '课程名称', 'align' => CommonModel::ALI_CENTER,'width'=>20),
            'CLASSNAME' => array( '班级', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'CREDITS' => array( '学分', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'WEEKS' => array('周次','align' => CommonModel::ALI_LEFT,'width'=>20,'type'  => 1),
            'HOURS' => array('周课时','align' => CommonModel::ALI_LEFT,'width'=>10),
            'COURSETYPE' => array( '修课方式', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'CATEGORYNAME' => array( '课程类型', 'align' => CommonModel::ALI_CENTER,'width'=>20),
            'EXAMTYPE' => array( '考核方式', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'ATTENDENTS' => array('参加人数','align' => CommonModel::ALI_LEFT,'width'=>10),
            'SCHOOLNAME' => array( '开课学院', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'LIMITGROUPNO' => array( '限选组号', 'align' => CommonModel::ALI_CENTER,'width'=>10),
            'LIMITNUM' => array( '限选人数', 'align' => CommonModel::ALI_LEFT,'width'=>10),
        );
        //表体
        $data['body'] = $listdata;
        $this->model->fullyExportExcelFile($data, $data['title']);
    }

    /**
     * 拷贝教学计划
     * @param array $rows
     * @param array $rows
     * @throws Exception
     */
    public function copyCoursePlan(array $rows){
        if(empty($rows)) throw new Exception('空！');
        $this->model->startTrans();
        foreach($rows as $courseplan){
            $rst = $this->model->copyCoursePlan($courseplan['YEAR'],$courseplan['TERM'],$courseplan['COURSENO'],$courseplan['GROUP']);
            if(is_string($rst) or !$rst){
                $this->model->rollback();
                $this->failedWithReport("复制失败！{$rst}");
            }
        }
        $this->model->commit();
        $this->successWithReport('复制成功！');
    }

    /**
     * 浏览开课计划　
     * @param array|null $rows
     */
    public function qlist(array $rows=null){
        if(REQTAG === 'export'){
            $rst = $this->model->getCourseplanList(array('I','J'),null, null,false);
            if(is_string($rst)){
                $this->exitWithUtf8("获取数据失败！{$rst}");
            }
            $this->exportCoursePlanList($rst['rows']);
        }elseif(REQTAG === 'copy'){
            $this->copyCoursePlan($rows);
        }
        if($this->_hasJson){
            $rtn = $this->model->getCourseplanList(array('I','J'),$this->_pageDataIndex, $this->_pageSize,false);
            $this->ajaxReturn($rtn,'JSON');
        }

        $this->assign('coursetypeoption','A');//表明类型为any
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->display("qlist");
    }

    public function assoCoursesLook(array $rows=null){
        if(REQTAG === 'export'){
            $rst = $this->model->getCourseplanList('I');
            if(is_string($rst)){
                $this->exitWithUtf8("获取数据失败！{$rst}");
            }
            $this->exportCoursePlanList($rst['rows']);
        }elseif(REQTAG === 'copy'){
            $this->copyCoursePlan($rows);
        }
        if($this->_hasJson){
            $rtn = $this->model->getCourseplanList('I',$this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($rtn,'JSON');
        }
        $this->assign('coursetypeoption','I');
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->display('qlist');
    }

    public function generalCoursesLook(array $rows=null){
        if(REQTAG === 'export'){
            $rst = $this->model->getCourseplanList('J');
            if(is_string($rst)){
                $this->exitWithUtf8("获取数据失败！{$rst}");
            }
            $this->exportCoursePlanList($rst['rows']);
        }elseif(REQTAG === 'copy'){
            $this->copyCoursePlan($rows);
        }
        if($this->_hasJson){
            $rtn = $this->model->getCourseplanList('J',$this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($rtn,'JSON');
        }
        $this->assign('coursetypeoption','J');
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->display('qlist');
    }

    /**
     * 社团课和通识课外的开课计划
     * 自动产生开课计划
     * @param string $grade
     * @param string $schoolno
     * @param string $classno
     */
    public function auto($grade='%',$schoolno='%',$classno='%'){
        $yearTerm =$this->model->getYearTerm('O');
        if(is_string($yearTerm)){
            $this->exitWithReport('查询学年学期过程中出现错误！'.$yearTerm);
        }
        if($this->_hasJson){
            $year = $_REQUEST['YEAR'];
            $term = $_REQUEST['TERM'];

            if($year!=$yearTerm['YEAR'] || $term!=$yearTerm['TERM'] || $_REQUEST['WEEKS']!=$yearTerm['WEEKS']){
                $this->exitWithReport('数据提交时，开课计划学年学期数据发生变化，请刷新页面确认后重新提交！');
            }
            
            switch(trim($_POST['coursetype'])){
                case 'ac':
                    $this->startCoursePlan('I',true,$year,$term,$grade,$schoolno,$classno);
                    break;
                case 'gc':
                    $this->startCoursePlan('J',true,$year,$term,$grade,$schoolno,$classno);
                    break;
                default:
                    //其他课程，处理IJ以外的
                    $this->startCoursePlan(array('I','J'),false,$year,$term,$grade,$schoolno,$classno);
            }
            exit;
        }
        $this->assign('yearTerm',$yearTerm);
        $this->display("auto");
    }

    /**
     * 导出到排课计划
     */
    public function toSchedulePlan(){
        if(!is_array($_REQUEST["ITEM"]) || count($_REQUEST["ITEM"])==0){
            $this->message["message"] = "没有提交任一数据";
            $this->__done();
        }
        $count = count($_REQUEST["ITEM"]);
        foreach($_REQUEST["ITEM"] as $item){
            $items = @explode(",",$item);
            if(count($items)!=4) {
                $count--;
                continue;
            }
            $this->model->startTrans();
            $bind = $this->model->getBind("YEAR1,TERM1,COURSENO1,GROUP1,YEAR2,TERM2,COURSENO2,GROUP2",
                array(
                    $items[0], $items[1], $items[2],$items[3],
                    $items[0], $items[1], $items[2],$items[3]));
            $data = $this->model->sqlExecute($this->model->getSqlMap("coursePlan/toSchedulePlan.sql"),$bind);
           /* $str=$this->model->getDbError();
            echo $str;*/
            if($data===false){
                $this->model->rollback();
                $count--;
            }
            $this->model->commit();
        }
        $this->message["message"] = $count."条的开课计划已导出到排课计划！";
        $this->__done();
    }


    public function Courseinfo(){
        $this->assign('courseno',$_GET['courseno']);
        $this->assign('schools',getSchoolList());
        //todo:课程类别Volist
        $this->xiala('coursetypeoptions','coursetype');
        //todo:课程类型数据Volist    (纯理论-纯实践-理论实践)
        $this->xiala('coursetypeoptions2','coursetype2');
        $this->display();
    }

    public function Courseinfo2(){
        $this->assign('courseno',$_GET['courseno']);
        $this->assign('schools',getSchoolList());
        //todo:课程类别Volist
        $this->xiala('coursetypeoptions','coursetype');
        //todo:课程类型数据Volist    (纯理论-纯实践-理论实践)
        $this->xiala('coursetypeoptions2','coursetype2');
        $this->display();
    }


}