/**
 * Created by Administrator on 2015/11/27.
 *
 * 由于插件中无法传递模板变量，故需要手动传递
 *
 *
 * 引入：
 *  <script src="__ROOT__/thirdparty/jquery/js/ExtSearcher.js"></script>
 *
 <label for="ES_YEAR">学年:</label><input name="year" size='4' id="ES_YEAR" class="ES_YEAR s" value="2015" />
 <label for="ES_TERM">学期:</label><input name="term" size='1' id="ES_TERM" class="ES_TERM s" value="1" />
 <label for="ES_GRADE">年级:</label><select name="grade" id="ES_GRADE" class="ES_GRADE s"></select>
 <label for="ES_SCHOOL">学部:</label><select name="schoolno" id="ES_SCHOOL" class="ES_SCHOOL s"></select>
 <label for="ES_CLASS">班级:</label><select name="classno" id="ES_CLASS" class="ES_CLASS s"></select>
 <label for="ES_TEACHER">教师:</label><select name="teacherno" id="ES_TEACHER" class="ES_TEACHER s"></select>
 <label for="ES_STUDENT">学生:</label><select name="studentno" id="ES_STUDENT" class="ES_STUDENT s"></select>
 <label for="ES_MAJOR">专业:</label><select name="majorno" id="ES_MAJOR" class="ES_MAJOR s"></select>
 <label for="ES_MAJORITEM">小专业:</label><select name="majoritemno" id="ES_MAJORITEM" class="ES_MAJORITEM s"></select>

 <label for="ES_APPROACH">修课方式:</label><select name="approach" id="ES_APPROACH" class="ES_APPROACH s"></select>
 <label for="ES_COURSETYPE">课程类别:</label><select name="coursetype" id="ES_COURSETYPE" class="ES_COURSETYPE s"></select>
 <label for="ES_EXAMTYPE">考试类别:</label><select name="examtype" id="ES_EXAMTYPE" class="ES_EXAMTYPE s"></select>

 <label for="ES_SCORETYPE">成绩类型:</label><select name="examtype" id="ES_SCORETYPE" class="ES_SCORETYPE s"></select>



 <label for="comptype">竞赛类型</label>
     <select id="comptype" name="comptype" class="ES_COMPETITION_TYPE s">
         <option value="C">体育竞赛</option>
         <option value="S">学科理论和技能竞赛</option>
     </select>
 <label for="complevel">竞赛级别</label><select id="complevel" name="complevel" class="ES_COMPETITION_LEVEL s"></select>
 <label for="comprank">获奖名次</label><select id="comprank" name="comprank" class="ES_COMPETITION_RANK s"></select>
 *
 * JS:(需要在HTML加载完毕后调用)
 * new ExtSearcher('__APP__/Common/Provider/seacher',true,'s').start();
 *
 * 二次开发：
 *  一、添加新的独立插叙项
 *      ①类似地设置选择器
            env.scoretypeSelect  = $('.ES_SCORETYPE'+spile).eq(0);
 *      ②设置加载函数，注意reqtag要与接收地址一致
             pro.loadScoreType = function (environment) {
                    if(!environment.scoretypeSelect.length) return ;
                    pro.doAjax(sourseUrl,{reqtag:'scoretype'},function(r){
                        pro.appendToSelect(r,environment.scoretypeSelect,'scoretype');
                    });
                };
 *      ③在pro.start方法中加入表示默认载入，如果未设置该项则不会加载
            pro.loadScoreType();
 *
 * 缺陷，为什么loadClass这样的函数中会获取的env会指向类的最后一次的env呢？
 *
 * @param sourseUrl 数据源根地址 __APP__/Common/Provider/seacher 获取数据类型通过tag进行区分
 * 参数二的意义：true表示全部不设置'全部（%）'的选项,false表示全部设置，也可以给定数组表示数组中的选项不设置
 *              参数二可选项为：grade,school,major,majoritem,courseapproach,coursetype,examtype,
 *                           scoretype,class,student,teacher,competitiontype,competitionrank
 * 参数三的意思：表示附加类名称，当一个页面有多个选择块并且相互独立的时候需要附加以作区分（如换班操作页面）
 */
