<?php

/**
 * Prints out javascript required to track which modules have been selected
 */
function massaction_print_js() {
    global $COURSE, $CFG, $USER;
    if (ajaxenabled() || ! $COURSE || $COURSE->id == 1) {
        return;
    } else {
        echo "<script type='text/javascript'>";
        include "massaction.js";
        echo "</script>";
    }
}

/**
 * Given an array or an object, converts it into a string value
 * 
 * @param array $array Object to be converted
 * @return string string representation of that array or object
 */
function encode_array($array) {
    return base64_encode(serialize($array));
}

/**
 * Given a string value, converts it into an array or an object
 * 
 * @param string $string Value to be converted
 * @return array Array or object representation of that string
 */
function decode_array($string) {
    return unserialize(base64_decode($string));
}

function print_button($name, $value, $js, $disabled = false) {
    echo "<button type=\"button\" name=\"$name\" onclick=\"$js\" ";
    if ($disabled) {
        echo " disabled = \"disabled\" ";
    }
    echo ">$value </button>\n";
}

/**
 * Given a name and value, prints out a hidden input
 * 
 * @param string $name Name of hidden input
 * @param string $value Value of hidden input
 */
function print_hidden_input($name, $value) {
    return "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
}

/**
 * Given a name and value, prints out a submit input
 *
 * @param string $name Name of submit
 * @param string $value Value of submit
 * @param boolean $disabled optional value states if the submit is disabled
 */
function print_submit_input($name, $value, $disabled = false) {
    global $CFG;
    $return = "<input type=\"submit\" name=\"$name\" value=\"$value\" ";
    if ($disabled) {
        $return .= " disabled = \"disabled\" ";    
    }
    $return .= "/>\n";
    return $return;
}
	
	
/**
 * Given a name and value, prints out a submit text
 *
 * @param string $name Name of submit
 * @param string $icon Action icon file
 * @param boolean $disabled optional value states if the submit is disabled
 */
function print_submit_text($name, $icon) {
    global $CFG;
    $text = get_string($name, 'block_massaction');
    $return = "<tr><td/>";
    $return .= "<td colspan='2'><a href=\"javascript:massaction_submit('act_$name')\">";
    $return .= "<img src=\"{$CFG->pixpath}$icon\" alt='$text' title='$text'/>&nbsp;";
    $return .= $text."</a></td></tr>";
    return $return;
}
	
function generate_form($inst_id) {
    global $USER, $COURSE, $CFG;
    
    $options = print_options($COURSE->id);

    $html = '';
    $html .= "<span id='massaction_javascriptwarning'>".get_string('javascript', 'block_massaction')."</span>";
    $html .= "<form name = \"massactionexecute\" id =\"massactionexecute\" action=\"{$CFG->wwwroot}/blocks/massaction/action.php\" method=\"post\">";
    $html .= print_hidden_input("return_to", $_SERVER["REQUEST_URI"]);
    $html .= print_hidden_input("act_move", "move");
    $html .= print_hidden_input("sesskey", $USER->sesskey);
    $html .= print_hidden_input("courseid", $COURSE->id);
    $html .= print_hidden_input("instance_id", $inst_id);
    $html .= "<table>";
    $html .= "<tr><td colspan = 3><b>".get_string('select', 'block_massaction')."</b></td></tr>";
    $html .= "<tr><td><img src=\"{$CFG->pixpath}/spacer.gif\" style='width:11px';/></td>";
    $html .= "<td colspan = 2><a href=\"javascript:select_all()\">".get_string('selectall', 'block_massaction')."</a></td></tr> ";
    $html .= $options['select'];
    $html .= "<tr><td/><td colspan = 2><a href=\"javascript:deselect_all()\">".get_string('deselectall', 'block_massaction')."</a></td></tr>";

    $html .= "<tr><td><div style='height:8px;'></div></td></tr>";
    $html .= "<tr><td colspan = 3><b>".get_string('with_selected', 'block_massaction')."</b></td></tr>";
    if (right_to_left()) { // support rtl / ltr (nadavkav patch)
        $html .= print_submit_text('outdent', '/t/right.gif');
        $html .= print_submit_text('indent', '/t/left.gif');
    } else {
        $html .= print_submit_text('outdent', '/t/left.gif');
        $html .= print_submit_text('indent', '/t/right.gif');
    }
    $html .= print_submit_text('hide', '/t/show.gif');
    $html .= print_submit_text('show', '/t/hide.gif');
    $html .= print_submit_text('delete', '/t/delete.gif');
    $html .= $options['move'];
    $html .= "<tr><td colspan=4 style='text-align:center; width:100%'>"
           . helpbutton('massaction', get_string('title', 'block_massaction'), 'block_massaction', true, false, NULL, true)
           . "</td></tr>";
    $html .= "</table></form>";
    return $html;
}

