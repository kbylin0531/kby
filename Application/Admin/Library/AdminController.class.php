<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/15
 * Time: 18:46
 */
namespace Application\Admin\Library;

use Application\Admin\System\Model\MenuModel;
use System\Library\Controller;
use System\Utils\Assets;
use System\Utils\SEK;

class AdminController extends Controller{

    public function __construct(){
        $this->checkLogin() or $this->redirectLoginPage();
    }

    /**
     * @param string|null $template 如果是null,将自动获取调用本方法的名称并去掉开头的Page前缀
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     */
    protected function displayManagement($template = null, $cache_id = null, $compile_id = null, $parent = null){
        //加载模块和菜单
//        dumpout(([
//            'cdn'   => $this->getCDN(),//加载CDN
//            'page'  => $this->getPageInfo(),
//            'user'  => $this->getUserInfo(),
//        ]));
        $this->assign('infos',json_encode([
            'cdn'   => $this->getCDN(),//加载CDN
            'page'  => $this->getPageInfo(),
            'user'  => $this->getUserInfo(),
        ]));

        //获取调用自己的函数
        null === $template and $template = SEK::getCallPlace(SEK::CALL_ELEMENT_FUNCTION,SEK::CALL_PLACE_FORWARD)[SEK::CALL_ELEMENT_FUNCTION];
        $this->display(substr($template,4) /* 第五个字符开始 */, $cache_id , $compile_id, $parent);
    }

    /**
     * 加载CDN方案
     * @return array
     */
    private function getCDN(){
        $solution = Assets::load('cdn');
        return $solution['solution_list'][$solution['active_index']];
    }

    protected function getUserInfo(){
        return Assets::load('_user');
    }
    /**
     * 分配管理员页面信息
     */
    protected function assignAdminPage(){
        $this->assignUserInfoList();
        $this->assignModulesList();
        $this->assignActionsList();
    }

    /**
     * 加载页面参数
     * @return array
     */
    private function getPageInfo(){
        $memuModel = new MenuModel();
        $pageinfo = [
            //head部分
            'title' => 'KbylinFramework',
            'coptright' => ' 2014 © YZ',
            //body部分
            'logo'  => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFYAAAAOCAYAAAC1i+ttAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4AUHAxccnGNgawAAARBJREFUWMPdV0EOAyEIFLP/Wp+uL6OXmlqrgKC2lsTDRmRhGEcFRHSlAYBrWelX+tTrtQYAZCxJXpQ/3vdrLqV2rKdPnq+/e/Fa5t0hVgOIiGQjqHkOFDYXbn2M7tIUNcKoXizKf3RHfLBz0i4SgRqjjLErk9JKhFSWRhqjYW29picpIin4NaCXsm+k4QSoR2is5kCzADLLvLQYroh8WJTDAqY1BqnhBbga1kqa4zVs+YY8aNi5QxJYYAHAlHxeXw5rrFUSYpUESSO8JLGVbJGytAWUdedYJeEvHginmd+pZbO0dYTJXD0jklAznGL5pSnM8mbfeSVb8W9I6R3QEJpP2qOkoGai9Fk8+27L+ofgHmS+qpSPeE/nAAAAAElFTkSuQmCC',

        ];
        $menu = [
            'header_menu'   => [
                'active_index'  => 0,
                'menu_list'     => $memuModel->getTopMenuSetting()
            ],
            'sidebar_menu'  => [
                'active_index'  => 0,
                'menu_list'     => [
                    [
                        'title' => 'Dashboard',
                        'href'  => '#',
                        'target'=> '_self',
                        'icon'  => 'icon-control-play',
                        'children'  => [
                            [
                                'title' => 'Section 1',
                                'href'  => '#',
                            ],
                            [
                                'title' => 'Section 2',
                                'href'  => '#',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Classic',
                        'href'  => '#',
                        'target'=> '_self',
                        'children'  => [
                            [
                                'title' => 'Section 1',
                                'href'  => '#',
                            ],
                            [
                                'title' => 'Section 2',
                                'href'  => '#',
                            ],
                            [
                                'title' => 'More',
                                'href'  => '#',
                                'children'   => [
                                    [
                                        'title' => 'SubSection',
                                        'href'  => '#',
                                        'children'   => [
                                            [
                                                'title' => 'SubSection',
                                                'href'  => '#',
                                                'children'   => [
                                                    [
                                                        'title' => 'SubSection',
                                                        'href'  => '#',
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
        return array_merge($pageinfo, $menu);
    }

//    private function getModules(){
//        return Assets::load('_menu');
//    }
//
//    private function getActions(){
//        return Assets::load('_action');
//    }

    /**
     * 分配用户信息列表
     */
    protected function assignUserInfoList(){}

    /**
     *  分配模块信息列表
     */
    protected function assignModulesList(){}

    /**
     * 分配模块下的操作信息列表
     */
    protected function assignActionsList(){}

    private function checkLogin(){
        return true;
    }

    private function redirectLoginPage(){
        echo '你还未登录系统';
    }

}