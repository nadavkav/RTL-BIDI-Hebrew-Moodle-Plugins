// JavaScript for the AJAX marking block

YAHOO.namespace('ajax_marking_block');

/**
 * Make a base class that can be used for the main and config trees. This extends the
 * YUI treeview class ready to add some new functions to it which are common to both the
 * main and config trees.
 */
YAHOO.ajax_marking_block.tree_base = function(tree_div) {

    YAHOO.ajax_marking_block.tree_base.superclass.constructor.call(this, tree_div);

};

// make the class into a subclass of the YUI treeview widget
YAHOO.lang.extend(YAHOO.ajax_marking_block.tree_base, YAHOO.widget.TreeView);

/**
 * function to build the assessment nodes once the AJAX request has returned a data object
 */
YAHOO.ajax_marking_block.tree_base.prototype.build_assessment_nodes = function(nodes_array) {

    var temp_node = '';
    
    // cycle through the array and make the nodes
    var  nodes_length = nodes_array.length;
    for (var m=0;m<nodes_length;m++) {

        // use the object to create a new node
        temp_node = new YAHOO.widget.TextNode(nodes_array[m], YAHOO.ajax_marking_block.node_holder , false);

        // set the node to load data dynamically, unless it is marked as not dynamic e.g. journal
        if ((!this.config) && (nodes_array[m].dynamic == 'true')) {
           temp_node.setDynamicLoad(this.request_node_data);
        }
    }

    // finally, run the function that updates the original node and adds the children
    YAHOO.ajax_marking_block.on_complete_function_holder();

    // the main tree will need the counts updated
    if (!this.config) {
        this.update_parent_node(YAHOO.ajax_marking_block.node_holder);
    }

};

/**
 * This function is called when a node is clicked (expanded) and makes the ajax request
 */
YAHOO.ajax_marking_block.tree_base.prototype.request_node_data = function(clicked_node, onCompleteCallback) {

    // store details of the node that has been clicked in globals for reference by later
    // callback function

    YAHOO.ajax_marking_block.node_holder = clicked_node;

    YAHOO.ajax_marking_block.on_complete_function_holder = onCompleteCallback;
    var request_url = amVariables.wwwroot+'/blocks/ajax_marking/ajax.php';

    // request data using AJAX
    var postData = 'id='+clicked_node.data.id+'&type='+clicked_node.data.type+'&userid='+amVariables.userid;

    if (typeof clicked_node.data.group  != 'undefined') {
        //add group id if its there
        postData += '&group='+clicked_node.data.group;
    }

    // Allow modules to add extra arguments to the AJAX request if necessary
    var type_array = clicked_node.data.type.split('_');

    // TODO - sometimes this is called on 
    var type_object = eval('YAHOO.ajax_marking_block.'+type_array[0]);

    if ((typeof (type_object) != 'undefined') && (typeof (type_object.extra_ajax_request_arguments) != 'undefined')) {
        postData += type_object.extra_ajax_request_arguments(clicked_node);
    }
 
    var request = YAHOO.util.Connect.asyncRequest('POST', request_url, ajax_marking_block_callback, postData);
};

/**
 * function to update the parent assessment node when it is refreshed dynamically so that
 * if more work has been found, or a piece has now been marked, the count for that label will be
 * accurate
 */
YAHOO.ajax_marking_block.tree_base.prototype.update_parent_node = function(parent_node_to_update) {

    // stop at the root one to end the recursion
    if (parent_node_to_update.isRoot()) {
        // updates the tree's HTML after child nodes are added
        this.root.refresh();
        this.update_total_count();
        return true;
    }

    var node_children_length = parent_node_to_update.children.length;

    // if the last child node was just removed, this one is now empty with all
    // outstanding work marked, so we remove it.
    if (node_children_length === 0) {

        this.removeNode(parent_node_to_update, true);

    } else {

        // sum the counts of all the child nodes, then update with the new count
        var running_total = 0;
        var child_count   = '';

        for (var i=0;i<node_children_length;i++) {
            child_count    = parent_node_to_update.children[i].data.count;
            running_total += parseInt(child_count, 10);
        }

        this.update_node_count(parent_node_to_update, running_total);
    }

    if (parent_node_to_update.parent != null) {
        // move up one level so that the change propagates to the whole tree recursively
        this.update_parent_node(parent_node_to_update.parent);
    }
    return true;
    
};

/**
 * function to alter a node's label with a new count once the children are removed or reloaded
 */
YAHOO.ajax_marking_block.tree_base.prototype.update_node_count = function (newNode, newCount) {

    var newLabel       = newNode.data.icon+'(<span class="AMB_count">'+newCount+'</span>) '+newNode.data.name;
    newNode.data.count = newCount;
    newNode.label      = newLabel;
};

/**
 * Creates the initial nodes for both the main block tree or configuration tree.
 */
