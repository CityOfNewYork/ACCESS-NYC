<?php

/**
 * Automatically log in to the CMS if the admin parameter and login constants are set.
 *
 * @author NYC Opportunity
 */

add_action('plugins_loaded', function() {
  if (isset($_REQUEST['admin']) && defined('LOGIN_USERNAME') && defined('LOGIN_PASSWORD')) {
    $response = wp_signon(array(
      'user_login' => LOGIN_USERNAME,
      'user_password' => LOGIN_PASSWORD
    ));

    if ($response->has_errors()) {
      $url = get_home_url() . '?admin=1';

      wp_die(
        __("Auto log in failed. Check your credentials and <a href=\"$url\">click here to try again</a>."),
        __('Auto log in failed')
      );
    }

    wp_redirect(get_home_url());
  }
});
