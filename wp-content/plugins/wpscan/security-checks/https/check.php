<?php

/**
 * Classname: WPScan\Checks\https
 */

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * HTTPS.
 *
 * Checks if the website is using HTTPS.
 *
 * @since 1.14.0
 */
class https extends Check {
  /**
   * Title.
   *
   * @since 1.14.0
   * @access public
   * @return string
   */
  public function title() {
    return __( 'Website HTTPS', 'wpscan' );
  }

  /**
   * Description.
   *
   * @since 1.14.0
   * @access public
   * @return string
   */
  public function description() {
      return __( 'Checks if your website is using HTTPS encryption for communications.', 'wpscan' );
  }

  /**
   * Success message.
   *
   * @since 1.14.0
   * @access public
   * @return string
   */
  public function success_message() {
      return __( 'Your website seems to be using HTTPS', 'wpscan' );
  }

  /**
   * Perform the check and save the results.
   *
   * @since 1.14.0
   * @access public
   * @return void
   */
  public function perform() {
    $vulnerabilities = $this->get_vulnerabilities();

    $wp_url   = get_bloginfo( 'wpurl' );
    $site_url = get_bloginfo( 'url' );

    // Check if the current page is using HTTPS.
    if ( 'https' !== substr( $wp_url, 0, 5 ) || 'https' !== substr( $site_url, 0, 5 ) ) {
      // No HTTPS used.
      $this->add_vulnerability( __( 'The website does not seem to be using HTTPS (SSL/TLS) encryption for communications.', 'wpscan' ), 'high', 'https', 'https://blog.wpscan.com/wordpress-ssl-tls-https-encryption/' );
    }
  }
}
