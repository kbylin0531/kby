<?php
/**
 * Email:784855684@qq.com.
 * User: Lin
 * Date: 2015/7/23
 * Time: 13:33
 */
class ProgramModel extends CommonModel{

    /**
     * 获取教学计划了列表
     * @param bool $all 是否加入全部的选项,创建教学计划的过程中一般设置为false
     * @return array
     */
    public function getProgramTypeList($all=true){
        $rtn = $this->getComboData('PROGRAMTYPE','NAME','VALUE');
        if($all){
            $rtn = array_merge(array(array('value'=>'%','text'=>'全部')),$rtn);
        }
        return is_string($rtn)?array():$rtn;
    }






    /**
     * 新建教学计划
     * @param string|null $values banding列的名称
     * @return bool|string
     */
    public function newProgram($values=NULL){
        if(NULL === $values){
            return $this->insertData('PROGRAMS');
        }elseif(is_array($values)){
//            PROGRAMNO,PROGNAME,SCHOOL,TYPE
            $bind = $this->getBind('PROGRAMNO,PROGNAME,SCHOOL,TYPE',$values);
            return $this->insertData('PROGRAMS',array(
                'columns'=>' PROGRAMNO,PROGNAME,DATE,VALID,SCHOOL,TYPE ',
                'values'=>':PROGRAMNO,:PROGNAME,GETDATE(),1,:SCHOOL,:TYPE',
                'bind'=>$bind,
            ));

        }
    }

    public function getProgram($whr){
        return $this->getTable('PROGRAMS',$whr,true);
    }

