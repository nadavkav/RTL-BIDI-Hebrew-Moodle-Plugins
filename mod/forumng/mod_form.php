<?php
require_once ($CFG->dirroot . '/course/moodleform_mod.php');
require_once ($CFG->dirroot . '/mod/forumng/forum.php');

class mod_forumng_mod_form extends moodleform_mod {

    private $clone;

    function definition() {

        global $CFG, $COURSE;
        $mform    =& $this->_form;
        $coursecontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        $forumng = $this->_instance
                ? get_record('forumng', 'id', $this->_instance) : null;
        $this->clone = $forumng ? $forumng->originalcmid : 0;

        // If this is a clone, don't show the normal form
        if ($this->clone) {
            $mform->addElement('hidden', 'name', $forumng->name);
            $mform->addElement('static', 'sharedthing', '', get_string(
                    'sharedinfo', 'forumng',
                    $CFG->wwwroot . '/course/modedit.php?update=' .
                    $this->clone . '&amp;return=1'));
            $this->shared_definition_part($coursecontext);
            return;
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Forum name
        $mform->addElement('text', 'name', get_string('forumname', 'forumng'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Forum types
        $types = forum_type::get_all();
        $options = array();
        foreach ($types as $type) {
            if ($type->is_user_selectable()) {
                $options[$type->get_id()] = $type->get_name();
            }
        }
        $mform->addElement('select', 'type', get_string('forumtype', 'forumng'), $options);
        $mform->setHelpButton('type', array('forumtype', get_string('forumtype', 'forumng'), 'forumng'));
        $mform->setDefault('type', 'general');

        $mform->addElement('htmleditor', 'intro', get_string('forumintro', 'forumng'));
        $mform->setType('intro', PARAM_RAW);
        $mform->setHelpButton('intro', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        // Subscription option displays only if enabled at site level
        if ($CFG->forumng_subscription == -1) {
            $options = forum::get_subscription_options();
            $mform->addElement('select', 'subscription',
                get_string('subscription', 'forumng'), $options);
            $mform->setDefault('subscription', forum::SUBSCRIPTION_PERMITTED);
            $mform->setHelpButton('subscription', array('subscription',
                get_string('subscription', 'forumng'), 'forumng'));
        } else {
            // Hidden element contains default value (not used anyhow)
            $mform->addElement('hidden', 'subscription',
                forum::SUBSCRIPTION_PERMITTED);
        }

        // Max size of attachments
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[-1] = get_string('uploadnotallowed');
        $choices[0] = get_string('courseuploadlimit') . ' (' .
            display_size($COURSE->maxbytes) . ')';
        $mform->addElement('select', 'attachmentmaxbytes',
            get_string('attachmentmaxbytes', 'forumng'), $choices);
        $mform->setHelpButton('attachmentmaxbytes', array('attachmentmaxbytes',
            get_string('attachmentmaxbytes', 'forumng'), 'forumng'));
        $mform->setDefault('attachmentmaxbytes', $CFG->forumng_attachmentmaxbytes);

        //Email address for reporting unacceptable post for this forum, default is blank
        $mform->addElement('text', 'reportingemail', get_string('reportingemail', 'forumng'),
            array('size'=>48));
        $mform->setType('reportingemail', PARAM_NOTAGS);
        $mform->setHelpButton('reportingemail', array('reportingemail',
                get_string('reportingemail', 'forumng'), 'forumng'));
        // Atom/RSS feed on/off/discussions-only
        if ($CFG->enablerssfeeds && !empty($CFG->forumng_enablerssfeeds)) {
            if($CFG->forumng_feedtype == -1 || $CFG->forumng_feeditems == -1) {
                $mform->addElement('header', '', get_string('feeds', 'forumng'));
            }

            if($CFG->forumng_feedtype == -1) {
                $mform->addElement('select', 'feedtype',
                    get_string('feedtype', 'forumng'), forum::get_feedtype_options());
                $mform->setDefault('feedtype', forum::FEEDTYPE_ALL_POSTS);
                $mform->setHelpButton('feedtype', array('feedtype',
                    get_string('feedtype', 'forumng'), 'forumng'));
            }

            // Atom/RSS feed item count
            if ($CFG->forumng_feeditems == -1) {
                $mform->addElement('select', 'feeditems',
                    get_string('feeditems', 'forumng'), forum::get_feeditems_options());
                $mform->setDefault('feeditems', 20);
                $mform->setHelpButton('feeditems', array('feeditems',
                    get_string('feeditems', 'forumng'), 'forumng'));
            }
        }

        // Ratings header
        /////////////////

        $mform->addElement('header', '', get_string('ratings', 'forumng'));
        $mform->addElement('checkbox', 'enableratings', get_string('enableratings', 'forumng'));
        $mform->setHelpButton('enableratings', array('enableratings', get_string('enableratings', 'forumng'), 'forumng'));

        // Scale
        $mform->addElement('modgrade', 'ratingscale', get_string('scale'), null, true);
        $mform->disabledIf('ratingscale', 'enableratings', 'notchecked');
        $mform->setDefault('ratingscale', 5);

        // From/until times
        $mform->addElement('date_time_selector', 'ratingfrom', get_string('ratingfrom', 'forumng'), array('optional'=>true));
        $mform->disabledIf('ratingfrom', 'enableratings', 'notchecked');

        $mform->addElement('date_time_selector', 'ratinguntil', get_string('ratinguntil', 'forumng'), array('optional'=>true));
        $mform->disabledIf('ratinguntil', 'enableratings', 'notchecked');

        $mform->addElement('text', 'ratingthreshold',
            get_string('ratingthreshold', 'forumng'));
        $mform->setType('ratingthreshold', PARAM_INT);
        $mform->setDefault('ratingthreshold', 1);
        $mform->addRule('ratingthreshold',
            get_string('error_ratingthreshold', 'forumng'),
            'regex', '/[1-9][0-9]*/', 'client');
        $mform->setHelpButton('ratingthreshold', array('ratingthreshold',
            get_string('ratingthreshold', 'forumng'), 'forumng'));
        $mform->disabledIf('ratingthreshold', 'enableratings', 'notchecked');

        // Grading
        $mform->addElement('select', 'grading', get_string('grade'),
            forum::get_grading_options());
        $mform->setDefault('grading', forum::GRADING_NONE);
        $mform->setHelpButton('grading', array('grading', get_string('grade'), 'forumng'));
        $mform->disabledIf('grading', 'enableratings', 'notchecked');

        // Blocking header
        //////////////////

        $mform->addElement('header', '', get_string('limitposts', 'forumng'));

        // Post dates
        $mform->addElement('date_time_selector', 'postingfrom', get_string('postingfrom', 'forumng'), array('optional'=>true));
        $mform->addElement('date_time_selector', 'postinguntil', get_string('postinguntil', 'forumng'), array('optional'=>true));

        // User limits
        $limitgroup = array();
        $limitgroup[] = $mform->createElement(
            'checkbox', 'enablelimit', '');

        $options = forum::get_max_posts_period_options();

        $limitgroup[] = $mform->createElement('text', 'maxpostsblock',
            '', array('size'=>3));
        $limitgroup[] = $mform->createElement('static', 'staticthing', '',
            ' ' . get_string('postsper', 'forumng') . ' ');
        $limitgroup[] = $mform->createElement('select', 'maxpostsperiod',
            '', $options);

        $mform->addGroup($limitgroup, 'limitgroup',
            get_string('enablelimit', 'forumng'));

        $mform->disabledIf('limitgroup[maxpostsblock]', 'limitgroup[enablelimit]');
        $mform->disabledIf('limitgroup[maxpostsperiod]', 'limitgroup[enablelimit]');

        $mform->setHelpButton('limitgroup', array('enablelimit',
            get_string('enablelimit', 'forumng'),'forumng'));

        $mform->setType('limitgroup[maxpostsblock]', PARAM_INT);
        $mform->setDefault('limitgroup[maxpostsblock]', '10');

        // Remove old discussion
        $options = array();
        $options[0] = get_string('removeolddiscussionsdefault', 'forumng');
        for ($i = 1; $i <= 36; $i++) {
            $options[$i*2592000] = $i;
        }
        $mform->addElement('header', '', get_string('removeolddiscussions', 'forumng'));
        $mform->addElement('select', 'removeafter', get_string('removeolddiscussionsafter', 'forumng'), $options);
        $mform->setHelpButton('removeafter', array('removeolddiscussions',
            get_string('removeolddiscussions', 'forumng'), 'forumng'));

        $options = array();
        $options[0] = get_string('deletepermanently', 'forumng');
        $modinfo = get_fast_modinfo($COURSE);
        $targetforumid = $this->_instance ? $this->_instance : 0;
        // Add all in stances to drop down if the user can access them and it's not the same as the current forum
        if (array_key_exists('forumng', $modinfo->instances)) {
            foreach($modinfo->instances['forumng'] as $info) {
                if ($info->uservisible && $targetforumid != $info->instance) {
                    $options[$info->instance] = $info->name;
                }
            }
        }
        $mform->addElement('select', 'removeto', get_string('withremoveddiscussions', 'forumng'), $options);
        $mform->disabledIf('removeto', 'removeafter', 'eq', 0);
        $mform->setHelpButton('removeto', array('withremoveddiscussions',
            get_string('withremoveddiscussions', 'forumng'), 'forumng'));

        // Sharing options are advanced and for administrators only
        if ($CFG->forumng_enableadvanced && has_capability('moodle/site:config',
            $coursecontext)) {
            $mform->addElement('header', '', get_string('sharing', 'forumng'));
            $mform->addElement('advcheckbox', 'shared', get_string('shared', 'forumng'));
            $mform->setHelpButton('shared', array('shared', get_string('shared', 'forumng'), 'forumng'));

            // Only when creating a forum, you can choose to make it a clone
            if (!$this->_instance) {
                $sharegroup = array();
                $sharegroup[] = $mform->createElement('checkbox', 'useshared', '');
                $sharegroup[] = $mform->createElement('text', 'originalcmidnumber', '');
                $mform->addGroup($sharegroup, 'usesharedgroup', get_string('useshared', 'forumng'));
                $mform->disabledIf('usesharedgroup[originalcmidnumber]', 'usesharedgroup[useshared]', 'notchecked');
                $mform->setHelpButton('usesharedgroup', array('useshared', get_string('useshared', 'forumng'), 'forumng'));
            }
        }

        // Do definition that is shared with clone version of form
        $this->shared_definition_part($coursecontext);

        if (count(forum_utils::get_convertible_forums($COURSE)) > 0 && !$this->_instance) {
            $mform->addElement('static', '', '', '<div class="forumng-convertoffer">' .
                get_string('offerconvert', 'forumng', $CFG->wwwroot .
                '/mod/forumng/convert.php?course=' . $COURSE->id) . '</div>');
        }
    }

    private function shared_definition_part($coursecontext) {
        $mform = $this->_form;
        // Special case for horrible OpenLearn functionality
        if(class_exists('ouflags') && 
            has_capability('local/course:revisioneditor', $coursecontext, null, false)) {
            include_once(dirname(__FILE__).'/../../local/insitu/lib.php');
            oci_mod_setup_form($mform, $this);
        } else {
            // Standard behaviour
            $features = new stdClass;
            $features->groups = true;
            $features->groupings = true;
            $features->groupmembersonly = true;
            $this->standard_coursemodule_elements($features);
        }

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $COURSE, $CFG;
        $errors = parent::validation($data, $files);

        if (isset($data['limitgroup']['maxpostsblock']) &&
            !preg_match('/^[0-9]{1,9}$/', $data['limitgroup']['maxpostsblock'])) {
            $errors['limitgroup'] = get_string('err_numeric', 'form');
        }
        if (!empty($data['reportingemail']) && !validate_email($data['reportingemail'])) {
            $errors['reportingemail'] = get_string('invalidemail', 'forumng');
        }

        // If old discussions are set to be moved to another forum...
        $targetforumid = isset($data['removeto'])? $data['removeto'] : 0;
        $removeafter = isset($data['removeafter']) ? $data['removeafter'] : 0;
        if ($removeafter && $targetforumid) {
            $modinfo = get_fast_modinfo($COURSE);
            // Look for target forum
            if (!array_key_exists($targetforumid, $modinfo->instances['forumng'])) {
                $errors['removeto'] = get_string('errorinvalidforum', 'forumng');
            }
        }

        // If sharing is turned on, check requirements
        if (!empty($data['shared'])) {
            if (!empty($data['groupmode'])) {
                $errors['groupmode'] = get_string('error_notwhensharing', 'forumng');
            }
            if (!empty($data['grading'])) {
                $errors['grading'] = get_string('error_notwhensharing', 'forumng');
            }
            if (empty($data['cmidnumber'])) {
                $errors['cmidnumber'] = get_string('error_sharingrequiresidnumber', 'forumng');
            } else {
                // Check it's unique
                $cmid = isset($data['coursemodule']) ? (int)$data['coursemodule'] : 0;
                if (count_records_select('course_modules', "idnumber = '" .
                    addslashes($data['cmidnumber']) . "' AND id <> " . $cmid)) {
                    $errors['cmidnumber'] = get_string('error_sharingrequiresidnumber', 'forumng');
                }
            }
        } else if(isset($data['shared'])) {
            // They are trying to turn sharing off. You aren't allowed to do
            // this if there are existing references.
            $cmid = isset($data['coursemodule']) ? (int)$data['coursemodule'] : -1;
            if (count_records('forumng', 'originalcmid', $cmid)) {
                $errors['shared'] = get_string('error_sharinginuse', 'forumng');
            }
        }

        if (!empty($data['usesharedgroup']['useshared'])) {
            if (empty($data['usesharedgroup']['originalcmidnumber'])) {
                $errors['usesharedgroup'] = get_string('error_sharingidnumbernotfound', 'forumng');;
            } else {
                // Check we can find it
                if (!forum::get_shared_cm_from_idnumber(
                        $data['usesharedgroup']['originalcmidnumber'])) {
                    $errors['usesharedgroup'] = get_string('error_sharingidnumbernotfound', 'forumng');;
                }
            }
        }
        return $errors;
    }

    function data_preprocessing(&$data){
        if (!empty($data['ratingscale'])) {
            $data['enableratings'] = 1;
        } else {
            $data['enableratings'] = 0;
            $data['ratingscale'] = 5;
        }

        if (!empty($data['maxpostsperiod']) && !empty($data['maxpostsblock'])) {
            $data['limitgroup[enablelimit]'] = 1;
            $data['limitgroup[maxpostsperiod]'] = $data['maxpostsperiod'];
            $data['limitgroup[maxpostsblock]'] = $data['maxpostsblock'];
        } else {
            $data['limitgroup[enablelimit]'] = 0;
            $data['limitgroup[maxpostsperiod]'] = 60*60*24;
            $data['limitgroup[maxpostsblock]'] = 10;
       }

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $data['completiondiscussionsenabled']=
            !empty($data['completiondiscussions']) ? 1 : 0;
        if(empty($data['completiondiscussions'])) {
            $data['completiondiscussions']=1;
        }
        $data['completionrepliesenabled']=
            !empty($data['completionreplies']) ? 1 : 0;
        if(empty($data['completionreplies'])) {
            $data['completionreplies']=1;
        }
        $data['completionpostsenabled']=
            !empty($data['completionposts']) ? 1 : 0;
        if(empty($data['completionposts'])) {
            $data['completionposts']=1;
        }
    }

    function add_completion_rules() {
        $mform =& $this->_form;

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completionpostsenabled', '', get_string('completionposts','forumng'));
        $group[] =& $mform->createElement('text', 'completionposts', '', array('size'=>3));
        $mform->setType('completionposts',PARAM_INT);
        $mform->addGroup($group, 'completionpostsgroup', get_string('completionpostsgroup','forumng'), array(' '), false);
        $mform->setHelpButton('completionpostsgroup', array('completion', get_string('completionpostshelp', 'forumng'), 'forumng'));
        $mform->disabledIf('completionposts','completionpostsenabled','notchecked');

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completiondiscussionsenabled', '', get_string('completiondiscussions','forumng'));
        $group[] =& $mform->createElement('text', 'completiondiscussions', '', array('size'=>3));
        $mform->setType('completiondiscussions',PARAM_INT);
        $mform->addGroup($group, 'completiondiscussionsgroup', get_string('completiondiscussionsgroup','forumng'), array(' '), false);
        $mform->setHelpButton('completiondiscussionsgroup', array('completion', get_string('completiondiscussionshelp', 'forumng'), 'forumng'));
        $mform->disabledIf('completiondiscussions','completiondiscussionsenabled','notchecked');

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completionrepliesenabled', '', get_string('completionreplies','forumng'));
        $group[] =& $mform->createElement('text', 'completionreplies', '', array('size'=>3));
        $mform->setType('completionreplies',PARAM_INT);
        $mform->addGroup($group, 'completionrepliesgroup', get_string('completionrepliesgroup','forumng'), array(' '), false);
        $mform->setHelpButton('completionrepliesgroup', array('completion', get_string('completionreplieshelp', 'forumng'), 'forumng'));
        $mform->disabledIf('completionreplies','completionrepliesenabled','notchecked');

        return array('completiondiscussionsgroup','completionrepliesgroup','completionpostsgroup');
    }

    function completion_rule_enabled($data) {
        return (!empty($data['completiondiscussionsenabled']) && $data['completiondiscussions']!=0) ||
            (!empty($data['completionrepliesenabled']) && $data['completionreplies']!=0) ||
            (!empty($data['completionpostsenabled']) && $data['completionposts']!=0);
    }

    function get_data($slashed=true) {
        $data=parent::get_data($slashed);
        if(!$data) {
            return false;
        }

        // Set the reportingemail to null if empty so that they are consistency
        if (empty($data->reportingemail)) {
            $data->reportingemail = null;
        }
        // Set the removeto to null if the default option 'Delete permanently' was select
        if (empty($data->removeto)) {
            $data->removeto = null;
        }
        // Turn off ratings/limit if required
        if (empty($data->enableratings)) {
            $data->ratingscale = 0;
        }
        if (empty($data->limitgroup['enablelimit'])) {
            $data->maxpostsperiod = 0;
            $data->maxpostsblock = 0;
        } else {
            $data->maxpostsperiod = $data->limitgroup['maxpostsperiod'];
            $data->maxpostsblock = $data->limitgroup['maxpostsblock'];
        }

        // Turn off completion settings if the checkboxes aren't ticked
        $autocompletion=!empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
        if(empty($data->completiondiscussionsenabled) || !$autocompletion) {
            $data->completiondiscussions=0;
        }
        if(empty($data->completionrepliesenabled) || !$autocompletion) {
            $data->completionreplies=0;
        }
        if(empty($data->completionpostsenabled) || !$autocompletion) {
            $data->completionposts=0;
        }
        return $data;
    }

    function definition_after_data() {
        parent::definition_after_data();
        global $COURSE;
        $mform =& $this->_form;

        if ($this->clone) {
            $mform->removeElement('groupmode');
            return;
        }

        $targetforumid = $mform->getElementValue('removeto');
        $targetforumid = $targetforumid[0];
        $removeafter = $mform->getElementValue('removeafter');
        $removeafter = $removeafter[0];
        if ($removeafter && $targetforumid) {
            $modinfo = get_fast_modinfo($COURSE);
            if (!array_key_exists($targetforumid, $modinfo->instances['forumng'])) {
                $mform->getElement('removeto')->addOption(
                    get_string('invalidforum','forumng'), $targetforumid);
            }
        }
    }
}
?>
