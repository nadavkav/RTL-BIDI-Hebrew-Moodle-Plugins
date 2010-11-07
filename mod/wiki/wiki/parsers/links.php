<?php
//special lines marks
global $WS;
$WS->parser_format = array();

$WS->parser_format['pre-parser'] = array();
$WS->parser_format['pre-parser']['moodle-format']->func = 'wiki_preformat_htmllines';

$WS->parser_format['no-parse'] = array();
$WS->parser_format['no-parse']['nowiki']->marks = array ('<nowiki>','</nowiki>');

$WS->parser_format['start-end'] = array ();
$WS->parser_format['start-end']['internal links']->marks = array ("[[","]]");
$WS->parser_format['start-end']['internal links']->func = 'wiki_parser_default_internal_link';
$WS->parser_format['start-end']['external links']->marks = array ("[","]");
$WS->parser_format['start-end']['external links']->func = 'wiki_parser_default_external_link';

//helping functions
function wiki_preformat_htmllines (&$text){
    $text2 = str_ireplace ('<p>', "\r\n", $text);
    $text2 = str_ireplace ('</p>', '', $text2);
    $text2 = str_ireplace ('<p />', '', $text2);
    $text2 = str_ireplace ('<br />', "\r\n", $text2);
    $text2 = str_ireplace ('<br />', "\r\n", $text2);
    return $text2;
}

?>