YAHOO.ajax_marking_block.tree_base.prototype.build_course_nodes = function(nodes_array) {

    var label = '';

    // make the array of nodes
    var nodes_length = nodes_array.length;
    // if the array is empty, say that there is nothing to mark
    if (nodes_length === 0) {

        if (this.config) {
            label = document.createTextNode(amVariables.configNothingString);
        } else {
            label = document.createTextNode(amVariables.nothingString);
        }
        message_div = document.createElement('div');
        message_div.appendChild(label);
        this.div.appendChild(message_div);
        this.icon.removeAttribute('class', 'loaderimage');
        this.icon.removeAttribute('className', 'loaderimage');
        
        if (!document.getElementById('AMBcollapse')) {
            YAHOO.ajax_marking_block.make_footer();
        }

    } else {
        // there is a tree to be drawn

        // cycle through the array and make the nodes
        for (var n=0;n<nodes_length;n++) {
            if (!this.config) { //only show the marking totals if its not a config tree
                label = '('+nodes_array[n].count+') '+nodes_array[n].name;
            } else {
                label = nodes_array[n].name;
            }

            var temp_node = new YAHOO.widget.TextNode(nodes_array[n], this.root, false);

            // save reference in the map for the context menu
            // AJAXtree.textNodeMap[tmpNode1.labelElId] = tmpNode1;

            temp_node.labelStyle = 'icon-course';
            temp_node.setDynamicLoad(this.request_node_data);
        }

        // now make the tree, add the total at the top and remove the loading icon
        this.render();

        // get rid of the loading icon - IE6 is rubbish so use 2 methods
        this.icon.removeAttribute('class', 'loaderimage');
        this.icon.removeAttribute('className', 'loaderimage');

        // add onclick events
        // Main tree option first:
        if (!this.config) {

            // Alter total count above tree
            label = document.createTextNode(amVariables.totalMessage);
            var total = document.getElementById('totalmessage');
            YAHOO.ajax_marking_block.remove_all_child_nodes(total);
            total.appendChild(label);
            this.update_total_count();

            // this function is the listener for the main tree. Event bubbling means that this
            // will catch all node clicks
            this.subscribe(
                "clickEvent",
                function(oArgs) {

                    // ref saves space
                    var node = oArgs.node;
                    // we only need to do anything if the clicked node is one of
                    // the final ones with no children to fetch.

                    if (node.data.dynamic == 'true') {
                        return true;
                    }

                    // if there is already a pop up, just focus on it (not working yet)
                    /*
                    if (typeof(YAHOO.ajax_marking_block.pop_up_holder) != 'undefined') {
                         YAHOO.ajax_marking_block.pop_up_holder.focus();
                         return true;
                    }
                    */


                    // putting window.open into the switch statement causes it to fail in IE6.
                    // No idea why.
                    // var pop_up_opening_url = amVariables.wwwroot;
                    // var pop_up_arguments = 'menubar=0,location=0,scrollbars,resizable,width=780,height=500';
                    var timer_function = '';
                    // var pop_up_closing_url = '';

                    // not used yet - waiting to get web services going so this can be an ajax
                    // call for a panel widget
                    //var pop_up_post_data = '';
                            
                    // Load the correct javascript object from the files that have been included.
                    // The type should always start with the name of the module, so
                    // we extract that first and then use it to access the object of that
                    // name that was created when the page was built by the inclusion
                    // of all the module_grading.js files.
                    // Yes, I know eval is evil, but how can this be done more elegantly?
                    var type_array = node.data.type.split('_');
                    var module_javascript = eval('YAHOO.ajax_marking_block.'+type_array[0]);

                    // Open a pop up with the url and arguments as specified in the module specific object
                    YAHOO.ajax_marking_block.pop_up_holder = window.open(amVariables.wwwroot+module_javascript.pop_up_opening_url(node), '_blank', module_javascript.pop_up_arguments(node));

                    // This function will add the module specifi javascript to the pop up. It is necessary
                    // in order to make the pop up update the main tree and close itself once
                    // the work has been graded
                    timer_function = function() {
                        module_javascript.alter_popup(node.data.uniqueid, node.data.sid);
                    };

                    // keep trying to run the function every 2 seconds till it executes (the pop up
                    // takes time to load)
                    // TODO: can an onload event work for a popup like this?
                    YAHOO.ajax_marking_block.timerVar  = window.setInterval(timer_function, 2000);
                    YAHOO.ajax_marking_block.pop_up_holder.focus();                 
                    return true;
                }
            );
            // Make the footer divs if they don't exist
            if (!document.getElementById('AMBcollapse')) {
                YAHOO.ajax_marking_block.make_footer();
            }

        } else {

            // procedure for config tree nodes:
            // This function is the listener for the config tree that makes the onclick stuff happen
            this.subscribe(
                'clickEvent',
                function(click_arguments) {

                    

                    var AJAXdata  = '';

                    // function to make checkboxes for each of the three main options
                    

                    // remove group nodes from the previous item if they are there.
                    YAHOO.ajax_marking_block.remove_config_groups();

                    var title   = document.getElementById('configInstructions');
                    title.innerHTML = click_arguments.node.data.icon+click_arguments.node.data.name;


                    var formDiv = document.getElementById('configshowform');
                    // grey out the form before ajax call - it will be un-greyed later
                    formDiv.style.color = '#AAA';

                    // add hidden variables so they can be used for the later AJAX calls
                    // If it's a course, we send things a bit differently

                    var hidden1       = document.createElement('input');
                        hidden1.type  = 'hidden';
                        hidden1.name  = 'course';
                        hidden1.value = (click_arguments.node.data.type == 'config_course') ? click_arguments.node.data.id : click_arguments.node.parent.data.id;
                    formDiv.appendChild(hidden1);

                    var hidden2       = document.createElement('input');
                        hidden2.type  = 'hidden';
                        hidden2.name  = 'assessment';
                        hidden2.value = click_arguments.node.data.id;
                    formDiv.appendChild(hidden2);

                    var hidden3   = document.createElement('input');
                        hidden3.type  = 'hidden';
                        hidden3.name  = 'assessmenttype';
                        hidden3.value = (click_arguments.node.data.type == 'config_course') ? 'course' : click_arguments.node.data.type;
                    formDiv.appendChild(hidden3);




                    // For non courses, add a default checkbox that will remove the record
                    if (click_arguments.node.data.type != 'config_course') {
                        YAHOO.ajax_marking_block.makeBox('default', 'config0', amVariables.confDefault, formDiv);

                        YAHOO.ajax_marking_block.makeBox('show',    'config1', amVariables.confAssessmentShow, formDiv);
                        YAHOO.ajax_marking_block.makeBox('groups',  'config2', amVariables.confGroups, formDiv);
                        YAHOO.ajax_marking_block.makeBox('hide',    'config3', amVariables.confAssessmentHide, formDiv);

                    } else {
                        YAHOO.ajax_marking_block.makeBox('show',    'config1', amVariables.confCourseShow, formDiv);
                        YAHOO.ajax_marking_block.makeBox('groups',  'config2', amVariables.confGroups, formDiv);
                        YAHOO.ajax_marking_block.makeBox('hide',    'config3', amVariables.confCourseHide, formDiv);

                    }

                    // now, we need to find out what the current group mode is and display that box as checked.
                    var AJAXUrl   = amVariables.wwwroot+'/blocks/ajax_marking/ajax.php';
                    if (click_arguments.node.data.type !== 'config_course') {
                        AJAXdata += 'courseid='       +click_arguments.node.parent.data.id;
                        AJAXdata += '&assessmenttype='+click_arguments.node.data.type;
                        AJAXdata += '&assessmentid='  +click_arguments.node.data.id;
                        AJAXdata += '&type=config_check';
                    } else {
                        AJAXdata += 'courseid='             +click_arguments.node.data.id;
                        AJAXdata += '&assessmenttype=course';
                        AJAXdata += '&type=config_check';
                    }
                    var request = YAHOO.util.Connect.asyncRequest('POST', AJAXUrl, ajax_marking_block_callback, AJAXdata);

                    return true;
                }
            );
        }
    }
};

/**
 * Make the group nodes for an assessment
 */
