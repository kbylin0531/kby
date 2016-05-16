<?php
/**
 * 教务系统框架Action先祖
 * 主要处理系统常用参数：
 * @_pageSize : 参数：$_REQUEST["rows"]，每页大小，默认值 20
 * @_pageNo : 参数：$_REQUEST["page"]，当前页码，默认值 1
 * @_hasJson : 参数：$_REQUEST["hasJson"], 是否发送JSON数据，默认值 true
 * @_order  : 参数：$_REQUEST['sort']=数据栏目名称, $_REQUEST['order']=排序方法(desc,asc)
 * @_where : 参数：查询条件
 *
 * User: cwebs
 * Date: 13-11-27
 * Time: 上午10:26
 */
class CommonAction extends Action {
    protected $_pageSize = 20;                              // 每页大小
    protected $_pageNo = 1;                                 // 当前页码
    protected $_pageDataIndex = 0;                         // 数据记录索引位置
    protected $_hasJson = false;                           // 是否发送JSON数据
    protected $_order = null; //排序项
    protected $_where = null; //查询条件

    /**
     * 毕业实习课号，特殊课程
     */
    const GRADUATION_COURSENO = 'BYSX011';

    public function __construct(){
        parent::__construct();
        //初始化控制器
        $this->initController();

        $request = intval($_REQUEST["rows"]);
        if($request > 0) $this->_pageSize = $request;
        $request = intval($_REQUEST["page"]);
        if($request > 0) $this->_pageNo = $request;
        $this->_pageDataIndex = ($this->_pageNo -1) * $this->_pageSize;
        $request = strtolower(trim($_REQUEST["hasJson"]));
        if("true"==$request || 1==$request) $this->_hasJson=true;

        if(isset($_REQUEST['sort'])){
            $this->_order = strval($_REQUEST['sort'])." ";
            $this->_order .= isset($_REQUEST['order']) ? strval($_REQUEST['order']) : "asc";
        }
    }
    /**
     * 向前端报告错误的信息
     * <code>
     *  信息报告格式:
     *  array(
     *      'type'  =>  'error|info',
     *      'msg'   =>  'XXXXXXX',
     *  );
     * </code>
     * @param string $msg
     * @param mixed $type
     * @return void
     */
    protected function exitWithReport($msg='Error occurred',$type=false){
        $msgBox = array(
            'type'=>empty($type)?'error':'info',
            'message'=>$msg,
        );
        $this->ajaxReturn($msgBox,'JSON');
        exit;
    }
    protected function successWithReport($msg='Success'){
        $this->exitWithReport($msg,true);
    }
    protected function failedWithReport($msg='Failed'){
        $this->exitWithReport($msg,false);
    }

    protected function headerUtf8(){
        ob_end_clean();
        header("Content-type:text/html;charset=utf-8");
    }

    /**
     * 超时限制“解除”
     */
    protected function limitOff(){
        set_time_limit(1800);
    }

    /**
     * 返回错误消息
     * @param string $message
     * @param string $errorCode
     */
    protected function errorBack($message='Error!',$errorCode='478'){
        header("HTTP/1.0 {$errorCode} {$message}");exit();
    }

    protected function successBack($message='Success!',$errorCode='200'){
        header("HTTP/1.0 {$errorCode} {$message}");exit();
    }
    /**
     * 输出错误信息
     * @param $message
     */
    protected function exitWithUtf8($message){
        ob_end_clean();
        header("Content-type:text/html;charset=utf-8");
        exit($message);
    }

    /**
     * 数组或者字符串形式的周次
     *  转化为整数形式
     * @param string|array $weeks 一学期周序列
     * @param int $cut 一学期的总周数
     * @return int|null
     */
    protected function weeks2Int($weeks,$cut = 16){
        $zhouci = null;
        if(is_array($weeks)){
            $zhouci = '';
            $len = count($weeks);//几周
            if($len > $cut){
                $weeks = array_slice($weeks,0,$cut);
            }else{
                while(($len = count($weeks)) < 16){
                    $weeks[$len] = 0;//不足补零
                }
            }
            while($weeks){
                $zhouci .= array_shift($weeks);//从后向前串连
            }
            $zhouci = bindec(strrev($zhouci));
        }elseif(is_string($weeks)){
            $len = strlen($weeks);
            if($len < $cut){//不足补零
                $weeks .= str_repeat('0',$cut - $len);
            }elseif($len > $cut){//多余截取
                $weeks = substr($weeks,0,$cut);
            }
            $zhouci = bindec($weeks);//分解得到数组
        }
        return $zhouci;
    }
    /**
     * int形式的周次信息转字符串形式
     * @param int $week
     * @param int $cut
     * @return string
     */
    protected function week2String($week,$cut = 16){
        return str_pad(strrev(decbin($week)),$cut,0);
    }
    /**
     * 判断是否有管理员权限
     * @return bool
     */
    protected function hasAuthority(){
        $model = new CommonModel();
        $info = $model->getTeacherInfo();
        return $info['SCHOOL'] === 'A1';
    }

