<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 14-7-9
 * Time: 下午12:44
 */
class ArrangeTestAction extends RightAction {
    private $model;
    protected $message = array("type"=>"info","message"=>"","dbError"=>"");
    private $dataInfo = array();

    public function __construct(){
        parent::__construct();
        $this->model = M("SqlsrvModel:");
    }

    public function auto(){
        $this->setDataInfo();
        $this->assign("dataInfo",$this->dataInfo);
        $this->__done();
    }

    public function arrangeTest(){
        //todo 取出所有排课课程

        $data=$this->model->sqlQuery('select coursename,b.* from courses inner join (select testcourse.courseno2 as courseno,count(*) as renshu from testcourse
inner join teststudent on TESTCOURSE.courseno=TESTSTUDENT.courseno
where TESTCOURSE.flag=0 and batch=1
group by TESTCOURSE.courseno2) as b
on COURSES.courseno=SUBSTRING(b.courseno,1,7)');

        if($data==null){
            $this->message["message"] = "没有需要排考的课程";
            $this->__done();
        }
        $num=0;
        if($_POST['pici']>2)$num+=20;
        foreach($data as $index=>$row){
            for($i=1;$i<=15;$i++) {
                //todo 找到批次已排课人数
                $bind = $this->model->getBind("FLAG",$i+$num);

                $sql = "Select count(*) from teststudent where flag=:FLAG";
                $batchCount = $this->model->sqlCount($sql,$bind);

                //todo 超过单场是大人娄，则退出
                if($batchCount + $row['renshu']>$_REQUEST['maxRs'])
                    continue;

                //todo 检测课程考试是否有冲突
                $bind = $this->model->getBind("COURSENO,FLAG",array($row['courseno'],$i+$num));
                $sql = $this->model->getSqlMap("exam/arrangeConflict.sql");
                $count = $this->model->sqlCount($sql,$bind);
                if($count>0) continue;

                //todo 排课完成
                $bind = $this->model->getBind("FLAG1,COURSENO1,FLAG2,COURSENO2",array($i+$num,$row['courseno'],$i+$num,$row['courseno']));

                $flagg=$this->model->sqlFind("select * from Testbatch where flag=:flag",array(':flag'=>$i+$num));

                if(!$flagg){

                   $ii= $this->model->sqlExecute("insert into testbatch(flag) values(:flag)",array(':flag'=>$i+$num));

                }
                $sql = $this->model->getSqlMap("exam/arrangeUpdate.sql");
                $this->model->sqlExecute($sql,$bind);
                $row["flag"] = $i;
                break;
            }
            if($row["flag"]>0) $this->message["message"] .= "[".$row['courseno']."] ".$row["coursename"]."排考试成功！<br />";
            else $this->message["message"] .= "<font color='red'>[".$row['courseno']."] ".$row["coursename"]."排考试失败！</font><br />";
        }
        $this->__done();
    }


    //todo:导入到TestPlan
    public function import(){
        //todo:检测批次
        $pici=$this->model->sqlQuery("select * from TESTBATCH");
        foreach($pici as $val){
            if($val['TESTTIME']==""){
                exit("第{$val['FLAG']}的考试时间还未输入 请先设定");
            }
        }

        //todo:判断是 期末、期初、毕业重修
        switch($_POST['examtype']){
            case 'M':               //todo:期末
                //todo:往TestPlan插入数据
                $num=$this->model->sqlExecute($this->model->getSqlmap("exam/insertTestPlan_M.SQL"),array(':YONE'=>$_POST['year'],':TONE'=>$_POST['term'],':examType'=>$_POST['examtype'],':YTWO'=>$_POST['year'],':TTWO'=>$_POST['term']));
                break;
            case 'C':               //todo:期初
                $num=$this->model->sqlExecute($this->model->getSqlmap("exam/insertTestPlan_C.SQL"),array(':YEAR'=>$_POST['year'],':TERM'=>$_POST['term']));
                break;
            case 'B':
                $num=$this->model->sqlExecute($this->model->getSqlmap("exam/insertTestPlan_B.SQL"),array(':YONE'=>$_POST['year'],':TONE'=>$_POST['term'],':YTWO'=>$_POST['year'],':TTWO'=>$_POST['term']));
                break;
        }
        exit('成功插入'.$num.'条记录！');

    }








