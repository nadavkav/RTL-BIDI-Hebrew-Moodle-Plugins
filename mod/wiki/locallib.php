<?php
// $Id: locallib.php,v 1.134 2009/01/08 11:31:32 kenneth_riba Exp $
/// Library of functions for module dfwiki
/// Authors: Ludo (Marc Alier ), David Castro & Ferran Recio

//sintax contains the wikiparser
require_once ($CFG->dirroot.'/mod/wiki/wiki/sintax.php');
//nwikiparser.php contains the nwiki parser
require_once ($CFG->dirroot.'/mod/wiki/wiki/nwikiparser.php');
//hist contains all historical functionalities
require_once ($CFG->dirroot.'/mod/wiki/wiki/hist.php');
//dblib contains the calls to the database
require_once ($CFG->dirroot.'/mod/wiki/db/dblib.php');
//dfwiki editor functions
require_once ('editor/editor.php');
//uploaded files functions
require_once ('upload/uploadlib.php');
//messaging functions
require_once ($CFG->dirroot.'/message/lib.php');
//html functions
require_once ($CFG->dirroot.'/mod/wiki/weblib.php');

require_once ($CFG->dirroot.'/mod/wiki/wiki/wikibook.php');

require_once ($CFG->dirroot.'/mod/wiki/lib/wiki_page.class.php');

require_once($CFG->libdir . '/ajax/ajaxlib.php');

//grades lib
require_once ($CFG->dirroot.'/mod/wiki/grades/grades.lib.php');

//wiki tags lib
require_once ($CFG->dirroot.'/mod/wiki/tags/tags.lib.php');

require_js(array('yui_yahoo','yui_event', 'yui_connection'));


//-------------------------- MAIN CONFIGURATION FUNCTIONS ---------------------------------------

//this function manage the configuration of the main content
function wiki_main_setup(){
    global $CFG,$USER,$COURSE,$WS;

    $wikimanager = wiki_manager_get_instance();

    //recover the value from the same variable if there is not defined any GET or POST:
    $WS->page = optional_param('page',$WS->page, PARAM_CLEANHTML);    // Wiki Page Name

    //Always strip slashes from page to be 100% sure we can add them back
    //when sending info to DB. This must be done before any action with $page and DB!!
    $WS->page = stripslashes_safe($WS->page);
    // Selected tab
	$WS->selectedtab = '';

    //Define the path of URL, and define the module id if the wiki is a modul
    //or course id if the wiki is a course whit format 'wiki'
    $WS->wikitype='/mod/wiki/view.php?id=';
    $WS->linkid=$WS->cm->id;
    if (isset($WS->dfcourse)){
        $WS->wikitype='/course/view.php?id=';
        $WS->linkid=$COURSE->id;
    }

    //look for $editor_size
    $WS->editor_size = wiki_get_editor_size($WS);
    //look for $page
    if (!$WS->page) {
        //if there's no page caught the wiki instance first page
		if(!empty($WS->page)){
			$WS->page = wiki_get_real_pagename ($WS->page);
        }else{
			$WS->page = (!empty($WS->dfwiki->pagename))?$WS->dfwiki->pagename:get_string('firstpage','wiki');
		}

        //students are considerated only by group (like old dfwiki version).
        if ($WS->dfwiki->studentmode == 0){
            //if the first page exists put action in view, otherwise put in edit
            $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page, null,
                $WS->groupmember->groupid, null);
            if ($wikimanager->page_exists($pageid)) {
                if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                    $WS->pageaction = 'view';
                } else {
                    $WS->pageaction = 'discussion';
                }
            } else{
                if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                    $WS->pageaction = 'edit';
                } else {
                    $WS->pageaction = 'editdiscussion';
                }
            }
        }else{
            //case "without groups", with visible or separate students
            if ($WS->cm->groupmode == 0){
                //if the first page exists put action in view, otherwise put in edit
                $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page, null,
                   null, $WS->member->id);
                if ($wikimanager->page_exists($pageid)) {
                    //if the first page exists put action in view, otherwise put in edit
                    if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                        $WS->pageaction = 'view';
                    } else {
                        $WS->pageaction = 'discussion';
                    }
                } else{
                    if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                        $WS->pageaction = 'edit';
                    } else {
                        $WS->pageaction = 'editdiscussion';
                    }
                }

            //case "visible groups" or "separate groups", with visble or separate students
            } else{
                //if the first page exists put action in view, otherwise put in edit
                $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page, null,
                    $WS->groupmember->groupid, $WS->member->id);
                if ($wikimanager->page_exists($pageid)) {
                    //if the first page exists put action in view, otherwise put in edit
                    if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                        $WS->pageaction = 'view';
                    } else {
                   $WS->pageaction = 'discussion';
                    }
                } else{
                    if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                        $WS->pageaction = 'edit';
                    } else {
                        $WS->pageaction = 'editdiscussion';
                    }
                }
            }
        }
    } else {
        /* The page variable can contain the name of the page and the page action. These two parameterers
         * are separated by the character '/'. If the page doesn't have the separations character it's
         * suppossed to be just the page name, and the action will be 'view' if the page exists or 'edit'
         * in other cases.
         *
         * For example, to view a page named 'eddy' that already exists, we can do:
         *      page = 'eddy' or page = 'view/eddy'
         * But if we want to edit the 'eddy' page that already exists, we can do only this:
         *      page = 'edit/eddy'
         */
        $pageexplode = explode ('/',$WS->page);

        switch (count($pageexplode)){
            case 1: //only the page name is given
                $WS->page = wiki_get_real_pagename($pageexplode[0]);
                //checkes if the page name exists
                 if(($WS->dfwiki->studentmode == '0') && ($WS->cm->groupmode != '0')){
                    //only by groups
                     $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page,
                         null, $WS->groupmember->groupid, null);
                    if ($wikimanager->page_exists($pageid)) {
                        if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                              $WS->pageaction = 'view';
                        } else {
                              $WS->pageaction = 'discussion';
                        }
                    }else{
                        if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                            $WS->pageaction = 'edit';
                        } else {
                             $WS->pageaction = 'editdiscussion';
                        }
                     }
                  } else {
                    //by students and their groups
                    $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page,
                        null, $WS->groupmember->groupid, $WS->member->id);
                    if ($wikimanager->page_exists($pageid)) {
                          if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                              $WS->pageaction = 'view';
                        } else {
                              $WS->pageaction = 'discussion';
                        }
                    }else{
                        if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
                            $WS->pageaction = 'edit';
                        } else {
                             $WS->pageaction = 'editdiscussion';
                        }
                     }
                }
                break;
            case 2: //the page name and the action are given
                $WS->page = wiki_get_real_pagename($pageexplode[1]);
                $WS->pageaction = $pageexplode[0];

                // Control action tabs when we are doing preview of discussions
                if (substr($WS->page,0,strlen('discussion:'))=='discussion:') {
                    if ($WS->pageaction=='view') {
                        if (isset($WS->dfform['addtitle'])) {
                            $WS->selectedtab = 'adddiscussion';
                        } else {
                            $WS->selectedtab = 'editdiscussion';
                        }
                    }
                }

                break;
            default: //Error
                error('Locallib: necessary parameters needed');
                break;
        }
    }

    // Tab needed for the discussions...
    if ($WS->selectedtab=='') {
        $WS->selectedtab = $WS->pageaction;
    }

    //configure wikieditable
    //configure permissions
    wiki_load_permissions($WS);

    if(!$WS->dfperms['edit'] && $WS->pageaction=='edit'){
        $WS->pageaction = 'view';
		$WS->selectedtab = 'view';
    }

    //configure update data
    wiki_upload_config($WS);

    //treats the form
    wiki_form_treatment($WS);
    //fetch the latest page version

	// Loads the data of the wiki page, depending on the group mode
	$WS->load_page_data();

	// Selects the editor
	$WS->select_editor();

	// If the page exists, adds the action to log
	add_action_to_log();

}


//-------------------------- MAIN CONTENT FUNCTIONS ---------------------------------------

//this function creates all the content of dfwiki module
function wiki_main_content(&$WS){

	global $USER,$COURSE;

    $prop = null;
	$prop->id = "groupmode_selection";

	//if($WS->groupmember->groupid == 0 && $WS->cm->groupmode !=0){
	//	$WS->groupmember->groupid = 1;
	//}

    wiki_div_start($prop);
	wiki_print_groupmode_selection($WS);
    wiki_div_end();

    //print current tab content ($pageaction)
    wiki_action_content ($WS);
}

//-------------------------- TAB ACTIONS FUNCTIONS ---------------------------------------

//this function is the responsable of execute the main tab actions
function wiki_action_content (&$WS){

    if ($WS->wikibookinfo) {
    	echo $WS->wikibookinfo->navibar;
    }

    $section     = optional_param('section',       '', PARAM_TEXT);
    $sectionnum  = optional_param('sectionnum',     0, PARAM_INTEGER);
    $sectionhash = optional_param('sectionhash',   '', PARAM_TEXT);
    $preview     = optional_param('dfformpreview', '', PARAM_TEXT);
    $action      = optional_param('dfformaction',  '', PARAM_TEXT);

    $sectionstring = '';
    if (($section == '') && ($sectionnum == 0)) { // main page
        $section = '';
    } else {
        if (($action == 'edit') && ($preview == '') &&
            !wiki_is_uploading_modified())
            $section = '';
        else {
            $section = urldecode($section);
            $sectionstring = strtolower(get_string('section', ''));
            if (($preview != '') || wiki_is_uploading_modified())
                $sectionstring = ' ('.$sectionstring.' '.$section.')';
            else
                $sectionstring = ' ('.$sectionstring.' '.stripslashes($section).')';
        }
    }

    //this is used for giving an appropiate format to the pagename when a simple id is given
    if( is_numeric($WS->page)){
        echo '<h1>'.wiki_get_pagename_from_id($WS).$sectionstring.'</h1>';
    }else{
        if (substr($WS->page,0,strlen('discussion:'))=='discussion:') {
            echo ('<h1>');
            print_string('discussionabout','wiki');
			$pagename = substr($WS->page,strlen('discussion:'),strlen($WS->page));
            echo(': <i>'.format_text($pagename,FORMAT_PLAIN).'</i></h1>');
        } else {
			if($WS->pageaction=='admin'){
				echo '<h1>'.get_string("admin","wiki").": ".format_text($WS->page,FORMAT_PLAIN).$sectionstring.'</h1>';
			} else if ($WS->wikibookinfo) {
				echo '<h1>'.format_text($WS->wikibookinfo->title, FORMAT_PLAIN).$sectionstring.'</h1>';
			} else if ($WS->pageaction == 'info') {
                echo '<h1>'.format_text($WS->page, FORMAT_PLAIN).'</h1>'.
                     '<h3>'.get_string('created_on', 'wiki').' '.strftime('%A, %d %B %Y, %H:%M',$WS->pagedata->created);
                if (wiki_grade_got_permission($WS)) { // don't show the grade if the user hasn't permission
                    $grade = wiki_grade_get_wikigrade($WS);
                    echo('<br/>'.get_string('grade').': '.$grade.'</h3>');
                }
			} else {
                if ($section == '')
                    echo '<h1>'.format_text($WS->page,FORMAT_PLAIN).'</h1>';
                else {
                    if ($sectionhash != '')
                        $stringhash = '&amp;sectionhash='.$sectionhash;
                    else
                        $stringhash = '';
						$WS->pagedata->editor = 'htmleditor'; // force htmleditor - wysiwyg (nadavkav)
						echo '<h1><a href="view.php?id='.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;page=view/'.urlencode($WS->page).'&amp;editor='.$WS->pagedata->editor.$stringhash.'">'.format_text($WS->page,FORMAT_PLAIN).'</a>'.$sectionstring.'</h1>';
                }
			}
        }
    }

    print_box_start();

    if ($sectionhash != '')
        $pagename = $WS->pagedata->pagename.'#'.$sectionhash;
    else
        $pagename = $WS->pagedata->pagename;

    switch ($WS->pageaction)
    {
        case 'view':
			wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
            wiki_view_content($WS);
            break;
        case 'edit':
            if (wiki_set_lock($WS)){
                wiki_edit_content($WS);
            }
            break;
        case 'info':
			wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
            wiki_info_content($WS);
            break;
        case 'discussion':
            wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
            wiki_release_lock($WS, $WS->dfwiki->id, $WS->page);
			wiki_view_content($WS);
            break;
        case 'editdiscussion':
            if (wiki_set_lock($WS)){
                wiki_edit_content($WS);
            }
            break;
        case 'adddiscussion':
			wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
            wiki_edit_content($WS);
            break;
        case 'infodiscussion':
            wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
			wiki_info_content($WS);
            break;
        case 'navigator':
			wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
            wiki_navigator_content($WS);
            break;
		case 'admin':
			wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
			wiki_admin($WS);
			break;
        default:
		    print_string('nopageaction','wiki');
            break;
    }

    print_box_end();

    // show wiki tags
    wiki_tags_print_viewbox($WS);

    // add grade evaluation box if possible
    wiki_grade_print_page_evaluation_box($WS);

    if ($WS->wikibookinfo) {
    	echo $WS->wikibookinfo->navibar;
    }
}

//this function create view tab content
function wiki_view_content(&$WS){
    global $CFG,$USER,$COURSE;

    // Set grade of the page
    wiki_grade_set_page_grade($WS);

    if ($WS->wikibookinfo) {
        if ($WS->wikibookinfo->index) {
            echo parse_nwiki_text($WS->wikibookinfo->index);
            return;
        } else {
            // don't print any content at leaf fake chapters
            if ($WS->wikibookinfo->isleaf)
                return;
        }
    }

    //if there's content to show
    if (isset($WS->pagedata->content)){

        if(($WS->dfwiki->studentmode == '0') && ($WS->cm->groupmode != '0')) {
            //only by groups
            $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page, null,
                $WS->groupmember->groupid, null);
        } else{
            //by students and their groups
            $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page, null,
                $WS->groupmember->groupid, $WS->member->id);
        }

        $wikimanager = wiki_manager_get_instance();
        if (!$wikimanager->increment_page_hits($pageid)) {
            error('Page hits not incremented!.');
        }

        //print page content
        //convert wiki content to html content
        wiki_print_page_content (stripslashes_safe($WS->pagedata->content),$WS);

        // wiki votes
		if($WS->dfwiki->votemode==1){

			$voted = false;

			$OK=optional_param("Vote",NULL,PARAM_ALPHA);

			if ($OK == 'Vote') {
                $vote = $wikimanager->vote_page($WS->dfwiki->id, $WS->page,
                    $WS->pagedata->version, $USER->username);
            }

			if(empty($OK)){
				if(isset($WS->dfcourse)) {
					$prop = null;
					$prop->action = $CFG->wwwroot."/course/view.php";
					$prop->method = "post";
					wiki_form_start($prop);

					$prop = null;
					$prop->class = 'textcenter';
					wiki_div_start($prop);

					$prop = null;
					$prop->name = "id";
					$prop->value = "{$WS->cm->course}";
					wiki_input_hidden($prop);

				} else {
					$prop = null;
					$prop->action = $CFG->wwwroot."/mod/wiki/view.php";
					$prop->method = "post";
					wiki_form_start($prop);

					$prop = null;
					$prop->class = 'textright';
					wiki_div_start($prop);

					$prop = null;
					$prop->name = "id";
					$prop->value = "{$WS->cm->id}";
					wiki_input_hidden($prop);
				}

				$prop = null;
				$prop->name = "page";
				$prop->value = $WS->page;
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = "gid";
				$prop->value = "{$WS->groupmember->groupid}";
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = "uid";
				$prop->value = "{$WS->member->id}";
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = "Vote";
				$prop->value = "Vote";
				wiki_input_submit($prop);

				wiki_br(1);
				wiki_div_end();
				wiki_form_end();
			}
		}

    }else{
        print_string('nocontent','wiki');
    }

}

