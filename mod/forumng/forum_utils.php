<?php
/**
 * Class that holds utility functions used by forum.
 */
class forum_utils {

    // Transactions
    ///////////////

    private static $transactions = 0;

    /**
     * Begins a (possibly nested) transaction. The forum uses transactions
     * where possible and where supported by the underlying Moodle function,
     * which does not cope with nesting.
     */
    static function start_transaction() {
        if(self::$transactions==0) {
            begin_sql();
        }
        self::$transactions++;
    }

    /**
     * Ends a (possibly nested) transaction.
     */
    static function finish_transaction() {
        self::$transactions--;
        if(self::$transactions==0) {
            commit_sql();
        }
    }

    // Exception-safe DML wrapper functions
    ///////////////////////////////////////

    /**
     * Temporary function until Moodle 2: does update_record, but throws
     * exception if it fails.
     * @param $table Table name
     * @param $record Record
     * @throws forum_exception If update fails
     */
    static function update_record($table, $record) {
        if(!update_record($table, $record)) {
            throw new forum_exception("Failed to update record in $table");
        }
    }

    /**
     * Temporary function until Moodle 2: does insert_record, but throws
     * exception if it fails.
     * @param $table Table name
     * @param $record Record
     * @throws forum_exception If insert fails
     */
    static function insert_record($table, $record) {
        $id = insert_record($table, $record);
        if (!$id) {
            throw new forum_exception("Failed to insert record in $table");
        }
        return $id;
    }

    static function get_record($table, $field1, $value1, $field2='', $value2='', $field3='', $value3='', $fields='*') {
        $result = get_record($table, $field1, $value1, $field2, $value2, $field3, $value3, $fields);
        if (!$result) {
            throw new forum_exception("Failed to get record in $table");
        }
        return $result;
    }

    static function get_records($table, $field='', $value='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
        $result = get_records($table, $field, $value, $sort, $fields, $limitfrom, $limitnum);
        return $result ? $result : array();
    }

    static function get_record_sql($sql, $expectmultiple=false, $nolimit=false) {
        $result = get_record_sql($sql, $expectmultiple=false, $nolimit=false);
        if (!$result) {
            throw new forum_exception("Failed to get record via SQL");
        }
        return $result;
    }

    static function get_records_sql($sql, $limitfrom='', $limitnum='') {
        $rs = self::get_recordset_sql($sql, $limitfrom, $limitnum);
        $result = recordset_to_array($rs);
        return $result ? $result : array();
    }

    static function count_records_sql($sql) {
        $rs = self::get_recordset_sql($sql);
        $result = rs_fetch_next_record($rs);
        if (!$result) {
            throw new forum_exception("No results from count query");
        }
        if (rs_fetch_next_record($rs)) {
            throw new forum_exception("Too many results from count query");
        }
        rs_close($rs);
        $junk = (array)$result;
        return reset($junk);
    }

    static function get_field($table, $return, $field1, $value1, $field2='', $value2='', $field3='', $value3='') {
        $result = get_field($table, $return, $field1, $value1, $field2, $value2, $field3, $value3);
        if ($result === false) {
            throw new forum_exception("Failed to get field $return in $table");
        }
        return $result;
    }

    static function delete_records($table, $field1='', $value1='', $field2='', $value2='', $field3='', $value3='') {
        $ok = delete_records($table, $field1, $value1, $field2, $value2, $field3, $value3);
        if (!$ok) {
            throw new forum_exception("Failed to delete records from table $table");
        }
    }

    static function get_recordset_sql($sql, $limitfrom=null, $limitnum=null) {
        $rs = get_recordset_sql($sql, $limitfrom, $limitnum);
        if (!$rs) {
            throw new forum_exception("Failed to get SQL recordset for $sql");
        }
        return $rs;
    }

    static function get_recordset($table, $field='', $value='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
        $rs = get_recordset($table, $field, $value, $sort, $fields, $limitfrom, $limitnum);
        if (!$rs) {
            throw new forum_exception("Failed to get SQL recordset for $sql");
        }
        return $rs;
    }

