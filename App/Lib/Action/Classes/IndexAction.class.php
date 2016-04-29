<?php
/**
 * 系统首页
 * User: cwebs
 * Date: 13-11-23
 * Time: 上午8:47
 */
class IndexAction extends RightAction {

    /**
     * 教师端菜单配置
     * @var array
     */
    private static $menu_list = null;
    /**
     * 班级管理首页
     */
    public function index(){
        isset(self::$menu_list) or static::$menu_list = array(
            'menus' => array(
                array(
                    'menuid'    => '1',
                    'icon'      => 'icon-classTable',
                    'menuname'  => '班级管理',
                    'menus'     => array(
                        array(
                            'menuid'    => '11',
                            'menuname'  => '班级管理',
//                            'icon'      => 'icon-personalList',
                            'url'       => U('Classes/Class/classesq'),
                        ),
                        array(
                            'menuid'    => '12',
                            'menuname'  => '转班管理',
//                            'icon'      => 'icon-kongbai',
                            'url'       => U('Classes/Distributary/index'),
                        ),
                    ),
                ),
            ),
        );
        $this->assign('menu_list',json_encode(self::$menu_list));
        $this->display();
    }

}