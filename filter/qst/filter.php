<?php //$Id: filter.php,v 1.6 2007/09/23 17:12:30 stronk7 Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 2001-3001 Martin Dougiamas        http://dougiamas.com  //
//           (C) 2001-3001 Eloy Lafuente (stronk7) http://contiento.com  //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

// This filter allows you to display a single question from
// the "question bank".
//
// Syntax for videos is:
//     [[q:course_id:question_id:height:width|title]]
// where:
//     q:        acronym of "question", must be always present.
//     course_id:    you can see it when you hover with the mouse pointer over the
//                         question's preview icon in the quesion bank page.
//     question_id:    you can see it when you hover with the mouse pointer over the
//                         question's preview icon in the quesion bank page.
//     height : height of the frame that is embeded inside the resource's page.
//     width : width of the frame that is embeded inside the resource's page.
//     title:     free text to be displayed before the quesiotn's frame
//

function qst_filter($courseid, $text) {

    $u = empty($CFG->unicodedb) ? '' : 'u'; //Unicode modifier

    preg_match_all('/\[\[q:(.*?):(.*?):(.*?):(.*?)(\|(.*?))\]\]/s'.$u, $text, $list_question);

/// No question links found. Return original text
    if (empty($list_question[0])) {
        return $text;
    }

    foreach ($list_question[0] as $key=>$item) {
        $replace = '';
    /// Extract info from the question link
        $question = new stdClass;
        $question->courseid = $list_question[1][$key];
        $question->id = $list_question[2][$key];
	$question->height = $list_question[3][$key];
	$question->width = $list_question[4][$key];
        $question->title = $list_question[6][$key];
    /// Calculate footer text (it's optional in the filter)
        if ($question->title) {
            $footertext = '<br /><span class="$question-title">'.format_string($question->title).'</span>';
        } else {
            $footertext = '';
        }

    $replace = '<div>'.$footertext.'</div><iframe src="/moodle/question/preview-qst.php?id='.$question->id.'&courseid='.$question->courseid.'" width="'.$question->width.'" height="'.$question->height.'"></iframe>';


    /// If replace found, do it
        if ($replace) {
            $text = str_replace($list_question[0][$key], $replace, $text);
        }
    }

/// Finally, return the text
    return $text;
}
?>
