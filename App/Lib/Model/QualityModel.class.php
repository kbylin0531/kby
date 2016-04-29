<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/4
 * Time: 9:34
 */

class QualityModel extends CommonModel{


    /**
     * 根据学年学期创建学期考评条目总表
     *
     * 将教师工作计划表内链排课计划表得到教师的工作计划条目评价记录
     *
     * @param $year
     * @param $term
     * @return int|string
     */
    public function createEvaluationTableByYearTerm($year,$term){
        //这段SQL由城市学院方仁富编写
        $sql = "declare @year char(4)
                declare @term char(1)
                set @year=:year
                set @term=:term
                insert into 教学质量评估综合(teacherno,courseno,task,year,term)
                select distinct teacherno,courseno,task,year,term from (
                select teacherplan.teacherno,scheduleplan.courseno+scheduleplan.[GROUP] courseno,case courses.type2
                when 'A' then 'K'
                when 'B' then 'S'
                when 'C' then 'C'
                else 'B' end as task,
                scheduleplan.year,scheduleplan.term
                 from teacherplan inner join scheduleplan on scheduleplan.recno=teacherplan.map
                inner join  courses on courses.courseno=scheduleplan.courseno
                where scheduleplan.year=@year and scheduleplan.term=@term and teacherplan.teacherno!='000000') as temp
                where not exists (
                    select * from 教学质量评估综合 as temp2
                    where temp2.year=@year and temp2.term=@term and temp.teacherno=temp2.teacherno
                    and temp.courseno=temp2.courseno)";
        $bind = array(':year'=>$year,':term'=>$term);
        $rst = $this->sqlExecute($sql,$bind);
        return $this->doneExecute($rst);
    }

    /**
     * 清除指定学年学期的考评条目总表
     * @param array $whr 筛选条件，遵循[CommonModel/makeSegments]的连接准则
     * @return int|string
     */
    public function clearEvaluationTable($whr){
        return $this->deleteRecords('教学质量评估综合',$whr);
    }

    /**
     * 创建考评条目
     * @param $map
     * @return int|string
     */
    public function createEvaluationItem($map){
        return $this->createRecord('教学质量评估综合',$map);
    }


    public function getEvaluationTableList($bind,$offset,$limit){
        $fields = '
TEACHERS.NAME AS TEACHERNAME,
temp.TEACHERNO AS TEACHERNO,
temp.COURSENO AS COURSENO,
COURSES.COURSENAME AS COURSENAME,
temp.TASK ,
YEAR,
TERM,
RECNO,
TEMP.ENABLED,
temp.lock';
        $join = '
INNER JOIN TEACHERS ON TEACHERS.TEACHERNO=temp.TEACHERNO
INNER JOIN COURSES ON COURSES.COURSENO=SUBSTRING(temp.COURSENO,1,7)';
        $where = '
where  YEAR=:YEAR AND TERM=:TERM AND TEMP.COURSENO LIKE :COURSENO AND COURSENAME LIKE :COURSENAME AND TASK LIKE :TASK
AND TEACHERS.NAME LIKE :TEACHERNAME AND TEMP.ENABLED LIKE :ENABLED';
        $order = 'TEMP.COURSENO';
        $csql = $this->makeCountSql('教学质量评估综合 as temp ',array(
            'join' => $join,
            'where' => $where
        ));
        $ssql = $this->makeSql('教学质量评估综合 as temp ',array(
            'fields' => $fields,
            'join' => $join,
            'where' => $where,
            'order' => $order
        ),$offset,$limit);
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 判断是否还有考评未完成，未完成无法进行选课
     * @param $studentno
     * @return string
     */
    public function hasQualityEvaluationUnfinished($studentno){
        $sql = '
select count(*) as lock
from 教学质量评估详细 inner join 教学质量评估综合 on 教学质量评估综合.recno=教学质量评估详细.map
where Compelete =0 and 教学质量评估详细.studentno=:studentno and 教学质量评估综合.enabled=1
group by 教学质量评估详细.studentno';
        $bind = array(
            ':studentno'    => $studentno
        );
        $rst = $this->sqlQuery($sql,$bind);
        if(false === $rst){
            return $this->getErrorMessage();
        }elseif(!$rst){
            return 'Empty Result!';
        }
        return $rst[0]['lock'];
    }


}