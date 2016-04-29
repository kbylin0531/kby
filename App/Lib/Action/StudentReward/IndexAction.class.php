<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/4
 * Time: 9:13
 */


/**
 * 各类特征：
 * DELP_NAME - 项目和学分固定，教师无需手动输入
 * COMP_NAME - 比赛类型、等级和名次固定，比赛名称手动输入，学分半固定（可手输，空置时使用系统预设）
 * ADDI_NAME - 项目和学分不固定，完全依靠教师手动输入
 *
 *
 * dvlp_type:
 *  0 - 发展性学分（for 鄞州职教）
 *  1 - 操行学分 （for 衢州中专）
 *  2 - 体验学分 （for 衢州中专）
 *  3 - 实习学分 （for 衢州中专）
 *
 * Class IndexAction
 */
class IndexAction extends RightAction {
    /**
     * 显示 教师端学生奖励添加页面
     */
    public function index(){

        switch(SCHOOL_CODE){
            case 'qzzz':
                define('DELP_NAME','证书、活动奖励');
                define('COMP_NAME','技能竞赛奖励');
                define('ADDI_NAME','操行、体验、实习');
                break;
            case 'yzzj':
                define('DELP_NAME','发展过程性');
                define('COMP_NAME','竞赛奖励');
                define('ADDI_NAME','技能证书、创业奖励');
                break;

            default:
                throw new Exception('未识别的学院代号，青岛入口文件处进行设置!');
        }

        $menu_list = array(
            'menus' => array(
                array(
                    'menuid'    => '1',
                    'icon'      => 'icon-personal',
                    'menuname'  => DELP_NAME.'学分',
                    'menus'     => array(
                        array(
                            'menuid'    => '11',
                            'menuname'  => '添加'.DELP_NAME,
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Management/pageCreateEncourageOfDevolopment'),
                        ),
                        array(
                            'menuid'    => '12',
                            'menuname'  => '查询'.DELP_NAME,
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Management/pageListEncourageOfDevolopment'),
                        ),
                    ),
                ),
                array(
                    'menuid'    => '3',
                    'icon'      => 'icon-personal',
                    'menuname'  => COMP_NAME.'学分',
                    'menus'     => array(
                        array(
                            'menuid'    => '31',
                            'menuname'  => '添加'.COMP_NAME,
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Management/pageCreateEncourageOfCompetition'),
                        ),
                        array(
                            'menuid'    => '32',
                            'menuname'  => '查询'.COMP_NAME,
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Management/pageListEncourageOfCompetition'),
                        ),
                    ),
                ),
                array(
                    'menuid'    => '4',
                    'icon'      => 'icon-personal',
                    'menuname'  => ADDI_NAME.'学分',
                    'menus'     => array(
                        array(
                            'menuid'    => '41',
                            'menuname'  => '增加'.ADDI_NAME,
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Management/pageCreateEncourageOfAddition'),
                        ),
                        array(
                            'menuid'    => '42',
                            'menuname'  => '查询'.ADDI_NAME,
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Management/pageListEncourageOfAddition'),
                        ),
                    ),
                ),
                array(
                    'menuid'    => '2',
                    'icon'      => 'icon-personal',
                    'menuname'  => '系统设置',
                    'menus'     => array(
                        //项目类型和学分不固定，设置取消 - 20160126
//                        array(
//                            'menuid'    => '21',
//                            'menuname'  => '技能证书设置',
//                            'icon'      => 'icon-personal',
//                            'url'       => U('StudentReward/Setting/pageCertificate'),
//                        ),
                        array(
                            'menuid'    => '22',
                            'menuname'  => '比赛等级设置',
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Setting/pageCompetitionLevel'),
                        ),
                        array(
                            'menuid'    => '23',
                            'menuname'  => '比赛名次设置',
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Setting/pageCompetitionRank'),
                        ),
                        array(
                            'menuid'    => '24',
                            'menuname'  => '比赛奖励设置',
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Setting/pageCompetitionReward'),
                        ),
                        array(
                            'menuid'    => '25',
                            'menuname'  => DELP_NAME.'设置',
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Setting/pageDevolopmentCourse'),
                        ),
                        array(
                            'menuid'    => '26',
                            'menuname'  => DELP_NAME.'类型', // 类型与子类型
                            'icon'      => 'icon-personal',
                            'url'       => U('StudentReward/Setting/pageDevolopmentCourseType'),
                        ),
                    ),
                ),
            ),
        );


        switch(SCHOOL_CODE){
            case 'yzzj':
                $menu_list['menus'][2]['menus'] = array(
                    array(
                        'menuid'    => '41',
                        'menuname'  => '增加',
                        'icon'      => 'icon-personal',
                        'url'       => U('StudentReward/Management/pageCreateEncourageOfAddition',array('dvlp_type'=>0)),
                    ),
                    array(
                        'menuid'    => '42',
                        'menuname'  => '查询',
                        'icon'      => 'icon-personal',
                        'url'       => U('StudentReward/Management/pageListEncourageOfAddition',array('dvlp_type'=>0)),
                    ),
                );
                break;
            case 'qzzz':
                $menu_list['menus'][2]['menus'] = array(
                    array(
                        'menuid'    => '41',
                        'menuname'  => '增加操行学分',
                        'icon'      => 'icon-personal',
                        'url'       => U('StudentReward/Management/pageCreateEncourageOfAddition',array('dvlp_type'=>1)),
                    ),
                    array(
                        'menuid'    => '42',
                        'menuname'  => '查询操行学分',
                        'icon'      => 'icon-personal',
                        'url'       => U('StudentReward/Management/pageListEncourageOfAddition',array('dvlp_type'=>1)),
                    ),
                    array(
                        'menuid'    => '41',
                        'menuname'  => '增加体验学分',
                        'icon'      => 'icon-personal',
                        'url'       => U('StudentReward/Management/pageCreateEncourageOfAddition',array('dvlp_type'=>2)),
                    ),
                    array(
                        'menuid'    => '42',
                        'menuname'  => '查询体验学分',
                        'icon'      => 'icon-personal',
                        'url'       => U('StudentReward/Management/pageListEncourageOfAddition',array('dvlp_type'=>2)),
                    ),
                    array(
                        'menuid'    => '41',
                        'menuname'  => '增加实习学分',
                        'icon'      => 'icon-personal',
                        'url'       => U('StudentReward/Management/pageCreateEncourageOfAddition',array('dvlp_type'=>3)),
                    ),
                    array(
                        'menuid'    => '42',
                        'menuname'  => '查询实习学分',
                        'icon'      => 'icon-personal',
                        'url'       => U('StudentReward/Management/pageListEncourageOfAddition',array('dvlp_type'=>3)),
                    ),
                );
                break;
            default:
                throw new Exception('Undefined school code!');
        }




        $this->assign('menu_list',json_encode($menu_list));

        $commonModel = new CommonModel();
        $this->assign('yearTerm',$commonModel->getYearTerm('S'));//使用系统默认的学年学期
        $this->display();
    }
/**
 *
INSERT INTO MENU_ACTIONS ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES ('24', NULL, '学生奖励', 'StudentReward/Index/index', '', 'CD', '', '|24|', '1');

INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24001,24, '教室管理', 'StudentReward/Setting/pageCertificate', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24002,24, '教室管理', 'StudentReward/Setting/listCertificate', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24003,24, '教室管理', 'StudentReward/Setting/createCertificate', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24004,24, '教室管理', 'StudentReward/Setting/deleteCertificate', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24005,24, '教室管理', 'StudentReward/Setting/updateCertificate', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24006,24, '教室管理', 'StudentReward/Setting/updateCertificateStatus', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24007,24, '教室管理', 'StudentReward/Setting/pageCompetitionLevel', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24008,24, '教室管理', 'StudentReward/Setting/listCompetitionLevel', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24009,24, '教室管理', 'StudentReward/Setting/createCompetitionLevel', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24010,24, '教室管理', 'StudentReward/Setting/deleteCompetitionLevel', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24011,24, '教室管理', 'StudentReward/Setting/updateCompetitionLevel', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24012,24, '教室管理', 'StudentReward/Setting/pageCompetitionRank', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24013,24, '教室管理', 'StudentReward/Setting/listCompetitionRank', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24014,24, '教室管理', 'StudentReward/Setting/createCompetitionRank', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24015,24, '教室管理', 'StudentReward/Setting/deleteCompetitionRank', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24016,24, '教室管理', 'StudentReward/Setting/updateCompetitionRank', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24017,24, '教室管理', 'StudentReward/Setting/pageCompetitionReward', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24018,24, '教室管理', 'StudentReward/Setting/listCompetitionReward', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24019,24, '教室管理', 'StudentReward/Setting/createCompetitionReward', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24020,24, '教室管理', 'StudentReward/Setting/deleteCompetitionReward', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24021,24, '教室管理', 'StudentReward/Setting/updateCompetitionReward', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24022,24, '教室管理', 'StudentReward/Setting/pageDevolopmentCourse', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24023,24, '教室管理', 'StudentReward/Setting/listDevolopmentCourse', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24024,24, '教室管理', 'StudentReward/Setting/createDevolopmentCourse', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24025,24, '教室管理', 'StudentReward/Setting/updateDevolopmentCourse', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24026,24, '教室管理', 'StudentReward/Setting/deleteDevolopmentCourse', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24027,24, '教室管理', 'StudentReward/Setting/pageDevolopmentCourseType', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24028,24, '教室管理', 'StudentReward/Setting/listDevolopmentCourseType', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24029,24, '教室管理', 'StudentReward/Setting/createDevolopmentCourseType', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24030,24, '教室管理', 'StudentReward/Setting/deleteDevolopmentCourseType', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24031,24, '教室管理', 'StudentReward/Setting/updateDevolopmentCourseType', '', 'CD', '', '|24|', '1');

INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24101,24, '教室管理', 'StudentReward/Management/pageCreateEncourageOfDevolopment', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24102,24, '教室管理', 'StudentReward/Management/getTreeCombo', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24103,24, '教室管理', 'StudentReward/Management/createEncourageOfDevolopment', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24104,24, '教室管理', 'StudentReward/Management/pageImport', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24105,24, '教室管理', 'StudentReward/Management/importEncourageOfDevolopment', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24106,24, '教室管理', 'StudentReward/Management/pageListEncourageOfDevolopment', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24107,24, '教室管理', 'StudentReward/Management/listEncourageOfDevolopment', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24108,24, '教室管理', 'StudentReward/Management/deleteEncourageOfDevolopment', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24109,24, '教室管理', 'StudentReward/Management/updateEncourageOfDevolopment', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24110,24, '教室管理', 'StudentReward/Management/pageCreateEncourageOfCompetition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24111,24, '教室管理', 'StudentReward/Management/createEncourageOfCompetition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24112,24, '教室管理', 'StudentReward/Management/importEncourageOfCompetition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24113,24, '教室管理', 'StudentReward/Management/listEncourageOfCompetition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24114,24, '教室管理', 'StudentReward/Management/deleteEncourageOfCompetition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24115,24, '教室管理', 'StudentReward/Management/updateEncourageOfCompetition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24116,24, '教室管理', 'StudentReward/Management/pageCreateEncourageOfAddition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24117,24, '教室管理', 'StudentReward/Management/pageListEncourageOfAddition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24118,24, '教室管理', 'StudentReward/Management/listEncourageOfAddition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24119,24, '教室管理', 'StudentReward/Management/createEncourageOfAddition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24120,24, '教室管理', 'StudentReward/Management/updateEncourageOfAddition', '', 'CD', '', '|24|', '1');
INSERT INTO [yzzj_jwgl].[dbo].[MENU_ACTIONS]
(id,[PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES
(24121,24, '教室管理', 'StudentReward/Management/importEncourageOfAddition', '', 'CD', '', '|24|', '1');
 *
 *
 *
 *
 */
}