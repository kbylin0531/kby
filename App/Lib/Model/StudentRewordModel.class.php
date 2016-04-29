<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2015/12/24
 * Time: 15:30
 */
class StudentRewordModel extends CommonModel {

    /**
     * 证书
     */
    const TBL_CERTIFICATE = 'cwebs_sreward_certificate';
    /**
     * 比赛等级\名次\比赛学分设置
     */
    const TBL_COMPETITION_LEVEL = 'cwebs_sreward_competition_level';
    const TBL_COMPETITION_RANK = 'cwebs_sreward_competition_rank';
    const TBL_COMPETITION_SETTING = 'cwebs_sreward_competition_setting';
    /**
     * 发展性课程类型\子类型\课程学分设置
     */
    const TBL_DEVELOP_TYPE    = 'cwebs_sreward_develop_type';
    const TBL_DEVELOP_SUBTYPE = 'cwebs_sreward_develop_subtype';
    const TBL_DEVELOP_SETTING = 'cwebs_sreward_develop_setting';
    /**
     * 奖励记录表
     */
    const TBL_RECORDLIST = 'cwebs_sreward_recordlist';

//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:系统设置
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ

    /**
     * 罗列证书列表数据
     * @param string $name 证书名称
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listCertificate($name,$offset=null,$limit=null){
        $where = array(
            'where' => 'name like :name',
        );
        return $this->getTableList(
            $this->makeCountSql(self::TBL_CERTIFICATE,$where),
            $this->makeSql(self::TBL_CERTIFICATE,$where,$offset,$limit),array(':name'=>$name));
    }

    /**
     * 创建证书项
     * INSERT INTO [cwebs_sreward_certificate] ([name], [credit], [status]) VALUES ('测试证书01', '2', '1');
     * @param string $name 证书名称
     * @param float $credit 证书学分
     * @param int $status 证书启用状态
     * @return int|string
     */
    public function createCertificate($name,$credit,$status=1){
        return $this->createRecord(self::TBL_CERTIFICATE,array(
            'name' => $name,
            'credit'   => $credit,
            'status'   => $status,
        ));
    }

    /**
     * 删除一个证书项
     * @param int $id 证书项ID
     * @return int|string
     */
    public function deleteCertificate($id){
        return $this->deleteRecords(self::TBL_CERTIFICATE,array(
            'id'    => $id,
        ));
    }


    /**
     * 修改证书
     * @param int $id 证书ID
     * @param null|string  $name 证书名称
     * @param null $credit
     * @param int $status
     * @return int|string
     */
    public function updateCertificate($id,$name=null,$credit=null,$status=null){
        $fields = array();
        isset($name) and $fields['name'] = $name;
        isset($credit) and $fields['credit'] = $credit;
        isset($status) and $fields['status'] = $status;
        return $this->updateRecords(self::TBL_CERTIFICATE,$fields,array('id'=>$id));
    }


//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
    /**
     * 罗列比赛等级列表
     * @param string $type 等级名称
     * @param string $name 等级名称
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listCompetitionLevel($type,$name='%',$offset=null,$limit=null){
        $comps = array('where'=>'type = :type and name like :name');
        return $this->getTableList(
            $this->makeCountSql(self::TBL_COMPETITION_LEVEL,$comps),
            $this->makeSql(self::TBL_COMPETITION_LEVEL,$comps,$offset,$limit),array(
            ':type' => $type,
            ':name' => $name,
        ));
    }

    /**
     * 添加比赛等级
     * @param string $name 等级名称
     * @param string $type 等级名称
     * @return int|string
     */
    public function createCompetitionLevel($name,$type){
        return $this->createRecord(self::TBL_COMPETITION_LEVEL,array(
            'name' => $name,
            'type'   => $type,
        ));
    }

    /**
     * 删除一个比赛等级
     * @param int $id 比赛等级ID
     * @return int|string
     */
    public function deleteCompetitionLevel($id){
        return $this->deleteRecords(self::TBL_COMPETITION_LEVEL,array('id'=>$id));
    }

