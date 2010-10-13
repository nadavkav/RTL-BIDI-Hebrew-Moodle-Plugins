<?php

define ('FNSITEGROUPENROL', 'fnsitegroups');    // This is the fn enrolment plug-in for site groups.
define ('FNGRPMANROLENAME', 'Group Manager');
define ('FNGRPMANROLESNAME', 'grpman');
define ('FNGRPMANROLEDESC', 'Manages site groups.');

/**
 * This function creates the course groups and associates with it the site group.
 *
 * @param int $groupid The site group id.
 * @param object $group The course group data.
 * @return boolean success.
 *
 */
    function fn_sg_create_group($groupid, $group) {

        /// This will generate another group create event. Will that be a problem?
        if (!($newid = groups_create_group($group))) {
            trigger_error('Could not create course group!');
            return false;
        }

        $sgrec = new Object();
        $sgrec->sitegroupid = $groupid;
        $sgrec->coursegroupid = $newid;
        $sgrec->courseid = $group->courseid;
        if (insert_record('block_fn_site_groups', $sgrec)) {
            return $newid;
        } else {
            return false;
        }
    }

/**
 * Get all FN Site Groups. If a course is specified, and the course is using the FN Site
 * Groups enrolment plug-in, then return the groups currently in use in the course.
 *
 * @param int    $courseid   The id of the requested course
 * @param int    $userid     The id of the requested user or all.
 * @param int    $groupingid The id of the requested grouping or all.
 * @param string $fields     The fields to return or all.
 * @return array
 */
    function fn_sg_get_all_groups($courseid=SITEID, $userid=0, $groupingid=0, $fields='g.*') {
        global $CFG;

        if (!($course = get_record('course', 'id', $courseid, null, null, null, null, 'id,enrol'))) {
            return false;
        }

        if (($courseid != SITEID) && (fn_sg_course_uses_sgenrol($course))) {
        /// Get site groups used in course.
            $agroups = false;
        } else {
            $agroups = groups_get_all_groups(SITEID, $userid, $groupingid, $fields);
        }

        if (!is_array($agroups)) {
            $agroups = array();
        }

        if (!($groupings = groups_get_all_groupings(SITEID))) {
            $groupings = array();
        }

        $groups = array();
        foreach ($groupings as $groupingid => $grouping) {
            $groups['g_'.$groupingid] = $grouping;
            if ($ggroups = groups_get_all_groups(SITEID, $userid, $groupingid, $fields)) {
                foreach ($ggroups as $ggroupid => $ggroup) {
                    $ggroup->name = ' - '.$ggroup->name;
                    $groups[$ggroupid] = $ggroup;
                    unset($agroups[$ggroupid]);
                }
            }
        }

        $nogrouping = new Object();
        $nogrouping->id = 0;
        $nogrouping->name = 'NOT IN GROUPING';
        foreach ($agroups as $agroup) {
            $agroup->name = ' - ' . $agroup->name;
        }

        if (!empty($agroups)) {
            $groups = $groups + array('g_0' => $nogrouping) + $agroups;
        }
        return $groups;
    }

/**
 * Returns true if the specified course is using site group enrolment.
 *
 * @param object $course The course in question. Requires the 'enrol' field as a minimum.
 * @return boolean
 */
    function fn_sg_course_uses_sgenrol($course) {
        global $CFG;
        require_once($CFG->dirroot.'/enrol/enrol.class.php');

        /// We don't add course groups to courses using the site group enrolment plug-in.
        $enrol = enrolment_factory::factory($course->enrol);
        $enrolp = get_class($enrol);
        $enrolp = substr($enrolp, strlen('enrolment_plugin_'));
        return ($enrolp == FNSITEGROUPENROL);
    }