/**
 * Returns the depth (0..6) of the section header
 *
 * @param  String $line    Line of a wiki page text
 * @param  String $section Name of the section
 * @return int    $depth   Depth between 0 and 6
 */
function wiki_get_section_depth($line, $section) {
    if ($section == '') return -1;

    if ($section != '(.*)+') // one defined section
        $section = preg_quote($section, '/');

    if      (ereg('^==========( )*'.$section.'( )*==========', $line)) return 10;
    else if (ereg('^=========( )*'.$section.'( )*=========', $line))   return 9;
    else if (ereg('^========( )*'.$section.'( )*========', $line))     return 8;
    else if (ereg('^=======( )*'.$section.'( )*=======', $line))       return 7;
    else if (ereg('^======( )*'.$section.'( )*======', $line))         return 6;
    else if (ereg('^=====( )*'.$section.'( )*=====', $line))           return 5;
    else if (ereg('^====( )*'.$section.'( )*====', $line))             return 4;
    else if (ereg('^===( )*'.$section.'( )*===', $line))               return 3;
    else if (ereg('^==( )*'.$section.'( )*==', $line))                 return 2;
    else if (ereg('^=( )*'.$section.'( )*=', $line))                   return 1;
    else return 0;
}

/**
 * Returns the number of wiki sections inside a text.
 *
 * @param String $text    Text which may contain sections
 */
function wiki_get_number_of_sections($text)
{
    $numsections = 0;
    $lines       = explode("\n", $text);
    foreach ($lines as $current_line) {
        if (wiki_get_section_depth($current_line, '(.*)+') > 0)
            $numsections++;
    }
    return $numsections;
}

/**
 * Returns an array with the positions of a section name in a text,
 * if there is only a section with that name returns only an array
 * with one position.
 *
 * @param  String $text    Text which may contain sections
 * @param  String $section Name of the section
 * @return Array           Contains the positions of the section
 */
function wiki_get_section_positions($text, $section)
{
    $positions = array();
    $numsections = 0;
    $lines       = explode("\n", $text);
    foreach ($lines as $current_line) {
        if (wiki_get_section_depth($current_line, '(.*)+') > 0)
            $numsections++;
        if (wiki_get_section_depth($current_line, $section) > 0)
            $positions[] = $numsections;
    }

    return $positions;
}

/**
 * Returns true if its a section link: pagename#sectionname
 * or pagename##sectionname
 */
function wiki_is_section_link($link)
{
    if (ereg('(.*)+(#|##)(.*)+', $link))
        return true;
    return false;
}

/**
 * Filters the section part of a link label and leaves only the page name.
 * E.g: with "pagename##section" or "pagename#section" returns "pagename".
 *
 * @param  String $link
 * @return String Filtered string
 */
function wiki_filter_section_link($link)
{
    if (ereg('(.*)+(#|##)(.*)+', $link)) { // section link
        $parts = explode('#', $link);
        return $parts[0]; // return the page name
    } else  // page link
        return $link;
}

/**
 * Returns an array consisting of page links only, filtering the section part
 * of the links.
 *
 * @param  Array $link
 * @return Array Filtered string
 */
function wiki_filter_section_links($links)
{
    $res = array();
    foreach ($links as $link)
    {
        $filtered_link = wiki_filter_section_link($link);
        if (!in_array($filtered_link, $res))
            $res[] = $filtered_link;
    }
    return $res;
}

/**
 * Returns an array consisting of page links only, removing the ones that are
 * section links.
 *
 * @param  Array $link
 * @return Array Filtered string
 */
function wiki_remove_section_links($links)
{
    $res = array();
    foreach ($links as $link)
    {
        $filtered_link = wiki_filter_section_link($link);
        if (!in_array($filtered_link, $res) &&
            ($filtered_link == $link))
            $res[] = $filtered_link;
    }
    return $res;
}

/**
 * Returns true if the section exist in the page
 *
 * @param $pagename Page name
 * @param $section  Section name
 */
function wiki_section_exists($pagename, $section)
{
    $pagename    = wiki_get_real_pagename($pagename);
    $page        = wiki_page_last_version($pagename);
    $sectionnums = wiki_get_section_positions($page->content, $section);
    if (count($sectionnums) < 1)
        return false;
    return true;
}

/**
 * Divides a wiki page content in 3 parts to allow a proper section
 * viewing/editing. We need the name of the section and the number
 * to allow sections with same name.
 *
 * The parts are:
 * 1. prev_part:    the text previous to the section
 * 2. current_part: the text of the section
 * 3. next_part:    the text after the section
 *
 * @param  String $text       Wiki page text
 * @param  String $section    Name of the section to cut
 * @param  String $sectionnum Number of the section to cut
 * @param  String $check      'nocheck' implies a weaker section validation
 * @return Array  $res        Array composed of 3 parts
 */
function wiki_split_sections($text, $section, $sectionnum) {
    global $WS;

    $res->prev_part    = '';
    $res->current_part = $text;
    $res->next_part    = '';
    $res->error        = '';

    // param checking
    if ($text == '') return $res;

    $res->current_part = '';
    $prev_part         = '';
    $next_part         = '';

    $current_depth   = -1;
    $matched_section = false;
    $num_sections    = 0;

    // analyze the text to divide the section in the 3 parts
    $lines           = explode("\n", $text);
    $numlines = 0;
    foreach ($lines as $current_line) {
        $numlines++;
        if (!isset($current_part)) { // we haven't found the section yet
            $current_depth = wiki_get_section_depth($current_line, $section);
            if (wiki_get_section_depth($current_line, '(.*)+') > 0)
                $num_sections++;
            if ($current_depth > 0) { // probably section found
                if ($num_sections == $sectionnum) { // if it's really the one we want
                    $current_part    = $current_line."\n";
                    $matched_depth   = $current_depth;
                    $matched_section = true;
                } else { // it's a section with the same name but not the one we want
                    $prev_part .= $current_line."\n";
                }
            } else // section not found, so it's text previous to it
                $prev_part .= $current_line."\n";
        } else {
            $new_depth = wiki_get_section_depth($current_line, '(.*)+');
            if ($new_depth > 0) {
                $num_sections++;
                $current_depth = $new_depth;
                if ($current_depth <= $matched_depth)
                    $matched_section = false;
            }
            if ($matched_section) { // belongs to the section
                $current_part .= $current_line."\n";
            } else { // doesn't belong to the section, so it's after
                 $next_part .= $current_line."\n";
            }
        }
    }

    // could find the section content ?
    if (!isset($current_part)) {
        $urls = wiki_view_page_url($WS->page, $section, 0, '');
        $res->error  = get_string('sectionerror', 'wiki', $section).'. '.
                       get_string('sectionchanged', 'wiki').': '.
                       '<a href="'.$urls[0].'">'.$WS->page.'</a>';
        return $res;
    }


    // construct and return the result
    $res->prev_part    = $prev_part;
    $res->current_part = $current_part."\n";
    $res->next_part    = $next_part;

    return $res;
}

/**
 * Returns the real position of the section inside the page.
 *
 * When editing a wiki page it's common that several people
 * add/delete different sections at the same time since section
 * editing is independent. If sections are added/deleted while we want
 * to view or edit another one we must ensure that the number of the
 * section is correct.
 *
 * @param  String $pagename   Name of the wiki page
 * @param  String $section    Name of the section
 * @param  String $sectionnum Number of the section
 * @return Integer            The real position of the section in the page
 */
function wiki_get_real_section_position($pagename, $section, $sectionnum)
{
    global $WS;
    $page = wiki_page_last_version($pagename, $WS);

    // here we have more than one section with the same name so we have
    // to check which one is, so we use the hash we calculated before
    $sectionhash = optional_param('sectionhash', '', PARAM_TEXT);
    if ($sectionhash == '') return $sectionnum;

    $numsections = 0;
    $lines       = explode("\n", $page->content);
    $numlines    = count($lines);
    for ($i = 0; $i < $numlines; $i++) {
        if (wiki_get_section_depth($lines[$i], '(.*)+') > 0)
            $numsections++;
        $section_depth = wiki_get_section_depth($lines[$i], $section);
        if ($section_depth > 0) { // found, lets get the lines of the section
            $j = $i;
            $text = $lines[$j];
            $j++;
            $text2 = '';
            while (($j < $numlines))
            {
                $line_depth = wiki_get_section_depth($lines[$j], '(.*)+');
                if (($line_depth > $section_depth) || ($line_depth == 0))
                    $text  .= $lines[$j];
                else
                    break;
                $j++;
            }
            if (md5($text) == $sectionhash)
                return $numsections;
        }
    }

    return 0;
}

/**
 * Returns the md5 hash of a section's content in a text.
 *
 * @param  String  $text       Content text
 * @param  Integer $sectionnum Position of the section in the text
 * @return String              md5sum of content of the section
 */
function wiki_get_section_hash($text, $sectionnum)
{
    $numsections = 0;
    $lines       = explode("\n", $text);
    $numlines    = count($lines);
    for ($i = 0; $i < $numlines; $i++) {
        if (wiki_get_section_depth($lines[$i], '(.*)+') > 0)
            $numsections++;
        if ($numsections == $sectionnum) {
            $section_depth = wiki_get_section_depth($lines[$i], '(.*)+');
            $text = $lines[$i]; $i++;
            while (($i < $numlines))
            {
                $line_depth = wiki_get_section_depth($lines[$i], '(.*)+');
                if (($line_depth > $section_depth) || ($line_depth == 0))
                    $text  .= $lines[$i];
                else
                    break;
                $i++;
            }
            return md5($text);
        }
    }
    return 0;
}

/**
 * Updates the section of a wiki page with a new content.
 *
 * @param  String $pagename   Page to update
 * @param  String $section    Section of the page to update
 * @param  String $newcontent Content of the section to update
 * @return String $txt        Content of the page updated
 */
function wiki_join_sections($pagename, $section, $sectionnum, $newcontent)
{
    global $WS, $USER;

    $txt  = '';
    $page = wiki_page_last_version($pagename, $WS);

    // check if someone has added/deleted sections while we were editing,
    $sectionnum = wiki_get_real_section_position($pagename, $section, $sectionnum);

    // check if someone has overrided its lock
    $sectionhash = optional_param('sectionhash', '', PARAM_TEXT);
    $pagename    = $pagename.'#'.$sectionhash;
    if (!wiki_is_locked_by_userid($WS, $WS->dfwiki->id, $pagename, $USER->id)) {
        error(get_string('sectionoverrided', 'wiki'));
    }

    $res = wiki_split_sections($page->content, $section, $sectionnum);
    if ($res->error != '') {
        wiki_release_lock($WS, $WS->dfwiki->id, $pagename.'#'.$sectionhash);
        error($res->error);
    }
    else {
        $txt .= $res->prev_part;
        $txt .= $newcontent."\n";
        $txt .= $res->next_part;
    }
    return $txt;
}

/**
 * Returns true if the page/section editing comes from
 * an addition or deletion of a file.
 */
function wiki_is_uploading_modified()
{
    $comeFromAddUpload = optional_param('dfformupload',  '', PARAM_TEXT);
    $comeFromDelUpload = optional_param('dfformdelfile', '', PARAM_TEXT);

    if (($comeFromAddUpload == '') &&
        ($comeFromDelUpload == ''))
        return false;
    else
        return true;
}

