<?php  // $Id: editstamps.php,v 1.6 2008/09/01 22:33:19 mudrd8mz Exp $

    require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once(dirname(__FILE__).'/lib.php');

    $id = required_param('id',PARAM_INT);           // Course Module ID
    $page = optional_param('page', 0, PARAM_INT);   // Page of the batch view

    if (! $cm = get_coursemodule_from_id('stampcoll', $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    require_login($course->id, false, $cm);

/// Get capabilities
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    include(dirname(__FILE__).'/caps.php');

    if (!$cap_givestamps) {
        // Illegal access to the page
        error("You are not allowed to use this page");
    }

    if (!$stampcoll = stampcoll_get_stampcoll($cm->instance)) {
        error("Course module is incorrect");
    }

    if (!$allstamps = stampcoll_get_stamps($stampcoll->id)) {
        $allstamps = array();
    }

    /// First we check to see if the preferences form has just been submitted
    /// to request user_preference updates
    if (isset($_POST['updatepref'])){
        $perpage = optional_param('perpage', STAMPCOLL_USERS_PER_PAGE, PARAM_INT);
        $perpage = ($perpage <= 0) ? STAMPCOLL_USERS_PER_PAGE : $perpage ;
        set_user_preference('stampcoll_perpage', $perpage);
        if (isset($_POST['showupdateforms']) && $_POST['showupdateforms'] == "1") {
            set_user_preference('stampcoll_showupdateforms', 1);
        } else {
            set_user_preference('stampcoll_showupdateforms', 0);
        }
        redirect("editstamps.php?id=$cm->id");
        exit;
    }
    
    $strstampcoll = get_string("modulename", "stampcoll");
    $strstampcolls = get_string("modulenameplural", "stampcoll");

    $navigation = build_navigation(get_string('editstamps', 'stampcoll') , $cm);
    print_header_simple(format_string($stampcoll->name), '',
                  $navigation, '', '', true,
                  update_module_button($cm->id, $course->id, $strstampcoll), navmenu($course, $cm));

/// Print the tabs
    $currenttab = 'edit';
    include(dirname(__FILE__).'/tabs.php');

/// Submit any new data if there is any

    if (($form = data_submitted()) && $cap_givestamps ) {
        if (isset($form->addstamp) and $form->addstamp == '1') {
            if (!isset($form->sesskey) || !confirm_sesskey($form->sesskey)) {
                error('Sesskey error');
            }
            $newstamp->stampcollid = $stampcoll->id;
            $newstamp->userid = $form->userid;
            if (!isset($form->text)) {
                $form->text = '';
            }
            if (empty($stampcoll->anonymous)) {
                $newstamp->giver = $USER->id;
            }
            $newstamp->text = $form->text;
            $newstamp->timemodified = time();
            
            if (! $newstamp->id = insert_record("stampcoll_stamps", $newstamp)) {
                error("Could not save new stamp");
            }
            add_to_log($course->id, "stampcoll", "add stamp", "view.php?id=$cm->id", $newstamp->userid, $cm->id);
            redirect("editstamps.php?id=$cm->id&page=$form->page");
            exit;
        }
        if (isset($form->updatestamp) and $form->updatestamp == '1') {
            if (!isset($form->sesskey) || !confirm_sesskey($form->sesskey)) {
                error('Sesskey error');
            }
            $updatedstamp = stampcoll_get_stamp($form->stampid);
            if (!($cap_managestamps || $updatedstamp->giver == $USER->id)) {
                error('You are not allowed to update this stamp');
            }
            if (!isset($form->text)) {
                $form->text = '';
            }
            $updatedstamp->text = $form->text;
            $updatedstamp->timemodified = time();
            
            if (! update_record("stampcoll_stamps", $updatedstamp)) {
                error("Could not update stamp");
            }
            $updatedstamp = stampcoll_get_stamp($updatedstamp->id);
            add_to_log($course->id, "stampcoll", "update stamp", "view.php?id=$cm->id", $updatedstamp->userid, $cm->id);
            redirect("editstamps.php?id=$cm->id&page=$form->page");
            exit;
        }
        if (isset($form->deletestamp)) {
            if (! $cap_managestamps) {
                error('You are not allowed to managestamps');
            }
            if (!isset($form->sesskey) || !confirm_sesskey($form->sesskey)) {
                error('Sesskey error');
            }
            if (! $stamp = stampcoll_get_stamp($form->deletestamp)) {
                error("Could not find stamp");
            }
            if (! delete_records("stampcoll_stamps", "id", $form->deletestamp)) {
                error("Could not delete stamp");
            }
            add_to_log($course->id, "stampcoll", "delete stamp", "view.php?id=$cm->id", $stamp->userid, $cm->id);
            if (isset($form->page)) {
                redirect("editstamps.php?id=$cm->id&page=".$form->page);
            } else {
                redirect("editstamps.php?id=$cm->id");
            }
        }

    }

/// Should be a stamp deleted?

    if (isset($_GET['d']) && $cap_managestamps) {
        if (!isset($_GET['sesskey']) || !confirm_sesskey($_GET['sesskey'])) {
            error('Sesskey error');
        }

       if (! $stamp = stampcoll_get_stamp($_GET['d'])) {
            error("Invalid stamp ID");
        }

        print_box_start();

        print_heading(get_string("confirmdel", "stampcoll"));

        $form = '<div align="center"><form name="delform" action="editstamps.php?id='.$cm->id.'" method="post">';
        $form .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $form .= '<input type="hidden" name="deletestamp" value="'.$stamp->id.'" />';
        if (isset($_GET['page'])) {
            $form .= '<input type="hidden" name="page" value="'.$_GET['page'].'" />';
        }
        $form .= '<input type="submit" value="'.get_string('yes').'" />';
        $form .= '<input type="button" value="'.get_string('no').'" onclick="javascript:history.go(-1);" />';
        $form .= '</form></div>';
        echo $form;
        print_box_end();

        print_box_start('delstampbox');
        echo '<div class="picture">'.stampcoll_stamp($stamp, $stampcoll->image).'</div>';
        echo '<div class="comment">'.format_text($stamp->text).'</div>';
        echo '<div class="timemodified">'.get_string('timemodified', 'stampcoll').': '.
                                                userdate($stamp->timemodified).'</div>';
        print_box_end();
        print_footer($course);
        exit;
    }

    /// Load all stamps into an array
    $userstamps = array();
    foreach ($allstamps as $s) {
        $userstamps[$s->userid][] = $s; 
    }
    unset($allstamps);
    unset($s);
    
/// Groups and users
    groups_print_activity_menu($cm, 'editstamps.php?page='.$page.'&amp;id='.$cm->id);
    $currentgroup = groups_get_activity_group($cm);
    $users = stampcoll_get_users_can_collect($cm, $context, $currentgroup);
    if (!$users) {
        print_heading(get_string("nousersyet"));
    }

/// Get perpage param from database
    $perpage = get_user_preferences('stampcoll_perpage', STAMPCOLL_USERS_PER_PAGE);
    $showupdateforms = get_user_preferences('stampcoll_showupdateforms', 1);
    
    $tablecolumns = array('picture', 'fullname', 'count', 'comment');
    $tableheaders = array('', get_string('fullname'), get_string('numberofstamps', 'stampcoll'), '');

    require_once($CFG->libdir.'/tablelib.php');

    $table = new flexible_table('mod-stampcoll-editstamps');

    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($CFG->wwwroot.'/mod/stampcoll/editstamps.php?id='.$cm->id.'&amp;currentgroup='.$currentgroup);

    $table->sortable(true, 'lastname'); // default sort - do not use "count" here!
    if (!$cap_viewotherstamps) {
        // prevent sorting by stamps count and so guessing the number of them
        $table->no_sorting('count');
    }
    $table->collapsible(false);
    $table->initialbars(true);

    $table->column_suppress('picture');
    $table->column_suppress('fullname');

    $table->column_class('picture', 'picture');
    $table->column_class('fullname', 'fullname');
    $table->column_class('count', 'count');
    $table->column_class('comment', 'comment');

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'stamps');
    $table->set_attribute('class', 'stamps');
    $table->set_attribute('width', '90%');
    $table->set_attribute('align', 'center');

    $table->setup();

    if (empty($users)) {
        print_heading(get_string('nousers','stampcoll'));
        return true;
    }

/// Construct the SQL

    if ($where = $table->get_sql_where()) {
        $where .= ' AND ';
    }
    
    if ($sort = $table->get_sql_sort()) {
        $sort = ' ORDER BY '.$sort;
    }

    $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, COUNT(s.id) AS count ';
    $sql = 'FROM '.$CFG->prefix.'user AS u '.
           'LEFT JOIN '.$CFG->prefix.'stampcoll_stamps s ON u.id = s.userid AND s.stampcollid = '.$stampcoll->id.' '.
           'WHERE '.$where.'u.id IN ('.implode(',', array_keys($users)).') GROUP BY u.id, u.firstname, u.lastname, u.picture ';

    $table->pagesize($perpage, count($users));
    
    if (($ausers = get_records_sql($select.$sql.$sort, $table->get_page_start(), $table->get_page_size())) !== false) {
        
        foreach ($ausers as $auser) {
            $picture = print_user_picture($auser->id, $course->id, $auser->picture, false, true);
            $fullname = fullname($auser);
            $count = '';
            if ($auser->id == $USER->id && $cap_viewownstamps) {
                $count = $auser->count;
            }
            if ($auser->id != $USER->id && $cap_viewotherstamps) {
                $count = $auser->count;
            }
            $comment = '<form name="addform" action="editstamps.php?id='.$cm->id.'" method="post">';
            $comment .= '<input name="text" type="text" size="35" maxlength="250" />';
            $comment .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            $comment .= '<input type="hidden" name="userid" value="'.$auser->id.'" />';
            $comment .= '<input type="hidden" name="page" value="'.$page.'" />';
            $comment .= '<input type="hidden" name="addstamp" value="1" />';
            $comment .= '<input type="submit" value="'.get_string('addstampbutton', 'stampcoll').'" /></form>';
            $row = array($picture, $fullname, $count, $comment);
            $table->add_data($row);

            if ($cap_viewotherstamps &&  $showupdateforms && isset($userstamps[$auser->id])) {
                foreach ($userstamps[$auser->id] as $userstamp) {
                    $count = stampcoll_stamp($userstamp, '', true, false, $CFG->pixpath.'/t/preview.gif');
                    $count .= '&nbsp;';
                    if ($cap_managestamps) {
                        $count .= '<a href="editstamps.php?id='.$cm->id.'&amp;d='.$userstamp->id.'&amp;sesskey='.sesskey().'&amp;page='.$page.'" title="'.get_string('deletestamp', 'stampcoll').'">';
                        $count .= '<img src="'.$CFG->pixpath.'/t/delete.gif" height="11" width="11" border="0" alt="'.get_string('deletestamp', 'stampcoll').'" />';
                        $count .= '</a>&nbsp;&nbsp;';
                    }

                    if ($cap_managestamps || ($userstamp->giver == $USER->id)) {
                        $comment = '<form name="updateform" action="editstamps.php?id='.$cm->id.'" method="post">';
                        $comment .= '<input name="text" type="text" size="35" maxlength="250" value="' . format_string($userstamp->text) . '" />';
                        $comment .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
                        $comment .= '<input type="hidden" name="stampid" value="'.$userstamp->id.'" />';
                        $comment .= '<input type="hidden" name="page" value="'.$page.'" />';
                        $comment .= '<input type="hidden" name="updatestamp" value="1" />';
                        $comment .= '<input type="submit" value="'.get_string('updatestampbutton', 'stampcoll').'" /></form>';
                    } else {
                        $comment = format_string($userstamp->text);
                    }
                    $row = array($picture, $fullname, $count, $comment);
                    $table->add_data($row);
                }
            }
                
        }
    }
    
    $table->print_html();  /// Print the whole table
    
    /// Mini form for setting user preference
    echo '<br />';
    echo '<form name="options" action="editstamps.php?id='.$cm->id.'" method="post">';
    echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
    echo '<table id="optiontable" align="center">';
    echo '<tr align="right"><td>';
    echo '<label for="perpage">'.get_string('studentsperpage','stampcoll').'</label>';
    echo ':</td>';
    echo '<td align="left">';
    echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
    helpbutton('studentperpage', get_string('studentsperpage','stampcoll'), 'stampcoll');
    echo '</td></tr>';
    echo '<tr align="right"><td>';
    echo '<label for="showupdateforms">'.get_string('showupdateforms','stampcoll').'</label>';
    echo ':</td>';
    echo '<td align="left">';
    echo '<input type="checkbox" id="showupdateforms" name="showupdateforms" value="1" ';
    if ($showupdateforms) {
        echo 'checked="checked" ';
    }
    echo '/>';
    helpbutton('showupdateforms', get_string('showupdateforms','stampcoll'), 'stampcoll');
    echo '</td></tr>';
    echo '<tr>';
    echo '<td colspan="2" align="right">';
    echo '<input type="submit" value="'.get_string('savepreferences').'" />';
    echo '</td></tr></table>';
    echo '</form>';
    ///End of mini form
    
    print_footer($course);
?>
