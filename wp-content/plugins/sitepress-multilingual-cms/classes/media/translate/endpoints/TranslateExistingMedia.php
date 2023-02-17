<?php

namespace WPML\Media\Translate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Right;

class TranslateExistingMedia implements IHandler {
	public function run( Collection $data ) {
		return Right::of(
			make( \WPML_Media_Attachments_Duplication::class )->batch_translate_media( false )
		);
	}
}
