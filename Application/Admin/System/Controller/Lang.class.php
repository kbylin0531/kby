<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/5/16
 * Time: 11:24 AM
 */

namespace Application\Admin\Controller;
use Application\Admin\Library\AdminController;

class Lang extends AdminController{

    public function index(){
        $this->display();
    }
}