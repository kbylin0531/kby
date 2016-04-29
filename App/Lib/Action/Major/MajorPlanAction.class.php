<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/27
 * Time: 15:32
 */

/**
 * Class MajorPlanAction 专业计划
 */
class MajorPlanAction extends RightAction {

    private $model = null;

    public function __construct(){
        parent::__construct();
        $this->model = new MajorModel();
    }

    /**
     * 毕业审核
     * @param null|int $year 年级
     */
    public function index($year=null){

        if(REQTAG === 'refresh'){
            $rst = $this->model->refreshStudentsFinishedScore($year);
            if(is_string($rst)) $this->failedWithReport($rst);
            $this->successWithReport("成功刷新{$rst}个学生的课程学分记录！");
        }

        $this->display();
    }

    public function listMajorPlans($grade,$schoolno='%',$classno='%',$studentno='%'){
        $rst = $this->model->listMajorPlans($grade,$schoolno,$classno,$studentno,$this->_pageDataIndex,$this->_pageSize);
        $this->ajaxReturn($rst);
    }

    /**
     * 学生考评查看页面
     * @param $studentno
     * @param $curstatus
     */
    public function pageStudentDetail($studentno,$curstatus){
        //标记为已经查看
        $rst = $this->model->createAuditIfNotExist($studentno);
        if(is_string($rst) or !$rst){
            exit("标记失败！{$rst}");
        }

        //获取数据
        $originlist = $this->model->getStudentCoursesForGraduationAudit($studentno);
//        mist($originlist);
        if(is_string($originlist)){
            exit("获取数据失败！{$originlist}");
        }
        $programlist = array();
        $studentinfo = array(
            'passed_credit' => 0,//通过总学分
            'total_credit'  => 0,//应修总学分
            'passed'        => '不通过',
        );
        foreach($originlist as $key=>$courseitem){
            $programno = $courseitem['programno'];
            if(!isset($programlist[$programno])){
                $programlist[$programno] = array(
                    'programname'   => $courseitem['programname'],
                    'courselist'    => array(),
                );
            }
            $programlist[$programno]['courselist'][] = $courseitem;
            $credit = intval($courseitem['credit']);
            if($courseitem['passed'] === '通过'){
                $studentinfo['passed_credit'] += $credit;
            }
            $studentinfo['total_credit'] += $credit;
        }

        if(0.8*$studentinfo['total_credit'] <= $studentinfo['passed_credit']){
            $studentinfo['passed'] = '通过';
        }

        $this->assign('studentinfo',$studentinfo);
        $this->assign('programlist',$programlist);
        $this->assign('studentno',$studentno);
        $this->assign('curstatus',$curstatus);
        $this->display();
    }

    /**
     * 修改学生的毕业审核结果
     * @param $studentno
     * @param $status
     */
    public function updateAuditStatus($studentno,$status){
        $rst = $this->model->updateAuditStatus($studentno,$status);
        if(is_string($rst) or !$rst){
            $this->failedWithReport("修改失败！{$rst}");
        }
        $this->successWithReport('修改成功！');
    }

}