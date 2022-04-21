<?php

class WPML_Term_Custom_Field_Setting extends WPML_Custom_Field_Setting {

	/**
	 * @return string
	 */
	protected function get_state_array_setting_index() {
		return WPML_Term_Custom_Field_Setting_Keys::get_state_array_setting_index();
	}

	/**
	 * @return string
	 */
	protected function get_unlocked_setting_index() {
		return WPML_Term_Custom_Field_Setting_Keys::get_unlocked_setting_index();
	}

	/**
	 * @return string
	 */
	protected function get_setting_prefix() {
		return WPML_Term_Custom_Field_Setting_Keys::get_setting_prefix();
	}

	/**
	 * @return  string[]
	 */
	protected function get_excluded_keys() {
		return WPML_Term_Custom_Field_Setting_Keys::get_excluded_keys();
	}
}
