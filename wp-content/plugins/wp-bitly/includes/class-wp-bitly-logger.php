<?php
/**
 * Logging for the plugin
 *
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */

/**
 * Plugin debug logging functionality.
 *
 * @since      2.6.0
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */
class Wp_Bitly_Logger {

	/**
	 * The options class.
	 *
	 * @since    2.6.0
	 * @access   protected
	 * @var      class $wp_bitly_options
	 */
	protected $wp_bitly_options;

	/**
	 * Initialize
	 *
	 * @since    2.6.0
	 */
	public function __construct() {
		$this->wp_bitly_options = new Wp_Bitly_Options();
	}


	/**
	 * Logging function.
	 *
	 * @since    2.6.0
	 * @param    mixed  $towrite The data to write to the log file.
	 * @param    string $message A label/message describing what is being logged.
	 * @param    bool   $bypass  Whether to bypass the debug option check.
	 */
	public function wpbitly_debug_log( $towrite, $message, $bypass = true ) {

		if ( ! $this->wp_bitly_options->get_option( 'debug' ) || ! $bypass ) {
			return;
		}

		$entry = 'WP Bitly [' . $message . ']';
		if ( '' !== $towrite ) {
			$entry .= ': ' . ( is_array( $towrite ) || is_object( $towrite ) ? print_r( $towrite, true ) : var_export( $towrite, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_var_export
		}
		error_log( $entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
