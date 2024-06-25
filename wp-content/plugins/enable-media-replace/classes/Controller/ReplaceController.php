<?php
namespace EnableMediaReplace\Controller;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Replacer\Replacer as Replacer;
use EnableMediaReplace\emrCache as emrCache;

class ReplaceController
{
	protected $post_id;
	protected $sourceFile;
	protected $sourceFileUntranslated;
	protected $targetFile;

	const MODE_REPLACE = 1;
	const MODE_SEARCHREPLACE = 2;

	const TIME_UPDATEALL = 1; // replace the date
	const TIME_UPDATEMODIFIED = 2; // keep the date, update only modified
	const TIME_CUSTOM = 3; // custom time entry

	const ERROR_TARGET_EXISTS = 20;
	const ERROR_DESTINATION_FAIL = 21;
	const ERROR_COPY_FAILED = 22;
	const ERROR_UPDATE_POST = 23;
	const ERROR_DIRECTORY_SECURITY = 24;
	const ERROR_DIRECTORY_NOTEXIST = 25;

	protected $replaceType;
	/** @var string */
	protected $new_location;
	protected $timeMode;
	protected $newDate;

	protected $new_filename;

	protected $tmpUploadPath;

	protected $lastError;
	protected $lastErrorData; // optional extra data for last error.

		public function __construct($post_id)
		{
				$this->post_id = $post_id;
				$this->setupSource();
		}

		/* getSourceFile
		*
		* @param $untranslated boolean if file is offloaded, this indicates to return remote variant. Used for displaying preview
		*/
		public function getSourceFile($untranslated = false)
		{

				if (true === $untranslated && ! is_null($this->sourceFileUntranslated))
				{
					return $this->sourceFileUntranslated;
				}
				return $this->sourceFile;
		}

		public function setupParams($params)
		{
				$this->replaceType = ($params['replace_type'] === 'replace_and_search') ? self::MODE_SEARCHREPLACE : self::MODE_REPLACE;

				if ($this->replaceType == self::MODE_SEARCHREPLACE && true === $params['new_location'] && ! is_null($params['location_dir']))
				{
					 $this->new_location = $params['location_dir'];
				}

				$this->timeMode = $params['timestamp_replace'];
				$this->newDate = $params['new_date'];

				$this->new_filename = $params['new_filename'];
				$this->tmpUploadPath = $params['uploadFile'];

				$targetFile = $this->setupTarget();
				if (is_null($targetFile))
				{
						return false;
				}
				$this->targetFile = $this->fs()->getFile($targetFile);

				return true;
		}

		public function returnLastError()
		{
			 return $this->lastError;
		}

		public function returnLastErrorData()
		{
			 if (! is_null($this->lastErrorData))
			 	return $this->lastErrorData;
			 else {
			 		return array();
			 }
		}

