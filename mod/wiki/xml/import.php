<?php //Created by Antonio Castaï¿½o & Juan Castaï¿½o

//  All the Moodle-specific stuff is in this top section
//  Configuration and access control occurs here.
//  Must define:  USER, basedir, baseweb, html_header and html_footer
//  USER is a persistent variable using sessions

    require('../../../config.php');
    require($CFG->libdir.'/filelib.php');
    require_once ('../../../backup/lib.php');
    //html functions
	require_once ($CFG->dirroot.'/mod/wiki/weblib.php');



    $id      = required_param('id', PARAM_INT);
	$cm      = required_param('cm', PARAM_INT);
    $file    = optional_param('file', '', PARAM_PATH);
    $wdir    = optional_param('wdir', '', PARAM_PATH);
    $action  = optional_param('action', '', PARAM_ACTION);
    $name    = optional_param('name', '', PARAM_FILE);
    $oldname = optional_param('oldname', '', PARAM_FILE);
    $save    = optional_param('save', 0, PARAM_BOOL);
    $choose  = optional_param('choose', '', PARAM_CLEAN);
    $confirm = optional_param('confirm', 0, PARAM_BOOL);


    if ($choose) {
        if (count(explode('.', $choose)) != 2) {
            error('Incorrect format for choose parameter');
        }
    }

    if (! $course = get_record("course", "id", $id) ) {
        error("That's an invalid course id");
    }

    require_login($course->id);

    $context = get_context_instance(CONTEXT_MODULE,$course->id);
	require_capability('mod/wiki:adminactions',$context);

    function html_footer() {

        global $course, $choose;

        if ($choose) {
            wiki_table_end();
        } else {
            wiki_table_end();
            print_footer($course);
        }
    }

    function html_header($course, $wdir, $formfield=""){
        global $CFG, $ME, $choose;

        if (! $site = get_site()) {
            error("Invalid site!");
        }

        $strfiles = get_string("viewexportedfiles", 'wiki');

        if ($wdir == "/") {
            $fullnav = "$strfiles";
        } else {
            $dirs = explode("/", $wdir);
            $numdirs = count($dirs);
            $link = "";
            $navigation = "";
            for ($i=1; $i<$numdirs-1; $i++) {
               $navigation .= " -> ";
               $link .= "/".urlencode($dirs[$i]);
               $navigation .= "<a href=\"".$ME."?id=$course->id&amp;wdir=$link&amp;choose=$choose\">".$dirs[$i]."</a>";
            }
            $fullnav = "<a href=\"".$ME."?id=$course->id&amp;wdir=/&amp;choose=$choose\">$strfiles</a> $navigation -> ".$dirs[$numdirs-1];
        }


        if ($choose) {
            print_header();

            $chooseparts = explode('.', $choose);

            ?>
            <script language="javascript" type="text/javascript">
            <!--
            function set_value(txt) {
                opener.document.forms['<?php echo $chooseparts[0]."'].".$chooseparts[1] ?>.value = txt;
                window.close();
            }
            -->
            </script>

            <?php
            $fullnav = str_replace('->', '&raquo;', "$course->shortname -> $fullnav");
            echo '<div id="nav-bar">'.$fullnav.'</div>';

            if ($course->id == $site->id) {
                print_heading(get_string("publicsitefileswarning"), "center", 2);
            }

        } else {

            if ($course->id == $site->id) {
                print_header("$course->shortname: $strfiles", "$course->fullname",
                             "<a href=\"../index.php?id=$course->id\">".get_string("modulenameplural", 'wiki').
                             "</a> -> $fullnav", $formfield);

                print_heading(get_string("publicsitefileswarning"), "center", 2);

            } else {
                print_header("$course->shortname: $strfiles", "$course->fullname",
                             "<a href=\"../../../course/view.php?id=$course->id\">$course->shortname".
                             "</a> -> <a href=\"../index.php?id=$course->id\">".get_string("modulenameplural", 'wiki')."</a> -> $fullnav", $formfield);
            }
        }

		$prop = null;
		$prop->border = "0";
		$prop->spacing = "3";
		$prop->padding = "3";
		$prop->width = "640";
		$prop->class = "boxaligncenter";
		$prop->colspantd = "2";
		wiki_table_start($prop);
    }


    if (! $basedir = make_upload_directory("$course->id")) {
        error("The site administrator needs to fix the file permissions");
    }

    $baseweb = $CFG->wwwroot;