YAHOO.ajax_marking_block.tree_base.prototype.build_group_nodes = function(responseArray) {
    // need to turn the groups for this course into an array and attach it to the course
    // node. Then make the groups bit on screen
    // for the config screen??

    var arrayLength = responseArray.length;
    var tmpNode4 = '';

    for (var n =0; n<arrayLength; n++) {

        tmpNode4 = new YAHOO.widget.TextNode(responseArray[n], YAHOO.ajax_marking_block.node_holder, false);

        tmpNode4.labelStyle = 'icon-group';

        // if the groups are for journals, it is impossible to display individuals, so we make the
        // node clickable so that the pop up will have the group screen.
        // TODO make this into a dynamic thing based on another attribute of the data object
        if (responseArray[n].type !== 'journal') {
            tmpNode4.setDynamicLoad(this.request_node_data);
        }
    }

    this.update_parent_node(YAHOO.ajax_marking_block.node_holder);
   // this.update_parent_node(YAHOO.ajax_marking_block.node_holder.parent);
    YAHOO.ajax_marking_block.on_complete_function_holder();
   // this.update_total_count();

};

/**
* makes the submission nodes for each student with unmarked work. Takes ajax data object as input
*/
YAHOO.ajax_marking_block.tree_base.prototype.build_submission_nodes = function(nodesArray) {

    var tmpNode3 = '';

    for (var k=0;k<nodesArray.length;k++) {

        // set up a unique id so the node can be removed when needed
        uniqueId = nodesArray[k].type + nodesArray[k].aid + 'sid' + nodesArray[k].sid + '';

        // set up time-submitted thing for tooltip. This is set to make the time match the
        // browser's local timezone, but I can't find a way to use the user's specified timezone
        // from $USER. Not sure if this really matters.

        var secs = parseInt(nodesArray[k].seconds, 10);
        // javascript likes to work in miliseconds, whereas moodle uses unix format (whole seconds)
        var time = parseInt(nodesArray[k].time, 10)*1000;
        // make a new data object
        var d = new Date();
        // set it to the time we just got above
        d.setTime(time);

        // altered - does this work?
        tmpNode3 = new YAHOO.widget.TextNode(nodesArray[k], YAHOO.ajax_marking_block.node_holder , false);

        // apply a style according to how long since it was submitted

        if (secs < 21600) {
            // less than 6 hours
            tmpNode3.labelStyle = 'icon-user-one';
        } else if (secs < 43200) {
            // less than 12 hours
            tmpNode3.labelStyle = 'icon-user-two';
        } else if (secs < 86400) {
            // less than 24 hours
            tmpNode3.labelStyle = 'icon-user-three';
        } else if (secs < 172800) {
            // less than 48 hours
            tmpNode3.labelStyle = 'icon-user-four';
        } else if (secs < 432000) {
            // less than 5 days
            tmpNode3.labelStyle = 'icon-user-five';
        } else if (secs < 864000) {
            // less than 10 days
            tmpNode3.labelStyle = 'icon-user-six';
        } else if (secs < 1209600) {
            // less than 2 weeks
            tmpNode3.labelStyle = 'icon-user-seven';
        } else {
            // more than 2 weeks
            tmpNode3.labelStyle = 'icon-user-eight';
        }

    }

    // update all the counts on the various nodes
    this.update_parent_node(YAHOO.ajax_marking_block.node_holder);
    //might be a course, might be a group if its a quiz by groups
    this.update_parent_node(YAHOO.ajax_marking_block.node_holder.parent);
    if (!YAHOO.ajax_marking_block.node_holder.parent.parent.isRoot()) {
        this.update_parent_node(YAHOO.ajax_marking_block.node_holder.parent.parent);
        if (!YAHOO.ajax_marking_block.node_holder.parent.parent.parent.isRoot()) {
            this.update_parent_node(YAHOO.ajax_marking_block.node_holder.parent.parent.parent);
        }
    }

    // finally, run the function that updates the original node and adds the children
    YAHOO.ajax_marking_block.on_complete_function_holder();
    this.update_total_count();

    // then add add_tooltips.
    //this.add_tooltips();

};

/**
 * just moved out from ajaxtree, this needs:
 * @ the tree icon div
 * @ the tree div for construction
 * @ the tree loadcounter (what was this for?)
 * @ the tree ajaxcallback function (same for both?)
 */
YAHOO.ajax_marking_block.tree_base.prototype.build_ajax_tree = function() {

    var sUrl = amVariables.wwwroot+'/blocks/ajax_marking/ajax.php';
    var postData = '';

   // if (this.loadCounter === 0) {

        // this means we are making a tree for the first time. Possible redundant now
        // as the above was a check to fix an old bug

        this.icon.setAttribute('class', 'loaderimage');
        this.icon.setAttribute('className', 'loaderimage');

        var request = YAHOO.util.Connect.asyncRequest('POST', sUrl, ajax_marking_block_callback, this.course_post_data);
        this.loadCounter = 1;
    //}
    return true;
};

/**
* function to update the total marking count by a specified number and display it
*/
YAHOO.ajax_marking_block.tree_base.prototype.update_total_count = function() {

    var total_count     = 0;
    var child_count     = 0;
    var children        = this.root.children;
    var children_length = children.length;

    for (var i=0;i<children_length;i++) {
        child_count  = children[i].data.count;
        total_count += parseInt(child_count, 10);
    }
    if (total_count > 0) {
        document.getElementById('totalmessage').style.visibility = 'visible';
        document.getElementById('count').innerHTML = total_count;
        
    } else {
        // hide the count
        document.getElementById('totalmessage').style.visibility = 'collapse';
        YAHOO.ajax_marking_block.remove_all_child_nodes(document.getElementById('count'));
    }
};

/**
* this function updates the tree to remove the node of the pop up that has just been marked,
* then it updates the parent nodes and refreshes the tree
*
*/
YAHOO.ajax_marking_block.tree_base.prototype.remove_node_from_tree = function(window_url, node_unique_id, frames) {

    /// get the node that was just marked and its parent node
    //nodeToRemove = AJAXtree.tree.getNodeByProperty("id", nodeUniqueId);
    var node_to_remove = this.getNodeByProperty("uniqueid", node_unique_id);

    var parent_node = node_to_remove.parent;
    // remove the node that was just marked
    this.removeNode(node_to_remove, true);

    this.update_parent_node(parent_node);
   
    // refresh the tree to redraw the nodes with the new labels
    YAHOO.ajax_marking_block.refresh_tree_after_changes(AJAXtree, frames);

    this.update_total_count();
    //YAHOO.ajax_marking_block.add_tooltips(AJAXtree);

    // no need if its an assignment as the pop up is self closing
    if (window_url != -1) {
        window_closer = "YAHOO.ajax_marking_block.popup_closing_timer('"+window_url+"')";
        setTimeout(window_closer, 500);
    }
};