function check_permission($instance_id) {
    $context_instance = get_context_instance(CONTEXT_BLOCK, $instance_id);
    return has_capability('block/massaction:canuse', $context_instance);
}

function print_options() {
    global $COURSE;
    $course_format = $COURSE->format;

    $move_to_section_string = '';
    $move_to_section_string .=  "<tr><td/><td colspan = 2><select name='section' onChange='this.form.submit()' format =\"$course_format\">\n";
    $move_to_section_string .= "<option value=\"-1\">".get_string('movetosection', 'block_massaction')."</option>\n";

    $select_section_string = "<tr><td/><td colspan = 2><select name='sel_section' onChange=\"select_section(this.options[this.selectedIndex].value)\">";
    $select_section_string .= "<option value=\"-1\">".get_string('selectsection', 'block_massaction')."</option>\n";

    $num_sections = get_field('course', 'numsections', 'id', $COURSE->id);
    $sections = get_records_select('course_sections', "course = {$COURSE->id} AND section <= $num_sections");
    $weekdate = $COURSE->startdate;
    $weekdate += 7200; // Add two hours to avoid possible DST problems
    $weekofseconds = 604800;
    $monthft = "%B";
    $dayft = "%d";

    $print_counter = 0;

    foreach ($sections as $id => $record) {
        if ($course_format == 'weeks') {
            if ($record->section == 0) {
                $print_value = get_string('weekzero', 'block_massaction');
            } else {

                $weekday = substr(userdate($weekdate, $monthft), 0, 3)
                    ." ".userdate($weekdate, $dayft);

                $nextweekdate = $weekdate + $weekofseconds;
                $nextweekday = substr(userdate($nextweekdate, $monthft), 0, 3)
                    ." ".userdate($nextweekdate, $dayft);

                $print_value = get_string('week', 'block_massaction')." ".$record->section;
                $print_value .= ": $weekday - $nextweekday";
                $weekdate = $nextweekdate;
            }
        }
        else {
            $print_value = get_string('topic', 'block_massaction')." $record->section";	
            $print_counter++;
        }       
        $value = array('id'=>$record->id, 'name'=>$print_value);
        $encoded_val = encode_array($value);
        $select_section_string .= "<option value=\"{$record->section}\">".$print_value."</option>\n";
        $move_to_section_string .= "<option value=\"".$encoded_val."\">".$print_value."</option>\n";
    }
    $select_section_string .= "</select></td></tr>";
    $move_to_section_string .= "</select></td></tr>";
    return array('select' => $select_section_string, 'move' => $move_to_section_string);
}

/**
 *  Given an array of module ids, returns an array of module names
 *
 *  @param array $modids array of module ids
 *  @return array array of module names, indexed by id
 */
	
	
function massaction_get_modnames($modids) {
    global $CFG;
    $inlist = "(".join(", ", $modids).")";
    $course_sql = 
        "SELECT cm.id, mo.name, cm.course FROM {$CFG->prefix}course_modules cm
        LEFT JOIN {$CFG->prefix}modules mo on mo.id=cm.module
        WHERE cm.id IN $inlist";
    $course_records = get_records_sql($course_sql);
    $return_vals = array();
    foreach ($modids as $modid) {
        $modinfo = get_coursemodule_from_id($course_records[$modid]->name, $course_records[$modid]->id, $course_records[$modid]->course);
        $return_vals[$modid] = $modinfo->name;
    }
    return $return_vals;		
}
	
?>
