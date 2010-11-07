<?php //Created by Antonio CastaÃƒÂ¯Ã‚Â¿Ã‚Â½o & Juan CastaÃƒÂ¯Ã‚Â¿Ã‚Â½o

//  Manage all uploaded files in a course file area

//  All the Moodle-specific stuff is in this top section
//  Configuration and access control occurs here.
//  Must define:  USER, basedir, baseweb, html_header and html_footer
//  USER is a persistent variable using sessions

    require('../../../config.php');
    require_once ('../../../backup/lib.php');
    //html functions
	require_once ($CFG->dirroot.'/mod/wiki/weblib.php');
    require($CFG->libdir.'/filelib.php');

    $id      = required_param('id', PARAM_INT);
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

	require_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $course->id));

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

    $strfiles = get_string("coursefiles", 'wiki');

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

					print_cancel ($choose, $id, $wdir, $strcancel);

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

        case "move":
            html_header($course, $wdir);
            if (($count = setfilelist($_POST)) and confirm_sesskey()) {
                $USER->fileop     = $action;
                $USER->filesource = $wdir;
                $prop = null;
            	$prop->class = "textcenter";
               	wiki_paragraph(get_string("selectednowmove", "moodle", $count),$prop);
            }
            displaydir($wdir);
            html_footer();
            break;

        case "paste":
            html_header($course, $wdir);
            if (isset($USER->fileop) and ($USER->fileop == "move") and confirm_sesskey()) {
                foreach ($USER->filelist as $file) {
                    $shortfile = basename($file);
                    $oldfile = $basedir.$file;
                    $newfile = $basedir.$wdir."/".$shortfile;
                    if (!rename($oldfile, $newfile)) {
                    	wiki_paragraph("Error: $shortfile not moved");
                    }
                }
            }
            clearfilelist();
            displaydir($wdir);
            html_footer();
            break;

        case "rename":
            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);
                $name = clean_filename($name);
                if (file_exists($basedir.$wdir."/".$name)) {
                    echo "Error: $name already exists!";
                } else if (!rename($basedir.$wdir."/".$oldname, $basedir.$wdir."/".$name)) {
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
					$prop->action = "index.php";
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

					print_cancel ($choose, $id, $wdir, $strcancel);

				wiki_table_end();
            }
            html_footer();
            break;

        case "mkdir":
            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);
                $name = clean_filename($name);
                if (file_exists("$basedir$wdir/$name")) {
                    echo "Error: $name already exists!";
                } else if (! make_upload_directory("$course->id/$wdir/$name")) {
                    echo "Error: could not create $name";
                }
                displaydir($wdir);

            } else {
                $strcreate = get_string("create");
                $strcancel = get_string("cancel");
                $strcreatefolder = get_string("createfolder", "moodle", $wdir);
                html_header($course, $wdir, "form.name");
                wiki_paragraph($strcreatefolder.":");
                wiki_table_start();
                	$prop = null;
					$prop->action = "index.php";
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
							$prop->name = "wdir";
							$prop->value = $wdir;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "action";
							$prop->value = "mkdir";
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "name";
							$prop->size = "35";
							wiki_input_text($prop);

							$prop = null;
							$prop->name = "sesskey";
							$prop->value = $USER->sesskey;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->value = $strcreate;
							wiki_input_submit($prop);

						wiki_div_end();
					wiki_form_end();
					wiki_change_column();

					print_cancel ($choose, $id, $wdir, $strcancel);

				wiki_table_end();
            }
            html_footer();
            break;

        case "edit":
            html_header($course, $wdir);
            if (isset($text) and confirm_sesskey()) {
                $fileptr = fopen($basedir.$file,"w");
                fputs($fileptr, stripslashes($text));
                fclose($fileptr);
                displaydir($wdir);

            } else {
                $streditfile = get_string("edit", "", "<b>$file</b>");
                $strcancel = get_string("cancel");
                $fileptr  = fopen($basedir.$file, "r");
                $contents = fread($fileptr, filesize($basedir.$file));
                fclose($fileptr);

                if (mimeinfo("type", $file) == "text/html") {
                    $usehtmleditor = can_use_html_editor();
                } else {
                    $usehtmleditor = false;
                }
                $usehtmleditor = false;    // Always keep it off for now

                print_heading("$streditfile");

				$prop = null;
				$prop->colspantd = "2";
                wiki_table_start($prop);
                	$prop = null;
					$prop->action = "index.php";
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
							$prop->name = "wdir";
							$prop->value = $wdir;
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "file";
							$prop->value = "$file";
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "action";
							$prop->value = "edit";
							wiki_input_hidden($prop);

							$prop = null;
							$prop->name = "sesskey";
							$prop->value = $USER->sesskey;
							wiki_input_hidden($prop);

                			print_textarea($usehtmleditor, 25, 80, 680, 400, "text", $contents);

                			wiki_br();

							$prop = null;
							$prop->name = "save";
							$prop->value = get_string("savechanges");
							wiki_input_submit($prop);

						wiki_div_end();
					wiki_form_end();
					$prop = null;
                	$prop->class = "textcenter";
                	wiki_change_row($prop);

					print_cancel ($choose, $id, $wdir, $strcancel);

				wiki_table_end();

                if ($usehtmleditor) {
                    use_html_editor();
                }


            }
            html_footer();
            break;

        case "zip":
            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);
                $name = clean_filename($name);

                $files = array();
                foreach ($USER->filelist as $file) {
                   $files[] = "$basedir/$file";
                }

                if (!zip_files($files,"$basedir/$wdir/$name")) {
                    error(get_string("zipfileserror","error"));
                }

                clearfilelist();
                displaydir($wdir);

            } else {
                html_header($course, $wdir, "form.name");

                if (setfilelist($_POST)) {
					$strcancel = get_string("cancel");
					$prop = null;
					$prop->class = "textcenter";
                	wiki_paragraph(get_string("youareabouttocreatezip").":",$prop);
                    print_simple_box_start("center");
                    printfilelist($USER->filelist);
                    print_simple_box_end();

					wiki_br();
                    $prop = null;
					$prop->class = "textcenter";
                	wiki_paragraph(get_string("whattocallzip"),$prop);
                	$prop = null;
                	$prop->class = "boxaligncenter";
                	wiki_table_start($prop);
	                	$prop = null;
						$prop->action = "index.php";
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
								$prop->name = "wdir";
								$prop->value = $wdir;
								wiki_input_hidden($prop);

								$prop = null;
								$prop->name = "action";
								$prop->value = "zip";
								wiki_input_hidden($prop);

								$prop = null;
								$prop->name = "name";
								$prop->size = "35";
								$prop->value = "new.zip";
								wiki_input_text($prop);

								$prop = null;
								$prop->name = "sesskey";
								$prop->value = $USER->sesskey;
								wiki_input_hidden($prop);

								$prop = null;
								$prop->value = get_string("createziparchive");
								wiki_input_submit($prop);

							wiki_div_end();
						wiki_form_end();
					wiki_change_column();

					print_cancel ($choose, $id, $wdir, $strcancel);

					wiki_table_end();
                } else {
                    displaydir($wdir);
                    clearfilelist();
                }
            }
            html_footer();
            break;

        case "unzip":

			html_header($course, $wdir);
            if (!empty($file) and confirm_sesskey()) {

                $strok = get_string("ok");
                $strunpacking = get_string("unpacking", "", $file);
				$prop = null;
				$prop->class = "textcenter";
				wiki_div($strunpacking.":",$prop);
				wiki_br();

                $file = basename($file);

                if (!unzip_file("$basedir/$wdir/$file")) {
                    error(get_string("unzipfileserror","error"));
                }
				wiki_br();
				print_cancel ($choose, $id, $wdir, $strok);

            } else {
                displaydir($wdir);
            }
            html_footer();
            break;

        case "listzip":
            html_header($course, $wdir);
            if (!empty($file) and confirm_sesskey()) {
                $strname = get_string("name");
                $strsize = get_string("size");
                $strmodified = get_string("modified");
                $strok = get_string("ok");
                $strlistfiles = get_string("listfiles", "", $file);

				$prop = null;
				$prop->class = "textcenter";
				wiki_div($strlistfiles.":",$prop);
				wiki_br();
                $file = basename($file);

                include_once("$CFG->libdir/pclzip/pclzip.lib.php");
                $archive = new PclZip(cleardoubleslashes("$basedir/$wdir/$file"));
                if (!$list = $archive->listContent(cleardoubleslashes("$basedir/$wdir"))) {
                    notify($archive->errorInfo(true));

                } else {

            	   	$prop = null;
					$prop->border = "0";
					$prop->spacing = "2";
					$prop->padding = "4";
					$prop->width = "640";
					$prop->class = "files";
					$prop->header = true;
					$prop->alignth = "left";
					$prop->classth = "header name";
					wiki_table_start($prop);

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

						$header = true;

	                   	foreach ($list as $item) {

	                   		if ($header){
	                   			$header = false;
	                   			$prop = null;
	                   			$prop->header = true;

	                   		} else {
	                   			$prop = null;
	                   		}

	                   		$prop->align = "left";
	                   		$prop->style = "white-space: nowrap;";
	                   		$prop->class = "name";
	                   		wiki_change_row($prop);
	                   		echo $item['filename'];

		                    if (! $item['folder']) {
		                    	$prop = null;
		                    	$prop->class = 'size';
		                    	$prop->align = "right";
    							$prop->style = "white-space: nowrap;";
    							wiki_change_column($prop);
   								echo display_size($item['size']);
		                    } else {
		                    	wiki_change_column();
		                    	echo '&nbsp;';
		                    }
		                    $filedate  = userdate($item['mtime'], get_string("strftimedatetime"));
		                    $prop = null;
		                    $prop->class = 'date';
		                    $prop->align = "right";
    						$prop->style = "white-space: nowrap;";
    						wiki_change_column($prop);
   							echo $filedate;
	                    }

	              	if ($header){
	              		$prop = null;
	              		$prop->header = true;
	              		wiki_table_end($prop);
	              		$header = false;
	              	} else {
	              		wiki_table_end();
	              	}
	            }

                wiki_br();
				print_cancel ($choose, $id, $wdir, $strok);
            } else {
                displaydir($wdir);
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
        	$prop = null;
            $prop->src = "$CFG->pixpath/f/folder.gif";
            $prop->height = "16";
            $prop->width = "16";
            $prop->alt = "";
            wiki_img($prop);
            echo $file;
            wiki_br();

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

            $prop = null;
            $prop->src = "$CFG->pixpath/f/$icon";
            $prop->height = "16";
            $prop->width = "16";
            $prop->alt = "";
            wiki_img($prop);
            echo $file;
            wiki_br();

        }
    }
}

