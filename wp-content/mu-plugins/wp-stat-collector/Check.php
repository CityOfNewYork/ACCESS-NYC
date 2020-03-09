<?php

namespace StatCollector;

use wpdb;
use StatCollector\wpdbssl as wpdbssl;

class Check {
  /**
   * Constructor
   */
  public function __construct($settings) {
    $this->settings = $settings;
  }

  /**
   * Checks for a Certificate Authority. Updates the ssl option if available.
   *
   * @return  Boolean  True is authority is present, false if not.
   */
  public function certificateAuthority() {
    $statc_ssl = $this->settings->prefix . '_' . $this->settings->ssl;
    $certificate_authority = plugin_dir_path(__FILE__) . $this->settings->ssl_ca;

    if (file_exists($certificate_authority)) {
      update_option($statc_ssl, $this->settings->ssl_value); // Update admin panel

      return true;
    } else {
      update_option($statc_ssl, '0');

      return false;
    }
  }

  /**
   * Check for Database Tables. Checks the bootstrapped option set by the initial DB query.
   *
   * @return  Boolean  True if bootstrapped, false if not
   */
  public function tables() {
    $statc_bootstrapped = $this->settings->prefix . '_' . $this->settings->bootstrapped;
    $bootstrapped = get_option($statc_bootstrapped);

    if ($bootstrapped !== $this->settings->bootstrapped_value) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Checks the database connection.
   *
   * @return  Boolean  True if connection works, false if not
   */
  public function connection($table = false) {
    $prefix = $this->settings->prefix . '_';

    $host = get_option($prefix . $this->settings->host);
    $database = get_option($prefix . $this->settings->database);
    $user = get_option($prefix . $this->settings->user);
    $password = get_option($prefix . $this->settings->password);

    $host = (!empty($host)) ? $host : STATC_HOST;
    $database = (!empty($database)) ? $database : STATC_DATABASE;
    $user = (!empty($user)) ? $user : STATC_USER;
    $password = (!empty($password)) ? $password : STATC_PASSWORD;

    $statc_connection = $prefix . $this->settings->connection;

    if ($this->certificateAuthority()) {
      $certificate_authority = plugin_dir_path(__FILE__) . $this->settings->ssl_ca;

      $db = new wpdbssl($user, $password, $database, $host, $certificate_authority);
    } else {
      $db = new wpdb($user, $password, $database, $host);
    }

    if ($db->ready) {
      update_option($statc_connection, $this->settings->connection_value); // Update admin panel

      $db->close();

      return false;
    } else {
      update_option($statc_connection, '0');

      $db->close();

      return true;
    }
  }
}
