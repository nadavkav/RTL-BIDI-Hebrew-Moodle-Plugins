<?php
require_once($CFG->libdir.'/grouplib.php');
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->dirroot.'/blocks/fn_site_groups/lib.php');

/**
 * This function is called when a 'course_created' event is triggered. It will then create course groups
 * for each corresponding site group in that course. This option must be enabled for this to happen.
 *
 *  @param object $eventdata The course object created.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_course_created_handler($eventdata) {
        global $CFG;

    /// If we aren't using site groups, do nothing.
        if (empty($CFG->block_fn_site_groups_enabled)) {
            return true;
        }

        $groups = groups_get_all_groups(SITEID);
        if (empty($groups)) {
            $groups = array();
        }

        foreach ($groups as $group) {
            $grpdata = new Object();
            $grpdata->name = $group->name;
            $grpdata->description = $group->description;
            $grpdata->enrolmentkey = $group->enrolmentkey;
            $grpdata->hidepicture = $group->hidepicture;
            $grpdata->courseid = $eventdata->id;
            $grpdata->picture = $group->picture;

            fn_sg_create_group($group->id, $grpdata);
        }
        return true;
    }

/**
 * This function is called when a 'course_deleted' event is triggered. It will then remove all
 * site group records for that course from the block_fn_site_groups table.
 *
 *  @param object $eventdata The course object deleted.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_course_deleted_handler($eventdata) {
        global $CFG;

        delete_records('block_fn_site_groups', 'courseid', $eventdata->id);

        return true;
    }

/**
 * This function is called when a 'groups_member_added' event is triggered. It will add the same user
 * to the corresponding site group. If member is added to the site group, will add that same member to
 * all similar course groups for courses the user is enrolled in.
 *
 *  @param object $eventdata The user and group id.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_groups_member_added_handler($eventdata) {
        return fn_groups_member_change($eventdata, 'groups_add_member');
    }

/**
 * This function is called when a 'groups_member_removed' event is triggered. It will remove the same user
 * from the corresponding site group. If member is removed from the site group, will remove that same member from
 * all similar course groups for courses the user is enrolled in.
 *
 *  @param object $eventdata The user and group id.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_groups_member_removed_handler($eventdata) {
        return fn_groups_member_change($eventdata, 'groups_remove_member');
    }

/**
 * This function does all the work for the add_member and remove_member events. The code is essentially the same
 * for both. The only difference is the group function called: add or remove.
 *
 *  @param object $eventdata The user and group id.
 *  @param string $function The group function to apply.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_groups_member_change($eventdata, $function) {
        global $CFG;
//        static $eventinprogress;

    /// Set a semaphore so that we don't do this for any new member adds from here.
    /// Without this, we would do the same operations again for every new membership made in this function.
//        if (!empty($eventinprogress)) {
//            return true;
//        } else {
//            $eventinprogress = true;
//        }

    /// If we aren't using site groups, do nothing.
        if (empty($CFG->block_fn_site_groups_enabled)) {
            return true;
        }

        /// If data is incomplete, do nothing.
        if (empty($eventdata->groupid) || empty($eventdata->userid)) {
            trigger_error('Event groups_member_added sent invalid data.');
            return true;
        }

        $cgroup = groups_get_group($eventdata->groupid);
        /// Should never happen.
        if (empty($cgroup)) {
            trigger_error('Event groups_member_added sent invalid group.');
            return true;
        }

    /// If added to a course group, add them to the site group.
        if ($cgroup->courseid != SITEID) {
            $sql = 'SELECT g.* '.
                   'FROM '.$CFG->prefix.'block_fn_site_groups sg '.
                   'INNER JOIN '.$CFG->prefix.'groups g ON g.id = sg.sitegroupid '.
                   'WHERE sg.coursegroupid = '.$cgroup->id;
            $sgroup = get_record_sql($sql);
        /// Might happen. Problem if a site group with same name doesn't exist.
            if (empty($sgroup)) {
                trigger_error('Event groups_member_added could not find matching site group.');
                return true;
            }

            $function($sgroup->id, $eventdata->userid);

        } else {
            $sgroup = $cgroup;
        }

    /// Find all the courses this user is in and add them to those groups.
        $courses = get_user_courses_bycap($eventdata->userid, 'moodle/course:view', get_user_access_sitewide($eventdata->userid), false);
        if (empty($courses)) {
            $courses = array();
        }

        foreach ($courses as $course) {
            if ($cgroupid = get_field('block_fn_site_groups', 'coursegroupid', 'sitegroupid', $sgroup->id, 'courseid', $course->id)) {
                $function($cgroupid, $eventdata->userid);
            }
        }

        return true;
    }

/**
 * This function is called when a 'role_assigned' event is triggered. It will cause the user that was
 * enrolled to be added to the same corresponding course group for the site group they are a member of.
 *
 *  @param object $eventdata The role_assignments record.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_role_assigned_handler($eventdata) {
        global $CFG;

    /// If we aren't using site groups, do nothing.
        if (empty($CFG->block_fn_site_groups_enabled)) {
            return true;
        }

        $context = get_context_instance_by_id($eventdata->contextid);

        /// Should never happen.
        if (empty($context)) {
            trigger_error('Event role_assigned sent invalid context.');
            return true;
        }

        /// Only care about course assigns, and not site course.
        if (($context->contextlevel != CONTEXT_COURSE) || ($context->instanceid == SITEID)) {
            return true;
        }

        $groups = groups_get_all_groups(SITEID, $eventdata->userid);

        /// No groups, nothing to do.
        if (empty($groups)) {
            return true;
        }

        foreach ($groups as $group) {
            if (!($cgid = get_field('block_fn_site_groups', 'coursegroupid', 'sitegroupid', $group->id, 'courseid', $context->instanceid))) {
            /// If no course group exists for that site group, create one.
                $cg = new Object();
                $cg->name          = $group->name;
                $cg->description   = $group->description;
                $cg->enrolmentkey  = $group->enrolmentkey;
                $cg->hidepicture   = $group->hidepicture;
                $cg->courseid      = $context->instanceid;
                $cg->picture       = $group->picture;
            /// This will generate another group create event. Will that be a problem?
                $cgid = fn_sg_create_group($group->id, $cg);
            }
            groups_add_member($cgid, $eventdata->userid);
        }
        return true;
    }

/**
 * This function is called when a 'user_created' event is triggered, and hopefully every time a new user
 * is created. It will assign the defined user to the configured role at the front page level, so that
 * the user can be added to site groups.
 *
 *  @param object $eventdata The user object created.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_user_created_handler($eventdata) {
        global $CFG;

    /// If we aren't using site groups, do nothing.
        if (empty($CFG->block_fn_site_groups_enabled)) {
            return true;
        }

    /// If no specific role has been configured, use the default role for a course in the user policies.
        if (empty($CFG->block_fn_site_groups_defaultroleid)) {
            $course = get_site();
            $role = get_default_course_role($course);
            $defaultroleid = $role->id;
        } else {
            $defaultroleid = $CFG->block_fn_site_groups_defaultroleid;
        }

    /// If the role is empty, there's nothing else we can do.
        if (!empty($defaultroleid)) {
            $context = get_context_instance(CONTEXT_COURSE, SITEID);
            role_assign($defaultroleid, $eventdata->id, false, $context->id);
        }

        return true;
    }

/**
 * This function is called when a 'group_created' event is triggered. It will then create a course group
 * for the corresponding site group in each course. This option must be enabled for this to happen.
 * If the site group enrolment scheme is in place for a course, it should not create the group.
 *
 *  @param object $eventdata The course object created.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_group_created_handler($eventdata) {
        global $CFG;
        require_once("$CFG->dirroot/enrol/enrol.class.php");

    /// If we aren't using site groups, do nothing.
        if (empty($CFG->block_fn_site_groups_enabled)) {
            return true;
        }

        /// Only care about site groups.
        if ($eventdata->courseid != SITEID) {
            return true;
        }

        /// If no courses, nothing to do.
        if (!($courses = get_courses(null, "c.id", $fields="c.id,c.enrol"))) {
            return true;
        }

        foreach ($courses as $course) {
            if ($course->id != SITEID) {

            /// We don't add course groups to courses using the site group enrolment plug-in.
                if (fn_sg_course_uses_sgenrol($course)) {
                    continue;
                }

                $grpdata = new Object();
                $grpdata->name = $eventdata->name;
                $grpdata->description = $eventdata->description;
                $grpdata->enrolmentkey = $eventdata->enrolmentkey;
                $grpdata->hidepicture = $eventdata->hidepicture;
                $grpdata->courseid = $course->id;
                $grpdata->picture = $eventdata->picture;

            /// This will generate another group create event. Will that be a problem?
                fn_sg_create_group($eventdata->id, $grpdata);
            }
        }
        return true;
    }

/**
 * This function is called when a 'group_deleted' event is triggered.
 *
 *  @param object $eventdata The group object deleted.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_group_deleted_handler($eventdata) {
        global $CFG;
        static $eventinprogress;
        require_once("$CFG->dirroot/enrol/enrol.class.php");

    /// Set a semaphore so that we don't do this for any group deleted from here.
    /// Without this, we would do the same operations again for every delete made in this function.
        if (!empty($eventinprogress)) {
            return true;
        } else {
            $eventinprogress = true;
        }

        if ($eventdata->courseid == SITEID) {
        /// Remove all associated course groups and associations.
            if (!($cgroups = get_records('block_fn_site_groups', 'sitegroupid', $eventdata->id))) {
            /// If no courses, nothing to do.
                return true;
            }

            foreach ($cgroups as $cgroup) {
                groups_delete_group($cgroup->coursegroupid);
                delete_records('block_fn_site_groups', 'id', $cgroup->id);
            }
        } else {
        /// Remove the course group association.
            delete_records('block_fn_site_groups', 'coursegroupid', $eventdata->id);
        }
        return true;
    }

/**
 * This function is called when a 'group_updated' event is triggered.
 *
 *  @param object $eventdata The course object created.
 *  @return boolean Always return true so that the event gets cleared.
 *
 */
    function fn_group_updated_handler($eventdata) {
        global $CFG;
        static $eventinprogress;
        require_once("$CFG->dirroot/enrol/enrol.class.php");

    /// Set a semaphore so that we don't do this for any group updated from here.
    /// Without this, we would do the same operations again for every update made in this function.
        if (!empty($eventinprogress)) {
            return true;
        } else {
            $eventinprogress = true;
        }

        /// If we aren't using site groups, do nothing.
        if (empty($CFG->block_fn_site_groups_enabled)) {
            return true;
        }

        /// Only care about site groups.
        if ($eventdata->courseid != SITEID) {
            return true;
        }

        /// If no courses, nothing to do.
        if (!($courses = get_records('block_fn_site_groups', 'sitegroupid', $eventdata->id))) {
            return true;
        }

        foreach ($courses as $course) {
            if ($course->courseid != SITEID) {
                $um = false;
                $grpdata = new Object();
                $grpdata->id = $course->coursegroupid;
                if (isset($eventdata->name)) {
                    $grpdata->name = $eventdata->name;
                }
                if (isset($eventdata->description)) {
                    $grpdata->description = $eventdata->description;
                }
                if (isset($eventdata->enrolmentkey)) {
                    $grpdata->enrolmentkey = $eventdata->enrolmentkey;
                }
                if (isset($eventdata->hidepicture)) {
                    $grpdata->hidepicture = $eventdata->hidepicture;
                }
                $grpdata->courseid = $course->courseid;
                if (isset($eventdata->picture)) {
                    $grpdata->picture = $eventdata->picture;
                //// *** NEED TO FIGURE OUT HOW TO GET A PICTURE COPIED *** ////
//                    $um = true;
                }

            /// This will generate another group create event. Will that be a problem?
                groups_update_group($grpdata, $um);
            }
        }
        return true;
    }
?>