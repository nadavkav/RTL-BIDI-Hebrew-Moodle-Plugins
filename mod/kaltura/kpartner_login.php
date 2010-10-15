<?php
require_once("../../config.php");
require_once('lib.php');

if (isset($_POST["email"]))
{
  try
  {
    $kClient = new KalturaClient(KalturaHelpers::getServiceConfiguration());
    $ksId = $kClient->adminUser->login($_POST["email"],$_POST["password"]);
    $kClient -> setKs($ksId);

    $kInfo = $kClient -> partner -> getInfo();

    $entry = new stdClass;
    $entry->plugin="kaltura";
    
    $entry->name="secret";
    $entry->value = $kInfo->secret;
    insert_record("config_plugins", $entry);
    
    $entry->name="adminsecret";
    $entry->value = $kInfo->adminSecret;
    insert_record("config_plugins", $entry);

    $entry->name="partner_id";
    $entry->value = $kInfo->id;
    insert_record("config_plugins", $entry);
    
    die('y:');
  }
  catch(Exception $exp)
  {
    die( 'n:' . $exp->getMessage());
  }
}
else
{
  // Report all errors except E_NOTICE
  // This is the default value set in php.ini
  error_reporting(E_ALL ^ E_NOTICE);
  //$meta = '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/kaltura/styles.php" />'."\n";
  //$meta = '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/mod/kaltura/css/kaltura.css" />'."\n";

  print_header('Kaltura Partner Login','','','',$meta);

  echo '
  <script type="text/javascript">
  function export_settings()
  {
    var email = document.getElementById("id_email").value;
    var password = document.getElementById("id_password").value;
    document.getElementById("id_export").disabled = true;
    
    $.ajax({ 
		  type: "POST", 
		  url: "'.$CFG->wwwroot.'/mod/kaltura/kpartner_login.php", 
		  data: "email="+email+"&password="+password, 
		  success: function(msg)
      { 
        if (msg.substr(0,2) == "y:")
        {
          window.top.kalturaRefreshTop();
          setTimeout("window.parent.kalturaCloseModalBox();",0);
        }
        else
        {
          alert(msg.substr(2));
        }
      },
      error: function(msg)
      {
        alert(msg);
      }
		});             
  }
  </script>';
  $id='';
  echo '<table>
        <tr><td>' . get_string('cmsemail','kaltura') . '</td><td><input type="text" id="id_email" /></td></tr>'.
        '<tr><td>' . get_string('password','kaltura') . '</td><td><input type="password" id="id_password" /></td></tr>
        <tr><td colspan="2" style="padding-top:10px;text-align:center"><input type="button" id="id_export" onclick="export_settings();" value="'. get_string('export','kaltura').'"/></td></tr></table>';        

  print_footer();
}
?>
