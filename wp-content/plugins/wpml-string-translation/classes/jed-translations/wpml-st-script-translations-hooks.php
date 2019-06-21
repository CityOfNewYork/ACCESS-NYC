<?php

class WPML_ST_Script_Translations_Hooks implements IWPML_Action {

	/** @var WPML_ST_Translations_File_Dictionary $dictionary */
	private $dictionary;

	/** @var WPML_ST_JED_File_Manager $jed_file_manager */
	private $jed_file_manager;

	/** @var WPML_File $wpml_file */
	private $wpml_file;

	public function __construct(
		WPML_ST_Translations_File_Dictionary $dictionary,
		WPML_ST_JED_File_Manager $jed_file_manager,
		WPML_File $wpml_file
	) {
		$this->dictionary       = $dictionary;
		$this->jed_file_manager = $jed_file_manager;
		$this->wpml_file        = $wpml_file;
	}

	public function add_hooks() {
		add_filter( 'load_script_translation_file', array( $this, 'override_jed_file' ), 10, 3 );
	}

	/**
	 * @param string $filepath
	 * @param string $handler
	 * @param string $domain
	 *
	 * @return string
	 */
	public function override_jed_file( $filepath, $handler, $domain ) {
		if ( $filepath ) {
			$native_jed_missing = false;
			$locale             = $this->get_file_locale( $filepath, $domain );
		} else {
			$native_jed_missing = true;
			$locale             = get_locale();
		}

		$domain        = WPML_ST_JED_Domain::get( $domain, $handler );
		$wpml_filepath = $this->jed_file_manager->get( $domain, $locale );

		if ( $wpml_filepath ) {
			return $wpml_filepath;
		}

		if ( ( $native_jed_missing || $this->is_file_imported( $filepath ) )
		     && $this->jed_file_manager->add( $domain, $locale )
		) {
			$wpml_filepath = $this->jed_file_manager->get( $domain, $locale );

			if ( $wpml_filepath ) {
				return $wpml_filepath;
			}
		}

		return $filepath;
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
		$file_locale = new WPML_ST_Translations_File_Locale( $filepath, $domain );
		return $file_locale->get();
	}
}
