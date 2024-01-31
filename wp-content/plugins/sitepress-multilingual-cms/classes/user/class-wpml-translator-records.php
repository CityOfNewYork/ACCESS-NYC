<?php

class WPML_Translator_Records extends WPML_Translation_Roles_Records {

	/**
	 * @return string
	 */
	protected function get_capability() {
		return \WPML\LIB\WP\User::CAP_TRANSLATE;
	}

	/**
	 * @return array
	 */
	protected function get_required_wp_roles() {
		return array();
	}

	/**
	 * @param string $source_language
	 * @param array  $target_languages
	 * @param bool   $require_all_languages - Translator must have all target languages if true otherwise they need at least one.
	 *
	 * @return array
	 */
	public function get_users_with_languages( $source_language, $target_languages, $require_all_languages = true ) {
		$translators = $this->get_users_with_capability();

		$language_records       = new WPML_Language_Records( $this->wpdb );
		$language_pairs_records = new WPML_Language_Pair_Records( $this->wpdb, $language_records );

		$translators_with_langs = array();
		foreach ( $translators as $translator ) {
			$language_pairs_for_user = $language_pairs_records->get( $translator->ID );

			if ( isset( $language_pairs_for_user[ $source_language ] ) ) {
				$lang_count = 0;
				foreach ( $target_languages as $target_language ) {
					$lang_count += in_array( $target_language, $language_pairs_for_user[ $source_language ], true ) ? 1 : 0;
				}
				if (
					$require_all_languages && $lang_count === count( $target_languages ) ||
					! $require_all_languages && $lang_count > 0
				) {
					$translators_with_langs[] = $translator;
				}
			}
		}

		return $translators_with_langs;
	}

}
