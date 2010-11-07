<?php
// $Id: exportxmllib.php,v 1.21 2008/01/23 11:15:16 pigui Exp $
//Created by Antonio Castaño & Juan Castaño
//last modified by Manu Carrasco

require_once ("$CFG->dirroot/lib/moodlelib.php");
require_once ("$CFG->dirroot/backup/lib.php");
require_once ("$CFG->dirroot/backup/backuplib.php");
require_once ($CFG->libdir.'/filelib.php');
//sintax contains the wikiparser
require_once ("$CFG->dirroot/mod/wiki/wiki/sintax.php");
//hist contains all historical functionalities
require_once ("$CFG->dirroot/mod/wiki/wiki/hist.php");
//dfwiki editor functions
require_once ("$CFG->dirroot/mod/wiki/editor/editor.php");
//uploaded files functions
require_once ("$CFG->dirroot/mod/wiki/upload/uploadlib.php");
//classxml contains xml classes
require_once ("$CFG->dirroot/mod/wiki/xml/classxml.php");
require_once ("$CFG->dirroot/lib/xmlize.php");

require_once ($CFG->libdir.'/ddllib.php');



global $WS;


$WS->cm = optional_param('cm',NULL,PARAM_FILE);
$contents = optional_param('contents',NULL,PARAM_RAW);
wiki_dfform_param($WS);
$WS->nocontents = optional_param('nocontents',NULL,PARAM_FILE);
$WS->pageaction = optional_param('pageaction',NULL,PARAM_ALPHA);
$WS->page = optional_param('pagename',NULL,PARAM_FILE);


//global variables
global $CFG;

//Adjust some php variables to the execution of this script
@ini_set("max_execution_time","300");
raise_memory_limit("memory_limit","128M");

