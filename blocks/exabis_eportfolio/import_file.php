<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once dirname(__FILE__).'/inc.php';
require_once dirname(__FILE__).'/lib/minixml.inc.php';
require_once dirname(__FILE__).'/lib/class.scormparser.php';


$courseid = optional_param("courseid", 0, PARAM_INT);
$confirm = optional_param("confirm", 0, PARAM_INT);

$context = get_context_instance(CONTEXT_SYSTEM);

require_login($courseid);
require_capability('block/exabis_eportfolio:use', $context);
require_capability('block/exabis_eportfolio:import', $context);

if (! $course = get_record("course", "id", $courseid) ) {
	error("That's an invalid course id");
}

$strimport = get_string("import","block_exabis_eportfolio");

require_once dirname(__FILE__).'/lib/edit_form.php';
$exteditform = new scorm_upload_form(null, null);

$imported = false;
$returnurl = $CFG->wwwroot.'/blocks/exabis_eportfolio/exportimport.php?courseid='.$courseid;
if ($exteditform->is_cancelled()){
	redirect($returnurl);
} else if ($exteditform->no_submit_button_pressed()) {
	die("nosubmitbutton");
	//no_submit_button_actions($exteditform, $sitecontext);
} else if ($fromform = $exteditform->get_data()){
	$imported = true;
	$dir = portfolio_file_area_name();
	if ($exteditform->save_files($dir) and $newfilename = $exteditform->get_new_filename()) {
		if(ereg("^(.*).zip$", $newfilename, $regs)) {
			if ($scormdir = make_upload_directory(import_file_area_name())) {
				$unzip_dir = $scormdir . '/' . $regs[1];

				if(is_dir($unzip_dir)) {
					$i = 0;
					do {
						$i++;
						$unzip_dir = $scormdir . '/' . $regs[1] . $i;
					} while(is_dir($unzip_dir));
				}

				if (mkdir($unzip_dir)) {
					if (unzip_file($CFG->dataroot . '/' . $dir . '/' . $newfilename, $unzip_dir, false)) {
						// parsing of file
						$scormparser = new SCORMParser();
						$scormTree = $scormparser->parse($unzip_dir . '/imsmanifest.xml');

						// write warnings and errors
						if($scormparser->isWarning()) {
							error($scormparser->getWarning());
						}
						else if($scormparser->isError()) {
							error($scormparser->getError());
						}
						else {
							foreach($scormTree as $organization) {
								switch($organization["data"]["identifier"]) {
									case "DATA":      if(isset($organization["items"][0]["data"]["url"])) {
														 $filepath = $unzip_dir . '/' . clean_param($organization["items"][0]["data"]["url"], PARAM_PATH);
														 if(is_file($filepath)) {
															import_user_description($filepath);
														 }
													  }
													  break;
									case "PORTFOLIO": import_structure($unzip_dir, $organization["items"],$course);
													  break;
									default:          import_files($unzip_dir, $organization["items"]);
													  break;
								}
							}
						}
					}
					else {
						error(get_string("couldntextractscormfile", "block_exabis_eportfolio"));
					}
				}
				else {
					error(get_string("couldntcreatetempdir", "block_exabis_eportfolio"));
				}
			}
			else {
				error(get_string("couldntcreatetempdir", "block_exabis_eportfolio"));
			}
		}
		else {
			error(get_string("scormhastobezip", "block_exabis_eportfolio"));
		}
	}
	else {
		error(get_string("uploadfailed", "block_exabis_eportfolio"));
	}
}

block_exabis_eportfolio_print_header("exportimportimport");

echo "<br />";

$form_data = new stdClass();
$form_data->courseid = $courseid;
$exteditform->set_data($form_data);
if($imported) {
	notify(get_string("success", "block_exabis_eportfolio"));
}
else {
	$exteditform->display();
}

print_footer($course);

die;

function import_files($unzip_dir, $structures, $i = 0, $previd=NULL) {
	// this function is for future use.
}

function portfolio_file_area_name() {
    global $CFG, $USER;
	return "exabis_eportfolio/temp/import/{$USER->id}";
}

