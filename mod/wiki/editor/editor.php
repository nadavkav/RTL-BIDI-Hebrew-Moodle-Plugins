<?php
//$Id: editor.php,v 1.23 2008/05/22 11:31:52 gonzaloserrano Exp $

function wiki_print_editor(&$content_text,$adddiscussion='0',$addtitle='',$oldcontent='',&$WS){
    global $CFG,$USER;
    //show upload error @@@@@@@@

    //try to delete a file
    wiki_print_editor_setup($WS);

	require_once($CFG->dirroot."/mod/wiki/weblib.php");
	require_once($CFG->dirroot."/mod/wiki/locallib.php");

    //Scritp WIKI_TREE
	$prop = null;
	$prop->type = 'text/javascript';
	if (isset($WS->dfcourse)){
    	$prop->src = '../mod/wiki/editor/wiki_tree.js';
    }
    else{
        $prop->src = 'editor/wiki_tree.js';
    }
	wiki_script('', $prop);

    if (isset ($WS->dfdir->error)){
    	foreach ($WS->dfdir->error as $err){
    		$prop = null;
    		$prop->class = 'except';
    		wiki_size_text($err,3,$prop);
    	}
    }

	if (substr($WS->pagedata->pagename,0,strlen('discussion:')) != 'discussion:') {
		$event = 'view';
	} else {
		$event = 'discussion';
	}

    //mount editing form
    //Converts reserved chars for html to prevent chars misreading
    $pagetemp = stripslashes_safe($WS->pagedata->pagename);
    $pagetemp = wiki_clean_name($pagetemp);

	$prop = null;
    $prop->action = 'view.php?id='.$WS->linkid.'&amp;page='.$event.'/'.urlencode($pagetemp).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id;
	$prop->method = 'post';
	$prop->enctype = 'multipart/form-data';
	$prop->id = 'prim';
	wiki_form_start($prop);

	wiki_table_start();

		$prop = null;
		$prop->name = 'dfformversion';
    	$prop->value = ($WS->pagedata->version)? $WS->pagedata->version:'0';
		wiki_input_hidden($prop);

		$prop = null;
		$prop->name = 'dfformcreated';
    	$prop->value = $WS->pagedata->created;
		wiki_input_hidden($prop);

		$prop = null;
		$prop->name = 'dfformeditable';
    	$prop->value = $WS->pagedata->editable;
		wiki_input_hidden($prop);

		$prop = null;
		if ($adddiscussion=='1') {
            $prop->value = ('adddiscussion');
        } else {
            $prop->value = ('edit');
        }
        $prop->name = 'dfformaction';
        wiki_input_hidden($prop);

        $prop = null;
        $prop->name = 'dfformeditor';
          $prop->value = $WS->pagedata->editor;
        wiki_input_hidden($prop);

        /* necessary for joining the section with the rest of the page */
        // section name
        $section = optional_param('section', '', PARAM_TEXT);
        $prop = null;
        $prop->name  = 'section';

        $preview  = optional_param('dfformpreview', '', PARAM_TEXT);
        if (($preview != '') || wiki_is_uploading_modified())
        {   // if come from a preview, upload file or delete file
            // the string is already unslashed and encoded
            $prop->value = $section;
        } else // gotta strip the slashes and encode it
            $prop->value = urlencode(stripslashes($section));

        wiki_input_hidden($prop);

        // section number
        $prop = null;
        $prop->name  = 'sectionnum';
        $prop->value = optional_param('sectionnum', '', PARAM_INTEGER);
        wiki_input_hidden($prop);

        $prop = null;
        $prop->name  = 'sectionhash';
        $prop->value = optional_param('sectionhash', '', PARAM_TEXT);
        wiki_input_hidden($prop);

        // discussion
        if ($adddiscussion=='1') {
              wiki_size_text(get_string('adddiscussionitem','wiki'),2);
              print_string('title','wiki');
              wiki_br();

              $prop = null;
              $prop->name = 'dfformaddtitle';
              $prop->value = $addtitle;
              $prop->size = '80';
              wiki_input_text($prop);

              wiki_br(2);

              $prop = null;
              $prop->name = 'dfformoldcontent';
              $prop->value = $oldcontent;
              wiki_input_hidden($prop);

              wiki_hr();
              wiki_change_row();
              date_default_timezone_set('UTC'); // add editing username and date to the end of each discussion (nadavkav patch)
            $txt = $content_text.'<br/><br/>  <i>('.get_string('discussioneditby','wiki').': '.$USER->firstname." ".$USER->lastname." ".get_string('discussionediton','wiki').": ".date(DATE_RFC822).")</i>";
        } else {
            $txt = $content_text;
        }

        // wikibook
        $prop = null;
        $prop->name  = 'wikibook';
        $prop->value = optional_param('wikibook', '', PARAM_TEXT);
        wiki_input_hidden($prop);

        wiki_print_edit_area ($txt,$WS);

        wiki_tags_print_editbox($WS);

        $prop = null;
        $prop->class = 'nwikileftnow';
        wiki_change_row($prop);

          if ($WS->upload_bar) wiki_print_edit_uploaded($WS);

        wiki_change_row();

          $prop = null;
          $prop->name = 'dfformsave';
          $prop->value = get_string('save','wiki');
          wiki_input_submit($prop);

          $prop = null;
          $prop->name = 'dfformpreview';
          $prop->value = get_string('preview');
          wiki_input_submit($prop);

          $prop = null;
          $prop->name = 'dfformcancel';
          $prop->value = get_string('cancel');
          wiki_input_submit($prop);

          $prop = null;
          $prop->name = 'dfformdefinitive';
          $prop->value = '1';
          $prop->id = 'checkbox';
          wiki_input_checkbox($prop);

          print_string('highlight','wiki');

        wiki_table_end();

        wiki_form_end();
}


