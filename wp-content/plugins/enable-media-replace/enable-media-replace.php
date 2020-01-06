<?php
/*
Plugin Name: Enable Media Replace
Plugin URI: https://wordpress.org/plugins/enable-media-replace/
Description: Enable replacing media files by uploading a new file in the "Edit Media" section of the WordPress Media Library.
Version: 3.3.7
Author: ShortPixel
Author URI: https://shortpixel.com
Text Domain: enable-media-replace
Domain Path: /languages
Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

/**
 * Main Plugin file
 * Set action hooks and add shortcode
 *
 * @author      ShortPixel  <https://shortpixel.com>
 * @copyright   ShortPixel 2018-2019
 * @package     wordpress
 * @subpackage  enable-media-replace
 *
 */

namespace EnableMediaReplace;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if(!defined("S3_UPLOADS_AUTOENABLE")) {
    define('S3_UPLOADS_AUTOENABLE', true);
}

if (! defined("EMR_ROOT_FILE")) {
	  define("EMR_ROOT_FILE", __FILE__);
}

if(!defined("SHORTPIXEL_AFFILIATE_CODE")) {
	define("SHORTPIXEL_AFFILIATE_CODE", 'VKG6LYN28044');
}

require_once('build/shortpixel/autoload.php');
require_once('classes/compat.php');
require_once('classes/functions.php');
require_once('classes/replacer.php');
require_once('classes/uihelper.php');
require_once('classes/file.php');
require_once('classes/cache.php');
require_once('classes/emr-plugin.php');
require_once('classes/externals.php');
require_once('thumbnail_updater.php');

$emr_plugin = EnableMediaReplacePlugin::get();
