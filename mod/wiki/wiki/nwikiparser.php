<?php
/*
 * Parser for the nwiki.  Will accept the 
 * mediawiki sintax.
 * Created by Didac Calventus at dfwikiteam
 * based on a parser library created by Ferran Recio
 * for Bluew Web and DFwiki
 * This parser is distributed under GPL licence
 */
require_once($CFG->dirroot."/lib/weblib.php");		// required to replace smilies  
require_once($CFG->dirroot."/lib/locallib.php");  
require_once($CFG->dirroot."/mod/wiki/locallib.php");

$regex = array();
$tocheaders = array();		// will use this to create the TOC
$references = array();		// will use this to create the reference list
$nreference=-1;
$nowikitext=array();
$nowikicounter=0;
$imgextension=array( '.jpg', '.png', '.bmp', '.jpeg', '.tiff', '.gif', '.jp2', '.jpc', '.xbm', '.wbmp');

// direct transformations
$regex['replace'] = array (
	'/{{wikibook:.*?}}/' => '',
	"/'''''(.*?)'''''/"  => '<b><i>\\1</i></b>',
	"/'''(.*?)'''/"      => '<b>\\1</b>',
	"/''(.*?)''/"        => '<i>\\1</i>', 
	"/\*\*(.*?)\*\*/"    => '<b>\\1</b>', // creole syntax
//	"/\/\/(.*?)\/\//"    => '<i>\\1</i>', // creole syntax   @TODO: Bug!! It breaks attachments
	'/^-{3,4}/m'         => "</p><hr /><p>",
	'/^ (.*?)$/m'        => '</p><pre class="quote">\\1</pre><p>',
	'/^%%%/m'            => '<br />', // creole syntax
	'/\\\\\\\\/'         => '<br />', // creole syntax
);

// non direct transformations
$regex['variable'] = array (
    '/^=+(.*?)=+/m'              => 'parse_header',
    '/(\[\[Image:)(.*?)\]\]/'    => 'parse_image',
    '/\{\{(<<ThisIsThePlaceOfTheNoWikiText-\d+>>)(\|(.*?))?\}\}/'
                                 => 'parse_creole_image',
    '/(\[\[attach:)(.*?)\]\]/'   => 'parse_attach',
    '/(\[|\[\[)(<<ThisIsThePlaceOfTheNoWikiText-\d+>>)(\ (.*?))?(\|(.*?))?(\]\]|\])/'
                                 => 'parse_external_link',
    '/\[\[#(.*?)\]\]/'           => 'parse_anchor',
    '/\[\[(.*?)\]\]/'            => 'parse_internal_link',
    '/^\{\|(.*?)\|\}/sm'         => 'parse_table',
    '/(^\|[^\n]*\|\n)+/sm'       => 'parse_creole_table',
    '/(^(\*|#|:)+.*?(\n|$))+/sm' => 'parse_list',
    '/(^;.*?:.*?(\n|$))+/sm'     => 'parse_definition_list',
    '/~{5}/'                     => 'parse_datetime',
    '/~{4}/'                     => 'parse_username_datetime',
    '/~{3}/'                     => 'parse_username',
    '/<ref name="(.*?)"\/?>((.*?)<\/ref>)?/'  
                                 => 'parse_references',
    '/<references\/>/'           => 'parse_reference_list',
);

$regex['nowiki'] = array (
	"/<nowiki>(.*?)<\/nowiki>/s" => 'parse_nowiki',
	"/\{\{\{(.*?)\}\}\}/s" => 'parse_nowiki',
	"/((http|https|ftp):\/\/[\w!~*'\(\).;?:@&=+$,%#-\/]+)/" => 'parse_nowiki',
);

