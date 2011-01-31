<?php 
  require_once("../../../../../config.php");

  $id = optional_param('id', SITEID, PARAM_INT);
  $useremail = optional_param('useremail', '', PARAM_TEXT);
  $userpassword = optional_param('userpassword', '', PARAM_TEXT);
  $dropboxfolder = optional_param('dropboxfolder', '/Public', PARAM_TEXT);

  require_course_login($id);
  @header('Content-Type: text/html; charset=utf-8');

/// Start of Dropbox code

// I used Dropbox PHP client library from : http://code.google.com/p/dropbox-php/
// and for Authentication to work...
// i have installed OAuth php extension on the Apache2 server (Debian 6.0)
// using "pecl install oauth" command. and restarted the Apache2 server.

/* Please supply your own consumer key and consumer secret */
// you can get one after you register with Dropbox developer community
// https://www.dropbox.com/developers
$consumerKey = 'iurx6cwhtzgynve';
$consumerSecret = 'k50pkbah4b6jt73';

include 'Dropbox/autoload.php';

//session_start();

$oauth = new Dropbox_OAuth_PHP($consumerKey, $consumerSecret);

// If the PHP OAuth extension is not available, you can try
// PEAR's HTTP_OAUTH instead.
// $oauth = new Dropbox_OAuth_PEAR($consumerKey, $consumerSecret);

$dropbox = new Dropbox_API($oauth);

//header('Content-Type: text/plain');

// Did we get user email and password from a previous Dialog post?
// if not, let's try to get it from Cookies
if (empty($useremail)) $useremail = $_COOKIE['dropbox_useremail'];
if (empty($userpassword)) $userpassword = $_COOKIE['dropbox_userpassword'];

// If no POST and no COOKIE...
if (empty($useremail) or empty($userpassword)) {
  echo "<form id=\"dropboxuser\" method=\"post\" action=\"dialog.php\">";
  echo get_string('whatisuseremail').'<input type="text" name="useremail" id="useremail"><br/>';
  echo get_string('whatisuserpassword').'<input type="text" name="userpassword" id="userpassword"><br/>';
  echo "<input type=\"submit\" value=\"".get_string('login')."\">";
  echo "</form>";
  die;
}
// If we got that far, we have a user email and password.
// Let's save it as a Cookie (for 7 days?)
setcookie('dropbox_useremail',$useremail,time() + (86400 * 7)); // 86400 = 1 day
setcookie('dropbox_userpassword',$userpassword,time() + (86400 * 7));

$tokens = $dropbox->getToken($useremail, $userpassword);
//echo "Tokens:\n";
//print_r($tokens);

// Note that it's wise to save these tokens for re-use.
$oauth->setToken($tokens);

//echo "Account info:\n";
//print_r($dropbox->getAccountInfo());

$accountinfo = $dropbox->getAccountInfo();

function display_folder($dropbox,$folder) {

  $dropboxfiles = $dropbox->getMetaData(str_replace(' ','%20',$folder));
  $accountinfo = $dropbox->getAccountInfo();

  $pathlist = explode('/',$folder);
  $showlink = '';
  foreach ($pathlist as $link) {
    $showlink .= '/'.$link;
    echo '<span style="color:green;"><a href="dialog.php?dropboxfolder='.$showlink.'">'.$link.'</a></span> >> ';
  }
  echo "<br/><br/>";
  
  foreach ($dropboxfiles['contents'] as $items) {
    if ($items['is_dir'] == 1) {
      //echo '<div style="color:blue;">'.$items['path'].'</div><br/>'."\n";
      echo '<div style="color:blue;"><a href="dialog.php?dropboxfolder='.$items['path'].'">'.$items['path'].'</a></div>';
      //display_folder($dropbox,$items['path']);
    } else {
      echo '<input type="checkbox" name="file" value="http://dl.dropbox.com/u/'.$accountinfo["uid"].str_replace("/Public","",$items["path"]).'">';
      echo '<a target="_new" href="http://dl.dropbox.com/u/'.$accountinfo['uid'].str_replace('/Public','',$items['path']).'">'.$items['path'].'</a><br/>'."\n";
    }
  }
}

/// end of Dropbox code

  print_header_simple();
  echo "<div style='direction:ltr;text-align:left;'>";
    echo "<form id=\"dropbox\" method=\"post\" action=\"dialog.php\">";
      display_folder($dropbox,$dropboxfolder); // We can only use the user's Public folder when sharing files outside of Dropbox (without a password)
    echo "<input type=\"button\" onclick=\"onOK();\" value=\"".get_string('add')."\">";
    echo "</form>";
  echo "</div>";
  print_footer();
?>

<script type="text/javascript">
//<![CDATA[

function Init() {
  var param = window.dialogArguments;
  /*
  if (param) {
      var alt = param["f_url"].substring(param["f_url"].lastIndexOf('/') + 1);
      document.getElementById("f_url").value = param["f_url"];
      document.getElementById("f_alt").value = param["f_alt"] ? param["f_alt"] : alt;
      document.getElementById("f_border").value = parseInt(param["f_border"] || 0);
      window.ipreview.location.replace('preview.php?id='+ <?php echo $id;?> +'&imageurl='+ param.f_url);
  }
*/
  document.getElementById('useremail').focus();
};

function onOK() {

  var param = new Object();
  var inputs = document.getElementsByTagName('input');
  for(var i = 0; i < inputs.length; i++) {
   if (inputs[i].checked == true){
     //alert(inputs[i].value);
     param[i] = '<a target="_new" href="'+inputs[i].value+'">'+inputs[i].value+'</a><br/>';
     //param[i] = '<a href="<?php echo $CFG->wwwroot; ?>/blocks/file_manager/file.php?cid=<?php echo $USER->id; ?>&groupid=0&fileid='+inputs[i].value+'">'+inputs[i].name+'</a>';
   };
  }

  opener.nbWin.retFunc(param);
  window.close();
  return false;
};

function onCancel() {

  window.close();
  return false;
};
//[[>
</script>