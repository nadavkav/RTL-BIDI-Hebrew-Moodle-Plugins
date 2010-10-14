<?php  // $Id: backuplib.php,v 1.2 2008/08/01 11:27:13 tmas Exp $
/**
 * Library of functions to backup for module email
 *
 * @author Toni Mas
 * @version $Id: backuplib.php,v 1.2 2008/08/01 11:27:13 tmas Exp $
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 *
 * modified by Sam Chaffee
 **/

function email_backup_instance($bf, $preferences, $courseid) {

    //are there any emails to backup?

    if ($emails = get_records('email_mail', 'course', $courseid)) {

        fwrite($bf, start_tag('EMAILS', 5, true));

        $folderwithmail = 0;
        $folderswithemail = array();
        // Write in all of the emails
        foreach ($emails as $email) {

            list($folderwithmail, $status) = email_backup_mail($bf, $preferences, $email);

            if (!empty($folderwithmail)) {
                $folderswithemail = array_merge($folderswithemail, $folderwithmail);
            }

        }

        fwrite($bf, end_tag('EMAILS', 5, true));

        //backup the folders
        $where = "course = '$courseid'";
        if (!empty($folderswithemail)) {
            $idsin = implode(', ', $folderswithemail);
            print "Ids in: $idsin";
            $where .= "OR id IN ($idsin)";
        }

        if ($folders = get_records_select('email_folder', $where)) {
            fwrite($bf, start_tag('EMAIL_FOLDERS', 5, true));

            foreach($folders as $folder) {

                email_backup_folder($bf, $preferences, $folder, $folders);

            }

            fwrite($bf, end_tag('EMAIL_FOLDERS', 5, true));
        }


        //any sent email to back up
        if ($sentemails = get_records('email_send', 'course', $courseid)) {
            fwrite($bf, start_tag('SENTEMAILS', 5, true));

            foreach ($sentemails as $sentemail) {

                $status = email_backup_send($bf, $preferences, $sentemail);
            }
            fwrite($bf, end_tag('SENTEMAILS', 5, true));
        }
    }
}

/**
 * This function execute backup to email_mail content.
 * This is executed by email_backup_mods
 *
 * @uses $CFG
 * @param $bf
 * @param $preferences
 * @param $account Account to do backup.
 * @return boolean Success/Fail
 */
function email_backup_mail($bf,$preferences,$email) {

    global $CFG;

    $status = true;
    $folderwithemail = array();

	fwrite($bf, start_tag('EMAIL',6,true));
    fwrite($bf, full_tag('ID', 7, false, $email->id));
    fwrite($bf, full_tag('USERID',7,false,$email->userid));
    fwrite($bf, full_tag('COURSE',7,false,$email->course));
    fwrite($bf, full_tag('SUBJECT',7,false,$email->subject));
    fwrite($bf, full_tag('TIMECREATED',7,false,$email->timecreated));
    fwrite($bf, full_tag('BODY', 7, false, $email->body));

    if ($foldermails = get_records('email_foldermail', 'mailid', $email->id)) {
        fwrite($bf, start_tag('FOLDERMAILS', 7, true));
        foreach ($foldermails as $foldermail) {
            $folderwithemail[] = $foldermail->folderid;
            fwrite($bf, start_tag('FOLDERMAIL', 8, true));
            fwrite($bf, full_tag('MAILID', 9, false, $foldermail->mailid));
            fwrite($bf, full_tag('FOLDERID', 9, false, $foldermail->folderid));
            fwrite($bf, end_tag('FOLDERMAIL', 8, true));
        }
        fwrite($bf, end_tag('FOLDERMAILS', 7, true));
    }
    fwrite($bf, end_tag('EMAIL',6,true));


    return array ($folderwithemail, $status);
}

/**
 * This function execute backup to email_send content.
 * This is executed by email_backup_mods
 *
 * @uses $CFG
 * @param $bf
 * @param $preferences
 * @param $account Account to do backup.
 * @return boolean Success/Fail
 */
