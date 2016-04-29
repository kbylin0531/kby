/**
 * Created by Linzh on 2016/1/28.
 * Email:linzhv@qq.com
 *
 * 引入：
 *  <script src="__ROOT__/thirdparty/jquery/js/Shadow.js"></script>
 * 使用：
 *  var shadow = new Shadow({'text':'后台处理中...'});
 *  shadow.show(); // 显示
 *  shadow.hide(); // 隐藏
 */
var Shadow = function(){
    var config =  {
        //alpha:0.5,
        background: 'gray',
        text:'Loading'
    };
    var newconf = arguments[0];
    for(var x in newconf){
        if(x in config){
            config[x] = newconf[x];
        }
    }

    var env = this;

    var instance = $(
        "<div id='__Loading' style='position:absolute;z-index: 1000; top: 0px; left: 0px;filter:alpha(opacity=50);-moz-opacity:0.5;opacity:0.5;width: 100%; height: 100%;text-align: center; background: "+config['background']+"; '>" +
            "<h1 style='top: 48%; position: relative;'>" +
                "<span style='color:blue;font-size:15px' >"+config['text']+"</span>" +
            "</h1>" +
        "</div>");
    instance.appendTo($("body"));
    instance.fadeOut();

    env.show = function () {
        instance.fadeIn();
    };

    env.hide = function () {
        instance.fadeOut();
    };

};

