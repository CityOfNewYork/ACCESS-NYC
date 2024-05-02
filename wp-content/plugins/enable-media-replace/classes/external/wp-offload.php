<?php
namespace EnableMediaReplace\Externals;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;


class WPOffload
{
		private static $instance;

		private $as3cf;
		private $sources; // cache for url > source_id lookup, to prevent duplicate queries.

		private static $offloadPrevented = array();

		private $post_id; // source_id.  The plugin has this, so why so tricky checks.


		public function __construct()
		{
			add_action('as3cf_init', array($this, 'init'));

			add_action('emr/converter/prevent-offload', array($this, 'preventOffload'), 10);
			add_action('emr/converter/prevent-offload-off', array($this, 'preventOffloadOff'), 10);
			add_filter('as3cf_pre_update_attachment_metadata', array($this, 'preventUpdateMetaData'), 10,4);


		}

		public static function getInstance()
		{
				 if (is_null(self::$instance))
				 {
					  self::$instance = new WPOffload();
				 }

				 return self::$instance;
		}

		public function init($as3cf)
		{
				if (! class_exists('\DeliciousBrains\WP_Offload_Media\Items\Media_Library_Item'))
				{
					Notices::addWarning(__('Your S3-Offload plugin version doesn\'t seem to be compatible. Please upgrade the S3-Offload plugin', 'enable-media-replace'), true);
					return false;
				}

				$this->as3cf = $as3cf;

				if (method_exists($as3cf, 'get_item_handler'))
				{
				}
				else {
					Notices::addWarning(__('Your S3-Offload plugin version doesn\'t seem to be compatible. Please upgrade the S3-Offload plugin', 'enable-media-replace'), true);
					return false;
				}

        // @todo This all is begging for the creating of an enviroment model / controller.
        if( !function_exists('is_plugin_active') ) {

    			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    		}
        $spio_active = \is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php');

        // Let spio handle this.
        if (false === $spio_active)
        {
				      add_filter('shortpixel/image/urltopath', array($this, 'checkIfOffloaded'), 10,2);
        }

				add_action('emr_after_remove_current', array($this, 'removeRemote'), 10, 5);
				add_filter('emr/file/virtual/translate', array($this, 'getLocalPath'), 10, 3);
				add_filter('emr/replace/file_is_movable', array($this, 'isFileMovable'), 10, 2);

				add_filter('emr/replace/original_image_path', array($this, 'checkScaledUrl'), 10,2);

				add_action('enable-media-replace-upload-done', array($this, 'updateOriginalPath'), 10, 3);
		}

		/*
		* @param $post_id int  The post ID
		* @param $meta  array Old Metadata before remove
		* @param $backup_sizes array  WP Backup sizes
		* @param $sourceFile  Object Source File
		* @param $targetFile Object Target File
		*/
		public function removeRemote($post_id, $meta, $backup_sizes, $sourceFile, $targetFile )
		{
// Always remove because also thumbnails can be different.
					$a3cfItem = $this->getItemById($post_id); // MediaItem is AS3CF Object
					if ($a3cfItem === false)
					{
						Log::addDebug('S3-Offload MediaItem not remote - ' . $post_id);
						return false;
					}

						$remove = \DeliciousBrains\WP_Offload_Media\Items\Remove_Provider_Handler::get_item_handler_key_name();
						$itemHandler = $this->as3cf->get_item_handler($remove);

						$result = $itemHandler->handle($a3cfItem, array( 'verify_exists_on_local' => false)); //handle it then.

		}

