<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/13
 * Time: 9:05
 */

/**
 * 排课计划表
 * Class SchedulePlanModel
 */
class SchedulePlanModel extends CommonModel{


    /**
     * 减少开课计划人数
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @param int $num
     * @return int|string
     */
    public function decreaseSchedulePlanAttendents($year,$term,$courseno,$groupno,$num=1){
        $sql = 'UPDATE SCHEDULEPLAN SET ATTENDENTS=ATTENDENTS-'.intval($num).' WHERE YEAR=:YEAR AND TERM=:TERM AND COURSENO=:COURSENO AND [GROUP]=:GROUP';
        $bind = array(
            ':YEAR' => $year,
            ':TERM' => $term,
            ':COURSENO' =>$courseno,
            ':GROUP' => $groupno,
        );
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
        return $rst;
    }
    /**
     * 减少开课计划人数
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @param int $num
     * @return int|string
     */
    public function increaseSchedulePlanAttendents($year,$term,$courseno,$groupno,$num=1){
        $sql = 'UPDATE SCHEDULEPLAN SET ATTENDENTS=ATTENDENTS+'.intval($num).' WHERE YEAR=:YEAR AND TERM=:TERM AND COURSENO=:COURSENO AND [GROUP]=:GROUP';
        $bind = array(
            ':YEAR' => $year,
            ':TERM' => $term,
            ':COURSENO' =>$courseno,
            ':GROUP' => $groupno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }


    /**
     * 获取schedulePlan记录
     * @param $whr
     * @return array|string
     */
    public function getSchedulePlan($whr){
        return $this->getTable('SCHEDULEPLAN',$whr);
    }

    /**
     * 获取学生所在的班级中已选某一门课程的人数
     * @param $year
     * @param $term
     * @param $courseno
     * @param $group
     * @param $studentno
     * @return int|string 返回string类型表示错误信息 返回int类型表示人数
     */
    public function getAttendentsOfClassByStudentno($year,$term,$courseno,$group,$studentno){
        $sql = '
SELECT COUNT(*) as c FROM R32
INNER JOIN STUDENTS on STUDENTS.STUDENTNO = R32.STUDENTNO
WHERE [YEAR] = :year and term= :term and COURSENO = :courseno and [group] = :group and STUDENTS.CLASSNO = (
SELECT STUDENTS.CLASSNO from STUDENTS WHERE STUDENTNO = :studentno)';
        $rst = $this->doneQuery($this->sqlQuery($sql,array(
            ':year' => $year,
            ':term' => $term,
            ':courseno' => $courseno,
            ':group'    => $group,
            ':studentno'    => $studentno,
        )),false);
        if(is_string($rst)){
            return $rst;
        }
        return intval($rst['c']);
    }

    /**
     * 获取指定学期某课好组号的已选人数
     * @param $year
     * @param $term
     * @param $courseno
     * @param $group
     * @return array|int|string
     */
    public function getAttendentsOfClassByCoursegroup($year,$term,$courseno,$group){
        $sql = 'SELECT COUNT(*) as c FROM R32 where [YEAR] = :year and TERM = :term and COURSENO = :courseno and [GROUP]  = :group';
        $rst = $this->doneQuery($this->sqlQuery($sql,array(
            ':year' => $year,
            ':term' => $term,
            ':courseno' => $courseno,
            ':group'    => $group,
        )),false);
        if(is_string($rst)){
            return $rst;
        }
        return intval($rst['c']);
    }


    /**
     * 更新排课计划实际人数
     * 实际人数从学生修课计划表R32中获取
     * @param $year
     * @param $term
     * @param mixed $coursetypeoption 课程类型一
     * @param bool $exclusion 参数三表示的类型是包含在内韩式排除在外
     * @return int|string
     */
    public function updateAttendent($year,$term,$coursetypeoption=NULL,$exclusion=true){
        $sql = '
UPDATE SCHEDULEPLAN SET ATTENDENTS=ISNULL(SELECTION.ATTENDENTS,0) --如果ATTENDENTS为null则返回0
FROM SCHEDULEPLAN
LEFT OUTER JOIN
(
	SELECT
	  R32.YEAR,
	  R32.TERM,
	  R32.COURSENO,
	  R32.[GROUP],
	  COUNT(*) AS ATTENDENTS
	FROM R32
	GROUP BY R32.YEAR,R32.TERM,R32.COURSENO,R32.[GROUP]
) AS SELECTION
ON SCHEDULEPLAN.YEAR=SELECTION.YEAR
AND SCHEDULEPLAN.TERM=SELECTION.TERM
AND SCHEDULEPLAN.COURSENO=SELECTION.COURSENO
AND SCHEDULEPLAN.[GROUP]=SELECTION.[GROUP]
INNER JOIN COURSEPLAN
  on SCHEDULEPLAN.[YEAR] = COURSEPLAN.[YEAR]
  and SCHEDULEPLAN.TERM = COURSEPLAN.TERM
  and SCHEDULEPLAN.COURSENO = COURSEPLAN.COURSENO
WHERE SCHEDULEPLAN.YEAR=:YEAR AND SCHEDULEPLAN.TERM=:TERM';
        $bind = array(
            ':YEAR' => $year,
            ':TERM' => $term,
        );
        if(NULL !== $coursetypeoption){
            if($exclusion){
                $sql .= ' AND COURSEPLAN.course_type_options = :course_type_options';
            }else{
                $sql .= ' AND COURSEPLAN.course_type_options != :course_type_options';
            }
            $bind[':course_type_options'] = $coursetypeoption;
        }
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
        return $rst;
    }

    /**
     * 选课开放与选课禁止
     * @param $year
     * @param $term
     * @param $courseno
     * @param bool $lock true选课禁止 false表示开放
     * @return int|string
     */
    public function gateSelectCourse($year,$term,$courseno,$lock=true){
        $lock = $lock?1:0;
        $sql = "
UPDATE SCHEDULEPLAN SET LOCK={$lock}
WHERE YEAR=:YEAR
AND TERM=:TERM
AND RTRIM(COURSENO)+RTRIM([GROUP]) LIKE :COURSENO";
        $bind = array(
            ':YEAR' => $year,
            ':TERM' => $term,
            ':COURSENO' => $courseno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 删除排课计划
     * @param $whr
     * @return int|string
     */
    public function deleteSchedulePlan($whr){
        return $this->deleteRecords('SCHEDULEPLAN',$whr);
    }



}