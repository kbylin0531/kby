<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/17
 * Time: 16:38
 */
return [
    'DRIVER_DEFAULT_INDEX' => 0,
    'DRIVER_CLASS_LIST' => [
        \System\Core\Config\File::class,
    ],
    'DRIVER_CONFIG_LIST' => [
    ],
    'CONFIG_CACHE_LIST'     => 'cache,cookie',
    'CONFIG_CACHE_EXPIRE'   => 0,//0表示永不过期
];