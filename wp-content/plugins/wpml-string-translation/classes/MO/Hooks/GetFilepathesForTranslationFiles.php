<?php

namespace WPML\ST\MO\Hooks;

use WPML\ST\MO\LoadedMODictionary;
use WPML\ST\MO\Hooks\LoadTranslationFile;

class GetFilepathesForTranslationFiles implements \IWPML_Action {

	/** @var LoadedMODictionary $loadedDictionary */
	private $loadedDictionary;

	public function __construct(
		LoadedMODictionary $loadedDictionary
	) {
		$this->loadedDictionary = $loadedDictionary;
	}

	public function add_hooks() {
		add_filter( 'wpml_st_get_filepathes_for_translation_files', [ $this, 'getFilepathes' ], 10, 2 );
	}

	public function getFilepathes( ...$args ) {
		$domain = $args[0];
		$locale = $args[1];
		$files = $this->loadedDictionary->getFiles( $domain, $locale );
		$defaultPath = LoadTranslationFile::getDefaultWordPressTranslationPath( $domain, $locale, true );
		if ( is_string( $defaultPath ) ) {
			$files[] = $defaultPath;
		}

		return $files;
	}
}