		public function run()
		{
			do_action('wp_handle_replace', array('post_id' => $this->post_id));

			// Set Source / and Source Metadata
			$Replacer = new Replacer();
			$source_url = $this->getSourceUrl();
			$Replacer->setSource($source_url);
			$Replacer->setSourceMeta(wp_get_attachment_metadata( $this->post_id ));

			$targetFileObj = $this->fs()->getFile($this->targetFile);

			$directoryObj = $targetFileObj->getFileDir();

			$result = $directoryObj->check();

			if ($result === false)
			{
				Log::addError('Directory creation for targetFile failed');
			}

			$permissions = ($this->sourceFile->exists() ) ? $this->sourceFile->getPermissions() : -1;

			$this->removeCurrent(); // tries to remove the current files.

			$fileObj = $this->fs()->getFile($this->tmpUploadPath);
			$copied = $fileObj->copy($targetFileObj);

			if (false === $copied)
			{
				if ($targetFileObj->exists())
				{
					 Log::addDebug('Copy declared failed, but target available');
				}
				else {
					$this->lastError = self::ERROR_COPY_FAILED;
				}
			}

			$deleted = $fileObj->delete();
			if (false === $deleted)
			{
				 Log::addWarn('Temp file could not be removed. Permission issues?');
			}

			$this->targetFile->resetStatus(); // reinit target file because it came into existence.

			if ($permissions > 0)
        chmod( $this->targetFile->getFullPath(), $permissions ); // restore permissions
      else {
        Log::addWarn('Setting permissions failed');
      }

			// Uspdate the file attached. This is required for wp_get_attachment_url to work.
			// Using RawFullPath because FullPath does normalize path, which update_attached_file doesn't so in case of windows / strange Apspaths it fails.
			$updated = update_attached_file($this->post_id, $this->targetFile->getRawFullPath() );
      if (! $updated)
			{
        Log::addError('Update Attached File reports as not updated or same value');
			}

      // Run the filter, so other plugins can hook if needed.
      $filtered = apply_filters( 'wp_handle_upload', array(
          'file' => $this->targetFile->getFullPath(),
          'url'  => $this->getTargetURL(),
          'type' => $this->targetFile->getMime(),
      ), 'sideload');

      // check if file changed during filter. Set changed to attached file meta properly.
      if (isset($filtered['file']) && $filtered['file'] != $this->targetFile->getFullPath() )
      {
        update_attached_file($this->post_id, $filtered['file'] );
        $this->targetFile = $this->fs()->getFile($filtered['file']);  // handle as a new file
        Log::addInfo('WP_Handle_upload filter returned different file', $filtered);
      }

			$target_url = $this->getTargetURL();
			$Replacer->setTarget($target_url);

			// Check and update post mimetype, otherwise badly coded plugins cry.
		  $post_mime = get_post_mime_type($this->post_id);
			$target_mime = $this->targetFile->getMime();

			// update DB post mime type, if somebody decided to mess it up, and the target one is not empty.
			if ($target_mime !== $post_mime && strlen($target_mime) > 0)
			{
				  \wp_update_post(array('post_mime_type' => $this->targetFile->getMime(), 'ID' => $this->post_id));
			}

			do_action('emr/converter/prevent-offload', $this->post_id);
      $target_metadata = wp_generate_attachment_metadata( $this->post_id, $this->targetFile->getFullPath() );
			do_action('emr/converter/prevent-offload-off', $this->post_id);
      wp_update_attachment_metadata( $this->post_id, $target_metadata );


			$Replacer->setTargetMeta($target_metadata);
			//$this->target_metadata = $metadata;

      /** If author is different from replacer, note this */
			$post_author = get_post_field( 'post_author', $this->post_id );
      $author_id = get_post_meta($this->post_id, '_emr_replace_author', true);

      if ( intval($post_author) !== get_current_user_id())
      {
         update_post_meta($this->post_id, '_emr_replace_author', get_current_user_id());
      }
      elseif ($author_id)
      {
        delete_post_meta($this->post_id, '_emr_replace_author');
      }


      if ($this->replaceType == self::MODE_SEARCHREPLACE)
      {
         // Write new image title.
         $title = $this->getNewTitle($target_metadata);
				 $excerpt = $this->getNewExcerpt($target_metadata);
         $update_ar = array('ID' => $this->post_id);
         $update_ar['post_title'] = $title;
         $update_ar['post_name'] = sanitize_title($title);
				 if ($excerpt !== false)
				 {
				 		$update_ar['post_excerpt'] = $excerpt;
				 }
         $update_ar['guid'] = $target_url; //wp_get_attachment_url($this->post_id);

         $post_id = \wp_update_post($update_ar, true);

				 global $wpdb;
         // update post doesn't update GUID on updates.
         $wpdb->update( $wpdb->posts, array( 'guid' =>  $target_url), array('ID' => $this->post_id) );
         //enable-media-replace-upload-done

         // @todo This error in general ever happens?
         if (is_wp_error($post_id))
         {
					  $this->lastError = self::ERROR_UPDATE_POST;
         }

      }

			/// Here run the Replacer Module
			$args = array(
          'thumbnails_only' => ($this->replaceType == self::MODE_SEARCHREPLACE) ? false : true,
      );

			$Replacer->replace($args);

			// Here Updatedata and a ffew others.
			$this->updateDate();

			// Give the caching a kick. Off pending specifics.
			$cache_args = array(
				'flush_mode' => 'post',
				'post_id' => $this->post_id,
			);

			$cache = new emrCache();
			$cache->flushCache($cache_args);

			do_action("enable-media-replace-upload-done", $target_url, $source_url, $this->post_id);

			return true;
		} // run


