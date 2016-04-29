<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/10
 * Time: 9:39
 */

/**
 * Class ResultInputModel 成绩输入模型
 */
class ResultInputModel extends ResultModel {

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
     * 获取必修课不及格学生列表
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
    public function getPasslessStudentCourseDetailTableList($year,$term,$scoretype,$school,$studentno,$classno,$courseno,$coursename,$offset=null,$limit=null){
        $vnm = $this->scoreViewtranslateAdv($scoretype,'cs');
        $fields = '
cs.[year],
cs.term,
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
RTRIM(STUDENTS.NAME) as studentname,
cs.studentno ,
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
cs.studentno like :studentno
and SCHOOLS.SCHOOL like :school
and cs.[year] = :year
and cs.term = :term
and cs.courseno+cs.[group] like :courseno
and COURSES.COURSENAME like :coursename
and UPPER(CLASSES.CLASSNO) like :classno";
        $csql = $this->makeCountSql($vnm,array(
            'where' => $where,
            'join'  => $join,
        ));
        $ssql = $this->makeSql($vnm,array(
            'fields'    => $fields,
            'where' => $where,
            'join'  => $join,
        ),$offset,$limit);
        $bind = array(
            ':studentno'    => $studentno,
            ':school'       => $school,
            ':year'         => $year,
            ':term'         => $term,
            ':courseno'     => $courseno,
            ':coursename'   => $coursename,
            ':classno'      => $classno,
        );
        return $this->getTableList($csql,$ssql,$bind);
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
    public function getClassStudentsForAnalyse($year,$term,$classno='%',$coursegroup='%',$schoolno='%',$isMusable=false,$scoretype=3){
        $approach = $isMusable?'M':'E';//必修 和 选修
        $statustype = $this->scoreStatusTranslation($scoretype);
        $scoretype = $this->scoretypeTranslation($scoretype);
        $sql = "
SELECT DISTINCT
cs.courseno + cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
st.STUDENTNO as studentno,
st.CLASSNO as classno,
RTRIM(CLASSES.CLASSNAME) as classname,
CLASSES.remark,
cp.attendents as attendents,
cs.{$scoretype}  as exam_score,
cs.{$statustype} as exam_status,
dbo.group_concat( RTRIM(TEACHERS.NAME),',') as teachername,
csum.classsum,
CLASSES.SCHOOL
from STUDENTS st
INNER JOIN CLASSES on st.CLASSNO = CLASSES.CLASSNO
INNER JOIN cwebs_scores cs on cs.studentno = st.STUDENTNO
INNER JOIN COURSES on COURSES.COURSENO = cs.courseno
LEFT OUTER JOIN (
    -- 统计班级人数
	SELECT DISTINCT
	UPPER(CLASSNO) as classno,
	COUNT(STUDENTNO) as classsum
	FROM STUDENTS
	where CLASSNO like :classnoA
	GROUP BY CLASSNO
) csum on csum.classno = st.CLASSNO
INNER JOIN view_classcourse_attendants cp on cs.[year] = cp.[year] and cs.term = cp.term and cs.courseno = cp.courseno and cs.[group] = cp.[group] and cp.classno = st.CLASSNO
INNER JOIN SCHEDULEPLAN sp on sp.[year] = cs.[year] and sp.TERM = cs.term and sp.COURSENO = cs.courseno and sp.[GROUP] = cs.[group]
INNER JOIN COURSEPLAN csp on csp.COURSENO = cs.courseno and csp.[GROUP] = cs.[group] and csp.[YEAR] = cs.[year] and cs.term = csp.TERM and csp.CLASSNO = st.CLASSNO
INNER JOIN TEACHERPLAN tp on tp.MAP = sp.RECNO
INNER JOIN TEACHERS on tp.TEACHERNO = TEACHERS.TEACHERNO
WHERE UPPER(st.CLASSNO) like :classnoC and cs.[year] = :year and cs.term = :term and cs.courseno+cs.[group] like :coursegroup
	and CLASSES.SCHOOL like :schoolno and csp.COURSETYPE = '{$approach}'
GROUP BY cs.courseno , cs.[group],COURSES.COURSENAME,st.STUDENTNO ,st.CLASSNO ,CLASSES.CLASSNAME,cp.attendents,cs.{$scoretype},cs.{$statustype} ,csum.classsum,CLASSES.SCHOOL,CLASSES.remark
ORDER BY cs.courseno+cs.[group]";
        $bind = array(
            ':classnoA'  => doWithBindStr(strtoupper($classno)),
//            ':classnoB'  => doWithBindStr(strtoupper($classno)),
            ':classnoC'  => doWithBindStr(strtoupper($classno)),
            ':year'     => $year,
            ':term'     => $term,
            ':coursegroup'  => $coursegroup,
            ':schoolno'     => $schoolno,

        );
//        mist($sql,$bind,$scoretype,$scoretype,$statustype);
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind));
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
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
RTRIM(SCHOOLS.NAME) as schoolname,
ISNULL(convert(varchar(10),cs.finals_exam_date,20),'') as examtime,
ISNULL(convert(varchar(10),cs.resit_exam_date,20),'') as resitexamtime,
ISNULL(convert(varchar(10),cs.finals_exam_date,20),'') as finals_exam_date,
ISNULL(convert(varchar(10),cs.midterm_exam_date,20),'') as midterm_exam_date,
cs.finals_exam_date,
cp.CREDITS as credit
from cwebs_scores cs
INNER JOIN COURSES ON cs.courseno = COURSES.COURSENO
INNER JOIN STUDENTS on cs.studentno = STUDENTS.STUDENTNO
INNER JOIN COURSEPLAN cp ON cp.year = cs.year and cp.term= cs.term and cp.courseno+cp.[group] = cs.courseno+cs.[group]
	and STUDENTS.CLASSNO = cp.classno
LEFT OUTER JOIN SCHOOLS on COURSES.SCHOOL = SCHOOLS.SCHOOL
WHERE cs.courseno+cs.[group] =:coursegroupno
and cs.[year] = :year and cs.term  = :term
ORDER BY cs.finals_exam_date DESC";
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


//TODO:成绩输入初始化部分
    /**
     * 批量初始化课程成绩输入
     * @param int $year
     * @param int $term
     * @param string $coursegoupnolike 课号组号，可以带百分号之类的模糊搜索，搜索到的课号组号都将被初始化
     * @return array|string 返回的数组的count元素记录成功初始化的课程数目，message记录初始化失败的课程数目，
     *                      如果返回string类型的结果表示查询相似组号的时候发生了错误
     */
    public function initCourseScoresInputByCoursegroupLikelyInBatch($year,$term,$coursegoupnolike){
        $scheduleModel = new ScheduleModel();
        $rst = $scheduleModel->getScheduleCoursegroupnoLike($year,$term,$coursegoupnolike.'%');
        if(is_string($rst)){
            return "查询课号组号类似{$coursegoupnolike}的课程失败！{$rst}";
        }
        $count = 0;
        $message = '';
        foreach($rst as $value){
            $coursegroupno = $value['coursegroupno'];
            $rtn = $this->initCourseScoresInputByCoursegroup($year,$term,$coursegroupno);
            if(is_string($rtn) or !$rst){
                $message .= "初始化课号组号为{$coursegroupno}的课程失败！{$rtn} \n";
            }else{
                ++ $count;
            }
        }
        return array(
            'count' =>  $count,
            'message'   => $message,
        );
    }

