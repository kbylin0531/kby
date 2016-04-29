<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/20
 * Time: 10:59
 */

/**
 * Class TeacherAction 教师端控制器
 *
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20801', '20400', '显示 考评项设置页面', 'Evaluation/Teacher/pageEvaluationItemsSetting', '', 'CD', '', '|20|20400|20801|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20802', '20400', '获取 考评项设置页面数据', 'Evaluation/Teacher/listEvaluationItems', '', 'CD', '', '|20|20400|20802|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20803', '20400', '添加 考评项设置记录', 'Evaluation/Teacher/createEvaluationItem', '', 'CD', '', '|20|20400|20803|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20804', '20400', '删除 考评项设置记录', 'Evaluation/Teacher/deleteEvaluationItem', '', 'CD', '', '|20|20400|20804|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20805', '20400', '批量修改 考评项设置记录', 'Evaluation/Teacher/updateEvaluationItemInBatch', '', 'CD', '', '|20|20400|20805|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20806', '20400', '显示 考评初始化设置页面', 'Evaluation/Teacher/pageEvaluationInit', '', 'CD', '', '|20|20400|20806|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20807', '20400', '初始化教师某学年学期的考评记录', 'Evaluation/Teacher/initEvaluationTeachers', '', 'CD', '', '|20|20400|20807|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20808', '20400', '初始化学生某学年学期的考评记录', 'Evaluation/Teacher/initEvaluationStudents', '', 'CD', '', '|20|20400|20808|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20809', '20400', '显示 评议结果查询页面', 'Evaluation/Teacher/pageEvaluationResult', '', 'CD', '', '|20|20400|20809|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20811', '20400', '获取 评议结果查询页面数据', 'Evaluation/Teacher/listEvaluationResult', '', 'CD', '', '|20|20400|20811|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20812', '20400', '导出 评议结果', 'Evaluation/Teacher/exportEvaluationResult', '', 'CD', '', '|20|20400|20812|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20813', '20400', '显示 学生评议查看界面', 'Evaluation/Teacher/pageEvaluationDetail', '', 'CD', '', '|20|20400|20813|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20814', '20400', '导出 学生评议详情', 'Evaluation/Teacher/exportEvaluationDetail', '', 'CD', '', '|20|20400|20814|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20815', '20400', '显示 评议结果汇总页面', 'Evaluation/Teacher/pageEvaluationCollect', '', 'CD', '', '|20|20400|20815|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20816', '20400', '获取 评议结果汇总页面数据', 'Evaluation/Teacher/listEvaluationCollect', '', 'CD', '', '|20|20400|20816|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20817', '20400', '导出 评议结果汇总页面数据', 'Evaluation/Teacher/exportEvaluationCollect', '', 'CD', '', '|20|20400|20817|', '1');
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
('20818', '20400', '重新生成教师的评议分数', 'Evaluation/Teacher/regenerateEvaluationScore', '', 'CD', '', '|20|20400|20818|', '1');
 *
 */
class TeacherAction extends RightAction {

    /**
     * 评教模型实例
     * @var EvaluationModel
     */
    private $model;

    /**
     * 教师端菜单配置
     * @var array
     */
    private static $menu_list = null;

    public function __construct(){
        parent::__construct();
        $this->model = new EvaluationModel();
    }

    /**
     * 教师端模块首页
     */
    public function index(){
        isset(self::$menu_list) or static::$menu_list = array(
            'menus' => array(
                array(
                    'menuid'    => '1',
                    'icon'      => 'icon-search2',
                    'menuname'  => '评教结果查看',
                    'menus'     => array(
                        array(
                            'menuid'    => '12',
                            'menuname'  => '评议结果查询',
//                            'icon'      => 'icon-personalList',
                            'url'       => U('Evaluation/Teacher/pageEvaluationResult'),
                        ),
                        array(
                            'menuid'    => '19',
                            'menuname'  => '评议结果汇总',
//                            'icon'      => 'icon-kongbai',
                            'url'       => U('Evaluation/Teacher/pageEvaluationCollect'),
                        ),
                    ),
                ),
                array(
                    'menuid'    => '2',
                    'icon'      => 'icon-fenxi',
                    'menuname'  => '系统设置',
                    'menus'     => array(
                        array(
                            'menuid'    => '23',
                            'menuname'  => '评教项设置', // 原来的"班级成绩分析"
//                            'icon'      => 'icon-personal',
                            'url'       => U('Evaluation/Teacher/pageEvaluationItemsSetting'),
                        ),
                        array(
                            'menuid'    => '23',
                            'menuname'  => '初始化评教', // 原来的"班级成绩分析"
//                            'icon'      => 'icon-personal',
                            'url'       => U('Evaluation/Teacher/pageEvaluationInit'),
                        ),
                    ),
                ),
            ),
        );
        $this->assign('menu_list',json_encode(self::$menu_list));
        $this->assign('yearTerm',$this->model->getYearTerm('D'));// 教学评估对应'D'
        $this->display();
    }

//TODO:考评项管理
    /**
     * 显示 考评项设置页面
     */
    public function pageEvaluationItemsSetting(){
        $this->display();
    }

