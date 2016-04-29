<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 2015/9/6
 * Time: 14:03
 */
class CommentsAction extends RightAction{
    private $model;
    protected $message = array("type"=>"info","message"=>"","dbError"=>"");
    protected $YearTerm;

    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");

        $this->YearTerm = $this->model->sqlFind("select * from YEAR_TERM where [TYPE]='S'");
        $this->assign("YearTerm", $this->YearTerm);
    }

    public function qlist(){
        $user = session("S_USER_INFO");
        $classes = $this->getChargeClasses($user["TEACHERNO"]);
        //$classes = $this->getChargeClasses("2011205");
        if($this->_hasJson){
            $this->ajaxReturn($this->getPage($_REQUEST["YEAR"], $_REQUEST["TERM"], $classes["CLASSNO"], $_REQUEST["SNAME"], $_REQUEST["SNO"]));
        }

        $this->assign("classes", $classes);
        return $this->display("qlist");
    }

    public function save(){
        if($this->_hasJson){
            $bind = $this->model->getBind("YEAR,TERM,STUDENTNO",$_REQUEST);
            $data = $this->model->sqlCount("select count(*) from MORAL_COMMENTS where YEAR=:YEAR and TERM=:TERM and STUDENTNO=:STUDENTNO",$bind);
            if($data==1){ //修改
                $bind = $this->model->getBind("COMMENT,YEAR,TERM,STUDENTNO",$_REQUEST);
                $data = $this->model->sqlExecute("update MORAL_COMMENTS set COMMENT=:COMMENT WHERE  YEAR=:YEAR and TERM=:TERM and STUDENTNO=:STUDENTNO",$bind);
            }else{//新增
                $bind = $this->model->getBind("YEAR,TERM,STUDENTNO,COMMENT",$_REQUEST);
                $data = $this->model->sqlExecute("insert into MORAL_COMMENTS (YEAR,TERM,STUDENTNO,COMMENT) VALUES (:YEAR, :TERM, :STUDENTNO, :COMMENT)", $bind);
            }

            if($data === false){
                $this->message["type"] = "error";
                $this->message["message"] = "添加评语时发生错误！";
            }elseif($data==0) {
                $this->message["type"] = "warning";
                $this->message["message"] = "没有任一评语操作成功！";
            }else$this->message["message"] = "学生评语操作成功！";
            $this->ajaxReturn($this->message, "JSON");
        }
    }

    /**
     * 一个班级只能有一个斑主任，一个教师只能任一个班级的班主任
     * @param $teacherNo
     * @return bool
     */
    private function getChargeClasses($teacherNo){
        $data = $this->model->sqlQuery("select * from CLASSES WHERE CHARGE_TEACHERNO=:TEACHERNO",array(":TEACHERNO"=>trim($teacherNo)));
        if($data===false) return false;
        elseif(count($data)==1) return $data[0];
        return false;
    }

    /**
     * 获得扣分列表
     * @param $year
     * @param $term
     * @param $sname
     * @param $sno
     */
    private function getPage($year, $term, $classmo, $sname, $sno){
        $json = array("total"=>0, "rows"=>array());
        $sql = "from STUDENTS s INNER JOIN CLASSES c ON (s.CLASSNO=c.CLASSNO) LEFT JOIN MORAL_COMMENTS m ON (m.YEAR=:YEAR and m.TERM=:TERM and m.STUDENTNO=s.STUDENTNO)";
        $where = " s.CLASSNO=:CLASSNO and s.name LIKE :SNAME and s.STUDENTNO LIKE :SNO";

        $year = intval($year); $term = intval($term);
        $bind = array(":YEAR"=>$year,":TERM"=>$term,":CLASSNO"=>$classmo, ":SNAME"=>$sname,":SNO"=>$sno);
        $count = $this->model->sqlCount("select count(*) ".$sql." where ".$where, $bind);
        $json["total"] = intval($count);
        if($count>0){
            $sql = $this->model->getPageSql("select s.STUDENTNO,s.NAME,s.CLASSNO,c.CLASSNAME,m.COMMENT, $year as YEAR, $term as TERM ".$sql." where ".$where." order by STUDENTNO", null, $this->_pageDataIndex, $this->_pageSize);
            $json["rows"] = $this->model->sqlQuery($sql, $bind);
        }
        return $json;
    }
}