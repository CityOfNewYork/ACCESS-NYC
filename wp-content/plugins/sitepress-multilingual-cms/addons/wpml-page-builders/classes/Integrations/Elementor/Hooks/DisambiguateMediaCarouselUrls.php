<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\LIB\WP\Hooks as WPHooks;

use function WPML\FP\spreadArgs;

class DisambiguateMediaCarouselUrls implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		WPHooks::onFilter( 'wpml_pb_elementor_register_string_name_media-carousel', 10, 2 )
			->then( spreadArgs( [ $this, 'getStringName' ] ) );
	}

	/**
	 * @param string $name
	 * @param array  $args
	 *
	 * @return string
	 */
	public function getStringName( $name, $args ) {
		return $args['element']['widgetType'] . '-' . $args['key'] . '-' . $args['field'] . '-' . $args['nodeId'] . '-' . $args['item']['_id'];
	}
}
