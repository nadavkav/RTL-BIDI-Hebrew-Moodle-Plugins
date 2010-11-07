<?php

/**
 * This file contains PHPUnit tests for Wiki Persistor Core
 * 
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: testwiki_persistor.php,v 1.4 2008/01/10 10:59:05 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package UnitTests
 */
 
require_once(dirname(__FILE__) . '/../../../../config.php');

global $CFG;

require_once($CFG->libdir . '/simpletestlib.php');

// Include the code to test
require_once($CFG->dirroot . '/mod/wiki/db/moodle/wiki_persistor.php');

// Include necesary classes.
require_once($CFG->dirroot . '/mod/wiki/lib/wiki.class.php');
require_once($CFG->dirroot . '/mod/wiki/lib/wiki_page.class.php');

class wiki_test_wiki_persistor extends UnitTestCase {
	
	/**
	  * This function tests the whole class wiki manager.
	  * By the moment this functionis not implemented but test is prepared
	  */
	 function test_wiki_persistor()
	 {
		 //First of all test wiki and wiki page are preparated.
		 $wiki = new stdClass();
		 $wiki_page = new stdClass();
		
		 // Create a default wiki.
		 $wiki->course = 0;
		 $wiki->name = 'WikiUnitSimpleTest';
		 $wiki->intro = 'Simple Test to wiki class';
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
		 $wiki_page->pagename = "SimpleTest Name";
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
		 
		 $wiki_persistor = new wiki_persistor(); 
		 
		 $message = 'Whether wikis gets by the id are the same';
		 $this->assertEqual($wiki_persistor->get_wiki_by_id($id), $wiki_object, $message);
		 
		 $message = 'Whether wiki pages gets by id are the same';
		 $this->assertEqual($wiki_persistor->get_wiki_page_by_id($id_page), $wiki_page_object, $message);
		 
		 delete_records("wiki",'id',$id);
		 
		 delete_records("wiki_pages",'id',$id_page);
	 }

}

?>