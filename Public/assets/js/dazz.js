/**
 * 原生javascript类库
 * typeof 运算符把类型信息当作字符串返回。typeof 返回值有六种可能：
 *  "number," "string," "boolean," "object," "function," 和 "undefined."
 * Created by kbylin on 5/14/16.
 */
window.dazz = (function () {
    "use strict";

    //常见的兼容性问题处理
    //处理console对象缺失
    !window.console &&  (window.console = (function(){var c = {}; c.log = c.warn = c.debug = c.info = c.error = c.time = c.dir = c.profile = c.clear = c.exception = c.trace = c.assert = function(){}; return c;})());
    //解决IE8不支持indexOf方法的问题
    if (!Array.prototype.indexOf){
        Array.prototype.indexOf = function(elt){
            var len = this.length >>> 0;
            var from = Number(arguments[1]) || 0;
            from = (from < 0) ? Math.ceil(from) : Math.floor(from);
            if (from < 0) from += len;
            for (; from < len; from++) {
                if (from in this && this[from] === elt) return from;
            }
            return -1;
        };
    }

    var options = {
        //公共资源目录
        'public_url':'',
        //自动加载路径
        'auto_url':'',
        //debug模式
        'debug_on':false
    };
    var readyStack = [];
    //加载的类库
    var _library = [];

    var pathen = function (path,autoload) {
        if((path.length > 4) && (path.substr(0,4) !== 'http')){
            if(autoload){
                if(!options['auto_url']) options['auto_url'] = '/auto/';//throw "Public uri not defined!";
                path = options['auto_url']+path;
            }else{
                if(!options['public_url']) options['public_url'] = '/';//throw "Public uri not defined!";
                path = options['public_url']+path;
            }
        }
        return path;
    };
    var load = function (path,type) {
        if(typeof path === 'object'){
            for(var x in path){
                if(!path.hasOwnProperty(x)) continue;
                load(path[x]);
            }
        }else{
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
                        //自动类加载
                        type = 'auto_js';
                }
            }
            //本页面加载过将不再重新载入
            for(var i = 0; i < _library.length; i++) if(_library[i] === path) return;
            //现仅仅支持css,js,ico的类型
            switch (type){
                case 'css':
                    document.write('<link href="'+pathen(path)+'" rel="stylesheet" type="text/css" />');
                    break;
                case 'js':
                    document.write('<script src="'+pathen(path)+'"  /></script>');
                    break;
                case 'ico':
                    document.write('<link rel="shortcut icon" href="'+pathen(path)+'" />');
                    break;
                case 'auto_js':
                    document.write('<script src="'+pathen(path,true)+'"  /></script>');
                    break;
                default:
                    return;
            }
            //记录已经加载过的
            _library.push(path);
        }
        return dazz;
    };

    //上下文环境相关的信息
    var context = {
        //获得可视区域的大小
        getViewPort:function () {
            var win = window;
            var type = 'inner';
            if (!('innerWidth' in window)) {
                type = 'client';
                win = document.documentElement ?document.documentElement: document.body;
            }
            return {
                width: win[type + 'Width'],
                height: win[type + 'Height']
            };
        },
        //获取浏览器信息 返回如 Object {type: "Chrome", version: "50.0.2661.94"}
        getBrowserInfo:function () {
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
        },
        /**
         * 月(M)、日(d)、小时(h)、分(m)、秒(s)、季度(q) 可以用 1-2 个占位符,年(y)可以用 1-4 个占位符，毫秒(S)只能用 1 个占位符(是 1-3 位的数字)
         * @param fmt
         * @returns {*}
         */
        date:function(fmt){ //author: meizz
            if(!fmt) fmt = "yyyy-MM-dd hh:mm:ss.S";//2006-07-02 08:09:04.423
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
        },
        //如果是非IE浏览器,返回的版本号是11
        ieVersion:function () {
            //IE判断
            var version;
            if(version = navigator.userAgent.toLowerCase().match(/msie ([\d.]+)/)){
                version = parseInt(version[1]);
            }else{
                version = 12;
            }
            return version;
        }
    };

    var utils = {
        /**
         * 原先的设计是在Object中添加一个方法,但与jquery的遍历难兼容
         *  Object.prototype.utils
         * 避免发生错误修改为参数加返回值的设计
         * @param options {{}}
         * @param config {{}}
         * @param covermode
         * @returns {*}
         */
        initOption:function (options,config,covermode) {
            for(var x in config){
                if(!config.hasOwnProperty(x)) continue;
                if(covermode || options.hasOwnProperty(x)) options[x] = config[x];
            }
            return options;
        },
        /**
         * 随机获取一个GUID
         * @returns {string}
         */
        guid:function() {
            var s = [];
            var hexDigits = "0123456789abcdef";
            for (var i = 0; i < 36; i++) {
                s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
            }
            s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
            s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
            s[8] = s[13] = s[18] = s[23] = "-";

            return s.join("");
        },
        /**
         * PHP中的parse_url 的javascript实现
         * @param str json字符串
         * @returns {Object}
         */
        parseUrl:function (str) {
            var obj = {};
            if(!str) return obj;

            str = decodeURI(str);
            var arr = str.split("&");
            for(var i=0;i<arr.length;i++){
                var d = arr[i].split("=");
                obj[d[0]] = d[1]?d[1]:'';
            }
            return obj;
        },
        /**
         * 判断是否是Object类的实例,也可以指定参数二来判断是否是某一个类的实例
         * 例如:isObject({}) 得到 [object Object] isObject([]) 得到 [object Array]
         * @param obj
         * @param classname
         * @returns {boolean}
         */
        isObject:function (obj,classname) {
            if(undefined === classname){
                return obj instanceof Object;
            }
            return Object.prototype.toString.call(obj) === '[object '+classname+']';
        },
        /**
         * 判断一个元素是否是数组
         * @param el
         * @returns {boolean}
         */
        isArray  : function (el) {
            return Object.prototype.toString.call(el) === '[object Array]';
        },

        //注意安全性问题,并不推荐使用
        toObject:function (str) {
            if(str instanceof Object) return str;/* 已经是对象的清空下直接返回 */
            return eval ("(" + str + ")");//将括号内的表达式转化为对象而不是作为语句来处理
        },
        /**
         * 遍历对象
         * @param object {{}|[]} 待遍历的对象或者数组
         * @param itemcallback
         * @param userdata
         */
        each:function (object,itemcallback,userdata) {
            if(this.isArray(object)){
                for(var i=0; i < object.length; i++){
                    itemcallback(object[i],i,userdata);
                }
                return ;
            }else if(this.isObject(object)){
                for(var key in object){
                    if(!object.hasOwnProperty(key)) continue;
                    itemcallback(object[key],key,userdata);
                }
                return ;
            }
            throw "Require an object/array!";
        },
        /**
         * 复制一个数组或者对象
         * @param array 要拷贝的数组或者对象
         * @param isObject bool 是否是对象
         * @returns array|{}
         */
        copy:function (array,isObject) {
            var kakashi;
            if(!isObject){
                kakashi = [];
                for(var i =0;i < array.length;i++){
                    kakashi[i] = array[i];
                }
            }else{
                kakashi = {};
                utils.each(array,function (item,key) {
                    kakashi[key] = item;
                });
            }
            return kakashi;
        },
        /**
         * 检查对象是否有指定的属性
         * @param object {{}}
         * @param prop 属性数组
         * @return int 返回1表示全部属性都拥有,返回0表示全部都没有,部分有的清空下返回-1
         */
        checkProperty:function (object, prop) {
            if(!utils.isArray(prop)) prop = [prop];
            if(undefined === prop) throw "Arguments should not be empty!";
            var count = 0;
            for(var i = 0; i < prop.length;i++){
                if(object.hasOwnProperty(prop[i])) count++;
            }
            if(count === prop.length) return 1;
            else if(count === 0) return 0;
            else return -1;
        }
    };



    //监听窗口状态变化
    window.document.onreadystatechange = function(){
        if( window.document.readyState === "complete" ){
            if(readyStack.length){
                for(var i=0;i<readyStack.length;i++) {
                    // console.log(callback)
                    (readyStack[i])();
                }
            }
        }
    };
    return {
        context:context,
        utils:utils,
        //获取一个单例的操作对象作为上下文环境的深度拷贝
        newInstance:function (context) {
            var Yan = function () {return {target:null};};
            var instance = new Yan();
            if(context){
                utils.each(context,function (item,key) {
                    instance[key] = item;
                });
            }
            return instance;
        },
        load:load,
        init:function (config) {
            utils.each(config,function (item,key) {
                options.hasOwnProperty(key) && (options[key] = item);
            });
            return this;
        },
        ready:function (callback) {
            readyStack.push(callback);
        }
    };
})();