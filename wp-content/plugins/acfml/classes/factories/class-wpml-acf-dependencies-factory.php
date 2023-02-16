<?php

use \ACFML\FieldPreferences\TranslationJobs;
use \ACFML\Group_Scanner;
use \ACFML\MigrateBlockPreferences;
use \ACFML\Repeater\Shuffle\Strategy;
use \ACFML\FieldState;
use \ACFML\Tools\Export;
use \ACFML\Tools\Import;
use \ACFML\Tools\Local;

/**
 * @author OnTheGo Systems
 */
class WPML_ACF_Dependencies_Factory {
	private $options_page;
	private $editor_hooks;
	private $display_translated;
	private $worker;
	private $duplicated_post;
	private $custom_fields_sync;
	/** @var TranslationJobs|void */
	private $field_translation_jobs;
	private $location_rules;
	private $attachments;
	private $field_settings;
	private $pro;
	private $annotations;
	private $xliff;
	private $blocks;
	private $migrateBlockPreferences;
	private $groupScanner;
	/** @var WPML_ACF_Migrate_Option_Page_Strings|void */
	private $migrateOptionsPageStrings;
	/**
	 * @var \ACFML\FieldReferenceAdjuster|void
	 */
	private $field_adjuster;
	/**
	 * @var WPML_ACF_Repeater_Shuffle|void
	 */
	private $repeater_shuffle;
	private $field_groups;
	
	/**
	 * @var FieldState|void
	 */
	private $field_state;
	
	/**
	 * @var Export|void
	 */
	private $tools_export;
	
	/**
	 * @var Import|void
	 */
	private $tools_import;
	
	/**
	 * @var Local|void
	 */
	private $tools_local;

	/**
	 * @var WPML_ACF_Translatable_Groups_Checker|void
	 */
	private $translatable_groups_checker;

	/**
	 * WPML_ACF_Options_Page factory.
	 *
	 * @return WPML_ACF_Options_Page
	 */
	public function create_options_page() {
		if ( ! $this->options_page ) {
			$this->options_page = new WPML_ACF_Options_Page( $this->get_sitepress(), $this->create_worker() );
		}

		return $this->options_page;
	}

	public function create_editor_hooks() {
		if ( ! $this->editor_hooks ) {
			$this->editor_hooks = new WPML_ACF_Editor_Hooks();
		}

		return $this->editor_hooks;
	}

	public function create_display_translated() {
		if ( ! $this->display_translated ) {
			$this->display_translated = new WPML_ACF_Display_Translated();
		}

		return $this->display_translated;
	}

	public function create_worker() {
		if ( ! $this->worker ) {
			$this->worker = new WPML_ACF_Worker( $this->create_duplicated_post() );
		}

		return $this->worker;
	}

	public function create_duplicated_post() {
		if ( ! $this->duplicated_post ) {
			$this->duplicated_post = new WPML_ACF_Duplicated_Post();
		}

		return $this->duplicated_post;
	}
	
	/**
	 * @param Strategy $strategy
	 *
	 * @return WPML_ACF_Custom_Fields_Sync
	 */
	public function create_custom_fields_sync( Strategy $strategy ) {
		if ( ! $this->custom_fields_sync ) {
			$this->custom_fields_sync = new WPML_ACF_Custom_Fields_Sync( $this->create_field_state( $strategy ) );
		}

		return $this->custom_fields_sync;
	}
	
	/**
	 * @return TranslationJobs
	 */
	public function create_field_translation_jobs() {
		if ( ! $this->field_translation_jobs ) {
			$this->field_translation_jobs = new TranslationJobs();
		}
		return $this->field_translation_jobs;
	}
	
	/**
	 * @param Strategy $strategy
	 *
	 * @return FieldState
	 */
	public function create_field_state( Strategy $strategy ) {
		if ( ! $this->field_state ) {
			$this->field_state = new FieldState( $strategy );
		}
		return $this->field_state;
	}

	public function create_location_rules() {
		if ( ! $this->location_rules ) {
			$this->location_rules = new WPML_ACF_Location_Rules( $this->get_sitepress() );
		}

		return $this->location_rules;
	}

	public function create_attachments() {
		if ( ! $this->attachments ) {
			$this->attachments = new WPML_ACF_Attachments();
		}

		return $this->attachments;
	}

