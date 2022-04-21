<?php

/**
 * Class WPML_REST_Base
 *
 * @author OnTheGo Systems
 */
abstract class WPML_REST_Base {
	const CAPABILITY_EXTERNAL = 'external';

	const REST_NAMESPACE = 'wpml/v1';
	/**
	 * @var null
	 */
	protected $namespace;

	/**
	 * WPML_REST_Base constructor.
	 *
	 * @param null $namespace Defaults to `\WPML_REST_Base::REST_NAMESPACE`.
	 */
	public function __construct( $namespace = null ) {
		if ( ! $namespace ) {
			$namespace = self::REST_NAMESPACE;
		}
		$this->namespace = $namespace;
	}

	abstract public function add_hooks();

	public function validate_permission( WP_REST_Request $request ) {
		$user_can = $this->user_has_matching_capabilities( $request );

		if ( ! $user_can ) {
			return false;
		}

		$nonce = $this->get_nonce( $request );

		return $user_can && wp_verify_nonce( $nonce, 'wp_rest' );
	}

	abstract public function get_allowed_capabilities( WP_REST_Request $request );

	/**
	 * @param string $route
	 * @param array  $args
	 */
	protected function register_route( $route, array $args ) {
		$args = $this->ensure_permission( $args );

		register_rest_route( $this->namespace, $route, $args );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	private function ensure_permission( array $args ) {
		if ( ! array_key_exists( 'permission_callback', $args ) || ! $args['permission_callback'] ) {
			$args['permission_callback'] = array( $this, 'validate_permission' );
		}

		return $args;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	private function user_has_matching_capabilities( WP_REST_Request $request ) {
		$capabilities = $this->get_allowed_capabilities( $request );

		$user_can = false;
		if ( self::CAPABILITY_EXTERNAL === $capabilities ) {
			$user_can = true;
		} elseif ( is_string( $capabilities ) ) {
			$user_can = current_user_can( $capabilities );
		} elseif ( is_array( $capabilities ) ) {
			foreach ( $capabilities as $capability ) {
				$user_can = $user_can || current_user_can( $capability );
			}
		}

		return $user_can;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|string|null
	 */
	private function get_nonce( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'x_wp_nonce' );
		if ( ! $nonce ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		return $nonce;
	}

}
