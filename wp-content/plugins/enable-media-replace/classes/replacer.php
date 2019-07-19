<?php
namespace EnableMediaReplace;
use \EnableMediaReplace\emrFile as File;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;

class Replacer
{
  protected $post_id;

  // everything source is the attachment being replaced
  protected $sourceFile; // File Object
  protected $source_post; // wpPost;
  protected $source_is_image;
  protected $source_metadata;
  protected $source_url;

  // everything target is what will be.
  protected $targetFile;
  protected $targetName;
  protected $target_metadata;
  protected $target_url;

  protected $replaceMode = null;
  protected $timeMode = null;
  protected $datetime = null;

  protected $ThumbnailUpdater; // class

  const MODE_REPLACE = 1;
  const MODE_SEARCHREPLACE = 2;

  const TIME_UPDATEALL = 1;
  const TIME_UPDATEMODIFIED = 2;
  const TIME_CUSTOM = 3;

  public function __construct($post_id)
  {
      $this->post_id = $post_id;

      $source_file = trim(get_attached_file($post_id, apply_filters( 'emr_unfiltered_get_attached_file', true )));

      $this->sourceFile = new File($source_file);

      $this->source_post = get_post($post_id);
      $this->source_is_image = wp_attachment_is('image', $this->source_post);
      $this->source_metadata = wp_get_attachment_metadata( $post_id );
      $this->source_url = wp_get_attachment_url($post_id);

      $this->ThumbnailUpdater = new \ThumbnailUpdater($post_id);
      $this->ThumbnailUpdater->setOldMetadata($this->source_metadata);
  }

  public function setMode($mode)
  {
    $this->replaceMode = $mode;
  }

  public function setTimeMode($mode, $datetime = 0)
  {
    if ($datetime == 0)
      $datetime = current_time('mysql');

    $this->datetime = $datetime;
    $this->timeMode = $mode;
  }

