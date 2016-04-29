<?php
/**
 * 教师奖励首页
 * User: cwebs
 * Date: 13-11-23
 * Time: 上午8:47
 */
class IndexAction extends RightAction {

    /**
     * @var TeacherRewardModel
     */
    private $model = null;

    protected $message = array("type"=>"info","message"=>"","dbError"=>"");
    public function __construct(){
        parent::__construct();
        $this->model = new TeacherRewardModel();
        $this->assign('yearterm',$this->model->getYearTerm());
    }

    /**
     * 教师奖励办法首页
     */
    public function index()
    {
        $this->display();
    }

    /////////////////////////////////////////////////////////////////////
    /**
     * 等级积分表维护
     */
    public function level(){
        if($this->_hasJson){
            $json = array("total"=>0, "rows"=>array());

            $data = $this->model->sqlQuery("select * from REWARD_RANK order by SORT");
            if($data && ($count=count($data))>0){
                $json["total"] = $count;
                $json["rows"] = $data;
            }

            $this->ajaxReturn($json,"JSON");
            exit;
        }
        $this->display();
    }

    /**
     * 等级积分表保存
     */
    public function level_save(){
        if(!isset($_REQUEST["DATAS"]) || !is_array($_REQUEST["DATAS"])){
            $this->message["type"] = "warning";
            $this->message["message"] = "没有提交任一信息进行修改！";
            $this->ajaxReturn($this->message, "JSON");
            exit;
        }

        $count = 0;
        foreach($_REQUEST["DATAS"] as $data){
            if($data["RANKID"]){
                $sql = "update REWARD_RANK set SORT=:SORT,G1=:G1,S2=:S2,C3=:C3,Q4=:Q4,X5=:X5 where RANKID=:RANKID";
                $bind = $this->model->getBind("SORT,G1,S2,C3,Q4,X5,RANKID", $data);
            }else{
                $sql = "insert into REWARD_RANK (NAME,ISTEAM,SORT,G1,S2,C3,Q4,X5) VALUES (:NAME,:ISTEAM,:SORT,:G1,:S2,:C3,:Q4,:X5)";
                $bind = $this->model->getBind("NAME,ISTEAM,SORT,G1,S2,C3,Q4,X5", $data);
            }
            $succ = $this->model->sqlExecute($sql, $bind);
            if($succ!==false) $count++;
        }

        $this->message["message"] = $count."条记录保存成功！";
        $this->ajaxReturn($this->message, "JSON");
        exit;
    }

    /////////////////////////////////////////////////////////////////////
    /**
     * 奖励项目设置
     */
    public function items(){
        if($this->_hasJson){
            $data = $this->model->sqlQuery("select * from REWARD_ITEMS where PATH LIKE :PATH order by SORT,ITEMID",array(":PATH"=>"|".intval($_REQUEST['PID'])."|%"));
            $menu = M('MenuActionsModel:');
            $data = $menu->getTreeGrid($data);

            $this->ajaxReturn($data,"JSON");
            exit;
        }
        $data = $this->model->sqlQuery("select * from REWARD_ITEMS where PID is NULL order by SORT,ITEMID");
        $this->assign("rootItems", $data);
        $this->display();
    }