// the following 2 variables sometimes hold different things e.g. user id or submission
// this holds the assessment id so it can be accessed by other functions
//YAHOO.ajax_marking_block.aidHolder = '';
// this holds the submission id so it can be accessed by other functions.
//YAHOO.ajax_marking_block.sidHolder = '';
// this holds the parent node so it can be referenced by other functions                                                    
YAHOO.ajax_marking_block.node_holder = '';
// this holds the callback function of the parent node so it can be called once all the child
// nodes have been built
YAHOO.ajax_marking_block.on_complete_function_holder = '';
// all pieces of work to be marked. Updated dynamically by altering this.
YAHOO.ajax_marking_block.totalCount = 0
// the div that holds totalCount
YAHOO.ajax_marking_block.valueDiv = '';
// this is the variable used by the openPopup function on the front page. 
YAHOO.ajax_marking_block.pop_up_holder = '';
// this holds the timer that keeps trying to add the onclick stuff to the pop ups as the pop up loads
YAHOO.ajax_marking_block.timerVar = '';


/**
 * ajax success function which checks the data coming in and calls the right function.
 * @
 */
YAHOO.ajax_marking_block.ajax_success_handler = function (o) {

    var type = null;
    var responseArray = null;
    var label = '';

    var YAMB = YAHOO.ajax_marking_block;

    /* uncomment for debugging output for the admin user

    if (userid == 2) {
        var checkDiv = document.getElementById("conf_left");
        checkDiv.innerHTML = o.responseText;
    }

    */
    try {
        responseArray = YAHOO.lang.JSON.parse(o.responseText);
    } catch (error) {
       // TODO - error handling code to prevent silent failure
    }

    // first object holds data about what kind of nodes we have so we can
    // fire the right function.
    if (responseArray != null) {

        type = responseArray[0].type;
        // remove the data object, leaving just the node objects
        responseArray.shift();

        // TODO - these are inconsistent. Some refer to where the request
        // is triggered and some to what it creates.

        switch (type) {

            case 'main':

                YAHOO.ajax_marking_block.main_instance.build_course_nodes(responseArray);
                //YAHOO.ajax_marking_block.build_course_nodes(responseArray, YAHOO.ajax_marking_block.main);
                break;

            case 'course':

                YAHOO.ajax_marking_block.main_instance.build_assessment_nodes(responseArray);
                YAMB.adjust_width_for_ie();
                break;

            case 'groups':

                YAHOO.ajax_marking_block.main_instance.build_group_nodes(responseArray);
                break;

            case 'submissions':

                YAHOO.ajax_marking_block.main_instance.build_submission_nodes(responseArray);
                break;

            case 'config_main_tree':

                YAMB.config_instance.build_course_nodes(responseArray);
                break;

            case 'config_course':

                YAMB.config_instance.build_assessment_nodes(responseArray);
                break;

            case 'config_groups':

                // called when the groups settings have been updated.

                // TODO - only change the select value, don't totally re build them
                YAMB.make_config_groups_list(responseArray);
                break;

            case 'config_set':

                //just need to un-disable the radio button

                if (responseArray[0].value === false) {
                    label = document.createTextNode(amVariables.configNothingString);
                    YAMB.remove_all_child_nodes(YAMB.config_instance.status);
                    YAMB.config_instance.status.appendChild(label);
                } else {
                    YAMB.enable_config_radio_buttons();
                }
                break;

            case 'config_check':
                // called when any data about config comes back after a request (not a data
                // setting request)

                // make the id of the checkbox div
                var checkId = 'config'+responseArray[0].value;

                // make the radio button on screen match the value in the database that was just
                // returned.
                document.getElementById(checkId).checked = true;
                // if its set to 'display by groups', make the groups bit underneath
                if (responseArray[0].value == 2) {
                    // remove the config bit leaving just the groups, which were tacked onto the
                    // end of the returned array
                    responseArray.shift();
                    //make the groups bit
                    YAMB.make_config_groups_list(responseArray);
                }
                //allow the radio buttons to be clicked again
                YAMB.enable_config_radio_buttons();
                break;

            case 'config_group_save':

                if (responseArray[0].value === false) {
                    YAMB.config_instance.status.innerHTML = 'AJAX error';
                } else {
                    YAMB.enable_config_radio_buttons();
                }

                break;

            default:
                
                // aplies to extra node levels from modules
                YAHOO.ajax_marking_block.main_instance.build_assessment_nodes(responseArray);
                break;

        }
    }
};

/**
 * function which fires if the AJAX call fails
 * TODO: why does this not fire when the connection times out?
 */
YAHOO.ajax_marking_block.ajax_failure_handler = function (o) {
    if (o.tId == -1) {
        YAHOO.ajax_marking_block.main_instance.div.innerHTML =  amVariables.collapseString;
    }
    if (o.tId === 0) {
        YAHOO.ajax_marking_block.main_instance.div.innerHTML = amVariables.connectFail;
        YAHOO.ajax_marking_block.main_instance.icon.removeAttribute('class', 'loaderimage');
        YAHOO.ajax_marking_block.main_instance.icon.removeAttribute('className', 'loaderimage');
        if (!document.getElementById('AMBcollapse')) {
            YAHOO.ajax_marking_block.make_footer();
        }
    }
};


/**
 * Obsolete function to make add_tooltips for the whole tree using YUI container widget.
 * Overkill, so disabled
 */
YAHOO.ajax_marking_block.add_tooltips = function(tree) {

    var name = navigator.appName;
    if (name.search('iPhone') == -1) {
        // this is disabled for IE because, although useful, in IE6 (assuming others too) the
        // add_tooltips seem to sometimes remain as an invisible div on top of the tree
        // structure once nodes has expanded, so that some of the child nodes are unclickable.
        // Firefox is ok with it. This is a pain because a person may not remember the full
        // details of the assignment that was set and a tooltip is better than leaving the front
        // page. I will re-enable it once I find a fix

        if (tree.config) {
            return false;
        }
        // 1. all courses loop
        var numberOfCourses = tree.root.children.length;
        for (var i=0;i<numberOfCourses;i++) {
            node = tree.root.children[i];
            YAHOO.ajax_marking_block.make_tooltip(node);
            var numberOfAssessments = tree.root.children[i].children.length;
            for (var j=0;j<numberOfAssessments;j++) {
                // assessment level
                node = tree.root.children[i].children[j];
                YAHOO.ajax_marking_block.make_tooltip(node);
                var numberOfThirdLevelNodes = tree.root.children[i].children[j].children.length;
                for (var k=0;k<numberOfThirdLevelNodes;k++) {
                    // users level (or groups)
                    node = tree.root.children[i].children[j].children[k];
                    check = node.data.time;
                    if (typeof(check) !== null) {
                        YAHOO.ajax_marking_block.make_tooltip(node);
                    }
                    var numberOfFourthLevelNodes = node.children.length;
                    for (var m=0;m<numberOfFourthLevelNodes;m++) {
                        node = tree.root.children[i].children[j].children[k].children[m];
                        YAHOO.ajax_marking_block.make_tooltip(node);
                        var numberOfFifthLevelNodes = node.children.length;
                        for (var n=0;n<numberOfFifthLevelNodes;n++) {
                            node = tree.root.children[i].children[j].children[k].children[m].children[n];
                            YAHOO.ajax_marking_block.make_tooltip(node);
                        }
                    }
                }
            }
        }
        return true;
    }
    return false;
};

