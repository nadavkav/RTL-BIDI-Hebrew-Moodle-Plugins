<?php
//Parser Created by Ferran Recio & David Castro.


//*********************************** PARSER TO HTML ****************************************
function wiki_sintax_html (&$text){
	global $WS;
    wiki_parser_reset_vars();
    wiki_parser_reset_logs();
    $WS->editor = (!empty($WS->pagedata->editor))? $WS->pagedata->editor : $WS->dfwiki->editor;
    $res = wiki_parse_text ($text,$WS->editor);

    return $res;
}
//-------------------------------- PRIVATE FUNCTIONS -----------------------------

//reset vars array
function wiki_parser_reset_vars(){
	global $WS;
	$WS->parser_vars = array();
    return true;
}
//reset log array
function wiki_parser_reset_logs(){
	global $WS;
    $WS->parser_logs = array();
    return true;
}
//reset sintax
function wiki_parser_reset_sintax(){
	global $WS;
    $WS->parser_format = array();
    return true;
}


//convert wiki content to html content
function wiki_parse_text($text,$format){
    global $CFG,$WS;

    //import format from the array
    if (!file_exists($CFG->dirroot.'/mod/wiki/wiki/parsers/'.$format.'.php')) error ('parser '.$format.' doesn\'t exists.');
    require_once ($CFG->dirroot.'/mod/wiki/wiki/parsers/'.$format.'.php');

    //PRE-PARSE
    if (isset($WS->parser_format['pre-parser'])){
    	foreach ($WS->parser_format['pre-parser'] as $par){
    		if (isset($par->func)){
    			$func = $par->func;
    			if (!isset ($par->reference))$par->reference = false;
    			if ($par->reference){
    				$func($text);
    			}else{
    				$text = $func($text);
    			}
    		}
    	}
    }

    //NO-PARSE
    if (isset($WS->parser_format['no-parse'])){
    	//if it's not an array, the function is supposed to do it
    	if (!is_array($WS->parser_format['no-parse'])) return $WS->parser_format['no-parse']($text);

    	$noparsetext = array();

    	foreach ($WS->parser_format['no-parse'] as $par){
    		//text with no-parse marks
    		$before = '';
    		//text still amb no-pase  text encara amb els talls de no-parse
    		$after = $text;
    		//start and ending marks
    		$startmark = $par->marks[0];
    		$endmark = $par->marks[1];
    		$endmark_len = strlen($endmark);
    		$startmark_len = strlen($startmark);

    		//begin text chops which won't be parsed
    		$noparsetext[$startmark] = array();

    		//index used to be foreach free
    		$index = 0;

    		while((($l = strpos($after, $startmark)) !== false) && ($r = strpos($after, $endmark, $l + $startmark_len))) {
    			//save no-parse text
    			if (!isset($par->delmarks)) $par->delmarks = true;
    			$subtext = ($par->delmarks)? substr($after, $l + $startmark_len, $r - $l - $startmark_len) : $startmark.substr($after, $l + $startmark_len, $r - $l - $startmark_len).$endmark;
    			if (isset($par->func)){
    				$func = $par->func;
    				$noparsetext[$startmark][$index] = $func($subtext);
    			}else{
    				$noparsetext[$startmark][$index] = $subtext;
    			}
    			//parse before part
    			$before.= substr($after, 0, $l).$startmark;
    			$after = substr($after, $r + $endmark_len);
    			$index++;
    		}
    		$before.= $after;
    		//save $text without no-parse chops
    		$text = $before;

    	}

    	//here we'd parse the text
    	$text = wiki_parser_inlines($text);

    	$res = $text;
    	foreach ($noparsetext as $mark => $mtext){
    		$index = 0;
    		$after = $res;
    		$res = '';
    		$startmark_len = strlen($mark);
    		while (($l = strpos($after, $mark)) !== false){
    			$res.= substr($after, 0, $l).$mtext[$index];
    			$index++;
    			$after = substr($after, $l + $startmark_len);
    		}
    		$res.=$after;
    	}

    } else {
    	//all text is passed
    	$res = wiki_parser_inlines($text);
    }

    //POST-PARSER
    if (isset($WS->parser_format['post-parser'])){
    	foreach ($WS->parser_format['post-parser'] as $par){
    		if (isset($par->func)){
    			$func = $par->func;
    			if (!isset ($par->reference))$par->reference = false;
    			if ($par->reference){
    				$func($res);
    			}else{
    				$res = $func($res);
    			}
    		}
    	}
    }

    return $res;
}

