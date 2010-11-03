<?php

/******************************************************************************
 *
 *  Name: Ungraded Assignment Block
 *  Author: Thomash Haines (thomash@cciu.org),
 *			Laura Mikowychok (laurami@cciu.org)
 *			from Chester County Intermediate Unit
 *
 *	Date: 05/19/2008
 *
 *	Description: This block finds and lists all assignmnts that have been modified
 * 	since the last time it has been graded (a.k.a assignments needing grading)
 *
 *	It also finds quiz attempts that include a question requiring manual
 *	grading, that has not been graded
 *
 *	This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 ******************************************************************************/
class block_ungraded_assignments extends block_base {

    function init() {
        $this->title = get_string('title','block_ungraded_assignments');
        $this->version = 2008051900;
    }


	function get_content() {
	  global $THEME, $CFG, $USER, $SITE,$COURSE;

	  if ($this->content !== NULL) {
	      return $this->content;
	  }
	  require_once("../config.php");
	  // get the course id
	  $id   = optional_param('id', 0, PARAM_INT);          // Course module ID

	  // get the current context
	  if (!empty($this->instance->pageid)) {
	    $context = get_context_instance(CONTEXT_COURSE, $this->instance->pageid);
	    if ($COURSE->id == $this->instance->pageid) {
		$course = $COURSE;
	    } else {
		$course = get_record('course', 'id', $this->instance->pageid);
	    }
	  } else {
	      $context = get_context_instance(CONTEXT_COURSE, $this->instance->pageid);
	      $course = $SITE;

	  }

		if ( empty($this->config->txtBlockDirectory) ) $this->config->txtBlockDirectory = "";
		if ( empty($this->config->chkHideQuizzes) ) $this->config->chkHideQuizzes = "";



		// check if user has permission to grade
		if (has_capability('mod/assignment:grade', $context)) {
			// load some block configuration
			//this will determine whether or not the list will be collapsed
			switch($this->config->txtBlockDirectory) {
				case ("") : {

					$blockPath = "/ungraded_assignments";
					break;
				}

				default : {

					$blockPath = $this->config->txtBlockDirectory;
					break;

				}

			}

			switch($this->config->chkHideQuizzes) {
				case (true) : {

					$hideQuizzes = true;

					$descHideQuizzes = "<br/><br/><small><em>Quizzes are not being included in the list of assignments.</em></small>";

					break;
				}

				default : {

					$hideQuizzes = false;

					$descHideQuizzes = "";

					break;

				}

			}

// 			$collapseImg = "{$CFG->wwwroot}/blocks{$blockPath}/collapse.png";
// 			$expandImg =  "{$CFG->wwwroot}/blocks{$blockPath}/expand.png";

      $collapseImg = "{$CFG->wwwroot}/blocks{$blockPath}/switch_minus.gif";
      $expandImg =  "{$CFG->wwwroot}/blocks{$blockPath}/switch_plus.gif";

			$refreshImg =  "{$CFG->wwwroot}/blocks{$blockPath}/refresh.png";

			//this will determine whether or not the list will be collapsed
			if ( empty($this->config->chkDontCollapse) ) $this->config->chkDontCollapse = true;
			switch($this->config->chkDontCollapse) {
				case (true) : {

					$collapseHTML = "";
					$defaultCollapseIcon = $collapseImg;
					break;
				}

				default : {

					$collapseHTML = "display:none;";
					$defaultCollapseIcon = $expandImg;
					break;

				}

			}

			//whether or not we should show assignments from users no longer enrolled
			if ( empty($this->config->chkShowUnenrolled) ) $this->config->chkShowUnenrolled = false;
			switch($this->config->chkShowUnenrolled) {
				case (true) : {

					$sqlEnrolledHTML1 = "";
					$sqlEnrolledHTML2 = "";
					$descUnenrolledText = "<br/><br/><small><em>Displaying assignments from users previously and currently enrolled in this course.</small></em>";

					break;
				}

				default : {

					$sqlEnrolledHTML1 = "INNER JOIN {$CFG->prefix}role_assignments r on (r.userid = u.id)";
					$sqlEnrolledHTML2 = "AND r.contextid = {$context->id}";

					$descUnenrolledText = "";
					break;

				}

			}

			/*
				get all assignments where the time modified is greater than the
				time marked. this will find all assigments in need of grading.
				then, exclude all assignments from users no longer enrolled
				in the course

			*/

			$query ="SELECT s.id as subID, 'assignment' as assignmentType, s.timemodified as timemodified, u.id as userID,  m.id as id ,a.name as name,u.lastname as lastname ,u.firstname as firstname
						FROM {$CFG->prefix}assignment a
						INNER JOIN {$CFG->prefix}course_modules m ON (a.id=m.instance AND a.course=m.course AND m.module=1)
						INNER JOIN {$CFG->prefix}assignment_submissions s ON (a.id=s.assignment)
						INNER JOIN {$CFG->prefix}user u ON (u.id=s.userid)
						{$sqlEnrolledHTML1}
						WHERE a.course={$id}
						AND s.timemodified>s.timemarked
						{$sqlEnrolledHTML2}";

			$query = ($hideQuizzes) ? $query : $query .

						" UNION

						SELECT qa.id as subID, 'quiz' as assignmentType, qa.timemodified as timemodified, u.id as userID,  qu.id as id,qu.name as name,u.lastname as lastname,u.firstname as firstname
						FROM {$CFG->prefix}quiz qu
						inner join {$CFG->prefix}quiz_attempts qa on qu.id = qa.quiz
						inner join {$CFG->prefix}user u on u.id = qa.userid
						inner join {$CFG->prefix}question_states state on qa.id = state.attempt
						inner join {$CFG->prefix}question_sessions sess on qa.id = sess.attemptid
						inner join {$CFG->prefix}question quest on quest.id = state.question
						$sqlEnrolledHTML1
						WHERE	qu.course={$id}
						and qa.timefinish > 0
						and qa.preview = 0
						and sess.newest = state.id
						and state.event != 9
						and quest.qtype = 'essay'
						$sqlEnrolledHTML2";

			$query .=	" order by name, lastname, firstname";


			$assignments = get_records_sql($query);

			// create the content class
			$this->content = new stdClass;
			$this->content->text = '';


			if ( empty($assignments) ) {
				$this->content->text = get_string('noassignmentswaiting','block_ungraded_assignments');
				return $this->content;
			}

			$courses = array();

			$totalAssignments = 0;
			// loop through assignments and add them to the $userArray array
			foreach ($assignments as $assignment) {

				$userArray = array();
				$userArray["userID"] = $assignment->userID;
				$userArray["name"] = $assignment->lastname . ', ' . $assignment->firstname;
				$userArray["timemodified"] = date("F j, Y, g:i a", $assignment->timemodified);
				$userArray["courseID"] = $assignment->id;
				$userArray["assignmentType"] = $assignment->assignmentType;
				$userArray["subID"] = $assignment->subID;

				$courses[$assignment->name][] = $userArray;

				// increment the number of assignments
				$totalAssignments += 1;
			}

			// loop through $assignments and build html to display them including javascript to allow collapsing\
			foreach ($courses as $key=>$value) {

				//javascript id to identify the div containing the submissions for this assignment
				$divID="mdl_block_ungraded_assignments_" . $value[0]["courseID"];

				//javascript id to identify the collapse / expand images for this assignment
				$imgID="imgCollapse_block_ungraded_assignments_" . $value[0]["courseID"];


				//display the assignment
				$this->content->text .= "<div style=\"padding: 2px; cursor:pointer;\" onClick=\"document.getElementById('$imgID').src=(document.getElementById('$imgID').src=='$collapseImg') ? document.getElementById('$imgID').src='$expandImg' : document.getElementById('$imgID').src='$collapseImg';document.getElementById('$divID').style.display=(document.getElementById('$divID').style.display=='none') ? document.getElementById('$divID').style.display='block' : document.getElementById('$divID').style.display='none'; \"><img src=\"$defaultCollapseIcon\" id=\"$imgID\" target=\"_blank\" style=\"padding:2px;\" alt=\"\" />$key</div>
				 	<small><ul id=\"$divID\"  style=\"margin-top:0px;padding-left: 10px; $collapseHTML\">";



				foreach ($value as $userInfo) {

					// display the submissions needing graded

					switch ($userInfo["assignmentType"]) {

						case ("quiz") : {

							$gradeURL = $CFG->wwwroot . "/mod/quiz/review.php?q=" . $userInfo["courseID"] . "&attempt=" . $userInfo["subID"];

							$iconURL = $CFG->modpixpath . "/quiz/icon.gif";

							break;

						}

						case ("assignment") : {

							$gradeURL = $CFG->wwwroot . "/mod/assignment/submissions.php?id=" . $userInfo["courseID"] . "&userid=" . $userInfo["userID"] . "&mode=single&offset=1";

							$iconURL = $CFG->modpixpath . "/assignment/icon.gif";
							break;

						}

					}

					$this->content->text .=
						"<li style=\"margin:3px;border: 1px dotted; list-style: none;\"><a href=\"{$gradeURL}\" target=\"_blank\" title=\"Click to grade this " . $userInfo["assignmentType"] . "\"><img src=\"{$iconURL}\" class=\"icon\" alt=\"Grade Icon\" /></a><strong><a href=\"{$CFG->wwwroot}/user/view.php?id=" . $userInfo["userID"] . "&course=$id \" target=\"_blank\">" . $userInfo["name"] . "</a></strong>" .
						"<br/><em><div style=\"padding-left: 20px;\">" . $userInfo["timemodified"] . "</div></em>" .
						"</li>";
				}

				$this->content->text .="</ul></small>";
			}

			$this->content->footer = "<small>".get_string('worktobegraded','block_ungraded_assignments')."<strong>{$totalAssignments}</strong></small>$descUnenrolledText$descHideQuizzes<br/>";
			$this->content->footer .= "<a href=\"". $_SERVER['REQUEST_URI'] . "\"><img src=\"$refreshImg\" /></a>";
			return $this->content;
		}

	}



	function applicable_formats() {

		// this block should only be showed in courses
		return array('site-index' => false,
					'course-view' => true, 'course-view-social' => true,
					'mod' => false, 'mod-quiz' => false);
	}

	function instance_allow_config() {
		return true;
	}
}
?>
