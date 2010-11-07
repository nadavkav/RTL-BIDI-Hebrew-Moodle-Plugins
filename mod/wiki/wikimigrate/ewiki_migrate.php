<?php

/**
 * This file contains necessary code to migrate old moodle's wiki
 * to our new wiki module
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: ewiki_migrate.php,v 1.4 2007/11/15 10:58:06 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Setup
 */

global $CFG;
require_once($CFG->libdir.'/ddllib.php');
require_once($CFG->libdir.'/dmllib.php');

/**
 * Migrates ewiki tables to nwiki tables
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_migrate_ewiki(){

	$result = true;

	// Merge primary table of two wikis
	$result = wiki_merge_wikitable_fields();

	if ($result){
		$result = $result && wiki_merge_wikipagestable_fields();
	}

	//
	// Migration Start
	//

	// Migrate ewiki to our format
	wiki_migrate_wiki_content();

	//
	// Migration End
	//

	// Drop useless fields from tables
	if ($result){
		$result = $result && wiki_drop_wikitable_fields();
	}
	if ($result){
		$result = $result && wiki_drop_wikipagestable_fields();
	}

	//
	// Do extra processes
	//

	if ($result){
		$result = $result && wiki_create_synonymous_tables();
	}

	if ($result){
		$result = $result && wiki_migrate_uploaded_files();
	}

	if ($result){
		$result = $result && wiki_drop_wikientriestable();
	}

	if ($result){
		$result = $result && wiki_drop_wikilockstable();
	}

	return $result;
}



//////////////////////////////////
//								//
//		PRIVATE FUNCTIONS		//
//								//
//////////////////////////////////


/**
 * Private function.
 *
 * This function adds/modifies fields of ewiki 'wiki table' to look
 * like nwiki 'wiki table'
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_merge_wikitable_fields(){

	$table = new XMLDBTable('wiki');

	$field = new XMLDBField('summary');
	$field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null, null);
	$result = rename_field($table, $field, 'intro');

	$field = new XMLDBField('introformat');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'intro');
	$result = $result && add_field ($table, $field);

	$field = new XMLDBField('editable');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '1', 'timemodified');
	$result = $result && add_field ($table, $field);

	$field = new XMLDBField('attach');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'editable');
	$result = $result && add_field ($table, $field);

	$field = new XMLDBField('upload');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'attach');
	$result = $result && add_field ($table, $field);

	$field = new XMLDBField('restore');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'upload');
	$result = $result && add_field ($table, $field);

  	$field = new XMLDBField('editor');
	$field->setAttributes(XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, null, 'dfwiki', 'restore');
	$result = $result && add_field ($table, $field);

  	$field = new XMLDBField('groupmode');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1',null, XMLDB_NOTNULL, null, null, null, '0', 'editor');
	$result = $result && add_field ($table, $field);

  	$field = new XMLDBField('studentmode');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'groupmode');
	$result = $result && add_field ($table, $field);

	$field = new XMLDBField('teacherdiscussion');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'studentmode');
	$result = $result && add_field ($table, $field);

	$field = new XMLDBField('studentdiscussion');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'teacherdiscussion');
	$result = $result && add_field ($table, $field);

	$field = new XMLDBField('editanothergroup');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'studentdiscussion');
	$result = $result && add_field ($table, $field);

	$field = new XMLDBField('editanotherstudent');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'editanothergroup');
	$result = $result && add_field ($table, $field);

	return $result;
}


/**
 * Private function.
 *
 * This function drop useless fields of ewiki 'wiki table' after
 * migration.
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_drop_wikitable_fields(){

	$table = new XMLDBTable('wiki');

	$field = new XMLDBField('wtype');
	$result = drop_field($table, $field);

	$field = new XMLDBField('ewikiprinttitle');
	$result = $result && drop_field($table, $field);

	$field = new XMLDBField('disablecamelcase');
	$result = $result && drop_field($table, $field);

	$field = new XMLDBField('setpageflags');
	$result = $result && drop_field($table, $field);

	$field = new XMLDBField('strippages');
	$result = $result && drop_field($table, $field);

	$field = new XMLDBField('removepages');
	$result = $result && drop_field($table, $field);

	$field = new XMLDBField('revertchanges');
	$result = $result && drop_field($table, $field);

	$field = new XMLDBField('htmlmode');
	$result = $result && drop_field($table, $field);

	$field = new XMLDBField('ewikiacceptbinary');
	$result = $result && drop_field($table, $field);

	$field = new XMLDBField('initialcontent');
	$result = $result && drop_field($table, $field);

	return $result;
}

/**
 * Private function.
 *
 * This function adds/modifies fields of ewiki 'wiki_pages table' to look
 * like nwiki 'wiki_pages table'
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_merge_wikipagestable_fields(){

	$table = new XMLDBTable('wiki_pages');

	$field = new XMLDBField('dfwiki');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);
	$result = add_field($table, $field);

	$field = new XMLDBField('editable');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '1', 'hits');
	$result = $result && add_field($table, $field);

	$field = new XMLDBField('editor');
	$field->setAttributes(XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, null, 'dfwiki', 'dfwiki');
	$result = $result && add_field($table, $field);

	$field = new XMLDBField('groupid');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '0', 'editor');
	$result = $result && add_field($table, $field);

	$field = new XMLDBField('ownerid');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'groupid');
	$result = $result && add_field($table, $field);

	$field = new XMLDBField('destacar');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null, '0', 'ownerid');
	$result = $result && add_field($table, $field);

	$field = new XMLDBField('votes');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '0', 'destacar');
	$result = $result && add_field($table, $field);

	$field = new XMLDBField('author');
	$field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null, null, 'dfwiki', null);
	$result = $result && change_field_default($table, $field);

	return $result;
}

/**
 * Private function.
 *
 * This function drop useless fields of ewiki 'wiki_pages table'
 * after migration.
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_drop_wikipagestable_fields(){

	$table = new XMLDBTable('wiki_pages');

	$field = new XMLDBField('flags');
	$result = drop_field($table, $field);

	$field = new XMLDBField('meta');
	$result = $result && drop_field($table, $field);

	// We have to drop this key before dropping wiki field
	$key = new XMLDBKey('wiki');
	$key->setAttributes(XMLDB_KEY_UNIQUE, array("pagename","version","wiki"));
	$result = $result && drop_key($table, $key);

	$field = new XMLDBField('wiki');
	$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);
	$result = $result && drop_field($table, $field);

	return $result;
}

/**
 * Private function.
 *
 * This function creates new wiki synonymous table
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_create_synonymous_tables(){

	$table = new XMLDBTable('wiki_synonymous');
	$table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
	$table->addFieldInfo('syn', XMLDB_TYPE_CHAR, '160', null, XMLDB_NOTNULL, null, null, null, null);
	$table->addFieldInfo('original', XMLDB_TYPE_CHAR, '160', null, XMLDB_NOTNULL, null, null, null, null);
	$table->addFieldInfo('dfwiki', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
	$table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, '0', null);
	$table->addFieldInfo('ownerid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, '0', null);
    $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->addKeyInfo('dfwiki_synonymous_uk', XMLDB_KEY_UNIQUE, array('syn','dfwiki','groupid','ownerid'));

    $result = create_table($table);

	return $result;
}

/**
 * Private function.
 *
 * This function drops old wiki wiki_entries table
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_drop_wikientriestable(){

	$table = new XMLDBTable('wiki_entries');
	$result = drop_table($table);

	return $result;
}

/**
 * Private function.
 *
 * This function drops old wiki wiki_locks table
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_drop_wikilockstable(){

	$table = new XMLDBTable('wiki_locks');
	$result = drop_table($table);

	return $result;
}

/**
 * Private function.
 *
 * This function makes all ewiki content migration
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_migrate_wiki_content(){

	$result = true;
    $module = get_record("modules", "name", 'wiki');

	$wikis = get_records('wiki');

	if(!empty($wikis)){
		foreach ($wikis as $wiki){

			// Adapt summary to intro and config introformat
	        $wiki->introformat = '0';

			// Change timemodified
	        $wiki->timemodified = time();

			// Adapt ewikiacceptbinary to attach & upload
			$wiki->attach = $wiki->upload = $wiki->ewikiacceptbinary;

			// Adapt revertchanges to restore
			$wiki->restore = $wiki->revertchanges;


			$coursemodule = get_record('course_modules', 'module', $module->id, 'instance', $wiki->id);

			// Adapt groupmode
			$wiki->groupmode = $coursemodule->groupmode;

			// Adapt wtype to studentmode and editable
	        if ($wiki->wtype == 'teacher'){
	            $wiki->editable = '0';
	            $wiki->studentmode = '0';
	        }elseif ($wiki->wtype == 'group'){
	            $wiki->editable = '1';
	            $wiki->studentmode = '0';
	        }else{ //($wiki->wtype == 'student')
	            $wiki->editable = '1';
	            if($wiki->groupmode == '0'){
	                $wiki->studentmode = '1';
	            }else{
	                $wiki->studentmode = '2';
	            }
	        }

			// Adapt htmlmode to editor
			switch ($wiki->htmlmode){
	            case '0':
	            case '1':
	                $wiki->editor = 'ewiki';
	                break;
	            case '2':
	                $wiki->editor = 'htmleditor';
	                break;
	            default:
	                break;
	        }

			// Update current wiki
			addslashes_recursive($wiki);
			$result = update_record('wiki', $wiki);

			// Abort in case of error in migration
			if (!$result){
				return false;
			}

			// Migrate all wikipages from this wiki
			$result = wiki_migrate_wikipages_content($wiki);

			// Abort in case of error in migration
			if (!$result){
				return false;
			}

		}//foreach ($wikis as $wiki)
	}

	return $result;
}

/**
 * Private function.
 *
 * This function migrates all wikipages of one given wiki
 *
 * @param array $wiki. All wiki info needed.
 *
 * @return boolean. true for success and false in case of error
 */
