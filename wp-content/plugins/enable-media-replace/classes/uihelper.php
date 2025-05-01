<?php
namespace EnableMediaReplace;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;

/* Collection of functions helping the interface being cleaner. */
class UIHelper
{
  protected $preview_size = '';
  protected $preview_width = 0;
  protected $preview_height = 0;

	protected $preview_max_width = 600;
  protected $preview_max_height = 600;

  protected $full_width = 0;
  protected $full_height = 0;

	private static $instance;

	const NOTICE_NEW_FEATURE = 'EMR001';

  public function __construct()
  {

  }

	public static function getInstance()
	{
		 if (is_null(self::$instance))
		 {
			  self::$instance = new UiHelper();
		 }

		 return self::$instance;
	}

	// @todo Add nonce URL to this url as well, in popup / prepare-remove-background
  public function getFormUrl($attach_id, $action = null)
  {
		  $action = (! is_null($action)) ? $action : 'media_replace_upload';

      $url = admin_url('upload.php');
      $url = add_query_arg(array(
          'page' => 'enable-media-replace/enable-media-replace.php',
    //      'noheader' => true,
          'action' => $action,
          'attachment_id' => $attach_id,
      ), $url);

      if (isset($_REQUEST['SHORTPIXEL_DEBUG']))
      {
        $spdebug = $_REQUEST['SHORTPIXEL_DEBUG'];
        if (is_numeric($spdebug))
          $spdebug = intval($spdebug);
        else {
          $spdebug = 3;
        }
        $url = add_query_arg('SHORTPIXEL_DEBUG', $spdebug, $url);
      }

      return $url;

  }

  public function getSuccesRedirect($attach_id)
  {
    $url = admin_url('post.php');
    $url = add_query_arg(array('action' => 'edit', 'post' => $attach_id, 'emr_replaced' => '1'), $url);

    if (isset($_REQUEST['SHORTPIXEL_DEBUG']))
    {
      $spdebug = $_REQUEST['SHORTPIXEL_DEBUG'];
      if (is_numeric($spdebug))
        $spdebug = intval($spdebug);
      else {
        $spdebug = 3;
      }
      $url = add_query_arg('SHORTPIXEL_DEBUG', $spdebug, $url);
    }

    $url = apply_filters('emr_returnurl', $url);

    return $url;

  }

	public function getBackgroundRemoveRedirect($attach_id)
	{
		//if ( isset( $_POST['remove_after_progress'] ) ) {
		    $url = admin_url("upload.php");
		    $url = add_query_arg(array(
		    'page' => 'enable-media-replace/enable-media-replace.php',
		    'action' => 'emr_prepare_remove',
		    'attachment_id' => $attach_id,
		    ), $url);

//		    $redirect_success = $url;
				return $url;
	//	  }
	}

  public function getFailedRedirect($attach_id)
  {
    $url = admin_url('upload.php');
    $url = add_query_arg(array(
          'page' => 'enable-media-replace/enable-media-replace.php',
          'action' => 'media_replace',
          'attachment_id' => $attach_id,
          '_wpnonce' => wp_create_nonce('media_replace'),
        ), $url
    );

    $url = apply_filters('emr_returnurl_failed', $url);
    return $url;
  }



  public function setPreviewSizes()
  {

    list($this->preview_size, $this->preview_width, $this->preview_height) = $this->findImageSizeByMax($this->preview_max_width);
  }

  public function setSourceSizes($attach_id)
  {
    $data = $this->getImageSizes($attach_id, 'full');  // wp_get_attachment_image_src($attach_id, 'full');
  //  $file = get_attached_file($attach_id);

    if (is_array($data))
    {

      $this->full_width = $data[1];
      $this->full_height = $data[2];
    }

  }

  protected function getImageSizes($attach_id, $size = 'thumbnail')
  {
		// We are not using this function, because depending on the theme, it can mess with the dimensions - https://wordpress.stackexchange.com/questions/167525/why-is-wp-get-attachment-image-src-returning-wrong-dimensions
//    $data = wp_get_attachment_image_src($attach_id, $size);
		$meta = wp_get_attachment_metadata($attach_id);

		$data = false;

		if (isset($meta['sizes']))
		{
				foreach($meta['sizes'] as $sizeName => $metaData)
				{
					  if ($sizeName == $size)
						{
							 $width = isset($metaData['width']) ? $metaData['width'] : 0;
							 $height = isset($metaData['height']) ? $metaData['height'] : 0;
							 $imgData = image_downsize($attach_id, $size); // return whole array w/ possible wrong dimensions.
							 $data = array($imgData[0], $width, $height);
						}
				}
		}

		if ($data === false)
		{
			$data = wp_get_attachment_image_src($attach_id, $size);
			$width = isset($data[1]) ? $data[1] : 0;
		}

    $file = get_attached_file($attach_id, true);
		if (! file_exists($file))
			return $data;

		$mime_type = wp_get_image_mime($file);

    if (strpos($mime_type, 'svg') !== false && $width <= 5)
    {
        $file = get_attached_file($attach_id);
        $data = $this->fixSVGSize($data, $file);
    }


    return $data;
  }

