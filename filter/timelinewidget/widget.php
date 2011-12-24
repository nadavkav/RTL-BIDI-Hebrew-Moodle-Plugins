<?php
/** Iframe handler for Timeline Widget filter.
 *
 *    An <iframe> is currently the only solution I have to limitations of
 * Moodle, SIMILE & Internet Explorer. SIMILE on MSIE requires <script> tags in
 * the page <head>, which AFAIK isn't possible for a filter in Moodle 1.9 and 2.
 *
 * @copyright (c) 2010 Nicholas Freear.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
ini_set('display_errors', 1);
require_once('../../config.php');

    $config  = required_param('conf', PARAM_RAW);

    $defaults = array(
        'id'=>'tlw0',
        'title'=>get_string('defaulttitle', 'filter_timelinewidget'),
        'date' =>1900,
        'intervalUnit'=>'DECADE',
        'intervalPixels'=>35);

    $config = (object) array_merge($defaults, $config);

    if (file_exists("$CFG->dirroot/lib/yui/2.8.2/build")) {
        // Moodle 2.x.
        $yui_root= "$CFG->wwwroot/lib/yui/2.8.2/build";
    } else {
        // Or Moodle 1.9.x
        $yui_root= "$CFG->wwwroot/lib/yui";
    }
    $tl_root = "$CFG->wwwroot/filter/timelinewidget";


    $js_load = $alt_link = NULL;
    if (isset($config->dataUrl)) { //XML.
        // Handle relative URLs. They must start with a course/resource ID, eg. '2/timeline-invent.xml'
        if (0!==strpos($config->dataUrl, '/')
          && 0!==stripos($config->dataUrl, 'http://')) {
          if (file_exists("$CFG->dirroot/draftfile.php")) {
              // Moodle 2.x.
              if (preg_match('#\d+\/[a-z_]+\/content\/\d+\/.+\.#', $config->dataUrl)) {
                //dataUrl = 60/mod_resource/content/1/simile-invent.xml
                $config->dataUrl="$CFG->wwwroot/pluginfile.php/$config->dataUrl";
              }
              elseif (preg_match('#\d+\/user\/draft\/\d+\/.+\.#', $config->dataUrl)) {
                //dataUrl = 14/user/draft/403117530/simile-invent.xml
                $config->dataUrl="$CFG->wwwroot/draftfile.php/$config->dataUrl";
              } else {
                  print_error('errorindataurl', 'filter_timelinewidget');
              }
          } else { // Moodle 1.8-1.9.x
              $config->dataUrl = "$CFG->wwwroot/file.php/$config->dataUrl";
          }
        }
        debugging($config->dataUrl, DEBUG_DEVELOPER);
        $label = get_string('xmltimelinedata', 'filter_timelinewidget');
        $js_load = <<<EOS
    TLW.tl.loadXML("$config->dataUrl?"+ (new Date().getTime()),
            function(xml, url) { eventSource.loadXML(xml, url); });
EOS;
    }
    elseif (isset($config->dataId)) { //JSON.
        $label = get_string('datasource', 'filter_timelinewidget');
        $js_load = <<<EOS
    TLW.tl.loadJSON("$tl_root/json.php?mid=$config->dataId&r="+ (new Date().getTime()),
            function(json, url) { eventSource.loadJSON(json, url); });
EOS;
    } else { //Error.
        print_error('errordataurloridrequired', 'filter_timelinewidget');
    }

    $filtername = get_string('filtername','filter_timelinewidget');
    $skip_label = get_string('skiplink', 'filter_timelinewidget');
    $loading = get_string('loading', 'filter_timelinewidget');
    $noscript= get_string('noscript', 'filter_timelinewidget');

    // For now, we embed the Javascript inline.
    $newtext = <<<EOF

<!--<script src="$tl_root/tlw-script.js"></script>-->
<script>
var Timeline_ajax_url ="$tl_root/timeline_ajax/simile-ajax-api.js";
var Timeline_urlPrefix="$tl_root/timeline_js/";
var Timeline_parameters="bundle=true";
//TLW.include('js', {'inner': TLW.config});
</script>
<script src="$tl_root/timeline_js/timeline-api.js"></script>
<script src="$yui_root/yahoo/yahoo-min.js"></script>
<script src="$yui_root/event/event-min.js"></script>
<script>
var TLW = TLW || {};
TLW.tl = null;
TLW.onLoad = function() {
    var eventSource = new Timeline.DefaultEventSource();
    var d = Timeline.DateTime.parseGregorianDateTime("$config->date");
    var bandInfos = [
        Timeline.createBandInfo({
            eventSource:    eventSource,
            date:           d,
            width:          "100%", //"70%",
            intervalUnit:   Timeline.DateTime.$config->intervalUnit,
            intervalPixels: $config->intervalPixels
        }) //Was: , bug (MSIE).
    ];

    TLW.tl = Timeline.create(document.getElementById("$config->id"), bandInfos);
$js_load

};
TLW.resizeTimer = null;
TLW.onResize = function() {
  if (TLW.resizeTimer === null) {
    TLW.resizeTimer = window.setTimeout(function() {
      TLW.resizeTimer = null;
      TLW.tl.layout();
    }, 500); //milliseconds.
  }
};
YAHOO.util.Event.onDOMReady(window.setTimeout(TLW.onLoad, 2000)); //500.
window.onresize = TLW.onResize;
</script>

<body>

<div id="$config->id" class="timeline-default">
  <p>$loading</p><p>$noscript</p>
</div>

</body>
EOF;

?>
<!DOCTYPE html><html <?php echo get_html_lang() ?>><meta charset="utf-8" /><title><?php
  echo $config->title; ?> | <?php echo $filtername; ?></title>
<style>
body{margin:0; font:.85em sans-serif;}
.timeline-date-label{font-size:.94em;}
.tl-widget-skip{display:inline-block; width:1px; height:1em; overflow:hidden;}
.tl-widget-skip:focus, .tl-widget-skip:active{width:auto; overflow:visible;}
.timeline-default{
  width:100%; min-width:250px; min-height:200px; position:fixed !important; top:0; bottom:0;
}
.timeline-default p{text-align:center;}
</style>
<!--[if IE]>
<style>.X-timeline-default{height:200px;}</style>
<![endif]-->
  <?php echo $newtext; ?>
</html>

<?php
/*object(stdClass)#2778 (6) {
  ["id"]=> "tlw0"
  ["title"]=> "Important inventions timeline"
  ["date"]=> "1870"
  ["intervalUnit"]=> "CENTURY"
  ["intervalPixels"]=> int(35)
  ["dataUrl"]=> string(43)"60/mod_resource/content/1/simile-invent.xml"
}
http://my.school/moodle2/filter/timelinewidget/widget.php?conf%5Btitle%5D=Important+inventions+timeline&conf%5Bdate%5D=1870&conf%5BintervalUnit%5D=CENTURY&conf%5BintervalPixels%5D=35&conf%5BdataUrl%5D=60/mod_resource/content/1/simile-invent.xml
*/
