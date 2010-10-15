<?php
/**
 * Backup Routine
 *
 * @author Mark Nielsen
 * @version $Id: backuplib.php,v 1.1 2009/12/21 01:01:25 michaelpenne Exp $
 * @package pagemenu
 **/

/**
 * This is the "graphical" structure of the pagemenu mod:
 *
 *         pagemenu
 *        (CL,pk->id)
 *             |
 *             |
 *             |
 *        pagemenu_links
 *    (pk->id,fk->pagemenuid)
 *             |
 *             |
 *             |
 *     pagemenu_link_data
 *     (pk->id,fk->linkid)
 *
 * Meaning: pk->primary key field of the table
 *          fk->foreign key to link with parent
 *          CL->course level info
 *          UL->user level info
 **/

/**
 * Cycles through all of the pagemenu instances
 * in a course and calls {@link pagemenu_backup_one_mod()}
 * to backup each.
 *
 * @param object $bf Backup file
 * @param object $preferences Backup preferences
 * @return boolean
 **/
function pagemenu_backup_mods($bf, $preferences) {
    $status = true;

    // Iterate over pagemenu table
    $pagemenus = get_records('pagemenu', 'course', $preferences->backup_course, 'id');
    if ($pagemenus) {
        foreach ($pagemenus as $pagemenu) {
            if (backup_mod_selected($preferences, 'pagemenu', $pagemenu->id)) {
                $status = pagemenu_backup_one_mod($bf, $preferences, $pagemenu);
            }
        }
    }
    return $status;
}

/**
 * Starts the backup of a whole module instance
 *
 * @param object $bf Backup file
 * @param object $preferences Backup preferences
 * @param object $pagemenu A full pagemenu record object
 * @return boolean
 **/
function pagemenu_backup_one_mod($bf, $preferences, $pagemenu) {
    $status = true;

    if (is_numeric($pagemenu)) {
        $pagemenu = get_record('pagemenu', 'id', $pagemenu);
    }

    // Start mod
    fwrite ($bf,start_tag('MOD',3,true));
    // Print pagemenu data
    fwrite ($bf,full_tag('ID',4,false,$pagemenu->id));
    fwrite ($bf,full_tag('MODTYPE',4,false,'pagemenu'));
    fwrite ($bf,full_tag('NAME',4,false,$pagemenu->name));
    fwrite ($bf,full_tag('RENDER',4,false,$pagemenu->render));
    fwrite ($bf,full_tag('DISPLAYNAME',4,false,$pagemenu->displayname));
    fwrite ($bf,full_tag('USEASTAB',4,false,$pagemenu->useastab));
    fwrite ($bf,full_tag('TABORDER',4,false,$pagemenu->taborder));
    fwrite ($bf,full_tag('TIMEMODIFIED',4,false,$pagemenu->timemodified));

    // Backup Links
    if (!$status = backup_pagemenu_links($bf, $preferences, $pagemenu)) {
        debugging('Link Backup Failed!');
    }

    // End mod
    if ($status) {
        $status = fwrite ($bf,end_tag('MOD',3,true));
    }

    return $status;
}

/**
 * Backup all links for a given pagemenu instance
 *
 * @param object $bf Backup file
 * @param object $preferences Backup preferences
 * @param object $pagemenu A full pagemenu record object
 * @return boolean
 **/
function backup_pagemenu_links($bf, $preferences, $pagemenu) {
    static $loaded = false;

    if (!$loaded) {
        global $CFG;

        // Backup routine has enough performance problems - only call this once
        require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');
        $loaded = true;
    }

    $status = true;

    // Backup links in order - makes restore much more pleasant
    if ($linkid = pagemenu_get_first_linkid($pagemenu->id) and
        $links  = get_records('pagemenu_links', 'pagemenuid', $pagemenu->id)) {

        fwrite ($bf,start_tag('LINKS',4,true));

        while ($linkid) {
            $link = $links[$linkid];

            fwrite ($bf,start_tag('LINK',5,true));
            fwrite ($bf,full_tag('ID',6,false,$link->id));
            fwrite ($bf,full_tag('PREVID',6,false,$link->previd));
            fwrite ($bf,full_tag('NEXTID',6,false,$link->nextid));
            fwrite ($bf,full_tag('TYPE',6,false,$link->type));

            if (!$status = backup_pagemenu_link_data($bf, $preferences, $link)) {
                debugging('Failed to backup link data');
                break;
            }
            fwrite ($bf,end_tag('LINK',5,true));

            $linkid = $link->nextid;
        }

        if ($status) {
            $status = fwrite ($bf,end_tag('LINKS',4,true));
        }
    }

    return $status;
}

