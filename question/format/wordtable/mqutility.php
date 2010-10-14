<?
/** mqutility.php
 *
 * wrapper function to convert a Word file to XML via YAWC Online
 *
 * Eoin Campbell, campbeeo@tcd.ie 2010-01-20
 */

// Set the name of the server, used by CURL
$site_url = $_SERVER['SERVER_NAME'];
$urlbase = "http://" . $_SERVER['SERVER_NAME'];
$working_folder = dirname($_SERVER['SCRIPT_FILENAME']) ;
$tmp_folder = $working_folder . "/temp/";
$tmp_folder_uri = dirname($_SERVER['SCRIPT_NAME']) . "/temp/";;
$mqdebug_flag = 0;
$mqdebug_handle = NULL;

// global variable to store the unique name assigned to a preview question
$mqutility_preview_question_unique_name = "";

$mqdebug_file = "";

function debug_init($debug_file, $debug) {
	global $mqdebug_file, $mqdebug_flag, $mqdebug_handle;
	
	$mqdebug_file = $debug_file;

	//print "<p>debug_init(" . $debug_file . ", " . $debug . ")</p>";

	if ($debug) {
		$mqdebug_handle = fopen($debug_file, "w");
		if (!$mqdebug_handle) { 
			//print "<p>fopen failed</p>";
			header("HTTP/1.0 403 Forbidden");
			die("Debugging file open failed: " . $debug_file . "\n");
			return false; 
		} else {
			$mqdebug_flag = $debug;
			return $mqdebug_handle;
		}
	}

	return false;
}

function debug_write($string) {
	global $mqdebug_file, $mqdebug_flag, $mqdebug_handle;
	//print "<p>debug_write(" . $string .  "), mqdebug_flag = " . $mqdebug_flag . "</p>";
	if ($mqdebug_flag) {
		fwrite($mqdebug_handle, $string);
	}
}

function debug_on() {
	global $mqdebug_file, $mqdebug_flag, $mqdebug_handle;

	return $mqdebug_flag;
}

function debug_unlink($filename) {
	global $mqdebug_file, $mqdebug_flag, $mqdebug_handle;

	debug_write("MQDebug: debug_unlink(filename = " . $filename . ") mqdebug_flag = " . $mqdebug_flag . "\n" );
	if ($mqdebug_flag < 2) {
		unlink($filename);
	} else {
		debug_write("MQDebug: did not delete " . $filename . "\n");
	}
}

function debug_close() {
	if ($mqdebug_flag) {
		fclose($mqdebug_handle);
	}
	return true;
	global $mqdebug_file, $mqdebug_flag, $mqdebug_handle;

	fclose($mqdebug_handle);
}

// XSLT stylesheet to convert Moodle Question XML into Word-compatible XHTML format

function xslt_transform($xml, $stylesheet, $preview_question) {
	require_once('xsl_emulate_xslt.inc');

	global $tmp_folder;

	debug_write("xslt_transform(xml = " . substr($xml, 0, 10) . "...; stylesheet = " . $stylesheet .
		"; preview = " . $preview_question .  ")\n");

	// Check that XSLT is installed, and the XSLT stylesheet and XHTML template are present
	if (!class_exists('XSLTProcessor') || !function_exists('xslt_create')) {
		debug_write("xslt_transform: no XSLT\n");
		return false;
	} else if(!file_exists($stylesheet)) {
		// XSLT stylesheet to transform Moodle Question XML into Word doesn't exist
		debug_write("xslt_transform failed: no stylesheet '" . $stylesheet . "'\n");		//notify(get_string('stylesheetunavailable', 'qformat_wordq', $stylesheet));
		return false;
	} 
	//else if(!file_exists($working_folder . $htmltemplatefile)) {
		// Word-compatible XHTML template doesn't exist
	//	notify(get_string('templateunavailable', 'qformat_wordq', $htmltemplatefile));
	//	return false;
	//}

	// Check that there is some content to convert into Word
	if (!strlen($xml)) {
		debug_write("xslt_transform failed: no xml\n");				//notify(get_string('noquestions', 'qformat_wordq'));
		return false;
	}

	//if ($debug) notify("presave_process: preflight checks complete, xmldata length = " . strlen($xml));

	// Create a temporary file to store the XML content to transform
	if (!($temp_xml_filename = tempnam($tmp_folder, "p1xml"))) {
		debug_write("xslt_transform failed: cannot create temp file '" . $temp_xml_filename . "'\n");
		//notify(get_string('cannotopentempfile', 'qformat_wordq', $temp_xml_filename));
		return false;
	}

	// Write the XML contents to be transformed
	if (($nbytes = file_put_contents($temp_xml_filename, $xml)) == 0) {
		debug_write("xslt_transform failed: cannot write to temp file '" . $temp_xml_filename . "'\n");
		//notify(get_string('cannotwritetotempfile', 'qformat_wordq', $temp_xml_filename . "(" . $nbytes . ")"));
		if (!debug_on()) unlink($temp_xml_file);
		return false;
	}

	// Identify which question to pluck out, and pass in a unique question name
	global $mqutility_preview_question_unique_name;
	$mqutility_preview_question_unique_name = basename($temp_xml_filename);

	//if ($debug) notify("presave_process: xml data saved to $temp_xml_filename");

	// Set parameters for XSLT transformation. Note that we cannot use arguments though
	$parameters = array (
		'preview' => $preview_question,
		'unique_name' => $mqutility_preview_question_unique_name
	);

	debug_write(print_r($parameters, true));
	debug_write("xslt_transform: calling xslt_process(, xmlfile = " . $temp_xml_filename . ",\n\tstylesheet = " . $stylesheet . ", null, null, parameters)\n");

	$xsltproc = xslt_create();
	// if(!($xslt_output = xslt_process($xsltproc, 'arg:/_xml', 'arg:/_xsl', null, $arguments, $parameters))) {
	if(!($xslt_output = xslt_process($xsltproc, 
			$temp_xml_filename, $stylesheet, null, null, $parameters))) {
		//notify(get_string('transformationfailed', 'qformat_wordq', $stylesheet . "/" . $xml));
		debug_write("xslt_transform failed: xslt_process '" . $stylesheet . "'\n");
		if (!debug_on()) unlink($temp_xml_filename);
		return false;
	}
	if (!debug_on())unlink($temp_xml_filename);

	debug_write("xslt_transform: xslt_output = '" . substr($xslt_output, 0, 200) . "'...\n");
	return $xslt_output;
}


