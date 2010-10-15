<?php // $Id: drafts.html.php,v 1.2.4.1 2007/09/24 08:46:22 janne Exp $

    if  (! defined('MOODLE_INTERNAL')) die ("This page cannot be viewed independetly");

    $strarticle  = get_string('article','netpublish');
    $stractions  = get_string('actions','netpublish');
    $strauthor   = get_string('author','netpublish');
    $strstatus   = get_string('status','netpublish');
    $strcreated  = get_string('created','netpublish');
    $strmodified = get_string('modified','netpublish');
    $strcreated  = get_string('created','netpublish');
    $strassigned = get_string('assignedto','netpublish');

    $tbl = new stdClass;
    $tbl->head = array($strarticle, $stractions, $strauthor, $strassigned,
                       $strstatus, $strcreated,$strmodified);

    $tbl->width  = "100%";
    $tbl->align  = array("left","center","left","center","center","center");
    $tbl->nowrap = array("nowrap","nowrap","nowrap","","","");
    $tbl->data   = array();

    $mine   = array();
    $others = array();

    if (! empty($articles) ) {

        $formcount = 1;
        foreach ($articles as $article) {

            $rights  = netpublish_get_rights($article->rights);
            $canread = !empty($rights[$USER->id]) ? $nperm->can_read($rights[$USER->id]) : 0;

            $newrow = array();

            if (has_capability('moodle/legacy:editingteacher', $coursecontext) ||
                $canread || (intval($article->userid) == intval($USER->id))) {
                $newrow[] = "<a href=\"preview.php?id=$cm->id&amp;article=$article->id&amp;".
                            "status=$article->statusid\">$article->title</a>";
            } else {
                $newrow[] = $article->title;
            }

            $newrow[] = netpublish_print_actionbuttons($cm, $article, $USER->id, $course->id, true, true);

            if (! empty($article->authors) ) {
                $newrow[] = fullname($article) .' '. netpublish_print_authors($article->authors, true);
            } else {
                $newrow[] = fullname($article);
            }

            $teacher = new stdClass;
            $teacher->firstname = $article->tfirstname;
            $teacher->lastname  = $article->tlastname;
            $newrow[] = fullname($teacher);

            unset($teacher);

            if ( has_capability('moodle/legacy:editingteacher', $coursecontext) ) {
                $newrow[] = "<form id=\"frm_$formcount\" name=\"frm_$formcount\"" .
                            " method=\"post\" action=\"drafts.php\">\n" .
                            "<input type=\"hidden\" name=\"id\" value=\"". $cm->id ."\" />\n" .
                            "<input type=\"hidden\" name=\"articleid\" value=\"". $article->id ."\" />\n" .
                            "<input type=\"hidden\" name=\"sesskey\" value=\"". $USER->sesskey ."\" />\n" .
                            netpublish_print_status_list ("statusid", $publish, $article->statusid, "onchange=\"send_form('frm_$formcount');\"", true) .
                            "</form>\n";
            } else {
                $newrow[] = $article->status;
            }

            $newrow[] = userdate($article->timecreated, "%x %X");
            $newrow[] = userdate($article->timemodified, "%x %X");

            if (has_capability('moodle/legacy:editingteacher', $coursecontext)) {
                if (intval($article->teacherid) != intval($USER->id) &&
                    intval($article->userid) != intval($USER->id)) {
                    array_push($others, $newrow);
                } else {
                    array_push($mine, $newrow);
                }
            }

            if (has_capability('moodle/legacy:student', $coursecontext)) {
                if (intval($article->userid) != intval($USER->id)) {
                    array_push($others, $newrow);
                } else {
                    array_push($mine, $newrow);
                }
            }

            //array_push($tbl->data, $newrow);

            clearstatcache();
            $formcount++;

        }

        unset($formcount, $newrow);

    }

    include_once('drafttabs.php');

    if ( empty($tab) or $tab == 1 ) {
        $tbl->data = !empty($mine) ? $mine : array();
    } else {
        $tbl->data = !empty($others) ? $others : array();
    }

    print_table($tbl);

?>
<script type="text/javascript">
//<![CDATA[
function send_form (inForm) {

    var frm = document.getElementById(inForm);

    var state = confirm("<?php print_string("changearticlestatusconfirm","netpublish");?>");
    if (state) {
        frm.submit();
    } else {
        return false;
    }

}
//]]>
</script>