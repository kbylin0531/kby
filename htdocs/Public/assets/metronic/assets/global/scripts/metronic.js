/**
Core script to handle the entire theme and core functions
**/
var Metronic = function() {
    var body = $("body");
    var resizeHandlers = [];

    /**
     * 初始化
     */
    var handleInit = function() {
        if (Genkits.isIE10()) {
            $('html').addClass('ie10'); // detect IE10 version
        }
        if (Genkits.isIE10() || Genkits.isIE9() || Genkits.isIE8()) {
            $('html').addClass('ie'); // detect IE10 version
        }
    };

    /**
     * 运行由Kbylin.addResponsiveHandler方法添加的处理函数
     * @private
     */
    var runResizeHandlers = function() {
        for (var i = 0; i < resizeHandlers.length; i++) resizeHandlers[i].call();
    };

    // handle the layout reinitialization on window resize
    /**
     *
     */
    var handleOnResize = function() {
        var resize;
        var currheight;

        $(window).resize(function() {
            if(Genkits.isIE8()){
                if(currheight == document.documentElement.clientHeight) return ;
                currheight = document.documentElement.clientHeight;
            }
            if (resize) clearTimeout(resize);
            resize = setTimeout(function() {
                runResizeHandlers();
            }, 50); // wait 50ms until window resize finishes.
        });
    };

    // Handlesmaterial design checkboxes
    var handleMaterialDesign = function() {
        if (body.hasClass('page-md')) {
            body.on('click', 'a.btn, button.btn, input.btn, label.btn', function(e) {
                var circle  = body.find(".md-click-circle"), d, x, y;

                if(circle.length == 0) {
                    body.prepend("<span class='md-click-circle'></span>");
                }
                    
                circle.removeClass("md-click-animate");
                
                if(!circle.height() && !circle.width()) {
                    d = Math.max(body.outerWidth(), body.outerHeight());
                    circle.css({height: d, width: d});
                }
                
                x = e.pageX - body.offset().left - circle.width()/2;
                y = e.pageY - body.offset().top - circle.height()/2;
                
                circle.css({top: y+'px', left: x+'px'}).addClass("md-click-animate");
            });
        }
    };


    // Handles Bootstrap Dropdowns
    var handleDropdowns = function() {
        body.on('click', '.dropdown-menu.hold-on-click', function(e) {
            e.stopPropagation();//停止事件的传播
        });
    };


    return {
        //main function to initiate the theme
        init: function() {
            //IMPORTANT!!!: Do not modify the core handlers call order.

            handleInit(); // initialize core variables
            handleOnResize(); // set and handle responsive    

            //UI Component handlers     
            handleMaterialDesign(); // handle material design       
            handleDropdowns(); // handle dropdowns

            // Hacks
            Genkits.fixInputPlaceholderForIE(); //IE8 & IE9 input placeholder issue fix
        },

        //main function to initiate core javascript after ajax complete
        // initAjax: function() {
        //     handleDropdowns(); // handle dropdowns
        // },


        //public function to add callback a function which will be called on window resize
        addResizeHandler: function(func) {
            resizeHandlers.push(func);
        },

        scrollTo: function(el, offeset) {
            var pos = (el instanceof jQuery && el.size() > 0) ? el.offset().top : 0;//不存在该元素时顶部为0

            if (el) {
                if (body.hasClass('page-header-fixed'))  pos = pos - $('.page-header').height();//头部被固定的时候需要减去头部的高度
                pos = pos + (offeset ? offeset : -1 * el.height());
            }

            $('html,body').animate({
                scrollTop: pos
            }, 'slow');
        },
        /**
         * 使得滚动条变细长
         * @param el
         */
        initSlimScroll: function(el) {
            $(el).each(function() {
                if ($(this).attr("data-initialized")) return;//如果初始化过了,直接返回

                //设置高度为data-height的高度,如果存在的话
                var height = $(this).attr("data-height");
                if(!height) height = $(this).height();

                var datavisible = $(this).attr("data-always-visible") == "1";
                $(this).slimScroll({
                    allowPageScroll: true, // allow page scroll when the element scroll is ended
                    size: '7px',
                    color: ($(this).attr("data-handle-color") ? $(this).attr("data-handle-color") : '#bbb'),
                    wrapperClass: ($(this).attr("data-wrapper-class") ? $(this).attr("data-wrapper-class") : 'slimScrollDiv'),
                    railColor: ($(this).attr("data-rail-color") ? $(this).attr("data-rail-color") : '#eaeaea'),
                    height: height,
                    alwaysVisible: datavisible,
                    railVisible: datavisible,
                    disableFadeOut: true
                });

                $(this).attr("data-initialized", "1");//标记已经初始化过了
            });
        },

        destroySlimScroll: function(el) {
            $(el).each(function() {
                if ($(this).attr("data-initialized") === "1") { //只针对初始化过的元素
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
        },

        // function to scroll to the top
        scrollTop: function() {
            Metronic.scrollTo();
        },

        alert: function(options) {

            options = $.extend(true, {
                container: "", // alerts parent container(by default placed after the page breadcrumbs)
                place: "append", // "append" or "prepend" in container 
                type: 'success', // alert's type
                message: "", // alert's message
                close: true, // make alert closable
                reset: true, // close all previouse alerts first
                focus: true, // auto scroll to the alert after shown
                closeInSeconds: 0, // auto close after defined seconds
                icon: "" // put icon before the message
            }, options);

            var id = Genkits.getUniqueID("Metronic_alert");

            var html = '<div id="' + id + '" class="Metronic-alerts alert alert-' + options.type + ' fade in">' + (options.close ? '<button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>' : '') + (options.icon !== "" ? '<i class="fa-lg fa fa-' + options.icon + '"></i>  ' : '') + options.message + '</div>';

            if (options.reset) {
                $('.Metronic-alerts').remove();
            }

            if (!options.container) {
                if (body.hasClass("page-container-bg-solid")) {
                    $('.page-title').after(html);
                } else {
                    var pagebar = $('.page-bar');
                    if (pagebar.size() > 0) {
                        pagebar.after(html);
                    } else {
                        $('.page-breadcrumb').after(html);
                    }
                }
            } else {
                if (options.place == "append") {
                    $(options.container).append(html);
                } else {
                    $(options.container).prepend(html);
                }
            }

            if (options.focus) {
                Metronic.scrollTo($('#' + id));
            }

            if (options.closeInSeconds > 0) {
                setTimeout(function() {
                    $('#' + id).remove();
                }, options.closeInSeconds * 1000);
            }

            return id;
        },

        // To get the correct viewport width based on  http://andylangton.co.uk/articles/javascript/get-viewport-size-javascript/
        getViewPort: function() {
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
        }

    };

}();