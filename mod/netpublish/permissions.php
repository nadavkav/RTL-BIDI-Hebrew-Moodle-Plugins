<?php

/*

    What does this page do???
        This page prints out students who you can share your article with.
        When you click around from group to group, the page will submit to
        itself.  Any students not printed out but have been checked, will
        be printed out as hidden fields so we can remember that they were
        checked.

        When you click on a checkbox, it returns read[studentid]=studentid&write[studentid]=studentid&etc
        back to the original page for saving.

        This page gets its original permission values from the form value rights that
        is on the page that called it.  Sends rights as GET variable to this page

        Hope this helps.

*/

/// array_chunk fix for older php versions

if (! function_exists('array_chunk')) {

    function array_chunk ($input, $size, $preservekeys=false) {

        $outarray = array();

        if (!$preservekeys) {
            $buffer  = array();

            foreach ($input as $value) {
                $buffer[] = $value;

                if (count($buffer) == $size) {
                    array_push($outarray, $buffer);
                    $buffer = array();
                }
            }
            // Push rest of array
            array_push($outarray, $buffer);

        } else {
            $buffer = array();

            foreach ($input as $key => $value) {
                $buffer[$key] = $value;

                if (count($buffer) == $size) {
                    array_push($outarray, $buffer);
                    $buffer = array();
                }
            }
            // push rest of array
            array_push($outarray, $buffer);
        }

        return $outarray;

    }
}

require_once('../../config.php');
include('lib.php');

$courseid = required_param('id', PARAM_INT);
$groupid = optional_param('groupid', -1, PARAM_INT);

define('OUTPUTSIZE', 20);  // this is how many students you see per page  (Even number is smartest for looks)

// need some security check here... I think? for read and write
// these are ones that have been checked and submitted to the page
if(isset($_REQUEST['read'])) {
    $read = $_REQUEST['read'];
} else {
    $read = array();
}

if(isset($_REQUEST['write'])) {
    $write = $_REQUEST['write'];
} else {
    $write = array();
}

// either grab all the students or grab all the students in a given group
if ($groupid != -1) {
    $students = get_group_users($groupid, 'u.firstname ASC, u.lastname ASC');

} else {
    $students = get_course_students($courseid, "u.firstname ASC, u.lastname ASC", "", 0, 99999,
                                    '', '', NULL, '', 'u.id,u.firstname,u.lastname');
}

if (!$groups = get_groups($courseid)) {
    $groups = array();
}

// dont think we need to pass anything here... ?
print_header("", "", "", "", "", true, "");

print_heading(get_string('permissions','netpublish'), 'center', 3);

// start the form off
echo "<form method=\"post\" action=\"permissions.php\" name=\"theform\" id=\"theform\">
    <input type=\"hidden\" name=\"id\" value=\"$courseid\" />
    <input type=\"hidden\" name=\"groupid\" value=\"\" />";
// making tabs
$tabs = array();
$tabrows = array();

// this tab is for viewing all the students
$tabrows[] = new tabobject(-1, "javascript:document.theform.groupid.value=-1; document.theform.submit();", get_string('viewall', 'netpublish'));
// create a tab foreach group
foreach ($groups as $group) {
    $tabrows[] = new tabobject($group->id, "javascript:document.theform.groupid.value=$group->id; document.theform.submit();", $group->name);
}
$tabs[] = $tabrows;
print_tabs($tabs, $groupid);

