<?php

class WPML_Language_Records {

	private $wpdb;

	private $languages;

	/** @var null|array $locale_lang_map */
	private $locale_lang_map;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function is_valid( $code ) {
		if ( ! $this->languages ) {
			$this->load();
		}

		return in_array( $code, $this->languages );
	}

	private function load() {
		$this->languages = $this->wpdb->get_col( "SELECT code FROM {$this->get_table()}" );
	}

	/**
	 * @param $lang_code
	 *
	 * @return string|null
	 */
	public function get_locale( $lang_code ) {
		$this->init_locale_lang_map();
		$locale = array_search( $lang_code, $this->locale_lang_map, true );
		return $locale ? $locale : null;
	}

	/**
	 * @param string $locale
	 *
	 * @return string|null
	 */
	public function get_language_code( $locale ) {
		$this->init_locale_lang_map();
		return isset( $this->locale_lang_map[ $locale ] ) ? $this->locale_lang_map[ $locale ] : null;
	}

	private function init_locale_lang_map() {
		if ( null === $this->locale_lang_map ) {
			$this->locale_lang_map = array();

			$sql    = "SELECT default_locale, code FROM {$this->get_table()}";
			$rowset = $this->wpdb->get_results( $sql );

			foreach ( $rowset as $row ) {
				$this->locale_lang_map[ $row->default_locale ?: $row->code ] = $row->code;
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_locale_lang_map() {
		$this->init_locale_lang_map();
		return $this->locale_lang_map;
	}

	private function get_table() {
		return $this->wpdb->prefix . 'icl_languages';
	}
}
