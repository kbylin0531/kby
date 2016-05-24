<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/4/13
 * Time: 10:26
 */
return [
    'DRIVER_DEFAULT_INDEX' => 1,
    'DRIVER_CLASS_LIST' => [
        \System\Core\Cache\File::class,
        \System\Core\Cache\Memcache::class,
    ],
    'DRIVER_CONFIG_LIST' => [
        [
            //选自THinkPHP，大小写保持原样
            'expire'        => 0,
            'cache_subdir'  => false,
            'path_level'    => 1,
            'prefix'        => '',
            'length'        => 0,
            'path'          => KL_RUNTIME_PATH.'/Cache/',
            'data_compress' => false,
        ],
        [
            'host'      => '192.168.200.174,localhost',
            'port'      => 11211,
            'expire'    => 0,
            'prefix'    => '',
            'timeout'   => 1000, // 超时时间（单位：毫秒）
            'persistent'=> true,
            'length'    => 0,
        ],
    ],
];