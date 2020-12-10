<?php

/**
 * Classname: WPScan\Checks\wpconfigBackups
 */

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WPconfigBackups.
 *
 * Checks for wp-config.php backed up files.
 *
 * @since 1.0.0
 */
class wpconfigBackups extends Check {
	/**
	 * Title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function title() {
		return __( 'Configuration Backups', 'wpscan' );
	}

	/**
	 * Description.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function description() {
		return __( 'Search the file system for wp-config.php backup files that are publicly accessible.', 'wpscan' );
	}

	/**
	 * Success message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function success_message() {
		return __( 'No publicly accessible wp-config.php backup files were found', 'wpscan' );
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

		$config_files = str_replace( ABSPATH, '', glob( ABSPATH . 'wp-config.*' ) );

		foreach ( $config_files as $config_file ) {
			if ( 'wp-config.php' === $config_file ) continue; // Ignore wp-config.php file.

			$path = ABSPATH . $config_file;
			$url  = esc_url( get_site_url() . '/' . $config_file );

			if ( file_exists( $path ) ) {
				$response = wp_remote_head( $url, array( 'timeout' => 5 ) );
				$code     = wp_remote_retrieve_response_code( $response );

				if ( 200 === $code ) {
					$this->add_vulnerability( __( 'A publicly accessible wp-config.php backup file  was found in', 'wpscan' ) . " <a href='$url' target='_blank'>$url</a>", 'high', sanitize_title( $path ) );
				}
			}
		}
	}
}
