<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-12-2
 * Time: 下午3:01
 **/
class WorkloadAction extends RightAction
{
    private $md;        //存放模型对象
    private $base;      //路径
    /**
     *  班级管理
     *
     **/
    public function __construct(){
        parent::__construct();
   $this->md=new SqlsrvModel();
        $this->base='Workload/';
     $this->assign('yearterm',$this->md->sqlFind($this->md->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"G")));

     $thistime=time();
     $date=$this->md->sqlFind("select convert(varchar,begintime,20) as begintime,convert(varchar,endtime,20) as endtime from workload_applydate where status=1");

     if(!$date){
        $this->assign('is_time','false');
     }else{


        $starttime=strtotime($date['begintime']);
        $endtime=strtotime($date['endtime']);


        if($thistime>$endtime||$thistime<$starttime){//todo:大于最晚时间 或小于最小时间
            $this->assign('is_time','false');
        }else{
            $this->assign('is_time','true');
        }
     }
   }


    //todo:认定时间设置的页面
    public function applydate(){
        if(isset($_POST['bind'])){
            $this->md->sqlExecute('update workload_applydate set status=0');
            $this->md->sqlExecute($this->md->getSqlMap($this->base.'one_two_insert_time.SQL'),$_POST['bind']);
            exit;
        }
        $this->getdateinfo();
        $this->display();
    }

    //todo:获取认定时间
    public function getdateinfo(){
        $timearr=$this->md->sqlQuery('select status,year,term,convert(varchar(20),begintime,20) as begintime,convert(varchar(20),endtime,20) as endtime from workload_applydate where status=1');
        if(count($timearr)==1){
            $this->assign('timearr',$timearr[0]);
        }else if(count($timearr)==0){//todo:全部关闭
            $timearr=$this->md->sqlFind('select top 1 status,year,term,convert(varchar(20),begintime,20) as begintime,convert(varchar(20),endtime,20) as endtime from workload_applydate order by applydate_id desc');
            $this->assign('timearr',$timearr);      //todo:取最后一次操作的
        }
    }





    //todo:标准班设置页面
    public function standard_class(){
        if($this->_hasJson){

            $this->md->startTrans();

            foreach($_POST['bind'] as $val){
                $bool=$this->md->sqlExecute("update totalwork set Standard='{$_POST['snumber']}' where Totalwork_id={$val['Totalwork_id']}");
                if(!$bool){
                    $this->md->rollback();
                    exit('系统错误');
                }

            }
            $this->md->commit();
            exit('设置成功');
         /*   $count=$this->md->sqlFind($this->md->getSqlMap($_POST['Sqlpath']['count']),$_POST['bind']);
            if($data['total']=$count['ROWS']){
                $data['rows']=$this->md->sqlQuery($this->md->getSqlMap($_POST['Sqlpath']['select']),$_POST['bind']);
            }else{
                $data['rows']=array();

            }
                $this->ajaxReturn($data,'JSON');
                exit;*/
        }

        $this->xiala('courseapproaches','courseapproaches');     //todo:修课方式
        $this->xiala('coursetypeoptions','coursetypeoptions');   //todo:课程类别
        $this->assign('schools',getSchoolList());                          //todo:学院
        $this->xiala('majorcode','majorcode');                              //todo:专业
        $this->xiala('workloadtype','workloadtype');                        //教学工作代码
/*
        $shuju=M('workloadtype');                            // 学位信息
        $degree=$shuju->select();
        $sjson4=array();
        foreach($degree as $val){
            $sjson2['text']=trim($val['worktype_code']);
            $sjson2['value']=trim($val['worktype_code']);                 // 把学位数据转成json格式给前台的combobox使用
            array_push($sjson4,$sjson2);
        }
        $this->assign('sjson4',json_encode($sjson4));*/
        $this->display();

    }

    //todo:插入或者修改标准班
    public function insert_standard_class(){
        $row=$this->md->sqlQuery($this->md->getSqlMap($_POST['Sqlpath']['select']),$_POST['bind']);
       //todo:开启事物
        $this->md->startTrans();
        foreach($row AS $val){

      /*      $bool=$this->md->sqlFind($this->md->getSqlMap($this->base.'One_one_selectclass.SQL'),array(':course_id'=>$val['course_id']));  //todo:COURSEPLAN的 RECNO
            if($bool){          //todo:如果已经存在了 则修改
                $int=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_one_updateclass.SQL'),array(':numbers'=>$_POST['numb'],':code'=>$_POST['code'],':course_id'=>$val['course_id']));
            }else{              //todo:如果不存在则  插入
                $int=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_one_insertclass.SQL'),array(':course_id'=>$val['course_id'],':courseno'=>$val['COURSENO'].$val['GROUP'],':The_course_type'=>$val['xkfs'],':coursename'=>$val['COURSENAME'],':numbers'=>$_POST['numb'],':code'=>$_POST['code']));
            }
            if(!$int){//todo:插入 、  修改过程中出错
                $this->md->rollback();
                exit('程序插入修改过程在出错');
            }*/
          $bool=$this->md->sqlExecute($this->md->getSqlMap($this->base.'chufaqi_demo.SQL'),array(':cone'=>$val['course_id'],':none'=>$_POST['numb'],':code_one'=>$_POST['code'],':ctwo'=>$val['course_id'],':course_id'=>$val['course_id'],':courseno'=>$val['COURSENO'].$val['GROUP'],':The_course_type'=>$val['xkfs'],':coursename'=>$val['COURSENAME'],':numbers'=>$_POST['numb'],':code'=>$_POST['code']));
            if(!$bool){//todo:插入 、  修改过程中出错
                $this->md->rollback();
                exit('程序插入修改过程在出错');
                 }
        }
        $this->md->commit();
        exit('批量设定成功,如需单条设定，可单击页面的记录进行单条设定');
    }

    //todo:插入或修改标准班(单条)
    public function insert_standard_class_one(){
        $bool=$this->md->sqlFind($this->md->getSqlMap($this->base.'One_one_selectclass.SQL'),array(':course_id'=>$_POST['row']['course_id']));
        if($bool){          //todo:如果已经存在了 则修改

            $int=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_one_updateclass.SQL'),array(':numbers'=>$_POST['row']['bzb'],':code'=>$_POST['row']['code'],':course_id'=>$_POST['row']['course_id']));
            var_dump($int);
        }else{              //todo:如果不存在则  插入
            echo '不存在';
            $int=$this->md->sqlExecute($this->md->getSqlMap($this->base.'One_one_insertclass.SQL'),array(':course_id'=>$_POST['row']['course_id'],':courseno'=>$_POST['row']['COURSENO'].$_POST['row']['GROUP'],':The_course_type'=>$_POST['row']['xkfs'],':coursename'=>$_POST['row']['COURSENAME'],':numbers'=>$_POST['row']['bzb'],':code'=>$_POST['row']['code']));
            var_dump($int);
      }

    }

    //todo:工作总量核定的页面
    public function total(){
        if($this->_hasJson){
            $int=$this->md->sqlExecute("update totalwork set status=1");
            //todo:插入到 工作总量表里去
     /*      $bool= $this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_one_insertTotal.SQL'),array(':yone'=>$_POST[':year'],':tone'=>$_POST[':term'],':ytwo'=>$_POST[':year'],':ttwo'=>$_POST[':term'],':COURSENO'=>$_POST['bind'][':COURSENO'],
           ':CLASSNO'=>$_POST['bind'][':CLASSNO'],':TEACHERNO'=>$_POST['bind'][':TEACHERNO'],':code'=>$_POST['bind'][':code'],':SCHOOL'=>$_POST['bind'][':SCHOOL']));


            //todo:插入到明细表
            $arr=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_two_insertmingxi.SQL'),array(':yone'=>$_POST[':year'],':tone'=>$_POST[':term'],':ytwo'=>$_POST[':year'],':ttwo'=>$_POST[':term'],':COURSENO'=>$_POST['bind'][':COURSENO'],
           ':CLASSNO'=>$_POST['bind'][':CLASSNO'],':TEACHERNO'=>$_POST['bind'][':TEACHERNO'],':code'=>$_POST['bind'][':code'],':SCHOOL'=>$_POST['bind'][':SCHOOL']));*/
           if($int)
            exit('提交成功');
           else
            exit('系统错误');
        }
        $this->xiala('workloadtype','workloadtype');                        //教学工作代码
        $this->assign('schools',getSchoolList());                                      //todo:开课学院
        $this->xiala('workcoursetype','workcoursetype');
        $this->display();
    }

    //todo:修改 总量核定页面的 标准班
    public function  Total_update_class(){
        $arr=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_one_updateclass.SQL'),array_merge(array(':numbers'=>$_POST['bzb']),$_POST['bind']));

    }

    //todo:工作量分解的页面
    public function Workload_Fj(){
        $this->assign('myschool',$this->md->sqlFind("select SCHOOL FROM TEACHERS WHERE TEACHERNO='{$_SESSION['S_USER_INFO']['TEACHERNO']}'"));
        if($this->_hasJson){
            $Tarr=explode(',',$_POST['bind'][':teacherno']);
            for($i=0;$i<count($Tarr);$i++){
                $Tarr[$i]="'".trim($Tarr[$i])."'";
            }
            $str=implode(',',$Tarr);

            $Tarr2=explode(',',$_POST['bind'][':totalwork_id']);
            for($i=0;$i<count($Tarr2);$i++){
                $Tarr2[$i]="'".trim($Tarr2[$i])."'";
            }
            $totalwork_id=implode(',',$Tarr2);

            //todo:处理查询出来      (根据总量表的id判断的信息)
            $arr=$this->md->sqlQuery("select work.TEACHERNO as jsh,TEACHERS.NAME as jsxm,SCHOOLS.NAME as jsxy,SCHOOLS.SCHOOL,POSITIONS.VALUE as zc,0 as grgzl,TEACHERTYPE.VALUE as gwlb,
				case [work].[work] when '.00' then '0' else cast([work].[work] as char)end as grgzl FROM work left join TEACHERS on([work].TeacherNo=TEACHERS.teacherno) left JOIN SCHOOLS ON TEACHERS.SCHOOL=SCHOOLS.SCHOOL
				LEFT JOIN POSITIONS ON POSITIONS.NAME=TEACHERS.POSITION LEFT JOIN TEACHERTYPE
				ON TEACHERS.TYPE=TEACHERTYPE.NAME  WHERE [work].Totalwork_id in($totalwork_id)");
            echo json_encode($arr);
            exit;
        }

        //todo:分解时间还剩几天
        $timearr=$this->md->sqlQuery('select year,term,convert(varchar(20),begintime,20) as begintime,convert(varchar(20),endtime,20) as endtime from workload_applydate where status=1');
        if(!$timearr){
            $timestr='待设置';
        }else{
            $timestr=ceil((strtotime($timearr[0]['endtime'])-time())/86400);
        }
        $this->assign('day',$timestr);
        $timearr=$this->md->sqlQuery('select year,term,convert(varchar(20),begintime,111) as begintime,convert(varchar(20),endtime,111) as endtime from workload_applydate where status=1');

        $this->assign('fjsj',$timearr);

        $this->assign('schools',getSchoolList());                          //todo:学院
        $this->xiala('workloadtype','workloadtype');                        //教学工作代码
        $this->assign('isdean',$this->checkUserIsDean(getUsername()));
        $this->display();
    }


    //todo:明细表的插入 或 修改操作
    public function insertMingxi(){


        foreach($_POST['bind'] as $val){
        //todo:查询是表里面 是否已经有保存的信息了
        $bool=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Two_two_selectMingxi2.SQL'),array(':courseno'=>$_POST['T_info']['kh'],':teachername'=>TRIM($val['jsxm']),':year'=>$_POST['year'],':term'=>$_POST['term']));
            if(!$bool){

            //todo:如果不存在  往 totalwork 和work表插入
        $bol=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_two_insertTotalwork.SQL'),array(':year'=>$_POST['year'],':term'=>$_POST['term'],':courseno'=>substr($_POST['T_info']['kh'],0,7),':coursename'=>$_POST['T_info']['km'],':W_Hours'=>$_POST['T_info']['mzks'],':W_number'=>$_POST['T_info']['zs'],':Standard'=>$_POST['T_info']['bzb'],':Estimate'=>$_POST['T_info']['yjrs'],
      ':Attendent'=>$_POST['T_info']['renshu'],':Classname'=>$_POST['T_info']['skbj'],':status'=>2,':teacherno'=>trim($val['jsh']),':teachername'=>trim($val['jsxm']),':classno'=>$_POST['T_info']['bjno'],':school'=>$_POST['T_info']['SCHOOL'],':credit'=>$_POST['T_info']['xf'],':kclx'=>$_POST['T_info']['kclx'],':xklx'=>$_POST['T_info']['xklx'],':mzks'=>$_POST['T_info']['mzks'],':gp'=>substr($_POST['T_info']['kh'],7,2),':code'=>$_POST['T_info']['code']));

         $bol2=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_two_insertmingxi_dan.SQL'),array(':courseno'=>$_POST['T_info']['kh'],':teachername'=>TRIM($val['jsxm']),':year'=>$_POST['year'],':term'=>$_POST['term'],':TeacherNo'=>$val['jsh'],':TeacherName'=>Trim($val['jsxm']),':Work'=>$val['grgzl'],':Status'=>1));
                //todo:修改totalwork的状态
                $this->md->sqlExecute("update totalwork set status={$_POST['status']} where RTRIM(courseno)+RTRIM([group])='{$_POST['T_info']['kh']}' and year={$_POST['year']} and term={$_POST['term']}");

       }else{

            $boo2=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_two_updateMingxi.SQL'),array(':workload'=>$val['grgzl'],':courseno'=>$_POST['T_info']['kh'],':teachername'=>$val['jsxm']));                 //todo:修改
                //todo:修改totalwork的状态
                $this->md->sqlExecute("update totalwork set status={$_POST['status']} where RTRIM(courseno)+RTRIM([group])='{$_POST['T_info']['kh']}' and year={$_POST['year']} and term={$_POST['term']}");

//(select totalwork_id from totalwork
  //             where rtrim(totalwork.courseno)+RTRIM(totalwork.[group])='{$_POST['T_info']['kh']}' and RTRIM(teachername)='{$val['jsxm']}')
        }


        }

            exit('win');

    }



    //todo:删除明细
    public function deleteMingxi(){
        $this->md->startTrans();

        //todo:删除明细表数据
        $boolean=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_two_deletemingxi.SQL'),
            array(':courseno'=>trim($_POST['bind'][':courseno']),':teachername'=>trim($_POST['bind'][':teachername'])));

        //todo:删除totalwork 表的数据
        $bool=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_two_deletetotal.SQL'),array(':courseno'=>trim($_POST['bind'][':courseno']),':teachername'=>trim($_POST['bind'][':teachername'])));


        if(!$bool||!$boolean){
            $this->md->rollback();
            exit('删除有问题');
        }else{
            $this->md->commit();
            exit('删除成功');
        }

    }









    //todo:保存周数
    public function saveWEEKS(){
        if(trim($_POST['bind']['W_number'])==''){
            exit('周数不能为空');
        }
        if(!is_numeric($_POST['bind']['W_number'])){
            exit('周数必须是数字');
        }
     //   $bool=$this->md->sqlFind("select totalwork_id from totalwork where RTRIM(courseno)+RTRIM([group])='{$_POST['bind']['kh']}' and year={$_POST['year']} and term={$_POST['term']} and teacherno='{$_POST['bind']['TEACHERNO']}'");

      /*  if(!$bool){
            //todo:插入到 工作总量表里去
            $boo= $this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_one_insertTotal_dan.SQL'),array(':zhoushu'=>$_POST['bind']['zs'],':yone'=>$_POST['year'],':tone'=>$_POST['term'],':ytwo'=>$_POST['year'],':ttwo'=>$_POST['term'],':COURSENO'=>'%'.$_POST['bind']['kh'].'%',
                ':CLASSNO'=>'%%',':TEACHERNO'=>'%'.$_POST['bind']['TEACHERNO'].'%',':code'=>'%'.$_POST['bind']['code'].'%',':SCHOOL'=>'%%'));

              /*  //todo:插入到明细表
                if($_POST['bind']['TEACHERNAME']==""){
                    $arr=true;
                }else{*/
           /*     $arr=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_two_insertmingxi.SQL'),array(':yone'=>$_POST['year'],':tone'=>$_POST['term'],':ytwo'=>$_POST['year'],':ttwo'=>$_POST['term'],':COURSENO'=>'%'.$_POST['bind']['kh'].'%',
                    ':CLASSNO'=>'%%',':TEACHERNO'=>'%'.$_POST['bind']['TEACHERNO'].'%',':code'=>'%'.$_POST['bind']['code'].'%',':SCHOOL'=>'%%'));*/
                //}

           // }else{
        $this->md->startTrans();
            //todo:修改totalwork的周数
            $boo=$this->md->sqlExecute("update totalwork set W_number={$_POST['bind']['W_number']},Attendent={$_POST['bind']['Attendent']} where totalwork_id='{$_POST['totalwork_id']}'");


            //todo 修改明细
            $arr=$this->md->sqlExecute($this->md->getSqlMap($this->base.'New_Total_updatework.SQL'),array(':TID'=>$_POST['totalwork_id']));


            //todo:修改校正系数
            $jzxs=$this->md->sqlExecute($this->md->getSqlMap($this->base.'New_Total_updateJZXS.SQL'),array(':TID'=>$_POST['totalwork_id']));

        if($boo&&$arr&&$jzxs){
            $this->md->commit();
            exit('操作成功');
        }else{

            var_dump($boo);
            var_dump($arr);
            $this->md->rollback();
            exit('失败');
        }



    }

    //todo:工作量终审
    public function Final2(){
        if($this->_hasJson){
            $this->md->startTrans();                //todo: 开启事物
            //todo:对工作量进行归档 并且修改归档了的工作量的状态
            $this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_four_insertfile.SQL'),ARRAY(':YEAR'=>$_POST['year'],':TERM'=>$_POST['term']));
            //todo:改变状态
            $bool=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_four_updateStatus.SQL'),array(':year'=>$_POST['year'],':term'=>$_POST['term']));
                if($bool){
                    $this->md->commit();
                    exit('归档成功');
                }else{
                     $this->md->rollback();
                     exit('失败了');
                }
            }


        //todo:分解时间还剩几天
        $timearr=$this->md->sqlQuery('select year,term,convert(varchar(20),begintime,20) as begintime,convert(varchar(20),endtime,20) as endtime from workload_applydate where status=1');
        if(!$timearr){
            $timestr='待设置';
        }else{
            $timestr=ceil((strtotime($timearr[0]['endtime'])-time())/86400);
        }
        $this->assign('day',$timestr);
        $timearr=$this->md->sqlQuery('select year,term,convert(varchar(20),begintime,111) as begintime,convert(varchar(20),endtime,111) as endtime from workload_applydate where status=1');

        $this->assign('fjsj',$timearr);
        $this->xiala('workloadtype','workloadtype');                        //教学工作代码
        $this->assign('schools',getSchoolList());
        $this->display();

    }

    //todo:工作量查看
    public function See(){
        $this->assign('schools',getSchoolList());                          //todo:学院
        $this->xiala('workloadtype','workloadtype');                        //教学工作代码
        $this->display();
    }


    //todo:工作量查看
    public function See2(){
        $this->assign('schools',getSchoolList());                          //todo:学院
        $this->xiala('workloadtype','workloadtype');                        //教学工作代码
        $this->display();
    }

    //todo:批量确认单条的时候
    public function piliang(){
        //todo:
        $this->md->startTrans();                                                                      //todo:开启事物
        $arrr=explode(',',RTRIM($_POST['in'],','));
        foreach($arrr as $key=>$val){
            $arrr[$key]="'".$val."'";
        };


        $arrr=implode(',',$arrr);
        $TotalID_List=rtrim($_POST['in'],',');                  //Totalid  集合做in 操作
       $bool= $this->md->sqlExecute("update totalwork set status=2 where totalwork_id in ($arrr)");//todo:修改 总量表里的状态


       foreach($_POST['work'] as $key=>$val){       //todo:现在只能想到 循环修改工作量
            $work=$this->md->sqlExecute("update work set work=$val where Totalwork_id='$key'");
            if(!$work){
                $this->md->rollback();
                echo $key;
                exit('修改work工作量的时候出错');
            }
       }
            $this->md->commit();
                exit('修改成功');

    }


    //todo:判断是否在时间里
    public function is_time(){
        $timearr=$this->md->sqlQuery('select year,term,convert(varchar(20),begintime,20) as begintime,convert(varchar(20),endtime,20) as endtime from workload_applydate where status=1');
        if(count($timearr)==1){
            $start=strtotime($timearr[0]['begintime']);
            $end=strtotime($timearr[0]['endtime']);
            if(time()<$start||time()>$end)
                return 'false';// exit('false');
        }
        return 'true';    // exit('true');

    }








    //todo:导出excel
    public function exportexcel(){
       header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=".$_POST['filename']."111.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        $data=$this->md->sqlQuery($this->md->getSqlMap($this->base.'Two_Four_excel.SQL'),array(':YEAR'=>$_POST['year_form'],':TERM'=>$_POST['term_form']
        ,':CLASSNO'=>doWithBindStr($_POST['classno_form']),':COURSENO'=>doWithBindStr($_POST['courseno_form']),':TEACHERNO'=>doWithBindStr($_POST['teacherno_form']),':CODE'=>doWithBindStr($_POST['code_form']),
        ':SCHOOL'=>doWithBindStr($_POST['school_form'])));


        $title2=$_POST['title'];
        $title=explode(',',$title2);
        //导出xls 开始
        if (!empty($title)){
            foreach ($title as $k => $v) {
                $title3[$v]=iconv("UTF-8","GB2312",$v);
            }
            $title= implode("\t", $title3);
            echo "$title\n";
        }
        if (!empty($data)){
            foreach($data as $key=>$val){
                foreach ($val as $ck => $cv) {
                    $data[$key][$ck]=iconv("UTF-8", "GB2312", $cv);
                }             $data[$key]=implode("\t", $data[$key]);
            }
            echo implode("\n",$data);
        }
    }

   //todo:初始o化数据
    public function chushihua(){
        if($this->_hasJson){
            $this->md->startTrans();
            $one=$this->md->sqlExecute('delete from totalwork');
            $two=$this->md->sqlExecute("delete from work");
            $three=$this->md->sqlExecute("delete from CHONGFU");

            //todo:插入到 工作总量表里去
            $three= $this->md->sqlExecute($this->md->getSqlMap($this->base.'chushihua2.SQL'),array(':yone'=>$_POST[':year'],':tone'=>$_POST[':term'],':ytwo'=>$_POST[':year'],
                ':ttwo'=>$_POST[':term'],':COURSENO'=>'%',':CLASSNO'=>'%',':TEACHERNO'=>'%',':SCHOOL'=>'%'));


            if($three){

                $four=$this->md->sqlExecute("insert into work(Totalwork_id,TeacherNo,TeacherName,work,status)select Totalwork_id,teacherno,teachername,0,1 from totalwork");
                if($four){

                    $this->md->commit();
                    exit('初始化成功');
                }
                var_dump($three);
                var_dump($four);
            }else{
                $this->md->rollback();
                exit('初始化失败');
            }

            exit;
        }
        $this->display();

    }



    public function Setcode(){
        if($this->_hasJson){
            $this->md->startTrans();

            foreach($_POST['bind'] as $val){
                $bool=$this->md->sqlExecute("update totalwork set code='{$_POST['scode']}' where Totalwork_id={$val['Totalwork_id']}");
                if(!$bool){
                    $this->md->rollback();
                    exit('系统错误');
                }

            }
            $this->md->commit();
            exit('设置成功');
        }
        $this->xiala('courseapproaches','courseapproaches');     //todo:修课方式
        $this->xiala('coursetypeoptions','coursetypeoptions');   //todo:课程类别
        $this->assign('schools',getSchoolList());                          //todo:学院
        $this->xiala('majorcode','majorcode');                              //todo:专业
        $this->xiala('workloadtype','workloadtype');                        //教学工作代码
        $this->display();

    }

    //todo:工作量计算的方法
    public function work_count(){
        if($this->_hasJson){
            //todo:只计算等于0的


            //todo:计算校正系数
            $jzxs=$this->md->sqlExecute($this->md->getSqlmap('Workload/New_work_count_JZXS.SQL'));
            $int=$this->md->sqlExecute($this->md->getSqlMap('Workload/New_work_count.SQL'));
            //todo:改变状态

            $this->md->sqlExecute("update totalwork set status=3 where status=0");
            if($int&&$jzxs){
                exit('计算成功');
            }else{

                exit('未找到可计算工作量');
            }
        }
        $this->display();
    }

    //todo:点
    public function chongfu(){
        //todo:终审这边单条修改的时候
        if($this->_hasJson){
          /*  echo '<pre>';
            print_r($_POST);
            exit;*/
            $this->md->startTrans();
            $teacherinfo=$this->md->sqlFind('select * from teacher where teacherno=:teacherno',
            array(':teacherno'=>$_POST['teacherno']));

            $int=$this->md->sqlExecute("update totalwork set Attendent=:Attendent where totalwork_id=:tid",
                array(':Attendent'=>$_POST['renshu'],':tid'=>$_POST['totalwork_id']));
            var_dump($int);
            if($teacherinfo['SCHOOL']=='10'){       //todo:如果外聘老师的话就只给他设置人数
                if(!$int){
                    $this->md->rollback();
                    exit('设置人数失败');
                }
                $this->md->commit();
                exit('设置人数成功');
            }

            //todo:找到这个老师这个课所有上课情况(用来判断重复系数)
            $totalList=$this->md->sqlQuery("select * from totalwork where courseno=:courseno and teacherno=:teacherno",
           array(':courseno'=>substr($_POST['kh'],0,7),':teacherno'=>$_POST['teacherno']));

            //todo:如果大于1
            if(count($totalList)>1){
                //todo:全部设置为1
                foreach($totalList as $k=>$v){
                    $updateCFXS=$this->md->sqlExecute("update work set CFXS=0.85 where totalwork_id=:tid",
                    array(':tid'=>$v['Totalwork_id']));
                    if(!$updateCFXS){
                        $this->md->rollback();
                        exit('修改重复系数的时候出错');
                    }
                }

                $maxtotal=$this->md->sqlQuery('select * from totalwork where courseno=:ctwo and teacherno=:ttwo and  Attendent in(select max(Attendent)
                from totalwork where courseno=:cone and teacherno=:tone) ',
                array(':ctwo'=>substr($_POST['kh'],0,7),':ttwo'=>$_POST['teacherno'],':cone'=>substr($_POST['kh'],0,7),':tone'=>$_POST['teacherno']));
                if(!$maxtotal){
                    $this->md->rollback();
                    exit('查找最大人数失败了!');
                }

                $updateMaxCFXS=$this->md->sqlExecute("update work set CFXS=1 where Totalwork_id=:tid",
                array(':tid'=>$maxtotal[0]['Totalwork_id']));



                if(!$updateMaxCFXS){
                    $this->md->rollback();
                    exit('修改最大人数的重复系数失败!');
                }

            }
            //todo:修改单条的校正系数
          $singleJZXS=$this->md->sqlExecute($this->md->getSqlMap('workload/New_work_Single_JZXS.SQL'),array(':tid'=>$_POST['totalwork_id']));

          if(!$singleJZXS){
              $this->md->rollback();
              exit('修改校正系数失败了');
          }


            //todo:修改单条的工作量
            $singleWork=$this->md->sqlExecute($this->md->getSqlMap('workload/New_work_Single.SQL'),array(':tid'=>$_POST['totalwork_id']));

            if(!$singleWork){
                $this->md->rollback();
                exit('修改工作量失败了');
            }
            $this->md->commit();
            exit('修改成功');


                exit;
        }
        $this->md->startTrans();

        $insert=$this->md->sqlExecute("insert into CHONGFU(Totalwork_id,CFXS,status)(
        select dd.totalwork_id,case  when (isnull(bb.totalwork_id,'aa')<>'aa' or dd.code!='M1'  ) THEN 1 else 0.85 END as CFXS,1
from VIEW_CF dd
left join(
            select dbo.getOne(b.renshu) as renshu,dbo.getOne(c.totalwork_id) as totalwork_id from
        (select teacherno,courseno,max(Attendent) as renshu from totalwork where totalwork.status=4 group by courseno,teacherno) as b
left join totalwork c on (b.courseno=c.courseno and b.teacherno=c.teacherno and b.renshu=c.Attendent) where c.status=4
GROUP BY c.courseno,c.teacherno,b.renshu) as bb on dd.totalwork_id=bb.totalwork_id
where dd.Totalwork_id not in(select Totalwork_id from CHONGFU)
 )");


        //  $demo=$this->m  d->sqlExecute($this->md->getSqlMap($this->base.'New_Final2_updateCF.SQL'));
        $update=$this->md->sqlExecute("update work set CFXS=(
        select CFXS from CHONGFU where  work.Totalwork_id=CHONGFU.Totalwork_id)
