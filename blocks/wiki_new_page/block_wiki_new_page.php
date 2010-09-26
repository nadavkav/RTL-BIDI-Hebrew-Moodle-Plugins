<?php

/**
 * This file contains the wiki new page class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_new_page.php,v 1.7 2007/08/23 12:24:00 tusefomal Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */
  
class block_wiki_new_page extends block_base {

////Function called when a module instance is activated
    function init() {

      $this->title = get_string('block_new_page', 'wiki').helpbutton ('new_page', get_string('block_new_page', 'wiki'), 'wiki', true, false, '', true);
      $this->version = 2004081200;
    }

    //applicable formats to the block, overrides block_base::applicable_formats()
    function applicable_formats() {
		return array('mod-wiki' => true);
    }

    function get_content() {
    	global $WS;

    	if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        //$this->content->footer = '<br />'.helpbutton ('new_page', get_string('block_new_page', 'wiki'), 'wiki', true, false, '', true).get_string ('block_new_page','wiki');
        //$this->content->footer = '<hr />'.get_string('block_helpaboutblock', 'wiki').helpbutton ('new_page', get_string('block_new_page', 'wiki'), 'wiki', true, false, '', true);

        //If we are out of a dfwiki activity or in a different
        //dfwiki format course and we want to create a block:
        if(empty($WS->dfwiki)) {
            $this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }


    	//mount the form
    	/*$form = '<form method="post" action="view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'"><div>
    				<input type="hidden" name="dfsetup" value="4" /><br />
    				<input type="text" name="dfformname" /><br />
    				<input type="submit" name="dfformbut" value="'.get_string('add').'" />
    			</div></form>';*/
    	$form = '<form method="post" action="view.php?id='.$WS->cm->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'"><div>
    				<br />
    				<input type="text" name="page" /><br />
    				<input type="submit" name="dfformbut" value="'.get_string('add').'" />
    			</div></form>';


    	$this->content->text = $form;

    	return $this->content;
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