//this function prints de text area editor for dfwiki
function wiki_print_edit_area (&$content_text,&$WS){
    global $USER,$dfwiki_editor;
    //select editor:
    switch ($WS->pagedata->editor){
        case 'htmleditor': //moodle html editor

          $prop = null;
        $prop->border = 0;
        $prop->width = '100%';
        $prop->aligntd = 'right';
        wiki_table_start($prop);

          helpbutton('howtowiki_htmleditor', get_string('helpeditor', 'wiki'), 'wiki');

          wiki_change_row();

            print_textarea(true, 20, 70, 0, 0, 'dfformcontent', $content_text);
            use_html_editor('dfformcontent');

          wiki_table_end();

          break;

    	default: //default and dfwiki editor
    		//print buttons

    		wiki_table_start();

    			$prop = null;
				$prop->width = '100%';
				$prop->valigntd = 'top';
				$prop->classtd = 'nwikitambuttons';
				wiki_table_start($prop);

					$prop = null;
					$prop->type = 'text/javascript';
					$prop->src = 'editor/buttons.js';
					wiki_script('', $prop);

					$prop = null;
					$prop->type = 'text/javascript';
					$prop->src = '../mod/wiki/editor/buttons.js';
					wiki_script('', $prop);

					$info = "";
					if (isset($dfwiki_editor)){
						foreach ($dfwiki_editor as $button){
							$text = ($button[4]!='')? get_string($button[4],'wiki') : '';
                            if (isset($WS->dfcourse)){
                            	$info .= 'addButton(\'../mod/wiki/editor/images/'.$button[0].'\',\''.get_string($button[1],'wiki').'\',\''.$button[2].'\',\''.$button[3].'\',\''.$text."');\n";
                            }
                            else{
                                $info .= 'addButton(\'editor/images/'.$button[0].'\',\''.get_string($button[1],'wiki').'\',\''.$button[2].'\',\''.$button[3].'\',\''.$text."');\n";
                            }
						}
					}

					$prop = null;
					$prop->type = 'text/javascript';
					wiki_script($info, $prop);

				$prop = null;
				$prop->valign = 'top';
				wiki_change_column($prop);

    				wiki_print_edit_smileis($WS);

    			$prop = null;
				$prop->align = 'right';
				$prop->valign = 'top';
				wiki_change_column($prop);

					helpbutton('howtowiki_'.$WS->pagedata->editor, get_string('helpeditor', 'wiki'), 'wiki');

    			wiki_table_end();

    		wiki_change_row();

    			$info = '
					var cont=0;
					function onKeyPress(e)
					{
						cont++;
						if(cont==200){
							<!-- 	No es pot accedir al checkbox a traves de document.form.dfform[definitive] -->
							document.getElementById("checkbox").click()
						}
					}';
				$prop = null;
				$prop->type = 'text/javascript';
				wiki_script($info, $prop);

    			$prop = null;
				$prop->cols = $WS->editor_size->editorcols;
				$prop->rows = $WS->editor_size->editorrows;
				$prop->name = 'dfformcontent';
				$prop->events = 'onkeypress="return onKeyPress(event);"';
				wiki_textarea(s($content_text), $prop);

	    	wiki_table_end();

    		break;
    }
}


