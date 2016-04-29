<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-12-2
 * Time: 下午3:01
 **/
class CodeAction extends RightAction{
    private $md;        //存放模型对象
    private $base;      //路径
    /*
     * 专业代码管理首页
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
     * 专业代码管理
     *
     **/
    public function codeq()
    {
        if($this->_hasJson)
        {
/*        	
            $xy=M('majorcode');
            $arr['total']=$xy->where($this->cowhere())->count();
            if($arr['total']>0)
                $arr['rows']=$xy->where($this->cowhere())->limit($this->_pageDataIndex,$this->_pageSize)->select(); //$this->_pageSize   请求多少行(该语句返回查询到的数组)
            else
                $arr['rows']=array();
*/   
            
            $bind = array(":CODE"=>doWithBindStr($_POST['CODE']),
            		":NAME"=>doWithBindStr($_POST['NAME']),":IT_SORT"=>trim($_POST['IT_SORT']));
            $sql_count = " select Count(i.CODE) As ROWS from MAJORCODE i left join majorcode_it_sort m1 ON m1.VALUE = i.IT_SORT where i.CODE like :CODE and i.NAME like :NAME and i.IT_SORT like :IT_SORT ";
            $data = $this->md->sqlFind($sql_count,$bind);
            $arr['total'] = $data['ROWS'];
            if($arr['total']>0)
            {
            	$sql_select = " select i.CODE, i.NAME, m1.NAME As IT_SORT from MAJORCODE i left join majorcode_it_sort m1 ON m1.VALUE = i.IT_SORT where i.CODE like :CODE and i.NAME like :NAME and i.IT_SORT like :IT_SORT ORDER BY i.CODE ";
            	
            	$sql = $this->md->getPageSql($sql_select,null, $this->_pageDataIndex, $this->_pageSize);
            	$arr['rows'] = $this->md->sqlQuery($sql,$bind);
            	//trace($arr['rows']);
            }
            else
            	$arr['rows']=array();
                        
            
            
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        
        $shuju=M('majorcode_it_sort');                            // 学位信息
        $itsorts=$shuju->select();
        $this->assign('itsorts',$itsorts);
        $itsortjson=array();
        foreach($itsorts as $val)
        {
        	$sjson2['text']=trim($val['NAME']);
        	$sjson2['value']=$val['VALUE'];                    // 把学位数据转成json格式给前台的combobox使用
        	array_push($itsortjson,$sjson2);
        }
        $itsortjson=json_encode($itsortjson);
        $this->assign('itsortjson',$itsortjson);
        
        $this->display();

    }
    // 组合where条件
    public function cowhere()
    {
        // 判断where条件
        $data=array();
        if(trim($_POST['CODE'])!='')
        {
            $data['CODE']=array('like',array("{$_POST['CODE']}"));
        }
        if(trim($_POST['NAME'])!='')
        {
            $data['NAME']=array('like',array("{$_POST['NAME']}"));
        }
        if(trim($_POST['IT_SORT'])!='')
        {
            $data['IT_SORT']=array('like',array("{$_POST['IT_SORT']}"));
        }
        $pd=count($data)==0?"":$data;
        return $pd;
    }
    /*
     * 插入数据的方法
     */
    public function insertco()
    {
        $shuju=M('majorcode');
        
         $data = array();
         if ($_POST['CODE'])
         {
         	$data['CODE'] = $_POST['CODE'];
         }
         if ($_POST['NAME'])
         {
         	$data['NAME'] = $_POST['NAME'];
         }         
         if ($_POST['IT_SORT'])
         {
         	$data['IT_SORT'] = $_POST['IT_SORT'];
         }
        
        $sql=$shuju->add($data);
        //$arr=$_POST['data'];
        if($sql) echo true;
        else echo false;
    }

    /*
     * 修改数据的方法
     */
    public function updateco()
    {
        //$shuju=M('majorcode');
     /*   [CODE] => 510202
    [NAME] => 园林技术1
    [ROW_NUMBER] => 2*/
        //$data=array();
        //$data['CODE']=$_POST['CODE'];
        
    	
    	$sstr='';
    	if(isset($_POST['toIT_SORT'])) $sstr="IT_SORT='".$_POST['toIT_SORT']."',";
    	
        $pd=$this->md->sqlExecute("update majorcode set {$sstr}code=:code,name=:name where code=:ctwo",
        array(':code'=>$_POST['CODE'],':name'=>$_POST['NAME'],':ctwo'=>$_POST['CODE']));


        //$pd=$shuju->where($data)->save($_POST);
        if($pd)
            echo true;
        else
            echo false;
    }

    public function deleteco()
    {
        //$shuju=M('majorcode');
        //$data=array();
        $str='';
        foreach($_POST['in'] as $val){
            $str.=$val.',';
        }
        $str=rtrim($str,',');
        /*
        $data['CODE']=array('in',$_POST['in']);
        echo '<pre>';
        print_r($_POST);*/


        $arr=$this->md->sqlExecute('delete from majorcode where code in(:code)',
        array(':code'=>$str));

     //   $arr=$shuju->where($data)->delete();
        if($arr)
            echo true;
        else
            echo false;
    }

}
?>