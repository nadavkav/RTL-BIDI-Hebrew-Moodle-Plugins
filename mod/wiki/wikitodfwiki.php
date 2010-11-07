<?php  // Export created by Antonio Casta�o & Juan Casta�o
/**
 * 
 * DEPRECATED ?????!!!!!!
 * 
 * 
 */

    require_once("../../config.php");
    require_once("lib.php");
    require_once ('../../backup/lib.php');
    require_once ('../../backup/restorelib.php');
    require_once ('../../course/lib.php');

    global $WS;
	$id = optional_param('id',NULL,PARAM_INT);    // Course Module ID

    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (! $dfwiki = get_record("dfwiki", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

    require_login($course->id);


/// Print the page header

    if ($course->category) {
        $navigation = "<A HREF=\"../../course/view.php?id=$course->id\">$course->shortname</A> ->";
    }

    //Adjust some php variables to the execution of this script
    @ini_set("max_execution_time","3000");
    raise_memory_limit("memory_limit","128M");

    //get mod plural and singlar name
    $strdfwikis = get_string("modulenameplural", 'dfwiki');
    $strdfwiki  = get_string("modulename", 'dfwiki');

    print_header("$course->shortname: $dfwiki->name", "$course->fullname",
                 "$navigation <A HREF=index.php?id=$course->id>$strdfwikis</A> -> $dfwiki->name",
                  "", "", true);

    //Check if either we're comming from the form or this is the first time
    $sure = optional_param('dfformsure',NULL,PARAM_ALPHA);
    if(isset($sure)){

        //Form has already been visited
        wiki_convert_all_wikis();
        echo '<center><table><tr><td>&nbsp;</td></tr></table>';
        print_simple_box_start();
        echo '<h2>';
        echo print_string("convertcorrectly","dfwiki");
        echo '</h2>';
        print_continue("view.php?id=$cm->id");
        print_simple_box_end();
        echo '</center>';

    }else{

        /// First time
        ?> <center><table><tr><td>&nbsp;</td></tr></table>
        <?php print_simple_box_start();?>
        <center><table>
        <?php print_string("convertcheck", "dfwiki");?>
        </table></center><br /><center><table>
        <tr><td><form name="form" method="post" action="<?php echo 'wikitodfwiki.php?id='.$cm->id?>">
        <input type="submit" name="dfform[sure]" value="<?php print_string('yes');?>" />
        </form></td><td>
        <form name="form" method="post" action="<?php echo 'view.php?id='.$cm->id?>">
        <input type="submit" name="dfform[cancel]" value="<?php print_string('no');?>" />
        </form></td></tr>
        <?php print_simple_box_end();?>
        </table></center>

    <?php
    }
    /// Finish the page
    print_footer($course);

    //This function converts all wikis in DB to DFwikis
    function wiki_convert_all_wikis() {

        global $CFG;

        //get all wikis
        if ($wikis = get_records_sql('SELECT *
                FROM '. $CFG->prefix.'wiki')){

            //get every wiki separately
            foreach($wikis as $wiki){

                //get the new cm pointing to the new dfwiki and not to the wiki
                $dfwikiid = wiki_config_course_module($wiki);

                //get every entry for the current wiki
                if($entries = get_records_sql('SELECT *
                    FROM '. $CFG->prefix.'wiki_entries
                    WHERE wikiid=\''.$wiki->id.'\'')){

                    //get eveery entry separately
                    foreach($entries as $entry){

                        //with every entry we get all wiki pages
                        if($pages = get_records_sql('SELECT *
                            FROM '. $CFG->prefix.'wiki_pages
                            WHERE wiki=\''.$entry->id.'\'')){

                            //get every wiki page
                            foreach($pages as $page){

                                //insert the new page into the new dfwiki
                                wiki_insert_page_from_wiki($page, $dfwikiid, $wiki->htmlmode, $wiki->course, $wiki->name, $wiki->id, $entry->groupid);

                                //delete the wiki page
                                $quer3 = 'DELETE FROM '. $CFG->prefix.'wiki_pages
                                    WHERE id=\''.$page->id.'\'';
                                execute_sql($quer3, false);
                            }

                        }

                        //delete entries from DB
                        $quer = 'DELETE FROM '. $CFG->prefix.'wiki_entries
                            WHERE id=\''.$entry->id.'\'';
                        execute_sql($quer, false);

                    }

                }

                //delete wiki entry in DB
                $quer2 = 'DELETE FROM '. $CFG->prefix.'wiki
                    WHERE id=\''.$wiki->id.'\'';
                execute_sql($quer2, false);

                $modul = get_record("modules", "name", 'wiki');
                $coursemodule = get_record_sql('SELECT *
                    FROM '. $CFG->prefix.'course_modules
                    WHERE module='.$modul->id.' AND instance='.$wiki->id);
            }
        }
        @rebuild_course_cache();
        return true;

    }

    //This function  gets the new cm pointing to the new dfwiki and not to the wiki
    function wiki_config_course_module($wiki){

        global $CFG;

        if($entry = get_record_sql('SELECT *
            FROM '. $CFG->prefix.'wiki_entries
            WHERE wikiid=\''.$wiki->id.'\'')){

            $dfwiki->pagename = $entry->pagename;
        }else $dfwiki->pagename = 'first';

        $dfwiki->course = $wiki->course;
        $dfwiki->name = $wiki->name;
        $dfwiki->timemodified = time();
        $dfwiki->editable = '1';
        $dfwiki->attach = '0';
        $dfwiki->restore = '0';
        switch ($wiki->htmlmode){
            case '0':
                $dfwiki->editor = 'ewiki';
                break;
            case '1':
                $dfwiki->editor = 'ewiki';
                break;
            case '2':
                $dfwiki->editor = 'htmleditor';
                break;
            default:
                break;
        }

        //look for the old wiki cm->id
        $modul = get_record("modules", "name", 'wiki');
        $coursemodule = get_record_sql('SELECT *
                    FROM '. $CFG->prefix.'course_modules
                    WHERE module='.$modul->id.' AND instance='.$wiki->id);

        $dfwiki->groupmode = $coursemodule->groupmode;

        $dfwikiid = insert_record("dfwiki", addslashes($dfwiki));

        backup_flush(300);

        //modify the course_modules entry which was pointing to the old wiki so as to point to the new dfwiki
        $moduldfwiki = get_record("modules", "name", 'dfwiki');
        $quer = 'UPDATE '. $CFG->prefix.'course_modules
                SET module=\''.$moduldfwiki->id.'\'
                WHERE id=\''.$coursemodule->id.'\'';
        execute_sql($quer, false);
        $quer2 = 'UPDATE '. $CFG->prefix.'course_modules
                SET instance=\''.$dfwikiid.'\'
                WHERE id=\''.$coursemodule->id.'\'';
        execute_sql($quer2, false);

        //copy the attached files to the new dfwiki
        $oldentryids = get_records_sql('SELECT *
                    FROM '. $CFG->prefix.'wiki_entries
                    WHERE wikiid=\''.$wiki->id.'\'');

        //get all the wiki entries
        foreach($oldentryids as $oldentryid){
           wiki_copy_attachments($wiki->id, $wiki->course, $coursemodule->id, $oldentryid->id);
        }

        //delete all old wiki attached files
        delete_dir_contents("$CFG->dataroot/$wiki->course/moddata/wiki/$wiki->id");

        return $dfwikiid;

    }

    //Function which inserts a page into the new dfwiki with id=$dfwikiid
    function wiki_insert_page_from_wiki($wikipage, $dfwikiid, $mode, $course, $name, $oldwikiid, $groupid){

        global $CFG;

        //search for the old wiki cm->id
        $modul = get_record("modules", "name", 'wiki');
        $coursemodule = get_record_sql('SELECT *
                    FROM '. $CFG->prefix.'course_modules
                    WHERE module='.$modul->id.' AND instance='.$dfwikiid);

        $page->pagename = restore_decode_absolute_links(addslashes($wikipage->pagename));
        $page->version = $wikipage->version;
        $page->content = wiki_treat_content(restore_decode_absolute_links(addslashes($wikipage->content)), $oldwikiid);
        $page->author = wiki_get_username($wikipage->author);
        $page->created = $wikipage->created;
        $page->lastmodified = $wikipage->lastmodified;
        $pagerefs = str_replace("\n","|", restore_decode_absolute_links(addslashes($wikipage->refs)));
        $page_refs = str_replace("||","", $pagerefs);
        $page->refs = wiki_treat_internal_ref($page_refs);
        $page->hits = $wikipage->hits;
        $page->editable = '1';
        $page->dfwiki = $dfwikiid;

        switch ($mode){
            case '0':
                $page->editor = 'ewiki';
                break;
            case '1':
                $page->editor = 'ewiki';
                break;
            case '2':
                $page->editor = 'htmleditor';
                break;
            default:
                break;
        }

        $page->groupid = $groupid;

        if($page->content != ''){
            if (!insert_record ('wiki_pages',addslashes($page))){
            }

            backup_flush(300);
        }

    }

    //Function which treats a wiki content to tranfer it to a dfwiki
    function wiki_treat_content($content, $wikiid){

        global $CFG;

        $links = null;
        $content = preg_replace("/\n/", "\r\n", $content);
        $content = preg_replace("/<br \/>/", "<br \/><br \/>", $content);
        $content = preg_replace("/internal:/", "attach:", $content);

        //get all internal or external links and save them in the links array
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

        //treat every link
        if($links != null){
            foreach ($links as $link) {
                $type = strpos($link, 'http://');
                if ($type === false){
                    //internal one
                    $link2 = substr($link, 1, strlen($link)-2);
                    $exist = strpos($link2, '|');
                    if ($exist === false){
                        //wikipedia exceptional case
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
                    //external one
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

    //This functions imports to the new dfwiki all attached files from the importing wiki
    function wiki_copy_attachments($wikiid, $course, $coursemodule, $oldentryid){

        global $CFG;

        //create the dir where attached dfwiki files are stored
        check_dir_exists("$CFG->dataroot/$course",true);
        check_dir_exists("$CFG->dataroot/$course/moddata",true);
        check_dir_exists("$CFG->dataroot/$course/moddata/dfwiki$coursemodule",true);

        check_dir_exists("$CFG->dataroot/$course/moddata",true);
        check_dir_exists("$CFG->dataroot/$course/moddata/wiki",true);
        check_dir_exists("$CFG->dataroot/$course/moddata/wiki/$wikiid",true);
        check_dir_exists("$CFG->dataroot/$course/moddata/wiki/$wikiid/$oldentryid",true);
        $files = list_directories_and_files("$CFG->dataroot/$course/moddata/wiki/$wikiid/$oldentryid");

        //get every attached file
        if($files != null){
            foreach ($files as $fil) {

                //in case it's not a directory
                if (!is_dir("$CFG->dataroot/$course/moddata/wiki/$wikiid/$oldentryid/$fil")) {
                    $to_file = "$CFG->dataroot/$course/moddata/dfwiki$coursemodule/$fil";
                    $from_file = "$CFG->dataroot/$course/moddata/wiki/$wikiid/$oldentryid/$fil";
                    copy($from_file,$to_file);

                }
                //otherwise
                else{
                    //get every single attached file
                    $files2 = list_directories_and_files("$CFG->dataroot/$course/moddata/wiki/$wikiid/$oldentryid/$fil");
                    if($files2 != null){
                        foreach ($files2 as $fil2) {

                           $to_file = "$CFG->dataroot/$course/moddata/dfwiki$coursemodule/$fil2";
                           $from_file = "$CFG->dataroot/$course/moddata/wiki/$wikiid/$oldentryid/$fil/$fil2";
                           copy($from_file,$to_file);

                        }

                    }

                }
            }
        }
    }

    //Returns the username from a given author
    function wiki_get_username ($author){

        $extension = explode(" ",$author);
        $num = count($extension)-1;
        $num2 = count($extension)-2;
        $num3 = count($extension)-3;
        $num4 = count($extension)-4;

        if (record_exists("user", 'firstname',addslashes($extension[$num4]), 'lastname',addslashes($extension[$num3]))){
            $info=get_user_info_from_db('firstname',addslashes($extension[$num4]), 'lastname',addslashes($extension[$num3]));
        }else $info->username = 'guest';

        return $info->username;

    }

    //Checks if an entry exists in an array
    function wiki_contain_vector ($vector, $element){

        if ($vector != null){
            foreach ($vector as $vec) {
                if (trim($vec) == trim($element)) return true;
            }
        }

        return false;

    }


    //Treats references to delete the external ones
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

?>
