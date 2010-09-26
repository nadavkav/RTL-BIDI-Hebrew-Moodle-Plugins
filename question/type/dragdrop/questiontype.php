<?php  // $Id: questiontype.php,v 1.10 2007/02/14 23:36:47 jmvedrine Exp $

/////////////
/// dragdrop ///
/////////////
/**
 * @author brian king brian@mediagonal.ch,free.as.in.speech@gmail.com
 * complete javascript rewrite by harry winkelmann harry ät winkelmaenner.de - june 2008
 */

//hw:
// done: when an image is dropped outside of the stage, force it back onto the stage (last position, if possible)
// done: hotspots can be resized via a handle at the bottom right

//bk: todo:
// when "snap hotspots to images" is clicked, if the image has been resized, and the hotspot was not yet positioned,
//    then the hotspot should be sized based on the current size of the image
// overlib.js, included from lib/javascript.php, causes problems for question display on IE. (search for overlib in javascript.php to see current hack fix)

//bk: feature wishlist:
// add right-click menu (makes the user interface for some of the other wishes easier to implement)
// add start positions (right-click menu)
// have drag-droppable text
// have toggleable id numbers at the top left of images and hotspots  (right-click menu)
// make the background moveable in the editor
// make the stage resizable
// when an image is dropped outside of the stage, force it back onto the stage (last position, if possible)
// have an option to make the images snap to a hotspot when dropped over one  (right now it's free-form)  (global, not per-image)
// move everything except the question text, penalty, feedback onto the drag-drop editor page

class drag_drop_object {
    var $x, $y, $width, $height;
    function drag_drop_object($x=null, $y=null, $width=null, $height=null) {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }
}

/// QUESTION TYPE CLASS //////////////////
class dragdrop_qtype extends default_questiontype {

    function name() {
        return 'dragdrop';
    }

    function get_question_options(&$question) {
        // Get additional information from database
        // and attach it to the question object
        if (!$question->options = get_record("question_dragdrop","questionid", $question->id)) {
            notify( "question_dragdrop $question->id record not found" );
            return false;
        }

        if (!$question->options->backgroundmedia = get_record("question_dragdrop_media", 'id', $question->options->backgroundmedia)) {
            notify( "question_dragdrop media record not found for question '$question->id'" );
            return false;
        }

        if (!$question->options->media = get_records_list("question_dragdrop_media","id",$question->options->dragdropmedia, 'id')) {
            notify( "question_dragdrop_media not found for question '$question->id'" );
            return false;
        }

        if (!$question->options->hotspots = get_records("question_dragdrop_hotspot","questionid",$question->id, 'id')) {
            notify( "question_dragdrop_sub $question->id not found" );
            return false;
        }

        $this->associate_media_hotspots($question->options->media, $question->options->hotspots);

        return true;
    }

    /**
    * Deletes question from the question-type specific tables
    *
    * @return boolean Success/Failure
    * @param integer $questionid
    */
    function delete_question($questionid) {
        delete_records("question_dragdrop", "questionid", $questionid);
        delete_records("question_dragdrop_media", "questionid", $questionid);
        delete_records("question_dragdrop_hotspot", "questionid", $questionid);
        return true;
    }

    // replaces the csv value in media->hotspots with the hotspot objects keyed by the ids in that field
    function associate_media_hotspots(&$mediaobjects, $hotspots) {

        foreach($mediaobjects as $key=>$media) {
            $hs = empty($media->hotspots) ? array() : explode(',', $media->hotspots);
            $mediaobjects[$key]->hotspots = array();
            foreach($hs as $id) {
                $mediaobjects[$key]->hotspots[$id] = $hotspots[$id];
            }
        }
    }

    /**
     * expands responses into an associative array
     *
     * @param array responses in string format like '171,323,123,433'
     * @return array of drag_drop_objects
     */
    function expand_responses($responses) {
        $expanded = array();
        foreach($responses as $key=>$response) {
            if (!empty($response)) {
                list($x,$y,$width,$height) = explode(',', $response);
                $expanded[$key] = new drag_drop_object($x,$y,$width,$height);
            }
        }
        return $expanded;
    }

    function restore_session_and_responses(&$question, &$state) {
        if ($state->responses[''] == '') {
            $state->responses = array();
        } else {
            $resp = explode('#', $state->responses['']);
            $responses = array();
            $resp = array_map(create_function('$val', 'return explode(":", $val);'), $resp);
            $state->responses = array();
            foreach($resp as $response) {
                $state->responses[$response[0]] = $response[1];
            }
        }
        return true;
    }

    function save_session_and_responses(&$question, &$state) {
        $delimiter = '#';
        $responses = array();
        foreach ($state->responses as $mediaid => $position) {
            if ($mediaid != '') {
                $responses[] = "$mediaid:$position";
            }
        }
        $responses = implode($delimiter, $responses);

        // Set the legacy answer field
        if (!set_field('question_states', 'answer', $responses, 'id', $state->id)) {
            return false;
        }
        return true;
    }

    // this is only called by external code - for example, preview.php, when it "fills with correct answers"
    // returns the responses set to their position as shown in the question editor
    function get_correct_responses(&$question, &$state) {
        $responses = array();
        foreach ($question->options->media as $item) {
            $responses[$item->id]="{$item->targetx},{$item->targety},{$item->displaywidth},{$item->displayheight}";
        }
        return empty($responses) ? null : $responses;
    }