    public function anpai(){
        //todo:找出批次
        $pici=$this->model->sqlFind("select count(*) as ROWS from TestBATCH");
        $roomList=$this->model->sqlQuery("select TR.KW,TR.ROOMNO,TR.ROOMNAME FROM TESTROOM TR where TR.status=1 order by TR.KW desc");
      $canshuer=array();
      $canshusan=array();
       for($i=0;$i<count($roomList);$i++){
            $canshuer[$roomList[$i]['KW']]+=1;
            if(!is_array($canshusan[$roomList[$i]['KW']])){
                $canshusan[$roomList[$i]['KW']]=array();
            }
           array_push($canshusan[$roomList[$i]['KW']],$roomList[$i]);

       }



        $rm=$roomList;
        //todo:如果是课程优先
        if($_POST['type']==1){
            for($i=0;$i<$pici['ROWS'];$i++){

                //todo:教室列表
                $length=count($roomList);
                //todo:255
                $courseList=$this->model->sqlQuery($this->model->getSqlmap('exam/select_255.SQL'),array(':YTWO'=>$_POST['year'],':TTWO'=>$_POST['term'],':FLAG'=>$i+1,':examType'=>$_POST['examtype']));


                $caner=$canshusan;
                $cansan=$canshuer;

                foreach($courseList as $val){
                    $ii=1;           //安排的教室数
                   // $weianpai=;         //todo:未安排人数


                       $content=getSimilar($val['ATTENDENTS'],$caner,$cansan,$ii);

                    if(!$content){//todo:如果三场都拍不下报错
                        continue;
                        exit('考场数已达到上限,请增加批次');
                    }


                    foreach($content as $key=>$vv){
                        $this->model->sqlExecute($this->model->sqlExecute("update TESTPLAN set ROOMNO{$key}='{$vv['ROOMNAME']}',seats{$key}={$vv['KW']},rs{$key}={$vv['rs']} where R15='{$val['R15']}'"));
                    }


                }


            }
            echo '排考成功';
        }else{
         //   $this->model->startTrans();
            for($i=0;$i<$pici['ROWS'];$i++){


                //todo:255
                $courseList=$this->model->sqlQuery($this->model->getSqlmap('exam/select_255.SQL'),array(':YTWO'=>$_POST['year'],':TTWO'=>$_POST['term'],':FLAG'=>$i+1,':examType'=>$_POST['examtype']));

                foreach($roomList as $val){

                    $seats=$val['KW'];           //todo:座位数

                    foreach($courseList as $key=>$v){
                        if($v['ATTENDENTS']==0){
                            continue;
                        }
                        if($courseList[$key]['rs2']){
                            $ii=3;
                        }else if($courseList[$key]['rs1']){
                            $ii=2;
                        }else{
                             $ii=1;
                        }


                        if($seats-$v['ATTENDENTS']<=0){  //todo:如果考位数小于 选课人数

                            //todo:设置考场
                            $bool=$this->model->sqlExecute("update TESTPLAN set ROOMNO$ii='{$val['ROOMNAME']}',seats$ii={$val['KW']},rs$ii=$seats where R15='{$v['R15']}'");
                            $courseList[$key]['ATTENDENTS']=$v['ATTENDENTS']-$seats;
                            $courseList[$key]["rs$ii"]=$seats;

                            if($courseList[$key]['ATTENDENTS']&&$ii==3){//todo:如果还有人,并且排不下了
                            //    $this->model->rollback();
                                exit("{$courseList[$key]['COURSeNO']}考场拍排不下了");
                            }
                            break;
                        }else{

                            $seats-=$v['ATTENDENTS'];
                            //todo:设置考场

                            $bool=$this->model->sqlExecute("update TESTPLAN set ROOMNO$ii='{$val['ROOMNAME']}',seats$ii={$val['KW']},rs$ii={$v['ATTENDENTS']} where R15='{$v['R15']}'");
                            if($bool){
                                unset($courseList[$key]);
                            }

                        }

                    }

                }
            }
            echo '排考成功';


                     }



    }




