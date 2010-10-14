<?php

require_once "../../config.php";
require_once "lib.php";
require_once($CFG->libdir .'/dmllib.php');

$sesskey = required_param('sesskey');
$return_to = required_param('return_to');
$instance_id = required_param('instance_id');

$continue = optional_param('continue', false);
$do = optional_param('do', false);
$modids = array();
$section = -1;

foreach ($_POST as $key => $val) {
    if (str_replace('massaction_check_', "", $key) == $val) {
        $modids[] = $val;
    } else if (substr($key, 0, 4) == 'act_') {
        $action = substr($key, 4);
    }
}

if ($encoded_modids = optional_param('modids', false)) {
    $modids = decode_array($encoded_modids);
}

// Turn arrays into strings that can easily be passed
$encoded_post = encode_array($_POST);
$encoded_mods = encode_array($modids);

if (! isset($action) && isset($_POST['action'])) {
    $action = $_POST['action'];
}

if (optional_param('cancel', false)) {
    redirect($return_to);
}

if (! isset($action)) {
    redirect($return_to, "No action was specified");
} else if (empty($modids)) {
    redirect($return_to, "No modules were selected");
}

if (! check_permission($instance_id)) {
    redirect($return_to, "You do not have permission to do that");
}

$include_file = $CFG->dirroot.'/blocks/massaction/'.$action.".php";
$include_file_exists = file_exists($include_file);

if (! $include_file_exists) {
    error("Required file '$include_file' does not exist");
}
require_once($include_file);

$confirm_function = "ma_".$action."_confirm";
$continue_function = "ma_".$action."_continue";
$execute_function = "ma_".$action."_execute";

$confirm_function_exists = function_exists($confirm_function);
$continue_function_exists = function_exists($continue_function);
$execute_function_exists = function_exists($execute_function);

$do_page = $do || ! $confirm_function_exists; // action has been confirmed or there is no confirm function
$confirm_page = ($continue || ! $continue_function_exists) && ! $do_page; // past the continue page, or no continue function

if ($do_page) { // Action has been confirmed OR there is no confirm function    
    if ($do) {
        $modids = decode_array($_POST['modids']);
        $post = decode_array($_POST['post']);
    } else {
        $post = $_POST;
    }
    if ($modids) {
        if (! $execute_function_exists) {
            error("Required function '$execute_function' does not exist");		
        }
        $message = $execute_function($modids, $post);
        rebuild_course_cache($post['courseid']);
        redirect($return_to, $message);
    }
} else {
    $modnames = massaction_get_modnames($modids);

    $string = '';
    // Get the custom string for the chosen action;
    if ($confirm_page) {
        $string .= $confirm_function($modnames, $_POST);
    } else {
        $string .= $continue_function($modnames, $_POST);
    }

    // Print the form

    print_header_simple(get_string($action, 'block_massaction')." (Mass Action)");
    print_simple_box_start('center', '60%', '#FFAAAA', 20, 'noticebox');

    echo $string;

    echo "<form method='post'>\n";
    echo print_hidden_input('post', $encoded_post);
    if ($confirm_page) {
        $name = 'do';
    } else {
        $name = 'continue';
    }

    echo print_hidden_input('modids', $encoded_mods);
    echo print_hidden_input('action', $action);
    echo print_hidden_input('sesskey', $sesskey);
    echo print_hidden_input('return_to', $return_to);
    echo print_hidden_input('courseid', $_POST['courseid']);
    echo print_hidden_input('instance_id', $instance_id);

    echo print_submit_input($name, get_string($action, 'block_massaction'));
    echo print_submit_input('cancel', get_string('cancel', 'block_massaction'));

    echo "</form>\n";
    print_simple_box_end();
    print_footer();
}
?>
