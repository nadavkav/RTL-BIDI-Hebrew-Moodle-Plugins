<?php

require_login(0, false);

class journal_functions extends module_base {

    function journal_functions(&$reference) {
        $this->mainobject = $reference;
        // must be the same as the DB modulename
        $this->type = 'journal';
        // doesn't seem to be a journal capability :s
        $this->capability = 'mod/assignment:grade';
        // How many nodes in total when fully expanded (no groups)?
        $this->levels = 2;
        // function to trigger for the third level nodes (might be different if there are four
        //$this->level2_return_function = 'journal_submissions';
        $this->icon = 'mod/journal/icon.gif';
        $this->functions  = array(
            'journal' => 'submissions'
        );
       
    }


     /**
      * gets all unmarked journal submissions from all courses ready for counting
      * called from get_main_level_data
      */
    function get_all_unmarked() {

        global $CFG;

        $sql = "SELECT je.id as entryid, je.userid, j.name, j.course, j.id, c.id as cmid
                  FROM {$CFG->prefix}journal_entries je
            INNER JOIN {$CFG->prefix}journal j
                    ON je.journal = j.id
            INNER JOIN {$CFG->prefix}course_modules c
                    ON j.id = c.instance
                 WHERE c.module = {$this->mainobject->modulesettings['journal']->id}
                   AND j.course IN ({$this->mainobject->course_ids})
                   AND c.visible = 1
                   AND j.assessed <> 0
                   AND je.modified > je.timemarked";

        $this->all_submissions = get_records_sql($sql);
        return true;
    }

    function get_all_course_unmarked($courseid) {

        global $CFG;

        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);

        $sql = "SELECT je.id as entryid, je.userid, j.intro as description, j.course, j.name,
                       j.timemodified, j.id, c.id as cmid
                  FROM {$CFG->prefix}journal_entries je
            INNER JOIN {$CFG->prefix}journal j
                    ON je.journal = j.id
            INNER JOIN {$CFG->prefix}course_modules c
                    ON j.id = c.instance
          
                 WHERE c.module = {$this->mainobject->modulesettings['journal']->id}
                   AND c.visible = 1
                   AND (je.userid IN ({$student_sql}))
                   AND j.assessed <> 0
                   AND je.modified > je.timemarked
                   AND j.course = {$courseid}";

        $unmarked = get_records_sql($sql);
        return $unmarked;
    }

    /**
     * gets all journals for all courses ready for the config tree
     */
    function get_all_gradable_items() {

        global $CFG;

        $sql = "SELECT j.id, j.intro as summary, j.name, j.course, c.id as cmid
                  FROM {$CFG->prefix}journal j
            INNER JOIN {$CFG->prefix}course_modules c
                    ON j.id = c.instance
                 WHERE c.module = {$this->mainobject->modulesettings['journal']->id}
                   AND c.visible = 1
                   AND j.assessed <> 0
                   AND j.course IN ({$this->mainobject->course_ids})";

        $journals = get_records_sql($sql);
        $this->assessments = $journals;
    }

    /**
     * this will never actually lead to submissions, but will only be called if there are group
     * nodes to show.
     */
    function submissions() {

        global $USER, $CFG;
        // need to get course id in order to retrieve students
        $journal = get_record('journal', 'id', $this->mainobject->id);
        $courseid = $journal->course;

        $coursemodule = get_record('course_modules', 'module', '1', 'instance', $journal->id) ;
        $modulecontext = get_context_instance(CONTEXT_MODULE, $coursemodule->id);
        if (!has_capability($this->capability, $modulecontext, $USER->id)) {
            return;
        }

        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);

        $this->mainobject->get_course_students($courseid);

        $sql = "SELECT je.id as entryid, je.userid, j.intro as description, j.name, j.timemodified,
                       j.id, c.id as cmid
                  FROM {$CFG->prefix}journal_entries je
            INNER JOIN {$CFG->prefix}journal j
                    ON je.journal = j.id
            INNER JOIN {$CFG->prefix}course_modules c
                    ON j.id = c.instance
      
                 WHERE c.module = {$this->mainobject->modulesettings['journal']->id}
                   AND c.visible = 1
                   AND (je.userid IN ({$student_sql}))
                   AND j.assessed <> 0
                   AND je.modified > je.timemarked
                   AND j.id = {$journal->id}";

        $submissions = get_records_sql($sql);

        // TODO: does this work with 'journal' rather than 'journal_final'?

        // This function does not need any checks for group status as it will only be called if groups are set.
        $group_filter = $this->mainobject->try_to_make_group_nodes($submissions, 'journal', $journal->id, $journal->course);
           
        // group nodes have now been printed by the groups function
        return;
    }

    function make_html_link($item) {

        global $CFG;
        $address = $CFG->wwwroot.'/mod/journal/report.php?id='.$item->cmid;
        return $address;
    }

}