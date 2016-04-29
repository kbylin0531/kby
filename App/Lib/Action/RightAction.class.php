<?php
/**
 * 验证Action先祖，需要权限验证的棋坛请继承此类
 *
 * User: educk
 * Date: 13-11-20
 * Time: 下午4:08
 */
class RightAction extends CommonAction{
	private $model;
    public function __construct(){
        parent::__construct();
        $this->model = new CommonModel();
        
        $actionPath = getActionPath();
        // 检验登陆
        $this->checkLogin($actionPath);

        // 如果是学生刚跳转到学生首页
        if($actionPath=="Teacher/Index/index" && session("S_LOGIN_TYPE")==2){
        	$this->logs(1);
            redirect("Student/Index/index");
            exit;
        }

        $user = $this->model->sqlFind("select ROLES from USERS where USERNAME=? and lock=?",
            array(session('S_USER_NAME'),0));
        $role = trim($user['ROLES']);
//        var_dump($role);
        if(!$role ){
            $role = "S";
        }

        //检验权限
        $mid = checkRight($actionPath, $role, session("S_LOGIN_TYPE"));
        if(true!==$mid)
        {
          $status = 710;

            $method_lost = $actionPath." 方法缺失，请到“系统维护/系统管理/方法授权”中添加！";
            $role_lost   = "本账户的角色对应的ID为【$mid】的方法权限缺失，请到“系统维护/系统管理/方法授权”中为该ID的方法添加本账号角色或者为本账号添加方法对应的角色！ ";

            if($this->_hasJson or IS_AJAX)
            {
                if($mid===false) header("HTTP/1.0 ".$status." ".$method_lost);
                else header("HTTP/1.0 ".$status." ".$role_lost);
            }elseif(IS_AJAX){
                //20150928 AJAX返回 错误信息
                $this->exitWithReport("'{$actionPath}' 方法权限缺失，请联系管理员！");
            }else{
                $this->assign("status", $status);
                $this->assign("errorMsg", $mid===false ? $method_lost : $role_lost);
                $this->display("Teacher@Login:error");
            }
            $this->logs(0);
            exit;
        }
        $this->logs(1);
    }
    /*
     * 下拉方法
     * @name:表名
     * @as:  变量名
     */
    public function xiala($name,$as){
        $shuju=M($name);
        $one= $shuju->select();
        $this->assign($as,$one);
    }

    /**
     * 检测登陆是否完整
     * @param $actionPath
     */
    private function checkLogin($actionPath)
    {
        //session中没有S_USER_NAME或者没有S_LOGIN_TYPE
        //表示没有登陆或者session到期，返回到登陆页，重新登陆
        if(session("?S_USER_NAME")==false || session("?S_LOGIN_TYPE")==false)
        {
            $status = 707;
            if($this->_hasJson)
            {
                header("HTTP/1.0 ".$status." Not Login");
            }
            else if($actionPath=="Teacher/Index/index")
            {
                redirect("Teacher/Login/index");
            }
            else
            {
                $this->assign("status", $status);
                $this->display("Teacher@Login:error");
            }
            $this->logs(0);
            exit;
        }

        //如果要LOGINS表中找不到相应该用户名(USERNAME)、SESSIONID和IP地址
        //表示用户登陆被重置，需要重新登陆
        $data = $this->model->sqlFind("select count(*) as NCOUNT from Sessions where SessionID=? and RemoteIP=?",
            array(session("S_GUID"), get_client_ip()));
        if($data["NCOUNT"]==0){
            $status = 708;
            if($this->_hasJson){
                header("HTTP/1.0 ".$status." Login Expired Or Reset");
            }else{
                $this->assign("status", $status);
                $this->display("Teacher@Login:error");
            }
            $this->logs(0);
            exit;
        }
    }
    /**
     * 写入系统日志
     * @param mixed $status
     */
    private function logs($status){
    	$method=$_SERVER["REQUEST_METHOD"];
    	$query = $method=="POST" ? print_r($_POST,true) : print_r($_GET,true);
        $query = preg_replace("/\n\s*/","", $query);
    	
    	$sql="INSERT INTO LOGS(USERNAME,EMAIL,REMOTEHOST,REMOTEIP,DERIVEDFROM,USERAGENT,COOKIEUSER,".
    	"COOKIEROLES,COOKIEGROUP,SCRIPTNAME,PATHINFO,QUERY,METHOD,TITLE,REQUESTTIME,SUCCESS)".
    	"VALUES('".$_SESSION['S_USER_NAME']."','','".$_SERVER['HTTP_HOST']."','".get_client_ip().
    	"','','".substr($_SERVER['HTTP_USER_AGENT'],0,40)."','".$_SESSION['S_USER_NAME']."',".
    	"'".$_SESSION['S_ROLES']."','','".substr(getActionPath(),0,strrpos(getActionPath(),'/')).
    	"','".strrchr(getActionPath(),'/').
    	"','".trim($query)."','".$method."','','".date('Y-m-d H:i:s')."','$status')";
    	
    	$this->model->sqlExecute($sql);
    }

    /**
     * 重写表
     * @param $fields
     * @return array
     */
    private function rewriteTableFields(array $fields){
        array_walk_recursive($fields,function(&$value,$key){
            //设置字段名称
//            isset($value['name']) or $value['name'] = 'id';
            if(is_string($value)){
                $value = array('text'=>$value);
            }elseif(null === $value){
                $value = array();//如果是null表示未设置，全部采取默认的设置
            }

            $value['name'] = $key;
            //设置字段名称
            isset($value['text']) or $value['text'] = "[未命名字段]";
            //设置宽度
            isset($value['width']) or $value['width'] = 40;
            //设置显隐
            if(isset($value['hidden'])){
                $value['hidden'] = $value['hidden']?'true':'false';
            }else{
                $value['hidden'] = 'false';
            }
            //设置对齐方式
            if(isset($value['align'])){
                switch($value['align']){
                    case -1:
                        $value['align'] = 'left';
                        break;
                    case 0:
                        $value['align'] = 'center';
                        break;
                    case 1:
                        $value['align'] = 'right';
                        break;
                    default:
//                        $value['align'] = $value['align'];//默认不变
                }
            }else{
                //未设置时默认关闭
                $value['align'] = 'center';
            }
        });
        return $fields;
    }

    /**
     * 设置Common/table.html中模板的设置
     * @param array $fields 表格列表
     * @param array $search 查询自动加载类型
     * @param string $url 表格数据源URL
     * @param bool $single 表格是否单选
     * @param bool $export 是否添加导出功能
     */
    protected function tableAssign(array $fields,array $search=null,$url=null,$single=true,$export=true){

        $this->assign('fields',$this->rewriteTableFields($fields));

        //自动搜索范围
        if(null === $search){
            //全部
            $string = 'false';
        }else{
            $string = '';
            foreach($search as $item){
                $string .= "'{$item}',";
            }
            $string = rtrim($string,' ,');
        }
        $this->assign('search',$string);

        $this->assign('listurl',null === $url?'__ACTION__':$url);
        $this->assign('single',$single?'true':'false');//文本的true或者false
        $this->assign('export',$export?1:0);
    }


}
