<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/17
 * Time: 12:59
 */
return [
    'DRIVER_DEFAULT_INDEX' => 0,
    'DRIVER_CLASS_LIST' => [
        \System\Library\View\SmartyEngine::class,
        \System\Library\View\KbylinEngine::class,
    ],
    'DRIVER_CONFIG_LIST' => [
        [
            'SMARTY_DIR'        => KL_SYSTEM_PATH.'Vendor/smarty3/libs/',
            'TEMPLATE_CACHE_DIR'    => KL_RUNTIME_PATH.'View/',

            'SMARTY_CONF'       => [ // 对应着smarty的配置项
                //模板变量分割符号
                'left_delimiter'    => '{',
                'right_delimiter'   => '}',
                //缓存开启和缓存时间
                'caching'           => true,
                'cache_lifetime'    => 1800, //half an hour
            ],
        ]
    ],

    //模板文件后缀名
    'TEMPLATE_SUFFIX'   => 'html',
    //模板文件提示错误信息模板
    'TEMPLATE_EMPTY_PATH'   => 'notpl',
];