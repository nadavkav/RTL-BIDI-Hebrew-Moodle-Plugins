<?php
/**
 * Library functions that provide full-text search.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ousearch
 *//** */
require_once(dirname(__FILE__).'/../../config.php');

define('OUSEARCH_SUMMARYLENGTH',50);  // Number of words in summary
define('OUSEARCH_RESULTSPERPAGE',10); // Number of results to display on default search result pages
define('OUSEARCH_SUPPORTS_OR',false); // No you can't just set this to true to make it support OR

/** Class that handles documents */
class ousearch_document {
    
    /**
     * Static function. Deletes all data relating to a module instance.
     * @param object $cm Course-module object (must have at least ->id and ->course)
     */
    function delete_module_instance_data($cm) {
        global $CFG;
        $where='courseid='.$cm->course.' AND coursemoduleid='.$cm->id;        
        delete_records_select('block_ousearch_occurrences','documentid IN (SELECT id FROM '.
            $CFG->prefix.'block_ousearch_documents WHERE '.$where.')');
        delete_records_select('block_ousearch_documents',$where);
    }
    
    /**
     * Initialise document with appropriate parameters for when text comes
     * from a module instance.
     * @param string $modulename Name of module e.g. 'ouwiki'
     * @param object $cm Course-module object
     */
    function init_module_instance($modulename,$cm,$timemodified=null,$timeexpires=null) {
        $this->plugin='mod/'.$modulename;
        $this->coursemoduleid=(int)$cm->id;
        $this->courseid=(int)$cm->course;
        if($timemodified) {
            $this->timemodified=(int)$timemodified;
        } else {
            $this->timemodified=time();
        }
        if($timeexpires) {
            $this->timeexpires=(int)$timeexpires;
        }
    }
    
    /**
     * Initialise document for testing use.
     * @param string $testname Test name (used for testname_ousearch_get_document function)
     */
    function init_test($testname) {
        $this->plugin='test/'.$testname;
        $this->timemodified=time();
    }
    
    /**
     * Sets the optional group ID.
     * @param int $groupid Group ID
     */
    function set_group_id($groupid) {
        $this->groupid=(int)$groupid;
    }
    
    /**
     * Sets the optional user ID.
     * @param int $userid User ID
     */
    function set_user_id($userid) {
        $this->userid=(int)$userid;
    }
    
    /**
     * Sets the optional string reference that locates this document 
     * within a module instance.
     * @param string $stringref String reference
     */
    function set_string_ref($stringref) {
        $this->stringref=$stringref;
    }
    
    /**
     * Sets the optional int refs that locate this document within a
     * module instance.
     * @param int $intref1 Int ref
     * @param int $intref2 Optional second int ref
     */
    function set_int_refs($intref1,$intref2=null) {
        $this->intref1=(int)$intref1;
        if(!is_null($intref2)) {
            $this->intref2=(int)$intref2;
        }
    }
    
    /**
     * Finds an existing document. The necessary set_ methods must already
     * have been called. If successful, $this->id will be set. 
     * @return True for success, false for failure
     */
    function find() {
        if(!empty($this->id)) { // Already got it
            return true;
        }
        // Find existing document
        $where="plugin='".addslashes($this->plugin)."'";
        if(isset($this->courseid)) {
            $where.=" AND courseid='{$this->courseid}'";
        } else {
            $where.=" AND courseid IS NULL";
        }
        if(isset($this->coursemoduleid)) {
            $where.=" AND coursemoduleid='{$this->coursemoduleid}'";
        } else {
            $where.=" AND coursemoduleid IS NULL";
        }
        if(isset($this->groupid)) {
            $where.=" AND groupid='{$this->groupid}'";
        } else {
            $where.=" AND groupid IS NULL";
        }
        if(isset($this->userid)) {
            $where.=" AND userid='{$this->userid}'";
        } else {
            $where.=" AND userid IS NULL";
        }
        if(isset($this->stringref)) {
            $where.=" AND stringref='".addslashes($this->stringref)."'";
        } else {
            $where.=" AND stringref IS NULL";
        }
        if(isset($this->intref1)) {
            $where.=" AND intref1='{$this->intref1}'";
        } else {
            $where.=" AND intref1 IS NULL";
        }
        if(isset($this->intref2)) {
            $where.=" AND intref2='{$this->intref2}'";
        } else {
            $where.=" AND intref2 IS NULL";
        }
        $this->id=get_field_select('block_ousearch_documents','id',$where);
        return $this->id ? true : false;
    }
    
    /**
     * Adds a new document or updates an existing one. The necessary set_ 
     * methods must already have been called.
     * @param string $title Document title (plain text)
     * @param string $content Document content (XHTML)
     * @param int $timemodified Optional modified time (defaults to now)
     * @param int $timeexpires Optional expiry time (defaults to none); if
     *   expiry time is included then module must provide a 
     *   modulename_ousearch_update($document=null) function
     * @param mixed $extrastrings An array of additional strings which are
     *   searchable, but not included as part of the document content (for
     *   display to users etc). This can be used for keywords and the like
     * @return True for success, false for failure
     */
    function update($title,$content,$timemodified=null,$timeexpires=null,
        $extrastrings=null) {
        global $OUSEARCH_NO_TRANSACTIONS;
        if (empty($OUSEARCH_NO_TRANSACTIONS)) {
            begin_sql();
        }
        // Find document ID, creating document if needed
        if(!$this->find()) {
            // Arse around with slashes so we can insert it safely
            // but the data is corrected again later.
            if(!empty($this->stringref)) {
                $beforestringref=$this->stringref;
                $this->stringref=addslashes($this->stringref);
            }
            $beforeplugin=$this->plugin;
            $this->plugin=addslashes($this->plugin);
            $ok=insert_record('block_ousearch_documents',$this);
            if(!empty($beforestringref)) {
                $this->stringref=$beforestringref;            
            }
            $this->plugin=$beforeplugin;        
            if(!$ok) {
                debugging('Failed to add ousearch document');
                if (empty($OUSEARCH_NO_TRANSACTIONS)) {
                    rollback_sql();
                }
                return false;
            }
            $this->id=$ok;
        }
        
        // Update document record if needed
        if($timemodified || $timeexpires) {
            $update=new StdClass;
            $update->id=$this->id;
            if($timemodified) {
                $update->timemodified=$timemodified;
            }
            if($timeexpires) {
                $update->timeexpires=$timeexpires;
            }
            if(!update_record('block_ousearch_documents',$update)) {
                debugging('Failed to update document record');
                if (empty($OUSEARCH_NO_TRANSACTIONS)) {
                    rollback_sql();
                }
                return false;
            }
        }
        
        // Delete existing words
        if(!delete_records('block_ousearch_occurrences','documentid',$this->id)) {
            debugging('Failed to delete occurrences for ousearch document '.$this->id);
            if (empty($OUSEARCH_NO_TRANSACTIONS)) {
                rollback_sql();
            }
            return false;
        }

        // Extra strings are just counted as more content in the database
        if($extrastrings) {
            foreach($extrastrings as $string) {
                $content.=' '.$string;
            }
        }
        
        // Add new words
        $result=$this->internal_add_words($title,$content);
        if (empty($OUSEARCH_NO_TRANSACTIONS)) {
            commit_sql();
        }
        return $result;        
    }
    
