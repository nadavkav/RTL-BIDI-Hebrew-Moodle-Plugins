var strCloseComments,strCloseCommentForm;

function ouwikiAddOnLoad(fn) {
	var oldHandler=window.onload;
	window.onload=function() {
	  if(oldHandler) oldHandler();
	  fn();
	}
}

function ouwikiToggleFunction(target,link) {
  return function() {
    if(target.style.display=='block') {
    	target.style.display='none';
    	link.removeChild(link.firstChild);
    	link.appendChild(link.originalLink);
    } else {
    	target.style.display='block';
    	link.originalLink=link.firstChild;
    	link.removeChild(link.firstChild);
    	link.appendChild(document.createTextNode(strCloseComments));
    }
    return false;
  };
}

function ouwikiKeyFunction(link) {
  return function(e) {
    if((e && e.keyCode==13) || (window.event && window.event.keyCode==13))  {
      link.onclick();
      return false;
    } else {
	    return true;
    }
  }
}

var ouwikiOpenComments=null;

function ouwikiShowFormFunction(target,gotComments,header,link) {
  return function() {
    if(ouwikiOpenComments) {
      ouwikiOpenComments.removeChild(ouwikiOpenComments.firstChild);
      ouwikiOpenComments.appendChild(ouwikiOpenComments.originalLink);      
    }

    var form=document.getElementById('ouw_ac_formcontainer');
    if(form.parentNode.firstChild==form) {
        form.parentNode.style.display='none';
    }
    if(target==form.parentNode && form.style.display!='none') {
      form.style.display='none';
      return false;
    }
    form.parentNode.removeChild(form);
    target.appendChild(form);
    form.style.display='block';
    if(!gotComments) {
	    target.style.display='block';
    }
    
    ouwikiOpenComments=link;
    link.originalLink=link.firstChild;
    link.removeChild(link.firstChild);
    link.appendChild(document.createTextNode(strCloseCommentForm));
    
    document.getElementById('ouw_ac_section').value=
    	header.id ? header.id.substring(5): '';
    document.getElementById('ouw_ac_title').focus();
    	
    return false;
  };
}


function ouwikiSetFields() {

    var createbutton = document.getElementById('ouw_create');
    createbutton.disabled=true;

    var pagename = document.getElementById('ouw_newpagename');
    pagename.style.color="gray";
    pagename.notusedyet=true;    
    pagename.onfocus = function() { ouwikiResetThisField(pagename); };
    pagename.onkeyup = function() { ouwikiClearDisabled(createbutton, pagename); };
    
    var addbutton=document.getElementById('ouw_add');
    addbutton.disabled=true;

    var sectionname =document.getElementById('ouw_newsectionname');
    sectionname.style.color="gray";
    sectionname.notusedyet=true;
    sectionname.onfocus = function() { ouwikiResetThisField(sectionname); };
    sectionname.onkeyup = function() { ouwikiClearDisabled(addbutton, sectionname); };

}


function ouwikiClearDisabled(element, field) {

    if(field.value.length == 0) {
        element.disabled = true;
    } else {
       element.disabled = false;
    }

}


function ouwikiResetThisField(field) {

    if(field.notusedyet) {
        field.value='';
        field.style.color="black";
        field.notusedyet=false;
    }

}


function ouwikiShowAllAnnotations(action) {
	annoboxes = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation', 'span');
	for (var box=0; box<annoboxes.length; box++) {
			annoboxes[box].style.display = action;
	}
	if(action == "block") {
		ouwikiSwapAnnotationUrl("hide");
	} else if(action == "none") {
		ouwikiSwapAnnotationUrl("show");
	}
}

function ouwikiSwapAnnotationUrl(action){
	var showurl=document.getElementById("showhideannotations");
	var show=document.getElementById("showallannotations");
	var hide=document.getElementById("hideallannotations");
	if(action == "hide") {
		show.style.display = "none";
		hide.style.display = "inline";
	} else if(action == "show") {
		show.style.display = "inline";
		hide.style.display = "none";
	} else if(action == "showall") {
		showurl.style.display = "inline";
	}
}

