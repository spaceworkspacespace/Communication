/*!

 @Name：layer mobile v2.0.0 弹层组件移动版
 @Author：贤心
 @Site：http://layer.layui.com/mobie/
 @License：MIT
    
 */layui.define(function(a){"use strict";var b=window,c=document,d=function(a){return c.querySelectorAll(a)},e={type:0,shade:!0,shadeClose:!0,fixed:!0,anim:"scale"//默认动画类型
},f={extend:function(a){var b=JSON.parse(JSON.stringify(e));for(var c in a)b[c]=a[c];return b},timer:{},end:{}};//默认配置
f.touch=function(a,b){a.addEventListener("click",function(a){b.call(this,a)},!1)};var g=0,h=["layui-m-layer"],i=function(a){var b=this;b.config=f.extend(a),b.view()};i.prototype.view=function(){var a=this,b=a.config,e=c.createElement("div");a.id=e.id=h[0]+g,e.setAttribute("class",h[0]+" "+h[0]+(b.type||0)),e.setAttribute("index",g);//标题区域
var f=function(){var a="object"==typeof b.title;return b.title?"<h3 style=\""+(a?b.title[1]:"")+"\">"+(a?b.title[0]:b.title)+"</h3>":""}(),i=function(){"string"==typeof b.btn&&(b.btn=[b.btn]);var a,c=(b.btn||[]).length;return 0!==c&&b.btn?(a="<span yes type=\"1\">"+b.btn[0]+"</span>",2===c&&(a="<span no type=\"0\">"+b.btn[1]+"</span>"+a),"<div class=\"layui-m-layerbtn\">"+a+"</div>"):""}();//按钮区域
if(b.fixed||(b.top=b.hasOwnProperty("top")?b.top:100,b.style=b.style||"",b.style+=" top:"+(c.body.scrollTop+b.top)+"px"),2===b.type&&(b.content="<i></i><i class=\"layui-m-layerload\"></i><i></i><p>"+(b.content||"")+"</p>"),b.skin&&(b.anim="up"),"msg"===b.skin&&(b.shade=!1),e.innerHTML=(b.shade?"<div "+("string"==typeof b.shade?"style=\""+b.shade+"\"":"")+" class=\"layui-m-layershade\"></div>":"")+"<div class=\"layui-m-layermain\" "+(b.fixed?"":"style=\"position:static;\"")+"><div class=\"layui-m-layersection\"><div class=\"layui-m-layerchild "+(b.skin?"layui-m-layer-"+b.skin+" ":"")+(b.className?b.className:"")+" "+(b.anim?"layui-m-anim-"+b.anim:"")+"\" "+(b.style?"style=\""+b.style+"\"":"")+">"+f+"<div class=\"layui-m-layercont\">"+b.content+"</div>"+i+"</div></div></div>",!b.type||2===b.type){var k=c.getElementsByClassName(h[0]+b.type),l=k.length;1<=l&&j.close(k[0].getAttribute("index"))}document.body.appendChild(e);var m=a.elem=d("#"+a.id)[0];b.success&&b.success(m),a.index=g++,a.action(b,m)},i.prototype.action=function(a,b){var c=this;//自动关闭
a.time&&(f.timer[c.index]=setTimeout(function(){j.close(c.index)},1e3*a.time));//确认取消
var d=function(){var b=this.getAttribute("type");0==b?(a.no&&a.no(),j.close(c.index)):a.yes?a.yes(c.index):j.close(c.index)};if(a.btn)for(var e=b.getElementsByClassName("layui-m-layerbtn")[0].children,g=e.length,h=0;h<g;h++)f.touch(e[h],d);//点遮罩关闭
if(a.shade&&a.shadeClose){var i=b.getElementsByClassName("layui-m-layershade")[0];f.touch(i,function(){j.close(c.index,a.end)})}a.end&&(f.end[c.index]=a.end)};var j={v:"2.0 m",index:g,//核心方法
open:function(a){var b=new i(a||{});return b.index},close:function(a){var b=d("#"+h[0]+a)[0];b&&(b.innerHTML="",c.body.removeChild(b),clearTimeout(f.timer[a]),delete f.timer[a],"function"==typeof f.end[a]&&f.end[a](),delete f.end[a])},//关闭所有layer层
closeAll:function(){for(var a=c.getElementsByClassName(h[0]),b=0,d=a.length;b<d;b++)j.close(0|a[0].getAttribute("index"))}};a("layer-mobile",j)});