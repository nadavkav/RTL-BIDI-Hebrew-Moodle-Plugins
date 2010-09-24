<?php
require_once(dirname(__FILE__).'/exceptions.php');
require_once(dirname(__FILE__).'/../config.php');
@include_once(dirname(__FILE__).'/utils.php');
require_once(dirname(__FILE__).'/utils_shared.php');

/**
 * Abstracts out basic XML operations for the backup files. Keeps track
 * of indent level and also throws exceptions if methods fail, so you 
 * don't need to check return values.
 *
 * @copyright &copy; 2006 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moduleapi
 */
class xml_backup {
    
    private $bf,$nestlevel;
    
    private $courseid,$uniquecode;
    
    private $stack;
    
    /**
     * @param int $bf File handle of backup file
     * @param object $preferences Backup 'preferences' object
     * @param int $nestlevel Initial indent level 
     */
    public function __construct($bf,$preferences,$nestlevel) {
        $this->bf=$bf;
        $this->nestlevel=$nestlevel;
        $this->stack=array();
        
        $this->courseid=$preferences->backup_course;
        $this->uniquecode=$preferences->backup_unique_code;
    }
    
    /**
     * @return Course ID this backup is for
     */
    public function get_courseid() {
        return $this->courseid;
    }
        
    /** 
     * Writes a full XML tag (open, content, close)
     * @param string $tag Name of tag (traditionally upper-case)
     * @param string $content Content (will be made safe for XML)
     * @param bool $endline If true (default), adds newline after end tag
     * @throws Exception if there's an error writing 
     */
    public function tag_full($tag,$content,$endline=true) {
        if(!fwrite ($this->bf,full_tag($tag,$this->nestlevel,$endline,$content))) {
            throw new Exception('Failed to write backup data',EXN_LOCAL_BACKUPWRITE);
        }        
    }
    
    /** 
     * Writes a full XML tag (open, content, close) only if the content is non-null.
     * @param string $tag Name of tag (traditionally upper-case)
     * @param string $content Content (will be made safe for XML)
     * @param bool $endline If true (default), adds newline after end tag
     * @throws Exception if there's an error writing 
     */
    public function tag_full_notnull($tag,$content,$endline=true) {
        if(!is_null($content)) {
            $this->tag_full($tag,$content,$endline);
        }
    }
    
    /** 
     * Writes an XML start tag and indents.
     * @param string $tag Name of tag (traditionally upper-case)
     * @param bool $endline If true (default), adds newline after start tag
     * @throws Exception if there's an error writing 
     */
    public function tag_start($tag,$endline=true) {
        if(!fwrite ($this->bf,start_tag($tag,$this->nestlevel,$endline))) {        
            throw new Exception('Failed to write backup data',EXN_LOCAL_BACKUPWRITE);
        }        
        $this->stack[$this->nestlevel]=$tag;
        $this->nestlevel++;
    }
    
    /** 
     * Unindents and writes an XML end tag. Also checks that your tags are
     * nested correctly.
     * @param string $tag Name of tag (traditionally upper-case)
     * @param bool $endline If true (default), adds newline after end tag
     * @throws Exception if tag doesn't match or there's an error writing 
     */
    public function tag_end($tag,$endline=true) {
        
        $this->nestlevel--;
        
        if(!array_key_exists($this->nestlevel,$this->stack)) {
            throw new Exception("Too many end tags: got extra $tag",EXN_LOCAL_BACKUPEMPTYSTACK);
        }
        
        $expected=$this->stack[$this->nestlevel];
        if($tag!=$expected) {
            throw new Exception("Incorrect XML nesting: got $tag, expecting $expected",EXN_LOCAL_BACKUPMISMATCH);
        }
        unset($this->stack[$this->nestlevel]);
        
        if(!fwrite ($this->bf,end_tag($tag,$this->nestlevel,$endline))) {     
            throw new Exception('Failed to write backup data',EXN_LOCAL_BACKUPWRITE);
        }        
    }
    
    /**
     * Copies a file from module data in the course.
     * @param string $module Name of module
     * @param string $file Source file or folder (relative to course/moddata/$module folder)
     * @throws Exception if file copy fails 
     */
    public function copy_module_file($module,$file) {
        $relative="moddata/$module/$file";
        global $CFG;
        $this->copy_file(
            $CFG->dataroot.'/'.$this->courseid.'/'.$relative,
            $relative);
    }

    /**
     * Copies a file into backup area.
     * @param string $source Full path of source file
     * @param string $target Target path relative to the backup folder (e.g. 'moddata/frog/myfile.txt')
     * @throws Exception if file copy fails 
     */    
    public function copy_file($source,$target) {
        global $CFG;
        $basefolder=$CFG->dataroot.'/temp/backup/'.$this->uniquecode.'/';
        $targetfolder=dirname($basefolder.$target);
        if(!@mkdir_recursive($targetfolder)) {
            throw new Exception("Failed to create folder for backup file ($targetfolder)",
                EXN_LOCAL_BACKUPFOLDER);   
        }
        if(!@backup_copy_file(
            $source,
            $basefolder.$target)) {
            throw new Exception("Failed to copy file for backup ($source)",EXN_LOCAL_BACKUPCOPY);   
        }      
    }
}

?>