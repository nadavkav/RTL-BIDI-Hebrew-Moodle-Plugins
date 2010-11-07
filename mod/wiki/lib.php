<?php  // $Id: lib.php,v 1.41 2008/12/02 09:24:06 kenneth_riba Exp $
/// Original DFwiki created by David Castro, Ferran Recio and Marc Alier.
/// Library of functions and constants for module wiki
/// (replace wiki with the name of your module and delete this line)
if (!isset($CFG)) die('This is a system part.');

//dfwikilib is the main program library
require_once ('locallib.php');
//this include have all blocks global functions.
//Any block which needs to print in the main
//content space goes here

//full_wiki is a tricky! it's used only when we
//install and dfwiki is already in the system
/*if (isset($full_wiki))
    require_once ('blocks/lib.php');*/

require_once("$CFG->dirroot/mod/wiki/class/wikistorage.class.php");

require_once("$CFG->dirroot/mod/wiki/lib/wiki_manager.php");
require_once("$CFG->dirroot/mod/wiki/lib/wiki.class.php");

// If this file is included, install/ewiki migration fails
//
//require_once ("$CFG->dirroot/mod/wiki/xml/exportxmllib.php");


//--------- require all .class.php in class directory --------------------
$dirclass = $CFG->dirroot.'/mod/wiki/class';
//open dir
if (!$dir = opendir($dirclass)) {  // Can't open it for some reason
	echo ('There\'s some error in wiki class directory');
} else {
	while ($file = readdir($dir)){
		//import class file
		if (substr_count($file,'.class.php')!=0){
			require_once ($dirclass.'/'.$file);
		}
	}
}

//lets free memory
unset($dir);
unset($file);
unset($dirclass);

//--------- require all .lib.php in lib directory --------------------
$dirlib = $CFG->dirroot.'/mod/wiki/lib';

//open dir
if (!$dir = opendir($dirlib)) {  // Can't open it for some reason
	echo ('There\'s some error in wiki lib directory');
} else {
	while ($file = readdir($dir)){
		//import lib file
		if (substr_count($file,'.lib.php')!=0){
			require_once ($dirlib.'/'.$file);
		}
	}
}

//lets free memory
//if (isset($dir)) unset($dir);
//if (isset($file)) unset($file);

//------------ include specific enviroment libraries ----------------

if (!isset($wiki_context)) $wiki_context = array('mod');
if (!is_array($wiki_context)) $wiki_context = array($wiki_context);

$wiki_context_files = array();

foreach ($wiki_context as $w_context) {
	//open dir
	if (file_exists($dirlib.'/'.$w_context) && $dir = opendir($dirlib.'/'.$w_context)) {
		while ($file = readdir($dir)){
			//import lib file
			if (!in_array($file,$wiki_context_files)) {
				if (substr_count($file,'.lib.php')!=0){
					require_once ($dirlib.'/'.$w_context.'/'.$file);
					$wiki_context_files[] = $file;
				}
			}
		}
	}
}

//lets free memory
if (isset($dir)) unset($dir);
if (isset($file)) unset($file);
unset($dirlib);
unset ($wiki_context_files);

//------------- require all parts part.php ----------------
$dirpart = $CFG->dirroot.'/mod/wiki/part';
//open dir
if (!$dir = opendir($dirpart)) {  // Can't open it for some reason
	echo ('There\'s some error in wiki part directory');
} else {
	while ($file = readdir($dir)){
		if ($file && $file != '.' && $file!='..' && is_dir($dirpart.'/'.$file)) {
			//import class file
			if (file_exists($dirpart.'/'.$file.'/part.php')){
				require_once ($dirpart.'/'.$file.'/part.php');
			}
		}
	}
}

//lets free memory
unset($dir);
unset($file);
unset($dirpart);

//------------- starts library ----------------