//this function parses every paragraf
function wiki_parser_inlines (&$text){

    global $WS;

    //LINE-DEFINITION
    if (!isset($WS->parser_format['line-definition']->marks)) $WS->parser_format['line-definition']->marks = "\r\n";
    if (!isset($WS->parser_format['line-definition']->type)) $WS->parser_format['line-definition']->type = "plain";

    if (isset($WS->parser_format['line-definition']->func)){
    	$func = $WS->parser_format['line-definition']->func;
    	if(isset($WS->parser_format['line-definition']->marks)){
    		$lines = $func ($text, $WS->parser_format['line-definition']->marks);
    	}else{
    		$lines = $func ($text);
    	}
    }else{
    	$lines = explode($WS->parser_format['line-definition']->marks,$text);
    }

    //this is the result string
    $res = '';
    //analice every paragraf
    foreach ($lines as $line){
    	$res.= wiki_parser_line($line);
    	//new paragraf
    	switch ($WS->parser_format['line-definition']->type) {
    		case 'html':
    			break;
    		default:
    			if(chop($line)==''){
    				$res.='<br /><br />';
    			}
    			break;
    	}
    	$res.="\n";
    }
    //cheat!!! this line end all opened tables and lists
    $line='';
    $res.= wiki_parser_line($line);

    return $res;
}

