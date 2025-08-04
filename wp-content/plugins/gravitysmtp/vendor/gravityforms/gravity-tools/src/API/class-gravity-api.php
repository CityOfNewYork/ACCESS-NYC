<?php
namespace Gravity_Forms\Gravity_Tools\API;

use Gravity_Forms\Gravity_Tools\Model\Form_Model;
use Gravity_Forms\Gravity_Tools\Utils\Common;
use WP_Error;

if ( ! defined( 'GRAVITY_API_URL' ) ) {
	define( 'GRAVITY_API_URL', 'https://gravityapi.com/wp-json/gravityapi/v1' );
}

/**
 * Client-side API wrapper for interacting with the Gravity APIs.
 *
 * @package    Gravity Tools
 * @since      1.0
 */
class Gravity_Api {

	/**
	 * @var Common
	 */
	protected $common;

	/**
	 * @var Form_Model
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param $common
	 * @param $model
	 * @param $namespace
	 */
	public function __construct( $common, $model, $namespace ) {
		$this->common    = $common;
		$this->model     = $model;
		$this->namespace = $namespace;
	}

	/**
	 * Retrieves site key and site secret key from remote API and stores them as WP options. Returns false if license key is invalid; otherwise, returns true.
	 *
	 * @since  1.0
	 *
	 * @param string  $license_key License key to be registered.
	 * @param boolean $is_md5      Specifies if $license_key provided is an MD5 or unhashed license key.
	 *
	 * @return bool|WP_Error
	 */
	public function register_current_site( $license_key, $is_md5 = false ) {
		$body              = array();
		$body['site_name'] = get_bloginfo( 'name' );
		$body['site_url']  = get_bloginfo( 'url' );

		if ( $is_md5 ) {
			$body['license_key_md5'] = $license_key;
		} else {
			$body['license_key'] = $license_key;
		}

		$result = $this->request( 'sites', $body, 'POST', array( 'headers' => $this->get_license_auth_header( $license_key ) ) );
		$result = $this->prepare_response_body( $result, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_option( 'gf_site_key', $result['key'] );
		update_option( 'gf_site_secret', $result['secret'] );

		return true;
	}

	/**
	 * Updates license key for a site that has already been registered.
	 *
	 * @since  1.0
	 *
	 * @param string $new_license_key_md5 Hash license key to be updated
	 *
	 * @return \Gravity_Forms\Gravity_Tools\License\License_API_Response|WP_Error
	 */
	public function update_current_site( $new_license_key_md5 ) {

		$site_key    = $this->get_site_key();
		$site_secret = $this->get_site_secret();
		if ( empty( $site_key ) || empty( $site_secret ) ) {

			return false;
		}

		$body                    = $this->get_remote_post_params();
		$body['site_name']       = get_bloginfo( 'name' );
		$body['site_url']        = get_bloginfo( 'url' );
		$body['site_key']        = $site_key;
		$body['site_secret']     = $site_secret;
		$body['license_key_md5'] = $new_license_key_md5;

		$result = $this->request( 'sites/' . $site_key, $body, 'PUT', array( 'headers' => $this->get_site_auth_header( $site_key, $site_secret ) ) );
		$result = $this->prepare_response_body( $result, true );

		if ( is_wp_error( $result ) ) {
			return $result;

		}

		return $result;
	}

	/***
	 * Removes a license key from a registered site. NOTE: It doesn't actually deregister the site.
	 *
	 * @deprecated Use gapi()->update_current_site('') instead.
	 *
	 * @return bool|WP_Error
	 */
	public function deregister_current_site() {
		$site_key    = $this->get_site_key();
		$site_secret = $this->get_site_secret();

		if ( empty( $site_key ) ) {
			return false;
		}

		$body = array(
			'license_key_md5' => '',
		);

		$result = $this->request( 'sites/' . $site_key, $body, 'PUT', array( 'headers' => $this->get_site_auth_header( $site_key, $site_secret ) ) );
		$result = $this->prepare_response_body( $result, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Check the given license key to get its information from the API.
	 *
	 * @since 1.0
	 *
	 * @param string $key The license key.
	 *
	 * @return array|false|WP_Error
	 */
	public function check_license( $key ) {
		$params = array(
			'site_url'     => get_option( 'home' ),
			'is_multisite' => is_multisite(),
		);

		/**
		 * Allow the params passed to check_license to be modified before sending.
		 *
		 * @since 1.0
		 */
		$params = apply_filters( 'gravity_api_check_license_params', $params );

		$resource = 'licenses/' . $key . '/check?' . build_query( $params );
		$result   = $this->request( $resource, null );
		$result   = $this->prepare_response_body( $result, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = $result;

		if ( $this->common->rgar( $result, 'license' ) ) {
			$response = $this->common->rgar( $result, 'license' );
		}

		// Set the license object to the transient.
		set_transient( 'rg_gforms_license', $response, DAY_IN_SECONDS );

		return $response;
	}

	/**
	 * Get GF core and add-on family information.
	 *
	 * @since 1.0
	 *
	 * @return false|array
	 */
	public function get_plugins_info() {
		$version_info = $this->get_version_info();

		if ( empty( $version_info['offerings'] ) ) {
			return false;
		}

		return $version_info['offerings'];
	}

	/**
	 * Get version information from the Gravity Manager API.
	 *
	 * @since 1.0
	 *
	 * @param false $cache
	 *
	 * @return array
	 */
	private function get_version_info( $cache = false ) {

		$version_info = null;

		if ( $cache ) {
			$cache_key = sprintf( '%s_version_info', $this->namespace );
			$cached_info = get_option( $cache_key );

			// Checking cache expiration
			$cache_duration  = DAY_IN_SECONDS; // 24 hours.
			$cache_timestamp = $cached_info && isset( $cached_info['timestamp'] ) ? $cached_info['timestamp'] : 0;

			// Is cache expired? If not, set $version_info to the cached data.
			if ( $cache_timestamp + $cache_duration >= time() ) {
				$version_info = $cached_info;
			}
		}

		if ( is_wp_error( $version_info ) || isset( $version_info['headers'] ) ) {
			// Legacy ( < 2.1.1.14 ) version info contained the whole raw response.
			$version_info = null;
		}

		// If we reach this point with a $version_info array, it's from cache, and we can return it.
		if ( $version_info ) {
			return $version_info;
		}

		//Getting version number
		$options = array(
			'method'  => 'POST',
			'timeout' => 20,
		);

		$options['headers'] = array(
			'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
			'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ),
		);

		$options['body']    = $this->get_remote_post_params();
		$options['timeout'] = 15;

		$nocache = $cache ? '' : 'nocache=1'; //disabling server side caching

		$raw_response = $this->common->post_to_manager( 'version.php', $nocache, $options );
		$version_info = array(
			'is_valid_key' => '1',
			'version'      => '',
			'url'          => '',
			'is_error'     => '1',
		);

		if ( is_wp_error( $raw_response ) || $this->common->rgars( $raw_response, 'response/code' ) != 200 ) {
			$version_info['timestamp'] = time();

			return $version_info;
		}

		$decoded = json_decode( $raw_response['body'], true );

		if ( empty( $decoded ) ) {
			$version_info['timestamp'] = time();

			return $version_info;
		}

		$decoded['timestamp'] = time();

		// Caching response.
		$cache_key = sprintf( '%s_version_info', $this->namespace );
		update_option( $cache_key, $decoded, false ); //caching version info

		return $decoded;
	}

	/**
	 * Get the parameters to use in a remote request.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_remote_post_params() {

		// This can get called in contexts where this file isn't loaded. Require it here to avoid fatals.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		global $wpdb;

		$plugin_list = get_plugins();
		$plugins     = array();

		$active_plugins = get_option( 'active_plugins' );

		foreach ( $plugin_list as $key => $plugin ) {
			$is_active = in_array( $key, $active_plugins );

			$slug = substr( $key, 0, strpos( $key, '/' ) );
			if ( empty( $slug ) ) {
				$slug = str_replace( '.php', '', $key );
			}

			$plugins[] = array(
				'name' => str_replace( 'phpinfo()', 'PHP Info', $plugin['Name'] ),
				'slug' => $slug,
				'version' => $plugin['Version'],
				'is_active' => $is_active,
			);
		}

		$plugins = json_encode( $plugins );

		//get theme info
		$theme            = wp_get_theme();
		$theme_name       = $theme->get( 'Name' );
		$theme_uri        = $theme->get( 'ThemeURI' );
		$theme_version    = $theme->get( 'Version' );
		$theme_author     = $theme->get( 'Author' );
		$theme_author_uri = $theme->get( 'AuthorURI' );

		$im             = is_multisite();
		$lang           = get_locale();

		$post = array(
			'plugins' => $plugins,
			'tn'      => $theme_name,
			'tu'      => $theme_uri,
			'tv'      => $theme_version,
			'ta'      => $theme_author,
			'tau'     => $theme_author_uri,
			'im'      => $im,
			'lang'    => $lang,
		);

		/**
		 * Allows the remote post parameters to be filtered to add more data.
		 *
		 * @since 1.0
		 *
		 * @param array $post The current data array.
		 *
		 * @return array
		 */
		return apply_filters( 'gravity_api_remote_post_params', $post );;
	}

	/**
	 * Update the usage data (call version.php in Gravity Manager). We will replace it once we have statistics API endpoints.
	 *
	 * @since 1.0
	 */
	public function update_site_data() {

		// Whenever we update the plugins info, we call the versions.php to update usage data.
		$options            = array( 'method' => 'POST' );
		$options['headers'] = array(
			'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
			'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ),
			'Referer'      => get_bloginfo( 'url' ),
		);
		$options['body']    = $this->common->get_remote_post_params();
		// Set the version to 3 which lightens the burden of version.php, it won't return anything to us anymore.
		$options['body']['version'] = '3';
		$options['timeout']         = 15;

		$nocache = 'nocache=1'; //disabling server side caching

		$this->common->post_to_manager( 'version.php', $nocache, $options );
	}

	/**
	 * Send an email to Hubspot to add to the list.
	 *
	 * @since 1.0
	 *
	 * @param $email
	 *
	 * @return mixed
	 */
	public function send_email_to_hubspot( $email ) {
		$body = array(
			'email' => $email,
		);

		$result = $this->request( 'emails/installation/add-to-list', $body, 'POST', array( 'headers' => $this->get_license_info_header( $site_secret ) ) );
		$result = $this->prepare_response_body( $result, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	// # HELPERS

	/**
	 * Get the stored license key.
	 *
	 * @since 1.0
	 *
	 * @return mixed
	 */
	public function get_key() {
		return $this->common->get_key();
	}

	/**
	 * Get the site auth header.
	 *
	 * @since 1.0
	 *
	 * @param $site_key
	 * @param $site_secret
	 *
	 * @return string[]
	 */
	private function get_site_auth_header( $site_key, $site_secret ) {

		$auth = base64_encode( "{$site_key}:{$site_secret}" );

		return array( 'Authorization' => 'GravityAPI ' . $auth );

	}

	/**
	 * Get the license info header.
	 *
	 * @since 1.0
	 *
	 * @param $site_secret
	 *
	 * @return string[]
	 */
	private function get_license_info_header( $site_secret ) {
		$auth = base64_encode( "gravityforms.com:{$site_secret}" );

		return array( 'Authorization' => 'GravityAPI ' . $auth );
	}

	/**
	 * Get the license auth header.
	 *
	 * @since 1.0
	 *
	 * @param $license_key_md5
	 *
	 * @return string[]
	 */
	private function get_license_auth_header( $license_key_md5 ) {

		$auth = base64_encode( "license:{$license_key_md5}" );

		return array( 'Authorization' => 'GravityAPI ' . $auth );

	}

	/**
	 * Prepare response body.
	 *
	 * @since 1.0
	 *
	 * @param WP_Error|WP_REST_Response $raw_response The API response.
	 * @param bool                      $as_array     Whether to return the response as an array or object.
	 *
	 * @return array|object|WP_Error
	 */
	public function prepare_response_body( $raw_response, $as_array = false ) {

		if ( is_wp_error( $raw_response ) ) {
			return $raw_response;
		}

		$response_body    = json_decode( wp_remote_retrieve_body( $raw_response ), $as_array );
		$response_code    = wp_remote_retrieve_response_code( $raw_response );
		$response_message = wp_remote_retrieve_response_message( $raw_response );

		if ( $response_code > 200 ) {

			// If a WP_Error was returned in the body.
			if ( $this->common->rgar( $response_body, 'code' ) ) {

				// Restore the WP_Error.
				$error = new WP_Error( $response_body['code'], $response_body['message'], $response_body['data'] );
			} else {
				$error = new WP_Error( 'server_error', 'Error from server: ' . $response_message );
			}

			return $error;

		}

		return $response_body;
	}

	/**
	 * Purge the site credentials.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function purge_site_credentials() {

		delete_option( 'gf_site_key' );
		delete_option( 'gf_site_secret' );
		delete_option( 'gf_site_registered' );

	}

	/**
	 * Making API requests.
	 *
	 * @since 1.0
	 *
	 * @param string $resource The API route.
	 * @param array  $body     The request body.
	 * @param string $method   The method.
	 * @param array  $options  The options.
	 *
	 * @return array|WP_Error
	 */
	public function request( $resource, $body, $method = 'POST', $options = array() ) {
		$body['timestamp'] = time();

		// set default options
		$options = wp_parse_args( $options, array(
			'method'    => $method,
			'timeout'   => 10,
			'body'      => in_array( $method, array( 'GET', 'DELETE' ) ) ? null : json_encode( $body ),
			'headers'   => array(),
			'sslverify' => false,
		) );

		// set default header options
		$options['headers'] = wp_parse_args( $options['headers'], array(
			'Content-Type' => 'application/json; charset=' . get_option( 'blog_charset' ),
			'User-Agent'   => 'WordPress/' . get_bloginfo( 'version' ),
			'Referer'      => get_bloginfo( 'url' ),
		) );

		// WP docs say method should be uppercase
		$options['method'] = strtoupper( $options['method'] );

		$request_url = $this->get_gravity_api_url() . $resource;

		return wp_remote_request( $request_url, $options );
	}

	/**
	 * Get the site key.
	 *
	 * @since 1.0
	 *
	 * @return false|mixed|void
	 */
	public function get_site_key() {

		if ( defined( 'GRAVITY_API_SITE_KEY' ) ) {
			return GRAVITY_API_SITE_KEY;
		}

		$site_key = get_option( 'gf_site_key' );
		if ( empty( $site_key ) ) {
			return false;
		}

		return $site_key;

	}

	/**
	 * Get the site secret.
	 *
	 * @since 1.0
	 *
	 * @return false|mixed|void
	 */
	public function get_site_secret() {
		if ( defined( 'GRAVITY_API_SITE_SECRET' ) ) {
			return GRAVITY_API_SITE_SECRET;
		}
		$site_secret = get_option( 'gf_site_secret' );
		if ( empty( $site_secret ) ) {
			return false;
		}

		return $site_secret;
	}

	/**
	 * Get the gravity URL.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_gravity_api_url() {
		return trailingslashit( GRAVITY_API_URL );
	}

	/**
	 * Check if the site has the gf_site_key and gf_site_secret options.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_site_registered() {
		return $this->get_site_key() && $this->get_site_secret();
	}

	/**
	 * Check if the site has the gf_site_key, gf_site_secret and also the gf_site_registered options.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_legacy_registration() {
		return $this->is_site_registered() && ! get_option( 'gf_site_registered' );
	}

}



