<?php
/**
 * Save template feature. Saves entire subwiki contents as an XML template.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */

require('basicpage.php');

if(class_exists('ouflags')) {
    require_once('../../local/mobile/ou_lib.php');
    global $OUMOBILESUPPORT;
    $OUMOBILESUPPORT = true;
    ou_set_is_mobile(ou_get_is_mobile_from_cookies());
}

define('OUWIKI_FORMAT_HTML','html');
define('OUWIKI_FORMAT_RTF','rtf');
define('OUWIKI_FORMAT_TEMPLATE','template');

$format=required_param('format',PARAM_ALPHA);
if($format!==OUWIKI_FORMAT_HTML && $format!==OUWIKI_FORMAT_RTF && $format!==OUWIKI_FORMAT_TEMPLATE) {
    error('Unexpected format');
} 

// Get basic wiki details for filename
$filename=$course->shortname.'.'.$ouwiki->name;
$filename=preg_replace('/[^A-Za-z0-9.-]/','_',$filename);

switch($format) {
    case OUWIKI_FORMAT_TEMPLATE:
        header('Content-Type: text/xml; encoding=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'.template.xml"');
        print '<wiki>';
        break;
    case OUWIKI_FORMAT_RTF:
        require_once('../../local/rtf.php');
        $html='<root><p>'.get_string('savedat','ouwiki',userdate(time())).'</p><hr />';
        break;
    case OUWIKI_FORMAT_HTML:
        // Do header
        if (class_exists('ouflags') && ou_get_is_mobile()){
            ou_mobile_configure_theme();
        }
        ouwiki_print_start($ouwiki,$cm,$course,$subwiki,get_string('entirewiki','ouwiki'),$context,null,false,true);
        print '<div class="ouwiki_content">';
        break;
}

// Get list of all pages
$first=true;
$index=ouwiki_get_subwiki_index($subwiki->id);
foreach($index as $pageinfo) {
    // Get page details
    $pageversion=ouwiki_get_current_page($subwiki,$pageinfo->title);
    // If the page hasn't really been created yet, skip it 
    if(is_null($pageversion->xhtml)) {
        continue;        
    }
    $visibletitle=is_null($pageversion->title) ? get_string('startpage','ouwiki') : $pageversion->title;
    
    switch($format) {
        case OUWIKI_FORMAT_TEMPLATE:
            print '<page>';
            if(!is_null($pageversion->title)) {
                print '<title>'.htmlspecialchars($pageversion->title).'</title>';
            }
            print '<xhtml>'.htmlspecialchars($pageversion->xhtml).'</xhtml>';
            print '</page>';
            break;
        case OUWIKI_FORMAT_RTF:
            $html.='<h1>'.htmlspecialchars($visibletitle).'</h1>';
            $html.=trim($pageversion->xhtml);
            $html.='<br /><br /><hr />';
            break;
        case OUWIKI_FORMAT_HTML:
            print '<div class="ouw_entry"><a name="'.$pageversion->pageid.'"></a><h1 class="ouw_entry_heading"><a href="view.php?'.
                ouwiki_display_wiki_parameters($pageversion->title,$subwiki,$cm).
                '">'.htmlspecialchars($visibletitle).'</a></h1>';
            print ouwiki_convert_content($pageversion->xhtml,$subwiki,$cm,$index);            
            print '</div>';
            break;
    }
    
    if($first) {
        $first=false;
    }    
}

switch($format) {
    case OUWIKI_FORMAT_TEMPLATE:
        print '</wiki>';
        break;
        
    case OUWIKI_FORMAT_RTF:
        $html.='</root>';
        rtf_from_html($filename.'.rtf',$html);
        break;
        
    case OUWIKI_FORMAT_HTML:
        print '</div>';
        ouwiki_print_footer($course,$cm,$subwiki);
        break;
}
?>
