<?php
/**
 * @package   wp-bitly
 * @author    Temerity Studios <info@temeritystudios.com>
 * @license   GPL-2.0+
 * @link      http://wordpress.org/plugins/wp-bitly
 */

if (!defined('WP_UNINSTALL_PLUGIN'))
    die;


/**
 *
 * @return void
 */
function wpbitly_uninstall() {
    // Delete associated options
    delete_option(WPBITLY_OPTIONS);
    delete_option(WPBITLY_AUTHORIZED);

    // Grab all posts with an attached shortlink
    $posts = get_posts('numberposts=-1&post_type=any&meta_key=_wpbitly');

    // And remove our meta information from them
    foreach ($posts as $post){
        delete_post_meta($post->ID, '_wpbitly');
    }

}
wpbitly_uninstall();