		protected function setupSource()
		{
				$source_file = false;

				// The main image as registered in attached_file metadata.  This can be regular or -scaled.
				$source_file_main = trim(get_attached_file($this->post_id, apply_filters( 'emr_unfiltered_get_attached_file', true )));

				// If available it -needs- to use the main image when replacing since treating a -scaled images as main will create a resursion in the filename when not replacing that one . Ie image-scaled-scaled.jpg or image-scaled-100x100.jpg .
				if (function_exists('wp_get_original_image_path')) // WP 5.3+
				{
						$source_file = wp_get_original_image_path($this->post_id, apply_filters( 'emr_unfiltered_get_attached_file', true ));
						// For offload et al to change path if wrong. Somehow this happens?
						$source_file = apply_filters('emr/replace/original_image_path', $source_file, $this->post_id);

			 }

			 if (false === $source_file) // If not scaled, use the main one.
			 {
				 	$source_file = $source_file_main;
			 }


				$sourceFileObj = $this->fs()->getFile($source_file);
				$isVirtual = false;
				if ($sourceFileObj->is_virtual())
				{
						$isVirtual = true;

						/***
						*** Either here the table should check scaled - non-scaled ** or ** the original_path should be updated.
						***

						*/

						$this->sourceFileUntranslated = $this->fs()->getFile($source_file);
						$sourcePath = apply_filters('emr/file/virtual/translate', $sourceFileObj->getFullPath(), $sourceFileObj, $this->post_id);

						if (false !== $sourcePath && $sourceFileObj->getFullPath() !== $sourcePath)
						{
							 $sourceFileObj = $this->fs()->getFile($sourcePath);
							 $source_file = $sourcePath;
						}

				}


				/* It happens that the SourceFile returns relative / incomplete when something messes up get_upload_dir with an error something.
					 This case shoudl be detected here and create a non-relative path anyhow..
				*/
				if (
					false === $isVirtual &&
					false === file_exists($source_file) &&
					$source_file && 0 !== strpos( $source_file, '/' )
					&& ! preg_match( '|^.:\\\|', $source_file ) )
				{
					$file = get_post_meta( $this->post_id, '_wp_attached_file', true );
					$uploads = wp_get_upload_dir();
					$source_file = $uploads['basedir'] . "/$source_file";
				}

				Log::addDebug('SetupSource SourceFile Path ' . $source_file);
				$this->sourceFile = $this->fs()->getFile($source_file);
		}

		/** Returns a full target path to place to new file. Including the file name!  **/
		protected function setupTarget()
		{
			$targetPath = null;
			if ($this->replaceType == self::MODE_REPLACE)
			{
				$targetFile = $this->getSourceFile()->getFullPath(); // overwrite source
			}
			elseif ($this->replaceType == self::MODE_SEARCHREPLACE)
			{
					$path = (string) $this->getSourceFile()->getFileDir();
					$targetLocation = $this->getNewTargetLocation();
					if (false === $targetLocation)
					{
						return null;
					}

					if (false === is_null($this->new_location)) // Replace to another path.
					{
						 $otherTarget = $this->fs()->getFile($targetLocation . $this->new_filename);
						 // Halt if new target exists, but not if it's the same ( overwriting itself )

						 if ($otherTarget->exists() && $otherTarget->getFullPath() !== $this->getSourceFile()->getFullPath() )
						 {
								$this->lastError = self::ERROR_TARGET_EXISTS;
								return null;
						 }

						 $path = $targetLocation; // $this->target_location; // if all went well.
					}
					//if ($this->sourceFile->getFileName() == $this->targetName)
					$targetpath = $path . $this->new_filename;

					// If the source and target path AND filename are identical, user has wrong mode, just overwrite the sourceFile.
					if ($targetpath == $this->sourceFile->getFullPath())
					{
							$unique = $this->sourceFile->getFileName();
							$this->replaceType == self::MODE_REPLACE;
					}
					else
					{
							$unique = wp_unique_filename($path, $this->new_filename);
					}
					$new_filename = apply_filters( 'emr_unique_filename', $unique, $path, $this->post_id );
					$targetFile = trailingslashit($path) . $new_filename;
			}
			if (is_dir($targetFile)) // this indicates an error with the source.
			{
					Log::addWarn('TargetFile is directory ' . $targetFile );
					$upload_dir = wp_upload_dir();
					if (isset($upload_dir['path']))
					{
						$targetFile = trailingslashit($upload_dir['path']) . wp_unique_filename($targetFile, $this->new_filename);
					}
					else {

						$this->lastError = self::ERROR_DESTINATION_FAIL;
					 	return null;
					}
			}
			return $targetFile;
		}

		protected function getNewTitle($meta)
		{
			// get basename without extension
			$title = basename($this->targetFile->getFileName(), '.' . $this->targetFile->getExtension());
		//	$meta = $this->target_metadata;

			if (isset($meta['image_meta']))
			{
				if (isset($meta['image_meta']['title']))
				{
						if (strlen($meta['image_meta']['title']) > 0)
						{
							 $title = $meta['image_meta']['title'];
						}
				}
			}

			// Thanks Jonas Lundman   (http://wordpress.org/support/topic/add-filter-hook-suggestion-to)
			$title = apply_filters( 'enable_media_replace_title', $title );

			return $title;
		}

		protected function getNewExcerpt($meta)
		{
		//	 $meta = $this->target_metadata;
			 $excerpt = false;

			 if (isset($meta['image_meta']))
			 {
				 if (isset($meta['image_meta']['caption']))
				 {
						 if (strlen($meta['image_meta']['caption']) > 0)
						 {
								$excerpt = $meta['image_meta']['caption'];
						 }
				 }
			 }

		 return $excerpt;
		}

