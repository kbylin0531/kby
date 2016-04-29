<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/10/20
 * Time: 16:46
 */


/**
 * Class CourseGeneralScoreManagementModel 课程总评成绩百分比管理模型
 */
class CourseGeneralScoreManagementModel extends CommonModel {

    protected $_convention = array(
        'COURSENO_LEN'  => 7,//针对限制的课程长度，如果针对组号，请设置为9
    );

    /**
     * 获取百分比设置 列表
     * @param string $coursegroup 用于精确查找的课程号加组号
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function getPercentSettingLikableTableList($coursegroup='%',$offset=null,$limit=null){
        $csql = $this->makeCountSql('cwebs_score_percent',array(
            'where' => 'coursegroup like :coursegroup',
        ));
        $ssql = $this->makeSql('cwebs_score_percent',array(
            'where' => 'coursegroup like :coursegroup',
        ),$offset,$limit);
        $bind = array(
            ':coursegroup'  => $coursegroup
        );
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 获取默认的百分比设置
     * @return array|string
     */
    public function getDefaultPercentSetting(){
        $sql = "SELECT * from cwebs_score_percent WHERE coursegroup = '000000000';";
        return $this->doneQuery($this->sqlQuery($sql),false);
    }

    /**
     * 添加特殊课程显示百分比
     * @param $coursegroup
     * @param $normalscore
     * @param $midtermscore
     * @param $finalsscore
     * @return int|string
     */
    public function createPercentSetting($coursegroup,$normalscore,$midtermscore,$finalsscore){
        if(intval($normalscore) +intval($midtermscore) +intval($finalsscore)  !== 100 ){
            return "未达到100的百分比!";
        }
        return $this->createRecord('cwebs_score_percent',array(
            'coursegroup'   => $coursegroup,
            'normalscore'   => $normalscore,
            'midtermscore'  => $midtermscore,
            'finalsscore'   => $finalsscore,
        ));
    }

    /**
     * 删除特殊课程显示百分比
     * @param $coursegroup
     * @return int|string
     */
    public function deletePercentSetting($coursegroup){
        if('000000000' === trim($coursegroup)){
            return "无法删除默认的百分比设置";
        }
        return $this->deleteRecords('cwebs_score_percent',array(
            'coursegroup'   => $coursegroup,
        ));
    }

    /**
     * 获取课程的百分比设置
     * @param $coursegroup
     * @return array|int|string
     */
    public function getCourseScoreSetting($coursegroup){
        //先查询是否有对应的可好组号设置，再查询是否有课程号设置
        $rst = $this->getTable('cwebs_score_percent',array(
            'coursegroup'   => $coursegroup,
        ));
        if(is_string($rst)){
            return "查询出错！{$rst}";
        }elseif(empty($rst)){
            //可能只有课程号
            $rst = $this->getTable('cwebs_score_percent',array(
                'coursegroup'   => substr($coursegroup,0,$this->_convention['COURSENO_LEN']),
            ));
            if(!is_string($rst) and $rst){
                return $rst[0];
            }else{
                $rst = $this->getDefaultPercentSetting();
                if(is_string($rst)){
                    return "获取课程总评百分比设置失败！ {$rst}";
                }
                return $rst;//返回默认设置
            }
        }else{
            return $rst[0];
        }
    }

    /**
     * 更新百分比设置
     * @param $coursegroup
     * @param $normal
     * @param $midterm
     * @param $finals
     * @return int|string
     */
    public function updatePercentSetting($coursegroup,$normal,$midterm,$finals){
        return $this->updateRecords('cwebs_score_percent',array(
            'normalscore'   => $normal,
            'midtermscore'  => $midterm,
            'finalsscore'   => $finals,
        ),array(
            'coursegroup'   => $coursegroup,
        ));
    }


}