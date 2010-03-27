<?PHP //$Id: block_participants.php,v 1.33.2.2 2008/03/03 11:41:03 moodler Exp $

class block_myclass_blogs extends block_list {
    function init() {
        $this->title = get_string('blockname','block_myclass_blogs');
        $this->version = 2009101509;
    }

    function get_content() {

        global $CFG, $COURSE ,$USER;

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        // the following 3 lines is need to pass _self_test();
        if (empty($this->instance->pageid)) {
            return '';
        }
        
        $this->content = new object();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        
        /// MDL-13252 Always get the course context or else the context may be incorrect in the user/index.php
        if (!$currentcontext = get_context_instance(CONTEXT_COURSE, $COURSE->id)) {
            $this->content = '';
            return $this->content;
        }
        
        if ($COURSE->id == SITEID) {
            if (!has_capability('moodle/site:viewparticipants', get_context_instance(CONTEXT_SYSTEM))) {
                $this->content = '';
                return $this->content;
            }
        } else {
            if (!has_capability('moodle/course:viewparticipants', $currentcontext)) {
                $this->content = '';
                return $this->content;
            }
        }

        // a nice sample of how to get a data from a table:
        $uif = get_field('user_info_field', 'id', 'shortname', 'class');
        //echo "uif = $uif<br/>";
        $currentuserclass = get_field('user_info_data', 'data', 'userid', $USER->id,'fieldid',$uif);
        //echo "udf data (class) = $udf<br/>";

        $courseusers = get_course_users($COURSE->id);
        foreach ($courseusers as $cuser) {
        $currentclass = get_field('user_info_data', 'data', 'userid', $cuser->id,'fieldid',$uif);
            if ( $currentclass == $currentuserclass ) {
                $this->content->items[] = "<a href=\"$CFG->wwwroot/blog/index.php?userid=$cuser->id&courseid=$COURSE->id\" >$cuser->firstname $cuser->lastname</a>";
                $this->content->icons[] = "<img src=\"$CFG->wwwroot/user/pix.php/$cuser->id/f2.jpg\" class=\"icon\" alt=\"\" />";
            }
        }

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    function applicable_formats() {
        return array('all' => true, 'my' => false, 'tag' => false);
    }

}

?>