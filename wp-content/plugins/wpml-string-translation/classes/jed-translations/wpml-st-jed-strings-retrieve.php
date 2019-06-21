<?php

class WPML_ST_JED_Strings_Retrieve extends WPML_ST_DB_Translation_Retrieve {

	/** @var array $plurals */
	private $plurals = array();

	/** @var array $strings */
	private $strings = array();

	/**
	 * @param $domain
	 * @param $language
	 *
	 * @return WPML_ST_JED_String[]
	 */
	public function get( $domain, $language ) {
		if ( ! in_array( $domain, $this->strings, true ) ) {
			$this->strings[ $domain ] = array();
			$this->load( $language, $domain );

			if ( ! empty( $this->loaded[ $domain ] ) ) {
				$this->build_plurals( $domain );
				$this->build_strings( $domain );
				$this->clear_temporary_data();
			}
		}

		return $this->strings[ $domain ];
	}

	/** @param string $domain */
	private function build_plurals( $domain ) {
		foreach ( $this->loaded[ $domain ] as $context => $dataset ) {

			foreach ( $dataset as $md5 => $data ) {
				$raw_original = isset( $data[2] ) ? $data[2] : null;

				if ( ! $raw_original ) { // no MO translation nor custom translation
					continue;
				}

				if ( preg_match( '/^(.+) \[plural ([0-9]+)\]$/', $raw_original, $matches ) ) {
					$original    = $matches[1];
					$index       = $matches[2];
					$translation = $data[1];

					$this->plurals[ $domain ][ $context ][ $original ][ $index ] = $translation;
					unset( $this->loaded[ $domain ][ $context ][ $md5 ] );
				}
			}
		}
	}

	/** @param string $domain */
	private function build_strings( $domain ) {
		foreach ( $this->loaded[ $domain ] as $context => $dataset ) {

			foreach ( $dataset as $md5 => $data ) {
				$original = isset( $data[2] ) ? $data[2] : null;

				if ( ! $original ) { // no MO translation nor custom translation
					continue;
				}

				$translations = array( $data[1] );

				$plurals = $this->get_plurals( $domain, $context, $original );

				if ( $plurals ) {
					$translations = array_merge( $translations, $plurals );
				}

				$this->strings[ $domain ][] = new WPML_ST_JED_String( $original, $translations, $context );
			}
		}
	}

	/**
	 * @param string $domain
	 * @param string $context
	 * @param string $original
	 *
	 * @return array
	 */
	private function get_plurals( $domain, $context, $original ) {
		if ( isset( $this->plurals[ $domain ][ $context ][ $original ] ) ) {
			sort( $this->plurals[ $domain ][ $context ][ $original ] );
			return $this->plurals[ $domain ][ $context ][ $original ];
		}

		return array();
	}

	private function clear_temporary_data() {
		$this->clear_cache();
		$this->plurals = array();
	}
}
