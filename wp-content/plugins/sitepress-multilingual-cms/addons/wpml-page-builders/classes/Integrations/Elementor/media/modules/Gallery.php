<?php

namespace WPML\PB\Elementor\Media\Modules;

use WPML\FP\Obj;
use function WPML\FP\compose;
use function WPML\FP\partialRight;

class Gallery extends \WPML_Elementor_Media_Node_With_Images_Property {
	protected function get_property_name() {
		return 'gallery';
	}

	/**
	 * @param array  $settings
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @return mixed
	 */
	public function translate( $settings, $target_lang, $source_lang ) {
		if ( 'multiple' === Obj::propOr( null, 'gallery_type', $settings ) ) {
			return $this->translateMultipleGalleries( $settings, $target_lang, $source_lang );
		}

		return parent::translate( $settings, $target_lang, $source_lang );
	}

	/**
	 * @param array  $settings
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @return array
	 */
	private function translateMultipleGalleries( $settings, $target_lang, $source_lang ) {
		$multipleGalleriesLens = compose( Obj::lensMappedProp( 'galleries' ), Obj::lensMappedProp( 'multiple_gallery' ) );
		$convertImageArray     = partialRight( [ $this, 'translate_image_array' ], $target_lang, $source_lang );

		return Obj::over( $multipleGalleriesLens, $convertImageArray, $settings );
	}
}