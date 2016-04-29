<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 13-12-2
 * Time: 下午3:01
 **/
class RewardAction extends RightAction{
	private $md;        //存放模型对象
	
	/**
	 *  班级管理
	 *
	 **/
	public function __construct(){
		//      $this->model = M("SqlsrvModel:");
		parent::__construct();
		$this->md=new SqlsrvModel();
		$this->assign('school',getSchoolList());
		$this->assign('quanxian',trim($_SESSION['S_USER_INFO']['ROLES']));
	}

	public function getjsonschools()
	{
		$jsonschools = array();
	
		$jsonschool["id"] = "0";
		$jsonschool["text"] = "全部";
		$jsonschool["parentId"] = "-1";
		array_push($jsonschools, $jsonschool);
	
		$schools = M('schools')->select();
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
	
		$jsonschools = create_tree($jsonschools,"-1");
	
		$this->ajaxReturn($jsonschools,"JSON");
	}

	public function get_teacher_list()
	{
		$prmTEACHERNO = "%";
		$prmNAME = "%";
		if ($_POST["q"])
		{
			$prmTEACHERNO = trim($_POST["q"])."%";
			$prmNAME = "%".trim($_POST["q"])."%";
		}
	
		$bind = array(":TEACHERNO"=>$prmTEACHERNO,":NAME"=>$prmNAME);
		$teachers = $this->md->sqlQuery("select top 30 rtrim(TEACHERNO) as id, rtrim(TEACHERNO) + ' / ' + rtrim(NAME) as text from TEACHERS where TEACHERNO like :TEACHERNO or NAME like :NAME order by TEACHERNO", $bind);
		$this->ajaxReturn($teachers,"JSON");
	}

	public function get_rewardssort_list()
	{
		$prmNAME = "%";
		if ($_POST["q"])
		{
			$prmNAME = "%".trim($_POST["q"])."%";
		}
	
		$bind = array(":SORT"=>$prmNAME);
		$teachers = $this->md->sqlQuery("select top 30 x.* from ( select distinct SORT as text from rewards_name where SORT like :SORT ) x order by x.text", $bind);
		$this->ajaxReturn($teachers,"JSON");
	}

	public function get_rewardsgrade_list()
	{
		$prmSORT = "%";
		if ($_POST["sort"])
		{
			$prmSORT = trim($_POST["sort"]);
		}
		$prmNAME = "%";
		if ($_POST["q"])
		{
			$prmNAME = "%".trim($_POST["q"])."%";
		}		
	
		$bind = array(":sort"=>$prmSORT,":grade"=>$prmNAME);
		
		$teachers = $this->md->sqlQuery("select top 30 x.* from ( select distinct GRADE as text from rewards_name where SORT = :sort and GRADE like :grade ) x order by x.text", $bind);
		$this->ajaxReturn($teachers,"JSON");
	}

	public function get_rewardsname_list()
	{
		$prmSORT = "%";
		if ($_POST["sort"])
		{
			$prmSORT = trim($_POST["sort"]);
		}
		$prmGRADE = "%";
		if ($_POST["grade"])
		{
			$prmGRADE = trim($_POST["grade"]);
		}
		$prmNAME = "%";
		if ($_POST["q"])
		{
			$prmNAME = "%".trim($_POST["q"])."%";
		}
	
		$bind = array(":sort"=>$prmSORT,":grade"=>$prmGRADE,":name"=>$prmNAME);
	
		$teachers = $this->md->sqlQuery("select top 30 ID as id, NAME as text, SCORE as score from rewards_name where SORT = :sort and GRADE = :grade and NAME like :name order by SORT,GRADE,NAME", $bind);
		$this->ajaxReturn($teachers,"JSON");
	}
	
