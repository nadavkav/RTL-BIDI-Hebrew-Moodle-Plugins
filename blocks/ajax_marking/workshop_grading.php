<?php

require_login(0, false);

class workshop_functions extends module_base {

    function workshop_functions(&$mainobject) {
        $this->mainobject = $mainobject;
        // must be the same as th DB modulename
        $this->type = 'workshop';
        $this->capability = 'mod/workshop:manage';
        $this->levels = 3;
        $this->icon = 'mod/workshop/icon.gif';
        $this->functions  = array(
            'workshop' => 'submissions'
        );
    }


    /**
     * Function to return all unmarked workshop submissions for all courses.
     * Called by courses()
     */
    function get_all_unmarked() {
        global $CFG, $USER;
        $sql = "SELECT s.id as subid, s.userid, w.id, w.name, w.course, w.description, c.id as cmid
                  FROM ({$CFG->prefix}workshop w
            INNER JOIN {$CFG->prefix}course_modules c
                    ON w.id = c.instance)
             LEFT JOIN {$CFG->prefix}workshop_submissions s
                    ON s.workshopid = w.id
             LEFT JOIN {$CFG->prefix}workshop_assessments a
                    ON (s.id = a.submissionid)
                 WHERE (a.userid != {$USER->id}
                    OR (a.userid = {$USER->id}
                   AND a.grade = -1))
                   AND c.module = {$this->mainobject->modulesettings['workshop']->id}
                   AND w.course IN ({$this->mainobject->course_ids})
                   AND c.visible = 1
              ORDER BY w.id";
   
        $this->all_submissions = get_records_sql($sql);
        return true;
    }

    function get_all_course_unmarked($courseid) {

        global $CFG, $USER;

        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);

        $sql = "SELECT s.id as submissionid, s.userid, w.id, w.name, w.course,
                       w.description, c.id as cmid
                  FROM ({$CFG->prefix}workshop w
            INNER JOIN {$CFG->prefix}course_modules c
                    ON w.id = c.instance)
            
             LEFT JOIN {$CFG->prefix}workshop_submissions s
                    ON s.workshopid = w.id
             LEFT JOIN {$CFG->prefix}workshop_assessments a
                    ON (s.id = a.submissionid)
          
                 WHERE (a.userid != {$USER->id}
                    OR (a.userid = {$USER->id}
                   AND a.grade = -1))
                   AND c.module = {$this->mainobject->modulesettings['workshop']->id}
                   AND c.visible = 1
                   AND (s.userid IN ({$student_sql}))
                   AND w.course = {$courseid}
              ORDER BY w.id";

        $unmarked = get_records_sql($sql);
        return $unmarked;
    }
    
    function submissions() {

        global $CFG, $USER;

        $workshop = get_record('workshop', 'id', $this->mainobject->id);
        $courseid = $workshop->course;
       
        $this->mainobject->get_course_students($workshop->course);
        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);
        
        $now = time();
        // fetch workshop submissions for this workshop where there is no corresponding record of
        // a teacher assessment
        $sql = "SELECT s.id, s.userid, s.title, s.timecreated, s.workshopid
                  FROM {$CFG->prefix}workshop_submissions s
             LEFT JOIN {$CFG->prefix}workshop_assessments a
                    ON (s.id = a.submissionid)
            INNER JOIN {$CFG->prefix}workshop w
                    ON s.workshopid = w.id
            INNER JOIN ({$student_sql}) as stsql
                    ON s.userid = stsql.id
                 WHERE (a.userid != {$USER->id}
                    OR (a.userid = {$USER->id}
                   AND a.grade = -1))
                   AND (s.userid IN ({$student_sql}))
                   AND s.workshopid = {$this->mainobject->id}
                   AND w.assessmentstart < {$now}
              ORDER BY s.timecreated ASC";

        $submissions = get_records_sql($sql);

        if ($submissions) {

            // if this is set to display by group, we divert the data to the groups() function
            if(!$this->mainobject->group) {
                $group_filter = $this->mainobject->try_to_make_group_nodes($submissions, "workshop", $workshop->id, $workshop->course);
                if (!$group_filter) {
                    return;
                }
            }
            // otherwise, submissionids have come back, so it must be set to display all.

            // begin json object
            $this->mainobject->output = '[{"type":"submissions"}';

            foreach ($submissions as $submission) {

                if (!isset($submission->userid)) {
                    continue;
                }
                // if we are displaying for a single group node, ignore those students in other groups
                $groupnode    = $this->mainobject->group;
                $inrightgroup = $this->mainobject->check_group_membership($this->mainobject->group, $submission->userid);
                if ($groupnode && !$inrightgroup) {
                    continue;
                }

                $name = $this->mainobject->get_fullname($submission->userid);

                $sid = $submission->id;

                // sort out the time stuff
                $now = time();
                $seconds = ($now - $submission->timecreated);
                $summary = $this->mainobject->make_time_summary($seconds);
                $this->mainobject->output .= $this->mainobject->make_submission_node($name, $sid, $this->mainobject->id, 
                                                                                     $summary, 'workshop_final', $seconds,
                                                                                     $submission->timecreated);

            }
            $this->mainobject->output .= "]";
        }
    }

    /**
     * gets all workshops for the config tree
     */
    function get_all_gradable_items() {

        global $CFG;

        $sql = "SELECT w.id, w.course, w.name, w.description as summary, c.id as cmid
                  FROM {$CFG->prefix}workshop w
            INNER JOIN {$CFG->prefix}course_modules c
                    ON w.id = c.instance
                 WHERE c.module = {$this->mainobject->modulesettings['workshop']->id}
                   AND c.visible = 1
                   AND w.course IN ({$this->mainobject->course_ids})
              ORDER BY w.id";

        $workshops = get_records_sql($sql);
        $this->assessments = $workshops;
    }

    function make_html_link($item) {

        global $CFG;
        $address = $CFG->wwwroot.'/mod/workshop/view.php?id='.$item->cmid;
        return $address;
    }

}