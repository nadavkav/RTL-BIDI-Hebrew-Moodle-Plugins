<?php // $Id: format.php,v 1.83.2.3 2008/12/10 06:05:27 dongsheng Exp $
      // Display the whole course as "topics" made of of modules
      // In fact, this is very similar to the "weeks" format, in that
      // each "topic" is actually a week.  The main difference is that
      // the dates aren't printed - it's just an aesthetic thing for
      // courses that aren't so rigidly defined by time.
      // Included from "view.php"


    require_once($CFG->libdir.'/ajax/ajaxlib.php');
    require_js(array('yui_yahoo','yui_dom','yui_event','yui_connection'));

    $topic = optional_param('topic', -1, PARAM_INT);

    // Bounds for block widths
    // more flexible for theme designers taken from theme config.php
    $lmin = (empty($THEME->block_l_min_width)) ? 100 : $THEME->block_l_min_width;
    $lmax = (empty($THEME->block_l_max_width)) ? 210 : $THEME->block_l_max_width;
    $rmin = (empty($THEME->block_r_min_width)) ? 100 : $THEME->block_r_min_width;
    $rmax = (empty($THEME->block_r_max_width)) ? 210 : $THEME->block_r_max_width;

    define('BLOCK_L_MIN_WIDTH', $lmin);
    define('BLOCK_L_MAX_WIDTH', $lmax);
    define('BLOCK_R_MIN_WIDTH', $rmin);
    define('BLOCK_R_MAX_WIDTH', $rmax);

    $preferred_width_left  = bounded_number(BLOCK_L_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]),
                                            BLOCK_L_MAX_WIDTH);
    $preferred_width_right = bounded_number(BLOCK_R_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]),
                                            BLOCK_R_MAX_WIDTH);

    if ($topic != -1) {
        $displaysection = course_set_display($course->id, $topic);
    } else {
        if (isset($USER->display[$course->id])) {       // for admins, mostly
            $displaysection = $USER->display[$course->id];
        } else {
            $displaysection = course_set_display($course->id, 0);
        }
    }

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
        $course->marker = $marker;
        if (! set_field("course", "marker", $marker, "id", $course->id)) {
            error("Could not mark that topic for this course");
        }
    }

    $streditsummary   = get_string('editsummary');
    $stradd           = get_string('add');
    $stractivities    = get_string('activities');
    $strshowalltopics = get_string('showalltopics');
    $strtopic         = get_string('topic');
    $strgroups        = get_string('groups');
    $strgroupmy       = get_string('groupmy');
    $editing          = $PAGE->user_is_editing();

    if ($editing) {
        $strstudents = moodle_strtolower($course->students);
        $strtopichide = get_string('topichide', '', $strstudents);
        $strtopicshow = get_string('topicshow', '', $strstudents);
        $strmarkthistopic = get_string('markthistopic');
        $strmarkedthistopic = get_string('markedthistopic');
        $strmoveup = get_string('moveup');
        $strmovedown = get_string('movedown');
    }


/// Layout the whole page as three big columns.
    echo '<table id="layout-table" class="digibook" cellspacing="0" summary="'.get_string('layouttable').'"><tr>';

/// The left column ...
    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':

    if (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
        echo '<td style="width:'.$preferred_width_left.'px" id="left-column">';

        $topicbutton = '<div id="navbuttons"><button id="next">'.get_string('next','format_summaryblk').'</button><button id="prev">'.get_string('prev','format_summaryblk').'</button></div><hr/>';
        $topicbutton .= '<div id="navtopics">';

        for ($topic = 0;  $topic < $COURSE->numsections ; $topic++) {
            if (empty($sections[$topic]->summary)) continue;
            if ($topic == 0) {
                $topicbutton .= "<a id=\"topic{$topic}\" href=\"#\">".get_string('overview','format_summaryblk')."</a><br/>";
            } else {
                $topicbutton .= "<a id=\"topic{$topic}\" href=\"#\">".$sections[$topic]->summary."</a></br>";
            }
               $topicbuttonevent .= '
               Event.on("topic'.$topic.'", "click", function(e) {
                   topic = '.$topic.';
                   console.log("topic: '.$topic.'");
                   var request = YAHOO.util.Connect.asyncRequest("GET", sUrl + "'.$topic.'", callback);
               });
           ';
        }
        $topicbutton .= '</div>';
        print_side_block(get_string('navmenu','format_summaryblk'), $topicbutton, NULL, NULL, NULL, array('id' => 'navmenu'),'');

        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        print_container_end();
        echo '</td>';
    }

            break;
            case 'middle':
