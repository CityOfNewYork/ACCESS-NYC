<?php
namespace EnableMediaReplace;

// Does what a plugin does.
class EnableMediaReplacePlugin
{

  protected $plugin_path;

  public function __construct()
  {
     $this->plugin_actions(); // init


  }

  public function plugin_actions()
  {
    $this->plugin_path = plugin_dir_path(EMR_ROOT_FILE);
    $this->plugin_url = plugin_dir_url(EMR_ROOT_FILE);

    // init plugin
    add_action('admin_menu', array($this,'menu'));
    add_action('admin_init', array($this,'init'));
    add_action('admin_enqueue_scripts', array($this,'admin_scripts'));

    // content filters
    add_filter('attachment_fields_to_edit', array($this, 'attachment_editor'), 10, 2);
    add_filter('media_row_actions', array($this,'add_media_action'), 10, 2);
    add_action('attachment_submitbox_misc_actions', array($this,'admin_date_replaced_media_on_edit_media_screen'), 91 );
    add_filter('upload_mimes', array($this,'add_mime_types'), 1, 1);

    // notices
    add_action('admin_notices', array($this,'display_notices'));
    add_action('network_admin_notices', array($this,'display_network_notices'));
    add_action('wp_ajax_emr_dismiss_notices', array($this,'dismiss_notices'));

    // shortcode
    add_shortcode('file_modified', array($this, 'get_modified_date'));

  }

  /**
   * Register this file in WordPress so we can call it with a ?page= GET var.
   * To suppress it in the menu we give it an empty menu title.
   */
  public function menu()
  {
  	add_submenu_page(null, esc_html__("Replace media", "enable-media-replace"), esc_html__("Replace media", "enable-media-replace"), 'upload_files', 'enable-media-replace/enable-media-replace', array($this, 'route'));
  }

  /**
   * Initialize this plugin. Called by 'admin_init' hook.
   * Only languages files needs loading during init.
   */
  public function init()
  {
    load_plugin_textdomain( 'enable-media-replace', false, basename(dirname(EMR_ROOT_FILE) ) . '/languages' );
  }

  /** Load EMR views based on request */
  public function route()
  {
    global $plugin_page;
    switch($plugin_page)
    {
        case 'enable-media-replace/enable-media-replace.php':
            $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
            wp_enqueue_style('emr_style');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-datepicker');
            wp_enqueue_script('emr_admin');

            if (! check_admin_referer( $action, '_wpnonce') )
            {
              die('Invalid Nonce');
            }

            // @todo Later this should be move to it's own controller, and built view from there.
            if ( $action == 'media_replace' ) {
              if ( array_key_exists("attachment_id", $_GET) && intval($_GET["attachment_id"]) > 0) {
                require_once($this->plugin_path . "views/popup.php"); // warning variables like $action be overwritten here.
              }
            }
            elseif ( $action == 'media_replace_upload' ) {
              require_once($this->plugin_path . 'views/upload.php');
            }
            else {
              exit('Something went wrong loading page, please try again');
            }

        break;
    }

  }

  /** register styles and scripts
  *
  * Nothing should ever by -enqueued- here, just registered.
  */
  public function admin_scripts()
  {
    if (is_rtl())
    {
      wp_register_style('emr_style', plugins_url('css/admin.rtl.css', EMR_ROOT_FILE) );
    }
    else {
      wp_register_style('emr_style', plugins_url('css/admin.css', EMR_ROOT_FILE) );
    }

    wp_register_script('emr_admin', plugins_url('js/emr_admin.js', EMR_ROOT_FILE), array('jquery'), false, true );
    wp_localize_script('emr_admin', 'emr_options', array('dateFormat' => $this->convertdate(get_option( 'date_format' ))));

  }