    /**
     * 获取 考评项设置页面数据
     */
    public function listEvaluationItems(){
        $rst = $this->model->listEvaluationItems();
        $this->ajaxReturn($rst);
    }

    /**
     * 添加 考评项设置记录
     * @param int $id 考评项ID，同时也是排序的准
     * @param string $description 考评项描述
     * @param int $score 考评项分数
     */
    public function createEvaluationItem($id,$description,$score){
        $rst = $this->model->createEvaluationItem($id,$description,$score);
        if(is_string($rst) or !$rst){
            $this->exitWithReport("添加失败！{$rst}");
        }
        $this->successWithReport("添加成功！");
    }

    /**
     * 删除 考评项设置记录
     * @param array $rows 考评项记录数组
     */
    public function deleteEvaluationItem($rows){
        if(empty($rows)){
            $this->failedWithReport('未选择需要删除的！');
        }
        $this->model->startTrans();
        foreach($rows as $item){
            $oid = $item['oid'];
            $rst = $this->model->deleteEvaluationItemById($oid);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("删除考评项失败！{$rst}");
            }
        }
        $rst = $this->model->unactiveAllEvaluationItem();
        if(is_string($rst)){
            $this->failedWithReport('反激活失败！');
        }
        $this->model->commit();
        $this->successWithReport('删除考评项成功！');
    }

    /**
     * 批量修改 考评项设置记录
     * @param array $rows 考评所有行
     */
    public function updateEvaluationItemInBatch($rows){
        if(empty($rows)){
            $this->failedWithReport('无法从输入流中获取数据！');
        }
        //检查是否满足一百分
        $sum = 0;
        foreach($rows as $item){
            $sum += intval($item['score']);
        }
        if($sum !== 100){
            $this->failedWithReport('所有记录项的分数和不是100，保存失败!');
        }

        //修改
        $this->model->startTrans();
        foreach($rows as $item){
            $rst = $this->model->updateEvaluationItemById($item['oid'],$item['id'],$item['description'],$item['score']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("保存过程出现错误！{$rst}");
            }
        }
        $this->model->commit();
        $this->successWithReport('保存成功');
    }

//TODO: 初始化考评项
    /**
     * 显示 考评初始化设置页面
     */
    public function pageEvaluationInit(){
        $this->display();
    }

    /**
     * 初始化教师某学年学期的考评记录
     * @param $year
     * @param $term
     * @param bool|false $clear
     */
    public function initEvaluationTeachers($year,$term,$clear=false){
        $rst = $this->model->initEvaluationTeachers($year,$term,$clear);
        if(is_string($rst)){
            $this->failedWithReport("初始化过程出现错误，过程已经终止！{$rst}");
        }
        $this->successWithReport("初始化成功，{$rst}条记录已经生成！");
    }
    /**
     * 初始化学生某学年学期的考评记录
     * @param $year
     * @param $term
     * @param bool|false $clear
     */
    public function initEvaluationStudents($year,$term,$clear=false){
        $rst = $this->model->initEvaluationStudents($year,$term,$clear);
        if(is_string($rst)){
            $this->failedWithReport("初始化过程出现错误，过程已经终止！{$rst}");
        }
        $this->successWithReport("初始化成功，{$rst}条记录已经生成！");
    }