//private function to convert a line in fomrat wiki into html
function wiki_parser_line(&$line){
	global $WS;
    //sintax is in ewiki.php between 1841 and 2035

    $res = $line;

    //PRE-LINE
    if (isset($WS->parser_format['pre-line'])){
    	foreach ($WS->parser_format['pre-line'] as $par){
    		if (isset($par->func)){
    			$func = $par->func;
    			$res = $func($res);
    		}
    	}
    }

    //SPECIAL-LINE
    if (isset($WS->parser_format['special-line'])){
    	foreach ($WS->parser_format['special-line'] as $par){
    		if (chop($line)==$par->marks){
    			if (isset($par->func)){
    				$func = $par->func;
    				$res = (isset($par->subs))? $func($line,$par->marks,$par->subs) : $func($line,$par->marks);
    			}else{
    				$res = $par->subs;
    			}
    			return $res;
    		} else {
    			if (isset($par->elsefunc)){
    				$func = $par->elsefunc;
    				$res = (isset($par->subs))? $func($res,$par->marks,$par->subs) : $func($res,$par->marks);
    				if (!isset ($par->exit)) $par->exit = false;
    				if($par->exit) return $res;
    			}
    		}
    	}
    }

    //WHOLE-LINE
    if (isset($WS->parser_format['whole-line'])){
    	$chopline = chop($line);
    	foreach ($WS->parser_format['whole-line'] as $par){
    		if (str_replace ( $par->marks, '', $chopline)=='' && $chopline!=''){
    			$res='';
    			if (!isset($par->multisubs)) $par->multisubs = true;
    			if ($par->multisubs){
    				$max = strlen($chopline)/strlen($par->marks);
    				for ($i=0;$i<$max;$i++){
    					if (isset($par->func)){
    						$func = $par->func;
    						$res.= (isset($par->subs))? $func($line,$par->subs) : $func($line);
    					}else{
    						$res.= $par->subs;
    					}
    				}
    			}else{
    				if (isset($par->func)){
    					$func = $par->func;
    					$res = (isset($par->subs))? $func($line,$par->marks,$par->subs) : $func($line,$par->marks);
    				}else{
    					$res = $par->subs;
    				}
    			}
    			return $res;
    		}else{
    			if (isset($par->elsefunc)){
    				$func = $par->elsefunc;
    				$res = (isset($par->subs))? $func($line,$par->marks,$par->subs) : $func($line,$par->marks);
    			}
    		}
    	}
    	unset ($chopline);
    }

    //LINE-START
    if (isset($WS->parser_format['line-start'])){
    	$chopline = chop($line);
    	foreach ($WS->parser_format['line-start'] as $par){
    		$mark_len = strlen($par->marks);
    		if (strpos($res, $par->marks) === 0) {
    			$inside = substr($res, $mark_len);
    			if (!isset($par->func)) $par->func = 'wiki_parser_default_encapsule';
    			$func = $par->func;
    			$part = (isset($par->subs))? $func($inside,$par->marks,$par->subs) : $func($inside,$par->marks);
    			$res = $part;
    			unset ($part);
    		} else {
    			//elsefunc
    			if (isset($par->elsefunc)){
    				$func = $par->elsefunc;
    				$res = (isset($par->subs))? $func($res,$par->marks,$par->subs) : $func($res,$par->marks);
    			}
    		}
    	}
    	unset ($chopline);
    }

    //LINE-END
    if (isset($WS->parser_format['line-end'])){
    	foreach ($WS->parser_format['line-end'] as $par){
    		$mark_len = strlen($par->marks);
    		$chopline = chop($line);
    		if (strrpos($chopline, $par->marks) === strlen($chopline)-strlen($par->marks)) {
    			$inside = substr($res,0, strrpos($chopline, $par->marks));
    			if (!isset($par->func)) $par->func = 'wiki_parser_default_encapsule';

    			$func = $par->func;
    			$part = (isset($par->subs))? $func($inside,$par->marks,$par->subs) : $func($inside,$par->marks);
    			$res = $part;
    			unset ($part);
    		} else {
    			//elsefunc
    			if (isset($par->elsefunc)){
    				$func = $par->elsefunc;
    				$res = (isset($par->subs))? $func($res,$par->marks,$par->subs) : $func($res,$par->marks);
    			}
    		}
    	}
    	unset ($chopline);
    }

    //LINE-START-END
    if (isset($WS->parser_format['line-start-end'])){
    	foreach ($WS->parser_format['line-start-end'] as $par){
    		$startmark_len = strlen($par->marks[0]);
    		$endmark_len = strlen($par->marks[1]);
    		if((($l = strpos($res, $par->marks[0])) !== false) && ($r = strpos($res, $par->marks[1], $l + $startmark_len))) {
    			if ($l==0){
    				$inside = substr($res, $l + $startmark_len, $r - $l - $startmark_len);
    				$after = substr($res, $r + $endmark_len);

    				if (!isset($par->func)) $par->func = 'wiki_parser_default_header';
    				$func = $par->func;
    				$part = (isset($par->subs))? $func($inside,$par->marks,$par->subs) : $func($inside,$par->marks);
    				$res = $part.$after;
    				unset ($part);
    			}
    		} else {
    			//elsefunc
    			if (isset($par->elsefunc)){
    				$func = $par->elsefunc;
    				$res = (isset($par->subs))? $func($res,$par->marks,$par->subs) : $func($res,$par->marks);
    			}
    		}
    	}
    }
    //START-END
    if (isset($WS->parser_format['start-end'])){
    	foreach ($WS->parser_format['start-end'] as $par){
    		$startmark_len = strlen($par->marks[0]);
    		$endmark_len = strlen($par->marks[1]);
    		$loop = 20;
    		while(($loop--) && (($l = strpos($res, $par->marks[0])) !== false) && ($r = strpos($res, $par->marks[1], $l + $startmark_len))) {
    			$before = substr($res, 0, $l);
    			$inside = substr($res, $l + $startmark_len, $r - $l - $endmark_len);
    			$after = substr($res, $r + $endmark_len);
    			//if there's not a function defined
    			if (!isset($par->func)) $par->func = 'wiki_parser_default_encapsule';             
    			$func = $par->func;
    			$part = (isset($par->subs))? $func($inside,$par->marks,$par->subs) : $func($inside,$par->marks);
    			$res = $before.$part.$after;
    			unset ($part);
    		}
    	}
    }
    
	//LINE-COUNT-START
    if (isset($WS->parser_format['line-count-start'])){
    	$chopline = chop($res);
    	foreach ($WS->parser_format['line-count-start'] as $par){
    		$mark_len = strlen($par->marks);
    		$num=0;
    		while (($l = strpos($chopline, $par->marks)) === 0){
    			$chopline = substr($chopline, $l + $mark_len);
    			$num++;
    		}

    		if ($num>0){
    			$max = $num*$mark_len;
    			$inside = substr($res, $max*$mark_len);
    			if (!isset($par->func)) $par->func = 'wiki_parser_default_repeat_before';
    			$func = $par->func;
    			$part = (isset($par->subs))? $func($inside,$max,$par->marks,$par->subs) : $func($inside,$max,$par->marks);
    			$res = $part;
    			unset ($part);
    		} else {
    			//execute elsefunc
    			if (isset($par->elsefunc)) {
    				$func = $par->elsefunc;
    				$part = (isset($par->subs))? $func($res,0,$par->marks,$par->subs) : $func($res,0,$par->marks);
    				$res = $part;
    			}
    		}
    	}
    	unset ($chopline);
    }

    //LINE-ARRAY-DEFINITION
    if (isset($WS->parser_format['line-array-definition'])){
    	foreach ($WS->parser_format['line-array-definition'] as $par){
    		$chopline = chop($line);
    		$mark_len = strlen($par->marks);
    		if((strpos($chopline, $par->marks)=== 0) && (strrpos($chopline, $par->marks)) === strlen ($chopline)-1) {
    			$inside = explode ($par->marks,substr($res, $mark_len,  strrpos($res, $par->marks) - $mark_len));

    			if (!isset($par->func)) $par->func = 'wiki_parser_default_array_encapsule';
    			$func = $par->func;
    			$res = (isset($par->subs))? $func($inside,$par->marks,$par->subs) : $func($inside,$par->marks);
    			unset ($part);
    		} else {
    			//elsefunc
    			if (isset($par->elsefunc)){
    				$func = $par->elsefunc;
    				$res = (isset($par->subs))? $func($res,$par->marks,$par->subs) : $func($res,$par->marks);
    			}
    		}
    	}
    }

    //DIRECT-SUBSTITUTION
    if (isset($WS->parser_format['direct-substitution'])){
    	foreach ($WS->parser_format['direct-substitution'] as $par){
    		$mark_len = strlen($par->marks);
    		$loop = 20;
    		while(($loop--) && (($l = strpos($res, $par->marks)) !== false)) {
    			$before = substr($res, 0, $l);
    			$inside = substr($res, $l, $mark_len);
    			$after = substr($res, $l + $mark_len);
    			//if there's no function defined then let's set it
    			if (!isset($par->func)) $par->func = 'wiki_parser_default_substitution';

    			$func = $par->func;
    			$part = (isset($par->subs))? $func($inside,$par->marks,$par->subs) : $func($inside,$par->marks);
    			$res = $before.$part.$after;
    			unset ($part);
    		}
    	}
    }

    //POST-LINE
    if (isset($WS->parser_format['post-line'])){
    	foreach ($WS->parser_format['post-line'] as $par){
    		//just execute the function
    		if (isset($par->func)){
    			$func = $par->func;
    			$res = $func($res);
    		}
    	}
    }

    return $res;
}

