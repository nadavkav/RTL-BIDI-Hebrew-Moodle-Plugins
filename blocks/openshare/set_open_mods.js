/*
JavaScript by John Krutsch, 2008
*/

function changeRadio(selectValue,selName,radioName,all) {
	if(all==0){
		for(i=0; i<document.form1.elements.length; i++){
			if(document.form1.elements[i].type=="radio"){
				document.form1.elements[(selectValue==1?i-1:i)].checked="checked";
			}
		}
		changeSelection(selectValue,selName,all);
	}else if(all>0){
		for(i=0; i<document.form1.elements.length; i++){
			if(document.form1.elements[i].type=="radio" && document.form1.elements[i].id.substring(0,document.form1.elements[i].id.indexOf('~'))==all){
				document.form1.elements[(selectValue==1?i-1:i)].checked="checked";
			}
		}
		changeSelection(selectValue,selName,all);	
	
	}else{
		document.form1["status_"+radioName][(selectValue == 1?0:1)].checked = "checked";
	}
}

function changeSelection(dropInd,selectName,all) {
	if(all==0){
		for(i=0; i<document.form1.elements.length; i++){
			if(document.form1.elements[i].type=="select-one"){ 
				document.form1.elements[i].selectedIndex = dropInd;
			}
			if(document.form1.elements[i].type=="radio"){
				document.form1.elements[(dropInd==1?i-1:i)].checked="checked";
			}
		}
	}else if(all>0){
		for(i=0; i<document.form1.elements.length; i++){
			if(document.form1.elements[i].type=="select-one" && document.form1.elements[i].id.substring(0,document.form1.elements[i].id.indexOf('~'))==all){ 
				document.form1.elements[i].selectedIndex = dropInd;
			}
			if(document.form1.elements[i].type=="radio" && document.form1.elements[i].id.substring(0,document.form1.elements[i].id.indexOf('~'))==all){
				document.form1.elements[(dropInd==1?i-1:i)].checked="checked";
			}	
		}
	}else{
		document.form1[selectName].selectedIndex = dropInd;
	}
}