function wiki_add_instance($dfwiki) {
/// Given an object containing all the necessary data,
/// (defined by the form in mod.html) this function
/// will create a new instance and return the id number
/// of the new instance.

    global $COURSE;

	$dfwiki->timemodified = time();

    # May have to add extra stuff in here #

    if (empty($dfwiki->pagename)) {
        $dfwiki->pagename = get_string('firstpage','wiki');
    } else{
		$dfwiki->pagename = trim ($dfwiki->pagename);
        $dfwiki->pagename = stripslashes($dfwiki->pagename);
        $dfwiki->pagename = wiki_clean_name($dfwiki->pagename);
        $dfwiki->pagename = addslashes($dfwiki->pagename);
	}

    //privileges
    $dfwiki->editable = (isset($dfwiki->editable)) ? 1 : 0;
    $dfwiki->attach = (isset($dfwiki->attach)) ? 1 : 0;
    $dfwiki->restore = (isset($dfwiki->restore)) ? 1 : 0;
    $dfwiki->teacherdiscussion = (isset($dfwiki->teacherdiscussion)) ? 1 : 0;
    $dfwiki->studentdiscussion = (isset($dfwiki->studentdiscussion)) ? 1 : 0;
    $dfwiki->editanothergroup = (isset($dfwiki->editanothergroup)) ? 1 : 0;
	$dfwiki->editanotherstudent = (isset($dfwiki->editanotherstudent)) ? 1 : 0;
	$dfwiki->votemode = (isset($dfwiki->votemode)) ? 1 : 0;
	$dfwiki->listofteachers = (isset($dfwiki->listofteachers)) ? 1 : 0;
    $dfwiki->editorrows = (isset ($dfwiki->editorrows)) ? $dfwiki->editorrows : 40;
    $dfwiki->editorcols = (isset ($dfwiki->editorcols)) ? $dfwiki->editorcols : 60;
    $dfwiki->wikicourse = (isset ($dfwiki->wikicourse)) ? $dfwiki->wikicourse : 0;
    $dfwiki->evaluation = (isset ($dfwiki->evaluation)) ? $dfwiki->evaluation : 0;
    $dfwiki->notetype = (isset ($dfwiki->notetype)) ? $dfwiki->notetype : 0;

    if($COURSE->groupmodeforce){
        switch($COURSE->groupmode){
        	case NOGROUPS:
        	    $dfwiki->groupmode = 0;
        	    break;
        	case SEPARATEGROUPS:
        		$dfwiki->groupmode = 1;
        		break;
        	case VISIBLEGROUPS:
        		$dfwiki->groupmode = 2;
        		break;
        	default:
        }
    }

    $wikiManager = wiki_manager_get_instance();
    $insert = $wikiManager->save_wiki($dfwiki);

    return $insert;
}


function wiki_update_instance($dfwiki) {

    global $CFG;

/// Given an object containing all the necessary data,
/// (defined by the form in mod.html) this function
/// will update an existing instance with new data.

    $dfwiki->timemodified = time();
    $dfwiki->id = $dfwiki->instance;

    # May have to add extra stuff in here #
    $dfwiki->pagename = trim ($dfwiki->pagename);
    $dfwiki->pagename = stripslashes($dfwiki->pagename);
    $dfwiki->pagename = wiki_clean_name($dfwiki->pagename);
    $dfwiki->pagename = addslashes($dfwiki->pagename);
    if (!$dfwiki->pagename) {
        $dfwiki->pagename = get_string('firstpage','wiki');
    }

    //privileges
    $dfwiki->editable = (isset($dfwiki->editable)) ? 1 : 0;
    $dfwiki->attach = (isset($dfwiki->attach)) ? 1 : 0;
    $dfwiki->restore = (isset($dfwiki->restore)) ? 1 : 0;
	$dfwiki->teacherdiscussion = (isset($dfwiki->teacherdiscussion)) ? 1 : 0;
	$dfwiki->studentdiscussion = (isset($dfwiki->studentdiscussion)) ? 1 : 0;
    $dfwiki->editanothergroup = (isset($dfwiki->editanothergroup)) ? 1 : 0;
	$dfwiki->editanotherstudent = (isset($dfwiki->editanotherstudent)) ? 1 : 0;
	$dfwiki->votemode = (isset($dfwiki->votemode)) ? 1 : 0;
	$dfwiki->listofteachers = (isset($dfwiki->listofteachers)) ? 1 : 0;
    $dfwiki->editorrows = (isset ($dfwiki->editorrows)) ? $dfwiki->editorrows : 40;
    $dfwiki->editorcols = (isset ($dfwiki->editorcols)) ? $dfwiki->editorcols : 60;
    $dfwiki->evaluation = (isset ($dfwiki->evaluation)) ? $dfwiki->evaluation : 0;
    $dfwiki->notetype = (isset ($dfwiki->notetype)) ? $dfwiki->notetype : 0;

    $wikimanager = wiki_manager_get_instance();

    //update page edit permission
    if ($dfwiki->effects=='allpages'){
        $wikimanager->pages_set_editable($dfwiki->id, $dfwiki->editable);
    }

    # May have to add extra stuff in here #
    return $wikimanager->update_wiki($dfwiki);
}


