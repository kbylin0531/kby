<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/10/23
 * Time: 13:21
 */

/**
 * Class SelectAction 成绩查询 控制器
 */
class SelectAction extends  RightAction {
    /**
     * 成绩查询模型实例
     * @var ResultSelectModel
     */
    private $model = null;

    public function __construct(){
        parent::__construct();
        $this->model = new ResultSelectModel();
    }
    /**
     * 学生个人信息查询界面
     */
    public function pagePersonScores(){
        $this->display('pagePersonScores');
    }

    /**
     * 学生个人信息查询 数据获取
     * @param $year
     * @param $term
     * @param $studentno
     */
    public function selectPersonScores($year,$term,$studentno){
        $personalInfo = $this->model->getPersonalInfo($year,$term,$studentno);
        if(is_string($personalInfo) or !$personalInfo){
            $this->failedWithReport("查询学生信息失败！{$personalInfo}");
        }

        $scoresInfo = $this->model->getPersonScoresInfoList($year,$term,$studentno);
        if(is_string($scoresInfo) or !$scoresInfo){
            $this->failedWithReport("查询学生课程总表失败！{$scoresInfo}");
        }

        //期中
        $midtermscoresPasslessInfo = $this->model->getPersonScoresPasslessInfoList($year,$term,$studentno,3);
        if(is_string($midtermscoresPasslessInfo) or !$midtermscoresPasslessInfo){
            $this->failedWithReport("查询学生期中未通过课程失败！{$midtermscoresPasslessInfo}");
        }
        //期末
        $finalsscoresPasslessInfo = $this->model->getPersonScoresPasslessInfoList($year,$term,$studentno,4);
        if(is_string($finalsscoresPasslessInfo) or !$finalsscoresPasslessInfo){
            $this->failedWithReport("查询学生期末未通过课程失败！{$finalsscoresPasslessInfo}");
        }
        //总评
        $generalscoresPasslessInfo = $this->model->getPersonScoresPasslessInfoList($year,$term,$studentno,1);
        if(is_string($generalscoresPasslessInfo) or !$generalscoresPasslessInfo){
            $this->failedWithReport("查询学生总评未通过课程失败！{$generalscoresPasslessInfo}");
        }
        //补考
        $resitscoresPasslessInfo = $this->model->getPersonScoresPasslessInfoList($year,$term,$studentno,2);
        if(is_string($resitscoresPasslessInfo) or !$resitscoresPasslessInfo){
            $this->failedWithReport("查询学生补考未通过课程失败！{$resitscoresPasslessInfo}");
        }

        $this->ajaxReturn(array(
            'person_info'   => $personalInfo,
            'scores_info'   => $scoresInfo,
            'midterm_scores_passless_info'  => $midtermscoresPasslessInfo,
            'finals_scores_passless_info'  => $finalsscoresPasslessInfo,
            'general_scores_finals_passless_info'  => $generalscoresPasslessInfo,
            'resitscores_resit_passless_info'  => $resitscoresPasslessInfo,
        ),'JSON');
    }

