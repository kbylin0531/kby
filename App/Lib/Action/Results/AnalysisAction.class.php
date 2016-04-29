<?php
/**
 * User: Administrator
 * Date: 2015/10/26
 * Time: 15:05
 */


/**
 * Class AnalysisAction 成绩分析
 */
class AnalysisAction extends RightAction {

    /**
     * @var ResultModel
     */
    protected $model = null;

    /**
     * @var array
     */
    private static $map = array(
        'A' => 5,        '优秀' => 5,         '5' => 5,
        'B' => 4,        '良好' => 4,         '4' => 4,
        'C' => 3,        '中等' => 3,         '3' => 3,
        'D' => 2,        '及格' => 2,         '2' => 2,
        'E' => 1,        '不及格' => 1,        '1'  => 1,
        '0' => 0,'100'  => 100,
        '缓考'  => 0,     '缺考'  => 0,        '违纪'  => 0,
    );

    private static $backMap = array(
        5   => '优秀',
        4   => '良好',
        3   => '中等',
        2   => '及格',
        1   => '--',
    );

    /**
     * 分数转换成成绩
     * @param $score
     * @return int
     */
    private function _score2Numeric($score){
        $score = strtoupper($score);
        if('' === $score){
            return 1;
        }else{
            return self::$map[$score];
        }
    }

    /**
     * 成绩记述改为文字显示
     * @param $num
     * @return mixed
     */
    private function _numeric2Score($num){
        $num = intval($num);
        return self::$backMap[$num];
    }

    public function __construct(){
        parent::__construct();
        $this->model = new ResultsAnalysisModel();
    }


    /**
     * 成绩分析（必修课） 页面显示
     */
    public function pageAnaliseClassScores(){
        $this->xiala('schools','schools');
        $this->display('pageAnaliseClassScores');
    }
    /**
     * 成绩分析（选修课） 页面显示
     */
    public function pageAnaliseClassScoresForSpecial(){
        $this->xiala('schools','schools');
        $this->display('pageAnaliseClassScoresForSpecial');
    }
    /**
     * 返回班级成绩分析结果
     * @param $year
     * @param $term
     * @param $classno
     * @param $schoolno
     * @param $coursegroup
     * @param int $isMusable
     * @param int $scoretype 成绩类型，默认为总评
     */
    public function listAnaliseClassScores($year,$term,$classno,$schoolno,$coursegroup,$isMusable=1,$scoretype=3){
        $rst = $this->getAnalisesClassCourseList($year,$term,$classno,$coursegroup,$schoolno,$isMusable,$scoretype);
        if(is_string($rst)){
            $this->failedWithReport($rst);
        }
        $this->ajaxReturn(array(
            'total' => count($rst),
            'rows'  => array_values($rst),//全部使用数字索引
        ),'JSON');
    }



