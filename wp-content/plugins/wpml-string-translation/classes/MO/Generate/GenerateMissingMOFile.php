<?php

namespace WPML\ST\MO\Generate;

use WPML\ST\MO\File\Builder;
use WPML\ST\MO\File\makeDir;
use WPML\ST\MO\Hooks\LoadMissingMOFiles;
use WPML\ST\TranslationFile\StringsRetrieve;
use WPML\WP\OptionManager;

class MissingMOFile {

	use makeDir;
	const OPTION_GROUP = 'ST-MO';
	const OPTION_NAME  = 'missing-mo-processed';

	/**
	 * @var Builder
	 */
	private $builder;
	/**
	 * @var StringsRetrieve
	 */
	private $stringsRetrieve;
	/**
	 * @var \WPML_Language_Records
	 */
	private $languageRecords;
	/**
	 * @var OptionManager
	 */
	private $optionManager;

	public function __construct(
		\WP_Filesystem_Direct $filesystem,
		Builder $builder,
		StringsRetrieveMOOriginals $stringsRetrieve,
		\WPML_Language_Records $languageRecords,
		OptionManager $optionManager
	) {

		$this->filesystem      = $filesystem;
		$this->builder         = $builder;
		$this->stringsRetrieve = $stringsRetrieve;
		$this->languageRecords = $languageRecords;
		$this->optionManager   = $optionManager;
	}

	/**
	 * @param string $generateMoPath
	 * @param string $domain
	 */
	public function run( $generateMoPath, $domain ) {
		$processed = $this->getProcessed();
		if ( ! $processed->contains( $generateMoPath ) && $this->maybeCreateSubdir() ) {
			$locale  = ( new \WPML_ST_Translations_File_Locale( $generateMoPath, $domain ) )->get();
			$strings = $this->stringsRetrieve->get(
				$domain,
				$this->languageRecords->get_language_code( $locale ),
				false
			);

			if ( ! empty( $strings ) ) {
				$fileContents = $this->builder
					->set_language( $locale )
					->get_content( $strings );

				$this->filesystem->put_contents( $generateMoPath, $fileContents, 0755 & ~umask() );
			}
			$processed->push( $generateMoPath );
			$this->optionManager->set( self::OPTION_GROUP, self::OPTION_NAME, $processed->toArray() );
		}
	}

	public function isNotProcessed( $generateMoPath ) {
		return ! $this->getProcessed()->contains( $generateMoPath );
	}

	public static function getSubdir() {
		return WP_LANG_DIR . LoadMissingMOFiles::MISSING_MO_FILES_DIR;
	}

	/**
	 * @return \WPML\Collect\Support\Collection
	 */
	private function getProcessed() {
		return wpml_collect( $this->optionManager->get( self::OPTION_GROUP, self::OPTION_NAME, [] ) );
	}
}
