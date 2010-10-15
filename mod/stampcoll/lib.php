<?php // $Id: lib.php,v 1.8 2008/09/01 22:33:19 mudrd8mz Exp $

/// MODULE CONSTANTS //////////////////////////////////////////////////////

/**
 * Default number of students per page
 */
define('STAMPCOLL_USERS_PER_PAGE', 30);

/**
 * Default stamp image URL
 */
define('STAMPCOLL_IMAGE_URL', $CFG->wwwroot.'/mod/stampcoll/defaultstamp.gif');


/// MODULE FUNCTIONS //////////////////////////////////////////////////////

/**
 * @todo Documenting this function. Capabilities checking
 */
function stampcoll_user_outline($course, $user, $mod, $stampcoll) {
    if ($stamps = get_records_select("stampcoll_stamps", "userid=$user->id AND stampcollid=$stampcoll->id")) {
        $result = new stdClass();
        $result->info = get_string('numberofcollectedstamps', 'stampcoll', count($stamps));
        $result->time = 0;  // empty
        return $result;
    }
    return NULL;
}

/**
 * @todo Documenting this function
 */
function stampcoll_user_complete($course, $user, $mod, $stampcoll) {
    
    global $USER;

    $context = get_context_instance(CONTEXT_MODULE, $mod->id); 
    if ($USER->id == $user->id) {
        if (!has_capability('mod/stampcoll:viewownstamps', $context)) {
            echo get_string('notallowedtoviewstamps', 'stampcoll');
            return true;
        }
    } else {
        if (!has_capability('mod/stampcoll:viewotherstamps', $context)) {
            echo get_string('notallowedtoviewstamps', 'stampcoll');
            return true;
        }
    }

    if (!$allstamps = stampcoll_get_stamps($stampcoll->id)) {
        // no stamps yet in this instance
        echo get_string('nostampscollected', "stampcoll");
        return true;
    }

    $userstamps = array();
    foreach ($allstamps as $s) {
        if ($s->userid == $user->id) {
            $userstamps[] = $s;
        }
    }
    unset($allstamps);
    unset($s);

    if (empty($userstamps)) {
        echo get_string('nostampscollected', 'stampcoll');
    } else {
        echo get_string('numberofcollectedstamps', 'stampcoll', count($userstamps));
        echo '<div class="stamppictures">';
        foreach ($userstamps as $s) {
            echo stampcoll_stamp($s, $stampcoll->image);
        }
        echo '</div>';
        unset($s);
    }
}

/**
 * Create a new instance of stamp collection and return the id number. 
 *
 * @param object $stampcoll Object containing data defined by the form in mod.html
 * @return int ID number of the new instance
 */
function stampcoll_add_instance($stampcoll) {
    $stampcoll->timemodified = time();
    $stampcoll->text = trim($stampcoll->text);
    return insert_record("stampcoll", $stampcoll);
}

/**
 * Update an existing instance of stamp collection with new data.
 *
 * @param object $stampcoll Object containing data defined by the form in mod.html
 * @return boolean
 */
function stampcoll_update_instance($stampcoll) {
    $stampcoll->id = $stampcoll->instance;
    $stampcoll->timemodified = time();
    $stampcoll->text = trim($stampcoll->text);
    return update_record('stampcoll', $stampcoll);
}


/**
 * Delete the instance of stamp collection and any data that depends on it.
 *
 * @param int $id ID of an instance to be deleted
 * @return bool
 */
function stampcoll_delete_instance($id) {
    if (! $stampcoll = get_record("stampcoll", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("stampcoll_stamps", "stampcollid", "$stampcoll->id")) {
        $result = false;
    }

    if (! delete_records("stampcoll", "id", "$stampcoll->id")) {
        $result = false;
    }

    return $result;
}

/**
 * Return the users with data in one stamp collection.
 *
 * Return users with records in stampcoll_stamps.
 *
 * @uses $CFG
 * @param int $stampcollid ID of an module instance
 * @return array Array of unique users
 */
function stampcoll_get_participants($stampcollid) {
    global $CFG;
    $students = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}stampcoll_stamps s
                                 WHERE s.stampcollid = '$stampcollid' AND
                                       u.id = s.userid");
    return ($students);
}

