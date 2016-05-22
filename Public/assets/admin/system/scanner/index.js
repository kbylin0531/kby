/**
 * Created by kbylin on 20/05/16.
 */

dazz.ready(function () {

    // Dazzling.page.Kits.adjustMinHeight(".ranklist");

    var ListHandler = (function () {
        var ul = $(".ranklist>ul");
        return {
            append:function (content) {
                ul.append($('<li> <span class="content">'+content+'</span> </li>'));
            }
        };
    })();

    var modules = {
        'cwebs':[],
        'kbylin':[]
    };

    var module_serial = '';


    (function () {
        $("#scan").click(function () {
            Dazzling.post(public_uri+'scan',{},function (data) {
                var cwebs = data[0];
                var kbylin = data[1];

                dazz.utils.each(cwebs,function (controlers,modulename) {
                    modules['cwebs'][modulename] = [];
                    dazz.utils.each(controlers, function (controller, controllername) {
                        modules['cwebs'][modulename][controllername] = [];
                        dazz.utils.each(controller, function (method) {
                            modules['cwebs'][modulename][controllername].push(method);
                            ListHandler.append("检测到: 模块["+modulename+"] 控制器["+controllername+"] 方法["+method+"]");
                            module_serial += modulename+"@"+controllername+"@"+method+"*";
                        });
                    });
                });

                var lookModules = function (object) {
                    if(!object) return ;
                    dazz.utils.each(object,function (infos, modulename) {
                        modules['kbylin'][modulename] = [];
                        var controllers = infos.controller;
                        dazz.utils.each(controllers,function (controller,controllername) {
                            modules['kbylin'][modulename][controllername] = controller;
                            dazz.utils.each(controller, function (method) {
                                ListHandler.append("检测到: 模块["+modulename+"] 控制器["+controllername+"] 方法["+method+"]");
                                module_serial += modulename+"@"+controllername+"@"+method+"*";
                            });
                        });
                        lookModules(infos.children);
                    });
                };

                lookModules(kbylin);
                // console.log(kbylin,modules)
                module_serial = module_serial.substring(0,module_serial.length-1);
                // console.log(module_serial,module_serial.substring(0,module_serial.length-1))
            });
        });
        $("#writein").click(function () {
            // return console.log(modules.toString())
            Dazzling.post(public_uri+'writein',{modules:module_serial});
        });
    })();


});
