<?php

namespace WPML\ST\BatchAction;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Either;
use WPML\ST\StringsRepository;

/*
 * This class is used to initialise change of the string language for all strings in particular domain.
 * This is executed with pagination by N strings per request.
 * So, in this class we calculate total rows count which equals to strings count which we want to change.
 * We do it by selecting how many rows in strings table for particular domain exists without rows in target language.
 * We return that result to frontend to calculate how many requests should be made in total by pages.
 * We also update a language of packages which contain strings from given domain as well as setting language for domain.
 *
 */
class InitChangeStringLangOfDomain implements IHandler {

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
		$targetLanguage = $data->get( 'targetLanguage', $this->sitepress->get_default_language() );

		if ( $domain === false ) {
			return Either::left( __( 'Error: please try again', 'wpml-string-translation' ) );
		}

		$langs = $this->stringsRepository->getLanguagesUsedInDomains( [ $domain ], [ $targetLanguage ] );
		$count = $this->stringsRepository->getCountInDomainsByLangs( [ $domain ], $langs );

		$this->changeLangDialog->changeLanguageOfStringsInPackages( $domain, $langs, $targetLanguage );
		$this->changeLangDialog->setLanguageOfDomain( $domain, $targetLanguage );

		return Either::of( [
			'totalItemsCount' => $count,
		] );
	}
}