//TODO:评议结果查询
    /**
     * 显示 评议结果查询页面
     */
    public function pageEvaluationResult(){
        $this->display();
    }

    /**
     * 获取 评议结果查询页面数据
     * @param $year
     * @param $term
     * @param string $classno
     * @param string $teacherno
     */
    public function listEvaluationResult($year,$term,$classno='%',$teacherno='%'){
        $rst = $this->model->listEvaluationResult($year,$term,$classno,$teacherno,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($rst)){
            $this->failedWithReport("获取列表数据失败！{$rst}");
        }
        $this->ajaxReturn($rst);
    }

    /**
     * 导出 评议结果
     * @param $year
     * @param $term
     * @param string $classno
     * @param string $teacherno
     */
    public function exportEvaluationResult($year,$term,$classno='%',$teacherno='%'){
        $rst = $this->model->listEvaluationResult($year,$term,$classno,$teacherno);
        $data = array();
        $this->model->initPHPExcel();
        $data['title'] = '评议结果';
        $data['head'] = array(
            'teacherno' => array( '编号', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'teachername' => array( '教师姓名', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'classname' => array( '班级', 'align' => CommonModel::ALI_LEFT,'width'=>30),
            'coursename' => array( '课程名称', 'align' => CommonModel::ALI_LEFT,'width'=>30),
            'score_avg' => array( '成绩', 'align' => CommonModel::ALI_LEFT,'width'=>15),
        );
        $data['body'] = $rst['rows'];
        $this->model->fullyExportExcelFile($data, $data['title']);
    }

    /**
     * 显示 学生评议查看界面
     * @param $year
     * @param $term
     * @param $row
     */
    public function pageEvaluationDetail($year,$term,array $row){
        $this->assign(array(
            'year'  => $year,
            'term'  => $term,
            'row'   => $row,
        ));
        $evaluation_list = $this->model->listEvaluationDetail($year,$term,$row['classno'],$row['teacherno'],$row['coursegroup']);
        $this->assign('evaluation_list',$evaluation_list);
        $this->display();
    }

    /**
     * 导出 学生评议详情
     * @param $year
     * @param $term
     * @param $classno
     * @param $teacherno
     * @param $coursegroup
     */
    public function exportEvaluationDetail($year,$term,$classno,$teacherno,$coursegroup){
        $evaluation_list = $this->model->listEvaluationDetail($year,$term,$classno,$teacherno,$coursegroup);
        $data = array();
        $this->model->initPHPExcel();
        $data['title'] = '评议详细';
        $data['head'] = array(
            'scores_general' => array( '得分', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'scores_detail' => array( '得分详情', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'remark' => array( '备注', 'align' => CommonModel::ALI_LEFT,'width'=>50),
        );
        $data['body'] = $evaluation_list;
        $this->model->fullyExportExcelFile($data, $data['title']);
    }



//TODO:评议结果汇总
    /**
     * 显示 评议结果汇总页面
     */
    public function pageEvaluationCollect(){
        $this->display();
    }

    /**
     * 获取 评议结果汇总页面数据
     * @param $year
     * @param $term
     * @param string $classno
     * @param string $teacherno
     */
    public function listEvaluationCollect($year,$term,$classno='%',$teacherno='%'){
        $rst = $this->model->listEvaluationCollect($year,$term,$classno,$teacherno,$this->_pageDataIndex,$this->_pageSize);
        $this->ajaxReturn($rst);
    }

    /**
     * 导出 评议结果汇总页面数据
     * @param $year
     * @param $term
     * @param string $classno
     * @param string $teacherno
     */
    public function exportEvaluationCollect($year,$term,$classno='%',$teacherno='%'){
        $rst = $this->model->listEvaluationCollect($year,$term,$classno,$teacherno);
        $data = array();
        $this->model->initPHPExcel();
        $data['title'] = '评议汇总';
        $data['head'] = array(
            'teacherno' => array( '教师编号', 'align' => CommonModel::ALI_LEFT,'width'=>20),
            'teachername' => array( '教师姓名', 'align' => CommonModel::ALI_LEFT,'width'=>20),
            'classname'   => array( '班级', 'align' => CommonModel::ALI_LEFT,'width'=>20),
            'score_avg' => array( '成绩', 'align' => CommonModel::ALI_LEFT,'width'=>20),
            'score_evaluation' => array( '结果', 'align' => CommonModel::ALI_LEFT,'width'=>20),
        );
        $data['body'] = $rst['rows'];
        $this->model->fullyExportExcelFile($data, $data['title']);
    }

    /**
     * 重新生成教师的评议分数
     * @param $year
     * @param $term
     */
    public function regenerateEvaluationScore($year,$term){
        $rst = $this->model->regenerateEvaluationScore($year,$term);
        if(is_string($rst)){
            $this->failedWithReport($rst);
        }else{
            $this->successWithReport("成功更新{$rst}条教师的记录!");
        }
    }



}