/**
 * Backup all link data for a given link instance
 *
 * @param object $bf Backup file
 * @param object $preferences Backup preferences
 * @param object $link A full link record object
 * @return boolean
 **/
function backup_pagemenu_link_data($bf, $preferences, $link) {
    $status = true;

    if ($data = get_records('pagemenu_link_data', 'linkid', $link->id)) {
        fwrite ($bf,start_tag('DATA',6,true));

        foreach ($data as $datum) {
            fwrite ($bf,start_tag('DATUM',7,true));
            fwrite ($bf,full_tag('ID',8,false,$datum->id));
            fwrite ($bf,full_tag('NAME',8,false,$datum->name));
            fwrite ($bf,full_tag('VALUE',8,false,$datum->value));
            fwrite ($bf,end_tag('DATUM',7,true));
        }

        $status = fwrite ($bf,end_tag('DATA',6,true));
    }

    return $status;
}

/**
 * Return an array of info (name,value) for a single instance
 *
 * @param object $instance Module instance
 * @param int $backup_unique_code Backup code
 * @return array
 **/
function pagemenu_check_backup_mods_instances($instance, $backup_unique_code) {

    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    $info[$instance->id.'0'][1] = '';

    // Links
    $info[$instance->id.'1'][0] = get_string('links', 'pagemenu');
    $info[$instance->id.'1'][1] = count_records('pagemenu_links', 'pagemenuid', $instance->id);

    return $info;
}

/**
 * Return an array of info (name,value)
 *
 * @param int $course Course ID
 * @param boolean $user_data Backing up user data flag
 * @param int $backup_unique_code Backup code
 * @param array $instances An array of instances
 * @return array
 **/
function pagemenu_check_backup_mods($course, $user_data = false, $backup_unique_code, $instances = null) {

    if (!empty($instances) and is_array($instances) and count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += pagemenu_check_backup_mods_instances($instance,$backup_unique_code);
        }
        return $info;
    }

    if (!$pagemenus = get_records('pagemenu', 'course', $course)) {
        $pagemenus = array();
        $pagemenuids = '0';  // None
    } else {
        $pagemenuids = implode(',', array_keys($pagemenus));
    }

    // First the course data
    $info[0][0] = get_string('modulenameplural', 'pagemenu');
    $info[0][1] = count($pagemenus);

    // Links
    $info[1][0] = get_string('links', 'pagemenu');
    $info[1][1] = count_records_select('pagemenu_links', "pagemenuid IN($pagemenuids)");

    return $info;
}

/**
 * Return a content encoded to support interactivities linking.
 * This is called automatically from the backup procedure.
 *
 * @param string $content The string to search for links in
 * @param object $preferences Backup preferences
 * @return string
 **/
function pagemenu_encode_content_links($content, $preferences) {
    global $CFG;

    $base     = preg_quote($CFG->wwwroot,"/");
    $patterns = array();
    $replaces = array();

    // Link to index.php
    $patterns[] = "/(".$base."\/mod\/pagemenu\/index.php\?id\=)([0-9]+)/";
    $replaces[] = '$@PAGEMENUINDEXBYID*$2@$';

    // Link to view.php
    $patterns[] = "/(".$base."\/mod\/pagemenu\/view.php\?id\=)([0-9]+)/";
    $replaces[] = '$@PAGEMENUVIEWBYID*$2@$';

    // Link to edit.php
    $patterns[] = "/(".$base."\/mod\/pagemenu\/edit.php\?id\=)([0-9]+)/";
    $replaces[] = '$@PAGEMENUEDITBYID*$2@$';

    return preg_replace($patterns, $replaces, $content);
}

?>