//this function create edit tab content
function wiki_edit_content(&$WS){
    global $CFG;

    $context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
	require_capability('mod/wiki:caneditawiki',$context);
	$cont = false;
    $txt = '';

    $titlebox='';
    $oldcontentbox='';
    $viewtitle = '0';

    if (($WS->pageaction=='adddiscussion') or (isset($WS->dfform['addtitle']) and ($WS->pageaction=='edit'))) {
      $viewtitle = '1';
        if (isset($WS->dfform['addtitle'])) {
            $titlebox = $WS->dfform['addtitle'];
            $oldcontentbox = $WS->dfform['oldcontent'];
            $contentbox = $WS->dfform['content'];
        } else {
            $titlebox='';
            $oldcontentbox = $WS->dfform['content'];
            $contentbox = '';
        }
    } else {
        if (isset($WS->dfform['content'])) {
            $contentbox = $WS->dfform['content'];
        } else {
          $contentbox='';
        }
    }

    //need to check if  we have preview mode
    $preview = optional_param('dfformpreview',NULL,PARAM_ALPHA);
    if (isset($preview)) {
        //we're in preview mode
        print_simple_box_start( 'center', '100%', '', '20');
        wiki_size_text("Preview", 2);

        // discussions
        if ($titlebox=='') {
            $txt = $oldcontentbox.$contentbox;
        } else {
            switch ($WS->dfwiki->editor) {
                case 'ewiki':
                    $txt = $oldcontentbox.chr(13).chr(10).'!!! '.$titlebox.' '.chr(13).chr(10).$contentbox.chr(10);
                    break;
                case 'htmleditor':
                    $txt = $oldcontentbox.chr(13).chr(10).'<h1> '.$titlebox.' </h1>'.chr(13).chr(10).$contentbox.chr(10);
                    break;
                default:
                    $txt = $oldcontentbox.chr(13).chr(10).'= '.$titlebox.' ='.chr(13).chr(10).$contentbox.chr(10);
                    break;
            }
        }

        wiki_print_page_content (stripslashes_safe($txt),$WS);
        print_simple_box_end();

    } else {
        //we're not in preview mode
        //check if there's content
		//echo 'wiki_forceeditorchange='.$CFG->wiki_forceeditorchange;
        if (!isset($WS->pagedata->content) || $CFG->wiki_forceeditorchange) {
            if (!isset($WS->pagedata->content)) { $WS->pagedata->content = ''; }

            //  si no hem decidit encara l'editor per aquesta pagina i no hem adjuntat cap arxiu demanem l'editor
            if ((!isset($WS->dfform['selectedit'])) && ((!isset($WS->pagedata->editor)) || ($WS->pagedata->editor == '')) || $CFG->wiki_forceeditorchange){
                $cont = true; //$pagename=stripslashes($pagename);

                $prop = null;
                $prop->border = "0";
                $prop->class = "nwikileftnow";
                wiki_table_start($prop);
/// removed "choose different wiki syntax on new page listbox" so teachers will not get confused (nadavkav 25.10.09)
/*
				print_string('anothereditor','wiki');
				//Converts reserved chars for html to prevent chars misreading
				$pagetemp = stripslashes_safe($WS->page);

                wiki_change_column();
				$prop = null;
				$prop->id = "sel_edit";
				$prop->method = "post";
				$prop->action = 'view.php?id='.$WS->linkid.'&amp;page=edit/'.urlencode($pagetemp).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id;
				wiki_form_start($prop);
				wiki_div_start();
				$opt = "";
                if ($WS->dfwiki->editor != 'dfwiki'){
                	$prop = null;
                	$prop->value = "0";
                	$opt = wiki_option(get_string('dfwiki','wiki'), $prop, true);
                }
                if ($WS->dfwiki->editor != 'nwiki'){
                	$prop = null;
                	$prop->value = "3";
                	$opt .= wiki_option(get_string('nwiki','wiki'), $prop, true);
                }
                if ($WS->dfwiki->editor != 'ewiki'){
                	$prop = null;
                	$prop->value = "1";
                	$opt .= wiki_option(get_string('ewiki','wiki'), $prop, true);
				}
                if ($WS->dfwiki->editor != 'htmleditor'){
                	$prop = null;
                	$prop->value = "2";
                	$opt .= wiki_option(get_string('htmleditor','wiki'), $prop, true);
                	}
                $prop = null;
                $prop->name = "dfformselectedit";
                wiki_select($opt,$prop);
                $prop = null;
                $prop->value = get_string("continue");
                wiki_input_submit($prop);
                wiki_div_end();
                wiki_form_end();
*/
                $prop = null;
                $prop->class = "nwikileftnow";
                wiki_change_row($prop);
                echo "&nbsp;";
                wiki_table_end();

                if (isset($WS->dfform['content'])){
                      $content_text = &$WS->dfform['content'];
                }else{
                      $content_text = &$WS->pagedata->content;
                }

			if (isset($WS->dfform['selectedit'])) {

				switch ($WS->dfform['selectedit']){
					case '0':
						$WS->pagedata->editor = $WS->dfwiki->editor =  'dfwiki';
						break;
					case '1':
						$WS->pagedata->editor = $WS->dfwiki->editor = 'ewiki';
						break;
					case '2':
						$WS->pagedata->editor = $WS->dfwiki->editor =  'htmleditor';
						break;
					case '3':
						$WS->pagedata->editor = $WS->dfwiki->editor = 'nwiki';
						break;
					default:
						error ('No editor was selected');
						break;
				}

			} else {
				$WS->pagedata->editor = $WS->dfwiki->editor;
			}
                wiki_print_editor($content_text,$viewtitle,$titlebox,'',$WS);
            }
        }
    }

    if (isset($WS->dfform['content'])){
        $content_text = stripslashes_safe($WS->dfform['content']);
    }else{
        $content_text = stripslashes_safe($WS->pagedata->content);
    }



    // partial editing of a section available only with nwiki editor
    $section     = optional_param('section',     '', PARAM_TEXT);
    $sectionnum  = optional_param('sectionnum',   0, PARAM_INTEGER);
    if (!isset($preview) && $WS->pagedata->editor == 'nwiki' &&
        !(($section == '') && ($sectionnum == 0)) && // we are not in main page
        !wiki_is_uploading_modified())
    {
        $section    = stripslashes($section);
        $sectionnum = wiki_get_real_section_position($WS->page, $section, $sectionnum);
        $res        = wiki_split_sections($content_text, $section, $sectionnum);
        if ($res->error != '') {
            $sectionhash = optional_param('sectionhash', '', PARAM_TEXT);
            wiki_release_lock($WS, $WS->dfwiki->id, $WS->pagedata->pagename.'#'.$sectionhash);
            error($res->error);
        }
        else
            $content_text = $res->current_part;
    }

     //print the editor
    if ($cont == false){
        if (!isset($WS->dfform['selectedit'])){
            $editor = optional_param('editor','',PARAM_ALPHA);
            if (!empty($WS->dfform['editor'])) $WS->pagedata->editor = $WS->dfform['editor'];
            else if (!empty($editor)) $WS->pagedata->editor = $editor;
            if (($WS->pageaction=='adddiscussion') or (isset($WS->dfform['addtitle']))) {
                if (!isset($oldcontentbox)) {
                    wiki_print_editor($contentbox,$viewtitle,$titlebox,$content_text,$WS);
                } else {
                    wiki_print_editor($contentbox,$viewtitle,$titlebox,$oldcontentbox,$WS);
                }
            } else {
                wiki_print_editor($content_text,$viewtitle,$titlebox,'',$WS);
            }
        }
        else{
            //comming from the above form
            switch ($WS->dfform['selectedit']){
                case '0':
                    $WS->pagedata->editor = 'dfwiki';
                    wiki_print_editor($contentbox,$viewtitle,$titlebox,$oldcontentbox,$WS);
                    break;
                case '1':
                    $WS->pagedata->editor = 'ewiki';
                    wiki_print_editor($contentbox,$viewtitle,$titlebox,$oldcontentbox,$WS);
                    break;
                case '2':
                    $WS->pagedata->editor = 'htmleditor';
                    wiki_print_editor($contentbox,$viewtitle,$titlebox,$oldcontentbox,$WS);
                    break;
                case '3':
                    $WS->pagedata->editor = 'nwiki';
                    wiki_print_editor($contentbox,$viewtitle,$titlebox,$oldcontentbox,$WS);
                    break;
                default:
                    error ('No editor was selected');
                    break;
            }
        }
    }
}


/**
 * Display History tab content with page versions
 */
function wiki_info_content(&$WS)
{
    global $CFG, $COURSE, $USER;

    //generate a list with all versions
    $vers = wiki_get_all_page_versions($CFG->prefix, $WS->page, $WS->dfwiki->id,
                                       $WS->groupmember->groupid, $WS->member->id);
    if ($vers) {
        $countver = count($vers);

        if ($countver > 1) {
            echo ('<script type="text/javascript" src="wiki/hist.js"></script>');

            echo('<form id="historyform" method="post" '.
                 'action="view.php?id='.$WS->linkid.'&amp;page='.urlencode('diff/'.$WS->page).
                 "&amp;gid={$WS->groupmember->groupid}&amp;uid={$WS->member->id}".'">');

            echo('<input type="submit" name="'.get_string('compareversions','wiki').'" value="'.get_string('compareversions','wiki').'"/>');
            echo('<br/><br/>');
        }

        //print table with the page versions
        $prop = null;
        $prop->id       = 'historytable';
        $prop->width    = "100%";
        $prop->border   = "1";
        $prop->padding  = "5";
        $prop->spacing  = "1";
        $prop->class    = "generaltable boxalignleft";
        $prop->header   = "true";
        $prop->valignth = "top";
        $prop->classth  = 'header c0 nwikileftnow';
        wiki_table_start($prop);

        if ($countver > 1)
        {
            echo ('');

            $prop = null;
            $prop->header = "true";
            $prop->valign = "top";
            $prop->class  = "nwikileftnow header c1";
            wiki_change_column($prop);
        }
        echo get_string('version');

        $prop = null;
        $prop->header = "true";
        $prop->valign = "top";
        $prop->class  = "nwikileftnow header c1";
        wiki_change_column($prop);
        echo get_string('user');

        $prop = null;
        $prop->header = "true";
        $prop->valign = "top";
        $prop->class  = "nwikileftnow header c2";
        wiki_change_column($prop);
        echo get_string('lastmodified');

        if (wiki_grade_got_permission($WS)) {
            $prop = null;
            $prop->header = "true";
            $prop->valign = "top";
            $prop->class  = "nwikileftnow header c3";
            wiki_change_column($prop);
            echo get_string('eval_editions_quality', 'wiki');
        }

        $prop = null;
        $prop->header = "true";

        //print content
        $i = 0;
        foreach ($vers as $ver)
        {
            if ($ver->highlight)
                $class = "textcenter nwikihighlight";
            else
                $class = "textcenter nwikibargroundblanco";

            if (isset($prop->header)) {
                $prop->class = $class;
                wiki_change_row($prop);
            } else {
                $prop = null;
                $prop->class = $class;
                wiki_change_row($prop);
            }

            // input types for history diff
            if ($countver > 1) {
                $style   = 'style="visibility:hidden" ';
                $checked = 'checked="checked"';

                if ($i == 0) {
                    echo('<input type="radio" value="'.$ver->version.'" name="oldid" '.$style.' />');
                    echo('<input type="radio" value="'.$ver->version.'" name="diff" '.$checked.' />');
                } elseif ($i == 1) {
                    echo('<input type="radio" value="'.$ver->version.'" name="oldid" '.$checked.' />');
                    echo('<input type="radio" value="'.$ver->version.'" name="diff" '.$style.' />');
                } else {
                    echo('<input type="radio" value="'.$ver->version.'" name="oldid" />');
                    echo('<input type="radio" value="'.$ver->version.'" name="diff" '.$style.' />');
                }

                $prop = null;
                $prop->class = $class;
                wiki_change_column($prop);
            }

            if ($ver->version == $WS->pagedata->version) {
                echo $ver->version;
            } else {
                $prop = null;
                $prop->href   = "javascript:document.forms['formu".$i."'].submit()";
                $out          = wiki_a($ver->version, $prop, true);

                $prop         = null;
                $prop->name   = "dfcontent";
                $prop->value  = "11";
                $out          .= wiki_input_hidden($prop, true);
                $out2         = wiki_div($out, '', true);

                $prop         = null;
                $prop->id     = "formu$i";
                $prop->action = "view.php?id={$WS->linkid}&amp;page=".urlencode("oldversion/$ver->pagename").
                                "&amp;ver=$ver->version&amp;gid={$WS->groupmember->groupid}&amp;uid={$WS->member->id}";
                $prop->method = "post";
                wiki_form($out2, $prop);
            }

            $prop = null;
            $prop->class = $class;
            wiki_change_column($prop);
            $author = wiki_get_user_info($ver->author);
            echo $author;

            $prop = null;
            $prop->class = $class;
            wiki_change_column($prop);
            $modified = strftime('%A, %d %B %Y %H:%M',$ver->lastmodified);
            echo $modified;

            if (wiki_grade_got_permission($WS)) {
                $prop = null;
                $prop->class = $class;
                wiki_change_column($prop);

                $scale = array(1 => "+", 2 => "=", 3 => "-");
                $gradevalue = get_field_select('wiki_evaluation_edition', 'valoration', 'wiki_pageid='.$ver->id);

                if ($gradevalue)
                    echo(wiki_grade_translate($gradevalue, $scale));
                else
                    echo(get_string('eval_notset', 'wiki'));
            }

            $i++;
        }
        wiki_table_end();

        if ($countver > 1) {
            echo('<br/>');
            echo('<input type="submit" name="'.get_string('compareversions','wiki').'" value="'.get_string('compareversions','wiki').'"/>');
        }

        echo('</form>');

    } else {
        print_string('noversion','wiki');
    }
}

function wiki_navigator_content(&$WS){
    global $CFG;
    $table = new stdClass;
    $table->head=array (get_string('camefrom','wiki'),get_string('pagename','wiki'),get_string('goesto','wiki'));
    $ead = wiki_manager_get_instance();//new wiki_ead_tools();
    $camefroms = $ead->get_wiki_page_camefrom($WS->pagedata->pagename);
    $goestos = $ead->get_wiki_page_goesto($WS->pagedata->pagename);
	$out = wiki_b($WS->page,'', true);
    $prop = null;
    $prop->class = 'textcenter';
    $out2 = wiki_paragraph($out,$prop, true);
    $table->data[]= array ("",$out2,"");
    $i=0;
    foreach ($camefroms as $cpage){
		$prop = null;
		$prop->href = $CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;page='.urlencode($cpage->pagename);
		$out = wiki_a($cpage->pagename, $prop, true);

		$prop = null;
		$prop->class = 'textcenter';
		$out2 = wiki_paragraph($out,$prop, true);
		$table->data[0][0] .= $out2;
    }
    foreach ($goestos as $gpage){
		if (wiki_page_exists($WS,$gpage)){
			$prop = null;
			$prop->href = $CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;page='.urlencode($gpage);
			$out=wiki_a($gpage,$prop, true);

			$prop = null;
			$prop->class = 'textcenter';
    		$out2 = wiki_paragraph($out,$prop, true);
			$table->data[0][2].=$out2;

		} else{
			$out= "";
			$prop = null;
			$prop->href = $CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;page='.urlencode($gpage);
			$out .= wiki_a($gpage,$prop, true);

    		$prop = null;
			$prop->class = 'textcenter nwikiwanted';
    		$out2 = wiki_paragraph($out,$prop, true);
			$table->data[0][2].=$out2;
		}
    }
    $table->width='100%';
    print_table($table);

}



