<?php

require_login(0, false);

class forum_functions extends module_base {
    function forum_functions(&$mainobject) {
        $this->mainobject = $mainobject;
        // must be the same as th DB modulename
        $this->type = 'forum';
        $this->capability = 'mod/forum:viewhiddentimedposts';
        $this->levels = 3;
        $this->icon = 'mod/forum/icon.gif';
        $this->functions  = array(
            'forum' => 'submissions'
        );
    }


     /**
     * Gets all unmarked forum posts, but defines unmarked as not marked by the current account. If
     * another teacher has marked it, that is a problem.
     * @return <type> gets all unmarked forum discussions for all courses
     */
    function get_all_unmarked() {
        global $CFG, $USER;


        $sql = "SELECT p.id as postid, p.userid, d.id, f.id, f.name, f.course, c.id as cmid
                  FROM {$CFG->prefix}forum_posts p
             LEFT JOIN {$CFG->prefix}forum_ratings r
                    ON p.id = r.post
            INNER JOIN {$CFG->prefix}forum_discussions d
                    ON p.discussion = d.id
            INNER JOIN {$CFG->prefix}forum f
                    ON d.forum = f.id
            INNER JOIN {$CFG->prefix}course_modules c
                    ON f.id = c.instance
                 WHERE p.userid <> {$USER->id}
                   AND (((r.userid <> {$USER->id}) AND (r.userid NOT IN ({$this->mainobject->teachers})))
                    OR r.userid IS NULL)
                   AND c.module = {$this->mainobject->modulesettings['forum']->id}
                   AND c.visible = 1
                   AND f.course IN ({$this->mainobject->course_ids})
                   AND ((f.type <> 'eachuser') OR (f.type = 'eachuser' AND p.id = d.firstpost))
                   AND f.assessed > 0
              ORDER BY f.id";

        $this->all_submissions = get_records_sql($sql);
        return true;
    }


    function get_all_course_unmarked($courseid) {

        global $CFG, $USER;
        $unmarked = '';
        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);

        $sql = "SELECT p.id as post_id, p.userid, d.firstpost, f.course, f.type, f.id, f.name,
                       f.intro as description, c.id as cmid

                  FROM {$CFG->prefix}forum f
            INNER JOIN {$CFG->prefix}course_modules c
                    ON f.id = c.instance
            INNER JOIN {$CFG->prefix}forum_discussions d
                    ON d.forum = f.id
            INNER JOIN {$CFG->prefix}forum_posts p
                    ON p.discussion = d.id
             LEFT JOIN {$CFG->prefix}forum_ratings r
                    ON p.id = r.post

                 WHERE p.userid <> $USER->id
                   AND (((r.userid <> $USER->id) AND (r.userid NOT IN ({$this->mainobject->teachers})))
                       OR r.userid IS NULL)
                   AND ((f.type <> 'eachuser') OR (f.type = 'eachuser' AND p.id = d.firstpost))
                   AND c.module = {$this->mainobject->modulesettings['forum']->id}
                   AND c.visible = 1
                   AND (p.userid IN ({$student_sql}))
                   AND f.course = $courseid
                   AND f.assessed > 0
                   
              ORDER BY f.id";
                
