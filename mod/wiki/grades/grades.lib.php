<?php

/**
 * Library functions to handle NWiki grading system.
 *
 * @author Javier 
 * @author Gonzalo Serrano
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: grades.lib.php,v 1.16 2008/06/16 18:24:27 gonzaloserrano Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

global $CFG;

//require_once('../../../config.php');
require_once ($CFG->dirroot.'/mod/wiki/lib/wiki_manager.php');
require_once ($CFG->dirroot.'/mod/wiki/locallib.php');

//html functions
require_once ($CFG->dirroot.'/mod/wiki/weblib.php');

//Grade lib (gradebook)
require_once ($CFG->libdir.'/gradelib.php');

/**
 * This function returns the scales from a course and
 * also the site-wide scales (available for all courses)
 *
 * @param String $cid Course id.
 *
 * @return Array Available scales.
 */
function wiki_grade_get_scales_from_course($cid)
{
    global $CFG;

    if ($scales = get_records_sql('SELECT s.id, s.name, s.scale
                                   FROM '.$CFG->prefix.'scale s
                                   WHERE s.courseid='.$cid.' OR s.courseid = 0'))
        return $scales; 
    return null;
}

function wiki_grade_got_permission($WS)
{
    $context    = get_context_instance(CONTEXT_MODULE, $WS->cm->id);
    $evaluation = $WS->dfwiki->evaluation;

    if ($evaluation == 0) return false; // DON'T EVALUATE
    if ($evaluation == 1) {       // EVALUATED BY TEACHERS
        if (!has_capability('mod/wiki:mainreview', $context))
            return false;
    } else {                      // STUDENTS CAN ALSO EVALUATE
        if (!has_capability('mod/wiki:peerreview', $context) &&
            !has_capability('mod/wiki:mainreview', $context))
            return false;
    }
    return true;
}

/** Prints the code that sets the wiki page grade 
 *  evaluation box.
 *
 * @param  Object $WS       WikiStorage
 */
function wiki_grade_print_page_evaluation_box(&$WS)
{
    global $USER;

    // evaluation box available only in view tab
    if ($WS->pageaction != 'view') return;

    if (!wiki_grade_got_permission($WS)) return;

    $wiki  = get_record('wiki', 'id', $WS->cm->instance);
    if (!$wiki) return;

    $scale = get_record('scale', 'id', (int)$wiki->notetype);
    if (isset($scale) && $scale != false)
    {
        print_box_start();

        $prop = null;
        $prop->id = "form_grades";
        $prop->method = "post";
        $prop->action =  'view.php?id='.$WS->linkid.'&amp;page=view/'.stripslashes_safe($WS->page).
                         '&amp;ver='.$WS->ver.'&amp;gid='.$WS->groupmember->groupid.
                         '&amp;uid='.$WS->member->id;

        wiki_form_start($prop);

        $prop = null;
        $prop->class="evaluationbox";

        wiki_div_start($prop);

        $prop = null;
        //$prop->spacing = "40";
        $prop->padding = "2";
        //$prop->valigntd = "top";
        $prop->aligntd = "right";
        //$prop->border = 1;

        wiki_table_start($prop);

        echo ('<b>'.get_string('grade').'</b>:&nbsp;');

        wiki_change_column();

        $scale = wiki_grade_scale_box($scale, true);

        $eval_instance = wiki_grade_evaluation_exist($WS->page,
                                                     $WS->dfwiki->id, 
                                                     $WS->groupmember->groupid, 
                                                     $WS->member->id, 
                                                     $USER->id);

        if ($scale) {
            echo('&nbsp;');
            $prop = null;
            $prop->name = 'grade_post_evaluation';
            $prop->value = get_string('set', 'wiki');
            wiki_input_submit($prop); 
            echo('&nbsp;');

            $context = get_context_instance(CONTEXT_MODULE, $WS->cm->id);

            if ((has_capability('mod/wiki:authorreview', $context) || 
                 has_capability('mod/wiki:mainreview', $context)) && 
                 $eval_instance != false)
            {
                echo ('<i>('.get_string('eval_current','wiki').' "'.trim($scale[$eval_instance->wikigrade-1]).'")</i>');
            }
       } else
           echo('grades.lib.php: there is no wikigrade yet.');

        wiki_change_row();
        echo('<b>'.get_string('eval_feedback', 'wiki').'</b>:&nbsp');
        wiki_change_column();
        unset($prop);
        $prop->size = 60;
        $prop->value = '';
        if ($eval_instance)
            $prop->value = $eval_instance->comment;
        $prop->name = "grade_commentary";
        wiki_input_text($prop);

        $prop->align = 'right';
        wiki_change_row($prop);

        wiki_change_column();

        $prop = null;
        $prop->name = 'grade_appendtodiscussion';
        wiki_input_checkbox($prop); 

        $a->link = 'view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;page=discussion/discussion:'.urlencode($WS->page);
        echo (get_string('eval_append_to_disc', 'wiki', $a));

        $prop->align = 'right';
        wiki_change_row($prop);
        wiki_change_column();

        $prop = null;
        $prop->name = 'grade_anonymous';
        wiki_input_checkbox($prop); 
        echo (get_string('eval_anonymous', 'wiki'));

        wiki_table_end();
        wiki_div_end();
        wiki_form_end();
        
        print_box_end();
    }
}

function wiki_grade_set_page_grade(&$WS)
{
    global $USER;


    $context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);

    $post = optional_param('grade_post_evaluation', NULL, PARAM_ALPHA);
    if (!$post) return;
    if (has_capability('mod/wiki:peerreview',$context) || has_capability('mod/wiki:mainreview',$context))
    {
        $grade_page = optional_param('grade_evaluation_page',  0, PARAM_INT);
        $commentary = optional_param('grade_commentary',      '', PARAM_TEXT);

        if (false != ($gradeinstance = wiki_grade_evaluation_exist($WS->page, $WS->dfwiki->id, $WS->groupmember->groupid, $WS->member->id, $USER->id))) // only modify the grade
        {
            $grade = array();
            $grade['id']        = $gradeinstance->id;
            $grade['wikigrade'] = $grade_page;
            $grade['comment']   = $commentary;

            if (!update_record('wiki_evaluation', $grade)) 
                error('gradeslib.php: Can\'t insert the new record');
        }
        else {
            // Start to insert the new valoration
            $grade                      = array();
            $grade['pagename']          = addslashes($WS->page);
            $grade['wikiid']            = $WS->dfwiki->id;
            $grade['groupid']           = $WS->groupmember->groupid;
            $grade['ownerid']           = $WS->member->id;
            $grade['userid']            = $USER->id;
            $grade['wikigrade']         = addslashes($grade_page);
            $grade['wikigrade_initial'] = addslashes($grade_page);
            $grade['comment']           = $commentary;

            if (!insert_record('wiki_evaluation', $grade)) 
                error('gradeslib.php: Can\'t insert the new record');
        }

        // add grade and comment to evaluation page
        $append = optional_param('grade_appendtodiscussion', '', PARAM_TEXT);
        if ($append == 'on')
            wiki_grade_append_to_discussion($WS, $WS->page, $grade_page, $commentary);
    }
}

function wiki_grade_append_to_discussion($WS, $pagename, $grade, $comment)
{
    global $USER, $CFG, $COURSE;

	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
    $wikimanager = wiki_manager_get_instance();

    $title = get_string('eval_discuss_title', 'wiki');
    switch ($WS->dfform['editor']) 
    {
        case 'ewiki':
            $append = chr(13).chr(10).'!!! '.$title.' '.chr(13).chr(10);
            break;
        case 'htmleditor':
            $append = chr(13).chr(10).'<h1> '.$title.' </h1>'.chr(13).chr(10);
            break;
        default:
            $append = chr(13).chr(10).'= '.$title.' ='.chr(13).chr(10);
            break;
    }

    $anonymous = optional_param('grade_anonymous', '', PARAM_TEXT);
    if ($anonymous == 'on')
        $a->user = get_string('eval_anonymous_user', 'wiki');
    else {
        $info = $wikimanager->get_user_info_by_id($USER->id);
        $userprofile = '<a href="'. $CFG->wwwroot .'/user/view.php?id='.
                       $USER->id .'&amp;course='. $COURSE->id .'">'.
                       $info->firstname.' '.$info->lastname.'</a>';
        $a->user = get_string('user').' '.$userprofile;
    }
    $a->grade = wiki_grade_get_wikigrade($WS, $grade);
    $append .= get_string('eval_discuss_body_1', 'wiki', $a);
    $append .= "\n $comment\n";
    unset($a);
    $a->quality = wiki_grade_get_quality($pagename, $WS->dfwiki->id, $WS->member->id, 
                                         $WS->groupmember->groupid, 0);
    $append .= get_string('eval_discuss_body_2', 'wiki', $a);

    $pagename = 'discussion:'.$pagename;
    $data->pagename = $pagename;

    $page = wiki_page_last_version($pagename, $WS);

    if ($page) 
    {
        $txt           = $page->content.$append;

        $data->version = $page->version + 1;
        $data->created = $page->created;

        //get internal links of the page
        $links_refs  = wiki_sintax_find_internal_links($txt);
        $links_clean = wiki_clean_internal_links($links_refs);
        $txt         = wiki_set_clean_internal_links($txt, $links_refs, $links_clean);

        $data->refs     = wiki_internal_link_to_string($links_refs);
        $data->editor   = $page->editor;
        $data->editable = $page->editable;
        $data->hits     = $page->hits;
    } else
    {
        $txt            = $append;
        $data->version  = 1;
        $data->created  = time();
        $data->refs     = '';
        //$data->editor   = $WS->dfform['editor']; 
        $data->editor   = 'nwiki';
        $data->editable = 1;
        $data->hits     = 0;
    }

    $data->author       = $USER->username;
    $data->userid       = $USER->id;
    $data->ownerid      = $WS->member->id;
    $data->lastmodified = time();
    $data->content      = addslashes($txt);
    $data->dfwiki       = $WS->dfwiki->id;
    $data->groupid      = $WS->groupmember->groupid;

    //$WS->groupmember->groupid = isset($WS->gid) ? $WS->gid : $WS->groupmember->groupid;

    //Check if the version passed is the last one or another one.
    if (($max=wiki_page_current_version_number ($data,$WS))>=$data->version){
        notify ("WARNING: some discussion version may be overwrited.", 'notifyproblem', $align='center');
        $data->version = $max+1;
    }

    ///Add some slashes before inserting the record
    $data->pagename = addslashes($data->pagename);

    $newpage = new wiki_page(PAGERECORD, $data);
    if (!$pageid = $wikimanager->save_wiki_page($newpage))
        error(get_string('noinsert','wiki'));

    add_to_log($COURSE->id, 'wiki', 'save page', addslashes("view.php?id={$WS->cm->id}&amp;page=$pagename"), 
               $pageid, $WS->cm->id);
}

function wiki_grade_print_edition_evaluation_box(&$WS)
{
    $context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);

    if (has_capability('mod/wiki:evaluateawiki',$context))
    {
        print_box_start();

        $prop = null;
        $prop->id = "form_grades";
        $prop->method = "post";
        $prop->action =  'view.php?id='.$WS->linkid.'&amp;page='.urlencode($WS->pageaction.'/'.stripslashes_safe($WS->page)).'&amp;dfcontent=11&amp;ver='.$WS->ver.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id;

        wiki_form_start($prop);

        $prop = null;
        $prop->padding = "2";
        $prop->aligntd = "right";
        wiki_table_start($prop);

        echo ('<b>'.get_string('grade').'</b>:&nbsp;');

        wiki_change_column();

         /// 1 -> "+", 2 -> "=", 3 -> "-"

        $prop = null;
        $prop -> value = '1';
        $opt = wiki_option('+', $prop, true);

        $prop = null;
        $prop -> value = '2';
        $opt .= wiki_option('=', $prop, true);

        $prop = null;
        $prop -> value = '3';
        $opt .= wiki_option('-', $prop, true);

        $prop = null;
        $prop->name = "grade_edition";
        wiki_select($opt, $prop);
        echo('&nbsp');

        $prop = null;
        $prop->name = 'grade_edition_confirm';
        $prop->value = get_string('set','wiki');
        wiki_input_submit($prop); 

        unset($prop);
        $prop->name = 'oldversion';
        $prop->value = $WS->pageolddata->version; 
        wiki_input_hidden($prop);

        unset($prop);
        $prop->name = 'currentversion';
        $prop->value = $WS->pageverdata->version; 
        wiki_input_hidden($prop);

        wiki_change_row();
        echo('<b>'.get_string('eval_feedback', 'wiki').'</b>:&nbsp');
        wiki_change_column();
        unset($prop);
        $prop->size = 60;
        $prop->value = '';
        $prop->name = 'feedback';
        wiki_input_text($prop);

        $prop->align = 'right';
        wiki_change_row($prop);
        echo ('<i>'.get_string('note', 'wiki').'</i>:&nbsp;');
        wiki_change_column();

        $oldversion     = $WS->pageolddata->version; 
        $currentversion = $WS->pageverdata->version; 
        if ($currentversion < $oldversion) {
            $tmp            = $currentversion;
            $currentversion = $oldversion;
            $oldversion     = $tmp;
        }
        $version = $oldversion + 1;
        
        $count = $version - $currentversion;
        if ($count == 1)
            echo(get_string('evaluate_hist_edition', 'wiki').' '.$version);
        else {
            echo('<i>'.get_string('evaluate_hist_editions', 'wiki').': ');
            for ($i = $version; $i <= $currentversion; $i++) {
                echo($i);
                if ($i < $currentversion)
                    echo(', ');
            }
            echo('</i>');
        }
        wiki_table_end();

        wiki_form_end();

        print_box_end();
    }
}