function wiki_delete_instance($id) {
/// Given an ID of an instance of this module,
/// this function will permanently delete the instance
/// and any data that depends on it.
	global $WS;

    $wikimanager = wiki_manager_get_instance();

    //get dfwiki entry
    if (! $wikimanager->get_wiki_by_id($id)) {
        return false;
    }

    //get modules id
    if (! $module = get_record("modules", "name", 'wiki')){
        return false;
    }
$WS->dfwiki->course = $wikimanager->persistencemanager->wikis[$id]->course;
$WS->dfwiki->id = $id;
    //get cm id
    if (! $WS->cm = get_record("course_modules", "course", $WS->dfwiki->course, "module", $module->id, "instance", $id)){
        return false;
    }

    //delete uploaded files
    wiki_upload_config($WS);
    if (!wiki_upload_deldir($WS)){
        return false;
    }

    # Delete any dependent records here #
    return $wikimanager->delete_wiki($WS->dfwiki->id);
}

function wiki_user_outline($mod, $dfwiki) {
/// Return a small object with summary information about what a
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description
    global $CFG, $USER;

    $wikimanager = wiki_manager_get_instance();
    $pages = $wikimanager->get_wiki_pages_user_outline($USER->username, $dfwiki->id);

    if($pages){
        $key = array_keys($pages);
        $return->time = $pages[$key[0]]->lastmodified;
        $nposts = wiki_count_unique_pages($pages);
        $return->info = get_string("numpages", 'dfwiki', $nposts);

        return $return;
     }

     return NULL;
}

function wiki_count_unique_pages($pages){
//This is an extra function that aids wiki_user_outline by
//counting no repeated pages in a dfwiki belonging to a user

    $count = -1;
    foreach($pages as $p){
        if($page != $p->pagename){
            $count++;
        }
        $page = $p->pagename;
    }
    return $count;
}