        $unmarked = get_records_sql($sql);
        return $unmarked;
    }


    /**
     * function to make nodes for forum submissions
     */

    function submissions() {

        global $CFG, $USER;

        $discussions = '';
        $forum = get_record('forum', 'id', $this->mainobject->id);
        $courseid = $forum->course;
        $this->mainobject->get_course_students($courseid);

        $discussions = get_records('forum_discussions', 'forum', $this->mainobject->id);
        if (!$discussions) {
            return;
        }

        $student_sql = $this->get_role_users_sql($this->mainobject->courses[$courseid]->context);

        // get ready to fetch all the unrated posts
        $sql = "SELECT p.id, p.userid, p.created, p.message, d.id as discussionid
                  FROM {$CFG->prefix}forum_discussions d ";

        if ($forum->type == 'eachuser') {
            // add a bit to link to forum so we can check the type is correct
            $sql .= "INNER JOIN {$CFG->prefix}forum f ON d.forum = f.id "  ;
        }

        $sql .= "INNER JOIN {$CFG->prefix}forum_posts p
                         ON p.discussion = d.id
                  LEFT JOIN {$CFG->prefix}forum_ratings r
                         ON  p.id = r.post
                      WHERE d.forum = {$this->mainobject->id}
                        AND p.userid <> {$USER->id}
                        AND (p.userid IN ({$student_sql}))
                        AND (((r.userid <> {$USER->id})
                            AND (r.userid NOT IN ({$this->mainobject->teachers})))
                            OR r.userid IS NULL) ";

        if ($forum->type == 'eachuser') {
            // make sure that it is just the first posts that we get
            $sql .= "AND (f.type = 'eachuser' AND p.id = d.firstpost)";
        }

        $posts = get_records_sql($sql);

        if ($posts) {
            foreach ($posts as $key=>$post) {

                // sort for obvious exclusions
                if (!isset($post->userid)) {
                    unset($posts[$key]);
                    continue;
                }
                // Maybe this forum doesn't rate posts earlier than X time, so we check.
                if ($forum->assesstimestart != 0) {

                    if (!($post->created > $forum->assesstimestart))  {
                        unset($posts[$key]);
                        continue;
                    }
                }
                // Same for later cut-off time
                if ($forum->assesstimefinish != 0) {
                    // it also has a later limit, so check that too.
                    if (!($post->created < $forum->assesstimefinish)) {
                        unset($posts[$key]);
                        continue;
                    }
                }
            }

            // Check to see if group nodes need to be made instead of submissions

            if(!$this->mainobject->group) {
                $group_filter = $this->mainobject->try_to_make_group_nodes($posts, $this->type,
                                                                            $forum->id, $forum->course);
                if (!$group_filter) {
                    return;
                }
            }

            // Submissions nodes are needed, so make one per discussion
            $this->mainobject->output = '[{"type":"submissions"}';      // begin json object.

            // we may have excluded all of them now, so check again
            if (count($posts) > 0) {
                foreach ($discussions as $discussion) {

                    $firstpost = null;

                    // If we are under a group node, we want to ignore submissions from other groups
                    $groupnode     = $this->mainobject->group;
                    $memberofgroup = $this->mainobject->check_group_membership($groupnode, $discussion->userid);
                    if ($groupnode && !$memberofgroup) {
                        continue;
                    }

                    $count = 0;
                    // This variable will hold the id of the first post which is unrated, so it can 
                    // be used in the link to load the pop up with the discussion page at that position.
                    $sid = 0; 
                    // Start seconds at current time so we can compare with time created to find the
                    // oldest as we cycle through.
                    $time = time(); 

                    // If this forum is set to 'each student posts one discussion', we want to only
                    // grade the first one, which is the only one returned.
                    if ($forum->type == 'eachuser') {
                         $count = 1;
                         $firstpost = $post;
                    } else {
                        // Any other type of graded forum, we can grade any posts that are not yet
                        // graded. This means counting them first.

                        // Start seconds at current time so we can compare with time created to find
                        // the oldest as we cycle through.
                        $time = time(); 
                        $firsttime = '';

                        foreach ($posts as $key=>$post) {

                            if ($discussion->id == $post->discussionid) {
                                //post is relevant
                                $count++;

                                // link needs the id of the earliest post, so store time if this is
                                // the first post; check and modify for subsequent ones
                                if ($firstpost) {
                                    if ($post->created > $firstpost) {
                                        $firstpost = $post;
                                    }
                                } else {
                                    $firstpost = $post;
                                }
                                // store the time created for the tooltip if its the oldest post yet
                                // for this discussion
                                if ($firsttime) {
                                    if ($post->created < $time) {
                                        $time = $post->created;
                                    }
                                } else {
                                    $firsttime = $post->created;
                                }
                            }
                        } 
                    } 

                    // Add the node if there were any posts -  the node is the discussion with a
                    // count of the number of unrated posts.
                    if ($count > 0) {

                        // Make all the variables ready to put them together into the array.
                        $seconds = time() - $discussion->timemodified;

                        // We will show the student name as the node name as there is only one post
                        // that matters.
                        if ($forum->type == 'eachuser') { 
                            $name = $this->mainobject->get_fullname($firstpost->userid);

                        } else { 
                            // // the name will be the name of the discussion
                            $name = $discussion->name.' ('.$count.')';

                        }

                        $sum = strip_tags($firstpost->message);

                        $shortsum = substr($sum, 0, 100);
                        if (strlen($shortsum) < strlen($sum)) {
                            $shortsum .= "...";
                        }
                        $timesum = $this->mainobject->make_time_summary($seconds, true);
                        if (!isset($discuss)) {
                            $discuss = get_string('discussion', 'block_ajax_marking');
                        }
                        $summary = $discuss.": ".$shortsum."<br />".$timesum;

                        $node = $this->mainobject->make_submission_node(
                                $name,
                                $firstpost->id,
                                $discussion->id,
                                $summary,
                                'forum_final',
                                $seconds,
                                $time,
                                $count);
                        $this->mainobject->output .= $node;
                    }
                }
            }
            $this->mainobject->output .= "]"; 
        }
    } 



    /**
     * gets all of the forums for all courses, ready for the config tree.
     * @global <type> $CFG
     * @return <type>
     */
    function get_all_gradable_items() {
        global $CFG;
        $sql = "SELECT f.id, f.course, f.intro as summary, f.name, f.type, c.id as cmid
                  FROM {$CFG->prefix}forum f
            INNER JOIN {$CFG->prefix}course_modules c
                    ON f.id = c.instance
                 WHERE c.module = {$this->mainobject->modulesettings['forum']->id}
                   AND c.visible = 1
                   AND f.course IN ({$this->mainobject->course_ids})
                   AND f.assessed > 0
              ORDER BY f.id";
        $forums = get_records_sql($sql);
        $this->assessments = $forums;
    }

    function make_html_link($item) {
        global $CFG;
        $address = $CFG->wwwroot.'/mod/forum/view.php?id='.$item->cmid;
        return $address;
    }


}