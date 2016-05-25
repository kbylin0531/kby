<?php
/**
 * Created by PhpStorm.
 * User: Lin
 * Date: 2015/7/6
 * Time: 16:22
 */

/**
 * 开课计划数据IO模型
 * Class CoursePlanModel
 */
class CoursePlanModel extends CommonModel{

    private static $spliter = '#';


    public function listScheduleplanTeachersByTeacherplanMap($map,$limit=null,$offset=null){
        $fields = '
tp.RECNO,
tp.TEACHERNO,
RTRIM(tp.REM) as REM,
tp.AMOUNT,
RTRIM(TEACHERS.NAME) as TEACHERSNAME,
RTRIM(POSITIONS.[VALUE]) as POSITIONSNAME,
tp.HOURS,
RTRIM(UNITOPTIONS.NAME) as UNITOPTIONSNAME,
UNITOPTIONS.CODE as UNIT,
RTRIM(TASKOPTIONS.NAME) as TASKOPTIONSNAME,
TASKOPTIONS.CODE as TASK,
tp.TOSCHEDULE,
CASE
	WHEN tp.TOSCHEDULE=1 THEN \'是\'
	WHEN tp.TOSCHEDULE=0 THEN \'否\'
	ELSE \'\' end as ZOVALUE,
RTRIM(SCHOOLS.NAME) as SCHOOLNAME';
        $join = '
INNER JOIN TEACHERS on TEACHERS.TEACHERNO = tp.TEACHERNO
LEFT OUTER JOIN POSITIONS on TEACHERS.[POSITION] = POSITIONS.NAME -- 职务
LEFT OUTER JOIN UNITOPTIONS on UNITOPTIONS.CODE =  tp.UNIT -- 课时
LEFT OUTER JOIN TASKOPTIONS on TASKOPTIONS.NAME =  tp.TASK -- 课堂任务
LEFT OUTER JOIN SCHOOLS on SCHOOLS.SCHOOL = TEACHERS.SCHOOL';
        $where = 'tp.MAP = :map';
        return $this->getTableList(
            $this->makeCountSql('TEACHERPLAN tp',array(
                'join'      => $join,
                'where'     => $where,
            )),
            $this->makeSql('TEACHERPLAN tp',array(
                'fields'    => $fields,
                'join'      => $join,
                'where'     => $where,
                ),$limit,$offset),
            array(
                ':map'  => $map,
            )
        );
    }

    /**
     * 根据sessionID获取教师信息
     * @param $sessionid
     * @return mixed
     */
    public function getTeacherFromSession($sessionid){
        $sql = '
SELECT TEACHERS.SCHOOL,TEACHERS.TGROUP,USERS.USERNAME,USERS.ROLES,SCHOOLS.NAME AS SCHOOLNAME,S1.NAME AS TGROUPNAME FROM TEACHERS
JOIN USERS ON TEACHERS.TEACHERNO=USERS.TEACHERNO
JOIN SESSIONS ON USERS.USERNAME=SESSIONS.USERNAME
left join SCHOOLS on SCHOOLS.SCHOOL=TEACHERS.SCHOOL
left join SCHOOLS S1 on S1.SCHOOL=TEACHERS.TGROUP
WHERE SESSIONS.SESSIONID=:SESSIONID';
        return $this->sqlFind($sql, array(":SESSIONID"=>$sessionid ));
    }

    /**
     * 检查课号是否存在，当第二个参数是非NULL时检查是否是该学院的
     * @param $courseno
     * @return mixed
     * @throws Exception
     */
    public function getCourseSchool($courseno){
        $bind = array(':COURSENO'=>$courseno);
        $data = $this->sqlQuery("SELECT SCHOOL FROM COURSES WHERE COURSENO=:COURSENO",
            $bind);
        if($data === false){
            throw new Exception('查询过程中出现错误:'.$this->getDbError());
        }elseif(empty($data)){
            return false;
        }else{
            return $data[0]['SCHOOL'];
        }
    }

    /**
     * 修改开课计划人数
     * @param  $whr
     * @param null $attend
     * @param null $totalAttendance
     * @return int|string
     * @throws Exception
     */
    public function updateCoursePlanAttendance($whr,$attend=null,$totalAttendance=null){
        $fields = array();
        isset($attend) and $fields['ATTENDENTS'] = $attend;
        isset($totalAttendance) and $fields['total_attendents_limit'] = $totalAttendance;
        if(empty($fields)) throw new Exception('检测到修改！');

        return $this->updateRecords('COURSEPLAN',$fields,$whr);
    }

    /**
     * 根据年份更新所有班级的 年级
     * bug:对于已经毕业的班级年份也会增加。。。
     * @param integer  $year 当前年份
     * @return string|integer
     */
    public function updateClassesGrade($year){
        $sql = 'UPDATE CLASSES set GRADE=:newyear -DATENAME(YEAR,[YEAR])+1';
        $bind = array(
            ':newyear'=>intval($year),
        );
        $rst = $this->sqlExecute($sql,$bind);
//        vardump($rst,$sql,$bind);
        return $this->doneExecute($rst);
    }