//helping functions

function wiki_moodle_format_text(&$text){
    return format_text($text,  FORMAT_HTML /*FORMAT_HTML*/);
}

function wiki_parser_default_identity (&$line,$p1='',$p2='',$p3='',$p4=''){
    echo '-&gt;identityt<br />';
    return $line;
}

function wiki_parser_default_internal_link (&$line,$marks){
    return wiki_sintax_create_internal_link ($line);
}

function wiki_parser_default_external_link (&$line,$marks){
    return wiki_sintax_create_external_link ($line);
}

function wiki_parser_default_encapsule (&$line,$marks,$subs=false){
    if ($subs === false) $subs = array('','');
    return $subs[0].$line.$subs[1];
}

function wiki_parser_default_substitution ($text,$marks,$subs=false){
    if ($subs===false) $subs = '';
    if (is_array($subs)) $subs = implode('',$subs);
    return $subs;
}

function wiki_parser_default_header (&$line,$marks,$subs=false){
    global $WS;
	if ($subs===false) $subs = array ('','');
    if (!is_array($subs)) $subs = array ($subs,$subs);

    $res='';

    //import parser_vars
    if (!isset($WS->parser_vars['header'])){
    	//indica si estem dins del grup
    	$WS->parser_vars['header'] = 1;
    }

    //create anchor label
    $anchor = 'toc'.$WS->parser_vars['header'];
    $WS->parser_vars['header']++;

    //save header into log
    if (!isset($WS->parser_logs['toc'])){
    	$WS->parser_logs['toc'] = array();
    	$res.='<<TOC>>';
    }
    $WS->parser_logs['toc'][] = array ($subs[0],$anchor,$line);

    //add anchors to $subs
    $subs = array ('<a name="'.$anchor.'"></a>'.$subs[0] , $subs[1]);

    //encapsule header:
    $res.= wiki_parser_default_encapsule ($line,$marks,$subs);
    return $res;
}

function wiki_parser_default_repeat_before ($line,$max,$marks,$subs=''){
    $res='';
    for ($i=0;$i<$max;$i++){
    	$res.=$subs;
    }
    $res.=$line;
    return $res;
}

function wiki_parser_default_array_encapsule ($line,$marks,$subs = false){
    if ($subs === false) $subs = array ('','');
    if (!is_array($line)) $line = array ($line);
    $res = '';
    foreach ($line as $part){
    	$res.= wiki_parser_default_encapsule ($part,'',$subs);
    }
    return $res;
}

function wiki_parser_default_toc (&$text){
	global $WS;
	if (!isset($WS->parser_logs['toc'])) return $text;

    $res = '';
    //look for the first title
    $tocpos = strpos($text,'<<TOC>>');
    if ($tocpos===false)$tocpos = 0;
    $before = substr ($text, 0, $tocpos);
    $text = substr ($text,$tocpos+strlen('<<TOC>>'),strlen($text));
    $res.=$before;
    //look for the lowest level
    $min = 3;
    foreach ($WS->parser_logs['toc'] as $header){
    	$lev = substr ($header[0],2,1);
    	$min = ($min>$lev)? $lev : $min;
    }
    $sesg = $min-1;

    $res.= '<div class="nwikisintaxtoc">'.get_string('toc','wiki').':<br />';

    $num = array(0,0,0);
    foreach ($WS->parser_logs['toc'] as $header){

    	//get level
    	$lev = substr ($header[0],2,1)-$sesg;

    	if ($lev<2){
    		$num[1]=0;
    	}
    	if ($lev<3){
    		$num[2]=0;
    	}
    	$num[$lev-1]++;
    	$numt = '';
    	for ($i=0;$i<$lev;$i++){
    		$res.='&nbsp;&nbsp;&nbsp;';
    		$numt.= $num[$i].'.';
    	}
    	$res.= '<a href="#'.$header[1].'">'.$numt.' '.$header[2].'</a><br />';
    }
    $res.="</div>\n".$text;
    return $res;
}

