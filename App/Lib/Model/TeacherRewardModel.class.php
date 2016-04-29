<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/3
 * Time: 9:52
 */
class TeacherRewardModel extends CommonModel {

    /**
     * 获取等级排序号
     * @param $levelid
     * @return string
     */
    public function getLevelSortNum($levelid){
        $sql = 'SELECT * from REWARD_LEVEL WHERE level_id = :levelid';
        $bind = array(':levelid'=>$levelid);
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind),false);
        if(is_string($rst) or !$rst){
            return "获取失败！".var_export($rst,true);
        }else{
            return $rst;
        }
    }

    /**
     * 获取登记权重信息和是否团队信息
     * @param string $fieldname 权重所在字段名称
     * @param int $id 记录id号
     * @return array|string
     */
    public function getRankInfoByField($fieldname,$id){
        $sql = "SELECT ISTEAM as isteam,{$fieldname} as weight,* from REWARD_RANK WHERE RANKID = :id";
        return $this->getInfo($sql,$id);
    }

    /**
     * 获取等级权重
     * @param int $id 记录号ID
     * @return array|string
     */
    public function getItemInfo($id){
        $sql = 'SELECT WEIGHTS as weight,* from REWARD_ITEMS WHERE ITEMID = :id';
        return $this->getInfo($sql,$id);
    }

    /**
     * 添加教师奖励项
     * @param $fields
     * @return int|string
     */
    public function createReward($fields){
        return $this->createRecord('REWARD',$fields);
    }

    /**
     * 删除教师奖励项
     * @param $rid
     * @return int|string
     */
    public function deleteReward($rid){
        $sql = 'DELETE from REWARD WHERE RID = :id';
        $bind = array(
            ':id'   => $rid,
        );
        return $this->doneExecute($this->sqlExecute($sql,$bind));
    }

    /**
     * 修改教师奖励项
     * @param $fields
     * @param $whrmap
     * @return int|string
     */
    public function updateReward($fields,$whrmap){
        return $this->updateRecords('REWARD',$fields,$whrmap);
    }

    protected function getInfo($sql,$id){
        $bind = array(':id' => $id);
        $rst = $this->doneQuery($this->sqlQuery($sql,$bind),false);
        if(is_string($rst) or !$rst){
            return "获取失败！".var_export($rst,true);
        }else{
            return $rst;
        }
    }

    public function formateItemPath($path){
        $path = str_replace('|',',',trim($path,'|'));
        $sql = "SELECT
dbo.GROUP_CONCAT(NAME,'>') as path
from REWARD_ITEMS WHERE ITEMID in ({$path})";
        $rst = $this->doneQuery($this->sqlQuery($sql),false);
        if(is_string($rst) or !$rst){
            return "获取失败！{$rst}";
        }
        return $rst['path'];
    }

    /**
     * 罗列统计数据
     * @param $year
     * @param $term
     * @param $levelname
     * @param $teachername
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listStatics($year,$term,$levelname,$teachername,$offset=null,$limit=null){
        $fields = '
RTRIM(t.NAME) as teachername,
r.reward_val,
r.level_name,
r.comment,
case
WHEN r.isteam = 1 then \'是\'
else \'否\' end as isteam ';
        $join = 'INNER JOIN TEACHERS t on t.TEACHERNO = r.TEACHERNO';
        $where = 'r.[YEAR] = :year and r.TERM = :term and r.RTYPE like :levelname and RTRIM(t.NAME) like :teachername';
        $csql = $this->makeCountSql('REWARD r ',array(
            'join'      => $join,
            'where'     => $where,
        ));
        $ssql = $this->makeSql('REWARD r ',array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
        ),$offset,$limit);
        return $this->getTableList($csql,$ssql,array(
            ':year' => $year,
            ':term' => $term,
            ':levelname' => $levelname,
            ':teachername' => $teachername,
        ));
    }


    public function getRewardLevelList(){
        return $this->getTable('REWARD_LEVEL');
    }

    public function getRewardRankList(){
        return $this->getTable('REWARD_RANK');
    }


}