where work.Totalwork_id not in(select Totalwork_id from CHONGFU where status<>1)");


        $status=$this->md->sqlExecute("update CHONGFU  set status=2");


     //  $demo2=$this->md->sqlExecute($this->md->getSqlMap($this->base.'New_Final2_updateCF_status.SQL'));


       // $this->md->rollback();
//echo '123';
        if($insert&&$update&&$status){
            var_dump($insert.'====');
            var_dump($update.'====');
            var_dump($status.'====');

            $this->md->commit();
            $this->set_waiping();
            exit('计算成功');
        }else{

            var_dump($insert.'====');
            var_dump($update.'====');
            var_dump($status.'====');

            exit('系统错误:没有可计算的重复工作量');
            $this->md->rollback();
        }


    }

    //todo:特殊类型课程添加
    public function add_teshu(){

        if($this->_hasJson){
            $this->md->startTrans();
            $teacherarr=explode(',',rtrim($_POST['teacherno'],','));
            $grgzl=$_POST['gzl']/count($teacherarr);
            foreach($teacherarr as $val){
                //$arr=$this->md->sqlFind("select T.NAME TEACHERNAME,S.NAME SCHOOL from TEACHERS T INNER JOIN SCHOOLS S ON T.SCHOOL=S.SCHOOL WHERE TEACHERNO='{$val}'");
                $arr=$this->md->sqlFind("select NAME FROM TEACHERS WHERE TEACHERNO='{$val}'");
                if(!$arr){
                    exit($val.'教师不存在,请检查输入框中的教师号是否正确');
                }
                $totalwork=$this->md->sqlExecute($this->md->getSqlMap($this->base.'New_add_teshu_insertTotal.SQL'),
                    array(':year'=>$_POST['year'],':term'=>$_POST['term'],':Estimate'=>$_POST['renshu'],':Attendent'=>$_POST['renshu'],
                    ':teachername'=>$arr['NAME'],':teacherno'=>$val,':code'=>$_POST['code'],':COURSENO'=>$_POST['courseno']));

                //$work=
                $work=$this->md->sqlExecute($this->md->getSqlMap($this->base.'Two_two_insertmingxi_dan.SQL'),
                    array(':courseno'=>$_POST['courseno'],':teachername'=>$arr['NAME'],':year'=>$_POST['year'],':term'=>$_POST['term'],':TeacherNo'=>$val,':TeacherName'=>$arr['NAME'],
                        ':Work'=>$grgzl,':Status'=>1));

             if(!$totalwork||!$work){
                 $this->md->rollback();
                 var_dump($work);
                 var_dump($totalwork);
                 exit('系统错误');
             }
            }

            $this->md->commit();
            exit('添加成功');


        }
        $this->assign('courses',$this->md->sqlQuery("select * from COURSES where TYPE='S'"));
        $this->display();
    }


    //todo:工作总量核定删除的按钮
    public function total_delete(){

        $this->md->startTrans();
        foreach($_POST['bind'] as $val){
            $int=$this->md->sqlExecute("delete from Totalwork where Totalwork_id='{$val['Totalwork_id']}'");
            $int2=$this->md->sqlExecute("delete from work where Totalwork_id='{$val['Totalwork_id']}'");
            if(!$int||!$int){
                $this->md->rollback();
                exit('系统错误');
            }
        }
        $this->md->commit();
        exit('删除成功');
    }



    //todo:工作总量核定删除的按钮
    public function total_delete2(){

        $this->md->startTrans();
        foreach($_POST['bind'] as $val){
            $int=$this->md->sqlExecute("delete from Totalwork where Totalwork_id='{$val['totalwork_id']}'");
            $int2=$this->md->sqlExecute("delete from work where Totalwork_id='{$val['totalwork_id']}'");
            if(!$int||!$int){
                $this->md->rollback();
                exit('系统错误');
            }
        }
        $this->md->commit();
        exit('删除成功');
    }


    //todo:新的课程初始
    public function zairuxin(){
        //todo:插入到 工作总量表里去
        $three= $this->md->sqlExecute($this->md->getSqlMap($this->base.'chushihua2.SQL'),array(':yone'=>$_POST[':year'],':tone'=>$_POST[':term'],':ytwo'=>$_POST[':year'],
            ':ttwo'=>$_POST[':term'],':COURSENO'=>'%',':CLASSNO'=>'%',':TEACHERNO'=>'%',':SCHOOL'=>'%'));


        if($three){

            $four=$this->md->sqlExecute("insert into work(Totalwork_id,TeacherNo,TeacherName,work,status)select Totalwork_id,teacherno,teachername,0,1 from totalwork");
            if($four){

                $this->md->commit();
                exit('载入成功');
            }

        }

    }

    public function chongxin_FJ(){
        $courseno=substr($_POST['bind']['kh'],0,7);
        $gp=substr($_POST['bind']['kh'],7);
        $this->md->startTrans();
        $int=$this->md->sqlExecute("update totalwork set status=1 where Courseno='{$courseno}' and [group]='{$gp}'");
        var_dump($int);
        $this->md->rollback();
    }





    public function set_waiping(){
        $this->md->startTrans();
            $one=$this->md->sqlExecute("update totalwork set jiaozhengxishu=1
where exists(
select 1 from totalwork t where teacherschool='10' and code='M1' and t.Totalwork_id=totalwork.Totalwork_id)");
            $two=$this->md->sqlExecute("update work set CFXS=1
where exists(
select 1 from totalwork t where teacherschool='10' and code='M1' and t.Totalwork_id=work.Totalwork_id)
");


            $three=$this->md->sqlExecute("update work set work=(
select
      W_number*CAST(mzks as decimal(10,2))
from totalwork as t
where  work.Totalwork_id=t.Totalwork_id
) where exists(
select 1 from totalwork t where teacherschool='10' and code='M1' and work.Totalwork_id=t.Totalwork_id) ");


        if($one&&$two&&$three){
            var_dump($three);
            $this->md->commit();
            exit('修改成功');
        }else{

            var_dump($three);

            $this->md->rollback();
            exit('修改失败');
        }
        exit;

    }



    //todo:重设类型和标准班
    public function chongshe(){
        $this->xiala('workloadtype','workloadtype');                        //教学工作代码
        $this->assign('schools',getSchoolList());
        $this->display();
    }


    //todo:设置类型
    public function setType2(){
        $array=array('B'=>array('B'=>0,'S'=>0.3,'Z'=>0.5,'Y'=>0.8),
            'S'=>array('B'=>-0.3,'S'=>0,'Z'=>0.2,'Y'=>0.5),
            'Y'=>array('B'=>-0.8,'S'=>-0.5,'Y'=>0,'Z'=>-0.3),
        'Z'=>array('B'=>-0.5,'S'=>-0.2,'Y'=>0.3,'Z'=>0));
     //
            $this->md->startTrans();
        //todo:修改他们的类型
        foreach($_POST['bind'] as $val){
            //todo:原来的类型
            $ytype=$this->md->sqlFind("select type from totalwork where rtrim(courseno)+rtrim([group])=:courseno",array(':courseno'=>$val));

            $one=$this->md->sqlExecute("update totalwork set type=:type where rtrim(courseno)+rtrim([group])=:courseno",
                array(':type'=>$_POST['type'],':courseno'=>$val));


            //todo：修改校正系数和工作量
            $jiaozhengxishu=$this->md->sqlQuery("select * from totalwork where rtrim(courseno)+rtrim([group])=:courseno",array(':courseno'=>$val));
            if(!$jiaozhengxishu){
                $this->md->rollback();
                exit('没有找到重复系数！');
            }
           //todo:循环这些课
           foreach($jiaozhengxishu as $k=>$v){
               $newJZXS=$v['jiaozhengxishu']+$array[trim($ytype['type'])][trim($_POST['type'])];    //todo:新的校正系数
               //todo:设置校正系数
               $updateJZXS=$this->md->sqlExecute("update totalwork set jiaozhengxishu=:jiaozhengxishu where Totalwork_id=:tid",
                   array(':jiaozhengxishu'=>$newJZXS,':tid'=>$v['Totalwork_id']));

               if(!$updateJZXS){
                    $this->md->rollback();
                    exit('修改校正系数的时候出错了!');
                }

               //todo:找出当前的工作量
               $ywork=$this->md->sqlFind("select * from work where Totalwork_id=:tid",
               array(':tid'=>$v['Totalwork_id']));
           /*    echo '<pre>';
               print_r($ywork);*/
            /*   var_dump($ywork['work']);
                var_dump($ywork['work']/$v['jiaozhengxishu']*$newJZXS);*/
               $updateWORK=$this->md->sqlExecute('update work set work=:work where totalwork_id=:tid',
               array(':work'=>$ywork['work']/$v['jiaozhengxishu']*$newJZXS,':tid'=>$v['Totalwork_id']));
               if(!$updateWORK){
                   $this->md->rollback();
                   exit('修改工作量出错了!');
               }
              /* $updatework=$this->md->sqlExecute("update work set work=:work where Totalwork_id=:tid",
               array(':work'=>));*/
               //:todo:xiu

           }




            if(!$one){
                var_dump($one);
                $this->md->rollback();
                exit($val.'出错');
            }




         }


        $this->md->commit();
        exit('修改成功!');
    }





}

