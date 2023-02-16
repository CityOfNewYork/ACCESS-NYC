<?php

namespace WPML\Media\Translate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Right;

class FinishMediaTranslation implements IHandler {
	public function run( Collection $data ) {
		make( \WPML_Media_Attachments_Duplication::class )->batch_mark_processed( false );
		return Right::of( true );
	}
}
