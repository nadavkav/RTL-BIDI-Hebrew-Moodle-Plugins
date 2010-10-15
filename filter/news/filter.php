<?php
// Import of css file
if (file_exists($CFG->dirroot.'/theme/'.current_theme().'/news.css')) {
	echo '<style type="text/css">';
    	echo '	@import url('. $CFG->httpsthemewww.'/'.current_theme().'/news.css);';
	echo '</style>';
} else {
	echo '<style type="text/css">';
    	echo '	@import url('.$CFG->wwwroot.'/filter/news/news.css);';
	echo '</style>';
}

/**
* This filter shows a top "x" news from a forum
* This will work ONLY on the first page of your Moodle (for security reasons)
* It uses timed discussion and other parameters
*
* [-NEWS($forumid(INT),$groupeid(INT),$nbpost(INT))-]
*
* @package filter-news
* @category filter
* @author Eric Bugnet
*
*/


/**
* Sort array on a specified field
*
* @param array $records The array to sort
* @param string $field The field used to sort the array
* @param bool $reverse ASC or DESC
* @return array An array wich contains the data sorted
*
* This script is issued from www.php.net
*
*/
function record_sort($records, $field, $reverse=false) {
	$hash = array();
	foreach($records as $key => $record) {
		$hash[$record[$field].$key] = $record;
	}
	($reverse)? krsort($hash) : ksort($hash);
	$records = array();
	foreach($hash as $record) {
		$records []= $record;
	}
	return $records;
}


/**
* Change all instances of NEWS in the text
*
* @uses $CFG,$COURSE;
* @param int $coursid The id of the course being tested
* @param string $text The text to filter
* @return string The text filtered
*/
function news_filter($courseid, $text) {
	global $CFG,$COURSE;
	$CFG->currenttextiscacheable = false;

	// Do a quick check to avoid unnecessary work
	// - Is there instance ?
	// - Are we on the first page ?
	if (($COURSE->id == 1) || (strpos($text, '[[forum(') === false)) {
		return $text;
	}

	// There is job to do.... so let's do it !
	$pattern = '\[\[\forum\(([0-9]+),([0-9]+),([0-9]+)\)\]\]';
	$moduleid = get_record('modules', 'name', 'forum');

	// If there is an instance again...
	while (ereg($pattern,$text,$regs)) {

		// For each instance
		if ($regs[3]>0) {
			$cmid=$regs[1];
			$groupid=$regs[2];
			$nbpost=$regs[3];
			$news = '';
			if ($groupid>0) {
				$group = $groupid;
			} else {
				$group = '-1';
			}
			$nbcaract=100;

			// Get the forum ID
			$data = array();
			if ($data = get_record('course_modules', 'id', $cmid, 'module', $moduleid->id)) {
				$forumid=$data->instance;

				// Get the discussions
				$discussions = array();
				$i=0;
				$time=time();

				// Get last "x" discussion with timestart and store them in $data array
				$query_with="
					    SELECT
						*
					    FROM
						{$CFG->prefix}forum_discussions
					    WHERE
					    	forum = {$forumid} AND
						timestart <> 0 AND
						groupid = {$group} AND
						(timeend > {$time} OR
						timeend = 0)
					    ORDER BY
						timestart DESC
						LIMIT {$nbpost}
				";
				if ($datas = get_records_sql($query_with)) {
					foreach ($datas as $data) {
						$discussions[$i]["id"]=$data->id;
						$discussions[$i]["userid"]=$data->userid;
						$discussions[$i]["time"]=$data->timestart;
						$discussions[$i]["name"]=$data->name;
						$i++;
					}
				}

				// Get last "x" discussion without timestart and store them in $data array
				$query_without="
					    SELECT
						*
					    FROM
						{$CFG->prefix}forum_discussions
					    WHERE
					    	forum = {$forumid} AND
						timestart = 0 AND
						groupid = {$group} AND
						(timeend > {$time} OR
						timeend = 0)
					    ORDER BY
						timemodified DESC
						LIMIT {$nbpost}
				";

				if ($datas = get_records_sql($query_without)) {
					foreach ($datas as $data) {
						$discussions[$i]["id"]=$data->id;
						$discussions[$i]["userid"]=$data->userid;
						$discussions[$i]["time"]=$data->timemodified;
						$discussions[$i]["name"]=$data->name;
						$i++;
					}
				}

				// Organize  $data array
				// - sort on $discussions["time"] DESC
				$discussions = record_sort($discussions, 'time', true);
				// - select only post nb, not more
				$discussions = array_slice($discussions, 0, $nbpost);

				if ($discussions) {
					// There is posts, let's print them !
					$news .= '<div class="newsfilter"><ul>';
					for ($i=0; $i<count($discussions); $i++) {
						$news .= '<li>';
						if ($user = get_record('user', 'id', $discussions[$i]["userid"])) {
							$news .= print_user_picture($user->id, $COURSE->id, $user->picture, '16', true, true, '', false);
						}
						// Make the full name
						 if ($CFG->fullnamedisplay == 'firstname lastname') {
							 $fullname = $user->firstname.' '.$user->lastname;
						 } else if ($CFG->fullnamedisplay == 'lastname firstname') {
							 $fullname = $user->lastname.' '.$user->firstname;
						 } else if ($CFG->fullnamedisplay == 'firstname') {
							 $fullname = $user->firstname;
						 }
						// Print the link
						$news .= '<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussions[$i]["id"].'" title="'.userdate($discussions[$i]["time"],'%d/%m/%y ').'- '.$fullname.'" >';
						$news .= substr($discussions[$i]["name"],0,$nbcaract);
						if (strlen($discussions[$i]["name"]) > $nbcaract) {
							$news .= '...';
						}
						$news .= '</a></li>';
					}
					$news .= '</ul></div>';
				}
			}
			// Change chain in text
			$text = str_replace('[[forum('.$cmid.','.$groupid.','.$nbpost.')]]',$news,$text);
		} else {
			break;
		}

	}

	return $text;
}
?>