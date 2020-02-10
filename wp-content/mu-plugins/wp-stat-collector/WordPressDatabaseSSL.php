<?php

namespace StatCollector;

use \wpdb;

/**
 * This class extends the wpdb WordPress Database Abstraction method. Both the
 * original class and this class need to be included in the same file to be used.
 *
 * require_once ABSPATH . WPINC . '/wp-db.php';
 * require_once plugin_dir_path(__FILE__) . 'wp-db-ssl.php';
 *
 * Then it can be used the same way wpdb is used with the additional certificate
 * authority argument which should be a path to an existing .pem certificate;
 *
 * new wpdbssl($user, $pass, $db, $host, $ca);
 *
 * This only passes the $ca argument to mysqli_ssl_set(). If there is a need to
 * pass the $key, $cert, or other args then this will need to be modified.
 *
 * @source https://developer.wordpress.org/reference/classes/wpdb/
 * @source https://www.php.net/manual/en/mysqli.ssl-set.php
 */
class wpdbssl extends wpdb {
  /**
   * Certificate Authority
   *
   * @var string
   */
  protected $ca = 'ca.pem';

  /**
   * Connects to the database server and selects a database
   *
   * PHP5 style constructor for compatibility with PHP5. Does
   * the actual setting up of the class properties and connection
   * to the database.
   *
   * @link https://core.trac.wordpress.org/ticket/3354
   * @since 2.0.8
   *
   * @global string $wp_version
   *
   * @param string $dbuser     MySQL database user
   * @param string $dbpassword MySQL database password
   * @param string $dbname     MySQL database name
   * @param string $dbhost     MySQL database host
   * @param string $dbca       Path to the certificate authority
   */
  public function __construct($dbuser, $dbpassword, $dbname, $dbhost, $dbca = false) {
    if ($dbca) {
      $this->certificate_authority = $dbca;
    } else {
      $this->certificate_authority = plugin_dir_path(__FILE__) . $ca;
    }

    parent::__construct($dbuser, $dbpassword, $dbname, $dbhost);
  }

  /**
   * Connect to and select database.
   *
   * If $allow_bail is false, the lack of database connection will need
   * to be handled manually.
   *
   * @since 3.0.0
   * @since 3.9.0 $allow_bail parameter added.
   *
   * @param bool $allow_bail Optional. Allows the function to bail. Default true.
   * @return bool True with a successful connection, false on failure.
   */
  public function db_connect($allow_bail = true) {
    $this->is_mysql = true;

    if ($this->use_mysqli) {
      $this->dbh = mysqli_init();

      $host = $this->dbhost;
      $port = null;
      $socket = null;
      $is_ipv6 = false;

      if ($host_data = $this->parse_db_host($this->dbhost)) {
        list($host, $port, $socket, $is_ipv6) = $host_data;
      }

      /**
       * If using the `mysqlnd` library, the IPv6 address needs to be
       * enclosed in square brackets, whereas it doesn't while using the
       * `libmysqlclient` library.
       * @see https://bugs.php.net/bug.php?id=67563
       */
      if ($is_ipv6 && extension_loaded('mysqlnd')) {
        $host = "[$host]";
      }

      /**
       * Use mysqli_ssl_set to configure SSL if the certificate authority exists.
       *
       * @link https://www.php.net/manual/en/mysqli.ssl-set.php
       *
       * MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT
       * Like MYSQLI_CLIENT_SSL, but disables validation of the provided SSL
       * certificate. This is only for installations using MySQL Native Driver
       * and MySQL 5.6 or later.
       *
       * @link https://www.php.net/manual/en/mysqli.real-connect.php
       *
       * @author NYC Opportunity
       */
      if (file_exists($this->certificate_authority)) {
        $this->mysqli_ssl_set();

        $client_flags = MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
      }

      if (WP_DEBUG) {
        mysqli_real_connect($this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags);
      } else {
        @mysqli_real_connect($this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags);
      }

      if ($this->dbh->connect_errno) {
        $this->dbh = null;

        /**
         * It's possible ext/mysqli is misconfigured. Fall back to ext/mysql if:
         *  - We haven't previously connected, and
         *  - WP_USE_EXT_MYSQL isn't set to false, and
         *  - ext/mysql is loaded.
         */
        $attempt_fallback = true;

        if ($this->has_connected) {
          $attempt_fallback = false;
        } elseif (defined('WP_USE_EXT_MYSQL') && ! WP_USE_EXT_MYSQL ) {
          $attempt_fallback = false;
        } elseif (!function_exists('mysql_connect')) {
          $attempt_fallback = false;
        }

        if ($attempt_fallback) {
          $this->use_mysqli = false;
          return $this->db_connect($allow_bail);
        }
      }
    }

    if (!$this->dbh && $allow_bail) {
      wp_load_translations_early();

      // Load custom DB error template, if present.
      if (file_exists(WP_CONTENT_DIR . '/db-error.php')) {
        require_once(WP_CONTENT_DIR . '/db-error.php');
        die();
      }

      $message = '' . __('WordPress Database SSL: Error establishing a database connection');

      $this->bail($message, 'db_connect_fail');

      return false;
    } elseif ($this->dbh) {
      if (!$this->has_connected) {
        $this->init_charset();
      }

      $this->has_connected = true;

      $this->set_charset($this->dbh);

      $this->ready = true;
      $this->set_sql_mode();
      $this->select($this->dbname, $this->dbh);

      return true;
    }

    return false;
  }

  /**
   * Use mysqli_ssl_set to configure the SSL connection to the database
   */
  private function mysqli_ssl_set() {
    $certificate_authority = $this->certificate_authority;

    mysqli_ssl_set($this->dbh, NULL, NULL, $certificate_authority, NULL, NULL);
  }
}
