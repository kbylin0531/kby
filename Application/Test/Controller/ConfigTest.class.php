<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/4/13
 * Time: 10:06
 */
namespace Application\Test\Controller;

use System\Core\Config;

class ConfigTest {


    public function index(){
        dumpout(

//            Config::readGlobal('cache')
//            Config::readAllGlobal()
//            Config::getGlobal('cache.DRIVER_CONFIG_LIST.0.path')
//            Config::getGlobalCache()

        );
    }

    public function set(){
        dumpout(Config::setGlobalCache(array('hello world'),5));
    }

    public function get(){
        dumpout(Config::getGlobalCache());
    }



    public function set1(){
        dumpout(Config::writeCustom('key02',['hello world!']));
    }

    public function get1(){
        dumpout(Config::readCustom('key02'));
    }



}