    /**
     * 修改比赛等级
     * @param int $id 比赛等级ID
     * @param string $name 等级名称
     * @param null|string $type 等级类型,名次类型为null时不作修改
     * @return int|string
     */
    public function updateCompetitionLevel($id,$name,$type=null){
        $fields = array('name'=>$name);
        isset($type) and $fields['type'] = $type;
        return $this->updateRecords(self::TBL_COMPETITION_LEVEL,$fields,array('id'=>$id));
    }

//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
    /**
     * 根据类型罗列名次列表
     * @param string $type 名次类型
     * @param string $name 名次名称
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listCompetitionRank($type,$name='%',$offset=null,$limit=null){
        $comp = array('where'=>'type = :type and name like :name');
        return $this->getTableList(
            $this->makeCountSql(self::TBL_COMPETITION_RANK,$comp),
            $this->makeSql(self::TBL_COMPETITION_RANK,$comp,$offset,$limit),array(
            ':type' => $type,
            ':name' => $name,
        ));
    }

    /**
     * 添加名词列表
     * @param string $name 名次名称
     * @param string $type 名称类型
     * @return int|string
     */
    public function createCompetitionRank($name,$type){
        return $this->createRecord(self::TBL_COMPETITION_RANK,array(
            'name' => $name,
            'type'   => $type,
        ));
    }

    /**
     * 删除一个名次
     * @param int $id 名次ID
     * @return int|string
     */
    public function deleteCompetitionRank($id){
        return $this->deleteRecords(self::TBL_COMPETITION_RANK,array('id'=>$id));
    }

    /**
     * 修改名次
     * @param $id
     * @param $name
     * @param $type
     * @return int|string
     */
    public function updateCompetitionRank($id,$name,$type=null){
        $fields = array('name'=>$name);
        isset($type) and $fields['type'] = $type;
        return $this->updateRecords(self::TBL_COMPETITION_RANK,$fields,array('id'=>$id));
    }

//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ

    /**
     * 罗列比赛名次等级学分奖励列表
     * @param string $type
     * @param string $lid
     * @param string $rid
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listCompetitionSetting($type='%',$lid='%',$rid='%',$offset=null,$limit=null){
        $fields = 'lid,rid,cast(credit as float) as credit,cl.name as lname,cr.name as rname';
        $join = '
LEFT OUTER JOIN cwebs_sreward_competition_level cl on cl.id = cs.lid
LEFT OUTER JOIN cwebs_sreward_competition_rank cr ON cr.id = cs.rid';
        $where = 'CAST(lid as VARCHAR(3)) like :lid and CAST(rid as VARCHAR(3)) like :rid and cs.type like :type';
        return $this->getTableList(
            $this->makeCountSql('cwebs_sreward_competition_setting cs',array(
                'join'  => $join,
                'where' => $where,
            )),
            $this->makeSql('cwebs_sreward_competition_setting cs',array(
                'fields'=> $fields,
                'join'  => $join,
                'where' => $where,
            ),$offset,$limit),array(
            ':lid'  => $lid,
            ':rid'  => $rid,
            ':type' => $type,
        ));
    }
    /**
     * 添加设置
     * @param $lid
     * @param $rid
     * @param $credit
     * @param $type
     * @return int|string
     */
    public function createCompetitionSetting($lid,$rid,$credit,$type){
        return $this->createRecord(self::TBL_COMPETITION_SETTING,array(
            'lid'   => $lid,
            'rid'   => $rid,
            'credit'=> $credit,
            'type'  => $type,
        ));
    }

    /**
     * 删除一个设置
     * @param $lid
     * @param $rid
     * @return int|string
     */
    public function deleteCompetitionSetting($lid,$rid){
        return $this->deleteRecords(self::TBL_COMPETITION_SETTING,array('lid'=>$lid,'rid'=>$rid));
    }