		// @param s3 based URL that which is needed for finding local path
		// @return String Filepath.  Translated file path
		public function getLocalPath($url, $sourceFileObj, $source_id)
		{
			 $item = $this->getItemById($source_id);

			 if ($item === false)
			 {
			 	$source_id = $this->getSourceIDByURL($url);
				if (false !== $source_id)
					$item = $this->getItemById($source_id);
			 }

			 if ($source_id == false)
			 {
				Log::addError('Get Local Path: No source id for URL (Offload) ' . $url);
				return false;
			}

			 $original_path = $item->original_source_path(); // $values['original_source_path'];

			 if (wp_basename($url) !== wp_basename($original_path)) // thumbnails translate to main file.
			 {
					$original_path = str_replace(wp_basename($original_path), wp_basename($url), $original_path);
			 }

			 $fs = emr()->filesystem();
			 $base = $fs->getWPUploadBase();

			 $file  = $base . $original_path;
			 return $file;
		}



		public function isFileMovable($bool, $attach_id)
		{
				$item = $this->getItemById($attach_id);
				if ($item === false)
				{
					 return $bool;
				}

				// Can't move offloaded items.
				if (is_object($item))
				{
					 return false;
				}

		}

		public function checkIfOffloaded($bool, $url)
		{

			$source_id = $this->sourceCache($url);

			if (false === $source_id)
			{
				$extension = substr($url, strrpos($url, '.') + 1);
				// If these filetypes are not in the cache, they cannot be found via geSourceyIDByUrl method ( not in path DB ), so it's pointless to try. If they are offloaded, at some point the extra-info might load.
				if ($extension == 'webp' || $extension == 'avif')
				{
					return false;
				}

				$source_id = $this->getSourceIDByURL($url);
			}

			if ($source_id !== false)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		// This is used in the converted. Might be deployed elsewhere for better control.
		public function preventOffload($attach_id)
		{
			 self::$offloadPrevented[$attach_id] = true;
		}

		public function preventOffloadOff($attach_id)
		{
				unset(self::$offloadPrevented[$attach_id]);
		}

		public function updateOriginalPath($source_url, $target_url, $post_id)
		{
				$item = $this->getItemById($post_id);

				// If no item comes back, probably it's not offloaded
				if (false === $item)
				{
					 return;
				}


				$original_path = $item->original_path(); // Original path (non-scaled-)
				$original_source_path = $item->original_source_path();
				$path = $item->path();
				$source_path = $item->source_path();

				$wp_original = wp_get_original_image_path($post_id, apply_filters( 'emr_unfiltered_get_attached_file', true ));
				$wp_original = apply_filters('emr/replace/original_image_path', $wp_original, $post_id);
				$wp_source = trim(get_attached_file($post_id, apply_filters( 'emr_unfiltered_get_attached_file', true )));

				$updated = false;

				// If image is replaced with another name, the original soruce path will not match.  This could also happen when an image is with -scaled as main is replaced by an image that doesn't have it.  In all cases update the table to reflect proper changes.
				if (wp_basename($wp_original) !== wp_basename($original_path))
				{

					 $newpath = str_replace( wp_basename( $original_path ), wp_basename($wp_original), $original_path );

					 $item->set_original_path($newpath);

					 $newpath = str_replace( wp_basename( $original_source_path ), wp_basename($wp_original), $original_source_path );
					 $updated = true;

					 $item->set_original_source_path($newpath);

					 $item->save();
				}
		}

		// When Offload is not offloaded but is created during the process of generate metadata in WP, wp_create_image_subsizes fires an update metadata after just moving the upload, before making any thumbnails.  If this is the case and the file has an -scaled / original image setup, the original_source_path becomes the same as the source_path which creates issue later on when dealing with optimizing it, if the file is deleted on local server.  Prevent this, and lean on later update metadata.
		public function preventUpdateMetaData($bool, $data, $post_id, $old_provider_object)
		{
			if (isset(self::$offloadPrevented[$post_id]))
			{
					return true ; // return true to cancel.
			}

			return $bool;

		}



		// WP Offload -for some reason - returns the same result of get_attached_file and wp_get_original_image_path , which are different files (one scaled) which then causes a wrong copy action after optimizing the image ( wrong destination download of the remote file ).   This happens if offload with delete is on.  Attempt to fix the URL to reflect the differences between -scaled and not.
		public function checkScaledUrl($filepath, $id)
		{
				// Original filepath can never have a scaled in there.
				// @todo This should probably check -scaled.<extension> as string end preventing issues.
				if (strpos($filepath, '-scaled') !== false)
				{
					$filepath = str_replace('-scaled', '', $filepath);
				}
			 return $filepath;
		}

		/** @return Returns S3Ofload MediaItem, or false when this does not exist */
		protected function getItemById($id, $create = false)
		{
				$class = $this->getMediaClass();
				$mediaItem = $class::get_by_source_id($id);

				if (true === $create && $mediaItem === false)
				{
					 $mediaItem = $class::create_from_source_id($id);
				}

				return $mediaItem;
		}

		protected function getSourceIDByURL($url)
    {
			$source_id = $this->sourceCache($url); // check cache first.

			if (false === $source_id) // check on the raw url.
			{
      	$class = $this->getMediaClass();

				$parsedUrl = parse_url($url);

				if (! isset($parsedUrl['scheme']) || ! in_array($parsedUrl['scheme'], array('http','https')))
				{
					 $url = 'http://' . $url; //str_replace($parsedUrl['scheme'], 'https', $url);
				}

      	$source = $class::get_item_source_by_remote_url($url);

				$source_id = isset($source['id']) ? intval($source['id']) : false;
			}

			if (false === $source_id) // check now via the thumbnail hocus.
			{
				$pattern = '/(.*)-\d+[xX]\d+(\.\w+)/m';
				$url = preg_replace($pattern, '$1$2', $url);

				$source_id = $this->sourceCache($url); // check cache first.

				if (false === $source_id)
				{
					$source = $class::get_item_source_by_remote_url($url);
					$source_id = isset($source['id']) ? intval($source['id']) : false;
				}

      }

			// Check issue with double extensions. If say double webp/avif is on, the double extension causes the URL not to be found (ie .jpg)
			if (false === $source_id)
			{
				 if (substr_count($parsedUrl['path'], '.') > 1)
				 {
					  // Get extension
						$ext = substr(strrchr($url, '.'), 1);

						// Remove all extensions from the URL
					  $checkurl = substr($url, 0, strpos($url,'.')) ;

						// Add back the last one.
						$checkurl .= '.' . $ext;

						// Retry
						$source_id = $this->sourceCache($checkurl); // check cache first.

						if (false === $source_id)
						{
							$source = $class::get_item_source_by_remote_url($url);
							$source_id = isset($source['id']) ? intval($source['id']) : false;
						}


				 }
			}

			if ($source_id !== false)
			{

				$this->sourceCache($url, $source_id);  // cache it.

				// get item
				$item = $this->getItemById($source_id);
				if (is_object($item) && method_exists($item, 'extra_info'))
				{
					$baseUrl = str_replace(basename($url),'', $url);
					$extra_info = $item->extra_info();

					if (isset($extra_info['objects']))
					{
						foreach($extra_info['objects'] as $extraItem)
						{
							 if (is_array($extraItem) && isset($extraItem['source_file']))
							 {
								 // Add source stuff into cache.
								  $this->sourceCache($baseUrl . $extraItem['source_file'], $source_id);
							 }
						}
					}
				}

				return $source_id;
			}

      return false;
    }

		private function sourceCache($url, $source_id = null)
		{
			if ($source_id === null && isset($this->sources[$url]))
			{
				$source_id = $this->sources[$url];
				return $source_id;
			}
			elseif ($source_id !== null)
			{
				 if (! isset($this->sources[$url]))
				 {
					  $this->sources[$url]  = $source_id;
				 }
				 return $source_id;
			}

			return false;
		}


		private function getMediaClass()
		{
			$class = $this->as3cf->get_source_type_class('media-library');
			return $class;
		}

}
