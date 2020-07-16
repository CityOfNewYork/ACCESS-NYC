<?php

/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package access
 */

// phpcs:disable
/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
// phpcs:enable
add_action('enqueue_block_editor_assets', function() {
  wp_enqueue_script(
    'access/smnyc-email-button',                                // theme/name
    ACCESS\block('smnyc-email-button/index.js', true),            // script uri
    array('wp-blocks', 'wp-element', 'wp-editor'),              // dependencies
    ('development' === WP_ENV) ? null : wp_get_theme()->version // use theme version if not in development
  );

  // register_block_type('access/smnyc-email-button', array(
  //   'editor_script' => 'smnyc-email-button-block-editor',
  //   'editor_style'  => 'smnyc-email-button-block-editor',
  //   'style'         => 'smnyc-email-button-block',
  // ));
});
