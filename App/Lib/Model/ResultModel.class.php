<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/10/13
 * Time: 9:08
 */

/**
 * Class ResultModel 成绩管理模型
 */
class ResultModel extends CommonModel {

    /**
     * 考试状态汉字到英文的转换的映射关系
     * @var array
     */
    protected $map = array(
        'H'     => 'H',
        'Q'     => 'Q',
        'W'     => 'W',
        'Z'     => 'Z',
        '缓考'     => 'H',
        '缺考'     => 'Q',
        '违纪'     => 'W',
        '正常'     => 'Z',
        'NaN'     => '',
    );

    /**
     * 罗列用于成绩学分计算的数据
     * @param $year
     * @param $term
     * @param $classno
     * @param $studentno
     * @param $coursegroup
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listScoreCreditForCalculate($year,$term,$classno,$studentno,$coursegroup,$offset=null,$limit=null){
        $fields = '
cs.year,cs.term,
cs.courseno,cs.[group], -- 用于外层排序
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
cs.studentno,
RTRIM(STUDENTS.NAME) as studentname,
RTRIM(CLASSES.CLASSNAME) as classname,
normal_score,
midterm_score,
finals_score,
general_score,
credit,
point';
        $join = '
INNER JOIN COURSES on COURSES.COURSENO = cs.courseno
INNER JOIN STUDENTS on STUDENTS.STUDENTNO = cs.studentno
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO';
        $where = '
cs.[year] = :year
and cs.term = :term
and cs.courseno+cs.[group]  like :coursegroup
and RTRIM(STUDENTS.CLASSNO) like :classno
and cs.studentno like  :studentno';
        $order = 'cs.courseno,cs.[group],cs.studentno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup'  => $coursegroup,
            ':classno'      => $classno,
            ':studentno'    => $studentno,
        );
        $csql = $this->makeCountSql('cwebs_scores cs',array(
            'join'      => $join,
            'where'     => $where,
        ));
        $ssql = $this->makeSql('cwebs_scores cs',array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
            'order'     => $order,
        ),$offset,$limit);
        $list = $this->getTableList($csql,$ssql,$bind);
//        mist($list,$csql,$ssql,$bind);
        return $list;
    }

    /**
     * @param $year
     * @param $term
     * @return mixed
     */
    public function calculateAllScoreCredit($year,$term){

    }

    /**
     *
     * @param $year
     * @param $term
     * @return array|string
     */
    public function getCourseInfoByYearTerm($year,$term){
        $sql = 'SELECT DISTINCT courseno,[group],course_type_options as coursetype,COURSETYPE as approach,score_type as scoretype,
                CREDITS as credit from COURSEPLAN cp WHERE cp.YEAR = :year and cp.term = :term';
        return $this->doneQuery($this->sqlQuery($sql,array(
            ':year' => $year,
            ':term' => $term,
        )));
    }

    /**
     * 更新学生课程学分
     * @param $studentno
     * @param $year
     * @param $term
     * @param $coursegroup
     * @param $credit
     * @return int|string
     */
    public function calculateScoreCredit($studentno,$year,$term,$coursegroup,$credit){
        $sql = 'UPDATE cwebs_scores SET credit = :credit WHERE [year] = :year and term = :term and courseno+[group] = :coursegroup and studentno = :studentno';
        return $this->doneExecute($this->sqlExecute($sql,array(
            ':credit'   => $credit,
            ':year'     => $year,
            ':term'     => $term,
            ':coursegroup'  => $coursegroup,
            ':studentno'    => $studentno,
        )));
    }

