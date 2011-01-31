<?php

/**
* decodes a course structure giving an ordered and
* recursive image of the course.
* The course structure will recognize topic, weekly and flexipage
* course format, keeping an accurate image of the course ordering.
*
* @param int $courseid
* @param reference $itemcount a recursive propagating counter in case of flexipage
* or recursive content.
* @return a complex structure representing the course organisation
*/
function reports_get_course_structure($courseid, &$itemcount){
    global $CFG;
    
    $structure = array();

    if (!$course = get_record('course', 'id', $courseid)){
        print_error("Course structure error : bad id $courseid");
    }
    
    if ($course->format == 'page'){
        include_once $CFG->dirroot.'/course/format/page/lib.php';
        // get first top level page (contains course structure)
        if (!$pages = get_records_select('format_page', " courseid = $course->id AND parent = 0 ", 'sortorder')){
            print_error("Course structure error : failed geting first page");        
        }
        $structure = array();
        foreach($pages as $key => $page){
            if (!($page->display & DISP_PUBLISH)) continue;
            
            $pageelement = new StdClass;
            $pageelement->type = 'page';
            $pageelement->name = $page->nametwo;
            
            $pageelement->subs = page_get_structure_from_page($page, $itemcount);
            $structure[] = $pageelement;
        }
    } else {
        // browse through course_sections and collect course items.
        $structure = array();

        if ($sections = get_records("course_sections", "course", $courseid, "section ASC")) {
            foreach ($sections as $section) {
                $element = new StdClass;
                $element->type = 'section';
                $element->plugintype = 'section';
                $element->instance = $section;
                $element->instance->visible = $section->visible;
                $element->id = $section->id;
                //shall we try to capture any title in there ?
                if (preg_match('/<h[1-7][^>]*?>(.*?)<\\/h[1-7][^>]*?>/i', $section->summary, $matches)){
                    $element->name = $matches[1];
                } else {
                    if ($section->section){
                        $element->name = get_string('section').' '.$section->section ;
                    } else {
                        $element->name = get_string('headsection', 'report_trainingsessions') ;
                    }
                }

                if (!empty($section->sequence)) {
                    $element->subs = array();
                    $sequence = explode(",", $section->sequence);
                    foreach ($sequence as $seq) {
                       $cm = get_record('course_modules', 'id', $seq);
                       $module = get_record('modules', 'id', $cm->module);
                       if (preg_match('/label$/', $module->name)) continue; // discard all labels
                       $moduleinstance = get_record($module->name, 'id', $cm->instance);
                       $sub = new StdClass;
                       $sub->id                 = $cm->id;
                       $sub->plugin             = 'mod';
                       $sub->type               = $module->name;
                       $sub->instance           = $cm;
                       $sub->name               = $moduleinstance->name;
                       $sub->visible            = $cm->visible;
                       $element->subs[] = $sub;
                       $itemcount++;
                    }
                }
                $structure[] = $element;    
            }
        }
    }

    return $structure;
}