    public function itemsSave(){
        if(!isset($_REQUEST['DATA']) || !is_array($_REQUEST["DATA"])){
            $this->message["type"] = "warning";
            $this->message["message"] = "没有提交任一信息进行修改！";
            $this->ajaxReturn($this->message, "JSON");
            exit;
        }

        $data = $_REQUEST['DATA'];
        $this->message["OLDID"] = $data['ITEMID'];
        $this->model->startTrans();
        //修改
        if(isset($data['ITEMID']) && intval($data['ITEMID'])>0){
            $sql = "update REWARD_ITEMS set NAME=:NAME,WEIGHTS=:WEIGHTS,SORT=:SORT where ITEMID=:ITEMID";
            $bind = $this->model->getBind("NAME,WEIGHTS,SORT,ITEMID", $data);
            $succ = $this->model->sqlExecute($sql, $bind);
        }else{ //新增
            $sql = "insert into REWARD_ITEMS (NAME,WEIGHTS,PID,SORT) values (:NAME,:WEIGHTS,:PID,:SORT) ";
            $bind = $this->model->getBind("NAME,WEIGHTS,PID,SORT", $data);
            $succ = $this->model->sqlExecute($sql, $bind);
            if($succ!==false){
                $data["ITEMID"] = $this->model->getLastInsID();
                $sql = "select * from REWARD_ITEMS where ITEMID=:ITEMID";
                $pdata = $this->model->sqlFind($sql, array(":ITEMID"=>$data['PID']));
                $data["PATH"] = ($pdata['PATH'] ? $pdata['PATH'] : "|").$data["ITEMID"]."|";
                $this->model->sqlExecute("update REWARD_ITEMS set PATH=:PATH where ITEMID=:ITEMID",array(":PATH"=>$data["PATH"],":ITEMID"=>$data["ITEMID"]));
            }
        }

        if($succ===false){
            $this->model->rollback();
            $this->message["type"] = "error";
            $this->message["message"] = "保存数据时发生错误！";
        }else{
            $this->model->commit();
            $this->message["message"] = "分类权重项已成功修改！";
            $this->message["row"] = $data;
        }
        $this->ajaxReturn($this->message, "JSON");
        exit;
    }

    /////////////////////////////////////////////////////////////////////
    //教师奖励主界面
    public function reward(){
        if($this->_hasJson){
            $json = array("total"=>0, "rows"=>array());

            $sqlCount = "select count(*) from REWARD R where R.YEAR=:YEAR and R.TERM=:TERM and R.TEACHERNO like :TEACHERNO";
            $sql = "select R.*,T.NAME as TEACHER_NAME from REWARD R "
                    ."left join TEACHERS T ON (R.TEACHERNO=T.TEACHERNO) where R.YEAR=:YEAR and R.TERM=:TERM and R.TEACHERNO like :TEACHERNO order by TEACHERNO,RID";
            $bind = $this->model->getBind("YEAR,TERM,TEACHERNO",$_REQUEST);
            $count = $this->model->sqlCount($sqlCount, $bind);
            if($count){
                $sql = $this->model->getPageSql($sql, null, $this->_pageDataIndex, $this->_pageSize);
                $data = $this->model->sqlQuery($sql,$bind);
                if($data){
                    $json["total"] = $count;
                    $json["rows"] = $data;
                }
            }
            $this->ajaxReturn($json,"JSON");
            exit;
        }

        $this->assign("levelList",$this->model->sqlQuery("select * from REWARD_LEVEL order by SORT"));
        $this->assign("rankList",$this->model->sqlQuery("select * from REWARD_RANK order by RANKID"));
        $this->assignYearTerm("S");
        $this->display();
    }

    public function createReward($YEAR,$TERM,$TEACHERNO,$RTYPE,$RANKID,$ITEMID,$comment=''){
        $rst = $this->model->createReward($this->makeModifiedFields($YEAR,$TERM,$TEACHERNO,$RTYPE,$RANKID,$ITEMID,$comment));
        if(is_string($rst) or !$rst){
            $this->failedWithReport("添加失败！{$rst}");
        }else{
            $this->successWithReport("添加成功1");
        }
    }
    public function updateReward($RID,$YEAR,$TERM,$TEACHERNO,$RTYPE,$RANKID,$ITEMID,$comment=''){
        $updfiedls = $this->makeModifiedFields($YEAR,$TERM,$TEACHERNO,$RTYPE,$RANKID,$ITEMID,$comment);
        $whr = array(
            'RID'   => $RID,
        );
        $rst = $this->model->updateReward($updfiedls,$whr);
        if(is_string($rst) or !$rst){
            $this->failedWithReport("修改失败！{$rst}");
        }else{
            $this->successWithReport("修改成功！");
        }
    }
    public function deleteReward($rows){
        if(empty($rows)){
            $this->failedWithReport('查询记录为空！');
        }
        $this->model->startTrans();
        foreach($rows as $row){
            $rst = $this->model->deleteReward($row['RID']);
            if(is_string($rst) or !$rst){
                $this->failedWithReport("修改失败！");
            }
        }
        $this->model->commit();
        $this->successWithReport('删除成功!');
    }