function wiki_migrate_wikipages_content($wiki){
	$result = true;

	$wikientries = get_records('wiki_entries', 'wikiid', $wiki->id);
	if(!empty($wikientries)){
		foreach ($wikientries as $entry){
			$wikipages = get_records('wiki_pages', 'wiki', $entry->id);

			if(!empty($wikipages)){
				foreach ($wikipages as $page){

					$page->editor = $wiki->editor;
					$page->content = wiki_adapt_page_content($page->content);
					$page->refs = wiki_treat_internal_refs($page->refs);

				    if($wiki->wtype == 'teacher'){
						$page->groupid = $entry->groupid;
						$page->ownerid = '0';
						$page->editable = '0';
					} elseif ($wiki->wtype == 'group'){
						$page->groupid = $entry->groupid;
						$page->ownerid = '0';
						$page->editable = '1';
					} else{

						$cm = get_coursemodule_from_instance('wiki',$wiki->id);
						//$groups = groups_get_groups_for_user($page->userid,$cm->course); // old function/depricated (nadavkav)
						$groups = groups_get_user_groups($cm->course,$page->userid);
						if ($groups){
							$page->groupid = $groups[0];
						} else{
							$page->groupid = 0;
						}

						$page->ownerid = $entry->userid;
						$page->editable = '1';
					}

					$page->dfwiki = $wiki->id;
					$page->author = wiki_adapt_page_author($page->userid);

					addslashes_recursive($page);
					$result = update_record('wiki_pages', $page);

					// Abort in case of error
					if (!$result){
						return false;
					}


				}// foreach ($wikipages as $page)
			}
		}// foreach ($wikientry as $entry)
	}
	return $result;
}