    static function execute_sql($sql) {
        $ok = execute_sql($sql, false);
        if (!$ok) {
            throw new forum_exception("Failed to execute SQL $sql");
        }
    }

    // Exception-safe IO
    ////////////////////

    /**
     * Deletes a file.
     * @param string $file File to delete
     * @throws forum_exception If the delete fails
     */
    static function unlink($file) {
        if (!unlink($file)) {
            throw new forum_exception("Failed to delete $file");
        }
    }

    /**
     * Renames a file, without needing to check the return value.
     * @param $oldfile Old name
     * @param $newfile New name
     * @throws forum_exception If the rename fails
     */
    static function rename($oldfile, $newfile) {
        if (!rename($oldfile, $newfile)) {
            throw new forum_exception("Failed to rename $oldfile to $newfile");
        }
    }

    /**
     * Deletes a folder, without needing to check the return value. (Note:
     * This is not a recursive delete. You need to delete files first.)
     * @param string $folder Path of folder
     * @throws forum_exception If the delete fails
     */
    static function rmdir($folder) {
        if (!rmdir($folder)) {
            throw new forum_exception("Failed to delete folder $folder");
        }
    }

    /**
     * Creates a folder, without needing to check the return value. (Note:
     * This is not a recursive create. You need to create the parent first.)
     * @param string $folder Path of folder
     * @throws forum_exception If the create fails
     */
    static function mkdir($folder) {
        if (!mkdir($folder)) {
            throw new forum_exception("Failed to make folder $folder");
        }
    }

    /**
     * Copies a file, without needing to check the return value.
     * @param $oldfile Old name
     * @param $newfile New name
     * @throws forum_exception If the copy fails
     */
    static function copy($oldfile, $newfile) {
        if (!copy($oldfile, $newfile)) {
            throw new forum_exception("Failed to copy $oldfile to $newfile");
        }
    }

    /**
     * Opens a directory handle. The directory must exist or this function
     * will throw an exception.
     * @param string $folder Folder to open
     * @return int Handle
     * @throws forum_exception If the open fails
     */
    static function opendir($folder) {
        $handle = @opendir($folder);
        if (!$handle) {
            throw new forum_exception(
              "Failed to open folder: $folder");
        }
        return $handle;
    }

    // SQL field selections
    ///////////////////////

    /**
     * Makes a list of fields with alias in front.
     * @param $fields Field
     * @param $alias Table alias (also used as field prefix)
     * @return SQL SELECT list
     */
    private static function select_fields($fields, $alias) {
        $result = '';
        foreach ($fields as $field) {
            if ($result) {
                $result .= ',';
            }
            $result .= $alias . '.' . $field . ' as ' . $alias . '_' . $field;
        }
        return $result;
    }

    /**
     * @param bool $includemailfields If true, includes email fields (loads)
     * @return array List of all field names in mdl_user to include
     */
    static function get_username_fields($includemailfields=false) {
        return $includemailfields
            ? array('id', 'username', 'firstname', 'lastname', 'picture', 'url',
                'imagealt', 'email', 'maildisplay', 'mailformat', 'maildigest',
                'emailstop', 'deleted', 'auth', 'timezone', 'lang', 'idnumber')
            :  array('id', 'username', 'firstname', 'lastname', 'picture', 'url',
                'imagealt', 'idnumber');
    }

    /**
     * Used when selecting users inside other SQL statements.
     * Returns list of fields suitable to go within the SQL SELECT block. For
     * example, if the alias is 'fu', one field will be fu.username AS fu_username.
     * Note, does not end in a comma.
     * @param string $alias Alias of table to extract
     * @param bool $includemailfields If true, includes additional fields
     *   needed for sending emails
     * @return string SQL select fields (no comma at start or end)
     */
    static function select_username_fields($alias, $includemailfields = false) {
        return forum_utils::select_fields(
            self::get_username_fields($includemailfields), $alias);
    }

