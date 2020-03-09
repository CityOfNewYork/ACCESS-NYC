<?php

namespace WPML\ST\MO\Hooks;

use WPML_ST_Translations_File_Locale;
use WPML\ST\MO\LoadedMODictionary;
use WPML\ST\MO\File\Manager;


class LoadTextDomain implements \IWPML_Action {

	/** @var Manager $file_manager */
	private $file_manager;

	/** @var WPML_ST_Translations_File_Locale $file_locale */
	private $file_locale;

	/** @var LoadedMODictionary $loaded_mo_dictionary */
	private $loaded_mo_dictionary;

	/** @var array $loaded_domains */
	private $loaded_domains = [];

	public function __construct(
		Manager $file_manager,
		WPML_ST_Translations_File_Locale $file_locale,
		LoadedMODictionary $loaded_mo_dictionary
	) {
		$this->file_manager         = $file_manager;
		$this->file_locale          = $file_locale;
		$this->loaded_mo_dictionary = $loaded_mo_dictionary;
	}

	public function add_hooks() {
		add_filter( 'override_load_textdomain', [ $this, 'overrideLoadTextDomain' ], 10, 3 );
		add_action( 'wpml_language_has_switched', [ $this, 'languageHasSwitched' ] );
	}

	/**
	 * When a MO file is loaded, we override the process to load
	 * the custom MO file before.
	 *
	 * That way, the custom MO file will be merged into the subsequent
	 * native MO files and the custom MO translations will always
	 * overwrite the native ones.
	 *
	 * This gives us the ability to build partial custom MO files
	 * with only the modified translations.
	 *
	 * @param bool   $override Whether to override the .mo file loading. Default false.
	 * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
	 * @param string $mofile   Path to the MO file.
	 *
	 * @return bool
	 */
	public function overrideLoadTextDomain( $override, $domain, $mofile ) {
		$this->loaded_mo_dictionary->addFile( $domain, $mofile );

		$locale = $this->file_locale->get( $mofile, $domain );

		if ( $this->isCustomMOLoaded( $domain ) ) {
			return $override;
		}

		$wpml_mofile = $this->file_manager->get( $domain, $locale );

		if ( $wpml_mofile && $wpml_mofile !== $mofile ) {
			$this->loadCustomMOFile( $domain, $wpml_mofile );
		}

		$this->setCustomMOLoaded( $domain );

		return $override;
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function isCustomMOLoaded( $domain ) {
		return in_array( $domain, $this->loaded_domains, true );
	}

	/**
	 * @param string $domain
	 * @param string $wpml_mofile
	 */
	private function loadCustomMOFile( $domain, $wpml_mofile ) {
		remove_filter( 'override_load_textdomain', [ $this, 'overrideLoadTextDomain' ], 10, 3 );
		load_textdomain( $domain, $wpml_mofile );
		add_filter( 'override_load_textdomain', [ $this, 'overrideLoadTextDomain' ], 10, 3 );
	}

	/**
	 * @param string $domain
	 */
	private function setCustomMOLoaded( $domain ) {
		$this->loaded_domains[] = $domain;
	}

	public function languageHasSwitched() {
		$this->loaded_domains = [];
	}
}
