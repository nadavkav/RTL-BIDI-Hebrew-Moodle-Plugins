<?php 
	if (!count_records('door'))
		set_field('modules', 'visible', 0, 'name', 'door');  // Disable it by default
?>