    static function select_course_module_fields($alias) {
        $fields = array('id', 'course', 'module', 'instance', 'section',
            'added', 'score', 'indent', 'visible', 'visibleold', 'groupmode',
            'groupingid', 'idnumber', 'groupmembersonly');

        if(class_exists('ouflags')) {
            $fields += array('parentcmid', 'completion',
              'completiongradeitemnumber', 'completionview',
              'completionexpected', 'availablefrom', 'availableuntil',
              'showavailability', 'parentpagename', 'showto', 'stealth');
        }

        return forum_utils::select_fields($fields, $alias);
    }

    static function select_course_fields($alias) {
        return forum_utils::select_fields(array('id', 'shortname', 'fullname'),
            $alias);
    }

    static function select_context_fields($alias) {
        return forum_utils::select_fields(array('id', 'contextlevel', 'instanceid',
            'path', 'depth'), $alias);
    }

    /**
     * Used when selecting forums inside other SQL statements.
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    static function select_forum_fields($alias) {
        return forum_utils::select_fields(array('id', 'course', 'name', 'type',
            'intro', 'ratingscale', 'ratingfrom', 'ratinguntil', 'grading',
            'attachmentmaxbytes', 'reportingemail', 'subscription', 'feedtype', 'feeditems',
            'maxpostsperiod', 'maxpostsblock', 'postingfrom', 'postinguntil',
            'typedata', 'magicnumber', 'originalcmid', 'shared'), $alias);
    }

    /**
     * Used when selecting discussions inside other SQL statements.
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    static function select_discussion_fields($alias) {
        return forum_utils::select_fields(array('id', 'forumid', 'groupid', 'postid',
            'lastpostid', 'timestart', 'timeend', 'deleted', 'locked',
            'sticky'), $alias);
    }

    /**
     * Used when selecting posts inside other SQL statements.
     * @param string $alias Alias of table to extract
     * @return string SQL select fields (no comma at start or end)
     */
    static function select_post_fields($alias) {
        return forum_utils::select_fields(array('id', 'discussionid', 'parentpostid',
            'userid', 'created', 'modified', 'deleted', 'important', 'mailstate',
            'oldversion', 'edituserid', 'subject', 'message', 'format',
            'attachments'), $alias);
    }

    // SQL generic helpers
    //////////////////////

    /**
     * Utility method, a variety of which is available as standard in Moodle 2.
     * Returns either =1 or IN(1,2,3,4) depending on size of input array.
     * @param array $array Array (must be integers!)
     * @return string Resulting SQL
     */
    static function in_or_equals($array) {
        switch (count($array)) {
        case 0:
            return '= NULL'; // This is always false, even if it is null
        case 1:
            return '= ' . reset($array);
        default:
            return 'IN (' . implode(',', $array) . ')';
        }
    }
    
    /**
     * Safe version of explode function. Always returns an array. Ignores blank
     * elements. So the result of calling this on '/3//4/5' will be array(3,4,5).
     * @param string $separator Separator eg. ","
     * @param string $string String to split
     * @return array String split into parts
     */
    static function safe_explode($separator, $string) {
        $results = explode($separator, $string);
        $answer = array();
        if ($results) {
            foreach($results as $thing) {
                if ($thing!=='') {
                    $answer[] = $thing;
                }
            }
        }
        return $answer;
    }

    // SQL object extraction
    ////////////////////////

    /**
     * Loops through all the fields of an object, removing those which begin
     * with a given prefix, and setting them as fields of a new object.
     * @param &$object object Object
     * @param $prefix string Prefix e.g. 'prefix_'
     * @return object Object containing all the prefixed fields (without prefix)
     */
    static function extract_subobject(&$object, $prefix) {
        $result = array();
        foreach((array)$object as $key=>$value) {
            if(strpos($key, $prefix)===0) {
                $result[substr($key, strlen($prefix))] = $value;
                unset($object->{$key});
            }
        }
        return (object)$result;
    }

