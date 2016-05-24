/**
 * 纯js进度条
 * Created by kiner on 15/3/22.
 */

function hasClass(obj, cls) {
    return obj.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));
}

function addClass(obj, cls) {
    if (!this.hasClass(obj, cls)) obj.className += " " + cls;
}

function removeClass(obj, cls) {
    if (hasClass(obj, cls)) {
        var reg = new RegExp('(\\s|^)' + cls + '(\\s|$)');
        obj.className = obj.className.replace(reg, ' ');
    }
}

function toggleClass(obj,cls){
    if(hasClass(obj,cls)){
        removeClass(obj, cls);
    }else{
        addClass(obj, cls);
    }
}

var Progress = (function () {
    var env = this;
    //origin option

    return {
        val:0,
        options : {
            width : 200,
            height: 30,
            bgColor : "#005538",
            proColor : "#009988",
            fontColor : "#FFFFFF",
            val : 10,
            text:"#*val*#%",
            showPresent : true,
            completeCallback:function(){},
            changeCallback:function(){}
        },
        init:function (options) {
            if(options){
                options.width  && (this.options.width =  parseFloat(options.width));
                options.height  && (this.options.height =  parseFloat(options.height));
                options.bgColor  && (this.options.bgColor =  options.bgColor);
                options.proColor  && (this.options.proColor =  options.proColor);
                options.fontColor  && (this.options.fontColor =  options.fontColor);
                (options.showPresent != undefined ) && (this.options.showPresent =  options.showPresent);
                options.completeCallback  && (this.options.completeCallback =  options.completeCallback);
                options.changeCallback  && (this.options.changeCallback =  options.changeCallback);
                options.text  && (this.options.text =  options.text);
                options.val  && (this.options.val =  options.val);
            }
            this.strTemp = this.text.substring(0,this.text.indexOf('#*'))+"{{pro}}"+this.text.substring(this.text.indexOf('*#')+2);

            //do init
            this.proBox = document.createElement('div');
            this.proBg = document.createElement('div');
            this.proPre = document.createElement('div');
            this.proFont = document.createElement('div');

            addClass(this.proBox,'proBox');
            addClass(this.proBg,'proBg');
            addClass(this.proPre,'proPre');
            addClass(this.proFont,'proFont');

            this.proBox.setAttribute("style","width:"+this.w+"px; height:"+this.h+"px; position:relative; overflow:hidden; box-shadow:0 0 5px #FFFFFF; -moz-box-shadow:0 0 5px #FFFFFF; -webkit-box-shadow:0 0 5px #FFFFFF; -o-box-shadow:0 0 5px #FFFFFF;");
            this.proBg.setAttribute("style","background-color:"+this.bgColor+"; position:absolute; z-index:1; width:100%; height:100%; top:0; left:0;");
            this.proPre.setAttribute("style","transition:all 300ms; -moz-transition:all 300ms; -webkit-transition:all 300ms; -o-transition:all 300ms; width:"+this.val+"%; height:100%; background-color:"+this.proColor+"; position:absolute; z-index:2; top:0; left:0;");

            if(this.showPresent){
                this.proFont.setAttribute("style","overflow:hidden;text-overflow:ellipsis; white-space:nowrap; *white-space:nowrap; width:100%; height:100%; color:"+this.fontColor+"; text-align:center; line-height:"+this.h+"px; z-index:3; position:absolute; font-size:12px;");
                var text = this.parseText();
                this.proFont.innerHTML = text;
                this.proFont.setAttribute("title",text);
                this.proBox.appendChild(this.proFont);
            }


            this.proBox.appendChild(this.proBg);
            this.proBox.appendChild(this.proPre);

        },
        refresh : function(){
            this.proPre.style.width = this.val+"%";

            this.proFont.innerHTML = this.parseText();
        },
        parseText : function(){
            this.text = this.strTemp.replace("{{pro}}",this.val);
            return this.text;
        },
        update : function(val){
            this.val = val;
            this.refresh();
            this.changeCallback.call(this,val);
            if(val==100){
                this.completeCallback.call(this,val);
            }
        },
        getBody : function(){
            return this.proBox;
        },
        getVal : function(){
            return this.val;
        },
        getInstance:function (options) {
            if(!instance){
            }
            return instance;
        }
    };
})();