/// Start main column
    echo '<td id="middle-column">';
    print_container_start();
    echo skip_main_destination();

    //print_heading_block(get_string('topicoutline'), 'outline');

    //echo '<div id="navbuttons" style="float:right;"><button id="next">הבאה</button><button id="prev">קודמת</button></div>';
if (isediting($course->id)) {
    $displayfirsttopic = '';
} else {
    $displayfirsttopic = '';//'var request = YAHOO.util.Connect.asyncRequest("GET", sUrl + "0", callback);';
}

echo '

<div id="dynamictopic"></div>
<div id="beforeme"></div>

<script type="text/javascript">
(function() {
    var Event = YAHOO.util.Event,
        topic = 0;

    var sUrl = "'.$CFG->wwwroot.'/course/format/digibook/gettopic.php?courseid='.$COURSE->id.'&topic=";
    var div = document.getElementById("dynamictopic");

    var handleSuccess = function(o){ 
	    if(o.responseText !== undefined){ 
	        div.innerHTML = o.responseText;
//	        div.innerHTML = "<li>Transaction id: " + o.tId + "</li>"; 
//	        div.innerHTML += "<li>HTTP status: " + o.status + "</li>"; 
//	        div.innerHTML += "<li>Status code message: " + o.statusText + "</li>"; 
//	        div.innerHTML += "<li>HTTP headers: <ul>" + o.getAllResponseHeaders + "</ul></li>"; 
//	        div.innerHTML += "<li>Server response: " + o.responseText + "</li>"; 
//	        div.innerHTML += "<li>Argument object: Object ( [foo] => " + o.argument.foo + 
//	                         " [bar] => " + o.argument.bar +" )</li>"; 

            var beforeme = document.getElementById("beforeme");

            if ( beforeme.hasChildNodes() )
            {
                while ( beforeme.childNodes.length >= 1 )
                {
                    beforeme.removeChild( beforeme.firstChild );
                }
            }
	    } 
	} 
	 
	var handleFailure = function(o){ 
	    if(o.responseText !== undefined){ 
	        div.innerHTML = "<li>Transaction id: " + o.tId + "</li>"; 
	        div.innerHTML += "<li>HTTP status: " + o.status + "</li>"; 
	        div.innerHTML += "<li>Status code message: " + o.statusText + "</li>"; 
	    } 
	} 
	 
	var callback = 
	{ 
	  success:handleSuccess, 
	  failure: handleFailure, 
	  argument: { foo:"foo", bar:"bar" } 
	}; 

    ////////////////////

    var beforeme = document.getElementById("beforeme");

    var handleSuccessMore = function(o){ 
	    if(o.responseText !== undefined){ 
	        var newdiv = document.createElement("div");
            beforeme.insertBefore(newdiv,beforeme.previousSibling.lastChild);

	        newdiv.innerHTML = o.responseText;
	    } 
	} 

	var handleFailureMore = function(o){ 
	    if(o.responseText !== undefined){ 
	        newdiv.innerHTML = "<li>Transaction id: " + o.tId + "</li>"; 
	        newdiv.innerHTML += "<li>HTTP status: " + o.status + "</li>"; 
	        newdiv.innerHTML += "<li>Status code message: " + o.statusText + "</li>"; 
	    } 
	} 

	var callbackmore = 
	{ 
	  success:handleSuccessMore, 
	  failure: handleFailureMore, 
	  argument: { foo:"foo", bar:"bar" } 
	}; 

    Event.onDOMReady(function() {

        // Display first Topic on first page load
        '.$displayfirsttopic.'

        Event.on("next", "click", function(e) {
            if (topic < '.$COURSE->numsections.') topic += 1;
            console.log("topic: " + topic);
            var request = YAHOO.util.Connect.asyncRequest("GET", sUrl + topic, callback);

        });

        Event.on("prev", "click", function(e) {
            if (topic > 0) topic -= 1;
            console.log("topic: " + topic);
            var request = YAHOO.util.Connect.asyncRequest("GET", sUrl + topic, callback);
        });

        Event.on("more", "click", function(e) {
            if (topic < '.$COURSE->numsections.') topic += 1;
            console.log("topic: " + topic);
            var request = YAHOO.util.Connect.asyncRequest("GET", sUrl + topic, callbackmore);
        });

        '.$topicbuttonevent.'
    });

})();
</script>
';

