<?php


namespace WPML\PB\Elementor\Media\Modules;


class AllNodes extends \WPML_Elementor_Media_Node {

	/**
	 * @param array $settings
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @return array
	 */
	public function translate( $settings, $target_lang, $source_lang ) {
		$backgrounds = [
			'_background_image',
			'background_image',
			'background_overlay_image',
			'background_hover_image'
		];

		foreach ( $backgrounds as $background ) {
				$settings = $this->translate_image_property( $settings, $background , $target_lang,
					$source_lang );
		}

		return $settings;
	}
}