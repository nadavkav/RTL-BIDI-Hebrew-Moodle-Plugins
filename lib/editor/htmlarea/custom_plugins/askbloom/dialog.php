<?php
/**

  AskBloom HTMLAREA custom plugin is heavily based on Ian Byrd's work: "The Differentiator"
  Ian's email: ian@byrdseed.com (http://www.byrdseed.com/about/)
  Ian's website: http://www.byrdseed.com/

  The Differentiator: http://www.byrdseed.com/the-differentiator/
  All right reserve to Ian Byrd

  Around Aug-2010...
  It was adapted to work with the Moodle framework (By Nadav Kavalerchik, nadavkav@gmail.com)
  Translation infrastructure was added + English and Hebrew translation.
  and the necessary wrappers to make it plug into HTMLAREA editor.

  Enjoy :-)

**/

  require_once("../../../../../config.php");

  $id = optional_param('id', SITEID, PARAM_INT);
  require_course_login($id);

  @header('Content-Type: text/html; charset=utf-8');

  $langfolder = $CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/askbloom/lang/';

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml" lang="en"><head>
  <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
  <title><?php echo get_string('windowtitle','askbloom','',$langfolder); ?></title>
  <link rel="stylesheet" type="text/css" href="TheDifferentiator/jqueryui.css">

  <style>
    body{direction:ltr;margin:5px;padding:5px;font:1em"Trebuchet MS",verdana,arial,sans-serif;font-size:100%;}
    div.container{}
    cite{font-size:0.75em;}
    h2{text-align:center;font-size:1em;color:#333;font-weight:normal;margin:0;background:#eee;border:1px solid#aaa;padding:2px;}
    h3{font-size:1em;color:#333;margin:0;}
    #objective{color:#333;padding:10px 0;margin:15px 0;border:1px solid#ccc;background:#eee;}
    .innerTabs{color:#666;font-size:.9em line-height:1.2em;}
    .innerTabs li ul li{border:1px solid#ccc;padding:10px 5px;margin:5px 0;}
    ul{list-style:none;padding:0;}
    div#tabs-1 ul li.innerTab{width:15%;}
    div#tabs-2 ul li.innerTab{width:23%;}
    div#tabs-3 ul li.innerTab{width:23%;}
    div#tabs-4 ul li.innerTab{width:18%;}
    div#tabs-5 ul li.innerTab{width:50%;}
    .innerTab{float:left;padding:0 5px;padding-bottom:0pt;}
    .drawer-content UL{float:none;padding-top:7px;}
    .drawer-content LI A{display:block;overflow:hidden;}
    li.hover{color:#000;background:#fdd;border:1px solid#333;}
    .editable{border-bottom:1px dashed#000;}
    #menu{list-style-type:none;margin:0;padding:0;font-size:0.8em;}
    #menu li{margin:0 3px 3px 3px;padding:0.4em;padding-left:1.5em;font-size:1.4em;height:18px;}
    #menu li span{position:absolute;margin-left:-1.3em;}
    h1{text-align:center;}
    #objective{text-align:left;}
    #objective a{margin-right:5px;border:1px solid green;padding:5px;color:green;text-decoration:none;background:#afa;}
    div.bottom{clear:both;}
    p{font-size:.7em;padding:0;margin:0;color:#777;}
    #help{display:none;color:#f00;background:#fcc;padding:5px;border:1px solid#f00;width:500px;margin:0 auto;}
    #pop{z-index:100;background:#eef;border:5px solid#66f;padding:10px;display:none;position:absolute;top:100px;left:0;margin:0 0 0 320px;width:640px;text-align:center;}
    #pop p{padding:5px 0;color:#444;font-size:0.9em;}
    #pop h2, #pop h3{font-weight:bold;color:#000;background:transparent;border:none;padding-bottom:5px;font-size:1.4em;font-family:Arial,Verdana,Helvetica,sans-serif;text-transform:uppercase;}
    #pop h3{font-weight:normal;text-transform:none;font-size:1.1em;color:#555;text-align:center;}
    #pop input{border:1px solid #00c;font-size:14px;padding:5px;margin:0;}
    #pop input#submit{padding:5px;border:none;background:#00c;color:#fff;margin:0;font-size:14px;}
    #pop form{padding:10px 0;}
    #pop #nope{font-size:12px;border-top:1px solid #ccc;text-align:left;}
    #pop a{color:#00f;}
    .ui-tabs .ui-tabs-nav li {float: left;}

    <?php
    if (right_to_left()) {
    ?>
      body{direction:rtl;}
      .innerTab{float:right;}
      #objective{text-align:right;}
      .ui-tabs .ui-tabs-nav li {float: right;}
    <?php
    }
    ?>

  </style>

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
      window.ipreview.location.replace('preview.php?id='+ <?php echo $id; ?> +'&imageurl='+ param.f_url);
  }
*/
  document.getElementById('objective').focus();
};

function onOK() {
  var required = {
    "objective": "You should better make up a nice sentence, before we move on..."
  };
  for (var i in required) {
    var el = document.getElementById(i);
    if (!el.innerHTML) {
      alert(required[i]);
      el.focus();
      return false;
    }
  }
  var fields = ["objective"];
  var param = new Object();
  for (var i in fields) {
    var id = fields[i];
    var el = document.getElementById(id);
    param[id] = el.innerHTML;
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

<script src="TheDifferentiator/jsapi"></script>
<script type="text/javascript">
    google.load("jquery", "1.3.2");
    google.load("jqueryui", "1.7.2");
</script>

<script src="TheDifferentiator/jquery_002.js" type="text/javascript"></script>
<script src="TheDifferentiator/jquery-ui.js" type="text/javascript"></script>
<script src="TheDifferentiator/jquery_004.js" type="text/javascript"></script>
<script src="TheDifferentiator/jquery_005.js" type="text/javascript"></script>
<script src="TheDifferentiator/jquery_003.js" type="text/javascript"></script>
<script src="TheDifferentiator/jquery.js" type="text/javascript"></script>

    <script type="text/javascript">
    <!--
    google.setOnLoadCallback(function() {

		$("#hidePop").click(function(){$('#pop').slideUp();});
		$("#submit").click(function(){$('#pop').slideUp();});
		$("#tabs").tabs();
    $("#menu").sortable();
		$("#menu").disableSelection();
		$(".editable").editInPlace({callback:function(){return true}});

    $('ul#ts li ul li').click(function () {
      $('#thinking_skill').html($(this).html().toLowerCase() );
      $('#thinking_skill').effect("highlight", {}, 1500);
    });
    $('ul#r li ul li').click(function () {
      $('#resource').html("<?php echo get_string('jquse','askbloom','',$langfolder); ?>" + $(this).html().toLowerCase() + " ");
      $('#resource').effect("highlight", {}, 1500);
    });
    $('ul#c li ul li').click(function () {
      $('#content').html("<?php echo get_string('jqto','askbloom','',$langfolder); ?>" + $(this).html().toLowerCase() + "<?php echo get_string('jqsubject','askbloom','',$langfolder); ?>");
      $('#content').effect("highlight", {}, 1500);
    });
    $('ul#p li ul li').click(function () {
      $('#product').html("<?php echo get_string('jqtocreate','askbloom','',$langfolder); ?>" + $(this).html().toLowerCase());
      $('#product').effect("highlight", {}, 1500);
    });
    $('ul#g li ul li').click(function () {
      $('#groups').html("<?php echo get_string('jqworkinggroupsof','askbloom','',$langfolder); ?>" + $(this).html().toLowerCase() + ".");
      $('#groups').effect("highlight", {}, 1500);
    });

    $('#submit').click(function(){$('#pop').slideUp();});
    $('#add').click(function(){
      var clone = $("h1#obj").clone();
      clone.find('span').removeAttr("id");
      $('ul#menu').append('<li class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' + clone.text() + '</li>');
      $('ul#menu li:last').effect("highlight", {}, 1500);
		});

    $('li.innerTab ul li').hover(
      function () {
        $(this).addClass("hover");
      },
      function () {
        $(this).removeClass("hover")
      }
    );

    $('div#help a').click(function(){$('div#help').slideUp()});
    $('a#showHelp').click(function(){$('div#help').slideDown()});
    if ($.cookie('40080523') != '1')
    {
      $('#pop').animate({opacity: 1.0}, 60001).slideDown('slow');
  		$.cookie('40080523', '1', { expires: 60 });
    }
});


    //-->
    </script>
</head>

<body id="page" onload="Init()">
  <span style="float: right;"><a style="color: red; font-decoration:none;" id="showHelp" href="#"><?php echo get_string('needhelp','askbloom','',$langfolder); ?></a></span><br/>

  <!--div id="help">
    <object id='stU0hcQkxIR15eSFleXlldUVZQ' width='500' height='344' type='application/x-shockwave-flash'
            data='http://www.screentoaster.com/swf/STPlayer.swf'
            codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0'>
      <param name='movie' value='http://www.screentoaster.com/swf/STPlayer.swf'/>
      <param name='allowFullScreen' value='true'/><param name='allowScriptAccess' value='always'/>
      <param name='flashvars' value='video=stU0hcQkxIR15eSFleXlldUVZQ'/>
    </object>
    <p style="text-align: center; color: blue;"><a href="#"><?php echo get_string('clicktohide','askbloom','',$langfolder); ?></a></p>
  </div-->

  <div id="objective">
    <h1 id="obj"><?php echo get_string('dearstudents','askbloom','',$langfolder); ?><span style="" id="thinking_skill"></span><span style="" id="content"></span>
    <span style="background:none repeat scroll 0% 0% transparent;" class="editable" id="your_content">
    <?php echo get_string('clicktoentersubject','askbloom','',$langfolder); ?></span>
    <span id="resource"></span><span id="product"></span><span id="groups"></span></h1>
  </div>

  <button type="button" name="ok" onclick="return onOK();"><?php echo get_string('okimdone','askbloom','',$langfolder); ?></button>
  <button type="button" name="cancel" onclick="return onCancel();"><?php echo get_string('cancel','askbloom','',$langfolder); ?></button>

<div class="ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabs">

  <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
    <li class="ui-corner-top ui-tabs-selected ui-state-active ui-state-focus"><a href="#tabs-1"><span title="<?php echo get_string('tabthinkingskillshelp','askbloom','',$langfolder); ?>"><?php echo get_string('tabthinkingskills','askbloom','',$langfolder); ?></span></a></li>
    <li class="ui-corner-top ui-state-default"><a href="#tabs-2"><span title="<?php echo get_string('tabcontenthelp','askbloom','',$langfolder); ?>"><?php echo get_string('tabcontent','askbloom','',$langfolder); ?></span></a></li>
    <li class="ui-corner-top ui-state-default"><a href="#tabs-3"><span title="<?php echo get_string('tabresourceshelp','askbloom','',$langfolder); ?>"><?php echo get_string('tabresources','askbloom','',$langfolder); ?></span></a></li>
    <li class="ui-corner-top ui-state-default"><a href="#tabs-4"><span title="<?php echo get_string('tabproducthelp','askbloom','',$langfolder); ?>"><?php echo get_string('tabproduct','askbloom','',$langfolder); ?></span></a></li>
    <li class="ui-corner-top ui-state-default"><a href="#tabs-5"><span title="<?php echo get_string('tabgroupshelp','askbloom','',$langfolder); ?>"><?php echo get_string('tabgroups','askbloom','',$langfolder); ?></span></a></li>
  </ul>

	<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="tabs-1">
  	<?php echo get_string('taboneinstructions','askbloom','',$langfolder); ?>
      <ul id="ts" class="innerTabs">
        <li class="innerTab">
          <h2><span title="<?php echo get_string('titleremembering_en','askbloom','',$langfolder); ?>"><?php echo get_string('titleremembering','askbloom','',$langfolder); ?></span></h2>
          <ul>
            <li><span title="<?php echo get_string('rememberhelp','askbloom','',$langfolder); ?>"><?php echo get_string('remember','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('listhelp','askbloom','',$langfolder); ?>"><?php echo get_string('list','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('definehelp','askbloom','',$langfolder); ?>"><?php echo get_string('define','askbloom','',$langfolder); ?></span></li>
            <li class="hover"><span title="<?php echo get_string('statehelp','askbloom','',$langfolder); ?>"><?php echo get_string('state','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('repeathelp','askbloom','',$langfolder); ?>"><?php echo get_string('repeat','askbloom','',$langfolder); ?></span></li>
            <li class="last"><span title="<?php echo get_string('duplicatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('duplicate','askbloom','',$langfolder); ?></span></li>
          </ul>
        </li>
        <li class="innerTab">
          <h2><span title="<?php echo get_string('titleunderstanding_en','askbloom','',$langfolder); ?>"><?php echo get_string('titleunderstanding','askbloom','',$langfolder); ?></span></h2>
          <ul>
            <li><span title="<?php echo get_string('classifyhelp','askbloom','',$langfolder); ?>"><?php echo get_string('classify','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('describehelp','askbloom','',$langfolder); ?>"><?php echo get_string('describe','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('discusshelp','askbloom','',$langfolder); ?>"><?php echo get_string('discuss','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('explainhelp','askbloom','',$langfolder); ?>"><?php echo get_string('explain','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('identifyhelp','askbloom','',$langfolder); ?>"><?php echo get_string('identify','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('locatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('locate','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('recognizehelp','askbloom','',$langfolder); ?>"><?php echo get_string('recognize','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('reporthelp','askbloom','',$langfolder); ?>"><?php echo get_string('report','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('selecthelp','askbloom','',$langfolder); ?>"><?php echo get_string('select','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('translatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('translate','askbloom','',$langfolder); ?></span></li>
            <li class="last"><span title="<?php echo get_string('paraphrasehelp','askbloom','',$langfolder); ?>"><?php echo get_string('paraphrase','askbloom','',$langfolder); ?></span></li>
          </ul>
        </li>
        <li class="innerTab">
          <h2><span title="<?php echo get_string('titleapplyhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titleapply','askbloom','',$langfolder); ?></span></h2>
          <ul>
            <li><span title="<?php echo get_string('choosehelp','askbloom','',$langfolder); ?>"><?php echo get_string('choose','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('demonstratehelp','askbloom','',$langfolder); ?>"><?php echo get_string('demonstrate','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('employhelp','askbloom','',$langfolder); ?>"><?php echo get_string('employ','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('illustratehelp','askbloom','',$langfolder); ?>"><?php echo get_string('illustrate','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('interprethelp','askbloom','',$langfolder); ?>"><?php echo get_string('interpret','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('operatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('operate','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('sketchhelp','askbloom','',$langfolder); ?>"><?php echo get_string('sketch','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('solvehelp','askbloom','',$langfolder); ?>"><?php echo get_string('solve','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('usehelp','askbloom','',$langfolder); ?>"><?php echo get_string('use','askbloom','',$langfolder); ?></span></li>
            <li class="last"><span title="<?php echo get_string('schedulehelp','askbloom','',$langfolder); ?>"><?php echo get_string('schedule','askbloom','',$langfolder); ?></span></li>
          </ul>
        </li>
        <li class="innerTab">
          <h2><span title="<?php echo get_string('titleanalyzehelp','askbloom','',$langfolder); ?>"><?php echo get_string('titleanalyze','askbloom','',$langfolder); ?></span></h2>
          <ul>
            <li><span title="<?php echo get_string('apprisehelp','askbloom','',$langfolder); ?>"><?php echo get_string('apprise','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('comparehelp','askbloom','',$langfolder); ?>"><?php echo get_string('compare','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('contrasthelp','askbloom','',$langfolder); ?>"><?php echo get_string('contrast','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('criticizehelp','askbloom','',$langfolder); ?>"><?php echo get_string('criticize','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('differentiatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('differentiate','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('discriminatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('discriminate','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('distinguishhelp','askbloom','',$langfolder); ?>"><?php echo get_string('distinguish','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('examinehelp','askbloom','',$langfolder); ?>"><?php echo get_string('examine','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('experimenthelp','askbloom','',$langfolder); ?>"><?php echo get_string('experiment','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('questionhelp','askbloom','',$langfolder); ?>"><?php echo get_string('question','askbloom','',$langfolder); ?></span></li>
            <li class="last"><span title="<?php echo get_string('testhelp','askbloom','',$langfolder); ?>"><?php echo get_string('test','askbloom','',$langfolder); ?></span></li>
          </ul>
        </li>
        <li class="innerTab">
          <h2><span title="<?php echo get_string('titleevaluatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('titleevaluate','askbloom','',$langfolder); ?></span></h2>
          <ul>
            <li><span title="<?php echo get_string('apprisehelp','askbloom','',$langfolder); ?>"><?php echo get_string('apprise','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('arguehelp','askbloom','',$langfolder); ?>"><?php echo get_string('argue','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('contrasthelp','askbloom','',$langfolder); ?>"><?php echo get_string('contrast','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('defendhelp','askbloom','',$langfolder); ?>"><?php echo get_string('defend','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('judgehelp','askbloom','',$langfolder); ?>"><?php echo get_string('judge','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('selecthelp','askbloom','',$langfolder); ?>"><?php echo get_string('select','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('supporthelp','askbloom','',$langfolder); ?>"><?php echo get_string('support','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('valuehelp','askbloom','',$langfolder); ?>"><?php echo get_string('value','askbloom','',$langfolder); ?></span></li>
            <li class="last"><span title="<?php echo get_string('evaluatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('evaluate','askbloom','',$langfolder); ?></span></li>
          </ul>
        </li>
        <li class="innerTab">
          <h2><span title="<?php echo get_string('titlecreatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('titlecreate','askbloom','',$langfolder); ?></span></h2>
          <ul>
            <li><span title="<?php echo get_string('assemblehelp','askbloom','',$langfolder); ?>"><?php echo get_string('assemble','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('constructhelp','askbloom','',$langfolder); ?>"><?php echo get_string('construct','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('createhelp','askbloom','',$langfolder); ?>"><?php echo get_string('create','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('designhelp','askbloom','',$langfolder); ?>"><?php echo get_string('design','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('develophelp','askbloom','',$langfolder); ?>"><?php echo get_string('develop','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('formulatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('formulate','askbloom','',$langfolder); ?></span></li>
            <li class="last"><span title="<?php echo get_string('writehelp','askbloom','',$langfolder); ?>"><?php echo get_string('write','askbloom','',$langfolder); ?></span></li>
          </ul>
        </li>
      </ul>
    <div class="bottom"></div>
	</div>

	<div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-2">
     <?php echo get_string('tabtwoinstructions','askbloom','',$langfolder); ?>
		  <ul id="c" class="innerTabs">
        <li class="innerTab">
            <h2><span title="<?php echo get_string('titledepthhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titledepth','askbloom','',$langfolder); ?></span></h2>
            <ul>
                <li><span title="<?php echo get_string('bigideahelp','askbloom','',$langfolder); ?>"><?php echo get_string('bigidea','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('unansweredquestionshelp','askbloom','',$langfolder); ?>"><?php echo get_string('unansweredquestions','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('ethicshelp','askbloom','',$langfolder); ?>"><?php echo get_string('ethics','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('patternshelp','askbloom','',$langfolder); ?>"><?php echo get_string('patterns','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('ruleshelp','askbloom','',$langfolder); ?>"><?php echo get_string('rules','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('languageofthedisciplinehelp','askbloom','',$langfolder); ?>"><?php echo get_string('languageofthediscipline','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('essentialdetailshelp','askbloom','',$langfolder); ?>"><?php echo get_string('essentialdetails','askbloom','',$langfolder); ?></span></li>
                <li class="last"><span title="<?php echo get_string('trendshelp','askbloom','',$langfolder); ?>"><?php echo get_string('trends','askbloom','',$langfolder); ?></span></li>
            </ul>
        </li>
        <li class="innerTab">
          <h2><span title="<?php echo get_string('titlecomplexityhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titlecomplexity','askbloom','',$langfolder); ?></span></h2>
            <ul>
                <li><span title="<?php echo get_string('multiplepovhelp','askbloom','',$langfolder); ?>"><?php echo get_string('multiplepov','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('changeovertimehelp','askbloom','',$langfolder); ?>"><?php echo get_string('changeovertime','askbloom','',$langfolder); ?></span></li>
                <li class="last"><span title="<?php echo get_string('accrossthedisiplinehelp','askbloom','',$langfolder); ?>"><?php echo get_string('accrossthedisipline','askbloom','',$langfolder); ?></span></li>
            </ul>
        </li>
        <li class="innerTab">
         <h2><span title="<?php echo get_string('titleimperativeshelp','askbloom','',$langfolder); ?>"><?php echo get_string('titleimperatives','askbloom','',$langfolder); ?></span></h2>
            <ul>
                <li><span title="<?php echo get_string('originhelp','askbloom','',$langfolder); ?>"><?php echo get_string('origin','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('convergencehelp','askbloom','',$langfolder); ?>"><?php echo get_string('convergence','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('parallelshelp','askbloom','',$langfolder); ?>"><?php echo get_string('parallels','askbloom','',$langfolder); ?></span></li>
                <li><span title="<?php echo get_string('paradoxhelp','askbloom','',$langfolder); ?>"><?php echo get_string('paradox','askbloom','',$langfolder); ?></span></li>
                <li class="last"><span title="<?php echo get_string('contributionhelp','askbloom','',$langfolder); ?>"><?php echo get_string('contribution','askbloom','',$langfolder); ?></span></li>
            </ul>
        </li>
      </ul>
    <div class="bottom"></div>
	</div>

	<div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-3">
    <?php echo get_string('tabthreeinstructions','askbloom','',$langfolder); ?>
    <ul id="r" class="innerTabs">
      <li class="innerTab">
        <h2><span title="<?php echo get_string('titlevoicehelp','askbloom','',$langfolder); ?>"><?php echo get_string('titlevoice','askbloom','',$langfolder); ?></span></h2>
        <ul>
          <li><span title="<?php echo get_string('recordinghelp','askbloom','',$langfolder); ?>"><?php echo get_string('recording','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('musiccdhelp','askbloom','',$langfolder); ?>"><?php echo get_string('musiccd','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('tvshowhelp','askbloom','',$langfolder); ?>"><?php echo get_string('tvshow','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('interviewhelp','askbloom','',$langfolder); ?>"><?php echo get_string('interview','askbloom','',$langfolder); ?></span></li>
          <li class="last"><span title="<?php echo get_string('radioshowhelp','askbloom','',$langfolder); ?>"><?php echo get_string('radioshow','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
      <li class="innerTab">
        <h2><span title="<?php echo get_string('titlenotdigitalhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titlenotdigital','askbloom','',$langfolder); ?></span></h2>
        <ul>
          <li><span title="<?php echo get_string('bookhelp','askbloom','',$langfolder); ?>"><?php echo get_string('book','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('magazinehelp','askbloom','',$langfolder); ?>"><?php echo get_string('magazine','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('articlehelp','askbloom','',$langfolder); ?>"><?php echo get_string('article','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('newspaperhelp','askbloom','',$langfolder); ?>"><?php echo get_string('newspaper','askbloom','',$langfolder); ?></span></li>
          <li class="last"><span title="<?php echo get_string('encyclopediahelp','askbloom','',$langfolder); ?>"><?php echo get_string('encyclopedia','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
      <li class="innerTab">
        <h2><span title="<?php echo get_string('titledigitalhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titledigital','askbloom','',$langfolder); ?></span></h2>
        <ul>
          <li><span title="<?php echo get_string('websitehelp','askbloom','',$langfolder); ?>"><?php echo get_string('website','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('wikipediahelp','askbloom','',$langfolder); ?>"><?php echo get_string('wikipedia','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('onlineencyclopediahelp','askbloom','',$langfolder); ?>"><?php echo get_string('onlineencyclopedia','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('bloghelp','askbloom','',$langfolder); ?>"><?php echo get_string('blog','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('facebookhelp','askbloom','',$langfolder); ?>"><?php echo get_string('facebook','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('twitterhelp','askbloom','',$langfolder); ?>"><?php echo get_string('twitter','askbloom','',$langfolder); ?></span></li>
          <li class="last"><span title="<?php echo get_string('onlinearticlehelp','askbloom','',$langfolder); ?>"><?php echo get_string('onlinearticle','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
    </ul>
    <div class="bottom"></div>
	</div>

	<div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-4">
    <?php echo get_string('tabfourinstructions','askbloom','',$langfolder); ?>
    <ul id="p" class="innerTabs">
      <li class="innerTab">
        <h2><span title="<?php echo get_string('titlevisualhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titlevisual','askbloom','',$langfolder); ?></span></h2>
        <ul>
            <li><span title="<?php echo get_string('charthelp','askbloom','',$langfolder); ?>"><?php echo get_string('chart','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('drawinghelp','askbloom','',$langfolder); ?>"><?php echo get_string('drawing','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('timelinehelp','askbloom','',$langfolder); ?>"><?php echo get_string('timeline','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('diagramhelp','askbloom','',$langfolder); ?>"><?php echo get_string('diagram','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('graphicorganizerhelp','askbloom','',$langfolder); ?>"><?php echo get_string('graphicorganizer','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('maphelp','askbloom','',$langfolder); ?>"><?php echo get_string('map','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('comichelp','askbloom','',$langfolder); ?>"><?php echo get_string('comic','askbloom','',$langfolder); ?></span></li>
            <li><span title="<?php echo get_string('bookcoverhelp','askbloom','',$langfolder); ?>"><?php echo get_string('bookcover','askbloom','',$langfolder); ?></span></li>
            <li class="last"><span title="<?php echo get_string('posterhelp','askbloom','',$langfolder); ?>"><?php echo get_string('poster','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
      <li class="innerTab">
        <h2><span title="<?php echo get_string('titleconstructhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titleconstruct','askbloom','',$langfolder); ?></span></h2>
        <ul>
          <li><span title="<?php echo get_string('modelhelp','askbloom','',$langfolder); ?>"><?php echo get_string('model','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('sculpturehelp','askbloom','',$langfolder); ?>"><?php echo get_string('sculpture','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('dioramahelp','askbloom','',$langfolder); ?>"><?php echo get_string('diorama','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('miniaturehelp','askbloom','',$langfolder); ?>"><?php echo get_string('miniature','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('artgalleryhelp','askbloom','',$langfolder); ?>"><?php echo get_string('artgallery','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('museumexhibithelp','askbloom','',$langfolder); ?>"><?php echo get_string('museumexhibit','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('mobilehelp','askbloom','',$langfolder); ?>"><?php echo get_string('mobile','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('collagehelp','askbloom','',$langfolder); ?>"><?php echo get_string('collage','askbloom','',$langfolder); ?></span></li>
          <li class="last"><span title="<?php echo get_string('mosaichelp','askbloom','',$langfolder); ?>"><?php echo get_string('mosaic','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
      <li class="innerTab">
        <h2><span title="<?php echo get_string('titleoralhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titleoral','askbloom','',$langfolder); ?></span></h2>
        <ul>
          <li><span title="<?php echo get_string('debatehelp','askbloom','',$langfolder); ?>"><?php echo get_string('debate','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('paneldiscussionhelp','askbloom','',$langfolder); ?>"><?php echo get_string('paneldiscussion','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('lessonhelp','askbloom','',$langfolder); ?>"><?php echo get_string('lesson','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('reporthelp','askbloom','',$langfolder); ?>"><?php echo get_string('report','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('playhelp','askbloom','',$langfolder); ?>"><?php echo get_string('play','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('readerstheatrehelp','askbloom','',$langfolder); ?>"><?php echo get_string('readerstheatre','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('pressconferencehelp','askbloom','',$langfolder); ?>"><?php echo get_string('pressconference','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('talkshowhelp','askbloom','',$langfolder); ?>"><?php echo get_string('talkshow','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('monologuehelp','askbloom','',$langfolder); ?>"><?php echo get_string('monologue','askbloom','',$langfolder); ?></span></li>
          <li class="last"><span title="<?php echo get_string('siskelroperreviewhelp','askbloom','',$langfolder); ?>"><?php echo get_string('siskelroperreview','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
      <li class="innerTab">
        <h2><span title="<?php echo get_string('titlemultimediahelp','askbloom','',$langfolder); ?>"><?php echo get_string('titlemultimedia','askbloom','',$langfolder); ?></span></h2>
        <ul>
          <li><span title="<?php echo get_string('songhelp','askbloom','',$langfolder); ?>"><?php echo get_string('song','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('illustratedbookhelp','askbloom','',$langfolder); ?>"><?php echo get_string('illustratedbook','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('newspaperhelp','askbloom','',$langfolder); ?>"><?php echo get_string('newspaper','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('tvshowhelp','askbloom','',$langfolder); ?>"><?php echo get_string('tvshow','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('presentationhelp','askbloom','',$langfolder); ?>"><?php echo get_string('presentation','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('videopoetryhelp','askbloom','',$langfolder); ?>"><?php echo get_string('videopoetry','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('photoessayhelp','askbloom','',$langfolder); ?>"><?php echo get_string('photoessay','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('videotraveloguehelp','askbloom','',$langfolder); ?>"><?php echo get_string('videotravelogue','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('newsreporthelp','askbloom','',$langfolder); ?>"><?php echo get_string('newsreport','askbloom','',$langfolder); ?></span></li>
          <li class="last"><span title="<?php echo get_string('webpagehelp','askbloom','',$langfolder); ?>"><?php echo get_string('webpage','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
      <li class="innerTab">
        <h2><span title="<?php echo get_string('titlewritenhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titlewriten','askbloom','',$langfolder); ?></span></h2>
        <ul>
          <li><span title="<?php echo get_string('responsetolitreturehelp','askbloom','',$langfolder); ?>"><?php echo get_string('responsetolitreture','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('reporthelp','askbloom','',$langfolder); ?>"><?php echo get_string('report','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('articlehelp','askbloom','',$langfolder); ?>"><?php echo get_string('article','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('persuasiveessayhelp','askbloom','',$langfolder); ?>"><?php echo get_string('persuasiveessay','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('sequelhelp','askbloom','',$langfolder); ?>"><?php echo get_string('sequel','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('letterhelp','askbloom','',$langfolder); ?>"><?php echo get_string('letter','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('childrenstoryhelp','askbloom','',$langfolder); ?>"><?php echo get_string('childrenstory','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('poemsonghelp','askbloom','',$langfolder); ?>"><?php echo get_string('poemsong','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('eulogyhelp','askbloom','',$langfolder); ?>"><?php echo get_string('eulogy','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('diaryhelp','askbloom','',$langfolder); ?>"><?php echo get_string('diary','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('reviewhelp','askbloom','',$langfolder); ?>"><?php echo get_string('review','askbloom','',$langfolder); ?></span></li>
          <li class="last"><span title="<?php echo get_string('storyinanewgenrehelp','askbloom','',$langfolder); ?>"><?php echo get_string('storyinanewgenre','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
    </ul>
    <div class="bottom"></div>
  </div>

  <div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-5">
    <ul id="g" class="innerTabs">
      <li class="innerTab">
        <?php echo get_string('tabfiveinstructions','askbloom','',$langfolder); ?>
        <h2><span title="<?php echo get_string('titlegroupsofhelp','askbloom','',$langfolder); ?>"><?php echo get_string('titlegroupsof','askbloom','',$langfolder); ?></span></h2>
        <ul>
          <li><span title="<?php echo get_string('onehelp','askbloom','',$langfolder); ?>"><?php echo get_string('one','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('twohelp','askbloom','',$langfolder); ?>"><?php echo get_string('two','askbloom','',$langfolder); ?></span></li>
          <li><span title="<?php echo get_string('threehelp','askbloom','',$langfolder); ?>"><?php echo get_string('three','askbloom','',$langfolder); ?></span></li>
          <li class="last"><span title="<?php echo get_string('fourhelp','askbloom','',$langfolder); ?>"><?php echo get_string('four','askbloom','',$langfolder); ?></span></li>
        </ul>
      </li>
    </ul>
  <div class="bottom"></div>
	</div>
</div> <!--End Of Tabs-->


</body></html>