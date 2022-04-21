<?php

namespace WPML\PB\AutoUpdate;

use WPML\FP\Maybe;
use WPML_Post_Element;
use function WPML\Container\make;
use function WPML\FP\invoke;

class TranslationStatus {

	/**
	 * @param WPML_Post_Element $element
	 *
	 * @return int|null
	 */
	public static function get( WPML_Post_Element $element ) {
		return Maybe::fromNullable( make( '\WPML_TM_Translation_Status' ) )
			->map( invoke( 'filter_translation_status' )->with( null, $element->get_trid(), $element->get_language_code() ) )
			->getOrElse( null );
	}
}