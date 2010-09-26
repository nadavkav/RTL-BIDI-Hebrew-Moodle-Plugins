<?php //$Id: block_birthday.php,v 1.7.2.1 2008/04/16 22:44:22 arborrow Exp $

/**
 * An elementary way of getting a list of the students with birthdays
 * based off of the site_online_users block
 */
function get_month_day($date, $format='ISO') {

        $year = date('Y');

        switch ($format) {
            case 'USA':
                $period = explode('.',$date);
                $month = $period[0];
                $day = $period[1];
                break;
            case 'ISO':
                $period = explode('-',$date);
                $month = $period[1];
                $day = $period[2];
                break;
            case 'EUR':
                $period = explode('.',$date);
                $month = $period[1];
                $day = $period[0];
                break;
            default : error('Invalid field type');
        }
            $return = date('M d',mktime(0,0,0,$month,$day,$year));
            return $return;

    }

class block_birthday extends block_base {
    function init() {
        $this->title = get_string('birthday','block_birthday'); //can be used for multiple languages as it gets developed further
        $this->version = 2008041601;
    }
    // $date a string value of the user profile field data
    // $format is a string of the $dateformat - either ISO, USA, or EUR

    function has_config() {return true;}

    function config_save($data) {
        foreach ($data as $name => $value) {
            set_config($name, $value,'block/birthday');
        }
        return true;
    }

    function applicable_formats() {
        return array('all' => true, 'my' => false, 'tag' => false);
    }