function import_user_description($file) {
	global $USER;
	$content = file_get_contents($file);

	if(($startDesc = strpos($content,  '<!--###BOOKMARK_PERSONAL_DESC###-->')) !== false) {
	   	$startDesc+=strlen('<!--###BOOKMARK_PERSONAL_DESC###-->');
	   	if(($endDesc = strpos($content, '<!--###BOOKMARK_PERSONAL_DESC###-->', $startDesc)) !== false) {
	        if(record_exists('block_exabeporuser', 'user_id', $USER->id)) {
	        	$record = get_record('block_exabeporuser', 'user_id', $USER->id);
	        	$record->description = block_exabis_eportfolio_clean_text(substr($content, $startDesc, $endDesc-$startDesc));
		        $record->persinfo_timemodified = time();
		        if (! update_record('block_exabeporuser', $record)) {
	                error(get_string("couldntupdatedesc", "block_exabis_eportfolio"));
	            }
	        }
	        else {
                $newentry = new stdClass();
		        $newentry->description =  addslashes(substr($content, $startDesc, $endDesc-$startDesc));
		        $newentry->persinfo_timemodified = time();
		        $newentry->id = $USER->id;
		        if (! insert_record('block_exabeporuser', $newentry)) {
	                error(get_string("couldntinsertdesc", "block_exabis_eportfolio"));
	            }
	        }
		}
	}
}

function import_structure($unzip_dir, $structures,$course, $i = 0, $previd=NULL) {
	global $USER, $COURSE;
	foreach($structures as $structure) {
		if(isset($structure["data"])) {
			if(isset($structure["data"]["title"]) &&
			   isset($structure["data"]["url"]) &&
			   ($previd != NULL)) {
				insert_entry($unzip_dir, $structure["data"]["url"], $structure["data"]["title"], $previd,$course);
			}
			else if(isset($structure["data"]["title"])) {
				if(is_null($previd)) {
	    			if(count_records_select("block_exabeporcate","name='".block_exabis_eportfolio_clean_title($structure["data"]["title"])."' AND userid='$USER->id' AND pid=0") == 0) {
	    				$newentry = new stdClass();
						$newentry->name = block_exabis_eportfolio_clean_title($structure["data"]["title"]);
						$newentry->timemodified = time();
						$newentry->course = $COURSE->id;
	                    $newentry->userid = $USER->id;
	                    //$newentry->pid = $previd;
	                    
	                    if (! $entryid = insert_record("block_exabeporcate", $newentry)) {
	                		notify("Could not insert category!");
	                    }
	    			}
	    			else {
	    				$entry = get_record_select("block_exabeporcate","name='".block_exabis_eportfolio_clean_title($structure["data"]["title"])."' AND userid='$USER->id' AND pid=0");
	    				$entryid = $entry->id;
	    			}
	    		}
	    		else {
	    			if(count_records_select("block_exabeporcate","name='".block_exabis_eportfolio_clean_title($structure["data"]["title"])."' AND userid='$USER->id' AND pid='$previd'") == 0) {
						$newentry->name = block_exabis_eportfolio_clean_title($structure["data"]["title"]);
						$newentry->timemodified = time();
						$newentry->course = $COURSE->id;
	                    $newentry->userid = $USER->id;
	                    $newentry->pid = $previd;

	                    if (! $entryid = insert_record("block_exabeporcate", $newentry)) {
	                		notify("Could not insert category!");
	                    }
	    			}
	    			else {
	    				$entry = get_record_select("block_exabeporcate","name='".block_exabis_eportfolio_clean_title($structure["data"]["title"])."' AND userid='$USER->id' AND pid='$previd'");
	    				$entryid = $entry->id;
	    			}
	    		}
			}
		}
		if(isset($structure["items"]) && isset($entryid)) {
			import_structure($unzip_dir, $structure["items"],$course, $i+1,$entryid);
		}
	}
}

function block_exabis_eportfolio_clean_title($title) {
	return clean_param(addslashes($title), PARAM_TEXT);
}

function block_exabis_eportfolio_clean_url($url) {
	return clean_param(addslashes($url), PARAM_URL);
}

function block_exabis_eportfolio_clean_text($text) {
	return addslashes($text);
}

function block_exabis_eportfolio_clean_path($text) {
	return clean_param($text, PARAM_PATH);
}


