<?php
/**
 * User roles report list all the users who have been assigned a particular
 * role in all contexts.
 *
 * @copyright &copy; 2007 The Open University
 * @author t.j.hunt@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package userrolesreport
 *//** */

require_once('../../../config.php');
require_once($CFG->dirroot . '/question/upgrade.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

// New function, the equivalent of require_js in lib/ajax/ajaxlib.php
/**
 * Include a reference to a CSS file when the header is printed.
 * 
 * This function will generate an error if it is called after print_header.
 * 
 * @param $cssurl the URL of the CSS file to include, you probably want to start this with $CFG->wwwroot.
 */
function require_css($cssurl = '') {
    global $CFG;
    static $requiredcss = array();

    if (!empty($cssurl)) {
        if (defined('HEADER_PRINTED')) {
            error('HTML header already printed');
        }

        $testpath = str_replace($CFG->wwwroot, $CFG->dirroot, $cssurl);
        if (!file_exists($testpath)) {        
            error('require_css: '.$cssurl.' - file not found.');
        }

        $requiredcss[$cssurl] = 1;
    } else {
        $output = '';
        foreach ($requiredcss as $css => $ignored) {
            $output .= '<link type="text/css" rel="stylesheet" href="' . $css . '" />' . "\n";
        }
        return $output;
    }
}

// Register our custom form control
MoodleQuickForm::registerElementType('username', "$CFG->dirroot/admin/report/userroles/username.php",
        'MoodleQuickForm_username');

// moodleform for controlling the report
class user_roles_report_form extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('header', 'reportsettings', get_string('reportsettings', 'report_userroles'));
        $mform->addElement('username', 'username', get_string('username'));
        $mform->addElement('submit', 'submit', get_string('getreport', 'report_userroles'));
    }
}
$mform = new user_roles_report_form();

// Start the page.
admin_externalpage_setup('reportuserroles');
admin_externalpage_print_header();
print_heading(get_string('userroleassignments', 'report_userroles'));

// Standard moodleform if statement.
if ($mform->is_cancelled()) {

    // Don't think this will ever happen, but do nothing.

} else if ($fromform = $mform->get_data()){

    if (!(isset($fromform->username) && $user = get_record('user', 'username', $fromform->username))) {
        
        // We got data, but the username was invalid.
        if (!isset($fromform->username)) {
            $message = get_string('unknownuser', 'report_userroles');
        } else {
            $message = get_string('unknownusername', 'report_userroles', $fromform->username);
        }
        print_heading($message, '', 3);
        
    } else {
        // We have a valid username, do stuff.
        $fullname = $fromform->username . ' (' . fullname($user) . ')';

        // Do any role unassignments that were requested.
        if ($tounassign = optional_param('unassign', array(), PARAM_SEQUENCE)) {
            echo '<form method="post" action="', $CFG->wwwroot, '/admin/report/userroles/index.php">', "\n";
            foreach ($tounassign as $assignment) {
                list($contextid, $roleid) = explode(',', $assignment);
                role_unassign($roleid, $user->id, 0, $contextid);
                echo '<input type="hidden" name="assign[]" value="', $assignment, '" />', "\n";
            }
            notify(get_string('rolesunassigned', 'report_userroles'), 'notifysuccess');
            form_fields_to_fool_mform($user->username, $mform);
            echo '<input type="submit" value="', get_string('undounassign', 'report_userroles'), '" />', "\n";
            echo '</form>', "\n";
            
        // Do any role re-assignments that were requested.
        } else if ($toassign = optional_param('assign', array(), PARAM_SEQUENCE)) {
            foreach ($toassign as $assignment) {
                list($contextid, $roleid) = explode(',', $assignment);
                role_assign($roleid, $user->id, 0, $contextid);
            }
            notify(get_string('rolesreassigned', 'report_userroles'), 'notifysuccess');
        }

        // Now get the role assignments for this user.
        $sql = "SELECT
                ra.id, ra.userid, ra.contextid, ra.roleid, ra.enrol,
                c.contextlevel, c.instanceid,
                r.name AS role
            FROM
                {$CFG->prefix}role_assignments ra,
                {$CFG->prefix}context c,
                {$CFG->prefix}role r
            WHERE
                ra.userid = $user->id
            AND ra.contextid = c.id
            AND ra.roleid = r.id
            ORDER BY
                contextlevel DESC, contextid ASC, r.sortorder ASC";
        $results = get_records_sql($sql);

        // Display them.
        if ($results) {
            print_heading(get_string('allassignments', 'report_userroles', $fullname), '', 3);

            // Start of unassign form.
            echo "\n\n";
            echo '<form method="post" action="', $CFG->wwwroot, '/admin/report/userroles/index.php">', "\n";

            // Print all the role assingments for this user.
            $stredit = get_string('edit');
            $strgoto = get_string('gotoassignroles', 'report_userroles');
            foreach ($results as $result) {
                $result->context = print_context_name($result, true, 'ou');
                $value = $result->contextid . ',' . $result->roleid;
                $inputid = 'unassign' . $value;
                
                $unassignable = in_array($result->enrol,
                        array('manual', 'workflowengine', 'fridayeditingcron', 'oucourserole', 'staffrequest'));
                
                echo '<p>';
                if ($unassignable) {
                    echo '<input type="checkbox" name="unassign[]" value="', $value, '" id="', $inputid, '" />', "\n";
                    echo '<label for="', $inputid, '">';
                }
                echo get_string('incontext', 'report_userroles', $result);
                if ($unassignable) {
                    echo '</label>';
                }
                echo ' <a title="', $strgoto, '" href="', $CFG->wwwroot, '/admin/roles/assign.php?contextid=',
                        $result->contextid, '&amp;roleid=', $result->roleid, '"><img ', 
                        'src="', $CFG->pixpath, '/t/edit.gif" alt="[', $stredit, ']" /></a>';
                echo "</p>\n";
            }
            
            echo "\n\n";
            form_fields_to_fool_mform($user->username, $mform);
            echo '<input type="submit" value="', get_string('unassignasabove', 'report_userroles'), '" />', "\n";
            echo '</form>', "\n";
            echo '<p>', get_string('unassignexplain', 'report_userroles'), "</p>\n\n";

        } else {
            print_heading(get_string('noassignmentsfound', 'report_userroles', $fullname), '', 3);
        }
    }
}

// Always show the form, so that the user can run another report.
echo "\n<br />\n<br />\n";
$mform->display();

admin_externalpage_print_footer();

function form_fields_to_fool_mform($username, $mform) {
    echo '<input type="hidden" name="username" value="', $username, '" />', "\n";
    echo '<input type="hidden" name="sesskey" value="', sesskey(), '" />', "\n";
    echo '<input type="hidden" name="_qf__', $mform->_formname, '" value="1" />', "\n";
}
?>