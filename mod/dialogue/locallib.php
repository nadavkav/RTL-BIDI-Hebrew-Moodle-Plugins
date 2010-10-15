<?php  // $Id: locallib.php,v 1.7 2009/03/25 22:50:33 deeknow Exp $

/// Library of extra functions for the dialogue module

// SQL FUNCTIONS ///////////////////////////////////////////////////////////////////


define ('DIALOGUETYPE_TEACHERSTUDENT', 0);
define ('DIALOGUETYPE_STUDENTSTUDENT', 1);
define ('DIALOGUETYPE_EVERYONE', 2);

//////////////////////////////////////////////////////////////////////////////////////
function dialogue_count_closed($dialogue, $user, $viewall=false) {
   
    if ($viewall) {
        $where = 'closed = 1';
    } else {
        $where = '(userid = '.$user->id.' OR recipientid = '.$user->id.') AND closed = 1'; 
    }
    
    return count_records_select("dialogue_conversations", "dialogueid = $dialogue->id AND $where");
}


//////////////////////////////////////////////////////////////////////////////////////
function dialogue_count_open($dialogue, $user) {
    
    return count_records_select("dialogue_conversations", "dialogueid = $dialogue->id AND 
        (userid = $user->id OR recipientid = $user->id) AND closed = 0");
}


//////////////////////////////////////////////////////////////////////////////////////
function dialogue_get_available_users($dialogue, $context, $editconversationid) {

    static $displaystudentindication = false; // display Student indication in multi select lstbox ?

    if (! $course = get_record("course", "id", $dialogue->course)) {
        error("Course is misconfigured");
    }
    $hascapopen = has_capability('mod/dialogue:participate', $context);
    $hascapmanage = has_capability('mod/dialogue:manage', $context);
    
    
    switch ($dialogue->dialoguetype) {
        case DIALOGUETYPE_TEACHERSTUDENT : // teacher to student
            if ($hascapmanage) {
                return dialogue_get_available_students($dialogue, $context, $editconversationid);
            }
            else {
                return dialogue_get_available_teachers($dialogue,  $context, $editconversationid);
            }
        case DIALOGUETYPE_STUDENTSTUDENT: // student to student
            if (! $hascapmanage && $hascapopen) {
                return dialogue_get_available_students($dialogue, $context, $editconversationid);
            }
            else {
                return;
            }
        case DIALOGUETYPE_EVERYONE: // everyone
            if ($teachers = dialogue_get_available_teachers($dialogue, $context, $editconversationid)) {
                foreach ($teachers as $userid=>$name) {
                    $names[$userid] = $name;// ." (".get_string('teacher','dialogue') . ")";
                }
                $names[-1] = "-------------";
            }
            if ($students = dialogue_get_available_students($dialogue, $context, $editconversationid)) {
                foreach ($students as $userid=>$name) {
                    //$names[$userid] = (is_numeric($userid) && $displaystudentindication) ? $name." (".get_string('student','dialogue') . ")" : $name;
		    $names[$userid] = $name;
                }
            }
            if (isset($names)) {
                return $names;
            }
            return;
    }
}

                    
//////////////////////////////////////////////////////////////////////////////////////
function dialogue_get_available_students($dialogue, $context, $editconversationid=0) {
global $USER, $CFG;
    
    if (! $course = get_record("course", "id", $dialogue->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("dialogue", $dialogue->id, $course->id)) {
        error("Course Module ID was incorrect");
    }

    // get the list of teachers (actually, those who have dialogue:manage capability)
    $teachers = array();
    if ($users = get_users_by_capability($context, 'mod/dialogue:manage', '', null, null, null, null, null, null,null,false)) {
        foreach ($users as $user) {
            $teachers[$user->id] = 1;
        }
    }

    $groupid = groups_get_activity_group($cm, true);
    // add current group before list of students if it's the teacher
    if ($teachers[$USER->id]) {
        // show teacher their current group
        if ($groupid) {
            if (!$group = get_record("groups", "id", $groupid)) {
                error("Dialogue get available students: group not found");
            }
            $gnames["g$groupid"] = $group->name;
        }
        $gnames["g0"] = get_string("allparticipants");
        $gnames["spacer"] = "------------";
    }


    // get the students on this course (default sort order)...
    if ($users = get_users_by_capability($context, 'mod/dialogue:participate', null, null, null, null, null, null, null,null,false)) {
        if (!empty($CFG->enablegroupings) && !empty($cm->groupingid) && !empty($users)) {
            $groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id');
            foreach($users as $key => $user) {
                if (!isset($groupingusers[$user->id])) {
                    unset($users[$key]);
                }
            }
        }
        foreach ($users as $otheruser) {
            // ...exclude self and...
            if ($USER->id != $otheruser->id) {

                // ...if not a student (eg co-teacher, teacher) then exclude from students list
                if ($teachers[$otheruser->id] == 1) {
                    continue;
                }

                // ...if teacher and groups then exclude students not in the current group
                if ($teachers[$USER->id] and groupmode($course, $cm) and $groupid) {
                    if (!ismember($groupid, $otheruser->id)) {
                        continue;
                    }
                }

                // ...if student and groupmode is SEPARATEGROUPS then exclude students not in student's group
                if (!$teachers[$USER->id] and (groupmode($course, $cm) == SEPARATEGROUPS)) {
                    if (!ismember($groupid, $otheruser->id)) {
                        continue;
                    }
                }

                // ... and any already in any open conversations unless multiple conversations allowed
                if ($dialogue->multipleconversations or count_records_select("dialogue_conversations", 
                        "dialogueid = $dialogue->id AND id != $editconversationid AND 
                        ((userid = $USER->id AND recipientid = $otheruser->id) OR 
                        (userid = $otheruser->id AND recipientid = $USER->id)) AND closed = 0") == 0) {
                    $names[$otheruser->id] = fullname($otheruser);
                }
            }
        }
    }
    if (isset($gnames)) {
        $list = $gnames;
    }
    if (isset($names)) {
        natcasesort($names);
        if (isset($list)) {
            $list += $names;
        } else {
            $list = $names;
        }
    }
    if (isset($list)) {
        return $list;
    } else {
        return;
    }
}


//////////////////////////////////////////////////////////////////////////////////////
function dialogue_get_available_teachers($dialogue, $context, $editconversationid = 0) {
    global $USER, $CFG;
    $canseehidden = has_capability('moodle/role:viewhiddenassigns', $context);
    if (! $course = get_record("course", "id", $dialogue->course)) {
        error("Course is misconfigured");
        }
    if (! $cm = get_coursemodule_from_instance("dialogue", $dialogue->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
    // get the list of teachers (actually, those who have dialogue:manage capability)
    $hiddenTeachers = array();
    $teachertitle = array();
    if ($users = get_users_by_capability($context, 'mod/dialogue:manage', '', null, null, null, null, null, null,true,null)) {
        foreach ($users as $user) {
            $userRoles = get_user_roles($context, $user->id, true);
            foreach ($userRoles as $role) {
                if ($role->hidden == 1) {
                    $hiddenTeachers[$user->id] = 1;
                    break;
                }
		if ($role->roleid == 3) { 
		  $teachertitle[$user->id] = get_string('teacher','dialogue'); 
		}
		if ($role->roleid == 4) { 
		  $teachertitle[$user->id] = get_string('teacherass','dialogue'); 
		}
            }
        }
        $canSeeHidden = false;
        if (has_capability('moodle/role:viewhiddenassigns', $context)) {
            $canSeeHidden = true;
        }
        $groupid = get_current_group($course->id);
        foreach ($users as $otheruser) {
            // ...exclude self and ...
            if ($USER->id != $otheruser->id) {
                // ...if groupmode is SEPARATEGROUPS then exclude teachers not in student's group
                if ($groupid and (groupmode($course, $cm) == SEPARATEGROUPS)) {
                    if (!ismember($groupid, $otheruser->id)) {
                        continue;
                    }
                }
                if (!$canSeeHidden && array_key_exists($otheruser->id, $hiddenTeachers) && ($hiddenTeachers[$otheruser->id] == 1)) {
                    continue;
                }
                // ...any already in open conversations unless multiple conversations allowed 
                if ($dialogue->multipleconversations or count_records_select("dialogue_conversations", 
                        "dialogueid = $dialogue->id AND id != $editconversationid AND ((userid = $USER->id AND 
                        recipientid = $otheruser->id) OR (userid = $otheruser->id AND 
                        recipientid = $USER->id)) AND closed = 0") == 0) {
                    $names[$otheruser->id] = fullname($otheruser)." {".$teachertitle[$otheruser->id]."}" ;

                }
            }
        }
    }
    if (isset($names)) {
        natcasesort($names);
        return $names;
    }
    return;
}



// OTHER dialogue FUNCTIONS ///////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////
function dialogue_list_conversations_closed($dialogue) {
// list open conversations of the current user awaiting their reply
    global $USER, $CFG;
  
    if (! $course = get_record("course", "id", $dialogue->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("dialogue", $dialogue->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
    $dialoguemanagers = array_keys(get_users_by_capability($context, 'mod/dialogue:manage'));

    $timenow = time();
    $showbutton = false;

    // list the conversations requiring a resonse from this user in full
    if ($conversations = dialogue_get_conversations($dialogue, $USER, 'closed = 1')) {

        // reorder the conversations by (other) name
        foreach ($conversations as $conversation) {
            
            if (in_array($USER->id, $dialoguemanagers)) {
                if (!in_array($conversation->userid, $dialoguemanagers)) {
                    if (!$with = get_record("user", "id", $conversation->userid)) {
                        error("User's record not found");
                    }
                }
                else {
                    if (!$with = get_record("user", "id", $conversation->recipientid)) {
                        error("User's record not found");
                    }                
                }
            } else {
                if ($USER->id != $conversation->userid) {
                    if (!$with = get_record("user", "id", $conversation->userid)) {
                        error("User's record not found");
                    }
                }
                else {
                    if (!$with = get_record("user", "id", $conversation->recipientid)) {
                        error("User's record not found");
                    }                
                }
            }
            $names[$conversation->id] = fullname($with);
        }
        natcasesort($names);
        
        print_simple_box_start("center");
        $table->head = array (get_string("dialoguewith", "dialogue"), get_string("subject", "dialogue"),  
            get_string("numberofentries", "dialogue"), get_string("lastentry", "dialogue"));
        $table->width = "100%";
        $table->align = array ("left", "left", "center", "left");
        $table->size = array ("*", "*", "*", "*");
        $table->cellpadding = 2;
        $table->cellspacing = 0;

        foreach ($names as $cid=>$name) {
            if (!$conversation = get_record("dialogue_conversations", "id", $cid)) {
                error("Closed conversations: could not find conversation record");
            }
            $total = dialogue_count_entries($dialogue, $conversation);
            
            $table->data[] = array("<a href=\"dialogues.php?id=$cm->id&amp;action=printdialogue&amp;cid=$conversation->id\">".
                "$name</a>",  clean_text($conversation->subject), $total,
                userdate($conversation->timemodified));
        }
        print_table($table);
        print_simple_box_end();
    }
}

//////////////////////////////////////////////////////////////////////////////////////
function dialogue_list_conversations_general() {
// list open conversations of the current user from all other courses
    global $USER, $CFG;
  
//     if (! $course = get_record("course", "id", $dialogue->course)) {
//         error("Course is misconfigured");
//     }
//     if (! $cm = get_coursemodule_from_instance("dialogue", $dialogue->id, $course->id)) {
//         error("Course Module ID was incorrect");
//     }
//     
//     $context = get_context_instance(CONTEXT_MODULE, $cm->id);
//     
//     $dialoguemanagers = array_keys(get_users_by_capability($context, 'mod/dialogue:manage'));

    $timenow = time();
    $showbutton = false;

    // list the conversations requiring a resonse from this user in full
    if ($conversations = dialogue_get_conversations_by_user( $USER)) {

        // reorder the conversations by (other) name
        foreach ($conversations as $conversation) {
            
            if (in_array($USER->id, $dialoguemanagers)) {
                if (!in_array($conversation->userid, $dialoguemanagers)) {
                    if (!$with = get_record("user", "id", $conversation->userid)) {
                        error("User's record not found");
                    }
                }
                else {
                    if (!$with = get_record("user", "id", $conversation->recipientid)) {
                        error("User's record not found");
                    }                
                }
            } else {
                if ($USER->id != $conversation->userid) {
                    if (!$with = get_record("user", "id", $conversation->userid)) {
                        error("User's record not found");
                    }
                }
                else {
                    if (!$with = get_record("user", "id", $conversation->recipientid)) {
                        error("User's record not found");
                    }                
                }
            }
            $names[$conversation->id] = fullname($with);
	    $coursenames[$conversation->id] = $conversation->shortname;
       }
        natcasesort($names);
        
        print_simple_box_start("center");
        $table->head = array (get_string("dialoguewith", "dialogue"), get_string("subject", "dialogue"),  
            get_string("numberofentries", "dialogue"), get_string("lastentry", "dialogue"),get_string("coursename", "dialogue"));
        $table->width = "100%";
        $table->align = array ("left", "left", "center", "left", "center");
        $table->size = array ("*", "*", "*", "*", "*");
        $table->cellpadding = 2;
        $table->cellspacing = 0;

        foreach ($names as $cid=>$name) {
            if (!$conversation = get_record("dialogue_conversations", "id", $cid)) {
                error("Closed conversations: could not find conversation record");
            }
            $total = dialogue_count_entries($dialogue, $conversation);
            
            $table->data[] = array("<a href=\"dialogues.php?id=$cm->id&amp;action=printdialogue&amp;cid=$conversation->id\">".
                "$name</a>",  clean_text($conversation->subject), $total,
                userdate($conversation->timemodified),$coursenames[$conversation->id]);
        }
        print_table($table);
        print_simple_box_end();
    }
}

//////////////////////////////////////////////////////////////////////////////////////
function dialogue_print_conversation($dialogue, $conversation) {
// print a conversation and allow a new entry
    global $USER, $CFG;

    if (! $course = get_record("course", "id", $dialogue->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("dialogue", $dialogue->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $dialoguemanagers = array_keys(get_users_by_capability($context, 'mod/dialogue:manage'));

    $timenow = time();
    $showbutton = false;

    
    require_once('dialogue_reply_form.php');
    $mform = new mod_dialogue_reply_form('dialogues.php', array('conversationid' => $conversation->id));
    $mform->set_data(array('id' => $cm->id,
                           'action' => 'insertentries',
                           'pane' => 2));

    $showbutton = true;
    print_simple_box_start("center", "");

    // back navigation button to the list of Conversations
    echo "<div style=\"float:left;\"><input type=button value=\"".get_string('back')."\" onClick=\"history.go(-1)\"></div>";

    echo "<table align=\"center\" border=\"1\" cellspacing=\"0\" valign=\"top\" cellpadding=\"4\" 
        width=\"100%\">\n";
    echo "<tr>";

    echo "<tr><td> ".get_string('from')." : ".fullname($USER)."</td></tr>";
    echo "<tr><td> ".get_string('course')." : $course->shortname </td></tr>";

    echo "<td>".get_string('recipients','dialogue').": ";
    if (in_array($USER->id, $dialoguemanagers)) {
        if (!in_array($conversation->userid, $dialoguemanagers)) {
            if (!$otheruser = get_record("user", "id", $conversation->userid)) {
                error("User's record not found");
            }
        }
        else {
            if (!$otheruser = get_record("user", "id", $conversation->recipientid)) {
                error("User's record not found");
            }                
        }
    } else {
        if ($USER->id != $conversation->userid) {
            if (!$otheruser = get_record("user", "id", $conversation->userid)) {
                error("User's record not found");
            }
        }
        else {
            if (!$otheruser = get_record("user", "id", $conversation->recipientid)) {
                error("User's record not found");
            }                
        }
    }
// used to display only the picture of the current/first user in the list of users that got
// this internal message.
//    $picture = print_user_picture($otheruser->id, $course->id, $otheruser->picture, false, true);
//    echo $picture." <b>".get_string("dialoguewith", "dialogue", fullname($otheruser)).

// display the names and pictures of the recipients that got the same message
    $recipients =  get_records_select("dialogue_conversations", "subject = '$conversation->subject'", "recipientid");
    foreach ($recipients as $recipient) {
      if (!$otherrecipient = get_record("user", "id", $recipient->recipientid)) {
	error("User not found");
      } else {
	//$picture = print_user_picture($otherrecipient->id, $course->id, $otherrecipient->picture, false, true);
	//echo $picture." <b> ". fullname($otherrecipient)."</b>";
	echo " <b> ". fullname($otherrecipient)."</b> |";
      }
    }
    echo "</td>";

    echo "<td>".get_string("deleteconversation", "dialogue")."<a href=\"dialogues.php?id=$cm->id&amp;action=deleteconversation&amp;cid=$conversation->id\">".
	  "<img src=\"$CFG->wwwroot/mod/dialogue/pix/mail-delete.png\" height=11 width=14 alt=\"".
	  get_string("deleteconversation", "dialogue")."\" title=\"".get_string("deleteconversation", "dialogue")."\" >&nbsp</a></td></tr>\n";

//     echo "<td><i>".clean_text($conversation->subject)."&nbsp;</i><br />\n";
//     echo "<div align=\"right\">\n";
//     if (!$conversation->subject) {
//         // conversation does not have a subject, show add subject link
//         echo "<a href=\"dialogues.php?action=getsubject&amp;id=$cm->id&amp;cid=$conversation->id&amp;pane=2\">".
//             get_string("addsubject", "dialogue")."</a>\n";
//         helpbutton("addsubject", get_string("addsubject", "dialogue"), "dialogue");
//         echo "&nbsp; | ";
//     }
//     if (!$conversation->closed && has_capability('mod/dialogue:close', $context)) {
//     echo "<a href=\"dialogues.php?action=confirmclose&amp;id=$cm->id&amp;cid=$conversation->id&amp;pane=2\">".
//         get_string("close", "dialogue")."</a>\n";
//         helpbutton("closedialogue", get_string("close", "dialogue"), "dialogue");
//     }
//     echo "</div></td></tr>\n";
//echo "</tr>\n";

    if ($entries = get_records_select("dialogue_entries", "conversationid = $conversation->id", "id")) {
        $firstentry = true;
        foreach ($entries as $entry) {
            if (!$otheruser = get_record("user", "id", $entry->userid)) {
                error("User not found");
            }
// always CAN EDIT :-)
/*            $canedit = false;
            if (!$conversation->closed && $entry->userid == $USER->id && $timenow < $entry->timecreated+($dialogue->edittime * 60)) {
            	 $canedit = true;
            }
*/
	    $canedit = true;

            if ($entry->timecreated != $entry->timemodified) {
                $modified = get_string('updated', 'dialogue', userdate($entry->timemodified));            
            } else {
                $modified = '';
            }
            
            if ($entry->userid == $USER->id) {
                echo "<tr><td colspan=\"2\" bgcolor=\"#FFFFFF\">\n";
                
                echo "<div class=\"msgheader\" style=\"background-color:Beige;color:Brown;\"><font size=\"1\">".get_string("onyouwrote", "dialogue", 
                            userdate($entry->timecreated).' '.$modified);
                echo ":</font></div>".clean_text($entry->text)."<br />";
		if ($canedit) {
                    if ($firstentry) {
                    	echo "<a href=\"dialogues.php?action=editconversation&amp;id=$cm->id&amp;entryid=$entry->id&amp;pane=2\">[".get_string('edit').']</a>';
                    } else {
                        echo "<a href=\"dialogues.php?action=editreply&amp;id=$cm->id&amp;entryid=$entry->id&amp;pane=2\">[".get_string('edit').']</a>';
                    }
                }
            }
            else {
                echo "<tr><td colspan=\"2\">\n";
                echo "<p><font size=\"1\">".get_string("onwrote", "dialogue", 
                            userdate($entry->timecreated)." $modified ".fullname($otheruser));
                
                echo ":</font></p><br />".clean_text($entry->text);
            }
            echo dialogue_print_attachments($entry);
            echo "</td></tr>\n";
            $firstentry = false;
        }
        
    }
    echo "</table><br />\n";
    if (!$conversation->closed && (has_capability('mod/dialogue:participateany', $context) || $conversation->userid == $USER->id || $conversation->recipientid == $USER->id)) {
        $mform->display();
    }
    
    
    print_simple_box_end();

    if (!$conversation->seenon && $conversation->lastrecipientid == $USER->id) {
        set_field('dialogue_conversations', 'seenon', time(), 'id', $conversation->id); 
    }
    dialogue_mark_conversation_read($conversation->id, $USER->id);
}



function dialogue_list_conversations($dialogue) {
// list open conversations of the current user awaiting their reply
    global $USER, $CFG;
  
    if (! $course = get_record("course", "id", $dialogue->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("dialogue", $dialogue->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
    $dialoguemanagers = array_keys(get_users_by_capability($context, 'mod/dialogue:manage'));
   
    $timenow = time();
    $showbutton = false;
    if (empty($params->order)) {
      $params->order = 'c.timemodified ASC';
    } else {
	$params->order = rtrim(ltrim(optional_param('order','',PARAM_TEXT),"\'"),"\'");
    }

    // list the conversations requiring a resonse from this user in full
    if ($conversations = dialogue_get_conversations_unique_subject($dialogue, $USER, 'closed = 0 ',$params->order )) {

        // reorder the conversations by (other) name
        foreach ($conversations as $conversation) {
            
            if (in_array($USER->id, $dialoguemanagers)) {
                if (!in_array($conversation->userid, $dialoguemanagers)) {
                    if (!$with = get_record("user", "id", $conversation->userid)) {
                        error("User's record not found");
                    }
                }
                else {
                    if (!$with = get_record("user", "id", $conversation->recipientid)) {
                        error("User's record not found");
                    }                
                }
            } else {
                if ($USER->id != $conversation->userid) {
                    if (!$with = get_record("user", "id", $conversation->userid)) {
                        error("User's record not found");
                    }
                }
                else {
                    if (!$with = get_record("user", "id", $conversation->recipientid)) {
                        error("User's record not found");
                    }                
                }
            }
            $names[$conversation->id] = fullname($with);

        }

	// used to sord by names, i disabled it to have the table be sorted by clicking the arrows
	// on each column's header title.
        //natcasesort($names);
        
        print_simple_box_start("center");
        $table->head = array (get_string("dialoguewith", "dialogue"), get_string("subject", "dialogue"),  
           /*  get_string("numberofentries", "dialogue"), get_string("unread", "dialogue"),*/ get_string("lastentry", "dialogue") .
	      "<a href=\"view.php?id=$cm->id&amp;pane=2&amp;order='c.timemodified ASC'\">" .
	      "<img src=\"$CFG->wwwroot/mod/dialogue/pix/arrow-down.png\" height=16 width=16></a>" .
	      "<a href=\"view.php?id=$cm->id&amp;pane=2&amp;order='c.timemodified DESC'\">" .
	      "<img src=\"$CFG->wwwroot/mod/dialogue/pix/arrow-up.png\" height=16 width=16></a>"
	    , get_string("actions", "dialogue"));
        $table->width = "80%";
        $table->align = array ("left",/* "left", "center",*/ "left", "left","center");
        $table->size = array ("*", /*"*", "*",*/ "*", "*","*");
        $table->cellpadding = 2;
        $table->cellspacing = 0;

        foreach ($names as $cid=>$name) {
            $conversation = $conversations[$cid];
	    
	    $hasattachmenticon = "&nbsp";
	    if ($entries = get_records_select("dialogue_entries", "conversationid = $conversation->id", "id")) {
	      foreach ($entries as $entry) {
		if (!empty($entry->attachment)) { 
		  $hasattachmenticon = "&nbsp<img src=\"$CFG->wwwroot/mod/dialogue/pix/paperclip.gif\" height=11 width=14>&nbsp"; 
		}
	      }
	    }
     
            if ($conversation->total-$conversation->readings > 0) {
		$mailstatusicon = "pix/pmmail.gif";
                $unread = '<span class="unread">'.($conversation->total-$conversation->readings).'</span>';
            } else {
                $unread = 0;
		$mailstatusicon = "pix/answermail.gif";
            }
	$actions = "&nbsp";
        $actions .= "<a href=\"dialogues.php?id=$cm->id&amp;action=deleteconversation&amp;cid=$conversation->id\">".
	  "<img src=\"$CFG->wwwroot/mod/dialogue/pix/mail-delete.png\" height=11 width=14 alt=\"".get_string("deleteconversation", "dialogue")."\" title=\"".get_string("deleteconversation", "dialogue")."\" >&nbsp</a>";
//        $actions .= "<a href=\"dialogues.php?id=$cm->id&amp;action=confirmclose&amp;cid=$conversation->id&pane=2\">".
//	  "<img src=\"$CFG->wwwroot/mod/dialogue/pix/mail-archive.png\" height=11 width=14 alt=\"".get_string("archiveconversation", "dialogue")."\" title=\"".get_string("archiveconversation", "dialogue")."\" >&nbsp</a>";
 
            $table->data[] = array($name,  "<img src=\"$CFG->wwwroot/mod/dialogue/$mailstatusicon\" height=14 width=15>&nbsp" .
	      "<a href=\"dialogues.php?id=$cm->id&amp;action=printdialogue&amp;cid=$conversation->id\">".clean_text($conversation->subject)."</a>" .$hasattachmenticon,
	     /* $conversation->total, $unread,*/ userdate($conversation->timemodified), $actions);
        }
        print_table($table);
        print_simple_box_end();
    }
}

// acceptable option for $inout = { 'incoming' || 'outgoing' }
function dialogue_list_conversations_inout($dialogue,$inout) {
// list open conversations of the current user awaiting their reply
    global $USER, $CFG;
  
    if (! $course = get_record("course", "id", $dialogue->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("dialogue", $dialogue->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
    $dialoguemanagers = array_keys(get_users_by_capability($context, 'mod/dialogue:manage'));

    $params->pane = optional_param('pane','',PARAM_TEXT); 

    $timenow = time();
    $showbutton = false;
    if (empty($params->order)) {
      $params->order = 'c.timemodified ASC';
    } else {
	$params->order = rtrim(ltrim(optional_param('order','',PARAM_TEXT),"\'"),"\'");
    }

    // list the conversations requiring a resonse from this user in full
    if ($conversations = dialogue_get_conversations_unique_subject($dialogue, $USER, 'closed = 0 ',$params->order )) {
        // reorder the conversations by (other) name
        foreach ($conversations as $conversation) {

	    if ($inout == 'outgoing' && !($USER->id == $conversation->userid)) continue;
	    if ($inout == 'incoming' && !($USER->id == $conversation->recipientid)) continue;

            if (in_array($USER->id, $dialoguemanagers)) {
                if (!in_array($conversation->userid, $dialoguemanagers)) {
                    if (!$with = get_record("user", "id", $conversation->userid)) {
                        error("User's record not found");
                    }
                }
                else {
                    if (!$with = get_record("user", "id", $conversation->recipientid)) {
			//continue;
                        error("User's record not found");
                    }                
                }
            } else {
                if ($USER->id != $conversation->userid) {
                    if (!$with = get_record("user", "id", $conversation->userid)) {
                        error("User's record not found");
                    }
                }
                else {
                    if (!$with = get_record("user", "id", $conversation->recipientid)) {
                        error("User's record not found");
                    }                
                }
            }
            $names[$conversation->id] = fullname($with);

        }

	// used to sord by names, i disabled it to have the table be sorted by clicking the arrows
	// on each column's header title.
        //natcasesort($names);
        
        print_simple_box_start("center");
        $table->head = array (get_string("dialoguewith", "dialogue"), get_string("subject", "dialogue"),  
           /*  get_string("numberofentries", "dialogue"), get_string("unread", "dialogue"),*/ get_string("lastentry", "dialogue") .
	      "<a href=\"view.php?id=$cm->id&amp;pane=$params->pane&amp;order='c.timemodified ASC'\">" .
	      "<img src=\"$CFG->wwwroot/mod/dialogue/pix/arrow-down.png\" height=16 width=16></a>" .
	      "<a href=\"view.php?id=$cm->id&amp;pane=$params->pane&amp;order='c.timemodified DESC'\">" .
	      "<img src=\"$CFG->wwwroot/mod/dialogue/pix/arrow-up.png\" height=16 width=16></a>"
	    , get_string("actions", "dialogue"));
        $table->width = "80%";
        $table->align = array ("left",/* "left", "center",*/ "left", "left","center");
        $table->size = array ("*", /*"*", "*",*/ "*", "*","*");
        $table->cellpadding = 2;
        $table->cellspacing = 0;

        foreach ($names as $cid=>$name) {
            $conversation = $conversations[$cid];
	    
	    $hasattachmenticon = "&nbsp";
	    if ($entries = get_records_select("dialogue_entries", "conversationid = $conversation->id", "id")) {
	      foreach ($entries as $entry) {
		if (!empty($entry->attachment)) { 
		  $hasattachmenticon = "&nbsp<img src=\"$CFG->wwwroot/mod/dialogue/pix/paperclip.gif\" height=11 width=14>&nbsp"; 
		}
	      }
	    }
     
            if ($conversation->total-$conversation->readings > 0) {
		$mailstatusicon = "pix/pmmail.gif";
                $unread = '<span class="unread">'.($conversation->total-$conversation->readings).'</span>';
            } else {
                $unread = 0;
		$mailstatusicon = "pix/answermail.gif";
            }
	$actions = "&nbsp";
        $actions .= "<a href=\"dialogues.php?id=$cm->id&amp;action=deleteconversation&amp;cid=$conversation->id\">".
	  "<img src=\"$CFG->wwwroot/mod/dialogue/pix/mail-delete.png\" height=11 width=14 alt=\"".get_string("deleteconversation", "dialogue")."\" title=\"".get_string("deleteconversation", "dialogue")."\" >&nbsp</a>";
//        $actions .= "<a href=\"dialogues.php?id=$cm->id&amp;action=confirmclose&amp;cid=$conversation->id&pane=2\">".
//	  "<img src=\"$CFG->wwwroot/mod/dialogue/pix/mail-archive.png\" height=11 width=14 alt=\"".get_string("archiveconversation", "dialogue")."\" title=\"".get_string("archiveconversation", "dialogue")."\" >&nbsp</a>";
 
            $table->data[] = array($name,  "<img src=\"$CFG->wwwroot/mod/dialogue/$mailstatusicon\" height=14 width=15>&nbsp" .
	      "<a href=\"dialogues.php?id=$cm->id&amp;action=printdialogue&amp;cid=$conversation->id\">".clean_text($conversation->subject)."</a>" .$hasattachmenticon,
	     /* $conversation->total, $unread,*/ userdate($conversation->timemodified), $actions);
        }
        print_table($table);
        print_simple_box_end();
    }
}

function dialogue_mark_conversation_read($conversationid, $userid) {
    global $CFG;

    $lastread = time();

    // Update any previously seen entries in this conversaion
    set_field('dialogue_read', 'lastread', $lastread, 'conversationid', $conversationid, 'userid', $userid);

    $sql = "SELECT e.id FROM {$CFG->prefix}dialogue_entries e
                LEFT JOIN {$CFG->prefix}dialogue_read r ON e.id = r.entryid AND r.userid = $userid 
            WHERE e.conversationid = $conversationid AND r.id IS NULL ";


    if ($unread = get_records_sql($sql)) {
        foreach($unread as $entry) {
            $read = new stdClass;
            $read->conversationid = $conversationid;
            $read->entryid        = $entry->id;
            $read->userid         = $userid;
            $read->firstread      = $lastread;
            $read->lastread       = $lastread;

            insert_record('dialogue_read', $read);
        }
    }
}
?>
