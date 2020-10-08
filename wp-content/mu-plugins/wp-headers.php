<?php

/**
 * Plugin Name: WP Headers
 * Description: Adds headers before they are sent to the browser. Includes CSP and X-Frame/X-Content headers.
 * Author: NYC Opportunity
 */

add_action('wp_headers', function() {
  // Do not send headers if the admin area is in use
  if (is_user_logged_in() || is_admin()) {
    return;
  }

  // X-DNS-Prefetch-Control - Proactively perform domain name resolution on both
  // links that the user may choose to follow as well as URLs for items
  // eferenced by the document
  if (defined('WP_HEADERS_DNS_PREFETCH_CONTROL') && WP_HEADERS_DNS_PREFETCH_CONTROL) {
    header('X-DNS-Prefetch-Control: ' . WP_HEADERS_DNS_PREFETCH_CONTROL);
  }

  // X-Frame-Options: SAMEORIGIN - Prevent web pages from being loaded inside iFrame
  if (defined('WP_HEADERS_SAMEORIGIN') && WP_HEADERS_SAMEORIGIN) {
    header('X-Frame-Options: SAMEORIGIN');
  }

  // X-Content-Type-Options: nosniff - Prevent MIME Type sniffing
  if (defined('WP_HEADERS_NOSNIFF') && WP_HEADERS_NOSNIFF) {
    header('X-Content-Type-Options: nosniff');
  }

  // Create a unique nonce (WP nonces are not unique by default, add rand()).
  $scripts = wp_create_nonce('csp_scripts_nonce_' . rand());

  // CSP Header
  $csp = (defined('WP_HEADERS_CSP_REPORTING') && WP_HEADERS_CSP_REPORTING)
    ? 'Content-Security-Policy-Report-Only: ' : 'Content-Security-Policy: ';

  /**
   * False values can be filtered out of the array. Output will be;
   * "Content-Security-Policy{{ -Report-Only }}: {{ source }}-src {{ policy }}; {{ source }}-src {{ policy }};"
   * Default policies;
   *
   * @param  Default  self
   * @param  Style    self
   * @param  Font     self
   * @param  Img      self
   * @param  Script   self nonce-{{ CSP_SCRIPT_NONCE }}
   * @param  Connect  self
   * @param  Frame    none
   * @param  Object   none
   */
  $csp = $csp . implode('; ', array_filter([
    (defined('WP_HEADERS_CSP_DEFAULT')) ? "default-src 'self' " . WP_HEADERS_CSP_DEFAULT : "default-src 'self'",
    (defined('WP_HEADERS_CSP_STYLE')) ? "style-src 'self' " . WP_HEADERS_CSP_STYLE : "style-src 'self'",
    (defined('WP_HEADERS_CSP_FONT')) ? "font-src 'self' " . WP_HEADERS_CSP_FONT : "font-src 'self'",
    (defined('WP_HEADERS_CSP_IMG')) ? "img-src 'self' " . WP_HEADERS_CSP_IMG : "img-src 'self'",
    (defined('WP_HEADERS_CSP_SCRIPT'))
      ? "script-src 'self' 'nonce-$scripts' " . WP_HEADERS_CSP_SCRIPT : "script-src 'self' 'nonce-$scripts'",
    (defined('WP_HEADERS_CSP_CONNECT')) ? "connect-src 'self' " . WP_HEADERS_CSP_CONNECT : "connect-src 'self'",
    (defined('WP_HEADERS_CSP_FRAME')) ? "frame-src " . WP_HEADERS_CSP_FRAME : "frame-src 'none'",
    (defined('WP_HEADERS_CSP_OBJECT')) ? "object-src " . WP_HEADERS_CSP_OBJECT : "object-src 'none'"
  ]));

  // Append the reporting URL if needed
  if (defined('WP_HEADERS_CSP_REPORTING') && WP_HEADERS_CSP_REPORTING) {
    $csp = $csp . '; report-uri ' . WP_HEADERS_CSP_REPORTING;
  }

  if (defined('WP_HEADERS_CSP_SEND') && WP_HEADERS_CSP_SEND) {
    // Send the header
    header($csp);

    // Add nonce to scripts loaded through the wp_enqueue_script() or wp_add_inline_script()
    add_filter('script_loader_tag', function($tag) use ($scripts) {
      return preg_replace('/<script( )*/', '<script nonce="' . $scripts . '"$1', $tag);
    });

    // Define global var for use elsewhere (to set to template content)
    // phpcs:disable
    define('CSP_SCRIPT_NONCE', $scripts);
    // phpcs:enable
  }
});