    public function deletePrograms($programno){
        $sql = 'DELETE PROGRAMS WHERE PROGRAMNO=:PROGRAMNO';
        $bind = array(
            ':PROGRAMNO'=>$programno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 获取教学计划下所有的修读课程或者数量
     * 从R12表中获取
     * @param string $programno
     * @param bool $count true表示只获取数量
     * @return integer|string|array
     */
    public function getProgramCourses($programno,$count=false){
        return $this->getFrom('R12',array(
            ':PROGRAMNO'=>$programno,
        ),$count);
    }


    /**
     * 查询教学计划下课程列表的课程数据
     * @param $programno
     * @return array|string
     */
    public function getProgramCoursesTableList($programno){
        $sql = "
select
R12.CourseNo As CourseNo,
Courses.CourseName As CourseName ,
R12.CREDITS As Credits,
Courses.Hours As Hours,
R12.PROGRAMNO as PROGRAMNO,
Courses.School As School,
Schools.Name As SchoolName,
EXAMOPTIONS.VALUE as ExamType,
R12.ExamType As ExamTypeCode,
COURSEAPPROACHES.VALUE as CourseType,
R12.CourseType As CourseTypeCode,
R12.Year as Year,
R12.Term as Term,
--默认选择学部统考
--TESTLEVEL.VALUE AS TESTVALUE,
R12.TEST as testlevel,
TESTLEVEL.[VALUE] as TESTVALUE,
-- (SELECT TESTLEVEL.[VALUE] from TESTLEVEL WHERE TESTLEVEL.NAME = 'S') AS TESTVALUE,
R12.Test As TestCode,
R12.Category As CategoryCode,
COURSETYPEOPTIONS.VALUE AS CATEGORYVALUE,
R12.Weeks As Weeks,
'0' AS UPDATED,
R12.LIMITGROUPNO,
R12.LIMITNUM,
R12.RECNO,
R12.LIMITCREDIT
from R12
INNER JOIN Courses ON R12.CourseNo=Courses.CourseNo
INNER JOIN Programs ON R12.ProgramNo=Programs.ProgramNo
INNER JOIN Schools ON Courses.School=Schools.School
INNER JOIN EXAMOPTIONS ON R12.EXAMTYPE=EXAMOPTIONS.NAME
INNER JOIN COURSEAPPROACHES  ON R12.COURSETYPE=COURSEAPPROACHES.NAME
LEFT OUTER JOIN COURSETYPEOPTIONS ON R12.CATEGORY=COURSETYPEOPTIONS.NAME
LEFT OUTER JOIN TESTLEVEL on R12.TEST = TESTLEVEL.NAME
Where R12.ProgramNo =:programno
ORDER BY year,term,Courseno";
        $bind = array(
            ':programno'=>$programno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    public function updateProgramCourse($updFields,$where){
        return $this->updateData('R12',$updFields,$where);
    }

    /**
     * 为教学计划添加课程
     * 该教学计划下的课程类别是课程的原始类别
     * @param $programno
     * @param $courseno
     * @return bool|string
     */
    public function addProgramCourse($programno,$courseno){
        $sql = '
insert INTO R12(ProgramNo,CourseNo,CATEGORY)
SELECT
:programno,
COURSENO ,
type
from COURSES
WHERE COURSENO = :courseno';
        $bind = array(
            ':programno'=>$programno,
            ':courseno'=>$courseno,
        );
        $rt = $this->doneExecute($this->sqlExecute($sql,$bind));
//        vardump($rt,$sql,$bind,$this->getDbError());
        return $rt;
    }

    /**
     * 获取课程类别
     * @param $bind
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getProgramCourseTableList($bind,$offset,$limit){
        $fields = '
courses.CourseNo,
courses.CourseName,
courses.Credits,
courses.School,
COURSES.TGROUP,
jyz.NAME AS tgroupname,
Schools.Name As SchoolName,
COURSETYPEOPTIONS.VALUE AS COURSETYPE,
COURSES.TYPE,
courses.REM';
        $join = '
LEFT OUTER JOIN TGROUPS jyz ON COURSES.TGROUP = jyz.TGROUP
INNER JOIN Schools ON COURSES.SCHOOL=SCHOOLS.SCHOOL
INNER JOIN COURSETYPEOPTIONS ON COURSES.TYPE=COURSETYPEOPTIONS.NAME';
        $where = '
where Courses.CourseNo like :COURSENO
and Courses.CourseName like :COURSENAME
and Courses.School like :SCHOOL
AND COURSETYPEOPTIONS.NAME LIKE :COURSETYPE
AND COURSES.TGROUP like :TGROUPNO';
        $csql = $this->makeSql('courses',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('courses',array(
            'fields'=>$fields,
            'where' =>$where,
            'join'  =>$join,
        ),$offset,$limit);

        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 拷贝就的教学计划的课程到新的教学计划下
     * @param $newProgramNo
     * @param $originProgramNo
     * @return bool|string
     */
    public function copyProgramCourses($newProgramNo,$originProgramNo){
        $sql = 'Insert into R12
            (PROGRAMNO,COURSENO,COURSETYPE,EXAMTYPE,TEST,CATEGORY,YEAR,TERM,WEEKS)
              SELECT
                :newporgramno,COURSENO,COURSETYPE,EXAMTYPE,TEST,CATEGORY,YEAR,TERM,WEEKS
                FROM R12 WHERE PROGRAMNO=:PROGRAMNO';
        $bind = array(
            ':newporgramno'=>$newProgramNo,
            ':PROGRAMNO'=>$originProgramNo,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 从教学计划中删除课程
     * @param $programno
     * @param $courseno
     * @return bool|string
     */
    public function deleteProgramCourse($programno,$courseno){
        $sql = 'DELETE R12 WHERE PROGRAMNO=:PROGRAMNO AND COURSENO=:COURSENO';
        $bind = array(
            ':PROGRAMNO'=>$programno,
            ':COURSENO'=>$courseno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }


    /**
     * 获取教学计划下所有的修读班级或者数量
     * 从R16表中获取
     * @param string $programno
     * @param bool $count true表示只获取数量
     * @return array|int|string
     */
    public function getProgramClasses($programno,$count=false){
        return $this->getFrom('R16',array(
            ':PROGRAMNO'=>$programno,
        ),$count);
    }

    public function getClassPrograms($classno,$offset,$limit){
        $fields = '
R16.CLASSNO AS CLASSNO,
R16.PROGRAMNO AS PROGRAMNO,
PROGRAMS.PROGNAME AS PROGRAMNAME,
PROGRAMS.REM AS REM,
PROGRAMS.TYPE AS TYPE,
PROGRAMTYPE.VALUE AS PROGRAMTYPEVALUE,
PROGRAMS.SCHOOL AS SCHOOL,
SCHOOLS.NAME AS SCHOOLNAME';
        $join = '
INNER JOIN PROGRAMS ON R16.PROGRAMNO=PROGRAMS.PROGRAMNO
INNER JOIN SCHOOLS ON PROGRAMS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN PROGRAMTYPE ON PROGRAMS.TYPE=PROGRAMTYPE.NAME';
        $where = 'WHERE R16.CLASSNO=:CLASSNO';
        $order = 'PROGRAMNO DESC';

        $csql = $this->makeSql('R16',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('R16',array(
            'fields'=>$fields,
            'where' =>$where,
            'join'  =>$join,
            'order' => $order,
        ),$offset,$limit);

        $bind = array(
            ':CLASSNO' => $classno,
        );
        return $this->getTableList($csql,$ssql,$bind);

    }

    public function addProgramClass($programno,$classno){
        $sql = 'INSERT INTO R16 (PROGRAMNO,CLASSNO) VALUES (:PROGRAMNO,:CLASSNO)';
        $bind = array(
            ':PROGRAMNO'=>$programno,
            ':CLASSNO'=>$classno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }



    /**
     * 获取教学计划的详细信息
     * @param $programno
     * @return array|NULL|string
     */
    public function _getProgramDetail($programno){
        $sql = '
SELECT Dbo_programs.PROGRAMNO,
Dbo_programs.PROGNAME,
Dbo_programs.DATE,
Dbo_programs.REM,
Dbo_programs.URL,
Dbo_programs.VALID,
Dbo_programs.SCHOOL,
Dbo_programs.TYPE,
Dbo_programs.MAJOR,
Dbo_schools.NAME As SchoolName,
PROGRAMTYPE.VALUE As TYPENAME,
lock
FROM dbo.PROGRAMS Dbo_programs
INNER JOIN dbo.SCHOOLS Dbo_schools on Dbo_programs.SCHOOL = Dbo_schools.SCHOOL
INNER JOIN PROGRAMTYPE on Dbo_programs.TYPE = PROGRAMTYPE.NAME
WHERE   Dbo_programs.PROGRAMNO = :PROGRAMNO';
        $bind = array(
            ':PROGRAMNO'=>$programno
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }

    /**
     * 获取教学计划下的班级显示列表
     * @param $programno
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getProgramClassTableList($programno,$offset,$limit){
        $fields = '
R16.ClassNo As ClassNo,
Classes.ClassName As ClassName,
Classes.School As School,
Classes.Grade As Grade,
Classes.REMARK As Remark,
R16.ProgramNo As ProgramNo,
Programs.ProgName As ProgramName,
Schools.Name As SchoolName';
        $join = '
INNER JOIN Classes on R16.ClassNo=Classes.ClassNo
INNER JOIN Programs on R16.ProgramNo=Programs.ProgramNo
INNER JOIN Schools on Classes.School=Schools.School';
        $where = 'where  R16.ProgramNo = :programno';
        $csql = $this->makeSql('R16',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('R16',array(
            'fields'=>$fields,
            'where' =>$where,
            'join'  =>$join,
        ),$offset,$limit);

        return $this->getTableList($csql,$ssql,array(
            ':programno'=>$programno
        ));
    }


    /**
     * 删除教学计划下的班级
     * @param $programno
     * @param $classno
     * @return bool|string
     */
    public function deleteClassesFromProgram($programno,$classno){
        $sql = 'DELETE R16 WHERE PROGRAMNO=:PROGRAMNO AND CLASSNO=:CLASSNO';
        $bind = array(
            ':PROGRAMNO'=>$programno,
            ':CLASSNO'=>$classno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 获取该教学计划下的学生列表或者学生数量
     * @param string $programno
     * @param bool $count true表示只获取数量
     * @return array|int|string
     */
    public function getProgramStudents($programno,$count=false){
        return $this->getFrom('R28',array(
            ':PROGRAMNO'=>$programno,
        ),$count);
    }

    /**
     * 获取教学计划下的学生列表
     * @param array $bind 查询参数绑定数组
     * @param integer $offset
     * @param integer $limit
     * @return array|string
     */
    public function getProgramStudentsTableList($bind,$offset,$limit){
        $fields = '
students.StudentNo As STUDENTNO,
students.Name As NAME,
Classes.ClassNo As CLASSNO,
Classes.ClassName as CLASSNAME,
STUDENTS.SCHOOL AS SCHOOL,
Schools.Name As SCHOOLNAME';
        $join = '
INNER JOIN R28 ON students.StudentNo=R28.StudentNo
INNER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
INNER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO';
        $where = ' where R28.ProgramNo=:programno ';

        $csql = $this->makeSql('students',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('students',array(
            'fields'=>$fields,
            'where' =>$where,
            'join'  =>$join,
        ),$offset,$limit);

        return $this->getTableList($csql,$ssql,$bind);
    }






    /**
     * 按照学号删除教学计划中的学生
     * @param $programno
     * @param $studentno
     * @return int|string
     */
    public function deleteProgramStduentByStudentno($programno,$studentno){
        $sql = 'delete from R28 where StudentNo=:STUDENTNO and ProgramNo=:PROGRAMNO';
        $bind = array(
            ':STUDENTNO'=>trim($studentno),
            ':PROGRAMNO'=>trim($programno),
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 按照学号将学生添加到教学计划中
     * @param $programno
     * @param $studentno
     * @return bool|string
     */
    public function insertProgramStudentByStudentno($programno,$studentno){
        $sql = 'INSERT INTO R28 (STUDENTNO,PROGRAMNO) VALUES (:STUDENTNO,:PROGRAMNO)';
        $bind = array(
            ':STUDENTNO'=>$studentno,
            ':PROGRAMNO'=>$programno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }


    /**
     * 删除教学计划下某个班级的所有学生
     * @param string $programno 教学计划号
     * @param string $classno 班级号
     * @return bool|string
     */
    public function deleteProgramStudentsByClassno($programno,$classno){
        $sql = 'delete from r28
from r28 inner join students on students.studentno=r28.studentno
where r28.programno=:D_PROGRAMNO AND students.CLASSNO=:D_CLASSNO';
        $bind = array(
            ':D_PROGRAMNO'=>$programno,
            ':D_CLASSNO'=>$classno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 按照班级号将学生添加到教学计划中
     * @param $programno
     * @param $classno
     * @return bool|string
     */
    public function insertProgramStudentsByClassno($programno,$classno){
        $sql = 'INSERT INTO R28
SELECT STUDENTS.STUDENTNO AS STUDENTNO,R16.PROGRAMNO AS PROGRAMNO
FROM CLASSES
JOIN STUDENTS ON CLASSES.CLASSNO=STUDENTS.CLASSNO
JOIN R16 ON CLASSES.CLASSNO=R16.CLASSNO
WHERE R16.PROGRAMNO=:I_PROGRAMNO
AND CLASSES.CLASSNO=:I_CLASSNO';
        $bind = array(
            ':I_PROGRAMNO'=>$programno,
            ':I_CLASSNO'=>$classno,
        );
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
//        vardump($rst);
        return $rst;
    }

    /**
     * 获取教学计划下所有的修读班级或者数量
     * @param $programno
     * @param bool $count
     * @return array|int|string
     */
    public function getProgramMinorClasses($programno,$count=false){
        return $this->getFrom('R7',array(
            ':PROGRAMNO'=>$programno,
        ),$count);
    }

    /**
     * 获取修读该教学计划下的专业方向
     * @param $programno
     * @param bool $count
     * @return array|int|string
     */
    public function getProgramMajorPlan($programno,$count=false){
        return $this->getTable('R30',array(
            'PROGNO'=>$programno,
        ),$count);
    }



    /**
     * 获取教学计划列表
     * @param $offset
     * @param $limit
     * @param null $bind
     * @return array|string
     */
    public function getProgramTableList($offset,$limit,$bind=NULL){
        if(NULL === $bind){
            $bind = $this->bindParameter(self::SEARCH_FEILD_PREFIX);
        }
        $fields = '
PROGRAMS.PROGRAMNO,
PROGRAMS.PROGNAME,
CONVERT(varchar(10) , PROGRAMS.DATE, 120 ) as DATE,
PROGRAMS.REM,
PROGRAMS.URL,
PROGRAMS.VALID,
PROGRAMS.SCHOOL,
PROGRAMS.TYPE,
SCHOOLS.NAME as SchoolName,
PROGRAMTYPE.VALUE As TYPENAME,
PROGRAMS.major';
        $join = '
inner JOIN PROGRAMTYPE ON PROGRAMS.TYPE = PROGRAMTYPE.NAME
inner JOIN SCHOOLS ON PROGRAMS.SCHOOL = SCHOOLS.SCHOOL';
        $where = '
WHERE (PROGRAMS.PROGRAMNO LIKE :programno)
AND  (PROGRAMS.PROGNAME LIKE :progname)
AND  (PROGRAMS.SCHOOL LIKE :school)';

        $csql = $this->makeSql('PROGRAMS',array(
            'fields'=>'count(*) As ROWS',
            'where'=>$where,
        ));
        $ssql = $this->makeSql('PROGRAMS',array(
            'fields'=>$fields,
            'where' =>$where,
            'join'  =>$join,
            'order' => 'PROGRAMNO DESC', // 逆序排列
        ),$offset,$limit);
        return $this->getTableList($csql,$ssql,$bind);
    }


    /**
     * 获取教学计划表中的原始数据
     * @param string $programno 教学计划号
     * @return string|integer 返回错误信息或者教学计划的原始数据
     */
    public function getPrograms($programno){
        return $this->getFrom('PROGRAMS',array(
            ':PROGRAMNO'=>$programno,
        ));
    }


    public function getClassTableList($bind,$offset,$limit){
        $fields = '
CLASSES.CLASSNO,
CLASSES.CLASSNAME,
CLASSES.SCHOOL,
SCHOOLS.NAME AS SCHOOLNAME ';
        $join = '
INNER JOIN SCHOOLS ON CLASSES.SCHOOL=SCHOOLS.SCHOOL ';
        $where = '
WHERE YEAR(CLASSES.YEAR) = :GRADE
AND CLASSES.CLASSNO LIKE :CLASSNO
AND CLASSES.CLASSNAME LIKE :CLASSNAME
AND CLASSES.SCHOOL LIKE :SCHOOL ';
        $order = ' SCHOOL,CLASSNO ';
        $csql = $this->makeSql('CLASSES',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('CLASSES',array(
            'fields'=>$fields,
            'join'=>$join,
            'where'=>$where,
            'order'=>$order,
        ),$offset,$limit);

        return $this->getTableList($csql,$ssql,$bind);
    }


    public function getClassDetail($classno){
        $rst = $this->getFrom('CLASSES',array(
            'classno'=>$classno,
        ));
        if(is_string($rst)){
            return $rst;
        }
        return !$rst?"Class [{$classno}] not found":$rst[0];
    }


    /**
     * 查询“学生修课计划”的学生详细信息列表
     * 区别于“getStudentsTableList” 后者查询的是教学计划下的学生列表
     * 可以将二者统一做到一起，前提是二者表格市局统一。。。。。
     * @param array $bind 查询参数绑定
     * @param integer $offset
     * @param integer $limit
     * @return string|integer 返回错误信息或者学生列表数据
     */
    public function getStudentsPlanTableList($bind ,$offset,$limit){
        $fields = '
STUDENTS.STUDENTNO,
STUDENTS.NAME,
SEXCODE.NAME AS SEX,
STUDENTS.ENTERDATE,
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
CLASSES.CLASSNAME,
SCHOOLS.NAME AS SCHOOLNAME,
STATUSOPTIONS.VALUE AS STATUSVALUE';
        $join = '
LEFT OUTER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN STATUSOPTIONS ON STUDENTS.STATUS=STATUSOPTIONS.NAME
LEFT OUTER JOIN SEXCODE ON STUDENTS.SEX=SEXCODE.CODE';
        $where = '
where RTRIM(STUDENTS.StudentNo) LIKE :STUDENTNO
and STUDENTS.Name like :NAME
and STUDENTS.ClassNo like :CLASSNO
and STUDENTS.School like :SCHOOL
and STUDENTS.Status like :STATUS';
        $order = 'STUDENTS.CLASSNO,STUDENTS.STUDENTNO';
        $csql = $this->makeSql('STUDENTS',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('STUDENTS',array(
            'fields'=>$fields,
            'join'=>$join,
            'where'=>$where,
            'order'=>$order,
        ),$offset,$limit);

        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 查询学生绑定的教学计划列表
     * @param string $studentno
     * @param integer $offset
     * @param integer $limit
     * @return array|string
     */
    public function getStudentProgramsTableList($studentno,$offset=NULL,$limit=NULL){
        $fields = '
R28.STUDENTNO AS STUDENTNO,
R28.PROGRAMNO AS PROGRAMNO,
PROGRAMS.PROGNAME AS PROGRAMNAME,
PROGRAMS.REM AS REM,
PROGRAMS.TYPE AS TYPE,
PROGRAMTYPE.VALUE AS PROGRAMTYPEVALUE,
PROGRAMS.SCHOOL AS SCHOOL,
SCHOOLS.NAME AS SCHOOLNAME';
        $join = '
INNER JOIN PROGRAMS ON R28.PROGRAMNO=PROGRAMS.PROGRAMNO
INNER JOIN SCHOOLS ON PROGRAMS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN PROGRAMTYPE ON PROGRAMS.TYPE=PROGRAMTYPE.NAME';
        $where = ' WHERE R28.STUDENTNO=:STUDENTNO ';
        $order = ' PROGRAMNO ';

        $csql = $this->makeSql('R28',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('R28',array(
            'fields'=>$fields,
            'join'=>$join,
            'where'=>$where,
            'order'=>$order,
        ),$offset,$limit);
        $bind = array(
            ':STUDENTNO'=>$studentno,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }























    /********************** 其他类别(待转移)  **************************************************/

    /**
     * 获取该学生在学生表中的原始数据信息
     * @param $studentno
     * @return array|int|string
     */
    public function getStudentInfo($studentno){
        $rst = $this->getFrom('STUDENTS',array(
            ':STUDENTNO'    =>  $studentno,
        ));
        return is_string($rst)?$rst:empty($rst)?"Student [{$studentno}] do not found !":$rst[0];
    }

    /**
     * 获取教学计划添加课程里的课程列表
     * @param array $condition 查询条件
     * @param integer $offset
     * @param integer $limit
     * @return array|string
     */
    public function getClassesTableList($condition,$offset,$limit){
        $fields ='
CLASSES.CLASSNO,
CLASSES.CLASSNAME,
CLASSES.SCHOOL,
SCHOOLS.NAME AS SCHOOLNAME';
        $join = '
JOIN SCHOOLS ON CLASSES.SCHOOL=SCHOOLS.SCHOOL';
        $where = '
WHERE CLASSES.CLASSNO LIKE :CLASSNO
AND CLASSES.CLASSNAME LIKE :CLASSNAME
AND CLASSES.SCHOOL LIKE :SCHOOL';
        $order = 'SCHOOL,CLASSNO';
        $csql = $this->makeSql('CLASSES',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('CLASSES',array(
            'fields'=>$fields,
            'where' =>$where,
            'join'  =>$join,
            'order'=>$order,
        ),$offset,$limit);

        return $this->getTableList($csql,$ssql,$condition);
    }

    /**
     * 教学计划下添加学生 的学生列表内容获取
     * 与方法getProgramStudentsTableList相区别的是，后者是在特定教学计划下的学生列表
     * @param $condition
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getStudentsTableList($condition,$offset,$limit){
        $fields = '
STUDENTS.STUDENTNO,
STUDENTS.NAME AS NAME,
STUDENTS.CLASSNO AS CLASSNO,
STUDENTS.SCHOOL AS SCHOOL,
SCHOOLS.NAME AS SCHOOLNAME,
CLASSES.CLASSNAME AS CLASSNAME';
        $join = '
INNER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
INNER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO';
        $where = '
WHERE STUDENTS.STUDENTNO LIKE :STUDENTNO
AND STUDENTS.NAME LIKE :NAME
AND STUDENTS.CLASSNO LIKE :CLASSNO
AND CLASSES.CLASSNAME LIKE :CLASSNAME
AND STUDENTS.SCHOOL LIKE :SCHOOL';
        $csql = $this->makeSql('STUDENTS',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
        ));
        $ssql = $this->makeSql('STUDENTS',array(
            'fields'=>$fields,
            'where' =>$where,
            'join'  =>$join,
        ),$offset,$limit);

        return $this->getTableList($csql,$ssql,$condition);
    }



    public function getProgramBySchoolno($schoolno,$offset,$limit){
        $fields = '
PROGRAMNO as CODE,
PROGRAMNAME as NAME,
CONVERT(varchar(10) , PROGRAMSDATE, 120 ) AS [DATE],
REM AS REM,
PROGRAMTYPENAME AS [TYPE],
SCHOOLNAME AS SCHOOLNAME';
        $where = 'WHERE SCHOOL=:SCHOOL';
        $csql = $this->makeCountSql('VIEW_PROGRAM',array(
            'where'     => $where,
        ));
        $ssql = $this->makeSql('VIEW_PROGRAM',array(
            'fields'    => $fields,
            'where'     => $where,
        ),$offset,$limit);
        $bind = array(
            ':SCHOOL'   => $schoolno,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }

    public function getProgramByStudentno($studentno,$offset,$limit){
        $fields = '
PROGRAMNO AS CODE,
PROGRAMNAME AS [NAME],
CONVERT(varchar(10) ,PROGRAMSDATE, 120 ) AS [DATE],
REM AS REM,
PROGRAMTYPENAME AS [TYPE],
SCHOOLNAME AS SCHOOLNAME';
        $where = 'WHERE STUDENTNO=:STUDENTNO';
        $csql = $this->makeCountSql('VIEW_R28',array(
            'where'     => $where,
        ));
        $ssql = $this->makeSql('VIEW_R28',array(
            'fields'    => $fields,
            'where'     => $where,
        ),$offset,$limit);
        $bind = array(
            ':STUDENTNO'   => $studentno,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }





}