		public function getSourceUrl()
		{
			if (function_exists('wp_get_original_image_url')) // WP 5.3+
			{
				$source_url = wp_get_original_image_url($this->post_id);
				if ($source_url === false)  // not an image, or borked, try the old way
					$source_url = wp_get_attachment_url($this->post_id);

				$source_url = $source_url;
			}
			else
				$source_url = wp_get_attachment_url($this->post_id);

			return $source_url;
		}

		/** Handle new dates for the replacement */
	  protected function updateDate()
	  {
	 	 global $wpdb;
	 	 $post_date = $this->newDate;
	 	 $post_date_gmt = get_gmt_from_date($post_date);

	 	 $update_ar = array('ID' => $this->post_id);
	 	 if ($this->timeMode == static::TIME_UPDATEALL || $this->timeMode == static::TIME_CUSTOM)
	 	 {
	 		 $update_ar['post_date'] = $post_date;
	 		 $update_ar['post_date_gmt'] = $post_date_gmt;
	 	 }
	 	 else {

 	 	 }
	 	 $update_ar['post_modified'] = $post_date;
	 	 $update_ar['post_modified_gmt'] = $post_date_gmt;

	 	 $updated = $wpdb->update( $wpdb->posts, $update_ar , array('ID' => $this->post_id) );

	 	 wp_cache_delete($this->post_id, 'posts');

	  }

		/** Tries to remove all of the old image, without touching the metadata in database
	  *  This might fail on certain files, but this is not an indication of success ( remove might fail, but overwrite can still work)
	  */
	  protected function removeCurrent()
	  {
	    $meta = \wp_get_attachment_metadata( $this->post_id );
	    $backup_sizes = get_post_meta( $this->post_id, '_wp_attachment_backup_sizes', true );

	    // this must be -scaled if that exists, since wp_delete_attachment_files checks for original_files but doesn't recheck if scaled is included since that the one 'that exists' in WP . $this->source_file replaces original image, not the -scaled one.
	    $file = $this->sourceFile->getFullPath();
	    $result = \wp_delete_attachment_files($this->post_id, $meta, $backup_sizes, $file );

	    // If Attached file is not the same path as file, this indicates a -scaled images is in play.
		  // Also plugins like Polylang tend to block delete image while there is translation / duplicate item somewhere
			// 10/06/22 : Added a hard delete if file still exists.  Be gone, hard way.
	    $attached_file = get_attached_file($this->post_id);
	    if (file_exists($attached_file))
	    {
	       @unlink($attached_file);
	    }

	    do_action( 'emr_after_remove_current', $this->post_id, $meta, $backup_sizes, $this->sourceFile, $this->targetFile );
	  }

		/** Since WP functions also can't be trusted here in certain cases, create the URL by ourselves */
		protected function getTargetURL()
		{
			if (is_null($this->targetFile))
			{
				 Log::addError('TargetFile NULL ', debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10));
				 return false;
			}
			//$uploads['baseurl']
			$url = wp_get_attachment_url($this->post_id);
			$url_basename = basename($url);

			// Seems all worked as normal.
			if (strpos($url, '://') >= 0 && $this->targetFile->getFileName() == $url_basename)
					return $url;

			// Relative path for some reason
			if (strpos($url, '://') === false)
			{
					$uploads = wp_get_upload_dir();
					$url = str_replace($uploads['basedir'], $uploads['baseurl'], $this->targetFile->getFullPath());
			}
			// This can happen when WordPress is not taking from attached file, but wrong /old GUID. Try to replace it to the new one.
			elseif ($this->targetFile->getFileName() != $url_basename)
			{
					$url = str_replace($url_basename, $this->targetFile->getFileName(), $url);
			}

			return $url;

		}

		protected function getNewTargetLocation()
		{
				$uploadDir = wp_upload_dir();
				$new_rel_location = $this->new_location;
				$newPath = trailingslashit($uploadDir['basedir']) . $new_rel_location;

				$realPath = realpath($newPath);
				$basedir = realpath($uploadDir['basedir']); // both need to go to realpath, otherwise some servers will have issues with it.

				// Detect traversal by making sure the canonical path starts with uploads' basedir.
			 	if ( strpos($realPath, $basedir) !== 0)
			 	{
					$this->lastError = self::ERROR_DIRECTORY_SECURITY;
					$this->lastErrorData = array('path' => $realPath, 'basedir' => $basedir);
					return false;
				}

				if (! is_dir($newPath))
				{
					$this->lastError = self::ERROR_DIRECTORY_NOTEXIST;
					return false;
				}
				return trailingslashit($newPath);
		}


		private function fs()
		{
		 return emr()->filesystem();
		}
}
