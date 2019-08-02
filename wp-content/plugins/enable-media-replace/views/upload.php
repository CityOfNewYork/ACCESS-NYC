<?php
namespace EnableMediaReplace;

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly.

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;

if (!current_user_can('upload_files'))
	wp_die( esc_html__('You do not have permission to upload files.', 'enable-media-replace') );


/*require_once('classes/replacer.php');
require_once('classes/file.php'); */

use \EnableMediaReplace\Replacer as Replacer;

// Define DB table names
global $wpdb;
$table_name = $wpdb->prefix . "posts";
$postmeta_table_name = $wpdb->prefix . "postmeta";

/**
 * Delete a media file and its thumbnails.
 *
 * @param string     $current_file
 * @param array|null $metadta
 */
 /* Phased-out, marked for delete.
function emr_delete_current_files( $current_file, $metadta = null ) {
	// Delete old file

	// Find path of current file
	$current_path = substr($current_file, 0, (strrpos($current_file, "/")));

	// Check if old file exists first
	if (file_exists($current_file)) {
		// Now check for correct file permissions for old file
		clearstatcache();
		if (is_writable($current_file)) {
			// Everything OK; delete the file
			unlink($current_file);
		}
		else {
			// File exists, but has wrong permissions. Let the user know.
			printf( esc_html__('The file %1$s can not be deleted by the web server, most likely because the permissions on the file are wrong.', "enable-media-replace"), $current_file);
			exit;
		}
	}

	// Delete old resized versions if this was an image
	$suffix = substr($current_file, (strlen($current_file)-4));
	$prefix = substr($current_file, 0, (strlen($current_file)-4));

	if (strtolower($suffix) === ".pdf") {
		$prefix .= "-pdf";
		$suffix = ".jpg";
	}

	$imgAr = array(".png", ".gif", ".jpg");
	if (in_array($suffix, $imgAr)) {
		// It's a png/gif/jpg based on file name
		// Get thumbnail filenames from metadata
		if ( empty( $metadata ) ) {
			$metadata = wp_get_attachment_metadata( $_POST["ID"] );
		}

		if (is_array($metadata)) { // Added fix for error messages when there is no metadata (but WHY would there not be? I don't know…)
			foreach($metadata["sizes"] AS $thissize) {
				// Get all filenames and do an unlink() on each one;
				$thisfile = $thissize["file"];
				// Create array with all old sizes for replacing in posts later
				$oldfilesAr[] = $thisfile;
				// Look for files and delete them
				if (strlen($thisfile)) {
					$thisfile = $current_path . "/" . $thissize["file"];
					if (file_exists($thisfile)) {
						unlink($thisfile);
					}
				}
			}
		}
		// Old (brutal) method, left here for now
		//$mask = $prefix . "-*x*" . $suffix;
		//array_map( "unlink", glob( $mask ) );
	}
} */

/**
 * Maybe remove query string from URL.
 *
 * @param string $url
 *
 * @return string
 */
function emr_maybe_remove_query_string( $url ) {
	$parts = explode( '?', $url );

	return reset( $parts );
}

/**
 * Remove scheme from URL.
 *
 * @param string $url
 *
 * @return string
 */
function emr_remove_scheme( $url ) {
	return preg_replace( '/^(?:http|https):/', '', $url );
}

/**
 * Remove size from filename (image[-100x100].jpeg).
 *
 * @param string $url
 * @param bool   $remove_extension
 *
 * @return string
 */
function emr_remove_size_from_filename( $url, $remove_extension = false ) {
	$url = preg_replace( '/^(\S+)-[0-9]{1,4}x[0-9]{1,4}(\.[a-zA-Z0-9\.]{2,})?/', '$1$2', $url );

	if ( $remove_extension ) {
		$ext = pathinfo( $url, PATHINFO_EXTENSION );
		$url = str_replace( ".$ext", '', $url );
	}

	return $url;
}

/**
 * Strip an image URL down to bare minimum for matching.
 *
 * @param string $url
 *
 * @return string
 */
function emr_get_match_url($url) {
	$url = emr_remove_scheme($url);
	$url = emr_maybe_remove_query_string($url);
	$url = emr_remove_size_from_filename($url, true);
	$url = emr_remove_domain_from_filename($url);
	return $url;
}


function emr_remove_domain_from_filename($url) {
	// Holding place for possible future function
	$url = str_replace(emr_remove_scheme(get_bloginfo('url')), '', $url);
	return $url;
	}

/**
 * Build an array of search or replace URLs for given attachment GUID and its metadata.
 *
 * @param string $guid
 * @param array  $metadata
 *
 * @return array
 */
function emr_get_file_urls( $guid, $metadata ) {
	$urls = array();

	$guid = emr_remove_scheme( $guid );
	$guid= emr_remove_domain_from_filename($guid);

	$urls['guid'] = $guid;

	if ( empty( $metadata ) ) {
		return $urls;
	}

	$base_url = dirname( $guid );

	if ( ! empty( $metadata['file'] ) ) {
		$urls['file'] = trailingslashit( $base_url ) . wp_basename( $metadata['file'] );
	}

	if ( ! empty( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $key => $value ) {
			$urls[ $key ] = trailingslashit( $base_url ) . wp_basename( $value['file'] );
		}
	}

	return $urls;
}

/**
 * Ensure new search URLs cover known sizes for old attachment.
 * Falls back to full URL if size not covered (srcset or width/height attributes should compensate).
 *
 * @param array $old
 * @param array $new
 *
 * @return array
 */
