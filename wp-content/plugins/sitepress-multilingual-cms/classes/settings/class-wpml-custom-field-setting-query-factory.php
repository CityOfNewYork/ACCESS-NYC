<?php

class WPML_Custom_Field_Setting_Query_Factory {

	const TYPE_POSTMETA = 'postmeta';
	const TYPE_TERMMETA = 'termmeta';


	public function create( $type ) {
		global $wpdb;

		if ( self::TYPE_TERMMETA === $type ) {
			$excluded_keys = $this->get_excluded_term_meta_keys();
			$table         = $wpdb->termmeta;
		} else {
			$excluded_keys = $this->get_excluded_post_meta_keys();
			$table         = $wpdb->postmeta;
		}

		return new WPML_Custom_Field_Setting_Query( $wpdb, $excluded_keys, $table );
	}

	/**
	 * @return array
	 */
	private function get_excluded_post_meta_keys() {
		return $this->get_excluded_meta_keys(
			WPML_Post_Custom_Field_Setting_Keys::get_excluded_keys(),
			WPML_Post_Custom_Field_Setting_Keys::get_setting_prefix(),
			WPML_Post_Custom_Field_Setting_Keys::get_state_array_setting_index(),
			WPML_Post_Custom_Field_Setting_Keys::get_unlocked_setting_index()
		);
	}

	/**
	 * @return array
	 */
	private function get_excluded_term_meta_keys() {
		return $this->get_excluded_meta_keys(
			WPML_Term_Custom_Field_Setting_Keys::get_excluded_keys(),
			WPML_Term_Custom_Field_Setting_Keys::get_setting_prefix(),
			WPML_Term_Custom_Field_Setting_Keys::get_state_array_setting_index(),
			WPML_Term_Custom_Field_Setting_Keys::get_unlocked_setting_index()
		);
	}

	/**
	 * @param array  $hardcoded_excluded_keys
	 * @param string $settings_prefix
	 * @param string $settings_state_index
	 * @param string $settings_unlock_index
	 *
	 * @return array
	 */
	private function get_excluded_meta_keys( array $hardcoded_excluded_keys, $settings_prefix, $settings_state_index, $settings_unlock_index ) {
		/** @var TranslationManagement $tm_instance */
		$tm_instance = wpml_load_core_tm();

		/**
		 * @see WPML_Custom_Field_Setting::excluded() for the logic ran on a single key
		 */
		$read_only_keys  = isset( $tm_instance->settings[ $settings_prefix . 'read_only' ] )
			? $tm_instance->settings[ $settings_prefix . 'read_only' ] : array();
		$not_ignore_keys = $this->get_not_ignore_keys( $tm_instance, $settings_state_index );
		$unlocked_keys   = isset( $tm_instance->settings[ $settings_unlock_index ] )
			? $tm_instance->settings[ $settings_unlock_index ] : array();

		$read_only_and_ignored_keys                  = array_diff( $read_only_keys, $not_ignore_keys );
		$read_only_and_ignored_and_not_unlocked_keys = array_diff( $read_only_and_ignored_keys, $unlocked_keys );

		$excluded_keys = array_merge(
			$hardcoded_excluded_keys,
			$read_only_and_ignored_and_not_unlocked_keys
		);

		return $excluded_keys;
	}

	/**
	 * @param TranslationManagement $tm_settings
	 * @param string                $index
	 *
	 * @return array
	 */
	private function get_not_ignore_keys( TranslationManagement $tm_settings, $index ) {
		$statuses = isset( $tm_settings->settings[ $index ] ) ? $tm_settings->settings[ $index ] : array();

		foreach ( $statuses as $meta_key => $status ) {

			if ( WPML_IGNORE_CUSTOM_FIELD === (int) $status ) {
				unset( $statuses[ $meta_key ] );
			}
		}

		return array_keys( $statuses );
	}
}