  protected function fixSVGSize($data, $file)
  {
    if (! function_exists('simplexml_load_file'))
      return $data;

    $xml = simplexml_load_file($file);
		//Log::addDebug('XML LOAD FILE', $xml);
    if ($xml)
    { // stolen from SVG Upload plugin
      $attr = $xml->attributes();
      $viewbox = explode(' ', $attr->viewBox);
      $data[1] = isset($attr->width) && preg_match('/\d+/', $attr->width, $value) ? (int) $value[0] : (count($viewbox) == 4 ? (int) $viewbox[2] : null);
      $data[2] = isset($attr->height) && preg_match('/\d+/', $attr->height, $value) ? (int) $value[0] : (count($viewbox) == 4 ? (int) $viewbox[3] : null);
    }

    return $data;
  }

	// Returns Preview Image HTML Output.
	public function getPreviewImage($attach_id,$file, $args = array())
	{
			$data = false;

			if ($attach_id > 0)
			{
				$data = $this->getImageSizes($attach_id, $this->preview_size); //wp_get_attachment_image_src($attach_id, $this->preview_size);
			}

			$mime_type = get_post_mime_type($attach_id);

			if (! is_array($data) || (! $file->exists() && ! $file->is_virtual()) )
			{
				// if attachid higher than zero ( exists ) but not the image, fail, that's an error state.
				$icon = ($attach_id < 0) ? '' : 'dashicons-no';
				$is_document = false;

				$defaults = array(
						'width' => $this->preview_width,
						'height' => $this->preview_height,
						'is_image' => false,
						'is_document' => $is_document,
						'is_upload' => false,
						'icon' => $icon,
						'mime_type' => null,
				);
			 $args = wp_parse_args($args, $defaults);

				// failed, it might be this server doens't support PDF thumbnails. Fallback to File preview.
				if ($mime_type == 'application/pdf')
				{
						return $this->getPreviewFile($attach_id, $file);
				}

				return $this->getPlaceHolder($args);
			}

			$url = $data[0];
			$width = $data[1];
			$height = $data[2];

		 // width
		 $width_ratio = $height_ratio = 0;
		 if ($width > $this->preview_max_width)
		 {
				 $width_ratio = $width / $this->preview_max_width;
		 }
		 if ($height > $this->preview_max_height) // height
		 {
				 $height_ratio = $height / $this->preview_max_height;
		 }

		 $ratio = ($width_ratio > $height_ratio) ? $width_ratio : $height_ratio;

		 if ($ratio > 0)
		 {

			 $width  = floor($width / $ratio);
			 $height = floor($height / $ratio);
		 }

			// SVG's without any helpers return around 0 for width / height. Fix preview.


			 // preview width, if source if found, should be set to source.
			 $this->preview_width = $width;
			 $this->preview_height = $height;

			$image = "<img src='$url' width='$width' height='$height' class='image' style='max-width:100%; max-height: 100%;' />";
		//	$image = "<span class='the-image' style='background: url(\"" . $url . "\")';>&nbsp;</span>";

			$defaults = array(
				'width' => $width,
				'height' => $height,
				'image' => $image,
				'mime_type' => $mime_type,
				'is_upload' => false,
				'file_size' => $file->getFileSize(),
			);

		 $args = wp_parse_args($args, $defaults);

			$output = $this->getPlaceHolder($args);
			return $output;
	}

  public function getPreviewError($attach_id)
  {
    $args = array(
      'width' => $this->preview_width,
      'height' => $this->preview_height,
      'icon' => 'dashicons-no',
      'is_image' => false,
    );
    $output = $this->getPlaceHolder($args);
    return $output;
  }

  public function getPreviewFile($attach_id, $file, $args = array())
  {
    if ($attach_id > 0)
    {
      //$filepath = get_attached_file($attach_id);
      $filename = $file->getFileName();
    }
    else {
      $filename = false;
    }

    $mime_type = $file->getMime();
		if (false === $mime_type) // If server is not supporting this, go w/ the post mime type one.
		{
			$mime_type = get_post_mime_type($attach_id);
		}

    $defaults = array(
      'width' => 300,
      'height' => 300,
      'is_image' => false,
      'is_document' => true,
			'is_upload' => false,
      'layer' => $filename,
      'mime_type' => $mime_type,
      'file_size' => $file->getFileSize(),
    );
		$args = wp_parse_args($args, $defaults);


    $output = $this->getPlaceHolder($args);
    return $output;
  }

  public function findImageSizeByMax($maxwidth)
  {
      $image_sizes = $this->wp_get_image_sizes();

      $match_width = 0;
      $match_height = 0;
      $match = '';

      foreach($image_sizes as $sizeName => $sizeItem)
      {

          $width = $sizeItem['width'];
          if ($width > $match_width && $width <= $maxwidth)
          {
            $match = $sizeName;
            $match_width = $width;
            $match_height = $sizeItem['height'];
          }
      }
      return array($match, $match_width, $match_height);
  }

