<?php
/**
 * @author brian king brian@mediagonal.ch,free.as.in.speech@gmail.com
 * complete javascript rewrite by harry winkelmann harry �t winkelmaenner.de - june 2008
 */

//hw: june 2008
// the new version uses moodles yui javascript libs.
// moodles yui libs are version 2.3.0 in moodleversion 1.9
// yui libs from YAHOO are 2.5.2 in june 2008 and are different to the old ones in moodle.
// so when moodle updates his yui libs there will be update work at the drag and drop questiontype.

//hw: june 2008
// the arrange.tpl for hotspot positioning and resizing uses his own yui 2.5.2 libs.
// but I wrote a little peace of code, which should be aware of moodles yui update and uses moodles yui libs if possible.

class dragdrop {
    var $cfg;  // moodle $CFG
    var $id;
    var $courseid;
    var $cmid;
    var $returnurl;
    var $question;
    var $options;
    var $readonly;
    var $responses;
    var $dragdropqcount;
    var $dragdropjs;
    var $ddqtype;

    function dragdrop($cfg, $id, $courseid, $cmid =null, $returnurl=null, $options = null, $question=null, $state=null)
    {

        global $SESSION;
        global $QTYPES;
        $this->ddqtype  = $QTYPES['dragdrop'];
        $this->cfg      = $cfg;
        $this->id       = $id;
        $this->options  = $options;
        $this->courseid = $courseid;
        $this->cmid     = $cmid;
        $this->returnurl = $returnurl;
        $this->readonly = !empty($options->readonly) ? $options->readonly : '';
        if (empty($state->responses)) {
            $this->responses = array();
        } else {
            $this->responses = $this->ddqtype->expand_responses($state->responses);
        }

        // sanity checking
        if (is_null($question)) {
            if (!$this->question = get_record("question", "id", $id)) {
                print_error('questiondoesnotexist', 'question', $returnurl);
            }
            $this->ddqtype->get_question_options($this->question);
        } else {
            $this->question = $question;
        }

        $this->dragdropqcount = 1;
    }


    function edit_positions()
    {
        $this->display('edit');
    }

    function display_question()
    {
        $displaytype = (empty($this->readonly) ? 'display' : 'displayro');
        $this->display($displaytype);
    }

    // modifies $this->responses to reflect whether or not the responses are accepted
    function evaluate_responses($mediaobjects) {
        if (!empty($this->responses)) {
            foreach ($this->responses as $key=>$response) {
                if ($key !== '') {
                    foreach ($mediaobjects[$key]->hotspots as $hotspot) {
                        if ($this->responses[$key]->accepted = $this->ddqtype->overlaps($response, $hotspot)) {
                            break;  // one match found; that's enough
                        }
                    }
                }
            }
        }
    }

    function get_div_style($isreview, $responses, $media) {
        if (!empty($this->options->feedback) && !empty($this->options->correct_responses)) {
            if (isset($responses[$media->id]) && $responses[$media->id]->accepted) {
                $specs = 'border:2px solid green;';
            } else {
                $specs = 'border:2px solid red;';
            }
        } else {
            $specs = '';
        }
        return ' style="position:absolute;' . $specs . '"';
    }


    // return the html for including the yui libs in the dragdroparrange.tpl template
    //hw: NOTE: should return html code with the paths to the moodle yui libs if they have the resize lib
    function get_arrange_tpl_yui_libs() {
        // get libpath and resizelib
        if (file_exists("{$this->cfg->libdir}/yui/resize/resize-min.js")) {
            $libdir    = $this->cfg->wwwroot."/lib";
            $resizelib = "resize-min.js";
        }
        else {
            // this is for the yui beta version included in this question type
            $libdir    = $this->cfg->wwwroot."/question/type/".$this->ddqtype->name();
            $resizelib = "resize-beta-min.js";
        }
        // make html for css and javascript includes
        $html  = '<link rel="stylesheet" type="text/css" href="'.$libdir.'/yui/resize/assets/skins/sam/resize.css" />'."\n";
        $html .= '<script type="text/javascript" src="'.$libdir.'/yui/utilities/utilities.js"></script>'."\n";
        $html .= '<script type="text/javascript" src="'.$libdir.'/yui/dragdrop/dragdrop-min.js"></script>'."\n";
        $html .= '<script type="text/javascript" src="'.$libdir.'/yui/resize/'.$resizelib.'"></script>';

        return $html;
    }

