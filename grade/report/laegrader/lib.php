<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// CLAMP # 194 2010-06-23 bobpuffer


/**
 * File in which the grader_report class is defined.
 * @package gradebook
 */
 
require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once $CFG->dirroot.'/grade/report/laegrader/locallib.php';
require_once $CFG->dirroot.'/grade/report/grader/lib.php';

/**
 * Class providing an API for the grader report building and displaying.
 * @uses grade_report
 * @package gradebook
 */
class grade_report_laegrader extends grade_report_grader {
    /**
     * The final grades.
     * @var array $grades
     */
    var $grades;

    /**
     * Array of errors for bulk grades updating.
     * @var array $gradeserror
     */
    var $gradeserror = array();

//// SQL-RELATED

    /**
     * The id of the grade_item by which this report will be sorted.
     * @var int $sortitemid
     */
    var $sortitemid;

    /**
     * Sortorder used in the SQL selections.
     * @var int $sortorder
     */
    var $sortorder;

    /**
     * An SQL fragment affecting the search for users.
     * @var string $userselect
     */
    var $userselect;

    /**
     * List of collapsed categories from user preference
     * @var array $collapsed
     */
    var $collapsed;

    /**
     * A count of the rows, used for css classes.
     * @var int $rowcount
     */
    var $rowcount = 0;

    /**
     * Capability check caching
     * */
    var $canviewhidden;

    var $preferences_page=false;

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     */
    function grade_report_laegrader($courseid, $gpr, $context, $page=null, $sortitemid=null) {
        global $CFG;
        parent::grade_report($courseid, $gpr, $context, $page);

        $this->canviewhidden = has_capability('moodle/grade:viewhidden', get_context_instance(CONTEXT_COURSE, $this->course->id));
        $this->accuratetotals		= ($temp = grade_get_setting($this->courseid, 'report_laegrader_accuratetotals', $CFG->grade_report_laegrader_accuratetotals)) ? $temp : 0;

        // need this array, even tho its useless in the laegrader report or we'll generate warnings
        $this->collapsed = array('aggregatesonly' => array(), 'gradesonly' => array());

        if (empty($CFG->enableoutcomes)) {
            $nooutcomes = false;
        } else {
            $nooutcomes = get_user_preferences('grade_report_shownooutcomes');
        }

        // force category_last to true
        $switch = true;

        // Grab the grade_tree for this course
        $this->gtree = new grade_tree($this->courseid, false, $switch, null, $nooutcomes);

        // Fill items with parent information needed later
        $this->gtree->parents = array();
        fill_parents($this->gtree->parents, $this->gtree->items, $this->gtree->top_element, $this->gtree->top_element['object']->grade_item->id, $this->accuratetotals);
//        fill_parents($this->gtree->items, $this->gtree->top_element,$this->gtree->top_element['object']->id,$this->gtree->top_element['object']->aggregation);
                
        $this->sortitemid = $sortitemid;

        // base url for sorting by first/last name
        $studentsperpage = 0; //$this->get_pref('studentsperpage');
        $perpage = '';
        $curpage = '';

        $this->baseurl = 'index.php?id='.$this->courseid. $perpage.$curpage.'&amp;';

        $this->pbarurl = 'index.php?id='.$this->courseid.$perpage.'&amp;';

        $this->setup_groups();

        $this->setup_sortitemid();
    }

    /**
     * Processes the data sent by the form (grades and feedbacks).
     * Caller is reposible for all access control checks
     * @param array $data form submission (with magic quotes)
     * @return array empty array if success, array of warnings if something fails.
     */
    function process_data($data) {
        $warnings = array();

        $separategroups = false;
        $mygroups       = array();
        if ($this->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $this->context)) {
            $separategroups = true;
            $mygroups = groups_get_user_groups($this->course->id);
            $mygroups = $mygroups[0]; // ignore groupings
            // reorder the groups fro better perf bellow
            $current = array_search($this->currentgroup, $mygroups);
            if ($current !== false) {
                unset($mygroups[$current]);
                array_unshift($mygroups, $this->currentgroup);
            }
        }

        // always initialize all arrays
        $queue = array();
        foreach ($data as $varname => $postedvalue) {

            $oldvalue = $data->{'old'.$varname};

            // was change requested?
            /// BP: moved this up to speed up the process of eliminating unchanged values
            if ($oldvalue == $postedvalue) { // string comparison
                continue;
            }
            $needsupdate = false;

            // skip, not a grade nor feedback
            if (strpos($varname, 'grade') === 0) {
                $data_type = 'grade';
            } else if (strpos($varname, 'feedback') === 0) {
                $data_type = 'feedback';
            } else {
                continue;
            }

            $gradeinfo = explode("_", $varname);
            $userid = clean_param($gradeinfo[1], PARAM_INT);
            $itemid = clean_param($gradeinfo[2], PARAM_INT);

			// HACK: bob puffer call local object
            if (!$grade_item = grade_item::fetch(array('id'=>$itemid, 'courseid'=>$this->courseid))) { // we must verify course id here!
//            if (!$grade_item = grade_item_local::fetch(array('id'=>$itemid, 'courseid'=>$this->courseid))) { // we must verify course id here!
            	// END OF HACK
                error('Incorrect grade item id');
            }

            // Pre-process grade
            if ($data_type == 'grade') {
                $feedback = false;
                $feedbackformat = false;
                if ($grade_item->gradetype == GRADE_TYPE_SCALE) {
                    if ($postedvalue == -1) { // -1 means no grade
                        $finalgrade = null;
                    } else {
                        $finalgrade = $postedvalue;
                    }
                } else {
		    // HACK: bob puffer to allow calculating grades from input letters
                    $context = get_context_instance(CONTEXT_COURSE, $grade_item->courseid);

                    // percentage input
                    if (strpos($postedvalue, '%')) {
                        $percent = trim(substr($postedvalue, 0, strpos($postedvalue, '%')));
                        $postedvalue = $percent * .01 * $grade_item->grademax;
                    // letter input?
                    } elseif ($letters = grade_get_letters($context)) {
			unset($lastitem);
                        foreach ($letters as $used=>$letter) {
                            if (strtoupper($postedvalue) == $letter) {
                                if (isset($lastitem)) {
                                    $postedvalue = $lastitem;
                                } else {
                                    $postedvalue = $grade_item->grademax;
                                }
                                break;
                            } else {
                                    $lastitem = ($used - 1) * .01 * $grade_item->grademax;
                            }
                        }
                    } // END OF HACK
                    $finalgrade = unformat_float($postedvalue);
                }

                $errorstr = '';
                // Warn if the grade is out of bounds.
                if (is_null($finalgrade)) {
                    // ok
                } else {
                    $bounded = $grade_item->bounded_grade($finalgrade);
                    if ($bounded > $finalgrade) {
                        $errorstr = 'lessthanmin';
                    } else if ($bounded < $finalgrade) {
                        $errorstr = 'morethanmax';
                    }
                }
                if ($errorstr) {
                    $user = get_record('user', 'id', $userid, '', '', '', '', 'id, firstname, lastname');
                    $gradestr = new object();
                    $gradestr->username = fullname($user);
                    $gradestr->itemname = $grade_item->get_name();
                    $warnings[] = get_string($errorstr, 'grades', $gradestr);
                }

            } else if ($data_type == 'feedback') {
                $finalgrade = false;
                $trimmed = trim($postedvalue);
                if (empty($trimmed)) {
                     $feedback = NULL;
                } else {
                     $feedback = stripslashes($postedvalue);
                }
            }

            // group access control
            if ($separategroups) {
                // note: we can not use $this->currentgroup because it would fail badly
                //       when having two browser windows each with different group
                $sharinggroup = false;
                foreach($mygroups as $groupid) {
                    if (groups_is_member($groupid, $userid)) {
                        $sharinggroup = true;
                        break;
                    }
                }
                if (!$sharinggroup) {
                    // either group membership changed or somebedy is hacking grades of other group
                    $warnings[] = get_string('errorsavegrade', 'grades');
                    continue;
                }
            }

            $grade_item->update_final_grade($userid, $finalgrade, 'gradebook', $feedback, FORMAT_MOODLE);
        }

