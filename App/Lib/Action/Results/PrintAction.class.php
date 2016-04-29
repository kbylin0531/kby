<?php
/**
 * Created by PhpStorm.
 * User: IBM
 * Date: 2015/11/4
 * Time: 13:49
 */

/**
 * Class PrintAction 打印控制器
 */
class PrintAction extends RightAction {

    /**
     * 重修
     */
    public function pageResit(){
        $this->xiala('schools','schools');
        $this->display('pageResit');
    }
    public function listResit(){

    }

    public function pageGeneralPrint($grade='%',$school='%',$classno='%',$studentno='%'){
        if(null !== REQTAG){
            $studentModel = new StudentModel();
            switch(REQTAG){
                case 'getlist':
                    $list = $studentModel->listStudents($grade,$school,$classno,$studentno,$this->_pageDataIndex,$this->_pageSize);
                    if(is_string($list)){
                        $this->failedWithReport("查询失败:{$list}");
                    }else{
                        $this->ajaxReturn($list);
                    }
                    break;
                case 'export':
                    $printModel = new PrintModel();
                    $list = $printModel->listStudentsCourses($grade,$school,$classno,$studentno);
                    if(is_string($list)){
                        $this->failedWithReport("查询失败:{$list}");
                    }
                    $listr = $printModel->listStudentsReward($grade,$school,$classno,$studentno);
                    if(is_string($listr)){
                        $this->failedWithReport("查询失败:{$listr}");
                    }

                    $this->generalExport($list,$listr);
                    exit();
                    break;
            }
        }

        $this->tableAssign(array(
            'grade' => '年级',
            'schoolname'    => '学部',
            'classname'     => '班级',
            'studentno'     => '学号',
            'studentname'   => '学生姓名',
        ),array('grade','school','classno'),null,null,true);
        $this->display('Common@Index/table');
    }

    private function generalExport($listdata,$listr){
        //获取格式化的数据
        $listdata = $this->formatExport($listdata,$listr);

        //打印学生列表数据
//        varsdumpout($listdata);

        $excel =  new ExcelExtensionModel();
        $phpExcelObject = $excel->getPhpExcelObject();

        //参数设置
        $firststudent = reset($listdata);//获取第一个学生的数据（标本）
//        varsdumpout($firststudent);
        $title = '宁波市鄞州职业教育中心学校学生综合成绩单';
        $excel->setGlobalStyle(array('font' => array('bold' => false, 'color' => array('argb' => '00000000'), 'size' => 8),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER)));//样式设置


        $metas = $listdata['metas'];
        $generalheaders = $listdata['generalheaders'];
        unset($listdata['metas'],$listdata['generalheaders']);

        $content_width = count($generalheaders['yearterm_list'])*$generalheaders['yearterm_width']+1; // 水平占据的宽度
        $aheadwidth = ceil($content_width/2);
        $ahead_end_column = $excel->getColumnNameByNum($aheadwidth);
        $back_start_width  = $aheadwidth+1;
        $back_start_column = $excel->getColumnNameByNum($back_start_width);
        $back_end_column = $excel->getColumnNameByNum($content_width);


//        varsdumpout($generalheaders,$content_width,$ahead_end_column,$back_start_column,$back_end_column);
        //设置标题部分
        $excel->setBigTitle($title,$content_width);


        $activeSheet = $phpExcelObject->getActiveSheet();
        $activeSheetIndex = $phpExcelObject->setActiveSheetIndex();
        $current_line_num = 1;

