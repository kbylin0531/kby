<?php
/**
 * 工作当量-归档
 * User: shencp
 * Date: 15-05-29
 * Time: 下午15:00
 */
class ArchivedAction extends RightAction{
    private $model;
    //构造
    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");
    }
    //工作量归档-列表
    public function archiveList(){
        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,COURSENAME,COURSENO,SCHOOL,NAME,TEACHERNO",$_REQUEST,"%");
            $arr = array("total"=>0, "rows"=>array());
            $sql = $this->model->getSqlMap("Workload/Archived/Q_workloadList.sql");
            $count = $this->model->sqlCount($sql,$bind,true);
            $arr["total"] = intval($count);
            if($arr["total"] > 0){
                $sql = $this->model->getPageSql($sql,null, $this->_pageDataIndex, $this->_pageSize);
                $arr["rows"] = $this->model->sqlQuery($sql,$bind);
            }
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        //开课学院
        $this->assign("school",M("schools")->select());
        $this->display();
    }
}