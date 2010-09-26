<?php

/**
 * This file contains the wiki navigator class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_navigator.php,v 1.12 2007/07/19 17:10:37 tusefomal Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

class block_wiki_navigator extends block_base {

    var $pagesel = array();

    ////Function called when a module instance is activated
    function init() {
        $this->title = get_string('block_navigator', 'wiki').helpbutton ('navigator', get_string('block_navigator', 'wiki'), 'wiki', true, false, '', true);;
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

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        //$this->content->footer = '<br />'.helpbutton ('navigator', get_string('block_navigator', 'wiki'), 'wiki', true, false, '', true).get_string('block_navigator', 'wiki');
//         $this->content->footer = '<hr />'.get_string('block_helpaboutblock', 'wiki') .
//                 helpbutton ('navigator', get_string('block_navigator', 'wiki'), 'wiki', true, false, '', true);


		// dfwiki-block || course-block
		$dir="";
		if(isset($this->instance->pagetype)){
			if($this->instance->pagetype=="mod-wiki-view"){
			$dir=$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id;
			}else{
				$dir=$CFG->wwwroot.'/course/view.php?id='.$WS->cm->course;
			}
		}
        //If we are out of a dfwiki activity or in a different
        //dfwiki format course and we want to create a block:
        if(empty($WS->dfwiki)) {
            $this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }

    	//starts ead tools
    	$ead = wiki_manager_get_instance();

    	$text = '<b><span class="nwikiunderline">'.get_string('camefrom','wiki').'</span></b>';
        $camefroms = $ead->get_wiki_page_camefrom(addslashes($WS->pagedata->pagename));
    	$text.= '<table border="0" cellpadding="0" cellspacing="0">';

    	if($camefroms==false){$text.='<tr><td></td></tr>';}

    	foreach ($camefroms as $camefrom){
    		$text.= '<tr><td class="nwikileftnow"><a href="'.$dir.'&amp;page='.urlencode($camefrom->pagename).'">'.format_text($this->trim_string($camefrom->pagename,36),FORMAT_HTML).'</a></td></tr>';
    	}
    	$text.='</table>';

    	$text.= '<hr /><b><span class="nwikiunderline">'.get_string('goesto','wiki').'</span></b>';

    	$goestos = $ead->get_wiki_page_goesto($WS->pagedata->pagename);

    	$text.= '<table border="0" cellpadding="0" cellspacing="0">';

        if($goestos==false){$text.='<tr><td></td></tr>';}

		// rtl / ltr CSS alignment support (nadavkav)
		if ( right_to_left() ) { $nwikialignment = 'nwikirightnow';} else { $nwikialignment = 'nwikileftnow';}

    	foreach ($goestos as $goesto){
    		if (wiki_page_exists($WS,$goesto)){
    			$text.= '<tr><td class="'.$nwikialignment.'"><a href="'.$dir.'&amp;page='.urlencode($goesto).'">'.format_text($this->trim_string($goesto,37),FORMAT_HTML).'</a></td></tr>';
    		}else{
    			$text.= '<tr><td class="'.$nwikialignment.'"><span class="nwikiwanted"><a href="'.$dir.'&amp;page='.urlencode($goesto).'">'.format_text($this->trim_string($goesto,37),FORMAT_HTML).'</a></span></td></tr>';
    		}
    	}
    	$text.='</table>';

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
