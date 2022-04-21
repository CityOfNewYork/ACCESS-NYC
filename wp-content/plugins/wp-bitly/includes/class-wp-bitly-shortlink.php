<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       https://watermelonwebworks.com
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */

class Wp_Bitly_Shortlink {

    /**
     * The auth class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class  $wp_bitly_auth
     */
    protected $wp_bitly_auth;

    /**
     * The options class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_options
     */
    protected $wp_bitly_options;

    /**
     * The logger class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class wp_bitly_logger
     */
    protected $wp_bitly_logger;

    /**
     * The api class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_api
     */
    protected $wp_bitly_api;

	/**
	 * Initialize 
	 *
	 * @since    2.6.0
	 */
	public function __construct() {
		$this->wp_bitly_auth = new Wp_Bitly_Auth(); 
        $this->wp_bitly_options = new Wp_Bitly_Options(); 
        $this->wp_bitly_logger = new Wp_Bitly_Logger(); 
        $this->wp_bitly_api = new Wp_Bitly_Api(); 
	}	


	/**
	 * Generates the shortlink for the post specified by $post_id.
	 *
	 * @since   0.1
	 * @param   int $post_id Identifies the post being shortened
	 * @param   bool $bypass True bypasses the link expand API check
	 * @return  bool|string  Returns the shortlink on success
	 */

	public function wpbitly_generate_shortlink($post_id, $bypass = false)
	{

	    // Token hasn't been verified, bail
	    if (!$this->wp_bitly_auth->isAuthorized()) {
	        return false;
	    }

	    // Verify this is a post we want to generate short links for
	    if (!in_array(get_post_status($post_id), array('publish', 'future', 'private'))) {
	        return false;
	    }

	    // We made it this far? Let's get a shortlink
	    $permalink = get_permalink($post_id);
	    $shortlink = get_post_meta($post_id, '_wpbitly', true);
	    $token = $this->wp_bitly_options->get_option('oauth_token');
            $default_domain = $this->wp_bitly_options->get_option('default_domain');
            $default_group = $this->wp_bitly_options->get_option('default_group');

	    if (!empty($shortlink) && !$bypass) {
	        $url = $this->wp_bitly_api->wpbitly_api('expand');
                $data = array("bitlink_id" => $shortlink);
	        $response = $this->wp_bitly_api->wpbitly_post($url,$token,$data);

	        $this->wp_bitly_logger->wpbitly_debug_log($response, '/expand/');

	        if (is_array($response) && $permalink == $response['long_url']) {
	            update_post_meta($post_id, '_wpbitly', $shortlink);
	            return $shortlink;
	        }
	    }

	    $url = $this->wp_bitly_api->wpbitly_api('shorten');
            $options = array("long_url" => $permalink);
            if($default_domain){
                $options['domain']=$default_domain;
            }
            if($default_group){
                $options['group_guid']=$default_group;
            }
            
	    $response = $this->wp_bitly_api->wpbitly_post($url,$token,$options);

	    $this->wp_bitly_logger->wpbitly_debug_log($response, '/shorten/');

	    if (is_array($response)) {
	        $shortlink = $response['link'];
	        update_post_meta($post_id, '_wpbitly', $shortlink);
	    }

	    return ($shortlink) ? $shortlink : false;
	}

	/**
	 * Short circuits the `pre_get_shortlink` filter.
	 *
	 * @since   0.1
	 * @param   bool $original False if no shortlink generated
	 * @param   int $post_id Current $post->ID, or 0 for the current post
	 * @return  string|mixed A shortlink if generated, $original if not
	 */
	public function wpbitly_get_shortlink($original, $post, $force = false)
	{

		// Avoid creating shortlinks during bulk edit
		if( isset( $_GET['bulk_edit'] ) ) return;

	    // Avoid creating shortlinks during an autosave
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
	        return;
	    }

		if (is_object($post)) {
			$post_id = $post->ID;
		} else if (is_integer($post)) {
			$post_id = $post;
		} else {
			$post_id = 0;
		}
	    
	    $shortlink = false;

	    // or for revisions
	    if (wp_is_post_revision($post_id)) {
	        return;
	    }

	    if (0 == $post_id) {
	        $post = get_post();
	        if (is_object($post) && !empty($post->ID)) {
	            $post_id = $post->ID;
	        }
	    }

	    if ($post_id) {
	        $shortlink = get_post_meta($post_id, '_wpbitly', true);

	        if (!$shortlink && (in_array(get_post_type($post_id), $this->wp_bitly_options->get_option('post_types')) || $force)) {
                    
	            $shortlink = $this->wpbitly_generate_shortlink($post_id);
	        }
	    }

	    return ($shortlink) ? $shortlink : $original;
	}

	/**
	 * Register the shortcode
	 *
	 * @since   2.6.0
	 * @param   array $atts Default shortcode attributes
	 */
	public function wpbitly_register_shortlink() {
		add_shortcode('wpbitly', array($this,'wpbitly_shortlink'));
	}

	/**
	 * This can be used as a direct php call within a theme or another plugin. It also handles the [wp_bitly] shortcode.
	 *
	 * @since   0.1
	 * @param   array $atts Default shortcode attributes
	 */
	public function wpbitly_shortlink($atts = array())
	{


	    $output = '';

	    $post = get_post();
	    $post_id = (is_object($post) && !empty($post->ID)) ? $post->ID : '';

	    $defaults = array(
	        'text' => '',
	        'title' => '',
	        'before' => '',
	        'after' => '',
	        'post_id' => $post_id
	    );

	    extract(shortcode_atts($defaults, $atts));
	    if (!$post_id) {
	        return $output;
	    }

	    $permalink = get_permalink($post_id);
	    $shortlink = $this->wpbitly_get_shortlink($permalink, $post_id, true);

	    if (empty($text)) {
	        $text = $shortlink;
	    }

	    if (empty($title)) {
	        $title = the_title_attribute(array(
	            'echo' => false
	        ));
	    }

	    if (!empty($shortlink)) {
	        $output = apply_filters('the_shortlink', sprintf('<a rel="shortlink" href="%s" title="%s">%s</a>', esc_url($shortlink), $title, $text), $shortlink, $text, $title);
	        $output = $before . $output . $after;
	    }

	    return $output;
	}

	

}