//this functions contains a group of lines
function wiki_parser_default_open_group ($line,$p1='',$p2='',$p3=false){
    global $WS;
    //if there are 4 params just ignore the second one
    if ($p3===false){
    	$marks = $p1;
    	$subs = $p2;
    }else{
    	$marks = $p2;
    	$subs = $p3;
    	$num = $p1;
    }
    $subs = (is_array($subs))? $subs : array ($subs,$subs);
    $marks = (is_array($marks))? $marks[0] : $marks;

    //initialize parse_vars[list]
    if (!isset($WS->parser_vars['enc_group'])) $WS->parser_vars['enc_group'] = array();
    if (!isset($WS->parser_vars['enc_group'][$marks])){
    	//indicates if within the given group
    	$WS->parser_vars['enc_group'][$marks] = false;
    }

    $res = '';
    //start up the group if needed
    if (!$WS->parser_vars['enc_group'][$marks]){
    	$res = $subs[0];
    	$WS->parser_vars['enc_group'][$marks] = true;
    }
    //if there are marks just set them
    if (isset($num)){
    	for ($i=0;$i<$num-1;$i++){
    		$res.= $marks;
    	}
    }
    //place the line
    $res.= $line;

    return $res;
}

//this function encapsulates a bunch of lines
function wiki_parser_default_close_group ($line,$p1='',$p2='',$p3=false){
	global $WS;
	
	if ($p3===false){
    	$marks = $p1;
    	$subs = $p2;
    }else{
    	$marks = $p2;
    	$subs = $p3;
    	$num = $p1;
    }
    $subs = (is_array($subs))? $subs : array ($subs,$subs);
    $marks = (is_array($marks))? $marks[0] : $marks;

    //initialize parse_vars[list]
    if (!isset($WS->parser_vars['enc_group'])) $WS->parser_vars['enc_group'] = array();
    if (!isset($WS->parser_vars['enc_group'][$marks])){
    	$WS->parser_vars['enc_group'][$marks] = false;
    }

    $res = '';
    if ($WS->parser_vars['enc_group'][$marks]){
    	$res = $subs[1];
    	$WS->parser_vars['enc_group'][$marks] = false;
    }
    $res.= $line;

    return $res;
}

function wiki_parser_default_table ($parts,$marks,$subs='') {
    
    global $WS;
	
    //initialize parse_vars[list]
    if (!isset($WS->parser_vars['table'])){
    	$WS->parser_vars['table'] = array();
    	$WS->parser_vars['table']['find'] = array();
    	$WS->parser_vars['table']['num'] = 0;
    	//indicates if we're within a line
    	$WS->parser_vars['table']['intable'] = false;
    }

    if (!is_array($subs)) $subs = array ($subs,$subs);

    //if no type is set then do it
    if (!in_array($marks,$WS->parser_vars['table']['find'])){
    	$WS->parser_vars['table']['find'][] = $marks;
    }

    //if we're at the first symbol then set it 0
    if ($WS->parser_vars['table']['find'][0] == $marks){
    	$WS->parser_vars['table']['num'] = 0;
    }

    //detects if there's a table inline
    if (!is_array($parts)){
    	if ($WS->parser_vars['table']['num'] > -1) $WS->parser_vars['table']['num']++;
    } else {
    	$WS->parser_vars['table']['num'] = -1;
    }

    $res = '';
    //if num = -1 means there's a row. If -2 it's already written
    if ($WS->parser_vars['table']['num'] < 0){
    	//open the talbe if necessary
    	if ($WS->parser_vars['table']['num'] == -1){
    		if (!$WS->parser_vars['table']['intable']){
    			$res = '<table align="center" width="80%"  class="generalbox" border="0" cellpadding="0" cellspacing="0">
    				<tr><td bgcolor="#ffffff" class="generalboxcontent">
    				<table width="100%" border="0" align="center"  cellpadding="5" cellspacing="1" class="generaltable" >';
    			$WS->parser_vars['table']['intable'] = true;
    		}

    		//write the line
    		$res.='<tr>';
    		$res.=wiki_parser_default_array_encapsule ($parts,$marks,$subs);
    		$res.='</tr>';
    		//means the lines is written
    		$WS->parser_vars['table']['num']=-2;
    	} else {
    		//if the line is already written then just return the string
    		$res = (is_array($parts)) ? implode ('',$parts) : $parts;
    	}
    } else{
    	if (count ($WS->parser_vars['table']['find']) == $WS->parser_vars['table']['num']){
    		//close the table
    		if ($WS->parser_vars['table']['intable']){
    			$res = '</table></td></tr></table>';
    			$WS->parser_vars['table']['intable'] = false;
    		}
    		//place the part
    		$res.= $parts;
    	}else{
    		//keep searching
    		$res = $parts;
    	}
    }
    return $res;
}

