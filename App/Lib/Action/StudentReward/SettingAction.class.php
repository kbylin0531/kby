<?php
/**
 * Email:784855684@qq.com
 * Created by Linzh
 * Date: 2015/12/16
 * Time: 9:18
 */


/**
 * Class Action 系统设置控制器
 */
class SettingAction extends RightAction {

    protected $model = null;

    public function __construct(){
        parent::__construct();
        $this->model = new StudentRewordModel();
    }

//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
//TODO:证书设置
//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓

    /**
     * 显示 技能证书设置页面
     */
    public function pageCertificate(){
        $this->display();
    }

    /**
     * 获取 技能证书列表数据
     * @param string $name 证书名称
     */
    public function listCertificate($name='%'){
        $this->ajaxReturn($this->model->listCertificate($name,$this->_pageDataIndex,$this->_pageSize));
    }

    /**
     * 添加 技能证书
     * @param string $name 证书名称
     * @param float $credit 证书学分
     * @param null|array $rows 批量添加时为array
     */
    public function createCertificate($name=null,$credit=null,array $rows=null){
        if(null === $rows){
            $rows  =array(array('name'=>$name,'credit'=>$credit));
        }
        foreach($rows as $row){
            $rst = $this->model->createCertificate($row['name'],$row['credit']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }
        }
        $this->successWithReport('添加成功！');
    }

