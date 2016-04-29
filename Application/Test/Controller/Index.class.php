<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/24
 * Time: 10:35
 */
namespace Application\Test\Controller;
use System\Traits\Controller\Render;

class Index {

    use Render;

    public function __construct(){
        defined('RESOURSE_PATH') or define('RESOURSE_PATH',PUBLIC_PATH.'resourse/');
    }

    public function main(){
        $this->display();
    }

    public function left(){
        $this->display();
    }

    public function index(){
        $this->display();
    }

}