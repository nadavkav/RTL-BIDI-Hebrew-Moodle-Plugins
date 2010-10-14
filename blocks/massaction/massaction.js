/*RT#59503 20100115 Diego. Removed references to getElementsByName and use getElementsByTagName which is supported by both IE and Mozilla FF*/
function massaction_check_f(checkbox) {
    
    var form = document.getElementById('massactionexecute');
    
    var modid = checkbox.getAttribute('modid');
    var name = 'massaction_check_' + modid;
    var id   = name;                         /**Attribute 'id' needed in the form input so to use js function 'getElementById'
                                                in massaction_uncheck to work with IE6 and IE7*/
    var js_name = 'js_input';
    var js_id   = js_name;

    if (! form.js_input) {                             /*if element named js_input not in, insert one*/
        var js_elem = document.createElement('input'); 
        js_elem.setAttribute('type', 'hidden');
        js_elem.setAttribute('id', js_id);
        js_elem.setAttribute('name',js_name);
        js_elem.setAttribute('value', 'js_enabled');
        form.appendChild(js_elem);
    }
    var newinput = document.createElement('input');   /*create new input equivalent to checked checkbox*/
    newinput.setAttribute('value', modid);
    newinput.setAttribute('id', id);
    newinput.setAttribute('name', name);
    newinput.setAttribute('type', 'hidden');
    form.appendChild(newinput);
}

function massaction_submit(name) {
    var value = '';
    var form = document.getElementById('massactionexecute');
    var action = document.createElement('input');
    action.setAttribute('type', 'hidden');
    action.setAttribute('name', name);
    action.setAttribute('value', value);
    form.appendChild(action);
    form.submit();
}

function massaction_uncheck(checkbox) {
    var modid = checkbox.getAttribute('modid');      
    var id = 'massaction_check_' + modid;
    
	oldinput = document.getElementById(id);         /*get Element with id above (it's under form for submission)*/
    if (typeof(oldinput) == 'object') {
        var form = oldinput.parentNode;     /*get form the input element belongs to; remove from form*/
        form.removeChild(oldinput);
    }
}

function massaction_addcheckboxes() {
    if (document.getElementById("section-0")) {
        for (var sec_i = 0, sec = null;  
             sec = document.getElementById("section-" + sec_i); sec_i++)     
        {
            massaction_section_addcheckboxes(sec);
        }
    }
}

function massaction_section_addcheckboxes(section) {
    var modprefix = 'module-';
    var list = section.getElementsByTagName("li");
    for (var i in list) {
        if (list[i].getAttribute && list[i].getAttribute("id") && list[i].getAttribute("id").substring(0, modprefix.length) == modprefix) {
            massaction_addcheckbox(list[i], section.id);
        }
    }   
}

function massaction_addcheckbox(obj, sec_id) {
    var modprefix = 'module-';
    var box = document.createElement("input");

    var modid = obj.getAttribute('id').substring(modprefix.length);

    box.setAttribute("type", "checkbox");
    box.setAttribute("modid", modid);
    box.setAttribute("sec_id", sec_id);
    box.setAttribute("name", "massaction_check");

    /**RT#59503 20100115 Diego. Changed how boxes are checked to work in IE*/
    box.onclick = function() {
        if (this.checked) {		     //if 'box' is checked
            massaction_check_f(this);   //add it to form that will be submitted
        } else { 			    
            massaction_uncheck(this);  //remove it from form that will be submitted
        }
    }

    obj.appendChild(box);
}

function disable_js_warning() {
    message = document.getElementById('massaction_javascriptwarning');
    var parent = message.parentNode;
    parent.removeChild(message);
}

function select_all() {
    var checkboxes = document.getElementsByTagName('input');
    for(var i = 0 ; i < checkboxes.length ; i++ ) {
        if(checkboxes[i].getAttribute('type') == 'checkbox' && checkboxes[i].getAttribute('name') == 'massaction_check' ){
            massaction_check_f(checkboxes[i]);
            checkboxes[i].checked = true;	
        }
    }
}

function deselect_all() {
    var checkboxes = document.getElementsByTagName('input');
    for(var i = 0 ; i < checkboxes.length ; i++ ){
        if(checkboxes[i].getAttribute('type') == 'checkbox' && checkboxes[i].getAttribute('name') == 'massaction_check' ){
            massaction_uncheck(checkboxes[i]);
            checkboxes[i].checked = false;
        }
    }
}

function select_section(section) {
    var checkboxes = document.getElementsByTagName('input');
    var sec_id = "section-" + section;
    for(var i = 0 ; i < checkboxes.length ; i++ ){
        if(checkboxes[i].getAttribute('type') == 'checkbox' && checkboxes[i].getAttribute('name') == 'massaction_check' && 
						checkboxes[i].getAttribute('sec_id') == sec_id){
                massaction_check_f(checkboxes[i]);
                checkboxes[i].checked = true;
        }
    }
}

