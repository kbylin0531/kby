<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-7-1
 * Time: 下午3:07
 */
class FinalAdminAction extends RightAction {
    private $md;
    protected $message = array("type"=>"info","message"=>"","dbError"=>"");

    public function __construct(){
        parent::__construct();
        $this->md = M("SqlsrvModel:");
    }

    //todo:安排监考教师的页面
    public function invigilate(){
        $this->assign('schools',getSchoolList());
        $this->assign('type',$_GET['examType']);
        $this->display();
    }



    //todo:统一排考的页面
    public function examCourses(){
        if($this->_hasJson){

            if(trim($_POST['bind'][':status'])=='启用'){
                $status=1;
            }else{
                $status=0;
            }
            $num=$this->md->sqlExecute($this->md->getSqlmap($_POST['exe']),array(':KW'=>$_POST['bind'][':KW'],':status'=>$status,':RECNO'=>$_POST['bind'][':RECNO']));
            exit;
        }
        $this->assign('schools',getSchoolList());
        $this->display();
    }

    //todo:统一排考页面---->提交到数据库的方法
    public function insertPaikao(){
        $this->md->startTrans();
        foreach($_POST['bind'] as $val){
            $bind = array(':exam'=>$val['exam'],':recno'=>$val['recno']);
            $panduan=$this->md->sqlExecute('update SCHEDULEPLAN set exam=:exam where recno=:recno;',$bind);
            if(!$panduan){
                $this->md->rollback();
                exit("课号为{$val['kh']}的课程修改失败了！");
            }
        }
        $this->md->commit();
        exit('已成功完成更新！');
    }
    /**
     * 排考初始化
     */
    public function dataInit(){
        if($this->_hasJson){
            $this->md->startTrans();
            $bind = array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM']);
            $rst = $this->isExecuteDone($this->md->sqlExecute('delete from TestCourse'),
                $this->md->SqlExecute($this->md->getSqlMap('exam/dataInit.SQL'),$bind),
                $this->md->sqlExecute('delete from TestStudent'),
                $this->md->SqlExecute($this->md->getSqlMap('exam/dataInit_student.SQL'),$bind),
                $this->md->sqlExecute('delete from Testbatch'));
            if($rst === 0){
                $this->md->commit();
                exit('success');
            }else{
                $this->md->rollback();
                exit('error code:'.$rst);
            }
        }
        $this->display();
    }

    /**
     * 处理返回有问题的情况
     *      如果任意一个参数全等于false，则返回false
     */
    private function isExecuteDone(){
        $params = func_get_args();
        foreach($params as $key=>$param){
            if($param === false){
                return $key+1;//表示第几个失败,不可以是第零个
            }
        }
        return 0;
    }

    /**
     * 排考设置等价课程
     */
    public function equalCourses(){
        if($this->_hasJson){
            //先查询等价课程号存在不存在
            $courseno=$this->md->sqlFind('select COURSENO from TestCourse where COURSENO=:COURSENO',array(':COURSENO'=>$_POST['bind'][':EQCOURSENO']));
            if(!$courseno){//课号不存在
                exit('flase');//课号不存在 您不能修改
            }
            $this->md->startTrans();
            $bind = array(':EQCOURSENO'=>$_POST['bind'][':EQCOURSENO'],':COURSENO'=>$_POST['bind'][':COURSENO']);
            //修改TestCourse 的等价课程
            $one=$this->md->sqlExecute("update TestCourse set CourseNo2=:EQCOURSENO WHERE CourseNo=:COURSENO",$bind );
            $two=$this->md->sqlExecute("update teststudent set courseno2=:EQCOURSENO where courseno=:COURSENO",$bind);
            if($one&&$two){
                $this->md->commit();
                exit('true');
            }
            $this->md->rollback();
            exit('false');
        }
        $this->display();
    }
    //todo:测试
    public function equalCourses2(){
        foreach($_POST['bind'] as $val){
            //先查询等价课程号是否存在于考试课程中
            $courseno=$this->md->sqlFind('select COURSENO from TestCourse where COURSENO=:COURSENO',array(':COURSENO'=>$_POST['eqcourseno']));
            if(!$courseno){//todo:课号不存在
                exit('flase');//课号不存在 不能修改
            }
            $this->md->startTrans();
            //todo:修改TestCourse 的等价课程
            $one=$this->md->sqlExecute("update TestCourse set CourseNo2=:EQCOURSENO WHERE CourseNo=:COURSENO",
                array(':EQCOURSENO'=>$_POST['eqcourseno'],':COURSENO'=>$val['kh']));
            $two=$this->md->sqlExecute("update teststudent set courseno2=:EQCOURSENO where courseno=:COURSENO",
                array(':EQCOURSENO'=>$_POST['eqcourseno'],':COURSENO'=>$val['kh']));
            if(!$one||!$two){
                $this->md->rollback();
                exit('false');
            }
        }

        $this->md->commit();
        exit('true');
    }