        foreach($listdata as $key => &$curstudent){
            $reward_list = $curstudent['reward_list'];
            unset($curstudent['reward_list']);

            //设置班级信息列
            ++ $current_line_num;
            $activeSheet->mergeCells("A{$current_line_num}:{$ahead_end_column}{$current_line_num}");
            $activeSheetIndex->setCellValue("A{$current_line_num}", "班级编号:{$curstudent['classno']}");
            $activeSheet->mergeCells("{$back_start_column}{$current_line_num}:{$back_end_column}{$current_line_num}");
            $activeSheetIndex->setCellValue("{$back_start_column}{$current_line_num}", "班级名称:{$curstudent['classname']}");
            //设置学生信息列
            ++ $current_line_num;
            $activeSheet->mergeCells("A{$current_line_num}:{$ahead_end_column}{$current_line_num}");
            $activeSheetIndex->setCellValue("A{$current_line_num}", "专业名称:{$curstudent['majorname']}");
            $activeSheet->mergeCells("{$back_start_column}{$current_line_num}:{$back_end_column}{$current_line_num}");
            $activeSheetIndex->setCellValue("{$back_start_column}{$current_line_num}", "学号:{$curstudent['studentno']} 姓名:{$curstudent['studentname']}");

            //设置学期标题列
            ++ $current_line_num;
            $activeSheetIndex->setCellValue("A{$current_line_num}", '');//第一行空格
            $start = 1;
            $end = $start + $generalheaders['yearterm_width'];
            foreach($generalheaders['yearterm_list'] as $item){
                $startcolumn = $excel->getColumnNameByNum($start);
                $endcolumn = $excel->getColumnNameByNum($end);
                $activeSheet->mergeCells("{$startcolumn}{$current_line_num}:{$endcolumn}{$current_line_num}");
                $activeSheetIndex->setCellValue("{$startcolumn}{$current_line_num}", $item);
            }

            //设置课程标题部分
             ++ $current_line_num;
            $activeSheetIndex->setCellValue("A{$current_line_num}",'');
            $thiscolumn = 1;
            for($i=0;$i<$generalheaders['repeatimes'];++$i){
                foreach($generalheaders['coursedetailheaders'] as $item){
                    $activeSheetIndex->setCellValue($excel->getColumnNameByNum($thiscolumn++).$current_line_num,$item);
                }
            }

            //设置课程列
            foreach($curstudent['course_list'] as $coursegroup => $course){
                ++ $current_line_num;
                $activeSheetIndex->setCellValue("A{$current_line_num}", $course['coursename']);
                $index = 1;
                foreach($metas as $_yearterm){
                    if($_yearterm === $course['course_detail']['_yearterm']){
                        $activeSheetIndex->setCellValue($excel->getColumnNameByNum($index++).$current_line_num,$course['course_detail']['general_score']);
                        $activeSheetIndex->setCellValue($excel->getColumnNameByNum($index++).$current_line_num,$course['course_detail']['resit_score']);
                        $activeSheetIndex->setCellValue($excel->getColumnNameByNum($index++).$current_line_num,$course['course_detail']['credit']);
                        $activeSheetIndex->setCellValue($excel->getColumnNameByNum($index++).$current_line_num,$course['course_detail']['point']);
                        $activeSheetIndex->setCellValue($excel->getColumnNameByNum($index++).$current_line_num,$course['course_detail']['coursetype']);
                    }else{
                        for($i=0;$i<$generalheaders['yearterm_width'];$i++){
//                            varsdumpout($excel->getColumnNameByNum($index).$current_line_num);
                            $activeSheetIndex->setCellValue($excel->getColumnNameByNum($index).$current_line_num,'');
                            ++$index;
                        }
                    }
                }
            }

            //设置学分统计列表
            ++ $current_line_num;
            $this->setCount('必修课小计:','course_must_sum',$activeSheetIndex,$current_line_num,$curstudent,$listdata,$activeSheet,$excel);
            ++ $current_line_num;
            $this->setCount('毕业实习小计:','course_graduation_sum',$activeSheetIndex,$current_line_num,$curstudent,$listdata,$activeSheet,$excel);
            ++ $current_line_num;
            $this->setCount('任选课小计:','course_choose_any',$activeSheetIndex,$current_line_num,$curstudent,$listdata,$activeSheet,$excel);
            ++ $current_line_num;
            $this->setCount('限选课小计:','course_shoose_limit',$activeSheetIndex,$current_line_num,$curstudent,$listdata,$activeSheet,$excel);

            ++ $current_line_num;
            $activeSheetIndex->setCellValue('A'.$current_line_num,'总学分:');
            $activeSheetIndex->setCellValue('B'.$current_line_num,$curstudent['credit_sum']);

            //设置奖励学分
            ++ $current_line_num;
            ++ $current_line_num;
            $activeSheetIndex->setCellValue('A'.$current_line_num,'所获学年学期');
            $activeSheetIndex->setCellValue('B'.$current_line_num,'学分名称');
            $activeSheetIndex->setCellValue('C'.$current_line_num,'学分');
            $activeSheetIndex->setCellValue('D'.$current_line_num,'备注');

//            varsdumpout($reward_list);
            foreach($reward_list as $rewarditem){
                ++ $current_line_num;
                $activeSheetIndex->setCellValue('A'.$current_line_num,"{$rewarditem['year']}学年{$rewarditem['term']}学期");
                $activeSheetIndex->setCellValue('B'.$current_line_num,$rewarditem['name']);
                $activeSheetIndex->setCellValue('C'.$current_line_num,$rewarditem['credit']);
                $activeSheetIndex->setCellValue('D'.$current_line_num,$rewarditem['rem']);
            }

            ++ $current_line_num;//换下一行
        }

