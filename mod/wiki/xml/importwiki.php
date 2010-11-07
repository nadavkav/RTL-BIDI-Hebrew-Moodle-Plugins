<?php //Created by Antonio Castaño & Juan Castaño

//  All the Moodle-specific stuff is in this top section
//  Configuration and access control occurs here.
//  Must define:  USER, basedir, baseweb, html_header and html_footer
//  USER is a persistent variable using sessions

    require_once ('../../../config.php');
    require_once ($CFG->libdir.'/filelib.php');
    require_once ('../../../backup/lib.php');
    require_once ('classxml.php');
    require_once ('../../../lib/xmlize.php');


	$id      = required_param('id', PARAM_INT);
    $cm      = required_param('cm', PARAM_INT);
    $file    = optional_param('file', '', PARAM_PATH);
    $wdir    = optional_param('wdir', '', PARAM_PATH);
    $action  = optional_param('action', '', PARAM_ACTION);
    $name    = optional_param('name', '', PARAM_FILE);
    $oldname = optional_param('oldname', '', PARAM_FILE);
    $choose  = optional_param('choose', '', PARAM_CLEAN);

    if ($choose) {
        if (count(explode('.', $choose)) != 2) {
            error('Incorrect format for choose parameter');
        }
    }

    //Adjust some php variables to the execution of this script
    @ini_set("max_execution_time","3000");
    raise_memory_limit("memory_limit","128M");

    if (! $course = get_record("course", "id", $id) ) {
        error("That's an invalid course id");
    }

    require_login($course->id);

	$context = get_context_instance(CONTEXT_MODULE,$course->id);
	require_capability('mod/wiki:adminactions',$context);

    function html_footer() {

        global $course, $choose;

        if ($choose) {
            echo "</td></tr></table>";
        } else {
            echo "</td></tr></table>";
            print_footer($course);
        }
    }

    function html_header($course, $wdir, $formfield=""){
        global $ME, $choose;

        if (! $site = get_site()) {
            error("Invalid site!");
        }

        $strfiles = get_string("backupwikis", 'wiki');

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


        echo "<table border=\"0\" class=\"boxaligncenter\" cellspacing=\"3\" cellpadding=\"3\" width=\"640\">";
        echo "<tr>";
        echo "<td colspan=\"2\">";

    }

    $basedir = $CFG->dataroot;

    $baseweb = $CFG->wwwroot;

//  End of configuration and access control


    if (!$wdir) {
        $wdir="/";
    }

    if (($wdir != '/' and detect_munged_arguments($wdir, 0))
      or ($file != '' and detect_munged_arguments($file, 0))) {
        $message = "Error: Directories can not contain \"..\"";
        $wdir = "/";
        $action = "";
    }

    switch ($action) {

        case "cancel":
            clearfilelist();

        default:
            html_header($course, $wdir);
            displaydir($wdir);
            html_footer();
            break;
}


/// FILE FUNCTIONS ///////////////////////////////////////////////////////////

function clearfilelist() {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";
}


function displaydir ($wdir) {
//  $wdir == / or /a or /a/b/c/d  etc

    global $basedir;
    global $id, $cm;
    global $USER, $CFG;
    global $choose;

    $fullpath = $basedir.$wdir;
    $filelist = null;

    // Find all files
    $listmoodledata = list_directories_and_files ("$CFG->dataroot");
        foreach ($listmoodledata as $filemoodledata) {
            if(check_dir_exists("$CFG->dataroot/$filemoodledata/backupdata",false) && ($filemoodledata != 'temp')){
                $exportedewikis = list_directories_and_files ("$CFG->dataroot/$filemoodledata/backupdata");
                if ($exportedewikis != null){
                    foreach ($exportedewikis as $exportedewiki) {
                        $extension = explode(".",$exportedewiki);
                        $num = count($extension)-1;
                        if($extension[$num] == "zip"){
                            $zip = "$CFG->dataroot/$filemoodledata/backupdata/$exportedewiki";
                            $list = get_ewikis($zip);
                            if($list != null){
                                foreach ($list as $l){
                                    $filelist[] = "$CFG->dataroot/$filemoodledata/backupdata/$exportedewiki/$l->modtype/$l->name";
                                }
                            }
                        }
                    }
                }
            }
        }

    $strname = get_string("name");
    $strtype = get_string("type",'wiki');
    $strzip = get_string("namezip", 'wiki');
    $strmodified = get_string("modified");
    $straction = get_string("action");
    $strimport = get_string("import", 'wiki');
    $strchoose = get_string("choose");

    echo "<form action=\"import.php\" method=\"post\" id=\"dirform\">";
    echo '<div><input type="hidden" name="choose" value="'.$choose.'" /></div>';
    echo "<hr />";
    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"780\" class=\"files\">";
    echo "<tr>";
    echo "<th></th>";
    echo "<th align=\"left\" class=\"header name\">$strname</th>";
    echo "<th align=\"center\" class=\"header name\">$strtype</th>";
    echo "<th align=\"center\" class=\"header name\">$strzip</th>";
    echo "<th align=\"right\" class=\"header date\">$strmodified</th>";
    echo "<th align=\"right\" class=\"header commands\">$straction</th>";
    echo "</tr>\n";

    if ($wdir == "/") {
        $wdir = "";
    }
    if (!empty($wdir)) {
        $dirlist[] = '..';
    }

    $count = 0;

    if (!empty($filelist)) {
        asort($filelist);
        foreach ($filelist as $filepath) {

            $extension = explode("/",$filepath);
            $num = count($extension)-1;
            $num1 = count($extension)-2;
            $num2 = count($extension)-3;
            $num3 = count($extension)-5;
            $file = $extension[$num];

            $count++;
            $type_mod = $extension[$num1];
            $newzip   = $extension[$num2];
            $icon = mimeinfo("icon", $newzip);
            $newcourse   = $extension[$num3];
            $filename    = "$CFG->dataroot/$newcourse/backupdata/$newzip";
            $fileurl     = "/backupdata/$newzip/$file";
            $filesafe    = rawurlencode($file);
            $fileurlsafe = rawurlencode($fileurl);
            $filepathsafe = rawurlencode($filename);
            $filedate    = userdate(filemtime($filename), "%d %b %Y, %I:%M %p");

            if (substr($fileurl,0,1) == '/') {
                $selectfile = substr($fileurl,1);
            } else {
                $selectfile = $fileurl;
            }

            echo "<tr class=\"file\">";

            echo "<td>";
            echo "&nbsp";
            echo "</td>";

            echo "<td class=\"nwikileftnow name\">";
            if ($CFG->slasharguments) {
                $ffurl = "/file.php/$newcourse/backupdata/$newzip";
            } else {
                $ffurl = "/file.php?file=/$newcourse/backupdata/$newzip";
            }

            echo $file;
            echo "</td>";
            echo "<td align=\"center\">";
            echo $type_mod;
            echo "</td>";
            echo "<td align=\"center\">";
            link_to_popup_window ($ffurl, "display",
                                  "<img src=\"$CFG->pixpath/f/$icon\" height=\"16\" width=\"16\" alt=\"File\" />",
                                  480, 640);
            echo '&nbsp;';
            link_to_popup_window ($ffurl, "display",
                                  htmlspecialchars($newzip),
                                  480, 640);
            echo "</td>";

 			echo '<td class="nwikirightrow date">'.$filedate.'</td>';

            if ($choose) {
                $edittext = "<b><a onMouseDown=\"return set_value('$selectfile')\" href=\"\">$strchoose</a></b>&nbsp;";
            } else {
                $edittext = '';
            }

            $edittext .= "<a href=\"exportxml.php?id=$cm&amp;path=$filepathsafe&amp;file=$filesafe&amp;type=$type_mod&amp;pageaction=importewiki\">$strimport</a> ";

 			echo '<td class="nwikirightrow commands">'.$edittext.'</td>';

            echo "</tr>";
        }
    }
    echo "</table>";
    echo "<hr />";

    if (empty($wdir)) {
        $wdir = "/";
    }

    echo "</form>";

}

