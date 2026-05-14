var haccordion={ajaxloadingmsg:'<div style="margin: 1em; font-weight: bold"><img src="ajaxloadr.gif" style="vertical-align: middle" /></div>',ismobile:navigator.userAgent.match(/(iPad)|(iPhone)|(iPod)|(android)|(webOS)/i)!=null,accordioninfo:{},expandli:function(accordionid,targetli){var config=haccordion.accordioninfo[accordionid]
var $targetli=(typeof targetli=="number")?config.$targetlis.eq(targetli):(typeof targetli=="string")?jQuery('#'+targetli):jQuery(targetli)
if(typeof config.$lastexpanded!="undefined")config.$lastexpanded.stop().animate({width:config.paneldimensions.peekw},config.speed)
$targetli.stop().animate({width:$targetli.data('hpaneloffsetw')},config.speed)
config.$lastexpanded=$targetli},urlparamselect:function(accordionid){var result=window.location.search.match(new RegExp(accordionid+"=(\\d+)","i"))
if(result!=null)result=parseInt(RegExp.$1)+""
return result},getCookie:function(Name){var re=new RegExp(Name+"=[^;]+","i")
if(document.cookie.match(re))return document.cookie.match(re)[0].split("=")[1]
return null},setCookie:function(name,value){document.cookie=name+"="+value+"; path=/"},loadexternal:function($,config){var $hcontainer=$('#'+config.ajaxsource.container).html(this.ajaxloadingmsg)
$.ajax({url:config.ajaxsource.path,async:true,error:function(ajaxrequest){$hcontainer.html('Error fetching content.<br />Server Response: '+ajaxrequest.responseText)},success:function(content){$hcontainer.html(content)
haccordion.init($,config)}})},init:function($,config){haccordion.accordioninfo[config.accordionid]=config
var $targetlis=$('#'+config.accordionid).find('ul:eq(0) > li')
config.$targetlis=$targetlis
config.selectedli=config.selectedli||[]
config.speed=config.speed||"normal"
$targetlis.each(function(i){var $target=$(this).data('pos',i)
$target.data('hpaneloffsetw',$target.find('.hpanel:eq(0)').outerWidth())
$target[haccordion.ismobile?"click":"mouseenter"](function(){haccordion.expandli(config.accordionid,this)
config.$lastexpanded=$(this)})
if(config.collapsecurrent){$target.mouseleave(function(){$(this).stop().animate({width:config.paneldimensions.peekw},config.speed)})}})
var selectedli=haccordion.urlparamselect(config.accordionid)||((config.selectedli[1]&&haccordion.getCookie(config.accordionid))?parseInt(haccordion.getCookie(config.accordionid)):config.selectedli[0])
selectedli=parseInt(selectedli)
if(selectedli>=0&&selectedli<config.$targetlis.length){config.$lastexpanded=$targetlis.eq(selectedli)
config.$lastexpanded.css('width',config.$lastexpanded.data('hpaneloffsetw'))}$(window).bind('unload',function(){haccordion.uninit($,config)})},uninit:function($,config){var $targetlis=config.$targetlis
var expandedliindex=-1
$targetlis.each(function(){var $target=$(this)
$target.unbind()
if($target.width()==$target.data('hpaneloffsetw'))expandedliindex=$target.data('pos')})
if(config.selectedli[1]==true)haccordion.setCookie(config.accordionid,expandedliindex)},setup:function(config){document.write('<style type="text/css">\n')
document.write('#'+config.accordionid+' li{width: '+config.paneldimensions.peekw+';\nheight: '+config.paneldimensions.h+';\n}\n')
document.write('#'+config.accordionid+' li .hpanel{width: '+config.paneldimensions.fullw+';\nheight: '+config.paneldimensions.h+';\n}\n')
document.write('<\/style>')
jQuery(document).ready(function($){if(config.ajaxsource)haccordion.loadexternal($,config)
else
haccordion.init($,config)})}}