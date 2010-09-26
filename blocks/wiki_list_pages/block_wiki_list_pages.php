<?php

/**
 * This file contains the wiki pages list class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_list_pages.php,v 1.19 2008/01/17 11:51:30 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

class block_wiki_list_pages extends block_base {

   var $alphabet = array();

    var $images = array();

    var $ordereds = array();

    ////Function called when a module instance is activated
    function init() {
      global $CFG, $WS;

      $this->title = get_string('block_list_pages', 'wiki').helpbutton ('list_pages', get_string('block_list_pages', 'wiki'), 'wiki', true, false, '', true);
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
            'square' => '<img src="'.$CFG->wwwroot.'/mod/wiki/images/square-'.$squaredir.'.png" alt="" />',  // rtl support (nadavkav patch)
            'syn' => '<img src="'.$CFG->wwwroot.'/mod/wiki/images/syn.gif" alt="" />'
            );

    }

    //applicable formats to the block, overrides block_base::applicable_formats()
    function applicable_formats() {
      return array('course-view-wiki' => true, 'mod-wiki' => true);
    }

    function get_content() {
    	global $CFG, $WS;
      $wiki = wiki_param ('dfwiki');
      $groupmember = wiki_param('groupmember');
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
      //$this->content->footer = '<br />'.helpbutton ('list_pages', get_string('block_list_pages', 'wiki'), 'wiki', true, false, '', true).get_string('block_list_pages', 'wiki');

    	$this->content->items[] = '<a href="http://www.google.com">google</a>';
    	$this->content->icons[] = '<img src="icon.gif" alt="">';

        //If we are out of a dfwiki activity or in a different
        //dfwiki format course and we want to create a block:
        if(empty($WS->dfwiki)) {
            $this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }

    	$ead = wiki_manager_get_instance();

    	$pages = $ead->get_wiki_page_names_of_wiki($wiki, $groupmember->groupid);

    	$this->orderpages($pages);

    	$syns = $ead->get_wiki_synonyms();

    	$this->orderpages($syns);


   		$list = '<table border="0" cellspacing="0" cellpadding="0"><tr><td class="nwikileftnow">
    			<ul class="wiki_listme">';

		if (!isset($WS->pagedata->pagename)){
			$WS->load_page_data();
		}

		if (count($pages)!=0){
    		foreach ($this->ordereds as $key => $ordered){

    			//chack if the letter is the curretn one.
    			if ($key == $this->get_index($WS->pagedata->pagename)){
    				$display = '';
    				$image_ico = $this->images['minus'];
    			}else{
    				$display = 'display:none';
    				$image_ico = $this->images['plus'];
    			}

    			$list.='<li class="wiki_listme">'.$image_ico.'<a href="#" class="wiki_folding">'.$key.'</a>
    					<ul class="wiki_listme" style="margin:auto auto auto 15px;'.$display.'">';

				// dfwiki-block || course-block
				$dir="";
				if($this->instance->pagetype=="mod-wiki-view"){
					$dir=$CFG->wwwroot.'/mod/wiki/view.php?id='.$WS->cm->id;
				}else{
					$dir=$CFG->wwwroot.'/course/view.php?id='.$WS->cm->course;
				}
    			foreach ($ordered as $link){
    				if (in_array($link,$syns)) {
    					$page_icon = $this->images['syn'];
						$page=wiki_get_real_pagename($link);

    				}else{
    					$page_icon = $this->images['square'];
						$page=$link;
    				}
    				$list.= '<li class="wiki_listme">'.$page_icon.'<a href="'.$dir.'&amp;page='.urlencode($page).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'" title="'.urlencode($link).'">'.format_text($this->trim_string($link,20),FORMAT_PLAIN).'</a>'.'</li>';
    			}

    			$list.= '</ul></li>';

    		}

    	}else{
    		$list.= '<li>'.get_string('nopages','wiki').'</li>';
    	}
    	$list.= '</ul></td></tr></table>';

    	$this->content->text = $list;

        $this->content->footer .= '<style>.wiki_listme { padding-right:5px; } .dir-rtl td.nwikileftnow  { text-align:right; } </style>';

    	return $this->content;
    }

    function orderpages($pages){
		if (!empty($pages)){
	    	foreach ($pages as $page){

	    		$index = $this->get_index($page);

	    		$this->ordereds[$index][] = $page;
	    	}

	    	ksort($this->ordereds);
		}
    }

    function get_index ($page){
    	$specials = array(
    			'0-9' => '1234567890',
    			'@' =>'!"�$%&/()=?�@#|�{}*[]�,\':;-+<>���.'
    		);

    	$char = $this->get_char($page);
    	$index = '';
    	//get index
    	foreach ($specials as $key => $special){
    		if (strspn($char,$special)!=0){
    			$index = $key;
    		}
    	}

    	if ($index==''){
    		//$index = strtoupper($char,get_string('thischarset'));
        mb_internal_encoding("UTF-8");
        $index = mb_strtoupper($char,get_string('thischarset'));
    	}
    	return $index;
    }

    //this function trims any given text and returns it with some dots at the end
    function trim_string($text, $limit) {
        mb_internal_encoding("UTF-8");
        if (mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit) . '...';
        }

        return $text;
    }

    //this function returns the first char in multibyte mode if required
    function get_char($page){
        mb_internal_encoding(get_string('UTF-8'));
        //return substr($page,0,1);
        return mb_substr($page,0,1);
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
