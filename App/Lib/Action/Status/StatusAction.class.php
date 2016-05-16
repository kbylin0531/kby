<?php
class StatusAction extends RightAction{
    private $model1;             //数据库对象实例
    private $xssign=array();        //保存替换前的变量

    public function __construct(){
        parent::__construct();
        $this->model1 = new StatusModel();
        $this->assign('quanxian',trim($_SESSION['S_USER_INFO']['ROLES']));
    }

    public function studentregister(){
        $yearterm=$this->getarr("course/getCourseYearTerm.sql",array(":TYPE"=>"C"));
        $this->assign('YEARTERM',$yearterm);
        $this->xiala('regcodeoptions','regcode');
        $this->xiala('classcode','classcode');                    //来源
        $this->assign('schools',getSchoolList());                        //学院
        $this->xiala('statusoptions','statusoption');           //学籍状态
        $this->xiala('majorcode','majorcode');

        $this->display();
    }

    /**
     * excel导入编辑
     */
    public function excelimpstatusedit()
    {
        $this->display();
    }

    /**
     * excel导入保存
     */
    public function excelimpstatussave(){
        set_time_limit(0);
        if ( $_FILES["afile"]["type"] == "application/vnd.ms-excel" ){
            $inputFileType = 'Excel5';
        }
        elseif ( $_FILES["afile"]["type"] == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ){
            $inputFileType = 'Excel2007';
        }
        else {
            echo "Type: " . $_FILES["afile"]["type"] . "<br />";
            echo "非法的文件格式！";
            exit();
        }

        if ($_FILES["afile"]["error"] > 0)
        {
            echo "Error: " . $_FILES["afile"]["error"] . "<br />";
            exit();
        }

        $inputFileName = C("__UPFILE__").  getGUIDStr(session_id()).".".pathinfo($_FILES["afile"]["name"], PATHINFO_EXTENSION);  //$_FILES["afile"]["name"];
        $suc = move_uploaded_file($_FILES["afile"]["tmp_name"], $inputFileName);

        //导入phpExcel
        vendor("PHPExcel.PHPExcel");

        //设置php服务器可用内存，上传较大文件时可能会用到
        ini_set('memory_limit', '1024M');

        $objReader = PHPExcel_IOFactory::createReader($inputFileType);

        $WorksheetInfo = $objReader->listWorksheetInfo($inputFileName);

        //读取文件最大行数、列数，偶尔会用到。
//        $maxRows = $WorksheetInfo[0]['totalRows'];
//        $maxColumn = $WorksheetInfo[0]['totalColumns'];
        //列数可用于粗略判断所上传文件是否符合模板要求

        //设置只读，可取消类似"3.08E-05"之类自动转换的数据格式，避免写库失败
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($inputFileName);
        $sheetData = $objPHPExcel->getSheet(0)->toArray(null,true,true,true);

        $result = array();
        $succount = 0;
        $failcount = 0;

        $key_val = array (
            '姓名'=>'A',      // ****
            '学号'=>'B',      // ****
            '性别'=>'C',      // ****
            '班号'=>'D',      // ****
            '班级名称'=>'E',
            '当前状态'=>'F',
            '出生日期'=>'G',
            '入学年月'=>'H',    // ****
            '学制'=>'I',       // ****
            '专业条目代码'=>'J',// ****
            '专业'=>'K',
            '学部'=>'L',       // ****
            '政治面貌'=>'M',
            '联系方式'=>'N',
            '民族'=>'O',
            '毕业学校'=>'P',
            '家庭住址'=>'Q',
            '邮编'=>'R',
            '户口'=>'S',
            '身份证号'=>'T',
        );


        $model = new StatusModel();

        $_SEX = $model->getSexCodeMap(true);
        is_string($_SEX) and $this->exitWithReport('获取性别数据时发生错误'.$_SEX);
        $_STATUS = $model->getStatusOptionMap(true);
        is_string($_STATUS) and $this->exitWithReport('获取学籍状态数据时发生错误'.$_STATUS);
        $_SCHOOLS = $model->getSchoolMap(true);
        is_string($_SCHOOLS) and $this->exitWithReport('获取学部数据时发生错误'.$_SCHOOLS);
        $_PARTYS = $model->getPartyCode(true);
        is_string($_PARTYS) and $this->exitWithReport('获取政治面貌数据时发生错误'.$_SCHOOLS);
        $_NATION = $model->getNationCode(true);
        is_string($_NATION) and $this->exitWithReport('获取名族数据时发生错误'.$_NATION);

        foreach ( $sheetData as $key => $words ){
            if ( $key != 1 ){//第一行作为标题栏
                $studentname = trim($words[$key_val['姓名']]);
                $studentno = trim($words[$key_val['学号']]);
                $sexname = trim($words[$key_val['性别']]);
                $sexcode = '';
                $classno = trim($words[$key_val['班号']]);

//                $classname = trim($words[$key_val['班机名称']]);
                $curstatus = trim($words[$key_val['当前状态']]);
                $curstatuscode = '';
                $birthdate = trim($words[$key_val['出生日期']]);
                $enterdate = trim($words[$key_val['入学年月']]);
                $years = trim($words[$key_val['学制']]);
                $majorcode = trim($words[$key_val['专业条目代码']]);
//                $majorname = trim($words[$key_val['专业名称']]);
                $school = trim($words[$key_val['学部']]);
                $schoolcode = '';
                $politicalstate = trim($words[$key_val['政治面貌']]);
                $contact = trim($words[$key_val['联系方式']]);
                $nationcode = trim($words[$key_val['民族']]);
                $graduateschool = trim($words[$key_val['毕业学校']]);
                $address = trim($words[$key_val['家庭住址']]);
                $postcode = trim($words[$key_val['邮编']]);
//                $telno = trim($words[$key_val['电话']]);
                $sourseplace = trim($words[$key_val['户口']]);
                $idcode = trim($words[$key_val['身份证号']]);


                $iresult = array();
                $iresult["row"] = $key;

                //必填项 $sexname,
                if(!$this->allValid($studentname,$studentno,$classno,$enterdate,$years,$majorcode,$school)){
                    $failcount++;
//                    var_dump($studentname,$studentno,$sexname,$classno,$enterdate,$years,$majorcode,$school);
                    $iresult['content'] = "第[{$key}]行出现空值，导入该条记录中止进行！";
                }else{
                    if(!empty($enterdate)){
                        $matches = array();
                        //针对'2011-05'这种样式
                        preg_match('/^\s*\d{4}-\d{2}\s*$/',$enterdate,$matches);
                        if(count($matches)){
                            $enterdate .= '-01';
                        }
                        //20100101这样的格式
                        preg_match('/^\s*\d{6}\s*$/',$enterdate,$matches);
                        if(count($matches)){
                            $temp = trim($enterdate);
                            $enterdate  = substr($temp,0,4).'-'.substr($temp,4,2).'-'.substr($temp,6);
                        }
                    }
                    if(!empty($birthdate)){
                        $matches = array();
                        //针对'2011-05'这种样式
                        preg_match('/^\s*\d{4}-\d{2}\s*$/',$birthdate,$matches);
                        if(count($matches)){
                            $birthdate .= '-01';
                        }
                        //20100101这样的格式
                        preg_match('/^\s*\d{6}\s*$/',$birthdate,$matches);
                        if(count($matches)){
                            $temp = trim($birthdate);
                            $birthdate  = substr($temp,0,4).'-'.substr($temp,4,2).'-'.substr($temp,6);
                        }
                    }


                    if (!empty($sexname)) $sexcode = trim($_SEX[$sexname]);
                    if (!empty($curstatus)) $curstatuscode = trim($_STATUS[$curstatus]);
                    if (!empty($school)) $schoolcode = trim($_SCHOOLS[$school]);
                    if (!empty($politicalstate)) $politicalstate = trim($_PARTYS[$politicalstate]);
                    if (!empty($nationcode)) $nationcode = trim($_NATION[$nationcode]);

                    $rst = $model->getStudents(trim($studentno));

                    if(is_string($rst)){
                        $failcount++;
                        $iresult['content'] = "学号为[{$studentno}]的学生已经存在于数据表中".$rst;
                    }elseif($rst){
                        $failcount++;
                        $iresult['content'] = "学号为[{$studentno}]的学生已经存在！";
                    }else{
                        //导入学生到STUDNET和PERSONAL表中
                        $toStudent = array(
                            'STUDENTNO' => $studentno,
                            'NAME'  => $studentname,
                            'SEX'   => $sexcode,
                            'CLASSNO'   => $classno,
                            'STATUS'    => $curstatuscode,
                            'ENTERDATE' => $enterdate,
                            'YEARS' => $years,
                            'SCHOOL'    => $schoolcode,
                            'CONTACT'   => $contact,
                            'PASSWORD'  => '123456',
                        );
                        $toPerson  = array(
                            'NAME'  => $studentname,
                            'STUDENTNO' => $studentno,
                            'SEX'   => $sexcode,
                            'CLASSNO'   => $classno,
                            'BIRTHDAY'  => $birthdate,
                            'DAYOFENROLL'   => $enterdate,
                            'YEARS'     => $years,
                            'MAJOR'     => $majorcode,
                            'SCHOOL'    => $schoolcode,
                            'PARTY'     => $politicalstate,
                            'NATIONALITY'   => $nationcode,
                            'ADDRESS'   => $address,
                            'POSTCODE'  => $postcode,
                            'TEL'       => $contact,
                            '籍贯'        => $sourseplace,
                            'ID'        => $idcode,
                            'MIDSCHOOL' => $graduateschool,//毕业院校
                        );
                        $rstP= null;
                        $rstS = $model->insertStudents($toStudent);
                        if(is_string($rstS)){
                            $failcount++;
                            $iresult['content'] = '添加到学生表的过程出现错误！'.$rstS;
                        }elseif(!$rstS){
                            $failcount++;
                            $iresult['content'] = "未能成功添加学号为[{$studentno}]的学生到学生表中！";
                        }else{
                            $rstP = $model->insertPersonal($toPerson);
                            if(is_string($rstP)){
                                $failcount++;
                                $iresult['content'] = '添加到个人表的过程出现错误！'.$rstP;
                            }elseif(!$rstP){
                                $failcount++;
                                $iresult['content'] = "未能成功添加学号为[{$studentno}]的学生到个人表中！";
                            }else{
                                ++$succount;
                                continue;//跳过添加错误记录的阶段
                            }
                        }
                    }
                }
                array_push($result, $iresult);
            }
        }

        $this->assign("result",$result);
        $this->assign("succount",$succount);
        $this->assign("failcount",$failcount);
        $this->display("excelimpstatusresult");

    }

