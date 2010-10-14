/**
 * This file contains functionality for add participants via AJAX.
 */

// New DataSource (v1.6)

var urlconn=mysite.strings['wwwroot']+'/blocks/email_list/email/get_users.php';

var oDS = new YAHOO.util.XHRDataSource("participants/get_users.php");
// Set the responseType
oDS.responseType = YAHOO.util.XHRDataSource.TYPE_XML;
// Define the schema of the JSON results
oDS.responseSchema = {
        resultsList : "ResultSet.Result",
        fields : ["Username", "Userid"]
};

oDS.generateRequest = 'course='+mysite.id;

// Define the dropdown lists and data sources
var myAutoCompTo = new YAHOO.widget.AutoComplete("id_nameto","qResultsTo", oDS);
var myAutoCompCC = new YAHOO.widget.AutoComplete("id_namecc","qResultsCC", oDS);
var myAutoCompBCC = new YAHOO.widget.AutoComplete("id_namebcc","qResultsBCC", oDS);

//define your itemSelect handler function:
var itemSelectHandler = function(sType, aArgs) {
	YAHOO.log(sType); //this is a string representing the event;
				      //e.g., "itemSelectEvent"
	var oMyAcInstance = aArgs[0]; // your AutoComplete instance
	var elListItem = aArgs[1]; //the <li> element selected in the suggestion
	   					       //container
	var aData = aArgs[2]; //array of the data for the item as returned by the DataSource

    // To determine which of the three fields data is from
    var fieldid = oMyAcInstance["_oTextbox"].id;
    var fieldtype = fieldid.substr(7,fieldid.length);

    // Checks if selected user was added already
    if (alreadySending("to", aData[1])) {
        return false;
    }
    if (alreadySending("cc", aData[1])) {
        return false;
    }
    if (alreadySending("bcc", aData[1])) {
        return false;
    }

    // On selection of name, this fills out the hidden elements required to send
    // to selected user
    // I Very Hate IE...had to do this ugly hack to get this to work for IE 6+ :(
    var contacts = window.document.createElement("span");
   // var contacts = window.document.createElement("input")
   // contacts.setAttribute("type", "hidden");
   // contacts.setAttribute("value", aData[1]);
   // contacts.setAttribute("name", fieldtype+"[]");
	window.document.getElementById(fieldid).parentNode.appendChild(contacts);
	contacts.innerHTML = '<input type="hidden" value="'+aData[1]+'" name="'+fieldtype+'[]">';
};
alert(itemSelectHandler);
//subscribe your handler to the event for to field
myAutoCompTo.itemSelectEvent.subscribe(itemSelectHandler);

//subscribe your handler to the event for to field
myAutoCompCC.itemSelectEvent.subscribe(itemSelectHandler);

//subscribe your handler to the event for to field
myAutoCompBCC.itemSelectEvent.subscribe(itemSelectHandler);

// Some settings (to field)
myAutoCompTo.delimChar = ",";
myAutoCompTo.maxResultsDisplayed = 5;
myAutoCompTo.minQueryLength = 1;
myAutoCompTo.queryDelay = 0;
// Was causing problems with interaction between YUI autocomplete and contacts list popup, so its disabled
myAutoCompTo.forceSelection = false;
// Was causing some user interaction issues, so its disabled
myAutoCompTo.typeAhead = false;
myAutoCompTo.maxCacheEntries = 20;
myAutoCompTo.queryMatchSubset = true;

// Some settings (cc field)
myAutoCompCC.delimChar = ",";
myAutoCompCC.maxResultsDisplayed = 5;
myAutoCompCC.minQueryLength = 1;
myAutoCompCC.queryDelay = 0;
// Was causing problems with interaction between YUI autocomplete and contacts list popup, so its disabled
myAutoCompTo.forceSelection = false;
// Was causing some user interaction issues, so its disabled
myAutoCompCC.typeAhead = false;
myAutoCompTo.maxCacheEntries = 20;
myAutoCompTo.queryMatchSubset = true;

// Some settings (bcc field)
myAutoCompBCC.delimChar = ",";
myAutoCompBCC.maxResultsDisplayed = 5;
myAutoCompBCC.minQueryLength = 1;
myAutoCompBCC.queryDelay = 0;
// Was causing problems with interaction between YUI autocomplete and contacts list popup, so its disabled
myAutoCompTo.forceSelection = false;
// Was causing some user interaction issues, so its disabled
myAutoCompBCC.typeAhead = false;
myAutoCompTo.maxCacheEntries = 20;
myAutoCompTo.queryMatchSubset = true;

/**
 * This function checks if user is added already
 *
 * @param string sentype 'to', 'cc', 'bcc'
 * @param string userid
 * @return boolean true if already sending, false if new participant
 */
function alreadySending(sendtype, userid) {
    var old = null;

    if (old = document.getElementsByName(sendtype+'[]')) {
        for (var i=0; i < old.length; i++) {
            if ( userid == old[i].value ) {
                return true;
            }
        }
    } else {
        return false;
    }
}