<?php
//aquest seria l'array del format
$WS->wiki_format = array();

//this array is where parser saves it's global variables.
$WS->parser_vars = array();
//this array is where parser saves logs (like TOC...)
$WS->parser_logs = array();

//*********************************** PARSER TO HTML ****************************************
function dfwiki_sintax_html (&$text){
    global $WS;
    dfwiki_parser_reset_vars();
    dfwiki_parser_reset_logs();
    $editor = (isset($WS->pagedata->editor))? $WS->pagedata->editor : $WS->dfwiki->editor;
    $res = dfwiki_parse_text ($text,$editor);
    return $res;
}
//-------------------------------- FUNCIONS PRIVADES -----------------------------

//reset vars array
function dfwiki_parser_reset_vars(){
    global $WS;
	$WS->parser_vars = array();
    return true;
}
//reset log array
function dfwiki_parser_reset_logs(){
    global $WS;
    $WS->parser_logs = array();
    return true;
}
//reset sintax
function dfwiki_parser_reset_sintax(){
    global $WS;
    $WS->parser_format = array();
    return true;
}


//convert wiki content to html content
function dfwiki_parse_text($text,$format){
    global $WS,$CFG;

    //importar l'array del format
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
    	//si no �s un array, suposem que �s la funci� que ho far�
    	if (!is_array($WS->parser_format['no-parse'])) return $WS->parser_format['no-parse']($text);

    	$noparsetext = array();

    	foreach ($WS->parser_format['no-parse'] as $par){
    		//text sense els talls de no-parse
    		$before = '';
    		//text encara amb els talls de no-parse
    		$after = $text;
    		//marques d'inici i final
    		$startmark = $par->marks[0];
    		$endmark = $par->marks[1];
    		$endmark_len = strlen($endmark);
    		$startmark_len = strlen($startmark);

    		//inicialitzem els talls d'aquest text que no es parseja
    		$noparsetext[$startmark] = array();

    		//l'index l'usem per no dependre d'un foreach
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
    		//guardem $text sense els talls no-parse
    		$text = $before;

    	}

    	//aqu� parsejar�em el text
    	$text = dfwiki_parser_inlines($text);

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
    	//no est� definit que no hi hagi text wiki per tant es passa a tot el text
    	$res = dfwiki_parser_inlines($text);
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
function dfwiki_parser_inlines (&$text){
    global $WS;

    //LINE-DEFINITION
    if (!isset($WS->parser_format['line-definition'])) $WS->parser_format['line-definition']->marks = "\r\n";

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
    	$res.= dfwiki_parser_line($line);
    	//new paragraf
    	if(chop($line)==''){
    		$res.='<br /><br />';
    	}
    	$res.="\n";
    }
    //cheat!!! this line end all opened tables and lists
    $line='';
    $res.= dfwiki_parser_line($line);

    return $res;
}

//private function to convert a line in fomrat wiki into html
function dfwiki_parser_line(&$line){

    //la sint�xis est� a la ewiki.php entre 1841 i 2035

    global $WS;

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
    			if (!isset($par->func)) $par->func = 'dfwiki_parser_default_encapsule';

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
    			if (!isset($par->func)) $par->func = 'dfwiki_parser_default_encapsule';

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

    				if (!isset($par->func)) $par->func = 'dfwiki_parser_default_header';
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

    //LINE-COUNT-START (conta les vegades que apareix)
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
    			if (!isset($par->func)) $par->func = 'dfwiki_parser_default_repeat_before';
    			$func = $par->func;
    			$part = (isset($par->subs))? $func($inside,$max,$par->marks,$par->subs) : $func($inside,$max,$par->marks);
    			$res = $part;
    			unset ($part);
    		} else {
    			//executem la elsefunc
    			if (isset($par->elsefunc)) {
    				$func = $par->elsefunc;
    				$part = (isset($par->subs))? $func($res,0,$par->marks,$par->subs) : $func($res,0,$par->marks);
    				$res = $part;
    			}
    		}
    	}
    	unset ($chopline);
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
    			//si no tneim funci� posem la de encerclar
    			if (!isset($par->func)) $par->func = 'dfwiki_parser_default_encapsule';

    			$func = $par->func;
    			$part = (isset($par->subs))? $func($inside,$par->marks,$par->subs) : $func($inside,$par->marks);
    			$res = $before.$part.$after;
    			unset ($part);
    		}
    	}
    }


    //LINE-ARRAY-DEFINITION
    if (isset($WS->parser_format['line-array-definition'])){
    	foreach ($WS->parser_format['line-array-definition'] as $par){
    		$chopline = chop($line);
    		$mark_len = strlen($par->marks);
    		if((strpos($chopline, $par->marks)=== 0) && (strrpos($chopline, $par->marks)) === strlen ($chopline)-1) {
    			$inside = explode ($par->marks,substr($res, $mark_len,  strrpos($res, $par->marks) - $mark_len));

    			if (!isset($par->func)) $par->func = 'dfwiki_parser_default_array_encapsule';
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
    			//si no tneim funci� posem la de substitu�r
    			if (!isset($par->func)) $par->func = 'dfwiki_parser_default_substitution';

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
    		//simplement executem la funci�
    		if (isset($par->func)){
    			$func = $par->func;
    			$res = $func($res);
    		}
    	}
    }

    return $res;
}

