<?php

/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 22/05/16
 * Time: 21:21
 */
namespace Application\Admin\Member\Controller;
use Application\Admin\Library\AdminController;

class User extends AdminController{

    public function index(){
        $this->displayManagement();
    }

}