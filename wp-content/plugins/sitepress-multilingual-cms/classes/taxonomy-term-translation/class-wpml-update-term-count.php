<?php

class WPML_Update_Term_Count {

	const CACHE_GROUP = __CLASS__;

	/** @var  WPML_WP_API $wp_api */
	private $wp_api;

	/**
	 * WPML_Update_Term_Count constructor.
	 *
	 * @param WPML_WP_API $wp_api
	 */
	public function __construct( $wp_api ) {
		$this->wp_api = $wp_api;
	}

	/**
	 * Triggers an update to the term count of all terms associated with the
	 * input post_id
	 *
	 * @param int $post_id
	 */
	public function update_for_post( $post_id ) {
		static $taxonomies;

		if ( ! $taxonomies ) {
			$taxonomies = $this->wp_api->get_taxonomies();
		}

		if ( is_wp_error( $taxonomies ) ) {
			return;
		}

		$found = false;
		$terms = WPML_Non_Persistent_Cache::get( $post_id, self::CACHE_GROUP, $found );
		if ( ! $found ) {
			$terms = $this->wp_api->wp_get_post_terms( $post_id, $taxonomies );

			WPML_Non_Persistent_Cache::set( $post_id, $terms, self::CACHE_GROUP );
		}

		if ( is_wp_error( $terms ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy ) {
			foreach ( $terms as $term ) {
				if ( $term->taxonomy === $taxonomy ) {
					$this->wp_api->wp_update_term_count( $term->term_taxonomy_id, $taxonomy );
				}
			}
		}
	}
}
