<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/4/6
 * Time: 13:23
 */
class PrintModel extends CommonModel {

    /**
     * 获取学生奖励列表
     * @param string $grade
     * @param string $school
     * @param string $classno
     * @param string $studentno
     * @return array|string
     */
    public function listStudentsReward($grade='%',$school='%',$classno='%',$studentno='%'){
        $sql = '
SELECT csr.[year],csr.term, csr.name,csr.studentno,csr.rem,csr.credit
from cwebs_sreward_recordlist csr
INNER JOIN STUDENTS on STUDENTS.STUDENTNO = csr.studentno
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
WHERE YEAR(STUDENTS.ENTERDATE) = :grade
and CLASSES.SCHOOL like :school
and CLASSES.CLASSNO like :classno
and STUDENTS.STUDENTNO like :studentno
ORDER BY csr.[year] DESC ,csr.term DESC';

        $list = $this->doneQuery($this->sqlQuery($sql,array(
            ':grade'    => $grade,
            ':school'   => $school,
            ':classno'  => $classno,
            ':studentno'=> $studentno,
        )));

        return $list;
    }

    /**
     * 获取学生列表
     * @param string $grade
     * @param string $school
     * @param string $classno
     * @param string $studentno
     * @return array
     */
    public function listStudentsCourses($grade='%',$school='%',$classno='%',$studentno='%'){
        $fields = '
cs.year,
cs.term,
cs.courseno,
RTRIM(COURSES.COURSENAME) as coursename,
cs.[group],
cs.credit,
cs.point,
cs.general_score,
cs.resit_score,
RTRIM(SCHOOLS.NAME)  as schoolname,
CLASSES.classno as classno,
RTRIM(CLASSES.CLASSNAME) as classname,
STUDENTS.STUDENTNO as studentno,
RTRIM(MAJORCODE.NAME) as majorname,
RTRIM(STUDENTS.NAME) as studentname,
-- cp.course_type_options as coursetype,
cp.COURSETYPE as approach,
cp.LIMITGROUPNO as limitgroupno,
RTRIM(COURSETYPEOPTIONS.[VALUE]) as coursetypename';
        $join = '
INNER JOIN PERSONAL on PERSONAL.studentno = STUDENTS.STUDENTNO
INNER JOIN MAJORCODE on MAJORCODE.CODE = PERSONAL.MAJOR
INNER JOIN CLASSES on CLASSES.CLASSNO = STUDENTS.CLASSNO
INNER JOIN SCHOOLS on CLASSES.SCHOOL = SCHOOLS.SCHOOL
INNER JOIN cwebs_scores cs on cs.studentno = STUDENTS.STUDENTNO
INNER JOIN COURSEPLAN cp on cs.[year] = cp.[YEAR] and cs.term = cp.TERM and cs.courseno = cp.COURSENO and cs.[group] = cp.[GROUP] and cp.CLASSNO = STUDENTS.CLASSNO
INNER JOIN COURSES on COURSES.COURSENO = cs.courseno
LEFT OUTER JOIN COURSETYPEOPTIONS on COURSETYPEOPTIONS.NAME = cp.course_type_options';
        $where = '
YEAR(STUDENTS.ENTERDATE) = :grade
and CLASSES.SCHOOL like :school
and CLASSES.CLASSNO like :classno
and STUDENTS.STUDENTNO like :studentno';

        $sql = $this->makeSql('STUDENTS',array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
        ));
        $list = $this->doneQuery($this->sqlQuery($sql,array(
            ':grade'    => $grade,
            ':school'   => $school,
            ':classno'  => $classno,
            ':studentno'=> $studentno,
        )));


//        varsdumpout($sql,array(
//            ':grade'    => $grade,
//            ':school'   => $school,
//            ':classno'  => $classno,
//            ':studentno'=> $studentno,
//        ));

        return $list;
    }


}