	//申请列表
	public function qlist()
	{
		if($this->_hasJson) {
			$model = M("SqlsrvModel:");
			$bind = array(
					":Quser"=>trim($_SESSION['S_USER_INFO']['USERNAME']),
					":Qyear"=>trim($_POST['Qyear']),
					":Qterm"=>trim($_POST['Qterm']),
					":Qschool"=>trim($_POST['Qschool']),					
					":Qteacherno"=>doWithBindStr($_POST['Qteacherno']),
					":Qteachername"=>doWithBindStr($_POST['Qteachername']),
					":Qrewardssort"=>doWithBindStr($_POST['Qrewardssort']),
					":Qrewardsgrade"=>doWithBindStr($_POST['Qrewardsgrade']),
					":Qstatus"=>trim($_POST['Qstatus'])
					);
			$sql = $model->getSqlMap("Reward/rewardsSelectCount.sql");
			$data = $model->sqlFind($sql,$bind);
			$arr['total'] = $data['ROWS'];
			if($arr['total']>0)
			{
				$sql = $model->getPageSql(null,"Reward/rewardsSelect.sql", $this->_pageDataIndex, $this->_pageSize);
				$arr['rows'] = $model->sqlQuery($sql,$bind);
			}
			else
				$arr['rows']=array();
			$this->ajaxReturn($arr,"JSON");
			exit;
		}
		
		
		$yearTerm = $this->md->sqlFind("select * from YEAR_TERM where [TYPE]='S'");
		$this->assign("yearTerm",$yearTerm);
			
		$this->display();
	}
	

	/*
	 * 插入数据的方法
	 */
	public function insert()
	{
		if ($_POST["YEAR"] && $_POST["TERM"] && $_POST["TEACHERNO"] && $_POST["REWARDS_NAME_ID"])
		{
			$data = array();
			$data["YEAR"] = trim($_POST["YEAR"]);
			$data["TERM"] = trim($_POST["TERM"]);
			$data["TEACHERNO"] = trim($_POST["TEACHERNO"]);
			$data["REWARDS_NAME_ID"] = trim($_POST["REWARDS_NAME_ID"]);
			$data["REWARDS_REMARK"] = trim($_POST["REWARDS_REMARK"]);
			$data["STATUS"] = trim($_POST["STATUS"]);			
			
			$teacher_school = $this->md->sqlFind("SELECT T.SCHOOL FROM TEACHERS T WHERE T.TEACHERNO='".trim($_POST["TEACHERNO"])."'");			
			$data["SCHOOL"] = $teacher_school["SCHOOL"];
			
			$data["ADD_USER"] = $_SESSION["S_USER_INFO"]["USERNAME"];
			$data["ADD_TIME"] = date("Y-m-d H:i:s");
			
			$shuju=M('rewards');
			$sql=$shuju->add($data);
			
			
			if($sql) 
			{
				$msg = '添加成功';
				if ($_POST["STATUS"] == 1)
				{
					$msg = '添加并提交审核成功';
				}
				echo $msg;
			}
			else 
			{
				$msg = '添加失败';
				if ($_POST["STATUS"] == 1)
				{
					$msg = '添加并提交审核失败';
				}
				echo $msg;
			}
		}
		else
		{
			echo '提交的数据不符要求';
		}		
		
	}
	/*
	 * 修改数据的方法
	 */
	public function update()
	{
		if ($_POST["ID"] && $_POST["YEAR"] && $_POST["TERM"] && $_POST["TEACHERNO"] && $_POST["REWARDS_NAME_ID"])
		{
			$data = array();
			$data["ID"] = trim($_POST["ID"]);
			$data["YEAR"] = trim($_POST["YEAR"]);
			$data["TERM"] = trim($_POST["TERM"]);
			$data["TEACHERNO"] = trim($_POST["TEACHERNO"]);
			$data["REWARDS_NAME_ID"] = trim($_POST["REWARDS_NAME_ID"]);
			$data["REWARDS_REMARK"] = trim($_POST["REWARDS_REMARK"]);
			$data["STATUS"] = trim($_POST["STATUS"]);			

			$teacher_school = $this->md->sqlFind("SELECT T.SCHOOL FROM TEACHERS T WHERE T.TEACHERNO='".trim($_POST["TEACHERNO"])."'");
			$data["SCHOOL"] = $teacher_school["SCHOOL"];
				
			$data["ADD_USER"] = $_SESSION["S_USER_INFO"]["USERNAME"];
			$data["ADD_TIME"] = date("Y-m-d H:i:s");
			
			$shuju=M('rewards');			
			$sql=$shuju->save($data);
				
			if($sql)
			{
				$msg = '修改成功';
				if ($_POST["STATUS"] == 1)
				{
					$msg = '修改并提交审核成功';
				}
				echo $msg;
			}
			else 
			{
				$msg = '修改失败';
				if ($_POST["STATUS"] == 1)
				{
					$msg = '修改并提交审核失败';
				}
				echo $msg;
			}
		}
		else
		{
			echo '提交的数据不符要求';
		}
	}
	
	//批量删除
	public function delete()
	{
		$shuju=M('rewards');
		$data=array();
		$data['ID']=array('in',$_POST['in']);
	
		$arr=$shuju->where($data)->delete();
		if($arr)
			echo 'true';
		else
			echo 'false';
	}

