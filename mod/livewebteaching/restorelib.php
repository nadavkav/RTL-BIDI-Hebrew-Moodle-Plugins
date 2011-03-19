<?php 

/**
 *  Backup
 *
 *  @copyright 2011 Victor Bautista (victor [at] sinkia [dt] com)
 *  @package   mod_livewebteaching
 *  @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 *  
 *  This file is free software: you may copy, redistribute and/or modify it  
 *  under the terms of the GNU General Public License as published by the  
 *  Free Software Foundation, either version 2 of the License, or any later version.  
 *  
 *  This file is distributed in the hope that it will be useful, but  
 *  WITHOUT ANY WARRANTY; without even the implied warranty of  
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU  
 *  General Public License for more details.  
 *  
 *  You should have received a copy of the GNU General Public License  
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.  
 *
 *  This file incorporates work covered by the following copyright and permission notice:
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @copyright 2010 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */


    //This php script contains all the stuff to backup/restore
    //livewebteaching mods

    //This is the "graphical" structure of the livewebteaching mod:   
    //
    //                       livewebteaching 
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
    function livewebteaching_restore_mods($mod,$restore) {

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
          
            //Now, build the LABEL record structure
            $livewebteaching->course = $restore->course_id;
            $livewebteaching->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $livewebteaching->moderatorpass= backup_todb($info['MOD']['#']['MODERATORPASS']['0']['#']);
            $livewebteaching->viewerpass= backup_todb($info['MOD']['#']['VIEWERPASS']['0']['#']);
            $livewebteaching->wait= backup_todb($info['MOD']['#']['WAIT']['0']['#']);
            $livewebteaching->meetingid= backup_todb($info['MOD']['#']['MEETINGID']['0']['#']);
            $livewebteaching->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the livewebteaching
            $newid = insert_record ("livewebteaching",$livewebteaching);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","livewebteaching")." \"".format_string(stripslashes($livewebteaching->name),true)."\"</li>";
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

    function livewebteaching_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

/**
        if ($livewebteachings = get_records_sql ("SELECT l.id, l.moderatorpass, l.viewerpass, l.wait, l.meetingid
                                   FROM {$CFG->prefix}livewebteaching l
                                   WHERE l.course = $restore->course_id")) {
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($livewebteachings as $livewebteaching) {
                //Increment counter
                $i++;
                $content = $livewebteaching->content;
                $result = restore_decode_content_links_worker($content,$restore);

                if ($result != $content) {
                    //Update record
                    $livewebteaching->content = addslashes($result);
                    $status = update_record("livewebteaching", $livewebteaching);
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
**/
        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function livewebteaching_restore_logs($restore,$log) {
                    
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
