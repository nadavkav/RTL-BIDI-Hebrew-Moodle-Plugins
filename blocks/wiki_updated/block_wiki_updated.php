<?php

/**
 * This file contains the wiki updated class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_updated.php,v 1.11 2007/07/19 14:09:50 davcastro Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

class block_wiki_updated extends block_base {

	var $pagesel = array();

	////Function called when a module instance is activated
    function init() {

        $this->title = get_string('block_updated', 'wiki').helpbutton ('updated', get_string('block_updated', 'wiki'), 'wiki', true, false, '', true);
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
        //$this->content->footer = '<br />'.helpbutton ('updated', get_string('block_updated', 'wiki'), 'wiki', true, false, '', true).get_string('block_updated', 'wiki');

		$ead = wiki_manager_get_instance();
		$pages = $ead->get_wiki_most_uptodate_pages(10);

		// dfwiki-block || course-block
		$dir="";
		if($this->instance->pagetype=="mod-wiki-view"){
			$dir=$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id;
		}else{
			$dir=$CFG->wwwroot.'/course/view.php?id='.$WS->cm->course;
		}

        // rtl / ltr CSS alignment support (nadavkav)
        if ( right_to_left() ) { $nwikialignment = 'nwikirightnow';} else { $nwikialignment = 'nwikileftnow';}

		if (count($pages)!=0){
			$text = '<table border="0" cellpadding="0" cellspacing="0">';
			$i = 1;
			foreach ($pages as $page){
				$pageinfo = wiki_page_last_version ($page);

				$brs = (strlen($page)>12)? '<br />&nbsp;&nbsp;&nbsp;' : '';

				$text.= '<tr>
					<td class="'.$nwikialignment.'">
						'.$i.'- <a href="'.$dir.'&amp;page='.urlencode($page).'" title="'.$page.'">'.$this->trim_string($page,40).'</a>'.$brs.'
						&nbsp;-&nbsp;<small>('.strftime('%d %b %y',$pageinfo->lastmodified).')</small>
						'.wiki_get_user_info($pageinfo->author,40,false).'
					</td>
					</tr>';
					$i++;
			}
			$text.='</table>';
		} else {
			$text = get_string('nopages','wiki');
		}

		$this->content->text = $text;

		return $this->content;
	}

    //this function trims any given text and returns it with some dots at the end
    function trim_string($text,$limit){

        if(strlen($text)>$limit){
            $text = substr($text,0,$limit).'...';
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
