// uses 'journal' as the node that will be clicked on will have this type.
YAHOO.ajax_marking_block.journal = {};

YAHOO.ajax_marking_block.journal.pop_up_post_data = function (node) {
    return 'id='+node.data.cmid;
}
YAHOO.ajax_marking_block.journal.pop_up_closing_url = function (node) {
    return '/mod/journal/report.php';
}
YAHOO.ajax_marking_block.journal.pop_up_arguments = function (node) {
    return 'menubar=0,location=0,scrollbars,resizable,width=900,height=500';
}
YAHOO.ajax_marking_block.journal.pop_up_opening_url = function (node) {
    var url  = '/mod/journal/report.php?id='+node.data.cmid+'&group=';
        url += ((typeof(node.data.group)) != 'undefined') ? node.data.group : '0' ;
    return url;
}


/**
 * adds onclick stuff to the journal pop up elements once they are ready.
 * me is the id number of the journal we want
 */
YAHOO.ajax_marking_block.journal.alter_popup = function (node_unique_id) {

    if (YAHOO.ajax_marking_block.pop_up_holder.closed) {
        window.clearInterval(YAHOO.ajax_marking_block.timerVar);
        return true;
    }

    // get the form submit input, which is always last but one (length varies)
    var input_elements = YAHOO.ajax_marking_block.pop_up_holder.window.document.getElementsByTagName('input');

    // TODO - might catch the pop up half loaded. Not ideal.
    if (typeof(input_elements) != 'undefined' && input_elements.length > 0) {
        var key = input_elements.length -1;

       // alert(els.length -1);

        YAHOO.util.Event.on(
            input_elements[key],
            'click',
            function(){
                alert('ok');
                return YAHOO.ajax_marking_block.main_instance.remove_node_from_tree(
                    '/mod/journal/report.php',
                    node_unique_id,
                    false
                );
            }
        );
        // cancel the timer loop for this function
        window.clearInterval(YAHOO.ajax_marking_block.timerVar);
    }
};