/**
* get the complete inner structure for one page of a page menu.
* Recursive function.
*
* @param record $page
* @param reference $itemcount a recursive propagating counter in case of flexipage
* or recursive content.
*/
function page_get_structure_from_page($page, &$itemcount){
    global $VISITED_PAGES;
    
    if (!isset($VISITED_PAGES)) $VISITED_PAGES = array();

    if (in_array($page->id, $VISITED_PAGES)) return;    
    $VISITED_PAGES[] = $page->id;    
    
    $structure = array();
    
    // get page items from first page. They are located in the center column    
    $select = "pageid = {$page->id} AND (position = 'c' OR position = 'r') ";
    $pageitems = get_records_select('format_page_items', $select, 'position, sortorder');
    
    // analyses course content component stack
    foreach($pageitems as $pi){
        if ($pi->blockinstance){
            // is a block
            $b = get_record('block_instance', 'id', $pi->blockinstance);
            $block = get_record('block', 'id', $b->blockid);
            $blockinstance = block_instance($block->name, $b);
            $element = new StdClass;
            $element->type = $block->name;
            $element->plugintype = 'block';
            $element->instance = $b;
            $element->instance->visible = $element->instance->visible * $pi->visible; // a bloc can be hidden by its page_module insertion.
            $element->name = (!empty($blockinstance->config->title)) ? $blockinstance->config->title : '' ;
            $element->id = $b->id;
            // $itemcount++;
                        
            // tries to catch modules, pages or resources in content

            $source = @$blockinstance->config->text;
            // if there is no subcontent, do not consider this bloc in reports.
            if ($element->subs = page_get_structure_in_content($source, $itemcount)){
                $structure[] = $element;
            }            
        } else {
            // is a module
            $cm = get_record('course_modules', 'id', $pi->cmid);
            $module = get_record('modules', 'id', $cm->module);
            
            switch($module->name){
                case 'customlabel':;
                case 'label':{
                }
                break;
                case 'pagemenu':{
                    continue;
                    /**
                    // if a page menu, we have to get substructure
                    $element = new StdClass;
                    $menu = get_record('pagemenu', 'id', $cm->instance);
                    $element->type = 'pagemenu';
                    $element->plugin = 'mod';
                    $element->name = $menu->name;
                    $menulinks = array();
                    if ($next = get_record('pagemenu_links', 'pagemenuid', $menu->id, 'previd', 0)){ // firstone
                        $menulinks[] = $next;
                        while($next = get_record_select('pagemenu_links', "pagemenuid = {$menu->id} AND id = {$next->nextid}")){
                            $menulinks[] = $next;
                            if ($next->nextid == 0) break;
                        }
                    }
                    $element->subs = array();
                    foreach($menulinks as $link){
                        if ($link->type == 'page'){

                            $linkdata = get_record('pagemenu_link_data', 'linkid', $link->id, 'name', 'pageid');
                            $subpage = get_record('format_page', 'id', $linkdata->value);
                            
                            $subelement = new StdClass;
                            $subelement->type = 'page';
                            $subelement->name = $subpage->nametwo;
                            
                            $subelement->subs = page_get_structure_from_page($subpage, $itemcount);
                            $element->subs[] = $subelement;
                        }
                    }
                    $structure[] = $element;
                    */
                }
                break;
                default:{
                    $element = new StdClass;
                    $element->type = $module->name;
                    $element->plugin = 'mod';
                    $moduleinstance = get_record($module->name, 'id', $cm->instance);
                    $element->name = $moduleinstance->name;
                    $element->instance = $cm;
                    $element->instance->visible = $element->instance->visible * $pi->visible; // a bloc can be hidden by its page_module insertion.
                    $element->id = $cm->id;
                    $structure[] = $element;
                    $itemcount++;
                }
            }
        }
    }
    return $structure;
}

/**
* get substructures hidden in content. this applies to content in HTML blocks that
* may be inserted in page based formats. Not applicable to topic and weekly format.
*
* @param string $source the textual source code of the content
* @param reference $itemcount a recursive propagating counter in case of flexipage
* or recursive content.
*/
function page_get_structure_in_content($source, &$itemcount){
    global $VISITED_PAGES;

    $structure = array();

    // get all links
    $pattern = '/href=\\"(.*)\\"/';
    preg_match_all($pattern, $source, $matches);
    if (isset($matches[1])){
        foreach($matches[1] as $href){
            // jump to another page
            if (preg_match('/course\\/view.php\\?id=(\\d+)&page=(\\d+)/', $href, $matches)){
                if (in_array($matches[2], $VISITED_PAGES)) continue;
                $page = get_record('format_page', 'id', $matches[2]);
                $element = new StdClass;
                $element->type = 'pagemenu';
                $element->plugin = 'mod';
                $element->subs = page_get_structure_from_page($page, $itemcount);
                $structure[] = $element;
                $VISITED_PAGES[] = $matches[2];
            }
            // points a module
            if (preg_match('/mod\\/([a-z_]+)\\/.*\\?id=(\\d+)/', $href, $matches)){
                $element = new StdClass;
                $element->type = $matches[1];
                $element->plugin = 'mod';
                $module = get_record('modules', 'name', $element->type);
                $cm = get_record('course_modules', 'id', $matches[2]);
                $moduleinstance = get_record($element->type, 'id', $cm->instance);
                $element->name = $moduleinstance->name;
                $element->instance = &$cm;
                $element->id = $cm->id;
                $structure[] = $element;
                $itemcount++;
            }
        }
    }

    return $structure;
}

