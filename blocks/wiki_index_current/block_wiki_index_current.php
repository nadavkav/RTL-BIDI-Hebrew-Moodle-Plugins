<?php
/**
 * This file contains the wiki index from current class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_index_current.php,v 1.17 2008/01/15 12:40:19 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

class block_wiki_index_current extends block_base {

	var $pagesel = array();

	var $images = array();

	var $levelsel = 0;

	////Function called when a module instance is activated
    function init() {
      global $CFG, $WS;

      $this->title = get_string('block_index_current', 'wiki').helpbutton ('index_current', get_string('block_index_current', 'wiki'), 'wiki', true, false, '', true);
      $this->version = 2004081200;

		//initiates images array
		if (isset($WS->dfcourse)){
	    	$imgclass = 'wiki_folding_co';
	    }
	    else{
	        $imgclass = 'wiki_folding';
	    }
    if (right_to_left()) { $squaredir = 'left'; } else { $squaredir = 'right'; } // rtl support (nadavkav patch)
    $this->images = array(
          'plus' => '<img src="'.$CFG->wwwroot.'/mod/wiki/images/plus.gif" class="'.$imgclass.'" alt="" />',
          'minus' => '<img src="'.$CFG->wwwroot.'/mod/wiki/images/minus.gif" class="'.$imgclass.'" alt="" />',
          'square' => '<img src="'.$CFG->wwwroot.'/mod/wiki/images/square-'.$squaredir.'.png" alt="" />'// rtl support (nadavkav patch)
          );
    }

    //applicable formats to the block, overrides block_base::applicable_formats()
    function applicable_formats() {
		return array('course-view-wiki' => true, 'mod-wiki' => true);
    }

	function get_content() {
		global $WS, $CFG;

		if($this->content !== NULL) {
            return $this->content;
        }

		//Scritp WIKI_TREE
		$prop = null;
		$prop->type = 'text/javascript';
		if (isset($WS->dfcourse)){
	    	$prop->src = '../mod/wiki/editor/wiki_tree.js';
	    }
	    else{
	        $prop->src = 'editor/wiki_tree.js';
	    }
		wiki_script('', $prop);

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        //$this->content->footer = '<br />'.helpbutton ('index_current', get_string('block_index_current', 'wiki'), 'wiki', true, false, '', true).get_string('block_index_current', 'wiki');
        ;

        //If we are out of a dfwiki activity or in a different
        //dfwiki format course and we want to create a block:
        if(empty($WS->dfwiki)) {
            $this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }

		$list = '<table border="0" cellspacing="0" cellpadding="0"><tr><td class="nwikileftnow">
			<ul class="wiki_listme">';

		$ead = wiki_manager_get_instance();
		$this->levelsel = 2;
		//call recursive function
		$list.= $this->built_index($WS->pagedata->pagename,0);

		$list.= '</ul></td></tr></table>';

		$this->content->text = $list;

        $this->content->footer .= '<style>.wiki_listme { padding-right:5px; } .dir-rtl td.nwikileftnow  { text-align:right; } </style>';

		return $this->content;
	}

	//this function return the index string
	function built_index($page,$level){
		global $CFG,$WS;

		//printed links array
		$printed_links = array();

		// dfwiki-block || course-block
		$dir="";
		if($this->instance->pagetype=="mod-wiki-view"){
			$dir=$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id;
		}else{
			$dir=$CFG->wwwroot.'/course/view.php?id='.$WS->cm->course;
		}

		//search in vector
		if (!in_array($page,$this->pagesel)){

			//put in vector
			$this->pagesel[] = $page;

			//get last version
			if (!$pageinfo = wiki_page_last_version ($page)){
				//empty page
				//put in result
				return '<li class="wiki_listme">'.$this->images['square'].format_text($this->trim_string($page,20),FORMAT_PLAIN).'<a href="'.$dir.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;page='.urlencode($page).'">?</a></li>';
			} else {
				//get links
				$links = wiki_internal_link_to_array($pageinfo->refs);
				$links = wiki_filter_section_links($links);

				//put in result
				if (count($links)==0){
					$res = '<li class="wiki_listme">'.$this->images['square'].'<a href="'.$dir.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;page='.urlencode($page).'">'.format_text($this->trim_string($page,20),FORMAT_PLAIN).'</a>'.'</li>';
				}else{

					//determine if this level is opened or close
					if ($level<=$this->levelsel) {
						$display = '';
						$image_ico = $this->images['minus'];
					}else{
						$display = 'display:none';
						$image_ico = $this->images['plus'];
					}

					$res = '<li class="wiki_listme">'.$image_ico.'<a href="'.$dir.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;page='.urlencode($page).'">'.format_text($this->trim_string($page,20),FORMAT_PLAIN).'</a>
						<ul class="wiki_listme" style="margin:auto auto auto 15px;'.$display.'">';


					//foreach link do recursive
					foreach ($links as $link){
						//get real page name
						$link = wiki_get_real_pagename ($link);
						//search in printed_links
						if (!in_array($link,$printed_links)){
							//put in printed links
							$printed_links[] = $link;
							//mount
							$res.= $this->built_index ($link,$level+1);
						}
					}

					$res.= '</ul></li>';
				}
				//return result
				return $res;
			}
		} else {
			//page is already printed
			return '<li class="wiki_listme">'.$this->images['square'].'<a href="'.$dir.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;page='.urlencode($page).'"><i>'.format_text($this->trim_string($page,20),FORMAT_PLAIN).'</i></a></li>';
		}
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
