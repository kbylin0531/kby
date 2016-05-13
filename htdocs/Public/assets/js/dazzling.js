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


    var adjustMinHeight = function (selector) {
        selector = toJquery(selector);
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
        selector.attr('style', 'min-height:' + height + 'px');
    };

    //自动调整page-container的高度(包括sidebar和content)
    var autoAdjustPageContainerHeight = function () {
        adjustMinHeight(page_content);
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
                if(target.length > 1){/* 出现了radio或者checkbox的清空 */
                    for(var x in target){
                        if(!target.hasOwnProperty(x)) continue;
                        // if(target[x].type === 'input' )
                        console.log(target[x],target[x].value,target[x].type);//dom
                        if(('radio' === target[x].type) && Kbylin.isEqual(target[x].value,data[key])){
                            target[x].checked = true;
                        }else{
                            target[x].checked = false;
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
            'toJquery':toJquery,
            //自动调整组件最小高度
            adjustMinHeight:adjustMinHeight,
            //获取一个单例的操作对象作为上下文环境的深度拷贝
            newInstance:function (context) {
                var Yan = function () {return {target:null};};
                var instance = new Yan();
                if(context){
                    for(var x in context){
                        if(context.hasOwnProperty(x)) {
                            instance[x] = context[x];
                        }
                    }
                }
                return instance;
            }
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
                var newinstance = Dazzling.utils.newInstance(this);
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
            'info':function (msg,title) {
                this.init();
                toastr.info(msg,title);
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

                //初始化
                for(var x in option){
                    if(!option.hasOwnProperty(x)) continue;
                    config[x] = option[x];
                }

                var id = 'modal_'+Dazzling.utils.guid();

                var modal = $('<div class="modal" id="'+id+'" aria-hidden="true" role="dialog"></div>');
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
                body.append(toJquery(selector));


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
            },
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
        'nestable':{
            //创建OL节点,children为子元素数组,target为附加的目标(目标缺失时选用自身)
            createItemList : function (itemlist,target) {
                itemlist = Kbylin.str2Obj(itemlist);
                var ol = $('<ol class="dd-list"></ol>');
                for(var index in itemlist){
                    if(!itemlist.hasOwnProperty(index)) continue;
                    this.createItem(itemlist[index],ol);
                }
                if(!target || !target.length) target = this.target;
                if(!target) return Dazzling.toast.warning('Nestable require a target to attach!');

                //如果已经存在该节点,删除它
                var targetol = target.children('ol');
                if(targetol.length) targetol.remove();

                target.append(ol);
                return this;
            },
            serialize:function (tostring) {
                var value = this.target.nestable('serialize');
                if(tostring){
                    if(!JSON) return Dazzling.toast.warning('你的浏览器无法支持JSON功能!');
                    value = JSON.stringify(value);
                }
                return value;
            },
            createItem : function (item,target) {
                item = Kbylin.str2Obj(item);

                //设置基本的两个属性
                if(!item.hasOwnProperty('id') || !item.hasOwnProperty('title')) return Dazzling.toast.warning("The id/title of item should not be empty");
                var li = $('<li class="dd-item dd3-item" data-id="'+item['id']+'"></li>');
                li.append($('<div class="dd-handle dd3-handle">'));
                var content = $('<div class="dd3-content">'+item['title']+'</div>');
                li.append(content);

                //设置其他附加属性(title id除外)
                for(var x in item){
                    if(!item.hasOwnProperty(x)) continue;
                    if(!$.inArray(x,['title','id','children'])) li.attr("data-"+x,item[x]);
                }

                //如果存在子元素的情况下创建
                item.hasOwnProperty('children') && this.createItemList(item['children'],li);

                if(!target || !target.length ) target = this.target;
                if(!target) return Dazzling.toast.warning('Nestable require a target to attach!');

                var targetol = target.children('ol');
                if(!targetol.length){/* 缺少OL的情况下创建一个空的UL */
                    this.createItemList([],target);
                    targetol = target.children('ol');
                }
                targetol.prepend(li);
                return this;
            },
            /**
             * 创建并添加到指定元素下
             * @param serialization 配置序列或者配置对象 (必须)
             * @param group 分组
             * @param attatchment 创建并添加的对象,如果指定了ID将添加到指定的对象上并返回nestable对象;否则返回创建的jquery对象
             */
            create : function (serialization,group,attatchment) {
                serialization = Kbylin.str2Obj(serialization);

                var instance = Dazzling.utils.newInstance(this);

                var id = 'nestable_'+guid();
                var topdiv = $('<div class="dd" id="'+id+'"></div>');
                this.createItemList(serialization,topdiv);

                instance.target = topdiv.nestable({group: group?group:id});
                undefined === attatchment || instance.target.prependTo(attatchment);
                // console.log(instance);
                return instance;
            },
            prependTo :function (attatchment) {
                attatchment = toJquery(attatchment);
                attatchment.html('');
                if(attatchment.length) {
                    attatchment.prepend(this.target);
                }
            },
            appendTo :function (attatchment) {
                attatchment = toJquery(attatchment);
                attatchment.html('');
                if(attatchment.length) {
                    attatchment.appendTo(this.target);
                }
            }
        },
        tab:{
            _createNav:function (config) {
                var id = Dazzling.utils.guid();
                var nav = $('<ul id="'+id+'" class="nav nav-tabs"></ul>');
                var isfirst = true;
                var node,ul;
                for(var x = 0; x < config.length; x ++){

                    var item = config[x];

                    if(!item.hasOwnProperty('title')) return Dazzling.toast.warning('Tab require a title!');


                    if(item.hasOwnProperty('children')){/*下拉*/
                        var guid = Dazzling.utils.guid();
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
                var content = $('<div id="'+Dazzling.utils.guid()+'" class="tab-content"></div>');
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
            },
            createIconPane:function (config) {
                var sections = {
                    'icons-web-app':'<div id="icons-web-app" class="row"><div class="span3"><ul class="the-icons"><li><i class="icon-adjust"></i> icon-adjust</li><li><i class="icon-asterisk"></i> icon-asterisk</li><li><i class="icon-ban-circle"></i> icon-ban-circle</li><li><i class="icon-bar-chart"></i> icon-bar-chart</li><li><i class="icon-barcode"></i> icon-barcode</li><li><i class="icon-beaker"></i> icon-beaker</li><li><i class="icon-beer"></i> icon-beer</li><li><i class="icon-bell"></i> icon-bell</li><li><i class="icon-bell-alt"></i> icon-bell-alt</li><li><i class="icon-bolt"></i> icon-bolt</li><li><i class="icon-book"></i> icon-book</li><li><i class="icon-bookmark"></i> icon-bookmark</li><li><i class="icon-bookmark-empty"></i> icon-bookmark-empty</li><li><i class="icon-briefcase"></i> icon-briefcase</li><li><i class="icon-bullhorn"></i> icon-bullhorn</li><li><i class="icon-calendar"></i> icon-calendar</li><li><i class="icon-camera"></i> icon-camera</li><li><i class="icon-camera-retro"></i> icon-camera-retro</li><li><i class="icon-certificate"></i> icon-certificate</li><li><i class="icon-check"></i> icon-check</li><li><i class="icon-check-empty"></i> icon-check-empty</li><li><i class="icon-circle"></i> icon-circle</li><li><i class="icon-circle-blank"></i> icon-circle-blank</li><li><i class="icon-cloud"></i> icon-cloud</li><li><i class="icon-cloud-download"></i> icon-cloud-download</li><li><i class="icon-cloud-upload"></i> icon-cloud-upload</li><li><i class="icon-coffee"></i> icon-coffee</li><li><i class="icon-cog"></i> icon-cog</li><li><i class="icon-cogs"></i> icon-cogs</li><li><i class="icon-comment"></i> icon-comment</li><li><i class="icon-comment-alt"></i> icon-comment-alt</li><li><i class="icon-comments"></i> icon-comments</li><li><i class="icon-comments-alt"></i> icon-comments-alt</li><li><i class="icon-credit-card"></i> icon-credit-card</li><li><i class="icon-dashboard"></i> icon-dashboard</li><li><i class="icon-desktop"></i> icon-desktop</li><li><i class="icon-download"></i> icon-download</li><li><i class="icon-download-alt"></i> icon-download-alt</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-edit"></i> icon-edit</li><li><i class="icon-envelope"></i> icon-envelope</li><li><i class="icon-envelope-alt"></i> icon-envelope-alt</li><li><i class="icon-exchange"></i> icon-exchange</li><li><i class="icon-exclamation-sign"></i> icon-exclamation-sign</li><li><i class="icon-external-link"></i> icon-external-link</li><li><i class="icon-eye-close"></i> icon-eye-close</li><li><i class="icon-eye-open"></i> icon-eye-open</li><li><i class="icon-facetime-video"></i> icon-facetime-video</li><li><i class="icon-fighter-jet"></i> icon-fighter-jet</li><li><i class="icon-film"></i> icon-film</li><li><i class="icon-filter"></i> icon-filter</li><li><i class="icon-fire"></i> icon-fire</li><li><i class="icon-flag"></i> icon-flag</li><li><i class="icon-folder-close"></i> icon-folder-close</li><li><i class="icon-folder-open"></i> icon-folder-open</li><li><i class="icon-folder-close-alt"></i> icon-folder-close-alt</li><li><i class="icon-folder-open-alt"></i> icon-folder-open-alt</li><li><i class="icon-food"></i> icon-food</li><li><i class="icon-gift"></i> icon-gift</li><li><i class="icon-glass"></i> icon-glass</li><li><i class="icon-globe"></i> icon-globe</li><li><i class="icon-group"></i> icon-group</li><li><i class="icon-hdd"></i> icon-hdd</li><li><i class="icon-headphones"></i> icon-headphones</li><li><i class="icon-heart"></i> icon-heart</li><li><i class="icon-heart-empty"></i> icon-heart-empty</li><li><i class="icon-home"></i> icon-home</li><li><i class="icon-inbox"></i> icon-inbox</li><li><i class="icon-info-sign"></i> icon-info-sign</li><li><i class="icon-key"></i> icon-key</li><li><i class="icon-leaf"></i> icon-leaf</li><li><i class="icon-laptop"></i> icon-laptop</li><li><i class="icon-legal"></i> icon-legal</li><li><i class="icon-lemon"></i> icon-lemon</li><li><i class="icon-lightbulb"></i> icon-lightbulb</li><li><i class="icon-lock"></i> icon-lock</li><li><i class="icon-unlock"></i> icon-unlock</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-magic"></i> icon-magic</li><li><i class="icon-magnet"></i> icon-magnet</li><li><i class="icon-map-marker"></i> icon-map-marker</li><li><i class="icon-minus"></i> icon-minus</li><li><i class="icon-minus-sign"></i> icon-minus-sign</li><li><i class="icon-mobile-phone"></i> icon-mobile-phone</li><li><i class="icon-money"></i> icon-money</li><li><i class="icon-move"></i> icon-move</li><li><i class="icon-music"></i> icon-music</li><li><i class="icon-off"></i> icon-off</li><li><i class="icon-ok"></i> icon-ok</li><li><i class="icon-ok-circle"></i> icon-ok-circle</li><li><i class="icon-ok-sign"></i> icon-ok-sign</li><li><i class="icon-pencil"></i> icon-pencil</li><li><i class="icon-picture"></i> icon-picture</li><li><i class="icon-plane"></i> icon-plane</li><li><i class="icon-plus"></i> icon-plus</li><li><i class="icon-plus-sign"></i> icon-plus-sign</li><li><i class="icon-print"></i> icon-print</li><li><i class="icon-pushpin"></i> icon-pushpin</li><li><i class="icon-qrcode"></i> icon-qrcode</li><li><i class="icon-question-sign"></i> icon-question-sign</li><li><i class="icon-quote-left"></i> icon-quote-left</li><li><i class="icon-quote-right"></i> icon-quote-right</li><li><i class="icon-random"></i> icon-random</li><li><i class="icon-refresh"></i> icon-refresh</li><li><i class="icon-remove"></i> icon-remove</li><li><i class="icon-remove-circle"></i> icon-remove-circle</li><li><i class="icon-remove-sign"></i> icon-remove-sign</li><li><i class="icon-reorder"></i> icon-reorder</li><li><i class="icon-reply"></i> icon-reply</li><li><i class="icon-resize-horizontal"></i> icon-resize-horizontal</li><li><i class="icon-resize-vertical"></i> icon-resize-vertical</li><li><i class="icon-retweet"></i> icon-retweet</li><li><i class="icon-road"></i> icon-road</li><li><i class="icon-rss"></i> icon-rss</li><li><i class="icon-screenshot"></i> icon-screenshot</li><li><i class="icon-search"></i> icon-search</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-share"></i> icon-share</li><li><i class="icon-share-alt"></i> icon-share-alt</li><li><i class="icon-shopping-cart"></i> icon-shopping-cart</li><li><i class="icon-signal"></i> icon-signal</li><li><i class="icon-signin"></i> icon-signin</li><li><i class="icon-signout"></i> icon-signout</li><li><i class="icon-sitemap"></i> icon-sitemap</li><li><i class="icon-sort"></i> icon-sort</li><li><i class="icon-sort-down"></i> icon-sort-down</li><li><i class="icon-sort-up"></i> icon-sort-up</li><li><i class="icon-spinner"></i> icon-spinner</li><li><i class="icon-star"></i> icon-star</li><li><i class="icon-star-empty"></i> icon-star-empty</li><li><i class="icon-star-half"></i> icon-star-half</li><li><i class="icon-tablet"></i> icon-tablet</li><li><i class="icon-tag"></i> icon-tag</li><li><i class="icon-tags"></i> icon-tags</li><li><i class="icon-tasks"></i> icon-tasks</li><li><i class="icon-thumbs-down"></i> icon-thumbs-down</li><li><i class="icon-thumbs-up"></i> icon-thumbs-up</li><li><i class="icon-time"></i> icon-time</li><li><i class="icon-tint"></i> icon-tint</li><li><i class="icon-trash"></i> icon-trash</li><li><i class="icon-trophy"></i> icon-trophy</li><li><i class="icon-truck"></i> icon-truck</li><li><i class="icon-umbrella"></i> icon-umbrella</li><li><i class="icon-upload"></i> icon-upload</li><li><i class="icon-upload-alt"></i> icon-upload-alt</li><li><i class="icon-user"></i> icon-user</li><li><i class="icon-user-md"></i> icon-user-md</li><li><i class="icon-volume-off"></i> icon-volume-off</li><li><i class="icon-volume-down"></i> icon-volume-down</li><li><i class="icon-volume-up"></i> icon-volume-up</li><li><i class="icon-warning-sign"></i> icon-warning-sign</li><li><i class="icon-wrench"></i> icon-wrench</li><li><i class="icon-zoom-in"></i> icon-zoom-in</li><li><i class="icon-zoom-out"></i> icon-zoom-out</li></ul></div></div>',
                    'icons-text-editor':'<section id="icons-text-editor" class="row"><div class="span3"><ul class="the-icons"><li><i class="icon-file"></i> icon-file</li><li><i class="icon-file-alt"></i> icon-file-alt</li><li><i class="icon-cut"></i> icon-cut</li><li><i class="icon-copy"></i> icon-copy</li><li><i class="icon-paste"></i> icon-paste</li><li><i class="icon-save"></i> icon-save</li><li><i class="icon-undo"></i> icon-undo</li><li><i class="icon-repeat"></i> icon-repeat</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-text-height"></i> icon-text-height</li><li><i class="icon-text-width"></i> icon-text-width</li><li><i class="icon-align-left"></i> icon-align-left</li><li><i class="icon-align-center"></i> icon-align-center</li><li><i class="icon-align-right"></i> icon-align-right</li><li><i class="icon-align-justify"></i> icon-align-justify</li><li><i class="icon-indent-left"></i> icon-indent-left</li><li><i class="icon-indent-right"></i> icon-indent-right</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-font"></i> icon-font</li><li><i class="icon-bold"></i> icon-bold</li><li><i class="icon-italic"></i> icon-italic</li><li><i class="icon-strikethrough"></i> icon-strikethrough</li><li><i class="icon-underline"></i> icon-underline</li><li><i class="icon-link"></i> icon-link</li><li><i class="icon-paper-clip"></i> icon-paper-clip</li><li><i class="icon-columns"></i> icon-columns</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-table"></i> icon-table</li><li><i class="icon-th-large"></i> icon-th-large</li><li><i class="icon-th"></i> icon-th</li><li><i class="icon-th-list"></i> icon-th-list</li><li><i class="icon-list"></i> icon-list</li><li><i class="icon-list-ol"></i> icon-list-ol</li><li><i class="icon-list-ul"></i> icon-list-ul</li><li><i class="icon-list-alt"></i> icon-list-alt</li></ul></div></section>',
                    'icons-directional':'<section id="icons-directional" class="row"><div class="span3"><ul class="the-icons"><li><i class="icon-angle-left"></i> icon-angle-left</li><li><i class="icon-angle-right"></i> icon-angle-right</li><li><i class="icon-angle-up"></i> icon-angle-up</li><li><i class="icon-angle-down"></i> icon-angle-down</li><li><i class="icon-arrow-down"></i> icon-arrow-down</li><li><i class="icon-arrow-left"></i> icon-arrow-left</li><li><i class="icon-arrow-right"></i> icon-arrow-right</li><li><i class="icon-arrow-up"></i> icon-arrow-up</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-caret-down"></i> icon-caret-down</li><li><i class="icon-caret-left"></i> icon-caret-left</li><li><i class="icon-caret-right"></i> icon-caret-right</li><li><i class="icon-caret-up"></i> icon-caret-up</li><li><i class="icon-chevron-down"></i> icon-chevron-down</li><li><i class="icon-chevron-left"></i> icon-chevron-left</li><li><i class="icon-chevron-right"></i> icon-chevron-right</li><li><i class="icon-chevron-up"></i> icon-chevron-up</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-circle-arrow-down"></i> icon-circle-arrow-down</li><li><i class="icon-circle-arrow-left"></i> icon-circle-arrow-left</li><li><i class="icon-circle-arrow-right"></i> icon-circle-arrow-right</li><li><i class="icon-circle-arrow-up"></i> icon-circle-arrow-up</li><li><i class="icon-double-angle-left"></i> icon-double-angle-left</li><li><i class="icon-double-angle-right"></i> icon-double-angle-right</li><li><i class="icon-double-angle-up"></i> icon-double-angle-up</li><li><i class="icon-double-angle-down"></i> icon-double-angle-down</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-hand-down"></i> icon-hand-down</li><li><i class="icon-hand-left"></i> icon-hand-left</li><li><i class="icon-hand-right"></i> icon-hand-right</li><li><i class="icon-hand-up"></i> icon-hand-up</li><li><i class="icon-circle"></i> icon-circle</li><li><i class="icon-circle-blank"></i> icon-circle-blank</li></ul></div></section>',
                    'icons-video-player':'<section id="icons-video-player" class="row"><div class="span3"><ul class="the-icons"><li><i class="icon-play-circle"></i> icon-play-circle</li><li><i class="icon-play"></i> icon-play</li><li><i class="icon-pause"></i> icon-pause</li><li><i class="icon-stop"></i> icon-stop</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-step-backward"></i> icon-step-backward</li><li><i class="icon-fast-backward"></i> icon-fast-backward</li><li><i class="icon-backward"></i> icon-backward</li><li><i class="icon-forward"></i> icon-forward</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-fast-forward"></i> icon-fast-forward</li><li><i class="icon-step-forward"></i> icon-step-forward</li><li><i class="icon-eject"></i> icon-eject</li></ul></div><div class="span3"><ul class="the-icons"><li><i class="icon-fullscreen"></i> icon-fullscreen</li><li><i class="icon-resize-full"></i> icon-resize-full</li><li><i class="icon-resize-small"></i> icon-resize-small</li></ul></div></section>'
                };
                var div = document.createElement(div);

                if(!config) config = {};
                for(var x in sections){
                    if(!sections.hasOwnProperty(x)) continue;
                    if(!config.hasOwnProperty(x) || config[x] !== false) {/* === false时明确表示不要 */
                        div.innerHTML += sections[x];
                    }
                }
                return toJquery(div);
            },
            getIconPaneConvention:function () {
                return [
                    { id:'web-app',title:'Web 应用',content:'#icons-web-app'},
                    { id:'text-editor',title:'编辑器',content:'#icons-web-app'},
                    { id:'directional',title:'方向的',content:'#icons-web-app'},
                    { id:'video-player',title:'播放器',content:'#icons-web-app'}
                    // { title:'其他',children:[{ id:'nana',title:'娜娜',content:'#sss2'}]}
                ];
            }
        }
    };
})();



