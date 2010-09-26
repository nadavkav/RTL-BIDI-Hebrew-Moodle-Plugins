<?php // $Id: filter.php,v 1.38.2.5 2008/07/07 17:38:42 skodak Exp $
//////////////////////////////////////////////////////////////
//  DocView plugin filter
//
//  This filter will replace any link to an Office Document - word proccessing or presentation
//  with an iframe in which the Office document preview will appear.
//
//  inspired by Firefox addon "Open It Online" by Denis Remondini.
//
//  To activate this filter, unzip the filter's folder and copy the folder
//  into the filters folder, in moodle's main directory tree (moodle/filters/docview)
//
//  to use the filter:
//  select some text in the embedded HTML editor and link it to a .doc / .odt / .odp / .ppt files.
//  goto admin/modules/filters/docview and enable this filter.
//
//  todo: 
//	[] pass iframe size parameters to each office document view
//	[] coding: better regexp and better select...case !
//
//	please, feedback me : nadavkav ET netvision DooT net DooT il
//////////////////////////////////////////////////////////////

/// This is the filtering function itself.  It accepts the
/// courseid and the text to be filtered (in HTML form).

require_once($CFG->libdir.'/filelib.php');


function docview_filter($courseid, $text) {
    global $CFG;

    if (!is_string($text)) {
        // non string data can not be filtered anyway
        return $text;
    }
    $newtext = $text; // fullclone is slow and not needed here
	
    if ($CFG->filter_docview_plugin_enable) {
        $search = '/<a.*?href="([^<]+\.doc)"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'docview_plugin_filter_callback', $newtext);
    }
    if ($CFG->filter_docview_plugin_enable) {
        $search = '/<a.*?href="([^<]+\.odt)"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'docview_plugin_filter_callback', $newtext);
    }

    if ($CFG->filter_docview_plugin_enable) {
        $search = '/<a.*?href="([^<]+\.ppt)"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'pptview_plugin_filter_callback', $newtext);
    }
    if ($CFG->filter_docview_plugin_enable) {
        $search = '/<a.*?href="([^<]+\.odp)"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'pptview_plugin_filter_callback', $newtext);
    }

    if (is_null($newtext) or $newtext === $text) {
        // error or not filtered
        return $text;
    }
    
     return $newtext;
}

///===========================
/// callback filter functions


function docview_plugin_filter_callback($link) {
    global $CFG;

    static $count = 0;
    $count++;
    $id = 'filter_docview_'.time().$count; //we need something unique because it might be stored in text cache

    $url = addslashes_js($link[1]);

    //http://viewer.zoho.com/api/view.do?apikey=c58ca12c4db1223738bbaf60349edad8&cache=false&url=
    //http://www.zohowriter.com/publicimport.im?url=
    //http://docs.google.com/?action=updoc&formsubmitted=true&client=navclient-ff&uploadURL=
    //http://viewer.thinkfree.com/view.jsp?app=WRITE_VIEWER&width=100%&height=100%&autostart=true&open=
    //$vieweroptions = array(0 => 'Zoho Viewer', 1 => 'Zoho Writer' , 2=> 'Google Docs', 3=> 'ThinkFree Viewer');

    $pluginviewer = ' http://viewer.zoho.com/api/view.do?apikey=c58ca12c4db1223738bbaf60349edad8&cache=false&url=';
    //echo 'what web2 service = '.$CFG->filter_docview_plugin_serviceviewer;
    if ($CFG->filter_docview_plugin_document == 0)
    {
      $pluginviewer = 'http://viewer.zoho.com/api/view.do?apikey=c58ca12c4db1223738bbaf60349edad8&cache=false&url=';	
    }
    if ($CFG->filter_docview_plugin_document == 1)
    {
      $pluginviewer = 'http://www.zohowriter.com/publicimport.im?url=';
	
    }
    if ($CFG->filter_docview_plugin_document == 2)
    {
      $pluginviewer = 'http://docs.google.com/?action=updoc&formsubmitted=true&client=navclient-ff&uploadURL=';
	
    }
    if ($CFG->filter_docview_plugin_document == 3)
    {
      $pluginviewer = 'http://viewer.thinkfree.com/view.jsp?app=WRITE_VIEWER&width=100%&height=100%&autostart=true&open=';	
    }
    
    return $link[0].'<hr/><iframe id='.$id.' src="'.$pluginviewer.$url.'" width="'.$CFG->filter_docview_plugin_width.'" height="'.$CFG->filter_docview_plugin_height.'"></iframe><hr/>';

}

function pptview_plugin_filter_callback($link) {
    global $CFG;

    static $count = 0;
    $count++;
    $id = 'filter_docview_'.time().$count; //we need something unique because it might be stored in text cache

    $url = addslashes_js($link[1]);

    //http://viewer.zoho.com/api/view.do?apikey=c58ca12c4db1223738bbaf60349edad8&cache=false&url=
    //http://www.zohowriter.com/publicimport.im?url=
    //http://docs.google.com/?action=updoc&formsubmitted=true&client=navclient-ff&uploadURL=
    //http://viewer.thinkfree.com/view.jsp?app=WRITE_VIEWER&width=100%&height=100%&autostart=true&open=
    //$vieweroptions = array(0 => 'Zoho Viewer', 1 => 'Zoho Writer' , 2=> 'Google Docs', 3=> 'ThinkFree Viewer');
    
    $pluginviewer = ' http://viewer.zoho.com/api/view.do?apikey=c58ca12c4db1223738bbaf60349edad8&cache=false&url=';
    //echo 'what web2 service = '.$CFG->filter_docview_plugin_serviceviewer;
    if ($CFG->filter_docview_plugin_presentation == 0)
    {
      $pluginviewer = 'http://viewer.zoho.com/api/view.do?apikey=c58ca12c4db1223738bbaf60349edad8&cache=false&url=';

    }
    if ($CFG->filter_docview_plugin_presentation == 1)
    {
      $pluginviewer = 'http://www.zohowriter.com/publicimport.im?url=';

    }
    if ($CFG->filter_docview_plugin_presentation == 2)
    {
      $pluginviewer = 'http://docs.google.com/?action=updoc&formsubmitted=true&client=navclient-ff&uploadURL=';

    }
    if ($CFG->filter_docview_plugin_presentation == 3)
    {
      $pluginviewer = 'http://viewer.thinkfree.com/view.jsp?app=WRITE_VIEWER&width=100%&height=100%&autostart=true&open=';
    }
    
    return $link[0].'<hr/><iframe id='.$id.' src="'.$pluginviewer.$url.'" width="'.$CFG->filter_docview_plugin_width.'" height="'.$CFG->filter_docview_plugin_height.'"></iframe><hr/>';

}

?>
