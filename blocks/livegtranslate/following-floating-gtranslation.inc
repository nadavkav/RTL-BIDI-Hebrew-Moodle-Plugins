<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load("language", "1");
</script>

<script>
if (!document.layers)
document.write('<div id="divStayTopLeft" style="position:absolute; display:none">')
</script>

<layer id="divStayTopLeft">

<!--EDIT BELOW CODE TO YOUR OWN MENU-->
<table border="1" width="500" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" bgcolor="#FFFFCC" colspan=3>
      <p align="center"><b><font size="4">תרגומון מאנגלית לעברית</font></b></td>
  </tr>
  <tr>
    <td align="center" width="45%" bgcolor="#FFFFFF">הקלידו מילים בעברית</td>
	<td width="10%"></td>
    <td align="center" width="45%" bgcolor="#FFFFFF">התרגום לאנגלית</td>
  </tr>
  <tr>
    <td width="45%" bgcolor="#FFFFFF"><input id="hebrewtext" size="50" type="text" value="מילים בעברית" ></td>
	<td width="10%"><input id="hebrewtext" type="button" value="תרגום" onClick="livetranslate('hebrew2english');"></td>
    <td width="45%" bgcolor="#FFFFFF"><input id="englishtranslation" size="50" type="text" value="" ></td>
  </tr>
  <tr>
    <td align="center" width="45%" bgcolor="#FFFFFF">הקלידו מילים באנגלית</td>
	<td width="10%"></td>
    <td align="center" width="45%" bgcolor="#FFFFFF">התרגום לעברית</td>
  </tr>
  <tr>
    <td width="45%" bgcolor="#FFFFFF"><input id="englishtext" type="text" size="50" value="english words"></td>
	<td width="10%"><input id="hebrewtext" type="button" value="תרגום" onClick="livetranslate('english2hebrew');"></td>
    <td width="45%" bgcolor="#FFFFFF"><input id="hebrewtranslation" size="50" type="text" value="" ></td>
  </tr>
</table>
<!--END OF EDIT-->

</layer>

<script type="text/javascript">
function livetranslate(lang) {
if (lang == 'hebrew2english') {
sourcelang = 'he'; translang = 'en';
sourceid = 'hebrewtext'; transid = 'englishtranslation';
} else {
sourcelang = 'en'; translang = 'he';
sourceid = 'englishtext'; transid = 'hebrewtranslation';
}
google.language.translate(document.getElementById(sourceid).value, sourcelang, translang, function(result) {
  if (!result.error) {
    var container = document.getElementById(transid);
    container.value = result.translation;
  }
});
}
</script>

<script type="text/javascript">

/*
Floating Menu script-  Roy Whittle (http://www.javascript-fx.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/

//Enter "frombottom" or "fromtop"
var verticalpos="frombottom"

if (!document.layers)
document.write('</div>')

function JSFX_FloatTopDiv()
{
	var startX = 3,
	startY = 150;
	var ns = (navigator.appName.indexOf("Netscape") != -1);
	var d = document;
	function ml(id)
	{

		var el=d.getElementById?d.getElementById(id):d.all?d.all[id]:d.layers[id];
		if(d.layers)el.style=el;
		el.sP=function(x,y){this.style.left=x;this.style.top=y;};
		el.x = startX;
		if (verticalpos=="fromtop")
		el.y = startY;
		else{
		el.y = ns ? pageYOffset + innerHeight : document.body.scrollTop + document.body.clientHeight;
		el.y -= startY;
		}
		return el;
	}
	window.stayTopLeft=function()
	{

		if (verticalpos=="fromtop"){
		var pY = ns ? pageYOffset : document.body.scrollTop;
		ftlObj.y += (pY + startY - ftlObj.y)/8;
		}
		else{
		var pY = ns ? pageYOffset + innerHeight : document.body.scrollTop + document.body.clientHeight;
		ftlObj.y += (pY - startY - ftlObj.y)/8;
		}
  //alert (ftlObj.x+" - "+ ftlObj.y);
		ftlObj.sP(ftlObj.x, ftlObj.y);
		ftlObj.style['top'] = (ftlObj.y-60)+'px';
		ftlObj.style['left'] = '10px';
		setTimeout("stayTopLeft()", 10);
	}
	ftlObj = ml("divStayTopLeft");


	stayTopLeft();
}
JSFX_FloatTopDiv();
</script>