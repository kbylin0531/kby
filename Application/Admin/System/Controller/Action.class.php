<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 19/05/16
 * Time: 22:47
 */

namespace Application\Admin\System\Controller;
use Application\Admin\Library\AdminController;

class Action extends AdminController{

    public function index(){
        $this->displayManagement();
    }

}