    /**
     * 将开课计划导入到排课计划中
     * @param $map
     * @return int|string
     */
    public function dumpCoursePlanIntoSchedulePlan($map){
        $insertPart = 'INSERT INTO SCHEDULEPLAN ([YEAR], TERM, COURSENO, [GROUP],ESTIMATE, ROOMTYPE, LHOURS, EHOURS,CHOURS,  SHOURS, KHOURS,ZHOURS, WEEKS, EXAM)';
        $fields = "
[YEAR], TERM, COURSEPLAN.COURSENO, [GROUP], SUM(ATTENDENTS),
 CASE [TYPE]   WHEN 'D' THEN 'B'  WHEN 'F' THEN 'P'  WHEN 'B' THEN 'B'  ELSE 'U'  END AS ROOMTYPE,
 (COURSES.HOURS-COURSES.EXPERIMENTS-COURSES.COMPUTING-COURSES.KHOURS-COURSES.SHOURS-COURSES.ZHOURS) AS LHOURS,
 COURSES.EXPERIMENTS,  COURSES.COMPUTING, COURSES.SHOURS, COURSES.KHOURS, COURSES.ZHOURS,  COURSEPLAN.WEEKS,
 CASE [EXAMTYPE]  WHEN 'T' THEN 1  WHEN 'E' THEN 0  ELSE 0  END AS EXAM ";
        $join = ' INNER JOIN COURSES on COURSES.COURSENO = COURSEPLAN.COURSENO ';
        $group = ' year,term,courseplan.courseno,[group],courses.hours,type,courses.experiments,courses.computing,
 courses.shours,courses.khours,courses.zhours,courseplan.weeks,courseplan.examtype ';
        $whrArr = $this->makeWhere($map);
        $sql = $this->makeSql('COURSEPLAN',array(
            'distinct'=>'distinct',
            'fields'=>$fields,
            'join'=>$join,
            'group'=>$group,
            'where'=>$whrArr[0],
        ));
        $rst = $this->sqlExecute($insertPart.$sql,$whrArr[1]);
        return $this->doneExecute($rst);

    }



