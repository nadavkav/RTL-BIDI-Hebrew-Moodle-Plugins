<?php


function wikibook_info($pagename, $wikibook="") {
	if (preg_match("/^wikibook:(.*)/", $pagename, $match)) {
		
		$nodes = wikibook_nodes($match[1], wikibook_page_content($pagename));
		if ($wikibook) {
			 foreach ($nodes as $node) {
			 	if ($node->section_label == $wikibook) break;
			 }
		} else {
			$node = $nodes[0];
		}
		
		$info->title = $node->title;
		$info->index = wikibook_index($match[1], $nodes, $node, $node->level);
		$info->navibar = wikibook_navibar($match[1], $nodes, $node);
        $info->isleaf = wikibook_is_node_leaf($nodes, $node);

		return $info;
		
	} else if ($wikibook) {
		
		$nodes = wikibook_nodes($wikibook, wikibook_page_content('wikibook:'.$wikibook));
		foreach ($nodes as $node) {
			if ($node->pagelink && $node->pagelink->page == $pagename) {
				$info->title = $node->pagelink->label;
				$info->index = null;
				$info->navibar = wikibook_navibar($wikibook, $nodes, $node);
                $info->isleaf = wikibook_is_node_leaf($nodes, $node);

				return $info;
			}
		}
		
	}
}

function wikibook_is_node_leaf($nodes, $node)
{
    // only look for fake chapters
    if (preg_match("/^(.*?\[\[).*?(\]\].*)/", $node->title) > 0)
        return false;

    $size = sizeof($nodes);
    for ($i = 0; $i < $size; $i++)
    {
        $currentnode = $nodes[$i];
        if ($currentnode == $node)
        {
            if ($i == $size - 1) return true;

            $nextnode = $nodes[$i + 1];
            if ($nextnode->level > $currentnode->level)
                return false;
            return true;
        }
    }
    return false;
}

function wikibook_page_content($pagename) {
	global $WS;
	$select = "dfwiki = {$WS->dfwiki->id} AND pagename = '$pagename' ".
		"AND groupid = {$WS->groupmember->groupid}";
	if ($WS->dfwiki->studentmode != '0' || $WS->cm->groupmode == '0') {
		$select .= " AND ownerid = {$WS->member->id}";
	}
	if ($version = get_field_select('wiki_pages', 'MAX(version)', $select)) {        
		if ($record = get_record_select('wiki_pages', $select . " AND version = $version"))
			return $record->content;
	}
}


function wikibook_index($wikibook, $nodes, $node, $baselevel=0) {
	$text = $node->content;
	foreach (wikibook_node_children($nodes, $node) as $child) {
		$text .= str_repeat(":", $child->level - $baselevel);
		$text .= " '''$child->section_label ''' ";
		if ($child->pagelink) {
			$text .= preg_replace("/^(.*?\[\[).*?(\]\].*)/s",
				"$1{$child->pagelink->page}|{$child->pagelink->label}|wikibook:$wikibook$2",
				 $child->title) . "\n";
		} else {
			$text .= $child->title . "\n";
		}
		$text .= wikibook_index($wikibook, $nodes, $child, $baselevel);
	}
	return $text;
}

function wikibook_navibar($wikibook, $nodes, $node) {
	$right_arrow = ' <span class="arrow sep">&#x25BA;</span> ';
	$left_arrow = ' <span class="arrow sep">&#x25C4;</span> ';	
	$html[] = '</ul></div></div></div>';
	$node_next = wikibook_node_next($nodes, $node);
	$node_prev = wikibook_node_prev($nodes, $node);
	if ($node_next) {
		 $html[] = $right_arrow . '</li>';
		 $html[] = wikibook_navibar_link($wikibook, $node_next, true,
			get_string('next'));
		 $html[] = '<li>';
		 if ($node_prev) {
		 	$html[] = '<li> <span class="arrow sep">&#x25CF;</span> </li>';
		 }
	}
	if ($node_prev) {
		$html[] = '</li>';
		 $html[] = wikibook_navibar_link($wikibook, $node_prev, true,
			get_string('previous'));
		 $html[] = '<li>' . $left_arrow;
	}
	$html[] = '</ul></div><div class="navbutton"><div class="breadcrumb"><ul>'; 
	$html[] = '<li>' . wikibook_navibar_link($wikibook, $node, false) . '</li>';
	foreach (wikibook_node_parents($nodes, $node) as $parent) {
		$html[] = $right_arrow . '</li>';;
		$html[] = wikibook_navibar_link($wikibook, $parent, true);
		$html[] = '<li>';
	}

	$html[] = '<div class="navbar clearfix"><div class="breadcrumb"><ul>';
	
	return implode("", array_reverse($html));
}


