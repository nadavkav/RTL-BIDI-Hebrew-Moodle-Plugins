<?PHP  // $Id: lib.php,v 1.3 2004/06/09 22:35:27 gustav_delius Exp $

/// Library of functions and constants for module bookings
/// (replace bookings with the name of your module and delete this line)



function bookings_delete_instance($id){
    global $CFG;

    if (! $bookings = get_record('bookings', 'id', $id)) {
        return false;
    }

    require_once("$CFG->dirroot/mod/bookings/type/$bookings->type/bookings.class.php");
    $bookingsclass = "bookings_$bookings->type";
    $ass = new $bookingsclass();
    return $ass->delete_instance($bookings);
}


function bookings_update_instance($bookings){
    global $CFG;

    require_once("$CFG->dirroot/mod/bookings/type/$bookings->type/bookings.class.php");
    $bookingsclass = "bookings_$bookings->type";
    $ass = new $bookingsclass();
    return $ass->update_instance($bookings);
}

function bookings_add_instance($bookings) {
    global $CFG;

    require_once("$CFG->dirroot/mod/bookings/type/$bookings->type/bookings.class.php");
    $bookingsclass = "bookings_$bookings->type";
    $ass = new $bookingsclass();
    return $ass->add_instance($bookings);
}



function bookings_user_outline($course, $user, $mod, $bookings) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    return $return;
}

function bookings_user_complete($course, $user, $mod, $bookings) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.

    return true;
}

function bookings_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in bookings activities and print it out. 
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

function bookings_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

    global $CFG;

    return true;
}

function bookings_grades($bookingsid) {
/// Must return an array of grades for a given instance of this module, 
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;

   return NULL;
}

function bookings_get_participants($bookingsid) {
//Must return an array of user records (all data) who are participants
//for a given instance of bookings. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    return false;
}

