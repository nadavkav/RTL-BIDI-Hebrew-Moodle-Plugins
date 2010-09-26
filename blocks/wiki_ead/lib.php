<?php

/**
 * This file contains block_wiki_ead functions.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: lib.php,v 1.27 2009/01/02 11:34:00 kenneth_riba Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

$dfwiki_ead_stat = false;


//this function prints in the main content page
//the ranking of the most viewed pages.
function wiki_ead_mostviewed (&$WS){

    //load ead tools
    $ead = wiki_manager_get_instance();

    $pages = $ead->get_wiki_most_viewed_pages();



    echo '<h1>'.get_string('mostviewed','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';

    //mount table
    $table->head = array (get_string('rank','wiki'),get_string('page'),get_string('hits','wiki'),get_string('version'),get_string('created','wiki'),get_string('lastmodified'));
    $table->wrap = array ('nowrap','','','','nowrap','nowrap');
    $table->width = '100%';
	$table->align = array ('center','left','center','center','center','center');

    $num = 1;
    foreach ($pages as $page){
    	if ($pageinfo = wiki_page_last_version($page)){
    		$row = array(
   					    $num,'<a href="view.php?id='.$WS->linkid.'&amp;page='.urlencode($pageinfo->pagename).'">'.$pageinfo->pagename.'</a>',
    					$pageinfo->hits,$pageinfo->version,
    					strftime('%A, %d %B %Y %H:%M',$pageinfo->created),
    					strftime('%A, %d %B %Y %H:%M',$pageinfo->lastmodified)
    					);
    		$table->data[] = $row;
    		$num++;
    	}
    }

    print_table($table);

   echo '</div>';
}

//print the page list ordened by created date
function wiki_ead_newest(&$WS){

    //load ead tools
    $ead = wiki_manager_get_instance();

    $pages = $ead->get_wiki_newest_pages();



    echo '<h1>'.get_string('newest','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';

    //mount table
    $table->head = array (get_string('rank','wiki'),get_string('page'),get_string('hits','wiki'),get_string('version'),get_string('created','wiki'),get_string('lastmodified'));
    $table->wrap = array ('nowrap','','','','nowrap','nowrap');
    $table->width = '100%';
	    $table->align = array ('center','left','center','center','center','center');

    $num = 1;
    foreach ($pages as $page){
    	if ($pageinfo = wiki_page_last_version($page)){
    		$row = array(
    					$num,'<a href="view.php?id='.$WS->linkid.'&amp;page='.urlencode($pageinfo->pagename).'">'.$pageinfo->pagename.'</a>',
    					$pageinfo->hits,$pageinfo->version,
    					strftime('%A, %d %B %Y %H:%M',$pageinfo->created),
    					strftime('%A, %d %B %Y %H:%M',$pageinfo->lastmodified)
    					);
    		$table->data[] = $row;
    		$num++;
    	}
    }

    print_table($table);

    echo '</div>';
}

function wiki_ead_updatest(&$WS){

    //load ead tools
    $ead = wiki_manager_get_instance();

    $pages = $ead->get_wiki_most_uptodate_pages();



    echo '<h1>'.get_string('updatest','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';

    //mount table
    $table->head = array (get_string('rank','wiki'),get_string('page'),get_string('user'),get_string('version'),get_string('created','wiki'),get_string('lastmodified'));
    $table->wrap = array ('nowrap','','','','nowrap','nowrap');
    $table->width = '100%';
	    $table->align = array ('center','left','left','center','center','center');

    $num = 1;
    foreach ($pages as $page){
    	if ($pageinfo = wiki_page_last_version($page)){
    		$row = array(
    					$num,'<a href="view.php?id='.$WS->linkid.'&amp;page='.urlencode($pageinfo->pagename).'">'.$pageinfo->pagename.'</a>',
    					wiki_get_user_info($pageinfo->author),
    					$pageinfo->version,
    					strftime('%A, %d %B %Y %H:%M',$pageinfo->created),
    					strftime('%A, %d %B %Y %H:%M',$pageinfo->lastmodified)
    					);
    		$table->data[] = $row;
    		$num++;
    	}
    }

    print_table($table);

    echo '</div>';
}

function wiki_ead_orphaned(){
    global $WS;

    //load ead tools
    $ead = wiki_manager_get_instance();

    $pages = $ead->get_wiki_orphaned_pages();



    echo '<h1>'.get_string('orphaned','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';

    //mount table
    $table->head = array ('',get_string('page'),get_string('user'),get_string('version'),get_string('created','wiki'),get_string('lastmodified'));
    $table->wrap = array ('nowrap','','','','nowrap','nowrap');

    $table->width = '100%';

    $num = 1;
    foreach ($pages as $page){
    	if ($pageinfo = wiki_page_last_version($page)){
    		$row = array(
    					$num,'<a href="view.php?id='.$WS->linkid.'&amp;page='.urlencode($pageinfo->pagename).'">'.$pageinfo->pagename.'</a>',
    					wiki_get_user_info($pageinfo->author),
    					$pageinfo->version,
    					strftime('%A, %d %B %Y %H:%M',$pageinfo->created),
    					strftime('%A, %d %B %Y %H:%M',$pageinfo->lastmodified)
    					);
    		$table->data[] = $row;
    		$num++;
    	}
    }

    print_table($table);

    echo '</div>';
}

//print the wanted pages list
function wiki_ead_wanted(){
    global $WS;

    //load ead tools
    $ead = wiki_manager_get_instance();

    $pages = $ead->get_wiki_wanted_pages();



    echo '<h1>'.get_string('wanted','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';

    //mount table
    $table->head = array ('',get_string('page'),get_string('camefrom','wiki'));
    $table->wrap = array ('','nowrap','');
    $table->width = '100%';
	$num=1;
    foreach ($pages as $page){
    	$row = array(
    				$num,$page.'<a href="view.php?id='.$WS->linkid.'&amp;page='.$page.'">?</a>',
    				);
    	//get the pages that refers that page
    	$camefroms = $ead->get_wiki_page_camefrom($page);
    	//$row[2] = '<a href="view.php?id='.$WS->cm->id.'&amp;page='.$camefroms[0].'">'.$camefroms[0].'</a>';
    	$cames = array();
    	foreach ($camefroms as $camefrom) {
    		$cames[] = '<a href="view.php?id='.$WS->cm->id.'&amp;page='.$camefrom->pagename.'">'.$camefrom->pagename.'</a> ';
    	}
    	$row[2] = implode (', ',$cames);

    	$table->data[] = $row;
		$num++;
    }

    print_table($table);

    echo '</div>';
}

function wiki_ead_activestusers(){
    global $WS;

    //load ead tools
    $ead = wiki_manager_get_instance();

    $users = $ead->activestusers();

    echo '<h1>'.get_string('activestusers','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';
    //mount table
	    $table->head = array (get_string('rank','wiki'),get_string('user'),get_string('participations','wiki'),get_string('participationin','wiki'));
	    $table->wrap = array ('nowrap','','','');
    $table->width = '100%';
	    $table->align = array ('center','center','center','center');

    $num = 1;
    foreach ($users as $user){
    	$pages = $ead->user_activity($user);
    	foreach ($pages as $key=>$page){
    		$pages[$key] = '<a href="view.php?id='.$WS->linkid.'&amp;page='.urlencode($page).'">'.$page.'</a>';
    	}

    	$row = array(
    				$num,
    				wiki_get_user_info($user),
    				$ead->user_num_activity($user),
    				implode (',',$pages)
    			);
    	$table->data[] = $row;
    	$num++;
    }

    print_table($table);

    echo '</div>';
}

//------------------------------------- SERIOUS ADMINITRATION TOOL -----------------------------------


//this function deletes a page with all its versions and synonimous
function wiki_ead_delpage(){
    global $dfwiki_ead_stat;
	$dfwiki = wiki_param('dfwiki');
	$groupmember = wiki_param('groupmember');
	$member = wiki_param('member');
	
	$delpage = optional_param('delpage',NULL,PARAM_CLEAN);
	if (isset($delpage)){
		if (wiki_can_change()) {
			if (wiki_page_exists (false,stripslashes($delpage), false)) {
				$delconfirm = optional_param('dfformdelconfirm',NULL,PARAM_ALPHA);
				if (isset ($delconfirm)){
					//del page and synonymous
					delete_records ('wiki_synonymous','dfwiki',$dfwiki->id,'original',$delpage);

					if (delete_records ('wiki_pages','dfwiki',$dfwiki->id,'pagename',$delpage)){
						$dfwiki_ead_stat = 'delok';
					}else{
						$dfwiki_ead_stat = 'delerror';
					}
				}else{
					//show confirm form
					$dfwiki_ead_stat = 'delconfirm';
				}
			}else{
				//this page can't be eliminated
				$dfwiki_ead_stat = 'delerror';
			}
		}
	}
    wiki_param ('dfcontent',6);
    wiki_main_setup();
}

function wiki_ead_print_delpage(){
    global $WS,$dfwiki_ead_stat;

	$delpage = optional_param('delpage',NULL,PARAM_CLEAN);
    echo '<h1>'.get_string('delpage','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';

    switch ($dfwiki_ead_stat){
    	case 'delok':
    		echo '<h2>'.get_string('delok','wiki').' '.stripslashes($delpage).'</h2><br />';
    		echo '<form method="post" action="view.php?id='.$WS->cm->id.'">
    				<div class="textcenter"><input type="submit" value="'.get_string('continue').'" /></div>
    			</form>';
    		break;
    	case 'delconfirm':
    		echo '<h2>'.get_string('delconfirm','wiki').' '.stripslashes($delpage).'?</h2>';
    		echo '<p>'.get_string('delmessage','wiki').'</p>';
    		echo '<table border="0" class="boxaligncenter">
    			<tr>
    				<td>
    					<form method="post" action="view.php?id='.$WS->cm->id.'&amp;delpage='.stripslashes($delpage).'">
    						<div>
    						<input type="submit" name="dfformdelconfirm" value="    '.get_string('yes').'    " />
    						<input type="hidden" name="dfsetup" value="0" />
    						</div>
    					</form>
    				</td>
    				<td>
    					<form method="post" action="view.php?id='.$WS->cm->id.'">
    						<div>
    						<input type="submit" value="    '.get_string('no').'    " />
    						</div>
    					</form>
    				</td>
    			</tr>
    			</table>';
    		break;
    	case 'delerror':
    		echo '<h2>'.get_string('delerror','wiki').' '.stripslashes($delpage).'</h2><br />';

    		echo '<form method="post" action="view.php?id='.$WS->cm->id.'">
    				<div class="textcenter"><input type="submit" value="'.get_string('continue').'" /></div>
    			  </form>';
    		break;
    }

    echo '</div>';
}


function wiki_ead_updatepage(){
    global $dfwiki_ead_stat,$CFG;
	
	$updatepage = wiki_param('updatepage');
	$dfwiki = wiki_param('dfwiki');
	
    if (isset($updatepage)){
    	if (wiki_can_change()) {
    		if (wiki_page_exists (false,$updatepage, false)) {
				$updatename = optional_param('dfformupdatename',NULL,PARAM_FILE);
				$updateconfirm = optional_param('dfformupdateconfirm',NULL,PARAM_ALPHA);
    			if (isset ($updateconfirm) && $updatename!=''){
    				//update page and synonymous

    				//rename first page
    				if ($dfwiki->pagename == $updatepage){
    					$dfwiki->pagename = addslashes($updatename);
    					update_record('wiki',$dfwiki);
    				}

    				//rename synonynous
    				$quer = 'UPDATE '. $CFG->prefix.'wiki_synonymous
    						SET original=\''.addslashes($updatename).'\'
    						WHERE original=\''.addslashes($updatepage).'\' AND dfwiki=\''.$dfwiki->id.'\'';

    				execute_sql($quer,false);

					//rename votes
    				$quer = 'UPDATE '. $CFG->prefix.'wiki_votes
    						SET pagename=\''.addslashes($updatename).'\'
    						WHERE pagename=\''.addslashes($updatepage).'\' AND dfwiki=\''.$dfwiki->id.'\'';

    				execute_sql($quer,false);

    				//rename pagetable entry
    				$quer = 'UPDATE '. $CFG->prefix.'wiki_pages
    						SET pagename=\''.addslashes($updatename).'\'
    						WHERE pagename=\''.addslashes($updatepage).'\' AND dfwiki=\''.$dfwiki->id.'\'';



    				if (execute_sql($quer,false)){
    					$dfwiki_ead_stat = 'updateok';
    				}else{
    					$dfwiki_ead_stat = 'updateerror';
    				}
    			}else{
    				//show confirm form
    				$dfwiki_ead_stat = 'updateconfirm';
    			}
    		}else{
    			//this page can't be eliminated
    			$dfwiki_ead_stat = 'updateerror';
    		}
    	}
    }
    wiki_param ('dfcontent',7);
    wiki_main_setup();
}

function wiki_ead_print_updatepage(){
    global $WS,$dfwiki_ead_stat;

    echo '<h1>'.get_string('updatepage','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';

	$updatename = optional_param('dfformupdatename',NULL,PARAM_FILE);
    switch ($dfwiki_ead_stat){
    	case 'updateok':
    		echo '<h2>'.get_string('updateok','wiki').' '.stripslashes($WS->updatepage).'</h2><br />';
    		echo '<form method="post" action="view.php?id='.$WS->cm->id.'&amp;page='.urlencode($updatename).'">
    				<div class="textcenter"><input type="submit" value="'.get_string('continue').'" /></div>
    			</form>';
    		break;
    	case 'updateconfirm':
    		echo '<h2>'.get_string('updateconfirm','wiki').' '.stripslashes($WS->updatepage).'?</h2>';
    		echo '<p>'.get_string('updatemessage','wiki').'</p>';
    		echo '<form method="post" action="view.php?id='.$WS->cm->id.'&amp;updatepage='.urlencode($WS->updatepage).'">
    			<table border="0" class="boxaligncenter">
    			<tr>
    				<td>
    						<input type="text" name="dfformupdatename" value="'.p($WS->updatepage).'" />
    						<input type="hidden" name="dfsetup" value="1" />
    				</td>
    				<td>
    						<input type="submit" name="dfformupdateconfirm" value="    '.get_string('update').'    " />
    				</td>
    			</tr>
    			</table></form>';
    		break;
    	case 'updateerror':
    		echo '<h2>'.get_string('updateerror','wiki').' '.stripslashes($WS->updatepage).'</h2><br />';

    		echo '<form method="post" action="view.php?id='.$WS->cm->id.'">
    				<div class="textcenter"><input type="submit" value="'.get_string('continue').'" /></div>
    			</form>';
    		break;
    }

    echo '</div>';
}

function wiki_ead_enpage(){
    global $dfwiki_ead_stat,$CFG;

	$dfformenconfirm = optional_param('dfformenconfirm','',PARAM_MULTILANG);
	$enpage= wiki_param('enpage');
	$dfwiki = wiki_param('dfwiki');
    if (isset($enpage)){
    	if (wiki_can_change()) {
    		if ($info = wiki_page_last_version ($enpage)) {
    			if (!empty($dfformenconfirm)){
    				//calculate new editable value
    				$newedit = ($info->editable+1)%2;

    				//rename pagetable entry
    				$quer = 'UPDATE '. $CFG->prefix.'wiki_pages
    						SET editable=\''.$newedit.'\'
    						WHERE pagename=\''.$enpage.'\' AND dfwiki='.$dfwiki->id;

    				//echo $quer;

    				if (execute_sql($quer,false)){
    					$dfwiki_ead_stat = 'enok';
    				}else{
    					$dfwiki_ead_stat = 'enerror';
    				}
    			}else{
    				//show confirm form
    				$dfwiki_ead_stat = 'enconfirm';
    			}
    		}else{
    			//this page can't be eliminated
    			$dfwiki_ead_stat = 'enerror';
    		}
    	}
    }
	wiki_param ('dfcontent',8);
    wiki_main_setup();
}

function wiki_ead_print_enpage(){
    global $WS, $dfwiki_ead_stat;

    if ($info = wiki_page_last_version ($WS->enpage)) {
    	//calculate new editable value
    	$newedit = ($info->editable+1)%2;
    } else {
    	$newedit = $WS->dfwiki->editable;
    }

    echo '<h1>'.get_string('en'.$newedit.'page','wiki').' '.$WS->enpage.'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';


    switch ($dfwiki_ead_stat){
    	case 'enok':
    		echo '<h2>'.get_string('enok','wiki').' '.$WS->enpage.'</h2><br />';
    		echo '<form method="post" action="view.php?id='.$WS->linkid.'&amp;page='.$WS->enpage.'">
    				<div class="textcenter"><input type="submit" value="'.get_string('continue').'" /></div>
    			</form>';
    		break;
    	case 'enconfirm':

    		echo '<h2>'.get_string('en'.$newedit.'confirm','wiki').' '.stripslashes($WS->updatepage).'?</h2>';
    		echo '<p>'.get_string('en'.$newedit.'message','wiki').'</p>';
    		echo '<table border="0" class="boxaligncenter">
    			<tr>
    				<td>
    					<form method="post" action="view.php?id='.$WS->linkid.'&amp;enpage='.$WS->enpage.'">
    						<div>
    						<input type="hidden" name="dfsetup" value="3" />
    						<input type="submit" name="dfformenconfirm" value="    '.get_string('yes').'    " />
    						</div>
    					</form>
    				</td>
    				<td>
    					<form method="post" action="view.php?id='.$WS->linkid.'">
    						<div>
    						<input type="submit" value="    '.get_string('no').'    " />
    						</div>
    					</form>
    				</td>
    			</tr>
    			</table>';
    		break;
    	case 'enerror':
    		echo '<h2>'.get_string('enerror','wiki').' '.$WS->enpage.'</h2><br />';

    		echo '<form method="post" action="view.php?id='.$WS->linkid.'">
    				<div class="textcenter"><input type="submit" value="'.get_string('continue').'" /></div>
    			</form>';
    		break;
    }

    echo '</div>';
}

function wiki_ead_cleanpage(){
    global $dfwiki_ead_stat;

	$cleanpage = optional_param('cleanpage',NULL,PARAM_FILE);
	$dfwiki = wiki_param('dfwiki');
	
	if (wiki_can_change()) {
		if (wiki_page_exists (false,$cleanpage, false)) {
			$cleanconfirm = optional_param('dfformcleanconfirm',NULL,PARAM_ALPHA);
			if (isset ($cleanconfirm)){
				$cleanvers = optional_param('dfformcleanvers',NULL,PARAM_INT);
				if(delete_records_select('wiki_pages', 'dfwiki='.$dfwiki->id.' and pagename=\''
				   .addslashes($cleanpage).'\' and version < '.$cleanvers)){
					$dfwiki_ead_stat = 'cleanok';
				}else{
					$dfwiki_ead_stat = 'cleanerror';
				}
			}else{
				//show confirm form
				$dfwiki_ead_stat = 'cleanconfirm';
			}
		}else{
			//this page can't be eliminated
			$dfwiki_ead_stat = 'cleanerror';
		}
	}
    wiki_param ('dfcontent',9);
	wiki_main_setup();
}

function wiki_ead_print_cleanpage(){
    global $WS,$dfwiki_ead_stat;

    echo '<h1>'.get_string('cleanpage','wiki').'</h1>';

	echo '<div class="box generalbox generalboxcontent boxaligncenter">';

    switch ($dfwiki_ead_stat){
    	case 'cleanok':
    		echo '<h2>'.get_string('cleanok','wiki').' '.stripslashes(optional_param('cleanpage',NULL,PARAM_FILE)).'</h2><br />';
    		echo '<form method="post" action="view.php?id='.$WS->cm->id.'">
    				<div class="textcenter"><input type="submit" value="'.get_string('continue').'" /></div>
    			</form>';
    		break;
    	case 'cleanconfirm':
    		echo '<h2>'.get_string('cleanconfirm','wiki').' '.stripslashes(optional_param('cleanpage',NULL,PARAM_FILE)).'?</h2>';
    		echo '<p>'.get_string('cleanmessage','wiki').'</p>';
    		echo '<form method="post" action="view.php?id='.$WS->cm->id.'&amp;cleanpage='.optional_param('cleanpage',NULL,PARAM_FILE).'">
    				<table border="0" class="boxaligncenter">
    				<tr>
    				<td>
    					'.get_string('howmanyversions','wiki').'
    				</td>
    				<td>
					<input type="hidden" name="dfsetup" value="2" />
					<select name="dfformcleanvers">';


    		//get the versions count
    		$pageinfo = wiki_page_last_version(optional_param('cleanpage',NULL,PARAM_FILE));

    		$ead = wiki_manager_get_instance();

    		$versions = $ead->versions(optional_param('cleanpage',NULL,PARAM_FILE));

    		$ops = array(5,10,20,50,100,150,200,300,500,1000);
    		echo '<option value="'.$pageinfo->version.'" selected="selected">'.get_string('none').'</option>';

    		foreach ($ops as $op){
    			if ($op<$versions){
    				echo '<option value="'.($pageinfo->version - $op).'">'.$op.'</option>';
    			}
    		}

    		echo '			</select>
    				</td></tr>
    				</table>

    				<p class="textcenter">
						<input type="submit" name="dfformcleanconfirm" value="    '.get_string('yes').'    " />
    				</p>
					</form>


    				<form method="post" action="view.php?id='.$WS->cm->id.'">
					<p class="textcenter">
						<input type="submit" value="    '.get_string('no').'    " />
					</p>
					</form>';
    		break;
    	case 'cleanerror':
    		echo '<h2>'.get_string('cleanerror','wiki').' '.stripslashes(optional_param('cleanpage',NULL,PARAM_FILE)).'</h2><br />';

    		echo '<form method="post" action="view.php?id='.$WS->cm->id.'">
    				<div class="textcenter"><input type="submit" value="'.get_string('continue').'" /></div>
    			  </form>';
    		break;
    }

    echo '</div>';
}


?>