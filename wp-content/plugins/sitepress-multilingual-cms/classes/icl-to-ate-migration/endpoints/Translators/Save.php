<?php

namespace WPML\ICLToATEMigration\Endpoints\Translators;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;

use WPML\FP\Fns;
use WPML\FP\Left;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Logic;
use WPML\Element\API\Languages;

use WPML\FP\Right;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\partialRight;
use WPML\TranslationRoles\SaveUser;
use WPML_Language_Pair_Records;

use function WPML\FP\pipe;

class Save extends SaveUser {

	const ERROR_MESSAGE_TRANSLATORS 	= 'There was an error when saving the following translators:';
	const SUCCESS_MESSAGE_TRANSLATORS 	= 'The translators were saved successfully.';

	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {
		return Either::of( $data )
			->map( Fns::map( $this->createTranslator() ) )
			->chain( $this->syncAteIfRequired() )
			->chain( $this->handleErrors() );
	}

	/**
	 * Creates a translator and returns either an error or the translator email.
	 *
	 * @return callable(Collection):Either
	 */
	private function createTranslator() {
		return function( $translator ) {
			$handleError = function($error) use ($translator) {
				return [ 'translator' => $translator, 'error' => $error ];
			};
			$translator = \wpml_collect( $translator );

			return self::getUser( \wpml_collect( $translator ) )
				->map( Fns::tap( invoke( 'add_cap' )->with( \WPML\LIB\WP\User::CAP_TRANSLATE ) ) )
				->map( Obj::prop( 'ID' ) )
				->map( Fns::tap( partialRight( [ make( WPML_Language_Pair_Records::class ), 'store_active' ], $translator->get( 'languagePairs' ) ) ) )
				->bimap( $handleError, Fns::always( $translator->get( 'user' )['email'] ) );
		};
	}

	/**
	 * Synchronize ATE translators if one of the results was added.
	 *
	 * @return callable(Either[]):Either[]
	 */
	private function syncAteIfRequired() {
		return function( $translatorResults ) {
			foreach( $translatorResults as $translatorResult ) {
				if ( $translatorResult instanceof Right ) {
					do_action( 'wpml_update_translator' );
					break;
				}
			}
			return $translatorResults;
		};
	}

	/**
	 * If any error happened, it prepares a message and the translators that failed.
	 *
	 * @return callable(Either[]):Either
	 */
	private function handleErrors() {
		return function( $translatorResults ) {
			$getErrorMsg     = pipe( invoke( 'coalesce' )->with( Fns::identity(), Fns::identity() ), invoke( 'get' ) );
			$getErrorDetails = pipe( Fns::filter( Fns::isLeft() ), Fns::map( $getErrorMsg ) );
			$errorDetails    = $getErrorDetails( $translatorResults );

			if ( count( $errorDetails ) ) {
				return Either::left( [
					'message' => __( self::ERROR_MESSAGE_TRANSLATORS, 'sitepress' ),
					'details' => $errorDetails
				] );
			}
			return Either::right( __( self::SUCCESS_MESSAGE_TRANSLATORS, 'sitepress' ) );
		};
	}

}

