<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\User;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class RemoveTranslator extends Remove {

	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {
		// $removeLanguagePairs :: WP_user -> WP_user
		$removeLanguagePairs = Fns::tap( pipe( Obj::prop( 'ID' ), [ make( \WPML_Language_Pair_Records::class ), 'remove_all' ] ) );

		// $resignFromUnfinishedJobs :: WP_user -> WP_user
		$resignFromUnfinishedJobs = Fns::tap( [ make( \TranslationManagement::class ), 'resign_translator_from_unfinished_jobs' ] );

		$runParentRemove = function() use ( $data ) {
			return parent::run( $data );
		};

		return Either::fromNullable( $data->get( 'ID' ) )
		             ->map( User::get() )
		             ->filter( invoke( 'exists' ) )
		             ->map( $removeLanguagePairs )
		             ->map( $resignFromUnfinishedJobs )
		             ->bichain(
		             	pipe( Fns::always( $this->msgUserNotFound() ), Either::left() ),
		             	$runParentRemove
		             )
		             ->map( function( $result ) {
		             	do_action( 'wpml_tm_ate_synchronize_translators' );
		             	return $result;
		             } );
	}

	protected static function getCap() {
		return User::CAP_TRANSLATE;
	}
}