function print_cancel ($choose='', $id='', $wdir='', $strcancel=''){

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
}

function print_cell($alignment='center', $text='&nbsp;', $class='') {
    if ($class) {
        $class = ' class="'.$class.' nwikileftnow"';
    }
    echo '<td align="'.$alignment.'"'.$class.'>'."\n".$text.'</td>';
}

function displaydir ($wdir) {
//  $wdir == / or /a or /a/b/c/d  etc

    global $basedir;
    global $id;
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
    $strmakeafolder = get_string("makeafolder");
    $struploadafile = get_string("uploadafile");
    $strwithchosenfiles = get_string("withchosenfiles");
    $strmovetoanotherfolder = get_string("movetoanotherfolder");
    $strmovefilestohere = get_string("movefilestohere");
    $strdeletecompletely = get_string("deletecompletely");
    $strcreateziparchive = get_string("createziparchive");
    $strrename = get_string("rename");
    $stredit   = get_string("edit");
    $strunzip  = get_string("unzip");
    $strlist   = get_string("list");
    $strrestore= get_string("restore");
    $strchoose   = get_string("choose");

	$prop = null;
	$prop->action = "index.php";
	$prop->method = "post";
	$prop->id = "dirform";
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
						$prop->href = 'index.php?id='.$id.'&amp;wdir='.$fileurl;
						wiki_a($out,$prop);
						$prop = null;
						$prop->href = 'index.php?id='.$id.'&amp;wdir='.$fileurl;
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
						$prop->href = 'index.php?id='.$id.'&amp;wdir='.$fileurl.'&amp;choose='.$choose;
						wiki_a($out,$prop);
						$prop = null;
						$prop->href = 'index.php?id='.$id.'&amp;wdir='.$fileurl.'&amp;choose='.$choose;
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
						$prop->href = 'index.php?id='.$id.'&amp;wdir='.$wdir.'&amp;file='.$filesafe.'&amp;action=rename&amp;choose='.$choose;
						wiki_a($strrename, $prop);
						//echo "<a href=\"index.php?id=$id&amp;wdir=$wdir&amp;file=$filesafe&amp;action=rename&amp;choose=$choose\">$strrename</a>";
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
		            $prop->class = "name nwikileftnow";
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
		                $edittext .= "<a href=\"index.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=edit&amp;choose=$choose\">$stredit</a>";
		            } else if ($icon == "zip.gif") {
		                $edittext .= "<a href=\"index.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=unzip&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strunzip</a>&nbsp;";
		                $edittext .= "<a href=\"index.php?id=$id&amp;wdir=$wdir&amp;file=$fileurl&amp;action=listzip&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strlist</a> ";
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
		                   "move" => "$strmovetoanotherfolder",
		                   "delete" => "$strdeletecompletely",
		                   "zip" => "$strcreateziparchive"
		               );
		    if (!empty($count)) {
		        choose_from_menu ($options, "action", "", "$strwithchosenfiles...", "javascript:document.forms['dirform'].submit()");
		    }

    	wiki_div_end();
    wiki_form_end();

    $prop = null;
	$prop->border = "0";
	$prop->spacing = "2";
	$prop->padding = "2";
	$prop->class = "boxalignright";
	$prop->aligntd = "center";
	wiki_table_start($prop);

	    if (!empty($USER->fileop) and ($USER->fileop == "move") and ($USER->filesource <> $wdir)) {

	    	$prop = null;
			$prop->action = "index.php";
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
					$prop->name = "wdir";
					$prop->value = $wdir;
					wiki_input_hidden($prop);

					$prop = null;
					$prop->name = "action";
					$prop->value = "paste";
					wiki_input_hidden($prop);

					$prop = null;
					$prop->name = "sesskey";
					$prop->value = $USER->sesskey;
					wiki_input_hidden($prop);

					$prop = null;
					$prop->value = $strmovefilestohere;
					wiki_input_submit($prop);

				wiki_div_end();
			wiki_form_end();
			$prop = null;
			$prop->align = "right";
			wiki_change_column($prop);
	    }

	    $prop = null;
		$prop->action = "index.php";
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
				$prop->name = "wdir";
				$prop->value = $wdir;
				wiki_input_hidden($prop);

				$prop = null;
				$prop->name = "action";
				$prop->value = "mkdir";
				wiki_input_hidden($prop);

				$prop = null;
				$prop->value = $strmakeafolder;
				wiki_input_submit($prop);

			wiki_div_end();
		wiki_form_end();
		$prop = null;
		$prop->align = "right";
		wiki_change_column($prop);

		$prop = null;
		$prop->action = "index.php";
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

?>