    // overriding save_question - we need to redirect to arrange.php before going back to edit.php
    function save_question($question, $form, $course) {
        global $CFG, $COURSE;

        // save question as usual
        $question = parent::save_question($question, $form, $course);

        $params = array();
        $params['returnurl'] = $form->returnurl;
        $params['id'] = $question->id;
        $params['courseid'] = $COURSE->id;
                $params['cmid'] = $form->cmid;

        // figure out if the position hotspot submit button was clicked
        // if so, redirect to the arrange page
        if (isset($form->arrange) && $form->arrange == 'true') {
            $url = new moodle_url($CFG->wwwroot . '/question/type/dragdrop/arrange.php', $params);
            redirect($url->out());
        }

        return $question;
    }

    function save_question_options($question) {
        global $COURSE;
        $result = new stdClass;
        $courseid = $COURSE->id;
       // following hack to check at least one dragdrop object exists
        $answercount = 0;
        foreach ($question->ddmedia as $mediapath) {
            if (!empty($mediapath)) {
                $answercount++;
            }
        }
        if ($answercount < 1) { // check there is at least 1 dragdrop object
            $result->notice = get_string("notenoughanswers", "quiz", "1");
            return $result;
        }

        $backgroundmediaid = $this->set_background_media($question, $courseid);
        if (!empty($backgroundmediaid->error)) {
            return $backgroundmediaid;
        }

        if ($options = get_record("question_dragdrop", "questionid", $question->id)) {
            $questionexisted = true;
        } else {
            $questionexisted = false;
            unset($options);
            $options->questionid = $question->id;
        }
        $options->backgroundmedia = $backgroundmediaid;
        $options->feedbackfraction = $question->globalfeedbackgrade;
        $options->feedbackok = $question->feedbackok;
        $options->feedbackmissed = $question->feedbackmissed;
        // added by Harry - beginn
        $options->arrangemedia = $question->arrangemedia;
        $options->placemedia = $question->placemedia;
        // added by Harry - end
        $oldmediaids = empty($options->dragdropmedia) ? '0' : $options->dragdropmedia;

        $hotspots = $this->save_question_hotspots($question, $courseid);
        if (!empty($hotspots->error)) {
            return $hotspots;
        }

        $mediaids = $this->save_question_media($question, $oldmediaids, $hotspots, $courseid);
        if (!empty($media->error)) {
            return $media;
        }

        $options->dragdropmedia = implode(",",$mediaids);

        if ($questionexisted) {
            if (!update_record("question_dragdrop", $options)) {
                $result->error = "Could not update dragdrop main question! (id=$options->id)";
                return $result;
            }
        } else {
            if (!insert_record("question_dragdrop", $options)) {
                $result->error = "Could not insert dragdrop main question!";
                return $result;
            }
        }
        return true;
    }

    function save_question_hotspots($question, $courseid) {

        if (!$oldhotspots = get_records("question_dragdrop_hotspot", "questionid", $question->id, "id")) {
            $oldhotspots = array();
        }

        $hotspots = array();

        // prepare the hotspots insertion into the database
        $i = 1;
        foreach ($question->ddmedia as $key => $formmedia) {

            $formmedia = trim($formmedia);

            if (!empty($formmedia)) {
                $hotspot = array_shift($oldhotspots);
                if ($hotspot) {
                    $preexistentrecord = true; // Existing object, so reuse it
                } else {
                    $preexistentrecord = false;
                    unset($hotspot);
                    $hotspot->questionid = $question->id;
                }
                $hotspot->x = $question->ddhotspotx[$key];
                $hotspot->y = $question->ddhotspoty[$key];
                $hotspot->width = $question->ddhotspotwidth[$key];
                $hotspot->height = $question->ddhotspotheight[$key];
                if ($preexistentrecord) {
                    if (!update_record("question_dragdrop_hotspot", $hotspot)) {
                        $result->error = "Could not update dragdrop hotspot! (id=$hotspot->id)";
                        return $result;
                    }
                } else {
                    if (!$hotspot->id = insert_record("question_dragdrop_hotspot", $hotspot)) {
                        $result->error = "Could not insert dragdrop hotspot!";
                        return $result;
                    }
                }
                $hotspots[$i] = $hotspot;
            } else {
                $hotspots[$i] = null;
            }
            $i++;
        }

        // delete leftovers
        if (!empty($oldhotspots)) {
            foreach($oldhotspots as $oh) {
                delete_records('question_dragdrop_hotspot', 'id', $oh->id);
            }
        }

        return $hotspots;
    }

    function save_question_media($question, $oldmediaids, $hotspots, $courseid) {

        if (!$oldmedia = get_records_list("question_dragdrop_media", "id", $oldmediaids, "id ASC")) {
            $oldmedia = array();
        }

        $mediaids = array();

        // prepare the drag-drop gap image and text pairs for insertion into the database
        $i = 1;
        foreach ($question->ddmedia as $key => $formmedia) {

            $formmedia = trim($formmedia);

            if (!empty($formmedia)) {
                $media = array_shift($oldmedia);
                if ($media) {
                    $preexistentrecord = true; // Existing answer, so reuse it
                } else {
                    $preexistentrecord = false;
                    unset($media);
                    $media->questionid = $question->id;
                }
                $media->questiontext = $question->ddtext[$key];

                $media->media   = $formmedia;
                $media->alt   = '';
                $this->set_mediainfo($media, $courseid, $formmedia);
                $media->targetx = $question->ddmediatargetx[$key];
                $media->targety = $question->ddmediatargety[$key];
                $media->displaywidth = $question->ddmediadisplaywidth[$key];
                $media->displayheight = $question->ddmediadisplayheight[$key];

                if ($media->targetx != 0 || $media->targety != 0) {
                    $media->positioned = 1;
                }

                $media->primary_hotspot = empty($hotspots[$i]) ? 0 : $hotspots[$i]->id;
                $althotspots = trim($question->althotspots[$key]);
                if (empty($althotspots)) {
                    $media->hotspots = $media->primary_hotspot ? $media->primary_hotspot: '0';
                } else {
                    // get the real ids of the alternate hotspots
                    $althotspots = str_replace(" ", "", $althotspots);
                    $hslist = array();
                    $formhotspots = explode(',', $althotspots);
                    foreach ($formhotspots as $fhs) {
                        if (isset($hotspots[$fhs])) {
                            $hslist[] = $hotspots[$fhs]->id;
                        }
                    }
                    $hslist[] = $media->primary_hotspot;
                    $media->hotspots = implode(',', $hslist);
                }

                if ($preexistentrecord) {
                    if (!update_record("question_dragdrop_media", $media)) {
                        $result->error = "Could not update dragdrop media! (id=$media->id)";
                        return $result;
                    }
                } else {
                    if (!$media->id = insert_record("question_dragdrop_media", $media)) {
                        $result->error = "Could not insert dragdrop media!";
                        return $result;
                    }
                }
                $mediaids[$i] = $media->id;
                $i++;
            }
        }

        if (count($mediaids) < 1) {
            $result->noticeyesno = get_string("notenoughsubquestions", "quiz");
            return $result;
        }

        // delete leftovers
        if (!empty($oldmedia)) {
            foreach($oldmedia as $om) {
                delete_records('question_dragdrop_media', 'id', $om->id);
            }
        }
        return $mediaids;

    }

