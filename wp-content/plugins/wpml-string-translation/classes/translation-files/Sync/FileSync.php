<?php

namespace WPML\ST\TranslationFile\Sync;

use WPML\ST\TranslationFile\Manager;
use WPML_ST_Translations_File_Locale;

class FileSync {

	/** @var Manager */
	private $manager;

	/** @var TranslationUpdates */
	private $translationUpdates;

	/** @var WPML_ST_Translations_File_Locale */
	private $fileLocale;

	public function __construct(
		Manager $manager,
		TranslationUpdates $translationUpdates,
		WPML_ST_Translations_File_Locale $FileLocale
	) {
		$this->manager            = $manager;
		$this->translationUpdates = $translationUpdates;
		$this->fileLocale         = $FileLocale;
	}

	/**
	 * Before to load the custom translation file, we'll:
	 * - Re-generate it if it's missing or outdated.
	 * - Delete it if we don't have custom translations.
	 *
	 * We will also sync the custom file when a native file is passed
	 * because the custom file might never be loaded if it's missing.
	 *
	 * @param string|false $filePath
	 * @param string       $domain
	 */
	public function sync( $filePath, $domain ) {
		if ( ! $filePath ) {
			return;
		}

		$locale        = $this->fileLocale->get( $filePath, $domain );
		$filePath      = $this->getCustomFilePath( $filePath, $domain, $locale );
		$lastDbUpdate  = $this->translationUpdates->getTimestamp( $domain, $locale );
		$fileTimestamp = file_exists( $filePath ) ? filemtime( $filePath ) : 0;

		if ( 0 === $lastDbUpdate ) {
			if ( $fileTimestamp ) {
				$this->manager->remove( $domain, $locale );
			}
		} elseif ( $fileTimestamp < $lastDbUpdate ) {
			$this->manager->add( $domain, $locale );
		}
	}



	/**
	 * @param string $filePath
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return string|null
	 */
	private function getCustomFilePath( $filePath, $domain, $locale ) {
		if ( self::isWpmlCustomFile( $filePath ) ) {
			return $filePath;
		}

		return $this->manager->getFilepath( $domain, $locale );
	}

	/**
	 * @param string $file
	 *
	 * @return bool
	 */
	private static function isWpmlCustomFile( $file ) {
		return 0 === strpos( $file, WP_LANG_DIR . '/' . Manager::SUB_DIRECTORY );
	}
}
