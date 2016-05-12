/**
 * Dazzling 粲
 * 框架高级执行工具
 * @type object
 */
var Dazzling = (function () {

    // for(var x in config) if(x in convention) convention[x] = config[x];
    if(!jQuery ) return Dazzling.toast.error("Require Jquery!");

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
    var getResponsiveBreakpoint = function(size) {
        var sizes = {
            'xs' : 480,     // extra small
            'sm' : 768,     // small
            'md' : 992,     // medium
            'lg' : 1200     // large
        };
        return sizes[size] ? sizes[size] : 0;
    };
    //布局初始化
    var resBreakpointMd = getResponsiveBreakpoint('md');

    //自动调整page-container的高度(包括sidebar和content)
    var autoAdjustPageContainerHeight = function () {
        var height;
        var headerHeight = page_header.outerHeight();
        var footerHeight = page_footer.outerHeight();

        if (Kbylin.getViewPort().width < resBreakpointMd) {
            height = Kbylin.getViewPort().height - headerHeight - footerHeight;
        } else {
            height = page_sidebar.height() + 20;
        }

        if ((height + headerHeight + footerHeight) <= Kbylin.getViewPort().height) {
            height = Kbylin.getViewPort().height - headerHeight - footerHeight;
        }
        page_content.attr('style', 'min-height:' + height + 'px');
    };

    // Handle sidebar menu
    var handleSidebarMenu = function () {
        // handle sidebar link click
        page_sidebar_menu.on('click', 'li > a.nav-toggle, li > a > span.nav-toggle', function (e) {
            var that = $(this).closest('.nav-item').children('.nav-link');

            if (Kbylin.getViewPort().width >= resBreakpointMd && !page_sidebar_menu.attr("data-initialized") && thisbody.hasClass('page-sidebar-closed') &&  that.parent('li').parent('.page-sidebar-menu').size() === 1) {
                return;
            }

            var hasSubMenu = that.next().hasClass('sub-menu');

            if (Kbylin.getViewPort().width >= resBreakpointMd && that.parents('.page-sidebar-menu-hover-submenu').size() === 1) { // exit of hover sidebar menu
                return;
            }

            if (hasSubMenu === false) {
                if (Kbylin.getViewPort().width < resBreakpointMd && page_sidebar.hasClass("in")) { // close the menu on mobile view while laoding a page
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
                    autoAdjustPageContainerHeight();
                });
            } else if (hasSubMenu) {
                $('.arrow', the).addClass("open");
                the.parent().addClass("open");
                sub.slideDown(slideSpeed, function () {
                    if (autoScroll === true && thisbody.hasClass('page-sidebar-closed') === false) {
                        scrollTo(the, slideOffeset);
                    }
                    autoAdjustPageContainerHeight();
                });
            }

            e.preventDefault();
        });


    };

    // Hanles sidebar toggler
    var handleSidebarToggler = function () {
        if ($.cookie && $.cookie('sidebar_closed') === '1' && Kbylin.getViewPort().width >= resBreakpointMd) {
            thisbody.addClass('page-sidebar-closed');
            page_sidebar_menu.addClass('page-sidebar-menu-closed');
        }
        // handle sidebar show/hide
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
        autoAdjustPageContainerHeight();
        handleSidebarMenu(); // handles main menu
        handleSidebarToggler(); // handles sidebar hide/show

        // handle bootstrah tabs
        thisbody.on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
            autoAdjustPageContainerHeight();
        });
        resizeHandlers.push(autoAdjustPageContainerHeight);// recalculate sidebar & content height on window resize

    };
    //返回顶部
    var initGotoTop = function () {
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
        }
        else {  // general
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
    //兼容性处理 与 加强
    var handleCompatibility = function () {
        //添加placeholder支持,处理不支持placeholder的浏览器,这里将不支持IE8以下的浏览器,故只有IE9和IE10
        var browerinfo = Kbylin.getBrowserInfo();
        var isIE8 = browerinfo.type === 'ie' && 8 === browerinfo.version;
        var isIE9 = browerinfo.type === 'ie' && 9 === browerinfo.version;
        if(isIE8 || isIE9) $('input, textarea').placeholder();
    };
    //初始化应用
    var initApplication = function () {
        var resize;
        var currheight;
        var browerinfo = Kbylin.getBrowserInfo();
        var isIE8 = browerinfo.type === 'ie' && 8 === browerinfo.version;
        var isIE9 = browerinfo.type === 'ie' && 9 === browerinfo.version;
        var isIE10 = browerinfo.type === 'ie' && 10 === browerinfo.version;

        isIE8 && thishtml.addClass('ie8 ie'); // detect ie8 version
        isIE9 && thishtml.addClass('ie9 ie'); // detect ie9 version
        isIE10 && thishtml.addClass('ie10 ie'); // detect IE10 version

        $(window).resize(function() {
            if (isIE8 && (currheight == document.documentElement.clientHeight)) return; //quite event since only body resized not window.
            if (resize) clearTimeout(resize);
            resize = setTimeout(function() {
                for (var i = 0; i < resizeHandlers.length; i++)  resizeHandlers[i].call();//执行调整函数
            }, 50); // wait 50ms until window resize finishes.
            isIE8 && (currheight = document.documentElement.clientHeight); // store last body client height
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
    };

    /**
     * 获取超链接
     * @param attrs
     * @param iconAhead
     * @returns {*|jQuery|HTMLElement}
     */
    var _getAnchor4Header = function (attrs,iconAhead) {
        var a = $(document.createElement('a'));
        attrs.hasOwnProperty('title') && a.text(" "+attrs.title+" ");
        attrs.hasOwnProperty('href')  && a.attr('href',attrs.href);
        attrs.hasOwnProperty('submenu')  && a.attr('data-toggle','dropdown');

        if(attrs.hasOwnProperty('icon')){
            var i = $(document.createElement('i'));
            i.addClass(attrs.icon);
            iconAhead?i.prependTo(a):i.appendTo(a);
        }
        return a;
    };

    var _getAnchor4Sidebar = function (attrs,hasSubmenu) {
        if(undefined === hasSubmenu) hasSubmenu = attrs.hasOwnProperty('submenu');

        var a = $(document.createElement('a')).addClass(hasSubmenu?'nav-link nav-toggle':'nav-link');

        //未传入参数的清空
        if(!attrs.hasOwnProperty('icon')) attrs['icon'] = 'icon-circle-blank';//默认图标

        a.append($('<i class="'+attrs['icon']+'"></i>')).append($('<span class="title"> '+attrs['title']+' </span>'));
        hasSubmenu && a.append($('<i class="float-right icon-angle-right"></i>'));
        return a;
    };

    /**
     * 获取ul列表
     * @param menuitem
     */
    var _getUnorderedLists4Header = function(menuitem){
        if(!menuitem.hasOwnProperty('submenu') || !menuitem.submenu) return;//不存在子菜单时直接返回
        var li_ul = $('<ul class="dropdown-menu"></ul>');
        //创建并添加ul
        for(var x in menuitem.submenu){
            if(!menuitem.submenu.hasOwnProperty(x)) continue;
            //子菜单项
            var subitem =  menuitem.submenu[x];

            var li = $(document.createElement('li'));
            li.append(_getAnchor4Header(subitem,true));
            li_ul.append(li);
            if(subitem.hasOwnProperty('submenu')){
                li.addClass('dropdown-submenu');
                li.append(_getUnorderedLists4Header(subitem));
            }
        }
        return li_ul;
    };

    /**
     * @param menuitem
     * @private
     */
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

    /**
     * 初始化头部的菜单
     */
    var initHeaderMenu = function (header_menu) {
        var dazz_headermenu = $("#dazz_header_menu");

        var active_index = parseInt(header_menu['active_index']);

        for(var index in header_menu['menu_list']){
            if(!header_menu['menu_list'].hasOwnProperty(index)) continue;
            //菜单项
            var menuitem = header_menu['menu_list'][index];

            var li = $(document.createElement('li'));
            li.addClass(parseInt(index) === active_index?'active classic-menu-dropdown':'classic-menu-dropdown');

            var hasSubmenu = menuitem.hasOwnProperty('submenu');

            if(hasSubmenu) menuitem['icon'] = ' icon-angle-down';
            li.append(_getAnchor4Header(menuitem,false));
            if(hasSubmenu) li.append(_getUnorderedLists4Header(menuitem));

            dazz_headermenu.append(li);
        }
    };

    var initSidebarMenu = function (sidebar_menu) {
        var dazz_sidebar_menu = $("#dazz_sidebar_menu");

        for(var index in sidebar_menu['menu_list']){
            if(!sidebar_menu['menu_list'].hasOwnProperty(index)) continue;
            //菜单项
            var menuitem = sidebar_menu['menu_list'][index];

            var li_navitem = $(document.createElement('li'));
            li_navitem.addClass('nav-item');

            var hasSubmenu = menuitem.hasOwnProperty('submenu');

            var a = _getAnchor4Sidebar(menuitem,hasSubmenu);

            li_navitem.append(a);
            hasSubmenu && li_navitem.append(_getUnorderedLists4Sidebar(menuitem));

            dazz_sidebar_menu.append(li_navitem);
        }
    };


    var lastRequestTime = null;

    var convention = {
        //post刷新间隔
        'requestExpireTime':400
    };

    var init = function () {
        handleCompatibility();//处理常见的兼容性问题

        initApplication();//初始化应用

        //初始化布局
        initHeaderSearchForm(alert); // handles horizontal menu
        initSidebar();//初始化sidebar
        initGotoTop();//处理足部的Go to top 按钮

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
        if(!lastRequestTime){
            lastRequestTime = currentMilliTime;
        }else{
            var gap = currentMilliTime - lastRequestTime;
            lastRequestTime = currentMilliTime;
            if(gap < convention['requestExpireTime']){
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
                var message_type = 1;
                var isMsg =  (data instanceof Object) && data.hasOwnProperty('_type') && data.hasOwnProperty('_message');
                //通知处理
                isMsg && (message_type = parseInt(data['_type']));

                if(callback(data,isMsg,message_type)) return true;//如果用户的回调明确声明返回true,表示已经处理得当,无需默认的参与

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
     * ??
     * @param elements
     * @param infos
     * @param ignores
     */
    var autoFillById = function (elements, infos, ignores) {
        if(!Kbylin.isArray(ignores)) ignores = [ignores];/* 自动转数组 */


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
        if(!(userinfo instanceof Object)) userinfo = Kbylin.str2Obj(userinfo);
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

    var initPageInfo = function (pageinfo) {
        if(!(pageinfo instanceof Object)) pageinfo = Kbylin.str2Obj(pageinfo);

        //设置标题
        pageinfo.hasOwnProperty('title') && $("title").text(pageinfo['title']);
        pageinfo.hasOwnProperty('logo') && $("#dazz_logo").attr('src',pageinfo['logo']);
        pageinfo.hasOwnProperty('coptright') && $("#dazz_copyright").html(pageinfo['coptright']);

        initHeaderMenu(pageinfo['header_menu']);
        initSidebarMenu(pageinfo['sidebar_menu']);
    };
    var setActive = function () {};
    /**
     * 随机获取一个GUID
     * @returns {string}
     */
    var guid = function() {
        var s = [];
        var hexDigits = "0123456789abcdef";
        for (var i = 0; i < 36; i++) {
            s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
        }
        s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
        s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
        s[8] = s[13] = s[18] = s[23] = "-";

        return s.join("");
    };

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
     ]
     */
    var createContextmenu = function (selector,target,handler,onItem,before) {
        var instance = toJquery(selector);

        var id = ("cm_"+Dazzling.utils.guid());

        var contextmenu = $("<div id='"+id+"'></div>");
        var ul = $('<ul class="dropdown-menu" role="">');
        contextmenu.append(ul);
        for(var index in target){
            if(!target.hasOwnProperty(index)) continue;

            var group = target[index];
            for(var i in group){
                if(!group.hasOwnProperty(i)) continue;
                var tabindex = i;
                var title = group[i];
                ul.append('<li><a tabindex="'+tabindex+'">'+title+'</a></li>');
            }

            ul.append($('<li class="divider"></li>'));//对象之间划割
        }
        thisbody.prepend(contextmenu);

        before || (before = function (e,c) {
        });
        onItem || (onItem = function (c,e) {
        });

        handler || (handler = function (element,tabindex,title) {});

        return instance.contextmenu({
            target:'#'+id,
            // execute on menu item selection
            onItem: function (context,event) {
                onItem(context,event);
                var target  = event.target;
                handler(context,target.getAttribute('tabindex'),target.innerText);
            },
            // execute code before context menu if shown
            before: before
        });
    };

    /**
     *
     * 注意:(弃用)
     * ①第一次创建时会把选择器中的元素'拐走',所以要记得保存热modal对象
     var remodal = null;
     ...
     if(! remodal) remodal = Dazzling.modal.create('#fortest',{});
     remodal.open();

     * @param modalBodySelector
     * @param option
     */
    var createModal = function (modalBodySelector,option) {
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

        //初始化
        for(var x in option){
            if(!option.hasOwnProperty(x)) continue;
            config[x] = option[x];
        }

        var id = 'modal_'+Dazzling.utils.guid();

        var modal = $('<div class="modal" id="'+id+'" aria-hidden="true"></div>');
        if(typeof config['backdrop'] !== "string"){
            config['backdrop'] = config['backdrop']?'true':'false';
        }
        if(config['fade']) modal.addClass('fade');
        modal.attr('data-backdrop',config['backdrop']);
        modal.attr('data-keyboard',config['keyboard']?'true':'false');

        var dialog = $('<div class="modal-dialog"></div>');
        modal.append(dialog);

        var content = $('<div class="modal-content"></div>');
        dialog.append(content);

        if(config['title']){
            var header = $('<div class="modal-header"></div>');
            var close = $('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
            header.append(close).append($('<h4 class="modal-title">'+config['title']+'</h4>'));
            content.append(header);
        }

        var body = $('<div class="modal-body"></div>');
        body.appendTo(content);
        body.append(toJquery(modalBodySelector));


        var footer = $('<div class="modal-footer"></div>');
        var cancel = $('<button type="button" class="btn btn-default" data-dismiss="modal">'+config['cancelText']+'</button>');
        var confirm = $('<button type="button" class="btn btn-primary">'+config['confirmText']+'</button>');
        content.append(footer.append(cancel).append(confirm));

        //确认和取消事件注册
        confirm.click(function (e) {
            if(config['confirm']){
                config['confirm'](e);
            }
        });
        cancel.click(function (e) {
            if(config['cancel']){
                config['cancel'](e);
            }
        });
        //事件注册

        thisbody.append(modal);

        var instance = $("#"+id);

        var events = ['show','shown','hide','hidden'];
        for(var i =0; i < events.length; i++){
            var eventname = events[i];
            config[eventname] && instance.on(eventname+'.bs.modal', function () {
            });
        }

        return instance.modal('hide');
    };

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
        var mapDefined = Kbylin.isObject(map,'Object');

        for (key in data){
            if(!data.hasOwnProperty(key)) continue;

            if(mapDefined && map.hasOwnProperty(key)){
                target = form.find("[name="+map[key]+"]");
            }else{
                target = form.find("[name="+key+"]");
            }

            if(target.length) {/*表单中存在这个name的输入元素*/
                target.val(data[key]);
            }else{
                form.append($('<input name="'+key+'" value="'+data[key]+'" type="hidden">'));
            }
        }

    };

    var pageTool = {
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
                a = $('<a href="javascript:void(0);" id="la_'+Dazzling.utils.guid()+'"><i class="'+icon+'"></i> '+actionName+'</a>');
            }else{
                a = $('<a href="javascript:void(0);" id="la_'+Dazzling.utils.guid()+'"> '+actionName+'</a>');
            }
            this.page_action_list.append(li.append(a));
            a.click(callback);
        }
    };

    var nestableTool = {
        //穿件OL节点
        '_createOl' : function (children) {
            var ol = $('<ol class="dd-list"></ol>');

            for(var index in children){
                if(!children.hasOwnProperty(index)) continue;
                var item = children[index];

                //设置基本的两个属性
                if(!item.hasOwnProperty('id') || !item.hasOwnProperty('title')) return Dazzling.toast.error("The id/title of item should not be empty");;
                var li = $('<li class="dd-item dd3-item" data-id="'+item['id']+'"></li>').append($('<div class="dd-handle dd3-handle">'));
                var content = $('<div class="dd3-content">'+item['title']+'</div>');
                li.append(content);

                //设置其他附加属性(title id除外)
                for(var x in item){
                    if(!item.hasOwnProperty(x)) continue;
                    (x !== 'title') && (x !== 'id') && li.attr("data-"+x,item[x]);
                }

                children.hasOwnProperty('children') && li.append(this._createOl(item['children']));
                ol.append(li);
            }
            return ol;
        },
        /**
         * 创建并添加到指定元素下
         * @param serialization 配置序列或者配置对象 (必须)
         * @param group 分组
         * @param attatchment 创建并添加的对象,如果指定了ID将添加到指定的对象上并返回nestable对象;否则返回创建的jquery对象
         */
        'create':function (serialization,group,attatchment) {
            serialization = Kbylin.str2Obj(serialization);

            var id = 'nestable_'+guid();
            var div = $('<div class="dd" id="'+id+'"></div>');
            div.append(this._createOl(serialization));

            if(undefined === group) group = id;
            div.nestable({ group: group});

            if(undefined !== attatchment) {
                attatchment = toJquery(attatchment);
                attatchment.html('');
                if(attatchment.length) {
                    // console.log(attatchment,div);
                    attatchment.append(div);
                }
            }
            return div;
        }
    };

    var breadcrumb = {
        'createItem':function (title,href) {
            var li = $(document.createElement("li"));
            var a = $(document.createElement("a"));
            href && a.attr('href',href);
            return li.append(a);
        },
        'create':function (list,attatchment) {
            attatchment = toJquery(attatchment);
            if(attatchment.length){
                for(var x in list){
                    if(!list.hasOwnProperty(x)) continue;
                    attatchment.append(this.createItem(list[x]['title']));
                }
            }
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
            //随机获取一个GUID
            'guid':guid,
            //投入string类型的jquery选择器或者dom对象或者jquery对象本身,返回实打实的jquery对象
            'toJquery':toJquery
        },
        //页面工具
        'page':pageTool,
        //datatable表格工具,一次只能操作一个表格API对象
        //datatable.find("tbody").on('dblclick','tr',function () {});//可以设置为双击编辑
        //改造成return new this;
        'datatables': {
            'tableApi':null,//datatable的API对象
            'dtJquery':null, // datatable的jquery对象
            'current_row':null,//当前操作的行,可能是一群行
            //设置之后的操作所指定的DatatableAPI对象
            'bind':function (dtJquery,options) {
                dtJquery = Dazzling.utils.toJquery(dtJquery);
                var newinstance = new Dazzling.datatables;
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
                    if(Kbylin.isArray(line)){
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
        'toast':{
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
            'clear':function () {
                toastr.clear();
            }
        },
        //上下文菜单工具
        'contextmenu': {
            //创建上下文菜单
            'create': createContextmenu
        },
        'modal':{
            //创建一个Modal对象,会将HTML中指定的内容作为自己的一部分拐走
            'create':createModal,
            //检查是否是有效的Remodal对象,简单地判断
            '_check':function (modal) {
                if(!(modal instanceof Object)) return Dazzling.toast.error("Require Remodal Object!");
            },
            'show':function (modal) {
                this._check(modal);
                return modal.modal('show');
            },
            'hide':function (modal) {
                this._check(modal);
                return modal.modal('hide');
            }
        },
        'form':{
            //自动填写表单
            'autoFill':autoFillForm,
            //表单中的所有值序列化
            'serialize':function(selector){
                selector = toJquery(selector);
                return selector.serialize();
            }
        },
        'nestable':nestableTool
    };
})();



