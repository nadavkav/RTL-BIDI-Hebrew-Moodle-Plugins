<?php
/**
 * Created by Nadav Kavalerchik.
 * Contact info: nadavkav@gmail.com
 * Date: 1/15/11 Time: 10:53 PM
 *
 * Description:
 *    	wrapper for the WIRIS plugin
 *	http://www.wiris.com/en/plugins/docs/moodle/install/install-manually-version-2-3
 */

require_once("../../../../../config.php");

$courseid = optional_param('id', SITEID, PARAM_INT);

include_once("wrs_plugin.js.php");

?>



function __wiris_formula (editor) {

    // Init WIRIS, if not initiated, yet.
    if(typeof window.WirisImgCache =="undefined"){
        Wiris(editor.config);
    }

    Wiris.buttonPress(editor, 'WRS-wiris-formula');

}