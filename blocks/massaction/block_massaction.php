<?php

// RT #48813 Ray: Change the default block layout, added a quick_links block

require_once $CFG->dirroot."/blocks/massaction/lib.php";

class block_massaction extends block_base {

    function init() {
        global $CFG;
       $link = "<a href='".$CFG->wwwroot."/help.php?file=massaction.html' target='popup' title='".get_string('help_title', 'block_massaction')."'>";
        $link .= "<img src='".$CFG->pixpath."/help.gif'/></a>";
        $this->title = get_string('blocktitle', 'block_massaction', $link);
        $this->version = 2009102900;
    }

    protected $add_load_event_declared = false;

    function addLoadEvent($script, $return = false) {

        global $CFG;
        $html = "";
        if (!$this->add_load_event_declared) {
            $html .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/massaction/massaction.js"></script>';
        }
        $html .= '<script type="text/javascript">
//<![CDATA[';
            if (!$this->add_load_event_declared) {
                $html .= '
function addLoadEvent(func)
{
    if (window.addEventListener)
        window.addEventListener("load", func, false);
    else if (window.attachEvent)
        window.attachEvent("onload", func);
    else {
        if (typeof window.onload != "function")
            window.onload = func;
        else {
            var prev = window.onload;
            window.onload = function ()
            {
                prev();
                func();
            };
        }
    }
}';
            $this->add_load_event_declared = true;
        }
        $html .= '
addLoadEvent(function () { '.$script.' });
//]]>
</script>
';

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }
    
    function get_content() {
        global $CFG, $USER, $COURSE;

        $this->content = new stdClass;
        $this->content->footer="";
        if (isediting($COURSE)) {
            if ($COURSE->format == 'weeks' || $COURSE->format == 'topics') {
                $this->add_load_event_declared = false;
                $this->content->text   = $this->addLoadEvent('massaction_addcheckboxes()', true)
                                       . $this->addLoadEvent('disable_js_warning()', true)
                                       . generate_form($this->instance->id);
            } else {
                $this->content->text = get_string('unsupported', 'block_massaction', $COURSE->format);
            }
        } else {
            $this->content = '';
        }

        return $this->content;
    }
	
    function has_config() {
        return false;
    }

    function get_version() {
        return $this->version;
    }

    function instance_allow_multiple() {
        return false;
    }
}
?>

