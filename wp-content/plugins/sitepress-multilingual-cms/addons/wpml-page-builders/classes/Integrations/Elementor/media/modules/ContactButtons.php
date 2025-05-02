<?php

namespace WPML\PB\Elementor\Media\Modules;

class ContactButtons extends \WPML_Elementor_Media_Node {

	/**
	 * @param array  $settings
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @return mixed
	 */
	public function translate( $settings, $target_lang, $source_lang ) {
		if ( isset( $settings['top_bar_image']['id'] ) ) {
			$translatedId = $this->media_translate->translate_id( (int) $settings['top_bar_image']['id'], $target_lang );

			if ( $translatedId !== (int) $settings['top_bar_image']['id'] ) {
				$settings['top_bar_image']['id']  = $translatedId;

				$settings['top_bar_image']['url'] = $this->media_translate->translate_image_url(
					$settings['top_bar_image']['url'],
					$target_lang,
					$source_lang
				);

				$data = wp_prepare_attachment_for_js( $translatedId );

				$settings['top_bar_image']['alt'] = $data['alt'];
			}
		}

		return $settings;
	}
}
