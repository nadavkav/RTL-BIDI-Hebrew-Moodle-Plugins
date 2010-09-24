<?php
global $CFG;
 
require_once($CFG->libdir. '/simpletestlib/unit_tester.php');
require_once($CFG->libdir. '/simpletestlib.php');

require_once(dirname(__FILE__).'/../../../lib/ddllib.php');
require_once(dirname(__FILE__).'/../ouwiki.php');

class test_comments extends UnitTestCase {
    
    const TESTPREFIX='unittest_';
    
    function setUp() {
        // Set db prefix for testing
        global $CFG,$USER;
        $this->beforeprefix=$CFG->prefix;
        $this->beforeuser=$USER;
        $CFG->prefix=self::TESTPREFIX;
        
        // Delete existing tables
        $this->delete_tables();
        
        // Install new wiki tables and core user table
        ob_start();
        install_from_xmldb_file(dirname(__FILE__).'/../db/install.xml');
        ob_end_clean();
        load_test_table(self::TESTPREFIX.'user',array(
            array('id', 'username', 'firstname', 'lastname'),
            array(1,    'u1',       'user',      'one',    ),
            array(2,    'u2',       'user',      'two',    ),
            array(3,    'u3',       'user',      'three'  )
            ));                
        $USER=get_record('user','id',1);
    }
    
    private function delete_tables() {
        global $db;
        wipe_tables(self::TESTPREFIX, $db);
        wipe_sequences(self::TESTPREFIX, $db);
    }
    
    function tearDown() {
        // Wipe data
        $this->delete_tables();
        
        // Put prefix back
        global $CFG,$USER;
        $CFG->prefix=$this->beforeprefix;
        $USER=$this->beforeuser;
    }
    
