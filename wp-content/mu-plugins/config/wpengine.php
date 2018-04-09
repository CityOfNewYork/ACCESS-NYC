<?php

/**
 * WP Engine Production environment config
 */

// Auto update WordPress Admin options
foreach ($_ENV as $key => $value) {
  if (substr($key, 0, 10) === 'WP_OPTION_') {
    update_option(strtolower(str_replace('WP_OPTION_', '', $key)), $value);
  }
}
