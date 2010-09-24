<?php
/**
 * Prints out four tables listing activity on a specified subwiki for a specified user 
 */
require('basicpage.php');
require_once('reportsquerylib.php');
require_once('csv_writer.php');

global $CFG;
global $context;
define('OUWIKI_PAGESIZE',50);

$wikiid = required_param('id',PARAM_INT);
$wikipath = $CFG->wwwroot . "/mod/ouwiki/view.php?id=$wikiid";

if($subwiki->userid) {
    $userid=$subwiki->userid;
} else {
    $userid=required_param('userid',PARAM_INT);
}

require_capability('mod/ouwiki:viewcontributions',$context);

// Check for downloading to .csv file
$title = get_string('report_userreports','ouwiki');
$csv = false;
$format = optional_param('format', false, PARAM_ALPHA);
if ($format == 'csv' || $format == 'excelcsv') {
    $filename = substr($course->shortname.'_'.
                format_string(htmlspecialchars($ouwiki->name)), 0, (31 - strlen($title))).
                '_'.$title;
    $csv = new csv_writer($filename, $format);
} else {
    // Do header
    ouwiki_print_start($ouwiki,$cm,$course,$subwiki,$title,$context,null,false);    
        
    print '<div id="ouwiki_belowtabs_reports">';
}


// get header text for tables from language page
$header->user = get_string('report_user', 'ouwiki');
$header->username = get_string('report_username', 'ouwiki');
$header->startday = get_string('report_startday', 'ouwiki');
$header->lastday = get_string('report_lastday', 'ouwiki');
$header->timeonwiki = get_string('report_timeonwiki', 'ouwiki');
$header->createdpages = get_string('report_createdpages', 'ouwiki');
$header->editedpages = get_string('report_editedpages', 'ouwiki');
$header->edits = get_string('report_edits', 'ouwiki');
$header->comments = get_string('report_comments', 'ouwiki');
$header->additions = get_string('report_additions', 'ouwiki');
$header->deletes = get_string('report_deletes', 'ouwiki');
$header->otheredits = get_string('report_otheredits', 'ouwiki');
$header->contributions = get_string('report_contributions', 'ouwiki');        
$header->userstabletitle = get_string('report_userstabletitle','ouwiki');        
$header->datetime = get_string('report_datetime', 'ouwiki');
$header->pagename = get_string('report_pagename', 'ouwiki');
$header->type = get_string('report_type', 'ouwiki');
$header->date = get_string('report_date', 'ouwiki');
$header->activitybydate = get_string('report_activitybydate', 'ouwiki');
$header->compare = get_string('report_compare', 'ouwiki');
$header->compareversions = get_string('wikirecentchanges','ouwiki');
// get text for some table cells from language page    
$cell->new = get_string('report_new', 'ouwiki');
$cell->existing = get_string('report_existing', 'ouwiki');
$cell->compare = get_string('report_compare', 'ouwiki');

// print summary table detailing counts of user edits and comments 
reportsuser_usersummarytable($subwiki->id, $userid, $header, $csv);
// print table listing edits by user in chronological order with links to the page and changes
reportsuser_usereditstable($subwiki, $cm, $userid, $header, $cell, $csv);
// print table listing comments by user in chronological order with links to the page
reportsuser_usercommentstable($subwiki, $cm, $userid, $header, $csv);
// print table for activity by user in order of date counting edits and comments for each date
reportsuser_useractivitybydatetable($subwiki->id, $userid, $header, $csv);

// That's it for downloading csv file
if ($csv) {
    exit;
}

// Display csv download links
$wikiparams = ouwiki_display_wiki_parameters(null,$subwiki,$cm);
print '<ul class="csv-links"><li><a href="reportsuser.php?id='.$wikiid.
    '&amp;userid='.$userid.'&amp;format=csv">'.get_string('csvdownload','ouwiki').'</a></li>
    <li><a href="reportsuser.php?id='.$wikiid.'&amp;userid='.$userid.'&amp;format=excelcsv">'.
    get_string('excelcsvdownload','ouwiki').'</a></li></ul>';

