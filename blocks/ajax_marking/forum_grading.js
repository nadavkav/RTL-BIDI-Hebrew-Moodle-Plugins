YAHOO.ajax_marking_block.forum = {};

YAHOO.ajax_marking_block.forum.pop_up_post_data = function (node) {
    return 'd='+node.data.aid+'#p'+node.data.sid;
}
YAHOO.ajax_marking_block.forum.pop_up_closing_url = function (node) {
    return '/mod/forum/discuss.php';
}
YAHOO.ajax_marking_block.forum.pop_up_opening_url = function (node) {
    return '/mod/forum/discuss.php?d='+node.data.aid+'#p'+node.data.sid;
}
YAHOO.ajax_marking_block.forum.pop_up_arguments = function (node) {
    return 'menubar=0,location=0,scrollbars,resizable,width=780,height=630';  
}
YAHOO.ajax_marking_block.forum.extra_ajax_request_arguments = function () {
    return '';
},


/**
 * function to add onclick stuff to the forum ratings button. This button also has no name or id
 * so we identify it by getting the last tag in the array of inputs. The function is triggered
 * on an interval of 1/2 a second until it manages to close the pop up after it has gone to the
 * confirmation page
 */
YAHOO.ajax_marking_block.forum.alter_popup = function (node_unique_id) {
    var input_elements ='';

    if (YAHOO.ajax_marking_block.pop_up_holder.closed) {
        window.clearInterval(YAHOO.ajax_marking_block.timerVar);
        return true;
    }

    // first, add the onclick if possible
    // TODO - did this change work?
    var input_type = typeof YAHOO.ajax_marking_block.pop_up_holder.window.document.getElementsByTagName('input');
    if (input_type != 'undefined') {
    // if (typeof YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByTagName('input') != 'undefined') {
        // The window is open with some input. could be loading lots though.
        input_elements = YAHOO.ajax_marking_block.pop_up_holder.window.document.getElementsByTagName('input');

        if (input_elements.length > 0) {
            var key = input_elements.length -1;
            // Does the last input have the 'send in my ratings string as label, showing that
            // all the rating are loaded?
            if (input_elements[key].value == amVariables.forumSaveString) {
                // IE friendly
                // TODO - did this change work?
                var functionText = "return YAHOO.ajax_marking_block.main_instance.remove_node_from_tree('/mod/forum/rate.php', ";
                    functionText += "'"+node_unique_id+"', false);";
                input_elements[key]["onclick"] = new Function(functionText);
                //els[key]["onclick"] = new Function("return YAHOO.ajax_marking_block.remove_node_from_tree('/mod/forum/rate.php', YAHOO.ajax_marking_block.main, '"+me+"');");
                // cancel loop for this function
                window.clearInterval(YAHOO.ajax_marking_block.timerVar);

            }
        }
    }
};



