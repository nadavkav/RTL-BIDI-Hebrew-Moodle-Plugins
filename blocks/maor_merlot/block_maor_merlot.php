<?php //$Id: block_maor_merlot.php,v 1.0.01.1 20qq/10/14 13:36:26 nadavkav@gmail.com Exp $

class block_maor_merlot extends block_base {

    function init() {
        $this->title = get_string('maor_merlot', 'block_maor_merlot');
        $this->version = 2011101401;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('newblock', 'block_maor_merlot'));
    }

    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;

        $searchmaor = '<div id="maor_wrapper" style="width:131px;height:100px;direction:rtl;text-align:right;border:1px solid black;background-color:#E5ECF9;padding:2px;margin:auto;">';
        $searchmaor .= '<a href="http://maor.iucc.ac.il" onclick="window.open(this.href, \'\'); return false;" title="MAOR">';
        $searchmaor .= '<img src="//maor.iucc.ac.il/images/logo_text_down.png" alt="MAOR" style="border:none;" /></a>';
        $searchmaor .= '<form name="MaorSearchForm" method="get" target="_blank" action="http://maor.iucc.ac.il/materials.php" style="margin:0;">';
        $searchmaor .= '<input name="keywords" id="keywords" type="text" size="10" value="חיפוש במאור" style="width:90px;height:17px;" onclick="if (this.value==\'חיפוש במאור\'){this.value=\'\';}" onfocus="if (this.value==\'חיפוש במאור\'){this.value=\'\';}" />';
        $searchmaor .= '<input name="image" type="image" value="חפש" id="maor_btn" src="//maor.iucc.ac.il/images/searchbutton.png" onclick="javascript:document.forms[\'MaorSearchForm\'].submit();" style="border:none;vertical-align:top;padding:1px 3px 0 0;" /></form>';
        $searchmaor .= '</div>';

        $this->content->text = $searchmaor;
        $this->content->footer = '';

        return $this->content;
    }

    /*
     * Hide the title bar when none set..
     */
    function hide_header(){
        return empty($this->config->title);
    }
}
?>
