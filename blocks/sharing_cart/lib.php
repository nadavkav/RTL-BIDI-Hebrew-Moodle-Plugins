<?php

// UTF-8 -> JavaScript
function jsurlencode($s) {
    $r = array();
    foreach (unpack('n*', mb_convert_encoding($s, 'UTF-16BE', 'UTF-8')) as $c)
        $r[] = $c < 0x80 ? chr($c) : sprintf('%%u%04X', $c);
    return implode('', $r);
}

// JavaScript -> UTF-8
function jsurldecode($s) {
    return preg_replace_callback('|%u([\dA-F]{2})([\dA-F]{2})|i',
        create_function('$m', 'return mb_convert_encoding(
            pack("CC", hexdec($m[1]), hexdec($m[2])), "UTF-8", "UTF-16BE");'), $s);
}

?>