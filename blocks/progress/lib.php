<?php

// Returns $def if $var is unset
function progress_default_value(&$var, $def = null) {
    return isset($var)?$var:$def;
}

?>