//auxiliar functions that will be modificated ---------------

function dfwiki_moodle_format_text(&$text){
    return format_text($text, FORMAT_MARKDOWN);
}

//identity function, returns the text in the same way
function dfwiki_parser_default_identity (&$line,$p1='',$p2='',$p3='',$p4=''){
    echo '-&gt;identityt<br />';
    return $line;
}

function dfwiki_parser_default_internal_link (&$line,$marks){
    return dfwiki_sintax_create_internal_link ($line);
}

function dfwiki_parser_default_external_link (&$line,$marks){
    return dfwiki_sintax_create_external_link ($line);
}

function dfwiki_parser_default_encapsule (&$line,$marks,$subs=false){
    if ($subs === false) $subs = array('','');
    return $subs[0].$line.$subs[1];
}

function dfwiki_parser_default_substitution ($text,$marks,$subs=false){
    if ($subs===false) $subs = '';
    if (is_array($subs)) $subs = implode('',$subs);
    return $subs;
}

function dfwiki_parser_default_header (&$line,$marks,$subs=false){
    global $WS;

    if ($subs===false) $subs = array ('','');
    if (!is_array($subs)) $subs = array ($subs,$subs);

    //import parser_vars
    if (!isset($WS->parser_vars['header'])){
    	//if we are inside group
    	$WS->parser_vars['header'] = 1;
    }

    //create anchor label
    $anchor = 'toc'.$WS->parser_vars['header'];
    $WS->parser_vars['header']++;

    //save header into log
    if (!isset($WS->parser_logs['toc'])) $WS->parser_logs['toc'] = array();
    $WS->parser_logs['toc'][] = array ($subs[0],$anchor,$line);

    //add anchors to $subs
    $subs = array ('<a name="'.$anchor.'"></a>'.$subs[0] , $subs[1]);

    //encapsule header:
    $res = dfwiki_parser_default_encapsule ($line,$marks,$subs);
    return $res;
}

//puts ahead line a number $max of times the value of $subs
function dfwiki_parser_default_repeat_before ($line,$max,$marks,$subs=''){
    $res='';
    for ($i=0;$i<$max;$i++){
    	$res.=$subs;
    }
    $res.=$line;
    return $res;
}

//encapsulates an array
function dfwiki_parser_default_array_encapsule ($line,$marks,$subs = false){
    if ($subs === false) $subs = array ('','');
    if (!is_array($line)) $line = array ($line);
    $res = '';
    foreach ($line as $part){
    	$res.= dfwiki_parser_default_encapsule ($part,'',$subs);
    }
    return $res;
}

function dfwiki_parser_default_toc (&$text){
    global $WS;

    if (!isset($WS->parser_logs['toc'])) return $text;

    $res='<table align="center" width="95%"  class="generalbox bordarkgrey" border="0" cellpadding="0" cellspacing="0">
    				<tr><td bgcolor="#EEEEEE">';

    $num = array(0,0,0);
    foreach ($WS->parser_logs['toc'] as $header){

    	//get level
    	$lev = substr ($header[0],2,1);
    	//set $numt (number text)
    	if ($lev<2) $num[2]=0;
    	if ($lev<3) $num[1]=0;
    	$num[$lev-1]++;

    	$numt = '';
    	for ($i=0;$i<$lev;$i++){
    		$res.='&nbsp;&nbsp;&nbsp;';
    		$numt.= $num[$i].'.';
    	}
    	$res.= '<a href="#'.$header[1].'">'.$numt.' '.$header[2].'</a><br>';
    }
    $res.= "</td></tr></table>\n".$text;
    return $res;
}

