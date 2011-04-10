<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 4/4/11 Time: 11:01 PM
 *
 * Description:
 *
 */
 
  require_once("../../../../../config.php");
  $glitemid = optional_param('glitemid', 0, PARAM_INT);

  //require_login(); // make sure SESSION timeout is updated
  $sql = 'SELECT concept, definition, format '.
         'FROM '.$CFG->prefix.'glossary_entries '.
         'WHERE id = '.$glitemid;


  if ($entry = get_records_sql($sql)) {

      $entry = reset($entry);

      $options = new object;
      $options->trusttext = true;
      echo format_text($entry->definition, $entry->format, $options);
  } else {
      echo get_string('noentriesyet','insertemplate','',$CFG->dirroot.'/lib/editor/htmlarea/custom_plugins/inserttemplate/lang/');
  }

?>