<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/4/13
 * Time: 9:36
 */
namespace Application\Test\Controller;

use System\Core\Cache;

class CacheTest {


    const CACHE_ID = 1;

    public function index(){


    }


    public function get(){
        Cache::using(self::CACHE_ID);
        dumpout(Cache::get('key01'));
    }

    public function del(){
        Cache::using(self::CACHE_ID);
        dumpout(Cache::delete('key01'));
    }

    public function clean(){
        dumpout(Cache::clean());
    }

    public function set(){
        Cache::using(self::CACHE_ID);
        dumpout(Cache::set('key01',array('hello world'),30));
    }

}