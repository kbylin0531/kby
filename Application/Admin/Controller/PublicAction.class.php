<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/13/16
 * Time: 2:21 PM
 */
namespace Application\Admin\Controller;
use System\Library\Controller;

/**
 * Class PublicAction 公用操作
 * @package Application\Admin\Controller
 */
class PublicAction extends Controller{

    public function PageIconSelection(){
        $this->display();
    }

}