function wiki_parser_default_list ($inside,$max,$marks,$subs) {
	global $WS;
    //initialize parse_vars[list]
    if (!isset($WS->parser_vars['list'])){
    	$WS->parser_vars['list'] = array();
    	$WS->parser_vars['list']['find'] = array();
    	$WS->parser_vars['list']['num'] = 0;
    	//indicates it's the last level inserted
    	$WS->parser_vars['list']['lastlevel'] = 0;
    	//indicates which level is open
    	$WS->parser_vars['list']['listtype'] = array();
    	//tag for elements
    	$WS->parser_vars['list']['listelem'] = array();
    }

    if (!is_array($subs)) $subs = array ($subs,$subs);

    //set the type if not set already
    if (!in_array($marks,$WS->parser_vars['list']['find'])){
    	$WS->parser_vars['list']['find'][] = $marks;
    }

    //if we're at the first symbol set num to 0
    if ($WS->parser_vars['list']['find'][0] == $marks){
    	$WS->parser_vars['list']['num'] = 0;
    }

    //detects a possible table inline
    if ($max==0){
    	if ($WS->parser_vars['list']['num'] > -1) $WS->parser_vars['list']['num']++;
    } else {
    	$WS->parser_vars['list']['num'] = -1;
    }

    $res = '';
    if ($WS->parser_vars['list']['num'] < 0){
    	//open table if needed
    	if ($WS->parser_vars['list']['num'] == -1){

    		//open up to the level
    		if ($WS->parser_vars['list']['lastlevel'] != $max){
    			//ither open or close levels as required
    			$inc = ($WS->parser_vars['list']['lastlevel']>$max)? -1 : 1;
    			while ($WS->parser_vars['list']['lastlevel'] != $max){
    				if ($inc<0){
    					//close
    					$res.=$WS->parser_vars['list']['listtype'][$WS->parser_vars['list']['lastlevel']-1];
    				} else {
    					//open
    					$res.= $subs[0];
    					$WS->parser_vars['list']['listtype'][$WS->parser_vars['list']['lastlevel']] = $subs[1];
    				}

    				$WS->parser_vars['list']['lastlevel'] = $WS->parser_vars['list']['lastlevel']+$inc;
    			}
    		}
    		$res.='<li>'.$inside;
    		//indicates a line was written
    		$WS->parser_vars['list']['num']=-2;
    	} else {
    		
    		//returns the string
    		$res = $inside;
    	}
    } else{
    	if (count ($WS->parser_vars['list']['find']) == $WS->parser_vars['list']['num']){
    		//close all levels
    		if ($WS->parser_vars['list']['lastlevel'] > 0){
    			$inc = ($WS->parser_vars['list']['lastlevel']>$max)? -1 : 1;
    			while ($WS->parser_vars['list']['lastlevel'] > 0){
    				//close
    				$res.=$WS->parser_vars['list']['listtype'][$WS->parser_vars['list']['lastlevel']-1];
    				$WS->parser_vars['list']['lastlevel']--;
    			}
    		}
    	}else{

    	}
    	$res.=$inside;
    }
    return $res;
}

//this function creates de url to a link
function wiki_sintax_create_internal_link (&$linktext) {
    global $WS,$itinerary,$CFG,$COURSE;

    $res = '';

    //separate type link from link text
    $parts = explode (":",$linktext);

    if (count($parts)==1){
    	$linktype = 'internal';
    	$linkname = $parts[0];
    }else{
    	$linktype = $parts[0];
    	$linkname = $parts[1];
    }

    switch ($linktype){
    	case 'internal': //normal internal links
    		//separate linktext into pagename and text
    		$parts = explode ("|",$linkname);

    		if (count($parts)==1){
    			$linkpage = trim($parts[0]);
    			$linktext = $parts[0];
    		}else{
    			$linkpage = trim($parts[0]);
    			$linktext = $parts[1];
    		}

            if(isset($itinerary)){
    			if (!wiki_page_exists($WS,$linkpage)){
    				$res = '<b><u>'.$linktext.'</u></b><a target="popup" href="'.$CFG->wwwroot.'/mod/wiki/view.php?a='.$itinerary['dfwiki'].'&amp;page='.urlencode($linkpage).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'">?</a>';
    			}else{
    				$res = '<a href="view.php?id='.$WS->cm->id.'&amp;page='.urlencode($linkpage).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'&amp;name=dfwikipage">'.$linkpage.'</a>';
    			}
            }else{
    			if (wiki_page_exists($WS,$linkpage)){
    				//if the page already exists
    				if(isset($WS->dfcourse)) {
    				//course internal link:
    					$res = '<a href="view.php?id='.$COURSE->id.'&amp;page='.urlencode($linkpage).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'">'.$linktext.'</a>';
    				}
    				else {
    					//module internal link:
    					$res = '<a href="view.php?id='.$WS->cm->id.'&amp;page='.urlencode($linkpage).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'">'.$linktext.'</a>';
    				}
    			}else{
    				//to create the page
    				$res = '<b><u>'.$linktext.'</u></b><a href="view.php?id='.$WS->linkid.'&amp;page='.urlencode($linkpage).'&amp;gid='.$WS->groupmember->groupid.'&amp;uid='.$WS->member->id.'">?</a>';
    			}
            }

    		//save link into log
    		if (!isset($WS->parser_logs['internal'])) $WS->parser_logs['internal'] = array();
    		if (!in_array($linkpage,$WS->parser_logs['internal'])) $WS->parser_logs['internal'][] = $linkpage;

    		break;


    	case 'user':
    		$res = wiki_get_user_info ($linkname,25);
    		break;

    	case 'attach':
    		$res = '['.wiki_upload_url($linkname,$WS).' '.$linkname.']';
    		break;
    	default: //error
    }

    return $res;
}

