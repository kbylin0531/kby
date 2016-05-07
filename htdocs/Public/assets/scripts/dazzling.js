/**
 * Created by Zhonghuang on 2016/4/15.
 */
var Dazzling = function (config) {

    var convention = {
    };

    for(var x in config) if(x in convention) convention[x] = config[x];

    if(!jQuery) throw "Require Jquery!";

    var thishtml = $('html');
    var thisbody = $('body');
    var resizeHandlers = [];

    var page_header  = $('.page-header');
    var page_content = $('.page-content');
    var page_sidebar = $('.page-sidebar');
    var page_footer  = $('.page-footer');
    var page_sidebar_menu = $('.page-sidebar-menu');

    var getBrowserInfo = function () {
            var ret = {}; //用户返回的对象
            var _tom = {};
            var _nick;
            var ua = navigator.userAgent.toLowerCase();
            (_nick = ua.match(/msie ([\d.]+)/)) ? _tom.ie = _nick[1] :
                (_nick = ua.match(/firefox\/([\d.]+)/)) ? _tom.firefox = _nick[1] :
                    (_nick = ua.match(/chrome\/([\d.]+)/)) ? _tom.chrome = _nick[1] :
                        (_nick = ua.match(/opera.([\d.]+)/)) ? _tom.opera = _nick[1] :
                            (_nick = ua.match(/version\/([\d.]+).*safari/)) ? _tom.safari = _nick[1] : 0;
            if (_tom.ie) {
                ret.type = "ie";
                ret.version = parseInt(_tom.ie);
            } else if (_tom.firefox) {
                ret.type = "firefox";
                ret.version = parseInt(_tom.firefox);
            } else if (_tom.chrome) {
                ret.type = "chrome";
                ret.version = parseInt(_tom.chrome);
            } else if (_tom.opera) {
                ret.type = "opera";
                ret.version = parseInt(_tom.opera);
            } else if (_tom.safari) {
                ret.type = "safari";
                ret.version = parseInt(_tom.safari);
            }else{
                ret.type = ret.version ="unknown";
            }
            return ret;
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
        // bootstrap responsive breakpoints
        var sizes = {
            'xs' : 480,     // extra small
            'sm' : 768,     // small
            'md' : 992,     // medium
            'lg' : 1200     // large
        };

        return sizes[size] ? sizes[size] : 0;
    };
    var getViewPort = function() {
        var e = window,
            a = 'inner';
        if (!('innerWidth' in window)) {
            a = 'client';
            e = document.documentElement || document.body;
        }

        return {
            width: e[a + 'Width'],
            height: e[a + 'Height']
        };
    };

    //SlimScroll相关
    var createSlimScroll = function(el) {
        $(el).each(function() {
            if ($(this).attr("data-initialized")) return; // 避免重复初始化

            var height;
            var data_always_visible = $(this).attr("data-always-visible") == "1" ;

            if ($(this).attr("data-height")) {
                height = $(this).attr("data-height");
            } else {
                height = $(this).css('height');
            }

            $(this).slimScroll({
                allowPageScroll: true, // allow page scroll when the element scroll is ended
                size: '7px',
                color: ($(this).attr("data-handle-color") ? $(this).attr("data-handle-color") : '#bbb'),
                wrapperClass: ($(this).attr("data-wrapper-class") ? $(this).attr("data-wrapper-class") : 'slimScrollDiv'),
                railColor: ($(this).attr("data-rail-color") ? $(this).attr("data-rail-color") : '#eaeaea'),
                position: 'left',
                height: height,
                alwaysVisible: data_always_visible,
                railVisible: data_always_visible,
                disableFadeOut: true
            });

            $(this).attr("data-initialized", "1");//标记初始化
        });
    };
    var destroySlimScroll = function(el) {
        $(el).each(function() {
            if ($(this).attr("data-initialized") === "1") { // destroy existing instance before updating the height
                $(this).removeAttr("data-initialized");
                $(this).removeAttr("style");

                var attrList = {};

                // store the custom attribures so later we will reassign.
                if ($(this).attr("data-handle-color")) {
                    attrList["data-handle-color"] = $(this).attr("data-handle-color");
                }
                if ($(this).attr("data-wrapper-class")) {
                    attrList["data-wrapper-class"] = $(this).attr("data-wrapper-class");
                }
                if ($(this).attr("data-rail-color")) {
                    attrList["data-rail-color"] = $(this).attr("data-rail-color");
                }
                if ($(this).attr("data-always-visible")) {
                    attrList["data-always-visible"] = $(this).attr("data-always-visible");
                }
                if ($(this).attr("data-rail-visible")) {
                    attrList["data-rail-visible"] = $(this).attr("data-rail-visible");
                }

                $(this).slimScroll({
                    wrapperClass: ($(this).attr("data-wrapper-class") ? $(this).attr("data-wrapper-class") : 'slimScrollDiv'),
                    destroy: true
                });

                var the = $(this);

                // reassign custom attributes
                $.each(attrList, function(key, value) {
                    the.attr(key, value);
                });

            }
        });
    };
    //布局初始化
    var resBreakpointMd = getResponsiveBreakpoint('md');

    //侧边栏高度处理
    var handleSidebarAndContentHeight = function () {
        var content = page_content;
        var sidebar = page_sidebar;
        var body = thisbody;
        var height;

        if (body.hasClass("page-footer-fixed") === true && body.hasClass("page-sidebar-fixed") === false) {
            var available_height = getViewPort().height - page_footer.outerHeight() - page_header.outerHeight();
            if (content.height() < available_height) {
                content.attr('style', 'min-height:' + available_height + 'px');
            }
        } else {
            if (body.hasClass('page-sidebar-fixed')) {
                height = _calculateFixedSidebarViewportHeight();
                if (body.hasClass('page-footer-fixed') === false) {
                    height = height - page_footer.outerHeight();
                }
            } else {
                var headerHeight = page_header.outerHeight();
                var footerHeight = page_footer.outerHeight();

                if (getViewPort().width < resBreakpointMd) {
                    height = getViewPort().height - headerHeight - footerHeight;
                } else {
                    height = sidebar.height() + 20;
                }

                if ((height + headerHeight + footerHeight) <= getViewPort().height) {
                    height = getViewPort().height - headerHeight - footerHeight;
                }
            }
            content.attr('style', 'min-height:' + height + 'px');
        }
    };

    // Handle sidebar menu
    var handleSidebarMenu = function () {
        // handle sidebar link click
        page_sidebar_menu.on('click', 'li > a.nav-toggle, li > a > span.nav-toggle', function (e) {
            var that = $(this).closest('.nav-item').children('.nav-link');

            if (getViewPort().width >= resBreakpointMd && !page_sidebar_menu.attr("data-initialized") && thisbody.hasClass('page-sidebar-closed') &&  that.parent('li').parent('.page-sidebar-menu').size() === 1) {
                return;
            }

            var hasSubMenu = that.next().hasClass('sub-menu');

            if (getViewPort().width >= resBreakpointMd && that.parents('.page-sidebar-menu-hover-submenu').size() === 1) { // exit of hover sidebar menu
                return;
            }

            if (hasSubMenu === false) {
                if (getViewPort().width < resBreakpointMd && page_sidebar.hasClass("in")) { // close the menu on mobile view while laoding a page
                    page_header.find('.responsive-toggler').click();
                }
                return;
            }

            if (that.next().hasClass('sub-menu always-open')) {
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
                parent.children('li.open').children('.sub-menu:not(.always-open)').slideUp(slideSpeed);
                parent.children('li.open').removeClass('open');
            }

            var slideOffeset = -200;

            if (sub.is(":visible")) {
                $('.arrow', the).removeClass("open");
                the.parent().removeClass("open");
                sub.slideUp(slideSpeed, function () {
                    if (autoScroll === true && thisbody.hasClass('page-sidebar-closed') === false) {
                        if (thisbody.hasClass('page-sidebar-fixed')) {
                            menu.slimScroll({
                                'scrollTo': (the.position()).top
                            });
                        } else {
                            scrollTo(the, slideOffeset);
                        }
                    }
                    handleSidebarAndContentHeight();
                });
            } else if (hasSubMenu) {
                $('.arrow', the).addClass("open");
                the.parent().addClass("open");
                sub.slideDown(slideSpeed, function () {
                    if (autoScroll === true && thisbody.hasClass('page-sidebar-closed') === false) {
                        if (thisbody.hasClass('page-sidebar-fixed')) {
                            menu.slimScroll({
                                'scrollTo': (the.position()).top
                            });
                        } else {
                            scrollTo(the, slideOffeset);
                        }
                    }
                    handleSidebarAndContentHeight();
                });
            }

            e.preventDefault();
        });

        // handle menu close for angularjs version

        // handle scrolling to top on responsive menu toggler click when header is fixed for mobile view
        $(document).on('click', '.page-header-fixed-mobile .page-header .responsive-toggler', function(){
            scrollTop();
        });

        // handle sidebar hover effect
        handleFixedSidebarHoverEffect();
    };

    // Helper function to calculate sidebar height for fixed sidebar layout.
    var _calculateFixedSidebarViewportHeight = function () {
        var sidebarHeight = getViewPort().height - page_header.outerHeight(true);
        if (thisbody.hasClass("page-footer-fixed")) {
            sidebarHeight = sidebarHeight - page_footer.outerHeight();
        }
        return sidebarHeight;
    };

    // Handles fixed sidebar
    var handleFixedSidebar = function () {
        destroySlimScroll(page_sidebar_menu);
        if ($('.page-sidebar-fixed').size() === 0) {
            return handleSidebarAndContentHeight();
        }
        if (getViewPort().width >= resBreakpointMd) {
            page_sidebar_menu.attr("data-height", _calculateFixedSidebarViewportHeight());
            createSlimScroll(page_sidebar_menu);
            handleSidebarAndContentHeight();
        }
    };

    // Handles sidebar toggler to close/hide the sidebar.
    var handleFixedSidebarHoverEffect = function () {
        var body = thisbody;
        if (body.hasClass('page-sidebar-fixed')) {
            page_sidebar.on('mouseenter', function () {
                if (body.hasClass('page-sidebar-closed')) {
                    $(this).find('.page-sidebar-menu').removeClass('page-sidebar-menu-closed');
                }
            }).on('mouseleave', function () {
                if (body.hasClass('page-sidebar-closed')) {
                    $(this).find('.page-sidebar-menu').addClass('page-sidebar-menu-closed');
                }
            });
        }
    };

    // Hanles sidebar toggler
    var handleSidebarToggler = function () {
        if ($.cookie && $.cookie('sidebar_closed') === '1' && getViewPort().width >= resBreakpointMd) {
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
                if (thisbody.hasClass("page-sidebar-fixed")) {
                    sidebarMenu.trigger("mouseleave");
                }
                if ($.cookie) {
                    $.cookie('sidebar_closed', '1');
                }
            }
            $(window).trigger('resize');
        });
    };

    //初始化顶部的查询
    var initHeaderSearchForm = function (handler) {
        // handle search box expand/collapse
        page_header.on('click', '.search-form', function () {
            var form_controll = $(this).find('.form-control');
            $(this).addClass("open");
            form_controll.focus();/* 主动聚焦 */
            form_controll.on('blur', function () {/* 失去焦点时自动关闭 */
                if(typeof handler === 'function') return handler(form_controll.val());
                $(this).closest('.search-form').removeClass("open");
                $(this).unbind("blur");
            });
        });

        // handle hor menu search form on enter press
        page_header.on('keypress', '.hor-menu .search-form .form-control', function (e) {
            if (e.which == 13) {/* 按下回车自动提交 */
                if(typeof handler === 'function') return handler($(this).val());
                $(this).closest('.search-form').submit();
                return false;
            }
        });

        // handle header search button click
        page_header.on('mousedown', '.search-form.open .submit', function (e) {/* .submit是超链接,需要阻止事件的传播 */
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('.search-form').submit();
        });
    };

    var quick_sidebar_wrapper = $('.page-quick-sidebar-wrapper');//quick-sidebar内容区
    //将page-quick-sidebar-wrapper类下的指定元素 resize scroll
    var initSlimScroll = function (selector) {
        var alertList = quick_sidebar_wrapper.find(selector);
        var alertListHeight = quick_sidebar_wrapper.height() - quick_sidebar_wrapper.find('.nav-justified > .nav-tabs').outerHeight();
        // alerts list
        destroySlimScroll(alertList);
        alertList.attr("data-height", alertListHeight);
        createSlimScroll(alertList);
    };
    // Handles quick sidebar chats
    var handleQuickSidebarChat = function () {
        var wrapper = $('.page-quick-sidebar-wrapper');
        var wrapperChat = wrapper.find('.page-quick-sidebar-chat');

        var initChatSlimScroll = function () {
            var chatUsers = wrapper.find('.page-quick-sidebar-chat-users');
            var chatUsersHeight = wrapper.height() - wrapper.find('.nav-tabs').outerHeight(true);

            // chat user list
            destroySlimScroll(chatUsers);
            chatUsers.attr("data-height", chatUsersHeight);
            createSlimScroll(chatUsers);

            var wrapperChat = wrapper.find('.page-quick-sidebar-chat');
            var chatMessages = wrapperChat.find('.page-quick-sidebar-chat-user-messages');
            var chatMessagesHeight = chatUsersHeight - wrapperChat.find('.page-quick-sidebar-chat-user-form').outerHeight(true) - wrapperChat.find('.page-quick-sidebar-nav').outerHeight(true);

            // user chat messages
            destroySlimScroll(chatMessages);
            chatMessages.attr("data-height", chatMessagesHeight);
            createSlimScroll(chatMessages);
        };

        initChatSlimScroll();
        resizeHandlers.push(initChatSlimScroll); // reinitialize on window resize

        wrapper.find('.page-quick-sidebar-chat-users .media-list > .media').click(function () {
            wrapperChat.addClass("page-quick-sidebar-content-item-shown");
        });
        wrapper.find('.page-quick-sidebar-chat-user .page-quick-sidebar-back-to-list').click(function () {
            wrapperChat.removeClass("page-quick-sidebar-content-item-shown");
        });

        var handleChatMessagePost = function (e) {
            e.preventDefault();

            var chatContainer = wrapperChat.find(".page-quick-sidebar-chat-user-messages");
            var input = wrapperChat.find('.page-quick-sidebar-chat-user-form .form-control');

            var text = input.val();
            if (text.length === 0) {
                return;
            }

            var preparePost = function(dir, time, name, avatar, message) {
                var tpl = '';
                tpl += '<div class="post '+ dir +'">';
                tpl += '<img class="avatar" alt="" src="' + Layout.getLayoutImgPath() + avatar +'.jpg"/>';
                tpl += '<div class="message">';
                tpl += '<span class="arrow"></span>';
                tpl += '<a href="#" class="name">Bob Nilson</a>&nbsp;';
                tpl += '<span class="datetime">' + time + '</span>';
                tpl += '<span class="body">';
                tpl += message;
                tpl += '</span>';
                tpl += '</div>';
                tpl += '</div>';

                return tpl;
            };

            // handle post
            var time = new Date();
            var message = preparePost('out', (time.getHours() + ':' + time.getMinutes()), "Bob Nilson", 'avatar3', text);
            message = $(message);
            chatContainer.append(message);

            chatContainer.slimScroll({scrollTo: '1000000px'});

            input.val("");

            // simulate reply
            setTimeout(function(){
                var time = new Date();
                var message = preparePost('in', (time.getHours() + ':' + time.getMinutes()), "Ella Wong", 'avatar2', 'Lorem ipsum doloriam nibh...');
                message = $(message);
                chatContainer.append(message);

                chatContainer.slimScroll({
                    scrollTo: '1000000px'
                });
            }, 3000);
        };

        wrapperChat.find('.page-quick-sidebar-chat-user-form .btn').click(handleChatMessagePost);
        wrapperChat.find('.page-quick-sidebar-chat-user-form .form-control').keypress(function (e) {
            if (e.which == 13) {
                handleChatMessagePost(e);
                return false;
            }
        });
    };

    //sidebar相关的初始化
    var initSidebar = function () {
        handleFixedSidebar(); // handles fixed sidebar menu
        handleSidebarMenu(); // handles main menu
        handleSidebarToggler(); // handles sidebar hide/show
        resizeHandlers.push(handleFixedSidebar); // reinitialize fixed sidebar on window resize

        // handle bootstrah tabs
        thisbody.on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
            handleSidebarAndContentHeight();
        });
        resizeHandlers.push(handleSidebarAndContentHeight);// recalculate sidebar & content height on window resize

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
        //处理console对象缺失
        !window.console &&  (window.console = (function(){var c = {}; c.log = c.warn = c.debug = c.info = c.error = c.time = c.dir = c.profile = c.clear = c.exception = c.trace = c.assert = function(){}; return c;})());
        //IE8不支持indexOf方法
        if (!Array.prototype.indexOf){
            Array.prototype.indexOf = function(elt){
                var len = this.length >>> 0;
                var from = Number(arguments[1]) || 0;
                from = (from < 0)
                    ? Math.ceil(from)
                    : Math.floor(from);
                if (from < 0)
                    from += len;
                for (; from < len; from++)
                {
                    if (from in this &&
                        this[from] === elt)
                        return from;
                }
                return -1;
            };
        }

        /**
         * 对Date的扩展，将 Date 转化为指定格式的String
         * 月(M)、日(d)、小时(h)、分(m)、秒(s)、季度(q) 可以用 1-2 个占位符，
         * 年(y)可以用 1-4 个占位符，毫秒(S)只能用 1 个占位符(是 1-3 位的数字)
         * 例子：
         * (new Date()).format("yyyy-MM-dd hh:mm:ss.S") ==> 2006-07-02 08:09:04.423
         * (new Date()).format("yyyy-M-d h:m:s.S")      ==> 2006-7-2 8:9:4.18
         */
        Date.prototype.format = function(fmt){ //author: meizz
            var o = {
                "M+" : this.getMonth()+1,                 //月份
                "d+" : this.getDate(),                    //日
                "h+" : this.getHours(),                   //小时
                "m+" : this.getMinutes(),                 //分
                "s+" : this.getSeconds(),                 //秒
                "q+" : Math.floor((this.getMonth()+3)/3), //季度
                "S"  : this.getMilliseconds()             //毫秒
            };
            if(/(y+)/.test(fmt))
                fmt=fmt.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
            for(var k in o)
                if(new RegExp("("+ k +")").test(fmt))
                    fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));
            return fmt;
        };

        //添加placeholder支持,处理不支持placeholder的浏览器,这里将不支持IE8以下的浏览器,故只有IE9和IE10
        var browerinfo = getBrowserInfo();
        var isIE8 = browerinfo.type === 'ie' && 8 === browerinfo.version;
        var isIE9 = browerinfo.type === 'ie' && 9 === browerinfo.version;
        if(isIE8 || isIE9){
            // this is html5 placeholder fix for inputs, inputs with placeholder-no-fix class will be skipped(e.g: we need this for password fields)
            $('input[placeholder]:not(.placeholder-no-fix), textarea[placeholder]:not(.placeholder-no-fix)').each(function() {
                var input = $(this);

                if (input.val() === '' && input.attr("placeholder") !== '') {
                    input.addClass("placeholder").val(input.attr('placeholder'));
                }

                input.focus(function() {
                    if (input.val() == input.attr('placeholder')) {
                        input.val('');
                    }
                });

                input.blur(function() {
                    if (input.val() === '' || input.val() == input.attr('placeholder')) {
                        input.val(input.attr('placeholder'));
                    }
                });
            });
        }
    };
    //初始化应用
    var initApplication = function () {
        var resize;
        var currheight;
        var browerinfo = getBrowserInfo();
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
                //执行调整函数
                for (var i = 0; i < resizeHandlers.length; i++) {
                    var each = resizeHandlers[i];
                    each.call();
                }
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
    //quick-sidebar
    var initQuickSidebar = function () {
        $('.dropdown-quick-sidebar-toggler a, .page-quick-sidebar-toggler').click(function () {
            $('body').toggleClass('page-quick-sidebar-open');
        });
        //初始化聊天界面
        handleQuickSidebarChat();

        // reinitialize on window resize
        var _resizeSlimOnWindowsResized = function(){
            initSlimScroll('.page-quick-sidebar-alerts-list');
            initSlimScroll('.page-quick-sidebar-settings-list');
        };
        _resizeSlimOnWindowsResized();
        resizeHandlers.push(_resizeSlimOnWindowsResized); // reinitialize on window resize
    };
    //初始化应用
    var init = function () {
        handleCompatibility();//处理常见的兼容性问题

        initApplication();//初始化应用

        //初始化布局
        initHeaderSearchForm(); // handles horizontal menu
        initSidebar();//初始化sidebar
        initGotoTop();//处理足部的Go to top 按钮

        //初始化quick-sidebar
        initQuickSidebar();//初始化quick-sidebar
    };

    return {
        'init':init,
        'scrollTo':scrollTo,
        /**
         * 习惯性的jquery方法
         * @param url 请求地址
         * @param data 请求数据对象
         * @param callback 服务器响应时的回调
         * @param datatype 期望返回的数据类型 json xml html script json jsonp text 中的一种
         * @param async 是否异步,希望同步的清空下使用false,默认为true
         * @returns {*}
         */
        'post':function (url, data, callback, datatype, async) {
            if(undefined === datatype) datatype = "json";
            if(undefined === async) async = true;
            return jQuery.ajax({
                url: url,
                type: 'post',
                dataType:datatype,
                async: async,
                data: data,
                success:callback
            });
        },
        /**
         * 获取浏览器信息
         * Object {type: "Chrome", version: "50.0.2661.94"}
         * @returns {{}}
         */
        'getBrowserInfo':getBrowserInfo,
        'initHeaderSearchForm':initHeaderSearchForm
    };
}();

$(function () {
    Dazzling.init();
    // console.log(Dazzling.getBrowserInfo())
});
