<?php

class WPML_ST_Translations_File_Scan_Factory {
	private $dictionary;
	private $notices;
	private $queue;
	/** @var WPML_Theme_Localization_Type */
	private $localization_type;
	private $storage;
	private $wpml_file;
	private $find_aggregate;

	public function check_core_dependencies() {
		global $wpdb;
		$string_index_check = new WPML_ST_Upgrade_String_Index( $wpdb );
		$scan_ui_block      = new WPML_ST_Translations_File_Scan_UI_Block( $this->create_localization_type(), wpml_get_admin_notices() );
		if ( ! $string_index_check->is_uc_domain_name_context_index_unique() ) {
			$scan_ui_block->block_ui();

			return false;
		}

		$scan_ui_block->unblock_ui();

		if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'get_plugins' ) ) {
			$file = ABSPATH . 'wp-admin/includes/plugin.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			} else {
				return false;
			}
		}

		$wpml_file = $this->get_wpml_file();

		return method_exists( $wpml_file, 'get_relative_path' );
	}

	/**
	 * @return array
	 */
	public function create_hooks() {
		$localization_type = $this->create_localization_type();
		if ( $localization_type->get_use_st_and_no_mo_files_value() !== (int) $localization_type->get_theme_localization_type() ) {
			return array();
		}

		return array(
			'stats-update'         => $this->get_stats_update(),
			'string-status-update' => $this->get_string_status_update(),
			'mo-file-registration' => $this->get_translations_file_registration(),
			'scanning-hooks'       => new WPML_ST_Translations_File_Scan_Hooks( $this->create_queue(), $this->create_dictionary(), $this->get_wpml_file() ),
		);
	}

	/**
	 * @return WPML_ST_Translations_File_Queue
	 */
	private function create_queue() {
		if ( ! $this->queue ) {
			global $wpdb;

			$charset_filter_factory = new WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory( $wpdb );

			$this->queue = new WPML_ST_Translations_File_Queue(
				$this->create_dictionary(),
				new WPML_ST_Translations_File_Scan( $charset_filter_factory ),
				$this->create_storage(),
				new WPML_Language_Records( $wpdb ),
				$this->get_scan_limit(),
				new WPML_Transient()
			);
		}

		return $this->queue;
	}

	/**
	 * @return WPML_ST_Translations_File_Scan_Storage
	 */
	private function create_storage() {
		if ( ! $this->storage ) {
			global $wpdb;

			$this->storage = new WPML_ST_Translations_File_Scan_Storage( $wpdb, new WPML_ST_Bulk_Strings_Insert( $wpdb ) );
		}

		return $this->storage;
	}

	/**
	 * @return WPML_ST_Translations_File_Dictionary
	 */
	private function create_dictionary() {
		if ( ! $this->dictionary ) {
			global $wpdb, $sitepress;


			$table_storage = new WPML_ST_Translations_File_Dictionary_Storage_Table( $wpdb );

			$st_upgrade = new WPML_ST_Upgrade( $sitepress );
			if ( $st_upgrade->has_command_been_executed( 'WPML_ST_Upgrade_MO_Scanning') ) {
				$table_storage->add_hooks();
			}

			$this->dictionary = new WPML_ST_Translations_File_Dictionary( $table_storage );
		}

		return $this->dictionary;
	}

	/**
	 * @return WPML_Theme_Localization_Type
	 */
	private function create_localization_type() {
		if ( ! $this->localization_type ) {
			global $sitepress;
			$this->localization_type = new WPML_Theme_Localization_Type( $sitepress );
		}

		return $this->localization_type;
	}

	/**
	 * @return int
	 */
	private function get_scan_limit() {
		$limit = WPML_ST_Translations_File_Queue::DEFAULT_LIMIT;
		if ( defined( 'WPML_ST_MO_SCANNING_LIMIT' ) ) {
			$limit = WPML_ST_MO_SCANNING_LIMIT;
		}

		return $limit;
	}

	private function get_sitepress() {
		global $sitepress;

		return $sitepress;
	}

	private function get_wpml_wp_api() {
		$sitepress = $this->get_sitepress();
		if ( ! $sitepress ) {
			return new WPML_WP_API();
		}

		return $sitepress->get_wp_api();
	}

	private function get_wpml_file() {
		if ( ! $this->wpml_file ) {
			$this->wpml_file = new WPML_File( $this->get_wpml_wp_api(), new WP_Filesystem_Direct( null ) );
		}

		return $this->wpml_file;
	}

	/**
	 * @return WPML_ST_Translations_File_Registration
	 */
	private function get_translations_file_registration() {
		return new WPML_ST_Translations_File_Registration(
			$this->create_dictionary(),
			$this->get_wpml_file(),
			$this->get_aggregate_find_component(),
			$this->get_sitepress()->get_active_languages()
		);
	}

	/**
	 * @return WPML_ST_Translations_File_Component_Stats_Update_Hooks
	 */
	private function get_stats_update() {
		global $wpdb;

		return new WPML_ST_Translations_File_Component_Stats_Update_Hooks(
			new WPML_ST_Strings_Stats( $wpdb, $this->get_sitepress() )
		);
	}

	/**
	 * @return WPML_ST_Translations_File_Component_Details
	 */
	private function get_aggregate_find_component() {
		if ( null === $this->find_aggregate ) {
			$debug_backtrace = new WPML_Debug_BackTrace( $this->get_wpml_wp_api()->phpversion(), 0 );

			$this->find_aggregate = new WPML_ST_Translations_File_Component_Details(
				new WPML_ST_Translations_File_Components_Find_Plugin( $debug_backtrace ),
				new WPML_ST_Translations_File_Components_Find_Theme( $debug_backtrace, $this->get_wpml_file() ),
				$this->get_wpml_file()
			);
		}

		return $this->find_aggregate;
	}
	/**
	 * @return WPML_ST_Translations_File_String_Status_Update
	 */
	private function get_string_status_update() {
		global  $wpdb;
		$num_of_secondary_languages = count( $this->get_sitepress()->get_active_languages() ) - 1;
		$status_update = new WPML_ST_Translations_File_String_Status_Update( $num_of_secondary_languages, $wpdb );
		$status_update->add_hooks();

		return $status_update;
	}
}
