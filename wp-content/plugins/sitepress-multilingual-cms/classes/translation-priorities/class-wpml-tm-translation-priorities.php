<?php

/**
 * Class WPML_Translation_Priorities
 */
class WPML_TM_Translation_Priorities {

	const DEFAULT_TRANSLATION_PRIORITY_VALUE_SLUG = 'optional';
	const TAXONOMY                                = 'translation_priority';

	public function get_values() {
		return get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
			)
		);
	}

	/**
	 * @return int
	 */
	public function get_default_value_id() {
		return (int) self::get_default_term()->term_id;
	}

	/**
	 * @return WP_Term
	 */
	public static function get_default_term() {
		$term = get_term_by( 'slug', self::DEFAULT_TRANSLATION_PRIORITY_VALUE_SLUG, self::TAXONOMY );
		if ( ! $term ) {
			$term = new WP_Term( (object) [ 'term_id' => 0 ] );
		}

		return $term;
	}


	/**
	 * @param int    $term_taxonomy_id
	 * @param string $original_name
	 * @param string $target_language
	 *
	 * @return int|bool
	 */
	public static function insert_missing_translation( $term_taxonomy_id, $original_name, $target_language ) {
		global $sitepress;

		$trid              = (int) $sitepress->get_element_trid( $term_taxonomy_id, 'tax_' . self::TAXONOMY );
		$term_translations = $sitepress->get_element_translations( $trid, 'tax_' . self::TAXONOMY );

		if ( ! isset( $term_translations[ $target_language ] ) ) {

			$sitepress->switch_locale( $target_language );

			$name            = __( $original_name, 'sitepress' );
			$slug            = WPML_Terms_Translations::term_unique_slug( sanitize_title( $name ), self::TAXONOMY, $target_language );
			$translated_term = wp_insert_term( $name, self::TAXONOMY, array( 'slug' => $slug ) );

			if ( $translated_term && ! is_wp_error( $translated_term ) ) {
				$sitepress->set_element_language_details( $translated_term['term_taxonomy_id'], 'tax_' . self::TAXONOMY, $trid, $target_language );

				return $translated_term['term_taxonomy_id'];
			}
		}

		return false;
	}

	public static function insert_missing_default_terms() {
		global $sitepress;

		$terms = array(
			array(
				'default' => __( 'Optional', 'sitepress' ),
				'en_name' => 'Optional',
			),
			array(
				'default' => __( 'Required', 'sitepress' ),
				'en_name' => 'Required',
			),
			array(
				'default' => __( 'Not needed', 'sitepress' ),
				'en_name' => 'Not needed',
			),
		);

		$default_language = $sitepress->get_default_language();
		$active_languages = $sitepress->get_active_languages();
		$current_language = $sitepress->get_current_language();
		unset( $active_languages[ $default_language ] );

		foreach ( $terms as $term ) {
			$sitepress->switch_locale( $default_language );
			$original_term = get_term_by( 'name', $term['default'], self::TAXONOMY, ARRAY_A );

			if ( ! $original_term ) {
				$original_term = wp_insert_term( $term['default'], self::TAXONOMY );
				$sitepress->set_element_language_details( $original_term['term_taxonomy_id'], 'tax_' . self::TAXONOMY, null, $default_language );
			}

			foreach ( $active_languages as $language ) {
				self::insert_missing_translation( $original_term['term_taxonomy_id'], $term['en_name'], $language['code'] );
			}
		}

		$sitepress->switch_locale( $current_language );
	}

	public static function insert_missing_term_relationship() {
		global $wpdb;
		$term = get_term_by( 'slug', self::DEFAULT_TRANSLATION_PRIORITY_VALUE_SLUG, self::TAXONOMY );
		if ( ! $term ) {
			return;
		}

		$postIds = $wpdb->get_col(
			"
               SELECT ID
               FROM {$wpdb->posts}
               WHERE post_type IN ('post', 'page') 
               AND post_status NOT IN ('inherit', 'auto-draft', 'trash')
            "
		);
		if ( count( $postIds ) === 0 ) {
			return;
		}

		foreach ( $postIds as $postId ) {
			$existingTermRel = $wpdb->get_var(
				$wpdb->prepare(
					"
                        SELECT object_id
                        FROM {$wpdb->term_relationships}
                        WHERE object_id = %d AND term_taxonomy_id = %d
                        LIMIT 1
                    ",
					$postId,
					$term->term_taxonomy_id
				)
			);
			if ( $existingTermRel ) {
				continue;
			}
			$wpdb->insert(
				$wpdb->term_relationships,
				[
					'object_id'        => $postId,
					'term_taxonomy_id' => $term->term_taxonomy_id,
					'term_order'       => 0,
				],
				[ '%d', '%d', '%d' ]
			);
		}
	}
}
