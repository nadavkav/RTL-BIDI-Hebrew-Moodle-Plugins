<?php

/* Twitter Search block
   This block shows the tweets resulting from a particular search. The search
   terms and no of results is configurable.
   It's only a quick hack, don't be nasty to it.
   khughes@southdevon.ac.uk
*/

class block_twitter_search extends block_base {
  function init() {
    $this->title   = get_string('blocktitle','block_twitter_search');
    $this->version = 2010011302;
  }

  function instance_allow_multiple(){
    return true;
  }

  function specialization() {
    if(empty($this->config->search_string)){
      $this->config->search_string = '#moodle';
    }
    if(empty($this->config->no_tweets)){
      $this->config->no_tweets = 10;
    }
  }

  function get_content() {
    if ($this->content !== NULL) {
      return $this->content;
    }

    $search_string = $this->config->search_string;
    $search_string_enc = urlencode($search_string);
    $no_tweets      = $this->config->no_tweets;
    $url = "http://search.twitter.com/search.atom?q=$search_string_enc&rpp=$no_tweets";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $xml = curl_exec($ch);
    curl_close($ch);
    $dom = DOMDocument::loadXML($xml);
    $tweets = $dom->getElementsByTagName('entry');

    $output = "<ul class='list'>";

    foreach ($tweets as $tweet) {
      $output .= "<li style='border-top:1px dotted #aaa;padding:4px'>";
      $author = $tweet->getElementsByTagName('author')->item(0);
      $authorname = $author->getElementsByTagName('name')->item(0)->textContent;
      $authorlink = $author->getElementsByTagName('uri')->item(0)->textContent;
      $output .= "<a href='$authorlink'>$authorname</a>: ";
      $output .= format_text($tweet->getElementsByTagName('content')->item(0)->textContent,FORMAT_HTML);
      $output .= "</li>";
    }

    $output .= "</ul>";

    $this->title           = $search_string.get_string('ontwitter','block_twitter_search');
    $this->content         =  new stdClass;
    $this->content->text   = $output;
    $this->content->footer = '';

    return $this->content;
  }
}
?>
