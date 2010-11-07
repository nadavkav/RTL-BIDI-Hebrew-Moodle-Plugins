<?php // $Id: view.php,v 1.17 2008/01/10 10:58:56 pigui Exp $

/// This page prints a particular instance of wiki
/// (Replace wiki with the name of your module)

    //this variable determine if we need all dfwiki libraries.
    $full_wiki = true;
	
	//Requieres that contains necessary classes and functions.
    require_once("../../config.php");
    require_once("lib.php");
    
    require_once($CFG->libdir.'/blocklib.php');
    require_once('pagelib.php');
	//require_once('class/wikistorage.class.php');
	//html functions
	require_once ($CFG->dirroot.'/mod/wiki/weblib.php');

    //Recover necessary variables. Most of them might not be needed at the same time
	$a = optional_param('a',NULL,PARAM_INT);
	wiki_param ('a',$a);
	$course = optional_param('course',NULL,PARAM_INT);
	$contents = optional_param('contents',NULL,PARAM_FILE);
	$editor = optional_param('editor',NULL,PARAM_ALPHA);
	$id = optional_param('id',NULL,PARAM_INT);
	wiki_param ('id',$id);

    //block vars
    $edit        = optional_param('edit',NULL,PARAM_ALPHA);
    $idnumber    = optional_param('idnumber',NULL,PARAM_INT);

	//create a new manager
	///$wikiManager = wiki_manager_get_instance ('moodle');
	wiki_config ();

    //WS contains all global variables
	//$WS = new storage();
	//Function to load al necessary data needed in WS
	//$WS->recover_variables();

    /*if ($id) {
        $cm = wiki_param ('cm',get_coursemodule_from_id('wiki',$id));
        if (!$cm) {
            error("Course Module ID was incorrect");
        }
        if (! $course = get_record("course", "id", $WS->cm->course)) {
            error("Course is misconfigured");
        }
    } else {
        $dfwiki = wiki_param ('dfwiki',get_record('wiki', "id", $a));
        if (!$dfwiki) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $WS->dfwiki->course)) {
            error("Course is misconfigured");
		}
    }*/

	//$WS->set_info($id);
	///wiki_param (null,null,'set_info');
	/*$cm = wiki_param('cm');
	if (!$course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured");
	}*/
	
	$course = wiki_param ('course');
	///require_login($course->id);
    
    ///wiki_setup_content();
	
    //The format begins here
    wiki_header();  // Include the actual course format
    
	wiki_print_content($WS);

	wiki_footer();

?>