<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

abstract class Manage {

	/** @var Blocks */
	protected $blocks;

	/** @var Translation */
	protected $translation;

	public function __construct(
		Blocks $blocks,
		Translation $translation
	) {
		$this->blocks      = $blocks;
		$this->translation = $translation;
	}

	/**
	 * @param \WPML\Collect\Support\Collection $blocks
	 *
	 * [
	 *  (object) [
	 *      'block_id'     => 1,
	 *      'target_langs' => ['fr' => 1, 'de' => 1],
	 *      'source_lang'  => 'en',
	 *  ],
	 *  (object) [
	 *      'block_id'     => 2,
	 *      'target_langs' => ['de' => 1],
	 *      'source_lang'  => 'en',
	 *  ],
	 * ]
	 *
	 * @return \WPML\Collect\Support\Collection
	 */
	protected function getBlockElementsToAdd( $blocks ) {
		return $blocks->map( [ $this, 'selectTargetLangs' ] )
			->reject( function( $block ) { return empty( $block->target_langs ); } );
	}

	/**
	 * @param \WPML\Collect\Support\Collection $post_elements
	 *
	 * @return \WPML\Collect\Support\Collection
	 */
	protected function getBlocksFromPostElements( \WPML\Collect\Support\Collection $post_elements ) {
		return $post_elements->map( [ $this, 'findBlocksInElement' ] )
		                     ->flatten( 1 )
		                     ->unique( 'block_id' );
	}

	/**
	 * @param \WPML_TM_Translation_Batch_Element|BasketElement $element
	 *
	 * @return array
	 */
	public function findBlocksInElement( $element ) {
		if (
			! $element instanceof \WPML_TM_Translation_Batch_Element
			&& ! $element instanceof BasketElement
		) {
			throw new \RuntimeException( '$element must be an instance of \WPML_TM_Translation_Batch_Element or Reusable_Blocks_Basket_Element.' );
		}

		if ( $element->get_element_type() !== 'post' ) {
			return [];
		}

		return \wpml_collect( $this->blocks->getChildrenIdsFromPost( $element->get_element_id() ) )
			->map( function( $block_id ) use ( $element ) {
				return (object) [
					'block_id'      => $block_id,
					'source_lang'   => $element->get_source_lang(),
					'target_langs'  => $element->get_target_langs(),
				];
			} )->toArray();
	}

	/**
	 * @param int    $block_id
	 * @param string $target_lang
	 *
	 * @return bool
	 */
	protected function requiresTranslation( $block_id, $target_lang ) {
		$needs_job     = true;
		$translated_id = $this->translation->convertBlockId( $block_id, $target_lang );

		if ( $translated_id !== $block_id ) {
			$needs_job = (bool) wpml_get_post_status_helper()->needs_update( $translated_id );
		}

		return $needs_job;
	}

	/**
	 * We will remove target langs that do not require a job
	 * for the reusable block.
	 *
	 * @param \stdClass $block
	 *
	 * @return \stdClass
	 */
	public function selectTargetLangs( \stdClass $block ) {
		$block->target_langs = wpml_collect( $block->target_langs )
			->filter( function ( $unused, $target_lang )  use ( $block ) {
				return $this->requiresTranslation( $block->block_id, $target_lang );
			} )
			->toArray();

		return $block;
	}
}
