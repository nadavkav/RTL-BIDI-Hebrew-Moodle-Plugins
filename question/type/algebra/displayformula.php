<?php

// Moodle algebra question type class
// Author: Roger Moore <rwmoore 'at' ualberta.ca>
// License: GNU Public License version 3
    
/**
 * Script which converts the given formula text into LaTeX code and then 
 * displays the appropriate image file. It relies on the LaTeX filter to
 * be present.
 */

require_once('../../../config.php');
require_once("$CFG->dirroot/question/type/algebra/parser.php");

$p = new qtype_algebra_parser;
try {
    $query=urldecode($_SERVER['QUERY_STRING']);
    $m=array();
    if(!preg_match('/vars=([^&]*)&expr=(.*)$/A',$query,$m)) {
        throw new Exception('Invalid query string received from http server!');
    }
    $vars=explode(',',$m[1]);
    if(empty($m[2])) {
        $texexp='';
    } else {
        $exp = $p->parse($m[2],$vars);
        $texexp = '$$'.$exp->tex().'$$';
    }
} catch(Exception $e) {
	$texexp = get_string('parseerror','qtype_algebra',$e->getMessage());
}
$text   = format_text($texexp);

?>
<html>
	<head>
		<title>Formula</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	</head>
	<body bgcolor="#FFFFFF">
		<?php echo $text; ?>
	</body>
</html>