//returns the name of all the ewikis of the backup of a course
function get_ewikis($zip){

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

    $info = restore_read_xml_bis($newfile);

    if ($info != null) {
        foreach ($info as $mod) {
            if(($mod->modtype == 'wiki') || ($mod->modtype == 'dfwiki')) $listewikis[] = $mod;
        }
    }

    //delete the folder created in temp
    $filelist2 = list_directories_and_files ("$CFG->dataroot/temp/ewikis");
    if ($filelist2 != null) $del = delete_dir_contents("$CFG->dataroot/temp/ewikis");

    return $listewikis;

}


function restore_read_xml_bis ($xml_file) {

        $status = true;

        $xml_parser = xml_parser_create('UTF-8');
        $moodle_parser = new MoodleParser();
        $moodle_parser->todo = "MODULES";
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

            //if ($tagName == "MOD" && $this->tree[3] == "MODULES") {                                     //Debug
            //    echo "<P>MOD: ".strftime ("%X",time()),"-";                                             //Debug
            //}                                                                                           //Debug

            //Output something to avoid browser timeouts...
            backup_flush();

            //Check if we are into MODULES zone
            //if ($this->tree[3] == "MODULES")                                                          //Debug
            //    echo $this->level.str_repeat("&nbsp;",$this->level*2)."&lt;".$tagName."&gt;<br />\n";   //Debug

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
                //if (trim($this->content))                                                                     //Debug
                //    echo "C".str_repeat("&nbsp;",($this->level+2)*2).$this->getContents()."<br />\n";           //Debug
                //echo $this->level.str_repeat("&nbsp;",$this->level*2)."&lt;/".$tagName."&gt;<br />\n";          //Debug
                //Acumulate data to info (content + close tag)
                //Reconvert: strip htmlchars again and trim to generate xml data
                if (!isset($this->temp)) {
                    $this->temp = "";
                }
                $this->temp .= htmlspecialchars(trim($this->content))."</".$tagName.">";
                //If we've finished a mod, xmlize it an save to db
                if (($this->level == 4) and ($tagName == "MOD")) {
                    //Prepend XML standard header to info gathered
                    $xml_data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".$this->temp;
                    //Call to xmlize for this portion of xml data (one MOD)
                    //echo "-XMLIZE: ".strftime ("%X",time()),"-";                                                  //Debug
                    $data = xmlize($xml_data,0);
                    //echo strftime ("%X",time())."<p>";                                                            //Debug
                    //traverse_xmlize($data);                                                                     //Debug
                    //print_object ($GLOBALS['traverse_array']);                                                  //Debug
                    //$GLOBALS['traverse_array']="";                                                              //Debug
                    //Now, save data to db. We'll use it later
                    //Get id and modtype from data
                    $mod_type = $data["MOD"]["#"]["MODTYPE"]["0"]["#"];
                    $mod_name = $data["MOD"]["#"]["NAME"]["0"]["#"];

                    //Only if we've selected to restore it

                    //echo "<p>id: ".$mod_id."-".$mod_type." len.: ".strlen($sla_mod_temp)." to_db: ".$status."<p>";   //Debug
                    //Create returning info
                    $ret_info->modtype = $mod_type;
                    $ret_info->name = $mod_name;
                    $this->info[] = $ret_info;

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

?>