    /**
     * 检查参数中是否含有空值
     * @return bool
     */
    private function allValid(){
        foreach(func_get_args() as $val){
            is_string($val) and $val = trim($val);
            if(empty($val)){
                return false;
            }
        }
        return true;
    }


    private function getSchoolMap($orig,$flag = true){
        if($orig === false){
            return null;
        }
        $schoolsmap = array();
        foreach($orig as $key=>$val){
            if($flag){
                $schoolsmap[$val['schoolno']] = $val['schoolname'];
            }else{
                $schoolsmap[$val['schoolname']] = $val['schoolno'];
            }
        }
        return $schoolsmap;
    }


    /*
     *按学生号注册的方法
     */
    public function studentNO(){

        $arr=$this->getarr('status/statusStudent.SQL',array(':StudentNo'=>$_POST['studentno']));

        if(!$arr){
            exit('false');
        }
        $arr333=$this->getQuery('status/statusRegCode.SQL',array(':studentno'=>$_POST['studentno']));

        $arr['regcode']=$arr333;
        echo json_encode($arr);
    }


    /*
     * todo:按班级注册的方法
     */
    public function regesClass(){
        //todo:获得学年 学期
        $yearterm=$this->getarr("course/getCourseYearTerm.sql",array(":TYPE"=>"C"));
        //todo:要注册的班级的信息
        $pd=true;
        $this->model1->startTrans();
        $arr=$this->model1->sqlQuery("SELECT * FROM STUDENTS where RTRIM(STUDENTS.ClassNo) ='".trim($_POST['classno']."'"));
        foreach($arr as $key=>$val){
            $bool=$this->model1->sqlExecute('INSERT INTO REGDATA(STUDENTNO,YEAR,TERM,REGDATE,REGCODE) VALUES(:studentno,:year,:term,:regdate,:regcode)',array(':studentno'=>$val['STUDENTNO'],':year'=>$yearterm['YEAR'],':term'=>$yearterm['TERM'],':regdate'=>date('Y-m-d H:i:s'),':regcode'=>'A'));
            if(!$bool)
                $pd=false;
        }
        if(!$pd){
            $this->model1->rollback();
            echo '本班已经注册过了';
        }else{
            $this->model1->commit();
            echo '成功为本班级注册';
        }

    }

