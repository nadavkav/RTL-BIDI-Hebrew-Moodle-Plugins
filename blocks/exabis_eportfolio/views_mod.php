<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once dirname(__FILE__).'/inc.php';
require_once dirname(__FILE__).'/lib/sharelib.php';

$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param("action", "", PARAM_ALPHA);
$confirm = optional_param("confirm", "", PARAM_BOOL);
$id = optional_param('id', 0, PARAM_INT);
$shareusers = optional_param('shareusers', '', PARAM_RAW); // array of integer

if (!confirm_sesskey()) {
	print_error("badsessionkey","block_exabis_eportfolio");    	
}


require_login($courseid);

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/exabis_eportfolio:use', $context);

if (!$COURSE) {
   print_error("invalidcourseid","block_exabis_eportfolio");
}

if ($action == 'userlist') {
	echo json_encode(exabis_eportfolio_get_shareable_courses_with_users());
	exit;
}


if ($id) {
	if (!$view = get_record('block_exabeporview', 'id', $id, 'userid', $USER->id)) {
		print_error("wrongviewid", "block_exabis_eportfolio");
	}
} else {
	$view  = null;
}

$returnurl = $CFG->wwwroot.'/blocks/exabis_eportfolio/views_list.php?courseid='.$courseid;

// delete item
if ($action == 'delete') {
	if (!$view) {
		print_error("bookmarknotfound", "block_exabis_eportfolio");        
	}
	if (data_submitted() && $confirm && confirm_sesskey()) {
		delete_records('block_exabeporviewblock', 'viewid', $view->id);
		$status = delete_records('block_exabeporview', 'id', $view->id);
		
		add_to_log(SITEID, 'blog', 'delete', 'views_mod.php?courseid='.$courseid.'&id='.$view->id.'&action=delete&confirm=1', $view->name);

		if (!$status) {
			print_error('deleteposterror', 'block_exabis_eportfolio', $returnurl);
		}
		redirect($returnurl);
	} else {
		$optionsyes = array('id'=>$id, 'action'=>'delete', 'confirm'=>1, 'sesskey'=>sesskey(), 'courseid'=>$courseid);
		$optionsno = array('courseid'=>$courseid);

		block_exabis_eportfolio_print_header('views');
		echo '<br />';
		notice_yesno(get_string("deleteconfirm", "block_exabis_eportfolio"), 'views_mod.php', 'views_list.php', $optionsyes, $optionsno, 'post', 'get');
		print_footer();
		die;
	}
}


$query = "select i.id, i.name, i.type, ic.name AS cname, ic2.name AS cname_parent, COUNT(com.id) As comments".
	 " from {$CFG->prefix}block_exabeporitem i".
	 " join {$CFG->prefix}block_exabeporcate ic on i.categoryid = ic.id".
	 " left join {$CFG->prefix}block_exabeporcate ic2 on ic.pid = ic2.id".
	 " left join {$CFG->prefix}block_exabeporitemcomm com on com.itemid = i.id".
	 " where i.userid= ".$USER->id.
	 " GROUP BY i.id, i.name, i.type, ic.name, ic2.name".
	 " ORDER BY i.name";
$portfolioItems = get_records_sql($query);
if (!$portfolioItems) {
	$portfolioItems = array();
}

foreach ($portfolioItems as &$item) {
	if (null == $item->cname_parent) {
		$item->category = format_string($item->cname);
	} else {
		$item->category = format_string($item->cname_parent) . " &rArr; " . format_string($item->cname);
	}
	unset($item->cname);
	unset($item->cname_parent);
}
unset($item);


if ($view) {
	$sharedUsers = get_records('block_exabeporviewshar', 'viewid', $view->id, null, 'userid');
	if (!$sharedUsers) {
		$sharedUsers = array();
	} else {
		$sharedUsers = array_flip(array_keys($sharedUsers));
	}
} else {
	$sharedUsers = array();
}


require_once $CFG->libdir.'/formslib.php';

