
// This file contain all JavaScript functions for manage the users who this email has sended.


/**
 * Abstract function for manage contacts. Use when add or remove contact.
 *
 */
function manageContact(user, userid, action, sendtype) {

	if ( action == 'remove' ) {
		var userremoved = false;

    	if (removeContact(userid, 'to', window.opener.document.sendmail.nameto, user)) {
        	userremoved = true;
    	}

    	if (removeContact(userid,'cc', window.opener.document.sendmail.namecc, user)) {
        	userremoved = true;
        }

        if (removeContact(userid,'bcc', window.opener.document.sendmail.namebcc, user)) {
                userremoved = true;
        }

        return userremoved;
	} else if ( action == 'add' ) {
		return addContact(user, userid, sendtype);
	} else {
		return false;
	}

}

/**
 * This function add to corresponding field one user
 *
 * @param string user User
 * @param string userid userid
 * @param string sendtype Type of send (to, cc or bcc)
 */
function addContact(user, userid, sendtype) {

	// Adds the contact to the list and the hidden id field.  Will ignore click event if the
	// ID/contact exists in to[], cc[], or bcc[] fields
	if (sendtype == 'bcc') {
    	var field = window.opener.document.sendmail.namebcc;
    } else if (sendtype == 'cc') {
        var field = window.opener.document.sendmail.namecc;
    } else if (sendtype == 'to') {
        var field = window.opener.document.sendmail.nameto;
    }

    // Checks if the user is already sending to clicked user in some way
    if ( alreadySending('to', userid) ) {
    	return false;
    }

    if ( alreadySending('cc', userid) ) {
        return false;
    }

    if ( alreadySending('bcc', userid) ) {
       return false;
    }

    // Adds the user id to the hidden fields for submit
    // I Very Hate IE...had to do this ugly hack to get this to work for IE 6+ :(
    var contacts = window.opener.document.createElement("span");
    window.opener.document.getElementById('id_name'+sendtype).parentNode.appendChild(contacts);
    contacts.innerHTML = '<input type="hidden" value="'+userid+'" name="'+sendtype+'[]">';

    // Adds Name to sendtype list
    if (field.value == '') {
    	field.value = user;
    } else {
    	// Checks for valid string entry for post-send validation
        if ((field.value.charAt(field.value.length-2) != ',')) {
        	if ((field.value.charAt(field.value.length-1) != ',')) {
            	user = ', '+user;
            } else {
                user = ' '+user;
            }
        }

        field.value = field.value + user;
	}
    return true;
}


/**
 * This function removes an added contact
 *
 * @param string userid userid of the user
 * @param string sendtype Type of send (to,cc or bcc)
 * @param class  field Field who contains the user
 * @param string  user User
 */
function removeContact(userid, sendtype, field, user) {
	// Show if exist in opener window.
    if ( existing = window.opener.document.getElementsByName(sendtype+'[]') ) {
        for (var i=0; i < existing.length; i++) {
            if (userid == existing[i].value) {
                var parent = window.opener.document.getElementById('id_name'+sendtype).parentNode;
                var fieldvalue = field.value;
                // Removes this element and returns boolean true
                parent.removeChild(existing[i].parentNode);
                // Removes the name from the contacts list
                if ( fieldvalue.indexOf(',') == -1) {
                    field.value = '';
                } else {
                    var firstindex = fieldvalue.indexOf(user);
                    // Not first name...so remove the comma as well
                    if (firstindex != 0) {
                        user = ', '+user;
                    } else {
                        user = user+', ';
                    }
                    fieldvalue = fieldvalue.replace(user, '');
                    field.value = fieldvalue;
                }
                return true;
            }
        }
    }
    // Not found...user not added
    return false;
}

/**
 * This function checks if user is added already
 *
 * @param string sentype 'to', 'cc', 'bcc'
 * @param string userid
 * @return boolean true if already sending, false if new participant
 */
function alreadySending(sendtype, userid) {
    var old = null;

    if (old = window.opener.document.getElementsByName(sendtype+'[]')) {
        for (var i=0; i < old.length; i++) {
            if ( userid == old[i].value ) {
                return true;
            }
        }
    } else {
        return false;
    }
}

//posa una direcci directament al per, eliminant-ne la resta
    function setContact(email) {
    window.document.theform.destiny.value = email;
   }

