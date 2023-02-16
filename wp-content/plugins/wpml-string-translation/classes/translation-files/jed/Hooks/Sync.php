<?php

namespace WPML\ST\JED\Hooks;

use WPML\ST\TranslationFile\Sync\FileSync;
use WPML_ST_JED_Domain;
use WPML_ST_JED_File_Manager;
use WPML_ST_Script_Translations_Hooks;
use WPML_ST_Translations_File_Locale;

class Sync implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	/** @var FileSync */
	private $fileSync;

	public function __construct( FileSync $fileSync ) {
		$this->fileSync = $fileSync;
	}

	public function add_hooks() {
		add_filter(
			'load_script_translation_file',
			[ $this, 'syncCustomJedFile' ],
			WPML_ST_Script_Translations_Hooks::PRIORITY_OVERRIDE_JED_FILE - 1,
			3
		);
	}

	/**
	 * @param string|false $jedFile Path to the translation file to load. False if there isn't one.
	 * @param string       $handler Name of the script to register a translation domain to.
	 * @param string       $domain  The text domain.
	 */
	public function syncCustomJedFile( $jedFile, $handler, $domain ) {
		$this->fileSync->sync( $jedFile, WPML_ST_JED_Domain::get( $domain, $handler ) );

		return $jedFile;
	}
}
