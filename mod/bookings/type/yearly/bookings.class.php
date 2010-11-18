<?php // $Id: assignment.class.php,v 1.6 2005/04/21 12:44:19 moodler Exp $

/**
 * Extend the base assignment class for offline assignments
 *
 */
class bookings_yearly extends bookings_base {

    function bookings_yearly($cmid=0) {
        parent::bookings_base($cmid);
    }

    function display_lateness($timesubmitted) {
        return '';
    }

    function view_dates() {
        global $CFG,$USER;
        $showcombo      = optional_param('showcombo',NULL, PARAM_INT);
        $komboroom      = optional_param('komboroom',NULL, PARAM_ALPHAEXT);
        $jday           = optional_param('jday',NULL, PARAM_INT);
        $subitemid      = optional_param('subitemid',NULL, PARAM_INT);
        $tidx           = optional_param('tidx',NULL, PARAM_INT);
        $delete         = optional_param('delete',NULL, PARAM_INT);
        $edit           = optional_param('edit',NULL, PARAM_INT);
        $save           = optional_param('save',NULL, PARAM_INT);
        $restore        = optional_param('restore',NULL, PARAM_INT);
        $resid          = optional_param('resid',NULL, PARAM_INT);
        $uid            = optional_param('uid',NULL, PARAM_INT);
        $value          = optional_param('value');

        
        $cmid = $this->cm->id;


        $itemid = $this->bookings->itemid;
        $username = $USER->username;

        // $bookings = get_record('bookings', 'id', $this->bookings->id);
        /// get start and end julian day number
        list($ey,$em,$ed) = explode("/",strftime("%Y/%m/%d",$this->bookings->enddate ));
        $ejday = 7* (int)(gregoriantojd($em,$ed,$ey) / 7);
        list($ey,$em,$ed) = explode("/",strftime("%Y/%m/%d",$this->bookings->startdate ));
        $sjday = 7* (int)(gregoriantojd($em,$ed,$ey) / 7);
        
        $UID = $USER->id;
        $firstname = $USER->firstname;
        $lastname = $USER->lastname;
        if ($firstname == '' or $lastname == '') return "";
        print '<form name=myform id=myform method=post action="view.php?id='.$cmid.'">';


        // fetch out data for this item
        $sql = 'SELECT id,name,type,parent
                FROM '.$CFG->prefix.'bookings_item i
                WHERE i.parent = '.$itemid.'
                ORDER BY i.name';
        $idx = 0;
        $main_reservation = array();            
        $iteminfo = array();
        $itemid_list = array();
        if (!$childlist = get_records_sql($sql)) {
            $sql = 'SELECT r.id,r.name,r.type,r.parent FROM '.$CFG->prefix.'bookings_item r WHERE r.id='.$itemid;
            $childlist = get_records_sql($sql);
        }
        foreach($childlist as $child) {
            // build array of props for this item (room)
            $itemid_list[] = $child->id;
            $proplist = bookings_item_properties($child->id);
            if (isset($proplist['image'])) {
                $childlist[$child->id]->image = '<br>' . '<img src="'.$proplist['image'].'">';
            }

            /// here we fetch out all reservations
            $reservation = array();            
            $sql = 'SELECT * FROM '.$CFG->prefix.'bookings_calendar 
                    WHERE eventtype="reservation" 
                    AND itemid='.$child->id.'
                    AND julday >= '.$sjday.'
                    AND julday <= '.$ejday;
            if ($res = get_records_sql($sql)) {
                foreach($res as $re) {
                    $reservation[7*(int)($re->julday/7) ] = $re;
                }
            }
            $childlist[$child->id]->proplist = $proplist;
            $main_reservation[$idx] = $reservation;
            $iteminfo[$idx] = $child;
            /// $childlist[$child->id]->reservation = $reservation;
            $idx ++;
        }    
        
        /// if there are children, then we must fetch out properties for parent
        /// these props decide if teachers or students can make bookings
        if ($idx > 1) {
            $proplist = bookings_item_properties($itemid);
        }

        // decide if user can edit timetable
        // 0 = view only, 1 = add items, delete/edit own, 2 add/delete/edit any
        $can_edit = isteacherinanycourse($USER->id) ? 1 : 0;   // default is that teachers can edit
        $link2room = '';

        if (isset($proplist['edit_group'])) {
            $can_edit = 0;   // default is no edit (that is: edit_group != teacher|student )
            if ($proplist['edit_group'] == 'teachers' and isteacherinanycourse($USER->id) ) {
                $can_edit = 1;
                $link2room = ' <a href="itemeditor.php?id='.$cmid.'&newid='.$itemid.'">Edit Item</a>';
            } else if ($proplist['edit_group'] == 'students') {
                $can_edit = 1;
            }
        }
        // intended to give edit-rights to named users
        // these users have admin rights, can delete/edit any booking
        if (isset($proplist['edit_list'])) {
            if (strstr($proplist['edit_list'],$username)) {
                $can_edit = 2;
            }
        }

        if (isadmin()) {  /// no matter what, admin can edit - even past dates
            $can_edit = 2;
            $link2room = ' <a href="itemeditor.php?id='.$cmid.'&newid='.$itemid.'">Edit Item</a>';
        }            


        /// this is where we make the reservation or delete reservations
        if (isset($subitemid) and $can_edit ) {
            $orig_res = $main_reservation[$tidx][$jday];
            // print_r($orig_res);
            // print "UID = $UID<p>";
            $value = isset($value) ? $value : $username;
            if (isset($edit) and ($can_edit == 2 or $UID == $orig_res->userid ) ) {
                print "<table><tr><td>";
                print '<script type="text/javascript" src="http://localhost/moodle/lib/editor/htmlarea.php?id=3"></script>
                <script type="text/javascript" src="http://localhost/moodle/lib/editor/lang/en.php"></script>';
                helpbutton("writing", get_string("helpwriting"), "moodle", true, true);
                echo "<br />";
                helpbutton("questions", get_string("helpquestions"), "moodle", true, true);
                echo "<br />";
                if ($usehtmleditor) {
                        helpbutton("richtext", get_string("helprichtext"), "moodle", true, true);
                } else {
                        emoticonhelpbutton("form", "description");
                } 
                echo "</td><td>";
                print_textarea($usehtmleditor, 20, 60, 680, 400, "value", $orig_res->value);
    
                if ($usehtmleditor) {
                    echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
                } else {
                    echo '<div align="right">';
                    helpbutton("textformat", get_string("formattexttype"));
                    print_string("formattexttype");
                    echo ':&nbsp;';
                    if (!$form->format) {
                        $form->format = $defaultformat;
                    }
                    choose_from_menu(format_text_menu(), "format", $form->format, ""); 
                    echo '</div>';
                }    
                print "<script language=\"javascript\" type=\"text/javascript\" defer=\"defer\">
                var config = new HTMLArea.Config();
                config.pageStyle = \"body { background-color: #ffffff; font-family: Trebuchet MS,Verdana,Arial,Helvetica,sans-serif; }\";
                config.killWordOnPaste = true;
                config.fontname = {
                \"Trebuchet\":    'Trebuchet MS,Verdana,Arial,Helvetica,sans-serif',
                \"Arial\":    'arial,helvetica,sans-serif',
                \"Courier New\":  'courier new,courier,monospace',
                \"Georgia\":  'georgia,times new roman,times,serif',
                \"Tahoma\":   'tahoma,arial,helvetica,sans-serif',
                \"Times New Roman\":  'times new roman,times,serif',
                \"Verdana\":  'verdana,arial,helvetica,sans-serif',
                \"Impact\":   'impact',
                \"Wingdings\":    'wingdings'};
                HTMLArea.replaceAll(config);
                </script>";
                print '<input type="submit" name="save" value="save" />';
                print "</td></tr></table>";
                print '<input type="hidden" name="subitemid" value="'.$subitemid.'" />';
                print '<input type="hidden" name="tidx" value="'.$tidx.'" />';
                print '<input type="hidden" name="jday" value="'.$jday.'" />';
                print '<input type="hidden" name="resid" value="'.$resid.'" />';
                print "</form>";
                return;
            }
            if (isset($resid) and ($orig_res->userid == $UID or $can_edit == 2)) {
                $sql = 'DELETE FROM '.$CFG->prefix.'bookings_calendar 
                        WHERE id='.$resid;
                execute_sql($sql,0);
                unset($main_reservation[$tidx][$jday]);
            }
            unset($res);
            if (isset($restore) ) {
                $res->start = 0;     
                $value = $orig_res->value;
            }
            if (isset($delete) and ($orig_res->userid == $UID or $can_edit == 2)) {
                if ($orig_res->start != -1) {
                    $res->start = -1;     
                    $value = $orig_res->value;
                    unset($delete);
                }
            }    
            if (!isset($delete)) {
                $res->name          = $username;
                $res->value         = $value;
                $res->bookingid     = $this->bookings->id;
                $res->userid        = $UID;
                $res->eventtype     = 'reservation';
                $res->itemid        = $iteminfo[$tidx]->id;
                $res->julday        = $jday;
                if ($returnid = insert_record("bookings_calendar", $res)) {
                    $res->id = $returnid;
                    $main_reservation[$tidx][$jday] = $res;
                }
            }
        }
    

    
        $html =  $link2room;

        // now we draw up the table
        // print_r($childlist);
        $html .= '<table border=2><tr><th>'.get_string('week').'</th>';
        foreach ($childlist as $child) {
            $html .= '<th>'.$child->name.' '.$child->image.'</th>';
        }
        $html .= '</tr>';
        $count = count($childlist);
        /// $widthprcent = (int)(95 / $count ) ;    // default width
        $julday = $sjday;
        $date = jdtogregorian($julday);
        list($m1,$d1,$y) = explode('/',$date);
        $time = mktime(12,0,0,$m1,$d1,$y); 
        while ($julday < $ejday ) {
            $baselink = '<a href="view.php?id='.$cmid.'&jday='.$julday;
            $date = jdtogregorian($julday);
            list($m,$d,$y) = explode('/',$date);
            $date = jdtogregorian($julday+6);
            list($m1,$d1,$y) = explode('/',$date);
            $monthname = userdate($time,'%b');
            $date = sprintf("%02d.%02d-%02d.%02d",$d,$m,$d1,$m1);
            $html .= "<tr><th><a name=\"jd$julday\">".(bookings_week($julday))."</a><div class='mod-bookings tiny'>$monthname $date<div></th>";
            for ($idx=0;$idx<$count;$idx++) {
                    if (isset($main_reservation[$idx][$julday]) ) {
                        $res = $main_reservation[$idx][$julday];
                        $class = 'reserved';
                        if (($can_edit) and ($res->userid == $UID or $can_edit == 2)) {
                            // $linktext = $main_reservation[$idx][$julday]->value;
                            $resid = $res->id;
                            $link = $res->value.'<br>';
                            if ($res->start == -1) {
                                $class = 'deleted';
                                $link .=  $baselink.'&tidx='.$idx.'&subitemid='.$iteminfo[$idx]->id.'&restore=1&resid='.$resid.'#jd'.$julday.    
                                    '" title="Restore" ><img src="'.$CFG->pixpath.'/i/restore.gif" '.
                                    ' height="14" width="14" border="0" alt="Restore" /></a> ';
                            } else {
                                $link .=  $baselink.'&tidx='.$idx.'&subitemid='.$iteminfo[$idx]->id.'&edit=1&resid='.$resid.'#jd'.$julday.    
                                    '" title="Edit" ><img src="'.$CFG->pixpath.'/t/edit.gif" '.
                                    ' height="12" width="12" border="0" alt="Edit" /></a> ';
                            }                                    
                            $link .=  $baselink.'&tidx='.$idx.'&subitemid='.$iteminfo[$idx]->id.'&delete=1&resid='.$resid.'#jd'.$julday.  
                                    '" title="Delete" ><img src="'.$CFG->pixpath.'/t/delete.gif" '.
                                    ' height="12" width="12" border="0" alt="Delete" /></a>';
                        } else {
                            $link = get_string('reserved','bookings');
                            if ($can_edit) {
                                $link = $res->value;
                            }
                        }
                    } else {
                        $linktext = 'free';
                        $class = 'free';
                        $link = $can_edit ? $baselink.'&tidx='.$idx.'&subitemid='.$iteminfo[$idx]->id.'#jd'
                                .$julday.'">'.get_string('free','bookings').'</a>' : get_string('free','bookings');
                    }
                    $html .= "<td class='$class'>$link</td>\n";
            }
            $julday += 7;   
            $time += WEEKSECS; 
            $html .= "</tr>";
        }
        $html .= "</table>";
        $html .=  '<input type="hidden" name="itemid" value="'.$itemid.'">';
    
        print $html;
        if ($can_edit > 0 ) {
            unset($table);
	        $table->head = array('&nbsp;', get_string('bookedby','bookings'));
            $table->align = array('center', 'left');
            $table->wrap = array('nowrap', 'nowrap');
            $table->width = '100%';
            $table->size = array(10, '*');
	        $table->head[] = get_string('week');
	        $table->align[] = 'center';
	        $table->wrap[] = 'nowrap';
            $table->size[] = '*';
	        $table->head[] = get_string('date');
	        $table->align[] = 'center';
	        $table->wrap[] = 'nowrap';
            $table->size[] = '*';
	        $table->head[] = get_string('reservation','bookings');
	        $table->align[] = 'center';
	        $table->wrap[] = 'nowrap';
            $table->size[] = '*';
	        $table->head[] = get_string('item','bookings');
	        $table->align[] = 'center';
	        $table->wrap[] = 'nowrap';
            $table->size[] = '*';
    
            // $books = get_records('calendar', 'bookingid', $this->bookings->id);
            $itemid_list = implode(',',$itemid_list);
                                /// WHERE r.bookingid = '{$this->bookings->id}' 
            if ($books = get_records_sql("SELECT r.*, u.firstname, u.lastname, u.picture, u.email
                                FROM {$CFG->prefix}bookings_calendar r,
                                    {$CFG->prefix}user u
                                WHERE r.itemid in ( $itemid_list )
                                AND r.julday >= $sjday
                                AND r.julday <= $ejday
                                AND r.userid = u.id ORDER BY r.itemid,r.julday"))  {
    	        foreach ($books as $request) {
		            $row = array();
		            $row[] = print_user_picture($request->userid, $course->id, $request->picture, 0, true);
		            $row[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$request->userid.'&course='.$course->id.'">'
			            .$request->lastname.' '.$request->firstname.'</a>';
                    $date = jdtogregorian($request->julday);
                    list($m1,$d1,$y) = explode('/',$date);
                    $time = mktime(12,0,0,$m1,$d1,$y); 
                    $monthname = userdate($time,'%b');
                    $date = jdtogregorian($request->julday+6);
                    list($m1,$d1,$y) = explode('/',$date);
                    $date = sprintf("%02d.%02d-%02d.%02d",$d,$m,$d1,$m1);
		            $row[] = get_string('week') . bookings_week($request->julday);
                    $row[] = " $monthname " . $date;
		            $row[] = $request->value;
		            $row[] = $childlist[$request->itemid]->name;
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
