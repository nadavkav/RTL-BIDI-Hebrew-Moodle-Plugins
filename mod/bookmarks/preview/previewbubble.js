/*
    WebSnapr - Preview Bubble Javascript
    Written by Juan Xavier Larrea 
    http://www.websnapr.com
    last modified: Aug 2007, mo
*/


// Point this variable to the correct location of the bg.png file
var bubbleImagePath = 'bg.png';

// Insert your WebSnapr developer key here - get it free on www.websnapr.com
var developerKey = 'ipgFXSCayIT8';



// DO NOT EDIT BENEATH THIS
if(typeof Array.prototype.push!="function"){
Array.prototype.push=ArrayPush;
function ArrayPush(_1){
this[this.length]=_1;
}
}
function WSR_getElementsByClassName(_2,_3,_4){
var _5=(_3=="*"&&_2.all)?_2.all:_2.getElementsByTagName(_3);
var _6=new Array();
_4=_4.replace(/\-/g,"\\-");
var _7=new RegExp("(^|\\s)"+_4+"(\\s|$)");
var _8;
for(var i=0;i<_5.length;i++){
_8=_5[i];
if(_7.test(_8.className)){
_6.push(_8);
}
}
return (_6);
}
function bindBubbles(e){
lbActions=WSR_getElementsByClassName(document,"a","previewlink");
for(i=0;i<lbActions.length;i++){
if(window.addEventListener){
lbActions[i].addEventListener("mouseover",attachBubble,false);
lbActions[i].addEventListener("mouseout",detachBubble,false);
}else{
lbActions[i].attachEvent("onmouseover",attachBubble);
lbActions[i].attachEvent("onmouseout",detachBubble);
}
}
}
function attachBubble(_b){
var _c;
if(_b["srcElement"]){
	_c=_b["srcElement"];
}else{
	_c=_b["target"];
}
if (_c.href == undefined){
	_c=_c.parentNode;
}
var _d=_c.title;
var _e=findPos(_c)[0]+5;
var _f=findPos(_c)[1]+17;
var _10=document.createElement("div");
document.getElementsByTagName("body")[0].appendChild(_10);
_10.className="previewbubble";
if (BrowserDetect.browser == 'Explorer') {
_10.style.width="240px";
_10.style.position="absolute";
_10.style.top=_f;
_10.style.zIndex=99999;
_10.style.left=_e;
_10.style.textAlign="left";
_10.style.height="190px";
_10.style.paddingTop="0";
_10.style.paddingLeft="0";
_10.style.paddingBottom="0";
_10.style.paddingRight="0";
_10.style.marginTop="0";
_10.style.marginLeft="0";
_10.style.marginBottom="0";
_10.style.marginRight="0";
_10.style.filter="progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + bubbleImagePath + "',sizingMethod='image')";
} else {
_10.setAttribute("style","text-align: center; z-index: 99999; position: absolute; top: "+_f+"px ; left: "+_e+"px ; background: url("+ bubbleImagePath +") no-repeat; width: 240px; height: 190px; padding: 0; margin: 0;");
}
if (BrowserDetect.browser == 'Safari' || BrowserDetect.browser == 'Konqueror' ) {

var _height = _f;
    
_10.setAttribute("style","text-align: center; z-index: 99999; position: absolute; top: "+ _height +"px ; left: "+_e+"px ; background: url("+ bubbleImagePath +") no-repeat; width: 240px; height: 190px; padding: 0; margin: 0;");
    
}
var img=document.createElement("img");
_10.appendChild(img);

if (BrowserDetect.browser == 'Explorer') {
img.style.paddingTop="0";
img.style.paddingLeft="0";
img.style.paddingBottom="0";
img.style.paddingRight="0";
img.style.margin="auto";
img.style.marginTop="27px";
img.style.marginLeft="25px";
img.style.marginBottom="0";
img.style.marginRight="0";
img.style.borderTop="0";
img.style.borderLeft="0";
img.style.borderBottom="0";
img.style.borderRight="0";
} else {
img.setAttribute("style","padding-top: 0; padding-left: 0; padding-right: 0; padding-bottom: 0; margin-top: 27px; margin-left: 12px; margin-bottom: 0; margin-right: 0; border: 0");
}
img.setAttribute("src","http://images.websnapr.com/?key=" + encodeURIComponent(developerKey) + "&url="+_d);
img.setAttribute("width",202);
img.setAttribute("height",152);
img.setAttribute("alt","Snapshot");



}
function detachBubble(_12){
lbActions=WSR_getElementsByClassName(document,"div","previewbubble");
for(i=0;i<lbActions.length;i++){
lbActions[i].parentNode.removeChild(lbActions[i]);
}
}
if(window.addEventListener){
addEventListener("load",bindBubbles,false);
}else{
attachEvent("onload",bindBubbles);
}
function findPos(obj){
var _14=curtop=0;
if(obj.offsetParent){
_14=obj.offsetLeft;
curtop=obj.offsetTop;
while(obj=obj.offsetParent){
_14+=obj.offsetLeft;
curtop+=obj.offsetTop;
}
}
return [_14,curtop];
}

var BrowserDetect = {
	init: function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
		this.version = this.searchVersion(navigator.userAgent)
			|| this.searchVersion(navigator.appVersion)
			|| "an unknown version";
		this.OS = this.searchString(this.dataOS) || "an unknown OS";
	},
	searchString: function (data) {
		for (var i=0;i<data.length;i++)	{
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1)
					return data[i].identity;
			}
			else if (dataProp)
				return data[i].identity;
		}
	},
	searchVersion: function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
	},
	dataBrowser: [
		{ 	string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari"
		},
		{
			prop: window.opera,
			identity: "Opera"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{		// for newer Netscapes (6+)
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{ 		// for older Netscapes (4-)
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS : [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]

};
BrowserDetect.init();
