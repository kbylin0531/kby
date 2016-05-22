<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 20/05/16
 * Time: 17:17
 */

namespace Application\Admin\Controller;
use Application\Admin\Library\AdminController;

class Test extends AdminController {


    public function index(){
        $this->displayManagement();
    }



}