function insert_entry($unzip_dir, $url, $title, $category,$course) {
	global $USER, $CFG, $COURSE;
	$filePath = $unzip_dir . '/' . $url;

	$content = file_get_contents($filePath);
	if((($startUrl = strpos($content,  '<!--###BOOKMARK_EXT_URL###-->')) !== false)&&
	   (($startDesc = strpos($content, '<!--###BOOKMARK_EXT_DESC###-->')) !== false)) {
	   	$startUrl+=strlen('<!--###BOOKMARK_EXT_URL###-->');
	   	$startDesc+=strlen('<!--###BOOKMARK_EXT_DESC###-->');
	   	if((($endUrl = strpos($content,  '<!--###BOOKMARK_EXT_URL###-->', $startUrl)) !== false)&&
		   (($endDesc = strpos($content, '<!--###BOOKMARK_EXT_DESC###-->', $startDesc)) !== false)) {
            $new = new stdClass();
	   	    $new->userid       = $USER->id;
		    $new->categoryid     = $category;
		    $new->name         = block_exabis_eportfolio_clean_title($title);
		    $new->url          = block_exabis_eportfolio_clean_url(substr($content, $startUrl, $endUrl-$startUrl));
		    $new->intro        = block_exabis_eportfolio_clean_text(substr($content, $startDesc, $endDesc-$startDesc));
		    $new->timemodified = time();
			$new->type = 'link';
		    $new->course = $COURSE->id;
		    
		    if($new->id = insert_record('block_exabeporitem', $new)) {
			    get_comments($content, $new->id, 'block_exabeporitemcomm');
			}
	   		else {
				notify(get_string("couldntinsert", "block_exabis_eportfolio", $title));
	   		}
		}
		else {
			notify(get_string("filetypenotdetected", "block_exabis_eportfolio", array("filename" => $url, "title" => $title)));
		}
	}
	else if((($startUrl = strpos($content, '<!--###BOOKMARK_FILE_URL###-->')) !== false)&&
	   (($startDesc = strpos($content,     '<!--###BOOKMARK_FILE_DESC###-->')) !== false)) {
	   	$startUrl+=strlen('<!--###BOOKMARK_FILE_URL###-->');
	   	$startDesc+=strlen('<!--###BOOKMARK_FILE_DESC###-->');
	   	if((($endUrl = strpos($content,  '<!--###BOOKMARK_FILE_URL###-->', $startUrl)) !== false)&&
		   (($endDesc = strpos($content, '<!--###BOOKMARK_FILE_DESC###-->', $startDesc)) !== false)) {
		   	$linkedFileName = block_exabis_eportfolio_clean_path(substr($content, $startUrl, $endUrl-$startUrl));
		   	$linkedFilePath = dirname($filePath) . '/' . $linkedFileName;
		   	if(is_file($linkedFilePath)) {
                $new = new stdClass();
		   	    $new->userid       = $USER->id;
			    $new->categoryid     = $category;
			    $new->name         = block_exabis_eportfolio_clean_title($title);
			    $new->intro        = block_exabis_eportfolio_clean_text(substr($content, $startDesc, $endDesc-$startDesc));
			    $new->timemodified = time();
				$new->type = 'file';
		    	$new->course       = $COURSE->id;
		    	// not necessary
		    	//$new->url          = str_replace($CFG->wwwroot, "", $_SERVER["HTTP_REFERER"]);
		    	
		   		if ($new->id = insert_record('block_exabeporitem', $new)) {
		   			$destination = block_exabis_eportfolio_file_area_name($new);
		   			if(make_upload_directory($destination, false)) {
						$destination = $CFG->dataroot . '/' . $destination;
						$destination_name = handle_filename_collision($destination, $linkedFileName);
						if(copy($linkedFilePath, $destination . '/' . $destination_name)) {
							set_field("block_exabeporitem", "attachment", $destination_name, "id", $new->id);
						}
						else {
							notify(get_string("couldntcopyfile", "block_exabis_eportfolio", $title));
						}
		   			}
		   			else {
						notify(get_string("couldntcreatedirectory", "block_exabis_eportfolio", $title));
		   			}
			    	get_comments($content, $new->id, 'block_exabeporitemcomm');
		   		}
		   		else {
					notify(get_string("couldntinsert", "block_exabis_eportfolio", $title));
		   		}
		   	}
			else {
				notify(get_string("linkedfilenotfound", "block_exabis_eportfolio", array("filename" => $linkedFileName, "url" => $url, "title" => $title)));
			}
		}
		else {
			notify(get_string("filetypenotdetected", "block_exabis_eportfolio", array("filename" => $url, "title" => $title)));
		}
	}
	else if( (($startDesc = strpos($content, '<!--###BOOKMARK_NOTE_DESC###-->')) !== false) ) {
	   	$startDesc+=strlen('<!--###BOOKMARK_NOTE_DESC###-->');
	   	if((($endDesc = strpos($content, '<!--###BOOKMARK_NOTE_DESC###-->', $startDesc)) !== false) ) {
	   	    $new = new stdClass();
	   	    $new->userid       = $USER->id;
		    $new->categoryid     = $category;
		    $new->name         = block_exabis_eportfolio_clean_title($title);
		    $new->intro        = block_exabis_eportfolio_clean_text(substr($content, $startDesc, $endDesc-$startDesc));
		    $new->timemodified = time();
			$new->type = 'note';
		    $new->course = $COURSE->id;
		    
		    if($new->id = insert_record('block_exabeporitem', $new)) {
			    get_comments($content, $new->id, 'block_exabeporitemcomm');
			}
	   		else {
				notify(get_string("couldntinsert", "block_exabis_eportfolio", $title));
	   		}
		}
		else {
			notify(get_string("filetypenotdetected", "block_exabis_eportfolio", array("filename" => $url, "title" => $title)));
		}
	}
	else {
		notify(get_string("filetypenotdetected", "block_exabis_eportfolio", array("filename" => $url, "title" => $title)));
	}
}

