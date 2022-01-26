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

class Wp_Bitly_Api {
	
	/**
	 * Initialize 
	 *
	 * @since    2.6.0
	 */
	public function __construct() {


	}

    /**
	 * Retrieve the requested API endpoint.
	 *
	 * @since 2.0
	 * @param   string $api_call Which endpoint do we need?
	 * @return  string Returns the URL for our requested API endpoint
	 */
	public function wpbitly_api($api_call)
	{

	    $api_links = array(
	        'shorten' => 'shorten',
	        'expand' => 'expand',
	        'link/clicks' => 'bitlinks/%1$s/clicks',
	        'link/refer' => 'link/referring_domains?link=%1$s',
	        'user' => 'user',
                'bsds' => 'bsds',
                'groups' => 'groups',
                'organizations' => 'organizations'
	    );

	    if (!array_key_exists($api_call, $api_links)) {
	        trigger_error(__('WP Bitly Error: No such API endpoint.', 'wp-bitly'));
	    }

	    return WPBITLY_BITLY_API . $api_links[ $api_call ];
	}
	
	/**
	 * WP Bitly wrapper for wp_remote_get that verifies a successful response.
	 *
	 * @since   2.1
	 * @param   string $url The API endpoint we're contacting
         * @param   string $token The API token
	 * @return  bool|array False on failure, array on success
	 */

	public function wpbitly_get($url,$token)
	{

	    $the = wp_remote_get($url, array(
                    'timeout' => '30',
                    'headers' => array("Authorization" => "Bearer $token")
                ));

	    if (is_array($the) && '200' == $the['response']['code']) {
	        return json_decode($the['body'], true);
	    }

	    return false;
	}
        
	/**
	 * WP Bitly wrapper for wp_remote_post that verifies a successful response.
	 *
	 * @since   2.1
	 * @param   string $url The API endpoint we're contacting
         * @param   string $token The API token
         * @param   array $params The params sent to the API endpoint
	 * @return  bool|array False on failure, array on success
	 */

	public function wpbitly_post($url, $token, $params = array())
	{

	    $the = wp_remote_post($url, array(
                    'timeout' => '30',
                    'headers' => array("Authorization" => "Bearer $token", "Content-Type" => "application/json"),
                    'method'  => 'POST',
                    'body' => json_encode($params)
                ));

	    if (is_array($the) && '200' == $the['response']['code']) {
	        return json_decode($the['body'], true);
	    }

	    return false;
	}

}