        return $warnings;
    }

    
    /**
     * Builds and returns the HTML code for the headers.
     * @return string $headerhtml
     */
    function get_headerhtml() {
        global $CFG, $USER;

//        $this->rowcount = 0;
        $fixedstudents = $this->is_fixed_students();

        $strsortasc   = $this->get_lang_string('sortasc', 'grades');
        $strsortdesc  = $this->get_lang_string('sortdesc', 'grades');
        $strfirstname = $this->get_lang_string('firstname');
        $strlastname  = $this->get_lang_string('lastname');
        $showuseridnumber = $this->get_pref('showuseridnumber');
        $showuserimage = $this->get_pref('showuserimage');
        $showquickfeedback = $this->get_pref('showquickfeedback');
        if ($showquickfeedback && $USER->gradeediting[$this->courseid]) {
            $minwidth = '; min-width: 180px';
        } else {
        	$minwidth = '';
        }
        // figure out how wide we should make the names column
        $namewidth = 1;
        foreach ($this->users as $user) {
            $namewidth = max($namewidth, strlen($user->firstname), strlen($user->lastname) * 1.4);
        }
        $namewidth = 'style="min-width:' . s(round($namewidth) * 7) . 'px"';
        if ($this->sortitemid === 'lastname') {
                if ($this->sortorder == 'ASC') {
                        $lastarrow = print_arrow('up', $strsortasc, true);
                } else {
                        $lastarrow = print_arrow('down', $strsortdesc, true);
                }
        } else {
                $lastarrow = '';
        }

        if ($this->sortitemid === 'firstname') {
                if ($this->sortorder == 'ASC') {
                        $firstarrow = print_arrow('up', $strsortasc, true);
                } else {
                        $firstarrow = print_arrow('down', $strsortdesc, true);
                }
        } else {
                $firstarrow = '';
        }


        // Prepare Table Headers
        $headerhtml = '';

//        $numrows = count($this->gtree->levels);
//        $columns_to_unset = array();

        $columncount = 0;

        $headerhtml .= '<tr class="heading r'.$this->rowcount++.'">';
//        $colspan = 'colspan="2" ';
        $colspan = '';
        $user_pic = '';
        $name_header = ($columncount == 0) ? ' class="name-header"' : '';
        $output = '<div class="inlinebutton" title="Download contents of gradebook as-is to Excel. SAVE CHANGES FIRST!">';
        $output .= '<a href="' . $CFG->wwwroot . '/grade/report/laegrader/index.php?id=' . $this->courseid
                . '&action=quick-dump" class="inlinebutton"><img src="' . $CFG->wwwroot . '/grade/report/laegrader/copytoexcel.png" /></a></div>';
        $options = array('id'=>$this->courseid,'action'=>'quick-dump');
        $headerhtml .= '<th class=" header c'.$columncount++ . '  nameheader" scope="col" ' . $colspan . $namewidth . '>' . $user_pic
                . $output
                . '<a href="'.$this->baseurl.'&amp;sortitemid=firstname">'
                . $strfirstname . '</a> '
                . $firstarrow. '/ <a href="'.$this->baseurl.'&amp;sortitemid=lastname" width="100px"' . $name_header . '>' . $strlastname . '</a>'. $lastarrow
                . '</th><th class="header gradeheader"></th>';
//                . '</th><th style="background-color:#ffffff"></th>'; // '#F3DFD0' : '#D0DBF3'
        if ($showuseridnumber) {
            if ('idnumber' == $this->sortitemid) {
                    if ($this->sortorder == 'ASC') {
                            $idnumberarrow = print_arrow('up', $strsortasc, true);
                    } else {
                            $idnumberarrow = print_arrow('down', $strsortdesc, true);
                    }
            } else {
                    $idnumberarrow = '';
            }
            $headerhtml .= '<th class="header  c'.$columncount++.' useridnumber" scope="col"><a href="'.$this->baseurl.'&amp;sortitemid=idnumber">'
                            . get_string('idnumber') . '</a> ' . $idnumberarrow . '</th>';
        }

        $catcount = 0;
        $catparent = 0;
        foreach ($this->gtree->items as $columnkey => $element) {
            $sort_link = '';
            if (isset($element->id)) {
                    $sort_link = $this->baseurl.'&amp;sortitemid=' . $element->id;
            }

            $eid = 'i' . $element->id;
            $object = $element;
            $type   = (stristr('courseitem,categoryitem',$element->itemtype)) ? $element->itemtype . 'item' : 'item';
            $element->type = $type;
            $itemmodule = null;
            $iteminstance = null;

            $columnclass = 'c' . $columncount++;
            $colspan = '';
            $catlevel = '';

            $itemmodule = $object->itemmodule;
            $iteminstance = $object->iteminstance;
            if ($element->id == $this->sortitemid) {
                    if ($this->sortorder == 'ASC') {
                            $arrow = $this->get_sort_arrow('up', $sort_link);
                    } else {
                            $arrow = $this->get_sort_arrow('down', $sort_link);
                    }
            } else {
                    $arrow = $this->get_sort_arrow('move', $sort_link);
            }

            $url = $this->baseurl . '&amp;action=display&target=' . $element->id . '&sesskey=' . sesskey();
            $hidden = '';
            if ($element->is_hidden()) {
                    $hidden = ' hidden ';
                    $catcolor = '#dfdfdf';
            } else if (isset($element->categoryid)) {
//            } else if (isset($element->categoryid) AND $this->gtree->items[$element->parent]->itemtype <> 'course') {
                    // same category as last
                    if ($element->categoryid <> $catparent) {
                            $catparent = $element->categoryid;
                            $catcount++;
                            $catcolor = ($catcount % 2) ? '#F3DFD0' : '#D0DBF3';
                    }
            // category header for last category
            } else if($element->itemtype == 'category') {
            // course item
            } else {
                    $catcolor = '#ffffff';
            }
            $headerlink = get_element_headerlaegrader($element, true, $this->get_pref('showactivityicons'), false);
            $headerhtml .= '<th class=" '.$columnclass.' '.$type.$catlevel.$hidden.'" style="background-color:' . $catcolor . $minwidth . '" scope="col" onclick="set_col(this.cellIndex)">'
                                    . $headerlink;
            if (isset($this->gtree->parents[$element->id]->agg)  AND $this->gtree->parents[$element->id]->agg == GRADE_AGGREGATE_WEIGHTED_MEAN) {
                    $headerhtml .= '<br /><div class="gradeweight">W=' . number_format($element->aggregationcoef,2) . '	</div>' . $arrow;
            } else {
            $headerhtml .= '<br />' . $arrow;
            }
/*
            $new_parent = $this->gtree->items[substr($eid,1,5)]->parent;
            if (isset($this->gtree->items[$new_parent]) AND $this->gtree->items[$new_parent]->agg_method == GRADE_AGGREGATE_WEIGHTED_MEAN) {
                    $headerhtml .= '<br /><div class="gradeweight">W=' . number_format($element->aggregationcoef,2) . '	</div>';
            }
*/

            $headerhtml .= '</th>';

        }

        $headerhtml .= '</tr>';

        return $headerhtml;
    }



    /**
     * Builds and return the HTML row of ranges for each column (i.e. range).
     * @return string HTML
     */
    function get_iconshtml() {
        global $USER, $CFG;

        $iconshtml = '';
        if ($USER->gradeediting[$this->courseid]) {

            $iconshtml = '<tr class="controls">';

            $fixedstudents = 0;  //$this->is_fixed_students();
            $showuseridnumber = $this->get_pref('showuseridnumber');

            $colspan = 'colspan="2"';
//            $colspan = '';
            if ($showuseridnumber) {
                $colspan = 'colspan="3"';
            }

            if (!$fixedstudents) {
                $iconshtml .= '<th class="header c0 controls" scope="row" '.$colspan.'>'.$this->get_lang_string('controls','grades').'</th>';
            }

            $columncount = 0;
            foreach ($this->gtree->items as $itemid=>$unused) {
                // emulate grade element
                $item =& $this->gtree->items[$itemid];

                $eid = $this->gtree->get_item_eid($item);
                $element = $this->gtree->locate_element($eid);

                $iconshtml .= '<td class="controls cell c'.$columncount++.' icons">' . $this->get_icons($element) . '</td>';
            }
            $iconshtml .= '</tr>';
        }
        return $iconshtml;
    }

    /**
     * Builds and return the HTML row of ranges for each column (i.e. range).
     * @return string HTML
     */
    function get_rangehtml() {
        global $USER, $CFG;

        $rangehtml = '';
        if ($this->get_pref('showranges')) {
            $rangesdisplaytype   = $this->get_pref('rangesdisplaytype');
            $rangesdecimalpoints = $this->get_pref('rangesdecimalpoints');

            $columncount=0;
            $rangehtml = '<tr class="range r'.$this->rowcount++.' heading">';

            $fixedstudents = 0;  //$this->is_fixed_students();
            if (!$fixedstudents) {
//                $colspan='';
                $colspan='colspan="2" ';
	                 if ($this->get_pref('showuseridnumber')) {
                    $colspan = 'colspan="3" ';
                }
                $rangehtml .= '<th class="header c0 range" '.$colspan.' scope="row">'.$this->get_lang_string('range','grades').'</th>';
            }

            foreach ($this->gtree->items as $itemid=>$unused) {
                $item =& $this->gtree->items[$itemid];

                $gradedisplaytype = (integer) substr( (string) $item->get_displaytype(),0,1);

                // if we have an accumulated total points that's not accurately reflected in the db, then we want to display the ACCURATE number
                // we only need to take the extra calculation into account if points display since percent and letter are accurate by their nature
                // max_earnable doesn't get set unless accuratetotals is on
                $tempmax = $item->grademax;
                if ($gradedisplaytype == GRADE_DISPLAY_TYPE_REAL && isset($item->max_earnable)) {
                    $item->grademax = $item->max_earnable;
/*
                    if (isset($this->gtree->parents[$itemid]->id)) {
                        $this->gtree->items[$this->gtree->parents[$itemid]->id]->max_earnable += $item->max_earnable;
                    }
 *
 */
                } else {
                    // this line needs to consider if accuratetotals is turned on for whether the ranges will be accurately computed
                    // If accuratepoints is checked at this place, then at not time will there be a max_earnable value computed if accuratepoints is not enabled
//                    if ($accuratetotals AND isset($this->gtree->parents[$item->id])) {
/*
                    if (isset($this->gtree->parents[$itemid]->id)) {
                        $this->gtree->items[$this->gtree->parents[$itemid]->id]->max_earnable += $item->grademax;
                    }
 * 
 */
                }

                $hidden = '';
                if ($item->is_hidden()) {
                    $hidden = ' hidden ';
                }

                $formatted_range = $item->get_formatted_range($rangesdisplaytype, $rangesdecimalpoints);

                $rangehtml .= '<td class="cell c'.$columncount++.' range"><span class="rangevalues'.$hidden.'">'. $formatted_range .'</span></td>';
                // reset item->grademax back to its original value, in case its been changed
                $item->grademax = $tempmax;

            }
            $rangehtml .= '</tr>';
        }
        return $rangehtml;
    }

    /**
     * Builds and return the HTML row of column totals.
     * @param  bool $grouponly Whether to return only group averages or all averages.
     * @return string HTML
     */
    function get_avghtml($grouponly=false) {
        global $CFG, $USER;

        if (!$this->canviewhidden) {
            // totals might be affected by hiding, if user can not see hidden grades the aggregations might be altered
            // better not show them at all if user can not see all hideen grades
            return;
        }

        $averagesdisplaytype   = $this->get_pref('averagesdisplaytype');
        $averagesdecimalpoints = $this->get_pref('averagesdecimalpoints');
        $meanselection         = $this->get_pref('meanselection');
        $shownumberofgrades    = $this->get_pref('shownumberofgrades');

        $avghtml = '';
        $avgcssclass = 'avg';

        if ($grouponly) {
            $straverage = get_string('groupavg', 'grades');
            $showaverages = $this->currentgroup && $this->get_pref('showaverages');
            $groupsql = $this->groupsql;
            $groupwheresql = $this->groupwheresql;
            $avgcssclass = 'groupavg';
        } else {
            $straverage = get_string('overallaverage', 'grades');
            $showaverages = $this->get_pref('showaverages');
            $groupsql = "";
            $groupwheresql = "";
        }

        if ($shownumberofgrades) {
            $straverage .= ' (' . get_string('submissions', 'grades') . ') ';
        }

        $totalcount = $this->get_numusers($grouponly);

        if ($showaverages) {

            // find sums of all grade items in course
            $SQL = "SELECT g.itemid, SUM(g.finalgrade) AS sum
                      FROM {$CFG->prefix}grade_items gi
                           JOIN {$CFG->prefix}grade_grades g      ON g.itemid = gi.id
                           JOIN {$CFG->prefix}user u              ON u.id = g.userid
                           JOIN {$CFG->prefix}role_assignments ra ON ra.userid = u.id
                           $groupsql
                     WHERE gi.courseid = $this->courseid
                           AND ra.roleid in ($this->gradebookroles)
                           AND ra.contextid ".get_related_contexts_string($this->context)."
                           AND g.finalgrade IS NOT NULL
                           $groupwheresql
                  GROUP BY g.itemid";
            $sum_array = array();
            if ($sums = get_records_sql($SQL)) {
                foreach ($sums as $itemid => $csum) {
                    $sum_array[$itemid] = $csum->sum;
                }
            }

            $columncount=0;

            $avghtml = '<tr class="' . $avgcssclass . ' r'.$this->rowcount++.'">';

            // MDL-10875 Empty grades must be evaluated as grademin, NOT always 0
            // This query returns a count of ungraded grades (NULL finalgrade OR no matching record in grade_grades table)
            $SQL = "SELECT gi.id, COUNT(u.id) AS count
                      FROM {$CFG->prefix}grade_items gi
                           CROSS JOIN {$CFG->prefix}user u
                           JOIN {$CFG->prefix}role_assignments ra        ON ra.userid = u.id
                           LEFT OUTER JOIN  {$CFG->prefix}grade_grades g ON (g.itemid = gi.id AND g.userid = u.id AND g.finalgrade IS NOT NULL)
                           $groupsql
                     WHERE gi.courseid = $this->courseid
                           AND ra.roleid in ($this->gradebookroles)
                           AND ra.contextid ".get_related_contexts_string($this->context)."
                           AND g.id IS NULL
                           $groupwheresql
                  GROUP BY gi.id";

            $ungraded_counts = get_records_sql($SQL);

            $fixedstudents = 0; //$this->is_fixed_students();
            if (!$fixedstudents) {
                $colspan='colspan="2" ';
//                $colspan='';
                if ($this->get_pref('showuseridnumber')) {
                    $colspan = 'colspan="3" ';
                }
                $avghtml .= '<th class="header c0 avg" '.$colspan.' scope="row">'.$straverage.'</th>';
            }

            foreach ($this->gtree->items as $itemid=>$unused) {
                $item =& $this->gtree->items[$itemid];

                if ($item->needsupdate) {
                    $avghtml .= '<td class="cell c' . $columncount++.'"><span class="gradingerror">'.get_string('error').'</span></td>';
                    continue;
                }

                if (!isset($sum_array[$item->id])) {
                    $sum_array[$item->id] = 0;
                }

                if (empty($ungraded_counts[$itemid])) {
                    $ungraded_count = 0;
                } else {
                    $ungraded_count = $ungraded_counts[$itemid]->count;
                }

                if ($meanselection == GRADE_REPORT_MEAN_GRADED) {
                    $mean_count = $totalcount - $ungraded_count;
                } else { // Bump up the sum by the number of ungraded items * grademin
                    $sum_array[$item->id] += $ungraded_count * $item->grademin;
                    $mean_count = $totalcount;
                }

                $decimalpoints = $item->get_decimals();

                // Determine which display type to use for this average
//                if ($USER->gradeediting[$this->courseid]) {
//                    $displaytype = GRADE_DISPLAY_TYPE_REAL;

                if ($averagesdisplaytype == GRADE_REPORT_PREFERENCE_INHERIT) { // no ==0 here, please resave the report and user preferences
                    $displaytype = $item->get_displaytype();

                } else {
                    $displaytype = $averagesdisplaytype;
                }

                // Override grade_item setting if a display preference (not inherit) was set for the averages
                if ($averagesdecimalpoints == GRADE_REPORT_PREFERENCE_INHERIT) {
                    $decimalpoints = $item->get_decimals();

                } else {
                    $decimalpoints = $averagesdecimalpoints;
                }

                if (!isset($sum_array[$item->id]) || $mean_count == 0) {
                    $avghtml .= '<td class="cell c' . $columncount++.' avg">-</td>';
                } else {
                    $sum = $sum_array[$item->id];
                    $avgradeval = $sum/$mean_count;
                    $gradehtml = grade_format_gradevalue($avgradeval, $item, true, $displaytype, $decimalpoints);

                    $numberofgrades = '';
                    if ($shownumberofgrades) {
                        $numberofgrades = " ($mean_count)";
                    }

                    $avghtml .= '<td class="cell c' . $columncount++.' avg">'.$gradehtml.$numberofgrades.'</td>';
                }
            }
            $avghtml .= '</tr>';
        }
        return $avghtml;
    }



    /**
     * Given a grade_category, grade_item or grade_grade, this function
     * figures out the state of the object and builds then returns a div
     * with the icons needed for the grader report.
     *
     * @param object $object
     * @return string HTML
     */
    function get_icons($element) {
        global $CFG, $USER;

        if (!$USER->gradeediting[$this->courseid]) {
            return '<div class="grade_icons" />';
        }

        // Init all icons
        $edit_icon = '';

//        if ($element['type'] != 'categoryitem' && $element['type'] != 'courseitem') {
            $edit_icon             = $this->gtree->get_edit_icon($element, $this->gpr);
//        }

        $edit_calculation_icon = '';
        $show_hide_icon        = '';
        $lock_unlock_icon      = '';
        $zerofill_icon         = get_zerofill_icon($element, $this->courseid, $this->context);

        if (has_capability('moodle/grade:manage', $this->context)) {

            if ($this->get_pref('showcalculations')) {
                $edit_calculation_icon = $this->gtree->get_calculation_icon($element, $this->gpr);
            }

            if ($this->get_pref('showeyecons')) {
               $show_hide_icon = $this->gtree->get_hiding_icon($element, $this->gpr);
            }

            if ($this->get_pref('showlocks')) {
                /// BP: just show locks on column headers
                if ($element['type'] <> 'grade' && $element['type'] <> 'categoryitem' && $element['type'] <> 'courseitem') {
                    $lock_unlock_icon = $this->gtree->get_locking_icon($element, $this->gpr);
                }
            }
        }

        return '<div class="grade_icons">'.$edit_icon.$edit_calculation_icon.$show_hide_icon.$lock_unlock_icon.$zerofill_icon.'</div>';
    }




    /**
     * Builds and return the HTML rows of the table (grades headed by student).
     * @return string HTML
     */
 
    function get_studentshtml() {
        global $CFG, $USER;

        $studentshtml = '';
        $strfeedback  = $this->get_lang_string("feedback");
        $strgrade     = $this->get_lang_string('grade');
/*
        if ($showquickfeedback ) {
            $gradetabindex += $numusers * 4;
        } else {
            $gradetabindex += $numusers * 2;
        }
 *
 */
        $gradetabindex = 1;
        $numusers      = count($this->users);
        $showuserimage = $this->get_pref('showuserimage');
        $showuseridnumber = $this->get_pref('showuseridnumber');
        $showquickfeedback = $this->get_pref('showquickfeedback');
        $accuratetotals = (get_user_preferences('grade_report_accuratepointtotals') == null) ? 1 : 0;

        // turn off fixed students column no matter what for this report
        $fixedstudents = 0; //$this->is_fixed_students();
        $quickgrading = $this->get_pref('quickgrading');

        // Preload scale objects for items with a scaleid
        $scales_list = '';
        $tabindices = array();

        foreach ($this->gtree->items as $item) {
            if (!empty($item->scaleid)) {
                $scales_list .= "$item->scaleid,";
            }
            if ($item->type == 'item') {
                $tabindices[$item->id]['grade'] = $gradetabindex;
                if ($showquickfeedback ) {
                    $tabindices[$item->id]['feedback'] = $gradetabindex + $numusers;
                    $gradetabindex += $numusers * 2;
                } else {
                    $gradetabindex += $numusers;
                }
            }
        }
        $scales_array = array();

        if (!empty($scales_list)) {
            $scales_list = substr($scales_list, 0, -1);
            $scales_array = get_records_list('scale', 'id', $scales_list);
        }

        $row_classes = array(' even ', ' odd ');

        foreach ($this->users as $userid => $user) {

            if ($this->canviewhidden) {
                $altered = array();
                $unknown = array();
            } else {
                $hiding_affected = grade_grade::get_hiding_affected($this->grades[$userid], $this->gtree->items);
                $altered = $hiding_affected['altered'];
                $unknown = $hiding_affected['unknown'];
                unset($hiding_affected);
            }

            $columncount = 0;
            if ($fixedstudents) {
                $studentshtml .= '<tr class="r'.$this->rowcount++ . $row_classes[$this->rowcount % 2] . '">';
            } else {
                // Student name and link
                $user_pic = null;
                if ($showuserimage) {
                    $user_pic = '<div class="userpic">' . print_user_picture($user, $this->courseid, null, 0, true) . '</div>';
                }

                //we're either going to add a th or a colspan to keep things aligned
                // REMOVING $colspan AS ANYTHING
                $userreportcell = '';
                $userreportcellcolspan = '';
                if (has_capability('gradereport/'.$CFG->grade_profilereport.':view', $this->context)) {
                    $a->user = fullname($user);
                    $strgradesforuser = get_string('gradesforuser', 'grades', $a);
                    $userreportcell = '<td class="tduser"><a href="'.$CFG->wwwroot.'/grade/report/'.$CFG->grade_profilereport.'/index.php?id='.$this->courseid.'&amp;userid='.$user->id.'" target="_blank">'
                                    .'<img class="userreport" src="'.$CFG->pixpath.'/t/grades.gif" alt="'.$strgradesforuser.'" style="align:center" title="'.$strgradesforuser.'" /></a></td>';
//                    $userreportcell = '<a href="'.$CFG->wwwroot.'/grade/report/'.$CFG->grade_profilereport.'/index.php?id='.$this->courseid.'&amp;userid='.$user->id.'" target="_blank">'
//                                    .'<img class="userreport" src="'.$CFG->pixpath.'/t/grades.gif" alt="'.$strgradesforuser.'" style="align:center" title="'.$strgradesforuser.'" /></a>';
                } else {
                    $userreportcell = '<td class="userreport"></td>';
                }
                $studentshtml .= '<tr class="r'.$this->rowcount++ . $row_classes[$this->rowcount % 2] . '">'
//                              .'<th class="c'.$columncount++.' user" scope="row" onclick="set_row(this.parentNode.rowIndex);" '.$userreportcellcolspan.' >'.$user_pic
                              .'<th class="c'.$columncount++.' user">'.$user_pic
                              .'<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$this->course->id.'">'
                              . $user->firstname . '<br /><span class="lastname">' . $user->lastname . "</a></span></th>$userreportcell";
//                              .fullname($user)."</a></th>$userreportcell";


                if ($showuseridnumber) {

                    $studentshtml .= '<th class="c'.$columncount++.' useridnumber" onclick="set_row(this.parentNode.rowIndex);">'.
                            $user->idnumber.'</th>';
                }

            }

            // each loop does an item, entire cycle does a user's row
            foreach ($this->gtree->items as $itemid=>$unused) {
                $item =& $this->gtree->items[$itemid];
                $grade = $this->grades[$userid][$item->id];

                // Get the decimal points preference for this item
                $decimalpoints = $item->get_decimals();

                if (in_array($itemid, $unknown)) {
                    $gradeval = null;
                } else if (array_key_exists($itemid, $altered)) {
                    $gradeval = $altered[$itemid];
                } else {
                    $gradeval = $grade->finalgrade;
                }

                // MDL-11274
                // Hide grades in the grader report if the current grader doesn't have 'moodle/grade:viewhidden'
                if (!$this->canviewhidden and $grade->is_hidden()) {
                    if (!empty($CFG->grade_hiddenasdate) and $grade->get_datesubmitted() and !$item->is_category_item() and !$item->is_course_item()) {
                        // the problem here is that we do not have the time when grade value was modified, 'timemodified' is general modification date for grade_grades records
                        $studentshtml .= '<td class="cell c'.$columncount++.'"><span class="datesubmitted">'.userdate($grade->get_datesubmitted(),get_string('strftimedatetimeshort')).'</span></td>';
                    } else {
                        $studentshtml .= '<td class="cell c'.$columncount++.'">-</td>';
                    }
                    continue;
                }

                // emulate grade element
                $eid = $this->gtree->get_grade_eid($grade);
                $element = array('eid'=>$eid, 'object'=>$grade, 'type'=>'grade');

                $cellclasses = 'grade cell c'.$columncount++;
                if ($item->is_category_item()) {
                    $cellclasses .= ' cat';
                }
                if ($item->is_course_item()) {
                    $cellclasses .= ' course';
                }
                if ($grade->is_overridden()) {
                    $cellclasses .= ' overridden';
                }

                if ($grade->is_excluded()) {
                    $cellclasses .= ' excluded';
                }
/*  ELLIMINATE ALT TEXT WITH DIV SECTIONS
                $grade_title = '<div class="fullname">'.fullname($user).'</div>';
                $grade_title .= '<div class="itemname">'.$item->get_name(true).'</div>';

                if (!empty($grade->feedback) && !$USER->gradeediting[$this->courseid]) {
                    $grade_title .= '<div class="feedback">'
                                 .wordwrap(trim(format_string($grade->feedback, $grade->feedbackformat)), 34, '<br/ >') . '</div>';
                } else {

                }
*/
                $grade_title = '';
                $studentshtml .= '<td class="'.$cellclasses.'" title="'.s($grade_title).'">';

                if ($grade->is_excluded()) {
                    $studentshtml .= '<span class="excludedfloater">'.get_string('excluded', 'grades') . '</span> ';
                }

                // Do not show any icons if no grade (no record in DB to match)
                if (!$item->needsupdate and $USER->gradeediting[$this->courseid]) {
                    $studentshtml .= $this->get_icons($element);
                }

                $hidden = '';
                if ($grade->is_hidden()) {
                    $hidden = ' hidden ';
                }

                $gradepass = ' gradefail ';
                if ($grade->is_passed($item)) {
                    $gradepass = ' gradepass ';
                } elseif (is_null($grade->is_passed($item))) {
                    $gradepass = '';
                }

                // if in editting mode, we need to print either a text box
                // or a drop down (for scales)
                // grades in item of type grade category or course are not directly editable
                if ($item->needsupdate) {
                    $studentshtml .= '<span class="gradingerror'.$hidden.'">'.get_string('error').'</span>';

                } else if ($USER->gradeediting[$this->courseid]) {

                    if ($item->scaleid && !empty($scales_array[$item->scaleid])) {
                        $scale = $scales_array[$item->scaleid];
                        $gradeval = (int)$gradeval; // scales use only integers
                        $scales = explode(",", $scale->scale);
                        // reindex because scale is off 1

                        // MDL-12104 some previous scales might have taken up part of the array
                        // so this needs to be reset
                        $scaleopt = array();
                        $i = 0;
                        foreach ($scales as $scaleoption) {
                            $i++;
                            $scaleopt[$i] = $scaleoption;
                        }

                        if ($quickgrading and $grade->is_editable()) {
                            $oldval = empty($gradeval) ? -1 : $gradeval;
                            if (empty($item->outcomeid)) {
                                $nogradestr = $this->get_lang_string('nograde');
                            } else {
                                $nogradestr = $this->get_lang_string('nooutcome', 'grades');
                            }
                            $studentshtml .= '<input type="hidden" name="oldgrade_'.$userid.'_'
                                          .$item->id.'" value="'.$oldval.'"/>';
                            $studentshtml .= choose_from_menu($scaleopt, 'grade_'.$userid.'_'.$item->id,
                                                              $gradeval, $nogradestr, '', '-1',
                                                              true, false, $tabindices[$item->id]['grade']);
                        } elseif(!empty($scale)) {
                            $scales = explode(",", $scale->scale);

                            // invalid grade if gradeval < 1
                            if ($gradeval < 1) {
                                $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">-</span>';
                            } else {
                                $gradeval = $grade->grade_item->bounded_grade($gradeval); //just in case somebody changes scale
                                $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">'.$scales[$gradeval-1].'</span>';
                            }
                        } else {
                            // no such scale, throw error?
                        }

                    } else if ($item->gradetype != GRADE_TYPE_TEXT) { // Value type
                        // We always want to display the correct (first) displaytype when editing
                        // regardless of grade_report_gradeeditalways
                        $gradedisplaytype = (integer) substr( (string) $item->get_displaytype(),0,1);

                        // if we have an accumulated total points that's not accurately reflected in the db, then we want to display the ACCURATE number
                        // we only need to take the extra calculation into account if points display since percent and letter are accurate by their nature
                        // If the settings don't call for ACCURATE point totals ($this->accuratetotals) then there will be no earned_total value
                        if ($gradedisplaytype == GRADE_DISPLAY_TYPE_REAL && isset($this->grades[$userid][$grade->itemid]->cat_item)) {
                        	$items = $this->gtree->items;
		               		$grade_values = $this->grades[$userid][$grade->itemid]->cat_item;
		               		$grade_maxes = $this->grades[$userid][$grade->itemid]->cat_max;
		               		$this_cat = $this->gtree->items[$grade->itemid]->get_item_category();
		               		limit_item($this_cat,$items,$grade_values,$grade_maxes);
			       			$gradeval = array_sum($grade_values);
							$item->grademax = array_sum($grade_maxes);
                        }
                      	$value = grade_format_gradevalue($gradeval, $item, true, $gradedisplaytype, null);
                        if (! $grade->is_hidden() && $gradeval <> null && $this->accuratetotals) {
                			$this->grades[$userid][$this->gtree->parents[$grade->itemid]->id]->cat_item[$grade->itemid] = $gradeval;
							$this->grades[$userid][$this->gtree->parents[$grade->itemid]->id]->cat_max[$grade->itemid] = $grade->rawgrademax;
				   		}
                        if ($quickgrading and $grade->is_editable()) {
                            if (! $item->is_course_item() and ! $item->is_category_item()) {
                                $studentshtml .= '<input type="hidden" name="oldgrade_'.$userid.'_'.$item->id.'" value="'.$value.'" />';
                                $studentshtml .= '<input size="6" tabindex="' . $tabindices[$item->id]['grade']
                                              . '" type="text" title="'. $strgrade .'" class ="gradevalue' . $hidden . $gradepass .'" rel="' . $item->id . '" name="grade_'
                                              .$userid.'_' .$item->id.'" value="'.$value.'" />';
                            } else {
                                $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">'.$value.'</span>';
                            }
                        } else {
                            $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">'.format_float($gradeval, $decimalpoints).'</span>';
                        }
                    }


                    // If quickfeedback is on, print an input element
                    if ($showquickfeedback and $grade->is_editable()) {

                        if (! $item->is_course_item() and ! $item->is_category_item()) {
                            $studentshtml .= '<input type="hidden" name="oldfeedback_'
                                          .$userid.'_'.$item->id.'" value="' . s($grade->feedback) . '" />';
                            $studentshtml .= '<input class="quickfeedback" tabindex="' . $tabindices[$item->id]['feedback']
                                          . '" size="6" title="' . $strfeedback . '" type="text" name="feedback_'
                                          .$userid.'_'.$item->id.'" value="' . s($grade->feedback) . '" />';
                        }
                    }

                } else { // Not editing
					
                    if ($this->accuratetotals) {
                        $gradedisplaytype = (integer) substr( (string) $item->get_displaytype(),0,1);
                    } else {
                        $gradedisplaytype = $item->get_displaytype();
                    }
                    // if we have an accumulated total points that's not accurately reflected in the db, then we want to display the ACCURATE number
                    // we only need to take the extra calculation into account if points display since percent and letter are accurate by their nature
                    // If the settings don't call for ACCURATE point totals ($this->accuratetotals) then there will be no earned_total value
                    if ($gradedisplaytype == GRADE_DISPLAY_TYPE_REAL && isset($this->grades[$userid][$grade->itemid]->cat_item)) {
                       	$items = $this->gtree->items;
	               		$grade_values = $this->grades[$userid][$grade->itemid]->cat_item;
	               		$grade_maxes = $this->grades[$userid][$grade->itemid]->cat_max;
	               		$this_cat = $this->gtree->items[$grade->itemid]->get_item_category();
	               		limit_item($this_cat,$items,$grade_values,$grade_maxes);
		       			$gradeval = array_sum($grade_values);
						$item->grademax = array_sum($grade_maxes);
                    }
               		// is calculating accurate totals store the earned_total for this item to its parent, if there is one
                    if (! $grade->is_hidden() && $gradeval <> null && $this->accuratetotals && isset($this->gtree->parents[$grade->itemid]->id)) {
              			$this->grades[$userid][$this->gtree->parents[$grade->itemid]->id]->cat_item[$grade->itemid] = $gradeval;
						$this->grades[$userid][$this->gtree->parents[$grade->itemid]->id]->cat_max[$grade->itemid] = $grade->rawgrademax;
			   		}
              	
                	
                    // If feedback present, surround grade with feedback tooltip: Open span here

                    if ($item->needsupdate) {
                        $studentshtml .= '<span class="gradingerror'.$hidden.$gradepass.'">'.get_string('error').'</span>';

                    } else {
                        $studentshtml .= '<span class="gradevalue'.$hidden.$gradepass.'">'.grade_format_gradevalue($gradeval, $item, true, $gradedisplaytype, null).'</span>';
                    }
                }

                if (!empty($this->gradeserror[$item->id][$userid])) {
                    $studentshtml .= $this->gradeserror[$item->id][$userid];
                }

                $studentshtml .=  '</td>' . "\n";
            }
            $studentshtml .= '</tr>';
        }
        return $studentshtml;
    }



    /**
     * pulls out the userids of the users to be display, and sorts them
     */
    function load_users() {
        global $CFG;

        if (is_numeric($this->sortitemid)) {
            // the MAX() magic is required in order to please PG
            $sort = "MAX(g.finalgrade) $this->sortorder";

            $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.imagealt, u.picture, u.idnumber
                      FROM {$CFG->prefix}user u
                           JOIN {$CFG->prefix}role_assignments ra ON ra.userid = u.id
                           $this->groupsql
                           LEFT JOIN {$CFG->prefix}grade_grades g ON (g.userid = u.id AND g.itemid = $this->sortitemid)
                     WHERE ra.roleid in ($this->gradebookroles) AND u.deleted = 0
                           $this->groupwheresql
                           AND ra.contextid ".get_related_contexts_string($this->context)."
                  GROUP BY u.id, u.firstname, u.lastname, u.imagealt, u.picture, u.idnumber
                  ORDER BY $sort";

        } else {
            switch($this->sortitemid) {
                case 'lastname':
                    $sort = "u.lastname $this->sortorder, u.firstname $this->sortorder"; break;
                case 'firstname':
                    $sort = "u.firstname $this->sortorder, u.lastname $this->sortorder"; break;
                case 'idnumber':
                default:
                    $sort = "u.idnumber $this->sortorder"; break;
            }

            $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.imagealt, u.picture, u.idnumber
                      FROM {$CFG->prefix}user u
                           JOIN {$CFG->prefix}role_assignments ra ON u.id = ra.userid
                           $this->groupsql
                     WHERE ra.roleid in ($this->gradebookroles)
                           $this->groupwheresql
                           AND ra.contextid ".get_related_contexts_string($this->context)."
                  ORDER BY $sort";
        }


        $this->users = get_records_sql($sql, 0,0);
/*
        $this->users = get_records_sql($sql, $this->get_pref('studentsperpage') * $this->page,
                            $this->get_pref('studentsperpage'));
 *
 */

        if (empty($this->users)) {
            $this->userselect = '';
            $this->users = array();
        } else {
            $this->userselect = 'AND g.userid in ('.implode(',', array_keys($this->users)).')';
        }

        return $this->users;
    }


    function quick_dump() {
        global $CFG;
        require_once($CFG->dirroot.'/lib/excellib.class.php');

//        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');
        $accuratetotals = get_user_preferences('accuratepointtotals') == null ? 1 : 0;


    /// Calculate file name
        $downloadfilename = clean_filename("{$this->course->shortname} $strgrades.xls");
    /// Creating a workbook
        $workbook = new MoodleExcelWorkbook("-");
    /// Sending HTTP headers
        $workbook->send($downloadfilename);
    /// Adding the worksheet
        $myxls =& $workbook->add_worksheet($strgrades);

    /// Print names of all the fields
        $myxls->write_string(0,0,get_string("firstname"));
        $myxls->write_string(0,1,get_string("lastname"));
        $myxls->write_string(0,2,get_string("idnumber"));
        $myxls->write_string(0,3,get_string("email"));
        $pos=4;
        
        // write out column headers
        foreach ($this->gtree->items as $grade_item) {
//            $myxls->write_string(0, $pos++, $this->format_column_name($grade_item));
            switch ($grade_item->itemtype) {
                    case 'category':
                        $grade_item->item_category = grade_category::fetch(array('id'=>$grade_item->iteminstance));
//                        $grade_item->load_category();
                        $myxls->write_string(0, $pos++, $grade_item->item_category->fullname . ' Category total');
                        break;
                    case 'course':
                        $myxls->write_string(0, $pos++, 'Course total');
                        break;
                    default:
                        $myxls->write_string(0, $pos++, $grade_item->itemname);
            }

            /// add a column_feedback column
            if (isset($this->export_feedback) AND $this->export_feedback) {
//                $myxls->write_string(0, $pos++, $this->format_column_name($grade_item, true));
                $myxls->write_string(0, $pos++, $grade_item->itemname);
            }
        }

        // write out range row
        $myxls->write_string(1, 2, 'Maximum grade->');
        $pos=4;
        foreach ($this->gtree->items as $grade_item) {
//            $myxls->write_string(0, $pos++, $this->format_column_name($grade_item));
            $myxls->write_number(1, $pos++, $grade_item->grademax);
//            $myxls->write_number($i,$j++,$gradestr);
            /// add a column_feedback column
            if (isset($this->export_feedback) AND $this->export_feedback) {
//                $myxls->write_string(0, $pos++, $this->format_column_name($grade_item, true));
                $myxls->write_string(1, $pos++, $grade_item->name);
            }
        }

        // write out weights row
        $myxls->write_string(2, 2, 'Weight->');
        $pos=4;
        foreach ($this->gtree->items as $grade_item) {
            if (isset($this->gtree->parents[$grade_item->id]->id) && $this->gtree->parents[$grade_item->id]->agg == GRADE_AGGREGATE_WEIGHTED_MEAN) {
                $myxls->write_number(2,$pos++,$grade_item->aggregationcoef);
//                $myxls->write_string(1, $pos++, format_float($grade_item->aggregationcoef,0));
            } else {
                $myxls->write_string(2, $pos++, "");
            }
            /// add a column_feedback column
            if (isset($this->export_feedback) AND $this->export_feedback) {
                $myxls->write_string(2, $pos++, $grade_item->name);
            }
        }


    /// Print all the lines of data.
        $i = 2;
//        $geub = new grade_export_update_buffer();
//        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
//        $gui->init();
        foreach ($this->users as $key=>$user) {
            $i++;
//            $user = $userdata->user;

            $myxls->write_string($i,0,$user->firstname);
            $myxls->write_string($i,1,$user->lastname);
            $myxls->write_string($i,2,$user->idnumber);
            $myxls->write_string($i,3,$user->email);
//           $myxls->write_string($i,3,$user->institution);
//            $myxls->write_string($i,4,$user->department);
//            $myxls->write_string($i,3,$user->email);
            $j=4;
            foreach ($this->gtree->items as $itemid => $item) {
//                if ($export_tracking) {
//                    $status = $geub->track($grade);
//                }

                $grade = $this->grades[$key][$itemid];
				$gradeval = $grade->finalgrade;
                // if gradeeditalways then we only want the first displaytype (in case multiple displaytypes are requested)
                $gradedisplaytype = (integer) substr( (string) $item->get_displaytype(),0,1);
                // if we have an accumulated total points that's not accurately reflected in the db, then we want to display the ACCURATE number
                if ($gradedisplaytype == GRADE_DISPLAY_TYPE_REAL && isset($this->grades[$userid][$grade->itemid]->cat_item)) {
                   	$items = $this->gtree->items;
               		$grade_values = $this->grades[$userid][$grade->itemid]->cat_item;
               		$grade_maxes = $this->grades[$userid][$grade->itemid]->cat_max;
               		$this_cat = $this->gtree->items[$grade->itemid]->get_item_category();
               		limit_item($this_cat,$items,$grade_values,$grade_maxes);
	       			$gradeval = array_sum($grade_values);
					$item->grademax = array_sum($grade_maxes);
                    }
           		// is calculating accurate totals store the earned_total for this item to its parent, if there is one
                if (! $grade->is_hidden() && $gradeval <> null && $this->accuratetotals) {
          			$this->grades[$userid][$this->gtree->parents[$grade->itemid]->id]->cat_item[$grade->itemid] = $gradeval;
					$this->grades[$userid][$this->gtree->parents[$grade->itemid]->id]->cat_max[$grade->itemid] = $grade->rawgrademax;
		   		}
                $gradestr = grade_format_gradevalue($gradeval, $item, true, $gradedisplaytype, null);
		   		
/*
		   		if ($gradedisplaytype == GRADE_DISPLAY_TYPE_REAL && $this->accuratetotals) {
                    $gradestr = grade_format_gradevalue($grade->earned_total, $item, true, $gradedisplaytype, null);
                } else {
                    $gradestr = grade_format_gradevalue($grade->finalgrade, $item, true, $gradedisplaytype, null);
                }
*/
                if (is_percentage($gradestr)) {
                    $myxls->write_number($i,$j++,$gradestr * .01);
//                    $myxls->write_number($i,$j++,substr(trim($gradestr),0,strlen(trim($gradestr))-1), array(num_format=>'Percent'));
                } else if (is_numeric($gradestr)) {
                    $myxls->write_number($i,$j++,$gradestr);
                } else {
                    $myxls->write_string($i,$j++,$gradestr);
                }
/*
                // writing feedback if requested
                if ($this->export_feedback) {
                    $myxls->write_string($i, $j++, $this->format_feedback($userdata->feedbacks[$itemid]));
                }
 * 
 */
            }
        }
//        $gui->close();
//        $geub->close();

    /// Close the workbook
        $workbook->close();

        exit;
    }


    function grade_format_local($value,$grade_item, $displaytype, $localized, $decimals) {
        switch ($displaytype) {
            case GRADE_DISPLAY_TYPE_REAL:
//                return format_float($value, $decimals, $localized);
                return grade_format_gradevalue_real($value, $grade_item, $decimals, $localized);

            case GRADE_DISPLAY_TYPE_PERCENTAGE:
//                return format_float($value, $decimals, $localized) .'%';
                return grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized);

            case GRADE_DISPLAY_TYPE_LETTER:
                return grade_format_gradevalue_letter($value, $grade_item);

            case GRADE_DISPLAY_TYPE_REAL_PERCENTAGE:
//                return format_float($value, $decimals, $localized) .'('
//                    . format_float($value, $decimals, $localized) .'%)';

                return grade_format_gradevalue_real($value, $grade_item, $decimals, $localized) . ' (' .
                        grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized) . ')';

            case GRADE_DISPLAY_TYPE_REAL_LETTER:
