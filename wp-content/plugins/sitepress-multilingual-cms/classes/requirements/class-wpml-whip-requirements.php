<?php
/**
 * WPML_Whip_Requirements class file.
 *
 * @package wpml-core
 */

/**
 * Class WPML_Whip_Requirements
 */
class WPML_Whip_Requirements {

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_whip' ) );
	}

	/**
	 * Get host name for message about PHP.
	 *
	 * @return string
	 */
	public function whip_name_of_host() {
		return 'WPML';
	}

	/**
	 * Get WPML message about PHP.
	 *
	 * @return string
	 */
	public function whip_message_from_host_about_php() {
		$message =
			'<li>' .
			__( 'This will be the last version of WPML which works with the currently installed PHP version', 'sitepress' ) .
			'</li>' .
			'<li>' .
			__( 'This version of WPML will only receive security fixes for the next 12 months', 'sitepress' ) .
			'</li>';

		return $message;
	}

	/**
	 * Load Whip.
	 */
	public function load_whip() {
		if ( ! ( 'index.php' === $GLOBALS['pagenow'] && current_user_can( 'manage_options' ) ) ) {
			return;
		}

		add_filter( 'whip_hosting_page_url_wordpress', '__return_true' );
		add_filter( 'whip_name_of_host', array( $this, 'whip_name_of_host' ) );
		add_filter( 'whip_message_from_host_about_php', array( $this, 'whip_message_from_host_about_php' ) );

		whip_wp_check_versions( array( 'php' => '>=5.6' ) );
	}
}
