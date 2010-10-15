<?php
require_once($CFG->libdir.'/questionlib.php');
require_once($CFG->libdir.'/gradelib.php');

/**
 * Function that can be used in various parts of the quiz code.
 * @param object $quiz
 * @param integer $cmid
 * @param object $question
 * @param string $returnurl url to return to after action is done.
 * @return string html for a number of icons linked to action pages for a
 * question - preview and edit / view icons depending on user capabilities.
 */
function qcreate_question_action_icons($cmid, $question, $returnurl){
    global $CFG, $COURSE;
    static $stredit = null;
    static $strview = null;
    static $strpreview = null;
    static $strdelete = null;
    if ($stredit === null){
        $stredit = get_string('edit');
        $strview = get_string('view');
        $strpreview = get_string('preview', 'quiz');
        $strdelete = get_string("delete");
    }
    $html =''; 
    if (($question->qtype != 'random')){
        if (question_has_capability_on($question, 'use', $question->cid)){ 
            $html .= link_to_popup_window('/question/preview.php?id=' . $question->id . '&amp;courseid=' .$COURSE->id, 'questionpreview',
                "<img src=\"$CFG->pixpath/t/preview.gif\" class=\"iconsmall\" alt=\"$strpreview\" />",
                0, 0, $strpreview, QUESTION_PREVIEW_POPUP_OPTIONS, true);
        }
    }
    $questionparams = array('returnurl' => $returnurl, 'cmid'=>$cmid, 'id' => $question->id);
    $questionurl = new moodle_url("$CFG->wwwroot/question/question.php", $questionparams);
    if (question_has_capability_on($question, 'edit', $question->cid) || 
                question_has_capability_on($question, 'move', $question->cid)) {
        $html .= "<a title=\"$stredit\" href=\"".$questionurl->out()."\">" .
                "<img src=\"$CFG->pixpath/t/edit.gif\" class=\"iconsmall\" alt=\"$stredit\" />" .
                "</a>";
    } elseif (question_has_capability_on($question, 'view', $question->cid)){
        $html .= "<a title=\"$strview\" href=\"".$questionurl->out(false, array('id'=>$question->id))."\">" .
                "<img src=\"$CFG->pixpath/i/info.gif\" alt=\"$strview\" />" .
                "</a>";
    }
    if (question_has_capability_on($question, 'edit', $question->cid)) {
        $html .= "<a title=\"$strdelete\" href=\"".$returnurl."&amp;delete=$question->id\">" .
                "<img src=\"$CFG->pixpath/t/delete.gif\" alt=\"$strdelete\" /></a>";
    }
    return $html;
}