/**
 * Makes one of the config checkboxes and attaches it to the form next to the config tree
 */
YAHOO.ajax_marking_block.makeBox = function(value, id, label, formDiv) {

    var box = '';

    try{
        box = document.createElement('<input type="radio" name="showhide" />');
    }catch(error){
        box = document.createElement('input');
    }
    box.setAttribute('type','radio');
    box.setAttribute('name','showhide');
    box.value = value;
    box.id    = id;
    box.onclick = function() {
        YAHOO.ajax_marking_block.request_config_checkbox_data(this);
    };
    formDiv.appendChild(box);

    var boxText = document.createTextNode(label);
    formDiv.appendChild(boxText);

    var breaker = document.createElement('br');
    formDiv.appendChild(breaker);

};


/**
 * This function enables the config popup radio buttons again after the AJAX request has
 * returned a success code.
 *
 */
YAHOO.ajax_marking_block.enable_config_radio_buttons = function() {
    
    var radio = document.getElementById('configshowform');
    radio.style.color = '#000';

    for (var h = 0; h < radio.childNodes.length; h++) {
        if (radio.childNodes[h].name == 'showhide') {
            radio.childNodes[h].setAttribute('disabled', false);
            radio.childNodes[h].disabled = false;
        }
    }
    var groupDiv = document.getElementById('configGroups');
    groupDiv.style.color = '#000';

    for (var i = 0; i < groupDiv.childNodes.length; i++) {
        if (groupDiv.childNodes[i].type == 'checkbox') {
            groupDiv.childNodes[i].setAttribute('disabled', false);
            groupDiv.childNodes[i].disabled = false;
        }
    }
};

/**
 * This function disables the radio buttons when AJAX request is sent so that
 * more than one request can't be sent at once.
 */
YAHOO.ajax_marking_block.disable_config_radio_buttons = function() {

    var radio = document.getElementById('configshowform');
    radio.style.color = '#AAA';

    for (var h = 0; h < radio.childNodes.length; h++) {
        if (radio.childNodes[h].type == 'radio') {
            radio.childNodes[h].setAttribute('disabled',  true);
        }
    }
    var groupDiv = document.getElementById('configGroups');
    groupDiv.style.color = '#AAA';

    for (var i = 0; i < groupDiv.childNodes.length; i++) {
        if (groupDiv.childNodes[i].type == 'checkbox') {
            groupDiv.childNodes[i].setAttribute('disabled', true);
        }
    }
};


/**
 * function to create add_tooltips. When root.refresh() is called it somehow wipes
 * out all the add_tooltips, so it is necessary to rebuild them
 * each time part of the tree is collapsed or expanded
 * add_tooltips for the courses are a bit pointless, so its just the assignments and submissions
 *
 *
 * n.b. the width of the add_tooltips is fixed because not specifying it makes them go narrow in
 * IE6. making them 100% works fine in IE6 but makes FF stretch them across the whole page.
 * 200px is a guess as to a good width for a 1024x768 screen based on the width of the block.
 * Change it in both places below if you don't like it
 *
 * IE problem - the add_tooltips appear to interfere with the submission nodes using ie, so that
 * they are not always clickable, but only when the user clicks the node text rather than the
 * expand (+) icon. Its not related to the timings as using setTimeout to delay the generation
 * of the add_tooltips makes no difference
 */
YAHOO.ajax_marking_block.make_tooltip = function(node) {

    tempLabelEl = node.getLabelEl();
    tempText = node.data.summary;
    tempTooltip = new YAHOO.widget.Tooltip('tempTooltip', { context:tempLabelEl, text:tempText,
                                           showdelay:0, hidedelay:0, width:150, iframe:false,
                                           zIndex:1110} );
};

//
///**
// * The main constructor function for each of the tree objects
// */
//YAHOO.ajax_marking_block.AJAXtree = function(treeDiv, icon, statusDiv, config) {
//
//    this.loadCounter = 0;
//
//    // YAHOO.widget.TreeView.preload();
//    this.tree    = new YAHOO.widget.TreeView(treeDiv);
//
//    // holds a map of textnodes for the context menu
//    // http://developer.yahoo.com/yui/examples/menu/treeviewcontextmenu.html
//    //this.textNodeMap = {};
//
//    this.treeDiv = treeDiv;
//    this.icon    = document.getElementById(icon);
//    this.div     = document.getElementById(statusDiv);
//    this.config  = config;
//
//    /// set the removal of all child nodes each time a node is collapsed (forces refresh)
//    // not needed for config tree
//    if (!config) { // the this keyword gets confused and can't be used for this
//        this.tree.subscribe('collapseComplete', function(node) {
//        // TODO - make this not use a hardcoded reference
//        YAHOO.ajax_marking_block.main.tree.removeChildren(node);
//        });
//    }
//
//    this.root = this.tree.getRoot();
//
//};

/**
 * funtion to refresh all the nodes once the update operations have all been carried out by
 * remove_node_from_tree()
 */

YAHOO.ajax_marking_block.refresh_tree_after_changes = function(tree_object, frames) {
    tree_object.root.refresh();

    // If there are no nodes left, we need to remove the tree altogether
    if (tree_object.root.children.length === 0) {
        YAHOO.ajax_marking_block.remove_all_child_nodes(document.getElementById("totalmessage"));
        YAHOO.ajax_marking_block.remove_all_child_nodes(document.getElementById("count"));
        if(frames) {
            YAHOO.ajax_marking_block.remove_all_child_nodes(tree_object.div);
        }
        tree_object.div.appendChild(document.createTextNode(amVariables.nothingString));
    }
};

/**
* Refresh tree function - for Collapse & refresh link in the main block
*/
YAHOO.ajax_marking_block.refresh_tree = function(tree_object) {

    if (tree_object.root.children.length >0) {

        tree_object.removeChildren(tree_object.root);
        tree_object.root.refresh();
    }

    YAHOO.ajax_marking_block.remove_all_child_nodes(document.getElementById('conf_right'));
    YAHOO.ajax_marking_block.remove_all_child_nodes(document.getElementById('conf_left'));
    YAHOO.ajax_marking_block.remove_all_child_nodes(tree_object.div);
    tree_object.build_ajax_tree();
};