function parse_nwiki_text($text) {
	global $regex, $nowikitext, $USER, $tocheaders, $WS;

	// Security Patch !!!
	// $find will contain the regex to find
    // $find   = array_keys($regex['than']);
    // $replace will contain the replacements of items in $find
    // $replace = array_values($regex['than']);
    // $text    = preg_replace($find, $replace, $text);

    $section    = optional_param('section',   '', PARAM_TEXT);
    $sectionnum = optional_param('sectionnum', 0, PARAM_INTEGER);
    $sectionhash= optional_param('sectionhash', '', PARAM_TEXT);
    $preview    = optional_param('dfformpreview', NULL, PARAM_TEXT);
    $action     = optional_param('dfformaction', NULL, PARAM_TEXT);

    if (!(($section == '') && ($sectionnum == 0)) && // if it's a partial/section viewing and...
         ($preview == '')  && ($action == ''))       // ...we are not in preview mode
    {
        // check if the section exists or it has been deleted
        $section = stripslashes($section);
        $section_positions = wiki_get_section_positions($text, $section);
        if (!in_array($sectionnum, $section_positions)) {
            $num_positions = count($section_positions);
            if ($num_positions == 0) { // section has been deleted
                $error_text  = get_string('sectionerror', 'wiki', $section).': '.strtolower(get_string('sectiondeleted', 'wiki'));
                error($error_text);
            } elseif ($num_positions == 1) { // section position has changed
                $sectionnum = $section_positions[0];
            } else { // there are more than one section with this name
                $error_text  = get_string('sectionerror', 'wiki', $section).': '.strtolower(get_string('sectionmultiple', 'wiki')).': ';
                if (isset($WS->dfcourse)) $type = 'course';
                else $type = 'mod';
                $urls = wiki_view_page_url($WS->page, $section, 2, '', $type);
                $num_urls = count($urls);
                for ($i = 0; $i < $num_urls; $i++) {
                    if ($i == 0)
                        $error_text .= '<a href="'.$urls[$i].'">[['.$WS->page.'##'.$section.'</a>';
                    elseif ($i == ($num_urls - 1))
                        $error_text .= ', <a href="'.$urls[$i].'">'.($i + 1).']]</a>';
                    else
                        $error_text .= ', <a href="'.$urls[$i].'">'.($i + 1).'</a>';
                }
                error($error_text);
            }
        }

        $res = wiki_split_sections($text, $section, $sectionnum);
        if ($res->error != '') {
            $text = $res->error;
        }
        else 
            $text = $res->current_part;
    }

	// Remove carriage returns
	$text = str_replace("\r", "", $text);
	foreach($regex['nowiki'] as $match => $func){
		$text=preg_replace_callback($match, $func, $text);
	}

	foreach($regex['variable'] as $match => $func){
		$text=preg_replace_callback($match, $func, $text);
	}

	// $find will contain the regex to find
	$find = array_keys($regex['replace']);
	// $replace will contain the replacements of items in $find
	$replace = array_values($regex['replace']);

	$text2 =  preg_replace($find, $replace, $text);
				
	
	foreach($nowikitext as $index => $singlenowikitext){	
		$text2 = preg_replace("/<<ThisIsThePlaceOfTheNoWikiText-{$index}>>/", $singlenowikitext, $text2);
	}

	// here comes the TOC, search for --TOC-- and include the real TOC
    $toc=parse_toc($tocheaders);
    $text2=str_replace("NWikiTableOfContents", $toc, $text2);
	
	$text2 = preg_replace('/^\n/m', '</p><p>', $text2);
	
	// remove empty paragraphs
	$text2 = preg_replace('/<p>\s*<\/p>/s', '', "<p>$text2</p>");
	
	// join consecutive pre blocks
	$text2 = preg_replace('/<\/pre>\s*<pre class="quote">/s', '<br />', $text2);
	
    $options->para=false;
	return format_text($text2,FORMAT_HTML,$options);
	//return $text2;
}

