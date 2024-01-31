<?php

namespace WPML\Compatibility\Divi\Hooks;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class GutenbergUpdate implements \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_is_post_built_with_shortcodes', 10, 2 )
			->then( spreadArgs( [ $this, 'isPostBuiltWithShortcodes' ] ) );
	}

	/**
	 * @param string   $builtWithShortcodes
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public static function isPostBuiltWithShortcodes( $builtWithShortcodes, $post ) {
		return self::isDiviPost( $post->ID ) || $builtWithShortcodes;
	}

	/**
	 * @param  int $postId
	 *
	 * @return bool
	 */
	private static function isDiviPost( $postId ) {
		return 'on' === get_post_meta( $postId, '_et_pb_use_builder', true );
	}
}
