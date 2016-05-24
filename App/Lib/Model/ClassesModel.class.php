<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/10/15
 * Time: 10:09
 */
class ClassesModel extends CommonModel {

    public function listStudentsByClassno($classno,$offset=null,$limit=null){
        $fields = "
STUDENTS.STUDENTNO as studentno,
RTRIM(STUDENTS.NAME) as studentname,
case when '1' = STUDENTS.SEX THEN '男'
else '女' end as sexname,
isnull(cscc.origin_classname,'') as preclassname,
RTRIM(MAJORCODE.NAME) as major,
isnull(cscc.change_date,'') as change_date";
        $join = "
INNER JOIN PERSONAL on PERSONAL.studentno = STUDENTS.STUDENTNO
INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
LEFT OUTER JOIN cwebs_student_class_change cscc on cscc.studentno = STUDENTS.STUDENTNO
LEFT OUTER JOIN MAJORCODE on MAJORCODE.CODE = PERSONAL.MAJOR";
        $where = "STUDENTS.CLASSNO = :classno  and STUDENTS.STATUS = 'A'";
        $order = "cscc.change_date";
        $csql = $this->makeCountSql('STUDENTS',array(
            'join'      => $join,
            'where'     => $where,
        ));
        $ssql = $this->makeSql('STUDENTS',array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
            'order'     => $order,
        ),$offset,$limit);

//        varsdumpout($csql,$ssql,$classno);

        $list = $this->getTableList($csql,$ssql,array(
                ':classno'  => $classno,
            ));
        return $list;
    }

    /**
     * 根据学年学期课号组号获取 对应的班级信息
     * @param $year
     * @param $term
     * @param $coursegroupno
     * @return array|string
     */
    public function getClassesnameByCourseno($year,$term,$coursegroupno){
        $sql = "
select  distinct
dbo.GROUP_CONCAT(RTRIM(classes.classname),',') AS classname
from courseplan
inner join classes on classes.classno=courseplan.classno
where
COURSEPLAN.YEAR= :year
AND COURSEPLAN.TERM= :term
and courseplan.courseno+courseplan.[group]=:coursegroupno";
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroupno'    => $coursegroupno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }

    /**
     * 根据班级号获取班级信息
     * @param $classno
     * @return array|int|string
     */
    public function getClassInfoByClassno($classno){
        return $this->getTable('CLASSES',array(
            'classno'   => $classno,
        ));
    }


    public function listClasses($classno='%',$classname='%',$school='%',$year='%',$limit=null,$offset=null){
        $fields = '
CLASSES.CLASSNO,
RTRIM(CLASSES.CLASSNAME) as CLASSNAME,
CLASSES.SCHOOL,
RTRIM(SCHOOLS.NAME) as SCHOOLNAME,
CLASSES.CHARGE_TEACHERNO,
rtrim(teachers.TEACHERNO) + \' / \' + rtrim(teachers.NAME) As CHARGE_TEACHERNAME,
CLASSES.STUDENTS, -- 预设班级人数
CONVERT(VARCHAR(10),CLASSES.[YEAR],20) as [YEAR],
CLASSES.REMARK,
MYCOUNT.COUNTS AS COUNTS -- 实际学生数目';
        $join = '
LEFT OUTER JOIN SCHOOLS on SCHOOLS.SCHOOL = CLASSES.SCHOOL
LEFT OUTER JOIN TEACHERS on CLASSES.CHARGE_TEACHERNO = TEACHERS.TEACHERNO
left outer join
(
    select CLASSES.CLASSNO AS CLASSNO,Count(students.studentNo) As COUNTS
    from CLASSES
    INNER JOIN STUDENTS ON CLASSES.classno=STUDENTS.classno
    GROUP BY CLASSES.CLASSNO
) AS MYCOUNT ON CLASSES.CLASSNO=MYCOUNT.CLASSNO';
        $where = '
Classes.ClassNo like :classno
and Classes.ClassName like :classname
and Classes.School like :school
and CAST(YEAR(Classes.YEAR) AS CHAR(4)) LIKE :year';
        $order = 'CLASSES.CLASSNO,CLASSES.SCHOOL';
        return $this->getTableList(
            $this->makeCountSql('CLASSES',array(
                'join'  => $join,
                'where' => $where,
            )),
            $this->makeSql('CLASSES',array(
                'fields'    => $fields,
                'join'  => $join,
                'where' => $where,
                'order' => $order,
            ),$limit,$offset),
            array(
                ':classno'  => $classno,
                ':classname'  => $classname,
                ':school'  => $school,
                ':year'  => $year,
            )
        );

    }


}