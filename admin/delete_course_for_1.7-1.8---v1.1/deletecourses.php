<?php
	$productname = "Bulk Course Delete";
	$version = "v1.1";
	$author = "Ashley Gooding & Cole Spicer";
/* 
 * A moodle addon to quickly remove a number of courses by uploading an
 *       unformatted text file containing the shortnames of the courses
 *       each on its own line
 *
 *
 * Date: 4/11/07
 * Employed By; Appalachian State University
 *
 * $productname = "Bulk Course Delete";
 * $version = "v1.1";
 * $author = "Ashley Gooding & Cole Spicer";
 *
 */

	require_once('../config.php');
	require_once("../course/lib.php");
	require_once($CFG->libdir.'/adminlib.php');
	require_once($CFG->libdir.'/moodlelib.php');
	
	
	
	require_login();
	
	if (!isadmin()) {
        error('You must be an administrator to edit courses in this way.');
    }
	if (! $site = get_site()) {
        error('Could not find site-level course');
    }
	if (!$adminuser = get_admin()) {
        error('Could not find site admin');
    }
	$stradministration = get_string('administration');
	$strchoose = get_string('choose');
	
	
	set_time_limit(300);
	list($usec, $sec) = explode(" ", microtime());
    $time_start = ((float)$usec + (float)$sec);
	
	
	print_header("$site->shortname: $productname", $site->fullname, 
                 "<a href=\"index.php\">$stradministration</a> -> $productname $version");
				 
				 
				 
	// If there is a file to upload... do it... else do the rest of the stuff
	$um = new upload_manager('deletefile',false,false,null,false,0);
	
    if ($um->preprocess_files()) {
		// All file processing stuff will go here. ID=2...
  		notify('Parsing file...','notifysuccess');
		
        if (!isset($um->files['deletefile'])) {
            error('Upload Error!');
		}
		
		$filename = $um->files['deletefile']['tmp_name'];
		$text = "";
		$file = @fopen($filename, "rb", 0);
		if ($file) {
			while (!feof($file)) {
				$text .= fread($file, 1024);
			}
			fclose($file);
		}
		
		
		// Convert all endings to unix endings for clarity
		$text = preg_replace('!\r\n?!',"\n",$text);
		$shortnames = explode("\n",$text);
		
		// Ok now we have the file in a proper format... lets parse it for course
		//    shortnames and show the list of courses with id #s followed by a confirm button
		
		// Fill this with a list of comma seperated id numbers to delete courses.
		$deleteids = "";
		$idnums = array();
		
		foreach($shortnames as $shortname) {
			if(!($mysqlresource = mysql_query('SELECT `id`,`fullname` FROM `'.$CFG->prefix.'course` WHERE `shortname`=\''.$shortname.'\';')) ) {
				// Say we couldnt find that course
				notify('MySQL Querry Error on course with shortname: '.$shortname.'. Bulk Deleter will not delete this course.','notifyproblem');
				continue;
			}
			if(!mysql_num_rows($mysqlresource)) {
				// If we didnt get anything returned but the sql statement went through... we still output error and skip
				notify('Could not find course with shortname: '.$shortname.'. Bulk Deleter will not delete this course.','notifyproblem');
				continue;
			}
			$arr = mysql_fetch_row($mysqlresource);
			$id = $arr[0]; // first element in row should be id
			$fullname = $arr[1]; //second element should be fullname
			
			if($idnums[$id]) {
				notify('Duplicate entry found for course with shortname: '.$shortname.'... skipping','notifyproblem');
				continue;
			}
			else {
				$idnums[$id] = $fullname;
				$deleteids = $deleteids.$id.',';
			}
			
		}
		
		// Remove last comma
		$deleteids = substr($deleteids, 0, strlen($deleteids)-1);
		
		// Show execute time
		list($usec, $sec) = explode(" ", microtime());
    	$time_end = ((float)$usec + (float)$sec);
        notify('Total Execution Time: '.round(($time_end - $time_start),2).' s','notifysuccess');
		
		echo '<center>';
		echo '<hr /><br /><b>WARNING:</b> Bulk Deleter is about to delete the following courses:<br />';
		echo '<font style="font-size:11pt;">';
		echo '<table border=0>';
		echo '<tr><td width="50"><b> ID </b></td><td><b> FullName </b></td></tr>';
		foreach($idnums as $id => $name) {
			echo '<tr><td>'.$id.'</td><td>'.$name.'</td></tr>';
		}
		echo '</table>';
		echo '</font><br />';
		echo '<br />Are you absolutely sure you want to completely delete these courses<br />for all eternity and from the face of this planet, forever?<br /><br />';
		
		
		echo '<table border=0><tr><td>';
		echo '<form method="post" action="deletecourses.php">';
		echo '<input type="hidden" name="ids" value="'.$deleteids.'">';
		echo '<input type="submit" value="Confirm">';
		echo '</form></td><td>';
		echo '<form method="post" action="deletecourses.php">';
		echo '<input type="submit" value="Cancel">';
		echo '</form></td></tr></table></br>';
		echo '</center>';
		
	}
	else if($ids = $_POST['ids']) {
		// We got passed a list of id's to delete... they pressed the confirm button. Go ahead and delete the courses
		
		
		
		$count = 0;
		
		$idarr = explode(",", $ids);
		foreach($idarr as $id) {
			if(!delete_course($id,false)) {
				notify('Problem deleting course with id: '.$id.'. The course may have been deleted, but not all of the elements.','notifyproblem');
			}
			else {
				$count++;
			}
		}
		
		fix_course_sortorder();
		echo '<br /><br />';
		notify('Deleted '.$count.' courses using '.$productname.' '.$version,'notifysuccess');
		
		// Show execute time
		list($usec, $sec) = explode(" ", microtime());
    	$time_end = ((float)$usec + (float)$sec);
        notify('Total Execution Time: '.round(($time_end - $time_start),2).' s','notifysuccess');
		
		// Put form to delete courses so they can do it agian if they want
		
		// Form to delete courses
		print_heading('Delete Courses');
		$maxuploadsize = get_max_upload_file_size();
		echo '<center>';
		echo '<form method="post" enctype="multipart/form-data" action="deletecourses.php">'.
			 $strchoose.':<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
			 '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">'.
			 '<input type="file" name="deletefile" size="30">'.
			 '<input type="submit" value="Upload">'.
			 '</form></br>';
		echo '</center>';
		// End form
	}
	else {
		// Start page... display instructions and upload buttons etc...
		echo '<center>';
		print_heading("$productname $version");
		echo '<b>Designers:</b> '.$author.'<br /><br />';
		echo '<font style="font-size:11pt;">';
		echo 'Please select an unformatted text file<br />The <i>shortnames</i> of the courses should each be on a seperate line<br /><br /><br />';
		echo 'For Example:<br /></font>';
		echo '<font style="font-family:monospace;font-size:8pt;">';
		echo 'hist1101<br />';
		echo 'bio2204<br />';
		echo 'test723<br /><br /><br /><hr /><br /><br />';
		echo '</font>';
		
	
	
	
		// Form to delete courses
		
		$maxuploadsize = get_max_upload_file_size();
		
		echo '<form method="post" enctype="multipart/form-data" action="deletecourses.php">'.
			 $strchoose.':<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
			 '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">'.
			 '<input type="file" name="deletefile" size="30">'.
			 '<input type="submit" value="Upload">'.
			 '</form></br>';
		echo '</center>';
		// End form
	
	}
				 
	print_footer($course);




?>