// Footer
ouwiki_print_footer($course, $cm, $subwiki);


// functions for printing tables:

/**
 * Show the table for the user's edit and comment (etc) summary info
 *
 * @param unknown_type $subwiki  the subwiki info
 * @param unknown_type $userid   the id of the user
 * @param unknown_type $header   the header text for the tables
 */
function reportsuser_usersummarytable($subwikiid, $userid, $header, $csv) {
    // get info from database:
    // get the number of pages created by each user in this subwiki
    $usercreates = ouwiki_get_userspagecreate($subwikiid);
    // get the user name, number of edits, number of pages edited, 
    //    start and finish days for editing by this user, in this subwiki	    
    $useredits = ouwiki_get_useredits($subwikiid, $userid);
    // get all comments by this user on this subwiki as well as
    //  the page title and the time the comment was posted	    
    $usercomments = ouwiki_get_usercomments($subwikiid, $userid);
    
    if(count($useredits)==0) {
        $inactive=get_string('report_user_is_inactive','ouwiki',fullname(get_record('user','id',$userid)));
        if (!$csv) {
            print 
<<<EOF
<div class='ouw_userlist'>
	<h3>$header->user</h3>
	<p>$inactive</p>
EOF;
        } else {
            print $csv->quote($header->user).$csv->line().
                  $csv->quote($inactive).$csv->line();
        }
        return;
    } 

    // get the last element in the array holding the user edits -
    //  there should be only one entry and this is the easy way of getting it
    $userinfo = array_pop($useredits);
	    	    	    	                                      
    // the full name of the user
	  $name = fullname($userinfo);
  	// the number of second in a day to calculate days from epoch time
    $day = 60 * 60 * 24;
    // the time of the first edit by the user
    $mintime = $userinfo->startday;
    // the time of the last edit by the user
    $maxtime = $userinfo->lastday;
    // round to the day
    $mintime -= $mintime % $day;    
    $maxtime -= $maxtime % $day;
    // get the dates from the times            
	$startday = userdate($mintime, get_string('strftimedate'));
    $lastday = userdate($maxtime, get_string('strftimedate'));
    // calculate the number of days between first and last edits
    $daycount = ceil(($maxtime - $mintime) / $day);
    // the number of pages created by the user - if there is no entry for the user, then it must be zero
    $createdpages = array_key_exists($userid, $usercreates) ? $usercreates[$userid]->createdcount : 0;  
    // get the number of edited pages  
	$editedpages = $userinfo->editedpagecount;
	// get the number of edits
    $edits = $userinfo->editcount;           
    // count the number of comments                
    $comments = count($usercomments);
    // calculate the number of contributions
    $contributions = $edits + $comments;    		    

    // print out the html for the table - one header row and one data row
    if (!$csv) { 
        print 
<<<EOF
<div class='ouw_userlist'>
	<h3>$header->user</h3>
	<table>
		<tr>			
			<th class="ouw_leftcol" scope="col">$header->username<div class='ouw_namecolumn'></div></th>
			<th scope="col" class='ouw_firstingroup'>$header->startday<div class='ouw_datecolumn'></div></th>	
			<th scope="col">$header->lastday<div class='ouw_datecolumn'></div></th>
			<th scope="col">$header->timeonwiki</th>
			<th scope="col" class='ouw_firstingroup'>$header->createdpages</th>
			<th scope="col">$header->editedpages</th>
		    <th scope="col">$header->edits</th>
		    <th scope="col">$header->comments</th>
			<!--th scope="col" class='ouw_firstingroup'>$header->additions</th>
			<th scope="col">$header->deletes</th>
			<th scope="col">$header->otheredits</th-->
		    <th scope="col" class='ouw_rightcol'>$header->contributions</th>
		 </tr>		 
		<tr>
			<td class='ouw_leftcol'>$name</td>
			<td class='ouw_firstingroup'>$startday</td>
			<td>$lastday</td>
			<td>$daycount</td>
			<td class='ouw_firstingroup'>$createdpages</td>			        	
        	<td>$editedpages</td>
        	<td>$edits</td>
        	<td>$comments</td>
			<!--td class='ouw_firstingroup'></td>        	
        	<td></td>
        	<td></td-->
        	<td class='ouw_rightcol'>$contributions</td>
        </tr>
   	</table>
</div>	
EOF;
    } else {
        print $csv->quote($header->user).$csv->line().

              $csv->quote($header->username).$csv->sep().
              $csv->quote($header->startday).$csv->sep().   
              $csv->quote($header->lastday).$csv->sep().
              $csv->quote($header->timeonwiki).$csv->sep().
              $csv->quote($header->createdpages).$csv->sep().
              $csv->quote($header->editedpages).$csv->sep().
              $csv->quote($header->edits).$csv->sep().
              $csv->quote($header->comments).$csv->sep().
              $csv->quote($header->contributions).$csv->line().

              $csv->quote($name).$csv->sep().
              $csv->quote($startday).$csv->sep().
              $csv->quote($lastday).$csv->sep().
              $csv->quote($daycount).$csv->sep().
              $csv->quote($createdpages).$csv->sep().
              $csv->quote($editedpages).$csv->sep().
              $csv->quote($edits).$csv->sep().
              $csv->quote($comments).$csv->sep().
              $csv->quote($contributions).$csv->line();
    }
}