    /*
     * $arr是教室列表
     */
    //todo:取得最差值值
   public function jiejin($num,$jiaarr,$key){
        $cha=$jiaarr[$key]['KW']-$num;        //todo:先取一个
        $j=$key;
        foreach($jiaarr as $k=>$v){
                if(!$cha){
                    return $k;
                }
                if($jiaarr[$k]['KW']-$num>0&&$jiaarr[$k]['KW']-$num<$cha&&$cha>0){
                    $cha=$jiaarr[$k]['KW']-$num;
                    $j=$k;
                }
        }
        if($j==$key&&$jiaarr[$key]['KW']-$num<0){
            return false;
        }
  //      var_dump($j.'最后出去的$j');
        return $j;
    }


    public function setDataInfo(){
        // 计算考生总人数
        $count = $this->model->sqlCount("select count(*) from TestStudent");
        $this->dataInfo["totalStudent"] = $count;

        // 计算课程数
        $count = $this->model->sqlCount("select count(*) from TestCourse");
        $this->dataInfo["totalCourse"] = $count;

        // 计算考场数及考位数
        $data = $this->model->sqlFind("select count(*) as NCOUNT,sum(KW)as KW from TESTROOM where status=1");
        $this->dataInfo["totalRoom"] = $data["NCOUNT"];
        $this->dataInfo["totalKw"] = $data["KW"];

        // 计算批次
        $count = $this->model->sqlCount("select count(*) from TESTBATCH");
        $this->dataInfo["totalBatch"] = $count;

        // 计算需要的最大批次
        $sql = $this->model->getSqlMap("exam/getMaxBatch.sql");
        $count = $this->model->sqlCount($sql);
        $this->dataInfo["maxBatch"] = $count;

        // 单科考试最大人数
        $count=$this->model->sqlCount("select top 1 COUNT(*) as ncount from TestStudent group by CourseNo2  ORDER BY ncount DESC");
        $this->dataInfo["maxStudent"] = $count;

    }



    public function setpici(){
        if($this->_hasJson){
            $this->model->startTrans();

            foreach($_POST['bin'] as $val){
            /*    $int=$this->model->sqlExecute('update teststudent set pici=:pici where exists(
                    select * from students where classno=:classno and students.studentno=teststudent.studentno
                )',array(':pici'=>$_POST['pici'],':classno'=>$val));
                var_dump($int);*/
                  $int=$this->model->sqlExecute('update testcourse set batch=:pici where courseno=:courseno',
                  array(':pici'=>$_POST['pici'],':courseno'=>$val));
                  if(!$int){
                      $this->model->rollback();
                      exit('修改失败');
                  }
            }
            $this->model->commit();
            exit('修改成功');
        }
        $this->display();



    }



}

/*function demo($num,$arr){
    $panduan=false;
    for($i=0;$i<count($arr);$i++){
        if($num<$arr[$i]){
            $panduan=true;
        }
        break;
    }else[

    ]
    //todo:如果都比它小
    if(!$panduan){

    }

}*/


function getSimilar($rs,&$arr,&$arrIndex,$count=1){


    $maxIndex = getMaxIndex($arrIndex); //取得最大房间号

    $_countRs = $rs;
    $reVal = array();

    if($rs>$maxIndex){          //todo:人数大于教室考位数考位数
        if($count>=3) return false;//如果大于第三次直接退出                //todo:已经不排了,三个考场都排满了
        $arrIndex[$maxIndex]--;
        $reVal[$count] = end($arr[$maxIndex]);
        $reVal[$count]['rs']=$maxIndex;
        array_pop($arr[$maxIndex]);
        $_reVal = getSimilar($rs-$maxIndex,$arr,$arrIndex,$count+1);
        if($_reVal===false) return false;
        else $reVal[$count+1] = $_reVal[$count+1];
    }

    while($_countRs<=$maxIndex){
        if(array_key_exists($_countRs,$arrIndex) && $arrIndex[$_countRs]>0){
            $reVal[$count] = end($arr[$_countRs]);
            $reVal[$count]['rs']=$rs;
            array_pop($arr[$_countRs]);
            $arrIndex[$_countRs]--;
            break;
        }
        $_countRs++;
    }

    return $reVal;
}





//todo:取得最大下标
function getMaxIndex($arrIndex){
    $val = end($arrIndex);
    if($val>0) return key($arrIndex);

    while(true){
        $val = prev($arrIndex);
        if($val===false) return false;
        elseif($val>0) return key($arrIndex);
    }
    return false;
}