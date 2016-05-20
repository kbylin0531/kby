<?php

/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/3/16
 * Time: 5:46 PM
 */
namespace Application\Admin\Library\Adapter;
use System\Core\KbylinException;
use System\Core\Storage;

/**
 * Class CwebsActionScanner Cwebs模块扫描器
 * @package Application\Admin\Library\Adapter
 */
class CwebsActionScanner {

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
        $files = Storage::read($this->scandir,false);
        foreach ($files as $name=>$path){
            if(is_dir($path)) $modules[] = trim($name,'/');
        }
        return $modules;
    }

    public function fetchController($modulename){
        $contlers = [];
        $path = $this->scandir.$modulename.'/';
        $infos = Storage::read($path);
        foreach ($infos as $name=>$path){
            $name = strstr($name,'.class.php',true);
            if(in_array($name,[
                'IndexAction','ExcelAction'
            ])) continue; //避免IndexAction因为缺少命名空间而导致重复
            if(is_file($path)) $contlers[$name] = $path;
        }
        return $contlers;
    }

    public function fetchAction($module,$controller){
        $actions = [];
        $path = $this->scandir."{$module}/{$controller}.class.php";
        include_once BASE_PATH.'ThinkPHP/Lib/Core/Action.class.php';
        include_once $this->scandir.'CommonAction.class.php';
        include_once $this->scandir.'RightAction.class.php';
//        dumpout($module,$controller,$path,class_exists($controller),is_file($path));

        $content = Storage::read($path);
        if(false === $content){
            throw new KbylinException("{$path} not found!");
        }
        $tempclassname = "{$module}_{$controller}";
        $tempfile = RUNTIME_PATH."classes/{$tempclassname}.php";
//        dump($content);
        $newcontent = str_replace($controller,$tempclassname,$content);
//        dumpout($newcontent);
        if(!Storage::write($tempfile,$newcontent)) throw new KbylinException('Write failed!');
//        dump($module,$controller,$tempfile);
        include_once $tempfile;
        if(!class_exists($tempclassname)) throw new KbylinException('Invalid module and controller!',$module,$controller,$tempfile);
        $instance = new \ReflectionClass($tempclassname);
        $methods = $instance->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method){
            $name = $method->getName();
            if(0 === strpos($name,'_')) continue;//以单下划线或者双下划线开头直接忽略
            $actions[] = $name;
        }
        return $actions;
    }

}