<?php
/**
 * Plugin Name: Bulk Delete
 * Plugin Script: bulk-delete.php
 * Plugin URI: https://bulkwp.com
 * Description: Bulk delete users and posts from selected categories, tags, post types, custom taxonomies or by post status like drafts, scheduled posts, revisions etc.
 * Version: 6.0.2
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Author: Sudar
 * Author URI: https://sudarmuthu.com/
 * Text Domain: bulk-delete
 * Domain Path: languages/
 * === RELEASE NOTES ===
 * Check readme file for full release notes.
 */

/**
 * Copyright 2009  Sudar Muthu  (email : sudar@sudarmuthu.com)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Include the stub of the old `Bulk_Delete` class, so that old add-ons don't generate a fatal error.
require_once 'include/Deprecated/old-bulk-delete.php';

if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
	/**
	 * Version 6.0.0 of the Bulk Delete plugin dropped support for PHP 5.2.
	 * If you are still struck with PHP 5.2 and can't update, then use v5.6.1 of the plugin.
	 * But note that some add-ons may not work.
	 *
	 * @see   http://sudarmuthu.com/blog/why-i-am-dropping-support-for-php-5-2-in-my-wordpress-plugins/
	 * @since 6.0.0
	 */
	function bulk_delete_compatibility_notice() {
		?>
		<div class="error">
			<p>
				<?php
				printf(
					__( 'Bulk Delete requires at least PHP 5.3 to function properly. Please upgrade PHP or use <a href="%s">v5.6.1 of Bulk Delete</a>.', 'bulk-delete' ), // @codingStandardsIgnoreLine
					'https://downloads.wordpress.org/plugin/bulk-delete.5.6.1.zip'
				);
				?>
			</p>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'bulk_delete_compatibility_notice' );

	/**
	 * Deactivate Bulk Delete.
	 *
	 * @since 6.0.0
	 */
	function bulk_delete_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
	add_action( 'admin_init', 'bulk_delete_deactivate' );

	return;
}

// PHP is at least 5.3, so we can safely include namespace code.
require_once 'load-bulk-delete.php';
bulk_delete_load( __FILE__ );
