/*!

 @Title: layui.upload 单文件上传 - 全浏览器兼容版
 @Author: 贤心
 @License：MIT

 */layui.define(["layer-mobile","zepto"],function(a){"use strict";var b=layui.zepto,c=layui["layer-mobile"],d=layui.device(),f="layui-upload-enter",g="layui-upload-iframe",h={icon:2,shift:6},i={file:"\u6587\u4EF6",video:"\u89C6\u9891",audio:"\u97F3\u9891"};c.msg=function(a){return c.open({content:a||"",skin:"msg",time:2//2秒后自动关闭
})};var j=function(a){this.options=a};//初始化渲染
//提交上传
//暴露接口
j.prototype.init=function(){var a=this,c=a.options,d=b("body"),e=b(c.elem||".layui-upload-file"),h=b("<iframe id=\""+g+"\" class=\""+g+"\" name=\""+g+"\"></iframe>");return b("#"+g)[0]||d.append(h),e.each(function(d,e){e=b(e);var h="<form target=\""+g+"\" method=\""+(c.method||"post")+"\" key=\"set-mine\" enctype=\"multipart/form-data\" action=\""+(c.url||"")+"\"></form>",j=e.attr("lay-type")||c.type;c.unwrap||(h="<div class=\"layui-box layui-upload-button\">"+h+"<span class=\"layui-upload-icon\"><i class=\"layui-icon\">&#xe608;</i>"+(e.attr("lay-title")||c.title||"\u4E0A\u4F20"+(i[j]||"\u56FE\u7247"))+"</span></div>"),h=b(h),c.unwrap||h.on("dragover",function(a){a.preventDefault(),b(this).addClass(f)}).on("dragleave",function(){b(this).removeClass(f)}).on("drop",function(){b(this).removeClass(f)}),e.parent("form").attr("target")===g&&(c.unwrap?e.unwrap():(e.parent().next().remove(),e.unwrap().unwrap())),e.wrap(h),e.off("change").on("change",function(){a.action(this,j)})})},j.prototype.action=function(a,d){var e=this,f=e.options,i=a.value,j=b(a),k=j.attr("lay-ext")||f.ext||"";//获取支持上传的文件扩展名;
if(i){//校验文件
switch(d){case"file"://一般文件
if(k&&!RegExp("\\w\\.("+k+")$","i").test(escape(i)))return c.msg("\u4E0D\u652F\u6301\u8BE5\u6587\u4EF6\u683C\u5F0F",h),a.value="";break;case"video"://视频文件
if(!RegExp("\\w\\.("+(k||"avi|mp4|wma|rmvb|rm|flash|3gp|flv")+")$","i").test(escape(i)))return c.msg("\u4E0D\u652F\u6301\u8BE5\u89C6\u9891\u683C\u5F0F",h),a.value="";break;case"audio"://音频文件
if(!RegExp("\\w\\.("+(k||"mp3|wav|mid")+")$","i").test(escape(i)))return c.msg("\u4E0D\u652F\u6301\u8BE5\u97F3\u9891\u683C\u5F0F",h),a.value="";break;default://图片文件
if(!RegExp("\\w\\.("+(k||"jpg|png|gif|bmp|jpeg")+")$","i").test(escape(i)))return c.msg("\u4E0D\u652F\u6301\u8BE5\u56FE\u7247\u683C\u5F0F",h),a.value="";}f.before&&f.before(a),j.parent().submit();var l=b("#"+g),m=setInterval(function(){var b;try{b=l.contents().find("body").text()}catch(a){c.msg("\u4E0A\u4F20\u63A5\u53E3\u5B58\u5728\u8DE8\u57DF",h),clearInterval(m)}if(b){clearInterval(m),l.contents().find("body").html("");try{b=JSON.parse(b)}catch(a){return b={},c.msg("\u8BF7\u5BF9\u4E0A\u4F20\u63A5\u53E3\u8FD4\u56DEJSON\u5B57\u7B26",h)}"function"==typeof f.success&&f.success(b,a)}},30);a.value=""}},a("upload-mobile",function(a){var b=new j(a=a||{});b.init()})});