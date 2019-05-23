<?php

class WPML_TM_Translation_Roles_Section implements IWPML_TM_Admin_Section {
	const SLUG = 'translators';

	/** @var WPML_Translator_Settings_Interface $translator_settings */
	private $translator_settings;

	/** @var WPML_Translation_Manager_Settings $translation_manager_settings */
	private $translation_manager_settings;

	public function __construct(
		WPML_Translation_Manager_Settings $translation_manager_settings,
		WPML_Translator_Settings_Interface $translator_settings )
	{
		$this->translation_manager_settings = $translation_manager_settings;
		$this->translator_settings = $translator_settings;
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return self::SLUG;
	}

	/**
	 * @inheritDoc
	 */
	public function get_capabilities() {
		return array( WPML_Manage_Translations_Role::CAPABILITY, 'manage_options' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_caption() {
		return current_user_can( 'manage_options' ) ?
			__( 'Translation Roles', 'wpml-translation-management' ) :
			__( 'Translators', 'wpml-translation-management' );

	}

	/**
	 * @inheritDoc
	 */
	public function get_callback() {
		return array( $this, 'render' );
	}

	/**
	 * @inheritDoc
	 */
	public function is_visible() {
		return true;
	}

	public function render() {
		echo $this->translator_settings->render();
		echo $this->translation_manager_settings->render();
	}
}
