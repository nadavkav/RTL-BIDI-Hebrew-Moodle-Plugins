<?php
/**
 * Defines the editing form for the dragdropr question type.
 *
 * @copyright &copy; 2007 Jamie Pratt
 * @author Jean-Michel Védrine vedrine@univ-st-etienne.fr
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 */


/**
 * dragdrop editing form definition.
 */
class question_edit_dragdrop_form extends question_edit_form {


    function definition_inner(&$mform) {
        global $COURSE, $CFG;

        $mform->removeElement('image');
        make_upload_directory($COURSE->id);    // Just in case
        $coursefiles = get_directory_list("$CFG->dataroot/$COURSE->id", $CFG->moddata);
        foreach ($coursefiles as $filename) {
            if (mimeinfo("icon", $filename) == "image.gif" || mimeinfo("icon", $filename) == "avi.gif" ||  mimeinfo("icon", $filename) == "flash.gif" || mimeinfo("icon", $filename) == "video.gif") {
                $images["$filename"] = $filename;
            }
        }
        if (empty($images)) {
            $mform->addElement('static', 'backgroundmedia', get_string('bkgdimage', 'qtype_dragdrop'), get_string('noimagesyet'));
        } else {
            $mform->addElement('select', 'backgroundmedia', get_string('bkgdimage', 'qtype_dragdrop'), array_merge(array(''=>get_string('none')), $images));
        }
        $mform->addRule('backgroundmedia', get_string('needbackground', 'qtype_dragdrop'), 'required', null, 'client');

        $creategrades = get_grade_options();
        $gradeoptions = $creategrades->gradeoptions;
        $mform->addElement('select', 'globalfeedbackgrade', get_string('feedbackmin', 'qtype_dragdrop'), $gradeoptions);
        $mform->setDefault('globalfeedbackgrade', 0.5);
        $mform->addRule('globalfeedbackgrade', null, 'required', null, 'client');

        $mform->addElement('htmleditor', 'feedbackok', get_string('feedbackok', 'qtype_dragdrop'),
                array('rows' => 10, 'course' => $this->coursefilesid));
        $mform->setType('feedbackok', PARAM_RAW);
        $mform->setHelpButton('feedbackok', array('feedbackok', get_string('feedbackok', 'qtype_dragdrop'), 'qtype_dragdrop'));

        $mform->addElement('htmleditor', 'feedbackmissed', get_string('feedbackmissed', 'qtype_dragdrop'),
                array('rows' => 10, 'course' => $this->coursefilesid));
        $mform->setType('feedbackmissed', PARAM_RAW);
        $mform->setHelpButton('feedbackmissed', array('feedbackmissed', get_string('feedbackmissed', 'qtype_dragdrop'), 'qtype_dragdrop'));

        $mform->addElement('hidden', 'arrange', 'false');
        $mform->setType('arrange', PARAM_ALPHA);

        // added by harry for layout - beginn
        $radioarray   = array();
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', get_string('onerow', 'qtype_dragdrop'), 0, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', get_string('onecolumn', 'qtype_dragdrop'), 1, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '2', 2, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '3', 3, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '4', 4, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '5', 5, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '6', 6, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '7', 7, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '8', 8, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '9', 9, '');
        $radioarray[] = $mform->createElement('radio', 'arrangemedia', '', '10', 10, '');
        $mform->addGroup($radioarray, 'radioar', get_string('arrangemedia', 'qtype_dragdrop'), array(' '), false);
        $mform->setDefault('arrangemedia', 0);

        $radioarray   = array();
        $radioarray[] = $mform->createElement('radio', 'placemedia', '', get_string('below', 'qtype_dragdrop'), 0, '');
        $radioarray[] = $mform->createElement('radio', 'placemedia', '', get_string('rightbeside', 'qtype_dragdrop'), 1, '');
        $mform->addGroup($radioarray, 'radioar2', get_string('placemedia', 'qtype_dragdrop'), array(' '), false);
        $mform->setDefault('placemedia', 0);
        // added by harry for layout - end


        /*
                $mform->addElement('hidden', 'sesskey', sesskey());
                $mform->addElement('hidden', 'id', $this->question->id);
                $mform->addElement('hidden', 'qtype', $this->question->qtype);
        $mform->setType('qtype', PARAM_ALPHA);
                $mform->addElement('hidden', 'courseid', $COURSE->id);

                $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', 0);
                $mform->addElement('hidden', 'returnurl', $qreturnurl);
        $mform->setType('returnurl', PARAM_LOCALURL);
        $mform->setDefault('returnurl', 0);*/

        $mform->addElement('static', 'answersinstruct', get_string('correctanswers', 'qtype_dragdrop'), get_string('filloutoneanswer', 'qtype_dragdrop'));
        $mform->closeHeaderBefore('answersinstruct');