    //todo:考试批次设置
    public function setBatch(){

        if(isset($_GET['tag']) && $_GET['tag'] == 'addbatch'){
            $bind = array(':year'=>trim($_POST['YEAR']),':term'=>trim($_POST['TERM']));
            $sql = $this->md->getSqlMap('exam/add_Batch.SQL');
            $rst = $this->md->sqlExecute($sql,$bind);
//            varsPrint($rst);
            $rst = $rst?'success':'error :'.$this->md->getDbError();
            exit($rst);
        }


        if(isset($_GET['tag']) && $_GET['tag'] == 'updatebatch'){
            $sql = 'update TESTBATCH SET TESTTIME=:testtime WHERE RECNO=:recno';
            $this->md->startTrans();
            foreach($_POST['rows'] as $val){
                $bind = array(':testtime'=>trim($val['sjsz']),':recno'=>$val['RECNO']);
                $rst = $this->md->sqlExecute($sql,$bind);
                if(!$rst){
                    $this->md->rollback();
                    exit('更新失败 错误：'.$this->md->getDbError());
                }
            }
            $this->md->commit();
            exit('success');
        }

        if($this->_hasJson){
            //todo:插入批次
        //   $boolean= $this->md->sqlExecute("insert into TESTBATCH(FLAG) select max(FLAG)+1 from TESTBATCH");
            $count=$this->md->sqlFind('select count(*) as ROWS from TESTBATCH');
            if($data['total']=$count['ROWS']){
                $data['rows']=$this->md->sqlQuery("select * from(select row_number() over(order by FLAG) as row,RECNO,FLAG as cc ,TESTTIME as sjsz FROM TESTBATCH) as b where b.row between :start and :end",array(':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize));
            }else{
                $data['rows']=array();
            }
            $this->ajaxReturn($data,'JSON');
            exit;
        }
        $this->display();
    }



    //todo:考试地点设置
    public function setAddress(){
        if($this->_hasJson){
            //todo:设为考场
            $this->md->startTrans();
            foreach($_POST['bind'] as $val){
               $boolean=$this->md->sqlExecute("insert into TESTROOM(ROOMNO,KW,louNO,menpaiNO,ROOMNAME,status) values(:ROOMNO,:KW,:louNO,:menpaiNO,:ROOMNAME,:status)",
               array(':ROOMNO'=>$val['jsh'],':KW'=>$val['kws'],':louNO'=>$val['lh'],':menpaiNO'=>$val['mph'],':ROOMNAME'=>$val['jsmc'],':status'=>1));
               if(!$boolean){
                   exit("{$val['jsh']}已经存在了，请检查后再插入");
               }
            }
            $this->md->commit();
            exit('插入成功');
        }
        $this->display();
    }

    public function Quality_teachers(){

        $arr=array(':teachername'=>doWithBindStr($_POST['bind']['teachername']),':teacherno'=>doWithBindStr($_POST['bind']['teacherno']),':school'=>doWithBindStr($_POST['bind']['school']));
        $count=$this->md->sqlFind("select count(*) as ROWS from(select row_number() over(order by teacherno) as row,teacherno,name from
            teachers where name like :teachername and teacherno like :teacherno and (school like :school))as b",$arr);

        if($data['total']=$count['ROWS']){
            $data['rows']=$this->md->sqlQuery("select * from(select row_number() over(order by teachers.name) as row,teacherno,teachers.name,schools.name as school from
            teachers inner join schools on teachers.school=schools.school where teachers.name like :teachername and teacherno like :teacherno and (teachers.school like :school))as b where b.row between :start and :end",array_merge($arr,
                array(':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize)));
        }else{
            $data['total']=0;
            $data['rows']=array();
        }


        $this->ajaxReturn($data,'JSON');
    }
    //todo:监考安排的页面
    public function jiankaoanpai(){

            $arr=array(':year'=>$_POST['year'],':term'=>$_POST['term'],':sone'=>$_POST['bind']['school'],':stwo'=>$_POST['bind']['school'],
            ':teacherno'=>doWithBindStr($_POST['bind']['teacherno']),':name'=>doWithBindStr($_POST['bind']['name']));
        if($this->_hasJson){
            $count=$this->md->sqlFind("select count(*) as ROWS from(select b.*,row_number() over(order by b.name) as row from
                (select distinct temp.name,teacherno,schools.name as schoolname
                from (
                select name,teacherno,school from teachers
                where exists (select * from teacherplan inner join scheduleplan  on scheduleplan.recno=teacherplan.map
                inner join courses on courses.courseno=scheduleplan.courseno
                where teacherplan.year=:year and teacherplan.term=:term and courses.school=:sone and teachers.teacherno=teacherplan.teacherno)
                 union
                 select name,teacherno,school
                 from teachers
                 where school=:stwo)as temp inner join schools on schools.school=temp.school
                 where temp.teacherno like :teacherno and temp.name like :name
                 ) as b) as c",$arr);

            if($data['total']=$count['ROWS']){
                $data['rows']=$this->md->sqlQuery("select c.* from(select b.*,row_number() over(order by b.name) as row from
                        (select distinct temp.name,teacherno,schools.name as schoolname,temp.school
                        from (
                        select name,teacherno,school from teachers
                        where exists (select * from teacherplan inner join scheduleplan  on scheduleplan.recno=teacherplan.map
                        inner join courses on courses.courseno=scheduleplan.courseno
                        where teacherplan.year=:year and teacherplan.term=:term and courses.school=:sone and teachers.teacherno=teacherplan.teacherno)
                         union
                         select name,teacherno,school
                         from teachers
                         where school=:stwo)as temp inner join schools on schools.school=temp.school
                         where temp.teacherno like :teacherno and temp.name like :name
                         ) as b) as c where c.row between :start and :end",array_merge($arr,
                    array(':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize)));

            }else{
                $data['total']=0;
                $data['rows']=array();
            }


            $this->ajaxReturn($data,'JSON');

        }

        $courseno=$_GET['COURSENO'];

        //todo:找出教师学院
        $teacherSCHOOL=$this->md->sqlfind('select SCHOOL from TEACHERS where RTRIM(TEACHERNO)=:TEACHERNO',array(':TEACHERNO'=>$_SESSION['S_USER_INFO']['TEACHERNO']));


        if($_GET['examtype']=='C'||$_GET['examtype']=='B'){

            $haha=$this->md->sqlQuery($this->md->getSqlMap('exam/Two_one_select_'.$_GET['examtype'].'.SQL'),
            array(':COURSESCHOOL'=>'%',':CHANGCI'=>'%',':COURSENO'=>doWithBindStr($courseno),':examType'=>$_GET['examtype'],':year'=>$_GET['YEAR'],':term'=>$_GET['TERM'],':start'=>1,':end'=>5));

        }else{
            $haha=$this->md->sqlQuery($this->md->getSqlMap('exam/Two_one_select.SQL'),
             array(':COURSESCHOOL'=>'%',':YEAR'=>$_GET['YEAR'],':TERM'=>$_GET['TERM'],':CLASSSCHOOL'=>'%',':CLASSNO'=>'%',':CHANGCI'=>'%',':COURSENO'=>doWithBindStr($courseno),':examType'=>$_GET['examtype'],':start'=>1,':end'=>5));
        }

        //todo:找出teacherList   T1,T2,T3....TNAME1,TNAME2,TNAME3...S1,S2,S3
       $gaga= $this->md->sqlQuery($this->md->getSqlmap('exam/jiankaoanpai_teacherList.SQL'),array(':R15'=>$_GET['R15']));

       $this->assign('R15',$_GET['R15']);
       $this->assign('TList',json_encode($gaga[0]));
       $this->assign('teacherList',$gaga);

        /*echo '<pre>';
        print_r($_SESSION);*/
        //todo:教师所在学院
        $teacherschool=$this->md->sqlFind("select SCHOOL from teachers where teacherno=:teacherno",
        array(':teacherno'=>$_SESSION['S_USER_INFO']['TEACHERNO']));
      //  var_dump($teacherschool);
        $this->assign('myschool',$teacherschool['SCHOOL']);

        //todo:查询考试时间
        $TIME=$this->md->sqlQuery("select FLAG,TESTTIME from TESTBATCH order by FLAG");

        //todo:查询教师列表
        $teacher=$this->md->sqlQuery("SELECT RTRIM(TEACHERNO)+'_'+RTRIM(SCHOOL) AS CODE,NAME FROM TEACHERS WHERE SCHOOL='{$teacherSCHOOL['SCHOOL']}' order by name");
       $schoolList=$this->md->sqlQuery('select * from schools');
        $this->assign('teacherSCHOOL',$teacherSCHOOL);
        $this->assign('R15',$_GET['R15']);
        $this->assign('TList',json_encode($gaga[0]));
        $this->assign('teacherList',$gaga);
        $this->assign('myschool',$teacherschool['SCHOOL']);
		$this->assign('schoolList',getSchoolList());
        $this->assign('teachers',$teacher);
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));

       $this->assign('time',$TIME);
        $this->assign('info',$haha);

//        varsPrint($teacherSCHOOL,$_GET['R15'],json_encode($gaga[0]),$gaga,$teacherschool['SCHOOL'],$schoolList,$teacher,$TIME,$haha);

        $this->display();
    }

    public  function setRoom(){
        $str=str_replace('kcc','roomno',$_POST['field']);
        $str2=str_replace('kcc','seats',$_POST['field']);
        $this->md->startTrans();
        $sql = "update testplan set $str=:roomname,$str2=:kw where courseno=:courseno and year=:year and term=:term";
        $bind =array(':roomname'=>$_POST['room']['jsmc'],':kw'=>$_POST['room']['kws'],':courseno'=>$_POST['kh'],':year'=>$_POST['year'],
            ':term'=>$_POST['term']);
        $int=$this->md->sqlExecute($sql,$bind);
        //==1 改为 >0 受影响行数可能大于1???  150615
        if($int>0){
            $this->md->commit();
            exit('设置成功');
        }
//        varsPrint($sql,$bind,$this->md->getDbError());
        $this->md->rollback();
        exit('设置失败 code:'+$int);
    }


}
