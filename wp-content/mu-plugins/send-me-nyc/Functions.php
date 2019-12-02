<?php

namespace SMNYC;

/**
 * Public-facing convenience functions
 */

function get_current_url() {
  global $wp;

  return home_url(esc_url(add_query_arg(null, null)));
}

function hash($data) {
  return wp_create_nonce('bsd_smnyc_token_' . $data);
}
