<?php
/**
 * Classname: WPScan\Checks\databaseExports
 */

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DatabaseExports.
 *
 * Checks for exported database files.
 *
 * @since 1.0.0
 */
class databaseExports extends Check {
	/**
	 * Title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function title() {
		return __( 'Database Exports', 'wpscan' );
	}

	/**
	 * Description.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function description() {
		return __( 'Search the file system for database export files that are publicly accessible.', 'wpscan' );
	}

	/**
	 * Success message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function success_message() {
		return __( 'No publicly accessible database export files were found', 'wpscan' );
	}

	/**
	 * Perform the check and save the results.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function perform() {
		$vulnerabilities = $this->get_vulnerabilities();

		$text    = file_get_contents( $this->dir . '/assets/db_exports.txt' );
		$exports = str_replace( '{domain_name}', $_SERVER['HTTP_HOST'], $text );
		$names   = explode( PHP_EOL, $exports );

		foreach ( $names as $name ) {
			$path = ABSPATH . $name;
			$url  = esc_url( get_site_url() . '/' . $name );
			
			if ( file_exists( $path ) ) {
				$response = wp_remote_head( $url, array( 'timeout' => 5 ) );
				$code     = wp_remote_retrieve_response_code( $response );

				if ( 200 === $code ) {
					$this->add_vulnerability( __( 'A publicly accessible database file  was found in', 'wpscan' ) . " <a href='$url' target='_blank'>$url</a>", 'high', sanitize_title( $name ) );
				}
			}
		}
	}
}
