<?php  // $Id: item_edit_form.php,v 1.2 2008/09/21 12:57:49 danielpr Exp $

require_once $CFG->libdir.'/formslib.php';
require_once $CFG->libdir.'/filelib.php';


class block_exabis_eportfolio_comment_edit_form extends moodleform {
	function definition() {
		global $CFG, $USER;
		$mform =& $this->_form;
		
        $mform->addElement('header', 'comment', get_string("addcomment", "block_exabis_eportfolio"));
        
		$mform->addElement('htmleditor', 'entry', get_string("comment", "block_exabis_eportfolio"), array('rows'=>10));
		$mform->setType('entry', PARAM_RAW);
		$mform->addRule('entry', get_string("commentshouldnotbeempty", "block_exabis_eportfolio"), 'required', null, 'client');
        $mform->setHelpButton('entry', array('writing', 'richtext'), false, 'editorhelpbutton');

        $this->add_action_buttons(false, get_string('add'));

        $mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_ACTION);
		$mform->setDefault('action', 'add');
        
		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
		
		$mform->addElement('hidden', 'itemid');
		$mform->setType('itemid', PARAM_INT);
		$mform->setDefault('itemid', 0);
		
		$mform->addElement('hidden', 'userid');
		$mform->setType('userid', PARAM_INT);
		$mform->setDefault('userid', 0);
	}
}

class block_exabis_eportfolio_item_edit_form extends moodleform {

	function definition() {
		global $CFG, $USER;

		$type = $this->_customdata['type'];
		
		$mform =& $this->_form;

		$mform->addElement('header', 'general', get_string($type, "block_exabis_eportfolio"));

		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
		$mform->setDefault('id', 0);
	
		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);

		// wird f�r das formular beim moodle import ben�tigt
		$mform->addElement('hidden', 'assignmentid');
		$mform->setType('assignmentid', PARAM_INT);

		$mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_ACTION);
		$mform->setDefault('action', '');

		$mform->addElement('text', 'name', get_string("title", "block_exabis_eportfolio"), 'maxlength="255" size="60"');
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', get_string("titlenotemtpy", "block_exabis_eportfolio"), 'required', null, 'client');

		$mform->addElement('select', 'categoryid', get_string("category", "block_exabis_eportfolio"), array());
		$mform->addRule('categoryid', get_string("categorynotempty", "block_exabis_eportfolio"), 'required', null, 'client');
		$mform->setDefault('categoryid', 0);
		$this->category_select_setup();

		if ($type == 'link') {
			$mform->addElement('text', 'url', get_string("url", "block_exabis_eportfolio"), 'maxlength="255" size="60"');
			$mform->setType('url', PARAM_TEXT);
			$mform->addRule('url', get_string("urlnotempty", "block_exabis_eportfolio"), 'required', null, 'client');
		}
		elseif ($type == 'file') {
			if ($this->_customdata['action'] == 'add') {
				$this->set_upload_manager(new upload_manager('attachment', true, false, $this->_customdata['course'], false, 0, true, true, false));
				$mform->addElement('file', 'attachment', get_string("file", "block_exabis_eportfolio"));
			} else {
				// filename for assignment import
				$mform->addElement('hidden', 'filename');
				$mform->setType('filename', PARAM_RAW);
				$mform->setDefault('filename', '');
			}
		}

		$mform->addElement('htmleditor', 'intro', get_string("intro", "block_exabis_eportfolio"), array('rows'=>25));
		$mform->setType('intro', PARAM_RAW);
		$mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');
		if ($type == 'note')
			$mform->addRule('intro', get_string("intronotempty", "block_exabis_eportfolio"), 'required', null, 'client');

		$mform->addElement('format', 'format', get_string('format'));

		$this->add_action_buttons();
	}

  function category_select_setup() {
    global $CFG, $USER;
    $mform =& $this->_form;
    $categorysselect =& $mform->getElement('categoryid');
    $categorysselect->removeOptions();

    $arrow = right_to_left() ? " &lArr; " : " &rArr; "; // nadavkav patch rtl

    $outercategories = get_records_select("block_exabeporcate", "userid='$USER->id' AND pid=0", "name asc");
    $categories = array();
    if ( $outercategories ) {
        foreach ( $outercategories as $curcategory ) {
          $categories[$curcategory->id] = format_string($curcategory->name);

      $inner_categories = get_records_select("block_exabeporcate", "userid='$USER->id' AND pid='$curcategory->id'", "name asc");
      if($inner_categories) {
            foreach ( $inner_categories as $inner_curcategory ) {

              $categories[$inner_curcategory->id] = format_string($curcategory->name) . $arrow . format_string($inner_curcategory->name);
            }
      }
        }
    } else {
        $categories[0] = get_string("nocategories","block_exabis_eportfolio");
    }
    $categorysselect->loadArray($categories);
  }
}
