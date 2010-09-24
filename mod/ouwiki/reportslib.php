<?php
/**
 * Show the time lines of the edits on each page as a graph
 *
 * @param unknown_type $subwiki the subwiki
 * @param unknown_type $cm
 * @param unknown_type $viewgroupid the user group to view
 */
function ouwiki_showtimelines($subwiki, $cm, $viewgroupid, $header, $csv) {
    // get the page versions edited in this subwiki by this user group
    $editpagetimes = ouwiki_get_editpagetimes($subwiki->id, $viewgroupid);

    // init arrays and min and max time
    $pagetimeline = array();
    $timeline = array();
    // record last page name
    $pagelast = "-1";
    $mintime = '-1';
    $maxtime = '-1';
    // for each edited page version initialise its array and min/max dates
    foreach ($editpagetimes as $editpagetime) {
        // get the page title
        $page = $editpagetime->pagetitle;
        // get the edit time and make it into a date
        $date = userdate($editpagetime->date, get_string('strftimedate'));
        // if a different page
        if ($page !== $pagelast) {
            // init the timeline info
            $timeline[$page] = array();
            // check for the earliest edit
            $mintime = ($mintime == -1) ? $editpagetime->date : min($mintime, $editpagetime->date);
        }
        // check for the latest edit
        $maxtime = ($maxtime == -1) ? $editpagetime->date : max($maxtime, $editpagetime->date);
        // save the title of the current page
        $pagelast = $page;
    }

    // number of seconds in a day
    $day = 60 * 60 * 24;
    // round the times to the beginning of the day
    $mintime -= $mintime % $day;
    $maxtime -= $maxtime % $day;
    // count the number of days
    $daycount = ceil(($maxtime - $mintime) / $day);

    // get the page titles
    $pages = array_keys($timeline);
    // for each page initialise every date to 0 edits
    foreach ($pages as $page) {
        // go from first edit date by user group on wiki to last edit date, a day at a time
        for ($time = $mintime; $time <= $maxtime; $time += $day) {
            // get the date
            $date = userdate($time, get_string('strftimedate'));
            // inititilise the data point to 0 edits
            $timeline[$page][$date] = 0;
        }
    }

    // init the maximum edits in a single day for scale
    $maxeditcount = 0;
    // for each edited page version count the number of edits for each page for each day
    foreach ($editpagetimes as $editpagetime) {
        // get the page title
        $page = $editpagetime->pagetitle;
        // get the edit date
        $date = userdate($editpagetime->date, get_string('strftimedate'));
        // increment the number of times the page has been edited on this date
        $timeline[$page][$date]++;
        // check for maximum edits in a single day
        $maxeditcount = max($maxeditcount, $timeline[$page][$date]);
    }
    // get min and max dates for edits by user group in wiki
    $mindate = userdate($mintime, get_string('strftimedate'));
    $maxdate = userdate($maxtime, get_string('strftimedate'));

    // Don't show it if there's nothing in it
    if(count($timeline)==0) {
        return;
    }

    // print opening div and table tags as well as headers, the first header is the page name and link to the page
    //    and the second contains the first and last date of editing
    if (!$csv) {
        print <<<EOF
    <div class="ouw_timelines_page">
    <h3>$header->timelinetitle</h3>
    <table>
    <tr>
    <th scope="col">$header->timelinepage<div class='ouw_pagecolumn'></div></th>
    <th class="ouw_firstingroup" scope="col">$mindate</th><th class='ouw_rightcol ouw_lastdate' scope="col">$maxdate</th>
    	    </tr>
EOF;
    } else {
        print $csv->line().$csv->quote($header->timelinetitle).$csv->line().
              $csv->quote($header->timelinepage);
        $data = reset($timeline);
        foreach($data as $date => $datum) {
            print $csv->sep().$csv->quote($date);
        }
    }
    // Draw the graphs
    // for each timeline for each page
    $count=count($timeline);
    foreach ($timeline as $pagetitle => $data) {
        // if no title then must be start page of wiki, create link to this
        if ($pagetitle === '') {
            // get the parameters to view the subwiki pages
            $pageparams = ouwiki_display_wiki_parameters(null, $subwiki, $cm);
            // create a link to the page
            $pagelink = "<a href='view.php?" . $pageparams . "'>" . get_string('startpage', 'ouwiki') . "</a>";
        } else {
            // get the page params to link to the page
            $pageparams = ouwiki_display_wiki_parameters($pagetitle, $subwiki, $cm);
            // create a link to the page
            $pagelink = "<a href='view.php?" . $pageparams . "'>".htmlspecialchars($pagetitle)."</a>";
        }
        // print the title and link to the page
        $count--;
        if (!$csv) {
            print "
        	<tr".($count==0 ? " class='ouw_lastrow'" : "").">
        <td class='ouw_leftcol'>$pagelink</td>
        <td class='ouw_rightcol ouw_firstingroup' colspan='2'>";
            // print the bar chart for the page timeline data, max edit count is for an even scale,
            // the numbers are the size of each graph => width, height
            barchart($data, 800, 20, $maxeditcount);
            // close the tags for the row
            print "
        		<div class='clearer'></div></td>
        	</tr>";
        } else {
            if ($pagetitle === '') {
                $pagetitle = get_string('startpage', 'ouwiki');
            }                    
            print $csv->line().$csv->quote(htmlspecialchars($pagetitle));
            foreach($data as $date => $datum) {
                print $csv->sep().$csv->quote($datum);
            }
        }
    }
    // close the table and div tags for the timeline data
    if (!$csv) {
        print '
    	</table>
    </div>';
    } else {
        print $csv->line();
    }
}