    /*
     *新生报到的方法
     */
    public function newStudentNO(){
        $_POST['examno']==""?$zarr=array(':NEWSTUDENTNO'=>trim($_POST['newstudentno']),':EXAMNO'=>trim($_POST['newstudentno'])):$zarr=array(':NEWSTUDENTNO'=>trim($_POST['examno']),':EXAMNO'=>trim($_POST['examno']));

        $arr=$this->getarr('status/statusNewStudent.SQL',$zarr);

        if(!$arr){
            exit('false');
        }
        echo json_encode($arr);
    }

    /*
     *获取查询数据(单条)
     */
    public function getarr($url,$bind="",$bool=false){
        if(!$bool)
            $sql=$this->model1->getSqlMap($url);
        else
            $sql=$url;
        $arr=$this->model1->sqlFind($sql,$bind);
        return $arr;
    }

    //获取查询数据(多条)
    public function getQuery($url,$bind='',$bool=false){
        if(!$bool)
            $sql=$this->model1->getSqlMap($url);
        else
            $sql=$url;
        $arr=$this->model1->sqlQuery($sql,$bind);
        return $arr;
    }


    /*
     * 修改注册信息时候用到的方法  正常注册|休学|未报到
     */
    public function updatereg(){
        $arr=$this->model1->sqlFind($this->model1->getSqlMap('status/updateReg.SQL'),array(':STUDENTNO'=>$_POST['STUDENTNO'],':REGCODE'=>$_POST['REGCODE']));
        if($arr)
            echo 'false';
    }

    /*
     * 修改学生学籍信息的时候，所使用的方法
     */
    public function studentUpdate(){

        $withdraw = M('students');                  //学生表
        $withdraw2=  M('personal');                 //学生人物信息表
        $withdraw->startTrans();
        if(trim($_POST['STUDENTNO'])==''){
            exit('非法操作');
        }
        $bool=$this->model1->sqlExecute("update students set name=:name,sex=:sex,enterdate=:enterdate,years=:years,classno=:classno,warn=:warn,
              status=:status,contact=:contact,school=:school where studentno=:studentno",
            array(':name'=>$_POST['NAME'],':sex'=>$_POST['SEXCODE'],':enterdate'=>$_POST['ENTERDATE'],':years'=>$_POST['YEARS'],
                ':classno'=>$_POST['CLASSNO'],':warn'=>$_POST['WARN'],':status'=>$_POST['STATUS'],':contact'=>$_POST['CONTACT'],
                ':school'=>$_POST['SCHOOL'],':studentno'=>$_POST['STUDENTNO']));

        $bool2=$this->model1->sqlExecute("update personal set name=:name,sex=:sex,major=:major,class=:class,nationality=:nationality,id=:id,birthday=:birthday,party=:party where studentno=:studentno",
            array(':name'=>$_POST['NAME'],':sex'=>$_POST['SEXCODE'],':major'=>$_POST['MAJOR'],
                ':class'=>$_POST['CLASS'],':nationality'=>$_POST['NATIONALITY'],
                ':id'=>trim($_POST['ID']),':birthday'=>$_POST['BIRTHDAY'],':party'=>$_POST['PARTY'],':studentno'=>$_POST['STUDENTNO']));

        if($bool&&$bool2){
            $withdraw->commit();
            echo '修改成功';
        }else{
            $withdraw->rollback();
            echo '修改出错！';

        }
    }

    /*
     * 新建学生的时候  插入学生数据的方法
     */
    public function newStudent(){
        $this->assign('schools',getSchoolList());
        $this->xiala('nationalitycode','nationalitycode');
        $this->xiala('partycode','partycode');
        $this->display();
    }

