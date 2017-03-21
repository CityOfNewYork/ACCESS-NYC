<?php

/**
 * Configure TinyMCE settings
 */
function bsdstarter_configure_tinymce( $init ) {
  $init['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5';
  return $init;
}
add_filter( 'tiny_mce_before_init', 'bsdstarter_configure_tinymce' );

/**
 * Remove buttons from the primary toolbar
 */
function bsdstarter_mce_buttons( $buttons ) {
  $remove = array( 'blockquote' );
  return array_diff( $buttons, $remove );
}
add_filter( 'mce_buttons', 'bsdstarter_mce_buttons' );

/**
 * Remove buttons from the advanced toolbar
 */
function bsdstarter_mce_buttons_2( $buttons ) {
  $remove = array( 'underline', 'alignjustify', 'forecolor' );
  return array_diff( $buttons, $remove );
}
add_filter( 'mce_buttons_2', 'bsdstarter_mce_buttons_2' );

/**
 * Filter function used to remove the tinymce emoji plugin.
 *
 * Taken from https://wordpress.org/plugins/disable-emojis/
 *
 * @param    array  $plugins
 * @return   array  Difference betwen the two arrays
 */
function bsdstarter_disable_emojis_tinymce( $plugins ) {
  if ( is_array( $plugins ) ) {
    return array_diff( $plugins, array( 'wpemoji' ) );
  } else {
    return array();
  }
}
add_filter( 'tiny_mce_plugins', 'bsdstarter_disable_emojis_tinymce' );