//this function create export tab content
function wiki_export_content(&$WS, $folder='exportedfiles'){
	global $CFG, $contents;

	$export = optional_param('dfformexport',NULL,PARAM_ALPHA);
	$exportall = optional_param('dfformexportall',NULL,PARAM_ALPHA);
    //check if the form was filled in
	if (isset($export) || isset($exportall)){
        //make sure the file doesn't exist
        $dfformname = optional_param('dfformname',NULL,PARAM_FILE);
        $currentname = clean_filename($dfformname);
        $cleandfwiki = clean_filename($WS->dfwiki->name);
        if (file_exists($CFG->dataroot."/".$WS->dfwiki->course."/".$folder."/".$cleandfwiki.$WS->cm->id."/".$currentname)) {
            $err = $currentname.' already exists!';
            error($err);
        } else {
            //export to xml
            wiki_export_content_XML($WS);
            $prop = null;
            $prop->class = 'textcenter';
            $info = wiki_size_text(get_string("exportcorrectly",'wiki'),2, '', true);
            wiki_div($info,$prop);

            //make the zip
            $files = array();

            //copy files inside temp where is the xml
            check_dir_exists("$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}",true);
            check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}",true);
            $flist = list_directories_and_files ("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}");
            if($flist != null){
                foreach ($flist as $fil) {
                    $from_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil";
                    $to_file = "$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}/$fil";
                    copy($from_file,$to_file);
                }
            }

            //continue compressing
            $filelist = list_directories_and_files ("$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}");
            foreach ($filelist as $file) {
                $files[] = "$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}/$file";
            }

            check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/".$folder,true);
            check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/".$folder."/$cleandfwiki{$WS->cm->id}",true);
            $destination = "$CFG->dataroot/{$WS->dfwiki->course}/".$folder."/$cleandfwiki{$WS->cm->id}/$currentname";
            $status = zip_files($files, $destination);

            //delete folder inside temp that we've created
            $filelist2 = list_directories_and_files ("$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}");
            foreach ($filelist2 as $file2) {
                unlink("$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}/$file2");
            }
            rmdir("$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}");

			$prop = null;
			$prop->border = '0';
			$prop->class = 'boxaligncenter';
			$prop->classtd = 'nwikileftnow';
			wiki_table_start($prop);

                $wdir = '/'.$folder.'/'.$cleandfwiki.$WS->cm->id;
                $fileurl = "$wdir/$currentname";
                $ffurl = "/file.php?file=/{$WS->cm->course}$fileurl";
                $icon = mimeinfo("icon", $currentname);

                $prop = null;
                $prop->src = "$CFG->pixpath/f/$icon";
                $prop->height = '16';
                $prop->width = '16';
                $prop->alt = 'File';
                $im = wiki_img($prop, true);
                link_to_popup_window ($ffurl, "display", $im, 480, 640);

                echo '&nbsp;';
                link_to_popup_window ($ffurl, "display", htmlspecialchars($currentname), 480, 640);

        	$prop = null;
			$prop->class = 'nwikileftnow';
			wiki_change_row($prop);

			wiki_table_end();

			$prop = null;
			$prop->border = '0';
			$prop->class = 'boxaligncenter';
			$prop->classtd = 'nwikileftnow';
			wiki_table_start($prop);

				$prop = null;
			    $prop->action = 'index.php?id='.$WS->dfwiki->course.'&amp;wdir=/"'.$folder;
				$prop->method = 'post';
				$prop->id = 'form';
				wiki_form_start($prop);

					$prop = null;
					$prop->name = 'dfformviewexported';
					$prop->value = get_string('viewexported','wiki');
					$input = wiki_input_submit($prop, true);

					wiki_div($input);

				wiki_form_end();

			wiki_change_column();

				print_continue("$CFG->wwwroot/mod/wiki/view.php?id={$WS->cm->id}");

			wiki_table_end();
		}
    }else{
	//create the form to export
	//first of all treat the form that we've sended about the pages to export
	if (!$frm = data_submitted()) {
		$contents = null;
        $contentsall = get_records_sql('SELECT DISTINCT pagename,dfwiki
				FROM '. $CFG->prefix.'wiki_pages
				WHERE dfwiki='.$WS->dfwiki->id);
		if ($contentsall != null){
           foreach ($contentsall as $call){
              $contents[] = $call->pagename;
           }
        }
        $WS->nocontents = null;
    }
	else{
		if (!empty($frm->addall)) {
            $contents = null;
            $contentsall = get_records_sql('SELECT DISTINCT pagename,dfwiki
				FROM '. $CFG->prefix.'wiki_pages
				WHERE dfwiki='.$WS->dfwiki->id);
            if ($contentsall != null){
               foreach ($contentsall as $call){
                  $contents[] = $call->pagename;
               }
            }
            $WS->nocontents = null;
        } else if (!empty($frm->removeall)) {
            $WS->nocontents = null;
            $nocontentsall = get_records_sql('SELECT DISTINCT pagename,dfwiki
				FROM '. $CFG->prefix.'wiki_pages
				WHERE dfwiki='.$WS->dfwiki->id);
            if ($nocontentsall != null){
                foreach ($nocontentsall as $nocall){
                    $WS->nocontents[] = $nocall->pagename;
                }
            }
            $contents = null;
        } else {

            $contents = null;
            $WS->nocontents = null;

            for($i=0; $i<$frm->sizecontents; $i++){
                $contents[]=$frm->contents[$i];
            }

            for($j=0; $j<$frm->sizenocontents; $j++){
                $WS->nocontents[]=$frm->nocontents[$j];
            }

            if (!empty($frm->add) and !empty($frm->addselect)) {
                foreach ($frm->addselect as $addpage) {
                    $contents[] = $addpage;
                    wiki_remove_nocontents($addpage,$WS);
                }
            } else if (!empty($frm->remove) and !empty($frm->removeselect)) {
                foreach ($frm->removeselect as $removepage) {
                    $WS->nocontents[] = $removepage;
                    wiki_remove_contents($removepage);
                }
            }

        }
    }

    $prop = null;
    $prop->class = 'textcenter';
    $info = wiki_size_text(get_string("selectpages",'wiki'),2, '', true);
    wiki_div($info, $prop);

    include('pages.html');

	wiki_div_end();

	$prop = null;
    $prop->class = 'box generalbox generalboxcontent boxaligncenter textcenter';
    wiki_div_start($prop);

		$prop = null;
	    $prop->action = 'exportxml.php?id='.$WS->cm->id.'&amp;pageaction=exportxml';
		$prop->method = 'post';
		$prop->id = 'form';
		wiki_form_start($prop);

			$prop = null;
			$prop->border = '0';
			$prop->class = 'boxaligncenter';
			$prop->classtd = 'textcenter';
			wiki_table_start($prop);

			    $sizecon = count($WS->nocontents);
			    for($i = 0; $i < $sizecon; $i++){
    				$prop = null;
					$prop->name = 'dfform['.$WS->nocontents[$i].']';
					$prop->value = $WS->nocontents[$i];
					wiki_input_hidden($prop);
				}

			    $prop = null;
			    $prop->class = 'textcenter';
			    $info = wiki_size_text(get_string("otheroptions",'wiki'),2, '', true);
			    wiki_div($info,$prop);
			    $cleandfwiki = clean_filename($WS->dfwiki->name);
			    $times = time();

			$prop = null;
			$prop->class = 'nwikileftnow';
			wiki_change_row($prop);

				$prop = null;
				$prop->name = 'dfforminfo';
				$prop->value = '1';
				wiki_input_checkbox($prop);
				print_string('exportinfo','wiki');

			$prop = null;
			$prop->class = 'nwikileftnow';
			wiki_change_row($prop);

				$prop = null;
				$prop->name = 'dfformblocks';
				$prop->value = '1';
				wiki_input_checkbox($prop);
				print_string('blocks','wiki');

			$prop = null;
			$prop->class = 'nwikicenternow';
			wiki_change_row($prop);

				$info = get_string('saveas','wiki');

				$prop = null;
				$prop->name = 'dfformname';
				$prop->value = $cleandfwiki.'-'.$times.'.zip';
				$info .= wiki_input_text($prop, true);

				wiki_paragraph($info, '');

			$prop = null;
			$prop->class = 'nwikicenternow';
			wiki_change_row($prop);

				$prop = null;
				$prop->name = 'dfformexport';
				$prop->value = get_string('export','wiki');
				wiki_input_submit($prop);

				$prop = null;
				$prop->name = 'dfformexportall';
				$prop->value = get_string('exportall','wiki');
				wiki_input_submit($prop);

				$prop = null;
				$prop->name = 'dfformcancel';
				$prop->value = get_string('cancel');
				wiki_input_submit($prop);

			wiki_table_end();

		wiki_form_end();
    }
}


//delete an element of an array
function wiki_remove_contents($element){

    global $contents;

    $vector = null;
    $size = count($contents);
    for ($i = 0; $i < $size; $i++) {
        if($contents[$i] != $element) $vector[] = $contents[$i];
    }

    $contents = $vector;
}


//delete an element of an array
function wiki_remove_nocontents($element,$WS){

    $vector = null;
    $size = count($WS->nocontents);
    for ($i = 0; $i < $size; $i++) {
        if($WS->nocontents[$i] != $element) $vector[] = $WS->nocontents[$i];
    }

    $WS->nocontents = $vector;
}


function wiki_export_content_XML(&$WS, $folder = 'exportedfiles'){

    global $CFG;

    //create the xml file with dfwiki name
    $cleandfwiki = clean_filename($WS->dfwiki->name);
    check_dir_exists("$CFG->dataroot/temp",true);
    check_dir_exists("$CFG->dataroot/temp/".$folder,true);
    check_dir_exists("$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}",true);
    $xml_file = fopen("$CFG->dataroot/temp/".$folder."/wiki{$WS->cm->id}/$cleandfwiki.xml", "w");
    fwrite($xml_file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
    fwrite($xml_file, "<dfwiki>\n");

    //export the dfwiki data
    fwrite($xml_file, "  <wiki>\n");
    fwrite($xml_file,wiki_full_tag("id",2,false,$WS->dfwiki->id));
    fwrite($xml_file,wiki_full_tag("course",2,false,$WS->dfwiki->course));
    fwrite($xml_file,wiki_full_tag("name",2,false,$WS->dfwiki->name));
    fwrite($xml_file,wiki_full_tag("pagename",2,false,$WS->dfwiki->pagename));
    fwrite($xml_file,wiki_full_tag("timemodified",2,false,$WS->dfwiki->timemodified));
    fwrite($xml_file,wiki_full_tag("editable",2,false,$WS->dfwiki->editable));
    fwrite($xml_file,wiki_full_tag("attach",2,false,$WS->dfwiki->attach));
    fwrite($xml_file,wiki_full_tag("restore",2,false,$WS->dfwiki->restore));
    fwrite($xml_file,wiki_full_tag("editor",2,false,$WS->dfwiki->editor));
    //fields added by dfwikiteam on spring 2006:
    fwrite($xml_file,wiki_full_tag("intro",2,false,$WS->dfwiki->intro));
    fwrite($xml_file,wiki_full_tag("introformat",2,false,$WS->dfwiki->introformat));
    fwrite($xml_file,wiki_full_tag("studentmode",2,false,$WS->dfwiki->studentmode));
    fwrite($xml_file,wiki_full_tag("teacherdiscussion",2,false,$WS->dfwiki->teacherdiscussion));
    fwrite($xml_file,wiki_full_tag("studentdiscussion",2,false,$WS->dfwiki->studentdiscussion));
    fwrite($xml_file,wiki_full_tag("editanothergroup",2,false,$WS->dfwiki->editanothergroup));
    fwrite($xml_file,wiki_full_tag("editanotherstudent",2,false,$WS->dfwiki->editanotherstudent));
	fwrite($xml_file,wiki_full_tag("votemode",2,false,$WS->dfwiki->votemode));
    fwrite($xml_file,wiki_full_tag("listofteachers",2,false,$WS->dfwiki->listofteachers));
    fwrite($xml_file,wiki_full_tag("editorrows",2,false,$WS->dfwiki->editorrows));
    fwrite($xml_file,wiki_full_tag("editorcols",2,false,$WS->dfwiki->editorcols));

    fwrite($xml_file, "  </wiki>\n");
    //label where we put the dfwiki content
    fwrite($xml_file, "  <contents>\n");

    //list with all the dfwiki groups
    if ($grouppages = get_records_sql('SELECT *
				FROM '. $CFG->prefix.'wiki_pages
				WHERE dfwiki='.$WS->dfwiki->id.' GROUP BY groupid')){

        //separate all the groups
        foreach ($grouppages as $grouppage){

            //list with the last version of all the pages
            if ($contentspages = get_records_sql('SELECT *
				FROM '. $CFG->prefix.'wiki_pages
				WHERE dfwiki='.$WS->dfwiki->id.'
                AND groupid='.$grouppage->groupid.' GROUP BY pagename')){

                foreach ($contentspages as $contentpage){

                    $max = get_record_sql('SELECT MAX(version) AS maxim
					    FROM '. $CFG->prefix.'wiki_pages
					    WHERE pagename=\''.addslashes($contentpage->pagename).'\' AND dfwiki='.$WS->dfwiki->id.'
                        AND groupid='.$grouppage->groupid);

                    $cont = get_record_sql('SELECT *
					   FROM '. $CFG->prefix.'wiki_pages
					   WHERE pagename=\''.addslashes($contentpage->pagename).'\' AND dfwiki='.$WS->dfwiki->id.'
                       AND groupid='.$grouppage->groupid.' AND version='.$max->maxim);

                    //only export the pages selected on form
                    $exportall = optional_param('dfformexportall',NULL,PARAM_ALPHA);
                    if (isset($WS->dfform["$cont->pagename"]) || isset($exportall)){

                        fwrite($xml_file, "    <page>\n");
                        fwrite($xml_file,wiki_full_tag("id",3,false,$cont->id));
                        fwrite($xml_file,wiki_full_tag("pagename",3,false,$cont->pagename));
                        fwrite($xml_file,wiki_full_tag("version",3,false,$cont->version));
                        fwrite($xml_file,wiki_full_tag("content",3,false,$cont->content));
                        fwrite($xml_file,wiki_full_tag("author",3,false,$cont->author));
                        fwrite($xml_file,wiki_full_tag("created",3,false,$cont->created));
                        fwrite($xml_file,wiki_full_tag("lastmodified",3,false,$cont->lastmodified));
                        fwrite($xml_file,wiki_full_tag("refs",3,false,$cont->refs));
                        fwrite($xml_file,wiki_full_tag("hits",3,false,$cont->hits));
                        fwrite($xml_file,wiki_full_tag("editable",3,false,$cont->editable));
                        fwrite($xml_file,wiki_full_tag("dfwiki",3,false,$cont->dfwiki));
                        fwrite($xml_file,wiki_full_tag("editor",3,false,$cont->editor));
                        fwrite($xml_file,wiki_full_tag("groupid",3,false,$cont->groupid));
                        //fields added by dfwikiteam on spring 2006:
                        fwrite($xml_file,wiki_full_tag("userid",3,false,$cont->userid));
                        fwrite($xml_file,wiki_full_tag("ownerid",3,false,$cont->ownerid));
                        fwrite($xml_file,wiki_full_tag("highlight",3,false,$cont->highlight));

                        fwrite($xml_file, "    </page>\n");

                    }
                }
            }
        }
    }

    //close the contents label
    fwrite($xml_file, "  </contents>\n");

    //label where we put all the pages
    fwrite($xml_file, "  <pages>\n");

	$info = optional_param('dfforminfo',NULL,PARAM_INT);
	if(isset($info)){
		if (($info == 1) || isset($exportall)){

    		//generate a list with all the dfwiki groups
			if ($grouphists = get_records_sql('SELECT *
				FROM '. $CFG->prefix.'wiki_pages
				WHERE dfwiki='.$WS->dfwiki->id.' GROUP BY groupid')){

        		//separate all the groups
				foreach ($grouphists as $grouphist){

            		//list with the pages
					if ($contentshist = get_records_sql('SELECT *
						FROM '. $CFG->prefix.'wiki_pages
						WHERE dfwiki='.$WS->dfwiki->id.'
                		AND groupid='.$grouphist->groupid.' GROUP BY pagename')){

							foreach ($contentshist as $contenthist){
								$max = get_record_sql('SELECT MAX(version) AS maxim
					    		FROM '. $CFG->prefix.'wiki_pages
					    		WHERE pagename=\''.addslashes($contenthist->pagename).'\' AND dfwiki='.$WS->dfwiki->id.'
                        		AND groupid='.$grouphist->groupid);

								if ($pages = get_records_sql('SELECT *
				        			FROM '. $CFG->prefix.'wiki_pages
				        			WHERE dfwiki='.$WS->dfwiki->id.'
				        			AND pagename=\''.addslashes($contenthist->pagename).'\'
                        			AND version<'.$max->maxim.'
                        			AND groupid='.$grouphist->groupid)){

										foreach ($pages as $page){
											fwrite($xml_file, "    <page>\n");
											fwrite($xml_file,wiki_full_tag("id",3,false,$page->id));
											fwrite($xml_file,wiki_full_tag("pagename",3,false,$page->pagename));
											fwrite($xml_file,wiki_full_tag("version",3,false,$page->version));
											fwrite($xml_file,wiki_full_tag("content",3,false,$page->content));
											fwrite($xml_file,wiki_full_tag("author",3,false,$page->author));
											fwrite($xml_file,wiki_full_tag("created",3,false,$page->created));
											fwrite($xml_file,wiki_full_tag("lastmodified",3,false,$page->lastmodified));
											fwrite($xml_file,wiki_full_tag("refs",3,false,$page->refs));
											fwrite($xml_file,wiki_full_tag("hits",3,false,$page->hits));
											fwrite($xml_file,wiki_full_tag("editable",3,false,$page->editable));
											fwrite($xml_file,wiki_full_tag("dfwiki",3,false,$page->dfwiki));
											fwrite($xml_file,wiki_full_tag("editor",3,false,$page->editor));
											fwrite($xml_file,wiki_full_tag("groupid",3,false,$page->groupid));
                            				//fields added by dfwikiteam on spring 2006:
											fwrite($xml_file,wiki_full_tag("userid",3,false,$page->userid));
											fwrite($xml_file,wiki_full_tag("ownerid",3,false,$page->ownerid));
											fwrite($xml_file,wiki_full_tag("highlight",3,false,$page->highlight));
											fwrite($xml_file, "    </page>\n");
										}
									}
							}
						}
					}
				}
		}
	}
    //close the pages label
    fwrite($xml_file, "  </pages>\n");

    //label where we put all the synonymous
    fwrite($xml_file, "  <synonymous>\n");

	$formblocks = optional_param('dfformblocks',NULL,PARAM_INT);

    if(isset($formblocks)){
		if (($formblocks == 1) || isset($exportall)){

    	//list with the synonymous
			if ($synonymous = get_records_sql('SELECT *
				FROM '. $CFG->prefix.'wiki_synonymous
				WHERE dfwiki='.$WS->dfwiki->id)){

					foreach ($synonymous as $syn){
						fwrite($xml_file, "    <synonym>\n");
						fwrite($xml_file,wiki_full_tag("id",3,false,$syn->id));
						fwrite($xml_file,wiki_full_tag("syn",3,false,$syn->syn));
						fwrite($xml_file,wiki_full_tag("original",3,false,$syn->original));
						fwrite($xml_file,wiki_full_tag("dfwiki",3,false,$syn->dfwiki));
						fwrite($xml_file,wiki_full_tag("groupid",3,false,$syn->groupid));
            			//fields added by dfwikiteam on spring 2006:
						fwrite($xml_file,wiki_full_tag("ownerid",3,false,$syn->ownerid));

						fwrite($xml_file, "    </synonym>\n");
					}

			}

		}
	}
    //close the synonymous label
    fwrite($xml_file, "  </synonymous>\n");

    //label where we put all the blocks
    fwrite($xml_file, "  <blocks>\n");

    if(isset($formblocks)){
		if (($formblocks == 1) || isset($exportall)){

	    	//list with all the wiki blocks
			if ($blocksids = get_records_sql("SELECT *
				FROM ".$CFG->prefix."block
				WHERE name LIKE 'wiki_%'")){

				$id="";
				foreach ($blocksids as $blockid){
					$id.=$blockid->id.",";
				}
				$id.="'pigui'";
				// list of blocks in this wiki
				$blocks = get_records_sql("SELECT *
					FROM ". $CFG->prefix."block_instance
					WHERE blockid IN (".$id.")
					AND pageid=".$WS->cm->course); //this is course if become of course and instance if become of a wiki
				foreach ($blocks as $block){
					fwrite($xml_file, "    <block>\n");
					fwrite($xml_file,wiki_full_tag("id",3,false,$block->id));
					fwrite($xml_file,wiki_full_tag("blockid",3,false,$blocksids[$block->blockid]->name));
					fwrite($xml_file,wiki_full_tag("pageid",3,false,$block->pageid));
					fwrite($xml_file,wiki_full_tag("pagetype",3,false,$block->pagetype));
					fwrite($xml_file,wiki_full_tag("position",3,false,$block->position));
					fwrite($xml_file,wiki_full_tag("weight",3,false,$block->weight));
					fwrite($xml_file,wiki_full_tag("visible",3,false,$block->visible));
					fwrite($xml_file,wiki_full_tag("configdata",3,false,$block->configdata));
					fwrite($xml_file, "    </block>\n");
				}

			}
		}
	}
    //close the blocks label
    fwrite($xml_file, "  </blocks>\n");


	//label where we put all the votes
    fwrite($xml_file, "  <votes>\n");

    if(isset($formblocks)){

		if (($formblocks == 1) || isset($exportall)){
	    	//list with the synonymous
			if ($votes = get_records_sql('SELECT *
					FROM '. $CFG->prefix.'wiki_votes
					WHERE dfwiki='.$WS->dfwiki->id)){

					foreach ($votes as $vote){
						fwrite($xml_file, "    <vote>\n");
						fwrite($xml_file,wiki_full_tag("id",3,false,$vote->id));
						fwrite($xml_file,wiki_full_tag("pagename",3,false,$vote->pagename));
						fwrite($xml_file,wiki_full_tag("version",3,false,$vote->version));
						fwrite($xml_file,wiki_full_tag("dfwiki",3,false,$vote->dfwiki));
						fwrite($xml_file,wiki_full_tag("username",3,false,$vote->username));
						fwrite($xml_file, "    </vote>\n");
					}
			}
		}
	}
	//close the votes label
	fwrite($xml_file, "  </votes>\n");

	// XML end
    fwrite($xml_file, "</dfwiki>");

}


//this function create import tab content
function wiki_import_content(&$WS){

    //if the form is complete
    $sure = optional_param('dfformsure',NULL,PARAM_ALPHA);
    if (isset($sure)){

        //import to xml
        wiki_import_content_XML($WS);

        $prop = null;
	    $prop->class = 'textcenter';
	    $info = wiki_size_text(get_string("importcorrectly",'wiki'),2, '', true);
	    wiki_div($info,$prop);

	    $prop = null;
	    $prop->action = '../view.php?id='.$WS->cm->id;
		$prop->method = 'post';
		$prop->id = 'form';
		wiki_form_start($prop);

			$prop = null;
			$prop->border = '0';
			$prop->class = 'boxaligncenter';
			$prop->classtd = 'nwikileftnow';
			wiki_table_start($prop);

				$prop = null;
				$prop->name = 'dfformcontinue';
				$prop->value = get_string('continue');
				$input = wiki_input_submit($prop, true);

				wiki_div($input);

			wiki_table_end();

		wiki_form_end();

    }
    else if (isset($WS->dfform['import'])){

        $extension = explode("/",$WS->path);
        $num = count($extension)-1;
        $name = $extension[$num];

        $info = wiki_b(get_string("importcheckwarning", 'wiki'), '', true);
        $info .= '    '.$name;
        $prop = null;
        $prop->class = 'textcenter';
        wiki_paragraph($info, $prop);

        switch ($WS->dfform['incase']) {
            case 0:
                $name = get_string("always", 'wiki');
                break;
            case 1:
                $name = get_string("never", 'wiki');
                break;
            case 2:
                $name = get_string("before", 'wiki');
                break;
            case 3:
                $name = get_string("after", 'wiki');
                break;
            default:
                break;
        }
        $info = wiki_b(get_string("incase", 'wiki'), '', true);
        $info .= '  '.$name;
        $prop = null;
        $prop->class = 'textcenter';
        wiki_paragraph($info, $prop);

        switch ($WS->dfform['incaseatach']) {
            case 0:
                $name = get_string("alwaysatach", 'wiki');
                break;
            case 1:
                $name = get_string("neveratach", 'wiki');
                break;
            case 2:
                $name = get_string("beforeatach", 'wiki');
                break;
            case 3:
                $name = get_string("afteratach", 'wiki');
                break;
            case 4:
                $name = get_string("bigeratach", 'wiki');
                break;
            case 5:
                $name = get_string("smalleratach", 'wiki');
                break;
            default:
                break;
        }
        $info = wiki_b(get_string("incaseatach", 'wiki'), '', true);
        $info .= '  '.$name;
        $prop = null;
        $prop->class = 'textcenter';
        wiki_paragraph($info, $prop);

		wiki_br();

		$prop = null;
        $prop->class = 'textcenter';
        wiki_paragraph(get_string("importcheckfiles", 'wiki'), $prop);

		$prop = null;
		$prop->class = 'boxaligncenter';
		wiki_table_start($prop);

			$prop = null;
		    $prop->action = 'exportxml.php?id='.$WS->cm->id.'&amp;pageaction=importxml&amp;path='.$WS->path;
			$prop->method = 'post';
			$prop->id = 'form1';
			wiki_form_start($prop);

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = $WS->dfform['incase'];
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = $WS->dfform['incaseatach'];
				wiki_input_hidden($prop);
									$prop = null;
				$prop->name = 'dfformsure';
				$prop->value = get_string('yes');
				wiki_input_submit($prop);

			wiki_form_end();

		wiki_change_column();

			$prop = null;
		    $prop->action = 'exportxml.php?id='.$WS->cm->id.'&amp;pageaction=importxml&amp;path='.$WS->path;
			$prop->method = 'post';
			$prop->id = 'form2';
			wiki_form_start($prop);

				$prop = null;
				$prop->name = 'dfformcancel';
				$prop->value = get_string('no');
				wiki_input_submit($prop);

			wiki_form_end();

		wiki_table_end();
	}
	else{
	//create the form to import

	$prop = null;
    $prop->action = 'exportxml.php?id='.$WS->cm->id.'&amp;pageaction=importxml&amp;path='.$WS->path;
	$prop->method = 'post';
	$prop->id = 'form';
	wiki_form_start($prop);

		$prop = null;
		$prop->border = '0';
		$prop->class = 'boxaligncenter';
		$prop->classtd = 'nwikileftnow';
		wiki_table_start($prop);

			wiki_b(get_string('incase','wiki'));

			wiki_table_start();

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = '0';
				$prop->checked = 'checked';
				wiki_input_radio($prop);

				print_string('always','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = '1';
				wiki_input_radio($prop);

				print_string('never','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = '2';
				wiki_input_radio($prop);

				print_string('before','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = '3';
				wiki_input_radio($prop);

				print_string('after','wiki');

			wiki_table_end();

			wiki_br(2);

		$prop = null;
		$prop->class = "nwikileftnow";
		wiki_change_row($prop);

			wiki_b(get_string('incaseatach','wiki'));

			wiki_table_start();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '0';
				$prop->checked = 'checked';
				wiki_input_radio($prop);

				print_string('alwaysatach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '1';
				wiki_input_radio($prop);

				print_string('neveratach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '2';
				wiki_input_radio($prop);

				print_string('beforeatach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '3';
				wiki_input_radio($prop);

				print_string('afteratach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '4';
				wiki_input_radio($prop);

				print_string('bigeratach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '5';
				wiki_input_radio($prop);

				print_string('smalleratach','wiki');

			wiki_table_end();

			wiki_br(2);

		$prop = null;
		$prop->class = "nwikicenternow";
		wiki_change_row($prop);

			$prop = null;
			$prop->name = 'dfformimport';
			$prop->value = get_string('import','wiki');
			wiki_input_submit($prop);

		wiki_table_end();

	wiki_form_end();

    }
}


//this function create import tab content
function wiki_import_wiki(&$WS){
	global $file;

	//if the form is complete
	$sure = optional_param('dfformsure',NULL,PARAM_ALPHA);
    if (isset($sure)){
        //import to xml
        wiki_import_wiki_XML($WS);

        $prop = null;
	    $prop->class = 'textcenter';
	    $info = wiki_size_text(get_string("importcorrectly",'wiki'),2, '', true);
	    wiki_div($info,$prop);

	    $prop = null;
	    $prop->action = '../view.php?id='.$WS->cm->id;
		$prop->method = 'post';
		$prop->id = 'form';
		wiki_form_start($prop);

			$prop = null;
			$prop->border = '0';
			$prop->class = 'boxaligncenter';
			$prop->classtd = 'nwikileftnow';
			wiki_table_start($prop);

				$prop = null;
				$prop->name = 'dfformcontinue';
				$prop->value = get_string('continue');
				$input = wiki_input_submit($prop, true);

				wiki_div($input);

			wiki_table_end();

		wiki_form_end();
    }
    else if (isset($WS->dfform['import'])){

        $extension = explode("/",$WS->path);
        $num = count($extension)-1;
        $name = $extension[$num];

        $info = wiki_b(get_string("importcheckwarning", 'wiki'), '', true);
        $info .= '    '.$name;
        $prop = null;
        $prop->class = 'textcenter';
        wiki_paragraph($info, $prop);

        switch ($WS->dfform['incase']) {
            case 0:
                $name = get_string("always", 'diki');
                break;
            case 1:
                $name = get_string("never", 'wiki');
                break;
            case 2:
                $name = get_string("before", 'wiki');
                break;
            case 3:
                $name = get_string("after", 'wiki');
                break;
            default:
                break;
        }
        $info = wiki_b(get_string("incase", 'wiki'), '', true);
        $info .= '  '.$name;
        $prop = null;
        $prop->class = 'textcenter';
        wiki_paragraph($info, $prop);

        switch ($WS->dfform['incaseatach']) {
            case 0:
                $name = get_string("alwaysatach", 'wiki');
                break;
            case 1:
                $name = get_string("neveratach", 'wiki');
                break;
            case 2:
                $name = get_string("beforeatach", 'wiki');
                break;
            case 3:
                $name = get_string("afteratach", 'wiki');
                break;
            case 4:
                $name = get_string("bigeratach", 'wiki');
                break;
            case 5:
                $name = get_string("smalleratach", 'wiki');
                break;
            default:
                break;
        }
        $info = wiki_b(get_string("incaseatach", 'wiki'), '', true);
        $info .= '  '.$name;
        $prop = null;
        $prop->class = 'textcenter';
        wiki_paragraph($info, $prop);

		wiki_br();

		$prop = null;
        $prop->class = 'textcenter';
        wiki_paragraph(get_string("importcheckfiles", 'wiki'), $prop);

		$prop = null;
		$prop->class = 'boxaligncenter';
		wiki_table_start($prop);

			$prop = null;
		    $prop->action = 'exportxml.php?id='.$WS->cm->id.'&amp;pageaction=importxml&amp;path='.$WS->path;
			$prop->method = 'post';
			$prop->id = 'form1';
			wiki_form_start($prop);

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = $WS->dfform['incase'];
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = $WS->dfform['incaseatach'];
				wiki_input_hidden($prop);
									$prop = null;
				$prop->name = 'dfformsure';
				$prop->value = get_string('yes');
				wiki_input_submit($prop);

			wiki_form_end();

		wiki_change_column();

			$prop = null;
		    $prop->action = 'exportxml.php?id='.$WS->cm->id.'&amp;pageaction=importewiki&amp;path='.$WS->path.'&amp;file='.$file;
			$prop->method = 'post';
			$prop->id = 'form2';
			wiki_form_start($prop);

				$prop = null;
				$prop->name = 'dfformcancel';
				$prop->value = get_string('no');
				wiki_input_submit($prop);

			wiki_form_end();

		wiki_table_end();
	}
	else{
	//create the form to import
	$prop = null;
    $prop->action = 'exportxml.php?id='.$WS->cm->id.'&amp;pageaction=importewiki&amp;type='.$WS->type.'&amp;path='.$WS->path.'&amp;file='.$file;
	$prop->method = 'post';
	$prop->id = 'form';
	wiki_form_start($prop);

		$prop = null;
		$prop->border = '0';
		$prop->class = 'boxaligncenter';
		$prop->classtd = 'nwikileftnow';
		wiki_table_start($prop);

			wiki_b(get_string('incase','wiki'));

			wiki_table_start();

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = '0';
				$prop->checked = 'checked';
				wiki_input_radio($prop);

				print_string('always','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = '1';
				wiki_input_radio($prop);

				print_string('never','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = '2';
				wiki_input_radio($prop);

				print_string('before','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincase';
				$prop->value = '3';
				wiki_input_radio($prop);

				print_string('after','wiki');

			wiki_table_end();

			wiki_br(2);

		$prop = null;
		$prop->class = "nwikileftnow";
		wiki_change_row($prop);

			wiki_b(get_string('incaseatach','wiki'));

			wiki_table_start();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '0';
				$prop->checked = 'checked';
				wiki_input_radio($prop);

				print_string('alwaysatach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '1';
				wiki_input_radio($prop);

				print_string('neveratach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '2';
				wiki_input_radio($prop);

				print_string('beforeatach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '3';
				wiki_input_radio($prop);

				print_string('afteratach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '4';
				wiki_input_radio($prop);

				print_string('bigeratach','wiki');

			wiki_change_row();

				$prop = null;
				$prop->name = 'dfformincaseatach';
				$prop->value = '5';
				wiki_input_radio($prop);

				print_string('smalleratach','wiki');

			wiki_table_end();

			wiki_br(2);

		$prop = null;
		$prop->class = "nwikicenternow";
		wiki_change_row($prop);

			$prop = null;
			$prop->name = 'dfformimport';
			$prop->value = get_string('import','wiki');
			wiki_input_submit($prop);

		wiki_table_end();

	wiki_form_end();

    }

}

//function that cross the xml and import with the associated files
function wiki_import_content_XML(&$WS){

    global $CFG,$COURSE, $infopages, $infopagesbis, $pagemaxim;

    check_dir_exists($CFG->dataroot."/temp/unzip",true);
    $destination = $CFG->dataroot."/temp/unzip";
    unzip_file ($WS->path, $destination, false);

    //take the .xml file
    $filelist = list_directories_and_files ("$CFG->dataroot/temp/unzip");
    $atachfiles = null;

    foreach ($filelist as $file) {
        $extension = explode(".",$file);
        $num = count($extension)-1;
        if($extension[$num] == "xml") $goodfile = $file;
        else $atachfiles[] = $file;
    }

    //import
    $xml = new XMLFile();
    $file_name = $CFG->dataroot."/temp/unzip/".$goodfile;
    $xml_file = fopen( "$file_name", "r" );
    $xml->read_file_handle($xml_file);
    $numRows = $xml->roottag->num_subtags(); //sobra
    //array where to save the name of the pages that we have to import their history
    $infopages = null;

    $wiki_content = wiki_pass_XML_to_Array($xml);
    wiki_validate_and_insert_content($wiki_content, $WS);

    //close the xml file that is still open
    fclose($xml_file);

    //import the associated files
    if($atachfiles != null) wiki_import_atachment($atachfiles,$WS);

    //delete the folder of the zip
    $filelist2 = list_directories_and_files ("$CFG->dataroot/temp/unzip");
    foreach ($filelist2 as $file2) {
        unlink("$CFG->dataroot/temp/unzip/$file2");
    }
    rmdir("$CFG->dataroot/temp/unzip");

}

//cross the xml and import it with all the associated files
function wiki_import_wiki_XML(&$WS){

    global $CFG,$file, $infopages, $oldid, $oldentryid;

    $e_wiki = get_ewikis_bis($WS->path, $file);
    if (is_array($e_wiki)) wiki_validate_and_insert_content($e_wiki[0],$WS);
    else error("Not exists wiki pages to import in the backup file!!");
  	wiki_import_atachment_wiki($e_wiki[0]['importfrombackup'][1], $WS);

  	//delete the folder that we've created on temp
    $filelist2 = list_directories_and_files ("$CFG->dataroot/temp/ewikis");
    if ($filelist2 != null) $del = delete_dir_contents("$CFG->dataroot/temp/ewikis");

}


//treat an ewiki content to pass to dfwiki
function wiki_treat_content_ewiki($content){

    global $CFG;

    //take the ewiki course to import
    $extension_ = explode("/",$WS->path);
    $num_ = count($extension_)-3;
    $course_ = $extension_[$num_];

    $links = null;
    $content = preg_replace("/file.php\//", "file.php\/$course_", $content);
    $content = preg_replace("/internal:/", "attach:", $content);

    //take all the links within []and save it into the "links" array
    $end = strpos($content, ']', 0);
    $start = strpos($content, '[', 0);
    while ($start !== false){
        $ofmoment = substr($content, $start, $end - $start + 1);
        $smilestart = substr($content, $start - 2, $start);
        $smileend = substr($content, $end - 2, $end);
        if ((!wiki_contain_vector($links, $ofmoment)) && ($smileend != '}-') && ($smilestart != '8-')){
            $links[] = substr($content, $start, $end - $start + 1);
        }
        if(($smileend == '}-') && ($smilestart != '8-')) $end = strpos($content, ']', $start+1);
        else if(($smileend != '}-') && ($smilestart == '8-')) $start = strpos($content, '[', $start+1);
        else{
            $start = strpos($content, '[', $start+1);
            $end = strpos($content, ']', $start+1);
        }
    }

    //with every link
    if($links != null){
        foreach ($links as $link) {
            $WS->type = strpos($link, 'http://');
            if ($WS->type === false){
                //is internal
                $link2 = substr($link, 1, strlen($link)-2);
                $exist = strpos($link2, '|');
                if ($exist === false){
                    //special case of redirect to wikipedia
                    $existwikipedia = strpos($link2, 'wikipedia:');
                    $existWikipedia = strpos($link2, 'Wikipedia:');
                    if (($existwikipedia === false) && ($existWikipedia === false)){
                        $link4 = trim($link2);
                        $link3 = "[[$link4]]";
                        $content = str_replace($link, $link3, $content);

                    }else{
                        $link4 = trim($link2);
                        $extensionwikipedia = explode(":",$link4);
                        $numwikipedia = count($extensionwikipedia)-1;
                        if(count($extensionwikipedia) == 1) $namewikipedia = '';
                        else $namewikipedia = trim($extensionwikipedia[$numwikipedia]);
                        $link3 = "[http://www.wikipedia.com/wiki.cgi?$namewikipedia]";
                        $content = str_replace($link, $link3, $content);
                    }
                }else{
                    $existwikipedia = strpos($link2, 'wikipedia:');
                    $existWikipedia = strpos($link2, 'Wikipedia:');
                    if (($existwikipedia === false) && ($existWikipedia === false)){
                        $extension = explode("|",$link2);
                        $num1 = count($extension)-1;
                        $num2 = count($extension)-2;
                        $name1 = trim($extension[$num1]);
                        $name2 = trim($extension[$num2]);
                        $link3 = "[[$name1|$name2]]";
                        $content = str_replace($link, $link3, $content);
                    }else{
                        $extension = explode("|",$link2);
                        $num1 = count($extension)-1;
                        $num2 = count($extension)-2;
                        $name1 = trim($extension[$num1]);
                        $name2 = trim($extension[$num2]);
                        $extensionwikipedia = explode(":",$name2);
                        $numwikipedia = count($extensionwikipedia)-1;
                        if(count($extensionwikipedia) == 1) $namewikipedia = '';
                        else $namewikipedia = trim($extensionwikipedia[$numwikipedia]);
                        $link3 = "[http://www.wikipedia.com/wiki.cgi?$namewikipedia $name1]";
                        $content = str_replace($link, $link3, $content);
                    }
                }
            }
            else{
                //is external
                $link2 = substr($link, 1, strlen($link)-2);
                $exist = strpos($link2, '|');
                if ($exist === false){
                    $link4 = trim($link2);
                    $link3 = "[$link4]";
                    $content = str_replace($link, $link3, $content);
                }else{
                    $extension = explode("|",$link2);
                    $num1 = count($extension)-1;
                    $num2 = count($extension)-2;
                    $name1 = trim($extension[$num1]);
                    $name2 = trim($extension[$num2]);
                    $link3 = "[$name1 $name2]";
                    $content = str_replace($link, $link3, $content);
                }
            }
        }
    }

    return $content;

}

//import to the present dfwiki the associated files of the dfwiki to import
function wiki_import_atachment($files,$WS){

    global $CFG;

    //create the directory where we save the associated files of dfwiki if there isn't yet
    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}",true);

    //take one by one
    foreach ($files as $file) {

        //look if the associated file exists
        if (file_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file")){
            //treat it
            wiki_treat_atach($file,$WS);
        }else{
            $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file";
            $from_file = "$CFG->dataroot/temp/unzip/$file";
            copy($from_file,$to_file);
        }
    }
}

//import to the present dfwiki the associated files of the dfwiki to import
function wiki_import_atachment_wiki($e_wiki, &$WS){

    global $CFG;
    if (isset($e_wiki['oldid'])){
        $oldid = $e_wiki['oldid'];
    }else{
        $oldid = '';
    }
    if (isset($e_wiki['oldentryid'])){
        $oldentryid = $e_wiki['oldentryid'];
    }else{
        $oldentryid = '';
    }
    //create the directory where we save the associated files of dfwiki if there isn't yet
    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}",true);
    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata",true);
    check_dir_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}",true);

    check_dir_exists("$CFG->dataroot/temp/ewikis/moddata",true);
    check_dir_exists("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}",true);
    check_dir_exists("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid",true);
    check_dir_exists("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid",true);
    $files = list_directories_and_files("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid");
    //one by one
    if($files != null){
        foreach ($files as $fil) {

            //if isn't a directory
            if (!is_dir("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil")) {
                //if the associated file exists
                if (file_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil")){
                    //treat it
                    wiki_treat_atach_wiki($fil,$WS);
                }else{
                    $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil";
                    $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil";
                    copy($from_file,$to_file);
                }
            }

            //if it's a directory
            else{
                //one by one
                $files2 = list_directories_and_files("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil");
                if($files2 != null){
                    foreach ($files2 as $fil2) {
                        if (file_exists("$CFG->dataroot/{$WS->dfwiki->course}/moddata/{$WS->type}{$WS->cm->id}/$fil2")){
                            //treat it
                            wiki_treat_atach_wiki_dir($fil, $fil2, $WS);
                        }else{
                            $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2";
                            $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil/$fil2";
                            copy($from_file,$to_file);
                        }

                    }

                }

            }
        }
    }
}

//the import of a page when exists
function wiki_treat_page($data,&$WS){

    global $CFG,$pagemaxim;

    //save the date of the page
    $max = get_record_sql('SELECT MAX(version) AS maxim
	FROM '. $CFG->prefix.'wiki_pages
	WHERE pagename=\''.addslashes($data->pagename).'\' AND dfwiki='.$WS->dfwiki->id.' AND groupid='.$data->groupid);

    $pagemaxim = get_record_sql('SELECT *
	FROM '. $CFG->prefix.'wiki_pages
	WHERE pagename=\''.addslashes($data->pagename).'\' AND dfwiki='.$WS->dfwiki->id.' AND groupid='.$data->groupid.'
    AND version='.$max->maxim);

	$time = $pagemaxim->lastmodified;
    //option selected by user
    switch ($WS->dfform['incase']) {
                 case 0:
                    $infopages=wiki_replace_page($data,$WS);
                    break;

                 case 1:
                    break;

                 case 2:
                    //before
                    //replace if it's older than existing
                    if($data->lastmodified < $time) $infopages=wiki_replace_page($data,$WS);
                    break;

                 case 3:
                    //after
                    //replace if it's newer than existing
                    if($data->lastmodified >= $time) $infopages=wiki_replace_page($data,$WS);
                    break;

                 default:
                    break;
    }
    return $infopages;
}

//treat the import of an associated file of import when it exists
function wiki_treat_atach($file,&$WS){

    global $CFG;

    //save the date of the associated file
	$time = filemtime("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file");
	$size = filesize("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file");
	$timeatach = filemtime("$CFG->dataroot/temp/unzip/$file");
	$sizeatach = filesize("$CFG->dataroot/temp/unzip/$file");

    //option selected by user
    switch ($WS->dfform['incaseatach']) {
                 case 0:
                    //alwaysatach
                    unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file");
                    $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file";
                    $from_file = "$CFG->dataroot/temp/unzip/$file";
                    copy($from_file,$to_file);
                    break;

                 case 1:
                    break;

                 case 2:
                    //beforeatach
                    //replace if it's older than existing
                    if($timeatach < $time){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file";
                        $from_file = "$CFG->dataroot/temp/unzip/$file";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 3:
                    //afteratach
                    //replace if it's newer than existing
                    if($timeatach >= $time){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file";
                        $from_file = "$CFG->dataroot/temp/unzip/$file";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 4:
                    //bigeratach
                    //replace if it's bigger
                    if($sizeatach >= $size){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file";
                        $from_file = "$CFG->dataroot/temp/unzip/$file";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 5:
                    //smalleratach
                    //replace if it's smaller
                    if($sizeatach < $size){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$file";
                        $from_file = "$CFG->dataroot/temp/unzip/$file";
                        copy($from_file,$to_file);
                    }
                    break;

                 default:
                    break;
    }
}

//treat the import of an associated file of import when it exists
function wiki_treat_atach_wiki($fil,&$WS){

    global $CFG, $oldid, $oldentryid;

    //save the date of the associated file
	$time = filemtime("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil");
	$size = filesize("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil");
	$timeatach = filemtime("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil");
	$sizeatach = filesize("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil");

    //option selected by user
    switch ($WS->dfform['incaseatach']) {
                 case 0:
                    //alwaysatach
                    unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil");
                    $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil";
                    $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil";
                    copy($from_file,$to_file);
                    break;

                 case 1:
                    break;

                 case 2:
                    //beforeatach
                    //replace if it's older than existing
                    if($timeatach < $time){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil";
                        $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 3:
                    //afteratach
                    //replace if it's newer than existing
                    if($timeatach >= $time){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil";
                        $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 4:
                    //bigeratach
                    //replace if it's bigger than existing
                    if($sizeatach >= $size){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil";
                        $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 5:
                    //smalleratach
                    //replace if it's smaller than existing
                    if($sizeatach < $size){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil";
                        $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil";
                        copy($from_file,$to_file);
                    }
                    break;

                 default:
                    break;
    }
}


//treat the import of an associated file of import when it exists
function wiki_treat_atach_wiki_dir($fil, $fil2, &$WS){

    global $CFG, $oldid, $oldentryid;

    //save the date of the associated file
	$time = filemtime("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2");
	$size = filesize("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2");
	$timeatach = filemtime("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil/$fil2");
	$sizeatach = filesize("$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil/$fil2");

    //option selected by user
    switch ($WS->dfform['incaseatach']) {
                 case 0:
                    //alwaysatach
                    unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2");
                    $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2";
                    $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil/$fil2";
                    copy($from_file,$to_file);
                    break;

                 case 1:
                    break;

                 case 2:
                    //beforeatach
                    //replace if it's older than existing
                    if($timeatach < $time){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2";
                        $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil/$fil2";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 3:
                    //afteratach
                    //replace if it's newer than existing
                    if($timeatach >= $time){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2";
                        $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil/$fil2";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 4:
                    //bigeratach
                    //replace if it's bigger than existing
                    if($sizeatach >= $size){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2";
                        $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil/$fil2";
                        copy($from_file,$to_file);
                    }
                    break;

                 case 5:
                    //smalleratach
                    //replace if it's smaller than existing
                    if($sizeatach < $size){
                        unlink("$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2");
                        $to_file = "$CFG->dataroot/{$WS->dfwiki->course}/moddata/wiki{$WS->cm->id}/$fil2";
                        $from_file = "$CFG->dataroot/temp/ewikis/moddata/{$WS->type}/$oldid/$oldentryid/$fil/$fil2";
                        copy($from_file,$to_file);
                    }
                    break;

                 default:
                    break;
    }
}

//import a page deleting the existing one
function wiki_replace_page($data,&$WS){

    global $COURSE,$infopages;
    //delete the page and their synonymous
    delete_records ('wiki_synonymous','dfwiki',$WS->dfwiki->id,'original',addslashes($data->pagename),'groupid',$data->groupid);
	delete_records ('wiki_pages','dfwiki',$WS->dfwiki->id,'pagename',addslashes($data->pagename),'groupid',$data->groupid);

    if (!insert_record ('wiki_pages',$data)){
        echo "Can\'t insert page record";
    }
    else{
    $infopages[] = $data->pagename;
    add_to_log($COURSE->id, 'wiki', "save $data->pagename", "view.php?id={$WS->cm->id}&amp;page=$data->pagename", "wiki edited {$WS->dfwiki->name}: $data->pagename",$WS->cm->id);
    }
    return $infopages;
}

//look if infopages contains a concrete element
function exist_pagename_infopages($infopages,$name){

    if ($infopages != null){
        foreach ($infopages as $page) {
            if($page == $name) return true;
        }
    }
    return false;

}

//returns the name of all the ewikis of the backup of a course
function get_ewikis_bis($zip, $ewiki){

    global $CFG;

    $listewikis = null;

    //unpack the .zip
    check_dir_exists("$CFG->dataroot/temp",true);
    check_dir_exists("$CFG->dataroot/temp/ewikis",true);
    $destination = "$CFG->dataroot/temp/ewikis";
    unzip_file ($zip, $destination, false);

    //take the .xml
    $filelist = list_directories_and_files ("$CFG->dataroot/temp/ewikis");
    if ($filelist == null) return $listewikis;
    foreach ($filelist as $file) {
        $extension = explode(".",$file);
        $num = count($extension)-1;
        if($extension[$num] == "xml") $goodfile = $file;
    }

    $newfile = "$CFG->dataroot/temp/ewikis/$goodfile";

    $info = restore_read_xml_bis_bis($newfile, $ewiki);

    return $info;

}

//returns the name of all the ewikis of the backup of a course
function restore_read_xml_bis_bis ($xml_file, $ewiki) {

        $status = true;

        $xml_parser = xml_parser_create('UTF-8');
        $moodle_parser = new MoodleParser();
        $moodle_parser->todo = "MODULES";
        $moodle_parser->ewiki = $ewiki;
        xml_set_object($xml_parser,$moodle_parser);

        xml_set_element_handler($xml_parser, "startElementModules", "EndElementModule");

        xml_set_character_data_handler($xml_parser, "characterData");
        $fp = fopen($xml_file,"r")
            or $status = false;
        if ($status) {
            while ($data = fread($fp, 4096) and !$moodle_parser->finished)
                    xml_parse($xml_parser, $data, feof($fp))
                            or die(sprintf("XML error: %s at line %d",
                            xml_error_string(xml_get_error_code($xml_parser)),
                                    xml_get_current_line_number($xml_parser)));
            fclose($fp);
        }
        //Get info from parser
        $info = $moodle_parser->info;

        //Clear parser mem
        xml_parser_free($xml_parser);

        if ($status && $info) {
            return $info;
        } else {
            return $status;
        }
    }

    //This is the class used to do all the xml parse
    class MoodleParser {

        var $level = 0;        //Level we are
        var $counter = 0;      //Counter
        var $tree = array();   //Array of levels we are
        var $content = "";     //Content under current level
        var $todo = "";        //What we hav to do when parsing
        var $ewiki = "";
        var $info = "";        //Information collected. Temp storage. Used to return data after parsing.
        var $temp = "";        //Temp storage.
        var $preferences = ""; //Preferences about what to load !!
        var $finished = false; //Flag to say xml_parse to stop

        //This function is used to get the current contents property value
        //They are trimed and converted from utf8
        function getContents() {
            return trim(utf8_decode($this->content));
        }

        //This is the startTag handler we use where we are reading the modules zone (todo="MODULES")
        function startElementModules($parser, $tagName, $attrs) {
            //Refresh properties
            $this->level++;
            $this->tree[$this->level] = $tagName;

            //Output something to avoid browser timeouts...
            backup_flush();

            //If we are under a MOD tag under a MODULES zone, accumule it
            if (isset($this->tree[4]) and isset($this->tree[3])) {
                if (($this->tree[4] == "MOD") and ($this->tree[3] == "MODULES")) {
                    if (!isset($this->temp)) {
                        $this->temp = "";
                    }
                    $this->temp .= "<".$tagName.">";
                }
            }
        }

    //This is the endTag handler we use where we are reading the modules zone (todo="MODULES")
    function EndElementModule($parser, $tagName) {

            //Check if we are into MODULES zone
            if ($this->tree[3] == "MODULES") {
                //Acumulate data to info (content + close tag)
                //Reconvert: strip htmlchars again and trim to generate xml data
                if (!isset($this->temp)) {
                    $this->temp = "";
                }
                $this->temp .= htmlspecialchars(trim($this->content))."</".$tagName.">";

                //If we've finished a mod, xmlize it an save to array $data
                if (($this->level == 4) and ($tagName == "MOD")) {
                    //Prepend XML standard header to info gathered
                    $xml_data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".$this->temp;
                    //Call to xmlize for this portion of xml data (one MOD)
                    $data = xmlize($xml_data,0);
                    $name = $data["MOD"]["#"]["NAME"]["0"]["#"];
                    if (isset($data["MOD"]["#"]["ENTRIES"]) && ($name == $this->ewiki)){
                        $this->info[]=wiki_read_xml_ewiki($data["MOD"]["#"]);
                    }

                    else if ($name == $this->ewiki){
                        $this->info[]=wiki_read_xml_wiki($data["MOD"]["#"]);
                    }

                    //Only if we've selected to restore it
                        //Reset temp
                        unset($this->temp);
                }
            }

            //Stop parsing if todo = MODULES and tagName = MODULES (en of the tag, of course)
            //Speed up a lot (avoid parse all)
            if ($tagName == "MODULES" and $this->level == 3) {
                $this->finished = true;
            }

            //Clear things
            $this->tree[$this->level] = "";
            $this->level--;
            $this->content = "";

    }
        //This is the handler to read data contents (simple accumule it)
        function characterData($parser, $data) {
            $this->content .= $data;
        }
    }

    //This function decode things to make restore multi-site fully functional
    //It does this conversions:
    //    - $@FILEPHP@$ ---|------------> $CFG->wwwroot/file.php/courseid (slasharguments on)
    //                     |------------> $CFG->wwwroot/file.php?file=/courseid (slasharguments off)
    //
    //Note: Inter-activities linking is being implemented as a final
    //step in the restore execution, because we need to have it
    //finished to know all the oldid, newid equivaleces
    function restore_decode_absolute_links($content) {

        global $CFG, $WS;
        //Now decode wwwroot and file.php calls
        $search = array ("$@FILEPHP@$");

        //Check for the status of the slasharguments config variable
        $slash = $CFG->slasharguments;

        //Build the replace string as needed

			if(isset($WS->cm->course)){
                if ($slash == 1) {
					$replace = array ($CFG->wwwroot."/file.php/".$WS->cm->course);
				} else {
					$replace = array ($CFG->wwwroot."/file.php?file=/".$WS->cm->course);
				}
					$result = str_replace($search,$replace,$content);
			} else {

                $result = $content;
			}

			if ($result != $content && $CFG->debug>7) {                                  //Debug
					echo "<br /><hr />".$content."<br />changed to<br />".$result."<hr /><br />";        //Debug
				}                                                                            //Debug

        return $result;
    }

    //funcion que devuelve el user de un autor
    function wiki_get_username ($author){
        global $CFG;
     $explode = explode (" ",$author);
        $name = strtolower($explode[0]);
        $user = get_record_sql('SELECT *
                    FROM '. $CFG->prefix.'user
                    WHERE username="'.$name.'"');
        return $user;
    }

    //funcion que mira si un elemento estÃ¯Â¿Â½ en un vector
    function wiki_contain_vector ($vector, $element){

        if ($vector != null){
            foreach ($vector as $vec) {
                if (trim($vec) == trim($element)) return true;
            }
        }

        return false;

    }

    //Return the start tag, the contents and the end tag
    function wiki_full_tag($tag,$level=0,$endline=true,$content,$to_utf=true) {

        global $CFG;

        //Here we encode absolute links
        //$content = wiki_backup_encode_absolute_links($content);

        //If enabled, we strip all the control chars from the text but tabs, newlines and returns
        //because they are forbiden in XML 1.0 specs. The expression below seems to be
        //UTF-8 safe too because it simply ignores the rest of characters.
        if (!empty($CFG->backup_strip_controlchars)) {
            $content = preg_replace("/(?(?=[[:cntrl:]])[^\n\r\t])/is","",$content);
        }

        //Start to build the tag
        $st = start_tag($tag,$level,$endline);
        $co="";
        if (empty($CFG->unicodedb)) {
            $co = preg_replace("/\r\n|\r/", "\n", utf8_encode(htmlspecialchars($content)));
        } else {
            $co = preg_replace("/\r\n|\r/", "\n", htmlspecialchars($content));
        }
        $et = end_tag($tag,0,true);

        return $st.$co.$et;
    }

    //returns the id if the user with id=$ownerid exists in the system
    function wiki_exits_ownerid($ownerid){
    	global $CFG;
        $user = get_record_sql('SELECT *
                    FROM '. $CFG->prefix.'user
                    WHERE id=\''.$ownerid.'\'');
        return $user->id;
    }
    //returns the id if the $courseid exists in the actual course
    function wiki_groupid($groupid, $courseid) {
        global $CFG;

        $group = get_record_sql('SELECT *
                    FROM '. $CFG->prefix.'groups
                    WHERE id=\''.$groupid.'\' AND courseid=\''.$courseid.'\'');

        return $group->id;

    }

	//returns the id of a group using the groupname
    function wiki_groupid_name($groupname) {

        global $CFG, $COURSE;

        $group = get_record_sql('SELECT *
                    FROM '. $CFG->prefix.'groups
                    WHERE name=\''.addslashes($groupname).'\' AND courseid='.$COURSE->id);
        return $group->id;

    }

	//Returns blockid of block $blockname
    function wiki_blockid($blockname) {
		global $CFG;
		$block = get_record_sql("SELECT * FROM ".$CFG->prefix."block WHERE name='".addslashes($blockname)."'");
		return $block->id;

	}


	//Returns false if $votedata is not in DB
	function wiki_voted($votedata) {
		global $CFG;
		return get_record_sql('SELECT * FROM '.$CFG->prefix.'wiki_votes
								WHERE pagename=\''.addslashes($votedata->pagename).'\'
								and version=\''.$votedata->version.'\'
								and username=\''.addslashes($votedata->username).'\'
                                and dfwiki=\''.addslashes($votedata->dfwiki).'\'');
	}



function wiki_synonymous_ok($data_syn){
        global $CFG;
        return get_record_sql('SELECT * FROM '.$CFG->prefix.'wiki_synonymous
                            WHERE syn=\''.$data_syn->syn.'\'
                            and dfwiki=\''.$data_syn->dfwiki.'\'');
}
//this function return the wiki schema bd in an array.
function wiki_create_schema(){
    global $CFG;

	$wiki_tables = array();
    $file = $CFG->dirroot.'/mod/wiki/db/install.xml';
    $xmldb_file = new XMLDBFile($file);
    if (!$xmldb_file->fileExists()) {
        continue;
    }
    $loaded    = $xmldb_file->loadXMLStructure();
    $structure =& $xmldb_file->getStructure();
    if ($loaded and $tables = $structure->getTables()) {
        foreach($tables as $table) {
            $wiki_tables[$table->name] = $table;
        }
    }
    $wiki_tables['block_instance'] = null;
    return $wiki_tables;
}

//this function pass a exported XML in an array
function wiki_pass_XML_to_Array($xml){
    $numRows = $xml->roottag->num_subtags();
        for ($i = 0; $i < $numRows; $i++) {
            $row = $xml->roottag->tags[$i];
            $numFields = $row->num_subtags();
            if ($i === 0) {
                for ($ii = 0; $ii < $numFields; $ii++) {
                    $field = $row->tags[$ii];
                    $arrValues[strtolower($row->name)][$i][strtolower($field->name)] = $field->cdata;
                }
            } else {
                for ($ii = 0; $ii < $numFields; $ii++) {
                    $field = $row->tags[$ii];
                    $numFields2 = $field->num_subtags();
                    for ($iii = 0; $iii < $numFields2; $iii++) {
                        $field2 = $field->tags[$iii];
                        $arrValues[strtolower($row->name)][$ii][strtolower($field2->name)] = $field2->cdata;
                     }
                }
            }
        }
        return $arrValues;
}

//this function validate the content of a wiki stored in $wiki_content and insert it in the bd
function wiki_validate_and_insert_content($wiki_content, &$WS, $id_group_template=0){

    global $CFG, $COURSE, $USER;
     $schemas = wiki_create_schema();
     $data[]= null;
     foreach ($wiki_content as $tablename => $rowstable){
        $actualTag = strtolower($tablename);
        if ($actualTag === 'synonymous' || $actualTag === 'votes'){
            $actualTag = 'wiki_'.$actualTag;
        }
        if ($actualTag === 'blocks'){
            $actualTag = 'block_instance';
        }

        foreach ($rowstable as $rownumber => $confrows){

            switch ($actualTag){
                case 'wiki':
                break;

                case 'contents':
                    foreach ($schemas['wiki_pages']->fields as $field){
                        if (array_key_exists($field->name,$confrows)){
                            $data["wiki_pages"][$rownumber][$field->name]=$confrows[$field->name];
                        }
                        else{
                            if (isset($field->Default)){
                                $data["wiki_pages"][$rownumber][$field->name]=$field->default;
                            }
                            else{
                                if ($field->notnull=="0"){
                                    $data["wiki_pages"][$rownumber][$field->name]=null;
                                }
                                else error ("The field:'$field->Field' not exist in the xml backup file, and is necesary in the wiki_pages table");
                            }
                        }
                    }
                break;

                case 'pages':
                    foreach ($schemas['wiki_'.$actualTag] as $field){
                        if (array_key_exists($field->name,$confrows)){
                            $data["wiki_history"][$rownumber][$field->name]=$confrows[$field->name];
                        }
                        else{
                            if (!isset($field->default)){
                                $data["wiki_history"][$rownumber][$field->name]=$field->default;
                            }
                            else{
                                if ($field->notnull=="0"){
                                    $data["wiki_history"][$rownumber][$field->name]=null;
                                }
                                else error ("The field:'$field->Field' not exist in the xml backup file, and is necesary in the wiki_pages table");

                            }
                        }
                    }
                break;

                //Tratamos las pÃ¯Â¿Â½ginas en caso de estar importando desde un backup, ya que no se hace distinciÃ¯Â¿Â½n entre la pÃ¯Â¿Â½gina principal y las histÃ¯Â¿Â½ricas.
                //Insertamos TODAS las pÃ¯Â¿Â½ginas
                case 'importfrombackup':
                    foreach ($schemas["wiki_pages"] as $field){
                        if (array_key_exists($field->name,$confrows)){
                            $data["wiki_import"][$rownumber][$field->name]=$confrows[$field->name];
                        }
                        else{
                            if (isset($field->default)){
                                $data["wiki_import"][$rownumber][$field->name]=$field->default;
                            }
                            else{
                                if ($field->notnull=="0"){
                                    $data["wiki_import"][$rownumber][$field->name]=null;
                                }
                                else error ("The field:'$field->Field' not exist in the import from backup xml file, and is necesary in the wiki_pages table");

                            }
                        }
                    }
                break;

                case 'wiki_synonymous' or 'wiki_votes' or 'block_instance':
                    foreach ($schemas[$actualTag] as $field){
                        if (array_key_exists($field->name,$confrows)){
                            $data[$actualTag][$rownumber][$field->name]=$confrows[$field->name];
                        }
                        else{
                            if (isset($field->default)){
                                $data[$actualTag][$rownumber][$field->name]=$field->default;
                            }
                            else{
                                if ($field->notnull=="0"){
                                    $data[$actualTag][$rownumber][$field->Field]=null;
                                }
                                else notify("The field:'$field->Field' not exist in the xml backup file, and is necesary in the $actualTag table");

                            }
                        }
                    }
                break;

                default: error ("Error in validation for the $actualtag content with the DB schema");
            }
        }
    }

    foreach($data as $bd_table => $table ){
        //tomamos el nombre de la tabla
        if (isset($table)){
                    foreach ($table as $fields){
                        $data_bd=null;
                        if(is_array($fields)){
                            $recordok=true;
                            foreach ($fields as $name => $value){
                            //Treat special cases
                                if ($WS->dfwiki->wikicourse == 0){
                                    $pageid = $WS->dfwiki->id;
                                    $pagetype = 'mod-wiki-view';
                                }else{
                                    $pageid = $WS->dfwiki->wikicourse;
                                    $pagetype = 'course-view';
                                }
                                if ($name=="dfwiki"){
                                    $data_bd->dfwiki=backup_todb($WS->dfwiki->id);
                                }else if ($name=="editable"){
                                    $data_bd->editable=backup_todb($WS->dfwiki->editable);
                                }else if ($name =="blockid"){
                                    $data_bd->blockid = wiki_blockid(backup_todb($value));
                                }else if ($name == "pageid"){
                                    $data_bd->pageid = backup_todb($pageid);
                                }else if ($name == "pagetype"){
                                    $data_bd->pagetype = backup_todb($pagetype);
                                }else if ($name == "weight"){
                                    $weight = get_record_sql('SELECT 1, max(weight) + 1 AS nextfree FROM '. $CFG->prefix .'block_instance WHERE pageid = '. $pageid.' AND position = \''. $data_bd->position .'\'');
                                    $data_bd->weight = empty($weight->nextfree) ? 0 : $weight->nextfree;
                                }else if($name == "author"){
                                	$userinfo = get_record_sql('SELECT *
                                            FROM '. $CFG->prefix.'user
                                            WHERE username=\''.$value.'\'');
                                    if($userinfo){
                                        $data_bd->author=$userinfo->username;
                                        $data_bd->userid=$userinfo->id;
                                    }else{
                                        $data_bd->author = $USER->username;
                                        $data_bd->userid = $USER->id;
                                  //  	$recordok=false;
                                  //      notify("The user $value not exists in the system, and is necesary for importing the wiki page.");
                                    }
                                }else if ($name == "userid"){
                                	//Userid is defined by the author.
                                }else if($name=="ownerid"){
                                    if($WS->dfwiki->studentmode==0){
                                        if ($value!='0'){//ownerid!=0
                                            $recordok=false;
                                            notify(" The field ownerid has defined the value:$value, and this is incompatible with the wiki on importing, because this wiki is a common student wiki.");
                                        }else{//ownerid=0
                                            $data_bd->ownerid='0';
                                        }
                                    }
                                    else{
                                        if($value!=0){
                                        	$ownerid=wiki_exits_ownerid($value);
                                            if(isset($ownerid)){
                                            	$data_bd->ownerid=$ownerid;
                                            }else{//ownerid not exists in the system
                                            	$recordok=false;
                                                notify(" The ownerid:$value, not exists in the system and is necesary for a wiki with students mode defined");
                                            }
                                        }else{//ownerid=0
                                            if(isset($data_bd->syn)){//Case synonimous
                                                $data_bd->ownerid='0';
                                            }else{//case pages
                                                $data_bd->ownerid=$data_bd->userid;
                                            }
                                        }
                                    }
                                }else if($name=="groupid"){ //name is the name of tag of xml, and your value is $value
                                	if($value==0){//content of xml
                                		if (isset($WS->cm->groupmode) && $WS->cm->groupmode==0){
                                            $data_bd->groupid=0;
                                        }
                                        else{ //if $value = grupid = 0, No groups on xml
                                        //but the course is possible to have groups

                                       	//only create wiki of current group of wiki or course.
                                            if($id_group_template==0){
                                            	$data_bd->groupid=wiki_groupid_actual();

                                            }else{
                                            	$data_bd->groupid=$id_group_template;

                                            }
                                        }
                                	}
                                    else{
                                    	if (isset($WS->cm->groupmode) && $WS->cm->groupmode==0){
                                            $recordok=false;
                                            notify("The field groupid has defined the value:$value, and this is incompatible with the wiki on importing, because this wiki is a common group wiki.");
                                            //XML for groups
                                            //current course without groups
                                            //add content of xml only one time, for the current course
                                        }
                                        //Verify if the groupid exists in the course of the wiki. If not exist the import is aborted.
                                        else{
                                            $groupid=wiki_groupid($value,$COURSE->id);
                                            if (isset ($groupid)){
                                                $data_bd->groupid=$groupid;
                                            }
                                            else{
                                            	//xml with groups
                                            	//current course with groups
                                            	//but the number ob groups of xml and current course there are diferent
                                            	//because the xml there are of any course
                                            	//is possible that this option there are not necessary
                                                $recordok=false;
                                                notify("The field groupid has defined the value:$value, and this group not exists in the course.");
                                            }
                                        }
                                    }
                                }
                                else{
                                    $data_bd->{$name}=backup_todb($value);
                                }
                            }

                            if($recordok){
                            switch ($bd_table) {

                                case "wiki_pages":
                                    if (record_exists('wiki_pages', 'pagename', $data_bd->pagename, 'dfwiki', $WS->dfwiki->id, 'groupid', $data_bd->groupid)){
                                        $infopages=wiki_treat_page($data_bd,$WS);

                                    }else{
                                        $infopages[] = $data_bd->pagename;

                                        if (!insert_record ("$bd_table",$data_bd)){

                                            notify("Can\'t insert $bd_table record with name $data_bd->pagename");
                                        }
                                        add_to_log($COURSE->id, 'wiki', "save $data_bd->pagename", "view.php?id={$WS->cm->id}&amp;page=$data_bd->pagename", "wiki edited {$WS->dfwiki->name}: $data_bd->pagename");
                                    }


                                    break;

                                case "wiki_history":

                                    if (exist_pagename_infopages($infopages, $data_bd->pagename)){
                                        echo"wiki_history:$data_bd->pagename <br>";
                                        if (!insert_record ('wiki_pages',$data_bd)){

                                            notify("Can\'t insert $bd_table record with name $data_bd->pagename");
                                    }
                                    add_to_log($COURSE->id, 'wiki', "save $data_bd->pagename", "view.php?id={$WS->cm->id}&amp;page=$data_bd->pagename", "wiki edited {$WS->dfwiki->name}: $data_bd->pagename");
                                    }else{
                                        if (record_exists('wiki_pages', 'pagename', $data_bd->pagename, 'dfwiki', $WS->dfwiki->id, 'groupid', $data_bd->groupid)){
                                            $infopages=wiki_treat_page($data_bd,$WS);

                                        }else{
                                            $infopages[] = $data_bd->pagename;

                                            if (!insert_record ("$bd_table",$data_bd)){
                                            notify("Can\'t insert $bd_table record with name $data_bd->pagename");
                                            }
                                            add_to_log($COURSE->id, 'wiki', "save $data_bd->pagename", "view.php?id={$WS->cm->id}&amp;page=$data_bd->pagename", "wiki edited {$WS->dfwiki->name}: $data_bd->pagename");
                                        }
                                    }
                                    break;
                               //import the synonymous only if not exists
                               //other sinonymous with the same name
                               //in the actual wiki.
                                case "wiki_synonymous":

                                    if (exist_pagename_infopages($infopages, $data_bd->original)){
                                        if (!wiki_synonymous_ok($data_bd)) {
                                           if(!insert_record("$bd_table",$data_bd)){

                                                notify("Can\'t insert $bd_table record with name $data_bd->syn");
                                           }
                                         }
                                     }
                                break;

                                case "block_instance":
                                    //look if dfwiki contains this block to not import
                                    $fill = get_record_sql('SELECT * FROM '. $CFG->prefix .'block_instance WHERE pageid = '. $data_bd->pageid .' AND blockid = \''. $data_bd->blockid .'\'');
                                        if(empty($fill)){
                                            if (!insert_record("$bd_table",$data_bd)){
                                                notify("Can\'t insert $bd_table record for blockid $data_bd->blockid");
                                            }
                                        }
                                        break;

                                case "wiki_votes":
                                    if(!wiki_voted($data_bd)){
                                        if (!insert_record("$bd_table",$data_bd)){
                                            notify("Can\'t insert $bd_table record for the pagename $data_bd->pagename");
                                        }
                                    }
                                    break;
                                case "wiki_import":

                                    if (record_exists('wiki_pages', 'dfwiki', $WS->dfwiki->id, 'pagename', $data_bd->pagename, 'version',$data_bd->version, 'groupid', $data_bd->groupid)){
                                       $infopages=wiki_treat_page($data_bd,$WS);

                                    add_to_log($COURSE->id, 'wiki', "save $data_bd->pagename", "view.php?id={$WS->cm->id}&amp;page=$data_bd->pagename", "wiki edited {$WS->dfwiki->name}: $data_bd->pagename");
                                    }
                                    else{
                                        $infopages[] = $data_bd->pagename;
                                        if (!insert_record('wiki_pages',$data_bd)){
                                            notify("Can\'t insert $bd_table record with name $data_bd->pagename");
                                        }
                                        add_to_log($COURSE->id, 'wiki', "save $data_bd->pagename", "view.php?id={$WS->cm->id}&amp;page=$data_bd->pagename", "wiki edited {$WS->dfwiki->name}: $data_bd->pagename");
                                    }
                                    break;

                                default :
                                    error ("Can\'t insert $bd_table record");
                            }
                            }else{
                            	notify($recordok);
                            }
                        }
                    }
        }
    }

}


function wiki_read_xml_wiki($data){
    if (isset($data['PAGES']['0']['#']['PAGE'])){
        $pages=$data['PAGES']['0']['#']['PAGE'];
        $numpages=count($pages);
        for ($i=0; $i<$numpages; $i++){
            foreach($pages[$i]['#'] as $name => $page){
                //search the groupid using the groupname
                if($name=="GROUPNAME"){
                	$groupname=$page['0']['#'];
                    if(isset($groupname)){
                        $pagedata['importfrombackup'][$i]['groupid']=wiki_groupid_name($groupname);
                    }
                    else $pagedata['importfrombackup'][$i]['groupid']=0;
                }
                else{
                    $pagedata['importfrombackup'][$i][strtolower($name)]=$page['0']['#'];
                }
            }
        }
    }
    else error("Not exists wiki pages to import in the backup file.");
    if (isset($data['SYNONYMOUS']['0']['#']['SYNONYM'])){
    	$syns=$data['SYNONYMOUS']['0']['#']['SYNONYM'];
        $numsyns=count($syns);
        for ($i=0; $i<$numsyns; $i++){
            foreach($syns[$i]['#'] as $name => $syn){
                $pagedata['synonymous'][$i][strtolower($name)]=$syn['0']['#'];
            }
            $pagedata['synonymous'][$i]['dfwiki']=0;
        }
    }
    return $pagedata;
}

function wiki_read_xml_ewiki($data){
    if (isset($data['ENTRIES']['0']['#']['ENTRY'])){

        $htmlmode=$data['HTMLMODE']['0']['#'];

        $dataentries=$data['ENTRIES']['0']['#']['ENTRY'];
        $i=0;
        foreach($dataentries as $dataentry){
            $groupid=$dataentry['#']['GROUPID']['0']['#'];
            foreach ($dataentry as $datapages){
                $datapage=$datapages['PAGES']['0']['#']['PAGE'];
                foreach ($datapage as $ewikipage){
                    $wikipages['importfrombackup'][$i]['id']=$ewikipage['#']['ID']['0']['#'];
                    $wikipages['importfrombackup'][$i]['pagename']=$ewikipage['#']['PAGENAME']['0']['#'];
                    $wikipages['importfrombackup'][$i]['version']=$ewikipage['#']['VERSION']['0']['#'];
                    $wikipages['importfrombackup'][$i]['content']=restore_decode_absolute_links(addslashes(wiki_treat_content($ewikipage['#']['CONTENT']['0']['#'])));
                    $wikipages['importfrombackup'][$i]['version']=$ewikipage['#']['VERSION']['0']['#'];
                    $pagerefs =restore_decode_absolute_links(addslashes($ewikipage['#']['REFS']['0']['#']));
                    $pagerefs=str_replace("$@LINEFEED@$","|",$pagerefs);
                    $pagerefs=str_replace("||","", $pagerefs);
                    $wikipages['importfrombackup'][$i]['refs']=wiki_treat_internal_ref($pagerefs);
                    $wikipages['importfrombackup'][$i]['lastmodified']=$ewikipage['#']['LASTMODIFIED']['0']['#'];
                    $wikipages['importfrombackup'][$i]['oldentryid'] = $data["ENTRIES"]["0"]["#"]["ENTRY"]["0"]["#"]["ID"]["0"]["#"];
                    $wikipages['importfrombackup'][$i]['oldid'] = $data["ID"]["0"]["#"];
                    switch ($htmlmode){
                        case '0':
                            $editor = 'ewiki';
                            break;
                        case '1':
                            $editor = 'ewiki';
                        break;
                            case '2':
                            $editor = 'htmleditor';
                            break;
                        default:
                            break;
                    }
                    $wikipages['importfrombackup'][$i]['editor']=$editor;
                    $wikipages['importfrombackup'][$i]['groupid']=$groupid;
                    $infouser = wiki_get_username($ewikipage['#']['AUTHOR']['0']['#']);
                    $wikipages['importfrombackup'][$i]['author']=$infouser->username;
                    $wikipages['importfrombackup'][$i]['userid']=$infouser->id;
                    $i++;
                }
            }
        }
    }
    else error("Not exists wiki pages to import in the backup file.");
    return $wikipages;
}

//treats wiki contents to convert it into dfwiki
    function wiki_treat_content($content){

        global $CFG;

        $links = null;
        $content = preg_replace("/\n/", "\r\n", $content);
        $content = preg_replace("/<br \/>/", "<br \/><br \/>", $content);
        //$content = preg_replace("/internal:/", "attach:", $content);

        //get all links and save them into an array
        $end = strpos($content, ']', 0);
        $start = strpos($content, '[', 0);
        while ($start !== false){
            $ofmoment = substr($content, $start, $end - $start + 1);
            $smilestart = substr($content, $start - 2, $start);
            $smileend = substr($content, $end - 2, $end);
                if ((!wiki_contain_vector($links, $ofmoment)) && ($smileend != '}-') && ($smilestart != '8-')){
                    $link = substr($content, $start, $end - $start + 1);
                    $pieces = explode('"', $link);
                    if (count($pieces) !== 3){
                    $links[] = $link;
                    }
                }
                if(($smileend == '}-') && ($smilestart != '8-')) $end = strpos($content, ']', $start+1);
                else if(($smileend != '}-') && ($smilestart == '8-')) $start = strpos($content, '[', $start+1);
                else{
                    $start = strpos($content, '[', $start+1);
                    $end = strpos($content, ']', $start+1);
                }
        }

        //treat every link
        if($links != null){
            foreach ($links as $link) {
                $type = strpos($link, 'http://');
                if ($type === false){
                    //it's an internal one
                    $link2 = substr($link, 1, strlen($link)-2);
                    $exist = strpos($link2, '|');
                    if ($exist === false){
                        //wikipedia special case
                        $existwikipedia = strpos($link2, 'wikipedia:');
                        $existWikipedia = strpos($link2, 'Wikipedia:');
                        if (($existwikipedia === false) && ($existWikipedia === false)){
                            $link4 = trim($link2);
                            $link3 = "[[$link4]]";
                            $content = str_replace($link, $link3, $content);
                        }else{
                            $link4 = trim($link2);
                            $extensionwikipedia = explode(":",$link4);
                            $numwikipedia = count($extensionwikipedia)-1;
                            if(count($extensionwikipedia) == 1) $namewikipedia = '';
                            else $namewikipedia = trim($extensionwikipedia[$numwikipedia]);
                            $link3 = "[http://www.wikipedia.com/wiki.cgi?$namewikipedia]";
                            $content = str_replace($link, $link3, $content);
                        }
                    }else{
                        $existwikipedia = strpos($link2, 'wikipedia:');
                        $existWikipedia = strpos($link2, 'Wikipedia:');
                        if (($existwikipedia === false) && ($existWikipedia === false)){
                            $extension = explode("|",$link2);
                            $num1 = count($extension)-1;
                            $num2 = count($extension)-2;
                            $name1 = trim($extension[$num1]);
                            $name2 = trim($extension[$num2]);
                            $link3 = "[[$name1|$name2]]";
                            $content = str_replace($link, $link3, $content);
                        }else{
                            $extension = explode("|",$link2);
                            $num1 = count($extension)-1;
                            $num2 = count($extension)-2;
                            $name1 = trim($extension[$num1]);
                            $name2 = trim($extension[$num2]);
                            $extensionwikipedia = explode(":",$name2);
                            $numwikipedia = count($extensionwikipedia)-1;
                            if(count($extensionwikipedia) == 1) $namewikipedia = '';
                            else $namewikipedia = trim($extensionwikipedia[$numwikipedia]);
                            $link3 = "[http://www.wikipedia.com/wiki.cgi?$namewikipedia $name1]";
                            $content = str_replace($link, $link3, $content);
                        }
                    }
                }
            }
        }

        return $content;

    }

    function wiki_treat_internal_ref ($refs){

        $ref = "";
        if ($refs != ""){

            $extension = explode('|',$refs);
            $num = count($extension);

            for ($i = 0; $i < $num; $i++) {
                if(stripos ($extension[$i],'http:')===false){
                    $ref.= $extension[$i];
                    $ref.= '|';
                }
            }
            if ($ref != "") $ref.= '|';
        }

        $ref = str_replace("||","", $ref);

        return $ref;

    }

    //Return the first groupid with the actual user is member in the actual course.
    function wiki_groupid_actual(){
    	global $CFG,$USER,$COURSE;
        $groups=get_records_sql('SELECT *
                FROM '. $CFG->prefix.'groups_members
                WHERE userid=\''.$USER->id.'\'');
        if (!empty($groups)){
            foreach($groups as $group){
        	   if(record_exists("groups",'id',$group->groupid,'courseid',$COURSE->id)){
        	       $info=get_record_sql('SELECT * FROM '. $CFG->prefix .'groups WHERE id = '. $group->id .' AND courseid = \''. $COURSE->id .'\'');
                   echo "estamos definiendo groupid=$info->id <p>";
                   return $info->id;
        	   }
            }
        }
        return 0;
    }


?>