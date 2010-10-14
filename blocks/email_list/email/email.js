

// JavaScript for sendmail. New mail form.

var upload_number = 1;
function addFileInput(txt) {
    var d = document.createElement("div");
    d.setAttribute("id", "id_FILE_"+upload_number);
    var file = document.createElement("input");
    file.setAttribute("type", "file");
    file.setAttribute("name", "FILE_"+upload_number);
    file.setAttribute("id", "FILE_"+upload_number);
//    file.setAttribute("onchange", "addFileInput('"+txt+"')");
    d.appendChild(file);
    var a = document.createElement("a");
    a.setAttribute("href", "javascript:removeFileInput('id_FILE_"+upload_number+"');");
    a.appendChild(document.createTextNode(txt));
    d.appendChild(a);
    document.getElementById("id_FILE_"+(upload_number-1)).parentNode.appendChild(d);
    upload_number++;
}

function removeFileInput(i) {
    var elm = document.getElementById(i);
    document.getElementById(i).parentNode.removeChild(elm);
    upload_number--;
}


// Javascript for add senders. Used in new mail form.
function addC(tipo) {
	var d = document.getElementById("fortextarea"+tipo);
    var d1 = document.getElementById("button"+tipo);
    var textarea = document.createElement("textarea");

    textarea.setAttribute("rows", "3");
    textarea.setAttribute("cols", "65");
    textarea.setAttribute("name", tipo);
    textarea.setAttribute("id", "textarea" + tipo);
    textarea.setAttribute("disabled","true");
    textarea.setAttribute("class","textareacontacts");

    var contacts = document.createElement("a");
    var text = document.createTextNode("'.get_string('participants', 'block_email_list').'...");

   	contacts.appendChild(text);
    contacts.setAttribute("target", "participants");
    contacts.setAttribute("title", "'.get_string('participants', 'block_email_list').'");
    contacts.setAttribute("href", "'. $CFG->wwwroot .'/blocks/email_list/email/participants.php?'. $url .'");
    contacts.setAttribute("onclick", "return openpopup(\'/blocks/email_list/email/participants.php?'.$url.'\', \'participants\', \'menubar=0,location=0,scrollbars=0,resizable,width=750,height=520\', 0)");

    d.appendChild(textarea);
    d1.appendChild(contacts);

    var b = document.createElement("b");
  	var txt;

   	if ( tipo == "cc") {
		txt = "'. get_string('cc', 'block_email_list').'" ;
  	} else {
   		txt = "' .get_string('bcc', 'block_email_list') . '";
   	}

	var node=document.createTextNode(txt+": ");
	b.appendChild(node);
   	document.getElementById("td"+tipo).appendChild(b);

	var rm = document.getElementById("url"+tipo);
	document.getElementById("url").removeChild(rm);

	var rm1 = document.getElementById("urltxt");

	if ( rm1 ) {
		document.getElementById("url").removeChild(rm1);
	}
}

// Print multiple eMail's
function get_for_print_multiple_emails(field) {

	var mycheck = '';

	if ( field.length > 0 ) {
		for (var i = 0; i < field.length; i++) {
			if ( field[i].checked ) {
				if ( i > 0 && mycheck != '' ) { mycheck += ','; } // Add semicolon for obtain sequence
				mycheck += field[i].value;
			}
		}
	} else {
		if ( field.checked ) {
			mycheck = field.value;
		}
	}

	return mycheck;
}