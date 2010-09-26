var mod_data_value_oAjax=new ExAjaxClass();
mod_data_value_oAjax.AddCallBackHnadler(recieve_data,AjaxResponseType.responseTEXT);


function add_value(req_for)
{
	
// alert("hi");
	var req='add';
	var data=''
	var block_id=req_for.id;
		var insertion_data='insertion_data'+block_id;
		var data=document.getElementById(insertion_data).value;
	    var url=document.getElementById('hidden_data').value;
		var course_id=document.getElementById('insertion_block_c_id').value;
		var user_id=document.getElementById('insertion_block_u_id').value;
		var insertion_block_row='insertion_block_row'+block_id;
		var noofrow=document.getElementById(insertion_block_row).value;
		//alert(noofrow);
		var insertion_block_noofcharbreak='insertion_block_noofcharbreak'+block_id;
	
		var noofcharbreak=document.getElementById(insertion_block_noofcharbreak).value;
			//alert(noofcharbreak);
	if(data=='') return false;
 

    uri=url+'/blocks/shoutbox/adddata.php';
	var pdata='data='+data+'&req='+req+'&course_id='+course_id+'&row='+noofrow+'&u_id='+user_id+'&noofcharbreak='+noofcharbreak+'&block_id='+block_id+'&';
	mod_data_value_oAjax.sendToServer(AjaxMethodType.POST,uri,pdata);
}

function recieve_data(retStr,errCode,AStatus)
{
//   alert(retStr);
	var result=retStr.split("%");
	//alert(result[1]);
	var insertion_data= 'insertion_data'+result[1];
document.getElementById(insertion_data).value='';
var list_container= 'list_container'+result[1];

document.getElementById(list_container).innerHTML = result[0];
}


function checked_all()
{
var fmobj=document.forms[0];
for (var i=0;i<fmobj.elements.length;i++) {
    var e = fmobj.elements[i];
    if (e.type=='checkbox') {
      e.checked = true;
    }
  }
	
	
	
}


function unchecked_all()
{
var fmobj=document.forms[0];

for (var i=0;i<fmobj.elements.length;i++) {
    var e = fmobj.elements[i];
    if (e.type=='checkbox') {
      e.checked = false;
    }
  }
	
}

function limiter(instanid){
var insertion_block_char='insertion_block_char'+instanid;
var count = document.getElementById(insertion_block_char).value;  
var insertion_data='insertion_data'+instanid;
var tex=document.getElementById(insertion_data).value;
var len = tex.length;

if(len > count){
        var insertion_data='insertion_data'+instanid;
		tex = tex.substring(0,count);
		
        document.getElementById(insertion_data).value =tex;
        return false;
}
var limit='limit'+instanid;
document.getElementById(limit).innerHTML = count-len;
}


function empty(b_id)
{
 var instance_id=b_id.id;
 var block_id=instance_id.substring("14");
  //alert(block_id);
 var insertion_data= 'insertion_data'+block_id;
document.getElementById(insertion_data).value ='';	
}


function popUp(ss) {
var url=document.getElementById('hidden_data').value;
var course_id=ss;	
URL=url+'/blocks/shoutbox/pop_window.php?course_id='+course_id;
day = new Date();
id = day.getTime();
eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=1,scrollbars=1,location=1,statusbar=1,menubar=1,resizable=1,width=650,height=500,left = 540,top = 134');");
}

function edit_shoutbox_message(id)
{
	var editid='editid'+id;
document.getElementById(editid).innerHTML = "";
var change_message='change_message'+id;
var message_text=document.getElementById(change_message).innerHTML;
document.getElementById(change_message).innerHTML = '<textarea class=textarea_edit id=tedit'+id+' >'+message_text+'</textarea> <input  type="button" value="Submit" onClick="update_submit('+id+')"  />';
}

var mod_data_edit_oAjax=new ExAjaxClass();
mod_data_edit_oAjax.AddCallBackHnadler(edit_value,AjaxResponseType.responseTEXT);
function update_submit(id)
{
var textarea_id='tedit'+id;	
var textarea_val=document.getElementById(textarea_id).value;
var url=document.getElementById('hidden_data').value;
uri=url+'/blocks/shoutbox/editdata.php?textarea_val='+textarea_val+'&id='+id+'&';
mod_data_edit_oAjax.sendToServer(AjaxMethodType.GET,uri,'true');

}

function edit_value(retStr,errCode,AStatus)
{
//alert(retStr);
var result=retStr.split(",");
var id=parseInt(result[0]);
var updated_data=result[1];
var editid='editid'+id;
	
document.getElementById(editid).innerHTML = "EDIT";
var change_message='change_message'+id;
document.getElementById(change_message).style.background="#FFE6FF";
//document.getElementById(change_message).style.font-weight="bold";
document.getElementById(change_message).innerHTML=updated_data;


}

function download_data(block_id,page,perpage,orderby)
{
	window.location.href='exportexcell.php?course_id='+block_id+'&page='+page+'&perpage='+perpage+'&orderby='+orderby+'&';
	
}