//  End of configuration and access control

    if (empty($wdir)) {
        $wdir="/";
    }
    if (($wdir != '/' and detect_munged_arguments($wdir, 0))
      or ($file != '' and detect_munged_arguments($file, 0))) {
        $message = "Error: Directories can not contain \"..\"";
        $wdir = "/";
        $action = "";
    }

    if ($wdir == "/backupdata") {
        if (! make_upload_directory("$course->id/backupdata")) {   // Backup folder
            error("Could not create backupdata folder.  The site administrator needs to fix the file permissions");
        }
    }

    switch ($action) {

        case "upload":
            html_header($course, $wdir);
            require_once($CFG->dirroot.'/lib/uploadlib.php');
            if ($save and confirm_sesskey()) {
                $course->maxbytes = 0;  // We are ignoring course limits
                $um = new upload_manager('userfile',false,false,$course,false,0);
                $dir = "$basedir$wdir";
                if ($um->process_file_uploads($dir)) {
                    notify(get_string('uploadedfile'));
                }
                // um will take care of error reporting.
                displaydir($wdir);
            } else {
                $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);
                $filesize = display_size($upload_max_filesize);

                $struploadafile = get_string("uploadafile");
                $struploadthisfile = get_string("uploadthisfile");
                $strmaxsize = get_string("maxsize", "", $filesize);
                $strcancel = get_string("cancel");

				$out = $struploadafile." (".$strmaxsize.") --> ";
                $out .= wiki_b($wdir, '',true);
				wiki_paragraph($out);
				$prop = null;
				$prop->colspantd = "2";
                wiki_table_start($prop);
                	$prop = null;
					$prop->action = "index.php";
					$prop->method = "post";
					$prop->enctype = "multipart/form-data";
					wiki_form_start($prop);
						wiki_div_start();

               				$prop = null;
							$prop->name = "choose";
							$prop->value = $choose;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "id";
							$prop->value = $id;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "wdir";
							$prop->value = $wdir;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "action";
							$prop->value = "upload";
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "sesskey";
							$prop->value = $USER->sesskey;
							wiki_input_hidden($prop);

                			upload_print_form_fragment(1,array('userfile'),null,false,null,$upload_max_filesize,0,false);

							$prop = null;
							$prop->name = "save";
							$prop->value = $struploadthisfile;
							wiki_input_submit($prop);

						wiki_div_end();
					wiki_form_end();

					$prop = null;
                	$prop->class = "textcenter";
                	wiki_change_row($prop);

					$prop = null;
					$prop->action = "index.php";
					$prop->method = "get";
					wiki_form_start($prop);
						$prop = null;
						$prop->class = "textcenter";
						wiki_div_start($prop);

							$prop = null;
							$prop->name = "choose";
							$prop->value = $choose;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "id";
							$prop->value = $id;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "wdir";
							$prop->value = $wdir;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "action";
							$prop->value = "cancel";
							wiki_input_hidden($prop);

							$prop = null;
							$prop->value = $strcancel;
							wiki_input_submit($prop);

						wiki_div_end();
					wiki_form_end();
				wiki_table_end();
            }
            html_footer();
            break;

        case "delete":
            if (!empty($confirm) and confirm_sesskey()) {
                html_header($course, $wdir);
                foreach ($USER->filelist as $file) {
                    $fullfile = $basedir.$file;
                    if (! fulldelete($fullfile)) {
                        echo "<br />Error: Could not delete: $fullfile";
                    }
                }
                clearfilelist();
                displaydir($wdir);
                html_footer();

            } else {
                html_header($course, $wdir);
                if (setfilelist($_POST)) {

					$prop = null;
            		$prop->class = "textcenter";
                	wiki_paragraph(get_string("deletecheckwarning").":",$prop);

                    $prop = null;
                    $prop->class = "box generalbox generalboxcontent boxaligncenter";
                    wiki_div_start($prop);
                    printfilelist($USER->filelist);
                    wiki_div_end();
                    wiki_br();

                    notice_yesno (get_string("deletecheckfiles"),
                                "index.php?id=$id&amp;wdir=$wdir&amp;action=delete&amp;confirm=1&amp;sesskey=$USER->sesskey",
                                "index.php?id=$id&amp;wdir=$wdir&amp;action=cancel");
                } else {
                    displaydir($wdir);
                }
                html_footer();
            }
            break;

        case "rename":
            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);
                $name = clean_filename($name);

                $extension = explode("/",$path);
                $num2 = count($extension)-2;
                $num3 = count($extension)-4;
                $newname = "$CFG->dataroot/$extension[$num3]/exportedfiles/$extension[$num2]";
                $new = "$newname/$name";
                $old = $path;
                if (file_exists($new)) {
                    echo "Error: $name already exists!";
                } else if (!rename($old, $new)) {
                    echo "Error: could not rename $oldname to $name";
                }
                displaydir($wdir);

            } else {
                $strrename = get_string("rename");
                $strcancel = get_string("cancel");
                $strrenamefileto = get_string("renamefileto", "moodle", $file);
                html_header($course, $wdir, "form.name");
                wiki_paragraph($strrenamefileto.":");
                wiki_table_start();
                	$prop = null;
					$prop->action = "import.php";
					$prop->method = "post";
					$prop->id = "form";
					wiki_form_start($prop);
						wiki_div_start();
							$prop = null;
							$prop->name = "choose";
							$prop->value = $choose;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "id";
							$prop->value = $id;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "cm";
							$prop->value = $cm;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "wdir";
							$prop->value = $wdir;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "action";
							$prop->value = "rename";
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "oldname";
							$prop->value = $file;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "path";
							$prop->value = $path;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "sesskey";
							$prop->value = $USER->sesskey;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "name";
							$prop->value = $file;
							$prop->size = "35";
							wiki_input_text($prop);

							$prop = null;
							$prop->value = $strrename;
							wiki_input_submit($prop);

						wiki_div_end();
					wiki_form_end();
					wiki_change_column();

					$prop = null;
					$prop->action = "import.php";
					$prop->method = "get";
					wiki_form_start($prop);
						$prop = null;
						$prop->class = "textcenter";
						wiki_div_start($prop);

							$prop = null;
							$prop->name = "choose";
							$prop->value = $choose;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "id";
							$prop->value = $id;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "cm";
							$prop->value = $cm;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "wdir";
							$prop->value = $wdir;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "action";
							$prop->value = "cancel";
							wiki_input_hidden($prop);

							$prop = null;
							$prop->value = $strcancel;
							wiki_input_submit($prop);

						wiki_div_end();
					wiki_form_end();
                wiki_table_end();
            }
            html_footer();
            break;

        case "cancel":
            clearfilelist();

        default:
            html_header($course, $wdir);
            displaydir($wdir);
            html_footer();
            break;
}


