<?php
    // check/set default config settings for question plugin
    // $forcereset is set in calling routine
    
    if (!isset($forcereset)) {
        $forcereset = false;
    }
    
   if (!isset($CFG->filter_question_plugin_enable) or $forcereset) {
        if (isset($CFG->filter_question_plugin_ignore)) {
            set_config( 'filter_question_plugin_enable', !$CFG->filter_question_plugin_ignore );
            set_config( 'filter_question_plugin_ignore', '' );
        }
        else {
            set_config( 'filter_question_plugin_enable', 1 );
        }
    }
  

?>
