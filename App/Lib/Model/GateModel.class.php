<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/10/22
 * Time: 9:51
 */
class GateModel extends CommonModel {
    /**
     * 初始化SQL
     * @var array
     */
    private static $_init = array(
        //成绩输入 id为1
        'result_input'    => "INSERT INTO [cwebs_gates] ( [name], [comment], [status], [begin_time], [end_time], [year], [term]) VALUES ( '成绩输入', '关闭后成绩输入将无法进行', '1', '2015-10-14 09:54:45', '2015-10-31 09:54:49', '2015', '1');",
    );

    /**
     * TODO:初始化Gate表
     * @return array
     */
    public static function init(){
        return self::$_init;
    }

    /**
     * 获取成绩输入时间限制
     * @return array|string
     */
    public function getResultInput(){
        $sql = "
SELECT
[name],
comment,
status,
ISNULL(CONVERT(VARCHAR(10),begin_time,20),'') as begin_time,
ISNULL(CONVERT(VARCHAR(10),end_time,20),'') as end_time,
[year],
term
from cwebs_gates where id=1";
        return $this->doneQuery($this->sqlQuery($sql),false);
    }
    /**
     * 更新成绩输入时间限制
     * @param $fields
     * @return int|string
     */
    public function updateResultInput($fields){
        return $this->updateRecords('cwebs_gates',$fields,array(
            'id'    => array(1,true),
        ));
    }

    /**
     * 判断是否在允许修改的时间内
     * @param null $year
     * @param null $term
     * @param $time
     * @return string|bool
     */
    public function isResultInputable($year,$term,$time){
        $rec = $this->getResultInput();
        if(is_string($rec) or !$rec){
            return "查询时间记录失败！{$rec}";
        }
        if(0 === intval($rec['status'])){
            return "第几学年第几学期当前状态是锁定的，有需要请联系管理员开启！";
        }
        if(intval($rec['year']) !== intval($year)){
            return "无法输入或者修改第{$year}学年的成绩，当前锁定第{$rec['year']}学年的成绩输入!";
        }
        if(intval($rec['term']) !== intval($term)){
            return "无法输入或者修改第{$term}学期的成绩，当前锁定第{$rec['term']}学期的成绩输入!";
        }
        if(strtotime($time) > strtotime($rec['end_time'])){
            return "修改时间为{$time}，成绩输入的结束时间是{$rec['end_time']},已经结束！";
        }
        if(strtotime($time) < strtotime($rec['begin_time'])){
            return "修改时间为{$time}，成绩输入的开始时间是{$rec['begin_time']},无法输入！";
        }
        return true;
    }



}