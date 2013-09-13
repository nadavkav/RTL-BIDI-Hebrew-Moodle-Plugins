<?php //$Id: restorelib.php,v 1.13 2006/09/18 09:13:04 moodler Exp $
    //This php script contains all the stuff to backup/restore
    //tab mods

    //This is the "graphical" structure of the tab mod:   
    //
    //                       TAB 
    //                    (CL,pk->id)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function tab_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug
          
            //Now, build the tab record structure
            $tab->course = $restore->course_id;
            $tab->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $tab->tab1 = backup_todb($info['MOD']['#']['TAB1']['0']['#']);
			$tab->tab2 = backup_todb($info['MOD']['#']['TAB2']['0']['#']);
			$tab->tab3 = backup_todb($info['MOD']['#']['TAB3']['0']['#']);
			$tab->tab4 = backup_todb($info['MOD']['#']['TAB4']['0']['#']);
			$tab->tab5 = backup_todb($info['MOD']['#']['TAB5']['0']['#']);
			$tab->tab6 = backup_todb($info['MOD']['#']['TAB6']['0']['#']);
			$tab->tab7 = backup_todb($info['MOD']['#']['TAB7']['0']['#']);
			$tab->tab8 = backup_todb($info['MOD']['#']['TAB8']['0']['#']);
			$tab->tab9 = backup_todb($info['MOD']['#']['TAB9']['0']['#']);
			$tab->tab0 = backup_todb($info['MOD']['#']['TAB0']['0']['#']);
			$tab->tab1content = backup_todb($info['MOD']['#']['TAB1CONTENT']['0']['#']);
			$tab->tab2content = backup_todb($info['MOD']['#']['TAB2CONTENT']['0']['#']);
			$tab->tab3content = backup_todb($info['MOD']['#']['TAB3CONTENT']['0']['#']);
			$tab->tab4content = backup_todb($info['MOD']['#']['TAB4CONTENT']['0']['#']);
			$tab->tab5content = backup_todb($info['MOD']['#']['TAB5CONTENT']['0']['#']);
			$tab->tab6content = backup_todb($info['MOD']['#']['TAB6CONTENT']['0']['#']);
			$tab->tab7content = backup_todb($info['MOD']['#']['TAB7CONTENT']['0']['#']);
			$tab->tab8content = backup_todb($info['MOD']['#']['TAB8CONTENT']['0']['#']);
			$tab->tab9content = backup_todb($info['MOD']['#']['TAB9CONTENT']['0']['#']);
			$tab->tab0content = backup_todb($info['MOD']['#']['TAB0CONTENT']['0']['#']);
			$tab->css = backup_todb($info['MOD']['#']['CSS']['0']['#']);
			$tab->menucss = backup_todb($info['MOD']['#']['MENUCSS']['0']['#']);
			$tab->displaymenu = backup_todb($info['MOD']['#']['DISPLAYMENU']['0']['#']);
			$tab->menuname = backup_todb($info['MOD']['#']['MENUNAME']['0']['#']);
            $tab->timemodified = $info['MOD']['#']['TIMEMODIFIED']['0']['#'];
 
            //The structure is equal to the db, so insert the tab
            $newid = insert_record ("tab",$tab);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","tab")." \"".format_string(stripslashes($tab->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
   
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    function tab_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        if ($tabs = get_records_sql ("SELECT l.id, l.content
                                   FROM {$CFG->prefix}tab l
                                   WHERE l.course = $restore->course_id")) {
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($tabs as $tab) {
                //Increment counter
                $i++;
                $content = $tab->content;
                $result = restore_decode_content_links_worker($content,$restore);

                if ($result != $content) {
                    //Update record
                    $tab->content = addslashes($result);
                    $status = update_record("tab", $tab);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }
        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function tab_restore_logs($restore,$log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
