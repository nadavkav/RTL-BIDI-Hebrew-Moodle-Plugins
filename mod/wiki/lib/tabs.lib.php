<?php
/// Original DFwiki created by David Castro, Ferran Recio and Marc Alier.
/// Library of functions and constants for tabs in module wiki

/**
 * print all tabs from global params
 * @param String $seltab=false: selected tab identifier
 */
function wiki_print_tabs ($seltab=false) {
	global $USER,$COURSE,$CFG, $WS;
	
	if ($seltab) wiki_param('selectedtab',$seltab);
	
	//main tabs options
    $tabs = array();
    
    $cm = wiki_param('cm');
    $page = wiki_param('page');
    $dfperms = wiki_param('dfperms');
	$wiki = wiki_param('dfwiki');
    
    $context = get_context_instance(CONTEXT_MODULE,$cm->id);
    if (substr($page,0,strlen('discussion:'))=='discussion:') {
        $tabs[] = 'view';
        $tabs[] = 'discussion';
        if ($dfperms['discuss']) {
            $tabs[] = 'editdiscussion';       // Edit dfwiki discussion
            $tabs[] = 'adddiscussion';       // Add new item to the discussion
            $tabs[] = 'infodiscussion';
        }

	} else {
		$tabs[] = 'view';
		$tabs[] = 'discussion';
		if ($dfperms['edit']) {
			$tabs[] = 'edit';
        }
		$tabs[] = 'info';
		$tabs[] = 'navigator';

        // grades tab only available for admins and teachers
        if ($wiki->evaluation && has_capability('mod/wiki:mainreview', $context))
            $tabs[] = 'grades';

		if(has_capability('mod/wiki:editawiki',$context)){
			$tabs[] = 'admin';
		}
     }


    $tabrows = array();
    $rows  = array();
	$wikibookname = "";
	
	$wikibook = wiki_param('wikibook');
	$linkid = wiki_param('linkid');
	$pagedata = wiki_param('pagedata');
	$groupmember = wiki_param('groupmember');
	$member = wiki_param('member');
	
	
	if (!empty($wikibook)){
		$wikibookname = '&amp;wikibook='.urlencode($wikibook);
	}

    $release = '';
    if ($WS->pageaction == 'edit') {
        $section     = optional_param('section',     '', PARAM_TEXT);
        $sectionhash = optional_param('sectionhash', '', PARAM_TEXT);
        if (($section != '') && ($sectionhash != ''))
            $release = '&amp;sectionhash='.$sectionhash;
    }
    
	$wikibaseurl = $CFG->wwwroot.'/mod/wiki/';
	
	if (!wiki_param ('wiki_tabs_cleaned')) {
	    foreach ($tabs as $num => $tab) {
	
	        switch ($tab) {
	
	            case 'view':
	
					if (substr($page,0,strlen('discussion:'))=='discussion:') {
	                    //$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page='.$tab.'/'.urlencode(substr($page,strlen('discussion:'),strlen($page)-strlen('discussion:'))) .'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string('return','wiki'));
	                    $tabme = new wikitab ($tab,'$baseurl/view.php?$basic&amp;page='.$tab.'/'.urlencode(substr($page,strlen('discussion:'),strlen($page)-strlen('discussion:'))) .'&amp;editor='.$pagedata->editor.$release.'',get_string('return','wiki'),true);
	                } else {
						$tabname = get_string($tab,'wiki');
						$tabme = new wikitab ($tab,'$baseurl/view.php?$basic&amp;page='.$tab.'/$pagename&amp;editor='.$pagedata->editor.$release.'',$tabname,true);
	                }
	                //$tabme = new wikitab ($tab,'$baseurl/view.php?$basic&amp;page='.$tab.'/$pagename&amp;editor='.$pagedata->editor.$release.'',null,true);
					wiki_add_tab($tabme,$num);
	                break;
	
	            case 'adddiscussion':
	
					//$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page=adddiscussion/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),' + ');
					$tabme = new wikitab ($tab,'$baseurl/view.php?$basic&amp;page='.$tab.'/$pagename&amp;editor='.$pagedata->editor.$release.'','+',true);
					wiki_add_tab($tabme,$num);
	                break;
	
	            case 'discussion':
	
	                if (substr($page,0,strlen('discussion:'))=='discussion:') {
	                    //$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page=discussion/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string($tab,'wiki'));
	                    $tabme = new wikitab ($tab,'$baseurl/view.php?$basic&amp;page='.$tab.'/$pagename&amp;editor='.$pagedata->editor.$release.'',null,true);
	                } else {
	                    //$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page=discussion/discussion:'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string('discuss','wiki'));
	                    $tabme = new wikitab ($tab,'$baseurl/view.php?$basic&amp;page='.$tab.'/discussion:$pagename&amp;editor='.$pagedata->editor.$release.'',get_string('discuss','wiki'),true);
	                }
	                
					wiki_add_tab($tabme,$num);
	                break;

                case 'grades':
                        $url = '$baseurl/grades/grades.evaluation.php?cid='.$COURSE->id.'&amp;cmid='.$WS->cm->id;
	                    $tabme = new wikitab ($tab, $url, get_string('grades'), true);
                        wiki_add_tab($tabme,$num);
                        break;
                    
	            
	            /*case 'infodiscussion':
	
					$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page=infodiscussion/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string($tab,'wiki'));
	                break;
	            
	            case 'editdiscussion':
					$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page=editdiscussion/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string($tab,'wiki'));
	                break;
	            
	            case 'navigator':
					$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page='.$tab.'/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string('navigator','wiki'));
	                break;
	                
				case 'edit':
					$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page=edit/'.urlencode($page).'&amp;editor='.$pagedata->editor.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string($tab,'wiki'));
	                break;
	
	            case 'info':
					$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page='.$tab.'/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string($tab,'wiki'));
	                break;
	             
				case 'view_evaluations':
					$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page='.$tab.'/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string('view_evaluations','wiki'));
	                break;
	
				case 'view_my_evaluations':
					$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page='.$tab.'/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string('view_my_evaluations','wiki'));
	                break;
	
				case 'admin':
					$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page='.$tab.'/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string($tab,'wiki'));
	                break;*/
	
	            default:
					$tabme = new wikitab ($tab,'$baseurl/view.php?$basic&amp;page='.$tab.'/$pagename&amp;editor='.$pagedata->editor.$release.'',null,true);
					wiki_add_tab($tabme,$num);
					//$rows[] = new tabobject($tab, $wikibaseurl.'view.php?id='.$linkid.'&amp;page='.$tab.'/'.urlencode($page).'&amp;editor='.$pagedata->editor.$release.'&amp;gid='.$groupmember->groupid.'&amp;uid='.$member->id.'&amp;wikibook='.urlencode($wikibook),get_string($tab,'wiki'));
	                break;
	            }
	    }
	}
    
    //add part defined tabs
    $wikitabs = wiki_param ('wikitabs_order');
    $wikitabs_obj = wiki_param('wikitabs_obj');
    if (!is_array($wikitabs)) {
    	$wikitabs = wiki_param ('wikitabs',array());
    }
    asort($wikitabs);
    foreach ($wikitabs as $wt => $num) {
    	$wikitab = $wikitabs_obj[$wt];
    	if ($wikitab->visible()) {
    		$tabname = $wt;
    		$tabtext = $wikitab->text();
    		if (!$tabtext) $tabtext = get_string($tabname,'wiki');
    		$rows[] = new tabobject($tabname, $wikitab->url(),$tabtext);
    	}
    }
    
    $tabrows[] = $rows;

    ////interface
    
    $pageaction = wiki_param('pageaction');
    $selectedtab = wiki_param('selectedtab');
    
    // tabs for discussions
    // I need to check if this is a preview or the real data tab
    if (($pageaction == 'view') and (($selectedtab =='adddiscussion') or ($selectedtab =='editdiscussion'))) {
        // The preview is submitted and we show the discussion info tab.
        $selectedtab = 'discussion';
    } else {
        if (($pageaction == 'edit') and ($selectedtab =='view')) {
            // The preview is submitted and we show the wiki info tab.
            $selectedtab = 'edit';
        }
    }
    
    wiki_print_tab_objects($tabrows, $selectedtab);
}

