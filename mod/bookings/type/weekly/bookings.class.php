<?php // $Id: assignment.class.php,v 1.6 2005/04/21 12:44:19 moodler Exp $

/**
 * Extend the base assignment class for offline assignments
 *
 */
class bookings_weekly extends bookings_base {

    function bookings_weekly($cmid=0) {
        parent::bookings_base($cmid);
    }

    function display_lateness($timesubmitted) {
        return '';
    }

    function view_dates() {
        global $CFG,$USER;
        $showcombo      = optional_param('showcombo',NULL, PARAM_INT);
        $komboroom      = optional_param('komboroom',NULL, PARAM_ALPHAEXT);
        $prev           = optional_param('prev',NULL, PARAM_ALPHAEXT);
        $next           = optional_param('next',NULL, PARAM_ALPHAEXT);
        $jday           = optional_param('jday',NULL, PARAM_INT);
        $rday           = optional_param('rday',NULL, PARAM_INT);
        $rslot          = optional_param('rslot',NULL, PARAM_INT);
        $delete         = optional_param('delete',NULL, PARAM_INT);
        $resid          = optional_param('resid',NULL, PARAM_INT);
        $uid            = optional_param('uid',NULL, PARAM_INT);
        require_once("../../config.php");

        $cmid = $this->cm->id;

        /// require_once($CFG->dirroot.'/blocks/timetable/locallib.php');

        $itemid = $this->bookings->itemid;
        $username = $USER->username;
        $UID = $USER->id;
        $firstname = $USER->firstname;
        $lastname = $USER->lastname;
        ///$prover = mineprover();
        ///$fridager = Fridager();
        if ($firstname == '' or $lastname == '') return "";
        $html .= '<form name=myform id=myform method=post action="view.php?id='.$cmid.'">';

        //// show combo is used if we don't already know which room/item is to be scheduled
        if (isset($showcombo) ) {
            if (isset($komboroom) ) {
                $itemid = $komboroom;
            }
            $sql = 'SELECT r.id, r.name
                    FROM '.$CFG->prefix.'bookings_item r,
                        '.$CFG->prefix.'bookings_item_property p
                    WHERE p.itemid = r.id
                        AND p.name="scheduled"
                        AND p.value="yes"
                    ORDER BY r.name';
            if ($roomlist = get_records_sql($sql)) {
                $kombo = "<select name=\"komboroom\" onchange=\"document.myform.reload.click()\">";
                $kombo .= "<option value=\" \"> -- Select -- </option>\n ";
                foreach ($roomlist as $room) {
                        $selected = "";
                        if ($room->id == $itemid) { 
                            $selected = "selected";
                            $rname = $room->name;
                        }
                        $kombo .= '<option value="'.$room->id.'" '.$selected.'>'.$room->name.'</option>'."\n ";
                }
                $kombo .= '</select>'."\n";
            }
            $html .= $kombo;
            $html .= '<input id="reload" type="submit" name="reload">';
            $html .= '<input type="hidden" name="showcombo" value="yes">';
        }
        ///////////////////////////

        // fetch out any timetable-data for this item
        $sql = 'SELECT r.id,r.name,r.type FROM '.$CFG->prefix.'bookings_item r WHERE r.id='.$itemid;
        $r = get_record_sql($sql);


    
        $sql = "SELECT concat(c.id,t.day,t.slot),c.id,c.shortname, r.type, r.name, t.day, t.slot  
                    FROM {$CFG->prefix}bookings_calendar t,
                        {$CFG->prefix}bookings_item r left join
                        {$CFG->prefix}course c  on c.id = t.courseid
                WHERE r.id=$itemid
                    AND t.eventtype = 'timetable'
                    AND r.id = t.itemid
                ORDER BY day,slot";
        $tp = array();
        if ($tplan = get_records_sql($sql)) {
            foreach ($tplan as $tpelm) {
                $shortshortname = $tpelm->shortname;
                $tp[$tpelm->day][$tpelm->slot] = "<a href=\"/moodle/course/view.php?id={$tpelm->id}\">$shortshortname</a>";
            }
        }


        // build array of props for this item (room)
        $proplist = bookings_item_properties($itemid);
    
        $days = explode(',','Mon,Tue,Wed,Thu,Fri,,');
        if (isset($proplist['days'])) {
            $days = explode(',',$proplist['days']);
        }
        $widthprcent = (int)(95 / count($days) ) ;    // default width

        $slots = array(8,9,10,11,12,13,14,15,16);
        if (isset($proplist['slots'])) {
            $slots = explode(',',$proplist['slots']);
        }

        $lookahead = 700;   // limit on number of weeks you can move from present week (100 weeks +/-)
        if (isset($proplist['lookahead'])) {
            $lookahead = 7*(int)$proplist['lookahead'];
        }

        // decide if user can edit timetable
        $can_edit = isteacherinanycourse($USER->id) ? 1 : 0;   // default is that teachers can edit
        $link2room = '';

        if (isset($proplist['edit_group'])) {
            $can_edit = 0;   // default is no edit (that is: edit_group != teacher|student )
            if ($proplist['edit_group'] == 'teachers' and isteacherinanycourse($USER->id) ) {
                $can_edit = 1;
                $link2room = ' <a href="itemeditor.php?id='.$cmid.'&newid='.$itemid.'">'.get_string('edit').' '.$r->type.'</a>';
            } else if ($proplist['edit_group'] == 'students') {
                $can_edit = 1;
            }
        }
        // intended to give edit-rights to named students
        if (isset($proplist['edit_list'])) {
            if (strstr($proplist['edit_list'],$username)) {
                $can_edit = 1;
            }
        }
    

        // a multiple resource has a property that is counted down on each reservation
        // so long as number of resevations is less than this values, then user can make a
        // reservation
        if (isset($proplist['multiple'])) {
            $multiple = (int)$proplist['multiple'];
        }

        // calculate week and day
        list($ty,$tm,$td) = explode("/",strftime("%Y/%m/%d",time() ));
        $tjday = gregoriantojd($tm,$td,$ty);
        $tweek = bookings_week($tjday);
        if (!isset($jday)) {
            $jday = $tjday;
        }

        // a list of days that isn't a week (7 days) or doesn't start on the first day of the week
        // should have this property, otherwise the schedule wont tile over the year
        list($ys,$ms,$ds) = explode(',',$proplist['startsched']);
        $start = gregoriantojd($ms,$ds,$ys) or 0;
        $jday = $start + sizeof($days) * (int)(($jday-$start)/sizeof($days));

        // $jday = 7 * (int)($jday/7);
    
        if (isset($prev) and  $jday>$tjday-$lookahead ) {
            $jday -= sizeof($days);
        }

        if (isset($next) and  $jday<$tjday+$lookahead-7 ) {
            $jday += sizeof($days);
        }

        list($ey,$em,$ed) = explode("/",strftime("%Y/%m/%d",$this->bookings->enddate ));
        $ejday = gregoriantojd($em,$ed,$ey);
        if ($jday > $ejday ) {
            $can_edit = 0;
        }
        if ($jday + sizeof($days) < $tjday ) {
            $can_edit = 0;
        }

        if (isadmin()) {  /// no matter what, admin can edit - even past dates
            $can_edit = 1;
            $link2room = ' <a href="itemeditor.php?id='.$cmid.'&newid='.$itemid.'">'.get_string('edit').' '.$r->type.'</a>';
        }            


        /// here we fetch out all reservations
        // list($m,$d,$y) = explode('/',jdtogregorian($jday));
        // $start = gmmktime(0, 0, 0, $m, $d, $y);
        // $stop = $start + 604800;   // weekofseconds
        $sql = 'SELECT * FROM '.$CFG->prefix.'bookings_calendar 
                WHERE eventtype="reservation" 
                AND itemid='.$r->id.'
                AND julday >= '.$jday.'
                AND julday <= '.($jday+sizeof($days)-1);
        $reservation = array();            
        if ($res = get_records_sql($sql)) {
            foreach($res as $re) {
                $reservation[$re->day][$re->slot][] = $re;
                if ($res->userid == $UID) {
                    $reservation[$re->day][$re->slot]->res = $re;
                }    
            }
        }

        /// this is where we make the reservation or delete reservations
        if ( ( (isset($resid) and isset($uid)) or (isset($rday) and isset($rslot) )) and $can_edit ) {
            if (!isteacherinanycourse($USER->id) AND !isadmin() and isset($reservation[$rday][$rslot]) ) { 
                if ($uid != $UID) {
                    // return;  // assume an attempt to phreak the system with params 
                }
            }
            $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar WHERE eventtype="reservation" AND slot='.$rslot.' AND day='.$rday.' AND julday='.($jday+$rday).'
                        AND userid='.$UID.' AND itemid='.$r->id;
            execute_sql($sql,0);   /// this removes multiple bookings by one person for a given slot
            if (isset($resid)) {
                $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar WHERE id='.$resid;
                execute_sql($sql,0);
            }
            if (!isset($delete) ) {
                $sql = 'INSERT INTO '.$CFG->prefix.'bookings_calendar (name,value,userid,eventtype,itemid,slot,day,julday) 
                    VALUES ("'.$r->name.'","'.$username.'",'
                    .$UID.',"reservation",'.$r->id.','.$rslot.','.$rday.','.($jday+$rday).')';
                execute_sql($sql,0);
            }
            // have to refetch data
            $sql = 'SELECT * FROM '.$CFG->prefix.'bookings_calendar 
                WHERE eventtype="reservation" 
                AND itemid='.$r->id.'
                AND julday >= '.$jday.'
                AND julday <= '.($jday+sizeof($days)-1);
            $reservation = array();            
            if ($res = get_records_sql($sql)) {
                foreach($res as $re) {
                    $reservation[$re->day][$re->slot][] = $re;
                }
            }
        }
    

    
        // navigation (next/prev) week
        $week = bookings_week($jday);
        list($m,$d,$y) =explode("/",jdtogregorian($jday));
        list($m1,$d1,$y1) =explode("/",jdtogregorian($jday+sizeof($days)-1));
        if (bookings_week($jday+sizeof($days)-1) != $week) {
            $week .= '-'.bookings_week($jday+sizeof($days)-1);
        }        
    
        $html .=  '<div id="all"><h2>'.$r->name.' ('.$r->type.')</h2>';
        $html .=  '<div class="mod-bookings navigate" ><input type="submit"  name="prev" value="&lt;&lt;">';
        $html .=  get_string('week')." $week &nbsp; <span id=\"date\">$d.$m - $d1.$m1 $y</span>";
        $html .=  '<input type="submit" name="next" value="&gt;&gt;"></div>';
        $html .=  $link2room;

        // now we draw up the table
        $table = array();
        if ($can_edit) {
            $baselink = '<a href="view.php?id='.$cmid.'&jday='.$jday.'&itemid='.$itemid;
        } else {
            $baselink = '';
        }
        $table[] = '<table border=1 width=100%>';
        $table[] = '<tr><th width=5%>&nbsp;</th>';
        foreach ($days as $day) {
            if ($day == '') continue;
            $table[] = '<th>'.$day.'</th>';
        }
        $table[] = '</tr>';
        $time = 0;
        foreach($slots as $slottime) {
            $t = $time + 1;
            $table[] = "<tr><th><span class=\"number\">$slottime</span></th>";
            $dag = 0;
            foreach ($days as $day) {
                $class = 'normal'; 
                if ($day != '' ) {
                    if ($tp[$dag][$time] == '') {
                        $class = 'free';
                        $tp[$dag][$time] = $can_edit ? $baselink.'&rday='.$dag.'&rslot='.$time.'">'.get_string('free','bookings').'</a>' : get_string('free','bookings');
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
                        $tp[$dag][$time] .= ' ' .$reservation[$dag][$time]->count . ' ';
                        $tp[$dag][$time] .= $can_edit ? $baselink.'&rday='.$dag.'&rslot='
                                        .$time.'">'.get_string('free','bookings').'</a>' : get_string('free','bookings');
                    }
                    }
                    $table[] = "<td width=\"$widthprcent%\" class=\"$class\" >" . $tp[$dag][$time] . "&nbsp;</td>\n";
                }
                $dag ++;
            }
            $table[] = "</tr>\n";
            $idx ++;
            $time += 1;
        }
        $table[] = "</table>\n";
        $html .= implode("",$table);
        $html .=  '<input type="hidden" name="itemid" value="'.$itemid.'">';
        $html .=  '<input type="hidden" name="jday" value="'.$jday.'">';
        $html .= '</div>';  // end div=all
    
        print $html;
    
        print "</form>";
    	return;
    }

}

?>
