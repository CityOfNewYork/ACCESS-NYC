<?php

class WPML_ST_Script_Translations_Hooks implements IWPML_Action {

	/**
	 * With default priority of 10 our custom WPML JED file can be loaded before the default one.
	 * It happens at least with WooCommerce wc-admin-app script.
	 */
	const PRIORITY_OVERRIDE_JED_FILE = PHP_INT_MAX;

	/** @var WPML_ST_Translations_File_Dictionary $dictionary */
	private $dictionary;

	/** @var WPML_ST_JED_File_Manager $jed_file_manager */
	private $jed_file_manager;

	/** @var WPML_File $wpml_file */
	private $wpml_file;

	/** @var WPML_ST_JED_File_Builder $jed_file_builder */
	private $jed_file_builder;

	public function __construct(
		WPML_ST_Translations_File_Dictionary $dictionary,
		WPML_ST_JED_File_Manager $jed_file_manager,
		WPML_File $wpml_file,
		WPML_ST_JED_File_Builder $jed_file_builder
	) {
		$this->dictionary       = $dictionary;
		$this->jed_file_manager = $jed_file_manager;
		$this->wpml_file        = $wpml_file;
		$this->jed_file_builder = $jed_file_builder;
	}

	public function add_hooks() {
		add_filter( 'load_script_translation_file', array( $this, 'override_jed_file' ), self::PRIORITY_OVERRIDE_JED_FILE, 3 );
	}

	/**
	 * @param string $filepath
	 * @param string $handler
	 * @param string $domain
	 *
	 * @return string|false
	 */
	public function override_jed_file( $filepath, $handler, $domain ) {
		$wpml_filepath = null;

		if ( false === $filepath ) {
			// When no filepath can be found in `load_script_textdomain`, it passes `false`.
			// This is the last fallback in the logic.
			// At that stage, we can return the WPML JED file if it exists.
			$locale        = determine_locale();
			$domain        = WPML_ST_JED_Domain::get( $domain, $handler );
			$wpml_filepath = $this->jed_file_manager->get( $domain, $locale );
		} elseif ( is_readable( $filepath ) ) {
			// If the filepath is a string, and is readable, it means WP would use it.
			// At this stage, we can apply our merge process and return the WPML JED file if it exists.
			$locale        = $this->get_file_locale( $filepath, $domain );
			$domain        = WPML_ST_JED_Domain::get( $domain, $handler );
			$wpml_filepath = $this->jed_file_manager->get( $domain, $locale );
			$wpml_filepath = $this->maybe_update_custom_file_with_new_native_translations( $wpml_filepath, $filepath, $domain, $locale );
		}

		return $wpml_filepath ?: $filepath;
	}

	/**
	 * If the native file is newer than the custom one, it means the native file
	 * has been updated, possibly with new string translations.
	 * As the JED file is unique and includes all translations, we need to merge
	 * these new translations in the custom JED file.
	 *
	 * This process is shortcutting the full translation import process, so it's more
	 * versatile/easy/quick to run it on the fly when the translation file is loaded.
	 *
	 * @param string|null $wpml_filepath
	 * @param string      $filepath
	 * @param string      $domain
	 * @param string      $locale
	 *
	 * @return string|null
	 */
	private function maybe_update_custom_file_with_new_native_translations( $wpml_filepath, $filepath, $domain, $locale ) {
		if ( $wpml_filepath && $this->is_original_newer( $filepath, $wpml_filepath ) ) {
			$jed_content = $this->jed_file_builder->merge_files( $filepath, $wpml_filepath );

			if ( $jed_content ) {
				$new_wpml_filepath = $this->jed_file_manager->write( $domain, $locale, $jed_content );

				if ( $new_wpml_filepath ) {
					$wpml_filepath = $new_wpml_filepath;
				}
			}
		}

		return $wpml_filepath;
	}

	/**
	 * @param string $filepath
	 *
	 * @return bool
	 */
	private function is_file_imported( $filepath ) {
		$relative_path = $this->wpml_file->get_relative_path( $filepath );
		$file          = $this->dictionary->find_file_info_by_path( $relative_path );
		$statuses      = array( WPML_ST_Translations_File_Entry::IMPORTED, WPML_ST_Translations_File_Entry::FINISHED );

		return $file && in_array( $file->get_status(), $statuses, true );
	}

	/**
	 * @param string $filepath
	 * @param string $domain
	 *
	 * @return string
	 */
	private function get_file_locale( $filepath, $domain ) {
		return \WPML\Container\make( \WPML_ST_Translations_File_Locale::class )->get( $filepath, $domain );
	}

	/**
	 * @param string $original_filepath
	 * @param string $wpml_filepath
	 *
	 * @return bool
	 */
	private function is_original_newer( $original_filepath, $wpml_filepath ) {
		return $this->wpml_file->get_file_modified_timestamp( $original_filepath ) > $this->wpml_file->get_file_modified_timestamp( $wpml_filepath );
	}
}
