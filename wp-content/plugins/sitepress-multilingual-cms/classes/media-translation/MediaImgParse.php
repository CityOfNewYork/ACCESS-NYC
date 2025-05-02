<?php

namespace WPML\MediaTranslation;

use WPML\LIB\WP\Attachment;
use WPML\MediaTranslation\MediaCollector\Collector;

class MediaImgParse {
	private $media = [];
	private $collector;

	/**
	 * @param string $text
	 *
	 * @return array
	 */
	public function get_imgs( $text ) {
		if ( $this->can_parse_blocks( $text ) ) {
			/** @var WP_Block_Parser_Block[] $blocks */
			$blocks = parse_blocks( $text );
			$this->collect_media_in_blocks( $blocks, $this->media );
			Attachment::addToCache( $this->media );
			$images = $this->get_from_css_background_images_in_blocks( $blocks );
		} else {
			$images = $this->get_from_css_background_images( $text );
		}

		$images = array_merge( $this->get_from_img_tags( $text ), $images );
		return $images;
	}

	/**
	 * @param WP_Block_Parser_Block[] $blocks
	 * @param array                   $mediaCollection
	 */
	public function collect_media_in_blocks( $blocks, &$mediaCollection = [] ) {
		if ( $this->collector == null ) {
			$file = __DIR__ . '/media-collector/block-definitions/all.php';

			if ( ! file_exists( $file ) ) {
				return $mediaCollection;
			}

			$this->collector = new Collector();
			$this->collector->addCollectorBlocks(
				require __DIR__ . '/media-collector/block-definitions/all.php'
			);
		}

		$this->collector->collectMediaFromBlocks( $blocks, $mediaCollection );

		return $mediaCollection;
	}

	/**
	 * @param string $text
	 *
	 * @return array
	 */
	private function get_from_img_tags( $text ) {
		$media = wpml_collect( [] );

		$media_elements = [
			'/<img ([^>]+)>/s',
			'/<video ([^>]+)>/s',
			'/<audio ([^>]+)>/s',
		];

		foreach ( $media_elements as $element_expression ) {
			if ( preg_match_all( $element_expression, $text, $matches ) ) {
				$media = $media->merge( $this->getAttachments( $matches ) );
			}
		}

		return $media->toArray();
	}

	private function getAttachments( $matches ) {
		$attachments = [];

		foreach ( $matches[1] as $i => $match ) {
			if ( preg_match_all( '/(\S+)\\s*=\\s*["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/', $match, $attribute_matches ) ) {
				$attributes = [];
				foreach ( $attribute_matches[1] as $k => $key ) {
					$attributes[ $key ] = $attribute_matches[2][ $k ];
				}
				if ( isset( $attributes['src'] ) ) {
					$attachments[ $i ]['attributes']    = $attributes;
					$attachments[ $i ]['attachment_id'] = Attachment::idFromUrl( $attributes['src'] );
				}
			}
		}

		return $attachments;
	}

	/**
	 * @param string $text
	 *
	 * @return array
	 */
	private function get_from_css_background_images( $text ) {
		$images = [];

		if ( preg_match_all( '/<\w+[^>]+style\s?=\s?"[^"]*?background-image:url\(\s?([^\s\)]+)\s?\)/', $text, $matches ) ) {
			foreach ( $matches[1] as $src ) {
				$images[] = [
					'attributes'    => [ 'src' => $src ],
					'attachment_id' => null,
				];
			}
		}

		return $images;
	}

	/**
	 * @param array $blocks
	 *
	 * @return array
	 */
	private function get_from_css_background_images_in_blocks( $blocks ) {
		$images = [];

		foreach ( $blocks as $block ) {
			$block = $this->sanitize_block( $block );

			if ( ! empty( $block->innerBlocks ) ) {
				$inner_images = $this->get_from_css_background_images_in_blocks( $block->innerBlocks );
				$images       = array_merge( $images, $inner_images );
				continue;
			}

			if ( ! isset( $block->innerHTML, $block->attrs->id ) ) {
				continue;
			}

			$background_images = $this->get_from_css_background_images( $block->innerHTML );
			$image             = reset( $background_images );

			if ( $image ) {
				$image['attachment_id'] = $block->attrs->id;
				$images[]               = $image;
			}
		}

		return $images;
	}

	/**
	 * `parse_blocks` does not specify which kind of collection it should return
	 * (not always an array of `WP_Block_Parser_Block`) and the block parser can be filtered,
	 *  so we'll cast it to a standard object for now.
	 *
	 * @param mixed $block
	 *
	 * @return stdClass|WP_Block_Parser_Block
	 */
	private function sanitize_block( $block ) {
		$block = (object) $block;

		if ( isset( $block->attrs ) && ! is_object( $block->attrs ) ) {
			/** Sometimes `$block->attrs` is an object or an array, so we'll use an object */
			$block->attrs = (object) $block->attrs;
		}

		return $block;
	}

	function can_parse_blocks( $string ) {
		return false !== strpos( $string, '<!-- wp:' ) && function_exists( 'parse_blocks' );
	}
}