/**
 * Get an sorted array of user-id/display-name objects.
 *
 * @param array $userids
 * @param int   $courseid
 * @param array $users
 * @return array
 */
    function fn_sg_groups_userids_to_user_names($userids, $courseid, $users=null) {
        global $CFG;
        static $rusers;

        if (!$userids && !$users) {
            return array();
        }

        $context = get_context_instance(CONTEXT_COURSE, SITEID);

        if (empty($rusers) && !empty($CFG->block_fn_site_groups_roles)) {
            $roleids = explode(',', $CFG->block_fn_site_groups_roles);
            foreach($roleids as $roleid) {
                /// Don't include the default role...
                if ($roleid != $CFG->block_fn_site_groups_defaultroleid) {
                    if ($role = get_record('role', 'id', $roleid)) {
                        $ridx = substr($role->name, 0, 4);
                        $rusers[$ridx] = get_role_users($roleid, $context, true, 'u.id,u.firstname,u.lastname');
                    }
                }
            }
        }

        $member_names = array();
        if (!$users) {
            foreach ($userids as $id) {
                $user = new object;
                $user->id = $id;
                $user->name = fn_sg_groups_get_user_displayname($id, $courseid, $rusers);
                $member_names[] = clone($user);
            }
        } else {
            foreach ($users as $id => $usern) {
                $user = new object;
                $user->id = $id;
                $user->name = fn_sg_groups_get_user_displayname($id, $courseid, $rusers, $usern);
                $member_names[] = clone($user);
            }
        }
        if (! usort($member_names, 'fn_sg_groups_compare_name')) {
            debug('Error usort [og_groups_compare_name].');
        }
        return $member_names;
    }

    /**
     * Returns the display name of a user - the full name of the user
     * prefixed by '#' for editing teachers and '-' for teachers.
     * @param int $userid The ID of the user.
     * @param int $courseid The ID of the related-course.
     * @return string The display name of the user.
     */
    function fn_sg_groups_get_user_displayname($userid, $courseid, &$rusers, $user=null) {
        if ($courseid == false) {
            $fullname = false;
        } else {
            if (!$user) {
                if ($user = get_record('user', 'id', $userid)) {
                    $fullname = fullname($user, true);
                } else {
                    $fullname = '';
                }
            } else {
                $fullname = $user;
            }

            $prefix= ' ';
            $postfix = '';
            if (isadmin($userid)) {
//                $prefix = '# ';
            }

            if (is_array($rusers)) {
                foreach ($rusers as $rabbr => $ruser) {
                    if (is_array($ruser) && array_key_exists($userid, $ruser)) {
                        $postfix .= (empty($postfix) ? '' : ',') . $rabbr;
                    }
                }
            }
            if (!empty($postfix)) {
                $postfix = '(' . $postfix . ')';
            }
            $fullname = $prefix.$fullname.$postfix;
        }
        return $fullname;
    }

    /**
     * Comparison function for 'usort' on objects with a name member.
     * Equivalent to 'natcasesort'.
     */
    function fn_sg_groups_compare_name($obj1, $obj2) {
        if (!$obj1 || !$obj2 || !isset($obj1->name) || !isset($obj2->name)) {
            debug('Error, groups_compare_name.');
        }
        return strcasecmp($obj1->name, $obj2->name);
    }

/**
 *
 * @param $a
 * @param $b
 * @return unknown_type
 */
    function fn_sg_sitegroup_name_sort($a, $b) {
        return (strcmp($a->name, $b->name));
    }

/**
 * Set the site course group mode.
 *
 * @param $settingname
 * @return unknown_type
 */
    function fn_sg_set_site_group_mode($settingname) {
        global $CFG;

        if ($CFG->block_fn_site_groups_enabled) {
            set_field('course', 'groupmode', VISIBLEGROUPS, 'id', SITEID);
        } else {
            set_field('course', 'groupmode', NOGROUPS, 'id', SITEID);
        }
        return true;
    }

/**
 * Called from the settings form. Assigns or unassigns capabilities from Group Manager role.
 * Assumes that this role exists, and that capabilities have numeric values 1 and 2 from form.
 *
 * @param $settingname
 * @return unknown_type
 */
    function fn_sg_set_user_capability($settingname) {
        global $CFG;

        $context = get_system_context();
        $role = get_record('role', 'shortname', FNGRPMANROLESNAME);

        if ($settingname == 's__block_fn_site_groups_users') {
            $capsset = explode(',', $CFG->block_fn_site_groups_users);
            if (in_array(1, $capsset)) {
                assign_capability('block/fn_site_groups:assignowngroupusers', CAP_ALLOW, $role->id, $context->id);
            } else {
                unassign_capability('block/fn_site_groups:assignowngroupusers', $role->id, $context->id);
            }
            if (in_array(2, $capsset)) {
                assign_capability('block/fn_site_groups:assignallusers', CAP_ALLOW, $role->id, $context->id);
            } else {
                unassign_capability('block/fn_site_groups:assignallusers', $role->id, $context->id);
            }
        } else if ($settingname == 's__block_fn_site_groups_creategroups') {
            if (!empty($CFG->block_fn_site_groups_creategroups)) {
                assign_capability('block/fn_site_groups:createnewgroups', CAP_ALLOW, $role->id, $context->id);
            } else {
                unassign_capability('block/fn_site_groups:createnewgroups', $role->id, $context->id);
            }
        }
        return true;
    }
?>