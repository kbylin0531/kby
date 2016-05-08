<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/7/16
 * Time: 11:06 AM
 */
return [
    //head部分
    'shortcut_ico'  => 'https://elementary.io/favicon.ico',
    'default_title' => 'KbylinFramework',
    'default_metas' => [
//        [
//            //重定向
//            'content'   => '2;url=http://www.w3school.com.cn',
//            'others' => [
//                'http-equiv'    => 'Refresh',
//            ],
//        ],
    ],

    //body部分
    'header_fixed' => true,
    'sidebar_fixed' => false,
    'header_top_fixed' => false,
    'header_menu_fixed' => false,
    'src_logo'  => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFYAAAAOCAYAAAC1i+ttAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4AUHAxccnGNgawAAARBJREFUWMPdV0EOAyEIFLP/Wp+uL6OXmlqrgKC2lsTDRmRhGEcFRHSlAYBrWelX+tTrtQYAZCxJXpQ/3vdrLqV2rKdPnq+/e/Fa5t0hVgOIiGQjqHkOFDYXbn2M7tIUNcKoXizKf3RHfLBz0i4SgRqjjLErk9JKhFSWRhqjYW29picpIin4NaCXsm+k4QSoR2is5kCzADLLvLQYroh8WJTDAqY1BqnhBbga1kqa4zVs+YY8aNi5QxJYYAHAlHxeXw5rrFUSYpUESSO8JLGVbJGytAWUdedYJeEvHginmd+pZbO0dYTJXD0jklAznGL5pSnM8mbfeSVb8W9I6R3QEJpP2qOkoGai9Fk8+27L+ofgHmS+qpSPeE/nAAAAAElFTkSuQmCC',
    'navbar_nav'   => [
        'active_index'  => 0,
        'menu_list'     => [
            0   =>  [
                'title' => 'Dashboard',
                'href'  => '#',
                'target'    => '_self',
            ],
            [
                'title' => 'Classic',
                'href'  => '#',
                'target'    => '_self',
                'icon' => 'fa-bookmark-o',
                'submenu'  => [
                    [
                        'title' => 'Section 1',
                        'href'  => '#',
                        'icon' => 'fa-bookmark-o',
                    ],
                    [
                        'title' => 'Section 2',
                        'href'  => '#',
                        'icon' => 'fa-user',
                    ],
                    [
                        'title' => 'More',
                        'href'  => '#',
                        'icon' => 'fa-envelope-o',
                        'submenu'   => [
                            [
                                'title' => 'SubSection',
                                'href'  => '#',
                                'icon' => 'fa-user',
                                'submenu'   => [
                                    [
                                        'title' => 'SubSection',
                                        'href'  => '#',
                                        'icon' => 'fa-user',
                                        'submenu'   => [
                                            [
                                                'title' => 'SubSection',
                                                'href'  => '#',
                                                'icon' => 'fa-user',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        ],
    ],
];