function ExtSearcher(sourseUrl) {
    //参数2表示是否显示全部的选项
    var noall = arguments[1];

    var spile = arguments[2]?"."+arguments[2]:'';

    var env = this;
    var pro = ExtSearcher.prototype;

    //必须加上这些类
    env.yearInput = $(".ES_YEAR"+spile).eq(0);
    env.termInput = $(".ES_TERM"+spile).eq(0);
    env.gradeSelect = $('.ES_GRADE'+spile).eq(0);
    env.schoolSelect = $('.ES_SCHOOL'+spile).eq(0);

    //专业性质
    env.majorSelect = $('.ES_MAJOR'+spile).eq(0);
    env.majorItemSelect = $('.ES_MAJORITEM'+spile).eq(0);
    //课程性质
    env.approachSelect  = $('.ES_APPROACH'+spile).eq(0);
    env.coursetypeSelect= $('.ES_COURSETYPE'+spile).eq(0);
    env.examtypeSelect  = $('.ES_EXAMTYPE'+spile).eq(0);
    env.scoretypeSelect  = $('.ES_SCORETYPE'+spile).eq(0);



    env.competitionTypeSelect  = $('.ES_COMPETITION_TYPE'+spile).eq(0);
    env.competitionLevelSelect  = $('.ES_COMPETITION_LEVEL'+spile).eq(0);
    env.competitionRankSelect  = $('.ES_COMPETITION_RANK'+spile).eq(0);



    //有前提条件
    env.classSelect = $('.ES_CLASS'+spile).eq(0);
    env.studentSelect = $('.ES_STUDENT'+spile).eq(0);
    env.teacherSelect = $('.ES_TEACHER'+spile).eq(0);
/**********  通用函数 **************************************************/
    /**
     * 判断是否有空值
     * @returns {boolean}
     */
    pro.hasEmpty = function () {
        for(var x in arguments){
            if(!arguments[x] || arguments[x] === '%'){
                return true;
            }
        }
        return false;
    };
    /**
     * 清空下拉框
     * @param sel
     * @param name
     */
    pro.cleanSelect = function (sel,name) {
        var flag = GenKits.isArray(noall)?GenKits.inArray(name,noall):noall;
        sel.html(flag?'':'<option value="%">全部</option>');
    };

    pro.appendToSelect = function (data,target,noallname) {
        pro.cleanSelect(target,noallname);
        for(var x in data){
            console.log(data)
            $('<option value="'+data[x].value+'">'+data[x].text+'</option>').appendTo(target);
        }
    };

    pro.doAjax = function (url,obj,callback) {
        $.ajax({
            type: "POST",
            url: url,
            data: obj,
            async:false,
            dataType: "json",
            success: function (data) {
                return callback(data);
            }
        });
    };

/******************* 加载函数 无条件的数据获取 **********************************************************/
    //-- 加载年级 --//
    pro.loadGrade = function(environment) {
        if(!environment.gradeSelect.length) return ;
        pro.doAjax(sourseUrl,{reqtag:'grade'},function(r){
            pro.appendToSelect(r,environment.gradeSelect,'grade');
        });
    };
    //-- 加载学院 --//
    pro.loadSchools = function (environment) {
        if(!environment.schoolSelect.length) return ;
        pro.doAjax(sourseUrl,{reqtag:'school'},function(r){
            pro.appendToSelect(r,environment.schoolSelect,'school');
        });
    };
    //-- 专业 --//
    pro.loadMajor = function (environment) {
        if(!environment.majorSelect.length) return ;
        pro.doAjax(sourseUrl,{reqtag:'major'},function(r){
            pro.appendToSelect(r,environment.majorSelect,'major');
        });
    };


    //-- 修课方式 --//
    pro.loadCourseApproach = function (environment) {
        if(!environment.approachSelect.length) return ;
        pro.doAjax(sourseUrl,{reqtag:'courseapproach'},function(r){
            pro.appendToSelect(r,environment.approachSelect,'courseapproach');
        });
    };
    //-- 课程类型一 --//
    pro.loadCourseType = function (environment) {
        if(!environment.coursetypeSelect.length) return ;
        pro.doAjax(sourseUrl,{reqtag:'coursetype'},function(r){
            pro.appendToSelect(r,environment.coursetypeSelect,'coursetype');
        });
    };
    //-- 考核方式 --//
    pro.loadExamType = function (environment) {
        if(!environment.examtypeSelect.length) return ;
        pro.doAjax(sourseUrl,{reqtag:'examtype'},function(r){
            pro.appendToSelect(r,environment.examtypeSelect,'examtype');
        });
    };
    //-- 考核方式 --//
    pro.loadScoreType = function (environment) {
        if(!environment.scoretypeSelect.length) return ;
        pro.doAjax(sourseUrl,{reqtag:'scoretype'},function(r){
            pro.appendToSelect(r,environment.scoretypeSelect,'scoretype');
        });
    };



/****************** 加载函数 带前提条件的数据获取(需要预先判断前提条件按是否满足，否则不进行ajax) ***************/
    //-- 加载班级 --//
    pro.loadClasses = function(environment){
        if(!environment.classSelect.length) return ;
        var gradeVal = environment.gradeSelect.val();
        var schoolVal = environment.schoolSelect.val();
        if(pro.hasEmpty(gradeVal,schoolVal)){
            return pro.cleanSelect(environment.classSelect,'class');
        }
        pro.doAjax(sourseUrl,{reqtag:'class',grade:gradeVal,schoolno:schoolVal},function(r){
            pro.appendToSelect(r,environment.classSelect,'class');
        });
    };
    //-- 加载学生 --//
    pro.loadStudents = function(environment){
        if(!environment.studentSelect.length) return ;
        var classVal = environment.classSelect.val();
        if(pro.hasEmpty(classVal)){
            return pro.cleanSelect(environment.studentSelect,'student');
        }
        pro.doAjax(sourseUrl,{reqtag:'student',classno:classVal},function(r){
            pro.appendToSelect(r,environment.studentSelect,'student');
        });
    };

    //-- 专业项 --//
    pro.loadMajorItem = function (environment) {
        if(!environment.majorItemSelect.length) return ;

        var majorcode = environment.majorSelect.length ? environment.majorSelect.val() :  '%';

        //if(pro.hasEmpty(majorcode)){
        //    return pro.cleanSelect(environment.majorItemSelect,'majoritem');
        //}
        pro.doAjax(sourseUrl,{reqtag:'majoritem',majorcode:majorcode},function(r){
            pro.appendToSelect(r,environment.majorItemSelect,'majoritem');
        });
    };


    /**
     * 额外注意的是查询教师需要指定学年学期，因为班级上课的每个学期的教师有可能不同
     */
    pro.loadTeachers = function(environment){
        if(!environment.teacherSelect.length) return ;
        var yearVal = environment.yearInput.val();
        var termVal = environment.termInput.val();
        var classVal = environment.classSelect.val();
        if(pro.hasEmpty(yearVal,termVal,classVal)){
            return pro.cleanSelect(environment.teacherSelect,'teacher');
        }
        pro.doAjax(sourseUrl,{reqtag:'teacher',year:yearVal,term:termVal,classno:classVal},function(r){
            pro.appendToSelect(r,environment.teacherSelect,'teacher');
        });
    };
    /**
     * 夹杂比赛类型
     * @param environment
     */
    pro.loadCompetitionLevel = function (environment) {
        if(!environment.competitionLevelSelect.length) return ;
        var type = environment.competitionTypeSelect.val();
        pro.doAjax(sourseUrl,{reqtag:'competitionlevel','type':type},function(r){
            pro.appendToSelect(r,environment.competitionLevelSelect,'competitiontype');
        });
    };

    /**
     * 夹杂比赛类型
     * @param environment
     */
    pro.loadCompetitionRank = function (environment) {
        if(!environment.competitionRankSelect.length) return ;
        var type = environment.competitionTypeSelect.val();
        pro.doAjax(sourseUrl,{reqtag:'competitionrank','type':type},function(r){
            pro.appendToSelect(r,environment.competitionRankSelect,'competitionrank');
        });
    };


    /********* 下拉框值发生改变时重新加载回调 ***************************************************/
    pro.reloadSchool = pro.reloadGrade = function(environment){
        pro.loadClasses(environment);
        pro.reloadClass(environment);
    };
    pro.reloadClass = function (environment) {
        pro.loadTeachers(environment);
        pro.loadStudents(environment);
    };

    pro.reloadMajorItem = function (env) {
        pro.loadMajorItem(env);
    };

/************ 开始函数 **********************************************************************/
    pro.start = function () {

        //-- 事件注册 --//
        env.gradeSelect.change(function () {
            pro.reloadGrade(env);
        });
        env.schoolSelect.change(function () {
            pro.reloadSchool(env);
        });
        env.classSelect.change(function () {
            pro.reloadClass(env);
        });
        env.majorSelect.change(function () {
            pro.reloadMajorItem(env)
        });

        env.competitionTypeSelect.change(function () {
            pro.loadCompetitionLevel(env);
            pro.loadCompetitionRank(env);
        });

        //-- 预加载 --//
        //专业性质
        pro.loadMajor(env);
        pro.loadMajorItem(env);
        //课程性质
        pro.loadExamType(env);
        pro.loadCourseType(env);
        pro.loadCourseApproach(env);
        pro.loadScoreType(env);
        //学生性质
        pro.loadGrade(env);
        pro.loadSchools(env);
        pro.reloadGrade(env);
        pro.loadCompetitionLevel(env);
        pro.loadCompetitionRank(env);

        return env;
    };
    

}