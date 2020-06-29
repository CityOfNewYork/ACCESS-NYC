<?php

/**
 * WP SEO by Yoast sitemap filter class
 *
 * @version 1.0.2
 * @deprecated version 4.3.0   use 'wp-seo-multilingual` plugin instead.
 */
class WPML_WPSEO_XML_Sitemaps_Filter_Old {
	/** @var  SitePress $sitepress */
	protected $sitepress;

	/**
	 * WPML_URL_Converter object.
	 *
	 * @var WPML_URL_Converter
	 */
	private $wpml_url_converter;

	/**
	 * @var WPML_Debug_BackTrace
	 */
	private $back_trace;

	const FILTER_PREFIX = 'wpseo_sitemap_';
	const FILTER_SUFFIX = '_content';

	/**
	 * WPSEO_XML_Sitemaps_Filter constructor.
	 *
	 * @param SitePress            $sitepress
	 * @param stdClass             $wpml_url_converter
	 * @param WPML_Debug_BackTrace $back_trace
	 */
	public function __construct( $sitepress, $wpml_url_converter, WPML_Debug_BackTrace $back_trace = null ) {
		$this->sitepress          = $sitepress;
		$this->wpml_url_converter = $wpml_url_converter;
		$this->back_trace         = $back_trace;
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		global $wpml_query_filter;

		if ( $this->is_per_domain() ) {
			add_filter( 'wpml_get_home_url', array( $this, 'get_home_url_filter' ), 10, 4 );
			add_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
			add_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
			add_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
			add_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
		} else {
			add_action( 'init', array( $this, 'init_hooks_when_custom_post_types_are_available' ), 20 );
			// Remove posts under hidden language.
			add_filter( 'wpseo_xml_sitemap_post_url', array( $this, 'exclude_hidden_language_posts' ), 10, 2 );
		}

		if ( $this->is_per_directory() ) {
			add_filter( 'wpml_get_home_url', array( $this, 'maybe_return_original_url_in_get_home_url_filter' ), 10, 2 );
		}

		add_filter( 'wpseo_enable_xml_sitemap_transient_caching', array( $this, 'transient_cache_filter' ), 10, 0 );
		add_filter( 'wpseo_build_sitemap_post_type', array( $this, 'wpseo_build_sitemap_post_type_filter' ) );
		add_action( 'wpseo_xmlsitemaps_config', array( $this, 'list_domains' ) );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'exclude_translations_of_static_pages' ), 10, 3 );
	}

	/**
	 * Delay these hooks until after 'init' so that custom post types are available.
	 */
	public function init_hooks_when_custom_post_types_are_available() {
		$types = wp_list_pluck( $this->sitepress->get_translatable_documents(), 'name' );
		foreach ( $types as $type ) {
			add_filter( self::FILTER_PREFIX . $type . self::FILTER_SUFFIX, array( $this, 'add_languages_to_sitemap' ) );
		}
	}

	/**
	 * Add home page urls for languages to sitemap.
	 * Do this only if configuration language per domain option is not used.
	 */
	public function add_languages_to_sitemap() {

		$output = '';
		$type   = $this->get_sitemap_type();

		// Check if page_for_posts is set and bail out early.
		if ( 'post' === $type && ! get_option( 'page_for_posts' ) ) {
			return $output;
		}

		// Loop through language adding urls.
		$default_lang = $this->sitepress->get_default_language();
		$active_langs = $this->sitepress->get_active_languages();
		unset( $active_langs[ $default_lang ] );

		foreach ( $active_langs as $lang_code => $lang_data ) {
			switch ( $type ) {
				case 'page':
					$url = $this->get_translated_home_url( $lang_code );
					break;
				case 'post':
					$url = $this->get_translated_page_for_posts( $lang_code );
					break;
				default:
					$this->sitepress->switch_lang( $lang_code );
					$url = get_post_type_archive_link( $type );
					$this->sitepress->switch_lang();
			}

			$output .= $this->sitemap_url_filter( $url );
		}

		return $output;
	}

	/**
	 * Update home_url for language per-domain configuration to return correct URL in sitemap.
	 *
	 * @param string $home_url
	 * @param string $url
	 * @param string $path
	 * @param string $orig_scheme
	 *
	 * @return bool|mixed|string
	 */
	public function get_home_url_filter( $home_url, $url, $path, $orig_scheme ) {
		if ( 'relative' !== $orig_scheme ) {
			$home_url = $this->wpml_url_converter->convert_url( $home_url, $this->sitepress->get_current_language() );
		}
		return $home_url;
	}

	/**
	 * Adds sitemap links in other languages.
	 * Only if we have a different domain per language.
	 */
	public function list_domains() {
		$ls_languages = $this->sitepress->get_ls_languages();
		if ( $ls_languages && $this->is_per_domain() ) {

			echo '<h3>' . esc_html__( 'WPML', 'sitepress' ) . '</h3>';
			echo esc_html__( 'Sitemaps for each language can be accessed below. You need to submit all these sitemaps to Google.', 'sitepress' );
			echo '<table class="wpml-sitemap-translations" style="margin-left: 1em; margin-top: 1em;">';

			foreach ( $ls_languages as $lang ) {
				$url = $lang['url'] . 'sitemap_index.xml';
				echo '<tr>';
				echo '<td>';
				echo '<a ';
				echo 'href="' . esc_url( $url ) . '" ';
				echo 'target="_blank" ';
				echo 'class="button-secondary" ';
				echo sprintf( "style=\"
					background-image:url('%s');
					background-repeat: no-repeat;
					background-position: 2px center;
					background-size: 16px;
					padding-left: 20px;
					width: 100%%;
					\"", esc_url( $lang['country_flag_url'] )
				);
				echo '>';
				echo esc_html( $lang['translated_name'] );
				echo '</a>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
	}

	/**
	 * @return bool
	 */
	public function is_per_domain() {
		return WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $this->sitepress->get_setting( 'language_negotiation_type' );
	}

	/**
	 * @return bool
	 */
	private function is_per_directory() {
		return WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $this->sitepress->get_setting( 'language_negotiation_type' );
	}

	/**
	 * Disables caching of sitemaps in transients.
	 *
	 * @return bool
	 */
	public function transient_cache_filter() {
		return false;
	}

	/**
	 * Removes auto-adjust-ids while building the sitemap.
	 * This allows Yoast to load terms in all languages.
	 *
	 * @param string $type
	 * @return string
	 */
	public function wpseo_build_sitemap_post_type_filter( $type ) {
		global $sitepress_settings;
		// Before building the sitemap and as we are on front-end make sure links aren't translated.
		// The setting should not be updated in DB.
		$sitepress_settings['auto_adjust_ids'] = 0;

		if ( ! $this->is_per_domain() ) {
			remove_filter( 'terms_clauses', array( $this->sitepress, 'terms_clauses' ) );
		}

		return $type;
	}

	/**
	 * Exclude posts under hidden language.
	 *
	 * @param  string   $url  Post URL.
	 * @param  stdClass $post Object with some post information.
	 *
	 * @return string
	 */
	public function exclude_hidden_language_posts( $url, $post ) {
		// Check that at least ID is set in post object.
		if ( ! isset( $post->ID ) ) {
			return $url;
		}

		// Get list of hidden languages.
		$hidden_languages = $this->sitepress->get_setting( 'hidden_languages', array() );

		// If there are no hidden languages return original URL.
		if ( empty( $hidden_languages ) ) {
			return $url;
		}

		// Get language information for post.
		$language_info = $this->sitepress->post_translations()->get_element_lang_code( $post->ID );

		// If language code is one of the hidden languages return null to skip the post.
		if ( in_array( $language_info, $hidden_languages, true ) ) {
			return null;
		}

		return $url;
	}

	/**
	 * Convert URL to sitemap entry format.
	 *
	 * @param string $url URl to prepare for sitemap.
	 *
	 * @return string
	 */
	public function sitemap_url_filter( $url ) {
		if ( ! $url ) {
			return '';
		}

		$url = htmlspecialchars( $url );

		$output  = "\t<url>\n";
		$output .= "\t\t<loc>" . $url . "</loc>\n";
		$output .= '';
		$output .= "\t\t<changefreq>daily</changefreq>\n";
		$output .= "\t\t<priority>1.0</priority>\n";
		$output .= "\t</url>\n";

		return $output;
	}

	/**
	 * @param array $excluded_post_ids
	 *
	 * @return array
	 */
	public function exclude_translations_of_static_pages( $excluded_post_ids ) {
		$static_pages = array( 'page_on_front', 'page_for_posts' );
		foreach ( $static_pages as $static_page ) {
			$page_id = (int) get_option( $static_page );
			if ( $page_id ) {
				$translations = (array) $this->sitepress->post_translations()->get_element_translations( $page_id );
				unset( $translations[ $this->sitepress->get_default_language() ] );
				$excluded_post_ids = array_merge( $excluded_post_ids, array_values( $translations ) );
			}
		}

		return $excluded_post_ids;
	}

	/**
	 * @param string $home_url
	 * @param string $original_url
	 *
	 * @return string
	 */
	public function maybe_return_original_url_in_get_home_url_filter( $home_url, $original_url ) {
		$places = array(
			array( 'WPSEO_Post_Type_Sitemap_Provider', 'get_home_url' ),
			array( 'WPSEO_Post_Type_Sitemap_Provider', 'get_classifier' ),
			array( 'WPSEO_Sitemaps_Router', 'get_base_url' ),
			array( 'WPSEO_Sitemaps_Renderer', '__construct' ),
		);

		foreach ( $places as $place ) {
			if ( $this->get_back_trace()->is_class_function_in_call_stack( $place[0], $place[1] ) ) {
				return $original_url;
			}
		}

		return $home_url;
	}

	/**
	 * @return WPML_Debug_BackTrace
	 */
	private function get_back_trace() {
		if ( null === $this->back_trace ) {
			$this->back_trace = new WPML\Utils\DebugBackTrace();
		}

		return $this->back_trace;
	}

	/**
	 * Figure out which sitemap we are working with by looking at the current filter.
	 */
	private function get_sitemap_type() {
		global $wp_current_filter;

		return substr( substr( end( $wp_current_filter ), strlen( self::FILTER_PREFIX ) ), 0, -strlen( self::FILTER_SUFFIX ) );
	}

	/**
	 * @param string $lang_code
	 *
	 * @return bool|mixed|string
	 */
	private function get_translated_home_url( $lang_code ) {
		return $this->wpml_url_converter->convert_url( home_url(), $lang_code );
	}

	/**
	 * @param string $lang_code
	 *
	 * @return false|string
	 */
	private function get_translated_page_for_posts( $lang_code ) {
		$page_for_posts = $this->sitepress->get_object_id( get_option( 'page_for_posts' ), 'page', true, $lang_code );

		return get_permalink( $page_for_posts );
	}

}
