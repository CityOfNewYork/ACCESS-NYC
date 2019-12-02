<?php

// phpcs:disable
/**
 * Plugin Name: Nonce Life
 * Description: Changing the default WordPress nonce lifetime from 1 day to 30 minutes. https://codex.wordpress.org/WordPress_Nonces#Modifying_the_nonce_system
 * Author: NYC Opportunity
 */
// phpcs:enable

add_filter('nonce_life', function() {
  return 0.5 * HOUR_IN_SECONDS;
});