/**
 * Private function.
 *
 * This function adapts old wikipage content to our wiki.
 *
 * @TODO: Why are we adapting ewiki content. Don't we have an ewiki parser? I copied this
 * function from old migration.
 *
 * @param string $pagecontent. Content to adapt.
 *
 * @return string. New wikipage content
 */
function wiki_adapt_page_content($pagecontent){

    $links = null;
    $content = preg_replace("/\n/", "\r\n", $pagecontent);
    $content = preg_replace("/<br \/>/", "<br \/><br \/>", $content);

    //get all links and save them into an array
    $end = strpos($content, ']', 0);
    $start = strpos($content, '[', 0);
    while ($start !== false){
        $ofmoment = substr($content, $start, $end - $start + 1);
        $smilestart = substr($content, $start - 2, $start);
        $smileend = substr($content, $end - 2, $end);
        	if ((!wiki_find_element_in_array($links, $ofmoment)) && ($smileend != '}-') && ($smilestart != '8-')){
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


/**
 * Private function.
 *
 * This function finds an element inside a given array
 *
 * @param array $vector
 * @param string $elements
 *
 * @return boolean
 */
function wiki_find_element_in_array($vector, $element){

    if ($vector != null){
        foreach ($vector as $vec) {
            if (trim($vec) == trim($element)) return true;
        }
    }

    return false;

}

/**
 * Private function.
 *
 * This function deletes external references from $refs
 *
 * @param string $refs.
 *
 * @return string. Containing only internal references
 */
function wiki_treat_internal_refs ($refs){

    $ref = "";
    if ($refs != ""){

		$pagerefs = str_replace("\n","|", $refs);
        $pagerefs = str_replace("||","", $pagerefs);
        $extension = explode('\n',$pagerefs);
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

/**
 * Private function.
 *
 * This function adapts page author to our format
 *
 * @param integer $userid.
 *
 * @return string. Username of the userid.
 */
function wiki_adapt_page_author($userid){

	$userdata = get_record('user', 'id', $userid);

	return $userdata->username;

}


/**
 * Private function.
 *
 * This function moves uploaded files to the new path
 *
 *
 * @return boolean. true in success or false in error
 */
function wiki_migrate_uploaded_files(){

    global $CFG;

	$wikientries = get_records('wiki_entries');
	if (!empty($wikientries)){
		$module = get_record('modules', 'name', 'wiki');
		foreach ($wikientries as $entry){
			$coursemodule = get_record('course_modules', 'instance', $entry->wikiid, 'module', $module->id);

			wiki_move_uploaded_files($entry->wikiid, $coursemodule->course, $coursemodule->id, $entry->id);

		}
	}

}
/**
 * Private function.
 *
 * This function moves uploaded files to the new path for each wiki
 *
 * @param integer $wikiid. New wiki id.
 * @param integer $courseid. Course id
 * @param integer $coursemodule Wiki course module
 * @param integer $oldentryid. Old wiki entry id
 *
 * @return boolean. true in success or false in error
 */
function wiki_move_uploaded_files($wikiid, $courseid, $coursemodule, $oldentryid){

    global $CFG;
	$result = true;

	$from = "$CFG->dataroot/$courseid/moddata/wiki/$wikiid/$oldentryid";
	$to = "$CFG->dataroot/$courseid/moddata/wiki$coursemodule";
	if( check_dir_exists($from)){

		check_dir_exists($to,true);
	    $result = wiki_move_file($from,$to);
    }

	return $result;
}

/**
 * Private function.
 *
 * This function moves file or directori $from to $to
 *
 * @TODO: Move this function
 *
 * @param string $from. File path to be moved
 * @param string $to. Destination path.
 *
 * @return boolean. true in success or false in error
 */
function wiki_move_file($from, $to){
	$result = true;

	if (is_dir($from)) {
		$files = list_directories_and_files($from);
		foreach ($files as $file){
			$from_file = $from.'/'.$file;
			if(is_dir($from_file)){
				$to_file = $to;
			} else{
				$to_file = $to.'/'.$file;
			}

			$result = $result && wiki_move_file($from_file, $to_file);
		}
		rmdir($from);
	} else {
		$result = copy($from, $to);
		unlink($from);
	}
	return $result;
}

?>