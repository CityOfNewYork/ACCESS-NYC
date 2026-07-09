<?php
namespace EnableMediaReplace\Replacer;

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Replacer\Libraries\Unserialize\Unserialize;

/** Module: Replacer.
*
* - Able to replace across database
* - Only replace thumbnails feature dependent on media library
* - Support for page builders / strange data
*/

class Replacer
{

	protected $source_url;
	protected $target_url;
	protected $source_metadata = array();
	protected $target_metadata = array();

	private $default_replace_settings = array(
			'component' => 'unset',
			'json_flags' => JSON_UNESCAPED_SLASHES,
			'replacer_do_save' => true,
			'replace_no_serialize' => false,
	);

	private $replace_settings;

	public function __construct()
	{
		  //$this->source_url = $source_url;
			///$this->target_url = $target_url;
			$this->loadFormats();
	}

	// Load classes that handle alternative formats that can occur in the metadata / post data.
	protected function loadFormats()
	{
			Modules\Elementor::getInstance();
			Modules\WpBakery::getInstance();
			Modules\YoastSeo::getInstance();
			Modules\Breakdance::getInstance();
	}

	public function setSource($url)
	{
			$this->source_url = $url;
	}

	public function getSource()
	{
		return $this->source_url;
	}

	public function setTarget($url)
	{
		  $this->target_url = $url;
	}

	public function getTarget()
	{
		 return $this->target_url;
	}

	public function setSourceMeta($meta)
	{
			$this->source_metadata = $meta;
	}

	public function setTargetMeta($meta)
	{
			$this->target_metadata = $meta;
	}

	public function replace($args = array())
	{
			if (is_null($this->source_url) || is_null($this->target_url))
			{
				Log::addWarn('Replacer called without source or target ');
				return false;
			}
	    $defaults = array(
	        'thumbnails_only' => false,
	    );

			$errors = array();
	    $args = wp_parse_args($args, $defaults);

			$this->setReplaceSettings(['component' => 'emr']); // set to defaults.

	     // Search-and-replace filename in post database
	     // @todo Check this with scaled images.
	 		$base_url = parse_url($this->source_url, PHP_URL_PATH);// emr_get_match_url( $this->source_url);
	    $base_url = str_replace('.' . pathinfo($base_url, PATHINFO_EXTENSION), '', $base_url);

	    /** Fail-safe if base_url is a whole directory, don't go search/replace */
	    if (false === $this->fileIsRestricted($base_url) && is_dir($base_url))
	    {
	      Log::addError('Search Replace tried to replace to directory - ' . $base_url);
				$errors[] = __('Fail Safe :: Source Location seems to be a directory.', 'enable-media-replace/');
	      return $errors;
	    }

	    if (strlen(trim($base_url)) == 0)
	    {
	      Log::addError('Current Base URL emtpy - ' . $base_url);
	      $errors[] = __('Fail Safe :: Source Location returned empty string. Not replacing content','enable-media-replace/');
	      return $errors;
	    }

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

	    Log::addDebug('Source', $this->source_metadata);
	    Log::addDebug('Target', $this->target_metadata);
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
	        $replace_url = isset($replace_urls[$size]) ? $replace_urls[$size] : false;
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
	      $errors[] = __('There was an issue with updating your image URLS: Search and replace have different amount of values. Aborting updating thumbnails', 'enable-media-replace');
	      return $errors;
	    }

	    Log::addDebug('Doing meta search and replace -', array($search_urls, $replace_urls) );
	    Log::addDebug('Searching with BaseuRL ' . $base_url);

	    do_action('emr/replacer/replace_urls', $search_urls, $replace_urls);
	    $updated = 0;

	    $updated += $this->doReplaceQuery($base_url, $search_urls, $replace_urls);

	    $replaceRuns = apply_filters('emr/replacer/custom_replace_query', array(), $base_url, $search_urls, $replace_urls);

	    foreach($replaceRuns as $component => $run)
	    {
	       Log::addDebug('Running additional replace for : '. $component, $run);

				 // @todo This could perhaps benefit from a more general approach somewhere in class for settings.
				 if (isset($run['args']))
				 {
						// Update current settings for this run only.
						$this->setReplaceSettings($run['args']);
				 }
				 else {
						$this->setReplaceSettings();
				 }
	       $updated += $this->doReplaceQuery($run['base_url'], $run['search_urls'], $run['replace_urls']);
	    }

