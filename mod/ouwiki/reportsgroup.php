<?php
require('basicpage.php');
require_once('reportsquerylib.php');
require_once('reportslib.php');
require_once('csv_writer.php');
global $CFG;
global $context;
define('OUWIKI_PAGESIZE',50);
$wikipath = $CFG->wwwroot . '/mod/ouwiki/';

$wikiid = required_param('id',PARAM_INT);
$wikipath = $CFG->wwwroot . '/mod/ouwiki/';

$viewgroupid=optional_param('viewgroup',-1,PARAM_INT);

require_capability('mod/ouwiki:viewcontributions',$context);

// Individual wikis are handled differently
if($subwiki->userid) {
    redirect('reportsuser.php?'.ouwiki_display_wiki_parameters(null,$subwiki,$cm,OUWIKI_PARAMS_URL));
        exit;
}

// Check for downloading to .csv file
$title = get_string('report_groupreports','ouwiki');
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

// header text for tables from language file
// headers for the group table
$header->grouptabletitle = get_string('report_grouptabletitle','ouwiki');    
$header->total = get_string('report_total', 'ouwiki');
$header->active = get_string('report_active', 'ouwiki');
$header->inactive = get_string('report_inactive', 'ouwiki');
$header->pages = get_string('report_pages', 'ouwiki');
$header->percentageparticipation = get_string('report_percentageparticipation', 'ouwiki');
$header->totalpages = get_string('report_totalpages', 'ouwiki');
$header->editedpages = get_string('report_editedpages', 'ouwiki');
$header->uneditedpages = get_string('report_uneditedpages', 'ouwiki');
$header->edits = get_string('report_edits', 'ouwiki');
$header->comments = get_string('report_comments', 'ouwiki');
    
// Header text for columns in edited pages table
$header->pagetabletitle = get_string('report_pagetabletitle','ouwiki');
$header->pagename = get_string('report_pagename','ouwiki');
$header->contributorcount = get_string('report_contributorcount','ouwiki');
$header->intensity = get_string('report_intensity','ouwiki');    
$header->startday = get_string('report_startday','ouwiki');
$header->lastday = get_string('report_lastday','ouwiki');    
$header->wordcount = get_string('report_wordcount','ouwiki');
$header->linkcount = get_string('report_linkcount','ouwiki');
    
// headers for edit-comments graph and edited pages graph
// Require bar graphs key for edits and comments graph
$a = new StdClass;
$a->ouw_bargraph1key = '<span class="ouw_bargraph1key">&nbsp;</span>';
$a->ouw_bargraph2key = '<span class="ouw_bargraph2key">&nbsp;</span>';
$header->editscommentsgraph = get_string('report_editscommentsgraphtitle','ouwiki', $a);
$header->editedpagesgraph = get_string('report_editedpagesgraphtitle','ouwiki', $a);

// headers for time line graphs
$header->timelinetitle = get_string('report_timelinetitle','ouwiki');
$header->timelinepage = get_string('report_timelinepage','ouwiki');

// headers for user list table    
$header->userstabletitle = get_string('report_userstabletitle','ouwiki');
$header->username = get_string('report_username', 'ouwiki');
$header->startday = get_string('report_startday', 'ouwiki');
$header->lastday = get_string('report_lastday', 'ouwiki');
$header->timeonwiki = get_string('report_timeonwiki', 'ouwiki');
$header->createdpages = get_string('report_createdpages', 'ouwiki');
$header->editedpages = get_string('report_editedpages', 'ouwiki');
$header->additions = get_string('report_additions', 'ouwiki');
$header->deletes = get_string('report_deletes', 'ouwiki');
$header->otheredits = get_string('report_otheredits', 'ouwiki');
$header->contributions = get_string('report_contributions', 'ouwiki');

