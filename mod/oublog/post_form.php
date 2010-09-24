<?php

require_once($CFG->libdir.'/formslib.php');

class mod_oublog_post_form extends moodleform {

    function definition() {

        global $CFG;

        $individualblog = $this->_customdata['individual'];
        $maxvisibility = $this->_customdata['maxvisibility'];
        $allowcomments = $this->_customdata['allowcomments'];
        $edit          = $this->_customdata['edit'];
        $personal      = $this->_customdata['personal'];
        
        $mform    =& $this->_form;


        $mform->addElement('header', 'general', '');

        $mform->addElement('text', 'title', get_string('title', 'oublog'), 'size="48"');
        $mform->setType('title', PARAM_TEXT);

        if (class_exists('ouflags')) {
            $message_type = 'htmleditor';
            $message_rows = 30;
            
            
            if(ou_get_is_mobile()){ 
                $message_type = 'textarea';
                $message_rows = 20;
            }           
              
            $mform->addElement($message_type, 'message', get_string('message', 'oublog'), array('cols'=>50, 'rows'=>$message_rows));
                    
        }
        else {
            $mform->addElement('htmleditor', 'message', get_string('message', 'oublog'), array('cols'=>50, 'rows'=>30));
        }

        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('message', array('reading', 'writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('textarea', 'tags', get_string('tagsfield', 'oublog'), array('cols'=>48, 'rows'=>2));
        $mform->setType('tags', PARAM_TAGLIST);
        $mform->setHelpButton('tags', array('tags', get_string('tags', 'oublog'), 'oublog'));


        $options = array();
        if ($allowcomments) {
            $options[OUBLOG_COMMENTS_ALLOW] = get_string('logincomments', 'oublog');
            if ($allowcomments >= OUBLOG_COMMENTS_ALLOWPUBLIC 
                && OUBLOG_VISIBILITY_PUBLIC <= $maxvisibility) {
                $maybepubliccomments = true;
                $options[OUBLOG_COMMENTS_ALLOWPUBLIC] = get_string('publiccomments', 'oublog');
            }
            $options[OUBLOG_COMMENTS_PREVENT] = get_string('no', 'oublog');

            $mform->addElement('select', 'allowcomments', get_string('allowcomments', 'oublog'), $options);
            $mform->setType('allowcomments', PARAM_INT);
            $mform->setHelpButton('allowcomments', array('allowcomments', get_string('allowcomments', 'oublog'), 'oublog'));

            if (isset($maybepubliccomments)) {
                // We have to add horrible custom javascript to make this hide
                // itself because mform does not support it
                $mform->addElement('static', 'publicwarning', '', <<<ENDJS
<div id="publicwarningmarker"></div>
<script type="text/javascript">
var field = document.getElementById('publicwarningmarker').parentNode.parentNode;
var select = document.getElementById('id_allowcomments');
select.onchange = function() {
  field.style.display = select.value == 2 ? 'block' : 'none';
};
select.onchange();
</script>
ENDJS
                        . get_string('publiccomments_info', 'oublog'));
            }
        } else {
            $mform->addElement('hidden', 'allowcomments', OUBLOG_COMMENTS_PREVENT);
            $mform->setType('allowcomments', PARAM_INT);
        }

        $options = array();
        if (OUBLOG_VISIBILITY_COURSEUSER <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_COURSEUSER] = oublog_get_visibility_string(OUBLOG_VISIBILITY_COURSEUSER,$personal); 
        }
        if (OUBLOG_VISIBILITY_LOGGEDINUSER <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_LOGGEDINUSER] = oublog_get_visibility_string(OUBLOG_VISIBILITY_LOGGEDINUSER,$personal); 
        }
        if (OUBLOG_VISIBILITY_PUBLIC <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_PUBLIC] = oublog_get_visibility_string(OUBLOG_VISIBILITY_PUBLIC,$personal); 
        }
        if ($individualblog > OUBLOG_NO_INDIVIDUAL_BLOGS) {
            $mform->addElement('hidden', 'visibility', OUBLOG_VISIBILITY_COURSEUSER);
            $mform->setType('visibility', PARAM_INT);
        }elseif (OUBLOG_VISIBILITY_COURSEUSER != $maxvisibility) {
            $mform->addElement('select', 'visibility', get_string('visibility', 'oublog'), $options);
            $mform->setType('visibility', PARAM_INT);
            $mform->setHelpButton('visibility', array('visibility', get_string('visibility', 'oublog'), 'oublog'));
        } else {
            $mform->addElement('hidden', 'visibility', OUBLOG_VISIBILITY_COURSEUSER);
            $mform->setType('visibility', PARAM_INT);
        }

        if ($edit) {
            $submitstring = get_string('savechanges');
        } else {
            $submitstring = get_string('addpost', 'oublog');
        }

        $this->add_action_buttons(true, $submitstring);

    /// Hidden form vars
        $mform->addElement('hidden', 'blog');
        $mform->setType('blog', PARAM_INT);

        $mform->addElement('hidden', 'post');
        $mform->setType('postid', PARAM_INT);

    }
}