        $repeated = array();
        $repeated[] =& $mform->createElement('header', 'answerhdr', get_string('dragdropno', 'qtype_dragdrop', '{no}'));
        if (empty($images)) {
            $repeated[] =& $mform->createElement('static', 'ddmedia', get_string('image', 'qtype_dragdrop'), get_string('noimagesyet'));
        } else {
            $repeated[] =& $mform->createElement('select', 'ddmedia', get_string('image', 'qtype_dragdrop'), array_merge(array(''=>get_string('none')), $images));
        }
        $repeated[] =& $mform->createElement('htmleditor', 'ddtext', get_string('text', 'qtype_dragdrop'),
        array('rows' => 6, 'course' => $this->coursefilesid));
        $repeated[] =& $mform->createElement('text', 'ddmediatargetx', get_string('imagepositionx', 'qtype_dragdrop'), array('size' => 4));
        $repeated[] =& $mform->createElement('text', 'ddmediatargety', get_string('imagepositiony', 'qtype_dragdrop'), array('size' => 4));
        $repeated[] =& $mform->createElement('text', 'ddmediadisplaywidth', get_string('imagepositionwidth', 'qtype_dragdrop'), array('size' => 4));
        $repeated[] =& $mform->createElement('text', 'ddmediadisplayheight', get_string('imagepositionheight', 'qtype_dragdrop'), array('size' => 4));
        $repeated[] =& $mform->createElement('text', 'ddhotspotx', get_string('hotspotx', 'qtype_dragdrop'), array('size' => 4));
        $repeated[] =& $mform->createElement('text', 'ddhotspoty', get_string('hotspoty', 'qtype_dragdrop'), array('size' => 4));
        $repeated[] =& $mform->createElement('text', 'ddhotspotwidth', get_string('hotspotwidth', 'qtype_dragdrop'), array('size' => 4));
        $repeated[] =& $mform->createElement('text', 'ddhotspotheight', get_string('hotspotheight', 'qtype_dragdrop'), array('size' => 4));
        $repeated[] =& $mform->createElement('text', 'althotspots', get_string('althotspots', 'qtype_dragdrop'), array('size' => 18));
        if (isset($this->question->options)){
            $countanswers = count($this->question->options->media);
        } else {
            $countanswers = 0;
        }
        $repeatsatstart = (QUESTION_NUMANS_START > ($countanswers + QUESTION_NUMANS_ADD))?
                            QUESTION_NUMANS_START : ($countanswers + QUESTION_NUMANS_ADD);
        $repeatedoptions = array();
        $mform->setType('ddtext', PARAM_RAW);
        $repeatedoptions['fraction']['default'] = 0;
        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions, 'noanswers', 'addanswers', QUESTION_NUMANS_ADD, get_string('addmoreanswerblanks', 'qtype_dragdrop'));

        $mybuttons = array();
        //javascript to put into the insert buttons to point to the text field
        $scriptattrs = 'onclick = "document.forms[\'mform1\'].arrange.value=\'true\'"';
        //construct the button to position the hotspots
        $mform->addElement('submit', 'hotspotsposition', get_string('hotspotspecify', 'qtype_dragdrop'),$scriptattrs);
    }


    function set_data($question) {
       if (isset($question->options)){
            // added by Harry - beginn
            $default_values['arrangemedia'] = $question->options->arrangemedia;
            $default_values['placemedia'] = $question->options->placemedia;
            // added by Harry - end
            $default_values['backgroundmedia'] = $question->options->backgroundmedia->media;
                        $default_values['globalfeedbackgrade'] = $question->options->feedbackfraction;
            $default_values['feedbackok'] = $question->options->feedbackok;
                        $default_values['feedbackmissed'] = $question->options->feedbackmissed;
            $oldmedia = $question->options->media;
            if (count($oldmedia)) {
                $key = 0;
                $dbmediaids = array();  // maps the db ids of the dragdrop objects to the html slots
                $dbhsids = array();  // maps the db ids of the primary hot spots to the html slots
                foreach ($oldmedia as $omedia){
                    $default_values['ddtext['.$key.']'] = $omedia->questiontext;
                    $default_values['ddmedia['.$key.']'] = $omedia->media;
                                        $default_values['ddmediatargetx['.$key.']']= $omedia->targetx;
                                        $default_values['ddmediatargety['.$key.']']= $omedia->targety;
                                        $default_values['ddmediadisplaywidth['.$key.']']= $omedia->displaywidth;
                                        $default_values['ddmediadisplayheight['.$key.']']= $omedia->displayheight;
                                        if ($omedia->primary_hotspot != 0) {
                        $hotspot = $omedia->hotspots[$omedia->primary_hotspot];
                                                $default_values['ddhotspotx['.$key.']']= $hotspot->x;
                                                $default_values['ddhotspoty['.$key.']']= $hotspot->y;
                                                $default_values['ddhotspotwidth['.$key.']']= $hotspot->width;
                                                $default_values['ddhotspotheight['.$key.']']= $hotspot->height;
                    } else {
                                                $default_values['ddhotspotgroup['.$key.'][ddhotspotx]']= 0;
                                                $default_values['ddhotspotgroup['.$key.'][ddhotspoty]']= 0;
                                                $default_values['ddhotspotgroup['.$key.'][ddhotspotwidth]']= 0;
                                                $default_values['ddhotspotgroup['.$key.'][ddhotspotheight]']= 0;
                    }
                                        $dbmediaids[$omedia->id] = $key+1;
                    $dbhsids[$omedia->primary_hotspot] = $key+1;
                    $key++;
                }
                // map the db ids to html "alternate hot spots" fields
                foreach($dbmediaids as $dbmediaid=>$htmlslot) {
                    if (count($oldmedia[$dbmediaid]->hotspots) > 1) {
                        $hotspotids = array();
                        foreach ($oldmedia[$dbmediaid]->hotspots as $id=>$hs) {
                            if ($id != $oldmedia[$dbmediaid]->primary_hotspot) {
                                $hotspotids [] = $dbhsids[$id];
                            }
                        }
                        $hotspotids = implode(',', $hotspotids);
                        $default_values['althotspots['.($htmlslot-1).']'] = $hotspotids;
                    }
                }
            }
            $question = (object)((array)$question + $default_values);
        }
        parent::set_data($question);
    }


    function validation($data, $files = null){
        $errors = parent::validation($data, $files);

        if (empty($data['backgroundmedia'])) {
            $errors['backgroundmedia'] = get_string('needbackground', 'qtype_dragdrop');
        }

        return $errors;
    }

    function qtype() {
        return 'dragdrop';
    }
}
?>
