<?php

/**
 * This file contains the wiki synonymous class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_synonymous.php,v 1.15 2007/09/07 11:04:05 tusefomal Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

require_once($CFG->dirroot . '/mod/wiki/lib/wiki_manager.php');
require_once($CFG->dirroot . '/mod/wiki/lib/wiki_pageid.class.php');
require_once($CFG->dirroot . '/mod/wiki/lib/wiki_synonym.class.php');

class block_wiki_synonymous extends block_base {

    /**
     * Function called when a module instance is activated
     */
    function init() {
        $this->title = get_string('block_synonymous', 'wiki').helpbutton ('synonymous', get_string('block_synonymous', 'wiki'), 'wiki', true, false, '', true);
        $this->version = 2004081200;
    }

    /**
     * Applicable formats to the block, overrides block_base::applicable_formats()
     */
    function applicable_formats() {
        return array('course-view-wiki' => true, 'mod-wiki' => true);
    }

    function get_content() {
        global $CFG, $WS, $USER;

        if ($this->content !== NULL) {
            return $this->content;
        }

        // dfwiki-block || course-block
        if (isset($this->instance->pagetype)) {
            if ($this->instance->pagetype == 'mod-wiki-view') {
                $dir = $CFG->wwwroot . '/mod/wiki/view.php?id=' . $WS->cm->id . '&amp;name=dfwikipage';
            } else {
                $dir = $CFG->wwwroot . '/course/view.php?id=' . $WS->cm->course;
            }
        }

        $this->content = new stdClass;

        // If we are out of a dfwiki activity or in a different
        // dfwiki format course and we want to create a block:
        if (empty($WS->dfwiki)) {
            $this->content->footer = '';
            $this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }

        $this->content->items = array();
        $this->content->icons = array();
/*        $this->content->footer = '<br />' . helpbutton ('synonymous',
            $this->title, 'wiki', true, false, '', true) . $this->title;*/
/*        $this->content->footer = '<hr />'.get_string('block_helpaboutblock', 'wiki') .
                helpbutton ('synonymous', get_string('block_synonymous', 'wiki'), 'wiki', true, false, '', true);*/
        $list = '';

        $wikimanager = wiki_manager_get_instance();

        $pageid = new wiki_pageid();

        if (preg_match("/^discussion:/", $WS->page) or !$wikimanager->page_exists($pageid)) {
            $this->content->text = get_string('nopages','wiki');
            return $this->content;
        }

        $delsyn = optional_param('delsyn',NULL,PARAM_FILE);

        // delete synonymous
        if (isset($delsyn)) {
            $wikimanager->delete_synonym(new wiki_synonym($delsyn, $pageid));
        }

        //wiki_dfform_param($WS);

        //insert new synonymous
        $syn = optional_param('dfformsyn',NULL,PARAM_FILE);
        if (isset($syn)) {
            $name = optional_param('dfformname', NULL, PARAM_FILE);
            $pageid_syn = clone($pageid);
            $pageid_syn->name = $name;
            if ($wikimanager->page_exists($pageid_syn)) {
                $list .= '<span class="except">' . get_string('synexists','wiki')
                        . ' ' . $name . '</span><hr />';
            } else {
                $name = str_replace('\\', '_', str_replace('/', '_', $name));
                $synonym = new wiki_synonym($name, $pageid);
                if (!$wikimanager->insert_synonym($synonym)) {
                    $list .= '<span class="except">' . get_string('errorsaving') . '</span><hr />';
                }
                unset($syn);
            }

        }

        //print content
        $list.= '<table border="0">
                <tr>
                    <td><b>'.$this->trim_string($WS->page,20).'</b></td>
                </tr>';

        // select synonymous
        if ($syns = $wikimanager->get_synonyms($pageid)) {
            foreach ($syns as $syn){
                $list.= "<tr><td>$syn->name ";
                if ($syn->deletable) {
                    $list .= '<a href="' . $dir . '&amp;gid=' . $pageid->groupid . '&amp;uid='
                        . $pageid->ownerid . '&amp;page=' . urlencode($pageid->name) . '&amp;delsyn='
                        . urlencode($syn->name) . '"><img src="' . $CFG->wwwroot
                        . '/mod/wiki/images/delete.gif" alt="" /></a>';
                }
                $list .= '</td></tr>';
            }
        }

        $list .= '</table>';

        //Converts reserved chars for html to prevent chars misreading
         $pagetemp = stripslashes_safe($WS->page);

        if ($wikimanager->page_is_editable($pageid)) {
            $list.='<br /><form method="post" action="'.$dir.'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;page='.urlencode($pagetemp).'"><div>
                        <input type="text" name="dfformname" /><br />
                        <input type="submit" name="dfformsyn" value="'.get_string('add').'" />
                    </div></form>';
            }


        $this->content->text = $list;

        return $this->content;
    }

    /**
     * Trims the given text and adds dots at the end, if necessary.
     *
     * @param String $text
     * @param Integer $limir
     *
     * @return String
     */
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
