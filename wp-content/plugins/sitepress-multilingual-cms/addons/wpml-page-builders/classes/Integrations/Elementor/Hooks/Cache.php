<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Cache implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	const TEMPLATES_POST_TYPE = 'elementor_library';
	const ELEMENT_CACHE_KEY   = '_elementor_element_cache';

	/**
	 * @return void
	 */
	public function add_hooks() {
		Hooks::onAction( 'wpml_translation_job_saved' )
			->then( spreadArgs( [ $this, 'flushAll' ] ) );
		Hooks::onAction( 'wpml_pb_translations_auto_updated' )
			->then( spreadArgs( [ $this, 'flushAll' ] ) );
	}

	/**
	 * @param int $postId
	 *
	 * @return void
	 */
	public function flushAll( $postId ) {
		$this->flushCss( $postId );
		$this->flushElement( $postId );
	}

	/**
	 * @param int $postId
	 *
	 * @return void
	 */
	private function flushCss( $postId ) {
		$post = get_post( $postId );
		if ( self::TEMPLATES_POST_TYPE === Obj::prop( 'post_type', $post ) ) {
			try {
				$fileManager = \WPML\Container\make( \Elementor\Core\Files\Manager::class );
				if ( $fileManager && method_exists( $fileManager, 'clear_cache' ) ) {
					$fileManager->clear_cache();
				}
			} catch ( \Exception $e ) {
				// Do nothing.
			}
		}
	}

	/**
	 * @param int $postId
	 *
	 * @return void
	 */
	private function flushElement( $postId ) {
		delete_metadata( 'post', $postId, self::ELEMENT_CACHE_KEY );
	}

}
