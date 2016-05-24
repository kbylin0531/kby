<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/16
 * Time: 16:22
 */
return [
    'DRIVER_DEFAULT_INDEX' => 0,
    'DRIVER_CLASS_LIST' => [
        \System\Core\Storage\File::class,
    ],
    'DRIVER_CONFIG_LIST' => [
        [
            'READ_LIMIT_ON'     => true,
            'WRITE_LIMIT_ON'    => true,
            'READABLE_SCOPE'    => KL_BASE_PATH,
            'WRITABLE_SCOPE'    => KL_RUNTIME_PATH,
            'ACCESS_FAILED_MODE'    => MODE_RETURN,
        ]
    ],

];