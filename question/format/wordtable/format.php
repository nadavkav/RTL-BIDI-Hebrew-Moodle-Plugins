<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Convert Word tables into Moodle Question XML format
 *
 * This code converts quiz questions between structured Word tables and Moodle
 * Question XML format. The import facility converts Word files into XML
 * by using YAWC Online (www.yawconline.com), a Word to XML conversion service,
 * to convert the Word file into the Moodle Question XML vocabulary.
 *
 * The export facility also converts questions into Word files using an XSLT script
 * and an XSLT processor. The Word files are really just XHTML files with some
 * extra markup to get Word to open them and apply styles and formatting properly.
 *
 * The wordtable class inherits from the XML question import class, rather than the
 * default question format class, as this minimises code duplication. Essentially,
 *
 * @package questionbank
 * @subpackage importexport
 * @copyright 2010 Eoin Campbell
 * @author Eoin Campbell
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */


require_once( "$CFG->libdir/xmlize.php" );

//require_once( $CFG->dirroot . "/question/format/wordtable/mqutility.php" );

// wordtable just extends XML import/export
require_once( "$CFG->dirroot/question/format/xml/format.php" );

// Include XSLT processor functions
require_once("$CFG->dirroot/question/format/wordtable/xsl_emulate_xslt.inc");

class qformat_wordtable extends qformat_xml {



    // IMPORT FUNCTIONS START HERE

