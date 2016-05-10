/**
 * Created by kbylin on 5/10/16.
 */
var Kbylin = (function (public_url) {

    var configuration = {
        //公共资源目录
        'public_url':null

    };


    var load = function (path,type) {
        if(typeof path === 'object'){
            for (var i = 0; i < path.length; i++) load(path[i],type);
        }else{
            if(!configuration['public_url']) throw "Public uri not defined!";
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
            switch (type){
                case 'css':
                    document.write('<link href="'+configuration['public_url']+path+'" rel="stylesheet" type="text/css" />');
                    break;
                case 'js':
                    document.write("<scri"+"pt src=\""+configuration['public_url']+path+"\"></s"+"cript>");
                    break;
                case 'ico':
                    document.write("<link rel=\"shortcut icon\" href=\""+configuration['public_url']+path+" />");
                    break;
                default:
                    throw "Undefained type";
            }
        }
    };

    /**
     * 如果是非IE浏览器,返回的版本号是0
     * @returns int
     */
    var ieVersion = function () {
        //IE判断
        var version;
        if(version = navigator.userAgent.toLowerCase().match(/msie ([\d.]+)/)){
            version = parseInt(version[1]);
        }else{
            version = 0;
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

    return {
        'init':function (config) {
            for(var i in config){
                if(!config.hasOwnProperty(i) || !configuration.hasOwnProperty(i)) continue;
                configuration[i] = config[i];
            }
            return Kbylin;
        },
        'load':load,
        'getBrowserInfo':getBrowserInfo,
        'ieVersion':ieVersion
    };
})();