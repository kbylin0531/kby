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
    public function search($year=null,$term=null,$classno=null){

        switch (REQTAG){
            case 'onekey':
                loadKL();
                $result = \System\Core\Dao::getInstance()->exec('INSERT INTO R28 (PROGRAMNO,STUDENTNO )
SELECT  DISTINCT
PROGRAMS.PROGRAMNO,
STUDENTS.STUDENTNO
FROM PROGRAMS
INNER JOIN R16 on R16.PROGRAMNO = PROGRAMS.PROGRAMNO
INNER JOIN STUDENTS on STUDENTS.CLASSNO = R16.CLASSNO
WHERE not EXISTS (SELECT 1 from R28 WHERE R28.STUDENTNO = STUDENTS.STUDENTNO
	and R28.PROGRAMNO = PROGRAMS.PROGRAMNO) and PROGRAMS.PROGRAMNO like :yearterm',array(
                    ':yearterm'  => substr($year . '', 2, 2).$term.'%',
                    ));
                if(false === $result){
                    $this->failedWithReport(\System\Core\Dao::getInstance()->getError());
                }else{
                    $this->successWithReport('成功插入的数据量:'.$result);
                }
                break;
            case 'classcourse':
                $this->assign('year',$year);
                $this->assign('term',$term);
                $this->assign('classno',$classno);
                $this->display('classcourse');
                exit ;
                break;
            case 'list':
                loadKL();
                $list = \System\Core\Dao::getInstance()->query('
select 
COURSES.COURSENAME as coursename,
R12.COURSENO as courseno,
COURSEAPPROACHES.[VALUE] as approach ,
COURSETYPEOPTIONS.[VALUE] as coursetype,
R12.CREDITS as credit,
PROGRAMS.PROGRAMNO as programno,
PROGRAMS.PROGNAME as programname
from PROGRAMS
INNER JOIN R16 on R16.PROGRAMNO = PROGRAMS.PROGRAMNO
INNER JOIN R12 on R12.PROGRAMNO = PROGRAMS.PROGRAMNO
INNER JOIN COURSES on COURSES.COURSENO = R12.COURSENO
INNER JOIN COURSEAPPROACHES on R12.COURSETYPE = COURSEAPPROACHES.NAME
INNER JOIN COURSETYPEOPTIONS on COURSETYPEOPTIONS.NAME = R12.CATEGORY
WHERE R16.CLASSNO = :classno and PROGRAMS.PROGRAMNO like :yearterm
ORDER BY R12.CATEGORY',array(
                    ':classno'  => $classno,
                    ':yearterm'  => substr($year . '', 2, 2).$term.'%',
                ));
                $this->ajaxReturn($list);
                break;

        }


        if($this->_hasJson){
            $bind = $this->model->getBind('GRADE,CLASSNO,CLASSNAME,SCHOOL',$_REQUEST);
            $bind[':year'] = $year;
            $bind[':term'] = $term;
            $rst = $this->model->getClassTableList($bind, $this->_pageDataIndex, $this->_pageSize);
            if(is_string($rst)){
                $this->exitWithReport('查询失败'.$rst);
            }
            $this->ajaxReturn($rst,"JSON");
            exit;
        }

        $model = new CommonModel();
        $this->assign('yearterm',$model->getYearTerm());
        $this->display("search");
    }


    /**
     * 查看和修改班级教学计划的页面
     */
    public function alist($year=null,$term=null){
        if($this->_hasJson){
            $rst = $this->model->getClassPrograms($_REQUEST['CLASSNO'],$year,$term, $this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($rst,"JSON");
            exit;
        }
        $rst = $this->model->getClassDetail($_REQUEST['CLASSNO']);
        $this->assign('error',is_string($rst)?$rst:'');
        $this->assign("class", $rst);
        $this->assign("year", $year);
        $this->assign("term", $term);
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