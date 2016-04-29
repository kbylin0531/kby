<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/25
 * Time: 14:39
 */
class MajorModel extends CommonModel{

    /**
     * 刷新学生已获得的学分表
     * @param int $year 年级，如2015
     * @return int|string int类型时表示成功生成的学生数目，string类型时表示错误信息
     */
    public function refreshStudentsFinishedScore($year){
        $this->startTrans();
        $dsql = 'DELETE from cwebs_temp_student_courses_credit WHERE [year] = :year';
        $rst = $this->doneExecute($this->sqlExecute($dsql,array(':year'=> $year)));
        if(is_string($rst)){
            $this->rollback();
            return "删除旧的记录失败！{$rst}";
        }
        $isql = '
INSERT INTO [cwebs_temp_student_courses_credit] ([studentno], [credit], [year])
SELECT
    R28.STUDENTNO as studentno,
    SUM(R12.[CREDITS]) as credit,
    :yeara
FROM R28
INNER JOIN STUDENTS on R28.STUDENTNO = STUDENTS.STUDENTNO
INNER JOIN PROGRAMS on PROGRAMS.PROGRAMNO = R28.PROGRAMNO
INNER JOIN R12 on R12.PROGRAMNO = R28.PROGRAMNO
INNER JOIN view_score_passed cs on cs.studentno = R28.STUDENTNO and cs.courseno = R12.COURSENO
WHERE YEAR(STUDENTS.ENTERDATE) = :yearb
and cs.courseno <> \'BYSX011\' -- 毕业实习课程除外
GROUP BY R28.STUDENTNO';
        $rst = $this->doneExecute($this->sqlExecute($isql,array(
            ':yeara'    => $year,
            ':yearb'    => $year,
        )));
        if(is_string($rst)){
            $this->rollback();
            return "生成新的记录失败！{$rst}";
        }
        $this->commit();
        return $rst;
    }