    /**
     * 同步成绩表中的学生记录与选课表一致
     * @param $year
     * @param $term
     * @param $coursegroup
     * @return array|string 返回的数组的第一个原色代表删除的记录数目，第二个代表增加的数目
     */
    public function sync($year,$term,$coursegroup){
        $this->startTrans();
        $bind = array(
            ':year'         => $year,
            ':term'         => $term,
            ':coursegroup'  => $coursegroup,
        );
        $dsql = 'DELETE FROM cwebs_scores WHERE not EXISTS (
SELECT 1 from R32 WHERE R32.COURSENO = cwebs_scores.courseno and R32.[GROUP] = cwebs_scores.[group]
and R32.[YEAR] = cwebs_scores.[year] and R32.TERM = cwebs_scores.term and R32.STUDENTNO = cwebs_scores.studentno
) AND [year] = :year and term = :term and courseno+[group] like :coursegroup;';
        $rst1 = $this->doneExecute($this->sqlExecute($dsql,$bind));
        if(is_string($rst1)){
            $this->rollback();
            return $rst1;
        }
        $sql = 'INSERT INTO cwebs_scores (courseno,[group],studentno,[year],term)
SELECT
R32.COURSENO as courseno,
R32.[GROUP] as [group],
R32.STUDENTNO as studentno,
R32.[YEAR] as [year],
R32.TERM as term
from R32
INNER JOIN STUDENTS on R32.STUDENTNO = STUDENTS.STUDENTNO
where R32.[YEAR] = :year AND R32.TERM = :term and R32.COURSENO+R32.[GROUP] = :coursegroup and
not EXISTS (
    select 1 from cwebs_scores where
		cwebs_scores.[YEAR] = R32.[YEAR] AND cwebs_scores.TERM = R32.TERM and
		STUDENTNO = R32.STUDENTNO and courseno=R32.COURSENO and [GROUP] = R32.[GROUP]
);';
        $rst2 = $this->doneExecute($this->sqlExecute($sql,$bind));
        if(is_string($rst2)){
            $this->rollback();
            return $rst2;
        }
        $this->commit();
        return array($rst1,$rst2);
    }

    /**
     * 根据记录ID修改单个学生的成绩锁定情况
     * @param string $recno
     * @param int $type 成绩类型参照符
     * @param int $lock
     * @return int|string
     * @throws Exception
     */
    public function updateStudentLockStatusByRecno($recno,$type,$lock){
        switch($type){
            case 1:$type = 'midterm_lock';break;
            case 2:$type = 'finals_lock';break;
            case 3:$type = 'resit_lock';break;
            default:
                throw new Exception('错误的参数'.var_export($type,true));
        }
        $lock = $lock?1:0;
        $sql = "UPDATE cwebs_scores SET {$type} = 0 WHERE recno = :recno";
        $bind = array(
            ':recno'    => $recno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 批量初始化课程不及格学生列表
     * @param int $year
     * @param int $term
     * @param string $coursegoupnolike 课号组号，可以带百分号之类的模糊搜索，搜索到的课号组号都将被初始化
     * @return array|string 返回的数组的count元素记录成功初始化的课程数目，message记录初始化失败的课程数目，
     *                      如果返回string类型的结果表示查询相似组号的时候发生了错误
     */
    public function initCourseResitInputByCoursegroupLikelyInBatch($year,$term,$coursegoupnolike){
        $scheduleModel = new ScheduleModel();
        $rst = $scheduleModel->getScheduleCoursegroupnoLike($year,$term,$coursegoupnolike.'%');
        if(is_string($rst)){
            return "查询课号组号类似{$coursegoupnolike}的课程失败！{$rst}";
        }
        $count = 0;
        $message = '';
        foreach($rst as $value){
            $coursegroupno = $value['coursegroupno'];
            $rtn = $this->initCourseResitInputByCourseno($year,$term,$coursegroupno);
            if(is_string($rtn) or !$rst){
                $message .= "初始化课号组号为{$coursegroupno}的课程失败！{$rtn} \n";
            }else{
                ++ $count;
            }
        }
        return array(
            'count' =>  $count,
            'message'   => $message,
        );
    }
    /**
     * 初始化课程成绩输入
     * 从学生选课列表（R32 外联 VIEWSCHEDULE）中获取课号组号和学生学号信息(courseno,group,studentno,year,term) 插入到 cwebs_scores 表中
     * 针对的成绩类型有多种，考试状态参见cwebs_scores表的默认值
     * @param int $year
     * @param int $term
     * @param string $coursegoupno 课号组号，必须是明确的
     * @return int|string 返回插入的记录数目，插入过程发生错误返回string类型的异常信息，使用is_string 判断
     */
    public function initCourseScoresInputByCoursegroup($year,$term,$coursegoupno){
        $sql = "
INSERT INTO cwebs_scores (courseno,[group],studentno,[year],term)
SELECT
R32.COURSENO as courseno,
R32.[GROUP] as [group],
R32.STUDENTNO as studentno,
R32.[YEAR] as [year],
R32.TERM as term
from R32
INNER JOIN STUDENTS on R32.STUDENTNO = STUDENTS.STUDENTNO
where R32.COURSENO+R32.[GROUP]  = :pcg and R32.[YEAR] = :pyear AND R32.TERM = :pterm and
not EXISTS (
    select
        cwebs_scores.COURSENO,
        cwebs_scores.[GROUP],
        cwebs_scores.studentno,
        cwebs_scores.[YEAR],
        cwebs_scores.TERM
    from cwebs_scores where cwebs_scores.COURSENO+cwebs_scores.[GROUP] = :ncp
    and cwebs_scores.[YEAR] = :nyear AND cwebs_scores.TERM = :nterm
)";
        $bind = array(
            ':pcg'      => $coursegoupno,
            ':pyear'    => $year,
            ':pterm'    => $term,
            ':ncp'      => $coursegoupno,
            ':nyear'    => $year,
            ':nterm'    => $term,
        );
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
//        mist($sql,$bind);
        return $rst;
    }


    /**
     * 初始化课程补考成绩输入
     * 将总评成绩认定为不合格的视为成绩不合格并 将其中的 “学号、课号组号、学年、学期” 倒入到课程补考表中 cwebs_resit_students
     * 不及格的认定条件是：
     *  ① 考试状态不是正常的（缺考、缓考、作弊）
     *  ② 百分制分数小于60分
     *  ③ 五级值或者二级制成绩不属于“'优秀','良好','中等','及格'”范围，（成绩也可以是“缺考、缓考、作弊”之类的考试异常状态）
     * 注意：初始化的前提条件是学生成绩学生成绩表中已经存在这些学生的记录
     * @param int $year
     * @param int $term
     * @param string $coursegoup 课号组号，必须是明确的
     * @return int|string 返回插入的记录数目，插入过程发生错误返回string类型的异常信息，使用is_string 判断
     */
    public function initCourseResitInputByCourseno($year,$term,$coursegoup){
        //删除旧的数据
        $rst = $this->deleteCourseResitStudentsByCoursegroup($year,$term,$coursegoup);
        if(is_string($rst)) return "删除旧的记录的过程中出现错误！{$rst}";
        //完整的插入
        $sql = "
INSERT INTO cwebs_resit_students (studentno,coursegroup,[year],term)
SELECT DISTINCT
sgf.studentno,
sgf.courseno+sgf.[group] as courseno,
sgf.[year],
sgf.term
from view_scores_general_fail sgf WHERE  [YEAR] = :year and TERM = :term
AND sgf.COURSENO+sgf.[GROUP]  = :coursegroup
AND not EXISTS (
    SELECT studentno,coursegroup as courseno,[year],term
    from cwebs_resit_students
    WHERE [year] = :yearb and term = :termb AND coursegroup  = :coursegroupb)";
        $bind = array(
            ':year'    => $year,
            ':term'    => $term,
            ':coursegroup'  => $coursegoup,
            ':yearb'   => $year,
            ':termb'   => $term,
            ':coursegroupb' => $coursegoup,
        );
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
//        mist($rst,$sql,$bind);
        return $rst;
    }

//TODO:考试成绩输入
    /**
     * 获取某学年学期教师的课程列表
     * 用于成绩输入和补考成绩输入
     * @param int $year
     * @param int $term
     * @param string $teacherno 完整的教室号
     * @param null|int $offset 记录的偏移，当同$limit设置为非null值时，可以得到mysql中 "limit m,n" 一样的效果
     * @param null|int $limit  获取记录的最大数目
     * @return array|string  获取用于easyui加载的表格数据，当SQL执行出错时返回string类型的错误信息
     */
    public function listScheduleCoursesByTeacherno($year,$term,$teacherno,$offset=null,$limit=null){
        $fields = "
RTRIM(COURSENOGROUP) AS coursegroup,
RTRIM(COURSENAME) AS coursename,
cp.score_type,
vs.CREDITS AS credits,
WEEKHOURS AS weekhours,
WEEKEXPEHOURS AS weekexpehours,
RTRIM(vs.COURSETYPE) AS coursetype,
RTRIM(vs.EXAMTYPE) AS examtype,
RTRIM(SCHOOLNAME) AS schoolname,
dbo.group_concat(RTRIM(CLASSNONAME),',')  AS classnoname,
RTRIM(TEACHERNONAME) AS teachernoname,
RTRIM(vs.REM) AS rem,
RTRIM(DAYNTIME) AS dayntime";
        $join = 'INNER JOIN COURSEPLAN cp ON cp.[YEAR] = vs.[YEAR] and cp.TERM = vs.TERM AND cp.COURSENO+cp.[GROUP] = vs.COURSENOGROUP';
        $where = '
vs.[YEAR] = :year
AND vs.TERM = :term
and vs.TEACHERNO = :teacherno';
        $group = '
COURSENOGROUP,COURSENAME,vs.CREDITS,WEEKHOURS,WEEKEXPEHOURS,vs.COURSETYPE,
vs.EXAMTYPE,SCHOOLNAME,TEACHERNONAME,vs.REM,DAYNTIME,cp.score_type';
        $csql = $this->makeCountSql('VIEWSCHEDULE vs',array(
            'where' => $where,
            'join'  => $join,
            'group' => $group,
        ));
        $ssql = $this->makeSql('VIEWSCHEDULE vs',array(
            'fields'=> $fields,
            'join'  => $join,
            'where' => $where,
            'group' => $group,
        ),$offset,$limit);
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':teacherno'    => $teacherno,
        );
        $rst = $this->getTableList2($csql,$ssql,$bind);
//        mist($csql,$ssql,$bind);
        return $rst;
    }
    /**
     * 获取某学年学期课号为XXX的学生成绩输入列表
     * @param int $year
     * @param int $term
     * @param string $coursegroup 完整的课号组号
     * @param null|int $offset 记录的偏移，当同$limit设置为非null值时，可以得到mysql中 "limit m,n" 一样的效果
     * @param null|int $limit  获取记录的最大数目
     * @return array|string  获取用于easyui加载的表格数据，当SQL执行出错时返回string类型的错误信息
     */
    public function listStudentScoreByCourseno($year,$term,$coursegroup,$offset=null,$limit=null){
        $fields = '
cs.studentno,
RTRIM(STUDENTS.NAME) as studentname,
cs.normal_score,
cs.midterm_score,
cs.finals_score,
cs.general_score,
cs.resit_score,
cs.midterm_lock,
cs.finals_lock,
cs.resit_lock,
cwebs_exam_status.name as status, -- 期末考试状态
ces_midterm.name as midterm_status, -- 期中考试状态
cs.credit,
cs.recno';
        $join = '
INNER JOIN students on cs.studentno=students.studentno
INNER JOIN cwebs_exam_status on cs.finals_exam_status = cwebs_exam_status.code
INNER JOIN cwebs_exam_status ces_midterm on cs.midterm_exam_status = ces_midterm.code
';
        $where = 'cs.[year] = :year and cs.term = :term and cs.courseno+cs.[group] = :coursegroup';
        $order = 'cs.studentno';
        $csql = $this->makeCountSql('cwebs_scores cs',array(
            'where' => $where,
            'join'  => $join,
        ));
        $ssql = $this->makeSql('cwebs_scores cs',array(
            'distinct'  => true,
            'fields'=> $fields,
            'where' => $where,
            'join'  => $join,
            'order' => $order,
        ),$offset,$limit);
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup' => $coursegroup,
        );
//        mist($csql,$ssql,$bind);
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 修改期末考试时间
     * @param string $coursegroup 课号组号
     * @param int $year
     * @param int $term
     * @param string $date 考试日期
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateFinalsExamtime($coursegroup,$year,$term,$date){
        $sql = 'UPDATE  [cwebs_scores] SET [finals_exam_date]=:date WHERE courseno+[group] = :coursegroup and [year] = :year and term = :term;';
        $bind = array(
            ':date'         => $date,
            ':coursegroup'  => $coursegroup,
            ':year'         => $year,
            ':term'         => $term,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }
    /**
     * 修改期末成绩输入状态
     * @param int $year
     * @param int $term
     * @param string $coursegroup 精确的课号组号
     * @param int $lock 0为解锁操作(默认)，1为上锁
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateStudentMidtermLockStatusByCoursegroup($year,$term,$coursegroup,$lock=0) {
        $lock = $lock?1:0;
        $sql = "UPDATE cwebs_scores SET midterm_lock = {$lock} WHERE [year] = :year and term = :term and courseno+[group] = :coursegroup";
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup' => $coursegroup,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }
    /**
     * 修改期末成绩输入状态
     * @param int $year
     * @param int $term
     * @param string $coursegroup 精确的课号组号
     * @param int $lock 0为解锁操作(默认)，1为上锁
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateStudentFinalsLockStatusByCoursegroup($year,$term,$coursegroup,$lock=0) {
        $lock = $lock?1:0;
        $sql = "UPDATE cwebs_scores SET finals_lock = {$lock} WHERE [year] = :year and term = :term and courseno+[group] = :coursegroup";
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup' => $coursegroup,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }
    /**
     * 解锁学生的补考成绩输入
     * @param int $year
     * @param int $term
     * @param string $coursegroup 精确的课号组号
     * @param int $lock 0为解锁操作(默认)，1为上锁
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateStudentResitLockStatusByCoursegroup($year,$term,$coursegroup,$lock=0){
        $lock = $lock?1:0;
        $sql = "update cwebs_scores set resit_lock = {$lock}} where [year]=:year and term=:term and courseno+[group] = :courseno";
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':courseno' => $coursegroup,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }
    /**
     * 修改学生 “平时、期中、期末、总评” 成绩
     * @param int $recno 记录号
     * @param array $origin 提交的原始表单数据
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateStudentFinalsScoresByRecno($recno,$origin) {
        $origin = $origin['_origin'];
        return $this->updateRecords('cwebs_scores',array(
            //设置分数
            'normal_score'          => $this->translateScore($origin['normal_score']),
            'midterm_score'         => $this->translateScore($origin['midterm_score']),
            'finals_score'          => $this->translateScore($origin['finals_score']),
            'general_score'         => $this->translateScore($origin['general_score']),
            //设置状态
            'midterm_exam_status'   => $this->translateScore($origin['midterm_score'],true),
            'finals_exam_status'    => $this->translateScore($origin['finals_score'],true),
            'general_status'        => $this->translateScore($origin['general_score'],true),
            //积分点设置
            'point'                 => $this->calculatePoint($origin['general_score']),
            //其他色湖之
            'finals_input_date'     => date('Y-m-d'),
//            'finals_lock'           => 1,
        ),array(
            'recno' => $recno,
//            'finals_lock = :bind' => array(':bind',0),
        ));
    }

    /**
     * 修改学生补考成绩
     * @param string $resitscore 补考成绩分数
     * @param int $recno 记录号
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateStudentResitScoreByRecno($resitscore,$recno) {
        return $this->updateRecords('cwebs_scores',array(
            'resit_score'          => $this->translateScore($resitscore),
            'resit_exam_status'    => $this->translateScore($resitscore,true),
            'resit_input_date'     => date('Y-m-d'),
//            'resit_lock'           => 1,
        ),array(
            'recno' => $recno,
        ));
    }

    /**
     * 按照多个条件更新补考成绩
     * @param int $year
     * @param int $term
     * @param string $courseno
     * @param string $group
     * @param string $studentno
     * @param string|int $score
     * @return int|string
     */
    public function updateStudentsResitScoreByMultiCondition($year,$term,$courseno='%',$group='%',$studentno='%',$score=0){
        $condition = array(
            'year' => $year,
            'term' => $term,
        );
        $condition['courseno'] = array($courseno,true,'like');
        $condition['group'] = array($group,true,'like');
        $condition['studentno'] = array($studentno,true,'like');

        return $this->updateRecords('cwebs_scores',array(
            'resit_score'          => $this->translateScore($score),
            'resit_exam_status'    => $this->translateScore($score,true),
            'resit_input_date'     => date('Y-m-d'),
//            'resit_lock'           => 1,
        ),$condition);
    }

    //updateStudentsMidtermScoreByMultiCondition
    public function updateStudentsMidtermScoreByMultiCondition($year,$term,$courseno='%',$group='%',$studentno='%',$score=0){
        $condition = array(
            'year' => $year,
            'term' => $term,
        );
        $condition['courseno'] = array($courseno,true,'like');
        $condition['group'] = array($group,true,'like');
        $condition['studentno'] = array($studentno,true,'like');

        return $this->updateRecords('cwebs_scores',array(
            'midterm_score'          => $this->translateScore($score),
            'midterm_exam_status'    => $this->translateScore($score,true),
            'midterm_input_date'     => date('Y-m-d'),
//            'resit_lock'           => 1,
        ),$condition);
    }

    public function updateStudentsFinalsScoreByMultiCondition($year,$term,$courseno='%',$group='%',$studentno='%',$normal_score=0,$finals_score=0,$general_score=0){
        $condition = array(
            'year' => $year,
            'term' => $term,
        );
        $condition['courseno'] = array($courseno,true,'like');
        $condition['group'] = array($group,true,'like');
        $condition['studentno'] = array($studentno,true,'like');

        $datetime = date('Y-m-d');

        return $this->updateRecords('cwebs_scores',array(
            'normal_score'          => $this->translateScore($normal_score),

            'finals_score'          => $this->translateScore($finals_score),
            'finals_exam_status'    => $this->translateScore($finals_score,true),
            'finals_input_date'     => $datetime,

            'general_score'          => $this->translateScore($general_score),
            'general_status'        => $this->translateScore($general_score,true),
//            'resit_lock'           => 1,
        ),$condition);
    }

    /**
     * 修改学生期中成绩
     * @param string $resitscore 期中成绩
     * @param int $recno 记录号
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateStudentMidtermScoreByRecno($resitscore,$recno) {
        return $this->updateRecords('cwebs_scores',array(
            'midterm_score'          => $this->translateScore($resitscore),
            'midterm_exam_status'    => $this->translateScore($resitscore,true),
            'midterm_input_date'     => date('Y-m-d'),
//            'midterm_lock'           => 1,
        ),array(
            'recno' => $recno,
        ));
    }
    /**
     * 修改补考时间
     * @param string $coursegroup 课号组号
     * @param int $year
     * @param int $term
     * @param string $date 考试时间
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateResitExamtime($coursegroup,$year,$term,$date) {
        return $this->updateRecords('cwebs_scores',array(
            'resit_exam_date'   => $date,
        ),array(
            'courseno'  => substr($coursegroup,0,7),
            'group'     => array(substr($coursegroup,7),true),
            'year'      => $year,
            'term'      => $term,
        ));
    }
    /**
     * 修改期中考试时间
     * @param string $coursegroup 课号组号
     * @param int $year
     * @param int $term
     * @param string $date 考试时间
     * @return int|string 返回受影响的行数目，或者返回string类型的SQL执行错误信息
     */
    public function updateMidtermExamtime($coursegroup,$year,$term,$date) {
        return $this->updateRecords('cwebs_scores',array(
            'midterm_exam_date'   => $date,
        ),array(
            'courseno'  => substr($coursegroup,0,7),
            'group'     => array(substr($coursegroup,7),true),
            'year'      => $year,
            'term'      => $term,
        ));
    }
    /**
     * 清除某学年学期某课程的补考学生名单
     * @param int $year
     * @param int $term
     * @param string $coursegoup 用于精确查找的课号组号
     * @return int|string 返回成功删除的记录数目，返回值为string类型时表示执行SQL发生了严重的错误!
     */
    public function deleteCourseResitStudentsByCoursegroup($year,$term,$coursegoup){
        $rst = $this->deleteRecords('cwebs_resit_students',array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup'  => $coursegoup,
        ));
        return $rst ;
    }
    /**
     * 更具学年学期学院号获取还没输入成绩的课程
     * @param int $year
     * @param int $term
     * @param string $school 学院号
     * @param string $courseno 课号组号
     * @param string $coursename 课程名称
     * @param string $teachername 教师名称
     * @param int $scoretype 成绩类型
     * @param null|int $offset
     * @param null|int  $limit
     * @param string $opera 课程录入情况操作符， '>'或者'1'表示录入未完成的   '<'或者'-1'表示从未录入过的   '!='或者'0'表示全部显示（默认）
     * @return array|string
     * @throws Exception
     */
    public function listCoursesWhoseScoreInputness($year,$term,$school,$courseno,$coursename,$teachername,$scoretype,$offset=null,$limit=null,$opera=' != '){
        ini_set("max_execution_time", "1800");
        $scoretype = $this->scoretypeTranslation($scoretype);

        $fields_never = "
sp.COURSENO+sp.[GROUP] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
RTRIM(COURSEAPPROACHES.[VALUE]) as approach,
RTRIM(EXAMOPTIONS.[VALUE]) as examtype,
dbo.group_concat(distinct RTRIM(CLASSES.CLASSNAME),',') as classname,
dbo.group_concat(distinct RTRIM(TEACHERS.NAME),',') as teachername,
RTRIM(TEACHERS.MOBILE_LONG)+ '('+ISNULL(RTRIM(TEACHERS.MOBILE_SHORT), '-')+')' as mobile,
-1 as nums";
        $join_never = '
INNER JOIN COURSES on sp.COURSENO = COURSES.COURSENO
INNER JOIN COURSEPLAN cp on cp.[YEAR] = sp.[YEAR] and cp.TERM = sp.TERM and cp.COURSENO = sp.COURSENO and cp.[GROUP] = sp.[GROUP]
INNER JOIN COURSEAPPROACHES on cp.COURSETYPE = COURSEAPPROACHES.NAME
INNER JOIN CLASSES on cp.CLASSNO = CLASSES.CLASSNO
INNER JOIN EXAMOPTIONS on cp.EXAMTYPE = EXAMOPTIONS.NAME
INNER JOIN TEACHERPLAN tp on tp.MAP = sp.RECNO
INNER JOIN TEACHERS on tp.TEACHERNO = TEACHERS.TEACHERNO
INNER JOIN(
	SELECT DISTINCT
	sp.[YEAR] as [year],
	sp.TERM as term,
	sp.COURSENO+sp.[group] as coursenogroup,
	sp.[group]
	from SCHEDULEPLAN sp
	WHERE sp.year = :yeara and sp.term = :yearb and sp.courseno+sp.[group] like :coursegroupa and
	not EXISTS (
		SELECT 1 from cwebs_scores cs WHERE cs.courseno = sp.COURSENO and cs.[group] = sp.[GROUP] and cs.year = sp.[YEAR] and cs.term = sp.TERM
	)
) temp on temp.[year] = sp.[YEAR] and temp.term = sp.TERM and sp.COURSENO+sp.[GROUP] = temp.coursenogroup';
        $where_never = '
sp.[YEAR] = :yearb and sp.TERM = :termb and CLASSES.SCHOOL like :school and sp.COURSENO+sp.[GROUP] like :coursegroupb
and courses.coursename like :coursename and TEACHERS.NAME like :teachername';
        $group_never = 'sp.COURSENO+sp.[GROUP],COURSES.COURSENAME,COURSEAPPROACHES.[VALUE],EXAMOPTIONS.[VALUE],TEACHERS.MOBILE_LONG,TEACHERS.MOBILE_SHORT';

        $fields_half = "
sp.COURSENO+sp.[GROUP] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
RTRIM(COURSEAPPROACHES.[VALUE]) as approach,
RTRIM(EXAMOPTIONS.[VALUE]) as examtype,
dbo.group_concat(distinct RTRIM(CLASSES.CLASSNAME),',') as classname,
dbo.group_concat(distinct RTRIM(TEACHERS.NAME),',') as teachername,
RTRIM(TEACHERS.MOBILE_LONG)+ '('+ISNULL(RTRIM(TEACHERS.MOBILE_SHORT), '-')+')' as mobile,
temp.snum as nums";
        $join_half = "
INNER JOIN COURSES on sp.COURSENO = COURSES.COURSENO
INNER JOIN COURSEPLAN cp on cp.[YEAR] = sp.[YEAR] and cp.TERM = sp.TERM and cp.COURSENO = sp.COURSENO and cp.[GROUP] = sp.[GROUP]
INNER JOIN COURSEAPPROACHES on cp.COURSETYPE = COURSEAPPROACHES.NAME
INNER JOIN CLASSES on cp.CLASSNO = CLASSES.CLASSNO
INNER JOIN EXAMOPTIONS on cp.EXAMTYPE = EXAMOPTIONS.NAME
INNER JOIN TEACHERPLAN tp on tp.MAP = sp.RECNO
INNER JOIN TEACHERS on tp.TEACHERNO = TEACHERS.TEACHERNO
INNER JOIN(
SELECT
	cs.courseno+cs.[group] as coursenogroup,
	cs.[year],
	cs.term,
	COUNT(DISTINCT cs.studentno) as snum
	FROM cwebs_scores cs WHERE {$scoretype} = '' and cs.[year] = :yeara and cs.term = :yearb and cs.courseno+cs.[group] like :coursegroupa
	GROUP BY cs.courseno,cs.[group],cs.[year],cs.term having COUNT(DISTINCT cs.studentno) > 5
) temp on temp.[year] = sp.[YEAR] and temp.term = sp.TERM and sp.COURSENO+sp.[GROUP] = temp.coursenogroup";
        $where_half = $where_never;
        $group_half = '
sp.COURSENO+sp.[GROUP],COURSES.COURSENAME,COURSEAPPROACHES.[VALUE],EXAMOPTIONS.[VALUE],temp.snum,
TEACHERS.MOBILE_LONG,TEACHERS.MOBILE_SHORT';



        //简单起见写成数字
        switch($opera){
            case -1:
                $cp = array(
                    'join'      => $join_never,
                    'where'     => $where_never,
                    'group'     => $group_never,
                );
                $sp = array(
                    'distinct'  => true,
                    'fields'    => $fields_never,
                    'join'      => $join_never,
                    'where'     => $where_never,
                    'group'     => $group_never,
                );
                break;
//            case 0 :$opera = '!=';break;
            case 1 :
                $cp = array(
                    'join'      => $join_half,
                    'where'     => $where_half,
                    'group'     => $group_half,
                );
                $sp = array(
                    'distinct'  => true,
                    'fields'    => $fields_half,
                    'join'      => $join_half,
                    'where'     => $where_half,
                    'group'     => $group_half,
                );
                break;
            default:
                throw new Exception("错误的参数！ {$opera}");//默认使用传进来的原始的参数
        }
        $csql = $this->makeCountSql('SCHEDULEPLAN sp',$cp);
        $ssql = $this->makeSql('SCHEDULEPLAN sp',$sp,$offset,$limit);
        $bind = array(
            ':yeara' => $year,
            ':terma' => $term,
            ':coursegroupa'   => $courseno,
            ':yearb' => $year,
            ':termb' => $term,
            ':school'   => $school,
            ':coursegroupb'   => $courseno,
            ':coursename'   => $coursename,
            ':teachername'  => $teachername,
        );
//        mist($csql,$ssql,$bind);

        $rst = $this->getTableList2($csql,$ssql,$bind);

        return $rst;
    }

    const TYPE_FINALS  = 1;
    const TYPE_RESIT   = 2;

    /**
     * @param $year
     * @param $term
     * @param $classno
     * @param $teachername
     * @param $coursename
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listExamCourses($year,$term,$classno,$teachername,$coursename,$offset=null,$limit=null){
        $fields = "
cs.courseno+cs.[group] as courseno,
dbo.getone(RTRIM(COURSES.COURSENAME)) as coursename,
SUM(cs.midterm_lock) as midterm_lock, -- 锁定人数
SUM(cs.finals_lock) as finals_lock, -- 锁定人数
SUM(cs.resit_lock) as resit_lock, -- 锁定人数
count(*) as num, -- 锁定的学生的数目
SCHOOLS.SCHOOL as schoolno,
RTRIM(SCHOOLS.NAME) as schoolname,
cp.score_type,
cp.CLASSNO,
tp.TEACHERNO,
RTRIM(TEACHERS.NAME) as teachername";
        $join = '
INNER JOIN SCHEDULEPLAN sp ON sp.[YEAR] = cs.[year] and sp.TERM = cs.term and sp.COURSENO = cs.courseno and sp.[GROUP] = cs.[group]
LEFT OUTER JOIN TEACHERPLAN tp ON tp.MAP = sp.RECNO
INNER JOIN COURSEPLAN cp on cp.[YEAR] = cs.[year] and cp.TERM = cs.term and cp.COURSENO+cp.[GROUP] = cs.courseno+cs.[group]
INNER JOIN COURSES on cs.courseno = COURSES.COURSENO
INNER JOIN TEACHERS on TEACHERS.TEACHERNO = tp.TEACHERNO
LEFT OUTER JOIN SCHOOLS on COURSES.SCHOOL = SCHOOLS.SCHOOL';
        $where = 'cs.[year] = :year and cs.term = :term and RTRIM(COURSES.COURSENAME) like :coursename and
            cp.classno like :classno and RTRIM(TEACHERS.NAME) like :teachername';
        $group = 'cs.courseno,cs.[group],COURSES.COURSENAME,SCHOOLS.SCHOOL,SCHOOLS.NAME,cp.score_type,tp.TEACHERNO,cp.CLASSNO,TEACHERS.NAME';
        $order = 'cs.courseno';
        $csql = $this->makeCountSql('cwebs_scores cs',array(
            'join'      => $join,
            'where'     => $where,
            'group'     => $group,
            'order'     => $order,
        ));
        $ssql = $this->makeSql('cwebs_scores cs',array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
            'group'     => $group,
            'order'     => $order,
        ),$offset,$limit);
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursename'       => $coursename,
            ':classno'          => $classno,
            ':teachername'      => $teachername,
        );
//        mist($csql,$ssql,$bind);
        $rst = $this->getTableList2($csql,$ssql,$bind);
        return $rst;
    }

    /**
     * 为单条成绩记录解指定类型的锁
     * @param $recno
     * @param $type
     * @return int|string
     * @throws Exception
     */
    public function unlockForAdmin($recno,$type){
        if(!in_array($type,array('midterm_lock','finals_lock','resit_lock','retake_lock',),true)){//强制类型比较
            throw new Exception('错误的参数'.$type);
        }
        return $this->updateRecords('cwebs_scores',
            array($type=>0),//解锁即设置为0
            array('recno'=>$recno)
        );
    }

    /**
     * 锁定已有期末和总评成绩的学生
     * @param mixed $lock
     * @return int|string
     */
    public function updateAllInputedLockStatus($lock=1){
        $lock = $lock ? 1 : 0;
        $sql = "UPDATE cwebs_scores SET finals_lock = {$lock} WHERE finals_score != '' and general_score != ''";
        return $this->doneExecute($this->sqlExecute($sql));
    }

    /**
     * 获取查询开放课程的数据列表
     * @param int $year
     * @param int $term
     * @param string $school 用于模糊查询的学院号
     * @param string $courseno 用于模糊查询的课号
     * @param string $coursename 用于模糊查询的课程名称
     * @param string $teachername 用于模糊查询的教师名称
     * @param null|int $offset 记录的偏移，当同$limit设置为非null值时，可以得到mysql中 "limit m,n" 一样的效果
     * @param null|int $limit  获取记录的最大数目
     * @return array|string  获取用于easyui加载的表格数据，当SQL执行出错时返回string类型的错误信息
     */
    public function listCoursesWithOpenTableList($year,$term,$school,$courseno,$coursename,$teachername,$offset=null,$limit=null){
        $fields = "
cp.COURSENO+cp.[GROUP] as coursegroup,
rtrim(COURSES.COURSENAME) AS coursename,
rtrim(COURSEAPPROACHES.[VALUE]) AS approach,
rtrim(EXAMOPTIONS.[VALUE]) AS examtype,
ISNULL(SUM(cs.midterm_lock),-1) as midterm_lock,
ISNULL(SUM(cs.finals_lock),-1) as finals_lock,
ISNULL(SUM(cs.resit_lock),-1)  as  resit_lock,
dbo.group_concat(DISTINCT RTRIM(TEACHERS.NAME),',') as teachers";
        $join = '
LEFT OUTER JOIN SCHEDULEPLAN sp ON sp.COURSENO = cp.COURSENO and sp.[GROUP] = cp.[GROUP] and sp.[YEAR] = cp.[YEAR] and sp.TERM = cp.TERM
LEFT OUTER JOIN TEACHERPLAN on sp.RECNO = TEACHERPLAN.MAP
LEFT OUTER JOIN TEACHERS on TEACHERPLAN.TEACHERNO = TEACHERS.TEACHERNO
LEFT OUTER JOIN COURSES ON COURSES.COURSENO = cp.COURSENO
LEFT OUTER JOIN COURSEAPPROACHES ON cp.COURSETYPE = COURSEAPPROACHES.NAME
LEFT OUTER JOIN EXAMOPTIONS ON EXAMOPTIONS.NAME = cp.EXAMTYPE
LEFT OUTER JOIN cwebs_scores cs ON cs.[year] = cp.[YEAR] AND cs.term = cp.TERM AND cp.courseno = cs.COURSENO AND cs.[group] = cp.[GROUP]';
        $where = '
cp.year= :year
and cp.term= :term
and cp.school LIKE :school
and cp.COURSENO+cp.[GROUP] like :coursegroup
and COURSES.COURSENAME like :coursename
and TEACHERS.NAME like :teachername';
        $group = 'cp.COURSENO + cp.[GROUP],COURSES.COURSENAME, COURSEAPPROACHES.[VALUE],EXAMOPTIONS.[VALUE]';
        $order = 'midterm_lock DESC,finals_lock DESC,resit_lock DESC';
        $csql = $this->makeCountSql('COURSEPLAN cp',array(
            'join'      => $join,
            'where'     => $where,
            'group'     => $group,
        ));
        $ssql = $this->makeSql('COURSEPLAN cp',array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
            'group'     => $group,
            'order'     => $order,
        ),$offset,$limit);
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':school'   => $school,
            ':coursegroup'  => $courseno,
            ':coursename'   => $coursename,
            ':teachername'  => $teachername,
        );
        $rst = $this->getTableList2($csql,$ssql,$bind);
