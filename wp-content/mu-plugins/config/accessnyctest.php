<?php

/**
 * Auto Updates
 */

// WordPress Core

// Update all versions
// add_filter('auto_update_core', '__return_true');

// or

// More prescriptive updaters
add_filter('allow_dev_auto_core_updates', '__return_true');
add_filter('allow_minor_auto_core_updates', '__return_true');
add_filter('allow_major_auto_core_updates', '__return_false');

// To enable automatic updates even if a VCS folder (.git, .hg, .svn etc) was
// found in the WordPress directory or any of its parent directories:
add_filter('automatic_updates_is_vcs_checkout', '__return_false', 1);

// Notifications
add_filter('auto_core_update_send_email', '__return_true');

// Plugins
add_filter('auto_update_plugin', '__return_true');

// or

// Update a more specific list of plugins

// add_filter('auto_update_plugin', function($update, $item) {
//   $plugins = array (
//   //  ... list of plugins to auto update ...
//   );
//
//   if (in_array($item->slug, $plugins)) {
//     // Always update plugins in this array
//     return true;
//   } else {
//     // Else, use the normal API response to decide whether to update or not
//     return $update;
//   }
// }, 10, 2);
