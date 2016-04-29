<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-11-27
 * Time: 上午11:09
 */
class BaseAction extends RightAction
{
    private $mdl;
    public function __construct(){
        parent::__construct();
        $this->mdl=new SqlsrvModel();
    }


    //组合where条件
    private function scwhere(){
        //判断where条件
        $data=array();
        if(trim($_POST['SCHOOL'])!=''){
            $one=trim($_POST['SCHOOL']);
            $data['SCHOOL']=array('like',array("$one"));
        }
        if(trim($_POST['NAME'])!=''){
            $two=trim($_POST['NAME']);
            $data['NAME']=array('like',array("$two"));
        }
        $pd=count($data)==0?"":$data;
        return $pd;
    }
    
	/**
	 * 添加学院
	 */
	public function inserted() {
		foreach ( $_POST as $key => $value ) {
			$_POST [$key] = trim ( $value );
		}
		
		$_POST ['PATH'] = "|" . $_POST ['SCHOOL'] . "|";
		
		$parentid = trim ( $_POST ['PARENT'] );
		if ($parentid) {
			// 判断Parent是否存在
			if (! $this->parentExist ( $parentid )) {
				exit ( '添加失败，父级学部编号不存在！' );
			}
			
			$parent = $this->mdl->sqlFind ( "SELECT * FROM SCHOOLS WHERE upper(ltrim(rtrim(SCHOOL))) = :SCHOOL ", array (
					":SCHOOL" => strtoupper ( $parentid ) 
			) );
			if ($parent) {
				$_POST ['PATH'] = $parent ['PATH'] . $_POST ['SCHOOL'] . "|";
			}
		}
		
		$sql = 'INSERT INTO SCHOOLS (SCHOOL,NAME,PARENT,PATH,ORDERBY) VALUES (:schoolno,:schoolname,:parent,:path,:orderby);';
		$bind = array (
				':schoolno' => $_POST ['SCHOOL'],
				':schoolname' => $_POST ['NAME'],
				':parent' => $parentid,
				':path' => $_POST ['PATH'],
				':orderby' => $_POST ['ORDERBY']
		);
			
		$res = $this->mdl->sqlExecute ( $sql, $bind );
		if ($res) {
			echo 'true'; // 测试用下
		} else {
			echo '插入失败，可能是添加的编号冲突,或者学部ID过长！';
		}
		
	}
    
    /**
     * 判断父级学部编号是否存在,（输入为‘’或者'-'则认为是顶层学院）
     * @param string $parentid 学部编号
     * @return boolean true  父级学部编号存在 
     */
    private function parentExist($parentid){
    	$sql = 'SELECT NAME from  SCHOOLS where SCHOOL = :schoolno;';
    	$res = $this->mdl->sqlExecute($sql,array(':schoolno'=>$parentid));
    	return (!$res && trim($parentid) != '' && trim($parentid) != '-')?false:true; 
    }

    /*
     * 修改数据的方法
     */
    public function updated(){
    	$parentid = trim($_POST['PARENT']);
    	//判断Parent是否存在
    	if(!$this->parentExist($parentid)){
    		var_dump($_POST);
    		exit('parent not exit') ;
    	}
    	
    	$temppath = "";
    	$tempschool = $this->mdl->sqlFind("SELECT * FROM SCHOOLS WHERE upper(ltrim(rtrim(SCHOOL))) = :SCHOOL ",array(":SCHOOL" => strtoupper(trim($_POST['SCHOOL']))));
    	if ($tempschool)
    	{
    		$temppath = $tempschool['PATH'];
    	}
    
       $_POST['PATH'] = "|" . $_POST['SCHOOL'] . "|";
   	   $parent = $this->mdl->sqlFind("SELECT * FROM SCHOOLS WHERE upper(ltrim(rtrim(SCHOOL))) = :SCHOOL ",array(":SCHOOL" => strtoupper($parentid)));
       if ($parent)
       {
       		$_POST['PATH'] = $parent['PATH'] . $_POST['SCHOOL'] . "|";
       }       
    	
        $sql = 'UPDATE SCHOOLS SET PATH=:path,ORDERBY=:orderby, NAME = :schoolname , PARENT = :parent where SCHOOL = :schoolno;';
        $bind = array(':path'=>$_POST['PATH'],':orderby'=>$_POST['ORDERBY'], ':schoolname'=>$_POST['NAME'],':parent'=>($parentid == '-')?'':$parentid,':schoolno'=>$_POST['SCHOOL']);
        $res = $this->mdl->sqlExecute($sql,$bind);
        if($res !== false && $res > 0){        	
        	if ($temppath)
        	{
        		$sql = "UPDATE SCHOOLS SET PATH='".$_POST['PATH']."'+substring(PATH,len('".$temppath."')+1,len(PATH)) where PATH like '".$temppath."%'";
        		$this->mdl->sqlExecute($sql,$bind);
        	}
        	
            echo 'true';
        }else{
            echo 'false';
        }
    }