function qcreate_required_q_list($requireds, $cat, $thisurl, $qcreate, $cm, $modulecontext){
    global $CFG, $USER, $COURSE;

    $qtypemenu = question_type_menu();

    if ($qcreate->graderatio == 100){
        $showmanualgrades = false;
    } else {
        $showmanualgrades = true;
    }

    $questionurl = new moodle_url($CFG->wwwroot.'/question/question.php');
    $questionurl->params(array('cmid'=>$cm->id, 'returnurl'=>$thisurl->out()));


    $questionsql = "SELECT q.*, c.id as cid, c.name as cname, g.grade, g.gradecomment, g.id as gid
        FROM {$CFG->prefix}question_categories c, {$CFG->prefix}question q
        LEFT JOIN {$CFG->prefix}qcreate_grades g ON q.id = g.questionid
                                                              AND g.qcreateid = {$qcreate->id}
        WHERE c.contextid = {$modulecontext->id} AND c.id = q.category AND q.hidden='0' AND q.parent='0'
            AND q.createdby=".$USER->id;
    if ($qcreate->allowed != 'ALL'){
        //wrap question type names in inverted commas.
        $allowedlistparts = explode(',', $qcreate->allowed);
        $allowedlist = ('\''.join($allowedlistparts, '\', \'').'\'');
        $questionsql .= " AND q.qtype IN ($allowedlist)";
    }

    $questions = get_records_sql($questionsql);
    $activityopen = qcreate_activity_open($qcreate);

    print_heading(get_string('requiredquestions', 'qcreate'), 'center');
    $qtyperequired = 0;
    $qtypedone = 0;
    $qtypeqs = qcreate_questions_of_type($questions);
    $content = "\n\t\t<ul id=\"requiredqlist\">";
    if ($requireds){
        $i = 1;
        $grammarised = qcreate_proper_grammar($requireds);
        $punctuated = qcreate_proper_punctuation($grammarised);
        foreach ($requireds as $qtype => $required){
            $qtyperequired += $required->no;
            if (!empty($qtypeqs[$qtype])){
                $requireds[$qtype]->done = (count($qtypeqs[$qtype]) > $required->no)
                                            ? $required->no:count($qtypeqs[$qtype]);
                $requireds[$qtype]->stillrequiredno = $required->no - $requireds[$qtype]->done;
                //sub list of questions done of each question type
                $questionlist = "\n\t\t\t\t<ul>";
                $i = 0;
                while (($i < $required->no) && ($qtypeq = array_shift($qtypeqs[$qtype]))){
                    $questionlistitem = question_item_html($qtypeq, $questionurl, $thisurl, $qcreate, $cm, $showmanualgrades);
                    $questionlist .= "\n\t\t\t\t\t<li>$questionlistitem</li>";
                    $i++;
                }
                $questionlist .= "\n\t\t\t\t</ul>";
            } else {
                $requireds[$qtype]->done = 0;
                $requireds[$qtype]->stillrequiredno = $required->no;
                $questionlist = '';
            }
            $qtypedone += $requireds[$qtype]->done;

            //one item list with one link to create question
            $linklist = "\n\t\t\t\t\t\t\t\t<ul>\n\t\t\t\t\t\t\t\t\t<li>";
            $linklist .= "<a href=\"".$questionurl->out(false, array('qtype'=>$qtype, 'category'=>$cat->id));
            if ($questionlist){
                $linklist .= "\">".get_string('clickhereanother', 'qcreate', $required->qtypestring)."</a>";
            } else {
                $linklist .= "\">".get_string('clickhere', 'qcreate', $required->qtypestring)."</a>";
            }
            $linklist .= "\n\t\t\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t\t</ul>";

            if (isset($qtypeqs[$qtype])){
               //top level list
                $requirementslist = "\n\t\t\t\t\t\t<ul>\n\t\t\t\t\t\t\t<li>";
                $requirementslist .= get_string('donequestionno', 'qcreate', $required);

                $requirementslist .= "$questionlist</li>";

                if ($requireds[$qtype]->stillrequiredno > 0){
                    $requirementslist .= "\n\t\t\t\t\t\t\t<li>";
                    $requirementslist .= get_string('todoquestionno', 'qcreate', $required);
                    $requirementslist .= "$linklist";
                    $requirementslist .= "\n\t\t\t\t\t\t</li>";
                }

                $requirementslist .= "\n\t\t\t\t\t\t\t</ul>";
                $content .= "\n\t\t\t<li>{$punctuated[$qtype]}$requirementslist</li>";
            } else {
                $content .= "\n\t\t\t<li>{$punctuated[$qtype]}$linklist</li>";
            }
        }
        $content .= "\n\t\t";
    }
    if ($qtyperequired < $qcreate->totalrequired){
        if ($qcreate->allowed != 'ALL'){
            $qtypesallowed = explode(',', $qcreate->allowed);
        } else {
            $qtypesallowed = array_keys($qtypemenu);
        }
        $extraquestionsdone = 0;
        foreach ($qtypeqs as $qtypeq){
            if (is_array($qtypeq)){
                $extraquestionsdone += count($qtypeq);
            }
        }
        $extraquestionlinklist = '<ul>';
        
        foreach ($qtypesallowed as $qtypeallowed){
            $countqtypes = isset($qtypeqs[$qtypeallowed])?count($qtypeqs[$qtypeallowed]):0;
            $extraqcreateurl = $questionurl->out(false, array('qtype'=>$qtypeallowed, 'category'=>$cat->id));
            $extraquestionlinklist .= "<li>";
            if ($activityopen){
                $extraquestionlinklist .= "<a href=\"$extraqcreateurl\">{$qtypemenu[$qtypeallowed]}</a>";
            } else {
                $extraquestionlinklist .= $qtypemenu[$qtypeallowed];
            }
            if (isset($requireds[$qtypeallowed]) && $countqtypes){
                if ($countqtypes==1){
                    $extraquestionlinklist .= '&nbsp;'.get_string('alreadydoneextraone', 'qcreate', $countqtypes);
                } else {
                    $extraquestionlinklist .= '&nbsp;'.get_string('alreadydoneextra', 'qcreate', $countqtypes);
                }
            } else if ($countqtypes) {
                if ($countqtypes==1){
                    $extraquestionlinklist .= '&nbsp;'.get_string('alreadydoneone', 'qcreate', $countqtypes);
                } else {
                    $extraquestionlinklist .= '&nbsp;'.get_string('alreadydone', 'qcreate', $countqtypes);
                }
            }

            if ($countqtypes){
                $extraquestionlinklist .= "<ul>";
                foreach ($qtypeqs[$qtypeallowed] as $qtypeq){
                    $extraquestionlinklist .= "<li>".question_item_html($qtypeq, $questionurl, $thisurl, $qcreate, $cm, $showmanualgrades)."</li>";
                }
                $extraquestionlinklist .= "</ul>";
            }

            $extraquestionlinklist .= "</li>";
        }
        $extraquestionlinklist .= '</ul>';

        $a= new object();
        $a->extraquestionsdone = $extraquestionsdone;
        $a->extrarequired = $qcreate->totalrequired - $qtyperequired;
        $content .= '<li><strong>';
        if ($a->extraquestionsdone == 1){
            $content .= get_string('extraqdone', 'qcreate', $a);
        } else {
            $content .= get_string('extraqsdone', 'qcreate', $a);
        }
        if ($a->extrarequired == 1){
            $content .= '&nbsp;'.get_string('extraqgraded', 'qcreate', $a);
        } else {
            $content .= '&nbsp;'.get_string('extraqsgraded', 'qcreate', $a);
        }
        $content .= '</strong>';
        $content .= $extraquestionlinklist.'</li>';
    }
    $grading_info = grade_get_grades($COURSE->id, 'mod', 'qcreate', $qcreate->id, $USER->id);
    $gradeforuser = $grading_info->items[0]->grades[$USER->id];
    if (!empty($gradeforuser->dategraded)){

        $fullgrade = new object();
        $fullgrade->grade = (float)$gradeforuser->str_grade;
        $fullgrade->outof = (float)$grading_info->items[0]->grademax;
        $content .= '<li><em>'.get_string('activitygrade', 'qcreate', $fullgrade);
        if (!empty($qcreate->graderatio)){
            $automaticgrade = new object();
            $automaticquestiongrade = $grading_info->items[0]->grademax * ($qcreate->graderatio / 100) / $qcreate->totalrequired;
            $automaticgrade->outof = $grading_info->items[0]->grademax * ($qcreate->graderatio / 100);
            $automaticgrade->done = ($extraquestionsdone + $qtypedone);
            $automaticgrade->required = $qcreate->totalrequired;
            if ($automaticgrade->done < $automaticgrade->required){
                $automaticgrade->grade = $automaticgrade->done * $automaticquestiongrade;
            } else {
                $automaticgrade->grade = $automaticgrade->outof;
            }
            $content .= '<ul><li><em>'.get_string('automaticgrade', 'qcreate', $automaticgrade).'</em></li>';
            if ($showmanualgrades){
                $manualgrade = new object();
                $manualgrade->grade = $gradeforuser->grade -  $automaticgrade->grade;
                $manualgrade->outof = $grading_info->items[0]->grademax - $automaticgrade->outof;
                $content .= '<li><em>'.get_string('manualgrade', 'qcreate', $manualgrade).'</em></li>';
            }
            echo '</ul>';
        }
        $content .= '</em></li>';
        $content .= '</ul>';
    }
    print_box($content, 'generalbox boxaligncenter boxwidthwide');
}