/// FILE FUNCTIONS ///////////////////////////////////////////////////////////


function setfilelist($VARS) {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";

    $count = 0;
    foreach ($VARS as $key => $val) {
        if (substr($key,0,4) == "file") {
            $count++;
            $val = rawurldecode($val);
            if (!detect_munged_arguments($val, 0)) {
                $USER->filelist[] = $val;
            }
        }
    }
    return $count;
}

function clearfilelist() {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";
}


function printfilelist($filelist) {
    global $CFG, $basedir;

    foreach ($filelist as $file) {
        if (is_dir($basedir.$file)) {
            echo "<img src=\"$CFG->pixpath/f/folder.gif\" height=\"16\" width=\"16\" alt=\"\" /> $file<br />";
            $subfilelist = array();
            $currdir = opendir($basedir.$file);
            while (false !== ($subfile = readdir($currdir))) {
                if ($subfile <> ".." && $subfile <> ".") {
                    $subfilelist[] = $file."/".$subfile;
                }
            }
            printfilelist($subfilelist);

        } else {
            $icon = mimeinfo("icon", $file);
            echo "<img src=\"$CFG->pixpath/f/$icon\"  height=\"16\" width=\"16\" alt=\"\" /> $file<br />";
        }
    }
}


function print_cell($alignment='center', $text='&nbsp;', $class='') {
    if ($class) {
        $class = ' class="'.$class.' nwikileftnow"';
    }
    echo '<td align="'.$alignment.'"'.$class.'>'.$text.'</td>';
}


