<?php 

/**
* Module Brainstorm V2
* Operator : categorize
* @author Valery Fremaux
* @package Brainstorm 
* @date 20/12/2007
*/

/********************************** Call the new category form ********************************/
if ($action == 'add'){
   $form->id = $cm->id;
   $form->categoryid = 0;
   include "$CFG->dirroot/mod/brainstorm/operators/categorize/edit.html";
   return -1;
}
/********************************** Call the update form ********************************/
if ($action == 'update'){
   $form->id = $cm->id;
   $form->categoryid = required_param('categoryid', PARAM_INT);
   include "$CFG->dirroot/mod/brainstorm/operators/categorize/edit.html";
   return -1;
}
/********************************** Stores data for a category ********************************/
if ($action == 'doadd'){
    $form->title = required_param('title', PARAM_CLEANHTML);
    $category->brainstormid = $brainstorm->id;
    $category->userid = $USER->id;
    $category->groupid = $currentgroup;
    $category->title = addslashes($form->title);
    $category->timemodified = time();
    if (!insert_record('brainstorm_categories', $category)){
        error("Could not add record");
    }
}
/********************************** Stores new data for a category ********************************/
if ($action == 'doupdate'){
    $form->categoryid = required_param('categoryid', PARAM_INT);
    $form->title = required_param('title', PARAM_CLEANHTML);
    $category->id = $form->categoryid;
    $category->userid = $USER->id;
    $category->groupid = $currentgroup;
    $category->title = addslashes($form->title);
    $category->timemodified = time();
    if (!update_record('brainstorm_categories', $category)){
        error("Could not update record");
    }
}
/********************************** Delete a category ********************************/
if ($action == 'delete'){
    $form->categoryid = required_param('categoryid', PARAM_INT);
    if (!delete_records('brainstorm_categories', 'id', $form->categoryid)){
        error("Could not delete record");
    }
}
/********************************** Save operator config ********************************/
if ($action == 'saveconfig'){
    $operator = required_param('operator', PARAM_ALPHA);
    brainstorm_save_operatorconfig($brainstorm->id, $operator);
}
?>