/**
 * Print out the html for a table listing all the edited pages by the user in this subwiki.
 *
 * @param unknown_type $subwiki  the subwiki in question
 * @param unknown_type $cm       
 * @param unknown_type $userid   the id of the user
 * @param unknown_type $header   the header text for the table
 * @param unknown_type $cell	 text for some of the cells of the table
 */
function reportsuser_usereditstable($subwiki, $cm, $userid, $header, $cell, $csv) {    
    // Get info from database:
    // Get all edited pages by this user in this subwiki as well as the time the 
    //  page versions were created, the page id and title, the current version id and 
    //  the previous version id.
    $usereditpages = ouwiki_get_usereditpages($subwiki->id, $userid);
    if(count($usereditpages)==0) {
        return;
    }
    
    // Get the earliest page version of each page in the subwiki to check whether a 
    //  version is new page or an existing page which is edited    
    $firstversions = ouwiki_get_earliestpageversions($subwiki->id);
    
    // print the div and table tags with the title of the table and also the header row
    if (!$csv) { 
        print <<<EOF
<div class="ouw_usereditslist">
 	<h3>$header->edits</h3>
	<table>
		<tr>
			<th class='ouw_leftcol' scope="col">$header->datetime</th>
			<th scope="col">$header->pagename</th>
			<th scope="col"><span class="accesshide">$header->compareversions</span></th>
			<th class='ouw_rightcol' scope="col">$header->type</th>			
		</tr>
EOF;
    } else {
        print $csv->line().$csv->quote($header->edits).$csv->line().

              $csv->quote($header->datetime).$csv->sep().
              $csv->quote($header->pagename).$csv->sep().
/* Do not display version changes link for csv
              $csv->quote($header->compareversions).$csv->sep().
*/
              $csv->quote($header->type).$csv->line();
    }
    // for each page edit by the user in this subwiki
    foreach ($usereditpages as $usereditpage) {        
        // get the time the edit version was created
        $datetime = ouwiki_nice_date($usereditpage->timecreated);
        // get the page title
        $title = $usereditpage->pagetitle;
        // get the page params to link to the page
        $pageparams = ouwiki_display_wiki_parameters($title, $subwiki, $cm);
        // create html to link to the difference between this version and the previous one - ie the changes for this version, or blank if no previous version           
        $versionlink = ($usereditpage->previousversionid == '') ? '' : 
        		"<a href='diff.php?$pageparams&amp;v2={$usereditpage->versionid}&amp;v1={$usereditpage->previousversionid}'>$cell->compare</a>";
        // create html to link to the latest version of the page
        $pagelink = "<a href='view.php?" . $pageparams . "'>".htmlspecialchars($title)."</a>";
        // the type of the page version ie whether it is the first created version or an edit version of an editing page
        $type = ($usereditpage->versionid == $firstversions[$usereditpage->pagetitle]->first) ? $cell->new : $cell->existing;
        // if the title is empty then it is the start page of the wiki      
        if ($title == '') {                      
            $pagelink = "<a href='view.php?" . ouwiki_display_wiki_parameters(null, $subwiki, $cm). "'>" . get_string('startpage', 'ouwiki') . "</a>";
        }
        
        // print the row of the table containing the data and time of the edited page, the link to the page, the changes, and the type of page
        if (!$csv) {
            print <<<EOF
		<tr>
			<td class='ouw_leftcol'>$datetime</td>
			<td>$pagelink</td>
			<td>$versionlink</td>
			<td class='ouw_rightcol'>$type</td>	
		</tr>		
EOF;
        } else {
            if ($title == '') {                      
                $title = get_string('startpage', 'ouwiki');
            }
            print $csv->quote($datetime).$csv->sep().
                  $csv->quote(htmlspecialchars($title)).$csv->sep().
/* Do not display version changes link for csv
                  $csv->quote($versionlink).$csv->sep().
*/
                  $csv->quote($type).$csv->line();
        }
    }		
    // close the table and div tags
    if (!$csv) {
    	print '
	</table>
</div>';	
    }	
}