  /** Utility function for the Jquery UI Datepicker */
  function convertdate( $sFormat ) {
      switch( $sFormat ) {
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

  /** Get the URL to the media replace page
  * @param $attach_id  The attachment ID to replace
  * @return Admin URL to the page.
  */
  protected function getMediaReplaceURL($attach_id)
  {
    $url = admin_url( "upload.php");
    $url = add_query_arg(array(
        'page' => 'enable-media-replace/enable-media-replace.php',
        'action' => 'media_replace',
        'attachment_id' => $attach_id,
    ), $url);

    return $url;

  }

  /**
   * Add some new fields to the attachment edit panel.
   * @param array form fields edit panel
   * @return array form fields with enable-media-replace fields added
   */
  public function attachment_editor($form_fields, $post)
  {
      $url = $this->getMediaReplaceURL($post->ID);

    	$action = "media_replace";
      $editurl = wp_nonce_url( $url, $action );

    	/* Unneeded - admin_url already checks for force_ssl_admin ( in set_scheme function )
    	if (FORCE_SSL_ADMIN) {
    		$editurl = str_replace("http:", "https:", $editurl);
    	} */
    	$link = "href=\"$editurl\"";
    	$form_fields["enable-media-replace"] = array(
              "label" => esc_html__("Replace media", "enable-media-replace"),
              "input" => "html",
              "html" => "<p><a class='button-secondary'$link>" . esc_html__("Upload a new file", "enable-media-replace") . "</a></p>", "helps" => esc_html__("To replace the current file, click the link and upload a replacement.", "enable-media-replace")
            );

    	return $form_fields;
  }

  /**
   * @param array $mime_types
   * @return array
   */

  public function add_mime_types($mime_types)
  {
    $mime_types['dat'] = 'text/plain';     // Adding .dat extension
    return $mime_types;
  }

  /**
   * Function called by filter 'media_row_actions'
   * Enables linking to EMR straight from the media library
  */
  public function add_media_action( $actions, $post) {
  	$url = $this->getMediaReplaceURL($post->ID);
  	$action = "media_replace";
    	$editurl = wp_nonce_url( $url, $action );

    /* See above, not needed.
  	if (FORCE_SSL_ADMIN) {
  		$editurl = str_replace("http:", "https:", $editurl);
  	} */
  	$link = "href=\"$editurl\"";

  	$newaction['adddata'] = '<a ' . $link . ' aria-label="' . esc_html__("Replace media", "enable-media-replace") . '" rel="permalink">' . esc_html__("Replace media", "enable-media-replace") . '</a>';
  	return array_merge($actions,$newaction);
  }


  public function display_notices() {
  	$current_screen = get_current_screen();

  	$crtScreen = function_exists("get_current_screen") ? get_current_screen() : (object)array("base" => false);

  	if(current_user_can( 'activate_plugins' ) && !get_option( 'emr_news') && !is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php')
  	   && ($crtScreen->base == "upload" || $crtScreen->base == "plugins")
          //for network installed plugins, don't display the message on subsites.
         && !(function_exists('is_multisite') && is_multisite() && is_plugin_active_for_network('enable-media-replace/enable-media-replace.php') && !is_main_site()))
  	{
  		require_once($this->plugin_path . '/views/notice.php');
  	}
  }

  public function display_network_notices() {
      if(current_user_can( 'activate_plugins' ) && !get_option( 'emr_news') && !is_plugin_active_for_network('shortpixel-image-optimiser/wp-shortpixel.php')) {
          require_once( str_replace("enable-media-replace.php", "notice.php", __FILE__) );
      }
  }

  /* Ajax function to dismiss notice */
  public function dismiss_notices() {
  	update_option( 'emr_news', true);
  	exit(json_encode(array("Status" => 0)));
  }

  /** Outputs the replaced date of the media on the edit_attachment screen
  *
  *   @param $post Obj Post Object
  */
  function admin_date_replaced_media_on_edit_media_screen($post) {
  	$post_id = $post->ID;

  	if ( $post->post_modified == $post->post_date ) {
  		return;
  	}

  	$modified = date_i18n( __( 'M j, Y @ H:i' ) , strtotime( $post->post_modified ) );

  	?>
  	<div class="misc-pub-section curtime">
  		<span id="timestamp"><?php echo esc_html__( 'Revised', 'enable-media-replace' ); ?>: <b><?php echo $modified; ?></b></span>
  	</div>
  	<?php
  }

  /**
   * Shorttag function to show the media file modification date/time.
   * @param array shorttag attributes
   * @return string content / replacement shorttag
   * @todo Note this returns the wrong date, ie. server date not corrected for timezone. Function could be removed altogether, not sure about purpose.
   */
  public function get_modified_date($atts) {
  	$id=0;
  	$format= '';

  	extract(shortcode_atts(array(
  		'id' => '',
  		'format' => get_option('date_format') . " " . get_option('time_format'),
  	), $atts));

  	if ($id == '') return false;

      // Get path to file
  	$current_file = get_attached_file($id);

  	if ( ! file_exists( $current_file ) ) {
  		return false;
  	}

  	// Get file modification time
  	$filetime = filemtime($current_file);

  	if ( false !== $filetime ) {
  		// do date conversion
  		return date( $format, $filetime );
  	}

  	return false;
  }



} // class