function wiki_grade_set_edition_grade(&$WS)
{

global $USER;

$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);

    if ( optional_param('grade_edition_confirm',NULL,PARAM_ALPHA) === get_string('set','wiki') && (has_capability('mod/wiki:mainreview', $context) || has_capability('mod/wiki:peerreview', $context)) )
    {
            $grade_edition  = optional_param('grade_edition', NULL, PARAM_INT);
            $feedback       = optional_param('feedback', NULL, PARAM_TEXT);
            $oldversion     = optional_param('oldversion', 0, PARAM_INT); // This version isn't valuated
            $currentversion = optional_param('currentversion', 0, PARAM_INT);

            if (!isset($grade_edition)) { error('grades.lib.php: There isn\'t any edition grade.'); }

            if ($currentversion < $oldversion) {
                $tmp            = $currentversion;
                $currentversion = $oldversion;
                $oldversion     = $tmp;
            }
            $version = $oldversion + 1; 

            while ($version <= $currentversion)
            {
                $where = 'pagename="'.$WS->page.'" AND dfwiki='.$WS->dfwiki->id.' AND groupid='.$WS->groupmember->groupid.' AND ownerid='.$WS->member->id.' AND version='.$version;

                if (!$wikipage_instance = get_record_select('wiki_pages', $where, '*')) { error('grades.lib.php: Don\'t exist the wikipage\'s version '.$version); }

                if (false != ($gradeinstance = wiki_grade_edition_exist($wikipage_instance->id, $USER->id))) // only modify the grade
                {
                    $grade = array();
                    $grade['id']= $gradeinstance->id;
                    $grade['valoration']= (string)$grade_edition;
                    $grade['feedback'] = isset($feedback) ? $feedback : null;

                    if (!update_record('wiki_evaluation_edition', $grade)) {error('gradeslib.php: Can\'t update the new record');}
                }

                else {
                    // Start to insert the new valoration
                    $grade = array();
                    $grade['wiki_pageid']= $wikipage_instance->id;
                    $grade['userid']= $USER->id;
                    $grade['valoration']= (string)$grade_edition;
                    $grade['feedback'] = isset($feedback) ? $feedback : null;

                    if (!insert_record('wiki_evaluation_edition', $grade)) {error('gradeslib.php: Can\'t insert the new record');}
           
                }
 
                $version++;
            }
            
    }

}

