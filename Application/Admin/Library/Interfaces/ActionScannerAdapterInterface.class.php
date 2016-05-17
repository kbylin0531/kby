<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/3/16
 * Time: 5:41 PM
 */
namespace Application\Admin\Library\Interfaces;

interface ActionScannerAdapterInterface {

    /**
     * @param string $scandir 扫描目标
     * @return $this
     */
    public function scan($scandir);

    /**
     * 获取模块列表
     * @return array
     */
    public function fetchModule();

    /**
     * 获取控制器列表
     * @param string $module 模块名称,注意区分大小写
     * @return array
     */
    public function fetchController($module);

    /**
     * 获取公共方法列表
     * @param string $module 模块名称
     * @param string $controler 控制器名称
     * @return array
     */
    public function fetchAction($module,$controler);

}