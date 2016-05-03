<?php

/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/3/16
 * Time: 5:46 PM
 */
namespace Application\Admin\Library\Adapter;
use Application\Admin\Library\Interfaces\ActionScannerAdapterInterface;
use System\Core\KbylinException;
use System\Core\Storage;

/**
 * Class CwebsActionScanner Cwebs模块扫描器
 * @package Application\Admin\Library\Adapter
 */
class CwebsActionScannerAdapter implements ActionScannerAdapterInterface{

    /**
     * 扫描目录
     * @var string
     */
    private $scandir = null;

    public function scan($scandir){
        $this->scandir = rtrim($scandir,'/').'/';
        if(!is_dir($this->scandir)){
            throw new KbylinException('Invalid path for Action Scanner!');
        }
        return $this;
    }

    public function fetchModule(){
        $modules = [];
        $files = Storage::readDirectory($this->scandir,false);
        foreach ($files as $name=>$path){
            if(is_dir($path)) $modules[] = $name;
        }
        return $modules;
    }

    public function fetchController($modulename){
        $controllers = [];
        $path = $this->scandir.$modulename.'/';
        $files = Storage::readDirectory($path);
        foreach ($files as $name=>$path){
            if(is_dir($path)) $controllers[strstr($name,'.class.php',true)] = $path;
        }
        return $controllers;
    }

    public function fetchAction($module,$controller){
        $path = $this->scandir."{$module}/{$controller}.class.php";
        @include_once $path;
        if(class_exists($controller)) throw new KbylinException('Invalid module and controller!',$module,$controller);
        $instance = new \ReflectionClass($controller);
        return $instance->getMethods(\ReflectionMethod::IS_PUBLIC);
    }

}