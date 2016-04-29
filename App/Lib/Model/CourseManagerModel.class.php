<?php
/**
 * Email:784855684@qq.com.
 * User: Lin
 * Date: 2015/7/23
 * Time: 10:24
 */

class CourseManagerModel extends CommonModel{

    /**
     * 获取教学计划
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @return array|int|string
     */
    public function getSchedulePlan($year,$term,$courseno,$groupno){
        $rst = $this->getFrom('SCHEDULEPLAN',array(
            'YEAR'=>$year,
            'TERM'=>$term,
            'COURSENO'=>$courseno,
            '[GROUP]'=>$groupno,
        ));
        return is_array($rst)?$rst[0]:$rst;
    }

    public function createStudentCourseRecord($fields){
        return $this->createRecord('R32',$fields);
    }

    /**
     * 获取开课计划中的一条记录
     * 主键组合缺少classno
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @return array|int|string 由于couseplan表的主键还有一个classno，所以可能会有多条记录，故全部返回
     */
    public function getCoursePlan($year,$term,$courseno,$groupno){
        return $this->getTable('COURSEPLAN',array(
            'YEAR' =>$year,
            'TERM' =>$term,
            'COURSENO' =>$courseno,
            'GROUP' =>array($groupno,true),
        ));
    }

    /**
     * 修改学生所在班级的选课人数
     * @param $year
     * @param $term
     * @param $coursegroup
     * @param $studentno
     * @param bool $increase
     * @return int|string
     */
    public function updateStudentClassAttendent($year,$term,$coursegroup,$studentno,$increase=true){
        $sql = $increase?
            'UPDATE cp SET cp.class_attendents = (cp.class_attendents+1 ) ':
            'UPDATE cp SET cp.class_attendents = (cp.class_attendents-1 ) ';
        $sql .= 'FROM COURSEPLAN cp,STUDENTS
        WHERE cp.CLASSNO = STUDENTS.CLASSNO and cp.[YEAR] = :year and cp.TERM = :term and cp.COURSENO+cp.[GROUP] = :coursegroup
        and STUDENTS.STUDENTNO = :studentno';
        return $this->doneExecute($this->sqlExecute($sql,array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroup'  => $coursegroup,
            ':studentno'    => $studentno,
        )));
    }

    /**
     * 弃用******
     * 刷新选课人数
     * 使用到临时表 cwebs_temp_courseselection_attendent
     * @param $year
     * @param $term
     * @return int|string
     */
    public function refreshCourseSelectionAttendent($year,$term){
        $clrsql = 'DELETE FROM cwebs_temp_courseselection_attendent ;';
        $istsql = '
INSERT INTO cwebs_temp_courseselection_attendent (
	[year],
	term,
	courseno,
	[group],
	classno,
	c
) SELECT DISTINCT
	R32.[YEAR] AS YEAR,
	R32.TERM AS term,
	R32.courseno,
	R32.[group],
	dbo.group_concat (
		DISTINCT STUDENTS.CLASSNO,
		\',\'
	) AS classno,
	COUNT (DISTINCT R32.STUDENTNO) AS c
FROM
	R32
INNER JOIN STUDENTS ON STUDENTS.STUDENTNO = R32.STUDENTNO
WHERE
	[year] = :year AND term = : term
GROUP BY
	R32.[YEAR],
	R32.TERM,
	R32.COURSENO,
	R32.[GROUP] ';
        $this->startTrans();
        $rst = $this->doneExecute($this->sqlExecute($clrsql));
        if(is_string($rst)){
            return "刷新选课人数的过程终止在清空旧记录的过程中，错误：{$rst}";
        }
        $rst2 = $this->doneExecute($this->sqlExecute($istsql,array(':year'  => $year,':term'    => $term)));
        if(is_string($rst2)){
            return "刷新选课人数的过程终止在插入新 记录的过程中，错误：{$rst2}";
        }
        $this->commit();
        return $rst2;
    }

    /**
     *
     * @param $coursetypepart
     * @param $bind
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getStudentCourseTableList($coursetypepart,$bind,$offset,$limit){

        //刷新人数
//        $rst = $this->refreshCourseSelectionAttendent($bind[':YEAR'],$bind[':TERM']);
//        if(is_string($rst)){
//            return $rst;
//        }

        $fields = "
RTRIM(VIEWSCHEDULETABLE.COURSENOGROUP) AS COURSENOGROUP,
dbo.GETONE(RTRIM(VIEWSCHEDULETABLE.COURSENAME)) AS COURSENAME,
dbo.GETONE(VIEWSCHEDULETABLE.CREDITS) AS CREDITS,
dbo.GETONE(VIEWSCHEDULETABLE.WEEKHOURS) AS WEEKHOURS,
dbo.GETONE(VIEWSCHEDULETABLE.WEEKEXPEHOURS) AS WEEKEXPEHOURS,
dbo.GETONE(RTRIM(VIEWSCHEDULETABLE.COURSETYPE)) AS COURSETYPE,
dbo.GETONE(RTRIM(VIEWSCHEDULETABLE.EXAMTYPE)) AS EXAMTYPE,
dbo.GETONE(RTRIM(VIEWSCHEDULETABLE.EXAM)) AS EXAM,
dbo.GETONE(SCHEDULEPLAN.ATTENDENTS) AS ATTENDENTS,
dbo.GETONE(RTRIM(VIEWSCHEDULETABLE.COURSETYPENAME)) AS COURSETYPENAME,
dbo.GETONE(RTRIM(VIEWSCHEDULETABLE.SCHOOLNAME)) AS SCHOOLNAME,
dbo.GROUP_CONCAT_MERGE(RTRIM(VIEWSCHEDULETABLE.CLASSNONAME), '; ') AS CLASSNONAME,
dbo.GETONE(RTRIM(VIEWSCHEDULETABLE.TEACHERNONAME)) AS TEACHERNONAME,
dbo.GETONE(RTRIM(VIEWSCHEDULETABLE.REM)) AS REM,
dbo.GETONE(VIEWSCHEDULETABLE.LIMITGROUPNO) AS LIMITGROUPNO,
dbo.GETONE(VIEWSCHEDULETABLE.LIMITNUM) AS LIMITNUM,
dbo.GETONE(COURSEPLAN.LIMITCREDIT) AS LIMITCREDIT,
COURSEPLAN.total_attendents_limit AS total_attendents_limit, -- 限制总人数
dbo.GETONE(R32.COURSENO) as SELECTED,
dbo.GETONE(RTRIM(courses.[课程介绍])) as introduce,
dbo.GROUP_CONCAT_MERGE(RTRIM(VIEWSCHEDULETABLE.DAYNTIME)+', 座位数:'+RTRIM(CAST(VIEWSCHEDULETABLE.SEATS AS char)), '<br />') AS CURRICULUM,
SUM(ISNULL(COURSEPLAN.class_attendents, 0)) as classnum, -- 该学生所在班级已经选择该课程的学生数目
COURSEPLAN.ATTENDENTS AS classlimit -- 该学生所在班级限定人数
";
//        $join = '
//LEFT OUTER JOIN (
//    SELECT DISTINCT
//    R32.[YEAR] as year,
//    R32.TERM as term,
//    R32.courseno,
//    R32.[group],
//    COUNT(DISTINCT R32.STUDENTNO) as c
//    from R32
//    INNER JOIN STUDENTS on STUDENTS.STUDENTNO = R32.STUDENTNO
//        and  STUDENTS.CLASSNO = (SELECT CLASSNO from STUDENTS where STUDENTS.studentno = :stno)
//    GROUP BY R32.[YEAR],R32.TERM,R32.COURSENO,R32.[GROUP]
//    ) temp on temp.[year] = VIEWSCHEDULETABLE.[YEAR] and temp.term = VIEWSCHEDULETABLE.TERM and temp.courseno = VIEWSCHEDULETABLE.COURSENO and VIEWSCHEDULETABLE.[GROUP] = temp.[group]
//LEFT OUTER JOIN SCHEDULEPLAN ON VIEWSCHEDULETABLE.RECNO=SCHEDULEPLAN.RECNO
//LEFT OUTER JOIN R32 ON R32.STUDENTNO=:STUDENTNO AND R32.YEAR=VIEWSCHEDULETABLE.YEAR AND R32.TERM = VIEWSCHEDULETABLE.TERM AND R32.COURSENO=VIEWSCHEDULETABLE.COURSENO AND R32.[GROUP]=VIEWSCHEDULETABLE.[GROUP]
//INNER JOIN COURSEPLAN on COURSEPLAN.[YEAR] = VIEWSCHEDULETABLE.[YEAR] and COURSEPLAN.[TERM] = VIEWSCHEDULETABLE.[TERM]
//    and COURSEPLAN.[COURSENO] = VIEWSCHEDULETABLE.[COURSENO] and COURSEPLAN.[GROUP] = VIEWSCHEDULETABLE.[GROUP] and COURSEPLAN.CLASSNO = VIEWSCHEDULETABLE.CLASSNO
//LEFT OUTER JOIN courses on courses.courseno = VIEWSCHEDULETABLE.courseno';
        $join = '
INNER JOIN COURSEPLAN on COURSEPLAN.[YEAR] = VIEWSCHEDULETABLE.[YEAR] and COURSEPLAN.[TERM] = VIEWSCHEDULETABLE.[TERM]
    and COURSEPLAN.[COURSENO] = VIEWSCHEDULETABLE.[COURSENO] and COURSEPLAN.[GROUP] = VIEWSCHEDULETABLE.[GROUP]
		and COURSEPLAN.CLASSNO = VIEWSCHEDULETABLE.CLASSNO
LEFT OUTER JOIN SCHEDULEPLAN ON VIEWSCHEDULETABLE.RECNO=SCHEDULEPLAN.RECNO
LEFT OUTER JOIN R32 ON R32.STUDENTNO=:STUDENTNO AND R32.YEAR=VIEWSCHEDULETABLE.YEAR AND R32.TERM = VIEWSCHEDULETABLE.TERM AND R32.COURSENO=VIEWSCHEDULETABLE.COURSENO AND R32.[GROUP]=VIEWSCHEDULETABLE.[GROUP]
LEFT OUTER JOIN courses on courses.courseno = VIEWSCHEDULETABLE.courseno';
        $where = "
WHERE VIEWSCHEDULETABLE.YEAR= :YEAR
AND VIEWSCHEDULETABLE.TERM= :TERM
AND VIEWSCHEDULETABLE.COURSENOGROUP LIKE :COURSENOGROUP
AND VIEWSCHEDULETABLE.COURSENAME LIKE :COURSENAME
AND (VIEWSCHEDULETABLE.TEACHERNAME LIKE :TEACHERNAME  OR VIEWSCHEDULETABLE.TEACHERNAME IS NULL)
AND {$coursetypepart}
AND VIEWSCHEDULETABLE.SCHOOL LIKE :SCHOOL
AND VIEWSCHEDULETABLE.CLASSNO LIKE :CLASSNO
AND (VIEWSCHEDULETABLE.DAY LIKE :DAY OR VIEWSCHEDULETABLE.DAY IS NULL)
AND (VIEWSCHEDULETABLE.TIME LIKE :TIME OR VIEWSCHEDULETABLE.TIME IS NULL)";
        $group = 'COURSENOGROUP,COURSEPLAN.ATTENDENTS,COURSEPLAN.total_attendents_limit';
        $order = 'VIEWSCHEDULETABLE.COURSETYPE,LIMITGROUPNO,COURSENOGROUP';

        $csql = $this->makeCountSql('VIEWSCHEDULETABLE',array(
            'join'=>$join,
            'where'=>$where,
            'group'=>$group
        ));
        $ssql = $this->makeSql('VIEWSCHEDULETABLE',array(
            'fields'=>$fields,
            'join'=>$join,
            'where'=>$where,
            'group'=>$group,
            'order'=>$order,
        ),$offset,$limit);
//        varsdumpout($csql,$ssql,$bind);
        $rst = $this->getTableList2($csql,$ssql,$bind);
        return $rst;
    }

    /** 0.0
     * 更新指定学年学期的排课计划参加人数
     * @param $year
     * @param $term
     * @param NULL|string $coursetypeoption 用于指定特定的课程类型
     * @param boolean $exclusion 指定了参数三时表示是排除在外还是包含在内
     * @return int|string
     */
    public function updateScheduleAttendents($year,$term,$coursetypeoption=NULL,$exclusion=true){
        $schedulePlanModel = new SchedulePlanModel();
        return $schedulePlanModel->updateAttendent($year,$term,$coursetypeoption,$exclusion);
    }
    /**
     * 清空旧的指定类型的排课计划表
     * @param $year
     * @param $term
     * @param null $coursetypeoption
     * @param bool $exclusion
     * @return int|string
     */
    public function clearViewScheduleTable($year,$term,$coursetypeoption=NULL,$exclusion=true){
        $sql = 'DELETE from VIEWSCHEDULETABLE WHERE YEAR=:YEAR AND TERM=:TERM';
        $bind = array(
            ':YEAR' => $year,
            ':TERM' => $term,
        );
        if(NULL !== $coursetypeoption){
            if($exclusion){
                $sql .= ' AND TYPE = :course_type_options';
            }else{
                $sql .= ' AND TYPE != :course_type_options';
            }
            $bind[':course_type_options'] = $coursetypeoption;
        }
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 重新生成排课计划
     * @param $year
     * @param $term
     * @param null $type
     * @param bool $exclusion
     * @return int|string
     */
    public function insertViewScheduleTable($year,$term,$type=NULL,$exclusion=true){
        $sql = "
INSERT INTO VIEWSCHEDULETABLE
SELECT VIEWSCHEDULE.*,newid() AS ROWID
FROM VIEWSCHEDULE
WHERE VIEWSCHEDULE.YEAR=:YEAR
AND VIEWSCHEDULE.TERM=:TERM";
        //and VIEWSCHEDULE.coursetype = '必修'
        $bind = array(
            ':YEAR' => $year,
            ':TERM' => $term,
        );
        if(NULL !== $type){
            if($exclusion){
                $sql .= ' AND TYPE = :course_type_options';
            }else{
                $sql .= ' AND TYPE != :course_type_options';
            }
            $bind[':course_type_options'] = $type;
        }
//        vardump($sql,$bind);
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }


    /**
     * 学生选课检测 列表数据源
     * @param int $year
     * @param int $term
     * @param $studentno
     * @param $classno
     * @param int $offset
     * @param int $limit
     * @return array|string 返回列表数据或者错误信息
     */
    public function getStudentCoursesTableList($year,$term,$studentno,$classno,$offset=null,$limit=null){
        //TODO:实际查询可以，PHP中不行，待检查！！！
        $declare = '
        declare @year char(4)
declare @term char(1)
declare @studentno VARCHAR(10)
DECLARE @classno VARCHAR(10)
set @year=:year
set @term=:term
SET @studentno = :studentno
SET @classno = :classno
';
        //yxms 已选门数
        $fields = '
STUDENTS.STUDENTNO as xh,
STUDENTS.NAME AS xm,
CLASSES.CLASSNAME as xsbj,
Datename(year,CLASSES.[YEAR]) as enterdate,
s_courses_choosed.[YEAR],
s_courses_choosed.TOTALCREDITS as zxf,
s_courses_choosed.TOTALCOURSES as hasChosedNum,
s_courses_should_choose_nolimit.shouldChoseNum_nolimit,
s_courses_should_choose_limit.shouldChoseNum_haslimit,
 ISNULL(s_courses_should_choose_nolimit.shouldChoseNum_nolimit,0) +
 	ISNULL(s_courses_should_choose_limit.shouldChoseNum_haslimit,0) as shouldChoseNum,
 ISNULL(s_courses_should_choose_nolimit.shouldChoseNum_nolimit,0) +
 	ISNULL(s_courses_should_choose_limit.shouldChoseNum_haslimit,0) - s_courses_choosed.TOTALCOURSES as missed
';
        //修改从course中找寻credit变更为从R12中获取
        $join = 'INNER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
-- 已经选择的课程
-- LEFT OUTER JOIN (
--     SELECT count(COURSENO) as shouldChoseNum,
--     R28.PROGRAMNO,R28.STUDENTNO,
--     R12.YEAR as s_year,
--     R12.TERM as s_term
--     from R12
--     INNER JOIN R28 on R28.PROGRAMNO = R12.PROGRAMNO
--     GROUP BY R28.STUDENTNO, R28.PROGRAMNO,R12.YEAR,R12.TERM
-- ) s_courses_should_choose on
LEFT OUTER JOIN
(
   SELECT DISTINCT
    STUDENTNO,
    SUM(R12.CREDITS) AS TOTALCREDITS,
    COUNT(DISTINCT R32.COURSENO+R32.[GROUP]) AS TOTALCOURSES,
R32.[YEAR] ,
R32.TERM
  FROM R32
  JOIN R12 ON R32.COURSENO=R12.COURSENO
  GROUP BY STUDENTNO,R32.[YEAR],R32.TERM
)AS s_courses_choosed ON STUDENTS.STUDENTNO=s_courses_choosed.STUDENTNO and :yeara  = s_courses_choosed.[YEAR] and :terma = s_courses_choosed.[TERM]

-- 按照教学计划应该选的
-- 分成有限选组号和无限选组号两部分
 LEFT OUTER JOIN (
 	SELECT DISTINCT
 		COUNT (DISTINCT COURSENO) AS shouldChoseNum_nolimit, -- COURSENO 前面是否需要添加distinct?? ps:不同的教学计划可能有同样的选修课
 		R28.STUDENTNO,
 		R12.YEAR AS s_year,
 		R12.TERM AS s_term
 	FROM R28
 	INNER JOIN R12 on R28.PROGRAMNO = R12.PROGRAMNO
 	INNER JOIN STUDENTS on STUDENTS.STUDENTNO = R28.STUDENTNO
 	INNER JOIN CLASSES on CLASSES.CLASSNO = STUDENTS.CLASSNO
 	-- INNER JOIN R16 ON R16.PROGRAMNO = R12.PROGRAMNO  -- 不查询班级绑定的教学计划 ，而直接查询R12（学生的）
 	WHERE R12.LIMITGROUPNO = 0 -- 无限定组号的，无需按照组号分组
 	and RTRIM(R28.STUDENTNO) like :studentnoa
 	and ( year(CLASSES.[YEAR]) - :yearb + 1) = R12.[YEAR] -- 学年转换
 	and R12.TERM = :termb
 	GROUP BY
 		R28.STUDENTNO,
 		R12.YEAR, -- 年级，非千位数
 		R12.TERM
 ) s_courses_should_choose_nolimit on s_courses_should_choose_nolimit.studentno = STUDENTS.STUDENTNO
 LEFT OUTER JOIN (
 	SELECT SUM(c.shouldChoseNum_haslimit) as shouldChoseNum_haslimit,
	STUDENTNO,s_year,s_term
	FROM
	(
		SELECT
				CAST(dbo.getone(R12.LIMITNUM) as INT) AS shouldChoseNum_haslimit,
		-- 		R12.COURSENO,
				R28.STUDENTNO,
				R12.LIMITGROUPNO,
		-- 		R12.LIMITNUM,
				R12.YEAR AS s_year,
				R12.TERM AS s_term
			FROM R28
			INNER JOIN R12 on R28.PROGRAMNO = R12.PROGRAMNO
			INNER JOIN STUDENTS on STUDENTS.STUDENTNO = R28.STUDENTNO
			INNER JOIN CLASSES on CLASSES.CLASSNO = STUDENTS.CLASSNO
			-- INNER JOIN R16 ON R16.PROGRAMNO = R12.PROGRAMNO  -- 不查询班级绑定的教学计划 ，而直接查询R12（学生的）
			WHERE R12.LIMITGROUPNO > 0 -- 有限定组号的
			and RTRIM(R28.STUDENTNO) like :studentnob
 	and ( year(CLASSES.[YEAR]) - :yearc + 1) = R12.[YEAR] -- 学年转换
 	and R12.TERM = :termc
			GROUP BY
				R28.STUDENTNO,
				R12.YEAR, -- 年级，非千位数
				R12.TERM,
			 R12.LIMITGROUPNO
	) as c GROUP BY c.STUDENTNO,c.s_year,c.s_term
    ) s_courses_should_choose_limit on s_courses_should_choose_limit.studentno = STUDENTS.STUDENTNO ';
        $where = 'STUDENTS.STUDENTNO like  :studentnoc and STUDENTS.classno like :classno';
        $csql = $this->makeCountSql('STUDENTS',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('STUDENTS',array(
            'fields'=>$fields,
            'join'  => $join,
            'where' => $where,
        ),$offset,$limit);
        $rst = $this->getTableList($csql,$ssql,array(
            ':yeara' => $year,
            ':terma' => $term,
            ':studentnoa'    => $studentno,
            ':yearb' => $year,
            ':termb' => $term,
            ':studentnob'    => $studentno,
            ':yearc' => $year,
            ':termc' => $term,
            ':studentnoc'    => $studentno,
            ':classno'      => $classno,
        ));

//        varsdumpout($csql,$ssql,array(
//            ':year' => $year,
//            ':term' => $term,
//            ':studentno'    => $studentno,
//            ':classno'      => $classno,
//        ),$rst);

        return $rst;
    }


    /**
     * @param $studentno
     * @param $coursenoGroupno
     * @return mixed|string
     */
    public function isRetake($studentno,$coursenoGroupno){
        return $this->getTable('R32',array(
            'STUDENTNO' => $studentno,
            'COURSENO'  => array(doWithBindStr($coursenoGroupno),'like'),
        ),true);
    }


    /**
     * ????? xu3xu4dong4 ?????
     * 放入全体学生名单
     * @return int|string
     */
    public function insertIntoMakingCredit(){
        $sql = 'insert into makingcredit(studentno)
select students.studentno
from students where not exists (select * from makingcredit where makingcredit.studentno=students.studentno)';
        return $this->doneExecute($this->sqlExecute($sql));
    }

    /**
     * 更新素质学分的汇总的创新技能素质学分部分
     * @return int|string
     */
    public function updateMakingCredit(){
        $sql = 'update makingcredit
set addcredit=addtion.amount
from makingcredit inner join (select sum(credit) as amount,studentno as studentno from addcredit group by studentno ) as addtion
on addtion.studentno=makingcredit.studentno
where addcredit!=addtion.amount';
        return $this->doneExecute($this->sqlExecute($sql));
    }

    /**
     * 更新素质类课程部分
     * @return int|string
     */
    public function updateMakingCreditCourse(){
        $sql = "update makingcredit
    set coursecredit=coursecredit.amount
    from makingcredit inner join (
    select sum(courses.credits) amount,scores.studentno as studentno
    from scores inner join courses on courses.courseno=scores.courseno
    where (scores.courseno like '08%' or scores.courseno like '007%' or scores.[group] like 'G%') and (examscore>=60 or testscore in ('合格','及格','中等','优秀','良好')
    or examscore2>=60 or testscore2 in ('合格','及格','中等','优秀','良好'))
    group by scores.studentno ) as coursecredit on coursecredit.studentno=makingcredit.studentno
    where coursecredit!=coursecredit.amount";
        return $this->doneExecute($this->sqlExecute($sql));
    }

    /**
     * 获取课程时间列表
     * @param $year
     * @param $term
     * @param $who
     * @param $para
     * @return array|NULL|string
     */
    public function getCourseTimelist($year,$term,$who,$para){
        return $this->getTimelist($year,$term,$who,'P',$para);
    }

    /**
     * 获取学生的时间列表
     * @param $year
     * @param $term
     * @param $studentno
     * @return array|NULL|string
     */
    public function getStudentTimelist($year,$term,$studentno){
        return $this->getTimelist($year,$term,$studentno,'S');
    }

    public function updateStudentTimelist(){

    }



    /**
     * 确认值是否都是可以使用的
     * @return bool
     */
    protected function checkAllValid(){
        $args = func_get_args();
        foreach($args as &$val){
            if(!isset($val)){
                return false;
            }
        }
        return true;
    }


    /**
     * 获取 课程选课情况 表格数据
     * @param $bind
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getCourseChoicesStatementTableList($bind,$offset=null,$limit=null){
        $fields = "
row_number() over(order by dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENOGROUP)))as row,
dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENOGROUP)) AS kh,
dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENAME)) AS km,
dbo.getOne(VIEWSCHEDULE.CREDITS) AS xf,
dbo.getOne(VIEWSCHEDULE.WEEKHOURS) AS zxs,
dbo.getOne(VIEWSCHEDULE.WEEKEXPEHOURS) AS zsy,
dbo.getOne(RTRIM(VIEWSCHEDULE.COURSETYPE)) AS xk,
dbo.getOne(RTRIM(VIEWSCHEDULE.APPROACH)) AS xk2,
dbo.getOne(RTRIM(VIEWSCHEDULE.EXAMTYPE)) AS kh2,
dbo.getOne(SCHEDULEPLAN.ATTENDENTS) AS yxrs,
dbo.getOne(COURSEPLAN.total_attendents_limit) AS ESTIMATE,
dbo.getOne(RTRIM(VIEWSCHEDULE.COURSETYPENAME)) AS kclb,
dbo.getOne(RTRIM(VIEWSCHEDULE.SCHOOLNAME)) AS kkxy,
dbo.getOne(RTRIM(VIEWSCHEDULE.SCHOOL)) AS SCHOOLNO,
dbo.getOne(RTRIM(VIEWSCHEDULE.CLASSNONAME)) AS bj,
dbo.getOne(RTRIM(VIEWSCHEDULE.TEACHERNONAME)) AS js,
dbo.getOne(RTRIM(VIEWSCHEDULE.REM)) AS bz,
dbo.GROUP_CONCAT_MERGE(RTRIM(VIEWSCHEDULE.DAYNTIME)+'座位数:'+CAST(VIEWSCHEDULE.SEATS AS char),',') AS kcap
";
        $join = '
LEFT OUTER JOIN SCHEDULEPLAN ON VIEWSCHEDULE.RECNO=SCHEDULEPLAN.RECNO
LEFT OUTER JOIN CLASSES ON CLASSES.CLASSNO=VIEWSCHEDULE.CLASSNO
INNER JOIN COURSEPLAN on COURSEPLAN.[YEAR] = VIEWSCHEDULE.[YEAR] and COURSEPLAN.[TERM] = VIEWSCHEDULE.[TERM]
    and COURSEPLAN.[COURSENO] = VIEWSCHEDULE.[COURSENO] and COURSEPLAN.[GROUP] = VIEWSCHEDULE.[GROUP] and COURSEPLAN.CLASSNO = VIEWSCHEDULE.CLASSNO
';
        $where = '
WHERE VIEWSCHEDULE.YEAR= :YEAR
AND VIEWSCHEDULE.TERM= :TERM
AND VIEWSCHEDULE.COURSENOGROUP LIKE :COURSE
AND VIEWSCHEDULE.COURSENAME LIKE :CNAME
AND VIEWSCHEDULE.TYPE LIKE :CTYPE
--AND VIEWSCHEDULE.SCHOOL LIKE :SCHOOL
AND CLASSES.SCHOOL LIKE :SCHOOL
AND VIEWSCHEDULE.CLASSNO LIKE :CLASSNO
AND CAST(SCHEDULEPLAN.SEATSLOCK AS CHAR) LIKE :SEATSLOCK
';
        $group = 'VIEWSCHEDULE.COURSENOGROUP';

        $csql = $this->makeSql('VIEWSCHEDULE',array(
            'fields'=>'count(*) As ROWS',
            'join'=>$join,
            'where'=>$where,
            'group' => $group,
        ));
        $ssql = $this->makeSql('VIEWSCHEDULE',array(
            'fields'=>$fields,
            'where' =>$where,
            'join'  =>$join,
            'group' => $group,
        ),$offset,$limit);
//        vardump($csql,$ssql,$bind);
     //   unset($bind[':SCHOOL']);
        return $this->getTableList2($csql,$ssql,$bind);
    }


    public function getStudentCourseChoosenExcelList($bind){
        $fields = "
STUDENTS.CLASSNO,
STUDENTS.STUDENTNO as xh,
RTRIM(STUDENTS.NAME) AS xm,
RTRIM(CLASSES.CLASSNAME) as xsbj,
SELECTIONS.TOTALCREDITS as zxf,
SELECTIONS.TOTALCOURSES as xkms,
Ts.coursename as tscoursename,
St.coursename as stcoursename,
Common.coursename as commoncoursenames";
        $join = "
INNER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN
(
  SELECT STUDENTNO,SUM(COURSES.CREDITS) AS TOTALCREDITS,COUNT(*) AS TOTALCOURSES,YEAR,TERM
  FROM R32
  INNER JOIN COURSES ON R32.COURSENO=COURSES.COURSENO
  GROUP BY STUDENTNO,YEAR,TERM
)AS SELECTIONS ON STUDENTS.STUDENTNO=SELECTIONS.STUDENTNO and SELECTIONS.[YEAR] = :year and SELECTIONS.TERM = :term
--通识
LEFT OUTER JOIN (
	SELECT
		R32.STUDENTNO,
		dbo.GROUP_CONCAT(RTRIM(COURSES.coursename)+'('+RTRIM(ISNULL(TEACHERS.NAME, '空'))+'/'+RTRIM(CLASSROOMS.BUILDING+CLASSROOMS.[NO])+')',',') AS coursename,
		R32.YEAR,
		R32.TERM
  FROM R32
  INNER JOIN COURSES ON R32.COURSENO=COURSES.COURSENO
LEFT OUTER JOIN SCHEDULEPLAN on R32.[YEAR]=SCHEDULEPLAN.[YEAR] and R32.TERM=SCHEDULEPLAN.TERM
		and R32.COURSENO = SCHEDULEPLAN.COURSENO and R32.[GROUP] = SCHEDULEPLAN.[GROUP]
LEFT OUTER JOIN TEACHERPLAN on SCHEDULEPLAN.RECNO = TEACHERPLAN.MAP
LEFT OUTER JOIN TEACHERS on TEACHERPLAN.TEACHERNO = TEACHERS.TEACHERNO
LEFT OUTER JOIN SCHEDULE on SCHEDULE.[YEAR]=SCHEDULEPLAN.[YEAR] and SCHEDULE.TERM=SCHEDULEPLAN.TERM
		and SCHEDULE.COURSENO = SCHEDULEPLAN.COURSENO and SCHEDULE.[GROUP] = SCHEDULEPLAN.[GROUP]
LEFT OUTER JOIN CLASSROOMS on CLASSROOMS.ROOMNO = SCHEDULE.ROOMNO

	WHERE R32.COURSENO like  'TS%'
  GROUP BY R32.STUDENTNO,R32.YEAR,R32.TERM

) as Ts on  Ts.studentno = STUDENTS.STUDENTNO and Ts.[YEAR] = SELECTIONS.[YEAR] and Ts.[TERM] = SELECTIONS.[TERM]
--社团
LEFT OUTER JOIN (
	SELECT
		R32.STUDENTNO,
		dbo.GROUP_CONCAT(RTRIM(COURSES.coursename)+'('+RTRIM(ISNULL(TEACHERS.NAME, '空'))+'/'+RTRIM(CLASSROOMS.BUILDING+CLASSROOMS.[NO])+')',',') AS coursename,
		R32.YEAR,
		R32.TERM
  FROM R32
  INNER JOIN COURSES ON R32.COURSENO=COURSES.COURSENO
LEFT OUTER JOIN SCHEDULEPLAN on R32.[YEAR]=SCHEDULEPLAN.[YEAR] and R32.TERM=SCHEDULEPLAN.TERM
		and R32.COURSENO = SCHEDULEPLAN.COURSENO and R32.[GROUP] = SCHEDULEPLAN.[GROUP]
LEFT OUTER JOIN TEACHERPLAN on SCHEDULEPLAN.RECNO = TEACHERPLAN.MAP
LEFT OUTER JOIN TEACHERS on TEACHERPLAN.TEACHERNO = TEACHERS.TEACHERNO
LEFT OUTER JOIN SCHEDULE on SCHEDULE.[YEAR]=SCHEDULEPLAN.[YEAR] and SCHEDULE.TERM=SCHEDULEPLAN.TERM
		and SCHEDULE.COURSENO = SCHEDULEPLAN.COURSENO and SCHEDULE.[GROUP] = SCHEDULEPLAN.[GROUP]
LEFT OUTER JOIN CLASSROOMS on CLASSROOMS.ROOMNO = SCHEDULE.ROOMNO

	WHERE R32.COURSENO like  'ST%'
  GROUP BY R32.STUDENTNO,R32.YEAR,R32.TERM
) as St on  St.studentno = STUDENTS.STUDENTNO and St.[YEAR] = SELECTIONS.[YEAR] and St.[TERM] = SELECTIONS.[TERM]
--普通
LEFT OUTER JOIN (
	SELECT
		R32.STUDENTNO,
		dbo.GROUP_CONCAT(RTRIM(COURSES.coursename)+'('+RTRIM(ISNULL(TEACHERS.NAME, '空'))+'/'+ISNULL(RTRIM(CLASSROOMS.BUILDING+CLASSROOMS.[NO]),'空')+')',',') AS coursename,
		R32.YEAR,
		R32.TERM
  FROM R32
  INNER JOIN COURSES ON R32.COURSENO=COURSES.COURSENO
LEFT OUTER JOIN SCHEDULEPLAN on R32.[YEAR]=SCHEDULEPLAN.[YEAR] and R32.TERM=SCHEDULEPLAN.TERM
		and R32.COURSENO = SCHEDULEPLAN.COURSENO and R32.[GROUP] = SCHEDULEPLAN.[GROUP]
LEFT OUTER JOIN TEACHERPLAN on SCHEDULEPLAN.RECNO = TEACHERPLAN.MAP
LEFT OUTER JOIN TEACHERS on TEACHERPLAN.TEACHERNO = TEACHERS.TEACHERNO
LEFT OUTER JOIN SCHEDULE on SCHEDULE.[YEAR]=SCHEDULEPLAN.[YEAR] and SCHEDULE.TERM=SCHEDULEPLAN.TERM
		and SCHEDULE.COURSENO = SCHEDULEPLAN.COURSENO and SCHEDULE.[GROUP] = SCHEDULEPLAN.[GROUP]
LEFT OUTER JOIN CLASSROOMS on CLASSROOMS.ROOMNO = SCHEDULE.ROOMNO
	WHERE (R32.COURSENO not like 'TS%' and R32.COURSENO not like 'ST%')
  GROUP BY R32.STUDENTNO,R32.YEAR,R32.TERM

) as Common on  Common.studentno = STUDENTS.STUDENTNO and Common.[YEAR] = SELECTIONS.[YEAR] and Common.[TERM] = SELECTIONS.[TERM]

";
        $where = '
STUDENTS.STUDENTNO like :studentno
and STUDENTS.CLASSNO like :classno ';
        $order = 'STUDENTS.CLASSNO';
        $group = 'STUDENTS.STUDENTNO,STUDENTS.NAME,CLASSES.CLASSNAME,SELECTIONS.TOTALCREDITS,SELECTIONS.TOTALCOURSES,STUDENTS.CLASSNO';
        $sql = $this->makeSql('STUDENTS',array(
            'fields'    => $fields,
            'join'  => $join,
            'where' => $where,
            'order' => $order,
//            'group' => $group,
        ));
//        vardump($sql,$bind);
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }
    public function getStudentCourseChoosenTableList($bind,$offset,$limit){
        $fields = '
row_number() over(ORDER BY STUDENTS.STUDENTNO) as row,
STUDENTS.STUDENTNO as xh,
STUDENTS.NAME AS xm,
CLASSES.CLASSNAME as xsbj,
SELECTIONS.TOTALCREDITS as zxf,
SELECTIONS.TOTALCOURSES as xkms';
        $join = '
INNER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN
(
  SELECT STUDENTNO,SUM(COURSES.CREDITS) AS TOTALCREDITS,COUNT(*) AS TOTALCOURSES,YEAR,TERM
  FROM R32
  INNER JOIN COURSES ON R32.COURSENO=COURSES.COURSENO
  GROUP BY STUDENTNO,YEAR,TERM
)AS SELECTIONS ON STUDENTS.STUDENTNO=SELECTIONS.STUDENTNO';
        $where = '
STUDENTS.STUDENTNO like :studentno
and STUDENTS.CLASSNO like :classno
and SELECTIONS.[YEAR] = :year
and SELECTIONS.[TERM] = :term';
        $csql = $this->makeCountSql('STUDENTS',array(
            'join' => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('STUDENTS',array(
            'fields' => $fields,
            'join' => $join,
            'where' => $where,
        ),$offset,$limit);
        $rst = $this->getTableList($csql,$ssql,$bind);
        return $rst;
    }


    /**
     * 获取这个班级的所有必修课
     * @param $year
     * @param $term
     * @param $classno
     * @param boolean $must 是否是必修课，true
     * @return array|int|string
     */
    public function getCoursePlanByApproches($year,$term,$classno,$must=true){
        return $this->getTable('COURSEPLAN',array(
            'CLASSNO'   => $classno,
            'YEAR'      => $year,
            'TERM'      => $term,
            'COURSETYPE'    => array('M',false,$must?' = ':' <> '),
        ));
    }

    public function clearStudentCourseByClassno($classno,$coursetype,$year,$term){
        $sql = "
DELETE from R32
WHERE R32.[YEAR] = :year and term  =:term
and R32.APPROACH = :coursetype and R32.STUDENTNO in (
SELECT STUDENTS.STUDENTNO from STUDENTS INNER JOIN CLASSES on STUDENTS.CLASSNO = CLASSES.CLASSNO
WHERE CLASSES.CLASSNO = :classno
)";
        $bind = array(
            ':year'         => $year,
            ':term'         => $term,
            ':coursetype'   => $coursetype,
            ':classno'      => $classno,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }


    /**
     *
     * 目的：将学生在该学年学期的选课记录插入到R32表中（即，学生修课计划表）
     * 问题：R32表的主键是Year,Term,Courseno,Studentno,而没有Group（组号），测试过程中发现一个学生在本学年学期可能对应多个课程的不同分组
     *      显然这有悖常理
insert into r32(year,term,courseno,[group],studentno,APPROACH,coursetype,examtype,selecttime)
    select distinct
    courseplan.year,
    courseplan.term,
    courseplan.courseno,
    students.studentno,
    courseplan.[group],
    courseplan.coursetype,--修课方式
    courseplan.course_type_options, --课程类别一
    courseplan.examtype ,
    getdate() s
    from courseplan
    inner join students on courseplan.classno=students.classno
    where courseplan.coursetype in ('M','T') --选修和模块课程
    and  courseplan.year=2015
    and courseplan.term=1
    and students.status='A'  --学籍状态为正常
    and not exists (
    select * from r32
    where r32.year=courseplan.year
    and r32.term=courseplan.term
    and r32.courseno=courseplan.courseno
    -- and r32.[group]=courseplan.[group]
    and students.studentno=r32.studentno)
    -- GROUP by courseplan.[YEAR] ,courseplan.TERM, courseplan.COURSENO,STUDENTS.studentno -- 查看主键冲突的时候使用
     *
     * @param null|string $classno 需要同步的班级号，传入null或者'%'将同步所有的班级
     * @param string|array $coursetype 修课方式***
     * @param int $year
     * @param int $term
     * @param bool $in 决定参数二是排除在外的还是包含在内的,默认为true(包含在内)
     * @return int|string
     */
    public function synchStudentCourseByClassno($classno,$coursetype,$year,$term,$in=true){

        $insertPart = 'insert into r32(year,term,courseno,[group],studentno,APPROACH,coursetype,examtype,selecttime)';
        $fields = '
    courseplan.year,
    courseplan.term,
    courseplan.courseno,
    courseplan.[group],
    students.studentno,
    courseplan.coursetype,
    courseplan.course_type_options,
    courseplan.examtype ,
    getdate() s  ';
        $join = 'inner join students on courseplan.classno=students.classno';
        $operator = null;
        if(is_array($coursetype)){
            $operator = $in?' in ':' not in ';
        }else{
            $operator = $in?' = ':' <> ';
        }

        $whrmap = array(
            array('courseplan.coursetype',$coursetype,$operator),
            'courseplan.year'=>$year,
            'courseplan.term'=>$term,
            'students.status'=>'A',
        );
        if(null !== $classno and '%' !== trim($classno)){
            $whrmap[] = array('courseplan.CLASSNO' , $classno);
        }
        $whr = $this->makeWhere($whrmap);
        $ssql = $this->makeSql('courseplan',array(
            'distinct'  => true,
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $whr[0],
        ));
        $sql = "$insertPart $ssql
    and not exists (
    select 1 from r32
    where r32.year=courseplan.year
    and r32.term=courseplan.term
    and r32.courseno=courseplan.courseno
    and students.studentno=r32.studentno)";

//varsdumpout($sql,$whr[1]);
        $rst = $this->doneExecute($this->sqlExecute($sql,$whr[1]));
//        vardump($sql,$whr[1],$rst);
        if(is_string($rst) and false !== stripos($rst,'PRIMARY KEY ')){
            $rst = "无法同步第[{$year}]学年第[$term]学期的课程！";
        }
        return $rst;
    }

    public function updateSchedulePlanAttendentsByYearTerm($year,$term){
        $usql = '
declare @year char(4)
declare @term char(1)
set @year=:year
set @term=:term
UPDATE sp set sp.ATTENDENTS = temp.c
FROM SCHEDULEPLAN sp,(
select R32.COURSENO+R32.[GROUP] as  coursegroup,COUNT(DISTINCT STUDENTNO) as c from R32
 WHERE year = @year and term = @term  GROUP BY R32.COURSENO+R32.[GROUP]
) temp WHERE sp.[YEAR] = @year and sp.TERM = @term and sp.COURSENO+sp.[GROUP] = temp.coursegroup';
        return $this->doneExecute($this->sqlExecute($usql,array(
            ':year' => $year,
            ':term' => $term,
        )));
    }

    /**
     * 清空学生旧的必修课的数据
     * @param $year
     * @param $term
     * @return int|string
     */
    public function clearOldStudentMustedCourse($year,$term){
        //--- 清空原有的必修课数据（未作扩展性） ----//
        $clearBind = array(
            ':year' => $year,
            ':term' => $term
        );
        $clearSQL = "
delete from R32
WHERE R32.[YEAR] = :year and R32.TERM = :term and R32.COURSENO in (
	SELECT COURSEPLAN.courseno from COURSEPLAN WHERE COURSEPLAN.year = R32.[YEAR] and courseplan.coursetype = 'M' and courseplan.term = R32.TERM	)";
        return $this->doneExecute($this->sqlExecute($clearSQL,$clearBind));
//        vardump($rst,$clearSQL,$clearBind);
        //--- 清空结束 ---//
    }


    /**
     * 检测是否有学生选择了同一门课程的不同组号
     * 原则上这是不允许的，因R32表的主键限制
     * <sql>
            SELECT
            courseplan.year,
            courseplan.term,
            courseplan.courseno,
            students.studentno,
            count(*) as ROWS
            from  courseplan
            inner join students on courseplan.classno=students.classno
            where  courseplan.coursetype  in ('M','T')
            and  courseplan.year  = 2015
            and  courseplan.term  = 1
            and  students.status  = 'A'
            and not exists (
            select * from r32
            where r32.year=courseplan.year
            and r32.term=courseplan.term
            and r32.courseno=courseplan.courseno
            and students.studentno=r32.studentno)
            GROUP BY year,term,courseno,studentno
            having count(*) > 1
     * </sql>
     * @param $classno
     * @param $coursetype
     * @param $year
     * @param $term
     * @param bool $in
     * @return array|string
     */
    public function checkStudentCoursePlanUnRepeat($classno,$coursetype,$year,$term,$in=true){

        $operator = null;
        if(is_array($coursetype)){
            $operator = $in?' in ':' not in ';
        }else{
            $operator = $in?' = ':' <> ';
        }

        $whrmap = array(
            array('courseplan.coursetype',$coursetype,$operator),
        );
        if(null !== $classno and '%' !== trim($classno)){
            $whrmap[] = array('courseplan.CLASSNO' , $classno);
        }
        $whr = $this->makeSegments($whrmap);

        $sql = "
SELECT year,term,courseno,studentno,count(*) as ROWS
from (
select distinct
    courseplan.year,
    courseplan.term,
    courseplan.courseno,
    courseplan.[group],
    students.studentno,
    courseplan.coursetype,
    courseplan.course_type_options,
    courseplan.examtype ,
    getdate() s
from  courseplan  inner join students on courseplan.classno=students.classno
 where   {$whr[0]} and  courseplan.year  = :year  and  courseplan.term  = :term  and  students.status  = 'A'
    and not exists (
    select * from r32
    where r32.year=courseplan.year
    and r32.term=courseplan.term
    and r32.courseno=courseplan.courseno
    and students.studentno=r32.studentno)
) tmp
GROUP BY year,term,courseno,studentno
having count(*) > 1";
        $bind = array_merge($whr[1],array(
            ':year' => $year,
            ':term' => $term,
        ));
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    /**
     * 获取学生端已选的课程列表(噢爱出别修课程)
     * @param $studentno
     * @param $year
     * @param $term
     * @return array|string
     */
    public function getStudentCourseTableListForRemoveList($studentno,$year,$term){
        $sql = "
SELECT  DISTINCT
RTRIM(R32.COURSENO)+RTRIM(R32.[GROUP]) as COURSENOGROUP,
COURSES.COURSENAME,
COURSEPLAN.CREDITS,
SCHEDULEPLAN.LHOURS+SCHEDULEPLAN.EHOURS as WEEKHOURS,--讲课学时+上机学时
COURSEPLAN.COURSETYPE as APPROACH,
COURSEAPPROACHES.[VALUE] as COURSETYPE,
EXAMOPTIONS.[VALUE] as EXAMTYPE,
R32.INPROGRAM as FLAG,
COURSEPLAN.course_type_options as course_type_option,
COURSETYPEOPTIONS.[VALUE] as coursetypeoptionname,
COURSEPLAN.LIMITGROUPNO,
COURSES.[课程介绍] as introduce,
TEACHERS.NAME as TEACHERNONAME
from R32
INNER JOIN STUDENTS on R32.STUDENTNO = STUDENTS.STUDENTNO
INNER JOIN COURSEPLAN on
	COURSEPLAN.COURSENO = R32.COURSENO and COURSEPLAN.[GROUP] = R32.[GROUP] and COURSEPLAN.[YEAR] = R32.[YEAR] and COURSEPLAN.TERM = R32.TERM
INNER JOIN COURSES on R32.COURSENO = COURSES.COURSENO
INNER JOIN COURSEAPPROACHES on COURSEPLAN.COURSETYPE = COURSEAPPROACHES.NAME
INNER JOIN EXAMOPTIONS on COURSEPLAN.EXAMTYPE = EXAMOPTIONS.NAME
INNER JOIN COURSETYPEOPTIONS on COURSETYPEOPTIONS.NAME = COURSEPLAN.course_type_options
LEFT OUTER JOIN SCHEDULEPLAN on SCHEDULEPLAN.[YEAR] = R32.[YEAR] and SCHEDULEPLAN.TERM = R32.TERM
			and SCHEDULEPLAN.COURSENO = R32.COURSENO and SCHEDULEPLAN.[GROUP] = R32.[GROUP]
LEFT OUTER JOIN TEACHERPLAN on TEACHERPLAN.MAP = SCHEDULEPLAN.RECNO
LEFT OUTER JOIN TEACHERS on TEACHERS.TEACHERNO = TEACHERPLAN.TEACHERNO

WHERE R32.[YEAR] = :year and R32.TERM = :term and R32.STUDENTNO = :studentno
and COURSEPLAN.COURSETYPE <> 'M'";//将必修课程排除在外 R32de xiuke fangshi buzhunque
        $bind = array(
            ':year' => $year,
            ':term' =>$term,
            ':studentno'    =>$studentno,
        );
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind));


        if(is_string($rst)){
            return $rst;
        }else{
            return array(
                'total' => count($rst),
                'rows'  => $rst,
            );
        }
    }


    /**
     * R32表中删除学生的选课记录
     * @param $year
     * @param $term
     * @param string $studentno
     * @param string $courseno
     * @param string $groupno 组号可以默认为确实，实际情况一个课号对应该学生的一条选课记录
     * @return boolean|string
     */
    private function removeStudentCourseRecord($year, $term, $studentno, $courseno,$groupno){
        $rst = $this->deleteRecords('R32',array(
            'YEAR'  => $year,
            'TERM'  => $term,
            'COURSENO'  => $courseno,
            'GROUP' => array($groupno,true),
            'STUDENTNO' => $studentno,
        ));
        return $rst;
    }

    /**
     * 删除学生选课
     * @param $whr
     * @return int|string
     */
    public function deleteStduentCourse($whr){
        return $this->deleteRecords('R32',$whr);
    }

    /**
     * @param $year
     * @param $term
     * @param $limitgroupno
     * @return string
     */
    public function getCourseLimitCredit($year,$term,$limitgroupno){
        $sql = 'SELECT LIMITCREDIT from COURSEPLAN WHERE [YEAR] = :year and TERM = :term and LIMITGROUPNO =:limitgroupno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':limitgroupno' => $limitgroupno,
        );
        $rst = $this->sqlQuery($sql,$bind);
        if(false === $rst || !$rst){
//            varsdumpout($sql,$bind);
            return '获取失败！'.$this->getErrorMessage();
        }else{
            return $rst[0]['LIMITCREDIT'];
        }
    }

    /**
     * 获取学生
     *  已选的课程的总学分
     * @param $year
     * @param $term
     * @param $limitgroupno
     * @param $studentno
     * @return float|int|string
     */
    public function getCourseCreditAmoutByLimitGroupno($year,$term,$limitgroupno,$studentno){
        $sql = '
SELECT
	COURSEPLAN.LIMITCREDIT,
	COURSEPLAN.CREDITS
from R32
INNER JOIN STUDENTS on STUDENTS.STUDENTNO = R32.STUDENTNO
INNER JOIN COURSEPLAN
	on  COURSEPLAN.YEAR = R32.[YEAR]
	and COURSEPLAN.TERM = R32.TERM
	and COURSEPLAN.COURSENO = R32.COURSENO
	and COURSEPLAN.CLASSNO = STUDENTS.CLASSNO
	and COURSEPLAN.[GROUP] = R32.[GROUP]
WHERE STUDENTS.STUDENTNO = :studentno
and R32.[YEAR] = :year
and R32.TERM = :term
and COURSEPLAN.LIMITGROUPNO = :limitgroupno';
        $bind = array(
            ':studentno' => $studentno,
            ':year'     => $year,
            ':term'     => $term,
            ':limitgroupno' => $limitgroupno,
        );
        $rst = $this->sqlQuery($sql,$bind);
        $sum = 0;
        if(false === $rst){
            return $this->getErrorMessage();
        }
        foreach($rst as $val){
            $sum += floatval($val['CREDITS']);
        }
        return $sum;
    }

    /**
     * 根据限定组号获取学生已选择的课程的数目
     * 原先的SQL语句是：
     *  select count(*) from R32 r
     *  WHERE r.STUDENTNO=:STUDENTNO AND r.[YEAR]=:R_YEAR AND r.TERM = :R_TERM AND r.COURSENO IN (
     *  select v.COURSENO from VIEWSCHEDULETABLE v WHERE v.[YEAR]=:V_YEAR AND v.TERM=:V_TERM AND v.LIMITGROUPNO=:LIMITGROUPNO)
     *  select count(*) as ROWS
     *  from R32
     *  INNER JOIN VIEWSCHEDULETABLE vst on vst.YEAR = R32.[YEAR] and vst.TERM = R32.TERM and vst.LIMITGROUPNO = :LIMITGROUPNO
     *  WHERE R32.STUDENTNO
     *
     * @param $year
     * @param $term
     * @param string $limitgroupno 限定组号
     * @param $studentno
     * @return string|int string表示错误信息 int表示该学生选择该限定组号的课程的数目
     */
    public function getCourseAmountByLimitgroupno($year,$term,$limitgroupno,$studentno){
        $sql = '
select count(*) as ROWS
from R32
INNER JOIN STUDENTS on R32.STUDENTNO = STUDENTS.STUDENTNO
INNER JOIN COURSEPLAN
    on  COURSEPLAN.YEAR = R32.[YEAR]
	and COURSEPLAN.TERM = R32.TERM
	and COURSEPLAN.COURSENO = R32.COURSENO
	and COURSEPLAN.CLASSNO = STUDENTS.CLASSNO
	and COURSEPLAN.[GROUP] = R32.[GROUP]
WHERE R32.STUDENTNO = :STUDENTNO
and COURSEPLAN.LIMITGROUPNO = :LIMITGROUPNO
and R32.[YEAR] = :YEAR
and R32.TERM = :TERM';
        $bind = array(
            ':STUDENTNO' => $studentno,
            ':LIMITGROUPNO' => $limitgroupno,
            ':YEAR' => $year,
            ':TERM' => $term,
        );
        $rst = $this->sqlQuery($sql,$bind);
        if(false === $rst){
            return $this->getErrorMessage();
        }elseif(!$rst or count($rst) > 1){
            return "获取第[$year]学年第[$term]学期学号为[$studentno]，限制组号为[$limitgroupno]的数量异常！";
        }
        return $rst[0]['ROWS'];
    }

    /**
     * 获取导出Excel的数据
     * @param string $fielter 课程类型过滤器
     * @param $bind
     * @return mixed
     */
    public function getCourseplanExcelSourse($fielter,$bind){
        $sql = "
SELECT Dbo_courseplan.YEAR,
    Dbo_courseplan.TERM,
    Dbo_courseplan.COURSENO,
    Dbo_courseplan.[GROUP],

    dbo.getone(RTRIM(Dbo_courses.COURSENAME)) as COURSENAME,
    dbo.GROUP_CONCAT(RTRIM(Dbo_classes.CLASSNO),',') as CLASSNO,
    dbo.GROUP_CONCAT(RTRIM(Dbo_classes.CLASSNAME),',') as CLASSNAME,
    dbo.getone(RTRIM(Dbo_courses.CREDITS)) as CREDITS,
    dbo.getone(RTRIM(Dbo_courses.HOURS)) as HOURS,
    dbo.getone(RTRIM(Dbo_courseplan.WEEKS)) as WEEKS,
    dbo.getone(RTRIM(Dbo_courseapproaches.VALUE)) AS COURSETYPE,
    dbo.getone(RTRIM(Dbo_courseplan.COURSETYPE)) AS TYPE,
    dbo.getone(RTRIM(Dbo_examoptions.VALUE)) AS EXAMTYPE,
    dbo.getone(RTRIM(Dbo_courseplan.EXAMTYPE)) AS EXAM,
    dbo.getone(RTRIM(Dbo_courseplan.ATTENDENTS)) as ATTENDENTS,
    dbo.getone(RTRIM(Dbo_schools.NAME)) AS SCHOOLNAME,
    dbo.getone(RTRIM(Dbo_courseplan.SCHOOL)) AS SCHOOL,
    dbo.getone(RTRIM(Dbo_courseplan.REM)) AS REM,
    dbo.getone(RTRIM(Dbo_classes.SCHOOL)) AS CLASSSCHOOL
FROM dbo.COURSEPLAN Dbo_courseplan,
    dbo.COURSEAPPROACHES Dbo_courseapproaches,
    dbo.EXAMOPTIONS Dbo_examoptions,
    dbo.SCHOOLS Dbo_schools,
    dbo.COURSES Dbo_courses,
    dbo.CLASSES Dbo_classes
WHERE  (Dbo_courseplan.COURSENO = Dbo_courses.COURSENO)
   AND  (Dbo_courseplan.SCHOOL = Dbo_schools.SCHOOL)
   AND  (Dbo_courseplan.COURSETYPE = Dbo_courseapproaches.NAME)
   AND  (Dbo_courseplan.EXAMTYPE = Dbo_examoptions.NAME)
   AND (Dbo_courseplan.CLASSNO =Dbo_classes.CLASSNO)
   AND (Dbo_courseplan.YEAR=:year)
   AND (Dbo_courseplan.TERM=:term)
   AND (Dbo_courseplan.COURSENO LIKE :courseno)
   AND (Dbo_courseplan.[GROUP] LIKE :groupno)
   AND (Dbo_courseplan.SCHOOL LIKE :school)
   AND (Dbo_courseplan.COURSETYPE LIKE :approach)
   AND (Dbo_courseplan.CLASSNO LIKE :classno)
   AND (Dbo_courseplan.EXAMTYPE LIKE :examtype)
   and ($fielter)
GROUP BY Dbo_courseplan.[YEAR],Dbo_courseplan.TERM,Dbo_courseplan.COURSENO,Dbo_courseplan.[GROUP]";
        $rst = $this->sqlQuery($sql,$bind);
//        vardump($rst,$sql,$bind,$this->getDbError());
        return $rst;
    }




    /**
     * 从用户的请求中获取课程信息插入到学生的修课计划中
     * @param $year
     * @param $term
     * @param $studentno
     * @param $data
     * @param $fee
     * @return string
     */
    public function insertStudentCourse($year,$term,$studentno,$data,$fee){
        $courseno = substr($data['COURSENOGROUP'],0,7);
        $groupno  = substr($data['COURSENOGROUP'],7);
        $examtype = $data['EXAM']; //考试类型
        $limitGroupno = $data['LIMITGROUPNO']; //限选组号
        $limitNum = $data['LIMITNUM']; //限选条数，前端传入，不可信

        $conflicts = 0;//判断这门课程是否与学生现有的教学课程计划相冲突
        $inprogram = 0;//判断课程是否在教学计划内部
        $coursetype = null;

        //获取课程类型
        $courseplanModel = new CoursePlanModel();
        $coursePlanInfo = $courseplanModel->getCourseplanByStudent($year,$term,$courseno,$groupno,$studentno);
        if(is_string($coursePlanInfo)){
            return "获取失败！";
        }
        $category = $coursePlanInfo['course_type_options'];



        //确认该学生该课程是否重修
        $checkRepeat = $this->getTable('R32',array(
            'STUDENTNO' => $studentno,
            'COURSENO'  => $courseno,
        ),true);
        if(is_string($checkRepeat)){
            return "确认学号为[$studentno]，课程号为[$courseno]的课程记录是否是重修出错！{$checkRepeat}";
        }elseif($checkRepeat){//是重修,不收费
            $fee = 0;
        }

        //限定条件判断，已经移到控制器中
        $coursePlanModel = new CoursePlanModel();
        $rst = $coursePlanModel->getLimitCondition($year,$term,$courseno,$groupno);
        if(is_string($rst)){
            return $rst;
        }
        $limitNum = $rst['LIMITNUM'];
        $limitCredit = floatval($rst['LIMITCREDIT']);//'.0'的布尔值判断是 true

        //判断是否有限选条件，先选组号大于0 即限选
        if($limitGroupno){
            if($limitNum){
                //限选条数不为0，判断限选条数
                $courseChoosedNum = $this->getCourseAmountByLimitgroupno($year,$term,$limitGroupno,$studentno);
                if(is_string($courseChoosedNum)){
                    return $courseChoosedNum;
                }elseif($courseChoosedNum >= $limitNum ){//大于等于限定条数，无法再选择
                    return "因第[$year]学年第[$term]学期学号为[$studentno]的学生已经有[$limitNum]门限定组号为[$limitGroupno]的课程，该限定组号的条数限制是[$limitNum]，添加失败！";
                }
            }elseif($limitCredit){
                //限选条数为0，限选学分不为0，以限选学分判断
                $courseChoosedCreditSum = $this->getCourseCreditAmoutByLimitGroupno($year,$term,$limitGroupno,$studentno);//获取已选课程的总学分
                if(is_string($courseChoosedCreditSum)){
                    return "获取学号为[$studentno]的学生第[$year]学年第[$term]学期限选组号为[$limitGroupno]的课程的总学分出现错误！".$courseChoosedCreditSum;
                }
                //获取这门课程的学分和限选总学分信息
                $courseplan = $this->getCoursePlan($year,$term,$courseno,$groupno);
                if( is_string($courseplan) or !$courseplan){
                    return "获取第[$year]学年第[$term]学期课号加组号为[$courseno-$groupno]的开课计划失败！".$courseplan;
                }elseif($courseplan[0]['CREDITS'] + $courseChoosedCreditSum > $courseplan[0]['LIMITCREDIT']){
                    return "课程号组号为[$courseno-$groupno]的课程的学分是[{$courseplan[0]['CREDITS']}]，学号为[$studentno]的学生已选择的总学分为[$courseChoosedCreditSum]，无法超出限制学分为[{$courseplan[0]['LIMITCREDIT']}]的限制！";
                }
            }else{
                //限选条数和限选学分都是0，意味着教学计划中的可以任意选
            }
        }


        //判断课程是否已经锁定
        $schduleModel = new SchedulePlanModel();
        $rst = $schduleModel->getSchedulePlan(array(
            'YEAR' => $year,
            'TERM' => $term,
            'COURSENO' => $courseno,
            'GROUP' => array($groupno,true),//进行转义
        ));
        if(is_string($rst)) return '获取教学计划相关信息失败！'.$rst;
        if($rst[0]['LOCK']==1){
            return "选定的课程号[$courseno-$groupno]已经上锁！\n";
        }

        //判断课程的总人数是否超过限制
       // if(  $rst[0]['ESTIMATE'] <= $rst[0]['ATTENDENTS']){ //同步课表ESTIMATE字段没有更新？此处检测会有问题
        //    return "选定的课程号[$courseno-$groupno]的课程预定人数为[{$rst[0]['ESTIMATE']}],已选择的人数为[{$rst[0]['ATTENDENTS']}],选课人数已达到上限！\n";
        //}

        //----------------------------------------------------------------------------//
        //------- 判断该学生是否可以选择该课程 160114A -----------------------------------//
        //----------------------------------------------------------------------------//
        //判断是否超出班级限定人数
        // 步骤：①从R32表中获取该班级中已经选择了这门课的学生的数目'NUM01' ②比较'NUM01'与‘$coursePlanInfo['ATTENDENTS']’
        $attendednum = $schduleModel->getAttendentsOfClassByStudentno($year,$term,$courseno,$groupno,$studentno);
        if(is_string($attendednum)) return "获取该学生所在班级已选人数失败！{$attendednum}";
        if($attendednum >= $coursePlanInfo['ATTENDENTS'] ){
            return "学号为[$studentno]的学生所在的班级的课号组号为[$courseno-$groupno]的课程的人数为[$attendednum]，该课程的限制人数是[{$coursePlanInfo['ATTENDENTS']}]";
        }
        //判断是否超出教学班总的限定人数
        $attendedrealnum = $schduleModel->getAttendentsOfClassByCoursegroup($year,$term,$courseno,$groupno);
        if(is_string($attendedrealnum)) return "获取该学生所在班级已选人数失败！{$attendedrealnum}";
        $total_attendents_limit = intval($coursePlanInfo['total_attendents_limit']);
        if($total_attendents_limit and $attendedrealnum >= $total_attendents_limit ){//人数限制为0时表示不作限制
            return "学号为[$studentno]的学生所选的课号组号为[$courseno-$groupno]的课程的教学班已选人数为[$attendedrealnum]，该课程的限制人数是[{$total_attendents_limit}]";
        }
        //----------------------------------------------------------------------------//
        //------- 判断该学生是否可以选择该课程 160224 ------------------------------------//
        //----------------------------------------------------------------------------//
//        $courses = $courseplanModel->getCoursePlan(array(
//            'year'  => $year,
//            'term'  => $term,
//            'courseno'  => $courseno,
//            'group'   => array($groupno,true),
//        ));
//        if(is_string($courses)){
//            return "查询课程类型失败".$courses;
//        }elseif(empty($courses)){
//            return '不存在这门课程';
//        }
//        $courseplanRecord = $courses[0];
//        if(in_array($courseplanRecord['course_type_options'],array('I','J'))){
//            //如果是社团课或者通识课,判断该学生是否已经选了这类课程
//            $sql = '
//SELECT cp.COURSENO+cp.[GROUP] as coursegroup from R32 r
//INNER JOIN STUDENTS sts on sts.STUDENTNO = r.STUDENTNO
//INNER JOIN COURSEPLAN cp on cp.[YEAR] = r.[YEAR] and cp.[GROUP] = r.[GROUP] and cp.COURSENO = r.COURSENO
//  and cp.[GROUP] = r.[GROUP] and cp.CLASSNO = sts.CLASSNO
//WHERE r.[YEAR] = :year and r.TERM = :term and r.STUDENTNO = :studentno and cp.course_type_options = :coursetype';
//            $rst = $this->doneQuery($this->sqlQuery($sql,array(
//                ':year' => $year,
//                ':term' => $term,
//                ':studentno' => $studentno,
//                ':coursetype' => $courseplanRecord['course_type_options'],
//            )),false);//只返回第一条数据
//            if(is_string($rst) ){
//                return "无法得知该学生选择的通识课或者社团课的数目！{$rst}";
//            }
//            if(!empty($rst)){
//                //数组非空，说明该学生已经选了这类课程
//                return "该学生已经选择了一门通识课或者社团课，课号为{$rst['coursegroup']}";
//            }
//        }
        //----------------------------------------------------------------------------//
        //----------------------------------------------------------------------------//


        //检查学生的课程时间安排 是否与 将选择的课程有冲突
        $timelistModel = new TimelistModel();
        $studentTimelist = $timelistModel->getTimelist($year,$term,$studentno,'S');
        $coursesTimelist  = $timelistModel->getTimelist($year,$term,$courseno,'P',$groupno);
        if(TimelistModel::checkConflict($studentTimelist,$coursesTimelist)){
            $conflicts = 1;
        }

        $this->startTrans();
        //获取课程类型和考试类型
        $rst = $this->getProgramCourseByCoursenoAndStudentno($courseno,$studentno);
        if(is_string($rst)){
            $this->rollback();
            return "查询学号为[$studentno]的学生，课程号为[$courseno]的修学课程失败！$rst";
        }elseif(count($rst) > 1){
            $this->rollback();
            return "学号为[$studentno]的学生在第[$year]学期第[$term]学期的课程号为[$courseno]的课程对应了".count($rst)."条记录！";
        }elseif($rst){
            $inprogram=1;
            $coursetype = $rst[0]['COURSETYPE'];
//            $category = $rst[0]['CATEGORY'];
            $examtype = $rst[0]['EXAMTYPE'];
        }else{//不在教学计划内部，则认定为选修
            $coursetype = 'E';//courseapproches
        }
//var_dump($rst);
        //添加到学生的修课计划中
        $rst = $this->createRecord('R32',array(
            'YEAR'  => $year,
            'TERM'  => $term,
            'COURSENO'  => $courseno,
            'GROUP' => array($groupno,true),
            'STUDENTNO' => $studentno,
            'INPROGRAM' =>$inprogram,
            'CONFLICTS' =>$conflicts,
            'REPEAT'    => $checkRepeat,
            'FEE'   => $fee,
            'APPROACH'  =>$coursetype,//修课方式
            'COURSETYPE'    => $category,//课程类别
            'EXAMTYPE'      => $examtype,
        ));
        if(is_string($rst) or !$rst){
            $this->rollback();
            if(strtolower(trim($coursetype)) === 'M'){
                return '无法选择必修课!';
            }
            return '添加到学生的修课计划的过程中出现错误！'.$rst;
        }

        //更新修课计划人数
        $schedulePlanModel = new SchedulePlanModel();
        $rst = $schedulePlanModel->increaseSchedulePlanAttendents($year,$term,$courseno,$groupno);
        if(is_string($rst) or !$rst){
            $this->rollback();
            return '更新修课计划人数失败!'.$rst;
        }

        //更新时间总表
        $rst = $timelistModel->deleteTimeList($year,$term,$studentno,'S');
        if(is_string($rst)){
            $this->rollback();
            return "无法删除第[$year]学年第[$term]学期学生号为[$studentno]的学生时间安排记录！$rst";
        }
        $rst = $timelistModel->getStudentCourseTimelist($year,$term,$studentno);
        if(is_string($rst) or !$rst){
            $this->rollback();
            return "无法获取第[$year]学年第[$term]学期学生号为[$studentno]的学生时间安排记录！$rst";
        }
        $rst = $timelistModel->createTimelistRecord(array(
            'YEAR' => $year,
            'TERM' => $term,
            'WHO' => $studentno,
            'TYPE' => 'S',
            'PARA' => '',
            'MON' => $rst['MON'],
            'TUE' => $rst['TUE'],
            'WES' => $rst['WES'],
            'THU' => $rst['THU'],
            'FRI' => $rst['FRI'],
            'SAT' => $rst['SAT'],
            'SUN' => $rst['SUN'],
        ));
        if(is_string($rst) or !$rst){
            $this->rollback();
            return "重新创建学号为[$studentno]的学生的第[$year]学年第[$term]学期的时间安排记录失败了！$rst";
        }
        //刷新人数
//        $rst = $this->refreshCourseSelectionAttendent($year,$term);
//        if(is_string($rst)){
//            return $rst;
//        }

        $rst = $this->updateStudentClassAttendent($year,$term,$data['COURSENOGROUP'],$studentno,true);
        if(is_string($rst)){
            return $rst;
        }

        $this->commit();
        return '';
    }

    /**
     * 彻底删除学生的选课记录
     * @param int $year
     * @param int $term
     * @param string $studentno 学号
     * @param string $courseno 课程号，默认为9位
     * @param null|string  $groupno 组号，默认为NULL，此时组号包含在$courseno内
     * @return bool|string true表示成功删除学生对应的选课记录 string表示发生了错误
     */
    public function removeStudentCourse($year,$term,$studentno,$courseno,$groupno=null){
        if(NULL === $groupno){
            $groupno = substr($courseno,7);
            $courseno = substr($courseno,0,7);
        }

        //查看是否锁定
        $lock = $this->checkCourseLock($year,$term,$courseno,$groupno);
        if(is_string($lock)){
            return $lock;
        }elseif($lock){
            return "课程号组号为[{$courseno}-{$groupno}]的课程已经锁定!";
        }

        $this->startTrans();
        //从学生修课记录中删除
        $rst = $this->removeStudentCourseRecord($year, $term, $studentno, $courseno,$groupno);
        if(is_string($rst) or !$rst){
            $this->rollback();
            return "删除学号为[{$studentno}]的学生的课号组号为[$courseno - $groupno]的课程删除失败！".$rst;
        }

        //更新已选人数
        $schedulePlanModel = new SchedulePlanModel();
        $rst = $schedulePlanModel->decreaseSchedulePlanAttendents($year,$term,$courseno,$groupno);
        if(is_string($rst) or !$rst){
            $this->rollback();
            return "减少第[$year]学年第[$term]学期课程号组号为[$courseno - $groupno]的排课计划人数修改失败！{$rst}";
        }

        //删除现有的学生时间表，不直接update是考虑到可能不存在学生的这条记录
        $timelistModel = new TimelistModel();
        $rst = $timelistModel->deleteTimeList($year,$term,$studentno,'S');
        if(is_string($rst)){
            $this->rollback();
            return "删除第[$year]学年第[$term]学期学生号为[$studentno]的时间记录失败！{$rst}";
        }
        //获取学生已选的课程的时间记录积
        $coursesTimelist = $timelistModel->getStudentCourseTimelist($year,$term,$studentno);
        if(is_string($coursesTimelist) or !$coursesTimelist){//!$coursesTimelist 获取空数组的情况一般不会发生，即使学生已选的课程数目是0
            $this->rollback();
            return "获取第[$year]学年第[$term]学期学生号为[$studentno]的已选课程的时间记录失败！{$coursesTimelist}";
        }
        //重新创建学生的时间记录
        $rst = $timelistModel->createTimelistRecord(array(
            'YEAR' => $year,
            'TERM' => $term,
            'WHO'  => $studentno,
            'TYPE' => 'S',
            'PARA' => '',
            'MON' => $coursesTimelist['MON'],
            'TUE' => $coursesTimelist['TUE'],
            'WES' => $coursesTimelist['WES'],
            'THU' => $coursesTimelist['THU'],
            'FRI' => $coursesTimelist['FRI'],
            'SAT' => $coursesTimelist['SAT'],
            'SUN' => $coursesTimelist['SUN'],
        ));
        if(is_string($rst) or !$rst){
            $this->rollback();
            return "创建第[$year]学年第[$term]学期学生号为[$studentno]的时间记录失败！{$rst}";
        }
        //刷新人数
//        $rst = $this->refreshCourseSelectionAttendent($year,$term);
//        if(is_string($rst)){
//            return $rst;
//        }
        $rst = $this->updateStudentClassAttendent($year,$term,$courseno.$groupno,$studentno,true);
        if(is_string($rst)){
            return $rst;
        }

        $this->commit();
        return true;
    }

    /**
     * 查看课程是否被锁定
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @return boolean|string string表示查询发生了错误
     */
    public function checkCourseLock($year,$term,$courseno,$groupno){
        $rst = $this->getTable('SCHEDULEPLAN',array(
            'YEAR'  => $year,
            'TERM'  => $term,
            'COURSENO'  => $courseno,
            array('GROUP',$groupno,'=',true),
        ));
        if(is_string($rst)){
            return $rst;
        }
        return  $rst?$rst[0]['LOCK']:false;
    }


    /**
     * 获取课程类别一的下拉鞋标数据
     * @return array|string
     */
    public function _getCourseTypeOptionsOfCommon(){
        $sql = "SELECT RTRIM(NAME) AS CODE,RTRIM(VALUE) AS NAME
                FROM COURSETYPEOPTIONS WHERE NAME NOT IN ('I','J')
                ORDER BY CODE";
        return $this->doneQuery($this->sqlQuery($sql));
    }

    /**
     * 获取学生绑定的教学计划下的课程
     * 可能一个学生对应不同的教学计划中有同样的课程，这个将留给调用者作判断
     * @param $courseno
     * @param $studentno
     * @return array|string
     */
    public function getProgramCourseByCoursenoAndStudentno($courseno,$studentno){
        $sql = 'SELECT * from R12
INNER JOIN R28 on R28.PROGRAMNO = R12.PROGRAMNO
WHERE R28.STUDENTNO = :STUDENTNO
AND R12.COURSENO = :COURSENO';
        $bind = array(
            ':STUDENTNO'=>$studentno,
            ':COURSENO'=>$courseno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }


    public function getCourseStudentTableList($coursenogroupno,$year,$term){
        $sql = '
SELECT
STUDENTS.STUDENTNO as xh, --学号
STUDENTS.NAME as xm,--姓名
SexCode.NAME as xb,--性别
CLASSES.CLASSNAME as xsbj,--班级名称
CLASSES.CLASSNO as bh,--班级号
student_courses.course_choosed_credit_sum as zxf,--学生已选课程的总学分
student_courses.course_choosed_num as xkms,--学生已选课程的门数
R32.CONFLICTS as ct,--冲突
COURSEAPPROACHES.[VALUE] as xkfs,--修课方式
EXAMOPTIONS.[VALUE] as kh2--考核方式
FROM R32
INNER JOIN STUDENTS on R32.STUDENTNO = STUDENTS.STUDENTNO
INNER JOIN CLASSES on CLASSES.CLASSNO = STUDENTS.CLASSNO
INNER JOIN SexCode on STUDENTS.SEX = SexCode.CODE
LEFT OUTER JOIN COURSEAPPROACHES on R32.APPROACH = COURSEAPPROACHES.NAME
INNER JOIN EXAMOPTIONS on R32.EXAMTYPE = EXAMOPTIONS.NAME
INNER JOIN (
	SELECT
dbo.getone(R32.[GROUP]) as [GROUP] ,
R32.COURSENO,
R32.[YEAR],R32.TERM,R32.STUDENTNO,
count(*) as course_choosed_num ,
sum(COURSEPLAN.CREDITS) as course_choosed_credit_sum
from R32
INNER JOIN COURSEPLAN on R32.[YEAR] = COURSEPLAN.[YEAR] and R32.TERM = COURSEPLAN.TERM and R32.COURSENO = COURSEPLAN.COURSENO
and R32.[GROUP] = COURSEPLAN.[GROUP]
 GROUP BY R32.STUDENTNO,R32.COURSENO,R32.[YEAR],R32.TERM
) student_courses on student_courses.[year] = R32.[YEAR] and student_courses.[term] = R32.[TERM]
	and student_courses.courseno = R32.COURSENO and student_courses.studentno = R32.STUDENTNO
WHERE  RTRIM(R32.COURSENO)+RTRIM(R32.[GROUP]) = :coursenogroupno
and R32.[YEAR] = :year and R32.TERM = :term';
        $bind = array(
            ':coursenogroupno'  => $coursenogroupno,
            ':year'             => $year,
            ':term'             => $term,
        );
        $json = array();
        $rst = $this->sqlQuery($sql,$bind);
//        vardump($rst,$sql,$bind,$this->getErrorMessage());
        $json['total'] = count($rst);
        $json['rows'] = $rst;
        return $json;
    }



}