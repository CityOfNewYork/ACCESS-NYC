<?php

use WPML\FP\Relation;
use \WPML\FP\Str;
use \WPML\SuperGlobals\Server;

/**
 * Class WPML_URL_Filters
 */
class WPML_URL_Filters {
	/** @var \SitePress */
	private $sitepress;

	/** @var \WPML_Post_Translation $post_translation */
	private $post_translation;

	/** @var \WPML_Canonicals */
	private $canonicals;

	/** @var \WPML_URL_Converter $url_converter */
	private $url_converter;

	/** @var \WPML_Debug_BackTrace */
	private $debug_backtrace;

	/**
	 * WPML_URL_Filters constructor.
	 *
	 * @param \WPML_Post_Translation $post_translation
	 * @param string                 $url_converter
	 * @param \WPML_Canonicals        $canonicals
	 * @param \SitePress             $sitepress
	 * @param \WPML_Debug_BackTrace   $debug_backtrace
	 */
	public function __construct(
		&$post_translation,
		&$url_converter,
		WPML_Canonicals $canonicals,
		&$sitepress,
		WPML_Debug_BackTrace $debug_backtrace
	) {
		$this->sitepress        = &$sitepress;
		$this->post_translation = &$post_translation;

		$this->url_converter   = &$url_converter;
		$this->canonicals      = $canonicals;
		$this->debug_backtrace = $debug_backtrace;

		if ( $this->frontend_uses_root() === true ) {
			WPML_Root_Page::init();
		}
		$this->add_hooks();
	}

	private function add_hooks() {
		if ( $this->frontend_uses_root() === true ) {
			add_filter( 'page_link', array( $this, 'page_link_filter_root' ), 1, 2 );
		} else {
			add_filter( 'page_link', array( $this, 'page_link_filter' ), 1, 2 );
		}

		$this->add_global_hooks();
		if ( $this->has_wp_get_canonical_url() ) {
			add_filter( 'get_canonical_url', array( $this, 'get_canonical_url_filter' ), 1, 2 );
		}

		add_action( 'current_screen', [ $this, 'permalink_options_home_url' ] );
	}

	public function add_global_hooks() {
		add_filter( 'home_url', [ $this, 'home_url_filter' ], - 10, 4 );
		// posts, pages & attachments links filters
		add_filter( 'post_link', [ $this, 'permalink_filter' ], 1, 2 );
		add_filter( 'attachment_link', [ $this, 'permalink_filter' ], 1, 2 );
		add_filter( 'post_type_link', [ $this, 'permalink_filter' ], 1, 2 );
		add_filter( 'wpml_filter_link', [ $this, 'permalink_filter' ], 1, 2 );
		add_filter( 'get_edit_post_link', [ $this, 'get_edit_post_link' ], 1, 3 );
		add_filter( 'oembed_request_post_id', [ $this, 'embedded_front_page_id_filter' ], 1, 2 );
		add_filter( 'post_embed_url', [ $this, 'fix_post_embedded_url' ], 1, 1 );

	}

	public function remove_global_hooks() {
		// posts and pages links filters
		remove_filter( 'oembed_request_post_id', [ $this, 'embedded_front_page_id_filter' ], 1 );
		remove_filter( 'post_embed_url', [ $this, 'fix_post_embedded_url' ], 1 );
		remove_filter( 'get_edit_post_link', [ $this, 'get_edit_post_link' ], 1 );
		remove_filter( 'wpml_filter_link', [ $this, 'permalink_filter' ], 1 );
		remove_filter( 'post_type_link', [ $this, 'permalink_filter' ], 1 );
		remove_filter( 'attachment_link', [ $this, 'permalink_filter' ], 1 );
		remove_filter( 'post_link', [ $this, 'permalink_filter' ], 1 );

		remove_filter( 'home_url', [ $this, 'home_url_filter' ], - 10 );
	}

	/**
	 * @param int    $post_id
	 * @param string $url
	 *
	 * @return int
	 *
	 * @hook oembed_request_post_id
	 */
	public function embedded_front_page_id_filter( $post_id, $url ) {
		if ( ! $post_id && $this->is_front_page( $url ) ) {
			$page_on_front = get_option( 'page_on_front' );

			if ( $page_on_front && get_post( $page_on_front ) instanceof WP_Post ) {
				$post_id = (int) $page_on_front;
			}
		}

		return $post_id;
	}

