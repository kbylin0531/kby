<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/13/16
 * Time: 2:21 PM
 */
namespace Application\Admin\Controller;
use Application\Admin\Member\Model\MemberModel;
use System\Library\Controller;
use System\Library\Cookie;
use System\Library\Session;
use System\Utils\Network;
use System\Utils\ThinkBase64;

/**
 * Class PublicAction 公用操作
 * @package Application\Admin\Controller
 */
class PublicAction extends Controller{

    private static $usrkey = '_userinfo_';

    /**
     * check the current user login status
     * @return bool
     */
    public function checkLoginStatus(){
        $status = Session::get(self::$usrkey);//return null if not set
        if(!$status){
            $cookie = Cookie::get(self::$usrkey);
            if($cookie){
                $usrinfo = unserialize(ThinkBase64::decrypt($cookie, self::$usrkey));
                Session::set(self::$usrkey, $usrinfo);
//            dumpout($cookie,$usrinfo);
                return true;
            }
        }
        return $status?true:false;
    }


    public function doLogout(){
        Session::clear(self::$usrkey);
        Cookie::clear(self::$usrkey);
        Network::redirect(__CONTROLLER__.'/PageLogin');
    }

    /**
     * @param $username
     * @param $password
     * @param bool $remember
     */
    public function doLogin($username,$password,$remember=false){
        $model = new MemberModel();
        $usrinfo = $model->getUserInfo($username);

        if(!$usrinfo) Network::redirect(__CONTROLLER__.'/PageLogin#'.urlencode("用户'{$username}'不存在"));

        if(false === stripos($usrinfo['roles'], 'A')){
            Network::redirect(__CONTROLLER__.'/PageLogin#'.urlencode('暂时不允许非管理员用户登录!'));
        }

        if (md5(rtrim($usrinfo['password'])) === $password){
            //set session,browser must enable cookie
            if($remember){
                $sinfo = serialize($usrinfo);
                $cookie = ThinkBase64::encrypt($sinfo, self::$usrkey);
                Cookie::set(self::$usrkey, $cookie,7*24*3600);//one week
            }
            Session::set(self::$usrkey, $usrinfo);
            $this->go('Admin/System/Menu/Management');
        }else{
            Network::redirect(__CONTROLLER__.'/PageLogin#'.urlencode('密码不正确'));
        }
    }

    public function PageLogin(){
        $this->display();
    }


    public function PageIconSelection(){
        $this->display();
    }




    protected function go($path,$base=null){
        $base or $base = __APPLICATION__;
        $path = ltrim($path,'/');
        Network::redirect(__APPLICATION__.$path);
    }

}