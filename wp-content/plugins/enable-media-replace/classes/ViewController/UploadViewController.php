<?php
namespace EnableMediaReplace\ViewController;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use EnableMediaReplace as emr;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Controller\UploadController as UploadController;
use EnableMediaReplace\Controller\ReplaceController as ReplaceController;


class UploadViewController extends \EnableMediaReplace\ViewController
{
	 static $instance;


	 public function __construct()
	 {
		 parent::__construct();
	 }

	 public static function getInstance()
	 {
		 	if (is_null(self::$instance))
		 		self::$instance = new UploadViewController();

			return self::$instance;
	 }

	 public function load()
	 {

		 // No form submit?
		 if (count($_POST) == 0)
		 {
			 	$post_id = isset($_REQUEST['attachment_id']) ? intval($_REQUEST['attachment_id']) : null;
				$this->setView($post_id);

			 	if (isset($_GET['emr_success']))
				{
						$this->viewSuccess();
				}

		 }

		 if ( ! isset( $_POST['emr_nonce'] )
    || ! wp_verify_nonce( $_POST['emr_nonce'], 'media_replace_upload' ))
		{
			$this->viewError(self::ERROR_NONCE);
		}

		 if (!current_user_can('upload_files')) {
			 	 $this->viewError(self::ERROR_UPLOAD_PERMISSION);
		    // wp_die(esc_html__('You do not have permission to upload files.', 'enable-media-replace'));
		 }

		 $post_id = isset($_POST['ID']) ? intval($_POST['ID']) : null; // sanitize, post_id.
		 if (is_null($post_id)) {
			 	 $this->viewError(self::ERROR_FORM);
//		     wp_die(esc_html__('Error in request. Please try again', 'enable-media-replace'));
		 }
		 $attachment = get_post($post_id);

		 if (! emr()->checkImagePermission($attachment)) {
			 	 $this->viewError(self::ERROR_IMAGE_PERMISSION);
//		     wp_die(esc_html__('You do not have permission to upload files for this author.', 'enable-media-replace'));
		 }

		 $params = $this->getPost();

		 // UploadController here / replacerController here with save Settings as well? s
		 $this->updateSettings($params);
		 $this->setView($post_id, $params); // set variables needed for view.

		 $replaceController = new ReplaceController($post_id);
		 $check = $replaceController->setupParams($params);

		if (false === $check)
		{
			 $error = $replaceController->returnLastError();
			 $data = $replaceController->returnLastErrorData();
			 $this->viewError($error, $data);
		}

		$result = $replaceController->run();

		if (true == $result)
		{
			 $this->viewSuccess();
		}
	 }


	 protected function getPost()
	 {
		 $ID = intval($_POST["ID"]); // legacy
		 $replace_type = isset($_POST["replace_type"]) ? sanitize_text_field($_POST["replace_type"]) : false;
		 $timestamp_replace = isset($_POST['timestamp_replace']) ? intval($_POST['timestamp_replace']) : ReplaceController::TIME_UPDATEMODIFIED;

		 $remove_background = ( isset( $_POST['remove_after_progress'] ) ) ? true : false;

		 $do_new_location  = isset($_POST['new_location']) ? true : false;
		 $do_new_location = apply_filters('emr/replace/file_is_movable', $do_new_location, $ID);
 		 $new_location_dir = isset($_POST['location_dir']) ? sanitize_text_field($_POST['location_dir']) : null;

		 $is_custom_date = false;

		 switch ($timestamp_replace) {
		     case ReplaceController::TIME_UPDATEALL:
		     case ReplaceController::TIME_UPDATEMODIFIED:
		         $datetime = current_time('mysql');
		         break;
		     case ReplaceController::TIME_CUSTOM:
		         $custom_date = $_POST['custom_date_formatted'];
		         $custom_hour = str_pad($_POST['custom_hour'], 2, 0, STR_PAD_LEFT);
		         $custom_minute = str_pad($_POST['custom_minute'], 2, 0, STR_PAD_LEFT);

		         // create a mysql time representation from what we have.
		         Log::addDebug('Custom Date - ' . $custom_date . ' ' . $custom_hour . ':' . $custom_minute);
		         $custom_date = \DateTime::createFromFormat('Y-m-d G:i', $custom_date . ' ' . $custom_hour . ':' . $custom_minute);
		         if ($custom_date === false) {
								 $this->viewError(self::ERROR_TIME);
		         }
		         $datetime  =  $custom_date->format("Y-m-d H:i:s");
						 $is_custom_date = true;
		         break;
		 }

		 list($uploadFile, $new_filename) = $this->getUpload();

		 return array(
			 	'post_id' => $ID,
				'replace_type' => $replace_type,
				'timestamp_replace' => $timestamp_replace,
				'new_date' => $datetime,
				'new_location' => $do_new_location,
				'location_dir' => $new_location_dir,
				'is_custom_date' => $is_custom_date,
				'remove_background' => $remove_background,
				'uploadFile' => $uploadFile,
				'new_filename' => $new_filename,
		 );

	 }

	 // Low init might only be w/ post_id ( error handling et al ), most advanced / nicer with params.
	 protected function setView($post_id, $params = array())
	 {
		 	$uiHelper = \emr()->uiHelper();
		  $this->view->post_id = $post_id;
			$this->view->postUrl = $uiHelper->getSuccesRedirect($post_id);
			$this->view->emrUrl = $uiHelper->getFailedRedirect($post_id);

			if (isset($params['remove_background']) && true === $params['remove_background'])
			{
				$this->view->postUrl = $uiHelper->getBackgroundRemoveRedirect($post_id);
			}
	 }


	 protected function updateSettings($params)
	 {
		 $settings = get_option('enable_media_replace', array()); // save settings and show last loaded.
		 $settings['replace_type'] = $params['replace_type'];
		 $settings['timestamp_replace'] = $params['timestamp_replace'];
		 $settings['new_location'] = $params['new_location'];
		 $settings['new_location_dir'] = $params['location_dir'];

		 if (true === $params['is_custom_date'])
		 {
			  $settings['custom_date']  = $params['new_date'];
		 }
		 update_option('enable_media_replace', $settings, false);

	 }

	 protected function getUpload()
	 {
		 if (is_uploaded_file($_FILES["userfile"]["tmp_name"])) {
				 Log::addDebug('Uploaded Files', $_FILES['userfile']);

				 // New method for validating that the uploaded file is allowed, using WP:s internal wp_check_filetype_and_ext() function.
				 $filedata = wp_check_filetype_and_ext($_FILES["userfile"]["tmp_name"], $_FILES["userfile"]["name"]);

				 Log::addDebug('Data after check', $filedata);
				 if (isset($_FILES['userfile']['error']) && $_FILES['userfile']['error'] > 0) {
							//$e = new RunTimeException('File Uploaded Failed');
							//Notices::addError($e->getMessage());
						//	wp_safe_redirect($redirect_error);
						  $this->viewError(self::ERROR_UPDATE_FAILED);
						//	exit();
				 }

				 if ($filedata["ext"] == false && ! current_user_can('unfiltered_upload')) {
						  $this->viewError(self::ERROR_SECURITY);
				 }

				 // Here we have the uploaded file
				 $new_filename = $_FILES["userfile"]["name"];
				 $new_filetype = $filedata["type"] ? $filedata["type"] : $_FILES['userfile']['type'];

				 return array($_FILES["userfile"]["tmp_name"], $new_filename);
				 // Execute hook actions - thanks rubious for the suggestion!
		 }
		 $this->viewError(self::ERROR_UPLOAD_FAILED);
	 }




} // class