/**
 * Print the table which list the comments by the user in this subwiki,
 *  with the date of the comment and a link to the page
 *
 * @param unknown_type $subwiki   the subwiki in question
 * @param unknown_type $cm
 * @param unknown_type $userid    the id of the user
 * @param unknown_type $header    the header text for the table
 */
function reportsuser_usercommentstable($subwiki, $cm, $userid, $header, $csv) {
    // get the user comments info
    $usercomments = ouwiki_get_usercomments($subwiki->id, $userid);
    if(count($usercomments)==0) {
        return;
    }
    // Print the opening div and table tags as well as the header row
    if (!$csv) { 
        print <<<EOF
<div class="ouw_usercommentslist">
 	<h3>$header->comments</h3>
	<table>
		<tr>
			<th class='ouw_leftcol' scope="col">$header->datetime</th>
			<th class='ouw_rightcol' scope="col">$header->pagename</th>			
		</tr>		
EOF;
    } else {
        print $csv->line().$csv->quote($header->comments).$csv->line().

              $csv->quote($header->datetime).$csv->sep().
              $csv->quote($header->pagename).$csv->line();
    }
		
    // for each comment in this subwiki by the user
    foreach ($usercomments as $usercomment) {
        // get the date and time of the comment
        $datetime = ouwiki_nice_date($usercomment->timeposted);
        // get the page title of the page with the comment
        $title = $usercomment->title;
        // make it html safe
        $pagetitle = htmlspecialchars($title);
        // create html link to the page
        $pagelink = "<a href='view.php?" . ouwiki_display_wiki_parameters($title,$subwiki,$cm). "'>$pagetitle</a>";
        // if title is blank then must be subwiki start page - create link to there        
        if ($title == '') {            
            $pagelink = "<a href='view.php?" . ouwiki_display_wiki_parameters(null, $subwiki, $cm). "'>" . get_string('startpage', 'ouwiki') . "</a>";
        }
        // print the table row for date and time and page link for comments by user
        if (!$csv) {
            print <<<EOF
		<tr>
			<td class='ouw_leftcol'>$datetime</td>
			<td class='ouw_rightcol'>$pagelink</td>	
		</tr>		
EOF;
        } else {
            if ($title == '') {
                $title = get_string('startpage', 'ouwiki');
            }            
            print $csv->quote($datetime).$csv->sep().
                  $csv->quote($title).$csv->line();
        }
    }
    // close table and div tags		
    if (!$csv) {
    	print '
	</table>
</div>';	
    }
}


