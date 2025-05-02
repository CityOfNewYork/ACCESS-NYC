<?php
/**
 * Class WPML_LS_Shortcodes
 */
class WPML_LS_Shortcodes extends WPML_LS_Public_API {
	// Important: Same length for disabled variant.
	// Because plugins/themes may store the content serialized and a different
	// characters length would break the serialization.
	const LS                 = 'wpml_language_switcher';
	const LS_DISABLED        = 'wpml_disabled_switcher';
	const LS_WIDGET          = 'wpml_language_selector_widget';
	const LS_WIDGET_DISABLED = 'wpml_disabled_selector_widget';
	const LS_FOOTER          = 'wpml_language_selector_footer';
	const LS_FOOTER_DISABLED = 'wpml_disabled_selector_footer';

	public function init_hooks() {
		add_action( 'init', [ $this, 'restrict_writing_shortcode_for_lower_roles' ], PHP_INT_MAX );

		if ( $this->sitepress->get_setting( 'setup_complete' ) ) {
			add_shortcode( self::LS, array( $this, 'callback' ) );
			add_shortcode( self::LS_DISABLED, array( $this, 'callback_disabled' ) );

			// Backward compatibility.
			add_shortcode( self::LS_WIDGET, array( $this, 'callback' ) );
			add_shortcode( self::LS_WIDGET_DISABLED, array( $this, 'callback_disabled' ) );
			add_shortcode( self::LS_FOOTER, array( $this, 'callback' ) );
			add_shortcode( self::LS_FOOTER_DISABLED, array( $this, 'callback_disabled' ) );
		}
	}

	/**
	 * @param array|string $args
	 * @param string|null  $content
	 * @param string       $tag
	 *
	 * @return string
	 */
	public function callback( $args, $content = null, $tag = '' ) {
		$args = (array) $args;
		$args = $this->parse_legacy_shortcodes( $args, $tag );
		$args = $this->convert_shortcode_args_aliases( $args );

		return $this->render( $args, $content );
	}

	public function callback_disabled() {
		// No need to make this translatable.
		return "You're not allowed to use this shortcode.";
	}


	/**
	 * @return bool
	 */
	private function user_has_permission_to_use_shortcode() {
		return current_user_can( 'unfiltered_html' )
			|| (
				defined( 'WPML_TRANSLATOR_CAN_USE_LS_SHORTCODE' )
				&& WPML_TRANSLATOR_CAN_USE_LS_SHORTCODE
				&& current_user_can( 'translate' )
		       );
	}


	/**
	 * Filter to disable the shortcode in content when user has a lower role
	 * than Editor or Translator. See wpmldev-4766.
	 */
	public function restrict_writing_shortcode_for_lower_roles() {
		// If current user role <= author AND not translator.
		if ( ! $this->user_has_permission_to_use_shortcode() ) {
			// User is a Visitor / Subscriber / Contributor / Author.
			$_REQUEST = $this->replace_shortcode( $_REQUEST );
			$_POST    = $this->replace_shortcode( $_POST );
			$_GET     = $this->replace_shortcode( $_GET );

			// Super globals do not contain request data for REST requests.
			add_filter( 'rest_pre_dispatch', [ $this, 'replace_shortcode_in_rest_request' ], PHP_INT_MAX, 3 );
		}
	}

	/**
	 * @param mixed            $result
	 * @param \WP_REST_Server  $WP_Rest_Server
	 * @param \WP_REST_Request $WP_REST_Request
	 *
	 * @return mixed
	 */
	public function replace_shortcode_in_rest_request( $result, $WP_Rest_Server, \WP_REST_Request $WP_REST_Request ) {
		$body = $WP_REST_Request->get_body();

		if ( ! empty( $body ) ) {
			$filtered_body = $this->replace_shortcode( $body );

			if ( $filtered_body !== $body ) {
				$WP_REST_Request->set_body( $filtered_body );
			}
		}

		return $result;
	}

	private function replace_shortcode( $content ) {
		if ( ! is_string( $content ) ) {
			if ( is_array( $content ) ) {
				foreach ( $content as $key => &$value ) {
					$content[ $key ] = $this->replace_shortcode( $value );
				}
			}
			return $content;
		}

		// Light check which will match in 99,999% of cases.
		if ( strpos( $content, 'wpml_language' ) === false ) {
			return $content;
		}

		$search  = [ self::LS, self::LS_WIDGET, self::LS_FOOTER ];
		$replace = [ self::LS_DISABLED, self::LS_WIDGET_DISABLED, self::LS_FOOTER_DISABLED ];

		foreach ( $search as $index => $search ) {
			if ( strpos( $content, $search ) !== false ) {
				$content = str_replace( $search, $replace[ $index ], $content );
			}
		}

		return $content;
	}


	/**
	 * @param array  $args
	 * @param string $tag
	 *
	 * @return mixed
	 */
	private function parse_legacy_shortcodes( $args, $tag ) {
		if ( 'wpml_language_selector_widget' === $tag ) {
			$args['type'] = 'custom';
		} elseif ( 'wpml_language_selector_footer' === $tag ) {
			$args['type'] = 'footer';
		}

		return $args;
	}

}
