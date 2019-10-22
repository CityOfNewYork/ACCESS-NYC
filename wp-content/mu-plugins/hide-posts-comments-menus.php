<?php

/**
 * Plugin Name: Hide Posts and Comments Menus
 * Description: Hide the "Posts" and "Comments" menus in the Admin menu.
 * Author: Blue State Digital
 */

add_action('admin_menu', function() {
  remove_menu_page('edit.php');
  remove_menu_page('edit-comments.php');
});
