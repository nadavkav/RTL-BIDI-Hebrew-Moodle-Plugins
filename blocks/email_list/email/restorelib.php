 <?php  // $Id: restorelib.php,v 1.2 2008/08/01 11:27:13 tmas Exp $
/**
 * Library of functions to restore for module email
 *
 * @author Toni Mas
 * @version $Id: restorelib.php,v 1.2 2008/08/01 11:27:13 tmas Exp $
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 *
 * Modified by Sam Chaffee 2008/07/19
 **/

function email_restore_instance($data, $restore) {

    $status = true;

    //restore the folders first
    if (!empty($data->info) and !empty($data->info['EMAIL_FOLDERS']['0']['#']['EMAIL_FOLDER'])) {
        $info = $data->info['EMAIL_FOLDERS']['0']['#']['EMAIL_FOLDER'];

//            traverse_xmlize($info);                                   //Debug
//            print_object ($GLOBALS['traverse_array']);                //Debug
//            $GLOBALS['traverse_array']='';                            //Debug

        $status = email_folders_restore($info, $restore);

    }

    if (!empty($data->info) and !empty($data->info['EMAILS']['0']['#']['EMAIL'])) {
        $info = $data->info['EMAILS']['0']['#']['EMAIL'];
//            traverse_xmlize($info);                                   //Debug
//            print_object ($GLOBALS['traverse_array']);                //Debug
//            $GLOBALS['traverse_array']='';                            //Debug
        $status = email_restore_mail($info, $restore);

    }

        //restore the sent mail
    if (!empty($data->info) and !empty($data->info['SENTEMAILS']['0']['#']['SENTEMAIL'])) {
        $info = $data->info['SENTEMAILS']['0']['#']['SENTEMAIL'];
//            traverse_xmlize($info);                                   //Debug
//            print_object ($GLOBALS['traverse_array']);                //Debug
//            $GLOBALS['traverse_array']='';                            //Debug
        $status = email_sends_restore($info, $restore);

    }
    return $status;
}


function email_restore_mail ($info, $restore) {

    global $CFG;

    $status = true;

    for($i = 0; $i < count($info); $i++) {
        $emailinfo = $info[$i];

        $email = new stdClass;

         /// Remap user ID
        $oldid = backup_todb($emailinfo['#']['USERID']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'user', $oldid)) {
            $email->userid = $newid->new_id;
        } else {
            // OK, this is bad
            $status = false;
            break;
        }
        unset($oldid, $newid);

    /// Remap course ID
        $oldid = backup_todb($emailinfo['#']['COURSE']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'course', $oldid)) {
            $email->course = $newid->new_id;
        } else {
            // OK, this is bad
            $status = false;
            break;
        }

        $email->subject = backup_todb($emailinfo['#']['SUBJECT']['0']['#']);

        $email->timecreated = backup_todb($emailinfo['#']['TIMECREATED']['0']['#']);

        $email->body = backup_todb($emailinfo['#']['BODY']['0']['#']);

        if (!$newemailid = insert_record('email_mail', $email)) {
                $status = false;
                break;
        }

        //get the old id
        $oldid = backup_todb($emailinfo['#']['ID']['0']['#']);
        //put the new email id for later use
        backup_putid($restore->backup_unique_code, 'email_mail', $oldid, $newemailid);

        //check the email/folder associations
        if (!empty($emailinfo['#']['FOLDERMAILS']['0']['#']['FOLDERMAIL'])) {
            $foldersmailinfo = $emailinfo['#']['FOLDERMAILS']['0']['#']['FOLDERMAIL'];

            //iterate through
            for ($ii = 0;$ii < count($foldersmailinfo); $ii++) {
                $foldermail = $foldersmailinfo[$ii];

                $newfoldermail = new stdClass;

                $newfoldermail->mailid = $newemailid;

                //get the old folder id
                $oldfolderid = backup_todb($foldermail['#']['FOLDERID']['0']['#']);

                //get the new folder id
                if (!$newfolderid = backup_getid($restore->backup_unique_code, 'email_folder', $oldfolderid)) {
                    $status = false;
                    break;
                }

                $newfoldermail->folderid = $newfolderid->new_id;
                //save the new email/folder association
                if (!$newfoldermailid = insert_record('email_foldermail', $newfoldermail)) {
                    $status = false;
                    break;
                }

            }
        }
    }

    return $status;
}


