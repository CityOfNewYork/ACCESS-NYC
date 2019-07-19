<?php
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly.

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;

/* Simple class for updating thumbnails.
*
*
*
*/
class ThumbnailUpdater
{
  protected $attach_id;
  protected $oldMeta = array();
  protected $newMeta = array();

  protected $convertArray = array();
  protected $relPath;

  protected $post_table;

  public function __construct($id)
  {
    $this->attach_id = intval($id);

    global $wpdb;
    $table_name = $wpdb->prefix . "posts";
  //  $postmeta_table_name = $wpdb->prefix . "postmeta";

    $this->post_table = $table_name;
  }

  public function setOldMetadata($metadata)
  {
      if (isset($metadata['sizes']))
        $this->oldMeta = $metadata;
  }

  public function setNewMetadata($metadata)
  {
      if (isset($metadata['sizes']))
        $this->newMeta = $metadata;


      // extract month prefix to prevent overwriting wrong images.
      $file = $metadata['file'];
      $pos = strrpos($metadata['file'], '/');
      $month_path = substr($file, 0, $pos);
      $this->relPath = trailingslashit($month_path);
  }


  public function updateThumbnails()
  {
    if (count($this->oldMeta) == 0 || count($this->newMeta) == 0)
      return false;

      $convertArray = array();
      foreach($this->oldMeta['sizes'] as $sizeName => $data)
      {
         if (isset($this->newMeta['sizes'][$sizeName]))
         {

           //in some rare cases 'file' is missing
           $oldFile = isset($data['file']) ? $data['file'] : null;
           if(is_array($oldFile)) { $oldFile = $oldFile[0];} // HelpScout case 709692915
           if(empty($oldFile)) {
               return false; //make sure we don't replace in this case as we will break the URLs for all the images in the folder.
           }
           $newFile = $this->newMeta['sizes'][$sizeName]['file'];

           // if images are not same size.
           if ($oldFile != $newFile)
           {
             $this->convertArray[] = array('imageFrom' => $this->relPath . $oldFile, 'imageTo' => $this->relPath . $newFile );
           }

         }
         else {
           $this->findNearestSize($data, $sizeName);
         }

      }
      $this->updateDatabase();

  }

  protected function updateDatabase()
  {
    global $wpdb;
    $sql = "UPDATE " . $this->post_table . " set post_content = REPLACE(post_content, %s, %s)";

		Log::addDebug('Thumbnail Updater - Converting Thumbnails for sizes', $this->convertArray);
    foreach($this->convertArray as $convert_item)
    {
        $from = $convert_item['imageFrom'];
        $to = $convert_item['imageTo'];

        $replace_sql = $wpdb->prepare($sql, $from, $to );
        $wpdb->query($replace_sql);

    }
  }

  /** FindNearestsize
  * This works on the assumption that when the exact image size name is not available, find the nearest width with the smallest possible difference to impact the site the least.
  */
  protected function findNearestSize($oldData, $sizeName)
  {
      $old_width = $oldData['width']; // the width from size not in new image
      $new_width = $this->newMeta['width']; // default check - new width on image

      $diff = abs($old_width - $new_width);
      $closest_file = str_replace($this->relPath, '', $this->newMeta['file']);

      foreach($this->newMeta['sizes'] as $sizeName => $data)
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

      if(empty($closest_file)) return;
      $oldFile = $oldData['file'];
      if(is_array($oldFile)) { $oldFile = $oldFile[0];} // HelpScout case 709692915
      if(empty($oldFile)) {
          return; //make sure we don't replace in this case as we will break the URLs for all the images in the folder.
      }
      $this->convertArray[] = array('imageFrom' => $this->relPath .  $oldFile, 'imageTo' => $this->relPath . $closest_file);

  }


}
