<?php

class WPML_TM_MCS_Custom_Field_Settings_Menu_Factory {

	/** @var WPML_Custom_Field_Setting_Factory $setting_factory */
	private $setting_factory;

	/** @var WPML_UI_Unlock_Button $unlock_button */
	private $unlock_button;

	/** @var WPML_Custom_Field_Setting_Query_Factory $query_factory */
	private $query_factory;

	/**
	 * @return WPML_TM_MCS_Post_Custom_Field_Settings_Menu
	 */
	public function create_post() {
		return new WPML_TM_MCS_Post_Custom_Field_Settings_Menu(
			$this->get_setting_factory(),
			$this->get_unlock_button(),
			$this->get_query_factory()
		);
	}

	/**
	 * @return WPML_TM_MCS_Term_Custom_Field_Settings_Menu
	 */
	public function create_term() {
		return new WPML_TM_MCS_Term_Custom_Field_Settings_Menu(
			$this->get_setting_factory(),
			$this->get_unlock_button(),
			$this->get_query_factory()
		);
	}

	private function get_setting_factory() {
		global $iclTranslationManagement;

		if ( null === $this->setting_factory ) {
			$this->setting_factory                     = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );
			$this->setting_factory->show_system_fields = array_key_exists( 'show_system_fields', $_GET )
				? (bool) $_GET['show_system_fields'] : false;
		}

		return $this->setting_factory;
	}

	private function get_unlock_button() {
		if ( null === $this->unlock_button ) {
			$this->unlock_button = new WPML_UI_Unlock_Button();
		}

		return $this->unlock_button;
	}

	private function get_query_factory() {
		if ( null === $this->query_factory ) {
			$this->query_factory = new WPML_Custom_Field_Setting_Query_Factory();
		}

		return $this->query_factory;
	}
}