<?php
/*
  Plugin Name: StatCollector
  Description: Collects information from Drools and saved results
  Author:      Blue State Digital
*/

namespace StatCollector;

if (!defined('WPINC')) {
  die; //no direct access
}
require plugin_dir_path(__FILE__) . 'settings.php';

// internal actions to call in a plugin/hook sense
add_action('drools_request', '\StatCollector\drools_request', 10, 2);
add_action('drools_response', '\StatCollector\drools_response', 10, 2);
add_action('results_sent', '\StatCollector\results_sent', 10, 5);
add_action('peu_data', '\StatCollector\peu_data', 10, 3);

// AJAX endpoints to directly write info
add_action('wp_ajax_response_update', '\StatCollector\response_update');
add_action('wp_ajax_nopriv_response_update', '\StatCollector\response_update');

function drools_request($data, $uid) {
  $db = _get_db();
  $db->insert("requests", [
    "uid" => $uid,
    "data" => json_encode($data),
  ]);
}

function drools_response($response, $uid) {
  $db = _get_db();
  $db->insert("responses", [
    "uid" => $uid,
    "data" => json_encode($response),
  ]);
}

function results_sent($type, $to, $uid, $url = null, $message = null) {
  $db = _get_db();
  $db->insert("messages", [
    "uid" => $uid,
    "msg_type" => strtolower($type),
    "address" => $to,
    "url" => $url,
    "message" => $message
  ]);
}

function peu_data($staff, $client, $uid) {
  if (empty($uid)) {
    return;
  }
  $db = _get_db();

  if (! empty($staff)) {
    $db->insert("peu_staff", [
      "uid" => $uid,
      "data" => json_encode($staff)
    ]);
  }
  if (! empty($client)) {
    $db->insert("peu_client", [
      "uid" => $uid,
      "data" => json_encode($client)
    ]);
  }
}

function response_update() {
  $uid = $_POST['GUID'];
  $url = $_POST['url'];
  $programs = $_POST['programs'];
  if (empty($uid) || empty($url) || empty($programs)) {
    wp_send_json(["status" => "fail","message" => "missing values"]);
    return wp_die();
  }

  $db = _get_db();
  $db->insert("response_update", [
    "uid" => $uid,
    "url" => $url,
    "program_codes" => $programs
  ]);
  wp_send_json(["status" => "ok"]);
  wp_die();
}

function _get_db() {
  $host = get_option('statc_host');
  $database = get_option('statc_database');
  $user = get_option('statc_user');
  $password = get_option('statc_password');
  $bootstrapped = get_option('statc_bootstrapped');

  $host = (!empty($host)) ? $host : $_ENV['STATC_HOST'];
  $database = (!empty($database)) ? $database : $_ENV['STATC_DATABASE'];
  $user = (!empty($user)) ? $user : $_ENV['STATC_USER'];
  $password = (!empty($password)) ? $password : $_ENV['STATC_PASSWORD'];
  $bootstrapped = (!empty($bootstrapped)) ? $bootstrapped : $_ENV['STATC_BOOTSTRAPPED'];

  if (empty($host) || empty($database) || empty($user) || empty($password)) {
    error_log('StatCollector is missing database connection information. Cannot log');
    return new MockDb();
  }

  $db = new \wpdb($user, $password, $database, $host);
  $db->show_errors();

  if ($bootstrapped !== '5') {
    __bootstrap($db);
  }
  return $db;
}

function __bootstrap($db) {
  $db->query(
    "CREATE TABLE IF NOT EXISTS messages (
       id INT(11) NOT NULL AUTO_INCREMENT,
       uid VARCHAR(13) DEFAULT NULL,
       msg_type VARCHAR(10) DEFAULT NULL,
       address VARCHAR(255) NOT NULL,
       date DATETIME DEFAULT NOW(),
       url VARCHAR(512) DEFAULT NULL,
       message TEXT DEFAULT NULL,
       PRIMARY KEY(id)
     ) ENGINE=InnoDB"
  );
  $db->query(
    "CREATE TABLE IF NOT EXISTS requests (
       id INT(11) NOT NULL AUTO_INCREMENT,
       uid VARCHAR(13) DEFAULT NULL,
       data MEDIUMBLOB NOT NULL,
       date DATETIME DEFAULT NOW(),
       PRIMARY KEY(id)
     ) ENGINE=InnoDB"
  );
  $db->query(
    "CREATE TABLE IF NOT EXISTS responses (
       id INT(11) NOT NULL AUTO_INCREMENT,
       uid VARCHAR(13) DEFAULT NULL,
       data MEDIUMBLOB NOT NULL,
       date DATETIME DEFAULT NOW(),
       PRIMARY KEY(id)
     ) ENGINE=InnoDB"
  );
  $db->query(
    "CREATE TABLE IF NOT EXISTS peu_staff (
       id INT(11) NOT NULL AUTO_INCREMENT,
       uid VARCHAR(13) DEFAULT NULL,
       data MEDIUMBLOB NOT NULL,
       date DATETIME DEFAULT NOW(),
       PRIMARY KEY(id)
     ) ENGINE=InnoDB"
  );
  $db->query(
    "CREATE TABLE IF NOT EXISTS peu_client (
       id INT(11) NOT NULL AUTO_INCREMENT,
       uid VARCHAR(13) DEFAULT NULL,
       data MEDIUMBLOB NOT NULL,
       date DATETIME DEFAULT NOW(),
       PRIMARY KEY(id)
     ) ENGINE=InnoDB"
  );
  $db->query(
    "CREATE TABLE IF NOT EXISTS response_update (
       id INT(11) NOT NULL AUTO_INCREMENT,
       uid VARCHAR(13) DEFAULT NULL,
       url MEDIUMBLOB NOT NULL,
       program_codes VARCHAR(256) DEFAULT NULL,
       PRIMARY KEY(id)
     ) ENGINE=InnoDB"
  );

  // we will just let this fail if the columns exist
  // from previous migrations. But silence the error
  $db->hide_errors();
  $db->query(
    "ALTER TABLE messages
     ADD url VARCHAR(512) DEFAULT NULL AFTER date,
     ADD message TEXT DEFAULT NULL AFTER url"
  );
  $db->query(
    "ALTER TABLE response_update
     ADD date DATETIME DEFAULT NOW() AFTER program_codes"
  );
  $db->show_errors();

  update_option('statc_bootstrapped', 5);
}

class MockDb
{

  public function insert($table, $args) {
    return;
  }

  public function query($q) {
    return;
  }

}