    /**
     * 不及格名单汇总 页面
     * @param null $year
     * @param null $term
     * @param null $coursegroup
     * @param string $schoolno
     * @param string $classno
     * @param int $scoretype
     */
    public function pagePasslessStudents($year=null,$term=null,$coursegroup=null,$schoolno='%',$classno='%',$scoretype=1){
        if(REQTAG === 'export'){
            $this->limitOff();
            $list = $this->model->getPasslessStudentsTableList($year,$term,$coursegroup,$scoretype,$schoolno,$classno);
            //初始化PHPExcel
            $data = array();
            //设置对齐信息和数据域
            $data['title'] = '不及格名单汇总';
            $data['head'] = array(
                'coursegroup' => array( '课号', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'coursename' => array( '课名', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'studentname' => array( '姓名', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'studentno' => array( '学号', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'classname' => array( '班级名称', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'schoolname' => array( '学部名称', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'approach' => array( '修课方式', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'general_score' => array( '总评成绩', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'resit_score' => array( '补考成绩', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            );
            $data['body'] = $list['rows'];

            $excelModel = new ExcelExtensionModel();
            $excelModel->export($data, $data['title']);
        }
        $this->display('pagePasslessStudents');
    }
    /**
     * 不及格名单汇总 数据源
     * @param $year
     * @param $term
     * @param $coursegroup
     * @param string $schoolno
     * @param string $classno
     * @param int $scoretype
     */
    public function listPasslessStudents($year,$term,$coursegroup,$schoolno='%',$classno='%',$scoretype=1){
        $rst = $this->model->getPasslessStudentsTableList($year,$term,$coursegroup,$scoretype,$schoolno,$classno,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($rst)){
            $this->failedWithReport("查询失败！{$rst}");
        }
        $this->ajaxReturn($rst ,'JSON');
    }




    /**
     * 毕业前补考学生列表 页面显示
     */
    public function pageRetakeStudents(){
        $this->display('pageRetakeStudents');
    }
    /**
     * 毕业前补考学生列表 获取数据
     * @param $year
     * @param $term
     * @param $courseno
     * @param $coursename
     * @param $school
     */
    public function listRetakeStudents($year,$term,$courseno,$coursename,$school){
        $rst = $this->model->getRetakeStudentsTableList($year,$term,$courseno,$coursename,$school,
            $this->_pageDataIndex,$this->_pageDataIndex+$this->_pageSize);
        if(is_string($rst)){
            $this->failedWithReport("查询失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }
    /**
     * 班级学期成绩汇总表 页面
     */
    public function pageClassStudentsScores(){
        $this->display('pageClassStudentsScores');
    }

    /**
     * 班级学期成绩汇总表 页面数据获取(使用表格格式进行格式化)
     * @param $year
     * @param $term
     * @param $classno
     * @param $approach
     */
    public function listClassStudentScores($year,$term,$classno,$approach){
        $result = $this->_getClassStudentScoresList($year,$term,$classno,$approach);
        $this->ajaxReturn(array(
            'total' => count($result),
            'rows'  => $result,
        ),'JSON');
    }

    /**
     * 查询课程成绩 页面
     */
    public function pageCourseStudentScore(){
        $this->display('pageCourseStudentScore');
    }
    /**
     * 查询课程成绩 数据源
     * @param $year
     * @param $term
     * @param $courseno
     * @param $studentno
     */
    public function listCourseStudentScore($year,$term,$courseno,$studentno){
        $rst = $this->model->getCourseStudentScoreTableList($year,$term,$courseno,$studentno,$this->_pageDataIndex,$this->_pageDataIndex+$this->_pageSize);
        if(is_string($rst)){
            $this->failedWithReport("查询失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }

    /**
     * 课程成绩导出
     * @param $year
     * @param $term
     * @param $courseno
     * @param $studentno
     */
    public function exportCourseStudentScore($year,$term,$courseno,$studentno){
        $rst = $this->model->getCourseStudentScoreTableList($year,$term,$courseno,$studentno);
        if(is_string($rst)){
            $this->failedWithReport("查询失败！{$rst}");
        }
        //初始化PHPExcel
        $this->model->initPHPExcel();

        //设置对齐信息和数据域
        $data['title'] = '课程学生成绩列表';
        $data['head'] = array(
            'coursegroup' => array( '课号', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'coursename' => array( '课名', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'studentname' => array( '学号', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'studentno' => array( '姓名', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'credit' => array( '学分', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'approach' => array( '修课方式', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'normal_score' => array( '平时', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'midterm_score' => array( '期中', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'finals_score' => array( '期末', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'general_score' => array( '总评', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'resit_score' => array( '补考', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            'retake_score' => array( '重修', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
        );
        $data['body'] = $rst['rows'];

        //输出Excel文件
        $this->model->fullyExportExcelFile($data, $data['title']);
    }








    /**
     * 班级学期成绩汇总表 页面数据获取(未格式化)
     * @param $year
     * @param $term
     * @param $classno
     * @param $approach
     * @return array
     */
    private function _getClassStudentScoresList($year,$term,$classno,$approach){
        $credits = $this->model->getClassStudentCreditConditions($year,$term,$classno);
        $scores = $this->model->getClassStudentsCoursesScores($year,$term,$classno,$approach);

        if(is_string($credits) or is_string($scores)){
            $this->failedWithReport("获取失败!{$credits}  {$scores} ");
        }
        $result = array();
        $ic=count($credits); $jc=count($scores);
        for($i=0;$i<$ic;$i++){
            $result[$i] = $credits[$i];
            for($j=0;$j<$jc;$j++){
                if($credits[$i]['studentno'] !== $scores[$j]['studentno']){
                    continue;
                }
                $result[$i][$scores[$j]['coursegroup'].'_0'] = $scores[$j]['midterm_score'];
                $result[$i][$scores[$j]['coursegroup'].'_1'] = $scores[$j]['finals_score'];
                $result[$i][$scores[$j]['coursegroup'].'_2'] = $scores[$j]['general_score'];
                $result[$i][$scores[$j]['coursegroup'].'_3'] = $scores[$j]['normal_score'];

                //课程信息
                $result[$i]['courses'][$scores[$j]['coursegroup']]['coursename'] = $scores[$j]['coursename'];
                $result[$i]['courses'][$scores[$j]['coursegroup']]['credit']     = $scores[$j]['credits'];
            }
        }
        return $result;
    }

    /**
     * Excel计算单元格列的名称
     * @param int $num 相对null的偏移
     * @return string
     */
    protected function chr($num){
        static $cache = array();
        //ord('Z') = 90  ord('A') = 65
        if(!isset($cache[$num])){
            $num = intval($num);
            $gap = $num - 90;
            if($gap > 0){//是否超过一个'Z'的限制
                $piecenum = floor($gap/26); // 几段
                $cache[$num] = $this->chr(65 + $piecenum).chr(65+$gap - $piecenum * 26);
            }else{
                $cache[$num] = chr($num);
            }
        }
        return $cache[$num];
    }

    /**
     * 班级学期成绩汇总表 导出Excel,代替直接跳到打印页面
     * @param $year
     * @param $term
     * @param $classno
     * @param $approach
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function exportClassStudentScores($year,$term,$classno,$approach){
        ini_set("max_execution_time", "1800");
        $rst = $this->_getClassStudentScoresList($year,$term,$classno,$approach);
        if(!$rst) $this->exitWithUtf8('导出的数据为空，请查询对应的班级中是否有学生');

        $classModel = new ClassesModel();
        $classInfo = $classModel->getClassInfoByClassno($classno);
        if(is_string($classInfo)) $this->exitWithUtf8("获取班级信息失败！{$classInfo}");
        $title = '宁波市职业教育中心学校'.$year.'学年第'.(intval($term) > 1?'二':'一').'学期'.trim($classInfo[0]['CLASSNAME']).'成绩单';

        vendor('PHPExcel.PHPExcel');
        $phpExcel = new PHPExcel();
        $phpExcel->getProperties()->setCreator('Maarten Balliauw')
            ->setLastModifiedBy('Maarten Balliauw')
            ->setTitle('Office 2007 XLSX Test Document')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');

        $bigTitleStyle = array(
            'font'      => array('bold' => true, 'color' => array('argb' => '00000000'), 'size' => 18),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical'  => PHPExcel_Style_Alignment::VERTICAL_CENTER)
        );
        $littleTitleStyle = array(
            'font'      => array('bold' => false,'color'=>array('argb' => '00000000'),'size'=>10),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER)
        );
//        $bodyStyle = array(
//            'font' => array('bold' => false,'color'=>array('argb' => '00000000'),'size'=>10),
//            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER)
//        );

        $phpExcel->getActiveSheet()->setTitle($title);
        $sample = $rst[0]['courses'];
        $phpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);//设置列的宽度
        //合并单元格
        $end = $this->chr( ord('A') + 5 + count($sample) * 4 - 1 );

//        mist($end,'A1:'.$end);
        $phpExcel->getActiveSheet(0)->mergeCells('A1:'.$end.'1');
        //设置默认字体和大小
        $phpExcel->getDefaultStyle()->getFont()->setName('宋体');
        $phpExcel->getDefaultStyle()->getFont()->setSize(11);
        $phpExcel->setActiveSheetIndex()->setCellValue('A1',$title);
        //大标题样式设置
        $phpExcel->getActiveSheet()->getStyle('A1')->applyFromArray($bigTitleStyle);

        $phpExcel->getActiveSheet(0)->mergeCells('A2:A3');
        $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit('A2', '序号',PHPExcel_Cell_DataType::TYPE_STRING);
        $phpExcel->getActiveSheet(0)->mergeCells('B2:B3');
        $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit('B2', '学号',PHPExcel_Cell_DataType::TYPE_STRING);
        $phpExcel->getActiveSheet(0)->mergeCells('C2:C3');
        $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit('C2', '姓名',PHPExcel_Cell_DataType::TYPE_STRING);
        $phpExcel->getActiveSheet(0)->mergeCells('D2:D3');
        $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit('D2', '获得学分',PHPExcel_Cell_DataType::TYPE_STRING);
        $phpExcel->getActiveSheet(0)->mergeCells('E2:E3');
        $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit('E2', '选课学分',PHPExcel_Cell_DataType::TYPE_STRING);
        //小标题样式设置
        $phpExcel->getActiveSheet()->getStyle('A2')->applyFromArray($littleTitleStyle);
        $phpExcel->getActiveSheet()->getStyle('B2')->applyFromArray($littleTitleStyle);
        $phpExcel->getActiveSheet()->getStyle('C2')->applyFromArray($littleTitleStyle);
        $phpExcel->getActiveSheet()->getStyle('D2')->applyFromArray($littleTitleStyle);
        $phpExcel->getActiveSheet()->getStyle('E2')->applyFromArray($littleTitleStyle);


        $offset = 5; //课程开始位置较第一个单元格的偏移
        $step = 4;   //没门课程到后面的步移
        $oa = ord('A');
        foreach($sample as $courseno=>$courseinfo) {
            $beginindex = $oa + $offset;
            $begin = $this->chr($beginindex);
//            $_endindex = $beginindex + $step - 1;
//            if($_endindex > 26){
//                $end = $oa+floor($_endindex/26)
//            }
            $end = $this->chr($beginindex + $step - 1);
//            echo " #{$begin}#{$end}# ";
            try{
                $phpExcel->getActiveSheet(0)->mergeCells($begin.'2:'.$end.'2');
                $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit($begin.'2',$courseinfo['coursename'],PHPExcel_Cell_DataType::TYPE_STRING);
                $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit($this->chr($beginindex     ).'3','平时',PHPExcel_Cell_DataType::TYPE_STRING);
                $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit($this->chr($beginindex + 1 ).'3','期中',PHPExcel_Cell_DataType::TYPE_STRING);
                $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit($this->chr($beginindex + 2 ).'3','期末',PHPExcel_Cell_DataType::TYPE_STRING);
                $phpExcel->setActiveSheetIndex(0)->setCellValueExplicit($this->chr($beginindex + 3 ).'3','总评',PHPExcel_Cell_DataType::TYPE_STRING);
                //小标题样式设置
                $phpExcel->getActiveSheet()->getStyle($begin.'2')->applyFromArray($littleTitleStyle);
                $phpExcel->getActiveSheet()->getStyle($this->chr($beginindex).'3')->applyFromArray($littleTitleStyle);
                $phpExcel->getActiveSheet()->getStyle($this->chr($beginindex + 1 ).'3')->applyFromArray($littleTitleStyle);
                $phpExcel->getActiveSheet()->getStyle($this->chr($beginindex + 2 ).'3')->applyFromArray($littleTitleStyle);
                $phpExcel->getActiveSheet()->getStyle($this->chr($beginindex + 3 ).'3')->applyFromArray($littleTitleStyle);
                $offset += $step;
            }catch (Exception  $e){
//                echo $e->getMessage();
            }
        }

        $activeSheet = $phpExcel->getActiveSheet();
        $flag = ord('E');
        foreach($rst as $key=>$value){
            try{
                $offset = $key + 4;
                $activeSheet->setCellValue('A'.$offset, $key);
                $activeSheet->setCellValue('B'.$offset, $value['studentno']);
                $activeSheet->setCellValue('C'.$offset, $value['studentname']);
                $activeSheet->setCellValue('D'.$offset, $value['passedcredit']);
                $activeSheet->setCellValue('E'.$offset, $value['totalcredit']);

                $offset2 = 1;
                foreach($sample as $courseno=>$courseinfo) {
//                    mistey($flag+$offset2,$this->chr($flag+$offset2),$value[$courseno.'_0'],
//                        $flag+$offset2+1,$this->chr($flag+$offset2+1),$value[$courseno.'_1'],
//                        $flag+$offset2+2,$this->chr($flag+$offset2+2),$value[$courseno.'_2']
//                    );


                    $activeSheet->setCellValue($this->chr($flag+$offset2).''.$offset,$value[$courseno.'_3']);
                    $activeSheet->setCellValue($this->chr($flag+$offset2 + 1).''.$offset,$value[$courseno.'_0']);
                    $activeSheet->setCellValue($this->chr($flag+$offset2 + 2).''.$offset,$value[$courseno.'_1']);
                    $activeSheet->setCellValue($this->chr($flag+$offset2 + 3).''.$offset,$value[$courseno.'_2']);
                    $offset2 += 4; //偏移加4
                }
            }catch (Exception  $e){
                mist($e->getMessage());
            }
        }

        //生成输出下载
        header('Content-Type: application/vnd.ms-excel');
        $filename = $title;//文件名转码
        header("Content-Disposition: attachment;filename=".iconv('UTF-8','GB2312',$filename.".xls"));
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');
        $objWriter = PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 获取班级课程信息
     * @param $year
     * @param $term
     * @param $classno
     */
    public function selectClassCourses($year,$term,$classno){
        $rst = $this->model->getClassCourses($year,$term,$classno);
        if(is_string($rst)){
            $this->exitWithReport("查询班级课程信息失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }

}