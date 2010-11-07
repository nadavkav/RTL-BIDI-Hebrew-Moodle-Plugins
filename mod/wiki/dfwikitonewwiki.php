<?php  // DFwiki2NewWiki created by Manel Carrasco Pacheco
/**
 * 
 * DEPRECADED ?????!!!!!
 * 
 */
    require_once("../../config.php");
    require_once("lib.php");
    require_once ('../../backup/lib.php');
    require_once ('../../backup/restorelib.php');
    require_once ('../../course/lib.php');
	//html functions
	require_once ($CFG->dirroot.'/mod/wiki/weblib.php');


	$id = optional_param('id',NULL,PARAM_INT);    // Course Module ID

    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (! $dfwiki = get_record("wiki", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

    require_login($course->id);

	$context = get_context_instance(CONTEXT_MODULE,$cm->id);
	require_capability('mod/wiki:adminactions',$context);


    /// Print the page header
    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    //Adjust some php variables to the execution of this script
    @ini_set("max_execution_time","3000");
    raise_memory_limit("memory_limit","128M");

    //get mod plural and singlar name
    $strwikis = get_string("modulenameplural", 'wiki');
    $strwiki  = get_string("modulename", 'wiki');

    print_header("$course->shortname: $dfwiki->name", "$course->fullname",
                 "$navigation <a href=\"index.php?id=$course->id\">$strwikis</a> -> $dfwiki->name",
                  "", "", true);

    //Check if either we're comming from the form or this is the first time
    if (optional_param('sure',NULL,PARAM_ALPHA)== get_string('yes')){

        //Form has already been visited
        if(wiki_convert_all_dfwikis($course)){
            wiki_br();
            print_simple_box_start();

            $prop = null;
            $prop->class = "textcenter";
          	wiki_size_text(get_string("convertdfwikicorrectly","wiki"), 2, $prop);
            print_continue("view.php?id=$cm->id");

            wiki_br();
            print_simple_box_end();
        } else{
            //there aren't dfwikis to convert
            wiki_div('&nbsp;');
            print_simple_box_start();

            $prop = null;
            $prop->class = "textcenter";
			wiki_paragraph(get_string('nodfwikistoconvert', 'wiki'), $prop);

			$prop = null;
			$prop->id = "form";
			$prop->method = "post";
			$prop->action = 'view.php?id='.$cm->id;
			wiki_form_start($prop);
			$prop = null;
            $prop->class = "textcenter";
			wiki_div_start($prop);
				$prop = null;
				$prop->name = "continue";
				$prop->value = get_string('continue');
				wiki_input_submit($prop);
			wiki_div_end();
			wiki_form_end();

			wiki_br();
            print_simple_box_end();
        }

    }else{

        // First time
		wiki_br();
		print_simple_box_start();
		$prop = null;
        $prop->class = "textcenter";
		wiki_paragraph(get_string("convertdfwikitonewwiki", "wiki"), $prop);

		$prop = null;
		$prop->class = "boxaligncenter";
		$prop->padding = "10";
		wiki_table_start($prop);

			$prop = null;
			$prop->id = "form1";
			$prop->method = "post";
			$prop->action = 'dfwikitonewwiki.php?id='.$cm->id;
			wiki_form_start($prop);
			wiki_div_start();
				$prop = null;
				$prop->name = "sure";
				$prop->value = get_string('yes');
				wiki_input_submit($prop);
			wiki_div_end();
			wiki_form_end();

			wiki_change_column();

			$prop = null;
			$prop->id = "form2";
			$prop->method = "post";
			$prop->action = 'view.php?id='.$cm->id;
			wiki_form_start($prop);
			wiki_div_start();
				$prop = null;
				$prop->name = "cancel";
				$prop->value = get_string('no');
				wiki_input_submit($prop);
			wiki_div_end();
			wiki_form_end();

		wiki_table_end();
		print_simple_box_end();
    }
    /// Finish the page
    print_footer($course);

    //This function converts all dfwikis in DB to new wikis
    function wiki_convert_all_dfwikis($course) {

        global $CFG;

        //get all dfwikis in the course
        if ($dfwikis = get_records_sql('SELECT *
                                        FROM '. $CFG->prefix.'dfwiki
                                        WHERE course='.$course->id)){

            //get every dfwiki separately
            foreach($dfwikis as $dfw){

                //get the new cm pointing to the new wiki and not to the dfwiki
                $newwiki = wiki_config_course_module($dfw);

                //with every dfwiki we get all dfwiki pages
                if($pages = get_records_sql('SELECT *
                                             FROM '. $CFG->prefix.'dfwiki_pages
                                             WHERE dfwiki=\''.$dfw->id.'\'')){


                    //get every dfwiki page
                    foreach($pages as $page){

                        //insert the new page into the new wiki
                        wiki_insert_page_from_dfwiki($page, $newwiki);

                        //delete the dfwiki page
                        $quer3 = 'DELETE FROM '. $CFG->prefix.'dfwiki_pages
                                  WHERE dfwiki=\''.$dfw->id.'\'';
                        execute_sql($quer3, false);

                    }
                }

                //delete dfwiki in DB
                $quer2 = 'DELETE FROM '. $CFG->prefix.'dfwiki
                          WHERE id=\''.$dfw->id.'\'';
                execute_sql($quer2, false);

            }

            @rebuild_course_cache();
            return true;
        } else{
            return false;
        }
    }

    //This function  gets the new cm pointing to the new wiki and not to the dfwiki
    function wiki_config_course_module($dfw){

        global $CFG;

        $newwiki->course = $dfw->course;
        $newwiki->name = $dfw->name;
        $newwiki->pagename = $dfw->pagename;
        $newwiki->timemodified = $dfw->timemodified;
        $newwiki->editable = $dfw->editable;
        $newwiki->attach = $dfw->attach;
        $newwiki->restore = $dfw->restore;
        $newwiki->editor = $dfw->editor;
        $newwiki->groupmode = $dfw->groupmode;
        $newwiki->teacherdiscussion = $dfw->teacherdiscussion;
        $newwiki->studentmode = $dfw->studentmode;
        $newwiki->editanothergroup = $dfw->editanothergroup;
        $newwiki->editanotherstudent = $dfw->editanotherstudent;
        //insert here for new added dfwiki table fields


        //look for the old dfwiki cm->id
        $modul = get_record("modules", "name", 'dfwiki');
        $coursemodule = get_record_sql('SELECT *
                    FROM '. $CFG->prefix.'course_modules
                    WHERE module='.$modul->id.' AND instance='.$dfw->id);

        $newwikiid = insert_record("wiki", $newwiki);

        backup_flush(300);

        //modify the course_modules entry which was pointing to the old dfwiki so as to point to the new wiki
        $modulwiki = get_record("modules", "name", 'wiki');
        $quer = 'UPDATE '. $CFG->prefix.'course_modules
                SET module=\''.$modulwiki->id.'\'
                WHERE id=\''.$coursemodule->id.'\'';
        execute_sql($quer, false);
        $quer2 = 'UPDATE '. $CFG->prefix.'course_modules
                SET instance=\''.$newwikiid.'\'
                WHERE id=\''.$coursemodule->id.'\'';
        execute_sql($quer2, false);

        return $newwikiid;

    }

    //Function which inserts a page into the new wiki
    function wiki_insert_page_from_dfwiki($dfwikipage, $newwiki){

		$page->pagename = restore_decode_absolute_links(addslashes($dfwikipage->pagename));
        $page->version = $dfwikipage->version;
        $page->content = $dfwikipage->content;
        $page->author = $dfwikipage->author;
        //get the user id from the author username
        $user = get_record('user', 'username', addslashes($dfwikipage->author));
        $page->userid = $user->id;

        $page->created = $dfwikipage->created;
        $page->lastmodified = $dfwikipage->lastmodified;
        $page->refs = $dfwikipage->refs;
        $page->hits = $dfwikipage->hits;
        $page->editable = $dfwikipage->editable;
        $page->dfwiki = $newwiki;
        $page->editor = $dfwikipage->editor;
        $page->groupid = $dfwikipage->groupid;
        $page->ownerid = $dfwikipage->ownerid;
        $page->highlight = $dfwikipage->destacar;
        $page->votes = $dfwikipage->votes;
        //insert here new added dfwiki_page table fields


        if(!insert_record ('wiki_pages',addslashes($page))){
             error ('Can\'t insert page record');
        }
    }

?>