    /**
     * 学生必修课不及格列表 页面显示
     */
    public function pagePasslessStudent($year=null,$term=null,$scoretype=null,$school='%',$studentno='%',$classno='%',$courseno='%',$coursename='%'){
        if(REQTAG === 'export'){
            ini_set("max_execution_time", "1800");
            $rst = $this->model->getPasslessStudentCourseDetailTableList($year,$term,$scoretype,$school,$studentno,$classno,$courseno,$coursename);
            if(is_string($rst)){
                $this->exitWithUtf8("获取数据失败！{$rst}");
            }

            $data = array();
            $this->model->initPHPExcel();
            $data['title'] = '学生必修课不及格列表';
            //表头
            $data['head'] = array(
                //默认值如 align type 的设计实例
                'year' => array( '学年', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                'term' => array('学期','align' => CommonModel::ALI_LEFT,'width'=>10),
                'coursegroup' => array('课号','align' => CommonModel::ALI_LEFT,'width'=>10),
                'coursename' => array( '课程名称', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                'studentno' => array( '学号', 'align' => CommonModel::ALI_CENTER,'width'=>10),
                'studentname' => array( '姓名', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                'classname' => array( '班级', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                'schoolname' => array( '学部', 'align' => CommonModel::ALI_CENTER,'width'=>10),
                'approach' => array( '选课性质', 'align' => CommonModel::ALI_LEFT,'width'=>10),

                'normal_score' => array('平时','align' => CommonModel::ALI_LEFT,'width'=>10),
                'midterm_score' => array( '期中', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                'finals_score' => array( '期末', 'align' => CommonModel::ALI_CENTER,'width'=>10),
                'general_score' => array( '总评', 'align' => CommonModel::ALI_LEFT,'width'=>10),
                'resit_score' => array( '补考', 'align' => CommonModel::ALI_CENTER,'width'=>10),
                'retake_score' => array( '重修', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            );
            //表体
            $data['body'] = $rst['rows'];
            $this->model->fullyExportExcelFile($data, $data['title']);
        }
        $this->xiala('schools','schools');
        $this->display('pagePasslessStudent');
    }
    /**
     * 获取必修课不及格学生列表
     * @param $year
     * @param $term
     * @param $scoretype
     * @param string $school
     * @param string $studentno
     * @param string $classno
     * @param string $courseno
     * @param string $coursename
     */
    public function listPasslessStudentCourseDetail($year,$term,$grade='%',$scoretype,$school='%',$studentno='%',$classno='%',$courseno='%',$coursename='%' ){
        $rst = $this->model->getPasslessStudentCourseDetailTableList($grade,$year,$term,$scoretype,$school,$studentno,$classno,$courseno,$coursename,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($rst)){
            $this->failedWithReport("查询失败！{$rst}");
        }
        $this->ajaxReturn($rst,'JSON');
    }

    public function pageClassesPasslessStatus($year=null,$term=null,$coursegroup=null,$classname=null,$coursename=null){
        if(REQTAG === 'getlist'){
            //获取列表数据
            $list = $this->model->listClassesPasslessStatus($year,$term,$coursegroup,$classname,$coursename,$this->_pageDataIndex,$this->_pageSize);
//            if(is_string($list)){
//                $this->errorBack('查询出错：'.$list);
//            }
            $this->ajaxReturn($list);
        }elseif(REQTAG === 'export'){
            //导出冽表数据
            $rst = $this->model->listClassesPasslessStatus($year,$term,$coursegroup,$classname,$coursename);
            if(is_string($rst)){
                $this->exitWithUtf8("获取数据失败！{$rst}");
            }

            $data = array();
            $this->model->initPHPExcel();
            $data['title'] = "第{$year}学年第{$term}学期班级不及格情况汇总";
            //表头
            $data['head'] = array(
                //默认值如 align type 的设计实例
                'coursegroup' => array( '课号', 'align' => CommonModel::ALI_LEFT,'width'=>20),
                'classname' => array('班级','align' => CommonModel::ALI_LEFT,'width'=>30),
                'coursename' => array('课程名称','align' => CommonModel::ALI_LEFT,'width'=>30),
                'c' => array( '人数', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            );
            //表体
            $data['body'] = $rst['rows'];
            $this->model->fullyExportExcelFile($data, $data['title']);
        }
        $this->display('pageClassesPasslessStatus');
    }


    /**
     * 班级成绩分析结果 Excel导出
     * @param $year
     * @param $term
     * @param string $classno
     * @param string $coursegroup
     * @param string $schoolno
     * @param int $isMusable
     * @param int $scoretype
     */
    public function exportAnaliseClassScores($year,$term,$classno='%',$coursegroup='%',$schoolno='%',$isMusable=1,$scoretype=3){
        $rst = $this->getAnalisesClassCourseList($year,$term,$classno,$coursegroup,$schoolno,$isMusable,$scoretype);
        if(is_string($rst)){
            $this->exitWithUtf8("获取数据失败！{$rst}");
        }

        $data = array();
        $this->model->initPHPExcel();
        $data['title'] = '班级成绩分析';
        //表头
        $data['head'] = array(
            //默认值如 align type 的设计实例
            'coursegroup' => array( '课号', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'coursename' => array('课名','align' => CommonModel::ALI_LEFT,'width'=>10),
            'classname' => array('班级','align' => CommonModel::ALI_LEFT,'width'=>10),
            'teachername' => array( '教师', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'attendents' => array( '选课人数', 'align' => CommonModel::ALI_CENTER,'width'=>10),
            'classsum' => array( '班级人数', 'align' => CommonModel::ALI_LEFT,'width'=>10),

            'class_scores_sum' => array('班级总分','align' => CommonModel::ALI_LEFT,'width'=>10),
            'class_scores_avg' => array( '平均分', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'class_scores_top' => array( '最高分', 'align' => CommonModel::ALI_CENTER,'width'=>10),
            'class_scores_buttom' => array( '最低分', 'align' => CommonModel::ALI_LEFT,'width'=>10),

            'AC' => array( '优秀人数', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'BC' => array('良好人数','align' => CommonModel::ALI_LEFT,'width'=>10),
            'CC' => array( '中等人数', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'DC' => array( '及格人数', 'align' => CommonModel::ALI_CENTER,'width'=>10),
            'EC' => array( '不及格人数', 'align' => CommonModel::ALI_LEFT,'width'=>10),

            'AP' => array( '优秀比例', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'BP' => array('良好比例','align' => CommonModel::ALI_LEFT,'width'=>10),
            'CP' => array( '中等比例', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'DP' => array( '及格比例', 'align' => CommonModel::ALI_CENTER,'width'=>10),
            'EP' => array( '不及格比例', 'align' => CommonModel::ALI_LEFT,'width'=>10),

            'ABC' => array( '缺考人数', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'ABP' => array( '缺考比例', 'align' => CommonModel::ALI_CENTER,'width'=>10),
            'PC' => array( '通过人数', 'align' => CommonModel::ALI_LEFT,'width'=>10),
            'PP' => array( '通过比例', 'align' => CommonModel::ALI_LEFT,'width'=>10),
        );
        //表体
        $data['body'] = array_values($rst);
        $this->model->fullyExportExcelFile($data, $data['title']);
    }








    /**
     * 获取班级课程成绩分析结果
     * 同于ajax返回或者打印
     * @param int $year 学年
     * @param int $term 学期
     * @param string $classno 班级号
     * @param string $coursegroup 课号组号
     * @param string $schoolno 学院号
     * @param int $isMusable 是否是选修课，默认否即必修课
     * @param int $scoretype 成绩类型
     * @return array|string 返回前段datagrid可以识别的json编码前的数组，或者返回出错信息
     */
    protected function getAnalisesClassCourseList($year,$term,$classno='%',$coursegroup='%',$schoolno='%',$isMusable=1,$scoretype=3) {
        $data = $this->model->getClassStudentsForAnalyse($year,$term,$classno,$coursegroup,$schoolno,$isMusable,$scoretype);
        if(is_string($data)){
            return "查询数据失败！{$data}";
        }
        $countArray = array();
        //遍历整合结果(SQL操作复杂，可维护性低,故挪到PHP程序中) ==> 按照课号组号归类
        foreach($data as $rec) {
            $coursegroup = $rec['coursegroup'];

            //选修课以课程分组  必修课以课号组号和班级分组
            $identifier = $isMusable?$coursegroup:$coursegroup.$rec['classno'];

            if(!isset($countArray[$identifier])) {
                //新建一个课程的统计记录
                $countArray[$identifier] = array(
                    //无需计算的信息
                    'coursegroup'   => $coursegroup,
                    'classname'     => $rec['remark']? "{$rec['classname']}({$rec['remark']})" : $rec['classname'],
                    'coursename'    => $rec['coursename'],
                    'attendents'    => $rec['attendents'],//比例时候用上
                    'realattendents'=> $rec['attendents'],//实际参加考试的学生数目，排除缺考缓考的学生
                    'teachername'   => $rec['teachername'],//任课老师
                    'classsum'      => $rec['classsum'],//班级人数
                    //成绩分级数量
                    'AC'            => 0, //Count of score 'A'
                    'BC'            => 0,
                    'CC'            => 0,
                    'DC'            => 0,
                    'EC'            => 0,
                    //成绩分级百分比
                    'AP'            => 0,//Percent of score 'A'
                    'BP'            => 0,
                    'CP'            => 0,
                    'DP'            => 0,
                    'EP'            => 0,
                    //缺考数量和缺考比例
                    'ABC'            => 0,//Count of ABsent
                    'ABP'            => 0,
                    //通过比例
                    'PC'            => 0,//Passed Count
                    'PP'            => 0,//Passed Percent
                    //班级总分
                    'class_scores_sum'  => 0,
                    //班级平均分
                    'class_scores_avg'  => 0,
                    //最高分
                    'class_scores_top'  => 0,
                    //最低分
                    'class_scores_buttom'  => 100,
                );
            }
            $this->_modifyCountInfo($countArray[$identifier],$rec['exam_score'],$rec);
        }

        //结果的第二次整理： 整理最高分和最低分  计算比率
        foreach($countArray as &$course){
            //说明成绩没有输入
            if($course['class_scores_buttom'] === 100 and 0 === $course['class_scores_top']){
                $course['class_scores_buttom'] = 0;
            }

            //平均分
            $course['class_scores_avg'] = number_format(1.0 * $course['class_scores_sum'] / $course['realattendents'] ,2);//1.0 为得到浮点数

            //百分比计算
            $course['AP'] = number_format(100.0 * $course['AC']/$course['attendents'],2) .'%';
            $course['BP'] = number_format(100.0 * $course['BC']/$course['attendents'],2) .'%';
            $course['CP'] = number_format(100.0 * $course['CC']/$course['attendents'],2) .'%';
            $course['DP'] = number_format(100.0 * $course['DC']/$course['attendents'],2) .'%';
            $course['EP'] = number_format(100.0 * $course['EC']/$course['attendents'],2) .'%';
            $course['PP'] = number_format(100.0 * $course['PC']/$course['attendents'],2) .'%';
            $course['ABP'] = number_format(100.0 * $course['ABC']/$course['attendents'],2) .'%';
        }
        return $countArray;
    }

    /**
     * 用于遍历过程中修改统计信息
     * @param array $countArray
     * @param mixed $score
     * @param array $rec
     */
    private function _modifyCountInfo(array &$countArray,$score,$rec){
        if(trim($score) === '') $score = 0;


        //注意：计算平均分时 缓考缺考都将不能算作参加考试
        if($rec['exam_status'] === 'Q'){
            //缺考人数统计
            ++ $countArray['ABC'];
            -- $countArray['realattendents'];
        }else{

            if(is_numeric($score)){//百分制
                $score = floatval($score);

                //总分色湖之
                $countArray['class_scores_sum'] += $score;

                //设置最高分和最低分
                if($score > floatval($countArray['class_scores_top']))    $countArray['class_scores_top']    = $score;
                if($score < floatval($countArray['class_scores_buttom'])) $countArray['class_scores_buttom'] = $score;

                //分数级 人数统计
                if($score >= 90) ++ $countArray['AC'];
                elseif($score >= 80 ) ++ $countArray['BC'];
                elseif($score >= 70 ) ++ $countArray['CC'];
                elseif($score >= 60 ) ++ $countArray['DC'];
                else ++ $countArray['EC'];

                //通过人数统计
                if($score >= 60) ++ $countArray['PC'];
            }else{//五级制(使用汉字和字母兼容转换时的错误)
                $score = strtoupper(trim($score));

                //设置最高分和最低分
                $numval = $this->_score2Numeric($score);

//            var_dump(
//                $numval ,
//                ($countArray['class_scores_top']),
//                ($countArray['class_scores_buttom']),
//                $this->_score2Numeric(($countArray['class_scores_top'])),
//                $this->_score2Numeric(($countArray['class_scores_buttom'])),
//                $numval > $this->_score2Numeric(($countArray['class_scores_top'])),
//                $numval < $this->_score2Numeric(($countArray['class_scores_buttom'])),
//                $this->_numeric2Score($numval),
//                $score
//            );
                if($numval > $this->_score2Numeric($countArray['class_scores_top'])) $countArray['class_scores_top'] = $score;
                if($numval < $this->_score2Numeric($countArray['class_scores_buttom'])) $countArray['class_scores_buttom'] = $score;

                //分数级 人数统计
                if(    $score === 'A' or '优秀' === $score) ++ $countArray['AC'];
                elseif($score === 'B' or '良好' === $score) ++ $countArray['BC'];
                elseif($score === 'C' or '中等' === $score) ++ $countArray['CC'];
                elseif($score === 'D' or '及格' === $score) ++ $countArray['DC'];
                else ++ $countArray['EC'];

                //通过人数统计
                if($score !== 'E' and $score !== '不及格' and '' !== $score) ++ $countArray['PC'];
            }
        }
    }
}