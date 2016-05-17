<?php

/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/3/16
 * Time: 8:21 PM
 */
namespace Application\Admin\Library;
use Application\Admin\Library\Interfaces\ActionScannerAdapterInterface;
use System\Core\KbylinException;

/**
 * Class ActionScanner 操作扫描器
 * @package Application\Admin\Library
 */
class ActionManager {

    /**
     * 适配器
     * @var ActionScannerAdapterInterface
     */
    private $adapter = null;

    /**
     * ActionScanner constructor.
     * @param ActionScannerAdapterInterface $adapter
     */
    public function __construct(ActionScannerAdapterInterface $adapter){
        $this->adapter = $adapter;
    }

    /**
     * 扫描目录
     * @param string $scandir
     * @return $this
     * @throws KbylinException
     */
    public function scan($scandir){
        if(!is_dir($scandir)){
            throw new KbylinException('Directory do not exist!');
        }
        $this->adapter->scan($scandir);
        return $this;
    }

    public function fetchModule(){
        return $this->adapter->fetchModule();
    }

    /**
     * 获取控制器列表
     * @param string $module 模块名称,注意区分大小写
     * @return array
     */
    public function fetchController($module){
        return $this->adapter->fetchController($module);
    }

    /**
     * 获取公共方法列表
     * @param string $module 模块名称
     * @param string $controler 控制器名称
     * @return array
     */
    public function fetchAction($module,$controler){
        return $this->adapter->fetchAction($module,$controler);
    }


}