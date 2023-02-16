<?php

namespace WPML\ST\StringsCleanup\Ajax;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\ST\StringsCleanup\UntranslatedStrings;

class RemoveStringsFromDomains implements IHandler {
	const REMOVE_STRINGS_BATCH_SIZE = 500;

	public function run( Collection $data ) {
		$domains = $data->get( 'domains', false );

		if ( $domains !== false ) {

			/** @var UntranslatedStrings $untranslated_strings */
			$untranslated_strings = make( UntranslatedStrings::class );

			return Either::of(
				[
					'total_strings'   => $untranslated_strings->getCountInDomains( $domains ),
					'removed_strings' => $untranslated_strings->remove( Fns::map(
						Obj::prop( 'id' ),
						$untranslated_strings->getFromDomains( $domains, self::REMOVE_STRINGS_BATCH_SIZE )
					) )
				]
			);
		} else {
			return Either::left( __( 'Error: please try again', 'wpml-string-translation' ) );
		}
	}
}
