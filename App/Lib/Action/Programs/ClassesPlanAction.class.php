<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 14-4-26
 * Time: 上午9:15
 */
class ClassesPlanAction extends RightAction {
    private $model;
    private $message = array("type"=>"info","message"=>"","dbError"=>"");
    private $theacher;

    public function __construct(){
        parent::__construct();
        $this->model = new ProgramModel();

        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->theacher = $this->model->getTeacherInfo();
        $this->assign("theacher", $this->theacher);
    }

    /**
     * 班级修课计划  查询
     */
    public function search(){
        if($this->_hasJson){
            $bind = $this->model->getBind('GRADE,CLASSNO,CLASSNAME,SCHOOL',$_REQUEST);
            $rst = $this->model->getClassTableList($bind, $this->_pageDataIndex, $this->_pageSize);
            if(is_string($rst)){
                $this->exitWithReport('查询失败'.$rst);
            }
            $this->ajaxReturn($rst,"JSON");
            exit;
        }
        $this->display("search");
    }


    /**
     * 查看和修改班级教学计划的页面
     */
    public function alist(){
        if($this->_hasJson){
            $rst = $this->model->getClassPrograms($_REQUEST['CLASSNO'], $this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($rst,"JSON");
            exit;
        }
        $rst = $this->model->getClassDetail($_REQUEST['CLASSNO']);
        $this->assign('error',is_string($rst)?$rst:'');
        $this->assign("class", $rst);
        $this->display("alist");
    }

    /**
     * 班级修课计划下 添加教学计划时候的查询
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

    /**
     * 将教学计划绑定到班级的批量提交操作
     */
    public function save(){
        if(!$_REQUEST["CLASSNO"] || !is_array($_REQUEST["PROGRAMNO"]) || count($_REQUEST["PROGRAMNO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }
        $data = $this->model->getClassDetail($_REQUEST['CLASSNO']);
        if(is_string($data)){
            $this->exitWithReport('查询班级详细信息过程出现错误！'.$data);
        }
        if($data==null || $data["SCHOOL"]!=$this->theacher["SCHOOL"]){
            $this->exitWithReport('对不起，别试图更改别的学部班级的教学计划绑定！');
        }

        $classno = $_REQUEST["CLASSNO"];
        $msg = '';

        foreach($_REQUEST["PROGRAMNO"] as $PROGRAMNO){
            if($PROGRAMNO){
                $rst = $this->model->addProgramClass($PROGRAMNO,$classno);
                if(is_string($rst) || !$rst){
                    $this->exitWithReport("教学计划号[{$PROGRAMNO}]添加失败！".$rst);
                }elseif($rst){
                    $msg .= "教学计划号[{$PROGRAMNO}]添加成功！\n";
                }
            }
        }
        $this->exitWithReport(nl2br($msg),'info');
    }


    /**
     * 删除班级所绑定的教学计划
     */
    public function delete(){
        if(!$_REQUEST["CLASSNO"] || !is_array($_REQUEST["PROGRAMNO"]) || count($_REQUEST["PROGRAMNO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }

        //查询班级详细信息
        $data = $this->model->getClassDetail($_REQUEST['CLASSNO']);
        if(is_string($data)){
            $this->exitWithReport('查询班级详细信息过程出现错误！'.$data);
        }
        if($data==null || $data["SCHOOL"]!=$this->theacher["SCHOOL"]){
            $this->exitWithReport('对不起，别试图删除别的学部班级的教学计划绑定！');
        }

        $classno = $_REQUEST["CLASSNO"];
        $msg = '';

        //删除教学计划
        foreach($_REQUEST["PROGRAMNO"] as $PROGRAMNO){
            if($PROGRAMNO){
                $rst = $this->model->deleteClassesFromProgram($PROGRAMNO,$classno);
                if(is_string($rst)){
                    $this->exitWithReport("教学计划号[{$PROGRAMNO}]删除时发生错误！");
                }else{
                    $msg .= "教学计划号[{$PROGRAMNO}]删除成功！\n";
                }
            }
        }

        $this->exitWithReport(nl2br($msg),'info');
    }


    /**
     * 班级修课计划下  将班级下的学生绑定到教学计划下
     */
    public function bind(){
        if(!$_REQUEST["CLASSNO"] || !is_array($_REQUEST["PROGRAMNO"]) || count($_REQUEST["PROGRAMNO"])==0){
            $this->exitWithReport('没有提交任一数据\n');
        }

        $data = $this->model->getClassDetail($_REQUEST['CLASSNO']);
        if(is_string($data)){
            $this->exitWithReport('查询班级详细信息过程出现错误！'.$data);
        }
        if($data==null || $data["SCHOOL"]!=$this->theacher["SCHOOL"]){
            $this->exitWithReport('对不起，别试图删除别的学部班级的教学计划绑定！');
        }

        $classno = $_REQUEST['CLASSNO'];
        $msg = '';

        $this->model->startTrans();

        foreach($_REQUEST["PROGRAMNO"] as $PROGRAMNO){
            if($PROGRAMNO){
                $rst = $this->model->deleteProgramStudentsByClassno($PROGRAMNO,$classno);
                if(is_string($rst)){
                    $this->model->rollback();
                    $this->exitWithReport("教学计划号[{$PROGRAMNO}]绑定时发生错误！".$rst);
                }

                $rst = $this->model->insertProgramStudentsByClassno($PROGRAMNO,$classno);
                if(is_string($rst)){
                    $this->model->rollback();
                    $this->exitWithReport("教学计划号[{$PROGRAMNO}]绑定时发生错误!！".$rst);
                }else{
                    $msg .= "教学计划号[{$PROGRAMNO}]绑定成功！\n";
                }
            }
        }
        $this->model->commit();
        $this->exitWithReport(nl2br($msg),'info');
    }



} 