<?php
global $CFG;

require_once($CFG->libdir .'/simpletestlib/unit_tester.php');
require_once(dirname(__FILE__) .'/../searchlib.php');

class test_searchlib extends UnitTestCase {
    
    function test_split_words() {
        // Standard usage and caps
        $this->assertEqual(
            ousearch_document::split_words('Hello I AM a basic test'),
            array('hello','i','am','a','basic','test'));
        // Numbers
        $this->assertEqual(
            ousearch_document::split_words('13 2by2'),
            array('13','2by2'));
        // Ignored and accepted punctuation and whitespace
        $this->assertEqual(
            ousearch_document::split_words('  hello,testing!what\'s&up      there-by   '),
            array('hello','testing','what\'s','up','there','by'));
        // Unicode letters and nonletter
        $this->assertEqual(
            ousearch_document::split_words('cafÃ© ÃŸÃ¥Å™Ä‰Ä•Ä¼Å?Å†Ã¤â€»tonight'),
            array('cafÃ©','ÃŸÃ¥Å™Ä‰Ä•Ä¼Å?Å†Ã¤','tonight'));
        // Unicode caps
        $this->assertEqual(
            ousearch_document::split_words('Ä€Ä’ÄªÅŒÅª'),
            array('Ä?Ä“Ä«Å?Å«'));
            
        // Query mode (keeps " + -)
        $this->assertEqual(
            ousearch_document::split_words('"hello there" +frog -doughnut extra-special',true),
            array('"hello','there"','+frog','-doughnut','extra-special'));
            
        // Position mode: normal
        $this->assertEqual(
            ousearch_document::split_words('hello test',false,true),
            array(array('hello','test'),array(0,6,10)));
        // Position mode: whitespace
        $this->assertEqual(
            ousearch_document::split_words('    hello    test    ',false,true),
            array(array('hello','test'),array(4,13,21)));
        // Position mode: unicode
        $this->assertEqual(
            ousearch_document::split_words('hÄ•llo tÄ•st',false,true),
            array(array('hÄ•llo','tÄ•st'),array(0,7,12))); // Positions are in bytes
    }
    
    function test_construct_query() {
        // Simple query
        $this->assertEqual($this->display_terms(new ousearch_search('frogs')),
            '+frogs -');
        // Case, whitespace, punctuation
        $this->assertEqual($this->display_terms(new ousearch_search('  FRoGs!!   ')),
            '+frogs -');
        // Requirement (currently unused but)
        $this->assertEqual($this->display_terms(new ousearch_search('+frogs')),
            '+frogs:req -');
        // Multiple terms
        $this->assertEqual($this->display_terms(new ousearch_search('green frogs')),
            '+green,frogs -');
        // Negative terms
        $this->assertEqual($this->display_terms(new ousearch_search('frogs -green')),
            '+frogs -green');
        // Quotes
        $this->assertEqual($this->display_terms(new ousearch_search('"green frogs"')),
            '+green/frogs -');
        // Mixed quotes and other
        $this->assertEqual($this->display_terms(new ousearch_search('"green frogs" sing')),
            '+green/frogs,sing -');
        // Mixed quotes and quotes
        $this->assertEqual($this->display_terms(new ousearch_search('"green frogs" "sing off key"')),
            '+green/frogs,sing/off/key -');
        // Mixed quotes and negative quotes
        $this->assertEqual($this->display_terms(new ousearch_search('"green frogs" -"sing off key"')),
            '+green/frogs -sing/off/key:req');
        // Mixed other and negative quotes
        $this->assertEqual($this->display_terms(new ousearch_search('frogs -"sing off key"')),
            '+frogs -sing/off/key:req');
        // Req. quotes (currently unused)
        $this->assertEqual($this->display_terms(new ousearch_search('+"green frogs"')),
            '+green/frogs:req -');
        
        // Hyphens (argh)
        $this->assertEqual($this->display_terms(new ousearch_search('double-dutch')),
            '+double/dutch -');
        $this->assertEqual($this->display_terms(new ousearch_search('It\'s all double-dutch to me')),
            '+it\'s,all,double/dutch,to,me -');
        $this->assertEqual($this->display_terms(new ousearch_search('"What double-dutch"')),
            '+what/double/dutch -');
        $this->assertEqual($this->display_terms(new ousearch_search('"double-dutch what"')),
            '+double/dutch/what -');
        $this->assertEqual($this->display_terms(new ousearch_search('"so-called double-dutch"')),
            '+so/called/double/dutch -');
        $this->assertEqual($this->display_terms(new ousearch_search('so-called double-dutch')),
            '+so/called,double/dutch -');        
    }
    
    function display_terms($query) {
        $input=array($query->terms,$query->negativeterms);
        $output=array();
        foreach($input as $thing) {
            $value='';
            foreach($thing as $term) {
                if($value!=='') {
                    $value.=',';
                }
                $value.=implode('/',$term->words);
                if(!empty($term->required)) {
                    $value.=':req';
                }
            }
            $output[]=$value;
        }
        return '+'.$output[0].' -'.$output[1];
    }
}


?>
