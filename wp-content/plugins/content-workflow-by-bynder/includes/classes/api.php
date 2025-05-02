<?php

namespace GatherContent\Importer;

use GatherContent\Importer\General;
use GatherContent\Importer\Debug;
use WP_Error;

class API extends Base {

	protected $base_url = 'https://api.gathercontent.com/';
	protected $user = '';
	protected $api_key = '';
	protected $only_cached = false;
	protected $reset_request_cache = false;
	protected $disable_cache = false;
	protected $last_response = false;

	/**
	 * WP_Http instance
	 *
	 * @var WP_Http
	 */
	protected $http;

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct( \WP_Http $http ) {
		parent::__construct();

		$this->http          = $http;
		$this->disable_cache = $this->_get_val( 'flush_cache' ) && 'false' !== $this->_get_val( 'flush_cache' );
		if ( ! $this->disable_cache ) {
			$this->disable_cache = $this->_post_val( 'flush_cache' ) && 'false' !== $this->_post_val( 'flush_cache' );
		}
	}

	public function set_user( $email ) {
		$this->user = $email;
	}

	public function set_api_key( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * GC API request to get the results from the "/me" endpoint.
	 *
	 * @param bool $uncached Whether bypass cache when making request.
	 *
	 * @return mixed          Results of request.
	 * @since  3.0.0
	 *
	 * @link https://gathercontent.com/developers/me/get-me/
	 *
	 */
	public function get_me( $uncached = false ) {
		if ( $uncached ) {
			$this->reset_request_cache = true;
		}

		return $this->get( 'me' );
	}

	/**
	 * GC API request to get the results from the "/accounts" endpoint.
	 *
	 * @return mixed Results of request.
	 * @link https://gathercontent.com/developers/accounts/get-accounts/
	 *
	 * @since  3.0.0
	 *
	 */
	public function get_accounts() {
		return $this->get( 'accounts' );
	}

	/**
	 * GC API request to get the results from the "/account/<ACCOUNT_ID>" endpoint.
	 *
	 * @return mixed Results of request.
	 * @link https://gathercontent.com/developers/accounts/get-account/
	 *
	 * @since  3.0.0
	 *
	 */
	public function get_account( $account_id ) {
		return $this->get(
			'accounts/' . $account_id,
			array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v0.6+json',
				),
			)
		);
	}

	/**
	 * GC API request to get the results from the "/projects?account_id=<ACCOUNT_ID>" endpoint.
	 *
	 * @param int $account_id Account ID.
	 *
	 * @return mixed             Results of request.
	 * @since  3.0.0
	 *
	 * @link https://gathercontent.com/developers/projects/get-projects/
	 *
	 */
	public function get_account_projects( $account_id ) {
		return $this->get( 'projects?account_id=' . $account_id );
	}

	/**
	 * GC API request to get the results from the "/projects/<PROJECT_ID>" endpoint.
	 *
	 * @param int $project_id Project ID.
	 *
	 * @return mixed             Results of request.
	 * @since  3.0.0
	 *
	 * @link https://gathercontent.com/developers/projects/get-projects-by-id/
	 *
	 */
	public function get_project( $project_id ) {
		return $this->get( 'projects/' . $project_id );
	}

	/**
	 * GC API request to get the results from the "/projects/<PROJECT_ID>/statuses" endpoint.
	 *
	 * @param int $project_id Project ID.
	 * @param string $response Response result type.
	 *
	 * @return mixed             Results of request.
	 * @link https://gathercontent.com/developers/projects/get-projects-statuses/
	 *
	 * @since  3.0.0
	 *
	 */
	public function get_project_statuses( $project_id, $response = '' ) {
		return $this->get( 'projects/' . $project_id . '/statuses', array(), $response );
	}

	/**
	 * GC V2 API request to get the results from the "/projects/{project_id}/items" endpoint.
	 *
	 * Pass template_id to filter it with template_id as well
	 *
	 * @param int $project_id Project ID.
	 * @param int $template_id Template ID.
	 * @param bool $include_status bool defaults to false.
	 *
	 * @return mixed             Results of request.
	 * @since  3.2.0
	 *
	 * @link https://docs.gathercontent.com/reference/listitems
	 *
	 */
	public function get_project_items( $project_id, $template_id, $include_status = false ) {

		$query_params = array(
			'template_id' => $template_id,
			'include'     => 'status_name',
			'per_page'    => 500,
		);

		$response = $this->get(
			'projects/' . $project_id . '/items',
			array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v2+json',
				),
			),
			'',
			$query_params
		);

		if ( $include_status ) {
			foreach ( $response as $i => $item ) {
				$response[ $i ]->status = (object) $this->add_status_to_item( $item );
			}
		}

		return $response;
	}

	/**
	 * GC API request to get the results from the "/items/{item_id}" endpoint.
	 *
	 * @param int $item_id Item ID.
	 * @param bool $exclude_status set this to true to avoid appending status data
	 *
	 * @return mixed        Results of request.
	 * @link https://docs.gathercontent.com/reference/getitem
	 *
	 * @since  3.2.0
	 *
	 */
	public function get_item( $item_id, $exclude_status = false ) {

		$response = $this->get(
			'items/' . $item_id . '?include=structure',
			array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v2+json',
				),
			)
		);

		// append status to the item as it was removed in the V2 APIs and needed everywhere
		if ( ! $exclude_status && $response ) {
			$response->status      = (object) $this->add_status_to_item( $response );
			$response->status_name = $response->status->data->name ?: '';
		}

		return $response;
	}

	/**
	 * Add project status to single item.
	 *
	 * @param mixed $item item result object.
	 *
	 * @return mixed $status_data.
	 * @since  3.2.0
	 *
	 */
	public function add_status_to_item( $item ) {

		if ( ! $item->project_id ) {
			return array();
		}

		// get cached version of all the project statuses by making sure that the cache is enabled for this request
		$this->disable_cache       = false;
		$this->reset_request_cache = false;
		$all_statuses              = $this->get_project_statuses( $item->project_id );

		$matched_status = is_array( $all_statuses )
			? array_values( wp_list_filter( $all_statuses, array( 'id' => $item->status_id ) ) )
			: array();
		$data           = count( $matched_status ) > 0 ? $matched_status[0] : array();

		return compact( 'data' );
	}

	/**
	 * GC V2 API request to get the results from the "/projects/{project_id}/statuses/:status_id" endpoint.
	 *
	 * @param int $project_id Project ID, int $status_id Status ID.
	 *
	 * @return mixed             Results of request.
	 * @since  3.2.0
	 *
	 * @link https://docs.gathercontent.com/v0.5/reference/get-project-statuses-by-id
	 *
	 */
	public function get_project_status_information( $project_id, $status_id ) {
		return $this->get( 'projects/' . $project_id . '/statuses/' . $status_id );
	}

	/**
	 * GC V2 API request to get the files from the "/projects/{project_id}/files" endpoint.
	 *
	 * @param string $project_id required project_id to fetch the files.
	 * @param array $file_ids optional array to filter files with the project id.
	 *
	 * @return mixed          Results of request.
	 * @link https://docs.gathercontent.com/reference/listfiles
	 *
	 * @since  3.2.0
	 *
	 */
	public function get_item_files( $project_id, $file_ids = array() ) {

		if ( ! $project_id ) {
			return array();
		}

		return $this->get(
			'projects/' . $project_id . '/files' . ( ! empty( $file_ids ) ? '?file_id=' . implode( ',', $file_ids ) : '' ),
			array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v2+json',
				),
			)
		);
	}

	/**
	 * GC V2 API request to get the files from the "/projects/{project_id}/files" endpoint.
	 *
	 * @param string $project_id required project_id to fetch the files.
	 * @param string $file_id to update meta data.
	 * @param array $meta_data to update
	 *
	 * @return int|false status code
	 * @since  3.2.0
	 *
	 * @link https://docs.gathercontent.com/reference/listfiles
	 *
	 */
	public function update_file_meta( $project_id, $file_id, $meta_data ) {

		if ( ! $project_id ) {
			return false;
		}

		$args = array(
			'body'    => wp_json_encode( $meta_data ),
			'headers' => array(
				'Accept'       => 'application/vnd.gathercontent.v0.6+json',
				'Content-Type' => 'application/json',
			),
		);

		$response = $this->put(
			'projects/' . absint( $project_id ) . '/files/' . $file_id,
			$args
		);

		return is_wp_error( $response ) ? false : 200 === $response['response']['code'];
	}

	/**
	 * GC V2 API request to get the results from the "/projects/{project_id}/templates" endpoint.
	 *
	 * @param int $project_id Project ID.
	 *
	 * @return mixed             Results of request.
	 * @since  3.0.0
	 *
	 * @link https://docs.gathercontent.com/reference/listtemplates
	 *
	 */
	public function get_project_templates( $project_id ) {

		return $this->get(
			'projects/' . $project_id . '/templates',
			array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v2+json',
				),
			)
		);
	}

	/**
	 * GC API request to get the results from the "/templates/{template_id}" endpoint.
	 *
	 * @param int $template_id Template ID.
	 *
	 * @return mixed              Results of request.
	 * @since  3.0.0
	 *
	 * @link https://docs.gathercontent.com/reference/gettemplate
	 *
	 */
	public function get_template( $template_id ) {
		$response = $this->get(
			'templates/' . $template_id,
			array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v2+json',
				),
			),
			'full_data'
		);

		return $response;
	}

	/**
	 * GC API request to set status ID for an item.
	 *
	 * /items/<ITEM_ID>/choose_status
	 *
	 * @param int $item_id GatherContent Item Id.
	 * @param int $status_id Id of status to set.
	 *
	 * @return bool            If request was successful.
	 * @link https://gathercontent.com/developers/items/post-items-choose_status/
	 *
	 * @since  3.0.0
	 *
	 */
	public function set_item_status( $item_id, $status_id ) {
		$response = $this->post(
			'items/' . absint( $item_id ) . '/choose_status',
			array(
				'body' => array(
					'status_id' => absint( $status_id ),
				),
			)
		);

		if ( 202 === $response['response']['code'] ) {
			$data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( isset( $data->data ) ) {
				return $data->data;
			}

			return true;
		}

		return false;
	}

	/**
	 * GC V2 API request to update an items content.
	 *
	 * /items/<ITEM_ID>/content
	 *
	 * @param int $item_id GatherContent Item Id.
	 * @param array $content Data to save.
	 *
	 * @return bool           If request was successful.
	 * @link https://docs.gathercontent.com/reference/updateitemcontent
	 *
	 * @since 3.0.0
	 *
	 */
	public function update_item( $item_id, $content ) {

		$args = array(
			'body'    => wp_json_encode( $content ),
			'headers' => array(
				'Accept'       => 'application/vnd.gathercontent.v2+json',
				'Content-Type' => 'application/json',
			),
		);

		$response = $this->post(
			'items/' . absint( $item_id ) . '/content',
			$args
		);

		return is_wp_error( $response ) ? $response : 202 === $response['response']['code'];
	}

	/**
	 * GC V2 API request to save an item.
	 *
	 * /projects/{project_id}/items
	 *
	 * @link https://docs.gathercontent.com/reference/createitem
	 *
	 * @param int $project_id Project ID.
	 * @param int $template_id Template ID.
	 * @param string $name Item name.
	 * @param array $content
	 *
	 * @return bool                If request was successful.
	 */
	public function create_item( $project_id, $template_id, $name, $content = array() ) {

		$args = array(
			'body'    => compact( 'template_id', 'name', 'content' ),
			'headers' => array(
				'Accept' => 'application/vnd.gathercontent.v2+json',
			),
		);

		$response = $this->post( 'projects/' . $project_id . '/items', $args );

		$item_id = null;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 201 === $response['response']['code'] ) {
			$item_id = json_decode( wp_remote_retrieve_body( $response ) )->data->id;
		}

		return $item_id;

	}

	/**
	 * GC V2 API request to save an item.
	 *
	 * /projects/{project_id}/items
	 *
	 * @link https://docs.gathercontent.com/reference/createitem
	 *
	 * @param int $project_id Project ID.
	 * @param int $template_id Template ID.
	 * @param string $name Item name.
	 * @param array $content
	 *
	 * @return bool                If request was successful.
	 */
	public function create_structured_item( $project_id, $template_id, $name, $content = array() ) {

		$args = array(
			'body'    => compact( 'template_id', 'name', 'content' ),
			'headers' => array(
				'Accept' => 'application/vnd.gathercontent.v2+json',
			),
		);

		$response = $this->post( 'projects/' . $project_id . '/items', $args );

		$item_id = null;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 201 === $response['response']['code'] ) {
			$item_id = json_decode( wp_remote_retrieve_body( $response ) )->data->id;
		}

		return $item_id;

	}


	/**
	 * GC V2 API request to get the results from the "/projects/{project_id}/components" endpoint.
	 *
	 * @param int $project_id Project Id.
	 *
	 * @return mixed              Results of request.
	 * @since  3.0.0
	 *
	 * @link https://docs.gathercontent.com/reference/listcomponents
	 *
	 */
	public function get_components( $project_id ) {
		return $this->get(
			'projects/' . $project_id . '/components/',
			array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v2+json',
				),
			)
		);
	}

	/**
	 * GC V2 API request to get the results from the "/components/{component_uuid}" endpoint.
	 *
	 * @param int $component_uuid Component UUid.
	 *
	 * @return mixed              Results of request.
	 * @since  3.0.0
	 *
	 * @link https://docs.gathercontent.com/reference/getcomponent
	 *
	 */
	public function get_component( $component_uuid ) {
		return $this->get(
			'components/' . $component_uuid,
			array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v2+json',
				),
			)
		);
	}

	/**
	 * POST request helper, which assumes a data parameter in response.
	 *
	 * @param string $endpoint GatherContent API endpoint to retrieve.
	 * @param array $args Optional. Request arguments. Default empty array.
	 *
	 * @return mixed            The response.
	 * @see    API::cache_get() For additional information
	 *
	 * @since  3.0.0
	 *
	 */
	public function post( $endpoint, $args = array() ) {
		return $this->request( $endpoint, $args, 'POST' );
	}

	/**
	 * PUT request helper, which assumes a data parameter in response.
	 *
	 * @param string $endpoint GatherContent API endpoint to retrieve.
	 * @param array $args Optional. Request arguments. Default empty array.
	 *
	 * @return mixed            The response.
	 * @see    API::cache_get() For additional information
	 *
	 * @since  3.2.0
	 *
	 */
	public function put( $endpoint, $args = array() ) {
		$final_args = array_merge( array( 'method' => 'PUT' ), $args );

		return $this->request( $endpoint, $final_args, 'PUT' );
	}

	/**
	 * GET request helper which assumes caching, and assumes a data parameter in response.
	 *
	 * @param string $endpoint GatherContent API endpoint to retrieve.
	 * @param array $args Optional. Request arguments. Default empty array.
	 * @param string $response_type Optional. expected response. Default empty
	 * @param array $query_params Optional. Request query parameters to append to the URL. Default empty array.
	 *
	 * @return mixed  The response.
	 * @see    API::cache_get() For additional information
	 *
	 * @since  3.0.0
	 *
	 */
	public function get( $endpoint, $args = array(), $response_type = '', $query_params = array() ) {

		$data = $this->cache_get( $endpoint, DAY_IN_SECONDS, $args, 'GET', $query_params );

		if ( $response_type == 'full_data' ) {
			return $data;
		} elseif ( isset( $data->data ) ) {
			return $data->data;
		}

		return false;
	}

	/**
	 * Retrieve and cache the HTTP request.
	 *
	 * @param string $endpoint GatherContent API endpoint to retrieve.
	 * @param string $expiration The expiration time. Defaults to an hour.
	 * @param array $args Optional. Request arguments. Default empty array.
	 * @param array $query_params Optional. Request query parameters to append to the URL. Default empty array.
	 *
	 * @return array                 The response.
	 * @see    API::request() For additional information
	 *
	 * @since  3.0.0
	 *
	 */
	public function cache_get( $endpoint, $expiration = HOUR_IN_SECONDS, $args = array(), $method = 'get', $query_params = array() ) {

		$trans_key = 'cwbytr-' . md5( serialize( compact( 'endpoint', 'args', 'method', 'query_params' ) ) );
		$response  = get_transient( $trans_key );

		if ( $this->only_cached ) {
			$this->only_cached = false;

			return $response;
		}

		if ( ! $response || $this->disable_cache || $this->reset_request_cache ) {

			$response = $this->request( $endpoint, $args, 'GET', $query_params );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			set_transient( $trans_key, $response, $expiration );

			$keys                = get_option( 'gathercontent_transients' );
			$keys                = is_array( $keys ) ? $keys : array();
			$keys[ $endpoint ][] = $trans_key;
			update_option( 'gathercontent_transients', $keys, false );

			$this->reset_request_cache = false;
		}

		return $response;
	}

	/**
	 * Retrieve the raw response from the HTTP request.
	 *
	 * Request method defaults for helper functions:
	 *  - Default 'GET'  for wp_remote_get()
	 *  - Default 'POST' for wp_remote_post()
	 *  - Default 'HEAD' for wp_remote_head()
	 *
	 * @param string $endpoint GatherContent API endpoint to retrieve.
	 * @param array $args Optional. Request arguments. Default empty array.
	 * @param array $method Optional. Request method, defaults to 'GET'.
	 * @param array $query_params Optional. Request query parameters to append to the URL. Default empty array.
	 *
	 * @return array            The response.
	 * @see    WP_Http::request() For additional information on default arguments.
	 *
	 * @since  3.0.0
	 *
	 */
	public function request( $endpoint, $args = array(), $method = 'GET', $query_params = array() ) {

		$uri = add_query_arg( $query_params, $this->base_url . $endpoint );

		try {
			$args = $this->request_args( $args );
		} catch ( \Exception $e ) {
			return new WP_Error( 'cwby_api_setup_fail', $e->getMessage() );
		}

		if ( Debug::debug_mode() ) {
			Debug::debug_log(
				add_query_arg(
					array(
						'disable_cache'       => $this->disable_cache,
						'reset_request_cache' => $this->reset_request_cache,
					),
					$uri
				),
				'api $uri'
			);
			// Only log if we have more than authorization/accept headers.
			if ( count( $args ) > 1 || isset( $args['headers'] ) && count( $args['headers'] ) > 2 ) {
				Debug::debug_log( $args, 'api $args' );
			}
		}

		$args['headers']['Referer'] = sanitize_url($_SERVER['HTTP_HOST']);

		if ( 'PUT' === $method ) {
			$response = $this->http->request( $uri, $args );
		} else {
			$response = $this->http->{strtolower( $method )}( $uri, $args );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {

			$code    = $response['response']['code'];
			$success = $code >= 200 && $code < 300;

			if ( 500 === $response['response']['code'] && ( $error = wp_remote_retrieve_body( $response ) ) ) {

				$error    = json_decode( $error );
				$message  = isset( $error->message ) ? $error->message : __( 'Unknown Error', 'content-workflow-by-bynder' );
				$response = new WP_Error(
					'gc_api_error',
					$message,
					array(
						'error' => $error,
						'code'  => 500,
					)
				);

			} elseif ( 401 === $response['response']['code'] && ( $error = wp_remote_retrieve_body( $response ) ) ) {

				$message  = $error ? $error : __( 'Unknown Error', 'content-workflow-by-bynder' );
				$response = new WP_Error(
					'gc_api_error',
					$message,
					array(
						'error' => $error,
						'code'  => 401,
					)
				);

			} elseif ( isset( $args['filename'] ) ) {
				$response = (object) array( 'data' => true );
			} elseif ( 'GET' === $method ) {
				$response = $success ? json_decode( wp_remote_retrieve_body( $response ) ) : $response;
			}
		}

		$this->last_response = $response;

		return $response;
	}

	/**
	 * Prepares headers for GC requests.
	 *
	 * @param array $args Array of request args.
	 *
	 * @return array        Modified array of request args.
	 * @since  3.0.0
	 *
	 */
	public function request_args( $args ) {
		if ( ! $this->user || ! $this->api_key ) {
			$settings = get_option( General::OPTION_NAME, array() );
			if (
				is_array( $settings )
				&& isset( $settings['account_email'] )
				&& isset( $settings['api_key'] )
			) {
				$this->set_user( $settings['account_email'] );
				$this->set_api_key( $settings['api_key'] );
			} else {
				throw new \Exception( esc_html__( 'The Content Workflow API connection is not set up.', 'content-workflow-by-bynder' ) );
			}
		}

		$wp_version     = get_bloginfo( 'version' );
		$plugin_version = GATHERCONTENT_VERSION;

		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->user . ':' . $this->api_key ),
			'Accept'        => 'application/vnd.gathercontent.v0.5+json',
			'user-agent'    => "Content-Workflow-Integration-WordPress-{$wp_version}/{$plugin_version}",
		);

		$args['headers'] = isset( $args['headers'] )
			? wp_parse_args( $args['headers'], $headers )
			: $headers;

		return $args;
	}

	/**
	 * Sets the only_cached flag and returns object, for chaining methods,
	 * and only gets results from cache (doesn't make actual request).
	 *
	 * e.g. `$this->only_cached()->get( 'me' )`
	 *
	 * @return $this
	 * @since  3.0.0
	 *
	 */
	public function only_cached() {
		$this->reset_request_cache = true;

		return $this;
	}

	/**
	 * Sets the reset_request_cache flag and returns object, for chaining methods,
	 * and flushing/bypassing cache for next request.
	 *
	 * E.g. `$this->uncached()->get( 'me' )`
	 *
	 * @return $this
	 * @since  3.0.0
	 *
	 */
	public function uncached() {
		$this->reset_request_cache = true;

		return $this;
	}

	/**
	 * Some methods return false if response is not found. This allows retrieving the last response.
	 *
	 * @return mixed  The last request response.
	 * @since  3.0.0
	 *
	 */
	public function get_last_response() {
		return $this->last_response;
	}

	/**
	 * Flush all cached responses, or only for a given endpoint.
	 *
	 * @param string $endpoint Optional endpoint to clear cached response.
	 *
	 * @return bool             Status of cache flush/deletion.
	 * @since  3.0.0
	 *
	 */
	public function flush_cache( $endpoint = '' ) {
		$deleted = false;
		$keys    = get_option( 'gathercontent_transients' );
		$keys    = is_array( $keys ) ? $keys : array();

		if ( $endpoint ) {
			if ( isset( $keys[ $endpoint ] ) ) {
				foreach ( $keys[ $endpoint ] as $transient ) {
					delete_transient( $transient );
				}

				unset( $keys[ $endpoint ] );
				$deleted = true;
			}
		} else {
			foreach ( $keys as $endpoint => $transients ) {
				foreach ( $transients as $transient ) {
					delete_transient( $transient );
				}
			}

			$keys    = array();
			$deleted = true;
		}

		update_option( 'gathercontent_transients', $keys, false );

		return $deleted;
	}

}
