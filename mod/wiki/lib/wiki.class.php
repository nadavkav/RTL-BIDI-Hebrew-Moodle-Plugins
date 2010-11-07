<?php
//$Id: wiki.class.php,v 1.22 2008/01/14 12:28:41 pigui Exp $

/**
 * This file contains wiki class.
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: wiki.class.php,v 1.22 2008/01/14 12:28:41 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Core
 */

require_once ($CFG->dirroot.'/lib/dmllib.php');
require_once ($CFG->dirroot.'/lib/weblib.php');

define('WIKIID', 1);
define('RECORD', 2);

class wiki{
	
	/**
	 * The atributes of the class wiki are the same as the colums of the 
	 * database table it represents.
	 */
	var $id;
	var $course;
	var $name;
	var $intro;
	var $introformat;
	var $pagename;
	var $timemodified;
	var $editable;
	var $attach;
	var $restore;
	var $editor;
	var $studentmode;
	var $teacherdiscussion;
	var $studentdiscussion;
	var $editanothergroup;
	var $editanotherstudent;
	var $votemode;
	var $listofteachers;
	var $editorrows;
	var $editorcols;
	var $wikicourse;

	/**
     *  Constructor of the class. 
     *	The parameter $type must be WIKIID or RECORD. It is used to indicate the type of the parameter $param.
     *
     *  @param integer $type. 
     *  @param object $param 
     */
		
	function wiki($type ,$param){
		
		// If the parameter type is WIKIID, we need to obtain the record from the
		// database and inicialize all the attributes of the class with it's value.
		if ($type == WIKIID){
			// Get the record.
			$record = get_record("wiki","id",$param);
			// Inicialize attributes with the value of the record.
			$this->id = $record->id;
			$this->course = $record->course;
			$this->name = $record->name;
			$this->intro = $record->intro;
			$this->introformat = $record->introformat;
			$this->pagename = $record->pagename;
			$this->timemodified = $record->timemodified;
			$this->editable = $record->editable;
			$this->attach = $record->attach;
			$this->restore = $record->restore;
			$this->editor = $record->editor;
			$this->studentmode = $record->studentmode;
			$this->teacherdiscussion = $record->teacherdiscussion;
			$this->studentdiscussion = $record->studentdiscussion;
			$this->editanothergroup = $record->editanothergroup;
			$this->editanotherstudent = $record->editanotherstudent;
			$this->votemode = $record->votemode;
			$this->listofteachers = $record->listofteachers;
			$this->editorrows = $record->editorrows;
			$this->editorcols = $record->editorcols;
			$this->wikicourse = $record->wikicourse;
		
		// If the parameter type is RECORD, we already have the record, so we only need to 
		// inicialize all the attributes of the class with it's value.
		} else if ($type == RECORD){
			// Inicialize t
            // tributes with the value of the record.
			$this->id = $param->id;
			$this->course = $param->course;
			$this->name = $param->name;
			$this->intro = $param->intro;
			$this->introformat = $param->introformat;
			$this->pagename = $param->pagename;
			$this->timemodified = $param->timemodified;
			$this->editable = $param->editable;
			$this->attach = $param->attach;
			$this->restore = $param->restore;
			$this->editor = $param->editor;
			$this->studentmode = $param->studentmode;
			$this->teacherdiscussion = $param->teacherdiscussion;
			$this->studentdiscussion = $param->studentdiscussion;
			$this->editanothergroup = $param->editanothergroup;
			$this->editanotherstudent = $param->editanotherstudent;
			$this->votemode = $param->votemode;
			$this->listofteachers = $param->listofteachers;
			$this->editorrows = $param->editorrows;
			$this->editorcols = $param->editorcols;
			$this->wikicourse = $param->wikicourse;
		} else{
			error("The parameter type must be WIKIID or RECORD");
		}		
	}
	
	///Get methods
	
	/**
     *  Database record of the wiki.
     *
     *  @return stdClass
     */
    function wiki_to_record() {
		
		$record = new stdClass();
		$record->course = $this->course;
		$record->name = $this->name;
		$record->intro = $this->intro;
		$record->introformat = $this->introformat;
		$record->pagename = $this->pagename;
		$record->timemodified = $this->timemodified;
		$record->editable = $this->editable;
		$record->attach = $this->attach;
		$record->restore = $this->restore;
		$record->editor = $this->editor;
		$record->studentmode = $this->studentmode;
		$record->teacherdiscussion = $this->teacherdiscussion;
		$record->studentdiscussion = $this->studentdiscussion;
		$record->editanothergroup = $this->editanothergroup;
		$record->editanotherstudent = $this->editanotherstudent;
		$record->votemode = $this->votemode;
		$record->listofteachers = $this->listofteachers;
		$record->editorrows = $this->editorrows;
		$record->editorcols = $this->editorcols;
		$record->wikicourse = $this->wikicourse;
		
        return $this;        
    }
    