    /*
     * 新建 学生数据的方法
     */
    public function insertStudent($studentno,$studentname,$sex,$enterdate,$nationality,$birthday,$years,$school,$classno,$id,$contact,$password,$party){

        $model = new CommonModel();
        $model->startTrans();
        $rst  = $model->createRecord('STUDENTS',array(
            'STUDENTNO' => $studentno,
            'NAME'      => $studentname,
            'SEX'       => $sex,
            'ENTERDATE' => $enterdate,
            'YEARS'     => $years,
            'CLASSNO'   => $classno,
            'CONTACT'   => $contact,
            'SCHOOL'    => $school,
            'PASSWORD'  => $password,
        ));
        if(is_string($rst) or !$rst){
            $this->failedWithReport("添加失败！$rst");
        }
        $rst2 = $model->createRecord('PERSONAL',array(
            'studentno' => $studentno,
            'NAME'      => $studentname,
            'SEX'       => $sex,
            'id'        => $id,
            'BIRTHDAY'  => $birthday,
            'NATIONALITY'   => $nationality,
            'SCHOOL'    => $school,
            'PARTY'     => $party,
        ));
        if(is_string($rst) or !$rst){
            $this->failedWithReport("添加失败！$rst");
        }else{
            $model->commit();
            $this->successWithReport("添加成功！");
        }
    }

    /*
     * //todo:判断学号是否有重复的方法
     */
    protected function Studentyz($num){
        return  $bool=$this->getarr("select NAME from STUDENTS where STUDENTNO=:STUDENTNO",array(':STUDENTNO'=>$num),true);
    }

    /*
     *todo: 判断班级号存不存在的方法
     */
    protected function classyz($num){
        return  $bool=$this->getarr("select CLASSNAME from CLASSES where CLASSNO=:CLASSNO",array(':CLASSNO'=>$num),true);
    }



    public function queryStudentReg(){

        if($this->_hasJson){
            $count=$this->getarr('status/statusZhuceCount.SQL',array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':SCHOOL'=>$_POST['SCHOOL']));
            if($ct['total']=$count['']){
                $ct['rows']=$this->getQuery('status/statusZhuceQuery.SQL',array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':SCHOOL'=>$_POST['SCHOOL'],':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
            }else{
                $ct['rows']=array();
            }
            $this->ajaxReturn($ct,'JSON');
            exit;
        }

        if($_POST['SCHOOL']){
            //  $yearterm=$this->getarr("course/getCourseYearTerm.sql",array(":TYPE"=>"C"));
            //todo:查询新生总数
            $newStudent=$this->getarr('status/_queryNewStudent.SQL',array(':SCHOOL'=>$_POST['SCHOOL']));
            $this->xssign['yingbaodao']=$newStudent[''];
            $this->xssign['year']=$_POST['YEAR'];
            $this->xssign['term']=$_POST['TERM'];

            //todo:查询应报到学生数
            $yingbaodao=$this->model1->sqlFind($this->model1->getSqlMap('status/_queryZhenStudent.SQL'),array(':SCHOOL'=>"{$_POST['SCHOOL']}"));
            $this->xssign['zaiceAll']=$yingbaodao['']-$newStudent[''];

            //todo:查询已报道新生生总数
            $yibaodaoNew=$this->model1->sqlFind($this->model1->getSqlMap('status/_queryYibaodaoNEWStudent.SQL'),array(':SCHOOL'=>$_POST['SCHOOL']));
            $this->xssign['yibaodaoNew']=$yibaodaoNew[''];

            //todo:查询应宝刀学生总数
            $yibaodao=$this->model1->sqlFind($this->model1->getSqlMap('status/_queryYibaodaoStudent2.SQL'),array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':SCHOOL'=>$_POST['SCHOOL']));
            $this->xssign['yiceAll']=$yibaodao['']-$yibaodaoNew[''];

            $top=$this->replace();

            echo $top;
            exit;
        }
        $yearterm=$this->getarr("course/getCourseYearTerm.sql",array(":TYPE"=>"C"));
        $this->assign('yearterm',$yearterm);                            //todo:学年 日期
        $this->xiala('regcodeoptions','regcodeoptions');          //todo:注册状态
        $this->xiala('statusoptions','statusoptions');            //todo:学籍状态
        $this->assign('schools',getSchoolList());                          //todo:学院信息

