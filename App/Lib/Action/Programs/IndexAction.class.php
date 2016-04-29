<?php
/**
 * 教学计划
 * User: cwebs
 * Date: 14-2-23
 * Time: 上午8:47
 */
class IndexAction extends RightAction {
    private $model;
    private $message = array("type"=>"info","message"=>"","dbError"=>"");
    private $theacher;

    public function __construct(){
        parent::__construct();
        $this->model = new ProgramModel();

        $bind = $this->model->getBind("SESSIONID", session("S_GUID"));
        $sql = $this->model->getSqlMap("user/teacher/getUserBySessionId.sql");
        $this->theacher = $this->model->sqlFind($sql, $bind);
        $this->assign("theacher", $this->theacher);
    }

    /**
     * 教学计划首页
     */
    public function index(){
        $this->display();
    }

    /**
     * 新建教学计划 页面
     */
    public function create(){
        $this->assign('programList',$this->model->getProgramTypeList(false));
        $this->assign('schools',getSchoolList());
        $this->display('create');
    }

    /**
     * 新建教学计划提交页面
     */
    public function newSave(){
        $msg = '';
        //检测教学计划是否冲突
        $programname = $_POST['CTD_PROGNAME'];
        $rst = $this->model->getProgram(array(
            'PROGNAME'  => array($programname,true,'like'),
        ));
        if(is_string($rst)){
            $msg = '检测教学计划名称是否冲突失败！'.$rst;
        }elseif($rst){
            $msg =  '该教学计划已经存在!';
        }else{
            //新建
            $rst = $this->model->newProgram();
            if(is_string($rst)){
                $msg = '插入数据表的过程出新错误，错误信息：';
            }else{
                $msg = $rst?'成功建立该教学计划':'建立教学计划失败了';
            }
        }
        $this->assign('message',$msg);
        $this->create();
    }

}