<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 2015/9/1
 * Time: 15:36
 */
class StatisticsAction extends RightAction
{
    private $model;
    protected $message = array("type" => "info", "message" => "", "dbError" => "");
    protected $YearTerm;

    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");

        $this->YearTerm = $this->model->sqlFind("select * from YEAR_TERM where [TYPE]='S'");
        $this->assign("YearTerm", $this->YearTerm);
    }

    public function qlist($rows=null){

        if(REQTAG === 'delete'){
            if(!empty($row)){
                $moralModel = new MoralModel();
                $c = 0;
                foreach($rows as $item){
                    $id = $item['ID'];
                    $rst = $moralModel->deleteById($id);
                    if(is_string($rst)){
                        $this->failedWithReport('删除失败'.$rst);
                    }
                    if($rst) $c ++;
                }
                $this->successWithReport("一个删除了‘{$c}’条数据");
            }else{
                $this->failedWithReport('删除失败！');
            }
        }elseif(REQTAG === 'add'){
            //TODO:添加处分记录
        }

        if($this->_hasJson){
            $json = array("total"=>0, "rows"=>array());

            $sql  = " from VIEW_MORAL v LEFT JOIN STUDENTS s ON s.STUDENTNO=v.STUDENTNO LEFT JOIN CLASSES c ON c.CLASSNO=s.CLASSNO ";
            $sql .= " WHERE v.[YEAR]=:YEAR AND v.TERM=:TERM AND v.STUDENTNO LIKE :STUDENTNO AND NAME LIKE :NAME AND CLASSNAME LIKE :CLASSNAME ";
            $order =" ORDER BY CLASSNO,STUDENTNO ";

            $bind = $this->model->getBind("YEAR,TERM,STUDENTNO,NAME,CLASSNAME",$_REQUEST,'%');

            if($_REQUEST["SUMVAL"]!=""){
                $sql .= " AND SUMVAL<=:SUMVAL";
                $bind[":SUMVAL"] = -abs(floatval($_REQUEST["SUMVAL"]));
            }

            $count = $this->model->sqlCount("select count(*) ".$sql, $bind);
            $json["total"] = intval($count);
            trace($json, "JSONJSONJSON");
            if($count>0){
                $sql = $this->model->getPageSql("select v.*,s.NAME,c.CLASSNAME,c.CLASSNO ".$sql.$order, null, $this->_pageDataIndex, $this->_pageSize);
                $data = $this->model->sqlQuery($sql, $bind);
                if(count($data)>0){
                    foreach ($data as $k=>$row) {
                        $_countArr = $this->makeCount($row);
                        $data[$k]["COUNT1"] = $_countArr[1];
                        $data[$k]["COUNT2"] = $_countArr[2];
                        $data[$k]["COUNT3"] = $_countArr[3];
                    }

                }
                $json["rows"]=$data;
            }
            $this->ajaxReturn($json,"JSON");
        }
        $this->display("qlist");
    }

    public function detail(){
        if($this->_hasJson){
            if(VarIsNotEmpty("YEAR,TERM,STUDENTNO,MType")==false) return;
            $bind = $this->model->getBind("YEAR,TERM,STUDENTNO,MType",$_REQUEST);
            $sql  = "select i.*,s.NAME,c.CLASSNAME from MORAL_INFO i LEFT JOIN STUDENTS s ON (i.STUDENTNO=s.STUDENTNO) ";
            $sql .= " LEFT JOIN CLASSES c ON (c.CLASSNO=s.CLASSNO)";
            $sql .= " WHERE i.[YEAR]=:YEAR and i.TERM=:TERM and i.STUDENTNO=:STUDENTNO and i.MORAL_TYPE=:MType order by i.ID";
            $data = $this->model->sqlQuery($sql,$bind);
            return $this->ajaxReturn($data,"JSON");
        }
    }

    private function makeCount($row){
        $MTYPES = @explode(",",$row["MORALTYPES"]);
        $VALUES = @explode(",",$row["DETAILVALUES"]);

        $returnValue = Array(1=>0,2=>0,3=>0);
        foreach($MTYPES as $k=>$v) {
            if($v==3) $returnValue[$v]++;
            else $returnValue[$v] += $VALUES[$k];
        }
        return $returnValue;
    }
}