<?php // $Id: filter.php,v 1.38.2.5 2008/07/07 17:38:42 skodak Exp $
//////////////////////////////////////////////////////////////
//  Question plugin filtering
//
//  This filter will replace any links to a question preview with
//  a iframe in which the question preview will appear.
//
//  To activate this filter, add a line like this to your
//  list of filters in your Filter configuration:
//
//  filter/question/filter.php
//  and copy all the accsesory files in the question folder
//
//  to use the filter:
//  copy a preview of a question (right click the zoom icon on the 
//  right side of the question in the question bank view and copy 
//  the link to the preview)
//  and paste it into a link dialog in any resource or activity.
//  add ".qst" to the end of the link (so the filter can recognize it)
//
//  todo: 
//	find a better regexp to filter the question without the need to add ".qst" to the link
//	make the question be a live question and not just a preview for practice !
//
//	please, feedback me : nadavkav ET netvision DooT net DooT il
//////////////////////////////////////////////////////////////

/// This is the filtering function itself.  It accepts the
/// courseid and the text to be filtered (in HTML form).

require_once($CFG->libdir.'/filelib.php');


function question_filter($courseid, $text) {
    global $CFG;

    if (!is_string($text)) {
        // non string data can not be filtered anyway
        return $text;
    }
    $newtext = $text; // fullclone is slow and not needed here

    if ($CFG->filter_question_plugin_enable) {
        $search = '/<a.*?href="([^<]+\.qst)"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'question_plugin_filter_callback', $newtext);
    }

    if (is_null($newtext) or $newtext === $text) {
        // error or not filtered
        return $text;
    }
    
     return $newtext;
}

///===========================
/// callback filter functions


function question_plugin_filter_callback($link) {
    global $CFG;

    static $count = 0;
    $count++;
    $id = 'filter_question_'.time().$count; //we need something unique because it might be stored in text cache

    $url = addslashes_js($link[1]);

    return $link[0].'<iframe id='.$id.' src="'.$url.'" width="60%"></iframe>';

}


?>
