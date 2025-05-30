<?php

class WPML_Endpoints_Support {

	const STRING_CONTEXT = 'WP Endpoints';

	/**
	 * @var WPML_Post_Translation
	 */
	private $post_translations;
	/**
	 * @var string
	 */
	private $current_language;
	/**
	 * @var string
	 */
	private $default_language;

	public function __construct( WPML_Post_Translation $post_translations, $current_language, $default_language ) {
		$this->post_translations = $post_translations;
		$this->current_language  = $current_language;
		$this->default_language  = $default_language;
	}

	public function add_hooks() {
		add_action( 'init', array( $this, 'add_endpoints_translations' ) );

		add_filter( 'option_rewrite_rules', array(
			$this,
			'translate_endpoints_in_rewrite_rules'
		), 0, 1 ); // high priority
		add_filter( 'page_link', array( $this, 'endpoint_permalink_filter' ), 10, 2 );
		add_filter( 'wpml_ls_language_url', array( $this, 'add_endpoint_to_current_ls_language_url' ), 10, 2 );
		add_filter( 'wpml_get_endpoint_translation', array( $this, 'get_endpoint_translation' ), 10, 3 );
		add_filter( 'wpml_register_endpoint_string', array( $this, 'register_endpoint_string' ), 10, 2 );
		add_filter( 'wpml_get_endpoint_url', array( $this, 'get_endpoint_url' ), 10, 4 );
		add_filter( 'wpml_get_current_endpoint', array( $this, 'get_current_endpoint' ) );
		add_filter( 'wpml_get_registered_endpoints', array( $this, 'get_registered_endpoints' ) );
	}

	public function add_endpoints_translations() {

		$registered_endpoints = $this->get_registered_endpoints();

		if ( $registered_endpoints ) {

			foreach ( $registered_endpoints as $endpoint_key => $endpoint_value ) {

				$endpoint_translation = $this->get_endpoint_translation( $endpoint_key, $endpoint_value );

				if ( $endpoint_value !== urldecode( $endpoint_translation ) ) {
					add_rewrite_endpoint( $endpoint_translation, EP_ROOT | EP_PAGES, $endpoint_value );
				}
			}
		}

		do_action( 'wpml_after_add_endpoints_translations', $this->current_language );
	}

	/**
	 * @param string $key
	 * @param string $endpoint
	 * @param null|string $language
	 *
	 * @return string
	 */
	public function get_endpoint_translation( $key, $endpoint, $language = null ) {

		$this->register_endpoint_string( $key, $endpoint );

		$endpoint_translation = apply_filters( 'wpml_translate_single_string', $endpoint, self::STRING_CONTEXT, $key, $language ? $language : $this->current_language );

		if ( ! empty( $endpoint_translation ) ) {
			return implode( '/', array_map( 'rawurlencode', explode( '/', $endpoint_translation ) ) );
		} else {
			return $endpoint;
		}
	}

	/**
	 * @param string $key
	 * @param string $endpoint
	 */
	public function register_endpoint_string( $key, $endpoint ) {

		if ( $key === $endpoint ) {
			if ( ! $this->is_registered( $endpoint ) ) {
				icl_register_string( self::STRING_CONTEXT, $key, $endpoint );
				wp_cache_delete( self::STRING_CONTEXT, __CLASS__ );
			}
		}
	}

	/**
	 * @param string $endpoint
	 *
	 * @return bool
	 */
	private function is_registered( $endpoint ) {
		global $wpdb;

		$endpoints = wp_cache_get( self::STRING_CONTEXT, __CLASS__ );
		if ( false === $endpoints ) {
			$endpoints = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT value FROM {$wpdb->prefix}icl_strings WHERE context = %s",
					self::STRING_CONTEXT
				)
			);