    function set_background_media($question, $courseid) {
        global $CFG;

        $sql = "select bg.* from {$CFG->prefix}question_dragdrop dd, {$CFG->prefix}question_dragdrop_media bg
                where dd.questionid = $question->id and bg.id = dd.backgroundmedia";

        if (!$background = get_record_sql($sql)) {
            $background = new stdClass();
            $background->questionid = $question->id;
            $existed = false;
        } else {
            $existed = true;
        }
        $background->media = $question->backgroundmedia;
        $background->alt = isset($question->backgroundalt) ? $question->backgroundalt : '';
                $background->questiontext = '';
        $this->set_mediainfo($background, $courseid);

        if ($existed) {
            if (!update_record("question_dragdrop_media", $background)) {
                $result->error = "Could not update dragdrop media! (id=$background->id)";
                return $result;
            }
        } else {
            if (!$background->id = insert_record("question_dragdrop_media", $background)) {
                $result->error = "Could not insert dragdrop background media!";
                return $result;
            }
        }

        return $background->id;
    }

    /**
     * sets mimetype, width, and height fields for the media object
     *
     * @param object $mediaobject (passed object will be changed)
     * @param string $courseid the course id
     * @param string $mediapath = null the media file url or local path (can be ommitted if this info is in $mediaobject->media)
     * @author brian@mediagonal.ch
     */
    function set_mediainfo(&$mediaobject, $courseid, $mediapath = null) {
        if (is_null($mediapath)) {
            if (isset($mediaobject->media)) {
                $mediapath = $mediaobject->media;
            } else {
                // can't do anything without a mediapath
                return;
            }
        }
        if (!empty($mediaobject)) {
            global $CFG;
            require_once($CFG->dirroot.'/question/format/qti2/qt_common.php');
            require_once($CFG->libdir.'/filelib.php');

            $mediaobject->mimetype = mimeinfo('type',$mediapath);

            // hw: workaround for bug (typo) in /question/format/qti2/qt_common.php
            // I opened a bugtracker because of this...
            $is_img = false;
            if (function_exists('is_image_by_extension'))
                $is_img = is_image_by_extension($mediapath);
            else
                $is_img = is_image_by_extentsion($mediapath);

            if ($is_img || is_sizable_multimedia($mediapath)) {
                $islocalfile = (substr(strtolower($mediapath), 0, 7) == 'http://') ? false : true;
                if ($islocalfile) {
                    $mediapath = "$CFG->dataroot/$courseid/$mediapath";
                }
                $dimensions = get_file_dimensions($mediapath);
                $mediaobject->width = $dimensions['x'];
                $mediaobject->height = $dimensions['y'];
            }
        }
    }

