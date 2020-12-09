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

// Starts processing.
$uihelper = new UIHelper();

// Get old guid and filetype from DB
$post_id = intval($_POST['ID']); // sanitize, post_id.
$replacer = new Replacer($post_id);

// Massage a bunch of vars
$ID = intval($_POST["ID"]); // legacy
$replace_type = isset($_POST["replace_type"]) ? sanitize_text_field($_POST["replace_type"]) : false;
$timestamp_replace = intval($_POST['timestamp_replace']);

$redirect_error = $uihelper->getFailedRedirect($post_id);
$redirect_success = $uihelper->getSuccesRedirect($post_id);

$do_new_location  = isset($_POST['new_location']) ? sanitize_text_field($_POST['new_location']) : false;
$new_location_dir = isset($_POST['location_dir']) ? sanitize_text_field($_POST['location_dir']) : null;

$settings = array(); // save settings and show last loaded.
$settings['replace_type'] = $replace_type;
$settings['timestamp_replace'] = $timestamp_replace;
$settings['new_location'] = $do_new_location;
$settings['new_location_dir'] = $new_location_dir; 

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
		$settings['custom_date'] = $datetime;
	break;
}

update_option('enable_media_replace', $settings, false);

// We have two types: replace / replace_and_search
if ($replace_type == 'replace')
{
	$replacer->setMode(\EnableMediaReplace\Replacer::MODE_REPLACE);
	$mode = \EnableMediaReplace\Replacer::MODE_REPLACE;
}
elseif ( 'replace_and_search' == $replace_type && apply_filters( 'emr_enable_replace_and_search', true ) )
{
	$replacer->setMode(\EnableMediaReplace\Replacer::MODE_SEARCHREPLACE);
	$mode = \EnableMediaReplace\Replacer::MODE_SEARCHREPLACE;

	if ($do_new_location && ! is_null($new_location_dir))
	{
		 $result = $replacer->setNewTargetLocation($new_location_dir);
		 if (! $result)
		 {
		 	 wp_safe_redirect($redirect_error);
			 exit();
		 }
	}
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


	if ($filedata["ext"] == false) {

		Notices::addError(esc_html__("File type does not meet security guidelines. Try another.", 'enable-media-replace') );
		wp_safe_redirect($redirect_error);
		exit();
	}

	// Here we have the uploaded file
	$new_filename = $_FILES["userfile"]["name"];
	//$new_filesize = $_FILES["userfile"]["size"]; // Seems not to be in use.
	$new_filetype = $filedata["type"];

	// Gather all functions that both options do.
	do_action('wp_handle_replace', array('post_id' => $post_id));


/*	if ($mode = \EnableMediaReplace\Replacer::MODE_SEARCHREPLACE && $do_new_location && ! is_null($new_location_dir))
	{
		exit($new_filename);
		 $newdirfile = $replacer->newTargetLocation($new_location_dir);
	}
*/
	try
	{
		$result = $replacer->replaceWith($_FILES["userfile"]["tmp_name"], $new_filename);
	}
	catch(\RunTimeException $e)
	{
		Log::addError($e->getMessage());
	//  exit($e->getMessage());
	}

	if (is_null($result))
	{
		 wp_safe_redirect($redirect_error);
		 exit();
  }
//	$returnurl = admin_url("/post.php?post={$_POST["ID"]}&action=edit&message=1");

	// Execute hook actions - thanks rubious for the suggestion!

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
