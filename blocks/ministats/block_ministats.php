<?php  //$Id: block_ministats.php

define("BLOCK_MINISTAT_TIMEOUT_DEFAULT", 10);
define("BLOCK_MINISTAT_ADMINS_ONLY_DEFAULT", 0);
define("BLOCK_MINISTAT_SHOW_TEACHERS_DEFAULT", 1);
define("BLOCK_MINISTAT_SHOW_ACTIVE_USERS_DEFAULT", 1);
define("BLOCK_MINISTAT_ACTIVE_USERS_DAYS_DEFAULT", 30);

class block_ministats extends block_base {
	function init() {
		global $CFG;

		$this->title = get_string('blockname', 'block_ministats');
		$this->version = 2007071600;

		// Set default config (if not saved)
		if ( !isset($CFG->block_ministats_timeout) )
			$CFG->block_ministats_timeout = BLOCK_MINISTAT_TIMEOUT_DEFAULT; // default: 10 min

		if ( !isset($CFG->block_ministats_admins_only) )
			$CFG->block_ministats_admins_only = BLOCK_MINISTAT_ADMINS_ONLY_DEFAULT;	//default: visible to all

		if ( !isset($CFG->block_ministats_show_teachers) )
			$CFG->block_ministats_show_teachers = BLOCK_MINISTAT_SHOW_TEACHERS_DEFAULT;

		if ( !isset($CFG->block_ministats_active_users_days))
			$CFG->block_ministats_active_users_days = BLOCK_MINISTAT_ACTIVE_USERS_DAYS_DEFAULT;

		if ( !isset($CFG->block_ministats_show_acrive_users))
			$CFG->block_ministats_show_acrive_users = BLOCK_MINISTAT_SHOW_ACTIVE_USERS_DEFAULT;
	}

	/**
	 * Block is globally configurable
	 */
	function has_config() {
		return true;
	}

	function applicable_formats() {
		return array('site' => true);
	}

	/**
	 * Returns active (!deleted, confirmed) Users count
	 */
	function _userCount() {
		return count_records('user','deleted', 0, 'confirmed', 1);
	}

	/**
	 * Returns Courses count
	 */
	function _courseCount() {
		return count_records('course', 'visible', 1);
	}

	/**
	 * Returns count of users with lastaccess in the last month
	 */
	function _activeUsers() {
		global $CFG;

		$activeUsersStartTime = time() - ( $CFG->block_ministats_active_users_days * 3600 * 24); // timestamp for user activity start time
		return count_records_select('user', 'lastaccess >= '.$activeUsersStartTime );
	}

	/**
	 * Counts distinct teachers
	 */
	function _teacherCount() {
		global $CFG;
		// This works on Moodle 1.6
//		$sql = 'select count(distinct userid) from '.$CFG->prefix.'user_teachers';

		//  The following works on Moodle 1.8 and later
    	$sql = "SELECT COUNT(DISTINCT u.id)
            FROM {$CFG->prefix}role_capabilities rc,
                 {$CFG->prefix}role_assignments ra,
                 {$CFG->prefix}user u
            WHERE (rc.capability = 'moodle/course:update'
					OR rc.capability='moodle/site:doanything'
					OR rc.capability='moodle/legacy:teacher'
					OR rc.capability='moodle/legacy:editingteacher' )
                   AND rc.roleid = ra.roleid
                   AND u.id = ra.userid";

  		return count_records_sql($sql);
   }

	/**
	 * Returns an array containing all stats
	 * and current timestamp
	 */
	function _calculateStats() {
		global $CFG;

		$stats = array();
		$stats['timestamp'] = time();
		$stats['userCount'] = $this->_userCount();
		$stats['courseCount'] = $this->_courseCount();
		if ( $CFG->block_ministats_show_acrive_users )
			$stats['activeUserCount'] = $this->_activeUsers();
		if ( $CFG->block_ministats_show_teachers )
			$stats['teacherCount'] = $this->_teacherCount();
		return $stats;
	}

	/**
	 * Get stats from $_SERVER (if available)
	 * If stats are out of date (300sec), recalculate
	 */
	function _getStats() {
		global $CFG;

		if ( isset($CFG->block_ministats_timeout) )
			$timeoutSecs = $CFG->block_ministats_timeout * 60;
		else
			$timeoutSecs = BLOCK_MINISTAT_ADMINS_ONLY_DEFAULT * 60;

		if ( array_key_exists('ministats_cache', $_SESSION ) ) {
			$stats = $_SESSION['ministats_cache'];
			if ( (time() - $stats['timestamp']) > $timeoutSecs ) {
				// Recalculate and store in $_SERVER
				$stats = $this->_calculateStats();
				$_SESSION['ministats_cache'] = $stats;
			}
		} else {
			$stats = $this->_calculateStats();
			$_SESSION['ministats_cache'] = $stats;
		}
		return $stats;
	}


	/**
	 * Check if current user has permission
	 */
	function _check_permission() {
		global $USER, $CFG;
		// Check if Block is visible only to Administrators
		if ( $CFG->block_ministats_admins_only == 1 && ( !isset($USER->id) || !isadmin($USER->id)) )
		  return false;
		 else
		   return true;
	}


	function get_content() {
		global $COURSE, $USER, $CFG;

		$this->content = new stdClass;
		$this->content->text = '';

		if (!$this->_check_permission()) {
			return $this->content;
		}

		// Calculate stats
		$stats = $this->_getStats();

		// Output stats
		$this->content->text .= get_string('availablecourses').': '.$stats['courseCount'].'<br />';
		$this->content->text .= get_string('users').': '.$stats['userCount'].'<br />';

		if ( $CFG->block_ministats_show_acrive_users && isset($stats['activeUserCount']) )
			$this->content->text .= get_string('activeusers').': '.$stats['activeUserCount'].' <br/>('.get_string('last_days', 'block_ministats', $CFG->block_ministats_active_users_days).')<br />';

		if ( $CFG->block_ministats_show_teachers && isset($stats['teacherCount']) )
			$this->content->text .= get_string('teachers').': '.$stats['teacherCount'].'<br />';


		$this->content->footer = get_string('updated_on', 'block_ministats').':&nbsp;'.userdate( $stats['timestamp'] );

		return $this->content;
	}

}

?>