        // maps the db ids of the dragdrop objects to the html slots
    // maps the db ids of the primary hot spots to the html slots
function map_dragdrop_objects(&$question, &$htmlslots, $startslot) {
    $oldmedia = $question->options->media;
    if (count($oldmedia)) {
        $key = $startslot;
        $dbmediaids = array();
        $dbhsids = array();
        foreach ($oldmedia as $omedia){
            $htmlslots['ddtext['.$key.']'] = $omedia->questiontext;
            $htmlslots['ddmedia['.$key.']'] = $omedia->media;
            $htmlslots['ddmediatargetx['.$key.']']= $omedia->targetx;
            $htmlslots['ddmediatargety['.$key.']']= $omedia->targety;
            $htmlslots['ddmediadisplaywidth['.$key.']']= $omedia->displaywidth;
            $htmlslots['ddmediadisplayheight['.$key.']']= $omedia->displayheight;
                        $htmlslots['althotspots['.$key.']'] = '';

            if ($omedia->primary_hotspot != 0) {
                $hotspot = $omedia->hotspots[$omedia->primary_hotspot];
                $htmlslots['ddhotspotx['.$key.']']= $hotspot->x;
                $htmlslots['ddhotspoty['.$key.']']= $hotspot->y;
                $htmlslots['ddhotspotwidth['.$key.']']= $hotspot->width;
                $htmlslots['ddhotspotheight['.$key.']']= $hotspot->height;
            } else {
                $htmlslots['ddhotspotgroup['.$key.'][ddhotspotx]']= 0;
                $htmlslots['ddhotspotgroup['.$key.'][ddhotspoty]']= 0;
                $htmlslots['ddhotspotgroup['.$key.'][ddhotspotwidth]']= 0;
                $htmlslots['ddhotspotgroup['.$key.'][ddhotspotheight]']= 0;
            }
            $dbmediaids[$omedia->id] = $key;
            $dbhsids[$omedia->primary_hotspot] = $key;
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
                $htmlslots['althotspots['.($htmlslot).']'] = $hotspotids;
                    }
        }
    }
}

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;

        // global feedback
        if ($options->feedback) {
            if ($state->raw_grade >= $question->options->feedbackfraction * $question->maxgrade) {
                if (!empty($question->options->feedbackok)) {
                                    $feedback = $question->options->feedbackok;
                }
            } else if (!empty($question->options->feedbackmissed)) {
                            $feedback = $question->options->feedbackmissed;
            }
                        if ($feedback) {
                            echo '<table border="1" width="100%"><tr><td><b>';
                echo get_string('feedbackoverall', 'qtype_dragdrop') . ':</b> &nbsp;';
                echo $feedback;
                echo '</td></tr></table>';
                        }

        }
        // the actual rendering is handled by the dragdrop class
        include_once("{$CFG->dirroot}/question/type/dragdrop/dragdrop.php");
        $dd = new dragdrop($CFG, $question->id, $cmoptions->course, 0, 0, $options, $question, $state);
        $dd->display_question();
    }

    function grade_responses(&$question,  &$state, $cmoptions) {
        global $CFG;
        include_once("{$CFG->dirroot}/question/type/dragdrop/dragdrop.php");
        $state->raw_grade = 0.0;
        $mediaobjects = $question->options->media;

        $responses = $this->expand_responses($state->responses);
        $fraction = 1.0 / count($mediaobjects);
        /// Populate correctanswers arrays:
        foreach ($mediaobjects as $id=>$media) {
            if (!empty($responses[$id])) {
                foreach ($media->hotspots as $hotspot) {
                    if ($this->overlaps($responses[$id], $hotspot)) {
                        $state->raw_grade += $fraction;
                        continue;  // don't count multiple overlaps
                    }
                }
            }
        }

        $state->raw_grade = min(max((float) $state->raw_grade, 0.0), 1.0) * $question->maxgrade;
        // Apply the penalty for this attempt
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;

        return true;
    }

    function get_all_responses(&$question, &$state) {
        $result = new stdClass;
        // TODO
        return $result;
    }

    function get_actual_response($question, $state) {
        // TODO
        $responses = '';
        return $responses;
    }

    /**
     * determines if the two rectangular positions overlap
     *
     * @param object $dd1 an object with x, y, width and height properties
     * @param object $dd2 an object with x, y, width and height properties
     * @return boolean
     */
    function overlaps($dd1, $dd2) {
        /*if (empty($coordinates)) {
            return false;
        }*/

        // horizontally aligned?
        $horizontal = (($dd1->x >= $dd2->x && $dd1->x <= $dd2->x + $dd2->width)
                  || ($dd1->x + $dd1->width >= $dd2->x && $dd1->x + $dd1->width <= $dd2->x + $dd2->width)
                  || ($dd1->x < $dd2->x && $dd1->x + $dd1->width > $dd2->x + $dd2->width));
        // vertically aligned?
        $vertical = (($dd1->y >= $dd2->y && $dd1->y <= $dd2->y + $dd2->height)
                  ||($dd1->y + $dd1->height >= $dd2->y && $dd1->y + $dd1->height <= $dd2->y + $dd2->height)
                  || ($dd1->y < $dd2->y && $dd1->y + $dd1->height > $dd2->y + $dd2->height));

        return ($horizontal && $vertical);
    }

