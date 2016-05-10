<?php
return [
    //当前激活的CDN方案
    'active_index'    => 0,
    //方案列表
    'solution_list' => [
        0   => [
            'css'   => [
                'assets/plugins/font-awesome/css/font-awesome.min.css',
                'assets/plugins/simple-line-icons/simple-line-icons.min.css',
                'assets/plugins/bootstrap/css/bootstrap.min.css',
                'assets/plugins/toastr/toastr.min.css',

                'assets/css/themes/darkblue.min.css',
                'assets/css/components.css',
                'assets/css/layout.css'
            ],
            'js'    => [
                'assets/plugins/bootstrap/js/bootstrap.min.js',
                'assets/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js',
                'assets/plugins/jquery-slimscroll/jquery.slimscroll.min.js',
                'assets/plugins/toastr/toastr.min.js',
                'assets/plugins/bootstrap-contextmenu/bootstrap-contextmenu.js',

                'assets/js/dazzling.js'
            ],
            //IE8
            'js_lt9'    =>[
                'assets/plugins/html5shiv/dist/html5shiv.min.js',
                'assets/plugins/respond/dest/respond.min.js',
                'assets/js/jquery-1.11.3.min.js',
                'assets/plugins/jquery-placeholder/jquery.placeholder.min.js'
            ],
            //非IE或者大于等于9
            'js_gt9'    => [
                'assets/js/jquery-2.2.3.min.js'
            ],
            'ico'   => [
                'https://elementary.io/favicon.ico',
            ]
        ],
    ]
];