function qcreate_teacher_overview($requireds, $qcreate){
    global $CFG;
    $qtypemenu = question_type_menu();
    echo '<div class="mdl-align">';
    echo '<p><strong>'.get_string('requiredquestions', 'qcreate').'</strong> :</p>';
    echo '</div>';
    $content = "\n\t\t<ul>";
    $qtyperequired =0;
    if ($requireds){
        $grammarised = qcreate_proper_grammar($requireds);
        foreach ($requireds as $qtype => $required){
            $qtyperequired += $required->no;
        }
    }
    if ($qtyperequired < $qcreate->totalrequired){
        $a= new object();
        $a->extrarequired = $qcreate->totalrequired - $qtyperequired;
        if ($a->extrarequired == 1){
            $grammarised['extras'] = get_string('extraqgraded', 'qcreate', $a);
        } else {
            $grammarised['extras'] = get_string('extraqsgraded', 'qcreate', $a);
        }


        $a= new object();
        $a->extrarequired = $qcreate->totalrequired - $qtyperequired;

        if ($qcreate->allowed != 'ALL'){
            $qtypesallowed = explode(',', $qcreate->allowed);
        } else {
            $qtypesallowed = array_keys($qtypemenu);
        }
        $allowedqtypelist = '<ul>';
        foreach ($qtypesallowed as $qtypeallowed){
            $allowedqtypelist .= "<li>{$qtypemenu[$qtypeallowed]}</li>";
        }
        $allowedqtypelist .= '</ul>';


    }
    $punctuateds = qcreate_proper_punctuation($grammarised);
    foreach ($punctuateds as $key => $punctuated){
        $content .= "\n\t\t\t<li>{$punctuated}";
        if ($key == 'extras'){
            $content .= $allowedqtypelist;
        }
        $content .= "</li>";
    }

    $content .= "\n\t\t</ul>";

    print_box($content, 'generalbox boxaligncenter boxwidthwide');
}



