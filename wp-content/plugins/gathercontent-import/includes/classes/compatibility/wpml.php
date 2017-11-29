<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Compatibility;
use GatherContent\Importer\Sync\Pull;
use GatherContent\Importer\Sync\Push;
use GatherContent\Importer\Base;

/**
 * Handles adding Compatibility for WPML
 *
 * @since 3.1.7
 */
class WPML extends Base {

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.1.7
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_filter( 'gc_new_wp_post_data', array( $this, 'maybe_transform_meta_for_wpml' ), 10, 2 );
		add_filter( 'gc_update_wp_post_data', array( $this, 'maybe_transform_meta_for_wpml' ), 10, 2 );
		add_filter( 'gc_config_taxonomy_field_value_updated', array( $this, 'maybe_update_taxonomy_item_value_from_wpml' ), 10, 4 );
	}

	/**
	 * Handles transforming multilingual taxonomy values from GC to WPML.
	 *
	 * If the GC item uses the foreign language term name, then this will need to be unhooked.
	 *
	 * @since  3.1.7
	 *
	 * @param  array $post_data The post data to import/update.
	 * @param  Pull  $pull      The Pull object.
	 *
	 * @return array            The possibly modified post data array.
	 */
	public function maybe_transform_meta_for_wpml( $post_data, Pull $pull ) {
		if (
			! $this->wpml_installed()
			|| empty( $post_data['tax_input'] )
			|| empty( $post_data['ID'] )
			|| ! is_array( $post_data['tax_input'] )
		) {
			return $post_data;
		}

		$lang = $this->get_post_lang( $post_data['ID'] );

		// If we can't find the post language, bail.
		if ( empty( $lang['language_code'] ) ) {
			return $post_data;
		}

		foreach ( $post_data['tax_input'] as $taxonomy => &$terms ) {
			foreach ( $terms as $index => $term ) {
				if ( is_numeric( $term ) ) {

					// Get the corresponding language term for this post.
					$lang_term = $this->get_lang_term( $term, $taxonomy, $lang['language_code'] );

					if ( ! empty( $lang_term ) ) {
						// If we found one, update the mapping.
						$terms[ $index ] = $lang_term;
					}
				}
			}
		}

		return $post_data;
	}

	/**
	 * Try to replace the foreign language term names with the default language term names,
	 * to allow a proper mapping to GC.
	 *
	 * If the GC item uses the foreign language term name, then this will need to be unhooked.
	 *
	 * @since  3.1.7
	 *
	 * @param  bool   $updated  Whether this value was updated.
	 * @param  string $taxonomy The taxonomy
	 * @param  array  $terms    The array of taxonomy terms for this post.
	 * @param  Push   $push     The Push object
	 *
	 * @return bool             Whether this value was updated.
	 */
	public function maybe_update_taxonomy_item_value_from_wpml( $updated, $taxonomy, $terms, Push $push ) {
		if (
			! $this->wpml_installed()
			|| empty( $terms )
			|| is_wp_error( $terms )
		) {
			return $updated;
		}

		$lang = $this->get_post_lang( $push->post->ID );

		// If we can't find the post language, bail.
		if ( empty( $lang['different_language'] ) ) {
			return $updated;
		}

		$default_language = $this->get_default_lang();

		// If we can't find the default (current) language, bail.
		if ( empty( $default_language ) ) {
			return $updated;
		}

		$new_terms = false;

		// Let's loop our terms and try to replace the language terms with the default language terms.
		foreach ( $terms as $index => $term ) {
			if ( isset( $term->term_id ) ) {
				$default_lang_term_id = $this->get_lang_term( $term->term_id, $taxonomy, $default_language );

				if (
					! $default_lang_term_id
					|| is_wp_error( $default_lang_term_id )
					|| absint( $term->term_id ) === absint( $default_lang_term_id )
				) {
					continue;
				}

				$default_lang_term = get_term_by( 'id', $default_lang_term_id, $taxonomy );

				if ( $default_lang_term ) {
					$terms[ $index ] = $default_lang_term;
					$new_terms = true;
				}
			}
		}

		// If we found some replaced terms...
		if ( $new_terms ) {

			// Then update the push item config.
			$term_names = wp_list_pluck( $terms, 'name' );
			$updated = $push->set_taxonomy_field_value_from_names( $term_names );
		}

		return $updated;
	}

	protected function wpml_installed() {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	protected function get_post_lang( $post_id ) {
		return apply_filters( 'wpml_post_language_details', null, $post_id );
	}

	protected function get_lang_term( $term_id, $taxonomy, $lang_code ) {
		return apply_filters( 'wpml_object_id', $term_id, $taxonomy, false, $lang_code );
	}

	protected function get_default_lang() {
		return apply_filters( 'wpml_current_language', null );
	}

}
