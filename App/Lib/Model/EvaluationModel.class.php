<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/23
 * Time: 9:16
 */
class EvaluationModel extends CommonModel {

    /**
     * 罗列
     * @param bool|false $withActive 是否只选择已经激活了的选项
     * @return array|string
     */
    public function listEvaluationItems($withActive=false){
        $withActive = $withActive?' and active = 1':'';
        $sql = "select id,id as oid,description,score,active from cwebs_evaluation_items {$withActive}";
        return $this->doneQuery($this->sqlQuery($sql));
    }

    public function createEvaluationItem($id,$description,$score){
        return $this->createRecord('cwebs_evaluation_items',array(
            'id'    => array($id,true),
            'description'  => $description,
            'score'        => $score,
        ));
    }

    public function deleteEvaluationItemById($oid){
        $sql = 'DELETE from cwebs_evaluation_items WHERE id = '.intval($oid);//使用intval可以避免注入
        return $this->doneExecute($this->sqlExecute($sql));
    }

    public function updateEvaluationItemById($oid,$nid,$description,$score){
        $sql = 'UPDATE cwebs_evaluation_items SET active = 1,id = :nid,description = :description,score = :score WHERE id = :oid';
        $bind = array(
            ':nid'  => $nid,
            ':description'  => $description,
            ':score'=> $score,
            ':oid'  => $oid
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    public function initEvaluationTeachers($year,$term,$clear=false){
        $this->startTrans();
        if($clear){
            $rst = $this->clearEvaluationTeachers($year,$term);
            if(is_string($rst)){
                return $rst;
            }
        }
        $sql = '
INSERT INTO cwebs_evaluation_teachers ([year],term,teacherno)
SELECT DISTINCT
s.[YEAR] as year,
s.TERM as term,
RTRIM(tp.TEACHERNO) as teacherno
FROM SCHEDULE s
INNER JOIN TEACHERPLAN tp on tp.RECNO = s.MAP
WHERE s.[YEAR] = :year and s.TERM = :term and not EXISTS (
SELECT 1 FROM cwebs_evaluation_teachers cet WHERE cet.year = s.[YEAR] and cet.term = s.TERM and cet.teacherno = tp.TEACHERNO)';
        $bind = array(':year' => $year,':term'    => $term);
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
        if(is_string($rst)){
            return $rst;
        }
        $this->commit();
        return $rst;
    }
    public function initEvaluationStudents($year,$term,$clear=false){
        $this->startTrans();
        if($clear){
            $rst = $this->clearEvaluationStudents($year,$term);
            if(is_string($rst)){
                return $rst;
            }
        }
        $sql = '
INSERT INTO cwebs_evaluation_students ([year],term,courseno,[group],studentno,teacherno)
SELECT
R32.[YEAR] as year,
R32.TERM as term,
R32.COURSENO as courseno,
R32.[GROUP] as [group],
R32.STUDENTNO as studentno,
RTRIM(tp.TEACHERNO) as teacherno
FROM R32
INNER JOIN SCHEDULE s on s.COURSENO = R32.COURSENO and s.[GROUP] = R32.[GROUP] and s.[YEAR] = R32.[YEAR] and s.TERM = R32.TERM
INNER JOIN TEACHERPLAN tp on s.MAP = tp.RECNO
WHERE R32.[YEAR] = :year and R32.TERM = :term AND NOT EXISTS (
SELECT 1 FROM cwebs_evaluation_students ces WHERE ces.year = R32.[YEAR] and ces.term = R32.TERM and ces.courseno = R32.COURSENO
and ces.[group] = R32.[GROUP] and ces.studentno = R32.STUDENTNO and ces.teacherno = tp.TEACHERNO)';
        $bind = array(':year' => $year,':term'    => $term);
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
        if(is_string($rst)){
            return $rst;
        }
        $this->commit();
        return $rst;
    }

    protected function clearEvaluationTeachers($year,$term){
        $sql = 'DELETE FROM cwebs_evaluation_teachers WHERE [year] = :year and term = :term';
        $bind = array(':year' => $year,':term'    => $term);
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    protected function clearEvaluationStudents($year,$term){
        $sql = 'DELETE FROM cwebs_evaluation_students WHERE [year] = :year and term = :term';
        $bind = array(':year' => $year,':term'    => $term);
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    public function listEvaluationResult($year,$term,$classno,$teacherno,$offset=null,$limit=null){
        $fields = '
cetc.teacherno,
cetc.classno,
cetc.courseno+cetc.[group] as coursegroup,
RTRIM(TEACHERS.NAME) as teachername,
RTRIM(CLASSES.CLASSNAME) as classname,
RTRIM(COURSES.COURSENAME) as coursename,
cetc.score_avg';
        $join = '
INNER JOIN TEACHERS on TEACHERS.TEACHERNO = cetc.teacherno
INNER JOIN CLASSES on CLASSES.CLASSNO = cetc.classno
INNER JOIN COURSES on COURSES.COURSENO = cetc.courseno';
        $where = 'cetc.classno like :classno and cetc.teacherno like :teacherno
and cetc.[year] = :year and cetc.term = :term';
        $order = 'cetc.score_avg';
        $csql = $this->makeCountSql('view_evaluation_teacher_courses cetc',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('view_evaluation_teacher_courses cetc',array(
            'fields'    => $fields,
            'join'  => $join,
            'where' => $where,
            'order' => $order,
        ),$offset,$limit);
        $bind = array(
            ':classno'  => $classno,
            ':teacherno'    => $teacherno,
            ':year' => $year,
            ':term' => $term,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }


    public function listEvaluationDetail($year,$term,$classno,$teacherno,$coursegroup){
        $sql = '
SELECT
ces.scores_detail,
ces.scores_general,
ces.remark
FROM cwebs_evaluation_students ces
INNER JOIN STUDENTS ON STUDENTS.STUDENTNO = ces.studentno
WHERE ces.[year] = :year AND ces.term = :term AND ces.teacherno = :teacherno
	AND ces.courseno+ces.[group] = :coursegroup and STUDENTS.CLASSNO = :classno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':teacherno'    => $teacherno,
            ':coursegroup'  => $coursegroup,
            ':classno'      => $classno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    public function listEvaluationCollect($year,$term,$classno='%',$teacherno='%',$offset=null,$limit=null){
        $filter = '';
        if($classno !== '%'){
            //加入班级时增加筛选条件
            $searchModel = new SearcherModel();
            $teacherlist = $searchModel->getTeachersByClassno($year,$term,$classno);
            $filter = " and cet.teacherno in ('";
            foreach($teacherlist as $teacher){
                $filter .= "{$teacher['value']}','";
            }
            $filter = rtrim($filter,"',")."')";
//            mist($filter);
        }
        $fiedls = '
cet.teacherno,
RTRIM(TEACHERS.NAME) as teachername,
AVG(vet.score_avg) as score_avg,
cet.score_evaluation';
        $join = '
INNER JOIN TEACHERS on TEACHERS.TEACHERNO = cet.teacherno
INNER JOIN view_evaluation_teacher vet on vet.[year] = cet.[year] and vet.term = cet.term and vet.teacherno = cet.teacherno ';
        $where = "cet.[year] = :year and cet.term = :term {$filter}";
        $group = 'cet.teacherno,cet.[year],cet.term,TEACHERS.NAME,cet.score_evaluation';
        $csql = $this->makeCountSql('cwebs_evaluation_teachers  cet',array(
            'join'      => $join,
            'where'     => $where,
            'group'     => $group,
        ));
        $ssql = $this->makeSql('cwebs_evaluation_teachers  cet',array(
            'fields'    => $fiedls,
            'join'      => $join,
            'where'     => $where,
            'group'     => $group,
        ),$offset,$limit);
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':teacherno'    => $teacherno,
        );
//        mist($csql,$ssql,$bind);
        return $this->getTableList2($csql,$ssql,$bind);
    }

    public function unactiveAllEvaluationItem(){
        $sql = 'UPDATE cwebs_evaluation_items SET active = 0 ';
        return $this->doneExecute($this->sqlExecute($sql));
    }

    public function regenerateEvaluationScore($year,$term){
        $sourse_sql = '
SELECT
cet.[year],
cet.term,
cet.teacherno,
vet.classno,
temp2.score_max,
temp2.score_min,
ISNULL(temp.score_avg,0) as score_avg
FROM cwebs_evaluation_teachers  cet
INNER JOIN view_evaluation_teacher_courses vet on vet.[year] = cet.[year] and vet.term = cet.term and vet.teacherno = cet.teacherno
LEFT OUTER JOIN ( -- 获取教师平均分
	select DISTINCT
	 [year],term,teacherno,
	AVG(score_avg) as score_avg
	FROM view_evaluation_teacher
	WHERE score_avg != 0
	GROUP BY year,term,teacherno
) temp  on temp.[year] = cet.[year] and temp.term = cet.term and temp.teacherno = cet.teacherno
INNER JOIN ( -- 获取最高和最低分
	SELECT
	[year],term,classno,
	MAX(score_avg) as score_max,
	MIN(score_avg) as score_min
	FROM view_evaluation_teacher
		WHERE score_avg != 0
	GROUP BY [year],term,classno
) temp2 on  temp2.[year] = cet.[year] and temp2.term = cet.term AND temp2.classno = vet.classno
WHERE cet.[year] = :year and cet.term = :term and temp.score_avg > 0
ORDER BY vet.classno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
        );
        $sourse = $this->doneQuery($this->sqlQuery($sourse_sql,$bind));
        if(is_string($sourse)){
            return "查询出错！{$sourse}";
        }

        //遍历计算 教师在各个班级的平均分
        $tscores = array();
        foreach($sourse as $cteacher){
            $score_evaluation = 10 - ($cteacher['score_max'] - $cteacher['score_avg'])/10;
            $key = ''.$cteacher['teacherno'];
            if(!isset($tscores[$key])){
                $tscores[$key] = array(
                    'sum'   => 0, // 该教师在各个班级的分数总和
                    'count' => 0, // 该教师的总分分散在几个班级
                );
            }
            $tscores[$key]['sum'] += intval($score_evaluation);
            ++ $tscores[$key]['count'];
        }

        //执行数据插入
        $updsql = 'UPDATE cwebs_evaluation_teachers SET score_evaluation = :score WHERE
                  [year] = :year and term = :term and teacherno = :teacherno ';
        $count = 0;

//        mist($tscores);

        foreach($tscores as $teacherno=>$scores){
            $updbind = array(
                ':score'    => intval($scores['sum']/$scores['count']),
                ':year'     => $year,
                ':term'     => $term,
                ':teacherno'=> ''.$teacherno,//需要转化为字符串，否则会被单座整数导致无法修改教师号带字符串的记录
            );
            $updrst = $this->doneExecute($this->sqlExecute($updsql,$updbind));
            if(is_string($updrst) or !$updrst) {
                varsdumpout($updrst,$updsql,$updbind);
                return "更新过程出现错误！{$updrst}";
            }
            ++ $count;
        }
        return $count;
    }


//TODO:学生端
    /**
     * 获取学生需要评价的教师列表
     * @param $year
     * @param $term
     * @param $studentno
     * @return array|string
     */
    public function listTeacherEvalutions($year,$term,$studentno){
        $sql = '
SELECT
ces.courseno+ces.[group] as coursegroup,
ces.teacherno,
RTRIM(TEACHERS.NAME) as teachername,
RTRIM(COURSES.COURSENAME) as coursename,
ces.scores_detail,
ces.scores_general,
convert(varchar(10), ces.input_date, 20) as input_date,
ces.recno
from cwebs_evaluation_students ces
INNER JOIN TEACHERS on TEACHERS.TEACHERNO = ces.teacherno
INNER JOIN COURSES on COURSES.COURSENO = ces.courseno
WHERE ces.[year] = :year and ces.term = :term and ces.studentno = :studentno';
        $bind = array(
            ':year' => $year,
            ':term' => $term,
            ':studentno'    => $studentno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    public function updateTeacherEvalution($detail,$sum,$remark,$recno){
        $sql = 'UPDATE cwebs_evaluation_students SET scores_detail = :scores_detail,scores_general = :score_general,
            remark=:remark,input_date=GETDATE() WHERE recno = :recno AND input_date is NULL';//AND input_date is NULL表示位老师还未输入过
        $bind = array(
            ':scores_detail'    => $detail,
            ':score_general'    => $sum,
            ':remark'           => $remark,
            ':recno'            => $recno,
        );
//        mist($sql,$bind);
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 获取评教详细信息
     * @param $recno
     * @return array|string
     */
    public function getEvalutionItemDetailByRecno($recno){
        return $this->doneQuery($this->sqlQuery('select
RTRIM(COURSENAME) as coursename,
RTRIM(TEACHERS.NAME) as teachername
from cwebs_evaluation_students ces
INNER JOIN COURSES on COURSES.COURSENO = ces.courseno
INNER JOIN TEACHERS on TEACHERS.TEACHERNO = ces.teacherno
WHERE ces.recno = :recno',array(
            ':recno'    => $recno,
        )),false);
    }


}