    // return the html for including the yui libs in the dragdropview.tpl template
    function get_view_tpl_yui_libs() {
        // make html for css and javascript includes
        $html  = '<script type="text/javascript" src="'.$this->cfg->wwwroot.'/lib/yui/yahoo-dom-event/yahoo-dom-event.js"></script>'."\n";
        $html .= '<script type="text/javascript" src="'.$this->cfg->wwwroot.'/lib/yui/dragdrop/dragdrop-min.js"></script>'."\n";
        $html .= '<script type="text/javascript" src="'.$this->cfg->wwwroot.'/lib/yui/utilities/utilities.js"></script>';

        return $html;
    }

    // displaytype can be edit,display, or displayro
    //bk: NOTE: might be a good idea to create a class to use for the layers
    function display($displaytype)
    {

        global $SESSION;
        require_once("{$this->cfg->libdir}/smarty/Smarty.class.php");

        // *******************************************************************
        // prepare and display the positioning board
        // *******************************************************************
        $id = $this->id;
        $courseid = $this->courseid;
        $cmid = $this->cmid;
        $returnurl = $this->returnurl;
        $question = $this->question;
        $background = $question->options->backgroundmedia;
        $mediaobjects = $this->question->options->media;

        $freestyle = $question->options->freestyle ? 1 : 0;
        $isreview = ($displaytype == 'displayro');

        $backgroundname = 'background'.$background->id;
        $background->mediatag = $this->dragdrop_tag($question->id, $background, $backgroundname);

        // the images or media files will be presented on layers.
        // there are two sets of layers - the hotspot layers, used for positioning the hotspots,
        //  and the display layers, which are used to display the media files in their actual size
        // In addition, there are container layers, that hold the display layers.

        // set up the arrays of information about the gap images,

        $displaylayers = array();

        $setformfields = array();
        $sethotspots = array();
        $setcontainers = array();

        $imageborderwidth=0;
        $mediaborderwidth=2;  // flash movies, for one, grab the mouse on mouseover, so an (invisible) border is used for the container

        $i = 0;
        $maxheight = 0;

        $this->evaluate_responses($mediaobjects);
        $responses = $this->responses;

        foreach ($mediaobjects as $media) {
            $mediahotspot = $media->hotspots[$media->primary_hotspot];
            $suffix = "{$this->dragdropqcount}_$i";
            $container = "container$suffix";
            $hotspot = "hotspot$suffix";

            $isimage = $this->is_image($media->media);

            $maxheight = max($maxheight, ($media->displayheight > 0 ? $media->displayheight : $media->height));
            $displayname = "gapimage$suffix";
            $displaylayers[$i]['tag'] = $this->dragdrop_tag($question->id, $media, $displayname, !$isimage);

            $displaylayers[$i]['name'] = $displayname;
            $displaylayers[$i]['id'] = $displayname;
            $displaylayers[$i]['text'] = $media->questiontext;
            $displaylayers[$i]['key'] = $media->id;
            $displaylayers[$i]['width'] = $media->displaywidth ? $media->displaywidth : $media->width;
            $displaylayers[$i]['height'] = $media->displayheight ? $media->displayheight : $media->height;
            $displaylayers[$i]['targetx'] = $media->targetx;
            $displaylayers[$i]['targety'] = $media->targety;

            if ($displaytype == 'edit') {
                // hotspot tag and id
                $sethotspots[$i]['tag'] = '<img src="transparentpixel.gif" id="'. $hotspot . '" title="'. $hotspot . '" style="position:absolute; border:solid red 2px; z-index:99;">';
                $sethotspots[$i]['id'] = $hotspot;

                // size the hotspots
                $hotspotWidth  = 0;
                $hotspotHeight = 0;
                if ($mediahotspot->width == 0 && $mediahotspot->height == 0) {
                    $hotspotWidth  = floor($media->width / 2);
                    $hotspotHeight = floor($media->height / 2);
                } else {
                    $hotspotWidth  = $mediahotspot->width;
                    $hotspotHeight = $mediahotspot->height;
                }
                $sethotspots[$i]['width']  = $hotspotWidth;
                $sethotspots[$i]['height'] = $hotspotHeight;

                //position the hotspots
                $hotspotX  = 0;
                $hotspotY  = 0;
                if ($mediahotspot->x == 0 && $mediahotspot->y == 0 && $mediahotspot->width == 0 && $mediahotspot->height == 0) {
                    //$hotspotX  = floor($media->width / 4);
                    //$hotspotY  = floor($media->height / 4);
                    $hotspotX  = -1;
                    $hotspotY  = -1;
                } else {
                    $hotspotX  = $mediahotspot->x;
                    $hotspotY  = $mediahotspot->y;
                }
                $sethotspots[$i]['x'] = $hotspotX;
                $sethotspots[$i]['y'] = $hotspotY;

            } else {
                $formid = $question->name_prefix.$media->id;
                // added by harry - beginn
                // every javascript object knows about his response forms
                $displaylayers[$i]['responseFormId'] = $formid;
                // added by harry - end

                if (isset($this->responses[$media->id])) {
                    $resp = $this->responses[$media->id];
                    $position = "{$resp->x},{$resp->y},{$resp->width},{$resp->height}";
                } else {
                    $position = '';
                }
                //hw: in a review the formfields are nonamed to prevent bad guys from changing the values...
                if ($isreview) {
                    $draggable = '+NO_DRAG';
                    $setformfields[] = "<input id=\"$formid\" type=\"hidden\" value=\"$position\" />";
                } else {
                    $draggable = '';
                    $setformfields[] = "<input id=\"$formid\" type=\"hidden\" name=\"$formid\" value=\"$position\" />";
                }
            }
            $i++;
        }

        $gapcount = count($displaylayers);

        if ($displaytype == 'edit') {
            $template = 'dragdroparrange.tpl';
        } else {
            $template = 'dragdropshow.tpl';
        }

        // create the page using a template
        $smarty =& $this->init_smarty("{$this->cfg->dirroot}/question/type/dragdrop/templates");

        if ($displaytype == 'edit')
            $smarty->assign('yuilibs', $this->get_arrange_tpl_yui_libs());
        else
            $smarty->assign('yuilibs', $this->get_view_tpl_yui_libs());

        $smarty->assign('title', get_string('dragdroparrange', 'qtype_dragdrop'));
        $smarty->assign('formaction', $_SERVER['PHP_SELF']);
        $smarty->assign('id', $id);
        $smarty->assign('courseid', $courseid);
        $smarty->assign('cmid', $cmid);
        $smarty->assign('returnurl', $returnurl);

        $smarty->assign('gapimages', $displaylayers);
        $smarty->assign('question', $question);
        $smarty->assign('background', $background);
        $smarty->assign('sethotspots', $sethotspots);
        $smarty->assign('gapcount', $gapcount);
        $smarty->assign('submitsavereturn', get_string('savereturn', 'qtype_dragdrop'));
        $smarty->assign('submitsavecontinue', get_string('savecontinue', 'qtype_dragdrop'));
        $smarty->assign('submitcancel', get_string('cancel'));
        $smarty->assign('hideimages', get_string('hideimages', 'qtype_dragdrop'));
        $smarty->assign('hidehotspots', get_string('hidehotspots', 'qtype_dragdrop'));
        $smarty->assign('showimages', get_string('showimages', 'qtype_dragdrop'));
        $smarty->assign('showhotspots', get_string('showhotspots', 'qtype_dragdrop'));
        $smarty->assign('snaphotspots', get_string('snaphotspots', 'qtype_dragdrop'));
        $smarty->assign('snapimages', get_string('snapimages', 'qtype_dragdrop'));
        $smarty->assign('setcontainers', $setcontainers);
        $smarty->assign('maxheight', $maxheight);

        // added by Harry for javascript - beginn

        // smarty caching deactivating for development only
        //$smarty->clear_cache($template);

        // Javascriptcode for DDGapImage object added here because we need it twice.
        // In dragdropshow.tpl and in dragdroparrange.tpl.
        $text = "<script type='text/javascript'>"."\n".
                "// Our custom drag and drop implementation, extending YAHOO.util.DD"."\n".
                "YAHOO.util.DDGapImage = function(id, sGroup, config) {"."\n".
                "    YAHOO.util.DDGapImage.superclass.constructor.apply(this, arguments);"."\n".
                "};"."\n"."\n".

                "YAHOO.extend(YAHOO.util.DDGapImage, YAHOO.util.DD, {"."\n".
                "     origZ: 0,"."\n".
                "     startPos: false,"."\n".
                "     responseForm: false,"."\n".
                "     backgroundId: false,"."\n".
                "     width: false,"."\n".
                "     height: false,"."\n"."\n".

                "     startDrag: function(x, y) {"."\n".
                "     },"."\n"."\n".

                "     endDrag: function(e) {"."\n".
                "         // Sets the response form values"."\n".
                "         // But I don't know if it is a good idea like this. Perhaps its easier to"."\n".
                "         // do it just before sending the form. Just like in dragdroparrange.tpl."."\n".
                "         this.setFormValues();"."\n".
                "     },"."\n"."\n".

                "     onMouseDown: function(e) {"."\n".
                "         // Brings every dragged object in front."."\n".
                "         var style = this.getEl().style;"."\n"."\n".

                "         // store the original z-index"."\n".
                "         this.origZ = style.zIndex;"."\n"."\n".

                "         // The z-index needs to be set very high so the element will indeed be on top"."\n".
                "         style.zIndex = 99;"."\n".
                "     },"."\n"."\n".

                "     onMouseUp: function(e) {"."\n".
                "         // restore the original z-index"."\n".
                "         this.getEl().style.zIndex = this.origZ;"."\n".
                "     },"."\n"."\n".

                "     onInvalidDrop: function(e) {"."\n".
                "         // Get background position"."\n".
                "         // Can not be stored statically because background can move while window resizing"."\n".
                "         // Restoring the absolute position relative to the background"."\n".
                "         backgroundXY    = YAHOO.util.Dom.getXY(this.backgroundId);"."\n"."\n".
                "         myAbsolutPos    = new Array();"."\n".
                "         myAbsolutPos[0] = this.startPos[0] + backgroundXY[0];"."\n".
                "         myAbsolutPos[1] = this.startPos[1] + backgroundXY[1];"."\n".

                "         // return to the start position"."\n".
                "         var attr = {"."\n".
                "                       points: { to: myAbsolutPos }"."\n".
                "                    };"."\n".
                "         // we store this in a dummy for calling the setFormValues function"."\n".
                "         // at the end of the animation"."\n".
                "         var dummy = this;"."\n".
                "         var anim = new YAHOO.util.Motion(this.id, attr, 1, YAHOO.util.Easing.easeOut);"."\n".
                "         anim.onComplete.subscribe(function() {"."\n".
                "            dummy.setFormValues();"."\n".
                "         });"."\n"."\n".

                "         anim.animate();"."\n".
                "         //alert('onInvalidDrop - end');"."\n".
                "     },"."\n"."\n".

                "     initDDGapImage: function() {"."\n".
                "         // Get background position"."\n".
                "         // Can not be stored statically because background can move while window resizing"."\n".
                "         backgroundXY = YAHOO.util.Dom.getXY(this.backgroundId);"."\n"."\n".

                "         // Set original start position"."\n".
                "         // We set it relative to the background"."\n".
                "         if (!this.startPos)"."\n".
                "            myAbsolutPos     = YAHOO.util.Dom.getXY(this.id);"."\n".
                "            myRelativePos    = new Array();"."\n".
                "            myRelativePos[0] = myAbsolutPos[0] - backgroundXY[0];"."\n".
                "            myRelativePos[1] = myAbsolutPos[1] - backgroundXY[1];"."\n".
                "            this.startPos    = myRelativePos;"."\n"."\n".

                "         // Set gapimage position if already moved"."\n".
                "         if(this.responseForm && this.responseForm.value) {"."\n".
                "            // if in view or respond mode"."\n".
                "            pos   = this.responseForm.value.split(',');"."\n".
                "            XY    = pos.slice(0,2);"."\n".
                "            XY[0] = parseInt(backgroundXY[0]) + parseInt(XY[0]);"."\n".
                "            XY[1] = parseInt(backgroundXY[1]) + parseInt(XY[1]);"."\n".
                "            YAHOO.util.Dom.setXY(this.id, XY);"."\n".
                "         }"."\n".
                "         else if(this.targetx || this.targety) {"."\n".
                "            // if in edit mode"."\n".
                "            XY    = new Array();"."\n".
                "            XY[0] = parseInt(backgroundXY[0]) + parseInt(this.targetx);"."\n".
                "            XY[1] = parseInt(backgroundXY[1]) + parseInt(this.targety);"."\n".
                "            YAHOO.util.Dom.setXY(this.id, XY);"."\n".
                "         }"."\n".
                "     },"."\n"."\n".

                "     setFormValues: function() {"."\n".
                "         // Get background position"."\n".
                "         // Can not be stored statically because background can move while window resizing"."\n".
                "         backgroundXY = YAHOO.util.Dom.getXY(this.backgroundId);"."\n"."\n".

                "         // compute the relativ position to background"."\n".
                "         dropAbsolutePos = YAHOO.util.Dom.getXY(this.id);"."\n".
                "         dropAbsoluteX   = dropAbsolutePos[0];"."\n".
                "         dropAbsoluteY   = dropAbsolutePos[1];"."\n".
                "         dropRelativeX   = dropAbsoluteX - backgroundXY[0];"."\n".
                "         dropRelativeY   = dropAbsoluteY - backgroundXY[1];"."\n"."\n".

                "         // fill response form values"."\n".
                "         this.responseForm.value = dropRelativeX + ',' + dropRelativeY + ',' + this.width + ',' + this.height;"."\n".
                "     }"."\n".
                "});"."\n"."\n".

                "var arrOfGapimageObjects$background->id = new Array();"."\n".
                "var arrOfGapimages$background->id = new Array();"."\n".
                "</script>"."\n"."\n";
        $smarty->assign('script1', $text);

        $text  = '<script type="text/javascript">'."\n".
                 '// Initializing the background as valid drop target'."\n".
                 'var background'.$background->id.' = new YAHOO.util.DDTarget(backgroundId'.$background->id.');'."\n"."\n".

                 '// Initializing the gapimages'."\n".
                 'function init'.$background->id.'() {'."\n".
                 '    for (var i = 0; i < arrOfGapimageObjects'.$background->id.'.length; ++i) {'."\n".
                 '        arrOfGapimages'.$background->id.'[i] = new YAHOO.util.DDGapImage(arrOfGapimageObjects'.$background->id.'[i].name);'."\n".
                 '        arrOfGapimages'.$background->id.'[i].backgroundId = arrOfGapimageObjects'.$background->id.'[i].backgroundId;'."\n".
                 '        arrOfGapimages'.$background->id.'[i].width = arrOfGapimageObjects'.$background->id.'[i].width;'."\n".
                 '        arrOfGapimages'.$background->id.'[i].height = arrOfGapimageObjects'.$background->id.'[i].height;'."\n";

        // the arrange template gets another script than the view template
        if ($displaytype == 'edit') {
            $text .= '        arrOfGapimages'.$background->id.'[i].targetx = arrOfGapimageObjects'.$background->id.'[i].targetx;'."\n";
            $text .= '        arrOfGapimages'.$background->id.'[i].targety = arrOfGapimageObjects'.$background->id.'[i].targety;'."\n";
            $text .= '        arrOfGapimages'.$background->id.'[i].initDDGapImage();'."\n";
            $text .= '        arrOfHotspots'.$background->id.'[i].gapImage = arrOfGapimages'.$background->id.'[i];'."\n";
            $text .= '        arrOfHotspots'.$background->id.'[i].initDDHotSpot();'."\n";
        }
        else {
            $text .= '        arrOfGapimages'.$background->id.'[i].responseForm = YAHOO.util.Dom.get(arrOfGapimageObjects'.$background->id.'[i].responseFormId);'."\n";
            $text .= '        arrOfGapimages'.$background->id.'[i].initDDGapImage();'."\n";
        }

        $text .= '    }'."\n".
                 '}'."\n"."\n".

                 '// calling init() when DOM is ready'."\n".
                 'YAHOO.util.Event.onDOMReady(init'.$background->id.');'."\n".
                 '</script>'."\n"."\n";
        $smarty->assign('script2', $text);
        // added by Harry for javascript - end


/*
        if ($displaytype == 'display') {
            $smarty->assign('formfields', $setformfields);
        }
*/
        //hw: I put this in every view because I need the data for positioning the gapimages
        $smarty->assign('formfields', $setformfields);
        $expout = $smarty->display($template);

    }