/**
 * This function change content of iframe
 *
 * @param string id Div html element ID
 * @param text txt HTML content
 */
function changeme (id, txt) {
    document.getElementById(id).innerHTML = txt;
}

//cid: element on assignat el valor
function setPage (cid,txt) {
    document.getElementById(cid).value = txt;
}

//el mtic toggle per modificar la visibilitat
function toggle(obj) {
    var el = window.document.getElementById(obj);
                           alert(el + obj);
    if ( el.style.display != 'none' ) {
	el.style.display = 'none';
    } else {
	el.style.display = '';
    }
}


/**
 * This function checks all added users and enables the remove user if they have
 * already been added to the email
 */
function checkAllRemoveActions() {
    var addedids = null;
    var icon = null;

    if (addedids = window.opener.document.getElementsByName('to[]')) {
        for (var i=0; i < addedids.length; i++) {
            if (icon = document.getElementById('removeuser'+addedids[i].value)) {
                toggleRemoveAction(addedids[i].value);
            }
        }
    }
    if (addedids = window.opener.document.getElementsByName('cc[]')) {
        for (var i=0; i < addedids.length; i++) {
            if (icon = document.getElementById('removeuser'+addedids[i].value)) {
                toggleRemoveAction(addedids[i].value);
            }
        }
    }
    if (addedids = window.opener.document.getElementsByName('bcc[]')) {
        for (var i=0; i < addedids.length; i++) {
            if (icon = document.getElementById('removeuser'+addedids[i].value)) {
                toggleRemoveAction(addedids[i].value);
            }
        }
    }
}

/**
 * This function enables/disables a remove icon for a particular user on the contact list
 * Also changes the style to .useradded
 *
 * @param string userid userid
 */
function toggleRemoveAction(userid) {
    var icon = document.getElementById('removeuser'+userid);
    var buttonto = document.getElementById('addto'+userid);
    var buttoncc = document.getElementById('addcc'+userid);
    var buttonbcc = document.getElementById('addbcc'+userid);

    if (icon.style.visibility == 'hidden') {
        icon.style.visibility = '';
        buttonto.style.visibility = 'hidden';
        buttoncc.style.visibility = 'hidden';
        buttonbcc.style.visibility = 'hidden';
    } else {
        icon.style.visibility = 'hidden';
        buttonto.style.visibility = '';
        buttoncc.style.visibility = '';
        buttonbcc.style.visibility = '';
    }
}


// Also resets the groupdropmenu if its there
function reloadiframegroup(params) {
    if (document.getElementById('selectgroup_jump')) {
        document.getElementById('selectgroup_jump').selectedIndex=0;
    }
    reloadiframe(params);
}


/**
 * Reload the Frame with params
 */
function reloadiframe (params) {
    var url = "get_users.php?"+params;
    document.getElementById("idsearch").src = url;
    // document.write( "Somthing" + url);
    //document.getElementById("search_res").innerHTML = url;
}


/**
 * Display or not one div
 */
function switchMenu(obj, pixpath) {
    var el = document.getElementById(obj);

    if ( el.style.display != 'none' ) {
		el.style.display = 'none';
    } else {
		el.style.display = '';
    }

    var im = document.getElementById(obj+"_icon");

    if ( im.src == pixpath+"switch_plus.gif" ) {
		im.src = pixpath+"switch_minus.gif";
    }  else {
		im.src = pixpath+"switch_plus.gif";
    }
}

/**
 * This function applys an action to all users in the contact popup
 *
 * @param string action 'to' = sendto all, 'cc' = sendcc all, 'bcc' = sendbcc all, 'remove' = remove all
 **/
function action_all_users(action) {
    // Gets all users
    var allrows = document.getElementsByName('userid'+action);
    for (var i = 0; i < allrows.length; i++) {
        // userid via innerHTML, substring for id, and passed action
        if (action == 'remove' ) {
        	if (manageContact(allrows[i].id, allrows[i].value, action, '')) {
            	// Changes display of user row to show it was removed
            	toggleRemoveAction(allrows[i].value);
        	}
        } else {
        	if (manageContact(allrows[i].id, allrows[i].value, 'add', action)) {
            	// Changes display of user row to show it was added
            	toggleRemoveAction(allrows[i].value);
        	}
        }
    }
}
