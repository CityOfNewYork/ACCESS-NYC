<?php

/**
 * Plugin Name: WP Headers
 * Description: Adds headers before they are sent to the browser. Includes CSP and X-Frame/X-Content headers.
 * Author: NYC Opportunity
 */

add_action('wp_headers', function() {
  $req = $_SERVER['REQUEST_URI'];
  if ($req === '/peu/' || $req === '/peu/results/') {
    return;
  }

  // Do not send headers if the admin area is in use
  if (is_user_logged_in() || is_admin()) {
    return;
  }

  // X-Frame-Options: SAMEORIGIN - Prevent web pages from being loaded inside iFrame
  header('X-Frame-Options: SAMEORIGIN');

  // X-Content-Type-Options: nosniff - Prevent MIME Type sniffing
  header('X-Content-Type-Options: nosniff');

  // Create a unique nonce (WP nonces are not unique by default, add rand()).
  $scripts = wp_create_nonce('csp_scripts_nonce_' . rand());

  // Add nonce CSP header
  // Set default content security policy to only allow content from self
  header('Content-Security-Policy: ' . implode('; ', [
    // Set default to only allow content from self
    "default-src 'self'",
    // CSS, allow all and inline CSS
    "style-src * 'unsafe-inline'",
    // Images, allow all, data attribute, and inline images
    "img-src * data: 'unsafe-inline'",
    // JS, allow specific scripts with nonce and their scripts
    "script-src 'self' 'nonce-$scripts' 'strict-dynamic'",
    // iFrames, google only
    "frame-src https://www.google.com https://www.googletagmanager.com",
    // Fonts, allow from self and gstatic.com (for Google Maps)
    "font-src 'self' https://fonts.gstatic.com",
    // No Flash
    "object-src 'none'"
  ]));

  // Add nonce to scripts loaded through the enqueue
  add_filter('script_loader_tag', function($tag, $handle) use ($scripts) {
    return str_replace(' src', ' nonce="' . $scripts . '" src', $tag);
  }, 10, 2);

  // Define global var for use elsewhere (to set to template content)
  // phpcs:disable
  define('CSP_SCRIPT_NONCE', $scripts);
  // phpcs:enable
});