    /**  Id of the wiki.
     *
     *  @return integer
     */
    /*function id() {
        return $this->id;
    }*/
    
    /**
     *  Course id of the instance of the wiki.
     *
     *  @return integer
     */
    function course_id() {
        return $this->course;
    }
    
    /**
     *  Name of the wiki.
     *
     *  @return string
     */
    function name() {
        return $this->name;
    }
	
	/**
	 * Wiki introduction.
	 * 
	 * @return string
	 */
	function intro(){
		return $this->intro;
	}    
	
	/**
	 * Wiki introduction format.
	 * 
	 * @return integer
	 */
	function introformat(){
		return $this->introformat;
	}
	
	/**
	 * Wiki first page name
	 * 
	 * @return string
	 */
	function pagename()	{
		return $this->pagename;
	}
	
	/**
	 * Last time wiki has been modified. Format: Absolut seconds
	 * 
	 * @return integer
	 */
	function time_modified(){
		return $this->timemodified;
	}
	
    /**  
     * Wether the wiki is editable by students.
     *
     *  @return boolean
     */
    function editable() {
        return $this->editable != 0;
    }
    
	/**
	 * Whether the students can attach files to the wiki.
	 * 
	 * @return boolean
	 */
	function attach(){
		return $this->attach != 0;
	}
	
	/**
	 * Whether the students can restore a previous version of the wiki.
	 *  
	 * @return boolean
	 */
	function restore(){
		return $this->restore != 0;
	}
	
	/**
	 * Wiki editor name. Only 3 diferent names: 'dfwiki','ewiki','htmleditor'.
	 * 
	 * @return string
	 */
	function editor(){
		return $this->editor;
	}
	
    /**
     *  Student mode of the wiki.
     *
     *  @return integer
     */
    function student_mode() {
        return $this->studentmode;
    }
        
	/**
	 * Whether teacher has discussion permissions.
	 * 
	 * @return boolean
	 */
	function teacher_discussion(){
		return $this->teacherdiscussion != 0;
	}
	
	/**
	 * Whether student has discusion permissions.
	 * 
	 * @return boolean
	 */
	function student_discussion(){
		return $this->studentdiscussion != 0;
	}
	
    /**
     *  Whether a student can edit pages of other students.
     *
     *  @return boolean
     */
    function edit_another_student() {
        return $this->editanotherstudent != 0;
    }

    /**
     *  Whether group editors can edit the wiki of another group.
     *
     *  @return boolean
     */
    function edit_another_group() {
        return $this->editanothergroup != 0;
    }
	
	/**
	 * Whether the wiki has vote privileges.
	 * 
	 * @return boolean
	 */
	function vote_mode(){
		return $this->votemode != 0;
		
	}
	
	/**
	 * Whether students can view wiki list of teachers.
	 *  
	 * @return boolean
	 */
	function list_of_teachers(){
		return $this->listofteachers != 0;
	}
	
	/**
	 * Number of editor rows. Defines editor size.
	 * 
	 * @return integer
	 */
	function editor_rows(){
		return $this->editorrows;
	}
	
	/**
	 * Number of editor columns. Defines editor size.
	 * 
	 * @return integer
	 */
	function editor_cols(){
		return $this->editorcols;
	}
	
	/**
	 * Wiki course.
	 * 
	 * @return mixed. It returns false if wiki has no association with any course. Return course id, integer, in any other case.
	 */
	function wiki_course(){
		return $this->wikicourse;
	}
	
	
///Set methods
	 
	/**
	 * Sets wiki id to $id
	 * 
	 * @param integer $id. This param is the new id number to asign to the wiki.
	 */
	function set_id($id){
		$this->id = $id;		
	}
	
	/**
	 * Sets course id of the wiki.
	 * 
	 *  @param integer $course_id. This param is the new course id number to asign to the wiki course id.
	 */
	function set_course($course_id){
		$this->course = $course_id;
	}
	