//this function creates de url to a link
function wiki_sintax_create_external_link (&$linktext) {

    $res = '';

    //if text doesn't start with http://, return the internal link.
    if (stripos ($linktext,'://')===false) {
    	return '['.$linktext.']';
    }
    //separate type link from link text
    $parts = explode (" ",$linktext,2);

    if (count($parts)==1){
    	$linkurl = $parts[0];
    	$linkname = $parts[0];
    }else{
    	$linkurl = $parts[0];
    	$linkname = $parts[1];
    }

    //get url extension
    $parts = explode ('.',$linkurl);
    $extension = $parts[count($parts)-1];

    //analize if it's an image
    $extensions = array (
    					'image' => array ('jpg','jpeg','gif','bmp','png'),
    					'flash' => array ('swf')
    				);


    foreach ($extensions as $typ => $ext){
    	if (in_array(strtolower($extension),$ext)){
    		$type = $typ;
    	}
    }
    if (!isset($type)){
    	$type = $extension;
    }
    switch ($type){
    	case 'image':
    		$res = '<img src="'.$linkurl.'" alt="'.$linkname.'" />';
    		break;
    	case 'flash':
    		//get size from $link name
    		$parts = explode(' ',$linkname);
    		
			if (count($parts)!=2){
    			$parts = array ('320','240');
    		} else {
    			$parts = array (trim($parts[0]),trim($parts[1]));
    			if (strlen($parts[0])!=strspn($parts[0], '0123456789') && strlen($parts[1])!=strspn($parts[1], '0123456789')){
    				$parts = array ('320','240');
    			}
    		}
    		$res = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="'.$parts[0].'" height="'.$parts[1].'">
    				<param name="movie" value="'.$linkurl.'" />
    				<param name="quality" value="high" />
    				<embed src="'.$linkurl.'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="'.$parts[0].'" height="'.$parts[1].'"></embed>
    			</object>';
    		break;
    	default:
    		$res = '<a href="'.$linkurl.'">'.$linkname.'</a>';
    		break;
    }

    return $res;
}

function wiki_parser_default_external_link_ewiki($line){
	$begin = strpos($line, 'http://');
	$count = 0;
	while ($begin !== false){
		for ($i=$begin; $i<strlen($line)and $line[$i]!==' ';$i++){
		}
		$end = $i;
		if (isset($line[$begin-1])){
			$ant = $line[$begin-1];
		}else{
			$ant = $line[$begin];
		}
		if ($ant !== '"'){
			if (ereg("[/!/~]", $ant)){
				$url = substr($line, $begin-1, $end-$begin+1);
				$link = substr($line, $begin, $end-$begin);
				$vect[$count]['url'] = $url;
				$vect[$count]['link'] = $link;
			}else{
				$url = substr($line, $begin, $end-$begin);
				$vect[$count]['url'] = $url;
				$link = wiki_sintax_create_external_link ($url);
				$vect[$count]['link'] = $link;	
			}
			$count++;	
		}		
		$begin = strpos($line, 'http://', $end);
	}
	for ($i=0; $i<$count; $i++){
		$pos = strpos($line, $vect[$i]['url']);
		$line = substr_replace($line, $vect[$i]['link'], $pos, strlen($vect[$i]['url']));
	}
	return $line;
}

function wiki_parser_default_internal_link_ewiki($line){
	$words = explode (' ', $line);
	foreach ($words as $word){
		if (wiki_is_wiki_word($word)){
			$word = wiki_sintax_create_internal_link($word);
		}
		$words_aux[] = $word;
	}
	$line = implode(' ',$words_aux);
	return $line;
}

function wiki_is_wiki_word ($word){
	$may1 = false;
	$may2 = false;
	if (isset($word[0])){
		if (ereg('[A-Z]',$word[0])) {
			$may1=true;
			for ($i=1; $i<strlen($word); $i++){
				if(ereg('[A-Z]',$word[$i])){
					if($i===1){
						return false;
					}else{
						$may2=true;
					}
				}
			}
		}
		$res = $may1 && $may2;	
	}else{
		$res = false;
	}
	return $res;
}

