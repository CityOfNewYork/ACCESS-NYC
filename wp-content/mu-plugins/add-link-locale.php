<?php

/**
 * Add locale to a link if it currently doesn't exist
 * Examples, if the locale is 'es':
 * https://access.nyc.gov/programs -> https://access.nyc.gov/es/programs
 * https://access.nyc.gov/es/programs -> https://access.nyc.gov/es/programs
 * If locale is 'en':
 * https://access.nyc.gov/programs -> https://access.nyc.gov/programs
 * 
 * @param string $original_url    The original URL that may need localization
 * @param string $site_url        The base site URL without locale
 * @param string $site_url_localized The site URL with locale segment
 * @return string                 The URL with proper localization
 */
if (! function_exists('convert_link_locale')) {
  function convert_link_locale($original_url, $site_url, $site_url_localized) {
    $site_url = rtrim($site_url, '/');
    $site_url_localized = rtrim($site_url_localized, '/');

    if (strpos($original_url, $site_url_localized) === false) {
      return str_replace($site_url, $site_url_localized, $original_url);
    }
    // if original url is already localized, return it
    return $original_url;
  }
}