/**
 * Makes a list of groups as checkboxes and appends them to the config div next to the config
 * tree. Called when the 'show by groups' check box is selected for a node.
 */
YAHOO.ajax_marking_block.make_config_groups_list = function(data, tree) {

    var groupDiv = document.getElementById('configGroups');
    YAHOO.ajax_marking_block.remove_all_child_nodes(groupDiv);

    // Closure holding onclick function.
    var config_checkbox_onclick = function() {
            YAHOO.ajax_marking_block.config_checkbox_onclick();
    };

    var dataLength = data.length;
    // Continue the numbering of the ids from 4 (main checkboxes are 1-3). This allows us to
    // disable/enable them
    var idCounter = 4;
    if (dataLength === 0) {
        var emptyLabel = document.createTextNode(amVariables.nogroups);
        groupDiv.appendChild(emptyLabel);
    }
    for (var v=0; v<dataLength; v++) {

        var box = '';
        try{
            box = document.createElement('<input type="checkbox" name="showhide" />');
        }catch(err){
            box = document.createElement('input');
        }
        box.setAttribute('type','checkbox');
        box.setAttribute('name','groups');
        box.id = 'config'+idCounter;
        box.value = data[v].id;
        groupDiv.appendChild(box);

        if (data[v].display == 'true') {
            box.checked = true;
        } else {
            box.checked = false;
        }
        box.onclick = config_checkbox_onclick;

        var label = document.createTextNode(data[v].name);
        groupDiv.appendChild(label);

        var breaker = document.createElement('br');
        groupDiv.appendChild(breaker);
        idCounter++;
    }

    YAHOO.ajax_marking_block.config_instance.icon.removeAttribute('class', 'loaderimage');
    YAHOO.ajax_marking_block.config_instance.icon.removeAttribute('className', 'loaderimage');
    //re-enable the checkboxes
    YAHOO.ajax_marking_block.enable_config_radio_buttons();
};

/**
 * on click function for the groups check boxes on the config screen. clicking sets or unsets
 * a particular group for display.
 */
YAHOO.ajax_marking_block.config_checkbox_onclick = function() {

    var form = document.getElementById('configshowform');

    window.YAHOO.ajax_marking_block.disable_config_radio_buttons();

    // hacky IE6 compatible fix
    for (var c=0;c<form.childNodes.length;c++) {
        switch (form.childNodes[c].name) {
            case 'course':
                var course = form.childNodes[c].value;
                break;
            case 'assessmenttype':
                var assessmentType = form.childNodes[c].value;
                break;
            case 'assessment':
                var assessment = form.childNodes[c].value;
                break;
        }
    }

    // need to construct a space separated list of group ids.
    var groupIds = '';
    var groupDiv = document.getElementById('configGroups');
    var groups = groupDiv.getElementsByTagName('input');
    var groupsLength = groups.length;

    for (var a=0;a<groupsLength;a++) {
        if (groups[a].checked === true) {
            groupIds += groups[a].value+' ';
        }
    }
    // there are no checked boxes
    if (groupIds === '') {
        // Don't leave the db field empty as it will cause confusion between no groups chosen
        // and first time we set this.
        groupIds = 'none';
    }

    var reqUrl = amVariables.wwwroot+'/blocks/ajax_marking/ajax.php';
    var postData = 'id='+course+'&assessmenttype='+assessmentType+'&assessmentid='+assessment;
    postData += '&type=config_group_save&userid='+amVariables.userid+'&showhide=2&groups='+groupIds;

    var request = YAHOO.util.Connect.asyncRequest('POST', reqUrl, ajax_marking_block_callback, postData);
};
//
///**
// * this function is called every 100 milliseconds once the assignment pop up is called
// * and tries to add the onclick handlers until it is successful. There are a few extra
// * checks in the following functions that appear to be redundant but which are
// * necessary to avoid errors. The part of /mod/assignment/lib.php at line 509 tries to
// * update the main window with $this->update_main_listing($submission). This fails because
// * there is no main window with the submissions table as there would have been if the pop
// * up had been generated from the submissions grading screen. To avoid the errors,
// *
// *
// * NOTE: the offset system for saveandnext depends on the sort state having been stored in the
// * $SESSION variable when the grading screen was accessed (which may not have happened, as we
// * are not coming from the submissions.php grading screen or may have been a while ago). The
// * sort reflects the last sort mode the user asked for when ordering the list of pop-ups, e.g.
// * by clicking on the firstname column header. I have not yet found a way to alter this variable
// * using javascript - ideally, the sort would be the same as it is in the list presented in the
// * marking block. Until a work around is found, the save and next function is be a bit wonky,
// * sometimes showing next when there is only one submission, so I have hidden it.
// */
//YAHOO.ajax_marking_block.alter_assignment_popup = function(me, userid) {
//
//    var els  ='';
//    var els2 = '';
//    var els3 = '';
//    YAHOO.ajax_marking_block.t++;
//
//    // when the DOM is ready, add the onclick events and hide the other buttons
//    if (YAHOO.ajax_marking_block.pop_up_holder.document) {
//        if (YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByName) {
//            els = YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByName('submit');
//            // the above line will not return anything until the pop up is fully loaded
//            if (els.length > 0) {
//
//                // To keep the assignment javascript happy, we need to make some divs for it to
//                // copy the grading data to, just as it would if it was called from the main
//                // submission grading screen. Line 710-728 of /mod/assignment/lib.php can't be
//                // dealt with easily, so there will be an error if outcomes are in use, but
//                // hopefully, that won't be so frequent.
//
//                // TODO see if there is a way to grab the outcome ids from the pop up and make
//                // divs using them that will match the ones that the javascript is looking for
//                var div = document.createElement('div');
//                div.setAttribute('id', 'com'+userid);
//                div.style.display = 'none';
//
//                var textArea = document.createElement('textarea');
//                textArea.setAttribute('id', 'submissioncomment'+userid);
//                textArea.style.display = 'none';
//                textArea.setAttribute('rows', "2");
//                textArea.setAttribute('cols', "20");
//                div.appendChild(textArea);
//                window.document.getElementById('javaValues').appendChild(div);
//
//                var div2 = document.createElement('div');
//                div2.setAttribute('id', 'g'+userid);
//                div2.style.display = 'none';
//                window.document.getElementById('javaValues').appendChild(div2);
//
//                var textArea2 = document.createElement('textarea');
//                textArea2.setAttribute('id', 'menumenu'+userid);
//                textArea2.style.display = 'none';
//                textArea2.setAttribute('rows', "2");
//                textArea2.setAttribute('cols', "20");
//                window.document.getElementById('g'+userid).appendChild(textArea2);
//
//                var div3 = document.createElement('div');
//                div3.setAttribute('id', 'ts'+userid);
//                div3.style.display = 'none';
//                window.document.getElementById('javaValues').appendChild(div3);
//
//                var div4 = document.createElement('div');
//                div4.setAttribute('id', 'tt'+userid);
//                div4.style.display = 'none';
//                window.document.getElementById('javaValues').appendChild(div4);
//
//                var div5 = document.createElement('div');
//                div5.setAttribute('id', 'up'+userid);
//                div5.style.display = 'none';
//                window.document.getElementById('javaValues').appendChild(div5);
//
//                var div6 = document.createElement('div');
//                div6.setAttribute('id', 'finalgrade_'+userid);
//                div6.style.display = 'none';
//                window.document.getElementById('javaValues').appendChild(div6);
//
//                // now add onclick
//                // TODO - has this change worked?
//                var functionText = 'return YAHOO.ajax_marking_block.main_instance.remove_node_from_tree(-1, ';
//                    functionText += me+"', false); ";
//                els[0]["onclick"] = new Function(functionText);
//                //els[0]["onclick"] = new Function("return YAHOO.ajax_marking_block.remove_node_from_tree(-1, YAHOO.ajax_marking_block.main, '"+me+"', false); "); // IE
//                els2 = YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByName('saveandnext');
//
//                if (els2.length > 0) {
//                    els2[0].style.display = "none";
//                    els3 = YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByName('next');
//                    els3[0].style.display = "none";
//                }
//                // cancel the timer loop for this function
//                window.clearInterval(YAHOO.ajax_marking_block.timerVar);
//
//            }
//        }
//    }
//};

