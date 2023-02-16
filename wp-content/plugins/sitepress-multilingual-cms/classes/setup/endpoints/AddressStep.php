<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\Core\LanguageNegotiation;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Right;
use WPML\FP\Str;
use function WPML\Container\make;
use function WPML\FP\chain;
use function WPML\FP\pipe;
use function WPML\FP\System\sanitizeString;

class AddressStep implements IHandler {

	public function run( Collection $data ) {

		$isDomainMode    = Relation::propEq( 'mode', LanguageNegotiation::DOMAIN_STRING );
		$isDirectoryMode = Relation::propEq( 'mode', LanguageNegotiation::DIRECTORY_STRING );
		$otherWise       = Fns::always( true );

		$handleModes = Logic::cond( [
			[ $isDomainMode, $this->handleDomains() ],
			[ $isDirectoryMode, $this->validateSubdirectoryUrls() ],
			[ $otherWise, Fns::identity() ]
		] );

		return Right::of( $data )
		            ->chain( $handleModes )
		            ->map( Obj::prop( 'mode' ) )
		            ->map( sanitizeString() )
		            ->map( LanguageNegotiation::saveMode() )
		            ->map( Fns::always( 'ok' ) );
	}

	/**
	 * @return callable(Collection) : Either Left(unavailable) | Right(data)
	 */
	private function validateDomains() {
		return function ( $data ) {
			return $this->validate( wpml_collect( $data->get( 'domains' ) ), $data );
		};
	}

	/**
	 * @return callable(Collection) : Either Left(unavailable) | Right(data)
	 */
	private function handleDomains() {
		$saveDomains = Fns::tap( pipe( Obj::prop( 'domains' ), LanguageNegotiation::saveDomains() ) );

		return pipe( $this->validateDomains(), chain( $saveDomains ) );
	}

	/**
	 * @return callable(Collection) : Either Left(unavailable) | Right(data)
	 */
	private function validateSubdirectoryUrls() {
		return function ( Collection $data ) {
			return $this->validate( \wpml_collect( $data->get( 'domains' ) )->map(  Fns::nthArg( 1 ) ), $data );
		};
	}

	/**
	 * @param Collection $domains
	 * @param Collection $data
	 *
	 * @return callable|\WPML\FP\Left|Right
	 */
	private function validate( Collection $domains, Collection $data ) {
		$unavailableUrls = $domains->reject( $this->getValidator( $data ) )->keys()->toArray();

		return $unavailableUrls
			? Either::left( $unavailableUrls )
			: Either::of( $data );
	}

	/**
	 * @param Collection $data
	 *
	 * @return callable(string) : bool
	 */
	private function getValidator( Collection $data ) {
		$validator = Relation::propEq( 'mode', LanguageNegotiation::DIRECTORY_STRING, $data )
			? [ make( \WPML_Lang_URL_Validator::class ), 'validate_langs_in_dirs' ]
			: [ make( \WPML_Language_Domain_Validation::class ), 'is_valid' ];

		return Obj::propOr( false, 'ignoreInvalidUrls', $data )
			? Fns::always( true )
			: Fns::unary( $validator );
	}
}
