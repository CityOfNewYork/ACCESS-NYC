<?php


namespace WPML\PB\Elementor\Media\Modules;

class AllNodes extends \WPML_Elementor_Media_Node {

	/** @var array|null */
	private $backgroundKeys;

	/**
	 * @param string $type
	 *
	 * @return array
	 */
	private function getBackgroundKeys( $type ) {
		if ( ! isset( $this->backgroundKeys ) ) {
			/**
			 * Filter the background fields that should be translated.
			 *
			 * @param array $background_fields {
			 *     @type array simple_fields   Fields containing a single image.
			 *     @type array repeater_fields Fields containing multiple images.
			 * }
			 */
			$this->backgroundKeys = apply_filters(
				'wpml_elementor_media_backgrounds_to_translate',
				[
					'simple_fields'   => [
						'_background_image',
						'background_image',
						'background_overlay_image',
						'background_hover_image',
					],
					'repeater_fields' => [
						'background_slideshow_gallery',
					],
				]
			);
		}

		return isset( $this->backgroundKeys[ $type ] ) && is_array( $this->backgroundKeys[ $type ] ) ? $this->backgroundKeys[ $type ] : [];
	}

	/**
	 * @param array  $settings
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @return array
	 */
	public function translate( $settings, $target_lang, $source_lang ) {
		foreach ( $this->getBackgroundKeys( 'simple_fields' ) as $background ) {
			$settings = $this->translate_image_property( $settings, $background, $target_lang, $source_lang );
		}

		foreach ( $this->getBackgroundKeys( 'repeater_fields' ) as $background ) {
			$settings = $this->translate_images_property( $settings, $background, $target_lang, $source_lang );
		}

		return $settings;
	}

}