// For a single subwiki, offer a group selector
if($ouwiki->subwikis==OUWIKI_SUBWIKIS_SINGLE) {
    $viewgroupid=optional_param('viewgroup',-1,PARAM_INT);
    $groups = groups_get_activity_allowed_groups($cm);
    if(!$groups) {
        $groups=array();
    }
    uasort($groups,create_function('$a,$b','return strcasecmp($a->name,$b->name);'));

    $allgroups=has_capability('moodle/site:accessallgroups',$context);

    // If they didn't select a group, pick the first one or     
    if($viewgroupid==-1) {
        if(count($groups)==0) {
            if(!$allgroups) {
                error('You do not have access to any groups to view user data for');
            } else {
                $viewgroupid=0;
            }
        } else {
            $viewgroupid=reset($groups)->id;
        }        
    } else if($viewgroupid==0) {
        if(!$allgroups) {
            error('You do not have access to view data for all users.');
        }
    } else {
        if(!array_key_exists($viewgroupid,$groups)) {
            error('You do not have access to view data for this group.');
        }
    }
    
    $choices=array();
    if($allgroups) {
        $choices[0]=get_string('all');
    }
    foreach($groups as $group) {
        $choices[$group->id]=$group->name;
    }
    if (!$csv) {
        print '<form method="get" action="reportsgroup.php" class="ouw_reportsgroups"><div>'.
            ouwiki_display_wiki_parameters(null,$subwiki,$cm,OUWIKI_PARAMS_FORM).
            '<label for="ouw_viewgroup">'.get_string('report_grouplabel','ouwiki').
            '</label> <select name="viewgroup" id="ouw_viewgroup">';

        foreach($choices as $id=>$value) {
            $selected = $id==$viewgroupid ? ' selected="selected"' : '';
            print '<option value="'.$id.'"'.$selected.'>'.htmlspecialchars($value).'</option>';
        }
        print '</select><input type="submit" value="'.get_string('changebutton','ouwiki').'" /></div></form>'.
          '<div class="clearer"></div>';
    } else {
        print $csv->quote(htmlspecialchars($choices[$viewgroupid])).$csv->line();
    }
} 

    
    
// get data from database
    
$pagesinfo = ouwiki_get_pages($subwiki->id);        
$coursecontext = get_context_instance(CONTEXT_COURSE,$course->id);
$contexts = get_related_contexts_string($context);
$allroles = get_roles_used_in_context($context,true);
        
// get all the role ids and names for this group as well as the number of users in each role 
$rolenames = array();
$usercounts = array();
foreach ($allroles as $role) {
    // TODO Roles to provide reports for will be selectable
    // For now restrict ('hardcode') reports to student and tutor/teacher roles only
    if(ouwiki_reports_include_role($role)) {
        $roleid = $role->id;
        $rolename = role_get_name($role,$coursecontext);
        $usercount = count(ouwiki_get_users($contexts, $viewgroupid, $roleid));
        if ($usercount > 0) {
            $rolenames[$roleid] = $rolename;
    	      $usercounts[$roleid] = $usercount;
        }
    }
}    
    
// table for group report
$allsubwikipages = ouwiki_get_subwiki_index($subwiki->id);
$pagecount = count($allsubwikipages);
    
// initialise arrays to be used in tables later 
$pageeditcount = array();
$pagecommentcount = array();
$pagecontributorcount = array();
$commentsbyroles = array();
$editsbyroles = array();
    
$activeusercount = array();
$editedpagecount = array();
$editcount = array();
$commentcount = array();

// init some arrays to 0's
foreach ($pagesinfo as $pageinfo) {
    $pagecommentcount[$pageinfo->title] = 0;
    $pageeditcount[$pageinfo->title] = 0;
    $pagecontributorcount[$pageinfo->title] = 0;
}
    
// params
$param->roles = 'roles=';
$param->edits = 'edits=';
$param->comments = 'comments=';    
$param->edited = 'edited=';
$param->pages = 'pages=';
    
    
// collate info for tables
// the total number of users on the wiki from all the roles
$totalusers = 0;
    