    /**
     * Perform required pre-processing, i.e. convert Word file into XML
     *
     * Send the Word file to YAWC Online for conversion into XML, using CURL
     * functions. First check that the file has the right suffix (.doc) and format
     * (binary Word 2003) required by YAWC.
     *
     * A Zip file containing the Question XML file is returned, and this XML file content
     * is overwritten into the input file, so that the later steps just process the XML
     * in the normal way.
     *
     * @return boolean success
     */
    function importpreprocess() {
        global $CFG;
        global $USER;
        global $COURSE;

        $import_preprocess_debug = 0;
        $wordtable_dir = "/question/format/wordtable/";

        // Use the default Moodle temporary folder to store temporary files
        $tmpdir = $CFG->dataroot . "/temp/";
        //$debughandle = debug_init($tmpdir . "wordtable.log", $import_preprocess_debug);
        //debug_write("importpreprocess:this->filename = $this->filename, this->realfilename = $this->realfilename\n");

        // Check that the module is registered, and redirect to registration page if not
        if(!record_exists('config', 'name', 'qformat_wordtable_version')) {
            notify(get_string('registrationpage', 'qformat_wordtable'));
            $redirect_url = $CFG->wwwroot. $wordtable_dir . 'register.php?sesskey=' . $USER->sesskey . "&courseid=" . $this->course->id;
            redirect($redirect_url);
        }

        // Check that the file is in Word 2000/2003 format, not HTML, XML, or Word 2007
        if((substr($this->realfilename, -4, 4) == 'docx')) {
            notify(get_string('docxnotsupported', 'qformat_wordtable', $this->realfilename));
            return false;
        }else if ((substr($this->realfilename, -3, 3) == 'xml')) {
            notify(get_string('xmlnotsupported', 'qformat_wordtable', $this->realfilename));
            return false;
        } else if ((stripos($this->realfilename, 'htm'))) {
            notify(get_string('htmlnotsupported', 'qformat_wordtable', $this->realfilename));
            return false;
        } else if ((stripos(file_get_contents($this->filename, 0, null, 0, 100), 'html'))) {
            notify(get_string('htmldocnotsupported', 'qformat_wordtable', $this->realfilename));
            return false;
        }

        // Temporarily copy the Word file so it has a .doc suffix, which is required by YAWC
        // The uploaded file name has no suffix by default
        $temp_doc_filename = $tmpdir . clean_filename(basename($this->filename)) . "-" . $this->realfilename;
        //debug_write("importpreprocess:temp_doc_filename = $temp_doc_filename\n");
        if (copy($this->filename, $temp_doc_filename)) {
            chmod($temp_doc_filename, 0666);
            clam_log_upload($temp_doc_filename, $COURSE);
            //debug_write("importpreprocess: $this->filename copied to $temp_doc_filename\n");
        } else {
            notify(get_string("uploadproblem", "", $temp_doc_filename));
        }

        // Get the username and password required for YAWC Online
        $yol_username = get_record('config', 'name', 'qformat_wordtable_username');
        $yol_password = get_record('config', 'name', 'qformat_wordtable_password');

        // Now send the file to YAWC  to convert it into Moodle Question XML inside a Zip file
        $yawcurl = 'http://www.yawconline.com/ye_convert1.php';
        $yawc_post_data = array(
            "username" => $yol_username->value,
            "password" => base64_decode($yol_password->value),
            "downloadZip" => "0",
            "okUpload" => "Convert",
            "docFile" => "@" . $temp_doc_filename
        );
        //debug_write("importpreprocess: " . print_r($yawc_post_data, true) . "\n");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $yawcurl );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $yawc_post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $yawczipdata = curl_exec($ch);
        curl_close ($ch);

        // Delete the temporary Word file once conversion complete
        if (!$import_preprocess_debug) unlink($temp_doc_filename);

        // Check that a non-zero length file is returned, and the file is a Zip file
        //debug_write("importpreprocess:yawczipdata type/length = " . substr($yawczipdata, 0, 2) . "/" . strlen($yawczipdata) . "\n");
        if((strlen($yawczipdata) == 0) || (substr($yawczipdata, 0, 2) !== "PK")) {
            notify(get_string('conversionfailed', 'qformat_wordtable'));
            return false;
        }

        // Save the Zip file to a regular temporary file, so that we can extract its
        // contents using the PHP zip library
        $zipfile = tempnam($tmpdir, "wt-");
        //debug_write("importpreprocess:zipfile = " . $zipfile . "\n");
        if(($fp = fopen($zipfile, "wb"))) {
            if(($nbytes = fwrite($fp, $yawczipdata)) == 0) {
                notify(get_string('cannotwritetotempfile', 'qformat_wordtable', $zipfile));
                return false;
            }
            fclose($fp);
        }

        // Open the Zip file and extract the Moodle Question XML file data
        $zfh = zip_open($zipfile);
        if ($zfh) {
            $xmlfile_found = false;
            while (!$xmlfile_found) {
                $zip_entry = zip_read($zfh);
                if (zip_entry_open($zfh, $zip_entry, "r")) {
                    $ze_filename = zip_entry_name($zip_entry);
                    $ze_file_suffix = substr($ze_filename, -3, 3);
                    $ze_filesize = zip_entry_filesize($zip_entry);
                    //debug_write("importpreprocess:zip_entry_name = $ze_filename, $ze_file_suffix, $ze_filesize\n");
                    if($ze_file_suffix == "xml") {
                        $xmlfile_found = true;
                        // Found the XML file, so grab the data
                        $xmldata = zip_entry_read($zip_entry, $ze_filesize);
                        //debug_write("importpreprocess:xmldata length = (" . strlen($xmldata) . ")\n");
                        zip_entry_close($zip_entry);
                        zip_close($zfh);
                        if (!$import_preprocess_debug) unlink($zipfile);
                    }
                } else {
                    notify(get_string('cannotopentempfile', 'qformat_wordtable', $zipfile));
                    zip_close($zfh);
                    if (!$import_preprocess_debug) unlink($zipfile);
                    return false;
                }
            }
        } else {
            notify(get_string('cannotopentempfile', 'qformat_wordtable', $zipfile));
            if (!$import_preprocess_debug) unlink($zipfile);
            return false;
        }


        // Now over-write the original Word file with the XML file, so that default XML file handling will work
        if(($fp = fopen($this->filename, "wb"))) {
            if(($nbytes = fwrite($fp, $xmldata)) == 0) {
                notify(get_string('cannotwritetotempfile', 'qformat_wordtable', $this->filename));
                return false;
            }
            fclose($fp);
        }

        //notify(get_string('conversionsucceeded', 'qformat_wordtable'));
        return true;
    }   // end importpreprocess


    // EXPORT FUNCTIONS START HERE

