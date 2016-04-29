/**
* Created by Lin on 2015/8/7.
*/
//-- 函数预定义 --//
function hasEmpty(){
    for(var x in arguments){
        if(!arguments[x] || arguments[x] === '%'){
            return true;
        }
    }
    return false;
}
function cleanSelect(sel,noall){
    noall = noall?'':'<option value="%">全部</option>';
    sel.html(noall);
}

/**
 * 由于插件中无法传递模板变量，故需要手动传递
 * @param root 数据源根地址
 * @param noall true时不设置'%'
 * @param attachclass 附加class
 */
function searcher(root,noall,attachclass) {

    noall = noall?true:false;
    attachclass = attachclass?"."+attachclass:'';
    var env = this;
    var pro = searcher.prototype;

    env.yearInput = $(".SEARCHER_YEAR"+attachclass).eq(0);
    env.termInput = $(".SEARCHER_TERM"+attachclass).eq(0);
    env.gradeSelect = $('.SEARCHER_GRADE'+attachclass).eq(0);
    env.schoolSelect = $('.SEARCHER_SCHOOL'+attachclass).eq(0);
    env.classSelect = $('.SEARCHER_CLASS'+attachclass).eq(0);
    env.studentSelect = $('.SEARCHER_STUDENT'+attachclass).eq(0);
    env.teacherSelect = $('.SEARCHER_TEACHER'+attachclass).eq(0);

    //-- 加载年级 --//
    pro.loadGrade = function(){
        $.post(root+'/Common/Provider/seacher',{reqtag:'grade'},function(r){
            env.gradeSelect.html('');
            for(var x in r){
                $('<option value="'+r[x].value+'">'+r[x].text+'</option>').appendTo(env.gradeSelect);
            }
            $.post(root+'/Common/Provider/seacher',{reqtag:'class',greade:env.gradeSelect.val()},function(r){
                cleanSelect(env.classSelect,noall);
                for(var x in r){
                    $('<option value="'+r[x].value+'">'+r[x].text+'</option>').appendTo(env.classSelect);
                }
            });
        });
    };
    //-- 加载学院 --//
    pro.loadSchools = function () {
        $.post(root+'/Common/Provider/seacher',{reqtag:'school'},function(r){
            cleanSelect(env.schoolSelect,noall);
            for(var x in r){
                $('<option value="'+r[x].value+'">'+r[x].text+'</option>').appendTo(env.schoolSelect);
            }
        });
    };

    //-- 回调函数 --//
    pro.loadClasses = function(){
        if(0 === env.classSelect.length) return ;
        if(hasEmpty(env.gradeSelect.val(),env.schoolSelect.val())){
            return cleanSelect(env.classSelect,noall);
        }
        $.post(root+'/Common/Provider/seacher',{reqtag:'class',greade:env.gradeSelect.val(),schoolno:env.schoolSelect.val()},function(r){
            cleanSelect(env.classSelect,noall);
            for(var x in r){
                $('<option value="'+r[x].value+'">'+r[x].text+'</option>').appendTo(env.classSelect);
            }
        });
    };
    //-- 加载学生 --//
    pro.loadStudents = function(){
        if(0 === env.studentSelect.length) return ;
        if(hasEmpty(env.gradeSelect.val(),env.schoolSelect.val(),env.classSelect.val())){
            return cleanSelect(env.studentSelect,noall);
        }
        $.post(root+'/Common/Provider/seacher',{reqtag:'student',classno:env.classSelect.val()},function(r){
            cleanSelect(env.studentSelect,noall);
            for(var x in r) {
                $('<option value="'+r[x].value+'">'+r[x].text+'</option>').appendTo(env.studentSelect);
            }
        });
    };
    /**
     * 额外注意的是查询教师需要指定学年学期，因为班级上课的每个学期的教师有可能不同
     */
    pro.loadTeachers = function(){
        if(0 === env.teacherSelect.length) return ;
        if(hasEmpty(env.yearInput.val(),env.termInput.val(),env.gradeSelect.val(),env.schoolSelect.val(),env.classSelect.val())){
            return cleanSelect(env.teacherSelect,noall);
        }
        $.post(root+'/Common/Provider/seacher',{reqtag:'teacher',year:env.yearInput.val(),term:env.termInput.val(),classno:env.classSelect.val()},function(r){
            cleanSelect(env.teacherSelect,noall);
            for(var x in r){
                $('<option value="'+r[x].value+'">'+r[x].text+'</option>').appendTo(env.teacherSelect);
            }
        });
    };
    pro.loadTS = function(){
        pro.loadTeachers();
        pro.loadStudents();
    };
    pro.reloadForm = function(){
        pro.loadClasses();
        pro.loadTS();
    };


    pro.loadGrade();
    pro.loadSchools();

    cleanSelect(env.studentSelect,noall);//学生默认选择全部
    cleanSelect(env.teacherSelect,noall);

    //-- 事件注册 --//
    env.gradeSelect.change(pro.reloadForm);
    env.schoolSelect.change(pro.reloadForm);
    env.classSelect.change(pro.loadTS);



    pro.reloadForm();

}






