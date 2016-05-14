/**
 * Created by kbylin on 5/14/16.
 */

var nestable = function () {
    "use strict";
  return {
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
  };
}();
