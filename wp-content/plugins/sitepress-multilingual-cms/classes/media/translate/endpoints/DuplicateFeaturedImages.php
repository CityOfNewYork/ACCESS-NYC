<?php

namespace WPML\Media\Translate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Obj;
use WPML\Media\Option;
use function WPML\Container\make;
use WPML\FP\Right;

class DuplicateFeaturedImages implements IHandler {
	public function run( Collection $data ) {
		if ( ! $this->shouldDuplicateFeaturedImages() ) {
			return Right::of( 0 );
		}

		$numberLeft = $data->get( 'remaining', null );

		return Right::of(
			make( \WPML_Media_Attachments_Duplication::class )->batch_duplicate_featured_images( false, $numberLeft )
		);
	}

	/**
	 * @return bool
	 */
	private function shouldDuplicateFeaturedImages() {
		return (bool) Obj::prop( 'duplicate_featured', Option::getNewContentSettings() );
	}
}
