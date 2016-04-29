<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/24
 * Time: 11:15
 */
class ClassModel extends CommonModel
{

    /**
     * 获取班级管理模块 班级绑定的教学计划列表
     * @param $classno
     * @return array|string
     */
    public function getClassProgramtableList($classno)
    {
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
        $where = 'R16.CLASSNO=:classno';
        $order = 'R16.PROGRAMNO';

        $csql = $this->makeCountSql('R16', array(
            'join' => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('R16', array(
            'fields' => $fields,
            'join' => $join,
            'where' => $where,
            'order' => $order,
        ));
        $bind = array(
            ':classno' => $classno,
        );
        return $this->getTableList($csql, $ssql, $bind);
    }


    public function listClasesStudentForDistributary($classno,$year,$term)
    {
        $fields = "
st.STUDENTNO as studentno,
RTRIM(st.NAME) as studentname,
RTRIM(st.CLASSNO) as cur_classno,
RTRIM(CLASSES.CLASSNAME) as cur_classname,
dbo.getone(ISNULL(cscc.origin_classno, '')) as origin_classno,
dbo.getone(ISNULL(cscc.origin_classname, '')) as origin_classname,
dbo.getone(ISNULL(cscc.year, '')) as year,
dbo.getone(ISNULL(cscc.term, '')) as term,
dbo.getone(ISNULL(CONVERT(VARCHAR(10),cscc.change_date,20),'')) as change_date ";
        $join = '
INNER JOIN CLASSES ON CLASSES.CLASSNO = st.CLASSNO
LEFT OUTER JOIN cwebs_student_class_change cscc on cscc.studentno = st.STUDENTNO and cscc.year = :year and cscc.term = :term';
        $where = 'st.CLASSNO = :classno';
        $group = 'st.STUDENTNO ,st.NAME,st.CLASSNO,CLASSES.CLASSNAME';
        $order = 'change_date desc';
        $csql = $this->makeCountSql('STUDENTS st', array(
            'join' => $join,
            'where' => $where,
            'group'  => $group,
        ));
        $ssql = $this->makeSql('STUDENTS st', array(
            'fields' => $fields,
            'join' => $join,
            'where' => $where,
            'group'  => $group,
            'order'  => $$order,
        ));
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':classno' => $classno,
        );
        $list =  $this->getTableList($csql, $ssql, $bind);
        /*
                varsdumpout(
        $list,$csql, $ssql, $bind
                    );
        */
        return $list;
    }

    /**
     * 学生分流到新的班级
     * @param array $student 当前学生信息数组
     * @param string $newclassno 即将转的班级号
     * @param int $year
     * @param int $term
     * @return int|string
     */
    public function distriStudentClass($student, $newclassno,$year,$term)
    {
        if($student['cur_classno'] === $newclassno){
            return "无法修改到同一个班级! ";
        }
        $this->startTrans();
        //先删除原有记录
        $studentno = $student['studentno'];
//        $delsql = 'DELETE FROM cwebs_student_class_change WHERE studentno = :studentno ';
//        $bind = array(':studentno' => $studentno);
//        $rst = $this->doneExecute($this->sqlExecute($delsql, $bind));
//        if (is_string($rst)) {
//            return $rst;
//        }

        //更新学生班级
        $updsql = 'UPDATE STUDENTS SET CLASSNO = :newclassno WHERE STUDENTNO = :studentno';
        $bind = array(
            ':newclassno'   => $newclassno,
            ':studentno'    => $studentno,
        );
        $rst = $this->doneExecute($this->sqlExecute($updsql, $bind));
        if (is_string($rst) or !$rst) {
            return $rst;
        }

        //插入新的纪录
        $inssql = 'INSERT INTO  cwebs_student_class_change (studentno,origin_classno,origin_classname,classno,year,term)
                    VALUES (:studentno,:origin_classno,:origin_classname,:classno,:year,:term)';
        $bind = array(
            ':studentno' => $studentno,
            ':origin_classno' => $student['cur_classno'],
            ':origin_classname' => $student['cur_classname'],
            ':classno' => $newclassno,
            ':year' => $year,
            ':term' => $term,
        );
        $rst = $this->doneExecute($this->sqlExecute($inssql, $bind));
        if(is_string($rst) or !$rst){
            return $rst;
        }
        /*
                //删除该学生本学期选课（包含必修课）
                $dsql = 'delete from R32 WHERE [YEAR] = :year and TERM =:term and STUDENTNO = :studentno;';
                $rst = $this->doneExecute($this->sqlExecute($dsql,array(
                    ':year' => $year,
                    ':term' => $term,
                    ':studentno' => $studentno,
                )));
                if(is_string($rst)){ //可能未选课而为0
                    return $rst;
                }*/
        $this->commit();
        return $rst;
    }

    public function listClassStudents($classno,$offset=null,$limit=null){

    }


}