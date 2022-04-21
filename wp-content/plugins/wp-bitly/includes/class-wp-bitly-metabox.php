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

        function _ceiling($number, $significance = 1)
        {
            return (is_numeric($number) && is_numeric($significance)) ? (ceil($number / $significance) * $significance) : false;
        }
        
        $shortlink = $args['args'][0];
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; // used in the display partial
        //remove leading https:// for urls
        if($shortlink){
            $shortlink_for_url=preg_replace('#^https?://#', '', $shortlink);

            

            echo '<input type="hidden" id="shortlink" value="'.$shortlink.'" />';


            // Retrieve lifetime total
            $url = sprintf($this->wp_bitly_api->wpbitly_api('link/clicks')."/summary?unit=month&units=-1", $shortlink_for_url);

            $response = $this->wp_bitly_api->wpbitly_get($url,$this->wp_bitly_options->get_option('oauth_token'));
            if (is_array($response)) {
                $totalclicks = $response['total_clicks'];
            }

            // Retrieve last 7 days of click information (starts at current date and runs back)
            $url = sprintf($this->wp_bitly_api->wpbitly_api('link/clicks') . '?units=7&unit=day', $shortlink_for_url);
            $response =  $this->wp_bitly_api->wpbitly_get($url,$this->wp_bitly_options->get_option('oauth_token'));

            if (is_array($response)) {
                $clicks = $response['link_clicks'];
            }

            // Build strings for use in Chartist
            $labels_arr = array();
            $data_arr = array();

            foreach (array_reverse($clicks) as $click) {
                $labels_arr[] = date('m/d', strtotime($click['date']));
                $data_arr[] = $click['clicks'];
            }

            $highest_clicks = max($data_arr);

            $labels_js = '"' . implode('","', $labels_arr) . '"';
            $data_js = implode(',', $data_arr);

            if ($highest_clicks < 10) {
                $max = 10;
            } else {
                $max = _ceiling($highest_clicks, str_pad('100', strlen((string)$highest_clicks), '0'));
            }

            // If the current highest clicks is less than 50, _ceiling will round up to 100. Better to not exceed 50.
            // TODO: Should this round 2020 to 2500 instead of 3000? 110 to 150 instead of 200? Etc.
            $p = ($highest_clicks / $max) * 100;
            if ($p < 50) {
                $max = $max / 2;
            }      


            echo '<label class="screen-reader-text">' . __('WP Bitly Statistics &amp; Administration', 'wp-bitly') . '</label>';

            if (isset($totalclicks) && isset($clicks)) {

                echo '<div class="wpbitly-clicks">';
                echo '<p>' . __('Clicks Today', 'wp-bitly') . ' <span>' . number_format($clicks[0]['clicks']) . '</span></p>';
                echo '<p>' . __('Clicks Over Time', 'wp-bitly') . ' <span>' . number_format($totalclicks) . '</span></p>';
                echo '</div>';

                require(WPBITLY_DIR . '/admin/partials/wp-bitly-admin-metabox.php');


            } else {

                echo '<p class="error">' . __('There was a problem retrieving information about your link. There may be no statistics yet.', 'wp-bitly') . '</p>';
                require(WPBITLY_DIR . '/admin/partials/wp-bitly-admin-metabox-regenerate.php');
            }
        }else{
            if("publish" == $post->post_status){
                require(WPBITLY_DIR . '/admin/partials/wp-bitly-admin-metabox-regenerate.php');
            } else {
                echo '<label class="screen-reader-text">WP Bitly Statistics &amp; Administration</label>';
                echo '<div class="wpbitly-clicks" style="margin:1em;"><p>Once this post is published, you will see click performance.</p></div>';
            }
        }

    }
}