function bookings_scale_used ($bookingsid,$scaleid) {
//This function returns if a scale is being used by one bookings
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
   
    $return = false;

    //$rec = get_record("bookings","id","$bookingsid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// return a list of bookings types

function bookings_types() {
    $types = array();
    $names = get_list_of_plugins('mod/bookings/type');
    foreach ($names as $name) {
        $types[$name] = get_string('type'.$name, 'bookings');
    }
    asort($types);
    return $types;
}


//////////////////////////////////////////////////////////////////////////////////////
/// fetch a list of properties for an item

function bookings_item_properties($itemid) {
    global $CFG;
    $proplist = array();
    $sql = 'SELECT id,name,value FROM '.$CFG->prefix.'bookings_item_property  WHERE itemid='.$itemid;
    if ($p = get_records_sql($sql)) {
        foreach($p as $prop) {
            $proplist[$prop->name] = $prop->value;
        }
    }
    return $proplist;
}            

/*
 * Standard base class for all bookings submodules (bookings types).
 *
 *
 */
class bookings_base {

    var $cm;
    var $course;
    var $bookings;

    /**
     * Constructor for the base bookings class
     *
     * Constructor for the base bookings class.
     * If cmid is set create the cm, course, bookings objects.
     *
     * @param cmid   integer, the current course module id - not set for new bookingss
     * @param bookings   object, usually null, but if we have it we pass it to save db access
     */
    function bookings_base($cmid=0, $bookings=NULL, $cm=NULL, $course=NULL) {

        global $CFG;

        if ($cmid) {
            if ($cm) {
                $this->cm = $cm;
            } else if (! $this->cm = get_record('course_modules', 'id', $cmid)) {
                error('Course Module ID was incorrect');
            }

            if ($course) {
                $this->course = $course;
            } else if (! $this->course = get_record('course', 'id', $this->cm->course)) {
                error('Course is misconfigured');
            }

            if ($bookings) {
                $this->bookings = $bookings;
            } else if (! $this->bookings = get_record('bookings', 'id', $this->cm->instance)) {
                error('bookings ID was incorrect');
            }

            $this->strbookings = get_string('modulename', 'bookings');
            $this->strbookingss = get_string('modulenameplural', 'bookings');
            $this->strsubmissions = get_string('submissions', 'bookings');
            $this->strlastmodified = get_string('lastmodified');

            if ($this->course->category) {
                $this->navigation = "<a target=\"{$CFG->framename}\" href=\"$CFG->wwwroot/course/view.php?id={$this->course->id}\">{$this->course->shortname}</a> -> ".
                                    "<a target=\"{$CFG->framename}\" href=\"index.php?id={$this->course->id}\">$this->strbookingss</a> ->";
            } else {
                $this->navigation = "<a target=\"{$CFG->framename}\" href=\"index.php?id={$this->course->id}\">$this->strbookingss</a> ->";
            }

            $this->pagetitle = strip_tags($this->course->shortname.': '.$this->strbookings.': '.format_string($this->bookings->name,true));

            if (!$this->cm->visible and !isteacher($this->course->id)) {
                $pagetitle = strip_tags($this->course->shortname.': '.$this->strbookings);
                print_header($pagetitle, $this->course->fullname, "$this->navigation $this->strbookings", 
                             "", "", true, '', navmenu($this->course, $this->cm));
                notice(get_string("activityiscurrentlyhidden"), "$CFG->wwwroot/course/view.php?id={$this->course->id}");
            }

            $this->currentgroup = get_current_group($this->course->id);
        }

    /// Set up things for a HTML editor if it's needed
        if ($this->usehtmleditor = can_use_html_editor()) {
            $this->defaultformat = FORMAT_HTML;
        } else {
            $this->defaultformat = FORMAT_MOODLE;
        }

    }

    /*
     * Display the bookings to students (sub-modules will most likely override this)
     */

    function view() {

        add_to_log($this->course->id, "bookings", "view", "view.php?id={$this->cm->id}", 
                   $this->bookings->id, $this->cm->id);

        $this->view_header();

        $this->view_intro();

        $this->view_dates();

        $this->view_footer();
    }

    /*
     * Display the top of the view.php page, this doesn't change much for submodules
     */
    function view_header($subpage='') {

        global $CFG;

        if ($subpage) {
            $extranav = '<a target="'.$CFG->framename.'" href="view.php?id='.$this->cm->id.'">'.
                          format_string($this->bookings->name,true).'</a> -> '.$subpage;
        } else {
            $extranav = ' '.format_string($this->bookings->name,true);
        }

        print_header($this->pagetitle, $this->course->fullname, $this->navigation.$extranav, '', '', 
                     true, update_module_button($this->cm->id, $this->course->id, $this->strbookings), 
                     navmenu($this->course, $this->cm));

    }


    /*
     * Display the bookings intro
     */
    function view_intro() {
        if ($this->bookings->summary) {
            print_simple_box_start('center', '', '', '', 'generalbox', 'intro');
            echo format_text($this->bookings->summary, $this->bookings->format);
            print_simple_box_end();
        }
    }

    /*
     * Display the bookings dates
     */
    function view_dates() {
    	return;
    }


    /*
     * Display the bottom of the view.php page, this doesn't change much for submodules
     */
    function view_footer() {
        print_footer($this->course);
    }







    /*
     * Print the start of the setup form for the current bookings type
     */
    function setup(&$form, $action='') {
        global $CFG, $THEME;

        if (empty($this->course)) {
            if (! $this->course = get_record("course", "id", $form->course)) {
                error("Course is misconfigured");
            }
        }
        if (empty($action)) {   // Default destination for this form
            $action = $CFG->wwwroot.'/course/mod.php';
        }

        if (empty($form->name)) {
            $form->name = "";
        }
        if (empty($form->type)) {
            $form->type = "";
        }
        if (empty($form->description)) {
            $form->description = "";
        }

        $strname    = get_string('name');
        $strbookingss = get_string('modulenameplural', 'bookings');
        $strheading = empty($form->name) ? get_string("type$form->type",'bookings') : s(format_string(stripslashes($form->name),true));

        print_header($this->course->shortname.': '.$strheading, "$strheading",
                "<a href=\"$CFG->wwwroot/course/view.php?id={$this->course->id}\">{$this->course->shortname} </a> -> ".
                "<a href=\"$CFG->wwwroot/mod/bookings/index.php?id={$this->course->id}\">$strbookingss</a> -> $strheading");

        print_simple_box_start('center', '70%');
        print_heading(get_string('type'.$form->type,'bookings'));
        print_simple_box(get_string('help'.$form->type, 'bookings'), 'center');
        include("$CFG->dirroot/mod/bookings/type/common.html");

        include("$CFG->dirroot/mod/bookings/type/".$form->type."/mod.html");
        $this->setup_end(); 
    }

    /*
     * Print the end of the setup form for the current bookings type
     */
    function setup_end() {
        global $CFG;

        include($CFG->dirroot.'/mod/bookings/type/common_end.html');

        print_simple_box_end();

        if ($this->usehtmleditor) {
            use_html_editor();
        }

        print_footer($this->course);
    }


    function add_instance($bookings) {
        // Given an object containing all the necessary data,
        // (defined by the form in mod.html) this function
        // will create a new instance and return the id number
        // of the new instance.

        $bookings->timemodified = time();
        $bookings->enddate = make_timestamp($bookings->dueyear, $bookings->duemonth, 
                                            $bookings->dueday, $bookings->duehour, 
                                            $bookings->dueminute);
        $bookings->startdate = make_timestamp($bookings->availableyear, $bookings->availablemonth, 
                                            $bookings->availableday, $bookings->availablehour, 
                                            $bookings->availableminute);

        if ($returnid = insert_record("bookings", $bookings)) {

            $event = NULL;
            $event->name        = $bookings->name;
            $event->description = $bookings->description;
            $event->courseid    = $bookings->course;
            $event->groupid     = 0;
            $event->userid      = 0;
            $event->modulename  = 'bookings';
            $event->instance    = $returnid;
            $event->eventtype   = 'due';
            $event->timestart   = $bookings->startdate;
            $event->timeduration = 0;

            add_event($event);
        }

        return $returnid;
    }

    function delete_instance($bookings) {
        $result = true;
        if (! delete_records('bookings_calendar', 'bookingid', $bookings->id)) {
            $result = false;
        }
        if (! delete_records('bookings', 'id', $bookings->id)) {
            $result = false;
        }

        /// events will be set by the booking sub-modules
	    /// all these will be deleted here
        if (! delete_records('event', 'modulename', 'bookings', 'instance', $bookings->id)) {
            $result = false;
        }

        return $result;
    }

    function update_instance($bookings) {
        // Given an object containing all the necessary data,
        // (defined by the form in mod.html) this function
        // will create a new instance and return the id number
        // of the new instance.

        $bookings->timemodified = time();
        $bookings->timemodified = time();
        $bookings->enddate = make_timestamp($bookings->dueyear, $bookings->duemonth, 
                                            $bookings->dueday, $bookings->duehour, 
                                            $bookings->dueminute);
        $bookings->startdate = make_timestamp($bookings->availableyear, $bookings->availablemonth, 
                                              $bookings->availableday, $bookings->availablehour, 
                                              $bookings->availableminute);

        $bookings->id = $bookings->instance;

        return update_record('bookings', $bookings);
    }




} ////// End of the bookings_base class


/// some utility functions
if (!function_exists('gregoriantojd')) {
function gregoriantojd($mm,$id,$iyyy) {
          if ($iyyy <0) $iyyy += 1;
          if ($mm>2) {
           $jy=$iyyy;
           $jm = $mm+1;
          } else {
           $jy =$iyyy-1;
           $jm = $mm + 13;
          }
          $jul = (int)(365.25*$jy) + (int)(30.6001*$jm)+$id+1720995;
          if ($id+31*($mm+12*$iyyy) >= (15+31*(10+12*1582))) {
           $ja = (int)(0.01 *$jy);
           $jul += (int)(2-$ja+(int)(0.25*$ja));
      }
          return (int)($jul);
}

function jdtogregorian($julian) {
         if ($julian >= (15+31*(10+12*1582))) {
                $jalpha = (int)(($julian -1867216-0.25)/36524.25);
                $ja = (int)($julian +1 + $jalpha - (int)(0.25*$jalpha));
         } else {  $ja = $julian; }
         $jb = (int)($ja + 1524);
         $jc = (int)(6680.0 + ($jb-2439870-122.1)/365.25);
         $jd = (int)(365*$jc+(0.25*$jc));
         $je = (int)(($jb-$jd)/30.6001);
         $id = (int)($jb - $jd -(int)(30.6001*$je));
         $mm = (int)($je-1);
         if ($mm >12) $mm -= 12;
         $iyyy = (int)($jc -4715);
         if ($mm>2) $iyyy -= 1;
         if ($iyyy <= 0) $iyyy -= 1;
         return "$mm/$id/$iyyy";
    }
}
	  

function bookings_w2j($year,$week) {
           $base = gregoriantojd(1,1,$year);
           $rest = $base % 7;
           $start = $base - $rest;
           if ($rest>3) $start += 7;
           return (int)($start + 7*($week-1));
}

function bookings_week($jd=0) {
          if ($jd == 0) {
             list($y,$m,$d) = explode("/",strftime("%Y/%m/%d",time() ));
          } else {
             list($m,$d,$y) = explode("/",jdtogregorian($jd));
          }
          $base = gregoriantojd(1,1,$y);
          $rest = $base % 7;
          $start = $base - $rest;
          if ($rest>3) $start += 7;
          if ($jd < $start) return 0;
          return 1 + (int)(($jd - $start) / 7);
}

?>
