<?php
/*
Plugin Name: Enable Media Replace
Plugin URI: http://www.mansjonasson.se/enable-media-replace
Description: Enable replacing media files by uploading a new file in the "Edit Media" section of the WordPress Media Library. 
Version: 1.4.1
Author: Måns Jonasson
Author URI: http://www.mansjonasson.se

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html

Developed for .SE (Stiftelsen för Internetinfrastruktur) - http://www.iis.se
*/

add_action( 'init', 'enable_media_replace_init' );
add_filter('attachment_fields_to_edit', 'enable_media_replace', 10, 2);

// Initialize this plugin. Called by 'init' hook.
function enable_media_replace_init() {
	load_plugin_textdomain( 'enable-media-replace', '/wp-content/plugins/enable-media-replace' );
	}

function enable_media_replace( $form_fields, $post ) {
	if ($_GET["attachment_id"]) {
		$popupurl = plugins_url("popup.php?attachment_id={$_GET["attachment_id"]}", __FILE__);
				
		$link = "href=\"#\" onclick=\"window.open('$popupurl', 'enable_media_replace_popup', 'width=500,height=500');\"";
		$form_fields["enable-media-replace"] = array("label" => __("Replace media", "enable-media-replace"), "input" => "html", "html" => "<p><a $link>" . __("Upload a new file", "enable-media-replace") . "</a></p>", "helps" => __("To replace the current file, click the link and upload a replacement.", "enable-media-replace"));
	}
	return $form_fields;
}



?>