	//批量提交
	public function upsubmit()
	{
		$shuju=M('rewards');
		$data=array();
		$data['ID']=array('in',$_POST['in']);
		
		$flds=array();
		$flds["ADD_USER"] = $_SESSION["S_USER_INFO"]["USERNAME"];
		$flds["ADD_TIME"] = date("Y-m-d H:i:s");
		$flds["STATUS"] = 1;
	
		$arr=$shuju->where($data)->save($flds);
		if($arr)
			echo 'true';
		else
			echo 'false';
	}	

	//审核列表
	public function vqlist()
	{
		if($this->_hasJson) {
			$model = M("SqlsrvModel:");
			$bind = array(
					":Qyear"=>trim($_POST['Qyear']),
					":Qterm"=>trim($_POST['Qterm']),
					":Qschool"=>trim($_POST['Qschool']),					
					":Qteacherno"=>doWithBindStr($_POST['Qteacherno']),
					":Qteachername"=>doWithBindStr($_POST['Qteachername']),
					":Qrewardssort"=>doWithBindStr($_POST['Qrewardssort']),
					":Qrewardsgrade"=>doWithBindStr($_POST['Qrewardsgrade']),
					":Qstatus"=>trim($_POST['Qstatus'])
					);
			$sql = $model->getSqlMap("Reward/rewardsVSelectCount.sql");
			$data = $model->sqlFind($sql,$bind);
			$arr['total'] = $data['ROWS'];
			if($arr['total']>0)
			{
				$sql = $model->getPageSql(null,"Reward/rewardsVSelect.sql", $this->_pageDataIndex, $this->_pageSize);
				$arr['rows'] = $model->sqlQuery($sql,$bind);
			}
			else
				$arr['rows']=array();
			$this->ajaxReturn($arr,"JSON");
			exit;
		}
		
		
		$yearTerm = $this->md->sqlFind("select * from YEAR_TERM where [TYPE]='S'");
		$this->assign("yearTerm",$yearTerm);
			
		$this->display();
	}
		
	/*
	 * 审核数据的方法
	 */
	public function verify()
	{
		if ($_POST["ID"])
		{
			$rewards = $this->md->sqlFind("SELECT T.REWARDS_VERIFY_ID FROM rewards T WHERE T.STATUS in (1,2,3) and T.ID='".trim($_POST["ID"])."'");
			if ($rewards)
			{
				$insertverify = true;				
				$verifyId = $rewards['REWARDS_VERIFY_ID'];
				$verify = $this->md->sqlFind("SELECT T.VERIFY_STATUS FROM rewards_verify T WHERE T.ID='".$verifyId."'");
				if ($verify)
				{
					$vstatus = $verify['VERIFY_STATUS'];
					if ($vstatus == 0)
					{
						$insertverify = false;
					}
				}
				
				$vstatus = trim($_POST["STATUS"]);
				if ($_POST["STATUS"] == 1)
				{
					$vstatus = 0;
				}
				
				if ($insertverify)
				{
					$data = array();
					$data["REWARDS_ID"] = trim($_POST["ID"]);					
					$data["VERIFY_STATUS"] = $vstatus;
					$data["VERIFY_REMARK"] = trim($_POST["VERIFY_REMARK"]);						
					$data["ADD_USER"] = $_SESSION["S_USER_INFO"]["USERNAME"];
					$data["ADD_TIME"] = date("Y-m-d H:i:s");
						
					$shuju = M('rewards_verify');
					$res = $shuju->add($data);
					
					$verifyId = $res;
					
				}
				else
				{
					$data = array();
					$data["ID"] = $verifyId;
					$data["VERIFY_STATUS"] = $vstatus;
					$data["VERIFY_REMARK"] = trim($_POST["VERIFY_REMARK"]);
					$data["ADD_USER"] = $_SESSION["S_USER_INFO"]["USERNAME"];
					$data["ADD_TIME"] = date("Y-m-d H:i:s");
												
					$shuju=M('rewards_verify');
					$res=$shuju->save($data);
						
				}
				
				if ($res)
				{
					$data = array();
					$data["ID"] = trim($_POST["ID"]);		
					$data["STATUS"] = trim($_POST["STATUS"]);
					$data["REWARDS_VERIFY_ID"] = $verifyId;
						
					$shuju=M('rewards');
					$res=$shuju->save($data);
					
					$msg = '审核成功';
					if ($_POST["STATUS"] == 1)
					{
						$msg = '审核保存成功';
					}
					else if ($_POST["STATUS"] == 2)
					{
						$msg = '审核通过成功';
					}
					else if ($_POST["STATUS"] == 3)
					{
						$msg = '审核不通过成功';
					}
					else if ($_POST["STATUS"] == -1)
					{
						$msg = '退回成功';
					}
					echo $msg;
					
				}
				else
				{
					$msg = '审核失败';
					if ($_POST["STATUS"] == 1)
					{
						$msg = '审核保存失败';
					}
					else if ($_POST["STATUS"] == 2)
					{
						$msg = '审核通过失败';
					}
					else if ($_POST["STATUS"] == 3)
					{
						$msg = '审核不通过失败';
					}
					else if ($_POST["STATUS"] == -1)
					{
						$msg = '退回失败';
					}
					echo $msg;
				}
				
				
			}
			else
			{
				echo '提交的数据不符要求';
			}
			
		}
		else
		{
			echo '提交的数据不符要求';
		}
	}
	