// for all the role names
foreach ($rolenames as $roleid => $rolename) {       
     // if there are users for this role
    if ($usercounts[$roleid] > 0) {
        // get the edits for by page by this role
        $pageedits = ouwiki_get_editedpages($contexts, $subwiki->id, $viewgroupid, $roleid);
        // get the active user info for this role	        
        $activeusers = ouwiki_get_activeusers($contexts, $subwiki->id, $viewgroupid, $roleid);
        // get the comment counts for this role
        $pagecomments = ouwiki_get_pagecomments($contexts, $subwiki->id, $viewgroupid, $roleid);
            
        // add the number of users in this role to the total
        $totalusers += $usercounts[$roleid];
        // build the rolenames array for later use
        $rolenames[$roleid] = $rolename;
        // put the active user info into the array
        $activeusercount[$roleid] = count($activeusers);
        // put the number of pages edited by this role info into the array
	    $editedpagecount[$roleid] = count($pageedits);                           
	    // put the comments info into the array    
	    $commentsbyroles[$roleid] = $pagecomments;
	    // init the comment count for this role	        
	    $commentcount[$roleid] = 0;        
	    // for all the comments
	    foreach ($pagecomments as $pagecomment) {
	        // add up the comments for this role
	        $commentcount[$roleid] += $pagecomment->commentcount;
	        // add up the comments for the page            
	        $pagecommentcount[$pagecomment->pagetitle] += $pagecomment->commentcount;
	    }	        
	    // all the edits by role then by page
	    $editsbyroles[$roleid] = $pageedits;	        
	    // the sum of total edits by each role     
	    $editcount[$roleid] = 0;	  
	    // for all the pages with edits by the current role  
	    foreach ($pageedits as $pageedit) {
	        // add up the total edits by each role	        
	        $editcount[$roleid] += $pageedit->editcount;
	        // add up the edits for each page        
	        $pageeditcount[$pageedit->pagetitle] += $pageedit->editcount;
	        // add up the number of contributors for each page    
  	        $pagecontributorcount[$pageedit->pagetitle] += $pageedit->contributorcount;
	    }	        	                   
	    // build the params for the graphs - a comma separated values list    	        
        $param->roles .= $rolename . ',';
	    $param->edits .= $editcount[$roleid] . ',';
	    $param->comments .= $commentcount[$roleid] . ',';
	    $param->edited .= $editedpagecount[$roleid] . ',';
	    $param->pages .= $pagecount . ',';
    }
}
// remove the last comma from the comma seperated values params
$param->roles = substr($param->roles, 0, -1); 
$param->edits = substr($param->edits, 0, -1);
$param->comments = substr($param->comments, 0, -1);
$param->edited = substr($param->edited, 0, -1);
$param->pages = substr($param->pages, 0, -1);

if($pagecount==0) {
    if (!$csv) {
        print '<p>'.get_string('report_emptywiki','ouwiki').'</p>';
    } else {
        print $csv->quote(get_string('report_emptywiki','ouwiki')).$csv->line();
        exit;
    }
} else {
    // print the group report table
    reportssummary_grouptable($header, $rolenames, $pagecount, $usercounts, 
                        $activeusercount, $editedpagecount, $editcount, $commentcount, $csv);
        
    // print edit and comments graphs
    // Graphs do not work so we have removed this feature for now
    if (!$csv) {
        reportssummary_printgraphs($header, $wikipath, $param);
    }
        
    reportssummary_editedpagetable($contexts, $subwiki, $cm, $viewgroupid, $header, $pagesinfo, $rolenames, $pageeditcount, $editsbyroles,  
                                                    $pagecontributorcount, $totalusers, $pagecommentcount, $commentsbyroles, $csv);    
        
    // display graphs of the time lines of edits in all the pages in the subwiki                                            
    ouwiki_showtimelines($subwiki, $cm, $viewgroupid, $header, $csv);
            
    // print the table showing user activity            
    reportssummary_usertable($cm,$wikiid, $contexts, $subwiki, $viewgroupid, $header, $rolenames, $csv);

    // That's it for downloading csv file
    if ($csv) {
        exit;
    }

    // Display csv download links
    $wikiparams = ouwiki_display_wiki_parameters(null,$subwiki,$cm);
    print '<ul class="csv-links"><li><a href="reportsgroup.php?id='.$wikiid.
        '&amp;viewgroup='.$viewgroupid.'&amp;format=csv">'.get_string('csvdownload','ouwiki').'</a></li>
        <li><a href="reportsgroup.php?id='.$wikiid.'&amp;viewgroup='.$viewgroupid.'&amp;format=excelcsv">'.
        get_string('excelcsvdownload','ouwiki').'</a></li></ul>';
}   

// Footer
ouwiki_print_footer($course, $cm, $subwiki);



/// Functions to display parts of the page

/**
 * Print out the graphs
 *
 * @param $header the header text
 * @param $wikipath the base url for the wiki
 * @param $param the parameter text which contains comma separated values for the graphs
 */
