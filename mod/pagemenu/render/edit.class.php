<?php
/**
 * Render the menu for editing
 *
 * @author Mark Nielsen
 * @version $Id: edit.class.php,v 1.1 2009/12/21 01:01:27 michaelpenne Exp $
 * @package pagemenu
 **/

require_once($CFG->dirroot.'/mod/pagemenu/render.class.php');
require_once($CFG->dirroot.'/mod/pagemenu/render/list.class.php');

class mod_pagemenu_render_edit extends mod_pagemenu_render {
    /**
     * Turn on editing
     *
     * @var boolean
     **/
    protected $editing = true;

    /**
     * Show whole menu for editing
     *
     * @var boolean
     **/
    protected $descend = true;

    /**
     * List renderer
     *
     * @var mod_pagemenu_render_list
     **/
    protected $renderer;

    /**
     * Constructor hook
     *
     * @return void
     **/
    protected function init() {
        $this->renderer = new mod_pagemenu_render_list($this->pagemenuid);
    }

    /**
     * Modify how the menu items
     * are glued together - each
     * one is a row in a table.
     *
     * @return string
     **/
    protected function menuitems_to_html($menuitems, $depth = 0) {
        global $CFG;

        $action = optional_param('action', '', PARAM_ALPHA);

        if ($action == 'move') {
            $moveid     = required_param('linkid', PARAM_INT);
            $alt        = s(get_string('movehere'));
            $movewidget = "<a title=\"$alt\" href=\"$CFG->wwwroot/mod/pagemenu/edit.php?a=$this->pagemenuid&amp;action=movehere&amp;linkid=$moveid&amp;sesskey=".sesskey().'&amp;after=%d">'.
                          "<img src=\"$CFG->pixpath/movehere.gif\" border=\"0\" alt=\"$alt\" /></a>";
            $move = true;
        } else {
            $move = false;
        }

        $table              = new stdClass;
        $table->id          = 'edit-table';
        $table->width       = '90%';
        $table->tablealign  = 'center';
        $table->cellpadding = '5px';
        $table->cellspacing = '0';
        $table->data        = array();

        if ($move) {
            $table->head  = array(get_string('movingcancel', 'pagemenu', "$CFG->wwwroot/mod/pagemenu/edit.php?a=$this->pagemenuid"));
            $table->wrap  = array('nowrap');
            $table->data[] = array(sprintf($movewidget, 0));

        } else {
            $table->head  = array(get_string('linktype', 'pagemenu'), get_string('actions', 'pagemenu'), get_string('rendered', 'pagemenu'));
            $table->align = array('left', 'center', '');
            $table->size  = array('*', '*', '100%');
            $table->wrap  = array('nowrap', 'nowrap', 'nowrap');
        }

        foreach ($this->links as $link) {
            if (array_key_exists($link->link->id, $menuitems)) {
                $html = $this->renderer->menuitems_to_html(array($menuitems[$link->link->id]));
            } else {
                $html = get_string('linkitemerror', 'pagemenu');
            }

            if ($move) {
                if ($moveid != $link->link->id) {
                    $table->data[] = array($html);
                    $table->data[] = array(sprintf($movewidget, $link->link->id));
                }
            } else {
                $widgets = array();
                foreach (array('move', 'edit', 'delete') as $widget) {
                    $alt = s(get_string($widget));

                    $widgets[] = "<a title=\"$alt\" href=\"$CFG->wwwroot/mod/pagemenu/edit.php?a=$this->pagemenuid&amp;action=$widget&amp;linkid={$link->link->id}&amp;sesskey=".sesskey().'">'.
                                 "<img src=\"$CFG->pixpath/t/$widget.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"$alt\" /></a>";
                }

                $table->data[] = array($link->get_name(), implode('&nbsp;', $widgets), $html);
            }
        }
        return print_table($table, true);
    }
}

?>