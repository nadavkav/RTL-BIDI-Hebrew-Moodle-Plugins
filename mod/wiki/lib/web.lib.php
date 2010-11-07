<?php
/// Original DFwiki created by David Castro, Ferran Recio and Marc Alier.
/// Library of functions and constants for module wiki

/**
 * define a static variable for page_blocks (just for interface auxiliar use)
 * @param String $info='PAGE': info identifier
 * @param mixed $value=false: definex values (for assignment)
 * @return page blocks object or null
 */
function wiki_page_info ($info='PAGE',$pageblocks=false) {
	static $pgblocks = array();
	if ($pageblocks) $pgblocks[$info] = $pageblocks;
	if (isset($pgblocks[$info])) return $pgblocks[$info];
	return null;
}

/**
 * Module Blocked Format for Moodle
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: web.lib.php,v 1.5 2008/05/05 09:10:43 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Activity_Format
 */
function wiki_header(){
	global $COURSE,$CFG,$WS;
    //define block sizes
    define('BLOCK_L_MIN_WIDTH', 100);
    define('BLOCK_L_MAX_WIDTH', 210);
    define('BLOCK_R_MIN_WIDTH', 100);
    define('BLOCK_R_MAX_WIDTH', 210);

    $edit = optional_param('edit',NULL,PARAM_ALPHA);

    //This structure is used to use blocks
	//Standard blocks:
	$dfwiki = wiki_param ('dfwiki');

	//$PAGE = page_create_instance($dfwiki->id);
	$PAGE =page_create_object('mod-wiki-view', $dfwiki->id);

	//little patch: if body class is a subtype od mod-wiki, class is mod-wiki
	if (substr($PAGE->body_class,0,8) == 'mod-wiki') {
		$PAGE->body_class = 'mod-wiki';
	}
	wiki_page_info ('PAGE',$PAGE);
	//Needed function to make blocks work properly
	$pageblocks = blocks_setup($PAGE);
	wiki_page_info ('pageblocks',$pageblocks);
	//Function that checks the edit permissions of the wiki
	wiki_check_edition($PAGE, $edit);
	//setup the module
	/// Print the page header
	if ($COURSE->category) {
	    $navigation = "<a href=\"../../course/view.php?id=$COURSE->id\">$COURSE->shortname</a> ->";
	}
	//get names in both singular and plural
	$strdfwikis = get_string("modulenameplural", 'wiki');
	$strdfwiki  = get_string("modulename", 'wiki');
	   $PAGE->print_header($COURSE->shortname.': %fullname%');
	/// Print the main part of the page
	$prop = new stdClass;
	$prop->class = "course-content";
	wiki_div_start($prop); // course wrapper start


    //to work out the default widths need to check all blocks
    $preferred_width_left = optional_param('preferred_width_left',  blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]),PARAM_INT);
    //preferred_width_left sizes
    //should be between BLOCK_x_MAX_WIDTH and BLOCK_x_MIN_WIDTH.
    $preferred_width_left = min($preferred_width_left, BLOCK_L_MAX_WIDTH);
    $preferred_width_left = max($preferred_width_left, BLOCK_L_MIN_WIDTH);


 	$cm = wiki_param('cm');

	$context = get_context_instance(CONTEXT_MODULE,$cm->id);
    //shows a specific topic
    if (has_capability('mod/wiki:editawiki',$context) and isset($marker) and confirm_sesskey()) {
        $COURSE->marker = $marker;
        if (! set_field("course", "marker", $marker, "id", $COURSE->id)) {
            error("Could not mark that topic for this course");
        }
    }

    //load strings
    $streditsummary   = get_string('editsummary');
    $stradd           = get_string('add');
    $stractivities    = get_string('activities');
    $strshowalltopics = get_string('showalltopics');
    $strtopic         = get_string('topic');
    $strgroups        = get_string('groups');
    $strgroupmy       = get_string('groupmy');
    //checks if the user is editing
    $editing          = $PAGE->user_is_editing();
    //load editing strings
    if ($editing) {
        $strstudents = moodle_strtolower($COURSE->students);
        $strtopichide = get_string('topichide', '', $strstudents);
        $strtopicshow = get_string('topicshow', '', $strstudents);
        $strmarkthistopic = get_string('markthistopic');
        $strmarkedthistopic = get_string('markedthistopic');
        $strmoveup = get_string('moveup');
        $strmovedown = get_string('movedown');
    }

