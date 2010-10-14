/**
 * JavaScript for checking or unchecking 
 * all the students or all students in a group.
 *
 * @param toggle Check All/None
 * @param start the first checkbox to be changed
 * @param end the last checkbox to be changed
 * return boolean
 **/
function block_quickmail_toggle(toggle, start, end) {
    // Element ID
    var id = 'mailto'+start;

    // iterate through all of the appropriate checkboxes and change their state
    while(document.getElementById(id) && start != end) {
        document.getElementById(id).checked = toggle;
        start++;
        id = 'mailto'+start;
    }

    return false;
}