function parse_username($matches) {
	$wikimanager = wiki_manager_get_instance();	
	$page = wiki_param('pagedata');

	$author = $wikimanager->get_user_info($page->author);

	return $author->firstname." ".$author->lastname;
}
function parse_username_datetime($matches) {
	$wikimanager = wiki_manager_get_instance();	
	$page = wiki_param('pagedata');

	$author = $wikimanager->get_user_info($page->author);
	
	return $author->firstname." ". $author->lastname.", ".strftime('%x')." - ".strftime('%X');
}
function parse_datetime($matches) {
	return strftime('%x')." - ".strftime('%X');
}
function parse_nowiki($matches) {
	global $nowikitext, $nowikicounter;

	$parsednowiki=preg_replace("/</","&lt;", $matches[1]);
	$parsednowiki=preg_replace("/>/","&gt;", $parsednowiki);
	$parsednowiki=preg_replace("/\n /","<br />&nbsp;", $parsednowiki);
	$parsednowiki=preg_replace("/\n/","<br />\n", $parsednowiki);
	$nowikitext[$nowikicounter]=$parsednowiki;
	$res="<<ThisIsThePlaceOfTheNoWikiText-$nowikicounter>>";
	$nowikicounter++;
	return $res;
}


function parse_header($matches) {
	global $tocheaders, $hindex;
    global $WS;
	
	$parsed_header = "";
	// the first header will be preceeded by the TOC
	if (!$tocheaders) $parsed_header .= "NWikiTableOfContents\n";
	$parsed_header .= "</p>";
	
    // $matches[0] contains the full expresion
    // $matches[1] contains the expresion without the tags
	$cat = (strlen($matches[0]) - strlen($matches[1])) / 2;
	//the header includes an anchor, to come from the TOC
	$headername = trim($matches[1]);

    // add the header to the TOC
    $page       = wiki_page_last_version($WS->page);
    $headernum  = sizeof($tocheaders) + 1;
	$tocheaders[] = array($cat, $headername, $headernum);

	$elem = ($cat <= 6) ? "h$cat" : "h6";

    //$parsed_header .= "<$elem class=\"nwiki\" id=\"$headerid\">"; 
    
    // add an edit link in the section header
    $parsed_header .= "<$elem class=\"nwiki\" id=\"$headernum\">"; 
    $preview = optional_param('dfformpreview', '', PARAM_TEXT);
    $action  = optional_param('dfformaction', '', PARAM_TEXT);
    $hash    = wiki_get_section_hash($WS->pagedata->content, $headernum);

    // if we are in discussion pages don't add the edit link
    if (substr($WS->page, 0, strlen('discussion:')) == 'discussion:') {
        $parsed_header .= $headername."</$elem>".'<p>';
        return $parsed_header;
    }

    // if we don't have permissions to edit (different group / student page)
    // forbid editing too
    if (!($WS->dfperms['edit'])) {
        $parsed_header .= $headername."</$elem>".'<p>';
        return $parsed_header;
    }

    // create the header edit links
    $section    = optional_param('section',   '', PARAM_TEXT);
    $sectionnum = optional_param('sectionnum', 0, PARAM_INTEGER);
    if (($section == '') && ($sectionnum == 0) && ($preview == '')) { // main page
        $parsed_header .= '<span class="nwikieditsection">[<a href="view.php?id='.$WS->linkid.'&amp;uid='.$WS->member->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;page=edit/'.urlencode($WS->page).'&amp;editor='.$WS->pagedata->editor.'&amp;section='.urlencode($headername).'&amp;sectionnum='.$headernum.'&amp;sectionhash='.$hash.'">edit</a>]</span>';
    } else {
        if (($action == 'edit') && ($preview == '')) {
            $parsed_header .= '<span class="nwikieditsection">[<a href="view.php?id='.$WS->linkid.'&amp;uid='.$WS->member->id.'&amp;gid='.$WS->groupmember->groupid.'&amp;page=edit/'.urlencode($WS->page).'&amp;editor='.$WS->pagedata->editor.'&amp;section='.urlencode($headername).'&amp;sectionnum='.$headernum.'&amp;sectionhash='.$hash.'">edit</a>]</span>';
        }
    }

    $parsed_header .= $headername."</$elem>".'<p>';

	return $parsed_header;
}

