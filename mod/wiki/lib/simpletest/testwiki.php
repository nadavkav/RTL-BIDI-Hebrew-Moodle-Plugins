<?php

/**
 * This file contains PHPUnit tests for Wiki Core
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: testwiki.php,v 1.15 2008/01/16 12:15:30 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package UnitTests
 */
 
require_once(dirname(__FILE__) . '/../../../../config.php');

global $CFG;

require_once($CFG->libdir . '/simpletestlib.php');

// Include the code to test
require_once($CFG->dirroot . '/mod/wiki/lib/wiki.class.php');
require_once($CFG->dirroot . '/mod/wiki/lib/wiki_page.class.php');
require_once($CFG->dirroot . '/mod/wiki/lib/wiki_discussion_page.class.php');
require_once($CFG->dirroot . '/mod/wiki/lib/wiki_manager.php');

/** This class contains the test cases for the functions in wiki.class.php, wiki_page.class.php
 *  wiki_discussion_page.class.php and wiki_manager.php. 
 *  To test separately this SimpleTest introduce in text box "Only run tests in" 
 *  this path "mod/wiki/lib/simpletest/testwiki.php" 
 */ 
class wiki_test_wiki extends UnitTestCase {
	 
	 // Test code
	 
	 /**
	  * This function tests the whole class wiki.
	  */
	 function test_wiki(){
		 
		 $wiki = new stdClass();
		
		 // Create a default wiki.
		 $wiki->course = 0;
		 $wiki->name = '[GET]WikiUnitSimpleTest';
		 $wiki->intro = '[GET]Simple Test to wiki class';
		 $wiki->introformat = 1;
		 $wiki->pagename = 'First Page Test';
		 // Test with past time: Tuesday, 15 May 2007 17:45
		 $wiki->timemodified = 1171902549;
		 $wiki->editable = 1;
		 $wiki->attach = 0;
		 $wiki->restore = 0;
		 $wiki->editor = 'dfwiki';
		 $wiki->studentmode = 1;
		 $wiki->teacherdiscussion = 0;
		 $wiki->studentdiscussion = 0;
		 $wiki->evaluation = 'noeval';
		 $wiki->notetype = 'quant';
		 $wiki->editanothergroup = 0;
		 $wiki->editanotherstudent = 0;
		 $wiki->votemode = 0;
		 $wiki->listofteachers = 0;
		 $wiki->editorrows = 40;
		 $wiki->editorcols = 60;
		 $wiki->wikicourse = 0;
		
		 // To test wiki with any concret parameter, only change it 
		 // at variables above defined.
		 
		 // Example of another default wiki:
		 
		 /*$wiki->course = 1;
		 $wiki->name = 'WikiUnitSecondSimpleTest';
		 $wiki->intro = 'Second Simple Test to wiki class';
		 $wiki->introformat = 2;
		 $wiki->pagename = 'Second page';
		 $wiki->timemodified = 1172993099;
		 $wiki->editable = 0;
		 $wiki->attach = 1;
		 $wiki->upload = 1;
		 $wiki->restore = 1;
		 $wiki->editor = 'dfwiki';
		 $wiki->groupmode = 2;
		 $wiki->studentmode = 1;
		 $wiki->teacherdiscussion = 1;
		 $wiki->studentdiscussion = 1;
		 $wiki->evaluation = 'noeval';
		 $wiki->notetype = 'qual';
		 $wiki->editanothergroup = 1;
		 $wiki->editanotherstudent = 1;
		 $wiki->votemode = 0;
		 $wiki->listofteachers = 0;
		 $wiki->editorrows = 100;
		 $wiki->editorcols = 250;
		 $wiki->wikicourse = 0;
		 $wiki->filetemplate = '';*/
		 
		 
		 $id = insert_record("wiki", $wiki);
		 
		 $wiki->id = $id;
		 $wiki_object = new wiki(RECORD,$wiki);
		 
		 
		 // Test part of gets methods from wiki class.
		 
		 $message = '[GET] Whether wiki identifiers are equals';
		 $this->assertEqual($id, $wiki_object->id(), $message);
		 
		 $message = '[GET] Whether course identifiers are equals';
		 $this->assertEqual($wiki->course, $wiki_object->course_id(), $message);
		 
		 $message = '[GET] Whether names are equals';
		 $this->assertEqual($wiki->name, $wiki_object->name(), $message);
		 
		 $message = '[GET] Whether intros are equals';
		 $this->assertEqual($wiki->intro, $wiki_object->intro(), $message);
		 
		 $message = '[GET] Whether introformats are equals';
		 $this->assertEqual($wiki->introformat, $wiki_object->introformat(), $message);
		 
		 $message= '[GET] Whether pagenames are equals';
		 $this->assertEqual($wiki->pagename, $wiki_object->pagename(), $message);
		 
		 $message = '[GET] Whether timemodifieds are equals';
		 $this->assertEqual($wiki->timemodified, $wiki_object->time_modified(), $message);		 
		 
		 $message = '[GET] Whether editables are equals';
		 $this->assertEqual($wiki->editable, $wiki_object->editable(), $message);
		 
		 $message = '[GET] Whether attachs are equals';
		 $this->assertEqual($wiki->attach, $wiki_object->attach(), $message);
		 
		 $message = '[GET] Whether restores are equals';
		 $this->assertEqual($wiki->restore, $wiki_object->restore(), $message);
		 
		 $message = '[GET] Whether editors are equals';
		 $this->assertEqual($wiki->editor, $wiki_object->editor(), $message);
		 		 
		 $message = '[GET] Whether studentmodes are equals';
		 $this->assertEqual($wiki->studentmode, $wiki_object->student_mode(), $message);
		 
		 $message = '[GET] Whether teacherdiscussions are equals';
		 $this->assertEqual($wiki->teacherdiscussion, $wiki_object->teacher_discussion(), $message);
		 
		 $message = '[GET] Whether studentdiscussions are equals';
		 $this->assertEqual($wiki->studentdiscussion, $wiki_object->student_discussion(), $message);
		 
		 $message = '[GET] Whether evaluations are equals';
		 $this->assertEqual($wiki->evaluation, $wiki_object->evaluation(), $message);
		 
		 $message = '[GET] Whether notetypes are equals';
		 $this->assertEqual($wiki->notetype, $wiki_object->note_type(), $message);
		 
		 $message = '[GET] Whether editanothergroups are equals';
		 $this->assertEqual($wiki->editanothergroup, $wiki_object->edit_another_group(), $message);
		 
		 $message = '[GET] Whether editabotherstudents are equals';
		 $this->assertEqual($wiki->editanotherstudent, $wiki_object->edit_another_student(), $message);
		 
		 $message = '[GET] Whether votemodes are equals';
		 $this->assertEqual($wiki->votemode,$wiki_object->vote_mode(), $message);
		 
		 $message = '[GET] Whether listofteachers are equals';
		 $this->assertEqual($wiki->listofteachers, $wiki_object->list_of_teachers(), $message);
		 
		 $message = '[GET] Whether editorrows are equals';
		 $this->assertEqual($wiki->editorrows, $wiki_object->editor_rows(), $message);
		 
		 $message = '[GET] Whether editorcols are equals';
		 $this->assertEqual($wiki->editorcols, $wiki_object->editor_cols(), $message);
		 
		 $message = '[GET] Whether wikicourses are equals';
		 $this->assertEqual($wiki->wikicourse, $wiki_object->wiki_course(), $message);
		 
		 // Test part of sets methods from wiki class.
		 
		 // Create another wiki to set the old one.
		 $wiki->id = 9999;
		 $wiki->course = 1;
		 $wiki->name = '[SET]WikiUnitSimpleTest';
		 $wiki->intro = '[SET]Simple Test to wiki class';
		 $wiki->introformat = 1;
		 $wiki->pagename = 'First Page Test';
		 // Test with past time: Tuesday, 15 May 2007 17:45
		 $wiki->timemodified = 1171902549;
		 $wiki->editable = 1;
		 $wiki->attach = 0;
		 $wiki->restore = 0;
		 $wiki->editor = 'dfwiki';
		 $wiki->studentmode = 1;
		 $wiki->teacherdiscussion = 0;
		 $wiki->studentdiscussion = 0;
		 $wiki->evaluation = 'noeval';
		 $wiki->notetype = 'quant';
		 $wiki->editanothergroup = 0;
		 $wiki->editanotherstudent = 0;
		 $wiki->votemode = 0;
		 $wiki->listofteachers = 0;
		 $wiki->editorrows = 40;
		 $wiki->editorcols = 60;
		 $wiki->wikicourse = 0;
		 
		 // To second test defined above of the GET test
		 /*$wiki->id = 9999;
		 $wiki->course = 1;
		 $wiki->name = 'WikiUnitSecondSimpleTest';
		 $wiki->intro = 'Second Simple Test to wiki class';
		 $wiki->introformat = 2;
		 $wiki->pagename = 'Second page';
		 $wiki->timemodified = 1172993099;
		 $wiki->editable = 0;
		 $wiki->attach = 1;
		 $wiki->upload = 1;
		 $wiki->restore = 1;
		 $wiki->editor = 'dfwiki';
		 $wiki->groupmode = 2;
		 $wiki->studentmode = 1;
		 $wiki->teacherdiscussion = 1;
		 $wiki->studentdiscussion = 1;
		 $wiki->evaluation = 'noeval';
		 $wiki->notetype = 'qual';
		 $wiki->editanothergroup = 1;
		 $wiki->editanotherstudent = 1;
		 $wiki->votemode = 0;
		 $wiki->listofteachers = 0;
		 $wiki->editorrows = 100;
		 $wiki->editorcols = 250;
		 $wiki->wikicourse = 0;
		 $wiki->filetemplate = '';*/
		 
		 $message = '[SET] Whether wiki identifiers are equals';
		 $wiki_object->set_id($wiki->id);
		 $this->assertEqual($wiki->id,$wiki_object->id, $message);
		 
		 $message = '[SET] Whether course identifiers are equals';
		 $wiki_object->set_course($wiki->course);
		 $this->assertEqual($wiki->course, $wiki_object->course_id(), $message);
		 
		 $message = '[SET] Whether names are equals';
		 $wiki_object->set_name($wiki->name);
		 $this->assertEqual($wiki->name, $wiki_object->name(), $message);
		 
		 $message = '[SET] Whether intros are equals';
		 $wiki_object->set_intro($wiki->intro);
		 $this->assertEqual($wiki->intro, $wiki_object->intro(), $message);
		 
		 $message = '[SET] Whether introformats are equals';
		 $wiki_object->set_intro_format($wiki->introformat);
		 $this->assertEqual($wiki->introformat, $wiki_object->introformat(), $message);
		 
		 $message= '[SET] Whether pagenames are equals';
		 $wiki_object->set_page_name($wiki->pagename);
		 $this->assertEqual($wiki->pagename, $wiki_object->pagename(), $message);
		 
		 $message = '[SET] Whether timemodifieds are equals';
		 $wiki_object->set_time_modified($wiki->timemodified);
		 $this->assertEqual($wiki->timemodified, $wiki_object->time_modified(), $message);		 
		 
		 $message = '[SET] Whether editables are equals';
		 $wiki_object->set_editable($wiki->editable);
		 $this->assertEqual($wiki->editable, $wiki_object->editable(), $message);
		 
		 $message = '[SET] Whether attachs are equals';
		 $wiki_object->set_attach($wiki->attach);
		 $this->assertEqual($wiki->attach, $wiki_object->attach(), $message);
		 
		 $message = '[SET] Whether restores are equals';
		 $wiki_object->set_restore($wiki->restore);
		 $this->assertEqual($wiki->restore, $wiki_object->restore(), $message);
		 
		 $message = '[SET] Whether editors are equals';
		 $wiki_object->set_editor($wiki->editor);
		 $this->assertEqual($wiki->editor, $wiki_object->editor(), $message);
		 		 
		 $message = '[SET] Whether studentmodes are equals';
		 $wiki_object->set_student_mode($wiki->studentmode);
		 $this->assertEqual($wiki->studentmode, $wiki_object->student_mode(), $message);
		 
		 $message = '[SET] Whether teacherdiscussions are equals';
		 $wiki_object->set_teacher_discussion($wiki->teacherdiscussion);
		 $this->assertEqual($wiki->teacherdiscussion, $wiki_object->teacher_discussion(), $message);
		 
		 $message = '[SET] Whether studentdiscussions are equals';
		 $wiki_object->set_student_discussion($wiki->studentdiscussion);
		 $this->assertEqual($wiki->studentdiscussion, $wiki_object->student_discussion(), $message);
		 
		 $message = '[SET] Whether evaluations are equals';
		 $wiki_object->set_evaluation($wiki->evaluation);
		 $this->assertEqual($wiki->evaluation, $wiki_object->evaluation(), $message);
		 
		 $message = '[SET] Whether notetypes are equals';
		 $wiki_object->set_note_type($wiki->notetype);
		 $this->assertEqual($wiki->notetype, $wiki_object->note_type(), $message);
		 
		 $message = '[SET] Whether editanothergroups are equals';
		 $wiki_object->set_edit_another_group($wiki->editanothergroup);
		 $this->assertEqual($wiki->editanothergroup, $wiki_object->edit_another_group(), $message);
		 
		 $message = '[SET] Whether editabotherstudents are equals';
		 $wiki_object->set_edit_another_student($wiki->editanotherstudent);
		 $this->assertEqual($wiki->editanotherstudent, $wiki_object->edit_another_student(), $message);
		 
		 $message = '[SET] Whether votemodes are equals';
		 $wiki_object->set_vote_mode($wiki->votemode);
		 $this->assertEqual($wiki->votemode,$wiki_object->vote_mode(), $message);
		 
		 $message = '[SET] Whether listofteachers are equals';
		 $wiki_object->set_list_of_teachers($wiki->listofteachers);
		 $this->assertEqual($wiki->listofteachers, $wiki_object->list_of_teachers(), $message);
		 
		 $message = '[SET] Whether editorrows are equals';
		 $wiki_object->set_editor_rows($wiki->editorrows);
		 $this->assertEqual($wiki->editorrows, $wiki_object->editor_rows(), $message);
		 
		 $message = '[SET] Whether editorcols are equals';
		 $wiki_object->set_editor_cols($wiki->editorcols);
		 $this->assertEqual($wiki->editorcols, $wiki_object->editor_cols(), $message);
		 
		 $message = '[SET] Whether wikicourses are equals';
		 $wiki_object->set_wiki_course($wiki->wikicourse);
		 $this->assertEqual($wiki->wikicourse, $wiki_object->wiki_course(), $message);
		 
		 // Delete the row inserted at the begining of the test. 
		 delete_records("wiki",'id',$id);
	 }
	 