  /** Replace the sourceFile with a target
  * @param $file String Full Path to the Replacement File. This will usually be an uploaded file in /tmp/
  * @param $fileName String The fileName of the uploaded file. This will be used if sourcefile is not to be overwritten.
  * @throws RunTimeException  Can throw exception if something went wrong with the files.
  */
  public function replaceWith($file, $fileName)
  {
      global $wpdb;
      //$this->targetFile = new File($file);
      $this->targetName = $fileName;
      //$this->targetFile = new File($file); // this will point to /tmp!

      $this->removeCurrent(); // tries to remove the current files.
      $targetFile = $this->getTargetFile();

      if (is_null($targetFile))
      {
        $ex = __('Target File could not be set. The source file might not be there. In case of search and replace, a filter might prevent this', "enable-media-replace");
        throw new \RuntimeException($ex);
      }

      $targetFileObj = new File($targetFile);
      $result = $targetFileObj->checkAndCreateFolder();
      if ($result === false)
        Log::addError('Directory creation for targetFile failed');

      /* @todo See if wp_handle_sideload / wp_handle_upload can be more securely used for this */
      $result_moved = move_uploaded_file($file,$targetFile);

      if (false === $result_moved)
      {
        $ex = sprintf( esc_html__('The uploaded file could not be moved to %1$s. This is most likely an issue with permissions, or upload failed.', "enable-media-replace"), $targetFile );
        throw new \RuntimeException($ex);
      }

      // init targetFile.
      $this->targetFile = new File($targetFile);

      if ($this->sourceFile->getPermissions() > 0)
        chmod( $targetFile, $this->sourceFile->getPermissions() ); // restore permissions
      else {
        // 'Setting permissions failed';
      }

      // update the file attached. This is required for wp_get_attachment_url to work.
      update_attached_file($this->post_id, $this->targetFile->getFullFilePath() );
      $this->target_url = wp_get_attachment_url($this->post_id);

      // Run the filter, so other plugins can hook if needed.
      $filtered = apply_filters( 'wp_handle_upload', array(
          'file' => $this->targetFile->getFullFilePath(),
          'url'  => $this->target_url,
          'type' => $this->targetFile->getFileMime(),
      ), 'sideload');

      // check if file changed during filter. Set changed to attached file meta properly.
      if (isset($filtered['file']) && $filtered['file'] != $this->targetFile->getFullFilePath() )
      {
        update_attached_file($this->post_id, $filtered['file'] );
        $this->targetFile = new File($filtered['file']);  // handle as a new file
        Log::addInfo('WP_Handle_upload filter returned different file', $filtered);
      }

      $metadata = wp_generate_attachment_metadata( $this->post_id, $this->targetFile->getFullFilePath() );
      wp_update_attachment_metadata( $this->post_id, $metadata );
      $this->target_metadata = $metadata;

      if ($this->replaceMode == self::MODE_SEARCHREPLACE)
      {
         $title = $this->getNewTitle();

         $update_ar = array('ID' => $this->post_id);
         $update_ar['post_title'] = $title;
         $update_ar['post_name'] = sanitize_title($title);
    //     $update_ar['guid'] = wp_get_attachment_url($this->post_id);
         $update_ar['post_mime_type'] = $this->targetFile->getFileMime();
         $post_id = \wp_update_post($update_ar, true);

         // update post doesn't update GUID on updates.
         $wpdb->update( $wpdb->posts, array( 'guid' =>  $this->target_url), array('ID' => $this->post_id) );
         //enable-media-replace-upload-done

         // @todo Replace this one with proper Notices:addError;
         if (is_wp_error($post_id))
         {
          $errors = $post_id->get_error_messages();
         foreach ($errors as $error) {
             echo $error;
          }
         }
          $this->doSearchReplace();
          do_action("enable-media-replace-upload-done", $this->target_url, $this->source_url);
      }

      if(wp_attachment_is_image($this->post_id))
      {
        $this->ThumbnailUpdater->setNewMetadata($this->target_metadata);
        $result = $this->ThumbnailUpdater->updateThumbnails();
        if (false === $result)
          Log::addWarn('Thumbnail Updater returned false');
      }

      // if all set and done, update the date.
      // This must be done after wp_update_posts
      $this->updateDate(); // updates the date.

      // Give the caching a kick. Off pending specifics.
      $cache_args = array(
        'flush_mode' => 'post',
        'post_id' => $this->post_id,
      );

      $cache = new emrCache();
      $cache->flushCache($cache_args);

  }

