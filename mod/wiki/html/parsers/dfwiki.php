<?php

/**
 * HTML Export
 * this file contains dfwiki sintax parser needed to export to
 * a HTML file.
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: dfwiki.php,v 1.2 2007/06/15 11:43:18 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package HTML_export
 */


//special lines marks

global $WS;
$WS->parser_format = array();

$WS->parser_format['pre-parser'] = array();
$WS->parser_format['pre-parser']['smilies']->func = 'replace_smilies';
$WS->parser_format['pre-parser']['smilies']->reference = true;

$WS->parser_format['no-parse'] = array();
$WS->parser_format['no-parse']['nowiki']->marks = array ('<nowiki>','</nowiki>');

$WS->parser_format['line-definition']->marks = "\r\n";

$WS->parser_format['whole-line'] = array ();
$WS->parser_format['whole-line']['hr']->marks = '-';
$WS->parser_format['whole-line']['hr']->subs = "<hr noshade=\"noshade\" />\n";
$WS->parser_format['whole-line']['hr']->multisubs = false;

$WS->parser_format['line-start'] = array ();

$WS->parser_format['line-start-end'] = array ();
$WS->parser_format['line-start-end']['h1']->marks = array ('===','===');
$WS->parser_format['line-start-end']['h1']->subs = array ('<h1>','</h1>');
$WS->parser_format['line-start-end']['h2']->marks = array ('==','==');
$WS->parser_format['line-start-end']['h2']->subs = array ('<h2>','</h2>');
$WS->parser_format['line-start-end']['h3']->marks = array ('=','=');
$WS->parser_format['line-start-end']['h3']->subs = array ('<h3>','</h3>');

$WS->parser_format['start-end'] = array ();
$WS->parser_format['start-end']['b']->marks = array ("'''","'''");
$WS->parser_format['start-end']['b']->subs = array ('<b>','</b>');
$WS->parser_format['start-end']['i']->marks = array ("''","''");
$WS->parser_format['start-end']['i']->subs = array ('<i>','</i>');
$WS->parser_format['start-end']['internal links']->marks = array ("[[","]]");
$WS->parser_format['start-end']['internal links']->func = 'wiki_parser_default_internal_link_bis';
$WS->parser_format['start-end']['external links']->marks = array ("[","]");
$WS->parser_format['start-end']['external links']->func = 'wiki_parser_default_external_link_bis';

$WS->parser_format['direct-substitution'] = array ();
$WS->parser_format['direct-substitution']['br']->marks = '%%%';
$WS->parser_format['direct-substitution']['br']->subs = '<br />';

$WS->parser_format['line-count-start'] = array ();
$WS->parser_format['line-count-start']['preformat']->marks = ' ';
$WS->parser_format['line-count-start']['preformat']->subs = array ('<pre>',"</pre>\n");
$WS->parser_format['line-count-start']['preformat']->func = 'wiki_parser_default_open_group_bis';
$WS->parser_format['line-count-start']['preformat']->elsefunc = 'wiki_parser_default_close_group_bis';
$WS->parser_format['line-count-start']['ul']->marks = '*';
$WS->parser_format['line-count-start']['ul']->subs = array('<ul>','</ul>');
$WS->parser_format['line-count-start']['ul']->func = 'wiki_parser_default_list_bis';
$WS->parser_format['line-count-start']['ul']->elsefunc = 'wiki_parser_default_list_bis';
$WS->parser_format['line-count-start']['ol']->marks = '#';
$WS->parser_format['line-count-start']['ol']->subs = array('<ol>','</ol>');
$WS->parser_format['line-count-start']['ol']->func = 'wiki_parser_default_list_bis';
$WS->parser_format['line-count-start']['ol']->elsefunc = 'wiki_parser_default_list_bis';
$WS->parser_format['line-count-start']['tabulacions']->marks = ':';
$WS->parser_format['line-count-start']['tabulacions']->subs = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

$WS->parser_format['line-array-definition'] = array ();
$WS->parser_format['line-array-definition']['header']->marks = '!';
$WS->parser_format['line-array-definition']['header']->func = 'wiki_parser_default_table_bis';
$WS->parser_format['line-array-definition']['header']->elsefunc = 'wiki_parser_default_table_bis';
$WS->parser_format['line-array-definition']['header']->subs = array ('<th class="header c0">','</th>');
$WS->parser_format['line-array-definition']['row']->marks = '|';
$WS->parser_format['line-array-definition']['row']->func = 'wiki_parser_default_table_bis';
$WS->parser_format['line-array-definition']['row']->elsefunc = 'wiki_parser_default_table_bis';
$WS->parser_format['line-array-definition']['row']->subs = array ('<td class="cell c0">','</td>');

$WS->parser_format['post-line'] = array();

$WS->parser_format['post-parser'] = array();
$WS->parser_format['post-parser']['moodle']->func = 'wiki_moodle_format_text_bis';
$WS->parser_format['post-parser']['toc']->func = 'wiki_parser_default_toc_bis';
?>
