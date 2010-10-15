<?php // $Id: migrate2utf8.php,v 1.2 2006/12/12 07:08:01 janne Exp $

function migrate2utf8_netpublish_intro($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$netpublish = get_record('netpublish','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($netpublish->course);  //Non existing!
        $userlang   = get_main_teacher_lang($netpublish->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($netpublish->intro, $fromenc);

        $newpublish = new object;
        $newpublish->id = $recordid;
        $newpublish->intro = $result;
        migrate2utf8_update_record('netpublish',$newpublish);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_netpublish_name($recordid) {
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$netpublish = get_record('netpublish','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($netpublish->course);  //Non existing!
        $userlang   = get_main_teacher_lang($netpublish->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($netpublish->name, $fromenc);

        $newpublish = new object;
        $newpublish->id = $recordid;
        $newpublish->name = $result;
        migrate2utf8_update_record('netpublish',$newpublish);
    }
/// And finally, just return the converted field
    return $result;
}

?>