	/**
	 * @param string $embedded_url
	 *
	 * @return string
	 */
	public function fix_post_embedded_url( $embedded_url ) {
		$query = wpml_parse_url( $embedded_url, PHP_URL_QUERY );
		$embed = user_trailingslashit( 'embed' );
		if ( Str::includes( $embed, $query ) ) {
			$embedded_url = Str::split( '?', $embedded_url )[0] . $embed . '?' .
				   untrailingslashit( Str::replace( $embed, '', $query ) );
		}

		return $embedded_url;
	}

	/**
	 * Filters the link to a post's edit screen by appending the language query argument
	 *
	 * @param string $link
	 * @param int    $id
	 * @param string $context
	 *
	 * @return string
	 *
	 * @hook get_edit_post_link
	 */
	public function get_edit_post_link( $link, $id, $context = 'display' ) {
		if ( $id && (bool) ( $lang = $this->post_translation->get_element_lang_code( $id ) ) === true ) {
			$link .= ( 'display' === $context ? '&amp;' : '&' ) . 'lang=' . $lang;
			if ( ! did_action( 'wpml_pre_status_icon_display' ) ) {
				do_action( 'wpml_pre_status_icon_display' );
			}
			$link = apply_filters( 'wpml_link_to_translation', $link, $id, $lang, $this->post_translation->get_element_trid( $id ) );
		}

		return $link;
	}

	/**
	 * Permalink filter that is used when the site uses a root page
	 *
	 * @param string      $link
	 * @param int|WP_Post $pid
	 *
	 * @return string
	 */
	public function permalink_filter_root( $link, $pid ) {
		$pid  = is_object( $pid ) ? $pid->ID : $pid;
		$link = $this->sitepress->get_root_page_utils()->get_root_page_id() != $pid
				? $this->permalink_filter( $link, $pid ) : $this->filter_root_permalink( $link );

		return $link;
	}

	/**
	 * @param string $link
	 * @param int $pid
	 *
	 * @return string|WPML_Notice|WPML_Notice_Render
	 */
	public function page_link_filter_root( $link, $pid ) {
		$pid  = is_object( $pid ) ? $pid->ID : $pid;
		$link = $this->sitepress->get_root_page_utils()->get_root_page_id() != $pid
			? $this->page_link_filter( $link, $pid ) : $this->filter_root_permalink( $link );

		return $link;
	}

	/**
	 * Filters links to the root page, so that they are displayed properly in the front-end.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function filter_root_permalink( $url ) {
		$root_page_utils = $this->sitepress->get_root_page_utils();
		if ( $root_page_utils->get_root_page_id() > 0 && $root_page_utils->is_url_root_page( $url ) ) {
			$url_parts = wpml_parse_url( $url );
			$query     = isset( $url_parts['query'] ) ? $url_parts['query'] : '';
			$path      = isset( $url_parts['path'] ) ? $url_parts['path'] : '';
			$slugs     = array_filter( explode( '/', $path ) );
			$last_slug = array_pop( $slugs );
			$new_url   = $this->url_converter->get_abs_home();
			$new_url   = is_numeric( $last_slug ) ? trailingslashit( trailingslashit( $new_url ) . $last_slug ) : $new_url;
			$query     = $this->unset_page_query_vars( $query );
			$new_url   = trailingslashit( $new_url );
			$url       = (bool) $query === true ? trailingslashit( $new_url ) . '?' . $query : $new_url;
		}

		return $url;
	}

	/**
	 * @param string      $link
	 * @param int|WP_Post $post
	 *
	 * @return string
	 */
	public function permalink_filter( $link, $post ) {
		if ( ! $post ) {
			return $link;
		}

		$post_id = $post;

		if ( is_object( $post ) ) {
			$post_id = $post->ID;
		}

		/** @var int $post_id */
		if ( $post_id < 1 ) {
			return $link;
		}

		$canonical_url = $this->canonicals->permalink_filter( $link, $post_id );
		if ( $canonical_url ) {
			return $canonical_url;
		}

		$post_element = new WPML_Post_Element( $post_id, $this->sitepress );
		if (
			! is_wp_error( $post_element->get_wp_element_type() )
			&& ! $this->is_display_as_translated_mode( $post_element )
			&& $post_element->is_translatable()
		) {
			$link = $this->get_translated_permalink( $link, $post_id, $post_element );
		}

		return $this->url_converter->get_strategy()->fix_trailingslashit( $link );
	}