        $excel->output("{$title}-{$firststudent['classname']}");
    }

    /**
     * @param $title
     * @param $index
     * @param PHPExcel_Worksheet $activeSheetIndex
     * @param $current_line_num
     * @param $curstudent
     * @param $listdata
     * @param PHPExcel_Worksheet $activeSheet
     * @param ExcelExtensionModel $excel
     */
    private function setCount($title,$index,$activeSheetIndex,&$current_line_num,&$curstudent,&$listdata,&$activeSheet,&$excel){
        $activeSheetIndex->setCellValue('A'.$current_line_num,$title);
        foreach($curstudent[$index] as $_yearterm2 => $creditsum){
            $index = 1;
            foreach($listdata['metas'] as $_yearterm){
                if(intval($_yearterm) === $_yearterm2){
                    $activeSheet->mergeCells($excel->getColumnNameByNum($index)."{$current_line_num}:".
                        $excel->getColumnNameByNum($index+$curstudent['_yeartermheader_width']).$current_line_num);
                    $activeSheetIndex->setCellValue($excel->getColumnNameByNum($index).$current_line_num,$creditsum);
                }else{
                    for($i=0;$i<$curstudent['_yeartermheader_width'];$i++){
                        $activeSheetIndex->setCellValue($excel->getColumnNameByNum($index++).$current_line_num,'');
                    }
                }
                $index += $curstudent['_yeartermheader_width'];
            }
        }
    }

    private function formatExport($listdata,$listr){
        $formated = array();
        $temp = array();
        $generalheaders = array(
            'yearterm_list'  => array(),
            'yearterm_width' => 4,
            'repeatimes'    => 0,
            'coursedetailheaders'  => array(
                '总评成绩','补考成绩','学分','绩点','课程类型'
            ),
        );

        foreach($listdata as $item){

            //检查是否设置了这个学生
            if(!isset($formated[$item['studentno']])){
                $formated[$item['studentno']] = array(
                    //共享无计算属性
                    'classno'       => $item['classno'],
                    'classname'     => $item['classname'],
                    'majorname'   => $item['majorname'],
                    'studentno'   => $item['studentno'],
                    'studentname'   => $item['studentname'],
                    //课程标题部分信息
                    'course_list'    => array(),
                    //共享累计属性(按学期叠加)
                    'course_must_sum'    => array(), // 必修课小计
                    'course_graduation_sum' => array(), // 毕业实习小计
                    'course_choose_any' => array(), // 任选课小计
                    'course_shoose_limit'   => array(), // 限选课小计
                    'course_totol_sum'  => array(),//学期学分小计
                    //所有课程学分累计（无学期区分）
                    'credit_sum'    => 0,

                    //奖励学分列表
                    'reward_list'   => array(),
                );
            }

            //初始化变量
            $thisstudent = &$formated[$item['studentno']]; // 引用这个学生的全部信息
            $yeartermkey = "{$item['year']}{$item['term']}"; // 得到20141 20142 这样的值，可以对学年续期进行排序
            $coursegroup = $item['courseno'].$item['group'];
            $year = intval($item['year']);
            $term = intval($item['term']);

            //通用标题部分设置
            if(!isset($generalheaders['yearterm_list'][$yeartermkey])){
                switch($term){
                    case 1:
                        $nm = '一';
                        break;
                    case 2:
                        $nm = '二';
                        break;
                    default:
                        $nm = $term;
                }
                $generalheaders['yearterm_list'][$yeartermkey] = "{$year}学年第{$nm}学期";
                ++ $generalheaders['repeatimes'];
            }

            //叠加计算操作
            if(!isset($thisstudent['course_must_sum'][$yeartermkey])){
                //一个未设置，说明这学期的其他累计全部未设置
                $thisstudent['course_must_sum'][$yeartermkey] = 0;
                $thisstudent['course_graduation_sum'][$yeartermkey] = 0;
                $thisstudent['course_choose_any'][$yeartermkey] = 0;
                $thisstudent['course_shoose_limit'][$yeartermkey] = 0;
                $thisstudent['course_totol_sum'][$yeartermkey] = 0;
            }
            if(strcasecmp(CommonAction::GRADUATION_COURSENO,$item['courseno']) === 0){
                //毕业实习课程
                $thisstudent['course_graduation_sum'][$yeartermkey] += $item['credit'];
            }else{
                if(strcasecmp('M',$item['approach'])){//必修课
                    $thisstudent['course_must_sum'][$yeartermkey] += $item['credit'];
                }else{//选修课
                    if(intval($item['limitgroupno']) > 0){//带限选组号
                        $thisstudent['course_shoose_limit'][$yeartermkey] += $item['credit'];
                    }else{
                        $thisstudent['course_choose_any'][$yeartermkey] += $item['credit'];
                    }
                }
            }
            $thisstudent['course_totol_sum'][$yeartermkey] += $item['credit'];//总学分按学期叠加
            $thisstudent['credit_sum'] += $item['credit'];//总学分按照学年叠加

            //课程列表(每一门课程对应一行)
            $minyear = null;
            $thisstudent['course_list'][$coursegroup] = array(
                'coursename'    => $item['coursename'],
                'course_detail' => array(),
            );

            $thisstudent['course_list'][$coursegroup]['course_detail'] = array(
                '_yearterm'     => $yeartermkey,
                'general_score' => $item['general_score'],
                'resit_score' => $item['resit_score'],
                'credit' => $item['credit'],
                'point' => $item['point'],
                'coursetype' => $item['coursetypename'],
            );
            //yearterm order
            isset($temp[$yeartermkey]) or $temp[$yeartermkey] = $yeartermkey;
        }

        //整理奖励学分
        foreach($listr as $student){
            isset($formated[$student['studentno']]['reward_list']) and $formated[$student['studentno']]['reward_list'] = array();
            $formated[$student['studentno']]['reward_list'][] = $student;
        }

        $formated['metas'] = array_values($temp);
        $formated['generalheaders'] = $generalheaders;
        sort($formated['metas']);
        return $formated;
    }


}