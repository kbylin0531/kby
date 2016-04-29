<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/26
 * Time: 12:41
 */
class UserinfoModel extends CommonModel{

    /**
     * 获取课程学生选课数据
     * @param $year
     * @param $term
     * @param $courseno
     * @return array|string
     */
    public function getCourseSelectionTableList($year,$term,$courseno){
        $fields = "
STUDENTS.NAME as studentname,
R32.STUDENTNO as studentno,
'' as cx,--重修
'' as tkfs,--听课方式
COURSEAPPROACHES.[VALUE] as coursetype,
EXAMOPTIONS.[VALUE] as kh,--考核方式
rtrim(CLASSES.CLASSNAME) as studentclass,
CLASSES.CLASSNO as studentclassno,
RTRIM(TEACHERS.NAME)+' / '+TEACHERS.MOBILE_SHORT as headteacher";
        $join = '
LEFT OUTER JOIN STUDENTS on STUDENTS.STUDENTNO = R32.STUDENTNO
LEFT OUTER JOIN COURSEAPPROACHES on R32.APPROACH = COURSEAPPROACHES.NAME
LEFT OUTER JOIN EXAMOPTIONS on R32.EXAMTYPE = EXAMOPTIONS.NAME
LEFT OUTER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
LEFT OUTER JOIN TEACHERS on CLASSES.CHARGE_TEACHERNO = TEACHERS.TEACHERNO
';
        $where = 'R32.COURSENO+R32.[GROUP] = :courseno and R32.[YEAR] = :year and R32.TERM = :term';
        $csql = $this->makeCountSql('R32',array(
            'join' => $join,
            'where' => $where
        ));
        $ssql = $this->makeSql('R32',array(
            'fields'    => $fields,
            'join' => $join,
            'where' => $where
        ));
        $bind = array(
            ':courseno' => $courseno,
            ':year'     => $year,
            ':term'     => $term,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }

    public function getCoursePlanInfo($year,$term,$courseno){
        $sql = '
SELECT COURSEPLAN.COURSENO+COURSEPLAN.[GROUP] AS COURSENOGROUP,
COURSES.COURSENAME,
COURSEPLAN.CREDITS,
SCHOOLS.NAME AS SCHOOLNAME,
SCHOOLS.SCHOOL,
EXAMOPTIONS.VALUE AS EXAM,
SCHEDULEPLAN.ESTIMATE AS ESTIMATE,
COURSES.SYLLABUS AS SYLLABUS
FROM COURSEPLAN INNER JOIN COURSES ON COURSEPLAN.COURSENO=COURSES.COURSENO
INNER JOIN SCHOOLS ON COURSES.SCHOOL=SCHOOLS.SCHOOL
INNER JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME
JOIN SCHEDULEPLAN ON COURSEPLAN.YEAR=SCHEDULEPLAN.YEAR AND COURSEPLAN.TERM=SCHEDULEPLAN.TERM
AND COURSEPLAN.COURSENO=SCHEDULEPLAN.COURSENO AND COURSEPLAN.[GROUP]=SCHEDULEPLAN.[GROUP]
WHERE COURSEPLAN.YEAR=:year
AND COURSEPLAN.TERM=:term
AND COURSEPLAN.COURSENO+COURSEPLAN.[GROUP]=:courseno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':courseno' => $courseno
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    /**
     * 获取教师信息
     * @param null|string $year
     * @param $term
     * @param $courseno
     * @return array|string
     */
    public function getTeacherInfo($year,$term,$courseno){
        $sql = '
SELECT
TEACHERS.NAME AS TEACHERNAME,
TASKOPTIONS.NAME AS TASK
FROM SCHEDULEPLAN
INNER JOIN TEACHERPLAN ON SCHEDULEPLAN.RECNO=TEACHERPLAN.MAP
INNER JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO
LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE
WHERE SCHEDULEPLAN.YEAR=:year
AND SCHEDULEPLAN.TERM=:term
AND SCHEDULEPLAN.COURSENO+SCHEDULEPLAN.[GROUP]=:courseno
ORDER BY TEACHERNAME,TASK';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':courseno' => $courseno
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    public function getMyMessage($teacherno,$offset,$limit){
        $fields = '
RTRIM(TEACHERS.NAME) js,
RTRIM(TEACHERS.TEACHERNO) jsh,
RTRIM(POSITIONS.VALUE) zc,
RTRIM(SCHOOLS.NAME) xy';
        $join = 'LEFT OUTER JOIN POSITIONS on  TEACHERS.POSITION=POSITIONS.NAME
LEFT OUTER JOIN SCHOOLS on TEACHERS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN users on  teachers.teacherno=users.teacherno';
        $where = 'TEACHERS.TEACHERNO = :teacherno ';
        $order = 'TEACHERS.TEACHERNO';
        $csql = $this->makeCountSql('TEACHERS',array(
            'join' => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('TEACHERS',array(
            'fields'    => $fields,
            'join' => $join,
            'where' => $where,
//            'order' => $order,
        ),$offset,$limit);
//        vardump($csql,$ssql);
        return $this->getTableList($csql,$ssql,array(':teacherno'=>$teacherno));
    }


    /**
     * 获取教师的课程列表
     * @param $year
     * @param $term
     * @param $teacherno
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getTeacherCourses($year,$term,$teacherno,$offset,$limit){
        $fields = "
dbo.GETONE(RTRIM(COURSENOGROUP)) AS kh,
dbo.GETONE(RTRIM(COURSENAME)) AS km,
dbo.GETONE(CREDITS) AS xf,
dbo.GETONE(WEEKHOURS) AS zxs,
dbo.GETONE(WEEKEXPEHOURS) AS zsy,
dbo.GETONE(RTRIM(COURSETYPE)) AS xk,
dbo.GETONE(RTRIM(EXAMTYPE)) AS kh2,
dbo.GETONE(RTRIM(ATTENDENTS)) AS ATTENDENTS, --实际人数
dbo.GETONE(RTRIM(SCHOOLNAME)) AS kkxy,
dbo.GROUP_CONCAT_MERGE(RTRIM(CLASSNONAME),'; ') AS class,
dbo.GROUP_CONCAT_MERGE(RTRIM(TEACHERNONAME),'; ') AS js,
dbo.GETONE(RTRIM(REM)) AS rem,
dbo.GROUP_CONCAT_MERGE(RTRIM(DAYNTIME),'; ') AS kcap";
        $group = 'COURSENOGROUP';
        $where = '
YEAR = :YEAR
AND TERM = :TERM
AND (TEACHERNO=:TEACHERNO)';
        $csql = $this->makeCountSql('VIEWSCHEDULE',array(
            'group' => $group,
            'where' => $where,
        ));
        $ssql = $this->makeSql('VIEWSCHEDULE',array(
            'fields'    => $fields,
            'group' => $group,
            'where' => $where,
        ),$offset,$limit);
        return $this->getTableList($csql,$ssql,array(
            ':YEAR' => $year,
            ':TERM' => $term,
            ':TEACHERNO'  => $teacherno,
        ));
    }
}