class block_exabis_eportfolio_view_edit_form extends moodleform {
	function definition() {
		global $CFG, $USER;
		$mform =& $this->_form;

		$mform->updateAttributes(array('class'=>''));
		
		$mform->addElement('hidden', 'items');
        $mform->addElement('hidden', 'action');
		$mform->addElement('hidden', 'courseid');
		$mform->addElement('hidden', 'viewid');

		$mform->addElement('text', 'name', get_string("title", "block_exabis_eportfolio"), 'maxlength="255" size="60"');
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', get_string("titlenotemtpy", "block_exabis_eportfolio"), 'required', null, 'client');

		$mform->addElement('textarea', 'description', get_string("title", "block_exabis_eportfolio"), 'maxlength="65000" size="60"');
		$mform->setType('description', PARAM_TEXT);

		$mform->addElement('hidden', 'blocks');
		$mform->setType('blocks', PARAM_RAW);

		$mform->addElement('checkbox', 'externaccess');
		$mform->setType('externaccess', PARAM_INT);

		$mform->addElement('checkbox', 'internaccess');
		$mform->setType('internaccess', PARAM_INT);

		$mform->addElement('checkbox', 'externcomment');
		$mform->setType('externcomment', PARAM_INT);

		$mform->addElement('text', 'shareall');
		$mform->setType('shareall', PARAM_INT);

		if ($this->_customdata['view'])
			$this->add_action_buttons(false, get_string('savechanges'));
		else
			$this->add_action_buttons(false, get_string('add'));
	}

	function toArray() {
        //finalize the form definition if not yet done
        if (!$this->_definition_finalized) {
            $this->_definition_finalized = true;
            $this->definition_after_data();
        }

        $form = $this->_form->toArray();

		$form['html_hidden_fields'] = '';
		$form['elements_by_name'] = array();

		foreach ($form['elements'] as $element) {
			if ($element['type'] == 'hidden')
				$form['html_hidden_fields'] .= $element['html'];
			$form['elements_by_name'][$element['name']] = $element;
		}
	
		return $form;
    }
}


$editform = new block_exabis_eportfolio_view_edit_form($_SERVER['REQUEST_URI'], array('view' => $view, 'course' => $COURSE->id, 'action'=> $action));

if ($editform->is_cancelled()) {
	redirect($returnurl);
} else if ($editform->no_submit_button_pressed()) {
	die("nosubmitbutton");
	//no_submit_button_actions($editform, $sitecontext);
} else if ($formView = $editform->get_data()) {

	$dbView = $formView;
	$dbView->timemodified = time();

	if (!$view || !$view->hash) {
		// generate view hash
        do {
			$hash = substr(md5(microtime()), 3, 8);
        } while (record_exists("block_exabeporview", "hash", $hash));
		$dbView->hash = $hash;
	}

	if (empty($dbView->externaccess)) {
		$dbView->externaccess = 0;
	}
	if (empty($dbView->internaccess)) {
		$dbView->internaccess = 0;
	}
	if (!$dbView->internaccess || empty($dbView->shareall)) {
		$dbView->shareall = 0;
	}
	if (empty($dbView->externcomment)) {
		$dbView->externcomment = 0;
	}

	switch ($action) {
		case 'add':

			$dbView->userid = $USER->id;

			if ($dbView->id = insert_record('block_exabeporview', $dbView)) {
				add_to_log(SITEID, 'bookmark', 'add', 'views_mod.php?courseid='.$courseid.'&id='.$dbView->id.'&action=add', $dbView->name);
			} else {
				print_error('addposterror', 'block_exabis_eportfolio', $returnurl);
			}
		break;

		case 'edit':
			
			if (!$view) {
				print_error("bookmarknotfound", "block_exabis_eportfolio");	                
			}

			$dbView->id = $view->id;

			if (update_record('block_exabeporview', $dbView)) {
				add_to_log(SITEID, 'bookmark', 'update', 'item.php?courseid='.$courseid.'&id='.$dbView->id.'&action=edit', $dbView->name);
			} else {
				print_error('updateposterror', 'block_exabis_eportfolio', $returnurl);
			}

		break;
		
		default:
			print_error("unknownaction", "block_exabis_eportfolio");
			exit;
	}

	// delete all blocks
	delete_records('block_exabeporviewblock', 'viewid', $dbView->id);

	// add blocks
	$blocks = json_decode(stripslashes($formView->blocks));
	foreach ($blocks as $block) {
		$block->viewid = $dbView->id;
		insert_record('block_exabeporviewblock', $block);
	}

	// delete all shared users
	delete_records("block_exabeporviewshar", 'viewid', $dbView->id);

	// add new shared users
	if ($dbView->internaccess && !$dbView->shareall && is_array($shareusers)) {
		foreach ($shareusers as $shareuser) {
			$shareuser = clean_param($shareuser, PARAM_INT);
			
			$shareItem = new stdClass();
			$shareItem->viewid = $dbView->id;
			$shareItem->userid = $shareuser;
			insert_record("block_exabeporviewshar", $shareItem);
		}
	}

	redirect($returnurl);
}

// gui setup
$postView = $view;
$postView->action       = $action;
$postView->courseid     = $courseid;