    /**
     * 创建修改或者添加字段映射
     * @param $YEAR
     * @param $TERM
     * @param $TEACHERNO
     * @param string $RTYPE 登记，国家级。。。。
     * @param int $RANKID 登记项ID
     * @param int $ITEMID 权重ID
     * @param string $comment 注释
     * @return array
     */
    protected function makeModifiedFields($YEAR,$TERM,$TEACHERNO,$RTYPE,$RANKID,$ITEMID,$comment=''){
        $level = $this->model->getLevelSortNum($RTYPE);
        if(is_string($level)){
            $this->failedWithReport($level);
        }
        $sort = $level['SORT'];

        $fiedlname = $RTYPE.$sort;
        $rankitem = $this->model->getRankInfoByField($fiedlname,$RANKID);
        if(is_string($rankitem)){
            $this->failedWithReport($rankitem);
        }

        $item = $this->model->getItemInfo($ITEMID);
        if(is_string($rankitem)){
            $this->failedWithReport($rankitem);
        }

        return array(
            'YEAR'  => $YEAR,
            'TERM'  => $TERM,
            'TEACHERNO' => $TEACHERNO,
            'REWARD_VAL'=> intval($rankitem['weight']) * intval($item['weight']),
            'RTYPE'     => $fiedlname,
            'RANKID'    => $item['ITEMID'],
            'LEVEL_NAME'=> $level['NAME'].','.$rankitem['NAME'],
            'ITEMID'    => $item['ITEMID'],
            'ITEM_NAME' => $this->model->formateItemPath($item['PATH']),
            'COMMENT'   => $comment,
            'ISTEAM'    => $rankitem['isteam'],
            'LEVEL_VAL' => $rankitem['weight'],
            'ITEM_VAL'  => $item['weight'],
        );
    }

    public function getItems(){
        $data = $this->model->sqlQuery("select ITEMID as ID,NAME,PID,PATH from REWARD_ITEMS order by SORT,ITEMID");
        $menu = M('MenuActionsModel:');
        $data = $menu->getComboTree($data);
        $this->ajaxReturn($data,"JSON");
        exit;
    }

    /**
     * 教师奖励统计
     * @param null $year
     * @param null $term
     * @param string $rankname
     * @param string $levelname
     * @param string $teachername
     */
    public function statistics($year=null,$term=null,$rankname='%',$levelname='%',$teachername='%'){
        //获取列表数据
        if(REQTAG === 'getlist'){
            $list = $this->model->listStatics($year,$term,$levelname.$rankname,$teachername,$this->_pageDataIndex,$this->_pageSize);
            if(is_string($list)){
                $this->errorBack('查询出错！'.$list);
            }
            $this->ajaxReturn($list);
        }elseif(REQTAG === 'export'){
            $list = $this->model->listStatics($year,$term,$levelname.$rankname,$teachername);
            $data = array();
            //设置对齐信息和数据域
            $data['title'] = '教师奖励统计';
            $data['head'] = array(
                'teachername' => array( '教师姓名', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'reward_val' => array( '奖励分', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'level_name' => array( '获奖等级', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'isteam' => array( '是否团队', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
                'comment' => array( '备注', 'align' => CommonModel::ALI_CENTER,'width'=>20,'type'=>CommonModel::TYPE_STR ),
            );
            $data['body'] = $list['rows'];
            $excelModel = new ExcelExtensionModel();
            $excelModel->export($data, $data['title']);
        }


        $this->assign('REWARD_LEVEL',$this->model->getRewardLevelList());
        $this->assign('REWARD_RANK',$this->model->getRewardRankList());

        $this->display();
    }

}