<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/24
 * Time: 10:35
 */
namespace Application\Test\Controller;
use System\Library\Controller;

class Index extends Controller{

    public function __construct(){
        defined('RESOURSE_PATH') or define('RESOURSE_PATH',PUBLIC_PATH.'resourse/');
    }

    public function index(){
        $this->display();
    }

}