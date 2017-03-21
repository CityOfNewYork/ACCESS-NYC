<?php
if ( ! defined( 'WPML_CMS_NAV_DEV_VERSION' ) && ( version_compare( get_option( 'WPML_CMS_NAV_VERSION' ), WPML_CMS_NAV_VERSION, '=' ) || ( isset( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] == 'error_scrape' ) || ! isset( $wpdb ) ) ) {
	return;
}
add_action( 'plugins_loaded', 'wpml_cms_nav_upgrade', 1 );

function wpml_cms_nav_upgrade() {
	$previous_version = get_option( 'WPML_CMS_NAV_VERSION' );

	//Forcing upgrade logic when WPML_CMS_NAV_DEV_VERSION is defined
	//This allow to run the logic between different alpha/beta/RC versions
	//since we are now storing only the formal version in the options
	if ( defined( 'WPML_CMS_NAV_DEV_VERSION' ) ) {
		wpml_cms_nav_upgrade_version( WPML_CMS_NAV_VERSION, true );
	}

	if ( version_compare( $previous_version, WPML_CMS_NAV_VERSION, '<' ) ) {
		update_option( 'WPML_CMS_NAV_VERSION', WPML_CMS_NAV_VERSION );
	}
}

function wpml_cms_nav_upgrade_version( $version, $force = false ) {
	$previous_version = get_option( 'WPML_CMS_NAV_VERSION' );

	if ( $force || ( $previous_version && version_compare( $previous_version, $version, '<' ) ) ) {
		$upg_file = WPML_CMS_NAV_PLUGIN_PATH . '/inc/upgrade-functions/upgrade-' . $version . '.php';
		if ( file_exists( $upg_file ) && is_readable( $upg_file ) ) {
			if ( ! defined( 'WPML_DOING_UPGRADE' ) ) {
				define( 'WPML_DOING_UPGRADE', true );
			}
			/** @noinspection PhpIncludeInspection */
			include_once $upg_file;
		}
	}
}