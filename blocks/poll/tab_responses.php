<script language="javascript" type="text/javascript">
 function show_poll_results(id) {
  window.location.href="<?php echo(str_replace('&amp;', '&', $url) . 'responses&pid='); ?>" + id;
 }
</script>

<?php
	// Paul Holden 24th July, 2007
	// poll block; view poll responses tab

	$pid = optional_param('pid', 0, PARAM_INTEGER);

	function poll_custom_callback($a, $b) {
		$counta = $a->responsecount;
		$countb = $b->responsecount;
		return ($counta == $countb ? 0 : ($counta > $countb ? -1 : 1));
	}

	function get_response_checks($options, $selected) {
		foreach ($options as $option) {
			$arr[] = '<input type="checkbox" onclick="this.checked=' . ($option->id == $selected ? 'true" checked' : 'false"') . ' />';
		}
		return $arr;
	}

	$polls = get_records('block_poll', 'courseid', $COURSE->id);
	foreach ($polls as $poll) {
		$menu[$poll->id] = $poll->name;
	}

	print_simple_box_start();
	echo(get_string('editpollname', 'block_poll') . ': ');
	choose_from_menu($menu, 'pid', $pid, 'choose', 'show_poll_results(this.options[this.selectedIndex].value);');
	print_simple_box_end();

	if (($poll = get_record('block_poll', 'id', $pid)) && ($options = get_records('block_poll_option', 'pollid', $poll->id))) {
		foreach ($options as $option) {
			$option->responses = get_records('block_poll_response', 'optionid', $option->id);
			$option->responsecount = (!$option->responses ? 0 : count($option->responses));
		}
		poll_sort_results($options, 'poll_custom_callback');

		print_simple_box_start();
		echo("<strong>$poll->questiontext</strong><ol>");
		foreach ($options as $option) {
			echo("<li>$option->optiontext ($option->responsecount)</li>");
		}
		echo('</ol>');
		print_simple_box_end();

		if ($responses = get_records('block_poll_response', 'pollid', $poll->id, 'submitted ASC')) {
			$responsecount = count($responses);
			$optioncount = count($options);

			$table = new Object();
			$table->head = array('&nbsp;', get_string('user'), get_string('date'));
			for ($i = 1; $i <= $optioncount; $i++) {
				$table->head[] = $i;
			}
			$table->tablealign = 'left';
			$table->width = '*';

			foreach ($responses as $response) {
				$user = get_record('user', 'id', $response->userid, '', '', '', '', 'id, firstname, lastname, picture');
				$table->data[] = array_merge(array(print_user_picture($user->id, $COURSE->id, $user->picture, 0, true),
								   fullname($user),
								   userdate($response->submitted)),
							     get_response_checks($options, $response->optionid));
			}
		
			print_table($table);
		}
	}
?>
