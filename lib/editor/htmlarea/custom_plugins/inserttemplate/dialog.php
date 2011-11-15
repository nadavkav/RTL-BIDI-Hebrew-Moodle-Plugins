<?php
  require_once("../../../../../config.php");

  $id = optional_param('id', SITEID, PARAM_INT);
  $langpath = $CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/inserttemplate/lang/';

  // Add ajax-related libs (nadavkav)
  //require_js(array('yui_yahoo', 'yui_event', 'yui_dom', 'yui_connection'));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title><?php echo get_string('title', 'inserttemplate','',$langpath); ?></title>
    <link rel="stylesheet" href="dialog.css" type="text/css" />
    <script type="text/javascript"  src="<?php echo $CFG->wwwroot; ?>/lib/yui/yahoo/yahoo-min.js"></script>
    <script type="text/javascript"  src="<?php echo $CFG->wwwroot; ?>/lib/yui/event/event-min.js"></script>
    <script type="text/javascript"  src="<?php echo $CFG->wwwroot; ?>/lib/yui/dom/dom-min.js"></script>
    <script type="text/javascript"  src="<?php echo $CFG->wwwroot; ?>/lib/yui/connection/connection-min.js"></script>


<script type="text/javascript">
//<![CDATA[

function Init() {
  //var param = window.dialogArguments;
  /*
  if (param) {
      var alt = param["f_url"].substring(param["f_url"].lastIndexOf('/') + 1);
      document.getElementById("f_url").value = param["f_url"];
      document.getElementById("f_alt").value = param["f_alt"] ? param["f_alt"] : alt;
      document.getElementById("f_border").value = parseInt(param["f_border"] || 0);
      window.ipreview.location.replace('preview.php?id='+ <?php echo $id;?> +'&imageurl='+ param.f_url);
  }
*/
  //document.getElementById('objective').focus();
};

function onOK() {

 /*
 var required = {
    "template": "<?php get_string("templatemissing", "inserttemplate",'',$langpath);?>",
  };
  for (var i in required) {
    var el = document.getElementById(i);
    if (!el.value) {
      alert(required[i]);
      el.focus();
      return false;
    }
  }
  */
  // pass data back to the calling window
  var param = new Object();
  var el = document.getElementById('template');
  param['template'] = el.innerHTML;


  opener.nbWin.retFunc(param);
  window.close();
  return false;
};

function cancel() {
  window.close();
  return false;
};
//]]>
</script>

<script>
  //var postData = "username=anonymous&userid=0";
  //var div = document.getElementById('template');

  var handleSuccess = function(o){
    if(o.responseText !== undefined){
      document.getElementById('template').innerHTML = o.responseText;
/*
      div.innerHTML = "Transaction id: " + o.tId;
      div.innerHTML += "HTTP status: " + o.status;
      div.innerHTML += "Status code message: " + o.statusText;
      div.innerHTML += "<li>HTTP headers: <ul>" + o.getAllResponseHeaders + "</ul></li>";
      div.innerHTML += "PHP response: " + o.responseText;
      div.innerHTML += "Argument object: " + o.argument;
*/
    }
  };

  var handleFailure = function(o){
      YAHOO.log("The failure handler was called.  tId: " + o.tId + ".", "info", "example");

    if(o.responseText !== undefined){
      div.innerHTML = "<li>Transaction id: " + o.tId + "</li>";
      div.innerHTML += "<li>HTTP status: " + o.status + "</li>";
      div.innerHTML += "<li>Status code message: " + o.statusText + "</li>";
    }
  };

  var callback =
  {
    success:handleSuccess,
    failure: handleFailure,
    argument: ['foo','bar']
  };

function updateTemplate(glitemid){
  var request = YAHOO.util.Connect.asyncRequest('POST', 'gettemplate.php', callback, 'glitemid='+glitemid);
}
</script>

<?php if (right_to_left() ) { echo '<style>body {direction:rtl;text-align:right;}</style>'; } ?>

</head>

<body onload="Init()">

<form action="dialog.php" method="get">
  <select id="templatelist">
    <?php //echo $CFG->editor_templateglossary;
      $templates = get_records('glossary_entries','glossaryid',$CFG->editor_templateglossary);
      echo '<option value="0">'.get_string('choosetemplate',"inserttemplate",'',$langpath).'</option>';
      foreach ($templates as $template) {
        echo '<option onclick="updateTemplate('.$template->id.')" value="'.$template->id.'" >'.$template->concept.'</option>';
      }

    ?>
  </select>
  <button type="button" onclick="return cancel();"><?php echo get_string("cancel","inserttemplate",'',$langpath);?></button>
  <button type="button" onclick="return onOK();"><?php echo get_string("set","inserttemplate",'',$langpath);?></button>
</form>

<hr/>
<div id="template"></div>
<hr/>

</body>
</html>