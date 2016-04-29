<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/16
 * Time: 9:19
 */

/**
 * Class ManagementAction 数据操作控制器
 */
class ManagementAction extends RightAction {

    public $model = null;

    public function __construct(){
        parent::__construct();
        $this->model =  new StudentRewordModel();
    }

    const TYPE_DEVP = '';

    /**
     * 用于导入时的学分转id
     * <b>私有的方法，无需添加权限</b>
     * @param $name
     * @param $type
     * @return mixed
     * @throws Exception
     */
    protected function name2id($name,$type){
        $map = null;
        switch($type){
            case self::TYPE_DEVP:
                $rst = $this->model->sqlQuery('select * from cwebs_sreward_develop_setting');
                if(false === $rst){
                    throw  new Exception('错误！');
                }
                foreach($rst as $value){
                    $map[$value['name']] = intval($value['id']);
                }
                break;


            default:
                throw new Exception('错误的参数2！');
        }

//        mistey($map,$name,$map[$name]);
        $name = trim($name);
        if(isset($map[$name])){
            return $map[$name];
        }else{
            throw new Exception('错误的字段值，请检查\''.$name.'\'! ');
        }
    }


//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
    /**
     * 显示 发展过程性学分添加页面
     * @param null $type
     */
    public function pageCreateEncourageOfDevolopment($type=null){
        if(REQTAG === 'gettreelist'){
            $this->getTreeCombo($type);
        }
        $this->assign('dvlp_list',$this->model->getDevelopList());
        $this->display();
    }

    /**
     * 获取表格编辑框的下拉数据 缺少将无法修改表格数据
     * @param $type
     */
    public function getTreeCombo($type){
        $this->ajaxReturn($this->model->getTreeCombo($type));
    }

