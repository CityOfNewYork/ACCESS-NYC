<?php

namespace WPML\ST\MO\Hooks;

use WPML\ST\MO\JustInTime\MO;
use WPML\ST\MO\LoadedMODictionary;
use WPML\ST\TranslationFile\Domains;
use WPML\ST\MO\File\Manager;

class CustomTextDomains implements \IWPML_Action {

	/** @var Manager $manager */
	private $manager;

	/** @var Domains $domains */
	private $domains;

	/** @var LoadedMODictionary $loadedDictionary */
	private $loadedDictionary;

	public function __construct(
		Manager $file_manager,
		Domains $domains,
		LoadedMODictionary $loadedDictionary
	) {
		$this->manager          = $file_manager;
		$this->domains          = $domains;
		$this->loadedDictionary = $loadedDictionary;
	}

	public function add_hooks() {
		$locale = get_locale();

		foreach ( $this->domains->getCustomMODomains() as $domain ) {
			$filePath = $this->manager->getFilepath( $domain, $locale );
			$this->loadedDictionary->addFile( $domain, $filePath );

			if ( is_readable( $filePath ) ) {
				$this->addJitMoToL10nGlobal( $locale, $domain );
			}
		}
	}

	/**
	 * @param string $locale
	 * @param string $domain
	 */
	public function addJitMoToL10nGlobal( $locale, $domain ) {
		$GLOBALS['l10n'][ $domain ] = new MO( $this->loadedDictionary, $locale, $domain );
	}
}