//                return format_float($value, $decimals, $localized) .'('
//                    . grade_format_gradevalue_letter($value, $grade_item) .')';
                return grade_format_gradevalue_real($value, $grade_item, $decimals, $localized) . ' (' .
                        grade_format_gradevalue_letter($value, $grade_item) . ')';

            case GRADE_DISPLAY_TYPE_PERCENTAGE_REAL:
//                return format_float($value, $decimals, $localized) .'% ('
//                    . format_float($value, $decimals, $localized) .')';
                return grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized) . ' (' .
                        grade_format_gradevalue_real($value, $grade_item, $decimals, $localized) . ')';

            case GRADE_DISPLAY_TYPE_LETTER_REAL:
//                return grade_format_gradevalue_letter($value, $grade_item) .'('
//                    . format_float($value, $decimals, $localized) .')';
                return grade_format_gradevalue_letter($value, $grade_item) . ' (' .
                        grade_format_gradevalue_real($value, $grade_item, $decimals, $localized) . ')';

            case GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE:
//                return grade_format_gradevalue_letter($value, $grade_item) .'('
//                    . format_float($value, $decimals, $localized) .'%)';
                return grade_format_gradevalue_letter($value, $grade_item) . ' (' .
                        grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized) . ')';

            case GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER:
//                return format_float($value, $decimals, $localized) .'% ('
//                    . grade_format_gradevalue_letter($value, $grade_item) .')';
                return grade_format_gradevalue_percentage($value, $grade_item, $decimals, $localized) . ' (' .
                        grade_format_gradevalue_letter($value, $grade_item) . ')';
            default:
                return '';
        }
    }
}


function is_percentage($gradestr = null) {
    return (substr(trim($gradestr),-1,1) == '%') ? true : false;
}

function grade_report_laegrader_settings_definition(&$mform) {
    global $CFG;

    $options = array(-1 => get_string('default', 'grades'),
                      0 => get_string('hide'),
                      1 => get_string('show'));
    if (empty($CFG->grade_report_laegrader_accuratetotals)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_laegrader_accuratetotals', get_string('accuratetotals', 'gradereport_laegrader'), $options);
}

// CLAMP # 194 2010-06-23 end


?>
