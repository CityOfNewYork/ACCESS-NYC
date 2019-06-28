<?php

// phpcs:disable
/**
 * Plugin Name: Configure TinyMCE Settings
 * Description: Configuration for the classic WordPress text editor. Adds p, h2, h3, h4, and h5 block options to the TinyMCE editor. Removes the blockquote block. Removes underline, alignjustify, and forecolor from advanced toolbar. Removes the TinyMCE Emoji Plugin.
 * Author: Blue State Digital
 */
// phpcs:enable

/**
 * Configure TinyMCE settings
 * @param  array $init [<description>]
 * @return array       [<description>]
 */
add_filter('tiny_mce_before_init', function ($init) {
  $init['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5';
  return $init;
});

/**
 * Remove buttons from the primary toolbar
 * @param  array $button [<description>]
 * @return array         [<description>]
 */
add_filter('mce_buttons', function ($buttons) {
  $remove = array( 'blockquote' );
  return array_diff($buttons, $remove);
});

/**
 * Remove buttons from the advanced toolbar
 * @param  array $button [<description>]
 * @return array         [<description>]
 */
add_filter('mce_buttons_2', function ($buttons) {
  $remove = array( 'underline', 'alignjustify', 'forecolor' );
  return array_diff($buttons, $remove);
});

/**
 * Filter function used to remove the tinymce emoji plugin.
 * Taken from https://wordpress.org/plugins/disable-emojis/
 * @param  array $plugins
 * @return array Difference betwen the two arrays
 */
add_filter('tiny_mce_plugins', function ($plugins) {
  if (is_array($plugins)) {
    return array_diff($plugins, array( 'wpemoji' ));
  } else {
    return array();
  }
});
