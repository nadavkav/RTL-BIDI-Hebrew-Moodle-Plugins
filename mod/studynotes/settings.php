<?php

$settings->add(
	new admin_setting_configmulticheckbox(
		'studynotes_markers_available',
		get_string('markers_available_title', 'studynotes'),
		get_string('markers_available_description', 'studynotes'),
		array(
			'question'=>true,
			'repetition'=>true,
			'reference'=>true
			), 
		array(
			'question'=>get_string('markers_question_title', 'studynotes'),
			'repetition'=>get_string('markers_repetition_title', 'studynotes'),
			'reference'=>get_string('markers_reference_title', 'studynotes'),
			)
		)
	);
	
$settings->add(
	new admin_setting_configmulticheckbox(
		'studynotes_plugins_available',
		get_string('plugins_available_title', 'studynotes'),
		get_string('plugins_available_description', 'studynotes'),
		array(
			'image'=>true,
			'link'=>true,
			'table'=>true,
			'HTML'=>true,
			'LaTeXmage'=>true
			), 
		array(
			'image'=>get_string('plugins_image_title', 'studynotes'),
			'link'=>get_string('plugins_link_title', 'studynotes'),
			'table'=>get_string('plugins_table_title', 'studynotes'),
			'HTML'=>get_string('plugins_html_title', 'studynotes'),
			'LaTeXmage'=>get_string('plugins_latex_title', 'studynotes'),
			)
		)
	);
	
$settings->add(new admin_setting_configtext('studynotes_latex_path', get_string('latex_path_title', 'studynotes'),
                   get_string('latex_path_description', 'studynotes'), "/usr/bin/latex"));
$settings->add(new admin_setting_configtext('studynotes_dvipng_path', get_string('dvipng_path_title', 'studynotes'),
                   get_string('dvipng_path_description', 'studynotes'), "/usr/bin/dvipng"));

$settings->add(new admin_setting_configcheckbox('studynotes_show_help', get_string('show_help', 'studynotes'),
                   get_string('show_help_description', 'studynotes'), 1));
?>
