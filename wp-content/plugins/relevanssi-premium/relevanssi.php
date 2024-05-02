<?php
/**
 * Relevanssi Premium
 *
 * /relevanssi.php
 *
 * @package Relevanssi Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 *
 * @wordpress-plugin
 * Plugin Name: Relevanssi Premium
 * Plugin URI: https://www.relevanssi.com/
 * Description: This premium plugin replaces WordPress search with a relevance-sorting search.
 * Version: 2.25.2
 * Author: Mikko Saari
 * Author URI: https://www.mikkosaari.fi/
 * Text Domain: relevanssi
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/*
	Copyright 2024 Mikko Saari  (email: mikko@mikkosaari.fi)

	This file is part of Relevanssi Premium, a search plugin for WordPress.

	Relevanssi Premium is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Relevanssi Premium is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Relevanssi Premium.  If not, see <http://www.gnu.org/licenses/>.
*/

add_action( 'init', 'relevanssi_premium_init' );
add_action( 'init', 'relevanssi_activate_auto_update' );
add_action( 'profile_update', 'relevanssi_profile_update', 9999 );
add_action( 'edit_user_profile_update', 'relevanssi_profile_update', 9999 );
add_action( 'user_register', 'relevanssi_profile_update', 9999 );
add_action( 'delete_user', 'relevanssi_delete_user' );
add_action( 'created_term', 'relevanssi_add_term', 9999, 3 );
add_action( 'edited_term', 'relevanssi_edit_term', 9999, 3 );
add_action( 'delete_term', 'relevanssi_delete_taxonomy_term', 9999, 3 );
add_action( 'save_post', 'relevanssi_save_postdata', 10 );
add_action( 'edit_attachment', 'relevanssi_save_postdata' );
add_action( 'edit_attachment', 'relevanssi_save_pdf_postdata' );
add_action( 'plugins_loaded', 'relevanssi_spamblock' );
add_filter( 'wpmu_drop_tables', 'relevanssi_wpmu_drop' );
add_action( 'network_admin_menu', 'relevanssi_network_menu' );
add_filter( 'attachment_link', 'relevanssi_post_link_replace', 10, 2 );
add_action( 'admin_enqueue_scripts', 'relevanssi_premium_add_admin_scripts', 11 );
add_filter( 'relevanssi_premium_tokenizer', 'relevanssi_enable_stemmer' );
add_filter( 'query_vars', 'relevanssi_premium_query_vars' );
add_filter( 'relevanssi_tabs', 'relevanssi_premium_add_tabs', 10 );
add_filter( 'relevanssi_phrase_queries', 'relevanssi_premium_phrase_queries', 10, 3 );

global $wp_version;
if ( version_compare( $wp_version, '5.1', '>=' ) ) {
	add_action( 'wp_initialize_site', 'relevanssi_new_blog', 200, 1 );
} else {
	add_action( 'wpmu_new_blog', 'relevanssi_new_blog', 10, 1 );
}

global $wpdb;
global $relevanssi_variables;

$relevanssi_variables['relevanssi_table']                      = $wpdb->prefix . 'relevanssi';
$relevanssi_variables['stopword_table']                        = $wpdb->prefix . 'relevanssi_stopwords';
$relevanssi_variables['log_table']                             = $wpdb->prefix . 'relevanssi_log';
$relevanssi_variables['tracking_table']                        = $wpdb->prefix . 'relevanssi_tracking'; // Note: this is also hardcoded in /premium/click-tracking.php.
$relevanssi_variables['post_type_weight_defaults']['post_tag'] = 0.5;
$relevanssi_variables['post_type_weight_defaults']['category'] = 0.5;
$relevanssi_variables['content_boost_default']                 = 5;
$relevanssi_variables['title_boost_default']                   = 5;
$relevanssi_variables['link_boost_default']                    = 0.75;
$relevanssi_variables['comment_boost_default']                 = 0.75;
$relevanssi_variables['database_version']                      = 23;
$relevanssi_variables['plugin_version']                        = '2.25.2';
$relevanssi_variables['plugin_dir']                            = plugin_dir_path( __FILE__ );
$relevanssi_variables['plugin_basename']                       = plugin_basename( __FILE__ );
$relevanssi_variables['file']                                  = __FILE__;
$relevanssi_variables['sidebar_capability']                    = 'edit_others_posts';