//echo '<button id="more">'.get_string('nexttopic','format_summaryblk').'</button>';

    if(isediting($course->id)){


    echo '<table class="topics" width="100%" summary="'.get_string('layouttable').'">';

/// If currently moving a file then show the current clipboard
    if (ismoving($course->id)) {
        $stractivityclipboard = strip_tags(get_string('activityclipboard', '', addslashes($USER->activitycopyname)));
        $strcancel= get_string('cancel');
        echo '<tr class="clipboard">';
        echo '<td colspan="3">';
        echo $stractivityclipboard.'&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey='.$USER->sesskey.'">'.$strcancel.'</a>)';
        echo '</td>';
        echo '</tr>';
    }

/// Print Section 0

    $section = 0;
    $thissection = $sections[$section];

    if ($thissection->summary or $thissection->sequence or isediting($course->id)) {
        echo '<tr id="section-0" class="section main">';
        echo '<td class="left side">&nbsp;</td>';
        echo '<td class="content">';

        echo '<div class="summary">';
        $summaryformatoptions->noclean = true;
        echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);

        if (isediting($course->id) && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
            echo '<a title="'.$streditsummary.'" '.
                 ' href="editsection.php?id='.$thissection->id.'"><img src="'.$CFG->pixpath.'/t/edit.gif" '.
                 ' alt="'.$streditsummary.'" /></a><br /><br />';
        }
        echo '</div>';

        print_section($course, $thissection, $mods, $modnamesused);

        if (isediting($course->id)) {
            print_section_add_menus($course, $section, $modnames);
        }

        echo '</td>';
        echo '<td class="right side">&nbsp;</td>';
        echo '</tr>';
        echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';
    }


