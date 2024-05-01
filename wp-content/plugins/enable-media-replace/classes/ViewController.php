<?php
namespace EnableMediaReplace;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use EnableMediaReplace as emr;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;


abstract class ViewController
{

	  abstract function load();


	 const ERROR_UPLOAD_PERMISSION = 1;
	 const ERROR_IMAGE_PERMISSION = 2;
	 const ERROR_FORM = 3;
	 const ERROR_TIME = 4;
	 const ERROR_UPDATE_FAILED = 5;
	 const ERROR_SECURITY = 6;
	 const ERROR_UPLOAD_FAILED = 7;
	 const ERROR_NONCE = 8;
	 const ERROR_KEY = 9; // Missing key when replacing backgrounds.

	 // These synced with ReplaceController
	 const ERROR_TARGET_EXISTS = 20;
	 const ERROR_DESTINATION_FAIL = 21;
	 const ERROR_COPY_FAILED = 22;
	 const ERROR_UPDATE_POST = 23;
	 const ERROR_DIRECTORY_SECURITY = 24;
	 const ERROR_DIRECTORY_NOTEXIST = 25;

	 // Remove Background
	 const ERROR_DOWNLOAD_FAILED = 31;

		protected static $viewsLoaded = array();

		protected $view; // object to use in the view.
	  protected $url; // if controller is home to a page, sets the URL here. For redirects and what not.


		public function __construct()
		{
			 $this->view = new \stdClass;
		}

		protected function loadView($template = null, $unique = true)
		{
				if (is_null($template) )
				{
					return false;
				}
				elseif (strlen(trim($template)) == 0)
				{
					return false;
				}

				$view = $this->view;
				$controller = $this;
				$template_path = emr()->plugin_path('views/' . $template  . '.php');

				if (file_exists($template_path) === false)
				{
					Log::addError("View $template could not be found in " . $template_path,
					array('class' => get_class($this)));
				}
				elseif ($unique === false || ! in_array($template, self::$viewsLoaded))
				{
					include($template_path);
					self::$viewsLoaded[] = $template;
				}
		}

		protected function viewError($errorCode, $errorData = array())
		{
			 $message = $description = false;
			 switch($errorCode)
			 {
					case self::ERROR_UPLOAD_PERMISSION:
					 $message = __('You don\'t have permission to upload images. Please refer to your administrator', 'enable-media-replace');
					break;
					case self::ERROR_IMAGE_PERMISSION:
					 $message = __('You don\'t have permission to edit this image', 'enable-media-replace');
					break;
					case self::ERROR_FORM:
					 $message = __('The form submitted is missing various fields', 'enable-media-replace');
					break;
					case self::ERROR_TIME:
					 $message = __('The custom time format submitted is invalid', 'enable-media-replace');
					break;
					case self::ERROR_UPDATE_FAILED:
					 $message = __('Updating the WordPress attachment failed', 'enable-media-replace');
					break;
					case self::ERROR_SECURITY:
					 $message = __('The file upload has been rejected for security reason. WordPress might not allow uploading this extension or filetype', 'enable-media-replace');
					break;
					case self::ERROR_UPLOAD_FAILED:
					 $message = __('The upload from your browser seem to have failed', 'enable-media-replace');
					break;
					case self::ERROR_TARGET_EXISTS:
					 $message = __('The target file already exists in this directory. Please try another name / directory', 'enable-media-replace');
					 $description = __('This error is shown because you try to move the image to another folder, which already has this file', 'enable-media-replace');
					break;
					case self::ERROR_DESTINATION_FAIL:
					 $message = __('Something went wrong while writing the file or directory', 'enable-media-replace');
					break;
					case self::ERROR_COPY_FAILED:
					 $message = __('Copying replacement file to destination failed', 'enable-media-replace');
					break;
					case self::ERROR_UPDATE_POST:
						$message = __('Error updating WordPress post in the database', 'enable-media-replace');
					break;
					case self::ERROR_DIRECTORY_SECURITY:
						$message = __('Specificed directory is outside the upload directory. This is not allowed for security reasons', 'enable-media-replace');
						$path = isset($errorData['path']) ? $errorData['path'] : false;
						$basedir = isset($errorData['basedir']) ? $errorData['basedir'] : false;

						if ($path !== false && $basedir !== false)
						{
							 $description  = sprintf(__('Path: %s is not within basedir reported as: %s', 'shortpixel-image-optimiser'), $path, $basedir);
						}
					break;
					case self::ERROR_DIRECTORY_NOTEXIST:
						$message = __('Specificed new directory does not exist. Path must be a relative path from the upload directory and exist', 'enable-media-replace');
					break;

					case self::ERROR_NONCE:
					 $message = __('Fail to validate form nonce. Please try again', 'enable-media-replace');
					 $description = __('This can happen when the window is open for a long time and/or there has been a timeout.  You can go back to previous screen and try again. If this happens each time when replacing, contact us', 'enable-media-replace');
					break;

					// Remove Background
					case self::ERROR_DOWNLOAD_FAILED:
						$message = __('Replacement Image could not be downloaded or does not exist', 'enable-media-replace');
					break;

					default:
					 $message = __('An unknown error has occured', 'enable-media-replace');
					break;
			 }

			 if( false !== $message)
			 	$this->view->errorMessage = $message;


			 if (false !== $description)
			 {
				  $this->view->errorDescription = $description;
			 }


			 $this->loadView('error');
			 exit();
		}


		protected function viewSuccess()
		{
			 wp_enqueue_script('emr_success');
			 $this->loadView('success');
			 exit();
		}

}
