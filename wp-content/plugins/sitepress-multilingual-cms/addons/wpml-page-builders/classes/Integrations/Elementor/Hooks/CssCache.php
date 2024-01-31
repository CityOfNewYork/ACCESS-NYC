<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class CssCache implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	const TEMPLATES_POST_TYPE = 'elementor_library';

	/**
	 * @return void
	 */
	public function add_hooks() {
		Hooks::onAction( 'wpml_translation_job_saved' )
			->then( spreadArgs( [ self::class, 'flushCache' ] ) );
	}

	/**
	 * @param int $postId
	 *
	 * @return void
	 */
	public static function flushCache( $postId ) {
		$post = get_post( $postId );
		if ( self::TEMPLATES_POST_TYPE === Obj::prop( 'post_type', $post ) ) {
			try {
				$fileManager = \WPML\Container\make( \Elementor\Core\Files\Manager::class );
				if ( $fileManager && method_exists( $fileManager, 'clear_cache' ) ) {
					$fileManager->clear_cache();
				}
			} catch ( \Exception $e ) {}
		}
	}
}
