<?php

namespace WPML\MediaTranslation;

use WPML\Media\Option;

class AddMediaDataToTranslationPackage implements \IWPML_Backend_Action {

	const ALT_PLACEHOLDER = '{%ALT_TEXT%}';
	const CAPTION_PLACEHOLDER = '{%CAPTION%}';

	/** @var PostWithMediaFilesFactory $post_media_factory */
	private $post_media_factory;

	public function __construct( PostWithMediaFilesFactory $post_media_factory ) {
		$this->post_media_factory = $post_media_factory;
	}

	public function add_hooks() {
		if ( Option::getTranslateMediaLibraryTexts() ) {
			add_filter( 'wpml_tm_translation_job_data', [ $this, 'add_media_strings' ], PHP_INT_MAX, 2 );
		}
	}

	public function add_media_strings( $package, $post ) {
		$bundled_media_data = $this->get_bundled_media_to_translate( $post );
		if ( $bundled_media_data ) {

			foreach ( $bundled_media_data as $attachment_id => $data ) {
				foreach ( $data as $field => $value ) {
					$options = [
						'translate' => 1,
						'data'      => base64_encode( $value ),
						'format'    => 'base64',
					];
					$package['contents'][ 'media_' . $attachment_id . '_' . $field ] = $options;
				}

				$package['contents'][ 'should_translate_media_image_' . $attachment_id ] = [
					'translate' => 0,
					'data'      => true,
					'format'    => '',
				];
			}

			$package = $this->add_placeholders_for_duplicate_fields( $package, $bundled_media_data );
		}

		return $package;
	}

	private function get_bundled_media_to_translate( $post ) {

		$post_media         = $this->post_media_factory->create( $post->ID );
		$bundled_media_data = [];

		foreach ( $post_media->get_media_ids() as $attachment_id ) {
			$attachment = get_post( $attachment_id );

			if ( $attachment->post_title ) {
				$bundled_media_data[ $attachment_id ]['title'] = $attachment->post_title;
			}
			if ( $attachment->post_excerpt ) {
				$bundled_media_data[ $attachment_id ]['caption'] = $attachment->post_excerpt;
			}
			if ( $attachment->post_content ) {
				$bundled_media_data[ $attachment_id ]['description'] = $attachment->post_content;
			}
			if ( $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
				$bundled_media_data[ $attachment_id ]['alt_text'] = $alt;
			}
		}

		return $bundled_media_data;

	}

	private function add_placeholders_for_duplicate_fields( $package, $bundled_media_data ) {

		$caption_parser = new MediaCaptionTagsParse();

		foreach ( $package['contents'] as $field => $data ) {
			if ( $data['translate'] && 'base64' === $data['format'] ) {
				$original = $content = base64_decode( $data['data'] );

				$captions = $caption_parser->get_captions( $content );

				foreach ( $captions as $caption ) {
					$caption_id        = $caption->get_id();
					$caption_shortcode = $new_caption_shortcode = $caption->get_shortcode_string();

					if ( isset( $bundled_media_data[ $caption_id ] ) ) {

						if ( isset( $bundled_media_data[ $caption_id ]['caption'] ) && $bundled_media_data[ $caption_id ]['caption'] === $caption->get_caption() ) {
							$new_caption_shortcode = $this->replace_caption_with_placeholder( $new_caption_shortcode, $caption );
						}

						if ( isset( $bundled_media_data[ $caption_id ]['alt_text'] ) && $bundled_media_data[ $caption_id ]['alt_text'] === $caption->get_image_alt() ) {
							$new_caption_shortcode = $this->replace_alt_text_with_placeholder( $new_caption_shortcode, $caption );
						}

						if ( $new_caption_shortcode !== $caption_shortcode ) {
							$content = str_replace( $caption_shortcode, $new_caption_shortcode, $content );
						}
					}
				}

				if ( $content !== $original ) {
					$package['contents'][ $field ]['data'] = base64_encode( $content );
				}
			}
		}

		return $package;
	}

	private function replace_caption_with_placeholder( $caption_shortcode, MediaCaption $caption ) {
		$caption_content     = $caption->get_content();
		$search_pattern      = '/(>\s?)(' . preg_quote( $caption->get_caption(), '/' ) . ')/';
		$new_caption_content = preg_replace( $search_pattern, '$1' . self::CAPTION_PLACEHOLDER, $caption_content, 1 );

		return str_replace( $caption_content, $new_caption_content, $caption_shortcode );

	}

	private function replace_alt_text_with_placeholder( $caption_shortcode, MediaCaption $caption ) {

		$alt_text = $caption->get_image_alt();

		return str_replace( 'alt="' . $alt_text . '"', 'alt="' . self::ALT_PLACEHOLDER . '"', $caption_shortcode );
	}
}