/**
* a raster for html printing of a report structure.
*
* @param string ref $str a buffer for accumulating output
* @param object $structure a course structure object.
*/
function training_reports_print_html(&$str, $structure, &$aggregate, &$done, $indent='', $level = 1){

    if (empty($structure)) {
        $str .= get_string('nostructure', 'report_trainingsessions');
        return;
    }

    $indent = str_repeat('&nbsp;&nbsp;', $level);
    $suboutput = '';

    // initiates a blank dataobject
    if (!isset($dataobject)){
        $dataobject->elapsed = 0;
        $dataobject->events = 0;
    }

    if (is_array($structure)){
        // if an array of elements produce sucessively each output and collect aggregates
        foreach($structure as $element){
            if (isset($element->instance) && empty($element->instance->visible)) continue; // non visible items should not be displayed
            $res = training_reports_print_html($str, $element, $aggregate, $done, $indent, $level);
            $dataobject->elapsed += $res->elapsed;
            $dataobject->events += $res->events;
        } 
    } else {
        if (!isset($structure->instance) || !empty($structure->instance->visible)){ // non visible items should not be displayed
            // name is not empty. It is a significant module (non structural)
            if (!empty($structure->name)){
                $str .= "<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" class=\"sessionreport\">";
                $str .= "<tr class=\"sessionlevel{$level}\" valign=\"top\">";
                $str .= "<td width=\"60%\" class=\"sessionitem\">";
                $str .= $indent;
                if (debugging()){
                    $str .= '['.$structure->type.'] ';
                }
                $str .= shorten_text($structure->name, 85);
                $str .= '</td>';
                $str .= "<td width=\"40%\" class=\"reportvalue\" align=\"right\">";
                if (isset($structure->id) && !empty($aggregate[$structure->type][$structure->id])){
                    $done++;
                    $dataobject = $aggregate[$structure->type][$structure->id];
                } 
                if (!empty($structure->subs)) {
                    $res = training_reports_print_html($suboutput, $structure->subs, $aggregate, $done, $indent, $level + 1);
                    $dataobject->elapsed += $res->elapsed;
                    $dataobject->events += $res->events;
                }                
    
                $str .= training_reports_format_time($dataobject->elapsed, 'html');
                $str .= ' ('.$dataobject->events.')';
    
                // plug here specific details
                $str .= '</td>';
                $str .= '</tr>';
                $str .= "</table>\n";
            } else {
                // It is only a structural module that should not impact on level
                if (isset($structure->id) && !empty($aggregate[$structure->type][$structure->id])){
                    $dataobject = $aggregate[$structure->type][$structure->id];
                }
                if (!empty($structure->subs)) {
                    $res = training_reports_print_html($suboutput, $structure->subs, $aggregate, $done, $indent, $level);
                    $dataobject->elapsed += $res->elapsed;
                    $dataobject->events += $res->events;
                }
            }
    
            if (!empty($structure->subs)){
                $str .= "<table width=\"100%\" class=\"trainingreport\">";
                $str .= "<tr valign=\"top\">";
                $str .= "<td colspan=\"2\">";
                $str .= $suboutput;
                $str .= '</td>';
                $str .= '</tr>';
                $str .= "</table>\n";
            }
        }
    }   
    return $dataobject;
}