if (!empty($students)) {  // no too likly

    // chunk up the student array to make our "pages"
    $chunks = array_chunk($students, OUTPUTSIZE);

    $readpermission  = get_string('readpermission','netpublish');
    $writepermission = get_string('writepermission','netpublish');
    $count = 0;
    $links = array();
    $display = "inline";

    // all table properties
    $table = new stdClass;
    $table->head = array("", $readpermission, $writepermission);
    $table->align = array("left", "left", "left");
    $table->wrap = array("", "", "");
    $table->width = "60%";
    $table->size = array("*", "*", "*");

    if (count($students) != 1) {
        $table->head = array_merge($table->head, array("", $readpermission, $writepermission));
        $table->align = array_merge($table->align, array("left", "left", "left"));
        $table->wrap = array_merge($table->wrap, array("", "", ""));
        $table->size = array_merge($table->size, array("*", "*", "*"));
    }
    // printing a table for each chunk/page
    foreach ($chunks as $students) {
        $table->data = array();

        $i = 0;

        // these are checks for when dealing with POST data
        $writecheck = false;
        $readcheck = false;

        foreach ($students as $student) {
            // if this student is being printed out, then we need to get it out of
            // our read and/or write arrays because we do not want to double print it
            if (in_array($student->id, $read)) {
                unset($read[array_search($student->id, $read)]);
                $readcheck = true;
            }
            if (in_array($student->id, $write)) {
                unset($write[array_search($student->id, $write)]);
                $writecheck = true;
            }

            $number = (($i + 1) % 2);
            // this if alternates with the next if to create 2 columns of students
            if ($number) {
                $tablerow = array();
            }
                $tablerow[] = $student->firstname.' '.$student->lastname;
                $checkbox =  "<input type=\"checkbox\" onclick=\"set_value()\" id=\"read\" name=\"read[]\" value=\"$student->id\"";
                $checkbox .= sprintf($readcheck) ? " checked=\"true\"" : "";
                $checkbox .= " />\n";
                $tablerow[] = $checkbox;
                $checkbox = "<input type=\"checkbox\" onclick=\"set_value()\" id=\"write\" name=\"write[]\" value=\"$student->id\"";
                $checkbox .= sprintf($writecheck) ? " checked=\"true\"" : "";
                $checkbox .= " />\n";
                $tablerow[] = $checkbox;

            if (!$number) {
                $table->data[] = $tablerow;
                // need to unset because we check for it later
                unset($tablerow);
            }

            // reset checks
            $writecheck = false;
            $readcheck = false;
            $i++;
        }
        // this is the link to show this table/page
        $countplus = $count+1;
        $links[] = '<a href="javascript: show_hide_rights(\'netpublish-rights'.$count.'\')">'.$countplus.'</a>';

        // if the $tablerow isset, then it has NOT been added.  So lets do it :)
        if(isset($tablerow)) {
            $table->data[] = $tablerow;
        }

        // first one visible, all rest hidden
        echo '<div id="netpublish-rights'.$count.'" style="display: '.$display.';">';
        if (count($chunks) != 1) { // only show heading with multiple pages
            print_heading(get_string('page', 'netpublish')." $countplus", 'center', 4);
        }
        print_table($table);
        echo '</div>';

        $display = "none";
        $count++;
    }
    echo '<div style="padding:10px;" align="center">';
    if (count($chunks) != 1) { // only show links on multiple pages
        echo implode(', ', $links);
    }
    echo '<p>'.close_window_button().'</p></div>';  // close window

    // this is where we generate hidden fields to keep track of students that
    // have been checked, but not already printed out
    foreach ($read as $readhidden) {
        echo '<input type="hidden" id="read" name="read[]" value="'.$readhidden.'" />';
    }
    foreach ($write as $writehidden) {
        echo '<input type="hidden" id="write" name="write[]" value="'.$writehidden.'" />';
    }

    echo '</form>';

} else {
    echo '<div align="center">';
    print_string('nostudentsfound', 'netpublish');
    echo '</div>';
}




?>

<script type="text/javascript">
//<![CDATA[

function show_hide_rights (id) {
    hide_all(); // first hide all

    // then display the one we want
    document.getElementById(id).style.display = 'inline';
}

// this hides all the netpublish-rights pages
function hide_all () {
    num=0;

    while (document.getElementById('netpublish-rights'+num)) {
        document.getElementById('netpublish-rights'+num).style.display = 'none';
        num = num + 1;
    }
}

// on every click, sets opener.document.forms['theform'].rights.value with all the reads and rights
// we do this on every click, so if the user closes the window at any moment, we will still have
// all the data.  We send the data back as an array definition.
function set_value() {
    var values = '';
    // go through all the read form fields
    //read = document.theform.read;
    read = document.getElementById('theform').read;
    if (!read.length) {
        if (read.checked) {
            values = "read["+read.value+"]="+read.value+"&";
        }
    } else {
        for (i = 0; i < read.length; i++) {
            if (read[i].checked) {
                values += "read["+read[i].value+"]="+read[i].value+"&";
            }
        }
    }
    // go through all the write form fields
    //write = document.theform.write;
    write = document.getElementById('theform').write;
    if (!write.length) {
        if (write.checked) {
            values += "write["+write.value+"]="+write.value;
        }
    } else {
        for (i = 0; i < write.length; i++) {
            if (write[i].checked) {
                values += "write["+write[i].value+"]="+write[i].value+"&";
                //if ((i+2) != write.length) {
                //    values +="&";
                //}
            }
        }
    }
    // send it off
    opener.document.getElementById('rights').value = values;
    //opener.document.forms['theform'].rights.value = values;
}

//]]>
</script>

<?php
    print_footer();
?>