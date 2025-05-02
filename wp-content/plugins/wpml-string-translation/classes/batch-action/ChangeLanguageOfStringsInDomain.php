<?php

namespace WPML\ST\BatchAction;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\ST\StringsRepository;

class ChangeLanguageOfStringsInDomain implements IHandler {

	/** @var \SitePress $sitepress */
	private $sitepress;

	/** @var StringsRepository $stringsRepository */
	private $stringsRepository;

	/** @var \WPML_Change_String_Domain_Language_Dialog */
	private $changeLangDialog;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct(
		\SitePress                                 $sitepress,
		StringsRepository                          $stringsRepository,
		\WPML_Change_String_Domain_Language_Dialog $changeLangDialog
	) {
		$this->sitepress         = $sitepress;
		$this->stringsRepository = $stringsRepository;
		$this->changeLangDialog  = $changeLangDialog;
	}

	public function run( Collection $data ) {
		$domain         = $data->get( 'domain', false );
		$batchSize      = $data->get( 'batchSize', 1 );
		$targetLanguage = $data->get( 'targetLanguage', $this->sitepress->get_default_language() );

		if ( $domain === false ) {
			return Either::left( __( 'Error: please try again', 'wpml-string-translation' ) );
		}

		$langs     = $this->stringsRepository->getLanguagesUsedInDomains( [ $domain ], [ $targetLanguage ] );
		$stringIds = $this->stringsRepository->getStringIdFromDomainsByLangs( [ $domain ], $langs, $batchSize );

		if ( count( $stringIds ) > 0 ) {
			$this->changeLangDialog->changeLanguageOfStrings($stringIds, $targetLanguage);
		}

		return Either::of(
			[
				'completedCount' => count( $stringIds ),
			]
		);
	}
}
