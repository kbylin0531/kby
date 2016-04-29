<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/7
 * Time: 10:38
 */

class SearcherModel extends CommonModel{

    /**
     * 获取所有班级的年级
     * @return array|string
     */
    public function getGrades(){
        $sql = 'SELECT DISTINCT top 200 DATENAME(YEAR,[YEAR]) as text,DATENAME(YEAR,[YEAR]) as [value] from CLASSES GROUP BY CLASSES.[YEAR]';
        return $this->doneQuery($this->sqlQuery($sql));
    }

    /**
     * 获取学部列表
     * @return array|int|string
     */
    public function getSchools(){
        return $this->getComboData('SCHOOLS','SCHOOL','NAME');
    }

    /**
     * 获取专业
     * @return array|string
     */
    public function getMajor(){
        $sql = 'SELECT CODE AS [value],NAME as [text] FROM MAJORCODE';
        return $this->doneQuery($this->sqlQuery($sql));
    }

    /**
     * 获取专业条目（小专业）
     * @param string $majorcode 专业代号
     * @return array|string
     */
    public function getMajorItem($majorcode='%'){
        $sql = 'SELECT RTRIM(m.CLASS_NATURE) as [value],
RTRIM(mc.NAME)+\'(\'+RTRIM(m.REM)+\')\' as [text] FROM MAJORS m
INNER JOIN MAJORCODE  mc on mc.CODE = m.MAJORNO
 WHERE RTRIM(m.MAJORNO) like :majorcode';

//        mist($sql,$majorcode);
        return $this->doneQuery($this->sqlQuery($sql,array(
            ':majorcode'    => trim($majorcode),
        )));
    }

    /**
     * 获取对应年级下的班级列表
     * @param $grade
     * @param $school
     * @return array|string
     */
    public function getClassesByGradeAndSchoolno($grade,$school){
        $sql = 'SELECT top 200 RTRIM(CLASSNO) as [value],RTRIM(CLASSNAME) as [text]
              from CLASSES WHERE DATENAME (YEAR,CLASSES.[YEAR]) like :grade AND SCHOOL like :school
              ORDER  By CLASSNO';
        $bind = array(
            ':grade' => doWithBindStr($grade),
            ':school' => doWithBindStr($school),
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    /**
     * 获取这个班级该学年学期的所有上课教师
     * @param int $year 学年
     * @param int $term 学期
     * @param string $classno 班级号
     * @return array|string
     */
    public function getTeachersByClassno($year=null,$term=null,$classno='%'){
        if(!isset($year,$term)) return array();
        $sql = '
SELECT DISTINCT
RTRIM(tp.TEACHERNO) as [value],
RTRIM(TEACHERS.NAME) as [text]
FROM SCHEDULE s
INNER JOIN TEACHERPLAN tp on tp.RECNO = s.MAP
INNER JOIN TEACHERS on TEACHERS.TEACHERNO = tp.teacherno
INNER JOIN COURSEPLAN cp ON cp.[YEAR] = s.[YEAR] and cp.TERM = s.TERM and cp.COURSENO = s.COURSENO and cp.[GROUP] = s.[GROUP]
WHERE s.[YEAR] = :year and s.TERM = :term AND cp.CLASSNO like :classno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':classno'  => $classno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    /**
     * 获取对应班级下的学生信息
     * @param string $classno 班级号
     * @return array|string
     */
    public function getStudentsByClassno($classno='%'){
        $sql = 'SELECT top 200 STUDENTNO as [value],RTRIM(NAME) as [text] from STUDENTS WHERE CLASSNO like :classno;';
        $bind = array(
            ':classno' => doWithBindStr($classno),
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    /**
     * 修课方式
     * @return array|string
     */
    public function getCourseApproach(){
        $sql = 'SELECT NAME as [value],RTRIM([VALUE]) as [text] FROM COURSEAPPROACHES';
        return $this->doneQuery($this->sqlQuery($sql));
    }

    /**
     * 课程类型一
     * @return array|string
     */
    public function getCourseType(){
        $sql = 'SELECT NAME as [value],RTRIM([VALUE]) as [text] FROM COURSETYPEOPTIONS';
        return $this->doneQuery($this->sqlQuery($sql));
    }

    /**
     * 考试类型
     * @return array|string
     */
    public function getCourseExamType(){
        $sql = 'SELECT NAME as [value],RTRIM([VALUE]) as [text] FROM EXAMOPTIONS';
        return $this->doneQuery($this->sqlQuery($sql));
    }

    /**
     * 获取成绩类型，百分制、五级值
     * @return array|string
     */
    public function getScoreType(){
        $sql = 'SELECT id as [value],description as [text] FROM cwebs_score_type';
        return $this->doneQuery($this->sqlQuery($sql));
    }

    /**
     * 获取比赛等级列表
     * @param string $type 比赛类型
     * @return array|string
     */
    public function getCompetitionLevel($type){
        $sql = 'select id as [value],name as [text] from cwebs_sreward_competition_level WHERE type like :type';
        return $this->doneQuery($this->sqlQuery($sql,array(':type'=>$type)));
    }

    /**
     * 获奖名次
     * @param $type
     * @return array|string
     */
    public function getCompetitionRank($type){
        $sql = 'select id as [value],name as [text] from cwebs_sreward_competition_rank WHERE type like :type';
        return $this->doneQuery($this->sqlQuery($sql,array(':type'=>$type)));
    }

    /**
     * 更具学院号和组号获取教室列表
     * @param string $school
     * @param string $tgroup
     * @return array|string
     */
    public function getTeachersByGroup($school='%', $tgroup='%'){
        $sql  = 'SELECT top 200 RTRIM(T.TEACHERNO) as [value],RTRIM(T.NAME) as [text] from TEACHERS T';
        $sql .= ' LEFT JOIN SCHOOLS S ON T.SCHOOL=S.SCHOOL LEFT JOIN TGROUPS S1 ON T.TGROUP=S1.TGROUP';
        $sql .= ' where T.SCHOOL LIKE :SCHOOL AND T.TGROUP LIKE :TGROUP';

        $bind = array(':SCHOOL' => $school, ':TGROUP' => $tgroup);
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    /**
     * 获取教研组列表项
     * @return array|string
     */
    public function getTGroup(){
        $sql = 'select RTRIM(TGROUP) as [value],RTRIM(NAME) as [text] from TGROUPS ORDER BY ORDERBY';
        return $this->doneQuery($this->sqlQuery($sql));
    }

}