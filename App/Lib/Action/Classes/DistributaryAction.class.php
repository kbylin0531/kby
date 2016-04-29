<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/26
 * Time: 14:28
 */

/**
 * Class DistributaryAction 班级分流
 */
class DistributaryAction extends RightAction {
    /**
     * @var ClassModel
     */
    private $model = null;

    public function __construct(){
        parent::__construct();
        $this->model = new ClassModel();
    }

    /**
     * 分流界面显示
     */
    public function index(){
        $this->assign('yearterm',$this->model->getYearTerm('S'));//获取当年学年学期
        $this->display();
    }

    /**
     * 为分流界面罗列学生名单
     * @param $classno
     * @param $year
     * @param $term
     */
    public function listClasesStudentForDistributary($classno,$year,$term){
        $this->ajaxReturn($this->model->listClasesStudentForDistributary($classno,$year,$term));
    }

    /**
     * 分流操作
     * @param $rows
     * @param $newclassno
     * @param int $year
     * @param int $term
     */
    public function distriStudentClass($rows,$newclassno,$year,$term) {
        foreach($rows as $student){
            $rst = $this->model->distriStudentClass($student,$newclassno,$year,$term);
            if(is_string($rst) or !$rst) {
                $this->failedWithReport("修改学生[{$student['studentname']}]失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }

}