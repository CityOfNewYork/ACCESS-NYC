<?php

// phpcs:disable
/**
 * Plugin Name: Stat Collector
 * Description: Adds WordPress hooks to enable the logging of data from the site to a specified MySQL database. Currently, it collects information from the Drools Request/Response, and Send Me NYC SMS and Email messages.
 * Author:      Blue State Digital, maintained by NYC Opportunity
 */
// phpcs:enable

/**
 *
 */

add_action('statc_register', function($statc) {
  if (defined('WP_ENV') && 'development' === WP_ENV) {
    return;
  }

  /**
   * Hook to save the Drools (eligibility screening) request
   *
   * @param   String  $data  The JSON object of the request
   * @param   String  $uid   The GUID of the request
   */
  add_action('drools_request', function($data, $uid) use ($statc) {
    $statc->collect('requests', [
      'uid' => $uid,
      'data' => json_encode($data),
    ]);
  }, $statc->settings->priority, 2);

  /**
   * Hook to save the Drools (eligibility screening) Response
   *
   * @param   String  $response  The JSON object of the response
   * @param   String  $uid       The GUID of the response
   */
  add_action('drools_response', function($response, $uid) use ($statc) {
    $statc->collect('responses', [
      'uid' => $uid,
      'data' => json_encode($response),
    ]);
  }, $statc->settings->priority, 2);

  /**
   * Hook for the Stat Collector action for saving the email content to the DB
   *
   * @param   String  $type  email/sms/whatever the class type is
   * @param   String  $to    The number/email sent to
   * @param   String  $uid   The GUID of the results
   * @param   String  $url   The main url shared
   * @param   String  $msg   The body of the message
   */
  add_action('smnyc_message_sent', function($type, $to, $uid, $url = null, $message = null) use ($statc) {
    $statc->collect('messages', [
      'uid' => $uid,
      'msg_type' => strtolower($type),
      'address' => $to,
      'url' => $url,
      'message' => $message
    ]);
  }, $statc->settings->priority, 5);

  return true;
});

/**
 * Creates the database tables for data if they do not exist.
 *
 * @param   Object  $db  Instance of wpdb (WordPress DB abstraction method)
 */
add_action('statc_bootstrap', function($db) {
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

  // We will just let this fail if the columns exist from previous migrations but silence the error.
  $db->hide_errors();

  $db->query(
    'ALTER TABLE messages
    ADD url VARCHAR(512) DEFAULT NULL AFTER date,
    ADD message TEXT DEFAULT NULL AFTER url'
  );

  $db->show_errors();

  return true;
});

require plugin_dir_path(__FILE__) . '/wp-stat-collector/StatCollector.php';
