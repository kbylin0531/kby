<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/3
 * Time: 9:44
 */

class ScheduleModel extends CommonModel{


    public function deleteSchedule($map){
    	$this->deleteRecords('TIMELIST',$map);
        return $this->deleteRecords('SCHEDULE',$map);
    }

    /**
     * 获取某学年学期的课号组号类似什么什么的课号组号
     * @param $year
     * @param $term
     * @param $coursegroupno
     * @return array|string
     */
    public function getScheduleCoursegroupnoLike($year,$term,$coursegroupno){
        $sql = 'SELECT DISTINCT vs.COURSENOGROUP as coursegroupno from VIEWSCHEDULE vs
WHERE vs.COURSENOGROUP LIKE :coursegroupno and vs.[YEAR] = :year and vs.TERM = :term';
        $bind = array(
            ':coursegroupno'    => $coursegroupno,
            ':year'             => $year,
            ':term'             => $term,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    public function getScheduleExcelList($bind,$filter){
        $fields = '
SCHEDULE.COURSENO+SCHEDULE.[GROUP] AS COURSENO,
RTRIM(COURSES.COURSENAME) COURSENAME,
SCHEDULE.DAY AS DAY,
RTRIM(TIMESECTORS.VALUE) AS TIME,
RTRIM(TEACHERS.NAME) AS TEACHERNAME,
RTRIM(CLASSROOMS.JSN) AS ROOMNAM,
RTRIM(COURSES.[课程介绍]) as REM';
        $join = '
left JOIN COURSEPLAN ON (SCHEDULE.COURSENO=COURSEPLAN.COURSENO AND SCHEDULE.[GROUP]=COURSEPLAN.[GROUP] AND SCHEDULE.YEAR=COURSEPLAN.YEAR AND SCHEDULE.TERM=COURSEPLAN.TERM)
left JOIN TEACHERPLAN ON(SCHEDULE.MAP=TEACHERPLAN.RECNO)
left JOIN COURSES ON (SCHEDULE.COURSENO=COURSES.COURSENO)
left JOIN CLASSES ON (COURSEPLAN.CLASSNO=CLASSES.CLASSNO)
left JOIN TEACHERS ON (TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO)
left JOIN SCHOOLS ON (COURSES.SCHOOL=SCHOOLS.SCHOOL)
left JOIN TIMESECTORS ON (SCHEDULE.TIME=TIMESECTORS.NAME)
left JOIN OEWOPTIONS ON (SCHEDULE.OEW=OEWOPTIONS.CODE)

left JOIN ROOMOPTIONS ON (SCHEDULE.ROOMR=ROOMOPTIONS.NAME)
left JOIN TASKOPTIONS ON (SCHEDULE.LE=TASKOPTIONS.CODE)
left join COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=courses.type2
left JOIN CLASSROOMS ON (SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO)
left join scheduleplan on scheduleplan.year=schedule.year and scheduleplan.term=schedule.term and scheduleplan.courseno+scheduleplan.[group]=schedule.courseno+schedule.[group]';
        $where = "
WHERE 
CLASSES.school like :schoolno and 
SCHEDULE.YEAR=:YEAR
AND SCHEDULE.TERM=:TERM
AND SCHEDULE.COURSENO LIKE :COURSENO
AND SCHEDULE.[GROUP] LIKE :GROUP
AND COURSEPLAN.CLASSNO LIKE :CLASSNO
AND COURSEPLAN.COURSETYPE LIKE :APPROACHES
AND COURSEPLAN.EXAMTYPE LIKE :EXAMTYPE
AND SCHEDULE.ROOMR LIKE :ROOMR
AND SCHEDULE.UNIT LIKE :UNIT
AND SCHEDULE.DAY LIKE :DAY
AND ( SCHEDULE.TIME LIKE :TIME )
AND SCHEDULE.ROOMNO LIKE :ROOMNO
AND CLASSES.CLASSNAME LIKE :CLASSNAME
AND TEACHERS.TEACHERNO LIKE :TEACHERNO
AND TEACHERS.NAME LIKE :TEACHERNAME
AND COURSES.COURSENAME LIKE :COURSENAME
AND COURSES.SCHOOL LIKE :SCHOOL
AND COURSEPLAN.course_type_options LIKE :COURSETYPE {$filter}";
        $group = '
SCHEDULE.COURSENO+SCHEDULE.[GROUP] ,
COURSES.COURSENAME,
SCHEDULE.DAY,
TIMESECTORS.VALUE,
TEACHERS.NAME,
CLASSROOMS.JSN,
COURSES.[�γ̽���]';
        $ssql = $this->makeSql('SCHEDULE',array(
            'distinct'=> true,
            'fields'    => $fields,
            'join'  => $join,
            'where' => $where,
            'group'=>$group,
        ));
//        vardump(iconv('gb2312','utf-8',$ssql),$bind);
        return $this->doneQuery($this->sqlQuery(iconv('gb2312','utf-8',$ssql),$bind));
    }

    public function getScheuleTablelist($bind,$filter,$offset,$limit){
        $fields = "
SCHEDULE.COURSENO+SCHEDULE.[GROUP] AS COURSENO,
RTRIM(COURSES.COURSENAME) COURSENAME,
SCHEDULE.DAY AS DAY,
RTRIM(TIMESECTORS.VALUE) AS TIME,
RTRIM(TEACHERS.NAME) AS TEACHERNAME,
RTRIM(CLASSROOMS.JSN) AS ROOMNAME,
RTRIM(TASKOPTIONS.NAME) AS TASKNAME,
TEACHERPLAN.HOURS AS HOURS,
RTRIM(OEWOPTIONS.NAME) AS OEWNAME,
COURSEPLAN.WEEKS AS WEEKS,
RTRIM(CLASSES.CLASSNAME)+'('+CLASSES.CLASSNO+')' AS CLASSNAME,
RTRIM(SCHOOLS.NAME) AS SCHOOLNAME,
scheduleplan.attendents AS ATTENDENTS,
COURSETYPEOPTIONS2.value COURSETYPE2,
SCHEDULE.RECNO";
        $join = '
INNER JOIN SCHEDULEPLAN sp ON sp.[YEAR] = SCHEDULE.[YEAR] and sp.TERM = SCHEDULE.TERM and sp.COURSENO = SCHEDULE.COURSENO and sp.[GROUP] = SCHEDULE.[GROUP]
left JOIN TEACHERPLAN ON(sp.recno=TEACHERPLAN.MAP)
left JOIN COURSEPLAN ON (SCHEDULE.COURSENO=COURSEPLAN.COURSENO AND SCHEDULE.[GROUP]=COURSEPLAN.[GROUP] AND SCHEDULE.YEAR=COURSEPLAN.YEAR AND SCHEDULE.TERM=COURSEPLAN.TERM)
left JOIN COURSES ON (SCHEDULE.COURSENO=COURSES.COURSENO)
left JOIN CLASSES ON (COURSEPLAN.CLASSNO=CLASSES.CLASSNO)
left JOIN TEACHERS ON (TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO)
left JOIN SCHOOLS ON (COURSES.SCHOOL=SCHOOLS.SCHOOL)
left JOIN TIMESECTORS ON (SCHEDULE.TIME=TIMESECTORS.NAME)
left JOIN OEWOPTIONS ON (SCHEDULE.OEW=OEWOPTIONS.CODE)

left JOIN ROOMOPTIONS ON (SCHEDULE.ROOMR=ROOMOPTIONS.NAME)
left JOIN TASKOPTIONS ON (SCHEDULE.LE=TASKOPTIONS.CODE)
left join COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=courses.type2
left JOIN CLASSROOMS ON (SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO)
left join scheduleplan on scheduleplan.year=schedule.year and scheduleplan.term=schedule.term and scheduleplan.courseno+scheduleplan.[group]=schedule.courseno+schedule.[group]';
        $where = "
WHERE 
CLASSES.school like :schoolno and 
SCHEDULE.YEAR=:YEAR
AND SCHEDULE.TERM=:TERM
AND SCHEDULE.COURSENO LIKE :COURSENO
AND SCHEDULE.[GROUP] LIKE :GROUP
AND COURSEPLAN.CLASSNO LIKE :CLASSNO
AND COURSEPLAN.COURSETYPE LIKE :APPROACHES
AND COURSEPLAN.EXAMTYPE LIKE :EXAMTYPE
AND SCHEDULE.ROOMR LIKE :ROOMR
AND SCHEDULE.UNIT LIKE :UNIT
AND SCHEDULE.DAY LIKE :DAY
AND SCHEDULE.TIME LIKE :TIME
AND SCHEDULE.ROOMNO LIKE :ROOMNO
AND CLASSES.CLASSNAME LIKE :CLASSNAME
AND TEACHERS.TEACHERNO LIKE :TEACHERNO
AND TEACHERS.NAME LIKE :TEACHERNAME
AND COURSES.COURSENAME LIKE :COURSENAME
AND COURSES.SCHOOL LIKE :SCHOOL
AND COURSEPLAN.course_type_options  like :COURSETYPE {$filter}";
        $csql = $this->makeCountSql('SCHEDULE',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('SCHEDULE',array(
            'fields'    => $fields,
            'join'  => $join,
            'where' => $where,
        ),$offset,$limit);
        $rst = $this->getTableList($csql,$ssql,$bind);
//        mist($rst,$csql,$ssql,$bind);
        return $rst;
    }


}