<?php
/*
Plugin Name: Enable Media Replace
Plugin URI: http://www.mansjonasson.se/enable-media-replace
Description: Enable replacing media files by uploading a new file in the "Edit Media" section of the WordPress Media Library.
Version: 3.1.1
Author: Måns Jonasson
Author URI: http://www.mansjonasson.se

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

/**
 * Main Plugin file
 * Set action hooks and add shortcode
 *
 * @author      Måns Jonasson  <http://www.mansjonasson.se>
 * @copyright   Måns Jonasson 13 sep 2010
 * @package     wordpress
 * @subpackage  enable-media-replace
 *
 */

add_action('admin_init', 'enable_media_replace_init');
add_action('admin_menu', 'emr_menu');
add_filter('attachment_fields_to_edit', 'enable_media_replace', 10, 2);
add_filter('media_row_actions', 'add_media_action', 10, 2);

add_shortcode('file_modified', 'emr_get_modified_date');

/**
 * Register this file in WordPress so we can call it with a ?page= GET var.
 * To suppress it in the menu we give it an empty menu title.
 */
function emr_menu() {
	add_submenu_page(NULL, __("Replace media", "enable-media-replace"), '','upload_files', 'enable-media-replace/enable-media-replace', 'emr_options');
}

/**
 * Initialize this plugin. Called by 'admin_init' hook.
 * Only languages files needs loading during init.
 */
function enable_media_replace_init() {
	load_plugin_textdomain( 'enable-media-replace', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Add some new fields to the attachment edit panel.
 * @param array form fields edit panel
 * @return array form fields with enable-media-replace fields added
 */
function enable_media_replace( $form_fields, $post ) {

	$url = admin_url( "upload.php?page=enable-media-replace/enable-media-replace.php&action=media_replace&attachment_id=" . $post->ID);
	$action = "media_replace";
  	$editurl = wp_nonce_url( $url, $action );

	if (FORCE_SSL_ADMIN) {
		$editurl = str_replace("http:", "https:", $editurl);
	}
	$link = "href=\"$editurl\"";
	$form_fields["enable-media-replace"] = array("label" => __("Replace media", "enable-media-replace"), "input" => "html", "html" => "<p><a class='button-secondary'$link>" . __("Upload a new file", "enable-media-replace") . "</a></p>", "helps" => __("To replace the current file, click the link and upload a replacement.", "enable-media-replace"));

	return $form_fields;
}

/**
 * Load the replace media panel.
 * Panel is show on the action 'media-replace' and a given attachement.
 * Called by GET var ?page=enable-media-replace/enable-media-replace.php
 */
function emr_options() {

	if ( isset( $_GET['action'] ) && $_GET['action'] == 'media_replace' ) {
    	check_admin_referer( 'media_replace' ); // die if invalid or missing nonce
		if ( array_key_exists("attachment_id", $_GET) && (int) $_GET["attachment_id"] > 0) {
			include("popup.php");
		}
	}
	
	if ( isset( $_GET['action'] ) && $_GET['action'] == 'media_replace_upload' ) {
		$plugin_url =  str_replace("enable-media-replace.php", "", __FILE__);
    	check_admin_referer( 'media_replace_upload' ); // die if invalid or missing nonce
		require_once($plugin_url . "upload.php");
	}

}

/**
 * Function called by filter 'media_row_actions'
 * Enables linking to EMR straight from the media library
*/
function add_media_action( $actions, $post) {
	$url = admin_url( "upload.php?page=enable-media-replace/enable-media-replace.php&action=media_replace&attachment_id=" . $post->ID);
	$action = "media_replace";
  	$editurl = wp_nonce_url( $url, $action );

	if (FORCE_SSL_ADMIN) {
		$editurl = str_replace("http:", "https:", $editurl);
	}
	$link = "href=\"$editurl\"";

	$newaction['adddata'] = '<a ' . $link . ' aria-label="' . __("Replace media", "enable-media-replace") . '" rel="permalink">' . __("Replace media", "enable-media-replace") . '</a>';
	return array_merge($actions,$newaction);
}

/**
 * Shorttag function to show the media file modification date/time.
 * @param array shorttag attributes
 * @return string content / replacement shorttag
 */
function emr_get_modified_date($atts) {
	$id=0;
	$format= '';

	extract(shortcode_atts(array(
		'id' => '',
		'format' => get_option('date_format') . " " . get_option('time_format'),
	), $atts));

	if ($id == '') return false;

    // Get path to file
	$current_file = get_attached_file($id);

	if ( ! file_exists( $current_file ) ) {
		return false;
	}

	// Get file modification time
	$filetime = filemtime($current_file);

	if ( false !== $filetime ) {
		// do date conversion
		return date( $format, $filetime );
	}
	
	return false;
}

// Add Last replaced by EMR plugin in the media edit screen metabox - Thanks Jonas Lundman (http://wordpress.org/support/topic/add-filter-hook-suggestion-to)
function ua_admin_date_replaced_media_on_edit_media_screen() {
	if( !function_exists( 'enable_media_replace' ) ) return;
	global $post;
	$id = $post->ID;
	$shortcode = "[file_modified id=$id]";

	$file_modified_time = do_shortcode($shortcode);
	if ( ! $file_modified_time ) {
		return;
	}
	?>
	<div class="misc-pub-section curtime">
		<span id="timestamp"><?php _e( 'Revised', 'enable-media-replace' ); ?>: <b><?php echo $file_modified_time; ?></b></span>
	</div>
	<?php
}
add_action( 'attachment_submitbox_misc_actions', 'ua_admin_date_replaced_media_on_edit_media_screen', 91 );