function email_backup_send($bf,$preferences, $sentemail) {

    global $CFG;

    $status = true;

	fwrite($bf, start_tag('SENTEMAIL',6,true));
    fwrite($bf, full_tag('USERID',7,false,$sentemail->userid));
    fwrite($bf, full_tag('COURSE',7,false,$sentemail->course));
    fwrite($bf, full_tag('MAILID',7,false,$sentemail->mailid));
    fwrite($bf, full_tag('TYPE',7,false,$sentemail->type));
    fwrite($bf, full_tag('READED', 7, false, $sentemail->readed));
    fwrite($bf, full_tag('SENDED', 7, false, $sentemail->sended));
    fwrite($bf, full_tag('ANSWERED', 7, false, $sentemail->answered));
    fwrite($bf, end_tag('SENTEMAIL',6,true));

    return $status;
}

/**
 * This function execute backup to email_folder content.
 * This is executed by email_backup_mods
 *
 * @uses $CFG
 * @param $bf
 * @param $preferences
 * @param $account Account to do backup.
 * @return boolean Success/Fail
 */
function email_backup_folder($bf,$preferences, $folder, $folders) {

    global $CFG;

    $status = true;

    fwrite($bf, start_tag('EMAIL_FOLDER', 6, true));
    fwrite($bf, full_tag('ID', 7, false, $folder->id));
    fwrite($bf, full_tag('USERID', 7, false, $folder->userid));
    fwrite($bf, full_tag('NAME', 7, false, $folder->name));
    fwrite($bf, full_tag('TIMECREATED', 7, false, $folder->timecreated));
    fwrite($bf, full_tag('ISPARENTTYPE', 7, false, $folder->isparenttype));
    //this means its some subfolder, so check to make sure its parent is being backedup
    $missedparent = false;
    if (empty($folder->isparenttype)) {
        $sql = "SELECT f.*
                FROM {$CFG->prefix}email_folder f, {$CFG->prefix}email_subfolder sf
                WHERE sf.folderchildid = {$folder->id}
                AND sf.folderparentid = f.id";
        if ($parentfolder = get_record_sql($sql)) {
            if (!array_key_exists($parentfolder->id, $folders)) {
                $missedparent = true;
            }
        }
    }

    fwrite($bf, full_tag('COURSE', 7, false, $folder->course));

    //check for subfolders
    if ($subfolders = get_records('email_subfolder', 'folderparentid', $folder->id)) {
        fwrite($bf, start_tag('SUBFOLDERS', 7, true));

        foreach($subfolders as $subfolder) {
            fwrite($bf, start_tag('SUBFOLDER', 8, true));
            fwrite($bf, full_tag('FOLDERCHILDID', 9, false, $subfolder->folderchildid));
            fwrite($bf, end_tag('SUBFOLDER', 8, true));
        }
        fwrite($bf, end_tag('SUBFOLDERS', 7, true));
    }
    fwrite($bf, end_tag('EMAIL_FOLDER', 6, true));

    if ($missedparent) {
        $status = email_backup_folder($bf, $preferences, $parentfolder, $folders);
    }


    return $status;
}

/**
 * This function execute backup to email_subfolder content.
 * This is executed by email_backup_mods
 *
 * @uses $CFG
 * @param $bf
 * @param $preferences
 * @param $folderparent Folder Parent to do backup.
 * @return boolean Success/Fail
 */
function email_backup_subfolder($bf,$preferences,$folderparent) {

    global $CFG;

    $status = true;



    return $status;
}

/**
 * This function execute backup to email_filter content.
 * This is executed by email_backup_mods
 *
 * @uses $CFG
 * @param $bf
 * @param $preferences
 * @param $folder Folder to do backup.
 * @return boolean Success/Fail
 */
function email_backup_filter($bf,$preferences,$folder) {

    global $CFG;

    $status = true;



    return $status;
}

/**
 * This function execute backup to email_foldermail content.
 * This is executed by email_backup_mods
 *
 * @uses $CFG
 * @param $bf
 * @param $preferences
 * @param $folder Folder to do backup.
 * @return boolean Success/Fail
 */
function email_backup_foldermail($bf,$preferences,$folder) {

    global $CFG;

    $status = true;



    return $status;
}



?>