	/*
	 * 批量审核数据的方法
	 */
	public function verifys()
	{
		if(count($_POST['in']))
		{
			$resmsg = "";
			foreach($_POST['in'] as $id)
			{
				$_POST["ID"] = $id;
				if ($_POST["ID"])
				{
					$rewards = $this->md->sqlFind("SELECT rtrim(m2.NAME) as TEACHERNAME,m3.NAME as REWARDSNAME,i.REWARDS_VERIFY_ID FROM rewards i left outer join TEACHERS m2 ON m2.TEACHERNO=i.TEACHERNO left outer join rewards_name m3 ON m3.ID=i.REWARDS_NAME_ID WHERE i.STATUS in (1,2,3) and i.ID='".trim($_POST["ID"])."'");
					if ($rewards)
					{
						$insertverify = true;
						$verifyId = $rewards['REWARDS_VERIFY_ID'];
						$verify = $this->md->sqlFind("SELECT T.VERIFY_STATUS FROM rewards_verify T WHERE T.ID='".$verifyId."'");
						if ($verify)
						{
							$vstatus = $verify['VERIFY_STATUS'];
							if ($vstatus == 0)
							{
								$insertverify = false;
							}
						}
				
						$vstatus = trim($_POST["STATUS"]);
						if ($_POST["STATUS"] == 1)
						{
							$vstatus = 0;
						}
				
						if ($insertverify)
						{
							$data = array();
							$data["REWARDS_ID"] = trim($_POST["ID"]);
							$data["VERIFY_STATUS"] = $vstatus;
							$data["VERIFY_REMARK"] = trim($_POST["VERIFY_REMARK"]);
							$data["ADD_USER"] = $_SESSION["S_USER_INFO"]["USERNAME"];
							$data["ADD_TIME"] = date("Y-m-d H:i:s");
				
							$shuju = M('rewards_verify');
							$res = $shuju->add($data);
								
							$verifyId = $res;
								
						}
						else
						{
							$data = array();
							$data["ID"] = $verifyId;
							$data["VERIFY_STATUS"] = $vstatus;
							$data["VERIFY_REMARK"] = trim($_POST["VERIFY_REMARK"]);
							$data["ADD_USER"] = $_SESSION["S_USER_INFO"]["USERNAME"];
							$data["ADD_TIME"] = date("Y-m-d H:i:s");
				
							$shuju=M('rewards_verify');
							$res=$shuju->save($data);
				
						}
				
						if ($res)
						{
							$data = array();
							$data["ID"] = trim($_POST["ID"]);
							$data["STATUS"] = trim($_POST["STATUS"]);
							$data["REWARDS_VERIFY_ID"] = $verifyId;
				
							$shuju=M('rewards');
							$res=$shuju->save($data);
								
							$msg = '审核成功';
							if ($_POST["STATUS"] == 1)
							{
								$msg = '审核保存成功';
							}
							else if ($_POST["STATUS"] == 2)
							{
								$msg = '审核通过成功';
							}
							else if ($_POST["STATUS"] == 3)
							{
								$msg = '审核不通过成功';
							}
							else if ($_POST["STATUS"] == -1)
							{
								$msg = '退回成功';
							}
							
							$resmsg = $resmsg . '<br>“' . $rewards['TEACHERNAME'] . '”“' . $rewards['REWARDSNAME'] . '”' . $msg;
								
						}
						else
						{
							$msg = '审核失败';
							if ($_POST["STATUS"] == 1)
							{
								$msg = '审核保存失败';
							}
							else if ($_POST["STATUS"] == 2)
							{
								$msg = '审核通过失败';
							}
							else if ($_POST["STATUS"] == 3)
							{
								$msg = '审核不通过失败';
							}
							else if ($_POST["STATUS"] == -1)
							{
								$msg = '退回失败';
							}
							
							$resmsg = $resmsg . '<br>“' . $rewards['TEACHERNAME'] . '”“' . $rewards['REWARDSNAME'] . '”' . $msg;
						}
				
				
					}
					else
					{
						$msg = '提交的数据不符要求';
						
						$resmsg = $resmsg . '<br>“' . $rewards['TEACHERNAME'] . '”“' . $rewards['REWARDSNAME'] . '”' . $msg;
					}
						
				}
			}
			
			echo $resmsg;
		}
		else
		{
			echo '提交的数据不符要求';
		}

	}
	
