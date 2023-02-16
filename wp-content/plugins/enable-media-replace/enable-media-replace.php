<?php
/**
 * Plugin Name: Enable Media Replace
 * Plugin URI: https://wordpress.org/plugins/enable-media-replace/
 * Description: Enable replacing media files by uploading a new file in the "Edit Media" section of the WordPress Media Library.
 * Version: 3.6.3
 * Author: ShortPixel
 * Author URI: https://shortpixel.com
 * GitHub Plugin URI: https://github.com/short-pixel-optimizer/enable-media-replace
 * Text Domain: enable-media-replace
 * Domain Path: /languages
 * Dual licensed under the MIT and GPL licenses:
 * License URI: http://www.opensource.org/licenses/mit-license.php
 * License URI: http://www.gnu.org/licenses/gpl.html
 */

/**
 * Main Plugin file
 * Set action hooks and add shortcode
 *
 * @author      ShortPixel  <https://shortpixel.com>
 * @copyright   ShortPixel 2018-2020
 * @package     wordpress
 * @subpackage  enable-media-replace
 *
 */

namespace EnableMediaReplace;

define('EMR_VERSION', '3.6.3');

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* Not sure why we define this?
if(!defined("S3_UPLOADS_AUTOENABLE")) {
    define('S3_UPLOADS_AUTOENABLE', true);
} */

if (! defined("EMR_ROOT_FILE")) {
	  define("EMR_ROOT_FILE", __FILE__);
}

if(!defined("SHORTPIXEL_AFFILIATE_CODE")) {
	define("SHORTPIXEL_AFFILIATE_CODE", 'VKG6LYN28044');
}

/** Usage:
* Define in wp-config.php
* // User must have this capability to replace all
* define('EMR_CAPABILITY' ,'edit_upload_all' );
* // User must have first capability to replace all OR second capability to replace only own files
* define('EMR_CAPABILITY' ,array('edit_upload_all', 'edit_upload_user') );
*
*
**/
if (! defined('EMR_CAPABILITY'))
  define('EMR_CAPABILITY', false);

/* if (! defined('EMR_CAPABILITY_USERONLY'))
  define('EMR_CAPABILITY_USERONLY', false); */

$plugin_path = plugin_dir_path(EMR_ROOT_FILE);

require_once($plugin_path . 'build/shortpixel/autoload.php');
require_once($plugin_path . 'classes/compat.php');
require_once($plugin_path . 'classes/functions.php');
require_once($plugin_path . 'classes/replacer.php');
require_once($plugin_path . 'classes/uihelper.php');
require_once($plugin_path . 'classes/file.php');
require_once($plugin_path . 'classes/cache.php');
require_once($plugin_path . 'classes/emr-plugin.php');
require_once($plugin_path . 'classes/externals.php');
require_once($plugin_path . 'classes/external/elementor.php');
require_once($plugin_path . 'classes/external/wpbakery.php');
require_once($plugin_path . 'classes/external/upsell_installer.php');
require_once($plugin_path . 'thumbnail_updater.php');

$emr_plugin = EnableMediaReplacePlugin::get();

register_uninstall_hook(__FILE__, '\EnableMediaReplace\emr_uninstall');

function emr_uninstall()
{
	delete_option('enable_media_replace');
	delete_option('emr_news');
}
