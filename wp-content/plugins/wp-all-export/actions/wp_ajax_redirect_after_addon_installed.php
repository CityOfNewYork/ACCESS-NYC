<?php

function pmxe_wp_ajax_redirect_after_addon_installed(){

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	if ( ! current_user_can( PMXE_Plugin::$capabilities ) ){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

    $input = new PMXE_Input();

    $addon = $input->post('addon', false);

    $result = false;

    if ($addon && defined( 'WP_PLUGIN_DIR' )) {
        $result = file_exists(trailingslashit(WP_PLUGIN_DIR) . $addon);
    }
	
	exit(json_encode(array('result' => $result)));
}