    /**
     * 将前段传入的成绩类型数字转换成数据表字符串
     * 成绩类型：期中 期末 补考，总评（默认） 且无注入风险，无需绑定成绩类型
     * @param int $scoretype
     * @return string
     */
    protected function scoretypeTranslation($scoretype){
        switch(intval($scoretype)){
            case 1:return 'midterm_score';break;
            case 2:return 'finals_score';break;
            case 4:return 'resit_score';break;
            case 3:
            default:return 'general_score';
        }
    }
    /**
     * 将前段传入的成绩类型数字转换成数据表字符串
     * 成绩类型：期中 期末 补考，总评（默认） 且无注入风险，无需绑定成绩类型
     * @param int $scoretype
     * @return string
     */
    protected function scoreStatusTranslation($scoretype){
        switch(intval($scoretype)){
            case 1:return 'midterm_exam_status';break;
            case 2:return 'finals_exam_status';break;
            case 4:return 'resit_exam_status';break;
            case 3:
            default:return 'finals_exam_status';
        }
    }
    /**
     * 转换成绩类型的视图名称
     * 参数二说明：
     *  1 - 总评成绩不合格视图
     *  2 - 补考成绩不合格视图
     * @param int $scoretype 成绩类型表
     * @param string $shortnm 表名简称
     * @return string
     */
    protected function scoreViewtranslateAdv($scoretype,$shortnm='temp'){
        switch(intval($scoretype)){
            case 1 :
                return  'view_scores_midterm_fail '.$shortnm;break;
            case 2 :
                return  'view_scores_finals_fail '.$shortnm;break;
            case 4 :
                return  'view_scores_resit_fail '.$shortnm;break;
            case 3:
            default:
                return  'view_scores_general_fail '.$shortnm;
        }
    }
    /**
     * 转换成绩类型的视图名称
     * 参数二说明：
     *  1 - 总评成绩不合格视图
     *  2 - 补考成绩不合格视图
     *  3 - 期中成绩不合格视图
     *  4 - 期末成绩不合格视图
     * @param int $scoretype 成绩类型表
     * @param string $shortnm 表名简称
     * @return string
     */
    protected function scoreViewtranslate($scoretype,$shortnm='temp'){
        switch(intval($scoretype)){
            case 2 :
                return  'view_scores_resit_fail '.$shortnm;break;
            case 3 :
                return  'view_scores_midterm_fail '.$shortnm;break;
            case 4 :
                return  'view_scores_finals_fail '.$shortnm;break;
            case 1:
            default:
                return  'view_scores_general_fail '.$shortnm;
        }
    }

