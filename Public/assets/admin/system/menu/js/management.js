/**
 * Created by kbylin on 17/05/16.
 */

dazz.ready(function () {
    var idLibrary = [];

    //初始化代码快
    (function () {
        Dazzling.page.registerAction('全部展开', function () {
            $(".dd").nestable("expandAll");
        }, "icon-resize-full");
        Dazzling.page.registerAction('全部折叠', function () {
            $(".dd").nestable("collapseAll");
        }, "icon-resize-small");
        $(window).resize(function () {
            Dazzling.page.adjustMinHeight(".nestable_container");
        }).trigger('resize');
    })();

    //assign to same group
    var topNestable = Dazzling.nestable.create(1).attachTo("#top_nestable_attach");
    var sideNestable = Dazzling.nestable.create(1).attachTo("#side_nestable_attach");

    //显示和添加item后自动递增ID
    var updateMenuIdForCreate = function (selector,id) {
        if(!id) id = idLibrary.max() +1;
        $(selector).find("input[name=id]").val(""+id);
    };

    //update the form using the object
    var updateFormUsing = function (object) {
        // console.log(MenuItemAddPane.target.find("#MenuItemAddForm")); throw 'XX';
        var form = MenuItemAddPane.target.find("#MenuItemAddForm");
        // console.log(MenuItemAddPane,form.length);
        dazz.utils.each(object,function (value, key) {
            // console.log(key,form.find("[name="+key+"]"),value)
            form.find("[name="+key+"]").val(value);
        });
    };

    //上下文菜单对象
    var MenuItemContextMenu = Dazzling.contextmenu.create([{
        'edit':'修改',
        'delete':'删除'
    }],function (element,tabindex) {
        if(tabindex === 'edit'){
            var obj = element.get(0).dataset;
            console.log(element,obj,dazz.utils.isObject(obj));
            // console.log(element.get(0).dataset);
            if(dazz.utils.isObject(obj)) {
                MenuItemAddPane.title('修改标题').show().onConfirm(function () {
                    // console.log(obj,"XXXX")
                    // return console.log(MenuItemAddPane.target.find("#MenuItemAddForm"),MenuItemAddPane.target.find("#MenuItemAddForm").serialize())
                    var obj = Dazzling.form.serialize("#MenuItemAddForm",true);
                    Dazzling.post(public_url+"updateMenuItem",obj,function (data, msg,msgtype) {
                        // console.log(msg,msgtype)
                        if(msg && (msgtype > 0)){
                            Dazzling.nestable.updateItemData(element,obj,function (ele, obj) {
                                return '<i class="'+obj['icon']+'"></i> '+obj['title'];
                            });
                            MenuItemAddPane.hide();
                        }
                    });
                });
                updateFormUsing(obj);
            }else{
                return Dazzling.toast.error('You click the wrong things!');
            }
        }
    });

    var MenuItemAddPane = Dazzling.modal.create("#MenuItemAddPane", {
        'title': '添加顶部菜单',
        'confirmText': '提交',
        'cancelText': '关闭',
        'shown':function () {
            // updateMenuIdForCreate("#MenuItemAddForm");
        },
        //确认和取消的回调函数
        'cancel': function () {
            //cancel btn is always to close the window,but confirm not
            MenuItemAddPane.hide();
        }
    });

    //操作列表
    var operator = (function () {
        return {
            loadTopModule : function () {
                Dazzling.post(public_url + 'listTopMenu', {}, function (data) {
                    topNestable.load(data,function (data,element) {
                        MenuItemContextMenu.bind(element);
                        idLibrary.push(parseInt(data.id));
                    });
//                        console.log(idLibrary)
                });
            }
        };
    })();

    $("#addTop").click(function () {
        MenuItemAddPane.show().onConfirm(function () {
            var obj = dazz.utils.parseUrl(Dazzling.form.serialize("#MenuItemAddForm"));
            var item = topNestable.createItem(obj);
            if(item){
                MenuItemContextMenu.bind(item);
                idLibrary.push(obj.id);
                updateMenuIdForCreate("#MenuItemAddForm");
                Dazzling.toast.success('添加成功!');
            }else{
                Dazzling.toast.error('添加失败!');
            }
        });
    });
    $("#saveTop").click(function () {
        var value = topNestable.serialize(true);
//            console.log(value);//获取序列化
        Dazzling.post(public_url+'saveTopMenu',{ topset:value});
    });

    $("#addSide").click(function () {
    });
    $("#saveSide").click(function () {
    });

    operator.loadTopModule();
});