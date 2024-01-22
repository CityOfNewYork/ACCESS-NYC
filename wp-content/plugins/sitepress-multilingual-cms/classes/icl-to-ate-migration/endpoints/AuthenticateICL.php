<?php

namespace WPML\ICLToATEMigration\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Json;
use WPML\FP\Logic;
use WPML\ICLToATEMigration\ICLStatus;
use WPML\TM\API\TranslationServices;
use WPML\TM\TranslationProxy\Services\AuthorizationFactory;

class AuthenticateICL implements IHandler {

	/** @var TranslationServices */
	private $translationServices;

	/** @var ICLStatus */
	private $iclStatus;

	/**
	 * @param TranslationServices $translationServices
	 */
	public function __construct( TranslationServices $translationServices ) {
		$this->translationServices = $translationServices;
		$this->iclStatus           = new ICLStatus( $translationServices );
	}


	public function run( Collection $data ) {
		return Either::of( $data->get( 'token' ) )
		             ->chain( Logic::ifElse(
			             Fns::identity(),
			             Either::of(),
			             Fns::always( Either::left( __( 'Token is not defined', 'sitepress-multilingual-cms' ) ) )
		             ) )
		             ->chain( Logic::ifElse(
			             [ $this->iclStatus, 'isActivatedAndAuthorized' ],
			             Fns::always( Either::left( __( 'ICanLocalize.com is already active and authorized', 'sitepress-multilingual-cms' ) ) ),
			             Either::of()
		             ) )
		             ->map( Logic::ifElse(
			             $this->isAnotherServiceCurrentlyActive(),
			             Fns::tap( [ $this->translationServices, 'deselect' ] ),
			             Fns::identity()
		             ) )
		             ->chain( Logic::ifElse(
			             [ $this->iclStatus, 'isActivated' ],
			             Either::of(),
			             function ( $token ) {
				             return $this->translationServices->selectBySUID( ICLStatus::SUID )->map( Fns::always( $token ) );
			             }
		             ) )
		             ->chain( [ $this->translationServices, 'authorize' ] )
		             ->bimap( $this->errorResponse(), $this->successResponse() );
	}

	private function successResponse() {
		return Fns::always( __( 'Service activated.', 'sitepress-multilingual-cms' ) );
	}

	private function errorResponse() {
		return function ( $errorDetails = '' ) {
			return [
				'message' => __( 'The authentication didn\'t work. Please make sure you entered your details correctly and try again.', 'sitepress-multilingual-cms' ),
				'details' => $errorDetails
			];
		};
	}

	private function isAnotherServiceCurrentlyActive() {
		return function () {
			return $this->translationServices->isAnyActive() && ! $this->iclStatus->isActivated();
		};
	}
}
