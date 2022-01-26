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

class Wp_Bitly_Options {
	
	/**
     * @var array The WP Bitly configuration is stored in here
     */
    private $_options = array();

	/**
	 * Initialize 
	 *
	 * @since    2.6.0
	 */
	public function __construct() {
		$this->populate_options();
	}

	/**
     * Populate WPBitly::$options with the configuration settings.
     *
     * @since 2.0
     */
    public function populate_options()
    {

        $defaults = apply_filters('wpbitly_default_options', array(
            'version' => WPBITLY_VERSION,
            'oauth_token' => '',
            'oauth_login' => '',
            'post_types' => array('post', 'page'),
            'default_org' => '',
            'default_domain' => '',
            'default_group' => '',
            'debug' => false,
        ));

        $this->_options = wp_parse_args(get_option(WPBITLY_OPTIONS), $defaults);

    }

	
    /**
     * Save all current options to the database
     *
     * @since 2.4.0
     */
    private function save_options()
    {
        update_option('wpbitly-options', $this->_options);
    }

    /**
     * Access to our Wp_Bitly_Options::$_options array.
     *
     * @since 2.2.5
     * @param  $option string The name of the option we need to retrieve
     * @return         mixed  Returns the option
     */
    public function get_option($option)
    {
        if (!isset($this->_options[ $option ])) {
            trigger_error(sprintf(WPBITLY_ERROR, ' <code>' . $option . '</code>'), E_USER_ERROR);
        }

        return $this->_options[ $option ];
    }

    /**
     * Sets a single Wp_Bitly_Options::$_options value on the fly
     *
     * @since 2.2.5
     * @param $option string The name of the option we're setting
     * @param $value  mixed  The value, could be bool, string, array
     */
    public function set_option($option, $value)
    {
        if (!isset($this->_options[ $option ])) {
            trigger_error(sprintf(WPBITLY_ERROR, ' <code>' . $option . '</code>'), E_USER_ERROR);
        }

        $this->_options[ $option ] = $value;
        $this->save_options();
    }

    /**
     * Validate user settings.
     *
     * @since   2.0
     * @param   array $input WordPress sanitized data array
     * @return  array           WP Bitly sanitized data
     */
    public function validate_settings($input)
    {    	
        $input['debug'] = ('1' == $input['debug']) ? true : false;

        if (!isset($input['post_types'])) {
            $input['post_types'] = array();
        } else {
            $post_types = apply_filters('wpbitly_allowed_post_types', get_post_types(array('public' => true)));

            foreach ($input['post_types'] as $key => $pt) {
                if (!in_array($pt, $post_types)) {
                    unset($input['post_types'][$key]);
                }
            }

        }

        return $input;

    }
	

}
