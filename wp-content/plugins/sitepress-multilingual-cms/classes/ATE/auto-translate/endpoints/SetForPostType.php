<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\Settings\PostType\Automatic;
use WPML\Setup\Option;
use WPML\Element\API\Languages;

class SetForPostType {
	public function run( Collection $data ) {
		$postTypes = $data->get( 'postTypes' );
		$automatic = (bool) $data->get( 'automatic' );
		$onlyNew   = (bool) $data->get( 'onlyNew' );

		foreach ( $postTypes as $type ) {
			Automatic::set( $type, $automatic );

			if ( $automatic && $onlyNew ) {
				// Only future content should be translated.
				Option::markPostTypeAsCompleted(
					$type,
					Languages::getSecondaryCodes()
				);
				continue;
			}

			// Not automatic or existing data should also be translated.
			// => Remove the flag that the post type was already translated.
			Option::removePostTypeFromCompleted( $type );
		}

		return Either::of( true );
	}
}
