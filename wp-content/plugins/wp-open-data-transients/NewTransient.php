<?php

namespace NYCO\Transients\NewTransient;

/**
 * Dependencies
 */

use NYCO\Transients as Transients;

/**
 * Constants
 */

const ID = 'open_data_transients';

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

  // Save the transient in our options
  $transient = Transients::save($_POST[ID . '_name'], $_POST[ID . '_url']);

  // Set the transient data
  Transients::set($transient['name']);

  // Redirect back to the page
  wp_redirect($_POST['_wp_http_referer']);
  exit();
});
