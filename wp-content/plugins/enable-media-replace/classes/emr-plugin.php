<?php
namespace EnableMediaReplace;

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;
use EnableMediaReplace\FileSystem\Controller\FileSystemController as FileSystem;
use EnableMediaReplace\Controller\RemoteNoticeController as RemoteNoticeController;
use EnableMediaReplace\Ajax;

// Does what a plugin does.
class EnableMediaReplacePlugin
{

    protected $plugin_path;
    private static $instance;

    private $user_cap = false;
    private $general_cap = false;

		private $features = array();

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'runtime')); //lowInit, before theme setup!
				add_action('admin_init', array($this, 'adminInit')); // adminInit, after functions.php
    }

    public function runtime()
    {
         $this->nopriv_plugin_actions();

        if (EMR_CAPABILITY !== false) {
            if (is_array(EMR_CAPABILITY)) {
                $this->general_cap = EMR_CAPABILITY[0];
                $this->user_cap = EMR_CAPABILITY[1];

                if (! current_user_can($this->general_cap) && ! current_user_can($this->user_cap)) {
                    return;
                }
            } else {
                $this->general_cap = EMR_CAPABILITY;
                if (! current_user_can($this->general_cap)) {
                    return;
                }
            }
        } elseif (! current_user_can('upload_files')) {
            return;
        }

				new Externals();

        $this->plugin_actions(); // init
    }

		public function adminInit()
		{
			$this->features['replace']  = true; // does nothing just for completeness
			$this->features['background'] = apply_filters('emr/feature/background', true);
      $this->features['remote_notice']  = apply_filters('emr/feature/remote_notice', true);

			load_plugin_textdomain('enable-media-replace', false, basename(dirname(EMR_ROOT_FILE)) . '/languages');

		// Load Submodules
			new Ajax();
		}

		public function filesystem()
		{
			 return new FileSystem();
		}

		public function uiHelper()
		{
			 return Uihelper::getInstance();
		}

		public function useFeature($name)
		{
				// If for some obscure reason, it's called earlier or out of admin, still load the features.
				if (count($this->features) === 0)
				{
					 $this->adminInit();
				}

			  switch($name)
				{
					 case 'background':
					 		$bool = $this->features['background'];
					 break;
           case 'remote_notice':
		           $bool = $this->features['remote_notice'];
           break;
					 default:
					 		$bool = false;
					 break;
				}
				return $bool;
		}

    public static function get()
    {
        if (is_null(self::$instance)) {
            self::$instance = new EnableMediaReplacePlugin();
        }

        $log = Log::getInstance();
        if (Log::debugIsActive()) {
            $uploaddir = wp_upload_dir(null, false, false);
            if (isset($uploaddir['basedir'])) {
                $log->setLogPath( trailingslashit($uploaddir['basedir']) . "emr_log");
            }
        }
        return self::$instance;
    }

    // Actions for EMR that always need to hook
    protected function nopriv_plugin_actions()
    {
        // shortcode
        add_shortcode('file_modified', array($this, 'get_modified_date'));
    }


    public function plugin_actions()
    {
        $this->plugin_path = plugin_dir_path(EMR_ROOT_FILE);
        //$this->plugin_url = plugin_dir_url(EMR_ROOT_FILE);

				// loads the dismiss hook.
				$notices = Notices::getInstance();

      // init plugin
        add_action('admin_menu', array($this,'menu'));
				add_action('submenu_file', array($this, 'hide_sub_menu'));

				add_action( 'current_screen', array($this, 'setScreen') ); // annoying workaround for notices in edit-attachment screen
        add_action('admin_enqueue_scripts', array($this,'admin_scripts'));


      // content filters
        add_filter('media_row_actions', array($this,'add_media_action'), 10, 2);
        add_action('attachment_submitbox_misc_actions', array($this,'admin_date_replaced_media_on_edit_media_screen'), 91);
      //add_filter('upload_mimes', array($this,'add_mime_types'), 1, 1);

      // notices

      // editors
        add_action('add_meta_boxes_attachment', array($this, 'add_meta_boxes'), 10, 2);
        add_filter('attachment_fields_to_edit', array($this, 'attachment_editor'), 10, 2);

      /** Just after an image is replaced, try to browser decache the images */
        if (isset($_GET['emr_replaced']) && intval($_GET['emr_replaced'] == 1)) {
            add_filter('wp_get_attachment_image_src', array($this, 'attempt_uncache_image'), 10, 4);

          // adds a metabox to list thumbnails. This is a cache reset hidden as feature.
          //add_action( 'add_meta_boxes', function () {  );
            add_filter('postbox_classes_attachment_emr-showthumbs-box', function ($classes) {
                $classes[] = 'closed';
                return $classes;
            });
        }
    }

  /**
   * Register this file in WordPress so we can call it with a ?page= GET var.
   * To suppress it in the menu we give it an empty menu title.
   */
    public function menu()
    {
			$title =  esc_html__("Replace media", "enable-media-replace");
			$title = (isset($_REQUEST['action']) && ($_REQUEST['action'] === 'emr_prepare_remove')) ? esc_html__("Remove background", "enable-media-replace") : $title;
        add_submenu_page('upload.php',$title, $title, 'upload_files', 'enable-media-replace/enable-media-replace.php', array($this, 'route'));

    }

		public function hide_sub_menu($submenu_file)
		{
			 global $plugin_page;
				// Select another submenu item to highlight (optional).
				if ( $plugin_page && $plugin_page == 'enable-media-replace/enable-media-replace.php' ) {
						$submenu_file = 'upload.php';
				}

				// Hide the submenu.

				remove_submenu_page( 'upload.php', 'enable-media-replace/enable-media-replace.php' );

				return $submenu_file;
		}


		public function setScreen()
		{
			 $screen = get_current_screen();

			 $notice_pages = array('attachment',  'media_page_enable-media-replace/enable-media-replace', 'upload' );
			 if ( in_array($screen->id, $notice_pages) &&	true === emr()->useFeature('remote_notice'))
			 {
				 RemoteNoticeController::getInstance(); // check for remote stuff
			 	 $notices = Notices::getInstance();
				 $notices->loadIcons(array(
						 'normal' => '<img class="emr-notice-icon" src="' . plugins_url('img/notices/slider.png', EMR_ROOT_FILE) . '">',
						 'success' => '<img class="emr-notice-icon" src="' . plugins_url('img/notices/robo-cool.png', EMR_ROOT_FILE) . '">',
						 'warning' => '<img class="emr-notice-icon" src="' . plugins_url('img/notices/robo-scared.png', EMR_ROOT_FILE) . '">',
						 'error' => '<img class="emr-notice-icon" src="' . plugins_url('img/notices/robo-scared.png', EMR_ROOT_FILE) . '">',
				 ));

				 add_action('admin_notices', array($notices, 'admin_notices')); // previous page / init time
			 }
		}

  /** Load EMR views based on request */
    public function route()
    {
        global $plugin_page;
        switch ($plugin_page) {
            case 'enable-media-replace/enable-media-replace.php':
                $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
                wp_enqueue_style('emr_style');
                wp_enqueue_script('jquery-ui-datepicker');
                wp_enqueue_style('jquery-ui-datepicker');
                wp_enqueue_script('emr_admin');

								$this->uiHelper()->featureNotice();

                if ($action == 'media_replace') {
                    if (array_key_exists("attachment_id", $_GET) && intval($_GET["attachment_id"]) > 0) {
                                wp_enqueue_script('emr_upsell');

											 $controller = \EnableMediaReplace\ViewController\ReplaceViewController::getInstance();
											 $controller->load();
//                       require_once($this->plugin_path . "views/popup.php"); // warning variables like $action be overwritten here.
                    }
                }
								elseif ($action == 'media_replace_upload') {

									  $controller = \EnableMediaReplace\ViewController\UploadViewController::getInstance();
										$controller->load();
                  //  require_once($this->plugin_path . 'views/upload.php');
								}
								elseif ('emr_prepare_remove' === $action && $this->useFeature('background')) {
//										$attachment_id = intval($_GET['attachment_id']);
//										$attachment    = get_post($attachment_id);
										//We're adding a timestamp to the image URL for cache busting

										wp_enqueue_script('emr_remove_bg');

										wp_enqueue_style('emr_style');
										wp_enqueue_style('emr-remove-background');
										wp_enqueue_script('emr_upsell');

										$controller = \EnableMediaReplace\ViewController\RemoveBackgroundViewController::getInstance();
										$controller->load();

									//	require_once($this->plugin_path . "views/prepare-remove-background.php");

								} elseif ('do_background_replace' === $action &&
												$this->useFeature('background')
											) {
												$controller = \EnableMediaReplace\ViewController\RemoveBackgroundViewController::getInstance();
												$controller->loadPost();

									//	require_once($this->plugin_path . 'views/do-replace-background.php');
								}
                else {

                    exit('Something went wrong loading page, please try again');
                }
                break;
        } // Route
    }

    public function getPluginURL($path = '')
    {
        return plugins_url($path, EMR_ROOT_FILE);
    }

		public function plugin_path($path = '')
		{
			$plugin_path = trailingslashit(plugin_dir_path(EMR_ROOT_FILE));
			if ( strlen( $path ) > 0 ) {
				$plugin_path .= $path;
			}
			 return $plugin_path;
		}

  /** register styles and scripts
  *
  * Nothing should ever by -enqueued- here, just registered.
  */
    public function admin_scripts()
    {
        if (is_rtl()) {
            wp_register_style('emr_style', plugins_url('css/admin.rtl.css', EMR_ROOT_FILE));
        } else {
            wp_register_style('emr_style', plugins_url('css/admin.css', EMR_ROOT_FILE));
        }

        wp_register_style('emr_edit-attachment', plugins_url('css/edit_attachment.css', EMR_ROOT_FILE));

				wp_register_style('emr-remove-background', plugins_url('css/remove_background.css', EMR_ROOT_FILE));

        $mimes = array_values(get_allowed_mime_types());

        wp_register_script('emr_admin', plugins_url('js/emr_admin.js', EMR_ROOT_FILE), array('jquery'), EMR_VERSION, true);
        $emr_options = array(
        'dateFormat' => $this->convertdate(get_option('date_format')),
        'maxfilesize' => wp_max_upload_size(),
        'allowed_mime' => $mimes,
        );

        wp_register_script('emr_upsell', plugins_url('js/upsell.js', EMR_ROOT_FILE), array('jquery'), EMR_VERSION, true);

        wp_localize_script('emr_upsell', 'emr_upsell', array(
                'ajax' => admin_url('admin-ajax.php'),
                'installing' => __('Installing ...', 'enable-media-replace'),

        ));

				$ts = time();
				$ajax_url = admin_url('admin-ajax.php');


        wp_register_script('emr_remove_bg', plugins_url('js/remove_bg.js', EMR_ROOT_FILE), array('jquery'), EMR_VERSION, true);
				wp_localize_script('emr_remove_bg', 'emrObject', array(
					'ajax_url' => $ajax_url,
					'nonce'    => wp_create_nonce('emr_remove_background')
				));


        if (Log::debugIsActive()) {
            $emr_options['is_debug'] = true;
        }

        wp_localize_script('emr_admin', 'emr_options', $emr_options);

				wp_register_script('emr_success', plugins_url('js/emr_success.js', EMR_ROOT_FILE), array(), EMR_VERSION, true);

				wp_localize_script('emr_success', 'emr_success_options', array(
					'timeout' => apply_filters('emr/success/timeout', 5),
				));
    }

  /** Utility function for the Jquery UI Datepicker */
    public function convertdate($sFormat)
    {
        switch ($sFormat) {
            //Predefined WP date formats
            case 'F j, Y':
                return( 'MM dd, yy' );
              break;
            case 'Y/m/d':
                return( 'yy/mm/dd' );
              break;
            case 'm/d/Y':
                return( 'mm/dd/yy' );
              break;
            case 'd/m/Y':
            default:
                return( 'dd/mm/yy' );
            break;
        }
    }

    public function checkImagePermission($post)
    {
			 if (! is_object($post))
			 {
				 return false;
			 }
			$post_id = $post->ID;
			$post_type = $post->post_type;
			$author_id = $post->post_author;

			if ($post_type !== 'attachment')
				return false;

			if (is_null($post_id) || intval($post_id) >! 0)
			{
				 return false;
			}

        if ($this->general_cap === false && $this->user_cap === false) {
            if (current_user_can('edit_post', $post_id)  === true) {
                            return true;
            }
        } elseif (current_user_can($this->general_cap)) {
            return true;
        } elseif (current_user_can($this->user_cap) && $author_id == get_current_user_id()) {
            return true;
        }

        return false;
    }

  /** Get the URL to the media replace page
  * @param $attach_id  The attachment ID to replace
  * @return Admin URL to the page.
  */
    protected function getMediaReplaceURL($attach_id)
    {
        $url = admin_url("upload.php");
        $url = add_query_arg(array(
        'page' => 'enable-media-replace/enable-media-replace.php',
        'action' => 'media_replace',
        'attachment_id' => $attach_id,
        ), $url);

        return $url;
    }

    protected function getRemoveBgURL($attach_id)
    {
        $url = admin_url("upload.php");
        $url = add_query_arg(array(
        'page' => 'enable-media-replace/enable-media-replace.php',
        'action' => 'emr_prepare_remove',
        'attachment_id' => $attach_id,
        ), $url);

        return $url;
    }

    public function add_meta_boxes($post)
    {
            // Because some plugins don't like to play by the rules.
        if (is_null($post) || ! is_object($post) ) {
              return false;
        }

        if (! $this->checkImagePermission($post)) {
            return;
        }

        add_meta_box('emr-replace-box', __('Replace Media', 'enable-media-replace'), array($this, 'replace_meta_box'), 'attachment', 'side', 'low');

        if (isset($_GET['emr_replaced']) && intval($_GET['emr_replaced'] == 1)) {
            add_meta_box('emr-showthumbs-box', __('Replaced Thumbnails Preview', 'enable-media-replace'), array($this, 'show_thumbs_box'), 'attachment', 'side', 'low');
        }
    }

    public function replace_meta_box($post)
    {

        //Replace media button
        $replace_url = $this->getMediaReplaceURL($post->ID);

        $replace_action = "media_replace";
        $replace_editurl = wp_nonce_url($replace_url, $replace_action);

        $replace_link = "href=\"$replace_editurl\"";

        echo "<p><a class='button-secondary' $replace_link>" . esc_html__("Upload a new file", "enable-media-replace") . "</a></p><p>" . esc_html__("To replace the current file, click the link and upload a replacement file.", "enable-media-replace") . "</p>";
        //Remove background button
        $removeBg_url = $this->getRemoveBgURL($post->ID);

        $removeBg_action = "emr_prepare_remove";
        $removeBg_editurl = wp_nonce_url($removeBg_url, $removeBg_action);

        $removeBg_link = "href=\"$removeBg_editurl\"";

				if ($this->uiHelper()->isBackgroundRemovable($post))
				{
        	echo "<p><a class='button-secondary' $removeBg_link>" . esc_html__("Remove background", "enable-media-replace") . "</a></p><p>" . esc_html__("To remove the background, click the link and select the options.", "enable-media-replace") . "</p>";
				}
    }

    public function show_thumbs_box($post)
    {
        if (! $this->checkImagePermission($post)) {
            return;
        }

        wp_enqueue_style('emr_edit-attachment');

        $meta = wp_get_attachment_metadata($post->ID);

        if (! isset($meta['sizes'])) {
            echo __('Thumbnails were not generated', 'enable-media-replace');
            return false;
        }

        if (function_exists('wp_get_original_image_url')) { // indicating WP 5.3+
            $source_url = wp_get_original_image_url($post->ID);
          // oldway will give -scaled in case of scaling.
            $source_url_oldway = wp_get_attachment_url($post->ID);

            if ($source_url !== $source_url_oldway) {
                echo "<div class='original previewwrapper'><img src='" . $source_url_oldway . "'><span class='label'>" . __('Original') . "</span></div>";
            }
        }


        foreach ($meta['sizes'] as $size => $data) {
            $display_size = ucfirst(str_replace("_", " ", $size));
            $img = wp_get_attachment_image_src($post->ID, $size);
            echo "<div class='$size previewwrapper'><img src='" . $img[0] . "'><span class='label'>$display_size</span></div>";
        }
    }

    public function attachment_editor($form_fields, $post)
    {
        $screen = null;

        if (! $this->checkImagePermission($post)) {
            return $form_fields;
        }

        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if (! is_null($screen) && $screen->id == 'attachment') { // hide on edit attachment screen.
                return $form_fields;
            }
        }

        $url = $this->getMediaReplaceURL($post->ID);
        $action = "media_replace";
        $editurl = wp_nonce_url($url, $action);

        $link = "href=\"$editurl\"";
        $form_fields["enable-media-replace"] = array(
              "label" => esc_html__("Replace media", "enable-media-replace"),
              "input" => "html",
              "html" => "<a class='button-secondary' $link>" . esc_html__("Upload a new file", "enable-media-replace") . "</a>", "helps" => esc_html__("To replace the current file, click the link and upload a replacement file.", "enable-media-replace")
            );

				if ($this->uiHelper()->isBackgroundRemovable($post))
				{
					$link = $this->getRemoveBgURL($post->ID);
					$link = "href='" . wp_nonce_url($link, 'emr_prepare_remove') . "'";
					$form_fields["emr-remove-background"] = array(
	              "label" => esc_html__("Remove background", "enable-media-replace"),
	              "input" => "html",
	              "html" => "<a class='button-secondary' $link>" . esc_html__("Remove background", "enable-media-replace") . "</a>", "helps" => esc_html__("To remove the background, click the link.", "enable-media-replace")
	            );
				}
        return $form_fields;
    }

  /**
   * @param array $mime_types
   * @return array
   */
     /* Off, no clue why this is here.
  public function add_mime_types($mime_types)
  {
    $mime_types['dat'] = 'text/plain';     // Adding .dat extension
    return $mime_types;
  }
*/
  /**
   * Function called by filter 'media_row_actions'
   * Enables linking to EMR straight from the media library
  */
    public function add_media_action($actions, $post)
    {

        if (! $this->checkImagePermission($post)) {
            return $actions;
        }

        $media_replace_editurl = $this->getMediaReplaceURL($post->ID);
        $media_replace_action = "media_replace";
     //   $media_replace_editurl = wp_nonce_url($url, $media_replace_action);
        $url = $this->getRemoveBgURL($post->ID);
        $background_remove_action = "emr_prepare_remove";
        $background_remove_editurl = wp_nonce_url($url, $background_remove_action);

      /* See above, not needed.
      if (FORCE_SSL_ADMIN) {
        $editurl = str_replace("http:", "https:", $editurl);
      } */
        $media_replace_link = "href=\"$media_replace_editurl\"";
        $background_remove_link = "href=\"$background_remove_editurl\"";

        $newaction['media_replace'] = '<a ' . $media_replace_link . ' aria-label="' . esc_attr__("Replace media", "enable-media-replace") . '" rel="permalink">' . esc_html__("Replace media", "enable-media-replace") . '</a>';

				if ($this->uiHelper()->isBackgroundRemovable($post))
				{
	        $newaction['remove_background'] = '<a ' . $background_remove_link . ' aria-label="' . esc_attr__("Remove  background", "enable-media-replace") . '" rel="permalink">' . esc_html__("Remove  background", "enable-media-replace") . '</a>';

				}
				return array_merge($actions, $newaction);
    }



  /** Outputs the replaced date of the media on the edit_attachment screen
  *
  *   @param $post Obj Post Object
  */
    function admin_date_replaced_media_on_edit_media_screen($post)
    {

      // Fallback for before version 4.9, doens't pass post.
        if (! is_object($post)) {
            global $post;
        }

        if (! is_object($post)) { // try to global, if it doesn't work - return.
            return false;
        }

        $post_id = $post->ID;
        if ($post->post_modified !== $post->post_date) {
            $modified = date_i18n(__('M j, Y @ H:i'), strtotime($post->post_modified));
            ?>
        <div class="misc-pub-section curtime">
            <span id="timestamp"><?php echo esc_html__('Revised', 'enable-media-replace'); ?>: <b><?php echo $modified; ?></b></span>
        </div>

            <?php
        }
        $author_id = get_post_meta($post_id, '_emr_replace_author', true);

        if ($author_id) {
            $display_name = get_the_author_meta('display_name', $author_id);
            ?>
      <div class="misc-pub-section replace_author">
        <span><?php echo esc_html__('Replaced By', 'enable-media-replace'); ?>: <b><?php echo $display_name; ?></b></span>
      </div>
            <?php
        }
    }

  /** When an image is just replaced, it can stuck in the browser cache making a look like it was not replaced. Try
  * undo that effect by adding a timestamp to the query string */
    public function attempt_uncache_image($image, $attachment_id, $size, $icon)
    {
        if ($image === false) {
            return $image;
        }

        // array with image src on 0
        $image[0] = add_query_arg('time', time(), $image[0]);
        return $image;
    }

  /**
   * Shorttag function to show the media file modification date/time.
   * @param array shorttag attributes
   * @return string content / replacement shorttag
   * @todo Note this returns the wrong date, ie. server date not corrected for timezone. Function could be removed altogether, not sure about purpose.
   */
    public function get_modified_date($atts)
    {
        $id=0;
        $format= '';

        extract(shortcode_atts(array(
        'id' => '',
        'format' => get_option('date_format') . " " . get_option('time_format'),
        ), $atts));

        if ($id == '') {
            return false;
        }

        // Get path to file
        $current_file = get_attached_file($id);

        if (! file_exists($current_file)) {
            return false;
        }

      // Get file modification time
        $filetime = filemtime($current_file);

        if (false !== $filetime) {
            // do date conversion
            return date($format, $filetime);
        }

        return false;
    }
} // class
