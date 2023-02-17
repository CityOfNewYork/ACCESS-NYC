<?php

/**
 * Class WPML_Term_Adjust_Id
 */
class WPML_Term_Adjust_Id {

	/** @var WPML_Debug_BackTrace */
	private $debug_backtrace;

	/** @var WPML_Term_Translation */
	private $term_translation;

	/** @var WPML_Post_Translation */
	private $post_translation;

	/** @var SitePress */
	private $sitepress;

	/**
	 * WPML_Term_Adjust_Id constructor.
	 *
	 * @param WPML_Debug_BackTrace  $debug_backtrace
	 * @param WPML_Term_Translation $term_translation
	 * @param WPML_Post_Translation $post_translation
	 * @param SitePress             $sitepress
	 */
	public function __construct(
		WPML_Debug_BackTrace $debug_backtrace,
		WPML_Term_Translation $term_translation,
		WPML_Post_Translation $post_translation,
		SitePress $sitepress
	) {
		$this->debug_backtrace  = $debug_backtrace;
		$this->term_translation = $term_translation;
		$this->post_translation = $post_translation;
		$this->sitepress        = $sitepress;
	}

	/**
	 * @param WP_Term $term
	 * @param boolean $adjust_id_url_filter_off
	 *
	 * @return WP_Term
	 */
	public function filter(
		WP_Term $term,
		$adjust_id_url_filter_off
	) {
		if (
			$adjust_id_url_filter_off
			|| ! $this->sitepress->get_setting( 'auto_adjust_ids' )
			|| $this->is_ajax_add_term_translation()
			|| $this->debug_backtrace->are_functions_in_call_stack(
				[
					'get_category_parents',
					'get_permalink',
					'wp_update_post',
					'wp_update_term',
				]
			)
		) {
			WPML_Non_Persistent_Cache::flush_group( __CLASS__ );

			return $term;
		}

		$object_id = isset( $term->object_id ) ? $term->object_id : false;

		$key         = md5( (int) $object_id . $term->term_id . $this->sitepress->get_current_language() . $term->count );
		$found       = false;
		$cached_term = WPML_Non_Persistent_Cache::get( $key, __CLASS__, $found );
		if ( $found ) {
			return $cached_term;
		}

		$translated_id = $this->term_translation->element_id_in( $term->term_taxonomy_id, $this->sitepress->get_current_language() );

		if ( $translated_id && (int) $translated_id !== (int) $term->term_taxonomy_id ) {

			/** @var \WP_Term|\stdClass $term Declared also as \stdClass because we are setting `object_id`, which is not a property of \WP_Term. */
			$term = get_term_by( 'term_taxonomy_id', $translated_id, $term->taxonomy );

			if ( $object_id ) {
				$translated_object_id = $this->post_translation->element_id_in( $object_id, $this->sitepress->get_current_language() );
				if ( $translated_object_id ) {
					$term->object_id = $translated_object_id;
				} elseif ( $this->sitepress->is_display_as_translated_post_type( $this->post_translation->get_type( $object_id ) ) ) {
					$term->object_id = $this->post_translation->element_id_in( $object_id, $this->sitepress->get_default_language() );
				}
			}
		}

		WPML_Non_Persistent_Cache::set( $key, $term, __CLASS__ );

		return $term;
	}

	/**
	 * @return bool
	 */
	private function is_ajax_add_term_translation() {
		/* phpcs:disable WordPress.Security.NonceVerification.Missing */
		$taxonomy = isset( $_POST['taxonomy'] ) ? $_POST['taxonomy'] : false;
		if ( $taxonomy ) {
			return isset( $_POST['action'] ) && 'add-tag' === $_POST['action'] && ! empty( $_POST[ 'icl_tax_' . $taxonomy . '_language' ] );
		}
		/* phpcs:enable WordPress.Security.NonceVerification.Missing */

		return false;
	}
}