//this function prints de server uploaded content.
function wiki_print_edit_uploaded(&$WS){
    global $CFG;
    //initiates images array

	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);

	$pl->src = $CFG->wwwroot.'/mod/wiki'.'/images/plus.gif';
	if (isset($WS->dfcourse)) {
   		$pl->class = 'wiki_folding_co';
    }
    else {
    	$pl->class = 'wiki_folding';
    }

	$mi->src = $CFG->wwwroot.'/mod/wiki'.'/images/minus.gif';
    if (isset($WS->dfcourse)) {
   		$mi->class = 'wiki_folding_co';
    }
    else {
    	$mi->class = 'wiki_folding';
    }

	$sq->src = $CFG->wwwroot.'/mod/wiki'.'/images/square.gif';

    $images = array(
		'plus' => wiki_img($pl, true),
    	'minus' => wiki_img($mi, true),
    	'square' => wiki_img($sq, true));

    //get www path:
    if(has_capability('mod/wiki:uploadfiles', $context) || $WS->dfperms['attach']){

		$prop = null;
		$prop->width = '100%';
		$prop->valigntd = 'top';
		$prop->classtd = 'nwikileftnow';
		wiki_table_start($prop);

			if ($WS->dfperms['attach']) {
    			print_string('insertfile','wiki');
    			$prop = null;
				$prop->valign = 'top';
				$prop->class = 'nwikileftnow';
				wiki_change_column($prop);
    		}

    		if ($WS->dfperms['attach']){
    			if (count($WS->dfdir->content)!=0){

					$prop = null;
					$prop->class = 'wiki_listme';
					$prop->classli = 'wiki_listme';
					wiki_start_ul($prop);

					echo $images['plus'];

					$prop = null;
				    $prop->href = '#';
				    if (isset($WS->dfcourse)) {
				   		$prop->class = 'wiki_folding_co';
				    }
				    else {
				    	$prop->class = 'wiki_folding';
				    }
				    $button = wiki_a(get_string('uploaded','wiki'), $prop);

					$numm = 0;
					//generate tree content.
					foreach ($WS->dfdir->content as $file){

						if($numm == 0){
							$prop = null;
							$prop->class = 'wiki_listme';
							$prop->style = 'margin:auto auto auto 15px;display:none';
							$prop->classli = 'wiki_listme';
							wiki_start_ul($prop);

							$numm++;
						}
						else{
							$prop = null;
							$prop->class = 'wiki_listme';
							wiki_change_li($prop);
						}

						//image url: http://147.83.59.184/moodle15/file.php/#courseid/
						$url = $WS->dfdir->www.'/'.$file;
						$url = substr($url,strlen($CFG->wwwroot));
						echo $images['square'];

					    $prop = null;
					    $prop->href = 'javascript:insertTags(\'[[attach:'.$file.']]\',\'\',\'\');';
					    $button = wiki_a($file, $prop);

						$prop = null;
						$prop->src = $CFG->wwwroot.'/mod/wiki/editor/images/file_view.gif';
						link_to_popup_window ($url, get_string('view'), wiki_img($prop, true), $height=400, $width=500, get_string('view'));

						if (has_capability('mod/wiki:deletefiles',$context)){
							$prop = null;
							$prop->name = 'dfformdelfile';
							$prop->value = $file;
							$prop->src = $CFG->wwwroot.'/mod/wiki/images/delete.gif';
							wiki_input_image($prop);
						}

					}
					wiki_end_ul();

				wiki_end_ul();
    			}
    			else{
    				//no files where uploaded
    				print_string('nofiles','wiki');
    			}
    		}
    		//print upload form
			$prop = null;
			$prop->valign = 'top';
			$prop->class = 'nwikiuptam';
			wiki_change_column($prop);

			// show upload files if enabled in wiki settings (students can upload) (nadavkav)
    		if (has_capability('mod/wiki:uploadfiles', $context) and $WS->dfwiki->attach == '1'){
    			$prop = null;
				$prop->name = 'MAX_FILE_SIZE';
    			$prop->value = get_max_upload_file_size();
				wiki_input_hidden($prop);

    			$prop = null;
				$prop->name = 'dfformfile';
    			$prop->size = '20';
				wiki_input_file($prop);

				$prop = null;
				$prop->name = 'dfformupload';
				$prop->value = get_string('upload');
				wiki_input_submit($prop);
    		}
    	//print help
		$prop = null;
		$prop->valign = 'top';
		$prop->class = 'nwikileftnow';
		wiki_change_column($prop);

    		helpbutton('attach', get_string('help'), 'wiki');

		wiki_table_end();

		wiki_hr();
    }
}

