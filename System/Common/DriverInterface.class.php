<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/4/13
 * Time: 13:11
 */
namespace System\Common;

interface DriverInterface {

    /**
     * 测试实现本接口的驱动是否可用
     * @return bool
     */
    public function available();

}