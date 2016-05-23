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
    if (!Array.prototype.max){
        Array.prototype.max = function(){
            return Math.max.apply({},this)
        };
    }
    if (!Array.prototype.min){
        Array.prototype.min = function(){
            return Math.min.apply({},this)
        };
    }

    if (!String.prototype.trim){
        String.prototype.trim=function()    {
            return this.replace(/(^\s*)|(\s*$)/g,'');
        };
    }
    if (!String.prototype.ltrim){
        String.prototype.ltrim=function()    {
            return this.replace(/(^\s*)/g,'');
        };
    }
    if (!String.prototype.rtrim){
        String.prototype.rtrim=function()    {
            return this.replace(/(\s*$)/g,'');
        };
    }


    Object.dazz = (function () {
        return {};
    })();

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
        /**
         * get the hash of uri
         * @returns {string}
         */
        getHash:function () {
            if(!location.hash) return "";
            var hash = location.hash;
            var index = hash.indexOf('#');
            if(index >= 0) hash = hash.substring(index+1);
            return ""+hash;
        },
        /**
         * get script path
         * there are some diffrence between domain access(virtual machine) and ip access of href
         * domian   :http://192.168.1.29:8085/edu/Public/admin.php/Admin/System/Menu/PageManagement#dsds
         * ip       :http://edu.kbylin.com:8085/admin.php/Admin/System/Menu/PageManagement#dsds
         * what we should do is SPLIT '.php' from href
         *
         * ps:location.hash
         */
        getBaseUri:function () {
            var href = location.href;
            var index = href.indexOf('.php');
            if(index > 0 ){//exist
                return href.substring(0,index+4);
            }else{
                if(location.origin){
                    return location.origin;
                }else{
                    return location.protocol+"//"+location.host;
                }
            }
        },
        //get real path to this action
        getPath:function () {
            var path = location.pathname;
            // var path = "/admin.php/Syse/dsds/dsdsds#dsds";
            var index = path.indexOf('.php');
            if(index >= 0 ) path = path.substring(index+4);
            index = path.indexOf('?');
            if(index >= 0) path = path.substring(0,index);
            index = path.indexOf("#");
            if(index >= 0) path = path.substring(0,index);
            // console.log(index,location.pathname,path);
            //trim '/'
            var startindex = 0;
            for(var i = 0 ; i < path.length; i ++){
                if(path[i] === '/' || path[i] === ' '){
                    startindex ++;
                    continue;
                }else{
                    break;
                }
            }

            return "/"+path.substring(startindex);
        },
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
        cookie:{
            /**
             * set cookie
             * @param name
             * @param value
             * @param expire
             * @param path
             */
            set:function (name, value, expire,path) {
                path = ";path="+(path ? path : '/');// all will access if not set the path
                var cookie;
                if(undefined === expire || false === expire){
                    //set or modified the cookie, and it will be remove while leave from browser
                    cookie = name+"="+value;
                }else if(!isNaN(expire)){// is numeric
                    var _date = new Date();//current time
                    if(expire > 0){
                        _date.setTime(_date.getTime()+expire);//count as millisecond
                    }else if(expire === 0){
                        _date.setDate(_date.getDate()+365);//expire after an year
                    }else{
                        //delete cookie while expire < 0
                        _date.setDate(_date.getDate()-1);//expire after an year
                    }
                    cookie = name+"="+value+";expires="+_date.toUTCString();
                }else{
                    throw "require the param 3 to be false/undefined/numeric !";
                }
                document.cookie = cookie+path;
            },
            //get a cookie with a name
            get:function (name) {
                if(document.cookie.length > 0){
                    var cstart = document.cookie.indexOf(name+"=");
                    if(cstart >= 0){
                        cstart = cstart + name.length + 1;
                        var cend = document.cookie.indexOf(';',cstart);//begin from the index of param 2
                        (-1 === cend) && (cend = document.cookie.length);
                        return document.cookie.substring(cstart,cend);
                    }
                }
                return "";
            }
        },
        //TODO:wait for test
        setOpacity: function(ele, opacity) {
            if (ele.style.opacity != undefined) {
                ///兼容FF和GG和新版本IE
                ele.style.opacity = opacity / 100;
            } else {
                ///兼容老版本ie
                ele.style.filter = "alpha(opacity=" + opacity + ")";
            }
        },
        //TODO:wait for test
        fadein:function (ele, opacity, speed) {
            if (ele) {
                var v = ele.style.filter.replace("alpha(opacity=", "").replace(")", "") || ele.style.opacity;
                (v < 1) && (v *= 100);
                var count = speed / 1000;
                var avg = count < 2 ? (opacity / count) : (opacity / count - 1);
                var timer = null;
                timer = setInterval(function() {
                    if (v < opacity) {
                        v += avg;
                        this.setOpacity(ele, v);
                    } else {
                        clearInterval(timer);
                    }
                }, 500);
            }
        },
        //TODO:wait for test
        fadeout:function (ele, opacity, speed) {
            if (ele) {
                var v = ele.style.filter.replace("alpha(opacity=", "").replace(")", "") || ele.style.opacity || 100;
                v < 1 && (v *= 100);
                var count = speed / 1000;
                var avg = (100 - opacity) / count;
                var timer = null;
                timer = setInterval(function() {
                    if (v - avg > opacity) {
                        v -= avg;
                        this.setOpacity(ele, v);
                    } else {
                        clearInterval(timer);
                    }
                }, 500);
            }
        },
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

    //TODO:wait for test
    var Shadow = (function () {
        var convention =  {
            //alpha:0.5,
            background: 'gray',
            text:'Loading',
            opacity:50,
            fontcolor:'blue',
            fontsize:'15px'
        };
        return {
            instance:null,
            init:function (config) {
                if(config || !this.instance){
                    config && utils.each(config,function (value,key) {
                        convention[key] = value;
                    });
                    this.instance = document.createElement('div');
                    // this.instance.style.display = 'none';
                    this.instance.style.position = 'absolute';
                    this.instance.style.zIndex = '1000';
                    this.instance.style.top = '0px';
                    this.instance.style.left = '0px';
                    this.instance.style.filter = 'alpha(opacity:'+convention['opacity']+')'; //设置IE的透明度
                    this.instance.style.opacity = convention['opacity'] / 100; //设置fierfox等透明度，注意透明度值是小数
                    this.instance.style.mozOpacity = convention['opacity'] / 100;
                    this.instance.style.width = '100%';
                    this.instance.style.height = '100%';
                    this.instance.style.textAlign = 'center';
                    this.instance.style.background = convention['background'];
                    var h1 = document.createElement('h1');
                    h1.style.top = '48%';
                    h1.style.position = 'relative';
                    var span = document.createElement('span');
                    span.style.color = convention['fontcolor'];
                    span.style.fontSize = convention['fontsize'];
                    span.innerText = convention['text'];
                    h1.appendChild(span);
                    this.instance.appendChild(h1);
                    document.body.appendChild(this.instance);
                }
            },
            show:function (config) {
                this.init(config);
                console.log(this.instance)
                utils.fadein(this.instance,50,1000);
            },
            hide:function (config) {
                this.init(config);
                utils.fadeout(this.instance,50,1000);
            }
        };
    })();

    //监听窗口状态变化
    window.document.onreadystatechange = function(){
        // console.log(window.document.readyState);
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
        shadow:Shadow,
        newElement:function (regular) {
            var tagname  = regular, classes, id;
            if(regular.indexOf('.') > 0 ){
                classes = regular.split(".");
                regular = classes.shift();
            }
            if(regular.indexOf("#") > 0){
                var tempid = regular.split("#");
                tagname = tempid[0];
                id = tempid[1];
            }else{
                tagname = regular
            }
            var element = document.createElement(tagname);
            id && element.setAttribute('id',id);
            if(classes){
                var ct = '';
                for (var i =0;i <classes.length; i++){
                    ct += classes[i];
                    if(i !== classes.length - 1)  ct += ",";
                }
                element.setAttribute('class',ct);
            }
        },
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
            // console.log(readyStack)
        }
    };
})();