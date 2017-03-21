<?php
/**
 * INTERNATIONALIZATION / LOCALIZATION
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action( 'init', 'bodhi_svgs_localization' );

function bodhi_svgs_localization() {
	load_plugin_textdomain( 'svg-support', false, basename( dirname( __FILE__ ) ) . '/languages' );

}

?>