//        mist($rst,$csql,$ssql,$bind);
        return $rst;
    }

    /**
     * 获取课程的学生锁定情况列表
     * @param $year
     * @param $term
     * @param $coursegroup
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listCourseStudentsLockStatus($year,$term,$coursegroup,$offset=null,$limit=null) {
        $fields = '
midterm_lock,
finals_lock,
resit_lock,
recno,
cs.studentno,
RTRIM(STUDENTS.NAME) as studentname';
        $join = 'INNER JOIN STUDENTS on STUDENTS.STUDENTNO = cs.studentno';
        $where = 'cs.courseno+cs.[group] = :coursegroup and cs.[year] = :year and cs.term = :term';
        $csql = $this->makeCountSql('cwebs_scores cs ',array(
            'join'      => $join,
            'where'     => $where,
        ));
        $ssql = $this->makeSql('cwebs_scores cs ',array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
        ),$offset,$limit);
        $bind = array(
            ':coursegroup'  => $coursegroup,
            ':year'         => $year,
            ':term'         => $term,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }
    /**
     * 获取某学年学期课号为XXX的学生补考名单
     * 通过 cwebs_resit_students 内联 cwebs_scores 获取分数信息
     * @param int $year
     * @param int $term
     * @param string $coursegroup 用于精确查找的课程号组号
     * @param null|int $offset 记录的偏移，当同$limit设置为非null值时，可以得到mysql中 "limit m,n" 一样的效果
     * @param null|int $limit  获取记录的最大数目
     * @return array|string  获取用于easyui加载的表格数据，当SQL执行出错时返回string类型的错误信息
     */
    public function listStudentResitScoreByCourseno($year,$term,$coursegroup,$offset=null,$limit=null){
        $fields = "
cs.studentno,
RTRIM(STUDENTS.NAME) as studentname,
cs.resit_score,
cs.resit_lock as lock,
cs.recno as recno,
ISNULL(CONVERT(VARCHAR(15),cs.resit_exam_date,20),'') as examdate,
cwebs_exam_status.name as resit_status";
        $join = '
INNER JOIN cwebs_scores cs on cs.courseno+cs.[group] = crs.coursegroup and cs.[year] = crs.[year] and cs.term = crs.term and crs.studentno =cs.studentno
INNER JOIN STUDENTS on STUDENTS.STUDENTNO = cs.studentno
INNER JOIN cwebs_exam_status on cs.resit_exam_status = cwebs_exam_status.code';
        $where = 'crs.coursegroup = :coursegroup and crs.[year] = :year and crs.term  = :term';
        $csql = $this->makeCountSql('cwebs_resit_students crs',array(
            'join'      => $join,
            'where'     => $where,
        ));
        $ssql = $this->makeSql('cwebs_resit_students crs',array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
        ),$offset,$limit);
        $bind = array(
            ':coursegroup' =>$coursegroup,
            ':year' => $year,
            ':term' => $term,
        );