function convert_to_xml() {

	// YAWC Online access details
	$conversionURL = 'http://www.yawconline.com/ye_convert.php?url=';
	// YOL login is extracted from the current server URL
	$login = 'mcq@' . $site_url;
	$password = 'mcq'; // should be $USER->id or something a bit more secure


	// Request conversion to XML, expecting a Zip file back, and delete the public Word file once complete
	$file_to_convert = $urlbase . "/" . $working_folder_uri . basename($yolfriendly_name);
	$yolconvertURL = $conversionURL . $file_to_convert . "&username=" . $login . "&password=" . $password;
	debug_write("MQPreview: yolconvertURL = " . $yolconvertURL . "\n");
	$yolzipdata = file_get_contents($yolconvertURL);
	debug_write("MQPreview: yolzipdata length/type = " . strlen($yolzipdata) . "/" . substr($yolzipdata, 0, 2) . "\n");
	if (!debug_on())unlink($working_folder . $temp_doc_folder . $cleanfilename);

	// Check a non-zero length file is returned, and the file is a Zip file
	if((strlen($yolzipdata) == 0) || (substr($yolzipdata, 0, 2) !== "PK")) {
		unlink($cookiefile);
		header("HTTP/1.0 500 Internal Server Error");
		die("Could not convert Word file.\n");
	}
	
	// Save the Zip file to a regular temporary file, not publicly available
	$zipfile = tempnam($working_folder, "zip-") . ".zip";
	debug_write("MQPreview: zipfile = " . $zipfile . "\n");

	if(($fp = fopen($zipfile, "wb"))) {
		if(($nbytes = fwrite($fp, $yolzipdata)) == 0) {
		fclose($fp);
		unlink($cookiefile);
		header("HTTP/1.0 500 Internal Server Error");
		die("Could not convert Word file.\n");
		}
		fclose($fp);
	}

	// Open Zip file and get the Moodle Question XML file
	$zfh = zip_open($zipfile);
	if ($zfh) {
		while ($zip_entry = zip_read($zfh)) {
			if (zip_entry_open($zfh, $zip_entry, "r")) {
				$ze_filename = zip_entry_name($zip_entry);
				$ze_file_suffix = substr($ze_filename, -3, 3);
				$ze_filesize = zip_entry_filesize($zip_entry);
				debug_write("MQPreview: zip_entry_name = $ze_filename, $ze_file_suffix, $ze_filesize" . "\n");

				if($ze_file_suffix === "xml" && $ze_filesize != 0) {
					$xmldata = zip_entry_read($zip_entry, $ze_filesize);
					debug_write("MQPreview: xmldata length = " . strlen($xmldata) . "\n");
				}
				zip_entry_close($zip_entry);
			} else {
				debug_write("MQPreview: cannot open zipfile ", $zipfile . "\n");
				if (!debug_on())unlink($zipfile);
				return false;
			}
			zip_close($zfh);
			if (!debug_on())unlink($zipfile);
		}
	} else {
		fwrite("MQPreview: cannot open zipfile ", $zipfile . "\n");
		if (!debug_on())unlink($zipfile);
		unlink($cookiefile);
		header("HTTP/1.0 500 Internal Server Error");
		die("Could not open preview XML file.\n");
	}

		
		// Now over-write the original Word file with the XML file, so that default XML file handling will work
		if(($fp = fopen($yolfriendly_name, "wb"))) {
			if(($nbytes = fwrite($fp, $xmldata)) == 0) {
				debug_write("MQPreview: cannot write to tempfile", $yolfriendly_name);
				return false;
			}
			fclose($fp);
		}
}



function file_upload_error_message($error_code) {
    switch ($error_code) { 
        case UPLOAD_ERR_INI_SIZE: 
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; 
        case UPLOAD_ERR_FORM_SIZE: 
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; 
        case UPLOAD_ERR_PARTIAL: 
            return 'The uploaded file was only partially uploaded'; 
        case UPLOAD_ERR_NO_FILE: 
            return 'No file was uploaded'; 
        case UPLOAD_ERR_NO_TMP_DIR: 
            return 'Missing a temporary folder'; 
        case UPLOAD_ERR_CANT_WRITE: 
            return 'Failed to write file to disk'; 
        case UPLOAD_ERR_EXTENSION: 
            return 'File upload stopped by extension'; 
        default: 
            return 'Unknown upload error'; 
    } 
}

?>
