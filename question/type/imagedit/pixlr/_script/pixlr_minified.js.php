<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nadav Kavavalerchik
 * Date: 1/8/11
 * Time: 5:54 PM
 * support multi instance of question type imagedit
 */
 ?>
        
var pixlr<?php echo $_GET['instanceid']; ?>=function(){function windowSize(){var w=0,h=0;if(!(document.documentElement.clientWidth==0)){w=document.documentElement.clientWidth;h=document.documentElement.clientHeight;}
else{w=document.body.clientWidth;h=document.body.clientHeight;}
return{width:w,height:h};}
function extend(settings,options){var mashup={};for(var attribute in settings){mashup[attribute]=settings[attribute];}
for(var attribute in options){mashup[attribute]=options[attribute];}
return mashup;}
function buildUrl(opt){var url='';for(var attribute in opt){if(attribute!='service')url+=((url!='')?"&":"?")+attribute+"="+escape(opt[attribute]);}
return'http://pixlr.com/'+opt.service+'/'+url;}
var bo={ie:window.ActiveXObject,ie6:window.ActiveXObject&&(document.implementation!=null)&&(document.implementation.hasFeature!=null)&&(window.XMLHttpRequest==null),quirks:document.compatMode==='BackCompat'}
return{settings:{'service':'editor'},overlay:{show:function(options){var opt=extend(pixlr<?php echo $_GET['instanceid']; ?>.settings,options||{});var iframe=document.createElement('iframe'),div=pixlr<?php echo $_GET['instanceid']; ?>.overlay.div=document.createElement('div'),idiv=pixlr<?php echo $_GET['instanceid']; ?>.overlay.idiv=document.createElement('div');div.style.background='#696969';div.style.opacity=0.8;div.style.filter='alpha(opacity=80)';if((bo.ie&&bo.quirks)||bo.ie6){var size=windowSize();div.style.position='absolute';div.style.width=size.width+'px';div.style.height=size.height+'px';div.style.setExpression('top',"(t=document.documentElement.scrollTop||document.body.scrollTop)+'px'");div.style.setExpression('left',"(l=document.documentElement.scrollLeft||document.body.scrollLeft)+'px'");}
else{div.style.width='100%';div.style.height='100%';div.style.top='0';div.style.left='0';div.style.position='fixed';}
div.style.zIndex=99998;idiv.style.border='1px solid #2c2c2c';if((bo.ie&&bo.quirks)||bo.ie6){idiv.style.position='absolute';idiv.style.setExpression('top',"25+((t=document.documentElement.scrollTop||document.body.scrollTop))+'px'");idiv.style.setExpression('left',"35+((l=document.documentElement.scrollLeft||document.body.scrollLeft))+'px'");}
else{idiv.style.position='fixed';idiv.style.top='25px';idiv.style.left='35px';}
idiv.style.zIndex=99999;document.body.appendChild(div);document.body.appendChild(idiv);iframe.style.width=(div.offsetWidth-70)+'px';iframe.style.height=(div.offsetHeight-50)+'px';iframe.style.border='1px solid #b1b1b1';iframe.style.backgroundColor='#606060';iframe.style.display='block';iframe.frameBorder=0;iframe.src=buildUrl(opt);idiv.appendChild(iframe);},hide:function(callback){if(pixlr<?php echo $_GET['instanceid']; ?>.overlay.idiv&&pixlr<?php echo $_GET['instanceid']; ?>.overlay.div){document.body.removeChild(pixlr<?php echo $_GET['instanceid']; ?>.overlay.idiv);document.body.removeChild(pixlr<?php echo $_GET['instanceid']; ?>.overlay.div);}
if(callback){eval(callback);}}},window:function(options){var opt=extend(pixlr<?php echo $_GET['instanceid']; ?>.settings,options||{});if(!window.open(buildUrl(opt),"pixlr","location=0,status=0,scrollbars=0")){alert("The editor window was blocked by your browser, please add pixlr.com to your pop-up blocker.");}},open:function(options){var opt=extend(pixlr<?php echo $_GET['instanceid']; ?>.settings,options||{});location.href=buildUrl(opt);}}}();