/**
 * This function restores the email_folder.
 *
 * @uses $CFG
 * @param int $account Account ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
function email_folders_restore($info,$restore) {

    global $CFG;

    $status = true;

    for($i = 0; $i < count($info); $i++) {
        $folderinfo = $info[$i];

        $folder = new stdClass;

    /// Remap user ID
        $oldid = backup_todb($folderinfo['#']['USERID']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'user', $oldid)) {
            $folder->userid = $newid->new_id;
        } else {
            // OK, this is bad
            $status = false;
            break;
        }
        unset($oldid);

    /// Remap coruse ID
        $oldid = backup_todb($folderinfo['#']['COURSE']['0']['#']);

        if ($oldid != 0) {
            //the folder is associated with a course
            if ($newid = backup_getid($restore->backup_unique_code, 'course', $oldid)) {

                $folder->course = $newid->new_id;
            } else {
                // OK, this is bad
                $status = false;
                break;
            }
        } else {
            //the folder is not associated with a course
            $folder->course = 0;
        }


        // folder isparenttype
        $folder->isparenttype = backup_todb($folderinfo['#']['ISPARENTTYPE']['0']['#']);

        //folder name
        $folder->name = backup_todb($folderinfo['#']['NAME']['0']['#']);

        // Get the times
        $folder->timecreated = backup_todb($folderinfo['#']['TIMECREATED']['0']['#']);

        //make sure the folder doesn't exists
        if ($existingfolder = get_record('email_folder', 'userid', $folder->userid, 'name', $folder->name, 'course', $folder->course)) {
            $newfolderid = $existingfolder->id;
        } else {
            if (!$newfolderid = insert_record('email_folder', $folder)) {
                $status = false;
                break;
            }
        }

        //we have a new id; find the old and put the new
        $oldid = backup_todb($folderinfo['#']['ID']['0']['#']);

        backup_putid($restore->backup_unique_code, 'email_folder', $oldid, $newfolderid);

        //check for subfolders of this folder
        if (!empty($folderinfo['#']['SUBFOLDERS']['0']['#']['SUBFOLDER'])) {
            $subinfo = $folderinfo['#']['SUBFOLDERS']['0']['#']['SUBFOLDER'];

            $subfolders = array();
            for($ii = 0; $ii < count($subinfo); $ii++) {
                $subfolderinfo = $subinfo[$ii];

                $subfolder = new stdClass;

                //parent folder id is the folder id of the folder we just restored
                $subfolder->folderparentid = $newfolderid;

                //try to get the new id of the child folder, but it may not have been restored yet
                $oldchildid = backup_todb($subfolderinfo['#']['FOLDERCHILDID']['0']['#']);

                $subfolder->oldfolderchildid = $oldchildid;
                $subfolders[] = $subfolder;

            } //end restoring subfolders of this folder
        }  //end if subfolders block
    } //end restoring folders

    //restore the subfolders
    $status = email_subfolders_restore($subfolders, $restore);

    return $status;
}

/**
 * This function restores the email_subfolder.
 *
 * @uses $CFG
 * @param int $folder Folder ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
function email_subfolders_restore($subfolders, $restore) {

    global $CFG;

    $status = true;

//restore the actual subfolders now

    foreach($subfolders as $subfolder) {
        if ($newchildid = backup_getid($restore->backup_unique_code, 'email_folder', $subfolder->oldfolderchildid)) {
            //found the new id
            $subfolder->folderchildid = $newchildid->new_id;
        } else {
            $status = false;
            break;
        }
        //insert the new record
        if (!$newsubid = insert_record('email_subfolder', $subfolder)) {
            $status = false;
            break;
        }
    }

    return $status;
}

/**
 * This function restores the email_filter.
 *
 * @uses $CFG
 * @param int $folder New Folder ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
//function email_filters_restore_mods($folder,$info,$restore) {
//
//    global $CFG;
//
//    $status = true;
//
//    //Get filter array
//    $filters = $info['#']['FILTERS']['0']['#']['FILTER'];
//
//    //Iterate over filters
//    for($i = 0; $i < sizeof($filters); $i++) {
//        $filter_info = $filters[$i];
//        //traverse_xmlize($filter_info);                                                                 //Debug
//        //print_object ($GLOBALS['traverse_array']);                                                  //Debug
//        //$GLOBALS['traverse_array']="";                                                              //Debug
//
//        //We'll need this later!!
//        $oldid = backup_todb($filter_info['#']['ID']['0']['#']);
//
//        //Now, build the EMAIL_FILTER record structure
//        $filter->folderid = $folder;
//        $filter->rule = backup_todb($filter_info['#']['RULE']['0']['#']);
//
//        //The structure is equal to the db, so insert the email_filter
//        $newid = insert_record ('email_subfolder',$filter);
//
//        //Do some output
//        if (($i+1) % 50 == 0) {
//            echo ".";
//            if (($i+1) % 1000 == 0) {
//                echo "<br />";
//            }
//            backup_flush(300);
//        }
//
//        if ($newid) {
//            //We have the newid, update backup_ids
//            backup_putid($restore->backup_unique_code, 'email_filter', $oldid,
//                         $newid);
//        } else {
//            $status = false;
//        }
//    }
//
//    return $status;
//}

/**
 * This function restores the email_foldermail.
 *
 * @uses $CFG
 * @param int $folder New Folder ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
function email_foldersmails_restore_mods($folder,$info,$restore) {

    global $CFG;

    $status = true;



    return $status;
}

/**
 * This function restores the email_send.
 *
 * @uses $CFG
 * @param int $account Account ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
function email_sends_restore($info, $restore) {

    $status = true;

    for($i = 0; $i < count($info); $i++) {
        $sentinfo = $info[$i];

        $sentemail = new stdClass;

        /// Remap user ID
        $oldid = backup_todb($sentinfo['#']['USERID']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'user', $oldid)) {
            $sentemail->userid = $newid->new_id;
        } else {
            // OK, this is bad
            $status = false;
            break;
        }
        unset($oldid, $newid);

    /// Remap course ID
        $oldid = backup_todb($sentinfo['#']['COURSE']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'course', $oldid)) {
            $sentemail->course = $newid->new_id;
        } else {
            // OK, this is bad
            $status = false;
            break;
        }

        unset($oldid, $newid);
        //remap the mailid
        $oldid = backup_todb($sentinfo['#']['MAILID']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'email_mail', $oldid)) {
            $sentemail->mailid = $newid->new_id;
        } else {
            // OK, this is bad
            $status = false;
            break;
        }

        $sentemail->type = backup_todb($sentinfo['#']['TYPE']['0']['#']);

        $sentemail->readed = backup_todb($sentinfo['#']['READED']['0']['#']);

        $sentemail->sended = backup_todb($sentinfo['#']['SENDED']['0']['#']);

        $sentemail->answered = backup_todb($sentinfo['#']['ANSWERED']['0']['#']);

        if (!$newsentid = insert_record('email_send', $sentemail)) {
            $status = false;
            break;
        }
    }

    return $status;
}

?>