<?php

namespace WPML\PB\Gutenberg\Hooks;

use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;

use function WPML\FP\spreadArgs;

class TranslationJobImages implements \IWPML_REST_Action, \WPML\PB\Gutenberg\Integration {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_replace_sequence_with_attachment_id', 10, 2 )
			->then( spreadArgs( [ $this, 'getAttachmentId' ] ) );
		Hooks::onFilter( 'wpml_pb_image_module_patterns' )
			->then( spreadArgs( Lst::append( '/(?:image-|media-text-)(\d+)$/' ) ) );
	}

	/**
	 * @param int   $sequence
	 * @param mixed $block
	 *
	 * @return int
	 */
	public function getAttachmentId( $sequence, $block ) {
		if ( ! is_a( $block, \WP_Block_Parser_Block::class ) ) {
			return $sequence;
		}

		return (int) ( $block->attrs['id'] ?? $block->attrs['mediaId'] ?? $sequence );
	}

}
