<?php

function pmxe_wp_ajax_dismiss_review_modal(){

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	if ( ! current_user_can( PMXE_Plugin::$capabilities ) ){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	$reviewLogic = new \Wpae\Reviews\ReviewLogic();
    $reviewLogic->dismissNotice();

	exit(json_encode(array('result' => true)));
}