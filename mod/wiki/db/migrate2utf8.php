<?php 

/**
 * This file contains wiki UTF-8 content migration process
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: migrate2utf8.php,v 1.4 2007/05/21 10:46:40 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Setup
 */

function migrate2utf8_wiki_name($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->name, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->name = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_pages_content($recordid){
    global $CFG, $globallang;
	
/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT w.course
           FROM {$CFG->prefix}wiki w,
                {$CFG->prefix}wiki_pages wp
           WHERE w.id = wp.dfwiki
                 AND wp.id = $recordid";

	if (!$wiki = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }
	
	if (!$wikipages = get_record('wiki_pages','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }
	
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wikipages->content, $fromenc);

        $newwikipages = new object;
        $newwikipages->id = $recordid;
        $newwikipages->content = $result;
        migrate2utf8_update_record('wiki_pages',$newwikipages);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_pages_evaluation($recordid){
    global $CFG, $globallang;
	
/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT w.course
           FROM {$CFG->prefix}wiki w,
                {$CFG->prefix}wiki_pages wp
           WHERE w.id = wp.dfwiki
                 AND wp.id = $recordid";

	if (!$wiki = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }
	
	if (!$wikipages = get_record('wiki_pages','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }
	
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wikipages->evaluation, $fromenc);

        $newwikipages = new object;
        $newwikipages->id = $recordid;
        $newwikipages->evaluation = $result;
        migrate2utf8_update_record('wiki_pages',$newwikipages);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_intro($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->intro, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->intro = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_pagename($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->pagename, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->pagename = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_evaluation($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->evaluation, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->evaluation = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_notetype($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->notetype, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->notetype = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_pages_author($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT w.course
           FROM {$CFG->prefix}wiki w,
                {$CFG->prefix}wiki_pages wp
           WHERE w.id = wp.dfwiki
                 AND wp.id = $recordid";

    if (!$wiki = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wikipages = get_record('wiki_pages','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wikipages->author, $fromenc);

        $newwikipages = new object;
        $newwikipages->id = $recordid;
        $newwikipages->author = $result;
        migrate2utf8_update_record('wiki_pages',$newwikipages);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_pages_refs($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT w.course
           FROM {$CFG->prefix}wiki w,
                {$CFG->prefix}wiki_pages wp
           WHERE w.id = wp.dfwiki
                 AND wp.id = $recordid";

    if (!$wiki = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wikipages = get_record('wiki_pages','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wikipages->refs, $fromenc);

        $newwikipages = new object;
        $newwikipages->id = $recordid;
        $newwikipages->refs = $result;
        migrate2utf8_update_record('wiki_pages',$newwikipages);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_synonymous_syn($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT w.course
           FROM {$CFG->prefix}wiki w,
                {$CFG->prefix}wiki_synonymous ws
           WHERE w.id = ws.dfwiki
                 AND ws.id = $recordid";

    if (!$wiki = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wikisynonymous = get_record('wiki_synonymous','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wikisynonymous->syn, $fromenc);

        $newwikisynonymous = new object;
        $newwikisynonymous->id = $recordid;
        $newwikisynonymous->syn = $result;
        migrate2utf8_update_record('wiki_synonymous',$newwikisynonymous);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_synonymous_original($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT w.course
           FROM {$CFG->prefix}wiki w,
                {$CFG->prefix}wiki_synonymous ws
           WHERE w.id = ws.dfwiki
                 AND ws.id = $recordid";

    if (!$wiki = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wikisynonymous = get_record('wiki_synonymous','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wikisynonymous->original, $fromenc);

        $newwikiwikisynonymous = new object;
        $newwikiwikisynonymous->id = $recordid;
        $newwikiwikisynonymous->original = $result;
        migrate2utf8_update_record('wiki_synonymous',$newwikiwikisynonymous);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_votes_pagename($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT w.course
           FROM {$CFG->prefix}wiki w,
                {$CFG->prefix}wiki_votes ws
           WHERE w.id = ws.dfwiki
                 AND ws.id = $recordid";

    if (!$wiki = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wikivotes = get_record('wiki_votes','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wikivotes->pagename, $fromenc);

        $newwikiwikivotes = new object;
        $newwikiwikivotes->id = $recordid;
        $newwikiwikivotes->pagename = $result;
        migrate2utf8_update_record('wiki_votes',$newwikiwikivotes);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_votes_username($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT w.course
           FROM {$CFG->prefix}wiki w,
                {$CFG->prefix}wiki_votes ws
           WHERE w.id = ws.dfwiki
                 AND ws.id = $recordid";

    if (!$wiki = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wikivotes = get_record('wiki_votes','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wikivotes->username, $fromenc);

        $newwikiwikivotes = new object;
        $newwikiwikivotes->id = $recordid;
        $newwikiwikivotes->username = $result;
        migrate2utf8_update_record('wiki_votes',$newwikiwikivotes);
    }
/// And finally, just return the converted field
    return $result;
}

?>
