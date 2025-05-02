<?php

namespace WPML\PB\BeaverBuilder\Hooks;

use FLBuilderModel;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Cache implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onAction( 'wpml_pro_translation_completed' )
			->then( spreadArgs( [ $this, 'flush' ] ) );
	}

	/**
	 * @param int $postId
	 */
	public function flush( $postId ) {
		// @phpstan-ignore-next-line
		if ( class_exists( FLBuilderModel::class ) && method_exists( FLBuilderModel::class, 'delete_all_asset_cache' ) ) {
			FLBuilderModel::delete_all_asset_cache( $postId );
		}
	}

}
