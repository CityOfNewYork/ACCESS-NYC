<?php

namespace FieldScreener;

// use ...;

class Backend {
  /**
   * Key for validating the drools request nonce
   *
   * @var String
   */
  const NONCE_KEY = 'field_screener_nonce';

  /**
   * Time, in seconds, for the application's nonce session life
   *
   * @var
   */
  const NONCE_LIFE = HOUR_IN_SECONDS;

  /**
   * Constructor
   *
   * @return  Object  Instance of Backend
   */
  public function __construct() {
    /** Adds database tables where the Stat Collector plugin can record data */
    add_action('statc_bootstrap', [$this, 'statCollectorBootstrap']);

    /** Registers hooks needed for the Stat Collector plugin to record data */
    add_action('statc_register', [$this, 'statCollectorRegister']);

    /** Registers the Share URL update REST route */
    add_action('rest_api_init', [$this, 'registerRestRoute']);

    return $this;
  }

  /**
   * Register the Outreach taxonomy used to determine which programs are
   * typically shared during outreach sessions
   */
  public function registerTaxonomy() {
    register_taxonomy(
      'outreach', ['programs'],
      array(
        'label' => __('Outreach Categories', Util::TRANSLATION_ID),
        'labels' => array(
          'archives' => __('Outreach', Util::TRANSLATION_ID)
        ),
        'hierarchical' => true,
        'public' => false // will affect the terms rest route
      )
    );
  }

  /**
   * Adds the actions needed for the Stat Collector plugin to record field
   * screener data.
   *
   * @param  Object  $statCollector  Instance of the StatCollector
   */
  public function statCollectorRegister($statCollector) {
    $this->statCollector = $statCollector;

    /**
     * When the application client sends field screener data to the screening
     * API (Drools) this action hook is triggered and passed data we want to
     * save to the Stat Collector.
     */

    add_action(
      'drools_request', [$this, 'droolsRequest'],
      $statCollector->settings->priority, 2
    );

    /**
     * When the user modifies their screening results by removing programs,
     * the shared URL is updated.
     *
     * Since this is a public application both actions are needed. wp_ajax
     * registers admin ajax hooks authenticated WordPress admin users while
     * wp_ajax_nopriv registers hooks for anonymous users.
     */

    add_action(
      'wp_ajax_response_update', [$this, 'responseUpdate'],
      $statCollector->settings->priority
    );

    add_action(
      'wp_ajax_nopriv_response_update', [$this, 'responseUpdate'],
      $statCollector->settings->priority
    );
  }

  /**
   * When the application client sends field screener data to the screening
   * API (Drools) this action hook is triggered and passed data we want to
   * save to the Stat Collector.
   *
   * @param  Array   $data  The request data to be sent to the screening API
   * @param  String  $ID    Unique ID associated by the request
   */
  public function droolsRequest($data, $ID) {
    add_filter('nonce_life', 'FieldScreener\Auth::nonceLife');

    /**
     * Verify the WordPress nonce sent with the request
     */

    if (false === wp_verify_nonce($_POST['nonce'], Auth::NONCE_KEY)) {
      remove_filter('nonce_life', 'FieldScreener\Auth::nonceLife');

      wp_send_json(array(
        'status' => 'fail',
        'message' => __('Session expired', Util::TRANSLATION_ID)
      ), 401);

      wp_die();
    }

    remove_filter('nonce_life', 'FieldScreener\Auth::nonceLife');

    $this->collectData($_POST['staff'], $_POST['client'], $ID);
  }