	 /**
	  * This function tests the whole class wiki_page.
	  */
	 function test_wiki_page(){
		 
		 $wiki_page = new stdClass();
		 
		 // Create a default wiki_page.
		 $wiki_page->pagename = "[GET]SimpleTest Name";
		 $wiki_page->version = 2;
		 $wiki_page->content = "This is the wiki page content.";
		 $wiki_page->author = "Laura";
		 $wiki_page->userid = 3;
		 // Test with past time: Wednesday, 30 May 2007 19:42
		 $wiki_page->created = 1180546919;
		 $wiki_page->lastmodified = 1180546944;
		 $wiki_page->refs = "http://morfeo.upc.es/crom/course/view.php?id=4";
		 $wiki_page->hits = 1;
		 $wiki_page->editable = 1;
		 $wiki_page->highlight = 1;
		 $wiki_page->dfwiki = 25;
		 $wiki_page->editor = "nwiki";
		 $wiki_page->groupid = 0;
		 $wiki_page->ownerid = 0;
		 $wiki_page->evaluation = "";
		 
		 $id_page = insert_record("wiki_pages", $wiki_page);
		 
		 $wiki_page->id = $id_page;
		 $wiki_page_object = new wiki_page(RECORD,$wiki_page);
		 
		 // Test part of gets methods from wiki page class.
		 
		 $message = '[GET] Whether wiki page identifiers are equals';
		 $this->assertEqual($id_page, $wiki_page_object->id, $message);
		 
		 $message = '[GET] Whether wiki page names are equals';
		 $this->assertEqual($wiki_page->pagename, $wiki_page_object->page_name(), $message);
		 
		 $message = '[GET] Whether wiki page versions are equals';
		 $this->assertEqual($wiki_page->version, $wiki_page_object->version(), $message);
		 
		 $message = '[GET] Whether wiki page authors are equals';
		 $this->assertEqual($wiki_page->author, $wiki_page_object->author(), $message);
		 
		 $message = '[GET] Whether wiki page user ids are equals';
		 $this->assertEqual($wiki_page->userid, $wiki_page_object->user_id(), $message);
		 
		 $message = '[GET] Whether wiki page createds are equals';
		 $this->assertEqual($wiki_page->created, $wiki_page_object->created(), $message);
		 
		 $message = '[GET] Whether wiki page lastmodifieds are equals';
		 $this->assertEqual($wiki_page->lastmodified, $wiki_page_object->last_modified(), $message);
		 
		 $message = '[GET] Whether wiki page refs are equals';
		 $this->assertEqual($wiki_page->refs, $wiki_page_object->refs(), $message);
		 
		 $message = '[GET] Whether wiki page hits are equals';
		 $this->assertEqual($wiki_page->hits, $wiki_page_object->hits(), $message);
		 
		 $message = '[GET] Whether wiki page editables are equals';
		 $this->assertEqual($wiki_page->editable, $wiki_page_object->editable(), $message);
		 
		 $message = '[GET] Whether wiki page highlights are equals';
		 $this->assertEqual($wiki_page->highlight, $wiki_page_object->high_light(), $message);
		 
		 $message = '[GET] Whether wiki page dfwikis are equals';
		 $this->assertEqual($wiki_page->dfwiki, $wiki_page_object->dfwiki(), $message);
		 
		 $message = '[GET] Whether wiki page editors are equals';
		 $this->assertEqual($wiki_page->editor, $wiki_page_object->editor(), $message);
		 
		 $message = '[GET] Whether wiki page group ids are equals';
		 $this->assertEqual($wiki_page->groupid, $wiki_page_object->group_id(), $message);
		 
		 $message = '[GET] Whether wiki page owner ids are equals';
		 $this->assertEqual($wiki_page->ownerid, $wiki_page_object->owner_id(), $message);
		 
		 $message = '[GET] Whether wiki page editors are equals';
		 $this->assertEqual($wiki_page->evaluation, $wiki_page_object->evaluation(), $message);
		 
		 // Test part of sets methods from wiki page class.
		 
		 // We store refs string to test set function of add refs.
		 
		 $refs_str = $wiki_page->refs;
		 
		 // Create another wiki to set the old one.
		 $wiki_page->id = 777;
		 $wiki_page->pagename = "[SET]SimpleTest Name";
		 $wiki_page->version = 3;
		 $wiki_page->content = "This is the wiki page content for set test.";
		 $wiki_page->author = "LM";
		 $wiki_page->userid = 4;
		 // Test with past time: Wednesday, 30 May 2007 19:42
		 $wiki_page->created = 1180546829;
		 $wiki_page->lastmodified = 1180546945;
		 $wiki_page->refs = "http://morfeo.upc.es/crom/course/view.php?id=2";
		 
		 // Needed to set add refs
		 $refs_str = $refs_str + "|" + $wiki_page->refs;
		 
		 $wiki_page->hits = 0;
		 $wiki_page->editable = 0;
		 $wiki_page->highlight = 0;
		 $wiki_page->dfwiki = 50;
		 $wiki_page->editor = "nwiki";
		 $wiki_page->groupid = 0;
		 $wiki_page->ownerid = 0;
		 $wiki_page->evaluation = "";
		 
		 $message = '[SET] Whether wiki page identifiers are equals';
		 $wiki_page_object->set_id($wiki_page->id);
		 $this->assertEqual($wiki_page->id,$wiki_page_object->id, $message);
		 
		 $message = '[SET] Whether page names are equals';
		 $wiki_page_object->set_page_name($wiki_page->pagename);
		 $this->assertEqual($wiki_page->pagename, $wiki_page_object->page_name(), $message);
		 
		 $message = '[SET] Whether page versions are equals';
		 $wiki_page_object->set_version($wiki_page->version);
		 $this->assertEqual($wiki_page->version, $wiki_page_object->version(), $message);
		 
		 $message = '[SET] Whether page versions incremented are equals';
		 $wiki_page_object->inc_version();
		 $this->assertEqual($wiki_page->version + 1, $wiki_page_object->version(), $message);
		 
		 $message = '[SET] Whether page contents are equals';
		 $wiki_page_object->set_content($wiki_page->content);
		 $this->assertEqual($wiki_page->content, $wiki_page_object->content(), $message);
		 
		 $message = '[SET] Whether page authors are equals';
		 $wiki_page_object->set_author($wiki_page->author);
		 $this->assertEqual($wiki_page->author, $wiki_page_object->author(), $message);
		 
		 $message = '[SET] Whether page users ids are equals';
		 $wiki_page_object->set_user_id($wiki_page->userid);
		 $this->assertEqual($wiki_page->userid, $wiki_page_object->user_id(), $message);
		 
		 $message = '[SET] Whether page createds are equals';
		 $wiki_page_object->set_created($wiki_page->created);
		 $this->assertEqual($wiki_page->created, $wiki_page_object->created(), $message);
		 
		 $message = '[SET] Whether page last modifieds are equals';
		 $wiki_page_object->set_last_modified($wiki_page->lastmodified);
		 $this->assertEqual($wiki_page->lastmodified, $wiki_page_object->last_modified(), $message);
		 
		 $message = '[SET] Whether page refs are equals';
		 $wiki_page_object->set_refs($wiki_page->refs);
		 $this->assertEqual($wiki_page->refs, $wiki_page_object->refs(), $message);
		 
		 $message = '[SET] Whether page added refs are equals';
		 $wiki_page_object->add_refs($wiki_page->refs);
		 $this->assertEqual($refs_str, $wiki_page_object->refs(), $message);
		 
		 $message = '[SET] Whether page hits are equals';
		 $wiki_page_object->set_hits($wiki_page->hits);
		 $this->assertEqual($wiki_page->hits, $wiki_page_object->hits(), $message);
		 
		 $message = '[SET] Whether page ediotables are equals';
		 $wiki_page_object->set_editable($wiki_page->editable);
		 $this->assertEqual($wiki_page->editable, $wiki_page_object->editable(), $message);
		 
		 $message = '[SET] Whether page owners ids are equals';
		 $wiki_page_object->set_owner_id($wiki_page->ownerid);
		 $this->assertEqual($wiki_page->ownerid, $wiki_page_object->owner_id(), $message);
		 
		 $message = '[SET] Whether page evaluations are equals';
		 $wiki_page_object->set_evaluation($wiki_page->evaluation);
		 $this->assertEqual($wiki_page->evaluation, $wiki_page_object->evaluation(), $message);
		 
		 // Delete the row inserted at the begining of the test. 
		 delete_records("wiki_pages",'id',$id_page);
		 
	 }
	 
