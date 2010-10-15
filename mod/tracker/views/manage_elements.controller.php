<?php

/**
* @package mod-tracker
* @category mod
* @author Valery Fremaux > 1.8
* @date 02/12/2007
*
* Controller for all "element management" related views
*
* @usecase createelement
* @usecase doaddelement
* @usecase editelement
* @usecase doupdateelement
* @usecase deleteelement
* @usecase submitelementoption
* @usecase viewelementoptions
* @usecase deleteelementoption
* @usecase editelementoption
* @usecase updateelementoption
* @usecase moveelementoptionup
* @usecase moveelementoptiondown
* @usecase addelement
* @usecase removeelement
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

/************************************* Create element form *****************************/
if ($action == 'createelement'){
	$form->type = required_param('type', PARAM_ALPHA);
	// $elementid = optional_param('elementid', null, PARAM_INT);
	
    $form->action = 'doaddelement';
	include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editelement.html';
	return -1;
}
/************************************* add an element *****************************/
elseif ($action == 'doaddelement'){
	$form->name = required_param('name', PARAM_ALPHANUM);
	$form->description = required_param('description', PARAM_CLEANHTML);
	$form->type = required_param('type', PARAM_ALPHA);
	$form->shared = optional_param('shared', 0, PARAM_INT);
	
	$errors = array();
	if (empty($form->name)){
	    $error->message = get_string('namecannotbeblank', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

    if(!count($errors)){
    	$element->name = $form->name;
    	$element->description = addslashes($form->description);
    	$form->type = $element->type = $form->type;
    	$element->course = ($form->shared) ? 0 : $COURSE->id;
    			
    	if (!$form->elementid = insert_record('tracker_element', $element, true)){
    		error ("Could not create element");
    	}

        $elementobj = tracker_getelement(null, $form->type);
        if ($elementobj->hasoptions()){  // Bounces to the option editor
            $form->name = '';
            $form->description = '';
            $action = 'viewelementoptions';
    	}
	}
	else{
        $form->name = '';
        $form->description = '';
		include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editelement.html';
    }
}
/************************************* Edit an element form *****************************/
elseif ($action == 'editelement'){
	$form->elementid = required_param('elementid', PARAM_INT);
	
	if ($form->elementid != null){
	    $element = tracker_getelement($form->elementid);
		$form->type = $element->type;
		$form->name = $element->name;
		$form->description = addslashes($element->description);
		$form->format = $element->format;
		$form->shared = ($element->course == 0) ;
		$form->action = 'doupdateelement';
		include $CFG->dirroot.'/mod/classes/trackercategorytype/editelement.html';
	} else {
		error ("Invalid element.  Cannot edit element id:" . $elementid);
	}
	return -1;
}
/************************************* Update an element *****************************/
if ($action == 'doupdateelement'){
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->name = required_param('name', PARAM_ALPHANUM);
	$form->description = required_param('description', PARAM_CLEANHTML);
	$form->format = optional_param('format', '', PARAM_INT);
	$form->type = required_param('type', PARAM_ALPHA);
	$form->shared = optional_param('shared', 0, PARAM_INT);

	if (empty($form->elementid)){
		error ("Error. Element does not exist");
	}

	$errors = array();
	if (empty($form->name)){
	    $error->message = get_string('namecannotbeblank', 'tracker');
	    $error->on = "name";
	    $errors[] = $error;
	}

    if(!count($errors)){
    	$element->id = $form->elementid;
    	$element->name = $form->name;
    	$element->type = $form->type;
    	$element->description = addslashes($form->description);
    	$element->format = $form->format;
    	$element->course = ($form->shared) ? 0 : $COURSE->id ;
    				
    	if (!update_record('tracker_element', $element)){
    		error ("Could not update element");
    	}
    } else {
    	$form->action = 'doupdateelement';
		include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editelement.html';
    }
}
/************************************ delete an element from available **********************/
if ($action == 'deleteelement'){
	$elementid = required_param('elementid', PARAM_INT);
	
	if(!tracker_iselementused($tracker->id, $elementid)){ 
    	if (!delete_records ('tracker_element', 'id', $elementid)){	
    		error ("Cannot delete element from list of available elements");
    	}
    	delete_records('tracker_elementitem', 'elementid', $elementid);
    } else { // should not even be proposed by the GUI
       error("Cannot delete because used by at least one active tracker");
    }
}	
/************************************* add an element option *****************************/
if ($action == 'submitelementoption'){
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->name = required_param('name', PARAM_ALPHANUM);
	$form->description = required_param('description', PARAM_CLEANHTML);
	$form->type = required_param('type', PARAM_ALPHA);
	
	$element = get_record('tracker_element', 'id', $form->elementid);
	
	// check validity
	$errors = array();
	if (count_records('tracker_elementitem', 'elementid', $form->elementid, 'name', $form->name)){
	    $error->message = get_string('optionisused', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

	if ($form->name == ''){
	    unset($error);
	    $error->message = get_string('optionnamecannotbeblank', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

	if ($form->description == ''){
	    unset($error);
	    $error->message = get_string('descriptionisempty', 'tracker');
	    $error->on = 'description';
	    $errors[] = $error;
	}
	
	if (!count($errors)){
    	$option->name = strtolower($form->name);
    	$option->description = addslashes($form->description);
    	$option->elementid = $form->elementid;
    
        $countoptions = 0 + count_records('tracker_elementitem', 'elementid', $form->elementid);
    	$option->sortorder = $countoptions + 1;
    
    	if (!insert_record('tracker_elementitem', $option, true)){
    		error ("Could not create element option");
    	}
    	
    	$form->name = '';
    	$form->description = '';
    } else {
        /// print errors
        $errorstr = '';
        foreach($errors as $anError){
            $errorstrs[] = $anError->message;
        }
        print_simple_box(implode('<br/>', $errorstrs), 'center', '70%', '', 5, 'errorbox');
    }
    print_heading(get_string('editoptions', 'tracker'));
    $element = tracker_getelement($form->elementid);
    $element->optionlistview($cm);
    
    $caption = get_string('addanoption', 'tracker');
    print_heading($caption . helpbutton('options', get_string('description'), 'tracker', true, false, false, true));
    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'viewelementoptions'){
	$form->elementid = optional_param('elementid', @$form->elementid, PARAM_INT);
	
	if ($form->elementid != null){
		$element = tracker_getelement($form->elementid);
		$form->type = $element->type;
		
        print_heading(get_string('editoptions', 'tracker'));
        $element = tracker_getelement($form->elementid);
        $element->optionlistview($cm);
        
        print_heading(get_string('addanoption', 'tracker'));
        include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
	} else {
		error ("Cannot view element options for elementid:" . $form->elementid);
	}
	return -1;
}
/************************************* delete an element option *****************************/
if ($action == 'deleteelementoption'){
	$form->elementid = optional_param('elementid', null, PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);
	
	$element = tracker_getelement($form->elementid);
	$deletedoption = $element->getoption($form->optionid);
	$form->type = $element->type;

	if (get_records('tracker_issueattribute', 'elementitemid', $form->optionid)){
		error ('Cannot delete the element option:"' . $element->options[$form->optionid]->name . '" (id:' . $form->optionid . ') because it is currently being used as a attribute for an issue', "view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid=" . $form->elementid);
	}
	
	if (!delete_records('tracker_elementitem', 'id', $form->optionid)){
		error ("Error trying to delete element option id:" . $form->optionid, "view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid=" . $form->elementid);
	}				
	
	/// renumber higher records
	$sql = "
	    UPDATE
	        {$CFG->prefix}tracker_elementitem
	    SET
	        sortorder = sortorder - 1
	    WHERE
	        elementid = $form->elementid AND
	        sortorder > $deletedoption->sortorder;
	";
	execute_sql($sql, false);
	
    print_heading(get_string('editoptions', 'tracker'));
    $element = tracker_getelement($form->elementid);
    $element->optionlistview($cm);
    
    $caption = get_string('addanoption', 'tracker');
    print_heading($caption . helpbutton('options', get_string('description'), 'tracker', true, false, false, true));
    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'editelementoption'){
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);
	
	$element = tracker_getelement($form->elementid);
	$option = $element->getoption($form->optionid);
	$form->type = $element->type;
	$form->name = $option->name;
	$form->description = $option->description;
	include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/updateoptionform.html';
	return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'updateelementoption'){
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);
	$form->name = required_param('name', PARAM_ALPHANUM);
	$form->description = required_param('description', PARAM_CLEANHTML);
	$form->format = optional_param('format', 0, PARAM_INT);

	$element = tracker_getelement($form->elementid);
	$form->type = $element->type;
	
	// check validity
	$errors = array();
	if (count_records_select('tracker_elementitem', "elementid = $form->elementid AND name = '$form->name' AND id != $form->optionid ")){
	    $error->message = get_string('optionisused', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

	if ($form->name == ''){
	    unset($error);
	    $error->message = get_string('optionnamecannotbeblank', 'tracker');
	    $error->on = 'name';
	    $errors[] = $error;
	}

	if ($form->description == ''){
	    unset($error);
	    $error->message = get_string('descriptionisempty', 'tracker');
	    $error->on = 'description';
	    $errors[] = $error;
	}

    if (!count($errors)){
    	$update->id = $form->optionid;
    	$update->name = $form->name;
    	$update->description = addslashes($form->description);
    	$update->format = $form->format;
    
    	if (update_record('tracker_elementitem', $update)){
            print_heading(get_string('editoptions', 'tracker'));
            $element = tracker_getelement($form->elementid);
            $element->optionlistview($cm);
            
            print_heading(get_string('addanoption', 'tracker'));
	        include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    	} else {
    		error ('Cannot update the element option:"' . $element->options[$form->optionid]->name . '" (id:' . $form->optionid . ') because it is currently being used as a attribute for an issue', 'view.php?id={$cm->id}&amp;what=viewelementoptions&amp;elementid=' . $form->elementid);
    	}
    } else {
        /// print errors
        $errorstr = '';
        foreach($errors as $anError){
            $errorstrs[] = $anError->message;
        }
        print_simple_box(implode("<br/>", $errorstrs), 'center', '70%', '', 5, 'errorbox');
	    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/updateoptionform.html';
    }
	return -1;
}
/********************************** move an option up in list ***************************/
if ($action == 'moveelementoptionup'){
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);

    $option = get_record('tracker_elementitem', 'elementid', $form->elementid, 'id', $form->optionid);
    $element = tracker_getelement($form->elementid);
	$form->type = $element->type;
	
	$option->id = $form->optionid;
	$sortorder = get_field('tracker_elementitem', 'sortorder', 'elementid', $form->elementid, 'id', $form->optionid);
	if ($sortorder > 1){
	    $option->sortorder = $sortorder - 1;
	    $previousoption->id = get_field('tracker_elementitem', 'id', 'elementid', $form->elementid, 'sortorder', $sortorder - 1);
	    $previousoption->sortorder = $sortorder;
			
	    // swap options in database
    	if (!update_record('tracker_elementitem', $option)){
    		error ("Could not update element");
    	}
    	if (!update_record('tracker_elementitem', $previousoption)){
    		error ("Could not update element");
    	}
	}	
    print_heading(get_string('editoptions', 'tracker'));
    $element = tracker_getelement($form->elementid);
    $element->optionlistview($cm);
    
    $caption = get_string('addanoption', 'tracker');
    print_heading($caption . helpbutton('options', get_string('description'), 'tracker', true, false, false, true));
    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    return -1;
}
/********************************** move an option down in list ***************************/
if ($action == 'moveelementoptiondown'){
	$form->elementid = required_param('elementid', PARAM_INT);
	$form->optionid = required_param('optionid', PARAM_INT);

    $option = get_record('tracker_elementitem', 'elementid', $form->elementid, 'id', $form->optionid);
    $element = tracker_getelement($form->elementid);
	$form->type = $element->type;
	
	$option->id = $form->optionid;
	$sortorder = get_field('tracker_elementitem', 'sortorder', 'elementid', $form->elementid, 'id', $form->optionid);
	if ($sortorder < $element->maxorder){
	    $option->sortorder = $sortorder + 1;
	    $nextoption->id = get_field('tracker_elementitem', 'id', 'elementid', $form->elementid, 'sortorder', $sortorder + 1);
	    $nextoption->sortorder = $sortorder;
			
	    // swap options in database
    	if (!update_record('tracker_elementitem', $option)){
    		error ("Could not update element");
    	}
    	if (!update_record('tracker_elementitem', $nextoption)){
    		error ("Could not update element");
    	}
    }
    print_heading(get_string('editoptions', 'tracker'));
    $element = tracker_getelement($form->elementid);
    $element->optionlistview($cm);
    
    $caption = get_string('addanoption', 'tracker');
    print_heading($caption . helpbutton('options', get_string('description'), 'tracker', true, false, false, true));
    include $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/editoptionform.html';
    return -1;
}
/********************************** add an element to be used ***************************/
if ($action == 'addelement'){
	$elementid = required_param('elementid', PARAM_INT);

	if(!tracker_iselementused($tracker->id, $elementid)){
		/// Add element to element used table;
		$used->elementid = $elementid;
		$used->trackerid = $tracker->id;
		$used->canbemodifiedby = $USER->id;
		
		/// get last sort order
		$sortorder = 0 + get_field_select('tracker_elementused', 'MAX(sortorder)', "trackerid = {$tracker->id} GROUP BY trackerid");
		$used->sortorder = $sortorder + 1;
		
		if (!insert_record ('tracker_elementused', $used)){	
			error ("Cannot add element to list of elements to use for this tracker");
		}		
	} else {
		//Feedback message that element is already in use
		error ('Element already in use', "view.php?id={$cm->id}&amp;what=manageelements");
	}		
}	
/****************************** remove an element from usable list **********************/
if ($action == 'removeelement'){
	$usedid = required_param('usedid', PARAM_INT);
	
	if (!delete_records ('tracker_elementused', 'elementid', $usedid, 'trackerid', $tracker->id)){	
		error ("Cannot delete element from list of elements to use for this tracker");
	}
}	
?>