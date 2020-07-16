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

  // everything target is what will be. This is set when the image is replace, the result. Used for replacing.
  protected $targetFile;
  protected $targetName;
  protected $target_metadata;
  protected $target_url;

  protected $target_location = false; // option for replacing to another target location

  protected $replaceMode = 1; // replace if nothing is set
  protected $timeMode = 1;
  protected $datetime = null;

  protected $ThumbnailUpdater; // class

  const MODE_REPLACE = 1;
  const MODE_SEARCHREPLACE = 2;

  const TIME_UPDATEALL = 1; // replace the date
  const TIME_UPDATEMODIFIED = 2; // keep the date, update only modified
  const TIME_CUSTOM = 3; // custom time entry

  public function __construct($post_id)
  {
      $this->post_id = $post_id;

      if (function_exists('wp_get_original_image_path')) // WP 5.3+
      {
          $source_file = wp_get_original_image_path($post_id);
          if ($source_file === false) // if it's not an image, returns false, use the old way.
            $source_file = trim(get_attached_file($post_id, apply_filters( 'emr_unfiltered_get_attached_file', true )));
      }
      else
        $source_file = trim(get_attached_file($post_id, apply_filters( 'emr_unfiltered_get_attached_file', true )));

      Log::addDebug('SourceFile ' . $source_file);
      $this->sourceFile = new File($source_file);

      $this->source_post = get_post($post_id);
      $this->source_is_image = wp_attachment_is('image', $this->source_post);
      $this->source_metadata = wp_get_attachment_metadata( $post_id );

      if (function_exists('wp_get_original_image_url')) // WP 5.3+
      {
        $source_url = wp_get_original_image_url($post_id);
        if ($source_url === false)  // not an image, or borked, try the old way
          $source_url = wp_get_attachment_url($post_id);

        $this->source_url = $source_url;
      }
      else
        $this->source_url = wp_get_attachment_url($post_id);
    //  $this->ThumbnailUpdater = new \ThumbnailUpdater($post_id);
      //$this->ThumbnailUpdater->setOldMetadata($this->source_metadata);
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

      $targetFile = $this->getTargetFile();

      if (is_null($targetFile))
      {
        return null;
      //  $ex = __('Target File could not be set. The source file might not be there. In case of search and replace, a filter might prevent this', "enable-media-replace");
      //  throw new \RuntimeException($ex);
      }



      $targetFileObj = new File($targetFile);
      $result = $targetFileObj->checkAndCreateFolder();
      if ($result === false)
        Log::addError('Directory creation for targetFile failed');


      $this->removeCurrent(); // tries to remove the current files.

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
        Log::addWarn('Setting permissions failed');
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

      /** If author is different from replacer, note this */
      $author_id = get_post_meta($this->post_id, '_emr_replace_author', true);

      if ( intval($this->source_post->post_author) !== get_current_user_id())
      {

         update_post_meta($this->post_id, '_emr_replace_author', get_current_user_id());
      }
      elseif ($author_id)
      {

        delete_post_meta($this->post_id, '_emr_replace_author');
      }

      if ($this->replaceMode == self::MODE_SEARCHREPLACE)
      {
         // Write new image title.
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

      }  // SEARCH REPLACE MODE

      $args = array(
          'thumbnails_only' => ($this->replaceMode == self::MODE_SEARCHREPLACE) ? false : true,
      );

      // Search Replace will also update thumbnails.
      $this->doSearchReplace($args);

      /*if(wp_attachment_is_image($this->post_id))
      {
        $this->ThumbnailUpdater->setNewMetadata($this->target_metadata);
        $result = $this->ThumbnailUpdater->updateThumbnails();
        if (false === $result)
          Log::addWarn('Thumbnail Updater returned false');
      }*/

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

      do_action("enable-media-replace-upload-done", $this->target_url, $this->source_url);

      return true;
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

  /** Gets the source file after processing. Returns a file */
  public function getSourceFile()
  {
     return $this->sourceFile;
  }

  public function setNewTargetLocation($new_rel_location)
  {
      $uploadDir = wp_upload_dir();
      $newPath = trailingslashit($uploadDir['basedir']) . $new_rel_location;

      if (! is_dir($newPath))
      {
        Notices::addError(__('Specificed new directory does not exist. Path must be a relative path from the upload directory and exist', 'enable-media-replace'));
        return false;
      }
      $this->target_location = trailingslashit($newPath);
      return true;
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
        if ($this->target_location) // Replace to another path.
        {
           $otherTarget = new File($this->target_location . $this->targetName);
           if ($otherTarget->exists())
           {
              Notices::addError(__('In specificied directory there is already a file with the same name. Can\'t replace.', 'enable-media-replace'));
              return null;
           }
           $path = $this->target_location; // if all went well.
        }
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
          $err = __('EMR could not establish a proper destination for replacement', 'enable-media-replace');
          Notices::addError($err);
          Log::addError($err);
        //  throw new \RuntimeException($err);
        //  exit($err); // fallback
          return null;
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

    // this must be -scaled if that exists, since wp_delete_attachment_files checks for original_files but doesn't recheck if scaled is included since that the one 'that exists' in WP . $this->source_file replaces original image, not the -scaled one.
    $file = get_attached_file($this->post_id);
    $result = \wp_delete_attachment_files($this->post_id, $meta, $backup_sizes, $file );

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

  }


  protected function doSearchReplace($args = array())
  {
    $defaults = array(
        'thumbnails_only' => false,
    );

    $args = wp_parse_args($args, $defaults);

    global $wpdb;

     // Search-and-replace filename in post database
     // @todo Check this with scaled images.
 		$current_base_url = parse_url($this->source_url, PHP_URL_PATH);// emr_get_match_url( $this->source_url);
    $current_base_url = str_replace('.' . pathinfo($current_base_url, PATHINFO_EXTENSION), '', $current_base_url);


    /** Fail-safe if base_url is a whole directory, don't go search/replace */
    if (is_dir($current_base_url))
    {
      Log::addError('Search Replace tried to replace to directory - ' . $current_base_url);
      Notices::addError(__('Fail Safe :: Source Location seems to be a directory.', 'enable-media-replace'));
      return;
    }

    if (strlen(trim($current_base_url)) == 0)
    {
      Log::addError('Current Base URL emtpy - ' . $current_base_url);
      Notices::addError(__('Fail Safe :: Source Location returned empty string. Not replacing content','enable-media-replace'));
      return;
    }


    //$search_files = $this->getFilesFromMetadata($this->source_metadata);
    //$replace_files = $this->getFilesFromMetadata($this->target_metadata);
  //  $arr = $this->getRelativeURLS();

    /*$search_urls  = emr_get_file_urls( $this->source_url, $this->source_metadata );
    $replace_urls = emr_get_file_urls( $this->target_url, $this->target_metadata );
    $replace_urls = array_values(emr_normalize_file_urls( $search_urls, $replace_urls ));*/

    // get relurls of both source and target.
    $urls = $this->getRelativeURLS();


    if ($args['thumbnails_only'])
    {
      foreach($urls as $side => $data)
      {
        if (isset($data['base']))
        {
          unset($urls[$side]['base']);
        }
        if (isset($data['file']))
        {
          unset($urls[$side]['file']);
        }
      }
    }

    $search_urls = $urls['source'];
    $replace_urls = $urls['target'];

    /* If the replacement is much larger than the source, there can be more thumbnails. This leads to disbalance in the search/replace arrays.
      Remove those from the equation. If the size doesn't exist in the source, it shouldn't be in use either */
    foreach($replace_urls as $size => $url)
    {
      if (! isset($search_urls[$size]))
      {
        Log::addDebug('Dropping size ' . $size . ' - not found in source urls');
        unset($replace_urls[$size]);
      }
    }

    /* If on the other hand, some sizes are available in source, but not in target, try to replace them with something closeby.  */
    foreach($search_urls as $size => $url)
    {
        if (! isset($replace_urls[$size]))
        {
           $closest = $this->findNearestSize($size);
           if ($closest)
           {
              $sourceUrl = $search_urls[$size];
              $baseurl = trailingslashit(str_replace(wp_basename($sourceUrl), '', $sourceUrl));
              Log::addDebug('Nearest size of source ' . $size . ' for target is ' . $closest);
              $replace_urls[$size] = $baseurl . $closest;
           }
           else
           {
             Log::addDebug('Unset size ' . $size . ' - no closest found in source');
           }
        }
    }

    /* If source and target are the same, remove them from replace. This happens when replacing a file with same name, and +/- same dimensions generated.

    After previous loops, for every search there should be a replace size.
    */
    foreach($search_urls as $size => $url)
    {
        $replace_url = $replace_urls[$size];
        if ($url == $replace_url) // if source and target as the same, no need for replacing.
        {
          unset($search_urls[$size]);
          unset($replace_urls[$size]);
        }
    }

    // If the two sides are disbalanced, the str_replace part will cause everything that has an empty replace counterpart to replace it with empty. Unwanted.
    if (count($search_urls) !== count($replace_urls))
    {

      Log::addError('Unbalanced Replace Arrays, aborting', array($search_urls, $replace_urls, count($search_urls), count($replace_urls) ));
      Notices::addError(__('There was an issue with updating your image URLS: Search and replace have different amount of values. Aborting updating thumbnails', 'enable-media-replace'));
      return;
    }

    Log::addDebug('Doing meta search and replace -', array($search_urls, $replace_urls) );
    Log::addDebug('Searching with BaseuRL' . $current_base_url);

    /* Search and replace in WP_POSTS */
    // Removed $wpdb->remove_placeholder_escape from here, not compatible with WP 4.8
 		$posts_sql = $wpdb->prepare(
 			"SELECT ID, post_content FROM $wpdb->posts WHERE post_status = 'publish' AND post_content LIKE %s;",
 			'%' . $current_base_url . '%');

    // json encodes it all differently. Catch json-like encoded urls
    //$json_url = str_replace('/', '\/', ltrim($current_base_url, '/') );

    $postmeta_sql = 'SELECT meta_id, post_id, meta_key, meta_value FROM ' . $wpdb->postmeta . '
        WHERE post_id in (SELECT ID from '. $wpdb->posts . ' where post_status = "publish") AND meta_value like %s';
    $postmeta_sql = $wpdb->prepare($postmeta_sql, '%' . $current_base_url . '%');

    // This is a desparate solution. Can't find anyway for wpdb->prepare not the add extra slashes to the query, which messes up the query.
//    $postmeta_sql = str_replace('[JSON_URL]', $json_url, $postmeta_sql);

    $rsmeta = $wpdb->get_results($postmeta_sql, ARRAY_A);

 		$rs = $wpdb->get_results( $posts_sql, ARRAY_A );

 		$number_of_updates = 0;

    Log::addDebug('Queries', array($postmeta_sql, $posts_sql));
    Log::addDebug('Queries found '  . count($rs) . ' post rows and ' . count($rsmeta) . ' meta rows');


 		if ( ! empty( $rs ) ) {
 			foreach ( $rs AS $rows ) {
 				$number_of_updates = $number_of_updates + 1;
 				// replace old URLs with new URLs.
 				$post_content = $rows["post_content"];
 				//$post_content = str_replace( $search_urls, $replace_urls, $post_content );

        $post_id = $rows['ID'];
        $post_ar = array('ID' => $post_id);
        $post_ar['post_content'] = $this->replaceContent($post_content, $search_urls, $replace_urls);

        if ($post_ar['post_content'] !== $post_content)
        {
          $result = wp_update_post($post_ar);
          if (is_wp_error($result))
          {
            Notice::addError('Something went wrong while replacing' .  $result->get_error_message() );
            Log::addError('WP-Error during post update', $result);
          }
        }

 			}
    }
    if (! empty($rsmeta))
    {
      foreach ($rsmeta as $row)
      {
        $number_of_updates++;
        $content = $row['meta_value'];
        $meta_key = $row['meta_key'];
        $post_id = $row['post_id'];
        $content = $this->replaceContent($content, $search_urls, $replace_urls); //str_replace($search_urls, $replace_urls, $content);

        update_post_meta($post_id, $meta_key, $content);
    //    $sql = $wpdb->prepare('UPDATE ' . $wpdb->postmeta . ' SET meta_value = %s WHERE meta_id = %d', $content, $row['meta_id'] );
    //    $wpdb->query($sql);
      }
    }


  } // doSearchReplace

  private function replaceContent($content, $search, $replace)
  {
    //$is_serial = false;
    $content = maybe_unserialize($content);
    $isJson = $this->isJSON($content);

    if ($isJson)
    {
      Log::addDebug('Found JSON Content');
      $content = json_decode($content);
    }

    if (is_string($content))  // let's check the normal one first.
    {
      $content = str_replace($search, $replace, $content);
    }
    elseif (is_wp_error($content)) // seen this.
    {
       //return $content;  // do nothing.
    }
    elseif (is_array($content) ) // array metadata and such.
    {
      foreach($content as $index => $value)
      {
        $content[$index] = $this->replaceContent($value, $search, $replace); //str_replace($value, $search, $replace);
      }
      //return $content;
    }
    elseif(is_object($content)) // metadata objects, they exist.
    {
      foreach($content as $key => $value)
      {
        $content->{$key} = $this->replaceContent($value, $search, $replace); //str_replace($value, $search, $replace);
      }
      //return $content;
    }

    if ($isJson) // convert back to JSON, if this was JSON. Different than serialize which does WP automatically.
    {
      Log::addDebug('Value was found to be JSON, encoding');
      // wp-slash -> WP does stripslashes_deep which destroys JSON
      $content = wp_slash(json_encode($content, JSON_UNESCAPED_SLASHES));
      Log::addDebug('Content returning', array($content));
    }

    return $content;
  }

  private function getFilesFromMetadata($meta)
  {
        $fileArray = array();
        if (isset($meta['file']))
          $fileArray['file'] = $meta['file'];

        if (isset($meta['sizes']))
        {
          foreach($meta['sizes'] as $name => $data)
          {
            if (isset($data['file']))
            {
              $fileArray[$name] = $data['file'];
            }
          }
        }
      return $fileArray;
  }

  /* Check if given content is JSON format. */
  private function isJSON($content)
  {
      if (is_array($content) || is_object($content))
        return false; // can never be.

      $json = json_decode($content);
      return $json && $json != $content;
  }

  // Get REL Urls of both source and target.
  private function getRelativeURLS()
  {
      $dataArray = array(
          'source' => array('url' => $this->source_url, 'metadata' => $this->getFilesFromMetadata($this->source_metadata) ),
          'target' => array('url' => $this->target_url, 'metadata' => $this->getFilesFromMetadata($this->target_metadata) ),
      );

    //  Log::addDebug('Source Metadata', $this->source_metadata);
  //    Log::addDebug('Target Metadata', $this->target_metadata);

      $result = array();

      foreach($dataArray as $index => $item)
      {
          $result[$index] = array();
          $metadata = $item['metadata'];

          $baseurl = parse_url($item['url'], PHP_URL_PATH);
          $result[$index]['base'] = $baseurl;  // this is the relpath of the mainfile.
          $baseurl = trailingslashit(str_replace( wp_basename($item['url']), '', $baseurl)); // get the relpath of main file.

          foreach($metadata as $name => $filename)
          {
              $result[$index][$name] =  $baseurl . wp_basename($filename); // filename can have a path like 19/08 etc.
          }

      }
  //    Log::addDebug('Relative URLS', $result);
      return $result;
  }


  /** FindNearestsize
  * This works on the assumption that when the exact image size name is not available, find the nearest width with the smallest possible difference to impact the site the least.
  */
  private function findNearestSize($sizeName)
  {

      $old_width = $this->source_metadata['sizes'][$sizeName]['width']; // the width from size not in new image
      $new_width = $this->target_metadata['width']; // default check - the width of the main image

      $diff = abs($old_width - $new_width);
    //  $closest_file = str_replace($this->relPath, '', $this->newMeta['file']);
      $closest_file = wp_basename($this->target_metadata['file']); // mainfile as default

      foreach($this->target_metadata['sizes'] as $sizeName => $data)
      {
          $thisdiff = abs($old_width - $data['width']);

          if ( $thisdiff  < $diff )
          {
              $closest_file = $data['file'];
              if(is_array($closest_file)) { $closest_file = $closest_file[0];} // HelpScout case 709692915
              if(!empty($closest_file)) {
                  $diff = $thisdiff;
                  $found_metasize = true;
              }
          }
      }


      if(empty($closest_file)) return false;

      return $closest_file;
      //$oldFile = $oldData['file'];
      //if(is_array($oldFile)) { $oldFile = $oldFile[0];} // HelpScout case 709692915
      /*if(empty($oldFile)) {
          return false; //make sure we don't replace in this case as we will break the URLs for all the images in the folder.
      } */
    //  $this->convertArray[] = array('imageFrom' => $this->relPath .  $oldFile, 'imageTo' => $this->relPath . $closest_file);

  }

} // class
