<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** Configurable Reports
  * A Moodle block for creating Configurable Reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

    require_once("../../config.php");
	
	require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");
	require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/component.class.php');	
	require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

	$id = required_param('id', PARAM_INT);
	$comp = required_param('comp', PARAM_ALPHA);

	if(! $report = get_record('block_configurable_reports_report','id',$id))
		print_error('reportdoesnotexists');

	
	if (! $course = get_record("course", "id", $report->courseid) ) {
		print_error("No such course id");
	}	
	
	// Force user login in course (SITE or Course)
    if ($course->id == SITEID){
		require_login();
		$context = get_context_instance(CONTEXT_SYSTEM);
	}	
	else{
		require_login($course->id);		
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
	}

	if(! has_capability('block/configurable_reports:managereports', $context) && ! has_capability('block/configurable_reports:manageownreports', $context))
		print_error('badpermissions');
	
				
	if(! has_capability('block/configurable_reports:managereports', $context) && $report->ownerid != $USER->id)
		print_error('badpermissions');
		
	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');
	
	$reportclassname = 'report_'.$report->type;	
	$reportclass = new $reportclassname($report->id);
	
	if(!in_array($comp,$reportclass->components))
		print_error('badcomponent');

	$elements = cr_unserialize($report->components);
	$elements = isset($elements[$comp]['elements'])? $elements[$comp]['elements'] : array();
	
	require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/component.class.php');	
	$componentclassname = 'component_'.$comp;
	$compclass = new $componentclassname($report->id);
	
	if($compclass->form){
		require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/form.php');
		$classname = $comp.'_form';
		$editform = new $classname('editcomp.php?id='.$id.'&comp='.$comp,compact('compclass','comp','id','report','reportclass','elements'));
		
		if($editform->is_cancelled()){
			redirect($CFG->wwwroot.'/blocks/configurable_reports/editcomp.php?id='.$id.'&amp;comp='.$comp);
		}
		else if ($data = $editform->get_data()) {
			$compclass->form_process_data(&$editform);
			add_to_log($report->courseid, '', 'edit', '', $report->name);
		}
		
		$compclass->form_set_data(&$editform);

	}
	
	if($compclass->plugins){
		$currentplugins = array();
		if($elements){
			foreach($elements as $e){
				$currentplugins[] = $e['pluginname'];
			}
		}
		$plugins = get_list_of_plugins('blocks/configurable_reports/components/'.$comp);
		$optionsplugins = array();
		foreach($plugins as $p){
			require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/'.$p.'/plugin.class.php');
			$pluginclassname = 'plugin_'.$p;
			$pluginclass = new $pluginclassname($report);
			if(in_array($report->type,$pluginclass->reporttypes)){
				if($pluginclass->unique && in_array($p,$currentplugins))
					continue;
				$optionsplugins[$p] = get_string($p,'block_configurable_reports');
			}
		}
		asort($optionsplugins);
	}
	

	$title = format_string($report->name).' '.get_string($comp,'block_configurable_reports');
	$navlinks = array();
	$navlinks[] = array('name' => get_string('managereports','block_configurable_reports'), 'link' => $CFG->wwwroot.'/blocks/configurable_reports/managereport.php?courseid='.$report->courseid, 'type' => 'title');
	$navlinks[] = array('name' => $title, 'link' => null, 'type' => 'title');
	$navigation = build_navigation($navlinks);
	
	print_header($title, $title, $navigation, "", "", true);
	
	$currenttab = $comp;
	include('tabs.php');
	
	if($elements){
		$table = new stdclass;
		$table->head = array(get_string('idnumber'),get_string('name'),get_string('summary'),get_string('edit'));
		$i = 0;
			
		
		foreach($elements as $e){
			require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/'.$e['pluginname'].'/plugin.class.php');
			$pluginclassname = 'plugin_'.$e['pluginname'];
			$pluginclass = new $pluginclassname($report);
		
			$editcell = '';
		
			if($pluginclass->form){
				$editcell .= '<a href="editplugin.php?id='.$id.'&comp='.$comp.'&pname='.$e['pluginname'].'&cid='.$e['id'].'"><img src="'.$CFG->pixpath.'/t/edit.gif" class="iconsmall"></a>';
			}
			
			$editcell .= '<a href="editplugin.php?id='.$id.'&comp='.$comp.'&pname='.$e['pluginname'].'&cid='.$e['id'].'&delete=1&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall"></a>';

			if($compclass->ordering && $i != 0 && count($elements) > 1)
				$editcell .= '<a href="editplugin.php?id='.$id.'&comp='.$comp.'&pname='.$e['pluginname'].'&cid='.$e['id'].'&moveup=1&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/up.gif" class="iconsmall"></a>';
			if($compclass->ordering && $i != count($elements) -1)
				$editcell .= '<a href="editplugin.php?id='.$id.'&comp='.$comp.'&pname='.$e['pluginname'].'&cid='.$e['id'].'&movedown=1&amp;sesskey='.sesskey().'"><img src="'.$CFG->pixpath.'/t/down.gif" class="iconsmall"></a>';
			
			$table->data[] = array('c'.($i+1),$e['pluginfullname'],$e['summary'],$editcell);
			$i++;
		}
		print_table($table);
	}
	else{
		if($compclass->plugins)
			echo print_heading(get_string('no'.$comp.'yet','block_configurable_reports'));
	}
	
	if($compclass->plugins){
		echo '<div class="boxaligncenter">';
		echo '<p class="centerpara">';
		print_string('add');
		echo ': &nbsp;';
		choose_from_menu($optionsplugins,'plugin','',get_string('choose'),"location.href = 'editplugin.php?id=".$id."&comp=".$comp."&pname='+document.getElementById('menuplugin').value");
		echo '</p>';
		echo '</div>';
	}
	
	if($compclass->form){
		$editform->display();
	}
	
	if($compclass->help){
		echo '<div class="boxaligncenter">';	
		echo '<p class="centerpara">';
		helpbutton('comp_'.$comp, get_string('componenthelp','block_configurable_reports'),'block_configurable_reports', true, true);
		echo '</p>';
		echo '</div>';
	}
	
	print_footer();

?>