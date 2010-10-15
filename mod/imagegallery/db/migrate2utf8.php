<?php // $Id: migrate2utf8.php,v 1.1 2006/10/09 21:31:58 janne Exp $
function migrate2utf8_imagegallery_images_description($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT ig.*
           FROM {$CFG->prefix}imagegallery ig,
                {$CFG->prefix}imagegallery_images igi
           WHERE ig.id = igi.galleryid
                 AND igi.id = $recordid";

    if (!$gallery = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$gallerytext = get_record('imagegallery_images','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($gallery->course);  //Non existing!
        $userlang   = get_main_teacher_lang($gallery->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($gallerytext->description, $fromenc);

        $newtext = new object;
        $newtext->id = $recordid;
        $newtext->description = $result;
        migrate2utf8_update_record('imagegallery_images',$newtext);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_imagegallery_categories_description($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT ig.*
           FROM {$CFG->prefix}imagegallery ig,
                {$CFG->prefix}imagegallery_categories igi
           WHERE ig.id = igi.galleryid
                 AND igi.id = $recordid";

    if (!$gallery = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$gallerytext = get_record('imagegallery_categories','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($gallery->course);  //Non existing!
        $userlang   = get_main_teacher_lang($gallery->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($gallerytext->description, $fromenc);

        $newtext = new object;
        $newtext->id = $recordid;
        $newtext->description = $result;
        migrate2utf8_update_record('imagegallery_categories',$newtext);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_imagegallery_categories_name($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    $SQL = "SELECT ig.*
           FROM {$CFG->prefix}imagegallery ig,
                {$CFG->prefix}imagegallery_categories igi
           WHERE ig.id = igi.galleryid
                 AND igi.id = $recordid";

    if (!$gallery = get_record_sql($SQL)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$catname = get_record('imagegallery_categories','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($gallery->course);  //Non existing!
        $userlang   = get_main_teacher_lang($gallery->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($catname->name, $fromenc);

        $newtext = new object;
        $newtext->id = $recordid;
        $newtext->name = $result;
        migrate2utf8_update_record('imagegallery_categories',$newtext);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_imagegallery_intro($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if (!$gallery = get_record('imagegallery', 'id', $recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($gallery->course);  //Non existing!
        $userlang   = get_main_teacher_lang($gallery->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($gallery->intro, $fromenc);

        $newgallery = new object;
        $newgallery->id = $recordid;
        $newgallery->intro = $result;
        migrate2utf8_update_record('imagegallery',$newgallery);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_imagegallery_name($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if (!$gallery = get_record('imagegallery', 'id', $recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($gallery->course);  //Non existing!
        $userlang   = get_main_teacher_lang($gallery->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities

/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($gallery->name, $fromenc);

        $newgallery = new object;
        $newgallery->id = $recordid;
        $newgallery->name = $result;
        migrate2utf8_update_record('imagegallery',$newgallery);
    }
/// And finally, just return the converted field
    return $result;
}

?>