    public function getMajorPlanTableList($bind,$offset,$limit){
        $fields = "
MAJORS.MAJORNO,
MAJORS.YEARS AS MAJORSYEARS,
MAJORS.REM AS MAJORSREM,
MAJORPLAN.YEAR,
MAJORPLAN.REM AS MAJORPLANREM,
MAJORCODE.NAME AS MAJORCODENAME,
BRANCHCODE.NAME AS BRANCHCODENAME,
DEGREEOPTIONS.NAME AS DEGREEOPTIONSNAME,
SCHOOLS.NAME AS SCHOOLSNAME,
MAJORPLAN.MCREDITS AS MAJORPLANMCREDITS,
MAJORPLAN.CREDITS AS MAJORPLANCREDITS,
MAJORPLAN.专业方向 AS SPECIALS,
MAJORPLAN.ROWID AS majorplanrowid,
(isnull(rtrim(MAJORS.MAJORNO),'') + '-' + isnull(cast(MAJORS.CLASS_NATURE as varchar),'')) as MAJORROWNO,
MAJORS.ROWID AS majorsrowid,
MAJORS.SCHOOL AS school";
        $join = '
INNER JOIN MAJORS ON MAJORPLAN.MAP = MAJORS.ROWID
INNER JOIN MAJORCODE ON MAJORS.MAJORNO = MAJORCODE.CODE
left JOIN BRANCHCODE ON MAJORS.BRANCH = BRANCHCODE.CODE
left JOIN DEGREEOPTIONS ON MAJORS.DEGREE = DEGREEOPTIONS.CODE
left JOIN SCHOOLS ON MAJORS.SCHOOL = SCHOOLS.SCHOOL';
        $where = "
(MAJORS.SCHOOL LIKE :SCHOOL or MAJORS.SCHOOL is null)
AND (MAJORPLAN.YEAR = :GRADE or MAJORPLAN.YEAR  is null)
AND (MAJORS.YEARS LIKE :YEARS or MAJORS.YEARS is null)
AND ((isnull(rtrim(MAJORS.MAJORNO),'') + '-' + isnull(cast(MAJORS.CLASS_NATURE as varchar),'')) like :MAJORROWNO)
AND (MAJORS.MAJORNO LIKE :MAJOR  or MAJORS.MAJORNO is null)";
        $order = 'MAJORS.MAJORNO';
        $csql = $this->makeCountSql('MAJORPLAN',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('MAJORPLAN',array(
            'fields' => $fields,
            'join'  => $join,
            'where' => $where,
            'order' => $order
        ),$offset,$limit);
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 创建船业条目
     * @param $map
     * @return bool
     */
    public function createMojorCode($map){
        return $this->createRecord('MAJORS',$map);
    }


    public function listMajorPlans($grade='%',$schoolno='%',$classno='%',$studentno='%',$offset=null,$limit=null){
        $fields = "
st.STUDENTNO as studentno,
RTRIM(st.NAME) as studentname,
RTRIM(SexCode.NAME) as sexname,
RTRIM(CLASSES.classname) as classname,
ISNULL(cga.status, 3) as status,
ISNULL(convert(VARCHAR(10), cga.auditime, 20), '-') as auditime ,
SUM(R12.[CREDITS]) as total_credit,
ISNULL(temp.credit, 0) as passed_credit ,
dbo.group_concat(DISTINCT mi.DETAILS,',') as exp,
ISNULL(cs.credit, 0) as graduation_practice_creidt -- 毕业实习学分";
        $join = '
LEFT OUTER JOIN cwebs_graduation_audit cga on cga.studentno = st.STUDENTNO
INNER JOIN CLASSES on st.CLASSNO = CLASSES.CLASSNO
INNER JOIN SexCode on SexCode.CODE = st.SEX
INNER JOIN R28 on R28.STUDENTNO = st.STUDENTNO
INNER JOIN PROGRAMS on PROGRAMS.PROGRAMNO = R28.PROGRAMNO
INNER JOIN R12 on R12.PROGRAMNO = R28.PROGRAMNO
LEFT OUTER JOIN MORAL_INFO mi on mi.STUDENTNO = st.STUDENTNO
-- LEFT OUTER JOIN (
-- 	SELECT
-- 	R28.STUDENTNO as studentno,
-- 	SUM(R12.[CREDITS]) as credit
-- 	FROM R28
-- 	INNER JOIN PROGRAMS on PROGRAMS.PROGRAMNO = R28.PROGRAMNO
-- 	INNER JOIN R12 on R12.PROGRAMNO = R28.PROGRAMNO
-- 	INNER JOIN view_score_passed cs on cs.studentno = R28.STUDENTNO and cs.courseno = R12.COURSENO
-- 	GROUP BY R28.STUDENTNO
-- ) temp on temp.studentno = st.STUDENTNO
LEFT OUTER JOIN cwebs_temp_student_courses_credit temp on temp.studentno = st.STUDENTNO
LEFT OUTER JOIN cwebs_scores cs on cs.studentno = st.STUDENTNO and cs.courseno = \'BYSX011\'';
        $where = "
DATENAME(YEAR,CLASSES.[YEAR]) like :grade
and CLASSES.SCHOOL LIKE :schoolno
and CLASSES.CLASSNO like :classno
and st.STUDENTNO like :studentno";
        $group = 'st.STUDENTNO,st.NAME,SexCode.NAME,CLASSES.CLASSNAME,cga.status,cga.auditime,temp.credit,cs.credit';
        $csql = $this->makeCountSql('STUDENTS st',array(
            'join'  => $join,
            'where' => $where,
            'group' => $group,
        ));
        $ssql = $this->makeSql('STUDENTS st',array(
            'fields' => $fields,
            'join'  => $join,
            'where' => $where,
            'group' => $group,
        ),$offset,$limit);
        $bind = array(
            ':grade'    => $grade,
            ':schoolno' => $schoolno,
            ':classno'  => $classno,
            ':studentno'=> $studentno,
        );
//        mist($csql,$ssql,$bind);
        return $this->getTableList2($csql,$ssql,$bind);
    }

    /**
     * 获取毕业审核学生数据
     * @param string $studentno 精确的学号
     * @return array|string
     */
    public function getStudentCoursesForGraduationAudit($studentno){
        $sql = '
SELECT
R28.STUDENTNO as studentno,
RTRIM(st.NAME) as studentname,
R28.PROGRAMNO as programno,
RTRIM(PROGRAMS.PROGNAME) as programname,
RTRIM(COURSES.COURSENAME) as coursename,
cs.[year],
cs.term,
COURSES.courseno+ISNULL(cs.[group], \'**\') as coursegroup,
RTRIM(cap.[VALUE]) as approach,
RTRIM(eop.[VALUE]) as examtype,
R12.[CREDITS] as credit,
case
	WHEN cs.studentno is NULL THEN \'未考试/未通过\'
	ELSE \'通过\'
  end as passed,
ISNULL(dbo.group_concat(mo.DETAILS,\',\'), \'-\') as exp
FROM R28
INNER JOIN STUDENTS st on st.STUDENTNO = R28.STUDENTNO
INNER JOIN PROGRAMS on PROGRAMS.PROGRAMNO = R28.PROGRAMNO
INNER JOIN R12 on R12.PROGRAMNO = R28.PROGRAMNO
INNER JOIN COURSES on COURSES.COURSENO = R12.COURSENO
LEFT OUTER JOIN view_score_passed cs on cs.studentno = R28.STUDENTNO and cs.courseno = R12.COURSENO
INNER JOIN COURSEAPPROACHES cap on cap.NAME = R12.COURSETYPE
INNER JOIN EXAMOPTIONS eop on eop.NAME = R12.EXAMTYPE
LEFT OUTER JOIN MORAL_INFO mo on mo.STUDENTNO = R28.STUDENTNO
WHERE R28.STUDENTNO = :studentno
GROUP BY R28.STUDENTNO,st.NAME,R28.PROGRAMNO,PROGRAMS.PROGNAME,COURSES.COURSENAME,cs.[year],
	cs.term,COURSES.courseno,cs.[group] ,cap.[VALUE],eop.[VALUE],R12.[CREDITS],cs.studentno';
        $bind = array(
            ':studentno'    => $studentno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    /**
     * 创建考评记录（在不存在的前提下）
     * @param $studentno
     * @return int|string
     */
    public function createAuditIfNotExist($studentno){
        $rst = $this->checkAuditRecordExist($studentno);
        if(is_string($rst)){
            return $rst;
        }elseif(!$rst){
            return $this->createAuditRecord($studentno);
        }else{
            //已经存在,不修改
        }
        return 1;
    }

    /**
     * 检查学生毕业审核过程 是否被查看过
     * @param $studentno
     * @return int|string
     */
    public function checkAuditRecordExist($studentno){
        $sql = 'SELECT * from cwebs_graduation_audit WHERE studentno = :studentno';
        $bind = array(':studentno'=>$studentno);
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind));
        return is_string($rst)?$rst:count($rst);
    }


    /**
     * 创建学生毕业审核记录
     * @param $studentno
     * @return int|string
     */
    public function createAuditRecord($studentno){
        $sql = 'INSERT INTO cwebs_graduation_audit (studentno,status) VALUES (:studentno,0);';
        $bind = array(':studentno'=>$studentno);
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 修改毕业审核结果
     * @param $studentno
     * @param int $status
     * @return int|string
     */
    public function updateAuditStatus($studentno,$status=1){
        $status = intval($status);
        $sql = "UPDATE cwebs_graduation_audit SET status = {$status} ,auditime=GETDATE() WHERE studentno = :studentno";
        $bind = array(':studentno'=>$studentno);
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }


    /**
     * 罗列专业绑定的学生列表
     * @param $grade
     * @param $studentno
     * @param $studentname
     * @param $classno
     * @param $school
     * @param null $limit
     * @param null $offset
     * @return array|string
     */
    public function listStudentsForMajors($grade,$studentno,$studentname,$classno,$school,$limit=null,$offset=null){
        $fields = '
st.STUDENTNO as studentno,
RTRIM(st.NAME) as studentname,
CASE
	WHEN st.SEX = \'M\' THEN \'男\'
	WHEN st.SEX = \'F\' THEN \'女\'
	ELSE \'未知\'
	END AS sexname,
RTRIM(CLASSES.CLASSNAME) as classname,
RTRIM(SCHOOLS.NAME) as schoolname,
MAJORCODE.name as majorname,
ISNULL(RTRIM(MAJORS.CLASS_NATURE_NAME), \'\') as classnature';
        $join = '
INNER JOIN PERSONAL pn on st.STUDENTNO = pn.studentno
inner  JOIN CLASSES on st.classno = CLASSES.CLASSNO
inner  JOIN SCHOOLS on CLASSES.SCHOOL = SCHOOLS.SCHOOL
-- important!
LEFT OUTER JOIN MAJORCODE on MAJORCODE.CODE = pn.MAJOR
LEFT OUTER JOIN MAJORS on MAJORS.MAJORNO = pn.MAJOR and MAJORS.CLASS_NATURE = pn.CLASS_NATURE';
        $where = '
LEFT(convert(varchar, st.ENTERDATE, 112),4) like :grade
and RTRIM(st.STUDENTNO) like :studentno
and RTRIM(st.NAME) like :studentname
and RTRIM(CLASSES.classno) like :classno
and SCHOOLS.SCHOOL like :school';
        return $this->getTableList(
            $this->makeCountSql('STUDENTS st',array(
                'join'  => $join,
                'where' => $where,
            )),
            $this->makeSql('STUDENTS st',array(
                'fields'    => $fields,
                'join'  => $join,
                'where' => $where,
            ),$limit,$offset ),
            array(
                ':grade' => $grade,
                ':studentno' => $studentno,
                ':studentname' => $studentname,
                ':classno' => $classno,
                ':school' => $school,
            )
        );
    }

    /**
     * @param $grade
     * @param $studentno
     * @param $studentname
     * @param $classname
     * @param $school
     * @param $majorno
     * @param $majoritemno
     * @return int|string
     */
    public function bindStudentsForMajors($grade,$studentno,$studentname,$classno,$school,$majorno,$majoritemno){
        $sql = '
UPDATE pn SET
pn.major = :major,
pn.class_nature = :majoritem
FROM PERSONAL pn
INNER JOIN STUDENTS st on st.studentno = pn.studentno
INNER JOIN CLASSES on CLASSES.classno = st.CLASSNO
WHERE
LEFT(convert(varchar, st.ENTERDATE, 112),4) like :grade
and RTRIM(st.STUDENTNO) like :studentno
and RTRIM(st.NAME) like :studentname
and RTRIM(CLASSES.classno) like :classno
and RTRIM(CLASSES.SCHOOL) like :school';
//        mist($sql,array(
//            ':major'    => $majorno,
//            ':majoritem'    => $majoritemno,
//            ':grade' => $grade,
//            ':studentno' => $studentno,
//            ':studentname' => $studentname,
//            ':classname' => $classname,
//            ':school' => $school,
//        ));

        return $this->doneExecute($this->sqlExecute($sql,array(
            ':major'    => $majorno,
            ':majoritem'    => $majoritemno,
            ':grade' => $grade,
            ':studentno' => $studentno,
            ':studentname' => $studentname,
            ':classno' => $classno,
            ':school' => $school,
        )));
    }

}