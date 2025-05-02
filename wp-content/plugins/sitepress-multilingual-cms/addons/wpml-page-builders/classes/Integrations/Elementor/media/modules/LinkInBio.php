<?php

namespace WPML\PB\Elementor\Media\Modules;

class LinkInBio extends \WPML_Elementor_Media_Node {

	/**
	 * @param array  $settings
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @return mixed
	 */
	public function translate( $settings, $target_lang, $source_lang ) {

		foreach ( [ 'identity_image', 'identity_image_cover' ] as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$settings[ $key ] = $this->convertImageArray( $settings[ $key ], $target_lang, $source_lang );
			}
		}

		foreach ( [ 'cta_link', 'image_links' ] as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$settings[ $key ] = $this->convertArrayOfImages( $settings[ $key ], $key . '_image', $target_lang, $source_lang );
			}
		}

		return $settings;
	}

	/**
	 * @param array  $image
	 * @param string $targetLang
	 * @param string $sourceLang
	 *
	 * @return array
	 */
	private function convertImageArray( $image, $targetLang, $sourceLang ) {
		if ( isset( $image['id'] ) ) {
			$translatedId = $this->media_translate->translate_id( (int) $image['id'], $targetLang );
			
			if ( $translatedId !== (int) $image['id'] ) {
				$image['id'] = $translatedId;
				
				$image['url'] = $this->media_translate->translate_image_url(
					$image['url'],
					$targetLang,
					$sourceLang
				);
				
				$imageData    = wp_prepare_attachment_for_js( $translatedId );
				$image['alt'] = $imageData['alt'];
			}
		}

		return $image;
	}

	/**
	 * @param array  $images
	 * @param string $targetLang
	 * @param string $sourceLang
	 *
	 * @return array
	 */
	private function convertArrayOfImages( $images, $imageKey, $targetLang, $sourceLang ) {
		foreach ( $images as &$image ) {
			if ( isset( $image[ $imageKey ] ) ) {
				$image[ $imageKey ] = $this->convertImageArray( $image[ $imageKey ], $targetLang, $sourceLang );
			}
		}

		return $images;
	}
}