function reportssummary_printgraphs($header, $wikipath, $param) {

    // define graph constants
    define('Y_POINTS', 5);
    define('Y_TITLE_WIDTH', 40);
    define('Y_TITLE_HEIGHT', 20);
    define('X_TITLE_HEIGHT', 40);
    define('X_SCALE_WIDTH', 120);
    define('Y_SCALE_HEIGHT', 40);
    define('MAX_PAGES_WIDTH', 40);
    define('PADDING', 4);
    define('BORDER', 1);

    // open the div tags, print the header and then include the edits/comments graph
    print "<div class='ouw_graphs'>";
    if ($param->roles != 'roles') {
        print "<table><tr>"; // fix table layout for rtl mode (nadavkav patch)
        print "<td>";
        print "<h3>$header->editscommentsgraph</h3>";
        include(dirname(__FILE__).'/'.'reportseditscommentscssgraph.php');
        print "</td>";

        print "<td>";
        // Print the header and then include the pages edited graph
        print "<h3>$header->editedpagesgraph</h3>";
        include(dirname(__FILE__).'/'.'reportspageseditedcssgraph.php');
        print "</td>";
        print "</tr></table>";
    }

    print "</div><br/>";

    print "</div>";
}


/**
 * Print out the group table
 *
 * @param $header           the header text for the table
 * @param $rolenames        the available roles for users on this wiki
 * @param $pagecount        the total page count of the wiki
 * @param $usercounts       the number of users from each role
 * @param $activeusercount  the number of active users from each role
 * @param $editedpagecount  the number of pages edited by each role
 * @param $editcount        the number of total edits by each role
 * @param $commentcount     the number of comments by each role
 */
function reportssummary_grouptable($header, $rolenames, $pagecount, $usercounts, 
                        $activeusercount, $editedpagecount, $editcount, $commentcount, $csv) {
    // get the last role id from the roles names to know when to print the bottom border of the table                        
    end($rolenames);
    $lastrole = each($rolenames);
    $lastroleid = $lastrole['key'];

    // open the div and table tags and prin the table header and total page count row
    if (!$csv) {
        print     
<<<EOF
<div class="ouw_groupreport">
	<table>
		<tr>
			<th scope="col" colspan="2" class="ouw_rightcol ouw_leftcol">$header->grouptabletitle</th>
		</tr>
        <tr class="ouw_lastingroup">
        	<td class="ouw_leftcol">$header->totalpages</td><td class="ouw_rightcol">$pagecount</td>
        </tr>            
EOF;
    } else {
        print $csv->quote($header->grouptabletitle).$csv->line().
              $csv->quote($header->totalpages).$csv->sep().$csv->quote($pagecount);
    }
    // for each role name
    foreach ($rolenames as $roleid => $rolename) {
        // if there are any users of this role        
        if ($usercounts[$roleid] > 0) {
            // calc inactive user count from all users - active users, for this role
	        $inactiveusercount = $usercounts[$roleid] - $activeusercount[$roleid];
	        // calc participation as a percentage of active users
	        $participation = round($activeusercount[$roleid] / $usercounts[$roleid] * 100,1);
	        // calc percentage of total pages edited by role
	        $editedpageratio = round($editedpagecount[$roleid] / $pagecount * 100, 1);
            // calc number of pages unedited by role
	        $uneditedpagecount = $pagecount - $editedpagecount[$roleid];
	        // calc percentage of total pages unedited by role
	        $uneditedpageratio = 100 - $editedpageratio;
            // class for last row of info for role - puts a bottom border on the table             
            $lastclass = ($roleid == $lastroleid) ? '' : 'class="ouw_lastingroup"';
            // print the rows of info for this role            
            if (!$csv) {
    	        print 
<<<EOF
		<tr>
			<td class="ouw_leftcol"><h4>$rolename</h4></td><td class="ouw_rightcol">&nbsp;</td>
		</tr>        
        <tr>
        	<td class="ouw_leftcol">$header->total</td>
        	<td class="ouw_rightcol">$usercounts[$roleid]</td>        	
        </tr>            
		<tr>
        	<td class="ouw_leftcol">$header->active</td><td  class="ouw_rightcol"><a href="#">$activeusercount[$roleid]</a></td>
        </tr>            
		<tr>
        	<td class="ouw_leftcol">$header->inactive</td><td class="ouw_rightcol"><a href="#">$inactiveusercount</a></td>
        </tr>            
        <tr class="ouw_lastingroup">
        	<td class="ouw_leftcol">$header->percentageparticipation</td><td class="ouw_rightcol">$participation%</td>
        </tr>            
        <tr>
        	<td class="ouw_leftcol">$header->editedpages</td><td class="ouw_rightcol">$editedpagecount[$roleid] ($editedpageratio%)</td>
        </tr>            
        <tr>
        	<td class="ouw_leftcol">$header->uneditedpages</td><td class="ouw_rightcol">$uneditedpagecount ($uneditedpageratio%)</td>
        </tr>            
        <tr>
        	<td class="ouw_leftcol">$header->edits</td><td class="ouw_rightcol">$editcount[$roleid]</td>
        </tr>            
        <tr $lastclass>
        	<td class="ouw_leftcol">$header->comments</td><td class="ouw_rightcol">$commentcount[$roleid]</td>
        </tr>            
EOF;
            } else {
                print $csv->line().
                      $csv->quote($rolename).$csv->sep().$csv->line().
                      $csv->quote($header->total).$csv->sep().$csv->quote($usercounts[$roleid]).$csv->line().
                      $csv->quote($header->active).$csv->sep().$csv->quote($activeusercount[$roleid]).$csv->line().
                      $csv->quote($header->inactive).$csv->sep().$csv->quote($inactiveusercount).$csv->line().
                      $csv->quote($header->percentageparticipation).$csv->sep().$csv->quote($participation.'%').$csv->line().
                      $csv->quote($header->editedpages).$csv->sep().$csv->quote($editedpagecount[$roleid].' ('.$editedpageratio.'%)').$csv->line().
                      $csv->quote($header->uneditedpages).$csv->sep().$csv->quote($uneditedpagecount.' ('.$uneditedpageratio.'%)').$csv->line().
                      $csv->quote($header->edits).$csv->sep().$csv->quote($editcount[$roleid]).$csv->line().
                      $csv->quote($header->comments).$csv->sep().$csv->quote($commentcount[$roleid]);
            }
        }
    }
    // close the table and div tags
    if (!$csv) {
        print '
    </table>
</div>
';    
    } else {
        print $csv->line();
    }
}


