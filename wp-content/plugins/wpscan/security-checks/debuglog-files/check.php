<?php

/**
 * Classname: WPScan\Checks\debuglogFiles
 */

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DebuglogFiles.
 *
 * Checks for debug.log files.
 *
 * @since 1.0.0
 */
class debuglogFiles extends Check {
	/**
	 * Title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function title() {
		return __( 'Debug Log Files', 'wpscan' );
	}

	/**
	 * Description.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function description() {
		return __( 'Search the file system for debug log files that are publicly accessible.', 'wpscan' );
	}

	/**
	 * Success message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function success_message() {
		return __( 'No publicly accessible debug log files were found', 'wpscan' );
	}

	/**
	 * Perform the check and save the results.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function perform() {
		require_once ABSPATH . 'wp-admin/includes/file.php'; // Needed for the list_files() function used below.

		$vulnerabilities = $this->get_vulnerabilities();

		$files = list_files( ABSPATH );

		foreach ( $files as $file ) {
			if ( preg_match( '/debug\.log/', $file ) ) {
				$url = esc_url( get_site_url() . '/' . str_replace( ABSPATH, '', $file ) );

				if ( file_exists( $file ) ) {
					$response = wp_remote_head( $url, array( 'timeout' => 5 ) );
					$code     = wp_remote_retrieve_response_code( $response );

					if ( 200 === $code ) {
						$this->add_vulnerability( __( 'A publicly accessible debug.log file was found in', 'wpscan' ) . " <a href='$url' target='_blank'>$url</a>", 'high', sanitize_title( $file ) );
					}
				}
			}
		}
	}
}