    /**
     * 修改一个设置
     * @param int $lid 旧的level id
     * @param int $rid 旧的rank id
     * @param int $lname 新的level id
     * @param int $rname 新的rank id
     * @param int $credit 学分
     * @return int|string
     */
    public function updateCompetitionSetting($lid,$rid,$lname,$rname,$credit){
        $fields = array(':credit'   => $credit,);
        $lpart = $rpart = '';
        if(is_numeric($lname)){
            $lpart = ', lid = :nlid';
            $fields[':nlid'] = $lname;
        }
        if(is_numeric($rname)){
            $lpart = ', rid = :nrid';
            $fields[':nrid'] = $rname;
        }
        $fields = array_merge($fields,array(
            ':orid'   => $lid,
            ':olid'   => $rid,
        ));
        $sql = "update cwebs_sreward_competition_setting set credit = :credit  {$lpart} {$rpart} where lid = :orid and rid = :olid";

        $rst = $this->doneExecute($this->sqlExecute($sql,$fields));
//        mist($rst,$sql,$fields);
        return $rst;
    }

//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
    /**
     * 罗列二维数据表显示
     * @return array|string
     */
    public function listDevolopmentCourseType(){
        $list = $this->doneQuery($this->sqlQuery('
SELECT
ct.id as pid,
ct.name as pname,
cs.id as cid,
cs.name as cname
FROM  cwebs_sreward_develop_type ct
LEFT OUTER JOIN cwebs_sreward_develop_subtype cs ON ct.id = cs.supertype'));
        if(is_string($list)){
            return $list;
        }
        $result = array();
        foreach($list as $item){
            if(!isset($result[$item['pid']])){
                $result[$item['pid']] = array(
                    'id'        => $item['pid'],
                    'name'     => $item['pname'],
                    'text'     => $item['pname'],
                    'pid'      => '',
                    'cid'      => '',
                    'children'  => array(),
                );
            }
            if(isset($item['cid'])){
                $result[$item['pid']]['children'][] = array(
                    'id'       => $item['pid'].'_'.$item['cid'],
                    'name'     => $item['cname'],
                    'text'     => $item['cname'],
                    'pid'      => $item['pid'],
                    'cid'      => $item['cid'],
                );
            }
        }
        return array_values($result);
    }

    /**
     * @param int $type
     * @return array
     * @throws Exception
     */
    public function getTreeCombo($type){
        $result = array();
        switch(intval($type)){
            case 1://level
                $sql = '
select cl.id,cl.name,cl.type,ct.name as typename
FROM cwebs_sreward_competition_level cl
INNER JOIN cwebs_sreward_competition_type ct ON cl.type = ct.code';
                break;
            case 2://rank
                $sql = '
SELECT cr.id,cr.name,cr.type,ct.name as typename
FROM cwebs_sreward_competition_rank cr
INNER JOIN cwebs_sreward_competition_type ct ON cr.type = ct.code';
                break;
            default:
                throw new Exception('无法识别的类型');
        }
        $list = $this->doneQuery($this->sqlQuery($sql));
        if(is_string($list)){
            return $list;
        }
        foreach($list as $item){
            if(!isset($result[$item['type']])){
                $result[$item['type']] = array(
                    'id'       => $item['type'],
                    'name'     => $item['name'],
                    'text'      => $item['typename'],
                    'children'  => array(),
                );
            }
            $result[$item['type']]['children'][] = array(
                'id'       => $item['id'],
                'name'     => $item['name'],
                'text'      => $item['name'],
                'type'      => $item['type'],
            );
        }
        return array_values($result);
    }

    /**
     * 创建类型或者子类型
     * @param $name
     * @return int|string
     */
    public function createDevolopmentCourseType($name){
        return $this->createRecord(self::TBL_DEVELOP_TYPE,array(
            'name' => $name,
        ));
    }
    public function deleteDevolopmentCourseType($id){
        //先确认是否有子类型没有删除干净
        $list = $this->getTable(self::TBL_DEVELOP_SUBTYPE,array(
            'supertype' => $id,
        ),true);
        if(is_string($list)){
            return "查询是否有子类型没有删除干净出错！ {$list}}";
        }elseif($list){
            return "请先将子类型删除干净在删除父类型！";
        }
        return $this->deleteRecords(self::TBL_DEVELOP_TYPE,array(
            'id'    => $id,
        ));
    }
    public function updateDevolopmentCourseType($id,$name){
        $fields = array('name'=>$name);
        return $this->updateRecords(self::TBL_DEVELOP_TYPE,$fields,array('id'=>$id));
    }

//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
    /**
     * 罗列二维数据表显示
     * @param string $supertype
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listDevolopmentCourseSubType($supertype='%',$offset=null,$limit=null){
        $components = array('where'=>'CAST(id as VARCHAR(3)) like :sid');
        return $this->getTableList(
            $this->makeCountSql(self::TBL_DEVELOP_SUBTYPE,$components),
            $this->makeSql(self::TBL_DEVELOP_SUBTYPE,$components,$offset,$limit),array(
                ':sid'  => $supertype,
            ));
    }
    /**
     * 创建类型或者子类型
     * @param string $name
     * @param int $supertype
     * @return int|string
     */
    public function createDevolopmentCourseSubType($name,$supertype){
        return $this->createRecord(self::TBL_DEVELOP_SUBTYPE,array(
            'name'      => $name,
            'supertype' => $supertype,
        ));
    }

    public function deleteDevolopmentCourseSubType($id){
        return $this->deleteRecords(self::TBL_DEVELOP_SUBTYPE,array(
            'id'    => $id,
        ));
    }
    public function updateDevolopmentCourseSubType($id,$name){
        $fields = array('name'=>$name);
        return $this->updateRecords(self::TBL_DEVELOP_SUBTYPE,$fields,array('id'=>$id));
    }

//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
    /**
     * 发展性课程设置
     * @param string $name 课程名称
     * @param string $type 上级类型
     * @param string $subtype 子类型
     * @param null $offset
     * @param null $limit
     * @return array|string
     */
    public function listDevolopmentSetting($name='%',$type='%',$subtype='%',$offset=null,$limit=null){
        $components = array('where'=>'CAST(type as VARCHAR(3)) like :type and CAST(subtype as VARCHAR(3)) like :subtype and name like :name');
        return $this->getTableList(
            $this->makeCountSql(self::TBL_DEVELOP_SETTING,$components),
            $this->makeSql(self::TBL_DEVELOP_SETTING,$components,$offset,$limit),
            array(
                ':type'    => $type,
                ':subtype' => $subtype,
                ':name'    => $name,
            ));
    }

    /**
     * 添加发展性课程设置
     * @param $name
     * @param $type
     * @param $subtype
     * @param $credit
     * @param string $rem
     * @return int|string
     */
    public function createDevolopmentSetting($name,$type,$subtype,$credit,$rem=''){
        return $this->createRecord(self::TBL_DEVELOP_SETTING,array(
            'name'      => $name,
            'type'      => $type,
            'subtype'   => $subtype,
            'credit'    => $credit,
            'rem'       => $rem,
        ));
    }

    /**
     * 删除发展性学分设置
     * @param $id
     * @return int|string
     */
    public function deleteDevolopmentSetting($id){
        return $this->deleteRecords(self::TBL_DEVELOP_SETTING,array(
            'id'    => $id,
        ));
    }

    /**
     * 修改发展性学分设置
     * @param $id
     * @param null $name
     * @param null $type
     * @param null $subtype
     * @param null $rem
     * @return int|string
     */
    public function updateDevolopmentSetting($id,$name=null,$type=null,$subtype=null,$rem=null){
        $fields = array();
        $name and $fields['name'] = $name;
        $type and $fields['type'] = $type;
        $subtype and $fields['subtype'] = $subtype;
        $rem and $fields['rem'] = $rem;
        return $this->updateRecords(self::TBL_CERTIFICATE,$fields,array('id'=>$id));
    }



//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ


    public function listDevolopmentCourse($name='%',$offset=null,$limit=null){
        $fields = 'cs.id,
cs.name,
cs.subtype,
cs2.name as subtype_name,
cast(cs.credit as float) as credit,
cs.rem';
        $tablename = 'cwebs_sreward_develop_setting cs ';
        $join = 'inner JOIN cwebs_sreward_develop_subtype cs2 on cs2.id = cs.subtype';
        $where = 'cs.name like :name';
        $order = 'cs.subtype';
        return $this->getTableList($this->makeCountSql($tablename,array('join'=>$join,'where'=>$where)),$this->makeSql($tablename,array(
            'fields'    => $fields,
            'join'      => $join,
            'where'     => $where,
            'order'     => $order,
        ),$offset,$limit),array(
            ':name' => $name,
        ));
    }

    public function createDevolopmentCourse($name,$subtype,$credit,$rem=''){
        return $this->createRecord(self::TBL_DEVELOP_SETTING,array(
            'name'=> $name,
            'subtype'   =>$subtype,
            'credit'    => $credit,
            'rem'       => $rem,
        ));
    }
    public function updateDevolopmentCourse($id,$name=null,$subtype=null,$credit=null,$rem=null){
        $fields = array();
        isset($name) and $fields['name'] = $name;
        isset($subtype) and $fields['subtype'] = $subtype;
        isset($credit) and $fields['credit'] = $credit;
        isset($rem) and $fields['rem'] = $rem;
        if(empty($fields)) return "无修改项";

        $rst = $this->updateRecords(self::TBL_DEVELOP_SETTING,$fields,array('id'=>$id));
//        mist($rst,self::TBL_DEVELOP_SETTING,$fields,array('id'=>$id));
        return $rst;
    }
    public function deleteDevolopmentCourse($id){
        return $this->deleteRecords(self::TBL_DEVELOP_SETTING,array('id'=>$id));
    }



//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ

    /**
     * 获取比赛等级列表
     * @param string $type
     * @return array|string
     */
    public function getLevelsByCompetitionType($type='%'){
        return $this->doneQuery($this->sqlQuery('SELECT * from cwebs_sreward_competition_level WHERE [type] like :type',array(':type'=>$type)));
    }

    /**
     * 获取比赛名称列表
     * @param $type
     * @return array|string
     */
    public function getRanksByCompetitionType($type){
        return $this->doneQuery($this->sqlQuery('SELECT * from cwebs_sreward_competition_rank WHERE [type] like :type',array(':type'=>$type)));
    }

    public function getDevelopList(){
        $list =  $this->getTable(self::TBL_DEVELOP_SETTING);
        if(is_array($list)){
            foreach($list as &$item){
                $item['serialize'] = serialize($item);
            }
        }
        return $list;
    }


    public function getStudentsByClassno($classno=null,$studentno='%'){
        if('%' === $studentno and null !== $classno){
            $rst = $this->doneQuery($this->sqlQuery(
                'SELECT DISTINCT STUDENTNO as studentno FROM STUDENTS WHERE RTRIM(CLASSNO) like :classno',
                array(
                ':classno'  => $classno,
            )));
            if(is_string($rst)){
                throw new Exception($rst);
            }
        }elseif($studentno !== null){
            $rst = array('studentno'=>$studentno);
        }else{
            throw new Exception('参数错误！');
        }
        return $rst;
    }

//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:数据管理
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ
//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ

    public function listEncourageOfDevolopment($year,$term,$classno='%',$studentno='%',$projname='%',$offset=null,$limit=null){
        $fields = '
cr.[year],
cr.term,
CAST(cr.[year] as VARCHAR)+\'学年\'+CAST(cr.[term] as VARCHAR)+\'学期\' as yearterm,
cr.studentno,
RTRIM(st.NAME) as studentname,
RTRIM(cls.CLASSNAME) as classname,
cr.name as projectname,
cast(cr.credit as float) as credit,
cr.id,
cr.rem';
        $join = '
INNER JOIN STUDENTS st on st.STUDENTNO = cr.studentno
INNER JOIN CLASSES cls ON cls.CLASSNO = st.CLASSNO';
        $where = '
cr.[year] = :year and cr.term = :term
and RTRIM(st.CLASSNO) like :classno
AND cr.studentno like :studentno
and cr.name like :projectname
and cr.reward_type = \'D\'';
        $csql = $this->makeCountSql('cwebs_sreward_recordlist cr',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('cwebs_sreward_recordlist cr',array(
            'fields'    => $fields,
            'join'  => $join,
            'where' => $where,
        ),$offset,$limit);
        $rst = $this->getTableList($csql,$ssql,array(
            ':year' => $year,
            ':term' => $term,
            ':classno'      => $classno,
            ':studentno'    => $studentno,
            ':projectname'  => $projname,
        ));
//        mist($rst,$csql,$ssql,array(
//            ':year' => $year,
//            ':term' => $term,
//            ':classno'      => $classno,
//            ':studentno'    => $studentno,
//            ':projectname'  => $projname,
//        ));
        return $rst;
    }

    /**
     * @param $id
     * @return int|string
     */
    public function deleteEncourageOfDevolopment($id){
        return $this->_deleteEncourageById($id);
    }

    private function _deleteEncourageById($id){
        return $this->deleteRecords(self::TBL_RECORDLIST,array(
            'id'    => $id,
        ));
    }

    /**
     * @param $id
     * @param null $year
     * @param null $term
     * @param null $studentno
     * @param null $projectname
     * @param null $credit
     * @param null $rem
     * @return int|string
     */
    public function updateEncourageOfDevolopment($id, $year=null, $term=null, $studentno=null, $projectname=null, $credit=null, $rem=null){
        $fields = array();
        isset($year) and $fields['year'] = $year;
        isset($term) and $fields['year'] = $term;
        isset($studentno)   and $fields['studentno'] = $studentno;
        isset($projectname) and $fields['name'] = $projectname;
        isset($rem)         and $fields['rem'] = $rem;
        isset($credit)      and $fields['credit'] = $credit;
        return $this->updateRecords(self::TBL_RECORDLIST,$fields,array('id'=>$id));
    }

    /**
     * 添加发展性项
     * @param $year
     * @param $term
     * @param $studentno
     * @param int $courseid 用于获取coursename和credit
     * @param string $rem
     * @return int|string
     */
    public function createEncourageOfDevolopment($year,$term,$studentno,$courseid,$rem=''){
        $sql = 'INSERT INTO [cwebs_sreward_recordlist]
( [year], [term], [studentno], [name], [credit], [rem], [reward_type],[devp_id])
SELECT
:year,:term,:studentno,cs.name,cs.credit,:rem,\'D\'-- 发展性
,:pid
from cwebs_sreward_develop_setting  cs
WHERE cs.id = :nid ';
        $bind = array(
            ':year' =>  $year,
            ':term' =>  $term,
            ':studentno' =>  $studentno,
            ':rem'  =>  $rem,
            ':pid'  =>  $courseid,
            ':nid'  =>  $courseid,
        );
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
        return $rst;
    }



//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ


    public function listEncourageOfCompetition($year,$term,$classno='%',$studentno='%',$projname='%',$offset=null,$limit=null){
        $fields = '
cr.[year],
cr.term,
CAST(cr.[year] as VARCHAR)+\'学年\'+CAST(cr.[term] as VARCHAR)+\'学期\' as yearterm,
cr.studentno,
RTRIM(st.NAME) as studentname,
RTRIM(cls.CLASSNAME) as classname,
cr.name as projectname,
cast(cr.credit as float) as credit,
cr.id,
cr.rem,
cl.name as levelname,
crk.name as rankname,
convert(varchar(10), cr.comp_datetime, 120) as datetime,
ct.name as comp_type_name,
cl.name+crk.name as levelrank';
        $join = '
INNER JOIN STUDENTS st on st.STUDENTNO = cr.studentno
INNER JOIN CLASSES cls ON cls.CLASSNO = st.CLASSNO
LEFT OUTER JOIN cwebs_sreward_competition_type ct ON ct.code = cr.comp_type
LEFT OUTER JOIN cwebs_sreward_competition_level cl ON cl.id = cr.comp_level_id
LEFT OUTER JOIN cwebs_sreward_competition_rank crk ON crk.id = cr.comp_rank_id ';
        $where = '
cr.[year] = :year and cr.term = :term
and RTRIM(st.CLASSNO) like :classno
AND cr.studentno like :studentno
and cr.name like :projectname
and cr.reward_type = \'C\'';
        $csql = $this->makeCountSql('cwebs_sreward_recordlist cr',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('cwebs_sreward_recordlist cr',array(
            'fields'=> $fields,
            'join'  => $join,
            'where' => $where,
        ),$offset,$limit);
        $list =  $this->getTableList($csql,$ssql,array(
            ':year' => $year,
            ':term' => $term,
            ':classno'      => $classno,
            ':studentno'    => $studentno,
            ':projectname'  => $projname,
        ));

//        mist($list,$csql,$ssql,array(
//            ':year' => $year,
//            ':term' => $term,
//            ':classno'      => $classno,
//            ':studentno'    => $studentno,
//            ':projectname'  => $projname,
//        ));

        return $list;
    }

    public function deleteEncourageOfCompetition($id){
        return $this->_deleteEncourageById($id);
    }
    /**
     *
     * 学分从$lid和$rid中获取
     * @param $year
     * @param $term
     * @param $studentno
     * @param $compname
     * @param $credit
     * @param null $comptype
     * @param null $lid
     * @param null $rid
     * @param null $datetime
     * @param string $rem
     * @return int|string
     */
    public function createEncourageOfCompetition($year,$term,$studentno,$compname,$credit=null,$comptype=null,$lid=null,$rid=null,$datetime=null,$rem=''){
        $bind = null;
        if(isset($comptype,$lid,$rid,$datetime)){//单个添加
            $bind = array(
                ':year' => $year,
                ':term' => $term,
                ':studentno' => $studentno,
                ':compname' => $compname,
                ':credit'   => $credit,
                ':rem'      => $rem,
                ':comptype' => $comptype,
                ':plid' => $lid,
                ':prid' => $rid,
                ':cdatetime' => $datetime,
            );
            if(isset($credit)){
                //学分手动设置
                $sql = 'INSERT INTO [cwebs_sreward_recordlist]
([year], [term], [studentno], [name], [credit], [rem], [reward_type],
 [comp_type], [comp_level_id], [comp_rank_id], [comp_datetime]) VALUES
 (:year, :term, :studentno, :compname, :credit, :rem, \'C\',  :comptype, :plid, :prid, :cdatetime);';
            }else{
                //学分从设置中自动获取
                $sql = '
INSERT INTO [cwebs_sreward_recordlist]
([year], [term], [studentno], [name], [credit], [rem], [reward_type],
 [comp_type], [comp_level_id], [comp_rank_id], [comp_datetime])
select
:year, :term, :studentno, :compname, cs.credit, :rem, \'C\',  :comptype, :plid, :prid, :cdatetime
from  cwebs_sreward_competition_setting cs
WHERE  lid = :nlid and rid = :nrid';
                $bind = array_merge($bind,array(
                    ':nlid' => $lid,
                    ':nrid' => $rid,
                ));
            }
        }else{//批量设置时
            $sql = '
INSERT INTO [cwebs_sreward_recordlist]
([year], [term], [studentno], [name], [credit],  [reward_type],rem)
VALUES (:year, :term, :studentno, :compname, :credit, \'C\',:rem);';
            $bind = array(
                ':year' => $year,
                ':term' => $term,
                ':studentno' => $studentno,
                ':compname' => $compname,
                ':credit'   => $credit,
                ':rem'      => $rem,
            );
        }
        $rst = $this->doneExecute($this->sqlExecute($sql,$bind));
        return $rst;
    }

    /**
     * @param $id
     * @param null $year
     * @param null $term
     * @param null $studentno
     * @param null $compname
     * @param null $comptype
     * @param null $lid
     * @param null $rid
     * @param null $credit 允许修改学分
     * @param null $datetime
     * @param null $rem
     * @return int|string
     */
    public function updateEncourageOfCompetition($id,$year=null,$term=null,$studentno=null,$compname=null,$comptype=null,
                                                 $lid=null,$rid=null,$credit=null,$datetime=null,$rem=null){
        $fields = array();
        isset($year) and $fields['year'] = $year;
        isset($term) and $fields['year'] = $term;
        isset($studentno)   and $fields['studentno'] = $studentno;
        isset($compname)    and $fields['name'] = $compname;
        isset($credit)      and $fields['credit'] = $credit;
        isset($comptype)    and $fields['comp_type'] = $comptype;
        isset($lid)         and $fields['comp_level_id'] = $lid;
        isset($rid)         and $fields['comp_rank_id'] = $rid;
        isset($datetime)    and $fields['comp_datetime'] = $datetime;
        isset($rem)         and $fields['rem'] = $rem;
        return $this->updateRecords(self::TBL_RECORDLIST,$fields,array('id'=>$id));
    }

//TODO:ѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺѺ

    public function listEncourageOfAddition($year,$term,$classno='%',$studentno='%',$projname='%',$dvlptype='%',$offset=null,$limit=null){
        $fields = '
cr.[year],
cr.term,
CAST(cr.[year] as VARCHAR)+\'学年\'+CAST(cr.[term] as VARCHAR)+\'学期\' as yearterm,
cr.studentno,
RTRIM(st.NAME) as studentname,
RTRIM(cls.CLASSNAME) as classname,
cr.name as projectname,
cast(cr.credit as float) as credit,
cr.id,
cr.rem';
        $join = '
INNER JOIN STUDENTS st on st.STUDENTNO = cr.studentno
INNER JOIN CLASSES cls ON cls.CLASSNO = st.CLASSNO';
        $where = '
cr.[year] = :year and cr.term = :term
and RTRIM(st.CLASSNO) like :classno
AND cr.studentno like :studentno
and cr.name like :projectname
and cr.reward_type = \'A\'
and cast(cr.devp_type as varchar(1)) like :dvlptype ';
        $csql = $this->makeCountSql('cwebs_sreward_recordlist cr',array(
            'join'  => $join,
            'where' => $where,
        ));
        $ssql = $this->makeSql('cwebs_sreward_recordlist cr',array(
            'fields'=> $fields,
            'join'  => $join,
            'where' => $where,
        ),$offset,$limit);
        return $this->getTableList($csql,$ssql,array(
            ':year' => $year,
            ':term' => $term,
            ':classno'      => $classno,
            ':studentno'    => $studentno,
            ':projectname'  => $projname,
            ':dvlptype'     => $dvlptype,
        ));
    }

    public function deleteEncourageOfAddition($id){
        return $this->_deleteEncourageById($id);
    }

    /**
     * @param $year
     * @param $term
     * @param $studentno
     * @param $projectname
     * @param $credit
     * @param int $dvlptype
     * @param string $rem
     * @return int|string
     */
    public function createEncourageOfAddition($year,$term,$studentno,$projectname,$credit,$dvlptype,$rem=''){
        return $this->createRecord(self::TBL_RECORDLIST,array(
            'year'  => $year,
            'term'  => $term,
            'studentno' => $studentno,
            'name'      => $projectname,
            'credit'    => $credit,
            'rem'       => $rem,
            'devp_type' => $dvlptype,
            'reward_type'   => 'A',
        ));
    }

    public function updateEncourageOfAddition($id,$year=null,$term=null,$studentno=null,$projectname=null,$credit=null,$rem=null){
        $fields = array();
        isset($year) and $fields['year'] = $year;
        isset($term) and $fields['term'] = $term;
        isset($studentno) and $fields['studentno'] = $studentno;
        isset($projectname) and $fields['name'] = $projectname;
        isset($credit) and $fields['credit'] = $credit;
        isset($rem) and $fields['rem'] = $rem;
        return $this->updateRecords(self::TBL_RECORDLIST,$fields,array('id'=>$id));
    }


}