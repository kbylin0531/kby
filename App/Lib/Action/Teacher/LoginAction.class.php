<?php
/**
 * 系统登陆页
 * User: educk
 * Date: 13-11-21
 * Time: 下午12:57
 */

class LoginAction extends Action
{
    /**
     * 登陆页面
     */
    public function index()
    {
        $this->display();
    }

    /**
     * 用户登陆
     */
    public function login(){
        if(intval(session("S_LOGIN_COUNT"))>2 && $_SESSION['verify'] != md5($_POST['checkCode']))
        {
            $this->_errorDisplay("验证码错误！");
        }

        $model = M("SqlsrvModel:");
        $user = $model->sqlFind('select * from USERS LEFT OUTER JOIN TEACHERS on USERS.TEACHERNO = TEACHERS.TEACHERNO
        		where USERNAME = ? and lock=?',array($_POST["userName"],0));

        if($user)
        {
        	$teacherNo=$user['TEACHERNO'];
        	$user_teacher = $model->sqlFind("SELECT T.SCHOOL,T.TGROUP FROM TEACHERS T WHERE T.TEACHERNO='$teacherNo'");
        	$user['SCHOOL'] = $user_teacher["SCHOOL"];
        	$user['TGROUP'] = $user_teacher["TGROUP"];        	 
        	
            $user = array_map("ctrim", $user);
            $this->_check($user['lock'],$user['password']); //用户的状态、及用户检测
            $guid = getGUID(session_id()); //获得GUID
            $this->_sessionToDB($user['username'], $guid); //把session信息写入到sessions表中

            session(null);
            session('S_USER_SCHOOL',$user['SCHOOL']);
            session("S_LOGIN_TYPE",1); //注册用户为教师
            session("S_USER_NAME", $user["username"]); //注册用户
            session("S_GUID",$guid); //注册GUID
            session("S_ROLES", $user['ROLES']); //注册角色信息
            session("S_LOGIN_COUNT", 0);
            session("S_USER_INFO", $user);
            if(isset($GLOBALS["CAS_LOGIN"]) && $GLOBALS["CAS_LOGIN"]===true) session("S_CAS_LOGIN", true);
            redirect("/Teacher/Index/index"); //跳转到教师主页
            exit;
        }

        $students = $model->sqlFind("select S.*,C.CLASSNAME from STUDENTS S left join CLASSES C on (S.CLASSNO=C.CLASSNO) where STUDENTNO=? and lock=?",array($_POST["userName"],0));
        if($students)
        {
            $students = array_map("ctrim", $students);
            $this->_check($students['lock'],$students['PASSWORD']); //用户的状态、及用户检测
            $guid = getGUID(session_id()); //获得GUID
            $this->_sessionToDB($students['STUDENTNO'], $guid); //把session信息写入到sessions表中

            session(null);
            session('S_USER_SCHOOL',$students['SCHOOL']);
            session("S_LOGIN_TYPE",2); //注册用户为学生
            session("S_USER_NAME", $students['STUDENTNO']); //注册用户
            session("S_GUID",$guid); //注册GUID
            session("S_ROLES", "S"); //注册角色信息
            session("S_LOGIN_COUNT", 0);
            session("S_USER_INFO", $students);
            redirect("/Student/Index/index"); //跳转到学生主页
            exit;
        }
        $this->_errorDisplay("用户不存在！");
    }

    /**
     * 用户退出
     */
    public function logout(){
        if(session("S_CAS_LOGIN")==true){
            redirect("/Teacher/Cas/logout");
        }else{
            session(null);
            redirect("/Teacher/Login/index");
        }
    }

    /**
     * 生成验证码
     */
    public function verify(){
        import('ORG.Util.Image');
        Image::buildImageVerify();
    }

    /**
     * 用户的状态、及用户检测
     * @param $lock 锁定状态
     * @param $password 登陆密码
     * @return void
     */
    private function _check($lock, $password){
        if($lock)
            $this->_errorDisplay("此用户已经被锁定，请联系管理员！");
        elseif(isset($GLOBALS["CAS_PWD"])) {
            unset($GLOBALS["CAS_PWD"]);
            $GLOBALS["CAS_LOGIN"] = true;
        }elseif(trim($password)!=$_POST["password"])
            $this->_errorDisplay("输入的密码不正确！");
    }

    /**
     * 显示错误，并显示模板
     * @param $errorMsg
     */
    private function _errorDisplay($errorMsg){
        session("S_LOGIN_COUNT", intval(session("S_LOGIN_COUNT"))+1);
        $this->assign("errorMsg", $errorMsg);
        $this->display("Teacher@Login/index");
        exit;
    }

    /**
     * 把session信息写入到sessions表中
     * @param $userName 用户名
     * @param $guid GUID;
     * @return void
     */
    private function _sessionToDB($userName, $guid)
    {
        $model = M("SqlsrvModel:");

        $row = $model->sqlFind("select count(*) NCOUNT from Sessions where UserName=?",array($userName));
        if($row['NCOUNT']==1)
        {
            $model->sqlExecute("update Sessions set SessionID=?, RemoteIP=?, LoginTime=GETDATE() where USERNAME=?", array($guid, get_client_ip(), $userName));
        }
        else
        {
            $model->sqlExecute("insert into Sessions (UserName, SessionID, RemoteIP, LoginTime) values (?,?,?,GETDATE())", array($userName, $guid, get_client_ip()));
        }
    }
}