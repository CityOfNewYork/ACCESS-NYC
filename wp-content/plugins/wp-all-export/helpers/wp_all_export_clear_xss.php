<?php

if ( ! function_exists('wp_all_export_clear_xss') ) {

    function wp_all_export_clear_xss( $str ) {

        return stripslashes(esc_sql(htmlspecialchars(strip_tags($str))));
    }
}