        $this->display();
    }


    //todo:查询班级报到情况的方法
    public function classbaodao(){
        if($this->_hasJson){
            $count=$this->getarr('status/Three_ClassBaodaoCount.SQL',array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':CLASSNO'=>$_POST['CLASSNO']));
            if($ct['total']=$count['']){
                $ct['rows']=$this->getQuery('status/Three_ClassBaodao.SQL',array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':CLASSNO'=>$_POST['CLASSNO'],':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
            }else{
                $ct['rows']=array();
            }
            $this->ajaxReturn($ct,'JSON');
            exit;
        }
        //todo:查询班级应报到人数
        $yingbaodao=$this->getarr('status/Three_countZhuceStudent.SQL',array(':CLASSNO'=>$_POST['CLASSNO']));
        $this->xssign['year']=$_POST['YEAR'];
        $this->xssign['term']=$_POST['TERM'];
        $this->xssign['yingbaodao']=$yingbaodao[''];

        //todo:查询班级实报道人数
        $shibaodao=$yingbaodao=$this->getarr('status/Three_countBaodaoStudent.SQL',array(':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM'],':CLASSNO'=>$_POST['CLASSNO']));
        $this->xssign['yibaodao']=$shibaodao[''];
        $str=$this->replace();
        echo $str;
    }


    //todo:替换占位符
    public function replace(){
        $one=array();
        $two=array();
        foreach($this->xssign as $key=>$val){
            array_push($two,$val);
            array_push($one,'x'.$key.'x');
        }
        $str=str_replace($one,$two,$_POST['str']);
        return $str;
    }
    /*
     * 学籍异动的方法
     */
    public function xuejiyidong($studentno='%',$fileno='%',$infotype='%'){
        if($this->_hasJson){
            $statusModel = new StatusModel();
            $list = $statusModel->listRegisteries($studentno,$fileno,$infotype,$this->_pageDataIndex,$this->_pageSize);
            $this->ajaxReturn($list);
        }

        $data= $this->model1->sqlFind($this->model1->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));
        $this->assign('YEARTERM',$data);
        $this->xiala('regcodeoptions','regcode');
        $this->xiala('classcode','classcode');                    //来源
        $this->assign('schools',getSchoolList());                        //学院
        $this->xiala('statusoptions','statusoption');           //学籍状态
        $this->xiala('majorcode','majorcode');
        $this->xiala('infotype','infotype');                    //todo:警告处分  严重警告处分  记过 记大过等
        $this->display();
    }


    /*
     *  todo:查看某个学籍异动的时候 所用到的方法
     */
    public function selectStudentFile(){
        $arr=$this->getarr('status/statusStudentFileEdit.SQL',array(":RECNO"=>$_POST['recno']));
        if(!$arr){
            exit('false');
        }
        echo json_encode($arr);
    }

    /*
     * 修改 某个 学籍异动的时候 所用到的方法
     */    //todo;学籍异动的权限判断还未做
    public function updateStudentFile(){
        $bool=$this->model1->sqlExecute($this->model1->getSqlMap('status/statusStudentFileUpdate.SQL'),array(':INFOTYPE'=>$_POST['INFOTYPE'],':DATE'=>$_POST['DATE'],':FILENO'=>$_POST['FILENO'],':REM'=>$_POST['REM'],':STUDENTNO'=>$_POST['STUDENTNO'],':OLDSTUDENTNO'=>$_POST['OLDSTUDENTNO']));
        if($bool){
            echo '学籍异动修改成功';
        }
    }




    /*
     * 在册学生管理的方法
     */
    public function StatusRegGate($studentno='%',$studentname='%',$grade='%',$classno='%',$school='%'){

        if(null !== REQTAG){
            $statusModel = new StatusModel();
            if(REQTAG === 'getlist'){
                //获取学生列表
                $list = $statusModel->listStudents($studentno,$studentname,$grade,$classno,$school,$this->_pageDataIndex,$this->_pageSize);
                $this->ajaxReturn($list);
            }elseif(REQTAG === 'getplist'){
                $list = $statusModel->listPersons($studentno,$studentname,$grade,$classno,$school,$this->_pageDataIndex,$this->_pageSize);
                $this->ajaxReturn($list);
            }elseif(REQTAG === 'exports'){
                $rst = $statusModel->listStudents($studentno,$studentname,$grade,$classno,$school);
                if(is_string($rst)){
                    $this->exitWithUtf8("获取数据失败！{$rst}");
                }

                $data = array();
                $statusModel->initPHPExcel();
                $data['title'] = '班级成绩分析';
                //表头
                $data['head'] = array(
                    //默认值如 align type 的设计实例
                    'studentno' => array( '学号', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                    'studentname' => array('姓名','align' => CommonModel::ALI_LEFT,'width'=>10),
                    'sexname' => array('性别','align' => CommonModel::ALI_LEFT,'width'=>10),
                    'classname' => array( '班级', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                    'status' => array( '学籍状态', 'align' => CommonModel::ALI_CENTER,'width'=>10),
                    'warn' => array( '退学警告次数', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                    'taken' => array( '选课学分', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                    'passed' => array( '完成学分', 'align' => CommonModel::ALI_CENTER,'width'=>10),
                    'schoolname' => array( '所在学部', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                );
                //表体
                $data['body'] = array_values($rst['rows']);
                $statusModel->fullyExportExcelFile($data, $data['title']);
            }
        }

        if($this->_hasJson){
            $bind = array(
                ':StudentNo'    => doWithBindStr($_POST['STUDENTNO']),
                ':Name1'    => doWithBindStr($_POST['NAME']),
                ':ClassNo'    => doWithBindStr($_POST['CLASSNO']),
                ':School'    => doWithBindStr($_POST['SCHOOL']),
                ':Status'    => doWithBindStr($_POST['STATUS']),
            );
            $one = $this->model1->getStudentStatusTableList($bind,$this->_pageDataIndex,$this->_pageSize);
//            $count=$this->getarr('status/statusZAICEcount.SQL',array(':StudentNo'=>doWithBindStr($_POST['STUDENTNO']),':Name1'=>doWithBindStr($_POST['NAME']),':ClassNo'=>doWithBindStr($_POST['CLASSNO']),':School'=>doWithBindStr($_POST['SCHOOL']),':Status'=>doWithBindStr($_POST['STATUS']),':oldstudentno'=>doWithBindStr($_POST['OLDSTUDENTNO'])));
//            if($one['total']=$count['']){
//                $one['rows']=$this->getQuery('status/statusZAICEquery.SQL',array(':StudentNo'=>doWithBindStr($_POST['STUDENTNO']),':Name1'=>doWithBindStr($_POST['NAME']),':ClassNo'=>doWithBindStr($_POST['CLASSNO']),':School'=>doWithBindStr($_POST['SCHOOL']),':Status'=>doWithBindStr($_POST['STATUS']),':oldstudentno'=>doWithBindStr($_POST['OLDSTUDENTNO']),':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
//            }else{
//                $one['rows']=array();
//            }
            $this->ajaxReturn($one,'JSON');
            exit;
        }
        $data= $this->model1->sqlFind($this->model1->getSqlMap("course/getCourseYearTerm.sql"),array(":TYPE"=>"C"));

        $this->assign('YEARTERM',$data);
        $this->xiala('regcodeoptions','regcode');
        $this->xiala('classcode','classcode');                    //来源
        $this->assign('schools',getSchoolList());      //所在学院
        $this->xiala('majorcode','majorcode');
        $this->xiala('statusoptions','statusoptions');           //学籍状态statusoption
        $this->display();
    }

    public function studentExp(){
        vendor("PHPExcel.PHPExcel");
        //创建一个新的对象
        $objPHPExcel = new PHPExcel();

        //设置文件属性
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        //重命名工作表名称
        $title="在册学生列表";
        $objPHPExcel->getActiveSheet()->setTitle($title);

        //设置默认字体和大小
        $objPHPExcel->getDefaultStyle()->getFont()->setName("宋体");
        $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);

        //设置默认行高
        //$objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(18);

        //设置默认宽度
        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(10);

        //设置单元格自动换行
        $objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(true);

        //设置默认内容垂直居左
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //设置单元格加粗，居中样式
        $style=array('font' => array('bold' => true,'color'=>array('argb' => '00000000')),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

        //标题设置
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($style);//设置样式
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');//合并A1单元格到M1
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A1',$title);//写入A1单元格内容
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(14);//设置字体大小
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(24);//设置行高

        //列名设置
        $objPHPExcel->getActiveSheet()->getStyle("A2:J2")->applyFromArray($style);//字体样式
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(18);
        //单元格内容写入
        $objPHPExcel->setActiveSheetIndex()->setCellValue('A2',"学号");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('B2',"姓名");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('C2',"性别");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('D2',"主修班级");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('E2',"学籍状态");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('F2',"退学警告次数");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('G2',"积点分");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('H2',"选课学分");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('I2',"完成学分");
        $objPHPExcel->setActiveSheetIndex()->setCellValue('J2',"所在学部");
        //设置宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(15);
        //设置个别列内容居中
        $objPHPExcel->getActiveSheet()->getStyle("A:J")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //获取信息
//        $data=$this->getQuery('status/statusZAICEquery.SQL',array(':StudentNo'=>doWithBindStr($_POST['StudentNo']),':Name1'=>doWithBindStr($_POST['Name']),':ClassNo'=>doWithBindStr($_POST['ClassNo']),':School'=>doWithBindStr($_POST['School']),':Status'=>doWithBindStr($_POST['Status']),':start'=>1,':end'=>300));
        $bind = array(
            ':StudentNo'    => doWithBindStr($_POST['StudentNo']),
            ':Name1'    => doWithBindStr($_POST['Name']),
            ':ClassNo'    => doWithBindStr($_POST['ClassNo']),
            ':School'    => doWithBindStr($_POST['SCHOOL']),
            ':Status'    => doWithBindStr($_POST['Status']),
        );
        $data = $this->model1->getStudentStatusTableList($bind);
        $data = $data['rows'];

        // $data= $this->model1->sqlQuery($this->model1->getSqlMap("status/statusZAICEexp.SQL"),array(':StudentNo'=>doWithBindStr($_POST['STUDENTNO']),':Name1'=>doWithBindStr($_POST['NAME']),':ClassNo'=>doWithBindStr($_POST['CLASSNO']),':School'=>doWithBindStr($_POST['SCHOOL']),':Status'=>doWithBindStr($_POST['STATUS']) ));
        $row=2;
        foreach($data as $val){
            $row++;
            $objPHPExcel->getActiveSheet()->setCellValue("A$row", $val['STUDENTNO']);
            $objPHPExcel->getActiveSheet()->setCellValue("B$row", $val['NAME']);
            $objPHPExcel->getActiveSheet()->setCellValue("C$row", $val['SEX']);
            $objPHPExcel->getActiveSheet()->setCellValue("D$row", $val['CLASSNAME']);
            $objPHPExcel->getActiveSheet()->setCellValue("E$row", $val['STATUSVALUE']);
            $objPHPExcel->getActiveSheet()->setCellValue("F$row", $val['WARN']);
            $objPHPExcel->getActiveSheet()->setCellValue("G$row", $val['POINTS']);
            $objPHPExcel->getActiveSheet()->setCellValue("H$row", $val['TAKEN']);
            $objPHPExcel->getActiveSheet()->setCellValue("I$row", $val['PASSED']);
            $objPHPExcel->getActiveSheet()->setCellValue("J$row", $val['SCHOOLNAME']);
        }
        //边框设置
        $objPHPExcel->getActiveSheet(0)->getStyle("A2:J$row")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);//设置边框

        //开始下载
        $filename = $title.".xls";
        $ua = $_SERVER["HTTP_USER_AGENT"];

        header('Content-Type:application/vnd.ms-excel');
        if(preg_match("/MSIE/", $ua)){
            header('Content-Disposition:attachment;filename="'.urlencode($filename).'"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition:attachment;filename*="utf8\'\''.$filename.'"');
        } else {
            header('Content-Disposition:attachment;filename="'.$filename.'"');
        }
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    public function insertregcode(){
        $rst = $this->model1->addRegisterChangeRecord($_POST['studentno'],$_POST['YEAR'],$_POST['TERM'],date('Y-m-d H:i:s'),$_POST['REGCODE']);
        if($rst[0] === 'error'){
            $this->exitWithReport('更新学籍信息失败'.$rst[1]);
        }else{
            if($rst[1] === 'update'){
                $this->exitWithReport('更新学籍信息成功!','info');
            }elseif($rst[1] === 'insert'){
                $this->exitWithReport('新增学籍变更记录成功!','info');
            }else{
                $this->exitWithReport('未知的执行结果!');
            }
        }
    }

    //专门用于查询的方法
    public function Squery(){
           // echo $_POST['Sqlpath']['count'];
        $count=$this->model1->sqlFind($this->model1->getSqlMap($_POST['Sqlpath']['count']),$_POST['bind']);
      //  var_dump($count);
        if($data['total']=$count['ROWS']){
            $data['rows']=$this->model1->sqlQuery($this->model1->getSqlMap($_POST['Sqlpath']['select']),array_merge($_POST['bind'],array(':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize)));

        }else{
            $data['total']=0;
            $data['rows']=array();
        }


        $this->ajaxReturn($data,'JSON');
    }


    public function Window_studentinfo(){
        $arr=$this->getarr('status/statusNewStudent.SQL',array(':NEWSTUDENTNO'=>$_GET['STUDENTNO'],':EXAMNO'=>$_GET['EXAMNO']));
        $this->assign('content',$arr);
        $this->display();
    }

    public function Window_studentRegis(){
        if(isset($_GET['EXAMNO'])){
            $studentno=$this->model1->sqlFind('SELECT PERSONAL.STUDENTNO FROM PERSONAL WHERE EXAMNO=:EXAMNO',array(':EXAMNO'=>$_GET['EXAMNO']));
            $_GET['STUDENTNO']=$studentno['STUDENTNO'];
        }
        //todo:查询出学生的注册信息（注册页面的上半部分）
        $arr=$this->getarr('status/statusStudent.SQL',array(':StudentNo'=>$_GET['STUDENTNO']));
        //todo:查询出学生的regDATE信息(注册页面的下半部分)
        $arr2=$this->getQuery('select REGDATA.YEAR,REGDATA.TERM,REGCODEOPTIONS.VALUE AS CODE from REGDATA,REGCODEOPTIONS where REGDATA.REGCODE=REGCODEOPTIONS.NAME AND REGDATA.STUDENTNO=:studentno',array(':studentno'=>$_GET['STUDENTNO']),true);
        $this->assign('schools',getSchoolList());
        $this->assign('regdate',$arr2);
        $this->assign('content',$arr);
        $this->display();

    }

    /**
     * 添加学籍异动的方法
     * @param string $studentno
     */
    public function addxuejiyidong($studentno=''){
        $model = new CommonModel();
        $this->assign('yearterm',$model->getYearTerm());
        $this->xiala('infotype','infotype');
        $this->assign('studentno',$studentno);
        $this->display();
    }

    //todo:按 新生报到情况汇总的统计方法
    public function Studenthz(){
        if($this->_hasJson){
            $total=$this->getarr($_POST['sqlpath']['count']);             //'status/Three_hz_count.SQL'
            if($content['total']=$total['']){
                $content['rows']=$this->getQuery($_POST['sqlpath']['select'],array(':start'=>$this->_pageDataIndex,':end'=>$this->_pageDataIndex+$this->_pageSize));
            }else{
                $content['rwos']=array();
            }
            $this->ajaxReturn($content,'JSON');
            exit;
        }
        $left='status/Three_hz_';
        foreach($_POST['sqlpath'] as $key=>$val){
            $$key=$this->getarr($left.$val.'.SQL');
            $name=$$key;
            $this->xssign[$key]=$name[''];
        }
        $this->xssign['year']=$_POST['YEAR'];
        $this->xssign['term']=$_POST['TERM'];
        $str=$this->replace();
        echo $str;
    }


    //todo:综合查询
    public function zonghe(){
        $all=$this->getarr('status/Three_zonghe_ALL.SQL',array(':SCHOOL'=>doWithBindStr($_POST['SCHOOL']),':CLASSNO'=>doWithBindStr($_POST['CLASSNO']),':STATUS'=>doWithBindStr($_POST['STATUS'])));
        $this->xssign['All']=$all['NUMBER'];
        $all2=$this->getarr('status/Three_zonghe_All2.SQL',array(':CLASSNO'=>doWithBindStr($_POST['CLASSNO']),':SCHOOL'=>doWithBindStr($_POST['SCHOOL']),':STATUS'=>doWithBindStr($_POST['STATUS']),':YEAR'=>$_POST['YEAR'],':TERM'=>$_POST['TERM']));

        $this->xssign['All2']=$all2['NUMBER'];
        $str=$this->replace();
        echo $str;
    }


    /**
     * 添加学籍异动记录
     * @param $studentno
     * @param $date
     * @param $fileno
     * @param $rem
     * @param $info
     */
    public function insertRegisteries($studentno,$date,$fileno,$rem,$info,$year,$term){
        $statusModel = new StatusModel();
        $rst = $statusModel->insertRegisteries($studentno,$date,$fileno,$rem,$info,$year,$term);
        exit((is_string($rst) or !$rst )? '添加失败!'.$rst:'添加成功');
    }

    public function statusexecute(){
        $bol=$this->model1->sqlExecute($this->model1->getSqlMap($_POST['sqlpath']),$_POST['bind']);
        if($bol){
            echo 'true';
        }else{
            var_dump($bol);
        }
    }

    //todo:正常注销 或 开除学籍的方法
    public function zhuxiaoxj(){
        //todo:启动回滚
        $this->model1->startTrans();
        //todo:查询出学生的信息
        $arr=$this->model1->sqlFind('select * from STUDENTS where STUDENTNO=:studentno',array(':studentno'=>$_POST[':studentno']));
        //todo:删除学生表信息
        $arr2=$this->model1->sqlExecute('delete from STUDENTS where STUDENTNO=:studentno',array(':studentno'=>$_POST[':studentno']));
        //todo:删除R32表的信息
        $arr3=$this->model1->sqlExecute('delete from R32 where STUDENTNO=:studentno',array(':studentno'=>$_POST[':studentno']));
        //todo:删除R4表信息
        $arr4=$this->model1->sqlExecute('delete from R4 where STUDENTNO=:studentno',array(':studentno'=>$_POST[':studentno']));
        //todo:删除R28表信息
        $arr5=$this->model1->sqlExecute('delete from R28 where STUDENTNO=:studentno',array(':studentno'=>$_POST[':studentno']));
        //todo:向毕业生表插入信息
        $arr6=$this->model1->sqlExecute(
'INSERT INTO GRADUATES
(STUDENTNO,NAME,SEX,GRADDATE,ENTERDATE,YEARS,GRADUATION,CREDITS) VALUES
(:studentno,:name,:sex,:graddate,:enterdate,:years,:graduation,:credits)',
array(':studentno'=>$arr['STUDENTNO'],':name'=>$arr['NAME'],':sex'=>$arr['SEX'],':graddate'=>date('Y-m-d H:i:s'),':enterdate'=>$arr['ENTERDATE'],':years'=>$arr['YEARS'],':graduation'=>$_POST['code'],':credits'=>0));

        //todo:向LOGS 表插入信息
        $bool=$arr&&$arr6?1:0;
        // $arr7=$this->model1->sqlExecute($this->model1->getSqlMap('status/Five_insertLOGS.SQL'),array(':username'=>$_SESSION['S_USER_NAME'],':emal'=>"",':remotehost'=>$_SERVER['REMOTE_HOST'],':remoteip'=>$_SERVER['REMOTE_ADDR'],':derivedfrom'=>'',':useragent'=>substr($_SERVER['HTTP_USER_AGENT'],0,40),':user'=>'',':rooles'=>$_SESSION['S_ROLES'],':group'=>'',':scriptname'=>$_SERVER['SCRIPT_NAME'],':pathinfo'=>array_pop(explode('/',$_SERVER['ORIG_PATH_INFO'])),':query'=>$_SERVER['QUERY_STRING'],':method'=>$_SERVER['REQUEST_METHOD'],':title'=>'',':time'=>date('Y-m-d H:i:s'),':success'=>$bool));
        //  var_dump($arr7);
        $fiex=substr($_SERVER['HTTP_USER_AGENT'],0,40);
        $pathinfo=array_pop(explode('/',$_SERVER['ORIG_PATH_INFO']));
        $time=date('Y-m-d H:i:s');


        if($bool){
            $this->model1->commit();
            echo '除名成功，学生的成绩记录和异动记录被保留，同时学生也被保留在毕业生库中，其它相关记录被删除！';
        }else{
            $this->model1->rollback();
            echo '除名失败！';
        }
    }



    public function xueshengzhuce(){

        $yearTerm = $this->model1->getYearTerm('C');
        $studentStatus = $this->model1->getStudentRegisterStatus($_GET['studentno'],$yearTerm['YEAR'],$yearTerm['TERM']);
        if(is_string($studentStatus)){
            $studentStatus = '';
        }
        $this->assign('studentRegisterData',$studentStatus);

        $this->assign('YEARTERM',$yearTerm);
        $this->xiala('classcode','classcode');                    //来源
        $this->assign('schools',getSchoolList());                          //所在学院
        $this->xiala('majorcode','majorcode');
        $this->xiala('statusoptions','statusoptions');           //学籍状态statusoption

        $this->assign('regcode',$this->model1->getComboRegCode()); //注册状态

        $this->assign('info',$_GET['studentno']);
        $this->xiala('nationalitycode','nationality');
        $this->xiala('partycode','party');
        $this->display();
    }



    public function xueshengedit(){
        $this->xiala('classcode','classcode');                    //来源
        $this->assign('schools',getSchoolList());                          //所在学院
        $this->xiala('majorcode','majorcode');
        $this->xiala('statusoptions','statusoptions');           //学籍状态statusoption
        $this->xiala('regcodeopions','regcode');
        $this->assign('info',$_GET['studentno']);
        $this->display();
    }

    public function studentFileEdit(){
        $this->xiala('infotype','infotype');
        $this->assign('info',$_GET['recno']);
        $this->display();
    }

    public function xinshengbaodao(){

        $this->assign('info',$_GET['newstudentno']);
        $this->assign('info2',$_GET['examno']);
        $this->display();
    }


    public function zonghechaxun(){
        $yearterm=$this->getarr("course/getCourseYearTerm.sql",array(":TYPE"=>"C"));
        $this->assign('yearterm',$yearterm);                            //todo:学年 日期
        $this->xiala('regcodeoptions','regcodeoptions');          //todo:注册状态
        $this->xiala('statusoptions','statusoptions');            //todo:学籍状态
        $this->assign('schools',getSchoolList());                          //todo:学院信息
        $this->display();
    }

    public function one(){

        $this->assign('year',$_GET['year']);
        $this->assign('term',$_GET['term']);
        $this->display();
    }

    public function one_xueyuanbaodao(){
        $this->assign('year',$_GET['year']);
        $this->assign('term',$_GET['term']);
        $this->assign('school',$_GET['school']);
        $this->display();
    }

    public function class_baodao(){
        $this->assign('classno',$_GET['classno']);
        $this->assign('year',$_GET['year']);
        $this->assign('term',$_GET['term']);
        $this->display();

    }

    public function one_xuekehuizong(){
        $this->assign('year',$_GET['year']);
        $this->assign('term',$_GET['term']);
        $this->display();
    }

    public function one_shengfenhuizong(){
        $this->assign('year',$_GET['year']);
        $this->assign('term',$_GET['term']);
        $this->display();
    }

    public function one_zonghechaxun(){
        /* obj['SCHOOL']=$('#sc').val();
                                                                                                                                                   obj['CLASSNO']=$('#class').val();
                                                                                                                                                   obj['REGIS']=$('#regiszt').val()
                                                                                                                                                   obj['STATUS']=$('#statuszt').val();
                                                                                                                                                   obj['YEAR']=$('#zhYEAR').val();
                                                                                                                                                   obj['TERM']=$('#zhTERM').val();*/

        $this->assign('school',str_replace('_','',$_GET['school']));
        $this->assign('classno',str_replace('_','',$_GET['classno']));
        $this->assign('regiszt',str_replace('_','',$_GET['regiszt']));
        $this->assign('status',str_replace('_','',$_GET['status']));
        $this->assign('year',$_GET['year']);
        $this->assign('term',$_GET['term']);
        $this->display();
    }



}



?>