/**
 * Get all users who can collect stamps in the given Stamp Collection
 *
 * Returns array of users with the capability mod/stampcoll:collectstamps. Caller may specify the group.
 * If groupmembersonly used, do not return users who are not in any group.
 *
 * @uses $CFG;
 * @param object $cm Course module record
 * @param object $context Current context
 * @param int $currentgroup ID of group the users must be in
 * @return array Array of users
 */
function stampcoll_get_users_can_collect($cm, $context, $currentgroup=false) {
    global $CFG;
    $users = get_users_by_capability($context, 'mod/stampcoll:collectstamps', 'u.id,u.picture,u.firstname,u.lastname',
                        '', '', '', $currentgroup, '', false, true);

    /// If groupmembersonly used, remove users who are not in any group
    /// XXX this has not been tested yet !!!
    if ($users && !empty($CFG->enablegroupings) && $cm->groupmembersonly) {
        if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id,u.picture,u.firstname,u.lastname' )) {
            $users = array_intersect($users, $groupingusers);
        }
    }
    return $users;
}

/**
 * Return full record of the stamp collection.
 *
 * @param int $stampcallid ID of an module instance
 * @return object Object containing instance data
 */
function stampcoll_get_stampcoll($stampcollid) {
    return get_record("stampcoll", "id", $stampcollid);
}

/**
 * Return all stamps in module instance.
 *
 * @param int $stamcallid ID of an module instance
 * @return array|false Array of found stamps (as objects) or false if no stamps or error occured
 */
function stampcoll_get_stamps($stampcollid) {
    return get_records("stampcoll_stamps", "stampcollid", $stampcollid, "id");
}

/**
 * Return one stamp.
 *
 * @param int $stamid ID of an stamp record
 * @return object|false Found stamp (as object) or false if not such stamp or error occured
 */
function stampcoll_get_stamp($stampid) {
    return get_record("stampcoll_stamps", "id", $stampid);
}


/**
 * Return HTML displaying the hoverable stamp image.
 *
 * @param int $stamp The stamp object
 * @param string $image The value of stampcollection image or absolute path to the file
 * @param bool $tooltip Show stamp details when mouse hover
 * @param bool $anonymous Hide the author of the stamp
 * @param string $imagaeurl Optional: use <img scr="$imageurl"> instead of $image
 * @return string HTML code displaying the image
 */
function stampcoll_stamp($stamp, $image='', $tooltip=true, $anonymous=false, $imageurl=null) {
    global $CFG, $COURSE;

    $image_location = $CFG->dataroot . '/'. $COURSE->id . '/'. $image;
    if (empty($image) || $image == 'default' || !file_exists($image_location)) {
        if ($imageurl) {
            $src = $imageurl;
        } else {
            $src = STAMPCOLL_IMAGE_URL;
        }
    } else {
        if ($CFG->slasharguments) {
            $src = $CFG->wwwroot . '/file.php/' . $COURSE->id . '/' . $image;
        } else {
            $src = $CFG->wwwroot . '/file.php?file=/' . $COURSE->id . '/' . $image;
        }
    }
    $alt = get_string('stampimage', 'stampcoll');
    $date = userdate($stamp->timemodified);
    if (!empty($stamp->giver) && $tooltip && !$anonymous) {
        $author = fullname(get_record('user', 'id', $stamp->giver, '', '', '', '', 'lastname,firstname')). '<br />';
        $author = get_string('givenby', 'stampcoll', $author);
    } else {
        $author = '';
    }
    if ($tooltip) {
        $recepient = fullname(get_record('user', 'id', $stamp->userid, '', '', '', '', 'lastname,firstname')). '<br />';
        $recepient = get_string('givento', 'stampcoll', $recepient);
    } else {
        $recepient = '';
    }
    $caption = $author . $recepient . $date;
    $comment = format_string($stamp->text);
    $tooltip_start = '<a class="stampimagewrapper" href="javascript:void(0);"
                         onmouseover="return overlib(\'' . $comment . '\', CAPTION, \'' . $caption . '\' );" 
                         onmouseout="nd();">';
    $tooltip_end = '</a>';
    $img = '<img class="stampimage" src="' . $src . '" alt="'. $alt .'" />';

    if ($tooltip) {
        return $tooltip_start . $img . $tooltip_end;
    } else {
        return $popup;
    }
}


/**
 * Returns installed module version
 *
 * @return int Version defined in the module's version.php
 */
function stampcoll_modversion() {
    require(dirname(__FILE__).'/version.php');
    return $module->version;
}


?>
