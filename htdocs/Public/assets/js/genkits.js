/**
 * Created by Zhonghuang on 2016/4/15.
 */

/**
 * 系统订制的ajax方法
 */
jQuery.each( [ "iGet", "iPost" ], function( i, method ) {
    /**
     * http://www.w3school.com.cn/jquery/ajax_ajax.asp
     * @param url
     * @param data
     * @param callback
     * @param handler 信息处理回调函数，该回调返回true表示处理完成并直接返回
     * @returns {*}
     */
    jQuery[ method ] = function( url, data, callback, handler ) {

        return jQuery.ajax({
            url: url,
            type: method.substring(1).toLowerCase(),
            data: data,
            success:function (data,status) {
                if("object" === typeof data && "_type" in data){
                    /* 是对象并且带有'_type'属性 则认定为后台返回消息 */
                    if(undefined !== handler){
                        if(true === handler(data)) return ;
                    }else{
                        alert(data["_message"]);
                        return ;
                    }
                }
                callback(data,status);
            }
        });
    };
});

/**
 * 通用工具
 * @type {{}}
 */
var Genkits = {

    getUniqueID: function() {
        return 'prefix_' + Math.floor(Math.random() * (new Date()).getTime());
    },
    /**
     * 获取响应断点
     * @param size
     * @returns {number}
     */
    getResponsiveBreakpoint: function(size) {
        // bootstrap responsive breakpoints
        var sizes = {
            'xs' : 480,     // extra small
            'sm' : 768,     // small
            'md' : 992,     // medium
            'lg' : 1200     // large
        };

        return sizes[size] ? sizes[size] : 0;
    },
    isIE8 : function () {
        return !!navigator.userAgent.match(/MSIE 8.0/);
    },
    isIE9 : function () {
        return !!navigator.userAgent.match(/MSIE 9.0/);
    },
    isIE10 : function () {
        return !!navigator.userAgent.match(/MSIE 10.0/);
    },
    /**
     * 修复IE8、9下的Placeholder的支持
     */
    fixInputPlaceholder4IE :function() {
        //fix html5 placeholder attribute for ie7 & ie8
        if (this.isIE8 || this.isIE9()) { // ie8 & ie9
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
    }

};