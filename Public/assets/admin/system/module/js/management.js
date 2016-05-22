/**
 * Created by kbylin on 19/05/16.
 */
dazz.ready(function () {
    var datatable = $('#dtable');

    var contextmenu = Dazzling.contextmenu.create([
        {
            'edit':'编辑',
            'delete':'删除'
        },
        {
            'deleteSelected':'删除选中'
        }
    ],function(element,tabindex,title){
        var data = tableManager.data(element);
//                                    console.log(tabindex,title,element);
        switch (tabindex){
            case 'edit':
                Dazzling.form.autoFill("#form_module",data,[
                    'id','title','order','description','status',
                    'code','parent'
                ]);
                Dazzling.modal.show(editModal);
                break;
            case 'delete':
                break;
            case 'deleteSelect':
                break;
        }
    });

    var tableManager = Dazzling.datatables.bind(datatable,{
        "dom": '<"toolbar">frtip',
        'columns':[
            {
                'title':'ID',
                'width':'8%',
                'data':'id'
            },
            {
                'title':'名称',
                'width':'20%',
                'className':'text_align_center',
                'data':'title'
            },
            {
                'title':'状态',
                'width':'8%',
                'className':'text_align_center',
                'data':function(row){
//                        console.log(row)
                    return parseInt(row.status)?'启用':'禁止';
                }
            },
            {
                'title':'排序',
                'width':'8%',
                'className':'text_align_center',
                'data':'order'
            },
            {
                'title':'代号',
                'width':'15%',
                'data':'code'
            },
            {
                'title':'描述',
                'data':'description'
            }
        ]
    }).
        onDraw(function () {contextmenu.bind(datatable.find("tr"));});

    var editModal = Dazzling.modal.create('#editModule',{
        'title':'编辑模块',
        'confirm':function () {
            var obj = dazz.utils.parseUrl(Dazzling.form.serialize("#form_module"));
            Dazzling.post(ctler_uri+'updateModule',obj,function (data) {
                if(data['_type'] > 0){
                    //更新对象
                    tableManager.update(obj);
                    //只有成功时才会自动消失
                    Dazzling.modal.hide(editModal);
                }
            });
        },
        'cancel':function () {
            Dazzling.modal.hide(editModal);
        }
    });
    var loadData = function () {
        Dazzling.post(
            ctler_uri+'listModule',
            {},
            function (data) {
                tableManager.load(data);
            }
        );
    };
    loadData();
    Dazzling.page.registerAction('刷新',function () {
        loadData();
        Dazzling.toast.success('加载完成!');
    });
    Dazzling.page.registerAction('扫描中页',function () {
        Dazzling.post(ctler_uri+"scanCwebsModule",{});
    });
    Dazzling.page.registerAction('扫描新系统',function () {
        Dazzling.post(ctler_uri+"scanKbylinModule",{});
    });

});