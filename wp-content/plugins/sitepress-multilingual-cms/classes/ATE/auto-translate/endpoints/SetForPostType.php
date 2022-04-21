<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\Settings\PostType\Automatic;

class SetForPostType {
	public function run( Collection $data ) {
		$postTypes = $data->get( 'postTypes' );
		foreach ( $postTypes as $type => $state ) {
			Automatic::set( $type, (bool) $state );
		}

		return Either::of( true );
	}
}