    function test_everything() {
        
        // Add a bunch of comments
        ouwiki_add_comment(13,'frog','Frogs','My happy comment','This comment has a title');
        ouwiki_add_comment(13,'frog','Frogs',null,'This one does not');
        ouwiki_add_comment(13,null,null,null,'This comment is on the main page section');
        ouwiki_add_comment(13,'zombie','Zombies',null,'This one is on a different section');
        ouwiki_add_comment(66,null,null,null,'This one is on another page');
        ouwiki_add_comment(66,'frog','Frogs',null,'This one is on another page but same section');
        ouwiki_add_comment(13,null,null,null,'Main 2',2);
        ouwiki_add_comment(13,null,null,null,'Main 3',OUWIKI_SYSTEMUSER);
        ouwiki_add_comment(13,null,null,null,'Main 4');
        ouwiki_add_comment(13,'frog','Frogs',null,'Frog 3');
        ouwiki_add_comment(13,'frog','Frogs',null,'Frog 4');
        ouwiki_add_comment(13,'frog','Frogs',null,'Frog 5');
        
        // Check sections
        $sections=get_records('ouwiki_sections');
        $this->assertEqual(count($sections),5);
        $this->assertEqual((array)array_shift($sections),
            array('pageid'=>13,'xhtmlid'=>'frog','title'=>'Frogs','id'=>1));
        $this->assertEqual((array)array_shift($sections),
            array('pageid'=>13,'xhtmlid'=>null,'title'=>null,'id'=>2));
        $this->assertEqual((array)array_shift($sections),
            array('pageid'=>13,'xhtmlid'=>'zombie','title'=>'Zombies','id'=>3));
        $this->assertEqual((array)array_shift($sections),
            array('pageid'=>66,'xhtmlid'=>null,'title'=>null,'id'=>4));
        $this->assertEqual((array)array_shift($sections),
            array('pageid'=>66,'xhtmlid'=>'frog','title'=>'Frogs','id'=>5));
            
        $comments=get_records('ouwiki_comments');
        $this->assertEqual(count($comments),12);
        
        // Check timeposted once then chuck it because it makes comparison harder
        $this->assertTrue(
            (time() - $comments[1]->timeposted <5) && 
            (time() - $comments[1]->timeposted >=0));            
        for($i=1;$i<=12;$i++) {
            unset($comments[$i]->timeposted);
        }
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>1,'title'=>'My happy comment','xhtml'=>'This comment has a title',
                'userid'=>1,'deleted'=>0,'id'=>1));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>1,'title'=>null,'xhtml'=>'This one does not',
                'userid'=>1,'deleted'=>0,'id'=>2));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>2,'title'=>null,'xhtml'=>'This comment is on the main page section',
                'userid'=>1,'deleted'=>0,'id'=>3));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>3,'title'=>null,'xhtml'=>'This one is on a different section',
                'userid'=>1,'deleted'=>0,'id'=>4));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>4,'title'=>null,'xhtml'=>'This one is on another page',
                'userid'=>1,'deleted'=>0,'id'=>5));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>5,'title'=>null,'xhtml'=>'This one is on another page but same section',
                'userid'=>1,'deleted'=>0,'id'=>6));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>2,'title'=>null,'xhtml'=>'Main 2',
                'userid'=>2,'deleted'=>0,'id'=>7));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>2,'title'=>null,'xhtml'=>'Main 3',
                'userid'=>null,'deleted'=>0,'id'=>8));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>2,'title'=>null,'xhtml'=>'Main 4',
                'userid'=>1,'deleted'=>0,'id'=>9));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>1,'title'=>null,'xhtml'=>'Frog 3',
                'userid'=>1,'deleted'=>0,'id'=>10));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>1,'title'=>null,'xhtml'=>'Frog 4',
                'userid'=>1,'deleted'=>0,'id'=>11));
        $this->assertEqual((array)array_shift($comments),
            array('sectionid'=>1,'title'=>null,'xhtml'=>'Frog 5',
                'userid'=>1,'deleted'=>0,'id'=>12));
                
        // Now delete Frog 3
        ouwiki_delete_comment(13,10,1);
        $comment=get_record('ouwiki_comments','id',10);
        unset($comment->timeposted);
        $this->assertEqual((array)$comment,
            array('sectionid'=>1,'title'=>null,'xhtml'=>'Frog 3',
                'userid'=>1,'deleted'=>1,'id'=>10));
        
        // Check query for all content...
        $comments=ouwiki_get_all_comments(13,'frog');
        $comment=$comments[1];
        unset($comment->timeposted);
        // Ensure comments have all expected details (just taking one example)
        $this->assertEqual((array)$comment,
            array('sectionid'=>1,'title'=>'My happy comment','xhtml'=>'This comment has a title',
                'userid'=>1,'deleted'=>0,'sectiontitle'=>'Frogs','section'=>'frog',
                'firstname'=>'user','lastname'=>'one','username'=>'u1','id'=>1));
        // Now check correct comments are included
        $this->assertEqual(self::get_id_array($comments),array(1,2,11,12));
        // Check it works to include deleted ones if asked
        $comments=ouwiki_get_all_comments(13,'frog',true);
        $this->assertEqual(self::get_id_array($comments),array(1,2,10,11,12));
        // Ask for comments from main section with everything else valid...
        $comments=ouwiki_get_all_comments(13,null,false,array('frog'=>0,'zombie'=>0));
        $this->assertEqual(self::get_id_array($comments),array(3,7,8,9));
        // ...and with nothing else valid
        $comments=ouwiki_get_all_comments(13,null,false,array());
        $this->assertEqual(self::get_id_array($comments),array(1,2,3,4,7,8,9,11,12));
        
        // Check query for page-display content
        $comments=ouwiki_get_recent_comments(13,array('frog'=>0,'zombie'=>0));
        $this->assertEqual($comments['']->count,4);
        $this->assertEqual(self::get_id_array($comments['']->comments),array(7,8,9));
        $this->assertEqual($comments['frog']->count,4);
        $this->assertEqual(self::get_id_array($comments['frog']->comments),array(2,11,12));
        $this->assertEqual($comments['zombie']->count,1);
        $this->assertEqual(self::get_id_array($comments['zombie']->comments),array(4));
    }
    
    function get_id_array($comments) {
        $results=array();
        foreach($comments as $comment) {
            $results[]=$comment->id;
        }
        return $results;
    }
    
}
?>