function wiki_print_view_uploaded(&$WS){
    global $CFG;
    //initiates images array

	$context = get_context_instance(CONTEXT_MODULE,$WS->cm->id);

	$pl->src = $CFG->wwwroot.'/mod/wiki'.'/images/plus.gif';
	if (isset($WS->dfcourse)) {
   		$pl->class = 'wiki_folding_co';
    }
    else {
    	$pl->class = 'wiki_folding';
    }

	$mi->src = $CFG->wwwroot.'/mod/wiki'.'/images/minus.gif';
    if (isset($WS->dfcourse)) {
   		$mi->class = 'wiki_folding_co';
    }
    else {
    	$mi->class = 'wiki_folding';
    }

	$sq->src = $CFG->wwwroot.'/mod/wiki'.'/images/weather-clear.png';

    $images = array(
		'plus' => wiki_img($pl, true),
    	'minus' => wiki_img($mi, true),
    	'square' => wiki_img($sq, true));

    //get www path:
    if(has_capability('mod/wiki:uploadfiles', $context) || $WS->dfperms['attach']){

		$prop = null;
		$prop->width = '30%';
		$prop->valigntd = 'top';
		$prop->classtd = 'nwikileftnow';
		echo '<hr>';
		wiki_table_start($prop);

			if ($WS->dfperms['attach']) {
    			print_string('insertedfiles','wiki');
    			$prop = null;
				$prop->valign = 'top';
				$prop->class = 'nwikileftnow';
				wiki_change_column($prop);
    		}

    		if ($WS->dfperms['attach']){
    			if (count($WS->dfdir->content)!=0){

					$prop = null;
					$prop->class = 'wiki_listme';
					$prop->classli = 'wiki_listme';
					wiki_start_ul($prop);

					echo $images['plus'];

					$prop = null;
				    $prop->href = '#';
				    if (isset($WS->dfcourse)) {
				   		$prop->class = 'wiki_folding_co';
				    }
				    else {
				    	$prop->class = 'wiki_folding';
				    }
				    $button = wiki_a(get_string('uploaded','wiki'), $prop);

					$numm = 0;
					//generate tree content.
					foreach ($WS->dfdir->content as $file){

						if($numm == 0){
							$prop = null;
							$prop->class = 'wiki_listme';
							$prop->style = 'margin:auto auto auto 15px;display:none';
							$prop->classli = 'wiki_listme';
							wiki_start_ul($prop);

							$numm++;
						}
						else{
							$prop = null;
							$prop->class = 'wiki_listme';
							wiki_change_li($prop);
						}

						//image url: http://147.83.59.184/moodle15/file.php/#courseid/
						$url = $WS->dfdir->www.'/'.$file;
						$url = substr($url,strlen($CFG->wwwroot));
						echo $images['square'];

					    $prop = null;
					    $prop->href = 'javascript:insertTags(\'[[attach:'.$file.']]\',\'\',\'\');';
					    $button = wiki_a($file, $prop);

						$prop = null;
						$prop->src = $CFG->wwwroot.'/mod/wiki/editor/images/file_view.gif';
						link_to_popup_window ($url, get_string('view'), wiki_img($prop, true), $height=400, $width=500, get_string('view'));

						if (has_capability('mod/wiki:deletefiles',$context)){
							$prop = null;
							$prop->name = 'dfformdelfile';
							$prop->value = $file;
							$prop->src = $CFG->wwwroot.'/mod/wiki/images/delete.gif';
							wiki_input_image($prop);
						}

					}
					wiki_end_ul();

				wiki_end_ul();
    			}
    			else{
    				//no files where uploaded
    				print_string('nofiles','wiki');
    			}
    		}
//     		//print upload form
// 			$prop = null;
// 			$prop->valign = 'top';
// 			$prop->class = 'nwikiuptam';
// 			wiki_change_column($prop);
//
//     		if (has_capability('mod/wiki:uploadfiles', $context)){
//     			$prop = null;
// 				$prop->name = 'MAX_FILE_SIZE';
//     			$prop->value = get_max_upload_file_size();
// 				wiki_input_hidden($prop);
//
//     			$prop = null;
// 				$prop->name = 'dfformfile';
//     			$prop->size = '20';
// 				wiki_input_file($prop);
//
// 				$prop = null;
// 				$prop->name = 'dfformupload';
// 				$prop->value = get_string('upload');
// 				wiki_input_submit($prop);
//     		}
//     	//print help
// 		$prop = null;
// 		$prop->valign = 'top';
// 		$prop->class = 'nwikileftnow';
// 		wiki_change_column($prop);
//
//     		helpbutton('attach', get_string('help'), 'wiki');

		wiki_table_end();

		wiki_hr();
    }
}

