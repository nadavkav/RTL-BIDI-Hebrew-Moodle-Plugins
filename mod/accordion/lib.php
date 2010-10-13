<?php  // $Id: lib.php,v 1.12 2008/10/15 09:33:07  $

/// Library of functions and constants for module accord


define("ACCORDION_MAX_NAME_LENGTH", 50);

function accordion_add_instance($accordion) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.
    $textlib = textlib_get_instance();

    $accordion->name = addslashes(strip_tags(format_string(stripslashes($accordion->content),true)));
    if ($textlib->strlen($accordion->name) > ACCORDION_MAX_NAME_LENGTH) {
        $accordion->name = $textlib->substr($accordion->name, 0, ACCORDION_MAX_NAME_LENGTH)."...";
    }
    $accordion->timemodified = time();

    return insert_record("accordion", $accordion);
}


function accordion_update_instance($accordion) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.
    $textlib = textlib_get_instance();

    $accordion->name = addslashes(strip_tags(format_string(stripslashes($accordion->content),true)));
    $accordion->title = addslashes(strip_tags(format_string(stripslashes($accordion->title),true)));
    if ($textlib->strlen($accordion->name) > ACCORDION_MAX_NAME_LENGTH) {
        $accordion->name = $textlib->substr($accordion->name, 0, ACCORDION_MAX_NAME_LENGTH)."...";
    }
    $accordion->timemodified = time();
    $accordion->id = $accordion->instance;

    return update_record("accordion", $accordion);
}


function accordion_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

    if (! $accordion = get_record("accordion", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("accordion", "id", "$accordion->id")) {
        $result = false;
    }

    return $result;
}

function accordion_get_participants($accordid) {
//Returns the users with data in one resource
//(NONE, but must exist on EVERY mod !!)

    return false;
}

function accordion_get_coursemodule_info($coursemodule) {
/// Given a course_module object, this function returns any 
/// "extra" information that may be needed when printing
/// this activity in a course listing.
///
/// See get_array_of_activities() in course/lib.php

   $info = NULL;

   if ($accordion = get_record("accordion", "id", $coursemodule->instance)) {
       $info->extra = urlencode($accordion->title.'/---/'.$accordion->content);
   }

   return $info;
}

function accordion_get_view_actions() {
    return array();
}

function accordion_get_post_actions() {
    return array();
}

function accordion_get_types() {
    $types = array();

    $type = new object();
    $type->modclass = MOD_CLASS_RESOURCE;
    $type->type = "accordion";
    $type->typestr = get_string('resourcetypeaccordion', 'resource');
    $types[] = $type;

    return $types;
}
?>
