(function($){
    //todo 创建页面
    function createHumanize(target){
        var opts=$.data(target,"humanize").options;
        $(opts).addClass("humanize-f");
        $(target).tooltip($.extend({},opts,
            {
                onShow:function(){
                    if(opts.inited==true) return true; //如果已经初始化过了直接退出

                    $(target).tooltip('tip').css("filter","none"); //提示框设置为不透明

                    $(target).tooltip('tip').unbind().bind('mouseenter', function(){
                        $(target).unbind("blur mouseleave");
                        $(target).tooltip('show');
                    });

                    opts.inited=true;
                    $.data(this, "humanize", {options : opts});

                    if(opts.humType=="student") selectorStudent(target, opts);
                    else if(opts.humType=="classes") selectorClasses(target, opts);
                    else if(opts.humType=='gteacher') selectorGTeacher(target, opts);
                    else alert("错误的筛选类型，请检查humType值是否正确！");
                    //$(target).bind('mouseleave', function(){$(target).tooltip('hide');});
                    //alert($(target).tooltip('options').content.html());
                    //$.parser.parse().html($(target).tooltip('options').content.html());
                    //alert($(target).tooltip("arrow").parent().prop("outerHTML"));
                },
                onUpdate:function(content){
                    if(opts.inited==true) return true; //如果已经初始化过了直接退出
                    content.panel($.extend({border:false, closable:true,onBeforeClose:function(){$(target).tooltip('hide');return false}},$.fn.humanize.defaultsPanel[opts.humType]));
                }
            }));
    };

    //todo 学生选择器
    function selectorStudent(target, options){
        var content = $(target).tooltip('options').content;
        var grade = content.find(".easyui-humanize-grade");
        var school = content.find(".easyui-humanize-school");
        var classes = content.find(".easyui-humanize-classes");
        var student = content.find(".easyui-humanize-student");

        //todo 学年
        var grade_data = humanizeGetGrade();
        grade_data.onSelect = function(data){
            var url = humanizeGetClasses(data.value, school.combobox("getValue")).url;
            classes.combobox('clear');
            classes.combobox('reload',url);
        }
        grade.combobox(grade_data);

        //todo 学院
        var school_data = humanizeGetSchool();
        school_data.onSelect = function(data){
            var url = humanizeGetClasses(grade.combobox("getValue"), data.value).url;
            classes.combobox('clear')
            classes.combobox('reload',url);
        }
        school.combobox(school_data);

        //todo 班级
        var classes_data = humanizeGetClasses();
        classes_data.onSelect = function(data){
            var url = humanizeGetStudent(data.value).url;
            student.combobox('clear')
            student.combobox('reload',url);
        }
        classes.combobox(classes_data);

        //todo 学生
        var student_data = humanizeGetStudent();
        student_data.onSelect = function(data){
            selecterValues(target, options, data);
        }
        student.combobox(student_data);
    }

    //todo 班级选择器
    function selectorClasses(target, options){
        var content = $(target).tooltip('options').content;
        var grade = content.find(".easyui-humanize-grade");
        var school = content.find(".easyui-humanize-school");
        var classes = content.find(".easyui-humanize-classes");

        //todo 学年
        var grade_data = humanizeGetGrade();
        grade_data.onSelect = function(data){
            var url = humanizeGetClasses(data.value, school.combobox("getValue")).url;
            classes.combobox('clear');
            classes.combobox('reload',url);
        }
        grade.combobox(grade_data);

        //todo 学院
        var school_data = humanizeGetSchool();
        school_data.onSelect = function(data){
            var url = humanizeGetClasses(grade.combobox("getValue"), data.value).url;
            classes.combobox('clear')
            classes.combobox('reload',url);
        }
        school.combobox(school_data);

        //todo 班级
        var classes_data = humanizeGetClasses();
        classes_data.onSelect = function(data){
            selecterValues(target, options, data);
        }
        classes.combobox(classes_data);
    }

    //todo 教研组教师选择器
    function selectorGTeacher(target,options){
        var content = $(target).tooltip('options').content;
        var school = content.find(".easyui-humanize-school");
        var tgroup = content.find(".easyui-humanize-tgroup");
        var gteacher = content.find(".easyui-humanize-gteacher");

        //todo 学院
        var school_data = humanizeGetSchool();
        school_data.onSelect = function(data){
            var url = humanizeGetGTeacher(data.value, tgroup.combobox("getValue")).url;
            gteacher.combobox('clear')
            gteacher.combobox('reload',url);
        }
        school.combobox(school_data);

        //todo 教研组
        var tgroup_data = humanizeGetTGroup();
        tgroup_data.onSelect = function(data){
            var url = humanizeGetGTeacher(school.combobox("getValue"), data.value).url;
            gteacher.combobox('clear')
            gteacher.combobox('reload',url);
        }
        tgroup.combobox(tgroup_data);

        //todo 教师
        var gteacher_data = humanizeGetGTeacher();
        gteacher_data.onSelect = function(data){
            selecterValues(target, options, data);
        }
        gteacher.combobox(gteacher_data);
    }

    function selecterValues(target, options, data){
       var str = $.trim($(target).val());
       var len = str.length;
       var i = str.indexOf('%');

       var _val;
       if(options.selValue=="id") _val = $.trim(data.value);
       else _val = $.trim(data.text);

       if(i==-1){str=_val}
       else if(i==0 && len>1){ str = '%'+_val}
       else {str = _val+'%'}

       var _type = $(target).attr("type");
       if(_type=="select") $(target).val(_val);
       if(_type=="checkbox" || _type=="radio") {
           $(target).each(function(){
               if($(this).attr('value')==_val) $(this).attr('checked','true');
           });
       }
       else $(target).val(str);
   }

    //todo 获得学年
    function humanizeGetGrade(){
        return {
            url:'/Common/Provider/seacher/reqtag/grade',
            valueField:'value',
            textField:'text'
        }
    }

    //todo 获得学部
    function humanizeGetSchool(){
        return {
            url: '/Common/Provider/seacher/reqtag/school',
            valueField: 'value',
            textField: 'text'
        }
    }

    //todo 获得班级
    function humanizeGetClasses(grade, school){
        var url = "/Common/Provider/seacher/reqtag/class";
        if(grade && grade.length>0) url += "/greade/"+grade;
        if(school && school.length>0) url += "/schoolno/"+school;

        return {
            url:url,
            valueField:'value',
            textField:'text'
        };
    }

    //todo 获得学生
    function humanizeGetStudent(classno){
        var url = "/Common/Provider/seacher/reqtag/student";
        if(classno && classno.length>0) url += "/classno/"+classno;

        return {
            url:url,
            valueField:'value',
            textField:'text'
        }
    }

    //todo 教研组教师
    function humanizeGetGTeacher(school, tgroup){
        var url = "/Common/Provider/seacher/reqtag/g-teacher";
        if(school && school.length>0) url += "/schoolno/"+school;
        if(tgroup && tgroup.length>0) url += "/tgroup/"+tgroup;

        return {
            url:url,
            valueField:'value',
            textField:'text'
        }
    }

    //todo 教研组
    function humanizeGetTGroup(){
        return {
            url:"/Common/Provider/seacher/reqtag/tgroup",
            valueField:'value',
            textField:'text'
        }
    }

    //todo 主方法
    $.fn.humanize = function(options, param){
        if (typeof options == 'string'){
            return $.fn.humanize.methods[options](this, param);
        }

        options = options || {};
        return this.each(function(){
            var state = $.data(this, "humanize");
            if (state) {
                $.extend(state.options, options);
                //$(this).tooltip("show");
            } else {
                //content : $("<div></div>")
                $.data(this, "humanize", {options : $.extend({inited:false, content:$("<div></div>")}, $.fn.humanize.defaults, $.fn.humanize.parseOptions(this), options)});
            }

            createHumanize(this);
            //你的插件。。。
            //如果在这里动态的生成了  easyui 的控件，html 写到页面上后是不能渲染成 easyui 组件的，需要手动调用
            //$.parser.parse(你定义的html);
            //parse 必须渲染父节点，不能渲染节点本身
        });
    };

    //todo 对外暴露的方法
    $.fn.humanize.methods = {
    }

    //todo 默认值配置;
    $.fn.humanize.defaults = {
        humType : "classes",
        showEvent : "focus click dblclick",
        selValue : 'id' //id, text
    };

    $.fn.humanize.defaultsPanel = {
        classes : {width:200, border:false, closable:true,title:"班级筛选", href:"/System/NonSafe/selectClasses.html"},
        student : {width:200, border:false, closable:true, title:"学生筛选", href:"/System/NonSafe/selectStudent.html"},
        course : {width:200, border:false, closable:true, title:"课程筛选", href:"/System/NonSafe/selectCourse.html"},
        teacher : {width:200, border:false, closable:true, title:"任课教师筛选", href:"/System/NonSafe/selectTeacher.html"},
        gteacher : {width:200, border:false, closable:true, title:"教师筛选", href:"/System/NonSafe/selectGTeacher.html"},
        room : {width:200, border:false, closable:true, title:"教室筛选", href:"/System/NonSafe/selectRoom.html"}
    }

    //todo class声明式定义属性data-options转化为options
    $.fn.humanize.parseOptions = function(target) {
        var t = $(target);
        return $.extend({},$.parser.parseOptions(target,["humType","showEvent","content","selValue"]));//解析 data-options 中的初始化参数
    };

    //todo 将自定义的插件加入 easyui 的插件组
    $.parser.plugins.push('humanize');
})(jQuery);