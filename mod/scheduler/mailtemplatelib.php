<?php
/**
* @package mod-scheduler
* @category mod
* @author Valery Fremaux (admin@ethnoinformatique.fr)
*/

/*
* index of functions
function compile_mail_template($template, $infomap, $module = 'scheduler') {
function get_mail_template($virtual, $modulename, $lang = ''){
*/

/**
* useful templating functions from an older project of mine, hacked for Moodle
* @param template the template's file name from $CFG->sitedir
* @param infomap a hash containing pairs of parm => data to replace in template
* @return a fully resolved template where all data has been injected
*/
function compile_mail_template($template, $infomap, $module = 'scheduler') {
    $notification = implode('', get_mail_template($template, $module));
    foreach($infomap as $aKey => $aValue){
        $notification = str_replace("<%%$aKey%%>", $aValue, $notification);
    }
    return $notification;
}

/*
* resolves and get the content of a Mail template, acoording to the user's current language.
* @param virtual the virtual mail template name
* @param module the current module
* @param lang if default language must be overriden
* @return string the template's content or false if no template file is available
*/
function get_mail_template($virtual, $modulename, $lang = ''){
   global $CFG;

   if ($lang == '') {
      $lang = $CFG->lang;
   }
   $templateName = "{$CFG->dirroot}/mod/{$modulename}/mails/{$lang}/{$virtual}.tpl";
   if (file_exists($templateName))
      return file($templateName);
   notice("template $templateName not found");
   return array();
}
?>