<?php

// Add locale to a link if it currently doesn't exist
// Examples, if the locale is 'es':
// https://jobs.nyc.gov/employers -> https://jobs.nyc.gov/es/employers
// https://jobs.nyc.gov/es/employers -> https://jobs.nyc.gov/es/employers
// If locale is 'en':
// https://jobs.nyc.gov/employers -> https://jobs.nyc.gov/employers
if (! function_exists('convert_link_locale')) {
  function convert_link_locale($original_url, $site_url, $site_url_localized) {
    if (strpos($original_url, $site_url_localized) === false) {
      $site_url_localized_to_use = $site_url_localized;
      if (substr($site_url_localized_to_use, -1) === "/") {
        $site_url_localized_to_use = substr_replace($site_url_localized_to_use, "", -1);
      }
      return str_replace($site_url, $site_url_localized_to_use, $original_url);
    }
    // if original url is already localized, return it
    return $original_url;
  }
}