//------------ tab management -----------------

/**
 * adds a tab
 * @param wikitab $tab: wikitab object
 * @param int $order=false: prefered sort order
 */
function wiki_add_tab ($tab, $order=false) {
	//get tabs
	$wikitabs = wiki_param ('wikitabs_order');
    $wikitabs_obj = wiki_param('wikitabs_obj');
	if (!is_array($wikitabs)) $wikitabs = array();
	
	if ($order===false) $order = 1000+count($wikitabs);
	$name = $tab->name();
	$wikitabs[$name] = $order;
	$wikitabs_obj[$name] = $tab;
	//restore
	wiki_param ('wikitabs_order',$wikitabs);
	wiki_param('wikitabs_obj',$wikitabs_obj);
	
	//put tab
	/*if ($order===false || $order>=count($wikitabs)) {
		$wikitabs[] = $tab;
	} else {
		$initials = array_slice($wikitabs,0,$order);
		$initials[] = $tab;
		$wikitabs = array_merge($initials, array_slice($wikitabs,$order));
	}
	//set tabs
	$wikitabs = wiki_param ('wikitabs',$wikitabs);*/
}

/**
 * remove all current tabs (empty tabs space)
 */
function wiki_clean_tabs () {
	wiki_param ('wikitabs_order',array());
	//this wiki_param is deprecated and will be removed in the future
	wiki_param ('wiki_tabs_cleaned',true);
}


