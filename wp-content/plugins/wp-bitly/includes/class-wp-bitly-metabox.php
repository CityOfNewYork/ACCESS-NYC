<?php

/**
 * Manage options for the plugin
 *
 * @link       https://watermelonwebworks.com
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */

class Wp_Bitly_Metabox {

    /**
     * The api class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_options
     */
    protected $wp_bitly_api;

    /**
     * The options class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_options
     */
    protected $wp_bitly_options;

	/**
	 * Initialize 
	 *
	 * @since    2.6.0
	 */
	public function __construct() {
        $this->wp_bitly_options = new Wp_Bitly_Options();
        $this->wp_bitly_api = new Wp_Bitly_Api();
	}

	
	/**
	 * Register the metaboxes.
	 *
	 * @since    2.6.0
	 */
	
	public function register_metaboxes() {

            $post_types = apply_filters('wpbitly_allowed_post_types', get_post_types(array('public' => true)));
            foreach($post_types as $type){
                add_action('add_meta_boxes_'.$type, array($this, 'add_metaboxes'));
            }
		
	}


	 /**
     * Add the Link Administration and Statistics metabox to any post with a shortlink.
     *
     * @since   2.0
     * @param   object $post WordPress $post object
     */
    public function add_metaboxes($post)
    {   	

        $shortlink = get_post_meta($post->ID, '_wpbitly', true);

        add_meta_box('wpbitly-meta', __('WP Bitly', 'wp-bitly'), array(
            $this,
            'display_metabox'
        ), $post->post_type, 'side', 'default', array($shortlink));
    }


    /**
     * Handles the display of the metabox. Currently uses the Chartist library for displaying the past 7 days of
     * link activity. Other potential information includes referrers and encoders. Eventually this information
     * might open in a larger modal to display more accurately.
     *
     * @since   2.0
     * @param   object $post WordPress $post object
     * @param   array $args The post shortlink
     */
    public function display_metabox($post, $args)
    {
        // 2024-05-02: SC removed this code for ACCESS, because this functionality is not used

    }
}
