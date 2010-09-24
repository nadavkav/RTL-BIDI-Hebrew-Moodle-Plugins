<?php
require('basicpage.php');
require_once('reportsquerylib.php');
require_once('reportslib.php');
require_once('csv_writer.php');

global $CFG;
global $context;
define('OUWIKI_PAGESIZE',50);

$wikiid = required_param('id',PARAM_INT);
$wikipath = $CFG->wwwroot . "/mod/ouwiki/view.php?id=$wikiid";

// Individual wikis are handled differently
if($subwiki->userid) {
    redirect('reportsuser.php?'.ouwiki_display_wiki_parameters(null,$subwiki,$cm,OUWIKI_PARAMS_URL));
    exit;
}

require_capability('mod/ouwiki:viewcontributions',$context);

// Check there are some actual groups
$groups = groups_get_all_groups($cm->course,0,$cm->groupingid);
if(!$groups || count($groups)==0) {
    redirect('reportsgroup.php?'.ouwiki_display_wiki_parameters(null,$subwiki,$cm,OUWIKI_PARAMS_URL).
        '&viewgroup=0');
    exit;
}

// Check for downloading to .csv file
$title = get_string('report_summaryreports','ouwiki');
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

$header->group = get_string('report_group', 'ouwiki');
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
$header->grouptabletitle = get_string('report_grouptabletitle','ouwiki');    

$pagetexts = ouwiki_get_pages($subwiki->id);    
        
$contexts = get_related_contexts_string($context);
$coursecontext = get_context_instance(CONTEXT_COURSE,$course->id);
$allroles = get_roles_used_in_context($context,true);
$header->grouptabletitle = get_string('report_grouptabletitle','ouwiki');
        
if (!$csv) {
    print     
<<<EOF
<div class='ouw_grouplist'>
	<h3>$header->grouptabletitle</h3> 
	<table>
	<tr class="ouw_dodgyextrarow">
    		<td>&nbsp;</td>	
			
EOF;
} else {
    print $csv->quote($header->grouptabletitle).$csv->line().$csv->sep();
}
	
$rolenames = array();
$roleids = array();

foreach ($allroles as $role) {
    if(!ouwiki_reports_include_role($role)) {
        continue;
    }
    $rolename = role_get_name($role,$coursecontext);
    $usercount = 0;
    foreach ($groups as $group) {
        $usercount = max(count(ouwiki_get_users($contexts, $group->id, $role->id)), $usercount);            
    }
    if ($usercount > 0) {
        $rolenames[$role->id] = $rolename;
        if (!$csv) {
            print
<<<EOF
    	    <td class="ouw_firstingroup" colspan="8">$rolename</td>
EOF;
        } else {
            print $csv->quote($rolename).str_repeat($csv->sep(), 8);
        }
    }
}    
if (!$csv) {
    print 
<<<EOF
		</tr>
		<tr>			
			<th class="ouw_leftcol" scope="col">$header->group</th>
		
EOF;
} else {
    print $csv->line().$csv->quote($header->group);
}
foreach ($rolenames as $roleid => $rolename) {    
    if (!$csv) {
        print
<<<EOF
			<th scope="col" class='ouw_firstingroup'>$header->total</th>	
			<th scope="col">$header->active</th>
			<th scope="col">$header->inactive</th>
			<th scope="col">$header->percentageparticipation</th>
			<th scope="col" class='ouw_firstingroup'>$header->editedpages</th>
		    <th scope="col">$header->uneditedpages</th>
		    <th scope="col">$header->edits</th>
		    <th scope="col" class='ouw_rightcol'>$header->comments</th>
EOF;
    } else {
        print $csv->sep().$csv->quote($header->total).$csv->sep().$csv->quote($header->active).
              $csv->sep().$csv->quote($header->inactive).$csv->sep().$csv->quote($header->percentageparticipation).
              $csv->sep().$csv->quote($header->editedpages).$csv->sep().$csv->quote($header->uneditedpages).
              $csv->sep().$csv->quote($header->edits).$csv->sep().$csv->quote($header->comments);
    }
}	
if (!$csv) {
    print '
    		</tr>';
}
	