/**
 * Return the metric "collaboration intensity" for the subwiki and user group by user role
 *   This metric is the number of times a different person edits the page from the prevoius version,
 *   (discounting the original version)
 *   divided by the number of different people who have edited the page.
 *
 * @param object $subwikiid    the subwiki
 * @param int $viewgroupid	 the user group
 * @return array Array of pageid=>collaboration intensity
 */
function ouwiki_get_collaborationintensity($contexts, $subwikiid, $viewgroupid) {

    // Get all page versions for this subwiki (regardless of role)
    $versions = ouwiki_get_pageversionsandusers($contexts, $subwikiid, $viewgroupid,0);

    // Array from page ID to info about the page. 
    // Info includes:
    //   ->lastedit (userid of last editor)
    //   ->switches (number of times lastedit has been changed, excluding first)
    //   ->users (array of userid=>true for all users who have touched the page)
    $pageinfo=array();

    foreach($versions as $version) {
        if(!array_key_exists($version->pageid,$pageinfo)) {
            // New page. Record this user. Number of switches is 0.
            $pageinfo[$version->pageid]=new stdClass;
            $pageinfo[$version->pageid]->lastedit=$version->userid;
            $pageinfo[$version->pageid]->switches=0;
            $pageinfo[$version->pageid]->users=array($version->userid=>true);
        } else {
            // Continuing page
            if($pageinfo[$version->pageid]->lastedit!=$version->userid) {
                // Different user to last one
                $pageinfo[$version->pageid]->lastedit=$version->userid;
                $pageinfo[$version->pageid]->switches++;
                // User may have already edited before, but set it anyway
                $pageinfo[$version->pageid]->users[$version->userid]=true;                            
            }
        }
    }

    // OK now we have built up page info, use this to create the array of data
    // values
    $result=array();
    foreach($pageinfo as $pageid=>$info) {
        $result[$pageid]=round($info->switches/count($info->users),1);
    }
    return $result;
}


/**
 * Print a bar chart for a single column of data using coloured divs.
 *
 * @param  $data            the array of data to graph
 * @param  $containerwidth  the width of the graph
 * @param  $containerheight the height of the graph
 * @param  $max             the maximum for the graph for scale
 */
function barchart($data, $containerwidth, $containerheight, $max = -1) {
    // the array of sequential colours for the graph bars
    $colors = array ('#000');///, '#444', '#888');
    // the number of colours
    $colorcount = count($colors);
    // calc width of each bar using chart width and number of bars
    $barwidth = ($containerwidth - 1) / count($data);
    $barwidth--; // Allow for 1 pixel margin
    $barwidth = $barwidth>0 ? $barwidth : 1;
    // if max not valid (+ve) then make it equal the max of the data
    $max = ($max < 0) ? max($data) : $max;
    // get the text for "edits"
    $editstr = get_string('report_edits','ouwiki');
    // print the opening dive for the container with style info
    print "
    <div class='ouw_chartcontainer' style='width:{$containerwidth}px; height:{$containerheight}px'>";
    // a count of the bars used to calc the colour to use
    $count = 0;
    // for each bit of data print the bar
    foreach($data as $date => $datum) {
        // calc the height of the bar
        $height = max(1, $containerheight / $max * $datum);
        // calc the top coordinate of the bar
        $top = $containerheight - $height;
        // get the colour of the bar
        $color = $colors[$count % $colorcount];
        // print the bar with a title giving the data info on mouseover
        $a=new StdClass;
        $a->date=$date;
        $a->edits=$datum;
        print "<div title='".get_string('report_timelinebar','ouwiki',$a)."' class='ouw_bar' style='top:{$top}px; height:{$height}px; width:{$barwidth}px; background-color:$color'></div>";
        // increment the count
        $count++;
    }
    // close the div for the chart
    print '</div>';
}

/**
 * Determines whether a particuar role should be included in reports.
 *
 * @param object $role Moodle role object
 * @return bool True if role is included in reports 
 */
function ouwiki_reports_include_role($role) {
    global $CFG;
    // Check that the config variable is set.
    if(empty($CFG->ouwiki_reportroles)) {
        // Find the student role
        $studentroleid=get_field('role','id','shortname','student');
        set_config('ouwiki_reportroles',$studentroleid);
    }
    // Is the given role ID somewhere in that comma-separated list?
    return strpos(','.$CFG->ouwiki_reportroles.',',','.$role->id.',')!==false;
}

?>