	//查看清单
	public function viewlist()
	{
		if ($_POST['Qyear'])
		{
		}
		else if ($_GET['Qyear'])
		{
			$_POST['Qyear'] = $_GET['Qyear'];
		}		
		if ($_POST['Qterm'])
		{
		}
		else if ($_GET['Qterm'])
		{
			$_POST['Qterm'] = $_GET['Qterm'];
		}		
		if ($_POST['Qteacherno'])
		{
		}
		else if ($_GET['Qteacherno'])
		{
			$_POST['Qteacherno'] = $_GET['Qteacherno'];
		}		
		
		if ($_POST['Qteacherno'])
		{
			if (!($_POST['Qyear'])) $_POST['Qyear'] = '%';
			if (!($_POST['Qterm'])) $_POST['Qterm'] = '%';

			$teacher = $this->md->sqlFind(" select i.TEACHERNO, i.NAME as TEACHERNAME from TEACHERS i where ltrim(rtrim(i.TEACHERNO)) = '".trim($_POST['Qteacherno'])."'");
			
			$bind = array(
					":Qyear"=>trim($_POST['Qyear']),
					":Qterm"=>trim($_POST['Qterm']),
					":Qteacherno"=>trim($_POST['Qteacherno'])
			);
			
			$rewards = $this->md->sqlQuery(" select "
										  ." i.ID, "
  										  ." i.YEAR, "
										  ." i.TERM, "
										  ." i.SCHOOL, "
										  ." rtrim(m1.NAME) as SCHOOLNAME, "
										  ." rtrim(i.TEACHERNO) as TEACHERNO, "
										  ." rtrim(m2.NAME) as TEACHERNAME, "
										  ." i.REWARDS_NAME_ID, "
										  ." m3.NAME as REWARDSNAME, "
										  ." m3.SORT as REWARDSSORT, "
									 	  ." m3.GRADE as REWARDSGRADE, "
										  ." m3.SCORE as REWARDSSCORE, "
										  ." i.REWARDS_REMARK, "
										  ." i.STATUS, "
										  ." i.ADD_USER, "
										  ." CONVERT(varchar,i.ADD_TIME,120) as ADD_TIME, "
										  ." m4.VERIFY_REMARK, "
										  ." m4.ADD_USER as VERIFY_USER, "
										  ." CONVERT(varchar,m4.ADD_TIME,120) as VERIFY_TIME "
					
										  ." from rewards i "
										  ." left outer join SCHOOLS m1 ON m1.SCHOOL=i.SCHOOL "
										  ." left outer join TEACHERS m2 ON m2.TEACHERNO=i.TEACHERNO "
										  ." left outer join rewards_name m3 ON m3.ID=i.REWARDS_NAME_ID "
										  ." left outer join rewards_verify m4 ON m4.ID=i.REWARDS_VERIFY_ID and m4.VERIFY_STATUS in (-1,2,3) "
										  ." where i.STATUS in (2,3) and i.YEAR like :Qyear and i.TERM like :Qterm and ltrim(rtrim(i.TEACHERNO)) = :Qteacherno ", $bind);

			
			if($this->_hasJson) {
				
				$arr['teacher'] = $teacher;
					
				$arr['total'] = count($rewards);
				$arr['rows'] = $rewards;
				
				$this->ajaxReturn($arr,"JSON");
				
				exit;
				
			}
			else
			{
				$this->assign("teacher",$teacher);
					
				$this->assign("rewards",$rewards);
				
			}
				
		}
		else
		{
			$this->assign("ERROR",'提交的数据不符要求');
		}
			
		$this->display();
	}
	