//--------------------------------------------- INTERFACES BEGINS HERE

/// Layout the whole page as three big columns.

/// The left column ...

    //checks if there are blocks to place on the left-hand side

    if(!empty($CFG->showblocksonmodpages) && (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing )) {

        $prop = new stdClass;
        $prop->id = "layout-table";
        $prop->idtd = "left-column";
    	$prop->classtd = "blockcourse";
        wiki_table_start($prop);

	    $prop = new stdClass;
    	$prop->width = $preferred_width_left.'px';
    	wiki_table_start($prop);

    		blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);

    	wiki_table_end();

        $prop = new stdClass;
        $prop->id = "middle-column";
        wiki_change_column($prop);
    } else {
    	$prop = new stdClass;
        $prop->id = "layout-table";
        $prop->idtd = "middle-column";
        wiki_table_start($prop);
    }

/// Start main column

    //central block title
    //print_heading_block(get_string('wiki','wiki'), 'outline');
	echo "<br />";
	print_box($dfwiki->intro, "generalbox", "intro");

   	//begin the content table
	$prop = new stdClass;
	$prop->class = "topics";
   	$prop->width = "100%";
	wiki_table_start($prop);

	//print_tabs
	//TODO: clean it when all functionalities are parts
		global $CFG;
	//this function is the responsable of printing the content of the module.
    $dfcontent = wiki_param ('dfcontent');
    $dfcontentf= wiki_param ('dfcontentf');
    if(isset($dfcontent) && $dfcontent>=0 && $dfcontent < count($dfcontentf)){
        //we won't print tabs cause is an special situation (this comprobation will be removed when all ead are passed to parts)
    } else {
        wiki_print_tabs();
    }
}

function wiki_footer () {
	global $COURSE,$CFG;
///document	ending
	$PAGE = wiki_page_info ('PAGE');
	$pageblocks = wiki_page_info ('pageblocks');
	$editing = $PAGE->user_is_editing();
	wiki_table_end();
/// The right column
	//if there are not blocks on the right part then don't enter in this condition
    if(!empty($pageblocks[BLOCK_POS_RIGHT])){
        //to work out the default widths need to check all blocks
        $preferred_width_right = optional_param('preferred_width_right', blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]),PARAM_INT);

        //preferred_width_right sizes
        //should be between BLOCK_x_MAX_WIDTH and BLOCK_x_MIN_WIDTH.
        $preferred_width_right = min($preferred_width_right, BLOCK_R_MAX_WIDTH);
        $preferred_width_right = max($preferred_width_right, BLOCK_R_MIN_WIDTH);
    }

    //if there are blocks on the right part, then they are placed
    if(!empty($pageblocks[BLOCK_POS_RIGHT])){

        //checks if there are blocks to place on the right-hand side
        if(!empty($CFG->showblocksonmodpages) && (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing )) {
		 	$prop = new stdClass;
	    	$prop->id = "right-column";
	    	$prop->class = "blockcourse";
	    	wiki_change_column($prop);

	    		$prop = new stdClass;
		    	$prop->width = $preferred_width_right.'px';
		    	wiki_table_start($prop);

		    		blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);

		    	wiki_table_end();
		    	$prop = new stdClass;
        }
    }
    wiki_table_end();
    // select the teacher
    $cm = wiki_param('cm');
    $dfwiki = wiki_param ('dfwiki');
    wiki_print_teacher_selection($cm, $dfwiki);
    wiki_div_end(); // content wrapper end
/// Finish the page
    print_footer($COURSE);
}

/**
* This function checks if the user has permissions to edit the
* wiki blocks.
*
* @param $PAGE
* @param string $edit the permission is set 'on' or 'off'
*/
function wiki_check_edition($PAGE, $edit){
	global $USER, $COURSE;
    //Set the editing stuff
    if (!isset($USER->editing)) {
        $USER->editing = false;
    }
    //Check whether  editing is allowed
    if ($PAGE->user_allowed_editing()) {
        //edit needs to be set on so as to activate editing option (given by POST)
        if ($edit == 'on') {
            //set it true
            $USER->editing = true;
        } else if ($edit == 'off') {
            //set it false
            $USER->editing = false;
            if(!empty($USER->activitycopy) && $USER->activitycopycourse == $COURSE->id) {
                $USER->activitycopy       = false;
                $USER->activitycopycourse = NULL;
            }
        }
    } else {
        $USER->editing = false;
    }
}


?>