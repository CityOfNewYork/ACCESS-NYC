<?php
global $wpdb;

$basket_ajax = new WPML_Basket_Tab_Ajax(
	TranslationProxy::get_current_project(),
	wpml_tm_load_basket_networking(),
	new WPML_Translation_Basket( $wpdb )
);
add_action( 'init', array( $basket_ajax, 'init' ) );
