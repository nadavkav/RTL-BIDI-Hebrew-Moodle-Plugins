<?php

/**
 * This file contains the wiki ranking class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_ranking.php,v 1.11 2008/02/29 13:45:56 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

require_once($CFG->dirroot . '/mod/wiki/lib/wiki_manager.php');

class block_wiki_ranking extends block_base {

    function init() {
        global $CFG;

        $this->title = get_string('block_ranking', 'wiki').helpbutton('ranking', $this->title, 'wiki', true, false, '', true);
        $this->version = 2004081200;
    }

    function applicable_formats() {
        return array('course-view-wiki' => true, 'mod-wiki' => true);
    }

    function get_content() {
        global $CFG, $WS, $USER;

        if (empty($WS->dfwiki)) {
            $this->content->text = get_string('block_warning','wiki');
            return $this->content;
        }

//         $this->content->footer = '<br />'
//             . helpbutton('ranking', $this->title, 'wiki', true, false, '', true)
//             . $this->title;

        // dfwiki-block || course-block
        if ($this->instance->pagetype == 'mod-wiki-view') {
            $dir = $CFG->wwwroot . '/mod/wiki/view.php?id=' . $WS->cm->id;
        } else {
            $dir = $CFG->wwwroot . '/course/view.php?id=' . $WS->cm->course;
        }

        if (!empty($WS->dfwiki) && $WS->dfwiki->votemode == 0) {
            $this->content->text = get_string('vote_warning','wiki');
            return $this->content;
        }
        
        $vote = optional_param('Vote', NULL, PARAM_ALPHA);

        $wikimanager = wiki_manager_get_instance();

        if ($WS->dfwiki->votemode == 1 and $vote == 'Vote') {
            $wikimanager->vote_page($WS->dfwiki->id, $WS->page,
                $WS->pagedata->version, $USER->username);
        }

        $ranking = $wikimanager->get_vote_ranking($WS->dfwiki->id);
        
        $this->content = new stdClass;

        if (!$ranking) {
            $this->content->text = get_string('nopages','wiki');
            $this->content->footer = '';
            return $this->content;
        }

        if (right_to_left()) { // rtl support for table cell alignment (nadavkav patch)
          $alignmentleft = 'right';
          $alignmentright = 'left';
        } else {
          $alignmentleft = 'left';
          $alignmentright = 'right';
        }

        $text = '<table border="0" width="100" cellpadding="0" cellspacing="0">'
            . '<tr><th>' . get_string('page') . '</th><th>'
            . get_string('votes','wiki') . '</th></tr>';

        $n_rows = 0;
        foreach ($ranking as $row) {
            $text .= '<tr><td align="'.$alignmentleft.'">'
                . '<a href="' . $dir . '&amp;page=' . urlencode($row->pagename). '">'
                . $this->trim_string($row->pagename, 20) . '</a></td>'
                . '<td align="center">'. $row->votes . '</td></tr>';

            // Show only the first 5 rows.
            if (++$n_rows == 5) {
                break;
            }
        }

        $text .= '</table>';
        $this->content->text = $text;
        $this->content->footer = '&nbsp;';

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