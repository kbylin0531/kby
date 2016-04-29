<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/10/15
 * Time: 9:52
 */
class TeacherModel extends CommonModel{
    /**
     * 获取课程排课计划 的教师情况
     * 注意到 '008A00A2B' 2014年第2学期有多条教师记录，而原来的程序员只列出了第一条教室信息
     * 具体情况可能是排课计划中由于程序或认为导致的错误
     * 现在列出教室名称和教师号 例如：刘玲(122107),方黛春(122103),朱建国(121112),刘文霞(012415)
     *
     * 注：SchedulePlan 主键是 year term courseno group
     *
     * @param $year
     * @param $term
     * @param $coursegroupno
     * @return string
     */
    public function getTeachersBySchedulePlan($year,$term,$coursegroupno){
        $sql = "
SELECT distinct
dbo.group_concat( RTRIM(teachers.NAME)+'('+RTRIM(teachers.teacherno)+')',',' ) as teachers
FROM SCHEDULEPLAN
INNER JOIN TEACHERPLAN ON SCHEDULEPLAN.RECNO=TEACHERPLAN.MAP
INNER JOIN TEACHERS  ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO
INNER JOIN users on users.teacherno=teachers.teacherno
INNER JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE
WHERE SCHEDULEPLAN.YEAR=:year
AND SCHEDULEPLAN.TERM=:term
AND SCHEDULEPLAN.COURSENO+SCHEDULEPLAN.[GROUP]=:coursegroupno
GROUP BY SCHEDULEPLAN.COURSENO+SCHEDULEPLAN.[GROUP]";
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':coursegroupno'    => $coursegroupno,
        );
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind),false);
        if(is_string($rst) or !$rst){
            return '未知的教师';
        }else{
            return $rst['teachers'];
        }
    }

    /**
     * 添加导入的教师信息
     * @param $teachername
     * @param $school
     * @param $birthday
     * @param $sex
     * @param $position
     * @param $party
     * @param $enterdate
     * @param string $metas 其他信息的序列化
     * @return int|string
     */
    public function createTeachersAutomatic($teachername,$school,$birthday,$sex,$position,$party,$enterdate,$metas=''){
        $ssql = 'SELECT MAX(TEACHERNO)+1 as maxnum from TEACHERS WHERE ISNUMERIC(TEACHERNO) = 1';
        $rst = $this->doneQuery($this->sqlQuery($ssql),false);
        if(is_string($rst)){
            return $rst;
        }
        $maxnum = $rst['maxnum'];

        $this->startTrans();
        $isql1 = '
INSERT INTO [TEACHERS] ([NAME], [TEACHERNO], [POSITION], [SCHOOL],  [SEX], [Party],  [METAS])
VALUES (
:teachername,
:maxnum,
ISNULL((SELECT TOP 1 NAME FROM POSITIONS WHERE [VALUE] like :position), \'\'),
ISNULL((SELECT TOP 1 SCHOOL from SCHOOLS WHERE NAME like :school),\'\'),
-- :department, -- 部门
:sex,
-- ISNULL((SELECT TOP 1 CODE from NATIONALITYCODE WHERE NAME like :nationality),\'\'),
ISNULL((SELECT TOP 1 CODE from PARTYCODE WHERE NAME like :party),\'\'),
-- :birthday, -- birthday
-- :enterdate,  -- enterdate
:metas );';
        $rst =  $this->doneExecute($this->sqlExecute($isql1,array(
            ':teachername' => $teachername,
            ':maxnum'   => $maxnum,
            ':position' => $position,
            ':school' => $school,
            ':sex' => $sex,
            ':party' => $party,
//            ':birthday' => $birthday,
//            ':enterdate' => $enterdate,
            ':metas' => $metas,
        )));
        if(is_string($rst) or !$rst){
            return "添加到教师表失败！{$rst}";
        }
        $isql2 = '
INSERT INTO [USERS]
([USERNAME], [PASSWORD], [MODIFYDATE], [DAYSTOLIVE], [ROLES], [PROMPT], [TEACHERNO], [lock], [sails], [ISDEAN]) VALUES
(:username, \'123456\', GETDATE(), \'-1\', \'*B \', \'0\', :teacherno, \'0\', \'0\', \'0\');';
        $rst2 = $this->doneExecute($this->sqlExecute($isql2,array(
            ':username' => $maxnum,
            ':teacherno'    => $maxnum,
        )));
        if(is_string($rst2) or !$rst2){
            return "添加到用户表失败！{$rst2}";
        }
        $this->commit();
        return $rst2;
    }

}