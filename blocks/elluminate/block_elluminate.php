<?php // $Id: block_elluminate.php,v 1.1.2.2 2009/03/18 16:45:57 mchurch Exp $

/**
 * Elluminate Live! block.
 *
 * Allows students to manage their user information on the Elluminate Live!
 * server from Moodle and admins/teachers to add students and other users
 * to a remote Elluminate Live! server.
 *
 * @version $Id: block_elluminate.php,v 1.1.2.2 2009/03/18 16:45:57 mchurch Exp $
 * @author Justin Filip <jfilip@oktech.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */


class block_elluminate extends block_list {

	function init() {
		$this->title   = get_string('elluminate', 'block_elluminate');
		$this->version = 2009091101;
	}

	function get_content() {
		global $CFG, $USER;
		

		require_once($CFG->dirroot . '/mod/elluminate/lib.php');

		if($this->content !== NULL) {
			return $this->content;
		}
		$this->content        = new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';

		if (!isloggedin() || empty($this->instance)) {
			return $this->content;
		}

		/*
		 if (isadmin()) {
		 $this->content->items[] = '<a href="' . $CFG->wwwroot . '/blocks/elluminate/' .
		 'manageusers.php?course=' . $this->instance->pageid . '">' .
		 get_string('manageusers', 'block_elluminate') . '</a>';
		 $this->content->icons[] = '<img src="' . $CFG->pixpath . '/c/group.gif" ' .
		 'width="16" height="16" alt="' .
		 get_string('manageusers', 'block_elluminate') . '" />';
		 }
		 */

		if ($recordings = elluminate_recent_recordings()) {
			$this->content->items[] = '<b>' . get_string('recentrecordings', 'block_elluminate') . ':</b>';
			$this->content->icons[] = '';

			foreach ($recordings as $recording) {
				$elluminate = get_record('elluminate', 'meetingid', $recording->meetingid);
				$this->content->items[] = '<a href="' . $CFG->wwwroot .
                                                  '/mod/elluminate/view.php?a=' .
				$elluminate->id . '&amp;group=' . $elluminate->groupid . '" target="new">' .
				$recording->name . '</a>';
				/*
				 $this->content->items[] = '<a href="' . $CFG->wwwroot .
				 '/mod/elluminate/loadrecording.php?id=' .
				 $recording->recordingid . '" target="new">' .
				 $recording->name . '</a><br /><span class="breadcrumb">' .
				 userdate($recording->created) . '</span>';
				 */
				$this->content->icons[] = '<img src="' . $CFG->pixpath . '/i/backup.gif" ' .
                                                  'width="16" height="16" alt="' .
				get_string('recentrecordings', 'block_elluminate') . '" />';
			}
		}

		return $this->content;
	}

}

?>
