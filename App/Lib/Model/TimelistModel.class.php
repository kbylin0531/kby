<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/13
 * Time: 9:28
 */
class TimelistModel extends CommonModel{


    /**
     * 删除时间表
     * @param $year
     * @param $term
     * @param $who
     * @param $type
     * @param string $para
     * @return int|string
     */
    public function deleteTimeList($year,$term,$who,$type,$para=''){
        return $this->deleteRecords('TIMELIST',array(
            'YEAR' => $year,
            'TERM' => $term,
            'WHO' => $who,
            'TYPE' => $type,
            'PARA' => $para,
        ));
    }

    /**
     * 获取学生的已选的课程的时间信息
     * 课程列表从R32中获取
     * 时间信息对应于TIMELIST的MON~SUN字段的按位或叠加
     * @param $year
     * @param $term
     * @param $studentno
     * @return array|string array只获取第一条结果 string数据库查询错误信息
     */
    public function getStudentCourseTimelist($year,$term,$studentno){
        $sql = "
SELECT
dbo.GROUP_OR(MON) as MON,
dbo.GROUP_OR(TUE) as TUE,
dbo.GROUP_OR(WES) as WES,
dbo.GROUP_OR(THU) as THU,
dbo.GROUP_OR(FRI) AS FRI,
dbo.GROUP_OR(SAT) AS SAT,
dbo.GROUP_OR(SUN) as SUN
FROM TIMELIST T
INNER JOIN R32 R ON T.[YEAR]=R.[YEAR] AND T.TERM=R.TERM AND T.WHO=R.COURSENO AND T.PARA=R.[GROUP]
WHERE T.TYPE='P' AND R.YEAR=:YEAR AND R.TERM=:TERM AND R.STUDENTNO=:STUDENTNO";
        $bind = array(
            ':YEAR' => $year,
            ':TERM' => $term,
            ':STUDENTNO' => $studentno,
        );
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }


    /**
     * 根据参数创建TIMELIST记录仪
     * @param $fields
     * @return int|string string错误信息 int表示创建成功的记录
     */
    public function createTimelistRecord($fields){
        if(CommonModel::checkInvalidExist(null,$fields['YEAR'],$fields['TERM'],$fields['WHO'],$fields['TYPE'],$fields['PARA'])){
            return '参数不合法，timelist';
        }
        return $this->createRecord('TIMELIST',$fields);
    }

    public function updateTimelistRecord($fields,$where){
        return $this->updateRecords('TIMELIST',$fields,$where);
    }


    /**
     * 比较前后二者是否存在时间上的冲突
     * @param $weeks
     * @param $compweeks
     * @return bool
     */
    public static function checkConflict(&$weeks,&$compweeks){
        return ($weeks['MON'] & $compweeks['MON']) ||
        ($weeks['TUE'] & $compweeks['TUE']) ||
        ($weeks['WES'] & $compweeks['WES']) ||
        ($weeks['THU'] & $compweeks['THU']) ||
        ($weeks['FRI'] & $compweeks['FRI']) ||
        ($weeks['SAT'] & $compweeks['SAT']) ||
        ($weeks['SUN'] & $compweeks['SUN']);
    }

}