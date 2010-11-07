<?php
//special lines marks

/**
 * HTML Export
 * this file contains HTML sintax parser needed to export to
 * a HTML file.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: htmleditor.php,v 1.2 2007/06/15 11:43:18 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package HTML_export
 */
 
 
global $WS;
$WS->parser_format = array();

$WS->parser_format['pre-parser'] = array();
$WS->parser_format['pre-parser']['moodle-format']->func = 'wiki_preformat_htmleditor_bis';

$WS->parser_format['no-parse'] = array();
$WS->parser_format['no-parse']['nowiki']->marks = array ('<nowiki>','</nowiki>');

$WS->parser_format['start-end'] = array ();
$WS->parser_format['start-end']['internal links']->marks = array ("[[","]]");
$WS->parser_format['start-end']['internal links']->func = 'wiki_parser_default_internal_link_bis';
$WS->parser_format['start-end']['external links']->marks = array ("[","]");
$WS->parser_format['start-end']['external links']->func = 'wiki_parser_default_external_link_bis';

$WS->parser_format['post-line'] = array();

$WS->parser_format['post-parser'] = array();
$WS->parser_format['post-parser']['moodle']->func = 'wiki_moodle_format_text_bis';


//EXTRA FUNCTION
function wiki_preformat_htmleditor_bis (&$text){
    $text2 = str_ireplace ('<p>', "\r\n", $text);
    $text2 = str_ireplace ('</p>', '', $text2);
    $text2 = str_ireplace ('<p />', '', $text2);
    $text2 = str_ireplace ('<br />', "\r\n", $text2);
    $text2 = str_ireplace ('<br />', "\r\n", $text2);
    return $text2;
}

?>
