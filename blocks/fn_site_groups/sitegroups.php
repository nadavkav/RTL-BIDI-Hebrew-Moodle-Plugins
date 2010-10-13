<?php //$Id: sitegroups.php,v 1.10 2009/08/24 20:54:31 mchurch Exp $
/**
 * Manage group settings for FN site groups extension
 *
 * @copyright &copy; 2009 Northern Links
 * @author Mike Churchward AT Remote Learner Canada
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package FN site groups
 */

    define('DEFAULT_PAGE_SIZE', 20);
    define('SHOW_ALL_PAGE_SIZE', 5000);

    require_once('../../config.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->dirroot.'/blocks/fn_site_groups/lib.php');

    if (empty($CFG->block_fn_site_groups_enabled)) {
        return;
    }

    /// get url variables
    $courseid = optional_param('courseid', SITEID, PARAM_INT);
    $id       = optional_param('id', 0, PARAM_INT);
    $action   = optional_param('action', 'groups', PARAM_ALPHA);
    $nonmembertype = optional_param('nonmembertype', 'notingroup', PARAM_TEXT);

    if (! $course = get_record("course", "id", $courseid) ) {
        error("No such course id");
    }

    require_login($course);
    $context = get_context_instance(CONTEXT_COURSE, SITEID);
    require_capability('block/fn_site_groups:managegroups', $context);

    $capallusers = has_capability('block/fn_site_groups:assignallusers', $context);
    $capgroupusers = has_capability('block/fn_site_groups:assignowngroupusers', $context);
    $capcreatenewgroups = has_capability('block/fn_site_groups:createnewgroups', $context);

    $sgenrolment = in_array(FNSITEGROUPENROL, explode(',', $CFG->enrol_plugins_enabled));
    $returnurl = $CFG->wwwroot.'/blocks/fn_site_groups/sitegroups.php?id='.$course->id.'&amp;group='.$id;

    $strmanagegroups = get_string('managegroups', 'block_fn_site_groups');
    $navlinks[] = array('name' => $strmanagegroups, 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);

    print_header("$course->fullname: $strmanagegroups", $course->fullname,
                 $navigation, "", "", true, "&nbsp;", navmenu($course));


    $strgroupaddusers = get_string('groupaddusers', 'block_fn_site_groups');
    $strgroupinfopeople = get_string('groupinfpeople', 'block_fn_site_groups');
    $strgroupremove = get_string('groupremove', 'block_fn_site_groups');
    $strgroupinfo = get_string('groupinfo', 'block_fn_site_groups');
    $strgroupadd = get_string('groupadd', 'block_fn_site_groups');
    $strgroupingsadd = get_string('groupingsadd', 'block_fn_site_groups');
    $strgroupremovemembers = get_string('groupremovemembers', 'block_fn_site_groups');
    $strgroupinfomembers = get_string('groupinfomembers', 'block_fn_site_groups');
    $strgroupnonmembers = get_string('groupnonmembers', 'block_fn_site_groups');
    $strgroups = get_string('groups', 'block_fn_site_groups');
    $strgroupings = get_string('groupings', 'block_fn_site_groups');
    $strgroupmembersselected = get_string('groupmembersselected', 'block_fn_site_groups');
    $strgroupremovefromgrouping = get_string('groupremovefromgrouping', 'block_fn_site_groups');
    $strdeassignroles = get_string('deassignroles', 'block_fn_site_groups');

    $sesskey = sesskey();
    $listmembers = array();
    $nonmembers = array();
    $members = array();

    $currenttab = $action;
    include('tabs.php');

    switch ($action) {
        case 'enrol':
            if ($sgenrolment) {
                $groupingid = optional_param('groupingid', 0, PARAM_INT);
                $groupid    = optional_param('groupid', 0, PARAM_INT);
                $defrole    = get_default_course_role($course);
                $roleid     = optional_param('roleid', $defrole->id, PARAM_INT);

                // Non-cached - get accessinfo
                if ($capallusers) {
                    $userid = 0;
                } else if ($capgroupusers) {
                    $userid = $USER->id;
                }
                if (isset($USER->access)) {
                    $accessinfo = $USER->access;
                } else {
                    $accessinfo = get_user_access_sitewide($USER->id);
                }
                $acourses = get_user_courses_bycap($USER->id, 'moodle/role:assign', $accessinfo, true, 'c.fullname ASC', array('id','fullname'));
                if ($acourses) {
                    $scourses = array();
                    foreach ($acourses as $acourse) {
                        $scourses[$acourse->id] = $acourse->fullname;
                    }
                } else {
                    notify(get_string('nocoursesassigned', 'block_fn_site_groups'));
                    break;
                }

                /// If a valid course isn't currently selected, default to the first in the list.
                if (empty($courseid) || ($courseid == SITEID) || !key_exists($courseid, $scourses)) {
                    reset($scourses);
                    $courseid = key($scourses);
                }
                $course = get_record("course", "id", $courseid);

                $ccontext = get_context_instance(CONTEXT_COURSE, $courseid);

                if ($data = data_submitted() and confirm_sesskey()) {
                    if (!empty($data->scourseid)) {
                        if ($data->scourseid != $courseid) {
                            if (! $course = get_record("course", "id", $data->scourseid) ) {
                                error("No such course id");
                            } else {
                                $courseid = $data->scourseid;
                                $ccontext = get_context_instance(CONTEXT_COURSE, $courseid);
                            }
                        }
                    }

                    if (isset($data->enroluser) && !empty($data->notenrolled)) {
                        foreach ($data->notenrolled as $uid) {
                            role_assign($roleid, $uid, 0, $ccontext->id, 0, 0, 0, FNSITEGROUPENROL);
                        }
                    } else if (isset($data->unenroluser) && !empty($data->enrolled)) {
                        foreach ($data->enrolled as $uid) {
                            role_unassign($roleid, $uid, 0, $ccontext->id);
                        }
                    }
                }

                $groups = fn_sg_get_all_groups(SITEID, $userid);
                $roles = get_assignable_roles($ccontext);

                echo '<div class="fnsgpage">';
                echo '
<br />
<form name="form1" id="form1" method="post" action="sitegroups.php">
    <input type="hidden" name="id" value="'.s($courseid).'" />
    <input type="hidden" name="action" value="enrol" />
    <input type="hidden" name="sesskey" value="'.s($sesskey).'" />
                ';

                echo '<div align="center">'.get_string('course').': ';
                choose_from_menu ($scourses, 'scourseid', $courseid, '', 'this.form.submit();');
                echo '</div>';

                echo '
</form>
                ';

                /// Figure out what groupings there are, and then load the groups for the selected grouping.
                $groupings = array();
                $ggroups = array();
                $grid = 0;
                $fgrid = 0;
                foreach ($groups as $gid => $groupinfo) {
                    if (!is_numeric($gid)) {
                        $grid = $groupinfo->id;
                        if (!$fgrid) {
                            $fgrid = $grid;
                        }
                        $groupings[$grid] = $groupinfo->name;
                    } else {
                        $ggroups[$grid][$groupinfo->id] = $groupinfo->name;
                    }
                }
                if (!$groupingid) {
                    $groupingid = $fgrid;
                }
                if (!$groupid || (isset($ggroups[$groupingid]) && !in_array($groupid, $ggroups[$groupingid]))) {
                    reset($ggroups[$groupingid]);
                    $groupid = key($ggroups[$groupingid]);
                }
                if (empty($ggroups[$groupingid])) {
                    $groupid = 0;
                }

                /// Find the course group for the corresponding site group.
                $cgrouprec = get_record('block_fn_site_groups sg', 'sitegroupid', $groupid, 'courseid', $courseid);
                if (empty($cgrouprec)) {
                    $eusers = array();
                    if (!$unenrolledusers = get_group_users($groupid)) {
                        $unenrolledusers = array();
                    }
                } else {
                    /// Now get the members that are assigned / not assigned the selected role in the selected course.
                    if (!($enrolledusers = get_role_users($roleid, $ccontext, false, '', 'u.lastname ASC', true, $cgrouprec->coursegroupid))) {
                        $enrolledusers = array();
                    }
                    $eusers = array();
                    $except = '';
                    foreach ($enrolledusers as $enrolleduser) {
                        $eusers[$enrolleduser->id] = fullname($enrolleduser);
                        $except .= (empty($except) ? $enrolleduser->id : ','.$enrolleduser->id);
                    }
                    if (!($unenrolledusers = get_group_users($groupid, $sort='u.lastaccess DESC', $except))) {
                        $unenrolledusers = array();
                    }
                }
                $ueusers = array();
                foreach ($unenrolledusers as $unenrolleduser) {
                    $ueusers[$unenrolleduser->id] = fullname($unenrolleduser);
                }

                echo '
<form name="form1" id="form1" method="post" action="sitegroups.php">
  <input type="hidden" name="id" value="'.s($course->id).'" />
  <input type="hidden" name="courseid" value="'.s($courseid).'" />
  <input type="hidden" name="roleid" value="'.s($roleid).'" />
  <input type="hidden" name="action" value="enrol" />
  <input type="hidden" name="groupingid" value="'.s($groupingid).'" />
  <input type="hidden" name="groupid" value="'.s($groupid).'" />
  <input type="hidden" name="sesskey" value="'.s($sesskey).'" />
<table align="center" class="generalbox fnsgenrol">
    <tr>
      <td width="45%" class="generalboxcontent fnsgenrol selector notenrolled">
                ';

                echo '<div>'.get_string('groupings', 'group').': ';
                choose_from_menu ($groupings, 'groupingid', $groupingid, '', 'this.form.submit();');
                echo '</div>';

                echo '<div>'.get_string('groups').': ';
                if (!isset($ggroups[$groupingid])) {
                    $ggroups[$groupingid] = array();
                }
                choose_from_menu ($ggroups[$groupingid], 'groupid', $groupid, '', 'this.form.submit();');
                echo '</div>';

                echo '
      </td>
                ';

                echo '
      <td width = "10%">&nbsp;
                ';
                echo '
      </td>
                ';

                echo '
      <td width = "45%" class="generalboxcontent fnsgenrol selector enrolled">
                ';
                echo '<div class="sgcoursename">'.get_string('course').': '.s($course->fullname);
                echo '</div>';

                echo '<div>'.get_string('role').': ';
                choose_from_menu ($roles, 'roleid', $roleid, '', 'this.form.submit();');
                echo '</div>';
                echo '
      </td>
                ';

                echo '
    </tr>
    <tr>
      <td width="45%" class="generalboxcontent fnsgenrol selector notenrolled">
                ';

                echo '<div class="fnsgenrol notenrolled title">'.get_string('notenrolled', 'block_fn_site_groups').'</div>';
                echo '
          <select name="notenrolled[]" size="15" multiple="multiple" class="users">
                ';
                if (!empty($ueusers)) {
                    foreach ($ueusers as $id => $membername) {
                        echo "<option value=\"$id\">$membername</option>";
                    }
                }

                echo '
          </select>
                ';

                echo '
      </td>
                ';
                echo '
      <td width = "10%" class="middle">
                ';
                echo '
          <input type="submit" name="unenroluser" value="'.get_string('unenrolbutton', 'block_fn_site_groups').'" /><br /><br />
          <input type="submit" name="enroluser" value="'.get_string('enrolbutton', 'block_fn_site_groups').'" />
                ';
                echo '
      </td>
                ';

                echo '
      <td width = "45%" class="generalboxcontent fnsgenrol selector enrolled">
                ';
                echo '<div class="fnsgenrol enrolled title">'.get_string('enrolled', 'block_fn_site_groups').'</div>';
                echo '
          <select name="enrolled[]" size="15" multiple="multiple" class="users">
                ';
                if (!empty($eusers)) {
                    foreach ($eusers as $id => $membername) {
                        echo "<option value=\"$id\">$membername</option>";
                    }
                }

                echo '
          </select>
                ';

                echo '
      </td>
                ';
                echo '
    </tr>
</table>
</form>
                ';
                echo '</div>';
            }
            break;

        case 'groups':
            if ($data = data_submitted() and confirm_sesskey()) {

                if (!empty($data->nonmembersadd)) {            /// Add people to a group
                    if (!empty($data->nonmembers) && !empty($data->groupid) && is_numeric($data->groupid)) {
                        $groupmodified = false;
                        foreach ($data->nonmembers as $userid) {
                            groups_add_member($data->groupid, $userid);
                        }
                    }
                    $selectedgroup = $data->groupid;

                } else if (!empty($data->groupsremove)) {
                    if (is_numeric($data->groups)) {           /// Remove a group, all members become nonmembers
                        groups_delete_group($data->groups);
                    } else {
                        $grpingid = substr($data->groups, 2);
                        groups_delete_grouping($grpingid);
                    }

                } else if (!empty($data->groupsadd)) {         /// Create a new group
                    if (!empty($data->newgroupname)) {
                        $newgroup->name = $data->newgroupname;
                        $newgroup->courseid = $course->id;
                        $newgroup->description = '';
                        $newgroup->enrolmentkey = '';
                        $newgroup->picture = 0;
                        $newgroup->hidepicture = 0;
                        $newgroup->id = groups_create_group($newgroup);

                        if (!empty($data->groups) && !is_numeric($data->groups)) {
                            $groupingid = substr($data->groups, 2);
                            groups_assign_grouping($groupingid, $newgroup->id);
                        }
                    }

                } else if (!empty($data->groupingsadd)) {         /// Create a new grouping
                    if (!empty($data->newgroupingsname)) {
                        $newgrouping->name = $data->newgroupingsname;
                        $newgrouping->courseid = $course->id;
                        groups_create_grouping($newgrouping);
                    }

                } else if (!empty($data->groupsremovefrom)) {     /// Remove group from groupings
                    if (!empty($data->groups) && is_numeric($data->groups)) {
                        if ($groupings = get_records('groupings_groups', 'groupid', $data->groups)) {
                            foreach ($groupings as $grouping) {
                                groups_unassign_grouping($grouping->groupingid, $data->groups);
                            }
                        }
                    }

                } else if (!empty($data->groupingsmove)) {     /// Move group to groupings
                    if (!empty($data->groups) && is_numeric($data->groups)) {
                        if ($groupings = get_records('groupings_groups', 'groupid', $data->groups)) {
                            foreach ($groupings as $grouping) {
                                groups_unassign_grouping($grouping->groupingid, $data->groups);
                            }
                        }
                        groups_assign_grouping($data->groupingsmove, $data->groups);
                    }

                } else if (!empty($data->membersremove)) {     /// Remove selected people from a particular group

                    if (!empty($data->members) && !empty($data->groupid) && is_numeric($data->groupid)) {
                        foreach ($data->members as $userid) {
                            groups_remove_member($data->groupid, $userid);
//                            og_deassign_group_registrar($userid);
//                            og_set_student($userid); /// Deassigns as a designated teacher.
                        }
                    }
                    $selectedgroup = $data->groupid;

                } else if (!empty($data->assignrole)) {
                    foreach ($data->members as $userid) {
                        role_assign($data->assignrole, $userid, 0, $context->id);
                    }
                    $selectedgroup = $data->groupid;

                } else if (!empty($data->deassignroles)) {
                    foreach ($data->members as $userid) {
                        $uroles = get_user_roles($context, $userid, false);
                        foreach ($uroles as $urole) {
                        /// Don't unassign the configured default role.
                            if ($urole->roleid != $CFG->block_fn_site_groups_defaultroleid) {
                                role_unassign($urole->roleid, $userid, 0, $context->id);
                            }
                        }
                    }
                    $selectedgroup = $data->groupid;

                } else if (!empty($data->membersinfo)) {       /// Return info about the selected users
                    notify("You must turn Javascript on");

                } else if (!empty($data->sitegroupnameupdate)) {
                    set_config('fnsitegroupname', clean_param($data->sitegroupname, PARAM_CLEAN));
                    set_config('fnsitegroupnameplural', clean_param($data->sitegroupnameplural, PARAM_CLEAN));
                    if (empty($CFG->fnsitegroupname)) {
                        set_config('fnsitegroupname', get_string('group'));
                    }
                    if (empty($CFG->fnsitegroupnameplural)) {
                        set_config('fnsitegroupnameplural', get_string('groups'));
                    }
                }
            }

            if ($capallusers) {
                $userid = 0;
            } else if ($capgroupusers) {
                $userid = $USER->id;
            }
            $groups = fn_sg_get_all_groups($courseid, $userid);

            $groupings = array();
            if ($agroupings = groups_get_all_groupings(SITEID)) {
                foreach ($agroupings as $agrouping) {
                    $groupings[$agrouping->id] = $agrouping->name;
                }
            }

            /// Get roles for the role assign function...
            $roles = array();
            if (!empty($CFG->block_fn_site_groups_roles)) {
                $select = 'id IN (' . $CFG->block_fn_site_groups_roles . ')';
                if ($aroles = get_records_select('role', $select)) {
                    foreach ($aroles as $arole) {
                        /// Don't include the default role...
                        if ($arole->id != $CFG->block_fn_site_groups_defaultroleid) {
                            $roles[$arole->id] = $arole->name;
                        }
                    }
                }
            }

            /// First, get everyone into the nonmembers array
            $select = 'deleted = 0 AND confirmed = 1 AND username != \'guest\' AND username NOT LIKE \'changeme%\'';
            $students = get_records_select('user', $select, 'lastname ASC', 'id,firstname,lastname');
            if (substr($nonmembertype, 0, 6) != 'group_') {
                $sgroupid = false;
            } else if (substr($nonmembertype, 6, 1) == 'g') {
                $sgroupid = 'g'.substr($nonmembertype, 8);
            } else {
                $sgroupid = (int)substr($nonmembertype, 6);
            }
            if ($students) {
                foreach ($students as $student) {
                    $nonmembers[$student->id] = fullname($student, true);
                }
                //unset($students);
            }

            if ($groups) {
                $gusers = false;
                foreach ($groups as $idx => $group) {
                    /// Skip groupings.
                    if (!is_numeric($idx)) {
                        continue;
                    }
                    $countusers = 0;
                    $listmembers[$group->id] = array();
                    if (!($groupusers = get_group_users($group->id))) {
                        $groupusers = array();
                    }

                    if ($sgroupid === $group->id) {
                        $gusers = $groupusers;

                    /// If its a grouping selection:
                    } else if (($sgroupid !== false) && !is_numeric($sgroupid)) {
                        $gusers = array();
                    }

                    foreach ($groupusers as $groupuser) {
                        if ($groupuser->deleted == 1) {
                            unset($nonmembers[$groupuser->id]);
                            groups_remove_member($group->id, $groupuser->id);
//                            og_set_student($groupuser->id); /// Deassigns as a designated teacher.
//                            og_deassign_group_registrar($groupuser->id);
                        } else if (substr($groupuser->username, 0, 8) == 'changeme') {
                            unset($nonmembers[$groupuser->id]);
                        } else if (isset($nonmembers[$groupuser->id])) {
                            $listmembers[$group->id][$groupuser->id] = $nonmembers[$groupuser->id];
                            if ($nonmembertype == 'notingroup') {
                                unset($nonmembers[$groupuser->id]);
                            }
                            $countusers++;
                        } else {
                            $listmembers[$group->id][$groupuser->id] = fullname($students[$groupuser->id], true);
                            $countusers++;
                        }
                    }
                    $fmembers = fn_sg_groups_userids_to_user_names(false, $course->id, $listmembers[$group->id]);
                    $listmembers[$group->id] = array();
                    foreach ($fmembers as $fmember) {
                        $listmembers[$group->id][$fmember->id] = $fmember->name;
                    }
                    natcasesort($listmembers[$group->id]);

                    if (is_numeric($idx)) {
                        $groups[$idx]->name .= " ($countusers)";
                    }
                }
                if (!empty($listgroups)) {
                    natcasesort($listgroups);
                }
            }

            if (!$capallusers && (($nonmembertype == 'all') || ($nonmembertype == 'notingroup'))) {
                $gusers = array();
                $sgroupid = true;
            }

            if (($sgroupid !== false) && is_array($gusers)) {
                $nonmembers = array();
                foreach ($gusers as $guser) {
                    $nonmembers[$guser->id] = fullname($guser, true);
                }
            }

            if (!empty($nonmembers)) {
                natcasesort($nonmembers);
            }

            /// If no user assignment caps, take all users away.
            if (!$capallusers && !$capgroupusers) {
                $nonmembers = array();

            }

            if (empty($selectedgroup) || !is_numeric($selectedgroup)) {    // Choose the first group by default
                if (!empty($listgroups) && ($selectedgroup = array_shift(array_keys($listgroups)))) {
                    $members = $listmembers[$selectedgroup];
                }
            } else {
                $members = $listmembers[$selectedgroup];
            }

            /// Print out the complete form

            if (!empty($groups)) {
//                uasort($groups, 'fn_sg_sitegroup_name_sort');
            }

            include ('fngroups-edit.html');
            break;

        case 'users':
            $page         = optional_param('page', 0, PARAM_INT);                     // which page to show
            $perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page
            $currentgroup = optional_param('groupid', 0, PARAM_INT);
            $currentgrpng = optional_param('grpngid', 0, PARAM_INT);
            $baseurl      = $CFG->wwwroot.'/blocks/fn_site_groups/sitegroups.php?courseid='.$courseid.'&amp;id='.$id.'&amp;action=users'.
                            '&amp;groupid='.$currentgroup.'&amp;grpngid='.$currentgrpng.'&amp;perpage='.$perpage;

            $groupings = array();
            if ($agroupings = groups_get_all_groupings(SITEID)) {
                $groupings[0] = get_string('all');
                foreach ($agroupings as $agrouping) {
                    $groupings[$agrouping->id] = $agrouping->name;
                }
            }
            if ($agroups = groups_get_all_groups($courseid, 0, $currentgrpng)) {
                if ($currentgrpng == 0) {
                    $groups[0] = get_string('all');
                }
                foreach ($agroups as $agroup) {
                    $groups[$agroup->id] = $agroup->name;
                }
            }

            if (!key_exists($currentgroup, $groups)) {
                reset($groups);
                $currentgroup = key($groups);
            }

            print_box_start('boxaligncenter centerpara');
            echo '
<form name="form1" id="form1" method="post" action="sitegroups.php">
    <input type="hidden" name="id" value="'.s($course->id).'" />
    <input type="hidden" name="courseid" value="'.s($course->id).'" />
    <input type="hidden" name="action" value="users" />
    <input type="hidden" name="perpage" value="'.s($perpage).'" />
    <input type="hidden" name="sesskey" value="'.s($sesskey).'" />
            ';
            echo $strgroupings.': ';
            choose_from_menu ($groupings, 'grpngid', $currentgrpng, '', 'this.form.submit();');
            echo '<br/>'.$strgroups.': ';
            choose_from_menu ($groups, 'groupid', $currentgroup, '', 'this.form.submit();');
            echo '<br /><br />';
            echo '</form>';

            $countries = get_list_of_countries();
            $strnever = get_string('never');

            $datestring->year  = get_string('year');
            $datestring->years = get_string('years');
            $datestring->day   = get_string('day');
            $datestring->days  = get_string('days');
            $datestring->hour  = get_string('hour');
            $datestring->hours = get_string('hours');
            $datestring->min   = get_string('min');
            $datestring->mins  = get_string('mins');
            $datestring->sec   = get_string('sec');
            $datestring->secs  = get_string('secs');

            /// Define a table showing a list of users in the current role selection
            $tablecolumns = array('userpic', 'fullname');
            $tableheaders = array('', get_string('fullname'));
            if (!isset($hiddenfields['city'])) {
                $tablecolumns[] = 'city';
                $tableheaders[] = get_string('city');
            }
            if (!isset($hiddenfields['lastaccess'])) {
                $tablecolumns[] = 'lastaccess';
                $tableheaders[] = get_string('lastaccess');
            }

            if ($course->enrolperiod) {
                $tablecolumns[] = 'timeend';
                $tableheaders[] = get_string('enrolmentend');
            }

            if (!empty($bulkoperations)) {
                $tablecolumns[] = '';
                $tableheaders[] = get_string('select');
            }

            $table = new flexible_table('user-index-participants-'.$course->id);

            $table->define_columns($tablecolumns);
            $table->define_headers($tableheaders);
            $table->define_baseurl($baseurl);

            if (!isset($hiddenfields['lastaccess'])) {
                $table->sortable(true, 'lastaccess', SORT_DESC);
            }

            $table->set_attribute('cellspacing', '0');
            $table->set_attribute('id', 'participants');
            $table->set_attribute('class', 'generaltable generalbox');
            $table->set_attribute('align', 'center');

            $table->set_control_variables(array(
                        TABLE_VAR_SORT    => 'ssort',
                        TABLE_VAR_HIDE    => 'shide',
                        TABLE_VAR_SHOW    => 'sshow',
                        TABLE_VAR_IFIRST  => 'sifirst',
                        TABLE_VAR_ILAST   => 'silast',
                        TABLE_VAR_PAGE    => 'spage'
                        ));
            $table->setup();

            $select = 'SELECT u.id, u.username, u.firstname, u.lastname,
                          u.email, u.city, u.country, u.picture,
                          u.lang, u.timezone, u.emailstop, u.maildisplay, u.imagealt,
                          u.lastaccess,
                          ctx.id AS ctxid, ctx.path AS ctxpath,
                          ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel ';

            $from = "FROM {$CFG->prefix}user u
                    LEFT OUTER JOIN {$CFG->prefix}context ctx
                        ON (u.id=ctx.instanceid AND ctx.contextlevel = ".CONTEXT_USER.") ";

            $where = "WHERE u.deleted = 0
                AND u.username != 'guest'";

            $totalcount = count_records_sql('SELECT COUNT(distinct u.id) '.$from.$where);   // Each user could have > 1 role

            if ($table->get_sql_where()) {
                $where .= ' AND '.$table->get_sql_where();
            }

            if ($currentgroup) {    // Displaying a group by choice
                // FIX: TODO: This will not work if $currentgroup == 0, i.e. "those not in a group"
                $from  .= 'LEFT JOIN '.$CFG->prefix.'groups_members gm ON u.id = gm.userid ';
                $where .= ' AND gm.groupid = '.$currentgroup;
            }

            if ($table->get_sql_sort()) {
                $sort = ' ORDER BY '.$table->get_sql_sort();
            } else {
                $sort = '';
            }

            $matchcount = count_records_sql('SELECT COUNT(distinct u.id) '.$from.$where);

            $table->initialbars(true);
            $table->pagesize($perpage, $matchcount);

            $userlist = get_recordset_sql($select.$from.$where.$sort,
            $table->get_page_start(),  $table->get_page_size());

            $countrysort = (strpos($sort, 'country') !== false);
            $timeformat = get_string('strftimedate');

            if ($userlist)  {
                $usersprinted = array();
                while ($user = rs_fetch_next_record($userlist)) {
                    if (in_array($user->id, $usersprinted)) { /// Prevent duplicates by r.hidden - MDL-13935
                        continue;
                    }
                    $usersprinted[] = $user->id; /// Add new user to the array of users printed

                    $user = make_context_subobj($user);
                    if ( !empty($user->hidden) ) {
                    // if the assignment is hidden, display icon
                        $hidden = " <img src=\"{$CFG->pixpath}/t/show.gif\" title=\"".get_string('userhashiddenassignments', 'role')."\" alt=\"".get_string('hiddenassign')."\" class=\"hide-show-image\"/>";
                    } else {
                        $hidden = '';
                    }

                    if ($user->lastaccess) {
                        $lastaccess = format_time(time() - $user->lastaccess, $datestring);
                    } else {
                        $lastaccess = $strnever;
                    }

                    if (empty($user->country)) {
                        $country = '';

                    } else {
                        if($countrysort) {
                            $country = '('.$user->country.') '.$countries[$user->country];
                        }
                        else {
                            $country = $countries[$user->country];
                        }
                    }

                    if (!isset($user->context)) {
                        $usercontext = get_context_instance(CONTEXT_USER, $user->id);
                    } else {
                        $usercontext = $user->context;
                    }

                    if ($piclink = ($USER->id == $user->id || has_capability('moodle/user:viewdetails', $context) || has_capability('moodle/user:viewdetails', $usercontext))) {
                        $profilelink = '<strong><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.fullname($user).'</a></strong>';
                    } else {
                        $profilelink = '<strong>'.fullname($user).'</strong>';
                    }

                    $data = array (
                            print_user_picture($user, $course->id, $user->picture, false, true, $piclink),
                            $profilelink . $hidden);

                    if (!isset($hiddenfields['city'])) {
                        $data[] = $user->city;
                    }
                    if (!isset($hiddenfields['country'])) {
                        $data[] = $country;
                    }
                    if (!isset($hiddenfields['lastaccess'])) {
                        $data[] = $lastaccess;
                    }
                    if ($course->enrolperiod) {
                        if ($user->timeend) {
                            $data[] = userdate($user->timeend, $timeformat);
                        } else {
                            $data[] = get_string('unlimited');
                        }
                    }
                    if (!empty($bulkoperations)) {
                        $data[] = '<input type="checkbox" name="user'.$user->id.'" />';
                    }
                    $table->add_data($data);

                }
            }

            $table->print_html();

            print_box_end();
            break;
    }

    print_footer($course);

?>