function wiki_parser_default_link_ewiki(&$linktext){
	$res = '';
	$pieces = explode( '"',$linktext);
	$parts = explode("|",$linktext);
	$fracs = explode (" ",$linktext,2);
	//linkname with '""'
	if ($pieces[0] !== $linktext && count($pieces)==3){
		if (empty($pieces[0])){
			$pieces[1] = trim($pieces[1]);
			$pieces[2] = trim($pieces[2]);
			if(strpos($pieces[2],"http://")!== false){
				$linkurl = $pieces[2];
				$linkname = $pieces[1];
			}elseif (wiki_is_wiki_word($pieces[2])){
				$intlink = 'internal:'.$pieces[2].'|'.$pieces[1];	
				$linktext = wiki_sintax_create_internal_link($intlink);
				return $linktext;
			}else{
				$res = "[".$linktext."]";
			}
		}elseif (empty($pieces[2])){
			$pieces[0] = trim($pieces[0]);
			$pieces[1] = trim($pieces[1]);
			if(strpos($pieces[0],"http://")!== false){
				$linkurl = trim($pieces[0]);
				$linkname = trim($pieces[1]);
			}elseif (wiki_is_wiki_word(trim($pieces[0]))){
				$intlink = 'internal:'.$pieces[0].'|'.$pieces[1];	
				$linktext = wiki_sintax_create_internal_link($intlink);
				return $linktext;
			}else{
				$res = "[".$linktext."]";
			}	
		}else{
			$res = "[".$linktext."]";
		}
	}else{
		//linkname with '|'
		if (($parts[0] !== $linktext) && (count($parts)===2)){
			if((strpos($parts[1],"http://")!== false)){
				$linkname = trim($parts[0]);
				$linkurl = trim($parts[1]);
			}elseif ((strpos($parts[0],"http://")!== false)){
				$linkname = trim($parts[1]);
				$linkurl = trim($parts[0]);
			}else{
				$res = "[".$linktext."]";
			}
		}else{
			//linkname with ' '
			if (count($fracs)==1){
    			$linkurl = $fracs[0];
    			$linkname = $fracs[0];
    		}else{
    			$linkurl = $fracs[0];
    			$linkname = $fracs[1];
   			}	
		}	
	}
	
	//get url extension
    $parts = explode ('.',$linkurl);
    $extension = $parts[count($parts)-1];

    //analize if it's an image
    $extensions = array (
    					'image' => array ('jpg','jpeg','gif','bmp','png'),
    					'flash' => array ('swf')
    				);

    foreach ($extensions as $typ => $ext){
    	if (in_array($extension,$ext)){
    		$type = $typ;
    	}
    }
    if (!isset($type)){
    	$type = $extension;
    }
    switch ($type){
    	case 'image':
    		$res = '<img src="'.$linkurl.'" alt="'.$linkname.'" />';
    		break;
    	case 'flash':
    		//get size from $link name
    		$parts = explode(' ',$linkname);
    		
			if (count($parts)!=2){
    			$parts = array ('320','240');
    		} else {
    			$parts = array (trim($parts[0]),trim($parts[1]));
    			if (strlen($parts[0])!=strspn($parts[0], '0123456789') && strlen($parts[1])!=strspn($parts[1], '0123456789')){
    				$parts = array ('320','240');
    			}
    		}
    		$res = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="'.$parts[0].'" height="'.$parts[1].'">
    				<param name="movie" value="'.$linkurl.'" />
    				<param name="quality" value="high" />
    				<embed src="'.$linkurl.'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="'.$parts[0].'" height="'.$parts[1].'"></embed>
    			</object>';
    		break;
    	default:
    		$res = '<a href="'.$linkurl.'">'.$linkname.'</a>';
    		break;
    }
 	 return $res;
}

function wiki_parser_attached_files_ewiki($line){
	global $WS;
	$begin = strpos($line, 'internal://') ;
	while ($begin !== false){
		for ($i=$begin; $i<strlen($line)and $line[$i]!== ' '; $i++){
		}
		$end = $i;
		$file = substr($line, $begin+11, $end-($begin+11));
		$internal = '<a href="'.wiki_upload_url($file,$WS).'">'.$file.'</a>';
		$line = str_replace ('internal://'.$file, $internal, $line);
		$begin = strpos($line, 'internal://', $end);
	}
	return $line;
}

// -------------------------------------- FIND WIKI LINKS IN A PAGE ---------------------------------------

//this function returns all the internal links in a page content.
//@return an array of pagenames
function wiki_sintax_find_internal_links ($text){
    global $WS;
    wiki_parser_reset_vars();
    wiki_parser_reset_logs();
    wiki_parser_reset_sintax();
    $res = wiki_parse_text ($text,'links');
    if (!isset($WS->parser_logs['internal'])) $WS->parser_logs['internal'] = array();
    if (!is_array($WS->parser_logs['internal'])) $WS->parser_logs['internal'] = array();
    return $WS->parser_logs['internal'];
}


?>
