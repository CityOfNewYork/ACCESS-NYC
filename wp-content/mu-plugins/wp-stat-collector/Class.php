<?php

namespace StatCollector;

use wpdb;
use StatCollector\Check as Check;
use StatCollector\MockDatabase as MockDatabase;
use StatCollector\wpdbssl as wpdbssl;

class StatCollector {
  /**
   * Plugin Constructor
   *
   * @param   Object  $settings  Instantiated settings object.
   */
  public function __construct($settings) {
    $this->settings = $settings;

    $this->prefix = $this->settings->prefix . '_';

    $this->check = new Check($settings);

    /**
     * Hook for internal actions that collect information to write to the DB.
     *
     * @param  Object  $this  Instance of StatCollector
     */
    do_action($this->prefix . 'register', $this);

    /**
     * Hook for plugin post-instantiation.
     *
     * @param  Object  $this  Instance of StatCollector
     */
    do_action($this->prefix . 'init', $this);
  }

  /**
   * Method to insert information into the database. It will log MySQL errors upon failure.
   *
   * @param   String   $table  The name of the table to use
   * @param   Data     $data   The data to insert into the DB
   *
   * @return  Boolean          False if failure, true if successful
   */
  public function collect($table, $data) {
    if (gettype($table) !== 'string') {
      $this->notify('Collector argument type incorrect.');

      return false;
    }

    if (gettype($data) !== 'array') {
      $this->notify('Collector argument type incorrect.');

      return false;
    }

    $db = $this->getDb($table);

    $result = $db->insert($table, $data);

    // Log errors
    if ($result === false) {
      $error = implode(" \r\n", [
        'Last error: ' . (('' === $db->last_error) ? 'Insert error' : $db->last_error),
        'Last query: ' . $this->last_query,
      ]);

      $this->notify($error, true);
    }

    $db->close();
  }

  /**
   * Logs a message and sends notification via wp_mail email. The admin will need
   * to reset the notify option once a message is sent to prevent continuous mailing.
   *
   * @param  String  $msg  The message to send.
   */
  public function notify($msg, $mail = false, $throttle = true) {
    $msg = $this->settings->plugin . ': ' . $msg;

    $statc_notify = $this->prefix . $this->settings->notify;

    $notify = get_option($statc_notify);

    error_log($msg);

    if ($mail && $throttle && $notify === $this->settings->notify_value) {
      $msg = $msg . ' This is the first instance of the error. All following
      instances will be logged to the server. Recheck the "Send Notifications"
      option in the admin menu.';

      wp_mail(get_option('admin_email'), $this->settings->plugin, $msg);

      update_option($statc_notify, '0');
    } elseif ($mail && !$throttle) {
      wp_mail(get_option('admin_email'), $this->settings->plugin, $msg);
    }
  }

  /**
   * Connects to the database using credentials and the WP DB SSL abstraction included in this plugin
   *
   * @return  Object  Instance of wpdb.
   */
  private function getDb($table = false) {
    $host = get_option($this->prefix . $this->settings->host);
    $database = get_option($this->prefix . $this->settings->database);
    $user = get_option($this->prefix . $this->settings->user);
    $password = get_option($this->prefix . $this->settings->password);

    $host = (!empty($host)) ? $host : STATC_HOST;
    $database = (!empty($database)) ? $database : STATC_DATABASE;
    $user = (!empty($user)) ? $user : STATC_USER;
    $password = (!empty($password)) ? $password : STATC_PASSWORD;

    if (empty($host) || empty($database) || empty($user) || empty($password)) {
      $this->notify('Missing database connection information. Cannot log.');

      return new MockDatabase();
    }

    if ($this->check->certificateAuthority()) {
      $certificate_authority = plugin_dir_path(__FILE__) . $this->settings->ssl_ca;

      $db = new wpdbssl($user, $password, $database, $host, $certificate_authority);
    } else {
      $db = new wpdb($user, $password, $database, $host);

      $this->notify('Certificate Authority not found.', true, false);
    }

    $db->suppress_errors();

    $db->show_errors();

    if (!$this->check->tables()) {
      $this->__bootstrap($db);
    }

    return $db;
  }

  /**
   * Creates the tables for the data if they do not exist. Sets the 'statc_bootstrapped'
   * admin option to confirm that this process has been done.
   *
   * @param  Object  $db  Instance of wpdb (WordPress DB abstraction method)
   */
  private function __bootstrap($db) {
    $prefix = $this->prefix;

    $statc_bootstrapped = $this->prefix . $this->settings->bootstrapped;

    /**
     * Hook for bootstrapping the database. Example hook below.
     *
     * @param   Object   The wpdb abstraction object.
     *
     * @return  Boolean  Wether the bootstrapping is successful.
     */
    $success = do_action($this->prefix . 'bootstrap', $db);

    if ($success) {
      update_option($statc_bootstrapped, $this->settings->bootstrapped_value); // Update admin panel
    }
  }
}