function wikibook_navibar_link($wikibook, $node, $enable=true, $label=null) {
	$html = '';
	$title = $node->pagelink ? $node->pagelink->label : $node->title;
	$label = $label ? $label : $title;
	$url = wikibook_node_url($wikibook, $node);
	$html .= $enable ? "<a href=\"$url\" title=\"$title\">$label</a>" : $label;
	return $html;
}

function wikibook_node_url($wikibook, $node) {
	global $WS, $COURSE;
	$url = 'view.php?id=' . (isset($WS->dfcourse) ? $COURSE->id : $WS->cm->id);
	$url .= '&amp;gid=' . $WS->groupmember->groupid;
	$url .= '&amp;uid=' . $WS->member->id;
	if ($node->pagelink) {
		$url .= '&amp;page=' . urlencode($node->pagelink->page);
		$url .= '&amp;wikibook=' . urlencode($wikibook);
		$url .= $node->pagelink->anchor ? '#a' . bin2hex($node->pagelink->anchor) : '';
	} else {
		$url .= '&amp;page=wikibook:' . urlencode($wikibook);
		if ($node->section_label) {
			$url .= '&amp;wikibook=' . urlencode($node->section_label);
		}
	}
	return $url;
}

function wikibook_nodes($title, $index) {
	$nodes = array();
	$node = new object();
	$node->title = wikibook_title($index);
	if (!$node->title) {
		$node->title = $title;
	}
	preg_match("/^(.*?)\*/s", $index, $match);
	$node->content = $match ? $match[1] : $index;
	$node->pagelink = null;
	$node->level = 0;
	$node->section = array();
	$node->section_label = '';
	$node->pos = 0;
	$nodes[] = $node;
	$last_node = $node;

	if (!$match) return $nodes;
	
	preg_match_all("/^(\*+)([^\n]+)\n(([^\*][^\n]*\n|\n)*)/ms", $index, $matches);
	if (!$matches) return $nodes;

	for ($i=0; $i < count($matches[0]); $i++) {
		$node = new object();
		$node->level = strlen($matches[1][$i]);
		$node->title = trim($matches[2][$i]);
		$node->content = $matches[3][$i];
		$node->pagelink = wikibook_parse_link($node->title);
		
		$node->section = array();
		$base = min($node->level - 1, $last_node->level);
		for ($j = 0; $j < $base; $j++) {
			$node->section[] = $last_node->section[$j];
		}
		if ($node->level <= $last_node->level) {
			$node->section[] = $last_node->section[$base] + 1;
		} else {
			for ($j = $base; $j < $node->level; $j++) {
				$node->section[] = 1;
			}
		}
		$node->section_label = implode('.', $node->section);

		$node->pos = count($nodes);

		$nodes[] = $node;
		$last_node = $node;
	}
	
	return $nodes;
}


function wikibook_title($text) {
	if (preg_match("{{wikibook:(.*?)}}", $text, $match)) {
		return trim($match[1]);	
	}
}

function wikibook_parse_link($text) {
	if (preg_match("/.*\[\[(.*?)\]\]/", $text, $match)) {
		$parts = explode("|", $match[1]);
		$target = $parts[0];
		$link->label = count($parts) == 2 ? $parts[1] : $target;
		$anchorparts = explode("#", $target);
		$link->page = $anchorparts[0];
		$link->anchor = count($anchorparts) == 2 ? "#a".bin2hex($anchorparts[1]) : "";
		return $link;
	}
}

function wikibook_node_children($nodes, $node) {
	$children = array();
	$level = $node->level + 1;
	for ($pos = $node->pos + 1; $pos < count($nodes); $pos++) {
		if ($nodes[$pos]->level == $level) {
			$children[] = $nodes[$pos];
		} else if ($nodes[$pos]->level < $level) {
			break;
		}
	}
	return $children;

}

function wikibook_node_parents($nodes, $node) {
	$parents = array();
	$level = $node->level;
	for ($pos = $node->pos - 1; $pos >= 0; $pos--) {
		if ($nodes[$pos]->level < $level) {
			$level = $nodes[$pos]->level;
			$parents[] = $nodes[$pos];
		}
	}
	return $parents;
}

function wikibook_node_next($nodes, $node) {
	if (isset($nodes[$node->pos + 1])) {
		return $nodes[$node->pos + 1];
	}
}

function wikibook_node_prev($nodes, $node) {
	if (isset($nodes[$node->pos - 1])) {
		return $nodes[$node->pos - 1];
	}
}

?>