    /*
     * 删除学院数据的方法
     */
    public function deleted(){
    	$json = array();
    	$result = false;
    	$msgStr = "";
    	if (isset($_POST['in']))    		
    	{
    		foreach($_POST['in'] as $schoolno)
    		{
    			if ($schoolno)
    			{
    				$sql_select = " select sum(icount) as icount from ( "

							    . " select count(*) as icount from SCHOOLS where upper(ltrim(rtrim(PARENT))) = '".strtoupper(trim($schoolno))."' "
								. " union "
								. " select count(*) as icount from TEACHERS where (upper(ltrim(rtrim(SCHOOL))) = '".strtoupper(trim($schoolno))."' or upper(ltrim(rtrim(TGROUP))) = '".strtoupper(trim($schoolno))."') "

								. " ) x ";
    				
    				$icount = $this->mdl->sqlFind($sql_select);;

    				if ($icount["icount"] > 0)
    				{
    					$msgStr .= "\r\n编号为“".$schoolno."”的数据已被使用不能删除！";
    					
    				}
    				else
    				{
    					$bind[':schoolno'] = strtoupper(trim($schoolno));
    					$sql_delete = " delete from SCHOOLS where upper(ltrim(rtrim(SCHOOL))) = :schoolno ";
    					$result = $this->mdl->sqlExecute($sql_delete,$bind);
    				}
    				
    			}    			
    		}    		
    	}

//    	if($result)
//   		echo true;
//   	else
//    		echo false;
    	 
    	$json["result"] = $result;
    	$json["msgStr"] = $msgStr;
    	 
    	$this->ajaxReturn($json,"JSON");
    }

    public function updateschoolstate()
    {
    	if (isset($_GET["selfId"]) && isset($_POST["state"]))
    	{
    		$bool = $this->mdl->sqlExecute(" update schools set STATE = :STATE where upper(ltrim(rtrim(SCHOOL))) = :SCHOOL ",array(":STATE"=>trim($_POST["state"]),":SCHOOL"=>strtoupper(trim($_GET["selfId"]))));
    		$this->ajaxReturn($bool,"JSON");
    	}
    }
    
    public function getjsonschools()
    {
    	$jsonschools = array();
    	 
    	if (isset($_GET["selfId"]))
    	{
  		   	$schools = $this->mdl->sqlQuery(" select * from schools where SCHOOL is null or (SCHOOL is not null and upper(ltrim(rtrim(SCHOOL)))<> :SCHOOL) ", array(':SCHOOL' => strtoupper(trim($_GET["selfId"]))));
    	}
    	else
    	{
    		$schools = M('schools')->select();
    	}    
    	
		
    	foreach($schools as $school)
    	{
    		$jsonschool["id"] = trim($school["SCHOOL"]);
    		$jsonschool["text"] = trim($school["NAME"]);
    		$parentId = "0";
    		if (trim($school["PARENT"]))
    		{
    			$parentId = trim($school["PARENT"]);
    		}
    		$jsonschool["parentId"] = $parentId;
    
    		array_push($jsonschools, $jsonschool);
    	}
    
    	$jsonschools = create_tree($jsonschools,"0");
    
    	$this->ajaxReturn($jsonschools,"JSON");
    }
    
    public function getschoolcount()
    {
    	$schoolcount = $this->mdl->sqlFind(" select count(*) as icount from schools ");
    	$schoolcount = $schoolcount['icount'];
    
    	$this->ajaxReturn($schoolcount,"JSON");
    }
    
