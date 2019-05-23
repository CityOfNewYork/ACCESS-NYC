<?php

class WPML_ST_JED_File_Manager {

	const SUB_DIRECTORY = 'wpml';

	/** @var WPML_ST_JED_Strings_Retrieve $strings */
	private $strings;

	/** @var WPML_ST_JED_File_Builder $builder */
	private $builder;

	/** @var WP_Filesystem_Direct $filesystem */
	private $filesystem;

	/** @var WPML_Language_Records $language_records */
	private $language_records;

	/** @var null|array $language_code_map */
	private $language_code_map;

	public function __construct(
		WPML_ST_JED_Strings_Retrieve $strings,
		WPML_ST_JED_File_Builder $builder,
		WP_Filesystem_Direct $filesystem,
		WPML_Language_Records $language_records
	) {
		$this->strings          = $strings;
		$this->builder          = $builder;
		$this->filesystem       = $filesystem;
		$this->language_records = $language_records;
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return bool
	 */
	public function add( $domain, $locale ) {
		if ( ! $this->maybe_create_subdir() ) {
			return false;
		}

		$lang_code   = $this->language_records->get_language_code( $locale );
		$strings     = $this->strings->get( $domain, $lang_code );
		$jed_content = $this->builder
			->set_language( $locale )
			->set_strings( $strings )
			->get_content();

		$filepath = $this->get_file_path( $domain, $locale );

		return $this->filesystem->put_contents( $filepath, $jed_content );
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return string|null
	 */
	public function get( $domain, $locale ) {
		$filepath = $this->get_file_path( $domain, $locale );

		if ( $this->filesystem->is_file( $filepath ) && $this->filesystem->is_readable( $filepath ) ) {
			return $filepath;
		}

		return null;
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 */
	public function remove( $domain, $locale ) {
		$this->filesystem->delete( $this->get_file_path( $domain, $locale ) );
	}

	/** @return bool */
	private function maybe_create_subdir() {
		$subdir = $this->get_subdir();

		if ( $this->filesystem->is_dir( $subdir ) && $this->filesystem->is_writable( $subdir ) ) {
			return true;
		}

		return $this->filesystem->mkdir( $subdir );
	}

	/** @return string */
	private function get_subdir() {
		return WP_LANG_DIR . '/' . self::SUB_DIRECTORY;
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return string
	 */
	private function get_file_path( $domain, $locale ) {
		return $this->get_subdir() . '/' . $locale . '-' . $domain . '.json';
	}
}
