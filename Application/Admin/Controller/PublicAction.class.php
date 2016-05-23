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
use System\Library\Session;
use System\Utils\Network;
use System\Utils\Response;

/**
 * Class PublicAction 公用操作
 * @package Application\Admin\Controller
 */
class PublicAction extends Controller{

    private static $_sessionkey = '_userinfo_';


    public function checkLoginStatus(){
        $status = Session::get(self::$_sessionkey);//return null if not set

    }

    public function checkUserPwd($username,$password,$remember=false){
        $model = new MemberModel();
        $result = $model->getUser($username);

        if(false === $result){
            Network::redirect(__CONTROLLER__.'/PageLogin#'.urlencode("用户'{$username}'不存在"));
        }elseif (rtrim($result['password']) !== $password){
            Network::redirect(__CONTROLLER__.'/PageLogin#'.urlencode('密码不正确'));
        }else{
            Network::redirect(__APPLICATION__.'/System/Menu/Management');
        }
    }

    public function PageLogin(){
        $this->display();
    }


    public function PageIconSelection(){
        $this->display();
    }

}