<?PHP //$Id: teacherlinks.php,v 1.1.1.1 2009/03/16 02:35:54 nadavkav Exp $

include_once('../../config.php');
//include_once('../../lib/accesslib.php');
//include_once('../../lib/datalib.php');
//include_once('../../course/lib.php');
   require_once('../../lib/filelib.php');
	require_once('lib.php');

global $CFG, $USER;

  $fileid = required_param('linkid', PARAM_INT);	//
  $groupid = optional_param('groupid', "0", PARAM_INT);
  $cid = required_param('id', PARAM_INT);		//

    // Checks if user is owner of file, if not, checks if file is shared to them...if not...errors are displayed
    if (!fm_user_can_view_file($course->id, $fileid, $groupid)) {
	error(get_string("errnoviewfile",'block_file_manager'));
    }
    $filerec = get_record('fmanager_link', "id", $fileid);
    
    $filename = $filerec->link;
    if ($groupid == 0) {
	$pathinfo = fm_get_user_dir_space($filerec->owner);
    } else {
	$pathinfo = fm_get_group_dir_space($groupid);
    }
    if ($tmpfolder = fm_get_folder_path($filerec->folder, true, $groupid)) {
	$pathinfo = $pathinfo.$tmpfolder;
    }

    $pathname = $CFG->dataroot."/".$pathinfo."/".$filename;
    // check that file exists
    if (!file_exists("$pathname")) {
        not_found($course->id);
    }

  $firstcourseid = 0;

/*        if (empty($CFG->disablemycourses) and 
            !empty($USER->id) and 
            !(has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) and $adminseesall) and
            !isguest()) {    // Just print My Courses*/
            if ($courses = get_my_courses($USER->id, 'visible DESC, fullname ASC')) {
                foreach ($courses as $course) {
                    if ($course->id == SITEID) {
                        continue;
                    }
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    echo "<a $linkcss title=\"" . format_string($course->shortname) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" . format_string($course->fullname) . "</a><br/>";
		    if ($firstcourseid == 0) { $firstcourseid = $course->id; }
                }
            }
//        }

echo $pathname ;
$dest = $CFG->dataroot."/".$firstcourseid."/".$filename;
echo "<hr> linking to first course on the list ".$dest."<hr>";

// see php notes for linking files on file systems other then ones used on linux OSes
// http://il.php.net/link
if (link($pathname,$dest)) {echo "successfully linked :-)";}

?>
