<?php
/**
 * Plugin Name: Register Menus
 * Description: Adds header and footer menu.
 * Author: Mayor's Office for Economic Opportunity
 */

add_action('init', function() {
  register_nav_menus(
    array(
      'header-menu' => __('Nav bar'),
      'header-menu-b' => __('Nav bar [variant B for A/B testing]'),
      'get-help-now' => __('Footer Menu 1 [left]'),
      'for-caseworkers' => __('Footer Menu 2 [left]'),
      'programs' => __('Footer Menu 3 [center]'),
      'about-access-nyc' => __('Footer Menu 4 [right]'),
    )
  );
});
