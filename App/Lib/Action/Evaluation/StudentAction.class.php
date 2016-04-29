<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/20
 * Time: 10:56
 */

/**
 * Class StudentAction 学生端控制器
 */
class StudentAction extends RightAction {

    protected $scoremap = array(
        '1.0'   => '很好',
        '0.8'   => '好',
        '0.6'   => '一般',
        '0.4'   => '不好',
        '0.2'   => '差',
    );

    /**
     * 评教模型实例
     * @var EvaluationModel
     */
    private $model;

    public function __construct(){
        parent::__construct();
        $this->model = new EvaluationModel();
    }

    /**
     * 显示学生入口页面
     * 介绍和说明考评的注意须知
     */
    public function index(){
        $this->display();
    }

    /**
     * 显示 学生上课教师选择页面
     */
    public function pageTeacherEvalutions(){
        $this->assign('studentno',$_SESSION['S_USER_INFO']['STUDENTNO']);
        $this->display();
    }

    /**
     * 获取 学生上课教师选择页面 数据
     * 从数据表ces中获取
     * @param int $year
     * @param int $term
     * @param string $studentno
     */
    public function listTeacherEvalutions($year,$term,$studentno){
        $rst = $this->model->listTeacherEvalutions($year,$term,$studentno);
        if(is_string($rst)){
            $this->failedWithReport("查询教室列表失败，请联系管理员！");
        }
        $this->ajaxReturn($rst);
    }

    /**
     * 显示 教师评价输入页面
     * @param int $recno 记录号
     */
    public function pageTeacherEvalutionInput($recno){
        $this->assign('evaluation_detail',$this->model->getEvalutionItemDetailByRecno($recno));
        $this->assign('evaluation_items',$this->model->listEvaluationItems(false));
        $this->assign('recno',$recno);
        $this->display();
    }

    /**
     * 更新 学生对教师的评价记录
     * @param string $recno
     * @param $subdata
     */
    public function updateTeacherEvalution($recno,$subdata){
        parse_str($subdata,$params);
        $detail = array();
        //计算总分
        $sum = 0;
        $items = $this->model->listEvaluationItems(false);

        foreach($items as $item){
            if(in_array($params['CK'][$item['id']],array('1.0','0.8','0.6','0.4','0.2'))){
                $detail[] = $this->scoremap[$params['CK'][$item['id']]];
                $sum += $params['CK'][$item['id']] * $item['score'];
            }else{
                $this->failedWithReport("错误的输入，请检查是否输入完成!{$params['CK'][$item['id']]}");
            }
        }
        $sum = round($sum);//四舍五入
        //设置得分细节
        $detail = implode(',',$detail);

        $rst = $this->model->updateTeacherEvalution($detail,$sum,$params['remark'],$recno);
        if(is_string($rst) ){
            $this->failedWithReport("提交考评成绩失败！{$rst}");
        }elseif(0 === $rst){
            $this->failedWithReport("无法修改已经输入过的评教！{$rst}");
        }
        $this->successWithReport('提交成功！');
    }


}