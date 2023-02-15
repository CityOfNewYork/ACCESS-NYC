<?php

/**
 * Classname: WPScan\Checks\weakPasswords
 */

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WeakPasswords.
 *
 * Checks if privileged users are using weak passwords.
 *
 * @since 1.14.0
 */
class weakPasswords extends Check {
  /**
   * Title.
   *
   * @since 1.14.0
   * @access public
   * @return string
   */
  public function title() {
    return __( 'Weak Passwords', 'wpscan' );
  }

  /**
   * Description.
   *
   * @since 1.14.0
   * @access public
   * @return string
   */
  public function description() {
      return __( 'Checks if privileged users are using any passwords from our weak password list.', 'wpscan' );
  }

  /**
   * Success message.
   *
   * @since 1.14.0
   * @access public
   * @return string
   */
  public function success_message() {
      return __( 'We were not able to brute force the password of any privileged user', 'wpscan' );
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

    // Password list from: https://github.com/danielmiessler/SecLists/blob/master/Passwords/probable-v2-top207.txt.
    $users     = get_users( array( 'role__in' => array( 'super_admin', 'administrator', 'editor', 'author', 'contributor' ) ) );
    $passwords = file( $this->dir . '/assets/passwords.txt', FILE_IGNORE_NEW_LINES );
    $found     = array();

    foreach ( $users as $user ) {
      $username = $user->user_login;
      
      foreach ( $passwords as $password ) {
        if ( wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
          array_push( $found, $username );
          break;
        }
      }
    }

    if ( ! empty( $found ) ) {
        if ( 1 === count( $found ) ) {
          $text = sprintf(
            __( 'The %s user was found to have a weak password. The user\'s password should be updated immediately.', 'wpscan' ),
            esc_html( $found[0] )
          );
        } else {
          $found = implode( ', ', $found );
          $text  = sprintf(
            __( 'The %s users were found to have weak passwords. The users\' passwords should be updated immediately.', 'wpscan' ),
            esc_html( $found )
          );
        }

        $this->add_vulnerability( $text, 'high', 'weak-passwords', 'https://blog.wpscan.com/wpscan-brute-force/' );
    }
  }
}
