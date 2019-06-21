<?php

/**
 * Class WPML_ACF
 */
class WPML_ACF {
	/** @var \WPML_ACF_Dependencies_Factory */
	private $dependencies_factory;

	/**
	 * WPML_ACF constructor.
	 *
	 * @param \WPML_ACF_Dependencies_Factory $WPML_ACF_Dependencies_Factory
	 */
	public function __construct( WPML_ACF_Dependencies_Factory $WPML_ACF_Dependencies_Factory ) {
		$this->dependencies_factory = $WPML_ACF_Dependencies_Factory;
	}

	/**
	 * @return WPML_ACF_Worker
	 */
	public function init_worker() {
		if ( $this->is_acf_active() ) {
			$WPML_ACF_Options_Page = $this->dependencies_factory->create_options_page();

			global $wpdb;
			$WPML_ACF_Migrate_Option_Page_Strings = new WPML_ACF_Migrate_Option_Page_Strings( $wpdb );
			$WPML_ACF_Migrate_Option_Page_Strings->run_migration();

			add_action( 'wpml_loaded', array( $this, 'wpml_loaded' ) );

			$this->dependencies_factory->create_requirements();

			$WPML_ACF_Editor_Hooks = $this->dependencies_factory->create_editor_hooks();
			$WPML_ACF_Editor_Hooks->init_hooks();

			$this->dependencies_factory->create_display_translated();

			return $this->init_duplicated_post();
		}

		return null;
	}

	public function wpml_loaded() {
		$this->init_acf_xliff();
		$this->init_acf_pro();
		$this->init_acf_field_annotations();
		$this->init_custom_fields_synchronisation_handler();
		$this->init_acf_location_rules();
		$this->init_acf_attachments();
		$this->init_acf_field_settings();
		$this->init_acf_blocks();
	}

	private function is_acf_active() {
		$active = false;

		$active_plugins = get_option( 'active_plugins' );

		if ( is_array( $active_plugins ) ) {
			foreach ( $active_plugins as $plugin ) {
				if ( stristr( $plugin, '/acf.php' ) ) {
					$active = true;
					break;
				}
			}
		}

		return $active;

	}

	private function init_duplicated_post() {
		return $this->dependencies_factory->create_worker();
	}

	private function init_acf_xliff() {
		if ( $this->can_create_xliff() ) {
			$WPML_ACF_Xliff = $this->dependencies_factory->create_xliff();
			$WPML_ACF_Xliff->init_hooks();
		}
	}

	private function init_acf_blocks() {
		$WPML_ACF_Blocks = $this->dependencies_factory->create_blocks();
		$WPML_ACF_Blocks->init_hooks();
	}

	private function init_acf_pro() {
		$this->dependencies_factory->create_pro();
	}

	private function init_acf_field_annotations() {
		$this->dependencies_factory->create_field_annotations();
	}

	private function init_custom_fields_synchronisation_handler() {
		$WPML_ACF_Custom_Fields_Sync = $this->dependencies_factory->create_custom_fields_sync();
		$WPML_ACF_Custom_Fields_Sync->register_hooks();
	}

	private function init_acf_location_rules() {
		$this->dependencies_factory->create_location_rules();
	}

	private function init_acf_attachments() {
		$WPML_ACF_Attachments = $this->dependencies_factory->create_attachments();
		$WPML_ACF_Attachments->register_hooks();
	}

	private function init_acf_field_settings() {
		$this->dependencies_factory->create_field_settings();
	}

	/**
	 * @return bool
	 */
	private function can_create_xliff() {
		return defined( 'WPML_ACF_XLIFF_SUPPORT' ) && WPML_ACF_XLIFF_SUPPORT && is_admin() && class_exists( 'acf' );
	}
}
