<?PHP //$Id: block_course_list.php,v 1.46.2.6 2008/08/29 04:23:38 peterbulmer Exp $

include_once($CFG->dirroot . '/course/lib.php');

class block_course_list_filtered extends block_list {
    function init() {
        $this->title = get_string('filteredcourses','block_course_list_filtered');
        $this->version = 2007101509;
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $THEME, $CFG, $USER, $COURSE;

        $filter  = optional_param('filterlist', '', PARAM_RAW); // filter courses by category sub string (nadavkav)

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';


        date_default_timezone_set('UTC');
        if (empty($filter)) {
            $catfilter = '2012'; //date('Y'); //'2011';
        } else {
            $catfilter = $filter;
        }

        $filterform = '<form action="view.php" method="get" name="filterform" id="filterform">';
            //$filterform .= '<input type="hidden" name="id" value="'.$id.'">';
            $filterform .=  '<input type="hidden" name="id" value="'.$COURSE->id.'">';
            //$filterform .=  '<select size="1" id="filterlist" name="filterlist" onChange="document.getElementById(\'filter\').value = document.getElementById(\'filterlist\').options[document.getElementById(\'filterlist\').selectedIndex].text; document.getElementById(\'filterform\').submit();">';
            $filterform .=  '<select size="1" id="filterlist" name="filterlist" onChange="document.getElementById(\'filterform\').submit();">';
			$filterform .=  '<option value="2010" '.(($catfilter=='2010')? ' selected ':'').' >תשע 2010</option>';
			$filterform .=  '<option value="2011" '.(($catfilter=='2011')? ' selected ':'').' >תשעא 2011</option>';
			$filterform .=  '<option value="2012" '.(($catfilter=='2012')? ' selected ':'').' >תשעב 2012</option>';
            $filterform .=  '</select>';
            //$filterform .=  '<input type="hidden" id="filter" name="filter" value="'.$catfilter.'">';
            //$filterform .=  '<input type="submit" id="submit" value=">>">';
        $filterform .=  '</form>';

        $this->content->items[] = $filterform; // first item is a YEAR filter listbox. hack.
        $this->content->icons[] = '';

        $icon  = "<img src=\"$CFG->pixpath/i/course.gif\"".
                 " class=\"icon\" alt=\"".get_string("coursecategory")."\" />";

        $adminseesall = true;
        if (isset($CFG->block_course_list_filtered_adminview)) {
           if ( $CFG->block_course_list_filtered_adminview == 'own'){
               $adminseesall = false;
           }
        }

        if (empty($CFG->disablemycourses) and
            !empty($USER->id) and
            !(has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) and $adminseesall) and
            !isguest()) {    // Just print My Courses
            if ($courses = get_my_courses($USER->id, 'visible DESC, fullname ASC')) {
                foreach ($courses as $course) {
                    if ($course->id == SITEID) {
                        continue;
                    }

                    // Remove course from the list if not under the filtered category (nadavkav)
                    $cat = block_get_category_fullpath($course);
                    if (strpos($cat,$catfilter) == 0) continue;

                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    $this->content->items[]="<a $linkcss title=\"" . format_string($course->shortname) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" . format_string($course->fullname) . "</a>";
                    $this->content->icons[]=$icon;
                }
                $this->title = get_string('mycourses');
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) || empty($CFG->block_course_list_filtered_hideallcourseslink)) {
                    $this->content->footer = "<hr/><a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a> ...";
                }
            }
            $this->get_remote_courses();
            if ($this->content->items) { // make sure we don't return an empty list
                return $this->content;
            }
        }

        $categories = get_categories("0");  // Parent = 0   ie top-level categories only
        if ($categories) {   //Check we have categories
            if (count($categories) > 1 || (count($categories) == 1 && count_records('course') > 200)) {     // Just print top level category links
                foreach ($categories as $category) {
                    $linkcss = $category->visible ? "" : " class=\"dimmed\" ";
                    $this->content->items[]="<a $linkcss href=\"$CFG->wwwroot/course/category.php?id=$category->id\">" . format_string($category->name) . "</a>";
                    $this->content->icons[]=$icon;
                }
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) || empty($CFG->block_course_list_filtered_hideallcourseslink)) {
                    $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                }
                $this->title = get_string('categories');
            } else {                          // Just print course names of single category
                $category = array_shift($categories);
                $courses = get_courses($category->id);

                if ($courses) {
                    foreach ($courses as $course) {
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";

                        $this->content->items[]="<a $linkcss title=\""
                                   . format_string($course->shortname)."\" ".
                                   "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">"
                                   .  format_string($course->fullname) . "</a>";
                        $this->content->icons[]=$icon;
                    }
                /// If we can update any course of the view all isn't hidden, show the view all courses link
                    if (has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) || empty($CFG->block_course_list_filtered_hideallcourseslink)) {
                        $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                    }
                    $this->get_remote_courses();
                } else {

                    $this->content->icons[] = '';
                    $this->content->items[] = get_string('nocoursesyet');
                    if (has_capability('moodle/course:create', get_context_instance(CONTEXT_COURSECAT, $category->id))) {
                        $this->content->footer = '<a href="'.$CFG->wwwroot.'/course/edit.php?category='.$category->id.'">'.get_string("addnewcourse").'</a> ...';
                    }
                    $this->get_remote_courses();
                }
                $this->title = get_string('courses');
            }
        }

        return $this->content;
    }

    function get_remote_courses() {
        global $THEME, $CFG, $USER;

        if (!is_enabled_auth('mnet')) {
            // no need to query anything remote related
            return;
        }

        $icon  = '<img src="'.$CFG->pixpath.'/i/mnethost.gif" class="icon" alt="'.get_string('course').'" />';

        // only for logged in users!
        if (!isloggedin() || isguest()) {
            return false;
        }

        if ($courses = get_my_remotecourses()) {
            $this->content->items[] = get_string('remotecourses','mnet');
            $this->content->icons[] = '';
            foreach ($courses as $course) {
                $this->content->items[]="<a title=\"" . format_string($course->shortname) . "\" ".
                    "href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$course->hostid}&amp;wantsurl=/course/view.php?id={$course->remoteid}\">"
                    . format_string($course->fullname) . "</a>";
                $this->content->icons[]=$icon;
            }
            // if we listed courses, we are done
            return true;
        }

        if ($hosts = get_my_remotehosts()) {
            $this->content->items[] = get_string('remotemoodles','mnet');
            $this->content->icons[] = '';
            foreach($USER->mnet_foreign_host_array as $somehost) {
                $this->content->items[] = $somehost['count'].get_string('courseson','mnet').'<a title="'.$somehost['name'].'" href="'.$somehost['url'].'">'.$somehost['name'].'</a>';
                $this->content->icons[] = $icon;
            }
            // if we listed hosts, done
            return true;
        }

        return false;
    }


}

function block_get_category_fullpath(&$course) {
  global $CFG;

  // Course Category name, if appropriate. //(nadavkav patch)
  if (!$category = get_record("course_categories", "id", $course->category)) {
    //error("Category not known!");
  }

  $categoryfullpath ='';
  if ( !empty($category->path) ) {
    $categorypath = explode('/',$category->path); // display all parent category paths (nadavkav)

    foreach ($categorypath as $eachcategory) {
      if (!$singlecategory = get_record("course_categories", "id", $eachcategory)) {
        //error("Category not known!");
      }

      if (!empty($singlecategory)) {
        $categoryfullpath .= "<a href=\"{$CFG->wwwroot}/course/category.php?id=$singlecategory->id\">$singlecategory->name</a> >> ";
      }
    }
  }
  return $categoryfullpath;
}

?>
