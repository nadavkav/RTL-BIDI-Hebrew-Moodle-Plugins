<?php

	// Paul Holden 24th July, 2007
	// various poll library functions

	function poll_sort_callback($a, $b) {
		return ($a == $b ? 0 : ($a > $b ? -1 : 1));
	}

	function poll_sort_results(&$options, $callback = 'poll_sort_callback') {
		return uasort($options, $callback);
	}

	function poll_get_graphbar($img = '0', $width = '100') {
		global $CFG;
		return "<img src=\"$CFG->wwwroot/blocks/poll/img/graph$img.gif\" height=\"15\" width=\"$width\" border=\"1\" /><br />";
	}

?>