function parse_toc($tocheaders) {
	$toc = "<div class=\"toc\">\n" . get_string('toc','wiki');
	$lastlevel = 0;
	$levels = array();
	foreach ($tocheaders as $tocline) {
		while ($tocline[0] < $lastlevel) {
			$lastlevel = array_pop($levels);
			$toc .= "</li></ol>";
		}
		if ($tocline[0] > $lastlevel) {
			array_push($levels, $lastlevel);
			$lastlevel = $tocline[0];
			$toc .= "<ol><li>";
		}
		$toc .= "</li><li><a href=\"#" . $tocline[2] . "\">" . $tocline[1] . "</a>";
	}
	// Remove introduced empty items
	$toc = str_replace("<li></li>", "", $toc);
	$toc = str_replace("</ol><ol>", "", $toc);
	return "</p>" . $toc . str_repeat("</li></ol>", count($levels)) . "</div><p>";
}


function parse_internal_link($matches) {
	global $WS, $CFG, $COURSE;
                
    // allow spaces before and after the internal link name
    $matches[1] = trim($matches[1]);
    $matches[1] = wiki_clean_name($matches[1]);

	$parts  = explode('|', $matches[1]);
	$target = $parts[0];
	$label  = $target;
	if (count($parts) > 1) {
		$label = $parts[1];
	}
	
    // internal links to sections 
    $page       = $target;
    $anchor     = '';
    $anchortype = 0;
    $pos        = strpos($target, '#');
    if ($pos > 0)
    {
        $page = substr($target, 0, $pos);
        if (substr($target, $pos + 1, 1) == "#")
        {
            // [[page##section]]: partial view of the section
            $anchortype = 2;
            $anchor = substr($target, $pos + 2, strlen($target) - $pos);
        } else {
            // [[page#section]]: page view plus scroll to section
            $anchortype = 1;
            $anchor = substr($target, $pos + 1, strlen($target) - $pos);
        }
    }
	
    // wikibook
	$wikibookname = '';
	if (count($parts) > 2 && preg_match("/^wikibook:(.*)/", $parts[2], $match)) {
		$wikibookname = $match[1];
	}
	$wikibookparam = '';
	if ($wikibookname) {
		$wikibookparam = '&amp;wikibook=' . urlencode($wikibookname);
	}

	// we can have more than one section with the same name, so we link
	// to all of them in a list after the link.
    $res      = '';
    $urls     = wiki_view_page_url($page, $anchor, $anchortype, $wikibookname);
    $num_urls = count($urls);

	for ($i = 0; $i < $num_urls; $i++) {
	    if ($i == 0) // we render the first matched section as a normal link
	        $res  = '<a href="'.$urls[$i].'">'.$label.'</a>';
	    elseif ($i == 1)
	        $res .= ' [<a href="'.$urls[$i].'">'.($i + 1).'</a>';
	    else
	        $res .= ', <a href="'.$urls[$i].'">'.($i + 1).'</a>';
	
	    if (($i == ($num_urls -1)) && ($i > 0)) $res .= ']';
	}
	
    if (!wiki_page_exists($WS, $page, true)) {
        $res = '<span class="nwikiwanted">'.$res.'</span>';
	} 
    else if ($anchor != '') 
    {
        if (!wiki_section_exists($page, $anchor))
            $res = '<span class="nwikiwanted">'.$res.'</span>';
    }

    return $res;	
}	

/*
 * Parsing anchors 
 */
function parse_anchor($matches) {
	
	$parts=explode("|", $matches[1]);
	$target=$parts[0];
	if(count($parts)==2){
		$label=$parts[1];
	}else{
		$label=$target;
	}
	$res="<a href=\"#a". bin2hex($target)."\">".$label."</a>";
	return $res;  
}


/*
 * Parsing external links 
 */
function parse_external_link($matches) {
	$url = $matches[2];
	$label = ($matches[4] != '') ? $matches[4] : $url;
	return '<a href="' . $url . '" title="' . $label . '" >' . $label . '</a>';
}

