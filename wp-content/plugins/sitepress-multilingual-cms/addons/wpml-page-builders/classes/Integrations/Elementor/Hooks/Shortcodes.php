<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\LIB\WP\Hooks;

use function WPML\FP\spreadArgs;

class Shortcodes implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_shortcode_content_for_translation', 10, 2 )
			->then( spreadArgs( [ $this, 'removePreviewContent' ] ) );
	}

	/**
	 * @param string $content
	 * @param int    $postId
	 *
	 * @return string
	 */
	public function removePreviewContent( $content, $postId ) {
		return \WPML_Elementor_Data_Settings::is_edited_with_elementor( $postId ) ? '' : $content;
	}

}
