<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-12-2
 * Time: 下午3:01
 **/
class RewardsnameAction extends RightAction{
    private $md;        //存放模型对象
    private $base;      //路径
    /*
     * 奖励名称管理首页
     * [CODE] => 510202
    [NAME] => 园林技术1
    [ROW_NUMBER] => 2
     */
    public function __construct(){
        parent::__construct();
        $this->md=new SqlsrvModel();
       // $this->base='CourseManager/';
    }

    public function index(){
        $this->display();
    }

    /**
     * 奖励名称管理
     *
     **/
    public function rewardsnameq()
    {
        if($this->_hasJson)
        {
        	$bind = array(":Qsort"=>doWithBindStr($_POST['Qsort']), ":Qgrade"=>doWithBindStr($_POST['Qgrade']));
        	
        	$sql_count = " select Count(*) As ROWS from rewards_name i where i.SORT like :Qsort and i.GRADE like :Qgrade ";
            $data = $this->md->sqlFind($sql_count,$bind);
            $arr['total'] = $data['ROWS'];
            if($arr['total']>0)
            {
            	$sql_select = " select i.ID, i.NAME, i.SORT, i.GRADE, i.SCORE from rewards_name i where i.SORT like :Qsort and i.GRADE like :Qgrade ORDER BY i.ID ";
            	
            	$sql = $this->md->getPageSql($sql_select,null, $this->_pageDataIndex, $this->_pageSize);
            	$arr['rows'] = $this->md->sqlQuery($sql,$bind);
            	//trace($arr['rows']);
            }
            else
            	$arr['rows']=array();
                        
            
            
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        
        $this->display();

    }
    
    /*
     * 插入数据的方法
     */
    public function insertrewardsname()
    {
    	$shuju=M('rewards_name');
    
    	$data = array();
    	if ($_POST['ID'])
    	{
    		$data['ID'] = $_POST['ID'];
    	}
    	if ($_POST['NAME'])
    	{
    		$data['NAME'] = $_POST['NAME'];
    	}
    	if ($_POST['SORT'])
    	{
    		$data['SORT'] = $_POST['SORT'];
    	}
    	if ($_POST['GRADE'])
    	{
    		$data['GRADE'] = $_POST['GRADE'];
    	}
    	if ($_POST['SCORE'])
    	{
    		$data['SCORE'] = $_POST['SCORE'];
    	}
    	 
    	$sql=$shuju->add($data);

    	if($sql) echo true;
    	else echo false;
 
    }
    
    /*
     * 修改数据的方法
     */
    public function updaterewardsname()
    {
    	if ($_POST['ID'] && $_POST['NAME'])
    	{
    		$shuju=M('rewards_name');
    		
    		$data=array();
    		$data['ID']=$_POST['ID'];
    		
    		$pd=$shuju->where($data)->save($_POST);
    		 
    		if($pd)
    			echo true;
    		else
    			echo false;
    		}
    	else
    	{
    		echo false;
    	}
    }

    /*
     * 删除数据的方法
     */
    public function deleterewardsname()
    {
    	$json = array();
    	$result = false;
    	$msgStr = "";
    	if (isset($_POST['in']))
    	{
    		foreach($_POST['in'] as $rewardsnameid)
    		{
    			if ($rewardsnameid)
    			{
    				$sql_select = " select sum(icount) as icount from ( "
    	
    						    . " select count(*) as icount from rewards where REWARDS_NAME_ID = '".$rewardsnameid."' "
    	
    							. " ) x ";
    	
    				$icount = $this->md->sqlFind($sql_select);;
    	
    				if ($icount["icount"] > 0)
    				{
    					$msgStr .= "\r\n编号为“".$rewardsnameid."”的数据已被使用不能删除！";
    						
    				}
    				else
    				{
    					$sql_delete = " delete from rewards_name where ID = '".$rewardsnameid."' ";
    					$result = $this->md->sqlExecute($sql_delete);
    					if ($result)
    					{
    						$msgStr .= "\r\n编号为“".$rewardsnameid."”的数据删除成功！";
    					}
    				}
    	
    			}
    		}
    	}

    	$json["result"] = $result;
    	$json["msgStr"] = $msgStr;
    	 
    	$this->ajaxReturn($json,"JSON");
    	 
    	
/*    	
    	$str='';
    	foreach($_POST['in'] as $val){
    		$str.=$val.',';
    	}
    	$str=rtrim($str,',');
    
    	$arr=$this->md->sqlExecute("delete from rewards_name where ID in (".$str.")");
    
    	if($arr)
    		echo true;
    	else
    		echo false;
    	
*/    	
    }
    
}
?>