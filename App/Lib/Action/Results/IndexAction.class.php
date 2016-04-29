<?php
/**
 * 系统首页
 * User: cwebs
 * Date: 13-11-23
 * Time: 上午8:47
 */
class IndexAction extends RightAction {
    /**
     * 成绩管理首页
     */
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
        //TODO:即将转移到后台管理 菜单的显示

        isset(self::$menu_list) or static::$menu_list = array(
            'menus' => array(
                array(
                    'menuid'    => '1',
                    'icon'      => 'icon-search2',
                    'menuname'  => '成绩查询',
                    'menus'     => array(
                        array(
                            'menuid'    => '11',
                            'menuname'  => '查询学生个人成绩',
                            'icon'      => 'icon-personal',
                            'url'       => U('Results/Select/pagePersonScores'),
                        ),
                        array(
                            'menuid'    => '12',
                            'menuname'  => '不及格名单汇总',
                            'icon'      => 'icon-personalList',
                            'url'       => U('Results/Select/pagePasslessStudents'),
                        ),
                        array(
                            'menuid'    => '13',
                            'menuname'  => '班级学期成绩汇总表',
                            'icon'      => 'icon-book',
                            'url'       => U('Results/Select/pageClassStudentsScores'),
                        ),
                        array(
                            'menuid'    => '18',
                            'menuname'  => '毕业前补考学生列表',
                            'icon'      => 'icon-search',
                            'url'       => U('Results/Select/pageRetakeStudents'),
                        ),
                        array(
                            'menuid'    => '19',
                            'menuname'  => '查询课程成绩',
                            'icon'      => 'icon-kongbai',
                            'url'       => U('Results/Select/pageCourseStudentScore'),
                        ),
//                        array(
//                            'menuid'    => '21',
//                            'menuname'  => '班级学绩分排名表',
//                            'icon'      => 'icon-shuru',
//                            'url'       => U('Results/Results/XJF_order'),
//                        ),
                    ),
                ),
                array(
                    'menuid'    => '2',
                    'icon'      => 'icon-fenxi',
                    'menuname'  => '成绩分析',
                    'menus'     => array(
                        array(
                            'menuid'    => '23',
                            'menuname'  => '成绩分析(必修)', // 原来的"班级成绩分析"
                            'icon'      => 'icon-room',
                            'url'       => U('Results/Analysis/pageAnaliseClassScores'),
                        ),
                        array(
                            'menuid'    => '21',
                            'menuname'  => '成绩分析(选修)', // 原来的"班级成绩分析"
                            'icon'      => 'icon-room',
                            'url'       => U('Results/Analysis/pageAnaliseClassScoresForSpecial'),
                        ),
                        array(
                            'menuid'    => '24',
                            'menuname'  => '课程不及格学生列表',
                            'icon'      => 'icon-personal',
                            'url'       => U('Results/Analysis/pagePasslessStudent'),
                        ),
                        array(
                            'menuid'    => '25',
                            'menuname'  => '班级课程补考情况分析',
                            'icon'      => 'icon-personal',
                            'url'       => U('Results/Analysis/pageClassesPasslessStatus'),
                        ),
//                        array(
//                            'menuid'    => '25',
//                            'menuname'  => '退学警告名单(仅供参考)',
//                            'icon'      => 'icon-personal2',
//                            'url'       => U('Results/Results/Two_tuixue'),
//                        ),
                    ),
                ),
                array(
                    'menuid'    => '3',
                    'icon'      => 'icon-shuru',
                    'menuname'  => '成绩输入',
                    'menus'     => array(
                        array(
                            'menuid'    => '31',
                            'menuname'  => '成绩输入时间',
                            'icon'      => 'icon-personal',
                            'url'       => U('Results/Input/pageInputTimeSetting'),
                        ),
                        array(
                            'menuid'    => '36',
                            'menuname'  => '课程成绩输入初始化',
                            'icon'      => 'icon-personal',
                            'url'       => U('Results/Input/pageInputCourseInit'),
                        ),
                        array(
                            'menuid'    => '34',
                            'menuname'  => '任课教师成绩输入',
                            'icon'      => 'icon-search',
                            'url'       => U('Results/Input/pageFinalsSelect'),
                        ),
                        array(
                            'menuid'    => '39',
                            'menuname'  => '管理员成绩输入',
                            'icon'      => 'icon-personalList',
                            'url'       => U('Results/Input/pageResitSelect'),
                        ),
                        array(
                            'menuid'    => '40',
                            'menuname'  => '成绩导入',
                            'icon'      => 'icon-personalList',
                            'url'       => U('Results/Input/pageImport'),
                        ),
//                        array(
//                            'menuid'    => '39',
//                            'menuname'  => '管理员期末成绩输入',
//                            'icon'      => 'icon-personalList',
//                            'url'       => U('Results/Input/pageFinalsSelectForAdmin'),
//                        ),
                        array(
                            'menuid'    => '38',
                            'menuname'  => '总评成绩百分比管理',
                            'icon'      => 'icon-personalList',
                            'url'       => U('Results/Input/pageCourseGeneralScorePercentManage'),
                        ),
                        array(
                            'menuid'    => '32',
                            'menuname'  => '开放与查看课程',
                            'icon'      => 'icon-personalList',
                            'url'       => U('Results/Input/pageCoursesWithOpen'),
                        ),
                        array(
                            'menuid'    => '35',
                            'menuname'  => '还没有输入的课程',
                            'icon'      => 'icon-kongbai',
                            'url'       => U('Results/Input/pageCoursesWhichScoreInputness'),
                        ),
                    ),
                ),
                array(
                    'menuid'    => '5',
                    'icon'      => 'icon-print',
                    'menuname'  => '学分计算',
                    'menus'     => array(
                        array(
                            'menuid'    => '41',
                            'menuname'  => '课程学分计算',
                            'icon'      => 'icon-book',
                            'url'       => U('Results/Calculation/calculate'),
                        ),
                    ),
                ),
                array(
                    'menuid'    => '4',
                    'icon'      => 'icon-print',
                    'menuname'  => '打印成绩单',
                    'menus'     => array(
                        array(
                            'menuid'    => '42',
                            'menuname'  => '成绩单打印',
                            'icon'      => 'icon-print',
                            'url'       => U('Results/Print/pageGeneralPrint'),
                        ),
//                        array(
//                            'menuid'    => '41',
//                            'menuname'  => '学期初补考',
//                            'icon'      => 'icon-book',
//                            'url'       => U('Results/Print/pageResit'),
//                        ),
//                        array(
//                            'menuid'    => '42',
//                            'menuname'  => '学期末期终成绩*',
//                            'icon'      => 'icon-book2',
//                            'url'       => U('Results/Results/Four_two'),
//                        ),
//                        array(
//                            'menuid'    => '43',
//                            'menuname'  => '学生成绩总表打印(含毕业生)*',
//                            'icon'      => 'icon-excel',
//                            'url'       => U('Results/Results/Four_three_daying'),
//                        ),
//                        array(
//                            'menuid'    => '44',
//                            'menuname'  => '毕业前补考成绩',
//                            'icon'      => 'icon-bookList',
//                            'url'       => U('Results/Results/Four_four'),
//                        ),
                    ),
                ),
            ),
        );
        $this->assign('menu_list',json_encode(self::$menu_list));

        $this->assign('yearTerm',$this->model->getYearTerm('J'));
        $this->display();
    }

}