<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Fns;
use WPML\FP\Str;

class Frontend implements \IWPML_Frontend_Action {

	/** @var string */
	const PERMALINKS_CATEGORY_PATTERN = '%category%';

	/**
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'elementor_pro/search_form/after_input', [ $this, 'addLanguageFormField' ] );
		if ( Str::includes( self::PERMALINKS_CATEGORY_PATTERN, get_option( 'permalink_structure' ) ) ) {
			add_filter( 'post_link_category', Fns::memorize( [ $this, 'fixLanguageSwitcherPermalink' ] ), 10, 3 );
		}
	}

    public function addLanguageFormField() {
        do_action( 'wpml_add_language_form_field' );
    }

	/**
	 * @param \WP_Term   $cat
	 * @param \WP_Term[] $cats
	 * @param \WP_Post   $post
	 *
	 * @return \WP_Term
	 */
	public function fixLanguageSwitcherPermalink( $cat, $cats, $post ) {
		$postLang = apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => $post->ID,
			'element_type' => $post->post_type,
		] );
		$catLang  = apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => $cat->term_id,
			'element_type' => $cat->taxonomy,
		] );

		if ( $postLang !== $catLang ) {
			$convertedCatId = apply_filters( 'wpml_object_id', $cat->term_id, $cat->taxonomy, true, $postLang );
			return get_term( $convertedCatId, $cat->taxonomy );
		}

		return $cat;
	}
}
