<?php
/**
 * Activity management
 *
 * @author Jeff Graham, Mark Nielsen
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot.'/course/format/page/plugin/action.php');

class format_page_action_activities extends format_page_action {

    function display() {
        global $CFG, $PAGE, $COURSE;

        require_capability('moodle/course:manageactivities', $this->context);

        get_all_mods($COURSE->id, $mods, $modnames, $modnamesplural, $modnamesused);

        // Right now storing modules in a section corresponding to the current
        // page - probably should all be section 0 though
        if ($COURSE->id == SITEID) {
            $section = 1; // Front page only has section 1 - so use 1 as default
        } else if (isset($page->id)) {
            $section = $page->id;
        } else {
            $section = 0;
        }

        $PAGE->print_tabs('activities');
        print_box_start('boxwidthwide boxaligncenter pageeditingtable', 'editing-table');
        print_section_add_menus($COURSE, $section, $modnames);

        if (!empty($modnamesused)) {
            $modinfo = get_fast_modinfo($COURSE);

            $vars          = new stdClass;
            $vars->delete  = get_string("delete");
            $vars->update  = get_string("update");
            $vars->sesskey = sesskey();

            foreach ($modnamesused as $module => $modnamestr) {
                $orderby = 'i.name';
                $fields  = 'i.id, i.name, m.name '.sql_as().' module, c.id '.sql_as().'cmid, c.visible';

                // Going to be executing SQL that could fail on purpose - avoid debug messages
                $debug      = $CFG->debug; // Store current value
                $CFG->debug = false;       // disable_debugging() doesn't seem to work

                // Check for field named type
                if (execute_sql("SELECT type FROM {$CFG->prefix}$module WHERE 1 = 2", false)) {
                    // Has type - incorperate it into the sql
                    $orderby = "i.type, $orderby";
                    $fields  = "$fields, i.type";
                }
                // Restore debug value
                $CFG->debug = $debug;

                $instances = get_records_sql("SELECT $fields
                                                FROM {$CFG->prefix}course_modules c,
                                                     {$CFG->prefix}modules m,
                                                     {$CFG->prefix}$module i
                                               WHERE i.course = $COURSE->id
                                                 AND m.name = '$module'
                                                 AND c.instance = i.id
                                                 AND c.module = m.id
                                            ORDER BY $orderby");
                if (!empty($instances)) {
                    $lasttype = '';

                    print "<h2><a href=\"$CFG->wwwroot/mod/$module/index.php?id=$COURSE->id\">$modnamestr</a></h2>\n";
                    print "<ul class=\"activity-list\">\n";

                    foreach ($instances as $instance) {
                        if (!empty($instance->type) and $lasttype != $instance->type) {
                            if (!empty($lasttype)) {
                                // Switching types and it isn't the first time, close previously opened list
                                print "</ul>\n</li>\n";
                            }
                            // Try to get a name for the type (check module first)
                            $strtype = get_string($instance->type, $module);
                            if (strpos($strtype, '[') !== false) {
                                $strtype = get_string($module.':'.$instance->type, 'format_page');
                            }
                            print "<li><p><strong>$strtype</strong></p>\n<ul>\n";
                            $lasttype = $instance->type;
                        }
                        if (!empty($modinfo->cms[$instance->cmid]->icon)) {
                            $icon = "$CFG->pixpath/".urldecode($modinfo->cms[$instance->cmid]->icon);
                        } else {
                            $icon = "$CFG->modpixpath/$module/icon.gif";
                        }
                        if (empty($instance->visible)) {
                            $linkclass = ' class="dimmed"';
                        } else {
                            $linkclass = '';
                        }

                        print '<li>';
                        print '<img src="'.$icon.'" class="icon" />';
                        print "<a$linkclass href=\"$CFG->wwwroot/mod/$module/view.php?id=$instance->cmid\">".format_string(strip_tags($instance->name), true, $COURSE->id).'</a>&nbsp;';
                        print '<span class="commands">';
                        print "<a title=\"$vars->update\" href=\"$CFG->wwwroot/course/mod.php?update=$instance->cmid&amp;sesskey=$vars->sesskey\">";
                        print "<img src=\"$CFG->pixpath/t/edit.gif\" class=\"icon-edit\" alt=\"$vars->update\" /></a>&nbsp;";
                        print "<a title=\"$vars->delete\" href=\"$CFG->wwwroot/course/format/page/format.php?id=$COURSE->id&amp;action=deletemod&amp;sesskey=$vars->sesskey&amp;cmid=$instance->cmid\">";
                        print "<img src=\"$CFG->pixpath/t/delete.gif\" class=\"icon-edit\" alt=\"$vars->delete\" /></a></span>";
                        print "</li>\n";
                    }
                    if (!empty($lasttype)) {
                        // Close type list since we know it was opened
                        print "</ul>\n</li>\n";
                    }
                    print "</ul>\n";
                }
            }
        } else {
            print_box(get_string('noacivitiesfound', 'format_page'));
        }

        print_box_end();
    }
}

?>