	/**
	 * @param string      $link
	 * @param int|WP_Post $post
	 *
	 * @return string
	 */
	public function page_link_filter( $link, $post ) {
		return $this->permalink_filter( $link, $post );
	}

	private function has_wp_get_canonical_url() {
		return $this->sitepress->get_wp_api()->function_exists( 'wp_get_canonical_url' );
	}

	/**
	 * @param string|bool $canonical_url
	 * @param WP_Post     $post
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function get_canonical_url_filter( $canonical_url, $post ) {
		return $this->canonicals->get_canonical_url( $canonical_url, $post, $this->get_request_language() );
	}

	/**
	 * @param WP_Screen $current_screen
	 */
	public function permalink_options_home_url( $current_screen ) {
		if ( Relation::propEq( 'id', 'options-permalink', $current_screen ) ) {
			add_filter( 'wpml_get_home_url', 'untrailingslashit' );
		}
	}

	/**
	 * @param string $url
	 * @param string $path
	 * @param string $orig_scheme
	 * @param int    $blog_id
	 *
	 * @return string
	 */
	public function home_url_filter( $url, $path, $orig_scheme, $blog_id ) {
		$language_negotiation_type = (int) $this->sitepress->get_setting( 'language_negotiation_type' );

		$context = new WPML_Home_Url_Filter_Context(
			$language_negotiation_type,
			$orig_scheme,
			$this->debug_backtrace
		);

		if ( $context->should_not_filter() ) {
			return $url;
		}

		$url_language = $this->get_request_language();

		if ( 'relative' === $orig_scheme ) {
			$home_url = $this->url_converter->get_home_url_relative( $url, $url_language );
		} else {
			$home_url = $this->url_converter->convert_url( $url, $url_language );
		}

		$home_url = apply_filters( 'wpml_get_home_url', $home_url, $url, $path, $orig_scheme, $blog_id );

		return $home_url;
	}

	public function frontend_uses_root() {
		/** @var array $urls */
		$urls = $this->sitepress->get_setting( 'urls' );

		if ( is_admin() ) {
			$uses_root = isset( $urls['root_page'], $urls['show_on_root'] )
						 && ! empty( $urls['directory_for_default_language'] )
						 && ( in_array( $urls['show_on_root'], array( 'page', 'html_file' ) ) );
		} else {
			$uses_root = isset( $urls['root_page'], $urls['show_on_root'] )
						 && ! empty( $urls['directory_for_default_language'] )
						 && ( ( $urls['root_page'] > 0 && 'page' === $urls['show_on_root'] )
							  || ( $urls['root_html_file_path'] && 'html_file' === $urls['show_on_root'] ) );
		}

		return $uses_root;
	}

	/**
	 * Finds the correct language a post belongs to by handling the special case of the post edit screen.
	 *
	 * @param int $post_id
	 *
	 * @return bool|mixed|null|String
	 */
	private function get_permalink_filter_lang( $post_id ) {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'sample-permalink' ) {
			$code = $this->get_language_from_url();
			$code = $code
				? $code
				: ( ! isset( $_SERVER['HTTP_REFERER'] )
					? $this->sitepress->get_default_language()
					: $this->url_converter->get_language_from_url( $_SERVER['HTTP_REFERER'] ) );
		} else {
			$code = $this->post_translation->get_element_lang_code( $post_id );
		}

