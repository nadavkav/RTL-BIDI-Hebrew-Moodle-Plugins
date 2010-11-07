<?php

/**
 * execute configuration functions
 */
function wiki_config () {
	$wikiManager = wiki_manager_get_instance ('moodle');
	wiki_param (null,null,'set_info');
	$course = wiki_param('course');
	require_login($course->id);
	wiki_setup_content();
	//execute setup callback functions
	wiki_execute_callbacks ('dfsetup');
	/*$callbacks = wiki_get_callbacks ('dfsetup');
	print_object ($callbacks);
	foreach ($callbacks as $callback) {
		if (function_exists($callback)) $callback ();
	}*/
}

/**
 * internal setup function. It just bring the possibility to callback functions from get param.
 */
function wiki_setup_content(){
	//this function contains all the instructions to configurate the module
	global $dfformaddtitle,$dfformoldcontent,$CFG;

	$dfform = wiki_param ('dfform');
    //this is a little tricky for the editor: javascript can't refer
    //dfform[content] but it can refer dfformcontent.
    if ($dfformcontent = wiki_param('dfformcontent')){
        $dfform['content'] = $dfformcontent;
    }

    if (isset($dfformaddtitle)) {
            $dfform['addtitle'] = $dfformaddtitle;
    }
    if (isset($dfformoldcontent)) {
            $dfform['oldcontent'] = $dfformoldcontent;
    }
    wiki_param ('dfform',$dfform);

	$dfsetup = wiki_param ('dfsetup');
	$dfsetupf = wiki_param ('dfsetupf');
    if(is_numeric($dfsetup) && isset($dfsetupf[$dfsetup])){
        $main_function = $dfsetupf[$dfsetup];
    } else {
        $main_function = 'wiki_main_setup';
    }
    if (!function_exists($main_function)) {
    	require_once ($CFG->dirroot.'/blocks/wiki_ead/lib.php');
    	require_once ($CFG->dirroot.'/blocks/wiki_search/lib.php');
    }
    $main_function();
}

?>