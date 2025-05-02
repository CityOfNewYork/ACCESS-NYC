<?php

namespace WPML\Compatibility\WPBakery\Hooks;

use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\PB\TranslationJob\Groups;

use function WPML\FP\spreadArgs;

class TranslationJobImages implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_shortcode_string_title', 10, 2 )
			->then( spreadArgs( [ $this, 'filterStringTitle' ] ) );
		Hooks::onFilter( 'wpml_pb_image_module_patterns' )
			->then( spreadArgs( Lst::append( '/(?:hoverbox|single_image)-(\d+)-\d+$/' ) ) );
	}

	/**
	 * @param string $title
	 * @param array  $shortcode
	 *
	 * @return string
	 */
	public function filterStringTitle( $title, $shortcode ) {
		$atts = shortcode_parse_atts( '[dummy ' . $shortcode['attributes'] . ']' );
		if ( isset( $atts['image'] ) && Groups::isGroupLabel( $title ) ) {
			$title = Groups::appendImageIdToGroupLabel( $title, $atts['image'] );
		}

		return $title;
	}
}
