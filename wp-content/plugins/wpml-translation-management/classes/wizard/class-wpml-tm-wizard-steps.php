<?php

class WPML_TM_Wizard_Steps implements IWPML_Action {

	const STORE_MODE_ACTION = 'wpml_tm_wizard_store_who_mode';
	const NONCE             = 'wpml_tm_wizard';

	/** @var WPML_Translation_Manager_Records $translation_manager_records */
	private $translation_manager_records;

	/** @var WPML_Translator_Records $translator_records */
	private $translator_records;

	/** @var WPML_TM_Translation_Services_Admin_Section_Factory $translation_services_factory */
	private $translation_services_factory;

	/** @var SitePress $sitepress */
	private $sitepress;

	private $language_pair_records;

	public function __construct(
		WPML_Translation_Manager_Records $translation_manager_records,
		WPML_Translator_Records $translator_records,
		WPML_TM_Translation_Services_Admin_Section_Factory $translation_services_factory,
		WPML_Language_Pair_Records $language_pair_records,
		SitePress $sitepress
	) {
		$this->translation_manager_records  = $translation_manager_records;
		$this->translator_records           = $translator_records;
		$this->translation_services_factory = $translation_services_factory;
		$this->language_pair_records        = $language_pair_records;
		$this->sitepress                    = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'wpml_wizard_fetch_tm_who_will_translate', array( $this, 'render_step' ) );
		add_filter( 'wpml_wizard_fetch_tm_translation_editor', array( $this, 'render_step' ) );
		add_filter( 'wpml_wizard_fetch_tm_summary', array( $this, 'render_step' ) );

		add_filter( 'wp_ajax_wpml_tm_wizard_done', array( $this, 'done' ) );
		add_filter( 'wp_ajax_' . self::STORE_MODE_ACTION, array( $this, 'store_mode' ) );
	}

	public function who_will_translate_step() {

		$translation_manager_settings = new WPML_Translation_Manager_Settings(
			new WPML_Translation_Manager_View(),
			$this->translation_manager_records
		);

		$translator_settings = new WPML_Translator_Settings(
			$this->translator_records,
			new WPML_Language_Collection( $this->sitepress, array_keys( $this->sitepress->get_active_languages() ) ),
			$this->sitepress->get_default_language()
		);

		$step = new WPML_TM_Wizard_Who_Will_Translate_Step(
			wp_get_current_user(),
			$translation_manager_settings,
			$translator_settings,
			$this->translation_services_factory,
			get_option( WPML_TM_Wizard_Options::WHO_WILL_TRANSLATE_MODE, array() )
		);

		return $step;
	}


	public function render_step( $content ) {
		$step_slug = $this->get_step_slug();

		$this->save_current_step( $step_slug );

		$step = $this->get_step( $step_slug );

		return $step->render();
	}

	public function get_step( $step_slug ) {
		switch ( $step_slug ) {

			case 'tm_who_will_translate':
				return $this->who_will_translate_step();

			case 'tm_translation_editor':
				$tm_strings_factory = new WPML_TM_Scripts_Factory();

				$tm_settings = $this->get_tm_settings_with_reset_defaults();

				return new WPML_TM_Wizard_Translation_Editor_Step(
					$tm_strings_factory->create_ate(),
					isset( $tm_settings['doc_translation_method'] ) ? $tm_settings['doc_translation_method'] : ''
				);

			case 'tm_summary':
				return new WPML_TM_Wizard_Summary_Step(
					$this->translator_records,
					get_option( WPML_TM_Wizard_Options::WHO_WILL_TRANSLATE_MODE, array() ),
					$this->get_active_translation_service()
				);
		}
	}

	private function get_tm_settings_with_reset_defaults() {
		$tm_settings = $this->sitepress->get_setting( 'translation-management', array() );
		$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_GLOBAL_USE_NATIVE ]        = false;
		$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ] = array();
		$this->sitepress->set_setting( 'translation-management', $tm_settings, true );
		return $tm_settings;
	}

	public function done() {
		$who_will_translate_mode = get_option( WPML_TM_Wizard_Options::WHO_WILL_TRANSLATE_MODE, array() );

		// We need to delete any Translators, Translation Managers or the translation service
		// if the user didn't select that mode in the end. eg They changed their mind while
		// running the wizard.

		if ( 'false' === $who_will_translate_mode['user'] ) {
			$this->translator_records->delete_all();
		}

		if ( 'false' === $who_will_translate_mode['leaveChoice'] ) {
			$translation_managers = $this->translation_manager_records->get_users_with_capability();
			foreach( $translation_managers as $translation_manager ) {
				if ( ! user_can( $translation_manager->ID, 'manage_options') ) {
					$this->translation_manager_records->delete( $translation_manager->ID );
				}
			}
		}

		if ( isset( $who_will_translate_mode['translationService'] ) && 'false' === $who_will_translate_mode['translationService'] ) {
			$this->sitepress->set_setting( 'translation_service', false, true );
			do_action( 'wpml_tp_service_dectivated' );
		}

		update_user_option( get_current_user_id(), WPML_TM_Wizard_Options::WIZARD_COMPLETE_FOR_MANAGER, true );

		if ( 'true' === $who_will_translate_mode['onlyI'] ) {
			$this->set_current_user_to_translate_all_langs();
		}


		delete_option( WPML_TM_Wizard_Options::CURRENT_STEP );
		delete_option( WPML_TM_Wizard_Options::WHO_WILL_TRANSLATE_MODE );

		wp_send_json_success();
	}

	private function set_current_user_to_translate_all_langs() {
		$current_user = wp_get_current_user();
		$current_user->add_cap( WPML_Translator_Role::CAPABILITY );
		update_user_meta( $current_user->ID, WPML_TM_Wizard_Options::ONLY_I_USER_META, true );

		$this->language_pair_records->store(
			$current_user->ID,
			WPML_All_Language_Pairs::get( $this->sitepress )
		);

		do_action( 'wpml_tm_ate_synchronize_translators' );
	}

	private function set_current_user_as_translation_manager() {
		$current_user = wp_get_current_user();
		$current_user->add_cap( WPML_Manage_Translations_Role::CAPABILITY );
	}

	private function save_current_step( $step_slug ) {
		update_option( WPML_TM_Wizard_Options::CURRENT_STEP, $step_slug );
	}

	private function get_step_slug() {
		return str_replace( 'wpml_wizard_fetch_', '', current_filter() );
	}

	private function get_active_translation_service() {
		$active_service = $this->sitepress->get_setting( 'translation_service' );

		return $active_service ? new WPML_TP_Service( $active_service ) : null;
	}

	public function store_mode() {
		if ( wp_verify_nonce( $_POST['nonce'], self::NONCE ) ) {
			update_option( WPML_TM_Wizard_Options::WHO_WILL_TRANSLATE_MODE, (array) $_POST['mode'] );
		}
		wp_send_json_success();
	}
}