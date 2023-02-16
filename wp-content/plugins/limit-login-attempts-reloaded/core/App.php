<?php

class LLAR_App {

	/**
	 * @var null|string
	 */
	private $id = null;

	/**
	 * @var mixed|string
	 */
	private $endpoint = '';

	/**
	 * @var array
	 */
	private $config = array();

	/**
	 * @var array
	 */
	private $login_errors = array();

	/**
	 * @var null
	 */
	public $last_response_code = null;

	/**
	 * LLAR_App constructor.
	 * @param array $config
	 */
	public function __construct( array $config ) {

		if( empty( $config ) ) {
			return false;
		}

		$this->id = 'app_' . $config['id'];
		$this->api = $config['api'];
		$this->config = $config;
	}

	/**
	 * @param $error
	 * @return bool
	 */
	public function add_error( $error ) {

		if( !$error ) return false;

		$this->login_errors[] = $error;
	}

	/**
	 * @return array
	 */
	public function get_errors() {

		return $this->login_errors;
	}

	/**
	 * @return null|string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @return array
	 */
	public function get_config() {
		return $this->config;
	}

	/**
	 * @param $link
	 * @return false[]
	 */
	public static function setup( $link ) {

		$return = array(
			'success' => false,
		);

		if( empty( $link ) ) {

			return $return;
		}

		$link = 'https://' . $link;

		$domain = parse_url( home_url( '/' ) );
		$link = add_query_arg( 'domain', $domain['host'], $link );

		$plugin_data = get_plugin_data( LLA_PLUGIN_DIR . '/limit-login-attempts-reloaded.php' );
		$link = add_query_arg( 'version', $plugin_data['Version'], $link );

		$setup_response = wp_remote_get( $link );
		$setup_response_body = json_decode( wp_remote_retrieve_body( $setup_response ), true );

		if( is_wp_error( $setup_response ) ) {

			$return['error'] = $setup_response->get_error_message();

		} else if( wp_remote_retrieve_response_code( $setup_response ) === 200 ) {

			$return['success'] = true;
			$return['app_config'] = $setup_response_body;

		} else {

			$return['error'] = ( !empty( $setup_response_body['message'] ) )
								? $setup_response_body['message']
								: __( 'The endpoint is not responding. Please contact your app provider to settle that.', 'limit-login-attempts-reloaded' );
			$return['response_code'] = wp_remote_retrieve_response_code( $setup_response );
		}

		return $return;
	}

	/**
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function stats() {

		return $this->request( 'stats', 'get' );
	}

	/**
	 * @return bool|mixed
	 */
	public static function stats_global() {

		$response = wp_remote_get('https://api.limitloginattempts.com/v1/global-stats');

		if( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {

			return false;
		} else {

			return json_decode( sanitize_textarea_field( stripslashes( wp_remote_retrieve_body( $response ) ) ), true );
		}
	}

	/**
	 * @param $data
	 * @return bool|mixed
	 */
	public function acl_check( $data ) {

		$this->prepare_settings( 'acl', $data );

		return $this->request( 'acl', 'post', $data );
	}

	/**
	 * @param $data
	 * @return bool|mixed
	 */
	public function acl( $data ) {

		return $this->request( 'acl', 'get', $data );
	}

	/**
	 * @param $data
	 * @return bool|mixed
	 */
	public function acl_create( $data ) {

		return $this->request( 'acl/create', 'post', $data );
	}

	/**
	 * @param $data
	 * @return bool|mixed
	 */
	public function acl_delete( $data ) {

		return $this->request( 'acl/delete', 'post', $data );
	}

	/**
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function country() {

		return $this->request( 'country', 'get' );
	}

	/**
	 * @param $data
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function country_add( $data ) {

		return $this->request( 'country/add', 'post', $data );
	}

	/**
	 * @param $data
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function country_remove( $data ) {

		return $this->request( 'country/remove', 'post', $data );
	}

	/**
	 * @param $data
	 * @return bool|mixed
	 */
	public function country_rule( $data ) {

		return $this->request( 'country/rule', 'post', $data );
	}

	/**
	 * @param $data
	 * @return bool|mixed
	 */
	public function lockout_check( $data ) {

		$this->prepare_settings( 'lockout', $data );

		return $this->request( 'lockout', 'post', $data );
	}

	/**
	 * @param int $limit
	 * @param string $offset
	 * @return bool|mixed
	 */
	public function log($limit = 25, $offset = '') {

		$data = array();

		$data['limit'] = $limit;
		$data['offset'] = $offset;
		$data['is_short'] = 1;

		return $this->request( 'log', 'get', $data );
	}

	public function get_lockouts($limit = 25, $offset = '') {

		$data = array();

		$data['limit'] = $limit;
		$data['offset'] = $offset;

		return $this->request( 'lockout', 'get', $data );
	}

	/**
	 * Prepare settings for API request
	 *
	 * @param $method
	 */
	public function prepare_settings( $method, &$data ) {

		$settings = array();

		if( !empty( $this->config['settings'] ) ) {

			foreach ( $this->config['settings'] as $setting_name => $setting_data ) {

				if( in_array( $method, $setting_data['methods'] ) ) {

					$settings[$setting_name] = $setting_data['value'];
				}
			}
		}

		if( $settings )
			$data['settings'] = $settings;
	}

	/**
	 * @param $method
	 * @param string $type
	 * @param null $data
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function request( $method, $type = 'get', $data = null ) {

		if( !$method ) {
			throw new Exception( 'You must to specify API method.' );
		}

		$headers = array();
		$headers[$this->config['header']] = $this->config['key'];

		if( $type === 'post' ) {

			$headers['Content-Type'] = 'application/json; charset=utf-8';
		}

		$func = ( $type === 'post' ) ? 'wp_remote_post' : 'wp_remote_get';

		$response = $func( $this->api.'/'.$method, array(
			'headers' 	=> $headers,
			'body' 		=> ( $type === 'post' ) ? json_encode( $data, JSON_FORCE_OBJECT ) : $data
		));

		$this->last_response_code = wp_remote_retrieve_response_code( $response );

		if( is_wp_error( $response ) || $this->last_response_code !== 200 ) {

			return false;
		} else {

			return json_decode( sanitize_textarea_field( stripslashes( wp_remote_retrieve_body( $response ) ) ), true );
		}

	}

}