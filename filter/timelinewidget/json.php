<?php
/**
 * Dynamically output SIMILE Timeline JSON, given a Moodle Database ID, and
 * the correct schema (effectively a json-feed for 'data'.)
 *
 * @author    Nick Freear.
 * @copyright Copyright (c) 2010 Nicholas Freear.
 * @license   http://gnu.org/copyleft/gpl.html
 */
/**
  Usage 2:

[Timeline]
; Alternatively, get data from Moodle.
dataSrc= mod/data
dataId = 4
;;wikiUrl= mod/forum/discuss.php?d=2&title=
;;wikiSection=?
; The following are the same as above.
date   = 1870
intervalUnit  = CENTURY
intervalPixels= 75
[/Timeline]

*/
ini_set('display_errors', 1);
require_once('../../config.php');

$module_id  = required_param('mid', PARAM_INT);  #'d=' or 'mid='?
#$module     = optional_param('m', 'mod/data', PARAM_RAW); #Or '=data'?
$json_pad   = optional_param('jsonpad', 'var timeline_data', PARAM_RAW);

$json_pad_var = FALSE;
if (preg_match('/^var [a-zA-Z_]+$/', $json_pad)) {
    #Variable.
    $json_pad_var = TRUE;
} elseif (preg_match('/^[a-zA-Z_]+$/', $json_pad)) {
    #Function name.
} else {
    #ERROR.
}

_timeline_get_data($module_id, $module=NULL);


function _timeline_get_data($data_id, $data_mod) {
    #$courseid= 2;

    if (is_numeric($data_id)) { // backwards compatibility
        $data = get_record('data', 'id', $data_id);
    }
    #ELSE: error?

    $name = $data->name;
    $intro= $data->intro;

    $data_fields = get_records("data_fields", "dataid", $data_id);

    $ids = array();
    foreach ($data_fields as $field) {
        $ids[$field->name] = $field->id;
        $fields[$field->id] = $field->name;
    }
    //Required.
    if (!isset($ids['title'])) {
        die("Error, 'title' is a required field.");
    }
    if (!isset($ids['start'])) {
        die("Error, 'start' (start-date) is a required field.");
    }

    $data_records= get_records("data_records", "dataid", $data_id);

    $events = array();
    foreach ($data_records as $record) {
        $record_id = $record->id;

        $data_content= get_records("data_content", "recordid", $record_id);
        $event = array();
        foreach ($data_content as $content) {

            // Optionally, append meta-data inc. comment-count to end of content, truncate? (+Prepend 'subTitle'? No!)

            $value   = $content->content;
            $field_id= $content->fieldid;
            $name = str_replace(' ','', trim($fields[$field_id])); #lcfirst?
            switch ($name) {
            case 'start': #Drop-thru.
            case 'end':
                $event[$name] = parse_date_to_iso($value);
                break;
            default:
                $event[$name] = $value;
                break;
            }
        }
        #$event['isDuration'] = true;
        $events[] = $event;
    }

    global $CFG;
    $url  = $CFG->wwwroot."/mod/data/view.php?d=$data_id&title=";
    $title= str_replace(' ', '_', $data->name);

    $timeline = array('dateTimeFormat'=>'iso8601', 'wikiURL'=>$url,
                      'wikiSection'=>$title, 'events'=>$events);

    @header("Content-Type: text/plain; charset=utf-8"); #application/json.

    $json = json_encode($timeline);
    $json = str_replace(array('{"', ',"'), array("{\n\"", ",\n\""), $json);

    // JSON-P I think!
    //echo "var timeline_data = $json;";
    echo $json;
}

function parse_date_to_iso($value) {
    //$iso = false;
    $iso = true;
    if (is_numeric($value) && $value < 1000) { #Just a year<1000, eg. 868?!
        $iso = sprintf('%04d', $value);
    } elseif (is_numeric($value) and !$iso) {
        $iso = $value;
    } else {
        $ts = strtotime($value);
        if (!$ts) {
            $iso = date('c',$value); //date('c', $ts);
        } elseif (function_exists('date_parse')) { #PHP 5.2+
            $p = (object) date_parse($value); #("December 5, 1822");
            $iso = sprintf("%04d-%02d-%02d", $p->year, $p->month, $p->day); #Hours..? #T.
        } else {
            $iso = $value; #Pray!
        }
        #$event[$name] = $ts ? date('c', $ts) : $value; #echo " C $ts;$value. ";
    }
    return $iso;
}
