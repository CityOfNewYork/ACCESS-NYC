<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class Basket {
	/** @var \WPML_Translation_Basket $translation_basket */
	private $translation_basket = null;

	public function update_basket( $basket_portion ) {
		if ( ! $this->translation_basket ) {
			$this->translation_basket = \WPML\Container\make( '\WPML_Translation_Basket' );
		}

		return $this->translation_basket->update_basket( $basket_portion );
	}
}