	/**
	 * Sets the name of the wiki.
	 * 
	 *  @param string $name. This param is the new name to asign to the name of the wiki.
	 */
	function set_name($name){
		$this->name = $name;
	}
	
	/**
	 * Sets the intro of the wiki.
	 * 
	 * @param string $intro. This param is the new text to asign to the wiki intro.
	 */
	function set_intro($intro){
		$this->intro = $intro;
	}
	
	/**
	 * Sets the format of the wiki intro.
	 * 
	 * @param integer $format. This param is the new format to asign to the format of the wiki intro.
	 */
	function set_intro_format($format){
		$this->wikiformat = $format;
	}
	
	/**
	 * Sets the name of the first page of the wiki.
	 * 
	 * @param string $page_name. This param is the name of the new first page of the wiki.
	 */
	function set_page_name($page_name){
		$this->pageaname = $page_name;
	}
	
	/**
	 * Sets the last time that wiki has been modified.
	 * 
	 * @param integer $time. This is the new time for the last modification of the wiki.
	 */
	function set_time_modified($time){
		$this->timemodified = $time;
	}
	
	
	/**
	 * Sets whether the wiki is editable.
	 * 
	 * @param boolean $editable.
	 */
	function set_editable($editable){
		$this->editable = $editable;
	}
	
	/**
	 * Sets whether the students can attach files to the wiki.
	 * 
	 * @param boolean $attach.
	 */
	function set_attach($attach){
		$this->attach = $attach;
	}
	
	/**
	 * Sets whether the students can restore a previous version of the wiki.
	 * 
	 * @param boolean $restore.
	 */
	function set_restore($restore){
		$this->restore = $restore;
	}
	
	/**
	 * Sets the name if the wiki editor. Only 3 diferent names: 'dfwiki','ewiki','htmleditor'.
	 * 
	 * @param string $editor. This param is the name of the new editor to asign to the attribute.
	 */
	function set_editor($editor){
		$this->editor = $editor;
	}
		
	/**
	 * Sets student mode of the wiki.
	 * 
	 * @param integer $student_mode
	 */
	function set_student_mode($student_mode){
		$this->studentmode = $student_mode;
	}
	
	/**
	 * Sets whether teacher has discussion permissions.
	 * 
	 * @param boolean $teacher_discussion.
	 */
	function set_teacher_discussion($teacher_discussion){
		$this->teacherdiscussion = $teacher_discussion;
	}
	
	/**
	 * Sets whether student has discussion permissions.
	 * 
	 * @param boolean $student_discussion.
	 */
	function set_student_discussion($student_discussion){
		$this->studentdiscussion = $student_discussion;
	}
	
	/**
	 * Sets whether group editors can edit the wiki of another group..
	 * 
	 * @param boolean $edit_another_group.
	 */
	function set_edit_another_group($edit_another_grop){
		$this->editanothergroup = $edit_another_grop;
	}
	
	
	/**
	 * Sets whether a student can edit pages of other students.
	 * 
	 * @param boolean $edit_another_student.
	 */
	function set_edit_another_student($edit_another_student){
		$this->editanotherstudent = $edit_another_student;
	}
	
	/**
	 * Sets whether the wiki has vote privileges.
	 * 
	 * @param boolean $vote_mode.
	 */
	function set_vote_mode($vote_mode){
		$this->votemode = $vote_mode;
	}
	
	/**
	 * Sets whether students can view the list of teachers of the wiki.
	 * 
	 * @param boolean $list_of_teachers.
	 */
	function set_list_of_teachers($list_of_teachers){
		$this->listofteachers = $list_of_teachers;
	}
	
	/**
	 * Sets number of editor rows. Defines editor size.
	 * 
	 * @param integer $edit_to_rows.
	 */
	function set_editor_rows($edit_to_rows){
		$this->editorrows = $edit_to_rows;
	}
	
	/**
	 * Sets number of editor cola. Defines editor size.
	 * 
	 * @param integer $edit_to_cols.
	 */
	function set_editor_cols($edit_to_cols){
		$this->editorcols = $edit_to_cols;
	}
	
	/**
	 * Sets wiki course.
	 * 
	 * @param integer $wiki_course. This param must be asigned 0 if wiki has no association with any course. In any other case it must be asigned by course id of the wiki.
	 */
	function set_wiki_course($wiki_course){
		$this->wikicourse = $wiki_course;
	}

}

?>