/**
* a raster for html printing of a report structure header
* with all the relevant data about a user.
*
*/
function training_reports_print_header_html($userid, $courseid, $data, $short = false){
    global $CFG;
    
    $user = get_record('user', 'id', $userid);
    $course = get_record('course', 'id', $courseid);
    
    print_user($user, $course);
    
    $usergroups = groups_get_all_groups($courseid, $userid, 0, 'g.id, g.name');

    echo "<center>";
    echo "<div style=\"width:80%;text-align:left;padding:3px;\" class=\"userinfobox\">";

    // print group status
    if (!empty($usergroups)){
        print_string('groups');
        echo ' : ';
        foreach($usergroups as $group){
            $str = $group->name;        
            if ($group->id == get_current_group($courseid)){
                $str = "<b>$str</b>";
            }
            $groupnames[] = $str;
        }
        echo implode(', ', $groupnames);
                
    }
    
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    echo '<br/>';
    print_string('roles');
    echo ' : ';
    echo get_user_roles_in_context($userid, $context);

    if (!empty($data->linktousersheet)){
        echo "<br/><a href=\"{$CFG->wwwroot}/course/report/trainingsessions/index.php?view=user&amp;id={$courseid}&amp;userid=$userid\">".get_string('seedetails', 'report_trainingsessions').'</a>';
    }

    // print completion bar
    if ($data->items){
        $completed = $data->done / $data->items;
    } else {
        $completed = 0;
    }
    $remaining = 1 - $completed;
    $completedpc = ceil($completed * 100);
    $remainingpc = 100 - $completedpc;
    $completedwidth = floor(500 * $completed);
    $remainingwidth = floor(500 * $remaining);

    echo '<p class="completionbar">';
    print_string('done', 'report_trainingsessions');
    
    echo "<img src=\"{$CFG->wwwroot}/course/report/trainingsessions/pix/green.gif\" style=\"width:{$completedwidth}px\" class=\"donebar\" align=\"top\" title=\"{$completedpc} %\" />";
    echo "<img src=\"{$CFG->wwwroot}/course/report/trainingsessions/pix/blue.gif\" style=\"width:{$remainingwidth}px\" class=\"remainingbar\" align=\"top\"  title=\"{$remainingpc} %\" />";
    
    // Start printing the overall times
    
    if (!$short){

        echo '<br/>';
        echo get_string('equlearningtime', 'report_trainingsessions');
        echo training_reports_format_time(0 + @$data->elapsed, 'html');
        echo ' ('.(0 + @$data->events).')';
    
        // plug here specific details
    }    
    
    echo '</p></div></center>';
}

/**
* special time formating
*
*/
function training_reports_format_time($timevalue, $mode = 'html'){
    if ($timevalue){
        if ($mode == 'html'){
            return format_time($timevalue);
        } else {
            // for excel time format we need have a fractional day value
            return  $timevalue / DAYSECS;
        }
    } else {
        return get_string('visited', 'report_trainingsessions');
    }
}

