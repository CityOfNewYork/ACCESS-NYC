<?php

/**
 * Classname: WPScan\Checks\versionControl
 */

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * VersionControl.
 *
 * Checks for version control files, such as .git and .svn.
 *
 * @since 1.0.0
 */
class versionControl extends Check {
	/**
	 * Title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function title() {
		return __( 'Version Control Files', 'wpscan' );
	}

	/**
	 * Description.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function description() {
		return __( 'Check if version control files, such as .git or .svn, are publicly accessible.', 'wpscan' );
	}

	/**
	 * Success message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function success_message() {
		return __( 'No version control files were found in the web root', 'wpscan' );
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

		$files = array( '.svn', '.git' );

		foreach ( $files as $file ) {
			$url = esc_html( get_site_url() . '/' . $file );

			if ( file_exists( ABSPATH . $file ) ) {
				$response = wp_remote_head( $url, array( 'timeout' => 5 ) );
				$code     = wp_remote_retrieve_response_code( $response );

				if ( 200 === $code ) {
					$this->add_vulnerability( __( 'A publicly accessible ' . esc_html( $file ) . ' file was found. The file could expose your websites\'s source code.', 'wpscan' ), 'high', sanitize_title( $file ) );
				}
			}
		}
	}
}
