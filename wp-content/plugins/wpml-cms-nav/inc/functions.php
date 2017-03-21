<?php
function wpml_cms_nav_js_escape($str){
    $str = esc_js($str);
    $str = htmlspecialchars_decode($str);
    return $str;
}
