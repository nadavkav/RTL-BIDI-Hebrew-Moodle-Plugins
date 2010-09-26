/**
 * Javascript for drag-and-drop matching question.
 *
 * @copyright &copy; 2007 Adriane Boyd
 * @author adrianeboyd@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package aab_ddmatch
 */

(function() {

var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;
var DDM = YAHOO.util.DragDropMgr;

// Override DDM moveToEl function to prevent it from repositioning items
// since MoodleDDMatchItem repositions them in the document.
DDM.moveToEl = function(srcEl, targetEl) {
    return;
}

MoodleDDMatchItem = function(id, sGroup, config, dragstring) {

    MoodleDDMatchItem.superclass.constructor.call(this, id, sGroup, config);

    var el = this.getDragEl();
    Dom.setStyle(el, "opacity", 0.67); // The proxy is slightly transparent

    this.sGroup = sGroup;
    this.isTarget = false;
    this.dragstring = dragstring;
};

YAHOO.extend(MoodleDDMatchItem, YAHOO.util.DDProxy, {

    startDrag: function(x, y) {
        // make the proxy look like the source element
        var dragEl = this.getDragEl();
        var clickEl = this.getEl();

        dragEl.innerHTML = clickEl.innerHTML;

        Dom.addClass(dragEl, "matchdrag");
    },

    endDrag: function(e) {
        var proxy = this.getDragEl();

        var proxyid = proxy.id;
        var thisid = this.id;

        Dom.setStyle(proxyid, "visibility", "hidden");
        Dom.setStyle(thisid, "visibility", "");
    },

    onDragDrop: function(e, id) {
        // get the drag and drop object that was targeted
        var oDD;

        if ("string" == typeof id) {
            oDD = DDM.getDDById(id);
        } else {
            oDD = DDM.getBestMatch(id);
        }

        var el = this.getEl();

        // move the item into the target, deleting anything already in the slot
        this.moveItem(el, oDD.getEl());

        Dom.replaceClass(oDD.getEl(), "matchover", "matchdefault");
    },

    onDragEnter: function(e, id) {
        // get the drag and drop object that was targeted
        var oDD;

        if ("string" == typeof id) {
            oDD = DDM.getDDById(id);
        } else {
            oDD = DDM.getBestMatch(id);
        }

        Dom.replaceClass(oDD.getEl(), "matchdefault", "matchover");
    },

    onDragOut: function(e, id) {
        // get the drag and drop object that was targeted
        var oDD;

        if ("string" == typeof id) {
            oDD = DDM.getDDById(id);
        } else {
            oDD = DDM.getBestMatch(id);
        }

        Dom.replaceClass(oDD.getEl(), "matchover", "matchdefault");
    },

    onInvalidDrop: function(e, id) {
        var el = this.getEl();
        // if the item was dragged off a target, delete it
        if (el.parentNode.id.match("target")) {
            // add dragstring back to empty box
            idparts = el.id.split("_");
            li = document.createElement("li");
            li.setAttribute("id", idparts[0] + "_0");
            li.appendChild(document.createTextNode(this.dragstring));
            el.parentNode.appendChild(li);

            // delete the item
            el.parentNode.removeChild(el);
        }
    },

    moveItem: function(el1, el2) {
        el1parent = el1.parentNode;

        // remove the item currently in the target
        for (i = 0; i < el2.childNodes.length; i++) {
            el2.removeChild(el2.childNodes[0]);
        }

        // if the item was moved from the origin, make a copy and move
        if (el1parent.id.match("origin")) {
            el1copy = el1.cloneNode(true);
            el1copy.setAttribute("id", "");
            el1id = Dom.generateId(el1copy, "_");
            el1copy.setAttribute("id", el1.id + el1id);
            el2.appendChild(el1copy);
            new MoodleDDMatchItem(el1copy.id, this.sGroup, '', this.dragstring);
        }
        // else move item
        else {
            el1parent.removeChild(el1);
            el2.appendChild(el1);
        }
    }

});

})();

// Replace the drop-down menus with drop targets and initialize the draggables
function ddMatchingInit(vars) {
    id = vars.id;
    questions = vars.questions;
    answers = vars.answers;
    responses = vars.responses;
    readonly = vars.readonly;
    dragstring = vars.dragstring;

    tdid = "td" + id;
    menuid = "menu" + id;

    Dom = YAHOO.util.Dom;

    qlength = questions.length;
    var td = null;

    for (i = 0; i < qlength; i++) {
        ul = document.createElement("ul");
        ul.setAttribute("id", "ultarget" + id + questions[i]);
        if (responses[i] == 0) {
            li = document.createElement("li");
            li.setAttribute("id", "drag" + id + "0");
            li.appendChild(document.createTextNode(dragstring));
            ul.appendChild(li);
        } else {
            li = Dom.get("drag" + id + responses[i]);
            licopy = li.cloneNode(true);
            licopy.setAttribute("id", "");
            liid = Dom.generateId(licopy, "drag" + id + responses[i] + "_");
            licopy.setAttribute("id", liid);
            ul.appendChild(licopy);
            if (!readonly) {
                new MoodleDDMatchItem(licopy.id, id, '', dragstring);
            }
        }
        Dom.addClass(ul, "matchtarget");
        Dom.addClass(ul, "matchdefault");
        if (!readonly) {
            new YAHOO.util.DDTarget("ultarget" + id + questions[i], id);
        }

        td = Dom.get(tdid + questions[i]);
        menu = Dom.get(menuid + questions[i]);
        td.replaceChild(ul, menu);
    }

    if (!readonly) {
        alength = answers.length
        for (i = 0; i < alength; i++) {
            new MoodleDDMatchItem("drag" + id + answers[i], id, '', dragstring);
        }
    }
}

// Set the hidden response variables according to the id of the item currently
// in each target list
function ddMatchingSetHiddens(event, vars) {;
    var id = vars.id;
    var questions = vars.questions;

    var Dom = YAHOO.util.Dom;

    var ul = null;
    var items = null;
    var answer = "0";

    for (i = 0; i < questions.length; i++) {
        ul = Dom.get("ultarget" + id + questions[i]);

        items = ul.getElementsByTagName("li");
        if (items.length > 0) {
            itemid = items[0].id; // there should only be one item in the list
            itemidparts = itemid.split("_");
            answer = itemidparts[1];
        }
        else {
            answer = "0";
        }

        hidden = Dom.get("hidden" + id + questions[i]);
        hidden.value = answer;
    }
}

// Cross-browser method to create variables with the name attribute
function createElementWithName(type, name) {
    try {
        // This should work in IE and throw an exception everywhere else
        var element = document.createElement('<' + type + ' name="' + name + '">');
    } catch (e) {
        // Compliant method that doesn't work in IE
        var element = document.createElement(type);
        element.setAttribute("name", name);
    }

    return element;
}
