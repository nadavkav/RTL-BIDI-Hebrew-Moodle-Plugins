<?php
	$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 5;
	$text = isset($_POST['text']) ? $_POST['text'] : '';
	
		
	$url = 'http://%s.wikipedia.org/w/index.php?title=%s&action=edit';
	
	// crawl wikipedia data
	$html = mindmap__curlHelper(sprintf($url, 'de', urlencode($text)));
	
	// if there is this string we are redirected to another page...
	$pattern = '!#REDIRECT\[\[(.*)\]\]!U';
	if(preg_match($pattern, $html, $matches))
	{
		$text = $matches[1];
		$html = mindmap__curlHelper(sprintf($url, 'de', urlencode($text)));
	}
	

	preg_match_all('#\[\[([a-zA-Z0-9 _-]*)\]]#u', $html, $matches);
	$nodes = array();
	foreach($matches[1] as $m)
	{
	
		if(!empty($m) && !in_array($m, $nodes))
		{
			$nodes[] = $m;	
		}
		if(count($nodes)>=$limit)
		{
			break;
		}
	}

	
	
/** 
 * Helper function to crawl a given url and return the content.
 * @param String $url The url to open
 * @return String The content of $url
 */    	
	function mindmap__curlHelper($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		return curl_exec($ch);		
	}




header('Content-Type: application/xml');?><xml>
<?php foreach($nodes as $node):?>
	<node><?php echo $node;?></node>
<?php endforeach;?>
</xml>

