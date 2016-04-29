<?php
/**
 * Created by PhpStorm.
 * User: cwebs
 * Date: 2015/8/13
 * Time: 13:28
 */
class CasAction extends Action{
    var $localService = null;
    var $casServerURI = "https://sso.nbyzzj.cn:8443/cas/login";
    var $casServer = "sso.nbyzzj.cn";
    var $casServerPort = 8443;

    public function __construct(){
        parent::__construct();

        //定义本系统服务器地址
        $this->localService = "http://".$_SERVER["HTTP_HOST"]."/";

        //引入cas第三方库
        vendor("CAS.CAS");
    }

    public function logout(){
        phpCAS::client(CAS_VERSION_2_0,  $this->casServer,  $this->casServerPort, "/cas", false);

        //方法二:退出登录后返回地址 -- 登出方法中加此句
        phpCAS::setNoCasServerValidation();

        //测定退出完成之后的回调地址
        $param = array("service"=>$this->localService);
        session(null);
        phpCAS::logout($param);
    }

    public function login(){
        //输出cas头信息
        Header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

        // initialize phpCAS
        //phpCAS::client(CAS_VERSION_2_0,'服务地址',端口号,'cas的访问地址');
        phpCAS::client(CAS_VERSION_2_0,  $this->casServer,  $this->casServerPort, "/cas", false);

        //可以不用，用于调试，可以通过服务端的cas.log看到验证过程。
        // phpCAS::setDebug();
        //登陆成功后跳转的地址 -- 登陆方法中加此句
        phpCAS::setServerLoginUrl($this->casServerURI."?embed=true&service=".$this->localService."Teacher/Cas/login");

        //no SSL validation for the CAS server 不使用SSL服务校验
        phpCAS::setNoCasServerValidation();

        //这里会检测服务器端的退出的通知，就能实现php和其他语言平台间同步登出了
        phpCAS::handleLogoutRequests();

        if(phpCAS::checkAuthentication()){
            //获取登陆的用户名
            session("S_LOGIN_COUNT", 0);
            $_POST["userName"] = phpCAS::getUser();
            $GLOBALS["CAS_PWD"] = phpCas::getAttribute("pwd");
            //用户登陆成功后,采用js进行页面跳转
            $loginAction = new LoginAction();
            $loginAction->login();
        }else{
            phpCAS::forceAuthentication();
        }
        exit;
    }
}