function emr_normalize_file_urls( $old, $new ) {
	$result = array();

	if ( empty( $new['guid'] ) ) {
		return $result;
	}

	$guid = $new['guid'];

	foreach ( $old as $key => $value ) {
		$result[ $key ] = empty( $new[ $key ] ) ? $guid : $new[ $key ];
	}

	return $result;
}

// Starts processing.
$uihelper = new UIHelper();

// Get old guid and filetype from DB
$post_id = intval($_POST['ID']); // sanitize, post_id.
$replacer = new replacer($post_id);

// Massage a bunch of vars
$ID = intval($_POST["ID"]); // legacy
$replace_type = isset($_POST["replace_type"]) ? sanitize_text_field($_POST["replace_type"]) : false;
$timestamp_replace = intval($_POST['timestamp_replace']);

$current_file = get_attached_file($post_id, apply_filters( 'emr_unfiltered_get_attached_file', true ));
$current_path = substr($current_file, 0, (strrpos($current_file, "/")));
$current_file = preg_replace("|(?<!:)/{2,}|", "/", $current_file); // @todo what does this mean?
$current_filename = wp_basename($current_file);
$current_metadata = wp_get_attachment_metadata( $post_id );


$redirect_error = $uihelper->getFailedRedirect($post_id);
$redirect_success = $uihelper->getSuccesRedirect($post_id);

switch($timestamp_replace)
{
	case \EnableMediaReplace\Replacer::TIME_UPDATEALL:
	case \EnableMediaReplace\Replacer::TIME_UPDATEMODIFIED:
		$datetime = current_time('mysql');
	break;
	case \EnableMediaReplace\Replacer::TIME_CUSTOM:
		$custom_date = $_POST['custom_date_formatted'];
		$custom_hour = str_pad($_POST['custom_hour'],2,0, STR_PAD_LEFT);
		$custom_minute = str_pad($_POST['custom_minute'], 2, 0, STR_PAD_LEFT);

		// create a mysql time representation from what we have.
		Log::addDebug($_POST);
		Log::addDebug('Custom Date - ' . $custom_date . ' ' . $custom_hour . ':' . $custom_minute );
		$custom_date = \DateTime::createFromFormat('Y-m-d G:i', $custom_date . ' ' . $custom_hour . ':' . $custom_minute );
		if ($custom_date === false)
		{

			wp_safe_redirect($redirect_error);
			$errors = \DateTime::getLastErrors();
			$error = '';
			if (isset($errors['errors']))
			{
				$error = implode(',', $errors['errors']);
			}
			Notices::addError(sprintf(__('Invalid Custom Date. Please custom date values (%s)', 'enable-media-replace'), $error));

			exit();
		}
 		$datetime  =  $custom_date->format("Y-m-d H:i:s");
	break;
}

// We have two types: replace / replace_and_search
if ($replace_type == 'replace')
{
	$replacer->setMode(\EnableMediaReplace\Replacer::MODE_REPLACE);
}
elseif ( 'replace_and_search' == $replace_type && apply_filters( 'emr_enable_replace_and_search', true ) )
{
	$replacer->setMode(\EnableMediaReplace\Replacer::MODE_SEARCHREPLACE);
}

$replacer->setTimeMode($timestamp_replace, $datetime);

/** Check if file is uploaded properly **/
if (is_uploaded_file($_FILES["userfile"]["tmp_name"])) {

	Log::addDebug($_FILES['userfile']);

	// New method for validating that the uploaded file is allowed, using WP:s internal wp_check_filetype_and_ext() function.
	$filedata = wp_check_filetype_and_ext($_FILES["userfile"]["tmp_name"], $_FILES["userfile"]["name"]);

	Log::addDebug('Data after check', $filedata);
	if (isset($_FILES['userfile']['error']) && $_FILES['userfile']['error'] > 0)
	{
		 $e = new RunTimeException('File Uploaded Failed');
		 Notices::addError($e->getMessage());
		 wp_safe_redirect($redirect_error);
		 exit();
	}

	if ($filedata["ext"] == "") {

		Notices::addError(esc_html__("File type does not meet security guidelines. Try another.", 'enable-media-replace') );
		wp_safe_redirect($redirect_error);
		exit();
	}

	// Here we have the uploaded file

	//$thumbUpdater = new ThumbnailUpdater($ID);
	//$thumbUpdater->setOldMetadata($current_metadata);

	$new_filename = $_FILES["userfile"]["name"];
	//$new_filesize = $_FILES["userfile"]["size"]; // Seems not to be in use.
	$new_filetype = $filedata["type"];

	// save original file permissions
	//$original_file_perms = fileperms($current_file) & 0777;

	// Gather all functions that both options do.
	do_action('wp_handle_replace', array('post_id' => $post_id));

	try
	{
		$replacer->replaceWith($_FILES["userfile"]["tmp_name"], $new_filename);
	}
	catch(\RunTimeException $e)
	{
		Log::addError($e->getMessage());
	  exit($e->getMessage());
	}

	$returnurl = admin_url("/post.php?post={$_POST["ID"]}&action=edit&message=1");

	// Execute hook actions - thanks rubious for the suggestion!
	//if (isset($new_guid)) { do_action("enable-media-replace-upload-done", $new_guid, $current_guid); }

} else {
	//TODO Better error handling when no file is selected.
	//For now just go back to media management
	//$returnurl = admin_url("upload.php");
	Log::addInfo('Failed. Redirecting - '.  $redirect_error);
	Notices::addError(__('File Upload seems to have failed. No files were returned by system','enable-media-replace'));
	wp_safe_redirect($redirect_error);
	exit();
}

Notices::addSuccess(__('File successfully replaced'));

// Allow developers to override $returnurl
//$returnurl = apply_filters('emr_returnurl', $returnurl);
wp_redirect($redirect_success);
exit();
?>