/**
* a raster for xls printing of a report structure header
* with all the relevant data about a user.
*
*/
function training_reports_print_header_xls(&$worksheet, $userid, $courseid, $data, $xls_formats){
    global $CFG;
    
    $user = get_record('user', 'id', $userid);
    $course = get_record('course', 'id', $courseid);
    
    $row = 0;

    $worksheet->set_row(0, 40, $xls_formats['t']);    
    $worksheet->write_string($row, 0, get_string('sessionreports', 'report_trainingsessions'), $xls_formats['t']);    
    $worksheet->merge_cells($row, 0, 0, 12);    
    $row++;
    $worksheet->write_string($row, 0, get_string('user').' :', $xls_formats['p']);    
    $worksheet->write_string($row, 1, fullname($user));    
    $row++;
    $worksheet->write_string($row, 0, get_string('email').' :', $xls_formats['p']);    
    $worksheet->write_string($row, 1, $user->email);    
    $row++;
    $worksheet->write_string($row, 0, get_string('city').' :', $xls_formats['p']);    
    $worksheet->write_string($row, 1, $user->city);    
    $row++;
    $worksheet->write_string($row, 0, get_string('institution').' :', $xls_formats['p']);    
    $worksheet->write_string($row, 1, $user->institution);    
    $row++;    
    $worksheet->write_string($row, 0, get_string('course', 'report_trainingsessions').' :', $xls_formats['p']);    
    $worksheet->write_string($row, 1, $course->fullname);  
    $row++;    
    $worksheet->write_string($row, 0, get_string('from').' :', $xls_formats['p']);    
    $worksheet->write_string($row, 1, userdate($data->from));  
    $row++;    
    $worksheet->write_string($row, 0, get_string('to').' :', $xls_formats['p']);    
    $worksheet->write_string($row, 1, userdate(time()));  
    $row++;    
    $usergroups = groups_get_all_groups($courseid, $userid, 0, 'g.id, g.name');

    // print group status
    $worksheet->write_string($row, 0, get_string('groups').' :', $xls_formats['p']);    
    $str = '';
    if (!empty($usergroups)){
        foreach($usergroups as $group){
            $str = $group->name;        
            if ($group->id == get_current_group($courseid)){
                $str = "[$str]";
            }
            $groupnames[] = $str;
        }
        $str = implode(', ', $groupnames);
                
    }
    $worksheet->write_string($row, 1, $str);    
    $row++;    
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $worksheet->write_string($row, 0, get_string('roles').' :', $xls_formats['p']);
    $worksheet->write_string($row, 1, strip_tags(get_user_roles_in_context($userid, $context)));
    $row++;
    // print completion bar
    $completed = $data->done / $data->items;
    $remaining = 1 - $completed;
    $completedpc = ceil($completed * 100);
    $remainingpc = 100 - $completedpc;

    $worksheet->write_string($row, 0, get_string('done', 'report_trainingsessions'), $xls_formats['p']);
    $worksheet->write_string($row, 1, $data->done. ' ' . get_string('over', 'report_trainingsessions'). ' '. $data->items. ' ('.$completedpc.' %)');
    $row++;    
    $worksheet->write_string($row, 0, get_string('elapsed', 'report_trainingsessions').' :', $xls_formats['p']);    
    $worksheet->write_number($row, 1, training_reports_format_time($data->elapsed, 'xls'), $xls_formats['zt']);
    $row++;    
    $worksheet->write_string($row, 0, get_string('hits', 'report_trainingsessions').' :', $xls_formats['p']);    
    $worksheet->write_number($row, 1, $data->events);

    return $row;
}

