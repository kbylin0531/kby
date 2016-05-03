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
class ActionScanner {

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

    public function getModules(){
        return $this->adapter->fetchModule();
    }



}