/**
 * This prints out the first two header rows of the table showing edited page info
 *
 * @param unknown_type $header     the object containing the header text
 * @param unknown_type $rolenames  the array containing the role names
 */    
function reportssummary_editedpagetableheaders($header, $rolenames, $csv) {
    // The colspan for the title of the table which depends on how many roles represented by members of the group 
    $titlecolspan = 8 + count($rolenames) * 3;    
    // print the div, table header and table tags    
    if (!$csv) {
        print 
<<<EOF
<div class="ouw_pagelist">
    <h3>$header->pagetabletitle</h3>    
	<table>
EOF;
    } else {
        print $csv->line().$csv->quote($header->pagetabletitle).$csv->line();
    }
    // print the row tag and some header space for summary columns in the row which displays the roles
    // the colspan is 4 for the headers: Page, Contributors, Edits and Comments 
    if (!$csv) {
        print '
    	<tr class="ouw_dodgyextrarow">	
    		<td colspan="9"></td>';
    } else {
        print str_repeat($csv->sep(), 9);
    }
    // for each role print the rolename header with colspan of 2 for the headers: Edits, comments    
    foreach ($rolenames as $roleid => $rolename) {            	
        if (!$csv) {
        	print "
			<td colspan='2' class='ouw_firstingroup'>$rolename</td>			
			";
        } else {
            print $csv->quote($rolename).$csv->sep().$csv->sep();
        }
	}
	// print some header space for the 4 headers not from the role: First edit, Last edit, Words and Links
	// then open the next header row and print the first four headers: Page, Contributors, Edits and Comments			        			
    if (!$csv) {
        print <<<EOF
 		</tr>   
		<tr>
			<th class="ouw_leftcol" scope="col">$header->pagename<div class='ouw_pagecolumn'></div></th>
			<th scope="col">$header->contributorcount</th>
			<th scope="col">$header->edits</th>
			<th scope="col">$header->comments</th>
			<th scope="col">$header->startday<div class='ouw_datecolumn'></div></th>
			<th scope="col">$header->lastday<div class='ouw_datecolumn'></div></th>
			<th scope="col">$header->wordcount</th>
			<th scope="col">$header->linkcount</th>
			<th scope="col">$header->intensity*</th>
EOF;
    } else {
        print $csv->line().
              $csv->quote($header->pagename).$csv->sep().
              $csv->quote($header->contributorcount).$csv->sep().
              $csv->quote($header->edits).$csv->sep().
              $csv->quote($header->comments).$csv->sep().
              $csv->quote($header->startday).$csv->sep().
              $csv->quote($header->lastday).$csv->sep().
              $csv->quote($header->wordcount).$csv->sep().
              $csv->quote($header->linkcount).$csv->sep().
/* Do not display intensity * or explanation due to html specific characters
              $csv->quote($header->intensity.'*');
*/
              $csv->quote($header->intensity);
    }
    // for each rolename print the 3 headers: Edits, Comments and Intensity
    $count=count($rolenames);
	  foreach ($rolenames as $roleid => $rolename) {
	      $count--;
	      $endornot=$count ?'' : ' class="ouw_rightcol"'; 
        if (!$csv) {
            print "
			<th scope=\"col\" class='ouw_firstingroup'>$header->edits</th>
			<th scope=\"col\"$endornot>$header->comments</th>
			";
        } else {
            print $csv->sep().$csv->quote($header->edits).$csv->sep().$csv->quote($header->comments);
        }
	  }	
    if (!$csv) {
	  print '
		</tr>';
    } else {
        print $csv->line();
    }
}