/// BACKUP FUNCTIONS ////////////////////////////

    /*
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf,$preferences,$question,$level=6) {
        $status = true;

        $dragdrops = get_records("question_dragdrop","questionid",$question,"id");
        //If there are dragdrops
        if ($dragdrops) {
            //Iterate over each dragdrop
            foreach ($dragdrops as $dragdrop) {
                $status = fwrite ($bf,start_tag("DRAGDROP",$level,true));
                fwrite ($bf,full_tag("ID",$level+1,false,$dragdrop->id));
                fwrite ($bf,full_tag("QUESTIONID",$level+1,false,$dragdrop->questionid));
                fwrite ($bf,full_tag("BACKGROUNDMEDIA",$level+1,false,$dragdrop->backgroundmedia));
                //fwrite ($bf,full_tag("DRAGDROPMEDIA",$level+1,false,$dragdrop->dragdropmedia));
                
                $medias = get_records_list("question_dragdrop_media","id",$dragdrop->dragdropmedia,"id");
                if ($medias) {
                    $status = $status && fwrite ($bf,start_tag("DRAGDROPMEDIAS",$level+1,true));
                    //Iterate over each dragdrop
                    foreach ($medias as $media) {
                        $status = fwrite ($bf,start_tag("DRAGDROPMEDIA",$level+2,true));
                        fwrite ($bf,full_tag("ID",$level+3,false,$media->id));
                        fwrite ($bf,full_tag("QUESTIONID",$level+3,false,$media->questionid));
                        fwrite ($bf,full_tag("QUESTIONTEXT",$level+3,false,$media->questiontext));
                        fwrite ($bf,full_tag("MEDIA",$level+3,false,$media->media));
                        fwrite ($bf,full_tag("ALT",$level+3,false,$media->alt));
                        fwrite ($bf,full_tag("WIDTH",$level+3,false,$media->width));
                        fwrite ($bf,full_tag("HEIGHT",$level+3,false,$media->height));
                        fwrite ($bf,full_tag("MIMETYPE",$level+3,false,$media->mimetype));
                        fwrite ($bf,full_tag("TARGETX",$level+3,false,$media->targetx));
                        fwrite ($bf,full_tag("TARGETY",$level+3,false,$media->targety));
                        fwrite ($bf,full_tag("DISPLAYWIDTH",$level+3,false,$media->displaywidth));
                        fwrite ($bf,full_tag("DISPLAYHEIGHT",$level+3,false,$media->displayheight));
                        fwrite ($bf,full_tag("POSITIONED",$level+3,false,$media->positioned));
                        //fwrite ($bf,full_tag("HOTSPOTS",$level+3,false,$media->hotspots));
                        //each media has one hotspot at least
                        $hotspots = get_records_list('question_dragdrop_hotspot', 'id', $media->hotspots);
                        $status = $status && fwrite ($bf,start_tag("HOTSPOTS",$level+3,true));
                        foreach ($hotspots as $hotspot) {
                            $status = $status && fwrite ($bf,start_tag("HOTSPOT",$level+4,true));
                            fwrite ($bf,full_tag("ID",$level+4,false,$hotspot->id));
                            fwrite ($bf,full_tag("QUESTIONID",$level+4,false,$hotspot->questionid));
                            fwrite ($bf,full_tag("X",$level+4,false,$hotspot->x));
                            fwrite ($bf,full_tag("Y",$level+4,false,$hotspot->y));
                            fwrite ($bf,full_tag("WIDTH",$level+4,false,$hotspot->width));
                            fwrite ($bf,full_tag("HEIGHT",$level+4,false,$hotspot->height));
                            $status = $status && fwrite ($bf,end_tag("HOTSPOT",$level+2,true));
                        }
                        $status = $status && fwrite ($bf,end_tag("HOTSPOTS",$level+3,true));
                        fwrite ($bf,full_tag("PRIMARY_HOTSPOT",$level+3,false,$media->primary_hotspot));
                        $status = fwrite ($bf,end_tag("DRAGDROPMEDIA",$level+2,true));
                    }
                    $status = $status && fwrite ($bf,end_tag("DRAGDROPMEDIAS",$level+1,true));
                }
                
                
                fwrite ($bf,full_tag("FREESTYLE",$level+1,false,$dragdrop->freestyle));
                fwrite ($bf,full_tag("FEEDBACKFRACTION",$level+1,false,$dragdrop->feedbackfraction));
                fwrite ($bf,full_tag("FEEDBACKOK",$level+1,false,$dragdrop->feedbackok));
                fwrite ($bf,full_tag("FEEDBACKMISSED",$level+1,false,$dragdrop->feedbackmissed));
                fwrite ($bf,full_tag("ARRANGEMEDIA",$level+1,false,$dragdrop->arrangemedia));
                fwrite ($bf,full_tag("PLACEMEDIA",$level+1,false,$dragdrop->placemedia));
                //Print dragdrop contents
                //first the backgroundmedia
                $status = $status && fwrite ($bf,start_tag("BACKGROUND",$level+1,true));
                $backgroundmedia = get_record("question_dragdrop_media","id",$dragdrop->backgroundmedia);
                if ($backgroundmedia) {
                    fwrite ($bf,full_tag("ID",$level+2,false,$backgroundmedia->id));
                    fwrite ($bf,full_tag("QUESTIONID",$level+2,false,$backgroundmedia->questionid));
                    fwrite ($bf,full_tag("QUESTIONTEXT",$level+2,false,$backgroundmedia->questiontext));
                    fwrite ($bf,full_tag("MEDIA",$level+2,false,$backgroundmedia->media));
                    fwrite ($bf,full_tag("ALT",$level+2,false,$backgroundmedia->alt));
                    fwrite ($bf,full_tag("WIDTH",$level+2,false,$backgroundmedia->width));
                    fwrite ($bf,full_tag("HEIGHT",$level+2,false,$backgroundmedia->height));
                    fwrite ($bf,full_tag("MIMETYPE",$level+2,false,$backgroundmedia->mimetype));
                }
                $status = $status && fwrite ($bf,end_tag("BACKGROUND",$level+1,true));
                $status = $status && fwrite ($bf,end_tag("DRAGDROP",$level,true));
            }

            //Now print question_answers
            $status = question_backup_answers($bf,$preferences,$question);
        }
        return $status;
    }

/// RESTORE FUNCTIONS /////////////////

    /*
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($oldquestionid, $newquestionid, $info, $restore) {
        $status = true;

        // Get the dragdrops array
        $dragdrop_info = $info['#']['DRAGDROP'];
        $oldquestid = backup_todb($dragdrop_info['0']['#']['QUESTIONID']['0']['#']);
        
        $new_dragdrop = new stdClass;
        $new_dragdrop->questionid = $newquestionid;

        //now we get the backgroundmedia
        $background = $dragdrop_info['0']['#']['BACKGROUND'];
        $new_background = new object();
        $new_background->id = '';
        $new_background->questionid = $newquestionid;
        $new_background->questiontext = backup_todb($background['0']['#']['QUESTIONTEXT']['0']['#']);
        $new_background->media = backup_todb($background['0']['#']['MEDIA']['0']['#']);
        $new_background->alt = backup_todb($background['0']['#']['ALT']['0']['#']);
        $new_background->width = backup_todb($background['0']['#']['WIDTH']['0']['#']);
        $new_background->height = backup_todb($background['0']['#']['HEIGHT']['0']['#']);
        $new_background->mimetype = backup_todb($background['0']['#']['MIMETYPE']['0']['#']);
        $new_background->targetx = 0;
        $new_background->targety = 0;
        $new_background->displaywidth = 0;
        $new_background->displayheight = 0;
        $new_background->positioned = 0;
        $new_background->hotspots = 0;
        $new_background->primary_hotspot = 0;
        
        $new_background->id = insert_record('question_dragdrop_media', $new_background);
        
        //set the new backgroundmediaid into the new dragdrop
        $new_dragdrop->backgroundmedia = $new_background->id;

        //now we get the dragdropmedia and collect the new mediaids
        $dragdropmedia_info = $dragdrop_info['0']['#']['DRAGDROPMEDIAS']['0']['#']['DRAGDROPMEDIA'];
        //each dragdrop has one ddmedia at least
        $ddmedia_string = ''; //now we collect all ddmedia
        foreach($dragdropmedia_info as $ddmedia) {
            $new_ddmedia = new object();

            $new_ddmedia->questionid = $newquestionid;
            $new_ddmedia->questiontext = backup_todb($ddmedia['#']['QUESTIONTEXT']['0']['#']);
            $new_ddmedia->media = backup_todb($ddmedia['#']['MEDIA']['0']['#']);
            $new_ddmedia->alt = backup_todb($ddmedia['#']['ALT']['0']['#']);
            $new_ddmedia->width = backup_todb($ddmedia['#']['WIDTH']['0']['#']);
            $new_ddmedia->height = backup_todb($ddmedia['#']['HEIGHT']['0']['#']);
            $new_ddmedia->mimetype = backup_todb($ddmedia['#']['MIMETYPE']['0']['#']);
            $new_ddmedia->targetx = backup_todb($ddmedia['#']['TARGETX']['0']['#']);
            $new_ddmedia->targety = backup_todb($ddmedia['#']['TARGETY']['0']['#']);
            $new_ddmedia->displaywidth = backup_todb($ddmedia['#']['DISPLAYWIDTH']['0']['#']);
            $new_ddmedia->displayheight = backup_todb($ddmedia['#']['DISPLAYHEIGHT']['0']['#']);
            $new_ddmedia->positioned = backup_todb($ddmedia['#']['POSITIONED']['0']['#']);
            //each media has one hotspot at least
            $hotspots_info = $ddmedia['#']['HOTSPOTS']['0']['#']['HOTSPOT'];
            $hotspots_string = '';
            $old_primary_hotspot = backup_todb($ddmedia['#']['PRIMARY_HOTSPOT']['0']['#']);
            $new_primary_hotspot = '';
            foreach($hotspots_info as $hotspot) {
                $new_hotspot = new object();
                
                $new_hotspot->questionid = $newquestionid;
                $new_hotspot->x = backup_todb($hotspot['#']['X']['0']['#']);
                $new_hotspot->y = backup_todb($hotspot['#']['Y']['0']['#']);
                $new_hotspot->width = backup_todb($hotspot['#']['WIDTH']['0']['#']);
                $new_hotspot->height = backup_todb($hotspot['#']['HEIGHT']['0']['#']);
                
                $new_hotspot->id = insert_record('question_dragdrop_hotspot', $new_hotspot);
                $hotspots_string .= $new_hotspot->id.',';
                if(backup_todb($hotspot['#']['ID']['0']['#']) == $old_primary_hotspot) {
                    $new_primary_hotspot = $new_hotspot->id;
                }
            }
            $new_ddmedia->hotspots = trim($hotspots_string, ',');
            $new_ddmedia->primary_hotspot = $new_primary_hotspot;
            
            $new_ddmedia->id = insert_record('question_dragdrop_media', $new_ddmedia);
            $ddmedia_string .= $new_ddmedia->id.',';
        }
        
        $new_dragdrop->dragdropmedia = trim($ddmedia_string, ',');
        $new_dragdrop->freestyle = backup_todb($dragdrop_info['0']['#']['FREESTYLE']['0']['#']);
        $new_dragdrop->feedbackfraction = backup_todb($dragdrop_info['0']['#']['FEEDBACKFRACTION']['0']['#']);
        $new_dragdrop->feedbackok = backup_todb($dragdrop_info['0']['#']['FEEDBACKOK']['0']['#']);
        $new_dragdrop->feedbackmissed = backup_todb($dragdrop_info['0']['#']['FEEDBACKMISSED']['0']['#']);
        $new_dragdrop->arrangemedia = backup_todb($dragdrop_info['0']['#']['ARRANGEMEDIA']['0']['#']);
        $new_dragdrop->placemedia = backup_todb($dragdrop_info['0']['#']['PLACEMEDIA']['0']['#']);
        
        $status = insert_record('question_dragdrop', $new_dragdrop);
        //some output
        $question = get_record('question', 'id', $newquestionid);
        echo '<ul><li>'.get_string('dragdrop','qtype_dragdrop').': '.$question->name.'</li></ul>';
        backup_flush(300);
        return $status;
    }

    /**
     * Decode links in question type specific tables.
     * @return bool success or failure.
     */
    function decode_content_links_caller($questionids, $restore, &$i) {
        $status = true;

        // Decode links in the dragdrop and dragdrop_media tables.
                if ($dragdrops = get_records_list('question_dragdrop', 'questionid',
                implode(',',  $questionids), '', 'id, feedbackok, feedbackmissed')) {

            foreach ($dragdrops as $dragdrop) {
                $feedbackok = restore_decode_content_links_worker($dragdrop->feedbackok, $restore);
                                $feedbackmissed = restore_decode_content_links_worker($dragdrop->feedbackmissed, $restore);
                if ($feedbackok != $dragdrop->feedbackok || $feedbackmissed != $dragdrop->feedbackmissed) {
                    $dragdrop->feedbackok = addslashes($feedbackok);
                                        $dragdrop->feedbackmissed = addslashes($feedbackmissed);
                    if (!update_record('question_dragdrop', $dragdrop)) {
                        $status = false;
                    }
                }
            }
        }
        if ($medias = get_records_list('question_dragdrop_media', 'questionid',
                implode(',',  $questionids), '', 'id, questiontext')) {

            foreach ($medias as $media) {
                $questiontext = restore_decode_content_links_worker($media->questiontext, $restore);
                if ($questiontext != $media->questiontext) {
                    $media->questiontext = addslashes($questiontext);
                    if (!update_record('question_dragdrop_media', $media)) {
                        $status = false;
                    }
                }

                // Do some output.
                if (++$i % 5 == 0 && !defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if ($i % 100 == 0) {
                        echo "<br />";
                    }
                    flush(300);
                }
            }
        }

        return $status;
    }

    /*
     * Find all course / site files linked from a question.
     * As DragDrop has a lot of fields containig html
     * We needs to override this method
     *
     * @param string html the html to search
     * @param int courseid search for files for courseid course or set to siteid for
     *              finding site files.
     * @return array of url, relative url is key and array with one item = question id as value
     *                  relative url is relative to course/site files directory root.
     */
    function find_file_links($question, $courseid){
        $urls = array();
        if ($question->options->backgroundmedia->media != ''){
            if (substr(strtolower($question->options->backgroundmedia->media), 0, 7) == 'http://') {
                $matches = array();

                //support for older questions where we have a complete url in image field
                if (preg_match('!^'.question_file_links_base_url($courseid).'(.*)!i', $question->options->backgroundmedia->media, $matches)){
                    if ($cleanedurl = question_url_check($urls[$matches[2]])){
                        $urls[$cleanedurl] = null;
                    }
                }
            } else {
                if ($question->options->backgroundmedia->media != ''){
                    if ($cleanedurl = question_url_check($question->options->backgroundmedia->media)){
                        $urls[$cleanedurl] = null;//will be set later
                    }
                }

            }

        }
                foreach ($question->options->media as $media) {
                    if ($media->media != ''){
                            if (substr(strtolower($media->media), 0, 7) == 'http://') {
                                    $matches = array();
                                        if (preg_match('!^'.question_file_links_base_url($courseid).'(.*)!i', $media->media, $matches)){
                                            if ($cleanedurl = question_url_check($urls[$matches[2]])){
                            $urls[$cleanedurl] = null;
                        }
                    }
                                } else {
                                    if ($media->media != ''){
                                            if ($cleanedurl = question_url_check($media->media)){
                            $urls[$cleanedurl] = null;//will be set later
                        }
                    }

                }
                        }
                        $urls += question_find_file_links_from_html($media->questiontext, $courseid);
        }

        $urls += question_find_file_links_from_html($question->options->feedbackok, $courseid);
        $urls += question_find_file_links_from_html($question->options->feedbackmissed, $courseid);
        //set all the values of the array to the question object
        if ($urls){
            $urls = array_combine(array_keys($urls), array_fill(0, count($urls), array($question->id)));
        }
        $urls = array_merge_recursive($urls, parent::find_file_links($question, $courseid));
        return $urls;
    }
    /*
     * Find all course / site files linked from a question.
     *
     * As DragDrop has a lot of fields containig html
     * We needs to override this method
     *
     * @param string html the html to search
     * @param int course search for files for courseid course or set to siteid for
     *              finding site files.
     * @return array of files, file name is key and array with one item = question id as value
     */
    function replace_file_links($question, $fromcourseid, $tocourseid, $url, $destination){
        global $CFG;
        parent::replace_file_links($question, $fromcourseid, $tocourseid, $url, $destination);
        $optionschanged = false;
        if (!empty($question->options->backgroundmedia->media)){
            //support for older questions where we have a complete url in image field
            if (substr(strtolower($question->options->backgroundmedia->media), 0, 7) == 'http://') {
                $questionbackground = preg_replace('!^'.question_file_links_base_url($fromcourseid).preg_quote($url, '!').'$!i', $destination, $question->options->backgroundmedia->media, 1);
            } else {
                $questionbackground = preg_replace('!^'.preg_quote($url, '!').'$!i', $destination, $question->options->backgroundmedia->media, 1);
            }
            if ($questionbackground != $question->options->backgroundmedia->media){
                $question->options->backgroundmedia->media = $questionbackground;
                $optionschanged = true;
            }
        }
        $question->options->feedbackok = question_replace_file_links_in_html($question->options->feedbackok, $fromcourseid, $tocourseid, $url, $destination, $optionschanged);
        $question->options->feedbackmissed = question_replace_file_links_in_html($question->options->feedbackmissed, $fromcourseid, $tocourseid, $url, $destination, $optionschanged);
        if ($optionschanged){
            if (!update_record('question_dragdrop', addslashes_recursive($question->options))){
                error('Couldn\'t update \'question_dragdrop\' record '.$question->options->id);
            }
        }

                $mediachanged = false;
        foreach ($question->options->media as $media) {
                    if (!empty($media->media)){
                            if (substr(strtolower($media->media), 0, 7) == 'http://') {
                                    $medianame = preg_replace('!^'.question_file_links_base_url($fromcourseid).preg_quote($url, '!').'$!i', $destination, $media->media, 1);
                                } else {
                                    $medianame = preg_replace('!^'.preg_quote($url, '!').'$!i', $destination, $media->media, 1);
                                }
                                if ($medianame != $media->media){
                                    $media->media = $medianame;
                                        $mediachanged = true;
                                }
                        }
            $media->questiontext = question_replace_file_links_in_html($media->questiontext, $fromcourseid, $tocourseid, $url, $destination, $mediachanged);
            if ($mediachanged){
                if (!update_record('question_dragdrop_media', addslashes_recursive($media))){
                    error('Couldn\'t update \'question_dragdrop_media\' record '.$media->id);
                }
            }
        }
    }


    function import_from_xml($data, $question, $format, $extra=null) {
        if (!array_key_exists('@', $data)) {
            return false;
        }
        if (!array_key_exists('type', $data['@'])) {
            return false;
        }
        if ($data['@']['type'] == 'dragdrop') {
            $question = $format->import_headers($data);

            // header parts particular to image click
                        // function getpath( $xml, $path, $default, $istext=false, $error='' ) {
            $question->qtype = 'dragdrop';
                        $question->globalfeedbackgrade = $format->getpath($data, array('#', 'feedbackfraction', 0, '#'), 0);
                        $question->backgroundmedia = $format->getpath($data, array('#', 'backgroundmedia', 0, '#'), 0);
                        $background_image = $format->getpath($data, array('#', 'backgroundmedia', 0, '#'), 0);
                        $background_image_base64 = $format->getpath( $data, array('#','image_base64','0','#'),'' );
            if (!empty($background_image_base64)) {
                    $question->backgroundmedia = $format->importimagefile( $background_image, stripslashes($background_image_base64) );
            }
                        $question->feedbackok = $format->getpath($data, array('#', 'feedbackok',0, '#','text',0,'#'), '', true);
                    $question->feedbackmissed = $format->getpath($data, array('#', 'feedbackmissed',0, '#','text',0,'#'), '', true);

            $medias = $data['#']['media'];
            $mcount = 0;
            foreach ($medias as $media) {
                $question->ddmedia[$mcount] = $format->getpath( $media, array('#','text',0,'#'), '', true);
                                $media_image = $format->getpath( $media, array('#','text',0,'#'), '', true);
                                $media_image_base64 = $format->getpath( $media, array('#','image_base64','0','#'),'' );
                if (!empty($media_image_base64)) {
                    $question->ddmedia[$mcount] = $format->importimagefile( $media_image, stripslashes($media_image_base64) );
                }
                                $question->ddtext[$mcount] = $format->getpath( $media, array('#','questiontext',0,'#','text',0,'#'), '', true);
                                $question->ddmediatargetx[$mcount] = $format->getpath( $media, array('#','targetx',0,'#'),0 );
                                $question->ddmediatargety[$mcount] = $format->getpath( $media, array('#','targety',0,'#'),0 );
                                $question->ddmediadisplaywidth[$mcount] = $format->getpath( $media, array('#','displaywidth',0,'#'),0 );
                                $question->ddmediadisplayheight[$mcount] = $format->getpath( $media, array('#','displayheight',0,'#'),0 );
                                $question->althotspots[$mcount] = $format->getpath( $media, array('#','althotspot',0,'#'),0 );
                $question->ddhotspotx[$mcount] = $format->getpath( $media, array('#','hotspotx',0,'#'),0 );
                                $question->ddhotspoty[$mcount] = $format->getpath( $media, array('#','hotspoty',0,'#'),0 );
                                $question->ddhotspotwidth[$mcount] = $format->getpath( $media, array('#','hotspotwidth',0,'#'),0 );
                                $question->ddhotspotheight[$mcount] = $format->getpath( $media, array('#','hotspotheight',0,'#'),0 );
                $mcount++;
            }
            return $question;
        }

        return false;
    }

    function export_to_xml($question, $format, $extra=null) {

        $expout = '';
        $expout .= "    <backgroundmedia>{$question->options->backgroundmedia->media}</backgroundmedia>\n ";
                $expout .= $format->writeimage($question->options->backgroundmedia->media);
                $expout .= "    <feedbackfraction>{$question->options->feedbackfraction}</feedbackfraction>\n ";
                $expout .= "    <feedbackok>\n";
                $expout .= $format->writetext( $question->options->feedbackok,4,false );
                $expout .= "    </feedbackok>\n";
                $expout .= "    <feedbackmissed>\n";
                $expout .= $format->writetext( $question->options->feedbackmissed,4,false );
                $expout .= "    </feedbackmissed>\n";
                $questionmedias = array();
                $this->map_dragdrop_objects($question,$questionmedias ,1 );
        for ($key=1; $key<=count($question->options->media); $key++) {
            $expout .= "    <media id=\"$key\">\n";
            $expout .= $format->writetext( $questionmedias['ddmedia['.$key.']'],3,false );
                        $expout .= $format->writeimage($questionmedias['ddmedia['.$key.']']);
            $expout .= "      <questiontext>\n";
            $expout .= $format->writetext( $questionmedias['ddtext['.$key.']'],4,false );
            $expout .= "      </questiontext>\n";
            $expout .= "      <targetx>{$questionmedias['ddmediatargetx['.$key.']']}</targetx>\n ";
            $expout .= "      <targety>{$questionmedias['ddmediatargety['.$key.']']}</targety>\n ";
            $expout .= "      <displaywidth>{$questionmedias['ddmediadisplaywidth['.$key.']']}</displaywidth>\n ";
            $expout .= "      <displayheight>{$questionmedias['ddmediadisplayheight['.$key.']']}</displayheight>\n ";
            $expout .= "      <althotspot>{$questionmedias['althotspots['.$key.']']}</althotspot>\n ";
            $expout .= "      <hotspotx>{$questionmedias['ddhotspotx['.$key.']']}</hotspotx>\n ";
            $expout .= "      <hotspoty>{$questionmedias['ddhotspoty['.$key.']']}</hotspoty>\n ";
            $expout .= "      <hotspotwidth>{$questionmedias['ddhotspotwidth['.$key.']']}</hotspotwidth>\n ";
            $expout .= "      <hotspotheight>{$questionmedias['ddhotspotheight['.$key.']']}</hotspotheight>\n ";
            $expout .= "    </media>\n";
        }
        return $expout;
    }

}
//// END OF CLASS ////

//////////////////////////////////////////////////////////////////////////
//// INITIATION - Without this line the question type is not in use... ///
//////////////////////////////////////////////////////////////////////////
question_register_questiontype(new dragdrop_qtype());
?>