//        mist($csql,$ssql,$bind);
        $rst = $this->getTableList($csql,$ssql,$bind);
        return $rst;
    }
    /**
     * 获取毕业重修 课程列表
     * @param $year
     * @param $term
     * @param $coursegroupno
     * @param $school
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function getRetakeCourseList($year,$term,$coursegroupno,$school,$offset=null,$limit=null){
        $fields = '
scores.courseno,
scores.[group],
scores.courseno+scores.[group] as coursegroupno,
rtrim(courses.coursename) as coursename,
count(scores.studentno) as num,
sum(isnull(lock,0)) as lock';
        $join = 'inner join courses on courses.courseno=scores.courseno';
        $where = "
scores.year=:year
and scores.term=:term
and courses.school=:school
and scores.courseno+scores.[group] like :courseno
and scores.[group]='BY' ";
        $group = 'scores.courseno,scores.[group],courses.coursename';
        $order = 'scores.courseno,scores.[group]';
        $csql = $this->makeCountSql('scores',array(
            'where'     => $where,
            'join'      => $join,
            'group'     => $group,
        ));
        $ssql = $this->makeSql('scores',array(
            'fields'    => $fields,
            'where'     => $where,
            'join'      => $join,
            'group'     => $group,
            'order'     => $order,
        ),$offset,$limit);
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':school'   => $school,
            ':courseno' => $coursegroupno,
        );
        $rst = $this->getTableList2($csql,$ssql,$bind);
//        mist($rst,$csql,$ssql,$bind);
        return $rst;
    }


    /**
     * 获取学生成绩记录
     * @param int $recno 记录号
     * @param bool $fromcache 是否从上次查询的缓存中读取，默认为否
     * @return array|string 返回学生成绩信息的数组，放回string类型表示发生了错误
     */
    public function selectStudentScoresByRecno($recno,$fromcache=false){
//        static $cache = array();
//        if($fromcache and isset($cache[$recno])){
//            return $cache[$recno];
//        }
        $sql = 'SELECT top 1 * from cwebs_scores where recno = :recno';
        $bind = array( ':recno' => $recno);
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind),false);
        //mist($rst,$sql,$bind);
        return (is_string($rst) or !$rst)?"查询学生成绩失败！".var_export($rst,true):$rst;
    }

    /**
     * 判断学生期末考试成绩输入是否锁定
     * @param $recno
     * @return string|int 返回1表示锁定，返回0表示没有锁定，返回string表示数据库发生了错误
     * @throws Exception
     */
    public function isStudentFinalsLockedByRecno($recno){
        $rst = $this->selectStudentScoresByRecno($recno);
        if(is_string($rst)){
            throw new Exception('获取课程锁定情况失败！'.$rst);
        }else{
            return $rst['finals_lock'];
        }
    }

    /**+
     * 检查教师是否拥有指定的角色
     * @param string $role char类型
     * @param null $username
     * @return int|string string 表示查询出错
     * @throws Exception
     */
    public function checkTeacherRole($role, $username=null){
        isset($username) or $username = session('S_USER_NAME');
        if(!$username){
            throw new Exception('无法从会话中读取教师信息，请重新登陆！');
        }
        $sql = 'SELECT * from USERS WHERE ROLES like :role and USERNAME = :username';
        $rst = $this->doneQuery($this->sqlQuery($sql,array(':role'=>"%{$role}%",':username'=>$username)));
        if(is_string($rst)  ){
            return "获取失败".var_export($rst,true);
        }
        return count($rst);
    }


    /**
     * 判断学生补考是否锁定
     * @param string $coursegroup 课号组号
     * @param int $year
     * @param int $term
     * @param string $studentno 学号
     * @return int|string 返回1表示锁定，返回0表示没有锁定，返回string表示数据库发生了错误
     */
    public function isStudentResitLocked($coursegroup,$year,$term,$studentno){
        $sql = 'SELECT resit_lock from cwebs_scores WHERE
[year] = :year and term = :term and courseno+[group] = :coursegroup and studentno = :studentno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup' => $coursegroup,
            ':studentno'    => $studentno,);
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind),false);
        if(is_string($rst) ){
            return $rst;
        }
        return $rst['resit_lock'];
    }
    /**
     * 判断学生期中成绩输入是否锁定
     * @param string $coursegroup 课号组号
     * @param int $year
     * @param int $term
     * @param string $studentno 学号
     * @return int|string 返回1表示锁定，返回0表示没有锁定，返回string表示数据库发生了错误
     */
    public function isStudentMidtermLocked($coursegroup,$year,$term,$studentno){
        $sql = 'SELECT midterm_lock from cwebs_scores WHERE
[year] = :year and term = :term and courseno+[group] = :coursegroup and studentno = :studentno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup' => $coursegroup,
            ':studentno'    => $studentno,);
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind),false);
        if(is_string($rst) ){
            return $rst;
        }
        return $rst['midterm_lock'];
    }

    /**
     * 一键锁定和解锁
     * @param int $scoretype 成绩类型
     * @param int $lock 1表示将要进行上锁操作，0表示不进行上锁操作
     * @return int|string
     */
    public function updateAllLock($scoretype,$lock=0){
        switch(intval($scoretype)){
            //对应关系查看 页面 pageCoursesWithOpen
            case 2:$scoretype = 'finals_lock';break;
            case 3:$scoretype = 'resit_lock';break;
            case 1:
            default:$scoretype = 'midterm_lock';
        }
        $lock = $lock?1:0;
        $sql = "UPDATE cwebs_scores set  {$scoretype} = {$lock} ;";
        return $this->doneExecute($this->sqlExecute($sql));
    }


}