    /**
     * Strips XHTML bits that don't display as text to users.
     * @param string $content XHTML string
     * @return string Plain-text string
     */
    function strip_xhtml($content) {
        $content=preg_replace(array(
            '|<!--.*?-->|s',                                 // Comments
            '|<script.*?</script>|s',                        // Scripts
            '|<noscript.*?</noscript>|s',                    // Noscript
            '|<object.*?</object>|s',                        // Objects
            ),'',$content);
        $content=preg_replace('|<.*?>|',' ',$content);       // All tags
        return html_entity_decode($content,ENT_QUOTES,'UTF-8');
    }
    
    /**
     * Internal method that actually adds words for this document to the 
     * database.
     * @param string $title Title of document
     * @param string $content XHTML content of document
     * @return True if add was successful, false otherwise
     */
    function internal_add_words($title,$content) {
        global $CFG;

        // Build up set of words with counts
        $wordset=array();
        self::internal_add_to_wordset($wordset,$title,true);
        self::internal_add_to_wordset($wordset,self::strip_xhtml($content));
        if(count($wordset)==0) {
            return true;
        }
        
        // Get word IDs from database
        $list='';
        foreach($wordset as $word=>$count) {
            $list.=",'".addslashes($word)."'";
        }
        $list=substr($list,1); // Get rid of first comma
        $dbwords=get_records_select('block_ousearch_words','word IN ('.$list.')','','word,id');
        if(!$dbwords) {
            $dbwords=array();
        }

        global $db;
        // Add any missing words to database
        if($CFG->dbtype=='postgres7') {
            // Do this in 512-word blocks (there is a limit at 1664)
            $sequences=array();
            $pos=0;
            $missingwords=array();
            foreach($wordset as $word=>$count) {
                if(!isset($dbwords[$word])) {
                    $missingwords[$pos]=$word;
                    $sequenceindex=(int)($pos/512);
                    if(!array_key_exists($sequenceindex,$sequences)) {
                        $sequences[$sequenceindex]='';
                    }
                    $sequences[$sequenceindex].=',nextval(\''.$CFG->prefix.'block_ousearch_words_id_seq\') AS s'.$pos;
                    $pos++;
                }
            }
            if(count($missingwords)>0) {
                foreach($sequences as $sequenceindex=>$sequenceselect) {
                    $rs=get_recordset_sql($sql='SELECT '.substr($sequences[$sequenceindex],1));
                    if(!$rs) {
                        print_object($sql);
                        print_object($db->ErrorMsg());
                        error('Error getting word sequences');
                    }
                    $data=array();
                    for($i=$sequenceindex*512;$i<$pos && $i<($sequenceindex+1)*512;$i++) {
                        $id=$rs->fields['s'.$i];
                        $data[]=$id."\t".$missingwords[$i];
                        $dbwords[$missingwords[$i]]=new StdClass;
                        $dbwords[$missingwords[$i]]->id=$id;
                    }
                    if(!pg_copy_from($db->_connectionID, $CFG->prefix.'block_ousearch_words',$data)) {
                        debugging('Failed to insert words');
                    }
                }
            }
        } else {
            foreach($wordset as $word=>$count) {
                if(!isset($dbwords[$word])) {
                    $newword=new StdClass;
                    $newword->word=addslashes($word);
                    if(!($newword->id=insert_record('block_ousearch_words',$newword))) {
                        debugging('Failed to insert word into ousearch table'); 
                        return false;
                    }
                    $dbwords[$word]=$newword;
                }
            }
        }
        
        // Now add the records attaching the words, with scoring, to this document
        if($CFG->dbtype=='postgres7' && count($wordset)>0) {
            $data=array();
            foreach($wordset as $word=>$count) {
                $titlecount=empty($count[true]) ? 0 : $count[true];
                $bodycount=empty($count[false]) ? 0 : $count[false];
                $score=($bodycount<15 ? $bodycount : 15)+($titlecount<15 ? $titlecount*16 : 15*16);
                
                $data[]=$dbwords[$word]->id."\t".$this->id."\t".$score;
            }
            if(!pg_copy_from($db->_connectionID, $CFG->prefix.'block_ousearch_occurrences',$data)) {
                debugging('Failed to insert occurrence records');
            }
        } else {
            foreach($wordset as $word=>$count) {
                $titlecount=empty($count[true]) ? 0 : $count[true];
                $bodycount=empty($count[false]) ? 0 : $count[false];
                $score=($bodycount<15 ? $bodycount : 15)+($titlecount<15 ? $titlecount*16 : 15*16);
                if(!execute_sql($sql='INSERT INTO '.$CFG->prefix.'block_ousearch_occurrences(wordid,documentid,score) '.
                    'VALUES('.$dbwords[$word]->id.','.$this->id.','.$score.')',false)) {
                    debugging('Failed to insert occurrence record');
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Splits text into words.
     * @param string $text Text to split
     * @param bool $query If true, text is treated as a query (preserve +, -, and ")
     * @param bool $positions If true, returns positions
     * @return array If $positions is false, returns an array of words. If it is true,
     *   returns a two-element array, first being an array of words, second being a 
     *   corresponding array of start positions of each word (in characters) with one extra
     *   value holding end position/length in characters
     */
    function split_words($text,$query=false,$positions=false) {
        // Treat single right quote as apostrophe
        $text=str_replace("\xe2\x80\x99","'",$text);

        // Words include all letters, numbers, and apostrophes. Though this is 
        // expressed with Unicode it is likely to work properly only for English and
        // some other European languages.
        $tl=textlib_get_instance();
        $text=preg_replace($query ? '/[^\pL\pN\x27+"-]/u' : '/[^\pL\pN\x27]/u',
            '_', $tl->strtolower($text));

        if(!$positions) {
            $text=preg_replace('/\x27+(_|$)/u','_', $text);
            $text=preg_replace('/(^|_)\x27+/u','_', $text);
            $text=preg_replace('/_+/u','_', $text);
            $result=explode('_',$text);
            $words=array();
            foreach($result as $word) {
                if($word!=='') {
                    $words[]=$word;
                }
            }
            return $words;
        } else {
            $text=self::replace_with_underline('/\x27+(_|$)/u',$text);
            $text=self::replace_with_underline('/(^|_)\x27+/u',$text);

            $words=array(); 
            $positions=array();
            $pos=0;
            while($pos<$tl->strlen($text)) {
                if($tl->substr($text,$pos,1)==='_') {
                    $pos++;
                    continue;
                }
                $nextunderline=$tl->strpos($text,'_',$pos+1);
                if($nextunderline===false) {
                    $nextunderline=$tl->strlen($text);
                }
                $words[]=$tl->substr($text,$pos,$nextunderline-$pos);
                $positions[]=$pos;
                $pos=$nextunderline+1;
            }
            $positions[]=$tl->strlen($text);
            return array($words,$positions);
        }
    }
    
    function internal_replace_callback($matches) {
        $underlines='';
        $tl=textlib_get_instance();
        for($i=0;$i<$tl->strlen($matches[0]);$i++) {
            $underlines.='_';
        }
        return $underlines;
    }
    
    /**
     * Uses preg_replace functions to replace a pattern with the same number of underlines.
     * @param string $pattern
     * @param string $text
     * @return New text (same length)
     */
    function replace_with_underline($pattern,$text) {
        return preg_replace_callback($pattern,array('ousearch_document','internal_replace_callback'),$text);
    }

    /** 
     * Splits the given plain text content into words and adds each word to a set
     * along with listing the number of occurrences.
     * @param array &$wordset Set from word => title (true/false) => occurrence count
     * @param string $text Plain text to add
     * @param bool $title Whether to add to title or nontitle occurrence count
     */    
    function internal_add_to_wordset(&$wordset,$text,$title=false) {
        $words=self::split_words($text);
        foreach($words as $word) {
            // Count occurrences in title or content 
            $before=isset($wordset[$word][$title]) ? $wordset[$word][$title] : 0;
            $wordset[$word][$title]=$before+1;
        }
    }
    
    /**
     * Deletes this document and all its words.
     */
    function delete() {
        // Find document ID
        if(!$this->find()) {
            debugging('Failed to find ousearch document');
            return false;
        }
        self::wipe_document($this->id);
    }
    
    /** 
     * Static function that wipes document and words
     * @param int $id ID of document to wipe 
     */
    function wipe_document($id) {        
        // Delete existing words
        if(!delete_records('block_ousearch_occurrences','documentid',$id)) {
            debugging('Failed to delete occurrences for ousearch document '.$id);
            return false;
        }
        if(!delete_records('block_ousearch_documents','id',$id)) {
            debugging('Failed to delete document for ousearch document '.$id);
            return false;
        }
    }
}

define('OUSEARCH_NONE','none');

/**
 * Represents the restrictions of a search.
 */
class ousearch_search {
    var $courseid=0,$plugin='',$coursemoduleid=0,$cmarray,$filter=null;
    var $groupids=null,$allownogroup=true,$groupexceptions=null,$userid=0,$allownouser=true;
    var $querytext;

    // Search is expressed as follows. 
    // Terms, array of objects:
    // ->words (array of string, possibly length 1)
    // ->ids (matching array of int)
    // ->required (bool)
    // Negative terms, array of objects:
    // ->words (array of string, possibly length 1)
    // ->ids (matching array of int)
    var $terms,$negativeterms;
    
    // True if translate_words has been done
    var $translated=false;
    
    function __construct($query) {
        $this->set_query($query);
    }
    
    function set_query($query) {
        $this->querytext=$query;
        // Clear the existing arrays
        $this->terms=array();
        $this->negativeterms=array();
        
        // Refill those arrays from the query text        
        $words=ousearch_document::split_words($query,true);
        $currentquote=array(); $sign=false; $inquote=false;
        foreach($words as $word) {
            // Clean word to get rid of +, ", and - except if it's in the middle            
            $cleaned=preg_replace('/(^-)|(-$)/','',preg_replace('/[+"]/','',$word));
            if($inquote) {
                // Handle hyphenated words
                if(strpos($cleaned,'-')!==false) {
                    foreach(explode('-',$cleaned) as $subword) {
                        $currentquote[]=$subword;
                    }
                } else {                
                    $currentquote[]=$cleaned;
                }
                if(substr($word,strlen($word)-1,1)=='"') {
                    $term=new StdClass;
                    $term->words=$currentquote;
                    switch($sign) {
                        case '+': $term->required=true; $this->terms[]=$term; break;
                        case '': $term->required=false; $this->terms[]=$term; break;
                        case '-': $term->required=true; $this->negativeterms[]=$term; break;
                    } 
                    $inquote=false;
                }
            } else {
                $firstchar=substr($word,0,1);
                $secondchar=strlen($word)>1 ? substr($word,1,1) : false;
                
                if($firstchar=='"') {
                    // "a phrase"
                    $currentquote=ousearch_search::internal_hyphenated_array($cleaned);
                    $inquote=true;
                    $sign='';                    
                } else if($firstchar=='+' && $secondchar=='"') {
                    // +"a phrase"
                    $currentquote=ousearch_search::internal_hyphenated_array($cleaned);
                    $inquote=true;
                    $sign='+';
                } else if($firstchar=='-' && $secondchar=='"') {
                    // -"a phrase"
                    $currentquote=ousearch_search::internal_hyphenated_array($cleaned);
                    $inquote=true;
                    $sign='-';
                } else if($firstchar=='+') {
                    // +cat
                    $term=new StdClass;
                    $term->words=ousearch_search::internal_hyphenated_array($cleaned);
                    $term->required=true; 
                    $this->terms[]=$term;
                } else if($firstchar=='-') {
                    // -cat
                    $term=new StdClass;
                    $term->words=ousearch_search::internal_hyphenated_array($cleaned);
                    $this->negativeterms[]=$term;
                } else {
                    $term=new StdClass;
                    $term->words=ousearch_search::internal_hyphenated_array($cleaned);
                    $term->required=false; 
                    $this->terms[]=$term;
                }
            }
        }        
    }
    
    function internal_hyphenated_array($cleaned) {
        if(strpos($cleaned,'-')!==false) {
            $currentquote=array();
            foreach(explode('-',$cleaned) as $subword) {
                $currentquote[]=$subword;
            }
            return $currentquote;
        } else {
            return array($cleaned);
        }
    }
        
    /**
     * Restricts search to a particular course.
     * @param int $courseid Course ID or 0 for no restriction
     */
    function set_course_id($courseid=0) {
        $this->courseid=(int)$courseid;
    }

    /**
     * Restricts search to the course-modules that are visible to the current
     * user on the given course.
     * @param object $course Moodle course object
     */
    function set_visible_modules_in_course($course) {
        $modinfo = get_fast_modinfo($course);
        $visiblecms = array();
        foreach ($modinfo->cms as $cm) {
            if ($cm->uservisible) {
                $visiblecms[$cm->id] = $cm;
            }
        }
        $this->set_coursemodule_array($visiblecms);
    }

    /**
     * Restricts search to a particular plugin.
     * @param string $plugin Plugin name e.g. 'mod/ouwiki' or '' for no restriction
     */
    function set_plugin($plugin='') {
        $this->plugin=$plugin;
    }
    /**
     * Restricts search to a particular course-module. This cancels any multiple coursemodule
     * restrictions.
     * @param object $coursemodule Course-module object or null to remove course and module restrictions
     */
    function set_coursemodule($cm=null) {        
        if($cm==null) {
            $this->courseid=0;
            $this->coursemoduleid=0;
        } else {
            $this->cmarray=null;
            $this->courseid=(int)$cm->course;
            $this->coursemoduleid=(int)$cm->id;
        }
    }
    /**
     * Restricts search to specified course-modules. Note that this cancels any single coursemodule
     * restriction.
     * @param object $cmarray Array of course-modules or null to cancel this requirement
     */
    function set_coursemodule_array($cmarray) {        
        $this->cmarray=$cmarray;
        if($cmarray) {
            $this->courseid=0;
            $this->coursemoduleid=0;            
        }
    }
    /**
     * Restricts search to a particular group.
     * @param int $groupid Single required group ID
     */
    function set_group_id($groupid) {
        $this->groupids=array($groupid);
        $this->allownogroup=false;
    }
    /**
     * Restricts search to a particular set of groups.
     * @param mixed $groupids Array of group IDs, or null for any group, or OUSEARCH_NONE to return only results that have no group
     * @param bool $ornone If true, also returns results that have no group (ignored if first parameter is OUSEARCH_NONE or null)
     */
    function set_group_ids($groupids,$ornone=true) {
        $this->groupids=$groupids;
        $this->allownogroup=$ornone;
    }
    /**
     * Adds exceptions to the group restriction (coursemodules in which you have accessallgroups permission, usually).  
     * @param array $cmarray Array of course-module objects (with at least id,course) or null to cancel this requirement
     */
    function set_group_exceptions($cmarray) {
        $this->groupexceptions=$cmarray;
        
    }
    /**
     * Restricts search to a particular user.
     * @param mixed $userid User ID, or 0 for any user, or OUSEARCH_NONE to return only results that have no user
     * @param bool $ornone If true, also returns results that have no user (ignored if first parameter is OUSEARCH_NONE or 0)
     */
    function set_user_id($userid,$ornone=true) {
        $this->userid=$userid;
        $this->allownouser=$ornone;
    }
    
    function internal_get_restrictions() {
        $where='';
        if($this->courseid) {
            $where.=' AND d.courseid='.$this->courseid;
        }
        if($this->plugin) {
            $where.=" AND d.plugin='".addslashes($this->plugin)."'";
        }        
        if($this->coursemoduleid) {
            $where.=' AND d.coursemoduleid='.$this->coursemoduleid;
        }
        if($this->cmarray) {
            // The courses restriction is technically unnecessary except
            // that we don't have index on coursemoduleid alone, so 
            // it is probably better to use course.
            $courses=''; $cms=''; $first=true;
            foreach($this->cmarray as $cm) {
                if($first) {
                    $first=false;
                } else {
                    $courses.=',';
                    $cms.=','; 
                }
                $courses.=$cm->course;
                $cms.=$cm->id;
            }
            $where.=' AND d.coursemoduleid IN ('.$cms.') AND d.courseid IN ('.$courses.')';
        }
        if(is_array($this->groupids)) {
            if($this->groupids==OUSEARCH_NONE) {
                $where.=' AND d.groupid IS NULL';
            } else {
                $where.='AND ';
                if($this->groupexceptions) {
                    $gxcourses=''; $gxcms=''; $first=true;
                    foreach($this->groupexceptions AS $cm) {
                        if($first) {
                            $first=false;
                        } else {
                            $gxcourses.=',';
                            $gxcms.=','; 
                        }
                        $gxcourses.=$cm->course;
                        $gxcms.=$cm->id;
                    }
                    $where.='((d.coursemoduleid IN ('.$gxcms.') AND d.courseid IN ('.$gxcourses.')) OR ';
                } 
                if (count($this->groupids) == 0) {
                    $where.='(FALSE';
                } else {
                    $where.='(d.groupid IN ('.implode(',',$this->groupids).')';
                }
                if($this->allownogroup) {
                    $where.=' OR d.groupid IS NULL';
                }
                $where.=')';
                if($this->groupexceptions) {
                    $where.=')';
                }
            }
        }
        if($this->userid) {
            if($this->userid==OUSEARCH_NONE) {
                $where.=' AND d.userid IS NULL';
            } else {
                $where.=' AND (d.userid='.$this->userid;
                if($this->allownouser) {
                    $where.=' OR d.userid IS NULL';
                }
                $where.=')';
            }
        }
        return $where;        
    }
    
    /**
     * Turns the words from the query into numeric IDs.
     * @return array A two-element array. First element is 'true' if the query
     *   can go ahead, or 'false' if it can't because a word doesn't exist that
     *   was in a required term. Second element is the word that doesn't exist
     *   or 'true' if first element was true.
     */
    function internal_translate_words() {
        global $CFG;
        $this->translated=false;
        
        // Get list of all words used
        $allwords=array();
        foreach(array_merge($this->terms,$this->negativeterms) as $term) {
            $allwords=array_merge($allwords,$term->words);
        }
        $allwords=array_unique($allwords);
        if(count($allwords)===0) {
            return array(false,'No words matched');
        }
        // OK, great, now let's build a query for all those words
        $wordlist='';
        foreach($allwords as $word) {
            if($wordlist) {
                $wordlist.=',';
            }
            $wordlist.="'".addslashes($word)."'";            
        }
        $words=get_records_select('block_ousearch_words','word IN ('.$wordlist.')','','word,id');
        if(!$words) {
            $words=array();
        }
        
        // Convert words to IDs
        $newterms=array();
        $lastmissed='';
        foreach($this->terms as $term) {
            $missed=false;
            $term->ids=array();
            foreach($term->words as $word) {
                if(!array_key_exists($word,$words)) {
                    $missed=true;
                    $lastmissed=$word;
                    break; 
                } else {
                    $term->ids[]=$words[$word]->id;
                }
            }
            // If we didn't have some words in the term...
            if($missed) {
                // Required term? Not going to find anything then
                if($term->required || !OUSEARCH_SUPPORTS_OR) {
                    return array(false,$lastmissed);
                }
                // If not required, just dump that term                
            } else {
                $newterms[]=$term;
            }             
        }
        // Must have some (positive) terms
        if(count($newterms)==0) {
            return array(false,$lastmissed);            
        }        
        $this->terms=$newterms;

        $newterms=array();
        foreach($this->negativeterms as $term) {
            $missed=false;
            $term->ids=array();
            foreach($term->words as $word) {
                if(!array_key_exists($word,$words)) {
                    $missed=$word;
                    break; 
                } else {
                    $term->ids[]=$words[$word]->id;
                }
            }
            // If we didn't have some words in the term, dump it
            if(!$missed) {
                $newterms[]=$term;
            }             
        }
        $this->negativeterms=$newterms;
        $this->translated=true;
        return array(true,true);
    }
    
    /**
     * Runs the database query corresponding to this query. (Basically ANDs all the
     * required terms. Doesn't handle phrases.)
     * @param int $start First DB record
     * @param int $limit Number of DB records
     * @return Array (with 0 - $limit records) of records including ->documentid, ->totalscore,
     *   all fields from block_ousearch_documents, courseshortname, coursefullname, and groupname 
     */
    function internal_query($start,$limit) {
        global $CFG;
        $from='';
        $where='';
        $total='';
        
        $join=0;
        foreach($this->terms as $term) {
            foreach($term->ids as $id) {
                $alias="o$join";
                if($join==0) {
                    $from.="{$CFG->prefix}block_ousearch_occurrences $alias";
                    $where.="$alias.wordid=$id";
                    $total.="$alias.score";
                } else { 
                    $from.="
INNER JOIN {$CFG->prefix}block_ousearch_occurrences $alias 
    ON $alias.documentid=o0.documentid AND $alias.wordid=$id";
                    $total.="+$alias.score";
                }              
                $join++;    
            }
        }
        foreach($this->negativeterms as $term) {
            if(count($term->ids)==1) {
                $alias="o$join";
                $from.="
LEFT JOIN {$CFG->prefix}block_ousearch_occurrences $alias 
    ON $alias.documentid=o0.documentid AND $alias.wordid={$term->ids[0]}";
                $total.="-(CASE WHEN $alias.score IS NULL THEN 0 ELSE 999999 END)";
                $join++;    
            }
        }
        
        $query="
SELECT 
    o0.documentid,$total AS totalscore,d.*,
    c.shortname AS courseshortname,c.fullname AS coursefullname,
    g.name AS groupname
FROM $from
INNER JOIN {$CFG->prefix}block_ousearch_documents d ON d.id=o0.documentid
LEFT JOIN {$CFG->prefix}course c ON d.courseid=c.id
LEFT JOIN {$CFG->prefix}groups g ON d.groupid=g.id
WHERE $where ".$this->internal_get_restrictions()." AND $total>0
ORDER BY totalscore DESC";
        $result=get_records_sql($query,$start,$limit);
        if(!$result) {
            $result=array();
        }
        return $result;
    }
    
    /**
     * Filters search results to pick out only the ones that match the query.
     * @param array $results Array of results from internal_query
     * @param int $desired Number of desired results
     * @return object ->results containing actual results and ->dbnext containing
     *   database position of next set of results.
     */
    function internal_filter($results,$desired) {
        global $CFG;
        $required=array();
        $accepted=array();
        $count=0;
        $return=new StdClass;
        $return->dbnext=0;
        $tl=textlib_get_instance();
        foreach($results as $result) {
            
            $return->dbnext++;
            if(substr($result->plugin,0,4)==='mod/') {
                // Module plugins
                $module=substr($result->plugin,4);
                $function=$module.'_ousearch_get_document';
                if(!array_key_exists($module,$required)) {
                    require_once($CFG->dirroot.'/mod/'.$module.'/lib.php');
                    $required[$module]=true;
                    if(!function_exists($function)) {
                        error('Missing module search support '.$function);
                    }
                }                
            } else if(substr($result->plugin,0,5)==='test/') {
                // Testing code, assumed to already be included
                $function=substr($result->plugin,5).'_ousearch_get_document';                 
            } else {
                // Nothing else supported yet
                error('Unsupported search plugin type '.$result->plugin); 
            }
            
            // Let's request the document. Note that the 'document' fields of 
            // $result are those used by this function to find the right one.
            $page=$function($result);
            // Ignore if we can't find the document
            if(!$page) {
                debugging('Module '.$result->plugin.' can\'t find search document');
                ousearch_document::wipe_document($result->id);
                continue;
            }

            // Page option can request that this result is not included
            if (!empty($page->hide)) {
                continue;
            }

            // Strip XHTML from content (need this before phrase scan)
            $textcontent=ousearch_document::strip_xhtml($page->content);

            // Add extra strings to the content after a special don't-show-this
            // marker and with another special marker between each (to prevent
            // phrases)
            if(isset($page->extrastrings) && count($page->extrastrings)>0) {
                $evilmarker=rand(); // This means people can't do it on purpose
                $textcontent.=' xxrealcontentends'.$evilmarker;
                foreach($page->extrastrings as $string) {
                    $textcontent.=' '.$string.' xxsplit'.$evilmarker;
                }
            }
            
            // Do quick phrase scan that doesn't deal with Unicode,
            // or word-splitting but just discards results that
            // don't have the phrase words next to each other without
            // ASCII letters in between. This is intended to discard 
            // results that (fairly) definitely don't have the phrase. 
            // The further check below will make sure they really do 
            // have it according to our standard (slow) word-splitting.
            $quickcheckcontent=$page->title.' '.$textcontent;
            $ok=true;
            foreach($this->terms as $term) {
                if(count($term->words)<2) {
                    continue;
                }
                $gap='[^A-Za-z0-9]+';  
                $pattern='/(^|'.$gap.')';
                $first=true;
                foreach($term->words as $word) {
                    if($first) {
                        $first=false;
                    } else {
                        $pattern.=$gap;
                    }
                    $pattern.=$word;
                }
                $pattern.='($|'.$gap.')/i';
                if(!preg_match($pattern,$quickcheckcontent)) {
                    $ok=false;
                    break;
                } 
            }
            if(!$ok) {
                continue;
            }

            // OK, obtain document as linear text
            list($contentwords,$contentpositions)=ousearch_document::split_words($textcontent,false,true);
            list($titlewords,$titlepositions)=ousearch_document::split_words($page->title,false,true);
            
            $allwords=array_merge($titlewords,$contentwords);
            
            // Check it for phrases
            $positivewords=array();
            $ok=true;
            $DNIfound=-1;
            foreach($this->terms as $term) {
                foreach($term->words as $word) {
                    $positivewords[$word]=true;
                }
                if(count($term->words)<2) {
                    continue;
                }
                $pos=0;
                $found=false;
                foreach($allwords as $word) {
                    if($word===$term->words[$pos]) {
                        $pos++;
                        if($pos===count($term->words)) {
                            $found=true;
                            break;
                        }
                    } else {
                        $pos=0;
                    }
                }
                if(!$found) {
                    $ok=false;
                    break;
                }
            }
            foreach($this->negativeterms as $term) {
                if(count($term->words)<2) {
                    continue;
                }
                $pos=0;
                $found=false;
                foreach($allwords as $word) {
                    if($word===$term->words[$pos]) {
                        $pos++;
                        if($pos===count($term->words)) {
                            $found=true;
                            break;
                        }
                    } else {
                        $pos=0;
                    }
                }
                if($found) {
                    $ok=false;
                    break;
                }
            }
            if(!$ok) {
                continue;
            }
            
            // Result passes! Make structure holding it...
            
            // We now have list of all positive words, let's mark these
            // in title and summary
            $result->title=self::internal_highlight_words(
                $page->title,$titlewords,$titlepositions,$positivewords);
                
            // Strip searchable-but-not-displayable content for summary
            if(isset($evilmarker)) {
                $strippedwords=array();
                foreach($contentwords as $word) {
                    // Do not include extra strings in summary
                    if($word=='xxrealcontentends'.$evilmarker) {
                        break;
                    }
                    $strippedwords[]=$word;
                }
                $contentwords=$strippedwords;
            }
            
            // Pick a section to include in the summary. This algorithm works as follows:
            // * Compute the 'score' (number of highlight words in the previous 20 words 
            //   up to and including this one) at each position in the text
            // * Observe where the maximum score is reached and where it is lost.
            // * A nice range that contains the most highlight words in the middle of the
            //   range will end at ($maxstart + $maxlength/2).
            $highlights=array();
            $pos=0;
            $currentscore=0;
            $maxscore=-1;
            $maxstart=0;
            $maxlength=0;            
            $run = true;
            foreach($contentwords as $word) {
                if(array_key_exists($pos-OUSEARCH_SUMMARYLENGTH,$highlights)) {
                    unset($highlights[$pos-OUSEARCH_SUMMARYLENGTH]);
                    $currentscore--;
                }                
                if(array_key_exists($word,$positivewords)) {
                    $highlights[$pos]=true;
                    $currentscore++;
                }
                if($currentscore > $maxscore) {
                    $maxscore=$currentscore;
                    $maxstart=$pos;
                    $maxlength=1;
                    $run = true;
                } else if($currentscore===$maxscore && $run) {
                    $maxlength++;
                } else {
                    $run = false;
                }
                $pos++;
            }
            $start=$maxstart+$maxlength/2-OUSEARCH_SUMMARYLENGTH;
            if($start<0) {
                $start=0;
            } 
            $end=$start+OUSEARCH_SUMMARYLENGTH;
            if($end>count($contentwords)) {
                $end=count($contentwords);
            }
            
            // $contentpositions is in characters
            $result->summary=$tl->substr($textcontent,$contentpositions[$start],
                $contentpositions[$end]-$contentpositions[$start]).
                ($end<count($contentwords) ? '...' : '');
            
            $offset=-$contentpositions[$start];
            
            $result->summary=self::internal_highlight_words(
                $result->summary,$contentwords,$contentpositions,$positivewords,
                $offset,$start,$end);
            
            if($start!==0) {
                $result->summary='...'.$result->summary;
            }
            
            $result->summary=trim($result->summary);
            
            $result->activityname=$page->activityname;
            $result->activityurl=$page->activityurl;
            $result->url=$page->url;
            if(isset($page->data)) {
                $result->data=$page->data;
            }
            
            // Do user-specified filter if set
            if($this->filter) {
                $filter=$this->filter;
                if(!$filter($result)) {
                    continue;
                }
            }
            
            $accepted[]=$result;
            
            $count++;
            if($count==$desired) {
                break;
            }
        }
        $return->results=$accepted;
        return $return;
    }
    
    function internal_highlight_words($summary,&$contentwords,&$contentpositions,&$positivewords,
        $offset=0,$start=0,$end=-1) {
        if($end==-1) {
            $end=count($contentwords);
        }
        $tl=textlib_get_instance();
        
        for($pos=$start;$pos<$end;$pos++) {
            $word=$contentwords[$pos];            
            if(array_key_exists($word,$positivewords)) {
                $wordpos=$contentpositions[$pos]; 
                $summary=$tl->substr($summary,0,$wordpos+$offset).
                    '<highlight>'.$tl->substr($summary,$wordpos+$offset,$tl->strlen($word)).
                    '</highlight>'.$tl->substr($summary,$wordpos+$offset+$tl->strlen($word));
                $offset+=23; // Length of highlight tags
            }                
        }
        $summary=htmlspecialchars($summary);
        $summary=preg_replace('@&lt;(/?highlight)&gt;@','<$1>',$summary);
        return $summary;
    }
    
    /**
     * Sets a filter function which can run on results (after they have been
     * obtained) to exclude unwanted ones or make other changes. The filter 
     * function should be defined like (filter that allows everything):
     * function myfilter(&$result) { return true; } 
     * Note that filters can also change the results object if required.
     * Also, filters have access to any additional results fields that you set
     * in your module's lib.php ousearch_get_document function.
     * @param function $filter
     */
    function set_filter($filter) {
        $this->filter=$filter;
    }
    
    /**
     * Runs actual query and obtains results.
     * @param int $dbstart Start position within database results (because 
     *   postprocessing is done which filters out some results, this might
     *   not be the same as the number of results shown previously)
     * @param int $desired Number of desired results to return
     * @return object Result object. Parameters ->success, then 
     *   ->dbstart and ->results if success is true, or ->problemword
     *   otherwise.
     */
    function query($dbstart=0,$desired=10) {
        $return=new StdClass;
        
        // Translate words to IDs
        list($ok,$problemword)=$this->internal_translate_words();
        if(!$ok) {
            $return->success=false;
            $return->problemword=$problemword;
            return $return;
        }
        
        // Initially assume that 1 in 2 results will pass filters, if there
        // are any terms that will require filters
        $filters=false;
        foreach($this->terms as $term) {
            if(count($term->ids) > 1) {
                $filters=true;
                break;
            }
        }
        if(!$filters) {
            foreach($this->negativeterms as $term) {
                if(count($term->ids) > 1) {
                    $filters=true;
                    break;
                }
            }
        }
        $sparsity=$filters ? 2 : 1;
        
        // Obtain results
        $totalrequested=0;
        $totalgot=0;
        $results=array();
        while(count($results)<$desired) {
            // Request a number of results
            $left=$desired-count($results);
            $dbrequest=$left*$sparsity;
            if($dbrequest > 1000) {
                $dbrequest=1000;
            }
            $dbresults=$this->internal_query($dbstart,$dbrequest);
            $filtered=$this->internal_filter($dbresults,$left);
            $results=array_merge($results,$filtered->results);
            $dbstart+=$filtered->dbnext;
            
            // If we're out of database results, stop now
            if(count($dbresults) < $dbrequest) {
                $totalrequested+=count($dbresults);
                break;
            }
            
            // We still have DB results. Update sparsity value if available
            $totalrequested+=$dbrequest;
            $totalgot+=count($filtered->results);
            if($totalgot>0) {
                $sparsity=$totalrequested/$totalgot;
            } else {
                $sparsity=20; // Request loads!
            }
        }

        $return->success=true;
        $return->dbstart=$dbstart;
        $return->results=$results;
        $return->dbrows=$totalrequested;
        return $return;        
    }
}

function ousearch_display_results($search, $baseurl, $title=null) {
    if(debugging()) {
        $before=microtime(true);
    }
    $results=$search->query(optional_param('dbstart',0,PARAM_INT),OUSEARCH_RESULTSPERPAGE);
    if(debugging()) {
        $searchtime = microtime(true) - $before;
    } else {
        $searchtime = null;
    }

    $query = stripslashes(required_param('query', PARAM_RAW));
    $from = optional_param('from', 0, PARAM_INT);
    $previous = optional_param('previous', '', PARAM_RAW);

    $linkshared = $baseurl . '&query=' . urlencode($query);
    $linkprev = null;
    $oldrange = null;
    $linknext = null;

    if ($results->success) {
        $matches = array();
        if ($from==OUSEARCH_RESULTSPERPAGE) {
            $linkprev = $linkshared;
        } else if ($from>OUSEARCH_RESULTSPERPAGE && 
            preg_match('/^(.*?),?([0-9]+)$/', $previous, $matches)) {
            $linkprev = $linkshared . '&from=' . ($from-OUSEARCH_RESULTSPERPAGE) .
                '&dbstart=' . $matches[2] . 
                ($matches[1] ? '&previous=' . $matches[1] : '');
        }
        $oldrange = ($from-OUSEARCH_RESULTSPERPAGE+1) . ' - ' . $from;

        if (count($results->results) == OUSEARCH_RESULTSPERPAGE) {
            if ($from < OUSEARCH_RESULTSPERPAGE) {
                $newprevious = '';
            } else {
                $newprevious = ($previous!=='') 
                    ? '&previous=' . $previous . ',' : '&previous=';
                $newprevious .= required_param('dbstart', PARAM_INT);
            }
            $linknext = 
                $linkshared . '&from=' . ($from+OUSEARCH_RESULTSPERPAGE) .
                '&dbstart=' . $results->dbstart . $newprevious;
        }
    }

    if ($title === null) {
        $title = get_string('searchresultsfor','block_ousearch', s($query));
    }
    print ousearch_format_results($results, $title, $from + 1, $linkprev,
        $oldrange, $linknext, $searchtime);

    return $results->success && count($results->results)>0;
}

/**
 * User interface helper code that prints out the results from a search. 
 * Normally called by ousearch_display_results, but provided in case other
 * code wants to mimmick that appearance.
 * @param object $results Results object with fields ->success (if false, 
 *   also set ->problemword to indicate the word causing the failure) and
 *   ->results (an array). Each element in ->results has fields ->title (HTML,
 *   may additionally include <highlight> tags), ->summary (ditto) and 
 *   ->url (URL, not escaped)
 * @param string $title Heading for search results (HTML)
 * @param int $number Number of first result (default 1)
 * @param string $prevlink URL (not escaped) of link to previous results page,
 *   if any
 * @param string $prevrange Range of results on previous page e.g. '1-10'
 * @param string $nextlink URL (not escaped) of link to next results page,
 *   if any
 * @param float $searchtime Search time as floating-point number of seconds
 * @return string HTML code to print out
 */
function ousearch_format_results($results, $title, $number=1, 
    $prevlink=null, $prevrange=null, $nextlink=null,
    $searchtime=null) {
    $out = '<div class="ousearch_results">';
    $out .= '<h2>' . $title . '</h2>';

    if (!$results->success) {
        $out .= '<p>' . get_string('resultsfail', 'block_ousearch', $results->problemword) . '</p>';
    }  else {
        if ($prevlink) {
            $out .= '<p>' . link_arrow_left(
                get_string('previousresults', 'block_ousearch', $prevrange),
                s($prevlink)) . '</p>';
        }

        if (count($results->results) == 0) {
            $out .= '<p>' . get_string(
                $prevlink ? 'nomoreresults' : 'noresults', 'block_ousearch') . '</p>';
        } else {
            $out .= '<ul>';
            foreach ($results->results as $result) {
                $title = $result->title === '' 
                    ? get_string('untitled','block_ousearch')
                    : $result->title;
                $out .= '<li><div class="ous_number">'.$number.'</div><h3><a href="'.$result->url.'">'.str_replace('highlight>','strong>',$title).'</a></h3>'.
                    '<div class="ous_summary">'.str_replace('highlight>','strong>',$result->summary).'</div></li>';
                $number++;
            }
            $out .= '</ul>';
        }

        if ($nextlink) { 
            $out .= '<p>' . link_arrow_right(
                get_string('findmoreresults', 'block_ousearch'),
                s($nextlink)) . '</p>';
        }
    }

    if($searchtime !== null) {
        $out .= '<p class="ous_searchtime">' . 
            get_string('searchtime','block_ousearch', round($searchtime, 1)) . 
            '</p>';
    }

    $out .= '</div>';
    return $out;
}

/**
 * Checks that the requesting user matches the remote-access IP addresses.
 * If not, calls error().
 */
function ousearch_require_remote_access() {
    global $CFG;
    $addresslist=empty($CFG->block_ousearch_remote) ? '' :$CFG->block_ousearch_remote; 
    if(!preg_match('/^[0-9.]+(,\s*[0-9.]+)*$/',$addresslist)) {
        error('Remote search access is not configured (or not correctly configured).');
    }
    $addresses=preg_split('/,\s*/',$addresslist);
    foreach($addresses as $address) {
        if(getremoteaddr()===$address) {
            return;
        }   
    }
    error('You do not have access to remote search');
}

/**
 * Displays search results as XML format (will send the relevant header too). 
 * @param object $results The return value from calling query() on a search
 * @param int $first First result (1-based)
 * @param int $perpage Number of results to show on a page
 * @return boolean True if the search found some results, false if not (function works anyway)
 */
function ousearch_display_remote_results($results,$first,$perpage) {
    global $CFG;
    header('Content-Type: text/xml; encoding=UTF-8');
    if(!$results->success || count($results->results)==0) {
        print '<results total="0"></results>';
    } else {
        $total=count($results->results);
        $count=$total-$first+1;
        if($count>$perpage) {
            $count=$perpage;
        }
        print '<results first="'.$first.'" last="'.($first+$count-1).'" total="'.$total.'">';
        
        foreach($results->results as $result) {
            // Skip first results
            if($first>1) {
                $first--;
                continue;
            }
            print '<result href="'.htmlspecialchars($result->url).'">';
            if(!empty($result->courseid)) {
                print '<course href="'.$CFG->wwwroot.'/course/view.php?id='.$result->courseid.
                    '"><shortname>'.htmlspecialchars($result->courseshortname).
                    '</shortname><fullname>'.htmlspecialchars($result->coursefullname).'</fullname></course>';
            }
            if(!empty($result->activityurl)) {
                print '<activity href="'.htmlspecialchars($result->activityurl).'">'.
                    htmlspecialchars($result->activityname).'</activity>';
            }
            print '<title>'.$result->title.'</title>';
            // Convert score into a sketchy percentage with 32 being 100%. 
            print '<score>'.round(100*min($result->totalscore,32)/32.0).'%</score>';
            print '<summary>'.$result->summary.'</summary>';
            print '</result>';
            // Only display $perpage results
            $perpage--;
            if($perpage==0) {
                break;
            }
        }
        print '</results>';
    }
}

/** 
 * Gets list of course-modules on the course which have search documents and 
 * for which the user has accessallgroups OR the item is set to visible groups.
 * @param int $courseid Course ID to check
 * @return array Array of course_module objects (id, course only)
 */
function ousearch_get_group_exceptions($courseid) {
    global $CFG;

    // Get all CMs that have a document    
    $possible=get_records_sql("
SELECT
    cm.id AS cmid, cm.course AS cmcourse, cm.groupmode AS cmgroupmode, x.*
FROM 
    {$CFG->prefix}block_ousearch_documents bod
    INNER JOIN {$CFG->prefix}course_modules cm ON bod.coursemoduleid=cm.id
    INNER JOIN {$CFG->prefix}context x ON x.instanceid=cm.id AND x.contextlevel=".CONTEXT_MODULE."
WHERE
    bod.courseid=$courseid");

    // Check accessallgroups on each one
    $results=array();
    foreach($possible as $record) {
        if($record->cmgroupmode==VISIBLEGROUPS || 
            has_capability('moodle/site:accessallgroups',$record)) {
            $results[]=(object)array(
                'id'=>$record->cmid,'course'=>$record->cmcourse);
        }
    }
    return $results;
}

?>