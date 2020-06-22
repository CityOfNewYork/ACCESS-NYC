<?php

class WPML_Cache_Terms_Per_Lang implements IWPML_Action {

	const CACHE_GROUP = 'WPML_Cache_Terms_Per_Lang';

	/** @var SitePress $sitepress */
	private $sitepress;

	/**
	 * WPML_Cache_Terms_Per_Lang constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'get_the_terms', array( $this, 'terms_per_lang' ), 10, 3 );
		add_action( 'clean_object_term_cache', array( $this, 'clear_cache' ), 10, 2 );
	}

	/**
	 * @param array  $terms
	 * @param int    $post_id
	 * @param string $taxonomy
	 *
	 * @return array|bool
	 */
	public function terms_per_lang( $terms, $post_id, $taxonomy ) {
		$all_terms = wp_cache_get( $post_id, self::CACHE_GROUP, false );
		if ( ! is_array( $all_terms ) ) {
			$current_post = get_post( $post_id );

			$all_terms = [];
			if ( $current_post && $this->sitepress->is_display_as_translated_post_type( $current_post->post_type ) ) {
				$taxonomies = get_post_taxonomies( $current_post );
				$all_terms  = wp_get_object_terms( $post_id, $taxonomies );
				$all_terms  = is_wp_error( $all_terms ) ? [] : $all_terms;
			}

			wp_cache_set( $post_id, $all_terms, self::CACHE_GROUP );
		}

		$terms_per_lang = $this->get_terms_by_tax( $all_terms ? $all_terms : [], $taxonomy );
		$terms_per_lang = $terms_per_lang->isEmpty() ? false : $terms_per_lang->toArray();

		return $terms_per_lang ?: $terms;
	}

	/**
	 * @param array  $all_terms
	 * @param string $taxonomy
	 *
	 * @return WPML\Collect\Support\Collection
	 */
	private function get_terms_by_tax( $all_terms, $taxonomy ) {
		return wpml_collect( $all_terms )
			->filter(
				function ( $term ) use ( $taxonomy ) {
					return $taxonomy === $term->taxonomy;
				}
			)
			->values();
	}

	/**
	 * @param array $object_ids An array of object IDs.
	 */
	public function clear_cache( $object_ids ) {
		foreach ( $object_ids as $id ) {
			wp_cache_delete( $id, self::CACHE_GROUP );
		}
	}
}
