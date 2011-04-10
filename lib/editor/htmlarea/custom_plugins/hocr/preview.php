<?php // $Id: preview.php,v 1.4 2007/01/27 23:23:44 skodak Exp $ preview for insert image dialog

    require("../../../../../config.php");
    global $USER;

    $id = optional_param('id', SITEID, PARAM_INT);
    $imageurl = required_param('imageurl', PARAM_RAW);

    require_login($id);
    require_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $id));

    @header('Content-Type: text/html; charset=utf-8');


    $imagetag = clean_text('<img src="'.htmlSpecialChars(stripslashes_safe($imageurl)).'" alt="" />');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo get_string('preview') ?></title>
<style type="text/css">
 body { margin: 2px; }
</style>
</head>
<body bgcolor="#ffffff">

<?php //echo $imagetag ?>
<?php

//if (!$basedir = mkdir($CFG->dataroot.'/temp/hocr')) {
  //error("The site administrator needs to fix the file permissions");
//}

echo "<br/>";
$imagefile = str_ireplace("$CFG->wwwroot/file.php/","",$imageurl);
$tempocrfile = $CFG->dataroot.'/temp/hocr/'.$USER->id.'_'.time().'.text';
echo "<br/>";

if (file_exists('/usr/bin/hocr')) {
  $exec_string = '/usr/bin/hocr -d --no-gtk -o '.$tempocrfile.' -i '.$CFG->dataroot.'/'.$imagefile;
  //$exec_string = '/usr/bin/hocr -h';
  //$exec_string = '/var/www/moodle-latest-stable/lib/editor/htmlarea/custom_plugins/hocr/hocr -h';
  echo $exec_string."<br/>";
  $ret = proc_exec($exec_string);

  //echo exec('whoami');
  //echo "HOCR - YES [$exec_string]<hr/>";
  //$hocrtext = file_get_contents($tempocrfile, FILE_USE_INCLUDE_PATH);
  //echo $hocrtext;
  //exec('rm -rf '.$toFile); // Linux specific!
}

/*
 * Wrapper for proc_*() functions.
 * The first parameter $cmd is the command line to execute.
 * Return the exit code of the process.
 */
function proc_exec($cmd)
{
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

    $ptr = proc_open($cmd, $descriptorspec, $pipes, NULL, $_ENV);
    if (!is_resource($ptr))
        return false;

    while (($buffer = fgets($pipes[1], 1024)) != NULL
            || ($errbuf = fgets($pipes[2], 1024)) != NULL) {
        if (!isset($flag)) {
            $pstatus = proc_get_status($ptr);
            $first_exitcode = $pstatus["exitcode"];
            $flag = true;
        }
        if (strlen($buffer))
            echo $buffer;
        if (strlen($errbuf))
            echo "ERR: " . $errbuf;
    }

    foreach ($pipes as $pipe)
        fclose($pipe);

    /* Get the expected *exit* code to return the value */
    $pstatus = proc_get_status($ptr);
    if (!strlen($pstatus["exitcode"]) || $pstatus["running"]) {
        /* we can trust the retval of proc_close() */
        if ($pstatus["running"])
            proc_terminate($ptr);
        $ret = proc_close($ptr);
    } else {
        if ((($first_exitcode + 256) % 256) == 255
                && (($pstatus["exitcode"] + 256) % 256) != 255)
            $ret = $pstatus["exitcode"];
        elseif (!strlen($first_exitcode))
            $ret = $pstatus["exitcode"];
        elseif ((($first_exitcode + 256) % 256) != 255)
            $ret = $first_exitcode;
        else
            $ret = 0; /* we "deduce" an EXIT_SUCCESS ;) */
        proc_close($ptr);
    }

    return ($ret + 256) % 256;
}

/* __init__ */
//if (isset($argv) && count($argv) > 1 && !empty($argv[1])) {
//    if (($ret = proc_exec($argv[1])) === false)
//        die("Error: not enough FD or out of memory.\n");
//    elseif ($ret == 127)
//        die("Command not found (returned by sh).\n");
//    else
//        exit($ret);
//}

?>

</body>
</html>
