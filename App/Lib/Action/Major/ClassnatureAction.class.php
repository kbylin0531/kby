<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-12-2
 * Time: 下午3:01
 **/
class ClassnatureAction extends RightAction{
    private $md;        //存放模型对象
    private $base;      //路径
    /*
     * 班级性质管理首页
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
     * 班级性质管理
     *
     **/
    public function classnatureq()
    {
        if($this->_hasJson)
        {
            $sql_count = " select Count(*) As ROWS from class_nature i ";
            $data = $this->md->sqlFind($sql_count);
            $arr['total'] = $data['ROWS'];
            if($arr['total']>0)
            {
            	$sql_select = " select i.ID, i.NAME from class_nature i ORDER BY i.ID ";
            	
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
    public function insertclassnature()
    {
    	$shuju=M('class_nature');
    
    	$data = array();
    	if ($_POST['ID'])
    	{
    		$data['ID'] = $_POST['ID'];
    	}
    	if ($_POST['NAME'])
    	{
    		$data['NAME'] = $_POST['NAME'];
    	}
    
    	$sql=$shuju->add($data);

    	if($sql) echo true;
    	else echo false;
    }
    
    /*
     * 修改数据的方法
     */
    public function updateclassnature()
    {
    	if ($_POST['ID'] && $_POST['NAME'])
    	{
    		$shuju=M('class_nature');
    		
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
    public function deleteclassnature()
    {
    	$str='';
    	foreach($_POST['in'] as $val){
    		$str.=$val.',';
    	}
    	$str=rtrim($str,',');
    
    	$arr=$this->md->sqlExecute("delete from class_nature where ID in (".$str.")");
    
    	if($arr)
    		echo true;
    	else
    		echo false;
    }
    
}
?>