	//查看
	public function view()
	{
		if ($_POST['id'])
		{			
		}
		else if ($_GET['id'])
		{
			$_POST['id'] = $_GET['id'];
		}		
		
		if ($_POST['id'])
		{
			$rewards = $this->md->sqlFind(" select "
										 ." i.ID, "
										 ." i.YEAR, "
										 ." i.TERM, "
										 ." i.SCHOOL, "
										 ." rtrim(m1.NAME) as SCHOOLNAME, "
										 ." rtrim(i.TEACHERNO) as TEACHERNO, "
										 ." rtrim(m2.NAME) as TEACHERNAME, "
										 ." i.REWARDS_NAME_ID, "
										 ." m3.NAME as REWARDSNAME, "
										 ." m3.SORT as REWARDSSORT, "
										 ." m3.GRADE as REWARDSGRADE, "
										 ." m3.SCORE as REWARDSSCORE, "
										 ." i.REWARDS_REMARK, "
										 ." i.STATUS, "
										 ." i.ADD_USER, "
										 ." CONVERT(varchar,i.ADD_TIME,120) as ADD_TIME, "
										 ." m4.VERIFY_REMARK, "
										 ." m4.ADD_USER as VERIFY_USER, "
										 ." CONVERT(varchar,m4.ADD_TIME,120) as VERIFY_TIME "
					
										 ." from rewards i "
										 ." left outer join SCHOOLS m1 ON m1.SCHOOL=i.SCHOOL "
										 ." left outer join TEACHERS m2 ON m2.TEACHERNO=i.TEACHERNO "
										 ." left outer join rewards_name m3 ON m3.ID=i.REWARDS_NAME_ID "
										 ." left outer join rewards_verify m4 ON m4.ID=i.REWARDS_VERIFY_ID and m4.VERIFY_STATUS in (-1,2,3) "
										 ." where i.ID = '".$_POST['id']."'");
			
			$verifys = $this->md->sqlQuery(" select "
										 ." i.VERIFY_STATUS, "
										 ." i.VERIFY_REMARK, "
										 ." i.ADD_USER as VERIFY_USER, "
										 ." CONVERT(varchar,i.ADD_TIME,120) as VERIFY_TIME "
			
										 ." from rewards_verify i "
										 ." where i.VERIFY_STATUS in (-1,2,3) and i.REWARDS_ID = '".$_POST['id']."'");
			
			
			if($this->_hasJson) {
				
				$arr['reward'] = $rewards;
					
				$arr['total'] = count($verifys);
				$arr['rows'] = $verifys;
				
				$this->ajaxReturn($arr,"JSON");
				
				exit;
			}
			else 
			{
				$this->assign("rewards",$rewards);
					
				$this->assign("verifys",$verifys);				
				
			}
	
			
		}
		else
		{
			$this->assign("ERROR",'提交的数据不符要求');
		}
			
		$this->display();
	}
	
	//统计列表
	public function statistics()
	{
		if($this->_hasJson) {
			$model = M("SqlsrvModel:");
			$bind = array(
					":Qyear"=>trim($_POST['Qyear']),
					":Qterm"=>trim($_POST['Qterm']),
					":Qschool"=>trim($_POST['Qschool']),
					":Qteacherno"=>doWithBindStr($_POST['Qteacherno']),
					":Qteachername"=>doWithBindStr($_POST['Qteachername']),
					":Qrewardssort"=>doWithBindStr($_POST['Qrewardssort']),
					":Qrewardsgrade"=>doWithBindStr($_POST['Qrewardsgrade']),
					":Qstatus"=>trim($_POST['Qstatus'])
			);
			$sql = $model->getSqlMap("Reward/statisticsSelectCount.sql");
			$data = $model->sqlFind($sql,$bind);
			$arr['total'] = $data['ROWS'];
			if($arr['total']>0)
			{				
				$sql = $model->getPageSql(null,"Reward/statisticsSelect.sql", $this->_pageDataIndex, $this->_pageSize);				
				$arr['rows'] = $model->sqlQuery($sql,$bind);
			}
			else
				$arr['rows']=array();
			$this->ajaxReturn($arr,"JSON");
			exit;
		}
	
	
		$yearTerm = $this->md->sqlFind("select * from YEAR_TERM where [TYPE]='S'");
		$this->assign("yearTerm",$yearTerm);
			
		$this->display();
	}	
	
	
}
?>