    /**
     *  学院模块
     */
    public function school(){
        if($this->_hasJson){
				/*
			 * $sql = 'SELECT count(*) as ROWS from ( (SELECT * from SCHOOLS WHERE SCHOOL LIKE :school and NAME LIKE :name ) UNION
			 * (SELECT * from SCHOOLS WHERE PARENT LIKE :parent)) as b;';
			 * $bind = array(':school'=>$_POST['SCHOOL'],':name'=>$_POST['NAME'],':parent'=>$_POST['SCHOOL']);
			 * $res = $this->mdl->sqlQuery($sql, $bind);
			 * $arr['total']= ($res !==false&&count($res)>0)?$res[0]['ROWS']:0;
			 * if($arr['total']>0){
			 * $arr['rows']=$this->mdl->sqlQuery(
			 * 'select * from(select row_number() over(order by SCHOOL) as rowId,RTRIM(SCHOOL) AS SCHOOL,RTRIM(NAME) AS NAME,
			 * RTRIM(PARENT) as PARENT,PATH,ORDERBY FROM schools
			 * where name like :name and (school like :school or parent like :parent ))
			 * as b where b.rowId between :start and :end',
			 * array(':name'=>$_POST['NAME'],':school'=>$_POST['SCHOOL'],':parent'=>$_POST['SCHOOL'],
			 * ':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize));
			 * }else{
			 * $arr['rows'] = array();
			 * };
			 *
			 * $arr['rows'] = create_schooltree($arr['rows'],null,"0");
			 *
			 */        	
			$rows = $this->mdl->sqlQuery ('select row_number() over(order by SCHOOL) as rowId,RTRIM(SCHOOL) AS SCHOOL,RTRIM(NAME) AS NAME,RTRIM(PARENT) as PARENT,PATH,ORDERBY,STATE as state FROM schools where name like :name and (school like :school or parent like :parent)'
			, array (
					':name' => $_POST ['NAME'],
					':school' => $_POST ['SCHOOL'],
					':parent' => $_POST ['SCHOOL'] 
			) );
            $arr = create_schooltree($rows,null,"0");
            
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        $this->display();
    }
    
    /**
     *  教研组模块
     */
    public function tgroup(){
    	if($this->_hasJson)
    	{ 
			  $sql = 'SELECT count(*) as ROWS from ( SELECT * from TGROUPS WHERE TGROUP LIKE :tgroup and NAME LIKE :name ) as b;';
			  $bind = array(':tgroup'=>$_POST['TGROUP'],':name'=>$_POST['NAME']);
			  $res = $this->mdl->sqlQuery($sql, $bind);
			  $arr['total']= ($res !==false&&count($res)>0)?$res[0]['ROWS']:0;
			  if($arr['total']>0){
			  $arr['rows']=$this->mdl->sqlQuery(
			  'select * from(select row_number() over(order by TGROUP) as rowId,RTRIM(TGROUP) AS TGROUP,RTRIM(NAME) AS NAME,ORDERBY FROM TGROUPS
			  where name like :name and TGROUP like :tgroup)
			  as b where b.rowId between :start and :end',
			  array(':name'=>$_POST['NAME'],':tgroup'=>$_POST['TGROUP'],
			  ':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize));
			  }else{
			  $arr['rows'] = array();
			  };
   	
    		
    		
    		$this->ajaxReturn($arr,"JSON");
    		exit;
    		
    	}
    	$this->display();
    }
    /*
     * 插入教研组的方法
     */
    public function inserttg()
    {
    	
    	foreach ( $_POST as $key => $value ) {
    		$_POST [$key] = trim ( $value );
    	}
    	
    	$sql = 'INSERT INTO TGROUPS (TGROUP,NAME,ORDERBY) VALUES (:tgroupno,:tgroupname,:orderby);';
    	$bind = array (
    			':tgroupno' => $_POST ['TGROUP'],
    			':tgroupname' => $_POST ['NAME'],
    			':orderby' => $_POST ['ORDERBY']
    	);
    		
    	$res = $this->mdl->sqlExecute ( $sql, $bind );

    	if($res) echo true;
    	else echo false;
    }
    /*
     * 修改教研组的方法
     */
    public function updatetg()
    {
    	$sql = 'UPDATE TGROUPS SET ORDERBY=:orderby, NAME = :tgroupname where TGROUP = :tgroupno;';
    	$bind = array(':orderby'=>$_POST['ORDERBY'], ':tgroupname'=>$_POST['NAME'],':tgroupno'=>$_POST['TGROUP']);
    	$res = $this->mdl->sqlExecute($sql,$bind);

    	if($res !== false && $res > 0)
    		echo true;
    	else
    		echo false;
    }
    /*
     * 修改教研组的方法
     */
    public function deletetg()
    {
    	$json = array();
    	$result = false;
    	$msgStr = "";
    	 
    	$blres = false;
    	if (isset($_POST['in']))
    	{
    		foreach($_POST['in'] as $tgroupno)
    		{
    			if ($tgroupno)
    			{
    				$sql_select = " select sum(icount) as icount from ( "
    	
    						    . " select count(*) as icount from COURSES where upper(ltrim(rtrim(TGROUP))) = '".strtoupper(trim($tgroupno))."' "
    							. " union "
    							. " select count(*) as icount from TEACHERS where upper(ltrim(rtrim(TGROUP))) = '".strtoupper(trim($tgroupno))."' "
    	
    							. " ) x ";
    	
    				$icount = $this->mdl->sqlFind($sql_select);;
    	
    				if ($icount["icount"] > 0)
    				{
    					$msgStr .= "\r\n编号为“".$tgroupno."”的数据已被使用不能删除！";
    						
    				}
    				else
    				{
    					$bind[':tgroupno'] = strtoupper(trim($tgroupno));
    					$sql_delete = " delete from TGROUPS where upper(ltrim(rtrim(TGROUP))) = :tgroupno ";
    					$result = $this->mdl->sqlExecute($sql_delete,$bind);
    				}
    	
    			}
    		}
    	}
    	 
    	
    	
 //   	if($blres)
 //  		echo true;
 //  	else
 //  		echo false;
 	
    	$json["result"] = $result;
    	$json["msgStr"] = $msgStr;
 
    	$this->ajaxReturn($json,"JSON");
 
    }
    
    
    /**
     *  职称管理
     *
     **/
    public function positions()
    {
        if($this->_hasJson)
        {

        	$sql = 'SELECT count(*) as ROWS from ( SELECT * from POSITIONS WHERE NAME LIKE :name and VALUE LIKE :value ) as b;';
        	$bind = array(':name'=>$_POST['NAME'],':value'=>$_POST['VALUE']);
        	$res = $this->mdl->sqlQuery($sql, $bind);
        	$arr['total']= ($res !==false&&count($res)>0)?$res[0]['ROWS']:0;
        	if($arr['total']>0){
        		$arr['rows']=$this->mdl->sqlQuery(
        				'select * from( select row_number() over(order by NAME) as rowId,RTRIM(NAME) AS NAME,RTRIM(VALUE) AS VALUE,RTRIM(JB) AS JB,RTRIM(ZhuJiangZhiGe) AS ZhuJiangZhiGe FROM POSITIONS
			  where NAME like :name and VALUE like :value)
			  as b where b.rowId between :start and :end',
        				array(':name'=>$_POST['NAME'],':value'=>$_POST['VALUE'],
        				  ':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize));
        	}else{
        		$arr['rows'] = array();
        	};
        	
        	
        	
        	$this->ajaxReturn($arr,"JSON");
        	exit;

        }
          $this->display();
    }

    // 组合where条件
    public function powhere()
    {
        // 判断where条件
        $data=array();
        $_POST['VALUE']=trim($_POST['VALUE']);
        $_POST['NAME']=trim($_POST['NAME']);
        if(trim($_POST['VALUE'])!='')
        {
            $data['VALUE']=array('like',array("{$_POST['VALUE']}"));
        }
        if(trim($_POST['NAME'])!='')
        {
            $data['NAME']=array('like',array("{$_POST['NAME']}"));
        }
        $pd=count($data)==0?"":$data;
        return $pd;
    }
    /*
     * 插入数据的方法
     */
    public function insertpo()
    {
    	foreach ( $_POST as $key => $value ) {
    		$_POST [$key] = trim ( $value );
    	}
    	 
    	$sql = 'INSERT INTO POSITIONS (NAME,VALUE,JB,ZhuJiangZhiGe) VALUES (:name,:value,:jb,:ZhuJiangZhiGe);';
    	$bind = array (
    			':name' => $_POST ['NAME'],
    			':value' => $_POST ['VALUE'],
    			':jb' => $_POST ['JB'],
    			':ZhuJiangZhiGe' => $_POST ['ZhuJiangZhiGe']
    	);
    	
    	$res = $this->mdl->sqlExecute ( $sql, $bind );
    	
    	if($res) echo true;
    	else echo false;
    	 
    }

    /*
    * 修改数据的方法
    */
    public function updatepo()
    {
        $shuju=M('positions');
        $data=array();
        $data['NAME']=$_POST['NAME'];
        $pd=$shuju->where($data)->save($_POST);
        if($pd)
            echo true;
        else
            echo false;
    }

    public function deletepo()
    {
        $shuju=M('positions');
        $data=array();
        $data['NAME']=array('in',$_POST['in']);

        // $str=implode(',',$_POST['in']);
        // echo $str;

        $arr=$shuju->where($data)->delete();
        if($arr)
            echo true;
        else
            echo false;
    }

    /**
     * 角色管理
     *
     **/
    public function roles()
    {
        if($this->_hasJson)
        {
            $xy=M('roles');
            $arr['total']=$xy->where($this->rowhere())->count();
            if($arr['total']>0)
                $arr['rows']=$xy->where($this->rowhere())->limit($this->_pageDataIndex,$this->_pageSize)->select(); //$this->_pageSize   请求多少行(该语句返回查询到的数组)
            else
                $arr['rows']=array();
            $this->ajaxReturn($arr,"JSON");
            exit;
        }
        $this->display();

    }
    // 组合where条件
    public function rowhere()
    {
        // 判断where条件
        $data=array();
        $_POST['ROLE']=trim($_POST['ROLE']);
        $_POST['DESCRIPTION']=trim($_POST['DESCRIPTION']);

        if(trim($_POST['ROLE'])!='')
        {
            $data['ROLE']=array('like',array("{$_POST['ROLE']}"));
        }
        if(trim($_POST['DESCRIPTION'])!='')
        {
            $data['DESCRIPTION']=array('like',array("{$_POST['DESCRIPTION']}"));
        }
        $pd=count($data)==0?"":$data;
        return $pd;
    }

    /* public function roles2()
     {

         $xy=M('roles');
         $str=$this->rowhere();                                                                                         // where条件组合

         $start=($this->_pageNo-1)*$this->_pageSize;                                                                    // limit起始位置
         $row=$this->_pageSize;                                                                                         // 请求多少行

         $roles=$xy->where($str)->limit($start,$row)->select();                                                         // 学院信息数组
         $total=$xy->where($str)->count();                                                                              // 总条数
         if(!$roles)
         {
             $roles=array('ROLE'=>"","DESCRIPTION"=>"");                                                                //  如果没查到数据 给个空数组 防止前台报错
             $total=0;
         }

         $arr=array();
         $arr['total']=$total;
         $arr['rows']=$roles;
         echo json_encode($arr);
     }*/

    /*
     * 插入数据的方法
     */
    public function insertro()
    {
        $shuju=M('roles');
        $sql=$shuju->add($_POST);
        //$arr=$_POST['data'];
        if($sql) echo true;
        else echo false;
    }

    /*
     * 修改数据的方法
     */
    public function updatero()
    {
        $shuju=M('roles');
        $data=array();
        //print($_POST);
        $data['ROLE']=$_POST['ROLE'];
        $pd=$shuju->where($data)->save($_POST);
        if($pd)
            echo true;
        else
            echo false;
    }

    public function deletero()
    {
        $shuju=M('roles');
        $data=array();
        $data['ROLE']=array('in',$_POST['in']);

        $arr=$shuju->where($data)->delete();
        if($arr)
            echo true;
        else
            echo false;
    }
	
	public function yearterm(){
        if($this->_hasJson){
            $count=$this->mdl->sqlFind("select count(*) as ROWS from (select row_number() over(order by type) as row,rem,year,term,type from year_term where rem like :rem) as b",
                array(':rem'=>$_POST['rem']));

            if($data['total']=$count['ROWS']){
                $data['rows']=$this->mdl->sqlQuery("select * from (select row_number() over(order by type) as row,rem,year,
                term,type,weeks,lock from year_term where rem like :rem) as b where b.row between :start and :end",array_merge(array(':rem'=>$_POST['rem']),array(':start'=>$this->_pageDataIndex+1,':end'=>$this->_pageDataIndex+$this->_pageSize)));
            }else{
                $data['total']=0;
                $data['rows']=array();
            }

            $this->ajaxReturn($data,'JSON');
            exit;
        }
        $this->display();
    }

    //todo:修改学年学期
    public function updateyearterm(){
        $this->mdl->starttrans;
        foreach($_POST['bind'] as $val){
            if(trim($val['term'])>2){
                exit('学期不能大于2');
            }
            $int=$this->mdl->sqlExecute("update year_term set year=:year,term=:term,weeks=:weeks,lock=:lock where type=:type",
            array(':year'=>$val['year'],':term'=>$val['term'],':weeks'=>$val['weeks'],':lock'=>$val['lock'],':type'=>$val['type']));
            if(!$int){
                exit($val['rem'].'修改的时候出错了！');
            }
        }
        $this->mdl->commit();
        exit('修改成功');

    }
}