    /**
     * 清空指定学年学期和课程类别的课程
     * @param integer $year 学年
     * @param integer $term 学期
     * @param string $grade
     * @param string $schoolno
     * @param string $classno
     * @param string|null|array $coursetype 值为null此时会清空所有的数据,为数组时绑定该课程
     * @param bool $flag 决定删除指定的数组还是指定以外的部分
     * @return int|string  返回受影响行数目
     */
    public function clearCoursePlan($year,$term,$grade='%',$schoolno='%',$classno='%',$coursetype,$flag = true){
        if(!is_numeric($year) || !is_numeric($term)){
            return false;
        }
        $sql = '
DELETE FROM COURSEPLAN WHERE COURSEPLAN.[YEAR] = :yeara and COURSEPLAN.TERM = :terma
and COURSEPLAN.CLASSNO in
(
	SELECT DISTINCT COURSEPLAN.CLASSNO from COURSEPLAN
	INNER JOIN CLASSES on COURSEPLAN.CLASSNO = CLASSES.CLASSNO
	WHERE  COURSEPLAN.[YEAR] = :yearb and COURSEPLAN.TERM = :termb AND
    YEAR(CLASSES.[YEAR]) like :grade
    and CLASSES.SCHOOL like :school
    and CLASSES.CLASSNO like :classno
)';
        $bind = array(
            ':yeara'     =>$year,
            ':terma'     =>$term,
            ':yearb'     =>$year,
            ':termb'     =>$term,
            ':grade'    => $grade,
            ':school'   => $schoolno,
            ':classno'  => $classno,
        );

        $sql .= $this->makeFilter('course_type_options',$coursetype,$flag);
        
//         var_dump($sql);

        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 对指定的课程执行开课
     * @param mixed $types string|array 指定制定开课计划的课程类型
     * @param string $grade
     * @param string $schoolno
     * @param string $classno
     * @param boolean $flag true，将第一个参数指定的课程类型执行开课计划
     *                      false，将第一个参数指定的课程类型外的全部执行开课计划
     * @return mixed
     * @throws Exception
     */
    public function startCoursePlan($types,$grade='%',$schoolno='%',$classno='%',$approach='%',$flag = true){
        $insertPart = '
INSERT INTO COURSEPLAN
([YEAR],TERM,COURSENO,CLASSNO,SCHOOL,WEEKS,[GROUP],COURSETYPE,EXAMTYPE,ATTENDENTS,LIMITGROUPNO,LIMITNUM,CREDITS,course_type_options,LIMITCREDIT) ';
        $searchPart ='
SELECT DISTINCT
:COL_YEAR as [YEAR],
:COL_TERM as TERM,
Dbo_r12.COURSENO as COURSENO,
Dbo_r16.CLASSNO as CLASSNO,
Dbo_courses.SCHOOL as SCHOOL,
:COL_WEEKS as WEEKS,
----------01start
dbo.fn_10to36(DENSE_RANK() over(PARTITION BY Dbo_r12.COURSENO order by Dbo_r16.CLASSNO,Dbo_r16.PROGRAMNO)+dbo.fn_36to10(cp.[group])) as [GROUP],
Dbo_r12.COURSETYPE as COURSETYPE,
Dbo_r12.EXAMTYPE as EXAMTYPE,
Dbo_classes.STUDENTS as ATTENDENTS,
Dbo_r12.LIMITGROUPNO,
Dbo_r12.LIMITNUM,
Dbo_r12.CREDITS,
Dbo_r12.CATEGORY as coursetypeoption,
Dbo_r12.LIMITCREDIT as LIMITCREDIT
FROM dbo.R12 Dbo_r12
INNER JOIN dbo.PROGRAMS Dbo_Programs on  Dbo_Programs.ProgramNo=Dbo_r12.ProgramNo
INNER JOIN dbo.COURSES Dbo_courses on Dbo_r12.COURSENO = Dbo_courses.COURSENO
INNER JOIN dbo.CLASSES Dbo_classes on  Dbo_r12.YEAR = Dbo_Classes.Grade
INNER JOIN dbo.R16 Dbo_r16 on Dbo_r16.CLASSNO = Dbo_classes.CLASSNO and Dbo_r16.PROGRAMNO = Dbo_r12.PROGRAMNO

------------changed by zqq @ 2016.3.26 adding fetch max group from courseplan ,if null then 0
left join (select distinct YEAR,TERM,COURSENO,MAX([GROUP]) as [group] from COURSEPLAN group by [YEAR],TERM,COURSENO) cp
           on cp.[YEAR] = YEAR(Dbo_classes.[YEAR]) and cp.TERM = Dbo_r12.TERM and cp.COURSENO = Dbo_r12.COURSENO
where Dbo_r12.TERM=:TERM
and YEAR(Dbo_classes.[YEAR]) like :grade
and Dbo_classes.SCHOOL like :school
and Dbo_classes.CLASSNO like :classno
and Dbo_r12.coursetype like :approach
and not EXISTS (
SELECT 1 from COURSEPLAN cp 
WHERE 
	    cp.[YEAR] = :COL_YEAR2 
	and cp.term = :COL_TERM2
	and cp.COURSENO = Dbo_r12.COURSENO 
	and cp.CLASSNO = Dbo_classes.CLASSNO
 )';
        $filter = $this->makeFilter('Dbo_r12.CATEGORY',$types,$flag);

        $bind = array(
            ':COL_YEAR'=>intval($_REQUEST['YEAR']),
            ':COL_TERM'=>intval($_REQUEST['TERM']),
            ':COL_WEEKS'=>bindec(str_replace('0','1',cwebsSchedule::zero('0',intval($_REQUEST['WEEKS'])))),
            ':TERM'=>intval($_REQUEST['TERM']),
            ':grade'    => $grade,
            ':school'   => $schoolno,
            ':classno'  => $classno,
            ':approach' => $approach,
            ':COL_YEAR2'=>intval($_REQUEST['YEAR']),
            ':COL_TERM2'=>intval($_REQUEST['TERM']),
        );

        $sql = " {$insertPart} {$searchPart} {$filter} ";
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 获取单条开课计划 原始数据（未join的数据）
     * @param array $map 主键构成的集合
     * @param boolean $count 是否只计算数量
     * @return array|int|string
     */
    public function getCoursePlan($map,$count=false){
        return $this->getTable('COURSEPLAN',$map,$count);
    }


    public function getCourseplanByStudent($year,$term,$courseno,$group,$studentno){
        $sql = '
SELECT COURSEPLAN.* from COURSEPLAN
INNER JOIN STUDENTS on STUDENTS.CLASSNO = COURSEPLAN.CLASSNO
WHERE year = :year  and term = :term and COURSENO = :courseno and [GROUP] = :group and STUDENTNO = :studentno';
        return $this->doneQuery($this->sqlQuery($sql,array(
            ':year' => $year,
            ':term' => $term,
            ':courseno' => $courseno,
            ':group'    => $group,
            ':studentno'    => $studentno,
        )),false);
    }

    /**
     * 获取开课计划中课程类型
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @return string
     */
    public function getCoursePlanMessage($year,$term,$courseno,$groupno){
        $rst  = $this->getTable('courseplan',array(
            'YEAR'  => $year,
            'TERM'  => $term,
            'COURSENO'  => $courseno,
            'GROUP' => array($groupno,true),
        ));
        if(is_string($rst) or !$rst){
            return '获取课程类型失败';
        }
        return $rst[0];
    }






    /**
     * 获取该筛选条件中最大的组号
     * @param array $map 筛选条件
     * @return array
     */
    public function getCoursePlanMaxGroupno($map){
        $sql = 'SELECT MAX(dbo.fn_36to10([GROUP])) as MAX FROM COURSEPLAN';
        $rst = $this->makeWhere($map);
        $sql .= $rst[0];

        $courseplans = $this->sqlQuery($sql,$rst[1]);
//        vardump($rst,$courseplans,$sql,$rst[1]);
        if(false === $courseplans){
            return array('error',$this->getDbError());
        }else{
            $maxGpno = NULL;
            foreach($courseplans as $val){
                if($val['MAX'] > $maxGpno){
                    $maxGpno = $val['MAX'];
                }
            }
//            vardump($maxGpno);
            return array('success',$maxGpno);
        }
    }

    /**
     * 更新开课计划表
     * @param array $updFields 更新的字段映射数组
     * @param array $filterMap where条件数组，元素可以是字符串或者数组，具体参照@$updateData
     * @return int|string
     */
    public function updateCoursePlan($updFields,$filterMap){
        return $this->updateRecords('COURSEPLAN',$updFields,$filterMap);
    }

    /**
     * 获取分页后的SQL
     * @param string $sql 查询SQL
     * @param integer $offset 偏移量
     * @param integer $limit  单页显示数量
     * @return string
     */
    protected function makePagedSql($sql,$offset,$limit){
        return  "
SELECT T1.* FROM (
  SELECT thinkphp.*, ROW_NUMBER() OVER (ORDER BY  COURSENO, [GROUP] ) AS ROW_NUMBER FROM (
    {$sql}
  ) AS thinkphp
) AS T1 WHERE (T1.ROW_NUMBER BETWEEN {$offset} AND {$offset} + {$limit} )";
    }


    /**
     * 获取教学计划中的课程的列表
     * @param mixed $types 类型字符串或者数组
     * @param integer|string $offset 实际偏移
     * @param integer|string $limit 实际大小
     * @param bool $flag 决定是否包含还是排除
     * @return array 返回结果数组
     */
    public function getCourseplanList($types,$offset=null,$limit=null,$flag = true){
        $filter = $this->makeFilter('cp.course_type_options ',$types,$flag);
        $fields = '
cp.[YEAR],
cp.TERM,
cp.COURSENO,
cp.[GROUP],
dbo.getone(RTRIM(COURSES.COURSENAME)) as COURSENAME,
dbo.group_concat(RTRIM(CLASSES.CLASSNAME),\',\') as CLASSNAME,
dbo.group_concat(RTRIM(CLASSES.CLASSNO),\'#\') as CLASSNO,
dbo.getone(cp.CREDITS ) as CREDITS,
dbo.getone(cp.WEEKS ) as WEEKS,
dbo.getone(COURSES.HOURS ) as HOURS,
dbo.getone(RTRIM(ca.[VALUE])) as COURSETYPE,-- 修课方式(中文)
dbo.getone(cp.COURSETYPE) as TYPE,-- 修课方式(代号)
dbo.getone(RTRIM(cto.[VALUE])) as CATEGORYNAME, -- 课程类型一(中文)
dbo.getone(course_type_options) as CTO, -- 课程类型一(代号)
dbo.getone(RTRIM(EXAMOPTIONS.[VALUE])) as EXAMTYPE, -- 考核方式(中文)
dbo.getone(cp.EXAMTYPE) as EXAM, -- 考核方式(代号)
SUM(cp.ATTENDENTS) as ATTENDENTS,-- 参加人数，被平分到各个班级
dbo.getone(RTRIM(SCHOOLS.NAME)) as SCHOOLNAME, -- 开课单位
dbo.getone(cp.LIMITGROUPNO) as LIMITGROUPNO,
dbo.getone(cp.LIMITNUM) as LIMITNUM,
dbo.getone(rtrim(cp.REM)) as REM,
dbo.getone(cst.description) as  score_type,
dbo.getone(cp.score_type) as score_type_code,
dbo.getone(cp.total_attendents_limit) as total_attendents_limit';
        $join = '
inner JOIN COURSES on COURSES.COURSENO = cp.COURSENO
inner JOIN CLASSES ON CLASSES.CLASSNO = cp.CLASSNO
inner JOIN COURSEAPPROACHES ca ON ca.NAME = cp.COURSETYPE
inner JOIN COURSETYPEOPTIONS cto ON cto.NAME = cp.course_type_options
left outer JOIN EXAMOPTIONS ON EXAMOPTIONS.NAME = cp.EXAMTYPE
inner JOIN SCHOOLS on SCHOOLS.SCHOOL = cp.school
left outer JOIN cwebs_score_type cst on cst.id = cp.score_type';
        $where = "
cp.[YEAR] = :year and cp.TERM = :term
and cp.COURSENO like :courseno AND cp.[GROUP] like :group
and CLASSES.SCHOOL like :schoolno
and cp.COURSETYPE like :approach and cp.course_type_options like :coursetype -- 修课方式和课程类型查询
and cp.CLASSNO like :classno and cp.EXAMTYPE like :examtype {$filter}";
        $group = 'cp.[YEAR],cp.TERM,cp.COURSENO,cp.[GROUP]';

        $csql = $this->makeCountSql('COURSEPLAN cp',array(
            'join'  => $join,
            'where' => $where,
            'group' => $group,
        ));
        $ssql = $this->makeSql('COURSEPLAN cp',array(
            'fields'=> $fields,
            'join'  => $join,
            'where' => $where,
            'group' => $group,
        ),$offset,$limit);
//        $_REQUEST['SCHOOL'];
        $bind = array(
            ':year' => $_REQUEST['YEAR'],
            ':term' => $_REQUEST['TERM'],
            ':courseno' => $_REQUEST['COURSENO'],
            ':group' => $_REQUEST['GROUP'],
            ':schoolno' => $_REQUEST['SCHOOL'],
            ':approach' => $_REQUEST['COURSETYPE'],
            ':coursetype' => $_REQUEST['CATEGORY'],
            ':classno' => $_REQUEST['CLASSNO'],
            ':examtype' => $_REQUEST['EXAMTYPE'],
        );
//        dumpout($csql,$ssql,$bind);
        $rst = $this->getTableList2($csql,$ssql,$bind);

        //周次整合
        foreach($rst['rows'] as $k=>&$row){
            $row['WEEKS'] = strrev(sprintf('%018s', decbin($row['WEEKS'])));
        }
//        dumpout($rst);
        return $rst;

    }


    /**
     * 分班时拆解多个班级
     * @param $items
     * @return array
     */
    public static function splitItem($items){
        //预先分解参数项,以井号分隔的单项按照班级号分隔成两项或者多项
        foreach($items as $key=>$val){
            $its = explode(',',$val);
            $classes = $its[4];
            if(false !==strpos($classes,self::$spliter)){
                $classarr = explode(self::$spliter,trim($classes));
                unset($items[$key]);
                $flag = true;
                foreach($classarr as $v){
                    if($flag){
                        //置于第一个位置
                        $items[$key] = "{$its[0]},{$its[1]},{$its[2]},{$its[3]},$v";
                        $flag = false;
                    }else{
                        //追加到后面的形式
                        $items[] = "{$its[0]},{$its[1]},{$its[2]},{$its[3]},$v";
                    }
                }
            }
        }
        return $items;
    }

    /**
     * 独立添加开课计划条目
     * 注意：
     *  COURSETYPE指的是修课方式
     *  course_type_options指的是课程类别一即表coursetyeoption中的数据
     *  limitcredit指的是限选总学分
     * @param array $bind 参数绑定
     * @return int|string
     */
    public function insertCoursePlan($bind){
        $sql = '
INSERT INTO COURSEPLAN
(YEAR, TERM, COURSENO, CLASSNO, SCHOOL, WEEKS,[GROUP],COURSETYPE, EXAMTYPE, ATTENDENTS,
 REM, LIMITGROUPNO, LIMITNUM, CREDITS, course_type_options,LIMITCREDIT)
VALUES(
:YEAR,:TERM,:COURSENO,:CLASSNO,:SCHOOL,:WEEKS,:GROUP,:COURSETYPE,:EXAMTYPE,:ATTENDENTS,
:REM,:LIMITGROUPNO,:LIMITNUM,:CREDITS,:CATEGORY,:LIMITCREDIT)';
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 复制一条教学计划
     * @param $year
     * @param $term
     * @param $courseno
     * @param $group
     * @return int|string
     */
    public function copyCoursePlan($year,$term,$courseno,$group){
        $maxGroup = $this->getCourseMaxGroupno($year,$term,$courseno);
        if(is_string($maxGroup)){
            return '获取最大组号的过程中出现错误'.$maxGroup;
        }
        $sql = '
INSERT INTO COURSEPLAN ([YEAR], [TERM], [COURSENO], [SCHOOL], [ATTENDENTS], [REM], [WEEKS], [GROUP],
[COURSETYPE], [EXAMTYPE], [CLASSNO], [Date], [LIMITGROUPNO], [LIMITNUM], [CREDITS], [CATEGORY],
 [LIMITRESULT], [course_type_options], [LIMITCREDIT], [score_type])
select
[YEAR], [TERM], [COURSENO], [SCHOOL], [ATTENDENTS], [REM], [WEEKS],
dbo.fn_10to36(:ngroup),
[COURSETYPE], [EXAMTYPE], [CLASSNO], [Date], [LIMITGROUPNO], [LIMITNUM], [CREDITS], [CATEGORY],
 [LIMITRESULT], [course_type_options], [LIMITCREDIT], [score_type]
from COURSEPLAN WHERE year= :year and term =:term and COURSENO = :courseno and [GROUP] = :group';
        $bind = array(
            ':ngroup'   => $maxGroup+1,
            ':year'     => $year,
            ':term'     => $term,
            ':courseno' => $courseno,
            ':group'    => $group,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 获取开课计划中的限选等信息
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @return array|string
     */
    public function getLimitCondition($year,$term,$courseno,$groupno){
        $limits = $this->getTable('COURSEPLAN',array(
            'YEAR'  => $year,
            'TERM'  => $term,
            'COURSENO'  => $courseno,
            'GROUP' => array($groupno,true),
        ));
        if(is_string($limits) or !$limits){
            return "无法获取第[$year]学年第[$term]学期课号组号为为[$courseno,$groupno]的课程记录！$limits";
        }
        //教学计划下同一组号课号下的限选条件及学分信息应该是一致的，返回第一个
        $rst = array(
            'LIMITGROUPNO'  =>  $limits[0]['LIMITGROUPNO'],
            'LIMITNUM'   => $limits[0]['LIMITNUM'],
            'LIMITCREDIT'   => $limits[0]['LIMITCREDIT'],
            'CREDITS' => $limits[0]['CREDITS'],
        );
        return $rst;
    }

    /**
     * 获取最大组号
     * 建议替换getCoursePlanMaxGroupno方法来使用
     * @param $year
     * @param $term
     * @param $courseno
     * @return string|int
     */
    public function getCourseMaxGroupno($year,$term,$courseno){
        $sql = 'SELECT MAX(dbo.fn_36to10([GROUP])) as maxgp FROM COURSEPLAN WHERE YEAR=:YEAR AND TERM=:TERM AND COURSENO=:COURSENO';
        $bind = array(
            ':YEAR'=>$year,
            ':TERM'=>$term,
            ':COURSENO'=>$courseno,
        );
        $rst = $this->sqlQuery($sql,$bind);
        if(is_string($rst)){
            return $this->getErrorMessage();
        }elseif(!$rst){
            return "第[$year]学年第[$term]学期课程号为[$courseno]的开课计划查询不到！";
        }ELSE{
            return intval($rst[0]['maxgp']);
        }
    }

    /**
     * 更新开课计划组号
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @param $classno
     * @param $newGroupno
     * @return int|string
     */
    public function updateCoursePlanOfGroupno($year,$term,$courseno,$groupno,$classno,$newGroupno){
        $sql = 'update courseplan set [group]=dbo.fn_10to36(:GP)
where YEAR=:YEAR AND TERM=:TERM AND COURSENO=:COURSENO AND CLASSNO=:CLASSNO AND [GROUP]=:GROUP';
        $bind = array(
            ':GP'=>$newGroupno,
            ':YEAR'=>$year,
            ':TERM'=>$term,
            ':COURSENO'=>$courseno,
            ':CLASSNO'=>$classno,
            ':GROUP'=>$groupno
        );
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
//        vardump($rst,$sql,$bind);
        return $rst;
    }


    /**
     * 删除开课计划
     * @param $where
     * @return int|string
     */
    public function removeCoursePlan($where){
        return $this->deleteRecords('COURSEPLAN',$where);
    }


    /**
     * 获取统一学年学期课号组号下的所有班级
     * @param $year
     * @param $term
     * @param $courseno
     * @param $groupno
     * @return array|string
     */
    public function getCoursePlanClasses($year,$term,$courseno,$groupno){
        $sql = "
SELECT
dbo.GROUP_CONCAT_MERGE(RTRIM(CLASSES.CLASSNAME),';') as CLASSNAME
FROM COURSEPLAN
LEFT JOIN CLASSES  ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO
WHERE COURSEPLAN.courseno = :courseno
AND COURSEPLAN.[GROUP]=:group
AND COURSEPLAN.YEAR=:year
AND COURSEPLAN.TERM=:term";
        $bind = array(
            ':courseno' => $courseno,
            ':group' => $groupno,
            ':year' => $year,
            ':term' => $term,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }


    public function getScheduleList($bind,$offset=null,$limit=null){
        $fields = "
dbo.getOne(SCHEDULEPLAN.YEAR) AS YEAR,
dbo.getOne(SCHEDULEPLAN.TERM) AS TERM,
SCHEDULEPLAN.COURSENO,
SCHEDULEPLAN.[GROUP],
RTRIM(dbo.getOne(COURSES.COURSENAME)) AS COURSENAME,
dbo.getOne(COURSEAPPROACHES.VALUE) AS COURSETYPEVALUE,
dbo.getOne(EXAMOPTIONS.VALUE) AS EXAMTYPEVALUE,
dbo.getOne(COURSEPLAN.CREDITS) AS CREDITS,
dbo.getOne(COURSETYPEOPTIONS2.value) AS COURSETYPE,
RTRIM(dbo.getOne(SCHOOLS.NAME)) AS SCHOOLNAME,
dbo.getOne(SCHOOLS.SCHOOL) AS SCHOOLNO,
dbo.getOne(SCHEDULEPLAN.ESTIMATE) AS ESTIMATE,
dbo.getOne(SCHEDULEPLAN.ATTENDENTS) AS ATTENDENTS,
dbo.getOne(SCHEDULEPLAN.EMPROOM) AS EMPROOM,
dbo.getOne(TIMESECTORS.VALUE) AS TIME,
dbo.getOne(COURSES.TOTAL) as TOTAL,
dbo.getOne(COURSES.HOURS) as HOURS,
dbo.getOne(SCHEDULEPLAN.LHOURS) as LHOURS,
dbo.getOne(SCHEDULEPLAN.EHOURS) as EHOURS,
dbo.getOne(SCHEDULEPLAN.CHOURS) as CHOURS,
dbo.getOne(SCHEDULEPLAN.SHOURS) as SHOURS,
dbo.getOne(SCHEDULEPLAN.ZHOURS) as ZHOURS,
dbo.getOne(SCHEDULEPLAN.KHOURS) as KHOURS,
dbo.getOne(SCHEDULEPLAN.WEEKS) as WEEKS,
dbo.getOne(SCHEDULEPLAN.DAYS) as DAYS,
dbo.getOne(SCHEDULEPLAN.RECNO) as RECNO,
dbo.getOne(SCHEDULEPLAN.REM) as REM,
dbo.getOne(ROOMOPTIONS.VALUE) AS ROOMTYPE,
dbo.getOne(CAST(SCHEDULEPLAN.EXAM  AS CHAR)) AS FINALEXAM,
dbo.GROUP_CONCAT_MERGE('('+COURSEPLAN.CLASSNO+' - '+RTRIM(CLASSES.CLASSNAME)+')',',') AS CLASS,
dbo.GROUP_CONCAT_MERGE(RTRIM(THETEACHERS.RECNO)+'#$'+RTRIM(THETEACHERS.TEACHERNAME) +',授课任务:'+RTRIM(THETEACHERS.TASK) +',周课时:'+CAST(THETEACHERS.HOURS AS CHAR),' ; ') AS TEACHERTASK,
dbo.GROUP_CONCAT_MERGE(RTRIM(do_THETEACHERS.RECNO)+'#$'+RTRIM(do_THETEACHERS.MAP)+'#$'+RTRIM(do_THETEACHERS.TEACHERNO)+'#$'+RTRIM(do_THETEACHERS.TEACHERNAME)+'#$'+RTRIM(do_THETEACHERS.SCHOOL)+'#$'+RTRIM(do_THETEACHERS.SCHOOLNAME)+'#$'+RTRIM(do_THETEACHERS.TGROUP)+'#$'+RTRIM(do_THETEACHERS.TGROUPNAME)+'#$'+RTRIM(do_THETEACHERS.UNIT)+'#$'+RTRIM(do_THETEACHERS.UNITNAME)+'#$'+RTRIM(do_THETEACHERS.TASK)+'#$'+RTRIM(do_THETEACHERS.TASKNAME)+'#$'+RTRIM(do_THETEACHERS.HOURS)+'#$'+RTRIM(do_THETEACHERS.TOSCHEDULE)+'#$'+RTRIM(do_THETEACHERS.REM),' ; ') AS do_TEACHERTASK,
dbo.GROUP_CONCAT_MERGE('['+RTRIM(CLASSROOMS.JSN)+'星期'+RTRIM(CAST(SCHEDULE.DAY AS CHAR))+RTRIM(TIMESECTIONS.VALUE)+RTRIM(OEWOPTIONS.NAME)+']','') AS CLASSTIME";

        $join = "
left JOIN COURSES ON (SCHEDULEPLAN.COURSENO = COURSES.COURSENO)
left JOIN COURSEPLAN ON (SCHEDULEPLAN.[YEAR]=COURSEPLAN.[YEAR] AND SCHEDULEPLAN.TERM=COURSEPLAN.TERM AND SCHEDULEPLAN.COURSENO=COURSEPLAN.COURSENO AND SCHEDULEPLAN.[GROUP]=COURSEPLAN.[GROUP])
left JOIN COURSEAPPROACHES ON (COURSEPLAN.COURSETYPE=COURSEAPPROACHES.NAME)
left JOIN EXAMOPTIONS ON COURSEPLAN.EXAMTYPE=EXAMOPTIONS.NAME
left JOIN COURSETYPEOPTIONS2 on COURSETYPEOPTIONS2.name=COURSES.TYPE2
left JOIN SCHOOLS ON COURSEPLAN.SCHOOL=SCHOOLS.SCHOOL
left JOIN CLASSES ON COURSEPLAN.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN ROOMOPTIONS ON SCHEDULEPLAN.ROOMTYPE=ROOMOPTIONS.NAME
LEFT OUTER JOIN TIMESECTORS ON SCHEDULEPLAN.TIME=TIMESECTORS.NAME
LEFT OUTER JOIN
		(SELECT TEACHERPLAN.RECNO,TEACHERPLAN.MAP,TEACHERPLAN.TEACHERNO,TEACHERS.SCHOOL AS SCHOOL,TEACHERS.NAME AS TEACHERNAME,TEACHERS.POSITION AS POSITION,TASKOPTIONS.NAME AS TASK,TEACHERPLAN.HOURS AS HOURS
		FROM TEACHERPLAN JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO
		LEFT OUTER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE
		) AS THETEACHERS ON SCHEDULEPLAN.RECNO=THETEACHERS.MAP
LEFT OUTER JOIN (
        SELECT
		isnull(m.RECNO,'') as RECNO,
		isnull(m.MAP,'') as MAP,
		isnull(m.TEACHERNO,'') as TEACHERNO,
		isnull(m1.NAME,'') AS TEACHERNAME,
		isnull(m1.SCHOOL,'') AS SCHOOL,
		isnull(m11.NAME,'') AS SCHOOLNAME,
		isnull(m1.TGROUP,'') AS TGROUP,
		isnull(m12.NAME,'') AS TGROUPNAME,
		isnull(m.UNIT,'') AS UNIT,
		isnull(m2.NAME,'') AS UNITNAME,
		isnull(m.TASK,'') AS TASK,
		isnull(m3.NAME,'') AS TASKNAME,
		isnull(m.HOURS,'') as HOURS,
		isnull(m.TOSCHEDULE,'') as TOSCHEDULE,
		isnull(m.REM,'') as REM
		FROM TEACHERPLAN m
		LEFT JOIN TEACHERS m1 ON m1.TEACHERNO = m.TEACHERNO
		LEFT JOIN SCHOOLS m11 ON m11.SCHOOL = m1.SCHOOL
		LEFT JOIN TGROUPS m12 ON m12.TGROUP = m1.TGROUP
		LEFT JOIN UNITOPTIONS m2 ON m2.CODE = m.UNIT
		LEFT JOIN TASKOPTIONS m3 ON m3.CODE = m.TASK
		) AS do_THETEACHERS ON SCHEDULEPLAN.RECNO = do_THETEACHERS.MAP
LEFT OUTER JOIN (SELECT POSITIONS.VALUE AS ZC,POSITIONS.NAME AS NAME,POSITIONS.JB FROM POSITIONS) AS L_ZC ON THETEACHERS.POSITION=L_ZC.NAME
LEFT OUTER JOIN SCHEDULE ON (SCHEDULEPLAN.YEAR=SCHEDULE.YEAR AND SCHEDULEPLAN.TERM=SCHEDULE.TERM AND SCHEDULEPLAN.COURSENO=SCHEDULE.COURSENO AND SCHEDULEPLAN.[GROUP]=SCHEDULE.[GROUP])
LEFT OUTER JOIN CLASSROOMS ON SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO
LEFT OUTER JOIN TIMESECTIONS ON SCHEDULE.TIME=TIMESECTIONS.NAME
LEFT OUTER JOIN OEWOPTIONS ON SCHEDULE.OEW=OEWOPTIONS.CODE";
        $where = '
SCHEDULEPLAN.YEAR = :YEAR
AND SCHEDULEPLAN.TERM = :TERM
AND SCHEDULEPLAN.COURSENO LIKE :COURSENO
AND SCHEDULEPLAN.[GROUP] LIKE :GROUP
AND COURSEPLAN.SCHOOL LIKE :SCHOOL
AND COURSES.TGROUP LIKE :TGROUP ---------------------------开课教研组有误
AND COURSEPLAN.course_type_options LIKE :COURSETYPE
AND SCHEDULEPLAN.SCHEDULED LIKE :SCHEDULED
AND SCHEDULEPLAN.ROOMTYPE LIKE :ROOMTYPE
AND COURSEPLAN.CLASSNO LIKE :CLASSNO
AND COURSEPLAN.EXAMTYPE like :EXAMTYPE
AND COURSEPLAN.COURSETYPE like :XCTYPE
AND (SCHEDULEPLAN.ESTIMATE <= :ESTIMATEUP AND SCHEDULEPLAN.ESTIMATE >= :ESTIMATEDOWN)
AND (SCHEDULEPLAN.ATTENDENTS <= :ATTENDENTSUP AND SCHEDULEPLAN.ATTENDENTS >= :ATTENDENTSDOWN)
--{$SQL.LOCK}
--{$SQL.EXAM}

AND SCHEDULEPLAN.DAYS LIKE :DAYS
AND year(CLASSES.YEAR) = :grade
AND CLASSES.SCHOOL like :schoolno
--{$SQL.SRARCHTYPE}
and (do_THETEACHERS.TEACHERNO like :teachernoname or do_THETEACHERS.TEACHERNO is null) ---------------------change by zqq @2016.2.17 add :teachernoname para
	and 	(CLASSES.CLASSNO like :classnoname )';
//    and (do_THETEACHERS.TEACHERNO like :teachernoname or do_THETEACHERS.TEACHERNO is null) ---------------------change by zqq @2016.2.17 add :teachernoname para
//or do_THETEACHERS.TEACHERNO is null
//or CLASSES.CLASSNO is null
        $group = 'SCHEDULEPLAN.COURSENO,SCHEDULEPLAN.[GROUP]';
        $order = 'SCHEDULEPLAN.COURSENO,SCHEDULEPLAN.[GROUP],COURSETYPEVALUE,EXAMTYPEVALUE';

        $cond =array(
            'join'  => $join,
            'where' => $where,
            'group' => $group,
        );
        if(isset($_REQUEST['schedulestate'])){
            switch($_REQUEST['schedulestate']){
                case '0':
                    $cond['having'] = 'count(do_THETEACHERS.RECNO) = 0';
                    break;
                case '1':
                    $cond['having'] = 'count(do_THETEACHERS.RECNO) > 0';
                    break;
                case '%':
                    //不设置条件
                    break;
            }
        }
        $bind[':teachernoname'] = trim($bind[':teachernoname']).'%';

        $csql = $this->_format($this->makeCountSql('SCHEDULEPLAN',$cond));
        $ssql = $this->_format($this->makeSql('SCHEDULEPLAN',array_merge($cond,array(
                'fields'    => $fields,
                'order' => $order,
            )),$offset,$limit));

//        vardump($csql,$ssql,$bind);
        return $this->getTableList2($csql,$ssql,$bind);
    }



    private function _format($sql){
        if(isset($_REQUEST["LOCK"]) && ($_REQUEST["LOCK"]==1 || $_REQUEST["LOCK"]==0))
            $sql = str_replace('{$SQL.LOCK}',"AND SCHEDULEPLAN.LOCK=".intval($_REQUEST["LOCK"]),$sql);
        else
            $sql = str_replace('{$SQL.LOCK}',"",$sql);
        if(isset($_REQUEST["EXAM"]) && ($_REQUEST["EXAM"]==1 || $_REQUEST["EXAM"]==0))
            $sql = str_replace('{$SQL.EXAM}',"AND SCHEDULEPLAN.EXAM=".intval($_REQUEST["EXAM"]),$sql);
        else
            $sql = str_replace('{$SQL.EXAM}',"",$sql);
        $searchType = intval($_REQUEST["SEARCHTYPE"]);
        if($searchType==2){
            $sql = str_replace('{$SQL.SRARCHTYPE}',"AND THETEACHERS.TEACHERNAME IS NULL",$sql);
        }elseif($searchType==3){
            $sql = str_replace('{$SQL.SRARCHTYPE}',"AND (THETEACHERS.TEACHERNAME IS NOT NULL AND L_ZC.JB='初级')",$sql);
        }elseif($searchType==4){
            $sql = str_replace('{$SQL.SRARCHTYPE}',"AND COURSEPLAN.CLASSNO LIKE '000000%'",$sql);
        }else{
            $sql = str_replace('{$SQL.SRARCHTYPE}',"AND (THETEACHERS.SCHOOL LIKE '".$_REQUEST["TEACHERNO"]."' OR THETEACHERS.SCHOOL IS NULL)",$sql);
        }
        return $sql;
    }


























/********************************* 其他模型的数据，待迁移 ***************************************************************************************/

    /**
     * 检查班级是否存在
     * @param $classno
     * @return array|int|string
     */
    public function checkClassnoExist($classno){
        return $this->getFrom('CLASSES',array(
            ':CLASSNO' => $classno,
        ),true);
    }

    /**
     * 获取排课几哈中指定的数量
     * @param $map
     * @param bool $count
     * @return array|int|string
     */
    public function getSchedulePlanList($map,$count=false){
        return $this->getFrom('SCHEDULEPLAN',$map,$count);
    }


    /**
     * 获取课程详细信息
     * @param string $courseno 课程号
     * @param boolean $count 是否只获取其数量
     * @return array|int|string
     */
    public function getCourse($courseno,$count=false){
        $courseno = substr($courseno,0,7);
        return $this->getTable('COURSES',array(
            'COURSENO' => $courseno,
        ),$count);
    }


    /**
     * @param array $updFields 更新的字段映射
     * @param array $whrMap 筛选条件映射
     * @return int|string
     */
    public function updateSchedulePlan($updFields,$whrMap){
        return $this->updateRecords('SCHEDULEPLAN',$updFields,$whrMap);
    }

    /**
     * @param $updFields
     * @param $whrMap
     * @return int|string
     */
    public function updateSchedule($updFields,$whrMap){
        return $this->updateRecords('SCHEDULE',$updFields,$whrMap);
    }

    /**
     * @param $updFields
     * @param $whrMap
     * @return int|string
     */
    public function updateStudentCoursePlan($updFields,$whrMap){
        return $this->updateRecords('R32',$updFields,$whrMap);
    }

    /**
     * 获取修课方式的下拉框的数据
     * @param string $defaultname 默认值
     * @param bool $defaultShowText 默认值是显示值？
     * @return array|string
     */
    public function getComboCourseApproches($defaultname = NULL,$defaultShowText=true){
        return $this->getComboData('COURSEAPPROACHES','NAME','VALUE',$defaultname,$defaultShowText);
    }

    /**
     * 获取课程类别一的下拉框数据
     * @param null $defaultname
     * @param bool $defaultShowText
     * @return array|string
     */
    public function getComboCourseTypeOption($defaultname = NULL,$defaultShowText=true){
        return $this->getComboData('COURSETYPEOPTIONS','NAME','VALUE',$defaultname,$defaultShowText);
    }


    /**
     * 获取考试类型 下拉框数据
     * @param null $defaultname
     * @param bool $defaultShowText
     * @return array|string
     */
    public function getComboExamType($defaultname = NULL,$defaultShowText=true){
        return $this->getComboData('EXAMOPTIONS','NAME','VALUE',$defaultname,$defaultShowText);
    }









}