<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 14-4-26
 * Time: 上午9:15
 */
class StudentsPlanAction extends RightAction {
    private $model;
    private $message = array("type"=>"info","message"=>"","dbError"=>"");
    private $theacher;

    public function __construct(){
        parent::__construct();

        $this->model = new ProgramModel();

        $this->theacher = $this->model->getTeacherInfo();
        $this->assign("theacher", $this->theacher);
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
    }

    /**
     * 学生修课计划 显示与查询页面
     */
    public function search(){
        if($this->_hasJson){
            $_REQUEST["STATUS"] = "%";
            $bind = $this->model->getBind("STUDENTNO,NAME,CLASSNO,SCHOOL,STATUS",$_REQUEST);
            $rst = $this->model->getStudentsPlanTableList($bind, $this->_pageDataIndex, $this->_pageSize);

            $this->ajaxReturn($rst,"JSON");
            exit;
        }
        $this->display("search");
    }


    public function alist(){
        if($this->_hasJson){
            $rst = $this->model->getStudentProgramsTableList($_REQUEST['STUDENTNO'], $this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($rst,"JSON");
            exit;
        }
        $rst = $this->model->getStudentInfo($_REQUEST['STUDENTNO']);
        $this->assign('error',is_string($rst)?$rst:'');
        $this->assign("student", $rst);
        $this->display("alist");
    }


    /**
     * 学生修课计划下 查询教学计划列表
     */
    public function add(){
        $bind = array(
            ':programno'=>$_REQUEST['PROGRAMNO'],
            ':progname'=>$_REQUEST['PROGRAMNAME'],
            ':school'=>$_REQUEST['SCHOOL'],
        );
        $rst = $this->model->getProgramTableList($this->_pageDataIndex, $this->_pageSize,$bind);
        $this->ajaxReturn($rst,"JSON");
        exit;
    }

    public function save(){
        if(!$_REQUEST["STUDENTNO"] || !is_array($_REQUEST["PROGRAMNO"]) || count($_REQUEST["PROGRAMNO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }

        $data = $this->model->getStudentInfo($_REQUEST['STUDENTNO']);
        if(is_string($data)){
            $this->exitWithReport('获取学生数据失败了！'.$data);
        }

        if($data==null || $data["SCHOOL"]!=$this->theacher["SCHOOL"]){
            $this->exitWithReport('这个学生的班级不属于你们学部，你不能对其修课计划作改动！');
        }

        $studentno = trim($_REQUEST["STUDENTNO"]);
        $msg = '';
        $this->model->startTrans();

        foreach($_REQUEST["PROGRAMNO"] as $PROGRAMNO){
            if($PROGRAMNO){
                $rst = $this->model->insertProgramStudentByStudentno($PROGRAMNO,$studentno);
                if(is_string($rst)){
                    $this->model->rollback();
                    $this->exitWithReport("教学计划号[{$PROGRAMNO}]已存在！");
                }elseif($rst){
                    $msg .= "教学计划号[{$PROGRAMNO}]添加成功！\n";
                }
            }
        }
        $this->model->commit();
        $this->exitWithReport(nl2br($msg),'info');
    }

    /**
     * 删除学生的教学计划
     */
    public function delete(){
        if(!$_REQUEST["STUDENTNO"] || !is_array($_REQUEST["PROGRAMNO"]) || count($_REQUEST["PROGRAMNO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }
        $data = $this->model->getStudentInfo($_REQUEST['STUDENTNO']);
        if(is_string($data)){
            $this->exitWithReport('获取学生数据失败了！'.$data);
        }
        if($data==null || $data["SCHOOL"]!=$this->theacher["SCHOOL"]){
            $this->exitWithReport('这个学生的班级不属于你们学部，你不能对其修课计划作删除！');
        }

        $studentno = trim($_REQUEST["STUDENTNO"]);
        $msg = '';

        foreach($_REQUEST["PROGRAMNO"] as $PROGRAMNO){
            if($PROGRAMNO){
                $rst = $this->model->deleteProgramStduentByStudentno($PROGRAMNO,$studentno);
                if(is_string($rst)){
                    $this->exitWithReport("教学计划号[{$PROGRAMNO}]删除时发生错误！ ");
                }elseif($rst){
                    $msg .= "教学计划号[{$PROGRAMNO}]删除成功！\n";
                }else{
                    vardump($rst,$PROGRAMNO,$studentno);
                }
            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }
} 