		return $code;
	}

	private function unset_page_query_vars( $query ) {
		parse_str( (string) $query, $query_parts );
		foreach ( array( 'p', 'page_id', 'page', 'pagename', 'page_name', 'attachement_id' ) as $part ) {
			if ( isset( $query_parts[ $part ] ) && ! ( $part === 'page_id' && ! empty( $query_parts['preview'] ) ) ) {
				unset( $query_parts[ $part ] );
			}
		}

		return http_build_query( $query_parts );
	}

	private function get_language_from_url( $return_default_language_if_missing = false ) {
		$query_string_argument = '';
		$language              = '';
		if ( $return_default_language_if_missing ) {
			$language = $this->sitepress->get_current_language();
		}
		if ( array_key_exists( 'wpml_lang', $_GET ) ) {
			$query_string_argument = 'wpml_lang';
		} elseif ( array_key_exists( 'lang', $_GET ) ) {
			$query_string_argument = 'lang';
		}
		if ( '' !== $query_string_argument ) {
			$language = filter_var( $_GET[ $query_string_argument ], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		return $language;
	}

	/**
	 * @param string            $link
	 * @param int               $post
	 * @param WPML_Post_Element $post_element
	 *
	 * @return bool|false|mixed|string
	 */
	public function get_translated_permalink( $link, $post, $post_element ) {
		$code                      = $this->get_permalink_filter_lang( $post );
		$force_translate_permalink = apply_filters( 'wpml_force_translated_permalink', false );

		if ( ( ! is_admin() || wp_doing_ajax() || wpml_is_ajax() || $force_translate_permalink )
			 && ( ! $this->sitepress->get_wp_api()->is_a_REST_request() || $force_translate_permalink )
			 && $this->should_use_permalink_of_post_translation( $post_element )
		) {
			$link = get_permalink( $this->get_translated_post_id_for_current_language( $post_element ) );
		} else {
			$link = $this->url_converter->get_strategy()->convert_url_string( $link, $code );
		}
		if ( $this->sitepress->get_wp_api()->is_feed() ) {
			$link = str_replace( '&lang=', '&#038;lang=', $link );
		}

		return $link;
	}

	/**
	 * @param string             $link
	 * @param int                $post_id
	 * @param \WPML_Post_Element $post_element
	 *
	 * @return bool|mixed|string
	 */
	public function get_translated_page_link( $link, $post_id, $post_element ) {
		$code = $this->get_permalink_filter_lang( $post_id );
		if ( ! is_admin() && $this->should_use_permalink_of_post_translation( $post_element ) ) {
			$link = get_page_link( $this->get_translated_post_id_for_current_language( $post_element ) );
		} else {
			$link = $this->url_converter->get_strategy()->convert_url_string( $link, $code );
		}
		if ( $this->sitepress->get_wp_api()->is_feed() ) {
			$link = str_replace( '&lang=', '&#038;lang=', $link );
		}

		return $link;
	}

	public function get_request_language() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		$server_name = strpos( $request_uri, '/' ) === 0
			? untrailingslashit( Server::getServerName() ) : trailingslashit( Server::getServerName() );
		$url_snippet = $server_name . $request_uri;

		return $this->url_converter->get_language_from_url( $url_snippet );
	}

	private function should_use_permalink_of_post_translation( WPML_Post_Element $post_element ) {
		return $this->sitepress->get_setting( 'auto_adjust_ids' )
			   && $post_element->get_language_code() !== $this->sitepress->get_current_language()
			   && $this->get_translated_post_id_for_current_language( $post_element );
	}

	private function is_display_as_translated_mode( WPML_Post_Element $post_element ) {
		return $post_element->is_display_as_translated() &&
			   $post_element->get_language_code() == $this->sitepress->get_default_language() &&
			   ! $this->get_translated_post_id_for_current_language( $post_element ) &&
			   ! $this->debug_backtrace->is_class_function_in_call_stack( 'SitePress', 'get_ls_languages' );
	}

	private function get_translated_post_id_for_current_language( WPML_Post_Element $post_element ) {
		$post_id          = $post_element->get_element_id();
		$current_language = $this->sitepress->get_current_language();

		return $this->post_translation->element_id_in( $post_id, $current_language );
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	private function is_front_page( $url ) {
		return $this->canonicals->get_general_canonical_url( $url ) === home_url() && 'page' === get_option( 'show_on_front' );
	}
}
