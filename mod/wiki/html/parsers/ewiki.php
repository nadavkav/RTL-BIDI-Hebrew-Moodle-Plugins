<?php


/**
 * HTML Export
 * this file contains ewiki sintax parser needed to export to
 * a HTML file.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: ewiki.php,v 1.2 2007/06/15 11:43:18 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package HTML_export
 */
 

global $WS;
$WS->parser_format = array();

$WS->parser_format['pre-parser'] = array();

$WS->parser_format['no-parse'] = array();
$WS->parser_format['no-parse']['nowiki']->marks = array ('<nowiki>','</nowiki>');

$WS->parser_format['line-definition']->marks = "\r\n";

$WS->parser_format['whole-line'] = array ();
$WS->parser_format['whole-line']['hr']->marks = '-';
$WS->parser_format['whole-line']['hr']->subs = "<hr noshade=\"noshade\" />\n";
$WS->parser_format['whole-line']['hr']->multisubs = false;

$WS->parser_format['line-start'] = array ();
$WS->parser_format['line-start']['h1']->marks = '!!!';
$WS->parser_format['line-start']['h1']->subs = array ('<h1>','</h1>');
$WS->parser_format['line-start']['h1']->marks = '!!';
$WS->parser_format['line-start']['h1']->subs = array ('<h2>','</h2>');
$WS->parser_format['line-start']['h1']->marks = '!';
$WS->parser_format['line-start']['h1']->subs = array ('<h3>','</h3>');


$WS->parser_format['start-end'] = array ();
$WS->parser_format['start-end']['b1']->marks = array ("**","**");
$WS->parser_format['start-end']['b1']->subs = array ('<b>','</b>');
$WS->parser_format['start-end']['b2']->marks = array ("__","__");
$WS->parser_format['start-end']['b2']->subs = array ('<b>','</b>');
$WS->parser_format['start-end']['b3']->marks = array ("'''","'''");
$WS->parser_format['start-end']['b3']->subs = array ('<b>','</b>');
$WS->parser_format['start-end']['i']->marks = array ("''","''");
$WS->parser_format['start-end']['i']->subs = array ('<i>','</i>');
$WS->parser_format['start-end']['big']->marks = array ("��","��");
$WS->parser_format['start-end']['big']->subs = array ('<BIG>','</BIG>');
$WS->parser_format['start-end']['small']->marks = array ("##","##");
$WS->parser_format['start-end']['small']->subs = array ('<SMALL>','</SMALL>');
$WS->parser_format['start-end']['big']->marks = array ("==","==");
$WS->parser_format['start-end']['big']->subs = array ('<tt>','</tt>');
$WS->parser_format['start-end']['internal links']->marks = array ("[[","]]");
$WS->parser_format['start-end']['internal links']->func = 'wiki_parser_default_internal_link_bis';
$WS->parser_format['start-end']['external links']->marks = array ("[","]");
$WS->parser_format['start-end']['external links']->func = 'wiki_parser_default_external_link_bis';


$WS->parser_format['line-count-start'] = array ();
$WS->parser_format['line-count-start']['tabulacions1']->marks = ' ';
$WS->parser_format['line-count-start']['tabulacions1']->subs = '&nbsp;';
$WS->parser_format['line-count-start']['tabulacions2']->marks = '	';
$WS->parser_format['line-count-start']['tabulacions2']->subs = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
$WS->parser_format['line-count-start']['ul']->marks = '*';
$WS->parser_format['line-count-start']['ul']->subs = array('<ul>','</ul>');
$WS->parser_format['line-count-start']['ul']->func = 'wiki_parser_default_list_bis';
$WS->parser_format['line-count-start']['ul']->elsefunc = 'wiki_parser_default_list_bis';
$WS->parser_format['line-count-start']['ol']->marks = '#';
$WS->parser_format['line-count-start']['ol']->subs = array('<ol>','</ol>');
$WS->parser_format['line-count-start']['ol']->func = 'wiki_parser_default_list_bis';
$WS->parser_format['line-count-start']['ol']->elsefunc = 'wiki_parser_default_list_bis';


$WS->parser_format['line-array-definition'] = array ();

$WS->parser_format['line-array-definition']['row']->marks = '|';
$WS->parser_format['line-array-definition']['row']->func = 'wiki_parser_default_table_bis';
$WS->parser_format['line-array-definition']['row']->elsefunc = 'wiki_parser_default_table_bis';
$WS->parser_format['line-array-definition']['row']->subs = array ('<td class="cell c0">','</td>');


$WS->parser_format['post-parser'] = array();
$WS->parser_format['post-parser']['moodle']->func = 'wiki_moodle_format_text_bis';
?>