function displaydir ($wdir) {
//  $wdir == / or /a or /a/b/c/d  etc

    global $basedir;
    global $id, $cm;
    global $USER, $CFG;
    global $choose;

    $fullpath = $basedir.$wdir;

    check_dir_exists($fullpath,true);

    $directory = opendir($fullpath);             // Find all files
    while (false !== ($file = readdir($directory))) {
        if ($file == "." || $file == "..") {
            continue;
        }

        if (is_dir($fullpath."/".$file)) {
            $dirlist[] = $file;
        } else {
            $filelist[] = $file;
        }
    }
    closedir($directory);

    $strname = get_string("name");
    $strsize = get_string("size");
    $strmodified = get_string("modified");
    $straction = get_string("action");
    $struploadafile = get_string("uploadafile");
    $strwithchosenfiles = get_string("withchosenfiles");
    $strdeletecompletely = get_string("deletecompletely");
    $strrename = get_string("rename");
    $strimport = get_string("import", 'wiki');
    $strchoose   = get_string("choose");

	$prop = null;
	$prop->action = "import.php";
	$prop->method = "post";
	$prop->id = "dir";
	wiki_form_start($prop);
		wiki_div_start();

			$prop = null;
			$prop->name = "choose";
			$prop->value = $choose;
			wiki_input_hidden($prop);
			wiki_hr();

			$prop = null;
			$prop->border = "0";
			$prop->spacing = "2";
			$prop->padding = "2";
			$prop->width = "640";
			$prop->class = "files";
			$prop->header = true;
			$prop->styleth = "width:5%";
			wiki_table_start($prop);

				$prop = null;
				$prop->header = true;
				$prop->align = "left";
				$prop->class = "header name";
				wiki_change_column($prop);
				echo $strname;

				$prop = null;
				$prop->header = true;
				$prop->align = "right";
				$prop->class = "header size";
				wiki_change_column($prop);
				echo $strsize;

				$prop = null;
				$prop->header = true;
				$prop->align = "right";
				$prop->class = "header date";
				wiki_change_column($prop);
				echo $strmodified;

				$prop = null;
				$prop->header = true;
				$prop->align = "right";
				$prop->class = "header commands";
				wiki_change_column($prop);
				echo $straction;

			    if ($wdir == "/") {
			        $wdir = "";
			    }
			    if (!empty($wdir)) {
			        $dirlist[] = '..';
			    }

			    $count = 0;
				$header = true;

			    if (!empty($dirlist)) {
			        asort($dirlist);
			        foreach ($dirlist as $dir) {

			        	if ($header){
			            	$header = false;
					        $prop = null;
			               	$prop->header = true;
						} else {
				        	$prop = null;
						}

			            if ($dir == '..') {
			                $fileurl = rawurlencode(dirname($wdir));

			                $prop->style = "white-space: nowrap;";
			       			$prop->align = "center";
			       			$prop->classtr = "folder";
							wiki_change_row($prop);
							echo '&nbsp;';
							$prop = null;
							$prop->class = 'name';
							$prop->align = 'left';
							$prop->style = "white-space: nowrap;";
							wiki_change_column($prop);
							$prop = null;
							$prop->src = $CFG->pixpath.'/f/parent.gif';
							$prop->height = "16";
							$prop->width = "16";
							$prop->alt = get_string('parentfolder');
							$out = wiki_img($prop,true);
							$prop = null;
							$prop->href = 'import.php?id='.$id.'&amp;cm='.$cm.'&amp;wdir='.$fileurl;
							wiki_a($out,$prop);
							$prop = null;
							$prop->href = 'import.php?id='.$id.'&amp;cm='.$cm.'&amp;wdir='.$fileurl;
							wiki_a(get_string('parentfolder'), $prop);
			                wiki_change_column();
			                echo '&nbsp;';
			                wiki_change_column();
			                echo '&nbsp;';
			                wiki_change_column();
			                echo '&nbsp;';
			            } else {
			                $count++;
			                $filename = $fullpath."/".$dir;
			                $fileurl  = rawurlencode($wdir."/".$dir);
			                $filesafe = rawurlencode($dir);
			                $filesize = display_size(get_directory_size("$fullpath/$dir"));
			                $filedate = userdate(filemtime($filename), "%d %b %Y, %I:%M %p");
			                $prop->align= "center";
			       			$prop->style = "white-space: nowrap;";
			       			$prop->class = "checkbox";
			       			$prop->classtr = "folder";
			       			wiki_change_row($prop);
			       			$prop = null;
			       			$prop->name = 'file'.$count;
			       			$prop->value = $fileurl;
			       			wiki_input_checkbox($prop);

							$prop = null;
			                $prop->class = 'name';
			                $prop->align = "left";
							$prop->style = "white-space: nowrap;";
							wiki_change_column($prop);
							$prop = null;
							$prop->src = $CFG->pixpath.'/f/folder.gif';
							$prop->height = "16";
							$prop->width = "16";
							$prop->alt = "Folder";
							$out = wiki_img($prop,true);
							$prop = null;
							$prop->href = 'import.php?id='.$id.'&amp;cm='.$cm.'&amp;wdir='.$fileurl.'&amp;choose='.$choose;
							wiki_a($out,$prop);
							$prop = null;
							$prop->href = 'import.php?id='.$id.'&amp;cm='.$cm.'&amp;wdir='.$fileurl.'&amp;choose='.$choose;
							wiki_a(htmlspecialchars($dir), $prop);

			                $prop = null;
			                $prop->class = 'size';
			                $prop->align = "right";
							$prop->style = "white-space: nowrap;";
							wiki_change_column($prop);
							echo $filesize;

							$prop = null;
			                $prop->class = 'date';
			                $prop->align = "right";
							$prop->style = "white-space: nowrap;";
							wiki_change_column($prop);
							echo $filedate;

							$prop = null;
			                $prop->class = 'commands';
			                $prop->align = "right";
							$prop->style = "white-space: nowrap;";
							wiki_change_column($prop);
							$prop = null;
							$prop->href = 'import.php?id='.$id.'&amp;cm='.$cm.'&amp;wdir='.$wdir.'&amp;file='.$filesafe.'&amp;action=rename&amp;choose='.$choose;
							wiki_a($strrename, $prop);
			            }
			        }
			    }

			    if (!empty($filelist)) {
			        asort($filelist);
			        foreach ($filelist as $file) {

			            $icon = mimeinfo("icon", $file);

			            $count++;
			            $filename    = $fullpath."/".$file;
			            $fileurl     = "$wdir/$file";
			            $filesafe    = rawurlencode($file);
			            $fileurlsafe = rawurlencode($fileurl);
			            $filedate    = userdate(filemtime($filename), "%d %b %Y, %I:%M %p");
						$filepathsafe = rawurlencode($filename);
			            if (substr($fileurl,0,1) == '/') {
			                $selectfile = substr($fileurl,1);
			            } else {
			                $selectfile = $fileurl;
			            }

						if ($header){
			           		$header = false;
				            $prop = null;
			           		$prop->header = true;
				       	} else {
				       		$prop = null;
				        }
			           	$prop->align= "center";
			           	$prop->style = "white-space: nowrap;";
			           	$prop->class = "checkbox";
			           	$prop->classtr = "file";
			           	wiki_change_row($prop);
						$prop = null;
			           	$prop->name = 'file'.$count;
			           	$prop->value = $fileurl;
			           	wiki_input_checkbox($prop);

			            $prop = null;
			            $prop->align = "left";
			            $prop->style = "white-space: nowrap;";
			            $prop->class = "name";
			            wiki_change_column($prop);

			          	if ($CFG->slasharguments) {
			                $ffurl = "/file.php/".$id.$fileurl;
			            } else {
			                $ffurl = "/file.php?file=/".$id.$fileurl;
			            }
			            link_to_popup_window ($ffurl, "display",
			                                  "<img src=\"$CFG->pixpath/f/$icon\" height=\"16\" width=\"16\" alt=\"File\" />",
			                                  480, 640);
			            echo '&nbsp;';
			            link_to_popup_window ($ffurl, "display",
			                                  htmlspecialchars($file),
			                                  480, 640);

			            $file_size = filesize($filename);
			            $prop = null;
				        $prop->class = 'size';
				        $prop->align = "right";
						$prop->style = "white-space: nowrap;";
						wiki_change_column($prop);
						echo display_size($file_size);
			         	$prop = null;
				        $prop->class = 'date';
				        $prop->align = "right";
						$prop->style = "white-space: nowrap;";
						wiki_change_column($prop);
						echo $filedate;

			            if ($choose) {
			                $edittext = "<b><a onMouseDown=\"return set_value('$selectfile')\" href=\"\">$strchoose</a></b>&nbsp;";
			            } else {
			                $edittext = '';
			            }


			            if ($icon == "text.gif" || $icon == "html.gif") {
			            } else if ($icon == "zip.gif") {
			                $edittext .= "<a href=\"exportxml.php?id=$cm&amp;path=$filepathsafe&amp;pageaction=importxml\">$strimport</a> ";
			            }
						$prop = null;
				        $prop->class = 'commands';
				        $prop->align = "right";
						$prop->style = "white-space: nowrap;";
						wiki_change_column($prop);
						echo $edittext;
						echo "<a href=\"index.php?id=$id&amp;wdir=$wdir&amp;file=$filesafe&amp;action=rename&amp;choose=$choose\">$strrename</a>";
			        }
			    }

		    if ($header){
		    	$prop = null;
		        $prop->header = true;
		        wiki_table_end($prop);
		        $header = false;
		    } else {
		    	wiki_table_end();
		    }
		    wiki_hr();

		    if (empty($wdir)) {
		        $wdir = "/";
		    }

			$prop = null;
			$prop->name = "id";
			$prop->value = $id;
			wiki_input_hidden($prop);

			$prop = null;
			$prop->name = "cm";
			$prop->value = $cm;
			wiki_input_hidden($prop);

			$prop = null;
			$prop->name = "choose";
			$prop->value = $choose;
			wiki_input_hidden($prop);

			$prop = null;
			$prop->name = "wdir";
			$prop->value = $wdir;
			wiki_input_hidden($prop);

			$prop = null;
			$prop->name = "sesskey";
			$prop->value = $USER->sesskey;
			wiki_input_hidden($prop);

		    $options = array (
		                   "delete" => "$strdeletecompletely",
		               );
		    if (!empty($count)) {
		        choose_from_menu ($options, "action", "", "$strwithchosenfiles...", "javascript:document.forms['dir'].submit()");
		    }

		wiki_div_end();
    wiki_form_end();

    $prop = null;
	$prop->border = "0";
	$prop->spacing = "2";
	$prop->padding = "2";
	$prop->class = "boxalignright";
	$prop->aligntd = "right";
	wiki_table_start($prop);
		$prop = null;
		$prop->action = "import.php";
		$prop->method = "get";
		wiki_form_start($prop);
			wiki_div_start();

				$prop = null;
				$prop->name = "choose";
				$prop->value = $choose;
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = "id";
				$prop->value = $id;
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = "cm";
				$prop->value = $cm;
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = "wdir";
				$prop->value = $wdir;
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = "action";
				$prop->value = "upload";
				wiki_input_hidden($prop);

				$prop = null;
				$prop->value = $struploadafile;
				wiki_input_submit($prop);

			wiki_div_end();
		wiki_form_end();
	wiki_table_end();
	wiki_hr();
}

//search the name of a course created with some of their dfwikis exported
function wiki_search_rest(){

    global $CFG;

    $rest = "1/exportedfiles/a0";

    $listmoodledata = list_directories_and_files ("$CFG->dataroot");
    foreach ($listmoodledata as $filemoodledata) {
        if(check_dir_exists("$CFG->dataroot/$filemoodledata/exportedfiles",false) && ($filemoodledata != temp)){
            $exporteddfwikis = list_directories_and_files ("$CFG->dataroot/$filemoodledata/exportedfiles");
            if ($exporteddfwikis != null){
                foreach ($exporteddfwikis as $exporteddfwiki) {
                    $xmllist = list_directories_and_files ("$CFG->dataroot/$filemoodledata/exportedfiles/$exporteddfwiki");
                    if ($xmllist != null){
                        foreach ($xmllist as $filexml) {
                            $rest = "$filemoodledata/exportedfiles/$exporteddfwiki";
                        }
                    }
                }
            }
        }
    }
    return $rest;

}

?>