    /**
     * 删除 技能证书（后台将status设置为0，证书的记录还是会存在）
     * @param int $id 证书ID
     * @param null $rows 批量删除时为array
     */
    public function deleteCertificate($id=null,$rows=null){
        if(isset($id)){$rows = array(array('id'=>$id,));}//删除单个的情况，统一整理成数组
        foreach($rows as $row){
            $id = $row['id'];
            $rst = $this->model->deleteCertificate($id);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("删除失败！{$rst}");
            }
        }
        $this->successWithReport('删除成功！');
    }

    /**
     * 修改 技能证书
     * @param null|array $rows 批量添加时为array
     * @param null|int $id 证书ID
     * @param null|string  $name 证书名称
     * @param null|int $credit 学分
     * @param null|int $status 证书状态 1为启用 0为不启用
     */
    public function updateCertificate(array $rows = null,$id=null,$name=null,$credit=null,$status=null){
        if(null === $rows){
            $rows = array(array(
                'id'        => $id,
                'name'      => $name,
                'credit'    => $credit,
                'status'    => $status,
            ));
        }
        foreach($rows as $row){
            $rst = $this->model->updateCertificate($row['id'],$row['name'],$row['credit'],$row['status']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }

    /**
     * 修改 技能证书(只允许批量改)
     * @param array $rows
     */
    public function updateCertificateStatus(array $rows){
        foreach($rows as $row){
            $rst = $this->model->updateCertificate($row['id'],null,null,$row['status'] > 0?0:1);//状态取反
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }



//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
//TODO:比赛等级
//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
    /**
     * 显示 比赛等级设置页面
     */
    public function pageCompetitionLevel(){
        $this->display();
    }

    /**
     * 获取 比赛等级列表数据
     * @param string $comptype 等级类型
     * @param string $compname 等级名称
     */
    public function listCompetitionLevel($comptype,$compname='%'){
        $this->ajaxReturn($this->model->listCompetitionLevel($comptype,$compname,$this->_pageDataIndex,$this->_pageSize));
    }

    /**
     * 添加 比赛等级
     * @param null|array $rows
     * @param string $name 等级名称
     * @param string $type 等级类型
     */
    public function createCompetitionLevel($rows=null,$name=null,$type=null){
        isset($rows) or $rows = array(array('name'=>$name));
        foreach($rows as $row){
            $rst = $this->model->createCompetitionLevel($row['name'],$type);
            if(is_string($rst) or !$rst) $this->failedWithReport("添加失败！{$rst}");
        }
        $this->successWithReport('添加成功！');
    }

    /**
     * 删除 比赛等级
     * @param int $id 比赛等级ID
     * @param null|array $rows
     */
    public function deleteCompetitionLevel($id=null,$rows=null){
        isset($rows) or $rows = array(array('id'=>$id));
        foreach($rows as $row){
            $rst = $this->model->deleteCompetitionLevel($row['id']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("删除失败！{$rst}");
            }
        }
        $this->successWithReport('删除成功！');
    }

    /**
     * 修改 比赛等级
     * @param null|array $rows
     * @param int $id 比赛等级ID
     * @param string $name 等级名称
     */
    public function updateCompetitionLevel($rows=null,$id=null,$name=null){
        isset($rows) or $rows = array(array('id'=>$id,'name'=>$name));
        foreach($rows as $row){
            $rst = $this->model->updateCompetitionLevel($row['id'],$row['name']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }

//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
//TODO:比赛名次
//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
    /**
     * 显示 比赛名次设置页面
     */
    public function pageCompetitionRank(){
        $this->display();
    }

    /**
     * 获取 比赛名次设置页面数据
     * @param string $comptype 名次类型
     * @param string $compname 名次名称
     */
    public function listCompetitionRank($comptype,$compname='%'){
        $this->ajaxReturn($this->model->listCompetitionRank($comptype,$compname,$this->_pageDataIndex,$this->_pageSize));
    }

    /**
     * 添加 比赛名次
     * @param null $rows
     * @param string $name 名次名称
     * @param null $type
     */
    public function createCompetitionRank($rows=null,$name=null,$type=null){
        isset($rows) or $rows = array(array('name'=>$name));
        foreach($rows as $row){
            $rst = $this->model->createCompetitionRank($row['name'],$type);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }
        }
        $this->successWithReport('添加成功！');
    }

    /**
     * 删除 比赛名次
     * @param int $id 名次ID
     * @param null $rows
     */
    public function deleteCompetitionRank($id=null,$rows=null){
        isset($rows) or $rows = array(array('id'=>$id));
        foreach($rows as $row){
            $rst = $this->model->deleteCompetitionRank($row['id']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("删除失败！{$rst}");
            }
        }
        $this->successWithReport('删除成功！');
    }

    /**
     * 修改 比赛名次
     * @param null $rows
     * @param null $id
     * @param null $name
     */
    public function updateCompetitionRank($rows=null,$id=null,$name=null){
        isset($rows) or $rows = array(array('id'=>$id,'name'=>$name));
        foreach($rows as $row){
            $rst = $this->model->updateCompetitionRank($row['id'],$row['name']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }



//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
//TODO:比赛奖励设置
//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
    /**
     * 显示 比赛奖励设置页面
     * @param string $type
     */
    public function pageCompetitionReward($type='%'){
        if(REQTAG === 'getlevels'){
            $list = $this->model->getLevelsByCompetitionType($type);
            $this->ajaxReturn($list);
        }elseif(REQTAG === 'getranks'){
            $list = $this->model->getRanksByCompetitionType($type);
            $this->ajaxReturn($list);
        }
        $this->display();
    }

    /**
     * 获取 已经设置的比赛奖励设置页面数据
     * @param $type
     * @param string $type
     * @param string $lid
     * @param string $rid
     */
    public function listCompetitionReward($type='%',$lid='%',$rid='%'){
        $this->ajaxReturn($this->model->listCompetitionSetting($type,$lid,$rid
//            ,$this->_pageDataIndex,$this->_pageSize
        ));
    }

    /**
     * 添加 比赛奖励设置
     * @param null $rows
     * @param null $lname
     * @param null $rname
     * @param null $credit
     * @param null $type
     */
    public function createCompetitionReward($rows=null,$lname=null,$rname=null,$credit=null,$type=null){
        isset($rows) or $rows = array(array('lid'=>$lname,'rid'=>$rname,'credit'=>$credit));
        foreach($rows as $row){
            $rst = $this->model->createCompetitionSetting($row['lname'],$row['rname'],$row['credit'],$type);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }
        }
        $this->successWithReport('添加成功！');
    }

    /**
     * 删除 比赛奖励设置
     * @param null $rows
     * @param null $lid
     * @param null $rid
     */
    public function deleteCompetitionReward($rows=null,$lid=null,$rid=null){
        isset($rows) or $rows = array(array('lid'=>$lid,'rid'=>$rid));
        foreach($rows as $row){
            $rst = $this->model->deleteCompetitionSetting($row['lid'],$row['rid']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("删除失败！{$rst}");
            }
        }
        $this->successWithReport('删除成功！');
    }

    /**
     * 修改 比赛奖励设置
     * @param null $rows
     * @param null $lid
     * @param null $rid
     * @param null $lname
     * @param null $rname
     * @param null $credit
     */
    public function updateCompetitionReward($rows=null,$lid=null,$rid=null,$lname=null,$rname=null,$credit=null){
        isset($rows) or $rows = array(array('lid'=>$lid,'rid'=>$rid,'lname'=>$lname,'rname'=>$rname,'credit'=>$credit));
        foreach($rows as $row){
            $rst = $this->model->updateCompetitionSetting($row['lid'],$row['rid'],$row['lname'],$row['rname'],$row['credit']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }

//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
//TODO:发展性课程设置
//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
    /**
     * 显示 发展性课程设置页面
     */
    public function pageDevolopmentCourse(){
        if(REQTAG === 'gettreelist'){
            $this->listDevolopmentCourseType();
        }
        $this->display();
    }

    /**
     * 获取 发展性课程设置页面数据
     * @param string $name 课程名称
     */
    public function listDevolopmentCourse($name='%'){
        $this->ajaxReturn($this->model->listDevolopmentCourse($name
            ,$this->_pageDataIndex,$this->_pageSize
        ));
    }

    /**
     * 添加 发展性课程设置
     * @param null $rows
     * @param null $name
     * @param null $subtype
     * @param null $credit
     * @param string $rem
     */
    public function createDevolopmentCourse($rows=null,$name=null,$subtype=null,$credit=null,$rem=''){
        isset($rows) or $rows= array(array('name'=>$name,'subtype'=>$subtype,'credit'=>$credit,'rem'=>$rem));
        foreach($rows as $row){
            $rst = $this->model->createDevolopmentCourse($row['name'],$row['subtype'],$row['credit'],$row['rem']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }
        }
        $this->successWithReport('添加成功！');
    }

    /**
     * 修改 发展性课程设置
     * @param null $rows
     * @param null $id
     * @param null $name
     * @param null $subtype
     * @param null $credit
     * @param null $rem
     */
    public function updateDevolopmentCourse($rows=null,$id=null,$name=null,$subtype=null,$credit=null,$rem=null){
        isset($rows) or $rows= array(array('id'=>$id,'name'=>$name,'subtype'=>$subtype,'credit'=>$credit,'rem'=>$rem));
        foreach($rows as $row){
            $rst = $this->model->updateDevolopmentCourse($row['id'],$row['name'],$row['subtype'],$row['credit'],$row['rem']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改失败！{$rst}");
            }
        }
        $this->successWithReport('修改成功！');
    }

    /**
     * 删除 发展性课程设置
     * @param null $rows
     * @param null $id
     */
    public function deleteDevolopmentCourse($rows=null,$id=null){
        isset($rows) or $rows= array(array('id'=>$id));
        foreach($rows as $row){
            $rst = $this->model->deleteDevolopmentCourse($row['id']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("删除失败！{$rst}");
            }
        }
        $this->successWithReport('删除成功！');
    }


//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
//TODO:类型与子类型设置
//TODO:▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
    /**
     * 显示 发展性课程类型与子类型设置页面
     */
    public function pageDevolopmentCourseType(){
        $this->display();
    }

    /**
     * 获取 发展性课程类型与子类型设置页面数据
     */
    public function listDevolopmentCourseType(){
        $this->ajaxReturn($this->model->listDevolopmentCourseType());
    }

    /**
     * 创建 发展性课程类型与子类型
     * @param $name
     * @param null $supertype 如果参数二位0，则参数三表示上级类型ID
     */
    public function createDevolopmentCourseType($name,$supertype=null){
        if(null === $supertype){
            $rst = $this->model->createDevolopmentCourseType($name);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }else{
                $this->successWithReport('添加成功！');
            }
        }else{
            $rst = $this->model->createDevolopmentCourseSubType($name,$supertype);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("添加失败！{$rst}");
            }else{
                $this->successWithReport('添加成功！');
            }
        }
    }

    /**
     * 删除 发展性课程类型与子类型
     * 当课程类型下还有子类型时需要清空所有的子类型
     * @param $id
     * @param $pid
     * @param $cid
     */
    public function deleteDevolopmentCourseType($id,$pid,$cid){
        if(empty($pid)){//修父类
            $rst = $this->model->deleteDevolopmentCourseType($id);
        }else{
            $rst = $this->model->deleteDevolopmentCourseSubType($cid);
        }
        if(is_string($rst) or !$rst){
            $this->failedWithReport("删除失败！{$rst}");
        }else{
            $this->successWithReport('删除成功！');
        }
    }

    /**
     * 修改  发展性课程类型与子类型
     * @param $id
     * @param $pid
     * @param $cid
     * @param $name
     */
    public function updateDevolopmentCourseType($id,$pid,$cid,$name){
        if(empty($pid)){//修父类
            $rst = $this->model->updateDevolopmentCourseType($id,$name);
        }else{
            $rst = $this->model->updateDevolopmentCourseSubType($cid,$name);
        }
        if(is_string($rst) or !$rst){
            $this->failedWithReport("修改失败！{$rst}");
        }else{
            $this->successWithReport('修改成功！');
        }
    }

}