define( 'RELEVANSSI_PREMIUM', true );
define( 'RELEVANSSI_EU_SERVICES_URL', 'https://eu.relevanssiservices.com/' );
define( 'RELEVANSSI_US_SERVICES_URL', 'https://us.relevanssiservices.com/' );
if ( ! defined( 'RELEVANSSI_DEVELOP' ) ) {
	define( 'RELEVANSSI_DEVELOP', false );
}

require_once 'lib/admin-ajax.php';
require_once 'lib/common.php';
require_once 'lib/debug.php';
require_once 'lib/didyoumean.php';
require_once 'lib/excerpts-highlights.php';
require_once 'lib/indexing.php';
require_once 'lib/init.php';
require_once 'lib/install.php';
require_once 'lib/interface.php';
require_once 'lib/log.php';
require_once 'lib/options.php';
require_once 'lib/phrases.php';
require_once 'lib/privacy.php';
require_once 'lib/search.php';
require_once 'lib/search-tax-query.php';
require_once 'lib/search-query-restrictions.php';
require_once 'lib/shortcodes.php';
require_once 'lib/stopwords.php';
require_once 'lib/sorting.php';
require_once 'lib/user-searches.php';
require_once 'lib/utils.php';

require_once 'premium/admin-ajax.php';
require_once 'premium/body-stopwords.php';
require_once 'premium/class-relevanssi-language-packs.php';
require_once 'premium/class-relevanssi-spellcorrector.php';
require_once 'premium/class-relevanssi-wp-auto-update.php';
require_once 'premium/click-tracking.php';
require_once 'premium/common.php';
require_once 'premium/excerpts-highlights.php';
require_once 'premium/indexing.php';
require_once 'premium/interface.php';
require_once 'premium/network-options.php';
require_once 'premium/pdf-upload.php';
require_once 'premium/pinning.php';
require_once 'premium/post-metabox.php';
require_once 'premium/proximity.php';
require_once 'premium/redirects.php';
require_once 'premium/related.php';
require_once 'premium/search.php';
require_once 'premium/search-multi.php';
require_once 'premium/spamblock.php';

if ( version_compare( $wp_version, '5.0', '>=' ) ) {
	require_once 'premium/gutenberg-sidebar.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once 'premium/class-relevanssi-wp-cli-command.php';
	add_filter( 'relevanssi_search_ok', 'relevanssi_cli_query_ok', 10, 2 );
}

/**
 * Sets the relevanssi_search_ok true for searches.
 *
 * @param boolean  $ok    Whether it's ok to do a Relevanssi search or not.
 * @param WP_Query $query The query object.
 *
 * @return boolean Whether it's ok to do a Relevanssi search or not.
 */
function relevanssi_cli_query_ok( $ok, $query ) {
	if ( $query->is_search() ) {
		return true;
	}
	return $ok;
}

/**
 * Activates the auto update mechanism.
 *
 * @global array $relevanssi_variables Relevanssi global variables, used for plugin file name and version number.
 *
 * Hooks into 'init' filter hook to activate the auto update mechanism.
 */
function relevanssi_activate_auto_update() {
	global $relevanssi_variables;
	$api_key = get_network_option( null, 'relevanssi_api_key' );
	if ( ! $api_key ) {
		$api_key = get_option( 'relevanssi_api_key' );
	}
	if ( 'su9qtC30xCLLA' === crypt( $api_key, 'suolaa' ) ) {
		$relevanssi_plugin_remote_path = 'https://www.relevanssi.com/update/update-development-2022.php';
	} else {
		$relevanssi_plugin_remote_path = 'https://www.relevanssi.com/update/update-2022.php';
	}
	$relevanssi_variables['autoupdate'] = new Relevanssi_WP_Auto_Update(
		$relevanssi_variables['plugin_version'],
		$relevanssi_plugin_remote_path,
		$relevanssi_variables['plugin_basename']
	);
}
