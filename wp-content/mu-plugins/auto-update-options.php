<?php

// phpcs:disable
/**
 * Plugin Name: Automatically Update Options
 * Description: Disables pingback flag, pings, comments, closes comments for old posts, notifies if there are new comments, and disables user registration.
 * Author: NYC Opportunity
 */
// phpcs:enable

$options = [
  'default_pingback_flag' => false,
  'default_ping_status' => false,
  'default_comment_status' => false,
  'close_comments_for_old_posts' => true,
  'comments_notify' => true,
  'comment_moderation' => true,
  'users_can_register' => false
];

foreach ($options as $key => $value) {
  update_option($key, $value);
}
