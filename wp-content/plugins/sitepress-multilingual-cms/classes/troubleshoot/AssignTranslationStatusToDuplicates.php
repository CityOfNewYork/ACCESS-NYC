<?php

namespace WPML\Troubleshooting;

class AssignTranslationStatusToDuplicates {

	public static function run() {
		global $sitepress, $iclTranslationManagement;

		$active_language_codes = array_keys( $sitepress->get_active_languages() );
		$duplicated_posts      = self::get_duplicates();
		$updated_items         = 0;
		foreach ( $duplicated_posts as $original_post_id ) {
			$element_type             = 'post_' . get_post_type( $original_post_id );
			$trid                     = $sitepress->get_element_trid( $original_post_id, $element_type );
			$element_language_details = $sitepress->get_element_translations( $trid, $element_type );
			$item_updated             = false;
			foreach ( $active_language_codes as $code ) {
				if ( ! isset( $element_language_details[ $code ] ) ) {
					continue;
				}
				$element_translation = $element_language_details[ $code ];
				if ( ! isset( $element_translation, $element_translation->element_id ) || $element_translation->original ) {
					continue;
				}
				$translation = $iclTranslationManagement->get_element_translation( $element_translation->element_id,
					$code,
					$element_type );
				if ( ! $translation ) {
					$status_helper = wpml_get_post_status_helper();
					$status_helper->set_status( $element_translation->element_id, ICL_TM_DUPLICATE );
					$item_updated = true;
				}
			}
			if ( $item_updated ) {
				$updated_items ++;
			}
			if ( $updated_items >= 20 ) {
				break;
			}
		}

		return $updated_items;

	}

	/**
	 * @return array
	 */
	private static function get_duplicates() {
		global $wpdb;

		$duplicated_posts_sql = "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_icl_lang_duplicate_of' AND meta_value<>'' GROUP BY meta_value;";
		return $wpdb->get_col( $duplicated_posts_sql );
	}
}