function wiki_grade_evaluation_exist($pagename, $wikiid, $groupid, $ownerid, $userid)
{
    global $CFG;

    $where = 'pagename="'.addslashes($pagename).'" AND wikiid='.$wikiid.' AND groupid='.$groupid.' AND ownerid='.$ownerid.' AND userid='.$userid;

    $result = get_record_select('wiki_evaluation', $where, '*');

    if ($result != false) { return $result; }
    else { return false; }
}

function wiki_grade_edition_exist($wiki_pageid, $userid)
{
    global $CFG;

    $where = 'wiki_pageid='.$wiki_pageid.' AND userid='.$userid;

    $result = get_record_select('wiki_evaluation_edition', $where, '*');

    if ($result != false) { return $result; }
    else { return false; }
}

function wiki_grade_get_quality($pagename, $wikiid, $ownerid, $groupid, $userid) 
{
    global $CFG;

    $scale_ed = array(1 => "+", 2 => "=", 3 => "-");

    $where = '';
    if ($userid != 0) $where.=" AND wp.userid=$userid";

    $positives = get_field_sql("SELECT count(*) positives FROM {$CFG->prefix}wiki_pages wp ".
                        "INNER JOIN {$CFG->prefix}wiki_evaluation_edition ee ".
                        'ON wp.id = ee.wiki_pageid '.
                        'WHERE wp.pagename = "'.$pagename.'" AND wp.dfwiki = '.$wikiid.
                        ' AND wp.ownerid = '.$ownerid.' AND wp.groupid = '.$groupid .
                        " AND ee.valoration='1' $where");

    $equals = get_field_sql("SELECT count(*) equals FROM {$CFG->prefix}wiki_pages wp ".
                        "INNER JOIN {$CFG->prefix}wiki_evaluation_edition ee ".
                        'ON wp.id = ee.wiki_pageid '.
                        'WHERE wp.pagename = "'.$pagename.'" AND wp.dfwiki = '.$wikiid.
                        ' AND wp.ownerid = '.$ownerid.' AND wp.groupid = '.$groupid .
                        " AND ee.valoration='2' $where");

    $negatives = get_field_sql("SELECT count(*) negatives FROM {$CFG->prefix}wiki_pages wp ".
                        "INNER JOIN {$CFG->prefix}wiki_evaluation_edition ee ".
                        'ON wp.id = ee.wiki_pageid '.
                        'WHERE wp.pagename = "'.$pagename.'" AND wp.dfwiki = '.$wikiid.
                        ' AND wp.ownerid = '.$ownerid.' AND wp.groupid = '.$groupid .
                        " AND ee.valoration='3' $where");

    $value = $positives - $negatives;
    $total = $positives + $negatives + $equals;

    if ($total != 0 && $value != 0)
    {
        if ($value < 0) $take = $negatives;
        else $take = $positives;
        $average = round((($take) * 100) / $total, 2);
        $average = ' ('.$average.'%)';
    } else
        $average = '';

    if ($value < 0) $value = 3;
    elseif ($value > 0) $value = 1;
    else {
        if ($total != 0) {
            $value = 2;
        } else
            $avg_eds = get_string('eval_notset', 'wiki');
    }
    if ($total != 0)
        $avg_eds = wiki_grade_translate($value, $scale_ed);

    $ret = $avg_eds.$average;
    return $ret;
}

