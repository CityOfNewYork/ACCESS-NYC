<?php

/**
 * Classname: WPScan\Checks\secretKeys
 */

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SecretKeys.
 *
 * Checks for the use of WordPress secret keys.
 *
 * @since 1.0.0
 */
class secretKeys extends Check {
	/**
	 * Title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function title() {
		return __( 'Secret Keys', 'wpscan' );
	}

	/**
	 * Description.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function description() {
		return __( 'Check if the WordPress secret keys have been changed.', 'wpscan' );
	}

	/**
	 * Success message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function success_message() {
		return __( 'The WordPress secret keys were not the default values', 'wpscan' );
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

		$keys = array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' );

		foreach ( $keys as $key ) {
			if ( defined( $key ) && constant( $key ) === 'put your unique phrase here' ) {
				$this->add_vulnerability( __( 'The ' . esc_html( $key ) . ' secret key in the wp-config.php file was the default key. It should be changed to a random value using', 'wpscan' ) . " <a href='https://api.wordpress.org/secret-key/1.1/salt/' target='_blank'>https://api.wordpress.org/secret-key/1.1/salt/</a>", 'high', sanitize_title( $key ) );
			}
		}
	}
}