    /**
     * 创建 发展过程性学分
     * @param int $year 学年
     * @param int $term 学期
     * @param string $projectid 发展性学分项ID
     * @param null|string $studentno 学号，如果为null时表示添加班级下的学生（此时不允许班级号为null）
     * @param null|string $classno 班级号，如果为null表示添加只添加一个学生（此时不允许学号为null）
     * @param string $rem
     * @return mixed
     */
    public function createEncourageOfDevolopment($year,$term,$projectid,$studentno=null,$classno=null,$rem=''){
        $students = $this->model->getStudentsByClassno($classno,$studentno);
        $this->model->startTrans();
        foreach($students as $student){
            $rst = $this->model->createEncourageOfDevolopment($year,$term,$student['studentno'],$projectid,$rem);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }
        }
        $this->model->commit();
        $this->successWithReport('成功添加'.count($students).'个学生！');
    }

    /**
     * 导入数据 的通用方法
     * 需要设置为公开
     */
    public function pageImport(){
        $this->assign('url',REQTAG);
        $this->display();
    }

    /**
     * 导入 发展过程性学分
     */
    public function importEncourageOfDevolopment(){
        $excelModel = new ExcelExtensionModel();
        $data = $excelModel->import(array(
            'studentno' => '学号',
            'year' => '学年',
            'term' => '学期',
            'itemname' => '项目名称',
            'rem'    => '备注',
        ));
        if(is_string($data)){
            exit("导入错误！{$data}");
        }

//        mist($data);
        $this->model->startTrans();
        foreach($data as $student){
            $rst = $this->model->createEncourageOfDevolopment($student['year'],$student['term'],$student['studentno'],
                $this->name2id($student['itemname'],self::TYPE_DEVP),$student['rem']);
            if(is_string($rst) or !$rst){
                exit("添加失败！{$rst}");
            }
        }
        $this->model->commit();
        exit('成功添加'.count($data).'个学生！<br /><a  href="javascript:history.go(-1);" >返回</a>  ');//输出提示和操作信息
    }




    /**
     * 显示 发展性学分查询查询页面
     */
    public function pageListEncourageOfDevolopment(){
        $this->display();
    }

    /**
     * 获取 发展过程性学分查询页面数据
     * @param int $year 学年
     * @param int $term 学期
     * @param string $name 发展性学分项ID，默认搜索全部
     * @param string $studentno 学号，默认搜索全部
     * @param string $classno 班级号，默认搜索全部
     */
    public function listEncourageOfDevolopment($year,$term,$name='%',$classno='%',$studentno='%'){
        $this->ajaxReturn($this->model->listEncourageOfDevolopment($year,$term,$classno,$studentno,$name,$this->_pageDataIndex,$this->_pageSize));
    }

    /**
     * 删除 一条发展过程性学分
     * @param $id
     */
    public function deleteEncourageOfDevolopment($id){
        $rst = $this->model->deleteEncourageOfDevolopment($id);
        if(is_string($rst) or !$rst){
            $this->failedWithReport("删除失败!{$rst}");
        }else{
            $this->successWithReport('删除成功！');
        }
    }

    /**
     * 批量修改 发展过程性学分数据
     * @param array $rows
     */
    public function updateEncourageOfDevolopment(array $rows){
        if(!$rows) $this->failedWithReport('修改的数据为空！');
        foreach($rows as $item){
            $rst = $this->model->updateEncourageOfDevolopment($item['id'],null,null,$item['studentno'],$item['projectname'],$item['credit'],$item['rem']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改学生'{$item['studentname']}失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }


//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ

    /**
     * 显示 竞赛体育奖励学分添加页面
     */
    public function pageCreateEncourageOfCompetition(){
        $this->display();
    }
    /**
     * 添加 一个赛体育奖励学分记录
     * @param $year
     * @param $term
     * @param $classno
     * @param $studentno
     * @param $compname
     * @param $comptype
     * @param $lid
     * @param $rid
     * @param $datetime
     * @param null $credit
     * @param string $rem
     * @throws Exception
     */
    public function createEncourageOfCompetition($year,$term,$classno,$studentno,$compname,$comptype,$lid,$rid,$datetime,$credit=null,$rem=''){
        $students = $this->model->getStudentsByClassno($classno,$studentno);
        $this->model->startTrans();
        foreach($students as $student){
            $rst = $this->model->createEncourageOfCompetition($year,$term,$student['studentno'],
                $compname,$credit,$comptype,$lid,$rid,$datetime,$rem);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }
        }
        $this->model->commit();
        $this->successWithReport('成功添加'.count($students).'个学生！');
    }

    /**
     * 批量导入 竞赛体育奖励学分
     * 由于采用了事务，可能无法一次完成过量的数据
     * 建议按照班级导入
     */
    public function importEncourageOfCompetition(){
        $excelModel = new ExcelExtensionModel();
        $data = $excelModel->import(array(
            'studentno' => '学号',
            'year'      => '学年',
            'term'      => '学期',
            'itemname'  => '项目名称',
            'credit'    => '已获学分',
        ));
        if(is_string($data)){
            exit("导入错误！{$data}");
        }

        $this->model->startTrans();
        foreach($data as $student){
            $rst = $this->model->createEncourageOfCompetition($student['year'],$student['term'],$student['studentno'],
                $student['itemname'],$student['credit'],null,null,null,null);
            if(is_string($rst) or !$rst){
                exit("添加失败！{$rst}");
            }
        }
        $this->model->commit();
        exit(count($data).'个学生已经成功添加！<br /><a  href="javascript:history.go(-1);" >返回</a>  ');//输出提示和操作信息
    }

    /**
     * 获取 竞赛体育奖励学分查询界面数据
     * @param $year
     * @param $term
     * @param string $name
     * @param string $classno
     * @param string $studentno
     */
    public function listEncourageOfCompetition($year,$term,$name='%',$classno='%',$studentno='%'){
        $this->ajaxReturn($this->model->listEncourageOfCompetition($year,$term,$classno,$studentno,$name,$this->_pageDataIndex,$this->_pageSize));
    }

    /**
     * 显示 竞赛体育奖励学分查询页面
     */
    public function pageListEncourageOfCompetition(){
        $this->display();
    }

    /**
     * 删除 一个竞赛体育奖励学分
     * @param $id
     */
    public function deleteEncourageOfCompetition($id){
        $rst = $this->model->deleteEncourageOfCompetition($id);
        if(is_string($rst) or !$rst){
            $this->failedWithReport("删除失败!{$rst}");
        }else{
            $this->successWithReport('删除成功！');
        }
    }

    /**
     * 批量修改 竞赛体育奖励学分
     * @param array $rows
     */
    public function updateEncourageOfCompetition(array $rows){
        if(!$rows) $this->failedWithReport('修改的数据为空！');
        foreach($rows as $item){
            //检测是否修改
            $item['comptype'] = strlen($item['comp_type_name']) === 1?$item['comp_type_name']:null;
            if(!is_numeric($item['levelname']))  $item['levelname'] = null;
            if(!is_numeric($item['rankname']))   $item['rankname'] = null;

            $rst = $this->model->updateEncourageOfCompetition($item['id'],null,null,$item['studentno'],$item['projectname'],$item['comptype'],
                $item['levelname'],$item['rankname'],$item['credit'],$item['datetime'],$item['rem']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改学生'{$item['studentname']}失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }


//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ

    /**
     * 显示 技能证书和创业奖励学分添加页面
     * @param int $dvlp_type 不固定学分类型
     */
    public function pageCreateEncourageOfAddition($dvlp_type){
        $this->assign('dvlptype',$dvlp_type);
        $this->display();
    }

    /**
     * 显示 技能证书和创业奖励学分查询页面
     * @param int $dvlp_type 不固定学分类型
     */
    public function pageListEncourageOfAddition($dvlp_type){
        $this->assign('dvlptype',$dvlp_type);
        $this->display();
    }

    /**
     * 获取 技能证书和创业奖励学分页面数据
     * @param $year
     * @param $term
     * @param string $name
     * @param string $classno
     * @param string $studentno
     */
    public function listEncourageOfAddition($year,$term,$dvlptype='%',$name='%',$classno='%',$studentno='%'){
        $this->ajaxReturn($this->model->listEncourageOfAddition($year,$term,$classno,$studentno,$name,$dvlptype,$this->_pageDataIndex,$this->_pageSize));
    }

    /**
     * 添加 技能证书和创业奖励学分
     * @param $year
     * @param $term
     * @param $classno
     * @param $studentno
     * @param $projectname
     * @param $credit
     * @param int $dvlptype
     * @param string $rem
     * @throws Exception
     */
    public function createEncourageOfAddition($year,$term,$classno,$studentno,$projectname,$credit,$dvlptype,$rem=''){
        $students = $this->model->getStudentsByClassno($classno,$studentno);
        $this->model->startTrans();
        foreach($students as $student){
            $rst = $this->model->createEncourageOfAddition($year,$term,$student['studentno'],$projectname,$credit,$dvlptype,$rem);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }
        }
        $this->model->commit();
        $this->successWithReport('成功添加'.count($students).'个学生！');
    }

    /**
     * 批量修改 技能证书和创业奖励学分
     * @param array $rows
     */
    public function updateEncourageOfAddition(array $rows){
        if(!$rows) $this->failedWithReport('修改的数据为空！');
        foreach($rows as $item){
            $rst = $this->model->updateEncourageOfAddition($item['id'],null,null,$item['studentno'],$item['projectname']
                ,$item['credit'],$item['rem']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改学生'{$item['studentname']}失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }

    /**
     * 导入 技能证书和创业奖励学分
     * @param int $dvlptype
     */
    public function importEncourageOfAddition($dvlptype=0){
        $excelModel = new ExcelExtensionModel();
        $data = $excelModel->import(array(
            'studentno' => '学号',
            'year'      => '学年',
            'term'      => '学期',
            'itemname'  => '项目名称',
            'credit'    => '已获学分',
        ));
        if(is_string($data)){
            exit("导入错误！{$data}");
        }

        $this->model->startTrans();

        foreach($data as $student){
            $rst = $this->model->createEncourageOfAddition($student['year'],$student['term'],$student['studentno'],$student['itemname'],$student['credit'],$dvlptype);
            if(is_string($rst) or !$rst){
                exit("添加失败！{$rst}");
            }
        }

        $this->model->commit();
        exit('成功添加'.count($data).'个学生！<br /><a  href="javascript:history.go(-1);" >返回</a>  ');//输出提示和操作信息
    }

}