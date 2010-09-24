YAHOO.namespace("example.container");

document.documentElement.className = "yui-pe";

YAHOO.util.Event.onDOMReady(init);

var newWin = null;
var currentMarker = "";
// var for the language strings
var ouwikiStrings;

function init() {
    // Define various event handlers for Dialog
    var handleSubmit = function() {
        var data = this.getData();
        newAnnotation(data.annotationtext);
        this.submit();
    };
    var handleCancel = function() {
        this.cancel();
    };
    var handleSuccess = function(o) {
        var response = o.responseText;
        response = response.split("<!")[0];
        document.getElementById("resp").innerHTML = response;
    };
    var handleFailure = function(o) {
        alert("Submission failed: " + o.status);
    };

    // Remove progressively enhanced content class, just before creating the module
    YAHOO.util.Dom.removeClass("ouwiki-annotation-dialog", "yui-pe-content");

    // Instantiate the Dialog
    YAHOO.example.container.dialog1 = new YAHOO.widget.Dialog("ouwiki-annotation-dialog", 
                            { width: "30em",
                              fixedcenter: true,
                              visible: false, 
                              constraintoviewport : true,
                              draggable: true,
                              zIndex: "103",
                              postmethod : "none",
                              buttons: [ { text:ouwikiStrings.save, handler:handleSubmit, isDefault:true },
                                      { text:ouwikiStrings.cancel, handler:handleCancel } ]
                            });

    // Wire up the success and failure handlers
    YAHOO.example.container.dialog1.callback = { success: handleSuccess,
                             failure: handleFailure };

    // Render the Dialog
    YAHOO.example.container.dialog1.render();

    markers = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation-marker', 'span');

    for (var i=0; i<markers.length; i++) {
            setupmarkers(markers[i],YAHOO.example.container.dialog1);
    }

    // Add event listeners to all the markers
    //YAHOO.util.Event.addListener(markers, "click", YAHOO.example.container.dialog1.show, YAHOO.example.container.dialog1, true);
}

function setupmarkers(marker,mydialog1) {
    marker.style.cursor = "pointer";
    marker.tabIndex = "0";
    marker.onkeydown = function(e) {
        var keycode = null;
        if(e){
            keycode = e.which;
        } else if (window.event) {
            keycode = window.event.keyCode;
        }
        if(keycode == 13 || keycode == 32){
            // call the function that handles adding an annotation
            openNewWindow(marker,mydialog1);
            return false;
        }
    };

    marker.onclick = function() {
        // call the function that handles adding an annotation
        openNewWindow(marker,mydialog1);
        return false;
    };
}

function addAnnotation(id) {
    var dialog = window.open('addannotation.php?id=' + id,
          '_blank', 'scrollbars=no,' + 'resizable=no,width=450,height=220,dependent=yes,dialog=no');
}

function newAnnotation(newtext) {
    // we need the number of the next form textarea
    var annotationcount = document.getElementById('annotationcount');
    var annotationnum = parseInt(annotationcount.firstChild.nodeValue) + 1;

    //create the new form section
    var newfitem = document.createElement('div');
    newfitem.id = 'newfitem'+annotationnum;
    newfitem.className = 'fitem';
    newfitem.style.display = 'none';

    var fitemtitle = document.createElement('div');
    fitemtitle.className = 'fitemtitle';

    var fitemlabel = document.createElement('label');
    fitemlabel.htmlFor = 'id_annotationedit' + annotationnum;
    //create a textnode and add it to the label
    var fitemlabeltext = document.createTextNode(annotationnum);
    fitemlabel.appendChild(fitemlabeltext);
    // append the label to the div
    fitemtitle.appendChild(fitemlabel);

    //create the div for the textarea
    var felement = document.createElement('div');
    felement.className = 'felement ftextarea';

    var textareatext  = document.createTextNode(newtext);
    var felementtextarea = document.createElement('textarea');
    felementtextarea.id = 'id_annotationedit' + annotationnum;
    felementtextarea.name = 'new'+currentMarker.substring(6);
    // we need the textare size set in the moodle form rather than setting explicitly here
    //var textareas = YAHOO.util.Dom.getElementsByClassName('felement ftextarea', 'div');
    felementtextarea.rows = '3';
    felementtextarea.cols = '40';
    felementtextarea.appendChild(textareatext);
    felement.appendChild(felementtextarea);

    newfitem.appendChild(fitemtitle);
    newfitem.appendChild(felement);

    // insert the new fitem before the last fitem (which is the delete orphaned checkbox)
    var fcontainer = YAHOO.util.Dom.getElementsByClassName('fcontainer', 'div');
    var checkbox = YAHOO.util.Dom.getElementsByClassName('felement fcheckbox', 'div');
    fcontainer[0].insertBefore(newfitem, checkbox[0].parentNode);

    markNewAnnotation(annotationnum);

    newfitem.style.display = 'block';
    annotationcount.firstChild.nodeValue = annotationnum;
}

function openNewWindow(marker,mydialog1) {
    currentMarker = marker.id;
    mydialog1.show();
}

function windowOpen() {
    if (newWin == null || newWin.closed) {
        return false;
    }
    return true;
}

function markNewAnnotation(annotationnum) {
    var theMarker = document.getElementById(currentMarker);
    var visualmarker = document.createElement('strong');
    var visualtext = document.createTextNode('('+annotationnum+')');
    visualmarker.appendChild(visualtext);
    theMarker.parentNode.insertBefore(visualmarker,theMarker);
    theMarker.parentNode.removeChild(theMarker);
}