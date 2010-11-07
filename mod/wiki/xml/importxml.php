<?php
//Created by Antonio CastaÃ¯Â¿Â½o & Juan CastaÃ¯Â¿Â½o

    require_once("../../../config.php");
    require_once("../lib.php");

    global $WS;

	$id	= optional_param('id',NULL,PARAM_INT);    // Course Module ID

    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (! $dfwiki = get_record('wiki', "id", $cm->instance)) {
        error("Course module is incorrect");
    }

    require_login($course->id);

	$context = get_context_instance(CONTEXT_MODULE,$cm->id);
	require_capability('mod/wiki:adminactions',$context);

	$cancel = optional_param('dfformcancel',NULL,PARAM_ALPHA);
    if(isset($WS->dfform)){
		if (isset($cancel)){
			redirect('../view.php?id='.$cm->id);
		}
    }
    if ($course->category) {
        $navigation = "<a href=\"../../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    //take the module names in singular and plural
    $strdfwikis = get_string("modulenameplural", 'wiki');
    $strdfwiki  = get_string("modulename", 'wiki');

    print_header("$course->shortname: $dfwiki->name", "$course->fullname",
                 "$navigation <a href=\"../index.php?id=$course->id\">$strdfwikis</a> -> $dfwiki->name");

    print_heading(get_string("importing",'wiki'));

	$prop = null;
	$prop->class = 'box generalbox generalboxcontent boxaligncenter textcenter';
    wiki_div_start($prop);

    	$prop = null;
	    $prop->action = 'import.php?id='.$course->id.'&amp;cm='.$cm->id.'&amp;wdir=/';
		$prop->method = 'post';
		$prop->id = 'form1';
		wiki_form_start($prop);

			$prop = null;
			$prop->name = 'dfform[importfromxml]';
			$prop->value = get_string('importfromxml','wiki');
			$input = wiki_input_submit($prop, true);

			wiki_div($input);

		wiki_form_end();

		wiki_br();

	    $prop = null;
	    $prop->action = 'importwiki.php?id='.$course->id.'&amp;cm='.$cm->id.'&amp;wdir=/';
		$prop->method = 'post';
		$prop->id = 'form2';
		wiki_form_start($prop);

			$prop = null;
			$prop->name = 'dfform[importfrombackup]';
			$prop->value = get_string('importfrombackup','wiki');
			$input = wiki_input_submit($prop, true);

			wiki_div($input);

		wiki_form_end();

	wiki_div_end();

    //Print footer
    print_footer();

?>
