<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class ManageBasket extends Manage {

	/** @var Basket $translation_basket */
	private $translation_basket;

	public function __construct(
		Blocks $blocks,
		Translation $translation,
		Basket $translation_basket
	) {
		parent::__construct( $blocks, $translation );
		$this->translation_basket = $translation_basket;
	}

	/**
	 * @param array $data
	 */
	public function addBlocks( array $data ) {
		if ( ! isset( $data['post'], $data['translate_from'], $data['tr_action'] ) ) {
			return;
		}

		$post_elements = $this->extractAddedPostElements( $data );
		$blocks        = $this->getBlocksFromPostElements( $post_elements );
		$blocks        = $this->getBlockElementsToAdd( $blocks )->toArray();

		if ( $blocks ) {
			$basket_portion = [
				'post'              => [],
				'source_language'	=> $data['translate_from'],
				'target_languages'	=> array_keys( $data['tr_action'] ),
			];

			foreach ( $blocks as $block ) {
				$basket_portion['post'][ $block->block_id ] = [
					'from_lang'  => $block->source_lang,
					'to_langs'   => $block->target_langs,
					'auto_added' => true, // This is an optional flag we can use when displaying the basket
				];
			}

			$this->translation_basket->update_basket( $basket_portion );
		}
	}

	/**
	 * @param array $data
	 *
	 * @return \WPML\Collect\Support\Collection
	 */
	private function extractAddedPostElements( array $data ) {
		$source_lang  = $data['translate_from'];
		$target_langs = \wpml_collect( $data['tr_action'] )
			->filter( function( $translate ) { return $translate; } )
			->map( function( $translate ) { return (int) $translate; } )
			->toArray();

		return \wpml_collect( $data['post'] )->map(
			function( $item ) use ( $source_lang, $target_langs ) {
				if (
					! isset( $item['checked'], $item['type'] )
					|| 'post' !== $item['type']
				) {
					return null;
				}

				$target_langs = $this->filterTargetLangs( $target_langs, $item['checked'] );

				if ( ! $target_langs ) {
					return null;
				}

				return new BasketElement(
					(int) $item['checked'],
					$source_lang,
					$target_langs
				);
			}
		)->filter();
	}

	/**
	 * @param array $target_langs
	 * @param int   $post_id
	 *
	 * @return array
	 */
	private function filterTargetLangs( array $target_langs, $post_id ) {
		$post_source_lang = $this->translation->getSourceLang( $post_id );

		return \wpml_collect( $target_langs )->reject(
			function( $unused, $target_lang ) use ( $post_source_lang ) {
				return $target_lang === $post_source_lang;
			}
		)->toArray();
	}
}
