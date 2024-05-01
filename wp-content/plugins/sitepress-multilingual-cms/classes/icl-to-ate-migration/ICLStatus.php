<?php

namespace WPML\ICLToATEMigration;

use WPML\FP\Relation;
use WPML\TM\API\TranslationServices;

class ICLStatus {

	const ICL_NAME = 'ICanLocalize';
	const ICL_ID   = 67;
	const SUID     = 'dd17d48516ca4bce0b83043583fabd2e';

	/** @var TranslationServices */
	private $translationServices;

	/**
	 * @param TranslationServices $translationServices
	 */
	public function __construct( TranslationServices $translationServices ) {
		$this->translationServices = $translationServices;
	}

	/**
	 * @return bool
	 */
	public function isActivated() {
		return $this->translationServices->getCurrentService() &&
		       Relation::propEq( 'name', self::ICL_NAME, $this->translationServices->getCurrentService() );
	}

	/**
	 * @return bool
	 */
	public function isActivatedAndAuthorized() {
		return self::isActivated() && $this->translationServices->isAuthorized();
	}
}