    /*
     * saves the positions of the images and hotspots
    */
    function process($process) {
        $id = $this->id;
        $cmid = $this->cmid;
        $courseid = $this->courseid;
        $returnurl = $this->returnurl;
        $mediaobjects = $this->question->options->media;
        // *******************************************************************
        // if the positions of the gap images are being submitted after editing, process them and redirect
        // *******************************************************************

        if ($process) {
            // if user pressed cancel, return to question editing
            if ($process == 'cancel') {
                $questionurl = new moodle_url("{$this->cfg->wwwroot}/question/question.php",
                                array('returnurl' => $returnurl, 'id' => $id));
                if ($cmid) {
                    redirect($questionurl->out(false, array('cmid'=>"$cmid")));
                } else {
                    $questionurl->param('courseid', $courseid);
                    redirect($questionurl->out(false, array('courseid'=>"$courseid")));
                }
            }

            if(!$ddform = data_submitted()) {
                error ("No data submitted");
            } else {

                $positions = $this->get_gap_image_positions($ddform);

                foreach ($mediaobjects as $key=>$media) {
                    $hotspot = $mediaobjects[$key]->hotspots[$mediaobjects[$key]->primary_hotspot];
                    $hotspot->x = $positions[$key]['hotspot']['x'];
                    $hotspot->y = $positions[$key]['hotspot']['y'];
                    $hotspot->height = $positions[$key]['hotspot']['height'];
                    $hotspot->width = $positions[$key]['hotspot']['width'];
                    if (!update_record("question_dragdrop_hotspot", $hotspot)) {
                        error("Could not update dragdrop hotspot! (id=$hotspot->id)");
                    }

                    $mediaobjects[$key]->questiontext = addslashes($mediaobjects[$key]->questiontext);
                    $mediaobjects[$key]->targetx = $positions[$key]['gapimage']['x'];
                    $mediaobjects[$key]->targety = $positions[$key]['gapimage']['y'];
                    $mediaobjects[$key]->displayheight = $positions[$key]['gapimage']['height'];
                    $mediaobjects[$key]->displaywidth = $positions[$key]['gapimage']['width'];
                    $mediaobjects[$key]->positioned = $positions[$key]['gapimage']['positioned'];
                    $mediaobjects[$key]->hotspots = implode(',', array_keys($mediaobjects[$key]->hotspots));
                    if (!update_record("question_dragdrop_media", $mediaobjects[$key])) {
                        error("Could not update dragdrop media! (id=$mediaobjects[$key]->id)");
                    }
                }
                if ($process == 'savecontinue') {
                    if ($cmid) {
                        $questionurl = new moodle_url("{$this->cfg->wwwroot}/mod/quiz/edit.php",
                                        array('returnurl' => $returnurl, 'id' => $id));
                        redirect($questionurl->out(false, array('cmid'=>"$cmid")));
                    } else {
                        $questionurl = new moodle_url("{$this->cfg->wwwroot}/question/question.php",
                                        array('returnurl' => $returnurl, 'id' => $id));
                        redirect($questionurl->out(false, array('courseid'=>"$courseid")));
                    }
                } else {
                    $questionurl = new moodle_url("{$this->cfg->wwwroot}/question/question.php",
                                    array('returnurl' => $returnurl, 'id' => $id));
                    if ($cmid) {
                        redirect($questionurl->out(false, array('cmid'=>"$cmid")));
                    } else {
                        redirect($questionurl->out(false, array('courseid'=>"$courseid")));
                    }
                }
            }
        }
    }