switch ($action) {
	case 'add':
		$postView->internaccess = 0;
		$postView->shareall = 1;

		$strAction = get_string('new');
		break;
	case 'edit':
		if (!isset($postView->internaccess) && ($postView->shareall || $sharedUsers)) {
			$postView->internaccess = 1;
		}

		$strAction = get_string('edit');
		break;
	default :
		print_error("unknownaction", "block_exabis_eportfolio");	                	            
}



if ($view) {
	$query = "select b.*".
		 " from {$CFG->prefix}block_exabeporviewblock b".
		 " where b.viewid = ".$view->id." ORDER BY b.positionx, b.positiony";

	$blocks = get_records_sql($query);
	$postView->blocks = json_encode($blocks);
}




require_js($CFG->wwwroot.'/blocks/exabis_eportfolio/js/jquery.js');
require_js($CFG->wwwroot.'/blocks/exabis_eportfolio/js/jquery.ui.js');
require_js($CFG->wwwroot.'/blocks/exabis_eportfolio/js/jquery.json.js');
require_js($CFG->wwwroot.'/blocks/exabis_eportfolio/js/exabis_eportfolio.js');
require_js($CFG->wwwroot.'/blocks/exabis_eportfolio/js/views_mod.js');
$CFG->stylesheets[] = $CFG->wwwroot.'/blocks/exabis_eportfolio/css/views_mod.css';
block_exabis_eportfolio_print_header('views');

$editform->set_data($postView);
$form = $editform->toArray();

// Translations
$translations = array(
	'name', 'role', 'nousersfound',
	'view_specialitem_headline', 'view_specialitem_headline_defaulttext', 'view_specialitem_text', 'view_specialitem_text_defaulttext',
	'viewitem', 'comments', 'category', 'type',
	'delete', 'viewand',
	'file', 'note', 'link',
	'internalaccess', 'externalaccess', 'internalaccessall', 'internalaccessusers', 'view_sharing_noaccess', 
);


$translations = array_flip($translations);
foreach ($translations as $key => &$value) {
	$value = block_exabis_eportfolio_get_string($key);
}
unset($value);

echo '<script>'."\n";
echo 'var portfolioItems = '.json_encode($portfolioItems).';'."\n";
echo 'var sharedUsers = '.json_encode($sharedUsers).';'."\n";
echo 'ExabisEportfolio.setTranslations('.json_encode($translations).');'."\n";
echo '</script>';

echo $form['javascript'];
echo '<form'.$form['attributes'].'><div id="view-mod">';
echo $form['html_hidden_fields'];

// view data form
echo '<div class="view-data view-group'.(!$view?' view-group-open':'').'">';
	echo '<div class="view-group-header"><div>';
	echo get_string('view', 'block_exabis_eportfolio').': <span id="view-name">'.(!empty($postView->name)?$postView->name:'new').'</span> <span class="change">('.get_string('change', 'block_exabis_eportfolio').')</span>';
	echo '</div></div>';
	echo '<div class="view-group-body">';
		echo '<div class="mform">';
		echo '<fieldset class="clearfix"><legend class="ftoggler">'.get_string('viewinformation', 'block_exabis_eportfolio').'</legend>';
			echo '<div class="fitem required"><div class="fitemtitle"><label for="id_name">'.get_string('viewtitle', 'block_exabis_eportfolio').'<img class="req" title="Required field" alt="Required field" src="'.$CFG->wwwroot.'/pix/req.gif" /> </label></div><div class="felement ftext">'.$form['elements_by_name']['name']['html'].'</div></div>';
			echo '<div class="fitem"><div class="fitemtitle"><label for="id_name">'.get_string('viewdescription', 'block_exabis_eportfolio').'</label></div><div class="felement ftext">'.$form['elements_by_name']['description']['html'].'</div></div>';
		echo '</fieldset>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="view-middle">';
	echo '<div id="view-options">';
		echo '<div id="portfolioItems" class="view-group view-group-open">';
			echo '<div class="view-group-header"><div>'.get_string('viewitems', 'block_exabis_eportfolio').'</div></div>';
			echo '<div class="view-group-body">';
			echo '<ul class="portfolioOptions">';
				if (!$portfolioItems) {
					echo '<div style="padding: 5px;">'.get_string('nobookmarksall', 'block_exabis_eportfolio').'</div>';
				} else {
					foreach ($portfolioItems as $item) {
						echo '<li class="item" itemid="'.$item->id.'">'.$item->name.'</li>';
					}
				}
			echo '</ul>';
			echo '</div>';
		echo '</div>';

		echo '<div id="portfolioExtras" class="view-group view-group-open">';
			echo '<div class="view-group-header"><div>'.get_string('view_specialitems', 'block_exabis_eportfolio').'</div></div>';
			echo '<div class="view-group-body">';
			echo '<ul class="portfolioOptions">';
			echo '<li block-type="personal_information">'.get_string("explainpersonal", "block_exabis_eportfolio").'</li>';
			echo '<li block-type="headline">'.get_string('view_specialitem_headline', 'block_exabis_eportfolio').'</li>';
			echo '<li block-type="text">'.get_string('view_specialitem_text', 'block_exabis_eportfolio').'</li>';
			echo '</ul>';
			echo '</div>';
		echo '</div>';
	echo '</div>';

	echo '<div id="view-preview">';
		echo '<div class="view-group-header"><div>'.get_string('viewdesign', 'block_exabis_eportfolio').'</div></div>';
		echo '<div>';
			echo '<table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50%" valign="top">';
			echo '<ul class="portfolioDesignBlocks" design-column="1">';
			echo '</ul>';
			echo '</td><td width="50%" valign="top">';
			echo '<ul class="portfolioDesignBlocks portfolioDesignBlocks-left" design-column="2">';
			echo '</ul>';
			echo '</td></tr></table>';
		echo '</div>';
	echo '</div>';
	echo '<div class="clear"><span>&nbsp;</span></div>';
