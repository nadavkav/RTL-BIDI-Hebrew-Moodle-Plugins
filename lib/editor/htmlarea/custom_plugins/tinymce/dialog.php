<?php

  require("../../../../../config.php");

  $id = optional_param('id', SITEID, PARAM_INT);

  //require_course_login($id);
  $langpath = $CFG->dirroot."/lib/editor/htmlarea/custom_plugins/tinymce/lang/";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title><?php echo get_string("title","tinymce",'',$langpath );?></title>

<script type="text/javascript">
//<![CDATA[

function Init() {

  //document.getElementById('tinymceeditor').innerHTML = opener.parent_editor.body.innerHTML;
  tinyMCE.execCommand('mceInsertContent',false,opener.parent_editor.body.innerHTML);
};

function onOK() {
//   var required = {
//     "tinymceeditor": "You should havesome Content here, before we move on..."
//   };
//   for (var i in required) {
//     var el = document.getElementById(i);
//     if (!el.value) {
//       alert(required[i]);
//       el.focus();
//       return false;
//     }
//   }
//   var fields = ["tinymceeditor"];
//   var param = new Object();
//   for (var i in fields) {
//     var id = fields[i];
//     var el = document.getElementById(id);
//     param[id] = el.value;
//   }
  var param = new Object();
  param['tinymceeditor'] = tinyMCE.get('tinymceeditor').getContent()
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

</script>
<style type="text/css">
html, body {
margin: 2px;
background-color: rgb(212,208,200);
font-family: Tahoma, Verdana, sans-serif;
font-size: 11px;
}
button { width: 170px; }
.space { padding: 2px; }
.title { direction:rtl; text-align:center; font-size: 22px;}
form { margin-bottom: 0px; margin-top: 0px; }
</style>

<!-- TinyMCE -->
<script type='text/javascript'>
  var default_value = opener.parent_editor.body.innerHTML;
</script>

<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
  tinyMCE.init({
    // General options
    mode : "textareas",
    theme : "advanced",
    plugins : "autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,autosave",

    // Theme options
    theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
    theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
    theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
    theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak,restoredraft",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "right",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,

    // Example content CSS (should be your site CSS)
    content_css : "css/content.css",

    // Drop lists for link/image/media/template dialogs
    template_external_list_url : "lists/template_list.js",
    external_link_list_url : "lists/link_list.js",
    external_image_list_url : "lists/image_list.js",
    media_external_list_url : "lists/media_list.js",

    // Style formats
    style_formats : [
      {title : 'Bold text', inline : 'b'},
      {title : 'Red text', inline : 'span', styles : {color : '#ff0000'}},
      {title : 'Red header', block : 'h1', styles : {color : '#ff0000'}},
      {title : 'Example 1', inline : 'span', classes : 'example1'},
      {title : 'Example 2', inline : 'span', classes : 'example2'},
      {title : 'Table styles'},
      {title : 'Table row 1', selector : 'tr', classes : 'tablerow1'}
    ],

    // Replace values for the template plugin
    template_replace_values : {
      username : "Some User",
      staffid : "991234"
    },

    setup : function(ed) {
        var is_default = false;
        ed.onInit.add(function(ed) {
            ed.focus();

            // set the focus
            var cont = ed.getContent();

            // get the current content
            slen = cont.length;
            cont = cont.substring(3,slen-4);

            // cut off <p> and </p> to comply with XHTML strict
            // these can't be part of the default_value
            //is_default = (cont == tiny_mce_default_value);
            is_default = (cont == default_value);

            // compare those strings
            if (!is_default)
                return;

            // nothing to do
            ed.selection.select(ed.dom.select('p')[0]);

            // select the first (and in this case only) paragraph
        });

        ed.onMouseDown.add(function(ed,e) {
            if (!is_default)
                return;

            // nothing to do
            ed.selection.setContent('');
            // replace the default content with nothing
        });

        // The onload-event in IE fires before TinyMCE has created the Editors,
        // so it is no good solution here.
    }

  });
</script>
<!-- /TinyMCE -->

</head>
<body onload="Init()">

<div class="title"><?php echo get_string("title","tinymce",'',$langpath );?></div>

<form action="" method="get">

<table width="100%" border="0" cellspacing="0" cellpadding="22">
  <tr>
    <td width="100%" valign="top">
      <!-- Gets replaced with TinyMCE, remember HTML in a textarea should be encoded -->
      <div>
        <textarea id="tinymceeditor" name="tinymceeditor" rows="15" cols="80" style="width: 80%"></textarea>
      </div>

      <!-- Some integration calls -->
<!--      <a href="javascript:;" onclick="tinyMCE.get('tinymceeditor').show();return false;">[Show]</a>
      <a href="javascript:;" onclick="tinyMCE.get('tinymceeditor').hide();return false;">[Hide]</a>
      <a href="javascript:;" onclick="tinyMCE.get('tinymceeditor').execCommand('Bold');return false;">[Bold]</a>
      <a href="javascript:;" onclick="alert(tinyMCE.get('tinymceeditor').getContent());return false;">[Get contents]</a>
      <a href="javascript:;" onclick="alert(tinyMCE.get('tinymceeditor').selection.getContent());return false;">[Get selected HTML]</a>
      <a href="javascript:;" onclick="alert(tinyMCE.get('tinymceeditor').selection.getContent({format : 'text'}));return false;">[Get selected text]</a>
      <a href="javascript:;" onclick="alert(tinyMCE.get('tinymceeditor').selection.getNode().nodeName);return false;">[Get selected element]</a>
      <a href="javascript:;" onclick="tinyMCE.execCommand('mceInsertContent',false,'<b>Hello world!!</b>');return false;">[Insert HTML]</a>
      <a href="javascript:;" onclick="tinyMCE.execCommand('mceReplaceContent',false,'<b>{$selection}</b>');return false;">[Replace selection]</a>-->
      <br/>
      <button type="button" name="ok" onclick="return onOK();"><?php echo get_string("ok","tinymce",'',$langpath ) ?></button>
      <button type="button" name="cancel" onclick="return onCancel();"><?php echo get_string("cancel","tinymce",'',$langpath ) ?></button>
    </td>
  </tr>
</table>

</form>

</body>
</html>