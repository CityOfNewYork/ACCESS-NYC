<?php


namespace WPML\ST\MO\Hooks;


use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\ST\MO\File\Manager;
use WPML\ST\MO\Generate\DomainsAndLanguagesRepository;
use function WPML\FP\pipe;

class StringsLanguageChanged implements \IWPML_Action {

	private $domainsAndLanguageRepository;
	private $manager;
	private $getDomainsByStringIds;

	/**
	 * @param DomainsAndLanguagesRepository $domainsAndLanguageRepository
	 * @param Manager                       $manager
	 * @param callable                      $getDomainsByStringIds
	 */
	public function __construct(
		DomainsAndLanguagesRepository $domainsAndLanguageRepository,
		Manager $manager,
		callable $getDomainsByStringIds
	) {
		$this->domainsAndLanguageRepository = $domainsAndLanguageRepository;
		$this->manager                      = $manager;
		$this->getDomainsByStringIds        = $getDomainsByStringIds;
	}


	public function add_hooks() {
		add_action( 'wpml_st_language_of_strings_changed', [ $this, 'regenerateMOFiles' ] );
	}

	public function regenerateMOFiles( array $strings ) {
		$stringDomains = call_user_func( $this->getDomainsByStringIds, $strings );

		$this->domainsAndLanguageRepository
			->get()
			->filter( pipe( Obj::prop( 'domain' ), Lst::includes( Fns::__, $stringDomains ) ) )
			->each( function ( $domainLangPair ) {
				$this->manager->add( $domainLangPair->domain, $domainLangPair->locale );
			} );
	}
}