function wiki_admin($WS){

	global $CFG, $COURSE;

	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
    require_capability('mod/wiki:editawiki',$context);

	$ead = wiki_manager_get_instance();//new wiki_ead_tools();
	$tools = array (
		array(get_string('mostviewed','wiki'),
			$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id."&amp;dfcontent=0"),
		array(get_string('updatest','wiki'),
			$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id."&amp;dfcontent=1"),
		array(get_string('newest','wiki'),
			$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id."&amp;dfcontent=2"),
		array(get_string('wanted','wiki'),
			$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id."&amp;dfcontent=3"),
		array(get_string('orphaned','wiki'),
			$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id."&amp;dfcontent=4"),
		array(get_string('activestusers','wiki'),
			$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id."&amp;dfcontent=5")
	);

		$table1->align = array ("left");
		$table1->tablealign = "left";
	    $table1->width = '100%';
    	$table1->cellpadding = 2;
    	$table1->cellspacing = 0;
    	$table1->head = array(get_string("stads","wiki"));

 		//print public tools
        $i=20;
        foreach ($tools as $tool){
        	$prop = null;
    		$prop->href = $tool[1];
    		$out = wiki_a($tool[0], $prop, true);
			$table1->data[] = array($out);
			$i++;
        }

    	if (isset($table1->data)) {
            print_table($table1);
        }
        wiki_br(1);

		$table2->align = array ("left");
		$table2->tablealign = "left";
	    $table2->width = '100%';
    	$table2->cellpadding = 2;
    	$table2->cellspacing = 0;
        $table2->head = array(get_string("admin","wiki"));

        //teacher page dependant tools
        $tools = array (
 				array(get_string('delpage','wiki'),
					$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;delpage='.urlencode($WS->pagedata->pagename).'&amp;dfsetup=0'),
                array(get_string('updatepage','wiki'),
					$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;updatepage='.urlencode($WS->pagedata->pagename).'&amp;dfsetup=1'),
                array(get_string('cleanpage','wiki'),
					$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;cleanpage='.urlencode($WS->pagedata->pagename).'&amp;dfsetup=2')
            );

        //teacher non page dependant tools
        $tools_indep = array (
                array(get_string('exportxml','wiki'),
					$CFG->wwwroot.'/mod/wiki/xml/exportxml.php?id='.$WS->cm->id.'&amp;pageaction=exportxml'),
                array(get_string('importxml','wiki'),
					$CFG->wwwroot.'/mod/wiki/xml/importxml.php?id='.$WS->cm->id),
                array(get_string('viewexported','wiki'),
					$CFG->wwwroot.'/mod/wiki/xml/index.php?id='.$WS->dfwiki->course.'&amp;wdir=/exportedfiles'),
                array(get_string('exporthtml','wiki'),
					$CFG->wwwroot.'/mod/wiki/html/exporthtml.php?id='.$WS->cm->id),
                /*array(get_string('dfwikitonewwiki','wiki'),
					$CFG->wwwroot.'/mod/wiki/dfwikitonewwiki.php?id='.$WS->cm->id),*/
				array(get_string('wikitopdf','wiki'),
					$CFG->wwwroot.'/mod/wiki/wikitopdf.php?id='.$WS->cm->id.'&amp;cid='.$COURSE->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;page='.urlencode($WS->pagedata->pagename).'&amp;version='.$WS->pagedata->version),
                array(get_string('wikibooktopdf','wiki'),
					$CFG->wwwroot.'/mod/wiki/export/wikibook2pdf/wikibooktopdf.php?cmid='.$WS->cm->id.'&amp;cid='.$COURSE->id.'&amp;gid='.$WS->groupmember->groupid),
                array(get_string('eval_reports','wiki'),
					$CFG->wwwroot.'/mod/wiki/grades/grades.evaluation.php?cid='.$COURSE->id.'&amp;cmid='.$WS->cm->id)
                );
                //public tools
    	        $tools_sens = array (
								array(($WS->pagedata->editable==0),get_string('en1page','wiki'),$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;enpage='.urlencode($WS->pagedata->pagename).'&amp;dfsetup=3'),
								array(($WS->pagedata->editable==1),get_string('en0page','wiki'),$CFG->wwwroot.$WS->wikitype.$WS->linkid.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;enpage='.urlencode($WS->pagedata->pagename).'&amp;dfsetup=3'),
            );

    	if (wiki_can_change($WS)){
    		foreach ($tools as $tool){
    					$prop = null;
    					$prop->href = $tool[1];
    					$out = wiki_a($tool[0], $prop, true);
    	    			$table2->data[] = array($out);
				$i++;
    		}
    		foreach ($tools_indep as $tool){
    			$prop = null;
    			$prop->href = $tool[1];
    			$out = wiki_a($tool[0], $prop, true);
    			$table2->data[] = array($out);
    		}
    		foreach ($tools_sens as $tool){
    			if ($tool[0]) {
//    				$table2->data[] = array("<form id=\"form$i\" action=\"".$tool[2]."\" method=\"post\">"."\n".'<div><a href="javascript:document.forms[\'form'.$i.'\'].submit()" title="'.$tool[1].' '.$WS->pagedata->pagename.'">'.$tool[1].' '.$WS->pagedata->pagename.'</a>'.$tool[3]."\n"."</div></form>"."\n");
					$prop = null;
    				$prop->href = $tool[2];
    				$out = wiki_a($tool[1]." ".$WS->pagedata->pagename, $prop, true);
					$table2->data[] = array($out);
					$i++;
    			}
    		}

    	}

    	if (isset($table2->data)) {
            print_table($table2);
            }

}

function wiki_get_students_page(&$WS){

	global $CFG;

    //generate a list with all versions
    if ($vers = wiki_get_all_page_versions($CFG->prefix,$WS->page,$WS->dfwiki->id,$WS->groupmember->groupid, $WS->member->id)){
        $xusmi = array();

		foreach ($vers as $ver){
            $author = $ver->author;
			$xusmi[]= $author;
        }

		$buit = array();
		$users = array();
		$i=0;
			//while ($xusmi[$i]){
			while(isset($xusmi[$i])){
				$tractat = $xusmi[$i];
				//$trobat=$false;
				$trobat = false;
				$j=0;
				//while ($buit[$j] && !$trobat){
				while(isset($buit[$j]) && !$trobat){
					if ($buit[$j]==$tractat){
						$trobat=true;
					}
					$j++;
				}
				if ($trobat==false){
					$buit[]=$tractat;
					$row = $tractat;
					$users[] = $row;
				}
			$i++;
			}

		return $users;

		}else{
			$users=array(); return $users ;
		}
}

function wiki_get_students_wiki(){

	$users = get_records('user');
	$total_users = array();
	$i = 0;
	foreach($users as $clau=>$valor){
			$killed = $valor->deleted;
			if ($killed==0 && $valor->username!="guest"){
				$total_users[$i] = $valor->username;
				$i++;
			}
	}
	return $total_users;
}

function wiki_get_teachers_course(&$WS){
    // TODO: AQUESTA TAULA JA NO EXISTEIX !!!!
    $user_id= get_records('user_teachers','course',$WS->dfwiki->course);
	$j = 0;
	$teachers = array();
	foreach($user_id as $clau=>$valor){
		$id = $valor->userid;
		$user_array = get_records('user','id',$id);
		$teachers[$j] = $user_array[$id]->username;
		$j++;
	}
	return $teachers;
}


//----------------- FORM TREATMENT FUNCTIONS -------------------------------

//this function manages the wiki form contents
function wiki_form_treatment (&$WS){
	$action = optional_param('dfformaction',NULL,PARAM_ALPHA);
    if (isset($action)){
        //decide which action to perform
		switch ($action){
            case 'edit': //page form treatment
                wiki_edit_treatment($WS);
                break;
            case 'adddiscussion': //form treatment to add a new item in a discussion
                wiki_edit_treatment($WS);
                break;
            default: //wrong action
                break;
        }
    }
}

function wiki_edit_treatment(&$WS){

    global $USER,$CFG,$COURSE;

	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);

    //user wants to  save the document
    $save = optional_param('dfformsave',NULL,PARAM_ALPHA);
    if (isset($save)){
        $data->pagename = $WS->page;
		$version = optional_param('dfformversion',NULL,PARAM_INT);
        $data->version = $version+1;

        $txt = '';

        // discussions
        if (isset($WS->dfform['addtitle'])) {

            switch ($WS->dfform['editor']) {
                case 'ewiki':
                    $txt = chr(13).chr(10).'!!! '.$WS->dfform['addtitle'].' '.chr(13).chr(10);
                    break;
                case 'htmleditor':
                    $txt = chr(13).chr(10).'<h1> '.$WS->dfform['addtitle'].' </h1>'.chr(13).chr(10);
                    break;
                default:
                    // dfwiki or nwiki
                    $txt = chr(13).chr(10).'= '.$WS->dfform['addtitle'].' ='.chr(13).chr(10);
                    break;
            }
        }

        if (isset($WS->dfform['content'])) {
            if ($WS->dfform['editor'] == 'nwiki') {
                $section     = optional_param('section',   '', PARAM_TEXT);
                $sectionnum  = optional_param('sectionnum', 0, PARAM_INTEGER);
                if (($section == '') && ($sectionnum == 0)) { // main page
                    $restore = get_string('restore', 'wiki');

                    if (!wiki_is_locked_by_userid($WS, $WS->dfwiki->id, $data->pagename, $USER->id) &&
                        $save != $restore && // don't check if we are using history's restore method
                        substr($WS->page, 0, strlen('discussion:')) != 'discussion:')
                    {
                        error(get_string('pageoverrided', 'wiki').' '.$WS->page);
                    }
                    $txt = $txt.$WS->dfform['content'];
                    $txt = stripslashes($txt);
                } else {                                      // section
                    $section    = urldecode($section);
                    $newcontent = stripslashes($WS->dfform['content']);
                    $txt        = wiki_join_sections($WS->page, $section, $sectionnum, $newcontent);
                }
            } else
                $txt = $txt.$WS->dfform['content'];
        }

        if (isset($WS->dfform['oldcontent'])) {
            $txt = $WS->dfform['oldcontent'].$txt;
        }

        $data->author = $USER->username;
        $data->userid = $USER->id;
        $data->ownerid = $WS->member->id;
        $data->created = optional_param('dfformcreated',NULL,PARAM_INT);;
        $data->lastmodified = time();

        //get internal links of the page
        $links_refs  = wiki_sintax_find_internal_links($txt);
        $links_clean = wiki_clean_internal_links($links_refs);
        $txt         = wiki_set_clean_internal_links($txt, $links_refs, $links_clean);

        $data->content = addslashes($txt);
        $WS->content   = $txt;

        $data->refs = wiki_internal_link_to_string($links_refs);
		$data->editable = optional_param('dfformeditable',NULL,PARAM_BOOL);
        $data->hits = 0; //wiki_view_content will increase that number if necessary.

        $data->dfwiki = $WS->dfwiki->id;
        $data->editor = $WS->dfform['editor'];
        $WS->groupmember->groupid = isset($WS->gid)?$WS->gid:$WS->groupmember->groupid;
        $data->groupid = $WS->groupmember->groupid;
        //print_object('_______________'.$data->groupid);
		$definitive = optional_param('dfformdefinitive',NULL,PARAM_INT);
        if(isset($definitive)){
			$data->highlight = $definitive;
        	if($data->highlight!=1){
				$data->highlight = 0;
        	}
        }
        //Check if the version passed is the last one or another one.
        if (($max=wiki_page_current_version_number ($data,$WS))>=$data->version){
            notify ("WARNING: some version may be overwrited.", 'notifyproblem', $align='center');
            $data->version = $max+1;
        }

        ///Add some slashes before inserting the record
        $data->pagename = addslashes($data->pagename);

        $wikimanager = wiki_manager_get_instance();
        $page = new wiki_page(PAGERECORD, $data);
        if (!$pageid = $wikimanager->save_wiki_page($page)) {
            echo $WS->page;
            error(get_string('noinsert','wiki'));
        }

        add_to_log($COURSE->id,'wiki', "save page", addslashes("view.php?id={$WS->cm->id}&amp;page=$WS->page"), $pageid,$WS->cm->id);
    }

    //do a preview
    $preview = optional_param('dfformpreview',NULL,PARAM_ALPHA);
    if (isset($preview)){
        //set pageaction to edit
        $WS->pageaction = 'edit';
    }

    //cancell action
    $cancel = optional_param('dfformcancel',NULL,PARAM_ALPHA);
    if (isset($cancel)){
        //do nothing
    }

    // allow file uploading and deleting in sections also
    $sectionhash = optional_param('sectionhash',   '', PARAM_TEXT);
    if ($sectionhash != '')
        $pagename = $WS->page.'#'.$sectionhash;
    else
        $pagename = $WS->page;

    //look for uploaded files
    $upload = optional_param('dfformupload',NULL,PARAM_ALPHA);
    if (isset($upload)){
        if (!wiki_is_locked_by_userid($WS, $WS->dfwiki->id, $pagename, $USER->id))
        {
            if ($sectionhash == '') error(get_string('pageoverrided', 'wiki'));
            else                    error(get_string('sectionoverrided', 'wiki'));
        }
		require_capability('mod/wiki:uploadfiles', $context);
        wiki_upload_file('dfformfile',$WS);
        if ($WS->pageaction == 'view') {
            wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
            $WS->pageaction = 'edit';
		}
    }

    $wikitags = optional_param('wikitags', '', PARAM_TEXT);
    wiki_tags_save_tags($WS, $wikitags);

    //check for deleted file@@@@@@@@@
    $delfile = optional_param('dfformdelfile');
	if (!empty($delfile)){
        if (!wiki_is_locked_by_userid($WS, $WS->dfwiki->id, $pagename, $USER->id))
        {
            if ($sectionhash == '') error(get_string('pageoverrided', 'wiki'));
            else                    error(get_string('sectionoverrided', 'wiki'));
        }
		require_capability('mod/wiki:deletefiles', $context);
        if (isset ($WS->dfdir->content)){
            wiki_upload_del($delfile,$WS);
            if ($WS->pageaction== 'view') {
                wiki_release_lock($WS, $WS->dfwiki->id, $pagename);
                $WS->pageaction = 'edit';
            }
        }
    }

    //either cancell or save action was selected
    if (isset($cancel) or isset($save)){
        //need to close the window if we're working on a Wiki Book
        if(strrchr ($WS->dfwiki->name, "DFIT::")){
            echo '<script language="javascript"> window.opener.location.reload(); window.close(); </script>';
        }
    }

}

//----------------- INTERNAL FUNCTIONS ---------------------------

//this function prints a well formated page content

function wiki_print_page_content (&$text,&$WS){
    global $CFG;

    echo wiki_parse_content($text,$WS);
	// display attached files, if any :-) (nadavkav)
	if ($WS->upload_bar or ( $WS->dfperms['attach'] and count($WS->dfdir->content) != 0 ) ) {
		//Scritp WIKI_TREE
		$prop = null;
		$prop->type = 'text/javascript';
		if (isset($WS->dfcourse)){
			$prop->src = '../mod/wiki/editor/wiki_tree.js';
		}
		else{
			$prop->src = 'editor/wiki_tree.js';
		}
		wiki_script('', $prop);

		wiki_print_view_uploaded($WS);
	}

}

//this function prints a well formated page content
function wiki_parse_content ($text,&$WS){
    global $CFG;
    //$WS->wiki_format is the wiki parser configuration array.

    //configure wiki parse with the editor
    switch ($WS->pagedata->editor) {
        case 'dfwiki':
            //dfwiki parse is already configured for use
            //the dfwiki editor, logically.
            return wiki_sintax_html($text);
            break;

        case 'nwiki':
            //nwiki parse is already configured for use
            //the nwiki editor, logically.
            return parse_nwiki_text($text);
            break;

        case 'ewiki': //configure parse for emulate ewiki format.
            //del all format
            foreach ($WS->wiki_format as $key=>$type){
                unset($WS->wiki_format[$key]);
            }
            //put new parser params
            $WS->wiki_format['line'] = array (
                            '-----' => "<hr noshade>\n",
                            '----' => "<hr noshade>\n",
                            '---' => "<hr noshade>\n"
                            );
            $WS->wiki_format['start-end'] = array (
                                            "**" => array ("<b>","</b>"),
                                            "__" => array ("<b>","</b>"),
                                            "'''" => array ("<b>","</b>"),
                                            "''" => array ("<i>","</i>"),
                                            "ï¿½ï¿½" => array ("<BIG>","</BIG>"),
                                            "##" => array ("<SMALL>","</SMALL>"),
                                            "==" => array ("<tt>","</tt>")
                                        );
            $WS->wiki_format['line-start'] = array (
                                            " " => "&nbsp;",
                                            "    " => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
                                        );
            $WS->wiki_format['lists'] = array(
                                        '*'=> 'ul',
                                        '#'=> 'ol'
                                    );
            $WS->wiki_format['links'] = array (
                                        'internal' => array ("[[","]]"),
                                        'external' => array ("[","]")
                                    );
            $WS->wiki_format['table'] = array (
                                        '|' => 'td'
                                    );
            $WS->wiki_format['nowiki'] = array('<nowiki>','</nowiki>');
            $WS->wiki_format['line-start-enc'] = array (
                                                '!!!' => array ('<h1>','</h1>'),
                                                '!!' => array ('<h2>','</h2>'),
                                                '!' => array ('<h3>','</h3>')
                                            );
			return wiki_sintax_html($text);
            break;

        case 'htmleditor':
            //the parse will only recognize nowiki and links markup.
            $aux = isset($WS->wiki_format['links'])?$WS->wiki_format['links']:'';
            $aux2 = isset($WS->wiki_format['nowiki'])?$WS->wiki_format['nowiki']:'';
            foreach ($WS->wiki_format as $key=>$type){
                unset($WS->wiki_format[$key]);
            }
            //restore links and nowiki.
            $WS->wiki_format['links'] = $aux;
            $WS->wiki_format['nowiki'] = $aux2;
            return wiki_sintax_html($text);
			break;
        default:
			return wiki_sintax_html($text);
            break;
    }
}

/**
 * this function return the real name if it's a synonymous,
 * or the same pagename otherwise.
 * @param name: name of the page.
 * @param id: dfwiki instance id, current dfwiki default
 * @return String
 */

function wiki_get_real_pagename ($name,$id=false) {

	$wikiManager = wiki_manager_get_instance ();
	$res =  $wikiManager->wiki_get_real_pagename ($name,$id=false);
	if (!$res) return array();
	return $res;
	/*
    //set default $id value
    $id = ($id)? $id : $WS->dfwiki->id;

    //watch in synonymous
    if ($synonymous = get_record ('wiki_synonymous', 'syn', addslashes($name), 'dfwiki', $id)) {
        //if there's synonymous search for the original
        return $synonymous->original;
    }
	print_object($name);
    //if isn't a synonymous it will be an original or an uncreated page.
    return $name;*/
}
/**
 * this function trims any given text and returns it with some dots at the end
 * @param String $text: text to reduce
 * @param int $limit: size of String
 * @return String
 */
function trim_string($text,$limit){

    if(strlen($text)>$limit){
        $text = substr($text,0,$limit).'...';
    }

    return $text;
}

//this function return the size of the box dfwiki editor.
function wiki_get_editor_size (&$WS) {
    if (!isset($id)){
		$id = $WS->dfwiki->id;
    }
    $wikimanager = wiki_manager_get_instance();
    $wiki = $wikimanager->get_wiki_by_id($id);
    $info = new stdClass();
    $info->editorcols = $wiki->editorcols;
    $info->editorrows = $wiki->editorrows;
    return $info;
}


//this function search if a pagename is in an instance
//
//@ param name: name of the page.
//@ param syn=true: if you want to search in synonymous too.
//@ return true if there's a page or a synonimous in the dfwiki
function wiki_page_exists ($deprecated, $name, $syn = true, $wid=false) {

	$dfwiki = wiki_param('dfwiki');
	$groupmember = wiki_param('groupmember');
	$member = wiki_param('member');

	if (!$wid && isset($dfwiki->id)) {
        $wid = $dfwiki->id;
    }

    $wikimanager = wiki_manager_get_instance();
    if (isset($groupmember) && isset($member)) {
	    //watch for pagename in dfwiki
        $pageid = new wiki_pageid($wid, $name, null, $groupmember->groupid, $member->id);
        if ($wikimanager->page_exists($pageid)) {
			return true;
	    }
    }
    if ($syn && isset($groupmember) && isset($member)) {
        //watch in synonymous
        if ($synonym = $wikimanager->wiki_get_real_pagename($name, $wid, $groupmember->groupid, $member->id)) {
            //if there's synonymous search for the original
            return wiki_page_exists (false,$synonym,false);
        }
    }

    return false;

}

//this function search if a pagename has a discusion page
//
//@ param name: name of the page.
//@ param syn=true: if you want to search in synonymous too.
//@ return true if there's a discusion page or a synonimous in the dfwiki
function wiki_discussion_page_exists ($deprecated, $name, $syn = true, $wid=false) {
    return wiki_page_exists(false, 'discussion:' . $name, $syn, $wid);
}

/**
 * this function return an array with the parse of internal links string from DB
 * @param String $links: references of page
 * @return Array
 */
function wiki_internal_link_to_array($links){
    $res = explode ('|',$links);
    if (count($res)==1 && $res[0]==''){
        $res = array();
    }
    return $res;
}

//convert internal links array into a compatible string with DB
function wiki_internal_link_to_string($links){
    $res = '';

    $first=true;
    foreach ($links as $link){
        if ($first){
            $res.=$link;
            $first=false;
        }else{
            $res.='|'.$link;
        }
    }
    return addslashes($res);
}

/**
 * this function returns last version page information
 * @param String $page: pagename
 * @return wikipage or false
 */
function wiki_page_last_version ($page){
    global $CFG;

	$wikiManager = wiki_manager_get_instance ();

	if (!$dfwiki = wiki_param ('dfwiki')) return false;
	if (!$groupmember = wiki_param ('groupmember')) return false;
	$member = wiki_param ('member');

	$wiki = $wikiManager->get_wiki_by_id($dfwiki->id);
	if (!$wiki) return false;

	$cm = get_coursemodule_from_instance('wiki',$dfwiki->id);
    $res = null;
	if(($dfwiki->studentmode == '0') && ($cm->groupmode != '0')){
        //only by groups
        $res =  $wikiManager->get_wiki_page_by_pagename ($wiki,$page,false,$groupmember->groupid);
    }else{
        //by students and their groups
        $res =  $wikiManager->get_wiki_page_by_pagename ($wiki,$page,false,$groupmember->groupid,$member->id);
    }
    return $res;

    //get the latest page version number
    /*if(($WS->dfwiki->studentmode == '0') && ($WS->dfwiki->groupmode != '0')){
        //only by groups
        $max = wiki_get_maximum_value_one($page,$WS->dfwiki->id,$WS->groupmember->groupid);
    }else{
        //by students and their groups
        $max = wiki_get_maximum_value_two($page,$WS->dfwiki->id,$WS->groupmember->groupid,$WS->member->id);
    }

    //load page data
    if ($max){
        if(($WS->dfwiki->studentmode == '0') && ($WS->dfwiki->groupmode != '0')){
            //only by groups
            return wiki_get_latest_page_version_one($page,$WS->dfwiki->id,$max,$WS->groupmember->groupid);
         }else{
            //by students and their groups
            return wiki_get_latest_page_version_two($page,$WS->dfwiki->id,$max,$WS->groupmember->groupid,$WS->member->id);
        }
    } else {
        return false;
    }*/
}

//this function returns a linkable information of a user with it's image
function wiki_get_user_info ($user, $size=0, $puttext=true){
    global $CFG,$COURSE;

    $wikimanager = wiki_manager_get_instance();
    $info = $wikimanager->get_user_info($user);
    //get user id and image
    if ($info){
        $picture = print_user_picture($info->id, $COURSE->id, $info->picture, $size, true,true);
        if ($puttext){
            $text = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$info->id.'">'.fullname($info).'</a>';
        } else {
            $text = '';
        }

    }else{
        $text = '<u>'.$user.'</u>';
        $picture = '';
    }

    //build url
    $res = $text.' '.$picture;
    return $res;
}

//this function return the current version number of a page
function wiki_page_current_version_number ($page,&$WS){
    global $CFG;

    if(($WS->dfwiki->studentmode == '0') && ($WS->cm->groupmode != '0')){
        //only by groups
        $max = wiki_get_maximum_value_one($page, $WS->dfwiki->id, $WS->groupmember->groupid);
    } else{
        //by students and their groups
		$max = wiki_get_maximum_value_two($page->pagename,$WS->dfwiki->id,$WS->groupmember->groupid, $WS->member->id);
    }

    //return the max version
    if ($max){
        return $max;
    } else {
        return false;
    }
}


/**
 * This function checks if the page exists and logs the action.
 *
 * Usually it will be used like this:
 *    add_action_to_log();
 */

function add_action_to_log(){
	global $WS, $COURSE;

    $logaction = true;

    $wikimanager = wiki_manager_get_instance();

	//Check the page exists to log the action
    if(($WS->dfwiki->studentmode == '0') && ($WS->cm->groupmode != '0')){
        //only by groups
        $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page, null,
            $WS->groupmember->groupid);
        if ($wikimanager->page_exists($pageid)) {
            $logaction = false;
        }
    } else{
        //by students and their groups
        $pageid = new wiki_pageid($WS->dfwiki->id, $WS->page, null,
            $WS->groupmember->groupid, $WS->member->id);
        if ($wikimanager->page_exists($pageid)) {
            $logaction = false;
        }
    }

    if ($logaction) {
        add_to_log($COURSE->id, 'wiki', "$WS->pageaction page",
		           addslashes("view.php?id={$WS->cm->id}&amp;page=$WS->page"),
				   $WS->dfwiki->id,
				   $WS->cm->id);
    }

}

//------- GROUP MODE MENUS --------------

function wiki_students_in_a_group($groupid, $listgroupsmembers){
//giving a group and a students group list
//it's returns a student list of this group (an objet array)
    foreach ($listgroupsmembers as $key => $lstudentsinagroup){
        if($lstudentsinagroup->groupid == $groupid){
            $liststudentsinagroup[$key] = (object) $lstudentsinagroup;
        }
    }
    return $liststudentsinagroup;
}

function wiki_print_menu_groups($listgroups, $groupid, $cm, &$WS){
//this function prints a groups list in the groupmode menu
    global $USER, $COURSE;
	if(empty($listgroups)){
        return;
    }

    $wikimanager = wiki_manager_get_instance();
    $wikipersistor = new wiki_persistor();

    $context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
	//if((($WS->dfwiki->groupmode == 1) && ($WS->cm->studentmode == 0))
    if (((isset($WS->cm->groupmode) && $WS->cm->groupmode == 1)) && ($WS->dfwiki->studentmode == 0)
        && !((has_capability('mod/wiki:editanywiki',$context)))) {
           $usergroupids = $wikipersistor->get_groupid_by_userid_and_courseid($USER->id, $COURSE->id);
           $userid = array();
           foreach ($usergroupids as $usergroup){
           	  $userid[] = $usergroup->groupid;
           }
           //$listgroups = $wikimanager->get_course_groups($COURSE->id);
        	foreach ($listgroups as $key => $group) {
                    if (!in_array($group->id,$userid)) {
                        unset($listgroups[$key]);
                    }
            }

    }

    $prop = null;
    $prop->border = "0";
    $prop->class = "boxalignright";
    $prop->classtd = "nwikileftnow";
    wiki_table_start($prop);
		if (!empty($groupid)){
			echo get_string('viewpagegroup','wiki') . $listgroups[$groupid]->name;
			$property->align = 'right';
			wiki_change_row($property);
		}
    	print_string('anothergroup','wiki');
    	wiki_change_column();
    	$prop = null;
    	$prop->id = "sel_group";
    	$prop->method = "post";
    	$prop->action = 'view.php?id='.$cm->id;
    	wiki_form_start($prop);

    		wiki_div_start();
				$opt = '';
	            foreach ($listgroups as $lgroup){
	                		if ($lgroup->id!=$groupid){
	                			$prop = null;
	                			$prop->value = $lgroup->id;
	                			$opt .= wiki_option($lgroup->name, $prop, true);
	                }
	            }
				if(has_capability('mod/wiki:editawiki',get_context_instance(CONTEXT_MODULE, $cm->id)) and $groupid != 0){
					$prop = null;
					$prop->value = 0;
	                $opt .= wiki_option($USER->username, $prop, true);
				}
    			$prop = null;
    			$prop->name = "dfformselectgroup";
    			wiki_select($opt,$prop);

    			$prop = null;
    			$prop->value = get_string("continue");
    			wiki_input_submit($prop);

    		wiki_div_end();

    	wiki_form_end();

    wiki_table_end();
}

function wiki_print_menu_students($listgroupsmembers, $userid, $cm){
//this function prints all the students in a course

    if (empty($listgroupsmembers)){
        return;
    }

    $prop = null;
    $prop->border = "0";
    $prop->class = "boxalignright";
    $prop->classtd = "nwikileftnow";
    wiki_table_start($prop);
    if (!empty($userid)){
			echo get_string('viewpagestudent','wiki') . $listgroupsmembers[$userid]->lastname.', '.$listgroupsmembers[$userid]->firstname;
			wiki_table_end();
			$prop = null;
		    $prop->border = "0";
		    $prop->class = "boxalignright";
		    $prop->classtd = "nwikileftnow";
		    wiki_table_start($prop);
		}

    	print_string('anotherstudent','wiki');
    	wiki_change_column();
    	$prop = null;
    	$prop->id = "sel_student";
    	$prop->method = "post";
    	$prop->action = 'view.php?id='.$cm->id;
    	wiki_form_start($prop);

    		wiki_div_start();

				$opt = '';
	            foreach ($listgroupsmembers as $lmembers){
	                		if ($lmembers->id!=$userid){
	                			$prop = null;
	                			$prop->value = $lmembers->id;
	                			$opt .= wiki_option($lmembers->lastname.', '.$lmembers->firstname, $prop, true);
	                }
	            }

    			$prop = null;
    			$prop->name = "dfformselectstudent";
    			wiki_select($opt,$prop);

    			$prop = null;
    			$prop->value = get_string("continue");
    			wiki_input_submit($prop);

    		wiki_div_end();

    	wiki_form_end();

    wiki_table_end();
}

function wiki_print_menu_students_in_group($listgroupsmembers, $groupid, $userid, $cm){
//this function prints the students in a group
    if(empty($listgroupsmembers)){
        return;
    }
    $liststudentsingroup = wiki_students_in_a_group($groupid, $listgroupsmembers);

    $prop = null;
    $prop->border = "0";
    $prop->class = "boxalignright";
    $prop->classtd = "nwikileftnow";
    wiki_table_start($prop);
		if (!empty($groupid)){
			echo get_string('viewpagegroup','wiki') . $listgroupsmembers[$groupid]->lastname.', '.$listgroupsmembers[$groupid]->firstname;
			wiki_table_end();
			$prop = null;
		    $prop->border = "0";
		    $prop->class = "boxalignright";
		    $prop->classtd = "nwikileftnow";
		    wiki_table_start($prop);
		}
    	print_string('anotherstudent','wiki');
    	wiki_change_column();
    	$prop = null;
    	$prop->id = "sel_student";
    	$prop->method = "post";
    	$prop->action = 'view.php?id='.$cm->id;
    	wiki_form_start($prop);

    		wiki_div_start();

				$opt = '';
	            foreach ($liststudentsingroup as $lmembers){
	                		if ($lmembers->id!=$userid){
	                			$prop = null;
	                			$prop->value = $lmembers->id;
	                			$opt .= wiki_option($lmembers->lastname.', '.$lmembers->firstname, $prop, true);
	                }
	            }

    			$prop = null;
    			$prop->name = "dfformselectstudent";
    			wiki_select($opt,$prop);

				$prop = null;
				$prop->name = "dfformselectgroup";
    			$prop->value = $groupid;
    			wiki_input_hidden($prop);

    			$prop = null;
    			$prop->value = get_string("continue");
    			wiki_input_submit($prop);

    		wiki_div_end();

    	wiki_form_end();

    wiki_table_end();
}

function wiki_print_menu_groups_and_students($listgroups, $listgroupsmembers, &$WS){
//this function prints the groupmode menu with the students
    global $CFG, $COURSE, $USER;
    if(empty($listgroupsmembers)){
        return;
    }

    $wikimanager = wiki_manager_get_instance();
    $wikipersistor = new wiki_persistor();
    //if we are in visible groups with separate students
    //and the user is not a teacher or admin
    //then we don't take his group in the menu list
    $context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);

    //if((($WS->dfwiki->groupmode == 1) && ($WS->cm->studentmode == 2))
    if (((isset($WS->cm->groupmode) && $WS->cm->groupmode == 1)) && ($WS->dfwiki->studentmode > 0)
        && !((has_capability('mod/wiki:editanywiki',$context)))){
        $usergroupids = $wikipersistor->get_groupid_by_userid_and_courseid($USER->id, $COURSE->id);
           $userid = array();
           foreach ($usergroupids as $usergroup){
           	  $userid[] = $usergroup->groupid;
           }
        	foreach ($listgroups as $key => $group) {
                    if (!in_array($group->id,$userid)) {
                        unset($listgroups[$key]);
                    }
            }
    }

    if (((isset($WS->cm->groupmode) && $WS->cm->groupmode == 1)) && ($WS->dfwiki->studentmode == 1)
        && !((has_capability('mod/wiki:editanywiki',$context)))){
            foreach ($listgroupsmembers as $key => $member) {
            	foreach ($usergroupids as $usergroup) {
                    if (($member->groupid == $usergroup->groupid) && ($member->id!=$USER->id)) {
                        unset($listgroupsmembers[$key]);
                    }
            	}
            }
    }

    //if((($WS->dfwiki->groupmode == 2) && ($WS->cm->studentmode == 1))
    if (((isset($WS->cm->groupmode) && $WS->cm->groupmode == 2)) && ($WS->dfwiki->studentmode == 1)
        && !((has_capability('mod/wiki:editanywiki',$context)))){
            //the user group
            $usergroupids = $wikipersistor->get_groupid_by_userid_and_courseid($USER->id, $COURSE->id);
            $listgroups = $wikimanager->get_course_groups($COURSE->id);

            //Course students list without the user group
            //We take the groups, and id, firstname and lastname of the students
            //$listgroupmembers = $wikimanager->get_course_members($COURSE->id);
            foreach ($listgroupsmembers as $key => $member) {
            	foreach ($usergroupids as $usergroup) {
                    if (($member->groupid == $usergroup->groupid) && ($member->id!=$USER->id)) {
                        unset($listgroupsmembers[$key]);
                        //break;
                    }
            	}
            }

    }

	$cm->id = isset($WS->dfcourse)?$COURSE->id:$WS->cm->id;

	$prop = null;
	$prop->type = "text/javascript";
	$info = '  //<![CDATA[

    function wiki_select_group(groups, groupstudent){
            var i=0;
            var j=0;
            var k=0;
            var numstudents=0 ;
            var numgroups=0;

            numgroups = groups.length;
            for(i=0; i<numgroups; i++){
                numstudents = groupstudent[i].length;
                for(j=0; j<numstudents; j++){
                    if((document.forms[\'form_sel_group\'].selectstudent.options[k].selected)&&(document.forms[\'form_sel_group\'].selectstudent.options[k].value == groupstudent[i][j])){
                            document.forms[\'form_sel_group\'].selectgroup.value = groups[i];
                    }
                k++;
            }
        }
    }

    var groups = new Array();';

        $ind=0;
        foreach ($listgroups as $lgroup){
            $info .= "
            groups[$ind] = '$lgroup->id';";
            $ind++;
        }
		if(has_capability('mod/wiki:editawiki',get_context_instance(CONTEXT_MODULE, $cm->id))){
			$info .= "
			groups[$ind] = '0';";
		}else{
			if(isset($usergroupids)){
				foreach($usergroupids as $usergroup){
				   $info .= "
				   groups[$ind] = '$usergroup->groupid';";
				}
			}
		}
    $info .= '
    var groupstudent = new Array();

	//]]>';

    wiki_script($info,$prop);

    $prop = null;
    $prop->border = "0";
    $prop->class = "boxalignright";
    $prop->classtd = "nwikileftnow";
    wiki_table_start($prop);
	$group = get_field('groups_members','id','groupid',$WS->groupmember->groupid,'userid',$WS->member->id);
		if (!empty($group) and (isset($listgroupsmembers[$group]))){
			echo get_string('viewpagestudent','wiki') . $listgroupsmembers[$group]->lastname.', '.$listgroupsmembers[$group]->firstname;
			wiki_table_end();
			$prop = null;
		    $prop->border = "0";
		    $prop->class = "boxalignright";
		    $prop->classtd = "nwikileftnow";
		    wiki_table_start($prop);
		}
	    print_string('anotherstudent','wiki');
	    wiki_change_column();
	    $prop = null;
	    $prop->id = "form_sel_group";
	    $prop->method = "post";
	    $prop->action = 'view.php?id='.$cm->id;
	    wiki_form_start($prop);

    		wiki_div_start();

            	$i=0;
	    		$opt = null;
	    		$opt2 = null;
	            foreach ($listgroups as $lgroup){
	                $liststudentsingroup[$i] = wiki_students_in_a_group($lgroup->id, $listgroupsmembers);
	                $j=0;

					$prop = null;
					$prop->type = "text/javascript";
					$info = "groupstudent[$i] = new Array();";
					wiki_script($info,$prop);

                        foreach ($liststudentsingroup[$i] as $lstudentsingroup){
		                    $prop = null;
		                    $prop->value = $lstudentsingroup->id;
		                    $opt .= wiki_option($lstudentsingroup->lastname.', '.$lstudentsingroup->firstname,$prop,true);

		                   	$prop = null;
							$prop->type = "text/javascript";
							$info = "groupstudent[$i][$j]= '$lstudentsingroup->id';";
							wiki_script($info,$prop);

	                        $j++;
                        }

					if ($WS->cm->groupmode !=0){
						$prop = null;
						$prop->label = $lgroup->name;
						$opt2 .= wiki_optgroup($opt,$prop,true);
						$opt = null;
                    }
                	$i++;
            	}

				if(has_capability('mod/wiki:editawiki',get_context_instance(CONTEXT_MODULE, $cm->id))){
		            $prop = null;
					$prop->value = $USER->id;
					$opt .= wiki_option($USER->lastname.', '.$USER->firstname,$prop,true);

                   	$prop = null;
					$prop->type = "text/javascript";
					$info = "groupstudent[$i] = new Array(); groupstudent[$i][0] = $USER->id;";
					wiki_script($info,$prop);

					$prop = null;
					$prop->label = "teacher";
					$opt2 .= wiki_optgroup($opt,$prop,true);
					$opt = null;
            	}else{
					if(isset($usergroupid)){
						$prop = null;
						$prop->value = $USER->id;
						$opt .= wiki_option($USER->lastname.', '.$USER->firstname,$prop,true);

	                   	$prop = null;
						$prop->type = "text/javascript";
						$info = "groupstudent[$i] = new Array(); groupstudent[$i][0] = $USER->id;";
						wiki_script($info,$prop);

						$prop = null;
						$prop->label = "user";
						$opt2 .= wiki_optgroup($opt,$prop,true);
						$opt = null;
	            	}
            	}
				$prop = null;
				$prop->id = "selectstudent";
				$prop->name = "dfformselectstudent";
				$prop->events = 'onchange="javascript:wiki_select_group(groups, groupstudent)"';
				wiki_select($opt2,$prop);

				$prop = null;
				$prop->id = "selectgroup";
				$prop->name = "dfformselectgroup";
	    		wiki_input_hidden($prop);

	    		$prop = null;
				$prop->type = "text/javascript";
				$info = "this.wiki_select_group(groups, groupstudent)";
				wiki_script($info,$prop);

	    		$prop = null;
	    		$prop->value = get_string("continue");
	    		wiki_input_submit($prop);

	    	wiki_div_end();

	    wiki_form_end();

	wiki_table_end();
}

function wiki_print_menu_teachers($listteachers, $cm){
//this function prints the list of theachers in the wiki's student

    if(empty($listteachers)){
        return;
    }

	wiki_br(2);
	$prop = null;
    $prop->border = "0";
    $prop->class = "boxalignright";
    $prop->classtd = "nwikileftnow";
    wiki_table_start($prop);

	    print_string('anotherteacher','wiki');
	    wiki_change_column();
	    $prop = null;
	    $prop->id = "selectteacher";
	    $prop->method = "post";
	    $prop->action = 'view.php?id='.$cm->id;
	    wiki_form_start($prop);

			wiki_div_start();

				$opt = null;
            	foreach ($listteachers as $lteacher){
					$prop = null;
	                $prop->value = $lteacher->id;
	               	$opt .= wiki_option($lteacher->lastname.', '.$lteacher->firstname,$prop,true);
            	}

            	$prop = null;
				$prop->name = "dfformselectteacher";
				wiki_select($opt,$prop);

				$prop = null;
	    		$prop->value = get_string("continue");
	    		wiki_input_submit($prop);

	    	wiki_div_end();

	    wiki_form_end();
		$prop = null;
	    $prop->class = "nwikileftnow";
	    wiki_change_row($prop);
	    echo "&nbsp;";

	wiki_table_end();
}

//-------------------------------- PERMISSIONS FUNCTIONS ---------------------------------

//this function loads permissions flags into $dfperms
function wiki_load_permissions(&$WS){
    $WS->dfperms['edit'] = wiki_can_edit($WS);
    $WS->dfperms['attach'] = wiki_can_do($WS->dfwiki->attach,$WS);
    $WS->dfperms['restore'] = wiki_can_do($WS->dfwiki->restore,$WS);
    $WS->dfperms['discuss'] =  wiki_can_discuss($WS);
	$WS->dfperms['editanothergroup'] = wiki_can_edit($WS);
	$WS->dfperms['editanotherstudent'] = wiki_can_edit($WS);
	$WS->dfperms['listofteachers'] = wiki_can_do($WS->dfwiki->listofteachers,$WS);
}

//this function determines if the current user can edit
function wiki_can_edit(&$WS){
    global $USER,$CFG,$COURSE;

	$can_edit = false;
    $student_can_edit = false;

	//if it's admin or teacher he always can edit.
	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
    if (has_capability('mod/wiki:editanywiki',$context) or
       ((isset($WS->dfwiki->groupmode) && !$WS->dfwiki->groupmode) and has_capability('mod/wiki:editawiki',$context))) {
        $can_edit = true;
    }
    else if (has_capability('mod/wiki:caneditawiki',$context)){
	        //watch if it's editable by students
	        if ($info = wiki_page_last_version ($WS->page,$WS)){
	            if ($info->editable){
	                $can_edit = true;
	                $student_can_edit = true;
	            }
	        } else {
	            if ($WS->dfwiki->editable){
	                $can_edit = true;
	                $student_can_edit = true;
	            }
	        }

            //watch if it's editable by another group
            if(($student_can_edit == true) && ($WS->dfwiki->editanothergroup == 0)
               && (isset ($WS->dfwiki->groupmode) && ($WS->dfwiki->groupmode != 0))) {
				$usergroups = get_records_sql('SELECT gm.groupid
						FROM '. $CFG->prefix.'groups g,
							 '. $CFG->prefix.'groups_members gm
						WHERE gm.userid=\''.$USER->id.'\'
							  AND gm.groupid=g.id
							  AND g.courseid=\''.$COURSE->id.'\'');

				$can_edit = false;
                if (isset($usergroups))
                {
                	foreach($usergroups as $usergroup){
                		if($usergroup->groupid == $WS->groupmember->groupid)
                		    $can_edit = true;
                	}
                }
			}

			//watch if it's editable by another student
			if (($student_can_edit == true) &&
                ($WS->dfwiki->editanotherstudent == 0) &&
                ($USER->id != $WS->member->id) &&
                ($WS->dfwiki->studentmode != 0))
            {
				$can_edit = false;
			}
    }
    return $can_edit;
}

//this function determine a permission
function wiki_can_do($permission,&$WS){

	$can_res = false;

    //if it's admin or teacher he always can edit.
   	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
	if (has_capability('mod/wiki:editanywiki',$context) or (!$WS->cm->groupmode and has_capability('mod/wiki:editawiki',$context))) {
        $can_res = true;
    }else{
        //watch if it's accessible for students
        if ($permission && has_capability('mod/wiki:caneditawiki',$context)){
            $can_res = true;
        }
        //@todo: group comprobations.
    }
    return $can_res;
}

//this function determine if the current user can change the wiki (del, rename...)
function wiki_can_change($deprecated=false){
	$cm = wiki_param ('cm');
	$dfwiki = wiki_param ('dfwiki');
	$context = get_context_instance(CONTEXT_MODULE,$cm->id);
	if (has_capability('mod/wiki:editanywiki',$context) or (!$cm->groupmode and has_capability('mod/wiki:editawiki',$context))) {
        return true;
    }else{
		return false;
	}
}

//this function determine if the current user can edit a discussion
function wiki_can_discuss(&$WS) {
        global $USER,$COURSE;

        $can_res = false;
        //if it's admin he always can edit
        $context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
		if (has_capability('mod/wiki:editanywiki',$context) or (has_capability('mod/wiki:editawiki',$context) and $WS->dfwiki->studentdiscussion) or ($WS->dfwiki->studentdiscussion and has_capability('mod/wiki:caneditawiki',$context))) {
             $can_res = true;
        }
        return $can_res;
}

function wiki_dfform_param(&$WS){

	// The comented global variables have been eliminated, but need more tests.

	$WS->dfform = optional_param('dfform',NULL,PARAM_FILE);
	$WS->dfform['addtitle'] = optional_param('dfformaddtitle',NULL,PARAM_RAW);
	$WS->dfform['but'] = optional_param('dfformbut',NULL,PARAM_ALPHA);
	$WS->dfform['content'] = optional_param('dfformcontent',NULL,PARAM_RAW);
	$WS->dfform['editor'] = optional_param('dfformeditor',NULL,PARAM_ALPHA);
	$WS->dfform['field'] = optional_param('dfformfield',NULL,PARAM_FILE);
	$WS->dfform['import'] = optional_param('dfformimport',NULL,PARAM_ALPHA);
	$WS->dfform['incase'] = optional_param('dfformincase',NULL,PARAM_INT);
	$WS->dfform['incaseatach'] = optional_param('dfformincaseatach',NULL,PARAM_INT);
	$WS->dfform['main'] = optional_param('dfformmain',NULL,PARAM_ALPHA);
	$WS->dfform['oldcontent'] = optional_param('dfformoldcontent',NULL,PARAM_RAW);
	$WS->dfform['result'] = optional_param('dfformresult',NULL,PARAM_ALPHA);
	$WS->dfform['selectedit'] = optional_param('dfformselectedit',NULL,PARAM_INT);
	$WS->dfform['selectgroup'] = optional_param('dfformselectgroup',NULL,PARAM_INT);
    $WS->dfform['selectstudent'] = optional_param('dfformselectstudent',NULL,PARAM_INT);
    $WS->dfform['selectteacher'] = optional_param('dfformselectteacher',NULL,PARAM_INT);
}

//this function returns a formatted name tag to display
function wiki_get_pagename_from_id(&$WS){

    $comarray = $_SESSION['itinerary']['pagecommentary'];
    $topicarray = $_SESSION['itinerary']['pagetopic'];
    $pos = array_search($WS->page, $comarray);
    $page = $topicarray[$pos];

    return get_string('commentaries','itinerary').': '.$page;
}

//from here there are all functions which access the data base
/**
 * returns the maximum version of a page associated with a wiki and a groupid
 * @param Object $page
 * @param int $dfwikiid
 * @param int $groupid
 * @return int
 */
function wiki_get_maximum_value_one($page,$dfwikiid,$groupid){
    $wikiManager = wiki_manager_get_instance ();
	//$res =  $wikiManager->get_maximum_value_one ($pagename,$dfwikiid,$groupid);
	//$res =  $wikiManager->get_last_wiki_page_version($pagename ,$groupid,0);
	$res =  $wikiManager->get_last_wiki_page_version($page ,$groupid,0);
    return $res;
}

/**
 * returns the maximum version of a page associated with a wiki and a groupid and memberid
 * @param String $pagename
 * @param int $dfwikiid
 * @param int $groupid
 * @param int $memberid
 * @return int
 */
function wiki_get_maximum_value_two($pagename,$dfwikiid,$groupid,$memberid){
    $wikiManager = wiki_manager_get_instance ();
	$res =  $wikiManager->get_maximum_value_two($pagename,$dfwikiid,$groupid,$memberid);
	//$res =  $wikiManager->get_last_wiki_page_version($pagename ,$groupid,$memberid);
    return $res;
}

/**
 * returns a page version using groups
 * @param String $pagename
 * @param int $dfwikiid
 * @param int $max version number
 * @param int $group id
 */
function wiki_get_latest_page_version_one($pagename,$dfwikiid,$max,$groupid){
    $wikimanager = wiki_manager_get_instance();
    return $wikimanager->get_wiki_page_by_pagename($dfwikiid, $pagename, $max,
        $groupid);
}

function wiki_get_latest_page_version_two($pagename,$dfwikiid,$max,$groupid,$memberid){
    $wikimanager = wiki_manager_get_instance();
    $wiki = $wikimanager->get_wiki_by_id($dfwikiid);
    return $wikimanager->get_wiki_page_by_pagename($wiki, $pagename, $max,
        $groupid, $memberid);
}

function wiki_get_all_page_versions($prefix,$pagename,$dfwikiid,$groupid,$memberid){
    $pageid = new wiki_pageid($dfwikiid, $pagename, null, $groupid, $memberid);
    $wikimanager = wiki_manager_get_instance();
    $page = $wikimanager->get_wiki_page_by_pageid($pageid);
    return $wikimanager->get_wiki_page_historic($page);
}

//This function returns a certain version of a page
function wiki_get_page_version($prefix,$pagename,$dfwikiid,$groupid,$memberid,$version){
    $pageid = new wiki_pageid($dfwikiid, $pagename, $version, $groupid, $memberid);
    $wikimanager = wiki_manager_get_instance();
    return $wikimanager->get_wiki_page_by_pageid($pageid);
}

//This function is similar to the get_record() function, but it's using four values
function wiki_get_record($table, $field1, $value1, $field2='', $value2='', $field3='', $value3='', $field4='', $value4='',$fields='*') {
    global $CFG ;
    $select = 'WHERE '. $field1 .' = \''. $value1 .'\'';
    if ($field2) {
        $select .= ' AND '. $field2 .' = \''. $value2 .'\'';
        if ($field3) {
            $select .= ' AND '. $field3 .' = \''. $value3 .'\'';
            if ($field4) {
               $select .= ' AND '. $field4 .' = \''. $value4 .'\'';
            }
        }
    }
    return get_record_sql('SELECT '.$fields.' FROM '. $CFG->prefix . $table .' '. $select);
}

/**
 * Substitutes the internal links of a page content for the clean
 * ones.
 *
 * @param String $content     Content page
 * @param Array  $links_refs  Dirty links
 * @param Array  $links_clean Clean links
 *
 * @return String Content with the clean links instead the dirty ones
 */

function wiki_set_clean_internal_links($content, $links_refs, $links_clean)
{
    for ($i = 0; $i < count($links_refs); $i++)
    {
        if ($links_refs[$i] != $links_clean[$i]) {
            $content = str_replace($links_refs[$i], $links_clean[$i], $content);
            notify ("WARNING: The name of the internal link "."(".stripslashes($links_refs[$i]).")".
                    " has been modified as "."(".stripslashes($links_clean[$i]).")",
                    'green', $align='center');
        }
    }
    return $content;
}

/**
 * Returns an array with all links cleaned.
 *
 * @param  Array $links_refs Dirty links
 * @return Array             Clean links
 */
function wiki_clean_internal_links($links_refs)
{
    $links_aux = array();
    foreach ($links_refs as $link) {
        $link_temp = wiki_clean_name($link);
        if ($link_temp != $link)
            $link = $link_temp;
        $links_aux[] = $link;
    }
    return $links_aux;
}

/**
 * Cleans a page name changing the problematic characters.
 *
 * @param  String $name Page name, section name or page+section name
 * @return String       Clean name
 */
function wiki_clean_name($name) {
    if (wiki_is_section_link($name))
        return wiki_clean_section_link($name);

    // list of forbidden characters in page names
    $name = str_replace('/',  '_', $name);
    $name = str_replace('\\', '_', $name);
    $name = str_replace('<',  '_', $name);
    $name = str_replace('>',  '_', $name);

    return $name;
}

/**
 * Cleans a link to a section of a page, which
 * means that only the part of the page name is
 * cleaned.
 *
 * @param  String $name Link to section of a page
 * @return String       Clean name.
 */
function wiki_clean_section_link($name) {
    if (ereg('(.*)+(##)(.*)+', $name))
        $at = '##';
    else
        $at = '#';

    $parts       = explode($at, $name, 2);
    $pagename    = wiki_clean_name($parts[0]);
    $sectionname = $parts[1];

    return $pagename.$at.$sectionname;
}

/**
 * Obtains an editing lock on a wiki page or section of a page
 * and returns its id, or null if it's already locked and not
 * locked by the current user.
 *
 * @param  Object $WS       WikiStorage
 * @param  int    $wikiid   ID of wiki object.
 * @param  string $pagename Name of page or section
 * @return Object           An object with the lock id
 */
function wiki_obtain_lock($WS, $wikiid, $pagename) {
    global $USER;

    $groupid = $WS->groupmember->groupid;
    $ownerid = $WS->member->id;

    $pagename = $groupid.'_'.$ownerid.'_'.addslashes($pagename);

    // Check for lock
    $alreadyownlock = false;

    $lock = wiki_get_record('wiki_locks', 'pagename', $pagename, 'wikiid',  $wikiid,
                                          'groupid',  $groupid,  'ownerid', $ownerid);
    if ($lock) {
        if($lock->lockedby == $USER->id) {
            // it's our lock, do nothing except mantain it in session
            $lockid = $lock->id;
        } else // it's not our lock
            return null;
    } else {
        // construct a new lock
        $newlock = new stdClass;
        $newlock->lockedby    = $USER->id;
        $newlock->lockedsince = time();
        $newlock->lockedseen  = $newlock->lockedsince;
        $newlock->wikiid      = $wikiid;
        $newlock->pagename    = $pagename;
        $newlock->groupid     = $WS->groupmember->groupid;
        $newlock->ownerid     = $WS->member->id;

        if (!$lockid = insert_record('wiki_locks', $newlock)) {
            error('Unable to insert lock record');
        }
    }

    // Store lock information in session so we can clear it later
    if(!array_key_exists(SESSION_WIKI_LOCKS,$_SESSION)) {
        $_SESSION[SESSION_WIKI_LOCKS] = array();
    }
    $_SESSION[SESSION_WIKI_LOCKS][$wikiid.'_'.$pagename]=$lockid;

    return $lockid;
}

/**
 * Returns an object with lock information: the name who locked it
 * (if the user have the rights), when was it locked and as for it's
 * locked.
 *
 * @param  Object $WS   WikiStorage
 * @param  Object $lock Lock database data
 * @param  String $page PageName or PageName#SectionHash
 * @param  String $by   Locked by (page/section)
 * @return Object       Lock information
 */
function wiki_get_lock_info($WS, $lock, $page, $by)
{
    global $USER;

    $modcontext  = get_context_instance(CONTEXT_MODULE, $WS->cm->id);

    $a        = new stdClass;
    $a->since = userdate($lock->lockedsince);
    $a->seen  = userdate($lock->lockedseen);
    $user     = get_record('user', 'id', $lock->lockedby);
    $a->name  = fullname($user, has_capability('moodle/site:viewfullnames', $modcontext));

    if ($lock->lockedby == $USER->id)
        $a->name .= ' ('.get_string('yourself').')';

    return $a;
}

/**
 * Returns null if the page / page#section is not locked, or the
 * lock object it's locked
 *
 * @param  Object  $WS        WikiStorage
 * @param  Integer $wikiid    Wiki id
 * @param  String  $pagename  PageName or PageName#SectionName
 * @return Object             lock
 */
function wiki_is_locked($WS, $wikiid, $pagename) {
    global $USER;

    $groupid = $WS->groupmember->groupid;
    $ownerid = $WS->member->id;

    $pagename = $groupid.'_'.$ownerid.'_'.addslashes($pagename);


    // Check for lock
    $lock = wiki_get_record('wiki_locks', 'pagename', $pagename, 'wikiid',  $wikiid,
                                          'groupid',  $groupid,  'ownerid', $ownerid);
    if ($lock) {
        // Consider the page locked if the lock has been confirmed within WIKI_LOCK_PERSISTENCE seconds
        if ((time() - $lock->lockedseen) < WIKI_LOCK_PERSISTENCE) {
            return $lock;
        } else { // it has timed out
            if(!delete_records('wiki_locks','pagename',$pagename,'wikiid', $wikiid)) {
                error('Unable to delete lock record');
            }
            return null;
        }
    }
    return null;
}

/**
 * Returns true if exists a lock of a the page/page#section and it
 * has been created by the user with the id passed as parameter.
 *
 * @param  Object  $WS        WikiStorage
 * @param  Integer $wikiid    Wiki id
 * @param  String  $pagename  PageName or PageName#SectionName
 * @return Integer $userid    User id
 */
function wiki_is_locked_by_userid($WS, $wikiid, $pagename, $userid) {
    global $USER;

    $groupid = $WS->groupmember->groupid;
    $ownerid = $WS->member->id;

    $pagename = $groupid.'_'.$ownerid.'_'.addslashes($pagename);

    // Check for lock
    $lock = wiki_get_record('wiki_locks', 'pagename', $pagename, 'wikiid',  $wikiid,
                                          'groupid',  $groupid,  'ownerid', $ownerid);
    if ($lock) {
        if ($lock->lockedby == $userid)
            return true;
    }

    return false;
}

/**
 * Returns a string which contains readable information about
 * why a page is locked: because it's already beeing edited or
 * because someone is editing one or more sections of it.
 * If it's not locked returns the empty string.
 *
 * @param  Object  $WS     WikiStorage
 * @param  Integer $wikiid Id of current wiki
 * @param  String  $page   PageName
 * @param  String  $text   Content text of the page
 * @return String          Lock information
 */
function wiki_is_page_locked($WS, $wikiid, $page, $text)
{
    global $USER;

    $locks      = array();
    $lock       = null;
    $lockstatus = null;

    // check if page is locked
    $lock = wiki_is_locked($WS, $wikiid, $page);
    if ($lock) {
        if (wiki_is_locked_by_userid($WS, $WS->dfwiki->id, $page, $USER->id)) {
            // if it's our lock allow to edit anyway
            wiki_release_lock($WS, $WS->dfwiki->id, $page);
            return null;
        }

        $locks[] = $lock;
        $lockstatus  = get_string('lockedpage_header', 'wiki');
        $lockstatus .= wiki_get_locks_table($WS, $locks, $page, 'page');
        $lockstatus .= get_string('lockedpage_footer', 'wiki');
        $lockstatus .= wiki_get_override_info($WS, $page, $locks);
        return $lockstatus;
    }

    // check if any section is locked
    $relative_hashes = wiki_get_relative_sections($text, '');
    foreach ($relative_hashes[1] as $subsection_hash)
    {
        $mypage = $page.'#'.$subsection_hash;
        $lock   = wiki_is_locked($WS, $wikiid, $mypage);
        if ($lock) $locks[] = $lock;
    }

    $sections_locked = count($locks);
    if ($sections_locked > 0)
    {
        $a = null; $a->n = $sections_locked;
        $lockstatus  = get_string('lockedpagebysections_header', 'wiki', $a);
        $lockstatus .= wiki_get_locks_table($WS, $locks, $page, 'sections');
        $lockstatus .= get_string('lockedpage_footer', 'wiki');
        $lockstatus .= wiki_get_override_info($WS, $page, $locks);
    }

    return $lockstatus;
}

/**
 * Returns a string which contains the information about
 * why a section is locked: because the page that contains it
 * is beeing edited, because it's part of a section that's beeing
 * edited, because it itself is already beeing edited or
 * because someone is editing one or more subsections of it.
 * If it's not locked returns the empty string.
 *
 * @param  Object  $WS     		WikiStorage
 * @param  Integer $wikiid 		Id of current wiki
 * @param  String  $page   		PageName
 * @param  String  $sectionhash md5sum of the section
 * @param  String  $text   		Content text of the page
 * @return String          		Lock information
 */
function wiki_is_section_locked($WS, $wikiid, $page, $sectionhash, $text)
{
    global $USER;

    $locks      = array();
    $lock       = null;
    $lockstatus = null;

    $mypage = $page.'#'.$sectionhash;

    // check if page is locked
    $lock = wiki_is_locked($WS, $wikiid, $page);
    if ($lock) {
        $locks[] = $lock;
        $lockstatus  = get_string('lockedsectionbypage_header', 'wiki');
        $lockstatus .= wiki_get_locks_table($WS, $locks, $page, 'page');
        $lockstatus .= get_string('lockedsection_footer', 'wiki');
        $lockstatus .= wiki_get_override_info($WS, $page, $locks);
        return $lockstatus;
    }

    // check if the section it's already locked
    $lock = wiki_is_locked($WS, $wikiid, $mypage);
    if ($lock) {
        if (wiki_is_locked_by_userid($WS, $WS->dfwiki->id, $mypage, $USER->id)) {
            // if it's our lock allow to edit anyway
            wiki_release_lock($WS, $WS->dfwiki->id, $mypage);
            return null;
        }

        $locks[] = $lock;
        $lockstatus  = get_string('lockedsection_header', 'wiki');
        $lockstatus .= wiki_get_locks_table($WS, $locks, $mypage, 'page');
        $lockstatus .= get_string('lockedsection_footer', 'wiki');
        $lockstatus .= wiki_get_override_info($WS, $mypage, $locks);
        return $lockstatus;
    }

    // check if relative sections (parents/subsections) are locked
    $relative_hashes = wiki_get_relative_sections($text, $sectionhash);

    //// check parents
    foreach ($relative_hashes[0] as $parent_hash)
    {
        $mypage =  $page.'#'.$parent_hash;
        $lock = wiki_is_locked($WS, $wikiid, $mypage);
        if ($lock) $locks[] = $lock;
    }

    $parents_locked = count($locks);
    if ($parents_locked > 0)
    {
        $lockstatus  = get_string('lockedsectionbyparent_header', 'wiki');
        $lockstatus .= wiki_get_locks_table($WS, $locks, $mypage, 'sections');
        $lockstatus .= get_string('lockedsectionbyparent_footer', 'wiki');
        $lockstatus .= wiki_get_override_info($WS, $mypage, $locks);

        return $lockstatus;
    }

    //// check subsections
    $locks = array();
    foreach ($relative_hashes[1] as $subsection_hash)
    {
        $mypage =  $page.'#'.$subsection_hash;
        $lock = wiki_is_locked($WS, $wikiid, $mypage);
        if ($lock) $locks[] = $lock;
    }

    $subsections_locked = count($locks);
    if ($subsections_locked > 0)
    {
        $a = null; $a->n = $subsections_locked;
        $lockstatus  = get_string('lockedsectionbysubsections_header', 'wiki', $a);
        $lockstatus .= wiki_get_locks_table($WS, $locks, $mypage, 'sections');
        $lockstatus .= get_string('lockedsection_footer', 'wiki');
        $lockstatus .= wiki_get_override_info($WS, $mypage, $locks);

        return $lockstatus;
    }

    return $lockstatus;
}

/**
 * Returns an array composed of two arrays related to a section:
 * 	1. array that contains the hashes of the parent sections
 * 	2. array that contains the hashes of the subsections
 *
 * @param  String  $text   		Content text of the page
 * @param  String  $sectionhash md5sum of the section
 * @return Array          		Two arrays with hashes
 */
function wiki_get_relative_sections($text, $sectionhash)
{
    $hashes = array();
    $depths = array();

    $depth   = -1;
    $pos     = -1;

    $lines    = explode("\n", $text);
    $numlines = count($lines);
    $numsecs  = 0;
    for ($i = 0; $i < $numlines; $i++) {
        $section_depth = wiki_get_section_depth($lines[$i], '(.*)+');
        if ($section_depth > 0) {
            $j = $i;
            $sectiontext = $lines[$j]; $j++;
            while (($j < $numlines))
            {
                $line_depth = wiki_get_section_depth($lines[$j], '(.*)+');
                if (($line_depth > $section_depth) || ($line_depth == 0))
                    $sectiontext  .= $lines[$j];
                else
                    break;
                $j++;
            }
            $hashes[] = md5($sectiontext);
            $depths[] = $section_depth;

            $numsecs = count($hashes);
            if ($hashes[$numsecs - 1] == $sectionhash) // the section we are treating
            {
                $depth = $depths[$numsecs - 1];
                $pos   = $numsecs - 1;
            }
        }
    }

    $res         = array();
    $parents     = array();
    $subsections = array();

    if ($pos == -1) // checking page sections
    {
        for ($i = 0; $i < $numsecs; $i++)
            $subsections[] = $hashes[$i];

        $res[0] = $parents;
        $res[1] = $subsections;
        return $res;
    }

    // checking section parents and subsections of sections
    for ($i = $pos + 1; $i < $numsecs; $i++)
    {
        if ($depths[$i] > $depth)
            $subsections[] = $hashes[$i];
        else
            break;
    }

    $max_depth = 6;
    for ($i = $pos - 1; $i >= 0; $i--)
    {
         if ($depths[$i] >= $max_depth) continue;
         if ($depths[$i] < $depth) $parents[] = $hashes[$i];
         if ($depths[$i] < $max_depth) $max_depth = $depths[$i];
    }

    $res[0] = $parents;
    $res[1] = $subsections;

    return $res;
}

/**
 * Returns the XHTML code of a moodle table containing
 * the lock information about a page or section.
 *
 * @param  Object $WS    WikiStorage
 * @param  Array  $locks Set of locks
 * @param  String $page  PageName
 * @param  String $by    Locked by
 */
function wiki_get_locks_table($WS, $locks, $page, $by)
{
    $return = '';
    $return .= "\n".
        '<table border="0" cellspacing="1" cellpadding="5" width="100%" class="generaltable boxalignleft">'."\n".
        '<tr>'."\n".
        '   <th valign="top" class="nwikileftnow header c0">#</th>'."\n".
        '   <th valign="top" class="nwikileftnow header c1">'.get_string('by', 'wiki').'</th>'."\n".
        '   <th valign="top" class="nwikileftnow header c2">'.get_string('since').'</th>'."\n".
        '   <th valign="top" class="nwikileftnow header c3">'.get_string('asof', 'wiki').'</th>'."\n".
        '</tr>'."\n";

    $i = 0;
    foreach ($locks as $lock) {
        $i++;
        $lock = wiki_get_lock_info($WS, $lock, $page, $by);

        $return .=
        '<tr>'."\n".
        '   <td class="textcenter nwikibargroundblanco">'.$i.'</td>'."\n".
        '   <td class="textcenter nwikibargroundblanco">'.$lock->name.'</td>'."\n".
        '   <td class="textcenter nwikibargroundblanco">'.$lock->since.'</td>'."\n".
        '   <td class="textcenter nwikibargroundblanco">'.$lock->seen.'</td>'."\n".
        '</tr>'."\n";
    }

    $return .= "</table>\n";
    return $return;
}

/**
 * If the user has an editing lock, releases it. Has no effect otherwise.
 * Note that it doesn't matter if this isn't called (as happens if their
 * browser crashes or something) since locks time out anyway. This is just
 * to avoid confusion of the 'what? it says I'm editing that page but I'm
 * not, I just saved it!' variety.
 * @param int $wikiid ID of wiki object.
 * @param string $pagename Name of page.
 */
function wiki_release_lock($WS, $wikiid, $pagename) {
    global $USER;

    if(!array_key_exists(SESSION_WIKI_LOCKS,$_SESSION)) {
        // No locks at all in session
        return;
    }

    // check that's our lock
    $groupid = $WS->groupmember->groupid;
    $ownerid = $WS->member->id;

    $pagename = $groupid.'_'.$ownerid.'_'.addslashes($pagename);

    // Check for lock
    $lock = wiki_get_record('wiki_locks', 'pagename', $pagename, 'wikiid',  $wikiid,
                                          'groupid',  $groupid,  'ownerid', $ownerid);
    if ($lock) {
        if ($lock->lockedby != $USER->id)
            return;
    } else
        return;

    $key = $wikiid.'_'.$pagename;

    if(array_key_exists($key,$_SESSION[SESSION_WIKI_LOCKS])) {
        $lockid = $_SESSION[SESSION_WIKI_LOCKS][$key];
        unset($_SESSION[SESSION_WIKI_LOCKS][$key]);
        if(!delete_records('wiki_locks','id',$lockid)) {
            error("Unable to delete lock record.");
        }
    }
}

/**
 * Locks a page or section if it has not any lock restrictions.
 * If it has any restrictions, prints them.
 *
 * @param  Object  $WS     WikiStorage
 * @return Boolean
 */
function wiki_set_lock($WS)
{
    $preview = optional_param('dfformpreview', '', PARAM_TEXT);
    // don't lock in preview mode
    if ($preview != '') return true;

    $page = wiki_page_last_version($WS->page, $WS);
    if (!$page) // when creating a new wiki or a new page of a wiki
        $content_text = '';
    else
        $content_text = $page->content;

    $sectionhash = optional_param('sectionhash', '', PARAM_TEXT);
    if ($sectionhash != '')
    {
        $lock_msg = wiki_is_section_locked($WS, $WS->dfwiki->id, $WS->page, $sectionhash, $content_text);
        if ($lock_msg == '') {
            return wiki_lock_message($WS, $sectionhash);
        } else
            print($lock_msg);
    } else {
        $lock_msg = wiki_is_page_locked($WS, $WS->dfwiki->id, $WS->page, $content_text);
        if ($lock_msg == '')
            return wiki_lock_message($WS);
        else
            print($lock_msg);
    }
    return false;
}

function wiki_get_override_info($WS, $page, $locks)
{
    $id   = optional_param('id', 0, PARAM_INT);
    $url  = isset($WS->dfcourse) ? $CFG->wwwroot."/mod/wiki/" : "";
    $cmid = isset($WS->dfcourse) ? $WS->cm->id                : 0;

    $modcontext      = get_context_instance(CONTEXT_MODULE, $WS->cm->id);
    $canoverridelock = has_capability('mod/wiki:overridelock', $modcontext);
    if ($canoverridelock) {
        $pages = '';
        $l = count($locks);
        for ($i = 0; $i < $l; $i++)
            $pages .= $locks[$i]->pagename.'|';

        if ($l == 1) {
            $stroverrideinfo   = get_string('overrideinfo','wiki');
            $stroverridebutton = get_string('overridebutton','wiki');
        } else {
            $stroverrideinfo   = get_string('overrideinfo_mult','wiki');
            $stroverridebutton = get_string('overridebutton_mult','wiki');
        }
        $sesskey = sesskey();
        $topage = urlencode($WS->page);
        $return = "\n<form id='overridelock' method='post' action='".$url."overridelock.php'>\n".
                  "  <div>\n".
                  "  <input type='hidden' name='sesskey' value='$sesskey' />\n".
                  "  <input type='hidden' name='id' value='$id' />\n".
                  "  <input type='hidden' name='topage' value='$topage' />\n".
                  "  <input type='hidden' name='cmid' value='$cmid' />\n";
        for ($i = 0; $i < $l; $i++) {
            $lockpage = urlencode($locks[$i]->pagename);
            $return .="  <input type='hidden' name='lockedpages[]' value='$lockpage' />\n";
        }
        $return .= $stroverrideinfo."\n".
                   "  <input type='submit' value='$stroverridebutton' />\n".
                   "  </div>\n".
                   "</form>\n";

        return $return;
    }
    else return '';
}

/**
 * Tries to obtain a lock of the page/section for the current user,
 * if suceeds adds the AJAX code to refresh it, if not returns false.
 *
 * @param  Object $WS          WikiStorage
 * @param  string $sectionhash Hash of the section (if needed)
 * @return boolean
 */
function wiki_lock_message($WS, $sectionhash='') {
    global $CFG;

    $id   = optional_param('id', 0, PARAM_INT);
    $page = optional_param('page', 0, PARAM_CLEANHTML);
    $page = str_replace('^edit/', '', $page);
    $url  = isset($WS->dfcourse) ? $CFG->wwwroot."/mod/wiki/" : "";
    $cmid = isset($WS->dfcourse) ? $WS->cm->id                : 0;

    if ($sectionhash != '') $pagename = $WS->page.'#'.$sectionhash;
    else                    $pagename = $WS->page;

    $lockid = wiki_obtain_lock($WS, $WS->dfwiki->id, $pagename);


    if ($lockid)
    {
        // it's our lock, add the AJAX code to refresh it
        $strlockcancelled   = get_string('lockcancelled','wiki');
        $strnojslockwarning = get_string('nojslockwarning','wiki');
        $intervalms         = WIKI_LOCK_RECONFIRM * 1000;

        // start javascript
        print "
<script type='text/javascript'>
var intervalID;
function handleResponse(o) {
    if (o.responseText == 'cancel') {
        document.forms['ewiki'].elements['preview'].disabled = true;
        document.forms['ewiki'].elements['save'].disabled    = true;
        clearInterval(intervalID);
        alert('$strlockcancelled');
    }
}
function handleFailure(o) {
    // Ignore for now
}
intervalID = setInterval(function() {
                            YAHOO.util.Connect.asyncRequest('POST','".$url."confirmlock.php',
                            {success:handleResponse, failure:handleFailure},'lockid={$lockid}');
                         }, $intervalms);
</script>
<noscript>
    <p>
        $strnojslockwarning
    </p>
</noscript>
";
        // end javascript
        return true;
    } // from if ($lock)

    // if it's locked and is not our lock
    return false;
}
?>
