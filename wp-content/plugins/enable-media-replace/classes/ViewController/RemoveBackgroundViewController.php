<?php

namespace EnableMediaReplace\ViewController;

use EnableMediaReplace\Replacer\Libraries\Unserialize\Unserialize;


if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Controller\ReplaceController as ReplaceController;
use EnableMediaReplace\Api as Api;

class RemoveBackGroundViewController extends \EnableMediaReplace\ViewController
{
	static $instance;

	public function __construct()
	{
		parent::__construct();
	}

	public static function getInstance()
	{
		if (is_null(self::$instance))
			self::$instance = new RemoveBackgroundViewController();

		return self::$instance;
	}

	public function load()
	{
	 if (!current_user_can('upload_files')) {
			 $this->viewError(self::ERROR_UPLOAD_PERMISSION);
			// wp_die(esc_html__('You do not have permission to upload files.', 'enable-media-replace'));
	 }


	 $attachment_id = intval($_REQUEST['attachment_id']);
	 $attachment = get_post($attachment_id);

	 $uiHelper = \emr()->uiHelper();
	 $uiHelper->setPreviewSizes();
	 $uiHelper->setSourceSizes($attachment_id);

	 $replacer = new ReplaceController($attachment_id);
	 $file = $replacer->getSourceFile(true); // for display only

	 $defaults = array(
	 	'bg_type' => 'transparent',
	 	'bg_color' => '#ffffff',
	 	'bg_transparency' => 100,
	 );
	 $settings = get_option('enable_media_replace', $defaults);
	 $settings = array_merge($defaults, $settings); // might miss some

	 $this->view->attachment = $attachment;
	 $this->view->settings = $settings;
	 $this->view->sourceFile = $file;

	 $this->loadView('prepare-remove-background');

	}

	// When the background has been posted - process.
	public function loadPost()
	{
			if ( ! isset( $_POST['emr_nonce'] )
		 || ! wp_verify_nonce( $_POST['emr_nonce'], 'media_remove_background' ))
		 {
			 $this->viewError(self::ERROR_NONCE);
		 }

		 $key = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : null;
		 if (is_null($key) || strlen($key) == 0)
		 {
			 $this->viewError(self::ERROR_KEY);
			 //wp_die(esc_html__('Error while sending form (no key). Please try again.', 'enable-media-replace'));
		 }

		 $post_id = isset($_POST['ID']) ? intval($_POST['ID']) : null; // sanitize, post_id.
		 if (is_null($post_id)) {
			 	 $this->viewError(self::ERROR_FORM);
//		     wp_die(esc_html__('Error in request. Please try again', 'enable-media-replace'));
		 }

		 $this->setView($post_id);
		 $result = $this->replaceBackground($post_id, $key);

		 if (false === $result->success)
		 {
			  $this->view->errorMessage = $result->message;
				$this->viewError();
		 }
		 elseif (! file_exists($result->image))
		 {
			 $this->viewError(self::ERROR_DOWNLOAD_FAILED);
		 }

//		 $result = $replacer->replaceWith($result->image, $source->getFileName() , true);
//$params = array();
		$replaceController = new ReplaceController($post_id);
		$sourceFile = $replaceController->getSourceFile();

		$datetime = current_time('mysql');

		$params = array(
			 'post_id' => $post_id,
			 'replace_type' => ReplaceController::MODE_REPLACE,
			 'timestamp_replace' => ReplaceController::TIME_UPDATEMODIFIED,
			 'new_date' => $datetime,
			 'is_custom_date' => false,
			 'remove_background' => true,
			 'uploadFile' => $result->image,
			 'new_filename' => $sourceFile->getFileName(),
		);


		 $check = $replaceController->setupParams($params);
		 $this->setView($post_id, $params);

		 if (false === $check)
		 {
				$error = $replaceController->returnLastError();
				$this->viewError($error);
		 }

		 $result = $replaceController->run();
		 if (true == $result)
		 {
				$this->viewSuccess();
		 }

	}

	// Low init might only be w/ post_id ( error handling et al ), most advanced / nicer with params.
	protected function setView($post_id, $params = array())
	{
		 $uiHelper = \emr()->uiHelper();
		 $this->view->post_id = $post_id;
		 $this->view->postUrl = $uiHelper->getSuccesRedirect($post_id);
		 $this->view->emrUrl = $uiHelper->getFailedRedirect($post_id);

	}


	protected function replaceBackground($post_id, $key)
	{
		$api = new Api();
		$result = $api->handleDownload($key);

		return $result;
	}



} // class