function qcreate_activity_open($qcreate){
    $timenow = time();
    return ($qcreate->timeopen == 0 ||($qcreate->timeopen < $timenow)) &&
        ($qcreate->timeclose == 0 ||($qcreate->timeclose >   $timenow));
}

function question_item_html($question, $questionurl, $thisurl, $qcreate, $cm, $showgrades = true){
    global $CFG;
    $activityopen = qcreate_activity_open($qcreate);
    if ($activityopen && (question_has_capability_on($question, 'edit', $question->cid) 
            || question_has_capability_on($question, 'move', $question->cid)
            || question_has_capability_on($question, 'view', $question->cid))){
        $questionlistitem = "<a href=\"".$questionurl->out(false, array('id'=>$question->id))."\">";
        $questionlistitem .= $question->name."</a>";
    } else {
        $questionlistitem = $question->name;
    }
    if ($showgrades){
        $questionlistitem .= "&nbsp;<em>";
        if ($question->gid && $question->grade != -1){
            $questionlistitem .= "({$question->grade}/{$qcreate->grade})";
        } else {
            $questionlistitem .= "(".get_string('notgraded', 'qcreate').")";
        }
        if ($question->gradecomment != ''){
            $questionlistitem .='"'.$question->gradecomment.'"';
        }
        $questionlistitem .= "</em>";
    }
    if ($activityopen){
        $questionlistitem .= qcreate_question_action_icons($cm->id, $question, $thisurl->out_action());
    }

    return $questionlistitem;
}
function qcreate_proper_grammar($arrayitems){
    $qtypemenu = question_type_menu();
    $grammarised = array();
    foreach ($arrayitems as $key => $arrayitem){
        if ($arrayitem->no > 1){
            $arrayitem->qtypestring = $qtypemenu[$arrayitem->qtype];
            $grammarised[$key] = get_string('requiredplural', 'qcreate', $arrayitem);
        } else {
            $arrayitem->qtypestring = $qtypemenu[$arrayitem->qtype];
            $grammarised[$key] = get_string('requiredsingular', 'qcreate', $arrayitem);
        }
    }
    return $grammarised;
}
function qcreate_proper_punctuation($arrayitems){
    $i = 1;
    $listitems = array();
    foreach ($arrayitems as $key => $arrayitem){
        //all but last and last but one items
        if ($i < (count($arrayitems)-1)){
            $listitems[$key] = get_string('comma', 'qcreate', $arrayitem);
        }
        //last but one item
        if ($i == (count($arrayitems)-1)){
            $listitems[$key] = get_string('and', 'qcreate', $arrayitem);
        }
        if ($i == (count($arrayitems))){
            //last item
            $listitems[$key] = get_string('fullstop', 'qcreate', $arrayitem);
        }
        $i++;
    }
    return $listitems;
}
function qcreate_questions_of_type($questions){
    $questionsofqtype = array();
    if ($questions){
        foreach ($questions as $key => $question){
            $questionsofqtype[$question->qtype][] = $question;
        }
    }
    return $questionsofqtype;
}
?>
