<?php

class WPML_Term_Custom_Field_Setting_Keys {

	/**
	 * @return string
	 */
	public static function get_state_array_setting_index() {
		return WPML_TERM_META_SETTING_INDEX_PLURAL;
	}

	/**
	 * @return string
	 */
	public static function get_unlocked_setting_index() {
		return WPML_TERM_META_UNLOCKED_SETTING_INDEX;
	}

	/**
	 * @return string
	 */
	public static function get_setting_prefix() {
		return 'custom_term_fields_';
	}

	/**
	 * @return  string[]
	 */
	public static function get_excluded_keys() {
		return array();
	}
}
