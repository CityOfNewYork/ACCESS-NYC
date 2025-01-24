<?php

// phpcs:disable
/**
 * Plugin Name: A/B test redirect
 * Description: Checks if the 'anyc_v' query parameter is set. If not, the function redirects to the specified A/B test variant 
 * Author: NYC Opportunity
 */
// phpcs:enable

function a_b_test_redirect($variant) {
  $anyc_v = get_query_var('anyc_v'); // Retrieve the query parameter.

  if (empty($anyc_v)) {
      // If 'anyc_v' is not set, generate the new URL with 'anyc_v=a'.
      $new_url = add_query_arg('anyc_v', $variant);
      
      // Redirect to the new URL.
      wp_redirect($new_url);
      exit; // Always exit after a redirect.
  }
}
