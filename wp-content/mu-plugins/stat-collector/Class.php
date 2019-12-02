<?php

namespace StatCollector;

use MockDatabase;

class StatCollector {
  public $priority = 10;

  /**
   * Constructor
   */
  public function __construct() {
    // Internal actions to call in a plugin/hook sense
    add_action('drools_request', [$this, 'droolsRequest'], $this->priority, 2);
    add_action('drools_response', [$this, 'droolsResponse'], $this->priority, 2);
    add_action('results_sent', [$this, 'resultsSent'], $this->priority, 5);
    add_action('peu_data', [$this, 'peuData'], $this->priority, 3);

    // AJAX endpoints to directly write info
    add_action('wp_ajax_response_update', [$this, 'responseUpdate'], $this->priority);
    add_action('wp_ajax_nopriv_response_update', [$this, 'responseUpdate'], $this->priority);

    // Hook for plugin instantiation
    do_action('init_stat_collector', $this);
  }

  public function droolsRequest($data, $uid) {
    $db = $this->getDb();
    $result = $db->insert('requests', [
      'uid' => $uid,
      'data' => json_encode($data),
    ]);

    if ($result === false) {
      error_log('STAT COLLECTOR ERROR ' . $db->last_error.json_encode($data));
    }
  }

  public function droolsResponse($response, $uid) {
    $db = $this->getDb();
    $result = $db->insert('responses', [
      'uid' => $uid,
      'data' => json_encode($response),
    ]);

    if ($result === false) {
      error_log('STAT COLLECTOR ERROR ' . $db->last_error.json_encode($response));
    }
  }

  /**
   * Hook for the Stat Collector action for saving the email content to the DB
   * @param   [type]  $type  email/sms/whatever the class type is
   * @param   [type]  $to    The number/email sent to
   * @param   [type]  $uid   The GUID of the results
   * @param   [type]  $url   The main url shared
   * @param   [type]  $msg   The body of the message
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

    // Log Data
    // if ($result === false) {
    //   $request_parameters = array(strtolower($type), $to, $uid, $url, $message);
    //   error_log('STAT COLLECTOR ERROR ' . $db->last_error.json_encode($request_parameters));
    // }
  }

  public function peuData($staff, $client, $uid) {
    if (empty($uid)) {
      return;
    }

    $db = $this->getDb();

    if (!empty($staff)) {
      $result = $db->query($db->prepare(
        'INSERT into peu_staff (uid, data) VALUES (%s, %s)',
        $uid,
        json_encode($staff)
      ));

      // Log Data
      // if ($result === false) {
      //   $request_parameters = array($staff, $client, $uid);
      //   error_log('STAT COLLECTOR ERROR ' . $db->last_error.json_encode($request_parameters));
      // }
    }

    if (!empty($client)) {
      $result = $db->query($db->prepare(
        'INSERT into peu_client (uid, data) VALUES (%s, %s)',
        $uid,
        json_encode($client)
      ));

      // Log Data
      // if ($result === false) {
      //   $request_parameters = array($staff, $client, $uid);
      //   error_log('STAT COLLECTOR ERROR ' . $db->last_error.json_encode($request_parameters));
      // }
    }
  }

  public function responseUpdate() {
    $uid = $_POST['GUID'];
    $url = $_POST['url'];
    $programs = $_POST['programs'];

    if (empty($uid) || empty($url) || empty($programs)) {
      wp_send_json(array(
        'status' => 'fail',
        'message' => 'missing values'
      ));

      return wp_die();
    }

    $db = $this->getDb();
    $result = $db->query($db->prepare(
      'INSERT into response_update (uid, url, program_codes) VALUES (%s, %s, %s)',
      $uid,
      $url,
      $programs
    ));

    if ($result === false) {
      $request_parameters = array($uid, $url, $programs);
      error_log('STAT COLLECTOR ERROR ' . $db->last_error . json_encode($request_parameters));
    }

    wp_send_json(array('status' => 'ok'));
    wp_die();
  }

  private function getDb() {
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
      return new MockDatabase();
    }

    $db = new \wpdb($user, $password, $database, $host);
    $db->suppress_errors();
    $db->show_errors();

    if ($bootstrapped !== '5') {
      $this->__bootstrap($db);
    }

    return $db;
  }

  private function __bootstrap($db) {
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
      'CREATE TABLE IF NOT EXISTS peu_staff (
        id INT(11) NOT NULL AUTO_INCREMENT,
        uid VARCHAR(13) DEFAULT NULL,
        data MEDIUMBLOB NOT NULL,
        date DATETIME DEFAULT NOW(),
        PRIMARY KEY(id)
      ) ENGINE=InnoDB'
    );

    $db->query(
      'CREATE TABLE IF NOT EXISTS peu_client (
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

    // we will just let this fail if the columns exist
    // from previous migrations. But silence the error
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

    update_option('statc_bootstrapped', 5);
  }

  public function createSettingsSection() {
    $id = 'statcollect_aws';
    $title = 'Stat Collector Settings';
    $callback = [$this, 'settingsHeadingText'];
    $page = 'collector_config';

    add_settings_section($id, $title, $callback, $page);

    add_settings_field(
      'statc_host', // field name
      'MySQL host/endpoint', // label
      [$this, 'settingsFieldHtml'], // HTML content
      'collector_config', // page
      'statcollect_aws', // section
      ['statc_host', '']
    );

    add_settings_field(
      'statc_database',
      'Database Name',
      [$this, 'settingsFieldHtml'],
      'collector_config',
      'statcollect_aws',
      ['statc_database', '']
    );

    add_settings_field(
      'statc_user',
      'Database Username',
      [$this, 'settingsFieldHtml'],
      'collector_config',
      'statcollect_aws',
      ['statc_user', '']
    );

    add_settings_field(
      'statc_password',
      'Database Password',
      [$this, 'settingsFieldHtml'],
      'collector_config',
      'statcollect_aws',
      ['statc_password', '']
    );

    register_setting('statcollect_settings', 'statc_host');
    register_setting('statcollect_settings', 'statc_database');
    register_setting('statcollect_settings', 'statc_user');
    register_setting('statcollect_settings', 'statc_password');
  }

  public function settingsHeadingText() {
    echo "<p>Enter your MySQL credentials here.</p>";
  }

  public function settingsFieldHtml($args) {
    echo implode([
      '<input ',
      'type="text" ',
      'size="40" ',
      'name="' . $args[0] . '" ',
      'id="' . $args[0] . '" ',
      'value="' . get_option($args[0], '') . '" ',
      'placeholder="' . $args[1] . '" ',
      '/>'
    ], '');

    if ($_ENV[strtoupper($args[0])]) {
      echo implode([
        '<p class="description">',
        'Environment currently set to ',
        '<code>' . $_ENV[strtoupper($args[0])] . '</code>',
        '<p>'
      ], '');
    }
  }

  /**
   * Singleton class instance.
   * @return AdManager
   */
  public static function getInstance() {
    static $instance = null;

    if ($instance == null) {
      $instance = new self();
    }

    return $instance;
  }
}
