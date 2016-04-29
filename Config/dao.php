<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/24
 * Time: 15:13
 */

return [
    'DRIVER_DEFAULT_INDEX' => 2,//默认为0
    'DRIVER_CLASS_LIST' => [
        \System\Core\Dao\MySQL::class,
        \System\Core\Dao\OCI::class,
        \System\Core\Dao\SQLServer::class,
    ],
    'DRIVER_CONFIG_LIST' => [
        [
            'type'      => 'Mysql',//数据库类型
            'dbname'    => 'kbylin',
            'username'  => 'lin',
            'password'  => '123456',
            'host'      => '192.168.200.173',
            'port'      => '3306',
            'charset'   => 'UTF8',
            'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
            'options'   => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
            ],
        ],
        [
            'type'      => 'Oci',//数据库类型
            'dbname'    => 'xor',//选择的数据库
            'username'  => 'lin',
            'password'  => '123456',
            'host'      => 'localhost',
            'port'      => '3306',
            'charset'   => 'UTF8',
            'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
            'options'   => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
            ],
        ],
        [
            'type'      => 'Sqlsrv',//数据库类型
            'dbname'    => 'yzzj_jwgl',//选择的数据库
            'username'  => 'sa',
            'password'  => 'ASD123zxc',
            'host'      => '192.168.200.171',
            'port'      => '1433',
            'charset'   => 'UTF8',
            'dsn'       => null,//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
            'options'   => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
            ],
        ],
    ],
];