    /**
     * Use a .doc file extension when exporting, so that Word is used to open the file
     * @return string file extension
     */
    function export_file_extension() {
        return ".doc";
    }


    /**
     * Convert the Moodle Question XML into Word-compatible XHTML format
     * just prior to the file being saved
     *
     * Use an XSLT script to do the job, as it is much easier to implement this,
     * and Moodle sites are guaranteed to have an XSLT processor available (I think).
     *
     * @param string  $content Question XML text
     * @return string Word-compatible XHTML text
     */
    function presave_process( $content ) {
        // override method to allow us convert to Word-compatible XHTML format
        global $CFG, $USER;

        $wordtable_installation_folder = "$CFG->dirroot/question/format/wordtable/";
        $presave_process_debug = 0;

        // Use the default Moodle temporary folder to store temporary files
        $tmpdir = $CFG->dataroot . "/temp/";
        //$debughandle = debug_init($tmpdir . "format_debug.txt", $presave_process_debug);

        // XSLT stylesheet to convert Moodle Question XML into Word-compatible XHTML format
        $stylesheet = $wordtable_installation_folder . 'mqxml2word.xsl';
        // XHTML template for Word file CSS styles formatting
        $htmltemplatefile = $wordtable_installation_folder . 'wordfile_template.html';

        // Check that XSLT is installed, and the XSLT stylesheet and XHTML template are present
        if (!class_exists('XSLTProcessor') || !function_exists('xslt_create')) {
            notify(get_string('xsltunavailable', 'qformat_wordtable'));
            return false;
        } else if(!file_exists($stylesheet)) {
            // XSLT stylesheet to transform Moodle Question XML into Word doesn't exist
            notify(get_string('stylesheetunavailable', 'qformat_wordtable', $stylesheet));
            return false;
        } else if(!file_exists($htmltemplatefile)) {
            // Word-compatible XHTML template doesn't exist
            notify(get_string('templateunavailable', 'qformat_wordtable', $htmltemplatefile));
            return false;
        }

        // Check that there is some content to convert into Word
        if (!strlen($content)) {
            notify(get_string('noquestions', 'qformat_wordtable'));
            return false;
        }

        //debug_write("presave_process: preflight checks complete, xmldata length = " . strlen($content) . "\n");

        // Create a temporary file to store the XML content to transform
        if (!($temp_xml_filename = tempnam($tmpdir, "m2w-"))) {
            notify(get_string('cannotopentempfile', 'qformat_wordtable', $temp_xml_filename));
            return false;
        }

        // Write the XML contents to be transformed
        if (($nbytes = file_put_contents($temp_xml_filename, "<quiz>" . $content . "</quiz>")) == 0) {
            notify(get_string('cannotwritetotempfile', 'qformat_wordtable', $temp_xml_filename . "(" . $nbytes . ")"));
        }

        //debug_write("presave_process: xml data saved to $temp_xml_filename\n");

        // Set parameters for XSLT transformation. Note that we cannot use arguments though
        $parameters = array (
            'htmltemplatefile' => $htmltemplatefile,
            'course_id' => $this->course->id,
            'course_name' => $this->course->fullname,
            'author_name' => $USER->firstname . ' ' . $USER->lastname,
            'moodle_url' => $CFG->wwwroot . "/"
        );


        $xsltproc = xslt_create();
        // TODO Get XSLT export to work on Windows - at present only Linux works
        if(!($xslt_output = xslt_process($xsltproc,
                $temp_xml_filename, $stylesheet, null, null, $parameters))) {
            notify(get_string('transformationfailed', 'qformat_wordtable', $stylesheet . "/" . $xmlfile));
            if (!$presave_process_debug) unlink($temp_xml_filename);
            return false;
        }
        if (!$presave_process_debug) unlink($temp_xml_filename);

        // Strip off the XML declaration, if present, since Word doesn't like it
        //$content = substr($xslt_output, strpos($xslt_output, ">"));
        if (strncasecmp($xslt_output, "<?xml ", 5) == 0) {
            $content = substr($xslt_output, strpos($xslt_output, "\n"));
        } else {
            $content = $xslt_output;
        }

        return $content;
    }   // end presave_process

}

?>
