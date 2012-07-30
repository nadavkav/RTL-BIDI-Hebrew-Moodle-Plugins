/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

YAHOO.ajax_marking_block.workshop_final = {};

YAHOO.ajax_marking_block.workshop_final.pop_up_arguments  = function(node) {
    return 'menubar=0,location=0,scrollbars,resizable,width=780,height=630';
};
//YAHOO.ajax_marking_block.workshop_final.pop_up_post_data = function (node) {
//    return 'id='+node.data.aid+'&sid='+node.data.sid+'&redirect='+amVariables.wwwroot;
//}
YAHOO.ajax_marking_block.workshop_final.pop_up_closing_url = function (node) {
    return '/mod/workshop/assess.php';
}
YAHOO.ajax_marking_block.workshop_final.pop_up_opening_url = function (node) {
    return '/mod/workshop/assess.php?id='+node.data.aid+'&sid='+node.data.sid+'&redirect='+amVariables.wwwroot;
}


/**
 * workshop pop up stuff
 * function to add workshop onclick stuff and shut the pop up after its been graded.
 * the pop -up goes to a redirect to display the grade, so we have to wait until
 * then before closing it so that the grade is processed properly.
 *
 * note: this looks odd because there are 2 things that needs doing, one after the pop up loads
 * (add onclicks)and one after it goes to its redirect (close window).it is easier to check for
 * a fixed url (i.e. the redirect page) than to mess around with regex stuff to detect a dynamic
 * url, so the else will be met first, followed by the if. The loop will keep running whilst the
 * pop up is open, so this is not very elegant or efficient, but should not cause any problems
 * unless the client is horribly slow. A better implementation will follow sometime soon.
 */
YAHOO.ajax_marking_block.workshop_final.alter_popup = function (node_id, user_id) {

    if (YAHOO.ajax_marking_block.pop_up_holder.closed) {
        window.clearInterval(YAHOO.ajax_marking_block.timerVar);
        return true;
    }

    var els ='';
    // check that the frames are loaded - this can vary according to conditions
    if (typeof YAHOO.ajax_marking_block.pop_up_holder.frames[0] != 'undefined') {
        // TODO - did this cahnge work?
        var currentUrl = YAHOO.ajax_marking_block.pop_up_holder.frames[0].location.href;
        var targetUrl = amVariables.wwwroot+'/mod/workshop/assessments.php';
        if (currentUrl != targetUrl) {
            // this is the early stage, pop up has loaded and grading is occurring
            // annoyingly, the workshop module has not named its submit button, so we have to
            // get it using another method as the 11th input
            els = YAHOO.ajax_marking_block.pop_up_holder.frames[0].document.getElementsByTagName('input');
            if (els.length == 11) {
                // TODO - did this change work?
                var functionText = "return YAHOO.ajax_marking_block.main_instance.remove_node_from_tree(";
                    functionText += "'/mod/workshop/assessments.php', '";
                    functionText += node_id+"', true);";
                els[10]["onclick"] = new Function(functionText);
                // els[10]["onclick"] = new Function("return YAHOO.ajax_marking_block.remove_node_from_tree('/mod/workshop/assessments.php', YAHOO.ajax_marking_block.main, '"+me+"', true);"); // IE
                // cancel timer loop
                window.clearInterval(YAHOO.ajax_marking_block.timerVar);

            }
        }
    }
};

