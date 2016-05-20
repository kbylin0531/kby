<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2016/3/16
 * Time: 23:18
 */
return [
    'EMPTY_CONTROLLER'  => Application\Admin\System\Controller\Menu::class,//控制器不存在时访问的空控制器
    'EMPTY_ACTION'      => 'Management',//控制器中不存在指定方法时反问的方法的名称
];