/**
* a raster for xls printing of a report structure.
*
*/
function training_reports_print_xls(&$worksheet, &$structure, &$aggregate, &$done, &$row, &$xls_formats, $level = 1){

    if (empty($structure)) {
        $str = get_string('nostructure', 'report_trainingsessions');
        $worksheet->write_string($row, 1, $str);
        return;
    }

    // makes a blank dataobject.
    if (!isset($dataobject)){
        $dataobject->elapsed = 0;
        $dataobject->events = 0;
        $dataobject->evaluating = 0;
        $dataobject->evaluatingevents = 0;
        $dataobject->preparing = 0;
        $dataobject->preparingevents = 0;
        $dataobject->executing = 0;
        $dataobject->executingevents = 0;
        $dataobject->mentored = 0;
        $dataobject->mentoredevents = 0;
        $dataobject->freerun = 0;
        $dataobject->freerunevents = 0;
    }

    if (is_array($structure)){
        foreach($structure as $element){
            if (isset($element->instance) && empty($element->instance->visible)) continue; // non visible items should not be displayed
            $res = training_reports_print_xls($worksheet, $element, $aggregate, $done, $row, $xls_formats, $level);
            $dataobject->elapsed += $res->elapsed;
            $dataobject->events += $res->events;
            $dataobject->evaluating += $res->evaluating;
            $dataobject->evaluatingevents += $res->evaluatingevents;
            $dataobject->preparing += $res->preparing;
            $dataobject->preparingevents += $res->preparingevents;
            $dataobject->executing += $res->executing;
            $dataobject->executingevents += $res->executingevents;
            $dataobject->mentored += $res->mentored;
            $dataobject->mentoredevents += $res->mentoredevents;
            $dataobject->freerun += $res->freerun;
            $dataobject->freerunevents += $res->freerunevents;
        } 
    } else {
        $format = (isset($xls_formats['a'.$level])) ? $xls_formats['a'.$level] : $xls_formats['z'] ;
        $timeformat = $xls_formats['zt'];
        
        if (!isset($element->instance) || !empty($element->instance->visible)){ // non visible items should not be displayed
            if (!empty($structure->name)){
                // write element title 
                $indent = str_pad('', 3 * $level, ' ');
                $str = $indent.shorten_text($structure->name, 85);
                $worksheet->set_row($row, 18, $format);
                $worksheet->write_string($row, 0, $str, $format);
                $worksheet->write_blank($row, 1, $format);

                if (isset($structure->id) && !empty($aggregate[$structure->type][$structure->id])){
                    $done++;
                    $dataobject = $aggregate[$structure->type][$structure->id];
                } 

                $thisrow = $row; // saves the current row for post writing aggregates
                $row++;
                if (!empty($structure->subs)) {
                    debug_trace("with subs");
                    $res = training_reports_print_xls($worksheet, $structure->subs, $aggregate, $done, $row, $xls_formats, $level + 1);
                    $dataobject->elapsed += $res->elapsed;
                    $dataobject->events += $res->events;
                    $dataobject->preparing += $res->preparing;
                    $dataobject->preparingevents += $res->preparingevents;
                    $dataobject->executing += $res->executing;
                    $dataobject->executingevents += $res->executingevents;
                    $dataobject->evaluating += $res->evaluating;
                    $dataobject->evaluatingevents += $res->evaluatingevents;
                    $dataobject->mentored += $res->mentored;
                    $dataobject->mentoredevents += $res->mentoredevents;
                    $dataobject->freerun += $res->freerun;
                    $dataobject->freerunevents += $res->freerunevents;
                }
                
                $str = training_reports_format_time($dataobject->elapsed, 'xls');
                $worksheet->write_number($thisrow, 2, $str, $timeformat);
                $worksheet->write_number($thisrow, 3, $dataobject->events, $format);
    
                // plug here specific details                
                if (!empty($dataobject->evaluating)){
                    $str = training_reports_format_time($dataobject->evaluating, 'xls');
                    $worksheet->write_number($thisrow, 4, $str, $timeformat);
                    $worksheet->write_number($thisrow, 5, $dataobject->evaluatingevents, $format);
                } else {
                    $dataobject->evaluating = 0;
                    $dataobject->evaluatingevents = 0;
                }
                if (!empty($dataobject->preparing)){
                    $str = training_reports_format_time($dataobject->preparing, 'xls');
                    $worksheet->write_number($thisrow, 6, $str, $timeformat);
                    $worksheet->write_number($thisrow, 7, $dataobject->preparingevents, $format);
                } else {
                    $dataobject->preparing = 0;
                    $dataobject->preparingevents = 0;
                }
                if (!empty($dataobject->executing)){
                    $str = training_reports_format_time($dataobject->executing, 'xls');
                    $worksheet->write_number($thisrow, 8, $str, $timeformat);
                    $worksheet->write_number($thisrow, 9, $dataobject->executingevents, $format);
                } else {
                    $dataobject->executing = 0;
                    $dataobject->executingevents = 0;
                }
                // for trainees
                if (!empty($dataobject->mentored)){
                    $str = training_reports_format_time($dataobject->mentored, 'xls');
                    $worksheet->write_number($thisrow, 10, $str, $timeformat);
                    $worksheet->write_number($thisrow, 11, $dataobject->mentoredevents, $format);
                } else {
                    $dataobject->mentored = 0;
                    $dataobject->mentoredevents = 0;
                }
                if (!empty($dataobject->freerun)){
                    $str = training_reports_format_time($dataobject->freerun, 'xls');
                    $worksheet->write_number($thisrow, 12, $str, $timeformat);
                    $worksheet->write_number($thisrow, 13, $dataobject->freerunevents, $format);
                } else {
                    $dataobject->freerun = 0;
                    $dataobject->freerunevents = 0;
                }
            } else {
                // It is only a structural module that should not impact on level
                if (isset($structure->id) && !empty($aggregate[$structure->type][$structure->id])){
                    $dataobject = $aggregate[$structure->type][$structure->id];
                }
                if (!empty($structure->subs)) {
                    $res = training_reports_print_xls($worksheet, $structure->subs, $aggregate, $done, $row, $xls_formats, $level);
                    $dataobject->elapsed += $res->elapsed;
                    $dataobject->events += $res->events;
                    $dataobject->preparing += $res->preparing;
                    $dataobject->preparingevents += $res->preparingevents;
                    $dataobject->executing += $res->executing;
                    $dataobject->executingevents += $res->executingevents;
                    $dataobject->evaluating += $res->evaluating;
                    $dataobject->evaluatingevents += $res->evaluatingevents;
                    $dataobject->mentored += $res->mentored;
                    $dataobject->mentoredevents += $res->mentoredevents;
                    $dataobject->freerun += $res->freerun;
                    $dataobject->freerunevents += $res->freerunevents;
                }
            }            
        }
    }
    return $dataobject;
}

