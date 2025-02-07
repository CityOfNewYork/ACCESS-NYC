<?php

/**
 * Plugin Name: A/B test variant
 * Description: Set the A/B test variant as a cookie and global variable
 * Author: NYC Opportunity
 */

// Done in the init hook so that it is set before page caching logic
add_action('init', function() {
  global $ab_test_variant;
  
  if (isset($_COOKIE['ab_test_variant'])) {
      $ab_test_variant = $_COOKIE['ab_test_variant'];
  } else {
      $ab_test_variant = rand(0, 1) ? 'a' : 'b';
      setcookie('ab_test_variant', $ab_test_variant, time() + (DAY_IN_SECONDS * 30), COOKIEPATH, COOKIE_DOMAIN);
  }
});
