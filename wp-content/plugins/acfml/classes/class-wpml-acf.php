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

	/**
	 * Hook fires when WPML is loaded.
	 */
	public function wpml_loaded() {
		$this->init_options_page();
		$this->init_field_groups();
		$this->init_acf_xliff();
		$this->init_acf_pro();
		$this->init_acf_field_annotations();
		$this->init_custom_fields_synchronisation_handler();
		$this->init_acf_location_rules();
		$this->init_acf_attachments();
		$this->init_acf_field_settings();
		$this->init_acf_blocks();
		$this->init_acf_repeater_shuffle();
	}

	private function is_acf_active() {
		$active = false;

		$active_plugins = get_option( 'active_plugins' );

		$active_network_plugins = array();
		if ( function_exists( 'wp_get_active_network_plugins' ) ) {
			$active_network_plugins = wp_get_active_network_plugins();
		}

		$all_plugins = array_merge( $active_plugins, $active_network_plugins );

		if ( is_array( $all_plugins ) ) {
			foreach ( $all_plugins as $plugin ) {
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

	/**
	 * Initiates class for handling changes in order of fields inside repeater field.
	 */
	private function init_acf_repeater_shuffle() {
		global $pagenow;
		$is_repeater_update_on_term_edit  = isset( $_REQUEST['action'] ) && 'editedtag' === $_REQUEST['action'] && isset( $_REQUEST['acf'] );
		$is_repeater_display_on_term_edit = isset( $pagenow ) && 'term.php' === $pagenow;
		$is_repeater_update_on_post_edit  = isset( $_REQUEST['action'] ) && 'editpost' === $_REQUEST['action'] && isset( $_REQUEST['acf'] );
		$is_repeater_display_on_post_edit = isset( $pagenow ) && 'post.php' === $pagenow;
		if ( $is_repeater_update_on_term_edit || $is_repeater_display_on_term_edit ) {
			if ( isset( $_REQUEST['taxonomy'] ) ) {
				$shuffled = new \ACFML\Repeater\Shuffle\Term( $_REQUEST['taxonomy'] );
			}
		} elseif ( $is_repeater_update_on_post_edit || $is_repeater_display_on_post_edit ) {
			$shuffled = new \ACFML\Repeater\Shuffle\Post();
		}

		if ( isset( $shuffled ) ) {
			$wpml_acf_repeater_shuffle = $this->dependencies_factory->create_repeater_shuffle( $shuffled );
			$wpml_acf_repeater_shuffle->register_hooks();
		}
	}

	private function init_acf_pro() {
		$this->dependencies_factory->create_pro();
	}

	/**
	 * Adds code for handling ACF field annotations.
	 */
	private function init_acf_field_annotations() {
		$field_annotations = $this->dependencies_factory->create_field_annotations();
		$field_annotations->register_hooks();
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
		$wpml_acf_field_settings = $this->dependencies_factory->create_field_settings();
		$wpml_acf_field_settings->add_hooks();
	}

	private function init_field_groups() {
		$WPML_ACF_Field_Groups = $this->dependencies_factory->create_field_groups();
		$WPML_ACF_Field_Groups->register_hooks();
	}

	/**
	 * Initializes class handling logic for compatibility with ACF options pages.
	 */
	private function init_options_page() {
		$wpml_acf_options_page = $this->dependencies_factory->create_options_page();
		$wpml_acf_options_page->register_hooks();
	}

	/**
	 * @return bool
	 */
	private function can_create_xliff() {
		return defined( 'WPML_ACF_XLIFF_SUPPORT' ) && WPML_ACF_XLIFF_SUPPORT && is_admin() && class_exists( 'acf' );
	}
}
