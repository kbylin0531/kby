<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/11
 * Time: 14:07
 */
class ResultSelectModel extends  ResultModel {

    /**
     * 获取毕业前补考学生列表数据
     * @param $year
     * @param $term
     * @param $courseno
     * @param $coursename
     * @param $school
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function getRetakeStudentsTableList($year,$term,$courseno,$coursename,$school,$offset=null,$limit=null){
        $fields = '
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
cs.studentno,
cs.courseno,
cs.[group],
RTRIM(STUDENTS.NAME) as studentname,
RTRIM(CLASSES.CLASSNAME) as classname,
cs.general_score,
cs.resit_score,
cs.retake_score';
        $join = '
INNER JOIN COURSES on COURSES.COURSENO = cs.courseno
INNER JOIN STUDENTS on STUDENTS.STUDENTNO = cs.studentno
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO';
        $where = '
cs.[year] = :year and cs.term = :term and  CLASSES.SCHOOL like :school
and cs.courseno+cs.[group] like :courseno and COURSES.COURSENAME like :coursename';
        $order = 'cs.courseno,cs.[group]';
        $csql = $this->makeCountSql('view_scores_general_fail cs',array(
            'where' => $where,
            'join'  => $join,
        ));
        $ssql = $this->makeSql('view_scores_general_fail cs',array(
            'fields'   => $fields,
            'where' => $where,
            'join'  => $join,
            'order' => $order,
        ),$offset,$limit);
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':school'   => $school,
            ':courseno' => $courseno,
            ':coursename'   => $coursename,
        );
        $rst = $this->getTableList($csql,$ssql,$bind);
//        mist($rst,$csql,$ssql,$bind);
        return $rst;
    }
    /**
     * 鑾峰彇鏌愬骞村鏈熻鍙蜂负XXX鐨勫鐢熻ˉ鑰冨悕鍗�
     * 閫氳繃 cwebs_resit_students 鍐呰仈 cwebs_scores 鑾峰彇鍒嗘暟淇℃伅
     * @param int $year
     * @param int $term
     * @param string $coursegroup 鐢ㄤ簬绮剧‘鏌ユ壘鐨勮绋嬪彿缁勫彿
     * @param null|int $offset 璁板綍鐨勫亸绉伙紝褰撳悓$limit璁剧疆涓洪潪null鍊兼椂锛屽彲浠ュ緱鍒癿ysql涓� "limit m,n" 涓�鏍风殑鏁堟灉
     * @param null|int $limit  鑾峰彇璁板綍鐨勬渶澶ф暟鐩�
     * @return array|string  鑾峰彇鐢ㄤ簬easyui鍔犺浇鐨勮〃鏍兼暟鎹紝褰揝QL鎵ц鍑洪敊鏃惰繑鍥瀞tring绫诲瀷鐨勯敊璇俊鎭�
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
        $rst = $this->getTableList($csql,$ssql,$bind);
//        mist($csql,$ssql,$bind);
        return $rst;
    }
    /**
     * 查询课程成绩 列表数据
     * @param $year
     * @param $term
     * @param $courseno
     * @param $studentno
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function getCourseStudentScoreTableList($year,$term,$courseno,$studentno,$offset=null,$limit=null){
        $fields = '
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
cp.CREDITS as credit,
cs.studentno,
RTRIM(STUDENTS.NAME) as studentname,
RTRIM(COURSEAPPROACHES.[VALUE]) as approach,
cs.normal_score,
cs.midterm_score,
cs.finals_score,
cs.general_score,
cs.resit_score,
cs.retake_score';
        $join = '
INNER JOIN COURSES on cs.courseno = COURSES.COURSENO
INNER JOIN STUDENTS on cs.studentno = STUDENTS.STUDENTNO
INNER JOIN COURSEPLAN cp on cp.courseno = cs.courseno and cp.[group] = cs.[group] and cp.year = cs.year and cp.term = cs.term and STUDENTS.CLASSNO = cp.CLASSNO
LEFT OUTER JOIN COURSEAPPROACHES on COURSEAPPROACHES.NAME = cp.COURSETYPE';
        $where = 'cs.[year] = :year and cs.term = :term  and cs.courseno+cs.[group] like :coursegroup and cs.studentno like :studentno';
        $csql = $this->makeCountSql('cwebs_scores cs',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('cwebs_scores cs',array(
            'fields'    => $fields,
            'join'  => $join,
            'where' => $where,
        ),$offset,$limit);
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup' => $courseno,
            ':studentno' => $studentno
        );
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 查询学生个人成绩  课程成绩总表
     * @param $year
     * @param $term
     * @param $studentno
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function getPersonScoresInfoList($year,$term,$studentno,$offset=null,$limit=null){
        $fields = '
cs.[year],
cs.term,
cs.courseno + cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
SUM(cp.CREDITS) as credit,
RTRIM(ca.[VALUE]) as approach,
RTRIM(eo.[VALUE]) as examtype,
cs.normal_score,
cs.midterm_score,
cs.finals_score,
cs.general_score,
cs.resit_score';
        $join = '
INNER JOIN COURSES on cs.courseno = COURSES.COURSENO
INNER JOIN COURSEPLAN cp on cp.[YEAR] = cs.[year] and cp.TERM = cs.term
	and cp.COURSENO = cs.courseno and cp.[GROUP] = cs.[group]
LEFT OUTER JOIN COURSEAPPROACHES ca on ca.NAME = cp.COURSETYPE -- 修课方式
LEFT OUTER JOIN EXAMOPTIONS eo on eo.NAME = cp.EXAMTYPE';
        $where = 'cs.[year] = :year and cs.term = :term and cs.studentno = :studentno';
        $group = 'cs.courseno ,cs.[group],COURSES.COURSENAME,ca.[VALUE],eo.[VALUE],cs.general_score,cs.normal_score,
            cs.resit_score,cs.[year],cs.term,cs.midterm_score,cs.finals_score';
        $csql = $this->makeCountSql('cwebs_scores cs',array(
            'where' => $where,
            'join'  => $join,
            'group' => $group,
        ));
        $ssql = $this->makeSql('cwebs_scores cs',array(
            'fields'    => $fields,
            'where' => $where,
            'join'  => $join,
            'group' => $group,
        ),$offset,$limit);
        $bind = array(
            ':year'     => $year,
            ':term'     => $term,
            ':studentno'    => $studentno,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }
    /**
     * 成绩查询页面 获取个人信息
     * @param $year
     * @param $term
     * @param $studentno
     * @return array|string
     */
    public function getPersonalInfo($year,$term,$studentno){
        $sql = '
SELECT
RTRIM(CLASSES.CLASSNAME) as classname,
RTRIM(STUDENTS.NAME) as studentname,
RTRIM(SCHOOLS.NAME ) as schoolname,
SUM(cp.CREDITS) as credit
from STUDENTS
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
INNER JOIN SCHOOLS on CLASSES.SCHOOL = SCHOOLS.SCHOOL
INNER JOIN cwebs_scores cs on cs.studentno = STUDENTS.STUDENTNO and cs.[year] = :year and cs.term = :term
INNER JOIN COURSEPLAN cp on cp.year = cs.year and cp.term = cs.term and cs.courseno = cp.courseno and cs.[group] = cp.[group]
WHERE STUDENTS.STUDENTNO = :studentno
GROUP BY CLASSES.CLASSNAME,STUDENTS.NAME,SCHOOLS.NAME
';
        $bind = array(
            ':year'     => $year,
            ':term'     => $term,
            ':studentno'    => $studentno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }



    /**
     * 查询学生个人成绩  期末未通过的课程
     * @param $year
     * @param $term
     * @param $studentno
     * @param int $scoretype
     * @param null $offset
     * @param null $limit
     * @return array|string
     * @throws Exception
     */
    public function getPersonScoresPasslessInfoList($year,$term,$studentno,$scoretype=1,$offset=null,$limit=null){
        $vnm = $this->scoreViewtranslate($scoretype,'cs');
        $fields = '
cs.[year],
cs.term,
cs.courseno + cs.[group] AS coursegroup,
RTRIM(COURSES.COURSENAME) AS coursename,
SUM (cp.CREDITS) AS credit,
RTRIM(ca.[VALUE]) AS approach,
RTRIM(eo.[VALUE]) AS examtype,
cs.midterm_score,
cs.finals_score,
cs.general_score,
cs.resit_score';
        $join = '
INNER JOIN COURSES ON cs.courseno = COURSES.COURSENO
INNER JOIN COURSEPLAN cp ON cp.[YEAR] = cs.[year]
AND cp.TERM = cs.term
AND cp.COURSENO = cs.courseno
AND cp.[GROUP] = cs.[group]
LEFT OUTER JOIN COURSEAPPROACHES ca ON ca.NAME = cp.COURSETYPE -- 修课方式
LEFT OUTER JOIN EXAMOPTIONS eo ON eo.NAME = cp.EXAMTYPE';
        $where = 'cs.[year] = :year AND cs.term = :term AND cs.studentno = :studentno';
        $group = 'cs.courseno ,cs.[group],COURSES.COURSENAME,ca.[VALUE],eo.[VALUE],cs.midterm_score,cs.finals_score,cs.general_score,cs.resit_score,cs.[year],cs.term';
        $csql = $this->makeCountSql($vnm,array(
            'where' => $where,
            'join'  => $join,
            'group' => $group,
        ));
        $ssql = $this->makeSql($vnm,array(
            'fields'    => $fields,
            'where' => $where,
            'join'  => $join,
            'group' => $group,
        ),$offset,$limit);
        $bind = array(
            ':year'     => $year,
            ':term'     => $term,
            ':studentno'    => $studentno,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 不及格名称汇总
     * @param $year
     * @param $term
     * @param $coursegroup
     * @param int $scoretype
     * @param string $school
     * @param string $classno
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function getPasslessStudentsTableList($year,$term,$coursegroup,$scoretype=1,$school='%',$classno='%',$offset=null,$limit=null){
        $vnm = $this->scoreViewtranslate($scoretype,'cs');
        $fields = '
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
cs.studentno,
RTRIM(STUDENTS.NAME) as studentname,
RTRIM(SCHOOLS.NAME) as schoolname,
-- 学生所在班级号 不同于 课程所在的班级号，可能是换班级所致
RTRIM(STUDENTS.CLASSNO) as student_classno , -- 学生班级号
RTRIM(CLASSES.CLASSNAME) as classname, -- 学生班级名称
cp.CLASSNO as courseplan_classno , -- 开课计划对应的班级号
RTRIM(clss2.CLASSNAME) as courseplan_classname,
cs.general_score,
cs.resit_score,
RTRIM(COURSEAPPROACHES.[VALUE]) as approach';
        $join = '
INNER JOIN COURSES on COURSES.COURSENO = cs.courseno
INNER JOIN STUDENTS on cs.studentno = STUDENTS.STUDENTNO
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
INNER JOIN SCHOOLS on STUDENTS.SCHOOL = SCHOOLS.SCHOOL
INNER JOIN COURSEPLAN cp on cs.[year] = cp.[YEAR] and cs.term = cp.TERM
	and cs.courseno = cp.COURSENO and cs.[group] = cp.[GROUP] and STUDENTS.CLASSNO = cp.CLASSNO
INNER JOIN CLASSES clss2 on  clss2.CLASSNO = cp.CLASSNO
LEFT OUTER JOIN COURSEAPPROACHES on COURSEAPPROACHES.NAME = cp.COURSETYPE';
        $where = 'WHERE cs.[year] = :year and cs.term = :term and  cs.courseno+cs.[group] like :coursegroup
and SCHOOLS.SCHOOL LIKE :school and CLASSES.CLASSNO like :classno';
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
            ':year' => $year,
            ':term' => $term,
            ':coursegroup'  => $coursegroup,
            ':school'   => $school,
            ':classno'  => $classno,
        );
        $rst = $this->getTableList($csql,$ssql,$bind);
//        mist($rst,$csql,$ssql,$bind);
        return $rst;
    }
    /**
     * 获取班级学生课程学分获取情况和总学分情况
     * @param $year
     * @param $term
     * @param $classno
     * @return array|string
     */
    public function getClassStudentCreditConditions($year,$term,$classno){
        $sql = "
SELECT
st.STUDENTNO as studentno,
RTRIM(st.NAME) as studentname,
ISNULL(selections.totalcredit,0) as totalcredit, -- 总选课学分
ISNULL(passedselections.passedcredit,0) as passedcredit  -- 获得学分
from STUDENTS st
LEFT OUTER JOIN (
    -- 全部选课学分
    SELECT
	R32.STUDENTNO as studentno,
	R32.[YEAR] as year,
	R32.TERM as term,
	SUM(cp.CREDITS) as  totalcredit
	FROM R32
	INNER JOIN STUDENTS on STUDENTS.STUDENTNO = R32.STUDENTNO
	INNER JOIN COURSEPLAN cp on  cp.[YEAR] = R32.[YEAR] and cp.TERM = R32.TERM and cp.[GROUP] = R32.[GROUP] and R32.COURSENO = cp.COURSENO
		and STUDENTS.CLASSNO = cp.CLASSNO
	WHERE R32.[YEAR] = :yeara and R32.TERM = :terma
	GROUP BY R32.STUDENTNO,R32.[YEAR],R32.TERM
) as selections on selections.studentno = st.STUDENTNO
LEFT OUTER JOIN (
	-- 通过的学分
	SELECT
	cs.studentno ,
	cs.year ,
	cs.term ,
	SUM(cp.CREDITS) as passedcredit
	from view_scores_general_pass cs
	INNER JOIN STUDENTS on cs.studentno = STUDENTS.STUDENTNO
	INNER JOIN COURSEPLAN cp on cs.courseno = cp.COURSENO and cs.[group] = cp.[GROUP]
		and cs.year = cp.[YEAR] and cs.term = cp.TERM and cp.CLASSNO = STUDENTS.CLASSNO
	where cs.[year] = :yearb and cs.term = :termb
	GROUP BY  cs.studentno , cs.year ,cs.term
) as passedselections on  passedselections.studentno = st.STUDENTNO and passedselections.[year] = selections.[year] and passedselections.term = selections.term
WHERE st.CLASSNO = :classno
ORDER BY st.STUDENTNO";
        $bind = array(
            ':yeara' => $year,
            ':terma' => $term,
            ':yearb' => $year,
            ':termb' => $term,
            ':classno'  => $classno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }
    /**
     * 获取学生课程信息
     * @param $year
     * @param $term
     * @param $classno
     * @param $approach
     * @return array|string
     */
    /**********************************change by zqq @2016.2.6*****************************************************************/
    public function getClassStudentsCoursesScores($year,$term,$classno,$approach){
         $sql2="
select
t1.studentno,
coursegroup,
coursename,
credits,
t1.approach,
CAST(normal_score as VARCHAR(15)) as normal_score,
CAST(midterm_score as VARCHAR(15)) as midterm_score,
CAST(finals_score as VARCHAR(15)) as finals_score,
CAST(general_score as VARCHAR(15)) as general_score,
t1.CLASSNO
from
(
SELECT
temp.CLASSNO,
st.STUDENTNO as studentno,
temp.[year],
temp.term,
coursegroup,
coursename,
credits,
temp.approach
from STUDENTS st
inner JOIN (
SELECT DISTINCT
cs.[year],
cs.term,
cl.CLASSNO,
cs.courseno+cs.[group] as coursegroup,
RTRIM(cu.COURSENAME) as coursename,
cp.COURSETYPE as approach,
cp.CREDITS as credits
from cwebs_scores cs
INNER JOIN COURSES cu ON cs.courseno = cu.COURSENO
INNER JOIN COURSEPLAN cp on cs.courseno = cp.COURSENO and cs.[group] = cp.[GROUP] AND cs.year = cp.[YEAR] and cs.term = cp.TERM
INNER JOIN STUDENTS on cs.studentno = STUDENTS.STUDENTNO
INNER JOIN CLASSES cl on STUDENTS.CLASSNO = cl.CLASSNO
where cs.[year] = :ya and cs.term = :ta and cl.CLASSNO = :ca and cp.COURSETYPE like :approach
) as temp  on temp.CLASSNO = st.CLASSNO

--where rtrim(st.CLASSNO) = :classa
) as t1
left join cwebs_scores cs
on cs.courseno + cs.[group] = t1.coursegroup and t1.STUDENTNO = cs.studentno

where t1.[year]= :year and t1.term= :term and t1.CLASSNO = :classno
ORDER BY t1.STUDENTNO,t1.approach desc,t1.coursegroup ";
        $bind2 = array(
            ':ya'     => $year,
            ':ta'     => $term,
          //  ':ca'  => $classno,
           ':ca'  => array($classno, SQLSRV_PARAM_IN ,null ,SQLSRV_SQLTYPE_CHAR(strlen($classno))),
            ':approach' => $approach,
            //':classa'  => $classno,
            ':year'     => $year,
            ':term'     => $term,
            ':classno'  => $classno,
        );
        $sql = '
SELECT
cs.studentno,
coursegroup,
coursename,
credits,
temp.approach,
CAST(normal_score as VARCHAR(15)) as normal_score,
CAST(midterm_score as VARCHAR(15)) as midterm_score,
CAST(finals_score as VARCHAR(15)) as finals_score,
CAST(general_score as VARCHAR(15)) as general_score
from cwebs_scores cs
inner JOIN (
SELECT DISTINCT
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
COURSEPLAN.COURSETYPE as approach,
COURSEPLAN.CREDITS as credits
from cwebs_scores cs
INNER JOIN COURSES ON cs.courseno = COURSES.COURSENO
INNER JOIN COURSEPLAN on cs.courseno = COURSEPLAN.COURSENO and cs.[group] = COURSEPLAN.[GROUP] AND cs.year = COURSEPLAN.[YEAR] and cs.term = COURSEPLAN.TERM
INNER JOIN STUDENTS on cs.studentno = STUDENTS.STUDENTNO
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
where cs.[year] = :year and cs.term = :term and CLASSES.CLASSNO = :classno and COURSEPLAN.COURSETYPE LIKE :approach
) temp  on temp.coursegroup = cs.courseno+ cs.[group]
ORDER BY studentno,temp.coursegroup';
        $bind = array(
            ':year'     => $year,
            ':term'     => $term,
            ':classno'  => $classno,
            ':approach' => $approach,
        );
        $list = $this->doneQuery($this->sqlQuery($sql2,$bind2));
//        mist($list,$sql,$bind);
        return $list;
    }
    /**
     * 获取班级课程成绩信息
     * @param $year
     * @param $term
     * @param $classno
     * @return array|string
     */
    public function getClassCourses($year,$term,$classno){
        $sql = '
SELECT DISTINCT
cs.courseno+cs.[group] as coursegroup,
RTRIM(COURSES.COURSENAME) as coursename,
COURSEPLAN.CREDITS as credits
from cwebs_scores cs
INNER JOIN COURSES ON cs.courseno = COURSES.COURSENO
INNER JOIN COURSEPLAN on cs.courseno = COURSEPLAN.COURSENO and cs.[group] = COURSEPLAN.[GROUP] AND cs.year = COURSEPLAN.[YEAR] and cs.term = COURSEPLAN.TERM
INNER JOIN STUDENTS on cs.studentno = STUDENTS.STUDENTNO
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
where cs.[year] = :year and cs.term = :term and CLASSES.CLASSNO = :classno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':classno'  => $classno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }








}