<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/24
 * Time: 13:12
 */
namespace Application\Admin;
use System\Core\Router;
use System\Traits\Controller\Render;

class General {

    use Render;

    public function __construct(){
        defined('ADMIN_URI') or define('ADMIN_URI',PUBLIC_URI.'Admin/');
        defined('STATIC_URI') or define('STATIC_URI',PUBLIC_URI.'static/');
        defined('ASSETS_URI') or define('ASSETS_URI',PUBLIC_URI.'assets/');

        $classname = static::class;
        $pos = strrpos($classname,'\\');
        $classname = strtolower(false === $pos?$classname:substr($classname,$pos+1));//调用该方法的类的短名称
        $this->generalAssign($classname);
    }

    protected function generalAssign($name){
        $this->assign('user_info',[]);
        $this->assign('uri',[
            'update_password'   => Router::create('Admin','User','updatePasswd'),
            'update_nickname'   => Router::create('Admin','User','updateNickname'),
            'logout'            => Router::create('Admin','User','logout'),

        ]);

        $nav = [
            [
                'url'   => Router::create('Admin','User','index1'),
                'title' => '用户',
            ],
            [
                'url'   => Router::create('Admin','Config','index1'),
                'title' => '设置',
            ]
        ];
        $this->assign('menu_list',$nav);
        switch($name){
            case 'user':
                $subnav = [
                    [
                        'name'      => '配置管理',
                        'submenus'  => [
                            [
                                'url'  => Router::create('Admin','User','index1'),
                                'title'  => 'index1',
                            ],
                            [
                                'url'  => Router::create('Admin','User','index2'),
                                'title'  => 'index2',
                            ],
                        ],
                    ],
                    [
                        'name'      => '测试2',
                        'submenus'  => [
                            [
                                'url'  => Router::create('Admin','User','index3'),
                                'title'  => 'index3',
                            ],
                        ],
                    ]
                ];
            break;
            case 'config':
                $subnav = [
                    [
                        'name'      => '配置管理',
                        'submenus'  => [
                            [
                                'url'  => Router::create('Admin','Config','index1'),
                                'title'  => 'index1',
                            ],
                            [
                                'url'  => Router::create('Admin','Config','index2'),
                                'title'  => 'index2',
                            ],
                        ],
                    ],
                    [
                        'name'      => '测试2',
                        'submenus'  => [
                            [
                                'url'  => Router::create('Admin','Config','index3'),
                                'title'  => 'index3',
                            ],
                        ],
                    ],
                ];
                break;
            case 'index':

                break;
            default:
                throw new \Exception();
        }

        $this->assign('subnav',$subnav);


        $this->assign('style','blue_color');

        $this->assign('copyright',[
            'fl'    => '感谢使用<a href="http://www.onethink.cn" target="_blank">OneThink</a>管理平台',
            'fr'    => 'V0.1',
        ]);
    }

}