<?php // $Id: assignment.class.php,v 1.6 2005/04/21 12:44:19 moodler Exp $

/**
 * Extend the base assignment class for offline assignments
 *
 */
class bookings_appointment extends bookings_base {

    function bookings_appointment($cmid=0) {
        parent::bookings_base($cmid);
    }
    
    function add_instance($bookings) {
    /// set up a new item with properties for this appointment
        unset($item);
        $item->type = 'appointment';
        $item->name = $bookings->name;
        $item->parent = 0;
        $itemid = insert_record('bookings_item', $item);
        $bookings->itemid = $itemid;
        unset($prop);
        $prop->itemid = $itemid;
        $prop->name = 'days';
        $prop->value = $bookings->colnames;
        insert_record('bookings_item_property', $prop);
        $prop->name = 'slots';
        $prop->value = $bookings->rownames;
        insert_record('bookings_item_property', $prop);
        $prop->name = 'multiple';
        $prop->value = $bookings->multiple;
        insert_record('bookings_item_property', $prop);
        $prop->name = 'exclusive';
        $prop->value = $bookings->exclusive;
        insert_record('bookings_item_property', $prop);
        $prop->name = 'edit_group';
        $prop->value = $bookings->editgroup;
        insert_record('bookings_item_property', $prop);
        $p = parent::add_instance($bookings);
        return $p;
    }


    function update_instance($bookings) {
    /// set up a new item with properties for this appointment
        unset($prop);
        $book = get_record('bookings', 'id', $bookings->instance);
        $itemid = $book->itemid;
        $prop->itemid = $itemid;
        delete_records('bookings_item_property','itemid',$itemid);
        $prop->name = 'days';
        $prop->value = $bookings->colnames;
        insert_record('bookings_item_property', $prop);
        $prop->name = 'slots';
        $prop->value = $bookings->rownames;
        insert_record('bookings_item_property', $prop);
        $prop->name = 'multiple';
        $prop->value = $bookings->multiple;
        insert_record('bookings_item_property', $prop);
        $prop->name = 'exclusive';
        $prop->value = $bookings->exclusive;
        insert_record('bookings_item_property', $prop);
        $prop->name = 'edit_group';
        $prop->value = $bookings->editgroup;
        insert_record('bookings_item_property', $prop);
        $p = parent::update_instance($bookings);
        return $p;
    }
    
    function delete_instance($bookings) {
        $itemid = $bookings->itemid;
        if (! delete_records('bookings_item', 'id', $itemid)) {
            $p = false;
        }
        if (! delete_records('bookings_item_property', 'itemid', $itemid)) {
            $p = false;
        }
        $p = parent::delete_instance($bookings) and $p;
        return $p;
    }

    function display_lateness($timesubmitted) {
        return '';
    }

