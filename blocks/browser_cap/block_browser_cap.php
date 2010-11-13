<?php 
/**
 * This block allows a teacher to indicate which browser plug-ins 
 * students are likely to require in the course based on the content they 
 * are intending to include. The plug-ins they choose are stored against 
 * the instance of the block for the course the block has been added to.
 *
 * Students will see a tick or a cross against each one of the plug-ins 
 * selected indicating whether or not their browser seems to support the 
 * plug-in.
 */
class block_browser_cap extends block_base {

function init() {
    $this->title = get_string('browser_cap', 'block_browser_cap'); 
    $this->version = 2008060600;
}

function get_content() {
    global $CFG;
    if ($this->content !== NULL) {
        return $this->content;
    }
    $this->content = new stdClass;
    $imgPath = $CFG->wwwroot . '/blocks/browser_cap/';
    $this->content->text .= '<span style="font-size: 0.9em;">'.get_string('browser_support', 'block_browser_cap').'</span>' . "\n" . 
        '<noscript><p style="font-size: 0.9em;">'.get_string('browser_noscript', 'block_browser_cap').'</p></noscript>' .
        '<div style="line-height: 0.8em; font-size: 0.8em;">';
    $this->content->text .= '<script language="JavaScript" type="text/javascript">
        <!--
        var browserCapImgPath = "' . $imgPath . '";';
    if ($this->config->detect_adobe) {
        $this->content->text .= 'showResult("'.get_string('pdf','block_browser_cap').'", detectAcrobat());';
    } 
    if ($this->config->detect_cookies) {
        $this->content->text .= 'showResult("'.get_string('cookies','block_browser_cap').'", detectCookies());';
    } 
    // not including Director, Flash player is used typically to play shockwave files
    // created in Adobe Director
    //if ($this->config->detect_director) {
    //    $this->content->text .= 'showResult("Director", detectDirector());';
    //} 
    if ($this->config->detect_flash) {
        $this->content->text .= 'showResult("'.get_string('flash','block_browser_cap').'", detectFlash());';
    } 
    if ($this->config->detect_java) {
        $this->content->text .= 'showResult("'.get_string('java','block_browser_cap').'", detectJava());';
    } 
    if ($this->config->detect_javascript) {
        $this->content->text .= 'showResult("'.get_string('javascript','block_browser_cap').'", 1);'; // dont need to test
    } 
    if ($this->config->detect_quicktime) {
        $this->content->text .= 'showResult("'.get_string('quicktime','block_browser_cap').'", detectQuickTime());';
    } 
    if ($this->config->detect_real) {
        $this->content->text .= 'showResult("'.get_string('real','block_browser_cap').'", detectReal());';
    } 
    if ($this->config->detect_windowsmedia) {
        $this->content->text .= 'showResult("'.get_string('windowsmedia','block_browser_cap').'", detectWindowsMedia());';
    } 
    $this->content->text .= '
        //-->
        </script>';
        // could include a link to a popup window to do instantiation and version testing
        //<p>Perform a <a href="#" onclick="alert(\'does nothing.. yet\');">full test</a></p>';
        
    $this->content->footer = '</div>';
    
    return $this->content;
}

function instance_allow_config() {
    return true;
}


}


?>