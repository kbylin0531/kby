<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/18
 * Time: 11:12
 */
class StudentExamModel extends CommonModel{

    /**
     * 获取学生端我的学业列表数据
     * @param $year
     * @param $term
     * @param $studentno
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getStudentFinalExamtableList($year,$term,$studentno,$offset,$limit){
        $fields = "
TESTPLAN.COURSeNO AS COURSENO ,
COURSES.COURSENAME ,
testplan.flag ,
TESTBATCH.TESTTIME ,
TESTPLAN.ATTENDENTS ,
TESTPLAN.ROOMNO1 ,
TESTPLAN.rs1 ,
TESTPLAN.ROOMNO2 ,
TESTPLAN.ROOMNO3,
TESTPLAN.rs2 ,
R15,
'' AS SL,
'' AS KC2,
'' AS KC3";
        $join = '
INNER JOIN TESTPLAN ON R32.YEAR = TESTPLAN.year AND R32.TERM = TESTPLAN.term AND R32.COURSENO + R32.[GROUP] = TESTPLAN.COURSeNO
INNER JOIN TESTBATCH ON TESTPLAN.year = TESTBATCH.YEAR AND TESTPLAN.term = TESTBATCH.TERM AND TESTPLAN.FLAG = TESTBATCH.FLAG
INNER JOIN COURSES ON R32.COURSENO = COURSES.COURSENO';
        $where = '
WHERE R32.studentno =:STUDENTNO
  AND R32.year = :YEAR
  AND R32.term=:TERM';
        $order = 'testplan.flag';
        $csql = $this->makeCountSql('R32',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('R32',array(
            'fields'    => $fields,
            'join'  => $join,
            'where' => $where,
            'order' => $order,
        ),$offset,$limit);
        $bind = array(
            ':STUDENTNO'    => $studentno,
            ':YEAR'         => $year,
            ':TERM'         => $term,
        );
//        vardump($csql,$ssql,$bind);
        return $this->getTableList($csql,$ssql,$bind);
    }

}
