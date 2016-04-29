<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/18
 * Time: 9:41
 */
class StudentModel extends CommonModel{

    /**
     * 查询学生面板信息
     * @param $studentno
     * @return array|string
     */
    public function getStudentPanelInfo($studentno){
        $sql = '
SELECT
STUDENTS.STUDENTNO,
STUDENTS.NAME,
SEXCODE.NAME AS SEX,
STUDENTS.SEX AS SEXCODE,
CONVERT(varchar(10) , STUDENTS.ENTERDATE, 120 ) as ENTERDATE,
STUDENTS.YEARS,
STUDENTS.CLASSNO,
STUDENTS.TAKEN,
STUDENTS.PASSED,
STUDENTS.POINTS,
STUDENTS.REG,
STUDENTS.WARN,
STUDENTS.STATUS,
STUDENTS.CONTACT,
STUDENTS.GRADE,
STUDENTS.SCHOOL,
PERSONAL.MAJOR,
PERSONAL.BIRTHDAY,
personal.PHOTO,
CLASSES.CLASSNAME,
PERSONAL.CLASS,
SCHOOLS.NAME AS SCHOOLNAME,
STATUSOPTIONS.VALUE AS STATUSVALUE
FROM STUDENTS INNER JOIN PERSONAL ON PERSONAL.STUDENTNO=STUDENTS.STUDENTNO
LEFT OUTER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN STATUSOPTIONS ON STUDENTS.STATUS=STATUSOPTIONS.NAME
LEFT OUTER JOIN SEXCODE ON STUDENTS.SEX=SEXCODE.CODE
where RTRIM(STUDENTS.StudentNo) =:STUDENTNO';
        $bind = array(
            ':STUDENTNO'    => $studentno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }

    /**
     * 获取学生注册信息
     * @param $studentno
     * @return array|string
     */
    public function getStudentRegisteData($studentno){
        $sql = '
SELECT
REGDATA.YEAR,
REGDATA.TERM,
REGDATA.REGDATE,
REGDATA.REGCODE,
REGCODEOPTIONS.VALUE AS REGVALUE
FROM REGDATA
LEFT OUTER JOIN REGCODEOPTIONS
ON REGDATA.REGCODE=REGCODEOPTIONS.NAME
WHERE REGDATA.STUDENTNO=:STUDENTNO';
        $bind = array(
            ':STUDENTNO'    => $studentno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }


    /**
     * 获取学生等级考试成绩列表数据
     * @param $studentno
     * @param $offset
     * @param $limit
     * @return array|int|string
     */
    public function getStudentLevelResultTableList($studentno,$offset,$limit){
        $where = 'where STUDENTNO = :STUDENTNO';
        $order = 'TYPE,[YEAR] DESC,[MONTH] DESC';
        $csql = $this->makeCountSql('VIEW_LEVELEXAM',array(
            'where' => $where
        ));
        $ssql = $this->makeSql('VIEW_LEVELEXAM',array(
            'where' => $where,
            'order' => $order,
        ),$offset,$limit);
        $bind = array(
            ':STUDENTNO'    => $studentno
        );
        vardump($csql,$ssql,$bind);
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 获取学生列表
     * @param string $grade
     * @param string $school
     * @param string $classno
     * @param string $studentno
     * @param null|int $offset
     * @param
     * @return array
     */
    public function listStudents($grade='%',$school='%',$classno='%',$studentno='%',$offset=null,$limit=null){
        $fields = '
YEAR(STUDENTS.ENTERDATE) as grade,
RTRIM(SCHOOLS.NAME)  as schoolname,
RTRIM(CLASSES.CLASSNAME) as classname,
STUDENTS.STUDENTNO as studentno,
RTRIM(STUDENTS.NAME) as studentname';
        $join = '
INNER JOIN CLASSES on CLASSES.CLASSNO = STUDENTS.CLASSNO
INNER JOIN SCHOOLS on CLASSES.SCHOOL = SCHOOLS.SCHOOL';
        $where = '
YEAR(STUDENTS.ENTERDATE) = :grade
and CLASSES.SCHOOL like :school
and CLASSES.CLASSNO like :classno
and STUDENTS.STUDENTNO like :studentno';
        return $this->getTableList($this->makeCountSql('STUDENTS',array(
            'join'      => $join,
            'where'     => $where,
        )),
            $this->makeSql('STUDENTS',array(
                'fields'    => $fields,
                'join'      => $join,
                'where'     => $where,
            ),$offset,$limit),array(
                ':grade'    => $grade,
                ':school'   => $school,
                ':classno'  => $classno,
                ':studentno'=> $studentno,
            ));
    }


}