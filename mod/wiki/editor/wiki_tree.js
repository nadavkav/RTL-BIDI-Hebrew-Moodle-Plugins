//By George Chiang (www.javascriptkit.com) JavaScript site.
//this script is uset to make jerarquic tree in dfwiki

img1=new Image()
img1.src="images/plus.gif"
img2=new Image()
img2.src="images/minus.gif"
ns6_index=0

function wiki_change(e){

	if(!document.all&&!document.getElementById)
		return

	if (!document.all&&document.getElementById)
	ns6_index=1

	var source=document.getElementById&&!document.all? e.target:event.srcElement

	//for wiki folding
	if (source.className=="wiki_folding"){
		var source2=document.getElementById&&!document.all? source.parentNode.childNodes:source.parentElement.all
		if (source2[2+ns6_index].style.display=="none"){
			source2[0].src=img2.src;
			source2[2+ns6_index].style.display='';
		}
		else{
			source2[0].src=img1.src;
			source2[2+ns6_index].style.display="none";
		}
	}

	//for wiki folding for course
	if (source.className=="wiki_folding_co"){
		var source2=document.getElementById&&!document.all? source.parentNode.childNodes:source.parentElement.all
		if (source2[2+ns6_index].style.display=="none"){
			source2[0].src="../mod/wiki/images/minus.gif";
			source2[2+ns6_index].style.display='';
		}
		else{
			source2[0].src="../mod/wiki/images/plus.gif";
			source2[2+ns6_index].style.display="none";
		}
	}

	//for emoticons button
	if (source.className=="icsme"){
		var source2=document.getElementById&&!document.all? source.parentNode.childNodes:source.parentElement.all
		if (source2[2+ns6_index].style.display=="none"){
			source2[0].src="editor/images/ed_smiley2.gif";
			source2[0].alt="Smileis";
			source2[2+ns6_index].style.display='';
		}
		else{
			source2[0].src="editor/images/ed_smiley1.gif";
			source2[2+ns6_index].style.display="none";
			source2[0].alt="Smileis";
		}
	}

	//for emoticons button for course
	if (source.className=="icsme_co"){
		var source2=document.getElementById&&!document.all? source.parentNode.childNodes:source.parentElement.all
		if (source2[2+ns6_index].style.display=="none"){
			source2[0].src="../mod/wiki/editor/images/ed_smiley2.gif";
			source2[0].alt="Smileis";
			source2[2+ns6_index].style.display='';
		}
		else{
			source2[0].src="../mod/wiki/editor/images/ed_smiley1.gif";
			source2[2+ns6_index].style.display="none";
			source2[0].alt="Smileis";
		}
	}

}
document.onclick=wiki_change
