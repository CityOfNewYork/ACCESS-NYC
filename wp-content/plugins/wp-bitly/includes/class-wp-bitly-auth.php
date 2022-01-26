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

class Wp_Bitly_Auth {


    /**
     * The logger class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class wp_bitly_logger
     */
    protected $wp_bitly_logger;


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
	public function __construct() 
    {

        $this->wp_bitly_logger = new Wp_Bitly_Logger(); 
        $this->wp_bitly_options = new Wp_Bitly_Options(); 

        add_action( 'wp_ajax_wpbitly_oauth_get_token', array( $this, 'get_token' ) );
        add_action( 'wp_ajax_wpbitly_oauth_disconnect', array( $this, 'disconnect' ) );

	}
	

	 /**
     * Used to short circuit any shortlink functions if we haven't authenticated to Bitly
     *
     * @since 2.4.0
     * @return bool
     */
    public function isAuthorized()
    {
        return get_option(WPBITLY_AUTHORIZED, false);
    }


    /**
     * @param bool $auth
     */
    public function authorize($auth = true)
    {
        if ($auth != true) {
            $auth = false;
        }

        update_option(WPBITLY_AUTHORIZED, $auth);
    }
	
	 /**
     * Ajax callback function to disconnect from bitly
     *
     * @since 2.6.0
     */
    public function disconnect() 
    {

        $this->wp_bitly_logger->wpbitly_debug_log('', 'Disconnecting (Ajax)');
        $this->wp_bitly_options->set_option('oauth_token', '');
        $this->wp_bitly_options->set_option('oauth_login', '');

        $this->authorize(false);

        echo json_encode( ['status' => 'disconnected'] );
        exit;
    }
	
	 /**
     * Ajax callback function to retrieve Bitly Access Token
     *
     * @since 2.6.0
     */
    public function get_token() 
    {
        if( !isset( $_POST['code'] ) || !$_POST['code'] ) {
            $response = array(
                'status' => 'error',
                'message' => 'Failed to retrieve authorization code.',
            );
            echo json_encode( $response );
            exit;
        }
        
        $code = sanitize_text_field($_POST['code']);

        $param_arr = array(
            'client_id' => WPBITLY_OAUTH_CLIENT_ID,
            'code' => $code,
            'redirect_uri' => WPBITLY_OAUTH_REDIRECT_URI,
        );
        
        $params = urldecode( http_build_query( $param_arr ) );
        $url = str_replace('v4/', '', WPBITLY_BITLY_API) . 'oauth/access_token?' . $params;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "Content-Length: 0",
                "Accept: */*",
                "Accept-Encoding: gzip, deflate, br",
                "Connection: keep-alive",
                "Cache-Control: no-cache",
            ),
        ));

        $curl_response = curl_exec($curl);

        curl_close($curl);

        $this->wp_bitly_logger->wpbitly_debug_log( $curl_response, 'class-wp-bitly-auth.php: Raw curl response' );

        if( is_array( json_decode( $curl_response ) ) ) {
            $curl_response = json_decode( $curl_response );
        } else {
            $curl_data = explode( '&', $curl_response );
            $curl_response = array();
            foreach( $curl_data as $curl_item ) {
                $curl_item = explode( '=', $curl_item );
                $curl_response[ $curl_item[0] ] = $curl_item[1];
            }
        }

        $this->wp_bitly_logger->wpbitly_debug_log( $curl_response, 'class-wp-bitly-auth.php: Processed curl response' );

        $access_token = isset( $curl_response['access_token'] ) ? sanitize_text_field($curl_response['access_token']) : NULL;
        $login = isset( $curl_response['login'] ) ? sanitize_text_field($curl_response['login']) : NULL;

        if( !$access_token ) {
            $response = array(
                'status' => 'error',
                'message' => 'Failed to retrieve access token.',
            );
            echo json_encode( $response );
            exit;
        }

        if( !$login ) {
            $response = array(
                'status' => 'error',
                'message' => 'Failed to retrieve login.',
            );
            echo json_encode( $response );
            exit;
        }

        // Sanitize values
        $access_token = preg_replace('/[^0-9a-z]/', '', $access_token);
        $login = preg_replace('/[^0-9a-z_-]/', '', $login);

        $this->wp_bitly_options->set_option('oauth_token', $access_token);
        $this->wp_bitly_options->set_option('oauth_login', $login);
        $this->authorize( true );

        $response = array(
            'status' => 'success',
            'message' => 'Got the access token.',
            'token' => $access_token,
        );
        echo json_encode( $response );
        exit;
    }

}