/**
 * adds onclick stuff to the journal pop up elements once they are ready.
 * me is the id number of the journal we want
 
YAHOO.ajax_marking_block.alter_journal_popup = function (me) {
    // TODO - does this even work?

    var journalClick = function () {

        // get the form submit input, which is always last but one (length varies)
        els = YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByTagName('input');
        var key = els.length -1;

        YAHOO.util.Event.on(
            els[key],
            'click',
            function(){
                return YAHOO.ajax_marking_block.main_instance.remove_node_from_tree(
                    '/mod/journal/report.php',
                    me
                );
            }
        );
    };
     // YAHOO.util.Event.addListener(YAHOO.ajax_marking_block.pop_up_holder, 'load', journalClick);
/*
      var els ='';
      // first, add the onclick if possible
      if (typeof YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByTagName('input') != 'undefined') { // window is open with some input. could be loading lots though.

          els = YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByTagName('input');

          if (els.length > 0) {

              //var key = els.length -1;

              if (els[key].value == amVariables.journalSaveString) {

                  // does the last input have the 'send in my ratings' string as label, showing
                  // that all the rating are loaded?
                  els[key]["onclick"] = new Function("return YAHOO.ajax_marking_block.remove_node_from_tree('/mod/journal/report.php', YAHOO.ajax_marking_block.main, '"+me+"');");
                  // cancel loop for this function
                  window.clearInterval(YAHOO.ajax_marking_block.timerVar);

              }
          }
      }
          
  };
*/


/**
 * function that waits till the pop up has a particular location,
 * i.e. the one it gets to when the data has been saved, and then shuts it.
 */
YAHOO.ajax_marking_block.popup_closing_timer = function (urlToClose) {

    if (!YAHOO.ajax_marking_block.pop_up_holder.closed) {

        if (YAHOO.ajax_marking_block.pop_up_holder.location.href == amVariables.wwwroot+urlToClose) {

            YAHOO.ajax_marking_block.pop_up_holder.close();
            delete  YAHOO.ajax_marking_block.pop_up_holder;

            return;

        } else {

            setTimeout(YAHOO.ajax_marking_block.popup_closing_timer(urlToClose), 1000);
            return;
        }
    }
};

/**
 * IE seems not to want to expand the block when the tree becomes wider.
 * This provides a one-time resizing so that it is a bit bigger
 */
YAHOO.ajax_marking_block.adjust_width_for_ie = function () {
    if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)){

        var el = document.getElementById('treediv');
        var width = el.offsetWidth;
        // set width of main content div to the same as treediv
        var contentDiv = el.parentNode;
        contentDiv.style.width = width;
    }
};

/**
 * The panel for the config tree and the pop ups is the same and is created
 * here if it doesn't exist yet
 */
YAHOO.ajax_marking_block.initialise_config_panel = function () {
    YAHOO.ajax_marking_block.greyOut = new YAHOO.widget.Panel(
            "greyOut",
            {
                width:"470px",
                height:"530px",
                fixedcenter:true,
                close:true,
                draggable:false,
                zindex:110,
                modal:true,
                visible:false,
                iframe: true
            }
        );
};

/**
 * Builds the greyed out panel for the config overlay
 */
YAHOO.ajax_marking_block.build_config_overlay = function() {

    var YAMB = YAHOO.ajax_marking_block;

    if (!YAHOO.ajax_marking_block.greyOut) {

        YAMB.initialise_config_panel();

        var headerText = amVariables.headertext+' '+amVariables.fullname;
        YAMB.greyOut.setHeader(headerText);

        var bodyText = "<div id='configIcon' class='AMhidden'></div><div id='configStatus'>";
            bodyText += "</div><div id='configTree'></div><div id='configSettings'>";
            bodyText += "<div id='configInstructions'>"+amVariables.instructions+"</div>";
            bodyText += "<div id='configCheckboxes'><form id='configshowform' name='configshowform'>";
            bodyText += "</form></div><div id='configGroups'></div></div>";

        YAMB.greyOut.setBody(bodyText);
        document.body.className += ' yui-skin-sam';

        YAMB.greyOut.beforeHideEvent.subscribe(function() {
            YAMB.refresh_tree(YAMB.main_instance);
        });

        YAMB.greyOut.render(document.body);
        YAMB.greyOut.show();
        // Now that the grey overlay is in place with all the divs ready, we build the config tree
        if (typeof (YAMB.config_instance) != 'object') {
            YAMB.config_instance = YAMB.tree_factory('config');
            YAMB.config_instance.build_ajax_tree();
        }

        YAMB.config_instance.icon.setAttribute('class', 'loaderimage');
        YAMB.config_instance.icon.setAttribute('className', 'loaderimage');

    } else {
        // It's all there from earlier, so just show it
        YAMB.greyOut.show();
        YAMB.remove_config_groups();
        YAMB.refresh_tree(YAMB.config);
    }
};


/**
 * the onclick for the radio buttons in the config screen.
 * if show by group is clicked, the groups thing pops up. If another one is, the groups thing
 * is hidden.
 */
