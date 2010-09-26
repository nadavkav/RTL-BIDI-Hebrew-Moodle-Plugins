<?php

/**
 * This file contains the wiki new page class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_orphaned.php,v 1.8 2007/07/19 13:00:43 tusefomal Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

class block_wiki_orphaned extends block_base {

   var $pagesel = array();

    ////Function called when a module instance is activated
    function init() {

        $this->title = get_string('block_orphaned', 'wiki').helpbutton ('orphaned', get_string('block_orphaned', 'wiki'), 'wiki', true, false, '', true);;
    	$this->version = 2004081200;
    }

    //applicable formats to the block, overrides block_base::applicable_formats()
    function applicable_formats() {
		return array('course-view-wiki' => true, 'mod-wiki' => true);
    }

    function get_content() {
    	global $CFG, $WS;

    	if($this->content !== NULL) {
            return $this->content;
        }

        //If we are out of a dfwiki activity or in a different
        //dfwiki format course and we want to create a block:
        if(empty($WS->dfwiki)) {
            $this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        //$this->content->footer = '<br />'.helpbutton ('orphaned', get_string('block_orphaned', 'wiki'), 'wiki', true, false, '', true).get_string('block_orphaned', 'wiki');
//         $this->content->footer = '<hr />'.get_string('block_helpaboutblock', 'wiki') .
//                 helpbutton ('orphaned', get_string('block_orphaned', 'wiki'), 'wiki', true, false, '', true);


    	$ead = wiki_manager_get_instance();
    	$pages = $ead->get_wiki_orphaned_pages();

		// dfwiki-block || course-block
		$dir="";
		if($this->instance->pagetype=="mod-wiki-view"){
			$dir=$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id;
		}else{
			$dir=$CFG->wwwroot.'/course/view.php?id='.$WS->cm->course;
		}


    	if (count($pages)!=0){
    		$text = '<table border="0" cellpadding="0" cellspacing="0">';
    		foreach ($pages as $page){
    			$text.= '<tr>
    				<td nowrap="nowrap">
    					<a href="'.$dir.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;page='.urlencode($page).'" title="'.urlencode($page).'">'.format_text($this->trim_string($page,25),FORMAT_PLAIN).'</a>
    				</td>
    				</tr>';
    		}
    		$text.='</table>';
    	} else {
    		$text = get_string('nopages','wiki');
    	}

    	$this->content->text = $text;

    	return $this->content;
    }

    //this function trims any given text and returns it with some dots at the end
    function trim_string($text, $limit) {
	mb_internal_encoding("UTF-8");
        if (mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit) . '...';
        }

        return $text;
    }

    /**
     * This function is called on your subclass right after an instance is loaded
     * Use this function to act on instance data just after it's loaded and before anything else is done
     * For instance: if your block will have different title's depending on location (site, course, blog, etc)
     */
    function specialization() {
        // Just to make sure that this method exists.
    }
}

?>