//this function prints the smileis bar
function wiki_print_edit_smileis(&$WS){
    global $CFG;

    $smileis = array (':-)',':-D',';-)',':-/','V-.',':-P',
    				'B-)','^-)','8-)','8-o',':-(','8-.',':-I',
    				':-X',':o)','P-|','8-[','xx-P','|-.','}-]');

    //import javascript

    $prop = null;
    $prop->href = '#';
    if (isset($WS->dfcourse)) {
   		$prop->class = 'wiki_folding_co';
    }
    else {
    	$prop->class = 'wiki_folding';
    }
    $button = wiki_a('', $prop, true);

	$prop = null;
	$prop->class = 'wiki_listme';
	$prop->classli = 'wiki_listme';
	wiki_start_ul($prop);

	  	$prop = null;
		$prop->src = $CFG->wwwroot.'/mod/wiki/editor/images/ed_smiley1.gif';
	    if (isset($WS->dfcourse)) {
	   		$prop->class = 'icsme_co';
	    }
	    else {
	    	$prop->class = 'icsme';
	    }
		wiki_img($prop);

		echo $button;

		$prop = null;
		$prop->class = 'wiki_listme';
		$prop->style = 'margin:auto auto auto 5px;display:none';
		$prop->classli = 'wiki_listme';
		wiki_start_ul($prop);

		    //generate tree content.
		    $put=1;
		    foreach ($smileis as $smiley){
		    	//image url: http://147.83.59.184/moodle15/file.php/#courseid/
		    	$img = $smiley;
		    	replace_smilies($img);

		    	$prop = null;
		    	$prop->href = 'javascript:insertTags(\''.$smiley.'\',\'\',\'\')';
		    	wiki_a($img, $prop);

		    	//only 10 emoticons per line
		    	if ($put>4) {
		    		wiki_br();
		    		$put = 0;
		    	}
		    	$put++;
		    }

			wiki_end_ul();

	wiki_end_ul();
}