function parse_list($matches) {
	$elem_map = array("#" => "ol", "*" => "ul", ":" => "dl");
	$item_map = array("#" => "li", "*" => "li", ":" => "dd");
	
	$html = "";
	$lastlevel = 0;
	$items = array();
	$elems = array();
	
	foreach (explode("\n", $matches[0]) as $item) {
		if (preg_match("/^(\*+|#+|:+)(.*)/", $item, $match)) {
			$elem = $elem_map[substr($match[1], 0, 1)];
			$item = $item_map[substr($match[1], 0, 1)];
			$level = strlen($match[1]);
			
			for (; $level > $lastlevel; $lastlevel++) {
				$html .=  "<$elem><$item>";
				array_push($elems, $elem);
				array_push($items, $item);
			}
			
			for (; $level < $lastlevel; $lastlevel--) {
				$html .=  "</" . array_pop($items) ."></" . array_pop($elems) . ">";
			}
			
			$html .= "</" . array_pop($items) . ">";

			$lastelem = array_pop($elems);
			if ($lastelem != $elem) {
				$html .= "</$lastelem><$elem>";
			}
			array_push($elems, $elem);
			
			$html .= "<$item>" . $match[2];
			array_push($items, $item);
		}
	}
	
	while (count($elems)) {
		$html .=  "</" . array_pop($items) . "></" . array_pop($elems) . ">";
	}
	
	// Remove introduced empty items
	foreach (array_values($item_map) as $item)
		$html = str_replace("<$item></$item>", "", $html);
	
	return "</p>$html<p>";
}

function parse_definition_list($matches) {
	$html = '<dl>';
	foreach(explode("\n", $matches[0]) as $line){
		if (preg_match('/^;(.*?):(.*?)$/', $line, $match)) {
			$html .= "<dt>{$match[1]}</dt><dd>{$match[2]}</dd>";
		}
	}
	return "</p>$html</dl><p>";	
}

/* *************************************
 *		REFERENCES & FOOTNOTES
 ************************************* */
function parse_references($matches){
	global $references, $nreference;
	$res="";
	$refname=$matches[1];
	if(count($matches)>3){
		$reftext=$matches[3];
	}else{
		$reftext="";
	}

	$nreference++;
	$references[$nreference]= array($refname, $reftext);
	//
	$res="<sup id=\"font$refname\"><a href=\"#target$refname\">$nreference</a></sup>";
		
	return $res;
}

function parse_reference_list($matches){
	global $references, $nreference;
	$res="";
	$reflist="<ul>";
	
	$counter=0;
	foreach($references as $ref){
		$refname=$ref[0];
		$reftext=$ref[1];
		$reflist.="<li id=\"target$refname\">$reftext <a href=\"#font$refname\"><sup>$counter</sup></a></li>";
		$counter++;
	}
	$reflist .= "</ul>";
	$res="<div class=\"references\">$reflist</div>";
			
	return "</p>$res<p>";
}

/* *************************************
 *  	FUNCTION TO PARSE TABLES
 ************************************* */
function parse_table($matches) {
	$lines = explode("\n", $matches[1]);
	$style = $lines[0];
	
	$elements = array();
	foreach (array_slice($lines, 1) as $line) {
		$line = trim($line);
		$element = Null;
		if (preg_match("/^\|\+(.*)/", $line, $matches)) {
			$element->type = 'caption';
			$element->content = $matches[1];
			array_push($elements, $element); 
		} else if (preg_match("/^\|-(.*)/", $line, $matches)) {
			$element->type = 'row';
			$element->content = "";
			array_push($elements, $element);
		} else if (preg_match("/^\|(.*)/", $line, $matches)) {
			$element->type = 'cells';
			$element->content = $matches[1];
			array_push($elements, $element);
		} else if (preg_match("/^!(.*)/", $line, $matches)) {
			$element->type = 'headings';
			$element->content = $matches[1];
			array_push($elements, $element);
		} else if ($elements) {
			$elements[count($elements)-1]->content .= "\n$line";
		}
	}
	
	$caption = "";
	$rows = array("");
	foreach ($elements as $element) {
		if ($element->type == 'caption') {
			$caption = $element->content;
		} else if ($element->type == 'row') {
			array_push($rows, "");
		} else {
			$sep = ($element->type == 'cells') ? '|' : '!';
			$elt = ($element->type == 'cells') ? 'td' : 'th';
			$cells = explode($sep . $sep, $element->content);
			foreach ($cells as $cell) {
				$cell = strpos($cell, $sep) ? explode($sep, $cell, 2) : array("", $cell);	
				$cell = "<$elt {$cell[0]}>{$cell[1]}</$elt>";
				$rows[count($rows)-1] .= $cell;
			}
		}
	}
	
	$html = "</p>";
	
	// Moodle's format_text remove caption element, so we print it as a p element.
	if ($caption) {
		$html .= "<p>$caption</p>";
	}
	
	$html .= "<table class=\"nwikitable\" $style>";
	
	foreach ($rows as $row) {
		if ($row) {
			$html .= "<tr>$row</tr>";
		}
	}
	$html .= "</table><p>";
	
	return $html;
}

