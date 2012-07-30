
// Add functions to provide the data needed for the pop up 
YAHOO.ajax_marking_block.assignment = {};
YAHOO.ajax_marking_block.assignment.pop_up_arguments  = function(clicked_node) {
    return 'menubar=0,location=0,scrollbars,resizable,width=780,height=630';
};
//YAHOO.ajax_marking_block.assignment_final.pop_up_post_data  = function (node) {
//    return 'id='+node.data.aid+'&userid='+node.data.sid+'&mode=single&offset=0';
//};
YAHOO.ajax_marking_block.assignment.pop_up_closing_url = function (clicked_node) {
    return '/mod/assignment/submissions.php';
};
YAHOO.ajax_marking_block.assignment.pop_up_opening_url = function (clicked_node) {
    return '/mod/assignment/submissions.php?id='+clicked_node.data.aid+'&userid='+clicked_node.data.sid+'&mode=single&offset=0';
};
YAHOO.ajax_marking_block.assignment.extra_ajax_request_arguments = function (clicked_node) {
    return '';
};

/**
 * this function is called every 100 milliseconds once the assignment pop up is called
 * and tries to add the onclick handlers until it is successful. There are a few extra
 * checks in the following functions that appear to be redundant but which are
 * necessary to avoid errors. The part of /mod/assignment/lib.php at line 509 tries to
 * update the main window with $this->update_main_listing($submission). This fails because
 * there is no main window with the submissions table as there would have been if the pop
 * up had been generated from the submissions grading screen. To avoid the errors,
 *
 *
 * NOTE: the offset system for saveandnext depends on the sort state having been stored in the
 * $SESSION variable when the grading screen was accessed (which may not have happened, as we
 * are not coming from the submissions.php grading screen or may have been a while ago). The
 * sort reflects the last sort mode the user asked for when ordering the list of pop-ups, e.g.
 * by clicking on the firstname column header. I have not yet found a way to alter this variable
 * using javascript - ideally, the sort would be the same as it is in the list presented in the
 * marking block. Until a work around is found, the save and next function is be a bit wonky,
 * sometimes showing next when there is only one submission, so I have hidden it.
 */
YAHOO.ajax_marking_block.assignment.alter_popup = function(node_id, user_id) {

    var els  ='';
    var els2 = '';
    var els3 = '';

    if (YAHOO.ajax_marking_block.pop_up_holder.closed) {
        window.clearInterval(YAHOO.ajax_marking_block.timerVar);
        return true;
    }

    // when the DOM is ready, add the onclick events and hide the other buttons
    if (YAHOO.ajax_marking_block.pop_up_holder.window.document) {
        if (YAHOO.ajax_marking_block.pop_up_holder.window.document.getElementsByName) {
            els = YAHOO.ajax_marking_block.pop_up_holder.window.document.getElementsByName('submit');
            // the above line will not return anything until the pop up is fully loaded
            if (els.length > 0) {
                // To keep the assignment javascript happy, we need to make some divs for it to
                // copy the grading data to, just as it would if it was called from the main
                // submission grading screen. Line 710-728 of /mod/assignment/lib.php can't be
                // dealt with easily, so there will be an error if outcomes are in use, but
                // hopefully, that won't be so frequent.

                // TODO see if there is a way to grab the outcome ids from the pop up and make
                // divs using them that will match the ones that the javascript is looking for
                var div = document.createElement('div');
                div.setAttribute('id', 'com'+user_id);
                div.style.display = 'none';

                var textArea = document.createElement('textarea');
                textArea.setAttribute('id', 'submissioncomment'+user_id);
                textArea.style.display = 'none';
                textArea.setAttribute('rows', "2");
                textArea.setAttribute('cols', "20");
                div.appendChild(textArea);
                window.document.getElementById('javaValues').appendChild(div);

                var div2 = document.createElement('div');
                div2.setAttribute('id', 'g'+user_id);
                div2.style.display = 'none';
                window.document.getElementById('javaValues').appendChild(div2);

                var textArea2 = document.createElement('textarea');
                textArea2.setAttribute('id', 'menumenu'+user_id);
                textArea2.style.display = 'none';
                textArea2.setAttribute('rows', "2");
                textArea2.setAttribute('cols', "20");
                window.document.getElementById('g'+user_id).appendChild(textArea2);

                var div3 = document.createElement('div');
                div3.setAttribute('id', 'ts'+user_id);
                div3.style.display = 'none';
                window.document.getElementById('javaValues').appendChild(div3);

                var div4 = document.createElement('div');
                div4.setAttribute('id', 'tt'+user_id);
                div4.style.display = 'none';
                window.document.getElementById('javaValues').appendChild(div4);

                var div5 = document.createElement('div');
                div5.setAttribute('id', 'up'+user_id);
                div5.style.display = 'none';
                window.document.getElementById('javaValues').appendChild(div5);

                var div6 = document.createElement('div');
                div6.setAttribute('id', 'finalgrade_'+user_id);
                div6.style.display = 'none';
                window.document.getElementById('javaValues').appendChild(div6);

                // now add onclick
                var functionText  = "return YAHOO.ajax_marking_block.main_instance.remove_node_from_tree(-1, '";
                    functionText += node_id+"', false); ";

                els[0]["onclick"] = new Function(functionText);
                //els[0]["onclick"] = new Function("return YAHOO.ajax_marking_block.remove_node_from_tree(-1, YAHOO.ajax_marking_block.main, '"+me+"', false); "); // IE
                els2 = YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByName('saveandnext');

                if (els2.length > 0) {
                    els2[0].style.display = "none";
                    els3 = YAHOO.ajax_marking_block.pop_up_holder.document.getElementsByName('next');
                    els3[0].style.display = "none";
                }
                // cancel the timer loop for this function
                window.clearInterval(YAHOO.ajax_marking_block.timerVar);

            }
        }
    }
};