//this function redefines the configuration array
function wiki_print_editor_setup(&$WS){
    global $dfwiki_editor,$USER;
    switch ($WS->pagedata->editor){
    	case 'dfwiki':
    		$dfwiki_editor['bold'] = array('ed_bold.gif','bolttext','\\\'\\\'\\\'','\\\'\\\'\\\'','bolttext');
    		$dfwiki_editor['italic'] = array('ed_italic.gif','italictext','\\\'\\\'','\\\'\\\'','italictext');
    		$dfwiki_editor['internal'] = array('ed_internal.gif','internaltext','[[',']]','internalurl');
    		$dfwiki_editor['external'] = array('ed_external.gif','externaltext','[',']','externalurl');
    		$dfwiki_editor['h1'] = array('ed_h1.gif','h1text','\\n= ',' =\\n','h1text');
    		$dfwiki_editor['h2'] = array('ed_h2.gif','h2text','\\n== ',' ==\\n','h2text');
    		$dfwiki_editor['h3'] = array('ed_h3.gif','h3text','\\n=== ',' ===\\n','h3text');
    		$dfwiki_editor['hr'] = array('ed_hr.gif','hrtext','\\n----\\n','','');
    		$dfwiki_editor['nowiki'] = array('ed_nowiki.gif','nowikitext','&lt;nowiki&gt;','&lt;/nowiki&gt;','nowikitext');
    		$dfwiki_editor['stamp'] = array('ed_stamp.gif','personalstamp','[[user:'.$USER->username.']]','','');

    		$WS->upload_bar = true;
    		break;

    	case 'nwiki':
    		$dfwiki_editor['bold'] = array('ed_bold.gif','bolttext','\\\'\\\'\\\'','\\\'\\\'\\\'','bolttext');
    		$dfwiki_editor['italic'] = array('ed_italic.gif','italictext','\\\'\\\'','\\\'\\\'','italictext');
    		$dfwiki_editor['internal'] = array('ed_internal.gif','internaltext','[[',']]','internalurl');
    		$dfwiki_editor['external'] = array('ed_external.gif','externaltext','[',']','externalurl');
    		$dfwiki_editor['h1'] = array('ed_h1.gif','h1text','\\n= ',' =\\n','h1text');
    		$dfwiki_editor['h2'] = array('ed_h2.gif','h2text','\\n== ',' ==\\n','h2text');
    		$dfwiki_editor['h3'] = array('ed_h3.gif','h3text','\\n=== ',' ===\\n','h3text');
    		$dfwiki_editor['hr'] = array('ed_hr.gif','hrtext','\\n----\\n','','');
    		$dfwiki_editor['nowiki'] = array('ed_nowiki.gif','nowikitext','&lt;nowiki&gt;','&lt;/nowiki&gt;','nowikitext');
    		$dfwiki_editor['stamp'] = array('ed_stamp.gif','personalstamp','~~~','','');
//    		$dfwiki_editor['stamp'] = array('ed_stamp.gif','personalstamp','[[user:'.$USER->username.']]','','');

    		$WS->upload_bar = true;
    		break;

    	case 'ewiki':
            $dfwiki_editor['bold'] = array('ed_bold.gif','bolttext','\\\'\\\'\\\'','\\\'\\\'\\\'','bolttext');
    		$dfwiki_editor['italic'] = array('ed_italic.gif','italictext','\\\'\\\'','\\\'\\\'','italictext');
    		$dfwiki_editor['internal'] = array('ed_internal.gif','internaltext','[[',']]','internalurl');
    		$dfwiki_editor['external'] = array('ed_external.gif','externaltext','[',']','externalurl');
    		$dfwiki_editor['h1'] = array('ed_h1.gif','h1text','\\n!!! ','\\n','h1text');
    		$dfwiki_editor['h2'] = array('ed_h2.gif','h2text','\\n!! ','\\n','h2text');
    		$dfwiki_editor['h3'] = array('ed_h3.gif','h3text','\\n! ','\\n','h3text');
    		$dfwiki_editor['hr'] = array('ed_hr.gif','hrtext','\\n----\\n','','');
    		$dfwiki_editor['nowiki'] = array('ed_nowiki.gif','nowikitext','&lt;nowiki&gt;','&lt;/nowiki&gt;','nowikitext');
    		$dfwiki_editor['stamp'] = array('ed_stamp.gif','personalstamp','[[user:'.$USER->username.']]','','');

    		$WS->upload_bar = true;
    		break;
    	default:
			$WS->upload_bar = true; // make upload files available to all editors (nadavkav)

    		break;
    }
}

?>