    /**
     * Copies fields beginning with the specified prefix from one object to
     * another, optionally changing the prefix.
     * @param $target Target object
     * @param $source Source object
     * @param $prefix Prefix for fields to copy
     * @param $newprefix New prefix (null = same prefix)
     */
    static function copy_subobject(&$target, $source, $prefix, $newprefix=null) {
        if ($newprefix === null) {
            $newprefix = $prefix;
        }
        foreach ($source as $key=>$value) {
            if (strpos($key, $prefix)===0) {
                $newkey = $newprefix . substr($key, strlen($prefix));
                $target->{$newkey} = $value;
            }
        }
    }

    // Moodle generic helpers
    /////////////////////////

    /**
     * @param int $userid User ID or 0 for default
     * @return Genuine (non-zero) user ID
     */
    static function get_real_userid($userid=0) {
        global $USER;
        $userid = $userid==0 ? $USER->id : $userid;
        if (!$userid) {
            throw new forum_exception('Cannot determine user ID');
        }
        return $userid;
    }

    /**
     * @param int $userid User ID or 0 for default
     * @return User object
     */
    static function get_user($userid=0) {
        global $USER;
        if ($userid && (empty($USER->id) || $USER->id != $userid)) {
            $user = forum_utils::get_record('id', $userid);
        } else {
            $user = $USER;
        }
    }

    static private $scales = array();

    /**
     * Wrapper for Moodle function that caches result, so can be called
     * without worry of a performance impact.
     * @param int $gradingtype Grading type value
     * @return array Array from value=>name
     */
    static function make_grades_menu($gradingtype) {
        if (!array_key_exists($gradingtype, self::$scales)) {
            self::$scales[$gradingtype] = make_grades_menu($gradingtype);
        }
        return self::$scales[$gradingtype];
    }

    // UI
    /////

    /**
     * We disable JavaScript for 'bad' browsers.
     * @return string Name of bad browser, or false if using a good browser
     */
    public static function is_bad_browser() {
        if (!array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
            // Don't know what browser it is, let's assume it's a good one
            return false;
        }
        $agent = $_SERVER['HTTP_USER_AGENT'];

        if (strpos($agent, 'MSIE 6')!==false) {
            return 'Internet Explorer 6';
        }
        return false;
    }

    /**
     * Wraps nice way to display reasonable date format in Moodle for use
     * in all forum locations.
     * @param int $date Date (seconds since epoch)
     * @return string Date as string
     */
    public static function display_date($date) {
        // Use OU custom 'nice date' function if available
        if (function_exists('specially_shrunken_date')) {
            return specially_shrunken_date($date, false, true);
        } else {
            return userdate($date,
                get_string('strftimedatetimeshort', 'langconfig'));
        }
    }

    /**
     * Displays an exception in HTML, and exists. Includes the exception
     * trace in an HTML comment, and a readable error string along with the
     * exception message.
     * @param exception $e Exception
     */
    public static function handle_exception($e) {
        // Display actual trace in HTML comment. There shouldn't be any
        // security-sensitive information in the trace, so this can be
        // displayed even on live server (I hope).
        if (debugging('', DEBUG_DEVELOPER)) {
            global $CFG;
            print "<pre class='forumng-stacktrace'>";
            print htmlspecialchars(str_replace($CFG->dirroot, '', $e->getTraceAsString()));
            print "</pre>";
        } else {
            print "<!--\n";
            print $e->getTraceAsString(); // Not escaped, I think this is correct...
            print "\n-->";
        }

        // Make a short version of the trace string for log
        $minitrace = self::get_minitrace_part($e->getFile(), $e->getLine());
        foreach($e->getTrace() as $entry) {
            $minitrace .= ' ' .
                self::get_minitrace_part($entry['file'], $entry['line']);
        }
        $minitrace = shorten_text($minitrace, 120, true);
        $message = shorten_text($e->getMessage(), 120, true);
        global $FULLME, $USER, $CFG;
        $url = str_replace($CFG->wwwroot . '/mod/forumng/', '', $FULLME);
        add_to_log(0, 'forumng', 'error', $url,
            "$message / $minitrace", 0, $USER->id);

        // Error to user with just the message
        print_error('error_exception', 'forumng', '', $e->getMessage());
    }

