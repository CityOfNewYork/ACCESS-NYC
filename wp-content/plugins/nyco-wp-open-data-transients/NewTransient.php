<?php

namespace nyco\WpOpenDataTransients\NewTransient;

/**
 * Dependencies
 */

use nyco\WpOpenDataTransients\Transients as Transients;

/**
 * Constants
 */

const ID = 'open_data_transients';
const NAME_REGEX = '/^[A-Za-z_]*$/';
const TRANSIENTS_OPTION = 'open_data_transients_saved';

/**
 * Variables
 */

$action = 'admin_action_' . ID;
$nonce = ID . '_nonce';

/**
 * Functions
 */

/**
 * Action for saving new transients and updating existing ones.
 */
add_action($action, function () use ($nonce, $action) {
  if (wp_verify_nonce($_POST[$nonce], ID)) {
    exit;
  }

  // Validate params
  $name = filter_var($_POST[ID . '_name'], FILTER_VALIDATE_REGEXP,
    ['options' => ['regexp' => NAME_REGEX]]);

  $url = filter_var($_POST[ID . '_url'], FILTER_VALIDATE_URL);

  if (!$name || !$url) {
    echo 'Valid name (ex; "my_name" or "MY_NAME")
      and url (ex; "https://endpoint.com") are required.';
    exit;
  }

  $option = get_option(TRANSIENTS_OPTION, null);

  $transients = (null === $option || $option === '')
    ? [] : json_decode($option, true);

  $transient_new = array(
    'name' => $name,
    'url' => $url
  );

  $update = false;

  foreach ($transients as $i => $transient) {
    if ($transient['name'] === $transient_new['name']) {
      $transients[$i]['url'] = $transient_new['url'];
      $update = true;
    }
  }

  if (!$update) {
    array_push($transients, $transient_new);
  }

  // Save our list of transients
  update_option(TRANSIENTS_OPTION, json_encode($transients));

  // Set the transient
  Transients\set($transient_new['name']);

  // Redirect back to the page
  wp_redirect($_POST['_wp_http_referer']);
  exit();
});