// Table in Creole syntax
function parse_creole_table($matches) {
	$res = '</p><table>';
	preg_match_all('/^\|(.*?(\|.*?)*)\|/m', $matches[0], $matches);
	foreach ($matches[1] as $row) {
		$res .= '<tr>';
		foreach (explode('|', $row) as $cell) {
			$res .= ($cell[0] == '=')
				? '<th>' . substr($cell, 1) . '</th>'
				: '<td>' . $cell . '</td>';
		}
		$res .= '</tr>';
	}
	$res .= "</table><p>";
	return $res;
}

/* **************************************************************************

//	A function to parse images properly
//	mediawiki syntax for images:
//	[[Image:{name}|{type}|{location}|{size}|{caption}]]
//	type:		thumb/thumbnail, frame
//	Location:	right, left, center, none
//	Size:		WIDTHpx or WIDTHxHEIGHTpx -> ([0-9]px|[0-9]x[0-9]px)
//	Caption:	any text not identified as one of above.	
//				if the image syntax finish with this: "|]]" caption is not showed
//	Order doesn't matter...

// [[Image:Westminstpalace.jpg|thumb|This text is displayed.|70px|right]]
// <a href="/wiki/Image:Westminstpalace.jpg" title="This text is displayed."><img src="http://upload.wikimedia.org/wikipedia/commons/thumb/3/39/Westminstpalace.jpg/70px-Westminstpalace.jpg" alt="This text is displayed." width="70" height="53" longdesc="/wiki/Image:Westminstpalace.jpg" /></a>

*/

/*
 *	This function is for the external images.
 */
function parse_image($matches) {
	global $WS, $CFG, $USER; 
	// the path of the image must be given on the name.
	$alt="";
	$captiontext="";
	$parsedimage="";
	$divclass="";

		// $fields is an array of the fields of the images in mediawiki syntax
		$fields = explode('|', $matches[2]);
		// Let's find which fields have the image	
		$num=count($fields);
		// fields[0] is the full link to the external image
		$imagefullname=$fields[0];
		// let's extract the real name of the image: *.jpg, *.png ...

		$aux=strrchr($imagefullname, "/");
		$imagename=substr($aux, 1, strlen($aux));
		
		$parsedimage="<a href=\"$imagefullname\" title=\"$imagename\" ><img src=\"$imagefullname\" ";		
		foreach ($fields as $field){
			
			if(!strcmp($field,"left")){
				$divclass.=$field;
				$position=$field;
			}
			elseif(!strcmp($field,"right")){
				$divclass.=$field;
				$position=$field;
			}
			elseif(!strcmp($field,"center") or !strcmp($field,"none")){
				$divclass.=$field;
				$position=$field;
			}
			elseif(ereg("(^[0-9]+px)", $field) or ereg("^([0-9]+x[0-9]+px)", $field)){
				//width="XX" height="XX"
				$size=substr($field, 0, strrchr($field, "p"));
				if(strpos($size,"x")){
					$width=substr($size, 0, strrchr($size, "x"));
					$height=substr($size, strrchr($size, "x"), strlen($size));
					$parsedimage.="width=".$width." height=".$height." ";	
				}else{
					$width=$size;
					$parsedimage.=" width=".$width." ";
				}
			}
			elseif(!strcmp($field,"thumb") or !strcmp($field,"thumbnail")){
				$divclass=$field.$divclass;
			}
			elseif(!strcmp($field,"frame")){
				$type=$field;
				$parsedimage.="type=".$type." ";
			}
			elseif(strcmp($field,$imagename) and strcmp($field, $fields[0])){
				//is caption text
				$alt=$field;
			}
		}
		// if the last field is empty "...|]]" caption text is not showed
		if (count($fields) <= 1 || $fields[$num-1]==""){
			//the alt text is the name of the image
			$parsedimage.=" alt=\"".$imagename."\" ";
			$captiontext="";
		}
		else{
			if(strcmp($alt, "")){
				$parsedimage.=" alt=\"".$alt."\" ";
				$captiontext="<div class=\"imgcaption\" > $alt </div>";	
			}
		}
//	return "<div align=\"$position\"><div class=\"img$divclass\" ><span>$parsedimage/></a>$captiontext</span></div></div><br clear=\"all\">\n";
	
	return "</p><div class=\"img$divclass\" >$parsedimage/></a>$captiontext</div><p>";
}

