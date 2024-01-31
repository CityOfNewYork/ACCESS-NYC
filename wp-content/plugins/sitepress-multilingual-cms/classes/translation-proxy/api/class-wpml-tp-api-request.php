<?php

use WPML\FP\Obj;
use WPML\FP\Str;

class WPML_TP_API_Request {
	const API_VERSION = 1.1;

	/** @var string */
	private $url;

	/** @var array */
	private $params = array( 'api_version' => self::API_VERSION );

	/** @var string */
	private $method = 'GET';

	/** @var bool */
	private $has_api_response = true;

	/**
	 * @param string $url
	 */
	public function __construct( $url ) {
		if ( empty( $url ) ) {
			throw new InvalidArgumentException( 'Url cannot be empty' );
		}

		// If we get absolute url(like XLIFF download url from TP) it can already contain
		// get parameters in the query string, so we should parse them correctly in such case.
		if ( Str::startsWith( 'http://', $url ) || Str::startsWith( 'https://', $url ) ) {
			$urlParts = wp_parse_url( $url );

			if ( Obj::has( 'query', $urlParts ) && is_string( $urlParts['query'] ) ) {
				$params       = explode( '&', $urlParts['query'] );
				$params       = array_reduce(
					$params,
					function( $params, $param ) {
						$paramParts               = explode( '=', $param );
						$params[ $paramParts[0] ] = $paramParts[1];
						return $params;
					},
					[]
				);
				$this->params = array_merge(
					$this->params,
					$params
				);
				$this->url    = explode( '?', $url )[0];
			} else {
				$this->url = $url;
			}
		} else {
			// This is the default case.
			$this->url = $url;
		}

	}

	/**
	 * @param array $params
	 */
	public function set_params( array $params ) {
		$this->params = array_merge( $this->params, $params );
	}

	/**
	 * @param string $method
	 */
	public function set_method( $method ) {
		if ( ! in_array( $method, array( 'GET', 'POST', 'PUT', 'DELETE', 'HEAD' ), true ) ) {
			throw new InvalidArgumentException( 'HTTP request method has invalid value' );
		}

		$this->method = $method;
	}

	/**
	 * @param bool $has_api_response
	 */
	public function set_has_api_response( $has_api_response ) {
		$this->has_api_response = (bool) $has_api_response;
	}

	/**
	 * @return string
	 */
	public function get_url() {
		$url = $this->url;
		if ( $this->get_params() ) {
			list( $url, $params_used_in_path ) = $this->add_parameters_to_path( $url, $this->get_params() );
			if ( 'GET' === $this->get_method() ) {
				$url = $this->add_query_parameters( $params_used_in_path, $url );
			}
		}

		return $url;
	}

	/**
	 * @return array
	 */
	public function get_params() {
		return $this->params;
	}

	/**
	 * @return string
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * @return bool
	 */
	public function has_api_response() {
		return $this->has_api_response;
	}

	private function add_parameters_to_path( $url, array $params ) {
		$used_params = array();

		if ( preg_match_all( '/\{.+?\}/', $url, $symbs ) ) {
			foreach ( $symbs[0] as $symb ) {
				$without_braces = preg_replace( '/\{|\}/', '', $symb );
				if ( preg_match_all( '/\w+/', $without_braces, $indexes ) ) {
					foreach ( $indexes[0] as $index ) {
						if ( isset( $params[ $index ] ) ) {
							$used_params[] = $index;

							$value = $params[ $index ];
							$url   = preg_replace( preg_quote( "/$symb/" ), $value, $url );
						}
					}
				}
			}
		}

		return array( $url, $used_params );
	}

	/**
	 * @param $params_used_in_path
	 * @param $url
	 *
	 * @return string
	 */
	private function add_query_parameters( $params_used_in_path, $url ) {
		$url .= '?' . preg_replace(
			'/\%5B\d+\%5D/',
			'%5B%5D',
			wpml_http_build_query(
				array_diff_key(
					$this->get_params(),
					array_fill_keys( $params_used_in_path, 1 )
				)
			)
		);

		return $url;
	}
}
