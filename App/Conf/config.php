<?php
return array(
	'__UPFILE__' => substr($_SERVER['DOCUMENT_ROOT'],0,strrpos($_SERVER['DOCUMENT_ROOT'],"\\"))."\\upload\\",
	//'TAGLIB_LOAD'               => true,//加载标签库打开
    //'APP_AUTOLOAD_PATH'         =>'@.TagLib',
    //'TAGLIB_BUILD_IN'           =>'Cx,Cwebs',
    'APP_GROUP_LIST' => 'Teacher,Student,System,Classes,Course,Major,Room,Status,Programs,Userinfo,Quality,Archive,Book,Credit,Attendance,Statistic,CoursePlan,Schedule,TeacherSchedules,SemesterTimetable,Results,Workload,CourseManager,Visit,Exam,Common,Moral,Evaluation,TReward,StudentReward', //项目分组设定
    'DEFAULT_GROUP'  => 'Teacher', //默认分组
    'LOG_RECORD' => true, // 开启日志记录
    "LOAD_EXT_FILE" => "right,cwebsSchedule", //自动加载类
    "TRANSACTION_ISOLATION" => SQLSRV_TXN_READ_UNCOMMITTED, //事务级别SQLSRV_TXN_READ_UNCOMMITTED=1，默认为SQLSRV_TXN_READ_COMMITTED=2

    //数据库配置信息
    'DB_TYPE'   => 'sqlsrv', // 数据库类型
    'DB_HOST'   => '192.168.200.171', // 服务器地址
    'DB_NAME'   => 'yzzj_jwgl', // 数据库名
    'DB_USER'   => 'sa', // 用户名
    'DB_PWD'    => 'ASD123zxc', // 密码
    'DB_PORT'   => 1433, // 端口
    'DB_PREFIX' => '', // 数据库表前缀


    //其他项目配置参数
    'TMPL_PARSE_STRING'  =>array(
        '__TITLE__' => '鄞州职业教育中心教务管理系统',
        '__COPYRIGHT__' => '版权所有：鄞州职业教育中心@2015    技术支持：易科中页 0574-87901749',
        '__WELCOME__' => '鄞州职业教育中心欢迎您',
        '__SCHOOLNAME__'   => '鄞州职业教育中心',
        '__CHROME__'    => __ROOT__.'/soft/chrome_41.exe',
        '__DEANNAME__'  => DEAN_NAME,
        '__SCHOOLCODE__'    => SCHOOL_CODE,
    )
);