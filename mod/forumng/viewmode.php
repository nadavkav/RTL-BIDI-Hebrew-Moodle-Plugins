<?php
/**
 * Switch between simple view and standard view for accessibility purpose
 * @see forum
 * @package forumng
 * @author Ray Guo
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @copyright Copyright 2009 The Open University
 */

require_once('../../config.php');
require_login();

$viewmode = required_param('simple', PARAM_INT);
if($viewmode) {
    set_user_preference('forumng_simplemode','y');
} else {
    unset_user_preference('forumng_simplemode');
}

// Redirect back
redirect($_SERVER['HTTP_REFERER']);
?>