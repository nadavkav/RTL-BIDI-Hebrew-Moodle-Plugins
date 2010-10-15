<?php  // $Id: tasks.php,v 1.3 2007/09/09 09:00:20 stronk7 Exp $
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");


    $id     = required_param('id', PARAM_INT);    // Course Module ID, or
    $a      = optional_param('a', '', PARAM_ALPHA);
    $action = optional_param('action', '', PARAM_ALPHA);
    $cancel = optional_param('cancel');

    $timenow = time();
    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $webquest = get_record("webquest", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $webquest = get_record("webquest", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $webquest->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("webquest", $webquest->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }
    require_login($course->id, false, $cm);

    $strtasks = get_string("tasks", "webquest");
    $strwebquest =  get_string("modulename", "webquest");
    $strwebquests =  get_string("modulenameplural", "webquest");

    print_header_simple(format_string($webquest->name), "",
                 "<a href=\"index.php?id=$course->id\">$strwebquests</a> ->
                  <a href=\"view.php?id=$cm->id\">".format_string($webquest->name,true)."</a> -> $strtasks",
                  "", "", true);



    add_to_log($course->id, "webquest", "update tasks", "view.php?id=$cm->id", "$webquest->id");

    $straction = ($action) ? '-> '.get_string($action, 'webquest') : '';

    //*********************************************edit tasks**********************/////
    if ($action == 'edittasks'){
        if (!isteacher($course->id)) {
            error("Only teachers can look at this page"); /// is trying to get access but not allowed jejejeje
        }
        $count = count_records("webquest_grades", "webquestid", $webquest->id);
        if ($count) {
            notify(get_string("warningtask", "webquest"));
        }
     ///// setup a form to edit tasks
        print_heading_with_help(get_string("edittasks", "webquest"), "tasks", "webquest");
     ?>
        <form name="form" method="post" action="tasks.php">
        <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
        <input type="hidden" name="action" value="inserttasks" />
        <center><table cellpadding="5" border="1">
        <?php

        // get existing tasks, if none set up appropriate default ones
        if ($tasksraw = get_records("webquest_tasks", "webquestid", $webquest->id, "taskno ASC" )) {
            foreach ($tasksraw as $task) {
                $tasks[] = $task;   // to renumber index 0,1,2...
            }
        }
        // check for missing tasks (this happens either the first time round or when the number of tasks is increased)
        for ($i=0; $i<$webquest->ntasks; $i++) {
            if (!isset($tasks[$i])) {
                $tasks[$i]->description = '';
                $tasks[$i]->scale =0;
                $tasks[$i]->maxscore = 0;
                $tasks[$i]->weight = 11;
            }
        }

        switch ($webquest->gradingstrategy) {
            case 0: // no grading
                for ($i=0; $i<$webquest->ntasks; $i++) {
                    $iplus1 = $i+1;
                    echo "<tr valign=\"top\">\n";
                    echo "  <td align=\"right\"><b>". get_string("task","webquest")." $iplus1:</b></td>\n";
                    echo "<td><textarea name=\"description[]\" rows=\"3\" cols=\"75\">".$tasks[$i]->description."</textarea>\n";
                    echo "  </td></tr>\n";
                    echo "<tr valign=\"top\">\n";
                    echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
                    echo "</tr>\n";
                }
                break;

            case 1: // accumulative grading
                // set up scales name
                foreach ($WEBQUEST_SCALES as $KEY => $SCALE) {
                    $SCALES[] = $SCALE['name'];
                }
                for ($i=0; $i<$webquest->ntasks; $i++) {
                    $iplus1 = $i+1;
                    echo "<tr valign=\"top\">\n";
                    echo "  <td align=\"right\"><b>". get_string("task","webquest")." $iplus1:</b></td>\n";
                    echo "<td><textarea name=\"description[]\" rows=\"3\" cols=\"75\">".$tasks[$i]->description."</textarea>\n";
                    echo "  </td></tr>\n";
                    echo "<tr valign=\"top\">\n";
                    echo "  <td align=\"right\"><b>". get_string("typeofscale", "webquest"). ":</b></td>\n";
                    echo "<td valign=\"top\">\n";
                    choose_from_menu($SCALES, "scale[]", $tasks[$i]->scale, "");
                    if ($tasks[$i]->weight == '') { // not set
                        $tasks[$i]->weight = 11; // unity
                    }
                    echo "</td></tr>\n";
                    echo "<tr valign=\"top\"><td align=\"right\"><b>".get_string("taskweight", "webquest").":</b></td><td>\n";
                    webquest_choose_from_menu($WEBQUEST_EWEIGHTS, "weight[]", $tasks[$i]->weight, "");
                    echo "      </td>\n";
                    echo "</tr>\n";
                    echo "<tr valign=\"top\">\n";
                    echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
                    echo "</tr>\n";
                }
                break;

            case 2: // error banded grading
                for ($i=0; $i<$webquest->ntasks; $i++) {
                    $iplus1 = $i+1;
                    echo "<tr valign=\"top\">\n";
                    echo "  <td align=\"right\"><b>". get_string("task","webquest")." $iplus1:</b></td>\n";
                    echo "<td><textarea name=\"description[$i]\" rows=\"3\" cols=\"75\">".$tasks[$i]->description."</textarea>\n";
                    echo "  </td></tr>\n";
                    if ($tasks[$i]->weight == '') { // not set
                        $tasks[$i]->weight = 11; // unity
                        }
                    echo "</tr>\n";
                    echo "<tr valign=\"top\"><td align=\"right\"><b>".get_string("taskweight", "webquest").":</b></td><td>\n";
                    webquest_choose_from_menu($WEBQUEST_EWEIGHTS, "weight[]", $tasks[$i]->weight, "");
                    echo "      </td>\n";
                    echo "</tr>\n";
                    echo "<tr valign=\"top\">\n";
                    echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
                    echo "</tr>\n";
                }
                echo "</center></table><br />\n";
                echo "<center><b>".get_string("gradetable","webquest")."</b></center>\n";
                echo "<center><table cellpadding=\"5\" border=\"1\"><tr><td align=\"CENTER\">".
                    get_string("numberofnegativeresponses", "webquest");
                echo "</td><td>". get_string("suggestedgrade", "webquest")."</td></tr>\n";
                for ($j = $webquest->grade; $j >= 0; $j--) {
                    $numbers[$j] = $j;
                }
                for ($i=0; $i<=$webquest->ntasks; $i++) {
                    echo "<tr><td align=\"CENTER\">$i</td><td align=\"CENTER\">";
                    if (!isset($tasks[$i])) {  // the "last one" will be!
                        $tasks[$i]->description = "";
                        $tasks[$i]->maxscore = 0;
                    }
                    choose_from_menu($numbers, "maxscore[$i]", $tasks[$i]->maxscore, "");
                    echo "</td></tr>\n";
                }
                echo "</table>\n";
                break;

            case 3: // criterion grading
                for ($j = 100; $j >= 0; $j--) {
                    $numbers[$j] = $j;
                }
                for ($i=0; $i<$webquest->ntasks; $i++) {
                    $iplus1 = $i+1;
                    echo "<tr valign=\"top\">\n";
                    echo "  <td align=\"right\"><b>". get_string("criterion","webquest")." $iplus1:</b></td>\n";
                    echo "<td><textarea name=\"description[$i]\" rows=\"3\" cols=\"75\">".$tasks[$i]->description."</textarea>\n";
                    echo "  </td></tr>\n";
                    echo "<tr><td><b>". get_string("suggestedgrade", "webquest").":</b></td><td>\n";
                    webquest_choose_from_menu($numbers, "maxscore[$i]", $tasks[$i]->maxscore, "");
                    echo "</td></tr>\n";
                    echo "<tr valign=\"top\">\n";
                    echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
                    echo "</tr>\n";
                }
                break;

            case 4: // rubric
                for ($j = 100; $j >= 0; $j--) {
                    $numbers[$j] = $j;
                }
                if ($rubricsraw = get_records("webquest_rubrics", "webquestid", $webquest->id)) {
                    foreach ($rubricsraw as $rubric) {
                        $rubrics[$rubric->taskno][$rubric->rubricno] = $rubric->description;   // reindex 0,1,2...
                    }
                }
                for ($i=0; $i<$webquest->ntasks; $i++) {
                    $iplus1 = $i+1;
                    echo "<tr valign=\"top\">\n";
                    echo "  <td align=\"right\"><b>". get_string("task","webquest")." $iplus1:</b></td>\n";
                    echo "<td><textarea name=\"description[$i]\" rows=\"3\" cols=\"75\">".$tasks[$i]->description."</textarea>\n";
                    echo "  </td></tr>\n";
                    echo "<tr valign=\"top\"><td align=\"right\"><b>".get_string("taskweight", "webquest").":</b></td><td>\n";
                    webquest_choose_from_menu($WEBQUEST_EWEIGHTS, "weight[]", $tasks[$i]->weight, "");
                    echo "      </td>\n";
                    echo "</tr>\n";

                    for ($j=0; $j<5; $j++) {
                        $jplus1 = $j+1;
                        if (empty($rubrics[$i][$j])) {
                            $rubrics[$i][$j] = "";
                        }
                        echo "<tr valign=\"top\">\n";
                        echo "  <td align=\"right\"><b>". get_string("grade","webquest")." $j:</b></td>\n";
                        echo "<td><textarea name=\"rubric[$i][$j]\" rows=\"3\" cols=\"75\">".$rubrics[$i][$j]."</textarea>\n";
                        echo "  </td></tr>\n";
                        }
                    echo "<tr valign=\"top\">\n";
                    echo "  <td colspan=\"2\" class=\"webquestassessmentheading\">&nbsp;</td>\n";
                    echo "</tr>\n";
                    }
                break;
            }
        // close table and form

        ?>
        </table><br />
        <input type="submit" value="<?php  print_string("savechanges") ?>" />
        <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
        </center>
        </form>
        <?php

    }
 ///////////Insert tasks////////////////////////////
    elseif ($action == 'inserttasks') {

        if (!isteacher($course->id)) {
            error("Only teachers can look at this page"); ///not allowed if isn't a teacher
        }

        $form = data_submitted();
        if (isset($cancel)){
            redirect("view.php?id=$cm->id&amp;action=tasks");
        }
        // let's not fool around here, dump the junk!
        delete_records("webquest_tasks", "webquestid", $webquest->id);

        // determine wich type of grading
        switch ($webquest->gradingstrategy) {
            case 0: // no grading
                // Insert all the tasks that contain something
                foreach ($form->description as $key => $description) {
                    if ($description) {
                        unset($task);
                        $task->description   = $description;
                        $task->webquestid = $webquest->id;
                        $task->taskno = $key;
                        if (!$task->id = insert_record("webquest_tasks", $task)) {
                            error("Could not insert webquest task!");
                        }
                    }
                }
                break;

            case 1: // accumulative grading
                // Insert all the tasks that contain something
                foreach ($form->description as $key => $description) {
                    if ($description) {
                        unset($task);
                        $task->description   = $description;
                        $task->webquestid = $webquest->id;
                        $task->taskno = $key;
                        if (isset($form->scale[$key])) {
                            $task->scale = $form->scale[$key];
                            switch ($WEBQUEST_SCALES[$form->scale[$key]]['type']) {
                                case 'radio' :  $task->maxscore = $WEBQUEST_SCALES[$form->scale[$key]]['size'] - 1;
                                                        break;
                                case 'selection' :  $task->maxscore = $WEBQUEST_SCALES[$form->scale[$key]]['size'];
                                                        break;
                            }
                        }
                        if (isset($form->weight[$key])) {
                            $task->weight = $form->weight[$key];
                        }
                        if (!$task->id = insert_record("webquest_tasks", $task)) {
                            error("Could not insert webquest task!");
                        }
                    }
                }
                break;

            case 2: // error banded grading...
            case 3: // ...and criterion grading
                // Insert all the elements that contain something, the number of descriptions is one less than the number of grades
                foreach ($form->maxscore as $key => $themaxscore) {
                    unset($task);
                    $task->webquestid = $webquest->id;
                    $task->taskno = $key;
                    $task->maxscore = $themaxscore;
                    if (isset($form->description[$key])) {
                        $task->description   = $form->description[$key];
                    }
                    if (isset($form->weight[$key])) {
                        $task->weight = $form->weight[$key];
                    }
                    if (!$task->id = insert_record("webquest_tasks", $task)) {
                        error("Could not insert webquest task!");
                    }
                }
                break;

            case 4: // ...and criteria grading
                // Insert all the elements that contain something
                foreach ($form->description as $key => $description) {
                    unset($task);
                    $task->webquestid = $webquest->id;
                    $task->taskno = $key;
                    $task->description   = $description;
                    $task->weight = $form->weight[$key];
                    for ($j=0;$j<5;$j++) {
                        if (empty($form->rubric[$key][$j]))
                            break;
                    }
                    $task->maxscore = $j - 1;
                    if (!$task->id = insert_record("webquest_tasks", $task)) {
                        error("Could not insert webquest task!");
                    }
                }
                // let's not fool around here, dump the junk!
                delete_records("webquest_rubrics", "webquestid", $webquest->id);
                for ($i=0;$i<$webquest->ntasks;$i++) {
                    for ($j=0;$j<5;$j++) {
                        unset($task);
                        if (empty($form->rubric[$i][$j])) {  // OK to have an element with fewer than 5 items
                            break;
                        }
                        $task->webquestid = $webquest->id;
                        $task->taskno = $i;
                        $task->rubricno = $j;
                        $task->description   = $form->rubric[$i][$j];
                        if (!$task->id = insert_record("webquest_rubrics", $task)) {
                            error("Could not insert webquest task!");
                        }
                    }
                }
                break;
        } // end of switch
        if (!count_records("webquest_resources","webquestid",$webquest->id)){
            redirect("view.php?id=$cm->id&amp;action=process",get_string("wellsaved","webquest"));
        }
        else {
            redirect("view.php?id=$cm->id&amp;action=tasks", get_string("wellsaved","webquest"));
        }
    }

    print_footer($course);
?>