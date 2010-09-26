<?php

/**
 * This file contains the wiki tags class for moodle blocks.
 *
 * @author DFWiki LABS
 * @author Gonzalo Serrano
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: block_wiki_tags.php,v 1.1 2008/05/26 18:37:04 gonzaloserrano Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Blocks
 */

class block_wiki_tags extends block_base 
{
    function init() {
        $this->title = get_string('block_wiki_tags', 'wiki');
        $this->version = 2008013106;
    }

    function applicable_formats() {
        return array('course-view-wiki' => true, 'mod-wiki' => true);
    }

    function specialization() {
    }

    function get_content() {

        global $CFG, $SITE, $COURSE, $USER;

        if (empty($CFG->usetags)) {
            $this->content->text = '';
            return $this->content;
        }

        if (empty($this->config->numberoftags)) {
            $this->config->numberoftags = 80;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';

        require_once($CFG->dirroot.'/mod/wiki/tags/tags.lib.php');
        //$this->content->text = wiki_tags_print_tag_cloud($this->config->numberoftags, true);
        $this->content->text = wiki_tags_print_tag_cloud();

        return $this->content;
    }

    function instance_config_print() {
/*
 *        global $CFG;
 *
 *    /// set up the numberoftags select field
 *        $numberoftags = array();
 *        for($i=1;$i<=200;$i++) $numberoftags[$i] = $i;
 *
 *        if (is_file($CFG->dirroot .'/blocks/'. $this->name() .'/config_instance.html')) {
 *            print_simple_box_start('center', '', '', 5, 'blockconfigglobal');
 *            include($CFG->dirroot .'/blocks/'. $this->name() .'/config_instance.html');
 *            print_simple_box_end();
 *        } else {
 *            notice(get_string('blockconfigbad'), str_replace('blockaction=', 'dummy=', qualified_me()));
 *        }
 */
    }
}

?>