/**
 * Enter description here...
 *
 * @param unknown_type $subwikiid
 * @param unknown_type $userid
 * @param unknown_type $header
 */
function reportsuser_useractivitybydatetable($subwikiid, $userid, $header, $csv) {
	// Get info from the database:
	// get all coomments in subwiki by user in order of time posted
    $usercommentsbydate = ouwiki_get_usercommentsbydate($subwikiid, $userid);
    // get all edits in subwiki by user in order of time posted
    $usereditsbydate = ouwiki_get_usereditsbydate($subwikiid, $userid);
    // number of seconds in a day to calculate days from epoch time which is in seconds
    $day = 60 * 60 * 24;
    // the array for the comments and edits info by date
    $datesinfo = array();
    // build the edits part of the array by date
    foreach ($usereditsbydate as $edit) {
        // round the date to the start of the day         
        $date = $edit->date - $edit->date % $day;
        // if there is no entry already for the date then initialise the array element
        if (!array_key_exists($date, $datesinfo)) {
             $datesinfo[$date]->edits = 0;
             $datesinfo[$date]->comments = 0;
        }
        // add the edit into the array
        $datesinfo[$date]->edits += $edit->editcount;          
    }
    // build the comments part of the array by date 
    foreach ($usercommentsbydate as $comment) {  
        // round the date to the start of the day       
        $date = $comment->date - $comment->date % $day;
        // if there is no entry already for the date then initialise the array element
        if (!array_key_exists($date, $datesinfo)) {
             $datesinfo[$date]->edits = 0;
             $datesinfo[$date]->comments = 0;
        }
        // add the comment into the array
        $datesinfo[$date]->comments += $comment->commentcount;          
    }
    if(count($datesinfo)==0) {
        return;
    }
    // sort the keys of the array into chronilogical order
    ksort($datesinfo);
    
    // print the open div tag, the table header and the header row of the table
    if (!$csv) {
        print <<<EOF
<div class="ouw_useractivitybydatelist">
 	<h3>$header->activitybydate</h3>
	<table>
		<tr>
			<th class='ouw_leftcol' scope="col">$header->date</th>
			<th scope="col">$header->edits</th>
			<th scope="col">$header->comments</th>
			<th class='ouw_rightcol' scope="col">$header->contributions</th>			
		</tr>
EOF;
    } else {
        print $csv->line().$csv->quote($header->activitybydate).$csv->line().

              $csv->quote($header->date).$csv->sep().
              $csv->quote($header->edits).$csv->sep().
              $csv->quote($header->comments).$csv->sep().
              $csv->quote($header->contributions).$csv->line();
    }
    // for each date of activity            
    foreach ($datesinfo as $date => $info) {
        // get the date of the activity
        $date = userdate($date, get_string('strftimedate'));
        // get the number of edits
        $edits = $info->edits;
        // get the number of comments
        $comments = $info->comments;
        // calc the number of contribtions
        $contributions = $edits + $comments;
        // print the row of data - the date, the number of edits, the comments and the contributions  for that date
        if (!$csv) {
            print <<<EOF
		<tr>
			<td class='ouw_leftcol'>$date</td>
			<td>$edits</td>
			<td>$comments</td>
			<td class='ouw_rightcol'>$contributions</td>	
		</tr>		
EOF;
        } else {
            print $csv->quote($date).$csv->sep().
                  $csv->quote($edits).$csv->sep().
                  $csv->quote($comments).$csv->sep().
                  $csv->quote($contributions).$csv->line();
        }
    }        
    // close the table and div tags
    if (!$csv) {
	   print '
	</table>
</div>';	
    }
}
?>