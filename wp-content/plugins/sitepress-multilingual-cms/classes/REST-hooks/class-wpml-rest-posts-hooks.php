<?php

use \WPML\FP\Fns;

class WPML_REST_Posts_Hooks implements IWPML_Action {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var WPML_Term_Translation $term_translations */
	private $term_translations;

	public function __construct(
		SitePress $sitepress,
		WPML_Term_Translation $term_translations
	) {
		$this->sitepress         = $sitepress;
		$this->term_translations = $term_translations;
	}

	public function add_hooks() {
		$post_types = $this->sitepress->get_translatable_documents();

		foreach ( $post_types as $post_type => $post_object ) {
			add_filter( "rest_prepare_$post_type", array( $this, 'prepare_post' ), 10, 2 );
		}

		add_filter( 'rest_request_before_callbacks', array( $this, 'reload_wpml_post_translation' ), 10, 3 );
	}

	/**
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_post( $response, $post ) {
		if ( $this->sitepress->get_setting( 'sync_post_taxonomies' ) ) {
			$response = $this->preset_terms_in_new_translation( $response, $post );
		}

		$response = $this->adjust_sample_links( $response, $post );

		return $response;
	}

	/**
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 *
	 * @return WP_REST_Response
	 */
	private function preset_terms_in_new_translation( $response, $post ) {
		if ( ! isset( $_GET['trid'] ) ) {
			return $response;
		}

		$trid        = filter_var( $_GET['trid'], FILTER_SANITIZE_NUMBER_INT );
		$source_lang = isset( $_GET['source_lang'] )
			? filter_var( $_GET['source_lang'], FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			: $this->sitepress->get_default_language();

		$element_type = 'post_' . $post->post_type;

		if ( $this->sitepress->get_element_trid( $post->ID, $element_type ) ) {
			return $response;
		}

		$translations = $this->sitepress->get_element_translations( $trid, $element_type );

		if ( ! isset( $translations[ $source_lang ] ) ) {
			return $response;
		}

		$current_lang      = $this->sitepress->get_current_language();
		$translatable_taxs = $this->sitepress->get_translatable_taxonomies( true, $post->post_type );
		$all_taxs          = wp_list_filter( get_object_taxonomies( $post->post_type, 'objects' ), array( 'show_in_rest' => true ) );
		$data              = $response->get_data();

		$this->sitepress->switch_lang( $source_lang );

		foreach ( $all_taxs as $tax ) {
			$tax_rest_base = ! empty( $tax->rest_base ) ? $tax->rest_base : $tax->name;

			if ( ! isset( $data[ $tax_rest_base ] ) ) {
				continue;
			}

			$terms = get_the_terms( $translations[ $source_lang ]->element_id, $tax->name );

			if ( ! is_array( $terms ) ) {
				continue;
			}

			$term_ids = $this->get_translated_term_ids( $terms, $tax, $translatable_taxs, $current_lang );
			wp_set_object_terms( $post->ID, $term_ids, $tax->name );
			$data[ $tax_rest_base ] = $term_ids;
		}

		$this->sitepress->switch_lang( null );
		$response->set_data( $data );

		return $response;
	}

	/**
	 * @param array    $terms
	 * @param stdClass $tax
	 * @param array    $translatable_taxs
	 * @param string   $current_lang
	 *
	 * @return array
	 */
	private function get_translated_term_ids( array $terms, $tax, array $translatable_taxs, $current_lang ) {
		$term_ids = array();

		foreach ( $terms as $term ) {
			if ( in_array( $tax->name, $translatable_taxs ) ) {
				$term_ids[] = $this->term_translations->term_id_in( $term->term_id, $current_lang, false );
			} else {
				$term_ids[] = $term->term_id;
			}
		}

		return wpml_collect( $term_ids )
			->filter()
			->values()
			->map( Fns::unary( 'intval' ) )
			->toArray();
	}

	/**
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 *
	 * @return WP_REST_Response
	 */
	private function adjust_sample_links( $response, $post ) {
		$data = $response->get_data();

		if ( ! isset( $data['link'], $data['permalink_template'] ) ) {
			return $response;
		}

		$lang_details = $this->sitepress->get_element_language_details( $post->ID, 'post_' . $post->post_type );

		if ( empty( $lang_details->language_code ) ) {
			$lang                       = $this->sitepress->get_current_language();
			$data['link']               = $this->sitepress->convert_url( $data['link'], $lang );
			$data['permalink_template'] = $this->sitepress->convert_url( $data['permalink_template'], $lang );

			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * @param WP_HTTP_Response|WP_Error $response Result to send to the client. Usually a WP_REST_Response or WP_Error.
	 * @param array                     $handler  Route handler used for the request.
	 * @param WP_REST_Request           $request  Request used to generate the response.
	 *
	 * @return WP_HTTP_Response|WP_Error
	 */
	public function reload_wpml_post_translation( $response, array $handler, WP_REST_Request $request ) {
		if ( ! is_wp_error( $response ) && $this->is_saving_reusable_block( $request ) ) {
			wpml_load_post_translation( is_admin(), $this->sitepress->get_settings() );
		}

		return $response;
	}

	private function is_saving_reusable_block( WP_REST_Request $request ) {
		return in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ) )
		       && preg_match( '#\/wp\/v2\/blocks(?:\/\d+)*#', $request->get_route() );
	}
}
