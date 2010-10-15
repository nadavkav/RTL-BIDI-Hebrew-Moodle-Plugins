<?php // $Id: exportgood.php,v 1.4 2008/12/02 09:49:53 jamiesensei Exp $
/**
 * Export questions in the given category and which have been assigned a grade
 * above a certain level.
 *
 * @author Martin Dougiamas, Howard Miller, Jamie Pratt and many others.
 *         {@link http://moodle.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

    require_once("../../config.php");
    require_once($CFG->dirroot."/question/editlib.php");
    require_once("export_good_questions_form.php");

    list($thispageurl, $contexts, $cmid, $cm, $qcreate, $pagevars) = question_edit_setup('export', true);

    if (!has_capability('moodle/question:viewmine', $contexts->lowest()) && !has_capability('moodle/question:viewall', $contexts->lowest())) {
        $capabilityname = get_capability_string('moodle/question:viewmine');
        print_error('nopermissions', '', '', $capabilityname);
    }

    // get display strings
    $txt = new object;
    $txt->category = get_string('category', 'quiz');
    $txt->download = get_string('download', 'quiz');
    $txt->downloadextra = get_string('downloadextra', 'quiz');
    $txt->exporterror = get_string('exporterror', 'quiz');
    $txt->exportname = get_string('exportname', 'quiz');
    $txt->exportquestions = get_string('exportquestions', 'quiz');
    $txt->fileformat = get_string('fileformat', 'quiz');
    $txt->exportcategory = get_string('exportcategory', 'quiz');
    $txt->modulename = get_string('modulename', 'quiz');
    $txt->modulenameplural = get_string('modulenameplural', 'quiz');
    $txt->tofile = get_string('tofile', 'quiz');



    // make sure we are using the user's most recent category choice
    if (empty($categoryid)) {
        $categoryid = $pagevars['cat'];
    }

    // ensure the files area exists for this course
    make_upload_directory("$COURSE->id");
    list($catid, $catcontext) = explode(',', $pagevars['cat']);
    if (!$category = get_record("question_categories", "id", $catid, 'contextid', $catcontext)) {
        print_error('nocategory','quiz');
    }

    /// Header
    $strupdatemodule = has_capability('moodle/course:manageactivities', $contexts->lowest())
        ? update_module_button($cm->id, $COURSE->id, get_string('modulename', $cm->modname))
        : "";
    $navlinks = array();
    $navlinks[] = array('name' => get_string('modulenameplural', $cm->modname), 'link' => "$CFG->wwwroot/mod/{$cm->modname}/index.php?id=$COURSE->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($qcreate->name), 'link' => "$CFG->wwwroot/mod/{$cm->modname}/view.php?id={$cm->id}", 'type' => 'title');
    $navlinks[] = array('name' => $txt->exportquestions, 'link' => '', 'type' => 'title');
    $navigation = build_navigation($navlinks);
    print_header_simple($txt->exportquestions, '', $navigation, "", "", true, $strupdatemodule);

    $currenttab = 'edit';
    $mode = 'exportgood';
    include($CFG->dirroot."/mod/$cm->modname/tabs.php");

    $exportfilename = default_export_filename($COURSE, $category);
    $export_form = new question_export__good_questions_form($thispageurl, array('contexts'=>array($contexts->lowest()), 'defaultcategory'=>$pagevars['cat'],
                                    'defaultfilename'=>$exportfilename, 'qcreate'=>$qcreate));


    if ($from_form = $export_form->get_data()) {   /// Filename


        if (! is_readable($CFG->dirroot."/question/format/$from_form->format/format.php")) {
            error("Format not known ($from_form->format)");
        }

        // load parent class for import/export
        require_once($CFG->dirroot."/question/format.php");

        // and then the class for the selected format
        require_once($CFG->dirroot."/question/format/$from_form->format/format.php");

        $classname = "qformat_$from_form->format";
        $qformat = new $classname();
        $qformat->setContexts($contexts->having_one_edit_tab_cap('export'));

        $questions = get_questions_category($category, true );
        if ($qcreate->graderatio != 100 && $from_form->betterthangrade != 0){
            //filter questions by grade
            $qkeys = array();
            foreach ($questions as $question){
                $qkeys[] = $question->id;
            }
            $questionlist = join($qkeys, ',');
            $sql = 'SELECT questionid, grade FROM '.$CFG->prefix.'qcreate_grades '.
                                    'WHERE questionid IN ('.$questionlist.') AND grade >= '.$from_form->betterthangrade;
            if ($goodquestions = get_records_sql($sql)){
                foreach($questions as $zbkey => $question){
                    if (!array_key_exists($question->id, $goodquestions)){
                        unset($questions[$zbkey]);
                    }
                }
            } else {
                $a = new object();
                $a->betterthan = $from_form->betterthangrade;
                $a->categoryname = $category->name;
                notice(get_string('noquestionsabove', 'qcreate', $a));
            }
        }
        
        if (isset($from_form->naming)){
            if (isset($from_form->naming['firstname'])||
                isset($from_form->naming['lastname'])||
                isset($from_form->naming['username'])){
                $useridkeys = array();
                foreach ($questions as $question){
                    $useridkeys[] = $question->createdby;
                }
                $useridlist = join($useridkeys, ',');
                if (!$users = get_records_select('user', "id IN ($useridlist)")){
                    $users = array();
                }
            }
            foreach ($questions as $question){
                $prefixes = array();
                if (isset($from_form->naming['other'])&& !empty($from_form->naming['othertext'])){
                    $prefixes[] = $from_form->naming['othertext'];
                }
                if (isset($from_form->naming['firstname'])){
                    $prefixes[] = isset($users[$question->createdby])?$users[$question->createdby]->firstname:'';
                }
                if (isset($from_form->naming['lastname'])){
                    $prefixes[] = isset($users[$question->createdby])?$users[$question->createdby]->lastname:'';
                }
                if (isset($from_form->naming['username'])){
                    $prefixes[] = isset($users[$question->createdby])?$users[$question->createdby]->username:'';
                }
                if (isset($from_form->naming['activityname'])){
                    $prefixes[] = $qcreate->name;
                }
                if (isset($from_form->naming['timecreated'])){
                    $prefixes[] = userdate($question->timecreated, get_string('strftimedatetimeshort'));
                }
                $prefixes[] = $question->name;
                $question->name = join($prefixes, '-');
            }
        }

        
        $qformat->setQuestions($questions);

        $qformat->setCourse($COURSE);

        if (empty($from_form->exportfilename)) {
            $from_form->exportfilename = default_export_filename($COURSE, $category);
        }
        $qformat->setFilename($from_form->exportfilename);
        $qformat->setCattofile(!empty($from_form->cattofile));
        $qformat->setContexttofile(!empty($from_form->contexttofile));

        if (! $qformat->exportpreprocess()) {   // Do anything before that we need to
            error($txt->exporterror, $thispageurl->out());
        }

        if (! $qformat->exportprocess()) {         // Process the export data
            error($txt->exporterror, $thispageurl->out());
        }

        if (! $qformat->exportpostprocess()) {                    // In case anything needs to be done after
            error($txt->exporterror, $thispageurl->out());
        }
        echo "<hr />";

        // link to download the finished file
        $file_ext = $qformat->export_file_extension();
        if ($CFG->slasharguments) {
          $efile = "{$CFG->wwwroot}/file.php/".$qformat->question_get_export_dir()."/$from_form->exportfilename".$file_ext."?forcedownload=1";
        }
        else {
          $efile = "{$CFG->wwwroot}/file.php?file=/".$qformat->question_get_export_dir()."/$from_form->exportfilename".$file_ext."&forcedownload=1";
        }
        echo "<p><div class=\"boxaligncenter\"><a href=\"$efile\">$txt->download</a></div></p>";
        echo "<p><div class=\"boxaligncenter\"><font size=\"-1\">$txt->downloadextra</font></div></p>";

        print_continue($thispageurl->out());
        print_footer($COURSE);
        exit;
    }

    /// Display export form


    print_heading_with_help($txt->exportquestions, 'export', 'quiz');

    $export_form->display();

    print_footer($COURSE);
?>