/**
 * This prints a table showing the info about each page including the name of the page with a link to the page;
 *   the number of contributors, edits, comments, intensity; the number of edits and comments by role;
 *   the date of the first and last edits; the number of words and number of links to the page.
 *
 * @param object $subwiki        the subwiki id 
 * @param object $cm			
 * @param int $viewgroupid    the group id
 * @param object $header         the header text object
 * @param unknown_type $pagesinfo      the title, first day of editing, last day and text of each page
 * @param array $rolenames	   the array of role names
 * @param unknown_type $pageeditcount  the array of edit counts for each page
 * @param unknown_type $editsbyroles   the array of edit counts for each role
 * @param unknown_type $pagecontributorcount   the number of contributors on each page
 * @param int $totalusers             the total number of users
 * @param unknown_type $pagecommentcount       the number of comments on each page
 * @param unknown_type $commentsbyroles        the number of comments by each role
 */
function reportssummary_editedpagetable($contexts, $subwiki, $cm, $viewgroupid, $header, $pagesinfo, $rolenames, $pageeditcount, $editsbyroles,  
                                                $pagecontributorcount, $totalusers, $pagecommentcount, $commentsbyroles, $csv) {
                                                    
    // list of edited pages table
    // get the data from the database
    // get the number of pages that link to the each page
    $pagelinks = ouwiki_get_pagelinks($subwiki->id);
    // get the collaboration intensity
    $collabintensity = ouwiki_get_collaborationintensity($contexts, $subwiki->id, $viewgroupid);
    // this function prints out the first two header rows of the table
    reportssummary_editedpagetableheaders($header, $rolenames, $csv);    
                                                    
                                                    
    // for each page in the subwiki, use the info to display a row of data      
    foreach ($pagesinfo as $pageid => $pageinfo) {
        // get the page title
        $title = $pageinfo->title;
        // get total number of edits on page
        $editcount = $pageeditcount[$title];
        // get total number of conrtributors for page
        $contributorcount = $pagecontributorcount[$title];
        // calc percentage of contributers from all users
        $contribpercent = ($totalusers != 0) ? round($contributorcount / $totalusers * 100, 1) : 0;
        // get the number of total comments on the page        
        $commentcount = $pagecommentcount[$title];
        // calc the number of words in the page text
        $wordcount = str_word_count(strip_tags($pageinfo->text));
        // get the first day of editing on the page
        $startday = userdate($pageinfo->startday, get_string('strftimedate'));
        // get the last day of editing on the page        
        $lastday = userdate($pageinfo->lastday, get_string('strftimedate'));
        // get the number of links to the page
        $linkcount = (isset($pagelinks[$pageid])) ? $pagelinks[$pageid]->linkcount : 0;
        // get the page params to link to the page
        $pageparams = ouwiki_display_wiki_parameters($title, $subwiki, $cm);        
        // create a link to the page               
        $pagelink = "<a href='view.php?" . $pageparams . "'>".htmlspecialchars($title)."</a>";
        // if no title then must be start page of wiki, create link to this
        if ($title === null) {                    
		    // get the parameters to view the subwiki pages
		        $pageparams = ouwiki_display_wiki_parameters(null, $subwiki, $cm);
            $pagelink = "<a href='view.php?" . $pageparams . "'>" . get_string('startpage', 'ouwiki') . "</a>";
        }
    		// get the collaboration intensity
    		$collaboration = (isset($collabintensity[$pageid])) ? $collabintensity[$pageid] : ($editcount ? 0 : '');
        
        
        // start the row of data in the table for the current page and print the first four bits of info: 
        //    page name and link, contributor count with percentage, edit count and comment count 
        if (!$csv) {
            print "
        <tr>
        	<td class='ouw_leftcol'>$pagelink</td>
           	<td>$contributorcount ($contribpercent%)</td>           			
           	<td>$editcount</td>
           	<td>$commentcount</td>           			
           	<td class='ouw_datecolumn'>$startday</td>
           	<td class='ouw_datecolumn'>$lastday</td>
           	<td>$wordcount</td>
           	<td>$linkcount</td>
           	<td>$collaboration</td>
           	";
        } else {
            if ($title === null) {
                $title = get_string('startpage', 'ouwiki');
            }                    
            print $csv->quote(htmlspecialchars($title)).$csv->sep().
                  $csv->quote($contributorcount.' ('.$contribpercent.'%)').$csv->sep().
                  $csv->quote($editcount).$csv->sep().
                  $csv->quote($commentcount).$csv->sep().
                  $csv->quote($startday).$csv->sep().
                  $csv->quote($lastday).$csv->sep().
                  $csv->quote($wordcount).$csv->sep().
                  $csv->quote($linkcount).$csv->sep().
                  $csv->quote($collaboration).$csv->sep();
        }
        // for each role name, print the info for that role: edits, comments and intensity   	
        $count=count($rolenames);
        foreach ($rolenames as $roleid => $rolename) {       
    	      $count--;
    	      $endornot=$count ?'' : ' class="ouw_rightcol"'; 
            // get the number of edits by current role     
        		$editsbyrole = $editsbyroles[$roleid];    		    		
        		$roleeditcount = (isset($editsbyrole[$pageid])) ? $editsbyrole[$pageid]->editcount : 0;
        		// get the number of comments by role
        		$commentsbyrole = $commentsbyroles[$roleid];    		    		
        		$rolecommentcount = (isset($commentsbyrole[$pageid])) ? $commentsbyrole[$pageid]->commentcount : 0;
                // print the 3 data columns for the role name: edits, comments and collaboration    		
                if (!$csv) {
            		print "
        		<td class='ouw_firstingroup'>$roleeditcount</td>
        		<td$endornot>$rolecommentcount</td>
        		";    		    		
                } else {
                    print $csv->quote($roleeditcount).$csv->sep().$csv->quote($rolecommentcount).$csv->sep();
                }
        }        
        // print the data columns for the current page: first day of editing, 
        //    last day of editing, word count and number of links    			
        if (!$csv) {
		    print "           			
   		</tr>
   		";
        } else {
            print $csv->line();
        }
    }
    // close the table and div tags
    $strintensityexplanation=get_string('report_intensityexplanation','ouwiki');
    if (!$csv) {
        print "
	</table>
	<p><small>
	  * $strintensityexplanation
	</small></p>
</div>
";
/* Do not display intensity * or explanation due to html specific characters
    } else {
        print $csv->quote('*' .$strintensityexplanation).$csv->line();
*/
    }
}


