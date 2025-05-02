<?php

namespace WPML\TranslationMode\Endpoint;

use WPML\Ajax\IHandler;
use WPML\API\PostTypes;
use WPML\Collect\Support\Collection;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Right;
use WPML\Setup\Option;
use function WPML\FP\partialRight;

/**
 * @depecated
 * @todo Remove this class
 */
class SetTranslateEverything implements IHandler {

	public function run( Collection $data ) {
		if ( $data->has( 'translateEverything' ) ) {
			$useTranslateEverything = $data->get( 'translateEverything' );

			Option::setTranslateEverything( $useTranslateEverything );
			do_action( 'wpml_set_translate_everything', $useTranslateEverything );
		}

		if ( $data->has( 'reviewMode' ) ) {
			Option::setReviewMode( $data->get( 'reviewMode' ) );
		}

		if ( $data->has( 'whoMode' ) ) {
			Option::setTranslationMode( $data->get( 'whoMode' ) );
		}

		if ( $data->has( 'translateEverythingDrafts' ) ) {
			Option::setTranslateEverythingDrafts( $data->get( 'translateEverythingDrafts' ) );
		}

		return Right::of( true );
	}
}