// External images in Creole syntax.
function parse_creole_image($matches) {
	global $WS, $CFG, $USER;
	$url = $matches[1];
	$alt = isset($matches[4]) ? $matches[4] : '';
	return'<img src="' . $url . '" alt="' . $alt . '" />';
}

function parse_attach($matches) {
	global $WS, $CFG, $USER, $imgextension; 
	
	$alt="";
	$captiontext="";
	$parsedattach="";
	$divclass="";
	$parsedattach.="<a href=\"{$WS->dfdir->www}/";

		// $fields is an array of the fields of the images in mediawiki syntax
		$fields = explode('|', $matches[2]);
		// Let's find which fields have the image	
		$num=count($fields);
		$attachname=$fields[0];
		
		$extension=stristr($attachname, ".");
		if(in_array(strtolower($extension), $imgextension)){
			//the attatchment is an image
			$imagename=$attachname;

			$parsedattach.=$imagename."\" title=\"".$imagename."\" ><img src=\"{$WS->dfdir->www}/$imagename\" ";		

			foreach ($fields as $field){
			
				if(!strcmp($field,"left")){
					$divclass.=$field;
				}
				elseif(!strcmp($field,"right")){
					$divclass.=$field;
				}
				elseif(!strcmp($field,"center") or !strcmp($field,"none")){
					$divclass.=$field;
				}
				elseif(ereg("([0-9]px)", $field) or ereg("([0-9]x[0-9]px)", $field)){
					//width="95" height="84"
					$size=substr($field, 0, strrchr($field, "p"));
					if(strpos($size,"x")){
						$width=substr($size, 0, strrchr($size, "x"));
						$height=substr($size, strrchr($size, "x"), strlen($size));
						$parsedattach.="width=".$width." height=".$height." ";	
					}else{
						$width=$size;
						$parsedattach.=" width=".$width." ";
					}
				}
				elseif(!strcmp($field,"thumb") or !strcmp($field,"thumbnail")){
					$divclass=$field.$divclass;
				}
				elseif(!strcmp($field,"frame")){
					$type=$field;
					$parsedattach.="type=".$type." ";
				}
				elseif(strcmp($field,$imagename) and strcmp($field, $fields[0])){
					//is caption text
					$alt=$field;
				}
			}
			// if the last field is empty "...|]]" caption text is not showed
			if ($fields[$num-1]==""){
				//the alt text is the name of the image
				$parsedattach.=" alt=\"".$imagename."\" ";
				$captiontext="";
			}
			else{
				if(strcmp($alt, "")){
					$parsedattach.=" alt=\"".$alt."\" ";
					$captiontext="<div class=\"imgcaption\" > $alt </div>";	
				}
			}
				return "</p><div  class=\"img$divclass\" ><span>$parsedattach/></a>$captiontext</span></div><br clear=\"all\" /><p>";
		}// if image...
		else{
			// the attatchment is not an image
//echo "<br> al else  $parsedattach <br>";
			return $parsedattach.$attachname."\"> $attachname </a>";			
		}
	
}



?>