function ouwikiShowHideAnnotation(id) {
	var box = document.getElementById(id);
	if (box.style.display == "block") {
		box.style.display = "none";
		ouwikiSwapAnnotationUrl("show")
	} else {
		box.style.display = "block";
		annoboxes = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation', 'span');
		var allblock = 1;
		for (var i=0; i<annoboxes.length; i++) {
			if(annoboxes[i].style.display != "block") {
				allblock = 0;
			};
		}
		if(allblock == 1){ouwikiSwapAnnotationUrl("hide");}
	}
}

function setupspans(span) {
	span.style.cursor = "pointer";
	span.tabIndex = "0";
	span.onkeydown = function(e) {
		if(e.which == 13 || e.which == 32){
			ouwikiShowHideAnnotation("annotationbox"+span.id.substring(10));
		}
	};
	span.onclick = function() {
		ouwikiShowHideAnnotation("annotationbox"+span.id.substring(10));
	};
}


function ouwikiOnLoad() {
  // Setup JS functions on links
  var links=document.getElementsByTagName('a');
  for(var i=0;i<links.length;i++) {
    var link=links[i];
    if(link.className=='ouw_revealcomment') {
      var hTag=link.parentNode.parentNode.parentNode.firstChild;
      var div;
      if(hTag.id=='ouw_topheading') {
        div=hTag.parentNode.parentNode.nextSibling.firstChild;
      } else {
        div=link.parentNode.parentNode.parentNode.nextSibling;
      }
    	link.onclick=ouwikiToggleFunction(div,link);
    	link.onkeydown=ouwikiKeyFunction(link);
    } else if(link.className=='ouw_makecomment') {
      var header=link.parentNode.parentNode;
      var div=document.createElement("div");
      div.className="ouw_hiddencomments ouw_nocomments";
      header.parentNode.insertBefore(div,header.nextSibling);
    	link.onclick=ouwikiShowFormFunction(div,false,header.firstChild,link);
    	link.onkeydown=ouwikiKeyFunction(link);
    } else if(link.className=='ouw_makecomment2') {
      var div=link.parentNode.parentNode;
      var target;
      if(div.previousSibling) {
      	link.onclick=ouwikiShowFormFunction(div,true,div.previousSibling.firstChild,link);
      } else {
      	link.onclick=ouwikiShowFormFunction(div,true,div.parentNode.previousSibling.firstChild.firstChild,link);
      }
    	link.onkeydown=ouwikiKeyFunction(link);
    }
  }
  // Show comments if specified
  var matches=/&showcomments(#(ouw_s.+))?$/.exec(location.href);
  if(matches) {
    var heading=matches[2] ? matches[2] : 'ouw_topheading';
    var item=document.getElementById(heading).parentNode;
    var links=item.getElementsByTagName('a');
    var link;
    for(var i=0;i<links.length;i++) {
      if(links[i].className=='ouw_revealcomment') {
        link=links[i];
        break;
      }
    }
    var div;
    if(matches[2]) {
        div=link.parentNode.parentNode.parentNode.nextSibling;
    } else {
        div=item.parentNode.nextSibling.firstChild;
    }
    ouwikiToggleFunction(div,link)();
  }

  // set add page and section fields
  if(document.getElementById('ouw_create') != null) {
    ouwikiSetFields();
  }

}

function init() {
	ouwikiShowAllAnnotations("none");
	ouwikiSwapAnnotationUrl("showall");
	annospans = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation-tag', 'span');
	for (var span=0; span<annospans.length; span++) {
		setupspans(annospans[span]);
	}
}

YAHOO.util.Event.onDOMReady(init);
ouwikiAddOnLoad(ouwikiOnLoad);
