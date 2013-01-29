<?php

/**
 * Project: Xcelerate WP Plugin
 * @author Eric
 */
function excel_array_find($array, $key, $default = FALSE) {
    if (!array_key_exists($key, $array))
        return $default;
    return $array[$key];
}

function excel_req($key, $default = FALSE) {
    return excel_array_find($_REQUEST, $key, $default);
}

?>
