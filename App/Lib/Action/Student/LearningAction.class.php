<?php
/**
 * 我的学业
 * User: educk
 * Date: 13-12-25
 * Time: 下午3:36
 */
class LearningAction extends RightAction {

    private $model;
    private $studentno;

    public function __construct(){
        parent::__construct();
        $this->studentno = session('S_USER_NAME');
        $this->assign('studentno',$this->studentno);
        $this->model = new StudentModel();
    }

    /**
     * 奖励惩罚
     */
    public function jc(){
        if(REQTAG === 'getlist'){
            $moralModel = new MoralModel();
            $this->ajaxReturn($moralModel->select($this->studentno));
        }
        $this->display();
    }

    /**
     * 我的教材
     */
    public function mybooks(){
        if($this->_hasJson){
            $model = new BookModel();
            $json = $model->getStudentBookTableList($this->studentno,$this->_pageDataIndex, $this->_pageSize);
            is_string($json) and exit($json);
            $this->ajaxReturn($json,'JSON');
            exit;
        }
        $this->display('mybooks');
    }

    /**
     * 我的教材 (新加)
     */
    public function mybooks_new(){
    	if($this->_hasJson){
        $model = new BookModel();
            $json = $model->getNewStudentBookTableList($this->studentno,$this->_pageDataIndex, $this->_pageSize);
            is_string($json) and exit($json);
    		$this->ajaxReturn($json,'JSON');
    		exit;
    	}
    	$this->display('mybooks_new');
    }
    /**
     * 我的学业/培养方案
     */
    public function  program(){
        if($this->_hasJson){
            $json = null;
            $programModel = new ProgramModel();
            if(isset($_REQUEST['SCHOOLCODE'])){
                $json = $programModel->getProgramBySchoolno($_REQUEST['SCHOOLCODE'], $this->_pageDataIndex, $this->_pageSize);
            }else{
                $json = $programModel->getProgramByStudentno($this->studentno, $this->_pageDataIndex, $this->_pageSize);
            }
            is_string($json) and exit($json);
            $this->ajaxReturn($json,'JSON');
            exit;
        }
        $this->display('program');
    }

    /**
     * {ACT]培养方案详细列表
     */
    public function programDetail(){
        $json = array("total"=>0, "rows"=>array());
        $bind = $this->model->getBind("PROGRAMNO",array($_REQUEST['programNo']));
        $json["rows"] = $this->model->sqlQuery($this->model->getSqlMap("Learning/programCourse.sql"), $bind);
        $json["total"] = count($json["rows"]);

        for($i=0; $i<$json["total"]; $i++){
            $json["rows"][$i]["WEEKS"] = decbin($json["rows"][$i]["WEEKS"]);
        }
        $this->ajaxReturn($json,"JSON");
        exit;
    }

    /**
     * 学籍信息
     */
    public function student(){
        $studentInfo = $this->model->getStudentPanelInfo($this->studentno);
        $registInfo = $this->model->getStudentRegisteData($this->studentno);
        $this->assign('info',$studentInfo);
        $this->assign('list',$registInfo);
        $this->display('student');
    }

    /**
     * 等级考试
     */
    public function level(){
    	if($this->_hasJson){
    		$json = $this->model->getStudentLevelResultTableList($this->studentno, $this->_pageDataIndex, $this->_pageSize);
    		$this->ajaxReturn($json,'JSON');
    		exit;
    	}
        $this->display('level');
    }

}