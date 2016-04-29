<?php
/**
 * 选课、退课模块
 * User: educk
 * Date: 13-12-25
 * Time: 下午2:59
 */
class CourseAction extends RightAction {
    private $model = null;
    private $_array = array(1=>"MON","TUE","WES","THU","FRI","SAT","SUN");
    private $userinfo = null;

    /**
     * @var CourseSelectionAction
     */
    private $commonAction = null;

    public function __construct(){
        parent::__construct();
        $this->model = new CourseManagerModel();
        $this->commonAction = A('CourseManager/CourseSelection');
        $this->userinfo = session('S_USER_INFO');
    }

    /**
     * 已选课程（退课）
     * 页面+数据源
     */
    public function removeList(){
        $this->assign('query_url','Student/Course/removeList/hasJson/true');
        $this->assign('remove_url','Student/Course/remove/hasJson/true');
        $this->commonAction->studentCourseSelectionList(session("S_USER_NAME"));
    }
    /**
     * 已选课程界面的退课操作
     * UPDATE METHODS set ROLES = '*SD' where METHODID = 'XS06'
     */
    public function remove(){
        $this->commonAction->removeStudentCourse(session("S_USER_NAME"));
    }
    /**
     * 普通类选课查询界面
     */
    public function query(){
        $this->assign('query_url','Student/Course/query');//查询显示界面
        $this->assign('qlist_url','Student/Course/qlist/tag/common');//数据查询地址
        $this->commonAction->queryPage(session("S_USER_NAME"));
    }
    /**
     * 社团课选课查询界面
     */
    public function assocQuery(){
        $this->assign('query_url','Student/Course/assocQuery');//查询显示界面
        $this->assign('qlist_url','Student/Course/qlist/tag/assoc');//数据查询地址
        $this->commonAction->queryPage(session("S_USER_NAME"),'I');
    }
    /**
     * 通识课选课查询界面
     */
    public function generalQuery(){
        $this->assign('query_url','Student/Course/generalQuery');//查询显示界面
        $this->assign('qlist_url','Student/Course/qlist/tag/general');//数据查询地址
        $this->commonAction->queryPage(session("S_USER_NAME"),'J');
    }
    /**
     * 普通类选课、通识课、社团课 数据查询界面
     */
    public function qlist(){
        $this->assign('qlist_url','Student/Course/qlist/hasJson/true');
        $this->assign('save_url','Student/Course/selected/hasJson/true');
        $this->commonAction->queryData(session("S_USER_NAME"));
    }






























    /**
     * [ACT]没选上的课
     */
    public function dump(){
        if($this->_hasJson){
            $json = array("total"=>0, "rows"=>array());
            $bind = $this->model->getBind("YEAR,TERM,STUDENTNO",array(intval($_REQUEST["YEAR"]), intval($_REQUEST["TERM"]), session("S_USER_NAME")));
            $sql = $this->model->getSqlMap("course/studentDump.sql");
            $count = $this->model->sqlCount($sql, $bind ,true);
            $json["total"] = intval($count);

            if($json["total"]>0){
                $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
                $json["rows"] = $this->model->sqlQuery($sql, $bind);
            }
            $this->ajaxReturn($json,"JSON");
            exit;
        }

        $data = $this->model->sqlFind($this->model->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
        $this->assign("yearTerm",$data);
        $this->display();
    }

    /**
     * {ACT]有空的公选课
     */
    public function free(){
        if($this->_hasJson){
            $json = array("total"=>0, "rows"=>array());
            $bind = $this->model->getBind("R32YEAR,R32TERM,YEAR,TERM",array(intval($_REQUEST["YEAR"]), intval($_REQUEST["TERM"]),intval($_REQUEST["YEAR"]), intval($_REQUEST["TERM"])));
            $sql = $this->model->getSqlMap("course/freeCourse.sql");
            $count = $this->model->sqlCount($sql, $bind ,true);
            $json["total"] = intval($count);

            if($json["total"]>0){
                $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
                $json["rows"] = $this->model->sqlQuery($sql, $bind);
            }
            $this->ajaxReturn($json,"JSON");
            exit;
        }

        $data = $this->model->sqlFind($this->model->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
        $this->assign("yearTerm",$data);
        $this->display();
    }


    /**
     * [ACT]选课确认
     */
    public function selected(){
        $this->commonAction->selectCourseForStudent(session('S_USER_NAME'));
    }




}