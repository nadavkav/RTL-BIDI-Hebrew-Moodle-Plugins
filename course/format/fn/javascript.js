<script type="text/javascript">

function getStyle(x,styleProp)
{
  if (x.currentStyle)
	  var y = x.currentStyle[styleProp];
  else if (window.getComputedStyle)
	  var y = document.defaultView.getComputedStyle(x,null).getPropertyValue(styleProp);
  return y;
}

var elm = document.getElementsByClassName('header');
var fnelm = document.getElementsByClassName('fnweeklynavselected');
var bgcolor = getStyle(elm[0],'background-color');
var color = getStyle(elm[0],'color');
if ( bgcolor == null or bgcolor = rgba(0,0,0,0) ) bgcolor = beige;
for (i=0;i<fnelm.length;i++) {
  fnelm[i].style.background = bgcolor;
  fnelm[i].style.color = color;
}

</script>