	 /**
	  * This function tests the whole class wiki_discussion_page.
	  */
	 function test_wiki_discussion_page()
	 {
		 $wiki_page_discussion = new stdClass();
		 
		 // Create a default wiki_page.
		 $wiki_page_discussion->pagename = "SimpleTest Discussion";
		 $wiki_page_discussion->version = 2;
		 $wiki_page_discussion->content = "This is the wiki discussion page content.";
		 $wiki_page_discussion->author = "Laura";
		 $wiki_page_discussion->userid = 3;
		 // Test with past time: Wednesday, 30 May 2007 19:42
		 $wiki_page_discussion->created = 1180546919;
		 $wiki_page_discussion->lastmodified = 1180546944;
		 $wiki_page_discussion->refs = "http://morfeo.upc.es/crom/course/view.php?id=4";
		 $wiki_page_discussion->hits = 1;
		 $wiki_page_discussion->editable = 1;
		 $wiki_page_discussion->highlight = 1;
		 $wiki_page_discussion->dfwiki = 25;
		 $wiki_page_discussion->editor = "nwiki";
		 $wiki_page_discussion->groupid = 0;
		 $wiki_page_discussion->ownerid = 0;
		 $wiki_page_discussion->evaluation = "";
		 
		 $id_page = insert_record("wiki_pages", $wiki_page_discussion);
		 
		 $wiki_page_discussion->id = $id_page;
		 $wiki_page_object = new wiki_discussion_page(RECORD,$wiki_page_discussion);
		 
		 // Test part of methods from wiki discussion page class.
		 
		 // New content to add.
		 $content = "Content added from discussion.";
		 
		 // Content to test.
		 $content_added = $wiki_page_object->content() + $content;
		 
		 $wiki_page_object->add_content($content);
		 
		 $message = '[SET] Whether page added contents are equals';
		 $wiki_page_object->add_content($content);
		 $this->assertEqual($content_added, $wiki_page_object->content(), $message);
		 
		 delete_records("wiki_pages",'id',$id_page); 
		
	 }
	 
	 
	 /**
	  * This function tests the whole class wiki manager.
	  * By the moment this functionis not implemented but test is prepared
	  */
	 function test_wiki_manager()
	 {
		 //First of all test wiki and wiki page are preparated.
		 $wiki = new stdClass();
		 $wiki_page = new stdClass();
		
		 // Create a default wiki.
		 $wiki->course = 0;
		 $wiki->name = '[GET]WikiUnitSimpleTest';
		 $wiki->intro = '[GET]Simple Test to wiki class';
		 $wiki->introformat = 1;
		 $wiki->pagename = 'First Page Test';
		 // Test with past time: Tuesday, 15 May 2007 17:45
		 $wiki->timemodified = 1171902549;
		 $wiki->editable = 1;
		 $wiki->attach = 0;
		 $wiki->restore = 0;
		 $wiki->editor = 'dfwiki';
		 $wiki->studentmode = 1;
		 $wiki->teacherdiscussion = 0;
		 $wiki->studentdiscussion = 0;
		 $wiki->evaluation = 'noeval';
		 $wiki->notetype = 'quant';
		 $wiki->editanothergroup = 0;
		 $wiki->editanotherstudent = 0;
		 $wiki->votemode = 0;
		 $wiki->listofteachers = 0;
		 $wiki->editorrows = 40;
		 $wiki->editorcols = 60;
		 $wiki->wikicourse = 0;
		 
		 // Create a default wiki_page.
		 $wiki_page->pagename = "[GET]SimpleTest Name";
		 $wiki_page->version = 2;
		 $wiki_page->content = "This is the wiki page content.";
		 $wiki_page->author = "Laura";
		 $wiki_page->userid = 3;
		 // Test with past time: Wednesday, 30 May 2007 19:42
		 $wiki_page->created = 1180546919;
		 $wiki_page->lastmodified = 1180546944;
		 $wiki_page->refs = "http://morfeo.upc.es/crom/course/view.php?id=4";
		 $wiki_page->hits = 1;
		 $wiki_page->editable = 1;
		 $wiki_page->highlight = 1;
		 $wiki_page->dfwiki = 25;
		 $wiki_page->editor = "nwiki";
		 $wiki_page->groupid = 0;
		 $wiki_page->ownerid = 0;
		 $wiki_page->evaluation = "";
		 
		 $id = insert_record("wiki", $wiki);
		 
		 $wiki->id = $id;
		 $wiki_object = new wiki(RECORD,$wiki);
		 
		 $id_page = insert_record("wiki_pages", $wiki_page);
		 
		 $wiki_page->id = $id_page;
		 $wiki_page_object = new wiki_page(RECORD,$wiki_page);
		 
		 // Now we are going to test wiki_manager in MOODLE environement. To do this we
		 // create wiki_manager with this environement.
		 
		 $type = "MOODLE";
		 
		 $wiki_manager = new wiki_manager($type); 
		 
		 $message = 'Whether wikis gets by the id are the same';
		 $this->assertEqual($wiki_manager->get_wiki_by_id($id), $wiki_object, $message);
		 
		 $message = 'Whether wiki pages gets by id are the same';
		 $this->assertEqual($wiki_manager->get_wiki_page_by_id($id_page), $wiki_page_object, $message);
		 
		 delete_records("wiki",'id',$id);
		 
		 delete_records("wiki_pages",'id',$id_page);
		 
		 // Next part we are going to test wiki_manager in OKI environement. To do this 
		 // we create wiki_manager with this environement.
		 
		 /*$type = "OKI";
		 
		 $wiki_manager = new wiki_manager($type);*/
	 }
	 
 }
 
?>