    /**
     * 确认教室是不是教务处人员
     * @param null $teacherno
     * @return int|string 1表示是教务处的老师
     */
    protected function isDeanTeahcer($teacherno=null){
        $username = getUsername();
        $model = new CommonModel();// M('SqlsrvModel:');
        $sql = 'SELECT USERS.isdean from USERS where USERNAME = :username;';
        $bind = array(':username'=>$username);

        $rst = $model->sqlQuery($sql,$bind);
        if(false == $rst){
            return '查询失败！'.$model->getDbError();
        }
        return intval($rst[0]['isdean']);
    }

    /**
     * 初始化控制器
     */
    protected function initController(){
        //定义所有输出不缓存
        header('Expires: Mon, 26 Jul 1970 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

        //150723自定义两个预定义常量
        define('IS_POST',       $_SERVER['REQUEST_METHOD'] =='POST' ? true : false);
        define('IS_AJAX', (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
            strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"));
        define('REQTAG',       isset($_REQUEST['reqtag'])?trim($_REQUEST['reqtag']):NULL);
        $this->assign('user_info',$_SESSION['S_USER_INFO']);
//mist($_SESSION['S_USER_INFO']);
    }

    /**
     * 获取上传文件，用于Excel导入
     * @return string
     */
    protected function getUpload() {
        import('ORG.Util.UploadFile');
        $upload = new UploadFile();
        $upload->savePath = './uploads/';
        if(!$upload->upload()) {// 上传错误提示错误信息
            return $upload->getErrorMsg();
        }else{// 上传成功
            $info = $upload->getUploadFileInfo();
            $info = $info[0];
            $info['path'] = str_replace('\\','/',dirname($_SERVER['SCRIPT_FILENAME']).'/uploads/').$info['savename'];
            return $info;
        }
    }
    /**
     * 判断登录的角色是否包含期望的角色（参数1）
     * @param string $expectrole 期望的角色
     * @return mixed false 没有在角色中找到期望的角色
     */
    protected  function checkRoleIsExpected($expectrole){
        return strpos(session('S_ROLES'),$expectrole);
    }
/****************************** 2015年7月23日 修改线 *******************************************************/


    /**
     * @param string $display
     * @param bool $hashPost
     * @param null $json
     */
    protected function __done($display="", $hashPost=true, $json=null){
        if(!isset($this->message)){
            $this->message = array();
        }
        if($json==null) $json = $this->_hasJson;
        if($json){
            $this->ajaxReturn($this->message,"JSON");
        }else{
            $this->assign("hashPost",$hashPost);
            $this->assign("message",$this->message);
            $this->display($display);
        }
        exit;
    }


    
    /**
     * 判断该用户是否属于教务处人员
     * 	各方法继承该方法，可以在模板中使用 '__URL__/checkUserIsDean'(方法授权)
     * 		控制器中使用$this->checkUserIsDean(getUsername());
     * @param string $username 登录账户名
     * @return mixed
     */
    public function checkUserIsDean($username){
    	return isDeanByUsername($username);
    }


    /**
     * 绑定学年学期并返回学年学期数据
     * @param $type
     * @return mixed
     * @throws Exception
     */
    public function assignYearTerm($type='S'){
        static $model = null;
        if(!isset($model)){
            $model = new CommonModel();// M("SqlsrvModel:");
        }

        $yearTerm = $model->sqlFind("select * from YEAR_TERM where [TYPE]=:code",array(':code'=>$type));
        if($yearTerm === false){
            return NULL;
        }
        if(is_array($yearTerm)){
            $this->assign("yearTerm",$yearTerm);
            return $yearTerm;
        }else{
            return NULL;
        }

    }






}