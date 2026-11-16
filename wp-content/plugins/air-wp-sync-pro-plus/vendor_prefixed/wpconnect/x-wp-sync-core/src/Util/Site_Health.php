<?php
/**
 * Site health checks
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util;

/**
 * Site_Health class.
 */
class Site_Health {

	/**
	 * Site_Health instance
	 *
	 * @var Site_Health $instance
	 */
	private static $instance;

	/**
	 * Returns Site_Health instance
	 *
	 * @return Site_Health
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return site health checks for the plugin.
	 *
	 * @return array
	 */
	public function check() {
		$result = get_transient( 'airwpsync_site_health_check' );
		if ( $result ) {
			return $result;
		}
		if ( ! class_exists( '\WP_Site_Health' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
		}

		$result = array();

		$health_check_site_status       = \WP_Site_Health::get_instance();
		$result['can_perform_loopback'] = true;
		if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
			$can_perform_loopback           = $health_check_site_status->can_perform_loopback();
			$result['can_perform_loopback'] = 'good' !== $can_perform_loopback->status ? $can_perform_loopback->message : true;
		}
		$cron           = $health_check_site_status->get_test_scheduled_events();
		$result['cron'] = 'good' !== $cron['status'] ? $cron['label'] : true;

		$result['all_good'] = array_reduce(
			$result,
			function ( $carry, $check_result ) {
				return $carry && true === $check_result;
			},
			true
		);

		set_transient( 'airwpsync_site_health_check', $result, DAY_IN_SECONDS );
		return $result;
	}
}
