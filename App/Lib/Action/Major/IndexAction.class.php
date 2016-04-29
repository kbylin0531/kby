<?php
/**
 * 系统首页
 * User: cwebs
 * Date: 13-11-23
 * Time: 上午8:47
 */
class IndexAction extends RightAction {
    private $model;

    /**
     * 菜单配置
     * @var array
     */
    private static $menu_list = null;

    public function __construct(){
        parent::__construct();
        $this->model = new CommonModel();

    }

    public function index(){
        isset(self::$menu_list) or static::$menu_list = array(
            'menus' => array(
                array(
                    'menuid'    => '1',
                    'icon'      => 'icon-major',
                    'menuname'  => '专业管理',
                    'menus'     => array(
                        array(
                            'menuid'    => '11',
                            'menuname'  => '专业性质管理',
                            'icon'      => 'icon-personal',
                            'url'       => U('Major/Classnature/classnatureq'),
                        ),
                        array(
                            'menuid'    => '12',
                            'menuname'  => '专业代码管理',
                            'icon'      => 'icon-personalList',
                            'url'       => U('Major/Code/codeq'),
                        ),
                        array(
                            'menuid'    => '13',
                            'menuname'  => '专业条目管理',
                            'icon'      => 'icon-book',
                            'url'       => U('Major/Major/majors'),
                        ),
                        array(
                            'menuid'    => '12',
                            'menuname'  => '检索专业',
                            'icon'      => 'icon-personalList',
                            'url'       => U('Major/Plan/selectmajors'),
                        ),
                        array(
                            'menuid'    => '12',
                            'menuname'  => '专业绑定',
                            'icon'      => 'icon-personalList',
                            'url'       => U('Major/Major/pageBanding'),
                        ),
//                        array(
//                            'menuid'    => '13',
//                            'menuname'  => '专业培养计划',
//                            'icon'      => 'icon-book',
//                            'url'       => U('Major/Plan/plans'),
//                        ),
                        array(
                            'menuid'    => '13',
                            'menuname'  => '毕业审核',
                            'icon'      => 'icon-book',
                            'url'       => U('Major/MajorPlan/index'),
                        ),
                    ),
                ),
            ),
        );
        $this->assign('menu_list',json_encode(self::$menu_list));

        $this->assign('yearTerm',$this->model->getYearTerm('S'));
        $this->display('Common@Index/index');
    }

}