  public function getPlaceHolder($args)
  {
    $defaults = array(
        'width' => 150,
        'height' => 150,
        'image' => '',
        'icon' => 'dashicons-media-document',
        'layer' =>  $this->full_width . ' x ' . $this->full_height,
        'is_image' => true,
        'is_document' => false,
				'is_upload' => false, // indicating right-side upload interface for image.
        'mime_type' => false,
        'file_size' => false,
				'remove_bg_ui' => false, // In process icons et al when removing background, for preview pane.

    );

    $args = wp_parse_args($args, $defaults);

    $w = $args['width'];
    $h = $args['height'];

    if ($w < 150)   // minimum
      $w = 150;
    if ($h < 150)
      $h = 150;

    $icon = $args['icon'];

    if ($args['is_image'])
    {
      $placeholder_class = 'is_image';
    }
    else {
      $placeholder_class = 'not_image';
    }

    if ($args['is_document'])
    {
      $placeholder_class .= ' is_document';
    }

		if (true == $args['is_upload'])
		{
			 $placeholder_class .= ' upload-file-action';
		}

    $filetype = '';
    if ($args['mime_type'])
    {
      $filetype = 'data-filetype="' . $args['mime_type'] . '"';
    }

    $filesize = ($args['file_size']) ? $args['file_size'] : '';

		$is_backgroundremove_ui = (isset($args['remove_bg_ui']) && $args['remove_bg_ui'] == true);
		$background_remove_ui = ($is_backgroundremove_ui) ? $this->getBgremoveUI() : '';

    $output = "<div class='image_placeholder $placeholder_class' $filetype style='width:" . $w . "px; height:". $h ."px'> ";
		if (true === $args['is_upload'])
		{
			$output .= "<span class='upload-title'>" . __('New', 'enable-media-replace') . "</span>";
			$output .= '<input type="file" name="userfile" id="upload-file" />';
				$output .= '<div class="drag-and-drop-title">
                              <span>' . __('Click here to upload or drop file in area', 'enable-media-replace') . '</span>
                   </div>';
		}
		else
		{
			$title = (true === $is_backgroundremove_ui ) ? __('Preview', 'enable-media-replace') : __('Current', 'enable-media-replace');
			$output .= "<span class='upload-title'>" . $title . "</span>";
    	$output .= $args['image'];
		}
    $output .= "<div class='dashicons $icon'>&nbsp;</div>";
    $output .= "<span class='textlayer'>" . $args['layer'] . "</span>";
		$output .= $background_remove_ui;
    $output .= "<div class='image_size'>" . $this->convertFileSize($filesize). "</div>";
    $output .= "</div>";

    return $output;
  }

	public function isBackgroundRemovable($post)
	{
		  if (false === wp_attachment_is_image($post))
				return false;

			if (false === emr()->useFeature('background'))
				return false;

			$extensions = array('jpg', 'png','jpeg');

			$mime = get_post_mime_type($post);
			foreach($extensions as $extension)
			{
				  if (strpos($mime, $extension) !== false )
						return true;
			}

			return false;

	}

	private function getBgremoveUI()
	{
		$output = '<div class="overlay" id="overlay">
										<div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>';

		$output .=	'<h3>'  .  esc_html__('Removing background...', 'enable-media-replace') . '</h3>';
		$output .= '</div>';

		return $output;
	}

  private function convertFileSize($filesize)
  {
     return size_format($filesize);
  }

    /**
  * Get size information for all currently-registered image sizes.
  * Directly stolen from - https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
  * @global $_wp_additional_image_sizes
  * @uses   get_intermediate_image_sizes()
  * @return array $sizes Data for all currently-registered image sizes.
  */
  private function wp_get_image_sizes() {
   global $_wp_additional_image_sizes;

   $sizes = array();

   foreach ( get_intermediate_image_sizes() as $_size ) {
     if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
       $sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
       $sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
     } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
       $sizes[ $_size ] = array(
         'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
         'height' => $_wp_additional_image_sizes[ $_size ]['height'],
       );
     }
   }

   return $sizes;
  }

  /** For Location Dir replacement. Get the Subdir that is in use now.  */
  public function getRelPathNow()
  {
      $uploadDir = wp_upload_dir();
      if (isset($uploadDir['subdir']))
        return ltrim($uploadDir['subdir'], '/');
      else
        return false;
  }

	public function featureNotice()
	{
		 	// @todo Remove in 2023.
			$message = sprintf(__('%s New Beta Feature! %s %s Enable Media Replace now gives you the ability to remove the background of any image. Try it out in the Media Library: hover over an image and click on Remove Background. Or just click on Remove background from the image editing window! %s  ', 'enable-media-replace' ), '<h3>', '</h3>',
				'<p>', '</p>');

		  $notice = Notices::addNormal($message, true);
			Notices::makePersistent($notice, self::NOTICE_NEW_FEATURE, 2 * YEAR_IN_SECONDS);
	}

} // class