	public function create_field_settings() {
		if ( ! $this->field_settings ) {
			$this->field_settings = new WPML_ACF_Field_Settings( $this->get_iclTranslationManagement() );
		}

		return $this->field_settings;
	}

	public function create_pro() {
		if ( ! $this->pro ) {
			$this->pro = new WPML_ACF_Pro();
		}

		return $this->pro;
	}

	/**
	 * Returns WPML_ACF_Field_Annotations object.
	 *
	 * @return WPML_ACF_Field_Annotations
	 */
	public function create_field_annotations() {
		if ( ! $this->annotations ) {
			$this->annotations = new WPML_ACF_Field_Annotations( $this->create_options_page(), $this->create_field_settings() );
		}

		return $this->annotations;
	}

	public function create_xliff() {
		if ( ! $this->xliff ) {
			$this->xliff = new WPML_ACF_Xliff( $this->get_wpdb(), $this->get_sitepress() );
		}

		return $this->xliff;
	}

	/**
	 * Return WPML_ACF_Blocks instance.
	 *
	 * @return WPML_ACF_Blocks
	 */
	public function create_blocks() {
		if ( ! $this->blocks ) {
			$this->blocks = new WPML_ACF_Blocks( $this->get_wpml_post_translations() );
		}

		return $this->blocks;
	}

	/**
	 * @param Strategy $strategy
	 *
	 * @return WPML_ACF_Repeater_Shuffle
	 */
	public function create_repeater_shuffle( Strategy $strategy ) {
		if ( ! $this->repeater_shuffle ) {
			$this->repeater_shuffle = new WPML_ACF_Repeater_Shuffle( $strategy, $this->create_field_state( $strategy ) );
		}

		return $this->repeater_shuffle;
	}

	public function create_field_groups() {
		if ( ! $this->field_groups ) {
			$this->field_groups = new WPML_ACF_Field_Groups( $this->get_sitepress() );
		}

		return $this->field_groups;
	}

	/**
	 * WPML_ACF_Migrate_Option_Page_Strings factory.
	 *
	 * @return WPML_ACF_Migrate_Option_Page_Strings
	 */
	public function createMigrateOptionsPageStrings() {
		if ( ! $this->migrateOptionsPageStrings ) {
			$this->migrateOptionsPageStrings = new WPML_ACF_Migrate_Option_Page_Strings( $this->get_wpdb() );
		}
		return $this->migrateOptionsPageStrings;
	}

	/*
	 * @return \ACFML\FieldReferenceAdjuster
	 */
	public function create_field_adjuster() {
		if ( ! $this->field_adjuster ) {
			$this->field_adjuster = new ACFML\FieldReferenceAdjuster( $this->get_sitepress() );
		}
		return $this->field_adjuster;
	}

	public function createMigrateBlockPreferences() {
		if ( ! $this->migrateBlockPreferences ) {
			$this->migrateBlockPreferences = new MigrateBlockPreferences( $this->create_field_settings() );
		}
		return $this->migrateBlockPreferences;
	}
	
	/**
	 * @return Export
	 */
	public function create_tools_export() {
		if ( ! $this->tools_export ) {
			$this->tools_export = new Export();
		}
		return $this->tools_export;
	}
	
	/**
	 * @return Import
	 */
	public function create_tools_import() {
		if ( ! $this->tools_import ) {
			$this->tools_import = new Import();
		}
		return $this->tools_import;
	}
	
	/**
	 * @return Local
	 */
	public function create_tools_local() {
		if ( ! $this->tools_local ) {
			$this->tools_local = new Local( $this->create_field_settings() );
		}
		return $this->tools_local;
	}

	/**
	 * @return WPML_ACF_Translatable_Groups_Checker
	 */
	public function create_translatable_groups_checker() {
		if ( ! $this->translatable_groups_checker ) {
			$this->translatable_groups_checker = new WPML_ACF_Translatable_Groups_Checker();
		}
		return $this->translatable_groups_checker;
	}

	private function get_sitepress() {
		global $sitepress;

		return $sitepress;
	}

	private function get_iclTranslationManagement() {
		global $iclTranslationManagement;

		return $iclTranslationManagement;
	}

	private function get_wpdb() {
		global $wpdb;

		return $wpdb;
	}

	/**
	 * @return WPML_Post_Translation
	 */
	private function get_wpml_post_translations() {
		global $wpml_post_translations;
		return $wpml_post_translations;
	}
}