    /**
     * 获取班级不及格情况列表
     * @param $year
     * @param $term
     * @param $coursegroup
     * @param $classname
     * @param $coursename
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listClassesPasslessStatus($year,$term,$coursegroup,$classname,$coursename,$offset=null,$limit=null){
        $fields = '
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
RTRIM(CLASSES.CLASSNAME) as classname,
COUNT(DISTINCT cs.studentno) as c';
        $where = '
cs.[year] = :year and cs.term = :term and cs.courseno+cs.[group] like :coursegroup
and RTRIM(COURSES.COURSENAME) like :coursename and RTRIM(CLASSES.CLASSNAME) like :classname';
        $join = '
INNER JOIN COURSES ON cs.courseno = COURSES.COURSENO
INNER JOIN STUDENTS ON cs.studentno = STUDENTS.STUDENTNO
INNER JOIN CLASSES on CLASSES.CLASSNO = STUDENTS.CLASSNO';
        $group = 'cs.[year],cs.term,cs.courseno,cs.[group],COURSES.COURSENAME,CLASSES.CLASSNAME';
        $order = 'CLASSES.CLASSNAME,COURSES.COURSENAME';
        $csql = $this->makeCountSql('view_scores_finals_fail cs',array(
            'where' => $where,
            'join'  => $join,
            'group' => $group,
        ));
        $ssql = $this->makeSql('view_scores_finals_fail cs',array(
            'fields'=> $fields,
            'where' => $where,
            'join'  => $join,
            'group' => $group,
            'order' => $order,
        ),$offset,$limit);
        return $this->getTableList2($csql,$ssql,array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup'  => $coursegroup,
            ':coursename'   => $coursename,
            ':classname'    => $classname,
        ));
    }


    /**
     * 获取必修课不及格学生列表
     * @param string $grade 年级
     * @param $year
     * @param $term
     * @param $scoretype
     * @param $school
     * @param $studentno
     * @param $classno
     * @param $courseno
     * @param $coursename
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function getPasslessStudentCourseDetailTableList($grade,$year,$term,$scoretype,$school,$studentno,$classno,$courseno,$coursename,$offset=null,$limit=null){
        $vnm = $this->scoreViewtranslateAdv($scoretype,'cs');
        $fields = '
cs.[year],
cs.term,
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
RTRIM(STUDENTS.NAME) as studentname,
cs.studentno ,
SCHOOLS.SCHOOL as school,
RTRIM(STUDENTS.CLASSNO) as classno,
RTRIM(CLASSES.CLASSNAME) as classname,
RTRIM(SCHOOLS.NAME) as schoolname,
RTRIM(COURSEAPPROACHES.[VALUE]) as approach,
cs.normal_score,
cs.midterm_score,
cs.finals_score,
cs.general_score,
cs.resit_score,
cs.retake_score';
        $join = '
inner JOIN STUDENTS on STUDENTS.STUDENTNO = cs.studentno
INNER JOIN COURSEPLAN cp on cp.[YEAR] = cs.[year] and cp.TERM = cs.term and cp.COURSENO = cs.courseno and cp.[GROUP] = cs.[group] and cp.CLASSNO = STUDENTS.CLASSNO
INNER JOIN COURSES on cs.courseno = COURSES.COURSENO
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
INNER JOIN SCHOOLS on CLASSES.SCHOOL = SCHOOLS.SCHOOL
INNER JOIN COURSEAPPROACHES on COURSEAPPROACHES.NAME = cp.COURSETYPE';
        $where = "
LEFT(convert(varchar, STUDENTS.ENTERDATE, 112),4) like :grade -- 年级
and cs.studentno like :studentno
and SCHOOLS.SCHOOL like :school
and cs.[year] = :year
and cs.term = :term
and cs.courseno+cs.[group] like :courseno
and COURSES.COURSENAME like :coursename
and UPPER(CLASSES.CLASSNO) like :classno";
        $order = 'school,classno,coursegroup';
        $csql = $this->makeCountSql($vnm,array(
            'where' => $where,
            'join'  => $join,
        ));
        $ssql = $this->makeSql($vnm,array(
            'fields'=> $fields,
            'where' => $where,
            'join'  => $join,
            'order' => $order,
        ),$offset,$limit);
        $bind = array(
            ':grade'        => $grade,
            ':studentno'    => $studentno,
            ':school'       => $school,
            ':year'         => $year,
            ':term'         => $term,
            ':courseno'     => $courseno,
            ':coursename'   => $coursename,
            ':classno'      => $classno,
        );
        $rt = $this->getTableList($csql,$ssql,$bind);
//        mist($csql,$ssql,$bind,$rt);
        return $rt;
    }

    /**
     * 获取成绩分析需要的数据
     * @param $year
     * @param $term
     * @param string $classno
     * @param string $coursegroup
     * @param string $schoolno
     * @param bool|false $isMusable
     * @param int $scoretype
     * @return array|string
     */
    public function getClassStudentsForAnalyse($year,$term,$classno='%',$coursegroup='%',$schoolno='%',$isMusable=false,$scoretype=3,$offset=null,$limit=null){
        $approach = $isMusable?'M':'E';//必修 和 选修
        $statustype = $this->scoreStatusTranslation($scoretype);
        $scoretype = $this->scoretypeTranslation($scoretype);


        $updateclasssumsql = '
UPDATE a SET a.students=b.classsum from CLASSES a,(
SELECT DISTINCT
UPPER(CLASSNO) as classno,
COUNT(DISTINCT STUDENTNO) as classsum
FROM STUDENTS
where CLASSNO like :classno
  and STUDENTS.STATUS = \'A\'
GROUP BY CLASSNO) b WHERE a.CLASSNO = b.classno';

        $updatescheduleplanattendantssql = '
UPDATE a SET a.ATTENDENTS=b.attendents from SCHEDULEPLAN a,(
SELECT
	R32.[YEAR] as [year],
	R32.TERM as term,
	R32.COURSENO as courseno,
	R32.[GROUP] as [group],
	COUNT(R32.STUDENTNO) as attendents
	FROM R32
	INNER JOIN STUDENTS on STUDENTS.STUDENTNO = R32.STUDENTNO
	WHERE STUDENTS.CLASSNO LIKE :classno
	  and STUDENTS.STATUS = \'A\'
	GROUP BY R32.[YEAR],R32.TERM,R32.COURSENO,R32.[GROUP]
) b WHERE a.[YEAR] = b.[year] and a.TERM=b.term and a.COURSENO+a.[GROUP] = b.courseno+b.[group]';
        $updbind = array(':classno' => $classno);
        $rst = $this->doneExecute($this->sqlExecute($updateclasssumsql,$updbind));
        if(is_string($rst)){
            return "failed to update class sum of student! {$rst}";
        }
        $rst = $this->doneExecute($this->sqlExecute($updatescheduleplanattendantssql,$updbind));
        if(is_string($rst)){
            return "failed to update schedule attendants of student! {$rst}";
        }

//        $fields = "
//SELECT DISTINCT
//cs.courseno + cs.[group] as coursegroup,
//RTRIM(COURSES.COURSENAME) as coursename,
//st.STUDENTNO as studentno,
//st.CLASSNO as classno,
//RTRIM(CLASSES.CLASSNAME) as classname,
//CLASSES.remark,
//sp.ATTENDENTS as attendents,
//cs.{$scoretype}  as exam_score,
//cs.{$statustype} as exam_status,
//dbo.group_concat( RTRIM(TEACHERS.NAME),',') as teachername,
//CLASSES.STUDENTS as  classsum,
//CLASSES.SCHOOL";
//        $join = '
//INNER JOIN CLASSES on st.CLASSNO = CLASSES.CLASSNO
//INNER JOIN cwebs_scores cs on cs.studentno = st.STUDENTNO
//INNER JOIN COURSES on COURSES.COURSENO = cs.courseno
//INNER JOIN SCHEDULEPLAN sp on sp.[year] = cs.[year] and sp.TERM = cs.term and sp.COURSENO = cs.courseno and sp.[GROUP] = cs.[group]
//INNER JOIN COURSEPLAN csp on csp.COURSENO = cs.courseno and csp.[GROUP] = cs.[group] and csp.[YEAR] = cs.[year] and cs.term = csp.TERM and csp.CLASSNO = st.CLASSNO
//INNER JOIN TEACHERPLAN tp on tp.MAP = sp.RECNO
//INNER JOIN TEACHERS on tp.TEACHERNO = TEACHERS.TEACHERNO';
//        $where = "
//WHERE UPPER(st.CLASSNO) like :classnoC and cs.[year] = :year and cs.term = :term and cs.courseno+cs.[group] like :coursegroup
//and CLASSES.SCHOOL like :schoolno and csp.COURSETYPE = '{$approach}'";
//        $group = '
//cs.courseno , cs.[group],COURSES.COURSENAME,st.STUDENTNO ,st.CLASSNO ,CLASSES.CLASSNAME,
//sp.ATTENDENTS,
//cs.midterm_score,cs.midterm_exam_status ,
//CLASSES.STUDENTS,
//CLASSES.SCHOOL,CLASSES.remark';
//        $order = '';
        $sql = "
SELECT DISTINCT
cs.courseno + cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
st.STUDENTNO as studentno,
st.CLASSNO as classno,
RTRIM(CLASSES.CLASSNAME) as classname,
CLASSES.remark,
sp.ATTENDENTS as attendents,
cs.{$scoretype}  as exam_score,
cs.{$statustype} as exam_status,
dbo.group_concat( RTRIM(TEACHERS.NAME),',') as teachername,
CLASSES.STUDENTS as  classsum,
CLASSES.SCHOOL
from STUDENTS st
INNER JOIN CLASSES on st.CLASSNO = CLASSES.CLASSNO
INNER JOIN cwebs_scores cs on cs.studentno = st.STUDENTNO
INNER JOIN COURSES on COURSES.COURSENO = cs.courseno
INNER JOIN SCHEDULEPLAN sp on sp.[year] = cs.[year] and sp.TERM = cs.term and sp.COURSENO = cs.courseno and sp.[GROUP] = cs.[group]
INNER JOIN COURSEPLAN csp on csp.COURSENO = cs.courseno and csp.[GROUP] = cs.[group] and csp.[YEAR] = cs.[year] and cs.term = csp.TERM and csp.CLASSNO = st.CLASSNO
INNER JOIN TEACHERPLAN tp on tp.MAP = sp.RECNO
INNER JOIN TEACHERS on tp.TEACHERNO = TEACHERS.TEACHERNO
WHERE RTRIM(st.CLASSNO) like :classnoC and cs.[year] = :year and cs.term = :term and cs.courseno+cs.[group] like :coursegroup
	and CLASSES.SCHOOL like :schoolno and csp.COURSETYPE = '{$approach}' and st.STATUS = 'A'
GROUP BY cs.courseno , cs.[group],COURSES.COURSENAME,st.STUDENTNO ,st.CLASSNO ,CLASSES.CLASSNAME,
sp.ATTENDENTS,
cs.{$scoretype},cs.{$statustype} ,
CLASSES.STUDENTS,
CLASSES.SCHOOL,CLASSES.remark
ORDER BY cs.courseno+cs.[group]";
        $bind = array(
            ':classnoC'  => $classno,
            ':year'     => $year,
            ':term'     => $term,
            ':coursegroup'  => $coursegroup,
            ':schoolno'     => $schoolno,
        );
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind));
        if(is_string($rst)){
            return $rst;
        }
        return $rst;
    }

    /**
     * 获取期末考试课程信息
     * @param $year
     * @param $term
     * @param $coursegroupno
     * @return array|string
     */
    public function getCourseInfo($year,$term,$coursegroupno){
        $sql = "
SELECT DISTINCT
cwebs_scores.courseno+cwebs_scores.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
RTRIM(SCHOOLS.NAME) as schoolname,
ISNULL(convert(varchar(10),cwebs_scores.finals_exam_date,20),'') as examtime,
ISNULL(convert(varchar(10),cwebs_scores.resit_exam_date,20),'') as resitexamtime,
ISNULL(convert(varchar(10),cwebs_scores.finals_exam_date,20),'') as finals_exam_date,
ISNULL(convert(varchar(10),cwebs_scores.midterm_exam_date,20),'') as midterm_exam_date,
cwebs_scores.finals_exam_date
from cwebs_scores
INNER JOIN COURSES ON cwebs_scores.courseno = COURSES.COURSENO
LEFT OUTER JOIN SCHOOLS on COURSES.SCHOOL = SCHOOLS.SCHOOL
WHERE cwebs_scores.courseno+cwebs_scores.[group] =:coursegroupno
and cwebs_scores.[year] = :year and cwebs_scores.term  = :term
ORDER BY cwebs_scores.finals_exam_date DESC";
        $bind = array(
            ':coursegroupno'    => $coursegroupno,
            ':year' => $year,
            ':term' => $term,
        );
//        mist($this->sqlQuery($sql,$bind),$sql,$bind);
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }
    /**
     * 返回status状态
     * @param $score
     * @param bool $status
     * @return string
     */
    protected function translateScore($score,$status=false){
        if($status){
            if(array_key_exists($score,$this->map)){
                return $this->map[$score];
            }else{
                return 'Z';
            }
        }
        return $score;
    }

    /**
     * 解锁学生的期末考试成绩输入
     * @param $recno
     * @return int|string
     */
    public function unlockStudentFinalsByRecno($recno){
        return $this->lockStudentFinalsByRecno($recno,false);
    }

    /**
     * 锁定学生的期末考试成绩输入
     * @param $recno
     * @param bool $tolock
     * @return int|string
     */
    public function lockStudentFinalsByRecno($recno,$tolock=true){
        $tolock = $tolock?1:0;//值已经确定，无需banding
        $sql = "UPDATE cwebs_scores SET finals_lock = {$tolock} WHERE recno = :recno";
        $bind = array( ':recno' => $recno);
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 计算学分绩点
     * @param string $scores 分数
     * @return int
     */
    public function calculatePoint($scores){
        $point = 0;
        if(is_numeric($scores)){
            $scores = intval($scores);
            if($scores >= 60 ){
                $point = $scores/10 - 5; // 1 + ($scores - 60)/10;
            }
        }else{
            switch(trim($scores)){
                case '优秀':$point = 4;break;
                case '良好':$point = 3;break;
                case '中等':$point = 2;break;
                case '及格':$point = 1;break;
                default:$point = 0; // 不属于上面任何一种情况都属于不及格
            }
        }
        return $point;
    }




}