<?php
/**
 * 考评条目筛选
 * User: cwebs
 * Date: 14-2-12
 * Time: 下午3:43
 */
class ItemAction extends RightAction
{
    private $md;        //存放模型对象
    private $model;
    /**
     *  考评条目管理
     *
     **/
    public function __construct()
    {
        parent::__construct();
        $this->model = M("SqlsrvModel:");
        $this->md = new QualityModel();
    }

    /**
     * 条目整理 - 考评条目筛选
     */
    public function itemq(){
        if($this->_hasJson){
            $year = trim($_POST['YEAR']);
            $term = trim($_POST['TERM']);

            $rst = $this->md->getEvaluationTableList(array(
                ':YEAR' => empty($year) ? $year:date('Y'),
                ':TERM' => empty($term) ? $term:1,
                ':COURSENO' => doWithBindStr($_POST['COURSENO']),
                ':COURSENAME'=>doWithBindStr($_POST['COURSENAME']),
                ':TASK'=>doWithBindStr($_POST['TASK']),
                ':TEACHERNAME'=>doWithBindStr($_POST['TEACHERNAME']),
                ':ENABLED'=>doWithBindStr($_POST['ENABLED'])
            ),$this->_pageDataIndex, $this->_pageSize);
            $this->ajaxReturn($rst,"JSON");
            exit;
        }
        $teachers = $this->md->getTeacher($_SESSION['S_USER_INFO']['TEACHERNO']);
        $teachername= $teachers[0];
        $this->assign('name',trim($teachername['NAME']));
        $this->assign('quanxian',$_SESSION['S_USER_INFO']['ROLES']);


        $json1 = '[{"text": "课堂教学","value": "value"},{"text": "实践教学","value": "S"},{"text": "毕业设计","value": "B"}]';
        $sjson=array();
        $sjson2['text']="课堂教学";
        $sjson2['value']="K";                    // 把类型数据转成json格式给前台的combobox使用
        array_push($sjson,$sjson2);
        $sjson2['text']="实践教学";
        $sjson2['value']="S";
        array_push($sjson,$sjson2);
        $sjson2['text']="毕业设计";
        $sjson2['value']="B";
        array_push($sjson,$sjson2);
        $sjson=json_encode($sjson);
        $this->assign('sjson',json_encode(array()));

        $sjson3=array();
        $sjson2['text']="参评";
        $sjson2['value']="1";                    // 把是否参评数据转成json格式给前台的combobox使用
        array_push($sjson3,$sjson2);
        $sjson2['text']="不参评";
        $sjson2['value']="0";
        array_push($sjson3,$sjson2);
        $sjson3=json_encode($sjson3);
        $this->assign('sjson3',$sjson3);

        $data = $this->model->sqlFind($this->model->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
     /*   echo '<pre>';
        print_r($_SESSION);*/
        $myschool=$this->model->sqlFind("select SCHOOL from teachers where teacherno=:teacherno",array(':teacherno'=>$_SESSION['S_USER_INFO']['TEACHERNO']));

        $this->assign('myschool',$myschool['SCHOOL']);
        $this->assign("yearTerm",$data);

        $this->xiala('教学质量评估类型','coursetypeoptions2');
        $this->display();
    }


    public function updatelock(){
        $this->model->startTrans();
        foreach($_POST['bind'] as $val){
            $one=$this->model->sqlExecute("UPDATE 教学质量评估综合 SET lock=:lock  WHERE recno=:recno",
                array(':lock'=>$_POST['lock'],':recno'=>$val));
            if(!$one){
                exit('未知错误!');
                $this->model->rollback();
            }

    }
        $this->model->commit();
        exit('设置成功');
    }

    /*
     * 修改数据时候的方法
     */
    function updateit()
    {
        if($this->_hasJson){
            $this->model->startTrans();
            foreach($_POST['bind'] as $val){
                $one=$this->model->sqlExecute("UPDATE 教学质量评估综合 SET task=:task  WHERE recno=:recno",
                array(':task'=>$_POST['task'],':recno'=>$val));
                if(!$one){
                    exit('未知错误!');
                    $this->model->rollback();
                }
            }

            $this->model->commit();
            exit('设置成功');

        }

        $this->model->startTrans();
        foreach($_POST['bind'] as $val){
            $one=$this->model->sqlExecute("UPDATE 教学质量评估综合 SET enabled=:task  WHERE recno=:recno",
                array(':task'=>$_POST['task'],':recno'=>$val));
            if(!$one){
                exit('未知错误!');
                $this->model->rollback();
            }
        }

        $this->model->commit();
        exit('设置成功');
/*
        $task_sql=trim($_POST["TASK"]);
        $enabled_sql=trim($_POST["ENABLED"]);
        $recno_sql=trim($_POST["RECNO"]);

        $sql="UPDATE 教学质量评估综合 SET task='".$task_sql."',enabled='$enabled_sql' WHERE recno='$recno_sql'";
        $boo=$this->model->sqlQuery($sql);
        if($boo===false) echo false;
        else echo true;*/
    }

    /**
     *  删除考评条目操作的方法
     **/
    public function deleteit()
    {
        $shuju=new SqlsrvModel();
        $data=array();
        $newids='';
        foreach($_POST['in'] as $val){
            $newids.="'".$val."',";
        }
        $newids=rtrim($newids,',');
        $sql="delete  from 教学质量评估综合 where recno in ($newids);delete from 教学质量评估详细 where map in ($newids);";
        $row=$this->model->sqlExecute($sql);
        if($row) echo true;
        else echo false;
    }

    /**
     *
     */
    public function additem(){
    	if($this->_hasJson){
    		$teacher =$this->model->sqlQuery("select rtrim(teacherno) code,rtrim(name) name from  teachers where name!='' order by name"); 
        	echo json_encode($teacher);exit;
    	}
        $this->assign('yearTerm',$this->md->getYearTerm('C'));
        $this->xiala('教学质量评估类型','coursetypeoptions2');
        $this->assign('schools',getSchoolList());
        $this->display();
    }

    /**
     * 条目整理 - 添加考评条目
     */
    function addit(){
        $rst = $this->md->createEvaluationItem(array(
            'teacherno'=>$_POST['TEACHERNO'],
            'courseno'=>$_POST['COURSENO'],
            'task'=>$_POST['TYPE'],
            'year'=>$_POST['YEAR'],
            'term'=>$_POST['TERM'],
            'lock'=>0,
        ));
        if(is_string($rst)){
            $this->exitWithReport('插入过程执行失败了!'.$rst);
        }elseif(!$rst){
            $this->exitWithReport('未能成功添加考评条目！');
        }else{
            $this->exitWithReport('考评条目添加成功！','info');
        }
    }
    
    /**
     * 课号教师号是否存在的检验
     */
    public function validation(){
        if(isset($_POST['teacherno'])){
            $teachers = $this->md->getTeacher($_POST['teacherno']);
            if(is_string($teachers)){
                $this->exitWithReport('查询教师信息失败了！'.$teachers);
            }elseif(!$teachers){
                $this->exitWithReport("未查询到教师号为[{$_POST['teacherno']}]的教师信息！");
            }else{
                $this->exitWithReport(json_encode($teachers[0]),'info');
            }
        }elseif(isset($_POST["VALUE"])){
            $model = new CoursePlanModel();
            $courseno = substr(trim($_POST["VALUE"]),0,7);
            $rst = $model->getCourse($courseno);
            if(is_string($rst)){
                $this->exitWithReport('查询课号是否存在失败了！'.$rst);
            }elseif(!$rst){
                $this->exitWithReport("未查询到课程号为[{$courseno}]的课程");
            }else{
                $this->exitWithReport('','info');
            }
        }
    }

    //todo:查看自己的课程
    public function mycreate(){
        $arr=array();
        $array=array(':school'=>$_POST['school'],
            ':yone'=>$_POST['year'],':tone'=>$_POST['term'],':ytwo'=>$_POST['year'],':ttwo'=>$_POST['term']);
        $count=$this->model->sqlFind(" select count(*) as ROWS from (SELECT row_number() over(order by TEACHERS.teacherno) as row,TEACHERS.NAME AS TEACHERNAME,temp.TEACHERNO AS TEACHERNO,
temp.COURSENO AS COURSENO,COURSES.COURSENAME AS COURSENAME,temp.TASK ,YEAR,TERM,RECNO,TEMP.ENABLED
FROM 教学质量评估综合 as temp INNER JOIN TEACHERS ON TEACHERS.TEACHERNO=temp.TEACHERNO
INNER JOIN COURSES ON COURSES.COURSENO=SUBSTRING(temp.COURSENO,1,7)
where courses.SCHOOL=:school  and temp.year=:yone and temp.term=:tone and temp.courseno not in(
	select rtrim(courseno)+rtrim([group]) from SCHEDULEPLAN where year=:ytwo and term=:ttwo
))as b ",$array);

        if($arr['total']=$count['ROWS']){
          $arr['rows']=$this->model->sqlQuery(" select *  from (SELECT row_number() over(order by TEACHERS.teacherno) as row,TEACHERS.NAME AS TEACHERNAME,temp.TEACHERNO AS TEACHERNO,
temp.COURSENO AS COURSENO,COURSES.COURSENAME AS COURSENAME,temp.TASK ,YEAR,TERM,RECNO,TEMP.ENABLED
FROM 教学质量评估综合 as temp INNER JOIN TEACHERS ON TEACHERS.TEACHERNO=temp.TEACHERNO
INNER JOIN COURSES ON COURSES.COURSENO=SUBSTRING(temp.COURSENO,1,7)
where courses.SCHOOL=:school  and temp.year=:yone and temp.term=:tone and temp.courseno not in(
	select rtrim(courseno)+rtrim([group]) from SCHEDULEPLAN where year=:ytwo and term=:ttwo
))as b",array_merge($array,array(':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize)));
        }else{
            $arr['total']=0;
            $arr['rows']=array();
        }
        echo json_encode($arr);

    }
}