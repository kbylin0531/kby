/**
 * Dazzling 粲
 * @type object
 */
var Dazzling = (function () {
    "use strict";
    if(!jQuery ) throw "Require Jquery!";
    var convention = {
        //post刷新间隔
        'requestInterval':400,
        'responsiveBreakpoint':{
            'xs' : 480,     // extra small
            'sm' : 768,     // small
            'md' : 992,     // medium
            'lg' : 1200     // large
        }
    };
    var _lastRequestTime = null;//上次发起请求时间

    var thishtml = $('html');
    var thisbody = $('body');
    var resizeHandlers = [];

    var page_header  = $('.page-header');
    var page_content = $('.page-content');
    var page_sidebar = $('.page-sidebar');
    var page_footer  = $('.page-footer');
    var page_sidebar_menu = $('.page-sidebar-menu');

    /**
     * 投入string类型的jquery选择器或者dom对象或者jquery对象本身,返回实打实的jquery对象
     * @param selector
     * @returns {jQuery}
     */
    var toJquery = function (selector) {
        if(typeof selector  === 'string'){
            return $(selector);
        }else if(typeof selector === 'object'){
            if(selector instanceof HTMLElement){
                return $(selector);
            }else if(selector instanceof jQuery){
                return selector;
            }else{
                return Dazzling.toast.error("Invalid arguments!");
            }
        }
    };

    var scrollTo = function (el, offeset) {
        var pos = (el && el.size() > 0) ? el.offset().top : 0;
        if (el) {
            if (thisbody.hasClass('page-header-fixed')) {
                pos = pos - $('.page-header'+convention['class_header_fixed'][1]).height();
            } else if (thisbody.hasClass('page-header-top-fixed')) {
                pos = pos - $('.page-header-top').height();
            } else if (thisbody.hasClass('page-header-menu-fixed')) {
                pos = pos - $('.page-header-menu').height();
            }
            pos = pos + (offeset ? offeset : -1 * el.height());
        }
        $('html,body').animate({
            scrollTop: pos
        }, 'slow');
    };

    //布局初始化
    var resBreakpointMd = convention['responsiveBreakpoint']['md'];

    //自动调整最小高度
    var adjustMinHeight = function (selector) {
        selector = toJquery(selector);
        var height;
        var headerHeight = page_header.outerHeight();
        var footerHeight = page_footer.outerHeight();
        var viewport = dazz.context.getViewPort();

        if (viewport.width < resBreakpointMd) {
            height = viewport.height - headerHeight - footerHeight;
        } else {
            height = page_sidebar.height() + 20;
        }

        if ((height + headerHeight + footerHeight) <= viewport.height) {
            height = viewport.height - headerHeight - footerHeight;
        }
        selector.attr('style', 'min-height:' + height + 'px');
    };

    //初始化顶部的查询
    var initHeaderSearchForm = function (handler) {
        if(!handler) handler = alert;
        // handle search box expand/collapse
        page_header.on('click', '.search-form', function () {
            var form_controll = $(this).find('.form-control');
            $(this).addClass("open");
            form_controll.focus();/* 主动聚焦 */
            form_controll.on('blur', function () {/* 失去焦点时自动关闭 */
                $(this).closest('.search-form').removeClass("open");
                $(this).unbind("blur");
            });
        });

        // handle hor menu search form on enter press
        page_header.on('keypress', '.hor-menu .search-form .form-control', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var val = $(this).closest('.search-form').find('.form-control').val();
            if (e.which == 13) {/* 按下回车自动提交 */
                return handler(val);
            }
            return false;
        });

        // handle header search button click
        page_header.on('mousedown', '.search-form.open .submit', function (e) {/* .submit是超链接,需要阻止事件的传播 */
            e.preventDefault();
            e.stopPropagation();
            var val = $(this).closest('.search-form').find('.form-control').val();
            if(typeof handler === 'function') return handler(val);
            return false;
        });
    };

    //sidebar相关的初始化
    var initSidebar = function () {
        //调整内容区高度
        adjustMinHeight(page_content);

        //控制sidebar的菜单
        page_sidebar_menu.on('click', 'li > a.nav-toggle, li > a > span.nav-toggle', function (e) {
            var that = $(this).closest('.nav-item').children('.nav-link');
            var viewport = dazz.context.getViewPort();

            if (viewport.width >= resBreakpointMd && !page_sidebar_menu.attr("data-initialized") && thisbody.hasClass('page-sidebar-closed') &&  that.parent('li').parent('.page-sidebar-menu').size() === 1) {
                return;
            }

            var hasSubMenu = that.next().hasClass('sub-menu');

            if (viewport.width >= resBreakpointMd && that.parents('.page-sidebar-menu-hover-submenu').size() === 1) { // exit of hover sidebar menu
                return;
            }

            if (hasSubMenu === false) {
                if (viewport.width < resBreakpointMd && page_sidebar.hasClass("in")) { // close the menu on mobile view while laoding a page
                    page_header.find('.responsive-toggler').click();
                }
                return;
            }

            var parent =that.parent().parent();
            var the = that;
            var menu = page_sidebar_menu;
            var sub = that.next();

            var autoScroll = menu.data("auto-scroll");
            var slideSpeed = parseInt(menu.data("slide-speed"));
            var keepExpand = menu.data("keep-expanded");

            if (!keepExpand) {
                parent.children('li.open').children('a').children('.arrow').removeClass('open');
                parent.children('li.open').children('.sub-menu').slideUp(slideSpeed);
                parent.children('li.open').removeClass('open');
            }

            var slideOffeset = -200;

            if (sub.is(":visible")) {
                $('.arrow', the).removeClass("open");
                the.parent().removeClass("open");
                sub.slideUp(slideSpeed, function () {
                    if (autoScroll === true && thisbody.hasClass('page-sidebar-closed') === false) {
                        scrollTo(the, slideOffeset);
                    }
                    adjustMinHeight(page_content);
                });
            } else if (hasSubMenu) {
                $('.arrow', the).addClass("open");
                the.parent().addClass("open");
                sub.slideDown(slideSpeed, function () {
                    if (autoScroll === true && thisbody.hasClass('page-sidebar-closed') === false) {
                        scrollTo(the, slideOffeset);
                    }
                    adjustMinHeight(page_content);
                });
            }

            e.preventDefault();
        });

        // 控制sidebar的显示和隐藏
        if ($.cookie && $.cookie('sidebar_closed') === '1' && dazz.context.getViewPort().width >= resBreakpointMd) {
            thisbody.addClass('page-sidebar-closed');
            page_sidebar_menu.addClass('page-sidebar-menu-closed');
        }
        thisbody.on('click', '.sidebar-toggler', function () {
            var sidebar = page_sidebar;
            var sidebarMenu = page_sidebar_menu;

            if (thisbody.hasClass("page-sidebar-closed")) {
                thisbody.removeClass("page-sidebar-closed");
                sidebarMenu.removeClass("page-sidebar-menu-closed");
                if ($.cookie) {
                    $.cookie('sidebar_closed', '0');
                }
            } else {
                thisbody.addClass("page-sidebar-closed");
                sidebarMenu.addClass("page-sidebar-menu-closed");
                if ($.cookie) {
                    $.cookie('sidebar_closed', '1');
                }
            }
            $(window).trigger('resize');
        });

        var autoAdjustPageContainerHeight = function () {
            adjustMinHeight(page_content);
        };
        // handle bootstrah tabs
        thisbody.on('shown.bs.tab', 'a[data-toggle="tab"]', autoAdjustPageContainerHeight);
        resizeHandlers.push(autoAdjustPageContainerHeight);// recalculate sidebar & content height on window resize

    };


    var init = function () {
        var resizehandler;
        var currentHeight;
        var browerinfo = dazz.context.getBrowserInfo();
        var isIE8 = browerinfo.type === 'ie' && 8 === browerinfo.version;
        var isIE9 = browerinfo.type === 'ie' && 9 === browerinfo.version;
        var isIE10 = browerinfo.type === 'ie' && 10 === browerinfo.version;

        isIE8 && thishtml.addClass('ie8 ie'); // detect ie8 version
        isIE9 && thishtml.addClass('ie9 ie'); // detect ie9 version
        isIE10 && thishtml.addClass('ie10 ie'); // detect IE10 version
        if((isIE8 || isIE9) && ('placeholder' in jQuery)) $('input, textarea').placeholder();//该插件存在时候开启placeholder

        $(window).resize(function() {
            if (isIE8 && (currentHeight == document.documentElement.clientHeight)) return; //quite event since only body resized not window.
            if (resizehandler) clearTimeout(resizehandler);
            resizehandler = setTimeout(function() {
                for (var i = 0; i < resizeHandlers.length; i++)  resizeHandlers[i].call();//执行调整函数
            }, 75); // 等待window调整完成
            isIE8 && (currentHeight = document.documentElement.clientHeight); // store last body client height
        });


        //处理Tab切换
        if (location.hash) {
            var tabid = encodeURI(location.hash.substr(1));
            var tabida = $('a[href="#' + tabid + '"]');
            tabida.parents('.tab-pane:hidden').each(function() {
                var tabid = $(this).attr("id");
                $('a[href="#' + tabid + '"]').click();
            });
            tabida.click();
        }

        //dropdown菜单相关
        thisbody.on('click', '.dropdown-menu.hold-on-click', function(e) {e.stopPropagation();});
        $('[data-hover="dropdown"]').not('.hover-initialized').each(function() {
            $(this).dropdownHover();
            $(this).addClass('hover-initialized');
        });

        //初始化布局
        initHeaderSearchForm(alert); // handles horizontal menu
        initSidebar();//初始化sidebar

        //处理足部的Go to top 按钮
        var offset = 300;
        var duration = 500;
        var scrollToTop = $('.scroll-to-top');
        if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {  // ios supported
            $(window).bind("touchend touchcancel touchleave", function(){
                if ($(this).scrollTop() > offset) {
                    scrollToTop.fadeIn(duration);
                } else {
                    scrollToTop.fadeOut(duration);
                }
            });
        } else {  // general
            $(window).scroll(function() {
                if ($(this).scrollTop() > offset) {
                    scrollToTop.fadeIn(duration);
                } else {
                    scrollToTop.fadeOut(duration);
                }
            });
        }
        scrollToTop.click(function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, duration);
            return false;
        });

    };

    /**
     * 习惯性的jquery方法
     * @param url 请求地址
     * @param data 请求数据对象
     * @param callback 服务器响应时的回调,如果回调函数返回false或者无返回值,则允许系统进行通知处理,返回true表示已经处理完毕,无需其他的操作
     * @param datatype 期望返回的数据类型 json xml html script json jsonp text 中的一种
     * @param async 是否异步,希望同步的清空下使用false,默认为true
     * @returns {*}
     */
    var dazzlingPost = function (url, data, callback, datatype, async) {

        var currentMilliTime = (new Date()).valueOf();
        if(!_lastRequestTime){
            _lastRequestTime = currentMilliTime;
        }else{
            var gap = currentMilliTime - _lastRequestTime;
            _lastRequestTime = currentMilliTime;
            if(gap < convention['requestInterval']){
                return Dazzling.toast.warning('请勿频繁刷新!');
            }
        }

        if(undefined === datatype) datatype = "json";
        if(undefined === async) async = true;

        if(typeof data === 'string'){
            data = {
                '_KBYLIN_':data //后台会进行分解
            };
        }

        return jQuery.ajax({
            url: url,
            type: 'post',
            dataType:datatype,
            async: async,
            data: data,
            success:function (data) {
                var message_type;
                var isMsg =  (data instanceof Object) && data.hasOwnProperty('_type') && data.hasOwnProperty('_message');
                //通知处理
                isMsg && (message_type = parseInt(data['_type']));

                if(callback && callback(data,isMsg,message_type)) return true;//如果用户的回调明确声明返回true,表示已经处理得当,无需默认的参与

                if(isMsg){
                    if(message_type > 0){
                        return Dazzling.toast.success(data['_message']);
                    }else if(message_type < 0){
                        return Dazzling.toast.warning(data['_message']);
                    }else{
                        return Dazzling.toast.error(data['_message']);
                    }
                }
            }
        });
    };

    /**
     * @param elements
     * @param infos
     * @param ignores
     */
    var autoFillById = function (elements, infos, ignores) {
        if(!dazz.utils.isArray(ignores)) ignores = [ignores];/* 自动转数组 */

        for(var x in elements){
            if(!elements.hasOwnProperty(x)) continue;
            var id = elements[x];
            if($.inArray(id,ignores) === 0) continue;

            var temp ;
            if(infos.hasOwnProperty(id)){
                if(id.indexOf('.') !== -1){
                    temp = id.split('.');
                    $("#"+temp[0]).attr(temp[1],infos[id]);
                }else{
                    $("#"+id).text(infos[id])
                }
            }
        }
    };


    var initUserInfo = function (userinfo,itemsIds) {
        if(!(userinfo instanceof Object)) userinfo = dazz.utils.toObject(userinfo);
        var usermenu = $("#menu");

        autoFillById(itemsIds,userinfo,['menu']);

        for(var index in userinfo['menu']){
            if(!userinfo['menu'].hasOwnProperty(index)) continue;
            var item = userinfo['menu'][index];

            var li = $(document.createElement('li'));
            var a = $(document.createElement('a'));
            a.text(item['title']);
            li.append(a);

            if(item.hasOwnProperty('href')) item['href'] = '#';
            a.attr('href',item['href']);

            if(item.hasOwnProperty('icon')){
                var i = $(document.createElement('i'));
                i.addClass(item['icon']);
                a.prepend(i);
            }
            usermenu.append(li);
        }
    };

    var HeaderMenuHandler = (function () {
        //default options
        var options = {
            selector:'#dazz_header_menu'
        };
        var headermenu = null;

        //检查是否有孩子树形
        var getChildren = function (element,childrenattrname) {
            if(!childrenattrname) childrenattrname = 'children';
            return (element.hasOwnProperty(childrenattrname) && element[childrenattrname])?element[childrenattrname]:null;
        };

        //获取超链接
        var _createAnchor = function (element) {
            var a = $(document.createElement('a'));
            element.hasOwnProperty('title') || (element.title = 'Untitled');
            a.text(" "+element.title+" ");
            element.hasOwnProperty('href') || (element.href = '#');
            a.attr('href',element.href);
            //有子菜单,设置下拉属性
            getChildren(element) && a.attr('data-toggle','dropdown');
            //设置了图标的情况下创建<i class="XX"></i>
            if(element.hasOwnProperty('icon')){
                $(document.createElement('i')).addClass(element.icon).appendTo(a);
            }
            return a;
        };
        var _createUnorderedLists = function(menuitem){
            //不存在子菜单或者子菜单为空的情况下时直接返回
            var children = getChildren(menuitem);
            if(!children) return;

            var list = $('<ul class="dropdown-menu"></ul>');
            //创建并添加ul
            dazz.utils.each(children,function (child) {
                var li = $(document.createElement('li'));
                li.append(_createAnchor(child,true));
                list.append(li);
                if(getChildren(child)){
                    li.addClass('dropdown-submenu');
                    li.append(_createUnorderedLists(child));
                }
            });
            return list;
        };

        return {
            //初始化
            init:function (config) {
                // console.log(config)
                options = dazz.utils.initOption(options,config,true);
                headermenu = toJquery(options.selector);
                // console.log(headermenu,options,config)
                return this;
            },
            //加载数据
            load:function (data) {
                // console.log(data);
                dazz.utils.each(data,function (menuitem) {
                    var li = $(document.createElement('li'));
                    li.addClass('classic-menu-dropdown');

                    var haschild = getChildren(menuitem)?true:false;

                    if(haschild) menuitem['icon'] = 'icon-angle-down';
                    li.append(_createAnchor(menuitem,false));
                    if(haschild) li.append(_createUnorderedLists(menuitem));

                    headermenu.append(li);
                });
                return this;
            },
            //将第几个设置为激活状态
            active:function (index) {
                var blocks = headermenu.find('.classic-menu-dropdown');
                if(index < blocks.length){
                    blocks.eq(index).addClass('active');
                    return true;
                }
                return false;
            }

        };
    })();

    var initPageInfo = function (pageinfo) {
        if(!(pageinfo instanceof Object)) pageinfo = dazz.utils.toObject(pageinfo);

        var index,menuitem,hasSubmenu;
        //设置标题
        pageinfo.hasOwnProperty('title') && $("title").text(pageinfo['title']);
        pageinfo.hasOwnProperty('logo') && $("#dazz_logo").attr('src',pageinfo['logo']);
        pageinfo.hasOwnProperty('coptright') && $("#dazz_copyright").html(pageinfo['coptright']);

        //处理顶部菜单
        HeaderMenuHandler.init(pageinfo).load(pageinfo['header_menu']['menu_list']).active(pageinfo['active_index']);

        var sidebar_menu = pageinfo['sidebar_menu'];
        var dazz_sidebar_menu = $("#dazz_sidebar_menu");
        var _getAnchor4Sidebar = function (attrs,hasSubmenu) {
            if(undefined === hasSubmenu) hasSubmenu = attrs.hasOwnProperty('submenu');

            var a = $(document.createElement('a')).addClass(hasSubmenu?'nav-link nav-toggle':'nav-link');

            //未传入参数的清空
            if(!attrs.hasOwnProperty('icon')) attrs['icon'] = 'icon-circle-blank';//默认图标

            a.append($('<i class="'+attrs['icon']+'"></i>')).append($('<span class="title"> '+attrs['title']+' </span>'));
            hasSubmenu && a.append($('<i class="float-right icon-angle-right"></i>'));
            return a;
        };
        var _getUnorderedLists4Sidebar = function (menuitem) {
            if(!menuitem.hasOwnProperty('submenu') || !menuitem.submenu) return;//不存在子菜单时直接返回
            var li_ul = $('<ul class="sub-menu"></ul>');

            //创建并添加ul
            for(var x in menuitem.submenu){
                if(!menuitem.submenu.hasOwnProperty(x)) continue;
                //子菜单项
                var subitem =  menuitem.submenu[x];

                var hasSubmenu = subitem.hasOwnProperty('submenu');

                var li_navitem = $(document.createElement('li'));
                li_navitem.addClass('nav-item');
                li_navitem.append(_getAnchor4Sidebar(subitem,hasSubmenu));
                li_ul.append(li_navitem);
                if(hasSubmenu){
                    li_navitem.append(_getUnorderedLists4Sidebar(subitem));
                }
            }
            return li_ul;
        };

        for(index in sidebar_menu['menu_list']){
            if(!sidebar_menu['menu_list'].hasOwnProperty(index)) continue;
            //菜单项
            menuitem = sidebar_menu['menu_list'][index];

            var li_navitem = $(document.createElement('li'));
            li_navitem.addClass('nav-item');

            hasSubmenu = menuitem.hasOwnProperty('submenu');

            var a = _getAnchor4Sidebar(menuitem,hasSubmenu);

            li_navitem.append(a);
            hasSubmenu && li_navitem.append(_getUnorderedLists4Sidebar(menuitem));

            dazz_sidebar_menu.append(li_navitem);
        }

    };
    var setActive = function () {};

    /**
     * 自动填写表单
     * @param form 表单对象或者表单选择器
     * @param data 待设置的数据 如 {'key':'value'}
     * @param map 数据映射 如[],{'data_key':'input_name'}
     */
    var autoFillForm = function (form, data, map) {
        if(typeof form === 'string') form = $(form);
        if(!(form instanceof jQuery))  return Dazzling.toast.error("Parameter 1 expect to be jquery ");
        var target,key;
        var mapDefined = dazz.utils.isObject(map,'Object');

        for (key in data){
            if(!data.hasOwnProperty(key)) continue;

            if(mapDefined && map.hasOwnProperty(key)){
                target = form.find("[name="+map[key]+"]");
            }else{
                target = form.find("[name="+key+"]");
            }

            if(target.length) {/*表单中存在这个name的输入元素*/
                if(target.length > 1){/* 出现了radio或者checkbox的清空 */
                    for(var x in target){
                        if(!target.hasOwnProperty(x)) continue;
                        // if(target[x].type === 'input' )
                        // console.log(target[x],target[x].value,target[x].type);//dom
                        if(('radio' === target[x].type) && parseInt(target[x].value) == parseInt(data[key])){
                            target[x].checked = true;
                        }
                    }
                }else{
                    target.val(data[key]);
                }
            }else{
                form.append($('<input name="'+key+'" value="'+data[key]+'" type="hidden">'));
            }
        }

    };

    var pageTool = {
        //自动调整组件最小高度
        adjustMinHeight:adjustMinHeight,
        'page_action_list':null,
        'init':function (selector) {
            if(undefined === selector) selector = '#dazz_page_action';//默认的选择器
            !this.page_action_list && (this.page_action_list = Dazzling.utils.toJquery(selector));
        },
        //注册操作:操作名称,点击时候的回调函数
        'registerAction':function (actionName, callback,icon) {
            this.init();
            var li = $("<li></li>");
            var a;
            if(icon){
                a = $('<a href="javascript:void(0);" id="la_'+dazz.utils.guid()+'"><i class="'+icon+'"></i> '+actionName+'</a>');
            }else{
                a = $('<a href="javascript:void(0);" id="la_'+dazz.utils.guid()+'"> '+actionName+'</a>');
            }
            this.page_action_list.append(li.append(a));
            a.click(callback);
        }
    };

    var BsModal = {
        /**
         * 创建一个Modal对象,会将HTML中指定的内容作为自己的一部分拐走
         * @param selector 要把哪些东西添加到modal中的选择器
         * @param option modal配置
         * @returns {*}
         */
        'create':function (selector,option) {
            var config = {
                'title':null,
                'confirmText':'提交',
                'cancelText':'取消',
                'fade':true,

                //确认和取消的回调函数
                'confirm':null,
                'cancel':null,

                'show':null,//即将显示
                'shown':null,//显示完毕
                'hide':null,//即将隐藏
                'hidden':null,//隐藏完毕

                'backdrop':'static',
                'keyboard':true
            };
            config = dazz.utils.initOption(config,option);

            var instance = dazz.newInstance(this);
            var id = 'modal_'+dazz.utils.guid();

            var modal = $('<div class="modal" id="'+id+'" aria-hidden="true" role="dialog"></div>');
            if(typeof config['backdrop'] !== "string") config['backdrop'] = config['backdrop']?'true':'false';
            if(config['fade']) modal.addClass('fade');
            modal.attr('data-backdrop',config['backdrop']);
            modal.attr('data-keyboard',config['keyboard']?'true':'false');
            thisbody.append(modal);

            var dialog = $('<div class="modal-dialog"></div>');
            modal.append(dialog);
            var content = $('<div class="modal-content"></div>');
            dialog.append(content);

            //设置title部分
            if(config['title']){
                var header = $('<div class="modal-header"></div>');
                var close = $('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
                header.append(close).append($('<h4 class="modal-title">'+config['title']+'</h4>'));
                content.append(header);
            }

            //设置体部分
            var body = $('<div class="modal-body"></div>');
            body.appendTo(content);
            body.append(toJquery(selector));

            //设置足部
            var cancel = $('<button type="button" class="btn btn-default" data-dismiss="modal">'+config['cancelText']+'</button>');
            var confirm = $('<button type="button" class="btn btn-primary">'+config['confirmText']+'</button>');
            content.append($('<div class="modal-footer"></div>').append(cancel).append(confirm));

            //确认和取消事件注册
            confirm.click(function (e) {
                config['confirm'] && (config['confirm'])(e);
            });
            cancel.click(function (e) {
                config['cancel'] && (config['cancel'])(e);
            });
            instance.target = modal.modal('hide');

            //事件注册
            dazz.utils.each(['show','shown','hide','hidden'],function (eventname) {
                modal.on(eventname+'.bs.modal', function () {
                    // console.log(eventname,config[eventname]);
                    config[eventname] && (config[eventname])();
                });
            });

            return instance;
        },
        'show':function () {
            // console.log(this.target)
            return this.target.modal('show');
        },
        'hide':function () {
            return this.target.modal('hide');
        }
    };

    var Jnestable = {
        /**
         * 创建列表
         * @param group 分组,如果未设置将自动创建一个GUID
         * @return this
         */
        create : function (group) {
            var instance = dazz.newInstance(this);

            var id = 'nestable_'+dazz.utils.guid();
            var dd = $('<div class="dd" id="'+id+'"></div>');

            instance.target = this._getJNestable().nestable({group: group?group:id});
            return instance;
        },
        _getJNestable:function () {
            return $('<div class="dd" id="nestable_'+dazz.utils.guid()+'"></div>');
        },
        /**
         * 返回加载过数据后的div
         * @param data
         * @param callback {callback}数据附加目的地(nestable div对象)
         * @returns {jQuery}
         */
        load:function (data,callback){
            callback || (callback = null);//显示声明为空
            this.createItemList(data,this.target,callback);
            return this.target;
        },
        //创建OL节点,children为子元素数组,target为创建的列表附加的目标(目标缺失时选用this.target,即dd)
        createItemList : function (data,target,callback) {
            data = dazz.utils.toObject(data);
            var env = this;
            var ol = $('<ol class="dd-list"></ol>');
            dazz.utils.each(data,function (item) {
                env.createItem(item,ol,callback);
            });

            //寻找附加target
            if(!(target = target?target:this.target)) return Dazzling.toast.warning('Nestable require a target to attach!');

            //如果target本身是ol节点,将不符合规则(ol下只能存在li,li下能存在ol)
            // console.log(target.get(0).tagName);
            switch (target.get(0).tagName.toUpperCase()){
                case 'DIV':
                case 'LI':
                    //设置ol
                    var targetol = target.children('ol');
                    if(targetol.length) targetol.remove();//深处原来的ol
                    // console.log(target);
                    target.append(ol);
                    break;
                case 'OL':
                default:
                    throw "无法在该元素上创建列表";
            }
            return ol;
        },
        createItem : function (data,target,callback) {
            data = dazz.utils.toObject(data);

            //设置基本的两个属性
            if(dazz.utils.checkProperty(data,['id','title']) < 1) return Dazzling.toast.warning("The id/title of item should not be empty");
            var linode = $('<li class="dd-item dd3-item"></li>').append($('<div class="dd-handle dd3-handle">')).append($('<div class="dd3-content">'+data['title']+'</div>'));

            //设置其他附加属性(title id除外)
            dazz.utils.each(data,function (value,key) {
                if( key === 'children' ) return ;
                switch (typeof value){
                    case 'string':
                    case 'number':
                        linode.attr("data-"+key,value);
                        break;
                    case 'boolean':
                        linode.attr("data-"+key,value?'true':'false');
                        break;
                    default:
                }
            });

            //如果存在子元素的情况下创建
            dazz.utils.checkProperty(data,'children') && this.createItemList(data['children'],linode,callback);

            // console.log(this.target)
            //设置attach目标
            if(!target) target = this.target;
            if(!target) return Dazzling.toast.warning('Nestable require a target to attach!');

            // console.log(target.get(0).tagName);
            var tagname = target.get(0).tagName.toUpperCase();
            // console.log(target)
            switch (tagname){
                case 'DIV'://直接点击添加时候
                case 'LI':
                    //设置ol
                    var targetol = target.children('ol');
                    if(!targetol.length){
                        //不存在ol链表时创建
                        this.createItemList([],target);
                        targetol = target.children('ol');
                    }
                    targetol.prepend(linode);
                    break;
                case 'OL':
                    target.append(linode);
                    break;
                default:
                    throw "无法在该元素上创建列表:"+tagname;
            }
            // console.log(data)
            callback && callback(data,linode);//每次遍历一项回调
            return linode;
        },
        //获得序列化的值,可以是对象或者数组
        serialize:function (tostring) {
            var value = this.target.nestable('serialize');
            if(tostring){
                if(!JSON) return Dazzling.toast.warning('你的浏览器不支持JSON对象!');
                value = JSON.stringify(value);
            }
            return value;
        },
        attachTo:function (selector,append) {
            selector = toJquery(selector);
            if(append){
                selector.append(this.target);
            }else{
                selector.prepend(this.target);
            }
            return this;
        },
        prependTo :function (attatchment) {
            attatchment = toJquery(attatchment);
            attatchment.html('');
            if(attatchment.length) {
                attatchment.prepend(this.target);
                return true;
            }
            return false;
        },
        appendTo :function (attatchment) {
            attatchment = toJquery(attatchment);
            attatchment.html('');
            if(attatchment.length) {
                attatchment.appendTo(this.target);
                return true;
            }
            return false;
        }
    };

    return {
        //初始化应用
        'init':init,
        //定制的方法,定制过程中避免对jquery中的方法进行修改
        'post':dazzlingPost,
        //初始化用户信息
        'initUserInfo':initUserInfo,
        //初始化页面信息
        'initPageInfo':initPageInfo,
        //为活跃的菜单项目添加active类
        'setActive':setActive,
        //工具箱
        'utils':{
            //投入string类型的jquery选择器或者dom对象或者jquery对象本身,返回实打实的jquery对象
            'toJquery':toJquery
        },
        //页面工具
        'page':pageTool,
        //datatable表格工具,一次只能操作一个表格API对象
        //datatable.find("tbody").on('dblclick','tr',function () {});//可以设置为双击编辑
        //改造成return new this;
        datatables: {
            'tableApi':null,//datatable的API对象
            'dtJquery':null, // datatable的jquery对象
            'current_row':null,//当前操作的行,可能是一群行
            //设置之后的操作所指定的DatatableAPI对象
            'bind':function (dtJquery,options) {
                dtJquery = Dazzling.utils.toJquery(dtJquery);
                var newinstance = dazz.newInstance(this);
                newinstance.dtJquery = dtJquery;
                newinstance.tableApi = dtJquery.DataTable(options);
                return newinstance;/* this 对象同于链式调用 */
            },
            //为tableapi对象加载数据,参数二用于清空之前的数据
            'load':function (data,clear) {
                if(!this.tableApi) return Dazzling.toast.error("No Datatable API binded!");
                if(undefined === clear || clear) this.tableApi.clear();//clear为true或者未设置时候都会清除之前的表格内容
                this.tableApi.rows.add(data).draw();
                return this;
            },
            //表格发生了draw事件时设置调用函数(表格加载,翻页都会发生draw事件)
            'onDraw':function (callback) {
                if(!this.dtJquery) return Dazzling.toast.error("No Datatables binded!");
                this.dtJquery.on( 'draw.dt', function (event,settings) {
                    callback(event,settings);
                    // console.log( 'Redraw occurred at: '+new Date().getTime() );
                } );
                return this;
            },
            //获取表格指定行的数据
            'data':function (element) {
                this.current_row = element;
                return this.tableApi.row(element).data();
            },
            'update':function (newdata,line){
                (line === undefined) && (line = this.current_row);
                if(line){
                    if(dazz.utils.isArray(line)){
                        for (var i = 0; i < line.length ; i ++){
                            this.update(newdata,line[i]);
                        }
                    }else{
                        //注意:如果出现这样的错误"DataTables warning: table id=[dtable 实际的表的ID] - Requested unknown parameter ‘acceptId’ for row X 第几行出现了错误 "
                        return this.tableApi.row(line).data(newdata).draw(false);
                    }
                }
            }
        },
        //页面的Toast工具,toast对象直接属于window对象
        toast:{
            'init':function () {
                toastr.options.closeButton = true;
                toastr.options.newestOnTop = true;
            },
            'success':function (msg,title) {
                this.init();
                window.toastr.success(msg,title);
            },
            'warning':function (msg,title) {
                this.init();
                toastr.warning(msg,title);
            },
            'error':function (msg,title) {
                this.init();
                toastr.error(msg,title);
            },
            'info':function (msg,title) {
                this.init();
                toastr.info(msg,title);
            },
            'clear':function () {
                toastr.clear();
            }
        },
        //上下文菜单工具
        contextmenu: {
            //Uncaught TypeError: Cannot read property 'left' of undefined
            //while the target menu do not exist,it will throw the error
            /**
             * target的格式:
             * [
             * //对象可以声明角标
             {
                 'index':'edit',
                 'title':'Edit'
             },
             //数组按照顺序填写tabindex和title
             //不同对象之间以hr划分
             *
             * @param selector 上下文菜单依附的元素的选择器,也可以是jquery对象
             * @param menus 菜单设置设置
             * @param handler 菜单点击时的处理函数
             * @param onItem
             * @param before
             */
            'create': function (menus,handler,onItem,before) {
                var instance = dazz.newInstance(this);

                var id = 'cm_'+dazz.utils.guid();
                var contextmenu = $(document.createElement('div'));
                contextmenu.attr('id',id);
                var ul = $(document.createElement('ul'));
                ul.addClass('dropdown-menu');
                ul.attr('role',"menu");
                contextmenu.append(ul);

                var flag = false;
                //菜单项
                dazz.utils.each(menus,function (group) {
                    flag && ul.append($('<li class="divider"></li>'));//对象之间划割
                    dazz.utils.each(group,function (value, key) {
                        ul.append('<li><a tabindex="'+key+'">'+value+'</a></li>');
                    });
                    flag = true;
                });
                $("body").prepend(contextmenu);

                before || (before = function (e,c) {});
                onItem || (onItem = function (c,e) {});
                handler || (handler = function (element,tabindex,title) {});

                //这里的target的上下文意思是 公共配置组
                instance.target = {
                    target:'#'+id,
                    // execute on menu item selection
                    onItem: function (context,event) {
                        onItem(context,event);
                        var target  = event.target;
                        handler(context,target.getAttribute('tabindex'),target.innerText);
                    },
                    // execute code before context menu if shown
                    before: before
                };
                return instance;
            },
            bind :function (selector) {
                selector = toJquery(selector);
                selector.contextmenu(this.target);
            }
        },
        //拟态框
        modal:BsModal,
        form:{
            //自动填写表单
            'autoFill':autoFillForm,
            //表单中的所有值序列化
            'serialize':function(selector){
                selector = toJquery(selector);
                return selector.serialize();
            }
        },
        nestable: Jnestable,
        tab:{
            _createNav:function (config) {
                var id = dazz.utils.guid();
                var nav = $('<ul id="'+id+'" class="nav nav-tabs"></ul>');
                var isfirst = true;
                var node,ul;
                for(var x = 0; x < config.length; x ++){

                    var item = config[x];

                    if(!item.hasOwnProperty('title')) return Dazzling.toast.warning('Tab require a title!');


                    if(item.hasOwnProperty('children')){/*下拉*/
                        var guid = dazz.utils.guid();
                        var children = item['children'];
                        node = $(document.createElement('li'));
                        node.append($('<a href="javascript:void(0);" id="'+guid+'" class="dropdown-toggle" data-toggle="dropdown">'+item['title']+'<b class="caret"></b></a>'));
                        ul = $('<ul class="dropdown-menu" role="menu" aria-labelledby="'+guid+'"></ul>');
                        for(var y in children){
                            if(!children.hasOwnProperty(y)) continue;
                            ul.append(this._createNode(children[y]));
                        }
                        node.append(ul);
                    }else{
                        if(!item.hasOwnProperty('id')) return Dazzling.toast.warning('Tab must be related with an ID!');
                        node = $('<li><a href="#'+item['id']+'" data-toggle="tab">'+item['title']+'</a></li>');
                    }

                    if(isfirst){/* 激活第一个 */
                        node.addClass('active');
                        isfirst = false;
                    }

                    nav.append(node);
                }
                return nav;
            },
            _createNode:function (node) {
                if(!node.hasOwnProperty('title')) return Dazzling.toast.warning('Tab require a title!');
                if(!node.hasOwnProperty('id')) return Dazzling.toast.warning('Tab must be related with an ID!');
                return $('<li><a href="#'+node['id']+'" data-toggle="tab">'+node['title']+'</a></li>');
            },
            _createContent:function (config) {
                var content = $('<div id="'+dazz.utils.guid()+'" class="tab-content"></div>');
                for(var x = 0; x < config.length; x ++) {
                    if(config[x].hasOwnProperty('children')){/*下拉*/
                        for(var y in config[x]['children']){
                            if(!config[x]['children'].hasOwnProperty(y)) continue;
                            content.append(this._createNode4Content(config[x]['children'][y]));
                        }
                    }else{
                        content.append(this._createNode4Content(config[x]));
                    }
                }
                return content;
            },
            _createNode4Content:function (config) {
                if(!config.hasOwnProperty('id') || !config.hasOwnProperty('content') ) return Dazzling.toast.warning('Tab must be related with an ID!');
                var div = $('<div class="tab-pane fade" id="'+config['id']+'"></div>');
                var content = toJquery(config['content']);
                div.append(content);
                return div;
            },
            create:function (config,attachment) {
                var nav = this._createNav(config);
                var content = this._createContent(config);
                if(attachment){
                    attachment = toJquery(attachment);
                    attachment.html('');
                    attachment.append(nav).append(content);
                }
                return [nav,content];
            }
        }
    };
})();