//encapsulates a group of lines
function dfwiki_parser_default_open_group ($line,$p1='',$p2='',$p3=false){
    global $WS;
    //if we have 4 parameters, we can ignore the second
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
    	//tells if we are inside the group
    	$WS->parser_vars['enc_group'][$marks] = false;
    }

    $res = '';
    //if it's necessary, start the group
    if (!$WS->parser_vars['enc_group'][$marks]){
    	$res = $subs[0];
    	$WS->parser_vars['enc_group'][$marks] = true;
    }
    //if there is a mark access, we repeat them
    if (isset($num)){
    	for ($i=0;$i<$num-1;$i++){
    		$res.= $marks;
    	}
    }
    //put the line
    $res.= $line;

    return $res;
}

//encapsulates a group of lines
function dfwiki_parser_default_close_group ($line,$p1='',$p2='',$p3=false){
    global $WS;
    
    //if we have 4 parameters, we can ignore the second
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
    	//tells if we are inside the group
    	$WS->parser_vars['enc_group'][$marks] = false;
    }

    $res = '';
    //if it's necessary, start the group
    if ($WS->parser_vars['enc_group'][$marks]){
    	$res = $subs[1];
    	$WS->parser_vars['enc_group'][$marks] = false;
    }
    //put the line
    $res.= $line;

    return $res;
}

function dfwiki_parser_default_table ($parts,$marks,$subs='') {
    global $WS;
    //initialize parse_vars[list]
    if (!isset($WS->parser_vars['table'])){
    	$WS->parser_vars['table'] = array();
    	$WS->parser_vars['table']['find'] = array();
    	$WS->parser_vars['table']['num'] = 0;
    	//tells if we are inside a table
    	$WS->parser_vars['table']['intable'] = false;
    }

    if (!is_array($subs)) $subs = array ($subs,$subs);

    //if the type isn't saved, save it
    if (!in_array($marks,$WS->parser_vars['table']['find'])){
    	$WS->parser_vars['table']['find'][] = $marks;
    }

    //if we are at first symbol, put num = 0
    if ($WS->parser_vars['table']['find'][0] == $marks){
    	$WS->parser_vars['table']['num'] = 0;
    }

    //look if we have a table inside the line
    if (!is_array($parts)){
    	if ($WS->parser_vars['table']['num'] > -1) $WS->parser_vars['table']['num']++;
    } else {
    	$WS->parser_vars['table']['num'] = -1;
    }

    $res = '';
    //if num == -1 we've finded a line, if num == -2 we've written it
    if ($WS->parser_vars['table']['num'] < 0){
    	//open table if it's necessary
    	if ($WS->parser_vars['table']['num'] == -1){
    		if (!$WS->parser_vars['table']['intable']){
    			$res = '<table align="center" width="80%"  class="generalbox" border="0" cellpadding="0" cellspacing="0">
    				<tr><td bgcolor="#ffffff" class="generalboxcontent">
    				<table width="100%" border="0" align="center"  cellpadding="5" cellspacing="1" class="generaltable" >';
    			$WS->parser_vars['table']['intable'] = true;
    		}

    		//write the line
    		$res.='<tr>';
    		$res.=dfwiki_parser_default_array_encapsule ($parts,$marks,$subs);
    		$res.='</tr>';
    		//we've written the line
    		$WS->parser_vars['table']['num']=-2;
    	} else {
    		//return the string if it's written
    		$res = (is_array($parts)) ? implode ('',$parts) : $parts;
    	}
    } else{
    	//if we have an equal num that the types we have, we know that we aren't at any table
    	if (count ($WS->parser_vars['table']['find']) == $WS->parser_vars['table']['num']){
    		//close the table if it's necessary
    		if ($WS->parser_vars['table']['intable']){
    			$res = '</table></td></tr></table>';
    			$WS->parser_vars['table']['intable'] = false;
    		}
    		//put the part.
    		$res.= $parts;
    	}else{
    		//the search continues
    		$res = $parts;
    	}
    }
    return $res;
}

