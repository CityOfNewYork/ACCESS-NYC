<?php

// DO NOT MODIFY THIS FILE!
// Use your theme's functions.php instead

/**
 * An alternate to using do_shortcode()
 * @since 1.7.5
 */
function facetwp_display() {
    $args = func_get_args();
    $atts = isset( $args[1] ) ?
        array( $args[0] => $args[1] ) :
        array( $args[0] => true );

    return FWP()->display->shortcode( $atts );
}


/**
 * Allow for translation of dynamic strings
 * @since 2.1
 */
function facetwp_i18n( $string ) {
    return apply_filters( 'facetwp_i18n', $string );
}
