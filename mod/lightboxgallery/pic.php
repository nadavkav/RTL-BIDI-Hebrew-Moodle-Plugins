<?php

    require_once('../../config.php');
    require_once($CFG->libdir . '/filelib.php');
    require_once('lib.php');

    disable_debugging();

    $argument = get_file_argument('pic.php');
    $thumb = optional_param('thumb', 0, PARAM_BOOL);
    $forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);

    if (! isset($CFG->filelifetime)) {
        $lifetime = 86400;     // Seconds for files to remain in caches
    } else {
        $lifetime = $CFG->filelifetime;
    }

    if (! $argument) {
        error('No valid arguments supplied or incorrect server configuration');
    } else if ($argument{0} != '/') {
        error('No valid arguments supplied, path does not start with slash!');
    }

    $args = explode('/', trim($argument, '/'));
    if (count($args) < 2) {
        error('Not enough valid arguments supplied');
    }

    if (! $gallery = get_record('lightboxgallery', 'id', $args[0])) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', 'id', $gallery->course)) {
        error('Course is misconfigured');
    }

    if (! ($gallery->public || (lightboxgallery_rss_enabled() && $gallery->rss))) {
        require_login($course->id);
    }

    $filename = clean_param($args[1], PARAM_PATH);

    if ($thumb) {
        $path = $CFG->dataroot . '/' . $course->id . '/' . $gallery->folder . '/_thumb/' . $filename . '.jpg';
    } else {
        $path = $CFG->dataroot . '/' . $course->id . '/' . $gallery->folder . '/' . $filename;
    }

    if (! file_exists($path)) {
        header('HTTP/1.0 404 not found');
        print_error('filenotfound', 'error', $CFG->wwwroot . '/course/view.php?id=' . $course->id);
    }

    session_write_close();

    send_file($path, $filename, $lifetime, $CFG->filteruploadedfiles, false, $forcedownload);

?>