foreach ($groups as $group) {
    $viewgroupid = $group->id;

      $roleseditcount = array();
      $rolescommentcount = array();
    
      $pageeditcount = array();
      $pagecommentcount = array();
      $pagecontributorcount = array();
      foreach ($pagetexts as $pagetext) {
          $pagecommentcount[$pagetext->title] = 0;
          $pageeditcount[$pagetext->title] = 0;
          $pagecontributorcount[$pagetext->title] = 0;
      }
    
      $commentsbyroles = array();
      $editsbyroles = array();	    
    
      $totalusers = 0;
    
    if (!$csv) {
        print
<<<EOF
		<tr>			
			<td class='ouw_leftcol'><a href="reportsgroup.php?id=$wikiid&amp;viewgroup=$viewgroupid">$group->name</a><div class='ouw_groupcolumn'></div></td>
EOF;
    } else {
        print $csv->line().$csv->quote($group->name);
    }
			
    foreach ($rolenames as $roleid => $rolename) {
        $usercount = count(ouwiki_get_users($contexts, $viewgroupid, $roleid));
        
        if ($usercount > 0) {
                                   
	        $allsubwikipages = ouwiki_get_subwiki_index($subwiki->id);
	        $pagecount = count($allsubwikipages);                
	        		        
	        $totalusers += $usercount;
	        
	        $activeusers = ouwiki_get_activeusers($contexts, $subwiki->id, $viewgroupid, $roleid);
	        $activeusercount = count($activeusers);        
	        $inactiveusercount = $usercount - $activeusercount;		        
	        
	        // calc comments
	        $pagecomments = ouwiki_get_pagecomments($contexts, $subwiki->id, $viewgroupid, $roleid);
	        
	        $commentsbyroles[$rolename] = $pagecomments;	        
	        
	        $commentcount = 0;        
	        foreach ($pagecomments as $pagecomment) {
	            $commentcount += $pagecomment->commentcount;            
	            $pagecommentcount[$pagecomment->pagetitle] += $pagecomment->commentcount;
	        }
	        $rolescommentcount[$rolename] = $commentcount;		        
	        $pageedits = ouwiki_get_editedpages($contexts, $subwiki->id, $viewgroupid, $roleid);
	        $editsbyroles[$rolename] = $pageedits;		        
	        $editedpagecount = count($pageedits);
	        $editedpageratio = $pagecount==0 ? 0 : round($editedpagecount / $pagecount * 100, 1);
	        $uneditedpagecount = $pagecount - $editedpagecount;
	        $uneditedpageratio = 100 - $editedpageratio;  
	        $editcount = 0;
	        foreach ($pageedits as $pageedit) {
	            $editcount += $pageedit->editcount;        
	            $pageeditcount[$pageedit->pagetitle] += $pageedit->editcount;    
 	            $pagecontributorcount[$pageedit->pagetitle] += $pageedit->contributorcount;
	        }
	        $roleseditcount[$rolename] = $editcount;	                 
          $participation = round($activeusercount / $usercount * 100,1);
            if (!$csv) {
                print 
<<<EOF
        	<td class='ouw_firstingroup'>$usercount</td>        	
        	<td>$activeusercount</td>
        	<td>$inactiveusercount</td>
        	<td>$participation%</td>
        	<td class='ouw_firstingroup'>$editedpagecount</td>
        	<td>$uneditedpagecount</td>
        	<td>$editcount</td>
        	<td class='ouw_rightcol'>$commentcount</td>
EOF;
            } else {
                print $csv->sep().$csv->quote($usercount).$csv->sep().$csv->quote($activeusercount).
                      $csv->sep().$csv->quote($inactiveusercount).$csv->sep().$csv->quote($participation.'%').
                      $csv->sep().$csv->quote($editedpagecount).$csv->sep().$csv->quote($uneditedpagecount).
                      $csv->sep().$csv->quote($editcount).$csv->sep().$csv->quote($commentcount);
            }
        // if no data then print blank cells
        } else {
            if (!$csv) {
                print '
	       	<td colspan="8">&nbsp;</td>';
            } else {
                print str_repeat($csv->sep(), 8);
            }        	
        }	        
    }
	    // close the row tag
    if (!$csv) {
        print '
   	</tr>';
    } else {
        print $csv->line();
    }
}
    // close table and div tags
if (!$csv) {
    print '
   	</table>
</div>';    
} else {
    // That's it for downloading csv file
    exit;
}

// For non-group wiki, also offer non-group view link
if(!$subwiki->groupid) {
    print '<p><a href="reportsgroup.php?'.ouwiki_display_wiki_parameters(null,$subwiki,$cm).'&amp;viewgroup=0">'.
        get_string('report_viewallgroups','ouwiki').'</a></p>';
}

// Display csv download links
$wikiparams = ouwiki_display_wiki_parameters(null,$subwiki,$cm);
print '<ul class="csv-links"><li><a href="reportssummary.php?'.$wikiparams.
    '&amp;format=csv">'.get_string('csvdownload','ouwiki').'</a></li>
    <li><a href="reportssummary.php?'.$wikiparams.'&amp;format=excelcsv">'.
    get_string('excelcsvdownload','ouwiki').'</a></li></ul>';

// Footer
ouwiki_print_footer($course, $cm, $subwiki);
?>