    function get_content() {
        global $USER, $CFG, $COURSE;
        $cfg_birthday = get_config('block/birthday');

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

// make sure config variables are defined - if not, set them to default values

        if (!isset($cfg_birthday->block_birthday_fieldname)) {
          $fieldname = 'DOB';            //this is the default
         }
          else {
           $fieldname = $cfg_birthday->block_birthday_fieldname ;
          }

        if (!isset($cfg_birthday->block_birthday_dateformat)) {
          $dateformat = 'ISO';            //this is the default
         }
          else {
           $dateformat = $cfg_birthday->block_birthday_dateformat ;
          }

        if (!isset($cfg_birthday->block_birthday_days)) {
          $days = 0; //this is the default
         }
          else {
           $days = $cfg_birthday->block_birthday_days ;
          }

		echo '<style type="text/css">';
		include ($CFG->dirroot."/blocks/birthday/styles.css");
		echo '</style>';

// get the field id for the given fieldname
        if (isset($fieldname)) {
			$sql = "SELECT * FROM {$CFG->prefix}user_info_field WHERE shortname='{$fieldname}'" ;
        }
		if ($field = get_record_sql($sql)) {
			$fieldid = $field->id;
		} else {
			$fieldid = 0; //no custom profile field with that shortname was found
		}

        // Get context so we can check capabilities.
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        //Calculate if we are in separate groups
        $isseparategroups = ($COURSE->groupmode == SEPARATEGROUPS
                             && $COURSE->groupmodeforce
                             && !has_capability('moodle/site:accessallgroups', $context));

  //Get the user current group
        $currentgroup = $isseparategroups ? groups_get_course_group($COURSE) : NULL;

        $groupmembers = "";
        $groupselect = "";

        //Add this to the SQL to show only group users
        if ($currentgroup !== NULL) {
            $groupmembers = ",  {$CFG->prefix}groups_members gm ";
            $groupselect = " AND u.id = gm.userid AND gm.groupid = '$currentgroup'";
        }

        $users = array();
// there are probably more eloquent ways of getting the current user's time/date information
        for ($i=0; $i <= $days; $i++) {
        $userdate = usergetdate((time()+($i*86400)),$USER->timezone);
        $usermonth = $userdate['mon'];
        $userday = $userdate['mday'];
        $SQL = "SELECT u.id, u.username, u.firstname, u.lastname, u.picture, u.lastaccess, ud.data
                FROM {$CFG->prefix}user_info_data ud,
                     {$CFG->prefix}user u
                WHERE
                      ud.userid = u.id
                      AND month(STR_TO_DATE(ud.data,'%d/%m/%Y')) = $usermonth
                      AND day(STR_TO_DATE(ud.data,'%d/%m/%Y')) = $userday
                      AND ud.fieldid={$fieldid}
                      AND u.deleted = 0
                ORDER BY ud.data, u.lastname, u.firstname ASC";

        $pcontext = get_related_contexts_string($context);
        if ($pusers = get_records_sql($SQL, 0, 50)) {   // We'll just take the most recent 50 maximum
            foreach ($pusers as $puser) {
                // if current user can't view hidden role assignment in this context and
                // user has a hidden role assigned at this context or any parent contexts,
                // ignore this user

                $SQL = "SELECT id,id FROM {$CFG->prefix}role_assignments
                        WHERE userid = $puser->id
                        AND contextid $pcontext
                        AND hidden = 1";

                if (!has_capability('moodle/role:viewhiddenassigns', $context) && record_exists_sql($SQL)) {
                    // can't see this user as the current user has no capability
                    // and this user has a hidden assignment at this context or higher
                    continue;
                }

                if ($COURSE->id == SITEID) {
                    ;  // Site-level
                } else { // Course-level
                    if ((!has_capability('moodle/course:viewparticipants', $context, $USER->id)) or (!has_capability('moodle/course:view',$context, $puser->id))) {
                        continue;
                    }
                }

                $puser->fullname = fullname($puser);
                $users[$puser->id] = $puser;
            }
        }
        } //end of for i loop

        //Calculate minutes
//        $minutes  = floor($timetoshowusers/60);

        $curday = date('M j', time()); //initialize current day
            //Accessibility: Don't want 'Alt' text for the user picture; DO want it for the envelope/message link (existing lang string).
            //Accessibility: Converted <div> to <ul>, inherit existing classes & styles.

        //Now, we have in users, the list of users to show
        //Because it is their birthday
        if (!empty($users)) {

            $this->content->text = '<div class="info">'.get_string("block_title","block_birthday").'</div>';
            $this->content->text .= '<ul class="list">';

            foreach ($users as $user) {
                if ($curday == get_month_day($user->data,$dateformat)) {
                    $this->content->text .= '<li class="listentry">';
                    if ($user->username == 'guest') {
                        $this->content->text .= '<div class="user">'.print_user_picture($user->id, $COURSE->id, $user->picture, 16, true, false, '', false);
                        $this->content->text .= get_string('guestuser').'</div>';
                    } else {
                        $this->content->text .= '<div class="user"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$COURSE->id.'">';
                        $this->content->text .= print_user_picture($user->id, $COURSE->id, $user->picture, 16, true, false, '', false);
                        $this->content->text .= $user->fullname.'</a></div>';
                    }
                    if (!empty($USER->id) and ($USER->id != $user->id) and !empty($CFG->messaging) and
                        !isguest() and $user->username != 'guest') {  // Only when logged in and messaging active etc
                        $this->content->text .= '<div class="message"><a title="'.get_string('messageselectadd').'" href="'.$CFG->wwwroot.'/message/discussion.php?id='.$user->id.'" onclick="this.target=\'message_'.$user->id.'\';return openpopup(\'/message/discussion.php?id='.$user->id.'\', \'message_'.$user->id.'\', \'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500\', 0);">'
                            .'<img class="iconsmall" src="'.$CFG->pixpath.'/t/message.gif" alt="'. get_string('messageselectadd') .'" /></a></div>';
                    }

                    $this->content->text .= '</li>';
                } else {
                    $this->content->text .= '</ul><div class="clearer"><!-- --></div>';

                    $curday = get_month_day($user->data,$dateformat); //if we have switched days then set the current day to the new day
                    $this->content->text .= '<div class="info">'.$curday.'</div>';
                    $this->content->text .= '<ul class="list">';
                    $this->content->text .= '<li class="listentry">';

                    if ($user->username == 'guest') {
                        $this->content->text .= '<div class="user">'.print_user_picture($user->id, $COURSE->id, $user->picture, 16, true, false, '', false);
                        $this->content->text .= get_string('guestuser').'</div>';
                    } else {
                        $this->content->text .= '<div class="user"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$COURSE->id.'">';
                        $this->content->text .= print_user_picture($user->id, $COURSE->id, $user->picture, 16, true, false, '', false);
                        $this->content->text .= $user->fullname.'</a></div>';
                    }
                    if (!empty($USER->id) and ($USER->id != $user->id) and !empty($CFG->messaging) and
                        !isguest() and $user->username != 'guest') {  // Only when logged in and messaging active etc
                        $this->content->text .= '<div class="message"><a title="'.get_string('messageselectadd').'" href="'.$CFG->wwwroot.'/message/discussion.php?id='.$user->id.'" onclick="this.target=\'message_'.$user->id.'\';return openpopup(\'/message/discussion.php?id='.$user->id.'\', \'message_'.$user->id.'\', \'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500\', 0);">'
                            .'<img class="iconsmall" src="'.$CFG->pixpath.'/t/message.gif" alt="'. get_string('messageselectadd') .'" /></a></div>';
                    }

                    $this->content->text .= '</li>';
                }
            }
            $this->content->text .= '</ul><div class="clearer"><!-- --></div>';
       } else {
            $this->content->text .= '<div class="info">'.get_string("nobirthdays","block_birthday").'</div>';
            if (!empty($cfg_birthday->block_birthday_visible)) {
                if ($cfg_birthday->block_birthday_visible=='Hide') { //block is hidden when empty
                    $this->content->text = '';
                }
            }
        }
        return $this->content;
    } //get_content
}

?>