function get_comments($content, $bookmarkid, $table) {
	global $USER;
	$i = 1;
	$comment = "";
	while((($startAuthor  = strpos($content, '<!--###BOOKMARK_COMMENT('.$i.')_AUTHOR###-->' )) !== false) &&
	      (($startTime    = strpos($content, '<!--###BOOKMARK_COMMENT('.$i.')_TIME###-->'   )) !== false) &&
	      (($startContent = strpos($content, '<!--###BOOKMARK_COMMENT('.$i.')_CONTENT###-->')) !== false)) {
	   	$startAuthor+=strlen('<!--###BOOKMARK_COMMENT('.$i.')_AUTHOR###-->');
	   	$startTime+=strlen('<!--###BOOKMARK_COMMENT('.$i.')_TIME###-->');
	   	$startContent+=strlen('<!--###BOOKMARK_COMMENT('.$i.')_CONTENT###-->');

	   	if((($endAuthor  = strpos($content, '<!--###BOOKMARK_COMMENT('.$i.')_AUTHOR###-->', $startAuthor  )) !== false) &&
	      (($endTime    = strpos($content, '<!--###BOOKMARK_COMMENT('.$i.')_TIME###-->', $startTime       )) !== false) &&
	      (($endContent = strpos($content, '<!--###BOOKMARK_COMMENT('.$i.')_CONTENT###-->', $startContent )) !== false)) {

		    $commentAuthor =  block_exabis_eportfolio_clean_text(substr($content, $startAuthor, $endAuthor-$startAuthor));
		    $commentTime =  block_exabis_eportfolio_clean_text(substr($content, $startTime, $endTime-$startTime));
		    $commentContent =  block_exabis_eportfolio_clean_text(substr($content, $startContent, $endContent-$startContent));

		    $comment .= '<span class="block_eportfolio_commentauthor">'.$commentAuthor.'</span> '.$commentTime.'<br />'.$commentContent.'<br /><br />';
	    }
	    else {
			notify(get_string("couldninsertcomment","block_exabis_eportfolio"));
	    }
	   	$i++;
	}
	if($comment != "") {
	    $new = new stdClass();
	    $new->userid       = $USER->id;
	    $new->timemodified = time();
		$new->bookmarkid   = $bookmarkid;
		$new->entry        = get_string("importedcommentsstart","block_exabis_eportfolio") . $comment . get_string("importedcommentsend","block_exabis_eportfolio");
		if (!insert_record($table, $new)) {
			notify(get_string("couldninsertcomment","block_exabis_eportfolio"));
		}
	}
}

function handle_filename_collision($destination, $filename) {
    if (file_exists($destination .'/'. $filename)) {
        $parts = explode('.', $filename);
        $lastPart = array_pop($parts);
        $firstPart = implode('.', $parts);
    	$i = 0;
    	do {
    		$i++;
			$filename = $firstPart . '_' . $i . '.' . $lastPart;
    	} while(file_exists($destination .'/'. $filename));
    }
    return $filename;
}

function import_file_area_name() {
	global $USER, $CFG, $COURSE;
	
	return "exabis_eportfolio/temp/import/{$USER->id}";
}
