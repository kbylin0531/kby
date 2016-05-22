<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/13/16
 * Time: 2:21 PM
 */
namespace Application\Admin\Controller;
use System\Library\Controller;
use System\Library\Session;

/**
 * Class PublicAction 公用操作
 * @package Application\Admin\Controller
 */
class PublicAction extends Controller{

    private static $_sessionkey = '_kl_access_';


    public function checkLoginStatus(){
        $status = Session::get(self::$_sessionkey);//return null if not set

    }

    public function PageLogin(){
        $this->display();
    }


    public function PageIconSelection(){
        $this->display();
    }

}