/**
* sets up a set fo formats
* @param object $workbook
* @return array of usable formats keyed by a label
*/
function training_reports_xls_formats(&$workbook){
    $xls_formats['t'] =& $workbook->add_format();
    $xls_formats['t']->set_size(20);
    $xls_formats['tt'] =& $workbook->add_format();
    $xls_formats['tt']->set_size(10);
    $xls_formats['tt']->set_color(1);
    $xls_formats['tt']->set_fg_color(4);
    $xls_formats['tt']->set_bold(1);
    $xls_formats['p'] =& $workbook->add_format();
    $xls_formats['p']->set_bold(1);
    $xls_formats['a1'] =& $workbook->add_format();
    $xls_formats['a1']->set_size(14);
    $xls_formats['a1']->set_fg_color(31);
    $xls_formats['a2'] =& $workbook->add_format();
    $xls_formats['a2']->set_size(12);
    $xls_formats['a3'] =& $workbook->add_format();
    $xls_formats['a3']->set_size(9);
    $xls_formats['z'] =& $workbook->add_format();
    $xls_formats['z']->set_size(9);
    $xls_formats['zt'] =& $workbook->add_format();
    $xls_formats['zt']->set_size(9);
    $xls_formats['zt']->set_num_format('[h]:mm:ss');
    $xls_formats['zd'] =& $workbook->add_format();
    $xls_formats['zd']->set_size(9);
    $xls_formats['zd']->set_num_format('aaaa/mm/jj hh:mm');
    
    return $xls_formats;
}

/**
* initializes a new worksheet with static formats
* @param int $userid
* @param int $startrow
* @param array $xls_formats
* @param object $workbook
* @return the initialized worksheet.
*/
function training_reports_init_worksheet($userid, $startrow, &$xls_formats, &$workbook){
    $user = get_record('user', 'id', $userid);
    $sheettitle = mb_convert_encoding(fullname($user), 'ISO-8859-1', 'UTF-8');
    $worksheet =& $workbook->add_worksheet($sheettitle);
    $worksheet->set_column(0,0,20);
    $worksheet->set_column(1,1,74);
    $worksheet->set_column(2,2,12);
    $worksheet->set_column(3,3,4);
    $worksheet->set_column(4,4,12);
    $worksheet->set_column(5,5,4);
    $worksheet->set_column(6,6,12);
    $worksheet->set_column(7,7,4);
    $worksheet->set_column(8,8,12);
    $worksheet->set_column(9,9,4);
    $worksheet->set_column(10,10,12);
    $worksheet->set_column(11,11,4);
    $worksheet->set_column(12,12,12);
    $worksheet->set_column(13,13,4);

    $worksheet->set_row($startrow - 1,12,$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,0,get_string('item', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_blank($startrow - 1,1,$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,2,get_string('elapsed', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,3,get_string('hits', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,4,get_string('evaluating', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,5,get_string('hits', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,6,get_string('preparing', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,7,get_string('hits', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,8,get_string('executing', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,9,get_string('hits', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,10,get_string('mentored', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,11,get_string('hits', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,12,get_string('freerun', 'report_trainingsessions'),$xls_formats['tt']);
    $worksheet->write_string($startrow - 1,13,get_string('hits', 'report_trainingsessions'),$xls_formats['tt']);
    
    return $worksheet;
}

?>