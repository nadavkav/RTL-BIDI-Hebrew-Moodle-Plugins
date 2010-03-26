<?php

// This Block enables the users, surfing a Moodle website, to add Annotations
// anywhere on the page. onto any element or fixed possition.
// using DIIGO services.
//
// You should (probably) use a general account for the whole class
// if you do not want to have each user open one for him/her self.
// { SPECIAL ACCOUNT FOR EDUCATORS : http://www.diigo.com/education }
// or have your users open individual accounts. to use this feature/service.
//
// Author: Kavalerchik Nadav (nadavkav@gmail.com)
// Date: 2009-Mar-19
//
// Using the public code from : http://www.diigo.com/tools
//

class block_diigo_annotate extends block_base {

    function init() {
           $this->title = get_string('blockname','block_diigo_annotate');
           $this->content_type = BLOCK_TYPE_TEXT;
           $this->version = 2010031901;
    }

	function instance_allow_config() {
		return true;
	}

    function get_content() {
        global $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }

		if(empty($this->config->username)){
			$username = '';
		} else {
			$username = $this->config->username;
		}

		if(empty($this->config->password)){
			$password = '';
		} else {
			$password = $this->config->password;
		}

        $this->content = new stdClass;
		$this->content->text = get_string('instructions','block_diigo_annotate');
		$this->content->text .= "<div class=\"annotate\">
									<input value=\"".get_string('annotate','block_diigo_annotate')."\" onclick='(function(){s=document.createElement(\"script\");s.type=\"text/javascript\";
										s.src=\"http://www.diigo.com/javascripts/webtoolbar/diigolet_b_h_b.js\";document.body.appendChild(s);})();' type=\"button\">
								</div>";
		//$optional_headers = 'Content-Type: application/x-www-form-urlencoded Content-Length: 70 ';
		//$authok = do_post_request('https://secure.diigo.com/user_mana2/login','referInfo=http%3A%2F%2Fwww.diigo.com&username='.$username.'&password='.$password.'');
		//$authok = do_post_request('http://toolbar3.diigo.com/chappai/pv=13/ct=let/cv=4.0b14/cmd=user_signIn?cmd=user_signIn&v=13&_nocache=0.44700542705994717&json={}&user='.$username.'&password='.$password.'&transId=2','cmd=user_signIn&v=13&_nocache=0.44700542705994717&json={}&user='.$username.'&password='.$password.'&transId=2');

		$this->content->text .= '<div class="singleaccount">
									<form target="_new" action="https://secure.diigo.com/user_mana2/login" method="post">
										<input type="hidden" name="username" value="'.$username.'">
										<input type="hidden" name="password" value="'.$password.'">
										<input type="submit" name="submit" value="'.get_string('connect','block_diigo_annotate').'">
									</form>
								</div>';

        $this->content->footer = '';//'Auth? = '.$authok ;

        return $this->content;
    }

    function applicable_formats() {
        return array('site' => true,'my' => true,'course' => true);
    }

}
	function do_post_request($url, $data, $optional_headers = null) {
		$params = array('http' => array(
					'method' => 'POST', //'method' => 'POST',
					'content' => $data
				));
		if ($optional_headers !== null) {
			$params['http']['header'] = $optional_headers;
		}
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if (!$fp) {
			throw new Exception("Problem with $url, $php_errormsg");
		}
		$response = @stream_get_contents($fp);
		if ($response === false) {
			throw new Exception("Problem reading data from $url, $php_errormsg");
		}
		return $response;

	}
?>
