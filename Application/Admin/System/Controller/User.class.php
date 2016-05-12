<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/24
 * Time: 15:00
 */
namespace Application\Admin\Controller;
use Application\Admin\General;
use System\Core\Router;

class User extends General{



    public function index1(){$this->assign('index',11);$this->display('index');}
    public function index2(){$this->assign('index',22);$this->display('index');}
    public function index3(){$this->assign('index',33);$this->display('index');}

    public function updatePasswd(){}

    public function updateNickname(){}

    public function login(){}

    public function logout(){}

}