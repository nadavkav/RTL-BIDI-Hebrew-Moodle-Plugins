<?php
// set javascript header
require_js($CFG->wwwroot . '/course/format/project/v2uploader/v2uploader.js');

/**
 * Put uploader plugin
 * 
 * @param    string  $objectid    // divタグのID
 * @param    int     $courseid    // コースid
 * @param    int        $section    // セクションID
 * @param    bool    $isregister    // T:セクションに登録, F:セクションには登録しない
 */
function v2uploader_put_plugin($objectid, $courseid = 0, $sectionid = 0, $isregister = false, $targetId) {
    global $CFG;
    
    echo("<script type=\"text/javascript\">uploader.swf_source='{$CFG->wwwroot}/course/format/project/v2uploader/projectupload.swf?inipath={$CFG->wwwroot}/course/format/project/v2uploader/';</script>");
    echo("<script type=\"text/javascript\">uploader.swf_element='{$objectid}';</script>");
    echo("<script type=\"text/javascript\">var param = {};");
    if ($courseid >0) echo("param['courseid']={$courseid};");
    if ($sectionid > 0) echo("param['sectionid']={$sectionid};");
    echo("param['isregister']=" . ($isregister?'1':'0') . ";");
    echo("param['" . session_name() . "']='" . session_id() . "';"); 
    echo("</script>");
    
    echo("<script type=\"text/javascript\">uploader.putUploader({$sectionid},'sesskey','" . sesskey() . "',param, " . $targetId . ");</script>");
}
?>