/// Now all the normal modules by topic
/// Everything below uses "section" terminology - each "section" is a topic.

    $timenow = time();
    $section = 1;
    $sectionmenu = array();

    while ($section <= $course->numsections) {

        if (!empty($sections[$section])) {
            $thissection = $sections[$section];

        } else {
            unset($thissection);
            $thissection->course = $course->id;   // Create a new section structure
            $thissection->section = $section;
            $thissection->summary = '';
            $thissection->visible = 1;
            if (!$thissection->id = insert_record('course_sections', $thissection)) {
                notify('Error inserting new topic!');
            }
        }

        $showsection = (has_capability('moodle/course:viewhiddensections', $context) or $thissection->visible or !$course->hiddensections);

        if (!empty($displaysection) and $displaysection != $section) {
            if ($showsection) {
                $strsummary = strip_tags(format_string($thissection->summary,true));
                if (strlen($strsummary) < 57) {
                    $strsummary = ' - '.$strsummary;
                } else {
                    $strsummary = ' - '.substr($strsummary, 0, 60).'...';
                }
                $sectionmenu['topic='.$section] = s($section.$strsummary);
            }
            $section++;
            continue;
        }

        if ($showsection) {

            $currenttopic = ($course->marker == $section);

            $currenttext = '';
            if (!$thissection->visible) {
                $sectionstyle = ' hidden';
            } else if ($currenttopic) {
                $sectionstyle = ' current';
                $currenttext = get_accesshide(get_string('currenttopic','access'));
            } else {
                $sectionstyle = '';
            }

            echo '<tr id="section-'.$section.'" class="section main'.$sectionstyle.'">';
            echo '<td class="left side">'.$currenttext.$section.'</td>';

            echo '<td class="content">';
            if (!has_capability('moodle/course:viewhiddensections', $context) and !$thissection->visible) {   // Hidden for students
                echo get_string('notavailable');
            } else {
                echo '<div class="summary">';
                $summaryformatoptions->noclean = true;
                echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);

                if (isediting($course->id) && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
                    echo ' <a title="'.$streditsummary.'" href="editsection.php?id='.$thissection->id.'">'.
                         '<img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.$streditsummary.'" /></a><br /><br />';
                }
                echo '</div>';

                print_section($course, $thissection, $mods, $modnamesused);

                if (isediting($course->id)) {
                    print_section_add_menus($course, $section, $modnames);
                }
            }
            echo '</td>';

            echo '<td class="right side">';
            if ($displaysection == $section) {      // Show the zoom boxes
                echo '<a href="view.php?id='.$course->id.'&amp;topic=0#section-'.$section.'" title="'.$strshowalltopics.'">'.
                     '<img src="'.$CFG->pixpath.'/i/all.gif" alt="'.$strshowalltopics.'" /></a><br />';
            } else {
                $strshowonlytopic = get_string('showonlytopic', '', $section);
                echo '<a href="view.php?id='.$course->id.'&amp;topic='.$section.'" title="'.$strshowonlytopic.'">'.
                     '<img src="'.$CFG->pixpath.'/i/one.gif" alt="'.$strshowonlytopic.'" /></a><br />';
            }

            if (isediting($course->id) && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
                if ($course->marker == $section) {  // Show the "light globe" on/off
                    echo '<a href="view.php?id='.$course->id.'&amp;marker=0&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strmarkedthistopic.'">'.
                         '<img src="'.$CFG->pixpath.'/i/marked.gif" alt="'.$strmarkedthistopic.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;marker='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strmarkthistopic.'">'.
                         '<img src="'.$CFG->pixpath.'/i/marker.gif" alt="'.$strmarkthistopic.'" /></a><br />';
                }

                if ($thissection->visible) {        // Show the hide/show eye
                    echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strtopichide.'">'.
                         '<img src="'.$CFG->pixpath.'/i/hide.gif" alt="'.$strtopichide.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strtopicshow.'">'.
                         '<img src="'.$CFG->pixpath.'/i/show.gif" alt="'.$strtopicshow.'" /></a><br />';
                }

                if ($section > 1) {                       // Add a arrow to move section up
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=-1&amp;sesskey='.$USER->sesskey.'#section-'.($section-1).'" title="'.$strmoveup.'">'.
                         '<img src="'.$CFG->pixpath.'/t/up.gif" alt="'.$strmoveup.'" /></a><br />';
                }

                if ($section < $course->numsections) {    // Add a arrow to move section down
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=1&amp;sesskey='.$USER->sesskey.'#section-'.($section+1).'" title="'.$strmovedown.'">'.
                         '<img src="'.$CFG->pixpath.'/t/down.gif" alt="'.$strmovedown.'" /></a><br />';
                }

            }

            echo '</td></tr>';
            echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';
        }

        $section++;
    }
    echo '</table>';

    if (!empty($sectionmenu)) {
        echo '<div class="jumpmenu">';
        echo popup_form($CFG->wwwroot.'/course/view.php?id='.$course->id.'&amp;', $sectionmenu,
                   'sectionmenu', '', get_string('jumpto'), '', '', true);
        echo '</div>';
    }
}
    print_container_end();
    echo '</td>';

            break;
            case 'right':
    // The right column
    if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing) {
        echo '<td style="width:'.$preferred_width_right.'px" id="right-column">';
        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        print_container_end();
        echo '</td>';
    }

            break;
        }
    }
    echo '</tr></table>';

?>
