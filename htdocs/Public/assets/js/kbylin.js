/**
 * Created by kbylin on 5/10/16.
 */
var Kbylin = (function (public_url) {

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

    var configuration = {
        //公共资源目录
        'public_url':''

    };
    /**
     * PHP中的parse_url 的javascript实现
     * @param str
     * @returns {{}}
     */
    var parseStr = function (str) {
        var obj = {};
        if(!str) return obj;

        str = decodeURI(str);
        var arr = str.split("&");
        for(var i=0;i<arr.length;i++){
            var d = arr[i].split("=");
            obj[d[0]] = d[1]?d[1]:'';
        }
        return obj;
    };

    var writeMetas = function () {
        //TODO:写入meta信息,否!!!
        //TODO:网络爬虫看到的页面将是一堆JS代码,而不是实际的meta标签
    };

    var load = function (path,type) {
        if(typeof path === 'object'){
            for (var i = 0; i < path.length; i++) load(path[i],type);
        }else{
            // if(!configuration['public_url']) throw "Public uri not defined!";
            if(undefined === type){
//                    console.log(path.substring(path.length-3));
                switch (path.substring(path.length-3)){
                    case 'css':
                        type = 'css';
                        break;
                    case '.js':
                        type = 'js';
                        break;
                    case 'ico':
                        type = 'ico';
                        break;
                    default:
                        throw "Invalid path";
                }

            }
            if(path.substr(0,4) !== 'http'){
                path = configuration['public_url']+path;
            }
            switch (type){
                case 'css':
                    document.write('<link href="'+path+'" rel="stylesheet" type="text/css" />');
                    break;
                case 'js':
                    document.write('<scri'+'pt src="'+path+'"></s'+'cript>');
                    break;
                case 'ico':
                    document.write('<link rel="shortcut icon" href="'+path+' />');
                    break;
                default:
                    throw "Undefained type";
            }
        }
        return Kbylin;
    };

    /**
     * 如果是非IE浏览器,返回的版本号是11
     * @returns int
     */
    var ieVersion = function () {
        //IE判断
        var version;
        if(version = navigator.userAgent.toLowerCase().match(/msie ([\d.]+)/)){
            version = parseInt(version[1]);
        }else{
            version = 12;
        }
        return version;
    };

    /**
     * 获取浏览器信息
     * Object {type: "Chrome", version: "50.0.2661.94"}
     * @returns {{}}
     */
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


    var str2Obj = function (str) {
        if(str instanceof Object)return str;/* 已经是对象的清空下直接返回
         由于json是以”{}”的方式来开始以及结束的，在JS中，它会被当成一个语句块来处理，所以必须强制性的将它转换成一种表达式。
         加上圆括号的目的是迫使eval函数在处理JavaScript代码的时候强制将括号内的表达式（expression）转化为对象，而不是作为语句（statement）来执行
         */
        return eval ("(" + str + ")");
    };


    /**
     * 判断一个元素是否是数组
     * @param el
     * @returns {boolean}
     */
    var isArray  = function (el) {
        return Object.prototype.toString.call(el) === '[object Array]';
    };
    /**
     * 判断是否是Object类的实例,也可以指定参数二来判断是否是某一个类的实例
     *
     * 测试代码:
     * var obj = new Date();
     * console.log(Object.prototype.toString.call(obj), // Date
     * Kbylin.isObject(obj,'Date')   // true
     *
     * @param obj 对象
     * @param classname 类名称
     * @returns {boolean}
     */
    var isObject  =function (obj,classname) {
        if(undefined === classname){
            return typeof obj === 'object';
        }else{
            return Object.prototype.toString.call(obj) === '[object '+classname+']';
        }
    };
    /**
     * 对Date的扩展，将 Date 转化为指定格式的String
     * 月(M)、日(d)、小时(h)、分(m)、秒(s)、季度(q) 可以用 1-2 个占位符，
     * 年(y)可以用 1-4 个占位符，毫秒(S)只能用 1 个占位符(是 1-3 位的数字)
     * 例子：
     * (new Date()).format("yyyy-MM-dd hh:mm:ss.S") ==> 2006-07-02 08:09:04.423
     * (new Date()).format("yyyy-M-d h:m:s.S")      ==> 2006-7-2 8:9:4.18
     */
    var formatDate = function(fmt){ //author: meizz
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
        for(var k in o){
            if(!o.hasOwnProperty(k)) continue;
            if(new RegExp("("+ k +")").test(fmt))
                fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));
        }

        return fmt;
    };

    return {
        'isArray':isArray,
        'isObject':isObject,
        'init':function (config) {
            for(var i in config){
                if(!config.hasOwnProperty(i) || !configuration.hasOwnProperty(i)) continue;
                configuration[i] = config[i];
            }
            return Kbylin;
        },
        'load':load,
        'getBrowserInfo':getBrowserInfo,
        'ieVersion':ieVersion,
        'str2Obj':str2Obj,
        //格式化日期
        'date':formatDate,
        'parseStr':parseStr
    };
})();