function wiki_user_complete($mod, $dfwiki) {
/// Print a detailed representation of what a  user has done with
/// a given particular instance of this module, for user activity reports.
    global $CFG,$USER;

    $wikimanager = wiki_manager_get_instance();
    $pages = $wikimanager->get_wiki_pages_user_outline($USER->username, $dfwiki->id);

    if($pages){

            echo '<table  width="100%" cellspacing="0" align="center" class="forumpost">
                      <tr height="30px" class="usercomplete">
                        <td align="center">';
                            echo get_string("pagename",'wiki');
                echo   '</td>
                        <td align="center">';
                            echo get_string("version",'wiki');
                echo   '</td>
                        <td align="center">';
                            echo get_string("status",'wiki');
                echo   '</td>
                        <td align="center">';
                            echo get_string("date",'wiki');
                echo   '</td>
                    </tr>';

            if(fmod(count($pages),2)==0){
                $count = 0;
            }else{
                $count = 1;
            }

            foreach($pages as $page){
                if(fmod($count,2)==0){
                    echo '<tr class="nwikibargroundgris">';
                }else{
                    echo '<tr class="nwikibargroundblanco">';
                }
                echo    '<td align="center">';

                echo '<a href='.$CFG->wwwroot.'/mod/wiki/view.php?id='.$mod->id.'&page=info/'.urlencode($page->pagename).'&editor='.$page->editor.'&gid=0>'.$page->pagename.'</a>';

                echo   '</td>
                        <td align="center">';
                            echo $page->version;
                echo   '</td>
                        <td align="center">';
                            echo get_string("created",'wiki');
                echo   '</td>
                        <td align="center">';
                             echo userdate($page->lastmodified);
                echo   '</td>
                    </tr>';
                $count++;
            }
        echo '</table>';

    } else {
        echo "<p>".get_string("nopages", 'wiki')."</p>";
    }


    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in newmodule activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course
 * @param bool $isteacher
 * @param int $timestart
 * @return boolean true on success, false on failure.
 **/
function wiki_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity
/// that has occurred in dfwiki activities and print it out.
/// Return true if there was output, or false is there was none.


	global $CFG, $WS;

	// get all wiki instances used in the course
	if (!$dfwikis = get_all_instances_in_course('wiki',$course)) {
        return false;
    }

	$ead = wiki_manager_get_instance();
	$allpages = array();
	// get all new pages (10 pages , no date search) for those wikis
	foreach($dfwikis as $dfwiki) {
		$allpages[$dfwiki->id] = array($dfwiki , $ead->get_wiki_most_uptodate_pages(10,$dfwiki) );
	}

	foreach($allpages as $instance) {
		$dfwiki = $instance[0];
		$pages = $instance[1];
		$dir = $CFG->wwwroot.'/mod/wiki/view.php?id='.$dfwiki->coursemodule;

		$text = "<h3>{$dfwiki->name}</h3>";
		if (count($pages)!=0){
			$text .= '<table border="0" cellpadding="0" cellspacing="0">';
			$i = 1;
			foreach ($pages as $page){
				//$pageinfo = wiki_page_last_version ($page);
				$pageinfo  =  $ead->get_wiki_page_by_pagename ($dfwiki,$page);
				$brs = '';//(strlen($page)>12)? '<br />&nbsp;&nbsp;&nbsp;' : '';
				$text.= '<tr>
					<td class="nwikipagesupdates">
						'.$i.'- <a href="'.$dir.'&amp;page='.urlencode($page).'" title="'.$page.'">'.ltrim($page,20).'</a>'.$brs.'
						&nbsp;-&nbsp;<small>('.strftime('%d %b %y',$pageinfo->lastmodified).')</small><br/>&nbsp;&nbsp;
						'.wiki_get_user_info($pageinfo->author).'
					</td>
					</tr>';
					$i++;
			}
			$text.='</table>';
		} else {
			$text = get_string('nopages','wiki');
		}
		echo $text;
	}

	return true;  //  True if anything was printed, otherwise false


}

function wiki_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such
/// as sending out mail, toggling flags etc ...

    return true;
}

function wiki_grades($dfwikiid) {
/// Must return an array of grades for a given instance of this module,
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;

   return NULL;
}

function wiki_get_participants($dfwikiid) {
//Must return an array of user records (all data) who are participants
//for a given instance of dfwiki. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    return false;
}

function wiki_scale_used ($dfwikiid,$scaleid) {
//This function returns if a scale is being used by one dfwiki
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.

    $return = false;

    return $return;
}

function wiki_print_content(&$WS){
	global $CFG;
	//this function is the responsable of printing the content of the module.
    if(isset($WS->dfcontent) && $WS->dfcontent>=0 && $WS->dfcontent < count($WS->dfcontentf)){
        $main_function = $WS->dfcontentf[$WS->dfcontent];
    } else {
        $main_function = 'wiki_main_content';
    }
    if (!function_exists($main_function)) {
    	require_once ($CFG->dirroot.'/blocks/wiki_ead/lib.php');
    }
    $main_function($WS);
}