			wp_cache_set( self::STRING_CONTEXT, $endpoints, __CLASS__ );
		}

		return in_array( $endpoint, $endpoints, true );
	}

	/**
	 * @param array $value
	 *
	 * @return array
	 */
	public function translate_endpoints_in_rewrite_rules( $value ) {

		if ( ! empty( $value ) ) {

			$registered_endpoints = $this->get_registered_endpoints();

			if ( $registered_endpoints ) {

				foreach ( $registered_endpoints as $endpoint_key => $endpoint_value ) {

					$endpoint_translation = $this->get_endpoint_translation( $endpoint_key, $endpoint_value );

					if ( $endpoint_value === urldecode( $endpoint_translation ) ) {
						continue;
					}

					$buff_value = array();

					foreach ( $value as $k => $v ) {
						$k                = preg_replace( '/(\/|^)' . preg_quote( $endpoint_value, '/' ) . '(\/)?(\(\/\(\.\*\)\)\?\/\?\$)/', '$1' . $endpoint_translation . '$2$3', $k );
						$buff_value[ $k ] = $v;
					}
					$value = $buff_value;
				}
			}
		}

		return $value;
	}

	/**
	 * @param string $link
	 * @param int $pid
	 *
	 * @return string
	 */
	public function endpoint_permalink_filter( $link, $pid ) {
		global $post, $wp;

		if ( isset( $post->ID ) && $post->ID && ! is_admin() ) {

			$page_lang = $this->post_translations->get_element_lang_code( $post->ID );

			if ( ! $page_lang ) {
				return $link;
			}

			if ( $pid === $post->ID ) {
				$pid_in_page_lang = $pid;
			} else {
				$translations     = $this->post_translations->get_element_translations( $pid );
				$pid_in_page_lang = isset( $translations[ $page_lang ] ) ? $translations[ $page_lang ] : $pid;
			}

			$current_lang = apply_filters( 'wpml_current_language', $this->current_language );

			if (
				(
					$current_lang != $page_lang &&
					$pid_in_page_lang == $post->ID
				) ||
				apply_filters( 'wpml_endpoint_force_permalink_filter', false, $current_lang, $page_lang )
			) {

				$endpoints = $this->get_registered_endpoints();

				foreach ( $endpoints as $key => $endpoint ) {
					if ( isset( $wp->query_vars[ $key ] ) ) {
						list( $link, $endpoint ) = apply_filters( 'wpml_endpoint_permalink_filter', array( $link, $endpoint ), $key );

						$link = $this->get_endpoint_url( $this->get_endpoint_translation( $key, $endpoint, $current_lang ), $wp->query_vars[ $key ], $link, $page_lang );
					}
				}
			}
		}

		return esc_url_raw( $link );
	}

	/**
	 * @param string $endpoint
	 * @param string $value
	 * @param string $permalink
	 * @param bool|string $page_lang
	 *
	 * @return string
	 */
	public function get_endpoint_url( $endpoint, $value = '', $permalink = '', $page_lang = false ) {

		$value = apply_filters( 'wpml_endpoint_url_value', $value, $page_lang );
		// Escape the value to prevent XSS attacks.
		$value = wp_kses_normalize_entities( $value );
		$value = str_replace( '&amp;', '&#038;', $value );
		$value = str_replace( "'", '&#039;', $value );

		if ( get_option( 'permalink_structure' ) ) {

			$query_string = '';
			if ( strstr( $permalink, '?' ) ) {
				$query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
				$permalink    = current( explode( '?', $permalink ) );
			}
			$url = trailingslashit( $permalink ) . $endpoint . '/' . $value . $query_string;
		} else {
			$url = add_query_arg( $endpoint, $value, $permalink );
		}

		return esc_url_raw( $url );
	}

	/**
	 * @param string $url
	 * @param array $data
	 *
	 * @return string
	 */
	public function add_endpoint_to_current_ls_language_url( $url, $data ) {
		global $post;

		$post_lang = '';
		$current_endpoint = array();

		if ( isset( $post->ID ) && $post->ID ) {

			$post_lang = $this->post_translations->get_element_lang_code( $post->ID );
			$current_endpoint = $this->get_current_endpoint( $data['code'] );

			if ( $post_lang === $data['code'] && $current_endpoint ) {
				$url = $this->get_endpoint_url( $current_endpoint['key'], $current_endpoint['value'], $url );
			}
		}

		$url = apply_filters( 'wpml_current_ls_language_url_endpoint', $url, $post_lang, $data, $current_endpoint );
		return esc_url_raw( $url );
	}

	/**
	 * @return array
	 */
	public function get_registered_endpoints() {
		global $wp_rewrite;

		$endpoints = empty( $wp_rewrite->endpoints ) ? [] : $wp_rewrite->endpoints;

		/**
		 * @param array $endpoints
		 *
		 * @return array
		 *
		 * @deprecated since 4.6, use `wpml_registered_endpoints` instead.
		 */
		$endpoints = apply_filters(
			'option_wpml_registered_endpoints',
			array_filter( wp_list_pluck( $endpoints, 2, 1 ) )
		);

		/**
		 * Filter the endpoints that WPML will handle.
		 *
		 * @param array $endpoints
		 *
		 * @return array
		 *
		 * @since 4.6
		 */
		return apply_filters( 'wpml_registered_endpoints', $endpoints );
	}

	/**
	 * @param string $language
	 *
	 * @return array
	 */
	public function get_current_endpoint( $language ) {
		global $wp;

		$current_endpoint = array();

		foreach ( $this->get_registered_endpoints() as $key => $value ) {
			if ( isset( $wp->query_vars[ $key ] ) ) {
				$current_endpoint['key']   = $this->get_endpoint_translation( $key, $value, $language );
				$current_endpoint['value'] = $wp->query_vars[ $key ];
				break;
			}
		}

		return $current_endpoint;
	}

}
