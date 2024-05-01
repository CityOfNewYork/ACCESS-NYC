<?php

class WPML_ACF_Migrate_Option_Page_Strings implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {
	const CONTEXT = "ACF Option";
	const OPTION_PREFIX = "options";
	private $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function add_hooks() {
		if ( $this->revert_should_run() ) {
			$this->revert_options_page_handling();
		}
	}

	/**
	 * Function moves ACF Option Pages' values translations from icl tables back to wp_options (where ACF stored this
	 * before ACFML 1.2)
	 */
	private function revert_options_page_handling() {
		$original_strings = $this->get_all_original_strings();
		if ( $original_strings ) {
			foreach ( $original_strings as $original_string ) {
				if ( $this->is_original_string_in_options_table( $original_string ) ) {
					$string_translations = $this->get_original_string_translations( $original_string );
					if ( $string_translations ) {
						foreach ( $string_translations as $string_translation ) {
							$this->move_translation( $string_translation, $original_string );
						}
					}
				}
				$this->all_string_translations_moved( $original_string );
			}
		} else { // no more strings in icl_strings so migration is done or user actually didn't translate any string
			update_option( 'acfml_options_page_revert_done', true );
		}
	}

	private function revert_should_run() {
		return false == get_option( 'acfml_options_page_revert_done' );
	}

	private function get_all_original_strings() {
		$original_strings_query = "SELECT id, name, value FROM {$this->wpdb->prefix}icl_strings WHERE context = %s";
		$original_strings_query = $this->wpdb->prepare( $original_strings_query, self::CONTEXT );
		$original_strings = $this->wpdb->get_results( $original_strings_query );

		return $original_strings;
	}

	private function is_original_string_in_options_table( $original_string ) {
		$original_wp_option_name = self::OPTION_PREFIX . "_" . $original_string->name;
		$original_wp_option_value = get_option( $original_wp_option_name );

		return $original_string->value === $original_wp_option_value;
	}

	private function get_original_string_translations( $original_string ) {
		$string_translations_query = "SELECT id, value, status, language FROM {$this->wpdb->prefix}icl_string_translations WHERE string_id = %d";
		$string_translations_query = $this->wpdb->prepare( $string_translations_query, $original_string->id );
		$string_translations = $this->wpdb->get_results( $string_translations_query );

		return $string_translations;
	}

	private function move_translation( $string_translation, $original_string ) { // from icl_string_translations to wp_options
		if ( $string_translation->status == ICL_TM_COMPLETE ) {
			$translated_wp_options_name = self::OPTION_PREFIX . "_" . $string_translation->language . "_" . $original_string->name;
			update_option( $translated_wp_options_name, $string_translation->value );
			// string translation have to be deleted as well
			$this->wpdb->delete( $this->wpdb->prefix . 'icl_string_translations',
				array( 'id' => $string_translation->id ),
				array( '%d' ) );
		}
	}

	private function all_string_translations_moved( $original_string ) {
		// each string have to be deleted from icl_strings
		$this->wpdb->delete( $this->wpdb->prefix . 'icl_strings',
			array( 'id' => $original_string->id ),
			array( '%d' ) );
	}
}