<?php

namespace WPML\ST\MO\Hooks;

use WPML\FP\Lst;
use WPML\ST\MO\File\Manager;
use WPML\ST\MO\JustInTime\MO;
use WPML\ST\MO\LoadedMODictionary;
use WPML\ST\TranslationFile\Domains;
use function WPML\FP\curryN;
use function WPML\FP\partial;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class CustomTextDomains implements \IWPML_Action {

	/** @var Manager $manager */
	private $manager;

	/** @var Domains $domains */
	private $domains;

	/** @var LoadedMODictionary $loadedDictionary */
	private $loadedDictionary;

	/** @var callable */
	private $syncMissingFile;

	public function __construct(
		Manager $file_manager,
		Domains $domains,
		LoadedMODictionary $loadedDictionary,
		callable $syncMissingFile = null
	) {
		$this->manager          = $file_manager;
		$this->domains          = $domains;
		$this->loadedDictionary = $loadedDictionary;
		$this->syncMissingFile  = $syncMissingFile ?: function () {
		};
	}

	public function add_hooks() {
		$locale = get_locale();

		$getDomainPathTuple = function ( $domain ) use ( $locale ) {
			return [ $domain, $this->manager->getFilepath( $domain, $locale ) ];
		};

		$isReadableFile = function ( $domainAndFilePath ) {
			return is_readable( $domainAndFilePath[1] );
		};

		$addJitMoToL10nGlobal = pipe( Lst::nth( 0 ), function ( $domain ) use ( $locale ) {
			unset( $GLOBALS['l10n'][ $domain ] );
			$GLOBALS['l10n'][ $domain ] = new MO( $this->loadedDictionary, $locale, $domain );
		} );

		\wpml_collect( $this->domains->getCustomMODomains() )
			->map( $getDomainPathTuple )
			->each( spreadArgs( $this->syncMissingFile ) )
			->each( spreadArgs( [ $this->loadedDictionary, 'addFile' ] ) )
			->filter( $isReadableFile )
			->each( $addJitMoToL10nGlobal );
	}
}
