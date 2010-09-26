<?php
    // $backupprefsを自動生成
    if (isset($SESSION->backupprefs[$course->id])) {
        unset($SESSION->backupprefs[$course->id]);
    }
    $backupprefs = new stdClass;
    project_backup_get_preferences($section_i, $backupprefs, $count, $course);

    // Check site
    if (!$site = get_site()) {
        error("Site not found!");
    }
    if ($count == 0) {
        notice("No backupable modules are installed!");
    }

    if (!execute_sql("DELETE FROM {$CFG->prefix}backup_ids WHERE backup_code = '{$backupprefs->backup_unique_code}'", false)) {
        error('Couldn\'t delete previous backup ids.');
    }
?>

<form id="form" method="post" action="backup.php">
<table cellpadding="5" style="text-align:center;margin-left:auto;margin-right:auto">
<?php
    //Here we check if backup_users = None. Then, we switch off every module
    //user info, user_files, logs and exercises, workshop and messages backups. A Warning is showed to
    //inform the user.
    // TODO: Move this logic to one function to be shared by any (manual, scheduled) backup
    if ($backupprefs->backup_users == 2) {
        if ($allmods = get_records('modules') ) {
            foreach ($allmods as $mod) {
            /// Reset global user_info settings to "no" (0)
                $modname = $mod->name;
                $var = 'backup_user_info_'.$modname;
                if (isset($backupprefs->$var)) {
                    $backupprefs->$var = 0;
                }
            /// Reset each instance userinfo settings to "no" (0)
                if (isset($backupprefs->mods[$modname])) {
                /// Set the module userinfo to no (0)
                    $backupprefs->mods[$modname]->userinfo = 0;
                /// Set the instances to no (o)
                // Warningの対策
                    if (is_array($backupprefs->mods[$modname]->instances)) {
                        foreach ($backupprefs->mods[$modname]->instances as $key => $instance) {
                            $backupprefs->mods[$modname]->instances[$key]->userinfo = 0;
                            $var = 'backup_user_info_' . $modname . '_instance_' . $key;
                            $backupprefs->$var = 0;
                        }
                    }
                }
            /// If modules are workshop or exercise, disable their backup completely
                if ($modname == 'exercise' || $modname == 'workshop') {
                    $var = 'backup_'.$modname;
                    if (isset($backupprefs->$var)) {
                        $backupprefs->$var = 0;
                    /// Reset each instance backup settings to "no" (0)
                        if (isset($backupprefs->mods[$modname])) {
                        /// Set the module backup to no (0)
                            $backupprefs->mods[$modname]->backup = 0;
                            $var = 'backup_' . $modname . '_instances';
                            $backupprefs->$var = 0;
                        /// Set the instances backup to no (o)
                            foreach ($backupprefs->mods[$modname]->instances as $key => $instance) {
                                $backupprefs->mods[$modname]->instances[$key]->backup = 0;
                                $var = 'backup_' . $modname . '_instance_' . $key;
                                $backupprefs->$var = 0;
                            }
                        }
                    }
                }
            }
        }
        $backupprefs->backup_user_files = 0;
        $backupprefs->backup_logs = 0;
        $backupprefs->backup_messages = 0;
        $backupprefs->backuproleassignments = array();

        //print_simple_box("<font color=\"red\">".get_string("backupnoneusersinfo")."</font>","center", "70%", '', "20", "noticebox");
        //echo "<hr />";
    }

    if (empty($to_course_id)) {
        //Now print the Backup Name tr
        echo "<tr>";
        echo "<td align=\"right\"><b>";
        echo get_string("name").":";
        echo "</b></td><td>";
        //Add as text field
        echo "<input type=\"text\" name=\"backup_name\" size=\"40\" value=\"".$backupprefs->backup_name."\" />";
        echo "</td></tr>";

        //Line
        echo "<tr><td colspan=\"2\"><hr /></td></tr>";

        //Now print the To Do list
        echo "<tr>";
        echo "<td colspan=\"2\" align=\"center\"><b>";

    }

    if (empty($to_course_id)) {
        echo get_string("backupdetails").":";
        echo "</b></td></tr>";
    }

    //This is the alignment of every row in the table
    $table->align = array ("left","right");
    
    if ($allmods = get_records("modules") ) {
        foreach ($allmods as $mod) {
            $modname = $mod->name;
            $modfile = $CFG->dirroot.'/mod/'.$modname.'/backuplib.php';
            if (!file_exists($modfile)) {
                continue;
            }
            require_once($modfile);
            $modbackup = $modname."_backup_mods";
            //If exists the lib & function
            $var = "exists_".$modname;
            
            //セクションにモジュールが登録されている場合のみ処理を行う
            $modins = $modname.'_instances';
            if (!empty($backupprefs->$modins)) {
                if (isset($backupprefs->$var) && $backupprefs->$var) {
                    $var = "backup_".$modname;
                    //Only if selected
                    if (!empty($backupprefs->$var) and ($backupprefs->$var == 1)) {
                        //Print the full tr
                        echo "<tr>";
                        echo "<td colspan=\"2\">";
                        //Print the mod name
                        echo "<b>".get_string("include")." ".get_string("modulenameplural",$modname)." ";
                        //Now look for user-data status
                        $backup_user_options[0] = get_string("withoutuserdata");
                        $backup_user_options[1] = get_string("withuserdata");
                        $var = "backup_user_info_".$modname;
                        //Print the user info
                        echo $backup_user_options[$backupprefs->$var]."</b>";
                        //Call the check function to show more info
                        $modcheckbackup = $modname."_check_backup_mods";
                        $var = $modname.'_instances';
                        $instancestopass = array();
                        if (!empty($backupprefs->$var) && is_array($backupprefs->$var) && count($backupprefs->$var)) {
                            $table->data = array();
                            $countinstances = 0;
                            foreach ($backupprefs->$var as $instance) {
                                $var1 = 'backup_'.$modname.'_instance_'.$instance->id;
                                $var2 = 'backup_user_info_'.$modname.'_instance_'.$instance->id;
                                if (!empty($backupprefs->$var1)) {
                                    $obj = new StdClass;
                                    $obj->name = $instance->name;
                                    $obj->userdata = $backupprefs->$var2;
                                    $obj->id = $instance->id;
                                    $instancestopass[$instance->id]= $obj;
                                    $countinstances++;
                                }
                            }
                        }
                        if (count($backupprefs->$var)) {
                            $table->data = $modcheckbackup($course_id, $backupprefs->$var, $backupprefs->backup_unique_code, $instancestopass);
                            print_table($table);
                        }
                        echo "</td></tr>";
                    }
                }
            }
        }

        //Now print the User Files tr conditionally
        if ($backupprefs->backup_user_files) {
            echo "<tr>";
            echo "<td colspan=\"2\"><b>";
            echo get_string("includeuserfiles").'</b>';
            //Print info
            $table->data = user_files_check_backup($course_id,$backupprefs->backup_unique_code);
            print_table($table);
            echo "</td></tr>";
        }

        //Now print the Course Files tr conditionally
        if ($backupprefs->backup_course_files) {
            echo "<tr>";
            echo "<td colspan=\"2\"><b>";
            echo get_string("includecoursefiles").'</b>';
            //Print info
            $table->data = project_course_files_check_backup($course_id, $section_i, $backupprefs->backup_unique_code);
            print_table($table);
            echo "</td></tr>";
        }

        //Now print the Course Files tr conditionally
        if ($backupprefs->newdirectoryname) {
            echo "<tr>";
            echo "<td colspan=\"2\"><b>";
            echo get_string('newdirectoryname', 'format_project').'</b>';
            $table->data = array(array(get_string('newdirectoryname', 'format_project'), $backupprefs->newdirectoryname));
            print_table($table);
            echo "</td></tr>";
        }
    }
    
    // now keep it for next time.
    $SESSION->backupprefs[$course->id] = $backupprefs;

?>
</table>
<div style="text-align:center;margin-left:auto;margin-right:auto">
<input type="hidden" name="to"     value="<?php p($to_course_id) ?>" />
<input type="hidden" name="id"     value="<?php  p($course_id) ?>" />
<input type="hidden" name="section" value="<?php p($section_i) ?>" />
<input type="hidden" name="tosection" value="<?php p($to_section_i) ?>" />
<input type="hidden" name="newdirectoryname" value="<?php p($newdirectoryname) ?>" />
<input type="hidden" name="launch" value="execute" />
<input type="submit" value="<?php  print_string("continue") ?>" />
<input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
</div>
</form>
