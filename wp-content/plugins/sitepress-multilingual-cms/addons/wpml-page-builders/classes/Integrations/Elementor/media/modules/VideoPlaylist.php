<?php

namespace WPML\PB\Elementor\Media\Modules;

use WPML\FP\Obj;
use function WPML\FP\partialRight;

class VideoPlaylist extends \WPML_Elementor_Media_Node {

	public function translate( $settings, $target_lang, $source_lang ) {
		$thumbnailLens     = Obj::lensProp( 'thumbnail' );
		$convertImageArray = partialRight( [ $this, 'translate_image_array' ], $target_lang, $source_lang );

		return Obj::assoc(
			'tabs',
			wpml_collect( Obj::propOr( [], 'tabs', $settings ) )
				->map( Obj::over( $thumbnailLens, $convertImageArray ) )
				->toArray(),
			$settings
		);
	}
}