<?php
namespace EnableMediaReplace\FileSystem\Controller;
use EnableMediaReplace\ShortpixelLogger\ShortPixelLogger as Log;

use EnableMediaReplace\FileSystem\Model\File\DirectoryModel as DirectoryModel;
use EnableMediaReplace\FileSystem\Model\File\FileModel as FileModel;


/** Controller for FileSystem operations
*
* This controller is used for -compound- ( complex ) FS operations, using the provided models File en Directory.
* USE via \wpSPIO()->filesystem();
*/
Class FileSystemController
{

    public function __construct()
    {

    }

    /** Get FileModel for a certain path. This can exist or not
    *
    * @param String Path Full Path to the file
    * @return FileModel FileModel Object. If file does not exist, not all values are set.
    */
    public function getFile($path)
    {
      return new FileModel($path);
    }



    /** Get DirectoryModel for a certain path. This can exist or not
    *
    * @param String $path Full Path to the Directory.
    * @return DirectoryModel Object with status set on current directory.
    */
    public function getDirectory($path)
    {
      return new DirectoryModel($path);
    }




    /** This function returns the WordPress Basedir for uploads ( without date and such )
    * Normally this would point to /wp-content/uploads.
    * @returns DirectoryModel
    */
    public function getWPUploadBase()
    {
      $upload_dir = wp_upload_dir(null, false);

      return $this->getDirectory($upload_dir['basedir']);
    }

    /** This function returns the Absolute Path of the WordPress installation where the **CONTENT** directory is located.
    * Normally this would be the same as ABSPATH, but there are installations out there with -cough- alternative approaches
    * @returns DirectoryModel  Either the ABSPATH or where the WP_CONTENT_DIR is located
    */
    public function getWPAbsPath()
    {
        $wpContentAbs = str_replace( 'wp-content', '', WP_CONTENT_DIR);
        if (ABSPATH == $wpContentAbs)
          $abspath = ABSPATH;
        else
          $abspath = $wpContentAbs;

				if (defined('UPLOADS')) // if this is set, lead.
					$abspath = trailingslashit(ABSPATH) . UPLOADS;

        $abspath = apply_filters('shortpixel/filesystem/abspath', $abspath );

        return $this->getDirectory($abspath);
    }

		public function getFullPathForWP(FileModel $file)
		{
				$fullpath = $file->getFullPath();
				$abspath = $this->getWPAbsPath();

				if (! strpos($abspath, $fullpath))
				{

				}

		}


    /** Utility function that tries to convert a file-path to a webURL.
    *
    * If possible, rely on other better methods to find URL ( e.g. via WP functions ).
    */
    public function pathToUrl(FileModel $file)
    {
      $filepath = $file->getFullPath();
      $directory = $file->getFileDir();

			$is_multi_site = (function_exists("is_multisite") && is_multisite()) ? true : false;
	    $is_main_site = is_main_site();

			//$is_multi_site = $this->env->is_multisite;
			//$is_main_site =  $this->env->is_mainsite;

      // stolen from wp_get_attachment_url
      if ( ( $uploads = wp_get_upload_dir() ) && (false === $uploads['error'] || strlen(trim($uploads['error'])) == 0  )  ) {
            // Check that the upload base exists in the file location.
            if ( 0 === strpos( $filepath, $uploads['basedir'] ) ) { // Simple as it should, filepath and basedir share.
                // Replace file location with url location.
                $url = str_replace( $uploads['basedir'], $uploads['baseurl'], $filepath );
						}
						// Multisite backups are stored under uploads/ShortpixelBackups/etc , but basedir would include uploads/sites/2 etc, not matching above
						// If this is case, test if removing the last two directories will result in a 'clean' uploads reference.
						// This is used by getting preview path ( backup pathToUrl) in bulk and for comparer..
					  elseif ($is_multi_site && ! $is_main_site  && 0 === strpos($filepath, dirname(dirname($uploads['basedir']))) )
						{

								$url = str_replace( dirname(dirname($uploads['basedir'])), dirname(dirname($uploads['baseurl'])), $filepath );
								$homeUrl = home_url();

								// The result didn't end in a full URL because URL might have less subdirs ( dirname dirname) .
								// This happens when site has blogs.dir (sigh) on a subdomain . Try to substitue the ABSPATH root with the home_url
								if (strpos($url, $homeUrl) === false)
								{
									 $url = str_replace( trailingslashit(ABSPATH), trailingslashit($homeUrl), $filepath);
								}

            } elseif ( false !== strpos( $filepath, 'wp-content/uploads' ) ) {
                // Get the directory name relative to the basedir (back compat for pre-2.7 uploads)
								//$relativePath = $this->getFile(_wp_get_attachment_relative_path( $filepath ) );
								//$basename = wp_basename($relativePath->getFullPath());

                $url = trailingslashit( $uploads['baseurl'] . '/' . _wp_get_attachment_relative_path( $filepath ) ) . wp_basename( $filepath );
            } else {
                // It's a newly-uploaded file, therefore $file is relative to the basedir.
                $url = $uploads['baseurl'] . "/$filepath";
            }
        }

        $wp_home_path = (string) $this->getWPAbsPath();
        // If the whole WP homepath is still in URL, assume the replace when wrong ( not replaced w/ URL)
        // This happens when file is outside of wp_uploads_dir
        if (strpos($url, $wp_home_path) !== false)
        {
          // This is SITE URL, for the same reason it should be home_url in FILEMODEL. The difference is when the site is running on a subdirectory
          // (1) ** This is a fix for a real-life issue, do not change if this causes issues, another fix is needed then.
		  // (2) ** Also a real life fix when a path is /wwwroot/assets/sites/2/ etc, in get site url, the home URL is the site URL, without appending the sites stuff. Fails on original image.
		    if ($is_multi_site && ! $is_main_site)
				{
					$wp_home_path = trailingslashit($uploads['basedir']);
					$home_url = trailingslashit($uploads['baseurl']);
				}
				else
          $home_url = trailingslashit(get_site_url()); // (1)
          $url = str_replace($wp_home_path, $home_url, $filepath);
        }

        // can happen if there are WP path errors.
        if (is_null($url))
          return false;

        $parsed = parse_url($url); // returns array, null, or false.

        // Some hosts set the content dir to a relative path instead of a full URL. Api can't handle that, so add domain and such if this is the case.
        if ( !isset($parsed['scheme']) ) {//no absolute URLs used -> we implement a hack

           if (isset($parsed['host'])) // This is for URL's for // without http or https. hackhack.
           {
             $scheme = is_ssl() ? 'https:' : 'http:';
             return $scheme. $url;
           }
           else
           {
           // From Metafacade. Multiple solutions /hacks.
              $home_url = trailingslashit((function_exists("is_multisite") && is_multisite()) ? trim(network_site_url("/")) : trim(home_url()));
              return $home_url . ltrim($url,'/');//get the file URL
           }
        }

        if (! is_null($parsed) && $parsed !== false)
          return $url;

        return false;
    }

    /** Utility function to check if a path is an URL
    *  Checks if this path looks like an URL.
    * @param $path String  Path to check
    * @return Boolean If path seems domain.
    */
    public function pathIsUrl($path)
    {
      $is_http = (substr($path, 0, 4) == 'http') ? true : false;
      $is_https = (substr($path, 0, 5) == 'https') ? true : false;
      $is_neutralscheme = (substr($path, 0, 2) == '//') ? true : false; // when URL is relative like //wp-content/etc
      $has_urldots = (strpos($path, '://') !== false) ? true : false; // Like S3 offloads

      if ($is_http || $is_https || $is_neutralscheme || $has_urldots)
        return true;
      else
        return false;
    }

    /** Sort files / directories in a certain way.
    * Future dev to include options via arg.
    */
    public function sortFiles($array, $args = array() )
    {
        if (count($array) == 0)
          return $array;

        // what are we sorting.
        $class = get_class($array[0]);
        $is_files = ($class == 'EnableMediaReplace\FileModel') ? true : false; // if not files, then dirs.

        usort($array, function ($a, $b) use ($is_files)
            {
              if ($is_files)
                return strcmp($a->getFileName(), $b->getFileName());
              else {
                return strcmp($a->getName(), $b->getName());
              }
            }
        );

        return $array;

    }

    public function downloadFile($url, $destinationPath)
    {

      //$downloadTimeout = defined(SHORTPIXEL_MAX_EXECUTION_TIME) ? max(SHORTPIXEL_MAX_EXECUTION_TIME - 10, 15) :;
			$max_exec = intval(ini_get('max_execution_time'));
			if ($max_exec === 0) // max execution time of zero means infinite. Quantify.
			  $max_exec = 60;
			elseif($max_exec < 0) // some hosts like to set negative figures on this. Ignore that.
			  $max_exec = 30;

			$downloadTimeout = $max_exec;


      $destinationFile = $this->getFile($destinationPath);

      $args_for_get = array(
        'stream' => true,
        'filename' => $destinationPath,
      );

      $response = wp_remote_get( $url, $args_for_get );

      if(is_wp_error( $response )) {
        Log::addError('Download file failed', array($url, $response->get_error_messages(), $response->get_error_codes() ));

        // Try to get it then via this way.
        $response = download_url($url, $downloadTimeout);
        if (!is_wp_error($response)) // response when alright is a tmp filepath. But given path can't be trusted since that can be reason for fail.
        {
          $tmpFile = $this->getFile($response);
          $result = $tmpFile->move($destinationFile);

        } // download_url ..
        else {
          Log::addError('Secondary download failed', array($url, $response->get_error_messages(), $response->get_error_codes() ));
        }
      }
      else { // success, at least the download.
          $destinationFile = $this->getFile($response['filename']);
      }

      Log::addDebug('Remote Download attempt result', array($url, $destinationPath));
      if ($destinationFile->exists())
        return true;
      else
        return false;
    }



    /** Get all files from a directory tree, starting at given dir.
    * @param DirectoryModel $dir to recursive into
    * @param Array $filters Collection of optional filters as accepted by FileFilter in directoryModel
    * @return Array Array of FileModel Objects
     **/
    public function getFilesRecursive(DirectoryModel $dir, $filters = array() )
    {
        $fileArray = array();

        if (! $dir->exists())
          return $fileArray;

        $files = $dir->getFiles($filters);
        $fileArray = array_merge($fileArray, $files);

        $subdirs = $dir->getSubDirectories();

        foreach($subdirs as $subdir)
        {
             $fileArray = array_merge($fileArray, $this->getFilesRecursive($subdir, $filters));
        }

        return $fileArray;
    }

		// Url very sparingly.
		public function url_exists($url)
		{
			 if (! function_exists('curl_init'))
			 {
				  return null;
			 }

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_exec($ch);
			$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($responseCode == 200)
			{
				return true;
			}
			else {
				return false;
			}

		}
}