  protected function getNewTitle()
  {
    // get basename without extension
    $title = basename($this->targetFile->getFileName(), '.' . $this->targetFile->getFileExtension());
    $meta = $this->target_metadata;

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

  /** Returns a full target path to place to new file. Including the file name!  **/
  protected function getTargetFile()
  {
    $targetPath = null;
    if ($this->replaceMode == self::MODE_REPLACE)
    {
      $targetFile = $this->sourceFile->getFullFilePath(); // overwrite source
    }
    elseif ($this->replaceMode == self::MODE_SEARCHREPLACE)
    {
        $path = $this->sourceFile->getFilePath();
        $unique = wp_unique_filename($path, $this->targetName);

        $new_filename = apply_filters( 'emr_unique_filename', $unique, $path, $this->post_id );
        $targetFile = trailingslashit($path) . $new_filename;
    }
    if (is_dir($targetFile)) // this indicates an error with the source.
    {
        Log::addWarn('TargetFile is directory ' . $targetFile );
        $upload_dir = wp_upload_dir();
        if (isset($upload_dir['path']))
        {
          $targetFile = trailingslashit($upload_dir['path']) . wp_unique_filename($targetFile, $this->targetName);
        }
        else {
          $err = 'EMR could not establish a proper destination for replacement';
          Log::addError($err);
          throw new \RuntimeException($err);
          exit($err); // fallback

        }
    }

    return $targetFile;
  }

  /** Tries to remove all of the old image, without touching the metadata in database
  *  This might fail on certain files, but this is not an indication of success ( remove might fail, but overwrite can still work)
  */
  protected function removeCurrent()
  {
    $meta = \wp_get_attachment_metadata( $this->post_id );
    $backup_sizes = get_post_meta( $this->post_id, '_wp_attachment_backup_sizes', true );
    $result = \wp_delete_attachment_files($this->post_id, $meta, $backup_sizes, $this->sourceFile->getFullFilePath() );

  }

  /** Handle new dates for the replacement */
  protected function updateDate()
  {
    global $wpdb;
    $post_date = $this->datetime;
    $post_date_gmt = get_gmt_from_date($post_date);

    $update_ar = array('ID' => $this->post_id);
    if ($this->timeMode == static::TIME_UPDATEALL || $this->timeMode == static::TIME_CUSTOM)
    {
      $update_ar['post_date'] = $post_date;
      $update_ar['post_date_gmt'] = $post_date_gmt;
    }
    else {
      //$update_ar['post_date'] = 'post_date';
    //  $update_ar['post_date_gmt'] = 'post_date_gmt';
    }
    $update_ar['post_modified'] = $post_date;
    $update_ar['post_modified_gmt'] = $post_date_gmt;

    $updated = $wpdb->update( $wpdb->posts, $update_ar , array('ID' => $this->post_id) );

    wp_cache_delete($this->post_id, 'posts');


    /*    $sql = $wpdb->prepare(
            "UPDATE $table_name SET post_date = '$post_date', post_date_gmt = '$post_date_gmt' WHERE ID = %d;",
            $ID
        );
        $wpdb->query($sql);   */
  }


  protected function doSearchReplace()
  {
      global $wpdb;

     // Search-and-replace filename in post database
 		$current_base_url = emr_get_match_url( $this->source_url);

    /** Fail-safe if base_url is a whole directory, don't go search/replace */
    if (is_dir($current_base_url))
    {
      Log::addError('Search Replace tried to replace to directory - ' . $current_base_url);
      exit('Fail Safe :: Source Location seems to be a directory.');
    }

    /* Search and replace in WP_POSTS */
 		$posts_sql = $wpdb->remove_placeholder_escape($wpdb->prepare(
 			"SELECT ID, post_content FROM $wpdb->posts WHERE post_status = 'publish' AND post_content LIKE %s;",
 			'%' . $current_base_url . '%'));

//INNER JOIN ' . $wpdb->posts . ' on ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id

    $postmeta_sql = 'SELECT meta_id, post_id, meta_value FROM ' . $wpdb->postmeta . '
        WHERE post_id in (SELECT ID from '. $wpdb->posts . ' where post_status = "publish") AND meta_value like %s  ';
    $postmeta_sql = $wpdb->remove_placeholder_escape($wpdb->prepare($postmeta_sql, '%' . $current_base_url . '%'));

    $rsmeta = $wpdb->get_results($postmeta_sql, ARRAY_A);
 		$rs = $wpdb->get_results( $posts_sql, ARRAY_A );

 		$number_of_updates = 0;

    $search_urls  = emr_get_file_urls( $this->source_url, $this->source_metadata );
    $replace_urls = emr_get_file_urls( $this->target_url, $this->target_metadata );
    $replace_urls = array_values(emr_normalize_file_urls( $search_urls, $replace_urls ));

    Log::addDebug('Replacing references', array($search_urls, $replace_urls));

 		if ( ! empty( $rs ) ) {
 			foreach ( $rs AS $rows ) {
 				$number_of_updates = $number_of_updates + 1;
 				// replace old URLs with new URLs.
 				$post_content = $rows["post_content"];
 				$post_content = str_replace( $search_urls, $replace_urls, $post_content );

 				$sql = $wpdb->prepare(
 					"UPDATE $wpdb->posts SET post_content = %s WHERE ID = %d;",
 					array($post_content, $rows["ID"])
 				);
      //  echo "$sql <BR>";
 				$wpdb->query( $sql );
 			}
    }
    if (! empty($rsmeta))
    {
      foreach ($rsmeta as $row)
      {
        $number_of_updates++;
        $content = $row['meta_value'];
        $content = str_replace($search_urls, $replace_urls, $content);

        $sql = $wpdb->prepare('UPDATE ' . $wpdb->postmeta . ' SET meta_value = %s WHERE meta_id = %d', $content, $row['meta_id'] );
        $wpdb->query($sql);
      }
    }


  } // doSearchReplace

} // class
