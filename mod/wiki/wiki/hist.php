<?php
//This document contains all the special function for the dfwiki historical
//full_wiki is a tricky! it's used only when we
//install and dfwiki is already in the system

	//html functions
	require_once ($CFG->dirroot.'/mod/wiki/weblib.php');

    //grades lib
    require_once ($CFG->dirroot.'/mod/wiki/grades/grades.lib.php');

if (isset($full_wiki) || isset($WS->dfcourse))
    require_once($CFG->dirroot.'/mod/wiki/wiki/diff.php');

//this function print the tabs for view an old version
function wiki_hist_content(&$WS){

    global $CFG;

    $compare_from = optional_param('diff', 0, PARAM_INT);
    if ($compare_from != 0)
    {
        $compare_to   = optional_param('oldid', 0, PARAM_INT);

        if ($compare_from == 1)
            $WS->ver = $compare_from.'/'.($compare_from);
        elseif ($compare_to == 0) 
            $WS->ver = $compare_from.'/'.($compare_from - 1);
        else
            $WS->ver = $compare_to.'/'.($compare_from);
    }

    ///////////////// WIKI EVALUATION EDITION
    wiki_grade_set_edition_grade($WS);

    $tabrows = array();
    $rows  = array();

    //mount tabs
    $tabs = array('diff','newdiff','oldversion');

    //Converts reserved chars for html to prevent chars misreading
    $pagetemp = stripslashes_safe($WS->page);

	// discussions
    if (substr($WS->page,0,strlen('discussion:'))!='discussion:') {
            $eventview = 'view';
            $eventinfo = 'info';
    } else {
            $eventview = 'discussion';
            $eventinfo = 'infodiscussion';
    }
    //info
    $rows[] = new tabobject('return', 'view.php?id='.$WS->linkid.'&amp;page='.$eventview.'/'.urlencode($pagetemp).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,get_string('return','wiki'));
    //view old version
    foreach ($tabs as $tab){
    	$rows[] = new tabobject($tab, 'view.php?id='.$WS->linkid.'&amp;page='.urlencode($tab.'/'.$pagetemp).'&amp;dfcontent=11&amp;ver='.$WS->ver.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,get_string($tab,'wiki'));
    }

    //info with discussions
    $rows[] = new tabobject('info', 'view.php?id='.$WS->linkid.'&amp;page='.$eventinfo.'/'.urlencode($pagetemp).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id,get_string($eventinfo,'wiki'));

    $tabrows[] = $rows;

    ////interface

    print_tabs($tabrows, $WS->pageaction);

    //load pageolddata and pageverdata
    if (isset($WS->ver)){
    	//separe old version from compare version
    	$parts = explode('/',$WS->ver);
    	if (count($parts)==1){

            $WS->pageolddata = get_record_sql('SELECT *	FROM '. $CFG->prefix.'wiki_pages
    				WHERE pagename=\''.addslashes($WS->page).'\' AND dfwiki='.$WS->dfwiki->id.'
                    AND groupid='.$WS->groupmember->groupid.' AND version='.$WS->ver.' AND ownerid='.$WS->member->id);
    		$WS->pageverdata = wiki_page_last_version($WS->page);


    	}else{
            $WS->pageolddata = get_record_sql('SELECT *	FROM '. $CFG->prefix.'wiki_pages
    				WHERE pagename=\''.addslashes($WS->page).'\' AND dfwiki='.$WS->dfwiki->id.'
                    AND groupid='.$WS->groupmember->groupid.' AND version='.$parts[0].' AND ownerid='.$WS->member->id);
            $WS->pageverdata = get_record_sql('SELECT *	FROM '. $CFG->prefix.'wiki_pages
    				WHERE pagename=\''.addslashes($WS->page).'\' AND dfwiki='.$WS->dfwiki->id.'
                    AND groupid='.$WS->groupmember->groupid.' AND version='.$parts[1].' AND ownerid='.$WS->member->id);
    	}
    	wiki_hist_actions($WS);
    }else{
    	print_string('nocontent','wiki');
    }
}

function wiki_hist_actions(&$WS){

    //print page title
    switch ($WS->pageaction){
    	case 'oldversion':

			wiki_table_start();
			wiki_size_text(wiki_hist_move_ver("&lt;",1,'oldversion'));
			wiki_change_column();
			wiki_size_text(get_string('oldversion','wiki').': '.stripslashes_safe($WS->page).' v.'.$WS->pageolddata->version);
			wiki_change_column();
			wiki_size_text(wiki_hist_move_ver("&gt;",-1,'oldversion'));
			wiki_table_end();

   			print_box_start();
    		wiki_hist_old ($WS);
    		break;

    	case 'diff':
    		wiki_table_start();
			wiki_size_text(wiki_hist_move_ver("&lt;",1));
			wiki_change_column();
			wiki_size_text(get_string('oldversion','wiki').': '.stripslashes_safe($WS->page).' v.'.$WS->pageolddata->version);
			wiki_change_column();
			wiki_size_text(wiki_hist_move_ver("&gt;",-1));
			wiki_table_end();

            ///// WIKI EVALUATION EDITION /////
            wiki_grade_print_edition_evaluation_box($WS);

		    print_box_start();

		    $prop = null;
		    $prop->class = "textright";
            //wiki_paragraph(helpbutton('differences', get_string('version'), 'wiki',true,false,'',true), $prop);
    		echo ('<div align="right">'.helpbutton('differences', get_string('version'), 'wiki',true,false,'',true).'</div>');

    		wiki_hist_diff($WS);
    		break;

		case 'newdiff':
			wiki_table_start();
			wiki_size_text(wiki_hist_move_ver("&lt;",1,'newdiff'));
			wiki_change_column();
			wiki_size_text(get_string('oldversion','wiki').': '.stripslashes_safe($WS->page).' v.'.$WS->pageolddata->version);
			wiki_change_column();
			wiki_size_text(wiki_hist_move_ver("&gt;",-1,'newdiff'));
			wiki_table_end();

            ///// WIKI EVALUATION EDITION /////
            wiki_grade_print_edition_evaluation_box($WS);

		    print_box_start();

    		$prop = null;
		    $prop->class = "textright";
    		wiki_paragraph('', $prop);

    		wiki_hist_newdiff($WS);
    		break;
    }

    print_box_end();
}

function wiki_hist_old (&$WS){

    global $USER,$COURSE;

    //convert wiki content to html content
    wiki_print_page_content ($WS->pageolddata->content,$WS);

    wiki_hist_restore ($WS);

	//link lo evaluate_page
	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);
	if(($WS->dfperms['evaluation']=='2' and has_capability('mod/wiki:viewevaluationswiki',$context)) or ($WS->dfperms['evaluation']=='1' && has_capability('mod/wiki:evaluateawiki',$context))){

		$prop = null;
		$prop->href = "view.php?id={$WS->linkid}&amp;page=".urlencode("evaluate_page/$WS->page")."&amp;ver=$WS->ver&amp;gid={$WS->groupmember->groupid}&amp;uid={$WS->member->id}";
		wiki_a(get_string('evaluate_edition','wiki'), $prop);

	}
}

function wiki_hist_diff(&$WS){

    //this point only effects on html editor mode.
    if ($WS->dfwiki->editor=='htmleditor') {
    	$current_text = wiki_hist_convert_paragrafs ($WS->pageverdata->content);
    	$old_text = wiki_hist_convert_paragrafs ($WS->pageolddata->content);

    }else{
    	$current_text = $WS->pageverdata->content;
    	$old_text = $WS->pageolddata->content;
    }

    //separe current lines
    $currentlines = array();
    $lines = explode("\r\n",$current_text);
    //analice every paragraf
    foreach ($lines as $line){
    	//new paragraf
    	if(chop($line)!=''){
    		$currentlines[] = $line;
    	}
    }

    //separe old lines
    $oldlines = array();
    $lines = explode("\r\n",$old_text);
    //analice every paragraf
    foreach ($lines as $line){
    	//new paragraf
    	if(chop($line)!=''){
    		$oldlines[] = $line;
    	}
    }


    $diffs = new WikiDiff($oldlines,$currentlines);


    //parse Diff result:
    $rows = array();
    $edits = $diffs->edits;
    for ($i=0; $i<count($edits);$i++){

    	$rows[] = wiki_hist_diff_parse ($edits[$i]);
    }

    //print version list
    wiki_hist_diff_print_versions();

    //print table
    $table->head = array (get_string('oldversion','wiki').' '.$WS->pageolddata->version ,
    						'',get_string('version').' '.$WS->pageverdata->version);
    $table->width = '100%';
    $table->data = array();

    foreach ($rows as $row){
    	for ($i=0;$i<count($row[0]);$i++){
    		$table->data[] = array($row[0][$i],$row[1][$i],$row[2][$i]);
    	}
    }

    print_table($table);

    wiki_hist_restore ($WS);
}

function wiki_hist_newdiff(&$WS){

	global $CFG;

	require_once($CFG->dirroot.'/mod/wiki/inline-diff/inline_function.php');

	//this point only effects on html editor mode.
	if ($WS->dfwiki->editor=='htmleditor') {
		$current_text = wiki_hist_convert_paragrafs ($WS->pageverdata->content);
		$old_text = wiki_hist_convert_paragrafs ($WS->pageolddata->content);

	}else{
		$current_text = $WS->pageverdata->content;
		$old_text = $WS->pageolddata->content;
	}

	//print version list
	wiki_hist_diff_print_versions("newdiff");


	//$diff= inline_diff($current_text,$old_text,$nl);
	$diff= inline_diff($current_text,$old_text);

	wiki_hist_restore ($WS);
}

function wiki_hist_diff_parse (&$diff){

    //get colors and symbol
    $selections = array (
    				'copy' => array ('#CCCCCC','='),
    				'add' => array ('#CCFFCC','+'),
    				'delete' => array ('#FFCCCC','X'),
    				'change' => array ('#CCCCFF','-'),
    			);
    $currentsel = $selections[$diff->type];

    $nrows = max(count($diff->orig),count($diff->closing));
    $row = array();

    for ($i=0;$i<$nrows;$i++){

	   	$validstruct = array ('<div style="background-color:'.$currentsel[0].';">',
    						'</div>');

    	$emptystruct = array ('<div class="textcenter">',
    						'</div>');

    	//old version
    	if (isset($diff->orig[$i])){
    		$row[0][] = $validstruct[0].htmlspecialchars($diff->orig[$i]).$validstruct[1];
    	}else{
    		$row[0][] = $emptystruct[0].$emptystruct[1];
    	}

    	//symbol
    	$row[1][]= $emptystruct[0].$currentsel[1].$emptystruct[1];

    	//current version
    	if (isset($diff->closing[$i])){
    		$row[2][] = $validstruct[0].htmlspecialchars($diff->closing[$i]).$validstruct[1];
    	}else{
    		$row[2][] = $emptystruct[0].$emptystruct[1];
    	}

    }

    return $row;

}

function wiki_hist_diff_print_versions($diff="diff"){

    global $WS;


	//if ($diff=="newdiff"){
		$vers->current = $WS->pageolddata->version;
		$vers->old = $WS->pageverdata->version;
		wiki_size_text(get_string('comparingwith','wiki',$vers),2);
	//}
    $prev = $WS->pageolddata->version-1;
    $next = $WS->pageolddata->version+1;

    $specialvers = array();

 	$prop = null;
	$prop->href = 'view.php?id='.$WS->linkid.'&amp;page='.$diff.'/'.urlencode($WS->pageolddata->pagename).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;ver='.$WS->pageolddata->version.'/1&amp;dfcontent=11';
    if ($WS->pageolddata->version != 1)
        $out = wiki_a(get_string('initial','wiki'),$prop,true);
    else
        $out = '<b>1</b>';
	$specialvers[1] = $out;

	$prop = null;
	$prop->href = 'view.php?id='.$WS->linkid.'&amp;page='.$diff.'/'.urlencode($WS->pageolddata->pagename).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;ver='.$WS->pageolddata->version.'/'.$next.'&amp;dfcontent=11';
	$out = wiki_a(get_string('next'),$prop,true);
	$specialvers[$next] = $out;

	$prop = null;
	$prop->href = 'view.php?id='.$WS->linkid.'&amp;page='.$diff.'/'.urlencode($WS->pageolddata->pagename).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;ver='.$WS->pageolddata->version.'/'.$prev.'&amp;dfcontent=11';
	$out = wiki_a(get_string('previous'),$prop,true);
	$specialvers[$prev] = $out;

	$prop = null;
	$prop->href = 'view.php?id='.$WS->linkid.'&amp;page='.$diff.'/'.urlencode($WS->pageolddata->pagename).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;ver='.$WS->pageolddata->version.'&amp;dfcontent=11';
	$out = wiki_a(get_string('current','wiki'),$prop,true);
	$specialvers[$WS->pagedata->version] = $out;

    print_simple_box_start( 'center', '100%', '', '20');

    //get how many versions have the page
    $ead = wiki_manager_get_instance();
    $vers = $ead->get_wiki_page_versions($WS->pageolddata->dfwiki);
    if (!$vers) $vers = array();
    $vers = count($vers);

    echo get_string ('comparewith','wiki').': ';
	//wiki_table_start();
	$j=false;
	for ($i=$WS->pagedata->version;$i>($WS->pagedata->version-$vers) && $i > 0;$i--){
        /*
		 *if($j){
		 *    wiki_change_column();
		 *}else {
		 *    $j=true;
		 *}
         */

    	//put in bold the compared version
    	if ($i==$WS->pageverdata->version){
    		$bolds = array ('<b>','</b>');
    	}else{
    		$bolds = array ('','');
    	}

		//print the version link
    	if (isset($specialvers[$i])){
			echo $bolds[0].$specialvers[$i].$bolds[1];
    	}else{
	   		$prop = null;

            if ($i != $WS->pageolddata->version) {
                $prop->href = 'view.php?id='.$WS->linkid.'&amp;page='.$diff.'/'.urlencode($WS->pageolddata->pagename).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;ver='.$WS->pageolddata->version.'/'.$i.'&amp;dfcontent=11';
                wiki_a($bolds[0].$i.$bolds[1],$prop);
            } else
                echo ('<b>'.$i.'</b> ');
    	}

		if ($i!=($WS->pagedata->version-$vers)+1 &&
            $i > 1) {
			//wiki_change_column();
			echo '- ';
		}
    }
    wiki_table_end();
    print_simple_box_end();
    wiki_br();
}

//this function prints restore form for an old version
function wiki_hist_restore (&$WS){

    if ($WS->dfperms['restore']){

    	$prop = null;
    	$prop->id = "form";
    	$prop->method = "post";
    	$prop->action =  'view.php?id='.$WS->linkid.'&amp;page=view/'.urlencode($WS->pageolddata->pagename).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id;
		wiki_form_start($prop);
		wiki_div_start();

		$prop = null;
		$prop->name = "dfformversion";
		$prop->value = $WS->pagedata->version;
		wiki_input_hidden($prop);

		$prop = null;
		$prop->name = "dfformcreated";
		$prop->value = $WS->pageolddata->created;
		wiki_input_hidden($prop);

		$prop = null;
		$prop->name = "dfformeditable";
		$prop->value = $WS->pageolddata->editable;
		wiki_input_hidden($prop);

		$prop = null;
		$prop->name = "dfformeditor";
		$prop->value = $WS->pageolddata->editor;
		wiki_input_hidden($prop);

		$prop = null;
		$prop->name = "dfformaction";
		$prop->value = "edit";
		wiki_input_hidden($prop);

		$prop = null;
		$prop->name = "dfformcontent";
		$prop->value = htmlspecialchars($WS->pageolddata->content);
		wiki_input_hidden($prop);

		$prop = null;
		$prop->name = "dfformsave";
		$prop->value = get_string('restore','wiki');
		wiki_input_submit($prop);

		wiki_div_end();
		wiki_form_end();

    }
}

//this function returns the text converting </p> into corry returns
function wiki_hist_convert_paragrafs(&$text){
    return str_replace ('</p>',"\r\n", $text);
}

//this function prints the '<' and '>' links for moving old version.
function wiki_hist_move_ver($symbol,$num,$diff='diff'){

    global $WS;

    //this is the destiny version
    $dest = $WS->pageolddata->version + $num;

    //get first and last version
    $last = $WS->pagedata->version;
    $ead = wiki_manager_get_instance();
    $vs = $ead->get_wiki_page_versions($WS->pageolddata->pagename);
    if (!$vs) $vs = array();
    $first = $last - count($vs);

    //get compare version
    $comp = $WS->pageverdata->version+$num;
    if(($comp>$last) || ($comp<=$first)){
    	$comp = $WS->pageverdata->version;
    }

    //return symbol if it's printable
    if(($dest<=$last) && ($dest>$first)){
		return '<a href="view.php?id='.$WS->linkid.'&amp;page='.$diff.'/'.urlencode($WS->pageolddata->pagename).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;ver='.$dest.'/'.$comp.'&amp;dfcontent=11">'.$symbol.'</a>';
    }
   	return '';
}

?>