  /**
   * Hook for Stat Collector that creates the database tables for collecting
   * data if they do not exist.
   *
   * @param  Object  $db  Instance of wpdb (WordPress DB abstraction method)
   */
  public function statCollectorBootstrap($db) {
    if ('development' === WP_ENV) {
      debug('Field Screener: Bootstrap Stat Collector Database');

      debug($db);
    }

    // $db->query(
    //   'CREATE TABLE IF NOT EXISTS field_screener_staff (
    //     id INT(11) NOT NULL AUTO_INCREMENT,
    //     uid VARCHAR(13) DEFAULT NULL,
    //     data MEDIUMBLOB NOT NULL,
    //     date DATETIME DEFAULT NOW(),
    //     PRIMARY KEY(id)
    //   ) ENGINE=InnoDB'
    // );

    // $db->query(
    //   'CREATE TABLE IF NOT EXISTS field_screener_client (
    //     id INT(11) NOT NULL AUTO_INCREMENT,
    //     uid VARCHAR(13) DEFAULT NULL,
    //     data MEDIUMBLOB NOT NULL,
    //     date DATETIME DEFAULT NOW(),
    //     PRIMARY KEY(id)
    //   ) ENGINE=InnoDB'
    // );

    // $db->query(
    //   'CREATE TABLE IF NOT EXISTS field_screener_response_update (
    //     id INT(11) NOT NULL AUTO_INCREMENT,
    //     uid VARCHAR(13) DEFAULT NULL,
    //     url MEDIUMBLOB NOT NULL,
    //     program_codes VARCHAR(256) DEFAULT NULL,
    //     PRIMARY KEY(id)
    //   ) ENGINE=InnoDB'
    // );

    // // Fail if the columns exist from previous migrations but silence the error.
    // $db->hide_errors();

    // $db->query(
    //   'ALTER TABLE field_screener_response_update
    //   ADD date DATETIME DEFAULT NOW() AFTER program_codes'
    // );

    // $db->show_errors();

    // update_option('statc_bootstrapped', 5);
  }

  /**
   * Send data to Stat Collector
   *
   * @param  Array   $staff   Staff data
   * @param  Array   $client  Client data
   * @param  String  $uid     A unique ID created to associate
   */
  private function collectData($staff, $client, $uid) {
    if ('development' === WP_ENV) {
      debug('Field Screener: Collect Screener Data ' . var_export([
        'field_screener_client', array(
          'uid' => $uid,
          'data' => json_encode($client)
        )
      ], true));
    } else {
      // if (!empty($staff)) {
      //   $this->statCollector->collect('field_screener_staff', array(
      //     'uid' => $uid,
      //     'data' => json_encode($staff)
      //   ));
      // }

      // if (!empty($client)) {
      //   $this->statCollector->collect('field_screener_client', array(
      //     'uid' => $uid,
      //     'data' => json_encode($client)
      //   ));
      // }
    }
  }

  /**
   * Update the URL stored in the database.
   *
   * @return  Array  Sends a JSON response of fail or success
   */
  public function responseUpdate() {
    $uid = $_POST['GUID'];

    $url = $_POST['url'];

    $programs = $_POST['programs'];

    if (empty($uid) || empty($url) || empty($programs)) {
      wp_send_json(array(
        'status' => 'fail',
        'message' => __('Missing values', Util::TRANSLATION_ID)
      ));

      wp_die();
    }

    if ('development' === WP_ENV) {
      debug('Field Screener: Collect Response Update ' . var_export([
        'field_screener_response_update', array(
          'uid' => $uid,
          'url' => $url,
          'program_codes' => $programs
        )
      ], true));
    } else {
      // $this->statCollector->collect('field_screener_response_update', [
      //   'uid' => $uid,
      //   'url' => $url,
      //   'program_codes' => $programs
      // ]);
    }

    wp_send_json(array(
      'status' => 'ok'
    ));

    wp_die();
  }

  /**
   * Registers a rest route that returns a shareable url and hash for the Send Me
   * NYC plugin. This is used when program results of the Field Screener results
   * are modified.
   */
  public function registerRestRoute() {
    register_rest_route('api/v1', '/shareurl/', array(
      'methods' => 'GET',
      'permission_callback' => [Auth::class, 'smnycToken'],
      'callback' => function(WP_REST_Request $request) {
        $params = $request->get_params();

        unset($params['url']);

        // Create the URL
        $data = Util::shareData($request->get_params());

        // Create the response object and status code
        $response = new WP_REST_Response($data);

        $response->set_status(200);

        return $response;
      }
    ));
  }
}