/**
 * This function is the responsable of printing the group mode menu
 *
 * There are 9 possible combinations of groupmode-studentmode.
 *
 * 	Groupmode	Studentmode				Description
 * 		0			0			No groups-Users in groups
 * 		0			1			No groups-Separate Users
 * 		0			2			No groups-Visible Users
 * 		1			0			Separate Groups-Users in groups
 * 		1			1			Separate Groups-Separate Users
 * 		1			2			Separate Groups-Visible Users
 * 		2			0			Visible Groups-Users in groups
 * 		2			1			Visible Groups-Separate Users
 * 		2			2			Visible Groups-Visible Users
 *
 *
 * @param WikiStorage $WS.
 *
 * @TODO: this function is filled of bugs. We have to rewrite it.
 *
 */
function wiki_print_groupmode_selection(&$WS){
	global $USER, $CFG, $COURSE;
	//this function is the responsable of printing the group mode menu
	//Course groups list
    $wikimanager = wiki_manager_get_instance();

    $listgroups = $wikimanager->get_course_groups($COURSE->id);

	$listmembers = $listgroupsmembers = $wikimanager->get_course_members($COURSE->id);

	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
	$cm->id = isset($WS->dfcourse)?$COURSE->id:$WS->cm->id;
	switch($WS->cm->groupmode){
		//Without groups:
		case '0':
            // if no groups then get all the users of the course
            $listmembers = get_course_users($COURSE->id, 'u.lastname');
			switch($WS->dfwiki->studentmode){
				//Commune wiki
				case '0':
					//no menu
					break;
				//Separate students
				case '1':
					if(has_capability('mod/wiki:editanywiki',$context)){
						wiki_print_menu_students($listmembers, $WS->member->id, $cm);
					}
					break;
				//Visible students
				case '2':
					wiki_print_menu_students($listmembers, $WS->member->id, $cm);
					break;
				default:
					break;
			}
			break;
		//Separate groups:
		case '1':
			switch($WS->dfwiki->studentmode){
					//Students in group
					case '0':
						wiki_print_menu_groups($listgroups, $WS->groupmember->groupid, $cm, $WS);
						break;
					//Separate students
					case '1':
						wiki_print_menu_groups_and_students($listgroups, $listgroupsmembers, $WS);
						break;
					//Visible students
					case '2':
						wiki_print_menu_groups_and_students($listgroups, $listgroupsmembers, $WS);
						break;
					default:
						break;
			}
			break;

		//Visible groups:
		case '2':
			switch($WS->dfwiki->studentmode){
				//Students in group
				case '0':
					wiki_print_menu_groups($listgroups, $WS->groupmember->groupid, $cm, $WS);
					break;
				//Separate students
				case '1':
					wiki_print_menu_groups_and_students($listgroups, $listgroupsmembers, $WS);
					break;
				//Visible students
				case '2':
					wiki_print_menu_groups_and_students($listgroups, $listgroupsmembers, $WS);
					break;
				default:
					break;
			}
			break;
		default:
			break;
	}
}


function wiki_print_teacher_selection($cm, $dfwiki){
    global $CFG, $COURSE;
    // dont print the list of teachers for wiki commune

	$cm = get_coursemodule_from_instance('wiki',$dfwiki->id,$COURSE->id);
    if(!(($cm->groupmode == '0') && ($dfwiki->studentmode == '0'))){
        if ($dfwiki->listofteachers){
            //Course teachers list
            $wikimanager = wiki_manager_get_instance();
            $listteachers = get_course_teachers($COURSE->id); //$wikimanager->get_course_teachers($COURSE->id); //(nadavkav)

            //print the teachers list
            wiki_print_menu_teachers($listteachers, $cm);
        }
    }
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other dfwiki functions go here.  Each of them must have a name that
/// starts with wiki_


?>