/**
 * This prints out the table showing user activity info
 *
 * @param $header     the object containing the header text
 * @param $rolenames  the array containing the role names
 */    
function reportssummary_usertable($cm,$wikiid, $contexts, $subwiki, $viewgroupid, $header, $rolenames, $csv) {
    if(count($rolenames)==0) {
        return;
    }
    $doneheaders=false;
    $usercreates = ouwiki_get_userspagecreate($subwiki->id);

    // Get list of users with required roles (default Tutor and Student)
    // within required contexts [and group] for this wiki
    $usersinfo = array();
    if (($usersroles = ouwiki_get_usersin($contexts, $viewgroupid))) {

        // Get lists of users who can edit and/or comment on this wiki
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        if (!($usersedit = get_users_by_capability($context, 'mod/ouwiki:edit', 'u.id', '', '', '', $viewgroupid, '', false))) {
            $usersedit = array();
        }
        if (!($userscomment = get_users_by_capability($context, 'mod/ouwiki:comment', 'u.id', '', '', '', $viewgroupid, '', false))) {
            $userscomment = array();
        }

        // Build list of users who can edit and/or comment
        // and have required role [and group] for this wiki
        $userlist = '';
        foreach ($usersroles as $userid => $user) {
            if (isset($usersedit[$userid]) || isset($userscomment[$userid])) {
                $userlist .= ','.$userid;
            }
        }

        // If there are any users get user edits and user comments
        if ($userlist) { 
            $userlist = substr($userlist, 1);
            $usersinfo = ouwiki_get_usersedits($userlist, $subwiki->id, $viewgroupid);
            $usercomments = ouwiki_get_userscomments($userlist, $subwiki->id, $viewgroupid);
        }
    }

    // for each user in this wiki, use their info
    	foreach ($usersinfo as $userinfo) {
		    // the user's id number
		    $userid = $userinfo->userid;
		    // the user's name
		    $name = fullname($userinfo);
        $usernamedisp = !class_exists('ouflags') ? '' : $userinfo->username;
		    // number of seconds in a day
		    $day = 60 * 60 * 24;
		    
        // end day of editing by this user rounded down to the start of the day
        $starttime = $userinfo->startday;
        // end day of editing by this user rounded down to the start of the day
        $endtime = $userinfo->lastday;
        // start day of editing by user in date form
		    $startday = $starttime ? userdate($starttime, get_string('strftimedate')) : '';
		    // start day of editing by user in date form
        $lastday = $endtime ? userdate($endtime, get_string('strftimedate')) : '';
        // number of days between first and last edits
        $daycount = ceil(($endtime - $starttime) / $day);
        
        // number of pages created by this user
        $createdpages = array_key_exists($userid, $usercreates) ? $usercreates[$userid]->createdcount : 0;
        // number of pages edited by this user    
		    $editedpages = $userinfo->editedpagecount;
		    // number of edits by this user
        $edits = $userinfo->editcount;      
        // number of comments by this user                  
        $comments = array_key_exists($userid, $usercomments) ? $usercomments[$userid]->commentcount : 0;
        // number of contributions by this user
        $contributions = $edits + $comments;
        if(!$doneheaders) {
            // OK we now know we're printing something, so print headers
            $doneheaders=true;
            if (!$csv) {
                print <<<EOF
<div class="ouw_userlist">
    <h3>$header->userstabletitle</h3>    
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
EOF;
            } else {
                print $csv->line().$csv->quote($header->userstabletitle).$csv->line().
                      $csv->quote($header->username).$csv->sep().
                      $csv->quote($header->startday).$csv->sep().
                      $csv->quote($header->lastday).$csv->sep().
                      $csv->quote($header->timeonwiki).$csv->sep().
                      $csv->quote($header->createdpages).$csv->sep().
                      $csv->quote($header->editedpages).$csv->sep().
                      $csv->quote($header->edits).$csv->sep().
                      $csv->quote($header->comments).$csv->sep().
                      $csv->quote($header->contributions);
            }
        }        
		    // print the row of info for this user which includes name, start day of editing, last day, 
		    //  number of days on wiki, number of created pages, edited pages, edits and commnets and total contributions		            
        if (!$csv) {
            print 
<<<EOF
		<tr>
			<td class='ouw_leftcol'><a href="reportsuser.php?id=$wikiid&amp;userid=$userid">$name $usernamedisp</a></td>
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
EOF;
        } else {
            print $csv->line().$csv->quote($name).$csv->sep(). 
                  $csv->quote($startday).$csv->sep(). 
                  $csv->quote($lastday).$csv->sep(). 
                  $csv->quote($daycount).$csv->sep(). 
                  $csv->quote($createdpages).$csv->sep(). 
                  $csv->quote($editedpages).$csv->sep(). 
                  $csv->quote($edits).$csv->sep(). 
                  $csv->quote($comments).$csv->sep(). 
                  $csv->quote($contributions); 
        }
   	}
	  if($doneheaders) {
        if (!$csv) {
          	// close the table and div tags
            print '
   	</table>
</div>';
        } else {
            print $csv->line();
        }
	  }
}
?>