echo '</div>';

echo '<div class="view-sharing view-group">';
	echo '<div class="view-group-header"><div>'.get_string('view_sharing', 'block_exabis_eportfolio').': <span id="view-share-text"></span> <span class="change">('.get_string('change', 'block_exabis_eportfolio').')</span></div></div>';
	echo '<div class="view-group-body">';
		echo '<div style="padding: 18px 22px"><table width="100%">';
			
			echo '<tr><td style="padding-right: 10px" width="10">';
			echo $form['elements_by_name']['externaccess']['html'];
			echo '</td><td>'.get_string("externalaccess", "block_exabis_eportfolio").'</td></tr>';
			
			if ($view) {
				$url = block_exabis_eportfolio_get_external_view_url($view);
				// only when editing a view, the external link will work!
				echo '<tr id="externaccess-settings"><td></td><td>';
					echo '<div style="padding: 4px;"><a href="'.$url.'" target="_blank">'.$url.'</a></div>';
					echo '<div style="padding: 4px 0;"><table width="100%">';
						echo '<tr><td style="padding-right: 10px" width="10">';
						echo '<input type="checkbox" name="externcomment" value="1"'.($postView->externcomment?' checked':'').' />';
						echo '</td><td>'.get_string("externcomment", "block_exabis_eportfolio").'</td></tr>';
					echo '</table></div>';
					/*
					echo '<table>';
					echo '<tr><td>'.$form['elements_by_name']['externcomment']['html'];
					echo '</td><td>'.get_string("externalaccess", "block_exabis_eportfolio").'</td></tr>';
					echo '</table>';
					*/
				echo '</td></tr>';
			}
		
			echo '<tr><td height="10"></td></tr>';

			echo '<tr><td style="padding-right: 10px">';
			echo $form['elements_by_name']['internaccess']['html'];
			echo '</td><td>'.get_string("internalaccess", "block_exabis_eportfolio").'</td></tr>';
			echo '<tr id="internaccess-settings"><td></td><td>';
				echo '<div style="padding: 4px 0;"><table width="100%">';
					echo '<tr><td style="padding-right: 10px" width="10">';
					echo '<input type="radio" name="shareall" value="1"'.($postView->shareall?' checked':'').' />';
					echo '</td><td>'.get_string("internalaccessall", "block_exabis_eportfolio").'</td></tr>';
					echo '<tr><td style="padding-right: 10px">';
					echo '<input type="radio" name="shareall" value="0"'.(!$postView->shareall?' checked':'').'/>';
					echo '</td><td>'.get_string("internalaccessusers", "block_exabis_eportfolio").'</td></tr>';
					echo '<tr id="internaccess-users"><td></td><td id="sharing-userlist">userlist</td></tr>';
				echo '</table></div>';
			echo '</td></tr>';

		echo '</table></div>';
	echo '</div>';
echo '</div>';

echo '<div style="padding-top: 20px; text-align: center;">';
echo $form['elements_by_name']['submitbutton']['html'];
echo '</div>';

echo '</div></form>';

echo "<pre>";

// print_r($form);

block_exabis_eportfolio_print_footer();

