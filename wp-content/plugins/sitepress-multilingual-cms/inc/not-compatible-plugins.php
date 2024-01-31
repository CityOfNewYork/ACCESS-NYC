<?php
$icl_ncp_plugins = array(
	'absolute-links/absolute-links-plugin.php',
	'cms-navigation/CMS-Navigation.php',
);
$active_plugins  = get_option( 'active_plugins' );

$icl_ncp_plugins = $active_plugins ? array_intersect( $icl_ncp_plugins, $active_plugins ) : [];

if ( ! empty( $icl_ncp_plugins ) ) {
	$icl_sitepress_disabled = true;
	icl_suppress_activation();


	add_action( 'admin_notices', 'icl_incomp_plugins_warn' );
	function icl_incomp_plugins_warn() {
		global $icl_ncp_plugins;
		echo '<div class="error"><ul><li><strong>';
		esc_html_e( 'WPML cannot be activated together with these older plugins:', 'sitepress' );
		echo '<ul style="list-style:disc;margin:20px;">';
		foreach ( $icl_ncp_plugins as $incp ) {
			echo '<li>' . esc_html( $incp ) . '</li>';
		}
		echo '</ul>';
		esc_html_e( 'WPML will be deactivated', 'sitepress' );
		echo '</strong></li></ul></div>';
	}
} else {
	$icl_sitepress_disabled = false;
}

$filtered_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
if ( 0 === strcmp( $filtered_page, WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' ) || isset( $pagenow ) && $pagenow == 'index.php' ) {
	$icl_ncp_plugins2 = array(
		'wp-no-category-base/no-category-base.php',
	);
	$active_plugins   = get_option( 'active_plugins' );
	$icl_ncp_plugins2 = array_intersect( $icl_ncp_plugins2, $active_plugins );
	if ( ! empty( $icl_ncp_plugins2 ) ) {
		if ( 0 === strcmp( $filtered_page, WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' ) ) {
			add_action( 'admin_notices', 'icl_incomp_plugins_warn2' );
			function icl_incomp_plugins_warn2() {
				global $icl_ncp_plugins2;
				echo '<a name="icl_inc_plugins_notice"></a><div class="error" style="padding:10px;">';
				esc_html_e( 'These plugins are known to have compatibiliy issues with WPML:', 'sitepress' );
				echo '<ul style="list-style:disc;margin-left:20px;">';
				foreach ( $icl_ncp_plugins2 as $incp ) {
					echo '<li>' . esc_html( $incp ) . '</li>';
				}
				echo '</ul>';
				echo '</div>';
			}
		}
	}
}
