YAHOO.ajax_marking_block.quiz = {};

YAHOO.ajax_marking_block.quiz.pop_up_post_data = function (node) {
    return 'mode=grading&action=grade&q='+node.parent.parent.data.id+'&questionid='+node.data.aid+'&userid='+node.data.sid;
}
YAHOO.ajax_marking_block.quiz.pop_up_closing_url = function (node) {
    return '/mod/quiz/report.php';
}
YAHOO.ajax_marking_block.quiz.pop_up_opening_url = function (node) {
    return '/mod/quiz/report.php?mode=grading&q='+node.parent.parent.data.id+'&questionid='+node.data.aid+'&userid='+node.data.sid;
}
YAHOO.ajax_marking_block.quiz.pop_up_arguments = function (node) {
    return 'menubar=0,location=0,scrollbars,resizable,width=780,height=630';
}
YAHOO.ajax_marking_block.quiz.extra_ajax_request_arguments = function (node) {
    if (node.data.type == 'quiz_question') {
        return '&secondary_id='+node.parent.data.id;
    } else {
        return '';
    }
}

/**
 * adds onclick stuff to the quiz popup
 */
YAHOO.ajax_marking_block.quiz.alter_popup = function (node_unique_id) {
    var els = '';
    var lastButOne = '';

    if (YAHOO.ajax_marking_block.pop_up_holder.closed) {
        window.clearInterval(YAHOO.ajax_marking_block.timerVar);
        return true;
    }

    if (typeof YAHOO.ajax_marking_block.pop_up_holder.window.document.getElementsByTagName('input') != 'undefined') {
        // window is open with some input. could be loading lots though.
        els = YAHOO.ajax_marking_block.pop_up_holder.window.document.getElementsByTagName('input');

        if (els.length > 14) {
            // there is at least the DOM present for a single attempt, but if the student has
            // made a couple of attempts, there will be a larger window.
            lastButOne = els.length - 1;

            if (els[lastButOne].value == amVariables.quizSaveString) {

                // the onclick carries out the functions that are already specified in lib.php,
                // followed by the function to update the tree
                // TODO - did this change work?
                var functionText = "return YAHOO.ajax_marking_block.main_instance.remove_node_from_tree('/mod/quiz/report.php', '";
                    functionText += node_unique_id+"', false); "
                els[lastButOne]["onclick"] = new Function(functionText);
                //els[lastButOne]["onclick"] = new Function("return YAHOO.ajax_marking_block.remove_node_from_tree('/mod/quiz/report.php', YAHOO.ajax_marking_block.main, '"+me+"'); ");
                // cancel the loop for this function

                window.clearInterval(YAHOO.ajax_marking_block.timerVar);

            }
        }
    }
};