//------------- internal functions -------------


/**
 * Returns a string containing a nested list, suitable for formatting into tabs with CSS.
 *
 * @param array $tabrows An array of rows where each row is an array of tab objects
 * @param string $selected  The id of the selected tab (whatever row it's on)
 * @param array  $inactive  An array of ids of inactive tabs that are not selectable.
 * @param array  $activated An array of ids of other tabs that are currently activated
**/
function wiki_print_tab_objects($tabrows, $selected=NULL, $inactive=NULL, $activated=NULL, $return=false) {
    global $CFG;

/// $inactive must be an array
    if (!is_array($inactive)) {
        $inactive = array();
    }

/// $activated must be an array
    if (!is_array($activated)) {
        $activated = array();
    }

/// Convert the tab rows into a tree that's easier to process
    if (!$tree = wiki_convert_tabrows_to_tree($tabrows, $selected, $inactive, $activated)) {
        return false;
    }

/// Print out the current tree of tabs (this function is recursive)

    $output = wiki_convert_tree_to_html($tree);

	$output = "\n\n".'<div class="tabtree">'.$output.'</div><div class="clearer"> </div>'."\n\n";

/// We're done!

    if ($return) {
        return $output;
    }
    echo $output;
}


function wiki_convert_tree_to_html($tree, $row=0) {

	global $WS;

    $str = "\n".'<ul class="tabrow'.$row.'">'."\n";

    $first = true;
    $count = count($tree);

    foreach ($tree as $tab) {
        $count--;   // countdown to zero

        $liclass = '';

        if ($first && ($count == 0)) {   // Just one in the row
            $liclass = 'first last';
            $first = false;
        } else if ($first) {
            $liclass = 'first';
            $first = false;
        } else if ($count == 0) {
            $liclass = 'last';
        }

        if ((empty($tab->subtree)) && (!empty($tab->selected))) {
            $liclass .= (empty($liclass)) ? 'onerow' : ' onerow';
        }

        if ($tab->inactive || $tab->active || ($tab->selected && !$tab->linkedwhenselected)) {
            if ($tab->selected) {
                $liclass .= (empty($liclass)) ? 'here selected' : ' here selected';
            } else if ($tab->active) {
                $liclass .= (empty($liclass)) ? 'here active' : ' here active';
            }
        }

        $str .= (!empty($liclass)) ? '<li class="'.$liclass.'">' : '<li>';

		$page = wiki_param('page');

		if(!wiki_discussion_page_exists($WS,$page) && $tab->id=='discussion' && $tab->text==get_string('discuss','wiki')){
			$estilo = ' style="color:red"';
		} else{
			$estilo = "";
		}

		if ($tab->inactive || $tab->active || ($tab->selected && !$tab->linkedwhenselected)) {
            $str .= '<a href="#" title="'.$tab->title.'"><span'.$estilo.'>'.$tab->text.'</span></a>';
        } else {
            $str .= '<a href="'.$tab->link.'" title="'.$tab->title.'"><span'.$estilo.'>'.$tab->text.'</span></a>';
        }

        if (!empty($tab->subtree)) {
            $str .= wiki_convert_tree_to_html($tab->subtree, $row+1);
        } else if ($tab->selected) {
            $str .= '<div class="tabrow'.($row+1).' empty">&nbsp;</div>'."\n";
        }

        $str .= '</li>'."\n";
    }
    $str .= '</ul>'."\n";

    return $str;
}


function wiki_convert_tabrows_to_tree($tabrows, $selected, $inactive, $activated) {

/// Work backwards through the rows (bottom to top) collecting the tree as we go.

    $tabrows = array_reverse($tabrows);

    $subtree = array();

    foreach ($tabrows as $row) {
        $tree = array();

        foreach ($row as $tab) {
            $tab->inactive = in_array((string)$tab->id, $inactive);
            $tab->active = in_array((string)$tab->id, $activated);
            $tab->selected = (string)$tab->id == $selected;

            if ($tab->active || $tab->selected) {
                if ($subtree) {
                    $tab->subtree = $subtree;
                }
            }
            $tree[] = $tab;
        }
        $subtree = $tree;
    }

    return $subtree;
}

?>