function dfwiki_parser_default_list ($inside,$max,$marks,$subs) {
	global $WS;
    //initialize parse_vars[list]
    if (!isset($WS->parser_vars['list'])){
    	$WS->parser_vars['list'] = array();
    	$WS->parser_vars['list']['find'] = array();
    	$WS->parser_vars['list']['num'] = 0;
    	//tells what is the last level to insert
    	$WS->parser_vars['list']['lastlevel'] = 0;
    	//tells the level type that is open
    	$WS->parser_vars['list']['listtype'] = array();
    	//label for the elements
    	$WS->parser_vars['list']['listelem'] = array();
    }

    if (!is_array($subs)) $subs = array ($subs,$subs);

    //if the type isn't saved, we save it
    if (!in_array($marks,$WS->parser_vars['list']['find'])){
    	$WS->parser_vars['list']['find'][] = $marks;
    }

    //if we are at first symbol, put num = 0
    if ($WS->parser_vars['list']['find'][0] == $marks){
    	$WS->parser_vars['list']['num'] = 0;
    }

    //detect if there is a table inside the line
    if ($max==0){
    	if ($WS->parser_vars['list']['num'] > -1) $WS->parser_vars['list']['num']++;
    } else {
    	$WS->parser_vars['list']['num'] = -1;
    }

    $res = '';
    //if num == -1 we've finded a line, if num == -2 the line has been written
    if ($WS->parser_vars['list']['num'] < 0){
    	//if it's necessary, open the table
    	if ($WS->parser_vars['list']['num'] == -1){

    		//open to the level
    		if ($WS->parser_vars['list']['lastlevel'] != $max){
    			//we must open or close levels
    			$inc = ($WS->parser_vars['list']['lastlevel']>$max)? -1 : 1;
    			while ($WS->parser_vars['list']['lastlevel'] != $max){
    				if ($inc<0){
    					//tanquem
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
    		//the line has been written
    		$WS->parser_vars['list']['num']=-2;
    	} else {
    		//if the line has been written, return the string
    		$res = $inside;
    	}
    } else{
    	//if we have an equal num that the types we have, we know that we aren't at any table
    	if (count ($WS->parser_vars['list']['find']) == $WS->parser_vars['list']['num']){
    		//close all the levels
    		if ($WS->parser_vars['list']['lastlevel'] > 0){
    			//we must open or close levels
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
function dfwiki_sintax_create_internal_link (&$linktext) {
    global $WS;

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
    			$linkpage = $parts[0];
    			$linktext = $parts[0];
    		}else{
    			$linkpage = $parts[0];
    			$linktext = $parts[1];
    		}

    		//built url
    		if (dfwiki_page_exists($linkpage)){
    			//if the page already exists
    			$res = '<a href="view.php?id='.$WS->cm->id.'&amp;page='.$linkpage.'">'.$linktext.'</a>';
    		}else{
    			//to create the page
    			$res = '<b><u>'.$linktext.'</u></b><a href="view.php?id='.$WS->cm->id.'&amp;page='.$linkpage.'">?</a>';
    		}

    		//save link into log
    		if (!isset($WS->parser_logs['internal'])) $WS->parser_logs['internal'] = array();
    		if (!in_array($linkpage,$WS->parser_logs['internal'])) $WS->parser_logs['internal'][] = $linkpage;

    		break;


    	case 'user':
    		$res = dfwiki_get_user_info ($linkname,25);
    		break;

    	case 'attach':
    		$res = '['.dfwiki_upload_url($linkname).' '.$linkname.']';
    		break;
    	default: //error
    }

    return $res;
}

//this function creates de url to a link
function dfwiki_sintax_create_external_link (&$linktext) {

    $res = '';

    //if text doesn't start with http://, return the internal link.

    if (stripos ($linktext,'http://')!==0 && stripos ($linktext,'https://')!==0) {
    	return dfwiki_sintax_create_internal_link ($linktext);
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
    	if (in_array($extension,$ext)){
    		$type = $typ;
    	}
    }


    switch ($type){
    	case 'image':
    		$res = '<img src="'.$linkurl.'" alt="'.$linkname.'" />';
    		break;
    	case 'flash':
    		//get size from $link name
    		$parts = explode(' ',$linkname);

    		if (count($parts)!=2){
    			echo 'mal<hr />';
    			$parts = array ('320','240');
    		} else {
    			$parts = array (trim($parts[0]),trim($parts[1]));
    			if (strlen($parts[0])!=strspn($parts[0], '0123456789') && strlen($parts[1])!=strspn($parts[1], '0123456789')){
    				echo 'mal2 '.strlen($parts[0]).' '.strspn($parts[0], '0123456789').'<hr />';
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

// -------------------------------------- FIND WIKI LINKS IN A PAGE ---------------------------------------

//this function returns all the internal links in a page content.
//@return an array of pagenames
function dfwiki_sintax_find_internal_links ($text){
    global $WS;
    dfwiki_parser_reset_vars();
    dfwiki_parser_reset_logs();
    dfwiki_parser_reset_sintax();
    $res = dfwiki_parse_text ($text,'links');
    if (!isset($WS->parser_logs['internal'])) $WS->parser_logs['internal'] = array();
    if (!is_array($WS->parser_logs['internal'])) $WS->parser_logs['internal'] = array();
    return $WS->parser_logs['internal'];
}

?>