    function view_dates() {
        global $CFG,$USER;
        $rday           = optional_param('rday',NULL, PARAM_INT);
        $rslot          = optional_param('rslot',NULL, PARAM_INT);
        $delete         = optional_param('delete',NULL, PARAM_INT);
        $resid          = optional_param('resid',NULL, PARAM_INT);
        $uid            = optional_param('uid',NULL, PARAM_INT);
        require_once("../../config.php");

        $cmid = $this->cm->id;
        if (! $course = get_record('course', 'id', $this->cm->course)) {
            error('Course is misconfigured');
        }


        $itemid = $this->bookings->itemid;
        $username = $USER->username;
        $UID = $USER->id;
        $firstname = $USER->firstname;
        $lastname = $USER->lastname;
        if ($firstname == '' or $lastname == '') return "";
        $html .= '<form name=myform id=myform method=post action="view.php?id='.$cmid.'">';

        $proplist = bookings_item_properties($itemid);
    
        $days = explode(',','A,B,C,D,E,F');
        $daylimits = array();
        if (isset($proplist['days']) and $proplist['days'] != '') {
            $days = explode(',',$proplist['days']);
            $dag = 0;
            foreach ($days as $day) {
                list($dy,$lim) = explode(':',$day);   // pick out optional size limit
                $days[$dag] = $dy;
                $daylimits[$dag] = (int)$lim;
                $dag++;
            }
        }
        $widthprcent = (int)(95 / count($days) ) ;    // default width

        $slots = explode(',','1,2,3,4,5,6');
        $slotlimits = array();
        if (isset($proplist['slots']) and $proplist['slots'] != '') {
            $slots = explode(',',$proplist['slots']);
            $slt = 0;
            foreach ($slots as $slot) {
                list($sl,$lim) = explode(':',$slot);   // pick out optional size limit
                $slots[$slt] = $sl;
                $slotlimits[$slt] = (int)$lim;
                $slt++;
            }
        }
    
        $multiple = 0;         
        if (isset($proplist['multiple'])) {
            $multiple = (int)$proplist['multiple'];
        }

        $exclusive = 'non';      // many entries pr user
        if (isset($proplist['exclusive'])) {
            $exclusive = $proplist['exclusive'];
        }


        $can_edit = 1;          // any user can make a booking
        if (isset($proplist['edit_group'])) {
            $can_edit = isadmin() ? 1 : 0;   
            if ($proplist['edit_group'] == 'teachers' and isteacherinanycourse($USER->id) ) {
                $can_edit = 1;
            } else if ($proplist['edit_group'] == 'students') {
                $can_edit = 1;
            } else {
                $can_edit = isteacherinanycourse($USER->id) ? 1 : 0;   // default is teachers can make a booking
            }
        }
        
        $privilege = (isteacherinanycourse($USER->id)) ? (isadmin() ? 2 : 1 ) : 0;

        /// here we fetch out all reservations
        $sql = 'SELECT * FROM '.$CFG->prefix.'bookings_calendar 
                WHERE eventtype="reservation" 
                AND bookingid='.$this->bookings->id;
        $reservation = array();            
        $daycount = array();    // count of reservations pr day (colcount)
        $slotcount = array();   // count of reservations pr slot (rowcount)
        $total = 0;
        if ($res = get_records_sql($sql)) {
            foreach($res as $re) {
                $reservation[$re->day][$re->slot][] = $re;
                $reservation[$re->day][$re->slot]->res = $re;
                $daycount[$re->day] += 1;
                $slotcount[$re->slot] += 1;
                $total ++;
            }
        }



        /// this is where we make the reservation or delete reservations
        if ( ( (isset($resid) and isset($uid)) or (isset($rday) and isset($rslot) )) and $can_edit ) {
            if (!isteacherinanycourse($USER->id) AND !isadmin() and isset($reservation[$rday][$rslot]) ) { 
                if ($uid != $UID) {
                    // return;  // assume an attempt to phreak the system with params 
                }
            }
            /// exclusive decides if and how multiple bookings made by one user is handled
            /// default is that a user can book once in any available slot
            switch($exclusive) {
                case 'row':     /// only one booking pr row
                    $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar WHERE eventtype="reservation" AND userid='.$UID.' AND slot='.$rslot;
                    break;
                case 'rowcol':  /// only one booking in this row+col
                    $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar WHERE eventtype="reservation" AND userid='.$UID.' AND (day='.$rday.' OR slot='.$rslot.')';
                    break;
                case 'col':     /// only one booking pr col
                    $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar WHERE eventtype="reservation" AND userid='.$UID.' AND day='.$rday;
                    break;
                case 'all':     /// only one booking
                    $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar WHERE eventtype="reservation" AND userid='.$UID;
                    break;
                default:
                    $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar WHERE eventtype="reservation" AND slot='.$rslot.' AND day='.$rday.' AND userid='.$UID;
            }
            execute_sql($sql,0);   /// this removes multiple bookings by one person for a given slot (or all bookings if exclusive)
            if (isset($resid)) {
                $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar WHERE id='.$resid;
                execute_sql($sql,0);
            }
            if (!isset($delete) ) {
                $sql = 'INSERT INTO '.$CFG->prefix.'bookings_calendar (bookingid,name,value,userid,eventtype,slot,day) 
                    VALUES ('.$this->bookings->id.',"'.$username.'","'.$username.'",'
                    .$UID.',"reservation",'.$rslot.','.$rday.')';
                execute_sql($sql,0);
            }
            // have to refetch data
            $sql = 'SELECT * FROM '.$CFG->prefix.'bookings_calendar 
                WHERE eventtype="reservation" 
                AND bookingid='.$this->bookings->id;
            $reservation = array();            
            $daycount = array();    // count of reservations pr day (colcount)
            $slotcount = array();   // count of reservations pr slot (rowcount)
            $total = 0;             // total number of reservations
            if ($res = get_records_sql($sql)) {
                foreach($res as $re) {
                    $reservation[$re->day][$re->slot][] = $re;
                    $daycount[$re->day] += 1;
                    $slotcount[$re->slot] += 1;
                    $total ++;
                }
            }
        }
    

    
    
        $html .=  '<div id="all">';


        // now we draw up the table
        $table = array();
        $lastrow = '';
        if ($can_edit) {
            $baselink = '<a href="view.php?id='.$cmid;
        } else {
            $baselink = '';
        }
        $table[] = '<table border=1 width=100%>';
        $table[] = '<tr><th width=5%>&nbsp;</th>';
        $dag = 0;
        foreach ($days as $day) {
            if ($day == '') continue;
            $table[] = '<th>'.$day.'</th>';
            $lastrow .= "<td>" . $daycount[$dag] . "&nbsp;</td>\n";
            $dag++;
        }
        $table[] = '</tr>';
        $time = 0;
        foreach($slots as $slottime) {
            $t = $time + 1;
            $table[] = "<tr><th class=\"number\"><span class=\"time\">$slottime</span></th>";
            $dag = 0;
            $scanedit = ( $slotlimits[$time] == 0 or ($slotlimits[$time] > $slotcount[$time] )) ? $can_edit : 0;
            foreach ($days as $day) {
                $canedit = $scanedit;
                $class = 'normal'; 
                if ($day != '' ) {
                    $canedit = ( $daylimits[$dag] == 0 or ($daylimits[$dag] > $daycount[$dag] )) ? $canedit : 0;
                    if ($tp[$dag][$time] == '') {
                        $class = 'free';
                        $tp[$dag][$time] = $canedit ? $baselink.'&rday='.$dag.'&rslot='.$time.'">'.get_string('free','bookings').'</a>' : get_string('free','bookings');
                    }            
                    if (isset($reservation[$dag][$time])) {
                    $tp[$dag][$time] = '';
                    foreach ($reservation[$dag][$time] as $myres) {
                        $class = 'reserved';
                        $linktext = 'Reserved '.$myres->value;
                        if ($myres->userid == $UID) {
                            $linktext = 'M';
                        } else if (sizeof($reservation[$dag][$time]>1)) {
                            $linktext = isteacherinanycourse($myres->userid) ? 'T' : 'S';
                        }
                        // admin can override any, teacher can override student 
                        if ($myres->userid == $UID or isadmin() or (isteacherinanycourse($USER->id) and !isteacherinanycourse($myres->userid) )) {
                            $tp[$dag][$time] .= $can_edit ? $baselink.'&delete=1&resid='.$myres->id.'&uid='.$myres->userid.'"
                            title="'.$myres->value.'" >'.$linktext.'</a> ' : $linktext.$myres->value;
                        } else {
                            $tp[$dag][$time] .= '<span title="'.$myres->value.'">'.$linktext.' </span>';
                        }
                    }  
                    if (isset($multiple) and $multiple > sizeof($reservation[$dag][$time]) ) {
                        /// $tp[$dag][$time] .= ' ' .$reservation[$dag][$time]->count . ' ';
                        $tp[$dag][$time] .= $canedit ? $baselink.'&rday='.$dag.'&rslot='
                                        .$time.'">'.get_string('free','bookings').'</a>' : get_string('free','bookings');
                    }
                    }
                    $table[] = "<td width=\"$widthprcent%\" class=\"$class\" >" . $tp[$dag][$time] . "&nbsp;</td>\n";
                }
                $dag ++;
            }
            if ($privilege > 0) {
                $table[] = "<td>" . $slotcount[$time] . "&nbsp;</td>\n";
            }                
            $table[] = "</tr>\n";
            $idx ++;
            $time += 1;
        }
        if ($privilege > 0) {
            $table[] = "<tr><td></td>" . $lastrow . "<td>$total</td></tr>";
        }
        $table[] = "</table>\n";
        $html .= implode("",$table);
        $html .=  '<input type="hidden" name="itemid" value="'.$itemid.'">';
        $html .=  '<input type="hidden" name="jday" value="'.$jday.'">';
        $html .= '</div>';  // end div=all
    
        print $html;
    
        if ($privilege > 0) {
            unset($table);
	        $table->head = array('&nbsp;', get_string('name'));
            $table->align = array('center', 'left');
            $table->wrap = array('nowrap', 'nowrap');
            $table->width = '100%';
            $table->size = array(10, '*');
    	    $table->head[] = get_string('email');
	        $table->align[] = 'center';
	        $table->wrap[] = 'nowrap';
            $table->size[] = '*';
	        $table->head[] = get_string('reservation','bookings');
	        $table->align[] = 'center';
	        $table->wrap[] = 'nowrap';
            $table->size[] = '*';
	        $table->head[] = get_string('choice','bookings');
		    $table->align[] = 'center';
		    $table->wrap[] = 'nowrap';
		    $table->size[] = '*';
    
            // $books = get_records('calendar', 'bookingid', $this->bookings->id);
            if ($books = get_records_sql("SELECT r.*, u.firstname, u.lastname, u.picture, u.email
                                FROM {$CFG->prefix}bookings_calendar r,
                                    {$CFG->prefix}user u
                                WHERE r.bookingid = '{$this->bookings->id}' 
                                AND r.userid = u.id ORDER BY r.day,r.slot"))  {
    	        foreach ($books as $request) {
		            $row = array();
		            $row[] = print_user_picture($request->userid, $course->id, $request->picture, 0, true);
		            $row[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$request->userid.'&course='.$course->id.'">'
			            .$request->lastname.' '.$request->firstname.'</a>';
		            $row[] = obfuscate_mailto($request->email);
		            $row[] = $days[$request->day];
		            $row[] = $slots[$request->slot];
	                $table->data[] = $row;
    	        }
                print "<p>";
	            print_table($table);
            }
        }
        print "</form>";
    	return;
    }

}

?>
