<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/13
 * Time: 20:38
 */
namespace Application\Test\Controller;

use System\Core\Storage;

class StorageTest {

    public function index(){
        dumpout(
            Storage::write(BASE_PATH.'test.f','yes bean','UTF-8')
            ,Storage::write(RUNTIME_PATH.'test.f','yes bean','UTF-8') //BASE_PATH RUNTIME_PATH

//            Storage::read(dirname(BASE_PATH).'/yes.bean') //不可访问，返回null
//            ,Storage::read(BASE_PATH.'README.md') //可以访问


        );
    }

}