<?php

namespace WPML\TranslationMode\Endpoint;

use WPML\Ajax\IHandler;
use WPML\API\PostTypes;
use WPML\Collect\Support\Collection;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Right;
use WPML\LIB\WP\User;
use WPML\Setup\Option;
use function WPML\FP\partialRight;

class SetTranslateEverything implements IHandler {

	public function run( Collection $data ) {
		if ( $data->has( 'translateEverything' ) ) {
			Option::setTranslateEverything( $data->get( 'translateEverything' ) );
			do_action( 'wpml_set_translate_everything', $data->get( 'translateEverything' ) );
		}

		if ( $data->has( 'onlyNew' ) ) {
			$markAsComplete = partialRight(
				[ Option::class, 'markPostTypeAsCompleted' ],
				$data->get( 'onlyNew', false ) ? Languages::getSecondaryCodes() : []
			);
			Fns::map( Fns::unary( $markAsComplete ), PostTypes::getAutomaticTranslatable() );
		}

		if ( $data->has( 'reviewMode' ) ) {
			Option::setReviewMode( $data->get( 'reviewMode' ) );
		}

		return Right::of( true );
	}
}