YAHOO.ajax_marking_block.request_config_checkbox_data = function(checkbox) {
    // if its groups, show the groups by getting them from the course node?

    var showHide = '';
    var len      = '';
    var form     = '';


    var configSet = function (showHide) {
        var form = document.getElementById('configshowform');

        var len = form.childNodes.length;

        // silly hack to fix the way IE6 will not retrieve data from an input added using
        // appendChild using form.assessment.value
        for (var b=0; b<len; b++) {

            switch (form.childNodes[b].name) {
                case 'assessment':
                    var assessmentValue = form.childNodes[b].value;
                    break;
                case 'assessmenttype':
                    var assessmentType = form.childNodes[b].value;
                    break;

            }
        }
        // make the AJAX request
        var url       = amVariables.wwwroot+'/blocks/ajax_marking/ajax.php';
        var postData  = 'id='+assessmentValue;
            postData += '&type=config_set';
            postData += '&userid='+amVariables.userid;
            postData += '&assessmenttype='+assessmentType;
            postData += '&assessmentid='+assessmentValue
            postData += '&showhide='+showHide;
        var request  = YAHOO.util.Connect.asyncRequest('POST', url, ajax_marking_block_callback, postData);
    }

    //empty the groups area
    var groupDiv = document.getElementById('configGroups');
    while (groupDiv.firstChild) {
        groupDiv.removeChild(groupDiv.firstChild);
    }

    switch (checkbox.value) {

        case 'default':

            configSet(0);
            break;

        case 'show':

            configSet(1);
            break;

        case 'groups':
            //need to set the type of this assessment to 'show groups' and get the groups stuff.
            showHide = 2;
            //get the form div to be able to read the values
            form = document.getElementById('configshowform');

            // silly IE6 bug fix
            for (var c=0;c<form.childNodes.length;c++) {
                switch (form.childNodes[c].name) {
                    case 'course':
                        var course = form.childNodes[c].value;
                        break;
                    case 'assessmenttype':
                        var assessmentType = form.childNodes[c].value;
                        break;
                    case 'assessment':
                        var assessment = form.childNodes[c].value;
                        break;
                }
            }
            var url        = amVariables.wwwroot+'/blocks/ajax_marking/ajax.php';
            var postData   = 'id='+course;
                postData  += '&assessmenttype='+assessmentType;
                postData  += '&assessmentid='+assessment;
                postData  += '&type=config_groups';
                postData  += '&userid='+amVariables.userid;
                postData  += '&showhide='+showHide;
            var request    = YAHOO.util.Connect.asyncRequest('POST', url, ajax_marking_block_callback, postData);
            break;

        case 'hide':

            configSet(3);
            break;
    }
    YAHOO.ajax_marking_block.disable_config_radio_buttons();
};

/**
 * Wipes all the group options away when another node or a course node is clicked in the config
 * tree
 */
YAHOO.ajax_marking_block.remove_config_groups = function() {

    YAHOO.ajax_marking_block.remove_all_child_nodes(document.getElementById('configshowform'));
    YAHOO.ajax_marking_block.remove_all_child_nodes(document.getElementById('configInstructions'));
    YAHOO.ajax_marking_block.remove_all_child_nodes(document.getElementById('configGroups'));
    return true;

};

/**
 * Used by other functions to clear all child nodes from some element
 */
YAHOO.ajax_marking_block.remove_all_child_nodes = function (el) {
    if (el.hasChildNodes()) {
        while (el.hasChildNodes()) {
            el.removeChild(el.firstChild);
        }
    }
};

/**
 * This is to generate the footer controls once the tree has loaded
 */
YAHOO.ajax_marking_block.make_footer = function () {
    // Create all text nodes

    // the two links
    var collapseButton = new YAHOO.widget.Button({
            label:amVariables.refreshString,
            id:"AMBcollapse",
            onclick: {fn: function() {YAHOO.ajax_marking_block.refresh_tree(YAHOO.ajax_marking_block.main_instance)} },
            container:"conf_left" });

    var configButton = new YAHOO.widget.Button({
            label:amVariables.configureString,
            id:"AMBconfig",
            onclick: {fn: function() {YAHOO.ajax_marking_block.build_config_overlay()} },
            container:"conf_right" });

    // Add bits to them like onclick
    // append them to each other and the DOM
};



/**
 * This function is to instantiate the tree_base class in order to create the
 * main and config trees.
 */
YAHOO.ajax_marking_block.tree_factory = function (type) {

    var treeObject = '';

    switch (type) {
        case 'main':
            treeObject                  = new YAHOO.ajax_marking_block.tree_base('treediv');
            treeObject.icon             = document.getElementById('mainIcon');
            treeObject.div              = document.getElementById('status');
            treeObject.course_post_data = 'id='+amVariables.userid+'&type=main&userid=';
            treeObject.course_post_data += amVariables.userid;
            treeObject.config           = false;


            // Set the removal of all child nodes each time a node is collapsed (forces refresh)
            // not needed for config tree
            treeObject.subscribe('collapseComplete', function(node) {
                // TODO - make this not use a hardcoded reference
                YAHOO.ajax_marking_block.main.tree.removeChildren(node);
            });
            break;

        case 'config':
            treeObject                  = new YAHOO.ajax_marking_block.tree_base('configTree');
            treeObject.icon             = document.getElementById('configIcon');
            treeObject.div              = document.getElementById('configStatus');
            treeObject.course_post_data = 'id='+amVariables.userid+'&type=config_main_tree&userid=';
            treeObject.course_post_data += amVariables.userid;
            treeObject.config           = true;
            break;


    }
    treeObject.root = treeObject.getRoot();
    return treeObject;

};

/**
 * Callback object for the AJAX call, which
 * fires the correct function. Doesn't work when part of the main class. Don't know why
 */
var  ajax_marking_block_callback = {

    cache    : false,
    success  : YAHOO.ajax_marking_block.ajax_success_handler,
    failure  : YAHOO.ajax_marking_block.ajax_failure_handler,
    scope    : YAHOO.ajax_marking_block,
    // TODO: find out what this was for as the timeouts seem not to be working
    // argument : 1200,
    timeout  : 10000

};

/**
 * The initialising stuff to get everything started
 */
YAHOO.ajax_marking_block.initialise = function() {
    // workaround for odd https setups. Probably not needed in most cases
    if ( document.location.toString().indexOf('https://') != -1) {
        amVariables.wwwroot = amVariables.wwwroot.replace('http:', 'https:');
    }
    // the context menu needs this for the skin to show up, as do other bits
    document.body.className += ' yui-skin-sam';

    YAHOO.ajax_marking_block.main_instance = YAHOO.ajax_marking_block.tree_factory('main');

    YAHOO.ajax_marking_block.main_instance.build_ajax_tree();
}