    private static function get_minitrace_part($file, $line) {
        global $CFG;
        // Remove dirroot
        $file = str_replace($CFG->dirroot, '', $file);
        // Remove mod/forum
        $file = str_replace('/mod/forumng/', '', $file);
        // Return file:line
        return "$file:$line";
    }

    /**
     * Prints a warning when an exception occurs during backup/restore.
     * @param Exception $e Exception
     * @param string $type Type of process (backup or restore)
     */
    public static function handle_backup_exception($e, $type='backup') {
        if (debugging()) {
            print '<pre>';
            print $e->getMessage() . ' (' . $e->getCode() . ')' . "\n";
            print $e->getFile() . ':' . $e->getLine() . "\n";
            print $e->getTraceAsString();
            print '</pre>';
        } else {
            print '<div><strong>Error</strong>: '.
                htmlspecialchars($e->getMessage()) . ' (' . $e->getCode() . ')</div>';
        }
        print "<div><strong>This $type has failed</strong> (even though it " .
            "may say otherwise later). Resolve this problem before " .
            "continuing.</div>";
    }

    /**
     * Obtains a list of forums on the given course which can be converted.
     * The requirements for this are that they must have a supported forum
     * type and there must not be an existing ForumNG with the same name.
     * @param object $course
     * @return array Array of id=>name of convertable forums
     */
    public static function get_convertible_forums($course) {
        global $CFG;
        return forum_utils::get_records_sql("
SELECT cm.id, f.name 
FROM
    {$CFG->prefix}forum f
    INNER JOIN {$CFG->prefix}course_modules cm ON cm.instance=f.id 
      AND cm.module = (SELECT id FROM {$CFG->prefix}modules WHERE name='forum')
    LEFT JOIN {$CFG->prefix}forumng fng ON fng.name=f.name AND fng.course=f.course
WHERE
    cm.course={$course->id} AND f.course={$course->id} 
    AND f.type='general'
    AND fng.id IS NULL");
    }

    /**
     * Executes a database update in such a way that it will work in MySQL,
     * when the update uses a subquery that refers to the table being updated.
     * @param string $update Update query with the special string %'IN'% at the
     *   point where the IN clause should go, i.e. replacing 'IN (SELECT id ...)' 
     * @param string $inids Query that selects a column (which must be named
     *   id), i.e. 'SELECT id ...'
     */
    public static function update_with_subquery_grrr_mysql($update, $inids) {
        global $CFG;
        if (preg_match('~^mysql~', $CFG->dbtype)) {
            // MySQL is a PoS so the update can't directly run (you can't update
            // a table based on a subquery that refers to the table). Instead,
            // we do the same thing but with a separate update using an IN clause.
            // This might theoretically run into problems if you had a really huge
            // set of forums with frequent posts (so that the IN size exceeds
            // MySQL query limit) however the limits appear to be generous enough
            // that this is unlikely.
            $ids = array();
            $rs = forum_utils::get_recordset_sql($inids);
            while($rec = rs_fetch_next_record($rs)) {
                $ids[] = $rec->id;
            }
            rs_close($rs);
            if (count($ids) > 0) {
                $update = str_replace("%'IN'%",
                    forum_utils::in_or_equals($ids), $update);
                forum_utils::execute_sql($update);
            }
        } else {
            // With a decent database we can do the update and query in one,
            // avoiding the need to transfer an ID list around.
            forum_utils::execute_sql(
                str_replace("%'IN'%", "IN ($inids)", $update));
        }
    }
}
?>