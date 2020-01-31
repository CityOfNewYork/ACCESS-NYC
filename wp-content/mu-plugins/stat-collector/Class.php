<?php

namespace StatCollector;

use wpdb;
use Aws\Ses\SesClient;
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

    $this->check = new Check($settings);

    /**
     * Hooks for internal actions that collect information to write to the DB.
     */

    add_action('drools_request', [$this, 'droolsRequest'], $this->settings->priority, 2);
    add_action('drools_response', [$this, 'droolsResponse'], $this->settings->priority, 2);
    add_action('smnyc_message_sent', [$this, 'resultsSent'], $this->settings->priority, 5);

    /**
     * Hook for plugin post-instantiation.
     */

    do_action('init_stat_collector', $this);
  }

  /**
   * Hook to save the Drools (eligibility screening) request
   *
   * @param   String  $data  The JSON object of the request
   * @param   String  $uid   The GUID of the request
   */
  public function droolsRequest($data, $uid) {
    $db = $this->getDb();

    $result = $db->insert('requests', [
      'uid' => $uid,
      'data' => json_encode($data),
    ]);

    if ($result === false) {
      $this->notify($db->last_error, true);
    }

    $db->close();
  }

  /**
   * Hook to save the Drools (eligibility screening) Response
   *
   * @param   String  $response  The JSON object of the response
   * @param   String  $uid       The GUID of the response
   */
  public function droolsResponse($response, $uid) {
    $db = $this->getDb();

    $result = $db->insert('responses', [
      'uid' => $uid,
      'data' => json_encode($response),
    ]);

    // Log errors
    if ($result === false) {
      $this->notify($db->last_error, true);
    }

    $db->close();
  }

  /**
   * Hook for the Stat Collector action for saving the email content to the DB
   *
   * @param   String  $type  email/sms/whatever the class type is
   * @param   String  $to    The number/email sent to
   * @param   String  $uid   The GUID of the results
   * @param   String  $url   The main url shared
   * @param   String  $msg   The body of the message
   */
  public function resultsSent($type, $to, $uid, $url = null, $message = null) {
    $db = $this->getDb();

    $result = $db->insert('messages', [
      'uid' => $uid,
      'msg_type' => strtolower($type),
      'address' => $to,
      'url' => $url,
      'message' => $message
    ]);

    // Log errors
    if ($result === false) {
      $this->notify($db->last_error, true);
    }

    $db->close();
  }

  /**
   * Logs a message and sends notification via wp_mail email. The admin will need
   * to reset the notify option once a message is sent to prevent continuous mailing.
   *
   * @param   String  $msg  The message to send.
   */
  public function notify($msg, $mail = false, $throttle = true) {
    $msg = $this->settings->plugin . ': ' . $msg;
    $statc_notify = $this->settings->prefix . '_' . $this->settings->notify;
    $notify = get_option($statc_notify);

    error_log($msg);

    if ($mail && $throttle && $notify === $this->settings->notify_value) {
      $msg = $msg . ' This is the first instance of the error. All following
      instances will be logged to the server. Recheck the "Send Notifications"
      option in the admin menu.';

      wp_mail(get_option('admin_email'), $this->settings->notify_subject, $msg);

      update_option($statc_notify, '0');
    } elseif ($mail && !$throttle) {
      wp_mail(get_option('admin_email'), $this->settings->notify_subject, $msg);
    }
  }

  /**
   * Connects to the database using credentials and the WP DB SSL abstraction included in this plugin
   *
   * @return  Object  Instance of wpdb.
   */
  private function getDb() {
    $prefix = $this->settings->prefix . '_';

    $host = get_option($prefix . $this->settings->host);
    $database = get_option($prefix . $this->settings->database);
    $user = get_option($prefix . $this->settings->user);
    $password = get_option($prefix . $this->settings->password);

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
   * @param   Object  $db  Instance of wpdb (WordPress DB abstraction method)
   */
  private function __bootstrap($db) {
    $statc_bootstrapped = $this->settings->prefix . '_' . $this->settings->bootstrapped;

    $db->query(
      'CREATE TABLE IF NOT EXISTS messages (
        id INT(11) NOT NULL AUTO_INCREMENT,
        uid VARCHAR(13) DEFAULT NULL,
        msg_type VARCHAR(10) DEFAULT NULL,
        address VARCHAR(255) NOT NULL,
        date DATETIME DEFAULT NOW(),
        url VARCHAR(512) DEFAULT NULL,
        message TEXT DEFAULT NULL,
        PRIMARY KEY(id)
      ) ENGINE=InnoDB'
    );

    $db->query(
      'CREATE TABLE IF NOT EXISTS requests (
        id INT(11) NOT NULL AUTO_INCREMENT,
        uid VARCHAR(13) DEFAULT NULL,
        data MEDIUMBLOB NOT NULL,
        date DATETIME DEFAULT NOW(),
        PRIMARY KEY(id)
      ) ENGINE=InnoDB'
    );

    $db->query(
      'CREATE TABLE IF NOT EXISTS responses (
        id INT(11) NOT NULL AUTO_INCREMENT,
        uid VARCHAR(13) DEFAULT NULL,
        data MEDIUMBLOB NOT NULL,
        date DATETIME DEFAULT NOW(),
        PRIMARY KEY(id)
      ) ENGINE=InnoDB'
    );

    $db->query(
      'CREATE TABLE IF NOT EXISTS response_update (
        id INT(11) NOT NULL AUTO_INCREMENT,
        uid VARCHAR(13) DEFAULT NULL,
        url MEDIUMBLOB NOT NULL,
        program_codes VARCHAR(256) DEFAULT NULL,
        PRIMARY KEY(id)
      ) ENGINE=InnoDB'
    );

    // We will just let this fail if the columns exist from previous migrations.
    // But silence the error.
    $db->hide_errors();

    $db->query(
      'ALTER TABLE messages
      ADD url VARCHAR(512) DEFAULT NULL AFTER date,
      ADD message TEXT DEFAULT NULL AFTER url'
    );

    $db->query(
      'ALTER TABLE response_update
      ADD date DATETIME DEFAULT NOW() AFTER program_codes'
    );

    $db->show_errors();

    update_option($statc_bootstrapped, $this->settings->bootstrapped_value); // Update admin panel
  }
}
