<?php

use \ACFML\Repeater\Shuffle\Strategy;

/**
 * @author OnTheGo Systems
 */
class WPML_ACF_Dependencies_Factory {
	private $options_page;
	private $requirements;
	private $editor_hooks;
	private $display_translated;
	private $worker;
	private $duplicated_post;
	private $custom_fields_sync;
	private $location_rules;
	private $attachments;
	private $field_settings;
	private $pro;
	private $annotations;
	private $xliff;
	private $blocks;
	/**
	 * @var WPML_ACF_Repeater_Shuffle
	 */
	private $repeater_shuffle;
	private $field_groups;

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

	public function create_requirements() {
		if ( ! $this->requirements ) {
			$this->requirements = new WPML_ACF_Requirements();
		}

		return $this->requirements;
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

	public function create_custom_fields_sync() {
		if ( ! $this->custom_fields_sync ) {
			$this->custom_fields_sync = new WPML_ACF_Custom_Fields_Sync();
		}

		return $this->custom_fields_sync;
	}

	public function create_location_rules() {
		if ( ! $this->location_rules ) {
			$this->location_rules = new WPML_ACF_Location_Rules();
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

	public function create_blocks() {
		if ( ! $this->blocks ) {
			$this->blocks = new WPML_ACF_Blocks();
		}

		return $this->blocks;
	}

	/**
	 * @param $strategy
	 *
	 * @return WPML_ACF_Repeater_Shuffle
	 */
	public function create_repeater_shuffle( Strategy $strategy ) {
		if ( ! $this->repeater_shuffle ) {
			$this->repeater_shuffle = new WPML_ACF_Repeater_Shuffle( $strategy );
		}

		return $this->repeater_shuffle;
	}

	public function create_field_groups() {
		if ( ! $this->field_groups ) {
			$this->field_groups = new WPML_ACF_Field_Groups( $this->get_sitepress() );
		}

		return $this->field_groups;
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
}