     /**
     * determines whether or not a file is an image, based on the file extension
     *
     * @param string $file the filename
     * @return boolean
     */
    function is_image($file) {
        $extensionsregex = '/\.(gif|jpg|jpeg|jpe|png|tif|tiff|bmp|xbm|rgb|svf)$/';
        if (preg_match($extensionsregex, $file)) {
            return true;
        }
        return false;
    }

    /**
     * creates a media tag to use with the dragdrop javascript
     *
     * the code is based on the function get_question_image() in lib/questionlib.php
     *
     * @param int $questionid
     * @param object $mediaobject containing the fields media, width, height
     * @param string $name this will be used for the id of the div, as well as for the alt tag for the media
     * @param boolean $islayer create a layer tag (false => it's just a plain image)
     * @return string media tag to use
     */
    function dragdrop_tag($questionid, $mediaobject, $name, $islayer = true) {
        if (substr(strtolower($mediaobject->media), 0, 7) == 'http://') {
            $image = $mediaobject->media;
        } else if ($this->cfg->slasharguments) {        // Use this method if possible for better caching
            $image = "{$this->cfg->wwwroot}/file.php/{$this->courseid}/{$mediaobject->media}";
        } else {
            $image = "{$this->cfg->wwwroot}/file.php?file=/{$this->courseid}/{$mediaobject->media}";
        }

        $tag = null;
        $width = !empty($mediaobject->displaywidth) ? $mediaobject->displaywidth : $mediaobject->width;
        $height = !empty($mediaobject->displayheight) ? $mediaobject->displayheight : $mediaobject->height;

        if ($islayer) {
            if ($this->is_image($image)) {
                // for background image tag
                $tag = "<img id=\"$name\" border=\"0\" src=\"$image\" alt=\"picture: $name\" width=\"$width\" height=\"$height\" galleryimg=\"no\" /> ";
            }
            else {
                // i don't know what this is for. I didn't try it...
                // there will be some further work to check it out - by harry
                // Some hours later: now I tried it. It works not ok!!!
                include_once("{$this->cfg->dirroot}/question/type/dragdrop/custommediafilter.php");
                $tag = '<div id="' . $name . '" style="position:relative; cursor:move; background:url(transparentpixel.gif);">' .
                            dd_custom_mediaplugin_filter('<a href="' . $image . '"></a>', $width, $height) .
                        '</div>';
            }
        } else {
            // for gapimage tag
            $tag = "<img style=\"position:relative; cursor:move; z-index:".$name[strlen($name)-1].";\" border=\"0\" id=\"$name$questionid\" name=\"$name$questionid\" src=\"$image\" title=\"picture: $name\" width=\"$width\" height=\"$height\" galleryimg=\"no\" />";
        }
        return $tag;
    }

    function get_gap_image_positions($gapform)
    {
        $pattern = '/^(gapimage|hotspot)([0-9]+)_([a-z]+)$/';
        $positions = array();
        foreach ($gapform as $key=>$value) {
            if (preg_match($pattern, $key, $matches)) {
                $positions[$matches[2]][$matches[1]][$matches[3]] = $value;
            }
        }
        return $positions;
    }

    /**
     * gets a new Smarty object, with the template and compile directories set
     *
     * @param string $templatedir template directory
     * @return object a smarty object
     */
    function & init_smarty($templatedir) {
        global $CFG;

        // create smarty compile dir in dataroot
        $path = $CFG->dataroot."/smarty_c";
        if (!is_dir($path)) {
            if (!mkdir($path, $CFG->directorypermissions)) {
              error("Cannot create path: $path");
            }
        }
        $smarty = new Smarty;
        $smarty->template_dir = $templatedir;
        $smarty->compile_dir  = $path;
        return $smarty;
    }
}
?>