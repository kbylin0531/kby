<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 19/05/16
 * Time: 19:00
 */
namespace Application\Admin\System\Controller;
use Application\Admin\Library\AdminController;

class ActionGroup extends AdminController{

    public function index(){
        $this->displayManagement();
    }

}