function wiki_grade_print_tables($group, $user, &$wiki, &$cmodule, &$scale, &$course)
{
    //print_object('g: '.$group.', u: '.$user.', gm: '.$cmodule->groupmode.', sm:'.$wiki->studentmode);

    global $CFG, $WS;

    $context = get_context_instance(CONTEXT_MODULE, $cmodule->id);    

    if (has_capability('mod/wiki:mainreview', $context))
    {
        $where = wiki_grade_get_sql_filter($cmodule->groupmode, $wiki->studentmode, $group, $user);

        $by = '';
        $unames = '';
        if ($user != 0) {
            $query  = "SELECT u.firstname, u.lastname FROM {$CFG->prefix}user u WHERE u.id = $user";
            $unames = get_record_sql($query);
            $by = ' '.strtolower(get_string('edited_by', 'wiki'))." {$unames->firstname} {$unames->lastname}";
        }
        $tables = '<h3 class="headingblock header outline">'.get_string('eval_wiki_pages', 'wiki').$by.'</h3>';

        if (!$result = get_records_sql('SELECT id, ownerid, pagename, wikiid, groupid, AVG(wikigrade) as wikigrade 
                                       FROM '.$CFG->prefix.'wiki_evaluation 
                                       WHERE wikiid='.$wiki->id.' AND '.$where.
                                       ' GROUP BY pagename, wikiid, groupid, ownerid')) 
        {
            echo($tables);
            echo('<p>grades.lib.php: there is no wikigrade yet.</p>');
            return;
        }

        $rows = array();
        foreach ($result as $eval)
        {
            $hits = get_record_sql('SELECT wp.hits
                                    FROM '.$CFG->prefix.'wiki_pages wp
                                    WHERE wp.pagename="'.$eval->pagename.'" AND wp.dfwiki='.$eval->wikiid.' AND wp.groupid='.$eval->groupid.' AND wp.ownerid='.$eval->ownerid.' AND wp.version= (SELECT MAX(wp2.version)
                                               FROM '.$CFG->prefix.'wiki_pages wp2
                                               WHERE wp2.pagename=wp.pagename AND wp2.dfwiki=wp.dfwiki AND 
                                                     wp2.groupid=wp.groupid AND wp2.ownerid=wp.ownerid 
                                               GROUP BY wp2.pagename, wp2.dfwiki, wp2.groupid, wp.ownerid )');

            $editions = get_field_sql('SELECT MAX(version) FROM '.$CFG->prefix.'wiki_pages wp'.
                                      ' WHERE wp.pagename="'.$eval->pagename.'" AND wp.dfwiki='.$eval->wikiid.
                                      ' AND wp.groupid='.$eval->groupid.' AND wp.ownerid='.$eval->ownerid);

             $rows[] = array('pagename'=>$eval->pagename, 'groupid'=>$eval->groupid, 
                             'ownerid'=>$eval->ownerid, 'wikigrade'=>$eval->wikigrade, 
                             'hits'=> $hits->hits, 'wikiid' => $eval->wikiid, 
                             'editions'=> $editions);
        }

        // INITIALIZATIONS FOREACH

        // Array of the users to avoid repeticions
        $users = array();

        // Array of the groups to avoid repeticions
        $groups = array();

        // Scale (1.0 ... n.0)
        $scale_values = split(',', $scale->scale);
        $num_values = count($scale_values);
        $values = array();
        $i=0;
        while ($i < $num_values)
        {
            $values[$i+1] = trim($scale_values[$i]);
            $i++;
        }

        $total_hits = 0;
        $total_editions = 0;
        
///////////// WIKIPAGES ////////////////////////////////////////////////////////////////////////
        $number_editions_by_user = 0;
        if ($user != 0) {
            foreach ($rows as $row)
            {
                $eds = get_field_sql("SELECT count(*) FROM {$CFG->prefix}wiki_pages wp ".
                                     'WHERE wp.pagename = "'.$row['pagename'].'" AND wp.dfwiki = '.$row['wikiid'].
                                     ' AND wp.ownerid = '.$row['ownerid'].' AND wp.groupid = '.$row['groupid'].
                                     " AND wp.userid=$user");
                $number_editions_by_user += $eds;
            }
        } else
            $number_editions_by_user = 1;

        if ($number_editions_by_user == 0)
            $tables .= '<p>'.get_string('eval_wiki_pages_no_editions', 'wiki').'</p>';
        else 
        {
            $tables .= '<p>'.
            '<table border="1" cellspacing="1" cellpadding="5" width="100%" class="generaltable boxalignleft">'."\n".
            '<tr>'."\n".
            '   <th valign="top" class="nwikileftnow header c0">'.get_string('pagename', 'wiki').'</th>'."\n";

            if ($cmodule->groupmode) 
                $tables .= '   <th valign="top" class="nwikileftnow header c1">'.get_string('group').'</th>'."\n";
            if ($wiki->studentmode)
                $tables .= '   <th valign="top" class="nwikileftnow header c2">'.get_string('eval_owner', 'wiki').'</th>'."\n";

            if ($user == 0) {
                $stringquality  = get_string('eval_avg_quality', 'wiki');
                $stringeditions = get_string('eval_editions', 'wiki');
            } else {
                $stringquality  = get_string('eval_avg_user_quality', 'wiki');
                $stringeditions = get_string('eval_editions_user', 'wiki');
            }

            $tables .=
            '   <th valign="top" class="nwikileftnow header c3">'.get_string('grade').'</th>'."\n".
            '   <th valign="top" class="nwikileftnow header c4">'.$stringquality.'</th>'."\n".
            '   <th valign="top" class="nwikileftnow header c5">'.get_string('eval_hits', 'wiki').'</th>'."\n".
            '   <th valign="top" class="nwikileftnow header c6">'.$stringeditions.'</th>'."\n";

            if ($cmodule->groupmode == 0 && $wiki->studentmode == 0)
                $tables .= '   <th valign="top" class="nwikileftnow header c7">'.get_string('eval_authors', 'wiki').'</th>'."\n";

            $tables .= '</tr>'."\n";

            $scale_ed = array(1 => "+", 2 => "=", 3 => "-");

            $avg_eds = 0;
            foreach ($rows as $row)
            {
                if ($user != 0) {
                    $eds = get_field_sql("SELECT count(*) FROM {$CFG->prefix}wiki_pages wp ".
                                        'WHERE wp.pagename = "'.$row['pagename'].'" AND wp.dfwiki = '.$row['wikiid'].
                                        ' AND wp.ownerid = '.$row['ownerid'].' AND wp.groupid = '.$row['groupid'].
                                        " AND wp.userid=$user");
                    if ($eds == 0)
                        continue;
                }

                /* pagename */
                $url  = $CFG->wwwroot.'/mod/wiki/'.
                        "view.php?id={$cmodule->id}&amp;page=view/".urlencode($row['pagename']);
                $link = '<a href="'.$url.'">'.$row['pagename'].'</a>';
                $tables .=
                '<tr>'."\n".
                '   <td class="textcenter nwikibargroundblanco">'.$link.'</td>'."\n";

                /* group */
                if ($cmodule->groupmode) 
                {
                    $groupid = $row['groupid'];
                    if ( $groupid == 0)
                        $stringgroup = ' ';
                    else if (isset($groups[$groupid]))
                        $stringgroup = $groups[$groupid];
                    else 
                    {
                        $group = get_record('groups', 'id', $groupid);
                        $groups[$groupid] = $group->name;
                        $stringroup = $groups[$groupid];
                    }

                    $tables .= '   <td class="textcenter nwikibargroundblanco">'.$stringgroup.'</td>'."\n";
                }

                /* owner */
                if ($wiki->studentmode)
                {
                    $userid = $row['ownerid'];
                    if ( $userid == 0) { 
                        $stringuser = ' '; 
                    } else if (isset($users[$userid])) {
                        $stringuser = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userid.'&amp;course='.$course->id.'">'.$users[$userid]->firstname.' '.$users[$userid]->lastname.'</a>';
                    } else {
                        $userdata = get_record('user', 'id', $userid);
                        $users[$userid] = $userdata;
                        $stringuser = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userid.'&amp;course='.$course->id.'">'.$users[$userid]->firstname.' '.$users[$userid]->lastname.'</a>';
                    }

                    $tables .= '   <td class="textcenter nwikibargroundblanco">'.$stringuser.'</td>'."\n";
                } else
                    $userid = 0;

                // grade
                $stringwikigrade = wiki_grade_translate($row['wikigrade'], $values);
                $tables .= '   <td class="textcenter nwikibargroundblanco">'.$stringwikigrade.'</td>'."\n";

                // quality of the grade
                $avg_eds = wiki_grade_get_quality($row['pagename'], $row['wikiid'], $row['ownerid'], $row['groupid'], $user);
                $tables .= '   <td class="textcenter nwikibargroundblanco">'.$avg_eds.'</td>'."\n";

                // hits
                $tables .= '   <td class="textcenter nwikibargroundblanco">'.$row['hits'].'</td>'."\n";
                $total_hits += $row['hits'];

                // editions
                if ($user == 0) {
                    $tables .= '   <td class="textcenter nwikibargroundblanco">'.$row['editions'].'</td>'."\n";
                    $total_editions += $row['editions'];
                } else {
                    $tables .= '   <td class="textcenter nwikibargroundblanco">'.$eds.'</td>'."\n";
                    $total_editions += $eds;
                }

                // authors
                $myauthors = get_records_sql('SELECT user.id, user.firstname, user.lastname
                                       FROM '.$CFG->prefix.'wiki_pages wp INNER JOIN '.$CFG->prefix.'user user 
                                       ON wp.userid = user.id 
                                       WHERE wp.pagename = "'.$row['pagename'].'" AND wp.dfwiki = '.$row['wikiid'].
                                       ' AND wp.ownerid = '.$row['ownerid'].' AND wp.groupid = '.$row['groupid']); 
                $authors = array();
                foreach ($myauthors as $author)
                    $authors[] = $author;

                $string_authors = '';
                $count = count($authors);
                for ($i = 0; $i < $count; $i++) {
                    $author = $authors[$i];
                    $string_author  = '<a href="'.$CFG->wwwroot.'/mod/wiki/grades/grades.evaluation.php?'.
                                      'cid='.$course->id.'&amp;cmid='.$cmodule->id.'&amp;group='.$group.
                                      '&amp;user='.$author->id.'">'.$author->firstname.' '.$author->lastname.'</a>';

                    if ($author->id == $user)
                        $string_authors .= '<b>'.$string_author.'</b>';
                    else
                        $string_authors .= $string_author;
                    if ($i < $count - 1)
                        $string_authors .= ', ';
                }

                if ($cmodule->groupmode == 0 && $wiki->studentmode == 0)
                    $tables .= '   <td class="nwikibargroundblanco">'.$string_authors.'</td>'."\n";

                // row end
                $tables .= '</tr>'."\n";
            }

            $tables .= '<tr>'."\n";
            $columns = 5;
            if (!$cmodule->groupmode) $columns--;
            if (!$wiki->studentmode) $columns--;
            for ($i = 0; $i < $columns; $i++)
                $tables .= '   <td class="textcenter"></td>'."\n";
            $tables .= '   <td class="textcenter nwikibargroundgris"><strong>'.$total_hits.'</strong></td>'."\n";
            $tables .= '   <td class="textcenter nwikibargroundgris"><strong>'.$total_editions.'</strong></td>'."\n";
            $tables .= '</tr>'."\n";
            $tables .= '</table></p>'."\n";

            $tables .= "<p>* Scale = $scale->scale<br/>\n"; 
            $tables .= "\n";
        }

        // EDITIONS ///////////////////////////////////////////////////////////////////////////////////
        //$scale_ed = array(1 => "+", 2 => "=", 3 => "-");
        if ($user == 0)
        {
            $tables .= '<h3 class="headingblock header outline">'.get_string('eval_wiki_pages_editions', 'wiki')."</h3>";
            $tables .= '<p>'.get_string('eval_wiki_pages_editions_select_user', 'wiki').'</p>';
            echo $tables;
            return;
        }

        $by = ' '.strtolower(get_string('by', 'wiki'))." {$unames->firstname} {$unames->lastname}";
        $tables .= '<h3 class="headingblock header outline">'.get_string('eval_wiki_pages_editions', 'wiki').$by."</h3>";

        $editions = 0;
        foreach ($rows as $row)
        {
            $ed = get_field_sql("SELECT count(*) FROM {$CFG->prefix}wiki_pages wp ".
                                  "INNER JOIN {$CFG->prefix}wiki_evaluation_edition ee ".
                                  'ON wp.id = ee.wiki_pageid '.
                                  'WHERE wp.pagename = "'.$row['pagename'].'" AND wp.dfwiki = '.$row['wikiid'].
                                  ' AND wp.ownerid = '.$row['ownerid'].' AND wp.groupid = '.$row['groupid'].
                                  " AND wp.userid=$user");

            if ($ed == 0) {
                continue;
            }
            $editions++;

            if ($editions == 1) {
                $tables .= '<p>'.
                '<table border="1" cellspacing="1" cellpadding="5" width="100%" class="generaltable boxalignleft">'."\n".
                '<tr>'."\n".
                '   <th valign="top" class="nwikileftnow header c0">'.get_string('pagename', 'wiki').'</th>'."\n".
                '   <th valign="top" class="nwikileftnow header c1">'.get_string('eval_edition', 'wiki').'</th>'."\n".
                '   <th valign="top" class="nwikileftnow header c2">'.get_string('eval_editions_quality', 'wiki').'</th>'."\n".
                '   <th valign="top" class="nwikileftnow header c3">'.get_string('date').'</th>'."\n".
                '   <th valign="top" class="nwikileftnow header c4">'.get_string('eval_feedback', 'wiki').'</th>'."\n";
                if ($cmodule->groupmode == 0 && $wiki->studentmode == 0)
                    $tables .= '   <th valign="top" class="nwikileftnow header c5">'.get_string('eval_diff_to_prev', 'wiki').'</th>'."\n";
                $tables .= '</tr>'."\n";
            }

            $where = '';
            if ($user != 0) { $where.=" AND wp.userid=$user"; }
            $wp = get_records_sql('SELECT wp.id, wp.version, wp.lastmodified, user.firstname, user.lastname
                                   FROM '.$CFG->prefix.'wiki_pages wp INNER JOIN '.$CFG->prefix.'user user 
                                   ON wp.userid = user.id 
                                   WHERE wp.pagename = "'.$row['pagename'].'" AND wp.dfwiki = '.$row['wikiid'].' AND wp.ownerid = '.$row['ownerid'].' AND wp.groupid = '.$row['groupid'].$where); 
            
            $tp = 0; $te = 0; $tn = 0;
            foreach ($wp as $w) // For version
            {
                $p=0; $e=0; $n=0;      
            
                if (! $grades = get_records_sql('SELECT * 
                                                 FROM '.$CFG->prefix.'wiki_evaluation_edition e
                                                 WHERE e.wiki_pageid='.$w->id))
                { continue; } // There isn't any grade

                else // There are some grade
                {
                    /* page name */
                    $url  = $CFG->wwwroot.'/mod/wiki/'.
                            "view.php?id={$cmodule->id}&amp;page=view/".urlencode($row['pagename']);
                    $link = '<a href="'.$url.'">'.$row['pagename'].'</a>';

                    $tables .=
                    '<tr>'."\n".
                    '   <td class="textcenter nwikibargroundblanco">'.$link.'</td>'."\n";

                    foreach ($grades as $grade)
                    {   
                        /// 1 -> "+", 2 -> "=", 3 -> "-"
                        switch ($grade->valoration)
                        {
                            case "1": $p++; $tp++; break;
                            case "2": $e++; $te++; break;
                            case "3": $n++; $tn++; break;
                        }
                    }

                    //Edition                
                    $tables .= '   <td class="textcenter nwikibargroundblanco"><b>'.$w->version.'</b></td>'."\n";
     
                    // Quality: +, -, =
                    $sign = wiki_grade_translate($grade->valoration, $scale_ed);
                    $tables .= '   <td class="textcenter nwikibargroundblanco">'.$sign.'</td>'."\n";

                    /* Date */
                    $modified = strftime('%A, %d %B %Y, %H:%M',$w->lastmodified);
                    $tables .= '   <td class="textcenter nwikibargroundblanco">'.$modified.'</td>'."\n";

                    /* feedback */
                    if ($grade->feedback != '')
                        $feedback = $grade->feedback;
                    else
                        $feedback = get_string('eval_notset', 'wiki');
                    $tables .= '   <td class="textcenter nwikibargroundblanco">'.$feedback."</td>\n";

                    /* diff */
                    if ($cmodule->groupmode == 0 && $wiki->studentmode == 0) {
                    if ($w->version > 1) {
                        $url  = $CFG->wwwroot.'/mod/wiki/'.
                                "view.php?id={$cmodule->id}&amp;page=diff/".urlencode($row['pagename']).
                                '&amp;ver='.($w->version).'/'.($w->version - 1).'&amp;gid=0'.
                                '&amp;uid=0&amp;dfcontent=11';
                        $diff = '<a href="'.$url.'">'.get_string('eval_diff', 'wiki').'</a>';
                    } else
                        $diff = '';
                    $tables .= '   <td class="textcenter nwikibargroundblanco">'."$diff</td>\n";
                    }

                    // end row
                    $tables .= '</tr>';
                }
            }
 
            // Total Row
            $tables .= '<tr>';
            $tables .= '   <td class="textcenter">'.' '.'</td>'."\n";
            $tables .= '   <td class="textcenter">'.' '.'</td>'."\n";

            $value = $tp - $tn;
            if     ($value < 0) $value = 3;
            elseif ($value > 0) $value = 1;
            else                $value = 2;
            $avg_value = wiki_grade_translate($value, $scale_ed);
            $tables .= '   <td class="textcenter nwikibargroundgris">'."<b>$avg_value</b></td>\n";

            $tables .= '   <td class="textcenter">'.' '.'</td>'."\n";
            $tables .= '   <td class="textcenter">'.' '.'</td>'."\n";
            if ($cmodule->groupmode == 0 && $wiki->studentmode == 0)
                $tables .= '   <td class="textcenter">'.' '.'</td>'."\n";

            $tables .= '</tr>';

        }

        $tables .= '</table>';

        if ($editions == 0)
            $tables .= '<p>'.get_string('eval_wiki_pages_editions_no_eval', 'wiki').'</p>';
        else
            $tables .= '<br/>';

        echo $tables;
    }
    else
        echo('<b>You don\'t have the capability required.</b>');
}

function wiki_grade_print_user_info($userid, $grade)
{
    global $CFG, $COURSE;

    if ($userid == 0) return;

    $wikimanager = wiki_manager_get_instance();
    $info        = $wikimanager->get_user_info_by_id($userid);
    $res = wiki_grade_print_user($info, $COURSE, $grade, '', true);

    echo $res;
}

function wiki_grade_print_user_picture($user, $courseid, $size, $class)
{
    global $CFG;

    $cmid = required_param('cmid', PARAM_INT);

    //$output = '<a href="'. $CFG->wwwroot .'/user/view.php?id='. $user->id .'&amp;course='. $courseid .'">';
    $group = optional_param('group', -1, PARAM_INT);

    if ($group < 0) 
        $output  = '<a href="'.$CFG->wwwroot.'/mod/wiki/grades/grades.evaluation.php?'.
                          'cid='.$courseid.'&amp;cmid='.$cmid.'&amp;group=0'.
                          '&amp;user='.$user->id.'">';
    else
        $output  = '<a href="'.$CFG->wwwroot.'/mod/wiki/grades/grades.evaluation.php?'.
                          'cid='.$courseid.'&amp;cmid='.$cmid.'&amp;group='.$group.
                          '&amp;user='.$user->id.'">';

    if (!empty($HTTPSPAGEREQUIRED)) {
        $wwwroot = $CFG->httpswwwroot;
    } else {
        $wwwroot = $CFG->wwwroot;
    }

    if ($size >= 50) {
        $file = 'f1';
    } else {
        $file = 'f2';
    }

    $picture = $user->picture;
    if ($picture) {  // Print custom user picture
        if ($CFG->slasharguments) {        // Use this method if possible for better caching
            $src =  $wwwroot .'/user/pix.php/'. $user->id .'/'. $file .'.jpg';
        } else {
            $src =  $wwwroot .'/user/pix.php?file=/'. $user->id .'/'. $file .'.jpg';
        }
    } else {         // Print default user pictures (use theme version if available)
        $src =  "$CFG->pixpath/u/$file.png";
    }

    $imagealt = get_string('pictureof','',fullname($user));

    $output .= '<img class="'.$class.'" src="'.$src.'" width="'.$size.'px" height="'.$size.'" alt="'.s($imagealt).'" />';
    $output .= '</a>';

    return $output;
}

function wiki_grade_get_users_info($courseid, $wikiid) 
{
    global $CFG;

    $USERS_PER_PAGE = 18;

    $group = optional_param('group', -1, PARAM_INT);

    if ($group >= 0) {
        $users = get_group_students($group);
        $groups = groups_get_all_groups($courseid);
        $groupname = $groups[$group]->name;
        $a->of = 'of group '.$groupname;
    }
    else {
        $users = get_course_users($courseid, 'u.lastname', '', 'u.id, u.firstname, u.lastname, u.idnumber');
        $a->of = '';
    }
    $users = array_values($users);

    $num_users    = count($users);
    $num_pages    = wiki_grade_get_num_userpages($num_users, $USERS_PER_PAGE) ;

    $userlistpage = optional_param('userlistpage', 0, PARAM_INT);
    if ($userlistpage >= $num_pages) $userlistpage = 0;

    $from = $userlistpage * $USERS_PER_PAGE;
    if ($userlistpage == $num_pages - 1)
        $to = $from + ($num_users % $USERS_PER_PAGE);
    else
        $to = $from + $USERS_PER_PAGE;

    $output = print_box_start('generalbox', '', true);
    
    $output .= '<div style="float:left">';
    $a->from = $from + 1; $a->to = $to; $a->total = $num_users; 
    $output .= get_string('eval_show_userlist', 'wiki', $a);
    $output .= '</div>';

    $output .= '<div style="float:right">';
    $output .= '<table><tr><td><i>'.get_string('legend', 'wiki').':&nbsp;</i></td>';
    $output .= '<td class="borderred">'.get_string('eval_legend_user_nograde', 'wiki').'</td>';
    $output .= '<td>&nbsp;&nbsp;</td>';
    $output .= '<td class="bordergreen">'.get_string('eval_legend_user_grade', 'wiki').'</td>';
    $output .= '</tr></table>';
    $output .= '</div>';

    $output .= '<div style="clear:both">&nbsp;</div>';

    if (!$users)
        echo('grades.lib.php: there is not any user in this course');
    else {
        $wikimanager = wiki_manager_get_instance();

        $output .= '<ul class="userlist">';
        $output .= "\n\n".'<div class="container"><br/>';
        for ($i = $from; $i < $to; $i++) {
            $myuser = $users[$i];

            $output .= '<li>';
            $info   = $wikimanager->get_user_info_by_id($myuser->id);

            $grade = grade_get_grades($courseid, 'mod', 'wiki', $wikiid, $myuser->id);
            if (isset($grade->items[0]->grades[$myuser->id]))
                $grade = $grade->items[0]->grades[$myuser->id]->str_grade;
            else
                $grade = null;
             
            $output .= "\n".wiki_grade_get_user_info($info, $courseid, $grade);
            $output .= '</li>';
        }
        $output .= '</ul>'."\n\n";
        $output .= wiki_grade_get_userpages($num_users, $userlistpage, $courseid, $USERS_PER_PAGE);
        $output .= '</tr></td></table>';

        $output .= print_box_end(true);
        echo($output);
    } 
}

function wiki_grade_get_num_userpages($num_users, $USERS_PER_PAGE) 
{
    if ($num_users <= $USERS_PER_PAGE) return 1;

    if ($num_users % $USERS_PER_PAGE == 0)
        $num_pages = (int)($num_users/$USERS_PER_PAGE);
    else 
        $num_pages = (int)($num_users/$USERS_PER_PAGE) + 1;

    return $num_pages;
}

function wiki_grade_get_userpages($num_users, $userlistpage, $courseid, $USERS_PER_PAGE) 
{
    global $CFG;

    $num_pages = wiki_grade_get_num_userpages($num_users, $USERS_PER_PAGE) ;

    $cmid  = optional_param('cmid', 0, PARAM_INT);
    $group = optional_param('group', -1, PARAM_INT);

    if ($group < 0)
        $link = $CFG->wwwroot.'/mod/wiki/grades/grades.evaluation.php?cid='.
                $courseid.'&amp;cmid='.$cmid;
    else
        $link = $CFG->wwwroot.'/mod/wiki/grades/grades.evaluation.php?cid='.
                $courseid.'&amp;cmid='.$cmid.'&amp;group='.$group;

    $output = '<i>'.get_string('pages', 'wiki').'</i>: [ ';

    if ($userlistpage != 0)
        $output .= '<a href="'.$link.'&amp;userlistpage='.($userlistpage-1).'">« '.get_string('prev', 'wiki').'</a> | ';

    for ($i = 0; $i < $num_pages; $i++)
    {
        $pagelink = '<a href="'.$link.'&amp;userlistpage='.$i.'">'.($i+1).'</a>';
        if ($i == $userlistpage)
            $pagelink = '<b><u>'.$pagelink.'</u></b>';

        if ($i != $num_pages - 1)
            $output .= $pagelink.' - ';
        else 
            $output .= $pagelink;
    }
    if ($userlistpage != $num_pages - 1)
        $output .= ' | <a href="'.$link.'&amp;userlistpage='.($userlistpage+1).'">'.get_string('next').' »</a>';

    return $output.' ]';
}

function wiki_grade_get_user_info($user, $courseid, $grade) 
{

    global $CFG, $USER;

    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    if (isset($user->context->id)) {
        $usercontext = get_context_instance_by_id($user->context->id);
    }

    $output  = '';
    if ($grade && $grade != '-') {
        $output .= wiki_grade_print_user_picture($user, $courseid, '90', 'bordergreen');
        $output .= '<br/>';
        $output .= fullname($user, has_capability('moodle/site:viewfullnames', $context));
        $output .= ' (<b>'.$grade.'</b>)';
    } else {
        $output .= wiki_grade_print_user_picture($user, $courseid, '90', 'borderred');
        $output .= '<br/>';
        $output .= fullname($user, has_capability('moodle/site:viewfullnames', $context));
    }

    return $output;
}

function wiki_grade_print_user($user, $course, $grade, $messageselect=false, $return=false) {

    global $CFG, $USER;

    $output = '';

    static $string;
    static $datestring;
    static $countries;

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    if (isset($user->context->id)) {
        $usercontext = get_context_instance_by_id($user->context->id);
    }

    if (empty($string)) {     // Cache all the strings for the rest of the page

        $string->email       = get_string('email');
        $string->city        = get_string('city');
        $string->lastaccess  = get_string('lastaccess');
        $string->activity    = get_string('activity');
        $string->unenrol     = get_string('unenrol');
        $string->loginas     = get_string('loginas');
        $string->fullprofile = get_string('fullprofile');
        $string->role        = get_string('role');
        $string->name        = get_string('name');
        $string->never       = get_string('never');

        $datestring->day     = get_string('day');
        $datestring->days    = get_string('days');
        $datestring->hour    = get_string('hour');
        $datestring->hours   = get_string('hours');
        $datestring->min     = get_string('min');
        $datestring->mins    = get_string('mins');
        $datestring->sec     = get_string('sec');
        $datestring->secs    = get_string('secs');
        $datestring->year    = get_string('year');
        $datestring->years   = get_string('years');

        $countries = get_list_of_countries();
    }

/// Get the hidden field list
    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    $output .= '<table class="wikigradesuserinfobox">';
    $output .= '<tr>';
    $output .= '<td class="left side">';
    $output .= print_user_picture($user, $course->id, $user->picture, true, true);
    $output .= '</td>';
    $output .= '<td class="content">';

    $output .= '<div class="username">'.fullname($user, has_capability('moodle/site:viewfullnames', $context));
    if ($grade && $grade != '-') {
        $a->grade = $grade;
        $output .= ' '.get_string('eval_user_grade', 'wiki', $a).'</div>';
    } else
        $output .= ' '.get_string('eval_user_nograde', 'wiki').'</div>';

    $output .= '<div class="info">';
    if (!empty($user->role) and ($user->role <> $course->teacher)) {
        $output .= $string->role .': '. $user->role .'<br />';
    }
    if ($user->maildisplay == 1 or ($user->maildisplay == 2 and ($course->id != SITEID) and !isguest()) or
has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $output .= $string->email .': <a href="mailto:'. $user->email .'">'. $user->email .'</a><br />';
    }
    if (($user->city or $user->country) and (!isset($hiddenfields['city']) or !isset($hiddenfields['country']))) {
        $output .= $string->city .': ';
        if ($user->city && !isset($hiddenfields['city'])) {
            $output .= $user->city;
        }
        if (!empty($countries[$user->country]) && !isset($hiddenfields['country'])) {
            if ($user->city && !isset($hiddenfields['city'])) {
                $output .= ', ';
            }
            $output .= $countries[$user->country];
        }
        $output .= '<br />';
    }

    if (!isset($hiddenfields['lastaccess'])) {
        if ($user->lastaccess) {
            $output .= $string->lastaccess .': '. userdate($user->lastaccess);
            $output .= '&nbsp; ('. format_time(time() - $user->lastaccess, $datestring) .')';
        } else {
            $output .= $string->lastaccess .': '. $string->never;
        }
    }
    $output .= '</div></td><td class="links">';
    //link to blogs
    if ($CFG->bloglevel > 0) {
        $output .= '<a href="'.$CFG->wwwroot.'/blog/index.php?userid='.$user->id.'">'.get_string('blogs','blog').'</a><br />';
    }
    //link to notes
    if (has_capability('moodle/notes:manage', $context) || has_capability('moodle/notes:view', $context)) {
        $output .= '<a href="'.$CFG->wwwroot.'/notes/index.php?course=' . $course->id. '&amp;user='.$user->id.'">'.get_string('notes','notes').'</a><br />';
    }

    if (has_capability('moodle/user:viewuseractivitiesreport', $context) || (isset($usercontext) && has_capability('moodle/user:viewuseractivitiesreport', $usercontext))) {
        $timemidnight = usergetmidnight(time());
        $output .= '<a href="'. $CFG->wwwroot .'/course/user.php?id='. $course->id .'&amp;user='. $user->id .'">'. $string->activity .'</a><br />';
    }
    if (has_capability('moodle/role:assign', $context, NULL)) {  // Includes admins
        $output .= '<a href="'. $CFG->wwwroot .'/course/unenrol.php?id='. $course->id .'&amp;user='. $user->id .'">'. $string->unenrol .'</a><br />';
    }
    if ($USER->id != $user->id && empty($USER->realuser) && has_capability('moodle/user:loginas', $context) &&
                                 ! has_capability('moodle/site:doanything', $context, $user->id, false)) {
        $output .= '<a href="'. $CFG->wwwroot .'/course/loginas.php?id='. $course->id .'&amp;user='. $user->id .'&amp;sesskey='. sesskey() .'">'. $string->loginas .'</a><br />';
    }
    $output .= '<a href="'. $CFG->wwwroot .'/user/view.php?id='. $user->id .'&amp;course='. $course->id .'">'. $string->fullprofile .'...</a>';

    if (!empty($messageselect)) {
        $output .= '<br /><input type="checkbox" name="user'.$user->id.'" /> ';
    }

    $output .= '</td></tr></table>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


function wiki_grade_scale_box(&$scale, $return_scale=false)
{
    if (!isset($scale->scale)) {
        wiki_select(null, null);
        return false;
    } else
        $scale = explode(',', $scale->scale);

        $i = 0; 
        $n = count($scale);
        $opt = '';
        while ($i < $n)
        {
            unset($prop);
            $prop->value = $i+1;
            $opt .= wiki_option($scale[$i], $prop, true);
            $i++;
        }
                    
        unset($prop);
        $prop->name = "grade_evaluation_page";
        wiki_select($opt,$prop);

        if ($return_scale) {return $scale;}
        return true;
}

function wiki_grade_translate($wikigrade, $scale)
{
    $grade_with_decimal = round($wikigrade, 1);
    $grade_without_decimal = floor($wikigrade);

    if ($grade_with_decimal - $grade_without_decimal == 0.5) { $value = $scale[(int)$grade_without_decimal].'/'.$scale[((int)$grade_without_decimal)+1]; }

    else if ($grade_with_decimal - $grade_without_decimal < 0.5) { $value = $scale[(int)$grade_without_decimal]; }

    else if ($grade_with_decimal - $grade_without_decimal > 0.5) { $value = $scale[((int)$grade_without_decimal)+1]; }

    else { $value = $scale[(int)$wikigrade]; }

    return trim((trim(trim($value, '0'), '.')));
}

function wiki_grade_get_wikigrade($WS, $gradeid=null) 
{
    global $CFG, $USER;

    $wikimanager = wiki_manager_get_instance();
    $scale = get_record('scale', 'id', (int)$WS->dfwiki->notetype);

    $user  = optional_param('uid', 0, PARAM_INT);
    $group = optional_param('gid', 0, PARAM_INT);
    $where = wiki_grade_get_sql_filter($WS->cm->groupmode, $WS->dfwiki->studentmode, $group, $user);

    if (!$gradeid) {
        $gradeid = get_field_sql('SELECT AVG(wikigrade) as wikigrade 
                                  FROM '.$CFG->prefix.'wiki_evaluation
                                  WHERE wikiid='.$WS->dfwiki->id.' AND pagename="'.addslashes($WS->page).'"'.
                                  ' AND '.$where.   
                                 ' GROUP BY pagename, wikiid, groupid, ownerid'); 
    }
    if (!$gradeid)
        return get_string('eval_notset', 'wiki'); 

    $scale_values = split(',', $scale->scale);
    $num_values = count($scale_values);
    $values = array();
    $i=0;
    while ($i < $num_values) {
        $values[$i+1] = trim($scale_values[$i]);
        $i++;
    }
    $stringwikigrade = wiki_grade_translate($gradeid, $values);
    return $stringwikigrade;
}

function wiki_grade_get_sql_filter($groupmode, $studentmode, $group, $user)
{
    if (($groupmode == 0) && ($studentmode == 0))       // Groupmode=0  Studentmode=0
        $where = 'groupid=0 AND ownerid=0';
    elseif ($groupmode == 0 && 
           ($studentmode == 1 || $studentmode == 2)) {  // Groupmode=0  Studentmode=1,2
        $where = 'groupid=0';
        if ($user != 0) $where .= ' AND ownerid='.$user;
    } elseif ($groupmode == 1 && $studentmode == 0)     // Groupmode=1  Studentmode=0
        $where='groupid='.$group.' AND ownerid=0';
    elseif ($groupmode == 1 && 
           ($studentmode == 1 || $studentmode == 2)) {  // Groupmode=1  Studentmode=1,2
        $where = 'groupid='.$group;
        if ($user != 0) $where.=' AND ownerid=0'; 
    } elseif ($groupmode == 2 && $studentmode == 0)     // Groupmode=1  Studentmode=2
        $where='groupid='.$group.' AND ownerid=0';
    elseif ($groupmode == 2 && 
           ($studentmode == 1 || $studentmode == 2)) {  // Groupmode=1  Studentmode=2
        $where = 'groupid='.$group;
        if ($user != 0) $where.=' AND userid='.$user;
    }

    return $where;
}

function wiki_grade_item_exist($courseid, $itemname, $wikiid)
{
    global $CFG;

    $result = get_record_sql('SELECT *
                              FROM '.$CFG->prefix.'grade_items gi
                              WHERE gi.courseid='.$courseid.' AND gi.itemname="'.$itemname.'" AND gi.itemtype="mod" AND gi.itemmodule="wiki" AND gi.iteminstance='.$wikiid);


    if ($result===false) { return false; }
    else { return true; }
}

?>