	    Log::addDebug("Updated Records : " . $updated);
	    return $updated;
	}

	private function doReplaceQuery($base_url, $search_urls, $replace_urls)
  {
    global $wpdb;
    /* Search and replace in WP_POSTS */
    // Removed $wpdb->remove_placeholder_escape from here, not compatible with WP 4.8

    $posts_sql = $wpdb->prepare(
      "SELECT ID, post_content FROM $wpdb->posts WHERE post_status in ('publish', 'future', 'draft', 'pending', 'private')
				AND post_content LIKE %s",
      '%' . $base_url . '%');

    $rs = $wpdb->get_results( $posts_sql, ARRAY_A );
    $number_of_updates = 0;

    if ( ! empty( $rs ) ) {
      foreach ( $rs AS $rows ) {
        $number_of_updates = $number_of_updates + 1;
        // replace old URLs with new URLs.

        $post_content = $rows["post_content"];
        $post_id = $rows['ID'];
        $replaced_content = $this->replaceContent($post_content, $search_urls, $replace_urls, false, true);

        if ($replaced_content !== $post_content)
        {

        //  $result = wp_update_post($post_ar);
          $sql = 'UPDATE ' . $wpdb->posts . ' SET post_content = %s WHERE ID = %d';
          $sql = $wpdb->prepare($sql, $replaced_content, $post_id);

          $result = $wpdb->query($sql);

          if ($result === false)
          {
            Notice::addError('Something went wrong while replacing' .  $result->get_error_message() );
            Log::addError('WP-Error during post update', $result);
          }
        }

      }
    }

    $number_of_updates += $this->handleMetaData($base_url, $search_urls, $replace_urls);
    return $number_of_updates;
  }

	  private function handleMetaData($url, $search_urls, $replace_urls)
	  {
	    global $wpdb;

	    $meta_options = apply_filters('emr/replacer/metadata_tables', array('post', 'comment', 'term', 'user', 'options'));
	    $number_of_updates = 0;

			$meta_default = [
					'id' => 'meta_id',
					'value' => 'meta_value',
			];

			$table_options = [
					'postmeta' => $meta_default,
					'commentmeta' => $meta_default,
					'termmeta' => $meta_default,
					'usermeta' => $meta_default,
					'options' => [
							'id' => 'option_id',
							'value' => 'option_value',
					]

			];

			$table_options = apply_filters('emr/replacer/replacement_tables', $table_options );

			// Exeception in user meta table.
			$table_options['usermeta']['id'] = 'umeta_id';

	    foreach($table_options as $table => $data)
	    {
				 	// These must be always defined.
					$id_field = esc_sql($data['id']);
					$value_field = esc_sql($data['value']);

	        switch($table)
	        {
	          case "postmeta": // special case.
	              $sql = 'SELECT * FROM ' . $wpdb->postmeta . '
	                WHERE post_id in (SELECT ID from '. $wpdb->posts . ' where post_status in ("publish", "future", "draft", "pending", "private") ) AND meta_value like %s';
	              $type = 'post';

	              $update_sql = ' UPDATE ' . $wpdb->postmeta . ' SET meta_value = %s WHERE meta_id = %d';
	          break;
	          default:
	              $wp_table = $wpdb->{$table};  // termmeta, commentmeta etc

	              $sql = "SELECT $id_field , $value_field FROM $wp_table WHERE $value_field like %s";

	              $update_sql = " UPDATE $wp_table set $value_field = %s WHERE $id_field  = %d ";
	          break;
	        }

	        $sql = $wpdb->prepare($sql, '%' . $url . '%');


	        // This is a desparate solution. Can't find anyway for wpdb->prepare not the add extra slashes to the query, which messes up the query.
	        $rsmeta = $wpdb->get_results($sql, ARRAY_A);


	        if (! empty($rsmeta))
	        {
	          foreach ($rsmeta as $row)
	          {
	            $number_of_updates++;
	            $content = $row[$value_field];

							$component = $this->replace_settings['component'];

	            $id = $row[$id_field];

						 // Content as how it's loading.
						 $content = apply_filters('emr/replacer/load_meta_value', $content, $row, $component);

						 // If content is null, break out of everything and don't replace this.
						 if (null === $content)
						 {
							 	Log::addDebug('Content returned null, aborting this record, meta_id : ' . $id_field);
						 }
						 else {
								 $content = $this->replaceContent($content, $search_urls, $replace_urls);

								 // Content as how it's going to dbase.
								 $content = apply_filters('emr/replacer/save_meta_value', $content, $row, $component );

								 // Check if usual save should be prevented. This is for integrations.
								 if (true === $this->replace_settings['replacer_do_save'])
								 {
			           		$prepared_sql = $wpdb->prepare($update_sql, $content, $id);
			           		$result = $wpdb->query($prepared_sql);
								}
						 }


						} // Loop
	        } // if
	    } // foreach

	    return $number_of_updates;
	  } // function


		private function setReplaceSettings($settings = array())
		{
			 $this->replace_settings = wp_parse_args($settings, $this->default_replace_settings);
		}

	  /**
	  * Replaces Content across several levels of possible data
	  * @param $content String The Content to replace
	  * @param $search Array Search string
	  * @param $replace Array Replacement String
	  * @param $in_deep Boolean.  This is use to prevent serialization of sublevels. Only pass back serialized from top.
	  * @param $strict_check Boolean . If true, remove all classes from serialization check and fail. This should be done on post_content, not on metadata.
	  */
		public function replaceContent($content, $search, $replace, $in_deep = false, $strict_check = false)
	  {

			// Since ReplaceContent can now be called directly, this might not be set, set defaults if so
			if (is_null($this->replace_settings))
			{
				 $this->setReplaceSettings([]);
			}

	    if ( true === is_serialized($content))
			{
				$serialized_content = $content; // use to return content back if incomplete classes are found, prevent destroying the original information

				if (true === $strict_check)
				{
						$args = array('allowed_classes' => false);
				}
				else
				{ 
						$args = array('allowed_classes' => true);
				}

	    	$content = Unserialize::unserialize($content, $args);
				// bail directly on incomplete classes. In < PHP 7.2 is_object is false on incomplete objects!
				if (true === $this->checkIncomplete($content))
				{
					 return $serialized_content;
				}
			}

	    $isJson = $this->isJSON($content);

	    if ($isJson)
	    {
	      $content = json_decode($content);

	    }

	    if (is_string($content))  // let's check the normal one first.
	    {
	      $content = apply_filters('emr/replacer/content', $content, $search, $replace);
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
	        $content[$index] = $this->replaceContent($value, $search, $replace, true); //str_replace($value, $search, $replace);
	        if (is_string($index)) // If the key is the URL (sigh)
	        {

	           $index_replaced = $this->replaceContent($index, $search,$replace, true);
	           if ($index_replaced !== $index)
	             $content = $this->change_key($content, array($index => $index_replaced));
	        }
	      }
	    }
	    elseif(is_object($content)) // metadata objects, they exist.
	    {
				// bail directly on incomplete classes.
				if (true === $this->checkIncomplete($content))
				{
					// if it was serialized, return the original as not to corrupt data.
					if (isset($serialized_content))
					{
						 return $serialized_content;
					}
					else { // else just return the content.
						 return $content;
					}
				}
	      foreach($content as $key => $value)
	      {
	        $content->{$key} = $this->replaceContent($value, $search, $replace, true);
	      }
	    }

	    if ($isJson && $in_deep === false) // convert back to JSON, if this was JSON. Different than serialize which does WP automatically.
	    {

	      // wp-slash -> WP does stripslashes_deep which destroys JSON
				$json_flags = $this->replace_settings['json_flags'];
				Log::addDebug('Value was found to be JSON, encoding with flag: ' . $json_flags);
				$content = json_encode($content, $json_flags);
	      Log::addDebug('Content returning (array ours) ', array($content));
	    }
	    elseif($this->replace_settings['replace_no_serialize'] === false &&
			 			$in_deep === false && (is_array($content) || is_object($content))
						)
			{
				$content = maybe_serialize($content);
			}
	    return $content;
	}

	/** Check if path is allowed within openbasedir restrictions. This is an attempt to limit notices in file funtions if so.  Most likely the path will be relative in that case.
	* @param String Path as String
	*/
	private function fileIsRestricted($path)
	{

		 $basedir = ini_get('open_basedir');

		 if (false === $basedir || strlen($basedir) == 0)
		 {
				 return false;
		 }

		 $restricted = true;
		 $basedirs = preg_split('/:|;/i', $basedir);

		 foreach($basedirs as $basepath)
		 {
					if (strpos($path, $basepath) !== false)
					{
						 $restricted = false;
						 break;
					}
		 }

		 // Allow this to be overridden due to specific server configs ( ie symlinks ) might get this flagged falsely.
		 $restricted = apply_filters('emr/file/basedir_check', $restricted);

		 return $restricted;
	}

	private function change_key($arr, $set) {
        if (is_array($arr) && is_array($set)) {
    		$newArr = array();
    		foreach ($arr as $k => $v) {
    		    $key = array_key_exists( $k, $set) ? $set[$k] : $k;
    		    $newArr[$key] = is_array($v) ? $this->change_key($v, $set) : $v;
    		}
    		return $newArr;
    	}
    	return $arr;
  }

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

	/** FindNearestsize
	* This works on the assumption that when the exact image size name is not available, find the nearest width with the smallest possible difference to impact the site the least.
	*/
	private function findNearestSize($sizeName)
	{

			if (! isset($this->source_metadata['sizes'][$sizeName]) || ! isset($this->target_metadata['width'])) // This can happen with non-image files like PDF.
			{
				 // Check if metadata-less item is a svg file. Just the main file to replace all thumbnails since SVG's don't need thumbnails.
				 if (strpos($this->target_url, '.svg') !== false)
				 {
					$svg_file = wp_basename($this->target_url);
					return $svg_file;  // this is the relpath of the mainfile.
				 }

				return false;
			}
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
	}

  /* Check if given content is JSON format. */
  private function isJSON($content)
  {
      if (is_array($content) || is_object($content) || is_null($content))
        return false; // can never be.

      $json = json_decode($content);
      return $json && $json != $content;
  }

	private function checkIncomplete($var)
	{
		 return ($var instanceof \__PHP_Incomplete_Class);
	}


} // class
